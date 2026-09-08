<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Cms\BlogsManager;
use App\Models\User;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class BlogsManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_edit_route_loads_existing_post_when_url_uses_slug(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $post = BlogPost::query()->where('slug', 'xu-huong-xay-dung-hien-dai-2026')->firstOrFail();

        $this->get(route('admin.blogs.edit', $post))
            ->assertOk()
            ->assertSee('Biên tập bài viết blog')
            ->assertDontSee('Tạo bài viết blog');
    }

    public function test_blog_category_edit_route_loads_existing_category_without_type_error(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $editedCategory = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Cẩm nang biển đảo',
            'slug' => 'cam-nang-bien-dao',
            'sort_order' => 10,
        ]);

        ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Cẩm nang quốc tế',
            'slug' => 'cam-nang-quoc-te',
            'sort_order' => 11,
        ]);

        $this->get(route('admin.blogs.categories.edit', $editedCategory))
            ->assertOk()
            ->assertSee('Biên tập danh mục blog')
            ->assertDontSee('Tạo danh mục blog');
    }

    public function test_blog_cover_can_use_image_selected_from_media_library(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $libraryMedia = SiteSetting::query()
            ->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('blog-library-cover.jpg', 1200, 800))
            ->usingName('Blog library cover')
            ->usingFileName('blog-library-cover.jpg')
            ->withCustomProperties(['alt' => 'Alt từ media library'])
            ->toMediaCollection('library', 'public');

        Livewire::test(BlogsManager::class)
            ->call('selectCoverLibraryMedia', $libraryMedia->id, 'Alt cover từ popup')
            ->set('form.title', 'Blog dùng cover library')
            ->set('form.status', 'draft')
            ->set('form.author_name', 'Test User')
            ->call('savePost')
            ->assertHasNoErrors();

        $post = BlogPost::query()->where('title', 'Blog dùng cover library')->firstOrFail();
        $media = $post->getFirstMedia('cover');

        $this->assertNotNull($media);
        $this->assertSame('Alt cover từ popup', $post->cover_alt);
        $this->assertSame($libraryMedia->id, (int) data_get($media?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Alt cover từ popup', (string) data_get($media?->custom_properties, 'alt'));
    }

    public function test_blog_post_can_auto_generate_slug_from_title_when_left_blank(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(BlogsManager::class)
            ->set('form.title', 'Cẩm nang đi Thái Lan')
            ->set('form.slug', '')
            ->set('form.status', 'draft')
            ->set('form.author_name', 'Test User')
            ->call('savePost')
            ->assertHasNoErrors();

        $post = BlogPost::query()->where('title', 'Cẩm nang đi Thái Lan')->firstOrFail();

        $this->assertSame('cam-nang-di-thai-lan', $post->slug);
    }

    public function test_blog_post_with_blank_slug_gets_unique_slug_when_title_matches_existing_post(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        BlogPost::query()->create([
            'title' => 'Cẩm nang đi Hàn Quốc',
            'slug' => 'cam-nang-di-han-quoc',
            'status' => 'draft',
            'author_name' => 'Test User',
        ]);

        Livewire::test(BlogsManager::class)
            ->set('form.title', 'Cẩm nang đi Hàn Quốc')
            ->set('form.slug', '')
            ->set('form.status', 'draft')
            ->set('form.author_name', 'Test User')
            ->call('savePost')
            ->assertHasNoErrors();

        $post = BlogPost::query()->where('slug', 'cam-nang-di-han-quoc-2')->firstOrFail();

        $this->assertSame('Cẩm nang đi Hàn Quốc', $post->title);
    }

    public function test_blog_post_manual_duplicate_slug_returns_validation_error(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $existingPost = BlogPost::query()->create([
            'title' => 'Review Tour Cửu Trại Câu',
            'slug' => 'review-tour-cuu-trai-cau-thien-duong-ha-gioi-mua-nao-dep-nhat',
            'status' => 'draft',
            'author_name' => 'Test User',
        ]);

        Livewire::test(BlogsManager::class)
            ->set('form.title', 'Bảng giá tour du lịch Trung Quốc trọn gói mới nhất năm nay')
            ->set('form.slug', $existingPost->slug)
            ->set('form.status', 'draft')
            ->set('form.author_name', 'Test User')
            ->call('savePost')
            ->assertHasErrors(['form.slug']);

        $this->assertSame(1, BlogPost::query()->where('slug', $existingPost->slug)->count());
    }
    public function test_blog_post_can_store_country_root_and_destination_context(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $country = Destination::query()->updateOrCreate(
            ['slug' => 'du-lich-thai-lan'],
            [
                'name' => 'Thái Lan',
                'status' => 'published',
                'is_country_root' => true,
            ],
        );
        $destination = Destination::query()->create([
            'country_id' => $country->getKey(),
            'name' => 'Bangkok',
            'slug' => 'bangkok',
            'status' => 'published',
        ]);

        Livewire::test(BlogsManager::class)
            ->set('form.title', 'Kinh nghiệm đi Bangkok')
            ->set('form.status', 'draft')
            ->set('form.author_name', 'Test User')
            ->set('form.country_destination_id', $country->getKey())
            ->set('form.destination_id', $destination->getKey())
            ->call('savePost')
            ->assertHasNoErrors();

        $post = BlogPost::query()->where('title', 'Kinh nghiệm đi Bangkok')->firstOrFail();

        $this->assertSame($country->getKey(), $post->country_destination_id);
        $this->assertSame($destination->getKey(), $post->destination_id);
    }

    public function test_uploaded_blog_cover_takes_priority_over_selected_library_media(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $libraryMedia = SiteSetting::query()
            ->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('blog-library-priority.jpg', 1200, 800))
            ->usingName('Blog library priority')
            ->usingFileName('blog-library-priority.jpg')
            ->withCustomProperties(['alt' => 'Alt library'])
            ->toMediaCollection('library', 'public');

        Livewire::test(BlogsManager::class)
            ->call('selectCoverLibraryMedia', $libraryMedia->id, 'Alt library')
            ->set('coverUpload', UploadedFile::fake()->image('blog-upload-priority.jpg', 1200, 800))
            ->set('form.title', 'Blog ưu tiên upload')
            ->set('form.status', 'draft')
            ->set('form.author_name', 'Test User')
            ->set('form.cover_alt', 'Alt upload')
            ->call('savePost')
            ->assertHasNoErrors();

        $post = BlogPost::query()->where('title', 'Blog ưu tiên upload')->firstOrFail();
        $media = $post->getFirstMedia('cover');

        $this->assertNotNull($media);
        $this->assertSame('blog-upload-priority.jpg', $media?->file_name);
        $this->assertNull(data_get($media?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Alt upload', $post->cover_alt);
        $this->assertSame('Alt upload', (string) data_get($media?->custom_properties, 'alt'));
    }

    public function test_blog_post_and_category_can_store_faq_items(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(BlogsManager::class)
            ->set('form.title', 'Blog có FAQ')
            ->set('form.status', 'draft')
            ->set('form.author_name', 'Test User')
            ->set('form.faq_items', [
                [
                    'question' => 'Cần chuẩn bị gì trước chuyến đi?',
                    'answer' => '<p>Nên kiểm tra giấy tờ, thời tiết và lịch trình khởi hành. Xem thêm <a href="https://example.com/checklist-di-tour">checklist</a>.</p>',
                ],
            ])
            ->call('savePost')
            ->assertHasNoErrors();

        Livewire::test(BlogsManager::class)
            ->set('categoryForm.name', 'Kinh nghiệm du lịch')
            ->set('categoryForm.faq_items', [
                [
                    'question' => 'Danh mục này tập trung nội dung gì?',
                    'answer' => '<p>Các bài viết kinh nghiệm thực tế trước, trong và sau chuyến đi. Xem thêm <a href="https://example.com/kinh-nghiem-du-lich">tổng hợp</a>.</p>',
                ],
            ])
            ->call('saveCategory')
            ->assertHasNoErrors();

        $this->assertSame(
            'Cần chuẩn bị gì trước chuyến đi?',
            data_get(BlogPost::query()->where('title', 'Blog có FAQ')->firstOrFail()->faq_items, '0.question')
        );
        $this->assertStringContainsString(
            'href="https://example.com/checklist-di-tour"',
            (string) data_get(BlogPost::query()->where('title', 'Blog có FAQ')->firstOrFail()->faq_items, '0.answer')
        );
        $this->assertSame(
            'Danh mục này tập trung nội dung gì?',
            data_get(ContentCategory::query()->forTaxonomy('blog')->where('slug', 'kinh-nghiem-du-lich')->firstOrFail()->faq_items, '0.question')
        );
        $this->assertStringContainsString(
            'href="https://example.com/kinh-nghiem-du-lich"',
            (string) data_get(ContentCategory::query()->forTaxonomy('blog')->where('slug', 'kinh-nghiem-du-lich')->firstOrFail()->faq_items, '0.answer')
        );
    }

    public function test_blog_category_can_be_saved_as_child_of_a_root_category(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(BlogsManager::class)
            ->set('categoryForm.name', 'Cẩm nang quốc tế')
            ->call('saveCategory')
            ->assertHasNoErrors();

        $parent = ContentCategory::query()
            ->forTaxonomy('blog')
            ->where('slug', 'cam-nang-quoc-te')
            ->firstOrFail();

        Livewire::test(BlogsManager::class)
            ->set('categoryForm.name', 'Visa châu Á')
            ->set('categoryForm.parent_id', $parent->getKey())
            ->call('saveCategory')
            ->assertHasNoErrors();

        $child = ContentCategory::query()
            ->forTaxonomy('blog')
            ->where('slug', 'visa-chau-a')
            ->firstOrFail();

        $this->assertSame($parent->getKey(), $child->parent_id);
    }

    public function test_blog_category_only_supports_two_levels(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $parent = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Visa',
            'slug' => 'visa',
            'sort_order' => 10,
        ]);

        $child = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Visa châu Á',
            'slug' => 'visa-chau-a',
            'parent_id' => $parent->getKey(),
            'sort_order' => 11,
        ]);

        Livewire::test(BlogsManager::class)
            ->set('categoryForm.name', 'Checklist Seoul')
            ->set('categoryForm.parent_id', $child->getKey())
            ->call('saveCategory')
            ->assertHasErrors(['categoryForm.parent_id']);
    }
}
