---
template_version: 1.1.0
last_updated: 2026-03-21
compatible_with: [retrospective-improvement, sprint-retrospective]
requires: [markdown-support]
---

# Retrospective Improvement RI-003: Semantic search operations runbook

**Purpose**: Track deployment and operations guidance for semantic search (embeddings) so production runs are predictable.

**Related**: [Sprint Retrospective Process](../../processes/sprint-retrospective-process.md), [US-018](../user-stories/US-018-semantic-search-task-index.md)

---

## Metadata

| Field | Value |
|-------|-------|
| **ID** | RI-003 |
| **Description** | Document semantic search deployment operations (reindex, cost, logs) in `apps/google-tasks/docs/DEPLOY.md` |
| **Owner** | Developer |
| **Due Sprint** | Sprint 4 (retro follow-up) |
| **Status** | ✅ Done |
| **Source Retro** | [Sprint 4](../../sprints/sprint-04-google-tasks-quality-and-v2.md) — 2026-03-21 |

---

## Details

**What**: Add an operations section covering when to run reindex, embedding API cost/latency expectations, and which log keys to watch (`google_oauth_*`, etc.).

**Why**: US-018 shipped indexing and search; operators need a single place to read run expectations without reading only code.

**How**: Implemented as a **Semantic search (operations)** section in [DEPLOY.md](../../../apps/google-tasks/docs/DEPLOY.md); cross-links [SEMANTIC_SEARCH.md](../../../apps/google-tasks/docs/SEMANTIC_SEARCH.md) and [DATA_RETENTION.md](../../../apps/google-tasks/docs/DATA_RETENTION.md).

---

## Status Values

- ⭕ **To Do**: Not yet begun
- ⏳ **In Progress**: Currently being worked on
- ✅ **Done**: Done and verified

---

## History

- 2026-03-21 — Created from Sprint 4 retrospective; implemented same day

---

**Last Updated**: 2026-03-21
