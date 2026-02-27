<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Shop;

class ShopAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get shop domain from query param or session
        $shopDomain = $request->query('shop') ?? $request->session()->get('shop_domain');

        if (!$shopDomain) {
            return redirect()->route('shopify.install')
                ->with('error', 'Shop domain is required. Please install the app first.');
        }

        // Find the shop in database
        $shop = Shop::findByDomain($shopDomain);

        if (!$shop) {
            return redirect()->route('shopify.install')
                ->with('error', 'Shop not found. Please install the app first.');
        }

        if (!$shop->is_active) {
            return redirect()->route('shopify.install')
                ->with('error', 'Shop is not active. Please reinstall the app.');
        }

        // Share shop with views and controllers
        $request->attributes->set('shop', $shop);
        $request->session()->put('shop_domain', $shopDomain);

        return $next($request);
    }
}
