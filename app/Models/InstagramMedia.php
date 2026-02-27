<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstagramMedia extends Model
{
    protected $fillable = [
        'instagram_account_id',
        'instagram_media_id',
        'media_type',
        'media_url',
        'caption',
        'likes_count',
        'comments_count',
        'posted_at',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
    ];

    /**
     * Get the Instagram account that owns the media.
     */
    public function instagramAccount()
    {
        return $this->belongsTo(InstagramAccount::class);
    }

    /**
     * Scope a query to only include images.
     */
    public function scopeImages($query)
    {
        return $query->where('media_type', 'IMAGE');
    }

    /**
     * Scope a query to only include videos.
     */
    public function scopeVideos($query)
    {
        return $query->where('media_type', 'VIDEO');
    }

    /**
     * Scope a query to only include carousel albums.
     */
    public function scopeCarousels($query)
    {
        return $query->where('media_type', 'CAROUSEL_ALBUM');
    }

    /**
     * Scope a query to order by likes count.
     */
    public function scopeOrderByLikes($query, $direction = 'desc')
    {
        return $query->orderBy('likes_count', $direction);
    }

    /**
     * Sync or create media by Instagram media ID.
     */
    public static function syncOrCreate(string $instagramMediaId, int $accountId, array $data): self
    {
        return static::updateOrCreate(
            ['instagram_media_id' => $instagramMediaId],
            [
                'instagram_account_id' => $accountId,
                'media_type' => $data['media_type'],
                'media_url' => $data['media_url'],
                'caption' => $data['caption'] ?? null,
                'likes_count' => $data['likes_count'] ?? 0,
                'comments_count' => $data['comments_count'] ?? 0,
                'posted_at' => $data['posted_at'] ?? now(),
            ]
        );
    }
}
