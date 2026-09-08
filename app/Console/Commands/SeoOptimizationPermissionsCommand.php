<?php

namespace App\Console\Commands;

use App\Support\Admin\AdminNavigationRegistry;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SeoOptimizationPermissionsCommand extends Command
{
    protected $signature = 'seo-optimize:permissions';

    protected $description = 'Bổ sung quyền SEO cho Admin, giữ nguyên quyền tùy chỉnh và role Content';

    public function handle(PermissionRegistrar $registrar): int
    {
        $permissions = array_filter(AdminNavigationRegistry::permissionKeys(), fn ($key) => str_starts_with($key, 'admin.seo-optimization.'));
        foreach ($permissions as $key) {
            $permission = Permission::findOrCreate($key, 'web');
            foreach (Role::query()->with('permissions')->where('guard_name', 'web')->whereIn('name', ['admin', 'super_admin'])->get() as $role) {
                $role->givePermissionTo($permission);
            }
        }
        $registrar->forgetCachedPermissions();
        $this->info('Đã bổ sung '.count($permissions).' quyền SEO; không đặt lại quyền hiện có.');

        return self::SUCCESS;
    }
}
