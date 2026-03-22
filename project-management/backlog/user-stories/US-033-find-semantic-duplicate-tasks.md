---
template_version: 1.1.0
last_updated: 2026-03-22
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-033 - Find Semantic Duplicate Tasks Across Lists

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🟡 Medium  
**Story Points**: 13  
**Created**: 2026-03-21  
**Updated**: 2026-03-22  
**Assigned Sprint**: Backlog

## Description

Users sometimes capture the **same commitment more than once** in **different Google task lists**, using **different wording** (synonyms, abbreviations, or rephrased titles). **Exact** or **fuzzy string** matching misses these cases. This story adds a way to **surface likely duplicates by meaning** using the same semantic representation approach as task search ([US-018](US-018-semantic-search-task-index.md)): compare embeddings (or equivalent) for titles and, where available, notes/snippets, and present **candidate duplicate groups or pairs** for human review. The product **never auto-merges or auto-deletes**; the user may run an explicit **one-tap merge** (after choosing which task to keep) to **complete or delete** the other, with confirmation and undo/destructive UX aligned with [US-029](US-029-undo-toast-destructive-actions.md) where applicable.

## User Story

As a user who manages many lists, I want to **discover tasks that mean the same thing** even when the **words differ**, so that I can **clean up duplicates** and trust one source of truth per commitment.

## Acceptance Criteria

- [ ] The user can open a **“Find similar / possible duplicates”** flow (name TBD) from an obvious place (e.g. Tasks tools menu, profile, or search area — document choice in implementation).
- [ ] The system computes or reuses **semantic similarity** among **incomplete** tasks only (**completed** tasks are **excluded** from the scan and from candidate pairs in this story). Candidates are **pairs or small clusters** above a **documented similarity threshold** (tunable server-side or via config).
- [ ] Each candidate shows **at least**: task titles, **list names**, completion state, and a **clear “why suggested”** hint (e.g. similarity score band or “Likely duplicate” label — no raw embedding dumps in UI).
- [ ] The user can **open each task** in context (same patterns as elsewhere in the app) to compare details. **Browsing candidates alone** does not change tasks.
- [ ] **One-tap merge** (per pair or small group): user **chooses the task to keep**, then one explicit action **completes or deletes** the other task(s) (exact affordances i18n EN/FR). **Confirmation** required before destructive effect; **undo/toast** behavior aligns with [US-029](US-029-undo-toast-destructive-actions.md) for complete/delete where technically reliable.
- [ ] **Merge hint (notes)**: when **note detail** is uneven (e.g. **one task has non-empty notes**, the other **empty** — or **clearly more** note text by a **documented** rule such as character threshold), show a **non-binding** EN/FR hint that **often** the task **with more notes** is the better **keeper**; **user may override** (e.g. pre-select suggested keeper in radio/list UI **or** inline tip only — implementer choice). When **no** meaningful difference, stay **neutral**. **Never** auto-merge from this hint alone.
- [ ] **Privacy and data handling** align with [US-018](US-018-semantic-search-task-index.md): embeddings/index scope, disconnect purge, and documentation updated if new data is stored or new jobs run.
- [ ] **English and French** task text is handled **as feasible** with the chosen model (same bar as semantic search).
- [ ] **Performance** is acceptable for typical personal accounts (document expected limits, e.g. batching, background job, or progressive results); degrade gracefully with messaging if the account is very large.
- [ ] **Automated tests** cover similarity grouping logic (fixtures), API authorization, and edge cases (single task, no index, empty lists).

## Business Value

Reduces **mental load and clutter** from accidental double capture across lists, improves **trust** in the task system, and differentiates the client from plain Google Tasks UIs that only offer literal search.

## Technical Requirements

- **Prefer reusing** existing embedding pipeline and storage from [US-018](US-018-semantic-search-task-index.md) (e.g. reindex, dimension, provider); avoid a second parallel embedding stack unless justified in **Notes**.
- Similarity search may be **nearest-neighbor** per task, **blocking** (e.g. by list or first token), or **clustering** — choose an approach that balances **recall** with **cost** and **latency**; document in technical references.
- **Idempotency**: re-running duplicate detection does not create duplicate artifacts server-side; results are derived from current task set and index version.
- Optional **rate limits** or **cooldown** if detection is expensive; document for operators.

## Reference Documents

- [INDEX.md](../../INDEX.md) — backlog entry points
- [US-018](US-018-semantic-search-task-index.md) — semantic index and search
- [US-023](US-023-all-tasks-all-lists.md) — cross-list visibility (mental model)
- `apps/google-tasks/docs/SEMANTIC_SEARCH.md` — embedding operations and privacy (extend or link duplicate-detection section)

## Technical References

- Semantic search / embedding services and tables introduced under US-018 (e.g. `TaskSemanticSearcher`, `TaskEmbeddingIndexer`, `task_embeddings` — adjust paths when implementing)
- `apps/google-tasks/app/Http/Controllers/TasksController.php` — task visibility, updates, complete/delete for **merge** flow

## Dependencies

- [US-018](US-018-semantic-search-task-index.md) — **done**; provides embeddings foundation
- [US-008](US-008-google-tasks-sync-engine.md) — **done**; tasks must be synced for meaningful coverage
- [US-029](US-029-undo-toast-destructive-actions.md) — align merge complete/delete with undo/toast patterns

## Clarifying Questions

*AI: Before starting implementation, ask the user clarifying questions. Document questions and answers here after the user responds.*

- **Q**: Should this story include **one-tap merge** (keep one task, complete/delete the other) or **only surfacing** candidates?
- **A**: **Both** — (1) **surface possible duplicates** (semantic candidates), and (2) **one-tap merge** after user picks **keeper** and confirms (complete or delete the other); no automatic merge/delete.
- **Date**: 2026-03-22

- **Q**: Should **completed** tasks participate by default, or only **incomplete**?
- **A**: **Incomplete only** — duplicate detection runs on **active / needsAction** tasks **only**; **completed** tasks are **out of scope** for this story (optional “include completed” can be a **follow-up** if needed).
- **Date**: 2026-03-22

- **Q**: On merge, if one task has **richer notes**, **suggest keeper** or stay **neutral**?
- **A**: **Suggest** — show a **soft recommendation** (EN/FR) favoring the task **with more note content** when the gap is **clear** (empty vs non-empty or heuristic threshold); **user always confirms** keeper; **override** allowed.
- **Date**: 2026-03-22

## Refinement (2026-03-22)

Session: [Backlog refinement — all user stories](../../sprints/backlog-refinement-session-2026-03-22.md).

| Definition of Ready | Met |
|---------------------|-----|
| Acceptance criteria specific & testable | ✓ |
| Dependencies identified | ✓ |
| Story points & priority | ✓ |
| No blocking clarifying questions | ✓ |
| Technical references | ✓ |
| User story format | ✓ |

**Ready for sprint planning**: Yes. **Sizing**: 13 points kept as one item; **optional split** if capacity forces scope cut — **Phase A** surfacing only, **Phase B** merge + confirmation (document new IDs only if PO splits).

## Notes

- False positives are expected; copy should say **“possible duplicates”** and encourage user judgment before merge.
- **Notes-based merge hint** is a **suggestion only** (see Acceptance Criteria); subtasks / attachments are **out of scope** unless already modeled.
- **Merge** increases UX and API surface; spike **delete undo** reliability with Google if needed (see US-029 notes).
- If implementation uses **symmetric** pair generation, deduplicate pairs (A–B vs B–A) in UI and APIs.

## Acceptance Verification

**Complete before marking status as Done.** Verify each acceptance criterion is met, then mark with `[x]`.

- [ ] All acceptance criteria above verified as met
- [ ] Each criterion tested or inspected and confirmed

## History

- 2026-03-21 - Created (semantic duplicate detection across lists)
- 2026-03-22 - Clarified: scope includes **one-tap merge** + surfacing; story points **13**
- 2026-03-22 - Clarified: duplicate detection **incomplete tasks only**
- 2026-03-22 - Clarified: **soft hint** to prefer keeper with **richer notes** when clear; user overrides
- 2026-03-22 - Backlog refinement: DoR recorded; optional Phase A/B split noted for capacity only
