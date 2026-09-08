<?php

namespace App\Http\Requests\Admin\Blogs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.blogs.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'excerpt' => ['sometimes', 'nullable', 'string'],
            'content' => ['sometimes', 'nullable', 'string'],
            'faq_items' => ['sometimes', 'nullable', 'array'],
            'faq_items.*.question' => ['nullable', 'string', 'max:500'],
            'faq_items.*.answer' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', 'required', 'string', 'max:50'],
            'content_category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('content_categories', 'id')->where('taxonomy', 'blog')],
            'content_category_slug' => ['sometimes', 'nullable', 'string', Rule::exists('content_categories', 'slug')->where('taxonomy', 'blog')],
            'author_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'cover_alt' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cover_library_media_id' => ['sometimes', 'nullable', 'integer', Rule::exists('media', 'id')],
            'detach_cover' => ['sometimes', 'boolean'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'og_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'og_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'canonical_url' => ['sometimes', 'nullable', 'url'],
            'robots_directive' => ['sometimes', 'nullable', 'string', 'max:255'],
            'schema' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
