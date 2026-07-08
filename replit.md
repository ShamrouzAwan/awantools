# AWAN Tools Platform

A self-hosted, multi-user SaaS platform for hosting modular online utilities. Built with PHP 8.2, zero Composer dependencies, zero external frameworks. Ships with 14+ plugins offering 500+ individual tools.

## How to Run

The app starts automatically via the **Start application** workflow:

```
php -S 0.0.0.0:5000 _router.php
```

The database (SQLite) is at `storage/database.sqlite` and auto-initializes on first boot.

## Default Admin Credentials

- URL: `/login`
- Username: `admin`
- Password: `Admin@1234`

> **Change these immediately after first login.**

## Stack

- **PHP 8.2** — no Composer, no frameworks
- **SQLite** (default) or **MySQL** — configured via `_config.php` / env vars / `config.local.php`
- **Plugins** in `plugins/{slug}/` — each has a `plugin.json` manifest and `index.php`
- **Themes** in `themes/{slug}/`

## Configuration

- Copy `config.local.php.example` → `config.local.php` for local overrides (git-ignored)
- Production: set env vars (`DB_DRIVER`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_KEY`, `AWAN_ENV`)
- Set `AWAN_ENV=development` to enable debug mode

## Deployment Context

This project targets **shared hosting** (Hostinger/cPanel). Changes are deployed by manually uploading only the changed files via FTP. Every change response includes a **Files Changed** section listing exactly which files to upload.

## Key Directories

| Path | Purpose |
|---|---|
| `_core/` | Core engine (singleton classes — do not add files without discussion) |
| `_database/schema.php` | DB schema — do not modify without explicit approval |
| `plugins/` | Plugin registry |
| `themes/` | Theme files |
| `storage/` | SQLite DB, logs, uploads, backups |
| `_bootstrap.php` | App bootstrap (loaded by every entry point) |
| `_router.php` | PHP built-in server router |
| `_config.php` | Central configuration |

## User Preferences

- Report all changed files after every code modification (shared hosting FTP workflow)
- Minimal, surgical changes only — no refactoring of working code
- No Composer packages, no npm packages, no new external dependencies
- Never modify `_database/schema.php` without explicit approval
- New frontend libraries via CDN only
