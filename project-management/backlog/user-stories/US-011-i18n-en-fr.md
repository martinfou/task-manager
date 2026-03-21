---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-011 - Internationalization (English and French)

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟠 High  
**Story Points**: 3  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 3](../../sprints/sprint-03-google-tasks-productivity.md)

## Description

Application UI strings in **English** and **French**; user-selectable locale persisted. Task content from Google remains as-is (user-authored).

## User Story

As a bilingual user, I want to use the interface in English or French, so that the app matches my language preference.

## Acceptance Criteria

- [x] Vue/i18n (or Laravel localization for server-rendered strings) with consistent key structure.
- [x] Language switcher; preference stored (user profile or browser + server).
- [x] Date/time formatting respects active locale where applicable.
- [x] Document translation contribution process in README (minimal).

## Dependencies

- [US-006](US-006-laravel-inertia-scaffold-deploy.md)

## History

- 2026-03-21 - Created from discovery questionnaire
- 2026-03-21 - Implemented: vue-i18n + `en`/`fr` JSON, `SetLocale` + `POST /locale` (cookie + `users.locale`), `LocaleSwitcher`, README section; tests in `LocaleTest.php`
