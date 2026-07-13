---
name: AWAN plugin CSS must be manually linked in index.php
description: plugin_render()'s $opts array has no 'stylesheet' key — passing one is silently ignored; the plugin's own index.php must emit a <link> tag itself.
---

In the AWAN Tools Platform (self-hosted PHP plugin SaaS), `plugin_render($title, $content, $opts)` in
`plugins/_sdk.php` does NOT read a `stylesheet` option — there is no such key handled anywhere in
`themes/default/templates/layout.php`. Passing `'stylesheet' => '/plugins/<slug>/assets/foo.css'` in `$opts`
compiles fine and looks correct, but the CSS file is never linked, so the page ships unstyled markup (raw
oversized SVG icons, no layout) while every other asset (site CSS, JS) loads normally — easy to miss because
there's no error, just a visually broken page.

**Why:** An existing plugin (network-toolkit) happens to emit its own `<link rel="stylesheet" ...>` tag
directly in its buffered HTML content (inside the `ob_start()`/`ob_get_clean()` block, before the closing
`plugin_render()` call) rather than relying on an opts key — that's the only correct pattern, but nothing
enforces it, so copying the surface-level `plugin_render(...)` call signature from a reference plugin without
also copying its manual `<link>` tag silently drops the CSS.

**How to apply:** When scaffolding a new plugin's `index.php`, always add
`<link rel="stylesheet" href="/plugins/<?= $slug ?>/assets/<slug>.css">` inside the content buffer (near the
top, before the plugin's own markup) — do not rely on any `plugin_render()` opts key to load plugin-specific
CSS. Verify by curling the rendered page and grepping for the literal `<link ... assets/<slug>.css>` tag, not
just checking that the CSS file itself returns 200.
