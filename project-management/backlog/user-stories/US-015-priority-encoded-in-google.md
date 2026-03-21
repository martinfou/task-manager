---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-015 - Priority Encoded into Google Tasks

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟡 Medium  
**Story Points**: 3  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 3](../../sprints/sprint-03-google-tasks-productivity.md)

## Description

Google Tasks has no native priority field. Encode priority in a way that **round-trips in Google** (e.g. title prefix `P1:` / emoji / structured first line in notes) and document the convention. App parses and displays priority UI.

## User Story

As a user, I want priorities stored in Google so that other clients or future tools see them, not only this app’s database.

## Acceptance Criteria

- [x] Documented encoding scheme; reversible parse from API payloads.
- [x] UI to set priority without raw title editing (writes through encoding).
- [x] Migration path for existing tasks without priority (default level).

## Dependencies

- [US-008](US-008-google-tasks-sync-engine.md)

## History

- 2026-03-21 - Created from discovery questionnaire
- 2026-03-21 - Implemented `[P1]`–`[P4]` title encoding with `TaskPriorityCodec`, Tasks UI priority selectors (create + per-task), default decode fallback `p3` for unencoded tasks, tests updated, and README documentation added
