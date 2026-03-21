---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-017 - Bulk Actions on Tasks

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟠 High  
**Story Points**: 5  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 3](../../sprints/sprint-03-google-tasks-productivity.md)

## Description

Multi-select tasks and apply **bulk complete**, **bulk move** (where API allows), **bulk delete** with confirmation; batch API calls with user-visible progress and error summary.

## User Story

As a user cleaning up or replanning, I want to act on many tasks at once, so that repetitive work is fast.

## Acceptance Criteria

- [x] Multi-select with shift/ctrl patterns on desktop; mobile selection pattern documented.
- [x] At least: complete, delete, move to list (if API supports).
- [x] Confirmation for destructive actions; partial failure reporting.

## Dependencies

- [US-008](US-008-google-tasks-sync-engine.md)

## History

- 2026-03-21 - Created from discovery questionnaire
- 2026-03-21 - Implemented: selection column + Ctrl/Cmd+click and Shift+click; bulk bar (complete, move, delete); `POST .../move` + `TasksController::moveTask`; delete confirm + partial-failure modal; README mobile note; `TasksSyncTest::test_move_task_proxies_to_google_api`
