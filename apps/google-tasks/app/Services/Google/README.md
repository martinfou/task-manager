# Google API services

**OAuth**: Socialite routes and token persistence live in `App\Http\Controllers\Auth\GoogleOAuthController` and `User` (see `docs/GOOGLE_OAUTH.md`).

- `GoogleTasksClient` — Tasks API v1 REST (US-008); `moveTask()` wraps `tasks.move` for bulk “move to list” (US-017).
- `TaskViewAggregator` — **Today** and default **Inbox** list resolution (US-009).
- `TaskPriorityCodec` — reversible priority encoding in Google-visible task titles (`[P1] ...` through `[P4] ...`) with default fallback `p3` for unencoded tasks (US-015).

**Notes, links, and “attachments” (US-016)**  
Google Tasks exposes a single `notes` string per task — there is **no binary attachment or file upload** in the Tasks API. In this app, “attachments” means **URLs (and Drive links as plain text)** stored in `notes`. The web UI detects `http://` and `https://` URLs, renders them as links that open in a new tab, and blocks other URL schemes for safety. Rich link previews are not provided by the API; users see standard anchor links only.

Token refresh and rate-limit handling stay out of Vue; Inertia receives DTOs only.

Namespace: `App\Services\Google`.
