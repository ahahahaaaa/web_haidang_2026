<?php

namespace App\Http\Requests\Admin\Blogs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlogAutomationJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.blogs.edit') ?? false;
    }

    public function rules(): array
    {
        $maxReferences = max(1, (int) config('blog_automation.max_reference_urls', 5));

        return [
            'target_post_id' => ['nullable', 'integer', 'exists:blog_posts,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'content_category_id' => ['nullable', 'integer', Rule::exists('content_categories', 'id')->where('taxonomy', 'blog')],
            'content_category_slug' => ['nullable', 'string', Rule::exists('content_categories', 'slug')->where('taxonomy', 'blog')],
            'author_name' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'cover_alt' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url'],
            'robots_directive' => ['nullable', 'string', 'max:255'],
            'additional_instructions' => ['nullable', 'string', 'max:5000'],
            'reference_urls' => ['required', 'array', 'min:1', 'max:'.$maxReferences],
            'reference_urls.*' => ['required', 'url', 'max:2048'],
            'max_references' => ['nullable', 'integer', 'min:1', 'max:'.$maxReferences],
        ];
    }
}
