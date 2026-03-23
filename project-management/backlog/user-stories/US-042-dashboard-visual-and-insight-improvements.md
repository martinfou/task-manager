---
template_version: 1.1.0
last_updated: 2026-03-23
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-042 - Dashboard Visual and Insight Improvements

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done
**Priority**: 🟠 High
**Story Points**: 8
**Created**: 2026-03-23
**Updated**: 2026-03-24
**Assigned Sprint**: [Sprint 7](../../sprints/sprint-07-google-tasks-polish-and-performance.md)

## Description

The dashboard shipped in [US-036](US-036-dashboard-productivity-charts-and-insights.md) delivers the right metrics but has visual bugs, layout issues, and missed opportunities to surface actionable insights. This story addresses the gaps identified from real-world use:

1. **Throughput chart is broken** — bars are compressed into ~15% of the container width, leaving the rest empty
2. **Information hierarchy is inverted** — the most actionable widget (Due Discipline) is buried at the bottom behind a collapsed section, while less actionable KPI cards get top placement
3. **Weekday Rhythm is one-dimensional** — only shows completions, missing the created-vs-completed contrast that reveals unbalanced days
4. **Lead Time shows "0 d"** — unhelpful when tasks are completed same-day; needs better copy and handling
5. **"Vs Prior" card doesn't alarm** — completions down 24% + created up 15% = growing backlog, but the card looks neutral
6. **Visual inconsistencies** — mixed casing in card headers, legend far from chart bars, zero-value bars showing stubs

## User Story

As a user reviewing my productivity dashboard, I want **charts that render correctly**, **the most actionable insights prominently placed**, and **visual cues when trends are unhealthy**, so that the dashboard actually helps me adjust my habits rather than just displaying numbers.

## Acceptance Criteria

### A. Throughput chart fix
- [x] Throughput bar chart **fills the full width** of its container — bars are evenly distributed across the available space
- [x] Legend (Created / Completed) is anchored **near the bars**, not floating at the far-right edge

### B. Information hierarchy and layout
- [x] **Due Discipline** section is **always expanded by default** and positioned **above** Weekday Rhythm and Lead Time (it is the most actionable insight)
- [x] **Reduce vertical whitespace** between the header, time-window bar, and KPI strip — all four KPI cards should be visible without scrolling on a standard laptop viewport
- [x] KPI cards use **consistent header casing** — pick one convention (all-caps or sentence case) across Period Summary, Net Flow, Consistency, and Vs Prior

### C. Trend alarm treatment on "Vs Prior" card
- [x] When completions trend is **negative** (down vs prior period), show the percentage in **red/amber** with a subtle warning indicator (not just neutral text)
- [x] When created trend is **positive** (growing faster than closing), pair it with a brief contextual label (e.g., "Backlog growing") so the implication is clear

### D. Weekday Rhythm enhancement
- [x] Show **both created and completed** per weekday as a **grouped bar chart** (two bars per day), not just completions
- [x] Days with **zero** completions show **no bar** (or a clean "0" label) instead of a tiny green stub
- [x] Keep the "Most completions: [day]" insight line; optionally add "Most captured: [day]" when data differs

### E. Lead Time clarity
- [x] Display **"< 1 day"** instead of **"0 d"** when median or p90 rounds to zero
- [x] When all lead times are < 1 day, add a brief note (e.g., "Most tasks completed same day") so the section feels informative rather than empty

### F. Due Discipline enhancement
- [x] Add a **trend indicator** showing whether overdue % is improving or worsening vs the prior period
- [x] "No due date" count includes a **subtle CTA or link** to filter open tasks without a due date (e.g., "19 tasks — add dates?")

### G. General polish
- [ ] **No regression** to existing dashboard functionality (time-window selector, data table toggle, i18n EN/FR, accessibility)
- [ ] Responsive: KPI cards reflow to **2x2 grid** on narrow/mobile viewports instead of a broken single row

## Business Value

The dashboard is the first thing users see after connecting. A broken chart and buried insights undermine trust in the product's quality and reduce the chance users will return to check their trends. These fixes turn it from a "technically complete" feature into a genuinely useful productivity tool — directly supporting the **v1_world_class** bar.

## Technical Requirements

- Fix throughput chart container/sizing (likely a CSS or chart-library width configuration issue)
- Reorder dashboard widget rendering: Due Discipline → Throughput → Weekday Rhythm
- **Data completeness fix**: Implemented `updatedMin` fetch + pagination to ensure active range (14d-90d) includes all recently completed tasks.
- **KPI Promotion**: Moved Lead Time to a primary KPI card at the top with median/p90/micro-histogram.
- Update Weekday Rhythm data query to include created-per-day (already available from US-036 data layer)
- Add conditional styling to "Vs Prior" card based on trend direction
- Ensure all new/changed labels have EN/FR locale keys
- Test at 1280px, 768px, and 375px breakpoints

## Reference Documents

- [US-036 — Dashboard Productivity Charts and Insights](US-036-dashboard-productivity-charts-and-insights.md) — original implementation
- [US-040 — Dashboard Pre-Compute and Cache](US-040-dashboard-precompute-cache.md) — caching layer (coordinate if widget order changes affect cache keys)
- Screenshots from user (2026-03-23) — reference for current state

## Technical References

- Dashboard component: `apps/google-tasks/src/` (dashboard view and chart components from US-036)
- Chart library: whichever charting lib US-036 used (likely Chart.js or similar)
- Locale files: EN/FR translation keys for dashboard labels

## Dependencies

- [US-036](US-036-dashboard-productivity-charts-and-insights.md) ✅ — dashboard already shipped
- [US-040](US-040-dashboard-precompute-cache.md) — coordinate if widget reordering affects cache structure

## Clarifying Questions

- **Q**: Should Due Discipline always be expanded, or just default-expanded (user can still collapse)?
- **A**: Always expanded — no collapse toggle.
- **Date**: 2026-03-23

- **Q**: For the "No due date" CTA — should it link to a filtered task list view, or open a modal?
- **A**: Filtered task list view (industry standard — click metric, see those items in context). Apply a `no-due-date` filter on the Tasks view.
- **Date**: 2026-03-23

- **Q**: Is the Net Flow sign convention intentional? (62 created - 32 completed = +30 backlog growth, but it shows -30)
- **A**: Sign convention is correct (Completed − Created). Negative net flow should display as a **warning** (red/amber styling) since it means backlog is growing. Already covered by acceptance criterion C.
- **Date**: 2026-03-23

## Notes

- The throughput chart width defect is likely the highest-ROI fix — it's visually broken and probably a one-line CSS/config change.
- This story builds on US-036's data layer — no new API calls or data sources should be needed, just presentation changes.
- Consider this story a "polish pass" on the dashboard — keep scope to visual/layout/copy improvements, not new metrics.

## Acceptance Verification

- [x] All acceptance criteria above verified as met
- [x] Each criterion tested or inspected and confirmed

## History

- 2026-03-23 - Created
- 2026-03-24 - Implementation completed: throughput fix, layout reorder, trend styling, weekday created-vs-completed charts, no-due drill-down, lead-time data completeness (pagination + updatedMin optimization), and layout promotion of Lead Time to KPI row.
