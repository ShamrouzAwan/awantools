# Awan Tools — Plugin System

**Repository:** [github.com/ShamrouzAwan/awan-tools-plugins](https://github.com/ShamrouzAwan/awan-tools-plugins)  
**Platform:** [awantools.site](https://awantools.site)  
**Developer:** Shamrouz Awan

---

## Overview

Awan Tools is a self-hosted, multi-user SaaS platform built with PHP 8.2 (no Composer, no framework). Its plugin system lets developers ship independent tools — calculators, productivity utilities, data converters, finance helpers, and more — that live inside the platform and inherit its account system, UI components, and analytics automatically.

Every plugin is a self-contained directory with a `plugin.json` manifest and one or more PHP pages. The platform handles routing, authentication gating, analytics tracking, favouriting, ratings, and admin management so plugin authors can focus entirely on the tool itself.

---

## Directory Structure

```
plugins/
├── README.md                  ← You are here
├── _sdk.php                   ← Plugin SDK — helper functions for all plugins
├── index.php                  ← Public plugin listing page (auto-populated)
├── rate.php                   ← Star rating endpoint (POST, CSRF-protected)
│
└── {plugin-slug}/             ← One directory per plugin
    ├── plugin.json            ← REQUIRED — manifest file
    ├── index.php              ← REQUIRED — plugin entry point (main page)
    ├── [any-other].php        ← Optional sub-pages
    └── assets/                ← Optional static assets (css, js, images)
        ├── style.css
        └── script.js
```

> **Rule:** Plugin files must never be accessed directly by the web server. The platform router enforces a 403 for any `GET /plugins/{slug}/anything` that resolves to a raw `.php` file outside of `index.php`. Always use `plugin_url()` to build links within your plugin.

---

## plugin.json — Manifest Reference

Every plugin must have a `plugin.json` at its root. This is the single source of truth for all platform features (listing, search, admin panel, analytics).

```json
{
  "name": "My Tool",
  "slug": "my-tool",
  "version": "1.0.0",
  "description": "A short description shown on the plugin card (max ~120 chars).",
  "author": "Your Name",
  "author_url": "https://yourwebsite.com",
  "homepage": "https://awantools.site/plugins/my-tool/",
  "license": "MIT",
  "min_php": "8.0",

  "icon": "<svg ...>...</svg>",

  "offered": 1,

  "requires_login": false,
  "stores_user_data": false,
  "dashboard_enabled": false,
  "analytics_enabled": true,

  "categories": ["Utility"],
  "keywords": ["convert", "transform", "text"],
  "tags": ["text", "converter", "utility", "tool", "free"],

  "permissions": [],

  "meta": {
    "title": "My Tool — Awan Tools",
    "description": "SEO description for the plugin landing page.",
    "og_image": "",
    "twitter_card": "summary_large_image",
    "canonical": "https://awantools.site/plugins/my-tool/"
  }
}
```

### Field Reference

| Field | Type | Required | Description |
|---|---|---|---|
| `name` | string | Yes | Display name shown in the plugin card and page title |
| `slug` | string | Yes | URL-safe identifier. Must match the directory name exactly (e.g. `json-tools` → `plugins/json-tools/`) |
| `version` | string | Yes | Semantic version (`1.0.0`) |
| `description` | string | Yes | Short description — shown on the listing card and plugin page header |
| `author` | string | Yes | Author name |
| `author_url` | string | No | Author's website URL |
| `homepage` | string | No | Full canonical URL of the plugin page |
| `license` | string | No | License identifier (`MIT`, `GPL-3.0`, `Proprietary`, etc.) |
| `min_php` | string | No | Minimum PHP version required (`8.0`, `8.1`, etc.) |
| `icon` | string | Yes | Raw SVG markup. Displayed at 48×48 px on cards; 64×64 on plugin page. Keep it simple, single colour, stroke-based. |
| `offered` | int | Yes | How many distinct tools this plugin provides (used in platform-wide stats). Minimum: `1`. |
| `requires_login` | bool | No | If `true`, unauthenticated users see a "Login Required" badge and cannot open the tool |
| `stores_user_data` | bool | No | Set `true` if the plugin creates rows in its own table(s) per user |
| `dashboard_enabled` | bool | No | If `true`, a widget appears on the user's account dashboard |
| `analytics_enabled` | bool | No | If `true`, page views are tracked in the platform analytics table |
| `categories` | array | Yes | At least one category string. Used for the category filter on the listing page. Recommended values: `Utility`, `Developer`, `Finance`, `Productivity`, `Text`, `Media`, `Data`, `Security`, `Science`. |
| `keywords` | array | No | Short search keywords (legacy, kept for backwards compat) |
| `tags` | array | No | Longer list of search terms. Both `keywords` and `tags` are searched. Include 20–40 relevant tags. |
| `permissions` | array | No | Declared data access. Values: `read_own_data`, `write_own_data`, `read_all_data` (admin only). Currently informational — enforcement is up to the plugin. |
| `meta.title` | string | No | `<title>` for the plugin landing page. Falls back to `{name} — {site_name}` |
| `meta.description` | string | No | `<meta name="description">` for the plugin page |
| `meta.og_image` | string | No | OpenGraph image URL for the plugin page |
| `meta.twitter_card` | string | No | Twitter card type. Default: `summary_large_image` |
| `meta.canonical` | string | No | Canonical URL for the plugin page |

---

## Plugin SDK (`_sdk.php`)

Every plugin must include the SDK at the very top of `index.php`:

```php
<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../plugins/_sdk.php';
```

> **Never** `require_once __DIR__ . '/../../_bootstrap.php'` yourself. The router already ran bootstrap. Calling it again wastes memory and may cause duplicate class definitions.

### Available SDK Functions

#### Rendering

```php
plugin_render(string $title, string $content, array $opts = []): void
```
Wraps your HTML in the platform's themed layout (header, nav, footer, dark mode). Always use this instead of rolling your own HTML boilerplate.

```php
// $opts keys:
// 'description' => string  — meta description for this page
// 'og_image'    => string  — OG image URL
```

#### Routing & Redirects

```php
plugin_url(string $slug, string $path = ''): string
// Returns the absolute URL to a path inside your plugin
// e.g. plugin_url('my-tool', 'results') → https://site.com/plugins/my-tool/results

plugin_redirect(string $slug, string $path = ''): void
// Redirects to a URL inside your plugin and exits
```

#### Flash Messages

```php
plugin_flash_success(string $message): void
plugin_flash_danger(string $message):  void
plugin_flash_info(string $message):    void
// Stores a one-time flash message shown on next page render
```

#### Input Handling

```php
plugin_input(string $key, mixed $default = '', string $method = 'POST'): string
// Sanitized input from $_POST or $_GET
// Automatically trims whitespace and strips HTML tags
```

#### Database Tables

```php
plugin_table(string $slug, string $table): string
// Returns the full prefixed table name for your plugin
// e.g. plugin_table('notes', 'items') → 'plg_notes_items'
```

Table prefix format: `plg_{slug}_{table}`

Always use `plugin_table()` when referencing your own tables. This ensures your tables never collide with platform tables or other plugins.

#### Related Plugins

```php
plugin_related(string $slug, int $limit = 4): array
// Returns an array of active plugins excluding $slug

plugin_related_html(string $slug): string
// Returns a ready-to-render HTML card grid of related plugins
// Paste directly into your plugin's output
```

---

## Accessing Platform Globals

The bootstrap globals are always available inside plugin files. Declare them at the top of your functions:

```php
global $db, $auth, $settings, $mailer, $logger, $seo;
```

| Global | Type | Purpose |
|---|---|---|
| `$db` | `Database` | PDO wrapper — `fetch()`, `fetchAll()`, `insert()`, `update()`, `delete()`, `exists()`, `count()`, `query()` |
| `$auth` | `Auth` | `$auth->check()` (logged in?), `$auth->id()`, `$auth->user()`, `$auth->isAdmin()`, `$auth->isSuperAdmin()` |
| `$settings` | `Settings` | `$settings->get('key', 'default')`, `$settings->set('key', 'value')` |
| `$mailer` | `Mailer` | `$mailer->sendTemplate('slug', 'to@email.com', ['var' => 'value'])` |
| `$logger` | `Logger` | `$logger->info('msg')`, `$logger->warning('msg')`, `$logger->error('msg', ['context'])` |
| `$seo` | `Seo` | `$seo->render(['title' => '...', 'description' => '...'])` |

Helper functions (always available, no `global` needed):

```php
siteUrl('/path')        // Full absolute URL
e($string)              // htmlspecialchars() — always escape output
fdate($timestamp)       // Formatted date using platform date_format setting
redirect('/url')        // Header redirect + exit
requireLogin()          // Redirects to /login if not authenticated
requireAdmin()          // Redirects if not admin
Security::csrfField()   // Outputs <input type="hidden" name="_csrf" value="...">
Security::verifyCsrf()  // Verifies POST _csrf token — call at top of all POST handlers
Security::sanitize($v)  // Basic string sanitize
```

---

## Database: Plugin Tables

If your plugin needs to persist data, create your tables inside your plugin's `index.php` using a one-time idempotent migration pattern:

```php
global $db;

// Run once per request (cheap: just a table existence check)
static $tableReady = false;
if (!$tableReady) {
    $isSqlite = $db->driver() === 'sqlite';
    $AI = $isSqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
    $TS = $isSqlite ? 'TEXT' : 'DATETIME';

    $db->query("CREATE TABLE IF NOT EXISTS " . plugin_table('my-tool', 'items') . " (
        id         {$AI},
        user_id    INTEGER NOT NULL,
        content    TEXT    NOT NULL,
        created_at {$TS}   DEFAULT NULL
    )");
    $tableReady = true;
}
```

Rules:
- Always `CREATE TABLE IF NOT EXISTS` — never assume the table exists.
- Always prefix with `plugin_table()` — never hardcode the table name.
- Both SQLite and MySQL must be supported. Use `$db->driver() === 'sqlite'` to branch column types.
- Never use `AUTO_INCREMENT` directly — use the `$AI` pattern above.
- Store `user_id` for any user-specific data so rows can be scoped cleanly.

---

## Authentication & Access Control

If your plugin requires login, set `"requires_login": true` in `plugin.json` AND call `requireLogin()` at the top of your `index.php`:

```php
<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../plugins/_sdk.php';
requireLogin();
```

`requireLogin()` redirects unauthenticated users to `/login?next=/plugins/my-tool/` and exits. The platform's login page respects the `?next=` parameter and redirects back after login.

For admin-only tools:

```php
requireAdmin();       // Redirects non-admins
requireSuperAdmin();  // Redirects anyone without the super_admin role
```

---

## Forms & CSRF

All POST forms inside plugins must include a CSRF token:

```php
<form method="POST">
    <?= Security::csrfField() ?>
    <!-- your fields -->
    <button type="submit">Submit</button>
</form>
```

And verify at the top of your POST handler:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    // ... handle form
}
```

`Security::verifyCsrf()` checks `$_POST['_csrf']`. It outputs a 419 error page and exits on failure. Never skip this on forms that mutate data.

---

## Complete Example Plugin

Here is a minimal but complete plugin — a word counter:

**`plugins/word-counter/plugin.json`**
```json
{
  "name": "Word Counter",
  "slug": "word-counter",
  "version": "1.0.0",
  "description": "Count words, characters, sentences and reading time in any text.",
  "author": "Shamrouz Awan",
  "author_url": "https://shamrouzawan.com",
  "icon": "<svg width='24' height='24' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'/><polyline points='14 2 14 8 20 8'/><line x1='16' y1='13' x2='8' y2='13'/><line x1='16' y1='17' x2='8' y2='17'/><line x1='10' y1='9' x2='8' y2='9'/></svg>",
  "offered": 1,
  "requires_login": false,
  "stores_user_data": false,
  "analytics_enabled": true,
  "categories": ["Text", "Utility"],
  "tags": ["word", "counter", "character", "text", "writing", "readability"],
  "permissions": []
}
```

**`plugins/word-counter/index.php`**
```php
<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../plugins/_sdk.php';

$text   = plugin_input('text', '', 'POST');
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $words     = $text ? str_word_count(strip_tags($text)) : 0;
    $chars     = mb_strlen($text);
    $sentences = preg_match_all('/[.!?]+/', $text);
    $readTime  = max(1, (int) ceil($words / 200));
    $result    = compact('words', 'chars', 'sentences', 'readTime');
}

ob_start(); ?>
<div style="max-width:720px;margin:0 auto;padding:32px 16px">
    <h1 style="font-size:26px;font-weight:800;margin-bottom:8px">Word Counter</h1>
    <p style="color:var(--color-text-muted);margin-bottom:24px">Paste your text below to count words, characters, and more.</p>

    <form method="POST">
        <?= Security::csrfField() ?>
        <textarea name="text" rows="10" style="width:100%;padding:12px;border:1px solid var(--color-border);border-radius:var(--radius-medium);font-size:14px;resize:vertical;background:var(--color-surface);color:var(--color-text)"
                  placeholder="Paste your text here..."><?= e($text) ?></textarea>
        <button type="submit" class="btn btn-primary" style="margin-top:12px">Analyse</button>
    </form>

    <?php if ($result): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;margin-top:28px">
        <?php foreach ([
            'Words'      => $result['words'],
            'Characters' => $result['chars'],
            'Sentences'  => $result['sentences'],
            'Read Time'  => $result['readTime'] . ' min',
        ] as $label => $value): ?>
        <div style="background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-medium);padding:20px;text-align:center">
            <div style="font-size:28px;font-weight:800;color:var(--color-primary)"><?= e($value) ?></div>
            <div style="font-size:13px;color:var(--color-text-muted);margin-top:4px"><?= $label ?></div>
        </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>
</div>
<?php
$content = ob_get_clean();
plugin_render('Word Counter', $content, ['description' => 'Free online word counter tool.']);
```

---

## Limitations & Rules

These are non-negotiable constraints. Violating them will cause the platform to auto-deactivate your plugin.

| Rule | Detail |
|---|---|
| **No direct bootstrap** | Never `require _bootstrap.php` yourself. It is already loaded. |
| **No Composer** | The platform has zero Composer dependencies. Your plugin cannot use Composer autoloading or vendor directories. Bundle any PHP libraries manually. |
| **No exit/die in render** | Don't call `exit` or `die` inside your page body. Use `redirect()` or `plugin_redirect()` for redirects. |
| **No hardcoded table names** | Always use `plugin_table($slug, $table)`. Never write raw table names. |
| **No globals redeclared** | Never re-instantiate `$db`, `$auth`, `$settings`, etc. Use `global $db;` to access them. |
| **No `<?xml` in PHP files** | PHP parses `<?xml` as a short tag and crashes. Put XML in a separate `.xml` file if needed. |
| **No backticks in PHP strings inside .php files** | Backtick template literals in `<script>` blocks inside `.php` files cause PHP parse errors. Put complex JavaScript in a `.js` file. |
| **CSRF on every POST** | `Security::verifyCsrf()` must be the first line of every POST handler. |
| **SQLite + MySQL** | Your `CREATE TABLE` statements must work on both SQLite and MySQL. Use the `$AI`/`$TS` pattern shown above. |
| **File size** | Keep individual PHP files under 300 lines. Factor large pages into sub-pages. |
| **Plugin slug = directory name** | The directory name must match `plugin.json` → `slug` exactly, kebab-case. |

---

## Plugin Lifecycle

```
Upload ZIP or add directory
        ↓
Admin → Plugins → Activate
        ↓
Platform reads plugin.json, validates manifest, inserts row into `plugins` table
        ↓
Plugin bootstrap runs (wrapped in try/catch — error = auto-deactivate)
        ↓
Plugin accessible at /plugins/{slug}/
        ↓
Admin → Plugins → Deactivate / Delete
```

When a plugin is deactivated, its page returns 404. Its database tables are **not** dropped — data is preserved for reactivation. If you want to clean up tables on deactivation, implement a `deactivate.php` file in your plugin root (called by the admin panel if it exists).

---

## Submitting a Plugin

1. Fork [github.com/ShamrouzAwan/awan-tools-plugins](https://github.com/ShamrouzAwan/awan-tools-plugins)
2. Create a directory: `plugins/{your-slug}/`
3. Add `plugin.json` and `index.php` (minimum)
4. Test locally — run the platform, activate your plugin, verify it works on both SQLite and MySQL
5. Open a Pull Request with the title: `[Plugin] Your Plugin Name`

Pull request checklist:
- [ ] `plugin.json` validates against the schema (name, slug, version, description, author, icon, offered, categories)
- [ ] `slug` in `plugin.json` matches directory name exactly
- [ ] All POST forms have `Security::csrfField()` and `Security::verifyCsrf()`
- [ ] No Composer dependencies
- [ ] No hardcoded table names
- [ ] Works on PHP 8.0+
- [ ] Works on both SQLite and MySQL
- [ ] No emojis in UI text (platform style guide)
- [ ] Output is escaped with `e()` for all user-controlled values

---

## Project Management System (Planned)

A full-featured project management plugin is on the roadmap for this platform. Planned features:

- **Projects** — create projects with title, description, client, status (`active`, `on-hold`, `completed`), due date, budget
- **Tasks** — per-project task list with priority, assignee, status (`todo`, `in-progress`, `review`, `done`), due date
- **Milestones** — group tasks into milestones with progress tracking
- **Time Tracking** — log hours per task; generate time reports per project or date range
- **Comments** — threaded notes on projects and tasks
- **Dashboard** — project overview with Kanban board view and Gantt-style timeline
- **Client Portal** — optional read-only view for clients (token-based, no account required)

Schema tables planned: `plg_pm_projects`, `plg_pm_tasks`, `plg_pm_milestones`, `plg_pm_time_logs`, `plg_pm_comments`, `plg_pm_members`

This will be built as a plugin (`plugins/project-manager/`) so it is fully optional and does not affect the core platform.

---

*Built with care by [Shamrouz Awan](https://shamrouzawan.com) — Made to Help*
