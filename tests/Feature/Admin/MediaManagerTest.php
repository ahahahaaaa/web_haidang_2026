<?php

namespace Tests\Feature\Admin;

use App\Models\User;
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

        Livewire::test(\App\Livewire\Admin\Cms\MediaManager::class)
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
