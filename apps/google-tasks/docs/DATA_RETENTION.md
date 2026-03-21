# Data retention (Google Tasks app)

This document describes what the app stores and what happens on **disconnect** and **account deletion**. It is a baseline for operators and privacy review; it is not legal advice.

## What is stored

| Data | Where | Purpose |
|------|--------|---------|
| Account | `users` (email, password hash, optional `locale`) | Authentication |
| Google OAuth | `users.google_id`, encrypted `google_refresh_token`, `google_token_expires_at` | Refresh access to Google Tasks |
| Cached access token | Laravel **cache** (`google_access_token:{user_id}`) | Short-lived token; not persisted in DB |
| Semantic index | `task_embeddings` (per-user rows with embedding vectors and title/notes snapshots) | Semantic search (optional feature) |

Task **titles and notes** in Google remain the **source of truth**. This app does not replace Google’s storage for live task content.

## Disconnect Google (Profile)

**POST `/profile/google/disconnect`** (password required):

- Deletes all **`task_embeddings`** rows for the user.
- Clears the **cached** Google access token for that user.
- Nulls **`google_id`**, **`google_refresh_token`**, **`google_token_expires_at`** on the user.

Google-side data is **not** deleted; only this app’s connection and local index rows are removed.

## Account deletion

Deleting the user account removes the `users` row. **`task_embeddings`** are removed by **foreign key cascade** on `user_id`.

Before deletion, the app clears the **cached** access token so no stale cache entry remains keyed by the old user id in typical setups.

## Logging

- **Development**: log level and verbosity may include more diagnostic detail; still avoid logging raw OAuth tokens or refresh tokens.
- **Production**: prefer **structured** logs with **identifiers** (`user_id`, request id, HTTP status). Do **not** log full task titles or notes in routine logs — use ids or redacted snippets if troubleshooting requires it.

OAuth token refresh failures are logged with **`user_id`** and HTTP **status** only (see `GoogleOAuthTokenService`).

## Configuration

Semantic search and embeddings are optional; see `.env.example` and [SEMANTIC_SEARCH.md](SEMANTIC_SEARCH.md).
