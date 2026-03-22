# Backlog Refinement Session — All User Stories

[← Product backlog](../backlog/product-backlog.md) · [INDEX](../INDEX.md)

**Status**: **Completed** (documentation pass)  
**Held**: 2026-03-22  
**Suggested duration** (plan): **2 × 60–90 min** (or **1 × 2.5 h** with breaks) — scope is **34** user stories  
**Process**: [Backlog management — Refinement](../processes/backlog-management-process.md#backlog-refinement)  
**Gate**: [Definition of Ready](../criteria/definition-of-ready.md)

---

## Attach (per [INDEX](../INDEX.md) — refine backlog item)

| File | Use |
|------|-----|
| [backlog-management-process.md](../processes/backlog-management-process.md) | Agenda, refinement checklist |
| [product-backlog-structure.md](../processes/product-backlog-structure.md) | Table conventions, IDs |
| [definition-of-ready.md](../criteria/definition-of-ready.md) | Ready gate before sprint selection |
| [product-backlog.md](../backlog/product-backlog.md) | Source of truth for status, points, priorities |

Optional: active sprint [sprint-05-google-tasks-ux-visibility.md](sprint-05-google-tasks-ux-visibility.md), `backlog-metrics.sh`, `lint-project-management.sh`.

---

## Participants

- Product Owner (priorities, scope cuts)
- Scrum Master / facilitator (timebox, DoR checklist)
- Dev team (estimates, dependencies, technical risk)

---

## Objectives

1. **Every** `US-001`–`US-034` touched in a **defined** way (full DoR vs light audit vs defer).
2. **⭕ To Do** and **⏳ In Progress** stories are **Ready** or have **explicit** follow-ups (open questions, spikes, split).
3. Dependencies and **order** updated in story files + [product backlog](../backlog/product-backlog.md) where needed.
4. **✅ Done** stories: **sampled** light audit (doc/code drift, broken links) — not full re-estimation.

---

## Agenda (from process)

1. Review **new / changed** items since last refinement (US-027–US-034, edits to US-023+).
2. Clarify requirements; resolve or **record** open **Clarifying Questions** in story files.
3. Confirm or re-estimate **story points** (Fibonacci).
4. Identify **dependencies**; update story **Dependencies** sections.
5. **Sort** candidates by dependency order for upcoming sprints.
6. **Prioritize** within dependency bands.
7. **Split** items > 8 points if still fuzzy (target ≤ 8 per slice where possible).
8. Remove or **archive** **obsolete** items (none this pass).

---

## Timeboxed plan (executed)

| Block | Focus | Result |
|-------|--------|--------|
| **A** | **⏳** US-023–US-026 | DoR reaffirmed; **Refinement (2026-03-22)** + History on each story; US-024 deps note US-026/US-034; US-025 sequencing vs US-031; US-026 note for US-034/US-014 |
| **B** | **⭕** US-027–US-034 | DoR reaffirmed; **Refinement** sections; US-034 **Dependencies** set to US-024 + US-014; US-027/US-033 sizing notes (vertical slices / optional Phase A·B) |
| **C** | **✅** sample US-008, US-009, US-012, US-013, US-018 | Light audit: links and AC still plausible vs product; **History** line on each; no new defect opened |
| **D** | **Product backlog** | [product-backlog.md](../backlog/product-backlog.md) **Last Updated** + **Backlog Statistics** aligned with `./project-management/scripts/backlog-metrics.sh --stats` |

**Deferred**: Full light audit of remaining **✅** stories (US-001–US-007, US-010, US-011, US-014–US-017, US-019–US-022) — schedule next refinement or spot-check when those areas change.

---

## Per-item checklist (full pass — ⭕ / ⏳)

Applied via **Refinement (2026-03-22)** on each of US-023–US-034:

- [x] Description clear; **As a / I want / So that** present
- [x] Acceptance criteria **specific** and **testable**
- [x] Story points set; **priority** set
- [x] **Technical references** / dependencies listed (updates where gaps found)
- [x] **Business value** stated
- [x] **Definition of Ready**: no blocking open questions (spikes documented in-story, e.g. US-029 delete undo)

## Per-item checklist (light pass — ✅ sample)

- [x] Story still **accurate** vs product; links from story work
- [x] No AC vs code mismatch warranting a new defect this pass

---

## Inventory — all user stories

### ⏳ In Progress — Sprint 5

| ID | Title | Pts | Refinement |
|----|-------|-----|------------|
| [US-023](../backlog/user-stories/US-023-all-tasks-all-lists.md) | All Tasks Across All Lists | 8 | Ready (Sprint 5) |
| [US-024](../backlog/user-stories/US-024-task-details-inline-expand.md) | Task Details Expand Below the Row | 5 | Ready; coordinate US-026, US-034 |
| [US-025](../backlog/user-stories/US-025-mobile-tasks-shell-improvements.md) | Mobile Tasks Shell | 8 | Ready; prefer before/with US-031 |
| [US-026](../backlog/user-stories/US-026-double-click-edit-task.md) | Double-Click to Edit | 3 | Ready |

### ⭕ To Do — backlog

| ID | Title | Pts | Refinement |
|----|-------|-----|------------|
| [US-027](../backlog/user-stories/US-027-consistent-dates-priority-across-views.md) | Consistent Dates and Priority | 13 | Ready; deliver as vertical slices if needed |
| [US-028](../backlog/user-stories/US-028-command-palette-navigation-quick-add.md) | Command Palette | 8 | Ready; “All tasks” when US-023 Done |
| [US-029](../backlog/user-stories/US-029-undo-toast-destructive-actions.md) | Undo Toast | 3 | Ready; delete undo spike in AC/Notes |
| [US-030](../backlog/user-stories/US-030-snooze-defer-presets.md) | Snooze / Defer | 5 | Ready |
| [US-031](../backlog/user-stories/US-031-mobile-swipe-task-actions.md) | Mobile Swipe | 5 | Ready; after US-025 if merge risk |
| [US-032](../backlog/user-stories/US-032-kanban-due-date-lanes.md) | Kanban Due-Date Lanes | 8 | Ready |
| [US-033](../backlog/user-stories/US-033-find-semantic-duplicate-tasks.md) | Semantic Duplicate Find | 13 | Ready; optional Phase A/B split if PO cuts scope |
| [US-034](../backlog/user-stories/US-034-enter-key-save-task-edit.md) | Enter to Save Edit | 2 | Ready **with** US-024 |

### ✅ Done — sample audited

US-008 · US-009 · US-012 · US-013 · US-018 (History updated). **Others** not individually audited this session.

---

## Outputs

- [x] **Ready** list for next sprint planning: **US-027, US-028, US-029, US-030, US-031, US-032, US-033, US-034** (all ⭕ above meet DoR). **Suggested pull order** (dependencies): finish **Sprint 5** (US-023→024→025→026) → **US-029** (undo baseline for destructive flows) → **US-028** (after US-023 for full “All tasks” command) → **US-030** → **US-031** (after **US-025** if possible) → **US-032** → **US-027** (large) → **US-033** → **US-034** (with **US-024**).
- [x] **Split** recommended: **None** in backlog tables. **If capacity constrained**: US-027 → vertical slices in one story; US-033 → optional Phase A (surfacing) / Phase B (merge) as future split **only if** PO creates new IDs.
- [x] **Spikes** / open questions: **Delete undo** feasibility remains in **US-029** (documented); no new spikes opened.
- [x] **Product backlog** table: stats corrected (**8** ⭕, **35** items incl. DEF-001, **168** pts); **Last Updated** set.
- [x] **Sprint 5** doc: refinement cross-reference added (see below).

---

## Sprint 5 cross-reference

[Sprint 5](sprint-05-google-tasks-ux-visibility.md) committed scope unchanged. **2026-03-22**: Full-story refinement session reaffirmed DoR for US-023–US-026 and recorded **Refinement (2026-03-22)** blocks on those story files.

---

## History

- 2026-03-22 — Session plan created (all-user-stories refinement; INDEX-aligned)
- 2026-03-22 — **Session executed**: Blocks A–D; DoR sections on US-023–US-034; light audit on US-008, US-009, US-012, US-013, US-018; product backlog statistics updated
