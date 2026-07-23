<?php
defined('AWAN') or die('Direct access denied.');
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';

$slug = 'whiteboard';
plugin_requires_login($slug);

$boardsTable = plugin_table($slug, 'boards');
$objectsTable = plugin_table($slug, 'objects');
$versionsTable = plugin_table($slug, 'versions');
$userId = (int)$auth->id();

function whiteboard_json(array $payload, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function whiteboard_board(int $id, int $userId): ?array {
    global $db, $boardsTable;
    return $db->fetch(
        "SELECT * FROM {$boardsTable} WHERE id = ? AND owner_id = ? AND status != 'deleted' LIMIT 1",
        [$id, $userId]
    );
}

function whiteboard_number(mixed $value, float $min, float $max, string $field): float {
    if (!is_numeric($value) || !is_finite((float)$value)) {
        throw new InvalidArgumentException("Invalid {$field}.");
    }
    $number = round((float)$value, 2);
    if ($number < $min || $number > $max) {
        throw new InvalidArgumentException("Invalid {$field}.");
    }
    return $number;
}

function whiteboard_color(mixed $value, string $field = 'color'): string {
    if (!is_string($value) || !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
        throw new InvalidArgumentException("Invalid {$field}.");
    }
    return strtolower($value);
}

function whiteboard_id(mixed $value): string {
    if (!is_string($value) || !preg_match('/^[a-zA-Z0-9_-]{1,80}$/', $value)) {
        throw new InvalidArgumentException('Invalid object id.');
    }
    return $value;
}

function whiteboard_text(mixed $value, int $max, string $field): string {
    if (!is_string($value)) throw new InvalidArgumentException("Invalid {$field}.");
    $value = trim(strip_tags($value));
    if ($value === '' || mb_strlen($value) > $max) {
        throw new InvalidArgumentException("Invalid {$field}.");
    }
    return $value;
}

function whiteboard_clean_objects(mixed $raw): array {
    if (!is_array($raw) || count($raw) > 10000) {
        throw new InvalidArgumentException('The board payload is invalid or exceeds the 10,000 object limit.');
    }

    $objects = [];
    foreach ($raw as $object) {
        if (!is_array($object)) throw new InvalidArgumentException('Invalid board object.');
        $type = $object['type'] ?? null;
        if (!is_string($type) || !in_array($type, ['stroke', 'shape', 'text', 'sticky'], true)) {
            throw new InvalidArgumentException('Invalid board object type.');
        }

        $id = whiteboard_id($object['id'] ?? null);
        if ($type === 'stroke') {
            $tool = $object['tool'] ?? 'pencil';
            if (!is_string($tool) || !in_array($tool, ['pencil', 'pen', 'marker', 'brush', 'highlighter'], true)) {
                throw new InvalidArgumentException('Invalid stroke tool.');
            }
            $points = $object['points'] ?? null;
            if (!is_array($points) || count($points) < 1 || count($points) > 5000) {
                throw new InvalidArgumentException('Invalid stroke points.');
            }
            $normalizedPoints = [];
            foreach ($points as $point) {
                if (!is_array($point)) throw new InvalidArgumentException('Invalid stroke point.');
                $normalizedPoints[] = [
                    'x' => whiteboard_number($point['x'] ?? null, -1000000, 1000000, 'point x'),
                    'y' => whiteboard_number($point['y'] ?? null, -1000000, 1000000, 'point y'),
                ];
            }
            $objects[] = [
                'id' => $id,
                'type' => 'stroke',
                'tool' => $tool,
                'points' => $normalizedPoints,
                'color' => whiteboard_color($object['color'] ?? null),
                'width' => whiteboard_number($object['width'] ?? null, 1, 96, 'stroke width'),
                'opacity' => whiteboard_number($object['opacity'] ?? 1, 0, 1, 'stroke opacity'),
            ];
        } elseif ($type === 'shape') {
            $shape = $object['shape'] ?? null;
            if (!is_string($shape) || !in_array($shape, ['rect', 'rounded', 'circle', 'line', 'arrow'], true)) {
                throw new InvalidArgumentException('Invalid shape.');
            }
            $objects[] = [
                'id' => $id,
                'type' => 'shape',
                'shape' => $shape,
                'x' => whiteboard_number($object['x'] ?? null, -1000000, 1000000, 'shape x'),
                'y' => whiteboard_number($object['y'] ?? null, -1000000, 1000000, 'shape y'),
                'w' => whiteboard_number($object['w'] ?? null, -1000000, 1000000, 'shape width'),
                'h' => whiteboard_number($object['h'] ?? null, -1000000, 1000000, 'shape height'),
                'color' => whiteboard_color($object['color'] ?? null),
                'width' => whiteboard_number($object['width'] ?? null, 1, 96, 'shape stroke width'),
            ];
        } elseif ($type === 'text') {
            $objects[] = [
                'id' => $id,
                'type' => 'text',
                'x' => whiteboard_number($object['x'] ?? null, -1000000, 1000000, 'text x'),
                'y' => whiteboard_number($object['y'] ?? null, -1000000, 1000000, 'text y'),
                'text' => whiteboard_text($object['text'] ?? null, 500, 'text'),
                'color' => whiteboard_color($object['color'] ?? null),
                'size' => whiteboard_number($object['size'] ?? null, 8, 144, 'text size'),
            ];
        } else {
            $objects[] = [
                'id' => $id,
                'type' => 'sticky',
                'x' => whiteboard_number($object['x'] ?? null, -1000000, 1000000, 'note x'),
                'y' => whiteboard_number($object['y'] ?? null, -1000000, 1000000, 'note y'),
                'w' => whiteboard_number($object['w'] ?? null, 1, 1000000, 'note width'),
                'h' => whiteboard_number($object['h'] ?? null, 1, 1000000, 'note height'),
                'text' => whiteboard_text($object['text'] ?? null, 220, 'note text'),
                'color' => whiteboard_color($object['color'] ?? null, 'note color'),
            ];
        }
    }
    return $objects;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? $_GET['action'] ?? '');

    if ($action === 'save_board') {
        Security::verifyCsrf();
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) whiteboard_json(['success' => false, 'error' => 'Invalid JSON payload.'], 422);
        $boardId = (int)($body['board_id'] ?? 0);
        $board = whiteboard_board($boardId, $userId);
        if (!$board) whiteboard_json(['success' => false, 'error' => 'Board not found.'], 404);

        try {
            $objects = whiteboard_clean_objects($body['objects'] ?? null);
            $rawViewport = $body['viewport'] ?? null;
            if (!is_array($rawViewport)) throw new InvalidArgumentException('Invalid viewport.');
            $viewport = [
                'x' => whiteboard_number($rawViewport['x'] ?? null, -1000000, 1000000, 'viewport x'),
                'y' => whiteboard_number($rawViewport['y'] ?? null, -1000000, 1000000, 'viewport y'),
                'zoom' => whiteboard_number($rawViewport['zoom'] ?? null, .1, 4, 'viewport zoom'),
            ];
        } catch (InvalidArgumentException $e) {
            whiteboard_json(['success' => false, 'error' => $e->getMessage()], 422);
        }
        $now = date('Y-m-d H:i:s');

        try {
            $db->beginTransaction();
            $db->delete($objectsTable, 'board_id = ?', [$boardId]);
            foreach ($objects as $index => $object) {
                $db->insert($objectsTable, [
                    'board_id' => $boardId,
                    'object_type' => $object['type'],
                    'data_json' => json_encode($object, JSON_UNESCAPED_UNICODE),
                    'z_index' => $index,
                    'created_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $db->update($boardsTable, [
                'viewport_json' => json_encode($viewport),
                'last_edited_by' => $userId,
                'updated_at' => $now,
            ], 'id = ? AND owner_id = ?', [$boardId, $userId]);
            $db->insert($versionsTable, [
                'board_id' => $boardId,
                'created_by' => $userId,
                'label' => 'Autosave',
                'snapshot_json' => json_encode(['objects' => $objects, 'viewport' => $viewport], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
            ]);
            $db->commit();
        } catch (Throwable $e) {
            try { $db->rollback(); } catch (Throwable $ignored) {}
            whiteboard_json(['success' => false, 'error' => 'Could not save this board.'], 500);
        }
        whiteboard_json(['success' => true, 'saved_at' => $now, 'object_count' => count($objects)]);
    }

    Security::verifyCsrf();

    if ($action === 'create_board') {
        $title = Security::sanitize(trim((string)($_POST['title'] ?? '')));
        if ($title === '') $title = 'Untitled board';
        $id = $db->insert($boardsTable, [
            'owner_id' => $userId,
            'title' => substr($title, 0, 160),
            'description' => null,
            'folder' => 'personal',
            'status' => 'active',
            'viewport_json' => json_encode(['x' => 0, 'y' => 0, 'zoom' => 1]),
            'last_edited_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        plugin_track('whiteboard_board_created', '/plugins/whiteboard/', ['plugin_slug' => $slug]);
        redirect(plugin_url($slug, '?board=' . (int)$id));
    }

    $boardId = (int)($_POST['board_id'] ?? 0);
    $board = whiteboard_board($boardId, $userId);
    if (!$board) whiteboard_json(['success' => false, 'error' => 'Board not found.'], 404);

    if ($action === 'rename_board') {
        $title = Security::sanitize(trim((string)($_POST['title'] ?? '')));
        if ($title === '') whiteboard_json(['success' => false, 'error' => 'A board name is required.'], 422);
        $db->update($boardsTable, [
            'title' => substr($title, 0, 160),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ? AND owner_id = ?', [$boardId, $userId]);
        whiteboard_json(['success' => true, 'title' => substr($title, 0, 160)]);
    }

    if ($action === 'board_action') {
        $boardAction = (string)($_POST['board_action'] ?? '');
        if ($boardAction === 'delete') {
            $db->update($boardsTable, ['status' => 'deleted', 'updated_at' => date('Y-m-d H:i:s')], 'id = ? AND owner_id = ?', [$boardId, $userId]);
            redirect(plugin_url($slug));
        }
        if ($boardAction === 'archive' || $boardAction === 'restore') {
            $status = $boardAction === 'archive' ? 'archived' : 'active';
            $db->update($boardsTable, ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = ? AND owner_id = ?', [$boardId, $userId]);
            redirect(plugin_url($slug . '', '?board=' . $boardId));
        }
        if ($boardAction === 'duplicate') {
            $newId = $db->insert($boardsTable, [
                'owner_id' => $userId,
                'title' => substr($board['title'] . ' copy', 0, 160),
                'description' => $board['description'],
                'folder' => $board['folder'],
                'status' => 'active',
                'viewport_json' => $board['viewport_json'],
                'last_edited_by' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $objects = $db->fetchAll("SELECT * FROM {$objectsTable} WHERE board_id = ? ORDER BY z_index ASC", [$boardId]);
            foreach ($objects as $object) {
                $db->insert($objectsTable, [
                    'board_id' => $newId,
                    'object_type' => $object['object_type'],
                    'data_json' => $object['data_json'],
                    'z_index' => $object['z_index'],
                    'created_by' => $userId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
            redirect(plugin_url($slug, '?board=' . (int)$newId));
        }
    }
}

$boardId = (int)($_GET['board'] ?? 0);
$board = $boardId ? whiteboard_board($boardId, $userId) : null;
$boards = $db->fetchAll(
    "SELECT b.*, COUNT(o.id) AS object_count
     FROM {$boardsTable} b
     LEFT JOIN {$objectsTable} o ON o.board_id = b.id
     WHERE b.owner_id = ? AND b.status != 'deleted'
     GROUP BY b.id
     ORDER BY CASE WHEN b.status = 'active' THEN 0 ELSE 1 END, b.updated_at DESC",
    [$userId]
);
$objects = [];
$viewport = ['x' => 0, 'y' => 0, 'zoom' => 1];
if ($board) {
    $rows = $db->fetchAll("SELECT data_json FROM {$objectsTable} WHERE board_id = ? ORDER BY z_index ASC", [$board['id']]);
    foreach ($rows as $row) {
        $object = json_decode($row['data_json'], true);
        if (is_array($object)) $objects[] = $object;
    }
    $savedViewport = json_decode($board['viewport_json'] ?? '', true);
    if (is_array($savedViewport)) $viewport = array_merge($viewport, $savedViewport);
}

$boot = [
    'csrf' => Security::csrfToken(),
    'board' => $board ? ['id' => (int)$board['id'], 'title' => $board['title'], 'status' => $board['status']] : null,
    'objects' => $objects,
    'viewport' => $viewport,
    'assetBase' => plugin_asset($slug, ''),
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Awan Whiteboard</title>
    <link rel="stylesheet" href="<?= e(plugin_asset($slug, 'whiteboard.css')) ?>">
</head>
<body class="wb-body">
<div class="wb-app">
    <header class="wb-topbar">
        <a class="wb-brand" href="/plugins" aria-label="Back to Awan Tools">
            <span class="wb-brand-mark">✦</span><span>Awan <strong>Whiteboard</strong></span>
        </a>
        <?php if ($board): ?>
            <div class="wb-board-name">
                <span class="wb-status-dot"></span>
                <span id="wbBoardTitle"><?= e($board['title']) ?></span>
                <small id="wbSaveState">Saved</small>
            </div>
            <div class="wb-top-actions">
                <a class="wb-icon-button" href="<?= e(plugin_url($slug)) ?>" title="All boards">▦</a>
                <button class="wb-button wb-button-quiet" id="wbExportBtn" type="button">Export SVG</button>
            </div>
        <?php else: ?>
            <div class="wb-top-actions"><a class="wb-button wb-button-quiet" href="/plugins">All tools</a></div>
        <?php endif; ?>
    </header>

    <?php if (!$board): ?>
        <main class="wb-library">
            <section class="wb-library-hero">
                <div>
                    <p class="wb-eyebrow">YOUR VISUAL WORKSPACE</p>
                    <h1>Think out loud.<br><em>Make it visible.</em></h1>
                    <p class="wb-lead">A calm, vector-first canvas for lessons, study notes, diagrams, and ideas. Everything stays in your AwanTools workspace.</p>
                </div>
                <form class="wb-new-board" method="post">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="create_board">
                    <label for="newBoardTitle">Start a new board</label>
                    <div class="wb-new-board-row">
                        <input id="newBoardTitle" name="title" value="Untitled board" maxlength="160" required>
                        <button class="wb-button wb-button-primary" type="submit">Create board <span>↗</span></button>
                    </div>
                </form>
            </section>
            <section class="wb-library-section">
                <div class="wb-section-heading">
                    <div><h2>My boards</h2><p><?= count($boards) ?> <?= count($boards) === 1 ? 'board' : 'boards' ?> in your workspace</p></div>
                </div>
                <?php if (!$boards): ?>
                    <div class="wb-empty"><div class="wb-empty-icon">✎</div><h3>Your canvas is waiting</h3><p>Create your first board to start sketching.</p></div>
                <?php else: ?>
                    <div class="wb-board-grid">
                        <?php foreach ($boards as $item): ?>
                            <article class="wb-board-card <?= $item['status'] === 'archived' ? 'is-archived' : '' ?>">
                                <a href="<?= e(plugin_url($slug, '?board=' . (int)$item['id'])) ?>" class="wb-board-preview">
                                    <span class="wb-preview-grid"></span><span class="wb-preview-scribble"></span><span class="wb-preview-note"></span>
                                    <span class="wb-card-open">Open ↗</span>
                                </a>
                                <div class="wb-board-card-body">
                                    <div><h3><?= e($item['title']) ?></h3><p><?= (int)$item['object_count'] ?> objects · <?= e(ucfirst($item['status'])) ?></p></div>
                                    <div class="wb-card-actions">
                                        <form method="post" onsubmit="return confirm('Delete this board?')">
                                            <?= Security::csrfField() ?>
                                            <input type="hidden" name="action" value="board_action"><input type="hidden" name="board_id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="board_action" value="delete">
                                            <button class="wb-kebab" title="Delete board" type="submit">×</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    <?php else: ?>
        <div class="wb-workspace">
            <aside class="wb-toolbar" aria-label="Drawing tools">
                <button class="wb-tool is-active" data-tool="select" title="Select (V)">⌁<span>Select</span></button>
                <button class="wb-tool" data-tool="pan" title="Pan (H)">✋<span>Pan</span></button>
                <div class="wb-tool-separator"></div>
                <button class="wb-tool" data-tool="pencil" title="Pencil (P)">✎<span>Pencil</span></button>
                <button class="wb-tool" data-tool="pen" title="Pen">╱<span>Pen</span></button>
                <button class="wb-tool" data-tool="highlighter" title="Highlighter">▰<span>Highlight</span></button>
                <button class="wb-tool" data-tool="eraser" title="Eraser (E)">⌫<span>Eraser</span></button>
                <div class="wb-tool-separator"></div>
                <button class="wb-tool" data-tool="rect" title="Rectangle">□<span>Rectangle</span></button>
                <button class="wb-tool" data-tool="circle" title="Circle">○<span>Circle</span></button>
                <button class="wb-tool" data-tool="line" title="Line">╱<span>Line</span></button>
                <button class="wb-tool" data-tool="arrow" title="Arrow">➜<span>Arrow</span></button>
                <button class="wb-tool" data-tool="text" title="Text (T)">T<span>Text</span></button>
                <button class="wb-tool" data-tool="sticky" title="Sticky note">▤<span>Sticky</span></button>
            </aside>
            <main class="wb-canvas-shell">
                <div class="wb-canvas-controls">
                    <div class="wb-control-group">
                        <button id="wbUndo" type="button" title="Undo">↶</button><button id="wbRedo" type="button" title="Redo">↷</button>
                    </div>
                    <div class="wb-control-group wb-view-controls">
                        <button id="wbZoomOut" type="button">−</button><span id="wbZoomLabel">100%</span><button id="wbZoomIn" type="button">+</button><button id="wbFit" type="button" title="Fit board">Fit</button>
                    </div>
                    <div class="wb-control-group">
                        <button id="wbGrid" type="button" class="is-on">▦ <span>Grid</span></button><button id="wbSnap" type="button">⌖ <span>Snap</span></button>
                    </div>
                </div>
                <svg id="wbCanvas" class="wb-canvas" xmlns="http://www.w3.org/2000/svg" tabindex="0" aria-label="Whiteboard canvas">
                    <defs><pattern id="wbGridPattern" width="24" height="24" patternUnits="userSpaceOnUse"><path d="M 24 0 L 0 0 0 24" fill="none" stroke="#d6dce8" stroke-width="1"/></pattern></defs>
                    <rect id="wbGridRect" x="-100000" y="-100000" width="200000" height="200000" fill="url(#wbGridPattern)"></rect>
                    <g id="wbObjectLayer"></g><g id="wbDraftLayer"></g>
                </svg>
                <div class="wb-canvas-hint">Scroll to zoom · Space + drag to pan · Your work autosaves</div>
            </main>
            <aside class="wb-properties">
                <div class="wb-properties-head"><span>Board</span><button id="wbToggleProperties" type="button">×</button></div>
                <div class="wb-properties-body">
                    <label class="wb-field-label" for="wbTitleInput">Name</label>
                    <input class="wb-field" id="wbTitleInput" value="<?= e($board['title']) ?>" maxlength="160">
                    <div class="wb-property-section"><p class="wb-property-label">Stroke</p><div class="wb-color-row"><button class="wb-color is-active" data-color="#182230" style="--swatch:#182230"></button><button class="wb-color" data-color="#ff6b5f" style="--swatch:#ff6b5f"></button><button class="wb-color" data-color="#ffc857" style="--swatch:#ffc857"></button><button class="wb-color" data-color="#4c9aff" style="--swatch:#4c9aff"></button><button class="wb-color" data-color="#58c7a4" style="--swatch:#58c7a4"></button><input id="wbCustomColor" type="color" value="#182230" title="Custom color"></div></div>
                    <div class="wb-property-section"><p class="wb-property-label">Brush size <span id="wbWidthLabel">4 px</span></p><input id="wbWidth" type="range" min="1" max="32" value="4"></div>
                    <div class="wb-property-section"><p class="wb-property-label">Actions</p>
                        <form method="post" class="wb-action-form"><input type="hidden" name="_csrf" value="<?= e(Security::csrfToken()) ?>"><input type="hidden" name="action" value="board_action"><input type="hidden" name="board_id" value="<?= (int)$board['id'] ?>"><input type="hidden" name="board_action" value="duplicate"><button type="submit" class="wb-wide-button">Duplicate board</button></form>
                        <form method="post" class="wb-action-form"><input type="hidden" name="_csrf" value="<?= e(Security::csrfToken()) ?>"><input type="hidden" name="action" value="board_action"><input type="hidden" name="board_id" value="<?= (int)$board['id'] ?>"><input type="hidden" name="board_action" value="<?= $board['status'] === 'archived' ? 'restore' : 'archive' ?>"><button type="submit" class="wb-wide-button"><?= $board['status'] === 'archived' ? 'Restore board' : 'Archive board' ?></button></form>
                        <form method="post" class="wb-action-form" onsubmit="return confirm('Delete this board?')"><input type="hidden" name="_csrf" value="<?= e(Security::csrfToken()) ?>"><input type="hidden" name="action" value="board_action"><input type="hidden" name="board_id" value="<?= (int)$board['id'] ?>"><input type="hidden" name="board_action" value="delete"><button type="submit" class="wb-wide-button wb-danger-button">Delete board</button></form>
                    </div>
                    <div class="wb-property-section wb-roadmap-note"><p class="wb-property-label">Coming next</p><p>Selection, sharing, handwriting recognition, classrooms, and offline sync are planned phases in the architecture.</p></div>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>
<?php if ($board): ?>
<script>window.AWAN_WHITEBOARD = <?= json_encode($boot, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= e(plugin_asset($slug, 'whiteboard.js')) ?>"></script>
<?php endif; ?>
</body>
</html>