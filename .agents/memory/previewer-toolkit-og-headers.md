---
name: Previewer Toolkit OG image headers
description: Two fixed bugs in plugins/previewer-toolkit/index.php — og:image session headers, and inspector SSRF false positives.
---

## Bug 1: og:image "no style" on social media crawlers

**Root cause:** PHP `session_start()` (called in `_bootstrap.php`) queued three headers on every response including image render URLs:
- `Set-Cookie: AWAN_SESSION=…`
- `Pragma: no-cache`
- `Expires: Thu, 19 Nov 1981 08:52:00`

Social-media OG crawlers (Facebook, Twitter/X, LinkedIn, Slack) treat any response with `Set-Cookie` or `Pragma: no-cache` as non-cacheable/private and refuse to render it as og:image.

**Fix:** At the top of the `$is_render` block, before any cache-hit or miss processing:
```php
if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
header_remove('Set-Cookie');
header_remove('Pragma');
header_remove('Expires');
header_remove('Cache-Control'); // PT_Exporter then sets the correct one
```

**Why safe:** Nothing in the render path (Cache.php, Exporter.php, Renderer.php) reads or writes session data.

---

## Bug 2: Inspector "Requests to private or internal addresses" false positives

**Root cause:** The SSRF guard called `gethostbyname()` and blocked ALL private/reserved IPs via `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`. This caused false positives because:
- Replit container routes `*.replit.dev` domains through `172.24.0.5` (RFC1918)
- Shared hosting with split-horizon DNS may resolve own domain to internal IP

**Fix:** Split guard into two paths:
- Raw IP literals → block all private/reserved ranges (full SSRF protection)
- Domain names → block only loopback (127.x/::1) and metadata services (169.254.x), NOT RFC1918

Also replaced `file_get_contents` (TOCTOU-vulnerable) with cURL + `CURLOPT_RESOLVE` to pin the connection to the pre-checked IP, eliminating DNS-rebinding attacks. Redirects handled manually with per-hop SSRF re-validation.

**Tradeoff documented in code:** Allowing RFC1918 for domain names means a DNS-rebinding attacker could route to internal IPs. Acceptable for a meta-tag-only parser because: (1) attacker still needs to control DNS, (2) CURLOPT_RESOLVE on hop-0 prevents timing attacks, (3) only meta tags are returned from responses.
