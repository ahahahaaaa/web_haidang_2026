<?php

namespace App\Services\Admin;

use App\Support\Admin\AdminNavigationRegistry;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CmsRolePermissionSynchronizer
{
    public function sync(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $fullAdminPermissions = $this->ensurePermissions([
            ...AdminNavigationRegistry::fullAdminPermissions(),
            ...AdminNavigationRegistry::legacyPermissionKeys(),
        ]);

        $contentPermissions = $this->ensurePermissions(
            AdminNavigationRegistry::defaultContentPermissions()
        );
        $salePermissions = $this->ensurePermissions(
            AdminNavigationRegistry::defaultSalePermissions()
        );

        Role::findOrCreate('super_admin', 'web')->syncPermissions($fullAdminPermissions);
        Role::findOrCreate('admin', 'web')->syncPermissions($fullAdminPermissions);
        Role::findOrCreate('content', 'web')->syncPermissions($contentPermissions);
        Role::findOrCreate('sale', 'web')->syncPermissions($salePermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<int, string>  $permissions
     * @return Collection<int, Permission>
     */
    protected function ensurePermissions(array $permissions): Collection
    {
        return collect($permissions)
            ->unique()
            ->map(fn (string $permission) => Permission::findOrCreate($permission, 'web'))
            ->values();
    }
}
