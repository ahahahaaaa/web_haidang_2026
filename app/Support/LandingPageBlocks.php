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

    public const TYPE_GEO_ANSWER = 'geo_answer';

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

    public const TYPE_VOUCHER_PROMOTION = 'voucher_promotion';

    public const TYPE_VOUCHER_PROMOTION_PREMIUM = 'voucher_promotion_premium';

    public const HOME_POSITION_DEFAULT = 'default';

    public const HOME_POSITION_AFTER_HERO = 'after_hero';

    public const HOME_POSITION_BEFORE_SEARCH = 'before_search';

    public const HOME_POSITION_BEFORE_GEO_ANSWER = 'before_geo_answer';

    public const HOME_POSITION_BEFORE_TOPIC_RAIL = 'before_topic_rail';

    public const HOME_POSITION_BEFORE_FEATURED_TOURS = 'before_featured_tours';

    public const HOME_POSITION_BEFORE_TOUR_TAXONOMY_TABS = 'before_tour_taxonomy_tabs';

    public const HOME_POSITION_BEFORE_REGION_TAXONOMY_TABS = 'before_region_taxonomy_tabs';

    public const HOME_POSITION_BEFORE_DESTINATION_SLIDER = 'before_destination_slider';

    public const HOME_POSITION_BEFORE_GALLERY = 'before_gallery';

    public const HOME_POSITION_BEFORE_SERVICES = 'before_services';

    public const HOME_POSITION_BEFORE_TRUST = 'before_trust';

    public const HOME_POSITION_BEFORE_PROCESS = 'before_process';

    public const HOME_POSITION_BEFORE_BLOG_PREVIEW = 'before_blog_preview';

    public const HOME_POSITION_BEFORE_FAQ = 'before_faq';

    public const HOME_POSITION_BEFORE_CTA = 'before_cta';

    public const HOME_POSITION_AFTER_CTA = 'after_cta';

    public const VOUCHER_PROMOTION_VARIANT_CLASSIC = 'classic';

    public const VOUCHER_PROMOTION_VARIANT_PREMIUM = 'premium';

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
            self::TYPE_GEO_ANSWER => 'GEO answer',
            self::TYPE_HTML_WIDGET => 'HTML widget',
            self::TYPE_VOUCHER_PROMOTION => 'Widget voucher promotion',
            self::TYPE_VOUCHER_PROMOTION_PREMIUM => 'Widget voucher promotion premium',
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
    public static function voucherPromotionVariants(): array
    {
        return [
            self::VOUCHER_PROMOTION_VARIANT_CLASSIC => 'Classic promotion',
            self::VOUCHER_PROMOTION_VARIANT_PREMIUM => 'Premium promotion card',
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
            'danh-gia-tour',
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

    /**
     * @return array<string, string>
     */
    public static function homePositionOptions(): array
    {
        return [
            self::HOME_POSITION_DEFAULT => 'Theo vị trí mặc định',
            self::HOME_POSITION_AFTER_HERO => 'Sau hero',
            self::HOME_POSITION_BEFORE_SEARCH => 'Trước thanh tìm kiếm',
            self::HOME_POSITION_BEFORE_GEO_ANSWER => 'Trước GEO / AI Search',
            self::HOME_POSITION_BEFORE_TOPIC_RAIL => 'Trước Chủ đề tour',
            self::HOME_POSITION_BEFORE_FEATURED_TOURS => 'Trước Tour nổi bật',
            self::HOME_POSITION_BEFORE_TOUR_TAXONOMY_TABS => 'Trước tab tour theo taxonomy',
            self::HOME_POSITION_BEFORE_REGION_TAXONOMY_TABS => 'Trước tab vùng miền',
            self::HOME_POSITION_BEFORE_DESTINATION_SLIDER => 'Trước Điểm đến nổi bật',
            self::HOME_POSITION_BEFORE_GALLERY => 'Trước gallery landing',
            self::HOME_POSITION_BEFORE_SERVICES => 'Trước Dịch vụ hỗ trợ',
            self::HOME_POSITION_BEFORE_TRUST => 'Trước trust proof',
            self::HOME_POSITION_BEFORE_PROCESS => 'Trước quy trình tư vấn',
            self::HOME_POSITION_BEFORE_BLOG_PREVIEW => 'Trước blog preview',
            self::HOME_POSITION_BEFORE_FAQ => 'Trước FAQ',
            self::HOME_POSITION_BEFORE_CTA => 'Trước CTA cuối trang',
            self::HOME_POSITION_AFTER_CTA => 'Sau CTA cuối trang',
        ];
    }

    public static function homePositionDefaultForType(string $type): string
    {
        return match ($type) {
            self::TYPE_BLOG_LIST => self::HOME_POSITION_BEFORE_BLOG_PREVIEW,
            self::TYPE_CTA => self::HOME_POSITION_BEFORE_CTA,
            self::TYPE_FAQ => self::HOME_POSITION_BEFORE_FAQ,
            self::TYPE_GALLERY_MEDIA, self::TYPE_GALLERY_SLIDER => self::HOME_POSITION_BEFORE_GALLERY,
            self::TYPE_GEO_ANSWER => self::HOME_POSITION_BEFORE_GEO_ANSWER,
            self::TYPE_HTML_WIDGET => self::HOME_POSITION_BEFORE_FEATURED_TOURS,
            self::TYPE_REGION_RAIL => self::HOME_POSITION_BEFORE_DESTINATION_SLIDER,
            self::TYPE_REGION_TAXONOMY_TABS => self::HOME_POSITION_BEFORE_REGION_TAXONOMY_TABS,
            self::TYPE_RICH_TEXT,
            self::TYPE_TRUST_PROOF,
            self::TYPE_VOUCHER_PROMOTION,
            self::TYPE_VOUCHER_PROMOTION_PREMIUM => self::HOME_POSITION_BEFORE_TRUST,
            self::TYPE_TOPIC_RAIL => self::HOME_POSITION_BEFORE_TOPIC_RAIL,
            self::TYPE_TOUR_LIST => self::HOME_POSITION_BEFORE_FEATURED_TOURS,
            self::TYPE_TOUR_TAXONOMY_TABS => self::HOME_POSITION_BEFORE_TOUR_TAXONOMY_TABS,
            default => self::HOME_POSITION_DEFAULT,
        };
    }

    public static function homePositionForBlock(array $block): string
    {
        $type = (string) ($block['type'] ?? '');
        $position = trim((string) ($block['home_position'] ?? ''));

        if ($position === '' || $position === self::HOME_POSITION_DEFAULT) {
            return self::homePositionDefaultForType($type);
        }

        return array_key_exists($position, self::homePositionOptions())
            ? $position
            : self::homePositionDefaultForType($type);
    }

    public static function defaultBlock(string $type): array
    {
        $block = match ($type) {
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
            self::TYPE_GEO_ANSWER => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'title' => '',
                'answer_summary' => '',
                'decision_notes' => [],
            ],
            self::TYPE_HTML_WIDGET => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'html' => '',
            ],
            self::TYPE_VOUCHER_PROMOTION, self::TYPE_VOUCHER_PROMOTION_PREMIUM => [
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'is_enabled' => true,
                'is_hero' => true,
                'variant' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM
                    ? self::VOUCHER_PROMOTION_VARIANT_PREMIUM
                    : self::VOUCHER_PROMOTION_VARIANT_CLASSIC,
                'badge_label' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM
                    ? 'Nhận voucher miễn phí 200.000đ'
                    : 'Giới hạn - nhận ngay',
                'badge_icon' => 'fa-solid fa-gift',
                'tag_label' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM ? 'Voucher đặc quyền' : 'Promotion',
                'kicker' => 'Voucher du lịch',
                'title_prefix' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM ? 'Áp dụng tất cả tour' : 'Nhận voucher',
                'title_highlight' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM ? '' : '200.000đ',
                'title_suffix' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM
                    ? 'trong & ngoài nước'
                    : '+ quà tặng bất ngờ',
                'description' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM
                    ? 'Hải Đăng Travel sẽ ghi nhận lead trong CMS Travel Inquiries và liên hệ để tư vấn cách dùng voucher theo nhu cầu tour, dịch vụ hoặc lịch trình đoàn.'
                    : 'Đăng ký nhanh để đội ngũ tư vấn giữ ưu đãi và gợi ý tour hợp gu cho chuyến đi tiếp theo.',
                'benefits' => [
                    self::defaultVoucherPromotionBenefit($type === self::TYPE_VOUCHER_PROMOTION_PREMIUM ? 'Voucher trừ trực tiếp 200.000đ vào hóa đơn tour' : 'Voucher ưu đãi 200.000đ', 'fa-solid fa-ticket'),
                    self::defaultVoucherPromotionBenefit($type === self::TYPE_VOUCHER_PROMOTION_PREMIUM ? 'Tư vấn lộ trình riêng, thiết kế theo sở thích' : 'Quà tặng theo chương trình', 'fa-solid fa-gift'),
                    self::defaultVoucherPromotionBenefit($type === self::TYPE_VOUCHER_PROMOTION_PREMIUM ? 'Hỗ trợ visa, vé máy bay, khách sạn chuẩn 5 sao' : 'Gợi ý tour theo lịch trình', 'fa-solid fa-route'),
                    self::defaultVoucherPromotionBenefit($type === self::TYPE_VOUCHER_PROMOTION_PREMIUM ? 'Tặng kèm quà tặng du lịch khi đặt tour' : 'Tư vấn miễn phí qua điện thoại', 'fa-solid fa-headset'),
                ],
                'offer_label' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM
                    ? 'Miễn phí - Cho mọi tour trong & ngoài nước'
                    : 'Ưu đãi trong tháng',
                'offer_code' => 'HDTRAVEL200',
                'offer_note' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM
                    ? 'Áp dụng toàn bộ tour: Thái Lan, Nhật Bản, Pháp, Đà Nẵng, Phú Quốc...'
                    : 'Điều kiện áp dụng sẽ được tư vấn viên xác nhận khi liên hệ.',
                'show_countdown' => true,
                'countdown_label' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM ? 'Chương trình kết thúc sau' : 'Thời gian còn lại',
                'countdown_expired_label' => 'Chương trình voucher đã kết thúc',
                'panel_eyebrow' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM ? 'Đăng ký trong 1 phút' : 'Nhanh hơn trên mobile',
                'panel_title' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM
                    ? 'Nhận voucher nhanh, tư vấn tour đúng gu.'
                    : 'Chạm để nhận voucher, còn lại để Hải Đăng Travel lo.',
                'panel_description' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM
                    ? 'Để lại số điện thoại, Hải Đăng Travel sẽ liên hệ và gợi ý tour phù hợp với lịch trình, ngân sách và nhu cầu của bạn.'
                    : 'Phù hợp cho khách trẻ đang lướt tour, chưa chốt điểm đến nhưng muốn giữ ưu đãi trước khi trao đổi với tư vấn viên.',
                'steps' => [
                    self::defaultVoucherPromotionStep($type === self::TYPE_VOUCHER_PROMOTION_PREMIUM ? 'Liên hệ chuyên viên' : 'Để lại số điện thoại', $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM ? 'Hotline / Zalo / Website - Đội ngũ tư vấn sẵn sàng hỗ trợ.' : 'Lead ghi nhận trực tiếp về CMS.'),
                    self::defaultVoucherPromotionStep('Chia sẻ gu chuyến đi', 'Biển, núi, team building, nghỉ dưỡng hoặc tour nước ngoài.'),
                    self::defaultVoucherPromotionStep($type === self::TYPE_VOUCHER_PROMOTION_PREMIUM ? 'Nhận voucher & gợi ý' : 'Nhận gợi ý phù hợp', $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM ? 'Tư vấn viên gửi voucher 200.000đ và đề xuất tour phù hợp nhất.' : 'Tư vấn viên liên hệ và giữ voucher theo chương trình.'),
                ],
                'primary_label' => 'Nhận voucher ngay',
                'secondary_label' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM ? 'Xem tour phù hợp' : 'Xem tour gợi ý',
                'secondary_url' => '/tour-trong-nuoc',
                'trust_note' => $type === self::TYPE_VOUCHER_PROMOTION_PREMIUM
                    ? 'Không bắt buộc đặt tour. Tư vấn viên chỉ liên hệ khi bạn để lại thông tin.'
                    : '',
                'voucher_campaign_slug' => 'voucher-du-lich-200k',
                'inquiry_context' => 'Nhận voucher du lịch 200.000đ',
                'inquiry_subject' => 'Đăng ký nhận voucher du lịch 200.000đ',
                'modal_title' => 'Nhận voucher du lịch',
                'modal_description' => 'Để lại thông tin để Hải Đăng Travel giữ voucher và tư vấn tour phù hợp với nhu cầu của bạn.',
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

        $block['home_position'] = self::homePositionDefaultForType((string) ($block['type'] ?? $type));

        return $block;
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
                $normalized['home_position'] = self::homePositionForBlock($normalized);

                if ($type === self::TYPE_GALLERY_MEDIA) {
                    $normalized['variant'] = array_key_exists((string) ($normalized['variant'] ?? ''), self::galleryVariants())
                        ? (string) $normalized['variant']
                        : self::GALLERY_VARIANT_STANDARD;
                    $normalized['items'] = self::normalizeGalleryItems($normalized['items'] ?? []);
                }

                if ($type === self::TYPE_FAQ) {
                    $normalized['items'] = FaqContent::prepareItems($normalized['items'] ?? [], [FaqContent::blankItem()]);
                }

                if ($type === self::TYPE_GEO_ANSWER) {
                    $normalized['title'] = trim((string) ($normalized['title'] ?? ''));
                    $normalized['answer_summary'] = GeoContent::plainText($normalized['answer_summary'] ?? '', GeoContent::MAX_SUMMARY_LENGTH);
                    $normalized['decision_notes'] = GeoContent::normalizeDecisionNotes($normalized['decision_notes'] ?? []);
                }

                if ($type === self::TYPE_TRUST_PROOF) {
                    $normalized['cards'] = self::normalizeTrustProofCards(is_array($normalized['cards'] ?? null) ? $normalized['cards'] : []);
                    $normalized['stats'] = self::normalizeTrustProofStats(is_array($normalized['stats'] ?? null) ? $normalized['stats'] : []);
                }

                if (in_array($type, [self::TYPE_VOUCHER_PROMOTION, self::TYPE_VOUCHER_PROMOTION_PREMIUM], true)) {
                    $normalized['is_hero'] = (bool) ($normalized['is_hero'] ?? true);
                    $normalized['show_countdown'] = (bool) ($normalized['show_countdown'] ?? true);
                    $normalized['variant'] = array_key_exists((string) ($normalized['variant'] ?? ''), self::voucherPromotionVariants())
                        ? (string) $normalized['variant']
                        : ($type === self::TYPE_VOUCHER_PROMOTION_PREMIUM
                            ? self::VOUCHER_PROMOTION_VARIANT_PREMIUM
                            : self::VOUCHER_PROMOTION_VARIANT_CLASSIC);
                    $normalized['benefits'] = self::normalizeVoucherPromotionBenefits(is_array($normalized['benefits'] ?? null) ? $normalized['benefits'] : []);
                    $normalized['steps'] = self::normalizeVoucherPromotionSteps(is_array($normalized['steps'] ?? null) ? $normalized['steps'] : []);
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

    public static function defaultVoucherPromotionBenefit(string $text = '', string $icon = 'fa-solid fa-circle-check'): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'icon' => trim($icon),
            'text' => trim($text),
        ];
    }

    public static function defaultVoucherPromotionStep(string $title = '', string $text = ''): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'title' => trim($title),
            'text' => trim($text),
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
    public static function normalizeVoucherPromotionBenefits(array $benefits): array
    {
        return collect($benefits)
            ->filter(fn ($benefit) => is_array($benefit))
            ->map(fn (array $benefit) => array_replace(self::defaultVoucherPromotionBenefit(), [
                'uuid' => filled($benefit['uuid'] ?? null) ? (string) $benefit['uuid'] : (string) Str::uuid(),
                'icon' => trim((string) ($benefit['icon'] ?? '')),
                'text' => trim((string) ($benefit['text'] ?? '')),
            ]))
            ->whenEmpty(fn (Collection $collection) => $collection->push(self::defaultVoucherPromotionBenefit()))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function normalizeVoucherPromotionSteps(array $steps): array
    {
        return collect($steps)
            ->filter(fn ($step) => is_array($step))
            ->map(fn (array $step) => array_replace(self::defaultVoucherPromotionStep(), [
                'uuid' => filled($step['uuid'] ?? null) ? (string) $step['uuid'] : (string) Str::uuid(),
                'title' => trim((string) ($step['title'] ?? '')),
                'text' => trim((string) ($step['text'] ?? '')),
            ]))
            ->whenEmpty(fn (Collection $collection) => $collection->push(self::defaultVoucherPromotionStep()))
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
            ->first(fn (array $block) => in_array($block['type'] ?? null, [
                self::TYPE_HERO_SLIDER,
                self::TYPE_HERO_MEDIA,
                self::TYPE_HERO_DEMO_LANDINGPAGE,
                self::TYPE_VOUCHER_PROMOTION,
                self::TYPE_VOUCHER_PROMOTION_PREMIUM,
            ], true)) ?? [];

        return [
            'hero_badge' => trim((string) ($hero['eyebrow'] ?? $hero['badge_label'] ?? '')),
            'hero_title' => trim(implode(' ', array_filter([
                (string) ($hero['title'] ?? $hero['title_prefix'] ?? ''),
                (string) ($hero['title_highlight'] ?? ''),
                (string) ($hero['title_suffix'] ?? ''),
            ]))),
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
