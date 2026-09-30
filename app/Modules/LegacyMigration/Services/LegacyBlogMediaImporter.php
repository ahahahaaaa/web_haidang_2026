<?php

namespace App\Modules\LegacyMigration\Services;

use App\Modules\LegacyMigration\Exceptions\LegacyImageCountLimitExceeded;
use App\Modules\LegacyMigration\Exceptions\LegacyImageDownloadFailure;
use App\Modules\LegacyMigration\Models\LegacyStagedObject;
use App\Services\Admin\MediaLibraryUploader;
use App\Support\FrontsiteUrls;
use App\Support\RichText;
use DOMDocument;
use DOMElement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Models\BlogPost;

class LegacyBlogMediaImporter
{
    private const PLACEHOLDER_URL = '/images/legacy-migration-placeholder.svg';

    public function __construct(
        private MediaLibraryUploader $uploader,
        private LegacyImageDownloader $downloader,
        private LegacyImageFileNormalizer $normalizer,
    ) {}

    /**
     * @return array{
     *     imported: int,
     *     reused: int,
     *     rewritten: int,
     *     cover_media_id: int|null,
     *     cover_fallback_url: string|null,
     *     skipped: int,
     *     warnings: array<int, array>,
     *     removed_sources: array<int, string>,
     *     replacements: array<string, string>
     * }
     */
    public function prepare(BlogPost $post, LegacyStagedObject $source): array
    {
        $maximumImages = max(1, (int) config('legacy_migration.media.max_images_per_object', 100));
        $skipFailed = (bool) config('legacy_migration.media.skip_failed_images', true);
        $deadline = microtime(true) + max(0, min(40, (int) config('legacy_migration.media.max_download_seconds_per_object', 40)));
        $sourceContent = $this->sourceContent($source);
        $manifest = collect((array) data_get($source->payload_json, 'media', []))
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['url'] ?? null));
        $knownSources = $manifest->flatMap(fn (array $item): array => $this->replacementSources([$item['source'] ?? '', $item['url']]))->all();
        $manifest = $manifest->merge($skipFailed ? collect($this->remoteContentImages($sourceContent))
            ->reject(fn (array $item): bool => in_array($item['source'], $knownSources, true)) : [])
            ->unique(fn (array $item): string => hash('sha256', html_entity_decode(trim((string) $item['url']), ENT_QUOTES | ENT_HTML5, 'UTF-8')))
            ->values();
        $contentEmbedded = collect($this->embeddedContentImages($sourceContent));
        $manifestEmbedded = $manifest
            ->filter(fn (array $item): bool => $this->isEmbeddedImage((string) $item['url']))
            ->map(fn (array $item): array => [
                'source' => trim((string) $item['url']),
                'alt' => trim((string) ($item['alt'] ?? $post->cover_alt ?? $post->title)),
                'field' => trim((string) ($item['field'] ?? 'content')),
                'replacement_sources' => array_values(array_unique(array_filter([
                    trim((string) ($item['source'] ?? '')),
                    trim((string) $item['url']),
                ]))),
            ]);
        $embedded = $contentEmbedded
            ->merge($manifestEmbedded)
            ->unique(fn (array $item): string => hash('sha256', $item['source']))
            ->values();

        if (! $skipFailed && $embedded->count() > $maximumImages) {
            throw new InvalidArgumentException("Nội dung có {$embedded->count()} ảnh nhúng; giới hạn migrate là {$maximumImages} ảnh mỗi bài.");
        }

        $remoteManifest = $manifest->reject(fn (array $item): bool => $this->isEmbeddedImage((string) $item['url']));
        if (! $skipFailed) {
            $remoteManifest = $remoteManifest->take(max(0, $maximumImages - $embedded->count()));
        }
        $decodedEmbedded = [];
        $totalEmbeddedBytes = 0;
        $maximumTotalBytes = $this->maximumEmbeddedTotalBytes();
        $result = ['imported' => 0, 'reused' => 0, 'rewritten' => 0, 'skipped' => 0, 'warnings' => [], 'cover_media_id' => null, 'cover_fallback_url' => null, 'replacements' => [], 'removed_sources' => []];

        foreach ($embedded as $index => $item) {
            try {
                if ($index >= $maximumImages) {
                    throw new LegacyImageCountLimitExceeded;
                }
                $decoded = $this->decodeEmbeddedImage($item['source']);
                $totalEmbeddedBytes += $decoded['bytes'];
                if ($totalEmbeddedBytes > $maximumTotalBytes) {
                    throw new InvalidArgumentException('Tổng dung lượng ảnh nhúng trong nội dung vượt quá giới hạn '.number_format($maximumTotalBytes).' bytes.');
                }
                $decodedEmbedded[] = [...$item, ...$decoded];
            } catch (InvalidArgumentException $exception) {
                $this->skipImage($result, $item['source'], $item['field'], $item['replacement_sources'], $exception);
            }
        }

        $coverCandidate = null;

        foreach ($decodedEmbedded as $item) {
            try {
                [$media, $reused] = $this->storeEmbeddedImage($item, $post, $source);
            } catch (LegacyImageDownloadFailure $exception) {
                $this->skipImage($result, $item['source'], $item['field'], $item['replacement_sources'], $exception);

                continue;
            }
            $result[$reused ? 'reused' : 'imported']++;

            foreach ($item['replacement_sources'] as $oldSource) {
                $result['replacements'][$oldSource] = $media->getUrl();
            }

            if (! $coverCandidate || $this->isCoverField($item['field'])) {
                $coverCandidate = $media;
            }
        }

        foreach ($remoteManifest->values() as $index => $item) {
            $sourceUrl = (string) $item['url'];
            $aliases = $this->replacementSources([$item['source'] ?? '', $sourceUrl]);
            try {
                if ($index + $embedded->count() >= $maximumImages) {
                    throw new LegacyImageCountLimitExceeded;
                }
                $sourceUrl = $this->validatedSourceUrl($sourceUrl);
                $aliases[] = $sourceUrl;
            } catch (InvalidArgumentException $exception) {
                $this->skipImage($result, $sourceUrl, (string) ($item['field'] ?? 'content'), $aliases, $exception);

                continue;
            }
            try {
                [$media, $reused] = $this->remoteMedia($sourceUrl, $post, $source, $skipFailed ? $deadline : null);
            } catch (LegacyImageDownloadFailure $exception) {
                $this->skipImage($result, $sourceUrl, (string) ($item['field'] ?? 'content'), $aliases, $exception);

                continue;
            }
            $result[$reused ? 'reused' : 'imported']++;

            foreach (array_unique($aliases) as $oldUrl) {
                $result['replacements'][$oldUrl] = $media->getUrl();
            }

            if (! $coverCandidate || $this->isCoverField((string) ($item['field'] ?? ''))) {
                $coverCandidate = $media;
            }
        }

        foreach ($result['replacements'] as $oldSource => $replacement) {
            $result['replacements'][htmlspecialchars($oldSource, ENT_QUOTES | ENT_HTML5, 'UTF-8', false)] = $replacement;
        }

        $result['rewritten'] = count(array_filter(
            array_keys($result['replacements']),
            fn (string $oldSource): bool => str_contains($sourceContent, $oldSource),
        ));

        if ($coverCandidate instanceof Media && ! $post->getFirstMedia('cover')) {
            $coverCandidate->copy(
                model: $post,
                collectionName: 'cover',
                diskName: config('media-library.disk_name', 'public'),
                fileAdderCallback: fn ($adder) => $adder->withCustomProperties([
                    ...($coverCandidate->custom_properties ?? []),
                    'alt' => trim((string) ($post->cover_alt ?: $post->title)),
                    'source_library_media_id' => (int) $coverCandidate->getKey(),
                ]),
            );
            $result['cover_media_id'] = (int) $coverCandidate->getKey();
        }

        $cover = $post->unsetRelation('media')->getFirstMedia('cover');
        if (! $coverCandidate && ! $cover && blank($post->cover_image_url)
            && collect($result['warnings'])->contains(fn (array $warning): bool => $warning['content_action'] === 'placeholder')) {
            $result['cover_fallback_url'] = self::PLACEHOLDER_URL;
        }

        if ($coverCandidate instanceof Media && $cover instanceof Media
            && $cover->getCustomProperty('source_library_media_id')
            && $cover->getCustomProperty('legacy_source_object') === $source->object_key
            && ! $this->hasUsableOriginal($cover)
        ) {
            $this->restoreMigratedCover($cover, $coverCandidate);
        }

        if ($coverCandidate instanceof Media && $cover instanceof Media
            && (int) $cover->getCustomProperty('source_library_media_id') === (int) $coverCandidate->getKey()
        ) {
            app(FileManipulator::class)->createDerivedFiles($cover, ['small', 'medium', 'full'], onlyMissing: true);
        }

        return $result;
    }

    private function replacementSources(array $sources): array
    {
        $aliases = [];
        foreach ($sources as $source) {
            $source = trim((string) $source);
            if ($source === '') {
                continue;
            }
            $aliases[] = $source;
            $aliases[] = html_entity_decode($source, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (! $this->isEmbeddedImage($source)) {
                try {
                    $aliases[] = $this->downloader->normalizeUrl($source);
                } catch (InvalidArgumentException) {
                }
            }
        }

        return array_values(array_unique($aliases));
    }

    private function skipImage(array &$result, string $sourceUrl, string $field, array $aliases, InvalidArgumentException $exception): void
    {
        if (! (bool) config('legacy_migration.media.skip_failed_images', true)) {
            throw $exception;
        }
        $embedded = $this->isEmbeddedImage($sourceUrl);
        $remove = ! $embedded && ! ($exception instanceof LegacyImageCountLimitExceeded)
            && ! ($exception instanceof LegacyImageDownloadFailure && in_array($exception->reasonCode, ['object_download_budget', 'ca_bundle', 'curl_77'], true));
        $placeholder = $remove ? null : self::PLACEHOLDER_URL;
        foreach ($aliases as $alias) {
            if ($remove) {
                $result['removed_sources'][] = $alias;
            } else {
                $result['replacements'][$alias] = $placeholder;
            }
        }
        $context = $exception instanceof LegacyImageDownloadFailure ? $exception->context() : [
            'image_source' => $embedded ? 'legacy-inline://'.hash('sha256', $sourceUrl) : LegacyImageDownloadFailure::redactedUrl($sourceUrl),
            'image_error_code' => $exception instanceof LegacyImageCountLimitExceeded ? 'image_count_limit' : 'image_validation',
            'retryable' => false,
            'detail' => null,
        ];
        $result['skipped']++;
        $result['warnings'][] = [
            ...$context,
            'source_hash' => hash('sha256', $sourceUrl),
            'field' => $field,
            'error_text' => mb_substr($exception->getMessage(), 0, 2000),
            'placeholder_url' => $placeholder,
            'content_action' => $remove ? 'removed' : 'placeholder',
        ];
    }

    private function remoteContentImages(string $html): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $images = [];
        foreach ($dom->getElementsByTagName('img') as $image) {
            $url = trim($image->getAttribute('src'));
            if ($url !== '' && ! $this->isEmbeddedImage($url)) {
                $images[] = ['field' => 'content', 'source' => $url, 'url' => $url];
            }
        }

        return $images;
    }

    /** @return array{imported: int, reused: int, rewritten: int, cover_media_id: int|null, skipped: int, warnings: array<int, array>} */
    public function import(BlogPost $post, LegacyStagedObject $source): array
    {
        $prepared = $this->prepare($post, $source);
        $sourceContent = $this->sourceContent($source);
        $containsEmbeddedContent = $this->embeddedContentImages($sourceContent) !== [];
        $content = $containsEmbeddedContent ? $sourceContent : (string) ($post->content ?? '');
        $rewrittenCount = count(array_filter(
            array_keys($prepared['replacements']),
            fn (string $oldSource): bool => str_contains($content, $oldSource),
        ));
        $rewritten = $this->rewriteContent($content, $prepared['replacements'], $prepared['removed_sources']);

        if ($containsEmbeddedContent) {
            $rewritten = FrontsiteUrls::normalizeInternalHtml(RichText::sanitize($rewritten));
        }

        if ($rewritten !== (string) ($post->content ?? '') || $prepared['cover_fallback_url'] !== null) {
            BlogPost::withoutTimestamps(function () use ($post, $rewritten, $prepared): void {
                $post->forceFill(['content' => $rewritten, ...($prepared['cover_fallback_url'] ? ['cover_image_url' => $prepared['cover_fallback_url']] : [])])->save();
            });
        }

        return [
            'imported' => $prepared['imported'],
            'reused' => $prepared['reused'],
            'rewritten' => $rewrittenCount,
            'cover_media_id' => $prepared['cover_media_id'],
            'skipped' => $prepared['skipped'],
            'warnings' => $prepared['warnings'],
        ];
    }

    public function hasEmbeddedContentImages(string $html): bool
    {
        return $this->embeddedContentImages($html) !== [];
    }

    /**
     * @param  array<string, string>  $replacements
     * @param  array<int, string>  $removedSources
     */
    public function rewriteContent(string $html, array $replacements = [], array $removedSources = []): string
    {
        $removedSources = array_diff($removedSources, array_keys($replacements));
        if ($removedSources !== []) {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $previous = libxml_use_internal_errors(true);
            try {
                $dom->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
                $removed = false;
                foreach (iterator_to_array($dom->getElementsByTagName('img')) as $image) {
                    $source = trim($image->getAttribute('src'));
                    if (! in_array($source, $removedSources, true)) {
                        continue;
                    }
                    $node = $image;
                    for ($ancestor = $image->parentNode; $ancestor instanceof DOMElement; $ancestor = $ancestor->parentNode) {
                        if ($ancestor->tagName === 'picture') {
                            $node = $ancestor;

                            break;
                        }
                        if (in_array($ancestor->tagName, ['body', 'html'], true)) {
                            break;
                        }
                    }
                    $parent = $node->parentNode;
                    $parent?->removeChild($node);
                    if ($parent instanceof DOMElement && $parent->tagName === 'a'
                        && trim($parent->textContent) === '' && $parent->getElementsByTagName('*')->length === 0) {
                        $parent->parentNode?->removeChild($parent);
                    }
                    $removed = true;
                }
                if ($removed) {
                    $html = '';
                    foreach ($dom->getElementsByTagName('body')->item(0)?->childNodes ?? [] as $child) {
                        $html .= $dom->saveHTML($child);
                    }
                }
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
        }

        return $replacements === [] ? $html : strtr($html, $replacements);
    }

    /** @return array<int, array{source: string, alt: string, field: string, replacement_sources: array<int, string>}> */
    private function embeddedContentImages(string $html): array
    {
        if (stripos($html, 'data:image/') === false) {
            return [];
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><!DOCTYPE html><html><body>'.$html.'</body></html>',
            LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $images = [];

        foreach ($dom->getElementsByTagName('img') as $image) {
            $source = trim(html_entity_decode($image->getAttribute('src'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if (! $this->isEmbeddedImage($source)) {
                continue;
            }

            $images[] = [
                'source' => $source,
                'alt' => trim($image->getAttribute('alt')),
                'field' => 'content',
                'replacement_sources' => [$source],
            ];
        }

        return $images;
    }

    /** @return array{binary: string, mime: string, extension: string, hash: string, bytes: int, width: int, height: int} */
    private function decodeEmbeddedImage(string $dataUri): array
    {
        if (! preg_match('/\Adata:(image\/(?:jpeg|jpg|png|webp|avif));base64,(.+)\z/is', trim($dataUri), $matches)) {
            throw new InvalidArgumentException('Ảnh nhúng trong nội dung phải là JPEG, PNG, WebP hoặc AVIF dạng base64 hợp lệ.');
        }

        $declaredMime = Str::lower($matches[1]) === 'image/jpg' ? 'image/jpeg' : Str::lower($matches[1]);
        $encoded = preg_replace('/\s+/', '', $matches[2]) ?? '';
        $maximumBytes = $this->maximumEmbeddedImageBytes();
        $maximumEncodedLength = (int) ceil($maximumBytes / 3) * 4 + 4;

        if ($encoded === '' || strlen($encoded) > $maximumEncodedLength) {
            throw new InvalidArgumentException('Một ảnh nhúng trong nội dung bị rỗng hoặc vượt quá giới hạn '.number_format($maximumBytes).' bytes.');
        }

        $binary = base64_decode($encoded, true);

        if (! is_string($binary) || $binary === '' || strlen($binary) > $maximumBytes) {
            throw new InvalidArgumentException('Không thể giải mã ảnh base64 trong nội dung hoặc ảnh vượt quá dung lượng cho phép.');
        }

        $actualMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/avif' => 'avif'];

        if (! isset($extensions[$actualMime]) || $actualMime !== $declaredMime) {
            throw new InvalidArgumentException('MIME khai báo của ảnh nhúng không khớp dữ liệu JPEG, PNG, WebP hoặc AVIF thực tế.');
        }

        $dimensions = @getimagesizefromstring($binary);
        $width = (int) ($dimensions[0] ?? 0);
        $height = (int) ($dimensions[1] ?? 0);
        $dimensionMime = isset($dimensions[2]) ? image_type_to_mime_type((int) $dimensions[2]) : '';
        $maximumPixels = max(1, (int) config('legacy_migration.media.max_embedded_pixels', 24_000_000));

        if ($dimensionMime !== $actualMime || $width < 1 || $height < 1 || $width > intdiv($maximumPixels, $height)) {
            throw new InvalidArgumentException('Ảnh nhúng không hợp lệ hoặc vượt quá giới hạn '.number_format($maximumPixels).' pixels.');
        }

        return [
            'binary' => $binary,
            'mime' => $actualMime,
            'extension' => $extensions[$actualMime],
            'hash' => hash('sha256', $binary),
            'bytes' => strlen($binary),
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * @param  array{binary: string, mime: string, extension: string, hash: string, bytes: int, width: int, height: int, alt: string}  $image
     * @return array{Media, bool}
     */
    private function storeEmbeddedImage(array $image, BlogPost $post, LegacyStagedObject $source): array
    {
        return Cache::lock('legacy-migration:inline-media:'.$image['hash'], 60)->block(10, function () use ($image, $post, $source): array {
            $existing = Media::query()
                ->where('collection_name', 'library')
                ->where('custom_properties->legacy_inline_sha256', $image['hash'])
                ->get()->first(fn (Media $media): bool => $this->hasUsableOriginal($media));

            if ($existing) {
                return [$existing, true];
            }

            $temporary = tempnam(sys_get_temp_dir(), 'legacy-inline-');

            if ($temporary === false || file_put_contents($temporary, $image['binary']) !== $image['bytes']) {
                if (is_string($temporary) && is_file($temporary)) {
                    @unlink($temporary);
                }

                throw new InvalidArgumentException('Không thể tạo tệp tạm cho ảnh nhúng trong nội dung.');
            }

            $normalized = null;

            try {
                $upload = new UploadedFile(
                    $temporary,
                    'legacy-inline-'.$image['hash'].'.'.$image['extension'],
                    $image['mime'],
                    null,
                    true,
                );
                $normalized = $this->normalizeImage(
                    $upload,
                    max(1, (int) config('legacy_migration.media.max_embedded_pixels', 24_000_000)),
                    $this->maximumEmbeddedImageBytes(),
                    'legacy-inline://'.$image['hash'],
                );
                $alt = trim($image['alt']) ?: trim((string) ($post->cover_alt ?: $post->title));
                $media = $this->uploader->uploadToLibrary($normalized, $post->title, $alt, [
                    'legacy_inline_sha256' => $image['hash'],
                    'legacy_source_object' => $source->object_key,
                    'legacy_migration_run_id' => (int) $source->run_id,
                    'legacy_inline_bytes' => $image['bytes'],
                    'legacy_inline_width' => $image['width'],
                    'legacy_inline_height' => $image['height'],
                ]);

                return [$media, false];
            } finally {
                if ($normalized instanceof UploadedFile && $normalized->getPathname() !== $temporary && is_file($normalized->getPathname())) {
                    @unlink($normalized->getPathname());
                }
                if (is_file($temporary)) {
                    @unlink($temporary);
                }
            }
        });
    }

    /** @return array{Media, bool} */
    private function remoteMedia(string $sourceUrl, BlogPost $post, LegacyStagedObject $source, ?float $deadline = null): array
    {
        return Cache::lock('legacy-migration:remote-media:'.hash('sha256', $sourceUrl), 60)->block(10, function () use ($sourceUrl, $post, $source, $deadline): array {
            $candidates = $this->downloadCandidates($sourceUrl);
            foreach ($candidates as $candidate) {
                if ($existing = $this->existingRemoteMedia($candidate)) {
                    return [$existing, true];
                }
            }
            $attempted = [];
            foreach ($candidates as $candidate) {
                $attempted[] = LegacyImageDownloadFailure::redactedUrl($candidate);
                try {
                    return [$this->downloadToLibrary($candidate, $post, $source, $deadline), false];
                } catch (LegacyImageDownloadFailure $exception) {
                    if (in_array($exception->reasonCode, ['object_download_budget', 'ca_bundle', 'curl_77'], true)) {
                        throw $exception;
                    }
                    $failure = $exception;
                }
            }

            throw new LegacyImageDownloadFailure(
                $failure->getMessage(), $failure->sourceUrl, $failure->reasonCode, $failure->retryable,
                mb_substr(($failure->detail ? $failure->detail.' ' : '').'Các nguồn đã thử: '.implode(', ', $attempted), 0, 1000),
                $failure,
            );
        });
    }

    /** @return array<int, string> */
    private function downloadCandidates(string $sourceUrl): array
    {
        $parts = parse_url($sourceUrl);
        $host = Str::lower((string) ($parts['host'] ?? ''));
        $candidates = [$sourceUrl];
        $alternate = match ($host) {
            'tour.org.vn', 'www.tour.org.vn' => 'haidangtravel.com',
            'haidangtravel.com', 'www.haidangtravel.com' => 'tour.org.vn',
            default => null,
        };
        if ($alternate !== null) {
            $url = 'https://'.$alternate.($parts['path'] ?? '').(isset($parts['query']) ? '?'.$parts['query'] : '');
            try {
                $this->downloader->validatedHost($url);
                $candidates[] = $url;
            } catch (InvalidArgumentException) {
            }
        }

        return $candidates;
    }

    private function existingRemoteMedia(string $sourceUrl): ?Media
    {
        return Media::query()
            ->where('collection_name', 'library')
            ->where('custom_properties->legacy_source_url', $sourceUrl)
            ->get()->first(fn (Media $media): bool => $this->hasUsableOriginal($media));
    }

    private function hasUsableOriginal(Media $media): bool
    {
        $disk = Storage::disk($media->disk);
        if (! $disk->exists($media->getPathRelativeToRoot())) {
            return false;
        }

        $temporary = tempnam(sys_get_temp_dir(), 'legacy-existing-');
        if ($temporary === false) {
            throw new InvalidArgumentException('Không tạo được tệp tạm để kiểm tra Media đã migrate.');
        }

        $input = null;
        $output = null;
        $normalized = null;
        $embedded = (bool) $media->getCustomProperty('legacy_inline_sha256');
        $maximumBytes = $embedded ? $this->maximumEmbeddedImageBytes() : $this->downloader->maxBytes();
        $maximumPixels = max(1, (int) config('legacy_migration.media.'.($embedded ? 'max_embedded_pixels' : 'max_remote_pixels'), 24_000_000));

        try {
            $input = $disk->readStream($media->getPathRelativeToRoot());
            $output = fopen($temporary, 'wb');
            if (! is_resource($input) || ! is_resource($output)) {
                return false;
            }

            stream_copy_to_stream($input, $output, $maximumBytes + 1);
            fclose($output);
            $output = null;
            $normalized = $this->normalizer->normalize(new UploadedFile($temporary, $media->file_name, null, null, true), $maximumPixels, $maximumBytes);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        } finally {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                fclose($output);
            }
            if ($normalized instanceof UploadedFile && $normalized->getPathname() !== $temporary && is_file($normalized->getPathname())) {
                @unlink($normalized->getPathname());
            }
            @unlink($temporary);
        }
    }

    private function restoreMigratedCover(Media $cover, Media $libraryMedia): void
    {
        if ($cover->mime_type !== $libraryMedia->mime_type) {
            throw new LegacyImageDownloadFailure('Original cover migrate bị hỏng và ảnh thay thế khác định dạng; cần chọn lại cover trong CMS.', 'legacy-cover://'.$cover->id, 'cover_format');
        }

        $input = Storage::disk($libraryMedia->disk)->readStream($libraryMedia->getPathRelativeToRoot());
        if (! is_resource($input)) {
            throw new LegacyImageDownloadFailure('Không đọc được ảnh Media để phục hồi cover migrate.', 'legacy-cover://'.$cover->id, 'cover_restore');
        }

        try {
            if (! Storage::disk($cover->disk)->put($cover->getPathRelativeToRoot(), $input)) {
                throw new LegacyImageDownloadFailure('Không ghi được original cover migrate khi phục hồi.', 'legacy-cover://'.$cover->id, 'cover_restore');
            }
        } finally {
            fclose($input);
        }

        $cover->forceFill([
            'size' => $libraryMedia->size,
            'custom_properties' => [
                ...($cover->custom_properties ?? []),
                ...($libraryMedia->custom_properties ?? []),
                'alt' => $cover->getCustomProperty('alt'),
                'source_library_media_id' => (int) $libraryMedia->getKey(),
            ],
        ])->save();
        app(FileManipulator::class)->createDerivedFiles($cover, ['small', 'medium', 'full']);
    }

    private function downloadToLibrary(string $sourceUrl, BlogPost $post, LegacyStagedObject $source, ?float $deadline = null): Media
    {
        if ($deadline !== null && microtime(true) >= $deadline) {
            throw new LegacyImageDownloadFailure('Đã hết ngân sách tải ảnh của bài; dùng ảnh tạm để kiểm tra sau.', $sourceUrl, 'object_download_budget', true);
        }
        try {
            $upload = $deadline !== null && $deadline - microtime(true) < 25
                ? $this->downloader->download($sourceUrl, $deadline)
                : $this->downloader->download($sourceUrl);
        } catch (InvalidArgumentException $exception) {
            throw $exception instanceof LegacyImageDownloadFailure ? $exception : new LegacyImageDownloadFailure($exception->getMessage(), $sourceUrl, 'image_validation', previous: $exception);
        }
        $path = $upload->getPathname();
        $normalized = null;

        try {
            $normalized = $this->normalizeImage(
                $upload,
                max(1, (int) config('legacy_migration.media.max_remote_pixels', 24_000_000)),
                $this->downloader->maxBytes(),
                $sourceUrl,
            );
            $baseName = pathinfo((string) parse_url($sourceUrl, PHP_URL_PATH), PATHINFO_FILENAME);
            $name = Str::limit(trim((string) ($baseName ?: $post->title)), 200, '');

            return $this->uploader->uploadToLibrary($normalized, $name, $post->title, [
                'legacy_source_url' => $sourceUrl,
                'legacy_source_object' => $source->object_key,
                'legacy_migration_run_id' => (int) $source->run_id,
            ]);
        } finally {
            if ($normalized instanceof UploadedFile && $normalized->getPathname() !== $path && is_file($normalized->getPathname())) {
                @unlink($normalized->getPathname());
            }
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function sourceContent(LegacyStagedObject $source): string
    {
        $attributes = (array) Arr::get($source->payload_json, 'attributes', []);

        foreach (['content', 'detail', 'description', 'body'] as $key) {
            if (array_key_exists($key, $attributes) && $attributes[$key] !== null && $attributes[$key] !== '') {
                return (string) $attributes[$key];
            }
        }

        return '';
    }

    private function validatedSourceUrl(string $url): string
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            $url = 'https://'.config('legacy_migration.media.download_source_remap_to_host', 'tour.org.vn').$url;
        }
        $url = $this->downloader->normalizeUrl($url);
        $overrides = (array) config('legacy_migration.media.source_url_overrides', []);

        if (array_key_exists($url, $overrides)) {
            if (! is_string($overrides[$url]) || trim($overrides[$url]) === '') {
                throw new InvalidArgumentException('URL ảnh thay thế trong cấu hình migration không hợp lệ.');
            }

            $url = $this->downloader->normalizeUrl($overrides[$url]);
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            throw new InvalidArgumentException('Nguồn ảnh không phải URL hợp lệ.');
        }

        $host = Str::lower((string) ($parts['host'] ?? ''));
        $allowedHosts = collect((array) config('legacy_migration.media.allowed_hosts', []))
            ->map(fn (mixed $item): string => Str::lower(trim((string) $item)))
            ->filter()
            ->all();

        $url = $this->remapDownloadSourceHost($url, $parts);
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        if (! (bool) config('legacy_migration.media.allow_any_public_host', true)
            && ! in_array($host, $allowedHosts, true)) {
            throw new InvalidArgumentException('Nguồn ảnh không thuộc danh sách host migration được phép.');
        }

        return $url;
    }

    /** @param array<string, int|string> $parts */
    private function remapDownloadSourceHost(string $url, array $parts): string
    {
        $host = Str::lower((string) ($parts['host'] ?? ''));
        $fromHosts = collect((array) config('legacy_migration.media.download_source_remap_from_hosts', []))
            ->map(fn (mixed $item): string => Str::lower(trim((string) $item)))
            ->filter()
            ->all();

        if (! in_array($host, $fromHosts, true)) {
            return $url;
        }

        $targetHost = Str::lower(trim((string) config('legacy_migration.media.download_source_remap_to_host', '')));
        $targetParts = parse_url('https://'.$targetHost);

        if ($targetHost === ''
            || ! is_array($targetParts)
            || ($targetParts['host'] ?? null) !== $targetHost
            || isset($targetParts['user'])
            || isset($targetParts['pass'])
            || isset($targetParts['port'])
            || isset($targetParts['path'])
            || isset($targetParts['query'])
            || isset($targetParts['fragment'])) {
            throw new InvalidArgumentException('Host thay thế nguồn tải ảnh migration không hợp lệ.');
        }

        return 'https://'.$targetHost
            .($parts['path'] ?? '')
            .(array_key_exists('query', $parts) ? '?'.$parts['query'] : '');
    }

    private function isEmbeddedImage(string $source): bool
    {
        return Str::startsWith(Str::lower(trim($source)), 'data:image/');
    }

    private function normalizeImage(UploadedFile $upload, int $maximumPixels, int $maximumBytes, string $sourceUrl): UploadedFile
    {
        try {
            return $this->normalizer->normalize($upload, $maximumPixels, $maximumBytes);
        } catch (InvalidArgumentException $exception) {
            throw new LegacyImageDownloadFailure($exception->getMessage(), $sourceUrl, 'image_decode', previous: $exception);
        }
    }

    private function isCoverField(string $field): bool
    {
        return (bool) preg_match('/(?:cover|image|thumbnail|avatar|banner)/i', $field);
    }

    private function maximumEmbeddedImageBytes(): int
    {
        return max(1024, min(10 * 1024 * 1024, (int) config('legacy_migration.media.max_embedded_image_bytes', 5 * 1024 * 1024)));
    }

    private function maximumEmbeddedTotalBytes(): int
    {
        return max(
            $this->maximumEmbeddedImageBytes(),
            min(10 * 1024 * 1024, (int) config('legacy_migration.media.max_embedded_total_bytes', 10 * 1024 * 1024)),
        );
    }
}
