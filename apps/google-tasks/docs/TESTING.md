# Testing (Google Tasks app)

## Test pyramid

| Layer | Tool | What we cover |
|-------|------|----------------|
| **PHP unit / feature** | PHPUnit (`php artisan test`) | HTTP routes, auth, Google client fakes, task sync, error JSON |
| **JS unit** | Vitest (`npm run test:unit`) | Pure utilities under `resources/js/utils/` (filters, API error helpers) |
| **Lint** | Laravel Pint (`composer lint`), ESLint (`npm run lint`) | PHP style, Vue/JS correctness (`route` global, no-undef errors) |
| **E2E smoke** | Playwright (`npm run test:e2e`) | `/health` JSON, `/login` email form — **no real Google OAuth** |
| **Semantic index** | Manual or `php artisan google-tasks:reindex-embeddings` | Requires `OPENAI_API_KEY`; not exercised in default CI (no API key in GitHub Actions). See [SEMANTIC_SEARCH.md](SEMANTIC_SEARCH.md). |

Full Google OAuth and Tasks API flows are covered with **HTTP fakes** in PHPUnit (see `tests/Feature/`). Playwright does not drive OAuth; it only checks that public routes render for regression detection.

## Commands

```bash
composer install && npm ci
cp .env.example .env && php artisan key:generate   # if needed

composer lint          # Pint (PHP), non-mutating check
npm run lint           # ESLint on resources/js
npm run test:unit      # Vitest
php artisan test       # PHPUnit
npm run build          # production Vite build
npx playwright install --with-deps   # first time / CI
npm run test:e2e       # Playwright (starts `php artisan serve` via config)
```

## Coverage targets (pragmatic)

- **No global coverage gate** in CI — focus on meaningful tests for sync, auth, and shared utilities.
- **Aim**: new domain logic (PHP services, `resources/js/utils`) ships with tests when feasible; feature tests use `Http::fake` for Google.
- Optional local report: `php artisan test --coverage` when Xdebug/PCOV is enabled (not required in default CI).

## CI

Repository workflow: `.github/workflows/google-tasks-ci.yml` runs lint, unit (JS + PHP), build, and Playwright smoke on changes under `apps/google-tasks/`.
