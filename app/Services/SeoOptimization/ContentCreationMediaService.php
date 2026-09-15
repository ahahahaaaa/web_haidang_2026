<?php

namespace App\Services\SeoOptimization;

use App\Models\SeoContentCreationAsset;
use App\Models\SeoContentCreationTask;
use App\Models\SeoOptimizationCredential;
use App\Models\User;
use App\Services\Admin\MediaLibraryUploader;
use App\Support\ContentGallery;
use App\Support\LandingPageBlocks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Models\SiteSetting;
use Throwable;

class ContentCreationMediaService
{
    public function __construct(
        private readonly MediaLibraryUploader $uploader,
        private readonly SeoImageWebpConverter $webpConverter,
        private readonly ContentCreationRegistry $registry,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function search(string $query = '', int $limit = 20): array
    {
        $siteSettingMorph = (new SiteSetting)->getMorphClass();

        return Media::query()
            ->where('model_type', $siteSettingMorph)
            ->where('collection_name', 'library')
            ->where('mime_type', 'like', 'image/%')
            ->when(trim($query) !== '', fn ($builder) => $builder->where(function ($nested) use ($query): void {
                $nested->where('name', 'like', '%'.trim($query).'%')
                    ->orWhere('file_name', 'like', '%'.trim($query).'%');
            }))
            ->latest('id')
            ->limit(max(1, min(50, $limit)))
            ->get()
            ->filter(fn (Media $media): bool => $this->isPublic($media))
            ->map(fn (Media $media): array => $this->mediaData($media))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function upload(
        string $taskId,
        string $leaseToken,
        string $reference,
        UploadedFile $upload,
        string $alt,
        string $prompt,
        User $user,
        string $credentialId,
    ): array {
        $createdMedia = null;
        $converted = null;

        try {
            return DB::transaction(function () use ($taskId, $leaseToken, $reference, $upload, $alt, $prompt, $user, $credentialId, &$createdMedia, &$converted): array {
                $task = $this->ownedTask($taskId, $leaseToken, $credentialId, $user);
                $this->validateReference($reference);
                Validator::make(compact('alt', 'prompt'), [
                    'alt' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
                    'prompt' => ['nullable', 'string', 'max:10000'],
                ])->validate();
                $source = $this->inspect($upload);

                if ($existing = SeoContentCreationAsset::query()->with('media')->where('task_id', $task->id)->where('reference', $reference)->first()) {
                    abort_unless($existing->sha256 === $source['sha256'] && $existing->media, 409, 'Reference ảnh đã dùng cho tệp khác.');

                    return ['status' => 'ready', 'asset' => $existing->manifest];
                }

                $converted = $this->webpConverter->convert($upload);
                $stored = $this->inspect($converted);
                $safeUpload = new UploadedFile($converted->getPathname(), 'cms-create-'.$task->id.'-'.$reference.'.webp', 'image/webp', null, true);
                $createdMedia = $this->uploader->uploadToLibrary(
                    $safeUpload,
                    Str::limit($task->content_type.' '.$reference, 255, ''),
                    trim($alt),
                    ['seo_content_creation_staged' => true],
                );
                $createdMedia->setCustomProperty('seo_content_creation', [
                    'task_id' => $task->id,
                    'reference' => $reference,
                    'prompt' => trim($prompt),
                    'generated_by_ai' => trim($prompt) !== '',
                ])->save();
                $manifest = [
                    'asset_id' => null,
                    'task_id' => $task->id,
                    'reference' => $reference,
                    'media_id' => (int) $createdMedia->getKey(),
                    'url' => $createdMedia->getUrl(),
                    'alt' => trim($alt),
                    'prompt' => trim($prompt),
                    'source_sha256' => $source['sha256'],
                    'stored_sha256' => $stored['sha256'],
                    'mime_type' => $stored['mime_type'],
                    'width' => $stored['width'],
                    'height' => $stored['height'],
                ];
                $asset = SeoContentCreationAsset::query()->create([
                    'task_id' => $task->id,
                    'media_id' => $createdMedia->id,
                    'created_by' => $user->getAuthIdentifier(),
                    'reference' => $reference,
                    'sha256' => $source['sha256'],
                    'manifest' => $manifest,
                ]);
                $manifest['asset_id'] = $asset->id;
                $asset->forceFill(['manifest' => $manifest])->save();

                return ['status' => 'ready', 'asset' => $manifest];
            }, 1);
        } catch (Throwable $exception) {
            $createdMedia?->delete();
            throw $exception;
        } finally {
            if ($converted && is_file($converted->getPathname())) {
                @unlink($converted->getPathname());
            }
        }
    }

    /**
     * @return array{payload: array<string, mixed>, placements: array<int, array<string, mixed>>}
     */
    public function preparePlacements(SeoContentCreationTask $task, array $payload, array $placements, array $contract): array
    {
        Validator::make(['placements' => $placements], [
            'placements' => ['array', 'max:60'],
            'placements.*' => ['array:ref,media_id,slot,alt,caption,title,block_uuid,item_uuid'],
            'placements.*.ref' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
            'placements.*.media_id' => ['required', 'integer'],
            'placements.*.slot' => ['required', 'string', Rule::in($contract['media_slots'] ?? [])],
            'placements.*.alt' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'placements.*.caption' => ['nullable', 'string', 'max:500', 'not_regex:/[<>]/'],
            'placements.*.title' => ['nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
            'placements.*.block_uuid' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
            'placements.*.item_uuid' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
        ])->validate();

        $refs = collect($placements)->pluck('ref');
        if ($refs->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['media_placements' => 'Mỗi ref ảnh chỉ được khai báo một lần.']);
        }
        if (collect($placements)->whereIn('slot', ['cover', 'avatar'])->count() > 1) {
            throw ValidationException::withMessages(['media_placements' => 'Mỗi nội dung chỉ có một ảnh đại diện.']);
        }

        $media = Media::query()->whereIn('id', collect($placements)->pluck('media_id')->all())->get()->keyBy('id');
        $taskAssetIds = SeoContentCreationAsset::query()->where('task_id', $task->id)->pluck('media_id')->map(fn ($id) => (int) $id)->all();
        $resolved = [];

        foreach ($placements as $placement) {
            $item = $media->get((int) $placement['media_id']);
            if (! $item || ! str_starts_with((string) $item->mime_type, 'image/') || ! $this->isPublic($item)) {
                throw ValidationException::withMessages(['media_placements' => 'Media '.$placement['media_id'].' không phải ảnh công khai hợp lệ.']);
            }
            $isLibrary = $item->collection_name === 'library' && $item->model_type === (new SiteSetting)->getMorphClass();
            if (! $isLibrary && ! in_array((int) $item->id, $taskAssetIds, true)) {
                throw ValidationException::withMessages(['media_placements' => 'Ảnh phải thuộc thư viện chung hoặc được upload cho đúng task.']);
            }
            $placement['media'] = $item;
            $resolved[] = $placement;

            if ($placement['slot'] === 'content') {
                $marker = '[[media:'.$placement['ref'].']]';
                $count = $this->markerCount($payload, $marker);
                if ($count < 1) {
                    throw ValidationException::withMessages(['media_placements' => 'Không tìm thấy marker '.$marker.' trong nội dung.']);
                }
                $payload = $this->replaceContentMarker($payload, $marker, $this->figure($item, $placement));
            }

            if ($placement['slot'] === 'gallery') {
                $uuid = trim((string) ($placement['item_uuid'] ?? '')) ?: (string) Str::uuid();
                $placement['item_uuid'] = $uuid;
                $resolved[array_key_last($resolved)] = $placement;
                $payload['gallery'][] = [
                    'uuid' => $uuid, 'type' => ContentGallery::TYPE_IMAGE,
                    'title' => trim((string) ($placement['title'] ?? '')),
                    'description' => trim((string) ($placement['caption'] ?? '')),
                    'image_alt' => trim((string) $placement['alt']), 'image_url' => '', 'video_url' => '',
                ];
            }

            if ($placement['slot'] === 'landing_block') {
                $this->assertLandingTarget($payload, $placement);
            }
        }

        if (preg_match('/\[\[media:[A-Za-z0-9_-]+\]\]/', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))) {
            throw ValidationException::withMessages(['media_placements' => 'Còn marker ảnh chưa có media_placements tương ứng.']);
        }

        return ['payload' => $payload, 'placements' => $resolved];
    }

    /** @param Model&HasMedia $owner */
    public function attach(Model $owner, string $type, array $placements): void
    {
        foreach ($placements as $placement) {
            if ($placement['slot'] === 'content') {
                continue;
            }
            $collection = match ($placement['slot']) {
                'cover' => 'cover',
                'avatar' => 'avatar',
                'gallery' => match ($type) {
                    'tour' => ContentGallery::tourCollection($placement['item_uuid']),
                    'service' => ContentGallery::serviceCollection($placement['item_uuid']),
                    default => ContentGallery::taxonomyCollection($type, $placement['item_uuid']),
                },
                'landing_block' => filled($placement['item_uuid'] ?? null)
                    ? LandingPageBlocks::galleryItemCollection($placement['block_uuid'], $placement['item_uuid'])
                    : LandingPageBlocks::mediaCollection($placement['block_uuid']),
                default => null,
            };
            if (! $collection || ! $owner instanceof HasMedia) {
                continue;
            }
            $source = $placement['media'];
            if (in_array($placement['slot'], ['cover', 'avatar'], true)) {
                $owner->clearMediaCollection($collection);
            }
            $source->copy(
                model: $owner,
                collectionName: $collection,
                diskName: config('media-library.disk_name', 'public'),
                fileAdderCallback: fn ($adder) => $adder->withCustomProperties([
                    ...($source->custom_properties ?? []),
                    'alt' => trim((string) $placement['alt']),
                    'source_library_media_id' => (int) $source->id,
                    'seo_content_creation_ref' => $placement['ref'],
                ]),
            );
        }
    }

    private function ownedTask(string $taskId, string $leaseToken, string $credentialId, User $user): SeoContentCreationTask
    {
        $credential = SeoOptimizationCredential::query()->where('user_id', $user->id)->findOrFail($credentialId);
        abort_unless(in_array('create', $credential->abilities ?? [], true), 403, 'Token không có quyền tạo nội dung CMS.');
        $task = SeoContentCreationTask::query()->lockForUpdate()->findOrFail($taskId);
        abort_unless((string) $task->credential_id === $credentialId && (int) $task->requested_by === (int) $user->id, 403);
        abort_unless($user->can($this->registry->get($task->content_type)['permission']), 403, 'Tài khoản không còn quyền với loại nội dung này.');
        abort_unless($task->status === 'drafting' && $task->leased_until?->isFuture()
            && hash_equals((string) $task->lease_token_hash, hash('sha256', $leaseToken)), 409, 'Lease tạo nội dung đã hết hạn hoặc không hợp lệ.');

        return $task;
    }

    /** @return array{sha256:string,mime_type:string,width:int,height:int} */
    private function inspect(UploadedFile $upload): array
    {
        $mime = $upload->getMimeType();
        $allowed = ['image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'image/webp' => ['webp']];
        $extension = strtolower($upload->getClientOriginalExtension());
        $dimensions = @getimagesize($upload->getPathname());
        $maxBytes = (int) config('seo_optimization.media_max_bytes', 10485760);
        $maxPixels = (int) config('seo_optimization.media_max_pixels', 24000000);
        if (! $upload->isValid() || ! isset($allowed[$mime]) || ! in_array($extension, $allowed[$mime], true)
            || $upload->getSize() < 1 || $upload->getSize() > $maxBytes || ! $dimensions
            || ($dimensions['mime'] ?? null) !== $mime || $maxPixels < $dimensions[0] * $dimensions[1]) {
            throw ValidationException::withMessages(['image' => 'Ảnh phải là JPEG, PNG hoặc WebP thật và nằm trong giới hạn dung lượng/pixel.']);
        }

        return ['sha256' => hash_file('sha256', $upload->getPathname()), 'mime_type' => $mime, 'width' => $dimensions[0], 'height' => $dimensions[1]];
    }

    private function validateReference(string $reference): void
    {
        if (! preg_match('/^[A-Za-z0-9_-]{1,100}$/', $reference)) {
            throw ValidationException::withMessages(['reference' => 'Reference ảnh chỉ dùng chữ, số, gạch ngang hoặc gạch dưới.']);
        }
    }

    private function isPublic(Media $media): bool
    {
        return config('filesystems.disks.'.$media->disk.'.visibility') === 'public';
    }

    private function mediaData(Media $media): array
    {
        return [
            'media_id' => (int) $media->id,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'alt' => (string) data_get($media->custom_properties, 'alt', $media->name),
            'url' => $media->getUrl(),
            'small_url' => $media->hasGeneratedConversion('small') ? $media->getUrl('small') : $media->getUrl(),
            'mime_type' => $media->mime_type,
            'width' => data_get($media->custom_properties, 'width'),
            'height' => data_get($media->custom_properties, 'height'),
        ];
    }

    private function markerCount(array $payload, string $marker): int
    {
        $count = substr_count((string) ($payload['content'] ?? ''), $marker)
            + substr_count((string) ($payload['body'] ?? ''), $marker);
        foreach ($payload['itinerary'] ?? [] as $item) {
            if (is_array($item)) {
                $count += substr_count((string) ($item['content'] ?? ''), $marker);
            }
        }

        return $count;
    }

    private function replaceContentMarker(array $payload, string $marker, string $replacement): array
    {
        foreach (['content', 'body'] as $field) {
            if (is_string($payload[$field] ?? null)) {
                $payload[$field] = str_replace($marker, $replacement, $payload[$field]);
            }
        }
        foreach ($payload['itinerary'] ?? [] as $index => $item) {
            if (is_array($item) && is_string($item['content'] ?? null)) {
                $payload['itinerary'][$index]['content'] = str_replace($marker, $replacement, $item['content']);
            }
        }

        return $payload;
    }

    private function figure(Media $media, array $placement): string
    {
        $url = htmlspecialchars($media->getUrl(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $alt = htmlspecialchars(trim((string) $placement['alt']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $caption = trim((string) ($placement['caption'] ?? ''));
        $captionHtml = $caption !== '' ? '<figcaption>'.htmlspecialchars($caption, ENT_QUOTES | ENT_HTML5, 'UTF-8').'</figcaption>' : '';

        return '<figure><img src="'.$url.'" alt="'.$alt.'" loading="lazy" data-media-id="'.$media->id.'">'.$captionHtml.'</figure>';
    }

    private function assertLandingTarget(array $payload, array $placement): void
    {
        $blockUuid = trim((string) ($placement['block_uuid'] ?? ''));
        $block = collect($payload['blocks'] ?? [])->firstWhere('uuid', $blockUuid);
        if (! is_array($block)) {
            throw ValidationException::withMessages(['media_placements' => 'Không tìm thấy landing block '.$blockUuid.'.']);
        }
        $itemUuid = trim((string) ($placement['item_uuid'] ?? ''));
        $blockType = $block['type'] ?? null;
        if (! in_array($blockType, [LandingPageBlocks::TYPE_HERO_MEDIA, LandingPageBlocks::TYPE_GALLERY_MEDIA], true)) {
            throw ValidationException::withMessages(['media_placements' => 'landing_block chỉ dùng cho hero_media hoặc gallery_media.']);
        }
        if ($blockType === LandingPageBlocks::TYPE_GALLERY_MEDIA) {
            if ($itemUuid === '' || ! collect($block['items'] ?? [])->contains(fn ($item) => is_array($item) && ($item['uuid'] ?? null) === $itemUuid)) {
                throw ValidationException::withMessages(['media_placements' => 'Gallery landing cần item_uuid tồn tại trong đúng block.']);
            }
        } elseif ($itemUuid !== '') {
            throw ValidationException::withMessages(['media_placements' => 'item_uuid chỉ dùng cho block gallery_media.']);
        }
    }
}
