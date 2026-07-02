# AWAN Tools Platform

> **A self-hosted, multi-user SaaS platform for hosting modular online utilities.**  
> Built with PHP 8.2, zero Composer dependencies, zero external frameworks. Ships with 14 plugins offering 500+ individual tools — all free, most 100% client-side.

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Tech Stack & Dependencies](#tech-stack--dependencies)
4. [Directory Structure](#directory-structure)
5. [Core Engine (`_core/`)](#core-engine-_core)
6. [Database Schema](#database-schema)
7. [Plugin System](#plugin-system)
8. [Installed Plugins](#installed-plugins)
9. [Admin Panel](#admin-panel)
10. [REST API](#rest-api)
11. [Authentication & Security](#authentication--security)
12. [Theme System](#theme-system)
13. [Blog & CMS](#blog--cms)
14. [Email & Scheduler](#email--scheduler)
15. [Configuration & Environment](#configuration--environment)
16. [Running Locally (Replit)](#running-locally-replit)
17. [Default Credentials](#default-credentials)
18. [Audit Findings](#audit-findings)
19. [Security Considerations](#security-considerations)

---

## Overview

AWAN Tools is a **single-codebase, self-hosted platform** designed to run a curated collection of independent online tools under one unified account system. A visitor can register once and use every tool on the platform; an admin can install, activate, or deactivate plugins from the admin panel without touching code.

**Key design decisions:**
- No Composer, no npm, no build step — the entire platform runs with a single `php -S` command.
- Frontend libraries are loaded from CDN only (Monaco Editor, Chart.js, TinyMCE, etc.).
- All tool logic is **client-side JavaScript** where possible, so no user data is transmitted to the server.
- The core uses the **Singleton pattern** throughout — `Database`, `Auth`, `Settings`, `Theme`, `Mailer` and others are initialized once per request in `_bootstrap.php` and injected by reference.

---

## Architecture

```
Browser request
      │
      ▼
_router.php   ← PHP built-in server router (or Apache .htaccess)
      │
      ├── Blocks /storage/, /_core/, /_database/, /_lang/
      ├── Serves static assets from /plugins/{slug}/assets/ with MIME types
      ├── Routes /plugins/{slug}/ → plugins/{slug}/index.php
      ├── Routes /admin/*, /account/*, /api/*, etc. → their PHP entry points
      └── Falls through to matching .php file (login.php, register.php, …)
            │
            ▼
      _bootstrap.php  ← Loaded by every request
            │
            ├── _config.php          (constants: DB, APP_KEY, paths)
            ├── Database::getInstance()
            ├── Session::start()
            ├── Settings::getInstance()
            ├── Auth::getInstance()
            ├── Theme::getInstance()
            ├── Lang::getInstance()
            ├── Mailer::getInstance()
            ├── Seo::getInstance()
            ├── Logger::getInstance()
            ├── Shortcode::registerDefaults()
            ├── Scheduler task seeding
            └── Active plugin _bootstrap.php files (isolated try/catch)
```

Every page (plugin, admin, public) simply does:

```php
defined('AWAN') or define('AWAN', true);
require_once __DIR__ . '/../../_bootstrap.php';
// ... page logic
```

---

## Tech Stack & Dependencies

| Layer | Technology |
|---|---|
| **Language** | PHP 8.2+ |
| **Database** | SQLite 3 (default) or MySQL/MariaDB |
| **PHP extensions** | `pdo_sqlite`, `pdo_mysql`, `mbstring`, `json`, `openssl` |
| **Frontend** | Vanilla JS + CSS3 (no React, no Vue) |
| **UI Framework** | Custom CSS design system (`assets/css/awan.css`) |
| **Code Editor** | Monaco Editor via CDN (`cdn.jsdelivr.net`) |
| **Charts** | Chart.js via CDN |
| **Rich Text** | TinyMCE + Quill via CDN |
| **Email** | PHP `mail()` built-in (optional PHPMailer in `_core/phpmailer/`) |
| **Auth 2FA** | TOTP (RFC 6238) — custom implementation in `_core/Totp.php` |
| **OAuth** | Google OAuth 2.0 — `auth/google/google.php` |
| **Icons** | Inline SVG (no icon font CDN) |
| **Package Manager** | None |
| **Build System** | None |

### CDN Libraries Loaded Per-Page (as needed)

| Library | Used In |
|---|---|
| Monaco Editor | Frontend Studio plugin, Admin code fields |
| Chart.js | Admin analytics dashboard |
| TinyMCE | Admin blog/page editor |
| Quill.js | Alternative rich text in admin |
| jsQR / qr-code-styling | Scannable Codes plugin |
| js-yaml | YAML Tools plugin |
| JSZip | GitHub Toolkit (repo zip download) |
| FileSaver.js | Various export features |
| Google Tag Manager | Optional — configured via Admin settings |
| Facebook Pixel | Optional — configured via Admin settings |
| Microsoft Clarity | Optional — configured via Admin settings |
| Google reCAPTCHA v2/v3 | Optional — configured via Admin settings |

---

## Directory Structure

```
/
├── _bootstrap.php          ← Platform bootstrap (loaded on every request)
├── _config.php             ← All constants (DB, APP_KEY, paths, roles)
├── _router.php             ← PHP built-in server router
│
├── _core/                  ← Core singleton classes
│   ├── Auth.php            ← Session auth + RBAC
│   ├── Database.php        ← PDO wrapper (SQLite + MySQL)
│   ├── Lang.php            ← i18n string loader
│   ├── Logger.php          ← DB-backed activity logger
│   ├── Mailer.php          ← Email delivery + queue
│   ├── Notifications.php   ← Admin notification system
│   ├── Plugin.php          ← Plugin lifecycle manager
│   ├── RateLimit.php       ← DB-backed rate limiter (API/stateless)
│   ├── Scheduler.php       ← Internal cron/task runner
│   ├── Security.php        ← CSRF, input sanitization, HMAC
│   ├── Seo.php             ← Meta tags, OG, JSON-LD schema
│   ├── Session.php         ← Session wrapper with CSRF token
│   ├── Settings.php        ← DB-backed key/value settings
│   ├── Shortcode.php       ← Template shortcode parser
│   ├── Theme.php           ← Theme loader + CSS variable injector
│   ├── Totp.php            ← TOTP 2FA implementation (RFC 6238)
│   └── phpmailer/          ← PHPMailer library (optional SMTP)
│
├── _database/
│   └── schema.php          ← Full DB schema + seed data (idempotent, versioned)
│
├── _lang/
│   └── en.php              ← English translations (only language currently)
│
├── admin/                  ← Admin panel (38 PHP files)
│   ├── index.php           ← Dashboard (stats, charts, notifications)
│   ├── users.php           ← User management (CRUD, roles, bans)
│   ├── plugins.php         ← Plugin installer/manager
│   ├── settings.php        ← Platform settings (8+ tabs)
│   ├── analytics.php       ← Traffic analytics with Chart.js
│   ├── blog.php            ← Blog post management
│   ├── media.php           ← Media library
│   ├── scheduler.php       ← Cron task monitor
│   ├── seo.php             ← SEO meta, sitemap, robots.txt
│   ├── theme-editor.php    ← Live CSS variable editor
│   └── …                   ← (backup, contacts, email, logs, etc.)
│
├── plugins/                ← Tool plugins
│   ├── _sdk.php            ← Plugin helper functions
│   ├── index.php           ← Public plugin listing page
│   ├── rate.php            ← Star rating endpoint
│   └── {slug}/             ← One directory per plugin
│       ├── plugin.json     ← REQUIRED manifest
│       ├── index.php       ← REQUIRED entry point
│       └── assets/         ← Optional static assets (css, js, images)
│
├── themes/
│   └── default/
│       ├── theme.json      ← Color palette + typography variables (light + dark)
│       └── templates/
│           ├── layout.php  ← Main site layout (nav, footer, dark mode)
│           └── admin.php   ← Admin layout (sidebar, header)
│
├── account/                ← Logged-in user area
│   ├── dashboard.php       ← User dashboard
│   ├── favourites.php      ← Favourited tools
│   ├── notifications.php   ← User notifications
│   ├── my-posts.php        ← User blog posts
│   ├── profile.php         ← Edit profile
│   ├── write.php           ← Write blog post
│   └── toggle-favourite.php← AJAX favourite toggle
│
├── auth/
│   └── google/
│       └── google.php      ← Google OAuth 2.0 callback
│
├── api/
│   └── index.php           ← REST API v1 (all endpoints)
│
├── blog/                   ← Public blog pages
├── assets/
│   ├── css/awan.css        ← Global platform stylesheet
│   └── js/awan.js          ← Global platform JavaScript
│
├── storage/
│   ├── database.sqlite     ← SQLite database (default)
│   ├── uploads/            ← User-uploaded media
│   ├── logs/               ← Application log files
│   ├── cache/              ← Cache files
│   └── backups/            ← Database backups
│
├── index.php               ← Homepage
├── login.php               ← Login form
├── register.php            ← Registration form
├── 2fa.php                 ← TOTP 2FA verification
├── forgot-password.php     ← Password reset request
├── reset-password.php      ← Password reset form
├── contact.php             ← Contact form
├── search.php              ← Site-wide search
├── sitemap.php             ← XML sitemap
├── robots.php              ← Dynamic robots.txt
├── feed.php                ← RSS feed
├── og-image.php            ← Dynamic OG image generator
├── cron.php                ← Scheduler trigger endpoint
├── _config.php             ← Platform constants
└── config.local.php.example← Local override template
```

---

## Core Engine (`_core/`)

### `Database.php`
PDO-based database abstraction layer. Supports **SQLite** (WAL mode + foreign keys enabled) and **MySQL** (utf8mb4). Provides a fluent API:

```php
$db->fetch(string $sql, array $params): ?array
$db->fetchAll(string $sql, array $params): array
$db->insert(string $table, array $data): string   // returns lastInsertId
$db->update(string $table, array $data, string $where, array $params): int
$db->delete(string $table, string $where, array $params): int
$db->exists(string $table, string $where, array $params): bool
$db->count(string $table, string $where, array $params): int
$db->beginTransaction() / commit() / rollback()
```

Schema auto-initializes on first request via `_database/schema.php`. Schema version is stored in `settings.schema_version`; the schema file returns early if the version already matches (fast-path).

### `Auth.php`
Singleton. Loaded from session on every request. Provides:

- `$auth->check()` — is logged in?
- `$auth->user()` / `id()` / `email()` / `username()` / `name()`
- `$auth->hasRole(string ...$roles)` — variadic RBAC check
- `$auth->isAdmin()` / `isSuperAdmin()`
- `$auth->attempt(string $emailOrUsername, string $password)` — login with rate limit
- `$auth->loginById(int $id)` — direct login (e.g. after OAuth)
- `$auth->logout()`

Roles are stored in a pivot table (`user_roles`) and loaded as an array on every request via `GROUP_CONCAT`.

### `Security.php`
Static class. Key methods:

```php
Security::csrfToken(): string         // Get (or generate) CSRF token from session
Security::csrfField(): string         // <input type="hidden" name="_csrf" …>
Security::verifyCsrf(): void          // Die 419 if token mismatch
Security::checkRateLimit(string $key, int $maxAttempts, int $window): bool
Security::sanitize(string $input): string      // trim + strip_tags
Security::sanitizeEmail(string $email): string
Security::generateToken(int $length): string   // cryptographic random hex
Security::hashToken(string $token): string     // SHA-256 hash for DB storage
```

Rate limiting is **session-based** (suitable for stateful pages). API endpoints use the DB-backed `RateLimit` class instead.

### `RateLimit.php`
Database-backed rate limiter for **stateless** contexts (API, no session). Uses an `api_rate_limits` table (key, attempts, window_start). Falls open on DB error — a database hiccup never locks out legitimate users.

### `Session.php`
Thin wrapper around PHP sessions. Manages CSRF token lifecycle, session regeneration on login, and typed accessors (`get`, `set`, `remove`, `flash`).

### `Settings.php`
DB-backed key/value store with in-memory cache. Values are loaded all-at-once on first access and cached for the request lifetime. Settings are grouped (e.g. `general`, `seo`, `branding`, `email`).

### `Mailer.php`
Uses PHP `mail()` by default. Supports:
- Direct send: `$mailer->send(string $to, string $subject, string $html)`
- Template send: `$mailer->sendTemplate(string $slug, string $to, array $vars)`
- Async queue: `$mailer->sendTemplateQueued(…)` + `$mailer->processQueue(int $limit)`
- HTML template builder: `Mailer::html(string $siteName, string $title, string $body)`

PHPMailer library is bundled in `_core/phpmailer/` for optional SMTP use.

### `Totp.php`
Full TOTP implementation (RFC 6238) without external dependencies. Generates and verifies time-based one-time passwords compatible with Google Authenticator, Authy, etc.

### `Plugin.php`
Manages the plugin lifecycle:
- `Plugin::sync(Database $db, string $slug)` — reads `plugin.json` and upserts DB record
- `Plugin::activate(Database $db, string $slug)` — runs `_install.php` hook, marks active
- `Plugin::deactivate(Database $db, string $slug)` — runs `_uninstall.php` hook
- `Plugin::getManifest(string $slug)` — reads `plugin.json` as array

### `Seo.php`
Generates all `<head>` meta tags, OpenGraph tags, Twitter Card tags, JSON-LD schema, and canonical URLs. Driven by both admin settings and per-plugin `meta` fields in `plugin.json`.

### `Scheduler.php`
Internal cron-like system. Tasks are rows in `scheduled_tasks` with `interval_seconds`, `next_run`, and `status`. The `/cron.php` endpoint (or `POST /api/v1/cron/process-emails`) triggers due tasks.

---

## Database Schema

Schema version: **2.7** (defined in `_database/schema.php`)

| Table | Purpose |
|---|---|
| `users` | User accounts (id, username, email, password hash, avatar, bio, status) |
| `roles` | Role definitions (super_admin, admin, user) |
| `user_roles` | Many-to-many pivot: users ↔ roles |
| `settings` | Platform key/value settings (grouped) |
| `plugins` | Plugin registry (slug, name, version, status, manifest JSON) |
| `logs` | Activity/error log (level, message, context, user_id, ip, url) |
| `theme_overrides` | Per-theme CSS variable overrides (stored by admin theme editor) |
| `pages` | CMS pages (title, slug, content, status, author_id) |
| `scheduled_tasks` | Internal cron tasks (slug, interval, next_run, last_run, status) |
| `analytics_events` | Page view + event tracking (path, user_id, plugin_slug, ip) |
| `media` | Media library (filename, path, url, mime, dimensions, folder) |
| `blog_posts` | Blog articles (title, slug, content, cover, status, featured) |
| `blog_categories` | Blog categories (name, slug, color) |
| `blog_post_categories` | Many-to-many: posts ↔ categories |
| `blog_tags` | Blog tags |
| `blog_post_tags` | Many-to-many: posts ↔ tags |
| `blog_comments` | Blog comments (threaded, status: pending/approved/spam) |
| `quote_requests` | "Get a Quote" form submissions |
| `tool_requests` | User-submitted tool/plugin requests |
| `issue_reports` | Bug reports from users |
| `newsletter_subscribers` | Newsletter list (email, name, status, unsubscribe_token) |
| `contact_messages` | Contact form submissions |
| `notifications` | Admin dashboard notifications |
| `api_rate_limits` | DB-backed rate limit counters for API endpoints |
| `email_queue` | Async email queue (to, subject, html, status, attempts) |
| `email_templates` | Admin-editable transactional email templates |
| `faq_items` | FAQ entries (question, answer, category, order) |
| `testimonials` | User testimonials |
| `menus` / `menu_items` | Navigation menu builder |
| `favourites` | User-favourited plugins |
| `plugin_ratings` | Star ratings per plugin per user |

---

## Plugin System

### How Plugins Work

Each plugin is a self-contained directory under `/plugins/{slug}/`:

```
plugins/my-tool/
├── plugin.json       ← Manifest (required)
├── index.php         ← Entry point (required)
├── _install.php      ← Runs on activation (optional)
├── _uninstall.php    ← Runs on deactivation (optional)
├── _bootstrap.php    ← Runs on every request when active (optional)
└── assets/           ← Static assets (served by router)
    ├── style.css
    └── script.js
```

The router serves plugin pages at `/plugins/{slug}/` and maps them to `plugins/{slug}/index.php`. Sub-pages can be served through query params or by including other files inside `index.php`. Direct `.php` URL access inside plugin directories is **blocked (403)** by the router.

### Plugin Manifest (`plugin.json`)

```json
{
    "name": "My Tool",
    "slug": "my-tool",
    "version": "1.0.0",
    "description": "Short description (max ~120 chars)",
    "author": "Name",
    "license": "MIT",
    "min_php": "8.0",
    "offered": 12,
    "icon": "<svg ...>...</svg>",
    "requires_login": false,
    "stores_user_data": false,
    "dashboard_enabled": false,
    "analytics_enabled": true,
    "permissions": [],
    "categories": ["Developer Tools"],
    "keywords": ["convert", "tool"],
    "tags": ["converter", "free"],
    "meta": {
        "title": "My Tool — AWAN Tools",
        "description": "SEO description",
        "og_image": "",
        "canonical": "/plugins/my-tool/"
    }
}
```

### Plugin SDK (`plugins/_sdk.php`)

Auto-included before every plugin. Key helpers:

```php
plugin_requires_login(string $slug): void        // Redirect to login if not authenticated
plugin_url(string $slug, string $path): string   // Build URL: /plugins/{slug}/{path}
plugin_asset(string $slug, string $asset): string// URL: /plugins/{slug}/assets/{asset}
plugin_view(string $slug, string $view, array $vars): string  // Render a view file
plugin_render(string $title, string $content, array $opts): void // Render full page
plugin_manifest(string $slug): array             // Read plugin.json as array
plugin_db_prefix(string $slug): string           // Table prefix: plg_{slug}_
```

---

## Installed Plugins

| Plugin | Slug | Tools | Description |
|---|---|---|---|
| **Time Toolkit** | `time-toolkit` | 120+ | Universal date/time workbench — timestamp converter, timezone world clock, date arithmetic, age calc, astronomy, cron parser, Hijri calendar, dev snippets |
| **Network Toolkit** | `network-toolkit` | 82+ | DNS lookup, WHOIS, IP calculator, subnet tools, port scanner, HTTP headers, SSL checker, ping, traceroute, BGP tools |
| **Developer Generator Toolkit** | `dev-generator-toolkit` | 100+ | UUID/GUID/NanoID, passwords, fake data, lorem ipsum, SQL, JSON, CSV, XML, HTML/CSS/JS snippets, regex, slugs, colors, boilerplate, config files |
| **Security Toolkit** | `security-toolkit` | 22 | MD5/SHA-1/224/256/384/512/CRC32/RIPEMD-160/Whirlpool hashes, password generator + strength checker, JWT encode/decode/validate, HMAC, file hash calculator |
| **Encoding Toolkit** | `encoding-toolkit` | 31 | Base64, URL encode/decode, ASCII, Unicode, UTF-8/16, Binary, Hex, Octal, URL parser, query string tools |
| **JSON Tools** | `json-tools` | 19 | Format, validate, minify, diff, sort keys, flatten/unflatten, JSONPath query, escape, JSON↔YAML/XML/CSV |
| **XML Tools** | `xml-tools` | 10 | Format, minify, validate, view, escape/unescape, XML↔CSV, diff two XML documents |
| **YAML Tools** | `yaml-tools` | 12 | Format, validate, view, sort keys, flatten, diff, YAML↔JSON/CSV |
| **GitHub Toolkit** | `github-toolkit` | 36 | Download repos/files/folders/releases/gists, profile analyzer, repo analyzer, badge/shield generator, README viewer, branch/tag/commit explorer |
| **Frontend Studio** | `frontend-studio` | 21 | Browser-based IDE (Monaco Editor) for HTML/CSS/JS/SVG/JSON/Markdown with live preview, project management, AI assistant, themes, command palette |
| **Internet Speed Test** | `internet-speed-test` | 15 | Download/upload/ping/jitter/packet loss, DNS timing, TLS handshake, HTTP response analysis, ISP quality score, JSON/PDF/TXT/CSV export |
| **Scannable Codes** | `scannable-codes` | 15 | QR code generator (with logo + custom colors/dots), barcode generator (Code 128, EAN-13, UPC-A, PDF417, DataMatrix, Aztec), camera + image scanner |
| **Regex Toolkit** | `regex-toolkit` | — | Regex builder, tester, and reference |
| **Previewer Toolkit** | `previewer-toolkit` | — | Website/URL preview and screenshot tools |

**Total: 500+ individual tools across 14 plugins**

---

## Admin Panel

Accessed at `/admin/`. Requires `admin` or `super_admin` role.

| File | Feature |
|---|---|
| `index.php` | Dashboard: user count, page views, plugin stats, recent logs, notifications |
| `users.php` | User CRUD, role assignment, status (active/banned), search/filter |
| `plugins.php` | One-click install/activate/deactivate, sync from filesystem |
| `settings.php` | 8+ tab settings: General, Branding, SEO, Email, Auth, Analytics, API, Scheduler |
| `analytics.php` | Chart.js visualizations of page views, top pages, plugin usage |
| `blog.php` | Blog post CRUD (TinyMCE editor, categories, tags, featured, scheduling) |
| `comments.php` | Blog comment moderation (approve/spam/delete) |
| `pages.php` | CMS page CRUD (static pages with slug-based routing) |
| `media.php` | Media library (upload, organize by folder, alt text, MIME filtering) |
| `theme-editor.php` | Live CSS variable editor (colors, fonts, spacing — stored in DB) |
| `themes.php` | Theme switcher |
| `scheduler.php` | Cron task list, manual trigger, last-run and status view |
| `seo.php` | SEO settings: robots.txt rules, sitemap config, OG image generator settings |
| `email-templates.php` | Edit transactional email templates (welcome, reset, newsletter) |
| `email-queue.php` | View and retry queued outbound emails |
| `email-logs.php` | Sent email log |
| `newsletter.php` | Subscriber list, export, bulk actions |
| `contacts.php` | Contact form inbox |
| `quotes.php` | "Get a Quote" request inbox |
| `tool-requests.php` | User plugin/tool request inbox |
| `reports.php` | Issue report inbox |
| `logs.php` | Application event log (filter by level, search) |
| `backup.php` | Download SQLite database backup |
| `migrate-db.php` | Run schema migration |
| `notifications.php` | Admin notification center |
| `search.php` | Admin-side search across users, posts, plugins |
| `system.php` | PHP info, disk usage, server environment |
| `faq.php` | FAQ management |
| `testimonials.php` | Testimonial management |
| `menus.php` | Navigation menu builder |
| `shortcodes.php` | Shortcode reference |
| `homepage-sections.php` | Edit homepage hero text, CTAs, section content |
| `languages.php` | i18n / translation management |
| `files.php` | File browser for storage/uploads |
| `profile.php` | Admin's own profile edit |
| `setup.php` | First-run setup wizard |

---

## REST API

Base URL: `/api/v1/`  
Authentication: `Authorization: Bearer {api_key}` header (or deprecated `?api_key=` query param)

### Public Endpoints (no key required)

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/health` | Health check — returns DB status, version, timestamp |
| `GET` | `/api/` | API index — lists all endpoints |
| `GET` | `/api/v1/pages` | List published CMS pages (paginated) |
| `GET` | `/api/v1/pages/{slug}` | Get a single published page by slug |
| `GET` | `/api/v1/users/{username}` | Public user profile |
| `GET` | `/api/v1/search?q=` | Search plugins (by name, description, keywords) + blog posts |
| `POST` | `/api/v1/auth/login` | Authenticate → returns HMAC token |
| `GET` | `/api/v1/auth/me` | Verify HMAC token → returns user info |
| `POST` | `/api/v1/newsletter` | Subscribe to newsletter (rate-limited, honeypot protected) |

**Rate limits:** Login — 10 attempts / 15 min / IP. Newsletter — 3 attempts / hour / IP.

### Authenticated Endpoints (API key required)

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/media` | List media library (filterable by type, paginated) |
| `GET` | `/api/v1/analytics` | Analytics summary (total/today/week views, top pages) |
| `GET` | `/api/v1/plugins` | List active plugins |
| `GET` | `/api/v1/settings` | Public settings subset |
| `POST` | `/api/v1/cron/process-emails` | Trigger email queue processing (requires API key + cron secret) |

### API Token Format

Login returns an HMAC-signed token: `{userId}:{expiry}:{hmac_sha256}`. Tokens expire in 1 hour. Verification is stateless (no DB lookup needed for validation).

---

## Authentication & Security

### Authentication Flow

1. **Standard login** — email or username + password (bcrypt). Rate-limited to 5 attempts / 15 min per IP.
2. **Google OAuth** — via `auth/google/google.php`. Requires Google OAuth client ID/secret in admin settings.
3. **2FA (TOTP)** — user enables via `account/profile.php`. On login, redirected to `2fa.php` to enter a 6-digit code. Compatible with Google Authenticator, Authy, Bitwarden.
4. **Password reset** — email link with signed token (SHA-256 hash stored in DB, 1-hour expiry).
5. **Email verification** — optional. Token-based, sent on registration.

### Security Measures

| Measure | Implementation |
|---|---|
| **CSRF Protection** | Every POST form requires `_csrf` token. Validated via `Security::verifyCsrf()`. Also accepted via `X-CSRF-Token` header for AJAX. |
| **Password Hashing** | `password_hash($password, PASSWORD_BCRYPT)` |
| **Session Security** | Session regenerated on login. CSRF token stored in session. |
| **Rate Limiting (sessions)** | Login, contact, newsletter forms — session-based counter. |
| **Rate Limiting (API)** | DB-backed `RateLimit` class — stateless, per-IP keyed. |
| **Input Sanitization** | `Security::sanitize()` (trim + strip_tags) and `filter_var()` for emails/URLs. |
| **Output Escaping** | Global `e()` helper = `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`. |
| **SQL Injection** | All queries use PDO prepared statements. No string interpolation for user data. |
| **HMAC Signing** | API tokens, password reset links, and email unsubscribe tokens use `hash_hmac('sha256', …, APP_KEY)`. |
| **Honeypots** | Newsletter and contact forms include a hidden `website` field. Bots that fill it get a silent success (not stored). |
| **Security Headers** | `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`, full `Content-Security-Policy`. |
| **Directory Protection** | Router blocks direct access to `/_core/`, `/_database/`, `/_lang/`, `/storage/` (except `/storage/uploads/`). |
| **Plugin Isolation** | Active plugin `_bootstrap.php` files are loaded inside individual `try/catch` blocks. A crashing plugin is automatically deactivated. |

---

## Theme System

The platform supports multiple themes. Each theme lives in `themes/{slug}/` with:

- `theme.json` — color palette and typography variables for **light** and **dark** mode
- `templates/layout.php` — main site layout (navigation, footer, dark-mode toggle)
- `templates/admin.php` — admin layout (sidebar, header, notification bell)

**The default theme** defines 40+ CSS custom properties (colors, shadows, border-radius, font sizes). The `Theme.php` core class merges theme defaults with any per-variable overrides stored in the `theme_overrides` DB table (editable live via the Admin Theme Editor).

Dark mode is toggled client-side via `localStorage` and applied by swapping a `data-theme="dark"` attribute on `<html>`.

---

## Blog & CMS

### Blog Features
- Posts with title, slug, excerpt, full content (TinyMCE), cover image, meta description, OG image
- Category and tag taxonomy (many-to-many)
- Post statuses: `draft`, `published`, `scheduled`
- Featured posts flag
- View counter
- Threaded comments with moderation (pending / approved / spam)
- RSS feed at `/feed.php`
- Dynamic OG image generator at `/og-image.php?slug={slug}` — renders a card image using GD

### CMS Pages
- Static pages with custom slugs, routed dynamically
- Rich content editor (TinyMCE)
- SEO fields (title, description, canonical)

---

## Email & Scheduler

### Email
Emails are sent via PHP `mail()`. Admin can configure:
- From address/name and reply-to
- Optional async queue (emails stored in `email_queue`, processed by cron)
- Admin-editable templates for: welcome, email verification, password reset, newsletter welcome, quote request, etc.

### Scheduler
Tasks are registered in `scheduled_tasks`. Built-in tasks:

| Task Slug | Interval | Purpose |
|---|---|---|
| `core_email_queue` | 5 minutes | Process async email queue |
| `core_log_cleanup` | Daily | Remove old log entries |
| `core_notification_cleanup` | Daily | Remove expired notifications |
| `core_analytics_prune` | Weekly | Remove old analytics events |
| `core_backup_cleanup` | Weekly | Remove old database backups |

Tasks are triggered by hitting `/cron.php` or `POST /api/v1/cron/process-emails` from a real cron job or uptime monitor.

---

## Configuration & Environment

### Environment Variables (or `config.local.php`)

| Variable | Default | Description |
|---|---|---|
| `AWAN_ENV` | `production` | Set to `development` to enable debug mode + error display |
| `APP_KEY` | (hardcoded default) | **Change this before deploying** — used for CSRF and HMAC signing |
| `DB_DRIVER` | `sqlite` | `sqlite` or `mysql` |
| `DB_HOST` | `127.0.0.1` | MySQL host |
| `DB_PORT` | `3306` | MySQL port |
| `DB_NAME` | `awan` | MySQL database name |
| `DB_USER` | `root` | MySQL username |
| `DB_PASS` | `` | MySQL password |

### Local Config File (`config.local.php`)

Copy `config.local.php.example` to `config.local.php` (git-ignored):

```php
<?php
define('AWAN_ENV_LOCAL', 'development');
define('APP_KEY_LOCAL', 'your-random-secret-key-here');

// Optional: switch to MySQL
// define('DB_DRIVER_LOCAL', 'mysql');
// define('DB_HOST_LOCAL',   '127.0.0.1');
// define('DB_NAME_LOCAL',   'awan_db');
// define('DB_USER_LOCAL',   'root');
// define('DB_PASS_LOCAL',   'password');
```

---

## Running Locally (Replit)

The project is pre-configured to run on Replit with PHP 8.2:

```bash
php -S 0.0.0.0:5000 _router.php
```

This starts PHP's built-in development server on port 5000 with `_router.php` as the entry point. The router handles URL rewriting, static assets, security blocks, and dispatches all requests.

**Required storage directories** are created automatically by `_bootstrap.php`:
```
storage/
storage/logs/
storage/uploads/
storage/cache/
storage/backups/
```

**Database** is auto-initialized on first request. SQLite file is created at `storage/database.sqlite`. The schema runs, default roles are seeded, a default super-admin account is created, and default settings are populated.

### For Apache/Nginx (Production)

Add a `.htaccess` or server block to route all requests through `_router.php` (or configure `FallbackResource /_router.php`). The router is compatible with both PHP built-in server and Apache.

---

## Default Credentials

Created automatically on first boot if no `admin` user exists:

| Field | Value |
|---|---|
| Username | `admin` |
| Email | `admin@localhost` |
| Password | `Admin@1234` |
| Role | `super_admin` |

> **⚠️ Change this password immediately after first login.** Navigate to Admin → Profile or Admin → Users.

---

## Audit Findings

### Strengths

1. **Zero-dependency PHP** — no Composer, no npm, no build step. Deployable on any shared host with PHP 8.2.
2. **Client-side tool architecture** — the majority of tool logic runs in the browser (JavaScript). No user data is sent to the server. Privacy-respecting by design.
3. **Robust plugin isolation** — plugin crashes are caught individually and auto-deactivate the offending plugin; the platform continues running.
4. **Layered security** — CSRF on all POST forms, prepared statements everywhere, HMAC-signed tokens, bcrypt passwords, security headers, rate limiting on all sensitive endpoints.
5. **Idempotent schema** — `CREATE TABLE IF NOT EXISTS` + schema versioning means schema migrations are safe to run on every boot.
6. **Comprehensive admin panel** — 38 admin pages covering every aspect of the platform without needing external CMS tools.
7. **API with health endpoint** — `/api/health` is public, making it easy to monitor with uptime services.
8. **Honeypot spam protection** — forms use a hidden `website` field. No CAPTCHA required (optional reCAPTCHA available).
9. **Theming without file edits** — CSS variables stored in the DB, editable live from the admin theme editor.
10. **Dynamic OG image generation** — blog posts and plugins get auto-generated social sharing images using PHP GD.

### Concerns & Recommendations

| Priority | Issue | Recommendation |
|---|---|---|
| 🔴 High | **Default `APP_KEY`** is committed to the repo. If deployed without changing it, CSRF tokens and HMAC-signed links are predictable. | Generate a random key and set via env var or `config.local.php` before any public deployment. |
| 🔴 High | **Scheduler depends on external trigger.** The email queue and log cleanup never run unless something hits `/cron.php` or the API cron endpoint. | Set up an external cron job (cPanel, GitHub Actions, uptime monitor) to call the cron endpoint every 5 minutes. |
| 🟡 Medium | **Rate limiting is session-based for forms** — an attacker can bypass by clearing cookies. | For sensitive forms (login, register), consider switching to the DB-backed `RateLimit` class (already used for the API). |
| 🟡 Medium | **IP address logging** without anonymization. The `analytics_events` and `logs` tables store raw IPs. | Consider truncating the last octet for GDPR compliance: `preg_replace('/\.\d+$/', '.0', $ip)`. |
| 🟡 Medium | **`Network Toolkit`** uses PHP-side network calls (DNS, HTTP requests to external servers). Requires `allow_url_fopen = On` and may be restricted on some shared hosts. | Document this requirement clearly; add a health check in the admin system panel. |
| 🟡 Medium | **No output buffering / flash of unstyled content** — pages begin rendering before all includes finish. Minor but visible on slow connections. | Add `ob_start()` at the top of each entry page. |
| 🟢 Low | **`og-image.php` uses PHP GD** — GD may not be available on all PHP installs. | Add a `extension_loaded('gd')` check and graceful fallback. |
| 🟢 Low | **Legacy `api_key` URL param** is still accepted (though deprecated with `X-Deprecated` header). | Remove it by the stated sunset date (2027-01-01) to prevent key leakage in server access logs. |
| 🟢 Low | **Single theme** (`default`) is currently the only available theme. The theme switcher UI exists but there is nothing to switch to. | Document the theme creation process or ship a second theme as an example. |
| 🟢 Low | **No automated tests.** | Add PHPUnit tests for the core classes (`Auth`, `Database`, `Security`) to prevent regressions as the platform grows. |

---

## Security Considerations

- **Change `APP_KEY`** before deploying. The default key in `_config.php` is public.
- **Change the default admin password** immediately after first boot.
- **Use HTTPS** in production. The platform auto-detects HTTPS for URL generation but does not enforce it.
- **Set `AWAN_ENV=production`** (the default) to suppress error output. Never run `development` mode on a public server.
- **Restrict `/storage/`** — the router blocks direct access to `/storage/` except `/storage/uploads/`. On Apache, also add an `.htaccess` to the `storage/` directory as a defense-in-depth measure.
- **Regular backups** — use Admin → Backup to download the SQLite database. For MySQL, use `mysqldump`.
- **Review CSP** — the default Content-Security-Policy includes `unsafe-inline` and `unsafe-eval` (required by Monaco Editor and inline admin scripts). Tighten this if the admin panel is not used.

---

*AWAN Tools Platform — version 1.0.0 — Schema version 2.7*  
*Author: Shamrouz Awan — [shamrouzawan.com](https://shamrouzawan.com)*  
*License: MIT*
