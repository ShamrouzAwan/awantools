---
name: Previewer Toolkit OG image headers
description: Why og:image from previewer-toolkit showed "no style" on social media, and the fix applied.
---

## The Problem
PHP's `session_start()` (called in `_bootstrap.php`) queues three headers on every response:
- `Set-Cookie: AWAN_SESSION=…`
- `Pragma: no-cache`
- `Expires: Thu, 19 Nov 1981 08:52:00`

These appeared on the image render URL even though the render code set `Cache-Control: public, max-age=86400`. Social-media OG crawlers (Facebook, Twitter/X, LinkedIn, Slack) treat any response with `Set-Cookie` or `Pragma: no-cache` as non-cacheable/private and refuse to render it as an og:image — hence "no style" or broken preview.

**Why:** The SVG preview in the builder looks fine (browser fetches it directly). The PNG URL itself is a valid image when opened in a tab. But crawlers fetching the same URL for og:image reject it due to the conflicting headers.

## The Fix (plugins/previewer-toolkit/index.php)
At the top of the `$is_render` block, before any cache-hit or cache-miss processing:

```php
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();   // release session lock — no session needed to render images
}
header_remove('Set-Cookie');    // no session cookie on image responses
header_remove('Pragma');        // remove Pragma: no-cache
header_remove('Expires');       // remove Expires: (past date)
header_remove('Cache-Control'); // let PT_Exporter set the correct Cache-Control
```

This applies to BOTH the cache-hit path (PT_Cache::serve_if_hit) and the cache-miss path (PT_Exporter::output), since it runs before either.

**Why:** session_write_close() is safe here — nothing in the render path (Cache.php, Exporter.php, Renderer.php) reads or writes session data.
