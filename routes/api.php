<?php

use App\Http\Controllers\Agency\TourExportController;
use Illuminate\Support\Facades\Route;

Route::middleware('agency.export.token')
    ->prefix('v1/agency')
    ->name('api.v1.agency.')
    ->group(function (): void {
        Route::get('/tours', TourExportController::class)->name('tours.index');
    });
