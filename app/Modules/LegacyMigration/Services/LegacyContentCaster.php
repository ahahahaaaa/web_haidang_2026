<?php

namespace App\Modules\LegacyMigration\Services;

use App\Models\User;
use App\Modules\LegacyMigration\Exceptions\LegacyImageDownloadFailure;
use App\Modules\LegacyMigration\Models\LegacyCastAudit;
use App\Modules\LegacyMigration\Models\LegacyMigrationRedirect;
use App\Modules\LegacyMigration\Models\LegacyObjectMap;
use App\Modules\LegacyMigration\Models\LegacyStagedObject;
use App\Modules\LegacyMigration\Models\LegacyStagedUrl;
use App\Services\Frontsite\FrontsiteCacheInvalidator;
use App\Support\FrontsiteUrls;
use App\Support\RichText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\PublicUrlMapping;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Src\Domains\Cms\Models\TourDeparture;
use Throwable;

class LegacyContentCaster
{
    public function __construct(
        private LegacyTargetRegistry $targets,
        private LegacyTimestampNormalizer $timestamps,
        private LegacyValueCaster $values,
        private LegacyPath $paths,
        private LegacyRunCounter $counter,
        private FrontsiteCacheInvalidator $cacheInvalidator,
        private LegacyBlogMediaImporter $mediaImporter,
    ) {}

    /** @param array<string, string> $contentReplacements */
    public function cast(LegacyStagedUrl $url, User $actor, array $contentReplacements = [], bool $importMedia = false): LegacyStagedUrl
    {
        $changedTarget = null;
        $mediaResult = null;
        $removedImageSources = [];

        try {
            if ($importMedia) {
                $url = $url->fresh(['run']);

                if ($url->status !== 'casted' && $url->target_type === 'blog_post'
                    && in_array($url->mapping_mode, ['cast_preserve_url', 'cast_only', 'cast_and_redirect'], true)
                ) {
                    if ($url->run->status === 'receiving') {
                        throw new InvalidArgumentException('Phiên chưa finalize; chưa được phép cast dữ liệu.');
                    }

                    if ($url->status !== 'mapped') {
                        throw new InvalidArgumentException('URL phải được lưu mapping trước khi cast.');
                    }

                    $source = $this->castSource($url);
                    $this->timestamps->requiredPair((array) Arr::get($source->payload_json, 'attributes', []));
                    $target = $this->targets->resolve('blog_post', (string) $url->target_id);
                    $mediaResult = $this->mediaImporter->prepare($target, $source);
                    $contentReplacements = array_replace($contentReplacements, $mediaResult['replacements']);
                    $removedImageSources = $mediaResult['removed_sources'];
                }
            }

            DB::transaction(function () use ($url, $actor, $contentReplacements, $removedImageSources, $mediaResult, &$changedTarget): void {
                $url = LegacyStagedUrl::query()->with('run')->lockForUpdate()->findOrFail($url->id);

                if ($url->status === 'casted') {
                    return;
                }

                if ($url->run->status === 'receiving') {
                    throw new InvalidArgumentException('Phiên chưa finalize; chưa được phép cast dữ liệu.');
                }

                if ($url->status !== 'mapped') {
                    throw new InvalidArgumentException('URL phải được lưu mapping trước khi cast.');
                }

                if ($url->mapping_mode === 'redirect_only') {
                    $this->storeRedirect($url, $actor);
                    $this->auditRedirect($url, $actor);
                    $url->forceFill(['status' => 'casted', 'casted_at' => now(), 'error_text' => null])->save();

                    return;
                }

                if (! in_array($url->mapping_mode, ['cast_preserve_url', 'cast_only', 'cast_and_redirect'], true)) {
                    throw new InvalidArgumentException('Chế độ mapping không hỗ trợ cast.');
                }

                if ($url->target_type === 'system_route') {
                    throw new InvalidArgumentException('Trang hệ thống chỉ hỗ trợ redirect, không hỗ trợ cast model.');
                }

                $source = $this->castSource($url);

                $target = $this->targets->resolve((string) $url->target_type, (string) $url->target_id);
                $before = $target->getRawOriginal();
                $previousCreatedAt = $target->created_at;
                $previousUpdatedAt = $target->updated_at;
                $sourcePayload = (array) $source->payload_json;
                $sourceAttributes = (array) Arr::get($sourcePayload, 'attributes', []);
                [$createdAt, $updatedAt] = $this->timestamps->requiredPair($sourceAttributes);
                $mapped = $this->mappedAttributes($target, $source, $sourceAttributes, $contentReplacements, $removedImageSources);
                if ($mediaResult !== null && $mediaResult['cover_fallback_url'] !== null && blank($target->cover_image_url)) {
                    $mapped['cover_image_url'] = $mediaResult['cover_fallback_url'];
                }
                $mapped = [...$mapped, ...$this->relationshipAttributes($url, $target, $source)];
                $mapped = array_intersect_key($mapped, array_flip($target->getFillable()));
                $changes = $this->merge($target, $mapped, (string) $url->merge_policy);

                $target::withoutTimestamps(function () use ($target, $changes, $createdAt, $updatedAt): void {
                    $target->fill($changes);
                    $target->setAttribute('created_at', $createdAt);
                    $target->setAttribute('updated_at', $updatedAt);
                    $target->save();
                });

                $this->syncRelationships($url, $target, $source);

                if ($target instanceof Tour) {
                    $this->castTourDepartures($url, $target, $source, $actor);
                }

                $this->mapObject($url, $source, (string) $url->target_type, (string) $target->getKey());
                $resolvedTargetPath = $this->targets->path((string) $url->target_type, $target);
                $url->forceFill(['target_path' => $resolvedTargetPath])->save();

                if ($url->mapping_mode === 'cast_preserve_url') {
                    $this->storeOriginalUrl($url, $target, $actor);
                } elseif ($url->mapping_mode === 'cast_and_redirect' && $this->isPubliclyRenderable($target)) {
                    if ($url->normalized_path !== $url->target_path) {
                        $this->storeRedirect($url, $actor, $target);
                    } else {
                        $this->deactivateUrlMappings((string) $url->normalized_path);
                    }

                    $this->useTargetAsCanonical($target, $resolvedTargetPath);
                }

                $target->refresh();
                LegacyCastAudit::query()->create([
                    'run_id' => $url->run_id,
                    'staged_url_id' => $url->id,
                    'staged_object_id' => $source->id,
                    'target_type' => $url->target_type,
                    'target_id' => (string) $target->getKey(),
                    'action' => $url->mapping_mode,
                    'merge_policy' => $url->merge_policy,
                    'timestamp_policy' => 'source',
                    'before_json' => $before,
                    'after_json' => $target->getRawOriginal(),
                    'source_created_at' => $createdAt,
                    'source_updated_at' => $updatedAt,
                    'previous_target_created_at' => $previousCreatedAt,
                    'previous_target_updated_at' => $previousUpdatedAt,
                    'actor_id' => $actor->id,
                    'status' => 'completed',
                ]);

                if ($mediaResult !== null) {
                    LegacyCastAudit::query()->create([
                        'run_id' => $url->run_id,
                        'staged_url_id' => $url->id,
                        'staged_object_id' => $source->id,
                        'target_type' => $url->target_type,
                        'target_id' => (string) $target->getKey(),
                        'action' => 'import_media',
                        'merge_policy' => $url->merge_policy,
                        'timestamp_policy' => 'source',
                        'after_json' => Arr::except($mediaResult, ['replacements', 'removed_sources']),
                        'actor_id' => $actor->id,
                        'status' => $mediaResult['skipped'] > 0 ? 'warning' : 'completed',
                    ]);
                }

                $source->forceFill(['status' => 'casted', 'error_text' => null])->save();
                $url->forceFill(['status' => 'casted', 'casted_at' => now(), 'error_text' => null])->save();
                $changedTarget = $target;
            }, 5);
        } catch (Throwable $exception) {
            $this->recordFailure($url, $actor, $exception);

            throw $exception;
        }

        if ($changedTarget instanceof Model) {
            $this->cacheInvalidator->modelChanged($changedTarget);
        }

        $this->counter->refreshAfterCast($url->run);

        return $url->refresh();
    }

    public function convertPreservedUrlToRedirect(LegacyStagedUrl $url, User $actor): LegacyStagedUrl
    {
        $changedTarget = null;

        DB::transaction(function () use ($url, $actor, &$changedTarget): void {
            $url = LegacyStagedUrl::query()->with('run')->lockForUpdate()->findOrFail($url->id);

            if ($url->run->status === 'receiving') {
                throw new InvalidArgumentException('Phiên chưa finalize; chưa thể chuyển URL cũ sang redirect.');
            }

            if ($url->status === 'casted' && $url->mapping_mode === 'cast_and_redirect') {
                return;
            }

            if ($url->status !== 'casted' || $url->mapping_mode !== 'cast_preserve_url') {
                throw new InvalidArgumentException('URL phải ở trạng thái đã cast và đang giữ URL nguồn trả 200.');
            }

            if (! $url->target_type || ! $url->target_id || $url->target_type === 'system_route') {
                throw new InvalidArgumentException('URL chưa có model đích hợp lệ để chuyển sang redirect.');
            }

            $target = $this->targets->resolve((string) $url->target_type, (string) $url->target_id);

            if (! $this->isPubliclyRenderable($target)) {
                throw new InvalidArgumentException('Không thể tạo redirect vì page đích chưa được publish/active.');
            }

            $sourcePath = $this->paths->normalize((string) $url->normalized_path);
            $targetPath = $this->paths->normalize($this->targets->path((string) $url->target_type, $target));

            $beforeCanonical = $target->getAttribute('canonical_url');
            $beforeTargetPath = $url->getRawOriginal('target_path');
            $timestamps = [
                'created_at' => $target->getRawOriginal('created_at'),
                'updated_at' => $target->getRawOriginal('updated_at'),
            ];

            $url->forceFill([
                'target_path' => $targetPath,
                'redirect_code' => 301,
            ]);
            if ($sourcePath !== $targetPath) {
                $this->storeRedirect($url, $actor, $target);
            } else {
                $this->deactivateUrlMappings($sourcePath);
            }

            $this->useTargetAsCanonical($target, $targetPath);
            $url->forceFill([
                'mapping_mode' => 'cast_and_redirect',
                'target_path' => $targetPath,
                'redirect_code' => 301,
                'error_text' => null,
            ])->save();

            LegacyCastAudit::query()->create([
                'run_id' => $url->run_id,
                'staged_url_id' => $url->id,
                'target_type' => $url->target_type,
                'target_id' => $url->target_id,
                'action' => 'promote_redirect',
                'merge_policy' => $url->merge_policy,
                'timestamp_policy' => 'preserve',
                'before_json' => [
                    'mapping_mode' => 'cast_preserve_url',
                    'source_path' => $sourcePath,
                    'target_path' => $beforeTargetPath,
                    'canonical_url' => $beforeCanonical,
                    ...$timestamps,
                ],
                'after_json' => [
                    'mapping_mode' => 'cast_and_redirect',
                    'source_path' => $sourcePath,
                    'target_path' => $targetPath,
                    'redirect_code' => $sourcePath !== $targetPath ? 301 : null,
                    'redirect_not_needed' => $sourcePath === $targetPath,
                    'canonical_url' => $target->getAttribute('canonical_url'),
                    ...$timestamps,
                ],
                'previous_target_created_at' => $target->created_at,
                'previous_target_updated_at' => $target->updated_at,
                'actor_id' => $actor->id,
                'status' => 'completed',
            ]);

            $changedTarget = $target;
        }, 5);

        if ($changedTarget instanceof Model) {
            $this->cacheInvalidator->modelChanged($changedTarget);
        }

        return $url->refresh();
    }

    private function castSource(LegacyStagedUrl $url): LegacyStagedObject
    {
        $source = $url->rootObject();

        if (! $source) {
            throw new InvalidArgumentException('URL chưa có root object để cast.');
        }

        if ($source->is_partial) {
            throw new InvalidArgumentException('Root object đang là bản partial; cần bản đầy đủ trước khi cast.');
        }

        if (! $this->targets->isCompatible($source->object_type, (string) $url->target_type)) {
            throw new InvalidArgumentException('Loại object nguồn không tương thích target đã chọn.');
        }

        return $source;
    }

    /** @param array<string, string> $contentReplacements */
    private function mappedAttributes(Model $target, LegacyStagedObject $source, array $attributes, array $contentReplacements = [], array $removedImageSources = []): array
    {
        $commonSeo = array_filter([
            'meta_title' => $this->first($attributes, ['seotitle', 'seo_title', 'meta_title']),
            'meta_description' => $this->plain($this->first($attributes, ['seodescription', 'seo_description', 'meta_description'])),
            'robots_directive' => $this->robots($attributes),
        ], fn (mixed $value): bool => $value !== null);

        return match (true) {
            $target instanceof Tour => [...$this->tourAttributes($source, $attributes), ...$commonSeo],
            $target instanceof BlogPost => [...$this->blogAttributes($source, $attributes, $contentReplacements, $removedImageSources), ...$commonSeo],
            $target instanceof TourCategory, $target instanceof Destination, $target instanceof Region => [
                ...$this->taxonomyAttributes($source, $attributes),
                ...$commonSeo,
            ],
            $target instanceof ContentCategory => $this->contentCategoryAttributes($source, $attributes),
            $target instanceof LandingPage => [...$this->landingAttributes($attributes), ...$commonSeo],
            $target instanceof Service => [...$this->serviceAttributes($attributes), ...$commonSeo],
            default => throw new InvalidArgumentException('Caster target chưa được hỗ trợ.'),
        };
    }

    private function tourAttributes(LegacyStagedObject $source, array $attributes): array
    {
        $mapped = $this->present([
            'title' => $this->first($attributes, ['title']),
            'excerpt' => $this->plain($this->first($attributes, ['excerpt', 'seodescription'])),
            'content' => $this->html($this->first($attributes, ['description', 'content'])),
            'transport' => $this->first($attributes, ['traffic']),
            'departure_location' => $this->first($attributes, ['dp_name']),
            'base_price' => $this->money($this->first($attributes, ['adultprice', 'price'])),
            'sale_price' => $this->money($this->first($attributes, ['price', 'adultprice'])),
            'rating_average' => $this->first($attributes, ['rating_cache', 'rating']),
            'rating_count' => $this->integer($this->first($attributes, ['rating_count', 'num_rating'])),
            'is_featured' => $this->featured($attributes),
            'sort_order' => $this->integer($this->first($attributes, ['priority'])),
        ]);

        $period = (string) ($attributes['period'] ?? '');

        if (preg_match('/(\d+)\s*(?:ng[aà]y|day)/iu', $period, $match)) {
            $mapped['duration_days'] = (int) $match[1];
        }

        if (preg_match('/(\d+)\s*(?:đ[eê]m|night)/iu', $period, $match)) {
            $mapped['duration_nights'] = (int) $match[1];
        }

        $itinerary = $this->tourItinerary($source);

        if ($itinerary !== []) {
            $mapped['itinerary'] = $itinerary;
        }

        return $mapped;
    }

    /** @param array<string, string> $contentReplacements */
    private function blogAttributes(LegacyStagedObject $source, array $attributes, array $contentReplacements = [], array $removedImageSources = []): array
    {
        $authorName = null;
        $authorKey = Arr::get($source->payload_json, 'relationships.author');

        if (is_string($authorKey)) {
            $author = $this->relatedObject($source, $authorKey);
            $authorName = $this->first((array) Arr::get($author?->payload_json, 'attributes', []), ['fullname', 'fullnameen', 'username']);
        }

        $published = array_key_exists('publish', $attributes)
            ? $this->boolean($attributes['publish'])
            : null;
        $content = $this->first($attributes, ['content', 'detail', 'description', 'body']);

        if ($content !== null) {
            $content = $this->mediaImporter->rewriteContent((string) $content, $contentReplacements, $removedImageSources);

            if ($this->mediaImporter->hasEmbeddedContentImages($content)) {
                throw new InvalidArgumentException('Nội dung còn ảnh base64 chưa được đưa vào Media Library. Bật tải ảnh về Media Library rồi xử lý lại URL này.');
            }
        }

        return $this->present([
            'title' => $this->first($attributes, ['title']),
            'excerpt' => $this->plain($this->first($attributes, ['excerpt', 'description', 'seodescription'])),
            'content' => $this->html($content),
            'author_name' => $authorName,
            'status' => $published === null ? null : ($published ? 'published' : 'draft'),
            'published_at' => $published ? $this->first($attributes, ['published_at', 'publish_at', 'created_at']) : null,
            'is_featured' => $this->boolean($this->first($attributes, ['homepage', 'is_featured'])),
            'sort_order' => $this->integer($this->first($attributes, ['priority'])),
            'cover_alt' => $this->first($attributes, ['image_alt', 'cover_alt', 'title']),
            'reading_time_minutes' => $this->integer($this->first($attributes, ['reading_time_minutes'])),
        ]);
    }

    private function taxonomyAttributes(LegacyStagedObject $source, array $attributes): array
    {
        return $this->present([
            'name' => $this->first($attributes, ['name', 'title']),
            'excerpt' => $this->plain($this->first($attributes, ['excerpt', 'seodescription'])),
            'content' => $this->html($this->first($attributes, ['content', 'description'])),
            'is_featured' => $this->boolean($this->first($attributes, ['homepage', 'isHomepage', 'is_featured'])),
            'sort_order' => $this->integer($this->first($attributes, ['priority', 'sort_order'])),
            'rating_average' => $this->first($attributes, ['rating_cache', 'rating']),
            'rating_count' => $this->integer($this->first($attributes, ['rating_count', 'num_rating'])),
        ]);
    }

    private function contentCategoryAttributes(LegacyStagedObject $source, array $attributes): array
    {
        return $this->present([
            'name' => $this->first($attributes, ['name', 'title']),
            'description' => $this->plain($this->first($attributes, ['description', 'seodescription'])),
            'content' => $this->html($this->first($attributes, ['content', 'description'])),
            'sort_order' => $this->integer($this->first($attributes, ['priority', 'sort_order'])),
        ]);
    }

    private function landingAttributes(array $attributes): array
    {
        return $this->present([
            'title' => $this->first($attributes, ['title', 'name']),
            'hero_title' => $this->first($attributes, ['title', 'name']),
            'hero_excerpt' => $this->plain($this->first($attributes, ['excerpt', 'description'])),
            'body' => $this->html($this->first($attributes, ['content', 'body', 'description'])),
            'editor_mode' => LandingPage::EDITOR_MODE_HTML,
        ]);
    }

    private function serviceAttributes(array $attributes): array
    {
        return $this->present([
            'title' => $this->first($attributes, ['title', 'name']),
            'excerpt' => $this->plain($this->first($attributes, ['excerpt', 'description'])),
            'content' => $this->html($this->first($attributes, ['content', 'body', 'description'])),
        ]);
    }

    private function relationshipAttributes(LegacyStagedUrl $url, Model $target, LegacyStagedObject $source): array
    {
        $relationships = (array) Arr::get($source->payload_json, 'relationships', []);
        $mapped = [];

        if ($target instanceof Tour) {
            $mapped['tour_category_id'] = $this->firstMappedTarget($url, (array) ($relationships['subject_tours'] ?? []), 'tour_category');
            $mapped['destination_id'] = $this->firstMappedTarget($url, (array) ($relationships['destinations'] ?? []), 'destination');
            $sourcePointKey = $relationships['source_point'] ?? null;

            if (is_string($sourcePointKey)) {
                $sourcePoint = $this->relatedObject($source, $sourcePointKey);
                $regionKey = Arr::get($sourcePoint?->payload_json, 'relationships.region');
                $mapped['region_id'] = is_string($regionKey) ? $this->mappedTargetId($url, $regionKey, 'region') : null;
            }
        } elseif ($target instanceof BlogPost) {
            $mapped['content_category_id'] = $this->firstMappedTarget($url, (array) ($relationships['subject_blogs'] ?? []), 'blog_category');
            $destinationKey = $relationships['destination'] ?? null;
            $mapped['destination_id'] = is_string($destinationKey) ? $this->mappedTargetId($url, $destinationKey, 'destination') : null;
        } elseif ($target instanceof Destination) {
            $regionKey = $relationships['region'] ?? null;
            $mapped['region_id'] = is_string($regionKey) ? $this->mappedTargetId($url, $regionKey, 'region') : null;
        } elseif ($target instanceof ContentCategory) {
            $parentKey = $relationships['parent'] ?? null;
            $mapped['parent_id'] = is_string($parentKey) ? $this->mappedTargetId($url, $parentKey, 'blog_category') : null;
        }

        return array_filter($mapped, fn (mixed $value): bool => $value !== null);
    }

    private function syncRelationships(LegacyStagedUrl $url, Model $target, LegacyStagedObject $source): void
    {
        if (! $target instanceof Tour) {
            return;
        }

        $relationships = (array) Arr::get($source->payload_json, 'relationships', []);
        $categoryIds = $this->mappedTargetIds($url, (array) ($relationships['subject_tours'] ?? []), 'tour_category');
        $destinationIds = $this->mappedTargetIds($url, (array) ($relationships['destinations'] ?? []), 'destination');

        if ($categoryIds !== []) {
            $target->categories()->syncWithoutDetaching($categoryIds);
        }

        if ($destinationIds !== []) {
            $target->destinations()->syncWithoutDetaching($destinationIds);
        }
    }

    private function castTourDepartures(LegacyStagedUrl $url, Tour $tour, LegacyStagedObject $source, User $actor): void
    {
        $keys = array_values(array_filter((array) Arr::get($source->payload_json, 'relationships.start_dates', []), 'is_string'));

        foreach ($keys as $key) {
            $sourceDeparture = $this->relatedObject($source, $key);

            if (! $sourceDeparture || $sourceDeparture->is_partial) {
                continue;
            }

            $attributes = (array) Arr::get($sourceDeparture->payload_json, 'attributes', []);
            $departureDate = $this->values->date($attributes['startdate'] ?? null);

            if ($departureDate === null) {
                continue;
            }

            $mappedId = $this->mappedTargetId($url, $key, 'tour_departure');
            $departure = $mappedId ? TourDeparture::query()->where('tour_id', $tour->id)->find($mappedId) : null;
            $departure ??= TourDeparture::query()
                ->where('tour_id', $tour->id)
                ->whereDate('departure_date', $departureDate)
                ->when(filled($attributes['standard'] ?? null), fn ($query) => $query->where('standard_label', $attributes['standard']))
                ->first();
            $departure ??= new TourDeparture(['tour_id' => $tour->id]);
            $before = $departure->exists ? $departure->getRawOriginal() : null;
            $previousCreatedAt = $departure->created_at;
            $previousUpdatedAt = $departure->updated_at;
            [$createdAt, $updatedAt] = $this->timestamps->requiredPair($attributes);
            $mapped = $this->present([
                'departure_date' => $departureDate,
                'transport_label' => $this->first($attributes, ['traffic']),
                'standard_label' => $this->first($attributes, ['standard']),
                'base_price' => $this->money($this->first($attributes, ['adult_price', 'price_sd'])),
                'sale_price' => $this->money($this->first($attributes, ['adult_price', 'price_sd'])),
                'available_slots' => $this->integer($this->first($attributes, ['seat', 'total_seat'])),
                'pricing_note' => $this->plain($this->first($attributes, ['ten_phu_thu', 'adding'])),
                'is_featured' => $this->boolean($this->first($attributes, ['isEvent'])),
            ]);
            $changes = $this->merge($departure, $mapped, (string) $url->merge_policy);

            $departure::withoutTimestamps(function () use ($departure, $changes, $createdAt, $updatedAt): void {
                $departure->fill($changes);
                $departure->setAttribute('created_at', $createdAt);
                $departure->setAttribute('updated_at', $updatedAt);
                $departure->save();
            });

            $this->mapObject($url, $sourceDeparture, 'tour_departure', (string) $departure->id);
            LegacyCastAudit::query()->create([
                'run_id' => $url->run_id,
                'staged_url_id' => $url->id,
                'staged_object_id' => $sourceDeparture->id,
                'target_type' => 'tour_departure',
                'target_id' => (string) $departure->id,
                'action' => 'cast_child',
                'merge_policy' => $url->merge_policy,
                'timestamp_policy' => 'source',
                'before_json' => $before,
                'after_json' => $departure->getRawOriginal(),
                'source_created_at' => $createdAt,
                'source_updated_at' => $updatedAt,
                'previous_target_created_at' => $previousCreatedAt,
                'previous_target_updated_at' => $previousUpdatedAt,
                'actor_id' => $actor->id,
                'status' => 'completed',
            ]);
        }
    }

    private function tourItinerary(LegacyStagedObject $source): array
    {
        $keys = array_values(array_filter((array) Arr::get($source->payload_json, 'relationships.details', []), 'is_string'));

        return collect($keys)->map(function (string $key) use ($source): ?array {
            $detail = $this->relatedObject($source, $key);
            $attributes = (array) Arr::get($detail?->payload_json, 'attributes', []);
            $content = $this->html($this->first($attributes, ['content', 'description']));

            if ($content === null) {
                return null;
            }

            return [
                '_day' => (int) ($attributes['day'] ?? 0),
                'title' => (string) ($this->first($attributes, ['title']) ?? ('Ngày '.($attributes['day'] ?? ''))),
                'content' => $content,
            ];
        })->filter()->sortBy('_day')->map(function (array $item): array {
            unset($item['_day']);

            return $item;
        })->values()->all();
    }

    private function merge(Model $target, array $mapped, string $policy): array
    {
        if ($policy === 'overwrite') {
            return $mapped;
        }

        if ($policy !== 'fill_blanks') {
            throw new InvalidArgumentException('Chính sách merge không hợp lệ.');
        }

        return array_filter($mapped, function (mixed $value, string $field) use ($target): bool {
            $current = $target->getAttribute($field);

            return $current === null || $current === '' || $current === [];
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function mapObject(LegacyStagedUrl $url, LegacyStagedObject $source, string $targetType, string $targetId): void
    {
        $identity = LegacyObjectMap::identity(
            (string) $url->run->source_system,
            $source->object_type,
            $source->legacy_id,
            $source->legacy_key,
        );
        $map = LegacyObjectMap::query()->where('source_identity', $identity)->first() ?? new LegacyObjectMap;
        $map->fill([
            'source_identity' => $identity,
            'source_system' => $url->run->source_system,
            'object_type' => $source->object_type,
            'legacy_id' => $source->legacy_id,
            'legacy_key' => $source->legacy_key,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'last_checksum' => $source->checksum,
            'is_partial' => $source->is_partial,
            'first_run_id' => $map->first_run_id ?: $url->run_id,
            'last_run_id' => $url->run_id,
        ])->save();
    }

    private function storeOriginalUrl(LegacyStagedUrl $url, Model $target, User $actor): void
    {
        $sourcePath = $this->paths->normalize($url->normalized_path);
        $targetPath = $this->paths->normalize((string) $url->target_path);

        if (! $this->isPubliclyRenderable($target)) {
            throw new InvalidArgumentException('Không thể giữ URL gốc vì page đích chưa được publish/active. Hãy publish page hoặc cấu hình redirect-only tới URL khác.');
        }

        if ($sourcePath === $targetPath) {
            PublicUrlMapping::query()
                ->where('source_hash', hash('sha256', $sourcePath))
                ->where('source_path', $sourcePath)
                ->update(['is_active' => false]);

            return;
        }

        $this->paths->assertRedirectable($sourcePath, $targetPath);
        LegacyMigrationRedirect::query()
            ->where('source_hash', hash('sha256', $sourcePath))
            ->where('source_path', $sourcePath)
            ->update(['is_active' => false]);
        PublicUrlMapping::query()->updateOrCreate(
            ['source_hash' => hash('sha256', $sourcePath)],
            [
                'source_path' => $sourcePath,
                'mode' => PublicUrlMapping::MODE_RENDER,
                'target_type' => $url->target_type,
                'target_id' => $url->target_id,
                'target_path' => $targetPath,
                'status_code' => 200,
                'is_active' => true,
                'origin' => 'legacy_migration',
                'context_json' => [
                    'legacy_run_uuid' => $url->run?->uuid,
                    'legacy_staged_url_id' => $url->id,
                ],
                'created_by' => $actor->id,
            ],
        );
    }

    private function deactivateUrlMappings(string $sourcePath): void
    {
        $sourcePath = $this->paths->normalize($sourcePath);

        PublicUrlMapping::query()
            ->where('source_hash', hash('sha256', $sourcePath))
            ->where('source_path', $sourcePath)
            ->update(['is_active' => false]);
        LegacyMigrationRedirect::query()
            ->where('source_hash', hash('sha256', $sourcePath))
            ->where('source_path', $sourcePath)
            ->update(['is_active' => false]);
    }

    private function storeRedirect(LegacyStagedUrl $url, User $actor, ?Model $target = null): void
    {
        $sourcePath = $this->paths->normalize($url->normalized_path);
        $targetPath = $this->paths->normalize((string) $url->target_path);

        if ($url->target_type !== 'system_route') {
            $target ??= $this->targets->resolve((string) $url->target_type, (string) $url->target_id);

            if (! $this->isPubliclyRenderable($target)) {
                throw new InvalidArgumentException('Không thể tạo redirect vì page đích chưa được publish/active.');
            }
        }

        $this->paths->assertRedirectable($sourcePath, $targetPath);
        $this->assertNoRedirectCycle($sourcePath, $targetPath);

        LegacyMigrationRedirect::query()->updateOrCreate(
            ['source_hash' => hash('sha256', $sourcePath)],
            [
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
                'status_code' => $url->redirect_code,
                'is_active' => true,
                'run_id' => $url->run_id,
                'staged_url_id' => $url->id,
            ],
        );
        PublicUrlMapping::query()->updateOrCreate(
            ['source_hash' => hash('sha256', $sourcePath)],
            [
                'source_path' => $sourcePath,
                'mode' => PublicUrlMapping::MODE_REDIRECT,
                'target_type' => $url->target_type,
                'target_id' => $url->target_id,
                'target_path' => $targetPath,
                'status_code' => $url->redirect_code,
                'is_active' => true,
                'origin' => 'legacy_migration',
                'context_json' => [
                    'legacy_run_uuid' => $url->run?->uuid,
                    'legacy_staged_url_id' => $url->id,
                ],
                'created_by' => $actor->id,
            ],
        );
    }

    private function useTargetAsCanonical(Model $target, string $targetPath): void
    {
        if (! in_array('canonical_url', $target->getFillable(), true)) {
            return;
        }

        $canonicalUrl = FrontsiteUrls::canonicalUrl($targetPath);

        if ($target->getAttribute('canonical_url') === $canonicalUrl) {
            return;
        }

        $target::withoutTimestamps(fn () => $target->newModelQuery()
            ->whereKey($target->getKey())
            ->update(['canonical_url' => $canonicalUrl]));
        $target->setAttribute('canonical_url', $canonicalUrl);
    }

    private function isPubliclyRenderable(Model $target): bool
    {
        if ($target instanceof ContentCategory) {
            return $target->taxonomy === 'blog';
        }

        if ($target instanceof LandingPage) {
            return $target->page_key === null && (bool) $target->is_active;
        }

        if (! in_array($target::class, [BlogPost::class, Tour::class, TourCategory::class, Destination::class, Region::class, Service::class], true)) {
            return false;
        }

        return $target::query()->published()->whereKey($target->getKey())->exists();
    }

    private function assertNoRedirectCycle(string $sourcePath, string $targetPath): void
    {
        $visited = [$sourcePath => true];
        $currentPath = $targetPath;

        for ($hop = 0; $hop < 100; $hop++) {
            if (isset($visited[$currentPath])) {
                throw new InvalidArgumentException('Redirect tạo thành vòng lặp URL.');
            }

            $visited[$currentPath] = true;
            $nextPath = PublicUrlMapping::query()
                ->where('source_hash', hash('sha256', $currentPath))
                ->where('source_path', $currentPath)
                ->where('mode', PublicUrlMapping::MODE_REDIRECT)
                ->where('is_active', true)
                ->value('target_path');
            $nextPath ??= LegacyMigrationRedirect::query()
                ->where('source_hash', hash('sha256', $currentPath))
                ->where('source_path', $currentPath)
                ->where('is_active', true)
                ->value('target_path');

            if (! is_string($nextPath) || $nextPath === '') {
                return;
            }

            $currentPath = $this->paths->normalize($nextPath);
        }

        throw new InvalidArgumentException('Chuỗi redirect vượt quá giới hạn an toàn.');
    }

    private function auditRedirect(LegacyStagedUrl $url, User $actor): void
    {
        LegacyCastAudit::query()->create([
            'run_id' => $url->run_id,
            'staged_url_id' => $url->id,
            'target_type' => $url->target_type,
            'target_id' => $url->target_id,
            'action' => 'redirect_only',
            'before_json' => null,
            'after_json' => ['source_path' => $url->normalized_path, 'target_path' => $url->target_path],
            'actor_id' => $actor->id,
            'status' => 'completed',
        ]);
    }

    private function recordFailure(LegacyStagedUrl $url, User $actor, Throwable $exception): void
    {
        $message = $exception instanceof ValidationException
            ? collect($exception->errors())->flatten()->filter(fn (mixed $item): bool => is_string($item))->implode(' ')
            : $exception->getMessage();
        $message = mb_substr(trim($message), 0, 2000);
        $url->forceFill(['status' => 'failed', 'error_text' => $message])->save();
        LegacyCastAudit::query()->create([
            'run_id' => $url->run_id,
            'staged_url_id' => $url->id,
            'target_type' => $url->target_type,
            'target_id' => $url->target_id,
            'action' => (string) $url->mapping_mode,
            'merge_policy' => $url->merge_policy,
            'timestamp_policy' => $url->timestamp_policy,
            'actor_id' => $actor->id,
            'status' => 'failed',
            'error_text' => $message,
            'after_json' => $exception instanceof LegacyImageDownloadFailure ? $exception->context() : null,
        ]);
        $this->counter->refresh($url->run);
    }

    private function relatedObject(LegacyStagedObject $source, string $key): ?LegacyStagedObject
    {
        return LegacyStagedObject::query()->where('run_id', $source->run_id)->where('object_key', $key)->first();
    }

    private function mappedTargetId(LegacyStagedUrl $url, string $objectKey, string $targetType): ?int
    {
        $source = LegacyStagedObject::query()->where('run_id', $url->run_id)->where('object_key', $objectKey)->first();

        if (! $source) {
            return null;
        }

        $identity = LegacyObjectMap::identity(
            (string) $url->run->source_system,
            $source->object_type,
            $source->legacy_id,
            $source->legacy_key,
        );

        return LegacyObjectMap::query()
            ->where('source_identity', $identity)
            ->where('target_type', $targetType)
            ->value('target_id');
    }

    private function firstMappedTarget(LegacyStagedUrl $url, array $keys, string $targetType): ?int
    {
        return collect($keys)
            ->filter(fn (mixed $key): bool => is_string($key))
            ->map(fn (string $key): ?int => $this->mappedTargetId($url, $key, $targetType))
            ->filter()
            ->first();
    }

    private function mappedTargetIds(LegacyStagedUrl $url, array $keys, string $targetType): array
    {
        return collect($keys)
            ->filter(fn (mixed $key): bool => is_string($key))
            ->map(fn (string $key): ?int => $this->mappedTargetId($url, $key, $targetType))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function first(array $attributes, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $attributes) && $attributes[$key] !== null && $attributes[$key] !== '') {
                return $attributes[$key];
            }
        }

        return null;
    }

    private function present(array $values): array
    {
        return array_filter($values, fn (mixed $value): bool => $value !== null);
    }

    private function plain(mixed $value): ?string
    {
        return $value === null ? null : RichText::normalizePlain((string) $value);
    }

    /** @param array<string, string> $replacements */
    private function html(mixed $value, array $replacements = []): ?string
    {
        if ($value === null) {
            return null;
        }

        $html = $replacements === [] ? (string) $value : strtr((string) $value, $replacements);

        return FrontsiteUrls::normalizeInternalHtml(RichText::sanitize($html));
    }

    private function integer(mixed $value): ?int
    {
        return $value === null ? null : $this->values->integer($value);
    }

    private function money(mixed $value): ?int
    {
        return $value === null ? null : $this->values->money($value);
    }

    private function boolean(mixed $value): ?bool
    {
        return $value === null ? null : $this->values->boolean($value);
    }

    private function featured(array $attributes): ?bool
    {
        $values = array_filter([
            $this->boolean($this->first($attributes, ['homepage'])),
            $this->boolean($this->first($attributes, ['isTopHome'])),
            $this->boolean($this->first($attributes, ['isSuggest'])),
        ], fn (mixed $value): bool => $value !== null);

        return $values === [] ? null : in_array(true, $values, true);
    }

    private function robots(array $attributes): ?string
    {
        if (! array_key_exists('no_index', $attributes)) {
            return null;
        }

        return $this->values->boolean($attributes['no_index']) ? 'noindex,follow' : 'index,follow';
    }
}
