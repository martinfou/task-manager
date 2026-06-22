# User Story: US-049 — Joplin Task Context Bridge

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🟠 High  
**Story Points**: 8  
**Created**: 2026-06-21  
**Updated**: 2026-06-21  
**Assigned Sprint**: Backlog

## Description

When a task's notes field contains a Joplin note link (`:/noteId` or `joplin://noteId`), the app fetches the note title and snippet from Joplin's Web Clipper API and renders an inline preview card below the task. This bridges Martin's second brain (Joplin PARA) with his action system (Google Tasks).

## User Story

As a Joplin PARA user,  
I want to paste a Joplin note ID into a task's description and see an inline preview of that note,  
so that I can connect my task context back to my second brain without leaving the app.

## Acceptance Criteria

- [ ] Pasting `:/aBcDeFgHiJkLmN` into a task's notes renders a preview card below the task title
- [ ] Preview card shows: 📝 icon + note title + first 100 chars of content
- [ ] "Open in Joplin" button opens the Joplin note via `joplin://` protocol
- [ ] Joplin note data cached in SQLite (title + snippet per note ID) to avoid re-fetching
- [ ] If Joplin is offline, shows cached data with "last seen X ago" timestamp
- [ ] If never cached and Joplin is offline, shows "Joplin note (unavailable)" with retry button
- [ ] Joplin URL format also supported: `https://joplinapp.org/notes/aBcDeFgHiJkLmN`
- [ ] Strips the link from the displayed notes text (replaced by the card)
- [ ] Works in task detail expand view (US-024 already exists)
- [ ] Graceful degradation: no error if Joplin API unreachable

## Business Value

This is the single feature that makes tasks.martinfournier.com *Martin's* app rather than just a Google Tasks skin. His entire PARA system lives in Joplin (167 notebooks). Every task references Joplin context — trading plans, project overviews, meeting notes. A clickable bridge between action and reference eliminates context-switching.

## Technical Requirements

- Backend: Joplin Web Clipper REST API (port 41184, localhost only) called via server-side proxy or client-side fetch
- Option A (preferred): Client-side fetch from `http://localhost:41184/notes/{id}` — only works when Martin's desktop is on with Joplin running
- Option B (fallback): Laravel proxy endpoint that forwards to Joplin API, caches result in SQLite
- Cache: new DB table `joplin_note_cache` (note_id, title, snippet, fetched_at)
- Cache TTL: 24 hours (notes rarely change title)

## Dependencies

- Joplin Desktop must be running with Web Clipper enabled for live lookups
- Cached lookups work without Joplin running

## History

- 2026-06-21 — Created
