<?php

namespace App\Support;

use Illuminate\Support\Str;
use Src\Domains\Cms\Enums\TourScope;

class TravelHomePageConfig
{
    public const FEATURED_TOUR_FILTER_DESTINATION = 'destination';

    public const FEATURED_TOUR_FILTER_LIMIT = 12;

    public const FEATURED_TOUR_FILTER_REGION = 'region';

    public const FEATURED_TOUR_FILTER_SCOPE = 'scope';

    public const FEATURED_TOUR_FILTER_TOPIC = 'tour_category';

    public const FEATURED_TOUR_POPULAR_SEARCH_LIMIT = 12;

    public const HOME_LAYOUT_BLOCK_PREFIX = 'block:';

    public const HOME_LAYOUT_SECTION_PREFIX = 'section:';

    /**
     * @return array<string, string>
     */
    public static function homeSectionOptions(): array
    {
        return [
            'search' => 'Thanh tìm kiếm',
            'geo_answer' => 'GEO / AI Search',
            'topic_rail' => 'Chủ đề tour',
            'featured_tours' => 'Tour nổi bật',
            'tour_taxonomy_tabs' => 'Tab tour theo taxonomy',
            'region_taxonomy_tabs' => 'Tab vùng miền',
            'destination_slider' => 'Điểm đến nổi bật',
            'gallery' => 'Gallery landing',
            'services' => 'Dịch vụ hỗ trợ',
            'trust' => 'Giới thiệu & giải thưởng',
            'process' => 'Quy trình tư vấn',
            'blog_preview' => 'Blog preview',
            'faq' => 'FAQ',
            'cta' => 'CTA cuối trang',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function featuredTourFilterTypes(): array
    {
        return [
            self::FEATURED_TOUR_FILTER_SCOPE => 'Loại tour',
            self::FEATURED_TOUR_FILTER_DESTINATION => 'Điểm đến',
            self::FEATURED_TOUR_FILTER_TOPIC => 'Chủ đề',
            self::FEATURED_TOUR_FILTER_REGION => 'Vùng miền / Châu',
        ];
    }

    public static function featuredTourFilter(
        string $sourceType = self::FEATURED_TOUR_FILTER_SCOPE,
        string $sourceValue = '',
        string $label = '',
        string $title = '',
        string $description = '',
        ?string $uuid = null,
    ): array {
        return [
            'uuid' => filled($uuid) ? (string) $uuid : (string) Str::uuid(),
            'source_type' => array_key_exists($sourceType, self::featuredTourFilterTypes())
                ? $sourceType
                : self::FEATURED_TOUR_FILTER_SCOPE,
            'source_value' => self::stringValue($sourceValue),
            'label' => self::stringValue($label),
            'title' => self::stringValue($title),
            'description' => self::stringValue($description),
        ];
    }

    public static function featuredTourPopularSearch(
        string $label = '',
        string $url = '',
        ?string $uuid = null,
        string $filterUuid = '',
    ): array {
        return [
            'uuid' => filled($uuid) ? (string) $uuid : (string) Str::uuid(),
            'label' => self::stringValue($label),
            'url' => self::stringValue($url),
            'filter_uuid' => self::stringValue($filterUuid),
        ];
    }

    public static function isSafeFeaturedTourPopularSearchUrl(mixed $url): bool
    {
        $url = self::stringValue($url);

        if ($url === '') {
            return false;
        }

        if (Str::startsWith($url, ['#', '?'])) {
            return true;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        return in_array(Str::lower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * @return array<string, string>
     */
    public static function homeSectionSlots(): array
    {
        return [
            'search' => LandingPageBlocks::HOME_POSITION_BEFORE_SEARCH,
            'geo_answer' => LandingPageBlocks::HOME_POSITION_BEFORE_GEO_ANSWER,
            'topic_rail' => LandingPageBlocks::HOME_POSITION_BEFORE_TOPIC_RAIL,
            'featured_tours' => LandingPageBlocks::HOME_POSITION_BEFORE_FEATURED_TOURS,
            'tour_taxonomy_tabs' => LandingPageBlocks::HOME_POSITION_BEFORE_TOUR_TAXONOMY_TABS,
            'region_taxonomy_tabs' => LandingPageBlocks::HOME_POSITION_BEFORE_REGION_TAXONOMY_TABS,
            'destination_slider' => LandingPageBlocks::HOME_POSITION_BEFORE_DESTINATION_SLIDER,
            'gallery' => LandingPageBlocks::HOME_POSITION_BEFORE_GALLERY,
            'services' => LandingPageBlocks::HOME_POSITION_BEFORE_SERVICES,
            'trust' => LandingPageBlocks::HOME_POSITION_BEFORE_TRUST,
            'process' => LandingPageBlocks::HOME_POSITION_BEFORE_PROCESS,
            'blog_preview' => LandingPageBlocks::HOME_POSITION_BEFORE_BLOG_PREVIEW,
            'faq' => LandingPageBlocks::HOME_POSITION_BEFORE_FAQ,
            'cta' => LandingPageBlocks::HOME_POSITION_BEFORE_CTA,
        ];
    }

    public static function homeSectionSlot(string $sectionKey): ?string
    {
        return self::homeSectionSlots()[$sectionKey] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public static function sectionOrder(array $config): array
    {
        return self::normalizeSectionOrder($config['section_order'] ?? []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, string>
     */
    public static function homeLayoutOrder(array $config, array $blocks = []): array
    {
        return self::normalizeHomeLayoutOrder(
            $config['layout_order'] ?? [],
            $config['section_order'] ?? [],
            $blocks,
        );
    }

    public static function homeLayoutTokenForSection(string $sectionKey): string
    {
        return self::HOME_LAYOUT_SECTION_PREFIX.$sectionKey;
    }

    public static function homeLayoutTokenForBlock(string $uuid): string
    {
        return self::HOME_LAYOUT_BLOCK_PREFIX.$uuid;
    }

    public static function homeLayoutSectionKey(string $token): ?string
    {
        if (! str_starts_with($token, self::HOME_LAYOUT_SECTION_PREFIX)) {
            return null;
        }

        $sectionKey = substr($token, strlen(self::HOME_LAYOUT_SECTION_PREFIX));

        return array_key_exists($sectionKey, self::homeSectionOptions()) ? $sectionKey : null;
    }

    public static function homeLayoutBlockUuid(string $token): ?string
    {
        if (! str_starts_with($token, self::HOME_LAYOUT_BLOCK_PREFIX)) {
            return null;
        }

        $uuid = trim(substr($token, strlen(self::HOME_LAYOUT_BLOCK_PREFIX)));

        return $uuid !== '' ? $uuid : null;
    }

    /**
     * @return array<int, string>
     */
    public static function sectionOrderFromLayout(array $layoutOrder): array
    {
        return self::normalizeSectionOrder(
            collect($layoutOrder)
                ->map(fn (mixed $token) => self::homeLayoutSectionKey((string) $token))
                ->filter()
                ->values()
                ->all(),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public static function prepare(?array $config, array $blocks = []): array
    {
        $config = is_array($config) ? $config : [];
        $defaults = self::defaults();
        $sectionOrder = self::normalizeSectionOrder($config['section_order'] ?? $defaults['section_order']);
        $layoutOrder = self::normalizeHomeLayoutOrder($config['layout_order'] ?? [], $sectionOrder, $blocks);

        return array_merge($config, [
            'section_order' => self::sectionOrderFromLayout($layoutOrder),
            'layout_order' => $layoutOrder,
            'featured_tour_category_slug' => trim((string) ($config['featured_tour_category_slug'] ?? '')),
            'featured_tour_limit' => FrontsiteCardGrid::normalizeLimit(
                $config['featured_tour_limit'] ?? null,
                FrontsiteCardGrid::MAX_ITEMS,
            ),
            'featured_blog_limit' => FrontsiteCardGrid::normalizeLimit(
                $config['featured_blog_limit'] ?? null,
                FrontsiteCardGrid::DEFAULT_LIMIT,
            ),
            'featured_service_slugs' => self::stringListValue($config['featured_service_slugs'] ?? []),
            'featured_blog_slugs' => self::stringListValue($config['featured_blog_slugs'] ?? []),
            'featured_destination_slugs' => self::stringListValue($config['featured_destination_slugs'] ?? []),
            'search' => self::normalizeSearchConfig($config['search'] ?? null, $defaults['search']),
            'geo_answer' => self::normalizeEnabledConfig($config['geo_answer'] ?? null, $defaults['geo_answer']),
            'topic_rail' => self::normalizeTopicRailConfig($config['topic_rail'] ?? null, $defaults['topic_rail']),
            'featured_tours' => self::normalizeFeaturedToursConfig($config['featured_tours'] ?? null, $defaults['featured_tours']),
            'tour_taxonomy_tabs' => self::normalizeEnabledConfig($config['tour_taxonomy_tabs'] ?? null, $defaults['tour_taxonomy_tabs']),
            'region_taxonomy_tabs' => self::normalizeEnabledConfig($config['region_taxonomy_tabs'] ?? null, $defaults['region_taxonomy_tabs']),
            'destination_slider' => self::normalizeDestinationSliderConfig($config['destination_slider'] ?? null, $defaults['destination_slider']),
            'gallery' => self::normalizeEnabledConfig($config['gallery'] ?? null, $defaults['gallery']),
            'services' => self::normalizeServicesConfig($config['services'] ?? null, $defaults['services']),
            'trust' => self::normalizeTrustConfig($config['trust'] ?? null, $defaults['trust']),
            'process' => self::normalizeProcessConfig($config['process'] ?? null, $defaults['process']),
            'blog_preview' => self::normalizeBlogPreviewConfig($config['blog_preview'] ?? null, $defaults['blog_preview']),
            'faq' => self::normalizeEnabledConfig($config['faq'] ?? null, $defaults['faq']),
            'cta' => self::normalizeEnabledConfig($config['cta'] ?? null, $defaults['cta']),
        ]);
    }

    public static function defaults(): array
    {
        return [
            'section_order' => array_keys(self::homeSectionOptions()),
            'layout_order' => [],
            'featured_tour_category_slug' => '',
            'featured_tour_limit' => FrontsiteCardGrid::MAX_ITEMS,
            'featured_blog_limit' => FrontsiteCardGrid::DEFAULT_LIMIT,
            'featured_service_slugs' => [],
            'featured_blog_slugs' => [],
            'featured_destination_slugs' => [],
            'search' => [
                'is_enabled' => true,
                'placeholder' => 'Bạn muốn đi đâu?',
                'button_label' => 'Tìm',
            ],
            'geo_answer' => [
                'is_enabled' => true,
            ],
            'topic_rail' => [
                'is_enabled' => true,
                'show_card_titles' => true,
                'eyebrow' => '',
                'title' => 'CHỦ ĐỀ TOUR',
                'description' => 'Chủ đề tour tiêu biểu năm 2026 với lịch trình độc bản. Từ hành trình hành hương tâm linh đến nghỉ dưỡng biển đảo đẳng cấp, Haidangtravel mang đến những trải nghiệm tinh tế và trọn gói nhất.',
            ],
            'featured_tours' => [
                'is_enabled' => true,
                'show_filters' => true,
                'cta_label' => 'Xem thêm',
                'card_cta_variant' => TourCardStyle::DEFAULT_CTA_VARIANT,
                'is_slider' => false,
                'all' => self::defaultFeaturedTourAll(),
                'filters' => self::defaultFeaturedTourFilters(),
                'popular_searches' => [],
            ],
            'tour_taxonomy_tabs' => [
                'is_enabled' => true,
            ],
            'region_taxonomy_tabs' => [
                'is_enabled' => true,
            ],
            'destination_slider' => [
                'is_enabled' => true,
                'title' => 'Điểm đến nổi bật',
                'description' => 'Lướt nhanh các hub điểm đến đang có tour hoạt động để chọn hướng đi phù hợp trước khi xem sâu hơn ở phần Điểm đến yêu thích.',
                'card_cta_label' => 'Xem hub điểm đến',
            ],
            'gallery' => [
                'is_enabled' => true,
            ],
            'services' => [
                'is_enabled' => true,
                'title' => 'Dịch vụ hỗ trợ',
                'description' => '',
                'cta_label' => 'Xem tất cả dịch vụ',
                'cta_url' => '/dich-vu',
            ],
            'trust' => [
                'is_enabled' => true,
                'title' => 'HAIDANGTRAVEL – HỆ SINH THÁI LỮ HÀNH TOÀN CẦU',
                'subtitle' => '',
                'description' => 'Giữ phần chứng minh ngắn, rõ đầu mối xử lý và đủ tin cậy để khách tự tin gửi yêu cầu ngay trên homepage.',
                'stats' => self::defaultTrustStats(),
                'awards' => self::defaultTrustAwards(),
            ],
            'process' => [
                'is_enabled' => true,
                'title' => 'Quy trình tư vấn',
                'description' => '',
                'cards' => self::defaultProcessCards(),
            ],
            'blog_preview' => [
                'is_enabled' => true,
                'title' => 'Cẩm Nang & Sự Kiện Nổi Bật',
                'description' => 'Tổng hợp kinh nghiệm du lịch thực tế, thông tin visa mới nhất và các sự kiện đặc sắc trong năm',
                'cta_label' => 'Xem tất cả bài viết',
                'cta_url' => '/blog',
            ],
            'faq' => [
                'is_enabled' => true,
            ],
            'cta' => [
                'is_enabled' => true,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected static function normalizeSectionOrder(mixed $value): array
    {
        $allowed = array_keys(self::homeSectionOptions());
        $ordered = is_array($value)
            ? collect($value)
                ->map(fn (mixed $section) => trim((string) $section))
                ->filter(fn (string $section) => in_array($section, $allowed, true))
                ->unique()
                ->values()
                ->all()
            : [];

        return array_values(array_merge($ordered, array_values(array_diff($allowed, $ordered))));
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, string>
     */
    protected static function normalizeHomeLayoutOrder(mixed $layoutOrder, mixed $sectionOrder, array $blocks): array
    {
        $legacyOrder = self::legacyHomeLayoutOrder($sectionOrder, $blocks);
        $allowedTokens = array_fill_keys($legacyOrder, true);
        $knownBlockTokens = collect($blocks)
            ->filter(fn ($block) => is_array($block) && filled($block['uuid'] ?? null))
            ->reject(fn (array $block) => in_array($block['type'] ?? null, [
                LandingPageBlocks::TYPE_HERO_SLIDER,
                LandingPageBlocks::TYPE_HERO_MEDIA,
                LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE,
            ], true))
            ->map(fn (array $block) => self::homeLayoutTokenForBlock((string) $block['uuid']))
            ->flip()
            ->all();
        $submitted = is_array($layoutOrder)
            ? collect($layoutOrder)
                ->map(fn (mixed $token) => trim((string) $token))
                ->filter(fn (string $token) => $token !== '' && (
                    isset($allowedTokens[$token])
                    || isset($knownBlockTokens[$token])
                    || ($blocks === [] && self::homeLayoutBlockUuid($token) !== null)
                ))
                ->unique()
                ->values()
                ->all()
            : [];

        $submittedMap = array_fill_keys($submitted, true);
        $missing = collect($legacyOrder)
            ->reject(fn (string $token) => isset($submittedMap[$token]))
            ->values();
        $order = array_values(array_merge(
            $submitted,
            $missing
                ->filter(fn (string $token) => self::homeLayoutSectionKey($token) !== null)
                ->values()
                ->all(),
        ));

        foreach ($missing->filter(fn (string $token) => self::homeLayoutBlockUuid($token) !== null)->values() as $token) {
            $legacyIndex = array_search($token, $legacyOrder, true);
            $inserted = false;
            $previousLegacyTokens = array_slice($legacyOrder, 0, $legacyIndex);

            if (! collect($previousLegacyTokens)->contains(fn (string $previousToken) => self::homeLayoutSectionKey($previousToken) !== null)) {
                foreach (array_reverse($previousLegacyTokens) as $previousToken) {
                    $targetIndex = array_search($previousToken, $order, true);

                    if ($targetIndex !== false) {
                        array_splice($order, $targetIndex + 1, 0, [$token]);
                        $inserted = true;

                        break;
                    }
                }

                if (! $inserted) {
                    array_splice($order, 0, 0, [$token]);
                    $inserted = true;
                }
            }

            foreach (array_slice($legacyOrder, $legacyIndex + 1) as $nextToken) {
                if ($inserted) {
                    break;
                }

                $targetIndex = array_search($nextToken, $order, true);

                if ($targetIndex !== false) {
                    array_splice($order, $targetIndex, 0, [$token]);
                    $inserted = true;

                    break;
                }
            }

            if ($inserted) {
                continue;
            }

            foreach (array_reverse(array_slice($legacyOrder, 0, $legacyIndex)) as $previousToken) {
                $targetIndex = array_search($previousToken, $order, true);

                if ($targetIndex !== false) {
                    array_splice($order, $targetIndex + 1, 0, [$token]);
                    $inserted = true;

                    break;
                }
            }

            if (! $inserted) {
                $order[] = $token;
            }
        }

        return $order;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, string>
     */
    protected static function legacyHomeLayoutOrder(mixed $sectionOrder, array $blocks): array
    {
        $tokensByPosition = self::homeBlockTokensByPosition($blocks);
        $order = $tokensByPosition[LandingPageBlocks::HOME_POSITION_AFTER_HERO] ?? [];

        foreach (self::normalizeSectionOrder($sectionOrder) as $sectionKey) {
            $slot = self::homeSectionSlot($sectionKey);

            if ($slot) {
                $order = array_merge($order, $tokensByPosition[$slot] ?? []);
            }

            $order[] = self::homeLayoutTokenForSection($sectionKey);

            if ($sectionKey === 'cta') {
                $order = array_merge($order, $tokensByPosition[LandingPageBlocks::HOME_POSITION_AFTER_CTA] ?? []);
            }
        }

        return collect($order)->unique()->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<string, array<int, string>>
     */
    protected static function homeBlockTokensByPosition(array $blocks): array
    {
        $heroTypes = [
            LandingPageBlocks::TYPE_HERO_SLIDER,
            LandingPageBlocks::TYPE_HERO_MEDIA,
            LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE,
        ];

        return collect($blocks)
            ->filter(fn ($block) => is_array($block) && filled($block['uuid'] ?? null))
            ->reject(fn (array $block) => in_array($block['type'] ?? null, $heroTypes, true))
            ->map(function (array $block): array {
                return [
                    'position' => LandingPageBlocks::homePositionForBlock($block),
                    'token' => self::homeLayoutTokenForBlock((string) $block['uuid']),
                ];
            })
            ->filter(fn (array $item) => $item['position'] !== LandingPageBlocks::HOME_POSITION_DEFAULT)
            ->groupBy('position')
            ->map(fn ($items) => $items->pluck('token')->values()->all())
            ->all();
    }

    protected static function defaultFeaturedTourTabs(): array
    {
        return [
            'domestic' => [
                'label' => 'Tour trong nước',
                'title' => 'Tổng hợp tour mới 2026',
                'description' => 'Khám phá danh sách tour đa dạng được thiết kế chuyên nghiệp, tối ưu thời gian và ngân sách nhưng vẫn đảm bảo tiêu chuẩn sang trọng tuyệt đối.',
            ],
            'international' => [
                'label' => 'Tour nước ngoài',
                'title' => 'Tổng hợp tour mới 2026',
                'description' => 'Khám phá danh sách tour đa dạng được thiết kế chuyên nghiệp, tối ưu thời gian và ngân sách nhưng vẫn đảm bảo tiêu chuẩn sang trọng tuyệt đối.',
            ],
            'group' => [
                'label' => 'Tour đoàn',
                'title' => 'Tổng hợp tour mới 2026',
                'description' => 'Khám phá danh sách tour đa dạng được thiết kế chuyên nghiệp, tối ưu thời gian và ngân sách nhưng vẫn đảm bảo tiêu chuẩn sang trọng tuyệt đối.',
            ],
        ];
    }

    protected static function defaultFeaturedTourAll(): array
    {
        return [
            'label' => 'Tất cả',
            'title' => 'Tour hot trong tháng',
            'description' => 'Tổng hợp các tour trọn gói và tour du lịch đoàn đang được quan tâm để bạn dễ so sánh hành trình, lịch đi và mức giá.',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected static function defaultFeaturedTourFilters(): array
    {
        $legacyTabs = self::defaultFeaturedTourTabs();

        return collect([
            TourScope::International,
            TourScope::Domestic,
            TourScope::Group,
        ])->map(function (TourScope $scope) use ($legacyTabs): array {
            $tab = $legacyTabs[$scope->value];

            return self::featuredTourFilter(
                self::FEATURED_TOUR_FILTER_SCOPE,
                $scope->value,
                $tab['label'],
                $tab['title'],
                $tab['description'],
                $scope->value,
            );
        })->all();
    }

    protected static function defaultProcessCards(): array
    {
        return [
            self::processCard(
                'Nhận nhu cầu',
                'Thu ngân sách, ngày đi, điểm khởi hành, số lượng khách và ưu tiên điểm đến hoặc mục tiêu chuyến đi.',
            ),
            self::processCard(
                'Gợi ý hành trình',
                'Đề xuất các tour phù hợp hoặc phương án tour đoàn, đồng thời gợi ý các dịch vụ hỗ trợ cần chuẩn bị.',
            ),
            self::processCard(
                'Chốt phương án',
                'Xác nhận lịch trình, mức giá, loại hình lưu trú, phương tiện và các yêu cầu phát sinh trước khi đi.',
            ),
            self::processCard(
                'Đồng hành trước chuyến đi',
                'Hỗ trợ thêm về visa, vé máy bay, thuê xe, SIM du lịch và thông tin cần chuẩn bị cho hành trình.',
            ),
        ];
    }

    protected static function defaultTrustStats(): array
    {
        return [
            self::trustStat(),
            self::trustStat(),
            self::trustStat(),
        ];
    }

    protected static function defaultTrustAwards(): array
    {
        return [self::trustAward()];
    }

    protected static function normalizeBlogPreviewConfig(mixed $value, array $defaults): array
    {
        $config = is_array($value) ? $value : [];

        return [
            'is_enabled' => self::enabledValue($config['is_enabled'] ?? null, (bool) ($defaults['is_enabled'] ?? true)),
            'title' => self::stringValue($config['title'] ?? $defaults['title']),
            'description' => self::stringValue($config['description'] ?? $defaults['description']),
            'cta_label' => self::stringValue($config['cta_label'] ?? $defaults['cta_label']),
            'cta_url' => self::stringValue($config['cta_url'] ?? $defaults['cta_url']),
        ];
    }

    protected static function normalizeDestinationSliderConfig(mixed $value, array $defaults): array
    {
        $config = is_array($value) ? $value : [];

        return [
            'is_enabled' => self::enabledValue($config['is_enabled'] ?? null, (bool) ($defaults['is_enabled'] ?? true)),
            'title' => self::stringValue($config['title'] ?? $defaults['title']),
            'description' => self::stringValue($config['description'] ?? $defaults['description']),
            'card_cta_label' => self::stringValue($config['card_cta_label'] ?? $defaults['card_cta_label']),
        ];
    }

    protected static function normalizeFeaturedToursConfig(mixed $value, array $defaults): array
    {
        $config = is_array($value) ? $value : [];
        $all = is_array($config['all'] ?? null)
            ? $config['all']
            : (is_array(data_get($config, 'tabs.all')) ? data_get($config, 'tabs.all') : []);
        $filters = array_key_exists('filters', $config) && is_array($config['filters'])
            ? array_values($config['filters'])
            : self::legacyFeaturedTourFilters($config['tabs'] ?? null, $defaults['filters']);
        $popularSearches = is_array($config['popular_searches'] ?? null)
            ? array_values($config['popular_searches'])
            : [];
        $normalizedFilters = collect($filters)
            ->filter(fn (mixed $filter) => is_array($filter))
            ->map(fn (array $filter) => self::featuredTourFilter(
                (string) ($filter['source_type'] ?? self::FEATURED_TOUR_FILTER_SCOPE),
                (string) ($filter['source_value'] ?? ''),
                (string) ($filter['label'] ?? ''),
                (string) ($filter['title'] ?? ''),
                (string) ($filter['description'] ?? ''),
                filled($filter['uuid'] ?? null) ? (string) $filter['uuid'] : null,
            ))
            ->take(self::FEATURED_TOUR_FILTER_LIMIT)
            ->values()
            ->all();
        $validFilterUuids = collect($normalizedFilters)->pluck('uuid')->filter()->all();

        return [
            'is_enabled' => self::enabledValue($config['is_enabled'] ?? null, (bool) ($defaults['is_enabled'] ?? true)),
            'cta_label' => self::stringValue($config['cta_label'] ?? $defaults['cta_label']),
            'card_cta_variant' => TourCardStyle::normalizeCtaVariant($config['card_cta_variant'] ?? $defaults['card_cta_variant']),
            'is_slider' => (bool) ($config['is_slider'] ?? $defaults['is_slider']),
            'show_filters' => (bool) ($config['show_filters'] ?? $defaults['show_filters']),
            'all' => [
                'label' => self::stringValue($all['label'] ?? $defaults['all']['label']),
                'title' => self::stringValue($all['title'] ?? $defaults['all']['title']),
                'description' => self::stringValue($all['description'] ?? $defaults['all']['description']),
            ],
            'filters' => $normalizedFilters,
            'popular_searches' => collect($popularSearches)
                ->filter(fn (mixed $item) => is_array($item))
                ->map(function (array $item) use ($validFilterUuids): array {
                    $filterUuid = self::stringValue($item['filter_uuid'] ?? '');

                    return self::featuredTourPopularSearch(
                        (string) ($item['label'] ?? ''),
                        (string) ($item['url'] ?? ''),
                        filled($item['uuid'] ?? null) ? (string) $item['uuid'] : null,
                        in_array($filterUuid, $validFilterUuids, true) ? $filterUuid : '',
                    );
                })
                ->filter(fn (array $item) => $item['label'] !== '' && self::isSafeFeaturedTourPopularSearchUrl($item['url']))
                ->take(self::FEATURED_TOUR_POPULAR_SEARCH_LIMIT)
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $defaults
     * @return array<int, array<string, mixed>>
     */
    protected static function legacyFeaturedTourFilters(mixed $value, array $defaults): array
    {
        if (! is_array($value)) {
            return $defaults;
        }

        $legacyTabs = self::defaultFeaturedTourTabs();

        return collect([
            TourScope::International,
            TourScope::Domestic,
            TourScope::Group,
        ])->map(function (TourScope $scope) use ($legacyTabs, $value): array {
            $defaultTab = $legacyTabs[$scope->value];
            $tab = is_array($value[$scope->value] ?? null) ? $value[$scope->value] : [];

            return self::featuredTourFilter(
                self::FEATURED_TOUR_FILTER_SCOPE,
                $scope->value,
                (string) ($tab['label'] ?? $defaultTab['label']),
                (string) ($tab['title'] ?? $defaultTab['title']),
                (string) ($tab['description'] ?? $defaultTab['description']),
                $scope->value,
            );
        })->all();
    }

    protected static function normalizeProcessConfig(mixed $value, array $defaults): array
    {
        $config = is_array($value) ? $value : [];
        $cards = collect(is_array($config['cards'] ?? null) ? array_values($config['cards']) : $defaults['cards'])
            ->filter(fn (mixed $card) => is_array($card))
            ->map(fn (array $card) => [
                'uuid' => filled($card['uuid'] ?? null) ? (string) $card['uuid'] : (string) Str::uuid(),
                'title' => self::stringValue($card['title'] ?? ''),
                'description' => self::stringValue($card['description'] ?? ''),
                'image_url' => self::stringValue($card['image_url'] ?? ''),
                'image_alt' => self::stringValue($card['image_alt'] ?? ''),
                'source_library_media_id' => is_numeric($card['source_library_media_id'] ?? null) ? (int) $card['source_library_media_id'] : null,
            ])
            ->values()
            ->all();

        if ($cards === []) {
            $cards = $defaults['cards'];
        }

        return [
            'is_enabled' => self::enabledValue($config['is_enabled'] ?? null, (bool) ($defaults['is_enabled'] ?? true)),
            'title' => self::stringValue($config['title'] ?? $defaults['title']),
            'description' => self::stringValue($config['description'] ?? $defaults['description']),
            'cards' => $cards,
        ];
    }

    protected static function normalizeSearchConfig(mixed $value, array $defaults): array
    {
        $config = is_array($value) ? $value : [];

        return [
            'is_enabled' => self::enabledValue($config['is_enabled'] ?? null, (bool) ($defaults['is_enabled'] ?? true)),
            'placeholder' => self::stringValue($config['placeholder'] ?? $defaults['placeholder']),
            'button_label' => self::stringValue($config['button_label'] ?? $defaults['button_label']),
        ];
    }

    protected static function normalizeEnabledConfig(mixed $value, array $defaults): array
    {
        $config = is_array($value) ? $value : [];

        return [
            'is_enabled' => self::enabledValue($config['is_enabled'] ?? null, (bool) ($defaults['is_enabled'] ?? true)),
        ];
    }

    protected static function normalizeTopicRailConfig(mixed $value, array $defaults): array
    {
        $config = is_array($value) ? $value : [];

        return [
            'is_enabled' => self::enabledValue($config['is_enabled'] ?? null, (bool) ($defaults['is_enabled'] ?? true)),
            'show_card_titles' => self::enabledValue($config['show_card_titles'] ?? null, (bool) ($defaults['show_card_titles'] ?? true)),
            'eyebrow' => self::stringValue($config['eyebrow'] ?? $defaults['eyebrow']),
            'title' => self::stringValue($config['title'] ?? $defaults['title']),
            'description' => self::stringValue($config['description'] ?? $defaults['description']),
        ];
    }

    protected static function normalizeServicesConfig(mixed $value, array $defaults): array
    {
        $config = is_array($value) ? $value : [];

        return [
            'is_enabled' => self::enabledValue($config['is_enabled'] ?? null, (bool) ($defaults['is_enabled'] ?? true)),
            'title' => self::stringValue($config['title'] ?? $defaults['title']),
            'description' => self::stringValue($config['description'] ?? $defaults['description']),
            'cta_label' => self::stringValue($config['cta_label'] ?? $defaults['cta_label']),
            'cta_url' => self::stringValue($config['cta_url'] ?? $defaults['cta_url']),
        ];
    }

    protected static function normalizeTrustConfig(mixed $value, array $defaults): array
    {
        $config = is_array($value) ? $value : [];
        $stats = collect(is_array($config['stats'] ?? null) ? array_values($config['stats']) : $defaults['stats'])
            ->filter(fn (mixed $stat) => is_array($stat))
            ->map(fn (array $stat) => [
                'uuid' => filled($stat['uuid'] ?? null) ? (string) $stat['uuid'] : (string) Str::uuid(),
                'value' => self::stringValue($stat['value'] ?? ''),
                'label' => self::stringValue($stat['label'] ?? ''),
            ])
            ->take(3)
            ->values()
            ->all();

        if ($stats === []) {
            $stats = $defaults['stats'];
        }

        $awards = collect(is_array($config['awards'] ?? null) ? array_values($config['awards']) : $defaults['awards'])
            ->filter(fn (mixed $award) => is_array($award))
            ->map(fn (array $award) => [
                'uuid' => filled($award['uuid'] ?? null) ? (string) $award['uuid'] : (string) Str::uuid(),
                'title' => self::stringValue($award['title'] ?? ''),
                'description' => self::stringValue($award['description'] ?? ''),
                'image_url' => self::stringValue($award['image_url'] ?? ''),
                'image_alt' => self::stringValue($award['image_alt'] ?? ''),
                'source_library_media_id' => is_numeric($award['source_library_media_id'] ?? null) ? (int) $award['source_library_media_id'] : null,
            ])
            ->take(12)
            ->values()
            ->all();

        if ($awards === []) {
            $awards = $defaults['awards'];
        }

        return [
            'is_enabled' => self::enabledValue($config['is_enabled'] ?? null, (bool) ($defaults['is_enabled'] ?? true)),
            'title' => self::stringValue($config['title'] ?? $defaults['title']),
            'subtitle' => self::stringValue($config['subtitle'] ?? $defaults['subtitle']),
            'description' => self::stringValue($config['description'] ?? $defaults['description']),
            'stats' => $stats,
            'awards' => $awards,
        ];
    }

    protected static function processCard(string $title, string $description, string $imageUrl = '', string $imageAlt = ''): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'title' => $title,
            'description' => $description,
            'image_url' => $imageUrl,
            'image_alt' => $imageAlt !== '' ? $imageAlt : $title,
            'source_library_media_id' => null,
        ];
    }

    protected static function stringListValue(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn (mixed $item) => trim((string) $item))
            ->filter(fn (string $item) => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    protected static function stringValue(mixed $value): string
    {
        return trim((string) $value);
    }

    protected static function trustStat(string $value = '', string $label = ''): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'value' => $value,
            'label' => $label,
        ];
    }

    protected static function trustAward(string $title = '', string $description = '', string $imageUrl = '', string $imageAlt = ''): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'title' => $title,
            'description' => $description,
            'image_url' => $imageUrl,
            'image_alt' => $imageAlt !== '' ? $imageAlt : $title,
            'source_library_media_id' => null,
        ];
    }

    protected static function enabledValue(mixed $value, bool $default = true): bool
    {
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
