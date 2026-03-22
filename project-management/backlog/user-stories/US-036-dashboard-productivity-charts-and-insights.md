---
template_version: 1.1.0
last_updated: 2026-03-22
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-036 - Dashboard Productivity Charts and Insights

[← Back to Product Backlog](../product-backlog.md)

**Status**: ⭕ To Do  
**Priority**: 🟡 Medium  
**Story Points**: 13 *(re-estimate if extended metrics + offline caching expand engineering effort)*  
**Created**: 2026-03-22  
**Updated**: 2026-03-22  
**Assigned Sprint**: [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md)

## Description

Elevate the **Dashboard** from a connection gate into a **personal productivity cockpit**: charts and concise insights derived from **when tasks were captured and when they were finished**, grounded in what best-in-class task tools emphasize—**throughput, consistency, and realistic self-awareness**—without turning the product into enterprise project analytics.

**Product intent (knowledge worker, “best in class” bar)**  
Leading personal task systems succeed because they answer: *Am I closing the loop?* *Is my backlog drifting?* *When am I most effective?* Borrow patterns from tools like **Todoist** (trends, goals, streaks, “you completed X this week”), **Things** (clear today vs. logbook mindset), and **OmniFocus** (forecast discipline)—adapted to **Google Tasks as source of truth**, with honest handling of **history the API may not fully expose**.

## User Story

As a **signed-in user** who relies on tasks for deep work,  
I want **charts and short, actionable insights on the Dashboard** about **tasks I created and completed over time**,  
so that I can **see my throughput, spot patterns, and adjust how I capture and close work**—not just manage a list.

## Acceptance Criteria

### A. Time-based charts (MVP scope)

- [ ] Dashboard shows at least one **chart** (accessible, responsive) for a user-selectable range (e.g. **7 / 14 / 30 / 90 days**), defaulting to **30 days**.
- [ ] For each **calendar day** in range, surface **count of tasks created** and **count of tasks completed** (definitions documented; see Technical Requirements).
- [ ] Chart supports **quick comparison** of the two series (e.g. grouped or dual-series **bar** or **line**—design chooses clarity over novelty).
- [ ] **Empty and partial data** states are explicit (“Not enough history yet”, “Connect Google Tasks to start”) without errors.
- [ ] When **Google Tasks is disconnected**, show **last successfully computed** dashboard stats with a clear **disclaimer** (stale / may not reflect other clients until reconnect)—not a blank or error state.
- [ ] **i18n**: EN/FR for dashboard labels, range control, empty states, and insight copy.
- [ ] **Accessibility**: chart has text alternative or data table/summary for screen readers; color is not the only differentiator.

### B. “Great information” — insight cards (paired with charts)

Implement a **small set of high-signal insight cards** (not a wall of numbers). At minimum:

- [ ] **Period summary**: total created, total completed, and **net flow** (completed − created) for the selected range, with plain-language framing (e.g. whether the user is adding faster than closing).
- [ ] **Consistency** (low-guilt default): primary metric is **active days** (days in range with ≥1 completion), framed neutrally—**no streak-as-pressure**; optional secondary streak-style callout only if copy stays professional and non-punitive.
- [ ] **Comparison to prior period** of equal length (e.g. “vs previous 30 days”: % change or delta for completions)—only when prior data exists.

### D. Extended metrics (in scope for this story)

Product decision: ship **weekday, lead time, and due discipline** with the same dashboard release (not deferred as optional stretch).

- [ ] **Weekday pattern**: simple breakdown or callout (e.g. which weekdays see most completions)—privacy-safe, single-user.
- [ ] **Lead time proxy**: median (or p90) **days from task creation to completion** among tasks completed in range—document exclusions where `created` or completion time is unreliable.
- [ ] **Due discipline** (if data supports): share of completions that were **due today** vs **overdue** vs **no due date**—planning realism, neutral framing.

### C. Trust, privacy, and performance

- [ ] Metrics are **scoped to the authenticated user** only; no cross-user reporting.
- [ ] **Performance**: dashboard load remains acceptable with bounded queries (indexes, aggregation strategy, or pre-rolled daily summaries—document choice).
- [ ] **Help or tooltip** links to a short in-app or docs note: **what is counted**, **timezone**, and **limitations** (e.g. tasks completed only in other clients may appear only after sync).

## Business Value

Differentiates the app for **power users** who already live in Google Tasks: the Dashboard becomes a **feedback loop** for habits (capture vs. finish), supporting retention and perceived depth without requiring a separate analytics tool.

## Technical Requirements

1. **Definitions (must be documented in code + user-facing copy)**  
   - **Created (product rule)**: **Prefer Google Tasks `Task.created`** (when the API provides it) for calendar-day bucketing—tasks created in Google’s native apps still count on their **true creation day**, not the day our sync first saw them. **Fallback** when `created` is missing or unusable: **first time this app observed the task** (e.g. first sync); disclose in “how metrics work” copy.  
   - **Completed (best-effort)**: **Server-side event timestamp** when the user completes a task **in this app**; **reconcile on sync** with API signals (e.g. `updated`, status change) where reliable. **No guarantee** of exact “completed at” for actions only in other clients until sync—UX and help copy must state this plainly.

2. **Data strategy (pick and document)**  
   - **Preferred**: append-only **event log** or **daily aggregates** on the server for actions observed through this app, plus **reconciliation** on sync where Google fields allow—so charts stay **fast** and **explainable**.  
   - **Constraint**: history **before** adoption of this app or changes made solely in other clients may be **incomplete**; UX must not overclaim precision.

3. **Timezone**  
   - Bucketing “per day” uses a **per-user profile timezone** (explicit setting). **Default for new users**: **`America/Toronto`** (IANA Eastern — correct for **Montréal** and most of Eastern Canada; equivalent practical behavior to `America/Montreal`). Align implementation with existing profile/locale patterns in the app.

4. **Stack**  
   - Fits current **Laravel + Inertia + Vue** dashboard; chart library choice should be **bundle-conscious** (or server-rendered sparkline SVG for MVP if preferred).

5. **Testing**  
   - Feature tests for aggregation endpoints or props; key edge cases (no data, single day, range boundaries).

## Reference Documents

- [Product Backlog](../product-backlog.md)
- Google Tasks API task resource (created/updated/status) — verify fields available for completion dating

## Technical References

- `apps/google-tasks/resources/js/Pages/Dashboard.vue` (or successor)
- `apps/google-tasks/app/Http/Controllers/` — dashboard data assembly
- Potential new tables: `task_activity_events` or `user_task_daily_stats` (names illustrative)

## Dependencies

- **Google Tasks connection** for **fresh** recomputation; when disconnected, **required UX**: show **last computed** stats plus **disclaimer** (see §A).
- May benefit from alignment with [US-008](US-008-google-tasks-sync-engine.md) sync semantics so completion events are consistent.

## Clarifying Questions

*Answers recorded from product owner (2026-03-22).*

- **Q**: Should “created” include tasks created in Google’s native apps but first seen by our sync later?  
- **A**: **Prefer `Task.created` from Google when present** so native-app tasks land on their **real** creation day; **fallback** to first-seen time only when the API does not give a usable `created`. (Reduces false “spikes” on first-sync day.)

- **Q**: Single global timezone vs. per-user profile timezone for bucketing?  
- **A**: **Per-user profile timezone**, default **`America/Toronto`** (Eastern; appropriate for Montréal).

- **Q**: How precise must “completed at” be when users also use other clients?  
- **A**: **Best effort** is acceptable: event log + sync reconciliation; honest limitations in UI/help.

- **Q**: Behavior when Google Tasks is disconnected?  
- **A**: Show **last successfully computed** stats with a clear **stale-data disclaimer**.

- **Q**: Ship weekday / lead time / due discipline with the first release?  
- **A**: **Yes — all in** for this story.

- **Q**: Tone for consistency metrics?  
- **A**: **Low guilt**: **active days** as the primary consistency signal; avoid pressure-heavy streak framing (optional secondary only with neutral copy).

## Notes

**Best-in-class reporting themes to keep in mind (product north star; first release includes chart + insight cards + §D extended metrics):**

| Theme | Why it matters for knowledge workers |
|--------|--------------------------------------|
| **Throughput** | Created vs. completed shows whether the system is stable or accumulating guilt-tasks. |
| **Consistency** | Steady small completions beat heroic spikes; reduces burnout narratives. |
| **Trends vs. snapshots** | Period-over-period answers “is this week actually worse or just perception?” |
| **Planning realism** | Due-date and overdue signals expose overcommitment without moralizing. |
| **Lead time** | Time-to-complete highlights where work hides (long-dwell tasks). |
| **Focus proxy** | Completions tied to “today” / scheduled work supports intentional daily design. |

**Anti-patterns to avoid:** vanity dashboards with charts that duplicate the task list; metrics that punish sick days or vacation; dark patterns copied from aggressive gamification UIs.

### Dashboard layout (ASCII wireframes)

Target information architecture: **insight cards first** (answer “am I closing the loop?”), **one primary throughput chart**, **secondary reports** on demand (weekday / lead time / due discipline), **trust strip** always visible.

**Desktop — primary layout**

```text
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│  Dashboard                                                      [? How metrics work]      │
│  Personal productivity — based on tasks you use in this app + synced Google Tasks data   │
└──────────────────────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────────────────────┐
│  TIME WINDOW   ( ) 7d   ( ) 14d   (●) 30d   ( ) 90d          TZ: America/Toronto ▾        │
│  Compare to previous period of same length:  ON ●                                            │
└──────────────────────────────────────────────────────────────────────────────────────────┘

┌─ INSIGHT ROW (high-signal, not a wall of KPIs) ──────────────────────────────────────────┐
│                                                                                            │
│  ┌─────────────────────┐ ┌─────────────────────┐ ┌─────────────────────┐ ┌──────────────┐│
│  │ PERIOD SUMMARY      │ │ NET FLOW            │ │ CONSISTENCY         │ │ VS PRIOR     ││
│  │                     │ │                     │ │                     │ │ (if data)    ││
│  │ Created    42       │ │  +8                 │ │ Active days  18/30  │ │ Completions  ││
│  │ Completed  50       │ │  (closing faster    │ │ (days w/ ≥1 done) │ │  +12%        ││
│  │                     │ │   than capturing)   │ │                     │ │ vs prev 30d  ││
│  │ [plain language]    │ │ [framed neutrally]  │ │ Active days (primary) │ │ Created Δ    ││
│  └─────────────────────┘ └─────────────────────┘ └─────────────────────┘ └──────────────┘│
│                                                                                            │
└──────────────────────────────────────────────────────────────────────────────────────────┘

┌─ MAIN CHART — Throughput (created vs completed per calendar day) ────────────────────────┐
│                                                                                            │
│   Legend:  ■ Created   ■ Completed   (pattern + label; not color-only)                    │
│                                                                                            │
│    ^                                                                                       │
│  n │        ██      ██                                                                    │
│    │   ██   ██  ██  ██  ██                                                                │
│    │   ██   ██  ██  ██  ██  ...                                                           │
│    └──────────────────────────────────────────────────────────────► day                   │
│                                                                                            │
│  [Accessible: “Show data table” | Screen reader summary: totals + min/max day callouts]   │
│                                                                                            │
└──────────────────────────────────────────────────────────────────────────────────────────┘

┌─ SECONDARY REPORTS (collapsible sections — “deeper without clutter”) ────────────────────┐
│                                                                                            │
│  ▼ Weekday rhythm                                                                         │
│  ┌────────────────────────────────────────────────────────────────────────────────────┐ │
│  │  Mon ████████   Tue ██████   Wed ████   Thu ██████   Fri ███   Sat ▌   Sun ▌         │ │
│  │  Callout: “Most completions: Tue–Thu” (privacy-safe, single-user)                  │ │
│  └────────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                            │
│  ▼ Lead time — median / p90 days from create → complete (completed in range)            │
│  ┌────────────────────────────────────────────────────────────────────────────────────┐ │
│  │  Median: 3d     p90: 14d     Long tail: N tasks > 30d (link: “what we exclude”)      │ │
│  └────────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                            │
│  ▼ Due discipline (if data supports)                                                      │
│  ┌────────────────────────────────────────────────────────────────────────────────────┐ │
│  │  Completed this period:  Due today ████   Overdue ███   No due ██████████            │ │
│  │  One-line insight: planning realism, not guilt                                         │ │
│  └────────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                            │
│  ▼ Focus proxy (north-star tie-in — only if definable; else follow-up US)                  │
│  ┌────────────────────────────────────────────────────────────────────────────────────┐ │
│  │  “Closed from Today / scheduled views” vs “ad hoc closes” (only if definable)        │ │
│  └────────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                            │
└──────────────────────────────────────────────────────────────────────────────────────────┘

┌─ TRUST STRIP (always visible, small) ────────────────────────────────────────────────────┐
│  Counts reflect tasks seen through this app. Other clients may sync later. [Learn more]   │
└──────────────────────────────────────────────────────────────────────────────────────────┘
```

**Empty / degraded states**

```text
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│  [ Connect Google Tasks to start ]   OR   [ Not enough history yet — check back in a few │
│                                             days; partial history before using this app ] │
│                                                                                            │
│  (Chart area: muted illustration + single primary CTA)                                   │
└──────────────────────────────────────────────────────────────────────────────────────────┘
```

**Mobile / narrow — stack order**

```text
┌────────────────────────────┐
│ Range:  [ 30d ▾ ]          │
│ Compare prior: [ toggle ]  │
├────────────────────────────┤
│ PERIOD SUMMARY (card)      │
├────────────────────────────┤
│ NET FLOW (card)            │
├────────────────────────────┤
│ CONSISTENCY (card)         │
├────────────────────────────┤
│ VS PRIOR (card or hidden)  │
├────────────────────────────┤
│ CHART (scrolls horizontal  │
│  or simplified spark bars) │
├────────────────────────────┤
│ Accordion: Weekday         │
│ Accordion: Lead time       │
│ Accordion: Due discipline  │
├────────────────────────────┤
│ Trust strip                │
└────────────────────────────┘
```

| Zone | Rationale |
|------|-----------|
| **Insight row first** | Answers throughput before the user parses the chart. |
| **One main chart** | Created vs completed per day — clarity over novelty. |
| **Secondary accordions** | Weekday / lead time / due (+ optional focus proxy) — depth without dashboard sprawl. |
| **Compare toggle** | Period-over-period reduces “feels worse” without evidence. |
| **Trust strip + help** | Sync and cross-client limits stay visible so metrics stay credible. |

## Acceptance Verification

- [ ] All acceptance criteria above verified as met
- [ ] Each criterion tested or inspected and confirmed

## History

- 2026-03-22 - Created
- 2026-03-22 - Added dashboard ASCII wireframes under Notes (layout for MVP + stretch + trust/empty states)
- 2026-03-22 - Recorded clarifying decisions: `Task.created` preference + fallback, profile TZ default America/Toronto, best-effort completion times, offline last-stats + disclaimer, extended metrics in scope, low-guilt consistency (active days primary)
- 2026-03-22 - Assigned to [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md) (all open backlog stories in sprint bucket)

---

## Status Values

- ⭕ **To Do**: Item not yet started
- ⏳ **In Progress**: Item currently being worked on
- ✅ **Done**: Item finished and verified
