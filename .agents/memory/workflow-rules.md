---
name: Workflow rules
description: How to work with this AWAN Tools project — deployment context, change reporting, schema protection, code conventions, and request-handling lessons
---

## Deployment Context
- Platform runs on **shared hosting**. User manually FTPs only the changed files to the server.
- Every response that modifies code must end with a `## Files Changed` section listing each file as Created / Modified / Deleted with a one-line reason.
- Changes must be minimal and surgical — never touch files that don't need to change.
- User reviews and uploads changes manually, sometimes across multiple sessions. They may not have applied the previous response before the next request arrives — do not assume prior responses are live.

## Schema Protection
- **Never modify `_database/schema.php` without explicit user approval.**
- If a feature needs a schema change: stop, explain exactly what table/column and why, wait for confirmation.
- Prefer implementing features using existing tables/columns.

## Code Conventions (must preserve)
- Singleton pattern for all core classes.
- All SQL via PDO prepared statements — never interpolate user input.
- Always escape output with `e()` or `htmlspecialchars()`.
- All POST endpoints must call `Security::verifyCsrf()`.
- No Composer, no npm, no new local dependencies — CDN only for frontend libs.
- Do not add files to `_core/` without discussion.
- Do not rename existing files, functions, classes, or DB columns.

## Scope Rules
- Smallest possible change that satisfies the request.
- Do not improve adjacent code while fixing something else.
- If change touches more than ~5 files, flag and confirm scope first before implementing.
- **Prefer single-file solutions**: before creating a new registry/config file, ask if the data already exists somewhere (e.g. plugin.json, DB, existing config).

## Plugin Rules
- New plugins: `plugins/{slug}/` with `plugin.json` + `index.php`.
- Every plugin index.php must start with: `defined('AWAN') or die(); require_once ../../_bootstrap.php; require_once ../../plugins/_sdk.php; require_once AWAN_ROOT/_core/Plugin.php;`
- Use SDK helpers from `plugins/_sdk.php`.
- Keep logic client-side (JS) where possible.
- Plugin DB tables must use prefix `plg_{slug}_` and require schema approval.
- `plugin.json` is the source of truth for plugin metadata (tags, keywords, slug, offered count, etc.) — read from there before creating new data structures.

## Request-Handling Lessons

### Use existing data sources first
When a feature needs data about plugins (tools, keywords, tags, metadata), always check `plugin.json` files before proposing a new registry/config file. The user prefers solutions that read from existing sources rather than introducing new files to maintain.

### Confirm before multi-file proposals
If a proposed solution touches more than ~5 files, describe the full list of changes and wait for approval before implementing. The sitemap deep-link approach (6 JS files + 1 PHP + 1 registry) was rejected in favour of a single-file solution reading plugin.json.

### Reversions
When the user says "revert all changes you made in [response]", immediately:
1. Restore every file that was Modified to its prior state (remove the additions)
2. Delete every file that was Created
3. Report each revert in the ## Files Changed section

### Proposals in suggest_next_ideas
Keep suggestions realistic and single-file when possible. Avoid suggesting approaches that would require touching many plugin files — the user consistently prefers minimal-touch solutions.

## Why
User runs production on shared hosting and uploads files manually. Unexpected file changes or schema diffs break the live site. Precision and transparency are critical.
