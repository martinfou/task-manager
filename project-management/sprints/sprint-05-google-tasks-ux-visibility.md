---
template_version: 1.1.0
last_updated: 2026-03-22
compatible_with: [product-backlog]
---

# Sprint 5: Google Tasks — UX, Visibility, and Interaction

[← Back to Product Backlog](../backlog/product-backlog.md)

**Sprint Goal**: Deliver an **all-lists task view**, **stronger mobile Tasks shell** (progressive search, less collapsible chrome), **inline task details** (below the row), and **double-click to edit** — one cohesive pass on Tasks UX.

**Duration**: 2026-03-22 — 2026-04-05 (2 weeks planned); **committed scope finished 2026-03-22** (review/retro held same day per [sprint-review-process.md](../processes/sprint-review-process.md) early-completion rule).  
**Team Velocity**: **18** points delivered in [Sprint 4](sprint-04-google-tasks-quality-and-v2.md) committed scope; Sprint 5 delivered **24** points across four stories (**US-023**–**US-026**).  
**Sprint Planning Date**: 2026-03-21  
**Sprint Review Date**: **2026-03-22** — completed; notes below.  
**Sprint Retrospective Date**: **2026-03-22** — completed; notes below.  
**Sprint status**: **Closed** — committed scope [US-023](../backlog/user-stories/US-023-all-tasks-all-lists.md)–[US-026](../backlog/user-stories/US-026-double-click-edit-task.md) ✅. Follow-on: [Sprint 6](sprint-06-google-tasks-trust-commands-mobile.md).

**Backlog refinement**: [2026-03-22 — all user stories](backlog-refinement-session-2026-03-22.md) reaffirmed Definition of Ready for US-023–US-026 (and full ⭕ backlog); no change to committed Sprint 5 scope.

## Sprint planning record (2026-03-21)

**Prerequisites met**: [Sprint 4](sprint-04-google-tasks-quality-and-v2.md) closed; backlog items US-023–US-026 refined with acceptance criteria and technical references.

| Step | Result |
|------|--------|
| Backlog metrics | `./project-management/scripts/backlog-metrics.sh --stats` |
| Definition of Ready | US-023–US-026 reviewed; dependencies [US-008](../backlog/user-stories/US-008-google-tasks-sync-engine.md) / [US-009](../backlog/user-stories/US-009-views-today-inbox-lists.md) ✅ for US-023; clarifying questions may be resolved during sprint |
| Capacity | Solo / small team; **24** pts committed — monitor burndown; US-026 de-scoped last if needed |
| Branching | Per [ADR-002](../architecture-decision-records/ADR-002-branching-strategy.md): `feature/US-XXX-short-description` per story |

### Committed implementation order

1. [US-023](../backlog/user-stories/US-023-all-tasks-all-lists.md) — **All tasks across all lists** (API + `TaskViewAggregator` / routes; new nav mode; tests) — establishes data shape for global list.  
2. [US-024](../backlog/user-stories/US-024-task-details-inline-expand.md) — **Task details below row** (remove right-rail edit inspector; shared form component; kanban parity) — large `Index.vue` refactor.  
3. [US-025](../backlog/user-stories/US-025-mobile-tasks-shell-improvements.md) — **Mobile shell** (collapsible search, hide help `details` on mobile, nav simplification) — same file; after US-024 to reduce merge conflicts.  
4. [US-026](../backlog/user-stories/US-026-double-click-edit-task.md) — **Double-click to edit** (list + board; guard checkboxes/links) — quick win once edit entry point is stable.

## Sprint Overview

**Focus Areas**:
- Cross-list visibility (aggregation + rate limits)
- Calm, single-column edit pattern (inline details)
- Mobile-first header and help chrome
- Desktop interaction polish (double-click)

**Key Deliverables**:
- “All tasks” (or equivalent) view with list badges and documented defaults
- Edit UI inline under row (desktop + mobile policy per US-024)
- Mobile: search on demand; help not as collapsible `details` on small screens
- Double-click opens same edit path as Details

**Dependencies**:
- US-023: [US-008](../backlog/user-stories/US-008-google-tasks-sync-engine.md) and [US-009](../backlog/user-stories/US-009-views-today-inbox-lists.md) ✅

**Risks & Blockers**:
- Google API quota when aggregating many lists (US-023) — backoff / batching / UX for partial failure
- **US-024 + US-025** both touch `Tasks/Index.vue` — strict branch order or frequent integration
- 24 points vs 18 prior sprint — scope risk; US-026 is the explicit flex item

---

## User Stories (this sprint)

| ID | Title | Points | Status |
|----|-------|--------|--------|
| [US-023](../backlog/user-stories/US-023-all-tasks-all-lists.md) | All tasks across all lists | 8 | ✅ |
| [US-024](../backlog/user-stories/US-024-task-details-inline-expand.md) | Task details expand below row | 5 | ✅ |
| [US-025](../backlog/user-stories/US-025-mobile-tasks-shell-improvements.md) | Mobile Tasks shell improvements | 8 | ✅ |
| [US-026](../backlog/user-stories/US-026-double-click-edit-task.md) | Double-click to edit | 3 | ✅ |

**Total Story Points**: 24

---

## Tasks (high level)

### US-023 — All tasks across all lists

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-5.1 | Design aggregate endpoint + rate-limit strategy; extend `TaskViewAggregator` (or new service) | `TaskViewAggregator`, `TasksController` | ✅ |
| T-5.2 | Wire `navMode` / route or data API; list badge on rows; empty + partial-error states | `Index.vue`, i18n | ✅ |
| T-5.3 | Feature + unit/integration tests for aggregator | `tests/` | ✅ |

### US-024 — Inline task details

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-5.4 | Extract shared edit form; render below focused row; remove desktop right inspector for edit | `Index.vue` | ✅ |
| T-5.5 | Kanban parity; new-task “More” decision per story | `TasksKanbanBoard.vue` | ✅ |
| T-5.6 | Regression pass: save, delete, bulk, a11y | Manual + tests | ✅ |

### US-025 — Mobile shell

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-5.7 | Progressive search UI (`max-lg:`); dismiss + focus | `Index.vue` | ✅ |
| T-5.8 | Hide or link help on mobile; simplify nav disclosure | `Index.vue`, i18n | ✅ |

### US-026 — Double-click edit

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-5.9 | `dblclick` on row/card → `openEditInspector`; exclude controls | `Index.vue`, `TasksKanbanBoard.vue` | ✅ |
| T-5.10 | Optional: shortcuts help text | `TasksKeyboardShortcutsHelp.vue` | ✅ |

---

## Sprint Summary

**Sprint Burndown**: All **24** story points delivered 2026-03-22 (implementation in `apps/google-tasks`; `php artisan test` green).

**Backlog metrics (review prep)**: `./project-management/scripts/backlog-metrics.sh --stats` run 2026-03-22 as part of sprint close.

### Sprint Review Notes (2026-03-22)

**Prepared per** [sprint-review-process.md](../processes/sprint-review-process.md).

**Demonstrated (summary)**:

- **US-023 — All tasks**: `/tasks/data/views/all` aggregate; nav mode **All lists**; list badge on rows; incomplete default with filter parity; partial-failure and empty states; EN/FR.
- **US-024 — Inline details**: `TaskDetailEditPanel` below row; single-expand; Esc/close; kanban parity; new-task **More** aligned with below-row pattern.
- **US-025 — Mobile shell**: progressive search on narrow viewports; workflow help as modal (not noisy `<details>` on mobile); nav remains one-tap oriented.
- **US-026 — Double-click**: list rows and kanban cards open the same edit path as **Details**; controls excluded from `dblclick`; shortcuts help updated where applicable.

**Acceptance criteria**: Walked in each of [US-023](../backlog/user-stories/US-023-all-tasks-all-lists.md)–[US-026](../backlog/user-stories/US-026-double-click-edit-task.md); all criteria marked verified in story files during this closeout.

**Stakeholder feedback**: No new defects or scope changes recorded; **stakeholder demo before production** remains recommended (especially aggregate + mobile).

**Backlog updates from review**: None; no incomplete sprint items.

### Sprint Retrospective Notes (2026-03-22)

**Prepared per** [sprint-retrospective-process.md](../processes/sprint-retrospective-process.md).

#### What went well

- Sprint goal stayed coherent: **visibility + interaction** shipped as one UX pass.
- Explicit **implementation order** (US-023 → US-024 → US-025 → US-026) reduced thrash versus arbitrary pickup.
- **Server-side aggregate** for All tasks kept the client simpler and testable (`TaskViewAggregator`, feature tests).
- **24 points** landed with tests green; clear upgrade path for [US-027](../backlog/user-stories/US-027-consistent-dates-priority-across-views.md) sort parity on All tasks.

#### What could be improved

- **`Tasks/Index.vue` concentration**: multiple stories touched the same file — merge and review cost was the main bottleneck.
- **Aggregate + Google quota**: operators need a **short runbook** for 429 / partial list failure (beyond in-app retry copy).
- **Calendar vs. delivery**: scope finished on day one of the nominal two-week window — cadence is fine, but **review/retro dates** must be set immediately when scope completes (done here).

#### Retrospective improvements

| ID | Description | Owner | Due Sprint | Status |
|----|-------------|-------|------------|--------|
| [RI-004](../backlog/retrospective-improvements/RI-004-tasks-index-vue-integration-playbook.md) | Document branch order + merge checklist for concurrent `Index.vue` work | Developer | Sprint 6 | ⭕ To Do |
| [RI-005](../backlog/retrospective-improvements/RI-005-all-tasks-aggregate-429-runbook.md) | Operator runbook for all-tasks aggregate rate limits / partial failure | Developer | Sprint 6 | ⭕ To Do |

#### Process changes to document

- [ ] None mandatory; optional: link RI-004 from `apps/google-tasks/README.md` when the doc exists.

#### Follow-up

- [x] Retrospective improvements added to [product backlog](../backlog/product-backlog.md) table.
- [x] RI-004 / RI-005 referenced from [Sprint 6](sprint-06-google-tasks-trust-commands-mobile.md) planning (carry-in docs).
- [ ] Next retrospective: review RI-004 / RI-005 status (close or carry forward).

---

## Status Values

- ⭕ **To Do**: Not yet begun  
- ⏳ **In Progress**: In sprint; work underway  
- ✅ **Done**: Done and verified
