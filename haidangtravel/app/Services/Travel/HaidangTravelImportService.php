<?php

namespace App\Services\Travel;

use App\Support\LandingPageBlocks;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Menu;
use Src\Domains\Cms\Models\MenuItem;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Slider;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Src\Domains\Cms\Models\TourDeparture;

class HaidangTravelImportService
{
    public const SNAPSHOT_PATH = 'database/seeders/Data/haidangtravel/core_snapshot.json';

    public function loadSnapshot(?string $path = null): array
    {
        $fullPath = $this->snapshotPath($path);

        if (! File::exists($fullPath)) {
            return [];
        }

        $decoded = json_decode((string) File::get($fullPath), true);

        return is_array($decoded) ? $decoded : [];
    }

    public function storeSnapshot(array $snapshot, ?string $path = null): string
    {
        $fullPath = $this->snapshotPath($path);
        File::ensureDirectoryExists(dirname($fullPath));
        File::put(
            $fullPath,
            json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
        );

        return $fullPath;
    }

    public function fetchSnapshotFromSource(): array
    {
        $snapshot = $this->loadSnapshot();

        if ($snapshot === []) {
            return [];
        }

        $response = Http::withoutVerifying()->timeout(20)->retry(2, 300)->get('https://haidangtravel.com');

        if (! $response->successful()) {
            return $snapshot;
        }

        $html = $response->body();
        $travelSnapshot = app(HaidangTravelSnapshotCrawler::class)->crawl(50);

        Arr::set($snapshot, 'site.phone', $this->firstMatch($html, '/1900[\\.\\s]?2011/u') ?: data_get($snapshot, 'site.phone'));
        Arr::set($snapshot, 'site.hotline', $this->firstMatch($html, '/0911\\s?2222\\s?88/u') ?: data_get($snapshot, 'site.hotline'));

        if (preg_match('/Tòa nhà Building Haidang[^<\\n]+/u', $html, $matches) === 1) {
            Arr::set($snapshot, 'site.address', trim(html_entity_decode($matches[0], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        }

        if ($travelSnapshot !== []) {
            Arr::set($snapshot, 'tour_categories', data_get($travelSnapshot, 'tour_categories', []));
            Arr::set($snapshot, 'regions', data_get($travelSnapshot, 'regions', []));
            Arr::set($snapshot, 'destinations', data_get($travelSnapshot, 'destinations', []));
            Arr::set($snapshot, 'tours', data_get($travelSnapshot, 'tours', []));
        }

        return $snapshot;
    }

    public function import(?array $snapshot = null, ?string $path = null): void
    {
        $snapshot ??= $this->loadSnapshot($path);

        if ($snapshot === []) {
            return;
        }

        $this->importSiteSettings((array) data_get($snapshot, 'site', []));
        $this->importMenus((array) data_get($snapshot, 'menus', []));
        $this->importCategories((array) data_get($snapshot, 'categories', []));
        $this->importTourCategories((array) data_get($snapshot, 'tour_categories', []));
        $this->importRegions((array) data_get($snapshot, 'regions', []));
        $this->importDestinations((array) data_get($snapshot, 'destinations', []));
        $this->importLandingPages((array) data_get($snapshot, 'landing_pages', []));
        $this->importServices((array) data_get($snapshot, 'services', []));
        $this->importTours((array) data_get($snapshot, 'tours', []));
        $this->importBlogPosts((array) data_get($snapshot, 'blog_posts', []));
        $this->syncDefaultLandingBlocks();
    }

    protected function importSiteSettings(array $payload): void
    {
        if ($payload === []) {
            return;
        }

        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            $payload + [
                'active_theme' => 'haidangtravel',
                'seo_robots' => 'index,follow',
            ],
        );
    }

    protected function importMenus(array $menus): void
    {
        foreach ($menus as $location => $items) {
            $menu = Menu::query()->updateOrCreate(
                ['location' => $location],
                ['name' => Str::headline((string) $location).' Menu']
            );

            MenuItem::query()->where('menu_id', $menu->getKey())->delete();

            foreach ((array) $items as $index => $item) {
                MenuItem::query()->create([
                    'menu_id' => $menu->getKey(),
                    'label' => (string) data_get($item, 'label'),
                    'url' => (string) data_get($item, 'url', '#'),
                    'target' => '_self',
                    'icon' => data_get($item, 'icon'),
                    'is_active' => true,
                    'order' => $index + 1,
                ]);
            }
        }
    }

    protected function importCategories(array $categories): void
    {
        foreach ($categories as $index => $category) {
            $values = [
                'name' => (string) data_get($category, 'name'),
                'description' => data_get($category, 'description'),
                'faq_items' => data_get($category, 'faq_items', []),
                'sort_order' => (int) data_get($category, 'sort_order', $index + 1),
            ];

            if (array_key_exists('content', $category)) {
                $values['content'] = data_get($category, 'content');
            }

            ContentCategory::query()->updateOrCreate(
                [
                    'taxonomy' => (string) data_get($category, 'taxonomy'),
                    'slug' => (string) data_get($category, 'slug'),
                ],
                $values,
            );
        }
    }

    protected function importDestinations(array $destinations): void
    {
        foreach ($destinations as $index => $destination) {
            Destination::query()->updateOrCreate(
                ['slug' => (string) data_get($destination, 'slug')],
                [
                    'region_id' => $this->regionIdBySlug((string) data_get($destination, 'region_slug')),
                    'name' => (string) data_get($destination, 'name'),
                    'scope' => $this->normalizeScope(
                        data_get($destination, 'scope'),
                        (string) data_get($destination, 'slug'),
                        (string) data_get($destination, 'name'),
                        (string) data_get($destination, 'region_slug'),
                    ),
                    'excerpt' => data_get($destination, 'excerpt'),
                    'content' => data_get($destination, 'content'),
                    'status' => (string) data_get($destination, 'status', 'published'),
                    'is_featured' => (bool) data_get($destination, 'is_featured', false),
                    'sort_order' => (int) data_get($destination, 'sort_order', $index),
                    'published_at' => data_get($destination, 'published_at', now()),
                    'cover_alt' => data_get($destination, 'cover_alt'),
                    'cover_image_url' => data_get($destination, 'cover_image_url'),
                    'meta_title' => data_get($destination, 'meta_title'),
                    'meta_description' => data_get($destination, 'meta_description'),
                    'og_title' => data_get($destination, 'og_title'),
                    'og_description' => data_get($destination, 'og_description'),
                    'canonical_url' => data_get($destination, 'canonical_url'),
                    'robots_directive' => data_get($destination, 'robots_directive', 'index,follow'),
                    'schema' => data_get($destination, 'schema'),
                    'gallery' => data_get($destination, 'gallery', []),
                    'faq_items' => data_get($destination, 'faq_items', []),
                ],
            );
        }
    }

    protected function importRegions(array $regions): void
    {
        foreach ($regions as $index => $region) {
            Region::query()->updateOrCreate(
                ['slug' => (string) data_get($region, 'slug')],
                [
                    'name' => (string) data_get($region, 'name'),
                    'scope' => $this->normalizeScope(
                        data_get($region, 'scope'),
                        (string) data_get($region, 'slug'),
                        (string) data_get($region, 'name'),
                    ),
                    'excerpt' => data_get($region, 'excerpt'),
                    'content' => data_get($region, 'content'),
                    'status' => (string) data_get($region, 'status', 'published'),
                    'is_featured' => (bool) data_get($region, 'is_featured', false),
                    'sort_order' => (int) data_get($region, 'sort_order', $index),
                    'published_at' => data_get($region, 'published_at', now()),
                    'cover_alt' => data_get($region, 'cover_alt'),
                    'cover_image_url' => data_get($region, 'cover_image_url'),
                    'meta_title' => data_get($region, 'meta_title'),
                    'meta_description' => data_get($region, 'meta_description'),
                    'og_title' => data_get($region, 'og_title'),
                    'og_description' => data_get($region, 'og_description'),
                    'canonical_url' => data_get($region, 'canonical_url'),
                    'robots_directive' => data_get($region, 'robots_directive', 'index,follow'),
                    'schema' => data_get($region, 'schema'),
                    'gallery' => data_get($region, 'gallery', []),
                ],
            );
        }
    }

    protected function importTourCategories(array $categories): void
    {
        foreach ($categories as $index => $category) {
            TourCategory::query()->updateOrCreate(
                ['slug' => (string) data_get($category, 'slug')],
                [
                    'name' => (string) data_get($category, 'name'),
                    'scope' => $this->normalizeScope(
                        data_get($category, 'scope'),
                        (string) data_get($category, 'slug'),
                        (string) data_get($category, 'name'),
                    ),
                    'excerpt' => data_get($category, 'excerpt'),
                    'content' => data_get($category, 'content'),
                    'status' => (string) data_get($category, 'status', 'published'),
                    'is_featured' => (bool) data_get($category, 'is_featured', false),
                    'sort_order' => (int) data_get($category, 'sort_order', $index),
                    'published_at' => data_get($category, 'published_at', now()),
                    'cover_alt' => data_get($category, 'cover_alt'),
                    'cover_image_url' => data_get($category, 'cover_image_url'),
                    'meta_title' => data_get($category, 'meta_title'),
                    'meta_description' => data_get($category, 'meta_description'),
                    'og_title' => data_get($category, 'og_title'),
                    'og_description' => data_get($category, 'og_description'),
                    'canonical_url' => data_get($category, 'canonical_url'),
                    'robots_directive' => data_get($category, 'robots_directive', 'index,follow'),
                    'schema' => data_get($category, 'schema'),
                    'gallery' => data_get($category, 'gallery', []),
                    'faq_items' => data_get($category, 'faq_items', []),
                ],
            );
        }
    }

    protected function importLandingPages(array $pages): void
    {
        foreach ($pages as $page) {
            LandingPage::query()->updateOrCreate(
                ['page_key' => (string) data_get($page, 'page_key')],
                Arr::except($page, ['page_key']),
            );
        }
    }

    protected function syncDefaultLandingBlocks(): void
    {
        $homePage = LandingPage::query()->where('page_key', 'home')->first();

        if (! $homePage) {
            return;
        }

        $blocks = LandingPageBlocks::normalize($homePage->blocks ?? []);
        $galleryItems = $this->buildHomeGalleryItems();

        if ($galleryItems === []) {
            return;
        }

        if ($blocks === []) {
            $homePage->forceFill([
                'template_key' => $homePage->template_key ?: 'home',
                'blocks' => $this->buildHomeLandingBlocks($homePage, $galleryItems),
            ])->save();

            return;
        }

        $galleryIndex = collect($blocks)->search(fn (array $block): bool => in_array($block['type'] ?? null, [
            LandingPageBlocks::TYPE_GALLERY_MEDIA,
            LandingPageBlocks::TYPE_GALLERY_SLIDER,
        ], true));

        if ($galleryIndex !== false) {
            $galleryBlock = $blocks[$galleryIndex];

            if (($galleryBlock['type'] ?? null) === LandingPageBlocks::TYPE_GALLERY_MEDIA && empty($galleryBlock['items'])) {
                $blocks[$galleryIndex] = $this->makeHomeGalleryBlock($galleryItems);
                $homePage->forceFill(['blocks' => $blocks])->save();
            }

            return;
        }

        $insertAt = collect($blocks)->search(fn (array $block): bool => ($block['type'] ?? null) === LandingPageBlocks::TYPE_TOUR_LIST);

        if ($insertAt === false) {
            $insertAt = collect($blocks)->search(fn (array $block): bool => ($block['type'] ?? null) === LandingPageBlocks::TYPE_RICH_TEXT);
        }

        $insertPosition = is_int($insertAt) ? $insertAt + 1 : count($blocks);
        array_splice($blocks, $insertPosition, 0, [$this->makeHomeGalleryBlock($galleryItems)]);

        $homePage->forceFill([
            'template_key' => $homePage->template_key ?: 'home',
            'blocks' => $blocks,
        ])->save();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildHomeLandingBlocks(LandingPage $homePage, array $galleryItems): array
    {
        $heroBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HERO_MEDIA);
        $heroBlock['eyebrow'] = (string) ($homePage->hero_badge ?? '');
        $heroBlock['title'] = (string) ($homePage->hero_title ?? $homePage->title);
        $heroBlock['description'] = (string) ($homePage->hero_excerpt ?? '');
        $heroBlock['primary_label'] = (string) ($homePage->cta_primary_label ?? '');
        $heroBlock['primary_url'] = (string) ($homePage->cta_primary_url ?? '');
        $heroBlock['secondary_label'] = (string) ($homePage->cta_secondary_label ?? '');
        $heroBlock['secondary_url'] = (string) ($homePage->cta_secondary_url ?? '');

        $introBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);
        $introBlock['title'] = (string) ($homePage->intro_title ?? '');
        $introBlock['excerpt'] = (string) ($homePage->intro_excerpt ?? '');
        $introBlock['body'] = (string) ($homePage->body ?? '');

        $galleryBlock = $this->makeHomeGalleryBlock($galleryItems);

        $blocks = [$heroBlock, $introBlock];

        if ($galleryItems !== []) {
            $blocks[] = $galleryBlock;
        }

        if (($homePage->faq_items ?? []) !== []) {
            $faqBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_FAQ);
            $faqBlock['title'] = 'Câu hỏi thường gặp';
            $faqBlock['items'] = $homePage->faq_items;
            $blocks[] = $faqBlock;
        }

        if (
            filled($homePage->cta_title)
            || filled($homePage->cta_excerpt)
            || filled($homePage->cta_primary_label)
            || filled($homePage->cta_secondary_label)
        ) {
            $ctaBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_CTA);
            $ctaBlock['title'] = (string) ($homePage->cta_title ?? '');
            $ctaBlock['description'] = (string) ($homePage->cta_excerpt ?? '');
            $ctaBlock['primary_label'] = (string) ($homePage->cta_primary_label ?? '');
            $ctaBlock['primary_url'] = (string) ($homePage->cta_primary_url ?? '');
            $ctaBlock['secondary_label'] = (string) ($homePage->cta_secondary_label ?? '');
            $ctaBlock['secondary_url'] = (string) ($homePage->cta_secondary_url ?? '');
            $blocks[] = $ctaBlock;
        }

        return $blocks;
    }

    protected function makeHomeGalleryBlock(array $galleryItems): array
    {
        $galleryBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_GALLERY_MEDIA);
        $galleryBlock['title'] = 'Điểm đến yêu thích';
        $galleryBlock['description'] = '';
        $galleryBlock['variant'] = LandingPageBlocks::GALLERY_VARIANT_STANDARD;
        $galleryBlock['items'] = $galleryItems;

        return $galleryBlock;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildHomeGalleryItems(): array
    {
        $categories = TourCategory::query()
            ->published()
            ->with('media')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->keyBy('slug');

        $homeSlider = Slider::query()
            ->where('location', 'home-hero')
            ->where('is_active', true)
            ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('order')])
            ->first();

        $sliderItems = collect($homeSlider?->items ?? [])
            ->filter(fn ($item): bool => filled($item->getFirstMediaUrl('image')))
            ->keyBy(fn ($item): string => Str::slug((string) ($item->title ?: 'slide-'.$item->order)));

        $tabDefinitions = [
            [
                'label' => 'Trong nước',
                'items' => [
                    $this->galleryItemFromCategory($categories, 'tour-mien-bac', 'Tour Miền Bắc', '', LandingPageBlocks::GALLERY_TILE_FEATURE),
                    $this->galleryItemFromCategory($categories, 'tour-mien-trung', 'Tour Miền Trung', '', LandingPageBlocks::GALLERY_TILE_STANDARD),
                    $this->galleryItemFromCategory($categories, 'tour-mien-nam', 'Tour Miền Nam - Miền Tây', '', LandingPageBlocks::GALLERY_TILE_WIDE),
                    $this->galleryItemFromCategory($categories, 'tour-du-lich-30-4', 'Du lịch 30/4', '', LandingPageBlocks::GALLERY_TILE_STANDARD),
                ],
            ],
            [
                'label' => 'Châu Á',
                'items' => [
                    $this->galleryItemFromCategory($categories, 'tour-dong-nam-a', 'Tour Đông Nam Á', '', LandingPageBlocks::GALLERY_TILE_FEATURE),
                    $this->galleryItemFromCategory($categories, 'tour-han-nhat-dai', 'Tour Hàn Nhật Đài', '', LandingPageBlocks::GALLERY_TILE_STANDARD),
                    $this->galleryItemFromCategory($categories, 'tour-trung-quoc', 'Tour Trung Quốc', '', LandingPageBlocks::GALLERY_TILE_STANDARD),
                    $this->galleryItemFromCategory($categories, 'chum-tour-an-do', 'Tour Ấn Độ - Himalaya', '', LandingPageBlocks::GALLERY_TILE_TALL),
                ],
            ],
            [
                'label' => 'Đường xa',
                'items' => [
                    $this->galleryItemFromCategory($categories, 'tour-my-chau-au-canada', 'Mỹ - Châu Âu - Canada', '', LandingPageBlocks::GALLERY_TILE_WIDE),
                    $this->galleryItemFromCategory($categories, 'tour-uc-newzeland', 'Úc - New Zealand', '', LandingPageBlocks::GALLERY_TILE_STANDARD),
                    $this->galleryItemFromSlider($sliderItems, 'tour-du-lich-nuoc-ngoai-gia-re', 'Tour quốc tế', 'Khám phá thêm', '/tour-nuoc-ngoai', LandingPageBlocks::GALLERY_TILE_FEATURE),
                    $this->galleryItemFromSlider($sliderItems, 'tour-du-lich-doan', 'Tour đoàn', 'Theo yêu cầu', '/tour-doan', LandingPageBlocks::GALLERY_TILE_STANDARD),
                ],
            ],
        ];

        return collect($tabDefinitions)
            ->flatMap(function (array $definition): array {
                return collect($definition['items'])
                    ->filter()
                    ->map(function (array $item) use ($definition): array {
                        $item['uuid'] = (string) Str::uuid();
                        $item['tab_label'] = $definition['label'];

                        return $item;
                    })
                    ->values()
                    ->all();
            })
            ->values()
            ->all();
    }

    protected function galleryItemFromCategory($categories, string $slug, string $title, string $subtitle, string $tileSize): ?array
    {
        $category = $categories->get($slug);
        $imageUrl = $category?->getFirstMediaUrl('avatar');

        if (! $category || ! filled($imageUrl)) {
            return null;
        }

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'description' => '',
            'image_alt' => (string) ($category->name ?: $title),
            'image_url' => $imageUrl,
            'tile_size' => $tileSize,
            'url' => '/danh-muc-tour/'.$category->slug,
        ];
    }

    protected function galleryItemFromSlider($sliderItems, string $key, string $title, string $subtitle, string $url, string $tileSize): ?array
    {
        $item = $sliderItems->get($key);
        $imageUrl = $item?->getFirstMediaUrl('image');

        if (! $item || ! filled($imageUrl)) {
            return null;
        }

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'description' => '',
            'image_alt' => (string) ($item->image_alt ?: $title),
            'image_url' => $imageUrl,
            'tile_size' => $tileSize,
            'url' => $url,
        ];
    }

    protected function importServices(array $services): void
    {
        foreach ($services as $service) {
            $categoryId = $this->categoryId(
                'service',
                (string) data_get($service, 'category_slug'),
            );

            Service::query()->updateOrCreate(
                ['slug' => (string) data_get($service, 'slug')],
                [
                    'title' => (string) data_get($service, 'title'),
                    'excerpt' => data_get($service, 'excerpt'),
                    'content' => data_get($service, 'content'),
                    'status' => (string) data_get($service, 'status', 'published'),
                    'content_category_id' => $categoryId,
                    'icon_class' => data_get($service, 'icon_class'),
                    'price_note' => data_get($service, 'price_note'),
                    'is_featured' => (bool) data_get($service, 'is_featured', false),
                    'cover_alt' => data_get($service, 'cover_alt'),
                    'cover_image_url' => data_get($service, 'cover_image_url'),
                    'meta_title' => data_get($service, 'meta_title'),
                    'meta_description' => data_get($service, 'meta_description'),
                    'og_title' => data_get($service, 'og_title'),
                    'og_description' => data_get($service, 'og_description'),
                    'canonical_url' => data_get($service, 'canonical_url'),
                    'robots_directive' => data_get($service, 'robots_directive', 'index,follow'),
                    'schema' => data_get($service, 'schema'),
                    'detail_config' => data_get($service, 'detail_config', []),
                    'faq_items' => data_get($service, 'faq_items', []),
                    'gallery' => data_get($service, 'gallery', []),
                    'related_questions' => data_get($service, 'related_questions', []),
                ],
            );
        }
    }

    protected function importTours(array $tours): void
    {
        foreach ($tours as $tour) {
            $tourCategorySlugs = $this->taxonomySlugList(data_get($tour, 'tour_category_slugs', []), (string) data_get($tour, 'tour_category_slug'));
            $destinationSlugs = $this->taxonomySlugList(data_get($tour, 'destination_slugs', []), (string) data_get($tour, 'destination_slug'));
            $regionSlugs = $this->taxonomySlugList(data_get($tour, 'region_slugs', []), (string) data_get($tour, 'region_slug'));
            $primaryTourCategorySlug = $tourCategorySlugs[0] ?? null;
            $primaryDestinationSlug = $destinationSlugs[0] ?? null;
            $primaryRegionSlug = $regionSlugs[0] ?? null;
            $tourModel = Tour::query()->updateOrCreate(
                ['slug' => (string) data_get($tour, 'slug')],
                [
                    'title' => (string) data_get($tour, 'title'),
                    'excerpt' => data_get($tour, 'excerpt'),
                    'content' => data_get($tour, 'content'),
                    'status' => (string) data_get($tour, 'status', 'published'),
                    'scope' => $this->normalizeScope(
                        data_get($tour, 'scope'),
                        (string) data_get($tour, 'slug'),
                        (string) data_get($tour, 'title'),
                        $primaryDestinationSlug,
                        $primaryRegionSlug,
                        $primaryTourCategorySlug,
                    ),
                    'tour_category_id' => $this->tourCategoryIdBySlug((string) $primaryTourCategorySlug),
                    'destination_id' => $this->destinationIdBySlug((string) $primaryDestinationSlug),
                    'region_id' => $this->regionIdBySlug((string) $primaryRegionSlug),
                    'destination_category_id' => $this->categoryId('tour_destination', (string) $primaryDestinationSlug),
                    'region_category_id' => $this->categoryId('tour_region', (string) $primaryRegionSlug),
                    'transport' => data_get($tour, 'transport'),
                    'departure_location' => data_get($tour, 'departure_location'),
                    'duration_days' => data_get($tour, 'duration_days'),
                    'duration_nights' => data_get($tour, 'duration_nights'),
                    'standard_label' => data_get($tour, 'standard_label'),
                    'base_price' => data_get($tour, 'base_price'),
                    'sale_price' => data_get($tour, 'sale_price'),
                    'rating_average' => data_get($tour, 'rating_average'),
                    'rating_count' => data_get($tour, 'rating_count'),
                    'cta_mode' => data_get($tour, 'cta_mode', 'book'),
                    'is_featured' => (bool) data_get($tour, 'is_featured', false),
                    'sort_order' => (int) data_get($tour, 'sort_order', 0),
                    'published_at' => data_get($tour, 'published_at', now()),
                    'cover_alt' => data_get($tour, 'cover_alt'),
                    'cover_image_url' => data_get($tour, 'cover_image_url'),
                    'meta_title' => data_get($tour, 'meta_title'),
                    'meta_description' => data_get($tour, 'meta_description'),
                    'og_title' => data_get($tour, 'og_title'),
                    'og_description' => data_get($tour, 'og_description'),
                    'canonical_url' => data_get($tour, 'canonical_url'),
                    'robots_directive' => data_get($tour, 'robots_directive', 'index,follow'),
                    'schema' => data_get($tour, 'schema'),
                    'itinerary' => data_get($tour, 'itinerary', []),
                    'pricing_table' => data_get($tour, 'pricing_table', []),
                    'departure_schedules' => data_get($tour, 'departure_schedules', []),
                    'inclusions' => data_get($tour, 'inclusions', []),
                    'faq_items' => data_get($tour, 'faq_items', []),
                    'gallery' => data_get($tour, 'gallery', []),
                ],
            );

            $tourModel->syncTaxonomyLinks(
                $this->tourCategoryIdsBySlug($tourCategorySlugs),
                $this->destinationIdsBySlug($destinationSlugs),
                $this->regionIdsBySlug($regionSlugs),
            );

            foreach ((array) data_get($tour, 'departures', []) as $index => $departure) {
                $signature = [
                    'tour_id' => $tourModel->getKey(),
                    'departure_date' => data_get($departure, 'departure_date'),
                    'departure_location' => data_get($departure, 'departure_location'),
                    'transport_label' => data_get($departure, 'transport_label'),
                ];

                if (blank($signature['departure_date']) && blank($signature['departure_location'])) {
                    continue;
                }

                TourDeparture::query()->updateOrCreate(
                    $signature,
                    [
                        'return_date' => data_get($departure, 'return_date'),
                        'standard_label' => data_get($departure, 'standard_label'),
                        'base_price' => data_get($departure, 'base_price'),
                        'sale_price' => data_get($departure, 'sale_price'),
                        'available_slots' => data_get($departure, 'available_slots'),
                        'pricing_note' => data_get($departure, 'pricing_note'),
                        'status' => data_get($departure, 'status', 'published'),
                        'is_featured' => (bool) data_get($departure, 'is_featured', false),
                        'sort_order' => (int) data_get($departure, 'sort_order', $index),
                    ],
                );
            }
        }
    }

    protected function importBlogPosts(array $posts): void
    {
        foreach ($posts as $post) {
            BlogPost::query()->updateOrCreate(
                ['slug' => (string) data_get($post, 'slug')],
                [
                    'title' => (string) data_get($post, 'title'),
                    'excerpt' => data_get($post, 'excerpt'),
                    'content' => data_get($post, 'content'),
                    'status' => (string) data_get($post, 'status', 'published'),
                    'content_category_id' => $this->categoryId('blog', (string) data_get($post, 'category_slug')),
                    'author_name' => data_get($post, 'author_name', 'Haidang Travel'),
                    'published_at' => data_get($post, 'published_at', now()),
                    'is_featured' => (bool) data_get($post, 'is_featured', false),
                    'sort_order' => (int) data_get($post, 'sort_order', 0),
                    'cover_alt' => data_get($post, 'cover_alt'),
                    'cover_image_url' => data_get($post, 'cover_image_url'),
                    'meta_title' => data_get($post, 'meta_title'),
                    'meta_description' => data_get($post, 'meta_description'),
                    'og_title' => data_get($post, 'og_title'),
                    'og_description' => data_get($post, 'og_description'),
                    'canonical_url' => data_get($post, 'canonical_url'),
                    'robots_directive' => data_get($post, 'robots_directive', 'index,follow'),
                    'schema' => data_get($post, 'schema'),
                    'faq_items' => data_get($post, 'faq_items', []),
                    'reading_time_minutes' => data_get($post, 'reading_time_minutes', 6),
                ],
            );
        }
    }

    protected function categoryId(string $taxonomy, string $slug): ?int
    {
        if ($slug === '') {
            return null;
        }

        return ContentCategory::query()
            ->where('taxonomy', $taxonomy)
            ->where('slug', $slug)
            ->value('id');
    }

    protected function destinationIdBySlug(string $slug): ?int
    {
        return $slug !== ''
            ? Destination::query()->where('slug', $slug)->value('id')
            : null;
    }

    protected function regionIdBySlug(string $slug): ?int
    {
        return $slug !== ''
            ? Region::query()->where('slug', $slug)->value('id')
            : null;
    }

    protected function tourCategoryIdBySlug(string $slug): ?int
    {
        return $slug !== ''
            ? TourCategory::query()->where('slug', $slug)->value('id')
            : null;
    }

    protected function destinationIdsBySlug(array $slugs): array
    {
        return $this->taxonomyIdsBySlug($slugs, Destination::query());
    }

    protected function normalizeScope(mixed $scope, string ...$signals): ?string
    {
        $normalized = TourScope::tryFrom((string) $scope)?->value;
        $haystack = Str::of(implode(' ', array_filter($signals)))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();

        if ($haystack !== '') {
            if (str_contains($haystack, 'doan')) {
                return TourScope::Group->value;
            }

            foreach (['chau a', 'chau au', 'chau my', 'chau uc', 'quoc te', 'international', 'singapore', 'malaysia', 'thai lan', 'thailand', 'han quoc', 'nhat ban', 'trung quoc', 'an do', 'uc', 'canada', 'my'] as $keyword) {
                if (str_contains($haystack, $keyword)) {
                    return TourScope::International->value;
                }
            }

            foreach (['mien bac', 'mien trung', 'mien nam', 'tay nguyen', 'viet nam', 'ha noi', 'da nang', 'phu quoc', 'da lat', 'nha trang'] as $keyword) {
                if (str_contains($haystack, $keyword)) {
                    return TourScope::Domestic->value;
                }
            }
        }

        return $normalized;
    }

    protected function regionIdsBySlug(array $slugs): array
    {
        return $this->taxonomyIdsBySlug($slugs, Region::query());
    }

    protected function snapshotPath(?string $path = null): string
    {
        return base_path($path ?: self::SNAPSHOT_PATH);
    }

    protected function firstMatch(string $subject, string $pattern): ?string
    {
        if (preg_match($pattern, $subject, $matches) !== 1) {
            return null;
        }

        return trim((string) ($matches[0] ?? ''));
    }

    protected function taxonomyIdsBySlug(array $slugs, $query): array
    {
        $slugs = collect($slugs)->filter()->unique()->values()->all();

        if ($slugs === []) {
            return [];
        }

        return $query
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function taxonomySlugList(mixed $pluralSlugs, ?string $singularSlug = null): array
    {
        return collect(is_array($pluralSlugs) ? $pluralSlugs : [$pluralSlugs])
            ->prepend($singularSlug)
            ->filter(fn ($slug) => filled($slug))
            ->map(fn ($slug) => trim((string) $slug))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function tourCategoryIdsBySlug(array $slugs): array
    {
        return $this->taxonomyIdsBySlug($slugs, TourCategory::query());
    }
}
