---
template_version: 1.1.0
last_updated: 2026-03-22
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-027 - Consistent Dates and Priority Across Every View

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done
**Priority**: 🟡 Medium
**Story Points**: 13
**Created**: 2026-03-21
**Updated**: 2026-03-22
**Assigned Sprint**: [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md)

## Description

Product discovery commits to using **due dates fully** (`f_dates`), keeping **priority encoded in Google** visible in the product (`f_priority`), and a **world-class** bar for **ease of use** (`v1_world_class`). Today those capabilities can be uneven across surfaces (standard list, Today/Inbox, Kanban, search/semantic results, mobile). This story makes **due date, overdue state, and priority** appear in a **consistent, scannable way** everywhere tasks are listed, so users do not reopen tasks to understand urgency.

**Discovery source**: [responses-submitted-2026-03-21.json](../../../docs/google-tasks-discovery/responses-submitted-2026-03-21.json) — `f_dates`, `f_priority`, `v1_world_class`.

## User Story

As a user who relies on due dates and priorities, I want them **shown the same way in every task view**, so that I can triage quickly without learning different layouts per screen.

## Acceptance Criteria

- [ ] **Standard task list rows** (per-list, Today, Inbox, and [US-023](US-023-all-tasks-all-lists.md) aggregate when present) show **due date** and **priority** using the same visual rules (order, truncation, compact vs comfortable density). **LTR** (**Question 22**): **priority** appears **before** **due** (left → right) in the row metadata strip; **DOM** / **screen-reader** order matches **visual** order. **RTL**: **mirror** when the app supports mirrored layout direction; until then document **LTR-only** metadata order. In **comfortable** density, tasks **without** a due show a **visible placeholder** (muted “No due” / “Add due” — exact copy EN/FR per i18n), not a blank column. In **compact** density, tasks **without** a due show **both** a **small due/calendar icon** and a **short “—”** in the due slot, with the **full** localized meaning exposed via **`aria-label`** and **tooltip**/`title` (same string as comfortable placeholder — EN/FR); the long phrase is **not** required as primary visible text in compact. **Priority** ([US-015](US-015-priority-encoded-in-google.md)): tasks **without** a parsed priority show **parallel** treatment (**Question 15**): **comfortable** — **visible** muted placeholder (e.g. “No priority” / “Set priority”, **EN/FR**); **compact** — **small priority/flag icon** + **“—”** with **full** meaning in **`aria-label`** and **tooltip**/`title`; not color-only.
- [ ] **Kanban** cards show **due** and **priority** with the **same semantics** as list rows (**priority** before **due** in LTR per **Question 22**; formatting may adapt to card width but meaning, contrast, and **slot order** align).
- [ ] **Search and semantic search** result rows include **due** and **priority** in line with list rows (**priority** before **due**, LTR per **Question 22**; no regression: snippets and highlighting still work).
- [ ] **Overdue** and **due today** treatments are **consistent** across views (copy and styling reference one spec or shared component behavior).
- [ ] **Global list sort** (single persisted preference — see below): two modes, **EN/FR** labels, documented in help: **(A) Due first** — **due-urgency cohorts** (**overdue** → **due today** by **time** when the API provides it → **no due** last among tasks in the current view), **within each cohort** by **priority** (higher first per [US-015](US-015-priority-encoded-in-google.md)), **then** **task title** (**locale-aware**, **case-insensitive** per **Question 17**; **empty** or **whitespace-only** titles sort **before** any non-empty title per **Question 18**; then `localeCompare` with `{ sensitivity: 'base' }` using the **active** UI locale from [US-011](US-011-i18n-en-fr.md)), **then** **final stable tie-breaker** per **Question 16** (**default recommendation**: **Google task `id`** string compare for deterministic order). **(B) Priority first** — by **priority** (high → low), **within same priority** by **due urgency** (overdue → due today → no due), **then** time, **then** title (same empty-title and collation rules), **then** same **final** tie-breaker. **(A)** is the **initial default** for new users / no saved preference. **Apply** this ordering to **client-side ordered lists** for **Today**, **Inbox**, **each per-list** task list. **[US-023](US-023-all-tasks-all-lists.md) All tasks** (**phased** with US-023 — **Question 25**): when **US-027** ships, **migrate** that view from US-023 **Phase 1** (**list → title**) to this **global** comparator (**same rules** within the aggregate task set as other list views). **Kanban** ([US-012](US-012-filters-kanban.md)): **within each column**, apply the **same** **Due first** / **Priority first** rules **client-side** to order cards (**no** API-order exception per **Question 14**); document in help that column order may differ from Google’s native sequence. **Document** the chosen final tie-breaker in **help** and **developer docs** / code (single shared comparator).
- [ ] **Sort control placement**: **Primary** control on **Today** — **Desktop / wide**: **Today** header/toolbar row (dropdown or segmented control), **keyboard operable** (Tab, arrows, Enter, Escape). **Mobile / narrow touch** on Today: **dedicated sort icon** with i18n **`aria-label`**; opens **bottom sheet** or **popover/modal** (industry-standard patterns). **Motion** (**Question 21**): **no** separate **`prefers-reduced-motion`** treatment for this sheet/popover — **same** open/close animation as default UI; **document** as **explicit product choice** (revisit if a11y audit requires). **Other** task views (**Inbox**, **per-list**, **All tasks**, **Kanban**) **surface the same control** (toolbar or icon+sheet per breakpoint) **or** show **current mode** with **one tap** to open the same picker — changing mode in **any** view updates the **global** preference. **Search** result ordering stays **out of scope** for this sort unless already unified (do not regress relevance ranking).
- [ ] **Persist** **one global** sort mode **per user** (server profile preferred if the app already stores user preferences; otherwise **localStorage** with documented limitation until profile exists). **Single** storage key / column; no per-view split. Restored on reload and across sessions on the same browser/device as implemented.
- [ ] **Global sort disclosure** (**option 1**, copy per **Question 19**; **capitalization** per **Question 24**): on **every** task **list** or **Kanban** view that shows the sort control, include **inline** (not tooltip-only) **short** helper text **next to or directly under** the control — **contextual** strings in **EN/FR** (separate locale keys): **list-style** views = meaning **applies to all lists**; **Kanban** = meaning **applies to all views**. **English** and **French** **wording and capitalization** must **match** existing **Tasks** UI patterns for **muted helper / hint** copy (audit `Index.vue`, locale files, similar tooltips). **Muted** secondary styling; readable at **compact** density (may wrap). **`aria-describedby`** (or equivalent) must reference the **visible** hint for that surface so screen readers get the **correct** wording. **Mobile** (**Question 13**): repeat the **same** **contextual** helper **inside** the **bottom sheet** or **popover** (header or footer) as for that surface—not only beside the toolbar trigger.
- [ ] **Mobile** / **narrow** layouts preserve the same information at responsive breakpoints. When **priority** + **due** do not fit **one** horizontal line (**Question 23**): **wrap** to a **second** line; keep **priority** **before** **due** on the **first** line when both partially fit; **no** silent dropping of either field—full values remain visible or wrapped, not tooltip-only. **Kanban** cards: same **wrap** rule where applicable.
- [ ] **i18n**: any new labels or relative-date strings exist in **English and French**; **capitalization** of sort hints follows **Question 24** (match existing Tasks helper copy style).
- [ ] **Accessibility**: priority and due information are not **color-only**; keyboard/screen-reader users get the same facts as sighted users.
- [ ] **First-run onboarding** (**in scope for US-027** — see Clarifying Questions): **one dismissible** screen after first successful Google connect, **EN/FR**, content per existing clarifying answers (**Today** vs **lists**, search default, **`⌘K`/`Ctrl+K`** when [US-028](US-028-command-palette-navigation-quick-add.md) exists, swipe/undo when [US-031](US-031-mobile-swipe-task-actions.md)/[US-029](US-029-undo-toast-destructive-actions.md) exist, **persist** dismissed). Include **one short** sentence that **task sort** is **shared globally** across **list views** and **Kanban** (**Question 20**); do not replace inline/sheet helper copy.

## Business Value

Reduces cognitive load and supports the discovery goal of **ease of use** and **using dates fully**—users trust one mental model of urgency wherever they work.

## Technical Requirements

- Prefer **shared presentation logic or components** for due/priority chips or columns; avoid duplicating format rules in Kanban vs list vs search. **Metadata order**: **priority** then **due** (LTR) per **Question 22**; **narrow** viewports: allow **wrap** to a second line (**Question 23**).
- **Sort comparator**: one shared implementation for global sort; **title**: **empty/whitespace-only** before non-empty (**Question 18**); **locale-aware**, **case-insensitive** (**Question 17**); **final** tie-breaker per **Question 16** (default: task `id`). **Document** in code / README.
- Respect existing **appearance** controls ([US-010](US-010-theme-dark-density-responsive.md)): dark theme, compact/comfortable density.
- **Sort sheet motion**: per **Question 21**, **do not** add a **`prefers-reduced-motion`** branch unless product revises; note in code comment.
- No change to **Google encoding** of priority ([US-015](US-015-priority-encoded-in-google.md)) unless a gap is found; scope is **display consistency**.

## Reference Documents

- [Product discovery responses (JSON)](../../../docs/google-tasks-discovery/responses-submitted-2026-03-21.json)
- [Product backlog](../product-backlog.md)
- [User story template](../../templates/user-story-template.md)

## Technical References

- `apps/google-tasks/resources/js/Pages/Tasks/Index.vue` — primary list surfaces
- `apps/google-tasks/resources/js/Components/TasksKanbanBoard.vue` — board cards
- Semantic / full-text search result components (as implemented under [US-013](US-013-full-text-search.md) / [US-018](US-018-semantic-search-task-index.md))
- Shared task row / cell components (extract or extend as needed)

## Dependencies

- [US-015](US-015-priority-encoded-in-google.md) — priority encoding and parsing must remain the source of truth for priority display.
- [US-012](US-012-filters-kanban.md) — Kanban surface exists.
- [US-013](US-013-full-text-search.md) / [US-018](US-018-semantic-search-task-index.md) — search surfaces exist.
- **Enabling**: [US-023](US-023-all-tasks-all-lists.md) aggregate view should follow the same rules once shipped (include in verification).

## Clarifying Questions

*Per [backlog-management-process.md](../../processes/backlog-management-process.md), ask and record answers before implementation.*

- **Q**: Should “no due date” always show a placeholder in comfortable density, or only in compact mode?
- **A**: **Option 1** — In **comfortable** density, **always** show an explicit placeholder when there is no due (never leave the due slot empty). **Compact** density: see following question.
- **Date**: 2026-03-21

- **Q**: In **compact** density, how should “no due” appear?
- **A**: **Options 2 and 3 together** — **short “—”** (minimal token) **plus** a **small due/calendar icon**; full meaning for assistive tech and hover via **`aria-label`** + **tooltip** (i18n), not color-only.
- **Date**: 2026-03-21

- **Q**: What is the app’s **main job** for you day to day?
- **A**: **Options 2 and 3** — **Plan today** (Today / focus) **and** **review everything** (scan all lists / full visibility). Implies strong **Today** and **all-tasks** ([US-023](US-023-all-tasks-all-lists.md)) experiences; capture stays important but is not the sole north star.
- **Date**: 2026-03-21

- **Q**: When someone creates a **new task**, where should it go **by default**?
- **A**: **Option 4** — user chooses a **default target list** in **Profile/settings**. **Plus**: when the user is **in** a **single concrete task list** (not an aggregate), **new tasks created in that context** go **into that list** by default. **Aggregate / special views** (e.g. **Today**, **Inbox**, **All tasks** per [US-023](US-023-all-tasks-all-lists.md)) use the **configured default list** unless the UI explicitly offers a different target (e.g. palette picker). Implement together with [US-028](US-028-command-palette-navigation-quick-add.md) / existing add flows; document edge cases in app help.
- **Date**: 2026-03-21

- **Q**: For **search** (full-text and/or semantic), what should the **default** be when you open it?
- **A**: **Option 1** — **Search everywhere** (all lists) **by default**. Optional **narrow to current list/view** remains available as a secondary control; align [US-013](US-013-full-text-search.md) / [US-018](US-018-semantic-search-task-index.md) UX copy and behavior.
- **Date**: 2026-03-21

- **Q**: After a user **first connects Google** and lands on tasks, what onboarding do you want?
- **A**: **Option 2** — **One short screen** after first successful connect: e.g. **Today** vs **lists**, **search defaults to all lists**, **`⌘K` / `Ctrl+K`** command palette when [US-028](US-028-command-palette-navigation-quick-add.md) exists, **mobile swipe** / **undo** when [US-031](US-031-mobile-swipe-task-actions.md) / [US-029](US-029-undo-toast-destructive-actions.md) exist. **Plus** **one line** EN/FR that **task sort** is **shared** across **lists** and **Kanban** (**Question 20**). **Dismissible**; do not show again once dismissed (persist per user). **i18n** EN/FR. **Scope**: implement **inside this story (US-027)** — **not** a separate backlog item; **not** deferred.
- **Date**: 2026-03-21

- **Q**: **First-run onboarding** — implement **inside US-027**, **separate small story**, or **defer**?
- **A**: **Inside US-027** — onboarding screen is **part of US-027** delivery.
- **Date**: 2026-03-22

- **Q**: When **Google Tasks is slow**, **rate-limited (429)**, or **temporarily failing**, what should the app **prioritize**?
- **A**: **Option 3** — **Mixed policy**: **Reads** may **degrade** (non-blocking **banner**, **stale** data allowed, last good fetch visible, refresh/retry). **Writes** stay **optimistic** with **clear status/retry** and queue or backoff per [US-008](US-008-google-tasks-sync-engine.md) / [US-019](US-019-api-error-retry-ux.md); user can keep working without a full lock unless safety requires it (document edge cases).
- **Date**: 2026-03-21

- **Q**: In the **Today** view, what should the **default sort** be?
- **A**: **Options 1 and 2 combined** — **Primary**: **due-urgency cohorts** (**overdue** → **due today** → **no due** among tasks eligible for Today). **Within each cohort**: sort by **priority** (higher first), **then** due **time** where applicable, **then** stable tie-breaker (e.g. title). *Rationale: time-sensitive plan for “today” while still surfacing priority inside each urgency band.*
- **Date**: 2026-03-21

- **Q**: **User-configurable sort** for Today, or **default only**?
- **A**: **Option 2** — **Sort control** on **Today** (at least **Due first** and **Priority first** modes; definitions match prior clarifying answer and second mode above). **Persist** choice per user (profile or localStorage per Technical Requirements).
- **Date**: 2026-03-21

- **Q**: Where should the **Today** sort control sit (**desktop** vs **mobile**)?
- **A**: **Option 3** — **Desktop**: **Today** toolbar row (dropdown or segmented). **Mobile / narrow**: **sort icon** opening **bottom sheet** or **popover** (not buried only in overflow unless space forces it). User asked to **follow industry best practice** on mobile — implement per common **Material / iOS list** patterns (clear icon, labeled options, dismiss gesture, focus return).
- **Date**: 2026-03-21

- **Q**: Same sort for **Inbox / All tasks** as **Today**, or **Today only**? **Global** vs **per-view** prefs?
- **A**: **Option 4** — **One global preference**: **Due first** / **Priority first** applies **everywhere** this story applies client-side list ordering (**Today**, **Inbox**, **per-list**, **All tasks** per [US-023](US-023-all-tasks-all-lists.md) **once US-027 is active** — see **Question 25** **phased** handoff from US-023 Phase 1), **and** **Kanban** **within-column** order per **Question 14**. **Single** persisted value; changing sort in **any** view updates **all**. **Caveat** (help copy): order may differ from Google’s raw API sequence; document. **Search** ranking: **unchanged** unless already tied to list sort (do not regress).
- **Date**: 2026-03-21

- **Q**: Should the UI **state that sort is global**?
- **A**: **Option 1** — **Yes**: **inline** hint near the sort control on each view that exposes it, **not** tooltip-only; **aria-describedby** (or equivalent). **Copy variants** per **Question 19** (lists vs Kanban).
- **Date**: 2026-03-21

- **Q**: Should the **global sort helper** also appear **inside** the mobile sort **sheet/popover**?
- **A**: **Yes** — repeat the **same** **contextual** helper (see **Question 19**) in the sheet **header** or **footer**, in addition to any inline hint near the trigger on the Tasks chrome.
- **Date**: 2026-03-21

- **Q**: Should **global sort** apply **inside Kanban columns**?
- **A**: **Option 1** — **Yes**. **Both** **Due first** and **Priority first** **reorder** tasks **within each Kanban column** **client-side**, using the **same** comparison rules as list views. Document that column order may differ from Google’s native order.
- **Date**: 2026-03-21

- **Q**: For tasks **without** encoded **priority**, mirror the **no due** display pattern?
- **A**: **Option 1** — **Yes**: **comfortable** = muted **“No priority”** / **“Set priority”** (EN/FR); **compact** = **priority/flag icon** + **“—”** + **`aria-label`** / **tooltip** with full phrase (same pattern as no-due).
- **Date**: 2026-03-21

- **Q**: When due, priority, time, and **title** still tie, what **final** stable order?
- **A**: **Option 4** — **No product preference**; implementer picks a **stable** final key, **documents** it. **Recommendation**: **Google task `id`** (string comparison) after title for **deterministic** ordering across reloads.
- **Date**: 2026-03-21

- **Q**: For **title** sorting, **case-insensitive** in the active **locale**?
- **A**: **Option 1** — **Yes**: **locale-aware**, **case-insensitive** comparison (e.g. `localeCompare` with `sensitivity: 'base'` or equivalent) using the **active** app locale ([US-011](US-011-i18n-en-fr.md)).
- **Date**: 2026-03-21

- **Q**: **Empty** or **whitespace-only** task title — where in **title** sort order?
- **A**: **Option 2** — Sort **before** (above) **all** tasks with a **non-empty** title (within the same due/priority/time band). Among several empty-title tasks, use **final** tie-breaker (**Question 16**).
- **Date**: 2026-03-21

- **Q**: Global-sort hint **“Applies to all lists”** on **Kanban** is awkward — **one** string or **contextual**?
- **A**: **Option 2** — **Contextual copy**: **List-style** views → **“Applies to all lists”** (+ FR). **Kanban** → **“Applies to all views”** (+ FR). **Mobile** sheet uses the **same** variant as the surface that opened it. Use **distinct i18n keys** (e.g. `sortHint.globalLists` / `sortHint.globalViews`).
- **Date**: 2026-03-21

- **Q**: Should **first-run onboarding** **explicitly mention** shared **task sort** across lists and Kanban?
- **A**: **Yes** (**option 1**) — Add **one short** line **EN/FR** on the dismissible first-run screen (see onboarding answer above) stating that **sort order applies across lists and the board** (wording aligned with **Question 19** intent — global sort); keep it **brief**; full detail remains in **inline** hints.
- **Date**: 2026-03-21

- **Q**: **`prefers-reduced-motion`** for the mobile **sort** sheet/popover?
- **A**: **Option 2** — **Same as default**; **no** special reduced-motion behavior for this sheet (explicit product choice). **Revisit** if WCAG / audit feedback requires.
- **Date**: 2026-03-21

- **Q**: Fixed **LTR** order of **priority** vs **due** on the row?
- **A**: **Option 2** — **Priority** then **Due** (left → right). **RTL**: mirror when supported; else document LTR-only.
- **Date**: 2026-03-22

- **Q**: **Very narrow** mobile row — if priority + due do not fit one line?
- **A**: **Option 1** — **Wrap** to a **second** line; **priority** stays **before** **due** when both appear on the **first** line partially; do **not** hide either without an affordance.
- **Date**: 2026-03-22

- **Q**: Sort hint **sentence case** vs **Title Case** in English?
- **A**: **Option 3** — **Match existing Tasks UI** for **muted helper / hint** strings (EN + FR typography conventions). Do not introduce a new casing style for these two lines alone.
- **Date**: 2026-03-22

- **Q**: **[US-023](US-023-all-tasks-all-lists.md) All tasks** — **global sort now**, **exception**, or **phased** after US-023 **list → title**?
- **A**: **Phased (option 3)** — [US-023](US-023-all-tasks-all-lists.md) may ship **list → title** first; **US-027** **migrates** **All tasks** to **global** comparator + sort **UI** when this story lands (**supersedes** US-023 Phase 1 for that view).
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

**Ready for sprint planning**: Yes. **Sizing**: 13 points accepted as one story; deliver in **vertical slices** (presentation → sort/persist → onboarding) via sequential PRs if capacity requires.

## Notes

- Inspiration note in discovery (`v1_references`: TickTick) is **not** a requirement to copy UI; use it only as a benchmark for **scan speed** and clarity.
- If a view is deprecated or merged during implementation, update acceptance criteria to match the **current** navigation model.
- **Product intent (clarifying)**: Prioritize **Today / planning** and **cross-list review** in UX decisions (see Clarifying Questions).
- **New-task targets**: See Clarifying Questions — **settings default** + **“in the list I’m viewing”** for per-list context.
- **Search default**: **All lists** first (see Clarifying Questions).
- **First-run onboarding**: one dismissible screen + **global sort** one-liner EN/FR (**Question 20**); **shipped in US-027** (see Clarifying Questions).
- **API degradation**: reads may show stale + banner; writes optimistic with strong messaging (see Clarifying Questions).
- **Global list sort**: **Due first** (default) vs **Priority first**; **one** persisted preference for **Today**, **Inbox**, **per-list**, **All tasks** (**phased** with [US-023](US-023-all-tasks-all-lists.md) — **Question 25**), **Kanban** columns (see Clarifying Questions). Primary control on **Today**; other views expose same picker or linked affordance. Points **13** (was 8) for global application + controls.
- **Sort placement (Today)**: desktop toolbar; mobile **icon → sheet/popover** (see Clarifying Questions).
- **Global sort hint**: contextual EN/FR — **lists** vs **Kanban** copy (**Question 19**); casing matches existing Tasks hints (**Question 24**); `aria-describedby`; **also** inside mobile sheet/popover (see Clarifying Questions).
- **No priority** display: mirrors **no due** pattern per **Question 15**.
- **Sort tie-break**: final key implementer-chosen, documented; **id** recommended (**Question 16**).
- **Title sort**: locale-aware, case-insensitive (**Question 17**); **empty** title **first** in title step (**Question 18**).
- **Sort sheet motion**: no `prefers-reduced-motion` override (**Question 21**); intentional; may revisit after a11y review.
- **Row metadata order**: **priority** → **due** (LTR) (**Question 22**); **narrow**: **wrap** second line (**Question 23**).

## Acceptance Verification

**Complete before marking status as Done.** Verify each acceptance criterion is met, then mark with `[x]`.

- [x] All acceptance criteria above verified as met
- [x] Each criterion tested or inspected and confirmed

## History

- 2026-03-21 - Created (from product discovery JSON via backlog process)
- 2026-03-21 - Clarified comfortable density: always show “no due” placeholder
- 2026-03-21 - Clarified compact “no due”: icon + “—”, full label in aria/tooltip
- 2026-03-21 - Product intent: plan today + review everything (options 2 and 3)
- 2026-03-21 - New-task default: Profile setting + per-list context override; aggregates use setting
- 2026-03-21 - Search default: all lists / everywhere first (option 1)
- 2026-03-21 - Onboarding: one short dismissible first-run screen (option 2)
- 2026-03-21 - API errors/slowness: mixed — degrade reads, optimistic writes (option 3)
- 2026-03-21 - Today default sort: due cohorts then priority within cohort (options 1 + 2)
- 2026-03-21 - Today sort: user control (Due first / Priority first) + persist; points 5→8
- 2026-03-21 - Today sort placement: desktop toolbar; mobile icon + sheet/popover + industry patterns
- 2026-03-21 - Sort preference: global (option 4) across list views; points 8→13
- 2026-03-21 - Global sort: mandatory inline hint + aria-describedby (option 1)
- 2026-03-21 - Global sort hint repeated inside mobile sort sheet/popover (Q13 yes)
- 2026-03-21 - Kanban: global sort within each column (Q14 option 1)
- 2026-03-21 - No-priority placeholders: mirror no-due comfortable/compact (Q15 option 1)
- 2026-03-21 - Sort final tie-breaker: implementer choice + doc; recommend task id (Q16 option 4)
- 2026-03-21 - Title sort: locale-aware case-insensitive (Q17 option 1)
- 2026-03-21 - Empty-title tasks sort before titled tasks in title step (Q18 option 2)
- 2026-03-21 - Global sort hint: contextual lists vs Kanban copy (Q19 option 2)
- 2026-03-21 - First-run onboarding: mention shared sort lists + Kanban (Q20 yes)
- 2026-03-21 - Sort sheet: no reduced-motion branch (Q21 option 2)
- 2026-03-22 - Clarified: **first-run onboarding** implemented **inside US-027** (not separate story / not deferred)
- 2026-03-22 - Row order: priority before due LTR (Q22 option 2)
- 2026-03-22 - Narrow mobile: wrap priority+due (Q23 option 1)
- 2026-03-22 - Sort hint capitalization: match existing Tasks UI (Q24 option 3)
- 2026-03-22 - **All tasks** sort: **phased** with US-023 — US-027 **supersedes** list→title when US-027 ships (Q25 option 3)
- 2026-03-22 - Backlog refinement: DoR recorded; vertical-slice delivery note for 13 pt scope
- 2026-03-22 - Assigned to [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md) (all open backlog stories in sprint bucket)
- 2026-03-22 - **Slice 1**: global sort (`taskSort.js`, `gt-task-sort-mode` localStorage), list + Kanban + search row metadata order (priority → due → title); inline sort hints EN/FR — onboarding, “no priority” placeholders, compact density tokens, profile persistence **not** done yet
- 2026-03-22 - **Slice 2**: task meta placeholders (no due/no priority, comfortable + compact), mobile sort sheet, first-run onboarding modal EN/FR — all acceptance criteria met; marked Done
