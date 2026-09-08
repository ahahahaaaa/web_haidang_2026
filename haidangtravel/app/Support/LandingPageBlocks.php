<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LandingPageBlocks
{
    public const GALLERY_TILE_FEATURE = 'feature';

    public const GALLERY_TILE_STANDARD = 'standard';

    public const GALLERY_TILE_TALL = 'tall';

    public const GALLERY_TILE_WIDE = 'wide';

    public const GALLERY_VARIANT_STANDARD = 'standard';

    public const GALLERY_VARIANT_TABBED_MOSAIC = 'tabbed_mosaic';

    public const TYPE_BLOG_LIST = 'blog_list';

    public const TYPE_CTA = 'cta';

    public const TYPE_FAQ = 'faq';

    public const TYPE_GALLERY_MEDIA = 'gallery_media';

    public const TYPE_GALLERY_SLIDER = 'gallery_slider';

    public const TYPE_HERO_MEDIA = 'hero_media';

    public const TYPE_HERO_DEMO_LANDINGPAGE = 'hero_demo_landingpage';

    public const TYPE_HERO_SLIDER = 'hero_slider';

    public const TYPE_HTML_WIDGET = 'html_widget';

    public const TYPE_REGION_RAIL = 'region_rail';

    public const TYPE_REGION_TAXONOMY_TABS = 'region_taxonomy_tabs';

    public const TYPE_RICH_TEXT = 'rich_text';

    public const TYPE_TOPIC_RAIL = 'topic_rail';

    public const TYPE_TOUR_TAXONOMY_TABS = 'tour_taxonomy_tabs';

    public const TYPE_TRUST_PROOF = 'trust_proof';

    public const TYPE_TOUR_LIST = 'tour_list';

    public const REGION_TAXONOMY_TABS_DEFAULT_LIMIT = 8;

    public const REGION_TAXONOMY_TABS_DEFAULT_TAB_LIMIT = 8;

    public const TOUR_TAXONOMY_TABS_DEFAULT_LIMIT = 8;

    /**
     * @return array<string, string>
     */
    public static function templates(): array
    {
        return [
            'home' => 'Trang chủ',
            'about' => 'Về chúng tôi',
            'contact' => 'Liên hệ',
            'services' => 'Landing dịch vụ',
            'blog' => 'Landing blog',
            'domestic_tours' => 'Tour trong nước',
            'international_tours' => 'Tour nước ngoài',
            'group_tours' => 'Tour đoàn',
            'blank' => 'Landing rỗng',
            'generic' => 'Landing tổng quát',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function blockTypes(): array
    {
        return [
            self::TYPE_HERO_SLIDER => 'Hero từ slider',
            self::TYPE_HERO_MEDIA => 'Hero từ media',
            self::TYPE_HERO_DEMO_LANDINGPAGE => 'Hero Demo Landingpage',
            self::TYPE_GALLERY_SLIDER => 'Gallery từ slider',
            self::TYPE_GALLERY_MEDIA => 'Gallery từ media',
            self::TYPE_HTML_WIDGET => 'HTML widget',
            self::TYPE_RICH_TEXT => 'Rich text',
            self::TYPE_REGION_RAIL => 'Block Vùng / miền',
            self::TYPE_REGION_TAXONOMY_TABS => 'Tab vùng miền + card taxonomy',
            self::TYPE_TOPIC_RAIL => 'Block Chủ đề tour',
            self::TYPE_TOUR_TAXONOMY_TABS => 'Tab tour theo taxonomy',
            self::TYPE_TRUST_PROOF => 'Lý do chọn / trust proof',
            self::TYPE_CTA => 'CTA',
            self::TYPE_FAQ => 'FAQ',
            self::TYPE_TOUR_LIST => 'Danh sách tour',
            self::TYPE_BLOG_LIST => 'Danh sách blog',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function systemPages(): array
    {
        return [
            'home' => 'Trang chủ',
            'about' => 'Về chúng tôi',
            'contact' => 'Liên hệ',
            'services' => 'Dịch vụ',
            'blog' => 'Blog',
            'domestic_tours' => 'Tour trong nước',
            'international_tours' => 'Tour nước ngoài',
            'group_tours' => 'Tour đoàn',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function reservedSlugs(): array
    {
        return [
            've-chung-toi',
            'tour-trong-nuoc',
            'tour-nuoc-ngoai',
            'tour-doan',
            'chuong-trinh',
            'tour',
            'dich-vu',
            'blog',
            'lien-he',
            'danh-muc',
            'danh-muc-tour',
            'diem-den',
            'vung-mien',
            'quoc-gia',
            'admin',
            'dashboard',
            'settings',
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'verify-email',
            'confirm-password',
            'robots.txt',
            'sitemap.xml',
            'up',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function tourSortOptions(): array
    {
        return [
            'featured',
            'latest',
            'price_asc',
            'price_desc',
            'title_asc',
            'title_desc',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function blogSortOptions(): array
    {
        return [
            'latest',
            'oldest',
            'featured',
            'title_asc',
            'title_desc',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function galleryVariants(): array
    {
        return [
            self::GALLERY_VARIANT_STANDARD => 'Lưới thường',
            self::GALLERY_VARIANT_TABBED_MOSAIC => 'Gallery tab ảnh',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function galleryTileSizes(): array
    {
        return [
            self::GALLERY_TILE_STANDARD => 'Ô chuẩn',
            self::GALLERY_TILE_WIDE => 'Ô ngang',
            self::GALLERY_TILE_TALL => 'Ô dọc',
            self::GALLERY_TILE_FEATURE => 'Ô nổi bật',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function tourTaxonomyTabTypes(): array
    {
        return [
            'region' => 'Vùng miền',
            'destination' => 'Điểm đến',
            'tour_category' => 'Chủ đề',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function regionTaxonomyCardTypes(): array
    {
        return [
            'destination' => 'Điểm đến',
            'tour_category' => 'Chủ đề tour',
        ];
    }

    public static function defaultBlock(string $type): array
    {
        return match ($type) {
            self::TYPE_HERO_SLIDER => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'eyebrow' => '',
                'title' => '',
                'description' => '',
                'slider_id' => null,
                'primary_label' => '',
                'primary_url' => '',
                'secondary_label' => '',
                'secondary_url' => '',
            ],
            self::TYPE_HERO_MEDIA => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'eyebrow' => '',
                'title' => '',
                'description' => '',
                'media_alt' => '',
                'primary_label' => '',
                'primary_url' => '',
                'secondary_label' => '',
                'secondary_url' => '',
            ],
            self::TYPE_HERO_DEMO_LANDINGPAGE => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'title' => '',
                'description' => '',
                'media_alt' => '',
                'primary_label' => '',
                'secondary_label' => '',
                'secondary_url' => '',
                'panel_title' => 'Bắt đầu từ nhóm tour phù hợp nhất',
                'category_slug' => '',
                'destination_slug' => '',
                'region_slug' => '',
                'scope' => '',
                'featured' => true,
                'limit' => 3,
                'sort' => 'featured',
            ],
            self::TYPE_GALLERY_SLIDER => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'eyebrow' => '',
                'title' => '',
                'description' => '',
                'slider_id' => null,
            ],
            self::TYPE_GALLERY_MEDIA => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'eyebrow' => '',
                'title' => '',
                'description' => '',
                'variant' => self::GALLERY_VARIANT_STANDARD,
                'items' => [self::defaultGalleryItem()],
            ],
            self::TYPE_HTML_WIDGET => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'html' => '',
            ],
            self::TYPE_RICH_TEXT => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'eyebrow' => '',
                'title' => '',
                'excerpt' => '',
                'body' => '',
            ],
            self::TYPE_REGION_RAIL => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'title' => '',
                'description' => '',
                'card_cta_label' => '',
                'scope' => '',
                'featured' => false,
                'limit' => 8,
            ],
            self::TYPE_REGION_TAXONOMY_TABS => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'title' => 'Khám phá vùng miền theo điểm đến hoặc chủ đề',
                'description' => 'Chọn nhanh từng vùng miền để mở các điểm đến hoặc chủ đề tour đang có hành trình hoạt động, giúp bạn thu hẹp nhu cầu trước khi xem sâu hơn từng landing hub.',
                'cta_label' => 'Xem hub vùng miền',
                'card_cta_label' => '',
                'scope' => 'domestic',
                'featured' => false,
                'card_source_type' => 'destination',
                'limit' => self::REGION_TAXONOMY_TABS_DEFAULT_LIMIT,
                'tab_limit' => self::REGION_TAXONOMY_TABS_DEFAULT_TAB_LIMIT,
            ],
            self::TYPE_TOPIC_RAIL => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'eyebrow' => '',
                'title' => 'Chủ đề tour nổi bật',
                'description' => 'Lướt nhanh các chủ đề tour đang có hành trình hoạt động để khoanh vùng nhu cầu phù hợp trước khi so sánh điểm đến, ngày đi và mức giá.',
                'show_navigation' => true,
                'limit' => 8,
            ],
            self::TYPE_TOUR_TAXONOMY_TABS => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'title' => 'Khám phá tour theo vùng, điểm đến và chủ đề',
                'description' => 'Chuyển nhanh giữa các nhóm tab được chọn để xem danh sách tour live theo vùng miền, điểm đến hoặc chủ đề đang có hành trình hoạt động.',
                'cta_label' => 'Xem danh sách tour',
                'scope' => 'domestic',
                'featured' => false,
                'limit' => self::TOUR_TAXONOMY_TABS_DEFAULT_LIMIT,
                'sort' => 'featured',
                'tabs' => [
                    self::defaultTourTaxonomyTab('region'),
                    self::defaultTourTaxonomyTab('destination'),
                    self::defaultTourTaxonomyTab('tour_category'),
                ],
            ],
            self::TYPE_TRUST_PROOF => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'title' => 'Lý do khách chọn Hải Đăng Travel',
                'description' => 'Dùng cụm proof ngắn để làm rõ đầu mối xử lý, cách tư vấn và cảm giác an tâm trước khi khách gửi yêu cầu.',
                'cards' => [
                    self::defaultTrustProofCard(
                        'Một đầu mối xử lý',
                        'Một yêu cầu, đội ngũ theo tới cùng',
                        'Tour, visa, vé máy bay và nhu cầu tour đoàn được gom về cùng một luồng tiếp nhận gọn hơn.',
                        'fa-solid fa-route',
                    ),
                    self::defaultTrustProofCard(
                        'So sánh dễ hơn',
                        'Thông tin tour được bày theo logic chốt mua',
                        'Ngày đi, thời lượng, giá và CTA được ưu tiên hiện sớm để khách lọc nhanh phương án phù hợp.',
                        'fa-solid fa-calendar-check',
                    ),
                    self::defaultTrustProofCard(
                        'Đồng hành trước chuyến đi',
                        'Không dừng ở bước gửi báo giá',
                        'Đội ngũ tiếp tục hỗ trợ hồ sơ, dịch vụ kèm và những việc cần chuẩn bị trước ngày khởi hành.',
                        'fa-solid fa-shield-heart',
                    ),
                ],
                'stats' => [],
            ],
            self::TYPE_CTA => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'title' => '',
                'description' => '',
                'primary_label' => '',
                'primary_url' => '',
                'secondary_label' => '',
                'secondary_url' => '',
            ],
            self::TYPE_FAQ => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'title' => '',
                'description' => '',
                'items' => [FaqContent::blankItem()],
            ],
            self::TYPE_TOUR_LIST => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'eyebrow' => '',
                'title' => '',
                'description' => '',
                'category_slug' => '',
                'destination_slug' => '',
                'region_slug' => '',
                'scope' => '',
                'featured' => false,
                'limit' => FrontsiteCardGrid::DEFAULT_LIMIT,
                'sort' => 'featured',
            ],
            self::TYPE_BLOG_LIST => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'eyebrow' => '',
                'title' => '',
                'description' => '',
                'category_slug' => '',
                'featured' => false,
                'limit' => FrontsiteCardGrid::DEFAULT_LIMIT,
                'sort' => 'latest',
            ],
            default => [
                'uuid' => (string) Str::uuid(),
                'type' => self::TYPE_RICH_TEXT,
                'is_enabled' => true,
                'eyebrow' => '',
                'title' => '',
                'excerpt' => '',
                'body' => '',
            ],
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function presetBlocks(string $templateKey): array
    {
        return match ($templateKey) {
            'home' => [
                self::defaultBlock(self::TYPE_HERO_SLIDER),
                self::defaultBlock(self::TYPE_GALLERY_SLIDER),
                self::defaultBlock(self::TYPE_TOPIC_RAIL),
                self::defaultBlock(self::TYPE_TOUR_LIST),
                self::defaultBlock(self::TYPE_TOUR_TAXONOMY_TABS),
                self::defaultBlock(self::TYPE_REGION_TAXONOMY_TABS),
                self::defaultBlock(self::TYPE_BLOG_LIST),
                self::defaultBlock(self::TYPE_FAQ),
                self::defaultBlock(self::TYPE_CTA),
            ],
            'about' => [
                self::defaultBlock(self::TYPE_HERO_MEDIA),
                self::defaultBlock(self::TYPE_GALLERY_MEDIA),
                self::defaultBlock(self::TYPE_RICH_TEXT),
                self::defaultBlock(self::TYPE_CTA),
            ],
            'contact' => [
                self::defaultBlock(self::TYPE_HERO_MEDIA),
                self::defaultBlock(self::TYPE_RICH_TEXT),
                self::defaultBlock(self::TYPE_CTA),
            ],
            'services' => [
                self::defaultBlock(self::TYPE_HERO_MEDIA),
                self::defaultBlock(self::TYPE_GALLERY_SLIDER),
                self::defaultBlock(self::TYPE_RICH_TEXT),
                self::defaultBlock(self::TYPE_CTA),
            ],
            'blog' => [
                self::defaultBlock(self::TYPE_HERO_MEDIA),
                self::defaultBlock(self::TYPE_GALLERY_MEDIA),
                self::defaultBlock(self::TYPE_RICH_TEXT),
                self::defaultBlock(self::TYPE_BLOG_LIST),
                self::defaultBlock(self::TYPE_CTA),
            ],
            'domestic_tours', 'international_tours', 'group_tours' => [
                self::defaultBlock(self::TYPE_HERO_SLIDER),
                self::defaultBlock(self::TYPE_GALLERY_MEDIA),
                self::defaultBlock(self::TYPE_RICH_TEXT),
                self::defaultBlock(self::TYPE_TOUR_LIST),
                self::defaultBlock(self::TYPE_CTA),
            ],
            'blank' => [],
            default => [
                self::defaultBlock(self::TYPE_HERO_MEDIA),
                self::defaultBlock(self::TYPE_RICH_TEXT),
                self::defaultBlock(self::TYPE_CTA),
            ],
        };
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $blocks
     * @return array<int, array<string, mixed>>
     */
    public static function normalize(?array $blocks): array
    {
        return collect($blocks ?? [])
            ->filter(fn ($block) => is_array($block) && filled($block['type'] ?? null))
            ->map(function (array $block): array {
                $type = array_key_exists($block['type'], self::blockTypes())
                    ? (string) $block['type']
                    : self::TYPE_RICH_TEXT;
                $normalized = array_replace_recursive(self::defaultBlock($type), $block);
                $normalized['uuid'] = filled($normalized['uuid'] ?? null)
                    ? (string) $normalized['uuid']
                    : (string) Str::uuid();
                $normalized['type'] = $type;
                $normalized['is_enabled'] = (bool) ($normalized['is_enabled'] ?? true);

                if ($type === self::TYPE_GALLERY_MEDIA) {
                    $normalized['variant'] = array_key_exists((string) ($normalized['variant'] ?? ''), self::galleryVariants())
                        ? (string) $normalized['variant']
                        : self::GALLERY_VARIANT_STANDARD;
                    $normalized['items'] = self::normalizeGalleryItems($normalized['items'] ?? []);
                }

                if ($type === self::TYPE_FAQ) {
                    $normalized['items'] = FaqContent::prepareItems($normalized['items'] ?? [], [FaqContent::blankItem()]);
                }

                if ($type === self::TYPE_TRUST_PROOF) {
                    $normalized['cards'] = self::normalizeTrustProofCards(is_array($normalized['cards'] ?? null) ? $normalized['cards'] : []);
                    $normalized['stats'] = self::normalizeTrustProofStats(is_array($normalized['stats'] ?? null) ? $normalized['stats'] : []);
                }

                if ($type === self::TYPE_TOPIC_RAIL) {
                    $normalized['show_navigation'] = (bool) ($normalized['show_navigation'] ?? true);
                }

                if ($type === self::TYPE_REGION_TAXONOMY_TABS) {
                    $normalized['card_source_type'] = array_key_exists(
                        (string) ($normalized['card_source_type'] ?? ''),
                        self::regionTaxonomyCardTypes(),
                    )
                        ? (string) $normalized['card_source_type']
                        : 'destination';
                    $normalized['tab_limit'] = FrontsiteCardGrid::normalizeLimit(
                        $normalized['tab_limit'] ?? null,
                        self::REGION_TAXONOMY_TABS_DEFAULT_TAB_LIMIT,
                    );
                }

                if ($type === self::TYPE_TOUR_TAXONOMY_TABS) {
                    $normalized['tabs'] = self::normalizeTourTaxonomyTabs($normalized['tabs'] ?? []);
                }

                $normalized['featured'] = (bool) ($normalized['featured'] ?? false);
                $normalized['limit'] = FrontsiteCardGrid::normalizeLimit(
                    $normalized['limit'] ?? null,
                    match ($type) {
                        self::TYPE_HERO_DEMO_LANDINGPAGE => 3,
                        self::TYPE_REGION_RAIL, self::TYPE_TOPIC_RAIL => 8,
                        self::TYPE_REGION_TAXONOMY_TABS => self::REGION_TAXONOMY_TABS_DEFAULT_LIMIT,
                        self::TYPE_TOUR_TAXONOMY_TABS => self::TOUR_TAXONOMY_TABS_DEFAULT_LIMIT,
                        default => FrontsiteCardGrid::DEFAULT_LIMIT,
                    },
                );

                if (in_array($type, [self::TYPE_REGION_RAIL, self::TYPE_TOPIC_RAIL, self::TYPE_REGION_TAXONOMY_TABS], true)) {
                    $normalized['limit'] = min(8, (int) $normalized['limit']);
                }

                if ($type === self::TYPE_REGION_TAXONOMY_TABS) {
                    $normalized['tab_limit'] = min(8, (int) ($normalized['tab_limit'] ?? self::REGION_TAXONOMY_TABS_DEFAULT_TAB_LIMIT));
                }

                return $normalized;
            })
            ->values()
            ->all();
    }

    public static function mediaCollection(string $blockUuid): string
    {
        return 'landing-block-media-'.Str::slug($blockUuid);
    }

    public static function defaultTrustProofCard(string $highlight = '', string $title = '', string $text = '', string $icon = ''): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'icon' => trim($icon),
            'highlight' => trim($highlight),
            'title' => trim($title),
            'text' => trim($text),
        ];
    }

    public static function defaultTrustProofStat(string $value = '', string $label = '', string $icon = ''): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'icon' => trim($icon),
            'label' => trim($label),
            'value' => trim($value),
        ];
    }

    public static function galleryItemCollection(string $blockUuid, string $itemUuid): string
    {
        return 'landing-gallery-'.Str::slug($blockUuid).'-'.Str::slug($itemUuid);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeGalleryItems(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): array {
                return array_replace(self::defaultGalleryItem(), [
                    'uuid' => filled($item['uuid'] ?? null) ? (string) $item['uuid'] : (string) Str::uuid(),
                    'title' => (string) ($item['title'] ?? ''),
                    'subtitle' => (string) ($item['subtitle'] ?? ''),
                    'description' => (string) ($item['description'] ?? ''),
                    'image_url' => (string) ($item['image_url'] ?? ''),
                    'tab_label' => trim((string) ($item['tab_label'] ?? '')),
                    'tile_size' => array_key_exists((string) ($item['tile_size'] ?? ''), self::galleryTileSizes())
                        ? (string) $item['tile_size']
                        : self::GALLERY_TILE_STANDARD,
                    'url' => (string) ($item['url'] ?? ''),
                    'image_alt' => (string) ($item['image_alt'] ?? ''),
                ]);
            })
            ->whenEmpty(fn (Collection $collection) => $collection->push(self::defaultGalleryItem()))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function normalizeTrustProofCards(array $cards): array
    {
        return collect($cards)
            ->filter(fn ($card) => is_array($card))
            ->map(fn (array $card) => array_replace(self::defaultTrustProofCard(), [
                'uuid' => filled($card['uuid'] ?? null) ? (string) $card['uuid'] : (string) Str::uuid(),
                'icon' => trim((string) ($card['icon'] ?? '')),
                'highlight' => trim((string) ($card['highlight'] ?? '')),
                'title' => trim((string) ($card['title'] ?? '')),
                'text' => trim((string) ($card['text'] ?? '')),
            ]))
            ->whenEmpty(fn (Collection $collection) => $collection->push(self::defaultTrustProofCard()))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function normalizeTrustProofStats(array $stats): array
    {
        return collect($stats)
            ->filter(fn ($stat) => is_array($stat))
            ->map(fn (array $stat) => array_replace(self::defaultTrustProofStat(), [
                'uuid' => filled($stat['uuid'] ?? null) ? (string) $stat['uuid'] : (string) Str::uuid(),
                'icon' => trim((string) ($stat['icon'] ?? '')),
                'label' => trim((string) ($stat['label'] ?? '')),
                'value' => trim((string) ($stat['value'] ?? '')),
            ]))
            ->filter(fn (array $stat) => $stat['value'] !== '' && $stat['label'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function normalizeTourTaxonomyTabs(array $tabs): array
    {
        return collect($tabs)
            ->filter(fn ($tab) => is_array($tab))
            ->map(function (array $tab): array {
                $sourceType = (string) ($tab['source_type'] ?? 'region');

                return array_replace(self::defaultTourTaxonomyTab($sourceType), [
                    'uuid' => filled($tab['uuid'] ?? null) ? (string) $tab['uuid'] : (string) Str::uuid(),
                    'source_type' => array_key_exists($sourceType, self::tourTaxonomyTabTypes())
                        ? $sourceType
                        : 'region',
                    'source_slug' => trim((string) ($tab['source_slug'] ?? '')),
                    'label' => trim((string) ($tab['label'] ?? '')),
                    'title' => trim((string) ($tab['title'] ?? '')),
                    'description' => trim((string) ($tab['description'] ?? '')),
                ]);
            })
            ->whenEmpty(fn (Collection $collection) => $collection->push(self::defaultTourTaxonomyTab()))
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function legacyContent(array $blocks): array
    {
        $enabledBlocks = self::enabledBlocks($blocks);
        $richText = $enabledBlocks->firstWhere('type', self::TYPE_RICH_TEXT) ?? [];
        $cta = $enabledBlocks->firstWhere('type', self::TYPE_CTA) ?? [];

        return [
            'intro_title' => trim((string) ($richText['title'] ?? '')),
            'intro_excerpt' => trim((string) ($richText['excerpt'] ?? '')),
            'body' => (string) ($richText['body'] ?? ''),
            'cta_title' => trim((string) ($cta['title'] ?? '')),
            'cta_excerpt' => trim((string) ($cta['description'] ?? '')),
            'cta_primary_label' => trim((string) ($cta['primary_label'] ?? '')),
            'cta_primary_url' => trim((string) ($cta['primary_url'] ?? '')),
            'cta_secondary_label' => trim((string) ($cta['secondary_label'] ?? '')),
            'cta_secondary_url' => trim((string) ($cta['secondary_url'] ?? '')),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function legacyHero(array $blocks): array
    {
        $hero = self::enabledBlocks($blocks)
            ->first(fn (array $block) => in_array($block['type'] ?? null, [self::TYPE_HERO_SLIDER, self::TYPE_HERO_MEDIA, self::TYPE_HERO_DEMO_LANDINGPAGE], true)) ?? [];

        return [
            'hero_badge' => trim((string) ($hero['eyebrow'] ?? '')),
            'hero_title' => trim((string) ($hero['title'] ?? '')),
            'hero_excerpt' => trim((string) ($hero['description'] ?? '')),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function legacyFaqItems(array $blocks): array
    {
        $faq = self::enabledBlocks($blocks)->firstWhere('type', self::TYPE_FAQ) ?? [];

        return FaqContent::normalizeItems($faq['items'] ?? []);
    }

    /**
     * @return array<string, string>
     */
    public static function defaultGalleryItem(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'title' => '',
            'subtitle' => '',
            'description' => '',
            'image_url' => '',
            'tab_label' => '',
            'tile_size' => self::GALLERY_TILE_STANDARD,
            'url' => '',
            'image_alt' => '',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function defaultTourTaxonomyTab(
        string $sourceType = 'region',
        string $sourceSlug = '',
        string $label = '',
        string $title = '',
        string $description = '',
    ): array {
        $resolvedSourceType = array_key_exists($sourceType, self::tourTaxonomyTabTypes())
            ? $sourceType
            : 'region';

        return [
            'uuid' => (string) Str::uuid(),
            'source_type' => $resolvedSourceType,
            'source_slug' => trim($sourceSlug),
            'label' => trim($label),
            'title' => trim($title),
            'description' => trim($description),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected static function enabledBlocks(array $blocks): Collection
    {
        return collect($blocks)->filter(fn ($block) => is_array($block) && (bool) ($block['is_enabled'] ?? true));
    }
}
