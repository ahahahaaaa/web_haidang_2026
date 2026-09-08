<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Seo\Enums\SeoPageStatus;
use Src\Domains\Seo\Enums\SeoPageType;
use Src\Domains\Seo\Models\ContentCluster;
use Src\Domains\Seo\Models\SeoLink;
use Src\Domains\Seo\Models\SeoPage;
use Tests\TestCase;

class SeoPagePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_seo_admin_can_preview_draft_destination_page_in_frontsite_shell(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();
        $cluster = ContentCluster::query()->create([
            'name' => 'SEO preview destination',
            'primary_keyword' => 'tour phú quốc',
            'secondary_keywords' => ['kinh nghiệm đi phú quốc'],
            'lsi_keywords' => ['lịch khởi hành phú quốc'],
            'intent' => 'commercial',
            'target_page_type' => SeoPageType::Destination->value,
            'business_value' => 7,
            'priority_score' => 70,
            'status' => 'completed',
            'context' => [
                'cta' => 'Nhận tư vấn tour Phú Quốc',
                'location' => 'Phú Quốc',
            ],
        ]);
        $relatedPage = SeoPage::query()->create([
            'content_cluster_id' => $cluster->getKey(),
            'page_type' => SeoPageType::TourCategory->value,
            'title' => 'Tour trong nước',
            'slug' => 'tour-trong-nuoc',
            'canonical_url' => url('/danh-muc-tour/tour-trong-nuoc'),
            'primary_keyword' => 'tour trong nước',
            'h1' => 'Danh mục tour trong nước',
            'excerpt' => 'Trang danh mục liên quan dùng để kiểm tra internal links khi preview.',
            'content' => '## Danh mục tour',
            'status' => SeoPageStatus::Published,
            'published_at' => now(),
        ]);
        $page = SeoPage::query()->create([
            'content_cluster_id' => $cluster->getKey(),
            'page_type' => SeoPageType::Destination->value,
            'title' => 'Tour Phú Quốc giá tốt',
            'slug' => 'phu-quoc-preview',
            'canonical_url' => url('/tour-phu-quoc-preview'),
            'primary_keyword' => 'tour phú quốc',
            'h1' => 'Tour Phú Quốc giá tốt, lịch khởi hành mới nhất',
            'excerpt' => 'Trang preview dùng để kiểm tra frontsite trước khi publish.',
            'content' => "## Điểm đến nổi bật\n\nĐây là nội dung xem trước cho trang điểm đến.",
            'status' => SeoPageStatus::Draft,
        ]);
        SeoLink::query()->create([
            'source_page_id' => $page->getKey(),
            'target_page_id' => $relatedPage->getKey(),
            'anchor_text' => 'danh mục tour trong nước',
            'link_type' => 'related',
            'priority' => 60,
            'status' => 'suggested',
        ]);

        $this->actingAs($user)
            ->get(route('admin.seo.pages.preview', $page))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('Chế độ xem trước frontsite cho SEO page này', false)
            ->assertSee('meta name="robots" content="noindex,nofollow"', false)
            ->assertSeeText('Tour Phú Quốc giá tốt, lịch khởi hành mới nhất')
            ->assertSeeText('Điểm cần chốt trước khi mở rộng cụm nội dung quanh Phú Quốc')
            ->assertSeeText('danh mục tour trong nước');
    }
}
