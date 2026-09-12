<?php

namespace App\Services\Cms;

use App\Support\ContentCategoryTree;
use App\Support\FaqContent;
use App\Support\RichText;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;

class BlogPostManager
{
    public function categories()
    {
        return ContentCategoryTree::flatten(
            ContentCategory::query()
                ->forTaxonomy('blog')
                ->with('parent')
                ->ordered()
                ->get()
        );
    }

    public function paginateForAdmin(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);
        $search = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $categorySlug = trim((string) ($filters['category_slug'] ?? ''));

        return BlogPost::query()
            ->with(['category.parent', 'countryDestination', 'destination.country'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhere('excerpt', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($categorySlug !== '', fn (Builder $query) => $query->whereHas('category', fn (Builder $category) => $category->where('slug', $categorySlug)))
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function save(array $payload, ?BlogPost $post = null, ?Authenticatable $actor = null): BlogPost
    {
        $post ??= new BlogPost;

        $title = array_key_exists('title', $payload)
            ? trim((string) $payload['title'])
            : (string) $post->title;

        $slugSeed = array_key_exists('slug', $payload)
            ? trim((string) ($payload['slug'] ?: $title))
            : ($post->slug ?: $title);

        $content = array_key_exists('content', $payload)
            ? RichText::sanitize($payload['content'])
            : (string) ($post->content ?? '');

        $excerpt = array_key_exists('excerpt', $payload)
            ? RichText::normalizePlain($payload['excerpt'])
            : (string) ($post->excerpt ?? '');

        $metaDescription = array_key_exists('meta_description', $payload)
            ? RichText::normalizePlain($payload['meta_description'])
            : $post->meta_description;

        $ogDescription = array_key_exists('og_description', $payload)
            ? RichText::normalizePlain($payload['og_description'])
            : $post->og_description;
        $geoIds = $this->resolveBlogGeoIds($payload, $post);

        $post->fill([
            'title' => $title,
            'slug' => $this->resolveUniqueSlug($slugSeed, $post),
            'excerpt' => $excerpt !== '' ? $excerpt : null,
            'content' => $content !== '' ? $content : null,
            'faq_items' => array_key_exists('faq_items', $payload)
                ? FaqContent::normalizeItems($payload['faq_items'])
                : ($post->faq_items ?? []),
            'status' => array_key_exists('status', $payload)
                ? trim((string) $payload['status'])
                : ($post->status ?: 'draft'),
            'content_category_id' => $this->resolveCategoryId($payload, $post),
            'country_destination_id' => $geoIds['country_destination_id'],
            'destination_id' => $geoIds['destination_id'],
            'author_name' => $this->resolveAuthorName($payload, $post, $actor),
            'published_at' => array_key_exists('published_at', $payload) ? $payload['published_at'] : $post->published_at,
            'is_featured' => array_key_exists('is_featured', $payload) ? (bool) $payload['is_featured'] : (bool) $post->is_featured,
            'sort_order' => array_key_exists('sort_order', $payload) ? (int) ($payload['sort_order'] ?? 0) : (int) ($post->sort_order ?? 0),
            'cover_alt' => array_key_exists('cover_alt', $payload) ? $this->nullableString($payload['cover_alt']) : $post->cover_alt,
            'meta_title' => array_key_exists('meta_title', $payload) ? $this->nullableString($payload['meta_title']) : $post->meta_title,
            'meta_description' => $metaDescription !== '' ? $metaDescription : null,
            'og_title' => array_key_exists('og_title', $payload) ? $this->nullableString($payload['og_title']) : $post->og_title,
            'og_description' => $ogDescription !== '' ? $ogDescription : null,
            'canonical_url' => array_key_exists('canonical_url', $payload) ? $this->nullableString($payload['canonical_url']) : $post->canonical_url,
            'robots_directive' => array_key_exists('robots_directive', $payload)
                ? ($this->nullableString($payload['robots_directive']) ?: 'index,follow')
                : ($post->robots_directive ?: 'index,follow'),
            'schema' => array_key_exists('schema', $payload) ? $payload['schema'] : $post->schema,
            'reading_time_minutes' => $this->calculateReadingTime($content),
        ]);

        $post->save();

        if ((bool) ($payload['detach_cover'] ?? false)) {
            $post->clearMediaCollection('cover');
        } elseif (array_key_exists('cover_library_media_id', $payload)) {
            $this->syncCoverFromLibrary(
                post: $post,
                mediaId: $payload['cover_library_media_id'],
                coverAlt: $post->cover_alt,
            );
        }

        return $post->fresh(['category.parent', 'countryDestination', 'destination.country']);
    }

    public function delete(BlogPost $post): void
    {
        $post->delete();
    }

    protected function calculateReadingTime(string $content): ?int
    {
        $plainText = RichText::normalizePlain($content);

        if ($plainText === '') {
            return null;
        }

        $words = preg_split('/\s+/u', $plainText, -1, PREG_SPLIT_NO_EMPTY);

        return max(1, (int) ceil(count($words ?: []) / 220));
    }

    protected function nullableString(mixed $value): ?string
    {
        $resolved = trim((string) ($value ?? ''));

        return $resolved !== '' ? $resolved : null;
    }

    protected function resolveAuthorName(array $payload, BlogPost $post, ?Authenticatable $actor): string
    {
        if (array_key_exists('author_name', $payload)) {
            return trim((string) ($payload['author_name'] ?? '')) ?: $this->fallbackAuthorName($actor);
        }

        return $post->author_name ?: $this->fallbackAuthorName($actor);
    }

    protected function fallbackAuthorName(?Authenticatable $actor): string
    {
        $name = trim((string) data_get($actor, 'name'));

        return $name !== '' ? $name : 'Ban biên tập';
    }

    protected function resolveCategoryId(array $payload, BlogPost $post): ?int
    {
        if (array_key_exists('content_category_id', $payload)) {
            return $payload['content_category_id'] ? (int) $payload['content_category_id'] : null;
        }

        if (array_key_exists('content_category_slug', $payload)) {
            $slug = trim((string) ($payload['content_category_slug'] ?? ''));

            if ($slug === '') {
                return null;
            }

            return ContentCategory::query()
                ->forTaxonomy('blog')
                ->where('slug', $slug)
                ->value('id');
        }

        return $post->content_category_id;
    }

    /**
     * @return array{country_destination_id: int|null, destination_id: int|null}
     */
    protected function resolveBlogGeoIds(array $payload, BlogPost $post): array
    {
        $countryId = array_key_exists('country_destination_id', $payload)
            ? $this->nullablePositiveInt($payload['country_destination_id'])
            : $this->nullablePositiveInt($post->country_destination_id);
        $destinationId = array_key_exists('destination_id', $payload)
            ? $this->nullablePositiveInt($payload['destination_id'])
            : $this->nullablePositiveInt($post->destination_id);

        $country = $countryId
            ? Destination::query()->countryRoots()->find($countryId)
            : null;
        $destination = $destinationId
            ? Destination::query()->regularDestinations()->find($destinationId)
            : null;

        if ($countryId && ! $country) {
            throw ValidationException::withMessages([
                'country_destination_id' => 'Quốc gia phải là một điểm đến root hợp lệ.',
            ]);
        }

        if ($destinationId && ! $destination) {
            throw ValidationException::withMessages([
                'destination_id' => 'Điểm đến phải là điểm đến con hợp lệ, không phải quốc gia root.',
            ]);
        }

        if ($destination && ! $countryId && filled($destination->country_id)) {
            $countryId = (int) $destination->country_id;
        }

        if ($destination && $countryId && (int) $destination->country_id !== (int) $countryId) {
            throw ValidationException::withMessages([
                'destination_id' => 'Điểm đến đã chọn không thuộc quốc gia của bài viết.',
            ]);
        }

        return [
            'country_destination_id' => $countryId,
            'destination_id' => $destination?->getKey() ? (int) $destination->getKey() : null,
        ];
    }

    protected function nullablePositiveInt(mixed $value): ?int
    {
        if (! filled($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    protected function resolveUniqueSlug(string $seed, BlogPost $post): string
    {
        $base = Str::slug($seed);
        $base = $base !== '' ? $base : 'blog-post';
        $slug = $base;
        $suffix = 2;

        while (
            BlogPost::query()
                ->where('slug', $slug)
                ->when($post->exists, fn (Builder $query) => $query->whereKeyNot($post->getKey()))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    protected function syncCoverFromLibrary(BlogPost $post, mixed $mediaId, ?string $coverAlt): void
    {
        if (! $mediaId) {
            return;
        }

        $sourceMedia = Media::query()
            ->whereKey($mediaId)
            ->where('mime_type', 'like', 'image/%')
            ->first();

        if (! $sourceMedia) {
            return;
        }

        $resolvedProperties = array_filter([
            ...($sourceMedia->custom_properties ?? []),
            'alt' => $coverAlt,
            'source_library_media_id' => (int) $sourceMedia->getKey(),
        ], static fn ($value) => $value !== null);

        $currentMedia = $post->getFirstMedia('cover');

        if ($currentMedia && (int) data_get($currentMedia->custom_properties, 'source_library_media_id') === (int) $sourceMedia->getKey()) {
            foreach ($resolvedProperties as $key => $value) {
                $currentMedia->setCustomProperty($key, $value);
            }

            $currentMedia->save();

            return;
        }

        $post->clearMediaCollection('cover');

        $sourceMedia->copy(
            model: $post,
            collectionName: 'cover',
            diskName: config('media-library.disk_name', 'public'),
            fileAdderCallback: fn ($fileAdder) => $fileAdder->withCustomProperties($resolvedProperties),
        );
    }
}
