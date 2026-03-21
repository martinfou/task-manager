---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-018 - Semantic Search over Tasks (Indexed)

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done  
**Priority**: 🟡 Medium  
**Story Points**: 8  
**Created**: 2026-03-21  
**Updated**: 2026-03-21  
**Assigned Sprint**: [Sprint 4](../../sprints/sprint-04-google-tasks-quality-and-v2.md)

## Description

**Semantic search** is not provided by Google Tasks API. Implement a **server-side index** of synced task titles/notes with embedding or hybrid retrieval; privacy and retention documented. Depends on cached task text ([US-013](US-013-full-text-search.md) may use simpler index first — this story upgrades to semantic).

## User Story

As a user, I want to find tasks by meaning (“that thing about invoices”), not only exact keywords, so that search feels modern and fast.

## Acceptance Criteria

- [x] Architecture doc: embedding provider (local vs API), data stored, delete on user disconnect.
- [x] Search UI mode or unified search with “semantic” quality; measure latency targets.
- [x] Reindex on sync or periodic job; handle API changes.
- [x] English + French query handling as feasible for chosen model.

## Business Value

Differentiates from basic Google UI; matches discovery request for semantic search.

## Dependencies

- [US-008](US-008-google-tasks-sync-engine.md), [US-013](US-013-full-text-search.md) recommended first

## History

- 2026-03-21 - Created from discovery questionnaire
- 2026-03-21 - Implemented: `task_embeddings` + OpenAI-compatible `EmbeddingClient`, `TaskEmbeddingIndexer`, `TaskSemanticSearcher`, `GET /tasks/data/search?mode=semantic`, `POST /tasks/data/search/reindex`, `google-tasks:reindex-embeddings`, UI mode + build-index CTA; [docs/SEMANTIC_SEARCH.md](../../../apps/google-tasks/docs/SEMANTIC_SEARCH.md)
