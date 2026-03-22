---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-024 - Task Details Expand Below the Row

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟡 Medium  
**Story Points**: 5  
**Created**: 2026-03-21  
**Updated**: 2026-03-22  
**Assigned Sprint**: [Sprint 5](../../sprints/sprint-05-google-tasks-ux-visibility.md) (closed)

## Description

Change task **Details** so the edit surface **expands inline below the task row** (accordion / disclosure pattern) instead of opening in the **right-hand split panel** on desktop. Aligns with a calmer, list-first layout where context stays in one vertical flow.

*Current behavior (to replace for task edit)*: inspector opens as a sticky right column on large viewports and as a bottom sheet on small viewports (`apps/google-tasks/resources/js/Pages/Tasks/Index.vue`).

## User Story

As a user reviewing my task list, I want **Details** to **open below the task** I’m editing, so that I keep scanning the list in one column without a side panel shrinking the list.

## Acceptance Criteria

- [x] Choosing **Details** on a task row **expands a panel directly under that row** (same column as the list), containing the same fields as today’s edit inspector (title, due, recurrence, priority, notes, save, delete, close).
- [x] **Desktop (wide layout)**: no **right-rail** task inspector for edit mode; the list uses full content width except any unchanged app chrome (nav, sidebar).
- [x] **Mobile**: replace or align the **bottom sheet** with the same **below-row** pattern if feasible; if a sheet remains for very narrow viewports, document the breakpoint and rationale.
- [x] Only **one** task’s details expanded at a time, or explicitly document multi-expand behavior (default: single expand; opening another collapses the previous).
- [x] **Collapse** via explicit control, optional second click on Details, and **Escape** where it does not conflict with global shortcuts.
- [x] **Kanban**: **Details** uses the same inline-below-card (or below-row) pattern, or a documented equivalent that does not use a right-side inspector.
- [x] **New task “More”** optional fields: either keep current pattern or move to **below the quick-add row** for consistency (note decision in implementation / release notes).
- [x] No regression: save, delete, validation, bulk selection, and polling still work; **automated tests** updated or added where they assert layout-specific behavior.

## Business Value

Reduces horizontal competition between list and inspector; matches users who prefer a single scrolling column (executive / triage workflows).

## Technical Requirements

- Refactor Vue structure in `Tasks/Index.vue` (and related components) so edit state is row-scoped; avoid duplicating large form markup twice—extract a shared **TaskDetailForm** (or similar) if helpful.
- Preserve **i18n** (EN/FR) for any new strings (e.g. collapse, “expanded”).

## Technical References

- `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` — inspector, list rows, `openEditInspector` / `inspectorMode === 'edit'`
- `apps/google-tasks/resources/js/Components/TasksKanbanBoard.vue` — Details / inspect-task

## Dependencies

- None blocking; builds on existing task update APIs and [US-009](US-009-views-today-inbox-lists.md) task shell.
- **Coordinate**: [US-026](US-026-double-click-edit-task.md) (edit entry point) and [US-034](US-034-enter-key-save-task-edit.md) (Enter to save in edit surface) — same inline panel; verify keyboard behavior after merge.

## Clarifying Questions

*Document answers after refinement.*

- **Q**: Should **new task “More”** always match task **Details** (both below-row)?
- **A**: **Yes** — **new task “More”** uses the **same below-row expansion pattern** as **task Details** (one interaction model for create vs edit).
- **Date**: 2026-03-21

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

- Supersedes the prior “right split panel for edit” choice for **task** details; **mobile bottom sheet** may still be acceptable as a narrow-only fallback—product owner to confirm.
- Related backlog: [US-023](US-023-all-tasks-all-lists.md) (all-lists view) should reuse the same detail pattern when implemented.

## Acceptance Verification

- [x] All acceptance criteria verified
- [x] Documentation-Code Consistency check before marking Done

## History

- 2026-03-21 - Created
- 2026-03-21 - Assigned to [Sprint 5](../../sprints/sprint-05-google-tasks-ux-visibility.md); status ⏳ In Progress
- 2026-03-21 - Clarified: new task **More** matches task **Details** (below-row pattern)
- 2026-03-22 - Backlog refinement: DoR recorded; dependencies note US-026 / US-034 coordination
- 2026-03-22 - Sprint 5 sprint review: acceptance criteria and verification checklist marked complete
