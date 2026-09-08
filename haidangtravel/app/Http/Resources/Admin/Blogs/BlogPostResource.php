<?php

namespace App\Http\Resources\Admin\Blogs;

use App\Support\ContentCategoryTree;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cover = $this->getFirstMedia('cover');
        $includeContent = ! $request->routeIs('api.v1.admin.blogs.index') || $request->boolean('include_content');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $includeContent ? $this->content : null,
            'faq_items' => $this->faq_items ?? [],
            'status' => $this->status,
            'author_name' => $this->author_name,
            'published_at' => optional($this->published_at)?->toIso8601String(),
            'is_featured' => (bool) $this->is_featured,
            'sort_order' => (int) $this->sort_order,
            'cover_alt' => $this->cover_alt,
            'cover' => [
                'media_id' => $cover?->getKey(),
                'name' => $cover?->name,
                'url' => $cover?->getUrl(),
                'alt' => data_get($cover?->custom_properties, 'alt', $this->cover_alt),
                'source_library_media_id' => data_get($cover?->custom_properties, 'source_library_media_id'),
            ],
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'og_title' => $this->og_title,
            'og_description' => $this->og_description,
            'canonical_url' => $this->canonical_url,
            'robots_directive' => $this->robots_directive,
            'schema' => $this->schema,
            'reading_time_minutes' => $this->reading_time_minutes,
            'category' => $this->category ? [
                'id' => $this->category->getKey(),
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'path' => ContentCategoryTree::pathLabel($this->category),
                'parent' => $this->category->parent ? [
                    'id' => $this->category->parent->getKey(),
                    'name' => $this->category->parent->name,
                    'slug' => $this->category->parent->slug,
                ] : null,
            ] : null,
            'links' => [
                'public_url' => route('blog.show', $this->resource),
            ],
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
