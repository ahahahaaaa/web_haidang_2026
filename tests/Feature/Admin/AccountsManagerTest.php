<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Cms\AccountsManager;
use App\Models\User;
use App\Support\Admin\AdminNavigationRegistry;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountsManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_content_account_with_extra_sidebar_permission(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(AccountsManager::class)
            ->set('form.name', 'Content Tours')
            ->set('form.email', 'content-tours@example.com')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->set('form.role_type', 'content')
            ->set('form.extra_permissions', ['admin.tours.index'])
            ->call('save')
            ->assertHasNoErrors();

        $user = User::query()->where('email', 'content-tours@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('content'));
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->can('admin.blogs.index'));
        $this->assertTrue($user->can('admin.tours.index'));
        $this->assertFalse($user->can('admin.accounts.index'));
    }

    public function test_switching_account_to_admin_clears_direct_content_extras(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $user = User::query()->where('email', 'content@example.com')->firstOrFail();
        $user->givePermissionTo('admin.tours.index');

        $this->actingAs($admin);

        Livewire::test(AccountsManager::class, ['user' => $user])
            ->call('editAccount', $user->id)
            ->set('form.role_type', 'admin')
            ->set('form.password', '')
            ->set('form.password_confirmation', '')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertTrue($user->hasRole('admin'));
        $this->assertCount(0, $user->permissions);
        $this->assertTrue($user->can('admin.accounts.index'));
    }

    public function test_missing_content_role_is_recreated_before_saving_content_account(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        Role::findByName('content', 'web')->delete();

        $this->actingAs($admin);

        Livewire::test(AccountsManager::class)
            ->set('form.name', 'Content Recovery')
            ->set('form.email', 'content-recovery@example.com')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->set('form.role_type', 'content')
            ->set('form.extra_permissions', ['admin.tours.index'])
            ->call('save')
            ->assertHasNoErrors();

        $user = User::query()->where('email', 'content-recovery@example.com')->firstOrFail();
        $contentRole = Role::findByName('content', 'web');

        $this->assertTrue($user->hasRole('content'));
        $this->assertEqualsCanonicalizing(
            AdminNavigationRegistry::defaultContentPermissions(),
            $contentRole->permissions->pluck('name')->all(),
        );
    }

    public function test_admin_can_create_sale_account_with_tour_only_permissions_and_phone(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(AccountsManager::class)
            ->set('form.name', 'Sale Tour')
            ->set('form.email', 'sale-tour@example.com')
            ->set('form.phone', '0911 222 288')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->set('form.role_type', 'sale')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::query()->where('email', 'sale-tour@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('sale'));
        $this->assertSame('0911 222 288', $user->phone);
        $this->assertTrue($user->can('admin.tours.index'));
        $this->assertTrue($user->can('admin.tours.edit'));
        $this->assertFalse($user->can('admin.tours.categories.edit'));
        $this->assertFalse($user->can('admin.accounts.index'));
    }

    public function test_admin_account_index_shows_status_filter_and_destructive_actions_for_other_users(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $contentUser = User::query()->where('email', 'content@example.com')->firstOrFail();

        $this->actingAs($admin);

        $this->get(route('admin.accounts'))
            ->assertOk()
            ->assertSeeText('Tất cả trạng thái')
            ->assertSee('wire:click="disableUser('.$contentUser->id.')"', false)
            ->assertSee('wire:click="deleteUser('.$contentUser->id.')"', false)
            ->assertDontSee('wire:click="deleteUser('.$admin->id.')"', false);
    }

    public function test_admin_can_disable_and_enable_user_account(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $user = User::factory()->create(['name' => 'Sale Disabled']);
        $user->assignRole('sale');

        DB::table('sessions')->insert([
            'id' => 'sale-disabled-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'a:0:{}',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin);

        Livewire::test(AccountsManager::class)
            ->call('disableUser', $user->id)
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertFalse($user->is_active);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);

        Livewire::test(AccountsManager::class)
            ->call('enableUser', $user->id)
            ->assertHasNoErrors();

        $this->assertTrue($user->refresh()->is_active);
    }

    public function test_admin_can_delete_other_user_account(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $user = User::factory()->create(['name' => 'Content Deleted']);
        $user->assignRole('content');

        $this->actingAs($admin);

        Livewire::test(AccountsManager::class)
            ->call('deleteUser', $user->id)
            ->assertHasNoErrors();

        $this->assertModelMissing($user);
    }

    public function test_admin_cannot_disable_or_delete_own_account(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(AccountsManager::class)
            ->call('disableUser', $admin->id)
            ->assertHasErrors(['account_action']);

        $this->assertTrue($admin->refresh()->is_active);

        Livewire::test(AccountsManager::class)
            ->call('deleteUser', $admin->id)
            ->assertHasErrors(['account_action']);

        $this->assertModelExists($admin);
    }

    public function test_admin_cannot_disable_own_account_from_editor_form(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(AccountsManager::class, ['user' => $admin])
            ->call('editAccount', $admin->id)
            ->set('form.is_active', false)
            ->set('form.password', '')
            ->set('form.password_confirmation', '')
            ->call('save')
            ->assertHasErrors(['form.is_active']);

        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_account_editor_updates_permission_mode_when_role_changes(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(AccountsManager::class)
            ->set('currentRouteName', 'admin.accounts.edit')
            ->call('editAccount', $admin->id)
            ->assertSee('wire:model.live="form.role_type"', false)
            ->assertSeeText('Admin có toàn quyền trên toàn bộ sidebar và action của CMS.')
            ->set('form.role_type', 'content')
            ->assertSeeText('Giữ nguyên quyền mặc định của Content và chỉ bật thêm các action cần thiết.')
            ->assertViewHas('accountGroups', fn (array $groups) => collect($groups)->doesntContain('key', 'accounts'));
    }

    public function test_content_user_cannot_receive_account_management_permission(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(AccountsManager::class)
            ->set('form.name', 'Content Accounts')
            ->set('form.email', 'content-accounts@example.com')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->set('form.role_type', 'content')
            ->set('form.extra_permissions', ['admin.accounts.edit'])
            ->call('save')
            ->assertHasErrors(['form.extra_permissions.0']);

        $this->assertDatabaseMissing('users', ['email' => 'content-accounts@example.com']);
    }

    public function test_content_user_with_legacy_direct_permissions_cannot_manage_accounts(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $content = User::query()->where('email', 'content@example.com')->firstOrFail();
        $content->givePermissionTo(['admin.accounts.index', 'admin.accounts.edit']);

        $this->actingAs($content);

        $this->get(route('admin.accounts'))->assertForbidden();
        $this->get(route('admin.accounts.edit', $admin))->assertForbidden();

        Livewire::test(AccountsManager::class)->assertForbidden();
    }

    public function test_open_account_manager_is_blocked_after_current_admin_loses_role(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($admin);

        $component = Livewire::test(AccountsManager::class);

        $admin->syncRoles(['content']);

        $component
            ->set('search', 'content')
            ->assertForbidden();
    }
}
