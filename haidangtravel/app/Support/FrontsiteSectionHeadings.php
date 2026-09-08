<?php

namespace App\Support;

class FrontsiteSectionHeadings
{
    public const STRUCTURED_DATA_KEY = 'frontsite_section_headings';

    public static function definitions(): array
    {
        return [
            'common' => [
                'label' => 'Dùng chung',
                'items' => [
                    'footer_support' => [
                        'label' => 'Footer - Kênh hỗ trợ',
                        'title' => 'Kênh hỗ trợ',
                    ],
                    'contact_map' => [
                        'label' => 'Liên hệ - Google Maps',
                        'title' => 'Google Maps',
                        'description' => 'Bản đồ được lấy từ cấu hình trong theme settings để khách hàng xem nhanh vị trí liên hệ.',
                    ],
                    'home_faq' => [
                        'label' => 'Trang chủ - FAQ',
                        'title' => 'Câu hỏi thường gặp',
                    ],
                ],
            ],
            'tour_listing' => [
                'label' => 'Trang danh sách tour',
                'items' => [
                    'tour_listing_reviews' => [
                        'label' => 'Đánh giá danh sách tour',
                        'title' => 'Đánh giá nổi bật về {title}',
                        'description' => 'Những nhận xét này được hiển thị trực tiếp trên trang để hỗ trợ quyết định trước khi gửi yêu cầu.',
                    ],
                    'tour_listing_faq' => [
                        'label' => 'FAQ danh sách tour',
                        'title' => 'Câu hỏi thường gặp về {title}',
                    ],
                    'tour_listing_cta' => [
                        'label' => 'CTA danh sách tour',
                        'title' => 'Gửi yêu cầu',
                        'description' => 'Cần đội ngũ tư vấn chốt nhanh theo ngày đi, ngân sách hoặc quy mô đoàn?',
                    ],
                ],
            ],
            'tour' => [
                'label' => 'Trang chi tiết tour',
                'items' => [
                    'tour_details' => [
                        'label' => 'Chi tiết tour',
                        'title' => 'Chi tiết tour',
                    ],
                    'tour_departures' => [
                        'label' => 'Lịch khởi hành',
                        'title' => 'Lịch khởi hành & giá theo tháng',
                        'description' => 'Tháng có ngày đi gần nhất được mở sẵn để bạn đối chiếu ngày khởi hành, tiêu chuẩn, giá và trạng thái còn chỗ trước khi giữ lịch.',
                    ],
                    'tour_itinerary' => [
                        'label' => 'Lịch trình',
                        'title' => 'Lịch trình chi tiết',
                    ],
                    'tour_pricing' => [
                        'label' => 'Phụ thu và ghi chú',
                        'title' => 'Phụ thu và ghi chú thêm',
                        'description' => 'Khối này chỉ giữ các khoản phụ thu hoặc ghi chú bổ sung để người xem nắm nhanh trước khi chọn lịch khởi hành phù hợp.',
                    ],
                    'tour_inclusions' => [
                        'label' => 'Tour bao gồm',
                        'title' => 'Tour bao gồm những gì',
                    ],
                    'tour_reviews' => [
                        'label' => 'Đánh giá tour',
                        'title' => 'Đánh giá nổi bật về hành trình',
                        'description' => 'Xem nhanh cảm nhận nổi bật về lịch trình, chất lượng dịch vụ và mức độ phù hợp của tour trước khi gửi yêu cầu.',
                    ],
                    'tour_faq' => [
                        'label' => 'FAQ tour',
                        'title' => 'Câu hỏi thường gặp',
                    ],
                    'tour_related' => [
                        'label' => 'Tour liên quan',
                        'title' => 'Tour liên quan',
                        'description' => 'Khối gợi ý này giữ mạch khám phá tiếp theo sau khi người xem đã đọc xong lịch trình, giá và FAQ của tour hiện tại.',
                    ],
                    'tour_social_share' => [
                        'label' => 'Chia sẻ tour',
                        'title' => 'Chia sẻ tour này',
                        'description' => 'Gửi nhanh lịch trình cho nhóm đi cùng.',
                    ],
                    'tour_cta' => [
                        'label' => 'CTA cuối trang tour',
                        'title' => 'Cần tư vấn nhanh cho tour này?',
                        'description' => 'Nếu bạn chưa chốt được ngày đi, chỉ cần để lại số điện thoại và nhu cầu, Hải Đăng Travel sẽ gọi lại để kiểm tra chỗ và tư vấn đúng lịch phù hợp.',
                    ],
                ],
            ],
            'service' => [
                'label' => 'Trang chi tiết dịch vụ',
                'items' => [
                    'service_related_questions' => [
                        'label' => 'Câu hỏi liên quan',
                        'title' => 'Những câu hỏi liên quan đến {title}',
                    ],
                    'service_highlights' => [
                        'label' => 'Điểm nổi bật',
                        'title' => 'Điểm nổi bật',
                        'description' => 'Phần tóm tắt này giúp người đọc và công cụ tìm kiếm hiểu nhanh giá trị chính của dịch vụ trước khi gửi yêu cầu tư vấn.',
                    ],
                    'service_faq' => [
                        'label' => 'FAQ dịch vụ',
                        'title' => 'Câu hỏi thường gặp',
                    ],
                    'service_inquiry' => [
                        'label' => 'Panel tư vấn dịch vụ',
                        'title' => 'Tư vấn dịch vụ',
                    ],
                    'service_related' => [
                        'label' => 'Dịch vụ liên quan',
                        'title' => 'Dịch vụ liên quan',
                    ],
                ],
            ],
            'blog' => [
                'label' => 'Trang chi tiết blog',
                'items' => [
                    'blog_toc' => [
                        'label' => 'Mục lục bài viết',
                        'title' => 'Mục lục bài viết',
                        'description' => 'Theo dõi nhanh các ý chính theo một trục đọc liền mạch.',
                    ],
                    'blog_social_share' => [
                        'label' => 'Chia sẻ bài viết',
                        'title' => 'Chia sẻ bài viết',
                        'description' => 'Gửi bài viết này cho bạn bè, nhóm du lịch hoặc lưu lại liên kết để đọc sau.',
                    ],
                    'blog_info' => [
                        'label' => 'Thông tin bài viết',
                        'title' => 'Thông tin bài viết',
                    ],
                    'blog_faq' => [
                        'label' => 'FAQ bài viết',
                        'title' => 'Câu hỏi thường gặp',
                    ],
                    'blog_related' => [
                        'label' => 'Bài viết liên quan',
                        'title' => 'Bài viết liên quan',
                    ],
                    'blog_cta' => [
                        'label' => 'CTA cuối trang blog',
                        'title' => 'Tư vấn nhanh',
                        'description' => 'Cần tư vấn theo chủ đề visa, điểm đến hoặc nội dung đang đọc để chốt hành trình nhanh hơn?',
                    ],
                    'blog_index_faq' => [
                        'label' => 'Blog danh mục - FAQ',
                        'title' => 'Câu hỏi thường gặp về {title}',
                    ],
                ],
            ],
        ];
    }

    public static function keys(): array
    {
        return collect(self::definitions())
            ->flatMap(fn (array $group): array => array_keys($group['items'] ?? []))
            ->values()
            ->all();
    }

    public static function prepare(mixed $stored): array
    {
        $stored = is_array($stored) ? $stored : [];

        return collect(self::definitions())
            ->flatMap(fn (array $group): array => $group['items'] ?? [])
            ->mapWithKeys(function (array $definition, string $key) use ($stored): array {
                $item = is_array(data_get($stored, $key)) ? data_get($stored, $key) : [];

                return [$key => [
                    'is_visible' => filter_var(data_get($item, 'is_visible', true), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true,
                    'title' => trim((string) data_get($item, 'title', $definition['title'] ?? '')),
                    'description' => trim((string) data_get($item, 'description', $definition['description'] ?? '')),
                ]];
            })
            ->all();
    }

    public static function resolve(mixed $stored, string $key, array $context = []): array
    {
        $prepared = self::prepare($stored);
        $heading = $prepared[$key] ?? [
            'is_visible' => true,
            'title' => '',
            'description' => '',
        ];

        return [
            'is_visible' => (bool) ($heading['is_visible'] ?? true),
            'title' => self::replacePlaceholders((string) ($heading['title'] ?? ''), $context),
            'description' => self::replacePlaceholders((string) ($heading['description'] ?? ''), $context),
        ];
    }

    protected static function replacePlaceholders(string $value, array $context): string
    {
        if ($value === '' || $context === []) {
            return $value;
        }

        $replacements = [];

        foreach ($context as $key => $replacement) {
            $replacements['{'.$key.'}'] = (string) $replacement;
        }

        return strtr($value, $replacements);
    }
}
