<?php

namespace Tests\Feature;

use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Seo\Enums\SeoPageStatus;
use Src\Domains\Seo\Enums\SeoPageType;
use Src\Domains\Seo\Models\ContentCluster;
use Src\Domains\Seo\Models\SeoLink;
use Src\Domains\Seo\Models\SeoPage;
use Src\Domains\Seo\Support\SeoSchemaFactory;
use Tests\TestCase;

class PublicSeoPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_destination_seo_page_renders_travel_hub_with_internal_links_and_faq(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        SiteSetting::query()->firstOrFail()->update([
            'address' => '357 Phan Văn Trị, Gò Vấp, TP. Hồ Chí Minh',
            'hotline' => '0909 999 222',
            'primary_email' => 'tour@haidangtravel.com',
        ]);

        $cluster = ContentCluster::query()->create([
            'name' => 'SEO điểm đến Phú Quốc',
            'primary_keyword' => 'tour phú quốc',
            'secondary_keywords' => ['tour phú quốc giá tốt', 'kinh nghiệm đi phú quốc'],
            'lsi_keywords' => ['lịch khởi hành phú quốc'],
            'intent' => 'commercial',
            'target_page_type' => SeoPageType::Destination->value,
            'business_value' => 8,
            'priority_score' => 80,
            'status' => 'completed',
            'context' => [
                'location' => 'Phú Quốc',
                'cta' => 'Nhận tư vấn tour Phú Quốc',
            ],
        ]);

        $relatedPage = SeoPage::query()->create([
            'content_cluster_id' => $cluster->getKey(),
            'page_type' => SeoPageType::TourCategory->value,
            'title' => 'Tour trong nước',
            'slug' => 'tour-trong-nuoc',
            'canonical_url' => url('/danh-muc-tour/tour-trong-nuoc'),
            'primary_keyword' => 'tour trong nước',
            'secondary_keywords' => ['tour việt nam'],
            'h1' => 'Danh mục tour trong nước',
            'excerpt' => 'Trang danh mục giúp gom các lựa chọn tour nội địa theo nhóm nhu cầu phổ biến.',
            'content' => '## Danh mục tour trong nước'."\n\n".str_repeat('Trang này tổng hợp các lựa chọn tour nổi bật, lịch khởi hành và cách chọn tour phù hợp theo nhóm nhu cầu thực tế. ', 18),
            'status' => SeoPageStatus::Published,
            'published_at' => now(),
        ]);

        $page = SeoPage::query()->create([
            'content_cluster_id' => $cluster->getKey(),
            'page_type' => SeoPageType::Destination->value,
            'title' => 'Tour Phú Quốc giá tốt',
            'slug' => 'phu-quoc',
            'canonical_url' => url('/tour-phu-quoc'),
            'primary_keyword' => 'tour phú quốc',
            'secondary_keywords' => ['tour phú quốc giá tốt', 'du lịch phú quốc'],
            'h1' => 'Tour Phú Quốc giá tốt, lịch khởi hành mới nhất',
            'excerpt' => 'Trang điểm đến này giúp người đọc chốt nhanh thời điểm đi, kiểu hành trình và nhóm tour phù hợp trước khi đặt dịch vụ.',
            'content' => implode("\n\n", [
                '## Vì sao nhiều người chọn Phú Quốc',
                str_repeat('Phú Quốc phù hợp với khách gia đình, nhóm bạn và khách muốn nghỉ dưỡng vì có nhiều lựa chọn lịch trình, điểm tham quan và khung khởi hành linh hoạt. ', 10),
                '## Khi nào nên đi',
                str_repeat('Người đọc thường cần biết mùa đi, nhịp tham quan, kiểu trải nghiệm và cách chọn tour phù hợp hơn với thời gian lưu trú thực tế. ', 10),
                '## Gợi ý hành trình',
                str_repeat('Trang hub nên dẫn tiếp sang tour cụ thể, danh mục tour trong nước, dịch vụ liên quan và các bài viết hỗ trợ quyết định trước khi đặt. ', 10),
            ]),
            'faq_items' => [
                [
                    'question' => 'Đi Phú Quốc nên chọn tour mấy ngày?',
                    'answer' => 'Tùy quỹ thời gian, tour 3 ngày 2 đêm thường phù hợp với nhóm khách muốn kết hợp nghỉ dưỡng và tham quan các điểm chính.',
                ],
            ],
            'schema' => [
                ['@context' => 'https://schema.org', '@type' => 'CollectionPage', 'name' => 'Tour Phú Quốc'],
                ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList'],
                ['@context' => 'https://schema.org', '@type' => 'ItemList'],
            ],
            'status' => SeoPageStatus::Published,
            'published_at' => now(),
        ]);

        SeoLink::query()->create([
            'source_page_id' => $page->getKey(),
            'target_page_id' => $relatedPage->getKey(),
            'anchor_text' => 'danh mục tour trong nước',
            'link_type' => 'related',
            'priority' => 60,
            'status' => 'suggested',
        ]);

        $path = parse_url($page->canonical_url, PHP_URL_PATH) ?: '/tour-phu-quoc';

        $this->get($path)
            ->assertOk()
            ->assertSeeText('Tour Phú Quốc giá tốt, lịch khởi hành mới nhất')
            ->assertSeeText('Điểm đến')
            ->assertSeeText('Điểm cần chốt trước khi mở rộng cụm nội dung quanh Phú Quốc')
            ->assertSee('data-ai-summary', false)
            ->assertSeeText('danh mục tour trong nước')
            ->assertSee(url('/danh-muc-tour/tour-trong-nuoc'), false)
            ->assertSeeText('Đi Phú Quốc nên chọn tour mấy ngày?')
            ->assertSeeText('Tùy quỹ thời gian, tour 3 ngày 2 đêm thường phù hợp với nhóm khách muốn kết hợp nghỉ dưỡng và tham quan các điểm chính.')
            ->assertSee(url('/tour-phu-quoc'), false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"@type":"ItemList"', false);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(url('/tour-phu-quoc'), false);
    }

    public function test_non_published_seo_page_is_not_publicly_accessible(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        SeoPage::query()->create([
            'page_type' => SeoPageType::Destination->value,
            'title' => 'Bản nháp điểm đến',
            'slug' => 'ban-nhap-diem-den',
            'canonical_url' => url('/tour-ban-nhap-diem-den'),
            'primary_keyword' => 'bản nháp điểm đến',
            'h1' => 'Bản nháp điểm đến',
            'content' => '## Nội dung nháp',
            'status' => SeoPageStatus::Draft,
        ]);

        $this->get('/tour-ban-nhap-diem-den')->assertNotFound();
    }

    public function test_blog_seo_page_schema_includes_article_author(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $page = SeoPage::query()->create([
            'page_type' => SeoPageType::Blog->value,
            'title' => 'Kinh nghiệm đi Phú Quốc',
            'slug' => 'kinh-nghiem-di-phu-quoc',
            'canonical_url' => url('/seo-pages/kinh-nghiem-di-phu-quoc'),
            'primary_keyword' => 'kinh nghiệm đi phú quốc',
            'h1' => 'Kinh nghiệm đi Phú Quốc lần đầu',
            'excerpt' => 'Bài viết hướng dẫn người đọc chuẩn bị lịch trình, chi phí và thời điểm đi phù hợp hơn.',
            'content' => "## Chuẩn bị trước chuyến đi\n\n".str_repeat('Nội dung cẩm nang giúp người đọc hiểu rõ bối cảnh trước khi chọn tour hoặc dịch vụ phù hợp. ', 20),
            'status' => SeoPageStatus::Published,
            'published_at' => now(),
        ]);

        $articleSchema = collect(app(SeoSchemaFactory::class)->forPage($page))
            ->firstWhere('@type', 'Article');

        $this->assertNotNull($articleSchema);
        $this->assertSame('Organization', data_get($articleSchema, 'author.@type'));
        $this->assertSame(config('seo_ai.business_name'), data_get($articleSchema, 'author.name'));
    }

    public function test_published_contact_seo_page_renders_travel_contact_sections(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        SiteSetting::query()->firstOrFail()->update([
            'address' => '357 Phan Văn Trị, Gò Vấp, TP. Hồ Chí Minh',
            'hotline' => '0909 999 222',
            'primary_email' => 'tour@haidangtravel.com',
        ]);

        $page = SeoPage::query()->create([
            'page_type' => SeoPageType::Contact->value,
            'title' => 'Liên hệ tư vấn tour',
            'slug' => 'lien-he-tu-van-tour',
            'canonical_url' => url('/seo-pages/lien-he-tu-van-tour'),
            'primary_keyword' => 'liên hệ tư vấn tour',
            'h1' => 'Liên hệ tư vấn tour nhanh',
            'excerpt' => 'Trang contact giúp định tuyến nhanh nhu cầu tour, visa và dịch vụ du lịch tới đúng đầu mối phụ trách.',
            'content' => "## Liên hệ nhanh\n\nNội dung trang contact cho khách đang cần tư vấn tour hoặc dịch vụ du lịch.",
            'status' => SeoPageStatus::Published,
            'published_at' => now(),
        ]);

        $path = parse_url($page->canonical_url, PHP_URL_PATH) ?: '/seo-pages/lien-he-tu-van-tour';

        $this->get($path)
            ->assertOk()
            ->assertSeeText('Kênh liên hệ phù hợp cho từng nhu cầu')
            ->assertSeeText('Chuẩn bị brief trước khi gửi yêu cầu')
            ->assertSeeText('0909 999 222')
            ->assertSeeText('tour@haidangtravel.com')
            ->assertSeeText('357 Phan Văn Trị, Gò Vấp, TP. Hồ Chí Minh');
    }
}
