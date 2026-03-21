---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-006 - Laravel Inertia Vue Scaffold and Deployment

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🔴 Critical  
**Story Points**: 5  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 2](../../sprints/sprint-02-google-tasks-mvp-foundation.md)

## Description

Bootstrap the Google Tasks web client as a Laravel application with Inertia.js and Vue 3, ready to deploy to Fly.io or DreamHost (document both paths).

## User Story

As a solo developer, I want a production-ready Laravel + Inertia + Vue skeleton with environment-based configuration, so that I can ship the app quickly and iterate without rework.

## Acceptance Criteria

- [ ] Laravel (current stable LTS or latest stable per team choice) with Vue 3 + Inertia; Vite build for frontend assets.
- [ ] Repository layout separates concerns (e.g. `app/` services for Google API, `resources/js/` for Vue pages).
- [ ] `.env.example` documents required keys (app URL, Google OAuth placeholders, database).
- [ ] Deployment notes: **Fly.io** and **DreamHost** — one primary target documented step-by-step; alternate summarized.
- [ ] Health check route (e.g. `/up` or `/health`) for load balancers.
- [ ] Basic error page styling consistent with future app shell (no blank Laravel default only).

## Business Value

Unblocks all feature work and reduces deployment risk for a fast MVP (“in days”).

## Technical Requirements

- PHP and Node versions pinned in README or `composer.json` / `package.json` engines.
- SQLite or MySQL acceptable for solo MVP; migrations runnable in CI/deploy.

## Reference Documents

- [Discovery responses](../../../docs/google-tasks-discovery/responses-submitted-2026-03-21.json)

## Dependencies

- None

## History

- 2026-03-21 - Created from discovery questionnaire
