---
template_version: 1.1.0
last_updated: 2026-03-22
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-034 — Press Enter to Save While Editing a Task

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟡 Medium  
**Story Points**: 2  
**Created**: 2026-03-22  
**Updated**: 2026-03-22  
**Assigned Sprint**: [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md)

## Description

While the **task edit** surface is open (inline panel below the row or under a kanban card per [US-024](US-024-task-details-inline-expand.md)), users should be able to press **Enter** to **save** without clicking **Save changes**, matching common form and mail-client expectations.

## User Story

As a user editing a task, I want to press **Enter** to **save my changes**, so that I can finish edits quickly from the keyboard.

## Acceptance Criteria

- [x] With task details open for edit, pressing **Enter** triggers the same **save** action as **Save changes** (successful path and error handling unchanged).
- [x] **Title** field (`TextInput`): **Enter** saves (and does not submit a second time if already saving / disabled).
- [x] **Notes** (`textarea`): plain **Enter** continues to insert a **newline**; **Ctrl+Enter** (Windows/Linux) and **Cmd+Enter** (macOS) save the task (document in keyboard help if not already covered).
- [x] Other single-line fields (**due** `datetime-local`, **recurrence** text): **Enter** saves unless the browser uses Enter for native picker behavior — document any exception.
- [x] **Priority** `<select>` (and **list** `<select>` when moving): **Enter** saves when the control would not otherwise commit a native change (align with browser defaults; no duplicate save loops).
- [x] **No regression**: Escape still closes/cancels per existing behavior; **Save** / **Delete** / validation messages unchanged; bulk selection and list shortcuts unaffected when focus is in the edit panel.

## Business Value

Reduces friction for keyboard-heavy users and aligns the edit form with familiar “submit on Enter” patterns on the primary field.

## Technical Requirements

- Implement in `apps/google-tasks/resources/js/Components/TaskDetailEditPanel.vue` (and parent wiring in `Tasks/Index.vue` if the save handler must be invoked from the panel).
- Optional: mention in `TasksKeyboardShortcutsHelp.vue` if we add **Cmd/Ctrl+Enter** for notes.

## Implementation notes

- **`datetime-local`**: Enter is wired to save; some browsers may prioritize native picker behavior — if Enter does not reach the handler, use **Save changes**.

## Technical References

- [US-024](US-024-task-details-inline-expand.md) — inline edit surface
- [US-014](US-014-keyboard-shortcuts.md) — keyboard conventions
- `apps/google-tasks/resources/js/Components/TaskDetailEditPanel.vue`
- `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` — `saveEditedTask`, `inspectorEditTaskKey`

## Dependencies

- **Enabling**: [US-024](US-024-task-details-inline-expand.md) — Enter-to-save applies to the **inline** edit surface described there (schedule same sprint or immediately after US-024).
- [US-014](US-014-keyboard-shortcuts.md) — row-focus **Enter** (e.g. complete) must remain distinct from **Enter** when focus is **inside** the edit panel (per AC).

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

**Ready for sprint planning**: Yes — **schedule with or after US-024** (see Dependencies).

## Acceptance Verification

- [x] All acceptance criteria verified
- [x] Documentation-Code Consistency check before marking Done

## History

- 2026-03-22 - Created
- 2026-03-22 - Backlog refinement: DoR recorded; Dependencies set to US-024 + US-014
- 2026-03-22 - Assigned to [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md) (all open backlog stories in sprint bucket)
- 2026-03-22 - **Done**: `TaskDetailEditPanel.vue` — Enter on single-line fields and list/priority selects; Ctrl/Cmd+Enter on notes; `saving` guard; keyboard help EN/FR updated
