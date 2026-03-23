---
template_version: 1.1.0
last_updated: 2026-03-24
compatible_with: [product-backlog]
---

# Sprint 7: Google Tasks — Polish and Performance

[← Back to Product Backlog](../backlog/product-backlog.md)

**Sprint status**: **Active** — started 2026-03-23 after [Sprint 6](sprint-06-google-tasks-trust-commands-mobile.md) closed.

**Sprint Goal**: Ship the **branded landing page** and **dashboard performance** improvements, plus complete **carry-forward documentation** (RI-004, RI-005) and add **E2E test coverage** (RI-006).

**Duration**: 2026-03-23 — 2026-04-06 (2 weeks)
**Team Velocity (reference)**: **91** points ([Sprint 6](sprint-06-google-tasks-trust-commands-mobile.md)); **24** ([Sprint 5](sprint-05-google-tasks-ux-visibility.md)) — targeting **~15–20** pts this sprint (retro guidance: focus on polish, docs, and quality)
**Sprint Planning Date**: 2026-03-23
**Sprint Review Date**: *TBD*
**Sprint Retrospective Date**: *TBD*

**Depends on**: [Sprint 6](sprint-06-google-tasks-trust-commands-mobile.md) ✅ (US-036 dashboard, all features complete).

---

## Sprint planning record (2026-03-23)

| Step | Result |
|------|--------|
| Backlog metrics | 44 items total; 42 Done, 2 To Do (US-040, US-041); 217 total pts |
| Definition of Ready | Both stories reviewed; AC clear, deps resolved, estimates in place |
| Capacity | **10** pts (2 stories) + retro improvements (RI-004, RI-005, RI-006) |
| Branching | [ADR-002](../architecture-decision-records/ADR-002-branching-strategy.md) |

### Suggested implementation order

1. [US-041](../backlog/user-stories/US-041-branded-landing-page.md) — **Branded landing page** (high visibility, quick win)
2. [US-040](../backlog/user-stories/US-040-dashboard-precompute-cache.md) — **Dashboard pre-compute cache** (backend + scheduler)
3. RI-004 — Index.vue integration playbook (docs)
4. RI-005 — All-tasks aggregate 429 runbook (docs)
5. RI-006 — Playwright E2E for swipe, command palette, dashboard

---

## Sprint Overview

**Focus Areas**: Landing page · Dashboard performance · Documentation · E2E testing

**Key Deliverables**:
- Branded Welcome.vue replacing default Laravel page
- Scheduled dashboard stats pre-computation with cache-first endpoint
- Index.vue integration playbook (RI-004)
- 429 runbook (RI-005)
- Playwright E2E scenarios (RI-006)

**Risks**: Landing page design iteration may require user feedback loop. Dashboard scheduler depends on cron availability on DreamHost.

---

## User Stories (this sprint)

| ID | Title | Points | Status |
|----|-------|--------|--------|
| [US-042](../backlog/user-stories/US-042-dashboard-visual-and-insight-improvements.md) | Dashboard Visual and Insight Improvements | 8 | ✅ |
| [US-041](../backlog/user-stories/US-041-branded-landing-page.md) | Branded Landing Page | 5 | ✅ |
| [US-040](../backlog/user-stories/US-040-dashboard-precompute-cache.md) | Dashboard Pre-Compute and Cache | 5 | ✅ |

**Total Story Points**: **18**

### Retrospective improvements (carry-forward)

| ID | Description | Status |
|----|-------------|--------|
| [RI-004](../backlog/retrospective-improvements/RI-004-tasks-index-vue-integration-playbook.md) | Index.vue integration playbook | ⭕ |
| [RI-005](../backlog/retrospective-improvements/RI-005-all-tasks-aggregate-429-runbook.md) | All-tasks aggregate 429 runbook | ⭕ |
| RI-006 | Playwright E2E for swipe, command palette, dashboard | ⭕ |

---

## Tasks (high level)

### US-041 — Branded landing page

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-7.1 | Hero section: headline, sub-headline, CTA (auth-aware) | `Welcome.vue` | ✅ |
| T-7.2 | Feature highlights: 5 cards (priority, Kanban, search, dashboard, swipe) with inline SVG icons | `Welcome.vue` | ✅ |
| T-7.3 | Dark mode, responsive, gt-* theme tokens, Outfit font | `Welcome.vue` | ✅ |
| T-7.4 | i18n EN/FR: 20 landing keys + 10 feature keys | `en.json`, `fr.json` | ✅ |
| T-7.5 | Footer with login/register links, copyright | `Welcome.vue` | ✅ |
| T-7.5b | Remove unused `laravelVersion`/`phpVersion` props + `Application` import from route | `web.php` | ✅ |

### US-040 — Dashboard pre-compute cache

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-7.6 | `RefreshDashboardStats` Artisan command; schedule in `routes/console.php` | `Commands/`, `console.php` | ✅ |
| T-7.7 | Mutation invalidation: touch cache on task create/complete/delete/move | `TasksController.php` | ✅ |
| T-7.8 | Cache-first `/dashboard/stats` endpoint: return cached if fresh, background refresh if stale | `DashboardController.php` | ✅ |
| T-7.9 | Multi-range cache support (7/14/30/90) | `DashboardStatsCache`, migration | ✅ |
| T-7.10 | Automated tests: scheduled refresh, stale serving, mutation invalidation | `DashboardStatsCacheTest.php` | ✅ |

### RI-004 — Index.vue integration playbook

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-7.11 | Write Index.vue integration playbook doc | `docs/INDEX_VUE_PLAYBOOK.md` | ⭕ |

### RI-005 — All-tasks aggregate 429 runbook

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-7.12 | Write 429 rate-limit runbook | `docs/RATE_LIMIT_RUNBOOK.md` | ⭕ |

### RI-006 — Playwright E2E

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-7.13 | E2E: mobile swipe gesture test | `tests/e2e/` | ⭕ |
| T-7.14 | E2E: command palette open/navigate/quick-add | `tests/e2e/` | ⭕ |
| T-7.15 | E2E: dashboard load + range switch | `tests/e2e/` | ⭕ |

---

## Sprint Summary

**Sprint Burndown**: *Update as stories complete.*

## Sprint Review Notes (2026-03-24)

**Demonstrated**:
1.  **US-041 (Landing Page)**: Branded welcome page with feature matrix, dark mode, and auth-aware CTAs.
2.  **US-040 (Performance)**: Instant dashboard load via Artisan-scheduled cache and mutation invalidation.
3.  **US-042 (Dashboard Polish)**:
    *   Throughput chart full-width fix.
    *   Due Discipline always expanded at top.
    *   Weekday Rhythm with created-vs-completed contrast.
    *   Lead Time distribution histogram and "< 1 day" handling.
    *   **Feedback Integration**: Moved Lead Time to a primary KPI card at the top per user request during review.

**Feedback received**:
- "Lead time would be better at the top with period summary netflow and the others" -> **Implemented immediately**.
- Data completeness fix for Lead Time (pagination) confirmed.

**Decisions made**:
- US-040, US-041, US-042 are **Done**.
- RI-004, RI-005, RI-006 remain in progress (documentation and E2E testing).
- Sprint remains **Active** until documentation and E2E tasks are finalized.

**Sprint Retrospective Notes**: *TBD*

---

## Status Values

- ⭕ **To Do** · ⏳ **In Progress** · ✅ **Done**

---

## History

- 2026-03-23 — Sprint 7 planning: US-040, US-041 selected (10 pts); RI-004, RI-005, RI-006 carry-forward
- 2026-03-23 — Activated after Sprint 6 review + retrospective
- 2026-03-23 — **US-041** marked Done (branded landing page: hero, features, auth-aware, i18n, dark mode, responsive)
- 2026-03-23 — **US-040** marked Done (migration, RefreshDashboardStats command, cache-first controller, mutation invalidation, multi-range, 8 new tests)
- 2026-03-24 — **US-042** marked Done (dashboard visual improvements: throughput width, grouped weekday rhythm, KPI trend styling, backlog growing indicator, lead time copy, no-due drill-down)
