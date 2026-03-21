---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [product-backlog]
---

# Sprint 2: Google Tasks MVP — Foundation

[← Back to Product Backlog](../backlog/product-backlog.md)

**Sprint Goal**: Ship a deployable Laravel + Inertia + Vue app with Google OAuth (combined Tasks scope), a working Tasks sync engine (polling + optimistic UI), core navigation (Today / Inbox / lists), and baseline responsive dark UI.

**Duration**: 2026-03-21 — 2026-04-04 (2 weeks)  
**Team Velocity**: 9 points carried from [Sprint 1](sprint-01-ai-workflow-foundation.md); Sprint 2 committed **26** points — stretch; prioritize US-006 → US-008 first if capacity tight.  
**Sprint Planning Date**: 2026-03-21  
**Sprint Review Date**: 2026-04-04  
**Sprint Retrospective Date**: 2026-04-04

## Sprint planning record (2026-03-21)

**Prerequisites met**: [Sprint 1](sprint-01-ai-workflow-foundation.md) retrospective section completed.

| Step | Result |
|------|--------|
| Backlog metrics | `./project-management/scripts/backlog-metrics.sh --stats` |
| Definition of Ready | US-006–US-010 have acceptance criteria, points, dependencies, links |
| Capacity | Solo dev; 26 pts > historical 9 pts — sequence strictly US-006 → US-007 → US-008 → US-009 → US-010 |
| Branching | Per [ADR-002](../architecture-decision-records/ADR-002-branching-strategy.md): `feature/US-XXX-short-description` per story; first branch created for US-006 |

**Related improvement**: [RI-001](../backlog/retrospective-improvements/RI-001-google-cloud-oauth-checklist.md) (OAuth checklist before deploy).

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
- Scope (26 pts) vs velocity (9) — time-box or spill US-009/US-010 to Sprint 3 if needed

---

## User Stories

### Story 1: US-006 — Laravel Inertia Vue scaffold + deploy — 5 Points

**Backlog**: [US-006](../backlog/user-stories/US-006-laravel-inertia-scaffold-deploy.md)

**Status**: ⏳ In Progress

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-011 | Create Laravel app + require Inertia + Vue 3 + Vite | US-006 | ⭕ |
| T-012 | Document Fly.io and DreamHost deploy paths; `.env.example` | US-006 | ⭕ |
| T-013 | Health route for load balancers | US-006 | ⭕ |

---

### Story 2: US-007 — Google OAuth combined flow + tokens — 5 Points

**Backlog**: [US-007](../backlog/user-stories/US-007-google-oauth-combined-flow.md)

**Status**: ⏳ In Progress

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-014 | Configure Socialite Google + combined Tasks scopes | US-007 | ⭕ |
| T-015 | Persist encrypted tokens; user model | US-007 | ⭕ |
| T-016 | Document OAuth console setup + RI-001 checklist link | US-007 | ⭕ |

---

### Story 3: US-008 — Tasks sync engine — 8 Points

**Backlog**: [US-008](../backlog/user-stories/US-008-google-tasks-sync-engine.md)

**Status**: ⏳ In Progress

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-017 | Google Tasks API service (lists, tasks CRUD) | US-008 | ⭕ |
| T-018 | Polling + backoff; optimistic UI contract | US-008 | ⭕ |
| T-019 | Subtasks, due dates, recurrence per API | US-008 | ⭕ |

---

### Story 4: US-009 — Today, Inbox, multi-list navigation — 5 Points

**Backlog**: [US-009](../backlog/user-stories/US-009-views-today-inbox-lists.md)

**Status**: ⏳ In Progress

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-020 | App shell: list switcher + task list view | US-009 | ⭕ |
| T-021 | Today + Inbox views with documented rules | US-009 | ⭕ |
| T-022 | Responsive navigation (drawer / bottom bar) | US-009 | ⭕ |

---

### Story 5: US-010 — Dark theme, density, responsive MVP — 3 Points

**Backlog**: [US-010](../backlog/user-stories/US-010-theme-dark-density-responsive.md)

**Status**: ⏳ In Progress

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-023 | Dark theme + toggle; persisted preference | US-010 | ⭕ |
| T-024 | Compact vs comfortable density | US-010 | ⭕ |

---

## Sprint Summary

**Total Story Points**: 26  
**Total Task Points**: 14 tasks (T-011–T-024)

**Sprint Burndown**: (update during sprint)

**Sprint Review Notes**: (fill at review on 2026-04-04)

**Sprint Retrospective Notes**: (fill at retrospective on 2026-04-04)

---

## Status Values

- ⭕ **To Do**: Not yet begun
- ⏳ **In Progress**: Currently being worked on
- ✅ **Done**: Done and verified
