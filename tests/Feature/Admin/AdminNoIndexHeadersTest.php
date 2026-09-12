<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNoIndexHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_html_routes_send_noindex_headers_and_meta(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.theme-settings'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive" />', false);

        $this->actingAs($user)
            ->get(route('admin.seo-optimization.index'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive" />', false);
    }

    public function test_admin_api_routes_send_noindex_header(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user)
            ->getJson(route('api.v1.admin.blogs.index'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
}
