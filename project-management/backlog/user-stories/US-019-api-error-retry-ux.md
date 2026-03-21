---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-019 - Google API Error Handling and Retry UX

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟠 High  
**Story Points**: 3  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 4](../../sprints/sprint-04-google-tasks-quality-and-v2.md)

## Description

When Google Tasks API fails (network, 401, 429, 5xx), show **clear user-visible errors** and **retry** affordances; align optimistic UI rollback with [US-008](US-008-google-tasks-sync-engine.md).

## User Story

As a user, I want to understand what failed and retry safely, so that transient Google issues do not feel like data loss.

## Acceptance Criteria

- [x] Mapped error types to human messages (auth expired, rate limit, generic).
- [x] Retry button or automatic bounded retry for idempotent reads; destructive actions confirm.
- [x] Logging: no sensitive tokens in logs; **PII in logs** follows product decision in [US-021](US-021-disconnect-purge-logging.md).

## Dependencies

- [US-008](US-008-google-tasks-sync-engine.md)

## History

- 2026-03-21 - Created from discovery questionnaire
- 2026-03-21 - Implemented: `GoogleTasksErrorCode`, structured JSON (`code`, `message`, `retry_after` for 429), `Log::warning` without response bodies; UI `messageFromAxiosError`, `withReadRetry` for reads, Retry button; i18n EN/FR
