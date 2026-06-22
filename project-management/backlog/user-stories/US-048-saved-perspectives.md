# User Story: US-048 — Saved Perspectives (Filtered Views)

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🟠 High  
**Story Points**: 5  
**Created**: 2026-06-21  
**Updated**: 2026-06-21  
**Assigned Sprint**: Backlog

## Description

Named, saved view filters that Martin can switch between with one click or keyboard shortcut. Each perspective stores: which lists to show, an optional search query, and a sort order. Martin can create "Trading" (Next Actions with @trading), "Dev" (Next Actions with @work), "Weekly Review" (all lists, stale-first) at a keystroke.

## User Story

As a GTD practitioner with multiple life domains,  
I want to save filtered views as named perspectives and switch between them with Ctrl+1/2/3,  
so that I can focus on one domain at a time without mentally filtering noise.

## Acceptance Criteria

- [ ] Perspectives stored in localStorage (client-side, no backend)
- [ ] Creating a perspective captures: list selection, search query, sort order
- [ ] Switching perspectives applies all filters instantly (no page reload)
- [ ] Default perspectives pre-configured: "All Tasks", "Trading" (@trading/@finance filter), "Today/Overdue"
- [ ] Keyboard shortcuts: Ctrl+1/2/3/4 for first 4 perspectives
- [ ] Perspective name and filter config editable (rename, update filters)
- [ ] Deleting a perspective is undo-able (toast with undo)
- [ ] Sweet default: Ctrl+1 = "Next Actions sorted by priority", Ctrl+2 = "All by stale-ness"

## Business Value

Martin has 6 lists but uses them across multiple contexts (work, trading, home, health). Currently he scrolls or remembers which list to open. Perspectives remove that mental overhead — one key chord, the right view appears. Inspired by OmniFocus perspectives.

## Technical Requirements

- 100% client-side (Vue composable + localStorage)
- Config stored as JSON keyed by perspective name
- No migrations, no backend changes
- Must honor existing filter/search architecture

## Dependencies

- None

## History

- 2026-06-21 — Created
