<?php

namespace Tests\Feature\SeoOptimization;

use App\Livewire\Admin\SeoOptimization\ProposalReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class OptimizationAdminTest extends OptimizationTestCase
{
    public function test_active_super_admin_without_email_verification_can_access_seo_admin(): void
    {
        $admin = User::factory()->unverified()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('super_admin', 'web'));

        $this->actingAs($admin);
        foreach (['/admin/seo-optimization', '/admin/seo-optimization/pages/'.$this->page->id, '/admin/seo-optimization/tasks', '/admin/seo-optimization/settings'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->assertNull($admin->fresh()->email_verified_at);
    }

    public function test_inactive_super_admin_is_still_denied(): void
    {
        $admin = User::factory()->create(['is_active' => false]);
        $admin->assignRole(Role::findOrCreate('super_admin', 'web'));

        $this->actingAs($admin)->get('/admin/seo-optimization')->assertForbidden();
    }

    public function test_authorized_cms_editor_follows_optional_email_verification(): void
    {
        $this->reviewer->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($this->reviewer)->get('/admin/seo-optimization')->assertOk();
        $this->reviewer->revokePermissionTo('admin.seo-optimization.index');
        $this->get('/admin/seo-optimization')->assertForbidden();
    }

    public function test_admin_pages_render_and_guests_and_unprivileged_users_are_denied(): void
    {
        $this->get('/admin/seo-optimization')->assertRedirect();
        $this->actingAs(User::factory()->create(['is_active' => true]))->get('/admin/seo-optimization')->assertForbidden();
        $this->actingAs($this->reviewer);
        $this->get('/admin/seo-optimization')->assertOk()->assertSee('SEO AI Optimize');
        $this->get('/admin/seo-optimization/pages/'.$this->page->id)->assertOk()->assertSee('Tư vấn hành trình Đà Nẵng');
        $this->get('/admin/seo-optimization/tasks')->assertOk();
        $this->get('/admin/seo-optimization/settings')->assertOk()->assertSee('Codex Schedule MCP');
        $this->get('/admin/seo-optimization/proposals/'.$this->proposal()->id)->assertOk();
    }

    public function test_livewire_approve_and_apply_require_separate_actions(): void
    {
        $proposal = $this->proposal();
        Livewire::actingAs($this->reviewer)->test(ProposalReview::class, ['proposal' => $proposal])
            ->call('approve')->assertHasNoErrors();
        $this->assertSame('approved', $proposal->fresh()->status);
        $this->assertSame('Tư vấn hành trình theo nhu cầu.', $this->service->fresh()->meta_description);
        Livewire::test(ProposalReview::class, ['proposal' => $proposal->fresh()])->call('apply')->assertHasNoErrors();
        $this->assertSame('applied', $proposal->fresh()->status);
    }

    public function test_original_content_permission_is_required_even_with_seo_permissions(): void
    {
        $this->reviewer->revokePermissionTo('admin.services.edit');
        Livewire::actingAs($this->reviewer)->test(ProposalReview::class, ['proposal' => $this->proposal()])
            ->call('approve')->assertForbidden();
    }

    public function test_permission_setup_is_additive_and_works_with_strict_lazy_loading(): void
    {
        $admin = Role::findOrCreate('admin', 'web');
        Role::findOrCreate('super_admin', 'web');
        $custom = Permission::findOrCreate('custom.permission.to.preserve', 'web');
        $admin->givePermissionTo($custom);
        Model::preventLazyLoading(true);
        try {
            $this->artisan('seo-optimize:permissions')->assertSuccessful();
            $this->assertTrue($admin->fresh()->hasPermissionTo($custom));
            $this->assertTrue($admin->fresh()->hasPermissionTo('admin.seo-optimization.approve'));
        } finally {
            Model::preventLazyLoading(false);
        }
    }
}
