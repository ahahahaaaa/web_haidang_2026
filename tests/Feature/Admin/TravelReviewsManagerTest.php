<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Cms\TravelReviewsManager;
use App\Models\User;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Src\Domains\Cms\Models\TourReviewBatch;
use Src\Domains\Cms\Models\TravelReview;
use Tests\TestCase;

class TravelReviewsManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tour_review_can_be_created_and_deleted_in_dedicated_manager(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $tour = Tour::query()->create([
            'title' => 'Hà Nội 3 ngày',
            'slug' => 'ha-noi-3-ngay',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
        ]);

        Livewire::test(TravelReviewsManager::class, ['tour' => $tour])
            ->set('form.title', 'Lịch đi gọn')
            ->set('form.author_name', 'Anh Minh')
            ->set('form.author_title', 'Nhóm 4 khách')
            ->set('form.content', 'Tour đi gọn và khâu tư vấn khá rõ ràng.')
            ->set('form.rating_value', 4.9)
            ->set('form.status', 'published')
            ->set('form.published_at', '2026-04-15')
            ->call('saveReview')
            ->assertHasNoErrors();

        $review = TravelReview::query()->firstOrFail();
        $this->assertSame(Tour::class, $review->reviewable_type);
        $this->assertSame($tour->id, $review->reviewable_id);

        Livewire::test(TravelReviewsManager::class, ['tour' => $tour])
            ->call('deleteReview', $review->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('travel_reviews', ['id' => $review->id]);
    }

    public function test_category_and_destination_review_routes_render(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $category = TourCategory::query()->create([
            'name' => 'Tour văn hóa',
            'slug' => 'tour-van-hoa',
            'status' => 'published',
        ]);

        $destination = Destination::query()->create([
            'name' => 'Hà Nội',
            'slug' => 'ha-noi',
            'status' => 'published',
        ]);

        $this->get(route('admin.tours.categories.reviews.index', $category))
            ->assertOk()
            ->assertSeeText('Quản lý đánh giá')
            ->assertSeeText('Tour văn hóa');

        $this->get(route('admin.tours.destinations.reviews.index', $destination))
            ->assertOk()
            ->assertSeeText('Quản lý đánh giá')
            ->assertSeeText('Hà Nội');
    }

    public function test_tour_reviews_can_be_filtered_and_assigned_to_review_batch(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $tour = Tour::query()->create([
            'title' => 'Hà Nội 3 ngày',
            'slug' => 'ha-noi-3-ngay',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
        ]);
        $batch = TourReviewBatch::query()->create([
            'tour_id' => $tour->id,
            'departure_date' => '2026-06-20',
            'enabled' => true,
            'label' => 'Đoàn 20/06',
            'token' => 'batch-token-123456789012345678901234567890123456',
        ]);

        Livewire::test(TravelReviewsManager::class, ['tour' => $tour])
            ->set('form.title', 'Lượt đúng')
            ->set('form.author_name', 'Anh Minh')
            ->set('form.content', 'Review đúng lượt.')
            ->set('form.rating_value', 5)
            ->set('form.tour_review_batch_id', (string) $batch->id)
            ->call('saveReview')
            ->assertHasNoErrors();

        $review = TravelReview::query()->firstOrFail();

        $this->assertSame($batch->id, $review->tour_review_batch_id);

        Livewire::test(TravelReviewsManager::class, ['tour' => $tour])
            ->set('batchFilter', (string) $batch->id)
            ->assertSee('Lượt đúng')
            ->assertSee('Đoàn 20/06');
    }

    public function test_review_manager_returns_404_when_travel_reviews_are_disabled(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $tour = Tour::query()->create([
            'title' => 'Hà Nội 3 ngày',
            'slug' => 'ha-noi-3-ngay',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
        ]);

        Config::set('travel_reviews.enabled', false);

        $this->get(route('admin.tours.reviews.index', $tour))
            ->assertNotFound();
    }

    public function test_review_edit_route_returns_404_when_review_does_not_belong_to_current_owner(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $firstTour = Tour::query()->create([
            'title' => 'Hà Nội 3 ngày',
            'slug' => 'ha-noi-3-ngay',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
        ]);

        $secondTour = Tour::query()->create([
            'title' => 'Đà Nẵng 4 ngày',
            'slug' => 'da-nang-4-ngay',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
        ]);

        $review = TravelReview::query()->create([
            'reviewable_type' => Tour::class,
            'reviewable_id' => $secondTour->id,
            'author_name' => 'Anh Minh',
            'content' => 'Review chỉ thuộc tour thứ hai.',
            'rating_value' => 4.8,
            'status' => 'published',
        ]);

        $this->get(route('admin.tours.reviews.edit', ['tour' => $firstTour, 'review' => $review]))
            ->assertNotFound();
    }

    public function test_sale_cannot_open_reviews_for_tour_managed_by_another_user(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $firstSale = User::query()->create([
            'name' => 'Sale One',
            'email' => 'sale-one@example.com',
            'password' => 'password',
        ]);
        $firstSale->assignRole('sale');

        $secondSale = User::query()->create([
            'name' => 'Sale Two',
            'email' => 'sale-two@example.com',
            'password' => 'password',
        ]);
        $secondSale->assignRole('sale');

        $otherTour = Tour::query()->create([
            'title' => 'Đà Nẵng 4 ngày',
            'slug' => 'da-nang-4-ngay',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'managed_by_user_id' => $secondSale->id,
        ]);

        $this->actingAs($firstSale);

        $this->get(route('admin.tours.reviews.index', $otherTour))
            ->assertNotFound();
    }
}
