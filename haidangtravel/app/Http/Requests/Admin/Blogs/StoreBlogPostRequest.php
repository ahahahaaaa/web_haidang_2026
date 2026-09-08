<?php

namespace App\Http\Requests\Admin\Blogs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.blogs.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'faq_items' => ['nullable', 'array'],
            'faq_items.*.question' => ['nullable', 'string', 'max:500'],
            'faq_items.*.answer' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', 'max:50'],
            'content_category_id' => ['nullable', 'integer', Rule::exists('content_categories', 'id')->where('taxonomy', 'blog')],
            'content_category_slug' => ['nullable', 'string', Rule::exists('content_categories', 'slug')->where('taxonomy', 'blog')],
            'author_name' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'cover_alt' => ['nullable', 'string', 'max:255'],
            'cover_library_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'detach_cover' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url'],
            'robots_directive' => ['nullable', 'string', 'max:255'],
            'schema' => ['nullable', 'array'],
        ];
    }
}
