# Retrospective Improvement RI-004: Tasks `Index.vue` integration playbook

**Purpose**: Track retrospective improvements from sprint retrospectives. Use RI-XXX for improvements the team commits to implementing.

**Related**: [Sprint Retrospective Process](../../processes/sprint-retrospective-process.md), [Retrospective Output Template](../../templates/retrospective-template.md)

---

## Metadata

| Field | Value |
|-------|-------|
| **ID** | RI-004 |
| **Description** | Document branch order and merge checklist for concurrent work on `Tasks/Index.vue` |
| **Owner** | Developer |
| **Due Sprint** | Sprint 6 |
| **Status** | ⭕ To Do |
| **Source Retro** | Sprint 5 — 2026-03-22 |

---

## Details

**What**: Add a short developer-facing note (e.g. in `apps/google-tasks/docs/`) that states: when multiple stories touch `Tasks/Index.vue`, follow an explicit integration order (as in sprint planning), rebase/merge frequently, and run `npm run build` + targeted manual QA before merge.

**Why**: Sprint 5 shipped US-024 and US-025 in the same surface; merge contention and regression risk were the main process pain. A playbook reduces repeated conflict and speeds reviews.

**How**: One page or section under existing docs README; link from `apps/google-tasks/README.md` if present.

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
