<?php

use App\Http\Controllers\Admin\Blogs\BlogCategoryApiController;
use App\Http\Controllers\Admin\Blogs\BlogPostApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:access admin panel', 'noindex.headers'])
    ->prefix('api/v1/admin')
    ->name('api.v1.admin.')
    ->group(function (): void {
        Route::get('/blogs/categories', BlogCategoryApiController::class)
            ->middleware('permission:admin.blogs.categories.index')
            ->name('blogs.categories.index');
        Route::get('/blogs', [BlogPostApiController::class, 'index'])
            ->middleware('permission:admin.blogs.index')
            ->name('blogs.index');
        Route::post('/blogs', [BlogPostApiController::class, 'store'])
            ->middleware('permission:admin.blogs.edit')
            ->name('blogs.store');
        Route::get('/blogs/{post}', [BlogPostApiController::class, 'show'])
            ->middleware('permission:admin.blogs.index')
            ->name('blogs.show');
        Route::match(['put', 'patch'], '/blogs/{post}', [BlogPostApiController::class, 'update'])
            ->middleware('permission:admin.blogs.edit')
            ->name('blogs.update');
        Route::delete('/blogs/{post}', [BlogPostApiController::class, 'destroy'])
            ->middleware('permission:admin.blogs.edit')
            ->name('blogs.destroy');
    });
