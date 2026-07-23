# Awan Whiteboard — Technical Architecture and Implementation Plan

## Scope and product boundary

Awan Whiteboard is an installable AwanTools plugin, not a standalone app. The first
release is intentionally a focused MVP: authenticated personal boards, vector
strokes and basic vector shapes, a responsive infinite canvas, autosave, duplicate/
archive/delete board management, and version snapshots. It uses only PHP, SQLite/
MySQL through the platform database wrapper, SVG, and vanilla JavaScript.

The full brief remains the product roadmap. Recognition, collaboration, classrooms,
offline synchronization, and document import/export are planned phases rather than
simulated controls in the MVP.

## Plugin boundary

```text
plugins/whiteboard/
├── api/                 # Future split API handlers; MVP uses index.php JSON actions
├── assets/
│   ├── whiteboard.css
│   └── whiteboard.js
├── controllers/         # Future request/controller extraction
├── migrations/
│   └── install.php      # Creates and removes only plg_whiteboard_* tables
├── models/              # Future repository extraction
├── templates/           # Future export/template documents
├── views/               # Future view extraction
├── index.php            # Authenticated board list and canvas entry point
├── on_activate.php      # Install migration
├── on_deactivate.php    # Non-destructive lifecycle hook
├── on_uninstall.php     # Removes plugin tables when explicitly uninstalled
└── plugin.json
```

The plugin uses `plugin_table('whiteboard', ...)` for every table. It never writes
to `_database/schema.php`, platform tables, or another plugin's storage.

## Data model

### MVP tables

- `plg_whiteboard_boards`: owner, title, description, folder, status, viewport,
  timestamps, and last editor.
- `plg_whiteboard_objects`: one vector object per row. `object_type` is `stroke`,
  `shape`, `text`, or `sticky`; `data_json` contains the normalized object payload.
- `plg_whiteboard_versions`: immutable JSON snapshots for restore/compare work.

### Reserved phase tables

The migration also creates the normalized collaboration-ready tables:
`board_members`, `classrooms`, `classroom_members`, `notes`, `flashcards`,
`comments`, and `activity_logs`. They are isolated and indexed now, but no UI
claims those features until their phases are implemented.

All user-controlled values are parameterized. Every mutating request verifies the
platform CSRF token and re-checks that the current user owns the board.

## MVP request flow

1. `/plugins/whiteboard/` is admitted by the existing plugin router only when the
   plugin is active.
2. `index.php` loads the current user's boards or a specific owned board.
3. The canvas keeps an in-memory vector model and renders it as SVG; the browser
   never rasterizes the board during editing.
4. Save requests post JSON to the same plugin route with the CSRF token in
   `X-CSRF-TOKEN`.
5. The server validates object count and JSON shape, replaces the board's objects
   in one transaction, updates board metadata, and records a version snapshot.
6. The client debounces saves and exposes the last saved state; failed saves stay
   dirty and show an actionable error.

## Security and performance

- Login is required through the AwanTools `requireLogin()` helper.
- CSRF uses `Security::verifyCsrf()`.
- Board ownership is checked for every read, save, and lifecycle action.
- SQL uses `Database` prepared statements.
- Object payloads are JSON data, escaped on output, and rendered through DOM/SVG
  APIs rather than interpolated as HTML.
- MVP caps one save at 10,000 objects and stores strokes as point arrays.
- Later scale work: viewport chunk queries, object pagination, snapshot retention,
  and client-side spatial indexing.

## Phased roadmap

### Phase 1 — MVP (implemented here)

Personal board CRUD, SVG infinite canvas, pan/zoom/fit, grid and snap toggles,
pen/pencil/marker/highlighter/eraser, rectangle/circle/line/arrow, text and sticky
notes, vector persistence, autosave, duplicate/archive/delete, and snapshots.

### Phase 2 — Editing and study workflow

Selection, resize/rotate, grouping, locking, rich text, font choices, note search,
flashcards, board import/export (SVG/PNG first), and version restore/compare.

### Phase 3 — Recognition

Stroke selection to temporary image, optional Tesseract.js client OCR, editable
recognized text, math-to-LaTeX adapters, KaTeX/MathJax rendering, and geometry
based shape beautification. Recognition must be opt-in and must fail clearly when
the browser cannot load the free client model.

### Phase 4 — Collaboration and classrooms

Board members and share links, optimistic conflict handling, comments, activity
logs, teacher classrooms, invite links, assignments, presentation mode, and
broadcast mode. Shared hosting compatibility favors polling/SSE before any
WebSocket requirement.

### Phase 5 — Offline and scale

Service worker, IndexedDB operation queue, reconnect reconciliation, spatial
chunking, lazy loading, retention controls, and admin usage/storage reporting.

## API contract for the MVP

The MVP keeps the API surface inside the plugin route for shared-hosting
compatibility:

- `POST /plugins/whiteboard/` with `action=create_board` — creates a board.
- `POST /plugins/whiteboard/` with `action=save_board` and JSON body — replaces
  the owned board's vector objects and creates a snapshot.
- `POST /plugins/whiteboard/` with `action=board_action` — duplicate, archive,
  restore, or delete an owned board.

The future `/api/` directory can split these actions once collaboration requires
separate endpoints and polling/version cursors.

## Installation and removal

1. Copy the `plugins/whiteboard` directory into AwanTools.
2. Open Admin → Plugins; the existing sync discovers `plugin.json`.
3. Activate Awan Whiteboard; `on_activate.php` creates only its prefixed tables.
4. Open `/plugins/whiteboard/` as a logged-in user.
5. Deactivate to hide the plugin without data loss.
6. Uninstall only when removal of all whiteboard data is intended; the uninstall
   hook drops the prefixed tables.
