<?php

namespace Tests\Feature\SeoOptimization;

use App\Models\SeoOptimizationPage;
use App\Models\User;
use App\Services\SeoOptimization\PageRegistryService;
use App\Services\SeoOptimization\PageSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class PageSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['seo_optimization.site_id' => 'haidang-test', 'frontsite_seo.canonical_url' => 'https://haidangtravel.test']);
        SiteSetting::query()->create(['id' => 1, 'active_theme' => 'haidangtravel', 'site_name' => 'Hải Đăng Travel', 'company_name' => 'Hải Đăng Travel', 'seo_description' => 'Du lịch cùng Hải Đăng Travel.']);
    }

    public function test_capture_uses_real_public_blade_and_restores_authenticated_context_without_http(): void
    {
        Http::preventStrayRequests();
        $service = Service::query()->create([
            'title' => 'Tư vấn hành trình Đà Nẵng', 'slug' => 'tu-van-da-nang', 'status' => 'published',
            'meta_title' => 'Hành trình Đà Nẵng cùng Hải Đăng', 'meta_description' => 'Tư vấn hành trình phù hợp nhu cầu của bạn.',
            'content' => '<h2>Chuẩn bị hành trình</h2><p>Chọn điểm đến và trao đổi nhu cầu với tư vấn viên.</p><p><a href="/tour-trong-nuoc">Tour trong nước</a></p>',
        ]);
        $user = User::factory()->create();
        $this->actingAs($user);
        session()->put('_old_input', ['customer_name' => 'PRIVATE_CUSTOMER_NAME']);
        $originalRequest = request();
        $registry = app(PageRegistryService::class);
        $registry->sync();
        $page = SeoOptimizationPage::query()->where('page_type', 'service')->firstOrFail();

        $snapshot = app(PageSnapshotService::class)->capture($page);

        $this->assertSame(200, $snapshot['http_status']);
        $this->assertSame($service->meta_title, $snapshot['title']);
        $this->assertSame($service->title, $snapshot['h1']);
        $this->assertSame('https://haidangtravel.test/dich-vu/tu-van-da-nang', $snapshot['canonical']);
        $this->assertStringContainsString('Chuẩn bị hành trình', $snapshot['html']);
        $this->assertStringNotContainsString('PRIVATE_CUSTOMER_NAME', json_encode($snapshot));
        $this->assertStringNotContainsString('name="_token"', $snapshot['html']);
        $this->assertStringContainsString('Service', json_encode($snapshot['structured_data']));
        $this->assertSame('page', data_get($snapshot, 'fact_sources.0.id'));
        $this->assertSame('current_page_snapshot', data_get($snapshot, 'fact_sources.0.kind'));
        $this->assertSame($snapshot['rendered_hash'], data_get($snapshot, 'fact_sources.0.rendered_hash'));
        $this->assertStringContainsString('baseline chính xác', data_get($snapshot, 'fact_sources.0.usage'));
        $this->assertSame($originalRequest, request());
        $this->assertSame($user->id, auth()->id());
        $this->assertSame('PRIVATE_CUSTOMER_NAME', session()->get('_old_input.customer_name'));
        Http::assertNothingSent();
    }

    public function test_parser_excludes_chrome_forms_hidden_content_and_unsafe_links(): void
    {
        $html = '<html><head><title>Đà Nẵng</title><meta name="description" content="Hành trình"><script type="application/ld+json">{"@type":"WebPage"}</script><script type="application/ld+json">invalid</script></head><body><header>HEADER_KEYWORD</header><main><h1>Đà Nẵng</h1><h2>Điểm đến</h2><p hidden>HIDDEN_KEYWORD</p><nav>NAV_KEYWORD</nav><form><input value="PRIVATE_TOKEN"></form><p>Nội dung thực tế</p><figure><img src="/img.jpg" alt="Bờ biển Đà Nẵng"><figcaption>Biển Đà Nẵng</figcaption></figure><a href="/tour-trong-nuoc">Tour nội địa</a><a href="https://evil.example/path">Ngoài site</a><a href="javascript:alert(1)">Không hợp lệ</a></main><footer>FOOTER_KEYWORD</footer></body></html>';

        $snapshot = app(PageSnapshotService::class)->parse($html, 'https://haidangtravel.test/da-nang');

        $this->assertSame('Đà Nẵng', $snapshot['h1']);
        $this->assertSame(1, $snapshot['h1_count']);
        $this->assertCount(2, $snapshot['headings']);
        $this->assertCount(1, $snapshot['internal_links']);
        $this->assertSame('https://haidangtravel.test/tour-trong-nuoc', $snapshot['internal_links'][0]['url']);
        $this->assertSame('Biển Đà Nẵng', $snapshot['media'][0]['caption']);
        $this->assertCount(1, $snapshot['schema_errors']);
        foreach (['HEADER_KEYWORD', 'HIDDEN_KEYWORD', 'NAV_KEYWORD', 'FOOTER_KEYWORD', 'PRIVATE_TOKEN'] as $private) {
            $this->assertStringNotContainsString($private, $snapshot['html']);
        }
    }

    public function test_private_page_cannot_be_captured_even_when_admin_is_authenticated(): void
    {
        Service::query()->create(['title' => 'Nháp', 'slug' => 'nhap', 'status' => 'draft', 'content' => 'PRIVATE_CONTENT']);
        $this->actingAs(User::factory()->create());
        app(PageRegistryService::class)->sync();
        $page = SeoOptimizationPage::query()->where('page_type', 'service')->firstOrFail();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Không chụp nội dung riêng tư');
        app(PageSnapshotService::class)->capture($page);
    }
}
