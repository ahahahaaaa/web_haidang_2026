<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Cms\MediaManager;
use App\Models\SeoOptimizationAsset;
use App\Models\SeoOptimizationPage;
use App\Models\SeoOptimizationTask;
use App\Models\User;
use App\Services\Admin\MediaLibraryFileAuditService;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class MediaManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_media_manager_and_browser_endpoint(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $this->get(route('admin.media'))
            ->assertOk()
            ->assertSeeText('Quản lý media')
            ->assertSeeText('Kiểm tra file thất lạc');

        $this->getJson(route('admin.media.browser.images'))
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'filters' => ['collections', 'models'],
            ]);
    }

    public function test_admin_can_upload_image_from_media_browser_popup(): void
    {
        Storage::fake('public');

        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $response = $this->postJson(route('admin.media.browser.images.upload'), [
            'image' => UploadedFile::fake()->image('popup-upload.jpg', 1200, 900),
            'name' => 'Ảnh popup',
            'alt' => 'Mô tả popup',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ảnh popup')
            ->assertJsonPath('data.alt', 'Mô tả popup')
            ->assertJsonPath('data.collection_name', 'library');

        $this->assertDatabaseHas('media', [
            'collection_name' => 'library',
            'file_name' => 'popup-upload.jpg',
            'name' => 'Ảnh popup',
        ]);
    }

    public function test_media_manager_deletes_an_unreferenced_image(): void
    {
        Storage::fake('public');

        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $media = SiteSetting::query()->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('delete-unreferenced.jpg', 1200, 800))
            ->toMediaCollection('library', 'public');

        $this->actingAs($user);

        Livewire::test(MediaManager::class)
            ->set('selectedMediaId', $media->id)
            ->call('deleteMedia', $media->id)
            ->assertSet('selectedMediaId', null)
            ->assertSeeText('Đã xóa ảnh khỏi thư viện media.');

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_media_manager_keeps_an_image_referenced_by_an_seo_optimization_asset(): void
    {
        Storage::fake('public');

        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $media = SiteSetting::query()->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('delete-protected.jpg', 1200, 800))
            ->toMediaCollection('library', 'public');
        $page = SeoOptimizationPage::query()->create([
            'site_id' => 'haidang-test',
            'page_type' => 'service',
            'owner_type' => 'service',
            'owner_id' => '1',
            'path' => '/dich-vu/test',
            'title' => 'Trang dịch vụ test',
            'classification' => 'INDEXABLE',
            'source_version' => 'source-revision',
        ]);
        $task = SeoOptimizationTask::query()->create([
            'page_id' => $page->id,
            'requested_by' => $user->id,
            'status' => 'completed',
            'brief' => [],
            'snapshot' => [],
            'source_version' => 'source-revision',
            'strategy_revision' => 'strategy-revision',
            'idempotency_key' => 'media-manager-delete-protected',
            'request_hash' => hash('sha256', 'media-manager-delete-protected'),
        ]);
        $asset = SeoOptimizationAsset::query()->create([
            'task_id' => $task->id,
            'page_id' => $page->id,
            'media_id' => $media->id,
            'created_by' => $user->id,
            'source_revision' => 'source-revision',
            'source_type' => 'generated',
            'alt' => 'Ảnh đang được SEO Optimize sử dụng',
            'sha256' => hash('sha256', 'media-file'),
            'request_hash' => hash('sha256', 'media-request'),
            'manifest_hash' => hash('sha256', 'media-manifest'),
            'manifest' => ['media_id' => $media->id],
        ]);

        $this->actingAs($user);

        Livewire::test(MediaManager::class)
            ->set('selectedMediaId', $media->id)
            ->call('deleteMedia', $media->id)
            ->assertSet('selectedMediaId', $media->id)
            ->assertSeeText('Không thể xóa ảnh vì ảnh đang được dữ liệu khác tham chiếu, bao gồm hồ sơ SEO Optimize. Ảnh được giữ lại để bảo toàn dữ liệu liên quan.');

        $this->assertDatabaseHas('media', ['id' => $media->id]);
        $this->assertDatabaseHas('seo_optimization_assets', ['id' => $asset->id]);

        Storage::disk('public')->delete($media->getPathRelativeToRoot());

        $this->assertSame([
            'deleted_count' => 0,
            'protected_count' => 1,
        ], app(MediaLibraryFileAuditService::class)->deleteMissingOriginalImages(search: 'delete-protected'));
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_media_manager_can_audit_and_delete_records_with_missing_physical_files(): void
    {
        Storage::fake('public');

        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $settings = SiteSetting::query()->findOrFail(1);
        $healthyMedia = $settings
            ->addMedia(UploadedFile::fake()->image('audit-target-healthy.jpg', 1200, 800))
            ->usingName('audit-target healthy')
            ->usingFileName('audit-target-healthy.jpg')
            ->withCustomProperties(['alt' => 'Ảnh test còn file'])
            ->toMediaCollection('library', 'public');
        $missingMedia = $settings
            ->addMedia(UploadedFile::fake()->image('audit-target-missing.jpg', 1200, 800))
            ->usingName('audit-target missing')
            ->usingFileName('audit-target-missing.jpg')
            ->withCustomProperties(['alt' => 'Ảnh test mất file'])
            ->toMediaCollection('library', 'public');

        Storage::disk('public')->delete($missingMedia->getPathRelativeToRoot());

        $this->actingAs($user);

        Livewire::test(MediaManager::class)
            ->set('search', 'audit-target')
            ->call('auditMissingMediaFiles')
            ->assertSet('missingMediaAudit.scanned_count', 2)
            ->assertSet('missingMediaAudit.healthy_count', 1)
            ->assertSet('missingMediaAudit.missing_count', 1)
            ->assertSet('missingMediaAudit.items.0.id', $missingMedia->id)
            ->assertSet('missingMediaAudit.items.0.path', $missingMedia->getPathRelativeToRoot())
            ->call('deleteMissingMediaFiles')
            ->assertSet('missingMediaAudit', null);

        $this->assertDatabaseHas('media', [
            'id' => $healthyMedia->id,
        ]);

        $this->assertDatabaseMissing('media', [
            'id' => $missingMedia->id,
        ]);
    }
}
