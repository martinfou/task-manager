---
template_version: 1.1.0
last_updated: 2026-03-22
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-023 - All Tasks Across All Lists

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟠 High  
**Story Points**: 8  
**Created**: 2026-03-21  
**Updated**: 2026-03-22  
**Assigned Sprint**: [Sprint 5](../../sprints/sprint-05-google-tasks-ux-visibility.md) (closed)

## Description

Provide a single view that lists **tasks from every Google task list** the user has, so they can scan or triage work in one place without switching lists one by one. Complements [US-009](US-009-views-today-inbox-lists.md) (Today / Inbox / per-list), which does not aggregate all open tasks across lists.

## User Story

As a user with many lists, I want to see **all my tasks from all lists in one place**, so that I can review everything at a glance and not miss work scattered across lists.

## Acceptance Criteria

- [x] A dedicated navigation entry or view mode (e.g. **All lists** / **All tasks**) loads tasks from **every** task list returned by Google (same account as today).
- [x] Each task row shows **which list** it belongs to (clear label or badge), consistent with how Today shows list context where applicable.
- [x] Existing task actions still work from this view: complete, open details, delete, bulk selection (where already supported), and **new task** targets a defined default list (document behavior — e.g. same as Inbox default).
- [x] **Filters** (status, due, priority) and **list/board** toggle apply meaningfully in this view, or scope is documented (e.g. list filter narrows to one list within the global view).
- [x] **Performance and API limits**: loading does not routinely hit Google rate limits; document strategy (sequential fetch, caching, pagination, or “load more”) if the full aggregate is large.
- [x] **Empty state** when the user has no tasks across lists; **error/retry** if a subset of lists fails to load.
- [x] **Mobile**: usable with existing nav patterns (drawer / bottom nav).
- [x] **i18n**: English and French strings for the new view and any new empty/error copy.
- [x] **Default sort (phased — Question 13 / Clarifying Questions)**: **Phase 1** (this story **without** [US-027](US-027-consistent-dates-priority-across-views.md) global sort): tasks ordered **by list name** ascending, **then by task title** ascending (locale-aware / consistent with existing list sorting). **Phase 2** (**when US-027 ships**): **All tasks** **adopts** US-027’s **global Due first / Priority first** comparator and **sort control** pattern like **Inbox** / **per-list**; the Phase 1 **list → title** ordering for this view is **replaced**. Verify Phase 1 for US-023 **alone**; verify Phase 2 when US-027 is merged or in the same release.

## Business Value

Reduces context switching for power users and executives who maintain many lists; improves confidence that nothing is hidden in a list they did not open.

## Technical Requirements

- Server-side aggregation in `apps/google-tasks` (extend or parallel `TaskViewAggregator` / new endpoint) with tests.
- Reuse Tasks page patterns (Inertia + Vue) where possible; avoid duplicating business logic in the client only.

## Technical References

- `apps/google-tasks/app/Services/Google/TaskViewAggregator.php` (or successor)
- `apps/google-tasks/app/Http/Controllers/TasksController.php` — data routes
- `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` — `navMode` / views

## Dependencies

- [US-008](US-008-google-tasks-sync-engine.md)
- [US-009](US-009-views-today-inbox-lists.md)
- **Sort Phase 2**: [US-027](US-027-consistent-dates-priority-across-views.md) — global list sort + control on **All tasks** (replaces Phase 1 list→title when present)

## Clarifying Questions

*Document answers here after refinement.*

- **Q**: Should “all tasks” include **completed** tasks by default, or only active — match per-list behavior?
- **A**: **Only active** (incomplete / `needsAction`) **by default**; **do not** show completed until the user uses the same completion filter as elsewhere (align **default** with **per-list** behavior).
- **Date**: 2026-03-21

- **Q**: Default **sort** for **All tasks** — match **Today** ([US-027](US-027-consistent-dates-priority-across-views.md)) or a **simpler** v1 rule?
- **A**: **Simpler v1 rule** — **list name**, then **task title** (both ascending); **not** required to mirror Today’s due/priority behavior or sort UI. Richer ordering can be a **follow-up**.
- **Date**: 2026-03-22

- **Q**: **All tasks** sort: [US-027](US-027-consistent-dates-priority-across-views.md) **global** sort vs US-023 **list → title** — which wins when **both** exist?
- **A**: **Phased (option 3)** — **US-023** ships **list → title** first. **When US-027 lands**, **All tasks** **switches** to **global Due first / Priority first** + same sort **UI** as other list views; US-027 **supersedes** the simple sort for that view.
- **Date**: 2026-03-22

## Refinement (2026-03-22)

Session: [Backlog refinement — all user stories](../../sprints/backlog-refinement-session-2026-03-22.md).

| Definition of Ready | Met |
|---------------------|-----|
| Acceptance criteria specific & testable | ✓ |
| Dependencies identified | ✓ |
| Story points & priority | ✓ |
| No blocking clarifying questions | ✓ |
| Technical references | ✓ |
| User story format | ✓ |

**Ready for sprint planning**: Yes (in Sprint 5; DoR reaffirmed).

## Notes

- Distinct from **Today** (due-based) and **full-text search** (US-013): this is a **navigational aggregate** of list contents.
- **Sort**: **Phase 1** **list → title**; **Phase 2** with [US-027](US-027-consistent-dates-priority-across-views.md) — **global** sort (see Clarifying Questions).

## Acceptance Verification

- [x] All acceptance criteria above verified as met
- [x] Each criterion tested or inspected and confirmed

## History

- 2026-03-21 - Created (backlog)
- 2026-03-21 - Assigned to [Sprint 5](../../sprints/sprint-05-google-tasks-ux-visibility.md); status ⏳ In Progress
- 2026-03-21 - Clarified: All tasks view defaults to **active only**; completed via existing filter parity with per-list
- 2026-03-22 - Clarified: default sort **list name** then **task title** (v1); need not match Today ([US-027](US-027-consistent-dates-priority-across-views.md))
- 2026-03-22 - Clarified: **phased** sort — US-023 **list→title** first; **US-027** then **replaces** with **global** sort on **All tasks**
- 2026-03-22 - Backlog refinement (all stories): DoR checklist recorded; no scope change
- 2026-03-22 - Sprint 5 ✅: `GET /views/all` default `showCompleted=false`; client passes `showCompleted` from status filter; refetch on filter change (aligns incomplete default with per-list)
- 2026-03-22 - Sprint 5 sprint review: acceptance criteria and verification checklist marked complete
