<?php

use App\Http\Middleware\AuthenticateSeoOptimizationMcp;
use App\Mcp\Servers\SeoOptimizationServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Route::middleware(['api', 'throttle:60,1', AuthenticateSeoOptimizationMcp::class])->group(function (): void {
    Route::post('/mcp/seo-optimization/commit', \App\Http\Controllers\SeoOptimization\CommitContentOptimizationController::class)->name('mcp.seo-optimization.commit');
    Route::post('/mcp/seo-optimization/media/{task}', \App\Http\Controllers\SeoOptimization\UploadSeoImageController::class)->name('mcp.seo-optimization.media');
    Mcp::web('/mcp/seo-optimization', SeoOptimizationServer::class)
        ->name('mcp.seo-optimization');
});
