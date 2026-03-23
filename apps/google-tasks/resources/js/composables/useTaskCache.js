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
        flush,
        version: readonly(version),
    };
}
