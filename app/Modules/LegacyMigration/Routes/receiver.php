<?php

use App\Modules\LegacyMigration\Http\Controllers\LegacyMigrationReceiverController;
use App\Modules\LegacyMigration\Http\Middleware\AuthenticateLegacyMigration;
use App\Modules\LegacyMigration\Http\Middleware\EnforceLegacyPayloadSize;
use App\Modules\LegacyMigration\Http\Middleware\EnsureLegacyMigrationEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'api',
    'throttle:60,1',
    EnsureLegacyMigrationEnabled::class,
    AuthenticateLegacyMigration::class,
    EnforceLegacyPayloadSize::class,
])->prefix('api/v1/legacy-migrations')->name('legacy-migration.receiver.')->group(function (): void {
    Route::post('/runs', [LegacyMigrationReceiverController::class, 'storeRun'])->name('runs.store');
    Route::put('/runs/{sourceRunUuid}/chunks/{sequence}', [LegacyMigrationReceiverController::class, 'storeChunk'])
        ->whereNumber('sequence')
        ->name('chunks.store');
    Route::post('/runs/{sourceRunUuid}/finalize', [LegacyMigrationReceiverController::class, 'finalize'])->name('runs.finalize');
    Route::get('/runs/{sourceRunUuid}', [LegacyMigrationReceiverController::class, 'show'])->name('runs.show');
});
