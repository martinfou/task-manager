---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-032 - Kanban Due-Date Lanes (Reschedule by Drag)

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🟠 High  
**Story Points**: 8  
**Created**: 2026-03-21  
**Updated**: 2026-03-22  
**Assigned Sprint**: [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md)

## Description

The board view today groups tasks into **P1–P4 priority columns** with drag-and-drop changing priority ([US-012](US-012-filters-kanban.md)). Many users think in **when work is due** rather than abstract priority. This story adds a **due-date lane** board: columns **Overdue · Today · This week · Later / No date**, aligned with Google Tasks’ date model. **Dragging a card between columns updates the task’s due date** per lane mapping (see Acceptance Criteria); **drop onto Later / No date** sets a **default future due** (not clear), while tasks that **already have no due** still **appear** in that lane by definition.

Lane definitions use the **user’s local calendar** and **date-only due** semantics (same basis as `taskFilters.js` helpers such as `isTaskOverdue` / `isTaskDueToday`):

- **Overdue**: incomplete task with a due date strictly before today’s calendar day.
- **Today**: incomplete task with due on today’s calendar day.
- **This week**: incomplete task with due **after** today through **end of the current calendar week** (Sunday 23:59:59 local, same week as “today”; Monday–Sunday week).
- **Later / No date**: incomplete tasks with **no due**, **or** due **after** the current week (including next week and beyond).

Completed tasks are excluded from the board or grouped per product decision (document in implementation; default: same as current board — show only tasks matching active completion filter).

### Layout (ASCII)

Due-date board (four lanes, left → right = sooner attention → later / unscheduled). Cards are draggable; dropping on another column **reschedules** the task (updates `due`). Example shows **Group by: Due date**; filters/toolbar above are illustrative only.

```
+==========================================================================================+
|  Tasks area   view: [ List ] [ Board * ]     Board:  ( ) Priority   (•) Group by due     |
+==========================================================================================+
|                                                                                          |
|   OVERDUE              TODAY                 THIS WEEK           LATER / NO DATE        |
|   (due before          (due = local          (due after          (no due, or due        |
|    today)               today)                today, same         after current week)    |
|                                              calendar week)                              |
|  +----------------+  +----------------+  +----------------+  +----------------+      |
|  | [ ] Ship hotfix|  | [ ] 1:1 Sam    |  | [ ] Draft doc  |  | [ ] Research AI |     |
|  |     P1  Mar 18 |  |     P2  today  |  |     P3  Fri    |  |     P4  (no due) |     |
|  +----------------+  +----------------+  +----------------+  +----------------+      |
|  | [ ] Renew SSL  |  |                |  | [ ] Taxes      |  | [ ] Q2 planning |     |
|  |     P3  Sun    |  |                |  |     P2  Sat    |  |     —   Apr 14  |     |
|  +----------------+  +----------------+  +----------------+  +----------------+      |
|         ^                    ^                    ^                    ^               |
|         |                    |                    |                    |               |
|         +--------------------+--------------------+--------------------+               |
|              drag card across columns  ==>  PATCH due on server (mapping per lane)      |
|                                                                                          |
+==========================================================================================+
```

**Contrast: existing priority board** (same surface, different grouping — still in scope as the alternate mode):

```
+==========================================================================================+
|  Tasks area   view: [ List ] [ Board * ]     Board:  (•) Priority   ( ) Group by due     |
+==========================================================================================+
|                                                                                          |
|      P1                    P2                    P3                    P4              |
|  +----------------+  +----------------+  +----------------+  +----------------+         |
|  | [ ] Critical   |  | [ ] Important  |  | [ ] Normal     |  | [ ] Low / info |         |
|  |     due today  |  |     no due     |  |     due Fri    |  |     due +2wk   |         |
|  +----------------+  +----------------+  +----------------+  +----------------+         |
|                                                                                          |
|       drag  ==>  changes encoded priority (current behaviour; unchanged by this story) |
+==========================================================================================+
```

## User Story

As a user planning my week in Google Tasks, I want the **board grouped by due horizon** and to **drag tasks between columns to reschedule**, so that the board answers “what should I touch next?” and each move is a real date change synced to Google.

## Acceptance Criteria

- [ ] In **board** view, the user can choose **grouping**: **By priority** (existing P1–P4 columns) **or** **By due date** (four lanes: Overdue, Today, This week, Later / No date). The choice is visible, persisted for the session (local preference acceptable), and documented in in-app help if a hint exists today.
- [ ] **Due-date lane** columns use **clear titles** and match the lane rules above; tasks appear in **exactly one** lane for incomplete items (no duplicates; edge cases at week boundaries are test-covered).
- [ ] **Drag-and-drop** between lanes **updates the task due** via the existing tasks API (same validation as manual due edit). **Drop target → due mapping** is fixed, documented in code, and covered by tests. **Minimum mapping**: **Today** → due on local today; **This week** → due on a date still inside the current week (implementation picks a single rule, e.g. **tomorrow** if tomorrow is in the same week, otherwise **last day of the current week**); **Later / No date** → set due to the **first calendar day after the current week** (local), per **Clarifying Questions** — **do not clear** due via this drop target.
- [ ] **Overdue** lane: tasks enter by date only; **dropping onto Overdue** sets the task **due** to **yesterday** (user’s **local calendar** date), so the task **shows as overdue**; document in code and tests (including timezone edge cases at day boundary).
- [ ] After a successful drop, the UI **optimistically** reflects the new lane; on API failure, **revert** and show an error consistent with other task updates.
- [ ] **Priority** remains visible on cards in due-lane mode (chips/badges per [US-027](US-027-consistent-dates-priority-across-views.md) when that story is done; until then, at least no regression from current card content).
- [ ] **Accessibility**: lanes and draggable cards have appropriate **roles/labels**; keyboard-only users can **change due** via existing edit paths if full DnD a11y is out of scope — document gap if any.
- [ ] **i18n**: column titles and any new strings in **English and French**.
- [ ] **Automated tests**: unit tests for lane bucketing (timezone/week-boundary fixtures); feature or JS tests for grouping logic; PHP tests if server rules are added.

## Business Value

Matches how users already reason about Google Tasks (**due dates**), makes the board a **planning surface** rather than only a priority grid, and turns column moves into **meaningful reschedule** actions.

## Technical Requirements

- Reuse or extend **`parseDueDate`**, **`isTaskOverdue`**, **`isTaskDueToday`** in `apps/google-tasks/resources/js/utils/taskFilters.js` (add `groupTasksByDueLane` or equivalent); avoid duplicating calendar math.
- **Google Tasks API** / backend: due updates already supported on task update (`due` in `TasksController`); ensure patch payload matches **RFC3339 date** format already in use.
- **Completed tasks**: respect current filter (`needsAction` vs `completed`); do not show completed tasks in overdue/today lanes unless filter shows completed (then define lane by due date regardless of status, or hide board — choose smallest consistent behavior and test it).
- Consider interaction with [US-030](US-030-snooze-defer-presets.md) (defer presets): due-lane DnD is complementary; no requirement to ship presets first.

## Reference Documents

- [INDEX.md](../../INDEX.md) — backlog entry points
- [US-012](US-012-filters-kanban.md) — existing Kanban
- [US-027](US-027-consistent-dates-priority-across-views.md) — date/priority consistency on cards

## Technical References

- `apps/google-tasks/resources/js/utils/taskFilters.js` — due parsing, `groupTasksByPriority`
- `apps/google-tasks/resources/js/Components/TasksKanbanBoard.vue` — board UI and DnD
- `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` — board mode, `kanbanBuckets`, `onKanbanDropPriority`
- `apps/google-tasks/app/Http/Controllers/TasksController.php` — `due` validation on create/update

## Dependencies

- [US-012](US-012-filters-kanban.md) (done) — board and filters foundation
- Soft coordination with [US-027](US-027-consistent-dates-priority-across-views.md) for card chrome consistency

## Clarifying Questions

*AI: Before starting implementation, ask the user clarifying questions. Document questions and answers here after the user responds.*

- **Q**: For **Later / No date**, should drop **clear** the due date or assign a **default future** date (e.g. end of next week)?
- **A**: **Assign a default future due** — **do not clear** on drop into this lane. Use the **first calendar day after the current week** (same week definition as lane bucketing in this story: Monday–Sunday local) as the **canonical** mapped due, unless implementation discovers an API constraint—then use the **nearest documented future date** and record in **Notes**. Align with Acceptance Criteria “first day after the current week” mapping.
- **Date**: 2026-03-22

- **Q**: **Overdue** lane — valid **drop target** with **backdated** due, or **read-only** column?
- **A**: **Valid drop target** — dropping onto **Overdue** sets due to **yesterday** (local calendar day) so the task appears overdue.
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

**Ready for sprint planning**: Yes.

## Notes

- If **both** priority and due boards are kept, the **default** for new users can remain **priority** to avoid surprising existing users; optional: remember last-used grouping in `localStorage`.
- Week definition **Monday–Sunday** matches common work-week planning; if product prefers **Sunday-start** week, update AC and tests in one place.

## Acceptance Verification

**Complete before marking status as Done.** Verify each acceptance criterion is met, then mark with `[x]`.

- [ ] All acceptance criteria above verified as met
- [ ] Each criterion tested or inspected and confirmed

## History

- 2026-03-21 - Created (due-date lanes Kanban + reschedule DnD)
- 2026-03-22 - Added ASCII layout diagrams (due lanes vs priority board)
- 2026-03-22 - Clarified: **Later / No date** drop → **default future due** (first day after current week); **not** clear
- 2026-03-22 - Clarified: **Overdue** drop → due set to **yesterday** (local)
- 2026-03-22 - Backlog refinement: DoR recorded
- 2026-03-22 - Assigned to [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md) (all open backlog stories in sprint bucket)
