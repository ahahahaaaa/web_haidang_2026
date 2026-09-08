<?php

use App\Http\Controllers\Admin\Seo\SeoAiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('ai')->name('ai.')->group(function () {
    Route::get('/seo/pages/{page}/prompt-preview', [SeoAiController::class, 'previewPrompt'])->name('seo.pages.prompt-preview');
});
