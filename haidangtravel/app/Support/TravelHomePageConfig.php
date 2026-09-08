<?php

namespace App\Support;

use Illuminate\Support\Str;

class TravelHomePageConfig
{
    public static function prepare(?array $config): array
    {
        $config = is_array($config) ? $config : [];
        $defaults = self::defaults();

        return array_merge($config, [
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
            'topic_rail' => self::normalizeTopicRailConfig($config['topic_rail'] ?? null, $defaults['topic_rail']),
            'featured_tours' => self::normalizeFeaturedToursConfig($config['featured_tours'] ?? null, $defaults['featured_tours']),
            'destination_slider' => self::normalizeDestinationSliderConfig($config['destination_slider'] ?? null, $defaults['destination_slider']),
            'services' => self::normalizeServicesConfig($config['services'] ?? null, $defaults['services']),
            'trust' => self::normalizeTrustConfig($config['trust'] ?? null, $defaults['trust']),
            'process' => self::normalizeProcessConfig($config['process'] ?? null, $defaults['process']),
            'blog_preview' => self::normalizeBlogPreviewConfig($config['blog_preview'] ?? null, $defaults['blog_preview']),
        ]);
    }

    public static function defaults(): array
    {
        return [
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
            'topic_rail' => [
                'is_enabled' => true,
                'title' => 'CHỦ ĐỀ TOUR',
                'description' => 'Chủ đề tour tiêu biểu năm 2026 với lịch trình độc bản. Từ hành trình hành hương tâm linh đến nghỉ dưỡng biển đảo đẳng cấp, Haidangtravel mang đến những trải nghiệm tinh tế và trọn gói nhất.',
            ],
            'featured_tours' => [
                'is_enabled' => true,
                'cta_label' => 'Xem danh sách tour',
                'tabs' => self::defaultFeaturedTourTabs(),
            ],
            'destination_slider' => [
                'is_enabled' => true,
                'title' => 'Điểm đến nổi bật',
                'description' => 'Lướt nhanh các hub điểm đến đang có tour hoạt động để chọn hướng đi phù hợp trước khi xem sâu hơn ở phần Điểm đến yêu thích.',
                'card_cta_label' => 'Xem hub điểm đến',
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
                'title' => 'Hải Đăng Travel phù hợp khi bạn cần chốt rõ và nhanh',
                'description' => 'Giữ phần chứng minh ngắn, rõ đầu mối xử lý và đủ tin cậy để khách tự tin gửi yêu cầu ngay trên homepage.',
                'cards' => self::defaultTrustCards(),
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
        ];
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

    protected static function defaultTrustCards(): array
    {
        return [
            self::trustCard(
                'Một đầu mối xử lý',
                'Một yêu cầu, đội ngũ theo tới cùng',
                'Tour, visa, vé máy bay và nhu cầu tour đoàn đều đi chung một luồng tiếp nhận để phản hồi gọn và ít vòng trao đổi hơn.',
                'fa-solid fa-route',
            ),
            self::trustCard(
                'So sánh dễ hơn',
                'Thông tin tour được bày theo logic chốt mua',
                'Ngày đi, thời lượng, giá và CTA luôn hiện sớm để khách lọc nhanh phương án phù hợp thay vì phải mở từng tour.',
                'fa-solid fa-calendar-check',
            ),
            self::trustCard(
                'Đồng hành trước chuyến đi',
                'Không dừng ở bước gửi báo giá',
                'Đội ngũ tiếp tục hỗ trợ hồ sơ, dịch vụ đi kèm và những việc cần chuẩn bị trước ngày khởi hành.',
                'fa-solid fa-shield-heart',
            ),
        ];
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
        $tabs = is_array($config['tabs'] ?? null) ? $config['tabs'] : [];

        return [
            'is_enabled' => self::enabledValue($config['is_enabled'] ?? null, (bool) ($defaults['is_enabled'] ?? true)),
            'cta_label' => self::stringValue($config['cta_label'] ?? $defaults['cta_label']),
            'tabs' => collect($defaults['tabs'])
                ->mapWithKeys(function (array $defaultTab, string $scope) use ($tabs): array {
                    $tab = is_array($tabs[$scope] ?? null) ? $tabs[$scope] : [];

                    return [
                        $scope => [
                            'label' => self::stringValue($tab['label'] ?? $defaultTab['label']),
                            'title' => self::stringValue($tab['title'] ?? $defaultTab['title']),
                            'description' => self::stringValue($tab['description'] ?? $defaultTab['description']),
                        ],
                    ];
                })
                ->all(),
        ];
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
        $cards = collect(is_array($config['cards'] ?? null) ? array_values($config['cards']) : $defaults['cards'])
            ->filter(fn (mixed $card) => is_array($card))
            ->map(fn (array $card) => [
                'uuid' => filled($card['uuid'] ?? null) ? (string) $card['uuid'] : (string) Str::uuid(),
                'icon' => self::stringValue($card['icon'] ?? ''),
                'highlight' => self::stringValue($card['highlight'] ?? ''),
                'title' => self::stringValue($card['title'] ?? ''),
                'text' => self::stringValue($card['text'] ?? ''),
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

    protected static function trustCard(string $highlight, string $title, string $text, string $icon = ''): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'icon' => $icon,
            'highlight' => $highlight,
            'title' => $title,
            'text' => $text,
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
