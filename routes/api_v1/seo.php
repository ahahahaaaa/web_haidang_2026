<?php

use App\Http\Controllers\Admin\Seo\SeoClusterController;
use App\Http\Controllers\Admin\Seo\SeoGenerationController;
use App\Http\Controllers\Admin\Seo\SeoPublishController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1/seo')->name('api.v1.seo.')->group(function () {
    Route::post('/clusters', [SeoClusterController::class, 'store'])->name('clusters.store');
    Route::post('/pages/{page}/regenerate', [SeoGenerationController::class, 'regenerate'])->name('pages.regenerate');
    Route::post('/pages/{page}/qa', [SeoGenerationController::class, 'qa'])->name('pages.qa');
    Route::post('/pages/{page}/publish', SeoPublishController::class)->name('pages.publish');
});
