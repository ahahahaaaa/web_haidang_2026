<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\Admin\AdminNavigationRegistry;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminSidebarPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_registry_only_references_defined_routes(): void
    {
        $missingRoutes = collect(AdminNavigationRegistry::groups())
            ->flatMap(fn (array $group) => $group['actions'])
            ->filter(fn (array $action) => $action['navigation'] ?? true)
            ->pluck('route')
            ->unique()
            ->reject(fn (string $routeName) => Route::has($routeName))
            ->values()
            ->all();

        $this->assertSame([], $missingRoutes);
    }

    public function test_content_user_sees_only_blog_group_and_is_blocked_from_other_routes(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $content = User::query()->where('email', 'content@example.com')->firstOrFail();

        $this->actingAs($content);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee("toggle('blogs')", false)
            ->assertDontSee("toggle('tours')", false)
            ->assertDontSee("toggle('sliders')", false)
            ->assertDontSee("toggle('accounts')", false);

        $this->get(route('admin.blogs'))->assertOk();
        $this->get(route('admin.sliders'))->assertForbidden();
        $this->get(route('admin.accounts'))->assertForbidden();
    }

    public function test_extra_permission_for_content_user_unlocks_sidebar_group_and_route(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $content = User::query()->where('email', 'content@example.com')->firstOrFail();
        $content->givePermissionTo('admin.tours.index');

        $this->actingAs($content);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee("toggle('tours')", false);

        $this->get(route('admin.tours'))->assertOk();
    }

    public function test_legacy_account_permissions_do_not_unlock_accounts_for_content_user(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $content = User::query()->where('email', 'content@example.com')->firstOrFail();
        $content->givePermissionTo(['admin.accounts.index', 'admin.accounts.edit']);

        $this->actingAs($content);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee("toggle('accounts')", false);

        $this->get(route('admin.accounts'))->assertForbidden();
    }

    public function test_admin_user_sees_slider_and_accounts_groups(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($admin);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee("toggle('sliders')", false)
            ->assertSee("toggle('accounts')", false);
    }

    public function test_current_route_group_is_rendered_open_in_sidebar_markup(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($admin);

        $response = $this->get(route('admin.tours'));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertMatchesRegularExpression('/data-sidebar-group-panel="tours"(?![^>]*style=)/', $content);
        $this->assertMatchesRegularExpression('/data-sidebar-group-panel="blogs"[^>]*style="display: none;"/', $content);
        $response->assertDontSee('@js($defaultOpenGroups)', false);
    }

    public function test_accounts_route_marks_accounts_group_open_in_sidebar_markup(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($admin);

        $response = $this->get(route('admin.accounts'));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertMatchesRegularExpression('/data-sidebar-group-panel="accounts"(?![^>]*style=)/', $content);
        $response->assertDontSee('@js($defaultOpenGroups)', false);
    }
}
