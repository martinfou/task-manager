---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-009 - Views — Today, Inbox, and Multi-List Navigation

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🔴 Critical  
**Story Points**: 5  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 2](../../sprints/sprint-02-google-tasks-mvp-foundation.md)

## Description

Deliver TickTick-inspired navigation: **easy movement across multiple Google task lists**, plus **Today** and **Inbox** views derived from task data (definitions documented in app help).

## User Story

As a user with many lists, I want to switch lists and jump to Today/Inbox quickly, so that I can navigate tasks without friction.

## Acceptance Criteria

- [x] Sidebar or equivalent: all Google task lists; clear active list.
- [x] **Today**: tasks due today (and overdue policy stated — e.g. include overdue in Today).
- [x] **Inbox**: defined consistently (e.g. default list or tasks without list assignment per API reality — document behavior).
- [x] Mobile: usable navigation (drawer or bottom nav) per “easy on mobile” discovery.
- [x] Empty states for each view.

## Business Value

Directly matches the one-liner: “easy way to navigate my multiple tasks lists.”

## Technical Requirements

- Inertia pages + Vue components; server provides filtered task payloads for heavy views if needed.

## Dependencies

- [US-008](US-008-google-tasks-sync-engine.md)

## History

- 2026-03-21 - Created from discovery questionnaire
- 2026-03-21 - Implemented `TaskViewAggregator` + `/tasks/data/views/today|inbox`; Tasks shell with desktop sidebar, mobile bottom nav + list drawer; in-app help copy; empty states; status ✅ Done
