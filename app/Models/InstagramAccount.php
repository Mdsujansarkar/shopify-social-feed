<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class InstagramAccount extends Model
{
    protected $fillable = [
        'shop_id',
        'instagram_business_account_id',
        'access_token',
        'account_data',
        'token_expires_at',
    ];

    protected $casts = [
        'account_data' => 'array',
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
    ];

    /**
     * Get the shop that owns the Instagram account.
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the media posts for the Instagram account.
     */
    public function media()
    {
        return $this->hasMany(InstagramMedia::class);
    }

    /**
     * Check if the access token is expired.
     */
    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    /**
     * Check if the token will expire within the given days.
     */
    public function willTokenExpireIn(int $days = 7): bool
    {
        return $this->token_expires_at && $this->token_expires_at->lte(now()->addDays($days));
    }

    /**
     * Get the account username.
     */
    public function getUsernameAttribute(): ?string
    {
        return $this->account_data['username'] ?? null;
    }

    /**
     * Get the account profile picture URL.
     */
    public function getProfilePictureUrlAttribute(): ?string
    {
        return $this->account_data['profile_picture_url'] ?? null;
    }

    /**
     * Get the number of followers.
     */
    public function getFollowersCountAttribute(): int
    {
        return (int) ($this->account_data['followers_count'] ?? 0);
    }
}
