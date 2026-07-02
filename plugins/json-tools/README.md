# JSON Tools Plugin

**Version:** 1.0.0  
**Author:** Shamrouz Awan  
**Platform:** AWAN Tools  
**Categories:** Developer Tools, Utilities  

---

## Overview

JSON Tools is a fully client-side developer plugin for the AWAN platform. It bundles **15 JSON utilities** into a single, tab-driven page. Every operation runs in the browser — no data is ever sent to a server.

---

## Tools Reference

### Format & View (7 tools)

| # | Tool | What it does |
|---|------|-------------|
| 1 | **JSON Formatter** | Parses and reformats JSON with configurable indentation (2 spaces, 4 spaces, or tabs). Live preview as you type. |
| 2 | **JSON Beautifier** | Instantly reformats minified or ugly JSON into a clean, 2-space indented structure. |
| 3 | **JSON Minifier** | Strips all whitespace from JSON to produce the smallest possible valid output. Shows bytes saved. |
| 4 | **JSON Validator** | Checks whether the input is valid JSON. Reports the exact error message and position on failure. |
| 5 | **JSON Parser** | Parses JSON and renders a structured table showing every key path, data type, and value. |
| 6 | **JSON Viewer** | Renders JSON as a collapsible, interactive tree. Expand/Collapse All buttons included. |
| 7 | **JSON Pretty Print** | Syntax-highlighted output with colour-coded keys, strings, numbers, booleans, and null values. |

### String Operations (2 tools)

| # | Tool | What it does |
|---|------|-------------|
| 8 | **JSON Escape** | Escapes a raw string so it can be safely embedded inside a JSON value (`"`, `\n`, `\t`, etc.). |
| 9 | **JSON Unescape** | Reverses JSON escaping — converts `\"`, `\\n`, `\\t` back to their literal characters. |

### Conversions (6 tools)

| # | Tool | What it does |
|---|------|-------------|
| 10 | **JSON to CSV** | Converts a JSON array of objects to CSV. All columns are auto-detected. Handles nested objects by serialising them. Includes Download button. |
| 11 | **CSV to JSON** | Parses CSV (first row = headers) into a JSON array. Auto-detects numbers, booleans, and strings. |
| 12 | **JSON to XML** | Converts a JSON object to indented XML. Array items become sibling elements. |
| 13 | **XML to JSON** | Parses XML using the browser's native DOMParser. Handles attributes (`@`), text content, and repeated child elements (arrays). |
| 14 | **JSON to YAML** | Converts JSON to YAML using js-yaml 4.1.0 (bundled locally — no CDN call). |
| 15 | **YAML to JSON** | Parses YAML back to JSON using js-yaml 4.1.0. |

---

## Technical Details

### Third-Party Libraries

| Library | Version | File | Purpose |
|---------|---------|------|---------|
| [js-yaml](https://github.com/nodeca/js-yaml) | 4.1.0 | `assets/js-yaml.min.js` | YAML parse and dump (MIT licence) |

All other functionality is implemented in vanilla JavaScript with no external dependencies.

### File Structure

```
plugins/json-tools/
├── plugin.json          Plugin manifest
├── index.php            Main plugin page (all 15 tools)
├── on_activate.php      Activation hook (no-op — no DB tables needed)
├── on_deactivate.php    Deactivation hook (no-op)
├── on_uninstall.php     Uninstall hook (no-op — stateless)
├── README.md            This file
└── assets/
    └── js-yaml.min.js   Bundled YAML library (39 KB)
```

### Privacy

- Zero server communication for tool operations
- No user data is stored or logged beyond standard platform analytics events
- `requires_login: false` — accessible to guests and registered users alike
- `stores_user_data: false`

---

## Installation

1. The plugin folder is already placed at `awan/plugins/json-tools/`.
2. Log in as **Super Admin** and navigate to **Admin → Plugins**.
3. Click **Activate** next to **JSON Tools**.
4. The plugin is immediately accessible at `/plugins/json-tools/`.

---

## Usage

1. Click any tool name in the left sidebar to switch to it.
2. Paste your input into the left textarea (or click **Load Sample** in the top-right for a demo).
3. Most Format & View tools run **live as you type**. Conversion tools require pressing **Convert**.
4. Use **Copy Output** to copy the result to your clipboard, or **Download CSV** for CSV exports.
5. Use **Clear** to reset any individual tool's input and output.

---

## Error Handling

- Invalid JSON input shows a red error message with the parser's exact error string (including position where available).
- Invalid XML (XML to JSON) surfaces the DOMParser's parse error.
- Invalid YAML (YAML to JSON) surfaces js-yaml's error message.
- All errors are displayed inline — no alerts or page reloads.

---

## Changelog

### 1.0.0 — Initial Release
- All 15 JSON tools implemented
- js-yaml 4.1.0 bundled locally
- Live processing for all Format & View tools
- JSON to CSV with Download button
- Interactive tree viewer with Expand/Collapse All
- Syntax-highlighted Pretty Print
- Full platform CSS variable compliance (dark mode compatible)
