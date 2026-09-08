<?php

namespace Tests\Feature;

use App\Support\ContentGallery;
use App\Support\FrontsiteCardData;
use App\Support\FrontsiteMedia;
use App\Support\FrontsiteUrls;
use App\Support\LandingPageBlocks;
use App\Support\TravelHomePageConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Src\Domains\Cms\Models\TourDeparture;
use Src\Domains\Cms\Models\TourDepartureSyncState;
use Src\Domains\Cms\Models\TravelReview;
use Tests\TestCase;

class TourSitemapAndBlocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_outputs_featured_tour_schema_with_breadcrumb_offer_and_visible_rating(): void
    {
        [$category, $tour, $destination] = $this->travelFixture();

        $category->update([
            'cover_image_url' => 'https://example.com/topic-tour-van-hoa.jpg',
            'is_featured' => true,
        ]);
        $destination->update([
            'cover_image_url' => 'https://example.com/destination-home-cover.jpg',
            'is_featured' => true,
        ]);
        $tour->update([
            'is_featured' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"name":"Trang chủ"', false)
            ->assertSee('"@id":"'.route('home').'#webpage"', false)
            ->assertSee('"@type":"WebPage"', false)
            ->assertSee('"mainEntity":{"@id":"'.route('home').'#featured-tour-list"}', false)
            ->assertSee('"mainEntityOfPage":"'.route('home').'"', false)
            ->assertSee('"@id":"'.route('home').'#featured-tour-list"', false)
            ->assertSee('"name":"Tour nổi bật trên trang chủ"', false)
            ->assertSee('"description":"Danh sách tour nổi bật đang hiển thị trên homepage để người xem so sánh nhanh ngày khởi hành, thời lượng, giá và đánh giá trước khi mở trang chi tiết."', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"@type":"Offer"', false)
            ->assertSee('"availability":"https://schema.org/InStock"', false)
            ->assertSee('"@type":"AggregateRating"', false)
            ->assertSee('"ratingCount":214', false)
            ->assertSee('"@id":"'.route('tours.show', $tour).'#tour"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSeeText('Làm sao chọn tour phù hợp khi chưa chốt được điểm đến?')
            ->assertSeeText('4,9/5')
            ->assertSeeText('214 đánh giá')
            ->assertDontSee('"@id":"'.route('home').'#destination-list"', false)
            ->assertDontSee('"@id":"'.route('home').'#tour-topic-list"', false)
            ->assertDontSee('"@id":"'.route('home').'#home-tour-taxonomy-tab-1"', false)
            ->assertDontSee('"@id":"'.route('home').'#home-region-taxonomy-tab-1"', false);
    }

    public function test_homepage_blog_widgets_output_blogposting_item_list_schema_when_visible(): void
    {
        [, $tour] = $this->travelFixture();

        $tour->update(['is_featured' => true]);

        $fixedCategory = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Cẩm nang tour gia đình',
            'slug' => 'cam-nang-tour-gia-dinh',
            'sort_order' => 1,
        ]);
        $dynamicCategory = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Kinh nghiệm đi tour theo mùa',
            'slug' => 'kinh-nghiem-di-tour-theo-mua',
            'sort_order' => 2,
        ]);

        $fixedPost = BlogPost::query()->create([
            'title' => 'Checklist chuẩn bị tour gia đình',
            'slug' => 'checklist-chuan-bi-tour-gia-dinh',
            'excerpt' => 'Các bước chuẩn bị hành lý, giấy tờ và lịch trình cho tour gia đình.',
            'content' => '<p>Nội dung checklist chuẩn bị trước chuyến đi.</p>',
            'status' => 'published',
            'content_category_id' => $fixedCategory->getKey(),
            'author_name' => 'Ban biên tập Hải Đăng',
            'cover_image_url' => 'https://example.com/blog-family-checklist.jpg',
            'published_at' => now()->subDay(),
        ]);
        $dynamicPost = BlogPost::query()->create([
            'title' => 'Kinh nghiệm chọn tour hè phù hợp',
            'slug' => 'kinh-nghiem-chon-tour-he-phu-hop',
            'excerpt' => 'Gợi ý chọn lịch khởi hành và điểm đến cho mùa hè.',
            'content' => '<p>Nội dung kinh nghiệm chọn tour hè.</p>',
            'status' => 'published',
            'content_category_id' => $dynamicCategory->getKey(),
            'author_name' => 'Hải Đăng Travel',
            'cover_image_url' => 'https://example.com/blog-summer-tour.jpg',
            'is_featured' => true,
            'published_at' => now(),
        ]);

        $blogBlockUuid = 'homepage-blog-widget';
        LandingPage::query()->updateOrCreate(
            ['page_key' => 'home'],
            [
                'template_key' => 'home',
                'editor_mode' => LandingPage::EDITOR_MODE_BLOCKS,
                'title' => 'Trang chủ',
                'slug' => null,
                'is_active' => true,
                'hero_title' => 'Hải Đăng Travel',
                'meta_description' => 'Tour trong nước, tour nước ngoài và tour đoàn.',
                'home_config' => [
                    'featured_blog_slugs' => [$fixedPost->slug],
                    'featured_blog_limit' => 1,
                    'layout_order' => [
                        TravelHomePageConfig::homeLayoutTokenForSection('featured_tours'),
                        TravelHomePageConfig::homeLayoutTokenForBlock($blogBlockUuid),
                        TravelHomePageConfig::homeLayoutTokenForSection('blog_preview'),
                    ],
                    'blog_preview' => [
                        'is_enabled' => true,
                        'title' => 'Cẩm nang du lịch nổi bật',
                        'description' => 'Các bài viết đang hiển thị ở trang chủ để khách chuẩn bị chuyến đi.',
                    ],
                ],
                'blocks' => [[
                    'uuid' => $blogBlockUuid,
                    'type' => LandingPageBlocks::TYPE_BLOG_LIST,
                    'is_enabled' => true,
                    'title' => 'Kinh nghiệm tour theo mùa',
                    'description' => 'Bài viết được chọn từ dữ liệu blog publish.',
                    'category_slug' => $dynamicCategory->slug,
                    'featured' => true,
                    'limit' => 1,
                    'sort' => 'latest',
                ]],
            ],
        );

        $html = $this->get(route('home'))
            ->assertOk()
            ->assertSeeText('Cẩm nang du lịch nổi bật')
            ->assertSeeText($fixedPost->title)
            ->assertSeeText('Kinh nghiệm tour theo mùa')
            ->assertSeeText($dynamicPost->title)
            ->getContent();

        $pageNode = $this->schemaNodeById($html, route('home').'#webpage');
        $this->assertSame(route('home').'#featured-tour-list', data_get($pageNode, 'mainEntity.@id'));
        $this->assertSame([
            route('home').'#blog-preview-list',
            route('home').'#home-blog-list-'.$blogBlockUuid,
        ], collect(data_get($pageNode, 'hasPart', []))->pluck('@id')->all());

        $blogPreviewList = $this->schemaNodeById($html, route('home').'#blog-preview-list');
        $this->assertSame('ItemList', data_get($blogPreviewList, '@type'));
        $this->assertSame('Cẩm nang du lịch nổi bật', data_get($blogPreviewList, 'name'));
        $this->assertSame(1, data_get($blogPreviewList, 'numberOfItems'));
        $this->assertSame(FrontsiteUrls::canonicalBlogPost($fixedPost->load('category')).'#article', data_get($blogPreviewList, 'itemListElement.0.item.@id'));
        $this->assertSame('BlogPosting', data_get($blogPreviewList, 'itemListElement.0.item.@type'));
        $this->assertSame('WebPage', data_get($blogPreviewList, 'itemListElement.0.item.mainEntityOfPage.@type'));
        $this->assertSame('Person', data_get($blogPreviewList, 'itemListElement.0.item.author.@type'));
        $this->assertSame('Ban biên tập Hải Đăng', data_get($blogPreviewList, 'itemListElement.0.item.author.name'));
        $this->assertSame(url('/tac-gia/'.\Illuminate\Support\Str::slug($fixedPost->author_name)), data_get($blogPreviewList, 'itemListElement.0.item.author.url'));
        $this->assertSame($this->organizationIdForTest(), data_get($blogPreviewList, 'itemListElement.0.item.publisher.@id'));

        $dynamicBlogList = $this->schemaNodeById($html, route('home').'#home-blog-list-'.$blogBlockUuid);
        $this->assertSame('ItemList', data_get($dynamicBlogList, '@type'));
        $this->assertSame('Kinh nghiệm tour theo mùa', data_get($dynamicBlogList, 'name'));
        $this->assertSame(FrontsiteUrls::canonicalBlogPost($dynamicPost->load('category')).'#article', data_get($dynamicBlogList, 'itemListElement.0.item.@id'));
        $this->assertSame('BlogPosting', data_get($dynamicBlogList, 'itemListElement.0.item.@type'));
        $this->assertSame(url('/tac-gia/'.\Illuminate\Support\Str::slug($dynamicPost->author_name)), data_get($dynamicBlogList, 'itemListElement.0.item.author.url'));
    }

    public function test_travel_reviews_feature_flag_hides_review_items_but_keeps_fake_rating_output(): void
    {
        [$category, $tour, $destination] = $this->travelFixture();

        $tour->update([
            'is_featured' => true,
        ]);

        Config::set('travel_reviews.enabled', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('"@type":"AggregateRating"', false)
            ->assertSee('"ratingCount":214', false)
            ->assertSeeText('4,9/5')
            ->assertSeeText('214 đánh giá');

        $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSee('"@type":"AggregateRating"', false)
            ->assertSee('"ratingCount":214', false)
            ->assertDontSee('"review"', false)
            ->assertDontSee('id="tour-reviews"', false)
            ->assertSeeText('4,9/5')
            ->assertSeeText('214 lượt đánh giá')
            ->assertDontSeeText('Anh Minh');

        $this->get(route('tour-categories.show', $category))
            ->assertOk()
            ->assertDontSee('id="tour-listing-reviews"', false)
            ->assertDontSeeText('Gia đình chị Lan')
            ->assertSeeText('4,8/5 • 128 lượt đánh giá');

        $destinationHtml = $this->get(route('destinations.show', $destination))
            ->assertOk()
            ->assertDontSee('id="tour-listing-reviews"', false)
            ->getContent();
        $destinationNode = $this->schemaNodeById($destinationHtml, route('destinations.show', $destination).'#product');

        $this->assertSame('Product', data_get($destinationNode, '@type'));
        $this->assertSame('AggregateRating', data_get($destinationNode, 'aggregateRating.@type'));
        $this->assertSame('4.9', data_get($destinationNode, 'aggregateRating.ratingValue'));
        $this->assertSame(86, data_get($destinationNode, 'aggregateRating.ratingCount'));
        $this->assertArrayNotHasKey('review', $destinationNode);
        $this->assertSame('AggregateOffer', data_get($destinationNode, 'offers.@type'));
        $this->assertSame('https://schema.org/InStock', data_get($destinationNode, 'offers.availability'));
        $this->assertSame(5990000, data_get($destinationNode, 'offers.lowPrice'));
        $this->assertSame(5990000, data_get($destinationNode, 'offers.highPrice'));
        $this->assertSame('VND', data_get($destinationNode, 'offers.priceCurrency'));
    }

    public function test_tour_category_listing_outputs_collection_page_schema_and_noindexes_filtered_variants(): void
    {
        [$category, , $destination, $region] = $this->travelFixture();
        $category->update([
            'cover_image_url' => 'https://example.com/tour-category-cover.jpg',
        ]);
        $fallbackDescriptionTour = Tour::query()->create([
            'title' => 'Tour schema fallback description',
            'slug' => 'tour-schema-fallback-description',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->getKey(),
            'destination_id' => $destination->getKey(),
            'region_id' => $region->getKey(),
            'transport' => 'Xe du lịch',
            'departure_location' => 'TP. Hồ Chí Minh',
            'duration_days' => 2,
            'duration_nights' => 1,
            'sale_price' => 1990000,
            'cover_image_url' => 'https://example.com/fallback-tour-cover.jpg',
            'cta_mode' => 'booking',
            'published_at' => now(),
        ]);

        $response = $this->get(route('tour-categories.show', $category))
            ->assertOk()
            ->assertSee('"@id":"'.route('tour-categories.show', $category).'#webpage"', false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"image":{"@type":"ImageObject","url":"https://example.com/tour-category-cover.jpg"', false)
            ->assertSee('"mainEntityOfPage":"'.route('tour-categories.show', $category).'"', false)
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"availability":"https://schema.org/InStock"', false)
            ->assertSeeText('Tour văn hóa có phù hợp cho gia đình không?')
            ->assertSeeText('Lịch trình dễ theo')
            ->assertSeeText('Gia đình chị Lan')
            ->assertSeeText('4,8/5 • 1 lượt đánh giá')
            ->assertSeeText('214 đánh giá')
            ->assertSee('href="https://example.com/tour-van-hoa-family"', false)
            ->assertDontSeeText('FAQ này giúp người xem hiểu nhanh nhóm tour, phạm vi lịch trình và các lưu ý trước khi chọn hành trình cụ thể.')
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('meta name="robots" content="index,follow"', false);

        $html = $response->getContent();
        $pageNode = $this->schemaNodeById($html, route('tour-categories.show', $category).'#webpage');
        $fallbackProduct = $this->schemaListItemEntityById($html, route('tours.show', $fallbackDescriptionTour).'#tour');

        $this->assertArrayNotHasKey('aggregateRating', $pageNode);
        $this->assertArrayNotHasKey('review', $pageNode);
        $this->assertSame('ImageObject', data_get($pageNode, 'image.@type'));
        $this->assertSame('https://example.com/tour-category-cover.jpg', data_get($pageNode, 'image.url'));
        $this->assertNotEmpty(data_get($fallbackProduct, 'description'));
        $this->assertStringContainsString('TP. Hồ Chí Minh', data_get($fallbackProduct, 'description'));
        $this->assertSame('ImageObject', data_get($fallbackProduct, 'image.@type'));
        $this->assertSame('https://example.com/fallback-tour-cover.jpg', data_get($fallbackProduct, 'image.url'));

        $this->assertNotFalse(strpos($html, 'id="tour-listing-reviews"'));
        $this->assertNotFalse(strpos($html, 'tour-listing-faq'));
        $this->assertLessThan(
            strpos($html, 'tour-listing-faq'),
            strpos($html, 'id="tour-listing-reviews"'),
            'Khối đánh giá của trang chủ đề nên đứng trước FAQ như nhịp ở tour detail.',
        );

        $this->get(route('tour-categories.show', $category).'?transport=Máy%20bay')
            ->assertOk()
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('meta name="robots" content="noindex,follow"', false);
    }

    public function test_tour_category_hero_slider_uses_gallery_and_overlays_video_background_slides(): void
    {
        Storage::fake('public');
        [$category] = $this->travelFixture();
        $category->update([
            'gallery' => [
                [
                    'uuid' => 'category-gallery-media',
                    'type' => 'image',
                    'title' => 'Ảnh chủ đề văn hóa',
                    'description' => 'Không gian trải nghiệm văn hóa trong tour.',
                    'image_alt' => 'Ảnh chủ đề tour văn hóa',
                    'image_url' => '',
                    'video_url' => '',
                ],
                [
                    'uuid' => 'category-gallery-youtube',
                    'type' => 'youtube',
                    'title' => 'Video chủ đề văn hóa',
                    'description' => 'Video giới thiệu nhóm tour văn hóa.',
                    'image_alt' => '',
                    'image_url' => '',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                ],
                [
                    'uuid' => 'category-gallery-mp4',
                    'type' => 'mp4',
                    'title' => 'Clip chủ đề văn hóa',
                    'description' => 'Clip MP4 cho nhóm tour văn hóa.',
                    'image_alt' => '',
                    'image_url' => '',
                    'video_url' => 'https://cdn.example.com/category-gallery.mp4',
                ],
            ],
        ]);
        $category
            ->addMedia(UploadedFile::fake()->image('category-hero-gallery.jpg', 1600, 900))
            ->usingFileName('category-hero-gallery.jpg')
            ->toMediaCollection(ContentGallery::taxonomyCollection('category', 'category-gallery-media'), 'public');

        $response = $this->get(route('tour-categories.show', $category))
            ->assertOk()
            ->assertSee('data-hero-slider', false)
            ->assertSee('category-hero-gallery', false)
            ->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('https://cdn.example.com/category-gallery.mp4', false);

        $html = $response->getContent();

        $this->assertSame(3, preg_match_all('/data-hero-slide-item(?=[\s=>])/', $html));
        $this->assertSame(3, preg_match_all('/data-hero-overlay(?=[\s=>])/', $html));
        $this->assertSame(1, preg_match_all('/data-hero-iframe(?=[\s=>])/', $html));
        $this->assertSame(1, preg_match_all('/data-hero-video(?=[\s=>])/', $html));
        $this->assertSame(0, preg_match_all('/data-hero-panel(?=[\s=>])/', $html));
    }

    public function test_destination_hero_uses_its_cms_avatar_instead_of_gallery_items(): void
    {
        Storage::fake('public');
        [, , $destination] = $this->travelFixture();
        $destination->update([
            'gallery' => [
                [
                    'uuid' => 'destination-gallery-media',
                    'type' => 'image',
                    'title' => 'Ảnh điểm đến Hà Nội',
                    'description' => 'Ảnh từ media collection của điểm đến Hà Nội.',
                    'image_alt' => 'Ảnh điểm đến Hà Nội',
                    'image_url' => '',
                    'video_url' => '',
                ],
                [
                    'uuid' => 'destination-gallery-youtube',
                    'type' => 'youtube',
                    'title' => 'Video điểm đến Hà Nội',
                    'description' => 'Video giới thiệu điểm đến Hà Nội.',
                    'image_alt' => '',
                    'image_url' => '',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                ],
                [
                    'uuid' => 'destination-gallery-mp4',
                    'type' => 'mp4',
                    'title' => 'Clip điểm đến Hà Nội',
                    'description' => 'Clip MP4 cho điểm đến Hà Nội.',
                    'image_alt' => '',
                    'image_url' => '',
                    'video_url' => 'https://cdn.example.com/destination-gallery.mp4',
                ],
            ],
        ]);
        $destination
            ->addMedia(UploadedFile::fake()->image('destination-hero-avatar.jpg', 1600, 900))
            ->usingFileName('destination-hero-avatar.jpg')
            ->toMediaCollection('avatar', 'public');

        $response = $this->get(route('destinations.show', $destination))
            ->assertOk()
            ->assertDontSee('data-hero-slider', false)
            ->assertSee('destination-hero-avatar-full.jpg', false)
            ->assertSeeText($destination->name)
            ->assertSeeText($destination->excerpt);

        $html = $response->getContent();

        $this->assertSame(0, preg_match_all('/data-hero-slide-item(?=[\s=>])/', $html));
        $this->assertSame(0, preg_match_all('/data-hero-iframe(?=[\s=>])/', $html));
        $this->assertSame(0, preg_match_all('/data-hero-video(?=[\s=>])/', $html));
    }

    public function test_destination_listing_shows_the_latest_eight_related_blog_posts(): void
    {
        [, , $destination] = $this->travelFixture();
        $destination->update(['show_blogs_on_page' => true]);

        foreach (range(1, 9) as $number) {
            BlogPost::query()->create([
                'title' => 'Cẩm nang Hà Nội '.$number,
                'slug' => 'cam-nang-ha-noi-'.$number,
                'excerpt' => 'Thông tin cần biết trước chuyến đi Hà Nội.',
                'content' => '<p>Nội dung cẩm nang Hà Nội.</p>',
                'status' => 'published',
                'destination_id' => $destination->getKey(),
                'author_name' => 'Haidang Travel',
                'published_at' => now()->subMinutes($number),
            ]);
        }

        $this->get(route('destinations.show', $destination))
            ->assertOk()
            ->assertSeeText('Cẩm nang Hà Nội 1')
            ->assertSeeText('Cẩm nang Hà Nội 8')
            ->assertDontSeeText('Cẩm nang Hà Nội 9');
    }

    public function test_domestic_listing_search_bar_can_filter_by_topic_without_showing_other_scope_topics(): void
    {
        [$domesticCategory, $domesticTour] = $this->travelFixture();

        $internationalCategory = TourCategory::query()->create([
            'name' => 'Tour châu Âu',
            'slug' => 'tour-chau-au',
            'scope' => TourScope::International->value,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $internationalRegion = Region::query()->create([
            'name' => 'Châu Âu',
            'slug' => 'chau-au',
            'scope' => TourScope::International->value,
            'status' => 'published',
        ]);

        $internationalDestination = Destination::query()->create([
            'region_id' => $internationalRegion->getKey(),
            'name' => 'Paris',
            'slug' => 'paris',
            'scope' => TourScope::International->value,
            'status' => 'published',
        ]);

        $internationalTour = Tour::query()->create([
            'title' => 'Paris 5 ngày 4 đêm',
            'slug' => 'paris-5-ngay-4-dem',
            'excerpt' => 'Hành trình quốc tế nổi bật.',
            'content' => '<p>Nội dung tour Paris.</p>',
            'status' => 'published',
            'scope' => TourScope::International->value,
            'tour_category_id' => $internationalCategory->getKey(),
            'destination_id' => $internationalDestination->getKey(),
            'region_id' => $internationalRegion->getKey(),
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('tours.domestic'))
            ->assertOk()
            ->assertSee('name="category"', false)
            ->assertSeeText($domesticCategory->name)
            ->assertDontSeeText($internationalCategory->name)
            ->assertSeeText($domesticTour->title)
            ->assertDontSeeText($internationalTour->title);

        $this->get(route('tours.domestic', ['category' => $domesticCategory->slug]))
            ->assertOk()
            ->assertSee('name="category"', false)
            ->assertSee('option value="'.$domesticCategory->slug.'" selected', false)
            ->assertSeeText($domesticTour->title)
            ->assertDontSeeText($internationalTour->title);

        $this->get(route('tours.international'))
            ->assertOk()
            ->assertSee('name="category"', false)
            ->assertSeeText($internationalCategory->name)
            ->assertDontSeeText($domesticCategory->name)
            ->assertSeeText($internationalTour->title)
            ->assertDontSeeText($domesticTour->title);

        $this->get(route('tours.international', ['category' => $internationalCategory->slug]))
            ->assertOk()
            ->assertSee('name="category"', false)
            ->assertSee('option value="'.$internationalCategory->slug.'" selected', false)
            ->assertSeeText($internationalTour->title)
            ->assertDontSeeText($domesticTour->title);

        $this->get(route('tours.group'))
            ->assertOk()
            ->assertDontSee('name="category"', false);
    }

    public function test_tour_search_matches_secondary_topic_and_destination_names(): void
    {
        [$category, $tour, $destination, $region] = $this->travelFixture();

        $secondaryCategory = TourCategory::query()->create([
            'name' => 'Tour nghỉ dưỡng wellness',
            'slug' => 'tour-nghi-duong-wellness',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $secondaryDestination = Destination::query()->create([
            'region_id' => $region->getKey(),
            'name' => 'Vịnh Lan Hạ',
            'slug' => 'vinh-lan-ha',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $otherTour = Tour::query()->create([
            'title' => 'Đà Lạt cuối tuần',
            'slug' => 'da-lat-cuoi-tuan',
            'excerpt' => 'Hành trình nghỉ ngắn ngày.',
            'content' => '<p>Nội dung tour Đà Lạt.</p>',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->getKey(),
            'destination_id' => $destination->getKey(),
            'region_id' => $region->getKey(),
            'published_at' => now()->subDay(),
        ]);

        $tour->categories()->attach($secondaryCategory);
        $tour->destinations()->attach($secondaryDestination);

        $this->get(route('tours.search', ['q' => 'wellness']))
            ->assertOk()
            ->assertSeeText($tour->title)
            ->assertDontSeeText($otherTour->title);

        $this->get(route('tours.search', ['q' => 'Lan Hạ']))
            ->assertOk()
            ->assertSeeText($tour->title)
            ->assertDontSeeText($otherTour->title);

        $this->get(route('tours.search', ['q' => 'da lat']))
            ->assertOk()
            ->assertSeeText($otherTour->title)
            ->assertDontSeeText($tour->title);
    }

    public function test_tour_text_search_ignores_descriptive_and_departure_fields(): void
    {
        [$category, $tour] = $this->travelFixture();

        $tour->update([
            'excerpt' => 'marker-search-excerpt-only',
            'departure_location' => 'marker-search-departure-only',
        ]);

        TourDeparture::query()
            ->where('tour_id', $tour->getKey())
            ->update([
                'transport_label' => 'marker-search-transport-only',
            ]);

        $this->get(route('tours.search', ['q' => 'Hà Nội 3 ngày']))
            ->assertOk()
            ->assertSeeText($tour->title);

        $this->get(route('tours.search', ['q' => $category->name]))
            ->assertOk()
            ->assertSeeText($tour->title);

        $this->get(route('tours.search', ['q' => 'marker-search-excerpt-only']))
            ->assertOk()
            ->assertDontSeeText($tour->title);

        $this->get(route('tours.search', ['q' => 'marker-search-departure-only']))
            ->assertOk()
            ->assertDontSeeText($tour->title);

        $this->get(route('tours.search', ['q' => 'marker-search-transport-only']))
            ->assertOk()
            ->assertDontSeeText($tour->title);
    }

    public function test_tour_category_search_bar_can_filter_by_tour_scope_only_on_topic_pages(): void
    {
        [$category, $domesticTour] = $this->travelFixture();

        $internationalRegion = Region::query()->create([
            'name' => 'Châu Á',
            'slug' => 'chau-a',
            'scope' => TourScope::International->value,
            'status' => 'published',
        ]);

        $internationalDestination = Destination::query()->create([
            'region_id' => $internationalRegion->getKey(),
            'name' => 'Singapore',
            'slug' => 'singapore',
            'scope' => TourScope::International->value,
            'status' => 'published',
        ]);

        $internationalTour = Tour::query()->create([
            'title' => 'Singapore 4 ngày 3 đêm',
            'slug' => 'singapore-4-ngay-3-dem',
            'excerpt' => 'Hành trình quốc tế cùng chủ đề.',
            'content' => '<p>Nội dung tour Singapore.</p>',
            'status' => 'published',
            'scope' => TourScope::International->value,
            'tour_category_id' => $category->getKey(),
            'destination_id' => $internationalDestination->getKey(),
            'region_id' => $internationalRegion->getKey(),
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('tour-categories.show', $category))
            ->assertOk()
            ->assertSee('name="scope"', false)
            ->assertSeeText('Tất cả')
            ->assertSeeText('Tour trong nước')
            ->assertSeeText('Tour nước ngoài')
            ->assertSeeText('Tour đoàn')
            ->assertSeeText($domesticTour->title)
            ->assertSeeText($internationalTour->title);

        $this->get(route('tour-categories.show', [
            'category' => $category,
            'scope' => TourScope::International->value,
        ]))
            ->assertOk()
            ->assertSee('option value="international" selected', false)
            ->assertSeeText($internationalTour->title)
            ->assertDontSeeText($domesticTour->title)
            ->assertSee('meta name="robots" content="noindex,follow"', false);

        $this->get(route('destinations.show', $domesticTour->destination))
            ->assertOk()
            ->assertDontSee('name="scope"', false);
    }

    public function test_tour_detail_outputs_product_offer_and_visible_taxonomy_links(): void
    {
        [$category, $tour, $destination, $region] = $this->travelFixture();
        $tour->departures()->firstOrFail()->update(['available_slots' => 0]);
        $expectedSku = 'HD'.($tour->published_at?->format('Y')).$tour->getKey();

        $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSee('"@id":"'.route('tours.show', $tour).'#tour"', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"@type":"Offer"', false)
            ->assertSee('"availability":"https://schema.org/InStock"', false)
            ->assertDontSee('https://schema.org/LimitedAvailability', false)
            ->assertDontSee('https://schema.org/SoldOut', false)
            ->assertSee('"@type":"TouristDestination"', false)
            ->assertSee('"@type":"AggregateRating"', false)
            ->assertSee('"@type":"Review"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('"@id":"'.route('tours.show', $tour).'#itinerary"', false)
            ->assertSee('"category":"Tour trong nước"', false)
            ->assertSee('"keywords":"Tour trong nước, Hà Nội, Miền Bắc, Máy bay"', false)
            ->assertSee('"productID":"'.$tour->getKey().'"', false)
            ->assertSee('"sku":"'.$expectedSku.'"', false)
            ->assertSee('"slogan":"Du lịch giá hấp dẫn cùng Hải Đăng Travel"', false)
            ->assertSee('"brand":{"@type":"Brand","name":"Hải Đăng Travel"}', false)
            ->assertSee('"seller":{"@type":"Organization","name":"Hải Đăng Travel"}', false)
            ->assertSee('"itemOffered":{"@id":"'.route('tours.show', $tour).'#tour"}', false)
            ->assertSee('"itemCondition":"https://schema.org/NewCondition"', false)
            ->assertSee('"name":"Điểm khởi hành","value":"TP. Hồ Chí Minh"', false)
            ->assertSeeTextInOrder(['Lịch khởi hành & giá theo tháng', 'Gần nhất', 'Lịch trình chi tiết'])
            ->assertSeeText('Phố cổ và hồ Hoàn Kiếm')
            ->assertSeeText('Giá từ')
            ->assertSeeText('5.990.000 đ')
            ->assertSeeText('Lịch đi gọn')
            ->assertSeeText('Anh Minh')
            ->assertSee('"ratingCount":1', false)
            ->assertSee('"ratingValue":"5.0"', false)
            ->assertSeeText('Khách cần đặt cọc để giữ chỗ.')
            ->assertSee('href="https://example.com/pho-co-ha-noi"', false)
            ->assertSee('href="https://example.com/chinh-sach-dat-coc"', false)
            ->assertSee('href="https://example.com/nhom-khach-phu-hop"', false)
            ->assertDontSeeText('Khối điều khoản dùng chung này áp dụng cho mọi tour chi tiết và được trình bày theo accordion để người xem đọc từng mục nhanh hơn.')
            ->assertDontSeeText('Khối FAQ tiếp tục giữ lợi thế semantic và accessibility của theme hiện tại, đồng thời giảm ma sát trước khi người dùng liên hệ.')
            ->assertSee(route('tour-categories.show', $category), false)
            ->assertSee(route('destinations.show', $destination), false)
            ->assertSee(route('regions.show', $region), false)
            ->assertDontSee('/quoc-gia/', false);
    }

    public function test_tour_itinerary_is_fully_expanded_by_default(): void
    {
        [, $tour] = $this->travelFixture();

        $html = $this->get(route('tours.show', $tour))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, preg_match('/<section id="tour-itinerary"[\s\S]*?<\/section>/', $html, $matches));
        $this->assertSame(2, substr_count($matches[0], '<details open'));
        $this->assertSame(2, substr_count($matches[0], '<summary'));
    }

    public function test_tour_detail_product_sku_prefers_api_sync_tour_code(): void
    {
        [, $tour] = $this->travelFixture();

        TourDepartureSyncState::query()->create([
            'tour_id' => $tour->getKey(),
            'source_tour_id' => 998,
            'tour_code' => 'HDL998',
            'source_startdate_id' => 23288,
            'last_synced_at' => now(),
        ]);

        $html = $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSee('"sku":"HDL998"', false)
            ->getContent();

        $product = $this->schemaNodeById($html, route('tours.show', $tour).'#tour');

        $this->assertSame('HDL998', data_get($product, 'sku'));
    }

    public function test_tour_detail_fallback_price_offer_includes_availability(): void
    {
        [$category, , $destination, $region] = $this->travelFixture();

        $fallbackPriceTour = Tour::query()->create([
            'title' => 'Tour Tây Tạng Cung Điện Potala',
            'slug' => 'tour-du-lich-tay-tang-cung-dien-potala',
            'excerpt' => 'Hành trình Tây Tạng dùng giá tour tổng khi chưa có lịch khởi hành public.',
            'content' => '<p>Nội dung tour Tây Tạng.</p>',
            'status' => 'published',
            'scope' => TourScope::International->value,
            'tour_category_id' => $category->getKey(),
            'destination_id' => $destination->getKey(),
            'region_id' => $region->getKey(),
            'transport' => 'Máy bay',
            'departure_location' => 'TP. Hồ Chí Minh',
            'duration_days' => 6,
            'duration_nights' => 5,
            'sale_price' => 39900000,
            'published_at' => now(),
        ]);

        $html = $this->get(route('tours.show', $fallbackPriceTour))
            ->assertOk()
            ->assertSee('"@id":"'.route('tours.show', $fallbackPriceTour).'#offer"', false)
            ->assertSee('"availability":"https://schema.org/InStock"', false)
            ->getContent();

        $product = $this->schemaNodeById($html, route('tours.show', $fallbackPriceTour).'#tour');
        $offer = data_get($product, 'offers');

        $this->assertSame('Product', data_get($product, '@type'));
        $this->assertIsArray($offer);
        $this->assertSame(route('tours.show', $fallbackPriceTour).'#offer', data_get($offer, '@id'));
        $this->assertSame('https://schema.org/InStock', data_get($offer, 'availability'));
        $this->assertOfferHasMerchantPolicies($offer);
    }

    public function test_destination_and_tour_detail_breadcrumbs_follow_scope_region_destination_trail(): void
    {
        [, $tour, $destination, $region] = $this->travelFixture();

        $region->update([
            'name' => 'Châu Á',
            'slug' => 'chau-a',
            'scope' => TourScope::International->value,
        ]);
        $country = Destination::query()->create([
            'name' => 'Trung Quốc',
            'slug' => 'du-lich-trung-quoc',
            'scope' => TourScope::International->value,
            'status' => 'published',
            'is_country_root' => true,
        ]);
        $destination->update([
            'country_id' => $country->getKey(),
            'name' => 'Bắc Kinh',
            'slug' => 'bac-kinh',
            'scope' => TourScope::International->value,
        ]);
        $tour->update([
            'title' => 'Tour Bắc Kinh 5 ngày 4 đêm',
            'slug' => 'tour-bac-kinh-5-ngay-4-dem',
            'scope' => TourScope::International->value,
        ]);

        $region->refresh();
        $country->refresh();
        $destination->refresh();
        $tour->refresh();

        $destinationUrl = route('destinations.show', $destination);
        $destinationHtml = $this->get($destinationUrl)->assertOk()->getContent();
        $destinationBreadcrumb = collect($this->schemaGraphFromHtml($destinationHtml))
            ->first(fn (array $node): bool => data_get($node, '@type') === 'BreadcrumbList');

        $this->assertSame(1, preg_match('/<nav aria-label="Breadcrumb"[\s\S]*?<\/nav>/', $destinationHtml, $destinationBreadcrumbHtml));
        $destinationBreadcrumbText = trim(preg_replace('/\s+/', ' ', strip_tags($destinationBreadcrumbHtml[0])));
        $this->assertStringContainsString('Trang chủ Tour nước ngoài Châu Á Bắc Kinh', $destinationBreadcrumbText);
        $this->assertStringNotContainsString('Trung Quốc', $destinationBreadcrumbText);
        $this->assertSame(
            ['Trang chủ', 'Tour nước ngoài', 'Châu Á', 'Bắc Kinh'],
            collect(data_get($destinationBreadcrumb, 'itemListElement'))->pluck('name')->all(),
        );
        $this->assertSame(
            [route('home'), route('tours.international'), route('regions.show', $region), $destinationUrl],
            collect(data_get($destinationBreadcrumb, 'itemListElement'))->pluck('item')->all(),
        );

        $tourUrl = route('tours.show', $tour);
        $tourHtml = $this->get($tourUrl)->assertOk()->getContent();
        $tourBreadcrumb = collect($this->schemaGraphFromHtml($tourHtml))
            ->first(fn (array $node): bool => data_get($node, '@type') === 'BreadcrumbList');

        $this->assertSame(1, preg_match('/<nav aria-label="Breadcrumb"[\s\S]*?<\/nav>/', $tourHtml, $tourBreadcrumbHtml));
        $tourBreadcrumbText = trim(preg_replace('/\s+/', ' ', strip_tags($tourBreadcrumbHtml[0])));
        $this->assertStringContainsString('Trang chủ Tour nước ngoài Châu Á Bắc Kinh Tour Bắc Kinh 5 ngày 4 đêm', $tourBreadcrumbText);
        $this->assertStringNotContainsString('Trung Quốc', $tourBreadcrumbText);
        $this->assertSame(
            ['Trang chủ', 'Tour nước ngoài', 'Châu Á', 'Bắc Kinh', 'Tour Bắc Kinh 5 ngày 4 đêm'],
            collect(data_get($tourBreadcrumb, 'itemListElement'))->pluck('name')->all(),
        );
        $this->assertSame(
            [route('home'), route('tours.international'), route('regions.show', $region), $destinationUrl, $tourUrl],
            collect(data_get($tourBreadcrumb, 'itemListElement'))->pluck('item')->all(),
        );
    }

    public function test_tour_without_offer_review_or_rating_does_not_emit_invalid_product_schema(): void
    {
        [$category, , $destination, $region] = $this->travelFixture();

        $contactOnlyTour = Tour::query()->create([
            'title' => 'Tour tư vấn riêng chưa chốt giá',
            'slug' => 'tour-tu-van-rieng-chua-chot-gia',
            'excerpt' => 'Hành trình cần tư vấn riêng theo quy mô đoàn và thời điểm khởi hành.',
            'content' => '<p>Nội dung tour tư vấn riêng.</p>',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->getKey(),
            'destination_id' => $destination->getKey(),
            'region_id' => $region->getKey(),
            'transport' => 'Xe du lịch',
            'departure_location' => 'TP. Hồ Chí Minh',
            'duration_days' => 2,
            'duration_nights' => 1,
            'published_at' => now(),
        ]);

        $detailHtml = $this->get(route('tours.show', $contactOnlyTour))
            ->assertOk()
            ->getContent();
        $detailNode = $this->schemaNodeById($detailHtml, route('tours.show', $contactOnlyTour).'#tour');

        $this->assertSame('TouristTrip', data_get($detailNode, '@type'));
        $this->assertArrayNotHasKey('offers', $detailNode);
        $this->assertArrayNotHasKey('review', $detailNode);
        $this->assertArrayNotHasKey('aggregateRating', $detailNode);

        $listingHtml = $this->get(route('tour-categories.show', $category))
            ->assertOk()
            ->getContent();
        $listNode = $this->schemaListItemEntityById($listingHtml, route('tours.show', $contactOnlyTour).'#tour');

        $this->assertSame('TouristTrip', data_get($listNode, '@type'));
        $this->assertArrayNotHasKey('offers', $listNode);
        $this->assertArrayNotHasKey('review', $listNode);
        $this->assertArrayNotHasKey('aggregateRating', $listNode);
    }

    public function test_legacy_tour_route_redirects_to_new_tour_detail_url(): void
    {
        [, $tour] = $this->travelFixture();

        $this->get('/tour/'.$tour->slug)
            ->assertStatus(301)
            ->assertRedirect(route('tours.show', $tour));
    }

    public function test_tour_detail_uses_chuong_trinh_canonical_path(): void
    {
        [, $tour] = $this->travelFixture();

        $this->assertSame(url('/chuong-trinh/'.$tour->slug), route('tours.show', $tour));

        $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSee(url('/chuong-trinh/'.$tour->slug), false);
    }

    public function test_tour_detail_hero_uses_cover_media_image(): void
    {
        Storage::fake('public');

        [, $tour] = $this->travelFixture();
        $tour->update([
            'cover_image_url' => '',
        ]);
        $tour
            ->addMedia(UploadedFile::fake()->image('tour-hero, cover.jpg', 1600, 900))
            ->usingName('Tour hero cover')
            ->usingFileName('tour-hero, cover.jpg')
            ->toMediaCollection('cover');

        $coverMedia = FrontsiteMedia::responsiveUrls($tour->fresh('media'), 'cover', 'cover_image_url');

        $response = $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSee('property="og:image" content="'.$coverMedia[FrontsiteMedia::SIZE_SMALL].'"', false);

        $heroHtml = $this->tourDetailHeroHtml($response->getContent());

        $this->assertNotSame('', $heroHtml);
        $this->assertStringContainsString('src="'.$coverMedia[FrontsiteMedia::SIZE_FULL].'"', $heroHtml);
        $this->assertStringContainsString('fetchpriority="high"', $heroHtml);
    }

    public function test_tour_detail_hero_falls_back_to_first_gallery_image_when_cover_is_missing(): void
    {
        [, $tour] = $this->travelFixture();
        $tour->update([
            'cover_image_url' => '',
            'gallery' => [
                [
                    'uuid' => 'tour-hero-video-first',
                    'type' => 'mp4',
                    'title' => 'Video mở đầu',
                    'description' => 'Clip mở đầu của hành trình.',
                    'image_alt' => '',
                    'image_url' => '',
                    'video_url' => 'https://cdn.example.com/tour-hero-video.mp4',
                ],
                [
                    'uuid' => 'tour-hero-gallery-image',
                    'type' => 'image',
                    'title' => 'Ảnh hero gallery',
                    'description' => 'Ảnh fallback cho hero tour.',
                    'image_alt' => 'Ảnh hero gallery',
                    'image_url' => 'https://example.com/tour-gallery-hero.jpg',
                    'video_url' => '',
                ],
            ],
        ]);

        $response = $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSee('property="og:image" content="https://example.com/tour-gallery-hero.jpg"', false)
            ->assertSee('"image":{"@type":"ImageObject","url":"https://example.com/tour-gallery-hero.jpg"', false);

        $heroHtml = $this->tourDetailHeroHtml($response->getContent());

        $this->assertNotSame('', $heroHtml);
        $this->assertStringContainsString('src="https://example.com/tour-gallery-hero.jpg"', $heroHtml);
    }

    public function test_tour_detail_prefers_tour_specific_terms_before_theme_setting_template(): void
    {
        [, $tour] = $this->travelFixture();

        $tour->update([
            'tour_terms_items' => [
                [
                    'question' => 'Điều khoản riêng theo hành trình',
                    'answer' => '<p>Tour này áp dụng quy định riêng. Xem thêm <a href="https://example.com/dieu-khoan-tour-rieng">chi tiết</a>.</p>',
                ],
            ],
        ]);

        $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSeeText('Điều khoản riêng theo hành trình')
            ->assertSee('href="https://example.com/dieu-khoan-tour-rieng"', false)
            ->assertDontSeeText('Giữ chỗ')
            ->assertDontSee('href="https://example.com/chinh-sach-dat-coc"', false);
    }

    public function test_tour_detail_gallery_renders_slick_like_shell_for_mixed_media(): void
    {
        [, $tour] = $this->travelFixture();
        $tour->update([
            'cover_image_url' => '',
        ]);

        $response = $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSee('data-tour-gallery', false)
            ->assertSee('data-tour-gallery-stage', false)
            ->assertSee('data-tour-gallery-open-active', false)
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('data-tour-gallery-kind="image"', false)
            ->assertSee('data-tour-gallery-kind="youtube"', false)
            ->assertSee('data-tour-gallery-kind="mp4"', false)
            ->assertSeeText('Khoảnh khắc phố cổ')
            ->assertSeeText('Video YouTube hành trình')
            ->assertSeeText('Video MP4 tour');

        $html = $response->getContent();
        $detailGalleryHtml = $this->tourDetailGalleryHtml($html);

        $this->assertNotSame('', $detailGalleryHtml);
        $this->assertSame(3, preg_match_all('/\sdata-tour-gallery-thumb(?=\s|>)/', $detailGalleryHtml));
        $this->assertSame(3, preg_match_all('/\sdata-tour-gallery-lightbox-trigger(?=\s|>)/', $detailGalleryHtml));
        $this->assertStringContainsString('1 / 3', $detailGalleryHtml);
        $this->assertStringContainsString('aria-pressed="true"', $detailGalleryHtml);
    }

    public function test_tour_detail_gallery_hides_thumbnail_rail_and_navigation_when_only_one_item_exists(): void
    {
        [, $tour] = $this->travelFixture();

        $tour->update([
            'cover_image_url' => '',
            'gallery' => [data_get($tour->gallery, '0')],
        ]);

        $response = $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSee('data-tour-gallery', false)
            ->assertSee('1 / 1', false);

        $detailGalleryHtml = $this->tourDetailGalleryHtml($response->getContent());

        $this->assertNotSame('', $detailGalleryHtml);
        $this->assertStringNotContainsString('data-tour-gallery-prev', $detailGalleryHtml);
        $this->assertStringNotContainsString('data-tour-gallery-next', $detailGalleryHtml);
        $this->assertStringNotContainsString('data-tour-gallery-thumb-rail', $detailGalleryHtml);
    }

    public function test_tour_detail_gallery_uses_available_preview_for_first_thumb_when_cover_is_missing(): void
    {
        [, $tour] = $this->travelFixture();

        $tour->update([
            'cover_image_url' => '',
            'gallery' => [
                [
                    'uuid' => 'tour-gallery-mp4-first',
                    'type' => 'mp4',
                    'title' => 'Video mở đầu',
                    'description' => 'Clip mở đầu của hành trình.',
                    'image_alt' => '',
                    'image_url' => '',
                    'video_url' => 'https://cdn.example.com/tour-gallery-first.mp4',
                ],
                [
                    'uuid' => 'tour-gallery-image-second',
                    'type' => 'image',
                    'title' => 'Ảnh fallback',
                    'description' => 'Ảnh fallback cho preview đầu gallery.',
                    'image_alt' => 'Ảnh fallback gallery',
                    'image_url' => 'https://example.com/tour-gallery-fallback.jpg',
                    'video_url' => '',
                ],
            ],
        ]);

        $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSee('data-tour-gallery-kind="mp4"', false)
            ->assertSee('data-tour-gallery-poster="https://example.com/tour-gallery-fallback.jpg"', false)
            ->assertSee('src="https://example.com/tour-gallery-fallback.jpg"', false);
    }

    public function test_tour_detail_gallery_section_is_hidden_when_tour_has_no_gallery_items(): void
    {
        [, $tour] = $this->travelFixture();

        $tour->update([
            'cover_image_url' => '',
            'gallery' => [],
        ]);

        $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertDontSee('<section id="tour-gallery"', false)
            ->assertDontSee('data-gallery-collection="tour-detail-current"', false);
    }

    public function test_destination_listing_outputs_destination_faq_block(): void
    {
        [, , $destination] = $this->travelFixture();
        $destination->update([
            'content' => '<p>Giới thiệu điểm đến Hà Nội.</p>',
            'cover_image_url' => 'https://example.com/destination-cover.jpg',
        ]);

        $response = $this->get(route('destinations.show', $destination))
            ->assertOk()
            ->assertSee('"@id":"'.route('destinations.show', $destination).'#webpage"', false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"image":{"@type":"ImageObject","url":"https://example.com/destination-cover.jpg"', false)
            ->assertSee('"mainEntityOfPage":"'.route('destinations.show', $destination).'"', false)
            ->assertSeeText('Nên đi Hà Nội mùa nào đẹp?')
            ->assertSeeText('Điểm đến dễ triển khai')
            ->assertSeeText('Khách đoàn quận 1')
            ->assertSeeText('4,9/5 • 1 lượt đánh giá')
            ->assertSee('data-expanded="true"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertDontSee('"@type":"TouristDestination"', false)
            ->assertDontSee('"tourBookingPage":"'.route('destinations.show', $destination).'"', false)
            ->assertDontSee(route('destinations.show', $destination).'#destination', false);

        $html = $response->getContent();
        $this->assertNotFalse(strpos($html, 'id="tour-list"'));
        $this->assertNotFalse(strpos($html, 'tour-listing-content-panel'));
        $this->assertLessThan(
            strpos($html, 'tour-listing-content-panel'),
            strpos($html, 'id="tour-list"'),
            'Danh sách tour của trang điểm đến phải hiển thị trước phần giới thiệu.',
        );

        $pageNode = $this->schemaNodeById($html, route('destinations.show', $destination).'#webpage');
        $productNode = $this->schemaNodeById($html, route('destinations.show', $destination).'#product');

        $this->assertSame('ImageObject', data_get($pageNode, 'image.@type'));
        $this->assertSame('https://example.com/destination-cover.jpg', data_get($pageNode, 'image.url'));
        $this->assertSame(route('destinations.show', $destination).'#product', data_get($pageNode, 'about.@id'));
        $this->assertSame('Product', data_get($productNode, '@type'));
        $this->assertSame($destination->name, data_get($productNode, 'name'));
        $this->assertSame('AggregateRating', data_get($productNode, 'aggregateRating.@type'));
        $this->assertSame(1, data_get($productNode, 'aggregateRating.ratingCount'));
        $this->assertSame('Review', data_get($productNode, 'review.@type'));
        $this->assertSame('AggregateOffer', data_get($productNode, 'offers.@type'));
        $this->assertSame(1, data_get($productNode, 'offers.offerCount'));

        $this->assertNotFalse(strpos($html, 'id="tour-listing-reviews"'));
        $this->assertNotFalse(strpos($html, 'tour-listing-faq'));
        $this->assertLessThan(
            strpos($html, 'tour-listing-faq'),
            strpos($html, 'id="tour-listing-reviews"'),
            'Khối đánh giá của trang điểm đến nên đứng trước FAQ như nhịp ở tour detail.',
        );
    }

    public function test_destination_listing_can_hide_tours_and_show_destination_blog_posts(): void
    {
        [, $tour, $destination] = $this->travelFixture();
        $country = Destination::query()->updateOrCreate(
            ['slug' => 'du-lich-viet-nam'],
            [
                'name' => 'Việt Nam',
                'status' => 'published',
                'is_country_root' => true,
            ],
        );
        $blogCategory = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Kinh nghiệm điểm đến',
            'slug' => 'kinh-nghiem-diem-den',
            'sort_order' => 1,
        ]);
        $destination->update([
            'show_tours_on_page' => false,
            'show_blogs_on_page' => true,
        ]);

        BlogPost::query()->create([
            'title' => 'Kinh nghiệm Hà Nội mùa thu',
            'slug' => 'kinh-nghiem-ha-noi-mua-thu',
            'excerpt' => 'Gợi ý chuẩn bị lịch trình Hà Nội theo mùa thu.',
            'content' => '<p>Nội dung cẩm nang Hà Nội.</p>',
            'status' => 'published',
            'content_category_id' => $blogCategory->getKey(),
            'destination_id' => $destination->getKey(),
            'author_name' => 'Haidang Travel',
            'published_at' => now()->subDay(),
            'reading_time_minutes' => 5,
        ]);
        BlogPost::query()->create([
            'title' => 'Kinh nghiệm Đà Nẵng mùa hè',
            'slug' => 'kinh-nghiem-da-nang-mua-he',
            'excerpt' => 'Bài viết không thuộc điểm đến Hà Nội.',
            'content' => '<p>Nội dung cẩm nang Đà Nẵng.</p>',
            'status' => 'published',
            'content_category_id' => $blogCategory->getKey(),
            'author_name' => 'Haidang Travel',
            'published_at' => now()->subDays(2),
            'reading_time_minutes' => 4,
        ]);

        $response = $this->get(route('destinations.show', $destination))
            ->assertOk()
            ->assertSee('id="destination-blog-list"', false)
            ->assertSeeText('Kinh nghiệm Hà Nội mùa thu')
            ->assertDontSeeText('Kinh nghiệm Đà Nẵng mùa hè')
            ->assertDontSeeText($tour->title)
            ->assertSee('"@id":"'.route('destinations.show', $destination).'#destination-blog-list"', false)
            ->assertDontSee('"@id":"'.route('destinations.show', $destination).'#tour-list"', false);

        $html = $response->getContent();
        $pageNode = $this->schemaNodeById($html, route('destinations.show', $destination).'#webpage');

        $this->assertSame(
            route('destinations.show', $destination).'#destination-blog-list',
            data_get($pageNode, 'mainEntity.@id'),
        );

        $blogOnlyDestination = Destination::query()->create([
            'country_id' => $country->getKey(),
            'name' => 'Huế',
            'slug' => 'hue',
            'status' => 'published',
            'show_tours_on_page' => false,
            'show_blogs_on_page' => true,
        ]);

        BlogPost::query()->create([
            'title' => 'Kinh nghiệm du lịch Huế tự túc',
            'slug' => 'kinh-nghiem-du-lich-hue-tu-tuc',
            'excerpt' => 'Gợi ý chuẩn bị hành trình Huế khi chưa cần chọn tour.',
            'content' => '<p>Nội dung cẩm nang Huế.</p>',
            'status' => 'published',
            'content_category_id' => $blogCategory->getKey(),
            'country_destination_id' => $country->getKey(),
            'destination_id' => $blogOnlyDestination->getKey(),
            'author_name' => 'Haidang Travel',
            'published_at' => now()->subDays(3),
            'reading_time_minutes' => 6,
        ]);

        $this->get(route('destinations.show', $blogOnlyDestination))
            ->assertOk()
            ->assertSeeText('Kinh nghiệm du lịch Huế tự túc')
            ->assertSee('"@id":"'.route('destinations.show', $blogOnlyDestination).'#destination-blog-list"', false);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('destinations.show', $blogOnlyDestination), false)
            ->assertSee(route('countries.show', ['slug' => $country->slug]), false);
    }

    public function test_country_listing_can_hide_tours_and_show_country_blog_posts(): void
    {
        [, $tour, $destination] = $this->travelFixture();
        $country = Destination::query()->create([
            'name' => 'Thái Lan',
            'slug' => 'du-lich-thai-lan',
            'status' => 'published',
            'is_country_root' => true,
            'content' => '<p>Giới thiệu chi tiết về Thái Lan.</p>',
            'show_tours_on_page' => false,
            'show_blogs_on_page' => true,
        ]);
        $destination->update([
            'country_id' => $country->getKey(),
        ]);
        $blogCategory = ContentCategory::query()->create([
            'taxonomy' => 'blog',
            'name' => 'Kinh nghiệm quốc gia',
            'slug' => 'kinh-nghiem-quoc-gia',
            'sort_order' => 1,
        ]);

        BlogPost::query()->create([
            'title' => 'Kinh nghiệm du lịch Thái Lan tự túc',
            'slug' => 'kinh-nghiem-du-lich-thai-lan-tu-tuc',
            'excerpt' => 'Gợi ý chuẩn bị hành trình Thái Lan.',
            'content' => '<p>Nội dung cẩm nang Thái Lan.</p>',
            'status' => 'published',
            'content_category_id' => $blogCategory->getKey(),
            'country_destination_id' => $country->getKey(),
            'author_name' => 'Haidang Travel',
            'published_at' => now()->subDay(),
            'reading_time_minutes' => 5,
        ]);

        $response = $this->get(route('countries.show', ['slug' => $country->slug]))
            ->assertOk()
            ->assertSee('id="destination-blog-list"', false)
            ->assertSeeText('Kinh nghiệm du lịch Thái Lan tự túc')
            ->assertSee('data-expanded="true"', false)
            ->assertDontSeeText($tour->title)
            ->assertSee('"@id":"'.route('countries.show', ['slug' => $country->slug]).'#destination-blog-list"', false)
            ->assertDontSee('"@id":"'.route('countries.show', ['slug' => $country->slug]).'#tour-list"', false);

        $pageNode = $this->schemaNodeById($response->getContent(), route('countries.show', ['slug' => $country->slug]).'#webpage');

        $this->assertSame(
            route('countries.show', ['slug' => $country->slug]).'#destination-blog-list',
            data_get($pageNode, 'mainEntity.@id'),
        );
    }

    public function test_region_listing_outputs_detailed_collection_page_schema(): void
    {
        [, , , $region] = $this->travelFixture();

        $region->update([
            'cover_image_url' => 'https://example.com/region-cover.jpg',
        ]);

        $this->get(route('regions.show', $region))
            ->assertOk()
            ->assertSee('"@id":"'.route('regions.show', $region).'#webpage"', false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"image":{"@type":"ImageObject","url":"https://example.com/region-cover.jpg"', false)
            ->assertSee('"mainEntityOfPage":"'.route('regions.show', $region).'"', false)
            ->assertSee('"@type":"ItemList"', false);
    }

    public function test_tour_detail_with_multiple_departures_outputs_aggregate_offer_and_referenced_offer_nodes(): void
    {
        [, $tour] = $this->travelFixture();

        $extraDeparture = TourDeparture::query()->create([
            'tour_id' => $tour->getKey(),
            'departure_date' => now()->addDays(25)->toDateString(),
            'departure_location' => 'TP. Hồ Chí Minh',
            'transport_label' => 'Máy bay',
            'standard_label' => 'Khách sạn 4 sao',
            'sale_price' => 6990000,
            'status' => 'scheduled',
        ]);

        $html = $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSee('"@type":"AggregateOffer"', false)
            ->assertSee('"offerCount":2', false)
            ->assertSee('"availability":"https://schema.org/InStock"', false)
            ->assertSee('"@id":"'.route('tours.show', $tour).'#offer-'.$extraDeparture->getKey().'"', false)
            ->getContent();

        $product = $this->schemaNodeById($html, route('tours.show', $tour).'#tour');
        $aggregateOffer = data_get($product, 'offers');
        $departureOffer = $this->schemaNodeById($html, route('tours.show', $tour).'#offer-'.$extraDeparture->getKey());

        $this->assertIsArray($aggregateOffer);
        $this->assertOfferHasMerchantPolicies($aggregateOffer);
        $this->assertOfferHasMerchantPolicies($departureOffer);
    }

    public function test_tour_pricing_section_can_render_from_departures_without_manual_pricing_table(): void
    {
        [, $tour] = $this->travelFixture();

        $tour->update([
            'pricing_table' => [],
        ]);

        $departure = $tour->departures()->firstOrFail();
        $departureTabId = 'month-'.$departure->departure_date?->format('Y-m');

        $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSee('data-tour-departure-tabs', false)
            ->assertSee('role="tablist"', false)
            ->assertSeeInOrder([
                'id="tour-departure-tab-'.$departureTabId.'"',
                'aria-selected="true"',
            ], false)
            ->assertSeeText('Gần nhất '.$departure->departure_date?->format('d/m'))
            ->assertSeeText($departure->departure_date?->format('d/m/Y') ?: 'Liên hệ')
            ->assertSeeText('Khách sạn 4 sao')
            ->assertSeeText('5.990.000 đ');
    }

    public function test_tour_detail_sidebar_uses_nearest_future_departure_for_price_transport_and_standard(): void
    {
        [, $tour] = $this->travelFixture();

        $tour->update([
            'base_price' => 11990000,
            'departure_location' => 'Khởi hành mặc định',
            'sale_price' => 9990000,
            'standard_label' => 'Tiêu chuẩn mặc định',
            'transport' => 'Xe mặc định',
        ]);

        $departure = $tour->departures()->firstOrFail();
        $departure->update([
            'base_price' => 8990000,
            'departure_date' => now()->addDays(8)->toDateString(),
            'departure_location' => 'Đà Nẵng theo ngày',
            'sale_price' => 7990000,
            'standard_label' => 'Khách sạn 5 sao',
            'transport_label' => 'Máy bay theo ngày',
        ]);
        $departure->refresh();

        TourDeparture::query()->create([
            'tour_id' => $tour->getKey(),
            'base_price' => 2990000,
            'departure_date' => now()->addDays(20)->toDateString(),
            'departure_location' => 'Khởi hành xa hơn',
            'sale_price' => 1990000,
            'standard_label' => 'Tiêu chuẩn xa hơn',
            'status' => 'scheduled',
            'transport_label' => 'Phương tiện xa hơn',
        ]);

        $html = $this->get(route('tours.show', $tour))
            ->assertOk()
            ->getContent();

        $sidebarHtml = $this->tourDetailSidebarHtml($html);

        $this->assertNotSame('', $sidebarHtml);
        $this->assertStringContainsString('8.990.000 đ / Khách', $sidebarHtml);
        $this->assertStringContainsString('7.990.000 đ', $sidebarHtml);
        $this->assertStringContainsString('Khởi hành mặc định', $sidebarHtml);
        $this->assertStringContainsString('Ngày khởi hành', $sidebarHtml);
        $this->assertStringContainsString($departure->departure_date?->format('d-m-Y'), $sidebarHtml);
        $this->assertStringContainsString('Di chuyển', $sidebarHtml);
        $this->assertStringContainsString('Máy bay theo ngày', $sidebarHtml);
        $this->assertStringContainsString('Khách sạn 5 sao', $sidebarHtml);
        $this->assertStringNotContainsString('9.990.000 đ', $sidebarHtml);
        $this->assertStringNotContainsString('1.990.000 đ', $sidebarHtml);
        $this->assertStringNotContainsString('Đà Nẵng theo ngày', $sidebarHtml);
        $this->assertStringNotContainsString('Xe mặc định', $sidebarHtml);
        $this->assertStringNotContainsString('Tiêu chuẩn mặc định', $sidebarHtml);
        $this->assertStringNotContainsString('Khởi hành xa hơn', $sidebarHtml);
        $this->assertStringNotContainsString('Phương tiện xa hơn', $sidebarHtml);
    }

    public function test_tour_detail_sidebar_standard_falls_back_to_tour_when_future_departure_has_no_standard(): void
    {
        [, $tour] = $this->travelFixture();

        $tour->update([
            'standard_label' => 'Tiêu chuẩn mặc định',
        ]);

        $departure = $tour->departures()->firstOrFail();
        $departure->update([
            'departure_date' => now()->addDays(8)->toDateString(),
            'standard_label' => null,
        ]);

        $html = $this->get(route('tours.show', $tour))
            ->assertOk()
            ->getContent();

        $sidebarHtml = $this->tourDetailSidebarHtml($html);

        $this->assertNotSame('', $sidebarHtml);
        $this->assertStringContainsString('Tiêu chuẩn mặc định', $sidebarHtml);
    }

    public function test_tour_detail_sidebar_falls_back_to_tour_defaults_without_future_departures(): void
    {
        [, $tour] = $this->travelFixture();

        $tour->update([
            'base_price' => 5990000,
            'departure_location' => 'Khởi hành mặc định',
            'departure_schedules' => ['Lịch linh hoạt theo yêu cầu'],
            'sale_price' => 4990000,
            'standard_label' => 'Tiêu chuẩn mặc định',
            'transport' => 'Xe mặc định',
        ]);

        $departure = $tour->departures()->firstOrFail();
        $departure->update([
            'base_price' => 2990000,
            'departure_date' => now()->subDay()->toDateString(),
            'departure_location' => 'Khởi hành đã qua',
            'sale_price' => 1990000,
            'standard_label' => 'Tiêu chuẩn đã qua',
            'transport_label' => 'Phương tiện đã qua',
        ]);
        $departure->refresh();

        $html = $this->get(route('tours.show', $tour))
            ->assertOk()
            ->getContent();

        $sidebarHtml = $this->tourDetailSidebarHtml($html);

        $this->assertNotSame('', $sidebarHtml);
        $this->assertStringContainsString('5.990.000 đ / Khách', $sidebarHtml);
        $this->assertStringContainsString('4.990.000 đ', $sidebarHtml);
        $this->assertStringContainsString('Khởi hành mặc định', $sidebarHtml);
        $this->assertStringContainsString('Lịch linh hoạt theo yêu cầu', $sidebarHtml);
        $this->assertStringContainsString('Xe mặc định', $sidebarHtml);
        $this->assertStringContainsString('Tiêu chuẩn mặc định', $sidebarHtml);
        $this->assertStringNotContainsString('1.990.000 đ', $sidebarHtml);
        $this->assertStringNotContainsString('Khởi hành đã qua', $sidebarHtml);
        $this->assertStringNotContainsString($departure->departure_date?->format('d-m-Y'), $sidebarHtml);
        $this->assertStringNotContainsString('Phương tiện đã qua', $sidebarHtml);
        $this->assertStringNotContainsString('Tiêu chuẩn đã qua', $sidebarHtml);
    }

    public function test_tour_card_data_uses_nearest_future_departure_for_price_transport_and_standard(): void
    {
        [, $tour] = $this->travelFixture();

        $tour->update([
            'base_price' => 11990000,
            'departure_location' => 'Khởi hành mặc định',
            'sale_price' => 9990000,
            'standard_label' => 'Tiêu chuẩn mặc định',
            'transport' => 'Xe mặc định',
        ]);

        $departure = $tour->departures()->firstOrFail();
        $departure->update([
            'base_price' => 8990000,
            'departure_date' => now()->addDays(8)->toDateString(),
            'departure_location' => 'Đà Nẵng theo ngày',
            'sale_price' => 7990000,
            'standard_label' => 'Khách sạn 5 sao',
            'transport_label' => 'Máy bay theo ngày',
        ]);
        $departure->refresh();

        TourDeparture::query()->create([
            'tour_id' => $tour->getKey(),
            'base_price' => 2990000,
            'departure_date' => now()->addDays(20)->toDateString(),
            'departure_location' => 'Khởi hành xa hơn',
            'sale_price' => 1990000,
            'standard_label' => 'Tiêu chuẩn xa hơn',
            'status' => 'scheduled',
            'transport_label' => 'Phương tiện xa hơn',
        ]);

        $card = FrontsiteCardData::tour($tour->fresh(['departures', 'destination', 'region', 'primaryCategory']));

        $this->assertSame('7.990.000 đ', $card['price_label']);
        $this->assertSame(8990000, $card['base_price_value']);
        $this->assertSame('Khởi hành mặc định', $card['departure_place']);
        $this->assertSame($departure->departure_date?->format('d/m/Y'), $card['next_departure_label']);
        $this->assertSame('Máy bay theo ngày', $card['transport_label']);
        $this->assertSame('Khách sạn 5 sao', $card['standard_label']);
    }

    public function test_tour_card_data_falls_back_to_tour_defaults_without_future_departures(): void
    {
        [, $tour] = $this->travelFixture();

        $tour->update([
            'base_price' => 5990000,
            'departure_location' => 'Khởi hành mặc định',
            'departure_schedules' => ['Lịch linh hoạt theo yêu cầu'],
            'sale_price' => 4990000,
            'standard_label' => 'Tiêu chuẩn mặc định',
            'transport' => 'Xe mặc định',
        ]);

        $tour->departures()->firstOrFail()->update([
            'base_price' => 2990000,
            'departure_date' => now()->subDay()->toDateString(),
            'departure_location' => 'Khởi hành đã qua',
            'sale_price' => 1990000,
            'standard_label' => 'Tiêu chuẩn đã qua',
            'transport_label' => 'Phương tiện đã qua',
        ]);

        $card = FrontsiteCardData::tour($tour->fresh(['departures', 'destination', 'region', 'primaryCategory']));

        $this->assertSame('4.990.000 đ', $card['price_label']);
        $this->assertSame(5990000, $card['base_price_value']);
        $this->assertSame('Khởi hành mặc định', $card['departure_place']);
        $this->assertSame('Lịch linh hoạt theo yêu cầu', $card['next_departure_label']);
        $this->assertSame('Xe mặc định', $card['transport_label']);
        $this->assertSame('Tiêu chuẩn mặc định', $card['standard_label']);
    }

    public function test_tour_card_data_standard_falls_back_to_tour_when_future_departure_has_no_standard(): void
    {
        [, $tour] = $this->travelFixture();

        $tour->update([
            'standard_label' => 'Tiêu chuẩn mặc định',
        ]);

        $tour->departures()->firstOrFail()->update([
            'departure_date' => now()->addDays(8)->toDateString(),
            'standard_label' => null,
        ]);

        $card = FrontsiteCardData::tour($tour->fresh(['departures', 'destination', 'region', 'primaryCategory']));

        $this->assertSame('Tiêu chuẩn mặc định', $card['standard_label']);
    }

    public function test_tour_departure_month_tabs_prioritize_nearest_upcoming_departure(): void
    {
        [, $tour] = $this->travelFixture();

        TourDeparture::query()->create([
            'tour_id' => $tour->getKey(),
            'departure_date' => now()->addMonthsNoOverflow(2)->toDateString(),
            'departure_location' => 'TP. Hồ Chí Minh',
            'transport_label' => 'Máy bay',
            'standard_label' => 'Khách sạn 4 sao',
            'sale_price' => 6990000,
            'status' => 'scheduled',
        ]);

        TourDeparture::query()->create([
            'tour_id' => $tour->getKey(),
            'departure_date' => now()->subDays(5)->toDateString(),
            'departure_location' => 'TP. Hồ Chí Minh',
            'transport_label' => 'Máy bay',
            'standard_label' => 'Khách sạn 4 sao',
            'sale_price' => 4990000,
            'status' => 'scheduled',
        ]);

        $nearestDate = now()->addDays(10);
        $laterDate = now()->addMonthsNoOverflow(2);
        $pastDate = now()->subDays(5);

        $this->get(route('tours.show', $tour))
            ->assertOk()
            ->assertSeeTextInOrder([
                'Tháng '.$nearestDate->format('m/Y'),
                'Gần nhất '.$nearestDate->format('d/m'),
                'Tháng '.$laterDate->format('m/Y'),
            ])
            ->assertDontSeeText('Tháng '.$pastDate->format('m/Y'));
    }

    public function test_sitemap_only_lists_taxonomy_hubs_that_have_published_tours(): void
    {
        [$category] = $this->travelFixture();

        $emptyCategory = TourCategory::query()->create([
            'name' => 'Tour trống',
            'slug' => 'tour-trong',
            'status' => 'published',
        ]);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('tour-categories.show', $category), false)
            ->assertDontSee(route('tour-categories.show', $emptyCategory), false);
    }

    public function test_country_route_renders_country_root_hub_and_enters_sitemap(): void
    {
        [, $tour, $destination] = $this->travelFixture();
        $country = Destination::query()->updateOrCreate(
            ['slug' => 'du-lich-an-do'],
            [
                'name' => 'Ấn Độ',
                'scope' => TourScope::International->value,
                'status' => 'published',
                'is_country_root' => true,
            ],
        );
        $destination->update([
            'country_id' => $country->getKey(),
            'scope' => TourScope::International->value,
        ]);
        $tour->update(['scope' => TourScope::International->value]);

        $response = $this->get(route('countries.show', ['slug' => $country->slug]))
            ->assertOk()
            ->assertSeeText('Ấn Độ')
            ->assertSeeText($tour->title)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee(route('destinations.show', $destination), false);

        $html = $response->getContent();
        $this->assertSame(1, preg_match('/<nav aria-label="Breadcrumb"[\\s\\S]*?<\\/nav>/', $html, $matches));
        $breadcrumbText = trim(preg_replace('/\\s+/', ' ', strip_tags($matches[0])));

        $this->assertStringContainsString('Trang chủ', $breadcrumbText);
        $this->assertStringContainsString('Tour nước ngoài', $breadcrumbText);
        $this->assertStringContainsString('Ấn Độ', $breadcrumbText);
        $this->assertStringNotContainsString('Quốc gia', $breadcrumbText);

        $breadcrumbSchema = collect($this->schemaGraphFromHtml($html))
            ->first(fn (array $node): bool => data_get($node, '@type') === 'BreadcrumbList');
        $this->assertSame(
            ['Trang chủ', 'Tour nước ngoài', 'Ấn Độ'],
            collect(data_get($breadcrumbSchema, 'itemListElement'))->pluck('name')->all(),
        );
        $this->assertSame(
            [route('home'), route('tours.international'), route('countries.show', ['slug' => $country->slug])],
            collect(data_get($breadcrumbSchema, 'itemListElement'))->pluck('item')->all(),
        );

        $this->get(route('countries.show', ['slug' => 'an-do']))
            ->assertRedirect(route('countries.show', ['slug' => 'du-lich-an-do']));

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('countries.show', ['slug' => $country->slug]), false);
    }

    public function test_legacy_destination_route_redirects_to_new_destination_hub_url(): void
    {
        [, , $destination] = $this->travelFixture();

        $this->get('/diem-den/'.$destination->slug)
            ->assertRedirect(route('destinations.show', $destination));
    }

    protected function tourDetailGalleryHtml(string $html): string
    {
        if (preg_match('/<section id="tour-gallery"[\\s\\S]*?<\\/section>/', $html, $matches) !== 1) {
            return '';
        }

        return $matches[0];
    }

    protected function tourDetailSidebarHtml(string $html): string
    {
        if (preg_match('/<aside class="order-1[\\s\\S]*?<\\/aside>/', $html, $matches) !== 1) {
            return '';
        }

        return $matches[0];
    }

    protected function tourDetailHeroHtml(string $html): string
    {
        if (preg_match('/<section[^>]*id="tour-hero"[\\s\\S]*?<\\/section>/', $html, $matches) !== 1) {
            return '';
        }

        return $matches[0];
    }

    /**
     * @return array{0: \Src\Domains\Cms\Models\TourCategory, 1: \Src\Domains\Cms\Models\Tour, 2: \Src\Domains\Cms\Models\Destination, 3: \Src\Domains\Cms\Models\Region}
     */
    protected function travelFixture(): array
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'active_theme' => 'haidangtravel',
                'company_name' => 'Hải Đăng Travel',
                'site_name' => 'Hải Đăng Travel',
                'site_tagline' => 'Du lịch giá hấp dẫn cùng Hải Đăng Travel',
                'site_description' => 'Đơn vị tư vấn tour trong nước và quốc tế.',
                'seo_description' => 'Tour trong nước, tour nước ngoài và tour đoàn.',
                'tour_terms_title' => 'Điều khoản tour chung',
                'tour_terms_items' => [
                    [
                        'title' => 'Giữ chỗ',
                        'content' => '<p>Khách cần <a href="https://example.com/chinh-sach-dat-coc">đặt cọc</a> để giữ chỗ.</p>',
                    ],
                ],
                'phone' => '028 1234 5678',
                'hotline' => '0909 123 456',
                'primary_email' => 'tour@example.com',
            ],
        );

        $region = Region::query()->create([
            'name' => 'Miền Bắc',
            'slug' => 'mien-bac',
            'status' => 'published',
        ]);

        $destination = Destination::query()->create([
            'region_id' => $region->getKey(),
            'name' => 'Hà Nội',
            'slug' => 'ha-noi',
            'status' => 'published',
            'excerpt' => 'Thủ đô với nhiều điểm văn hóa và lịch city tour dễ triển khai.',
            'rating_average' => 4.9,
            'rating_count' => 86,
            'faq_items' => [
                [
                    'question' => 'Nên đi Hà Nội mùa nào đẹp?',
                    'answer' => 'Mùa thu và đầu xuân thường là thời điểm dễ đi và thời tiết dễ chịu.',
                ],
            ],
        ]);

        $category = TourCategory::query()->create([
            'name' => 'Tour văn hóa',
            'slug' => 'tour-van-hoa',
            'status' => 'published',
            'rating_average' => 4.8,
            'rating_count' => 128,
            'excerpt' => 'Nhóm tour thiên về khám phá văn hóa địa phương.',
            'content' => '<p>Landing danh mục tour văn hóa.</p>',
            'faq_items' => [
                [
                    'question' => 'Tour văn hóa có phù hợp cho gia đình không?',
                    'answer' => '<p>Phù hợp nếu gia đình ưu tiên trải nghiệm điểm đến và lịch trình vừa phải. Xem thêm <a href="https://example.com/tour-van-hoa-family">gợi ý cho gia đình</a>.</p>',
                ],
            ],
        ]);

        $tour = Tour::query()->create([
            'title' => 'Hà Nội 3 ngày 2 đêm',
            'slug' => 'ha-noi-3-ngay-2-dem',
            'excerpt' => 'Hành trình ngắn ngày khám phá phố cổ và điểm văn hóa nổi bật.',
            'content' => '<p>Nội dung tour Hà Nội.</p>',
            'cover_image_url' => 'https://example.com/tour-ha-noi-cover.jpg',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->getKey(),
            'destination_id' => $destination->getKey(),
            'region_id' => $region->getKey(),
            'transport' => 'Máy bay',
            'departure_location' => 'TP. Hồ Chí Minh',
            'duration_days' => 3,
            'duration_nights' => 2,
            'standard_label' => 'Khách sạn 4 sao',
            'sale_price' => 5990000,
            'rating_average' => 4.9,
            'rating_count' => 214,
            'cta_mode' => 'booking',
            'pricing_table' => [
                [
                    'label' => 'Giá từ',
                    'price' => '5.990.000 đ',
                ],
                [
                    'label' => 'Phụ thu phòng đơn',
                    'price' => '1.200.000 đ',
                ],
            ],
            'itinerary' => [
                [
                    'title' => 'Phố cổ và hồ Hoàn Kiếm',
                    'content' => '<p>Khởi hành city tour trung tâm và khám phá <a href="https://example.com/pho-co-ha-noi">phố cổ Hà Nội</a>.</p>',
                ],
                [
                    'title' => 'Làng nghề và ẩm thực',
                    'content' => 'Tiếp tục hành trình trải nghiệm làng nghề và ẩm thực Hà Nội.',
                ],
            ],
            'faq_items' => [
                [
                    'question' => 'Tour này phù hợp với nhóm khách nào?',
                    'answer' => '<p>Phù hợp với khách muốn đi ngắn ngày, ưu tiên trải nghiệm văn hóa và ẩm thực Hà Nội. Xem thêm <a href="https://example.com/nhom-khach-phu-hop">hướng dẫn</a>.</p>',
                ],
            ],
            'gallery' => $this->defaultTourGalleryItems(),
            'published_at' => now(),
        ]);

        TravelReview::query()->create([
            'reviewable_type' => Destination::class,
            'reviewable_id' => $destination->id,
            'author_name' => 'Khách đoàn quận 1',
            'author_title' => 'Đoàn 18 khách',
            'title' => 'Điểm đến dễ triển khai',
            'content' => 'Hà Nội phù hợp cho đoàn cần lịch trình city tour gọn và nhiều điểm dừng dễ chọn.',
            'rating_value' => 4.9,
            'status' => 'published',
            'published_at' => '2026-04-12',
        ]);

        TravelReview::query()->create([
            'reviewable_type' => TourCategory::class,
            'reviewable_id' => $category->id,
            'author_name' => 'Gia đình chị Lan',
            'author_title' => 'Khởi hành từ TP. Hồ Chí Minh',
            'title' => 'Lịch trình dễ theo',
            'content' => 'Nhóm tour này có nhiều lựa chọn dễ đi cho gia đình và người lớn tuổi.',
            'rating_value' => 4.8,
            'status' => 'published',
            'published_at' => '2026-04-10',
        ]);

        TravelReview::query()->create([
            'reviewable_type' => Tour::class,
            'reviewable_id' => $tour->id,
            'author_name' => 'Anh Minh',
            'author_title' => 'Nhóm 4 khách',
            'title' => 'Lịch đi gọn',
            'content' => 'Tour đi gọn, hướng dẫn viên hỗ trợ tốt và khâu tư vấn trước chuyến đi khá rõ ràng.',
            'rating_value' => 5.0,
            'status' => 'published',
            'published_at' => '2026-04-15',
        ]);

        TourDeparture::query()->create([
            'tour_id' => $tour->getKey(),
            'departure_date' => now()->addDays(10)->toDateString(),
            'departure_location' => 'TP. Hồ Chí Minh',
            'transport_label' => 'Máy bay',
            'standard_label' => 'Khách sạn 4 sao',
            'sale_price' => 5990000,
            'status' => 'scheduled',
        ]);

        return [$category, $tour, $destination, $region];
    }

    protected function assertOfferHasMerchantPolicies(array $offer): void
    {
        $this->assertSame('MerchantReturnPolicy', data_get($offer, 'hasMerchantReturnPolicy.@type'));
        $this->assertSame('VN', data_get($offer, 'hasMerchantReturnPolicy.applicableCountry'));
        $this->assertSame('https://schema.org/MerchantReturnNotPermitted', data_get($offer, 'hasMerchantReturnPolicy.returnPolicyCategory'));
        $this->assertSame('OfferShippingDetails', data_get($offer, 'shippingDetails.@type'));
        $this->assertSame('VN', data_get($offer, 'shippingDetails.shippingDestination.addressCountry'));
        $this->assertSame('VND', data_get($offer, 'shippingDetails.shippingRate.currency'));
        $this->assertSame(0, data_get($offer, 'shippingDetails.shippingRate.value'));
        $this->assertSame('DAY', data_get($offer, 'shippingDetails.deliveryTime.handlingTime.unitCode'));
        $this->assertSame(0, data_get($offer, 'shippingDetails.deliveryTime.handlingTime.minValue'));
        $this->assertSame(0, data_get($offer, 'shippingDetails.deliveryTime.transitTime.maxValue'));
    }

    protected function organizationIdForTest(): string
    {
        return \App\Support\FrontsiteUrls::canonicalUrl(route('home')).'#organization';
    }
    protected function schemaGraphFromHtml(string $html): array
    {
        $this->assertSame(1, preg_match('/<script type="application\/ld\+json">(.+?)<\/script>/s', $html, $matches));

        $schema = json_decode($matches[1], true);

        $this->assertIsArray($schema);

        return data_get($schema, '@graph', []);
    }

    protected function schemaNodeById(string $html, string $id): array
    {
        $node = collect($this->schemaGraphFromHtml($html))
            ->first(fn (array $item): bool => data_get($item, '@id') === $id);

        $this->assertIsArray($node, 'Expected schema node '.$id.' to exist.');

        return $node;
    }

    protected function schemaListItemEntityById(string $html, string $id): array
    {
        foreach ($this->schemaGraphFromHtml($html) as $node) {
            foreach ((array) data_get($node, 'itemListElement', []) as $item) {
                $entity = data_get($item, 'item');

                if (is_array($entity) && data_get($entity, '@id') === $id) {
                    return $entity;
                }
            }
        }

        $this->fail('Expected schema list item entity '.$id.' to exist.');
    }

    protected function defaultTourGalleryItems(): array
    {
        return [
            [
                'uuid' => 'tour-gallery-image',
                'type' => 'image',
                'title' => 'Khoảnh khắc phố cổ',
                'description' => 'Ảnh check-in và nhịp khám phá phố cổ trong hành trình Hà Nội.',
                'image_alt' => 'Phố cổ Hà Nội buổi sáng',
                'image_url' => 'https://example.com/tour-gallery-image.jpg',
                'video_url' => '',
            ],
            [
                'uuid' => 'tour-gallery-youtube',
                'type' => 'youtube',
                'title' => 'Video YouTube hành trình',
                'description' => 'Video preview cho nhịp trải nghiệm của tour Hà Nội 3 ngày 2 đêm.',
                'image_alt' => '',
                'image_url' => '',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
            [
                'uuid' => 'tour-gallery-mp4',
                'type' => 'mp4',
                'title' => 'Video MP4 tour',
                'description' => 'Clip MP4 quay ngắn toàn cảnh hành trình Hà Nội.',
                'image_alt' => '',
                'image_url' => '',
                'video_url' => 'https://cdn.example.com/tour-gallery.mp4',
            ],
        ];
    }
}
