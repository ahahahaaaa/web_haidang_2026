<?php

namespace App\Services\SeoOptimization;

use App\Support\LandingPageBlocks;
use Illuminate\Validation\ValidationException;

class ContentCreationRegistry
{
    public const CONTRACT_VERSION = 'cms-content-creation-v1';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $seoFields = [
            'meta_title' => ['kind' => 'plain_text', 'max' => 255, 'required' => true],
            'meta_description' => ['kind' => 'plain_text', 'max' => 500, 'required' => true],
            'og_title' => ['kind' => 'plain_text', 'max' => 255, 'required' => false],
            'og_description' => ['kind' => 'plain_text', 'max' => 500, 'required' => false],
        ];
        $faq = ['faq_items' => ['kind' => 'faq', 'max_items' => 20, 'required' => false]];

        return [
            'blog_post' => [
                'label' => 'Bài viết', 'permission' => 'admin.blogs.edit', 'commit_mode' => 'draft',
                'editor_route' => 'admin.blogs.edit', 'identity_field' => 'title', 'representative_slot' => 'cover',
                'fields' => [
                    'title' => ['kind' => 'plain_text', 'max' => 255, 'required' => true],
                    'slug' => ['kind' => 'slug', 'max' => 180, 'required' => false],
                    'excerpt' => ['kind' => 'plain_text', 'max' => 1000, 'required' => true],
                    'content' => ['kind' => 'rich_html', 'max' => 150000, 'required' => true],
                    'content_category_id' => ['kind' => 'relation', 'target' => 'blog_category', 'required' => false],
                    'country_destination_id' => ['kind' => 'relation', 'target' => 'country', 'required' => false],
                    'destination_id' => ['kind' => 'relation', 'target' => 'destination', 'required' => false],
                    'author_name' => ['kind' => 'plain_text', 'max' => 255, 'required' => false],
                    'cover_alt' => ['kind' => 'plain_text', 'max' => 255, 'required' => false],
                    ...$faq, ...$seoFields,
                ],
                'media_slots' => ['cover', 'content'],
            ],
            'tour' => [
                'label' => 'Tour', 'permission' => 'admin.tours.edit', 'commit_mode' => 'draft',
                'editor_route' => 'admin.tours.edit', 'identity_field' => 'title', 'representative_slot' => 'cover',
                'fields' => [
                    'title' => ['kind' => 'plain_text', 'max' => 255, 'required' => true],
                    'slug' => ['kind' => 'slug', 'max' => 180, 'required' => false],
                    'excerpt' => ['kind' => 'plain_text', 'max' => 1000, 'required' => true],
                    'content' => ['kind' => 'rich_html', 'max' => 150000, 'required' => true],
                    'scope' => ['kind' => 'enum', 'values' => ['domestic', 'international', 'group'], 'required' => true],
                    'tour_category_id' => ['kind' => 'relation', 'target' => 'tour_category', 'required' => false],
                    'destination_id' => ['kind' => 'relation', 'target' => 'destination', 'required' => false],
                    'region_id' => ['kind' => 'relation', 'target' => 'region', 'required' => false],
                    'transport' => ['kind' => 'plain_text', 'max' => 255, 'required' => false],
                    'departure_location' => ['kind' => 'plain_text', 'max' => 255, 'required' => false],
                    'duration_days' => ['kind' => 'integer', 'min' => 0, 'max' => 365, 'required' => false],
                    'duration_nights' => ['kind' => 'integer', 'min' => 0, 'max' => 365, 'required' => false],
                    'standard_label' => ['kind' => 'plain_text', 'max' => 255, 'required' => false],
                    'itinerary' => ['kind' => 'itinerary', 'max_items' => 60, 'required' => false],
                    'inclusions' => ['kind' => 'string_list', 'max_items' => 60, 'required' => false],
                    'tour_terms_items' => ['kind' => 'faq', 'max_items' => 30, 'required' => false],
                    'cover_alt' => ['kind' => 'plain_text', 'max' => 255, 'required' => false],
                    ...$faq, ...$seoFields,
                ],
                'media_slots' => ['cover', 'content', 'gallery'],
                'server_only_fields' => ['status', 'published_at', 'canonical_url', 'schema', 'base_price', 'sale_price', 'rating_average', 'rating_count', 'departure_schedules'],
            ],
            'service' => [
                'label' => 'Dịch vụ', 'permission' => 'admin.services.edit', 'commit_mode' => 'draft',
                'editor_route' => 'admin.services.edit', 'identity_field' => 'title', 'representative_slot' => 'cover',
                'fields' => [
                    'title' => ['kind' => 'plain_text', 'max' => 255, 'required' => true],
                    'slug' => ['kind' => 'slug', 'max' => 180, 'required' => false],
                    'excerpt' => ['kind' => 'plain_text', 'max' => 1000, 'required' => true],
                    'content' => ['kind' => 'rich_html', 'max' => 150000, 'required' => true],
                    'content_category_id' => ['kind' => 'relation', 'target' => 'service_category', 'required' => false],
                    'icon_class' => ['kind' => 'plain_text', 'max' => 255, 'required' => false],
                    'price_note' => ['kind' => 'plain_text', 'max' => 1000, 'required' => false],
                    'related_questions' => ['kind' => 'string_list', 'max_items' => 20, 'required' => false],
                    'cover_alt' => ['kind' => 'plain_text', 'max' => 255, 'required' => false],
                    ...$faq, ...$seoFields,
                ],
                'media_slots' => ['cover', 'content', 'gallery'],
            ],
            'tour_category' => $this->taxonomy('Chủ đề tour', 'admin.tours.categories.edit', 'admin.tours.categories.edit', true),
            'destination' => [
                ...$this->taxonomy('Điểm đến', 'admin.tours.destinations.edit', 'admin.tours.destinations.edit', true),
                'fields' => [
                    ...$this->taxonomyFields(true),
                    'country_id' => ['kind' => 'relation', 'target' => 'country', 'required' => true],
                    'region_id' => ['kind' => 'relation', 'target' => 'region', 'required' => false],
                ],
            ],
            'country' => [
                ...$this->taxonomy('Quốc gia', 'admin.tours.destinations.edit', 'admin.tours.destinations.edit', true),
                'slug_rule' => 'country_slug',
            ],
            'region' => $this->taxonomy('Vùng / miền', 'admin.tours.regions.edit', 'admin.tours.regions.edit', false),
            'blog_category' => $this->contentCategory('Danh mục bài viết', 'blog', 'admin.blogs.categories.edit', 'admin.blogs.categories.edit'),
            'service_category' => $this->contentCategory('Danh mục dịch vụ', 'service', 'admin.services.categories.edit', 'admin.services.categories.edit'),
            'landing' => [
                'label' => 'Landing page custom', 'permission' => 'admin.landing-pages.edit', 'commit_mode' => 'inactive',
                'editor_route' => 'admin.landing-pages.edit', 'identity_field' => 'title', 'representative_slot' => null,
                'fields' => [
                    'title' => ['kind' => 'plain_text', 'max' => 255, 'required' => true],
                    'slug' => ['kind' => 'slug', 'max' => 180, 'required' => true],
                    'template_key' => ['kind' => 'enum', 'values' => array_keys(LandingPageBlocks::templates()), 'required' => true],
                    'editor_mode' => ['kind' => 'enum', 'values' => ['blocks', 'html'], 'required' => true],
                    'body' => ['kind' => 'manual_html', 'max' => 250000, 'required' => false],
                    'blocks' => ['kind' => 'landing_blocks', 'max_items' => 50, 'required' => false],
                    ...$seoFields,
                ],
                'media_slots' => ['content', 'landing_block'],
                'allowed_block_types' => ['hero_media', 'gallery_media', 'html_widget', 'rich_text', 'trust_proof', 'cta', 'faq'],
                'server_only_fields' => ['page_key', 'is_active', 'canonical_url', 'schema', 'home_config', 'visual_config'],
            ],
        ];
    }

    public function get(string $type): array
    {
        $contract = $this->all()[$type] ?? null;
        if (! is_array($contract)) {
            throw ValidationException::withMessages(['content_type' => 'Loại nội dung CMS không được hỗ trợ.']);
        }

        return ['contract_version' => self::CONTRACT_VERSION, 'content_type' => $type, ...$contract];
    }

    /** @return array<int, string> */
    public function types(): array
    {
        return array_keys($this->all());
    }

    private function taxonomy(string $label, string $permission, string $route, bool $faq): array
    {
        return [
            'label' => $label, 'permission' => $permission, 'commit_mode' => 'draft',
            'editor_route' => $route, 'identity_field' => 'name', 'representative_slot' => 'avatar',
            'fields' => $this->taxonomyFields($faq),
            'media_slots' => ['avatar', 'content', 'gallery'],
            'server_only_fields' => ['status', 'published_at', 'canonical_url', 'schema', 'rating_average', 'rating_count'],
        ];
    }

    private function taxonomyFields(bool $faq): array
    {
        $fields = [
            'name' => ['kind' => 'plain_text', 'max' => 255, 'required' => true],
            'slug' => ['kind' => 'slug', 'max' => 180, 'required' => false],
            'scope' => ['kind' => 'enum', 'values' => ['domestic', 'international', 'group'], 'required' => false],
            'excerpt' => ['kind' => 'plain_text', 'max' => 1000, 'required' => true],
            'content' => ['kind' => 'rich_html', 'max' => 150000, 'required' => true],
            'cover_alt' => ['kind' => 'plain_text', 'max' => 255, 'required' => false],
            'meta_title' => ['kind' => 'plain_text', 'max' => 255, 'required' => true],
            'meta_description' => ['kind' => 'plain_text', 'max' => 500, 'required' => true],
            'og_title' => ['kind' => 'plain_text', 'max' => 255, 'required' => false],
            'og_description' => ['kind' => 'plain_text', 'max' => 500, 'required' => false],
        ];
        if ($faq) {
            $fields['faq_items'] = ['kind' => 'faq', 'max_items' => 20, 'required' => false];
        }

        return $fields;
    }

    private function contentCategory(string $label, string $taxonomy, string $permission, string $route): array
    {
        return [
            'label' => $label, 'taxonomy' => $taxonomy, 'permission' => $permission,
            'commit_mode' => 'manual_review', 'editor_route' => $route, 'identity_field' => 'name',
            'representative_slot' => 'avatar',
            'fields' => [
                'name' => ['kind' => 'plain_text', 'max' => 255, 'required' => true],
                'slug' => ['kind' => 'slug', 'max' => 180, 'required' => false],
                'parent_id' => ['kind' => 'relation', 'target' => $taxonomy.'_category', 'required' => false],
                'description' => ['kind' => 'plain_text', 'max' => 3000, 'required' => true],
                'content' => ['kind' => 'rich_html', 'max' => 150000, 'required' => false],
                'faq_items' => ['kind' => 'faq', 'max_items' => 20, 'required' => false],
            ],
            'media_slots' => ['avatar', 'content'],
            'notice' => 'Loại taxonomy này chưa có trạng thái draft; submit chỉ lưu chờ người quản trị xác nhận.',
        ];
    }
}
