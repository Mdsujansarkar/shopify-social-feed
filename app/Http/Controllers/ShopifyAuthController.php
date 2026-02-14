<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShopifyService;
use App\Models\OAuthState;

class ShopifyAuthController extends Controller
{
    protected ShopifyService $shopifyService;

    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }

    /**
     * Redirect to Shopify OAuth installation page.
     */
    public function install(Request $request)
    {
        $shopDomain = $request->query('shop');

        if (!$shopDomain) {
            return view('shopify.install', [
                'error' => 'Shop domain is required. Use format: ?shop=your-store.myshopify.com'
            ]);
        }

        // Validate shop domain format
        if (!$this->shopifyService->isValidShopDomain($shopDomain)) {
            return view('shopify.install', [
                'error' => 'Invalid shop domain format. Use format: your-store.myshopify.com'
            ]);
        }

        // Create OAuth state for security
        $state = OAuthState::createFor('shopify', ['shop_domain' => $shopDomain]);

        // Generate Shopify OAuth URL
        $installUrl = $this->shopifyService->getInstallUrl($shopDomain, $state->state);

        return redirect($installUrl);
    }

    /**
     * Handle Shopify OAuth callback.
     */
    public function callback(Request $request)
    {
        // Verify HMAC signature (critical for security)
        if (!$this->shopifyService->verifyHmac($request->all())) {
            return response('Invalid HMAC signature.', 400);
        }

        $shopDomain = $request->query('shop');
        $code = $request->query('code');
        $state = $request->query('state');

        // Verify state token
        $oauthState = OAuthState::verifyAndConsume($state, 'shopify');
        if (!$oauthState) {
            return response('Invalid or expired state token.', 400);
        }

        // Verify shop domain matches
        if ($oauthState->metadata['shop_domain'] !== $shopDomain) {
            return response('Shop domain mismatch.', 400);
        }

        // Exchange code for access token
        $accessToken = $this->shopifyService->exchangeCodeForToken($shopDomain, $code);
        if (!$accessToken) {
            return response('Failed to obtain access token.', 500);
        }

        // Create or update shop in database
        $shop = $this->shopifyService->createOrUpdateShop($shopDomain, $accessToken);

        // Store shop in session
        $request->session()->put('shop_domain', $shopDomain);

        return redirect()->route('dashboard.index', ['shop' => $shopDomain])
            ->with('success', 'Shop installed successfully!');
    }

    /**
     * Handle app uninstall webhook from Shopify.
     */
    public function uninstall(Request $request)
    {
        $shopDomain = $request->header('X-Shopify-Shop-Domain');

        if (!$shopDomain) {
            return response('Shop domain header missing.', 400);
        }

        // Verify webhook HMAC
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, config('services.shopify.api_secret'), true));

        if (!hash_equals($hmacHeader, $calculatedHmac)) {
            return response('Invalid HMAC signature.', 401);
        }

        // Deactivate shop
        $this->shopifyService->uninstallShop($shopDomain);

        return response('', 200);
    }
}
