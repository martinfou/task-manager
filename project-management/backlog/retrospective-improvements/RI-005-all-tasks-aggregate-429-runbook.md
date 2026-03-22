# Retrospective Improvement RI-005: All-tasks aggregate — rate limit and partial failure runbook

**Purpose**: Track retrospective improvements from sprint retrospectives. Use RI-XXX for improvements the team commits to implementing.

**Related**: [Sprint Retrospective Process](../../processes/sprint-retrospective-process.md), [Retrospective Output Template](../../templates/retrospective-template.md)

---

## Metadata

| Field | Value |
|-------|-------|
| **ID** | RI-005 |
| **Description** | Operator runbook snippet for Google 429 / partial list failure on all-lists aggregate |
| **Owner** | Developer |
| **Due Sprint** | Sprint 6 |
| **Status** | ⭕ To Do |
| **Source Retro** | Sprint 5 — 2026-03-22 |

---

## Details

**What**: Document in `apps/google-tasks/docs/` (e.g. `DEPLOY.md` or operational README): what users see when the **All tasks** aggregate hits rate limits or returns partial list errors, which config knobs exist (`poll_interval_ms`, backoff), and recommended support responses (retry, reduce lists, check Google quotas).

**Why**: US-023 introduced multi-list aggregation; production operators and power users need a single place to interpret failures without reading source.

**How**: Short subsection with bullets; cross-link to existing Google API error UX ([US-019](../user-stories/US-019-api-error-retry-ux.md)) where relevant.

---

## Status Values

- ⭕ **To Do**: Not yet begun
- ⏳ **In Progress**: Currently being worked on
- ✅ **Done**: Done and verified

---

## History

- 2026-03-22 — Created from Sprint 5 retrospective

---

**Last Updated**: 2026-03-22
