---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-028 - Command Palette — Navigate, Search, and Quick Add

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🟠 High  
**Story Points**: 8  
**Created**: 2026-03-21  
**Updated**: 2026-03-22  
**Assigned Sprint**: Backlog

## Description

Add a **command palette** (modal overlay) so power users can jump to views and lists, focus search, and start **quick add** without hunting the sidebar—pattern common in Linear, Raycast, and Notion. Origin: usability report (option **A**); user confirmed scope **A–D** split across US-028–US-031.

## User Story

As a user, I want a **command palette** to go anywhere and capture a task quickly—**keyboard on desktop**, **menu on narrow/mobile**—so that I spend less time navigating chrome and more time working tasks.

## Acceptance Criteria

- [ ] **`⌘K` / `Ctrl+K`** opens the palette on **desktop** (documented in app help); shortcut appears in [US-014](US-014-keyboard-shortcuts.md) help overlay and does not fire while typing in inputs (same guard pattern as other shortcuts). **External keyboard** on tablet/large narrow view: same shortcut when focus is not in an input.
- [ ] **Mobile / narrow (middle path)**: on documented **small-viewport** or **touch-primary** breakpoint, user can open the **same** palette from the **primary overflow / “More” / account menu** (single menu item, e.g. **Command palette** — i18n EN/FR). **No** dedicated persistent toolbar icon required in US-028; **no** requirement for a software-keyboard shortcut on phone.
- [ ] Palette lists **navigable actions**: jump to Today, Inbox, each task list, All-tasks view when [US-023](US-023-all-tasks-all-lists.md) exists, open/focus search, **New task** (targets current context list or documented default).
- [ ] **Type-to-filter** narrows commands and lists; **Arrow keys + Enter** selects; **Escape** closes; focus trap and focus restore on close.
- [ ] **Quick add** path from palette: user can enter a title and confirm; task is created via existing API/sync patterns (same list rules as inline add).
- [ ] **i18n**: EN/FR for palette chrome, empty state, and command labels.
- [ ] **No regression**: existing shortcuts still work; palette does not steal keys when inputs/modals already focused (reuse typing-context patterns from keyboard shortcuts).

## Business Value

Cuts navigation friction for multi-list users; matches discovery emphasis on **speed** and desktop power ([discovery JSON](../../../docs/google-tasks-discovery/responses-submitted-2026-03-21.json) `ux_layout`, `v1_world_class`).

## Technical Requirements

- Vue component + composable; integrate with Inertia routes or client-side view state as appropriate.
- Prefer accessible dialog (`role="dialog"`, labelled, `aria-modal`).

## Reference Documents

- [UX usability report session](../../../docs/google-tasks-discovery/responses-submitted-2026-03-21.json) (context)

## Technical References

- `apps/google-tasks/resources/js/composables/useTasksKeyboardShortcuts.js`
- `apps/google-tasks/resources/js/Components/TasksKeyboardShortcutsHelp.vue`
- `apps/google-tasks/resources/js/Pages/Tasks/Index.vue`

## Dependencies

- [US-014](US-014-keyboard-shortcuts.md)
- [US-009](US-009-views-today-inbox-lists.md)
- **Enabling**: [US-023](US-023-all-tasks-all-lists.md) for “All tasks” target in palette (palette can ship first with a stub or hidden command until US-023 lands)

## Clarifying Questions

- **Q**: Original choice was one theme; you selected **A, B, C, D**. How should backlog treat that?
- **A**: Split into **US-028 (A)**, **US-029 (B)**, **US-030 (C)**, **US-031 (D)** so each story is independently shippable.
- **Date**: 2026-03-21

- **Q**: Which shortcut opens the palette on desktop?
- **A**: **Option 1** — `⌘K` (macOS) / `Ctrl+K` (Windows/Linux), Linear-style.
- **Date**: 2026-03-21

- **Q**: **Mobile / narrow** entry to the palette — toolbar button, **desktop shortcut only** for v1, or **middle path**?
- **A**: **Middle path** — **no** persistent toolbar icon; palette opens from **overflow / More / account menu** item (**Command palette**, i18n). Desktop/tablet with keyboard keeps **⌘K / Ctrl+K** per prior answer.
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

**Ready for sprint planning**: Yes. **Palette “All tasks”**: enable command when [US-023](US-023-all-tasks-all-lists.md) is Done (stub/hidden until then per Dependencies).

## Notes

- **Narrow / mobile**: see Clarifying Questions — **menu item** only for v1 (not full chrome icon).

## Acceptance Verification

- [ ] All acceptance criteria above verified as met
- [ ] Each criterion tested or inspected and confirmed

## History

- 2026-03-21 - Created from usability report; scope A confirmed as part of A–D split
- 2026-03-21 - Clarified palette shortcut: ⌘K / Ctrl+K
- 2026-03-22 - Clarified: **middle path** mobile entry (overflow menu item); story points **8**
- 2026-03-22 - Backlog refinement: DoR recorded
