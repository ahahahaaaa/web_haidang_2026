<?php

namespace App\Services\Admin;

use App\Services\Cms\SiteSettingsManager;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class FrontendHtmlImageImporter
{
    public function __construct(
        protected FrontendHtmlImageScanner $scanner,
        protected SiteSettingsManager $siteSettings,
    ) {}

    /**
     * @return array{
     *     failed: int,
     *     imported: int,
     *     items: array<int, array{
     *         message: string,
     *         name: string,
     *         src: string,
     *         status: string
     *     }>,
     *     scanned: int,
     *     skipped: int
     * }
     */
    public function import(string $path, bool $dryRun = false, bool $force = false, bool $insecure = false): array
    {
        $images = $this->scanner->scanDirectory($path);
        $existingSources = $this->existingLibrarySources();

        $result = [
            'failed' => 0,
            'imported' => 0,
            'items' => [],
            'scanned' => $images->count(),
            'skipped' => 0,
        ];

        if ($dryRun) {
            foreach ($images as $image) {
                $alreadyImported = $existingSources->has($image['src']);

                $result['items'][] = [
                    'message' => $alreadyImported ? 'Ảnh đã có trong library.' : 'Sẵn sàng import.',
                    'name' => $image['name'],
                    'src' => $image['src'],
                    'status' => $alreadyImported ? 'skipped' : 'pending',
                ];

                if ($alreadyImported) {
                    $result['skipped']++;

                    continue;
                }

                $result['imported']++;
            }

            return $result;
        }

        $settings = $this->siteSettings->current();

        foreach ($images as $image) {
            if (! $force && $existingSources->has($image['src'])) {
                $result['skipped']++;
                $result['items'][] = [
                    'message' => 'Ảnh đã có trong library.',
                    'name' => $image['name'],
                    'src' => $image['src'],
                    'status' => 'skipped',
                ];

                continue;
            }

            $temporaryFile = null;

            try {
                $download = $this->download($image['src'], $insecure);
                $temporaryFile = $download['path'];

                $settings
                    ->addMedia($temporaryFile)
                    ->usingName($image['name'])
                    ->usingFileName($this->buildFileName($image, $download['extension']))
                    ->withCustomProperties([
                        'alt' => $image['alt'],
                        'import_source' => 'docs/front_end',
                        'source_file' => $image['source_file'],
                        'source_files' => $image['source_files'],
                        'source_occurrences' => $image['occurrences'],
                        'source_url' => $image['src'],
                    ])
                    ->toMediaCollection('library', config('media-library.disk_name', 'public'));

                $existingSources->put($image['src'], true);
                $result['imported']++;
                $result['items'][] = [
                    'message' => 'Đã import vào library.',
                    'name' => $image['name'],
                    'src' => $image['src'],
                    'status' => 'imported',
                ];
            } catch (Throwable $exception) {
                report($exception);

                $result['failed']++;
                $result['items'][] = [
                    'message' => $exception->getMessage(),
                    'name' => $image['name'],
                    'src' => $image['src'],
                    'status' => 'failed',
                ];
            } finally {
                if ($temporaryFile && File::exists($temporaryFile)) {
                    File::delete($temporaryFile);
                }
            }
        }

        return $result;
    }

    /**
     * @return \Illuminate\Support\Collection<string, bool>
     */
    protected function existingLibrarySources(): Collection
    {
        return Media::query()
            ->where('collection_name', 'library')
            ->get()
            ->map(fn (Media $media) => (string) data_get($media->custom_properties, 'source_url'))
            ->filter()
            ->mapWithKeys(fn (string $sourceUrl) => [$sourceUrl => true]);
    }

    /**
     * @return array{extension: string, path: string}
     */
    protected function download(string $url, bool $insecure = false): array
    {
        $request = Http::accept('image/*')
            ->connectTimeout(10)
            ->retry(3, 500, throw: false)
            ->timeout(20);

        if ($insecure) {
            $request = $request->withoutVerifying();
        }

        $response = $request->get($url);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $contentType = Str::before(strtolower((string) $response->header('Content-Type')), ';');

        if (! Str::startsWith($contentType, 'image/')) {
            throw new RuntimeException("URL [{$url}] không trả về dữ liệu hình ảnh hợp lệ.");
        }

        $body = $response->body();
        $maxFileSize = (int) config('media-library.max_file_size', 0);

        if ($maxFileSize > 0 && strlen($body) > $maxFileSize) {
            throw new RuntimeException("Ảnh từ [{$url}] vượt quá kích thước cho phép của media library.");
        }

        $extension = $this->extensionFromContentType($contentType) ?? $this->extensionFromUrl($url) ?? 'jpg';
        $directory = storage_path('app/tmp/frontend-media-imports');

        File::ensureDirectoryExists($directory);

        $path = $directory.DIRECTORY_SEPARATOR.Str::uuid().'.'.$extension;
        File::put($path, $body);

        return [
            'extension' => $extension,
            'path' => $path,
        ];
    }

    /**
     * @param  array{
     *     alt: string,
     *     name: string,
     *     occurrences: int,
     *     source_file: string,
     *     source_files: array<int, string>,
     *     source_index: int,
     *     src: string
     * }  $image
     */
    protected function buildFileName(array $image, string $extension): string
    {
        $path = parse_url($image['src'], PHP_URL_PATH);
        $baseName = is_string($path) ? pathinfo($path, PATHINFO_FILENAME) : '';
        $stub = Str::slug($baseName);
        $stub = $stub !== '' ? $stub : Str::slug(Str::limit($image['name'], 80, ''));
        $stub = $stub !== '' ? $stub : 'frontend-image';
        $stub = Str::limit($stub, 40, '');

        return "{$stub}-".substr(sha1($image['src']), 0, 12).".{$extension}";
    }

    protected function extensionFromContentType(string $contentType): ?string
    {
        return match ($contentType) {
            'image/gif' => 'gif',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/svg+xml' => 'svg',
            'image/webp' => 'webp',
            default => null,
        };
    }

    protected function extensionFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['gif', 'jpeg', 'jpg', 'png', 'svg', 'webp'], true)
            ? $extension
            : null;
    }
}
