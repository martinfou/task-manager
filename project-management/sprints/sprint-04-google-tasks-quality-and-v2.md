---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [product-backlog]
---

# Sprint 4: Google Tasks — Quality, Semantic Search, and Privacy

[← Back to Product Backlog](../backlog/product-backlog.md)

**Sprint Goal**: Ship semantic search over an indexed task corpus, robust API error/retry UX, test pyramid + CI, and disconnect/purge + logging policy — “industry standard” quality bar from discovery.

**Duration**: 2026-03-21 — 2026-04-04 (2 weeks)  
**Team Velocity**: **28** points delivered in [Sprint 3](sprint-03-google-tasks-productivity.md); Sprint 4 commits **18** points (US-018–US-021). **−10 pts vs last sprint** — room for spikes on embeddings and indexing.  
**Sprint Planning Date**: 2026-03-21 (after Sprint 3 review + retrospective)  
**Sprint Review Date**: 2026-04-04  
**Sprint Retrospective Date**: 2026-04-04  
**Sprint status**: **Active** — committed scope [US-018](../backlog/user-stories/US-018-semantic-search-task-index.md)–[US-021](../backlog/user-stories/US-021-disconnect-purge-logging.md) (**18 / 18** story points planned).

## Sprint planning record (2026-03-21)

**Prerequisites met**: [Sprint 3](sprint-03-google-tasks-productivity.md) sprint review and retrospective sections completed.

| Step | Result |
|------|--------|
| Backlog metrics | `./project-management/scripts/backlog-metrics.sh --stats` |
| Definition of Ready | US-018–US-021 reviewed; [US-013](../backlog/user-stories/US-013-full-text-search.md) ✅ (supports US-018 indexing context) |
| Capacity | Solo dev; velocity 28 last sprint; committed **18** pts — US-018 is largest (8); order below de-risks API reliability and test gates before indexing work |
| Branching | Per [ADR-002](../architecture-decision-records/ADR-002-branching-strategy.md): `feature/US-XXX-short-description` per story |

### Committed implementation order

1. [US-019](../backlog/user-stories/US-019-api-error-retry-ux.md) — Google API error handling and retry UX (users see failures early; informs indexing error paths)  
2. [US-020](../backlog/user-stories/US-020-test-pyramid-ci.md) — Test pyramid + CI gates (strengthens safety net before semantic search)  
3. [US-018](../backlog/user-stories/US-018-semantic-search-task-index.md) — Semantic search over indexed tasks (embeddings / hybrid; privacy documented)  
4. [US-021](../backlog/user-stories/US-021-disconnect-purge-logging.md) — Disconnect, purge, and logging policy (data lifecycle + observability)

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
| [US-018](../backlog/user-stories/US-018-semantic-search-task-index.md) | Semantic search index | 8 | ✅ |
| [US-019](../backlog/user-stories/US-019-api-error-retry-ux.md) | API error + retry UX | 3 | ✅ |
| [US-020](../backlog/user-stories/US-020-test-pyramid-ci.md) | Test pyramid + CI | 5 | ✅ |
| [US-021](../backlog/user-stories/US-021-disconnect-purge-logging.md) | Disconnect purge + logging | 2 | ⭕ |

**Total Story Points**: 18

---

## Follow-up (backlog, not this sprint)

| ID | Title | Notes |
|----|-------|--------|
| [US-022](../backlog/user-stories/US-022-pwa-install-phase.md) | PWA install | Next phase per discovery |

---

## Sprint Summary

**Sprint Burndown**:
- 2026-03-21: +3 story points ([US-019](../backlog/user-stories/US-019-api-error-retry-ux.md)); **15** points remaining (US-018, US-020, US-021)
- 2026-03-21: +5 story points ([US-020](../backlog/user-stories/US-020-test-pyramid-ci.md)); **10** points remaining (US-018, US-021)
- 2026-03-21: +8 story points ([US-018](../backlog/user-stories/US-018-semantic-search-task-index.md)); **2** points remaining (US-021)

**Sprint Review Notes**: (fill at review on 2026-04-04)

**Sprint Retrospective Notes**: (fill at retrospective on 2026-04-04)

---

## Status Values

- ⭕ **To Do**: Not yet begun
- ⏳ **In Progress**: Currently being worked on
- ✅ **Done**: Done and verified
