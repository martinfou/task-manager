/**
 * US-037: In-memory task cache for instant list switching.
 *
 * Keyed by navigation context (e.g. "today", "inbox", "all", "list:<id>").
 * Holds the last-fetched tasks array per context so revisiting shows data
 * immediately while a background refresh runs.
 *
 * Cleared on disconnect or explicit flush. Not persisted to disk — session
 * lifetime only (avoids stale data across sessions and storage limits).
 */
import { ref, readonly } from 'vue';

/**
 * @typedef {{ tasks: Array, timestamp: number }} CacheEntry
 */

export function useTaskCache() {
    /** @type {Map<string, CacheEntry>} */
    const store = new Map();

    /** Reactive counter bumped on every write so watchers can react. */
    const version = ref(0);

    /**
     * Build the cache key for a navigation context.
     * @param {'today'|'inbox'|'all'|'list'} navMode
     * @param {string|null} listId — required when navMode is 'list'
     */
    function cacheKey(navMode, listId = null) {
        if (navMode === 'list' && listId) return `list:${listId}`;
        return navMode; // 'today', 'inbox', 'all'
    }

    /**
     * Get cached tasks for the given context, or null if cold.
     * @returns {Array|null}
     */
    function get(navMode, listId = null) {
        const entry = store.get(cacheKey(navMode, listId));
        return entry ? entry.tasks : null;
    }

    /**
     * Store tasks for the given context.
     */
    function set(navMode, listId, tasks) {
        store.set(cacheKey(navMode, listId), {
            tasks: [...tasks],
            timestamp: Date.now(),
        });
        version.value++;
    }

    /**
     * Invalidate a specific context (e.g. after a mutation in that list).
     */
    function invalidate(navMode, listId = null) {
        store.delete(cacheKey(navMode, listId));
    }

    /**
     * Invalidate all entries that may contain a given task (by list ID).
     * Used after task mutations that could affect aggregate views.
     */
    function invalidateForList(listId) {
        store.delete(`list:${listId}`);
        // Aggregate views may include this list's tasks
        store.delete('today');
        store.delete('inbox');
        store.delete('all');
        version.value++;
    }

    /**
     * DEF-002: After a mutation, save the current tasks array into the cache
     * for the active view. This keeps the client-side cache in sync with the
     * optimistic UI without re-fetching from the server.
     *
     * Also invalidates other views that may be affected (aggregate views
     * and the specific list cache) so they are re-fetched fresh on next visit.
     *
     * @param {'today'|'inbox'|'all'|'list'} navMode - current navigation mode
     * @param {string|null} listId - current list ID (for list mode)
     * @param {Array} tasks - current tasks array (already reflects the mutation)
     * @param {string|null} affectedListId - list ID affected by the mutation (for cross-list invalidation)
     */
    function syncAfterMutation(navMode, listId, tasks, affectedListId = null) {
        // Save the current (correct) state for the active view
        set(navMode, listId, tasks);

        // Invalidate other views that might contain stale data.
        // The active view was just set above, so re-deleting it is harmless
        // (set() already overwrote it). We invalidate aggregates so the next
        // navigation triggers a fresh server fetch.
        const currentKey = cacheKey(navMode, listId);
        for (const key of ['today', 'inbox', 'all']) {
            if (key !== currentKey) store.delete(key);
        }
        if (affectedListId && `list:${affectedListId}` !== currentKey) {
            store.delete(`list:${affectedListId}`);
        }
        version.value++;
    }

    /**
     * Clear the entire cache (disconnect, logout, etc.).
     */
    function flush() {
        store.clear();
        version.value++;
    }

    /**
     * Check if a cached entry exists.
     */
    function has(navMode, listId = null) {
        return store.has(cacheKey(navMode, listId));
    }

    return {
        get,
        set,
        has,
        invalidate,
        invalidateForList,
        syncAfterMutation,
        flush,
        version: readonly(version),
    };
}
