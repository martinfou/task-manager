---
template_version: 1.1.0
last_updated: 2026-03-22
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-037 — Instant List Switch (Cache-First, Background Sync)

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🟠 High  
**Story Points**: 8  
**Created**: 2026-03-22  
**Updated**: 2026-03-22  
**Assigned Sprint**: [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md)

## Description

Make **changing the active task list feel immediate**: when I click a list (or equivalent navigation), I should **see tasks for that list right away** instead of waiting on a full round-trip to Google on every switch.

Implementation may combine **client-side persistence** (e.g. per-list task snapshots), **server-side caching**, **stale-while-revalidate**, and/or **prefetch**—the team chooses the smallest approach that meets the perceived-performance bar and stays consistent with sync trust ([US-008](US-008-google-tasks-sync-engine.md), [US-019](US-019-api-error-retry-ux.md)).

## User Story

As a user who jumps between several Google task lists, I want the UI to **show each list’s tasks almost instantly** when I select it, so that **triage stays fast** while I still trust that the app **catches up with Google** shortly after.

## Acceptance Criteria

- [ ] **Instant paint on revisit**: After a list has been loaded at least once in the session (or within a defined cache window), selecting that list again shows **the last known tasks immediately** (no empty list flash solely due to network latency).
- [ ] **Background reconciliation**: A **fresh fetch from Google** runs after showing cached data (or in parallel where safe), and the UI **updates** when newer data arrives **without** surprising jumps (e.g. preserve scroll/selection where reasonable, or document deliberate reset).
- [ ] **First visit / cold cache**: When there is **no** cache for a list, the UI shows a **clear loading state** and completes load as today—or faster if prefetch is implemented—without blocking other lists’ cached views.
- [ ] **Errors and rate limits**: Failed refresh or **429** / partial failure is handled with messaging or retry patterns **consistent** with existing Tasks error UX ([US-019](US-019-api-error-retry-ux.md)); cached data may remain visible with an **explicit “couldn’t refresh”** or subtle indicator (product choice documented).
- [ ] **Trust boundaries**: On **disconnect / purge / token loss**, cached task data for that user is **not** wrongly shown (aligned with [US-021](US-021-disconnect-purge-logging.md)); behavior is documented briefly in app or ops notes if non-obvious.
- [ ] **Today, Inbox, All lists**: Behavior is **defined** for aggregate views ([US-009](US-009-views-today-inbox-lists.md), [US-023](US-023-all-tasks-all-lists.md))—either same cache-first pattern, or a short rationale if a view always requires live aggregation.

## Business Value

List switching is a **high-frequency** action during triage. Perceived lag undermines the product’s **“fast and trustworthy”** positioning even when data is eventually correct. Cache-first display plus background sync improves **focus and throughput** without asking users to trade accuracy for speed.

## Technical Requirements

- **Perceived latency**: Target **subjective instant** switch for cached lists (e.g. paint under ~100 ms on typical devices; exact metric chosen during implementation).
- **Consistency**: Task mutations (complete, edit, move) should **invalidate or update** the relevant cache entries so users do not see **obviously stale** state after their own actions.
- **Storage**: If using browser storage, respect **size limits**, **private browsing**, and **multi-account** edge cases; avoid unbounded growth.
- **Security**: Cached payloads must remain **scoped to the authenticated user** and cleared on logout/disconnect as required by policy.

## Reference Documents

- [US-008 — Google Tasks Sync Engine](US-008-google-tasks-sync-engine.md) — source of truth and sync behavior
- [US-009 — Multi-list navigation](US-009-views-today-inbox-lists.md) — navigation surfaces
- [US-019 — API error and retry UX](US-019-api-error-retry-ux.md) — error patterns
- [US-021 — Disconnect and purge](US-021-disconnect-purge-logging.md) — cache lifecycle on disconnect

## Technical References

- Route: `GET /tasks/data/{taskList}/tasks` — `tasks.data.tasks` in `apps/google-tasks/routes/web.php`
- Controller: `TasksController::tasks` — `apps/google-tasks/app/Http/Controllers/TasksController.php`
- Client shell: `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` (list selection, polling, load flows)

## Dependencies

- [US-008](US-008-google-tasks-sync-engine.md) — sync and Google API access
- [US-009](US-009-views-today-inbox-lists.md) — list navigation UX

## Clarifying Questions

*AI: Before starting implementation, ask the user clarifying questions. Document questions and answers here after the user responds.*

## Notes

- A **time-boxed spike** (0.5–1 day) may be useful to pick among IndexedDB + SWR, Laravel cache + ETag, or prefetch-on-hover/focus—document the chosen approach in the PR or a short `docs/` note if it affects operators.
- Align with **service worker** / PWA direction ([US-022](US-022-pwa-install-phase.md)) only if it reduces duplication; avoid two competing caches without a clear strategy.

## Acceptance Verification

**Complete before marking status as Done.** Verify each acceptance criterion is met, then mark with `[x]`.

- [ ] All acceptance criteria above verified as met
- [ ] Each criterion tested or inspected and confirmed

## History

- 2026-03-22 - Created (product request: faster list switching; cache-first + background sync)
- 2026-03-22 - Assigned to [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md) (full backlog pulled into active sprint)
