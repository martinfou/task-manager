---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-021 - Disconnect, Data Purge, and Logging Policy

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟠 High  
**Story Points**: 2  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 4](../../sprints/sprint-04-google-tasks-quality-and-v2.md)

## Description

On **disconnect** or account deletion: purge **tokens** and any **cached task/index data** per discovery. Define **logging policy**: discovery allowed task titles in logs — implement **redaction** option or document risk for production.

## User Story

As a privacy-conscious user, I want my data removed when I disconnect, and predictable logging behavior.

## Acceptance Criteria

- [x] Disconnect flow removes OAuth tokens and semantic index rows if [US-018](US-018-semantic-search-task-index.md) exists.
- [x] Documented data retention; GDPR-friendly baseline for solo deploy.
- [x] Logging policy in README: what is logged in dev vs prod; avoid raw titles in prod if chosen.

## Dependencies

- [US-007](US-007-google-oauth-combined-flow.md)

## History

- 2026-03-21 - Created from discovery questionnaire
- 2026-03-21 - Implemented: `GoogleTasksConnectionPurgeService`, Profile **Disconnect Google** (`POST /profile/google/disconnect`), `docs/DATA_RETENTION.md`, README logging/retention section, feature tests
