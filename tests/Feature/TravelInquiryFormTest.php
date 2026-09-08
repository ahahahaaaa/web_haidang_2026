<?php

namespace Tests\Feature;

use App\Mail\TravelInquiryMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Src\Domains\Cms\Enums\TravelInquirySource;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\TravelInquiry;
use Tests\TestCase;

class TravelInquiryFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_frontsite_travel_inquiry_stores_extended_form_fields_in_meta_and_reopens_modal(): void
    {
        Mail::fake();

        $this->seedSiteSettings();

        $response = $this->post(route('travel-inquiries.store'), [
            'submission_mode' => 'modal',
            'source' => TravelInquirySource::General->value,
            'inquiry_type' => 'travel',
            'customer_name' => 'Nguyễn Văn A',
            'customer_email' => 'nguyenvana@example.com',
            'customer_phone' => '0909123456',
            'adult_guest_count' => 12,
            'party_size' => 2,
            'address' => '123 Nguyễn Huệ, Quận 1',
            'subject' => 'Tour đoàn Đà Nẵng tháng 7',
            'message' => 'Cần tư vấn lịch trình 3 ngày 2 đêm cho đoàn công ty.',
            'page_url' => route('home'),
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('travel_inquiry_open_modal', true)
            ->assertSessionHas('travel_inquiry_status');

        $inquiry = TravelInquiry::query()->latest('id')->first();

        $this->assertNotNull($inquiry);
        $this->assertSame('Tour đoàn Đà Nẵng tháng 7', $inquiry->context_title);
        $this->assertSame('Nguyễn Văn A', $inquiry->customer_name);
        $this->assertSame('nguyenvana@example.com', $inquiry->customer_email);
        $this->assertSame(2, $inquiry->party_size);
        $this->assertSame([
            'address' => '123 Nguyễn Huệ, Quận 1',
            'adult_guest_count' => 12,
            'inquiry_type' => 'travel',
            'subject' => 'Tour đoàn Đà Nẵng tháng 7',
        ], $inquiry->meta);

        Mail::assertSent(TravelInquiryMail::class);
    }

    public function test_frontsite_travel_inquiry_accepts_blank_customer_email(): void
    {
        Mail::fake();

        $this->seedSiteSettings();

        $response = $this->post(route('travel-inquiries.store'), [
            'submission_mode' => 'modal',
            'source' => TravelInquirySource::General->value,
            'inquiry_type' => 'travel',
            'customer_name' => 'Nguyễn Văn B',
            'customer_phone' => '0909000111',
            'subject' => 'Cần tư vấn tour hè',
            'message' => 'Tôi muốn được tư vấn tour phù hợp cho gia đình.',
            'page_url' => route('home'),
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('travel_inquiry_status');

        $inquiry = TravelInquiry::query()->latest('id')->first();

        $this->assertNotNull($inquiry);
        $this->assertSame('Nguyễn Văn B', $inquiry->customer_name);
        $this->assertNull($inquiry->customer_email);

        Mail::assertSent(TravelInquiryMail::class);
    }

    public function test_frontsite_travel_inquiry_accepts_blank_message(): void
    {
        Mail::fake();

        $this->seedSiteSettings();

        $response = $this->post(route('travel-inquiries.store'), [
            'submission_mode' => 'modal',
            'source' => TravelInquirySource::General->value,
            'inquiry_type' => 'travel',
            'customer_name' => 'Nguyễn Văn C',
            'customer_phone' => '0909000222',
            'subject' => 'Đặt tour Phú Quốc',
            'page_url' => route('home'),
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('travel_inquiry_status');

        $inquiry = TravelInquiry::query()->latest('id')->first();

        $this->assertNotNull($inquiry);
        $this->assertNull($inquiry->message);

        Mail::assertSent(TravelInquiryMail::class);
    }

    public function test_frontsite_travel_inquiry_verifies_google_recaptcha_v3_when_enabled(): void
    {
        Mail::fake();

        $this->seedSiteSettings([
            'google_recaptcha_v3_enabled' => true,
            'google_recaptcha_v3_site_key' => 'site-key-demo',
            'google_recaptcha_v3_secret_key' => 'secret-key-demo',
            'google_recaptcha_v3_min_score' => 0.5,
        ]);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
                'action' => 'travel_inquiry',
            ]),
        ]);

        $response = $this->postJson(route('travel-inquiries.store'), [
            'submission_mode' => 'modal',
            'source' => TravelInquirySource::General->value,
            'inquiry_type' => 'travel',
            'customer_name' => 'Nguyễn Văn D',
            'customer_phone' => '0909000333',
            'subject' => 'Tư vấn tour có captcha',
            'page_url' => route('home'),
            'g-recaptcha-response' => 'valid-token',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Yêu cầu của bạn đã được ghi nhận. Hải Đăng Travel sẽ liên hệ sớm nhất.');

        $this->assertSame('Nguyễn Văn D', TravelInquiry::query()->latest('id')->first()?->customer_name);
        Mail::assertSent(TravelInquiryMail::class);

        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
                && $request['secret'] === 'secret-key-demo'
                && $request['response'] === 'valid-token';
        });
    }

    public function test_frontsite_travel_inquiry_rejects_missing_google_recaptcha_token_when_enabled(): void
    {
        Mail::fake();

        $this->seedSiteSettings([
            'google_recaptcha_v3_enabled' => true,
            'google_recaptcha_v3_site_key' => 'site-key-demo',
            'google_recaptcha_v3_secret_key' => 'secret-key-demo',
        ]);

        Http::fake();

        $this->postJson(route('travel-inquiries.store'), [
            'submission_mode' => 'modal',
            'source' => TravelInquirySource::General->value,
            'inquiry_type' => 'travel',
            'customer_name' => 'Nguyễn Văn E',
            'customer_phone' => '0909000444',
            'subject' => 'Tư vấn tour thiếu captcha',
            'page_url' => route('home'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('g-recaptcha-response');

        $this->assertSame(0, TravelInquiry::query()->count());
        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    private function seedSiteSettings(array $overrides = []): void
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            array_merge([
                'active_theme' => 'haidangtravel',
                'company_name' => 'Hải Đăng Travel',
                'site_name' => 'Hải Đăng Travel',
                'site_description' => 'Tư vấn tour và dịch vụ du lịch.',
                'seo_description' => 'Tư vấn tour và dịch vụ du lịch.',
                'phone' => '028 1234 5678',
                'hotline' => '0909 123 456',
                'primary_email' => 'frontsite@example.com',
                'mail_contact_recipient' => 'sales@example.com',
            ], $overrides),
        );
    }
}
