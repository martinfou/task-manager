---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-025 — Mobile Tasks Shell (Search + Less Collapsible Chrome)

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟠 High  
**Story Points**: 8  
**Created**: 2026-03-21  
**Updated**: 2026-03-24  
**Assigned Sprint**: [Sprint 5](../../sprints/sprint-05-google-tasks-ux-visibility.md) (closed)

## Description

Improve the **Tasks** experience on **phones and small viewports**: reclaim vertical space by **not keeping search always visible**, and **remove reliance on collapsible “how it works”** content for **Today / Inbox / lists** (and related nested disclosure) that adds noise without helping repeat users.

Targets the current shell in `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` (header search block, `<details>` help, mobile nav).

## User Story

As a mobile user, I want **more room for my task list** and **simpler navigation**, so that I can work quickly without scrolling past a permanent search field or expanding help about Inbox and lists.

## Acceptance Criteria

- [x] **Search (mobile / narrow breakpoint, e.g. `lg` hidden)**: the full search field (and semantic mode selector when present) is **not shown by default** in the sticky page header. User opens search via a **clear control** (icon button or “Search”) that reveals the field (inline expand, slide-down, or full-width overlay). **Dismiss** or navigate away restores the compact header.
- [x] **Desktop / tablet (wide)**: existing always-visible search **may remain** unless product owner prefers parity; document the breakpoint behavior.
- [x] **Collapsible Today/Inbox/lists help**: the `<details>` “How Today, Inbox & lists work” block is **not shown on mobile** (or replaced by a **single link** to Help / Profile doc / modal). Full text remains available somewhere discoverable (e.g. desktop only, or linked article).
- [x] **Inbox & Lists navigation**: no **extra collapsible layer** required to switch between Inbox, Today, and lists on mobile—e.g. bottom nav + list drawer stay **one tap** to destination without nested disclosure inside those surfaces. If current UI violates this, redesign that flow (document before/after in implementation notes).
- [x] **Touch targets and safe areas** remain acceptable; no regression on OAuth, task CRUD, or filters.
- [x] **i18n** (EN/FR) for any new strings (search open/close, help link).
- [x] **Manual QA** on a real device or narrow emulator; optional Playwright viewport test if the suite already covers Tasks.

## Business Value

Mobile is a primary context for task triage; reducing header chrome directly increases usable list viewport and perceived speed.

## Technical Requirements

- Tailwind breakpoint-driven layout in Vue; prefer `max-lg:` / `lg:` over JS `matchMedia` unless necessary.
- Keep keyboard shortcut `/` and Ctrl+K behavior on desktop; on mobile, opening search via shortcut may be N/A—document.

## Technical References

- `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` — `#header` search, help `<details>`, `showListDrawer`, bottom nav
- `apps/google-tasks/resources/js/composables/useTasksKeyboardShortcuts.js` — search focus

## Dependencies

- [US-009](US-009-views-today-inbox-lists.md) (navigation baseline) — refine, not replace, unless story scope expands.
- **Order**: Prefer landing **before** or with [US-031](US-031-mobile-swipe-task-actions.md) so row swipe affordances fit the mobile shell without rework (see Sprint planning).

## Clarifying Questions

*Document answers after refinement.*

- **Q**: Should **semantic search** toggle live inside the revealed search panel only on mobile?
- **A**: **Middle ground** — **Primary**: semantic mode control lives **inside the revealed search panel** (no permanent toggle on the main chrome). **Discoverability**: add **lightweight hints** — e.g. **first-time** copy when opening search (“Try semantic — matches meaning”) and/or **overflow / long-press** on the search affordance for “Search by meaning” without crowding the header. Exact UX is implementation detail; goal = **minimal chrome** + **not hidden forever**.
- **Date**: 2026-03-21

## Refinement (2026-03-22)

Session: [Backlog refinement — all user stories](../../sprints/backlog-refinement-session-2026-03-22.md).

| Definition of Ready | Met |
|---------------------|-----|
| Acceptance criteria specific & testable | ✓ |
| Dependencies identified | ✓ |
| Story points & priority | ✓ |
| No blocking clarifying questions | ✓ |
| Technical references | ✓ |
| User story format | ✓ |

**Ready for sprint planning**: Yes (in Sprint 5; DoR reaffirmed).

## Notes

- “Collapsible inbox and list work” in the request is interpreted as (1) the **help `<details>`** about those views and (2) **avoiding nested collapsible navigation** for switching Inbox/Today/lists. If the product owner meant only one of these, narrow scope during sprint planning.
- Coordinate with [US-024](US-024-task-details-inline-expand.md) if both land in the same sprint (shared layout work).

## Acceptance Verification

- [x] Criteria verified on mobile viewport and wide viewport
- [x] Documentation-Code Consistency before marking Done

## Post–sprint follow-up (traceability)

Shipped after Sprint 5 closed; same **Tasks shell** (`Index.vue`), improves **perceived responsiveness** when Google round-trips are slow (e.g. **All lists**).

- **Loading overlay** — On **Today / Inbox / All / list** navigation, **Add to list** change (reloads tasks), **completion filter** change (refetch), **search result → open task** (loads that list), or **Retry** after load error: the **task list / board** area shows a **spinner** and **nav-aware** EN/FR copy (`tasks.loadingTasksToday` / Inbox / All / list / named list, with **`tasks.loadingTasks`** fallback) until fetch completes. Ref-counted for overlapping requests. Overlay uses a **short opacity transition**; **`prefers-reduced-motion: reduce`** disables it.

## History

- 2026-03-21 - Created
- 2026-03-21 - Assigned to [Sprint 5](../../sprints/sprint-05-google-tasks-ux-visibility.md); status ⏳ In Progress
- 2026-03-21 - Clarified: semantic toggle **in search panel** + **hints / overflow** for discoverability (middle ground)
- 2026-03-22 - Backlog refinement: DoR recorded; sequencing note vs US-031 added under Dependencies
- 2026-03-22 - Sprint 5 sprint review: acceptance criteria and verification checklist marked complete
- 2026-03-23 - Post–sprint: task list/board loading spinner on scope changes (see **Post–sprint follow-up**); traceability `US-025` in git
- 2026-03-24 - Post–sprint: nav-specific loading strings + reduced-motion-safe fade on overlay (`US-025` commit)
