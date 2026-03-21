---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-012 - Filters and Kanban View

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟠 High  
**Story Points**: 8  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 3](../../sprints/sprint-03-google-tasks-productivity.md)

## Description

**Filters** for task lists (e.g. due date range, completion state, list) and a **Kanban** view mapped sensibly onto Google Tasks (columns strategy documented — e.g. by list or by custom segments that map to API fields).

## User Story

As a power user, I want filters and a board view, so that I can triage work like TickTick without leaving Google Tasks data.

## Acceptance Criteria

- [x] Filter panel or query bar with clear, testable filter combinations.
- [x] Kanban: columns definition documented; drag-and-drop updates tasks via API where supported (or explain limitations).
- [x] Performance: large lists handled without unusable lag (virtualization or pagination if needed).

## Technical Requirements

- No tags (per discovery); filters use API-backed fields only unless product adds local-only metadata later (out of scope).

## Dependencies

- [US-008](US-008-google-tasks-sync-engine.md), [US-009](US-009-views-today-inbox-lists.md)

## History

- 2026-03-21 - Created from discovery questionnaire
- 2026-03-21 - Implemented: client-side filters (status, due, priority, list on Today); list/board toggle; P1–P4 Kanban with DnD → `updatePriority`; `taskFilters.js`; scrollable columns; README + in-app help
