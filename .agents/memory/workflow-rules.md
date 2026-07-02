---
name: Workflow rules
description: How to work with this AWAN Tools project — deployment context, change reporting, schema protection, code conventions
---

## Deployment Context
- Platform runs on **shared hosting**. User manually FTPs only the changed files to the server.
- Every response that modifies code must end with a `## Files Changed` section listing each file as Created / Modified / Deleted with a one-line reason.
- Changes must be minimal and surgical — never touch files that don't need to change.

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
- If change touches more than ~5 files, flag and confirm scope first.

## Plugin Rules
- New plugins: `plugins/{slug}/` with `plugin.json` + `index.php`.
- Use SDK helpers from `plugins/_sdk.php`.
- Keep logic client-side (JS) where possible.
- Plugin DB tables must use prefix `plg_{slug}_` and require schema approval.

## Why
User runs production on shared hosting and uploads files manually. Unexpected file changes or schema diffs break the live site. Precision and transparency are critical.
