<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardStatsCache extends Model
{
    protected $table = 'dashboard_stats_cache';

    protected $fillable = ['user_id', 'range_days', 'stats', 'computed_at', 'stale_at'];

    protected function casts(): array
    {
        return [
            'stats' => 'array',
            'computed_at' => 'datetime',
            'stale_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFresh(?int $ttlMinutes = null): bool
    {
        if ($this->stale_at !== null) {
            return false;
        }

        $ttl = $ttlMinutes ?? (int) config('google-tasks.dashboard_stats_cache_ttl_minutes', 90);

        return $this->computed_at && $this->computed_at->diffInMinutes(now()) < $ttl;
    }

    /**
     * Mark all cache rows for a user as stale.
     */
    public static function markStaleForUser(int $userId): void
    {
        static::where('user_id', $userId)
            ->whereNull('stale_at')
            ->update(['stale_at' => now()]);
    }
}
