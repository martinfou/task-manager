---
template_version: 1.1.0
last_updated: 2026-03-28
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-044 — Fast Today, Inbox, and All Tasks Views

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done
**Priority**: 🟠 High
**Story Points**: 8
**Created**: 2026-03-28
**Updated**: 2026-03-28
**Assigned Sprint**: [Sprint 7](../../sprints/sprint-07-google-tasks-polish-and-performance.md)

## Description

Switching to Today, Inbox, or All Tasks should feel **instant** — on par with switching between regular lists. Currently, **Today** and **All Tasks** hit the Google API with an N+1 pattern (1 call for task lists + 1 call per list) on every request, with **no server-side caching**. Even though US-037 added a client-side in-memory cache with background refresh, the first visit in a session is slow, and the client-side cache invalidation after mutations (create/complete/delete/move) is **not wired up**, so aggregate views can show stale data until the next poll cycle.

This story adds a **server-side cache layer** for the three aggregate views (Today, Inbox, All) — similar to US-040's dashboard cache approach — plus **proper client-side invalidation** after task mutations, so these views paint instantly on every visit and stay fresh after user actions.

## User Story

As a user who relies on Today, Inbox, and All Tasks for daily triage, I want these views to **load instantly** (cached) and **stay accurate after my own edits**, so that triage feels fast and trustworthy.

## Acceptance Criteria

- [x] **Server-side caching for aggregate views**: Today, Inbox, and All Tasks responses are cached per user on the server (database or file cache). Subsequent requests within the TTL return cached data without hitting the Google API.
- [x] **Cache-first with stale indicator**: The endpoint returns cached data immediately with a `cachedAt` timestamp so the frontend shows "as of …". Background refresh of stale cache is handled by the `views:refresh-cache` GitHub Actions cron (every 15 min). Note: `defer()` was originally used for in-request background refresh but removed (commit 346b29d) because it blocks HTTP responses on Dreamhost shared hosting.
- [x] **Pull-to-refresh forces live fetch**: The user can pull down (mobile) or click a refresh action (desktop) to bypass the cache and trigger an immediate live fetch from Google, updating the cache.
- [x] **Mutation-triggered invalidation**: When the user creates, completes, deletes, moves, or edits a task through the app, the server-side aggregate view caches for that user are marked stale (or eagerly invalidated), so the next request triggers a fresh computation.
- [x] **Client-side invalidation wired up**: After task mutations in `Index.vue`, the client-side `useTaskCache` invalidation (`invalidateForList`) is called so that aggregate views (`today`, `inbox`, `all`) are cleared and refetched — no stale display until next poll.
- [x] **Cold-cache fallback**: If no cache exists (new user, first session, cache expired), the endpoint falls back to live Google API aggregation (current behavior) and populates the cache for subsequent visits.
- [x] **First paint under 200 ms (warm cache)**: With a warm server-side cache, the aggregate view endpoints respond in under 200 ms (measured at the server). Client-side cache-first pattern then makes the paint effectively instant on revisit within the same session.
- [x] **Today view accuracy**: Today's cache is time-sensitive (overdue tasks, tasks becoming due). The uniform 5-minute TTL is acceptable; the user can pull-to-refresh if needed. Midnight rollover (new day) should invalidate the Today cache.
- [x] **No regression**: Disconnect/purge clears server-side aggregate caches for the user. Error handling (429, partial list failure) remains consistent with existing UX ([US-019](US-019-api-error-retry-ux.md)).
- [x] **Automated tests**: At least 4 tests covering: warm cache serving, stale cache + background refresh, mutation invalidation, and cold-cache fallback.

## Business Value

Today, Inbox, and All Tasks are the **highest-traffic views** — users open them first every session to triage. A multi-second wait (especially for Today and All, which aggregate across all lists) makes the app feel sluggish and undermines the "fast and trustworthy" positioning established by US-037's instant list switch. Server-side caching eliminates the N+1 Google API round-trips and makes these views load as fast as a single cached list.

## Technical Requirements

- **Server-side cache**: Extend the approach from US-040 (dashboard stats cache) or use Laravel's cache system (`Cache::remember` with per-user, per-view keys). Store serialized task payloads for `today`, `inbox`, and `all` views per user.
- **TTL**: Configurable; default 5 minutes for all three views. Mutation invalidation resets the TTL. Pull-to-refresh bypasses TTL entirely.
- **Cache key scheme**: `tasks_view:{user_id}:{view}:{showCompleted}` — supports the `showCompleted` toggle for Inbox and All.
- **Background refresh**: Use `dispatch_after_response` or queue job (consistent with US-040 pattern) to refresh stale cache without blocking the HTTP response.
- **Client-side**: Wire `invalidateForList()` calls in `Index.vue` after successful task mutations (complete, delete, move, create, edit). The composable already supports clearing aggregate caches; it just needs to be called.
- **API call reduction**: With server-side caching, the N+1 Google API pattern only runs on cache miss/refresh, reducing API quota usage significantly for frequent visitors.

## Reference Documents

- [US-037 — Instant List Switch (Cache-First)](US-037-instant-list-switch-cache-first-sync.md) — client-side cache pattern
- [US-040 — Dashboard Pre-Compute and Cache](US-040-dashboard-precompute-cache.md) — server-side cache precedent
- [US-009 — Views: Today, Inbox, Multi-List](US-009-views-today-inbox-lists.md) — original view implementation
- [US-023 — All Tasks Across All Lists](US-023-all-tasks-all-lists.md) — All Tasks view
- [US-019 — API Error and Retry UX](US-019-api-error-retry-ux.md) — error handling patterns

## Technical References

- Aggregator: `apps/google-tasks/app/Services/Google/TaskViewAggregator.php` — Today (lines 15–51), All (lines 73–111)
- Controller: `apps/google-tasks/app/Http/Controllers/TasksController.php` — `todayView()`, `inboxView()`, `allListsView()` (lines 146–203)
- Client cache: `apps/google-tasks/resources/js/composables/useTaskCache.js` — `invalidateForList()` exists but is not called after mutations
- Client shell: `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` — fetch functions (lines 1146–1192), cache usage (lines 1287–1294)
- Mutation endpoints: `TasksController` — create, update, delete, move, complete

## Dependencies

- [US-037](US-037-instant-list-switch-cache-first-sync.md) ✅ — client-side cache infrastructure
- [US-009](US-009-views-today-inbox-lists.md) ✅ — aggregate view endpoints
- [US-023](US-023-all-tasks-all-lists.md) ✅ — All Tasks view

## Clarifying Questions

*AI: Before starting implementation, ask the user clarifying questions. Document questions and answers here after the user responds.*

- **Q**: Should server-side caching use the database (like US-040's `dashboard_stats_cache` table) or Laravel's cache driver (Redis/file)?
- **A**: Database table (`task_view_cache`) for consistency with US-040 pattern.
- **Date**: 2026-03-28
- **Q**: Is a 2-minute TTL for Today acceptable, or should it be shorter/longer?
- **A**: Up to 5 minutes stale is fine for all views. User can force refresh by pulling down.
- **Date**: 2026-03-28
- **Q**: Should this include prefetching (e.g. warm Today + All caches on login), or is on-demand caching sufficient?
- **A**: On-demand caching is sufficient. User can pull-to-refresh to force fresh data.
- **Date**: 2026-03-28

## Notes

- The biggest win is **server-side caching for Today and All Tasks** — these make N+1 API calls (1 + number of lists). A user with 10 lists makes 11 Google API calls just to see Today. With caching, most visits are zero API calls.
- **Inbox** is already fast (only 2 API calls — list of lists + default list tasks), but benefits from cache consistency and the client-side invalidation fix.
- The client-side `invalidateForList()` fix is small but high-impact — it ensures aggregate views refresh after mutations instead of showing stale data for up to 5 seconds (poll interval).
- Consider aligning the cache layer with the dashboard stats cache pattern from US-040 for consistency, or use Laravel's built-in cache if the payload shape is simpler (raw task arrays vs. computed stats).

## Acceptance Verification

- [x] All acceptance criteria above verified as met
- [x] Each criterion tested or inspected and confirmed

## History

- 2026-03-28 - Created (performance gap: aggregate views hit Google API N+1 on every request; no server cache; client invalidation not wired)
- 2026-03-28 - Implemented: `task_view_cache` table + `TaskViewCache` model, cache-first `cachedView()` in `TasksController` with `defer()` background refresh, mutation invalidation (`markCachesStale`), `forceRefresh` query param, client-side `invalidateForList()` wired to all 10+ mutation paths in `Index.vue`, `usePullToRefresh` composable with visual indicator, disconnect/purge via `GoogleTasksConnectionPurgeService`, 10 PHP tests + all 64 JS tests pass — marked ✅ Done
