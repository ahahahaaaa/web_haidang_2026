<?php

namespace Src\Domains\Seo\Policies;

use App\Models\User;
use Src\Domains\Seo\Models\SeoPage;

class SeoPagePolicy
{
    public function viewSeoAdmin(User $user): bool
    {
        return method_exists($user, 'hasRole')
            ? $user->hasRole('admin') || $user->hasRole('seo_manager')
            : true;
    }

    public function update(User $user, SeoPage $page): bool { return $this->viewSeoAdmin($user); }
    public function publish(User $user, SeoPage $page): bool { return $this->viewSeoAdmin($user); }
}
