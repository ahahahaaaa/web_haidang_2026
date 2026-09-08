<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TravelReview;
use Tests\TestCase;

class TourReviewSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_review_link_requires_valid_token_and_password(): void
    {
        [$tour, $batch] = $this->reviewableTour();

        $this->get('/danh-gia-tour/'.$tour->slug.'/'.Str::random(48))
            ->assertNotFound();

        $this->get(route('tour-reviews.public.show', ['tour' => $tour, 'token' => $batch->token]))
            ->assertOk()
            ->assertSeeText('Nhập mật khẩu đánh giá');

        $this->postJson(route('tour-reviews.public.unlock', ['tour' => $tour, 'token' => $batch->token]), [
            'review_password' => 'sai-mat-khau',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('review_password');
    }

    public function test_customer_can_unlock_qr_link_and_submit_draft_review_for_tour(): void
    {
        [$tour, $batch] = $this->reviewableTour();

        $this->postJson(route('tour-reviews.public.unlock', ['tour' => $tour, 'token' => $batch->token]), [
            'review_password' => 'HDT-HANOI-2026',
        ])->assertOk()
            ->assertJsonPath('redirect_url', route('tour-reviews.public.show', ['tour' => $tour, 'token' => $batch->token]).'#tour-review-form');

        $this->postJson(route('tour-reviews.public.store', ['tour' => $tour, 'token' => $batch->token]), [
            'author_email' => 'khach@example.com',
            'author_name' => 'Chị Lan',
            'author_phone' => '0909123456',
            'author_title' => 'Gia đình 4 khách',
            'content' => 'Lịch trình rõ ràng, tư vấn trước chuyến đi rất kỹ.',
            'rating_value' => 5,
            'title' => 'Dịch vụ chu đáo',
        ])->assertOk()
            ->assertJsonPath('message', 'Cảm ơn bạn đã gửi đánh giá. Nội dung sẽ hiển thị sau khi đội ngũ Hải Đăng Travel kiểm duyệt.');

        $review = TravelReview::query()->firstOrFail();

        $this->assertSame(Tour::class, $review->reviewable_type);
        $this->assertSame($tour->id, $review->reviewable_id);
        $this->assertSame('public_qr', $review->source);
        $this->assertSame('draft', $review->status);
        $this->assertSame('0909123456', $review->author_phone);
        $this->assertSame($batch->id, $review->tour_review_batch_id);

        $this->get(route('tours.show', $tour))
            ->assertDontSeeText('Dịch vụ chu đáo');

        $review->update([
            'published_at' => now(),
            'status' => 'published',
        ]);

        $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSeeText('Dịch vụ chu đáo')
            ->assertSeeText('Chị Lan');
    }

    public function test_qr_review_without_password_opens_form_directly(): void
    {
        [$tour, $batch] = $this->reviewableTour(password: null);

        $this->get(route('tour-reviews.public.show', ['tour' => $tour, 'token' => $batch->token]))
            ->assertOk()
            ->assertSeeText('Gửi đánh giá của bạn')
            ->assertDontSeeText('Nhập mật khẩu đánh giá');

        $this->postJson(route('tour-reviews.public.store', ['tour' => $tour, 'token' => $batch->token]), [
            'author_name' => 'Anh Tuấn',
            'author_phone' => '+84 909 123 456',
            'content' => 'Tour vận hành đúng lịch và hướng dẫn viên hỗ trợ tốt.',
            'rating_value' => 5,
        ])->assertOk();

        $review = TravelReview::query()->firstOrFail();

        $this->assertSame('0909123456', $review->author_phone);
        $this->assertSame($batch->id, $review->tour_review_batch_id);
    }

    public function test_public_tour_detail_review_requires_vietnam_phone_and_stores_draft(): void
    {
        [$tour, $batch] = $this->reviewableTour();

        $this->postJson(route('tour-reviews.web.store', $tour), [
            'author_name' => 'Khách không hợp lệ',
            'author_phone' => '12345',
            'content' => 'Số điện thoại sai.',
            'rating_value' => 4,
            'tour_review_batch_id' => $batch->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('author_phone');

        $this->postJson(route('tour-reviews.web.store', $tour), [
            'author_name' => 'Chị Mai',
            'author_phone' => '028 1234 5678',
            'content' => 'Tư vấn trước chuyến đi rõ ràng.',
            'rating_value' => 5,
            'tour_review_batch_id' => $batch->id,
            'title' => 'Đánh giá từ trang tour',
        ])->assertOk();

        $review = TravelReview::query()->firstOrFail();

        $this->assertSame('public_web', $review->source);
        $this->assertSame('draft', $review->status);
        $this->assertSame('02812345678', $review->author_phone);
        $this->assertSame($batch->id, $review->tour_review_batch_id);
    }

    public function test_public_tour_review_accepts_valid_recaptcha_when_enabled(): void
    {
        [$tour] = $this->reviewableTour();

        SiteSetting::query()->updateOrCreate(['id' => 1], [
            'active_theme' => 'haidangtravel',
            'company_name' => 'Hải Đăng Travel',
            'google_recaptcha_v3_enabled' => true,
            'google_recaptcha_v3_min_score' => 0.5,
            'google_recaptcha_v3_secret_key' => 'secret-key-demo',
            'google_recaptcha_v3_site_key' => 'site-key-demo',
            'seo_description' => 'Tour trong nước, tour nước ngoài và tour đoàn.',
            'site_name' => 'Hải Đăng Travel',
        ]);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'action' => 'tour_review',
                'score' => 0.9,
                'success' => true,
            ]),
        ]);

        $this->postJson(route('tour-reviews.web.store', $tour), [
            'author_name' => 'Anh Nam',
            'author_phone' => '0909000333',
            'content' => 'Có captcha hợp lệ.',
            'g-recaptcha-response' => 'valid-token',
            'rating_value' => 5,
        ])->assertOk();

        $this->assertSame('Anh Nam', TravelReview::query()->latest('id')->first()?->author_name);

        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
                && $request['secret'] === 'secret-key-demo'
                && $request['response'] === 'valid-token';
        });
    }

    public function test_public_tour_review_rejects_missing_recaptcha_when_enabled(): void
    {
        [$tour] = $this->reviewableTour();

        SiteSetting::query()->updateOrCreate(['id' => 1], [
            'active_theme' => 'haidangtravel',
            'company_name' => 'Hải Đăng Travel',
            'google_recaptcha_v3_enabled' => true,
            'google_recaptcha_v3_min_score' => 0.5,
            'google_recaptcha_v3_secret_key' => 'secret-key-demo',
            'google_recaptcha_v3_site_key' => 'site-key-demo',
            'seo_description' => 'Tour trong nước, tour nước ngoài và tour đoàn.',
            'site_name' => 'Hải Đăng Travel',
        ]);

        Http::fake();

        $this->postJson(route('tour-reviews.web.store', $tour), [
            'author_name' => 'Anh Nam',
            'author_phone' => '0909000333',
            'content' => 'Thiếu captcha.',
            'rating_value' => 5,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('g-recaptcha-response');

        Http::assertNothingSent();
    }

    protected function reviewableTour(?string $password = 'HDT-HANOI-2026'): array
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'active_theme' => 'haidangtravel',
                'company_name' => 'Hải Đăng Travel',
                'site_name' => 'Hải Đăng Travel',
                'seo_description' => 'Tour trong nước, tour nước ngoài và tour đoàn.',
            ],
        );

        $tour = Tour::query()->create([
            'title' => 'Hà Nội 3 ngày 2 đêm',
            'slug' => 'ha-noi-3-ngay-2-dem',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'excerpt' => 'Hành trình ngắn ngày khám phá Hà Nội.',
            'published_at' => now(),
        ]);

        $batch = $tour->reviewBatches()->create([
            'departure_date' => '2026-06-20',
            'enabled' => true,
            'label' => 'Đoàn khởi hành 20/06',
            'password' => $password,
            'token' => Str::random(48),
        ]);

        $tour->forceFill([
            'review_submission_enabled' => true,
            'review_submission_password' => $password,
            'review_submission_token' => $batch->token,
        ])->save();

        return [$tour->fresh(), $batch->fresh()];
    }
}
