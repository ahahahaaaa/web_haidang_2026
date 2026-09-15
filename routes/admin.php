<?php

use App\Http\Controllers\Admin\VoucherCampaignCodesExportController;
use App\Livewire\Admin\Cms\BlogsManager;
use App\Livewire\Admin\Cms\AccountsManager;
use App\Livewire\Admin\Cms\LandingPagesManager;
use App\Livewire\Admin\Cms\MediaManager;
use App\Livewire\Admin\Cms\MenuManager;
use App\Livewire\Admin\Cms\ServicesManager;
use App\Livewire\Admin\Cms\SlidersManager;
use App\Livewire\Admin\Cms\ThemeSettingsManager;
use App\Livewire\Admin\Cms\ToursManager;
use App\Livewire\Admin\Cms\TourAgencySyncQueueManager;
use App\Livewire\Admin\Cms\TravelReviewsManager;
use App\Livewire\Admin\Cms\VoucherCampaignCodesManager;
use App\Livewire\Admin\Cms\TravelInquiriesManager;
use App\Livewire\Admin\Cms\VoucherCampaignsManager;
use App\Http\Controllers\Admin\Media\MediaBrowserController;
use App\Http\Controllers\Admin\Media\MediaBrowserUploadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:access admin panel', 'noindex.headers'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('blogs')->group(function () {
        Route::get('/', BlogsManager::class)->middleware('permission:admin.blogs.index')->name('blogs');
        Route::get('/create', BlogsManager::class)->middleware('permission:admin.blogs.edit')->name('blogs.create');
        Route::get('/{post}/edit', BlogsManager::class)->middleware('permission:admin.blogs.edit')->name('blogs.edit');
        Route::get('/categories', BlogsManager::class)->middleware('permission:admin.blogs.categories.index')->name('blogs.categories');
        Route::get('/categories/create', BlogsManager::class)->middleware('permission:admin.blogs.categories.edit')->name('blogs.categories.create');
        Route::get('/categories/{category}/edit', BlogsManager::class)->middleware('permission:admin.blogs.categories.edit')->name('blogs.categories.edit');
    });

    Route::prefix('tours')->group(function () {
        Route::get('/', ToursManager::class)->middleware('permission:admin.tours.index')->name('tours');
        Route::get('/create', ToursManager::class)->middleware('permission:admin.tours.edit')->name('tours.create');
        Route::get('/agency-sync-queue', TourAgencySyncQueueManager::class)->middleware('permission:admin.tours.edit')->name('tours.agency-sync-queue');
        Route::get('/{tour}/edit', ToursManager::class)->middleware('permission:admin.tours.edit')->name('tours.edit');
        Route::get('/{tour}/reviews', TravelReviewsManager::class)->middleware('permission:admin.tours.edit')->name('tours.reviews.index');
        Route::get('/{tour}/reviews/create', TravelReviewsManager::class)->middleware('permission:admin.tours.edit')->name('tours.reviews.create');
        Route::get('/{tour}/reviews/{review}/edit', TravelReviewsManager::class)->middleware('permission:admin.tours.edit')->name('tours.reviews.edit');
        Route::get('/categories', ToursManager::class)->middleware('permission:admin.tours.categories.index')->name('tours.categories');
        Route::get('/categories/create', ToursManager::class)->middleware('permission:admin.tours.categories.edit')->name('tours.categories.create');
        Route::get('/categories/{category}/edit', ToursManager::class)->middleware('permission:admin.tours.categories.edit')->name('tours.categories.edit');
        Route::get('/categories/{category}/reviews', TravelReviewsManager::class)->middleware('permission:admin.tours.categories.edit')->name('tours.categories.reviews.index');
        Route::get('/categories/{category}/reviews/create', TravelReviewsManager::class)->middleware('permission:admin.tours.categories.edit')->name('tours.categories.reviews.create');
        Route::get('/categories/{category}/reviews/{review}/edit', TravelReviewsManager::class)->middleware('permission:admin.tours.categories.edit')->name('tours.categories.reviews.edit');
        Route::get('/destinations', ToursManager::class)->middleware('permission:admin.tours.destinations.index')->name('tours.destinations');
        Route::get('/destinations/create', ToursManager::class)->middleware('permission:admin.tours.destinations.edit')->name('tours.destinations.create');
        Route::get('/destinations/{destination}/edit', ToursManager::class)->middleware('permission:admin.tours.destinations.edit')->name('tours.destinations.edit');
        Route::get('/destinations/{destination}/reviews', TravelReviewsManager::class)->middleware('permission:admin.tours.destinations.edit')->name('tours.destinations.reviews.index');
        Route::get('/destinations/{destination}/reviews/create', TravelReviewsManager::class)->middleware('permission:admin.tours.destinations.edit')->name('tours.destinations.reviews.create');
        Route::get('/destinations/{destination}/reviews/{review}/edit', TravelReviewsManager::class)->middleware('permission:admin.tours.destinations.edit')->name('tours.destinations.reviews.edit');
        Route::get('/regions', ToursManager::class)->middleware('permission:admin.tours.regions.index')->name('tours.regions');
        Route::get('/regions/create', ToursManager::class)->middleware('permission:admin.tours.regions.edit')->name('tours.regions.create');
        Route::get('/regions/{region}/edit', ToursManager::class)->middleware('permission:admin.tours.regions.edit')->name('tours.regions.edit');
    });

    Route::prefix('services')->group(function () {
        Route::get('/', ServicesManager::class)->middleware('permission:admin.services.index')->name('services');
        Route::get('/create', ServicesManager::class)->middleware('permission:admin.services.edit')->name('services.create');
        Route::get('/{service}/edit', ServicesManager::class)->middleware('permission:admin.services.edit')->name('services.edit');
        Route::get('/categories', ServicesManager::class)->middleware('permission:admin.services.categories.index')->name('services.categories');
        Route::get('/categories/create', ServicesManager::class)->middleware('permission:admin.services.categories.edit')->name('services.categories.create');
        Route::get('/categories/{category}/edit', ServicesManager::class)->middleware('permission:admin.services.categories.edit')->name('services.categories.edit');
    });

    Route::get('/travel-inquiries', TravelInquiriesManager::class)
        ->middleware('permission:admin.travel-inquiries.index')
        ->name('travel-inquiries');

    Route::prefix('landing-pages')->group(function () {
        Route::get('/', LandingPagesManager::class)->middleware('permission:admin.landing-pages.index')->name('landing-pages');
        Route::get('/create', LandingPagesManager::class)->middleware('permission:admin.landing-pages.edit')->name('landing-pages.create');
        Route::get('/{landingPage}/edit', LandingPagesManager::class)->middleware('permission:admin.landing-pages.edit')->name('landing-pages.edit');
    });

    Route::get('/voucher-codes', VoucherCampaignCodesManager::class)
        ->middleware('permission:admin.voucher-campaigns.index')
        ->name('voucher-codes');
    Route::get('/voucher-codes/export', VoucherCampaignCodesExportController::class)
        ->middleware('permission:admin.voucher-campaigns.index')
        ->name('voucher-codes.export');

    Route::prefix('voucher-campaigns')->group(function () {
        Route::get('/', VoucherCampaignsManager::class)->middleware('permission:admin.voucher-campaigns.index')->name('voucher-campaigns');
        Route::get('/create', VoucherCampaignsManager::class)->middleware('permission:admin.voucher-campaigns.edit')->name('voucher-campaigns.create');
        Route::get('/{campaign}/codes', fn () => redirect()->route('admin.voucher-codes', request()->query()))->middleware('permission:admin.voucher-campaigns.index')->name('voucher-campaigns.codes');
        Route::get('/{campaign}/codes/export', fn () => redirect()->route('admin.voucher-codes.export', request()->query()))->middleware('permission:admin.voucher-campaigns.index')->name('voucher-campaigns.codes.export');
        Route::get('/{campaign}/edit', VoucherCampaignsManager::class)->middleware('permission:admin.voucher-campaigns.edit')->name('voucher-campaigns.edit');
    });

    Route::prefix('sliders')->group(function () {
        Route::get('/', SlidersManager::class)->middleware('permission:admin.sliders.index')->name('sliders');
        Route::get('/create', SlidersManager::class)->middleware('permission:admin.sliders.edit')->name('sliders.create');
        Route::get('/{slider}/edit', SlidersManager::class)->middleware('permission:admin.sliders.edit')->name('sliders.edit');
    });

    Route::get('/media', MediaManager::class)->middleware('permission:admin.media.index')->name('media');
    Route::get('/media/browser/images', MediaBrowserController::class)->middleware('permission:admin.media.index')->name('media.browser.images');
    Route::post('/media/browser/images', MediaBrowserUploadController::class)->middleware('permission:admin.media.index')->name('media.browser.images.upload');

    Route::get('/menus', MenuManager::class)->middleware('permission:admin.menus.index')->name('menus');
    Route::get('/theme-settings', ThemeSettingsManager::class)->middleware('permission:admin.theme-settings.index')->name('theme-settings');

    Route::middleware('role:admin|super_admin')->prefix('accounts')->group(function () {
        Route::get('/', AccountsManager::class)->middleware('permission:admin.accounts.index')->name('accounts');
        Route::get('/create', AccountsManager::class)->middleware('permission:admin.accounts.edit')->name('accounts.create');
        Route::get('/{user}/edit', AccountsManager::class)->middleware('permission:admin.accounts.edit')->name('accounts.edit');
    });
});
