# Semantic search (US-018)

## Architecture

| Piece | Role |
|-------|------|
| **OpenAI-compatible embeddings API** | Default: `https://api.openai.com/v1/embeddings` with `text-embedding-3-small` (1536 dimensions). Set `OPENAI_EMBEDDINGS_URL` for a compatible proxy or self-hosted endpoint. |
| **`task_embeddings` table** | Per-user rows: Google `task_list_id` + `task_id`, JSON **embedding** vector, **title_raw** / **notes_raw** / **status** snapshots for display and priority decoding. |
| **`TaskEmbeddingIndexer`** | Full scan of all lists/tasks via Google API, batch embedding calls, then replace-all index for the user. |
| **`TaskSemanticSearcher`** | Embeds the query, loads all vectors for the user, ranks by **cosine similarity**, returns top N (default 75). |
| **Keyword search** | Unchanged: `TaskSearcher` scans Google with word matching (`mode=keyword` or omitted). |

## Privacy and retention

- Indexed text is **server-side only** (not sent to the browser except as search results).
- On **account deletion**, `task_embeddings` rows are removed via **foreign key cascade** on `user_id`.
- **Disconnect** (Profile → **Disconnect Google**): clears OAuth fields, forgets the cached access token, and **deletes all `task_embeddings` rows** for the user. See [US-021](../../../project-management/backlog/user-stories/US-021-disconnect-purge-logging.md) and [DATA_RETENTION.md](DATA_RETENTION.md).

## Configuration

See `.env.example`:

- `SEMANTIC_SEARCH_ENABLED=true`
- `OPENAI_API_KEY=...`
- Optional: `OPENAI_EMBEDDING_MODEL`, `OPENAI_EMBEDDINGS_URL`, `SEMANTIC_INDEX_BATCH_SIZE`, `SEMANTIC_SEARCH_MAX_RESULTS`

Without these, the Tasks UI hides semantic mode and `GET /tasks/data/search?mode=semantic` returns **422**.

## Operations

- **HTTP**: `POST /tasks/data/search/reindex` (authenticated + Google connected) — rebuilds the index (can take minutes on large accounts).
- **CLI**: `php artisan google-tasks:reindex-embeddings [user_id]`

## English and French

Queries and task text are embedded as UTF-8 strings; OpenAI’s embedding models handle multilingual input. Quality varies by model — tune `OPENAI_EMBEDDING_MODEL` if needed.

## Latency

Semantic search performs **one** embedding API call per query plus **in-memory** cosine similarity over all indexed tasks for that user. Very large indices may need future work (ANN index, chunking, queue-based reindex).
