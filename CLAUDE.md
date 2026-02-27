# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A Laravel 12 application integrating Shopify stores with Instagram Professional accounts via the Instagram Graph API. The app uses a dual-OAuth architecture: Shopify OAuth for shop authentication, followed by Facebook Login for Instagram Business account connection.

## Development Commands

### Docker (recommended for production-like environment)
```bash
# Start all services (app, nginx, postgres, redis, worker, scheduler)
docker compose up -d

# Rebuild after Dockerfile changes
docker compose up -d --build

# View logs
docker compose logs -f app

# Run commands inside container
docker compose exec app php artisan migrate
docker compose exec app php artisan queue:work
docker compose exec app composer install
```

### Local development
```bash
# Full development stack (server, queue, logs, vite)
composer run dev

# Setup fresh installation
composer run setup

# Run tests
composer run test
# Or single test suite
php artisan test --testsuite=Feature
php artisan test --filter test_name

# Code quality
./vendor/bin/pint  # Laravel Pint formatter
```

### Cache and configuration
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

## Architecture

### OAuth Flow & Security

**Shopify OAuth** (`/shopify/install`):
1. User enters shop domain or is redirected from Shopify App Store
2. `ShopifyService::getInstallUrl()` generates OAuth URL with state token
3. User approves app → redirected to `/shopify/callback`
4. **CRITICAL**: `ShopifyService::verifyHmac()` validates ALL request parameters using HMAC-SHA256 to prevent request forgery
5. Exchange code for access token, fetch shop data, store in `shops` table

**Instagram OAuth** (`/instagram/connect`):
1. Create `OAuthState` record for CSRF protection (auto-expires after 10 minutes)
2. Redirect to Facebook Login with scopes
3. Facebook returns code + state → `/instagram/callback`
4. Verify and consume state token
5. Exchange short-lived token for long-lived token (60 days)
6. Fetch Instagram business accounts linked to user's Facebook Pages
7. Store in `instagram_accounts` table

### Authentication Middleware

`ShopAuth` middleware protects all `/dashboard/*` routes:
- Extracts shop domain from `?shop=` query param or session
- Validates shop exists and is active
- Shares shop instance with views/controllers via `$request->attributes->set('shop', $shop)`

### Data Model Relationships

```
Shop (hasOne) → InstagramAccount (hasMany) → InstagramMedia
```

- `Shop::findByDomain($domain)` - Find shop by domain
- `$shop->instagramAccount` - Get connected IG account
- `$account->media()->orderBy('posted_at', 'desc')->paginate(24)` - Get paginated media
- `$account->isTokenExpired()` / `$account->willTokenExpireIn(7)` - Token expiry checks

### Background Jobs

`SyncInstagramMediaJob` - Fetches up to 100 Instagram posts via pagination. Worker container runs `php artisan queue:work`. Scheduler container runs `php artisan schedule:run` every 60 seconds for scheduled tasks.

## Configuration Required

### Environment variables (.env)
```bash
# Shopify
SHOPIFY_API_KEY=...          # From Shopify Partners dashboard
SHOPIFY_API_SECRET=...
SHOPIFY_REDIRECT_URI=http://localhost:8000/shopify/callback

# Instagram/Facebook
INSTAGRAM_APP_ID=...         # Facebook App ID
INSTAGRAM_APP_SECRET=...
INSTAGRAM_REDIRECT_URI=http://localhost:8000/instagram/callback
```

### Third-party setup
**Shopify**: Create app at https://partners.shopify.com/, set redirect URI, configure scopes

**Instagram/Facebook**: Create app at https://developers.facebook.com/, add "Instagram Graph API" product, configure OAuth redirect. **User must have Instagram Professional (Business/Creator) account linked to a Facebook Page.**

## Important Security Patterns

1. **HMAC Verification (Shopify)**: All Shopify callbacks must verify HMAC signature. The `verifyHmac()` method in `ShopifyService` removes the `hmac` param, sorts remaining params by key, builds query string, and compares using `hash_equals()` for timing-attack safety.

2. **OAuth State Pattern**: Create state token before OAuth redirect, verify and consume on callback. Prevents CSRF. Auto-expires after 10 minutes via database timestamp check.

3. **Token Storage**: Access tokens stored in database, hidden from JSON serialization via `$hidden` property on models.

## File Structure Notes

- Services: `app/Services/ShopifyService.php`, `app/Services/InstagramService.php` - Third-party API clients
- Middleware: `app/Http/Middleware/ShopAuth.php` - Dashboard authentication
- Jobs: `app/Jobs/SyncInstagramMediaJob.php` - Async media sync
- Docker: `docker-compose.yml` defines multi-service stack with health checks

## Common Issues

- **"No Instagram business accounts found"**: User's Instagram is not Professional account OR not linked to Facebook Page
- **"Invalid HMAC signature"**: `SHOPIFY_API_SECRET` mismatch or missing params in HMAC calculation
- **Token expires after 60 days**: Use `/dashboard/refresh-token` endpoint or create scheduled task