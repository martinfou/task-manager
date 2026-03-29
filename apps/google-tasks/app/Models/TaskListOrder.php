<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class TaskListOrder extends Model
{
    protected $table = 'task_list_order';

    protected $fillable = ['user_id', 'google_list_id', 'position', 'pinned'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'pinned' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get stored order entries for a user, pinned first then by position.
     *
     * @return list<array{google_list_id: string, pinned: bool, position: int}>
     */
    public static function getOrderedEntries(int $userId): array
    {
        return static::where('user_id', $userId)
            ->orderByDesc('pinned')
            ->orderBy('position')
            ->get(['google_list_id', 'pinned', 'position'])
            ->map(fn (self $row) => [
                'google_list_id' => $row->google_list_id,
                'pinned' => $row->pinned,
                'position' => $row->position,
            ])
            ->values()
            ->all();
    }

    /**
     * Apply custom order to a raw Google API list-items array.
     * Injects a `pinned` boolean flag into each list item.
     * On first use, auto-pins a list named "In" (or the first list).
     *
     * @param  list<array<string, mixed>>  $googleLists  Raw items from Google Tasks API
     * @return list<array<string, mixed>> Reordered items with `pinned` flag
     */
    public static function applyOrder(int $userId, array $googleLists, ?string $autoSort = null): array
    {
        if ($googleLists === []) {
            return [];
        }

        $entries = static::getOrderedEntries($userId);

        // First use: seed initial order with auto-pin
        if ($entries === []) {
            static::autoPin($userId, $googleLists);
            $entries = static::getOrderedEntries($userId);
        }

        // Clean up stale entries (lists deleted from Google)
        $currentIds = array_column($googleLists, 'id');
        static::cleanupStale($userId, $currentIds);

        // Build lookup: google_list_id → {pinned, position}
        $orderMap = [];
        foreach ($entries as $entry) {
            $orderMap[$entry['google_list_id']] = $entry;
        }

        // Partition into known (in order table) and new (not yet tracked)
        $pinned = [];
        $unpinned = [];
        $newLists = [];

        foreach ($googleLists as $list) {
            $id = $list['id'] ?? '';
            if (isset($orderMap[$id])) {
                $list['pinned'] = $orderMap[$id]['pinned'];
                if ($orderMap[$id]['pinned']) {
                    $pinned[$orderMap[$id]['position']] = $list;
                } else {
                    $unpinned[$orderMap[$id]['position']] = $list;
                }
            } else {
                $list['pinned'] = false;
                $newLists[] = $list;
            }
        }

        ksort($pinned);
        ksort($unpinned);
        $pinned = array_values($pinned);
        $unpinned = array_values($unpinned);

        // Insert new lists and persist them
        if ($newLists !== []) {
            $maxPosition = static::where('user_id', $userId)
                ->where('pinned', false)
                ->max('position') ?? -1;

            foreach ($newLists as $list) {
                $maxPosition++;
                static::create([
                    'user_id' => $userId,
                    'google_list_id' => $list['id'] ?? '',
                    'position' => $maxPosition,
                    'pinned' => false,
                ]);
            }
            $unpinned = array_merge($unpinned, $newLists);
        }

        // Apply auto-sort to unpinned if enabled
        if ($autoSort === 'alpha_asc') {
            usort($unpinned, fn ($a, $b) => strcasecmp($a['title'] ?? '', $b['title'] ?? ''));
        } elseif ($autoSort === 'alpha_desc') {
            usort($unpinned, fn ($a, $b) => strcasecmp($b['title'] ?? '', $a['title'] ?? ''));
        }

        return array_merge($pinned, $unpinned);
    }

    /**
     * Bulk save a new order. Positions are implied by array index.
     *
     * @param  list<array{id: string, pinned: bool}>  $items  Ordered list items
     */
    public static function saveOrder(int $userId, array $items, ?string $autoSort = null): void
    {
        DB::transaction(function () use ($userId, $items) {
            static::where('user_id', $userId)->delete();

            foreach ($items as $position => $item) {
                static::create([
                    'user_id' => $userId,
                    'google_list_id' => $item['id'],
                    'position' => $position,
                    'pinned' => $item['pinned'] ?? false,
                ]);
            }
        });

        if ($autoSort !== null) {
            User::where('id', $userId)->update(['task_list_auto_sort' => $autoSort ?: null]);
        }
    }

    /**
     * Toggle pin state for a single list. Returns the new pinned state.
     */
    public static function togglePin(int $userId, string $googleListId): bool
    {
        $entry = static::where('user_id', $userId)
            ->where('google_list_id', $googleListId)
            ->first();

        if (! $entry) {
            return false;
        }

        $newPinned = ! $entry->pinned;

        // Move to end of the target section
        $maxPosition = static::where('user_id', $userId)
            ->where('pinned', $newPinned)
            ->max('position') ?? -1;

        $entry->update([
            'pinned' => $newPinned,
            'position' => $maxPosition + 1,
        ]);

        // Recompact positions in both sections
        static::recompactPositions($userId, true);
        static::recompactPositions($userId, false);

        return $newPinned;
    }

    /**
     * Remove order entries for lists no longer in Google.
     *
     * @param  list<string>  $currentGoogleListIds
     */
    public static function cleanupStale(int $userId, array $currentGoogleListIds): void
    {
        if ($currentGoogleListIds === []) {
            return;
        }

        static::where('user_id', $userId)
            ->whereNotIn('google_list_id', $currentGoogleListIds)
            ->delete();
    }

    /**
     * Delete all order entries for a user (disconnect/purge).
     */
    public static function purgeForUser(int $userId): void
    {
        static::where('user_id', $userId)->delete();
    }

    /**
     * First-use initialization: pin list named "In" (case-insensitive) or first list.
     *
     * @param  list<array<string, mixed>>  $googleLists
     */
    public static function autoPin(int $userId, array $googleLists): void
    {
        $pinIndex = null;
        foreach ($googleLists as $i => $list) {
            if (strcasecmp($list['title'] ?? '', 'In') === 0) {
                $pinIndex = $i;
                break;
            }
        }
        $pinIndex ??= 0;

        $pinnedPosition = 0;
        $unpinnedPosition = 0;

        foreach ($googleLists as $i => $list) {
            $isPinned = ($i === $pinIndex);
            static::create([
                'user_id' => $userId,
                'google_list_id' => $list['id'] ?? '',
                'position' => $isPinned ? $pinnedPosition++ : $unpinnedPosition++,
                'pinned' => $isPinned,
            ]);
        }
    }

    /**
     * Recompact positions in a section (pinned or unpinned) to be sequential.
     */
    private static function recompactPositions(int $userId, bool $pinned): void
    {
        $entries = static::where('user_id', $userId)
            ->where('pinned', $pinned)
            ->orderBy('position')
            ->get();

        foreach ($entries as $i => $entry) {
            if ($entry->position !== $i) {
                $entry->update(['position' => $i]);
            }
        }
    }
}
