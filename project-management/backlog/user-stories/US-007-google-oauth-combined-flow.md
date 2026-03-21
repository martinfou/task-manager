---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-007 - Google OAuth Combined Flow and Token Storage

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⏳ In Progress  
**Priority**: 🔴 Critical  
**Story Points**: 5  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 2](../../sprints/sprint-02-google-tasks-mvp-foundation.md)

## Description

Implement a **single** Google sign-in flow that requests identity plus **Google Tasks API** scopes in one consent experience. Persist refresh tokens using the **simplest secure approach** (Laravel encryption + DB).

## User Story

As a solo user with one Google account per app user, I want to sign in once and grant Tasks access without a second OAuth dance, so that onboarding feels seamless.

## Acceptance Criteria

- [ ] Laravel Socialite (or equivalent) configured for Google; combined scopes include Tasks read/write as required by the API.
- [ ] User record stores encrypted refresh token (and access token expiry if used); no plaintext secrets in DB or logs.
- [ ] Sign-out clears session; **disconnect** behavior covered in [US-021](US-021-disconnect-purge-logging.md) for full purge.
- [ ] Document OAuth client setup in Google Cloud Console (redirect URIs for local + production).

## Business Value

Core prerequisite for any Tasks data; matches “one combined flow” from discovery.

## Technical Requirements

- Use Laravel’s `encrypt()` / `Crypt` for tokens at rest; `APP_KEY` required in all environments.
- HTTPS in production; redirect URIs whitelisted.

## Dependencies

- [US-006](US-006-laravel-inertia-scaffold-deploy.md) (scaffold)

## History

- 2026-03-21 - Created from discovery questionnaire
