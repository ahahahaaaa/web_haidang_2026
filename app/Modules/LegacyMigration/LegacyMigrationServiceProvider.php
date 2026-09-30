<?php

namespace App\Modules\LegacyMigration;

use Illuminate\Support\ServiceProvider;

class LegacyMigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/legacy_migration.php', 'legacy_migration');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'legacy-migration');
        $this->loadRoutesFrom(__DIR__.'/Routes/receiver.php');
        $this->loadRoutesFrom(__DIR__.'/Routes/admin.php');
    }
}
