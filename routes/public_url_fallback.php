<?php

use App\Http\Controllers\PublicUrlFallbackController;
use Illuminate\Support\Facades\Route;

Route::fallback(PublicUrlFallbackController::class)->name('public-url-fallback');
