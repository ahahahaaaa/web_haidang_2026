<?php

namespace Tests\Feature;

use App\Http\Middleware\CanonicalizeFrontsiteUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FrontsiteCanonicalSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('frontsite_seo.canonical_url', 'https://haidangtravel.com');
        Config::set('frontsite_seo.canonical_redirect_enabled', true);
        Config::set('frontsite_seo.canonical_redirect_hosts', [
            'haidangtravel.com',
            'www.haidangtravel.com',
            'haidangtravel.dtaa-tech.com',
        ]);
    }

    public function test_known_host_and_index_php_variant_redirects_to_clean_canonical_url(): void
    {
        $response = $this->get('https://www.haidangtravel.com/index.php/ve-chung-toi?utm_source=audit');

        $response->assertStatus(301);
        $response->assertHeader('Location', 'https://haidangtravel.com/ve-chung-toi?utm_source=audit');
    }

    public function test_index_php_base_url_redirects_on_canonical_host(): void
    {
        $request = Request::create(
            'https://haidangtravel.com/index.php/tour-du-lich-da-nang?utm_source=audit',
            'GET',
            [],
            [],
            [],
            [
                'HTTPS' => 'on',
                'PHP_SELF' => '/index.php/tour-du-lich-da-nang',
                'SCRIPT_NAME' => '/index.php',
                'SERVER_PORT' => 443,
            ],
        );

        $response = (new CanonicalizeFrontsiteUrl())->handle($request, fn () => response('ok'));

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame(
            'https://haidangtravel.com/tour-du-lich-da-nang?utm_source=audit',
            $response->headers->get('Location'),
        );
    }

    public function test_robots_advertises_only_the_canonical_sitemap_url(): void
    {
        $response = $this->get('https://haidangtravel.com/robots.txt');

        $response
            ->assertOk()
            ->assertSee('Sitemap: https://haidangtravel.com/sitemap.xml', false)
            ->assertDontSee('www.haidangtravel.com', false)
            ->assertDontSee('haidangtravel.dtaa-tech.com', false)
            ->assertDontSee('/index.php', false);
    }
}