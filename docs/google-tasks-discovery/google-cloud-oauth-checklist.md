# Google Cloud — OAuth + Tasks API checklist

Use this before the **first production** (or shared-staging) OAuth cutover for [`apps/google-tasks`](../../apps/google-tasks/). Detailed app routes and env keys: [`apps/google-tasks/docs/GOOGLE_OAUTH.md`](../../apps/google-tasks/docs/GOOGLE_OAUTH.md).

## APIs

- [ ] **Google Tasks API** enabled: Google Cloud Console → **APIs & Services** → **Library** → search “Google Tasks API” → **Enable**.

## OAuth consent screen

- [ ] **APIs & Services** → **OAuth consent screen** configured (User type **External** is typical for consumer Google accounts).
- [ ] App name, support email, and developer contact filled in.
- [ ] **Scopes** include what the app requests (this codebase uses Socialite with):
  - `openid`, `profile`, `email` (identity)
  - `https://www.googleapis.com/auth/tasks` (Tasks read/write)
- [ ] If the app is in **Testing**: **Test users** lists every Google account that should be able to sign in (only test users can use the app until published or moved to production verification).

## OAuth client (Web application)

- [ ] **Credentials** → **Create credentials** → **OAuth client ID** → type **Web application**.
- [ ] **Authorized JavaScript origins** (if your OAuth flow requires them), e.g.  
  - `http://127.0.0.1:8000` / `http://localhost:8000`  
  - `https://your-production-host.example`
- [ ] **Authorized redirect URIs** match **`GOOGLE_REDIRECT_URI`** in `.env` **exactly** (scheme, host, port, path), e.g.  
  - `http://localhost:8000/auth/google/callback`  
  - `https://your-production-host.example/auth/google/callback`  
  The Laravel route is [`/auth/google/callback`](../../apps/google-tasks/routes/web.php) (see `routes/web.php`).

## Application secrets

- [ ] **Client ID** → `GOOGLE_CLIENT_ID` in `.env`.
- [ ] **Client secret** → `GOOGLE_CLIENT_SECRET` in `.env`.
- [ ] `APP_URL` matches the origin you use for redirects (production must be **HTTPS**).
- [ ] `GOOGLE_REDIRECT_URI` is set (often `${APP_URL}/auth/google/callback`); must match Console redirect URI.

## Post-setup smoke test

- [ ] Local: `php artisan serve`, open `/login`, use **Continue with Google**, complete consent, land in app with `/tasks` loading after connection.
- [ ] If redirect fails: compare Console redirect URI, `APP_URL`, and `GOOGLE_REDIRECT_URI` byte-for-byte (trailing slashes matter).

## Related

- [US-007 — Google OAuth flow](../../project-management/backlog/user-stories/US-007-google-oauth-combined-flow.md)
- [RI-001 — retrospective improvement](../../project-management/backlog/retrospective-improvements/RI-001-google-cloud-oauth-checklist.md)

**Last updated**: 2026-03-21
