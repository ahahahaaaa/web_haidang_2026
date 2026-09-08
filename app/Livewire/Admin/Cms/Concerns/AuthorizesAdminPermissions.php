<?php

namespace App\Livewire\Admin\Cms\Concerns;

use Symfony\Component\HttpKernel\Exception\HttpException;

trait AuthorizesAdminPermissions
{
    protected function authorizeAdminPermission(string $permission): void
    {
        if (! $this->canAdmin($permission)) {
            throw new HttpException(403, 'Bạn không có quyền thực hiện thao tác này.');
        }
    }

    protected function canAdmin(string $permission): bool
    {
        return (bool) auth()->user()?->can($permission);
    }
}
