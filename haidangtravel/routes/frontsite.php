<?php

use App\Http\Controllers\FrontsiteController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TravelInquiryController;
use App\Support\LandingPageBlocks;
use Illuminate\Support\Facades\Route;

Route::middleware('frontsite.cache')->group(function (): void {
    Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

    Route::get('/', [FrontsiteController::class, 'home'])->name('home');
    Route::get('/ve-chung-toi', [FrontsiteController::class, 'about'])->name('about');
    Route::get('/tour-trong-nuoc', [FrontsiteController::class, 'toursIndex'])->defaults('scope', 'domestic')->name('tours.domestic');
    Route::get('/tour-nuoc-ngoai', [FrontsiteController::class, 'toursIndex'])->defaults('scope', 'international')->name('tours.international');
    Route::get('/tour-doan', [FrontsiteController::class, 'toursIndex'])->defaults('scope', 'group')->name('tours.group');
    Route::get('/tim-tour', [FrontsiteController::class, 'tourSearch'])->name('tours.search');
    Route::get('/chuong-trinh/{tour}', [FrontsiteController::class, 'toursShow'])->name('tours.show');
    Route::get('/tour/{tour}', [FrontsiteController::class, 'legacyTourShow']);
    Route::get('/dich-vu', [FrontsiteController::class, 'services'])->name('services.index');
    Route::get('/dich-vu/danh-muc/{category:slug}', [FrontsiteController::class, 'servicesCategoryShow'])->name('service-categories.show');
    Route::get('/dich-vu/{service}', [FrontsiteController::class, 'servicesShow'])->name('services.show');
    Route::get('/blog', [FrontsiteController::class, 'blog'])->name('blog.index');
    Route::get('/danh-muc/{slug}', [FrontsiteController::class, 'blogCategoryShow'])->name('blog-categories.show');
    Route::get('/blog/{post}', [FrontsiteController::class, 'blogShow'])->name('blog.show');
    Route::get('/lien-he', [FrontsiteController::class, 'contact'])->name('contact');
    Route::get('/danh-muc-tour/{category}', [FrontsiteController::class, 'tourCategoryShow'])->name('tour-categories.show');
    Route::get('/tour-{destination}', [FrontsiteController::class, 'destinationShow'])->name('destinations.show');
    Route::get('/diem-den/{destination}', [FrontsiteController::class, 'legacyDestinationShow']);
    Route::get('/vung-mien/{region}', [FrontsiteController::class, 'regionShow'])->name('regions.show');
    Route::get('/quoc-gia/{slug}', [FrontsiteController::class, 'countryShow'])->name('countries.show');
    Route::post('/yeu-cau-tu-van', [TravelInquiryController::class, 'store'])->name('travel-inquiries.store');
});

$reservedLandingSlugs = collect(LandingPageBlocks::reservedSlugs())
    ->filter(fn (string $slug) => ! str_contains($slug, '.'))
    ->map(fn (string $slug) => preg_quote($slug, '/'))
    ->implode('|');

Route::middleware('frontsite.cache')->group(function () use ($reservedLandingSlugs): void {
    Route::get('/{slug}', [FrontsiteController::class, 'landingShow'])
        ->where('slug', '^(?!(?:'.$reservedLandingSlugs.')$)[A-Za-z0-9-]+$')
        ->name('landing.show');
});
