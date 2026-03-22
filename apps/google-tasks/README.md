# Google Tasks web client

Laravel + Inertia + Vue 3 application (Breeze). Part of the monorepo `task-manager`; product backlog: `project-management/` (US-006+).

## Requirements

| Tool | Version |
|------|---------|
| PHP | ^8.4 (`composer.json`) |
| Node | >=20 (`package.json` engines) |

## Local development

```bash
cd apps/google-tasks
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
# other terminal:
php artisan serve
```

Visit `http://127.0.0.1:8000`. Health: `GET /up` (Laravel), `GET /health` (JSON).

## Layout

| Path | Purpose |
|------|---------|
| `app/Services/Google/` | OAuth token cache, `GoogleTasksClient` (Tasks API v1) |
| `docs/GOOGLE_OAUTH.md` | Google Cloud OAuth client, scopes, redirect URIs |
| `resources/js/Pages/Tasks/` | Tasks list UI (polling + optimistic updates) |
| `resources/js/` | Other Vue pages & components (Inertia) |
| `routes/web.php` | Web, health, OAuth, `/tasks` + `/tasks/data/*` |

## Theme & density (US-010)

- **Theme**: **Dark** is the default; toggle (sun/moon) in the top nav persists `gt-theme` (`dark` | `light`) in **localStorage**. A short inline script in `resources/views/app.blade.php` runs before paint to reduce flash.
- **Density**: **Comfort** vs **Compact** toggles persist `gt-density` in localStorage and set `data-density` on `<html>`, adjusting stack spacing and task row padding via `resources/css/app.css` (`.density-stack`, `.density-task-row`, `.density-card-padding`).
- **Responsive**: main content uses `min-w-0` / `overflow-x-hidden` where needed; Tasks mobile drawer and bottom nav unchanged from US-009.

## Internationalization (US-011)

- **Locales**: **English** (`en`) and **French** (`fr`). UI strings live in `resources/js/locales/en.json` and `fr.json` (nested keys: `nav.*`, `tasks.*`, `auth.*`, etc.).
- **Switching**: **EN** / **FR** control in the nav (authenticated) and on guest auth layouts. `POST /locale` sets a **`locale` cookie** (1 year) and, when logged in, persists **`users.locale`**.
- **Server**: `SetLocale` middleware resolves locale (signed-in user → cookie → `Accept-Language` → `en`). `HandleInertiaRequests` shares **`locale`** for the initial Vue i18n instance; Inertia navigations update `vue-i18n` via the router `navigate` event.
- **Dates**: Task due times on the Tasks page use `Intl.DateTimeFormat` with the active locale (`useLocaleDate` composable).
- **Contributing translations**: Add or edit keys in both JSON files; keep keys identical across locales. Run `npm run build` before PHP tests that render Inertia.

## Keyboard shortcuts (US-014)

- **Where**: Tasks page (`/tasks`) when Google is connected. Shortcuts are **disabled** while focus is in a text field (`input` / `textarea` / `select` / `contenteditable`) so typing is never hijacked—industry-standard “typing context” guard.
- **Implementation**: [`resources/js/composables/useTasksKeyboardShortcuts.js`](resources/js/composables/useTasksKeyboardShortcuts.js) (global `keydown` listener, **capture** phase for Space/Enter on highlighted rows). Help overlay: [`TasksKeyboardShortcutsHelp.vue`](resources/js/Components/TasksKeyboardShortcutsHelp.vue) + Breeze [`Modal.vue`](resources/js/Components/Modal.vue).
- **Bindings (summary)**: `/` — focus search · **Ctrl+K** / **⌘K** — command palette (navigate, search, quick add) · **n** — focus new task · **g** then **t** / **i** / **l** — Today / Inbox / list view · **↑**/**↓** — move row highlight · **Space** / **Enter** — toggle complete on highlighted row · **?** or **Ctrl+/** — help. Full table is localized (`shortcuts.*` in `locales/en.json` / `fr.json`).
- **Accessibility**: Row highlight uses a **visual ring** (no roving `tabindex` on rows) so native checkbox/tab order stays intact; `aria-selected` reflects the highlighted row for assistive tech.
- **Browser notes**: Documented in the help dialog—e.g. some browsers reserve keys; use **/** for search or open the command palette from the nav menu if needed.

## Priority encoding in Google (US-015)

- **Why**: Google Tasks has no native priority field. Priorities are encoded directly in the Google-visible title so values round-trip across clients.
- **Scheme**: Titles are written as `[P1]`, `[P2]`, `[P3]`, or `[P4]` prefix plus the human title (e.g. `[P2] Prepare sprint demo`). `P1` is highest urgency, `P4` lowest.
- **Decode behavior**: API responses exposed to the Vue UI are normalized by `TaskPriorityCodec`: `title` is returned without prefix, `priority` is returned as `p1`–`p4`, and `titleRaw` preserves the original Google title. Tasks without prefix default to `p3` (migration-safe default).
- **UI**: Tasks page includes a priority selector in the create form and per-task priority dropdown. Updates write through encoding (no raw title editing required).
- **Implementation**: `app/Services/Google/TaskPriorityCodec.php` with wiring in `TasksController` and `TaskSearcher`.

## Filters and Kanban (US-012)

- **Filters** (client-side on the loaded task set): **status** (all / incomplete / completed), **due** (any / overdue / due today / has due / no due), **priority** (any / P1–P4), and on **Today** only a **Google list** filter. Filters combine with the current navigation (Today, Inbox, or a single list). Preferences persist in **localStorage** (`gt-task-filters`, `gt-task-view-mode`).
- **List vs board**: **List** is the existing row layout; **Board** shows four **priority** columns (P1–P4), aligned with `TaskPriorityCodec`. Drag-and-drop between columns calls the same **PATCH** priority update as the per-task dropdown (no new server routes).
- **Performance**: Board columns use **scrollable** areas (`max-height` ~70vh) so large lists stay usable without embedding a virtual-list library. If every task is in one column, scroll that column.
- **Limits**: Google Tasks has no native “status columns” or tags; Kanban is **priority-only**. Moving between lists is not a drag target on the board (use bulk **Move to list** from list view).

## Bulk actions (US-017)

- **Desktop**: Each task row has a **selection** checkbox (first column) plus the existing **complete** checkbox. **Ctrl/Cmd+click** a row to toggle its selection without clearing others; **Shift+click** selects from the last anchor to that row. The bulk bar (**Clear**, **Select all**, **Complete**, **Move to list…**, **Delete…**) appears when at least one task is selected.
- **API**: Moves use `POST /tasks/data/{taskList}/tasks/{task}/move` with `{ destinationTasklist }` (Tasks API `tasks.move`). Complete/delete use the existing `PATCH`/`DELETE` routes. Calls run **one task at a time**; if some fail, a modal lists the partial errors.
- **Mobile**: Use the **checkbox column** only — there is no touch multi-select gesture; range selection is desktop-only.

## Semantic search (US-018)

- **Optional**: set `SEMANTIC_SEARCH_ENABLED=true` and `OPENAI_API_KEY` (see `.env.example`). Embeddings are stored in `task_embeddings` (per-user, cascades on account delete).
- **Modes**: keyword (default, same as US-013) vs **semantic** (cosine similarity over indexed vectors). Use **Build semantic index** in the UI or `POST /tasks/data/search/reindex` / `php artisan google-tasks:reindex-embeddings`.
- **Details**: [docs/SEMANTIC_SEARCH.md](docs/SEMANTIC_SEARCH.md).

## Search (US-013)

- **Endpoint**: `GET /tasks/data/search?q=` (min 2, max 200 characters). Requires Google Tasks connection (`google.tasks` middleware).
- **Behavior**: Server lists every task list, paginates through tasks per list via the Tasks API, and matches **all words** in the query against **title + notes** (case-insensitive). Returns up to **75** matches with **list name** and a **snippet** around the first word hit.
- **Limits**: No server-side index — each search calls Google repeatedly; large accounts may be slow or hit rate limits (429). Results are capped at 75; `truncated: true` in JSON when the cap is hit. Not a substitute for offline/full-text indexing (see backlog for future semantic/indexed search).

## Views (US-009)

- **Today** (server-aggregated): incomplete tasks with a **due** date on or before the end of **today** in `APP_TIMEZONE` — includes **overdue** items until completed or rescheduled. Tasks **without** a due date do not appear here.
- **Inbox**: tasks in the **default** Google list — we pick the list titled **“My Tasks”** when it exists, otherwise the **first** list returned by Google. New tasks from Today/Inbox are created in that default list.
- **Lists**: per-list view unchanged from US-008; sidebar shows the active list; mobile uses a **Lists** drawer plus **Today** / **Inbox** in the bottom bar.

## Sync and performance (US-008)

Google Tasks is the **source of truth**. This app refreshes OAuth access tokens server-side (cached), proxies list/task operations to the **Tasks API v1** REST surface, and keeps tokens out of the browser.

- **Polling**: the Tasks page refetches task lists and tasks on an interval from `GOOGLE_TASKS_POLL_INTERVAL_MS` (default **5000** ms). On HTTP **429** from Google, the UI increases delay up to `GOOGLE_TASKS_MAX_BACKOFF_MS` (default **120000** ms).
- **~5 seconds** “feels synced” after edits is a **design goal**, not an SLA: Google quotas, network latency, and the poll interval dominate.
- **Tests / CI**: feature tests render Inertia pages via Vite; run **`npm run build`** in `apps/google-tasks` so `public/build/manifest.json` includes new pages before `php artisan test`. Full pyramid and commands: [docs/TESTING.md](docs/TESTING.md).

## Testing (US-020)

- **PHP**: `php artisan test` (feature tests use `Http::fake` for Google — no live API).
- **JavaScript**: `npm run test:unit` (Vitest) for `resources/js/utils/`.
- **Lint**: `composer lint` (Pint), `npm run lint` (ESLint).
- **E2E smoke**: `npm run test:e2e` (Playwright) — `/health` + `/login` email form; **no OAuth** in the browser.

See [docs/TESTING.md](docs/TESTING.md) for the pyramid, coverage stance, and CI.

## PWA install (US-022)

- **Install**: Web app manifest at `/manifest.webmanifest`, icons in `public/icons/`, `theme-color` in `app.blade.php`. Install prompts depend on browser heuristics (engagement, HTTPS).
- **Service worker**: Registers only in **production** builds; **cache-first** for `/build/` assets only — no offline Tasks or API caching. Details: [docs/PWA.md](docs/PWA.md).

## Data retention and logging (US-021)

- **Disconnect**: Profile includes **Disconnect Google** when a Google Tasks connection exists. It removes OAuth fields from the user, clears the cached access token, and deletes **semantic index** rows (`task_embeddings`) for that user. Google Tasks data in Google is unchanged.
- **Account delete**: User row deletion cascades to `task_embeddings`; cached access token is cleared before logout/delete.
- **Logs**: Prefer structured fields (`user_id`, status codes) over raw task text in production. Details: [docs/DATA_RETENTION.md](docs/DATA_RETENTION.md).

## Deploy

Production deploys from the monorepo **`main`** branch: push (or merge) to `main` triggers the **Google Tasks — DreamHost deploy** workflow when `apps/google-tasks/**` changes. Details, secrets, and manual DreamHost steps: [docs/DEPLOY.md](docs/DEPLOY.md). OAuth setup: [docs/GOOGLE_OAUTH.md](docs/GOOGLE_OAUTH.md).

**Backups / restore** ([US-035](../../../project-management/backlog/user-stories/US-035-server-backup-and-restore.md)): [docs/BACKUP_RESTORE.md](docs/BACKUP_RESTORE.md), script `scripts/backup-google-tasks.sh`.
