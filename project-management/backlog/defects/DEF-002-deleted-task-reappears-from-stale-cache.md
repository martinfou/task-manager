---
template_version: 1.1.0
last_updated: 2026-03-29
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# Defect: DEF-002 — Deleted Task Reappears from Stale Cache

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done
**Priority**: 🟠 High
**Story Points**: 3
**Created**: 2026-03-29
**Updated**: 2026-03-30
**Assigned Sprint**: [Sprint 8](../../sprints/sprint-08-google-tasks-cache-integrity.md)

## Description

When a user deletes a task, the task briefly disappears (optimistic UI removal) but reappears moments later when `pollOnce()` fetches the current view from the server. The server returns stale cached data that still contains the deleted task, because the cache was only marked stale — not refreshed — after the mutation.

This regression was introduced when `defer()` was removed from the stale-cache path in `cachedView()` (to fix Dreamhost shared hosting blocking). Previously, `defer()` would re-fetch from Google and update the cache in the background; now the stale cache persists until the next cron run (up to 15 minutes).

The same issue affects **all mutations** (create, update, move, complete/uncomplete), not just delete. Any action that calls `markCachesStale()` followed by `pollOnce()` will reload stale data.

## Steps to Reproduce

1. Navigate to the Today, Inbox, or All Lists view (must be a cached view)
2. Delete a task by swiping or using the delete action
3. Wait for the undo toast to commit (or let it expire)
4. Observe: the deleted task reappears in the list within 1–2 seconds (when `pollOnce()` completes)

## Expected Behavior

- The deleted task should not reappear after deletion
- After any mutation (create/update/delete/move), the view should reflect the current state from Google, not stale cached data

## Actual Behavior

- The deleted task reappears when `pollOnce()` returns stale cached data from the server
- The stale cache is served because `cachedView()` returns stale data immediately without refreshing
- The task only disappears permanently on the next cron-triggered cache refresh (up to 15 min later) or on pull-to-refresh

## Environment

- **Server Environment**: Production (Dreamhost shared hosting) and Development
- **Browser**: All browsers
- **Affected Views**: Today, Inbox, All Lists (any view using `cachedView()`)

## Technical Details

The mutation flow is:

1. Frontend: optimistically removes the task from `tasks.value`
2. Backend: `deleteTask()` → `markCachesStale($userId)` (sets `stale_at` on all `TaskViewCache` rows)
3. Frontend: `pollOnce()` → calls `fetchToday()` / `fetchInbox()` / `fetchAll()`
4. Backend: `cachedView()` → finds cache with `stale_at` set → `isFresh()` returns false → enters stale branch → returns stale payload (still contains the deleted task)
5. Frontend: `tasks.value = result` → deleted task reappears

The `pollOnce()` call does **not** pass `forceRefresh: true` — only `forceRefreshCurrentView()` (pull-to-refresh) does. So after a mutation, `pollOnce()` always gets stale data back.

### Affected code paths

All mutation handlers in `Index.vue` that call `pollOnce()` after `taskCache.invalidateForList()`:
- `removeTask()` (line ~2147)
- `completeTask()` (line ~325)
- `bulkComplete()` (line ~878)
- `bulkDelete()` (line ~916)
- `bulkMove()` (line ~1039)
- `updateTask()` and `moveTaskToList()` also call `markCachesStale()` on the backend

## Root Cause

The `cachedView()` stale-cache path returns cached data without refreshing, and `pollOnce()` does not use `forceRefresh=true`. After `defer()` was removed (commit 346b29d), there is no mechanism to refresh the cache between cron runs when a mutation marks it stale.

## Solution

Two possible approaches (choose one or combine):

### Option A: Force-refresh after mutations (frontend)

Change `pollOnce()` calls after mutations to use `forceRefreshCurrentView()` instead, or add a `forceRefresh` parameter to `pollOnce()`. This forces the server to re-fetch from Google and update the cache.

**Pros**: Simple, targeted fix. Cache is always fresh after a mutation.
**Cons**: Each mutation triggers a full Google API round-trip (adds ~1–3s latency on production).

### Option B: Skip pollOnce after mutations (frontend)

Since mutations already apply optimistic UI updates, and the backend has already confirmed the action succeeded (the axios call completed), there's no need to re-fetch. Simply skip `pollOnce()` after mutations and only invalidate the client-side `taskCache`. The next navigation or pull-to-refresh will fetch fresh data.

**Pros**: Fastest UX — no round-trip after mutation. Simpler code.
**Cons**: The view may be slightly out of sync if other changes happened on Google's side.

### Option C: Re-add defer with timeout guard (backend)

Re-add `defer()` but wrap it in a timeout or check for `fastcgi_finish_request()` availability so it only runs on compatible hosting. Fall back to no-defer on shared hosting.

**Pros**: Transparent fix, stale cache is refreshed automatically.
**Cons**: More complex, may still block on Dreamhost.

### Option D: Surgical cache patching (recommended)

Instead of re-fetching or invalidating, **patch the cache in-place** to reflect exactly what changed. This is how world-class apps (Linear, Todoist, Notion) handle mutations — they never re-fetch what they already know.

#### Frontend (client-side `taskCache` + reactive `tasks` ref)

After a mutation succeeds:
- **Delete**: Remove the task from `tasks.value` (already done via optimistic UI) and from `taskCache` entries for all affected views (today, inbox, all, the specific list).
- **Create**: Insert the new task (returned by the API) into `tasks.value` and into the relevant `taskCache` entries.
- **Update**: Replace the task in `tasks.value` (already done) and in `taskCache` entries with the updated version returned by the API.
- **Move**: Remove the task from the source list's `taskCache` entry, insert into the destination list's entry, and update `tasks.value`.
- **Complete/Uncomplete**: Update the task's status in `tasks.value` and in all `taskCache` entries that contain it.

Remove `pollOnce()` calls from all mutation handlers — the optimistic UI + cache patch is the source of truth until the next natural refresh.

#### Backend (server-side `TaskViewCache`)

After a mutation succeeds, instead of `markCachesStale()`, surgically patch the stored `TaskViewCache` payload:
- **Delete**: Decode the cached payload, filter out the deleted task ID, re-encode and save with a fresh `computed_at`.
- **Create**: Decode, insert the new task into the appropriate position (respecting sort order), re-encode and save.
- **Update**: Decode, find and replace the task by ID, re-encode and save.
- **Move**: Patch both the source and destination view caches.

This avoids marking the cache stale entirely — the cache remains fresh and accurate. The cron still runs as a safety net for eventual consistency (catching changes made directly in the Google Tasks app).

#### Why this is the right approach

- **No round-trip to Google** after mutations — instant UX.
- **No stale data** — the cache always reflects reality.
- **No polling** — mutations are self-contained; the cache is updated as a side effect.
- **Cron is a safety net**, not the primary refresh mechanism. It catches external changes (e.g., tasks added via the Google Tasks mobile app) but is not relied upon for in-app mutations.

**Recommended**: Option D — surgical cache patching on both frontend and backend. This is the world-class approach.

## Reference Documents

- [US-044 — Fast Today, Inbox, and All Tasks Views](../user-stories/US-044-fast-today-inbox-all-views.md) — introduced `cachedView()`
- [US-037 — Instant List Switch](../user-stories/US-037-instant-list-switch-cache-first-sync.md) — client-side `taskCache`

## Technical References

- `TasksController::cachedView()` — `app/Http/Controllers/TasksController.php` (line ~393)
- `TasksController::markCachesStale()` — `app/Http/Controllers/TasksController.php` (line ~436)
- `TaskViewCache` model — `app/Models/TaskViewCache.php` (payload storage + `putCache()`)
- `removeTask()` — `resources/js/Pages/Tasks/Index.vue` (line ~2125)
- `completeTask()` — `resources/js/Pages/Tasks/Index.vue` (line ~315)
- `pollOnce()` — `resources/js/Pages/Tasks/Index.vue` (line ~1318) — to be removed from mutation handlers
- `forceRefreshCurrentView()` — `resources/js/Pages/Tasks/Index.vue` (line ~1252)
- `useTaskCache` composable — `resources/js/composables/useTaskCache.js` — needs patch methods
- Commit that removed `defer()`: 346b29d

## Testing

- [x] Unit test added/updated — 11 PHPUnit tests in `TaskViewCachePatchTest.php`
- [x] Integration test added/updated — `test_delete_mutation_does_not_return_stale_data` regression test
- [ ] Manual testing completed — T-8.5 pending
- [x] Regression testing completed — DEF-002 regression test verifies delete → cache patch → no stale data

## Clarifying Questions

- **Q**: Which solution approach do you prefer?
- **A**: Option D — surgical cache patching. This is what world-class apps do.
- **Date**: 2026-03-29
- **Q**: Should the fix also cover individual list views?
- **A**: Yes — all views should be covered (Today, Inbox, All, individual lists).
- **Date**: 2026-03-29

## Notes

- This is a regression from commit 346b29d which removed `defer()` from the stale-cache path to fix Dreamhost response blocking.
- The bug also affects the Dashboard controller (`DashboardController::stats()`), though it's less noticeable since dashboard mutations are rare.
- The `forceRefreshCurrentView()` function (pull-to-refresh) correctly passes `forceRefresh: true` and is not affected by this bug — it always gets fresh data.

## Acceptance Verification

- [x] Actual Behavior now matches Expected Behavior
- [ ] All items in the Testing Checklist above are completed (manual QA pending)
- [x] Steps to Reproduce no longer produce the defect

## History

- 2026-03-29 - Created (regression from defer() removal for Dreamhost compatibility)
- 2026-03-30 - Implemented Option D: surgical cache patching on frontend (`syncAfterMutation` in `useTaskCache.js`, `syncCacheAfterMutation` replacing all `pollOnce()` in `Index.vue`) and backend (`removeTaskFromCaches`, `upsertTaskInCaches` in `TaskViewCache.php`, surgical patches in `TasksController`). 11 PHPUnit tests passing.
