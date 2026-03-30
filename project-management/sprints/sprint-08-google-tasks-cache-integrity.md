---
template_version: 1.1.0
last_updated: 2026-03-30
compatible_with: [product-backlog]
---

# Sprint 8: Google Tasks — Cache Integrity

[← Back to Product Backlog](../backlog/product-backlog.md)

**Sprint status**: **Active** — started 2026-03-29 after [Sprint 7](sprint-07-google-tasks-polish-and-performance.md) closed.

**Sprint Goal**: Fix the stale cache regression (DEF-002) with surgical cache patching so that mutations are reflected instantly without re-fetching from Google.

**Duration**: 2026-03-29 — 2026-04-12 (2 weeks)
**Team Velocity (reference)**: **42** points ([Sprint 7](sprint-07-google-tasks-polish-and-performance.md)); targeting **~3–5** pts this sprint (focused, quality-first — retro guidance)
**Sprint Planning Date**: 2026-03-29
**Sprint Review Date**: *TBD*
**Sprint Retrospective Date**: *TBD*

**Depends on**: [Sprint 7](sprint-07-google-tasks-polish-and-performance.md) ✅

---

## Sprint planning record (2026-03-29)

| Step | Result |
|------|--------|
| Backlog metrics | 50 items total; 48 Done, 2 To Do; 257 total pts (249 completed) |
| Definition of Ready | DEF-002 reviewed — root cause identified, solution documented (Option D: surgical cache patching), all technical refs included |
| Capacity | **3** pts (DEF-002) + RI-007 (process) |
| Branching | [ADR-002](../architecture-decision-records/ADR-002-branching-strategy.md) |

### Decisions made at planning

- **US-043** (scope audit tool): **Dropped** — never refined, no story file, no longer needed.
- **RI-004** (Index.vue playbook): **Dropped** — carried 3 sprints with no action.
- **RI-005** (429 runbook): **Dropped** — rate limiting handled in code, separate runbook not needed.
- **RI-006** (Playwright E2E): **Dropped** — carried 2 sprints with no action.
- **RI-007** (infrastructure removal impact analysis): **Included** — apply during DEF-002 fix.

### Implementation order

1. [DEF-002](../backlog/defects/DEF-002-deleted-task-reappears-from-stale-cache.md) — Surgical cache patching (frontend + backend)
2. RI-007 — Verify impact analysis checklist is satisfied by DEF-002 implementation

---

## Sprint Overview

**Focus Areas**: Cache integrity · Mutation consistency · Quality

**Key Deliverables**:
- Surgical cache patching on both frontend (`useTaskCache`) and backend (`TaskViewCache`) after mutations
- Remove `pollOnce()` from all mutation handlers
- Tests that verify the full mutation → cache patch → UI consistency cycle

**Risks**: Surgical patching of server-side cache payloads requires careful handling of sort order and view-specific filtering (e.g., a task created with a future due date should not appear in Today cache).

---

## Defects (this sprint)

| ID | Title | Points | Status |
|----|-------|--------|--------|
| [DEF-002](../backlog/defects/DEF-002-deleted-task-reappears-from-stale-cache.md) | Deleted/Modified Task Reappears from Stale Cache | 3 | ✅ |

**Total Story Points**: **3**

### Retrospective improvements

| ID | Description | Status |
|----|-------------|--------|
| [RI-007](../backlog/retrospective-improvements/RI-007-infrastructure-removal-impact-analysis.md) | Infrastructure removal impact analysis — apply during DEF-002 fix | ✅ |

---

## Tasks

### DEF-002 — Surgical cache patching

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-8.1 | **Frontend: add patch methods to `useTaskCache`** — `syncAfterMutation(navMode, listId, tasks, affectedListId)` saves current tasks into active view cache, invalidates other views | `resources/js/composables/useTaskCache.js` | ✅ |
| T-8.2 | **Frontend: replace `pollOnce()` with cache patches in mutation handlers** — all 20 `pollOnce()` calls replaced with `syncCacheAfterMutation()` in every mutation handler | `resources/js/Pages/Tasks/Index.vue` | ✅ |
| T-8.3 | **Backend: surgical cache patching** — `removeTaskFromCaches()`, `upsertTaskInCaches()`, `extractTaskId()` added to `TaskViewCache`. `TasksController` mutation endpoints call surgical patches instead of `markCachesStale()`. | `app/Models/TaskViewCache.php`, `app/Http/Controllers/TasksController.php` | ✅ |
| T-8.4 | **Tests: mutation → cache → UI cycle** — 11 PHPUnit tests covering remove (today/inbox/all/multi-view/noop/other-user), upsert (today/inbox/completed-removal/absent-noop), and DEF-002 regression | `tests/Feature/TaskViewCachePatchTest.php` | ✅ |
| T-8.5 | **Manual QA**: Delete task → verify no reappearance. Create task → verify appears in relevant views. Move task → verify source/destination caches updated. Complete task → verify status change in cache. | — | ⭕ |

### RI-007 — Infrastructure removal impact analysis

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-8.6 | **Verify DEF-002 fix includes regression test** that would have caught the original defer-removal issue (mutation → stale data returned) — `test_delete_mutation_does_not_return_stale_data` | `tests/Feature/TaskViewCachePatchTest.php` | ✅ |

---

## Sprint Summary

**Sprint Burndown**: 5/6 tasks complete (T-8.1–T-8.4, T-8.6 ✅). T-8.5 (Manual QA) pending user testing.

**Sprint Review Notes**: *TBD*

**Sprint Retrospective Notes**: *TBD*

---

## Status Values

- ⭕ **To Do** · ⏳ **In Progress** · ✅ **Done**

---

## History

- 2026-03-29 — Sprint 8 planning: DEF-002 selected (3 pts); US-043 dropped, RI-004/005/006 dropped; RI-007 included
- 2026-03-29 — Activated after Sprint 7 review + retrospective
- 2026-03-30 — T-8.1 through T-8.4 and T-8.6 completed: surgical cache patching implemented on frontend (`syncAfterMutation`) and backend (`removeTaskFromCaches`, `upsertTaskInCaches`); 11 PHPUnit tests passing; all `pollOnce()` calls removed from mutation handlers
