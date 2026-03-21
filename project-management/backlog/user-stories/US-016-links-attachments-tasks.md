---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-016 - Links and Attachments in Tasks

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
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

- [x] Detect URLs in notes; render as links; open in new tab.
- [x] Document what “attachment” means given API constraints (no binary upload if unsupported).
- [x] Optional: paste handler for quick link add.

## Dependencies

- [US-008](US-008-google-tasks-sync-engine.md)

## History

- 2026-03-21 - Created from discovery questionnaire
- 2026-03-21 - Implemented: `TaskNotesRichText` + `linkifyNotes` (http/https only); optional notes field on create with URL paste append; `app/Services/Google/README.md` documents API limits; `TasksSyncTest` covers store with notes
