<?php

namespace App\Modules\LegacyMigration\Services;

use App\Models\User;
use App\Modules\LegacyMigration\Models\LegacyObjectMap;
use App\Modules\LegacyMigration\Models\LegacyStagedObject;
use App\Modules\LegacyMigration\Models\LegacyStagedUrl;
use App\Services\Cms\BlogPostManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Src\Domains\Cms\Models\BlogPost;

class LegacyAutomaticTargetResolver
{
    private const SOURCE_TARGET_TYPES = [
        'blog' => 'blog_post',
        'subject_blog' => 'blog_category',
        'tour' => 'tour',
        'subject_tour' => 'tour_category',
        'destination' => 'destination',
        'region' => 'region',
    ];

    public function __construct(
        private LegacyTargetRegistry $targets,
        private BlogPostManager $blogPosts,
        private LegacyValueCaster $values,
    ) {}

    /** @return array<string, string> */
    public function sourceTypeLabels(): array
    {
        return [
            'blog' => 'Bài viết blog',
            'subject_blog' => 'Danh mục blog',
            'tour' => 'Tour',
            'subject_tour' => 'Chủ đề tour',
            'destination' => 'Điểm đến / quốc gia',
            'region' => 'Vùng miền',
        ];
    }

    /** @return array<string, string> */
    public function strategyLabels(string $sourceType): array
    {
        if ($sourceType === 'blog') {
            return [
                'create_new' => 'Tạo bài mới (mặc định)',
                'match_slug' => 'Ghép bài hiện có cùng slug',
            ];
        }

        return ['match_slug' => 'Ghép bản ghi hiện có cùng slug'];
    }

    public function targetType(string $sourceType): string
    {
        return self::SOURCE_TARGET_TYPES[$sourceType]
            ?? throw new InvalidArgumentException('Loại dữ liệu nguồn chưa hỗ trợ xử lý tự động.');
    }

    public function resolve(LegacyStagedUrl $url, string $sourceType, string $strategy, User $actor): Model
    {
        if (! array_key_exists($strategy, $this->strategyLabels($sourceType))) {
            throw new InvalidArgumentException('Cách tự động xử lý không hợp lệ cho loại dữ liệu đã chọn.');
        }

        return DB::transaction(function () use ($url, $sourceType, $strategy, $actor): Model {
            $source = LegacyStagedObject::query()
                ->where('run_id', $url->run_id)
                ->where('object_key', $url->root_object_key)
                ->lockForUpdate()
                ->first();

            if (! $source || $source->object_type !== $sourceType) {
                throw new InvalidArgumentException('URL không có root object đúng loại đã chọn.');
            }

            if ($source->is_partial) {
                throw new InvalidArgumentException('Root object chỉ là bản partial, chưa thể tự động cast.');
            }

            $targetType = $this->targetType($sourceType);
            $identity = LegacyObjectMap::identity(
                (string) $url->run->source_system,
                $source->object_type,
                $source->legacy_id,
                $source->legacy_key,
            );
            $map = LegacyObjectMap::query()->where('source_identity', $identity)->lockForUpdate()->first();

            if ($map && $map->target_type === $targetType) {
                try {
                    return $this->targets->resolve($targetType, $map->target_id);
                } catch (ModelNotFoundException) {
                    // Target was removed after a previous migration attempt; resolve it again below.
                }
            }

            $slug = $this->sourceSlug($url, $source);
            $target = $strategy === 'create_new'
                ? $this->createTarget($sourceType, $source, $slug, $actor)
                : $this->targets->resolveBySlug($targetType, $slug);

            if (! $target instanceof Model) {
                throw new InvalidArgumentException("Không tìm thấy bản ghi đích có slug [{$slug}].");
            }

            LegacyObjectMap::query()->updateOrCreate(
                ['source_identity' => $identity],
                [
                    'source_system' => $url->run->source_system,
                    'object_type' => $source->object_type,
                    'legacy_id' => $source->legacy_id,
                    'legacy_key' => $source->legacy_key,
                    'target_type' => $targetType,
                    'target_id' => (string) $target->getKey(),
                    'last_checksum' => $source->checksum,
                    'is_partial' => false,
                    'first_run_id' => $map?->first_run_id ?: $url->run_id,
                    'last_run_id' => $url->run_id,
                ],
            );

            return $target;
        });
    }

    public function resolveExisting(LegacyStagedUrl $url, string $sourceType): Model
    {
        return DB::transaction(function () use ($url, $sourceType): Model {
            $source = LegacyStagedObject::query()
                ->where('run_id', $url->run_id)
                ->where('object_key', $url->root_object_key)
                ->lockForUpdate()
                ->first();

            if (! $source || $source->object_type !== $sourceType || $source->is_partial) {
                throw new InvalidArgumentException('Không thể khôi phục: thiếu root object đầy đủ đúng loại nguồn.');
            }

            $targetType = $this->targetType($sourceType);
            $targetId = $url->target_id;

            if (filled($targetId) || filled($url->target_type)) {
                if (! filled($targetId) || $url->target_type !== $targetType) {
                    throw new InvalidArgumentException('Không thể khôi phục: target đã lưu không tương thích dữ liệu nguồn.');
                }
            } else {
                $identity = LegacyObjectMap::identity(
                    (string) $url->run->source_system,
                    $source->object_type,
                    $source->legacy_id,
                    $source->legacy_key,
                );
                $map = LegacyObjectMap::query()->where('source_identity', $identity)->lockForUpdate()->first();

                if (! $map || $map->target_type !== $targetType) {
                    throw new InvalidArgumentException('Không thể khôi phục: chưa có target hoặc object map; hệ thống không tạo bản ghi mới.');
                }

                $targetId = $map->target_id;
            }

            try {
                return $this->targets->resolve($targetType, (string) $targetId);
            } catch (ModelNotFoundException) {
                throw new InvalidArgumentException('Không thể khôi phục: target cũ đã bị xóa; hệ thống không tạo bản ghi mới.');
            }
        });
    }

    private function createTarget(string $sourceType, LegacyStagedObject $source, string $slug, User $actor): Model
    {
        if ($sourceType !== 'blog') {
            throw new InvalidArgumentException('Hiện chỉ bài blog được phép tự động tạo bản ghi mới.');
        }

        $attributes = (array) Arr::get($source->payload_json, 'attributes', []);
        $title = trim((string) ($attributes['title'] ?? ''));
        $title = $title !== '' ? $title : 'Bài viết migrate '.$source->legacy_id;
        $published = array_key_exists('publish', $attributes)
            && $this->values->boolean($attributes['publish']);

        return $this->blogPosts->save([
            'title' => $title,
            'slug' => $slug,
            'status' => $published ? 'published' : 'draft',
            'published_at' => $published ? ($attributes['published_at'] ?? $attributes['created_at'] ?? $source->source_created_at) : null,
            'author_name' => trim((string) $actor->name) ?: 'Ban biên tập',
            'cover_alt' => $title,
        ], new BlogPost, $actor);
    }

    private function sourceSlug(LegacyStagedUrl $url, LegacyStagedObject $source): string
    {
        $attributes = (array) Arr::get($source->payload_json, 'attributes', []);
        $slug = Str::slug((string) ($attributes['slug'] ?? ''));

        if ($slug === '') {
            $path = trim((string) parse_url($url->normalized_path, PHP_URL_PATH), '/');
            $slug = Str::slug((string) Str::afterLast($path, '/'));
        }

        if ($slug === '') {
            $slug = Str::slug($source->object_type.'-'.($source->legacy_id ?: $source->legacy_key));
        }

        return $slug !== '' ? $slug : 'legacy-item';
    }
}
