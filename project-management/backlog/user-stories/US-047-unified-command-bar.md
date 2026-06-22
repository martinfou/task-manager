# User Story: US-047 — Unified Command Bar (Ctrl+K Quick Capture)

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🟠 High  
**Story Points**: 8  
**Created**: 2026-06-21  
**Updated**: 2026-06-21  
**Assigned Sprint**: Backlog

## Description

A Ctrl+K (or Cmd+K) command palette that lets Martin create tasks, search tasks, and navigate the app — all from one keyboard shortcut. The core use case is **sub-3-second task capture**: type natural language, it parses the list, due date, and priority instantly.

## User Story

As a daily power user,  
I want to press Ctrl+K and type a task in natural language like `"review PR by friday Work p2"`,  
so that I can capture tasks at the speed of thought without touching the mouse.

## Acceptance Criteria

- [ ] Ctrl+K / Cmd+K opens a full-screen modal from any view
- [ ] Typing `"buy milk tomorrow Home"` creates a task titled "buy milk" in Home list, due tomorrow, priority p3 (default)
- [ ] Typing `"review PR by friday #Work p2"` creates in Work list, due Friday, priority p2
- [ ] French input supported: `"acheter lait demain Maison p3"` works identically
- [ ] Fuzzy list name matching: `"Hm"` → "Home", `"Trd"` → "Trading", `"Nxt"` → "Next Actions"
- [ ] `@` suffix syntax for notes: `"buy milk @ comment:lactose-free"` appends description
- [ ] Due date keywords: `tomorrow`, `friday`, `next monday`, `mar 28`, `in 3 days` (via chrono-node)
- [ ] Priority notation: `p1`, `p2`, `p3`, `p4` anywhere in input
- [ ] Defaults: no date = Today view, no priority = p3, no list = Inbox
- [ ] Command palette also works for navigation: type `/> today`, `/> inbox`, `/> trading`
- [ ] Escape or click outside closes without creating
- [ ] Works offline (100% client-side parsing)

## Business Value

Martin's biggest daily friction is the multi-step create-a-task flow (click → form → select list → date → priority). This reduces it from ~8 interactions to 1. At 10-20 tasks/day, that's significant time saved. Also reduces cognitive friction — ideas captured before they vanish.

## Technical Requirements

- Pure client-side (Vue component, no Laravel backend changes)
- Use `chrono-node` library for natural language date parsing (npm package, lightweight, supports EN + FR locales)
- Regex-based NLP parser for list/priority/tag extraction
- Fuzzy list matching via Levenshtein distance on list names (client-side)
- Existing task creation endpoint (`POST /{taskList}/tasks`) unchanged
- Must work on mobile (swipe-down gesture or FAB shortcut)
- Must respect existing keyboard shortcut typing-context guard (no hijacking in inputs)

## Reference Documents

- Todoist natural language input (reference)
- Linear command palette (reference)
- US-028 (Command Palette — Navigate, Search, and Quick Add) — existing implementation

## Technical References

- File: `resources/js/Components/` — new `CommandPalette.vue`
- File: `resources/js/Composables/` — new `useQuickCapture.js`
- NPM: `chrono-node` (date parsing)
- Existing: US-028 command palette code in `resources/js/`

## Dependencies

- None — fully client-side, can be built in any order

## Clarifying Questions

- **Q**: Should the command bar also support editing tasks? (e.g., selecting a task then pressing Ctrl+K)
- **A**: Not in MVP — capture-only for now.

## History

- 2026-06-21 — Created
