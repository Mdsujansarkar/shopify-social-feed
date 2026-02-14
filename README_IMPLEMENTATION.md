# Shopify App with Instagram Integration - Implementation Complete

## What Was Built

A complete Laravel 12 application that integrates Shopify stores with Instagram Professional accounts via the Instagram Graph API.

### Features Implemented

- ✅ **Shopify OAuth Authentication** - Secure installation flow with HMAC verification
- ✅ **Instagram/Facebook OAuth** - Connect Instagram Business accounts
- ✅ **Dashboard** - View shop info, IG account, and media posts
- ✅ **Media Sync** - Fetch and display Instagram posts
- ✅ **Token Management** - Long-lived tokens (60 days) with refresh capability
- ✅ **Background Jobs** - Queue-based media synchronization
- ✅ **Security** - HMAC verification, OAuth state tokens, encrypted access tokens

## Project Structure

```
app/
├── Models/
│   ├── Shop.php                    # Shop model with Instagram relationship
│   ├── InstagramAccount.php         # IG account with token expiry checks
│   ├── InstagramMedia.php          # Media posts with scopes
│   └── OAuthState.php              # OAuth state management
├── Services/
│   ├── ShopifyService.php          # Shopify API + OAuth
│   └── InstagramService.php        # Instagram Graph API
├── Http/
│   ├── Controllers/
│   │   ├── ShopifyAuthController.php   # Shopify auth flow
│   │   ├── InstagramAuthController.php  # Instagram auth flow
│   │   └── DashboardController.php      # Main dashboard
│   └── Middleware/
│       └── ShopAuth.php            # Shop authentication middleware
└── Jobs/
    └── SyncInstagramMediaJob.php   # Background media sync

resources/views/
├── dashboard/
│   ├── index.blade.php            # Main dashboard
│   ├── instagram-account.blade.php # Account details
│   └── instagram-media.blade.php   # Media gallery
└── shopify/
    └── install.blade.php          # Installation page

routes/
└── web.php                        # All routes defined

database/migrations/
├── create_shops_table.php
├── create_instagram_accounts_table.php
├── create_instagram_media_table.php
└── create_oauth_states_table.php
```

## Setup Instructions

### 1. Configure Environment Variables

Edit `.env` file and add:

```bash
# Shopify (Get from https://partners.shopify.com/)
SHOPIFY_API_KEY=your_api_key
SHOPIFY_API_SECRET=your_api_secret
SHOPIFY_REDIRECT_URI=http://localhost:8000/shopify/callback
SHOPIFY_SCOPES=read_products,read_orders,read_content
SHOPIFY_API_VERSION=2024-01

# Instagram/Facebook (Get from https://developers.facebook.com/)
INSTAGRAM_APP_ID=your_facebook_app_id
INSTAGRAM_APP_SECRET=your_facebook_app_secret
INSTAGRAM_REDIRECT_URI=http://localhost:8000/instagram/callback
INSTAGRAM_GRAPH_VERSION=v19.0
INSTAGRAM_SCOPES=instagram_basic,instagram_manage_insights,pages_show_list
```

### 2. Required API Credentials Setup

#### Shopify Setup
1. Go to https://partners.shopify.com/
2. Create a new app
3. Configure redirect URI: `http://localhost:8000/shopify/callback`
4. Copy API Key and Secret to `.env`
5. Install app on a test store

#### Instagram/Facebook Setup
1. Go to https://developers.facebook.com/
2. Create a new app (Select "Business" type)
3. Add "Instagram Graph API" product
4. Configure redirect URI: `http://localhost:8000/instagram/callback`
5. Add permissions: `instagram_basic`, `instagram_manage_insights`, `pages_show_list`
6. Copy App ID and App Secret to `.env`
7. IMPORTANT: You must have an Instagram Professional (Business/Creator) account linked to a Facebook Page

### 3. Database Setup

```bash
# Ensure MySQL is configured in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=social_feed
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations
php artisan migrate
```

### 4. Start the Development Server

```bash
php artisan serve
```

The app will be available at: `http://localhost:8000`

## Usage Flow

### 1. Install the Shopify App

Visit: `http://localhost:8000/shopify/install?shop=your-store.myshopify.com`

This will:
- Redirect to Shopify OAuth
- Verify HMAC signature (security)
- Exchange code for access token
- Store shop in database
- Redirect to dashboard

### 2. Connect Instagram Account

After Shopify installation, click "Connect Instagram" on the dashboard.

This will:
- Redirect to Facebook Login
- Request Instagram permissions
- Exchange for long-lived token (60 days)
- Fetch Instagram Business accounts from Facebook pages
- Store account in database
- Redirect back to dashboard

### 3. Sync Instagram Media

Click "Sync New Posts" to fetch latest media from Instagram.

This will:
- Fetch up to 100 recent posts
- Store in database
- Display on dashboard

### 4. View Media Gallery

Visit `/dashboard/media?shop=your-store.myshopify.com` to view all posts.

Filter by type:
- All posts
- Images only
- Videos only
- Carousel albums

## Security Features

- ✅ **HMAC Verification** - All Shopify callbacks are verified
- ✅ **OAuth State Tokens** - Prevents CSRF attacks
- ✅ **Token Encryption** - Access tokens hidden from JSON
- ✅ **Shop Domain Validation** - Validates `.myshopify.com` format
- ✅ **Session Management** - Shop domain stored in session

## API Routes

### Public Routes
- `GET /` - Welcome page
- `GET /shopify/install` - Start Shopify OAuth
- `GET /shopify/callback` - Shopify OAuth callback
- `POST /shopify/uninstall` - App uninstall webhook
- `GET /instagram/connect` - Start Instagram OAuth
- `GET /instagram/callback` - Instagram OAuth callback
- `GET /instagram/disconnect` - Disconnect Instagram account

### Protected Routes (require shop authentication)
- `GET /dashboard` - Main dashboard
- `GET /dashboard/instagram-account` - Account details
- `GET /dashboard/media` - Media gallery
- `POST /dashboard/sync-media` - Sync media from Instagram
- `POST /dashboard/refresh-token` - Refresh access token

## Models

### Shop
- `shop_domain` - Unique Shopify domain
- `shopify_token` - Encrypted access token
- `shop_data` - JSON shop details
- `is_active` - Active status

### InstagramAccount
- `shop_id` - Foreign key to shops
- `instagram_business_account_id` - IG account ID
- `access_token` - Long-lived token (hidden from JSON)
- `account_data` - JSON account details
- `token_expires_at` - Token expiry timestamp

### InstagramMedia
- `instagram_account_id` - Foreign key to accounts
- `instagram_media_id` - Unique media ID
- `media_type` - IMAGE, VIDEO, or CAROUSEL_ALBUM
- `media_url` - URL to media file
- `caption` - Post caption
- `likes_count` - Number of likes
- `comments_count` - Number of comments
- `posted_at` - Post timestamp

## Services

### ShopifyService
```php
// Generate OAuth URL
$url = $shopifyService->getInstallUrl($shopDomain, $state);

// Verify HMAC signature
$isValid = $shopifyService->verifyHmac($params);

// Exchange code for token
$token = $shopifyService->exchangeCodeForToken($shopDomain, $code);

// Get shop data
$data = $shopifyService->getShopData($shopDomain, $accessToken);

// Create/update shop
$shop = $shopifyService->createOrUpdateShop($shopDomain, $accessToken);
```

### InstagramService
```php
// Generate OAuth URL
$url = $instagramService->getAuthUrl($state);

// Exchange code for token
$token = $instagramService->exchangeCodeForToken($code);

// Get long-lived token
$longToken = $instagramService->getLongLivedToken($shortToken);

// Get Instagram accounts
$accounts = $instagramService->getInstagramAccounts($accessToken);

// Sync media
$media = $instagramService->syncMedia($instagramAccount);

// Refresh token
$newToken = $instagramService->refreshToken($accessToken);
```

## Queue Setup (Optional)

For background media sync:

```bash
# Start queue worker
php artisan queue:work

# Or run in background
php artisan queue:work --daemon
```

## Troubleshooting

### "No Instagram business accounts found"
- Ensure you have an Instagram Professional (Business/Creator) account
- Link your Instagram account to a Facebook Page
- Verify your Facebook app has the required permissions

### "Invalid HMAC signature"
- Check SHOPIFY_API_SECRET is correct in .env
- Verify all query parameters are included in HMAC calculation

### "Token expired"
- Click "Refresh Token" button
- Or use: `php artisan tinker --execute="App\Models\InstagramAccount::first()->refresh();"`

### Routes not working
- Clear route cache: `php artisan route:clear`
- Clear config cache: `php artisan config:clear`

## Testing

1. Visit `http://localhost:8000/shopify/install?shop=test.myshopify.com`
2. (Will show error for non-existent store - this is expected)
3. For real testing, use a valid Shopify development store
4. After Shopify OAuth, connect Instagram
5. Verify dashboard shows account info
6. Test media sync functionality

## Next Steps (Optional Enhancements)

- [ ] Add webhooks for real-time Instagram updates
- [ ] Implement token refresh automation
- [ ] Add media analytics and insights
- [ ] Create scheduled sync commands
- [ ] Add multi-store support
- [ ] Implement rate limiting
- [ ] Add error monitoring (Sentry/Bugsnag)
- [ ] Create admin panel for managing all shops

## License

Proprietary - For internal use only.
