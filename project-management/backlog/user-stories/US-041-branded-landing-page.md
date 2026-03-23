---
template_version: 1.1.0
last_updated: 2026-03-23
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-041 — Branded Landing Page

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done
**Priority**: 🟠 High
**Story Points**: 5
**Created**: 2026-03-23
**Updated**: 2026-03-23
**Assigned Sprint**: [Sprint 7](../../sprints/sprint-07-google-tasks-polish-and-performance.md)

## Description

The homepage (`/`) currently renders the **default Laravel Breeze Welcome page** — a generic template with Laravel branding, documentation links, and no connection to the Google Tasks app. Visitors landing on the site have no idea what the product does, whether they should sign up, or how it relates to Google Tasks.

This story replaces the Welcome page with a **branded landing page** that communicates the product's value proposition, shows key features, and funnels visitors toward registration or login.

## User Story

As a visitor arriving at the homepage, I want to **immediately understand what this app does and why I should use it**, so that I feel confident signing up or logging in.

## Acceptance Criteria

- [x] **Hero section**: Clear headline and sub-headline communicating the value proposition (e.g. "A better way to manage your Google Tasks" or similar). A primary CTA button leading to registration (or dashboard if already logged in)
- [x] **Feature highlights**: 3–5 concise feature cards or sections showcasing key capabilities (e.g. priority management, Kanban view, smart search, dashboard insights, mobile swipe actions)
- [x] **Visual identity**: Uses the existing design system (Outfit font, warm amber accent, `gt-*` theme tokens, dark mode support). Feels like the same product as the tasks and dashboard pages
- [x] **Auth-aware**: If the user is already logged in, the primary CTA changes to "Go to Tasks" / "Go to Dashboard" instead of "Sign up"
- [x] **Responsive**: Looks good on mobile (375px+), tablet, and desktop
- [x] **Dark mode**: Respects the user's system/browser dark mode preference, consistent with the rest of the app
- [x] **i18n**: All user-visible strings in **en** + **fr** locale files
- [x] **Performance**: No heavy images or external assets that slow initial load. Prefer inline SVG illustrations or CSS-based visuals
- [x] **Footer**: Minimal footer with link to login/register, and optionally a link to the GitHub repo or project info
- [x] **No regression**: Login and register pages continue to work. Authenticated redirect to `/tasks` or `/dashboard` unchanged

## Business Value

The landing page is the first impression for every visitor. The default Laravel page signals "unfinished project" and erodes trust before the user even tries the product. A branded landing page communicates professionalism and purpose.

## Technical Requirements

- Replace content of `resources/js/Pages/Welcome.vue` (or rewrite it)
- The route (`/`) already renders `Welcome` via Inertia with `canLogin` and `canRegister` props — keep that contract
- Use existing Tailwind config and `gt-*` theme tokens for consistency
- Keep the page lightweight — no additional npm dependencies for illustrations or animations

## Reference Documents

- [US-010 — Dark Theme, Density, Responsive MVP](US-010-theme-dark-density-responsive.md) — design system foundation
- [US-011 — Internationalization](US-011-i18n-en-fr.md) — i18n pattern

## Technical References

- `apps/google-tasks/resources/js/Pages/Welcome.vue` — current default Laravel page
- `apps/google-tasks/routes/web.php` — `/` route with `canLogin` / `canRegister` props
- `apps/google-tasks/resources/css/app.css` — theme tokens
- `apps/google-tasks/tailwind.config.js` — design system config

## Dependencies

- None

## Notes

- Content tone should match the product's identity: confident, minimal, task-oriented. Avoid generic SaaS marketing language.
- Consider a simple screenshot or stylized mockup of the tasks view as a hero image — can be a static asset or a CSS-rendered illustration.
- The page should be entirely self-contained (no external API calls, no auth-required data).

## Acceptance Verification

- [x] All acceptance criteria above verified as met
- [x] Each criterion tested or inspected and confirmed

## History

- 2026-03-23 - Created
- 2026-03-23 - Implemented: Welcome.vue rewritten with hero, 5 feature cards, auth-aware CTA, dark mode, responsive, i18n EN/FR, inline SVG icons, gt-* tokens, footer
- 2026-03-23 - Marked ✅ Done — build green, Pint clean, 64 tests pass
