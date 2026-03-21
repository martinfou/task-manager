---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-014 - Keyboard Shortcuts (Desktop Power User)

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟡 Medium  
**Story Points**: 3  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 3](../../sprints/sprint-03-google-tasks-productivity.md)

## Description

Provide **keyboard shortcuts** for core actions (navigate, complete, create, search, switch list) with a **help overlay** listing bindings.

## User Story

As a desktop user, I want to drive the app from the keyboard, so that I can work faster than with mouse-only.

## Acceptance Criteria

- [x] Shortcut set covers: new task, complete, move focus, open search, go to Today/Inbox (minimum agreed set).
- [x] `?` or Ctrl+/ opens shortcut help.
- [x] No conflicts with browser defaults where avoidable; document overrides.

## Dependencies

- [US-009](US-009-views-today-inbox-lists.md)

## History

- 2026-03-21 - Created from discovery questionnaire
- 2026-03-21 - Implemented: `useTasksKeyboardShortcuts`, `TasksKeyboardShortcutsHelp`, typing-context guard, g-chord navigation, README + i18n
