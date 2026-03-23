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

    public function isFresh(int $ttlMinutes = 30): bool
    {
        if ($this->stale_at !== null) {
            return false;
        }

        return $this->computed_at && $this->computed_at->diffInMinutes(now()) < $ttlMinutes;
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
