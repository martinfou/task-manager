---
template_version: 1.1.0
last_updated: 2026-03-28
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-045 — Custom Task List Ordering

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done
**Priority**: 🟠 High
**Story Points**: 8
**Created**: 2026-03-28
**Updated**: 2026-03-28
**Assigned Sprint**: Backlog

## Description

The Google Tasks API returns task lists in its own default order, which the user cannot control. For users who rely on a primary "In" list for daily capture, this list may appear buried in the sidebar among other lists. There is no way to pin, reorder, or group lists — the sidebar always reflects Google's arbitrary ordering.

This story adds **persistent, user-controlled task list ordering** with three capabilities:

1. **Pin to top**: Designate one or more lists to always appear at the top of the sidebar (primary use case: pin the "In" list).
2. **Drag-to-reorder**: Reorder remaining (unpinned) lists via drag-and-drop on both desktop and mobile.
3. **Auto-sort options**: Optionally sort unpinned lists alphabetically (A→Z or Z→A) instead of manual ordering, for users who prefer automatic organisation.

Since the Google Tasks API has no concept of list ordering, this is stored **entirely locally** in a per-user database table. The frontend applies the custom order on top of the API response, so the feature works transparently without modifying any Google data.

## User Story

As a power user with many task lists, I want to **pin my most-used list to the top** and **reorder or auto-sort the rest**, so that the sidebar reflects my personal workflow rather than Google's default ordering.

## Acceptance Criteria

- [x] **Pin to top**: The user can pin one or more lists to the top of the sidebar. Pinned lists always appear above unpinned lists, in their own pinned order.
- [x] **Auto-pin on first use**: On first use (no custom ordering saved), the list matching a configurable name (default: "In") is automatically pinned to position 1. If no match, the first list is pinned. The user can change or remove this pin later.
- [x] **Drag-to-reorder (desktop)**: In the sidebar, the user can drag lists to reorder them. Pinned and unpinned sections are reorderable independently. Order persists across sessions.
- [x] **Drag-to-reorder (mobile)**: On mobile, a dedicated "Organise Lists" screen (accessible from the sidebar) allows reordering via drag handles. The main sidebar/drawer reflects the saved order.
- [x] **Auto-sort unpinned lists**: The user can toggle an option to auto-sort unpinned lists alphabetically (A→Z). When enabled, manual drag reorder for unpinned lists is disabled and new lists from Google automatically slot into the correct position.
- [x] **Persistence**: Custom list order is stored server-side (database) per user. The order survives browser clears, device switches, and app restarts.
- [x] **New list handling**: When Google returns a list not yet in the user's custom order (e.g., user created a new list in Google Tasks), it appears at the bottom of the unpinned section (or in sorted position if auto-sort is enabled).
- [x] **Deleted list handling**: When a list present in the custom order no longer exists in the Google API response (user deleted it externally), it is silently removed from the stored order — no errors, no phantom entries.
- [x] **Order applies everywhere**: The custom list order is applied consistently in the sidebar, the mobile list drawer, and the task composer list selector. The "All Lists" aggregate view is **not** affected — it sorts tasks by priority and due date instead.
- [x] **No Google API modification**: The feature does not call any Google API endpoints to reorder lists. All ordering is local/server-side.
- [x] **Performance**: Applying custom order adds < 5 ms to any view render. The order is fetched once on session init and cached client-side.
- [x] **Automated tests**: At least 6 tests covering: pin to top, reorder persistence, new list insertion, deleted list cleanup, auto-sort, and order application to views.

## Business Value

The sidebar is the user's primary navigation. When it doesn't reflect their workflow — when their most important list is buried — every interaction starts with scanning and scrolling. For a "fast and trustworthy" task manager, the sidebar should feel like the user's personal workspace, not a random list from an API. Pinning the primary capture list ("In") to the top removes friction from the most frequent action: opening the inbox. Drag-to-reorder gives power users control over their full workflow hierarchy.

## Technical Requirements

### Database

- **New table**: `task_list_order` with columns:
  - `id` (primary key)
  - `user_id` (foreign key to users, indexed)
  - `google_list_id` (string, the Google Tasks list ID)
  - `position` (integer, 0-based sort order)
  - `pinned` (boolean, default false)
  - `created_at`, `updated_at`
  - Unique constraint: `(user_id, google_list_id)`
  - Index: `(user_id, pinned, position)` for efficient ordered retrieval

- **New model**: `TaskListOrder` with methods:
  - `getOrderedListIds(int $userId): array` — returns Google list IDs in custom order (pinned first, then unpinned by position)
  - `applyOrder(int $userId, array $googleLists): array` — takes raw Google API list array, returns reordered array with `pinned` flag injected
  - `saveOrder(int $userId, array $orderedIds, array $pinnedIds): void` — bulk upsert positions
  - `togglePin(int $userId, string $googleListId): void` — pin/unpin a list
  - `cleanupStale(int $userId, array $currentGoogleListIds): void` — remove entries for lists no longer in Google

### API Endpoints

- `GET /api/tasks/list-order` — returns the user's custom list order (positions + pinned flags)
- `PUT /api/tasks/list-order` — bulk save reordered list (array of `{id, position, pinned}`)
- `PATCH /api/tasks/list-order/{listId}/pin` — toggle pin for a single list

### User Preferences

- **Auto-sort preference**: Add `task_list_auto_sort` column to `users` table (enum: `null`, `alpha_asc`, `alpha_desc`). When set, unpinned lists are sorted automatically instead of using stored positions.

### Frontend

- **Sidebar reorder**: Use a lightweight drag library (e.g., `vuedraggable` / `@vueuse/integrations` Sortable) for desktop drag-to-reorder within the sidebar `<aside>` component.
- **Mobile reorder sheet**: A dedicated bottom sheet or full-screen modal with drag handles for touch reordering.
- **Pin action**: Right-click context menu (desktop) or long-press (mobile) on a list to pin/unpin. Visual indicator (pin icon) for pinned lists.
- **Client-side order cache**: Store the order in a reactive ref on session init. Apply it as a computed property over `taskLists` so all consumers (sidebar, drawer, composer selector, All Lists view) automatically respect the order.
- **Divider**: Visual separator between pinned and unpinned sections in the sidebar.

### Aggregate View Impact

- **All Lists view**: The `TaskViewAggregator::allListsTasks()` method currently sorts by `taskListTitle` alphabetically. This should be changed to sort by **priority (p1→p4) then due date (soonest first)** — custom list order does **not** apply to the All Lists view.
- **Cache compatibility**: Custom order is applied **after** cache retrieval — the cached payloads don't need to change. Order is a presentation-layer concern.

## Reference Documents

- [US-009 — Views: Today, Inbox, Multi-List](US-009-views-today-inbox-lists.md) — sidebar and list navigation
- [US-037 — Instant List Switch](US-037-instant-list-switch-cache-first-sync.md) — client-side cache for list switching
- [US-044 — Fast Views](US-044-fast-today-inbox-all-views.md) — server-side view caching
- [US-023 — All Tasks Across All Lists](US-023-all-tasks-all-lists.md) — All Lists aggregate view

## Technical References

- Sidebar rendering: `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` — desktop sidebar (lines ~2869–2918), mobile drawer (lines ~4044–4050)
- List fetch: `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` — `fetchTaskLists()` (lines ~1124–1139)
- Task lists endpoint: `apps/google-tasks/app/Http/Controllers/TasksController.php` — `taskLists()` (lines 41–48)
- Google API client: `apps/google-tasks/app/Services/Google/GoogleTasksClient.php` — `listTaskLists()` (line 20)
- All Lists aggregator: `apps/google-tasks/app/Services/Google/TaskViewAggregator.php` — `allListsTasks()` (lines 73–111)
- Composer list selector: `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` — list selector in add-task composer (lines ~3003–3009)

## Dependencies

- [US-009](US-009-views-today-inbox-lists.md) ✅ — sidebar and list navigation
- [US-044](US-044-fast-today-inbox-all-views.md) ✅ — server-side view caching (cache compatibility)

## Clarifying Questions

*AI: Before starting implementation, ask the user clarifying questions. Document questions and answers here after the user responds.*

- **Q**: Should the "In" list be auto-pinned on first use, or should the user manually pin it?
- **A**: Auto-pinned on first use.
- **Date**: 2026-03-28
- **Q**: For mobile reordering, do you prefer a dedicated "Organise Lists" screen accessible from the sidebar, or inline drag handles directly in the drawer?
- **A**: Dedicated "Organise Lists" screen.
- **Date**: 2026-03-28
- **Q**: Should auto-sort (alphabetical) be an option, or is manual reorder + pin sufficient for your needs?
- **A**: Yes, auto-sort should be an option.
- **Date**: 2026-03-28
- **Q**: In the "All Lists" aggregate view, should task grouping follow the custom list order, or remain alphabetical?
- **A**: No — All Lists view should sort by priority and due date, not custom list order.
- **Date**: 2026-03-28

## Notes

- The Google Tasks API (`tasks.tasklists.list`) does **not** support custom ordering — lists are returned in Google's internal order. All ordering in this feature is purely local.
- The feature is designed as a **presentation-layer concern**: the cached view payloads (US-044) don't need to change. Custom order is applied after data retrieval, keeping the cache layer simple.
- Pinning is the highest-value sub-feature. Even without drag-to-reorder, just pinning the "In" list to the top would solve the primary pain point. Consider shipping pin-to-top first as an incremental delivery.
- The `task_list_order` table is lightweight (one row per list per user, typically 5–15 rows) and can be fetched in a single query on session init.
- Drag-to-reorder on mobile is notoriously tricky with scroll containers. Using a dedicated reorder sheet (separate from the main sidebar) avoids scroll-vs-drag conflicts.

## Acceptance Verification

- [x] All acceptance criteria above verified as met
- [x] Each criterion tested or inspected and confirmed

## History

- 2026-03-28 - Created (user wants "In" list pinned to top + custom ordering for remaining lists; Google API has no ordering support, requires local storage)
- 2026-03-28 - Implementation started: migrations, TaskListOrder model (11 tests), API endpoints (GET/PUT/PATCH), taskLists() applies custom order, AllLists view sort changed to priority+due, vuedraggable installed, useListOrder composable, TaskListOrganiseSheet component, desktop sidebar with pinned/unpinned sections + context menu, mobile drawer with sections + organise button, i18n EN/FR, purge on disconnect
- 2026-03-28 - Marked ✅ Done (ACs verified; `TaskListOrderTest` 11 feature tests)
