<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InstagramService;
use App\Models\OAuthState;
use App\Models\Shop;
use App\Models\InstagramAccount;

class InstagramAuthController extends Controller
{
    protected InstagramService $instagramService;

    public function __construct(InstagramService $instagramService)
    {
        $this->instagramService = $instagramService;
    }

    /**
     * Redirect to Facebook/Instagram OAuth page.
     */
    public function connect(Request $request)
    {
        $shopDomain = $request->query('shop') ?? $request->session()->get('shop_domain');

        if (!$shopDomain) {
            return redirect()->route('shopify.install')
                ->with('error', 'Shop domain is required.');
        }

        $shop = Shop::findByDomain($shopDomain);

        if (!$shop || !$shop->is_active) {
            return redirect()->route('shopify.install')
                ->with('error', 'Shop not found or not active.');
        }

        // Create OAuth state
        $state = OAuthState::createFor('instagram', ['shop_domain' => $shopDomain]);

        // Generate Instagram OAuth URL
        $authUrl = $this->instagramService->getAuthUrl($state->state);

        return redirect($authUrl);
    }

    /**
     * Handle Instagram/Facebook OAuth callback.
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error');
        $errorReason = $request->query('error_reason');

        // Handle user denial
        if ($error === 'access_denied') {
            return redirect()->route('dashboard.index')
                ->with('error', 'Instagram access was denied.');
        }

        // Verify state token
        $oauthState = OAuthState::verifyAndConsume($state, 'instagram');
        if (!$oauthState) {
            return response('Invalid or expired state token.', 400);
        }

        $shopDomain = $oauthState->metadata['shop_domain'];
        $shop = Shop::findByDomain($shopDomain);

        if (!$shop) {
            return response('Shop not found.', 404);
        }

        // Exchange code for short-lived token
        $shortLivedToken = $this->instagramService->exchangeCodeForToken($code);
        if (!$shortLivedToken) {
            return redirect()->route('dashboard.index', ['shop' => $shopDomain])
                ->with('error', 'Failed to obtain access token.');
        }

        // Exchange for long-lived token (60 days)
        $longLivedToken = $this->instagramService->getLongLivedToken($shortLivedToken);
        if (!$longLivedToken) {
            return redirect()->route('dashboard.index', ['shop' => $shopDomain])
                ->with('error', 'Failed to obtain long-lived token.');
        }

        // Fetch Instagram business accounts
        $instagramAccounts = $this->instagramService->getInstagramAccounts($longLivedToken['access_token']);

        if (!$instagramAccounts || empty($instagramAccounts)) {
            return redirect()->route('dashboard.index', ['shop' => $shopDomain])
                ->with('error', 'No Instagram business accounts found. Please ensure you have an Instagram Professional account connected to your Facebook page.');
        }

        // Store the first Instagram account (could extend to support multiple)
        $igAccount = $instagramAccounts[0];
        $instagramAccount = $this->instagramService->createOrUpdateAccount(
            $shop,
            $igAccount['id'],
            $longLivedToken,
            $igAccount
        );

        return redirect()->route('dashboard.index', ['shop' => $shopDomain])
            ->with('success', 'Instagram account connected successfully!');
    }

    /**
     * Disconnect Instagram account.
     */
    public function disconnect(Request $request)
    {
        $shopDomain = $request->query('shop') ?? $request->session()->get('shop_domain');

        if (!$shopDomain) {
            return redirect()->route('shopify.install')
                ->with('error', 'Shop domain is required.');
        }

        $shop = Shop::findByDomain($shopDomain);

        if (!$shop) {
            return redirect()->route('shopify.install')
                ->with('error', 'Shop not found.');
        }

        $instagramAccount = $shop->instagramAccount;

        if (!$instagramAccount) {
            return redirect()->route('dashboard.index', ['shop' => $shopDomain])
                ->with('error', 'No Instagram account connected.');
        }

        $this->instagramService->disconnectAccount($instagramAccount);

        return redirect()->route('dashboard.index', ['shop' => $shopDomain])
            ->with('success', 'Instagram account disconnected successfully.');
    }
}
