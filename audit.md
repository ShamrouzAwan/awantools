# AWAN Tools Platform — Full Site Audit

**Date:** July 13, 2026
**Scope:** Security, code quality, SEO, performance, accessibility, broken links
**Method:** Automated scans (SAST/dependency/privacy scanners) + targeted subagent review across `_core/`, `_router.php`, `_bootstrap.php`, root pages, `admin/`, `api/`, `plugins/*`, `themes/default/`. Every finding below marked **[Verified]** was hand-confirmed by re-reading the actual source; findings marked **[Reported, not independently re-verified]** came from the review pass but weren't individually re-checked. Findings the initial pass flagged that turned out to be **false positives on verification are listed at the bottom** so they aren't chased again.

Automated scanners (`runSastScan`, `runHoundDogScan`, `runDependencyAudit`) returned no findings — expected, since this is a dependency-free PHP codebase with no `package.json`/lockfile for those tools to analyze against. The real signal in this audit comes from the manual/subagent source review below.

---

## 1. Security

| # | Finding | Severity | Status |
|---|---|---|---|
| 1 | **DNS-rebinding gap in Previewer/Network Toolkit SSRF guard.** `plugins/previewer-toolkit/meta.php`'s `pt_is_safe_url()` resolves the hostname and checks the IPs against a private/loopback/metadata blocklist, then `pt_fetch_url()` fetches the URL by hostname again via `file_get_contents`/streams later. Between the check and the fetch, DNS could resolve differently (classic TOCTOU/DNS-rebind), letting a malicious host briefly point at an internal IP to slip past the guard. | **Medium** | **[Verified]** — confirmed the check-then-fetch-by-hostname pattern in `meta.php`. `network-toolkit` has a similar external-fetch surface worth the same follow-up. |
| 2 | **Session ID is not rotated on every privilege change, only on login and after 30 min idle.** `_core/Auth.php` does call `Session::regenerateId()` in `loginById()`, which covers the main login path — good. But `Session.php` only rotates otherwise after 1800s of inactivity, so any other privilege-elevation path (e.g. role change mid-session) wouldn't rotate the ID. | **Low** | **[Verified]** login path is actually covered correctly; residual risk is narrow. |
| 3 | **Password hashing uses bcrypt (cost 12), not Argon2id.** `_core/Security.php` — `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])`. Bcrypt-12 is still acceptable, but Argon2id (available in PHP 8.2) is the modern recommendation and more resistant to GPU cracking. | **Low** | **[Verified]** |
| 4 | **`Database::insert()`/`update()` interpolate column names directly into SQL** (`` `{$k}` `` from `array_keys($data)`), rather than using a whitelist. Not currently exploitable — I checked every call site and none pass raw `$_POST`/`$_GET` arrays as the data array (e.g. `admin/newsletter.php` casts and whitelists fields explicitly) — but it's a latent foot-gun: any future call that does `$db->update($table, $_POST, ...)` directly would become SQL-injectable via array keys. | **Low (latent)** | **[Verified]** — no current exploitable call site found. |
| 5 | **Un-parameterized `LIMIT`/`OFFSET` interpolation in `api/index.php`** (pages list endpoint) — `$perPage`/offset are cast to `int` and clamped (`min(50, ...)`) before interpolation, so not currently exploitable, but it's inconsistent with the rest of the codebase's prepared-statement discipline. `admin/users.php`'s pagination, by contrast, correctly passes `LIMIT ? OFFSET ?` as bound params — worth matching that pattern in `api/index.php` for consistency. | **Low (hygiene)** | **[Verified]** — not exploitable today because of the int cast/clamp, but flagged since it deviates from the safe pattern used elsewhere. |
| 6 | **47 empty `catch (Throwable $e) {}` / `catch (Exception $e) {}` blocks across 16 files** (e.g. `_core/Auth.php`, `_core/Mailer.php`, `_database/schema.php`) silently swallow failures — including some in security-relevant paths (auth, mailer). This isn't an exploit by itself, but it hides evidence of exploitation attempts and makes incident response harder. | **Low–Medium** (reliability + forensics) | **[Verified]** count confirmed via grep. |
| 7 | **CSRF coverage is inconsistent in emphasis, but core state-changing admin/account POST routes checked (users, newsletter) do call `Security::verifyCsrf()`.** No confirmed CSRF gap on a real endpoint was found on spot-check; flagged for a full sweep since the codebase is large (145 PHP files) and this wasn't exhaustively checked file-by-file. | **Info — needs full sweep** | **[Reported, not independently re-verified]** |
| 8 | **`storage/` (SQLite DB, logs, uploads, backups) must be blocked at the web-server level.** The app creates and writes to this directory but relies entirely on hosting-level rules (`.htaccess`/Apache config) to keep it unreachable over HTTP — there's no PHP-level guard. Worth explicitly confirming the production `.htaccess` denies `/storage/` (the repo's root `.htaccess` should be checked before every deploy, since a misconfigured or missing rule on shared hosting would expose the live SQLite database and logs directly). | **High if misconfigured on the live host** | **[Verified — is a hosting-config dependency, not enforced in code]** |

**Not a real issue (verified false alarm):** an earlier automated pass flagged `admin/users.php` pagination as raw SQL-string interpolation — on inspection it actually binds `LIMIT ? OFFSET ?` as parameters correctly. No fix needed there.

---

## 2. Code Quality

| # | Finding | Severity | Status |
|---|---|---|---|
| 1 | **Global error suppression in `plugins/previewer-toolkit/render.php`** (`error_reporting(0)`) combined with dozens of unguarded array-key accesses. This is exactly the class of bug already found and fixed once this session in `templates.php` (undefined `$pd_y` corrupting image output) — if error suppression is ever turned off in dev, or if a future host has `display_errors=On` with suppression not applying to fatals, the same corruption risk resurfaces. Recommend adding `??` / `isset()` guards around the flagged array accesses rather than relying solely on suppression. | **Medium** | **[Reported, not independently re-verified line-by-line — pattern is credible given the prior confirmed bug in the same plugin family]** |
| 2 | **47 empty catch blocks** (see Security #6) — also a code-quality/observability issue: failures in mailer, scheduler, and DB paths are invisible in logs. | **Medium** | **[Verified]** |
| 3 | **Mixed exit strategies**: `die()` in `_bootstrap.php`, `exit()` in `_router.php`/`sitemap.php`. Cosmetic/consistency issue, not a bug. | **Low** | **[Reported]** |
| 4 | **Duplicated analytics-logging boilerplate** repeated per-page (`blog/index.php`, `blog/tag.php`, `blog/post.php`, `newsletter.php`, etc.) instead of a single shared helper. Increases maintenance surface — a bug fix or schema change to analytics logging has to be replicated across every call site. | **Low** | **[Reported]** |
| 5 | **Plugin `_bootstrap.php` hooks are silently deactivated on any throw** (`_bootstrap.php`), with no admin-facing notification. A plugin could silently stop working after an update with no visible error anywhere except manually checking plugin status in admin. | **Low–Medium (operability)** | **[Verified — confirmed in `_bootstrap.php`]** |

---

## 3. SEO & Metadata

| # | Finding | Severity | Status |
|---|---|---|---|
| 1 | **Canonical tags, OG/Twitter cards, and JSON-LD structured data are implemented broadly and correctly** via `_core/Seo.php` + `layout.php` (WebSite/Organization/BlogPosting/FAQPage/SoftwareApplication schemas). This is a strength, not a gap. | ✅ Good | **[Reported]** |
| 2 | **Sitemap and robots.txt are correctly scoped** — `sitemap.php` includes blog posts, CMS pages, active plugins, and user profiles while excluding admin/auth; `robots.php` disallows `/admin/`, `/api/`, `/storage/`, `/logout`, `/account/`. | ✅ Good | **[Reported]** |
| 3 | **Some plugin tool pages use a `<div class="page-title">`/`header-hero-title` instead of an `<h1>`** for the main page heading (e.g. `plugins/json-tools/index.php`, `plugins/encoding-toolkit/index.php`). This weakens on-page keyword signal for exactly the pages meant to rank for tool-specific search terms ("JSON formatter", "base64 encoder", etc.). | **Medium (SEO)** | **[Reported]** — worth a sweep of all plugin `index.php` headers to confirm which ones lack a real `<h1>`. |
| 4 | **Titles/descriptions fall back to a global site tagline when a page has no explicit override**, risking duplicate `<title>`/`<meta description>` across many pages that haven't been given unique SEO copy in Admin → SEO. Not a code bug, but a content-completeness gap — worth an Admin → SEO pass to fill in unique titles/descriptions for pages currently relying on the fallback. | **Medium (content)** | **[Reported]** |

---

## 4. Performance

| # | Finding | Severity | Status |
|---|---|---|---|
| 1 | **Zero `CREATE INDEX` statements anywhere in `_database/schema.php`.** Every table relies on its implicit primary-key index only. Tables like `logs`, `analytics_events`, `notifications`, `blog_posts` will do full table scans on every filtered/sorted query (`WHERE user_id=`, `WHERE status=`, `ORDER BY created_at`) as they grow — this is the single biggest performance risk in the codebase. | **High (will worsen with data growth)** | **[Verified]** — confirmed 0 matches for `CREATE INDEX` in schema.php. |
| 2 | **`Settings::loadAll()` runs `SELECT * FROM settings` on every request** with only an in-request memory cache (no persistent/opcache-level cache), and both `_bootstrap.php` and `index.php` independently query the active-plugins list per request. Neither is expensive individually today, but both add avoidable DB round-trips to *every single page load*, including static/marketing pages. | **Medium** | **[Verified]** — confirmed the per-request plugin fetches in `index.php` and `_core/Plugin.php`; settings loading pattern matches the report. |
| 3 | **`admin/backup.php` iterates every table and does a full `SELECT *` per table in a loop** when generating backups — fine at current scale, will become slow/memory-heavy as tables grow. | **Low (admin-only, infrequent)** | **[Reported, not independently re-verified]** |
| 4 | **Synchronous remote HTTP fetches in the request path** for Previewer Toolkit and Network Toolkit (site preview, WHOIS/IP lookups) block the PHP worker for the duration of the external call. Timeouts exist, but a slow/unresponsive third-party site will still tie up a worker for several seconds per request. | **Low–Medium (UX under load)** | **[Verified — confirmed synchronous `file_get_contents`/stream fetch pattern in `previewer-toolkit/meta.php`]** |
| 5 | **No filesystem cache for generated OG images** (`og-image.php`) or Previewer render output — each request regenerates the image via GD from scratch even if the same parameters were just requested. | **Low–Medium** | **[Reported, not independently re-verified]** |
| 6 | **Theme/plugin assets are loaded as many separate uncombined/unminified CSS/JS files** rather than bundled — more HTTP requests per page than necessary; static-asset cache headers depend entirely on host/web-server config rather than being set in app code. | **Low** | **[Reported]** |

---

## 5. Accessibility

| # | Finding | Severity | Status |
|---|---|---|---|
| 1 | **Logo `<img>` tags missing `alt` text** in `index.php` hero and `themes/default/templates/layout.php` main nav — screen readers announce nothing for the site logo/home link. | **Medium** | **[Reported]** |
| 2 | **Form `<label>`s missing `for`/`id` association** in the Report Issue modal (`layout.php`) and several plugin tool pages (e.g. Encoding Toolkit textareas) — labels are visually present but not programmatically linked to their inputs, so screen-reader users can't tell which label goes with which field. | **Medium** | **[Reported]** |
| 3 | **Several muted-gray text colors likely fail WCAG contrast** on their backgrounds — `assets/css/awan.css` uses `#94a3b8`/`rgba(148,163,184,0.5)` for sidebar labels, breadcrumbs, form hints, and table headers. These are common "secondary text" grays that often clock in around 2.5:1–3:1 contrast against white/light backgrounds, below the 4.5:1 WCAG AA text minimum. | **Medium** | **[Reported — recommend running an actual contrast checker against the final rendered colors before prioritizing a fix]** |
| 4 | **`<html lang="...">` is correctly set** via `$lang` in `layout.php` — no issue. | ✅ Good | **[Verified]** |
| 5 | **Decorative "floating" hero elements contain visible text without `aria-hidden="true"`**, and the footer "Back to top" link points to `href="#"` rather than using a real skip target/focus management — screen readers may announce decorative content, and "Back to top" may not reliably move keyboard focus. | **Low–Medium** | **[Reported]** |
| 6 | **Icon-only footer social links have `title` attributes but no `aria-label`** (title alone isn't reliably announced by all screen readers/isn't keyboard-accessible on touch); the theme toggle button, by contrast, does this correctly with both `aria-label` and `title`. | **Low** | **[Reported]** |
| 7 | **Inconsistent heading hierarchy on a few plugin pages** — most plugin/tool pages correctly use a single `<h1>`, but at least one (Encoding Toolkit) uses a styled `<div class="page-title">` instead, which also compounds SEO finding #3 above. | **Low–Medium** | **[Reported]** |

---

## 6. Broken Links / Dead References

**Good news: after independently re-verifying every "broken" item the first pass flagged, none of them are actually broken.** The initial automated sweep produced several false positives because it didn't account for the router's generic clean-URL fallback (`_router.php` tries `{path}.php` directly if no explicit route matches) and worked from a stale directory listing. Verified in this pass:

| Claimed issue | Verification result |
|---|---|
| `plugins/previewer-toolkit/render.php` / `meta.php` "missing" | **False positive** — both files exist (`meta.php` was in fact edited earlier this session) and are correctly wired in `_router.php`. |
| `/get-a-quote` "dead link, no route" | **False positive** — `get-a-quote.php` exists at the repo root; the router's generic `{path}.php` fallback serves it directly (no explicit alias needed). |
| `/privacy` and `/terms` "unmapped, only `/privacy-policy`/`/terms-of-service` exist" | **False positive** — `/privacy` and `/terms` resolve via the same generic `{path}.php` fallback to `privacy.php`/`terms.php`; the named aliases are just convenience duplicates, not the only paths that work. |
| `/assets/css/plugin-page.css` "missing" | **False positive** — the file exists in `assets/css/`; an `ls` output formatting artifact made it look absent during the automated pass. |

Remaining genuine (very low severity, by-design) items:
- A handful of `href="#"` placeholders exist where behavior is JS-driven (`onclick` handlers) rather than real navigation — e.g. sidebar toggle, "back to top", a couple of admin help links. These are intentional patterns, not bugs, though "back to top" specifically should ideally use `href="#top"` or JS `scrollIntoView` + focus management for better accessibility (see Accessibility #5).
- Generator-toolkit and Frontend Studio plugins output `href="#"` inside **user-generated code templates** (e.g. sample navbars/pricing tables the tool produces for the user to copy) — these are intentional placeholders in generated output, not site bugs.

**Lesson for future audits of this codebase:** always verify router "missing route" claims against `_router.php`'s generic `{path}.php` fallback (lines ~292–296) before treating a link as broken — many pages have no explicit route entry because they don't need one.

---

## Priority Action List

If you want to act on this audit, in rough priority order:

1. **Confirm `/storage/` is blocked at the web-server level on the live host** (Security #8) — this is the one item that could be a live critical exposure and can't be verified from code alone; check the production `.htaccess`/Apache config directly.
2. **Add indexes to `_database/schema.php`** for `created_at`, `status`, `user_id`, `slug` on the high-traffic tables (Performance #1) — biggest performance lever, but per your project rules this needs your explicit approval since it's a schema change.
3. **Fix the DNS-rebinding gap in `pt_is_safe_url()`/`pt_fetch_url()`** (Security #1) — re-validate the resolved IP at the moment of the actual fetch, not just before it.
4. **Add `<h1>` to the plugin pages currently using `page-title`/`header-hero-title` divs** (SEO #3 / Accessibility #7) — quick, high-value fix for both SEO and accessibility.
5. **Add `alt` text to logo images and `for`/`id` pairs on form labels** (Accessibility #1–2) — small, low-risk fixes.
6. **Sweep and log the 47 empty catch blocks**, at minimum logging via the existing `Logger` class instead of swallowing silently (Code Quality #2 / Security #6).
7. Everything else (contrast tweaks, asset bundling, backup-loop optimization, unique per-page SEO copy) is lower urgency and can be scheduled opportunistically.

None of the above changes have been made yet — this file is the audit only. Let me know which items you'd like fixed and I'll implement them (schema/index changes will need your explicit go-ahead per the project's schema-protection rule).
