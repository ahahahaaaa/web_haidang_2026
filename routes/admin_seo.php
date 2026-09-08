<?php

use App\Http\Controllers\Admin\Seo\SeoPageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:viewSeoAdmin', 'noindex.headers'])->prefix('admin/seo')->name('admin.seo.')->group(function () {
    Route::get('/pages', [SeoPageController::class, 'index'])->name('pages.index');
    Route::get('/pages/{page}', [SeoPageController::class, 'edit'])->name('pages.edit');
    Route::get('/pages/{page}/preview', [SeoPageController::class, 'preview'])->name('pages.preview');
});
