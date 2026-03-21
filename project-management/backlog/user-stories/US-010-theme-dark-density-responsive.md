---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-010 - Dark Theme, Density, and Responsive MVP Layout

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⏳ In Progress  
**Priority**: 🟠 High  
**Story Points**: 3  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 2](../../sprints/sprint-02-google-tasks-mvp-foundation.md)

## Description

Ship **dark theme**, **compact vs comfortable** density, and **responsive** layout: powerful on desktop, easy on mobile. MVP is **responsive web only** (PWA deferred to [US-022](US-022-pwa-install-phase.md)).

## User Story

As a user, I want a beautiful, fast UI that adapts to screen size and my density preference, so that I enjoy daily use and long sessions.

## Acceptance Criteria

- [ ] Dark theme as default or first-class toggle; persisted per user (local or server profile).
- [ ] Density switch: **compact** vs **comfortable** (spacing/line height).
- [ ] Breakpoints tested for mobile and desktop; no horizontal scroll on common phone widths for main views.
- [ ] “Beauty” and speed: no blocking synchronous heavy work on main thread for list render.

## Business Value

Matches discovery: speed, ease of use, beauty; TickTick-inspired feel.

## Dependencies

- [US-006](US-006-laravel-inertia-scaffold-deploy.md)

## History

- 2026-03-21 - Created from discovery questionnaire
