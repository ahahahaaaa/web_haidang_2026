<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Cms\ToursManager;
use App\Jobs\Travel\PushTourToAgencyJob;
use App\Models\User;
use App\Services\Cms\SiteSettingsManager;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourAgencyPushSyncRun;
use Src\Domains\Cms\Models\TourCategory;
use Src\Domains\Cms\Models\TourDeparture;
use Src\Domains\Cms\Models\TourDepartureSyncState;
use Src\Domains\Cms\Models\TourReviewBatch;
use Tests\TestCase;

class ToursManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tour_category_and_destination_can_store_faq_items_and_rating_summary_config(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);
        $countryRoot = Destination::query()->countryRoots()->firstOrFail();

        Livewire::test(ToursManager::class)
            ->set('categoryForm.name', 'Tour văn hóa')
            ->set('categoryForm.faq_items', [
                [
                    'question' => 'Tour văn hóa phù hợp với ai?',
                    'answer' => '<p>Phù hợp với khách muốn ưu tiên trải nghiệm bản sắc địa phương. Xem thêm <a href="https://example.com/tour-van-hoa">chi tiết</a>.</p>',
                ],
            ])
            ->set('categoryForm.rating_average', 4.8)
            ->set('categoryForm.rating_count', 128)
            ->call('saveCategory')
            ->assertHasNoErrors();

        Livewire::test(ToursManager::class)
            ->set('destinationForm.name', 'Hà Nội')
            ->set('destinationForm.country_id', $countryRoot->getKey())
            ->set('destinationForm.show_tours_on_page', false)
            ->set('destinationForm.show_blogs_on_page', true)
            ->set('destinationForm.faq_items', [
                [
                    'question' => 'Nên đi Hà Nội mùa nào?',
                    'answer' => '<p>Mùa thu và mùa xuân thường dễ đi và thời tiết dễ chịu hơn. Xem thêm <a href="https://example.com/ha-noi-mua-thu">gợi ý lịch</a>.</p>',
                ],
            ])
            ->set('destinationForm.rating_average', 4.9)
            ->set('destinationForm.rating_count', 86)
            ->call('saveDestination')
            ->assertHasNoErrors();

        $this->assertSame(
            'Tour văn hóa phù hợp với ai?',
            data_get(TourCategory::query()->where('slug', 'tour-van-hoa')->firstOrFail()->faq_items, '0.question')
        );
        $this->assertStringContainsString(
            'href="https://example.com/tour-van-hoa"',
            (string) data_get(TourCategory::query()->where('slug', 'tour-van-hoa')->firstOrFail()->faq_items, '0.answer')
        );
        $this->assertSame(
            'Nên đi Hà Nội mùa nào?',
            data_get(Destination::query()->where('slug', 'ha-noi')->firstOrFail()->faq_items, '0.question')
        );
        $this->assertStringContainsString(
            'href="https://example.com/ha-noi-mua-thu"',
            (string) data_get(Destination::query()->where('slug', 'ha-noi')->firstOrFail()->faq_items, '0.answer')
        );
        $this->assertSame('4.8', (string) TourCategory::query()->where('slug', 'tour-van-hoa')->firstOrFail()->rating_average);
        $savedDestination = Destination::query()->where('slug', 'ha-noi')->firstOrFail();
        $this->assertSame(86, $savedDestination->rating_count);
        $this->assertFalse((bool) $savedDestination->show_tours_on_page);
        $this->assertTrue((bool) $savedDestination->show_blogs_on_page);
    }

    public function test_regular_destination_requires_country_root(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ToursManager::class)
            ->set('destinationForm.name', 'Điểm đến thiếu quốc gia')
            ->call('saveDestination')
            ->assertHasErrors(['destinationForm.country_id']);

        $this->assertDatabaseMissing('destinations', [
            'slug' => 'diem-den-thieu-quoc-gia',
        ]);
    }

    public function test_destination_can_be_saved_as_country_root_without_parent_country(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ToursManager::class)
            ->set('destinationForm.name', 'Thái Lan')
            ->set('destinationForm.is_country_root', true)
            ->set('destinationForm.show_tours_on_page', false)
            ->set('destinationForm.show_blogs_on_page', true)
            ->call('saveDestination')
            ->assertHasNoErrors();

        $countryRoot = Destination::query()->where('slug', 'du-lich-thai-lan')->firstOrFail();

        $this->assertTrue((bool) $countryRoot->is_country_root);
        $this->assertNull($countryRoot->country_id);
        $this->assertFalse((bool) $countryRoot->show_tours_on_page);
        $this->assertTrue((bool) $countryRoot->show_blogs_on_page);
    }

    public function test_country_root_destination_save_ignores_stale_parent_country_state(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $countryRoot = Destination::query()->countryRoots()->firstOrFail();
        $staleRegularDestination = Destination::query()->create([
            'name' => 'Điểm đến state cũ',
            'slug' => 'diem-den-state-cu',
            'country_id' => $countryRoot->getKey(),
            'is_country_root' => false,
            'status' => 'published',
        ]);

        Livewire::test(ToursManager::class)
            ->set('destinationForm.name', 'Lào kiểm tra state cũ')
            ->set('destinationForm.is_country_root', true)
            ->set('destinationForm.country_id', $staleRegularDestination->getKey())
            ->call('saveDestination')
            ->assertHasNoErrors();

        $savedCountryRoot = Destination::query()->where('slug', 'du-lich-lao-kiem-tra-state-cu')->firstOrFail();

        $this->assertTrue((bool) $savedCountryRoot->is_country_root);
        $this->assertNull($savedCountryRoot->country_id);
    }

    public function test_country_root_destination_editor_exposes_page_display_flags(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $countryRoot = Destination::query()->countryRoots()->firstOrFail();

        $this->get(route('admin.tours.destinations.edit', $countryRoot))
            ->assertOk()
            ->assertSeeText('Là quốc gia root')
            ->assertSeeText('Hiển thị tour trên trang điểm đến')
            ->assertSeeText('Hiển thị blog trên trang điểm đến');
    }

    public function test_tour_can_store_rating_summary_itinerary_pricing_and_tour_terms_config_for_frontsite_schema(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ToursManager::class)
            ->set('form.title', 'Hà Nội cuối tuần')
            ->set('form.status', 'published')
            ->set('form.rating_average', 4.9)
            ->set('form.rating_count', 214)
            ->set('form.itinerary_items', [
                [
                    'title' => 'Ngày 1 - Phố cổ',
                    'content' => '<p>Tham quan <a href="https://example.com/pho-co">phố cổ</a> và khu trung tâm.</p>',
                ],
            ])
            ->set('form.pricing_items', [
                [
                    'label' => 'Giá từ',
                    'price' => '5.990.000 đ',
                ],
            ])
            ->set('form.tour_terms_items', [
                [
                    'question' => 'Điều khoản riêng cho tour test',
                    'answer' => '<p>Nội dung điều khoản riêng. Xem thêm <a href="https://example.com/dieu-khoan-rieng">chi tiết</a>.</p>',
                ],
            ])
            ->call('saveTour')
            ->assertHasNoErrors();

        $tour = \Src\Domains\Cms\Models\Tour::query()->where('slug', 'ha-noi-cuoi-tuan')->firstOrFail();

        $this->assertSame('4.9', (string) $tour->rating_average);
        $this->assertSame(214, $tour->rating_count);
        $this->assertSame('Ngày 1 - Phố cổ', data_get($tour->itinerary, '0.title'));
        $this->assertStringContainsString('href="https://example.com/pho-co"', (string) data_get($tour->itinerary, '0.content'));
        $this->assertSame('Giá từ', data_get($tour->pricing_table, '0.label'));
        $this->assertSame('5.990.000 đ', data_get($tour->pricing_table, '0.price'));
        $this->assertSame('Điều khoản riêng cho tour test', data_get($tour->tour_terms_items, '0.question'));
        $this->assertStringContainsString('href="https://example.com/dieu-khoan-rieng"', (string) data_get($tour->tour_terms_items, '0.answer'));
    }

    public function test_tour_can_auto_generate_slug_from_title_when_left_blank(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ToursManager::class)
            ->set('form.title', 'Tour Seoul mùa lá đỏ')
            ->set('form.slug', '')
            ->set('form.status', 'draft')
            ->call('saveTour')
            ->assertHasNoErrors();

        $tour = \Src\Domains\Cms\Models\Tour::query()->where('title', 'Tour Seoul mùa lá đỏ')->firstOrFail();

        $this->assertSame('tour-seoul-mua-la-do', $tour->slug);
    }

    public function test_tour_editor_can_store_review_batches_and_render_qr_links(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(ToursManager::class)
            ->set('form.title', 'Tour QR đánh giá')
            ->set('form.status', 'published')
            ->set('form.review_batches', [[
                'id' => null,
                'tour_departure_id' => '',
                'departure_date' => '2026-06-20',
                'label' => 'Đoàn khởi hành 20/06',
                'enabled' => true,
                'password' => '',
                'token' => '',
                'sort_order' => 0,
            ]])
            ->call('saveTour')
            ->assertHasNoErrors();

        $tour = Tour::query()->where('slug', 'tour-qr-danh-gia')->firstOrFail();
        $batch = TourReviewBatch::query()->where('tour_id', $tour->id)->firstOrFail();

        $this->assertTrue($tour->review_submission_enabled);
        $this->assertNull($tour->review_submission_password);
        $this->assertSame($batch->token, $tour->review_submission_token);
        $this->assertSame('Đoàn khởi hành 20/06', $batch->label);
        $this->assertNull($batch->password);

        Livewire::test(ToursManager::class)
            ->call('editTour', $tour->id)
            ->set('form.review_batches', [[
                'id' => $batch->id,
                'tour_departure_id' => '',
                'departure_date' => '2026-06-20',
                'label' => 'Đoàn khởi hành 20/06 cập nhật',
                'enabled' => true,
                'password' => 'HDT-2006',
                'token' => $batch->token,
                'sort_order' => 0,
            ]])
            ->call('saveTour')
            ->assertHasNoErrors();

        $batch->refresh();
        $tour->refresh();

        $this->assertSame('Đoàn khởi hành 20/06 cập nhật', $batch->label);
        $this->assertSame('HDT-2006', $batch->password);
        $this->assertSame($batch->token, $tour->review_submission_token);

        $this->get(route('admin.tours.edit', $tour))
            ->assertOk()
            ->assertSee('QR đánh giá', false)
            ->assertSee(route('tour-reviews.public.show', ['tour' => $tour, 'token' => $batch->token]), false)
            ->assertSee('<svg', false);
    }

    public function test_tour_editor_hides_review_batch_panel_when_reviews_are_disabled(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        Config::set('travel_reviews.enabled', false);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $tour = Tour::query()->create([
            'title' => 'Tour tắt đánh giá',
            'slug' => 'tour-tat-danh-gia',
            'status' => 'published',
            'scope' => 'domestic',
        ]);

        $this->get(route('admin.tours.edit', $tour))
            ->assertOk()
            ->assertDontSee('Quản lý đánh giá')
            ->assertDontSee('Lượt đánh giá theo tour - ngày khởi hành');
    }

    public function test_sale_can_create_only_own_tour_with_phone_defaulted_from_user(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $sale = User::query()->create([
            'name' => 'Sale One',
            'email' => 'sale-one@example.com',
            'phone' => '0911 222 288',
            'password' => 'password',
        ]);
        $sale->assignRole('sale');

        $this->actingAs($sale);

        Livewire::test(ToursManager::class)
            ->set('form.title', 'Tour Nhật Bản mùa hoa')
            ->set('form.slug', '')
            ->set('form.status', 'draft')
            ->set('form.contact_phone', '')
            ->call('saveTour')
            ->assertHasNoErrors();

        $tour = Tour::query()->where('slug', 'tour-nhat-ban-mua-hoa')->firstOrFail();

        $this->assertSame($sale->id, $tour->managed_by_user_id);
        $this->assertSame('0911 222 288', $tour->contact_phone);
    }

    public function test_sale_cannot_open_or_update_tour_managed_by_another_user(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $firstSale = User::query()->create([
            'name' => 'Sale One',
            'email' => 'sale-one@example.com',
            'phone' => '0911 222 288',
            'password' => 'password',
        ]);
        $firstSale->assignRole('sale');

        $secondSale = User::query()->create([
            'name' => 'Sale Two',
            'email' => 'sale-two@example.com',
            'password' => 'password',
        ]);
        $secondSale->assignRole('sale');

        $ownTour = Tour::query()->create([
            'title' => 'Tour của Sale One',
            'slug' => 'tour-cua-sale-one',
            'status' => 'draft',
            'scope' => \Src\Domains\Cms\Enums\TourScope::Domestic->value,
            'managed_by_user_id' => $firstSale->id,
        ]);

        $otherTour = Tour::query()->create([
            'title' => 'Tour của Sale Two',
            'slug' => 'tour-cua-sale-two',
            'status' => 'draft',
            'scope' => \Src\Domains\Cms\Enums\TourScope::Domestic->value,
            'managed_by_user_id' => $secondSale->id,
        ]);

        $this->actingAs($firstSale);

        $this->get(route('admin.tours'))
            ->assertOk()
            ->assertSeeText($ownTour->title)
            ->assertDontSeeText($otherTour->title);

        $this->get(route('admin.tours.edit', $otherTour))
            ->assertNotFound();
    }

    public function test_admin_can_filter_tours_by_manager(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();

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

        Tour::query()->create([
            'title' => 'Tour Sale One',
            'slug' => 'tour-sale-one',
            'status' => 'draft',
            'scope' => \Src\Domains\Cms\Enums\TourScope::Domestic->value,
            'managed_by_user_id' => $firstSale->id,
        ]);

        Tour::query()->create([
            'title' => 'Tour Sale Two',
            'slug' => 'tour-sale-two',
            'status' => 'draft',
            'scope' => \Src\Domains\Cms\Enums\TourScope::Domestic->value,
            'managed_by_user_id' => $secondSale->id,
        ]);

        Tour::query()->create([
            'title' => 'Tour chưa gán Sale',
            'slug' => 'tour-chua-gan-sale',
            'status' => 'draft',
            'scope' => \Src\Domains\Cms\Enums\TourScope::Domestic->value,
        ]);

        $this->actingAs($admin);

        Livewire::test(ToursManager::class)
            ->assertSee('Tất cả người phụ trách')
            ->set('managerFilter', (string) $firstSale->id)
            ->assertSee('Tour Sale One')
            ->assertDontSee('Tour Sale Two')
            ->assertDontSee('Tour chưa gán Sale')
            ->set('managerFilter', '0')
            ->assertSee('Tour chưa gán Sale')
            ->assertDontSee('Tour Sale One');
    }

    public function test_staff_filter_options_ignore_inactive_sale_accounts(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();

        $activeSale = User::query()->create([
            'name' => 'Sale Active Filter',
            'email' => 'sale-active-filter@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $activeSale->assignRole('sale');

        $inactiveSale = User::query()->create([
            'name' => 'Sale Locked Filter',
            'email' => 'sale-locked-filter@example.com',
            'password' => 'password',
            'is_active' => false,
        ]);
        $inactiveSale->assignRole('sale');

        $this->actingAs($admin);

        Livewire::test(ToursManager::class)
            ->assertSee($activeSale->name)
            ->assertDontSee($inactiveSale->name)
            ->set('currentRouteName', 'admin.tours.create')
            ->assertSee('Chưa gán Sale phụ trách')
            ->assertSee($activeSale->name)
            ->assertDontSee($inactiveSale->name);
    }

    public function test_tour_list_taxonomy_filters_follow_selected_scope(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($admin);

        $domesticCategory = TourCategory::query()->create([
            'name' => 'QA chủ đề domestic',
            'slug' => 'qa-chu-de-domestic',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
        ]);
        $internationalCategory = TourCategory::query()->create([
            'name' => 'QA chủ đề international',
            'slug' => 'qa-chu-de-international',
            'scope' => TourScope::International->value,
            'status' => 'published',
        ]);
        $sharedCategory = TourCategory::query()->create([
            'name' => 'QA chủ đề dùng chung',
            'slug' => 'qa-chu-de-dung-chung',
            'scope' => null,
            'status' => 'published',
        ]);

        $vietnam = Destination::query()->countryRoots()->where('name', 'Việt Nam')->firstOrFail();
        $internationalCountry = Destination::query()->create([
            'name' => 'QA Quốc gia quốc tế',
            'slug' => 'du-lich-qa-quoc-gia-quoc-te',
            'scope' => TourScope::International->value,
            'status' => 'published',
            'is_country_root' => true,
        ]);
        $domesticDestination = Destination::query()->create([
            'name' => 'QA điểm đến domestic',
            'slug' => 'qa-diem-den-domestic',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
            'country_id' => $vietnam->getKey(),
        ]);
        $internationalDestination = Destination::query()->create([
            'name' => 'QA điểm đến international',
            'slug' => 'qa-diem-den-international',
            'scope' => TourScope::International->value,
            'status' => 'published',
            'country_id' => $internationalCountry->getKey(),
        ]);
        $sharedDestination = Destination::query()->create([
            'name' => 'QA điểm đến dùng chung',
            'slug' => 'qa-diem-den-dung-chung',
            'scope' => null,
            'status' => 'published',
            'country_id' => $vietnam->getKey(),
        ]);

        Livewire::test(ToursManager::class)
            ->set('currentRouteName', 'admin.tours')
            ->set('scopeFilter', TourScope::Domestic->value)
            ->assertSee($domesticCategory->name)
            ->assertSee($sharedCategory->name)
            ->assertDontSee($internationalCategory->name)
            ->assertSee('<optgroup label="Việt Nam">', false)
            ->assertSee($domesticDestination->name)
            ->assertSee($sharedDestination->name)
            ->assertDontSee($internationalDestination->name);
    }

    public function test_tour_editor_taxonomy_options_follow_form_scope(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($admin);

        $domesticCategory = TourCategory::query()->create([
            'name' => 'QA editor chủ đề domestic',
            'slug' => 'qa-editor-chu-de-domestic',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
        ]);
        $internationalCategory = TourCategory::query()->create([
            'name' => 'QA editor chủ đề international',
            'slug' => 'qa-editor-chu-de-international',
            'scope' => TourScope::International->value,
            'status' => 'published',
        ]);

        $vietnam = Destination::query()->countryRoots()->where('name', 'Việt Nam')->firstOrFail();
        $internationalCountry = Destination::query()->create([
            'name' => 'QA editor quốc gia quốc tế',
            'slug' => 'du-lich-qa-editor-quoc-gia-quoc-te',
            'scope' => TourScope::International->value,
            'status' => 'published',
            'is_country_root' => true,
        ]);
        $domesticDestination = Destination::query()->create([
            'name' => 'QA editor điểm đến domestic',
            'slug' => 'qa-editor-diem-den-domestic',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
            'country_id' => $vietnam->getKey(),
        ]);
        $internationalDestination = Destination::query()->create([
            'name' => 'QA editor điểm đến international',
            'slug' => 'qa-editor-diem-den-international',
            'scope' => TourScope::International->value,
            'status' => 'published',
            'country_id' => $internationalCountry->getKey(),
        ]);

        Livewire::test(ToursManager::class)
            ->set('currentRouteName', 'admin.tours.create')
            ->assertSee($domesticCategory->name)
            ->assertSee('<optgroup label="Việt Nam">', false)
            ->assertSee($domesticDestination->name)
            ->assertDontSee($internationalCategory->name)
            ->assertDontSee($internationalDestination->name)
            ->set('form.scope', TourScope::International->value)
            ->assertSee($internationalCategory->name)
            ->assertSee('<optgroup label="QA editor quốc gia quốc tế">', false)
            ->assertSee('<option value="'.$internationalCountry->getKey().'">QA editor quốc gia quốc tế (quốc gia)</option>', false)
            ->assertSee($internationalDestination->name)
            ->assertDontSee($domesticCategory->name)
            ->assertDontSee($domesticDestination->name)
            ->set('form.tour_category_id', $domesticCategory->getKey())
            ->set('form.destination_id', $domesticDestination->getKey())
            ->assertSee($domesticCategory->name)
            ->assertSee('<optgroup label="Việt Nam">', false)
            ->assertSee($domesticDestination->name);
    }

    public function test_tour_primary_destination_can_be_country_root(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($admin);

        $countryRoot = Destination::query()->create([
            'name' => 'QA quốc gia chọn chính',
            'slug' => 'du-lich-qa-quoc-gia-chon-chinh',
            'scope' => TourScope::International->value,
            'status' => 'published',
            'is_country_root' => true,
        ]);

        Livewire::test(ToursManager::class)
            ->set('currentRouteName', 'admin.tours.create')
            ->set('form.title', 'Tour chọn quốc gia chính')
            ->set('form.scope', TourScope::International->value)
            ->assertSee('<option value="'.$countryRoot->getKey().'">QA quốc gia chọn chính (quốc gia)</option>', false)
            ->set('form.destination_id', $countryRoot->getKey())
            ->call('saveTour')
            ->assertHasNoErrors();

        $tour = Tour::query()->where('slug', 'tour-chon-quoc-gia-chinh')->firstOrFail();

        $this->assertSame($countryRoot->getKey(), $tour->destination_id);
        $this->assertTrue($tour->destinations()->whereKey($countryRoot->getKey())->exists());
    }

    public function test_tour_contact_phone_falls_back_to_site_settings_when_user_phone_is_missing(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        SiteSetting::query()->updateOrCreate(['id' => 1], [
            'site_name' => 'Haidang Travel',
            'hotline' => '1900 2011',
            'phone' => '0911 222 288',
        ]);

        $sale = User::query()->create([
            'name' => 'Sale No Phone',
            'email' => 'sale-no-phone@example.com',
            'password' => 'password',
        ]);
        $sale->assignRole('sale');

        $this->actingAs($sale);

        Livewire::test(ToursManager::class)
            ->set('form.title', 'Tour fallback hotline')
            ->set('form.contact_phone', '')
            ->call('saveTour')
            ->assertHasNoErrors();

        $tour = Tour::query()->where('slug', 'tour-fallback-hotline')->firstOrFail();

        $this->assertSame('1900 2011', $tour->contact_phone);
    }

    public function test_admin_tour_save_dispatches_agency_push_with_deleted_departure_mapping(): void
    {
        $this->seed(CmsBootstrapSeeder::class);
        Bus::fake();

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $tour = Tour::query()->create([
            'title' => 'Tour đã map agency',
            'slug' => 'tour-da-map-agency',
            'status' => 'published',
            'scope' => \Src\Domains\Cms\Enums\TourScope::Domestic->value,
            'cta_mode' => 'book',
        ]);
        $departure = $tour->departures()->create([
            'departure_date' => '2026-06-20',
            'status' => 'published',
        ]);
        TourDepartureSyncState::query()->create([
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => $departure->getKey(),
            'source_tour_id' => 998,
            'tour_code' => 'HDL998',
            'source_startdate_id' => 23288,
            'last_synced_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(ToursManager::class)
            ->call('editTour', $tour->id)
            ->set('form.title', 'Tour đã map agency cập nhật')
            ->set('form.departures', [[
                'id' => null,
                'departure_date' => '2026-07-10',
                'return_date' => null,
                'departure_location' => '',
                'transport_label' => '',
                'standard_label' => 'Khách sạn 4 sao',
                'base_price' => 6990000,
                'sale_price' => 6490000,
                'available_slots' => 10,
                'pricing_note' => '',
                'status' => 'published',
                'is_featured' => false,
                'sort_order' => 0,
            ]])
            ->call('saveTour')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tour_agency_push_sync_runs', [
            'tour_id' => $tour->getKey(),
            'source_tour_id' => 998,
            'tour_code' => 'HDL998',
            'trigger' => 'tour_saved',
            'status' => TourAgencyPushSyncRun::STATUS_PENDING,
        ]);

        Bus::assertDispatched(PushTourToAgencyJob::class, function (PushTourToAgencyJob $job) use ($tour): bool {
            return $job->tourId === $tour->getKey()
                && $job->runId !== null
                && (int) data_get($job->deletedDepartures, '0.startdate_id') === 23288
                && (int) data_get($job->deletedDepartures, '0.tour_id') === 998
                && data_get($job->deletedDepartures, '0.tour_code') === 'HDL998';
        });
    }

    public function test_admin_can_choose_cms_to_agency_direction_from_tour_sync_picker(): void
    {
        Carbon::setTestNow('2026-05-23 09:30:00');
        $this->seed(CmsBootstrapSeeder::class);
        $this->configureAgencySyncSettings();
        Bus::fake();

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $tour = Tour::query()->create([
            'title' => 'Tour CMS cần gửi API Master Data DashBoard',
            'slug' => 'tour-cms-can-gui-agency',
            'status' => 'published',
            'scope' => \Src\Domains\Cms\Enums\TourScope::Domestic->value,
            'cta_mode' => 'book',
        ]);
        $tour->departures()->create([
            'departure_date' => '2026-07-10',
            'status' => 'published',
        ]);

        Http::fake([
            'https://agency.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => ['token' => 'staff-token'],
            ]),
            'https://agency.example.test/api/tour/agency/sync/tours*' => Http::response([
                'status' => 'success',
                'data' => [[
                    'tour_id' => 998,
                    'tour_name' => 'Tour đích API Master Data DashBoard',
                    'title' => 'Tour đích API Master Data DashBoard',
                    'tour_code' => 'HDL998',
                    'slug' => 'tour-dich-agency',
                ]],
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
        ]);

        $this->actingAs($admin);
        Log::spy();

        Livewire::test(ToursManager::class)
            ->call('openTourSyncPicker', $tour->id)
            ->assertSet('syncTourDirection', 'cms_to_agency')
            ->assertSee('Gửi CMS sang API Master Data DashBoard')
            ->set('syncSourceTourId', '998')
            ->call('pushSelectedTourToAgency')
            ->assertSet('syncTourPickerOpen', false);

        $this->assertDatabaseHas('tour_departure_sync_states', [
            'tour_id' => $tour->getKey(),
            'tour_departure_id' => null,
            'source_tour_id' => 998,
            'tour_code' => 'HDL998',
            'source_startdate_id' => null,
        ]);
        $this->assertDatabaseHas('tour_agency_push_sync_runs', [
            'tour_id' => $tour->getKey(),
            'source_tour_id' => 998,
            'tour_code' => 'HDL998',
            'trigger' => 'manual_picker',
            'status' => TourAgencyPushSyncRun::STATUS_PENDING,
        ]);
        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($tour, $admin): bool {
                return $message === 'tour_sync.master_data_dashboard.queued'
                    && (int) data_get($context, 'cms_tour_id') === $tour->getKey()
                    && (int) data_get($context, 'source_tour_id') === 998
                    && data_get($context, 'tour_code') === 'HDL998'
                    && data_get($context, 'trigger') === 'manual_picker'
                    && data_get($context, 'status') === TourAgencyPushSyncRun::STATUS_PENDING
                    && data_get($context, 'target') === 'api_master_data_dashboard'
                    && (int) data_get($context, 'created_by_user_id') === $admin->getKey();
            });
        Bus::assertDispatched(PushTourToAgencyJob::class, function (PushTourToAgencyJob $job) use ($tour): bool {
            return $job->tourId === $tour->getKey()
                && $job->runId !== null
                && $job->deletedDepartures === [];
        });

        Carbon::setTestNow();
    }

    public function test_admin_can_sync_future_tour_departures_from_agency_api(): void
    {
        Carbon::setTestNow('2026-05-21 10:00:00');
        $this->seed(CmsBootstrapSeeder::class);

        $this->configureAgencySyncSettings();
        Bus::fake();

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $tour = Tour::query()->create([
            'title' => 'Tour Đà Lạt mùa hoa CMS',
            'slug' => 'tour-da-lat-cms',
            'status' => 'published',
            'scope' => \Src\Domains\Cms\Enums\TourScope::Domestic->value,
            'transport' => 'Xe CMS',
            'standard_label' => 'Giữ tiêu chuẩn CMS',
            'duration_days' => 2,
            'duration_nights' => 1,
            'departure_schedules' => ['20/05/2026', 'Ghi chú giữ lại'],
        ]);
        $existingDeparture = $tour->departures()->create([
            'departure_date' => '2026-06-20',
            'return_date' => '2026-06-23',
            'departure_location' => 'Giữ điểm đi CMS',
            'transport_label' => 'Xe giường nằm',
            'standard_label' => 'Giữ tiêu chuẩn ngày CMS',
            'base_price' => 1110000,
            'sale_price' => 2220000,
            'available_slots' => 99,
            'pricing_note' => 'Giữ ghi chú CMS',
            'status' => 'published',
            'is_featured' => true,
            'sort_order' => 7,
        ]);

        Http::fake([
            'https://agency.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => ['token' => 'staff-token'],
            ]),
            'https://agency.example.test/api/tour/agency/sync/tours*' => Http::response([
                'status' => 'success',
                'data' => [[
                    'tour_id' => 998,
                    'tour_name' => 'Tour Đà Lạt mùa hoa',
                    'title' => 'Tour Đà Lạt mùa hoa',
                    'tour_code' => 'HDL998',
                    'slug' => 'tour-da-lat-mua-hoa',
                    'transport' => 'Máy bay',
                    'standard_label' => 'Resort 4 sao',
                    'duration_days' => 4,
                    'duration_nights' => 3,
                ]],
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
            'https://agency.example.test/api/tour/agency/sync/startdates*' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'startdate_id' => 23287,
                        'tour_id' => 998,
                        'tour_code' => 'HDL998',
                        'startdate' => '2026-05-21',
                        'traffic' => 'Xe giường nằm',
                        'available_seat' => 8,
                        'adult_price' => 5990000,
                        'price' => 5490000,
                    ],
                    [
                        'startdate_id' => 23288,
                        'tour_id' => 998,
                        'tour_code' => 'HDL998',
                        'startdate' => '2026-06-20',
                        'startdate_label' => 'Khách sạn 3 sao',
                        'traffic' => 'Xe giường nằm',
                        'available_seat' => 12,
                        'adult_price' => 5990000,
                        'price' => 5490000,
                        'notice_agency' => 'Giá áp dụng cho khách lẻ',
                        'status_label' => 'Mở bán',
                    ],
                    [
                        'startdate_id' => 23289,
                        'tour_id' => 998,
                        'tour_code' => 'HDL998',
                        'startdate' => '2026-07-15',
                        'traffic' => 'Xe ghế ngồi',
                        'price' => 3190000,
                    ],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
        ]);

        $this->actingAs($admin);
        Log::spy();

        Livewire::test(ToursManager::class)
            ->call('openTourSyncPicker', $tour->id)
            ->assertSet('syncTourPickerOpen', true)
            ->assertSet('syncSourceTours.0.tour_id', 998)
            ->set('syncSourceTourId', '998')
            ->call('syncSelectedTourFromWeb');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://agency.example.test/api/DashboardLogin'
            && $request['UserName'] === 'staff-sync'
            && $request['Password'] === 'secret');
        Http::assertSent(function ($request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_starts_with($request->url(), 'https://agency.example.test/api/tour/agency/sync/startdates')
                && (string) ($query['tour_id'] ?? '') === '998'
                && $request->hasHeader('Authorization', 'Bearer staff-token');
        });

        $departure = TourDeparture::query()->where('tour_id', $tour->id)->firstOrFail();

        $this->assertSame($existingDeparture->getKey(), $departure->getKey());
        $this->assertSame('2026-06-20', $departure->departure_date->toDateString());
        $this->assertSame('2026-06-23', $departure->return_date->toDateString());
        $this->assertSame('Giữ điểm đi CMS', $departure->departure_location);
        $this->assertSame('Xe giường nằm', $departure->transport_label);
        $this->assertSame('Giữ tiêu chuẩn ngày CMS', $departure->standard_label);
        $this->assertSame(5990000, $departure->base_price);
        $this->assertSame(2220000, $departure->sale_price);
        $this->assertSame(99, $departure->available_slots);
        $this->assertSame('Giữ ghi chú CMS', $departure->pricing_note);
        $this->assertSame('published', $departure->status);
        $this->assertTrue($departure->is_featured);
        $this->assertSame(7, $departure->sort_order);
        $this->assertSame(1, TourDeparture::query()->where('tour_id', $tour->id)->count());
        $this->assertSame(23288, TourDepartureSyncState::query()->where('tour_id', $tour->id)->firstOrFail()->source_startdate_id);
        $syncedTour = $tour->fresh();
        $this->assertSame('Tour Đà Lạt mùa hoa', $syncedTour->title);
        $this->assertSame('tour-da-lat-mua-hoa', $syncedTour->slug);
        $this->assertSame('Máy bay', $syncedTour->transport);
        $this->assertSame('Giữ tiêu chuẩn CMS', $syncedTour->standard_label);
        $this->assertSame(4, $syncedTour->duration_days);
        $this->assertSame(3, $syncedTour->duration_nights);
        $this->assertSame(['Ghi chú giữ lại', '20/06/2026'], $syncedTour->departure_schedules);
        $this->assertDatabaseMissing('tour_agency_push_sync_runs', [
            'tour_id' => $tour->getKey(),
        ]);
        Bus::assertNotDispatched(PushTourToAgencyJob::class);
        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($tour): bool {
                return $message === 'tour_sync.master_data_dashboard.pull_started'
                    && data_get($context, 'source') === 'api_master_data_dashboard'
                    && data_get($context, 'target') === 'haidangtravel_cms'
                    && data_get($context, 'direction') === 'agency_to_cms'
                    && (int) data_get($context, 'cms_tour_id') === $tour->getKey()
                    && (int) data_get($context, 'source_tour_id') === 998
                    && data_get($context, 'source_transport') === 'Máy bay'
                    && data_get($context, 'source_transport_field') === 'transport'
                    && data_get($context, 'source_transport_candidates.transport') === 'Máy bay';
            });
        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($tour): bool {
                return $message === 'tour_sync.master_data_dashboard.pull_finished'
                    && data_get($context, 'source') === 'api_master_data_dashboard'
                    && data_get($context, 'target') === 'haidangtravel_cms'
                    && data_get($context, 'direction') === 'agency_to_cms'
                    && (int) data_get($context, 'cms_tour_id') === $tour->getKey()
                    && (int) data_get($context, 'source_tour_id') === 998
                    && data_get($context, 'source_transport') === 'Máy bay'
                    && data_get($context, 'source_transport_field') === 'transport'
                    && data_get($context, 'source_transport_candidates.transport') === 'Máy bay'
                    && data_get($context, 'cms_transport') === 'Máy bay'
                    && (bool) data_get($context, 'tour_details_updated') === true;
            });

        Carbon::setTestNow();
    }

    public function test_agency_departure_sync_is_idempotent_by_startdate_id(): void
    {
        Carbon::setTestNow('2026-05-21 10:00:00');
        $this->seed(CmsBootstrapSeeder::class);

        $this->configureAgencySyncSettings();

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $tour = Tour::query()->create([
            'title' => 'Tour Seoul mùa lá đỏ',
            'slug' => 'tour-seoul-mua-la-do',
            'status' => 'published',
            'scope' => \Src\Domains\Cms\Enums\TourScope::International->value,
        ]);

        Http::fake([
            'https://agency.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => ['token' => 'staff-token'],
            ]),
            'https://agency.example.test/api/tour/agency/sync/tours*' => Http::response([
                'status' => 'success',
                'data' => [[
                    'tour_id' => 1003,
                    'tour_name' => 'Tour Seoul mùa lá đỏ',
                    'title' => 'Tour Seoul mùa lá đỏ',
                    'tour_code' => 'SEL1003',
                    'slug' => 'tour-seoul-mua-la-do',
                ]],
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
            'https://agency.example.test/api/tour/agency/sync/startdates*' => Http::response([
                'status' => 'success',
                'data' => [[
                    'startdate_id' => 24543,
                    'tour_id' => 1003,
                    'tour_code' => 'SEL1003',
                    'startdate' => '2026-10-10',
                    'traffic' => 'Máy bay',
                    'available_seat' => 4,
                    'adult_price' => 19990000,
                    'price' => 18990000,
                ]],
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
        ]);

        $this->actingAs($admin);

        Livewire::test(ToursManager::class)
            ->call('openTourSyncPicker', $tour->id)
            ->set('syncSourceTourId', '1003')
            ->call('syncSelectedTourFromWeb')
            ->call('openTourSyncPicker', $tour->id)
            ->set('syncSourceTourId', '1003')
            ->call('syncSelectedTourFromWeb');

        $this->assertSame(1, TourDeparture::query()->where('tour_id', $tour->id)->count());
        $this->assertSame(1, TourDepartureSyncState::query()->where('tour_id', $tour->id)->count());

        Carbon::setTestNow();
    }

    public function test_admin_can_refresh_cached_agency_tour_catalog(): void
    {
        Carbon::setTestNow('2026-05-22 10:00:00');
        $this->seed(CmsBootstrapSeeder::class);

        $this->configureAgencySyncSettings();

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $tour = Tour::query()->create([
            'title' => 'Tour cần map nguồn',
            'slug' => 'tour-can-map-nguon',
            'status' => 'published',
            'scope' => \Src\Domains\Cms\Enums\TourScope::Domestic->value,
        ]);

        Http::fake([
            'https://agency.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => ['token' => 'staff-token'],
            ]),
            'https://agency.example.test/api/tour/agency/sync/tours*' => Http::sequence()
                ->push([
                    'status' => 'success',
                    'data' => [[
                        'tour_id' => 5001,
                        'tour_name' => 'Tour cache hôm nay',
                        'tour_code' => 'CACHE5001',
                        'slug' => 'tour-cache-hom-nay',
                    ]],
                    'meta' => ['current_page' => 1, 'last_page' => 1],
                ])
                ->push([
                    'status' => 'success',
                    'data' => [[
                        'tour_id' => 5002,
                        'tour_name' => 'Tour vừa tải lại',
                        'tour_code' => 'CACHE5002',
                        'slug' => 'tour-vua-tai-lai',
                    ]],
                    'meta' => ['current_page' => 1, 'last_page' => 1],
                ]),
        ]);

        $this->actingAs($admin);

        Livewire::test(ToursManager::class)
            ->call('openTourSyncPicker', $tour->id)
            ->assertSet('syncSourceTours.0.tour_id', 5001)
            ->call('closeTourSyncPicker')
            ->call('openTourSyncPicker', $tour->id)
            ->assertSet('syncSourceTours.0.tour_id', 5001)
            ->call('refreshTourSyncSourceCatalog')
            ->assertSet('syncSourceTours.0.tour_id', 5002);

        $tourListRequests = Http::recorded(fn ($request, $response): bool => str_starts_with($request->url(), 'https://agency.example.test/api/tour/agency/sync/tours'));

        $this->assertCount(2, $tourListRequests);

        Carbon::setTestNow();
    }

    public function test_agency_tour_picker_supports_filtering_and_pagination(): void
    {
        Carbon::setTestNow('2026-05-22 10:00:00');
        $this->seed(CmsBootstrapSeeder::class);

        $this->configureAgencySyncSettings();

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $tour = Tour::query()->create([
            'title' => 'Tour cần chọn nguồn',
            'slug' => 'tour-can-chon-nguon',
            'status' => 'published',
            'scope' => \Src\Domains\Cms\Enums\TourScope::Domestic->value,
        ]);
        $sourceTours = collect(range(1, 25))
            ->map(fn (int $index): array => [
                'tour_id' => 6000 + $index,
                'tour_name' => sprintf('Tour nguồn %02d', $index),
                'tour_code' => sprintf('SRC%02d', $index),
                'slug' => sprintf('tour-nguon-%02d', $index),
                'status_label' => $index % 2 === 0 ? 'Tạm dừng' : 'Mở bán',
            ])
            ->all();

        Http::fake([
            'https://agency.example.test/api/DashboardLogin' => Http::response([
                'status' => 'success',
                'data' => ['token' => 'staff-token'],
            ]),
            'https://agency.example.test/api/tour/agency/sync/tours*' => Http::response([
                'status' => 'success',
                'data' => $sourceTours,
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
        ]);

        $this->actingAs($admin);

        Livewire::test(ToursManager::class)
            ->call('openTourSyncPicker', $tour->id)
            ->assertSee('Tour nguồn 01')
            ->assertDontSee('Tour nguồn 25')
            ->call('nextSyncSourcePage')
            ->assertSee('Tour nguồn 21')
            ->assertDontSee('Tour nguồn 01')
            ->set('syncSourceSearch', 'SRC25')
            ->assertSet('syncSourcePage', 1)
            ->assertSee('Tour nguồn 25')
            ->assertDontSee('Tour nguồn 21')
            ->set('syncSourceSearch', '')
            ->set('syncSourceStatusFilter', 'Tạm dừng')
            ->assertSet('syncSourcePage', 1)
            ->assertSee('Tour nguồn 02')
            ->assertDontSee('Tour nguồn 01')
            ->set('syncSourcePerPage', 10)
            ->assertSet('syncSourcePage', 1)
            ->assertSee('10 / trang');

        Carbon::setTestNow();
    }

    protected function configureAgencySyncSettings(): void
    {
        Cache::flush();

        config()->set('tour_sync.username', null);
        config()->set('tour_sync.password', null);
        config()->set('tour_sync.token', null);

        SiteSetting::query()->updateOrCreate(['id' => 1], [
            'site_name' => 'Haidang Travel',
            'customer_loyalty_api_base_url' => 'https://agency.example.test/api',
            'customer_loyalty_api_username' => 'staff-sync',
            'customer_loyalty_api_password' => 'secret',
            'customer_loyalty_api_token' => null,
            'customer_loyalty_api_token_expires_at' => null,
            'customer_loyalty_api_token_refreshed_at' => null,
        ]);

        app(SiteSettingsManager::class)->refresh();
    }
}
