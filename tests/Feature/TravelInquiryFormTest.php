<?php

namespace Tests\Feature;

use App\Mail\TravelInquiryMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Src\Domains\Cms\Enums\TravelInquirySource;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourDeparture;
use Src\Domains\Cms\Models\TourFlashSale;
use Src\Domains\Cms\Models\TourFlashSaleItem;
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

    public function test_flash_sale_inquiry_reserves_total_adult_and_child_tickets_and_snapshots_server_price(): void
    {
        Mail::fake();
        $this->seedSiteSettings();
        [$tour, $departure, $campaign, $item] = $this->flashSaleFixture(ticketQuantity: 6);

        $response = $this->postJson(route('travel-inquiries.store'), [
            'submission_mode' => 'modal',
            'source' => TravelInquirySource::Tour->value,
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $departure->getKey(),
            'flash_sale_slug' => $campaign->slug,
            'inquiry_type' => 'travel',
            'customer_name' => 'Nguyễn Flash Sale',
            'customer_phone' => '0909111222',
            'adult_guest_count' => 2,
            'party_size' => 1,
            'subject' => 'Đặt tour Flash Sale',
            'page_url' => route('tours.show', [
                'tour' => $tour,
                'flash_sale' => $campaign->slug,
                'flash_departure' => $departure->getKey(),
            ]),
            'quoted_unit_price' => 1,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('booking_quote.price_type', 'flash_sale')
            ->assertJsonPath('booking_quote.quoted_unit_price', 3990000)
            ->assertJsonPath('booking_quote.ticket_count', 3)
            ->assertJsonPath('booking_quote.flash_tickets_remaining_after', 3)
            ->assertJsonFragment(['message' => 'Đã ghi nhận giá Flash Sale 3.990.000 đ/khách cho 3 vé (2 người lớn + 1 trẻ em). Còn 3 vé Flash Sale. Hải Đăng Travel sẽ liên hệ xác nhận.']);

        $inquiry = TravelInquiry::query()->latest('id')->firstOrFail();
        $this->assertSame('flash_sale', $inquiry->price_type);
        $this->assertSame(3990000, $inquiry->quoted_unit_price);
        $this->assertSame(6990000, $inquiry->regular_unit_price);
        $this->assertSame(3, $inquiry->ticket_count);
        $this->assertSame($departure->getKey(), $inquiry->tour_departure_id);
        $this->assertSame($item->getKey(), $inquiry->tour_flash_sale_item_id);
        $this->assertSame('reserved', data_get($inquiry->meta, 'booking_quote.flash_sale_status'));
        $this->assertSame(3, $item->fresh()->booked_quantity);

        $mailHtml = view('emails.travel-inquiry', ['inquiry' => $inquiry])->render();
        $this->assertStringContainsString('3.990.000 đ/khách', $mailHtml);
        $this->assertStringContainsString('Đã giữ đủ vé Flash Sale', $mailHtml);

        $adminHtml = view('livewire.admin.cms.partials.travel-inquiry-detail-panel', [
            'selectedInquiry' => $inquiry,
        ])->render();
        $this->assertStringContainsString('Giá tại thời điểm đặt', $adminHtml);
        $this->assertStringContainsString('3.990.000 đ/khách', $adminHtml);

        Mail::assertSent(TravelInquiryMail::class);
    }

    public function test_flash_sale_inquiry_falls_back_to_regular_price_when_tickets_are_insufficient(): void
    {
        Mail::fake();
        $this->seedSiteSettings();
        [$tour, $departure, $campaign, $item] = $this->flashSaleFixture(ticketQuantity: 2);

        $response = $this->postJson(route('travel-inquiries.store'), [
            'submission_mode' => 'modal',
            'source' => TravelInquirySource::Tour->value,
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $departure->getKey(),
            'flash_sale_slug' => $campaign->slug,
            'inquiry_type' => 'travel',
            'customer_name' => 'Nguyễn Giá Thường',
            'customer_phone' => '0909222333',
            'adult_guest_count' => 2,
            'party_size' => 1,
            'subject' => 'Đặt tour không đủ vé Flash Sale',
            'page_url' => route('tours.show', $tour),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('booking_quote.price_type', 'regular')
            ->assertJsonPath('booking_quote.quoted_unit_price', 6990000)
            ->assertJsonPath('booking_quote.flash_sale_status', 'insufficient_tickets')
            ->assertJsonPath('booking_quote.flash_tickets_remaining_before', 2)
            ->assertJsonFragment(['message' => 'Flash Sale chỉ còn 2 vé, không đủ cho 3 khách. Yêu cầu đã được ghi nhận theo giá thường 6.990.000 đ/khách; Hải Đăng Travel sẽ liên hệ xác nhận.']);

        $inquiry = TravelInquiry::query()->latest('id')->firstOrFail();
        $this->assertSame('regular', $inquiry->price_type);
        $this->assertSame(6990000, $inquiry->quoted_unit_price);
        $this->assertSame(0, $item->fresh()->booked_quantity);

        Mail::assertSent(TravelInquiryMail::class);
    }

    public function test_tour_inquiry_requires_at_least_one_adult_or_child_ticket(): void
    {
        Mail::fake();
        $this->seedSiteSettings();
        [$tour, $departure, $campaign] = $this->flashSaleFixture(ticketQuantity: 2);

        $this->postJson(route('travel-inquiries.store'), [
            'submission_mode' => 'modal',
            'source' => TravelInquirySource::Tour->value,
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $departure->getKey(),
            'flash_sale_slug' => $campaign->slug,
            'inquiry_type' => 'travel',
            'customer_name' => 'Nguyễn Thiếu Số Khách',
            'customer_phone' => '0909333444',
            'adult_guest_count' => 0,
            'party_size' => 0,
            'subject' => 'Đặt tour',
            'page_url' => route('tours.show', $tour),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('adult_guest_count');

        $this->assertSame(0, TravelInquiry::query()->count());
        Mail::assertNothingSent();
    }

    public function test_later_inquiry_falls_back_to_regular_price_after_previous_inquiry_uses_last_flash_tickets(): void
    {
        Mail::fake();
        $this->seedSiteSettings();
        [$tour, $departure, $campaign, $item] = $this->flashSaleFixture(ticketQuantity: 2);
        $payload = [
            'submission_mode' => 'modal',
            'source' => TravelInquirySource::Tour->value,
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $departure->getKey(),
            'flash_sale_slug' => $campaign->slug,
            'inquiry_type' => 'travel',
            'customer_phone' => '0909444555',
            'party_size' => 0,
            'subject' => 'Đặt vé cuối Flash Sale',
            'page_url' => route('tours.show', $tour),
        ];

        $this->postJson(route('travel-inquiries.store'), [
            ...$payload,
            'customer_name' => 'Khách lấy vé cuối',
            'adult_guest_count' => 2,
        ])->assertOk()
            ->assertJsonPath('booking_quote.price_type', 'flash_sale')
            ->assertJsonPath('booking_quote.flash_tickets_remaining_after', 0);

        $this->postJson(route('travel-inquiries.store'), [
            ...$payload,
            'customer_name' => 'Khách đến sau',
            'customer_phone' => '0909555666',
            'adult_guest_count' => 1,
        ])->assertOk()
            ->assertJsonPath('booking_quote.price_type', 'regular')
            ->assertJsonPath('booking_quote.flash_sale_status', 'sold_out')
            ->assertJsonPath('booking_quote.flash_tickets_remaining_before', 0);

        $this->assertSame(2, $item->fresh()->booked_quantity);
        $this->assertSame(['flash_sale', 'regular'], TravelInquiry::query()->orderBy('id')->pluck('price_type')->all());
        Mail::assertSentCount(2);
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

    /** @return array{Tour, TourDeparture, TourFlashSale, TourFlashSaleItem} */
    private function flashSaleFixture(int $ticketQuantity): array
    {
        $tour = Tour::query()->create([
            'title' => 'Tour Thái Lan Flash Sale',
            'slug' => 'tour-thai-lan-flash-sale-inquiry',
            'status' => 'published',
            'scope' => 'international',
            'base_price' => 7990000,
            'sale_price' => 6990000,
            'published_at' => now()->subDay(),
        ]);
        $departure = TourDeparture::query()->create([
            'tour_id' => $tour->getKey(),
            'departure_date' => now()->addDays(5)->toDateString(),
            'base_price' => 7990000,
            'sale_price' => 6990000,
            'available_slots' => 20,
            'status' => 'scheduled',
        ]);
        $campaign = TourFlashSale::query()->create([
            'title' => 'Flash Sale đặt tour',
            'slug' => 'flash-sale-dat-tour',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);
        $item = $campaign->items()->create([
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $departure->getKey(),
            'flash_price' => 3990000,
            'ticket_quantity' => $ticketQuantity,
            'sort_order' => 0,
        ]);

        return [$tour, $departure, $campaign, $item];
    }
}
