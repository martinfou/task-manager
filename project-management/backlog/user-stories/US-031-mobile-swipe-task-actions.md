---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-031 - Mobile Swipe Actions on Task Rows

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🟠 High  
**Story Points**: 5  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: Backlog

## Description

On **touch devices**, support **horizontal swipe** on task rows to expose primary actions—iOS Reminders / Gmail mobile pattern. Minimum set: **complete**, **snooze/defer** (ties to [US-030](US-030-snooze-defer-presets.md) when available), **move list**, **delete** (with confirmation or undo per [US-029](US-029-undo-toast-destructive-actions.md)). Origin: usability report option **D**; user confirmed **A–D** split (US-028–US-031).

## User Story

As a mobile user, I want to **act on tasks with swipes**, so that I can triage quickly with one thumb.

## Acceptance Criteria

- [ ] On **narrow / touch** breakpoints (LTR): **swipe right** reveals **Complete** (primary); **swipe left** reveals **More** leading to **defer/snooze** ([US-030](US-030-snooze-defer-presets.md) presets or equivalent), **move to list**, and **delete** (sheet, action row, or bottom sheet — labels in EN/FR).
- [ ] **Together**, the two directions cover **Complete**, **defer**, **move**, and **delete** without requiring a third swipe pattern for the baseline set.
- [ ] **Move to list** is available via swipe or nested action without breaking scroll performance.
- [ ] **Delete** uses confirmation **or** relies on undo toast when [US-029](US-029-undo-toast-destructive-actions.md) is done; document chosen pattern.
- [ ] **Scroll vs swipe** disambiguation: vertical scroll remains smooth; no accidental completes at scroll end (thresholds documented).
- [ ] **Accessibility**: non-touch path still fully usable (long-press menu or action buttons); swipe actions have text labels.
- [ ] **i18n**: EN/FR for action labels and hints.
- [ ] **Kanban**: if cards are shown on mobile, define whether swipe applies or list-only (document and test).

## Business Value

Matches discovery **“easy on mobile”** alongside desktop power; reduces taps per action.

## Technical Requirements

- Consider a small library or composable for touch panes; avoid main-thread jank on long lists (virtualization-aware if applicable).

## Reference Documents

- [Discovery](../../../docs/google-tasks-discovery/responses-submitted-2026-03-21.json) — `ux_layout`, `ux_mobile`

## Technical References

- `apps/google-tasks/resources/js/Pages/Tasks/Index.vue`
- `apps/google-tasks/resources/js/Components/TasksKanbanBoard.vue`
- [US-025](US-025-mobile-tasks-shell-improvements.md)

## Dependencies

- [US-025](US-025-mobile-tasks-shell-improvements.md) — shell/nav should not conflict with swipe affordances
- **Synergy**: [US-030](US-030-snooze-defer-presets.md) for defer entry from swipe
- **Synergy**: [US-029](US-029-undo-toast-destructive-actions.md) for destructive safety

## Clarifying Questions

- **Q**: Scope A–D split?
- **A**: **US-031** = option **D**.
- **Date**: 2026-03-21

- **Q**: Swipe **left** vs **right** — one direction only, or both with different actions?
- **A**: **Option 3** — **both directions**; **left** and **right** each reveal a **different** action set (mapping: following Q).
- **Date**: 2026-03-21

- **Q**: What is the **action mapping** per direction (LTR layout)? E.g. swipe right → ? ; swipe left → ?
- **A**: **Option 1** — **Swipe right** → **Complete**. **Swipe left** → **More** (contains **Defer/snooze**, **Move to list**, **Delete** via sheet/buttons or equivalent).
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

**Ready for sprint planning**: Yes. **Shell**: coordinate with [US-025](US-025-mobile-tasks-shell-improvements.md) (Sprint 5) — row swipe vs progressive search are compatible; schedule US-031 after US-025 if the same files conflict heavily.

## Notes

- If implementation is list-only for v1, state explicitly in release notes and acceptance criteria.
- **RTL**: If the app later adopts mirrored layout direction, **mirror** swipe semantics (Complete on the “start” side of the row); until then, document LTR-only behavior.

## Acceptance Verification

- [ ] All acceptance criteria above verified as met
- [ ] Each criterion tested or inspected and confirmed

## History

- 2026-03-21 - Created from usability report; scope D
- 2026-03-21 - Clarified bidirectional swipe (option 3); action mapping: swipe right → Complete; swipe left → More (defer, move, delete)
- 2026-03-22 - Backlog refinement: DoR recorded; sequencing note vs US-025
