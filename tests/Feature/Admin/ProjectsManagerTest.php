<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Cms\ProjectsManager;
use App\Support\ContentGallery;
use App\Models\User;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Domains\Cms\Models\Project;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class ProjectsManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_edit_route_renders_existing_project_and_delete_confirmation(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'biet-thu-lakeview')->firstOrFail();

        $response = $this->actingAs($user)->get(route('admin.projects.edit', $project));

        $response
            ->assertOk()
            ->assertSee('wire:submit.prevent="saveProject"', false)
            ->assertSee("wire:click=\"deleteProject({$project->id})\"", false)
            ->assertSee('Xóa dự án')
            ->assertSee('wire:confirm="Bạn có chắc chắn muốn xóa dự án này? Hành động này không thể hoàn tác."', false);
    }

    public function test_project_cover_can_use_image_selected_from_media_library(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'biet-thu-lakeview')->firstOrFail();
        $libraryMedia = SiteSetting::query()
            ->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('project-library-cover.jpg', 1200, 800))
            ->usingName('Project library cover')
            ->usingFileName('project-library-cover.jpg')
            ->withCustomProperties(['alt' => 'Alt từ media library'])
            ->toMediaCollection('library', 'public');

        $this->actingAs($user);

        Livewire::test(ProjectsManager::class)
            ->call('editProject', $project->id)
            ->call('selectCoverLibraryMedia', $libraryMedia->id, 'Alt cover dự án từ popup')
            ->call('saveProject')
            ->assertHasNoErrors();

        $project->refresh();
        $media = $project->getFirstMedia('cover');

        $this->assertNotNull($media);
        $this->assertSame('Alt cover dự án từ popup', $project->cover_alt);
        $this->assertSame($libraryMedia->id, (int) data_get($media?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Alt cover dự án từ popup', (string) data_get($media?->custom_properties, 'alt'));
    }

    public function test_uploaded_project_cover_takes_priority_over_selected_library_media(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'biet-thu-lakeview')->firstOrFail();
        $libraryMedia = SiteSetting::query()
            ->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('project-library-priority.jpg', 1200, 800))
            ->usingName('Project library priority')
            ->usingFileName('project-library-priority.jpg')
            ->withCustomProperties(['alt' => 'Alt library'])
            ->toMediaCollection('library', 'public');

        $this->actingAs($user);

        Livewire::test(ProjectsManager::class)
            ->call('editProject', $project->id)
            ->call('selectCoverLibraryMedia', $libraryMedia->id, 'Alt library')
            ->set('coverUpload', UploadedFile::fake()->image('project-upload-priority.jpg', 1200, 800))
            ->set('form.cover_alt', 'Alt upload')
            ->call('saveProject')
            ->assertHasNoErrors();

        $project->refresh();
        $media = $project->getFirstMedia('cover');

        $this->assertNotNull($media);
        $this->assertSame('project-upload-priority.jpg', $media?->file_name);
        $this->assertNull(data_get($media?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Alt upload', $project->cover_alt);
        $this->assertSame('Alt upload', (string) data_get($media?->custom_properties, 'alt'));
    }

    public function test_project_gallery_can_store_library_image_and_mp4_video(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'biet-thu-lakeview')->firstOrFail();
        $libraryMedia = SiteSetting::query()
            ->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('project-gallery-library.jpg', 1200, 800))
            ->usingName('Project gallery library')
            ->usingFileName('project-gallery-library.jpg')
            ->withCustomProperties(['alt' => 'Alt gallery'])
            ->toMediaCollection('library', 'public');

        $this->actingAs($user);

        $component = Livewire::test(ProjectsManager::class)
            ->call('editProject', $project->id)
            ->call('addGalleryItem', 'image')
            ->call('addGalleryItem', 'mp4');

        $gallery = $component->get('form.gallery');
        $imageUuid = data_get($gallery, '0.uuid');

        $component
            ->call('selectGalleryLibraryMedia', $imageUuid, $libraryMedia->id, 'Alt gallery dự án từ popup')
            ->set('form.gallery.1.title', 'Video tiến độ')
            ->set('form.gallery.1.video_url', 'https://cdn.example.com/project-progress.mp4')
            ->call('saveProject')
            ->assertHasNoErrors();

        $project->refresh();
        $media = $project->getFirstMedia(ContentGallery::projectCollection($imageUuid));

        $this->assertNotNull($media);
        $this->assertSame($libraryMedia->id, (int) data_get($media?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Alt gallery dự án từ popup', (string) data_get($media?->custom_properties, 'alt'));
        $this->assertSame('mp4', data_get($project->gallery, '1.type'));
        $this->assertSame('https://cdn.example.com/project-progress.mp4', data_get($project->gallery, '1.video_url'));
    }

    public function test_project_manager_can_store_faq_items_and_related_questions(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'biet-thu-lakeview')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(ProjectsManager::class)
            ->call('editProject', $project->id)
            ->set('form.faq_items.0.question', 'FAQ dự án test')
            ->set('form.faq_items.0.answer', 'Nội dung FAQ dự án test')
            ->set('form.related_questions.0', 'Câu hỏi liên quan dự án số 1')
            ->call('addRelatedQuestion')
            ->set('form.related_questions.1', 'Câu hỏi liên quan dự án số 2')
            ->call('saveProject')
            ->assertHasNoErrors();

        $project->refresh();

        $this->assertSame('FAQ dự án test', data_get($project->faq_items, '0.question'));
        $this->assertSame('Nội dung FAQ dự án test', data_get($project->faq_items, '0.answer'));
        $this->assertSame('Câu hỏi liên quan dự án số 1', data_get($project->related_questions, '0'));
        $this->assertSame('Câu hỏi liên quan dự án số 2', data_get($project->related_questions, '1'));
    }

    public function test_project_create_can_store_uploaded_image_youtube_and_mp4_gallery_items(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user);

        $component = Livewire::test(ProjectsManager::class)
            ->set('form.title', 'Nhà mẫu ven sông')
            ->set('form.slug', 'nha-mau-ven-song')
            ->set('form.location', 'Quận 2')
            ->call('addGalleryItem', 'image')
            ->call('addGalleryItem', 'youtube')
            ->call('addGalleryItem', 'mp4');

        $gallery = $component->get('form.gallery');
        $imageUuid = data_get($gallery, '0.uuid');

        $component
            ->set('galleryUploads.0', UploadedFile::fake()->image('project-gallery-upload.jpg', 1200, 800))
            ->set('form.gallery.0.title', 'Phối cảnh mặt tiền')
            ->set('form.gallery.0.image_alt', 'Ảnh phối cảnh nhà mẫu ven sông')
            ->set('form.gallery.1.title', 'Video walkthrough')
            ->set('form.gallery.1.video_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
            ->set('form.gallery.2.title', 'Video timelapse')
            ->set('form.gallery.2.video_url', 'https://cdn.example.com/project-gallery.mp4')
            ->call('saveProject')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.projects.edit', 'nha-mau-ven-song'));

        $project = Project::query()->where('slug', 'nha-mau-ven-song')->firstOrFail();
        $imageMedia = $project->getFirstMedia(ContentGallery::projectCollection($imageUuid));

        $this->assertNotNull($imageMedia);
        $this->assertSame('project-gallery-upload.jpg', $imageMedia?->file_name);
        $this->assertSame('Ảnh phối cảnh nhà mẫu ven sông', (string) data_get($imageMedia?->custom_properties, 'alt'));
        $this->assertSame('youtube', data_get($project->gallery, '1.type'));
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', data_get($project->gallery, '1.video_url'));
        $this->assertSame('mp4', data_get($project->gallery, '2.type'));
        $this->assertSame('https://cdn.example.com/project-gallery.mp4', data_get($project->gallery, '2.video_url'));
    }

    public function test_project_save_redirects_to_slug_based_edit_route(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'biet-thu-lakeview')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(ProjectsManager::class)
            ->call('editProject', $project->id)
            ->set('form.title', 'Căn hộ mẫu Riverside Premium')
            ->set('form.slug', 'can-ho-mau-riverside-premium')
            ->call('saveProject')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.projects.edit', $project->fresh()));
    }

    public function test_project_editor_livewire_updates_keep_editor_view_when_adding_gallery_item(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'biet-thu-lakeview')->firstOrFail();

        $response = $this->actingAs($user)->get(route('admin.projects.edit', $project));
        $response->assertOk();

        [$snapshot, $updateUri] = $this->extractLivewirePayload($response->getContent());

        $updateResponse = $this->postJson($updateUri, [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => (object) [],
                'calls' => [[
                    'path' => '',
                    'method' => 'addGalleryItem',
                    'params' => ['image'],
                ]],
            ]],
        ], [
            'X-Livewire' => 'true',
        ]);

        $updateResponse->assertOk();

        $html = (string) data_get($updateResponse->json(), 'components.0.effects.html', '');

        $this->assertStringContainsString('Biên tập dự án', $html);
        $this->assertStringContainsString('Gallery item 1', $html);
        $this->assertStringContainsString('Loại media', $html);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractLivewirePayload(string $html): array
    {
        preg_match('/wire:snapshot="([^"]+)"/', $html, $snapshotMatch);
        preg_match('/data-update-uri="([^"]+)"/', $html, $updateUriMatch);

        $this->assertNotEmpty($snapshotMatch[1] ?? null);
        $this->assertNotEmpty($updateUriMatch[1] ?? null);

        return [
            html_entity_decode($snapshotMatch[1], ENT_QUOTES),
            html_entity_decode($updateUriMatch[1], ENT_QUOTES),
        ];
    }
}
