# User Story: US-051 — Recurring Tasks via Notes Encoding

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🟠 High  
**Story Points**: 5  
**Created**: 2026-06-21  
**Updated**: 2026-06-21  
**Assigned Sprint**: Backlog

## Description

Recurring tasks encoded in the Google Tasks `notes` field using a simple syntax like `[weekly:fri]` or `[monthly:15]`. When Martin completes a recurring task, the app automatically creates a new instance with the next due date. This works within Google Tasks API constraints (no custom fields needed).

## User Story

As a user of Google Tasks (which lacks recurring tasks),  
I want to mark a task as repeating by adding a simple code like `[weekly:fri]` in its notes,  
so that completing it automatically creates the next occurrence without manual re-entry.

## Acceptance Criteria

- [ ] `[daily]` in notes = recreates next day on completion
- [ ] `[weekly:fri]` = recreates every Friday
- [ ] `[weekly:mon,wed,fri]` = recreates on specified days only
- [ ] `[monthly:15]` = recreates on the 15th of each month
- [ ] `[weekday]` = recreates next weekday (Mon-Fri)
- [ ] On task completion, the app creates a new task with: same title, same notes (with recurrence code), next calculated due date, same list
- [ ] Recurrence icon (🔄) shown on the task row to indicate recurring
- [ ] If Martin completes the same recurring task twice by accident, the duplicate detection (US-033) catches it
- [ ] Graceful: if the recurrence regex isn't found in notes, the task completes normally (no effect)
- [ ] Works offline (recurrence parsing is client-side, API call for creation happens when online)

## Business Value

Google Tasks famously lacks recurring tasks. This is the #1 request from every Google Tasks user. Encoding rules in the `notes` field works within the API's constraints. Martin has weekly rituals (weekly review, trading prep, blog post drafts) that currently require manual re-creation.

## Technical Requirements

- New `RecurrenceParser` service class (`app/Services/RecurrenceParser.php`) — ~100 lines
- Recurrence patterns: cron-lite (daily, weekly:day, monthly:day, weekday)
- On completion (existing `updateTask` endpoint), check notes for `[...code...]` pattern
- If matched, call `insertTask` with same title/notes + calculated next due date
- No new DB tables — encoded in Google Tasks `notes` field
- Edge case: task with `[weekly:fri]` completed on Thursday → creates for this Friday, not next

## Dependencies

- None — standalone feature

## History

- 2026-06-21 — Created
