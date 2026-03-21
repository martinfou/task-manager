# Google OAuth (Tasks API)

This app uses **Laravel Socialite** with a **single consent screen** that includes identity (`openid`, `profile`, `email`) and **Google Tasks** (`https://www.googleapis.com/auth/tasks`). Refresh tokens are stored **encrypted** on the `users` table (`APP_KEY` required in every environment).

## Google Cloud Console

1. Open [Google Cloud Console](https://console.cloud.google.com/) and select or create a project.
2. **APIs & Services → Enable APIs** — enable **Google Tasks API**.
3. **APIs & Services → Credentials → Create credentials → OAuth client ID**.
   - Application type: **Web application**.
   - **Authorized JavaScript origins** (if required by your setup):
     - Local: `http://localhost:8000` (or your `APP_URL` origin).
     - Production: `https://your-domain.example`.
   - **Authorized redirect URIs** — must match `GOOGLE_REDIRECT_URI` exactly:
     - Local: `http://localhost:8000/auth/google/callback` (adjust port/host to match `APP_URL`).
     - Production: `https://your-domain.example/auth/google/callback`.
4. Copy **Client ID** and **Client secret** into `.env` as `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`.

## Application environment

Set in `.env` (see `.env.example`):

- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI` — typically `${APP_URL}/auth/google/callback`

Use **HTTPS** in production; redirect URIs are sensitive to scheme and path.

## Routes

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/auth/google` | Redirect to Google OAuth |
| GET | `/auth/google/callback` | OAuth callback; creates/updates user, stores tokens, session login |
| GET | `/tasks` | Inertia Tasks UI (requires login; data calls need a stored refresh token) |
| GET | `/tasks/data/task-lists` | JSON: task lists (middleware: Google Tasks connected) |
| GET | `/tasks/data/views/today` | JSON: aggregated Today rows (`taskListId`, `taskListTitle`, `task`) |
| GET | `/tasks/data/views/inbox` | JSON: default list + tasks (`taskList`, `items`) |
| GET/POST/PATCH/DELETE | `/tasks/data/{taskList}/tasks` … | JSON: sync with Google Tasks API (see `TasksController`) |

Sign-out uses the existing Breeze **logout** route and clears the session. Full account disconnect and data purge are tracked in [US-021](../../../project-management/backlog/user-stories/US-021-disconnect-purge-logging.md).

## Related

- [DEPLOY.md](DEPLOY.md) — hosting and env
- [RI-001](../../../project-management/backlog/retrospective-improvements/RI-001-google-cloud-oauth-checklist.md) — checklist (expand over time)
