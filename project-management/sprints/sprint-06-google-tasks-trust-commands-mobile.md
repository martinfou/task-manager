---
template_version: 1.1.0
last_updated: 2026-03-22
compatible_with: [product-backlog]
---

# Sprint 6: Google Tasks — Trust, Commands, and Mobile Actions

[← Back to Product Backlog](../backlog/product-backlog.md)

**Sprint status**: **Active** — started 2026-03-22 after [Sprint 5](sprint-05-google-tasks-ux-visibility.md) closed.

**Sprint Goal**: Ship the **usability pack** (undo, command palette, snooze/defer, mobile swipe) **and** complete **all remaining open product backlog stories** ([US-027](../backlog/user-stories/US-027-consistent-dates-priority-across-views.md)–[US-038](../backlog/user-stories/US-038-due-date-timezone-display.md)) in this sprint bucket.

**Duration**: 2026-03-22 — 2026-04-05 (2 weeks) — *nominal end date; **91** story points committed (US-027–US-038; see below) — exceeds typical velocity (~18–24 pts/2w). **Replan**: extend sprint window, split into Sprint 6a/6b, or move lower-priority stories back to backlog after review.*

**Team Velocity (reference)**: **18** points ([Sprint 4](sprint-04-google-tasks-quality-and-v2.md)); **24** ([Sprint 5](sprint-05-google-tasks-ux-visibility.md)) ✅  
**Sprint Planning Date**: 2026-03-22 (rolled from Sprint 5 close); **expanded scope** 2026-03-22 — all open backlog stories assigned here  
**Sprint Review Date**: *TBD*  
**Sprint Retrospective Date**: *TBD*

**Depends on**: [US-023](../backlog/user-stories/US-023-all-tasks-all-lists.md)–[US-026](../backlog/user-stories/US-026-double-click-edit-task.md) ✅.

---

## Sprint planning record (2026-03-22, expanded)

| Step | Result |
|------|--------|
| Backlog metrics | `./project-management/scripts/backlog-metrics.sh --stats` |
| Definition of Ready | [Refinement session](backlog-refinement-session-2026-03-22.md); large stories groom before starting |
| Capacity | **91** pts — **all** open stories (US-027–US-038); expect mid-sprint scope negotiation |
| Branching | [ADR-002](../architecture-decision-records/ADR-002-branching-strategy.md) |

### Suggested implementation order

1. [US-029](../backlog/user-stories/US-029-undo-toast-destructive-actions.md) — **Undo toast** ✅.  
2. [US-028](../backlog/user-stories/US-028-command-palette-navigation-quick-add.md) — **Command palette** ✅.  
3. [US-030](../backlog/user-stories/US-030-snooze-defer-presets.md) — **Snooze / defer** ✅.  
4. [US-031](../backlog/user-stories/US-031-mobile-swipe-task-actions.md) — **Mobile swipe** ✅.  
5. [US-034](../backlog/user-stories/US-034-enter-key-save-task-edit.md) — **Enter to save** (small) ✅.  
6. [US-027](../backlog/user-stories/US-027-consistent-dates-priority-across-views.md) — **Dates/priority consistency**.  
7. [US-032](../backlog/user-stories/US-032-kanban-due-date-lanes.md) — **Kanban due lanes**.  
8. [US-033](../backlog/user-stories/US-033-find-semantic-duplicate-tasks.md) — **Semantic duplicate finder**.  
9. [US-036](../backlog/user-stories/US-036-dashboard-productivity-charts-and-insights.md) — **Dashboard insights**.  
10. [US-035](../backlog/user-stories/US-035-server-backup-and-restore.md) — **Backup / restore** (ops; may parallelize).  
11. [US-038](../backlog/user-stories/US-038-due-date-timezone-display.md) — **Due date / timezone display** (pairs with US-027 where overlap).  
12. [US-037](../backlog/user-stories/US-037-instant-list-switch-cache-first-sync.md) — **Instant list switch** (cache-first; may follow list performance work).

**From Sprint 5 retro (carry-in)**: [RI-004](../backlog/retrospective-improvements/RI-004-tasks-index-vue-integration-playbook.md), [RI-005](../backlog/retrospective-improvements/RI-005-all-tasks-aggregate-429-runbook.md) — docs in Sprint 6.

---

## Sprint Overview

**Focus Areas**: Undo · Command palette · Snooze · Swipe · Consistency · Kanban lanes · Duplicates · Dashboard · Backup · Keyboard save

**Key Deliverables**: Per story files; see **Risks** for capacity.

**Risks**: `Index.vue` integration load ([RI-004](../backlog/retrospective-improvements/RI-004-tasks-index-vue-integration-playbook.md)); **91 pts** vs **~2-week** cadence — prioritize US-029–US-031 first, then negotiate carry-over.

---

## User Stories (this sprint)

| ID | Title | Points | Status |
|----|-------|--------|--------|
| [US-027](../backlog/user-stories/US-027-consistent-dates-priority-across-views.md) | Consistent dates & priority | 13 | ⏳ |
| [US-028](../backlog/user-stories/US-028-command-palette-navigation-quick-add.md) | Command palette | 8 | ✅ |
| [US-029](../backlog/user-stories/US-029-undo-toast-destructive-actions.md) | Undo toast | 3 | ✅ |
| [US-030](../backlog/user-stories/US-030-snooze-defer-presets.md) | Snooze / defer | 5 | ✅ |
| [US-031](../backlog/user-stories/US-031-mobile-swipe-task-actions.md) | Mobile swipe | 5 | ✅ |
| [US-032](../backlog/user-stories/US-032-kanban-due-date-lanes.md) | Kanban due-date lanes | 8 | ⭕ |
| [US-033](../backlog/user-stories/US-033-find-semantic-duplicate-tasks.md) | Semantic duplicate tasks | 13 | ⭕ |
| [US-034](../backlog/user-stories/US-034-enter-key-save-task-edit.md) | Enter to save while editing | 2 | ✅ |
| [US-035](../backlog/user-stories/US-035-server-backup-and-restore.md) | Server backup & restore | 8 | ⭕ |
| [US-036](../backlog/user-stories/US-036-dashboard-productivity-charts-and-insights.md) | Dashboard productivity charts | 13 | ⭕ |
| [US-037](../backlog/user-stories/US-037-instant-list-switch-cache-first-sync.md) | Instant list switch (cache-first) | 8 | ⭕ |
| [US-038](../backlog/user-stories/US-038-due-date-timezone-display.md) | Due date / time display & timezone | 5 | ⭕ |

**Total Story Points**: **91** (twelve stories: US-027–US-038)

---

## Tasks (high level)

### US-029 — Undo toast

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.1 | Toast + `useUndoToast`; complete / deferred delete / single bulk-move undo | `Index.vue`, `useUndoToast.js`, `UndoToast.vue` | ✅ |
| T-6.2 | i18n EN/FR; `role="status"` | `en.json`, `fr.json` | ✅ |
| T-6.3 | Manual QA + mark DoD | — | ✅ |

### US-028 — Command palette

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.4 | Modal + ⌘K/Ctrl+K; typing guard | `TasksCommandPalette.vue` | ✅ |
| T-6.5 | Commands + quick add | `Index.vue` | ✅ |
| T-6.6 | Mobile menu entry | `AuthenticatedLayout.vue` | ✅ |

### US-030 — Snooze

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.8 | Preset date helper + tests | `deferPresets.js` | ✅ |
| T-6.9 | UI list + board + inspector + doc | `Index.vue`, `DEFER_SNOOZE.md` | ✅ |

### US-031 — Mobile swipe

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.11 | Gesture layer + thresholds | `TaskListRowSwipe.vue` | ✅ |
| T-6.12 | Wire actions + US-030 entry | `Index.vue`, `MOBILE_SWIPE.md` | ✅ |

### US-034 — Enter to save

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.13 | Enter / Ctrl+Enter handlers; list select; i18n + help | `TaskDetailEditPanel.vue`, `TasksKeyboardShortcutsHelp.vue`, `en.json`, `fr.json` | ✅ |

### US-027 — Consistent dates & priority (in progress)

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.14 | Global sort comparator + tests; persisted mode; list / Kanban / search metadata; sort UI + hints | `taskSort.js`, `Index.vue`, `TasksKanbanBoard.vue`, locales | ⏳ |

*Tasks for US-032–US-036: add when each story is started.*

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
- 2026-03-22 — **Scope expansion**: all open backlog stories (US-027–US-038, including [US-037](../backlog/user-stories/US-037-instant-list-switch-cache-first-sync.md) + [US-038](../backlog/user-stories/US-038-due-date-timezone-display.md)) assigned to Sprint 6; **91** pts total
- 2026-03-21 — **US-029** marked Done (AC verified; PHPUnit green)
- 2026-03-21 — **US-028** marked Done (command palette + layout menu; build/tests green)
- 2026-03-22 — **US-034** marked Done (Enter to save in task edit panel; keyboard help EN/FR)
- 2026-03-22 — **US-027** started: global task sort + row metadata order (slice 1)
