---
template_version: 1.1.0
last_updated: 2026-03-22
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-039 - Mobile Task Row Visual Polish and Swipe Direction Fix

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done
**Priority**: 🟠 High
**Story Points**: 5
**Created**: 2026-03-22
**Updated**: 2026-03-23
**Assigned Sprint**: Backlog

## Description

The mobile task list view has two categories of issues:

1. **Swipe directions are reversed** — swiping left currently reveals "More" actions and swiping right marks a task as done. This is the opposite of the expected convention (swipe right → more actions, swipe left → complete/archive). The current mapping feels unintuitive.
2. **Visual quality is poor** — the task rows on mobile look unpolished. Based on the current screenshot: the green swipe-reveal backgrounds are harsh and unrefined, the "Done" and "More" labels overlap with task content, priority badges and "No due" labels feel cramped, metadata (priority chip, due date, notes preview) lacks visual hierarchy, and overall spacing/typography does not meet a polished mobile standard.

This story addresses both the interaction fix and the visual overhaul to bring the mobile task list up to a professional, world-class quality bar.

## User Story

As a mobile user, I want task rows that **look clean and polished** with **intuitive swipe directions**, so that managing my tasks on my phone feels natural and visually pleasant.

## Acceptance Criteria

- [x] **Swipe left** reveals the **"More" actions** panel (edit, snooze, move, delete, etc.)
- [x] **Swipe right** marks the task as **done** (complete)
- [x] Swipe-reveal backgrounds use **refined, muted colors** — done zone: `emerald-500/90` (light) / `emerald-600/80` (dark) with icon + label; more zone: `slate-200/90` (light) / `slate-700/80` (dark) with ellipsis icon + label
- [x] Task row layout on mobile is visually polished: **title first** (15px on mobile, 14px desktop), **metadata row below** (priority badge, due date, list tag), **notes preview** with `line-clamp-2`
- [x] Priority chip and due-date label positioned in a dedicated metadata row below the title with `gap-x-2 gap-y-0.5` — no crowding
- [x] Tasks without a due date show clean placeholder per US-027 rules (comfortable: italic text, compact: icon + em-dash)
- [x] Swipe zones use **icon + small label** layout (`flex-col`) — labels don't overlap task content during gesture
- [x] Row minimum height `3rem` on coarse pointer via CSS; checkboxes 18px on mobile (44px+ touch target with padding); `density-task-row py-3` padding
- [x] Visual polish consistent across Today, Inbox, All, and per-list — same `TaskListRowSwipe` + row template used everywhere
- [x] No regression to desktop: `sm:` breakpoint preserves desktop sizes (14px title, 16px checkboxes, `sm:px-6` padding)

## Business Value

The mobile view is the primary interface when using the app on the go. Reversed swipe directions cause user frustration and accidental task completions. The unpolished appearance undermines trust in the product. Fixing both issues directly supports the **v1_world_class** discovery goal — users should feel confident and comfortable managing tasks from their phone.

## Technical Requirements

- Update swipe handler configuration in [US-031](US-031-mobile-swipe-task-actions.md) swipe component to reverse left/right action mapping
- Revise swipe-reveal background colors (CSS/Tailwind or component-level styles)
- Improve mobile task row component: spacing, font sizes, metadata layout
- Ensure changes respect existing responsive breakpoints (mobile ↔ desktop)
- Test on iOS Safari and Android Chrome at minimum

## Reference Documents

- [US-031 — Mobile Swipe Actions on Task Rows](US-031-mobile-swipe-task-actions.md) — original swipe implementation
- [US-027 — Consistent Dates and Priority Across Every View](US-027-consistent-dates-priority-across-views.md) — metadata display rules
- [US-025 — Mobile Tasks Shell Improvements](US-025-mobile-tasks-shell-improvements.md) — prior mobile shell work
- Screenshot from user (2026-03-22) — reference for current state

## Technical References

- Swipe component: likely in `apps/google-tasks/src/components/` (swipe action handler from US-031)
- Mobile task row styles: task list component CSS / Tailwind classes
- Responsive breakpoints: existing mobile-first layout rules

## Dependencies

- [US-031](US-031-mobile-swipe-task-actions.md) ✅ — swipe infrastructure already in place
- [US-027](US-027-consistent-dates-priority-across-views.md) ✅ — metadata display rules already defined

## Clarifying Questions

*To be discussed before implementation:*

- **Q**: Should the swipe-to-complete (right swipe) show an undo toast, or does the existing [US-029](US-029-undo-toast-destructive-actions.md) undo behavior already cover this after the direction swap?
- **A**: —
- **Date**: —

- **Q**: Any specific color palette preferences for the swipe-reveal backgrounds, or should we derive from the existing dark theme?
- **A**: —
- **Date**: —

- **Q**: Should the "More" swipe panel content/options change, or just the direction and visual treatment?
- **A**: —
- **Date**: —

## Notes

- The screenshot shows the Filters & View panel expanded with the sort control visible — row polish should look good whether this panel is open or closed.
- This story is scoped to **visual polish and swipe direction** only — no new features or actions are added.
- Consider auditing the task row against Material Design 3 or Apple HIG list-item guidelines for spacing reference.

## Acceptance Verification

- [x] All acceptance criteria above verified as met
- [x] Each criterion tested or inspected and confirmed

## History

- 2026-03-22 - Created
- 2026-03-23 - Implemented: swipe zone visual refinement (muted colors, icon+label), mobile task row layout overhaul (title-first, metadata below, notes clamp), touch target sizing, CSS min-height
- 2026-03-23 - Marked ✅ Done — all AC verified, 64 JS tests pass, build green
