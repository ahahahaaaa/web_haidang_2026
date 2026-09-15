<?php

namespace App\Services\SeoOptimization;

use App\Services\Cms\BlogPostManager;
use App\Support\ContentGallery;
use App\Support\FaqContent;
use App\Support\LandingPageBlocks;
use App\Support\RichText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;

class ContentCreationWriter
{
    public function __construct(
        private readonly ContentCreationRegistry $registry,
        private readonly BlogPostManager $blogPosts,
    ) {}

    /** @return array<string, mixed> */
    public function prepare(string $type, array $payload): array
    {
        $contract = $this->registry->get($type);
        $fields = $contract['fields'];
        $allowed = array_keys($fields);

        Validator::make(['payload' => $payload], [
            'payload' => ['required', 'array:'.implode(',', $allowed)],
        ])->validate();

        $rules = [];
        foreach ($fields as $name => $definition) {
            $rules[$name] = $this->rulesFor($name, $definition);
        }
        $validated = Validator::make($payload, $rules)->validate();
        $clean = [];

        foreach ($fields as $name => $definition) {
            if (! array_key_exists($name, $validated)) {
                continue;
            }
            $clean[$name] = $this->clean($validated[$name], $definition);
        }

        $identity = (string) ($contract['identity_field'] ?? 'title');
        if (trim((string) ($clean[$identity] ?? '')) === '') {
            throw ValidationException::withMessages([$identity => 'Tên hoặc tiêu đề nội dung không được để trống.']);
        }

        $slug = Str::slug((string) ($clean['slug'] ?? $clean[$identity]));
        if ($type === 'country') {
            $slug = Destination::countryRootSlug($slug);
        }
        if ($slug === '' || mb_strlen($slug) > 180) {
            throw ValidationException::withMessages(['slug' => 'Slug không hợp lệ hoặc dài quá 180 ký tự.']);
        }
        $clean['slug'] = $slug;

        if ($type === 'landing') {
            $this->validateLanding($clean, $contract);
        }

        return $clean;
    }

    public function create(string $type, array $payload, mixed $actor = null): Model
    {
        $this->assertUniqueSlug($type, (string) $payload['slug']);

        return match ($type) {
            'blog_post' => $this->blogPosts->save([
                ...$payload,
                'status' => 'draft',
                'published_at' => null,
                'is_featured' => false,
                'sort_order' => 0,
                'robots_directive' => 'index,follow',
            ], null, $actor),
            'tour' => $this->createTour($payload),
            'service' => $this->createService($payload),
            'tour_category' => $this->createTourCategory($payload),
            'destination' => $this->createDestination($payload, false),
            'country' => $this->createDestination($payload, true),
            'region' => $this->createRegion($payload),
            'blog_category', 'service_category' => $this->createContentCategory($type, $payload),
            'landing' => $this->createLanding($payload),
            default => throw ValidationException::withMessages(['content_type' => 'Loại nội dung không có writer CMS.']),
        };
    }

    private function rulesFor(string $name, array $definition): array
    {
        $required = (bool) ($definition['required'] ?? false);
        $kind = (string) ($definition['kind'] ?? 'plain_text');
        $rules = [$required ? 'required' : 'nullable'];

        if (in_array($kind, ['plain_text', 'rich_html', 'manual_html', 'slug'], true)) {
            $rules[] = 'string';
            $rules[] = 'max:'.(int) ($definition['max'] ?? 100000);
        } elseif ($kind === 'integer') {
            array_push($rules, 'integer', 'min:'.(int) ($definition['min'] ?? 0), 'max:'.(int) ($definition['max'] ?? PHP_INT_MAX));
        } elseif ($kind === 'enum') {
            array_push($rules, 'string', Rule::in($definition['values'] ?? []));
        } elseif ($kind === 'relation') {
            array_push($rules, 'integer', $this->relationRule($name, (string) ($definition['target'] ?? '')));
        } elseif (in_array($kind, ['faq', 'itinerary', 'string_list', 'landing_blocks'], true)) {
            array_push($rules, 'array', 'max:'.(int) ($definition['max_items'] ?? 100));
        }

        return $rules;
    }

    private function relationRule(string $name, string $target): mixed
    {
        return match ($target) {
            'blog_category' => Rule::exists('content_categories', 'id')->where('taxonomy', 'blog'),
            'service_category' => Rule::exists('content_categories', 'id')->where('taxonomy', 'service'),
            'country' => Rule::exists('destinations', 'id')->where('is_country_root', true),
            'destination' => Rule::exists('destinations', 'id')->where(fn ($query) => $query->where('is_country_root', false)->orWhereNull('is_country_root')),
            'tour_category' => Rule::exists('tour_categories', 'id'),
            'region' => Rule::exists('regions', 'id'),
            default => throw ValidationException::withMessages([$name => 'Quan hệ CMS của field này chưa được hỗ trợ.']),
        };
    }

    private function clean(mixed $value, array $definition): mixed
    {
        if ($value === null) {
            return null;
        }
        $kind = (string) ($definition['kind'] ?? 'plain_text');

        return match ($kind) {
            'plain_text' => $this->nullablePlain($value),
            'slug' => Str::slug((string) $value),
            'rich_html' => $this->richHtml((string) $value),
            'manual_html' => $this->manualHtml((string) $value),
            'faq' => FaqContent::normalizeItems($value),
            'string_list' => collect($value)->map(fn ($item) => RichText::normalizePlain((string) $item))->filter()->unique()->values()->all(),
            'itinerary' => collect($value)->filter(fn ($item) => is_array($item))->map(fn (array $item) => [
                'title' => RichText::normalizePlain((string) ($item['title'] ?? '')),
                'content' => $this->richHtml((string) ($item['content'] ?? '')),
            ])->filter(fn (array $item) => $item['title'] !== '' && $item['content'] !== '')->values()->all(),
            'landing_blocks' => $this->landingBlocks($value),
            'integer', 'relation' => (int) $value,
            default => $value,
        };
    }

    private function richHtml(string $value): ?string
    {
        $this->assertSafeHtml($value);
        $this->assertSafeUrls($value);
        if (preg_match('/<\s*h1\b/iu', $value)) {
            throw ValidationException::withMessages(['content' => 'Nội dung chỉ dùng H2–H6; H1 lấy từ tiêu đề CMS.']);
        }
        if (preg_match('/<\s*img\b/iu', $value)) {
            throw ValidationException::withMessages(['content' => 'Ảnh trong nội dung phải dùng marker [[media:ref]] và media_placements.']);
        }

        $clean = RichText::sanitize($value);

        return $clean !== '' ? $clean : null;
    }

    private function manualHtml(string $value): ?string
    {
        $this->assertSafeHtml($value);
        $this->assertSafeUrls($value);
        if (preg_match('/<\s*h1\b/iu', $value)) {
            throw ValidationException::withMessages(['body' => 'HTML landing chỉ dùng H2–H6; H1 lấy từ title CMS.']);
        }
        if (preg_match('/<\s*img\b/iu', $value)) {
            throw ValidationException::withMessages(['body' => 'Ảnh landing HTML phải dùng marker [[media:ref]] và media_placements.']);
        }

        return trim($value) !== '' ? $value : null;
    }

    private function landingBlocks(array $blocks): array
    {
        foreach ($blocks as $block) {
            if (! is_array($block)) {
                throw ValidationException::withMessages(['blocks' => 'Mỗi landing block phải là object.']);
            }
            $encoded = json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $this->assertSafeHtml($encoded);
            $this->assertSafeUrls($encoded);
            if (str_contains(strtolower(json_encode($block)), '<img')) {
                throw ValidationException::withMessages(['blocks' => 'Ảnh landing block phải dùng media_placements.']);
            }
        }

        return LandingPageBlocks::normalize($blocks);
    }

    private function validateLanding(array $payload, array $contract): void
    {
        $mode = (string) ($payload['editor_mode'] ?? '');
        if ($mode === LandingPage::EDITOR_MODE_HTML && trim((string) ($payload['body'] ?? '')) === '') {
            throw ValidationException::withMessages(['body' => 'Landing HTML cần body.']);
        }
        if ($mode === LandingPage::EDITOR_MODE_BLOCKS && ($payload['blocks'] ?? []) === []) {
            throw ValidationException::withMessages(['blocks' => 'Landing block cần ít nhất một block.']);
        }
        if ($mode === LandingPage::EDITOR_MODE_HTML && ! empty($payload['blocks'])) {
            throw ValidationException::withMessages(['blocks' => 'Không gửi blocks khi editor_mode=html.']);
        }
        if ($mode === LandingPage::EDITOR_MODE_BLOCKS && filled($payload['body'] ?? null)) {
            throw ValidationException::withMessages(['body' => 'Không gửi body khi editor_mode=blocks.']);
        }
        $allowedTypes = $contract['allowed_block_types'] ?? [];
        foreach ($payload['blocks'] ?? [] as $block) {
            if (! in_array($block['type'] ?? null, $allowedTypes, true)) {
                throw ValidationException::withMessages(['blocks' => 'Landing chỉ cho phép block nội dung an toàn: '.implode(', ', $allowedTypes).'.']);
            }
        }
        if (in_array($payload['slug'], LandingPageBlocks::reservedSlugs(), true)) {
            throw ValidationException::withMessages(['slug' => 'Slug landing đang được route hệ thống sử dụng.']);
        }
    }

    private function assertSafeHtml(string $value): void
    {
        if (preg_match('/<\s*(script|iframe|object|embed|form|input|button|style|link|meta|base)\b|\bon[a-z]+\s*=|javascript\s*:|data\s*:\s*text\/html|display\s*:\s*none|visibility\s*:\s*hidden/iu', $value)) {
            throw ValidationException::withMessages(['content' => 'Nội dung có mã hoặc thuộc tính HTML không an toàn.']);
        }
    }

    private function assertSafeUrls(string $value): void
    {
        if (! preg_match_all('/(?:href|src)\s*=\s*["\']([^"\']+)/iu', $value, $matches)) {
            return;
        }
        foreach ($matches[1] as $url) {
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if (str_starts_with($url, '//') || ($scheme !== null && ! in_array(strtolower((string) $scheme), ['http', 'https'], true))) {
                throw ValidationException::withMessages(['content' => 'Liên kết phải dùng URL HTTP(S), đường dẫn nội bộ hoặc fragment.']);
            }
        }
    }

    private function nullablePlain(mixed $value): ?string
    {
        $clean = RichText::normalizePlain((string) $value);

        return $clean !== '' ? $clean : null;
    }

    private function assertUniqueSlug(string $type, string $slug): void
    {
        [$model, $query] = match ($type) {
            'blog_post' => [BlogPost::class, BlogPost::query()],
            'tour' => [Tour::class, Tour::query()],
            'service' => [Service::class, Service::query()],
            'tour_category' => [TourCategory::class, TourCategory::query()],
            'destination', 'country' => [Destination::class, Destination::query()],
            'region' => [Region::class, Region::query()],
            'blog_category' => [ContentCategory::class, ContentCategory::query()->forTaxonomy('blog')],
            'service_category' => [ContentCategory::class, ContentCategory::query()->forTaxonomy('service')],
            'landing' => [LandingPage::class, LandingPage::query()],
            default => [null, null],
        };
        if ($model && $query->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['slug' => 'Slug đã tồn tại trong loại nội dung '.$type.'.']);
        }
    }

    private function base(array $payload): array
    {
        return collect($payload)->except(['gallery', 'blocks', 'body'])->all();
    }

    private function createTour(array $payload): Tour
    {
        $tour = Tour::query()->create([
            ...$this->base($payload), 'status' => 'draft', 'published_at' => null,
            'is_featured' => false, 'sort_order' => 0, 'robots_directive' => 'index,follow',
            'gallery' => ContentGallery::normalize($payload['gallery'] ?? []),
        ]);
        $tour->syncTaxonomyLinks(
            filled($payload['tour_category_id'] ?? null) ? [(int) $payload['tour_category_id']] : [],
            filled($payload['destination_id'] ?? null) ? [(int) $payload['destination_id']] : [],
            filled($payload['region_id'] ?? null) ? [(int) $payload['region_id']] : [],
        );

        return $tour;
    }

    private function createService(array $payload): Service
    {
        return Service::query()->create([
            ...$this->base($payload), 'status' => 'draft', 'is_featured' => false,
            'robots_directive' => 'index,follow', 'gallery' => ContentGallery::normalize($payload['gallery'] ?? []),
        ]);
    }

    private function createTourCategory(array $payload): TourCategory
    {
        return TourCategory::query()->create([
            ...$this->base($payload), 'status' => 'draft', 'published_at' => null,
            'is_featured' => false, 'sort_order' => 0, 'robots_directive' => 'index,follow',
            'gallery' => ContentGallery::normalize($payload['gallery'] ?? []),
        ]);
    }

    private function createDestination(array $payload, bool $country): Destination
    {
        return Destination::query()->create([
            ...$this->base($payload), 'status' => 'draft', 'published_at' => null,
            'is_featured' => false, 'sort_order' => 0, 'robots_directive' => 'index,follow',
            'gallery' => ContentGallery::normalize($payload['gallery'] ?? []),
            'is_country_root' => $country, 'country_id' => $country ? null : $payload['country_id'],
            'region_id' => $country ? null : ($payload['region_id'] ?? null),
            'show_tours_on_page' => true, 'show_blogs_on_page' => false,
        ]);
    }

    private function createRegion(array $payload): Region
    {
        return Region::query()->create([
            ...$this->base($payload), 'status' => 'draft', 'published_at' => null,
            'is_featured' => false, 'sort_order' => 0, 'robots_directive' => 'index,follow',
            'gallery' => ContentGallery::normalize($payload['gallery'] ?? []),
        ]);
    }

    private function createContentCategory(string $type, array $payload): ContentCategory
    {
        return ContentCategory::query()->create([
            ...$this->base($payload), 'taxonomy' => $type === 'blog_category' ? 'blog' : 'service',
            'is_default' => false, 'sort_order' => 0,
        ]);
    }

    private function createLanding(array $payload): LandingPage
    {
        $blocks = $payload['editor_mode'] === LandingPage::EDITOR_MODE_BLOCKS
            ? LandingPageBlocks::normalize($payload['blocks'] ?? [])
            : [];
        $legacyHero = LandingPageBlocks::legacyHero($blocks);
        $legacyContent = LandingPageBlocks::legacyContent($blocks);

        return LandingPage::query()->create([
            'page_key' => null, 'template_key' => $payload['template_key'], 'editor_mode' => $payload['editor_mode'],
            'title' => $payload['title'], 'slug' => $payload['slug'], 'is_active' => false,
            'hero_badge' => $legacyHero['hero_badge'], 'hero_title' => $legacyHero['hero_title'],
            'hero_excerpt' => RichText::normalizePlain((string) $legacyHero['hero_excerpt']),
            'intro_title' => $legacyContent['intro_title'],
            'intro_excerpt' => RichText::normalizePlain((string) $legacyContent['intro_excerpt']),
            'body' => $payload['editor_mode'] === LandingPage::EDITOR_MODE_HTML ? ($payload['body'] ?? null) : ($legacyContent['body'] ?? null),
            'cta_title' => $legacyContent['cta_title'], 'cta_excerpt' => RichText::normalizePlain((string) $legacyContent['cta_excerpt']),
            'cta_primary_label' => $legacyContent['cta_primary_label'], 'cta_primary_url' => $legacyContent['cta_primary_url'],
            'cta_secondary_label' => $legacyContent['cta_secondary_label'], 'cta_secondary_url' => $legacyContent['cta_secondary_url'],
            'meta_title' => $payload['meta_title'], 'meta_description' => $payload['meta_description'],
            'og_title' => $payload['og_title'] ?? null, 'og_description' => $payload['og_description'] ?? null,
            'robots_directive' => 'index,follow', 'faq_items' => LandingPageBlocks::legacyFaqItems($blocks),
            'blocks' => $blocks, 'visual_config' => LandingPageBlocks::legacyVisualConfig($blocks),
        ]);
    }
}
