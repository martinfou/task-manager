# Deploying the Google Tasks web client

Stack: **Laravel 13**, **Inertia**, **Vue 3**, **Vite**. PHP **^8.3** (see `composer.json`), Node **>=20** (see `package.json`).

## Prerequisites

- Composer 2.x, PHP 8.3+ with extensions Laravel needs (`openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`).
- Node 20+ for `npm ci` / `npm run build`.
- SQLite (default) or MySQL/PostgreSQL for production.

## Fly.io (recommended path)

1. Install [flyctl](https://fly.io/docs/hands-on/install-flyctl/).
2. From `apps/google-tasks`: `fly launch` — set `APP_URL` to your Fly app URL, generate `APP_KEY` (`php artisan key:generate --show`), add secrets: `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, database URL, `GOOGLE_*` from Google Cloud Console.
3. Build: Dockerfile or use the [Laravel Fly template](https://fly.io/docs/laravel/) — run `npm run build` in the image and `php artisan migrate --force` on release.
4. Health checks: point Fly HTTP checks to `GET /up` (framework) or `GET /health` (JSON).

## DreamHost (shared / VPS)

1. PHP 8.3+ on the host; document root should point to `public/` (not project root).
2. Set `APP_URL`, `APP_KEY`, database, mail, and `GOOGLE_*` in `.env` on the server.
3. Run `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, `php artisan config:cache route:cache view:cache`, `php artisan migrate --force`.
4. Ensure `storage/` and `bootstrap/cache/` are writable; cron for `php artisan schedule:run` if you use the scheduler later.

## OAuth redirect URIs

Full setup (scopes, Tasks API, env keys): [GOOGLE_OAUTH.md](GOOGLE_OAUTH.md).

Register **exact** URIs in Google Cloud Console (OAuth client):

- Local: `http://localhost:8000/auth/google/callback` (or your Valet/ Sail port).
- Production: `https://your-domain/auth/google/callback`.

See also [RI-001](../../../project-management/backlog/retrospective-improvements/RI-001-google-cloud-oauth-checklist.md) (checklist to be expanded).
