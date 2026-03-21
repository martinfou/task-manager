---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [product-backlog]
---

# Sprint 4: Google Tasks — Quality, Semantic Search, and Privacy

[← Back to Product Backlog](../backlog/product-backlog.md)

**Sprint Goal**: Ship semantic search over an indexed task corpus, robust API error/retry UX, test pyramid + CI, and disconnect/purge + logging policy — “industry standard” quality bar from discovery.

**Duration**: (set at planning)  
**Team Velocity**: TBD  
**Sprint Planning Date**: (after Sprint 3 retrospective)  
**Sprint Review Date**: (set at planning)  
**Sprint Retrospective Date**: (set at planning)

## Sprint Overview

**Focus Areas**:
- Semantic retrieval (embeddings / hybrid search) with privacy controls
- Reliability UX for Google API failures
- Automated testing and CI gates
- Data lifecycle on disconnect; logging stance

**Key Deliverables**:
- Semantic search MVP with documented retention
- CI green on main
- Clear privacy and logging docs

**Dependencies**:
- [US-013](../backlog/user-stories/US-013-full-text-search.md) recommended before or in parallel with indexing for [US-018](../backlog/user-stories/US-018-semantic-search-task-index.md)

**Risks & Blockers**:
- Embedding cost/latency; French + English quality
- Reindex cost on large task sets

---

## User Stories (this sprint)

| ID | Title | Points | Status |
|----|-------|--------|--------|
| [US-018](../backlog/user-stories/US-018-semantic-search-task-index.md) | Semantic search index | 8 | ⭕ |
| [US-019](../backlog/user-stories/US-019-api-error-retry-ux.md) | API error + retry UX | 3 | ⭕ |
| [US-020](../backlog/user-stories/US-020-test-pyramid-ci.md) | Test pyramid + CI | 5 | ⭕ |
| [US-021](../backlog/user-stories/US-021-disconnect-purge-logging.md) | Disconnect purge + logging | 2 | ⭕ |

**Total Story Points**: 18

---

## Follow-up (backlog, not this sprint)

| ID | Title | Notes |
|----|-------|--------|
| [US-022](../backlog/user-stories/US-022-pwa-install-phase.md) | PWA install | Next phase per discovery |

---

## Sprint Summary

**Sprint Burndown**: (update during sprint)

**Sprint Review Notes**: (fill at review)

**Sprint Retrospective Notes**: (fill at retrospective)

---

## Status Values

- ⭕ **To Do**: Not yet begun
- ⏳ **In Progress**: Currently being worked on
- ✅ **Done**: Done and verified
