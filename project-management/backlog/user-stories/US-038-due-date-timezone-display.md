---
template_version: 1.1.0
last_updated: 2026-03-22
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-038 — Due Date/Time Display and Timezone

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🟠 High  
**Story Points**: 5  
**Created**: 2026-03-22  
**Updated**: 2026-03-22  
**Assigned Sprint**: [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md)

## Description

Users report that **due times look wrong**—for example **always around 8:00 p.m.** on tasks—suggesting the UI may be showing a **converted UTC instant** (Google Tasks often uses **midnight UTC** for **date-only** due values) instead of a **calendar day** or **true local time** the user expects.

This story **investigates** how `due` is returned from the Google Tasks API and passed through the Laravel API to the client, then **fixes display and any filtering** so behavior matches **user locale / local timezone** and aligns with Google Tasks semantics (date-only vs date-time). Work should stay consistent with [US-027](US-027-consistent-dates-priority-across-views.md) where it overlaps.

## User Story

As a user who sets due dates in Google Tasks, I want **due dates and times to reflect my timezone and intent** (all-day vs specific time), so that I am not misled by identical or incorrect clock times on every task.

## Acceptance Criteria

- [ ] **Root cause documented** (short note in `apps/google-tasks/docs/` or inline in PR): e.g. RFC3339 `…T00:00:00.000Z` for date-only dues, client using `Intl.DateTimeFormat` with **time** on those values, etc.
- [ ] **Display rules** implemented and consistent across **list**, **Kanban**, **task detail/edit**, and **command palette** previews (where due is shown):
  - **Date-only** (or API convention for “no specific time”): show **date without a misleading time**, or an explicit **local end-of-day / start-of-day** policy **documented** and applied uniformly—not a raw UTC midnight shifted to local unless that matches product choice.
  - **Due with a real time**: show **correct local time** using the user’s locale/timezone (`Intl` / browser timezone).
- [ ] **Filtering / “today” / overdue** logic ([`taskFilters.js`](../../../apps/google-tasks/resources/js/utils/taskFilters.js) and related) uses **calendar-day boundaries in the local timezone** (or documented alignment with Google’s model)—no off-by-one from UTC-only interpretation.
- [ ] **Automated tests** cover at least: one **UTC-offset** case that previously produced the wrong wall-clock label, and **date-only** vs **time-specific** parsing/formatting helpers if introduced.
- [ ] **i18n**: any new user-visible strings in **en** + **fr** (`resources/js/locales/`).
- [ ] **Regression**: defer/snooze presets and API payloads ([`deferPresets.js`](../../../apps/google-tasks/resources/js/utils/deferPresets.js)) still match Google Tasks API expectations after changes.

## Business Value

Correct due information is **core trust** for a tasks client; wrong or identical times erode confidence and cause missed or confused deadlines.

## Technical Requirements

- Prefer a **single formatting pipeline** (composable or util) for `task.due` rather than ad hoc `new Date` + `Intl` in many components.
- Respect **browser locale** and **local timezone** (no hard-coded offset); optional future server-side TZ is out of scope unless discovery shows it is required.
- Do not log raw PII beyond existing patterns.

## Reference Documents

- [US-027](US-027-consistent-dates-priority-across-views.md) — consistency across views
- [US-030](US-030-snooze-defer-presets.md) — due presets

## Technical References

- `apps/google-tasks/resources/js/composables/useLocaleDate.js` — `formatDateTime`
- `apps/google-tasks/resources/js/utils/taskFilters.js` — `parseDueDate`, overdue/today
- `apps/google-tasks/resources/js/utils/deferPresets.js` — RFC3339 for Google `due`
- `apps/google-tasks/app/Http/Controllers/TasksController.php` — `decodeTasks` / task payloads
- Google Tasks API: `tasks.due` (RFC3339)

## Dependencies

- None blocking; coordinate with [US-027](US-027-consistent-dates-priority-across-views.md) if both touch the same files in one sprint.

## Clarifying Questions

*Document answers during implementation if product choices fork (e.g. show “Mar 22” only vs “Mar 22, 12:00 AM” local).*

## Notes

- Symptom **“always 8:00 p.m.”** often appears when **UTC midnight** on the stored due date is formatted in **Americas** timezones (e.g. Eastern: previous **evening**). Confirm with the reporter’s actual timezone during QA.
- If Google distinguishes **all-day** via time component, mirror that; if not, use **heuristic** (e.g. `T00:00:00.000Z` + no other time source) only with tests and docs.

## Acceptance Verification

- [ ] All acceptance criteria above verified as met
- [ ] Each criterion tested or inspected and confirmed

## History

- 2026-03-22 - Created (due time always wrong / timezone; e.g. 8:00 p.m. display issue)
- 2026-03-22 - Assigned to [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md) (full backlog pulled into active sprint)
