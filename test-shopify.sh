#!/bin/bash

echo "======================================"
echo "Shopify App Test Script"
echo "======================================"
echo ""

APP_URL="http://89.117.60.72:3000"

echo "📋 Configuration Check:"
echo "----------------------------------------"
curl -s "$APP_URL/test/shopify" | jq '.'
echo ""

echo "🔐 HMAC Verification Test:"
echo "----------------------------------------"
curl -s "$APP_URL/test/hmac" | jq '.'
echo ""

echo "✅ Test Complete!"
echo ""
echo "To test the full OAuth flow:"
echo "1. Visit: $APP_URL/shopify/install?shop=your-store.myshopify.com"
echo "2. Replace 'your-store' with your actual development store"
echo "3. Approve the app in Shopify"
echo "4. Check dashboard: $APP_URL/dashboard?shop=your-store.myshopify.com"
echo ""
echo "To check database shops:"
echo "docker compose exec app php artisan tinker --execute='\\App\\Models\\Shop::all()'"
