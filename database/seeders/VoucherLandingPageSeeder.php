<?php

namespace Database\Seeders;

use App\Services\Travel\VoucherCampaignService;
use App\Support\LandingPageBlocks;
use App\Support\TravelHomePageConfig;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\VoucherCampaign;

class VoucherLandingPageSeeder extends Seeder
{
    private const SLUG = 'voucher-du-lich';

    private const CAMPAIGN_SLUG = 'voucher-du-lich-200k';

    private const HOME_VOUCHER_BLOCK_UUID = 'home-voucher-public-offers';

    /**
     * @var array<int, string>
     */
    private const SAMPLE_CAMPAIGN_SLUGS = [
        'voucher-du-lich-150k',
        'voucher-du-lich-300k',
        'voucher-du-lich-500k',
        'voucher-du-lich-800k',
        'voucher-du-lich-1tr',
    ];

    private const USE_PREMIUM_WIDGET_VARIANT = true;

    public function run(VoucherCampaignService $vouchers): void
    {
        $demoCountries = $this->demoCountries();
        $primaryCountry = $demoCountries->first();
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
            'cta_secondary_label' => 'Khám phá điểm đến',
            'cta_secondary_url' => $this->countryUrl($primaryCountry),
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
            'blocks' => $this->blocks($primaryCountry),
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
                'meta' => [
                    'public_widget_enabled' => true,
                    'public_code' => 'HDTRAVEL200',
                    'public_terms' => 'Áp dụng cho tour trong nước và nước ngoài theo điều kiện chương trình.',
                ],
            ],
        );

        $vouchers->ensureGeneratedCodes($campaign, 200, 'HDTRAVEL200');
        $this->seedSampleCampaigns($vouchers, $demoCountries);
        $this->placeVoucherRailOnHomePage();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function blocks(?Destination $country): array
    {
        return [
            $this->voucherPromotionBlock($country),
            $this->voucherRailBlock(),
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
    private function voucherPromotionBlock(?Destination $country): array
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
            'secondary_label' => 'Khám phá điểm đến',
            'secondary_url' => $this->countryUrl($country),
            'voucher_campaign_slug' => self::CAMPAIGN_SLUG,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function voucherRailBlock(string $uuid = 'voucher-public-offers'): array
    {
        return [
            ...LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_VOUCHER_RAIL),
            'uuid' => $uuid,
            'campaign_slugs' => [self::CAMPAIGN_SLUG, ...self::SAMPLE_CAMPAIGN_SLUGS],
            'limit' => 6,
        ];
    }

    private function placeVoucherRailOnHomePage(): void
    {
        $homePage = LandingPage::query()->where('page_key', 'home')->first();

        if (! $homePage) {
            return;
        }

        $blocks = LandingPageBlocks::normalize($homePage->blocks ?? []);
        $voucherRail = [
            ...$this->voucherRailBlock(self::HOME_VOUCHER_BLOCK_UUID),
            'home_position' => LandingPageBlocks::HOME_POSITION_BEFORE_FEATURED_TOURS,
        ];
        $voucherBlockIndex = collect($blocks)
            ->search(fn (array $block): bool => ($block['uuid'] ?? null) === self::HOME_VOUCHER_BLOCK_UUID);

        if ($voucherBlockIndex === false) {
            $blocks[] = $voucherRail;
        } else {
            $blocks[$voucherBlockIndex] = array_replace($blocks[$voucherBlockIndex], $voucherRail);
        }

        $homeConfig = is_array($homePage->home_config) ? $homePage->home_config : [];
        $voucherToken = TravelHomePageConfig::homeLayoutTokenForBlock(self::HOME_VOUCHER_BLOCK_UUID);
        $layoutOrder = collect(TravelHomePageConfig::homeLayoutOrder($homeConfig, $blocks))
            ->reject(fn (string $token): bool => $token === $voucherToken)
            ->values()
            ->all();
        $topicBlockTokens = collect($blocks)
            ->filter(fn (array $block): bool => (bool) ($block['is_enabled'] ?? true))
            ->filter(fn (array $block): bool => ($block['type'] ?? null) === LandingPageBlocks::TYPE_TOPIC_RAIL)
            ->map(fn (array $block): string => TravelHomePageConfig::homeLayoutTokenForBlock((string) $block['uuid']));
        $topicAnchorIndex = collect($layoutOrder)
            ->search(fn (string $token): bool => $topicBlockTokens->contains($token));

        if ($topicAnchorIndex === false) {
            $topicAnchorIndex = array_search(
                TravelHomePageConfig::homeLayoutTokenForSection('topic_rail'),
                $layoutOrder,
                true,
            );
        }

        if ($topicAnchorIndex === false) {
            $topicAnchorIndex = array_search(
                TravelHomePageConfig::homeLayoutTokenForSection('featured_tours'),
                $layoutOrder,
                true,
            );
            $insertAt = $topicAnchorIndex === false ? count($layoutOrder) : $topicAnchorIndex;
        } else {
            $insertAt = $topicAnchorIndex + 1;
        }

        array_splice($layoutOrder, $insertAt, 0, [$voucherToken]);

        $homePage->forceFill([
            'blocks' => $blocks,
            'home_config' => TravelHomePageConfig::prepare([
                ...$homeConfig,
                'layout_order' => $layoutOrder,
            ], $blocks),
        ])->save();
    }

    /**
     * @param  Collection<int, Destination>  $demoCountries
     */
    private function seedSampleCampaigns(VoucherCampaignService $vouchers, Collection $demoCountries): void
    {
        foreach ($this->sampleCampaigns() as $index => $sample) {
            $country = $demoCountries->isNotEmpty()
                ? $demoCountries->get($index % $demoCountries->count())
                : null;
            $landingPage = $this->seedSampleLandingPage($sample, $country);
            $campaign = VoucherCampaign::query()->updateOrCreate(
                ['slug' => $sample['slug']],
                [
                    'landing_page_id' => $landingPage->getKey(),
                    'title' => $sample['title'],
                    'description' => $sample['description'],
                    'frame_image_url' => '',
                    'code_prefix' => $sample['code_prefix'],
                    'code_quantity' => $sample['code_quantity'],
                    'code_set_version' => VoucherCampaign::query()->where('slug', $sample['slug'])->value('code_set_version') ?: (string) Str::uuid(),
                    'starts_at' => now()->startOfDay(),
                    'ends_at' => now()->addDays(45)->endOfDay(),
                    'code_valid_until' => now()->addDays(60)->endOfDay(),
                    'is_active' => true,
                    'meta' => [
                        'public_widget_enabled' => true,
                        'public_code' => $sample['public_code'],
                        'public_terms' => $sample['public_terms'],
                    ],
                ],
            );

            $vouchers->ensureGeneratedCodes($campaign, $sample['code_quantity'], $sample['code_prefix']);
        }
    }

    /**
     * @param  array<string, mixed>  $sample
     */
    private function seedSampleLandingPage(array $sample, ?Destination $country): LandingPage
    {
        $landingPage = LandingPage::query()
            ->whereNull('page_key')
            ->where('slug', $sample['slug'])
            ->first() ?? new LandingPage(['slug' => $sample['slug']]);
        $landingTitle = 'Nhận eVoucher du lịch '.$sample['value_label'];

        $landingPage->forceFill([
            'page_key' => null,
            'template_key' => 'generic',
            'editor_mode' => LandingPage::EDITOR_MODE_BLOCKS,
            'title' => $landingTitle,
            'slug' => $sample['slug'],
            'is_active' => true,
            'hero_badge' => 'Ưu đãi giới hạn',
            'hero_title' => $landingTitle,
            'hero_excerpt' => $sample['description'],
            'intro_title' => $landingTitle,
            'intro_excerpt' => $sample['public_terms'],
            'body' => null,
            'cta_title' => 'Nhận voucher cho hành trình sắp tới',
            'cta_excerpt' => 'Để lại thông tin để Hải Đăng Travel cấp mã và tư vấn tour phù hợp.',
            'cta_primary_label' => 'Nhận voucher',
            'cta_primary_url' => '#nhan-voucher',
            'cta_secondary_label' => 'Khám phá điểm đến',
            'cta_secondary_url' => $this->countryUrl($country),
            'meta_title' => $landingTitle.' | Hải Đăng Travel',
            'meta_description' => $sample['description'].' '.$sample['public_terms'],
            'og_title' => $landingTitle,
            'og_description' => $sample['description'],
            'canonical_url' => null,
            'robots_directive' => 'index,follow',
            'schema' => null,
            'service_detail_config' => null,
            'home_config' => null,
            'visual_config' => null,
            'blocks' => $this->sampleLandingBlocks($sample, $country),
            'estimate_config' => null,
            'faq_items' => $this->faqItems(),
        ])->save();

        return $landingPage;
    }

    /**
     * @param  array<string, mixed>  $sample
     * @return array<int, array<string, mixed>>
     */
    private function sampleLandingBlocks(array $sample, ?Destination $country): array
    {
        $promotion = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_VOUCHER_PROMOTION_PREMIUM);
        $featuredTours = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOUR_LIST);

        return [
            [
                ...$promotion,
                'uuid' => 'voucher-promotion-'.$sample['slug'],
                'type' => LandingPageBlocks::TYPE_VOUCHER_PROMOTION,
                'is_enabled' => true,
                'is_hero' => true,
                'variant' => LandingPageBlocks::VOUCHER_PROMOTION_VARIANT_PREMIUM,
                'badge_label' => 'Nhận eVoucher miễn phí '.$sample['value_label'],
                'tag_label' => 'Voucher đặc quyền',
                'title_prefix' => 'Nhận eVoucher',
                'title_highlight' => $sample['value_label'],
                'title_suffix' => 'cho hành trình sắp tới',
                'description' => $sample['description'],
                'benefits' => [
                    LandingPageBlocks::defaultVoucherPromotionBenefit($sample['public_terms'], 'fa-solid fa-ticket'),
                    LandingPageBlocks::defaultVoucherPromotionBenefit('Tư vấn tour theo điểm đến, lịch đi và ngân sách', 'fa-solid fa-map-location-dot'),
                    LandingPageBlocks::defaultVoucherPromotionBenefit('Hỗ trợ thêm vé máy bay, visa và dịch vụ đoàn', 'fa-solid fa-plane'),
                ],
                'offer_label' => 'Nhận miễn phí eVoucher '.$sample['value_label'],
                'offer_code' => $sample['public_code'],
                'offer_note' => $sample['public_terms'],
                'countdown_label' => 'Chương trình kết thúc sau',
                'steps' => [
                    LandingPageBlocks::defaultVoucherPromotionStep('Điền thông tin', 'Chia sẻ nhanh họ tên, số điện thoại và nhu cầu du lịch.'),
                    LandingPageBlocks::defaultVoucherPromotionStep('Nhận mã voucher', 'Hệ thống cấp mã còn hiệu lực ngay sau khi form được gửi thành công.'),
                    LandingPageBlocks::defaultVoucherPromotionStep('Nhận tư vấn tour', 'Đội ngũ Hải Đăng Travel liên hệ và gợi ý hành trình phù hợp.'),
                ],
                'primary_label' => 'Nhận voucher',
                'voucher_campaign_slug' => $sample['slug'],
                'inquiry_context' => 'Nhận eVoucher du lịch '.$sample['value_label'],
                'inquiry_subject' => 'Đăng ký nhận eVoucher '.$sample['value_label'],
                'modal_title' => 'Nhận eVoucher '.$sample['value_label'],
                'modal_description' => 'Để lại thông tin để nhận mã voucher và tư vấn tour phù hợp.',
                'secondary_label' => 'Khám phá điểm đến',
                'secondary_url' => $this->countryUrl($country),
            ],
            [
                'uuid' => 'voucher-geo-suppressor-'.$sample['slug'],
                'type' => LandingPageBlocks::TYPE_GEO_ANSWER,
                'is_enabled' => false,
                'title' => '',
                'answer_summary' => '',
                'decision_notes' => [],
            ],
            [
                ...$featuredTours,
                'uuid' => 'voucher-featured-tours-'.$sample['slug'],
                'title' => 'Tour phù hợp với voucher này',
                'description' => 'Danh sách lấy từ các tour nổi bật đang được publish trên hệ thống.',
                'featured' => true,
                'limit' => 3,
                'sort' => 'featured',
            ],
            [
                'uuid' => 'voucher-faq-'.$sample['slug'],
                'type' => LandingPageBlocks::TYPE_FAQ,
                'is_enabled' => true,
                'title' => 'Câu hỏi nhanh về voucher',
                'description' => '',
                'items' => $this->faqItems(),
            ],
        ];
    }

    /**
     * Demo vouchers only link to published international country roots from the Destination CMS.
     *
     * @return Collection<int, Destination>
     */
    private function demoCountries(): Collection
    {
        return Destination::query()
            ->published()
            ->countryRoots()
            ->where('scope', TourScope::International->value)
            ->where('slug', '!=', 'du-lich-quoc-te')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'sort_order']);
    }

    private function countryUrl(?Destination $country): string
    {
        if (! $country) {
            return route('tours.international', absolute: false);
        }

        return route('countries.show', ['slug' => $country->slug], absolute: false);
    }

    /**
     * @return array<int, array{
     *     slug: string,
     *     title: string,
     *     description: string,
     *     code_prefix: string,
     *     value_label: string,
     *     public_code: string,
     *     public_terms: string,
     *     code_quantity: int
     * }>
     */
    private function sampleCampaigns(): array
    {
        return [
            [
                'slug' => 'voucher-du-lich-150k',
                'title' => 'Tặng bạn eVoucher 150K',
                'description' => 'Giảm 150.000đ cho đơn hàng online từ 8.000.000đ.',
                'code_prefix' => 'HDTRAVEL150',
                'value_label' => '150.000đ',
                'public_code' => 'TRIP150',
                'public_terms' => 'Áp dụng cho tour nội địa và Đông Nam Á.',
                'code_quantity' => 25,
            ],
            [
                'slug' => 'voucher-du-lich-300k',
                'title' => 'Tặng bạn eVoucher 300K',
                'description' => 'Giảm 300.000đ cho đơn hàng online từ 16.000.000đ.',
                'code_prefix' => 'HDTRAVEL300',
                'value_label' => '300.000đ',
                'public_code' => 'TRIP300',
                'public_terms' => 'Áp dụng cho tour nội địa và Đông Nam Á.',
                'code_quantity' => 25,
            ],
            [
                'slug' => 'voucher-du-lich-500k',
                'title' => 'Tặng bạn eVoucher 500K',
                'description' => 'Giảm 500.000đ cho đơn hàng online từ 26.000.000đ.',
                'code_prefix' => 'HDTRAVEL500',
                'value_label' => '500.000đ',
                'public_code' => 'TRIP500',
                'public_terms' => 'Áp dụng cho tour nội địa, Đông Nam Á và Đông Bắc Á.',
                'code_quantity' => 25,
            ],
            [
                'slug' => 'voucher-du-lich-800k',
                'title' => 'Tặng bạn eVoucher 800K',
                'description' => 'Giảm 800.000đ cho đơn hàng online từ 40.000.000đ.',
                'code_prefix' => 'HDTRAVEL800',
                'value_label' => '800.000đ',
                'public_code' => 'TRIP800',
                'public_terms' => 'Áp dụng cho tour nước ngoài theo điều kiện chương trình.',
                'code_quantity' => 25,
            ],
            [
                'slug' => 'voucher-du-lich-1tr',
                'title' => 'Tặng bạn eVoucher 1 triệu',
                'description' => 'Giảm 1.000.000đ cho đơn hàng online từ 60.000.000đ.',
                'code_prefix' => 'HDTRAVEL1000',
                'value_label' => '1.000.000đ',
                'public_code' => 'TRIP1000',
                'public_terms' => 'Áp dụng cho tour nước ngoài và tour đoàn theo điều kiện chương trình.',
                'code_quantity' => 25,
            ],
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
