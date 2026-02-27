<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopifyAuthController;
use App\Http\Controllers\InstagramAuthController;
use App\Http\Controllers\DashboardController;

// Welcome / Landing page
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Shopify Authentication Routes
Route::prefix('shopify')->name('shopify.')->group(function () {
    Route::get('/install', [ShopifyAuthController::class, 'install'])->name('install');
    Route::get('/callback', [ShopifyAuthController::class, 'callback'])->name('callback');
    Route::post('/uninstall', [ShopifyAuthController::class, 'uninstall'])->name('uninstall');
});

// Instagram Authentication Routes
Route::prefix('instagram')->name('instagram.')->group(function () {
    Route::get('/connect', [InstagramAuthController::class, 'connect'])->name('connect');
    Route::get('/callback', [InstagramAuthController::class, 'callback'])->name('callback');
    Route::get('/disconnect', [InstagramAuthController::class, 'disconnect'])->name('disconnect');
});

// Dashboard Routes (protected by shop authentication)
Route::middleware(['shop.auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/instagram-account', [DashboardController::class, 'instagramAccount'])->name('instagram-account');
    Route::get('/media', [DashboardController::class, 'instagramMedia'])->name('instagram-media');
    Route::post('/sync-media', [DashboardController::class, 'syncMedia'])->name('sync-media');
    Route::post('/refresh-token', [DashboardController::class, 'refreshToken'])->name('refresh-token');
});
