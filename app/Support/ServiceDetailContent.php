<?php

namespace App\Support;

use Illuminate\Support\Str;

class ServiceDetailContent
{
    public static function defaultLandingConfig(): array
    {
        return [
            'partners' => [
                'eyebrow' => 'DOI TAC CHIEN LUOC',
                'title' => 'Đối tác chiến lược',
                'description' => 'Hệ sinh thái đối tác vật tư, thiết bị và triển khai giúp công trình được vận hành ổn định từ thiết kế đến bàn giao.',
                'items' => [
                    self::partnerItem('AVT ABC', 'Đối tác vật liệu hoàn thiện'),
                    self::partnerItem('HPY LEAD', 'Đối tác kết cấu và cơ điện'),
                    self::partnerItem('CEMTCO', 'Đối tác bê tông và nền móng'),
                    self::partnerItem('SAFE LAB', 'Đối tác kiểm định chất lượng'),
                    self::partnerItem('BDG 002', 'Đối tác thiết bị công trình'),
                ],
            ],
        ];
    }

    public static function defaultServiceConfig(?string $serviceTitle = null, ?string $excerpt = null, ?string $content = null, ?string $priceNote = null): array
    {
        $serviceTitle = trim((string) $serviceTitle);
        $excerpt = trim((string) $excerpt);
        $content = trim(strip_tags((string) $content));
        $priceNote = trim((string) $priceNote);

        return [
            'hero_slides' => [
                [
                    'uuid' => (string) Str::uuid(),
                    'eyebrow' => 'XAY DUNG VA NOI THAT',
                    'title' => $serviceTitle !== '' ? $serviceTitle : 'Thi công Biệt thự Trọn gói',
                    'description' => $excerpt !== '' ? $excerpt : 'Giải pháp triển khai đồng bộ từ kiến trúc, kết cấu, hoàn thiện đến kiểm soát tiến độ ngay trên công trình.',
                    'primary_label' => 'Liên hệ ngay',
                    'secondary_label' => 'Gọi tư vấn',
                    'image_alt' => $serviceTitle !== '' ? $serviceTitle : 'Service hero',
                ],
            ],
            'feature_blocks' => [
                [
                    'uuid' => (string) Str::uuid(),
                    'eyebrow' => 'Giải pháp kiến trúc',
                    'title' => 'Độc bản & Cân bằng',
                    'description' => $content !== '' ? Str::limit($content, 220) : 'Chúng tôi phát triển phương án bám sát nhu cầu sinh hoạt, gu thẩm mỹ và điều kiện thi công thực tế của từng khu đất.',
                    'highlights' => [
                        'Thiết kế theo bối cảnh công trình',
                        'Đồng bộ concept, vật liệu, kỹ thuật',
                        'Kiểm soát rủi ro ngay từ giai đoạn đầu',
                    ],
                    'image_alt' => 'Giải pháp kiến trúc',
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'eyebrow' => 'Thi công chính xác',
                    'title' => 'Tiêu chuẩn kết cấu',
                    'description' => 'Từng hạng mục được bám theo checklist chất lượng, mốc tiến độ và kịch bản xử lý hiện trường để giảm phát sinh khi triển khai.',
                    'highlights' => [
                        'Báo cáo hiện trường theo mốc rõ ràng',
                        'Tổ chức thi công an toàn, đúng quy trình',
                        'Phối hợp giữa đội kỹ thuật và đội hoàn thiện',
                    ],
                    'image_alt' => 'Thi công chính xác',
                ],
            ],
            'process' => [
                'eyebrow' => 'QUY TRINH',
                'title' => 'Quy trình thực hiện',
                'description' => 'Quy trình 5 bước giúp chủ đầu tư nhìn rõ phạm vi, nguồn lực và các mốc kiểm soát chất lượng trước khi bắt đầu.',
                'cards' => [
                    self::processCard('Tư vấn & đề xuất', 'Tiếp nhận mục tiêu, ngân sách và điều kiện pháp lý của công trình.', 'fa-solid fa-pencil-ruler'),
                    self::processCard('Phân tích hiện trạng', 'Khảo sát hiện trường, kết cấu và các điều kiện thi công quan trọng.', 'fa-solid fa-file-lines'),
                    self::processCard('Giải pháp & báo giá', 'Đề xuất phương án triển khai, vật liệu và mức đầu tư tham khảo.', 'fa-solid fa-house'),
                    self::processCard('Triển khai & giám sát', 'Tổ chức thi công, quản lý tiến độ và báo cáo công trường định kỳ.', 'fa-solid fa-paint-roller'),
                    self::processCard('Nghiệm thu & bàn giao', 'Hoàn thiện checklist chất lượng, bàn giao và hướng dẫn vận hành.', 'fa-solid fa-key'),
                ],
            ],
            'pricing' => [
                'eyebrow' => 'BAO GIA THAM KHAO',
                'title' => 'Báo giá tham khảo',
                'description' => $priceNote !== '' ? $priceNote : 'Đơn giá tham khảo thay đổi theo quy mô, mức độ hoàn thiện, vật tư và điều kiện triển khai thực tế.',
                'columns' => [
                    'label' => 'Hạng mục',
                    'size' => 'Quy mô',
                    'price' => 'Mức đầu tư',
                    'note' => 'Ghi chú',
                ],
                'rows' => [
                    self::pricingRow('Biệt thự mini', '85 - 120 m2', '4.8 - 5.7 triệu/m2', 'Phù hợp gói hoàn thiện tiêu chuẩn'),
                    self::pricingRow('Biệt thự phố', '120 - 220 m2', '5.8 - 6.9 triệu/m2', 'Bao gồm phối hợp nội thất cơ bản'),
                    self::pricingRow('Luxury Villa', '220 m2 trở lên', 'Liên hệ tư vấn', 'Tối ưu theo vật liệu và công nghệ riêng'),
                ],
                'footnote' => 'Bảng giá chưa bao gồm các điều chỉnh đặc thù theo kết cấu hiện trạng hoặc vật liệu nhập khẩu.',
            ],
        ];
    }

    public static function prepareLandingConfig(?array $config): array
    {
        if (! is_array($config) || $config === []) {
            return self::defaultLandingConfig();
        }

        $partners = data_get($config, 'partners.items');

        return [
            'partners' => [
                'eyebrow' => self::stringValue(data_get($config, 'partners.eyebrow')),
                'title' => self::stringValue(data_get($config, 'partners.title')),
                'description' => self::stringValue(data_get($config, 'partners.description')),
                'items' => collect(is_array($partners) ? array_values($partners) : [])
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn (array $item) => [
                        'uuid' => self::uuidValue(data_get($item, 'uuid')),
                        'name' => self::stringValue(data_get($item, 'name')),
                        'description' => self::stringValue(data_get($item, 'description')),
                        'image_alt' => self::stringValue(data_get($item, 'image_alt')),
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }

    public static function prepareServiceConfig(?array $config, ?string $serviceTitle = null, ?string $excerpt = null, ?string $content = null, ?string $priceNote = null): array
    {
        if (! is_array($config) || $config === []) {
            return self::defaultServiceConfig($serviceTitle, $excerpt, $content, $priceNote);
        }

        return [
            'hero_slides' => collect(is_array($config['hero_slides'] ?? null) ? array_values($config['hero_slides']) : [])
                ->filter(fn ($item) => is_array($item))
                ->map(fn (array $item) => [
                    'uuid' => self::uuidValue(data_get($item, 'uuid')),
                    'eyebrow' => self::stringValue(data_get($item, 'eyebrow')),
                    'title' => RichText::sanitizeInline(data_get($item, 'title')),
                    'description' => self::stringValue(data_get($item, 'description')),
                    'primary_label' => self::stringValue(data_get($item, 'primary_label')),
                    'secondary_label' => self::stringValue(data_get($item, 'secondary_label')),
                    'image_alt' => self::stringValue(data_get($item, 'image_alt')),
                ])
                ->values()
                ->all(),
            'feature_blocks' => collect(is_array($config['feature_blocks'] ?? null) ? array_values($config['feature_blocks']) : [])
                ->filter(fn ($item) => is_array($item))
                ->map(fn (array $item) => [
                    'uuid' => self::uuidValue(data_get($item, 'uuid')),
                    'eyebrow' => self::stringValue(data_get($item, 'eyebrow')),
                    'title' => self::stringValue(data_get($item, 'title')),
                    'description' => self::stringValue(data_get($item, 'description')),
                    'highlights' => collect(is_array($item['highlights'] ?? null) ? array_values($item['highlights']) : [])
                        ->map(fn ($highlight) => self::stringValue($highlight))
                        ->values()
                        ->all(),
                    'image_alt' => self::stringValue(data_get($item, 'image_alt')),
                ])
                ->values()
                ->all(),
            'process' => [
                'eyebrow' => self::stringValue(data_get($config, 'process.eyebrow')),
                'title' => self::stringValue(data_get($config, 'process.title')),
                'description' => self::stringValue(data_get($config, 'process.description')),
                'cards' => collect(is_array(data_get($config, 'process.cards')) ? array_values(data_get($config, 'process.cards')) : [])
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn (array $item) => [
                        'icon_class' => self::stringValue(data_get($item, 'icon_class')),
                        'title' => self::stringValue(data_get($item, 'title')),
                        'description' => self::stringValue(data_get($item, 'description')),
                    ])
                    ->values()
                    ->all(),
            ],
            'pricing' => [
                'eyebrow' => self::stringValue(data_get($config, 'pricing.eyebrow')),
                'title' => self::stringValue(data_get($config, 'pricing.title')),
                'description' => self::stringValue(data_get($config, 'pricing.description')),
                'columns' => [
                    'label' => self::stringValue(data_get($config, 'pricing.columns.label')),
                    'size' => self::stringValue(data_get($config, 'pricing.columns.size')),
                    'price' => self::stringValue(data_get($config, 'pricing.columns.price')),
                    'note' => self::stringValue(data_get($config, 'pricing.columns.note')),
                ],
                'rows' => collect(is_array(data_get($config, 'pricing.rows')) ? array_values(data_get($config, 'pricing.rows')) : [])
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn (array $item) => [
                        'label' => self::stringValue(data_get($item, 'label')),
                        'size' => self::stringValue(data_get($item, 'size')),
                        'price' => self::stringValue(data_get($item, 'price')),
                        'note' => self::stringValue(data_get($item, 'note')),
                    ])
                    ->values()
                    ->all(),
                'footnote' => self::stringValue(data_get($config, 'pricing.footnote')),
            ],
        ];
    }

    public static function normalizeLandingConfig(array $config): array
    {
        return self::prepareLandingConfig($config);
    }

    public static function normalizeServiceConfig(array $config): array
    {
        return self::prepareServiceConfig($config);
    }

    public static function featureBlockCollection(string $uuid): string
    {
        return 'service-feature-'.$uuid;
    }

    public static function heroSlideCollection(string $uuid): string
    {
        return 'service-hero-'.$uuid;
    }

    public static function partnerCollection(string $uuid): string
    {
        return 'service-partner-'.$uuid;
    }

    protected static function partnerItem(string $name, string $description): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'description' => $description,
            'image_alt' => $name,
        ];
    }

    protected static function pricingRow(string $label, string $size, string $price, string $note): array
    {
        return [
            'label' => $label,
            'size' => $size,
            'price' => $price,
            'note' => $note,
        ];
    }

    protected static function processCard(string $title, string $description, string $iconClass = ''): array
    {
        return [
            'icon_class' => $iconClass,
            'title' => $title,
            'description' => $description,
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
}
