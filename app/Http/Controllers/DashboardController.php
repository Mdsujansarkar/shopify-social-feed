<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;
use App\Services\InstagramService;

class DashboardController extends Controller
{
    protected InstagramService $instagramService;

    public function __construct(InstagramService $instagramService)
    {
        $this->instagramService = $instagramService;
    }

    /**
     * Display the main dashboard.
     */
    public function index(Request $request)
    {
        $shop = $request->attributes->get('shop');

        if (!$shop) {
            return redirect()->route('shopify.install')
                ->with('error', 'Shop not found. Please install the app first.');
        }

        $instagramAccount = $shop->instagramAccount;
        $recentMedia = $instagramAccount ? $instagramAccount->media()->orderBy('posted_at', 'desc')->take(12)->get() : collect();

        return view('dashboard.index', [
            'shop' => $shop,
            'instagramAccount' => $instagramAccount,
            'recentMedia' => $recentMedia,
        ]);
    }

    /**
     * Display Instagram account details.
     */
    public function instagramAccount(Request $request)
    {
        $shop = $request->attributes->get('shop');

        if (!$shop) {
            return redirect()->route('shopify.install')
                ->with('error', 'Shop not found.');
        }

        $instagramAccount = $shop->instagramAccount;

        if (!$instagramAccount) {
            return redirect()->route('dashboard.index', ['shop' => $shop->shop_domain])
                ->with('error', 'No Instagram account connected.');
        }

        return view('dashboard.instagram-account', [
            'shop' => $shop,
            'instagramAccount' => $instagramAccount,
        ]);
    }

    /**
     * Display Instagram media posts.
     */
    public function instagramMedia(Request $request)
    {
        $shop = $request->attributes->get('shop');

        if (!$shop) {
            return redirect()->route('shopify.install')
                ->with('error', 'Shop not found.');
        }

        $instagramAccount = $shop->instagramAccount;

        if (!$instagramAccount) {
            return redirect()->route('dashboard.index', ['shop' => $shop->shop_domain])
                ->with('error', 'No Instagram account connected.');
        }

        $mediaType = $request->query('type', 'all');
        $query = $instagramAccount->media()->orderBy('posted_at', 'desc');

        if ($mediaType !== 'all') {
            $query->where('media_type', strtoupper($mediaType));
        }

        $media = $query->paginate(24);

        return view('dashboard.instagram-media', [
            'shop' => $shop,
            'instagramAccount' => $instagramAccount,
            'media' => $media,
            'mediaType' => $mediaType,
        ]);
    }

    /**
     * Sync media from Instagram.
     */
    public function syncMedia(Request $request)
    {
        $shop = $request->attributes->get('shop');

        if (!$shop) {
            return response()->json(['error' => 'Shop not found.'], 404);
        }

        $instagramAccount = $shop->instagramAccount;

        if (!$instagramAccount) {
            return response()->json(['error' => 'No Instagram account connected.'], 400);
        }

        try {
            $synced = $this->instagramService->syncMedia($instagramAccount);

            return response()->json([
                'success' => true,
                'message' => count($synced) . ' media posts synced successfully.',
                'count' => count($synced),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to sync media: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh Instagram access token.
     */
    public function refreshToken(Request $request)
    {
        $shop = $request->attributes->get('shop');

        if (!$shop) {
            return response()->json(['error' => 'Shop not found.'], 404);
        }

        $instagramAccount = $shop->instagramAccount;

        if (!$instagramAccount) {
            return response()->json(['error' => 'No Instagram account connected.'], 400);
        }

        try {
            $newToken = $this->instagramService->refreshToken($instagramAccount->access_token);

            if (!$newToken) {
                return response()->json(['error' => 'Failed to refresh token.'], 500);
            }

            // Update token in database
            $instagramAccount->update([
                'access_token' => $newToken['access_token'],
                'token_expires_at' => now()->addSeconds($newToken['expires_in'] ?? 5184000),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Access token refreshed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to refresh token: ' . $e->getMessage(),
            ], 500);
        }
    }
}
