# Retrospective Improvement RI-001: Google Cloud OAuth Checklist

**Related**: [Sprint Retrospective Process](../../processes/sprint-retrospective-process.md)

---

## Metadata

| Field | Value |
|-------|-------|
| **ID** | RI-001 |
| **Description** | Publish a Google Cloud OAuth + Tasks API setup checklist in `docs/` before first OAuth deploy |
| **Owner** | Developer |
| **Due Sprint** | Sprint 3 (carried from Sprint 2 — checklist not yet published) |
| **Status** | ✅ Done |
| **Source Retro** | Sprint 1 — 2026-03-21 |

---

## Details

**What**: Short doc (`docs/google-tasks-discovery/google-cloud-oauth-checklist.md`) listing: enable Tasks API, OAuth consent / test users, redirect URIs for local + production, required scopes.

**Why**: Reduces failed deploys and callback mismatches when implementing [US-007](../user-stories/US-007-google-oauth-combined-flow.md).

**How**: Follow Google Cloud Console steps; link from [US-006](../user-stories/US-006-laravel-inertia-scaffold-deploy.md) / Sprint 2.

---

## History

- 2026-03-21 - Created from Sprint 1 retrospective
- 2026-03-21 - Due sprint extended to Sprint 2 delivery window; still open — carried to Sprint 3 per Sprint 2 retrospective
- 2026-03-21 - Published checklist at `docs/google-tasks-discovery/google-cloud-oauth-checklist.md`; linked from `docs/README.md`, discovery README, and `apps/google-tasks/docs/GOOGLE_OAUTH.md`
