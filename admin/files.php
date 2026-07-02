<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireSuperAdmin();

// ─── Configuration ────────────────────────────────────────────────────────────
// Root that the file browser is allowed to access. Default: workspace parent of AWAN_ROOT.
// On shared hosting this is typically /home/username or public_html parent.
define('FILES_ROOT', dirname(AWAN_ROOT));
define('FILES_ROOT_NAME', basename(FILES_ROOT));

// ─── Safe path resolution ─────────────────────────────────────────────────────
function files_safe_path(string $rel): string {
    $rel   = str_replace("\0", '', $rel);
    $full  = FILES_ROOT . '/' . ltrim($rel, '/');
    // Resolve existing paths; for new paths resolve parent
    $real  = realpath($full);
    if ($real === false) {
        $parent = realpath(dirname($full));
        if ($parent === false) {
            throw new RuntimeException('Invalid path');
        }
        $real = $parent . DIRECTORY_SEPARATOR . basename($full);
    }
    $rootReal = realpath(FILES_ROOT);
    if ($rootReal === false) throw new RuntimeException('Files root does not exist');
    if (!str_starts_with($real, $rootReal . DIRECTORY_SEPARATOR) && $real !== $rootReal) {
        throw new RuntimeException('Path outside allowed root');
    }
    return $real;
}

function files_rel(string $abs): string {
    return ltrim(substr($abs, strlen(realpath(FILES_ROOT))), DIRECTORY_SEPARATOR);
}

// ─── AJAX handlers ───────────────────────────────────────────────────────────
if (!empty($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = Security::sanitize($_GET['action'] ?? '');

    // Verify CSRF for all mutating actions
    if (in_array($action, ['write', 'mkdir', 'touch', 'delete', 'rename', 'upload'])) {
        $token = $_SERVER['HTTP_X_Csrf_Token'] ?? $_POST['_csrf'] ?? '';
        if (!hash_equals(Session::csrfToken(), $token)) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'error' => 'CSRF token mismatch']);
            exit;
        }
    }

    try {
        switch ($action) {
            case 'list':
                $path = files_safe_path($_GET['path'] ?? '');
                if (!is_dir($path)) throw new RuntimeException('Not a directory');
                $items = [];
                $scan  = @scandir($path);
                if ($scan === false) throw new RuntimeException('Cannot read directory');
                foreach ($scan as $name) {
                    if ($name === '.' || $name === '..') continue;
                    $full    = $path . DIRECTORY_SEPARATOR . $name;
                    $isDir   = is_dir($full);
                    $items[] = [
                        'name'     => $name,
                        'path'     => files_rel($full),
                        'is_dir'   => $isDir,
                        'size'     => $isDir ? null : filesize($full),
                        'modified' => filemtime($full),
                        'readable' => is_readable($full),
                        'writable' => is_writable($full),
                    ];
                }
                usort($items, fn($a, $b) => ($b['is_dir'] - $a['is_dir']) ?: strcasecmp($a['name'], $b['name']));
                echo json_encode(['ok' => true, 'items' => $items]);
                break;

            case 'read':
                $path = files_safe_path($_GET['path'] ?? '');
                if (!is_file($path)) throw new RuntimeException('Not a file');
                if (!is_readable($path)) throw new RuntimeException('File not readable');
                $size = filesize($path);
                if ($size > 2 * 1024 * 1024) throw new RuntimeException('File too large to edit (max 2 MB)');
                echo json_encode(['ok' => true, 'content' => file_get_contents($path), 'size' => $size]);
                break;

            case 'write':
                $path    = files_safe_path($_POST['path'] ?? '');
                $content = $_POST['content'] ?? '';
                if (!is_writable(dirname($path)) && !is_writable($path)) {
                    throw new RuntimeException('File or directory is not writable');
                }
                $bytes = file_put_contents($path, $content, LOCK_EX);
                if ($bytes === false) throw new RuntimeException('Write failed');
                $logger->info("File edited via browser: " . files_rel($path), [], $auth->id());
                echo json_encode(['ok' => true, 'bytes' => $bytes]);
                break;

            case 'mkdir':
                $path = files_safe_path($_POST['path'] ?? '');
                if (file_exists($path)) throw new RuntimeException('Already exists');
                if (!@mkdir($path, 0755, true)) throw new RuntimeException('mkdir failed');
                $logger->info("Directory created: " . files_rel($path), [], $auth->id());
                echo json_encode(['ok' => true]);
                break;

            case 'touch':
                $path = files_safe_path($_POST['path'] ?? '');
                if (file_exists($path)) throw new RuntimeException('File already exists');
                if (file_put_contents($path, '') === false) throw new RuntimeException('Could not create file');
                $logger->info("File created: " . files_rel($path), [], $auth->id());
                echo json_encode(['ok' => true]);
                break;

            case 'delete':
                $path = files_safe_path($_POST['path'] ?? '');
                if (!file_exists($path)) throw new RuntimeException('Path does not exist');
                if (is_dir($path)) {
                    if (!files_rmdir_recursive($path)) throw new RuntimeException('Could not delete directory');
                } else {
                    if (!@unlink($path)) throw new RuntimeException('Could not delete file');
                }
                $logger->info("Deleted via file browser: " . files_rel($path), [], $auth->id());
                echo json_encode(['ok' => true]);
                break;

            case 'rename':
                $from = files_safe_path($_POST['from'] ?? '');
                $to   = files_safe_path($_POST['to']   ?? '');
                if (!file_exists($from)) throw new RuntimeException('Source does not exist');
                if (file_exists($to)) throw new RuntimeException('Destination already exists');
                if (!@rename($from, $to)) throw new RuntimeException('Rename failed');
                $logger->info("Renamed: " . files_rel($from) . " → " . files_rel($to), [], $auth->id());
                echo json_encode(['ok' => true]);
                break;

            default:
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Unknown action']);
        }
    } catch (RuntimeException $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

function files_rmdir_recursive(string $path): bool {
    if (!is_dir($path)) return false;
    $items = @scandir($path);
    if ($items === false) return false;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full)) {
            if (!files_rmdir_recursive($full)) return false;
        } else {
            if (!@unlink($full)) return false;
        }
    }
    return @rmdir($path);
}

// ─── Page HTML ────────────────────────────────────────────────────────────────
ob_start();
?>
<style>
.fb-layout { display:flex; height:calc(100vh - 56px); overflow:hidden; }
.fb-tree { width:260px; min-width:180px; max-width:380px; border-right:1px solid var(--color-border); overflow-y:auto; background:var(--color-surface); display:flex; flex-direction:column; resize:horizontal; }
.fb-tree-header { padding:10px 12px; border-bottom:1px solid var(--color-border); display:flex; align-items:center; gap:6px; flex-shrink:0; }
.fb-tree-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--color-text-muted); flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.fb-tree-body { flex:1; overflow-y:auto; padding:4px 0; }
.fb-item { display:flex; align-items:center; gap:5px; padding:3px 8px; cursor:pointer; font-size:12px; user-select:none; border-radius:3px; white-space:nowrap; }
.fb-item:hover { background:var(--color-hover,rgba(0,0,0,.05)); }
.fb-item.active { background:var(--color-primary); color:#fff; }
.fb-item.active .fb-item-icon { color:#fff !important; }
.fb-item-icon { width:14px; flex-shrink:0; }
.fb-item-name { overflow:hidden; text-overflow:ellipsis; flex:1; }
.fb-item-indent { padding-left:14px; }
.fb-editor { flex:1; display:flex; flex-direction:column; overflow:hidden; }
.fb-editor-bar { padding:8px 12px; border-bottom:1px solid var(--color-border); display:flex; align-items:center; gap:8px; flex-shrink:0; background:var(--color-surface); }
.fb-breadcrumb { font-size:12px; color:var(--color-text-muted); font-family:monospace; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.fb-status { font-size:11px; color:var(--color-text-muted); }
.fb-status.unsaved { color:var(--color-warning,#f59e0b); }
#monaco-editor { flex:1; width:100%; min-height:0; }
.fb-welcome { flex:1; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:8px; color:var(--color-text-muted); font-size:13px; }
.fb-ctx-menu { position:fixed; z-index:1000; background:var(--color-surface); border:1px solid var(--color-border); border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,.15); padding:4px 0; min-width:160px; font-size:12px; }
.fb-ctx-item { padding:6px 14px; cursor:pointer; }
.fb-ctx-item:hover { background:var(--color-hover,rgba(0,0,0,.05)); }
.fb-ctx-sep { height:1px; background:var(--color-border); margin:3px 0; }
</style>

<div class="fb-layout">

    <!-- File Tree -->
    <div class="fb-tree" id="fb-tree">
        <div class="fb-tree-header">
            <span class="fb-tree-title" title="<?= e(FILES_ROOT) ?>"><?= e(FILES_ROOT_NAME) ?></span>
            <button class="btn btn-ghost" style="padding:2px 6px;font-size:11px" onclick="newFilePrompt()" title="New File">+F</button>
            <button class="btn btn-ghost" style="padding:2px 6px;font-size:11px" onclick="newFolderPrompt()" title="New Folder">+D</button>
        </div>
        <div class="fb-tree-body" id="fb-tree-body">
            <div style="padding:20px;text-align:center;font-size:12px;color:var(--color-text-muted)">Loading…</div>
        </div>
    </div>

    <!-- Editor Panel -->
    <div class="fb-editor">
        <div class="fb-editor-bar">
            <span class="fb-breadcrumb" id="fb-breadcrumb">Select a file to edit</span>
            <span class="fb-status" id="fb-status"></span>
            <button class="btn btn-primary btn-sm" id="fb-save-btn" style="display:none" onclick="saveCurrentFile()">Save</button>
            <button class="btn btn-ghost btn-sm" id="fb-delete-btn" style="display:none" onclick="deleteCurrentFile()">Delete</button>
        </div>
        <div id="fb-welcome" class="fb-welcome">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.3"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <div>Select a file from the tree to edit</div>
            <div style="font-size:11px">Ctrl+S / Cmd+S to save</div>
        </div>
        <div id="monaco-editor" style="display:none;flex:1"></div>
    </div>
</div>

<!-- Context Menu -->
<div id="fb-ctx" class="fb-ctx-menu" style="display:none">
    <div class="fb-ctx-item" onclick="ctxAction('open')">Open</div>
    <div class="fb-ctx-item" onclick="ctxAction('rename')">Rename</div>
    <div class="fb-ctx-sep"></div>
    <div class="fb-ctx-item" onclick="ctxAction('newfile')">New File Here</div>
    <div class="fb-ctx-item" onclick="ctxAction('newfolder')">New Folder Here</div>
    <div class="fb-ctx-sep"></div>
    <div class="fb-ctx-item" style="color:var(--color-danger,#ef4444)" onclick="ctxAction('delete')">Delete</div>
</div>

<!-- Monaco loader must be before the inline script that calls require.config -->
<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/loader.js"></script>
<script>
var CSRF  = <?= json_encode(Security::csrfToken()) ?>;
var ROOT  = '';
var currentFile = null;
var openDirs    = {};
var ctxTarget   = null;
var editor      = null;
var editorLang  = 'plaintext';

// ─── API ────────────────────────────────────────────────────────────────────

async function api(action, params = {}, method = 'GET') {
    var url = '?ajax=1&action=' + action;
    if (method === 'GET') {
        for (var k in params) url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        var res = await fetch(url);
    } else {
        var body = new FormData();
        body.append('_csrf', CSRF);
        for (var k in params) body.append(k, params[k]);
        var res = await fetch(url, { method: 'POST', body: body });
    }
    return res.json();
}

// ─── Tree ────────────────────────────────────────────────────────────────────

async function loadTree(dirPath, container, indent) {
    container.innerHTML = '<div style="padding:6px ' + (indent + 12) + 'px;font-size:11px;color:var(--color-text-muted)">Loading…</div>';
    var r = await api('list', { path: dirPath });
    if (!r.ok) { container.innerHTML = '<div style="padding:6px ' + (indent+12) + 'px;color:red;font-size:11px">' + r.error + '</div>'; return; }
    container.innerHTML = '';
    r.items.forEach(function(item) {
        var el = document.createElement('div');
        el.className = 'fb-item';
        el.style.paddingLeft = (indent + 8) + 'px';
        el.dataset.path  = item.path;
        el.dataset.isDir = item.is_dir ? '1' : '';
        var icon = item.is_dir ? iconFolder() : iconFile(item.name);
        el.innerHTML = '<span class="fb-item-icon">' + icon + '</span><span class="fb-item-name">' + escHtml(item.name) + '</span>';
        el.addEventListener('click', function(e) { e.stopPropagation(); handleItemClick(item, el); });
        el.addEventListener('contextmenu', function(e) { e.preventDefault(); showCtxMenu(e, item, el); });
        container.appendChild(el);
        if (item.is_dir) {
            var sub = document.createElement('div');
            sub.className = 'fb-sub-' + item.path.replace(/[^a-z0-9]/gi, '_');
            sub.style.display = 'none';
            container.appendChild(sub);
        }
    });
}

function handleItemClick(item, el) {
    if (item.is_dir) {
        var subClass = 'fb-sub-' + item.path.replace(/[^a-z0-9]/gi, '_');
        var sub = document.querySelector('.' + subClass);
        if (!sub) return;
        if (sub.style.display === 'none') {
            sub.style.display = '';
            if (!sub.children.length) loadTree(item.path, sub, parseInt(el.style.paddingLeft) + 12);
            openDirs[item.path] = true;
        } else {
            sub.style.display = 'none';
            delete openDirs[item.path];
        }
    } else {
        if (!item.readable) { alert('File is not readable'); return; }
        openFile(item, el);
    }
}

async function openFile(item, el) {
    if (currentFile && editor) {
        var isDirty = document.getElementById('fb-status').classList.contains('unsaved');
        if (isDirty && !confirm('You have unsaved changes. Open anyway?')) return;
    }
    document.querySelectorAll('.fb-item.active').forEach(e => e.classList.remove('active'));
    if (el) el.classList.add('active');
    document.getElementById('fb-welcome').style.display   = 'none';
    document.getElementById('monaco-editor').style.display = 'flex';
    document.getElementById('fb-breadcrumb').textContent   = '/' + item.path;
    document.getElementById('fb-save-btn').style.display   = item.writable !== false ? '' : 'none';
    document.getElementById('fb-delete-btn').style.display = '';
    document.getElementById('fb-status').textContent       = 'Loading…';
    document.getElementById('fb-status').className         = 'fb-status';

    var r = await api('read', { path: item.path });
    if (!r.ok) { document.getElementById('fb-status').textContent = 'Error: ' + r.error; return; }

    currentFile = item.path;
    var lang = detectLang(item.path);
    if (editor) {
        monaco.editor.setModelLanguage(editor.getModel(), lang);
        editor.setValue(r.content);
    } else {
        require(['vs/editor/editor.main'], function() {
            var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            editor = monaco.editor.create(document.getElementById('monaco-editor'), {
                value: r.content,
                language: lang,
                theme: isDark ? 'vs-dark' : 'vs',
                automaticLayout: true,
                minimap: { enabled: window.innerWidth > 1200 },
                fontSize: 13,
                tabSize: 4,
                wordWrap: 'off',
                scrollBeyondLastLine: false,
                renderLineHighlight: 'all',
                smoothScrolling: true,
            });
            editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, saveCurrentFile);
            editor.onDidChangeModelContent(function() {
                document.getElementById('fb-status').textContent = 'Unsaved changes';
                document.getElementById('fb-status').className   = 'fb-status unsaved';
            });
            // Sync theme toggle
            document.addEventListener('awan-theme-change', function(e) {
                monaco.editor.setTheme(e.detail === 'dark' ? 'vs-dark' : 'vs');
            });
        });
    }
    document.getElementById('fb-status').textContent = '';
    document.getElementById('fb-status').className   = 'fb-status';
}

async function saveCurrentFile() {
    if (!currentFile || !editor) return;
    document.getElementById('fb-status').textContent = 'Saving…';
    var r = await api('write', { path: currentFile, content: editor.getValue() }, 'POST');
    if (r.ok) {
        document.getElementById('fb-status').textContent = 'Saved (' + r.bytes + ' bytes)';
        document.getElementById('fb-status').className   = 'fb-status';
        setTimeout(function() {
            if (document.getElementById('fb-status').textContent.startsWith('Saved'))
                document.getElementById('fb-status').textContent = '';
        }, 3000);
    } else {
        document.getElementById('fb-status').textContent = 'Save failed: ' + r.error;
    }
}

async function deleteCurrentFile() {
    if (!currentFile) return;
    if (!confirm('Delete /' + currentFile + '?\nThis cannot be undone.')) return;
    var r = await api('delete', { path: currentFile }, 'POST');
    if (r.ok) {
        currentFile = null;
        if (editor) { editor.setValue(''); }
        document.getElementById('fb-welcome').style.display    = '';
        document.getElementById('monaco-editor').style.display = 'none';
        document.getElementById('fb-save-btn').style.display   = 'none';
        document.getElementById('fb-delete-btn').style.display = 'none';
        document.getElementById('fb-breadcrumb').textContent   = 'Select a file to edit';
        refreshTree();
    } else {
        alert('Delete failed: ' + r.error);
    }
}

function refreshTree() {
    loadTree('', document.getElementById('fb-tree-body'), 0);
}

// ─── Context Menu ────────────────────────────────────────────────────────────

function showCtxMenu(e, item, el) {
    ctxTarget = { item: item, el: el };
    var menu = document.getElementById('fb-ctx');
    menu.style.display = '';
    menu.style.left    = Math.min(e.pageX, window.innerWidth - 170) + 'px';
    menu.style.top     = Math.min(e.pageY, window.innerHeight - 160) + 'px';
}

document.addEventListener('click', function() {
    document.getElementById('fb-ctx').style.display = 'none';
});

async function ctxAction(action) {
    document.getElementById('fb-ctx').style.display = 'none';
    if (!ctxTarget) return;
    var item = ctxTarget.item;
    switch (action) {
        case 'open':
            if (!item.is_dir) openFile(item, ctxTarget.el);
            break;
        case 'rename':
            var newName = prompt('Rename "' + item.name + '" to:', item.name);
            if (!newName || newName === item.name) return;
            var parentPath = item.path.includes('/') ? item.path.substring(0, item.path.lastIndexOf('/')) : '';
            var newPath    = (parentPath ? parentPath + '/' : '') + newName;
            var r = await api('rename', { from: item.path, to: newPath }, 'POST');
            if (r.ok) {
                if (currentFile === item.path) currentFile = newPath;
                refreshTree();
            } else {
                alert('Rename failed: ' + r.error);
            }
            break;
        case 'newfile':
            newFilePrompt(item.is_dir ? item.path : (item.path.includes('/') ? item.path.substring(0, item.path.lastIndexOf('/')) : ''));
            break;
        case 'newfolder':
            newFolderPrompt(item.is_dir ? item.path : (item.path.includes('/') ? item.path.substring(0, item.path.lastIndexOf('/')) : ''));
            break;
        case 'delete':
            if (!confirm('Delete "' + item.name + '"?\nThis cannot be undone.')) return;
            var r2 = await api('delete', { path: item.path }, 'POST');
            if (r2.ok) { refreshTree(); if (currentFile === item.path) { currentFile = null; document.getElementById('fb-welcome').style.display = ''; document.getElementById('monaco-editor').style.display = 'none'; } }
            else alert('Delete failed: ' + r2.error);
            break;
    }
}

async function newFilePrompt(dir) {
    dir = dir || '';
    var name = prompt('New file name' + (dir ? ' in /' + dir : '') + ':');
    if (!name) return;
    var path = (dir ? dir + '/' : '') + name;
    var r = await api('touch', { path: path }, 'POST');
    if (r.ok) {
        refreshTree();
        setTimeout(function() {
            var el = document.querySelector('[data-path="' + path + '"]');
            if (el) openFile({ path: path, name: name, is_dir: false, readable: true, writable: true }, el);
        }, 400);
    } else {
        alert('Failed: ' + r.error);
    }
}

async function newFolderPrompt(dir) {
    dir = dir || '';
    var name = prompt('New folder name' + (dir ? ' in /' + dir : '') + ':');
    if (!name) return;
    var path = (dir ? dir + '/' : '') + name;
    var r = await api('mkdir', { path: path }, 'POST');
    if (r.ok) refreshTree();
    else alert('Failed: ' + r.error);
}

// ─── Language Detection ──────────────────────────────────────────────────────

function detectLang(filename) {
    var ext = filename.split('.').pop().toLowerCase();
    var map = {
        php:'php', js:'javascript', ts:'typescript', jsx:'javascript', tsx:'typescript',
        html:'html', htm:'html', css:'css', scss:'scss', less:'less',
        json:'json', xml:'xml', yaml:'yaml', yml:'yaml',
        md:'markdown', txt:'plaintext', sh:'shell', bash:'shell',
        py:'python', rb:'ruby', go:'go', rs:'rust', java:'java',
        c:'c', cpp:'cpp', h:'cpp', cs:'csharp', sql:'sql',
        env:'ini', ini:'ini', conf:'ini', toml:'ini',
        svg:'xml', vue:'html', twig:'html',
    };
    return map[ext] || 'plaintext';
}

// ─── Icons ──────────────────────────────────────────────────────────────────

function iconFolder() {
    return '<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#f59e0b"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>';
}

function iconFile(name) {
    var ext = name.split('.').pop().toLowerCase();
    var colors = { php:'#6366f1', js:'#eab308', ts:'#3b82f6', css:'#06b6d4', html:'#f97316', json:'#10b981', md:'#8b5cf6', sh:'#22c55e', sql:'#f59e0b', py:'#3b82f6', env:'#94a3b8' };
    var color  = colors[ext] || '#64748b';
    return '<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:' + color + '"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
}

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ─── Init ────────────────────────────────────────────────────────────────────

// Load Monaco Editor
require.config({ paths: { 'vs': 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs' } });
window.MonacoEnvironment = {
    getWorkerUrl: function(workerId, label) {
        return 'data:text/javascript;charset=utf-8,' + encodeURIComponent(
            'self.MonacoEnvironment={baseUrl:"https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/"};'
            + 'importScripts("https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/base/worker/workerMain.js");'
        );
    }
};

// Load file tree
loadTree('', document.getElementById('fb-tree-body'), 0);
</script>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';

// Render without the default page padding (full height layout)
ob_start();
render_admin('File Browser', $content, ['section' => 'files']);
$html = ob_get_clean();
// Remove main-content padding so file browser fills the panel
echo str_replace(
    'class="main-content"',
    'class="main-content" style="display:flex;flex-direction:column;padding:0"',
    $html
);
