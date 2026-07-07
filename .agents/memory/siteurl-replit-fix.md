---
name: siteUrl Replit localhost fix
description: siteUrl() produced 127.0.0.1:5000 og:image URLs on Replit dev; fix uses REPLIT_DOMAINS env var instead.
---

## Rule
When `site_url` is blank in the DB, `siteUrl()` in `_bootstrap.php` must check `REPLIT_DOMAINS` env var before falling back to `HTTP_HOST`. On Replit's built-in PHP dev server, `HTTP_HOST` is `127.0.0.1:5000` — not publicly reachable — so any absolute URL built from it (og:image, canonical, email links, etc.) will be broken for browser or crawler access.

**Why:** Replit runs PHP with `php -S 0.0.0.0:5000` behind a reverse proxy. The proxy exposes a public `*.replit.dev` domain but does not rewrite `HTTP_HOST`. `REPLIT_DOMAINS` (always set in Replit's environment) contains the correct public hostname.

**How to apply:**
- In `siteUrl()`: strip port, check if host is a loopback address (`127.*`, `localhost`, `::1`, `0.0.0.0`); if so, read `REPLIT_DOMAINS` and use `https://` + first entry.
- In any file that manually builds a base URL from `HTTP_HOST`, replace it with `siteUrl()` so the fix is centralized.
- The `site_url` DB setting (set by admin) always takes priority; this fallback only applies when that setting is blank.
- `index.php`'s og:image block was previously duplicating the base-URL logic; it now calls `siteUrl()` directly.
