---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-020 - Test Pyramid and CI

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟠 High  
**Story Points**: 5  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 4](../../sprints/sprint-04-google-tasks-quality-and-v2.md)

## Description

Implement **test pyramid**: PHPUnit for Laravel domain and HTTP, frontend unit tests for Vue utilities, narrow E2E (e.g. Playwright) for critical paths. Run in **CI** on push/PR.

## User Story

As a maintainer, I want automated tests and CI, so that refactors do not break Tasks sync or auth.

## Acceptance Criteria

- [x] CI workflow runs unit/feature tests + frontend tests + lint.
- [x] At least one E2E smoke: login stub or OAuth mock strategy documented.
- [x] Coverage targets documented (pragmatic, not vanity %).

## Dependencies

- [US-006](US-006-laravel-inertia-scaffold-deploy.md)

## History

- 2026-03-21 - Created from discovery questionnaire
- 2026-03-21 - Implemented: Vitest (`resources/js/utils/*.test.js`), ESLint + `route` global, `composer lint` (Pint), Playwright `e2e/smoke.spec.ts` (health + login, no OAuth), `docs/TESTING.md`, CI workflow extended
