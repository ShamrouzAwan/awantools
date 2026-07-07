---
name: Previewer Toolkit meta.php SSRF hardening
description: How to safely fetch external URLs in PHP without SSRF via redirect chaining
---

## Rule
Never use `follow_location => true` with `file_get_contents` when the initial URL has been validated against a blocklist. Redirects bypass the validation entirely.

**Why:** A safe external URL can issue a 301/302 to `169.254.169.254` or `10.x.x.x`, which the HTTP client will silently follow, hitting the internal target even though the original URL passed the allow-check.

**How to apply:** Disable `follow_location`, read `$http_response_header` after each request to detect 3xx status codes, resolve relative Location headers into absolute URLs, run them through `pt_is_safe_url()`, then follow manually (up to N hops).

## Also
- Always leave TLS verification enabled (`verify_peer => true`, `verify_peer_name => true`). Disabling it opens MITM risk on metadata fetches.
- IPv6 private ranges (::1, fc00::/7, fe80::/10) need separate handling if `gethostbynamel()` only returns IPv4. Use `dns_get_record()` for full dual-stack coverage if that matters.
