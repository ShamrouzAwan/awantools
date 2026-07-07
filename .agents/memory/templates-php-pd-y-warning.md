---
name: Templates.php dead variable bug
description: All template functions in Templates.php had 4 undefined-variable lines ($pd_y) that caused PHP E_WARNING on shared hosting, corrupting image responses before Content-Type: image/png could be set.
---

# Templates.php dead variable bug

**Rule:** Never leave dead variable blocks that reference undefined variables in any template function. PHP E_WARNING fires before `ob_start()` in the render path, sending text output that corrupts the image response.

**Why:** Shared hosting often has `display_errors = On` at the system level and cannot be overridden by `ini_set()`. The warnings appear in the HTTP response body before any `header()` call can set Content-Type, causing the browser to receive mixed text+PNG and show a blank image.

**How to apply:** When adding new template functions to `plugins/previewer-toolkit/engine/Templates.php`, ensure every variable is explicitly initialized before use. There are no `$pd_*` variables in the codebase — do not add them.

**Fix applied:** Removed 38 dead-variable blocks (`$pd_badge_y = $pd_y + 8;` etc.) from every template function in Templates.php via Python regex replace.
