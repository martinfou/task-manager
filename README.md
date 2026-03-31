# Google Tasks Client

**A better way to manage your Google Tasks.**

Priority levels, Kanban boards, smart search, productivity insights, and mobile swipe actions — all synced with your Google account.

**[Open the app](https://tasks.martinfournier.com)**

<p align="center">
  <img src="apps/google-tasks/demo.gif" alt="Google Tasks Client demo" width="800">
</p>

---

## Why use this over the default Google Tasks?

Google Tasks is great for capturing tasks. But it gives you no priorities, no Kanban view, no search across lists, no productivity stats, and no keyboard shortcuts. This client adds all of that while keeping Google Tasks as the source of truth — your data never leaves Google.

---

## Features

### Task Views

| View | What it shows |
|------|---------------|
| **Today** | Tasks due today or overdue, aggregated across all lists |
| **Inbox** | Your default Google list |
| **All Lists** | Every task across every list, with list filters |
| **Per-List** | Individual list view |

Switch between views instantly — cached locally for zero-wait navigation.

### Priority System

Four levels: **P1** (Urgent), **P2** (High), **P3** (Medium), **P4** (Low). Priorities are encoded into task titles as `[P1]` prefixes, so they sync across every Google Tasks client — phone, web, widget.

### Kanban Board

Drag-and-drop tasks between columns. Two board layouts:

- **Priority lanes** — P1 through P4 columns
- **Due-date lanes** — Overdue, Today, This Week, Later

### Smart Search

- **Full-text search** across all task titles and notes
- **Semantic search** (optional, AI-powered) — finds tasks by meaning, not just keywords
- **Duplicate detection** — surface similar tasks across lists

### Productivity Dashboard

Track your task throughput with real analytics:

- **Period summary** — tasks created vs. completed
- **Focus score** — your completion rate
- **Lead time** — median days to close a task
- **Consistency** — active days vs. calendar days
- **Throughput chart** — daily created/completed over 7, 14, 30, or 90 days
- **Weekday rhythm** — which days you get the most done
- **Due discipline** — on-time vs. overdue breakdown

### Mobile-First Design

- **Swipe right** to complete, **swipe left** for more actions
- **Pull-to-refresh** on all views
- **Bottom navigation** — Today, Inbox, Lists, Dashboard
- **Snooze presets** — Tomorrow, Next Week, Weekend, or pick a date
- **Undo toast** — 10-second window to reverse any destructive action

### Keyboard Shortcuts (Desktop)

| Key | Action |
|-----|--------|
| `Ctrl+K` / `Cmd+K` | Command palette |
| `n` | New task |
| `/` | Search |
| `g` then `t` / `i` / `l` | Go to Today / Inbox / Lists |
| `Space` / `Enter` | Toggle completion |
| `i` | Open task details |
| `?` | Show all shortcuts |

### Bulk Actions

Select multiple tasks with checkboxes, then complete, move, or delete them all at once.

### Command Palette

`Ctrl+K` opens a searchable command list: navigate views, jump to a list, create a task, or trigger a sync — without touching the mouse.

### Task List Organization

Pin your most-used lists to the top. Drag to reorder. Optional auto-sort (A-Z or Z-A).

### Dark Mode & Density

Dark theme by default. Toggle to light. Switch between Comfort and Compact density.

### Bilingual

Full English and French interface. Language preference persists across sessions.

### Installable (PWA)

Add to your home screen on mobile or desktop. Launches as a standalone app with its own icon.

---

## Privacy

Your tasks live in Google. This app stores only:

- Your auth session and encrypted OAuth tokens (server-side, never exposed to the browser)
- An optional semantic search index (deleted on disconnect)
- Dashboard analytics cache (computed from Google data, not stored long-term)

Disconnect your Google account at any time — tokens and index data are purged immediately.

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 13, PHP 8.4 |
| Frontend | Vue 3, Inertia.js, Vite |
| Database | SQLite |
| Hosting | DreamHost (auto-deployed via GitHub Actions) |
| Auth | Google OAuth 2.0 + email/password |
| Search | OpenAI embeddings (optional) |
| PWA | Service worker with cache-first for static assets |

---

## Development

```bash
cd apps/google-tasks

# Backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve

# Frontend
npm install
npm run dev
```

See [apps/google-tasks/docs/DEPLOY.md](apps/google-tasks/docs/DEPLOY.md) for production deployment and [apps/google-tasks/docs/GOOGLE_OAUTH.md](apps/google-tasks/docs/GOOGLE_OAUTH.md) for OAuth setup.

---

## Repository Layout

| Path | Purpose |
|------|---------|
| `apps/google-tasks/` | The Laravel + Vue application |
| `project-management/` | Backlog, sprints, processes ([INDEX.md](project-management/INDEX.md)) |
| `agency-agents/` | Agent role definitions for AI orchestration |
| `mcp-project-management/` | MCP server for AI-assisted project management |

---

## License

Private repository. All rights reserved.
