<?php

namespace Tests\Feature;

use App\Support\FrontsiteUrls;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class FrontsiteSchemaCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_and_contact_pages_emit_page_entity_schema(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('"@id":"'.route('about').'#webpage"', false)
            ->assertSee('"@type":"AboutPage"', false)
            ->assertSee('"mainEntity":{"@id":"'.route('home').'#organization"}', false);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('"@id":"'.route('contact').'#webpage"', false)
            ->assertSee('"@type":"ContactPage"', false)
            ->assertSee('"mainEntity":{"@id":"'.route('home').'#organization"}', false);
    }

    public function test_homepage_organization_address_country_uses_vietnam_iso_code(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        SiteSetting::query()->findOrFail(1)->update([
            'address' => 'Building Haidangtravel 367 Đường Tân Sơn',
            'structured_data' => [
                'address' => [
                    'street_address' => 'Building Haidangtravel 367 Đường Tân Sơn',
                    'address_locality' => 'Phường Tân Sơn',
                    'address_region' => 'Hồ Chí Minh',
                    'postal_code' => '70000',
                    'address_country' => [
                        '@type' => 'Country',
                        'name' => 'Việt Nam',
                    ],
                ],
            ],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"@type":"PostalAddress"', false)
            ->assertSee('"addressCountry":"VN"', false)
            ->assertDontSee('"addressCountry":{"@type":"Country"', false)
            ->assertDontSee('"addressCountry":"Việt Nam"', false);
    }

    public function test_service_listing_and_detail_emit_enriched_schema_nodes(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $service = Service::query()
            ->published()
            ->with('category')
            ->firstOrFail();

        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee('"@id":"'.route('services.index').'#webpage"', false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"mainEntityOfPage":"'.route('services.index').'"', false)
            ->assertSee('"@id":"'.route('services.index').'#service-list"', false);

        $this->get(route('services.show', $service))
            ->assertOk()
            ->assertSee('"@id":"'.route('services.show', $service).'#service"', false)
            ->assertSee('"@type":"Service"', false)
            ->assertSee('"mainEntityOfPage":"'.route('services.show', $service).'"', false);
    }

    public function test_blog_listing_and_detail_emit_enriched_schema_nodes(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $post = BlogPost::query()
            ->published()
            ->with('category')
            ->firstOrFail();
        $postUrl = FrontsiteUrls::canonicalBlogPost($post);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('"@id":"'.route('blog.index').'#webpage"', false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"mainEntityOfPage":"'.route('blog.index').'"', false)
            ->assertSee('"@id":"'.route('blog.index').'#blog-list"', false);

        $this->get(FrontsiteUrls::blogPost($post))
            ->assertOk()
            ->assertSee('"@id":"'.route('blog.index').'#blog"', false)
            ->assertSee('"@type":"Blog"', false)
            ->assertSee('"@id":"'.$postUrl.'#article"', false)
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('"author":{"@id":"'.url('/tac-gia/'.\Illuminate\Support\Str::slug($post->author_name ?: 'Nguyễn Ngọc')).'"}', false)
            ->assertSee('"affiliation":{"@id":"'.route('home').'#organization"}', false)
            ->assertSee('"knowsAbout":["Kinh nghiệm du lịch","Checklist chuẩn bị chuyến đi","Du lịch Phú Quốc"]', false)
            ->assertSee('"url":"'.url('/tac-gia/'.\Illuminate\Support\Str::slug($post->author_name ?: 'Nguyễn Ngọc')).'"', false)
            ->assertSee('"image":{"@type":"ImageObject"', false)
            ->assertSee('"isPartOf":{"@id":"'.route('blog.index').'#blog"}', false)
            ->assertSee('"mainEntityOfPage":{"@type":"WebPage","@id":"'.$postUrl, false)
            ->assertSee('"publisher":{"@id":"'.route('home').'#organization"}', false);
    }
}
