---
template_version: 1.1.0
last_updated: 2026-03-23
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-040 — Dashboard Pre-Compute and Cache for Instant Load

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done
**Priority**: 🟡 Medium
**Story Points**: 5
**Created**: 2026-03-23
**Updated**: 2026-03-23
**Assigned Sprint**: [Sprint 7](../../sprints/sprint-07-google-tasks-polish-and-performance.md)

## Description

The dashboard currently computes stats **on every page visit** by fetching all tasks (including completed) from the Google Tasks API, then bucketing, aggregating, and deriving insights client-side via the `/dashboard/stats` AJAX endpoint. This makes the dashboard feel slow — the user sees an empty/loading state for several seconds while the API call and computation finish.

Since task data changes infrequently (a few creates/completes per day), we can **pre-compute stats on a schedule** (e.g. every 15–30 minutes, or after task mutations) and serve cached results instantly. The existing `dashboard_stats_cache` table already stores computed stats; this story extends it with **scheduled refresh** and **mutation-triggered invalidation** so the dashboard loads instantly from cache and only falls back to live computation when the cache is stale or missing.

## User Story

As a user opening my dashboard, I want stats to **load instantly** without waiting for a Google API round-trip, so that the dashboard feels responsive and trustworthy.

## Acceptance Criteria

- [x] **Scheduled pre-compute**: A Laravel scheduled command (e.g. `dashboard:refresh-stats`) runs periodically (configurable, default every 30 minutes) and refreshes `dashboard_stats_cache` for users with an active Google Tasks connection
- [x] **Mutation-triggered invalidation**: When the user creates, completes, deletes, or moves a task via the app, the cache is marked stale (or eagerly refreshed in the background) so the next dashboard visit shows fresh data
- [x] **Instant cache-first load**: The `/dashboard/stats` endpoint returns cached stats immediately if available and fresh (< configured TTL). A `stale` flag or `computedAt` timestamp lets the frontend show "as of …" context
- [x] **Background refresh**: If cache exists but is stale, the endpoint returns the stale cache immediately and triggers an async refresh (queue job or deferred process). The frontend can optionally poll or show a "refreshing…" indicator
- [x] **Fallback**: If no cache exists (new user, first visit), the endpoint falls back to live computation (current behavior) and populates the cache for subsequent visits
- [x] **Multi-range support**: Cache stores stats for the user's last-requested range (or all common ranges: 7, 14, 30, 90 days) so switching ranges in the UI doesn't require a live re-fetch
- [x] **No regression**: Disconnected/offline fallback to cached stats still works as before
- [x] **Automated tests**: At least 3 tests covering scheduled refresh, stale-cache serving, and mutation invalidation

## Business Value

A fast dashboard builds trust and encourages daily use. The current multi-second load discourages users from checking their productivity data, undermining the value of the charts and insights built in US-036.

## Technical Requirements

- **Scheduled command**: `app/Console/Commands/RefreshDashboardStats.php` registered in `routes/console.php` or `Kernel` schedule. Should handle rate-limiting gracefully (skip user if Google API 429).
- **Cache invalidation**: Hook into existing task mutation flows in `TasksController` (create, update, delete, move, complete). Can use a lightweight `DashboardStatsCacheInvalidated` event or direct `dashboard_stats_cache` touch.
- **Schema change** (if needed): Add `stale_at` or `range` column to `dashboard_stats_cache`, or store multiple range entries per user.
- **Queue**: Background refresh should use Laravel's queue (or `dispatch_after_response`) to avoid blocking the HTTP response.
- **Rate-limit awareness**: The scheduled command should batch users and respect Google API quotas (the per-user daily limit is generous, but 429s should be caught and retried).

## Reference Documents

- [US-036 — Dashboard Productivity Charts](US-036-dashboard-productivity-charts-and-insights.md) — original dashboard implementation
- [US-037 — Instant List Switch](US-037-instant-list-switch-cache-first-sync.md) — cache-first pattern precedent

## Technical References

- `apps/google-tasks/app/Services/Google/DashboardStatsService.php` — `compute()` and `getCached()`
- `apps/google-tasks/app/Http/Controllers/DashboardController.php` — `stats()` endpoint
- `apps/google-tasks/app/Models/DashboardStatsCache.php` — Eloquent model
- `apps/google-tasks/database/migrations/2026_03_22_100001_create_dashboard_stats_cache_table.php`
- `apps/google-tasks/app/Http/Controllers/TasksController.php` — task mutation endpoints

## Dependencies

- [US-036](US-036-dashboard-productivity-charts-and-insights.md) ✅ — dashboard and cache table already exist

## Notes

- The current `DashboardStatsService::compute()` already writes to `dashboard_stats_cache` after every live computation, so the cache infrastructure is in place — this story adds the **proactive refresh** and **invalidation** layers.
- For single-user or low-traffic deployments (like DreamHost shared hosting), the scheduled command can run via cron. For multi-user, consider queuing individual user refreshes.
- Consider storing stats for all 4 ranges (7/14/30/90) in a single JSON blob or as separate cache rows keyed by `(user_id, range)`.

## Acceptance Verification

- [x] All acceptance criteria above verified as met
- [x] Each criterion tested or inspected and confirmed

## History

- 2026-03-23 - Created
- 2026-03-23 - Implemented: migration (range_days + stale_at), RefreshDashboardStats command, cache-first DashboardController with deferred refresh, mutation invalidation in TasksController, multi-range DashboardStatsService, 8 new cache tests
- 2026-03-23 - Marked ✅ Done — build green, Pint clean, 86 PHP tests + 64 JS tests pass
