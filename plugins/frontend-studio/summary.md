# Frontend Studio — Project Summary

## Project Overview

Frontend Studio is a professional browser-based frontend IDE built as a plugin for the Awan Tools platform. It provides a complete development environment for HTML, CSS, JavaScript, SVG, JSON, Markdown, and other frontend technologies — entirely in the browser, with no backend execution.

**Target users:** Frontend developers who need a capable, fast IDE accessible from any browser without installation.

**Philosophy:** Familiar IDE workflows (VS Code-like), unique identity, developer-first UX.

---

## Current Implemented Features

### Core IDE
- Full-viewport IDE layout (activity bar, sidebar, editor area, bottom panel, status bar)
- Resizable panels (sidebar drag, bottom panel drag)
- Panel show/hide (sidebar, bottom, preview)
- Keyboard shortcut system

### Projects & Files
- Virtual file system stored in IndexedDB (browser-local, no server)
- Project creation, rename, duplicate, delete
- Nested file/folder tree
- File operations: create, rename, delete, duplicate
- Context menus for all file/project operations
- Auto-save on change (debounced, configurable delay)

### Editor (Monaco)
- Monaco Editor loaded from CDN (VS Code engine)
- Language detection from file extension
- Syntax highlighting for: HTML, CSS, SCSS, LESS, JavaScript, TypeScript, JSON, SVG, Markdown, TXT
- Full Monaco feature set: IntelliSense, autocomplete, folding, minimap, multi-cursor, find/replace, breadcrumbs, go to line

### Tabs
- Unlimited tabs
- Dirty state (unsaved indicator dot)
- Pinned tabs
- Tab close, close others, close to right
- Reopen closed tab history
- Drag to reorder

### Live Preview
- Iframe-based preview panel
- Asset linking system: associate CSS/JS files with HTML entry points
- Auto-refresh on save
- Device presets: Desktop (1280px), Tablet (768px), Phone (375px)
- Landscape rotation
- Zoom control
- Open in new browser tab

### Extensions
- Extension manager with enable/disable
- HTML/CSS/JS/JSON Formatter (js-beautify)
- HTML/CSS/JS Minifier
- Markdown Preview
- Color Picker
- Lorem Ipsum Generator
- Regex Tester

### AI Assistant
- Dedicated AI sidebar panel
- Multiple provider support: OpenAI, Anthropic, Google Gemini, OpenRouter, DeepSeek, Groq, Mistral, Custom endpoint
- API keys stored in localStorage only (never sent to any proxy)
- Chat interface with streaming
- Context modes: selection, current file, full project
- Quick actions: Explain, Fix, Improve, Refactor, Comments, Docs
- Insert/replace response in editor

### Search
- Project-wide text search
- Regex, case-sensitive options
- Replace / Replace All
- Results grouped by file, clickable to navigate

### Command Palette (Ctrl+Shift+P)
- All major actions accessible by keyboard
- Fuzzy search
- Keyboard shortcut hints

### Settings
- Theme picker (8 themes)
- Editor settings (font, size, tab size, word wrap, minimap, auto-save)
- Preview settings
- Import/Export settings as JSON
- Reset to defaults

### Themes
- Dark (default)
- Light
- High Contrast
- Monokai
- Dracula
- Tokyo Night
- Catppuccin Mocha
- GitHub Dark

### Status Bar
- Language, encoding, line/column, tab size, error count, auto-save status, preview status, AI provider

---

## Architecture

```
frontend-studio/
├── plugin.json         ← Plugin manifest
├── index.php           ← PHP entry; outputs full standalone HTML page
├── todo.md             ← Implementation tracker
├── summary.md          ← This file
└── assets/
    ├── studio.css      ← Complete IDE styles (CSS custom properties, layout, themes)
    └── studio.js       ← Complete IDE JavaScript (FS namespace)
```

### PHP Layer
- `index.php` outputs a standalone HTML page (does NOT call `plugin_render`)
- PHP only generates the page skeleton and asset URLs
- No server-side processing beyond initial page delivery

### JavaScript Architecture (`FS` namespace, IIFE)
```
FS.THEMES      — theme color definitions
FS.LANGS       — file extension → Monaco language mapping
FS.db          — IndexedDB wrapper (projects, files)
FS.settings    — localStorage settings manager
FS.events      — simple pub/sub event bus
FS.ui          — dialogs, context menus, toasts, tooltips
FS.projects    — project CRUD
FS.explorer    — file tree rendering, drag/drop
FS.tabs        — tab management
FS.editor      — Monaco editor wrapper
FS.preview     — iframe preview engine
FS.extensions  — extension system
FS.ai          — AI panel and API calls
FS.search      — project-wide search
FS.palette     — command palette
FS.shortcuts   — keyboard shortcut system
FS.status      — status bar updates
FS.firstRun    — onboarding wizard
FS.init()      — application bootstrap
```

### Storage Schema
**IndexedDB (`fs-ide` database, version 1):**
- Store `projects`: `{ id, name, description, created, updated, linkedAssets }`
- Store `files`: `{ id, projectId, parentId, type, name, content, created, updated }` — indexed by `projectId`

**localStorage (`fs-settings` key):**
Full settings object including theme, editor prefs, open tabs, active project, sidebar state.

---

## Design System

### CSS Custom Properties
All IDE colors defined as `--fs-*` variables, scoped to `[data-theme="..."]` on `<html>`. Monaco theme applied separately via `monaco.editor.setTheme()`.

### Typography
- UI: system-ui stack
- Editor: JetBrains Mono → Fira Code → Consolas → monospace (loaded from Google Fonts)

### Layout Grid
Activity bar: 48px fixed width
Sidebar: 280px default, resizable 180–600px
Bottom panel: 200px default, resizable 80–600px
Status bar: 22px fixed
Title bar: 38px fixed
Tab bar: 35px fixed

---

## Supported Languages

| Extension | Language | Monaco Mode |
|-----------|----------|-------------|
| .html, .htm | HTML | html |
| .css | CSS | css |
| .scss | SCSS | scss |
| .less | LESS | less |
| .js, .mjs | JavaScript | javascript |
| .ts | TypeScript | typescript |
| .json | JSON | json |
| .svg | SVG | xml |
| .md, .markdown | Markdown | markdown |
| .txt | Plain Text | plaintext |

---

## Known Limitations

- Monaco requires internet access (CDN load)
- File System Access API (open local folder/save to disk) only works in Chromium-based browsers
- No real-time collaboration
- No backend languages / server-side execution
- Large files (>500KB) may degrade Monaco performance
- Split editor (two panes) not yet implemented

---

## Future Improvements

1. Split editor (two panes)
2. CSS generator tools (flexbox builder, grid builder, gradient/shadow/animation generators)
3. Accessibility checker extension
4. SEO analyzer extension
5. SVG optimizer extension
6. Import/Export ZIP (JSZip integration)
7. File System Access API for local folder editing
8. Version history (session-based snapshots)
9. More AI provider profiles
10. Collaborative editing foundation

---

## Major Implementation Decisions

**IndexedDB over localStorage for files:** Files can be large (multi-MB); localStorage has a 5MB limit and is synchronous. IndexedDB handles large content and async ops cleanly.

**Monaco from CDN:** Avoids bundling ~10MB of Monaco assets. Acceptable since the tool requires internet access anyway.

**No server roundtrip for preview:** Preview iframe builds a Blob URL from in-memory file content. No PHP involved. Instant refresh.

**Plugin bypasses `plugin_render`:** Frontend Studio fills 100vh with no platform nav. The router just `require`s index.php, which outputs raw HTML and exits. This is the correct approach for full-viewport tools.

**AI keys in localStorage only:** Per spec — credentials never leave the browser except for direct requests to the configured provider endpoint. No proxy.
