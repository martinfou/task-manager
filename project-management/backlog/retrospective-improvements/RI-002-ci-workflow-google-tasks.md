# Retrospective Improvement RI-002: CI workflow for `apps/google-tasks`

**Related**: [Sprint Retrospective Process](../../processes/sprint-retrospective-process.md)

---

## Metadata

| Field | Value |
|-------|-------|
| **ID** | RI-002 |
| **Description** | Add a CI workflow (e.g. GitHub Actions) that runs `composer install`, `npm ci`, `npm run build`, and `php artisan test` in `apps/google-tasks` |
| **Owner** | Developer |
| **Due Sprint** | Sprint 3 |
| **Status** | ⭕ To Do |
| **Source Retro** | Sprint 2 — 2026-03-21 |

---

## Details

**What**: Repository automation that validates the Laravel + Vite app on each push/PR.

**Why**: Catches broken Vite manifests, missing deps, and test regressions before merge; supports solo + AI velocity safely.

**How**: `.github/workflows/` job with `working-directory: apps/google-tasks`; PHP and Node versions aligned with `composer.json` / `package.json` engines.

---

## History

- 2026-03-21 — Created from Sprint 2 retrospective

---

**Last Updated**: 2026-03-21
