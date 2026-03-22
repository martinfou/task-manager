---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-029 - Undo Toast for Complete, Delete, and Move

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⏳ In Progress  
**Priority**: 🟠 High  
**Story Points**: 3  
**Created**: 2026-03-21  
**Updated**: 2026-03-22  
**Assigned Sprint**: [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md)

## Description

After **complete**, **delete**, or **move** (change list) actions, show a **non-blocking toast** with **Undo** that reverses the action within a short window—Gmail/Google Calendar pattern. Reduces fear of mistakes and fits optimistic UI ([US-008](US-008-google-tasks-sync-engine.md), [US-019](US-019-api-error-retry-ux.md)). Origin: usability report option **B**; user confirmed **A–D** split across US-028–US-031.

## User Story

As a user, I want to **undo recent task actions** from a toast, so that accidental completes, deletes, or moves do not derail my flow.

## Acceptance Criteria

- [ ] **Complete**: toast with Undo restores task to incomplete state (or equivalent API operations) when still within the undo window.
- [ ] **Delete**: Undo restores the task if Google API allows recreation within window; if not possible, document behavior and offer best-effort (e.g. only undo before sync confirmed) — **spike note**: align with actual API constraints in implementation doc.
- [ ] **Move to another list**: Undo moves task back to the previous list.
- [ ] Toast **auto-dismisses** after a **configurable delay** (default **5 seconds** via `GOOGLE_TASKS_UNDO_TOAST_DELAY_MS` / Profile); Undo is available until dismiss or timeout (document in help if needed).
- [ ] **Stacking**: if multiple actions occur quickly, behavior is defined (e.g. one toast at a time vs queue); no silent loss of undo for the prior action without documentation.
- [ ] **i18n**: EN/FR for toast copy and Undo label.
- [ ] **Accessibility**: toast exposes `role="status"` or `alert` per severity; Undo is keyboard reachable.

## Business Value

Builds trust with optimistic sync; fewer support moments and less hesitation when triaging quickly.

## Technical Requirements

- Client-side undo stack or server-backed idempotency as needed; must not violate Google as source of truth beyond the defined undo window.
- Coordinate with existing error/retry flows so undo and API failure messages are not contradictory.

## Reference Documents

- [Google Tasks discovery](../../../docs/google-tasks-discovery/responses-submitted-2026-03-21.json) — `g_sync`, optimistic UI

## Technical References

- `apps/google-tasks/resources/js/composables/useUndoToast.js` — 10s window; flushes deferred delete before a new toast
- `apps/google-tasks/resources/js/Components/UndoToast.vue` — `role="status"`, Undo control
- Task mutation handlers in `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` (complete undo = reverse PATCH; delete = deferred `destroy`; single-task bulk move undo = reverse `move`)
- [US-019](US-019-api-error-retry-ux.md) patterns

## Dependencies

- [US-008](US-008-google-tasks-sync-engine.md)
- [US-019](US-019-api-error-retry-ux.md) (conceptual alignment)

## Clarifying Questions

- **Q**: Scope A–D split?
- **A**: **US-029** = option **B**; see US-028 description for full split.
- **Date**: 2026-03-21

- **Q**: Undo window duration (seconds)?
- **A**: **Option 3** — **10 seconds** (toast visible and Undo available until auto-dismiss or explicit dismiss).
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

**Ready for sprint planning**: Yes. **Spike**: delete-undo feasibility remains per AC and Notes (non-blocking for Ready).

## Notes

- **Bulk delete** and **multi-task bulk move** do not show an undo toast in the first implementation; only **single-task delete**, **complete**, and **single-task move** (one selected row moved) do.
- If **delete undo** cannot be made reliable after Google hard-delete, acceptance criteria may be adjusted after spike with explicit user-visible messaging.

## Acceptance Verification

- [ ] All acceptance criteria above verified as met
- [ ] Each criterion tested or inspected and confirmed

## History

- 2026-03-21 - Created from usability report; scope B
- 2026-03-21 - Clarified undo/toast window: 10 seconds
- 2026-03-22 - Backlog refinement: DoR recorded
- 2026-03-22 - Sprint 6: initial implementation — `useUndoToast`, `UndoToast`, `Index.vue` wiring (complete / deferred delete / single bulk move); i18n EN/FR
