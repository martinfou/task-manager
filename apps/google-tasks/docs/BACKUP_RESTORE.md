# Server backup and restore (Google Tasks app)

Operators: this is the **canonical runbook** for [US-035](../../../project-management/backlog/user-stories/US-035-server-backup-and-restore.md). It covers what to back up on the **DreamHost** (or similar) host, **tiered retention**, **offsite copy to Dropbox**, and **how to restore** without reverse-engineering the server.

**Product choice (US-035)**: archives are **not** encrypted at rest beyond **who can access** the DreamHost account and Dropbox. DB dumps may contain **PII** (emails, OAuth metadata, embedding-related text). Treat backups like production data.

**This production deployment uses SQLite** (`DB_CONNECTION=sqlite`, usually `database/database.sqlite` under the app root). The backup script copies a **consistent** snapshot via `sqlite3 … .backup`. Restore steps below treat **SQLite as the primary path**; MySQL/PostgreSQL remain documented if you ever migrate.

---

## What is in scope

| Include | Why |
|---------|-----|
| **Database** | Full logical dump: users, sessions, jobs, `task_embeddings`, etc. (see [DATA_RETENTION.md](DATA_RETENTION.md)) |
| **`storage/`** | App-owned files under `storage/app`, framework cache/sessions/views — **excluding** `storage/logs` (large, low value for restore) |
| **`backup/MANIFEST.txt`** | Inside each archive: UTC time, hostname, `APP_ENV`, `DB_CONNECTION`, artifact names — **no secrets** |

| Exclude | Why |
|---------|-----|
| **`vendor/`, `node_modules/`** | Rebuilt from lockfiles on deploy |
| **`.env`, `.env.*`** | Secrets; **re-apply manually** after restore (see below) |
| **`public/build/`** | Rebuilt by CI/deploy |
| **`storage/logs/**`** | Noise, size; optional manual copy if debugging a specific incident |

Google Tasks **task titles and content** remain authoritative in **Google**; this backup protects **app-owned** state only.

With **SQLite**, the live file lives on disk next to the app; DreamHost **account backups** (if any) may still capture `database/database.sqlite`. For a given incident, pick **one** restore path (panel/home backup *or* `backup/database.sqlite` from this tarball) and stick to it so instructions do not conflict. If you later move to a **managed server DB**, treat host snapshots and logical dumps the same way: **one** source of truth per restore.

---

## Layout on the server (DreamHost)

Align with deploy workflow: Laravel root = `DREAMHOST_REMOTE_PATH` in [`.github/workflows/google-tasks-deploy-dreamhost.yml`](../../../.github/workflows/google-tasks-deploy-dreamhost.yml) (example: `/home/martinfournier_tasks/tasks.martinfournier.com`).

**Recommendation**: set `BACKUP_ROOT` to a **sibling directory** (or another user-writable tree), **not** inside `public/` and not the live DB directory:

```text
/home/martinfournier_tasks/
  tasks.martinfournier.com/     # live app (from rsync)
  backups/google-tasks/         # BACKUP_ROOT — backup tiers live here
```

Under `BACKUP_ROOT`, the script creates:

```text
$BACKUP_ROOT/<APP_ENV>/
  incoming/   # latest copy of each run (optional audit trail)
  daily/      # last 14 successful daily archives
  weekly/     # up to 4 (Sunday UTC runs promoted)
  monthly/    # up to 3 (1st-of-month UTC runs promoted)
```

**Naming**: `google-tasks_<APP_ENV>_<YYYYMMDD_HHMMSS>.tar.gz` (UTC).

---

## Automated backup (recommended: server cron)

**Rationale**: Runs next to the live **SQLite** file (fast, single-file copy via `sqlite3 .backup`), does not depend on GitHub Actions being up. CI-based SSH backup is a valid alternative; if you use it, mirror this retention and alert on workflow failure.

### 1. Install the script

The repo ships `scripts/backup-google-tasks.sh`. Deploy already rsyncs `apps/google-tasks/`; after deploy the script lives at:

`<APP_ROOT>/scripts/backup-google-tasks.sh`

Make it executable once on the server if needed: `chmod +x scripts/backup-google-tasks.sh`

### 2. Environment variables on the server

Set in the **cron wrapper** or a **root-only** env file that you `source` (never commit):

| Variable | Required | Purpose |
|----------|----------|---------|
| `BACKUP_ROOT` | Yes | Parent of per-`APP_ENV` backup trees |
| `RCLONE_REMOTE` | No | rclone remote **name** (no colon), e.g. `dh_dropbox` |
| `BACKUP_LOG` | No | Append log path for troubleshooting |
| `SKIP_PRUNE` | No | Set to `1` to only create archives without tier prune (debug) |

**Dropbox (offsite)**: install [rclone](https://rclone.org/) on the host, run `rclone config`, create a remote using a **Dropbox app token** or limited OAuth, scoped to a **dedicated folder** (e.g. `GoogleTasksBackups/`). Store **only** credentials in server env or `~/.config/rclone/` — **never** in git.

**If rclone/Dropbox fails**: the script **exits non-zero** after writing the **DreamHost** copy under `daily/`; fix Dropbox or run `rclone copy` manually later. Documented fallback: **retry** from `daily/` or copy tarball by hand.

Remote path used: `GoogleTasksBackups/<APP_ENV>/incoming/<archive>.tar.gz`.

### 3. Cron schedule example

Daily run (adjust paths and PHP user):

```cron
MAILTO=you@example.com
SHELL=/bin/bash
PATH=/usr/local/bin:/usr/bin:/bin

# 03:15 UTC daily — Google Tasks backup + prune + Dropbox
15 3 * * * BACKUP_ROOT=/home/martinfournier_tasks/backups/google-tasks RCLONE_REMOTE=dh_dropbox BACKUP_LOG=/home/martinfournier_tasks/backups/google-tasks/backup.log /home/martinfournier_tasks/tasks.martinfournier.com/scripts/backup-google-tasks.sh /home/martinfournier_tasks/tasks.martinfournier.com
```

**Monitoring (minimal)**:

- **MAILTO**: cron emails on failure (many hosts).
- Or rely on **`BACKUP_LOG`** and a **weekly manual check** (documented here as acceptable per US-035).
- GitHub Actions: only if you add a scheduled workflow — fail the job and use GitHub notifications.

### 4. Retention rules (automated in script)

| Tier | Rule | Count |
|------|------|-------|
| **Daily** | Every successful run writes to `daily/` | Keep **14** newest |
| **Weekly** | Runs on **Sunday UTC** copy the archive into `weekly/` | Keep **4** newest |
| **Monthly** | Runs on the **1st UTC** copy into `monthly/` | Keep **3** newest |

**Anchors**: weekly/monthly snapshots are **copies of the same archive** produced that day (the daily run at cron time). If you need “end of week” instead, change cron to Sunday 23:59 UTC.

---

## Restore runbook (English)

**Who may restore**: operators with **SSH** to the app host and (if recovering from offsite) **Dropbox** access. SQLite has **no separate DB password** in typical Laravel setups — protect the server account and `.env` instead. **Do not** share Dropbox tokens in tickets.

**RPO (plain language)**: you lose changes **after** the backup timestamp in the archive (tasks edited only in Google are unaffected).

**Rough duration**: often **~5–15 minutes** for SQLite + `storage/` on a small VPS/shared host; longer if the DB grows large or disk is slow.

### Prerequisites

- A **backup tarball** (`google-tasks_*.tar.gz`) from `daily/`, `weekly/`, `monthly/`, Dropbox `incoming/`, or DreamHost `incoming/`.
- **`.env`** for the target environment (same keys as production — **restore from password manager / deploy notes**, not from the tarball).
- **Maintenance** (recommended): enable Laravel maintenance if you must avoid writes during DB restore:

  ```bash
  php artisan down --secret="your-secret-token"
  ```

  Users hit `/up` with secret only if you use that pattern; or use `php artisan down` without secret for full block.

### Steps

1. **SSH** to the host (or use staging VM / local Docker with same PHP extensions).

2. **Extract** the archive to a temp directory:

   ```bash
   mkdir -p /tmp/gt-restore && tar -xzf google-tasks_production_YYYYMMDD_HHMMSS.tar.gz -C /tmp/gt-restore
   ```

   Inspect `/tmp/gt-restore/backup/MANIFEST.txt` for `db_connection` and `db_artifact`.

3. **Stop writers** (optional but safer): maintenance mode above, or stop queue workers if you run them separately.

4. **Restore database**

   - **SQLite (production for this app)** — artifact: `backup/database.sqlite` inside the tarball.

     Resolve the live path from `.env`: `DB_DATABASE` is often `database/database.sqlite` **relative** to `APP_ROOT`, or an absolute path.

     ```bash
     APP=/home/martinfournier_tasks/tasks.martinfournier.com   # your Laravel root
     # Optional safety copy of current DB before overwrite:
     cp "$APP/database/database.sqlite" "$APP/database/database.sqlite.bak.$(date +%Y%m%d%H%M)" 2>/dev/null || true

     cp /tmp/gt-restore/backup/database.sqlite "$APP/database/database.sqlite"
     chmod 664 "$APP/database/database.sqlite"
     ```

     Set **owner/group** to whatever **PHP** uses on that host (DreamHost shared hosting is often **your shell user**; some VPS setups use `www-data`). Match permissions on `database/` and the old file if unsure.

   - **MySQL / MariaDB** (`backup/db.sql`) — only if you use that engine:

     ```bash
     mysql -h DB_HOST -u DB_USER -p DB_NAME < /tmp/gt-restore/backup/db.sql
     ```

   - **PostgreSQL** (`backup/db.dump`) — only if you use that engine:

     ```bash
     pg_restore --clean --if-exists -h DB_HOST -U DB_USER -d DB_NAME /tmp/gt-restore/backup/db.dump
     ```

5. **Restore `storage/`** (merge over live `storage/`, excluding logs if you prefer):

   ```bash
   rsync -a /tmp/gt-restore/backup/storage/ /path/to/app/storage/
   ```

6. **`.env`**: ensure **production** `.env` exists on the server (never from backup). Confirm `APP_KEY`, `DB_*`, `GOOGLE_*`, `OPENAI_*`, etc.

7. **Laravel**:

   ```bash
   cd /path/to/app
   php artisan migrate --force    # only if schema drift vs backup; often no-op
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan up                 # if you used down
   ```

8. **Verify**

   - `curl -sf -o /dev/null -w "%{http_code}" https://your-domain/health` → **200**
   - Browser: **login**, open **Tasks**, load **task lists** (Google OAuth must still be valid for each user).

9. **Semantic index**: if `task_embeddings` was restored but Google data moved on, operators may run a **reindex** from the UI or `php artisan google-tasks:reindex-embeddings` per [SEMANTIC_SEARCH.md](SEMANTIC_SEARCH.md).

### Restore from Dropbox only

If DreamHost disk is lost: download the same `google-tasks_*.tar.gz` from `GoogleTasksBackups/<APP_ENV>/incoming/` (or sync with `rclone copy` to a new host), then follow the steps above on the new server after reinstalling the app tree and `.env`.

---

## Verification log (dry run)

**Requirement (US-035)**: restore from a fresh backup to a **non-production** target once; record **date** and **commit** here.

| Environment | Date (UTC) | Commit | Result |
|-------------|------------|--------|--------|
| Local smoke: backup script, SQLite DB, tarball contents | 2026-03-22 | Record `git rev-parse HEAD` after merging US-035 | Archive contains `MANIFEST.txt`, DB file, `storage/` without `logs/`; script exit 0 |
| Full restore to staging/disposable DB | *Operator TBD* | | *Fill after dry run* |

---

## Related docs

- [DEPLOY.md](DEPLOY.md) — production deploy path
- [DATA_RETENTION.md](DATA_RETENTION.md) — disconnect, purge, what is stored
- [SEMANTIC_SEARCH.md](SEMANTIC_SEARCH.md) — embeddings / reindex
