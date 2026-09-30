<?php

namespace Tests\Feature;

use App\Services\Frontsite\FrontsiteCache;
use App\Services\Seo\SitemapBuilder;
use App\Support\FrontsiteUrls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\PublicUrlMapping;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Tests\TestCase;

class MigrationSitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['frontsite_seo.canonical_url' => 'https://haidangtravel.com']);
        SiteSetting::query()->create(['active_theme' => 'haidangtravel', 'seo_robots' => 'index,follow']);
    }

    public function test_index_and_robots_advertise_typed_canonical_sitemaps(): void
    {
        $this->blogFixture();

        $response = $this->get('/sitemap-index.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $this->assertSame('sitemapindex', $xml->getName());
        $this->assertSame(['https://haidangtravel.com/sitemap-pages.xml', 'https://haidangtravel.com/sitemap-blogs.xml'], array_map('strval', iterator_to_array($xml->xpath('//*[local-name()="loc"]'))));
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: https://haidangtravel.com/sitemap-index.xml', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('<urlset ', false);
    }

    public function test_preserved_blog_url_uses_original_path_and_target_timestamp_without_changing_content(): void
    {
        $post = $this->blogFixture();
        $post->timestamps = false;
        $post->forceFill(['created_at' => '2019-01-02 03:04:05', 'updated_at' => '2020-02-03 04:05:06'])->save();
        $post = $post->fresh();
        $before = $post->fresh()->getAttributes();
        $this->mapping($post, 'blog_post', '/tin-tuc/bai-viet-cu');

        $response = $this->get('/sitemap-blogs.xml')->assertOk();
        $response->assertSee('<loc>https://haidangtravel.com/tin-tuc/bai-viet-cu</loc>', false)
            ->assertSee('<lastmod>'.$post->updated_at->toAtomString().'</lastmod>', false)
            ->assertDontSee('/admin/legacy-migrations', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('https://haidangtravel.com/tin-tuc/bai-viet-cu', false);
        $this->get('https://haidangtravel.com/tin-tuc/bai-viet-cu')->assertOk()->assertSee('<link rel="canonical" href="https://haidangtravel.com/tin-tuc/bai-viet-cu">', false);
        $this->assertSame($before, $post->fresh()->getAttributes());
    }

    public function test_preserved_targets_are_grouped_by_their_cms_type(): void
    {
        $category = TourCategory::query()->create(['name' => 'Tour chủ đề', 'slug' => 'chu-de', 'status' => 'published']);
        $region = Region::query()->create(['name' => 'Vùng miền', 'slug' => 'vung-mien', 'status' => 'published']);
        $country = Destination::query()->create(['name' => 'Việt Nam', 'slug' => 'viet-nam', 'status' => 'published', 'is_country_root' => true]);
        $destination = Destination::query()->create(['name' => 'Đà Lạt', 'slug' => 'da-lat', 'status' => 'published', 'country_id' => $country->id]);
        $tour = Tour::query()->create(['title' => 'Tour Đà Lạt', 'slug' => 'tour-da-lat', 'status' => 'published', 'scope' => 'domestic', 'tour_category_id' => $category->id, 'region_id' => $region->id, 'destination_id' => $destination->id]);
        $tour->destinations()->syncWithoutDetaching([$country->id, $destination->id]);
        $blogCategory = ContentCategory::query()->create(['name' => 'Cẩm nang', 'slug' => 'cam-nang', 'taxonomy' => 'blog']);
        $this->blogFixture(['content_category_id' => $blogCategory->id]);
        $serviceCategory = ContentCategory::query()->create(['name' => 'Visa', 'slug' => 'visa', 'taxonomy' => 'service']);
        $service = Service::query()->create(['title' => 'Visa du lịch', 'slug' => 'visa-du-lich', 'status' => 'published', 'content_category_id' => $serviceCategory->id]);
        $landing = LandingPage::query()->create(['title' => 'Khuyến mãi', 'slug' => 'khuyen-mai-mua-he', 'is_active' => true]);
        $cases = [
            [$tour, 'tour', 'tours'], [$category, 'tour_category', 'tour-categories'],
            [$region, 'region', 'regions'], [$country, 'destination', 'countries'],
            [$destination, 'destination', 'destinations'], [$service, 'service', 'services'],
            [$blogCategory, 'blog_category', 'blog-categories'], [$landing, 'landing_page', 'landings'],
        ];

        foreach ($cases as [$target, $targetType, $group]) {
            $this->mapping($target, $targetType, '/cu/'.$group);
            $this->get('/sitemap-'.$group.'.xml')->assertOk()->assertSee('<loc>https://haidangtravel.com/cu/'.$group.'</loc>', false);
        }

        $this->get('/sitemap-service-categories.xml')->assertOk()->assertSee('https://haidangtravel.com/dich-vu/danh-muc/visa', false)->assertDontSee('/cu/services', false);
    }

    public function test_unpublished_missing_inactive_and_noindex_targets_are_excluded(): void
    {
        $draft = $this->blogFixture(['slug' => 'draft', 'status' => 'draft']);
        $future = $this->blogFixture(['slug' => 'future', 'published_at' => now()->addDay()]);
        $noindex = $this->blogFixture(['slug' => 'noindex', 'robots_directive' => 'NOINDEX,follow']);
        $inactive = LandingPage::query()->create(['title' => 'Inactive', 'slug' => 'inactive', 'is_active' => false]);
        $system = LandingPage::query()->create(['title' => 'Home', 'page_key' => 'home', 'slug' => 'system', 'is_active' => true]);
        $empty = ContentCategory::query()->create(['name' => 'Empty', 'slug' => 'empty', 'taxonomy' => 'blog']);
        foreach ([[$draft, 'blog_post'], [$future, 'blog_post'], [$noindex, 'blog_post'], [$inactive, 'landing_page'], [$system, 'landing_page'], [$empty, 'blog_category']] as [$target, $type]) {
            $this->mapping($target, $type, '/exclude/'.$target->slug);
        }
        $this->mapping($draft, 'blog_post', '/exclude/missing', ['target_id' => '999999']);
        $this->mapping($draft, 'system_route', '/exclude/unsupported');

        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/exclude/', false)->assertDontSee('/bai-viet/noindex', false);
    }

    public function test_redirect_sources_and_inactive_mappings_are_not_included_even_when_source_is_a_native_url(): void
    {
        $post = $this->blogFixture();
        $this->mapping($post, 'blog_post', '/inactive', ['is_active' => false]);
        $this->mapping($post, 'blog_post', FrontsiteUrls::canonicalPath(FrontsiteUrls::blogPost($post)), ['mode' => PublicUrlMapping::MODE_REDIRECT, 'status_code' => 301, 'target_path' => '/blog']);

        $this->get('/sitemap-blogs.xml')->assertOk()->assertDontSee('/inactive', false)->assertDontSee('/bai-viet/bai-viet-cu', false);
    }

    public function test_legacy_blog_alias_redirects_to_the_native_self_canonical_url_and_only_target_is_in_sitemap(): void
    {
        $post = $this->blogFixture();
        $targetPath = FrontsiteUrls::canonicalPath(FrontsiteUrls::blogPost($post));
        $post->update(['canonical_url' => FrontsiteUrls::canonicalUrl($targetPath)]);
        $this->mapping($post, 'blog_post', '/tin-tuc/bai-viet-cu', [
            'mode' => PublicUrlMapping::MODE_REDIRECT,
            'target_path' => $targetPath,
            'status_code' => 301,
        ]);

        $this->get('/tin-tuc/bai-viet-cu')->assertRedirect($targetPath);
        $this->get($targetPath)->assertOk()
            ->assertSee('<link rel="canonical" href="https://haidangtravel.com'.$targetPath.'">', false);
        $this->get('/sitemap-blogs.xml')->assertOk()
            ->assertSee('<loc>https://haidangtravel.com'.$targetPath.'</loc>', false)
            ->assertDontSee('<loc>https://haidangtravel.com/tin-tuc/bai-viet-cu</loc>', false);
    }

    public function test_explicit_alternate_canonical_excludes_native_url_but_keeps_rendered_original_url(): void
    {
        $post = $this->blogFixture(['canonical_url' => '/tin-tuc/bai-viet-cu']);
        $this->mapping($post, 'blog_post', '/tin-tuc/bai-viet-cu');
        $this->get('/sitemap-blogs.xml')->assertOk()->assertSee('https://haidangtravel.com/tin-tuc/bai-viet-cu', false)->assertDontSee('https://haidangtravel.com/bai-viet/bai-viet-cu', false);
    }

    public function test_render_mapping_cannot_leak_a_native_url_when_its_actual_target_is_noindex(): void
    {
        $native = $this->blogFixture();
        $noindex = $this->blogFixture(['slug' => 'private', 'robots_directive' => 'noindex,follow']);
        $this->mapping($noindex, 'blog_post', FrontsiteUrls::canonicalPath(FrontsiteUrls::blogPost($native)));

        $this->get('/sitemap-blogs.xml')->assertOk()->assertDontSee('/bai-viet/bai-viet-cu', false)->assertDontSee('/bai-viet/private', false);
    }

    public function test_render_mapping_overriding_a_native_url_uses_actual_target_group_and_lastmod(): void
    {
        $native = $this->blogFixture();
        $service = Service::query()->create(['title' => 'Visa', 'slug' => 'visa', 'status' => 'published']);
        $service->timestamps = false;
        $service->forceFill(['updated_at' => '2021-02-03 04:05:06'])->save();
        $service = $service->fresh();
        $path = FrontsiteUrls::canonicalPath(FrontsiteUrls::blogPost($native));
        $this->mapping($service, 'service', $path);

        $this->get('/sitemap-blogs.xml')->assertOk()->assertDontSee($path, false);
        $this->get('/sitemap-services.xml')->assertOk()->assertSee('<loc>https://haidangtravel.com'.$path.'</loc>', false)->assertSee('<lastmod>'.$service->updated_at->toAtomString().'</lastmod>', false);
    }

    public function test_same_object_aliases_keep_existing_render_behavior_and_exact_urls_are_deduplicated(): void
    {
        $post = $this->blogFixture();
        $native = FrontsiteUrls::canonicalPath(FrontsiteUrls::blogPost($post));
        $this->mapping($post, 'blog_post', $native);
        $this->mapping($post, 'blog_post', '/tin-tuc/bai-viet-cu');
        $this->mapping($post, 'blog_post', '/tin-tuc/bai-viet-cu', ['origin' => 'legacy_migration']);

        $xml = simplexml_load_string($this->get('/sitemap-blogs.xml')->assertOk()->getContent());
        $this->assertCount(2, $xml->xpath('//*[local-name()="loc"]'));
        $this->assertCount(2, array_unique(array_map('strval', $xml->xpath('//*[local-name()="loc"]'))));
        $this->assertNull($post->fresh()->canonical_url);
    }

    public function test_malformed_hash_paths_and_protected_routes_are_excluded(): void
    {
        $post = $this->blogFixture();
        foreach (['/admin/private', '/api/private', '/storage/file', '/foo.xml', '//duplicate', '/has?query=1', '/has#fragment', '/bad path', '/index.php/foo', '/trailing/'] as $path) {
            $this->mapping($post, 'blog_post', $path);
        }
        $this->mapping($post, 'blog_post', '/hash-mismatch', ['source_hash' => str_repeat('0', 64)]);
        $entries = app(SitemapBuilder::class)->build();
        $this->assertCount(9, $entries);
        $this->assertSame([FrontsiteUrls::canonicalUrl(FrontsiteUrls::blogPost($post))], collect($entries)->where('type', 'blogs')->pluck('url')->all());
    }

    public function test_mappings_can_be_disabled_or_table_absent_without_breaking_current_sitemap(): void
    {
        $this->mapping($this->blogFixture(), 'blog_post', '/tin-tuc/bai-viet-cu');
        config(['public_url_mappings.enabled' => false]);
        $this->get('/sitemap-blogs.xml')->assertOk()->assertDontSee('/tin-tuc/', false)->assertSee('/bai-viet/bai-viet-cu', false);
        Schema::drop('public_url_mappings');
        config(['public_url_mappings.enabled' => true]);
        $this->get('/sitemap-blogs.xml')->assertOk()->assertSee('/bai-viet/bai-viet-cu', false);
    }

    public function test_mapping_and_target_changes_invalidate_sitemap_cache(): void
    {
        config(['frontsite_cache.enabled' => true, 'frontsite_cache.stale.enabled' => false]);
        $post = $this->blogFixture();
        $this->assertNotContains('https://haidangtravel.com/tin-tuc/bai-viet-cu', $this->sitemapUrls());
        $mapping = $this->mapping($post, 'blog_post', '/tin-tuc/bai-viet-cu');
        $this->assertContains('https://haidangtravel.com/tin-tuc/bai-viet-cu', $this->sitemapUrls());
        $mapping->update(['is_active' => false]);
        $this->assertNotContains('https://haidangtravel.com/tin-tuc/bai-viet-cu', $this->sitemapUrls());
        $mapping->update(['is_active' => true]);
        $this->assertContains('https://haidangtravel.com/tin-tuc/bai-viet-cu', $this->sitemapUrls());
        $mapping->delete();
        $this->assertNotContains('https://haidangtravel.com/tin-tuc/bai-viet-cu', $this->sitemapUrls());
        $this->mapping($post, 'blog_post', '/tin-tuc/bai-viet-cu');
        $post->update(['status' => 'draft']);
        $this->assertCount(8, $this->sitemapUrls());
    }

    public function test_sitewide_noindex_removes_entries_and_invalidates_cached_sitemaps(): void
    {
        config(['frontsite_cache.enabled' => true, 'frontsite_cache.stale.enabled' => false]);
        $this->mapping($this->blogFixture(), 'blog_post', '/tin-tuc/bai-viet-cu');
        $this->get('/sitemap-blogs.xml')->assertOk()->assertSee('/tin-tuc/', false);
        SiteSetting::query()->firstOrFail()->update(['seo_robots' => 'noindex,nofollow']);
        $this->assertSame([], app(SitemapBuilder::class)->build());
        $this->get('/sitemap-index.xml')->assertOk()->assertDontSee('<sitemap>', false);
    }

    public function test_large_groups_split_into_root_level_xml_pages_and_compatibility_sitemap_becomes_index(): void
    {
        $entries = [];
        for ($index = 1; $index <= SitemapBuilder::PAGE_SIZE + 1; $index++) {
            $entries[] = ['url' => 'https://haidangtravel.com/tin-tuc/bai-'.$index, 'type' => 'blogs', 'lastmod' => null, 'changefreq' => 'monthly', 'priority' => '0.6'];
        }
        $builder = Mockery::mock(SitemapBuilder::class, [app(FrontsiteCache::class)])->makePartial();
        $builder->shouldReceive('build')->andReturn($entries);
        $this->app->instance(SitemapBuilder::class, $builder);

        $this->get('/sitemap-index.xml')->assertOk()->assertSee('https://haidangtravel.com/sitemap-blogs.xml', false)->assertSee('https://haidangtravel.com/sitemap-blogs-2.xml', false);
        $this->assertCount(SitemapBuilder::PAGE_SIZE, simplexml_load_string($this->get('/sitemap-blogs.xml')->assertOk()->getContent())->xpath('//*[local-name()="url"]'));
        $this->get('/sitemap-blogs-2.xml')->assertOk()->assertSee('<loc>https://haidangtravel.com/tin-tuc/bai-10001</loc>', false);
        $this->get('/sitemap-blogs-3.xml')->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertSee('<sitemapindex ', false);
    }

    public function test_unknown_types_and_invalid_page_numbers_are_not_found(): void
    {
        foreach (['/sitemap-admin.xml', '/sitemap-blogs-0.xml', '/sitemap-blogs-abc.xml', '/sitemap-blogs-2.xml'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    private function blogFixture(array $attributes = []): BlogPost
    {
        return BlogPost::query()->create($attributes + ['title' => 'Bài viết cũ', 'slug' => 'bai-viet-cu', 'content' => '<p>Nội dung nguồn</p>', 'status' => 'published', 'published_at' => now()->subYear()]);
    }

    private function sitemapUrls(): array
    {
        return array_column(app(SitemapBuilder::class)->build(), 'url');
    }

    private function mapping(Model $target, string $type, string $path, array $attributes = []): PublicUrlMapping
    {
        return PublicUrlMapping::query()->updateOrCreate(['source_hash' => $attributes['source_hash'] ?? hash('sha256', $path)], $attributes + [
            'source_path' => $path, 'mode' => PublicUrlMapping::MODE_RENDER, 'target_type' => $type, 'target_id' => (string) $target->getKey(), 'target_path' => '/target/'.$target->getKey(), 'status_code' => 200, 'is_active' => true,
        ]);
    }
}
