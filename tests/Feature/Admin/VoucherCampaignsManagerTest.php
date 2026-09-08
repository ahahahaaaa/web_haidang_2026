<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Cms\VoucherCampaignsManager;
use App\Livewire\Admin\Cms\VoucherCampaignCodesManager;
use App\Models\User;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Src\Domains\Cms\Enums\TravelInquirySource;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\TravelInquiry;
use Src\Domains\Cms\Models\VoucherCampaign;
use Src\Domains\Cms\Models\VoucherCode;
use Tests\TestCase;

class VoucherCampaignsManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_voucher_campaign_editor_uses_media_popup_for_frame_image(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $campaign = $this->createVoucherCampaign();

        $response = $this->actingAs($user)->get(route('admin.voucher-campaigns.edit', ['campaign' => $campaign]));

        $response
            ->assertOk()
            ->assertSee('data-admin-media-picker-trigger', false)
            ->assertSee('data-pick-method="selectFrameImageFromLibrary"', false)
            ->assertSee('placeholder="dd/mm/yyyy"', false)
            ->assertSeeText('Chọn từ Media popup');

        Livewire::test(VoucherCampaignsManager::class, ['campaign' => $campaign])
            ->assertSet('form.starts_at', $campaign->starts_at?->format('d/m/Y'))
            ->assertSet('form.ends_at', $campaign->ends_at?->format('d/m/Y'))
            ->assertSet('form.code_valid_until', $campaign->code_valid_until?->format('d/m/Y'));
    }

    public function test_voucher_campaign_frame_image_can_use_media_popup_selection(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $campaign = $this->createVoucherCampaign();
        $libraryMedia = SiteSetting::query()
            ->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('voucher-frame-library.jpg', 1200, 630))
            ->usingName('Voucher frame library')
            ->usingFileName('voucher-frame-library.jpg')
            ->withCustomProperties(['alt' => 'Frame voucher từ media library'])
            ->toMediaCollection('library', 'public');

        $this->actingAs($user);

        Livewire::test(VoucherCampaignsManager::class, ['campaign' => $campaign])
            ->call('selectFrameImageFromLibrary', $libraryMedia->id)
            ->call('save')
            ->assertHasNoErrors();

        $campaign->refresh();

        $this->assertSame($libraryMedia->getUrl(), $campaign->frame_image_url);
    }

    public function test_voucher_campaign_codes_screen_shows_issued_codes(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $campaign = $this->createVoucherCampaign();
        $otherCampaign = $this->createVoucherCampaign([
            'title' => 'Voucher campaign second',
            'slug' => 'voucher-campaign-second',
        ]);
        $inquiry = TravelInquiry::query()->create([
            'source' => TravelInquirySource::General->value,
            'context_title' => 'Voucher du lịch 200.000đ',
            'status' => 'new',
            'customer_name' => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
            'customer_email' => 'nguyenvana@example.com',
            'page_url' => '/voucher-du-lich',
            'meta' => [
                'voucher' => [
                    'code' => 'TEST-CLAIMED-001',
                    'campaign_slug' => $campaign->slug,
                ],
            ],
        ]);

        VoucherCode::query()->create([
            'voucher_campaign_id' => $campaign->id,
            'travel_inquiry_id' => $inquiry->id,
            'code' => 'TEST-CLAIMED-001',
            'code_set_version' => $campaign->code_set_version,
            'status' => VoucherCode::STATUS_CLAIMED,
            'claimed_at' => now()->subMinutes(5),
        ]);

        VoucherCode::query()->create([
            'voucher_campaign_id' => $campaign->id,
            'code' => 'TEST-AVAILABLE-001',
            'code_set_version' => $campaign->code_set_version,
            'status' => VoucherCode::STATUS_AVAILABLE,
        ]);

        VoucherCode::query()->create([
            'voucher_campaign_id' => $otherCampaign->id,
            'code' => 'TEST-CLAIMED-SECOND',
            'code_set_version' => $otherCampaign->code_set_version,
            'status' => VoucherCode::STATUS_CLAIMED,
            'claimed_at' => now()->subMinutes(4),
        ]);

        $response = $this->actingAs($user)->get(route('admin.voucher-codes'));

        $response
            ->assertOk()
            ->assertSeeText('Mã voucher đã cấp phát')
            ->assertSeeText('Xuất Excel')
            ->assertSeeText('TEST-CLAIMED-001')
            ->assertSeeText('TEST-CLAIMED-SECOND')
            ->assertSeeText('Voucher campaign test')
            ->assertSeeText('Voucher campaign second')
            ->assertSeeText('Nguyễn Văn A')
            ->assertSeeText('0901234567')
            ->assertSeeText('Đã phát')
            ->assertDontSeeText('TEST-AVAILABLE-001');

        Livewire::test(VoucherCampaignCodesManager::class)
            ->set('search', 'SECOND')
            ->assertSee('TEST-CLAIMED-SECOND')
            ->assertDontSee('TEST-CLAIMED-001');
    }

    public function test_voucher_campaign_code_can_be_marked_used_with_note(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $campaign = $this->createVoucherCampaign();
        $code = VoucherCode::query()->create([
            'voucher_campaign_id' => $campaign->id,
            'code' => 'TEST-CLAIMED-002',
            'code_set_version' => $campaign->code_set_version,
            'status' => VoucherCode::STATUS_CLAIMED,
            'claimed_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($user);

        Livewire::test(VoucherCampaignCodesManager::class)
            ->set('usageNotes.'.$code->id, 'Khách đã dùng cho tour Phú Quốc')
            ->call('markVoucherCodeUsed', $code->id)
            ->assertHasNoErrors();

        $code->refresh();

        $this->assertSame(VoucherCode::STATUS_USED, $code->status);
        $this->assertSame('Khách đã dùng cho tour Phú Quốc', $code->used_note);
        $this->assertSame($user->id, $code->used_by);
        $this->assertNotNull($code->used_at);
    }

    public function test_expired_voucher_campaign_code_cannot_be_marked_used(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $campaign = $this->createVoucherCampaign();
        $campaign->forceFill(['code_valid_until' => now()->subMinute()])->save();
        $code = VoucherCode::query()->create([
            'voucher_campaign_id' => $campaign->id,
            'code' => 'TEST-EXPIRED-001',
            'code_set_version' => $campaign->code_set_version,
            'status' => VoucherCode::STATUS_CLAIMED,
            'claimed_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($user);

        Livewire::test(VoucherCampaignCodesManager::class)
            ->set('usageNotes.'.$code->id, 'Khách báo đã dùng sau hạn')
            ->call('markVoucherCodeUsed', $code->id);

        $code->refresh();

        $this->assertSame(VoucherCode::STATUS_CLAIMED, $code->status);
        $this->assertNull($code->used_at);
        $this->assertNull($code->used_by);
        $this->assertNull($code->used_note);

        $this->actingAs($user)
            ->get(route('admin.voucher-codes'))
            ->assertOk()
            ->assertSeeText('Đã quá hạn sử dụng');
    }

    public function test_voucher_campaign_codes_can_be_exported_to_excel(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $campaign = $this->createVoucherCampaign();
        $otherCampaign = $this->createVoucherCampaign([
            'title' => 'Voucher campaign export second',
            'slug' => 'voucher-campaign-export-second',
        ]);
        VoucherCode::query()->create([
            'voucher_campaign_id' => $campaign->id,
            'code' => 'TEST-EXPORT-001',
            'code_set_version' => $campaign->code_set_version,
            'status' => VoucherCode::STATUS_USED,
            'claimed_at' => now()->subMinutes(5),
            'used_at' => now(),
            'used_by' => $user->id,
            'used_note' => 'Đã dùng trong booking test',
        ]);
        VoucherCode::query()->create([
            'voucher_campaign_id' => $otherCampaign->id,
            'code' => 'TEST-EXPORT-002',
            'code_set_version' => $otherCampaign->code_set_version,
            'status' => VoucherCode::STATUS_CLAIMED,
            'claimed_at' => now()->subMinutes(3),
        ]);

        $response = $this->actingAs($user)->get(route('admin.voucher-codes.export'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('TEST-EXPORT-001', $content);
        $this->assertStringContainsString('TEST-EXPORT-002', $content);
        $this->assertStringContainsString('Đã sử dụng', $content);
        $this->assertStringContainsString($campaign->code_valid_until?->format('d/m/Y'), $content);
        $this->assertStringContainsString('Đã dùng trong booking test', $content);

        $filteredResponse = $this->actingAs($user)->get(route('admin.voucher-codes.export', ['search' => 'EXPORT-002']));
        $filteredResponse->assertOk();
        $filteredContent = $filteredResponse->streamedContent();

        $this->assertStringContainsString('TEST-EXPORT-002', $filteredContent);
        $this->assertStringNotContainsString('TEST-EXPORT-001', $filteredContent);
    }

    public function test_voucher_campaign_editor_saves_code_usage_deadline(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $campaign = $this->createVoucherCampaign();

        $this->actingAs($user);

        Livewire::test(VoucherCampaignsManager::class, ['campaign' => $campaign])
            ->set('form.ends_at', '07/07/2026')
            ->set('form.code_valid_until', '20/07/2026')
            ->call('save')
            ->assertHasNoErrors();

        $campaign->refresh();

        $this->assertSame('2026-07-20 23:59:59', $campaign->code_valid_until?->format('Y-m-d H:i:s'));
    }

    public function test_voucher_campaign_can_be_deleted_by_admin(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $campaign = $this->createVoucherCampaign();
        $code = VoucherCode::query()->create([
            'voucher_campaign_id' => $campaign->id,
            'code' => 'TEST-DELETE-001',
            'code_set_version' => $campaign->code_set_version,
            'status' => VoucherCode::STATUS_AVAILABLE,
        ]);

        $this->actingAs($user);

        Livewire::test(VoucherCampaignsManager::class, ['campaign' => $campaign])
            ->call('deleteCampaign', $campaign->id)
            ->assertRedirect(route('admin.voucher-campaigns', absolute: false));

        $this->assertModelMissing($campaign);
        $this->assertModelMissing($code);
    }

    public function test_landing_page_can_only_be_assigned_to_one_voucher_campaign(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $campaign = $this->createVoucherCampaign();

        $this->actingAs($user);

        Livewire::test(VoucherCampaignsManager::class)
            ->set('form.title', 'Duplicate landing voucher campaign')
            ->set('form.slug', 'duplicate-landing-voucher-campaign')
            ->set('form.landing_page_id', $campaign->landing_page_id)
            ->set('form.code_prefix', 'DUP')
            ->set('form.code_quantity', 5)
            ->call('save')
            ->assertHasErrors(['form.landing_page_id']);

        $this->assertSame(
            1,
            VoucherCampaign::query()->where('landing_page_id', $campaign->landing_page_id)->count(),
        );
    }

    protected function createVoucherCampaign(array $overrides = []): VoucherCampaign
    {
        $campaignSlug = (string) ($overrides['slug'] ?? 'voucher-campaign-test');

        $landing = LandingPage::query()->create([
            'title' => 'Landing '.$campaignSlug,
            'slug' => 'landing-'.$campaignSlug,
            'is_active' => true,
            'template_key' => 'generic',
        ]);

        return VoucherCampaign::query()->create([
            'landing_page_id' => $landing->getKey(),
            'title' => 'Voucher campaign test',
            'slug' => $campaignSlug,
            'description' => 'Voucher campaign test.',
            'code_prefix' => 'TEST',
            'code_quantity' => 10,
            'code_set_version' => (string) Str::uuid(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'code_valid_until' => now()->addDays(10)->endOfDay(),
            'is_active' => true,
            ...$overrides,
        ]);
    }
}
