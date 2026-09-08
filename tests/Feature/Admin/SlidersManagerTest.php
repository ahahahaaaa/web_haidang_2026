<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Slider;
use Src\Domains\Cms\Models\SliderItem;

class SlidersManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_slider_item_can_use_an_image_selected_from_media_library(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $libraryMedia = SiteSetting::query()
            ->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('library-slider.jpg', 1200, 800))
            ->usingName('Library slider image')
            ->usingFileName('library-slider.jpg')
            ->withCustomProperties(['alt' => 'Alt từ media library'])
            ->toMediaCollection('library', 'public');

        Livewire::test(\App\Livewire\Admin\Cms\SlidersManager::class)
            ->call('selectLibraryMedia', $libraryMedia->id, 'Alt đã chọn từ popup')
            ->set('itemForm.title', 'Slider lấy ảnh từ library')
            ->set('itemForm.effect', 'animate__fadeIn')
            ->set('itemForm.order', 1)
            ->call('saveItem')
            ->assertHasNoErrors();

        $item = SliderItem::query()->where('title', 'Slider lấy ảnh từ library')->firstOrFail();
        $media = $item->getFirstMedia('image');

        $this->assertNotNull($media);
        $this->assertSame('Alt đã chọn từ popup', $item->image_alt);
        $this->assertSame($libraryMedia->id, (int) data_get($media?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Alt đã chọn từ popup', (string) data_get($media?->custom_properties, 'alt'));
    }

    public function test_uploaded_slider_image_takes_priority_over_selected_library_media(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $libraryMedia = SiteSetting::query()
            ->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('library-priority.jpg', 1200, 800))
            ->usingName('Library image priority')
            ->usingFileName('library-priority.jpg')
            ->withCustomProperties(['alt' => 'Alt library'])
            ->toMediaCollection('library', 'public');

        Livewire::test(\App\Livewire\Admin\Cms\SlidersManager::class)
            ->call('selectLibraryMedia', $libraryMedia->id, 'Alt library')
            ->set('itemImageUpload', UploadedFile::fake()->image('uploaded-priority.jpg', 1200, 800))
            ->set('itemForm.title', 'Slider ưu tiên upload')
            ->set('itemForm.image_alt', 'Alt upload')
            ->set('itemForm.effect', 'animate__fadeInUp')
            ->set('itemForm.order', 2)
            ->call('saveItem')
            ->assertHasNoErrors();

        $item = SliderItem::query()->where('title', 'Slider ưu tiên upload')->firstOrFail();
        $media = $item->getFirstMedia('image');

        $this->assertNotNull($media);
        $this->assertSame('uploaded-priority.jpg', $media?->file_name);
        $this->assertNull(data_get($media?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Alt upload', (string) data_get($media?->custom_properties, 'alt'));
    }

    public function test_slider_item_can_store_two_buttons_and_video_url(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(\App\Livewire\Admin\Cms\SlidersManager::class)
            ->set('itemForm.title', 'Slider có 2 CTA')
            ->set('itemForm.effect', 'animate__fadeIn')
            ->set('itemForm.primary_label', 'Khám phá tour')
            ->set('itemForm.primary_url', '/tour-trong-nuoc')
            ->set('itemForm.secondary_label', 'Xem gallery')
            ->set('itemForm.secondary_url', '/ve-chung-toi')
            ->set('itemForm.video_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
            ->call('saveItem')
            ->assertHasNoErrors();

        $item = SliderItem::query()->where('title', 'Slider có 2 CTA')->firstOrFail();

        $this->assertSame('Khám phá tour', $item->primary_label);
        $this->assertSame('/tour-trong-nuoc', $item->primary_url);
        $this->assertSame('Xem gallery', $item->secondary_label);
        $this->assertSame('/ve-chung-toi', $item->secondary_url);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $item->video_url);
        $this->assertSame('Khám phá tour', $item->cta_label);
        $this->assertSame('/tour-trong-nuoc', $item->cta_url);
    }

    public function test_slider_item_can_store_responsive_images_and_display_toggles(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(\App\Livewire\Admin\Cms\SlidersManager::class)
            ->set('itemForm.title', 'Slider responsive')
            ->set('itemForm.effect', 'animate__zoomInLeft')
            ->set('itemForm.image_alt', 'Alt responsive')
            ->set('itemForm.show_overlay', false)
            ->set('itemForm.show_inner_media', false)
            ->set('itemImageUpload', UploadedFile::fake()->image('desktop-slider.jpg', 1600, 900))
            ->set('itemMobileImageUpload', UploadedFile::fake()->image('mobile-slider.jpg', 900, 1400))
            ->set('itemInnerImageUpload', UploadedFile::fake()->image('inner-slider.jpg', 900, 1200))
            ->call('saveItem')
            ->assertHasNoErrors();

        $item = SliderItem::query()->where('title', 'Slider responsive')->firstOrFail();

        $this->assertFalse($item->show_overlay);
        $this->assertFalse($item->show_inner_media);
        $this->assertSame('animate__zoomInLeft', $item->effect);
        $this->assertSame('desktop-slider.jpg', $item->getFirstMedia('image')?->file_name);
        $this->assertSame('mobile-slider.jpg', $item->getFirstMedia('mobile_image')?->file_name);
        $this->assertSame('inner-slider.jpg', $item->getFirstMedia('inner_image')?->file_name);
    }

    public function test_new_slider_item_defaults_to_the_end_of_the_selected_slider(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $slider = Slider::query()->create([
            'name' => 'Slider kiểm tra thứ tự',
            'location' => 'slider-kiem-tra-thu-tu',
            'is_active' => true,
            'autoplay_delay' => 5000,
        ]);

        $existingItem = SliderItem::query()->create([
            'slider_id' => $slider->id,
            'title' => 'Item đang sửa',
            'effect' => 'animate__fadeIn',
            'order' => 7,
            'is_active' => true,
            'show_overlay' => true,
            'show_inner_media' => true,
        ]);

        SliderItem::query()->create([
            'slider_id' => $slider->id,
            'title' => 'Item trước đó',
            'effect' => 'animate__fadeInUp',
            'order' => 3,
            'is_active' => true,
            'show_overlay' => true,
            'show_inner_media' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\Admin\Cms\SlidersManager::class)
            ->call('selectSlider', $slider->id)
            ->assertSet('itemForm.order', 8)
            ->call('editItem', $existingItem->id)
            ->assertSet('itemForm.order', 7);
    }

    public function test_slider_index_is_separate_and_editor_uses_shared_sweetalert_form_feedback_contract(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $slider = Slider::query()->firstOrFail();
        $this->actingAs($user);

        $response = $this->get(route('admin.sliders'));

        $response->assertOk()
            ->assertDontSee('data-admin-feedback-form', false)
            ->assertDontSee('data-admin-loading-text="Đang lưu cấu hình slider..."', false)
            ->assertDontSee('data-admin-loading-text="Đang lưu slider item..."', false)
            ->assertSee(route('admin.sliders.create'), false);

        $editorResponse = $this->get(route('admin.sliders.edit', $slider));

        $editorResponse->assertOk()
            ->assertSee('data-admin-feedback-form', false)
            ->assertSee('data-admin-loading-text="Đang lưu cấu hình slider..."', false)
            ->assertSee('data-admin-loading-text="Đang lưu slider item..."', false);

        $this->assertSame(2, substr_count($editorResponse->getContent(), 'data-admin-feedback-form'));
    }
}
