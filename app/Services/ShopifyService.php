<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\Shop;

class ShopifyService
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $apiVersion;
    protected string $redirectUri;

    public function __construct()
    {
        $this->apiKey = config('services.shopify.api_key');
        $this->apiSecret = config('services.shopify.api_secret');
        $this->apiVersion = config('services.shopify.api_version', '2024-01');
        $this->redirectUri = config('services.shopify.redirect_uri');
    }

    /**
     * Generate Shopify OAuth installation URL.
     */
    public function getInstallUrl(string $shopDomain, string $state = null): string
    {
        $state = $state ?? Str::random(40);
        $scopes = config('services.shopify.scopes', 'read_products,read_orders,read_content');

        $query = http_build_query([
            'client_id' => $this->apiKey,
            'scope' => $scopes,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'response_type' => 'code',
            'grant_options[]' => 'per-user',
        ]);

        return "https://{$shopDomain}/admin/oauth/authorize?{$query}";
    }

    /**
     * Verify HMAC signature from Shopify.
     * Critical for security - prevents request forgery.
     */
    public function verifyHmac(array $params): bool
    {
        if (!isset($params['hmac'])) {
            return false;
        }

        $hmac = $params['hmac'];
        unset($params['hmac']);

        // Sort parameters by key
        ksort($params);

        // Build query string
        $queryString = http_build_query($params);

        // Generate HMAC hash
        $calculatedHmac = hash_hmac('sha256', $queryString, $this->apiSecret);

        // Use hash_equals to prevent timing attacks
        return hash_equals($hmac, $calculatedHmac);
    }

    /**
     * Exchange authorization code for access token.
     */
    public function exchangeCodeForToken(string $shopDomain, string $code): ?string
    {
        $response = Http::asForm()->post("https://{$shopDomain}/admin/oauth/access_token", [
            'client_id' => $this->apiKey,
            'client_secret' => $this->apiSecret,
            'code' => $code,
        ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json('access_token');
    }

    /**
     * Fetch shop details from Shopify API.
     */
    public function getShopData(string $shopDomain, string $accessToken): ?array
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
        ])->get("https://{$shopDomain}/admin/api/{$this->apiVersion}/shop.json");

        if ($response->failed()) {
            return null;
        }

        return $response->json('shop');
    }

    /**
     * Create or update shop in database.
     */
    public function createOrUpdateShop(string $shopDomain, string $accessToken): Shop
    {
        $shopData = $this->getShopData($shopDomain, $accessToken);

        return Shop::updateOrCreate(
            ['shop_domain' => $shopDomain],
            [
                'shopify_token' => $accessToken,
                'shop_data' => $shopData,
                'is_active' => true,
            ]
        );
    }

    /**
     * Validate shop domain format.
     */
    public function isValidShopDomain(string $domain): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/', $domain);
    }

    /**
     * Make an authenticated API request to Shopify.
     */
    public function apiRequest(string $shopDomain, string $endpoint, array $params = []): ?array
    {
        $shop = Shop::findByDomain($shopDomain);

        if (!$shop || !$shop->is_active) {
            return null;
        }

        $url = "https://{$shopDomain}/admin/api/{$this->apiVersion}/{$endpoint}";

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $shop->shopify_token,
        ])->get($url, $params);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Uninstall a shop (deactivate).
     */
    public function uninstallShop(string $shopDomain): bool
    {
        $shop = Shop::findByDomain($shopDomain);

        if (!$shop) {
            return false;
        }

        $shop->deactivate();

        return true;
    }
}
