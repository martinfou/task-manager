---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-026 — Double-Click a Task to Edit

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟡 Medium  
**Story Points**: 3  
**Created**: 2026-03-21  
**Updated**: 2026-03-22  
**Assigned Sprint**: [Sprint 5](../../sprints/sprint-05-google-tasks-ux-visibility.md) (closed)

## Description

Enable **double-click** on a task (list row or kanban card body) to open the same **edit / Details** experience as the **Details** control, so desktop users can edit without aiming at the link.

Must **not** break existing **single-click** behavior (row focus, Ctrl/Cmd+click selection, Shift+range, checkbox clicks).

## User Story

As a desktop user managing tasks, I want to **double-click a task** to **open it for editing**, so that I can work faster than using the Details button alone.

## Acceptance Criteria

- [x] **List view**: `dblclick` on a task row (excluding interactive controls) opens the task editor — same behavior as **Details** today (inspector, inline panel, or future [US-024](US-024-task-details-inline-expand.md) pattern).
- [x] **Single click** still performs current behavior: focus for keyboard shortcuts, modifier multi-select, no accidental open on one click.
- [x] **Checkboxes**, **Details**, **Delete**, and other controls: `dblclick` does **not** trigger edit when the event originates on those elements (`stopPropagation` / target checks as needed).
- [x] **Kanban**: double-click on a card (outside inner checkboxes and **Details**) opens the same editor.
- [x] **Optimistic / pending tasks**: double-click disabled or no-op consistent with Details.
- [x] **Keyboard help** (`TasksKeyboardShortcutsHelp`) updated to mention double-click if the product owner wants it documented.
- [x] **Touch / mobile**: double-tap is optional; if not implemented, document “desktop-oriented”; no regression to tap behaviors for selection and complete.

## Business Value

Familiar pattern from file managers and mail clients; reduces pointer travel to a small **Details** link.

## Technical Requirements

- Implement in `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` (row handler) and `TasksKanbanBoard.vue` or via shared handler passed from parent.
- Avoid firing edit when `dblclick` follows intentional **double single-clicks** on controls — use event target checks.

## Technical References

- `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` — `onTaskRowClick`, `openEditInspector`
- `apps/google-tasks/resources/js/Components/TasksKanbanBoard.vue` — card click / `inspect-task`
- `apps/google-tasks/resources/js/composables/useTasksKeyboardShortcuts.js` — ensure no conflict with `Enter` (currently toggles complete on focused row)

## Dependencies

- None; aligns with whichever **Details** implementation is current when picked up. Coordinate with [US-024](US-024-task-details-inline-expand.md) if both are in the same sprint.
- [US-034](US-034-enter-key-save-task-edit.md) adjusts **Enter** inside the edit surface vs [US-014](US-014-keyboard-shortcuts.md) row focus — verify both after US-034 lands.

## Clarifying Questions

*Document answers after refinement.*

- **Q**: If the row is already focused, should a second **double-click** toggle close the editor?
- **A**: **Typical pattern** — **No**: a second **double-click does not close** the editor. **Double-click on the row** enters edit; once the **title/input is focused**, further **double-clicks** are **ordinary text behavior** (e.g. word select). **Exit** via **Esc**, **blur** (click outside), or explicit **Save/Done/Cancel** — avoids accidental collapse while editing.
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

- **Enter** on a focused task currently toggles completion ([US-014](US-014-keyboard-shortcuts.md)); double-click is complementary and should remain distinct.

## Acceptance Verification

- [x] Manual verification on list and board
- [x] Documentation-Code Consistency before marking Done

## History

- 2026-03-21 - Created
- 2026-03-21 - Assigned to [Sprint 5](../../sprints/sprint-05-google-tasks-ux-visibility.md); status ⏳ In Progress
- 2026-03-21 - Clarified: **no** toggle-close on second double-click; exit via Esc / blur / explicit controls (typical pattern)
- 2026-03-22 - Backlog refinement: DoR recorded; forward dependency note for US-034 / US-014 Enter behavior
- 2026-03-22 - Sprint 5 sprint review: acceptance criteria and verification checklist marked complete
