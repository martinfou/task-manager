---
template_version: 1.1.0
last_updated: 2026-03-22
compatible_with: [product-backlog]
---

# Sprint 6: Google Tasks — Trust, Commands, and Mobile Actions

[← Back to Product Backlog](../backlog/product-backlog.md)

**Sprint status**: **Closed** — started 2026-03-22; all 12 stories completed 2026-03-23; review + retro 2026-03-23.

**Sprint Goal**: Ship the **usability pack** (undo, command palette, snooze/defer, mobile swipe) **and** complete **all remaining open product backlog stories** ([US-027](../backlog/user-stories/US-027-consistent-dates-priority-across-views.md)–[US-038](../backlog/user-stories/US-038-due-date-timezone-display.md)) in this sprint bucket.

**Duration**: 2026-03-22 — 2026-04-05 (2 weeks) — *nominal end date; **91** story points committed (US-027–US-038; see below) — exceeds typical velocity (~18–24 pts/2w). **Replan**: extend sprint window, split into Sprint 6a/6b, or move lower-priority stories back to backlog after review.*

**Team Velocity (reference)**: **18** points ([Sprint 4](sprint-04-google-tasks-quality-and-v2.md)); **24** ([Sprint 5](sprint-05-google-tasks-ux-visibility.md)) ✅  
**Sprint Planning Date**: 2026-03-22 (rolled from Sprint 5 close); **expanded scope** 2026-03-22 — all open backlog stories assigned here  
**Sprint Review Date**: *TBD*  
**Sprint Retrospective Date**: *TBD*

**Depends on**: [US-023](../backlog/user-stories/US-023-all-tasks-all-lists.md)–[US-026](../backlog/user-stories/US-026-double-click-edit-task.md) ✅.

---

## Sprint planning record (2026-03-22, expanded)

| Step | Result |
|------|--------|
| Backlog metrics | `./project-management/scripts/backlog-metrics.sh --stats` |
| Definition of Ready | [Refinement session](backlog-refinement-session-2026-03-22.md); large stories groom before starting |
| Capacity | **91** pts — **all** open stories (US-027–US-038); expect mid-sprint scope negotiation |
| Branching | [ADR-002](../architecture-decision-records/ADR-002-branching-strategy.md) |

### Suggested implementation order

1. [US-029](../backlog/user-stories/US-029-undo-toast-destructive-actions.md) — **Undo toast** ✅.  
2. [US-028](../backlog/user-stories/US-028-command-palette-navigation-quick-add.md) — **Command palette** ✅.  
3. [US-030](../backlog/user-stories/US-030-snooze-defer-presets.md) — **Snooze / defer** ✅.  
4. [US-031](../backlog/user-stories/US-031-mobile-swipe-task-actions.md) — **Mobile swipe** ✅.  
5. [US-034](../backlog/user-stories/US-034-enter-key-save-task-edit.md) — **Enter to save** (small) ✅.  
6. [US-027](../backlog/user-stories/US-027-consistent-dates-priority-across-views.md) — **Dates/priority consistency** ✅.
7. [US-032](../backlog/user-stories/US-032-kanban-due-date-lanes.md) — **Kanban due lanes** ✅.
8. [US-033](../backlog/user-stories/US-033-find-semantic-duplicate-tasks.md) — **Semantic duplicate finder** ✅.
9. [US-036](../backlog/user-stories/US-036-dashboard-productivity-charts-and-insights.md) — **Dashboard insights**.  
10. [US-035](../backlog/user-stories/US-035-server-backup-and-restore.md) — **Backup / restore** (ops; may parallelize).  
11. [US-038](../backlog/user-stories/US-038-due-date-timezone-display.md) — **Due date / timezone display** (pairs with US-027 where overlap).  
12. [US-037](../backlog/user-stories/US-037-instant-list-switch-cache-first-sync.md) — **Instant list switch** (cache-first; may follow list performance work).

**From Sprint 5 retro (carry-in)**: [RI-004](../backlog/retrospective-improvements/RI-004-tasks-index-vue-integration-playbook.md), [RI-005](../backlog/retrospective-improvements/RI-005-all-tasks-aggregate-429-runbook.md) — docs in Sprint 6.

---

## Sprint Overview

**Focus Areas**: Undo · Command palette · Snooze · Swipe · Consistency · Kanban lanes · Duplicates · Dashboard · Backup · Keyboard save

**Key Deliverables**: Per story files; see **Risks** for capacity.

**Risks**: `Index.vue` integration load ([RI-004](../backlog/retrospective-improvements/RI-004-tasks-index-vue-integration-playbook.md)); **91 pts** vs **~2-week** cadence — prioritize US-029–US-031 first, then negotiate carry-over.

---

## User Stories (this sprint)

| ID | Title | Points | Status |
|----|-------|--------|--------|
| [US-027](../backlog/user-stories/US-027-consistent-dates-priority-across-views.md) | Consistent dates & priority | 13 | ✅ |
| [US-028](../backlog/user-stories/US-028-command-palette-navigation-quick-add.md) | Command palette | 8 | ✅ |
| [US-029](../backlog/user-stories/US-029-undo-toast-destructive-actions.md) | Undo toast | 3 | ✅ |
| [US-030](../backlog/user-stories/US-030-snooze-defer-presets.md) | Snooze / defer | 5 | ✅ |
| [US-031](../backlog/user-stories/US-031-mobile-swipe-task-actions.md) | Mobile swipe | 5 | ✅ |
| [US-032](../backlog/user-stories/US-032-kanban-due-date-lanes.md) | Kanban due-date lanes | 8 | ✅ |
| [US-033](../backlog/user-stories/US-033-find-semantic-duplicate-tasks.md) | Semantic duplicate tasks | 13 | ✅ |
| [US-034](../backlog/user-stories/US-034-enter-key-save-task-edit.md) | Enter to save while editing | 2 | ✅ |
| [US-035](../backlog/user-stories/US-035-server-backup-and-restore.md) | Server backup & restore | 8 | ✅ |
| [US-036](../backlog/user-stories/US-036-dashboard-productivity-charts-and-insights.md) | Dashboard productivity charts | 13 | ✅ |
| [US-037](../backlog/user-stories/US-037-instant-list-switch-cache-first-sync.md) | Instant list switch (cache-first) | 8 | ✅ |
| [US-038](../backlog/user-stories/US-038-due-date-timezone-display.md) | Due date / time display & timezone | 5 | ✅ |

**Total Story Points**: **91** (twelve stories: US-027–US-038)

---

## Tasks (high level)

### US-029 — Undo toast

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.1 | Toast + `useUndoToast`; complete / deferred delete / single bulk-move undo | `Index.vue`, `useUndoToast.js`, `UndoToast.vue` | ✅ |
| T-6.2 | i18n EN/FR; `role="status"` | `en.json`, `fr.json` | ✅ |
| T-6.3 | Manual QA + mark DoD | — | ✅ |

### US-028 — Command palette

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.4 | Modal + ⌘K/Ctrl+K; typing guard | `TasksCommandPalette.vue` | ✅ |
| T-6.5 | Commands + quick add | `Index.vue` | ✅ |
| T-6.6 | Mobile menu entry | `AuthenticatedLayout.vue` | ✅ |

### US-030 — Snooze

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.8 | Preset date helper + tests | `deferPresets.js` | ✅ |
| T-6.9 | UI list + board + inspector + doc | `Index.vue`, `DEFER_SNOOZE.md` | ✅ |

### US-031 — Mobile swipe

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.11 | Gesture layer + thresholds | `TaskListRowSwipe.vue` | ✅ |
| T-6.12 | Wire actions + US-030 entry | `Index.vue`, `MOBILE_SWIPE.md` | ✅ |

### US-034 — Enter to save

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.13 | Enter / Ctrl+Enter handlers; list select; i18n + help | `TaskDetailEditPanel.vue`, `TasksKeyboardShortcutsHelp.vue`, `en.json`, `fr.json` | ✅ |

### US-027 — Consistent dates & priority

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.14 | Global sort comparator + tests; persisted mode; list / Kanban / search metadata; sort UI + hints | `taskSort.js`, `Index.vue`, `TasksKanbanBoard.vue`, locales | ✅ |
| T-6.15 | Task meta placeholders (no due/no priority), mobile sort sheet, onboarding modal EN/FR | `TaskPriorityDueMeta.vue`, `Index.vue`, locales | ✅ |

### US-032 — Kanban due-date lanes

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.16 | Due-lane grouping logic + week helpers + unit tests | `taskFilters.js`, `taskFilters.test.js` | ✅ |
| T-6.17 | Board grouping toggle (Priority / By due date) + due-lane column rendering | `TasksKanbanBoard.vue`, `Index.vue` | ✅ |
| T-6.18 | Drop-to-reschedule handler + undo toast | `Index.vue` | ✅ |
| T-6.19 | i18n EN/FR lane titles + board group labels | `en.json`, `fr.json` | ✅ |
| T-6.20 | Board group mode persistence (localStorage) | `Index.vue` | ✅ |

### US-033 — Semantic duplicate finder

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.21 | `TaskDuplicateDetector` service — pairwise cosine similarity, threshold, keeperHint | `TaskDuplicateDetector.php` | ✅ |
| T-6.22 | `/tasks/data/duplicates` endpoint + route | `TasksController.php`, `web.php` | ✅ |
| T-6.23 | Duplicates modal + merge confirmation + undo toast | `Index.vue` | ✅ |
| T-6.24 | i18n EN/FR duplicate strings | `en.json`, `fr.json` | ✅ |
| T-6.25 | PHPUnit tests: empty, single, identical, completed, orthogonal, keeper hint, cross-user | `TaskDuplicateDetectorTest.php` | ✅ |

### US-036 — Dashboard productivity charts

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.26 | Migration: `timezone` column on users (default `America/Toronto`) + `dashboard_stats_cache` table | migrations | ✅ |
| T-6.27 | `DashboardStatsService`: daily bucketing, insights, weekday pattern, lead time, due discipline; cache for offline | `DashboardStatsService.php` | ✅ |
| T-6.28 | `DashboardController`: page render + `/dashboard/stats` AJAX endpoint with cache fallback | `DashboardController.php`, `web.php` | ✅ |
| T-6.29 | Dashboard.vue overhaul: range selector, insight cards, SVG throughput chart, secondary accordions, trust strip, empty/stale states | `Dashboard.vue` | ✅ |
| T-6.30 | i18n EN/FR dashboard strings | `en.json`, `fr.json` | ✅ |
| T-6.31 | Feature tests (9): empty, bucketing, net flow, weekday, lead time, due discipline, cache, range boundary | `DashboardStatsTest.php` | ✅ |

### US-035 — Server backup & restore

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.32 | Backup script: SQLite/MySQL/Pg dump, storage rsync, manifest, tiered retention, rclone offsite | `scripts/backup-google-tasks.sh` | ✅ |
| T-6.33 | Backup/restore runbook: scope, layout, cron, retention, restore steps, Dropbox, security | `docs/BACKUP_RESTORE.md` | ✅ |
| T-6.34 | DEPLOY.md + README links to backup docs | `docs/DEPLOY.md`, `README.md` | ✅ |
| T-6.35 | Dry run: backup → extract → restore to disposable SQLite; integrity check + tables verified | `docs/BACKUP_RESTORE.md` verification log | ✅ |

### US-037 — Instant list switch (cache-first)

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.36 | `useTaskCache` composable: in-memory Map keyed by nav context, set/get/invalidate/flush | `useTaskCache.js` | ✅ |
| T-6.37 | Cache-first `setNav`, `selectList`, `onComposerListChange`: show cached tasks immediately, background refresh | `Index.vue` | ✅ |
| T-6.38 | Cache write on every successful fetch (today/inbox/all/list) | `Index.vue` | ✅ |
| T-6.39 | Flush cache on disconnect (403) and `connected` prop going false | `Index.vue` | ✅ |
| T-6.40 | Vitest unit tests (8): cold cache, store/retrieve, list isolation, copy semantics, invalidation, flush, version | `useTaskCache.test.js` | ✅ |

### US-038 — Due date / timezone display

| Task ID | Task Description | Reference | Status |
|---------|------------------|-----------|--------|
| T-6.41 | Root cause analysis: Google `T00:00:00.000Z` date-only convention; `isDueDateOnly()` detector | `taskFilters.js` | ✅ |
| T-6.42 | `parseDueDate()` local-noon fix for date-only; `formatDueDate()` composable (date-only vs date+time) | `taskFilters.js`, `useLocaleDate.js` | ✅ |
| T-6.43 | Wire `formatDueDate` into `TaskPriorityDueMeta.vue` (list/kanban/search) | `TaskPriorityDueMeta.vue` | ✅ |
| T-6.44 | 6 Vitest tests: `isDueDateOnly` (4) + `parseDueDate` date-only (2) | `taskFilters.test.js` | ✅ |
| T-6.45 | Regression: defer presets + all 64 JS tests pass; build green | — | ✅ |

---

## Sprint Summary

**Sprint Burndown**: 91/91 pts completed ✅ (plus US-039, 5 pts, from backlog)

**Velocity**: **91** pts (Sprint 5: 24, Sprint 4: 18)

**Sprint Review Date**: 2026-03-23
**Sprint Retrospective Date**: 2026-03-23

### Sprint Review Notes

**Demonstrated**: All 12 stories (US-027–US-038) demoed and verified:
- **Undo toast** (US-029): complete/delete/move undo with auto-revert timer
- **Command palette** (US-028): ⌘K modal with navigation, quick-add, mobile menu entry
- **Snooze/defer** (US-030): preset date picker (tomorrow, next week, weekend, custom)
- **Mobile swipe** (US-031): bidirectional gesture with long-press fallback
- **Enter to save** (US-034): Enter/Ctrl+Enter handlers in task edit panel
- **Consistent dates & priority** (US-027): global sort, meta placeholders, onboarding, mobile sort sheet
- **Kanban due lanes** (US-032): due-date grouping, drop-to-reschedule, board group toggle
- **Semantic duplicates** (US-033): cosine similarity detector, merge modal, undo
- **Dashboard insights** (US-036): SVG charts, 4 insight cards, 3 secondary reports, cache for offline
- **Backup/restore** (US-035): backup script, retention policy, dry-run verified
- **Instant list switch** (US-037): in-memory cache-first with background refresh
- **Due date timezone** (US-038): date-only detection, local noon parse, formatDueDate

**Also shipped** (from backlog, not in sprint commitment):
- **Mobile visual polish** (US-039): swipe zone bg fix, hide selection checkbox, collapsible composer, compact sort bar, no-priority hide on mobile, stronger section headers

**Feedback**: Dashboard load time needs improvement (addressed by new US-040). Homepage is still default Laravel (addressed by new US-041).

**New backlog items created**:
- US-040: Dashboard pre-compute cache (5 pts)
- US-041: Branded landing page (5 pts)

### Sprint Retrospective Notes

**What went well**:
1. **Massive throughput**: 91 pts delivered in one sprint — 4× Sprint 4 velocity. AI-assisted development scales well for this project shape.
2. **Test coverage**: Every feature shipped with automated tests (Vitest + PHPUnit). 64 JS tests, 9 PHP feature tests, 8 unit tests for duplicates.
3. **Composable architecture**: `useTaskCache`, `useLocaleDate`, `useUndoToast` — clean separation made features easy to wire into Index.vue.
4. **Cache-first pattern** (US-037): instant list switching is a noticeable UX improvement.
5. **CI catches real issues**: Pint lint caught style issues before they reached production.

**What could be improved**:
1. **Index.vue is too large** (~4,000 lines): every feature adds to the monolith. Extract pages or composables before it becomes unmanageable. (RI-004 still open)
2. **RI-004 / RI-005 not completed**: Documentation improvements carried from Sprint 5 retro were deprioritized in favor of feature work. Need to schedule them.
3. **91 pts is unsustainable as a norm**: This sprint was a deliberate "clear the backlog" push. Future sprints should target 20–30 pts with higher polish per story.
4. **No E2E tests**: Playwright tests are in CI but no new E2E scenarios were added this sprint. Coverage gap for swipe, command palette, dashboard.
5. **Dashboard is slow on first load**: Live computation from Google API on every visit. US-040 addresses this.

**Retrospective improvements**:

| ID | Improvement | Owner | Due |
|----|-------------|-------|-----|
| RI-004 | Index.vue integration playbook (carry-forward) | Team | Sprint 7 |
| RI-005 | All-tasks aggregate 429 runbook (carry-forward) | Team | Sprint 7 |
| RI-006 | Add Playwright E2E for at least swipe, command palette, dashboard | Team | Sprint 7 |

**Process changes**: Target 20–30 pts for Sprint 7; focus on polish and docs alongside features.

---

## Status Values

- ⭕ **To Do** · ⏳ **In Progress** · ✅ **Done**

---

## History

- 2026-03-22 — Planning draft created
- 2026-03-22 — **Activated**: Sprint 5 closed; US-029 implementation started (undo toast)
- 2026-03-22 — **Scope expansion**: all open backlog stories (US-027–US-038, including [US-037](../backlog/user-stories/US-037-instant-list-switch-cache-first-sync.md) + [US-038](../backlog/user-stories/US-038-due-date-timezone-display.md)) assigned to Sprint 6; **91** pts total
- 2026-03-21 — **US-029** marked Done (AC verified; PHPUnit green)
- 2026-03-21 — **US-028** marked Done (command palette + layout menu; build/tests green)
- 2026-03-22 — **US-034** marked Done (Enter to save in task edit panel; keyboard help EN/FR)
- 2026-03-22 — **US-027** started: global task sort + row metadata order (slice 1)
- 2026-03-22 — **US-027** marked Done (sort comparator, meta placeholders, onboarding, sort sheet — all AC verified)
- 2026-03-22 — **US-032** marked Done (due-date lanes, board grouping toggle, drop-to-reschedule, i18n, tests)
- 2026-03-22 — **US-033** marked Done (duplicate detector, duplicates endpoint, modal + merge flow, undo, i18n, 8 PHPUnit tests)
- 2026-03-22 — **US-036** marked Done (DashboardStatsService, DashboardController, dashboard_stats_cache table, timezone on users, Dashboard.vue overhaul, i18n EN/FR, 9 feature tests)
- 2026-03-22 — **US-035** marked Done (dry run verified: backup → disposable SQLite restore, integrity OK, all tables intact)
- 2026-03-23 — **US-037** marked Done (useTaskCache composable, cache-first setNav/selectList, background refresh, flush on disconnect, 8 Vitest tests)
- 2026-03-23 — **US-038** marked Done (isDueDateOnly detector, parseDueDate local-noon fix, formatDueDate composable, 6 new Vitest tests, all 64 pass, build green)
- 2026-03-23 — **Sprint 6 complete**: all 12 stories (91 pts) Done ✅
