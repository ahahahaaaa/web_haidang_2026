<?php

namespace App\Support;

use Illuminate\Support\Str;

class HomePageContent
{
    public static function defaultConfig(): array
    {
        return [
            'hero' => [
                'slides' => [
                    self::heroSlide(
                        eyebrow: 'PHONG THÀNH ĐẠT',
                        title: 'Kiến tạo không gian, xây dựng niềm tin',
                        description: 'Đồng hành cùng chủ đầu tư từ ý tưởng, thiết kế đến triển khai công trình với quy trình rõ ràng và hình ảnh thương hiệu chỉn chu.',
                    ),
                    self::heroSlide(
                        eyebrow: 'TỔNG THẦU XÂY DỰNG',
                        title: 'Giải pháp thi công và hoàn thiện đồng bộ',
                        description: 'Tối ưu tiến độ, chất lượng vật tư và trải nghiệm hợp tác để công trình đi từ bản vẽ đến bàn giao theo một nhịp thống nhất.',
                        primaryLabel: 'Xem dịch vụ',
                        primaryUrl: '/dich-vu',
                        secondaryLabel: 'Xem dự án',
                        secondaryUrl: '/du-an',
                    ),
                ],
            ],
            'package_showcase' => [
                'eyebrow' => 'Gói thi công nhà ở',
                'title' => 'Đi thẳng vào gói thi công phù hợp trước khi chốt phạm vi công trình',
                'description' => 'Khối nội dung này dẫn khách hàng từ trang chủ sang 3 trang chi tiết chuyên sâu: gói tiêu chuẩn, gói cao cấp và trang so sánh khác biệt để ra quyết định nhanh hơn.',
                'panel_badge' => 'Bộ trang chi tiết chuyên sâu',
                'panel_title' => 'Cùng một nhu cầu xây nhà nhưng cách tổ chức phần thô, hoàn thiện và mức độ kiểm soát có thể rất khác nhau.',
                'panel_description' => 'Dùng block này để giới thiệu nhanh 2 gói triển khai phổ biến và 1 trang so sánh, giúp khách hàng tự định vị nhu cầu trước khi gửi tư vấn.',
                'primary_label' => 'Xem trang so sánh',
                'primary_url' => '/giai-phap/so-sanh-tieu-chuan-va-cao-cap',
                'secondary_label' => 'Nhận tư vấn theo nhu cầu',
                'secondary_url' => '#consultation',
                'background_alt' => 'Giới thiệu gói thi công phần thô và nhân công hoàn thiện',
                'items' => [
                    self::packageShowcaseItem(
                        tone: 'standard',
                        badge: 'Gói tiêu chuẩn',
                        title: 'Thi công phần thô + nhân công hoàn thiện tiêu chuẩn',
                        description: 'Phù hợp nhà phố và nhà ở gia đình cần tiến độ rõ, chất lượng ổn định và ngân sách dễ kiểm soát.',
                        url: '/giai-phap/phan-tho-hoan-thien-tieu-chuan',
                    ),
                    self::packageShowcaseItem(
                        tone: 'premium',
                        badge: 'Gói cao cấp',
                        title: 'Thi công phần thô + nhân công hoàn thiện cao cấp',
                        description: 'Dành cho công trình cần kiểm soát sâu hơn về mockup, mẫu duyệt, chi tiết bề mặt và cảm quan hoàn thiện.',
                        url: '/giai-phap/phan-tho-hoan-thien-cao-cap',
                    ),
                    self::packageShowcaseItem(
                        tone: 'compare',
                        badge: 'So sánh 2 gói',
                        title: 'Trang so sánh điểm khác biệt giữa gói tiêu chuẩn và cao cấp',
                        description: 'Giúp khách hàng nhìn nhanh mục tiêu sử dụng, mức độ kiểm soát và cách chọn gói phù hợp trước khi làm báo giá.',
                        url: '/giai-phap/so-sanh-tieu-chuan-va-cao-cap',
                        linkLabel: 'Xem bảng so sánh',
                    ),
                ],
            ],
            'stats' => [
                'items' => [
                    self::statItem('15+', 'Năm kinh nghiệm'),
                    self::statItem('320', 'Dự án hoàn thành'),
                    self::statItem('85+', 'Nhân sự chuyên gia'),
                    self::statItem('ISO / QA', 'Cam kết chất lượng'),
                ],
            ],
            'services' => [
                'eyebrow' => 'Dịch vụ của chúng tôi',
                'title' => 'Giải pháp kiến trúc và xây dựng toàn diện',
                'description' => 'Quản lý riêng nội dung từng dịch vụ nổi bật trên trang chủ để dẫn khách hàng đến đúng nhóm nhu cầu.',
                'cta_label' => 'Xem tất cả',
                'cta_url' => '/dich-vu',
                'items' => [
                    self::serviceItem('domain', 'Thi công nhà phố trọn gói', 'Từ phần thô đến hoàn thiện, bám sát tiến độ và ngân sách đã thống nhất.'),
                    self::serviceItem('holiday_village', 'Thi công biệt thự cao cấp', 'Giải pháp tổ chức thi công và kiểm soát chất lượng theo tiêu chuẩn cao.'),
                    self::serviceItem('architecture', 'Thiết kế nội thất', 'Đồng bộ công năng, thẩm mỹ và khả năng triển khai trên thực tế công trình.'),
                    self::serviceItem('format_paint', 'Cải tạo và hoàn thiện', 'Nâng cấp không gian đang vận hành với lộ trình thi công rõ ràng và ít gián đoạn.'),
                ],
            ],
            'gallery' => [
                'eyebrow' => 'Kiến trúc mang tầm vóc',
                'title' => 'Khoảnh khắc công trình và không gian tiêu biểu',
                'description' => 'Tải ảnh riêng cho HomePage hoặc chọn trực tiếp từ Media để tạo gallery theo đúng định hướng thương hiệu.',
                'items' => [
                    self::galleryItem('Biệt thự hiện đại', 'Mặt tiền nổi bật'),
                    self::galleryItem('Không gian nội thất', 'Hoàn thiện chỉn chu'),
                    self::galleryItem('Nhà phố thương mại', 'Tối ưu công năng'),
                    self::galleryItem('Công trình bàn giao', 'Hình ảnh thực tế'),
                ],
            ],
            'process' => [
                'eyebrow' => 'Quy trình làm việc',
                'title' => 'Chuyên nghiệp trong từng bước',
                'description' => 'Có thể thêm hoặc bớt card linh hoạt, giao diện desktop tự cân theo 3 đến 6 bước và mobile chuyển sang dạng slide 1 card.',
                'cards' => [
                    self::processCard('Tư vấn', 'Lắng nghe nhu cầu, ngân sách và mục tiêu sử dụng của công trình.'),
                    self::processCard('Khảo sát', 'Đánh giá hiện trạng, khu đất và các điều kiện triển khai thực tế.'),
                    self::processCard('Đề xuất', 'Lập phương án thiết kế, vật tư và chi phí theo mục tiêu đầu tư.'),
                    self::processCard('Triển khai', 'Tổ chức thi công, giám sát chất lượng và bám mốc tiến độ.'),
                ],
            ],
            'values' => [
                'eyebrow' => 'Cảm nhận khách hàng',
                'title' => 'Những phản hồi thực tế từ khách hàng sau quá trình đồng hành và bàn giao công trình',
                'description' => 'Section này dùng để hiển thị testimonial trên homepage. Mobile là slider 1 card, desktop hiển thị 2 card cùng lúc.',
                'cards' => [
                    self::valueCard('Anh Minh Tuấn', 'Chủ đầu tư nhà phố 4 tầng, TP Thủ Đức', 'Điều tôi hài lòng nhất là đội ngũ làm việc rất rõ ràng ở từng giai đoạn. Từ tiến độ, vật tư đến các đầu việc phát sinh đều được cập nhật kịp thời nên gia đình tôi luôn yên tâm trong suốt quá trình thi công.', 'Ảnh đại diện anh Minh Tuấn'),
                    self::valueCard('Chị Ngọc Anh', 'Khách hàng hoàn thiện nội thất biệt thự, Quận 2', 'Phần thiết kế và thi công bám sát nhau nên khi vào thực tế gần như không bị lệch tinh thần ban đầu. Đội ngũ xử lý chi tiết tốt, phối hợp nhẹ nhàng và luôn tôn trọng mong muốn sử dụng thật của gia đình tôi.', 'Ảnh đại diện chị Ngọc Anh'),
                    self::valueCard('Anh Quốc Bảo', 'Chủ đầu tư cải tạo văn phòng, Bình Thạnh', 'Tiến độ là yếu tố quan trọng nhất với chúng tôi và đội ngũ đã giữ nhịp rất tốt. Kế hoạch triển khai rõ, báo cáo gọn, xử lý nhanh nên công việc vận hành của công ty không bị ảnh hưởng nhiều.', 'Ảnh đại diện anh Quốc Bảo'),
                ],
            ],
            'insights' => [
                'eyebrow' => 'Góc nhìn',
                'title' => 'Tin tức và góc nhìn xây dựng để nuôi chuyển đổi dài hạn',
                'description' => 'Có thể nhập ID bài viết hoặc chọn nhanh từ danh sách blog hiện có để hiển thị trên trang chủ.',
                'cta_label' => 'Xem tất cả bài viết',
                'cta_url' => '/blog',
                'items' => [
                    self::insightItem(''),
                    self::insightItem(''),
                    self::insightItem(''),
                    self::insightItem(''),
                    self::insightItem(''),
                    self::insightItem(''),
                ],
            ],
            'final_cta' => [
                'title' => 'Sẵn sàng khởi công công trình của bạn?',
                'description' => 'Hãy để đội ngũ của chúng tôi cùng bạn rà phạm vi, chi phí và lộ trình triển khai phù hợp cho dự án ngay từ hôm nay.',
                'primary_label' => 'Yêu cầu tư vấn ngay',
                'secondary_label' => 'Gọi hotline',
                'secondary_url' => '',
            ],
            'consultation' => [
                'eyebrow' => 'Liên hệ ngay',
                'title' => 'Yêu cầu tư vấn cho công trình của bạn',
                'description' => 'Điền thông tin bên dưới, đội ngũ sẽ liên hệ lại để trao đổi phạm vi, nhu cầu và mốc triển khai phù hợp.',
                'button_label' => 'Gửi yêu cầu',
                'success_message' => 'Yêu cầu tư vấn đã được gửi. Đội ngũ sẽ liên hệ với bạn trong thời gian sớm nhất.',
            ],
        ];
    }

    public static function prepareConfig(?array $config): array
    {
        if (! is_array($config) || $config === []) {
            return self::defaultConfig();
        }

        $defaults = self::defaultConfig();

        return [
            'hero' => [
                'slides' => collect(is_array(data_get($config, 'hero.slides')) ? array_values(data_get($config, 'hero.slides')) : [])
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn (array $item) => [
                        'uuid' => self::uuidValue(data_get($item, 'uuid')),
                        'eyebrow' => self::stringValue(data_get($item, 'eyebrow')),
                        'title' => RichText::sanitizeInline(data_get($item, 'title')),
                        'description' => RichText::sanitizeInline(data_get($item, 'description')),
                        'primary_label' => self::stringValue(data_get($item, 'primary_label')),
                        'primary_url' => self::stringValue(data_get($item, 'primary_url')),
                        'secondary_label' => self::stringValue(data_get($item, 'secondary_label')),
                        'secondary_url' => self::stringValue(data_get($item, 'secondary_url')),
                        'image_alt' => self::stringValue(data_get($item, 'image_alt')),
                    ])->values()->all(),
            ],
            'package_showcase' => [
                'eyebrow' => self::stringValue(data_get($config, 'package_showcase.eyebrow', data_get($defaults, 'package_showcase.eyebrow'))),
                'title' => self::stringValue(data_get($config, 'package_showcase.title', data_get($defaults, 'package_showcase.title'))),
                'description' => self::stringValue(data_get($config, 'package_showcase.description', data_get($defaults, 'package_showcase.description'))),
                'panel_badge' => self::stringValue(data_get($config, 'package_showcase.panel_badge', data_get($defaults, 'package_showcase.panel_badge'))),
                'panel_title' => self::stringValue(data_get($config, 'package_showcase.panel_title', data_get($defaults, 'package_showcase.panel_title'))),
                'panel_description' => self::stringValue(data_get($config, 'package_showcase.panel_description', data_get($defaults, 'package_showcase.panel_description'))),
                'primary_label' => self::stringValue(data_get($config, 'package_showcase.primary_label', data_get($defaults, 'package_showcase.primary_label'))),
                'primary_url' => self::stringValue(data_get($config, 'package_showcase.primary_url', data_get($defaults, 'package_showcase.primary_url'))),
                'secondary_label' => self::stringValue(data_get($config, 'package_showcase.secondary_label', data_get($defaults, 'package_showcase.secondary_label'))),
                'secondary_url' => self::stringValue(data_get($config, 'package_showcase.secondary_url', data_get($defaults, 'package_showcase.secondary_url'))),
                'background_alt' => self::stringValue(data_get($config, 'package_showcase.background_alt', data_get($defaults, 'package_showcase.background_alt'))),
                'items' => collect(is_array(data_get($config, 'package_showcase.items')) ? array_values(data_get($config, 'package_showcase.items')) : array_values(data_get($defaults, 'package_showcase.items', [])))
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn (array $item) => [
                        'uuid' => self::uuidValue(data_get($item, 'uuid')),
                        'tone' => self::packageTone(data_get($item, 'tone')),
                        'badge' => self::stringValue(data_get($item, 'badge')),
                        'title' => self::stringValue(data_get($item, 'title')),
                        'description' => self::stringValue(data_get($item, 'description')),
                        'link_label' => self::stringValue(data_get($item, 'link_label')),
                        'url' => self::stringValue(data_get($item, 'url')),
                    ])->values()->all(),
            ],
            'stats' => [
                'items' => collect(is_array(data_get($config, 'stats.items')) ? array_values(data_get($config, 'stats.items')) : [])
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn (array $item) => [
                        'uuid' => self::uuidValue(data_get($item, 'uuid')),
                        'value' => self::stringValue(data_get($item, 'value')),
                        'label' => self::stringValue(data_get($item, 'label')),
                    ])->values()->all(),
            ],
            'services' => [
                'eyebrow' => self::stringValue(data_get($config, 'services.eyebrow')),
                'title' => self::stringValue(data_get($config, 'services.title')),
                'description' => self::stringValue(data_get($config, 'services.description')),
                'cta_label' => self::stringValue(data_get($config, 'services.cta_label')),
                'cta_url' => self::stringValue(data_get($config, 'services.cta_url')),
                'items' => collect(is_array(data_get($config, 'services.items')) ? array_values(data_get($config, 'services.items')) : [])
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn (array $item) => [
                        'uuid' => self::uuidValue(data_get($item, 'uuid')),
                        'icon' => self::stringValue(data_get($item, 'icon')),
                        'title' => self::stringValue(data_get($item, 'title')),
                        'description' => self::stringValue(data_get($item, 'description')),
                        'service_id' => self::integerValue(data_get($item, 'service_id')),
                        'link_label' => self::stringValue(data_get($item, 'link_label')),
                        'url' => self::stringValue(data_get($item, 'url')),
                    ])->values()->all(),
            ],
            'gallery' => [
                'eyebrow' => self::stringValue(data_get($config, 'gallery.eyebrow')),
                'title' => self::stringValue(data_get($config, 'gallery.title')),
                'description' => self::stringValue(data_get($config, 'gallery.description')),
                'items' => collect(is_array(data_get($config, 'gallery.items')) ? array_values(data_get($config, 'gallery.items')) : [])
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn (array $item) => [
                        'uuid' => self::uuidValue(data_get($item, 'uuid')),
                        'title' => self::stringValue(data_get($item, 'title')),
                        'subtitle' => self::stringValue(data_get($item, 'subtitle')),
                        'url' => self::stringValue(data_get($item, 'url')),
                        'image_alt' => self::stringValue(data_get($item, 'image_alt')),
                    ])->values()->all(),
            ],
            'process' => [
                'eyebrow' => self::stringValue(data_get($config, 'process.eyebrow')),
                'title' => self::stringValue(data_get($config, 'process.title')),
                'description' => self::stringValue(data_get($config, 'process.description')),
                'cards' => collect(is_array(data_get($config, 'process.cards')) ? array_values(data_get($config, 'process.cards')) : [])
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn (array $item) => [
                        'uuid' => self::uuidValue(data_get($item, 'uuid')),
                        'title' => self::stringValue(data_get($item, 'title')),
                        'description' => self::stringValue(data_get($item, 'description')),
                    ])->values()->all(),
            ],
            'values' => [
                'eyebrow' => self::stringValue(data_get($config, 'values.eyebrow')),
                'title' => self::stringValue(data_get($config, 'values.title')),
                'description' => self::stringValue(data_get($config, 'values.description')),
                'cards' => collect(is_array(data_get($config, 'values.cards')) ? array_values(data_get($config, 'values.cards')) : [])
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn (array $item) => [
                        'uuid' => self::uuidValue(data_get($item, 'uuid')),
                        'title' => self::stringValue(data_get($item, 'title')),
                        'role' => self::stringValue(data_get($item, 'role')),
                        'text' => self::stringValue(data_get($item, 'text')),
                        'image_alt' => self::stringValue(data_get($item, 'image_alt')),
                    ])->values()->all(),
            ],
            'insights' => [
                'eyebrow' => self::stringValue(data_get($config, 'insights.eyebrow')),
                'title' => self::stringValue(data_get($config, 'insights.title')),
                'description' => self::stringValue(data_get($config, 'insights.description')),
                'cta_label' => self::stringValue(data_get($config, 'insights.cta_label')),
                'cta_url' => self::stringValue(data_get($config, 'insights.cta_url')),
                'items' => collect(is_array(data_get($config, 'insights.items')) ? array_values(data_get($config, 'insights.items')) : [])
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn (array $item) => [
                        'uuid' => self::uuidValue(data_get($item, 'uuid')),
                        'blog_ref' => self::stringValue(data_get($item, 'blog_ref')),
                    ])->values()->all(),
            ],
            'final_cta' => [
                'title' => self::stringValue(data_get($config, 'final_cta.title')),
                'description' => self::stringValue(data_get($config, 'final_cta.description')),
                'primary_label' => self::stringValue(data_get($config, 'final_cta.primary_label')),
                'secondary_label' => self::stringValue(data_get($config, 'final_cta.secondary_label')),
                'secondary_url' => self::stringValue(data_get($config, 'final_cta.secondary_url')),
            ],
            'consultation' => [
                'eyebrow' => self::stringValue(data_get($config, 'consultation.eyebrow')),
                'title' => self::stringValue(data_get($config, 'consultation.title')),
                'description' => self::stringValue(data_get($config, 'consultation.description')),
                'button_label' => self::stringValue(data_get($config, 'consultation.button_label')),
                'success_message' => self::stringValue(data_get($config, 'consultation.success_message')),
            ],
        ];
    }

    public static function normalizeConfig(array $config): array
    {
        return self::prepareConfig($config);
    }

    public static function extractReferenceId(?string $value): ?int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/\d+/', $value, $matches) !== 1) {
            return null;
        }

        $id = (int) $matches[0];

        return $id > 0 ? $id : null;
    }

    public static function galleryCollection(string $uuid): string
    {
        return 'home-gallery-'.$uuid;
    }

    public static function heroSlideCollection(string $uuid): string
    {
        return 'home-hero-'.$uuid;
    }

    public static function packageShowcaseBackgroundCollection(): string
    {
        return 'home-package-showcase-background';
    }

    public static function valueCardCollection(string $uuid): string
    {
        return 'home-value-card-'.$uuid;
    }

    protected static function galleryItem(string $title, string $subtitle): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'title' => $title,
            'subtitle' => $subtitle,
            'url' => '',
            'image_alt' => $title,
        ];
    }

    protected static function heroSlide(
        string $eyebrow,
        string $title,
        string $description,
        string $primaryLabel = 'Nhận báo giá',
        string $primaryUrl = '#consultation',
        string $secondaryLabel = 'Xem dự án',
        string $secondaryUrl = '/du-an',
    ): array {
        return [
            'uuid' => (string) Str::uuid(),
            'eyebrow' => $eyebrow,
            'title' => $title,
            'description' => $description,
            'primary_label' => $primaryLabel,
            'primary_url' => $primaryUrl,
            'secondary_label' => $secondaryLabel,
            'secondary_url' => $secondaryUrl,
            'image_alt' => $title,
        ];
    }

    protected static function insightItem(string $blogRef): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'blog_ref' => $blogRef,
        ];
    }

    protected static function integerValue(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $resolved = (int) $value;

        return $resolved > 0 ? $resolved : null;
    }

    protected static function processCard(string $title, string $description): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'title' => $title,
            'description' => $description,
        ];
    }

    protected static function packageShowcaseItem(
        string $tone,
        string $badge,
        string $title,
        string $description,
        string $url,
        string $linkLabel = 'Xem chi tiết',
    ): array {
        return [
            'uuid' => (string) Str::uuid(),
            'tone' => self::packageTone($tone),
            'badge' => $badge,
            'title' => $title,
            'description' => $description,
            'link_label' => $linkLabel,
            'url' => $url,
        ];
    }

    protected static function packageTone(mixed $value): string
    {
        $tone = trim((string) $value);

        return in_array($tone, ['standard', 'premium', 'compare'], true) ? $tone : 'standard';
    }

    protected static function serviceItem(string $icon, string $title, string $description): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'icon' => $icon,
            'title' => $title,
            'description' => $description,
            'service_id' => null,
            'link_label' => 'Xem chi tiết',
            'url' => '',
        ];
    }

    protected static function statItem(string $value, string $label): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'value' => $value,
            'label' => $label,
        ];
    }

    protected static function stringValue(mixed $value): string
    {
        return trim((string) $value);
    }

    protected static function uuidValue(mixed $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : (string) Str::uuid();
    }

    protected static function valueCard(string $title, string $role, string $text, string $imageAlt = ''): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'title' => $title,
            'role' => $role,
            'text' => $text,
            'image_alt' => $imageAlt !== '' ? $imageAlt : $title,
        ];
    }
}
