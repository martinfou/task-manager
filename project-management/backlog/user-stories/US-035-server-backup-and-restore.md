---
template_version: 1.1.0
last_updated: 2026-03-22
compatible_with: [user-story, sprint-planning, product-backlog]
requires: [markdown-support]
---

# User Story: US-035 — Server Backup and Easy Restore

[← Back to Product Backlog](../product-backlog.md)

**Status**: ✅ Done
**Priority**: 🟠 High  
**Story Points**: 8  
**Created**: 2026-03-22  
**Updated**: 2026-03-22  
**Assigned Sprint**: [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md)

## Description

The **production** host for `apps/google-tasks` ([DreamHost deploy workflow](../../../.github/workflows/google-tasks-deploy-dreamhost.yml)) must have **reliable backups** of data the app **owns** (database, local `storage/` artifacts if any) and a **documented, repeatable restore** path so a human can recover from mistake, corruption, or host failure **without** reverse-engineering the server. Google Tasks content remains **authoritative in Google**; this story protects **app state** (users, tokens metadata, embeddings index tables, queues, logs policy per [US-021](US-021-disconnect-purge-logging.md), etc.).

## User Story

As an **operator** (or future you), I want **automated server backups** and a **clear restore procedure**, so that I can **recover the app quickly** after data loss or a bad deploy without guessing which files to copy or which commands to run.

## Acceptance Criteria

- [x] **Backup scope** is **documented** in-repo (e.g. `apps/google-tasks/docs/BACKUP_RESTORE.md` or extend `DEPLOY.md`): **database** (full logical dump appropriate to the engine in use), **`storage/`** (if non-empty or required for restore), and **explicit exclusions** (e.g. `vendor/`, `node_modules/`, build caches). **`.env`** is **not** stored inside unencrypted backup artifacts by default; document how secrets are re-applied on restore.
- [x] **Automated backups** run on a **schedule** (cron on server, DreamHost panel job, or CI with SSH — choose one, document rationale). **Retention** (per Clarifying Questions): keep **14 daily**, **4 weekly**, and **3 monthly** snapshots; pruning rules and naming (e.g. which run promotes to weekly/monthly) are **documented** in the backup doc/script.
- [x] Backup artifacts: **primary** copy on **DreamHost** (**separate path** from the live DB / app tree when feasible — per Clarifying Questions) **and** a **second copy** synced or uploaded to **Dropbox** (offsite). **Naming**: timestamp + environment on both sides; document **Dropbox** folder layout and auth (token/app — **no** tokens in repo). If DreamHost job cannot reach Dropbox, document **fallback** (queue + retry or alert).
- [x] **Restore runbook**: step-by-step **EN** (French optional for internal ops doc) — prerequisites, **stop traffic or maintenance mode** if required, restore DB, restore `storage/` if applicable, `php artisan` steps (migrate/cache/config as appropriate), **verify** health (HTTP 200, login, one critical API). Include **estimated time** order-of-magnitude and **what is lost** if restoring to an older point (RPO plain language).
- [x] **One verified dry run**: restore from a fresh backup to a **non-production** target (local Docker, staging subdomain, or disposable DB) **once** and record **date + commit** in the doc (screenshots optional). Fix gaps discovered during the dry run. *(Local backup + restore to disposable SQLite DB recorded in [BACKUP_RESTORE.md](../../../apps/google-tasks/docs/BACKUP_RESTORE.md#verification-log-dry-run); integrity check OK, all tables intact.)*
- [x] **Monitoring or alert** (minimal): operator gets a signal if backup job **fails** (email from cron, GitHub Actions failure notification, or weekly manual checklist in doc — pick and document).
- [x] **Security**: backup files rely on **access-controlled** storage only (**DreamHost** + **Dropbox** account permissions); **no** additional **at-rest encryption** of archives per Clarifying Questions — **document** this **explicit product choice** and residual risk (anyone with Dropbox/host access can read dumps). Document **who** may run restore and how **API tokens** for Dropbox are stored (env only).

## Business Value

Reduces **downtime and data-loss risk** for a single-tenant or small multi-user deployment; makes **compliance and peace of mind** explicit instead of “we hope DreamHost has something.”

## Technical Requirements

- Align paths with production layout referenced in [`.github/workflows/google-tasks-deploy-dreamhost.yml`](../../../.github/workflows/google-tasks-deploy-dreamhost.yml) (`DREAMHOST_REMOTE_PATH`).
- Prefer **script + doc** over one-off tribal knowledge; scripts live under `apps/google-tasks/scripts/` or `docs/` with executable bit / usage in README.
- Do **not** commit secrets; use server env or CI secrets for backup upload credentials if used.

## Reference Documents

- [INDEX.md](../../INDEX.md)
- [US-006](US-006-laravel-inertia-scaffold-deploy.md) — deployment baseline
- [US-021](US-021-disconnect-purge-logging.md) — data lifecycle context

## Technical References

- `.github/workflows/google-tasks-deploy-dreamhost.yml`
- `apps/google-tasks/docs/` — deploy / ops docs
- Laravel: `storage/`, database config in `.env` / `config/database.php`

## Dependencies

- [US-006](US-006-laravel-inertia-scaffold-deploy.md) — production deploy path must exist

## Clarifying Questions

*Record answers after refinement.*

- **Q**: **Retention**: how many **daily** backups to keep, and any **weekly** / **monthly** long-term?
- **A**: **14 daily** + **4 weekly** + **3 monthly** (tiered retention). Implement **pruning** so counts are enforced automatically; document how **weekly** and **monthly** anchors are chosen (e.g. last successful run of calendar week / month).
- **Date**: 2026-03-22

- **Q**: **Offsite** target — DreamHost-only, **S3-compatible**, other?
- **A**: **DreamHost + Dropbox** — keep a **primary** backup set on the **DreamHost** account (**non-primary** volume/path vs live DB when possible). **Also** push or sync the **same** artifacts to **Dropbox** for **offsite** recovery. Implementation choice (**rclone**, **Dropbox API**, **dbxcli**, etc.) is documented in ops docs; **credentials** via server env / secrets only. **Restore runbook** covers restore from **either** location (DreamHost path first for speed, Dropbox when host storage is lost).
- **Date**: 2026-03-22

- **Q**: **Encrypt** backup archives at rest (e.g. **gpg** / **age**) before **Dropbox**?
- **A**: **No** — **no** extra encryption layer; rely on **host + Dropbox access control** only. Revisit if threat model or compliance needs change.
- **Date**: 2026-03-22

## Notes

- This story is **ops/infra**; optional follow-up could add **application-level “export my data”** for end users (distinct from server backup).
- **Plaintext archives** (no encryption): limit **Dropbox** sharing, use **dedicated** folder, prefer **app** token scoped to that folder; DB dumps may contain **PII** — treat like production data.
- If production uses **managed DB** with its own snapshots, document **double coverage** or **single source of truth** to avoid conflicting restore instructions.

## Acceptance Verification

- [x] All acceptance criteria above verified as met
- [x] Each criterion tested or inspected and confirmed

## History

- 2026-03-22 - Implemented: `docs/BACKUP_RESTORE.md`, `scripts/backup-google-tasks.sh`, `DEPLOY.md` + README links
- 2026-03-22 - Dry run verified: backup → extract → restore to disposable SQLite DB; `PRAGMA integrity_check` OK, all tables intact (commit `ae7fd18`) — marked Done
- 2026-03-22 - Created (server backup + restore runbook)
- 2026-03-22 - Clarified retention: **14 daily**, **4 weekly**, **3 monthly**
- 2026-03-22 - Clarified storage: **DreamHost** primary path + **Dropbox** offsite copy
- 2026-03-22 - Clarified: **no** at-rest encryption of backups; access control only
- 2026-03-22 - Assigned to [Sprint 6](../../sprints/sprint-06-google-tasks-trust-commands-mobile.md) (all open backlog stories in sprint bucket)
