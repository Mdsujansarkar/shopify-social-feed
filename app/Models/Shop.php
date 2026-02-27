<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = [
        'shop_domain',
        'shopify_token',
        'shop_data',
        'is_active',
    ];

    protected $casts = [
        'shop_data' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'shopify_token',
    ];

    /**
     * Get the Instagram account associated with the shop.
     */
    public function instagramAccount()
    {
        return $this->hasOne(InstagramAccount::class);
    }

    /**
     * Find a shop by domain.
     */
    public static function findByDomain(string $domain): ?self
    {
        return static::where('shop_domain', $domain)->first();
    }

    /**
     * Activate the shop.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the shop.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }
}
