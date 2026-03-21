# Release notes

Human-readable summary of what changed in each release. Add a new **dated section** for each merge to `main` or end-of-sprint review.

**How to write an entry**: Follow [project-management/templates/release-note-section-template.md](project-management/templates/release-note-section-template.md) and [project-management/processes/release-notes-process.md](project-management/processes/release-notes-process.md).

**Automation** (optional): `./project-management/scripts/generate-release-notes-draft.sh --auto`

---

## 2026-03-21

### New Features

- **AI Agent Configuration Files** — Added `.cursorrules`, GitHub Copilot, Antigravity, and Claude Code instruction files embedding the project-management workflow and Git commit preset. [US-001](project-management/backlog/user-stories/US-001-ai-agent-configs.md)
- **Git Initialization and Commit Standards** — Repository initialized with Git; commit message format documented in agent configs per git-commit-guide. [US-002](project-management/backlog/user-stories/US-002-init-git-commit-rules.md)
- **Root-Level Docs Folder** — Added `docs/` with README describing purpose versus `project-management/`. [US-003](project-management/backlog/user-stories/US-003-root-docs-folder.md)
- **Root-Level Release Notes** — Added this file and linked processes to the existing section template. [US-004](project-management/backlog/user-stories/US-004-root-release-notes.md)
- **Laravel Inertia Vue Scaffold and Deployment** — Added `apps/google-tasks/` (Laravel 13, Breeze + Vue/Inertia, Vite), `/health` plus Laravel `/up`, Fly.io-focused deploy doc with DreamHost notes, Google OAuth env placeholders, and dark-themed error pages. [US-006](project-management/backlog/user-stories/US-006-laravel-inertia-scaffold-deploy.md)
- **Google OAuth Combined Flow and Token Storage** — Laravel Socialite with identity + Google Tasks scope; encrypted refresh token and access-token expiry on `users`; `/auth/google` routes, login “Continue with Google” when configured, and `docs/GOOGLE_OAUTH.md` for Cloud Console setup. [US-007](project-management/backlog/user-stories/US-007-google-oauth-combined-flow.md)
- **Google Tasks Sync Engine** — Server-side `GoogleTasksClient` (REST v1) with cached OAuth access tokens; authenticated JSON routes under `/tasks/data/*`; Inertia **Tasks** page with polling (configurable interval, exponential backoff on 429) and optimistic create/complete/delete with rollback on error; due dates and RRULE recurrence passed through per API. [US-008](project-management/backlog/user-stories/US-008-google-tasks-sync-engine.md)
- **Today, Inbox, and Multi-List Navigation** — Aggregated **Today** (incomplete tasks with due on/before today, app timezone) and **Inbox** (default list: “My Tasks” or first list) via `/tasks/data/views/today` and `/views/inbox`; Tasks shell with desktop sidebar, mobile bottom nav + list drawer, per-view empty states, and in-app help. [US-009](project-management/backlog/user-stories/US-009-views-today-inbox-lists.md)
- **Dark Theme, Density, and Responsive MVP** — Tailwind `dark` class strategy; default **dark** theme with light toggle; **Comfort** / **Compact** density via `data-density` + utility classes; preferences in `localStorage` (`gt-theme`, `gt-density`); FOUC prevention script in `app.blade.php`; dark styling across authenticated shell, guest layout, Tasks, inputs, and nav. [US-010](project-management/backlog/user-stories/US-010-theme-dark-density-responsive.md)
- **Internationalization (English and French)** — `vue-i18n` with `resources/js/locales/en.json` and `fr.json`; `SetLocale` middleware + `POST /locale` (cookie + `users.locale`); **EN**/**FR** switcher in nav and guest layouts; locale-aware due dates on Tasks; README section on contributing translations. [US-011](project-management/backlog/user-stories/US-011-i18n-en-fr.md)
- **Full-Text Search Across Tasks** — `GET /tasks/data/search` with `TaskSearcher` (all lists, paginated API reads, word-and matching on title + notes); Tasks page header search with debounced queries, results showing list name and snippet, open result jumps to list view and scrolls to task; README documents API cost and 75-result cap. [US-013](project-management/backlog/user-stories/US-013-full-text-search.md)
- **Keyboard Shortcuts (Desktop)** — `useTasksKeyboardShortcuts` with typing-context guard, `/` and Ctrl+K for search, `n` for new task, `g`+`t`/`i`/`l` for views, arrows + Space/Enter for highlighted row, `?`/Ctrl+/ for localized help modal; README covers accessibility and browser conflicts. [US-014](project-management/backlog/user-stories/US-014-keyboard-shortcuts.md)
- **Priority Encoded into Google Tasks** — Added `TaskPriorityCodec` with reversible `[P1]`–`[P4]` title encoding (default decode fallback `p3`), wired decoding into tasks/today/inbox/search payloads, added create + per-task priority selectors in the Tasks UI, and covered the contract with feature tests. [US-015](project-management/backlog/user-stories/US-015-priority-encoded-in-google.md)
- **Links in Task Notes** — `http`/`https` URLs in notes and search snippets render as links (new tab, `noopener`); optional notes field on new task with paste-to-append URL; `app/Services/Google/README.md` documents Tasks API limits (no binary attachments). [US-016](project-management/backlog/user-stories/US-016-links-attachments-tasks.md)
- **Bulk Actions on Tasks** — Multi-select via row checkboxes, Ctrl/Cmd+click, and Shift+click; bulk **Complete**, **Move to list** (`POST /tasks/data/{list}/tasks/{id}/move`), and **Delete** with confirmation; partial API failures summarized in a modal; mobile documented as checkbox-only. [US-017](project-management/backlog/user-stories/US-017-bulk-actions.md)
- **Filters and Kanban** — Client-side filters (status, due, priority, Google list on Today); **List** / **Board** toggle with P1–P4 columns; drag cards between columns to change priority (`PATCH` via existing priority encoding); scrollable columns; preferences in `localStorage`. [US-012](project-management/backlog/user-stories/US-012-filters-kanban.md)
- **Google Cloud OAuth checklist (RI-001)** — Added [docs/google-tasks-discovery/google-cloud-oauth-checklist.md](docs/google-tasks-discovery/google-cloud-oauth-checklist.md) (Tasks API, consent, scopes, redirect URIs, test users) and linked it from root `docs/README.md`, discovery README, and `apps/google-tasks/docs/GOOGLE_OAUTH.md`.
- **CI for `apps/google-tasks` (RI-002)** — GitHub Actions workflow [`.github/workflows/google-tasks-ci.yml`](.github/workflows/google-tasks-ci.yml) runs `composer install`, `npm ci`, `npm run build`, and `php artisan test` when `apps/google-tasks/**` changes.
- **Google API error handling and retry UX (US-019)** — JSON errors include stable `code` (`auth_expired`, `rate_limit`, `server_error`, `network`, etc.) plus human `message`; `retry_after` on 429; `Log::warning` without token/response bodies; Tasks UI maps codes to EN/FR copy, bounded automatic retry for idempotent reads, and a **Retry** action on load failures. [US-019](project-management/backlog/user-stories/US-019-api-error-retry-ux.md)

### Defect Fixes

- **Example defect for demo** — Confirmed DEF-001 as the reference defect for workflow and template demonstration. [DEF-001](project-management/backlog/defects/DEF-001-ui-glitch-fix.md)

### Technical Debt

- (none this release)

### Breaking Changes

- (none this release)

### Migration Notes

- (none this release)
