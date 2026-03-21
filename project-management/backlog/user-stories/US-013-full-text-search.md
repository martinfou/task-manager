---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-013 - Full-Text Search Across Tasks

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟠 High  
**Story Points**: 3  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 3](../../sprints/sprint-03-google-tasks-productivity.md)

## Description

Search titles and notes across visible/synced tasks (server-side index or client-side over fetched data for MVP scope — choose and document).

## User Story

As a user, I want to find tasks by typing keywords, so that I can locate work quickly.

## Acceptance Criteria

- [x] Search box available from main shell; keyboard shortcut optional (see [US-014](US-014-keyboard-shortcuts.md)).
- [x] Results list with context (list name, snippet).
- [x] Document limitations (e.g. tasks not loaded in memory may require server index).

## Dependencies

- [US-008](US-008-google-tasks-sync-engine.md)

## History

- 2026-03-21 - Created from discovery questionnaire
- 2026-03-21 - Implemented: `GET /tasks/data/search`, `TaskSearcher` service, Tasks header search UI, README limits; tests `TaskSearchTest.php`
