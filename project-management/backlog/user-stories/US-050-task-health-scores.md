# User Story: US-050 — Task Health Scores

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🟡 Medium  
**Story Points**: 3  
**Created**: 2026-06-21  
**Updated**: 2026-06-21  
**Assigned Sprint**: Backlog

## Description

Visual indicators that show how "healthy" each task is based on age and status. A task in "Next Actions" for 7+ days gets a yellow badge. 14+ days = red. Overdue + not touched = stale warning. Inspired by Linear's issue health scores.

## User Story

As a GTD practitioner,  
I want to see at a glance which tasks are stagnating in my lists,  
so that I can review, re-prioritize, or delete them during my weekly review.

## Acceptance Criteria

- [ ] Task in "Next Actions" list with no update in 7+ days shows 🟡 (yellow dot / "1w")
- [ ] Task in "Next Actions" list with no update in 14+ days shows 🔴 (red dot / "2w")
- [ ] Overdue task with no recent modification shows ⏰ (clock icon)
- [ ] Health indicators appear in all task list views
- [ ] Health computed client-side (dates from Google Tasks `updated` field)
- [ ] Hovering shows tooltip: "Not touched in 12 days" / "Overdue since Jun 15"
- [ ] Bulk action: "Select all stale tasks" button available in overflow menu
- [ ] Can be disabled in Settings (some users prefer clean lists)

## Business Value

Tasks go stale silently. Martin's GTD practice requires regular review, but he doesn't always remember which items to look at. Health scores surface the aging tasks automatically — no mental tracking needed.

## Technical Requirements

- 100% client-side computation from task `updated` timestamps (already in API response)
- No backend changes
- Color-coded dots match existing Tailwind theme (slate/gray)

## Dependencies

- None

## History

- 2026-06-21 — Created
