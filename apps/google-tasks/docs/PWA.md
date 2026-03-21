# Progressive Web App (US-022)

The app is **installable** (Add to Home Screen / install from the browser) when served over **HTTPS** (or `localhost`).

## Manifest

- **URL**: `GET /manifest.webmanifest` — `Content-Type: application/manifest+json`
- **Icons**: `public/icons/` — 192×192, 512×512 (maskable), 180×180 Apple touch
- **Theme**: `theme_color` / `background_color` align with the default dark shell (`#0f172a`)

`name` / `short_name` come from `config('app.name')` (`APP_NAME` in `.env`). Keep `short_name` ≤ 12 characters for best home-screen labels; the controller truncates long names.

## Service worker (`public/sw.js`)

The file lives under `public/` for CDN/static hosting. A **Laravel route** (`GET /sw.js`, name `pwa.sw`) also serves it with the correct `Content-Type` so PHPUnit and PHP’s built-in server behave consistently.

| Behavior | Detail |
|----------|--------|
| **Registered** | Only in **production** builds (`import.meta.env.PROD` in `resources/js/app.js`). Not registered during `npm run dev` (avoids interfering with Vite HMR). |
| **Scope** | `/` |
| **Caching** | **Cache-first** for same-origin **`/build/**`** responses only (hashed Vite assets). |
| **Not cached** | HTML documents, `/tasks`, `/tasks/data/*`, OAuth, Inertia JSON, APIs — all require the network. |

So the PWA **does not** imply offline Google Tasks: users always need connectivity for task data. There is **no** offline task queue in this story; that would be a separate backlog item.

## Changing icons

Replace PNGs under `public/icons/` and bump the service worker cache name in `public/sw.js` if you need to force clients to drop old cached assets after a deploy.

## Testing

- `GET /manifest.webmanifest` should return **200** and valid JSON.
- After `npm run build`, production JS registers the service worker; use browser DevTools → Application → Service Workers to verify.
