---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-016 - Links and Attachments in Tasks

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🟡 Medium  
**Story Points**: 3  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 3](../../sprints/sprint-03-google-tasks-productivity.md)

## Description

Support **URLs and Google-friendly links** in task notes (Tasks API stores notes as text; “attachments” may mean link previews or Drive links in notes — scope per API limits). Deliver click-safe rendering and optional rich display.

## User Story

As a user, I want to attach links to tasks, so that I can jump to related docs or pages.

## Acceptance Criteria

- [ ] Detect URLs in notes; render as links; open in new tab.
- [ ] Document what “attachment” means given API constraints (no binary upload if unsupported).
- [ ] Optional: paste handler for quick link add.

## Dependencies

- [US-008](US-008-google-tasks-sync-engine.md)

## History

- 2026-03-21 - Created from discovery questionnaire
