---
template_version: 1.1.0
last_updated: 2026-03-28
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-046 — Sync / Refresh from Google (Desktop Parity & Smart Refresh)

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟠 High  
**Story Points**: 8  
**Created**: 2026-03-28  
**Updated**: 2026-03-28  
**Assigned Sprint**: _(TBD)_

## Clarifying Questions

- **Q**: Single-list mode — hide refresh, or refresh the current list?  
- **A**: **A — Full parity**: “Refresh from Google” refetches the **current list** from Google; add or use `forceRefresh` on the list tasks endpoint as needed.  
- **Date**: 2026-03-28  
- **Q**: Ship **Phase 4** (tab visibility refresh) in US-046 or defer?  
- **A**: **Ship in US-046** — **throttled soft refresh** on `visibilitychange` (not `forceRefresh` every time); global debounce (~60 s); applies to Tasks (all relevant nav modes + dashboard optional). Matches strong mail/calendar behavior without a separate story.  
- **Date**: 2026-03-28  
- **Q**: Keyboard chord for “Refresh from Google”?  
- **A**: **`⌘⌥R` (macOS) / `Ctrl+Alt+R` (Windows/Linux)** — avoids **`⌘⇧R` / `Ctrl+Shift+R`**, which commonly trigger **browser hard reload** in Chromium and Firefox. Implementer verifies no OS/app conflict in QA; if blocked, fall back to command palette + toolbar only or adjust chord.  
- **Date**: 2026-03-28  

## Description

[US-044](US-044-fast-today-inbox-all-views.md) shipped **server-side view caching** and **pull-to-refresh** on **touch** devices. The acceptance criteria also called for a **desktop refresh action**, but the shipped composable (`usePullToRefresh`) is **touch-only**, so mouse/trackpad users have no in-app way to send `forceRefresh=1` to bypass the cache.

This story delivers **desktop parity** and **smart refresh**: explicit “sync from Google,” **throttled visibility refresh** when returning to the tab, **keyboard** access (`⌘⌥R` / `Ctrl+Alt+R`), and **feedback** (loading, errors, rate limits)—aligned with how strong mail/calendar clients behave.

## User Story

As a desktop user of Today, Inbox, and All Tasks (and the productivity dashboard), I want a **clear way to pull the latest data from Google** and **trust that the app tells me when it’s updating or if something failed**, so cached views never feel like a dead end.

## Implementation Plan (Phased)

### Phase 1 — Core: one explicit refresh path (Tasks)

1. **Extract or reuse a single async function** that performs force refresh for the **current** navigation context:
   - Today / Inbox / All: call existing `fetchToday` / `fetchInbox` / `fetchAll` with `{ forceRefresh: true }` (already implemented).
   - **Single list** (`navMode === 'list'`): **force-refresh that list’s tasks** from Google (add `forceRefresh` support on the list endpoint if missing); same toolbar control and shortcut as aggregate views.
2. **UI placement (primary)**  
   - Add a **toolbar control** visible on **`lg+`** (desktop): icon + accessible name, e.g. “Refresh from Google” / “Sync now.”  
   - **Integrate with the cache disclaimer** (yellow banner): append a **text button or link** (“Refresh”) that calls the same handler so users who read the disclaimer get a one-click fix.  
   - Match **tokens** (`gt-*`, density) and **dark/light** from existing shell.
3. **Loading & errors**  
   - While refresh runs: disable button / show **spinner** on the icon (respect `prefers-reduced-motion`).  
   - On failure: use existing **`messageFromAxiosError`** / toast patterns; on **429**, surface **retry_after** per [US-019](US-019-api-error-retry-ux.md).  
   - Optional **success** microcopy (short toast or aria-live): only if it doesn’t clutter; many apps skip success for refresh.

### Phase 2 — Keyboard & discoverability

4. **Keyboard shortcut** (desktop, **typing-context guard** consistent with [US-014](US-014-keyboard-shortcuts.md)):  
   - **Default chord**: **`⌘⌥R` / `Ctrl+Alt+R`** (see Clarifying Questions — avoids browser hard-reload on `⌘⇧R` / `Ctrl+Shift+R`).  
   - Register in the same shortcut pipeline as other task shortcuts; **ignore** when `event.target` is editable / inside `[contenteditable]`.
5. **Documentation**  
   - Add a row to **`TasksKeyboardShortcutsHelp.vue`** + **en/fr** locale keys.  
   - **Recommend** a **command palette** action (“Refresh from Google”) in **`TasksCommandPalette.vue`** ([US-028](US-028-command-palette-navigation-quick-add.md)) for discoverability when users forget the chord.

### Phase 3 — Dashboard parity

6. **Dashboard** (`Dashboard.vue`): add the **same** explicit refresh control for **`lg+`** (stats already support `forceRefresh` via `fetchStats`). Reuse styling/copy pattern as Tasks for consistency.  
   - Pull-to-refresh on dashboard stays as-is for touch.

### Phase 4 — Smart refresh (in scope, throttled)

7. **`visibilitychange` strategy** (Tasks + **Dashboard** for parity):  
   - When `document.visibilityState` becomes **`visible`** after **`hidden`**, trigger a **soft** refresh for the **current** screen (same contexts as the toolbar button: aggregate views, single list, dashboard).  
   - **Do not** call `forceRefresh` on every visibility event — use **normal** fetches so the server can return fresh cache / background refresh; optional: escalate to `forceRefresh` only if **last user-initiated or successful load** is older than **N minutes** (config constant, e.g. 5).  
   - **Debounce** globally: at most once per **~60 s** per tab (skip if a refresh is already in flight).  
8. **Tests / telemetry** (lightweight): manual QA matrix (tab switch, rapid toggles); optional debug log when refresh is skipped due to throttle.

## Acceptance Criteria

- [x] **Desktop refresh control** on Tasks: visible on large breakpoints for **Today, Inbox, All, and single-list** mode; aggregate views use the same code path as pull-to-refresh (`forceRefresh=1`); list mode always hits Google API (no server view cache — invalidate + refetch same as pull).
- [x] **Disclaimer affordance**: the cache / stale banner includes a control that runs the same refresh handler (EN/FR).
- [x] **Accessibility**: control has visible focus, `aria-busy` while loading, and a clear accessible name; shortcut documented in the shortcuts modal.
- [x] **Typing safety**: shortcut never fires while focus is in inputs, textareas, or rich text editors.
- [x] **Errors**: failed refresh shows actionable feedback; 429 handled consistently with existing UX.
- [x] **Dashboard**: explicit desktop refresh for cached stats (parity with Tasks pattern).
- [x] **No regression**: pull-to-refresh on touch still works; mutations still invalidate caches ([US-044](US-044-fast-today-inbox-all-views.md)).
- [x] **i18n**: new strings in `en.json` and `fr.json` with matching keys.
- [x] **Automated coverage**: existing PHPUnit covers `forceRefresh` on views; visibility composable is thin (manual QA: tab switch, ⌘⌥R, toolbar, palette).
- [x] **Visibility refresh**: returning to the tab triggers **throttled soft refresh** on **Tasks** and **Dashboard** (Phase 4 rules); no API storm.

## Business Value

Cached aggregate views are fast but can lag behind **other Google Tasks clients**. Without a desktop refresh, power users lose trust or resort to hacks. A visible sync action plus optional focus-based refresh matches expectations set by US-044’s copy (“until this view refreshes from Google”) and closes the gap left by touch-only pull-to-refresh.

## Technical References

- `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` — `forceRefreshCurrentView`, `fetchToday` / `fetchInbox` / `fetchAll`, cache banner  
- `apps/google-tasks/resources/js/composables/usePullToRefresh.js` — touch-only; keep; desktop uses separate control calling same refresh callback  
- `apps/google-tasks/app/Http/Controllers/TasksController.php` — `cachedView()`, `forceRefresh` query param  
- `apps/google-tasks/resources/js/Pages/Dashboard.vue` — `fetchStats({ forceRefresh: true })`  
- `apps/google-tasks/resources/js/Components/TasksKeyboardShortcutsHelp.vue` — add shortcut row  
- `apps/google-tasks/resources/js/composables/useTasksKeyboardShortcuts.js` — register new binding with typing guard  
- `apps/google-tasks/resources/js/Components/TasksCommandPalette.vue` — optional action  

## Dependencies

- [US-044](US-044-fast-today-inbox-all-views.md) ✅ — cache + `forceRefresh` + pull-to-refresh  
- [US-014](US-014-keyboard-shortcuts.md) ✅ — shortcut patterns  
- [US-019](US-019-api-error-retry-ux.md) ✅ — 429 / error messaging  
- [US-028](US-028-command-palette-navigation-quick-add.md) ✅ — optional palette entry  

## History

- 2026-03-28 — Created (plan: desktop refresh parity, banner action, keyboard, dashboard parity, optional visibility refresh)  
- 2026-03-28 — Q&A: single-list mode → **full parity** (refresh current list; extend API if needed)  
- 2026-03-28 — Q&A: Phase 4 → **in scope** (throttled soft refresh on tab visible; ~60 s debounce)  
- 2026-03-28 — Q&A: shortcut → **`⌘⌥R` / `Ctrl+Alt+R`** (avoid browser hard-reload chords); story points **5 → 8**  
- 2026-03-28 — **Implemented**: `useVisibilitySoftRefresh.js`; Tasks header button + banner + `userInitiatedSyncFromGoogle` + palette `action-sync-google`; `useTasksKeyboardShortcuts` ⌘⌥R/Ctrl+Alt+R; Dashboard `fetchStats({ silent })` + header + banner; EN/FR strings.

## Acceptance Verification

- [x] All acceptance criteria above verified or documented for manual QA
