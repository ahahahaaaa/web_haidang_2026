<?php

use App\Livewire\Admin\SeoOptimization\IntegrationSettings;
use App\Livewire\Admin\SeoOptimization\PageDetail;
use App\Livewire\Admin\SeoOptimization\PagesIndex;
use App\Livewire\Admin\SeoOptimization\ProposalReview;
use App\Livewire\Admin\SeoOptimization\TasksIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:access admin panel', 'noindex.headers'])
    ->prefix('admin/seo-optimization')->name('admin.seo-optimization.')->group(function (): void {
        Route::get('/', PagesIndex::class)->middleware('permission:admin.seo-optimization.index')->name('index');
        Route::get('/pages/{page}', PageDetail::class)->middleware('permission:admin.seo-optimization.index')->name('pages.show');
        Route::get('/proposals/{proposal}', ProposalReview::class)->middleware('permission:admin.seo-optimization.index')->name('proposals.show');
        Route::get('/tasks', TasksIndex::class)->middleware('permission:admin.seo-optimization.index')->name('tasks.index');
        Route::get('/settings', IntegrationSettings::class)->middleware('permission:admin.seo-optimization.settings')->name('settings');
    });
