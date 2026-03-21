---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-008 - Google Tasks Sync Engine (Polling + Optimistic UI)

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🔴 Critical  
**Story Points**: 8  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 2](../../sprints/sprint-02-google-tasks-mvp-foundation.md)

## Description

Google Tasks is the **source of truth**. Implement server-side integration with the Google Tasks API: list task lists and tasks, create/update/complete/delete, support **subtasks only as the API allows**, and full **due dates and recurrence** fields. Use **polling + optimistic UI**; batch requests where helpful; target **visible sync within ~5 seconds** under normal conditions after local changes (best-effort; document limits).

## User Story

As a user, I want edits to feel instant while still matching Google Tasks within seconds, so that the app feels fast without lying about sync state.

## Acceptance Criteria

- [x] Service layer wraps Tasks API (lists, tasks, insert/patch/delete, move as needed).
- [x] Polling strategy defined (interval + backoff on 429); no offline queue required for v1 (online-only).
- [x] Optimistic updates in UI with rollback/reconcile on API error.
- [x] Subtasks, due dates, and recurrence: parity with **supported** API fields only.
- [x] Performance note in README: expected latency vs Google; **5 second** target as design goal, not a hard SLA if API throttles.

## Business Value

Core product differentiator versus raw tasks.google.com — reliable sync is mandatory.

## Technical Requirements

- Google API PHP client or HTTP client with token refresh handled server-side.
- Rate-limit handling: batch + exponential backoff per discovery.

## Dependencies

- [US-007](US-007-google-oauth-combined-flow.md)

## History

- 2026-03-21 - Created from discovery questionnaire
- 2026-03-21 - Implemented `GoogleTasksClient` + token refresh cache; `/tasks` + `/tasks/data/*` JSON API; `Tasks/Index.vue` polling + optimistic CRUD; README performance note; status ✅ Done
