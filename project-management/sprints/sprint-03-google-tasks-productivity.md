---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [product-backlog]
---

# Sprint 3: Google Tasks — Productivity Layer

[← Back to Product Backlog](../backlog/product-backlog.md)

**Sprint Goal**: Add i18n (EN/FR), filters + Kanban, search, keyboard shortcuts, priority encoding in Google, links in tasks, and bulk actions — matching TickTick-style power without leaving Google Tasks as source of truth.

**Duration**: 2026-03-21 — 2026-04-04 (2 weeks)  
**Team Velocity**: 26 points delivered in [Sprint 2](sprint-02-google-tasks-mvp-foundation.md); Sprint 3 commits **28** points (US-011–US-017). **+2 pts vs last sprint** — if capacity tight, drop scope from the bottom: Kanban polish in US-012 last.  
**Sprint Planning Date**: 2026-03-21 (after Sprint 2 review + retrospective)  
**Sprint Review Date**: 2026-04-04  
**Sprint Retrospective Date**: 2026-04-04  
**Sprint status**: In progress — [US-011](../backlog/user-stories/US-011-i18n-en-fr.md) ✅, [US-013](../backlog/user-stories/US-013-full-text-search.md) ✅, [US-014](../backlog/user-stories/US-014-keyboard-shortcuts.md) ✅, [US-015](../backlog/user-stories/US-015-priority-encoded-in-google.md) ✅, [US-016](../backlog/user-stories/US-016-links-attachments-tasks.md) ✅, [US-017](../backlog/user-stories/US-017-bulk-actions.md) ✅; next [US-012](../backlog/user-stories/US-012-filters-kanban.md) ⭕.

## Sprint planning record (2026-03-21)

**Prerequisites met**: [Sprint 2](sprint-02-google-tasks-mvp-foundation.md) sprint review and retrospective sections completed.

| Step | Result |
|------|--------|
| Backlog metrics | `./project-management/scripts/backlog-metrics.sh --stats`: 23 items; ⭕ 13 / ⏳ 0 / ✅ 10; 87 total story points in backlog |
| Definition of Ready | US-011–US-017 meet [Definition of Ready](../criteria/definition-of-ready.md); dependencies on Sprint 2 (US-008, US-009) satisfied |
| Capacity | Solo dev; velocity 26; committed 28 pts — sequence below de-risks slip |
| Branching | Per [ADR-002](../architecture-decision-records/ADR-002-branching-strategy.md): first branch for US-011: `feature/US-011-i18n-en-fr` |

**Related improvements** (parallel, same sprint): [RI-001](../backlog/retrospective-improvements/RI-001-google-cloud-oauth-checklist.md), [RI-002](../backlog/retrospective-improvements/RI-002-ci-workflow-google-tasks.md).

### Committed implementation order

1. [US-011](../backlog/user-stories/US-011-i18n-en-fr.md) — i18n (no Sprint 3 dependencies; localizes UI for subsequent work)  
2. [US-013](../backlog/user-stories/US-013-full-text-search.md) — full-text search  
3. [US-014](../backlog/user-stories/US-014-keyboard-shortcuts.md) — keyboard shortcuts + help  
4. [US-015](../backlog/user-stories/US-015-priority-encoded-in-google.md) — priority encoding in Google-visible fields  
5. [US-016](../backlog/user-stories/US-016-links-attachments-tasks.md) — links in notes  
6. [US-017](../backlog/user-stories/US-017-bulk-actions.md) — bulk actions  
7. [US-012](../backlog/user-stories/US-012-filters-kanban.md) — filters + Kanban (8 pts; highest integration risk — last)

## Sprint Overview

**Focus Areas**:
- Localization (English + French)
- Board and filter UX
- Full-text search foundation
- Desktop keyboard power; bulk operations

**Key Deliverables**:
- Filtered views and Kanban documented against API capabilities
- Priority scheme stored in Google-visible fields
- Bulk complete/move/delete with safe confirmations

**Dependencies**:
- Sprint 2 complete (sync + navigation stable)

**Risks & Blockers**:
- Kanban column model may require product compromise vs API
- Priority encoding must stay reversible in other clients

---

## User Stories

### Story 1: US-011 — Internationalization (English and French) — 3 Points

**Backlog**: [US-011](../backlog/user-stories/US-011-i18n-en-fr.md)

**Status**: ✅ Done

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-025 | Vue i18n (or equivalent) + key structure for shell and Tasks UI | US-011 | ✅ |
| T-026 | Language switcher; persist locale (profile or localStorage + server) | US-011 | ✅ |
| T-027 | Locale-aware date/time formatting; README note on translations | US-011 | ✅ |

---

### Story 2: US-012 — Filters and Kanban view — 8 Points

**Backlog**: [US-012](../backlog/user-stories/US-012-filters-kanban.md)

**Status**: ⭕ To Do

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-028 | Filter panel / query bar (list, due range, completion state) | US-012 | ⭕ |
| T-029 | Kanban columns definition documented; board UI | US-012 | ⭕ |
| T-030 | Drag/move flow mapped to API; document unsupported cases | US-012 | ⭕ |
| T-031 | Virtualization or pagination for large lists | US-012 | ⭕ |

---

### Story 3: US-013 — Full-text search across tasks — 3 Points

**Backlog**: [US-013](../backlog/user-stories/US-013-full-text-search.md)

**Status**: ✅ Done

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-032 | Search entry in shell; client or server index (choose + document) | US-013 | ✅ |
| T-033 | Results list with list name and snippet | US-013 | ✅ |
| T-034 | Document scope limits (memory vs server index) | US-013 | ✅ |

---

### Story 4: US-014 — Keyboard shortcuts (desktop) — 3 Points

**Backlog**: [US-014](../backlog/user-stories/US-014-keyboard-shortcuts.md)

**Status**: ✅ Done

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-035 | Shortcuts: new task, complete, focus, Today/Inbox, search (minimum set) | US-014 | ✅ |
| T-036 | `?` or Ctrl+/ opens shortcut help overlay | US-014 | ✅ |
| T-037 | Document overrides vs browser defaults | US-014 | ✅ |

---

### Story 5: US-015 — Priority encoded into Google Tasks — 3 Points

**Backlog**: [US-015](../backlog/user-stories/US-015-priority-encoded-in-google.md)

**Status**: ✅ Done

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-038 | Encoding scheme; parse/write from API payloads | US-015 | ✅ |
| T-039 | Priority UI without raw title editing | US-015 | ✅ |
| T-040 | Default level for tasks without encoded priority | US-015 | ✅ |

---

### Story 6: US-016 — Links and attachments in tasks — 3 Points

**Backlog**: [US-016](../backlog/user-stories/US-016-links-attachments-tasks.md)

**Status**: ✅ Done

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-041 | Detect URLs in notes; safe link rendering (new tab) | US-016 | ✅ |
| T-042 | Document “attachment” vs Tasks API limits | US-016 | ✅ |
| T-043 | Optional paste handler for quick link add | US-016 | ✅ |

---

### Story 7: US-017 — Bulk actions on tasks — 5 Points

**Backlog**: [US-017](../backlog/user-stories/US-017-bulk-actions.md)

**Status**: ✅ Done

**Tasks**:

| Task ID | Task Description | Document Reference | Status |
|---------|------------------|---------------------|--------|
| T-044 | Multi-select (shift/ctrl desktop); mobile pattern documented | US-017 | ✅ |
| T-045 | Bulk complete, delete, move-to-list; batch API calls | US-017 | ✅ |
| T-046 | Confirmations; partial failure summary | US-017 | ✅ |

---

## Sprint Summary

**Total Story Points**: 28  
**Total Tasks**: 22 (T-025–T-046)

**Sprint Burndown**:
- 2026-03-21: 12 story points completed ([US-011](../backlog/user-stories/US-011-i18n-en-fr.md), [US-013](../backlog/user-stories/US-013-full-text-search.md), [US-014](../backlog/user-stories/US-014-keyboard-shortcuts.md), [US-015](../backlog/user-stories/US-015-priority-encoded-in-google.md)); **16** points remaining in sprint scope (US-012, US-016–US-017)
- 2026-03-21: +3 story points ([US-016](../backlog/user-stories/US-016-links-attachments-tasks.md)); **13** points remaining (US-012, US-017)
- 2026-03-21: +5 story points ([US-017](../backlog/user-stories/US-017-bulk-actions.md)); **8** points remaining ([US-012](../backlog/user-stories/US-012-filters-kanban.md) only)

**Sprint Review Notes**: (fill at review on 2026-04-04)

**Sprint Retrospective Notes**: (fill at retrospective on 2026-04-04)

---

## Status Values

- ⭕ **To Do**: Not yet begun
- ⏳ **In Progress**: Currently being worked on
- ✅ **Done**: Done and verified
