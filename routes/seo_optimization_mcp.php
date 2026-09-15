<?php

use App\Http\Controllers\SeoOptimization\CommitContentOptimizationController;
use App\Http\Controllers\SeoOptimization\UploadContentCreationImageController;
use App\Http\Controllers\SeoOptimization\UploadSeoImageController;
use App\Http\Middleware\AuthenticateSeoOptimizationMcp;
use App\Mcp\Servers\SeoOptimizationServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Route::middleware(['api', 'throttle:60,1', AuthenticateSeoOptimizationMcp::class])->group(function (): void {
    Route::post('/mcp/seo-optimization/commit', CommitContentOptimizationController::class)->name('mcp.seo-optimization.commit');
    Route::post('/mcp/seo-optimization/media/{task}', UploadSeoImageController::class)->name('mcp.seo-optimization.media');
    Route::post('/mcp/seo-optimization/content-creation/{task}/media', UploadContentCreationImageController::class)->name('mcp.seo-optimization.content-creation.media');
    Mcp::web('/mcp/seo-optimization', SeoOptimizationServer::class)
        ->name('mcp.seo-optimization');
});
