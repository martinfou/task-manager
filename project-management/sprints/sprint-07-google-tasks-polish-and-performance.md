---
template_version: 1.1.0
last_updated: 2026-03-24
compatible_with: [product-backlog]
---

# Sprint 7: Google Tasks — Polish and Performance

[← Back to Product Backlog](../backlog/product-backlog.md)

**Sprint status**: **Closed** — 2026-03-23 → 2026-03-29. 42/47 pts completed (6/7 stories). US-043 moved back to backlog.

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
| [US-046](../backlog/user-stories/US-046-sync-from-google-refresh-ux.md) | Sync / Refresh from Google (Desktop Parity & Smart Refresh) | 8 | ✅ |
| [US-045](../backlog/user-stories/US-045-custom-task-list-ordering.md) | Custom Task List Ordering (Pin, Reorder, Auto-Sort) | 8 | ✅ |
| [US-044](../backlog/user-stories/US-044-fast-today-inbox-all-views.md) | Fast Today, Inbox, and All Tasks Views | 8 | ✅ |
| [US-043](../backlog/user-stories/US-043-scope-audit-tool.md) | Outil d'Audit des Scopes (Dev vs Prod) | 5 | ⭕ Not started |
| [US-042](../backlog/user-stories/US-042-dashboard-visual-and-insight-improvements.md) | Dashboard Visual and Insight Improvements | 8 | ✅ |
| [US-041](../backlog/user-stories/US-041-branded-landing-page.md) | Branded Landing Page | 5 | ✅ |
| [US-040](../backlog/user-stories/US-040-dashboard-precompute-cache.md) | Dashboard Pre-Compute and Cache | 5 | ✅ |

**Total Story Points**: **47** (42 completed, 5 not started)

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

**Sprint Burndown**: 42 of 47 pts completed. US-043 (5 pts) not started — moved to backlog.

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

## Sprint Retrospective Notes (2026-03-29)

### What Went Well

1. **Reusable caching pattern**: The `cachedView()` pattern from US-040 (dashboard) was cleanly extended to US-044 (task views). Same architecture, same invalidation strategy, consistent API — good abstraction.
2. **Rapid Dreamhost debugging**: Systematically diagnosed shared hosting issues (hanging commands → missing HTTP timeouts, move 400 → empty JSON body, defer blocking → `fastcgi_finish_request` unavailability). Each fix was targeted and well-understood.
3. **GitHub Actions crons**: Moving scheduled tasks from unreliable shared hosting crontab to GitHub Actions SSH crons was a smart infrastructure decision. Reliable, auditable, version-controlled.
4. **US-045 quality**: Custom list ordering shipped with 11 tests, auto-pin, drag-to-reorder, auto-sort, and full i18n. Cleanly separated as a presentation-layer concern (no Google API modification).
5. **US-046 completeness**: Desktop sync parity, keyboard shortcut, command palette action, visibility-change refresh, dashboard parity — all four phases delivered in one story. Polished.

### What Could Be Improved

1. **US-043 never started**: A Critical-priority item sat for 6 days with no story file even created. The item was assigned to the sprint during planning but had no Definition of Ready gate (no file, no AC, no technical refs). **Root cause**: planning didn't enforce DoR for US-043 — it was added as a placeholder.
2. **RI-004/005/006 carried forward for the 3rd sprint**: Documentation and E2E tasks have been deprioritized in every sprint since Sprint 5. Carrying items forward indefinitely is worse than dropping them — it clutters the backlog and erodes trust in the process. **Decision needed**: commit to them in Sprint 8 or drop them.
3. **defer() removal caused a regression (DEF-002)**: Removing `defer()` from the stale-cache path fixed the Dreamhost blocking issue but introduced a side effect — mutations mark the cache stale, but `pollOnce()` returns stale data (deleted tasks reappear). The change wasn't analyzed end-to-end before deployment. **Root cause**: no impact analysis of dependent flows when modifying infrastructure.
4. **Sprint scope creep — planned 18 pts, delivered 42**: US-044, US-045, US-046 were added mid-sprint without formal planning. While productive, the unplanned work displaced US-043 and all three RIs. Ad-hoc additions should be weighed against existing commitments.
5. **Index.vue still growing**: RI-004 (integration playbook to manage Index.vue complexity) has been open since Sprint 5. Meanwhile, US-044/045/046 all added significant code to Index.vue. The file is now well past 4,000 lines. The longer this is deferred, the harder extraction becomes.

### Retrospective Improvements

| ID | Description | Owner | Due Sprint | Status |
|----|-------------|-------|------------|--------|
| RI-007 | Before removing infrastructure (e.g., defer, cron, cache layers), do end-to-end impact analysis on all dependent flows. Document what breaks and verify with a test. | Developer | Sprint 8 | ⭕ |
| RI-004 | Index.vue integration playbook — **final decision**: commit to Sprint 8 or drop | Developer | Sprint 8 | ⭕ |
| RI-005 | All-tasks 429 runbook — **final decision**: commit to Sprint 8 or drop | Developer | Sprint 8 | ⭕ |
| RI-006 | Playwright E2E — **final decision**: commit to Sprint 8 or drop | Developer | Sprint 8 | ⭕ |

### Process Changes to Document

- [ ] **Enforce DoR at sprint planning**: Items without a story file and completed AC must not enter the sprint. Update sprint planning process to include a hard gate.
- [ ] **Mid-sprint additions policy**: When adding unplanned work mid-sprint, explicitly assess impact on committed items. If committed items will be displaced, re-negotiate scope with the user.
- [ ] **Carry-forward limit**: Items carried forward for 2+ sprints must be explicitly decided on (commit or drop) at the next retrospective. No silent carry-forward.

### Previous Retrospective Improvements Review

| ID | From Sprint | Status | Notes |
|----|-------------|--------|-------|
| RI-004 | Sprint 5 | ⭕ Not done | Carried forward 3 sprints. Must decide in Sprint 8. |
| RI-005 | Sprint 5 | ⭕ Not done | Carried forward 3 sprints. Must decide in Sprint 8. |
| RI-006 | Sprint 6 | ⭕ Not done | Carried forward 2 sprints. Must decide in Sprint 8. |

### Follow-up

- [ ] Add RI-007 to backlog retrospective improvements
- [ ] Decide on RI-004/005/006 during Sprint 8 planning (commit or drop)
- [ ] Create US-043 story file before Sprint 8 planning if it's to be included
- [ ] Update sprint planning process with DoR hard gate

## Sprint Review Notes (2026-03-29) — Final Review

**Demonstrated** (in addition to 2026-03-24 mid-sprint review):

4.  **US-044 (Fast Views)**: Server-side `TaskViewCache` for Today/Inbox/All views. Cache-first with stale indicator, pull-to-refresh with visual feedback, mutation invalidation, 10 new PHP tests. First paint under 200ms on warm cache.
5.  **US-045 (Custom List Ordering)**: Pin-to-top with auto-pin for "In" list, drag-to-reorder via dedicated Organise sheet (mobile + desktop), auto-sort (A→Z / Z→A), `task_list_order` database table, 11 PHP tests. Pinned/unpinned sidebar sections with visual divider.
6.  **US-046 (Sync from Google)**: Desktop refresh toolbar button, cache disclaimer "Refresh now" link, `⌘⌥R` / `Ctrl+Alt+R` keyboard shortcut, command palette action, visibility-change throttled soft refresh (60s debounce), dashboard parity. Full i18n EN/FR.
7.  **Infrastructure**: GitHub Actions crons for dashboard refresh (hourly) and view cache refresh (every 15 min) — replaces unreliable Dreamhost crontab. HTTP timeouts added to `GoogleTasksClient` and `GoogleOAuthTokenService` for shared hosting compatibility.
8.  **Bug fix**: Google Tasks API `move` endpoint 400 error — empty JSON body rejected by Google; fixed with `withBody('', '')` for bodyless POST requests.
9.  **Bug fix**: Removed `defer()` from stale cache paths (Dashboard + Tasks) — `defer()` blocks HTTP response on Dreamhost shared hosting (no `fastcgi_finish_request()`), causing 17s+ delays.

**Not completed**:
- **US-043** (Scope Audit Tool): Never started. Critical priority but story file was never created. 6 days past aging threshold. Recommend moving back to backlog or deprioritizing.
- **RI-004** (Index.vue integration playbook): Not started. Carried forward from Sprint 6.
- **RI-005** (429 rate-limit runbook): Not started. Carried forward from Sprint 6.
- **RI-006** (Playwright E2E tests): Not started. Carried forward from Sprint 6.

**Issues discovered during review**:

1. **US-042 has 2 unchecked AC items** (Section G): "No regression" and "Responsive 2x2 grid" are unchecked, but Acceptance Verification is marked complete. Inconsistency — needs manual verification or AC checkbox update.
2. **US-040 & US-044 AC text is now inaccurate**: Both mention "background refresh triggered" for stale cache via `defer()`. This was removed in commit 346b29d. The cron now handles background refresh. AC text should be updated to reflect current behavior.
3. **DEF-002 identified**: Removing `defer()` introduced a regression — mutations mark cache stale, but `pollOnce()` returns the stale data (with deleted/modified tasks still present). Surgical cache patching is the recommended fix.

**Decisions**:
- US-040, US-041, US-042, US-044, US-045, US-046 are **Done** (42 pts completed).
- US-043 moves back to **Backlog** — to be refined before it can enter a sprint (needs story file created).
- RI-004, RI-005, RI-006 move to Sprint 8 or are deprioritized — they have carried forward for 2 sprints without progress.
- DEF-002 is a **Sprint 8 candidate** (High priority, 3 pts, solution documented).
- Sprint 7 is **closed** as of 2026-03-29.

**Sprint metrics**:
- **Planned**: 18 pts (US-040 + US-041 + US-042)
- **Added mid-sprint**: 24 pts (US-044 + US-045 + US-046)
- **Completed**: 42 pts / 6 stories
- **Not completed**: 5 pts (US-043)
- **Duration**: 2026-03-23 → 2026-03-29 (7 days)

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
- 2026-03-28 — **US-044** marked Done (server-side view caching, pull-to-refresh, mutation invalidation, 10 tests)
- 2026-03-28 — **US-045** marked Done (custom list ordering: pin, reorder, auto-sort, 11 tests)
- 2026-03-28 — **US-046** marked Done (desktop sync button, keyboard shortcut, visibility refresh, dashboard parity)
- 2026-03-29 — Sprint review (final): 42/47 pts completed. US-043 not started, moved to backlog. RI-004/005/006 not started. DEF-002 identified.
- 2026-03-29 — Sprint 7 **closed**
