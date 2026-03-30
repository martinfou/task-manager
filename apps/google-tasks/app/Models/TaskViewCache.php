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
     * Remove a task by ID from all cached view payloads for a user.
     * Surgically patches each cached payload instead of marking stale.
     */
    public static function removeTaskFromCaches(int $userId, string $taskId): void
    {
        $rows = static::where('user_id', $userId)->get();

        foreach ($rows as $row) {
            $payload = $row->payload;
            if (! is_array($payload) || ! isset($payload['items'])) {
                continue;
            }

            $before = count($payload['items']);
            $payload['items'] = array_values(array_filter(
                $payload['items'],
                fn (array $item) => static::extractTaskId($item, $row->view_name) !== $taskId,
            ));

            if (count($payload['items']) < $before) {
                $row->update([
                    'payload' => $payload,
                    'computed_at' => now(),
                    'stale_at' => null,
                ]);
            }
        }
    }

    /**
     * Update a task in-place within all cached view payloads for a user.
     * Only updates existing entries — does not add the task to views where it's absent.
     */
    public static function upsertTaskInCaches(int $userId, string $taskId, array $decodedTask, ?string $taskListId = null): void
    {
        $rows = static::where('user_id', $userId)->get();

        foreach ($rows as $row) {
            $payload = $row->payload;
            if (! is_array($payload) || ! isset($payload['items'])) {
                continue;
            }

            $found = false;
            $isAggregate = in_array($row->view_name, ['today', 'all']);

            foreach ($payload['items'] as $i => $item) {
                if (static::extractTaskId($item, $row->view_name) === $taskId) {
                    if ($isAggregate) {
                        // Aggregate views wrap tasks: { taskListId, taskListTitle, task }
                        $payload['items'][$i]['task'] = $decodedTask;
                        if ($taskListId !== null) {
                            $payload['items'][$i]['taskListId'] = $taskListId;
                        }
                    } else {
                        // Inbox/list views store flat task objects
                        $payload['items'][$i] = $decodedTask;
                    }
                    $found = true;
                    break;
                }
            }

            // If task was completed and view is 'today', remove it
            if ($found && $row->view_name === 'today' && ($decodedTask['status'] ?? '') === 'completed') {
                $payload['items'] = array_values(array_filter(
                    $payload['items'],
                    fn (array $item) => static::extractTaskId($item, $row->view_name) !== $taskId,
                ));
            }

            if ($found) {
                $row->update([
                    'payload' => $payload,
                    'computed_at' => now(),
                    'stale_at' => null,
                ]);
            }
        }
    }

    /**
     * Extract the task ID from a cached item based on view type.
     * Aggregate views (today, all) wrap tasks: { taskListId, task: { id, ... } }
     * Other views (inbox, list) store flat task objects: { id, ... }
     */
    private static function extractTaskId(array $item, string $viewName): ?string
    {
        if (in_array($viewName, ['today', 'all'])) {
            return $item['task']['id'] ?? null;
        }

        return $item['id'] ?? null;
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
