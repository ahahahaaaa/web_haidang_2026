<?php

namespace Tests\Feature;

use App\Support\FrontsiteCardData;
use App\Support\FrontsiteMedia;
use App\Support\FrontsiteUrls;
use Database\Seeders\CmsBootstrapSeeder;
use Database\Seeders\CmsEstimateBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Project;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class FrontsitePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_index_pages_render_successfully(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $this->get(route('home'))->assertOk();
        $this->get(route('estimate.show'))
            ->assertOk()
            ->assertSeeText('Bảng dự toán đang tính theo input hiện tại')
            ->assertSeeText('Mô hình minh họa')
            ->assertSeeText('Bảng diện tích quy đổi')
            ->assertSeeText('Breakdown theo cấu trúc thành phần')
            ->assertSeeText('Breakdown nội thất theo phòng')
            ->assertSeeText('Hướng dẫn nhập liệu')
            ->assertSee('data-estimate-room-program-section', false)
            ->assertSee('data-estimate-guide-modal', false);
        $this->get(route('services.index'))->assertOk();
        $this->get(route('projects.index'))->assertOk();
        $this->get(route('blog.index'))->assertOk();
    }

    public function test_public_detail_pages_render_successfully_for_seeded_content(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();
        $project = Project::query()->where('slug', 'biet-thu-lakeview')->firstOrFail();
        $post = BlogPost::query()->where('slug', 'xu-huong-xay-dung-hien-dai-2026')->firstOrFail();

        $this->get(route('services.show', $service))
            ->assertOk()
            ->assertSeeText($service->title)
            ->assertSeeText('Thi công nhà phố trọn gói thường bao gồm những phần việc nào?')
            ->assertSeeText('Những câu hỏi liên quan đến '.$service->title)
            ->assertSee('"@type":"FAQPage"', false);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSeeText($project->title)
            ->assertSeeText('Dự án Biệt thự Lakeview bắt đầu từ bài toán gì?')
            ->assertSeeText('Những câu hỏi liên quan khi tham chiếu dự án này')
            ->assertSee('"@type":"FAQPage"', false);

        $this->get(FrontsiteUrls::blogPost($post))
            ->assertOk()
            ->assertSeeText($post->title);
    }

    public function test_blog_listing_can_render_faq_for_selected_blog_category(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $category = ContentCategory::query()
            ->forTaxonomy('blog')
            ->whereHas('blogPosts')
            ->firstOrFail();

        $category->update([
            'faq_items' => [
                [
                    'question' => 'Danh mục này thường viết về điều gì?',
                    'answer' => '<p>Các nội dung thực tế giúp người đọc chuẩn bị tốt hơn cho hành trình. Xem thêm <a href="https://example.com/blog-category-faq">tổng hợp</a>.</p>',
                ],
            ],
        ]);

        $this->get(route('blog-categories.show', ['slug' => $category->slug]))
            ->assertOk()
            ->assertSeeText('Danh mục này thường viết về điều gì?')
            ->assertSee('href="https://example.com/blog-category-faq"', false)
            ->assertDontSeeText('FAQ theo danh mục giúp người đọc nắm nhanh những thắc mắc phổ biến trước khi đi sâu vào từng bài viết chi tiết.')
            ->assertSee('"@type":"FAQPage"', false);
    }

    public function test_legacy_blog_category_query_redirects_to_canonical_category_path(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $category = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Cẩm nang visa',
            'slug' => 'cam-nang-visa',
            'sort_order' => 89,
        ]);

        $response = $this->get(route('blog.index', ['category' => $category->slug, 'q' => 'nhat ban']));

        $response->assertStatus(301);
        $response->assertHeader('Location', route('blog-categories.show', ['slug' => $category->slug]).'?q=nhat%20ban');
    }

    public function test_legacy_blog_detail_path_redirects_to_category_post_path(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $post = BlogPost::query()
            ->where('slug', 'xu-huong-xay-dung-hien-dai-2026')
            ->with('category')
            ->firstOrFail();

        $this->get('/blog/'.$post->slug)
            ->assertRedirect(FrontsiteUrls::blogPost($post));
    }

    public function test_blog_detail_redirects_when_category_slug_does_not_match(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $post = BlogPost::query()
            ->where('slug', 'xu-huong-xay-dung-hien-dai-2026')
            ->with('category')
            ->firstOrFail();

        $this->get('/sai-danh-muc/'.$post->slug)
            ->assertRedirect(FrontsiteUrls::blogPost($post));
    }

    public function test_service_and_blog_text_search_uses_only_titles_and_category_names(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'active_theme' => 'haidangtravel',
                'company_name' => 'Hải Đăng Travel',
                'site_name' => 'Hải Đăng Travel',
                'site_description' => 'Đơn vị tư vấn tour trong nước và quốc tế.',
                'seo_description' => 'Tour trong nước, tour nước ngoài và tour đoàn.',
            ],
        );

        $serviceCategory = ContentCategory::query()->create([
            'taxonomy' => 'service',
            'name' => 'Dịch vụ MICE riêng',
            'slug' => 'dich-vu-mice-rieng',
            'sort_order' => 95,
        ]);

        $service = Service::query()->create([
            'title' => 'Tư vấn đoàn doanh nghiệp',
            'slug' => 'tu-van-doan-doanh-nghiep',
            'excerpt' => 'marker-service-excerpt-only',
            'content' => '<p>Nội dung dịch vụ.</p>',
            'status' => 'published',
            'content_category_id' => $serviceCategory->getKey(),
        ]);

        $blogCategory = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Cẩm nang Nhật Bản riêng',
            'slug' => 'cam-nang-nhat-ban-rieng',
            'sort_order' => 96,
        ]);

        $post = BlogPost::query()->create([
            'title' => 'Chuẩn bị lịch trình tự túc',
            'slug' => 'chuan-bi-lich-trinh-tu-tuc',
            'excerpt' => 'marker-blog-excerpt-only',
            'content' => '<p>Nội dung bài viết.</p>',
            'status' => 'published',
            'content_category_id' => $blogCategory->getKey(),
            'author_name' => 'Ban biên tập',
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('services.index', ['q' => 'Tư vấn đoàn']))
            ->assertOk()
            ->assertSeeText($service->title);

        $this->get(route('services.index', ['q' => 'tu van doan']))
            ->assertOk()
            ->assertSeeText($service->title);

        $this->get(route('services.index', ['q' => $serviceCategory->name]))
            ->assertOk()
            ->assertSeeText($service->title);

        $this->get(route('services.index', ['q' => 'marker-service-excerpt-only']))
            ->assertOk()
            ->assertDontSeeText($service->title);

        $this->get(route('blog.index', ['q' => 'Chuẩn bị lịch trình']))
            ->assertOk()
            ->assertSeeText($post->title);

        $this->get(route('blog.index', ['q' => 'chuan bi lich trinh']))
            ->assertOk()
            ->assertSeeText($post->title);

        $this->get(route('blog.index', ['q' => $blogCategory->name]))
            ->assertOk()
            ->assertSeeText($post->title);

        $this->get(route('blog.index', ['q' => 'marker-blog-excerpt-only']))
            ->assertOk()
            ->assertDontSeeText($post->title);
    }

    public function test_blog_listing_parent_category_filter_includes_posts_from_child_categories(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $parentCategory = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Cẩm nang quốc tế',
            'slug' => 'cam-nang-quoc-te',
            'sort_order' => 90,
        ]);

        $childCategory = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Visa châu Á',
            'slug' => 'visa-chau-a',
            'parent_id' => $parentCategory->getKey(),
            'sort_order' => 91,
        ]);

        $post = BlogPost::query()->create([
            'title' => 'Chuẩn bị visa Nhật Bản lần đầu',
            'slug' => 'chuan-bi-visa-nhat-ban-lan-dau',
            'excerpt' => 'Checklist hồ sơ và mốc thời gian cần lưu ý trước chuyến đi.',
            'content' => '<p>Nội dung bài viết.</p>',
            'status' => 'published',
            'content_category_id' => $childCategory->getKey(),
            'author_name' => 'Ban biên tập',
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('blog-categories.show', ['slug' => $parentCategory->slug]))
            ->assertOk()
            ->assertSeeText($post->title)
            ->assertSeeText($parentCategory->name)
            ->assertSeeText($childCategory->name)
            ->assertSee(route('blog-categories.show', ['slug' => $parentCategory->slug]), false)
            ->assertDontSee(route('blog.index', ['category' => $parentCategory->slug]), false);
    }

    public function test_blog_category_path_resolves_blog_taxonomy_when_service_category_has_same_slug(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        ContentCategory::query()->create([
            'taxonomy' => 'service',
            'name' => 'Visa',
            'slug' => 'visa',
            'sort_order' => 10,
        ]);

        $blogCategory = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Visa',
            'slug' => 'visa',
            'sort_order' => 11,
        ]);

        $post = BlogPost::query()->create([
            'title' => 'Kinh nghiệm chuẩn bị visa du lịch',
            'slug' => 'kinh-nghiem-chuan-bi-visa-du-lich',
            'excerpt' => 'Các mốc chuẩn bị hồ sơ trước chuyến đi.',
            'content' => '<p>Nội dung bài viết.</p>',
            'status' => 'published',
            'content_category_id' => $blogCategory->getKey(),
            'author_name' => 'Ban biên tập',
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('blog-categories.show', ['slug' => 'visa']))
            ->assertOk()
            ->assertSeeText($post->title);
    }

    public function test_blog_detail_renders_category_trail_links_for_child_category(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $parentCategory = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Cẩm nang quốc tế',
            'slug' => 'cam-nang-quoc-te',
            'sort_order' => 95,
        ]);

        $childCategory = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Visa Hàn Quốc',
            'slug' => 'visa-han-quoc',
            'parent_id' => $parentCategory->getKey(),
            'sort_order' => 96,
        ]);

        $post = BlogPost::query()->where('slug', 'xu-huong-xay-dung-hien-dai-2026')->firstOrFail();
        $post->update([
            'content_category_id' => $childCategory->getKey(),
        ]);

        $this->get(FrontsiteUrls::blogPost($post))
            ->assertOk()
            ->assertSeeText($parentCategory->name)
            ->assertSeeText($childCategory->name)
            ->assertSee(route('blog-categories.show', ['slug' => $parentCategory->slug]), false)
            ->assertSee(route('blog-categories.show', ['slug' => $childCategory->slug]), false);
    }

    public function test_blog_listing_article_schema_includes_author(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('"author":{"@type":"Person","name":"Phong Thành Đạt","url":"'.url('/tac-gia/phong-thanh-dat').'"}', false);
    }

    public function test_blog_detail_can_render_faq_block_and_schema(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $post = BlogPost::query()->where('slug', 'xu-huong-xay-dung-hien-dai-2026')->firstOrFail();
        $post->update([
            'faq_items' => [
                [
                    'question' => 'Bài viết này phù hợp với ai?',
                    'answer' => '<p>Phù hợp với người đang cần thêm bối cảnh trước khi chọn tour hoặc dịch vụ. Xem thêm <a href="https://example.com/blog-detail-faq">gợi ý đọc tiếp</a>.</p>',
                ],
            ],
        ]);

        $this->get(FrontsiteUrls::blogPost($post))
            ->assertOk()
            ->assertSeeText($post->title)
            ->assertSeeText('Bài viết này phù hợp với ai?')
            ->assertSee('href="https://example.com/blog-detail-faq"', false)
            ->assertDontSeeText('Khối FAQ này bám đúng nội dung bài viết đang đọc để xử lý nhanh các thắc mắc phổ biến trước khi người xem chuyển sang bài liên quan hoặc trang tour.')
            ->assertSee('"@type":"FAQPage"', false);
    }

    public function test_blog_detail_article_schema_uses_first_visible_content_image_when_cover_is_missing(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $post = BlogPost::query()->where('slug', 'xu-huong-xay-dung-hien-dai-2026')->firstOrFail();
        $post->clearMediaCollection('cover');
        $post->update([
            'cover_image_url' => null,
            'content' => '<p><img src="https://example.com/blog-body-image.jpg" alt="Ảnh minh họa blog"></p><p><img src="https://example.com/blog-gallery-image.jpg" alt="Ảnh gallery blog"></p><h2>Nội dung chính</h2><p>Phần thân bài có ảnh hiển thị thật trên frontsite.</p>',
        ]);

        $this->get(FrontsiteUrls::blogPost($post))
            ->assertOk()
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('"image":{"@type":"ImageObject","url":"https://example.com/blog-body-image.jpg"', false)
            ->assertSee('"associatedMedia":[{"@type":"ImageObject","url":"https://example.com/blog-body-image.jpg"', false)
            ->assertSee('"url":"https://example.com/blog-gallery-image.jpg"', false)
            ->assertSee('src="https://example.com/blog-body-image.jpg"', false)
            ->assertSee('property="og:image" content="https://example.com/blog-body-image.jpg"', false);
    }

    public function test_blog_category_schema_includes_associated_image_objects_from_topic_and_visible_posts(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $category = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Kinh nghiệm săn vé',
            'slug' => 'kinh-nghiem-san-ve',
            'sort_order' => 96,
        ]);

        $category
            ->addMedia(UploadedFile::fake()->image('blog-topic-avatar.jpg', 1200, 800))
            ->usingName('Blog topic avatar')
            ->usingFileName('blog-topic-avatar.jpg')
            ->toMediaCollection('avatar');

        BlogPost::query()->create([
            'title' => 'Cách chọn giờ bay cho chuyến đi gia đình',
            'slug' => 'cach-chon-gio-bay-cho-chuyen-di-gia-dinh',
            'excerpt' => 'Các lưu ý chọn giờ bay để hành trình nhẹ hơn.',
            'content' => '<p><img src="https://example.com/blog-topic-gallery.jpg" alt="Gia đình chuẩn bị ra sân bay"></p><p>Nội dung bài viết.</p>',
            'status' => 'published',
            'content_category_id' => $category->getKey(),
            'author_name' => 'Ban biên tập',
            'published_at' => now(),
        ]);

        $avatarUrl = FrontsiteMedia::modelUrl($category->fresh('media'), 'avatar', FrontsiteMedia::SIZE_FULL, null);

        $this->assertNotEmpty($avatarUrl);

        $this->get(route('blog-categories.show', ['slug' => $category->slug]))
            ->assertOk()
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"associatedMedia":[{"@type":"ImageObject","url":"'.$avatarUrl.'"', false)
            ->assertSee('"url":"https://example.com/blog-topic-gallery.jpg"', false);
    }

    public function test_blog_detail_hero_uses_post_cover_image(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $post = BlogPost::query()->where('slug', 'xu-huong-xay-dung-hien-dai-2026')->firstOrFail();
        $post
            ->addMedia(UploadedFile::fake()->image('blog-hero-cover.jpg', 1600, 900))
            ->usingName('Blog hero cover')
            ->usingFileName('blog-hero-cover.jpg')
            ->toMediaCollection('cover');

        $coverMedia = FrontsiteMedia::responsiveUrls($post->fresh('media'), 'cover', 'cover_image_url');

        $this->get(FrontsiteUrls::blogPost($post))
            ->assertOk()
            ->assertSee('src="'.$coverMedia[FrontsiteMedia::SIZE_FULL].'"', false);
    }

    public function test_blog_card_resolves_cover_with_url_encoded_storage_filename(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $post = BlogPost::query()->where('slug', 'xu-huong-xay-dung-hien-dai-2026')->firstOrFail();
        $post->clearMediaCollection('cover');
        $post
            ->addMedia(UploadedFile::fake()->image('du-lich-an-do-nepal, cover.jpg', 1200, 800))
            ->usingName('Du lịch Ấn Độ Nepal mùa đẹp')
            ->usingFileName('du-lich-an-do-nepal, cover.jpg')
            ->toMediaCollection('cover');

        $card = FrontsiteCardData::blog($post->fresh(['category.parent', 'media']));

        $this->assertNotEmpty($card['image_url']);
        $this->assertStringContainsString('du-lich-an-do-nepal', $card['image_url']);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee($card['image_url'], false);
    }

    public function test_blog_listing_card_uses_first_visible_content_image_when_cover_is_missing(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $category = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Kinh nghiệm du lịch riêng',
            'slug' => 'kinh-nghiem-du-lich-rieng',
            'sort_order' => 97,
        ]);

        BlogPost::query()->create([
            'title' => 'Checklist chuẩn bị chuyến đi Đà Nẵng',
            'slug' => 'checklist-chuan-bi-chuyen-di-da-nang',
            'excerpt' => 'Các bước chuẩn bị trước khi khởi hành.',
            'content' => '<p><img src="https://example.com/blog-card-body-image.jpg" alt="Ảnh trong nội dung blog"></p><p>Nội dung bài viết.</p>',
            'status' => 'published',
            'content_category_id' => $category->getKey(),
            'author_name' => 'Ban biên tập',
            'published_at' => now(),
        ]);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('src="https://example.com/blog-card-body-image.jpg"', false);
    }

    public function test_blog_detail_article_schema_falls_back_to_sitewide_og_image_when_post_has_no_image(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        SiteSetting::query()->findOrFail(1)->update([
            'og_image_url' => 'https://example.com/sitewide-og-image.jpg',
        ]);

        $post = BlogPost::query()->where('slug', 'xu-huong-xay-dung-hien-dai-2026')->firstOrFail();
        $post->clearMediaCollection('cover');
        $post->update([
            'cover_image_url' => null,
            'content' => '<h2>Nội dung chính</h2><p>Bài viết không có ảnh riêng.</p>',
        ]);

        $this->get(FrontsiteUrls::blogPost($post))
            ->assertOk()
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('"image":{"@type":"ImageObject","url":"https://example.com/sitewide-og-image.jpg"', false)
            ->assertSee('property="og:image" content="https://example.com/sitewide-og-image.jpg"', false);
    }

    public function test_service_detail_renders_faq_without_supporting_description(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();

        $this->get(route('services.show', $service))
            ->assertOk()
            ->assertSeeText('Câu hỏi thường gặp')
            ->assertDontSeeText('Phần hỏi đáp này được hiển thị trực tiếp trên trang để vừa hỗ trợ người đọc vừa giữ schema FAQ nhất quán với nội dung thực tế.');
    }

    public function test_homepage_outputs_enriched_organization_and_local_business_schema(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        SiteSetting::query()->findOrFail(1)->update([
            'structured_data' => [
                'organization' => [
                    'image_url' => 'https://example.com/schema-image.jpg',
                ],
                'local_business' => [
                    'price_range' => 'Từ 2.500.000đ/m²',
                ],
                'address' => [
                    'street_address' => '123 Đường Mẫu',
                    'address_locality' => 'Thành phố Hồ Chí Minh',
                    'address_country' => 'VN',
                    'postal_code' => '700000',
                ],
            ],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"image":{"@type":"ImageObject","url":"https://example.com/schema-image.jpg"', false)
            ->assertSee('"@type":"PostalAddress"', false)
            ->assertSee('"streetAddress":"123 Đường Mẫu"', false)
            ->assertSee('"addressLocality":"Thành phố Hồ Chí Minh"', false)
            ->assertSee('"postalCode":"700000"', false)
            ->assertSee('"addressCountry":"VN"', false)
            ->assertSee('"@type":"LocalBusiness"', false)
            ->assertSee('"priceRange":"Từ 2.500.000đ/m²"', false);
    }

    public function test_contact_page_renders_google_maps_share_url_as_embed_url(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        SiteSetting::query()->findOrFail(1)->update([
            'map_embed_url' => 'https://www.google.com/maps/search/10.827624,+106.642022?entry=tts',
        ]);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('src="https://www.google.com/maps?q=10.827624%2C%20106.642022&amp;output=embed"', false)
            ->assertDontSee('src="https://www.google.com/maps/search/10.827624,+106.642022?entry=tts"', false);
    }

    public function test_package_landing_outputs_service_offer_catalog_process_and_faq_schema(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $this->get(route('packages.show', 'phan-tho-hoan-thien-tieu-chuan'))
            ->assertOk()
            ->assertSee('"@type":"Service"', false)
            ->assertSee('"serviceType":"Thi công phần thô + nhân công hoàn thiện"', false)
            ->assertSee('"@type":"OfferCatalog"', false)
            ->assertSee('"@type":"HowTo"', false)
            ->assertSee('"@type":"FAQPage"', false);
    }

    public function test_package_comparison_page_outputs_collection_page_and_offer_catalog_schema(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $this->get(route('packages.show', 'so-sanh-tieu-chuan-va-cao-cap'))
            ->assertOk()
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"@type":"OfferCatalog"', false)
            ->assertSee('"Catalog gói thi công nhà ở"', false);
    }

    public function test_estimate_page_outputs_web_application_service_and_offer_catalog_schema(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $this->get(route('estimate.show'))
            ->assertOk()
            ->assertSee('"@type":"WebApplication"', false)
            ->assertSee('"applicationCategory":"BusinessApplication"', false)
            ->assertSee('"@type":"Service"', false)
            ->assertSee('"@type":"OfferCatalog"', false)
            ->assertSee('"name":"Dự toán cơ bản"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSeeText('Tôi nên bắt đầu dùng trang dự toán từ bước nào?');
    }

    public function test_robots_txt_advertises_the_sitemap_endpoint(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $this->get(route('robots'))
            ->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin/', false)
            ->assertSee('Disallow: /api/v1/admin/', false)
            ->assertSee('Sitemap: '.route('sitemap'), false);
    }

    public function test_sitemap_xml_lists_key_public_urls_and_published_content(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();
        $project = Project::query()->where('slug', 'biet-thu-lakeview')->firstOrFail();
        $post = BlogPost::query()->where('slug', 'xu-huong-xay-dung-hien-dai-2026')->firstOrFail();

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false)
            ->assertSee(route('home'), false)
            ->assertSee(route('estimate.show'), false)
            ->assertSee(route('packages.show', 'phan-tho-hoan-thien-tieu-chuan'), false)
            ->assertSee(route('services.show', $service), false)
            ->assertSee(route('projects.show', $project), false)
            ->assertSee(FrontsiteUrls::blogPost($post), false);
    }

    public function test_key_public_pages_render_ai_answer_summary_blocks(): void
    {
        $this->seed(CmsEstimateBootstrapSeeder::class);

        $service = Service::query()->where('slug', 'thi-cong-nha-pho-tron-goi')->firstOrFail();
        $project = Project::query()->where('slug', 'biet-thu-lakeview')->firstOrFail();
        $this->get(route('services.show', $service))
            ->assertOk()
            ->assertSee('data-ai-summary', false);

        $this->get(route('packages.show', 'phan-tho-hoan-thien-tieu-chuan'))
            ->assertOk()
            ->assertSee('data-ai-summary', false);

        $this->get(route('estimate.show'))
            ->assertOk()
            ->assertSee('data-ai-summary', false);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('data-ai-summary', false);
    }

    public function test_blog_detail_renders_h1_and_generated_table_of_contents_from_h2_content(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $post = BlogPost::query()->where('slug', 'xu-huong-xay-dung-hien-dai-2026')->firstOrFail();
        $post->update([
            'content' => '<p>Đoạn mở đầu.</p><h2>Tổng quan xu hướng</h2><p>Nội dung phần một.</p><h2>Những lưu ý khi triển khai</h2><p>Nội dung phần hai.</p>',
        ]);

        $this->get(FrontsiteUrls::blogPost($post))
            ->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('Mục lục bài viết')
            ->assertSee('class="toc-container mt-6"', false)
            ->assertSee('"cssSelector":".toc-container"', false)
            ->assertSee('href="#tong-quan-xu-huong"', false)
            ->assertSee('href="#nhung-luu-y-khi-trien-khai"', false)
            ->assertDontSee('data-ai-summary', false);
    }
}
