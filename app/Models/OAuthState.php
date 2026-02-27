<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OAuthState extends Model
{
    protected $table = 'oauth_states';

    protected $fillable = [
        'state',
        'provider',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Create a new state for the given provider.
     */
    public static function createFor(string $provider, array $metadata = []): self
    {
        return static::create([
            'state' => Str::random(64),
            'provider' => $provider,
            'expires_at' => now()->addMinutes(10),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Verify and consume a state token.
     */
    public static function verifyAndConsume(string $state, string $provider): ?self
    {
        $oauthState = static::where('state', $state)
            ->where('provider', $provider)
            ->where('expires_at', '>', now())
            ->first();

        if ($oauthState) {
            $oauthState->delete();
        }

        return $oauthState;
    }

    /**
     * Clean up expired states.
     */
    public static function cleanupExpired(): int
    {
        return static::where('expires_at', '<', now())->delete();
    }
}
