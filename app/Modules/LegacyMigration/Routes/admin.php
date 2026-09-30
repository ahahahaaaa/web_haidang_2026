<?php

use App\Modules\LegacyMigration\Http\Controllers\ExportLegacyRedirectsController;
use App\Modules\LegacyMigration\Livewire\Admin\MigrationManager;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified', 'permission:access admin panel', 'role:super_admin', 'noindex.headers'])
    ->prefix('admin/legacy-migrations')
    ->name('admin.legacy-migration.')
    ->group(function (): void {
        Route::get('/', MigrationManager::class)->name('index');
        Route::get('/redirects/export', ExportLegacyRedirectsController::class)->name('redirects.export');
        Route::get('/{run}', MigrationManager::class)->name('runs.show');
    });
