<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\Shop;
use App\Models\InstagramAccount;
use App\Models\InstagramMedia;
use Carbon\Carbon;

class InstagramService
{
    protected string $appId;
    protected string $appSecret;
    protected string $redirectUri;
    protected string $graphVersion;

    public function __construct()
    {
        $this->appId = config('services.instagram.app_id');
        $this->appSecret = config('services.instagram.app_secret');
        $this->redirectUri = config('services.instagram.redirect_uri');
        $this->graphVersion = config('services.instagram.graph_version', 'v19.0');
    }

    /**
     * Generate Facebook Login URL for Instagram connection.
     */
    public function getAuthUrl(string $state = null): string
    {
        $state = $state ?? Str::random(40);
        $scopes = config('services.instagram.scopes', 'instagram_basic,instagram_manage_insights,pages_show_list');

        $query = http_build_query([
            'client_id' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $scopes,
            'state' => $state,
            'response_type' => 'code',
            'auth_type' => 'rerequest',
        ]);

        return "https://www.facebook.com/{$this->graphVersion}/dialog/oauth?{$query}";
    }

    /**
     * Exchange authorization code for short-lived token.
     */
    public function exchangeCodeForToken(string $code): ?string
    {
        $response = Http::asForm()->get("https://graph.facebook.com/{$this->graphVersion}/oauth/access_token", [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json('access_token');
    }

    /**
     * Exchange short-lived token for long-lived token (60 days).
     */
    public function getLongLivedToken(string $shortLivedToken): ?array
    {
        $response = Http::get("https://graph.facebook.com/{$this->graphVersion}/oauth/access_token", [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => $this->appSecret,
            'access_token' => $shortLivedToken,
        ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Get Instagram business accounts from Facebook pages.
     */
    public function getInstagramAccounts(string $accessToken): ?array
    {
        // Get user's pages
        $pagesResponse = Http::get("https://graph.facebook.com/{$this->graphVersion}/me/accounts", [
            'access_token' => $accessToken,
            'fields' => 'id,name,instagram_business_account',
        ]);

        if ($pagesResponse->failed()) {
            return null;
        }

        $pages = $pagesResponse->json('data', []);
        $instagramAccounts = [];

        foreach ($pages as $page) {
            if (isset($page['instagram_business_account'])) {
                $igId = $page['instagram_business_account']['id'];

                // Get Instagram account details
                $igResponse = Http::get("https://graph.facebook.com/{$this->graphVersion}/{$igId}", [
                    'fields' => 'id,username,profile_picture_url,followers_count,media_count',
                    'access_token' => $accessToken,
                ]);

                if (!$igResponse->failed()) {
                    $igData = $igResponse->json();
                    $igData['page_id'] = $page['id'];
                    $igData['page_name'] = $page['name'];
                    $instagramAccounts[] = $igData;
                }
            }
        }

        return $instagramAccounts;
    }

    /**
     * Create or update Instagram account in database.
     */
    public function createOrUpdateAccount(Shop $shop, string $instagramAccountId, string $longLivedToken, array $accountData): InstagramAccount
    {
        $expiresIn = $longLivedToken['expires_in'] ?? 5184000; // Default 60 days in seconds
        $tokenExpiresAt = Carbon::now()->addSeconds($expiresIn);

        return InstagramAccount::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'instagram_business_account_id' => $instagramAccountId,
            ],
            [
                'access_token' => $longLivedToken['access_token'],
                'account_data' => $accountData,
                'token_expires_at' => $tokenExpiresAt,
            ]
        );
    }

    /**
     * Get media posts from Instagram account.
     */
    public function getMedia(InstagramAccount $account, int $limit = 25, ?string $after = null): ?array
    {
        $params = [
            'fields' => 'id,media_type,media_url,caption,like_count,comments_count,timestamp,permalink',
            'limit' => $limit,
        ];

        if ($after) {
            $params['after'] = $after;
        }

        $response = Http::get("https://graph.facebook.com/{$this->graphVersion}/{$account->instagram_business_account_id}/media", [
            ...$params,
            'access_token' => $account->access_token,
        ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Sync media posts from Instagram to database.
     */
    public function syncMedia(InstagramAccount $account): array
    {
        $synced = [];
        $after = null;
        $count = 0;
        $maxFetch = 100; // Maximum number of posts to fetch

        do {
            $mediaData = $this->getMedia($account, 25, $after);

            if (!$mediaData || !isset($mediaData['data'])) {
                break;
            }

            foreach ($mediaData['data'] as $media) {
                $mediaPost = InstagramMedia::syncOrCreate(
                    $media['id'],
                    $account->id,
                    [
                        'media_type' => $media['media_type'],
                        'media_url' => $media['media_url'] ?? $media['permalink'] ?? '',
                        'caption' => $media['caption'] ?? null,
                        'likes_count' => (int) ($media['like_count'] ?? 0),
                        'comments_count' => (int) ($media['comments_count'] ?? 0),
                        'posted_at' => $media['timestamp'] ?? now(),
                    ]
                );

                $synced[] = $mediaPost;
                $count++;

                if ($count >= $maxFetch) {
                    break 2;
                }
            }

            $after = $mediaData['paging']['after'] ?? null;
        } while ($after && $count < $maxFetch);

        return $synced;
    }

    /**
     * Refresh an expired or soon-to-expire access token.
     */
    public function refreshToken(string $accessToken): ?array
    {
        $response = Http::get("https://graph.facebook.com/{$this->graphVersion}/oauth/access_token", [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $accessToken,
        ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Disconnect Instagram account.
     */
    public function disconnectAccount(InstagramAccount $account): bool
    {
        return $account->delete();
    }

    /**
     * Get insights for an Instagram account.
     */
    public function getInsights(InstagramAccount $account, string $metric = 'impressions', string $period = 'day'): ?array
    {
        $response = Http::get("https://graph.facebook.com/{$this->graphVersion}/{$account->instagram_business_account_id}/insights", [
            'metric' => $metric,
            'period' => $period,
            'access_token' => $account->access_token,
        ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }
}
