# AWAN Tools Platform — Agent Instructions

## Deployment Context

This platform runs on **shared hosting**. The production system is separate from this development environment. When changes are made here, the user manually uploads **only the changed files** to the server via FTP/cPanel. This means:

- Every response that modifies code **must include a clear list of changed files**.
- Changes must be minimal and surgical — avoid touching files that don't need to change.
- Never refactor working code unless explicitly asked.

---

## Change Reporting Format

After every code change, always end the response with a section like this:

```
## Files Changed
- `path/to/file.php` — brief description of what changed and why
- `path/to/another.php` — brief description
```

If a new file was created, say **Created**. If an existing file was modified, say **Modified**. If a file was deleted, say **Deleted**.

The user will upload only those listed files to their shared hosting server.

---

## Database Schema Rules

- **Do NOT modify `_database/schema.php` without explicit permission.**
- If a requested feature requires a schema change, **stop and ask first**:
  - Explain exactly what table or column needs to be added/changed.
  - Explain why it is necessary.
  - Wait for confirmation before making any schema change.
- Prefer implementing features using **existing tables and columns** whenever possible.
- If a feature genuinely cannot work without a schema change, make that clear upfront — do not discover it mid-implementation.

---

## General Code Rules

- Preserve all existing code patterns and conventions (Singleton classes, `e()` for escaping, `Security::verifyCsrf()` on all POST forms, etc.).
- All queries must use **PDO prepared statements** — never string-interpolate user input into SQL.
- Always escape output with `e()` or `htmlspecialchars()`.
- All POST endpoints must call `Security::verifyCsrf()`.
- Do not add Composer packages, npm packages, or any new external dependencies.
- New frontend libraries must be loaded from CDN only, consistent with the existing pattern.
- Do not add new files to `_core/` without explicit discussion — the core is stable.
- Do not rename existing files, functions, or classes.
- Do not change existing database column names or table names.

---

## Scope of Changes

- Make the **smallest possible change** that satisfies the request.
- Do not "improve" adjacent code while fixing or adding something else.
- If a change touches more than ~5 files, flag it and confirm the scope with the user before proceeding.

---

## Plugin Development Rules

- New plugins go in `plugins/{slug}/` with a valid `plugin.json` and `index.php`.
- Use `plugins/_sdk.php` helpers (`plugin_url()`, `plugin_asset()`, `plugin_render()`, etc.).
- Plugin logic that can run in the browser (JavaScript) should stay client-side — do not add server-side processing unless necessary.
- Plugin table names must use the prefix `plg_{slug}_` if DB storage is needed (and requires schema approval first).

---

## Testing & Validation

- After making changes, verify the affected pages load correctly using the app preview.
- If a change involves a form or API endpoint, test it before reporting completion.
- Always check browser console logs for JavaScript errors after frontend changes.

---

## Communication Style

- Be direct and concise.
- Do not ask unnecessary questions — make sensible decisions and explain them.
- Do ask before: schema changes, deleting files, broad refactors, adding new dependencies.
- Always tell the user **what changed and where** so they can upload exactly the right files.
