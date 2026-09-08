<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Admin\CmsRolePermissionSynchronizer;
use App\Services\Travel\HaidangTravelImportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HaidangTravelBootstrapSeeder extends Seeder
{
    public function run(CmsRolePermissionSynchronizer $cmsRoles): void
    {
        $cmsRoles->sync();

        $testUser = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'is_active' => true,
            ],
        );
        $testUser->forceFill(['is_active' => true])->save();
        $testUser->syncRoles(['super_admin']);

        $adminUser = User::query()->updateOrCreate(
            ['email' => 'quantri@haidangtravel.com'],
            [
                'name' => 'quantri',
                'password' => Hash::make('Abc123!@#'),
                'is_active' => true,
            ],
        );
        $adminUser->syncRoles(['super_admin']);

        $contentUser = User::query()->updateOrCreate(
            ['email' => 'content@haidangtravel.com'],
            [
                'name' => 'content-editor',
                'password' => Hash::make('Abc123!@#'),
                'is_active' => true,
            ],
        );
        $contentUser->syncRoles(['content']);

        app(HaidangTravelImportService::class)->import();

        $this->call(VoucherLandingPageSeeder::class);
    }
}
