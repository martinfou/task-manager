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
**Sprint Review Date**: 2026-03-21 (held early — implementation completed same day)  
**Sprint Retrospective Date**: 2026-03-21

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

**Status**: ✅ Done

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-011 | Create Laravel app + require Inertia + Vue 3 + Vite | US-006 | ✅ |
| T-012 | Document Fly.io and DreamHost deploy paths; `.env.example` | US-006 | ✅ |
| T-013 | Health route for load balancers | US-006 | ✅ |

---

### Story 2: US-007 — Google OAuth combined flow + tokens — 5 Points

**Backlog**: [US-007](../backlog/user-stories/US-007-google-oauth-combined-flow.md)

**Status**: ✅ Done

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-014 | Configure Socialite Google + combined Tasks scopes | US-007 | ✅ |
| T-015 | Persist encrypted tokens; user model | US-007 | ✅ |
| T-016 | Document OAuth console setup + RI-001 checklist link | US-007 | ✅ |

---

### Story 3: US-008 — Tasks sync engine — 8 Points

**Backlog**: [US-008](../backlog/user-stories/US-008-google-tasks-sync-engine.md)

**Status**: ✅ Done

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-017 | Google Tasks API service (lists, tasks CRUD) | US-008 | ✅ |
| T-018 | Polling + backoff; optimistic UI contract | US-008 | ✅ |
| T-019 | Subtasks, due dates, recurrence per API | US-008 | ✅ |

---

### Story 4: US-009 — Today, Inbox, multi-list navigation — 5 Points

**Backlog**: [US-009](../backlog/user-stories/US-009-views-today-inbox-lists.md)

**Status**: ✅ Done

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-020 | App shell: list switcher + task list view | US-009 | ✅ |
| T-021 | Today + Inbox views with documented rules | US-009 | ✅ |
| T-022 | Responsive navigation (drawer / bottom bar) | US-009 | ✅ |

---

### Story 5: US-010 — Dark theme, density, responsive MVP — 3 Points

**Backlog**: [US-010](../backlog/user-stories/US-010-theme-dark-density-responsive.md)

**Status**: ✅ Done

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-023 | Dark theme + toggle; persisted preference | US-010 | ✅ |
| T-024 | Compact vs comfortable density | US-010 | ✅ |

---

## Sprint Summary

**Total Story Points**: 26  
**Total Task Points**: 14 tasks (T-011–T-024)

**Sprint Burndown**:
- 2026-03-21: 5 story points completed (US-006); 21 points remaining in sprint scope
- 2026-03-21: 5 story points completed (US-007); 16 points remaining in sprint scope
- 2026-03-21: 8 story points completed (US-008); 8 points remaining in sprint scope
- 2026-03-21: 5 story points completed (US-009); 3 points remaining in sprint scope (US-010 only)
- 2026-03-21: 3 story points completed (US-010); **26 / 26** sprint story points delivered (US-006–US-010)

**Sprint Review Notes**:

**Review date**: 2026-03-21  
**Participants**: Development (solo); stakeholders — async documentation review (no live stakeholder session this cycle).

**Sprint goal outcome**: ✅ Met — deployable Laravel + Inertia + Vue app in `apps/google-tasks/` with Google OAuth (Tasks scope), sync engine (polling + optimistic UI), Today / Inbox / multi-list navigation, and dark theme + density + responsive MVP per committed stories.

**Metrics** (from `./project-management/scripts/backlog-metrics.sh --stats` at review time):

- Backlog: 23 items total; 10 ✅ Done; 13 ⭕ To Do; 0 ⏳ In Progress.
- Sprint 2 committed scope: **26** story points across [US-006](../backlog/user-stories/US-006-laravel-inertia-scaffold-deploy.md)–[US-010](../backlog/user-stories/US-010-theme-dark-density-responsive.md) — all delivered.

**What was demonstrated** (acceptance criteria verified by inspection / tests):

| Item | Demonstration / verification |
|------|------------------------------|
| US-006 | `apps/google-tasks/` Laravel 13 + Breeze Vue/Inertia + Vite; `/health` and Laravel `/up`; Fly.io-focused deploy doc; Google OAuth env placeholders; dark-themed error pages. |
| US-007 | Socialite combined identity + Tasks scope; encrypted refresh token and access-token expiry on `users`; `/auth/google` routes and login CTA; `docs/GOOGLE_OAUTH.md` for Cloud Console setup. |
| US-008 | `GoogleTasksClient` + token service; authenticated JSON under `/tasks/data/*`; Tasks page with polling (interval + backoff on 429), optimistic create/complete/delete with rollback; due dates and RRULE through API. |
| US-009 | `/tasks/data/views/today` and `/views/inbox`; desktop sidebar, mobile bottom nav + list drawer; empty states and in-app help. |
| US-010 | Tailwind `dark` + theme toggle; Comfort/Compact `data-density`; `localStorage` keys `gt-theme`, `gt-density`; FOUC script; styling across shell and Tasks. |

**Incomplete sprint scope**: None — every Sprint 2 backlog item assigned to this sprint is ✅ Done.

**Feedback and decisions**:

- No new defects raised during this review.
- **Carry-over / next**: [US-005](../backlog/user-stories/US-005-test-mcp-integration.md) remains ⭕ in the product backlog for a future sprint. [RI-001](../backlog/retrospective-improvements/RI-001-google-cloud-oauth-checklist.md) not yet completed — due [Sprint 3](../sprints/sprint-03-google-tasks-productivity.md).
- **Definition of Done (sprint level)**: Sprint review notes complete; sprint retrospective completed 2026-03-21 per [definition-of-done.md](../criteria/definition-of-done.md) sprint checklist.

**Sprint Retrospective Notes**:

**Retrospective date**: 2026-03-21 (solo / async)

**What went well?**

- All Sprint 2 committed scope delivered (26 story points): scaffold, OAuth, sync engine, views/navigation, theme/density/responsive.
- Vertical slice from API routes through Inertia/Vue with tests and `RELEASE_NOTES.md` updates aligned to stories.
- [ADR-002](../architecture-decision-records/ADR-002-branching-strategy.md) branch naming applied for story work.

**What could be improved?**

- **Google Cloud checklist** ([RI-001](../backlog/retrospective-improvements/RI-001-google-cloud-oauth-checklist.md)) still not published in `docs/` — carry to Sprint 3 before first production OAuth cutover.
- **Automation**: No CI yet for `apps/google-tasks` — add workflow for Composer, Vite build, and `php artisan test` ([RI-002](../backlog/retrospective-improvements/RI-002-ci-workflow-google-tasks.md)).

**Retrospective improvements for next sprint**

| ID | Description | Due |
|----|-------------|-----|
| [RI-001](../backlog/retrospective-improvements/RI-001-google-cloud-oauth-checklist.md) | Google Cloud OAuth + Tasks API checklist in `docs/` | Sprint 3 |
| [RI-002](../backlog/retrospective-improvements/RI-002-ci-workflow-google-tasks.md) | CI workflow for `apps/google-tasks` | Sprint 3 |

**Process changes**: None beyond carrying RI-001/RI-002; next sprint planning should sequence US-011+ against capacity (28 pts proposed in [Sprint 3](sprint-03-google-tasks-productivity.md)).

---

## Status Values

- ⭕ **To Do**: Not yet begun
- ⏳ **In Progress**: Currently being worked on
- ✅ **Done**: Done and verified
