<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskViewCache extends Model
{
    protected $table = 'task_view_cache';

    protected $fillable = ['user_id', 'view_name', 'params_hash', 'payload', 'computed_at', 'stale_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'computed_at' => 'datetime',
            'stale_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFresh(int $ttlMinutes = 5): bool
    {
        if ($this->stale_at !== null) {
            return false;
        }

        return $this->computed_at && $this->computed_at->diffInMinutes(now()) < $ttlMinutes;
    }

    /**
     * Mark all view caches for a user as stale.
     */
    public static function markStaleForUser(int $userId): void
    {
        static::where('user_id', $userId)
            ->whereNull('stale_at')
            ->update(['stale_at' => now()]);
    }

    /**
     * Delete all view caches for a user (disconnect/purge).
     */
    public static function purgeForUser(int $userId): void
    {
        static::where('user_id', $userId)->delete();
    }

    /**
     * Build a deterministic params hash for cache keying.
     */
    public static function paramsHash(array $params = []): string
    {
        if (empty($params)) {
            return '';
        }
        ksort($params);

        return md5(json_encode($params));
    }

    /**
     * Get cached view data for a user.
     */
    public static function getCached(int $userId, string $viewName, array $params = []): ?static
    {
        return static::where('user_id', $userId)
            ->where('view_name', $viewName)
            ->where('params_hash', static::paramsHash($params))
            ->first();
    }

    /**
     * Store or update cached view data.
     */
    public static function putCache(int $userId, string $viewName, array $params, array $payload): static
    {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'view_name' => $viewName,
                'params_hash' => static::paramsHash($params),
            ],
            [
                'payload' => $payload,
                'computed_at' => now(),
                'stale_at' => null,
            ],
        );
    }
}
