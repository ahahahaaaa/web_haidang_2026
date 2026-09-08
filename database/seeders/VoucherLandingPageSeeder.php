<?php

namespace Database\Seeders;

use App\Services\Travel\VoucherCampaignService;
use App\Support\LandingPageBlocks;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\VoucherCampaign;

class VoucherLandingPageSeeder extends Seeder
{
    private const SLUG = 'voucher-du-lich';

    private const CAMPAIGN_SLUG = 'voucher-du-lich-200k';

    private const USE_PREMIUM_WIDGET_VARIANT = true;

    public function run(VoucherCampaignService $vouchers): void
    {
        $landingPage = LandingPage::query()
            ->whereNull('page_key')
            ->where('slug', self::SLUG)
            ->first() ?? new LandingPage(['slug' => self::SLUG]);

        $landingPage->forceFill([
            'page_key' => null,
            'template_key' => 'generic',
            'editor_mode' => LandingPage::EDITOR_MODE_BLOCKS,
            'title' => 'Nhận voucher du lịch 200.000đ',
            'slug' => self::SLUG,
            'is_active' => true,
            'hero_badge' => 'Giới hạn - nhận ngay',
            'hero_title' => 'Nhận voucher 200.000đ và quà tặng bất ngờ cho chuyến đi sắp tới',
            'hero_excerpt' => 'Để lại nhu cầu du lịch, Hải Đăng Travel sẽ gửi voucher ưu đãi và gợi ý tour phù hợp với lịch đi, ngân sách và nhóm khách của bạn.',
            'intro_title' => 'Ưu đãi dành cho khách đang lên kế hoạch du lịch',
            'intro_excerpt' => 'Một form ngắn để đội ngũ tư vấn ghi nhận nhu cầu, gửi voucher và đề xuất hành trình phù hợp.',
            'body' => '<p>Landing voucher được thiết kế cho chiến dịch promotion ngắn hạn, ưu tiên CTA nhanh và thu lead về Travel Inquiries.</p>',
            'cta_title' => 'Nhận voucher trước khi chọn tour',
            'cta_excerpt' => 'Điền họ tên, số điện thoại và nhu cầu ngắn gọn. Lead sẽ được ghi nhận trong CMS Travel Inquiries để đội ngũ tư vấn xử lý.',
            'cta_primary_label' => 'Nhận voucher ngay',
            'cta_primary_url' => '#travel-inquiry-form',
            'cta_secondary_label' => 'Xem tour nổi bật',
            'cta_secondary_url' => '/tour-trong-nuoc',
            'meta_title' => 'Nhận voucher du lịch 200.000đ | Hải Đăng Travel',
            'meta_description' => 'Đăng ký nhận voucher du lịch 200.000đ và quà tặng bất ngờ từ Hải Đăng Travel. Để lại thông tin để được tư vấn tour phù hợp.',
            'og_title' => 'Nhận voucher du lịch 200.000đ',
            'og_description' => 'Voucher du lịch và quà tặng bất ngờ cho khách đang lên kế hoạch đi tour cùng Hải Đăng Travel.',
            'canonical_url' => null,
            'robots_directive' => 'index,follow',
            'schema' => null,
            'service_detail_config' => null,
            'home_config' => null,
            'visual_config' => null,
            'blocks' => $this->blocks(),
            'estimate_config' => null,
            'faq_items' => $this->faqItems(),
        ])->save();

        $campaign = VoucherCampaign::query()->updateOrCreate(
            ['slug' => self::CAMPAIGN_SLUG],
            [
                'landing_page_id' => $landingPage->getKey(),
                'title' => 'Voucher du lịch 200.000đ',
                'description' => 'Mã voucher được lưu trên trình duyệt này để bạn có thể xem lại sau khi đăng ký.',
                'frame_image_url' => '',
                'code_prefix' => 'HDTRAVEL200',
                'code_quantity' => 200,
                'code_set_version' => VoucherCampaign::query()->where('slug', self::CAMPAIGN_SLUG)->value('code_set_version') ?: (string) Str::uuid(),
                'starts_at' => now()->startOfDay(),
                'ends_at' => now()->addDays(45)->endOfDay(),
                'code_valid_until' => now()->addDays(60)->endOfDay(),
                'is_active' => true,
            ],
        );

        $vouchers->ensureGeneratedCodes($campaign, 200, 'HDTRAVEL200');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function blocks(): array
    {
        return [
            $this->voucherPromotionBlock(),
            [
                'uuid' => 'voucher-geo-suppressor',
                'type' => LandingPageBlocks::TYPE_GEO_ANSWER,
                'is_enabled' => false,
                'title' => '',
                'answer_summary' => '',
                'decision_notes' => [],
            ],
            [
                'uuid' => 'voucher-trust',
                'type' => LandingPageBlocks::TYPE_TRUST_PROOF,
                'is_enabled' => true,
                'title' => 'Vì sao nên nhận voucher qua Hải Đăng Travel?',
                'description' => 'Ưu đãi chỉ hữu ích khi đi kèm tư vấn đúng nhu cầu. Landing này gom nhanh thông tin để đội ngũ đề xuất tour và dịch vụ phù hợp hơn.',
                'cards' => [
                    [
                        'uuid' => 'voucher-trust-fast',
                        'highlight' => 'Tư vấn nhanh',
                        'title' => 'Một form là đủ để bắt đầu',
                        'text' => 'Thông tin được chuyển thẳng về CMS Travel Inquiries, giúp đội ngũ sale nhìn được nhu cầu và phản hồi đúng ngữ cảnh.',
                        'icon' => 'fa-solid fa-bolt',
                    ],
                    [
                        'uuid' => 'voucher-trust-clear',
                        'highlight' => 'Ưu đãi rõ ràng',
                        'title' => 'Voucher gắn với nhu cầu tour thật',
                        'text' => 'Khách để lại điểm đến, ngày đi hoặc số lượng khách để được gợi ý cách dùng voucher hợp lý.',
                        'icon' => 'fa-solid fa-ticket',
                    ],
                    [
                        'uuid' => 'voucher-trust-mobile',
                        'highlight' => 'Hợp khách trẻ',
                        'title' => 'CTA ngắn, hình ảnh vui và thao tác ít',
                        'text' => 'Luồng nội dung ưu tiên đăng ký nhanh trên mobile, sau đó tư vấn viên tiếp tục chăm sóc qua điện thoại.',
                        'icon' => 'fa-solid fa-mobile-screen-button',
                    ],
                ],
                'stats' => [],
            ],
            [
                'uuid' => 'voucher-featured-tours',
                'type' => LandingPageBlocks::TYPE_TOUR_LIST,
                'is_enabled' => true,
                'eyebrow' => '',
                'title' => 'Tour dễ dùng voucher',
                'description' => 'Gợi ý nhanh các tour đang được ưu tiên trên hệ thống. Danh sách lấy từ dữ liệu tour đã publish, không nhập tay trong landing.',
                'category_slug' => '',
                'destination_slug' => '',
                'region_slug' => '',
                'country_slug' => '',
                'scope' => '',
                'featured' => true,
                'limit' => 3,
                'sort' => 'featured',
            ],
            [
                'uuid' => 'voucher-faq',
                'type' => LandingPageBlocks::TYPE_FAQ,
                'is_enabled' => true,
                'title' => 'Câu hỏi nhanh về voucher',
                'description' => '',
                'items' => $this->faqItems(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function voucherPromotionBlock(): array
    {
        $block = LandingPageBlocks::defaultBlock(
            self::USE_PREMIUM_WIDGET_VARIANT
                ? LandingPageBlocks::TYPE_VOUCHER_PROMOTION_PREMIUM
                : LandingPageBlocks::TYPE_VOUCHER_PROMOTION,
        );

        return [
            ...$block,
            'uuid' => 'voucher-promotion-panel',
            'type' => LandingPageBlocks::TYPE_VOUCHER_PROMOTION,
            'is_enabled' => true,
            'is_hero' => true,
            'variant' => self::USE_PREMIUM_WIDGET_VARIANT
                ? LandingPageBlocks::VOUCHER_PROMOTION_VARIANT_PREMIUM
                : LandingPageBlocks::VOUCHER_PROMOTION_VARIANT_CLASSIC,
            'badge_label' => 'Nhận voucher miễn phí 200.000đ',
            'tag_label' => 'Voucher đặc quyền',
            'title_prefix' => 'Áp dụng tất cả tour',
            'title_highlight' => '',
            'title_suffix' => 'trong & ngoài nước',
            'description' => 'Hải Đăng Travel sẽ ghi nhận lead trong CMS Travel Inquiries và liên hệ để tư vấn cách dùng voucher theo nhu cầu tour, dịch vụ hoặc lịch trình đoàn.',
            'benefits' => [
                LandingPageBlocks::defaultVoucherPromotionBenefit('Voucher trừ trực tiếp 200.000đ vào hóa đơn tour', 'fa-solid fa-ticket'),
                LandingPageBlocks::defaultVoucherPromotionBenefit('Tư vấn lộ trình riêng, thiết kế theo sở thích', 'fa-solid fa-calendar-days'),
                LandingPageBlocks::defaultVoucherPromotionBenefit('Hỗ trợ visa, vé máy bay, khách sạn chuẩn 5 sao', 'fa-solid fa-star'),
                LandingPageBlocks::defaultVoucherPromotionBenefit('Tặng kèm quà tặng du lịch khi đặt tour', 'fa-solid fa-gift'),
            ],
            'offer_label' => 'Miễn phí - Cho mọi tour trong & ngoài nước',
            'offer_note' => 'Áp dụng toàn bộ tour: Thái Lan, Nhật Bản, Pháp, Đà Nẵng, Phú Quốc...',
            'countdown_label' => 'Chương trình kết thúc sau',
            'steps' => [
                LandingPageBlocks::defaultVoucherPromotionStep('Liên hệ chuyên viên', 'Hotline / Zalo / Website - Đội ngũ tư vấn sẵn sàng hỗ trợ.'),
                LandingPageBlocks::defaultVoucherPromotionStep('Chia sẻ gu chuyến đi', 'Biển, núi, team building, nghỉ dưỡng hoặc tour nước ngoài.'),
                LandingPageBlocks::defaultVoucherPromotionStep('Nhận voucher & gợi ý', 'Tư vấn viên gửi voucher 200.000đ và đề xuất tour phù hợp nhất.'),
            ],
            'voucher_campaign_slug' => self::CAMPAIGN_SLUG,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function faqItems(): array
    {
        return [
            [
                'question' => 'Voucher được phát mã như thế nào?',
                'answer' => 'Campaign voucher có thời gian áp dụng từ ngày bắt đầu đến ngày kết thúc, số lượng mã giới hạn và một frame hình chung cho popup hiển thị mã. Khi khách gửi form thành công, hệ thống cấp một mã còn trống và lưu cùng lead trong Travel Inquiries.',
            ],
            [
                'question' => 'Tôi có thể xem lại mã sau khi đã submit không?',
                'answer' => 'Có. Mã voucher được lưu bằng session cookie trên trình duyệt của khách. Khi quay lại landing page trên cùng trình duyệt, khách có thể bấm xem lại mã đã nhận.',
            ],
            [
                'question' => 'Nếu bộ mã voucher được đổi mới thì sao?',
                'answer' => 'Mỗi lần admin đổi mới bộ mã, campaign sẽ đổi phiên bản mã. Cookie cũ không còn hợp lệ, khách cần submit lại để nhận mã mới trong thời gian campaign còn áp dụng.',
            ],
        ];
    }
}
