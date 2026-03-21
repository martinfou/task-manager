---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-022 - PWA Install (Post-MVP Phase)

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟢 Low  
**Story Points**: 5  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: Backlog

## Description

After responsive MVP, add **PWA** capabilities: install prompt, service worker shell for assets (online-only v1 — no offline task queue until product decides).

## User Story

As a mobile user, I want to install the app to my home screen, so that it feels like a native shortcut.

## Acceptance Criteria

- [ ] Web app manifest, icons, theme color.
- [ ] Service worker strategy documented (cache-first for assets; no false offline Tasks promise).
- [ ] “Next phase” alignment: offline queue out of scope unless new story.

## Dependencies

- [US-010](US-010-theme-dark-density-responsive.md)

## History

- 2026-03-21 - Created from discovery questionnaire
- 2026-03-21 - Implemented: `PwaController` + `/manifest.webmanifest`, `public/sw.js` + route, icons, `app.blade.php` meta/link, prod-only SW registration, [docs/PWA.md](../../../apps/google-tasks/docs/PWA.md)
