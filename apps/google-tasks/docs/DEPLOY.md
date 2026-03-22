# Deploying the Google Tasks web client

Stack: **Laravel 13**, **Inertia**, **Vue 3**, **Vite**. PHP **^8.4** (see `composer.json`), Node **>=20** (see `package.json`).

## Production branch

**`main` is the production branch** for this monorepo. Merge or push work there when it is ready to ship.

- **GitHub Actions — DreamHost**: pushing to `main` (with changes under `apps/google-tasks/` or the deploy workflow file) runs [`.github/workflows/google-tasks-deploy-dreamhost.yml`](../../../.github/workflows/google-tasks-deploy-dreamhost.yml): build assets, `rsync` to the server, then remote `artisan migrate --force` and Laravel caches.
- **CI on `main`**: [`.github/workflows/google-tasks-ci.yml`](../../../.github/workflows/google-tasks-ci.yml) runs tests on the same path filters.
- You can also run the DreamHost workflow manually: **Actions → Google Tasks — DreamHost deploy → Run workflow** (uses the selected branch; use `main` for production).

**GitHub setting**: the repository default branch should be **`main`** (Settings → General → Default branch) so PRs and clones target production by default.

## Prerequisites

- Composer 2.x, PHP 8.4+ with extensions Laravel needs (`openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`).
- Node 20+ for `npm ci` / `npm run build`.
- SQLite (default) or MySQL/PostgreSQL for production.

## Fly.io (recommended path)

1. Install [flyctl](https://fly.io/docs/hands-on/install-flyctl/).
2. From `apps/google-tasks`: `fly launch` — set `APP_URL` to your Fly app URL, generate `APP_KEY` (`php artisan key:generate --show`), add secrets: `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, database URL, `GOOGLE_*` from Google Cloud Console.
3. Build: Dockerfile or use the [Laravel Fly template](https://fly.io/docs/laravel/) — run `npm run build` in the image and `php artisan migrate --force` on release.
4. Health checks: point Fly HTTP checks to `GET /up` (framework) or `GET /health` (JSON).

## DreamHost (shared / VPS)

**Typical flow**: configure the server once (below), then rely on **push to `main`** (or manual workflow) for deploys. First-time or emergency deploys can still run the commands on the server by hand.

1. PHP 8.4+ on the host; **document root** must point to **`public/`** (not the Laravel project root).
2. Set `APP_URL`, `APP_KEY`, database, mail, and `GOOGLE_*` in **`.env` on the server** (never committed). Match production URL in Google Cloud OAuth redirect URIs.
3. **GitHub Actions secrets** for automated deploy: `DREAMHOST_SSH_KEY`, `DREAMHOST_SSH_HOST`, `DREAMHOST_SSH_USER`. Optional repo variable **`DREAMHOST_PHP_BIN`** if your shell uses something other than `php-8.4` (see workflow comments).
4. Ensure `storage/` and `bootstrap/cache/` are writable; cron for `php artisan schedule:run` only if you add scheduled tasks.

The workflow rsyncs `apps/google-tasks/` to `DREAMHOST_REMOTE_PATH` (set in the workflow file), runs `migrate --force`, then `config:cache`, `route:cache`, `view:cache` on the server.

## OAuth redirect URIs

Full setup (scopes, Tasks API, env keys): [GOOGLE_OAUTH.md](GOOGLE_OAUTH.md).

Register **exact** URIs in Google Cloud Console (OAuth client):

- Local: `http://localhost:8000/auth/google/callback` (or your Valet/ Sail port).
- Production: `https://your-domain/auth/google/callback`.

See also [RI-001](../../../project-management/backlog/retrospective-improvements/RI-001-google-cloud-oauth-checklist.md) (checklist to be expanded).

## Semantic search (operations)

When `SEMANTIC_SEARCH_ENABLED=true` and `OPENAI_API_KEY` (or compatible endpoint) are set, users can build a **semantic index** and search by meaning. See [SEMANTIC_SEARCH.md](SEMANTIC_SEARCH.md) for architecture; [DATA_RETENTION.md](DATA_RETENTION.md) for disconnect and index deletion.

| Concern | Guidance |
|---------|----------|
| **First index / rebuild** | UI: **Build semantic index** on Tasks, or `POST /tasks/data/search/reindex` (authenticated + Google connected), or `php artisan google-tasks:reindex-embeddings [user_id]`. Large accounts can take **minutes** and many Google API + embedding calls. |
| **Cost** | Each (re)index batches tasks through the embeddings API; each **semantic query** adds one embedding call. Monitor provider usage; tune `SEMANTIC_INDEX_BATCH_SIZE` if needed. |
| **Reliability** | If Google returns 429/5xx, indexing may partially fail — check application logs. OAuth refresh issues appear as `google_oauth_token_refresh_failed` / `google_oauth_token_response_invalid` (see `GoogleOAuthTokenService`) — **no** tokens in log payloads. |
| **After deploy** | No extra migration beyond `task_embeddings` migration; ensure env vars are set in production. |

Tracked as [RI-003](../../../project-management/backlog/retrospective-improvements/RI-003-semantic-search-ops-runbook.md).

## Migrations (Tasks app)

Run `php artisan migrate` after deploy when new migrations ship. Recent examples: `task_embeddings` (semantic search), `users.undo_toast_delay_ms` (optional per-user undo-toast delay for destructive actions; default comes from `GOOGLE_TASKS_UNDO_TOAST_DELAY_MS` in config when the column is null).
