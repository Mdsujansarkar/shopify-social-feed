<?php

use Illuminate\Support\Facades\Route;
use App\Services\ShopifyService;

// Test route for Shopify connection
Route::get('/test/shopify', function () {
    $shopifyService = app(ShopifyService::class);

    $results = [
        'config' => [
            'api_key' => config('services.shopify.api_key'),
            'api_secret_set' => !empty(config('services.shopify.api_secret')),
            'redirect_uri' => config('services.shopify.redirect_uri'),
            'scopes' => config('services.shopify.scopes'),
            'api_version' => config('services.shopify.api_version'),
        ],
        'database' => [
            'shops_count' => \App\Models\Shop::count(),
            'shops' => \App\Models\Shop::all(['id', 'shop_domain', 'is_active', 'created_at']),
        ],
        'test_url' => url('/shopify/install?shop=your-store.myshopify.com'),
    ];

    return $results;
})->name('test.shopify');

// Test route to verify HMAC
Route::get('/test/hmac', function () {
    $shopifyService = app(ShopifyService::class);

    // Test HMAC verification with sample data
    $testParams = [
        'shop' => 'test-store.myshopify.com',
        'code' => 'test_code',
        'timestamp' => time(),
    ];

    // Generate HMAC
    $secret = config('services.shopify.api_secret');
    ksort($testParams);
    $queryString = http_build_query($testParams);
    $hmac = hash_hmac('sha256', $queryString, $secret);

    // Add HMAC to params
    $testParams['hmac'] = $hmac;

    // Verify
    $isValid = $shopifyService->verifyHmac($testParams);

    return [
        'test_params' => $testParams,
        'hmac_valid' => $isValid,
        'secret_set' => !empty($secret),
    ];
})->name('test.hmac');
