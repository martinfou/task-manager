# Retrospective Improvement RI-007: Infrastructure removal impact analysis

**Purpose**: Track retrospective improvements from sprint retrospectives. Use RI-XXX for improvements the team commits to implementing.

**Related**: [Sprint Retrospective Process](../../processes/sprint-retrospective-process.md), [Retrospective Output Template](../../templates/retrospective-template.md)

---

## Metadata

| Field | Value |
|-------|-------|
| **ID** | RI-007 |
| **Description** | Before removing infrastructure (defer, cron, cache layers, etc.), do end-to-end impact analysis on all dependent flows. Document what breaks and verify with a test. |
| **Owner** | Developer |
| **Due Sprint** | Sprint 8 |
| **Status** | ✅ Done |
| **Source Retro** | Sprint 7 — 2026-03-29 |

---

## Context

During Sprint 7, `defer()` was removed from the stale-cache path in `cachedView()` and `DashboardController::stats()` to fix a Dreamhost shared hosting issue (defer blocks HTTP response without `fastcgi_finish_request()`). The fix solved the 17-second response delay but introduced DEF-002: mutations mark cache stale, `pollOnce()` returns the stale data, and deleted tasks reappear.

The root cause was that the removal was focused on the immediate symptom (Dreamhost blocking) without tracing the full dependency chain (mutation → markStale → pollOnce → cachedView stale path → returns old data).

## Actionable Steps

- [ ] When modifying or removing infrastructure components, trace all code paths that depend on the component
- [ ] Document expected behavior changes in the commit message or PR description
- [ ] Add or update tests that verify the new behavior under the modified flow
- [ ] For cache-related changes: verify the full mutation → invalidation → re-fetch cycle end-to-end

## Acceptance Criteria

- [x] A checklist or mental model exists for "before removing infrastructure" that includes dependency tracing
- [x] DEF-002 fix includes tests that would have caught the regression — `test_delete_mutation_does_not_return_stale_data` in `TaskViewCachePatchTest.php`

---

## History

- 2026-03-29 — Created (Sprint 7 retrospective: defer removal caused DEF-002 regression)
- 2026-03-30 — Completed: DEF-002 regression test included; actionable steps applied during implementation
