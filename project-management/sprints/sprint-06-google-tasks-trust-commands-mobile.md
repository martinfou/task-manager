---
template_version: 1.1.0
last_updated: 2026-03-22
compatible_with: [product-backlog]
---

# Sprint 6: Google Tasks — Trust, Commands, and Mobile Actions

[← Back to Product Backlog](../backlog/product-backlog.md)

**Sprint status**: **Active** — started 2026-03-22 after [Sprint 5](sprint-05-google-tasks-ux-visibility.md) closed.

**Sprint Goal**: Ship the **usability pack** from discovery: **undo toast** for destructive actions, **command palette** (⌘K / Ctrl+K), **snooze/defer presets**, and **mobile swipe** actions — faster triage with lower fear of mistakes.

**Duration**: 2026-03-22 — 2026-04-05 (2 weeks) — *aligned with prior cadence; adjust end date if needed*  
**Team Velocity (reference)**: **18** points in [Sprint 4](sprint-04-google-tasks-quality-and-v2.md); **24** committed in Sprint 5 ✅  
**Sprint Planning Date**: 2026-03-22 (rolled from Sprint 5 close)  
**Sprint Review Date**: *TBD*  
**Sprint Retrospective Date**: *TBD*

**Depends on**: [US-023](../backlog/user-stories/US-023-all-tasks-all-lists.md)–[US-026](../backlog/user-stories/US-026-double-click-edit-task.md) ✅.

---

## Sprint planning record (2026-03-22)

| Step | Result |
|------|--------|
| Backlog metrics | `./project-management/scripts/backlog-metrics.sh --stats` |
| Definition of Ready | US-029–US-031 per [refinement session](backlog-refinement-session-2026-03-22.md) |
| Capacity | **21** pts committed (US-029–US-031); flex US-034 deferred |
| Branching | [ADR-002](../architecture-decision-records/ADR-002-branching-strategy.md) |

### Implementation order

1. [US-029](../backlog/user-stories/US-029-undo-toast-destructive-actions.md) — **Undo toast** (in progress — first slice landed in code 2026-03-22).  
2. [US-028](../backlog/user-stories/US-028-command-palette-navigation-quick-add.md) — **Command palette**.  
3. [US-030](../backlog/user-stories/US-030-snooze-defer-presets.md) — **Snooze / defer**.  
4. [US-031](../backlog/user-stories/US-031-mobile-swipe-task-actions.md) — **Mobile swipe**.

**From Sprint 5 retro (carry-in)**: [RI-004](../backlog/retrospective-improvements/RI-004-tasks-index-vue-integration-playbook.md), [RI-005](../backlog/retrospective-improvements/RI-005-all-tasks-aggregate-429-runbook.md) — schedule in Sprint 6 or early grooming (docs-only).

**Deferred**: [US-034](../backlog/user-stories/US-034-enter-key-save-task-edit.md) (flex).

---

## Sprint Overview

**Focus Areas**: Undo safety · Command palette · Snooze · Swipe gestures

**Key Deliverables**: See story files; US-029 undo UI uses `useUndoToast` + `UndoToast.vue` (10s window; delete is deferred server commit).

**Risks**: `Index.vue` size; palette vs existing Ctrl+K search shortcut — reconcile in US-028.

---

## User Stories (this sprint)

| ID | Title | Points | Status |
|----|-------|--------|--------|
| [US-029](../backlog/user-stories/US-029-undo-toast-destructive-actions.md) | Undo toast | 3 | ⏳ |
| [US-028](../backlog/user-stories/US-028-command-palette-navigation-quick-add.md) | Command palette | 8 | ⭕ |
| [US-030](../backlog/user-stories/US-030-snooze-defer-presets.md) | Snooze / defer | 5 | ⭕ |
| [US-031](../backlog/user-stories/US-031-mobile-swipe-task-actions.md) | Mobile swipe | 5 | ⭕ |

**Total Story Points**: 21

---

## Tasks (high level)

### US-029 — Undo toast

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.1 | Toast + `useUndoToast`; complete / deferred delete / single bulk-move undo | `Index.vue`, `useUndoToast.js`, `UndoToast.vue` | ⏳ |
| T-6.2 | i18n EN/FR; `role="status"` | `en.json`, `fr.json` | ⏳ |
| T-6.3 | Manual QA + mark DoD | — | ⭕ |

### US-028 — Command palette

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.4 | Modal + ⌘K/Ctrl+K; typing guard | new component | ⭕ |
| T-6.5 | Commands + quick add | `Index.vue` | ⭕ |
| T-6.6 | Mobile menu entry | — | ⭕ |

### US-030 — Snooze

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.8 | Preset date helper + tests | `taskFilters` or util | ⭕ |
| T-6.9 | UI list + board | `Index.vue` | ⭕ |

### US-031 — Mobile swipe

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.11 | Gesture layer + thresholds | composable | ⭕ |
| T-6.12 | Wire actions + US-030 entry | `Index.vue` | ⭕ |

---

## Sprint Summary

**Sprint Burndown**: *Update as stories complete.*

**Sprint Review Notes**: *TBD*

**Sprint Retrospective Notes**: *TBD*

---

## Status Values

- ⭕ **To Do** · ⏳ **In Progress** · ✅ **Done**

---

## History

- 2026-03-22 — Planning draft created
- 2026-03-22 — **Activated**: Sprint 5 closed; US-029 implementation started (undo toast)
