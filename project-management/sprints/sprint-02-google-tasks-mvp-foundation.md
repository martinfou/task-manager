---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [product-backlog]
---

# Sprint 2: Google Tasks MVP — Foundation

[← Back to Product Backlog](../backlog/product-backlog.md)

**Sprint Goal**: Ship a deployable Laravel + Inertia + Vue app with Google OAuth (combined Tasks scope), a working Tasks sync engine (polling + optimistic UI), core navigation (Today / Inbox / lists), and baseline responsive dark UI.

**Duration**: 2026-03-21 start — target short cadence (“days” per discovery); adjust end date at planning.  
**Team Velocity**: TBD (first delivery sprint for product)  
**Sprint Planning Date**: 2026-03-21  
**Sprint Review Date**: (set at planning)  
**Sprint Retrospective Date**: (set at planning)

## Sprint Overview

**Focus Areas**:
- Stack: Laravel, Inertia, Vue 3; deploy to Fly.io or DreamHost
- Auth: one Google OAuth flow with Tasks API access
- Sync: Google as source of truth; polling + optimistic updates; ~5s target after edits when healthy
- UX: multi-list navigation, Today/Inbox, dark theme + density + responsive MVP

**Key Deliverables**:
- Running deployed app with sign-in
- Tasks and lists CRUD through API with documented sync behavior
- Usable shell on desktop and mobile widths

**Dependencies**:
- Google Cloud OAuth client and Tasks API enabled
- US-006 before US-007/US-008

**Risks & Blockers**:
- Google API quotas and rate limits vs “5 second” feel — document honest behavior under 429
- Inertia + OAuth callback URLs must match deployment URLs

---

## User Stories (this sprint)

| ID | Title | Points | Status |
|----|-------|--------|--------|
| [US-006](../backlog/user-stories/US-006-laravel-inertia-scaffold-deploy.md) | Laravel Inertia Vue scaffold + deploy | 5 | ⭕ |
| [US-007](../backlog/user-stories/US-007-google-oauth-combined-flow.md) | Google OAuth combined flow + tokens | 5 | ⭕ |
| [US-008](../backlog/user-stories/US-008-google-tasks-sync-engine.md) | Tasks sync engine | 8 | ⭕ |
| [US-009](../backlog/user-stories/US-009-views-today-inbox-lists.md) | Today, Inbox, multi-list navigation | 5 | ⭕ |
| [US-010](../backlog/user-stories/US-010-theme-dark-density-responsive.md) | Dark theme, density, responsive MVP | 3 | ⭕ |

**Total Story Points**: 26

---

## Sprint Summary

**Sprint Burndown**: (update during sprint)

**Sprint Review Notes**: (fill at review)

**Sprint Retrospective Notes**: (fill at retrospective)

---

## Status Values

- ⭕ **To Do**: Not yet begun
- ⏳ **In Progress**: Currently being worked on
- ✅ **Done**: Done and verified
