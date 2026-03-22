---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-030 - Snooze and Defer Presets (Due Date)

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🟡 Medium  
**Story Points**: 5  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md)

## Description

Google Tasks has no native “snooze” field. Implement **snooze/defer** as **updating the task due date** (and optionally clearing vs preserving time) using **one-tap presets**—Todoist / Apple Reminders pattern. Discovery commits to **use dates fully** (`f_dates`). Origin: usability report option **C**; user confirmed **A–D** split (US-028–US-031).

## User Story

As a user, I want to **defer tasks with presets** (e.g. tomorrow, next week), so that I can reschedule quickly without opening the full date picker every time.

## Acceptance Criteria

- [ ] From a task row (and task detail when present), user can open **Snooze / Defer** and choose at least: **Tomorrow**, **Next week** (Monday 00:00 — first day of the **next** ISO week after the week containing today; same timezone as **Today**), **Weekend** (**next upcoming Saturday 09:00** local — see Notes if “now” is already past that moment), **Pick date…** (existing date UI).
- [ ] Presets **set due** per Google Tasks API rules; behavior for tasks **without** a prior due date is documented (e.g. set date-only due).
- [ ] **Timezone**: dates follow user/app timezone already used for Today view; document edge cases.
- [ ] Works in **list** and **Kanban** surfaces where tasks are shown; no orphaned action on mobile if [US-031](US-031-mobile-swipe-task-actions.md) adds another entry point.
- [ ] **i18n**: EN/FR preset labels and screen-reader text.
- [ ] **Undo**: optional coordination with [US-029](US-029-undo-toast-destructive-actions.md) if move/complete undo ships first (defer may use same toast pattern for “Due updated” + Undo).

## Business Value

Speeds daily triage; directly supports “use dates fully” and reduces friction for busy lists.

## Technical Requirements

- Reuse existing due-date update API path; batching/rate limits per [US-008](US-008-google-tasks-sync-engine.md).
- Centralize preset → date mapping in one module for tests.

## Reference Documents

- [Discovery](../../../docs/google-tasks-discovery/responses-submitted-2026-03-21.json) — `f_dates`

## Technical References

- Task update endpoints and Vue inspectors for due date
- `apps/google-tasks/app/Services/Google/` task update flow

## Dependencies

- [US-008](US-008-google-tasks-sync-engine.md)
- **Optional synergy**: [US-029](US-029-undo-toast-destructive-actions.md) for undo of due changes

## Clarifying Questions

- **Q**: Scope A–D split?
- **A**: **US-030** = option **C**.
- **Date**: 2026-03-21

- **Q**: What should **“Next week”** mean (e.g. next Monday, +7 days from today)?
- **A**: **Option 1** — **Start of the next calendar week**: the **Monday 00:00** that begins the **ISO week immediately after** the week containing “today,” in the same timezone as the **Today** view (so if today is Monday, “next week” is **not** today—it is Monday **+7 days**).
- **Date**: 2026-03-21

- **Q**: What should **“Weekend”** mean?
- **A**: **Option 2** — **Upcoming Saturday 09:00** in the same timezone as the **Today** view.
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

**Ready for sprint planning**: Yes.

## Notes

- Preset definitions must be **user-visible** in help or tooltip to avoid surprises.
- **Next week** = Monday 00:00 local time at the start of the **next** ISO week after the current date (see Clarifying Questions); implement with one tested date helper.
- **Weekend** = the **next** Saturday 09:00 local that is **strictly after “now”** (e.g. if today is Saturday before 09:00, use today at 09:00; if Saturday after 09:00 or Sun–Fri, use the **following** Saturday 09:00).

## Acceptance Verification

- [ ] All acceptance criteria above verified as met
- [ ] Each criterion tested or inspected and confirmed

## History

- 2026-03-21 - Created from usability report; scope C
- 2026-03-21 - Clarified “Next week”: Monday 00:00, first day of next ISO week (incl. if today is Monday → +7d)
- 2026-03-21 - Clarified “Weekend”: next upcoming Saturday 09:00 local
- 2026-03-22 - Backlog refinement: DoR recorded
- 2026-03-22 - Assigned to [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md) (all open backlog stories in sprint bucket)
