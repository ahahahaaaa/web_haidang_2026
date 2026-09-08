<?php

namespace Tests\Feature;

use App\Mail\TravelInquiryMail;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Src\Domains\Cms\Enums\TravelInquirySource;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\TravelInquiry;
use Src\Domains\Cms\Models\VoucherCampaign;
use Src\Domains\Cms\Models\VoucherCode;
use Tests\TestCase;

class VoucherCampaignFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_voucher_campaign_issues_code_and_records_it_on_travel_inquiry(): void
    {
        Mail::fake();
        $this->seedSiteSettings();
        $campaign = $this->createCampaign();
        $validUntilLabel = $campaign->code_valid_until?->format('d/m/Y');
        $campaign->codes()->create([
            'code' => 'HDTRAVEL200-TEST01',
            'code_set_version' => $campaign->code_set_version,
        ]);

        $response = $this->postJson(route('travel-inquiries.store'), [
            'submission_mode' => 'modal',
            'source' => TravelInquirySource::General->value,
            'inquiry_type' => 'travel',
            'customer_name' => 'Nguyễn Voucher',
            'customer_phone' => '0909123000',
            'subject' => 'Đăng ký nhận voucher du lịch',
            'page_url' => route('landing.show', ['slug' => 'voucher-du-lich']),
            'voucher_campaign_slug' => $campaign->slug,
            'voucher_variant' => 'premium',
        ]);

        $response
            ->assertOk()
            ->assertCookie($campaign->cookieName())
            ->assertJsonPath('voucher.code', 'HDTRAVEL200-TEST01')
            ->assertJsonPath('voucher.valid_until_label', $validUntilLabel)
            ->assertJsonPath('voucher.valid_until_note', 'Áp dụng đến hết ngày '.$validUntilLabel)
            ->assertJsonPath('voucher_status', 'issued');

        $inquiry = TravelInquiry::query()->latest('id')->firstOrFail();
        $code = VoucherCode::query()->firstOrFail();

        $this->assertSame('HDTRAVEL200-TEST01', data_get($inquiry->meta, 'voucher.code'));
        $this->assertSame($campaign->slug, data_get($inquiry->meta, 'voucher.campaign_slug'));
        $this->assertSame('premium', data_get($inquiry->meta, 'voucher_variant'));
        $this->assertSame(VoucherCode::STATUS_CLAIMED, $code->status);
        $this->assertSame($campaign->code_set_version, $code->code_set_version);
        $this->assertSame($inquiry->id, $code->travel_inquiry_id);
        $this->assertNotNull($code->cookie_token);

        Mail::assertSent(TravelInquiryMail::class);
    }

    public function test_phone_number_receives_one_code_per_campaign_version_and_new_code_after_refresh(): void
    {
        Mail::fake();
        $this->seedSiteSettings();
        $campaign = $this->createCampaign(['code_set_version' => 'version-1']);
        $campaign->codes()->createMany([
            ['code' => 'HDTRAVEL200-FIRST', 'code_set_version' => 'version-1'],
            ['code' => 'HDTRAVEL200-SECOND', 'code_set_version' => 'version-1'],
        ]);

        $this->postJson(route('travel-inquiries.store'), $this->voucherRequest($campaign, [
            'customer_name' => 'Nguyễn Voucher',
            'customer_phone' => '0909123000',
        ]))
            ->assertOk()
            ->assertJsonPath('voucher.code', 'HDTRAVEL200-FIRST')
            ->assertJsonPath('voucher_status', 'issued');

        $this->postJson(route('travel-inquiries.store'), $this->voucherRequest($campaign, [
            'customer_name' => 'Nguyễn Voucher lần hai',
            'customer_phone' => '+84909123000',
        ]))
            ->assertOk()
            ->assertJsonPath('voucher.code', 'HDTRAVEL200-FIRST')
            ->assertJsonPath('voucher_status', 'already_issued');

        $this->assertSame(
            1,
            VoucherCode::query()->where('status', VoucherCode::STATUS_CLAIMED)->where('code_set_version', 'version-1')->count(),
        );
        $this->assertDatabaseHas('voucher_codes', [
            'code' => 'HDTRAVEL200-SECOND',
            'status' => VoucherCode::STATUS_AVAILABLE,
        ]);

        $campaign->forceFill(['code_set_version' => 'version-2'])->save();
        $campaign->codes()->create([
            'code' => 'HDTRAVEL200-NEW',
            'code_set_version' => 'version-2',
        ]);
        $campaign->refresh();

        $this->postJson(route('travel-inquiries.store'), $this->voucherRequest($campaign, [
            'customer_name' => 'Nguyễn Voucher sau đổi mã',
            'customer_phone' => '0909 123 000',
        ]))
            ->assertOk()
            ->assertJsonPath('voucher.code', 'HDTRAVEL200-NEW')
            ->assertJsonPath('voucher_status', 'issued');
    }

    public function test_remembered_voucher_endpoint_returns_code_and_rejects_old_code_version(): void
    {
        $this->withoutMiddleware(EncryptCookies::class);

        $campaign = $this->createCampaign(['code_set_version' => 'version-1']);
        $code = $campaign->codes()->create([
            'code' => 'HDTRAVEL200-REMEMBER',
            'code_set_version' => 'version-1',
            'status' => VoucherCode::STATUS_CLAIMED,
            'cookie_token' => 'remember-token',
            'claimed_at' => now(),
        ]);
        $cookiePayload = json_encode([
            'campaign_slug' => $campaign->slug,
            'code_id' => $code->id,
            'token' => 'remember-token',
            'version' => 'version-1',
        ], JSON_THROW_ON_ERROR);

        $this->withCredentials()
            ->withUnencryptedCookie($campaign->cookieName(), $cookiePayload)
            ->getJson(route('voucher-campaigns.remembered', ['campaign' => $campaign->slug]))
            ->assertOk()
            ->assertJsonPath('voucher.code', 'HDTRAVEL200-REMEMBER')
            ->assertJsonPath('forget_cookie', false);

        $campaign->forceFill(['code_set_version' => 'version-2'])->save();

        $this->withCredentials()
            ->withUnencryptedCookie($campaign->cookieName(), $cookiePayload)
            ->getJson(route('voucher-campaigns.remembered', ['campaign' => $campaign->slug]))
            ->assertOk()
            ->assertJsonPath('voucher', null)
            ->assertJsonPath('forget_cookie', true);
    }

    private function createCampaign(array $overrides = []): VoucherCampaign
    {
        $landing = LandingPage::query()->create([
            'page_key' => null,
            'template_key' => 'generic',
            'editor_mode' => LandingPage::EDITOR_MODE_BLOCKS,
            'title' => 'Voucher du lịch',
            'slug' => 'voucher-du-lich',
            'is_active' => true,
        ]);

        return VoucherCampaign::query()->create([
            'landing_page_id' => $landing->id,
            'title' => 'Voucher du lịch 200.000đ',
            'slug' => 'voucher-du-lich-200k',
            'description' => 'Mã voucher test.',
            'code_prefix' => 'HDTRAVEL200',
            'code_quantity' => 1,
            'code_set_version' => (string) Str::uuid(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'code_valid_until' => now()->addDays(10)->endOfDay(),
            'is_active' => true,
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function voucherRequest(VoucherCampaign $campaign, array $overrides = []): array
    {
        return [
            'submission_mode' => 'modal',
            'source' => TravelInquirySource::General->value,
            'inquiry_type' => 'travel',
            'customer_name' => 'Nguyễn Voucher',
            'customer_phone' => '0909123000',
            'subject' => 'Đăng ký nhận voucher du lịch',
            'page_url' => route('landing.show', ['slug' => 'voucher-du-lich']),
            'voucher_campaign_slug' => $campaign->slug,
            'voucher_variant' => 'premium',
            ...$overrides,
        ];
    }

    private function seedSiteSettings(): void
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'active_theme' => 'haidangtravel',
                'company_name' => 'Hải Đăng Travel',
                'site_name' => 'Hải Đăng Travel',
                'site_description' => 'Tư vấn tour và dịch vụ du lịch.',
                'seo_description' => 'Tư vấn tour và dịch vụ du lịch.',
                'phone' => '028 1234 5678',
                'hotline' => '0909 123 456',
                'primary_email' => 'frontsite@example.com',
                'mail_contact_recipient' => 'sales@example.com',
            ],
        );
    }
}
