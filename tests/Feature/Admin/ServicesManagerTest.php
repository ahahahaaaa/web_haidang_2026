<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Cms\ServicesManager;
use App\Support\ContentGallery;
use App\Models\User;
use App\Support\ServiceDetailContent;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Service;
use Tests\TestCase;

class ServicesManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_manager_can_store_detail_config_and_dynamic_block_images(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(ServicesManager::class)
            ->call('editService', $service->id)
            ->set('form.detail_config.hero_slides.0.title', 'Hero title moi')
            ->set('form.detail_config.feature_blocks.0.title', 'Block giai phap moi')
            ->set('form.detail_config.process.cards.0.icon_class', 'fa-solid fa-key')
            ->set('heroSlideUploads.0', UploadedFile::fake()->image('hero-slide.jpg', 1600, 900))
            ->set('featureBlockUploads.0', UploadedFile::fake()->image('feature-block.jpg', 1400, 900))
            ->call('saveService')
            ->assertHasNoErrors();

        $service->refresh();

        $this->assertSame('Hero title moi', data_get($service->detail_config, 'hero_slides.0.title'));
        $this->assertSame('Block giai phap moi', data_get($service->detail_config, 'feature_blocks.0.title'));
        $this->assertSame('fa-solid fa-key', data_get($service->detail_config, 'process.cards.0.icon_class'));
        $this->assertNotNull($service->getFirstMedia(ServiceDetailContent::heroSlideCollection(data_get($service->detail_config, 'hero_slides.0.uuid'))));
        $this->assertNotNull($service->getFirstMedia(ServiceDetailContent::featureBlockCollection(data_get($service->detail_config, 'feature_blocks.0.uuid'))));
    }

    public function test_service_cover_can_use_image_selected_from_media_library(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();
        $libraryMedia = SiteSetting::query()
            ->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('service-library-cover.jpg', 1200, 800))
            ->usingName('Service library cover')
            ->usingFileName('service-library-cover.jpg')
            ->withCustomProperties(['alt' => 'Alt từ media library'])
            ->toMediaCollection('library', 'public');

        $this->actingAs($user);

        Livewire::test(ServicesManager::class)
            ->call('editService', $service->id)
            ->call('selectCoverLibraryMedia', $libraryMedia->id, 'Alt cover dịch vụ từ popup')
            ->call('saveService')
            ->assertHasNoErrors();

        $service->refresh();
        $media = $service->getFirstMedia('cover');

        $this->assertNotNull($media);
        $this->assertSame('Alt cover dịch vụ từ popup', $service->cover_alt);
        $this->assertSame($libraryMedia->id, (int) data_get($media?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Alt cover dịch vụ từ popup', (string) data_get($media?->custom_properties, 'alt'));
    }

    public function test_service_can_auto_generate_slug_from_title_when_left_blank(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(ServicesManager::class)
            ->set('form.title', 'Visa Hàn Quốc trọn gói')
            ->set('form.slug', '')
            ->set('form.status', 'draft')
            ->call('saveService')
            ->assertHasNoErrors();

        $service = Service::query()->where('title', 'Visa Hàn Quốc trọn gói')->firstOrFail();

        $this->assertSame('visa-han-quoc-tron-goi', $service->slug);
    }

    public function test_service_category_can_store_editable_page_content(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $category = ContentCategory::query()->forTaxonomy('service')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(ServicesManager::class)
            ->call('editCategory', $category->id)
            ->set('categoryForm.description', 'Mô tả ngắn cho danh mục visa.')
            ->set('categoryForm.content', '<h2>Nội dung category visa</h2><p>Khách có thể chỉnh phần nội dung dài của trang danh mục dịch vụ từ CMS.</p><script>alert(1)</script>')
            ->call('saveCategory')
            ->assertHasNoErrors();

        $category->refresh();

        $this->assertSame('Mô tả ngắn cho danh mục visa.', $category->description);
        $this->assertStringContainsString('<h2>Nội dung category visa</h2>', (string) $category->content);
        $this->assertStringContainsString('Khách có thể chỉnh phần nội dung dài', (string) $category->content);
        $this->assertStringNotContainsString('<script>', (string) $category->content);
    }

    public function test_service_category_editor_uses_shared_quill_media_popup_and_avatar_picker(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $category = ContentCategory::query()->forTaxonomy('service')->firstOrFail();

        $response = $this->actingAs($user)->get(route('admin.services.categories.edit', $category));

        $response
            ->assertOk()
            ->assertSee('data-allow-images="true"', false)
            ->assertSee('data-admin-media-picker-trigger', false)
            ->assertSee('data-pick-method="selectAvatarLibraryMedia"', false)
            ->assertSee('wire:model.live="avatarUpload"', false)
            ->assertSee('data-media-upload-file', false);
    }

    public function test_service_category_avatar_can_use_upload_or_media_popup_selection(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $category = ContentCategory::query()->forTaxonomy('service')->firstOrFail();
        $libraryMedia = SiteSetting::query()
            ->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('service-category-library-avatar.jpg', 1200, 800))
            ->usingName('Service category library avatar')
            ->usingFileName('service-category-library-avatar.jpg')
            ->withCustomProperties(['alt' => 'Alt avatar từ library'])
            ->toMediaCollection('library', 'public');

        $this->actingAs($user);

        Livewire::test(ServicesManager::class)
            ->call('editCategory', $category->id)
            ->call('selectAvatarLibraryMedia', $libraryMedia->id, 'Alt avatar từ popup')
            ->call('saveCategory')
            ->assertHasNoErrors();

        $category->refresh();
        $avatar = $category->getFirstMedia('avatar');

        $this->assertNotNull($avatar);
        $this->assertSame($libraryMedia->id, (int) data_get($avatar?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Alt avatar từ popup', (string) data_get($avatar?->custom_properties, 'alt'));

        Livewire::test(ServicesManager::class)
            ->call('editCategory', $category->id)
            ->set('avatarUpload', UploadedFile::fake()->image('service-category-upload-avatar.jpg', 1200, 800))
            ->set('categoryForm.avatar_alt', 'Alt avatar upload')
            ->call('saveCategory')
            ->assertHasNoErrors();

        $category->refresh();
        $avatar = $category->getFirstMedia('avatar');

        $this->assertNotNull($avatar);
        $this->assertSame('service-category-upload-avatar.jpg', $avatar?->file_name);
        $this->assertNull(data_get($avatar?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Alt avatar upload', (string) data_get($avatar?->custom_properties, 'alt'));
    }

    public function test_service_manager_can_store_faq_items_and_related_questions(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(ServicesManager::class)
            ->call('editService', $service->id)
            ->set('form.faq_items.0.question', 'FAQ dịch vụ test')
            ->set('form.faq_items.0.answer', 'Nội dung FAQ dịch vụ test')
            ->set('form.related_questions.0', 'Câu hỏi liên quan số 1')
            ->call('addRelatedQuestion')
            ->set('form.related_questions.1', 'Câu hỏi liên quan số 2')
            ->call('saveService')
            ->assertHasNoErrors();

        $service->refresh();

        $this->assertSame('FAQ dịch vụ test', data_get($service->faq_items, '0.question'));
        $this->assertStringContainsString('Nội dung FAQ dịch vụ test', (string) data_get($service->faq_items, '0.answer'));
        $this->assertSame('Câu hỏi liên quan số 1', data_get($service->related_questions, '0'));
        $this->assertSame('Câu hỏi liên quan số 2', data_get($service->related_questions, '1'));
    }

    public function test_uploaded_service_cover_takes_priority_over_selected_library_media(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();
        $libraryMedia = SiteSetting::query()
            ->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('service-library-priority.jpg', 1200, 800))
            ->usingName('Service library priority')
            ->usingFileName('service-library-priority.jpg')
            ->withCustomProperties(['alt' => 'Alt library'])
            ->toMediaCollection('library', 'public');

        $this->actingAs($user);

        Livewire::test(ServicesManager::class)
            ->call('editService', $service->id)
            ->call('selectCoverLibraryMedia', $libraryMedia->id, 'Alt library')
            ->set('coverUpload', UploadedFile::fake()->image('service-upload-priority.jpg', 1200, 800))
            ->set('form.cover_alt', 'Alt upload')
            ->call('saveService')
            ->assertHasNoErrors();

        $service->refresh();
        $media = $service->getFirstMedia('cover');

        $this->assertNotNull($media);
        $this->assertSame('service-upload-priority.jpg', $media?->file_name);
        $this->assertNull(data_get($media?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Alt upload', $service->cover_alt);
        $this->assertSame('Alt upload', (string) data_get($media?->custom_properties, 'alt'));
    }

    public function test_service_can_choose_library_images_for_multiple_hero_slides_by_uuid(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();
        $siteSetting = SiteSetting::query()->findOrFail(1);
        $firstMedia = $siteSetting
            ->addMedia(UploadedFile::fake()->image('service-hero-library-1.jpg', 1200, 800))
            ->usingName('Service hero library 1')
            ->usingFileName('service-hero-library-1.jpg')
            ->withCustomProperties(['alt' => 'Alt hero 1'])
            ->toMediaCollection('library', 'public');
        $secondMedia = $siteSetting
            ->addMedia(UploadedFile::fake()->image('service-hero-library-2.jpg', 1200, 800))
            ->usingName('Service hero library 2')
            ->usingFileName('service-hero-library-2.jpg')
            ->withCustomProperties(['alt' => 'Alt hero 2'])
            ->toMediaCollection('library', 'public');

        $this->actingAs($user);

        $component = Livewire::test(ServicesManager::class)
            ->call('editService', $service->id)
            ->call('addHeroSlide');

        $heroSlides = $component->get('form.detail_config.hero_slides');
        $firstUuid = data_get($heroSlides, '0.uuid');
        $secondUuid = data_get($heroSlides, '1.uuid');

        $component
            ->call('selectHeroSlideLibraryMedia', $firstUuid, $firstMedia->id, 'Alt slide 1 từ popup')
            ->call('selectHeroSlideLibraryMedia', $secondUuid, $secondMedia->id, 'Alt slide 2 từ popup')
            ->call('saveService')
            ->assertHasNoErrors();

        $service->refresh();

        $firstHeroMedia = $service->getFirstMedia(ServiceDetailContent::heroSlideCollection($firstUuid));
        $secondHeroMedia = $service->getFirstMedia(ServiceDetailContent::heroSlideCollection($secondUuid));

        $this->assertNotNull($firstHeroMedia);
        $this->assertNotNull($secondHeroMedia);
        $this->assertSame($firstMedia->id, (int) data_get($firstHeroMedia?->custom_properties, 'source_library_media_id'));
        $this->assertSame($secondMedia->id, (int) data_get($secondHeroMedia?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Alt slide 1 từ popup', (string) data_get($firstHeroMedia?->custom_properties, 'alt'));
        $this->assertSame('Alt slide 2 từ popup', (string) data_get($secondHeroMedia?->custom_properties, 'alt'));
    }

    public function test_service_feature_block_can_choose_image_from_media_library(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();
        $libraryMedia = SiteSetting::query()
            ->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('service-feature-library.jpg', 1200, 800))
            ->usingName('Service feature library')
            ->usingFileName('service-feature-library.jpg')
            ->withCustomProperties(['alt' => 'Alt feature'])
            ->toMediaCollection('library', 'public');

        $this->actingAs($user);

        $component = Livewire::test(ServicesManager::class)
            ->call('editService', $service->id);

        $blocks = $component->get('form.detail_config.feature_blocks');
        $uuid = data_get($blocks, '0.uuid');

        $component
            ->call('selectFeatureBlockLibraryMedia', $uuid, $libraryMedia->id, 'Alt block từ popup')
            ->call('saveService')
            ->assertHasNoErrors();

        $service->refresh();
        $media = $service->getFirstMedia(ServiceDetailContent::featureBlockCollection($uuid));

        $this->assertNotNull($media);
        $this->assertSame($libraryMedia->id, (int) data_get($media?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Alt block từ popup', (string) data_get($media?->custom_properties, 'alt'));
    }

    public function test_service_gallery_can_store_library_image_and_youtube_video(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();
        $libraryMedia = SiteSetting::query()
            ->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('service-gallery-library.jpg', 1200, 800))
            ->usingName('Service gallery library')
            ->usingFileName('service-gallery-library.jpg')
            ->withCustomProperties(['alt' => 'Alt gallery'])
            ->toMediaCollection('library', 'public');

        $this->actingAs($user);

        $component = Livewire::test(ServicesManager::class)
            ->call('editService', $service->id)
            ->call('addGalleryItem', 'image')
            ->call('addGalleryItem', 'youtube');

        $gallery = $component->get('form.gallery');
        $imageUuid = data_get($gallery, '0.uuid');

        $component
            ->call('selectGalleryLibraryMedia', $imageUuid, $libraryMedia->id, 'Alt gallery từ popup')
            ->set('form.gallery.0.title', 'Ảnh thi công thực tế')
            ->set('form.gallery.1.title', 'Video walkthrough')
            ->set('form.gallery.1.video_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
            ->call('saveService')
            ->assertHasNoErrors();

        $service->refresh();
        $media = $service->getFirstMedia(ContentGallery::serviceCollection($imageUuid));

        $this->assertNotNull($media);
        $this->assertSame($libraryMedia->id, (int) data_get($media?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Alt gallery từ popup', (string) data_get($media?->custom_properties, 'alt'));
        $this->assertSame('youtube', data_get($service->gallery, '1.type'));
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', data_get($service->gallery, '1.video_url'));
    }

    public function test_service_create_can_store_uploaded_image_youtube_and_mp4_gallery_items(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user);

        $component = Livewire::test(ServicesManager::class)
            ->set('form.title', 'Thi công showroom cao cấp')
            ->set('form.slug', 'thi-cong-showroom-cao-cap')
            ->call('addGalleryItem', 'image')
            ->call('addGalleryItem', 'youtube')
            ->call('addGalleryItem', 'mp4');

        $gallery = $component->get('form.gallery');
        $imageUuid = data_get($gallery, '0.uuid');

        $component
            ->set('galleryUploads.0', UploadedFile::fake()->image('service-gallery-upload.jpg', 1200, 800))
            ->set('form.gallery.0.title', 'Ảnh thi công showroom')
            ->set('form.gallery.0.image_alt', 'Ảnh thi công showroom cao cấp')
            ->set('form.gallery.1.title', 'Video walkthrough')
            ->set('form.gallery.1.video_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
            ->set('form.gallery.2.title', 'Video timelapse')
            ->set('form.gallery.2.video_url', 'https://cdn.example.com/service-gallery.mp4')
            ->call('saveService')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.services.edit', 'thi-cong-showroom-cao-cap'));

        $service = Service::query()->where('slug', 'thi-cong-showroom-cao-cap')->firstOrFail();
        $imageMedia = $service->getFirstMedia(ContentGallery::serviceCollection($imageUuid));

        $this->assertNotNull($imageMedia);
        $this->assertSame('service-gallery-upload.jpg', $imageMedia?->file_name);
        $this->assertSame('Ảnh thi công showroom cao cấp', (string) data_get($imageMedia?->custom_properties, 'alt'));
        $this->assertSame('youtube', data_get($service->gallery, '1.type'));
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', data_get($service->gallery, '1.video_url'));
        $this->assertSame('mp4', data_get($service->gallery, '2.type'));
        $this->assertSame('https://cdn.example.com/service-gallery.mp4', data_get($service->gallery, '2.video_url'));
    }

    public function test_service_edit_route_renders_existing_service_and_delete_confirmation(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();

        $response = $this->actingAs($user)->get(route('admin.services.edit', $service));

        $response
            ->assertOk()
            ->assertSee('wire:submit.prevent="saveService"', false)
            ->assertSee('wire:click="deleteService('.$service->id.')"', false)
            ->assertSee('Xóa dịch vụ')
            ->assertSee('wire:confirm="Bạn có chắc chắn muốn xóa dịch vụ này? Hành động này không thể hoàn tác."', false);
    }

    public function test_service_save_redirects_to_slug_based_edit_route(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(ServicesManager::class)
            ->call('editService', $service->id)
            ->set('form.title', 'Hoàn thiện căn hộ cao cấp')
            ->set('form.slug', 'hoan-thien-can-ho-cao-cap')
            ->call('saveService')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.services.edit', $service->fresh()));
    }

    public function test_service_editor_livewire_updates_keep_editor_view_when_adding_gallery_item(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();

        $response = $this->actingAs($user)->get(route('admin.services.edit', $service));
        $response->assertOk();

        [$snapshot, $updateUri] = $this->extractLivewirePayload($response->getContent());

        $updateResponse = $this->postJson($updateUri, [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => (object) [],
                'calls' => [[
                    'path' => '',
                    'method' => 'addGalleryItem',
                    'params' => ['youtube'],
                ]],
            ]],
        ], [
            'X-Livewire' => 'true',
        ]);

        $updateResponse->assertOk();

        $html = (string) data_get($updateResponse->json(), 'components.0.effects.html', '');

        $this->assertStringContainsString('Biên tập dịch vụ', $html);
        $this->assertStringContainsString('Gallery item 1', $html);
        $this->assertStringContainsString('URL video', $html);
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
