<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$logger   = Logger::getInstance($db);
$maxSizeMb = (int)$settings->get('media_max_size_mb', '10');
$maxBytes  = $maxSizeMb * 1024 * 1024;

// ─── Allowed file types ────────────────────────────────────────────────────────
$allowedMimes = [
    'image/jpeg'      => ['jpg',  'image'],
    'image/png'       => ['png',  'image'],
    'image/gif'       => ['gif',  'image'],
    'image/webp'      => ['webp', 'image'],
    'image/svg+xml'   => ['svg',  'image'],
    'application/pdf' => ['pdf',  'document'],
    'text/plain'      => ['txt',  'document'],
    'text/csv'        => ['csv',  'document'],
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx', 'document'],
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'       => ['xlsx', 'document'],
    'application/zip' => ['zip',  'archive'],
    'application/x-zip-compressed' => ['zip', 'archive'],
];

// ─── Helpers ──────────────────────────────────────────────────────────────────
function formatBytes(int $bytes): string {
    if ($bytes < 1024)       return $bytes . ' B';
    if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 2) . ' MB';
}

function mediaTypeIcon(string $type, string $mime): string {
    return match($type) {
        'image'    => 'IMG',
        'document' => (str_contains($mime, 'pdf') ? 'PDF' : 'DOC'),
        'archive'  => 'ZIP',
        default    => 'FILE',
    };
}

// ─── POST Handler ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = Security::sanitize($_POST['action'] ?? '');

    // ── Upload ──
    if ($action === 'upload') {
        $files   = $_FILES['files'] ?? [];
        $folder  = Security::sanitize($_POST['folder'] ?? 'general');
        $errors  = [];
        $uploads = 0;

        if (empty($files['name'][0])) {
            Session::flash('danger', 'No files selected.');
            redirect('/admin/media');
        }

        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = "Upload error for: " . htmlspecialchars($files['name'][$i]);
                continue;
            }

            $originalName = $files['name'][$i];
            $tmpPath      = $files['tmp_name'][$i];
            $size         = $files['size'][$i];

            if ($size > $maxBytes) {
                $errors[] = "{$originalName}: exceeds {$maxSizeMb}MB limit.";
                continue;
            }

            // Detect MIME type reliably
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpPath);

            if (!isset($allowedMimes[$mimeType])) {
                $errors[] = "{$originalName}: file type not allowed ({$mimeType}).";
                continue;
            }

            [$ext, $fileType] = $allowedMimes[$mimeType];

            // Generate safe unique filename
            $safeName = preg_replace('/[^a-z0-9\-_]/i', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $safeName = strtolower(substr($safeName, 0, 60));
            $filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safeName . '.' . $ext;
            $filePath = UPLOADS_PATH . '/' . $filename;
            $urlPath  = '/storage/uploads/' . $filename;

            if (!move_uploaded_file($tmpPath, $filePath)) {
                $errors[] = "{$originalName}: could not save file.";
                continue;
            }

            // Image dimensions
            $width = $height = null;
            if ($fileType === 'image' && $ext !== 'svg') {
                $info = @getimagesize($filePath);
                if ($info) { $width = $info[0]; $height = $info[1]; }
            }

            $db->insert('media', [
                'filename'      => $filename,
                'original_name' => $originalName,
                'file_path'     => $filePath,
                'url_path'      => $urlPath,
                'mime_type'     => $mimeType,
                'file_type'     => $fileType,
                'file_size'     => $size,
                'width'         => $width,
                'height'        => $height,
                'folder'        => $folder,
                'uploader_id'   => $auth->id(),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
            $uploads++;
            $logger->info("Media uploaded: {$filename}", [], $auth->id());
        }

        if ($uploads > 0) {
            Session::flash('success', "{$uploads} file" . ($uploads > 1 ? 's' : '') . " uploaded successfully.");
        }
        foreach ($errors as $err) {
            Session::flash('danger', $err);
        }
        redirect('/admin/media');
    }

    // ── Delete ──
    if ($action === 'delete') {
        $mediaId = (int)($_POST['media_id'] ?? 0);
        $item    = $db->fetch("SELECT * FROM media WHERE id = ?", [$mediaId]);
        if ($item) {
            if (file_exists($item['file_path'])) {
                @unlink($item['file_path']);
            }
            $db->delete('media', 'id = ?', [$mediaId]);
            $logger->info("Media deleted: {$item['filename']}", [], $auth->id());
            Session::flash('success', 'File deleted.');
        }
        redirect('/admin/media');
    }

    // ── Update alt text ──
    if ($action === 'update_alt') {
        $mediaId = (int)($_POST['media_id'] ?? 0);
        $altText = Security::sanitize($_POST['alt_text'] ?? '');
        $db->update('media', ['alt_text' => $altText], 'id = ?', [$mediaId]);
        redirect('/admin/media');
    }

    redirect('/admin/media');
}

// ─── Query / Filter ───────────────────────────────────────────────────────────
$filterType   = Security::sanitize($_GET['type']   ?? 'all');
$searchQuery  = Security::sanitize($_GET['search'] ?? '');
$filterFolder = Security::sanitize($_GET['folder'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 24;
$offset       = ($page - 1) * $perPage;

$where  = '1=1';
$params = [];

if ($filterType !== 'all' && in_array($filterType, ['image','document','archive','other'])) {
    $where .= ' AND file_type = ?';
    $params[] = $filterType;
}
if ($searchQuery) {
    $where .= ' AND (original_name LIKE ? OR filename LIKE ?)';
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}
if ($filterFolder) {
    $where .= ' AND folder = ?';
    $params[] = $filterFolder;
}

$total   = $db->count('media', $where, $params);
$items   = $db->fetchAll("SELECT * FROM media WHERE {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}", $params);
$pages   = (int)ceil($total / $perPage);

// Stats
$totalFiles  = $db->count('media');
$totalImages = $db->count('media', "file_type = 'image'");
$totalSizeRow = $db->fetch("SELECT SUM(file_size) as s FROM media");
$totalSize   = (int)($totalSizeRow['s'] ?? 0);

// All folders
$folderRows  = $db->fetchAll("SELECT DISTINCT folder FROM media ORDER BY folder ASC");
$folders     = array_column($folderRows, 'folder');

// ─── View ─────────────────────────────────────────────────────────────────────
ob_start();
?>
<style>
/* ── Upload drop zone ── */
.drop-zone {
    border: 2px dashed var(--color-border);
    border-radius: var(--radius-medium);
    padding: 40px 24px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    background: var(--color-background);
}
.drop-zone.dragging {
    border-color: var(--color-primary);
    background: var(--color-primary-light);
}
.drop-zone-icon { font-size: 32px; margin-bottom: 12px; opacity: 0.5; }
.drop-zone-text { font-size: 14px; font-weight: 600; color: var(--color-text); margin-bottom: 6px; }
.drop-zone-sub  { font-size: 12px; color: var(--color-text-muted); }

/* ── Filter bar ── */
.media-filters {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 16px;
}
.filter-tab {
    padding: 5px 12px;
    border-radius: var(--radius-full);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    border: 1px solid var(--color-border);
    color: var(--color-text-secondary);
    background: var(--color-surface);
    transition: all 0.15s;
}
.filter-tab:hover, .filter-tab.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: #fff;
}

/* ── Media grid ── */
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
}
.media-item {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-medium);
    overflow: hidden;
    transition: border-color 0.15s, box-shadow 0.15s;
    position: relative;
}
.media-item:hover { border-color: var(--color-primary); box-shadow: var(--shadow-small); }
.media-item:hover .media-actions { opacity: 1; }

.media-thumb {
    width: 100%;
    height: 110px;
    object-fit: cover;
    display: block;
    background: var(--color-background);
}
.media-icon-placeholder {
    width: 100%;
    height: 110px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-background);
    font-size: 32px;
}
.media-info {
    padding: 8px 10px;
    border-top: 1px solid var(--color-border);
}
.media-name {
    font-size: 11px;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: var(--color-text);
    margin-bottom: 2px;
}
.media-meta {
    font-size: 10px;
    color: var(--color-text-muted);
}
.media-actions {
    position: absolute;
    top: 6px;
    right: 6px;
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.15s;
}
.media-action-btn {
    width: 28px;
    height: 28px;
    border-radius: var(--radius-small);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    backdrop-filter: blur(4px);
}
.media-copy-btn { background: rgba(255,255,255,0.92); color: var(--color-text); }
.media-del-btn  { background: rgba(239,68,68,0.92);  color: #fff; }
.media-copy-btn:hover { background: var(--color-primary); color: #fff; }
.media-del-btn:hover  { background: var(--color-danger); }

/* ── Upload progress ── */
.upload-progress { display:none; margin-top:12px; }
.upload-bar-wrap { background: var(--color-border); border-radius: var(--radius-full); height: 6px; margin-top: 6px; }
.upload-bar { height: 6px; background: var(--color-primary); border-radius: var(--radius-full); width: 0%; transition: width 0.2s; }
</style>

<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Media Library</div>
            <div class="page-subtitle">
                <?= number_format($totalFiles) ?> file<?= $totalFiles !== 1 ? 's' : '' ?>
                · <?= number_format($totalImages) ?> images
                · <?= formatBytes($totalSize) ?> used
            </div>
        </div>
    </div>
</div>

<div class="page-body">

    <!-- Upload card -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-header">
            <span class="card-title">Upload Files</span>
            <span class="text-muted text-sm">Max <?= $maxSizeMb ?>MB per file · Images, PDFs, Docs, ZIP</span>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" id="upload-form">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="upload">
                <input type="hidden" name="folder" id="upload-folder-input" value="general">

                <div class="drop-zone" id="drop-zone" onclick="document.getElementById('file-input').click()">
                    <div class="drop-zone-icon">☁️</div>
                    <div class="drop-zone-text">Drop files here or click to browse</div>
                    <div class="drop-zone-sub">
                        JPG, PNG, GIF, WEBP, SVG, PDF, TXT, CSV, DOCX, XLSX, ZIP
                    </div>
                    <input type="file" id="file-input" name="files[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.pdf,.txt,.csv,.docx,.xlsx,.zip" style="display:none">
                </div>

                <div class="upload-progress" id="upload-progress">
                    <div style="font-size:12px;color:var(--color-text-secondary)" id="upload-status">Uploading…</div>
                    <div class="upload-bar-wrap"><div class="upload-bar" id="upload-bar"></div></div>
                </div>

                <div style="display:flex;gap:12px;align-items:center;margin-top:12px;flex-wrap:wrap">
                    <div style="display:flex;align-items:center;gap:8px">
                        <label class="form-label" style="margin-bottom:0;white-space:nowrap">Folder:</label>
                        <select class="form-input" style="width:auto" id="upload-folder-select" onchange="document.getElementById('upload-folder-input').value=this.value">
                            <option value="general">General</option>
                            <option value="images">Images</option>
                            <option value="documents">Documents</option>
                            <?php foreach ($folders as $f): if (in_array($f, ['general','images','documents'])) continue; ?>
                            <option value="<?= e($f) ?>"><?= e(ucfirst($f)) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" id="upload-btn" disabled>Upload Selected</button>
                    <span id="selected-files-label" class="text-muted text-sm"></span>
                </div>
            </form>
        </div>
    </div>

    <!-- Filters + Search -->
    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px">
        <div class="media-filters">
            <?php
            $tabs = ['all' => 'All', 'image' => 'Images', 'document' => 'Documents', 'archive' => 'Archives'];
            foreach ($tabs as $k => $label):
                $url = '/admin/media?type=' . $k . ($searchQuery ? '&search=' . urlencode($searchQuery) : '');
            ?>
            <a href="<?= $url ?>" class="filter-tab <?= $filterType === $k ? 'active' : '' ?>"><?= $label ?></a>
            <?php endforeach ?>
        </div>

        <form method="GET" action="/admin/media" style="display:flex;gap:8px;margin-left:auto">
            <?php if ($filterType !== 'all'): ?><input type="hidden" name="type" value="<?= e($filterType) ?>"><?php endif ?>
            <input type="text" name="search" class="form-input" placeholder="Search files…" value="<?= e($searchQuery) ?>" style="width:200px">
            <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            <?php if ($searchQuery): ?><a href="/admin/media" class="btn btn-ghost btn-sm">Clear</a><?php endif ?>
        </form>
    </div>

    <!-- Grid -->
    <?php if (empty($items)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon"></div>
            <h3><?= $searchQuery ? 'No files match your search' : 'No files uploaded yet' ?></h3>
            <p><?= $searchQuery ? 'Try a different search term.' : 'Upload files using the form above.' ?></p>
        </div>
    </div>
    <?php else: ?>
    <div class="media-grid" id="media-grid">
        <?php foreach ($items as $item): ?>
        <div class="media-item" data-id="<?= $item['id'] ?>">

            <!-- Thumbnail or icon -->
            <?php if ($item['file_type'] === 'image' && $item['mime_type'] !== 'image/svg+xml'): ?>
            <img class="media-thumb" src="<?= e($item['url_path']) ?>" alt="<?= e($item['alt_text'] ?? $item['original_name']) ?>" loading="lazy">
            <?php elseif ($item['file_type'] === 'image'): ?>
            <div class="media-icon-placeholder"><?= mediaTypeIcon($item['file_type'], $item['mime_type']) ?></div>
            <?php else: ?>
            <div class="media-icon-placeholder"><?= mediaTypeIcon($item['file_type'], $item['mime_type']) ?></div>
            <?php endif ?>

            <!-- Hover actions -->
            <div class="media-actions">
                <button class="media-action-btn media-copy-btn" title="Copy URL"
                        onclick="copyUrl('<?= e($item['url_path']) ?>', this)">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                </button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this file permanently?')">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="media_id" value="<?= $item['id'] ?>">
                    <button type="submit" class="media-action-btn media-del-btn" title="Delete">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    </button>
                </form>
            </div>

            <!-- File info -->
            <div class="media-info">
                <div class="media-name" title="<?= e($item['original_name']) ?>"><?= e($item['original_name']) ?></div>
                <div class="media-meta">
                    <?= formatBytes($item['file_size']) ?>
                    <?php if ($item['width'] && $item['height']): ?>
                    · <?= $item['width'] ?>×<?= $item['height'] ?>
                    <?php endif ?>
                </div>
            </div>
        </div>
        <?php endforeach ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div style="display:flex;justify-content:center;gap:8px;margin-top:24px;flex-wrap:wrap">
        <?php for ($p = 1; $p <= $pages; $p++):
            $isActive = $p === $page;
            $pUrl = '/admin/media?page=' . $p
                  . ($filterType !== 'all' ? '&type=' . $filterType : '')
                  . ($searchQuery ? '&search=' . urlencode($searchQuery) : '');
        ?>
        <a href="<?= $pUrl ?>" class="btn <?= $isActive ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $p ?></a>
        <?php endfor ?>
    </div>
    <?php endif ?>
    <?php endif ?>

</div><!-- /page-body -->

<!-- Copy URL toast -->
<div id="copy-toast" style="
    position:fixed; bottom:24px; right:24px; z-index:9999;
    background:var(--color-text); color:#fff;
    padding:10px 16px; border-radius:var(--radius-medium);
    font-size:13px; font-weight:500;
    box-shadow:var(--shadow-medium);
    opacity:0; transition:opacity 0.2s; pointer-events:none;
">URL copied!</div>

<script>
// ── Copy URL ──────────────────────────────────────────────────────────────────
function copyUrl(url, btn) {
    navigator.clipboard.writeText(window.location.origin + url).then(() => {
        const toast = document.getElementById('copy-toast');
        toast.style.opacity = '1';
        setTimeout(() => toast.style.opacity = '0', 2000);
    });
}

// ── Drag & Drop ───────────────────────────────────────────────────────────────
const dropZone  = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');
const uploadBtn = document.getElementById('upload-btn');
const label     = document.getElementById('selected-files-label');

dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('dragging'); });
dropZone.addEventListener('dragleave', e => { dropZone.classList.remove('dragging'); });
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragging');
    const dt = new DataTransfer();
    for (const f of e.dataTransfer.files) dt.items.add(f);
    fileInput.files = dt.files;
    updateLabel();
});

fileInput.addEventListener('change', updateLabel);

function updateLabel() {
    const n = fileInput.files.length;
    uploadBtn.disabled = n === 0;
    label.textContent  = n > 0 ? `${n} file${n > 1 ? 's' : ''} selected` : '';
}

// ── XHR upload with progress bar ──────────────────────────────────────────────
document.getElementById('upload-form').addEventListener('submit', function(e) {
    if (fileInput.files.length === 0) return;
    e.preventDefault();

    const prog   = document.getElementById('upload-progress');
    const bar    = document.getElementById('upload-bar');
    const status = document.getElementById('upload-status');
    prog.style.display = 'block';
    uploadBtn.disabled  = true;

    const fd = new FormData(this);
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/media');

    xhr.upload.onprogress = ev => {
        if (ev.lengthComputable) {
            const pct = Math.round((ev.loaded / ev.total) * 100);
            bar.style.width = pct + '%';
            status.textContent = `Uploading… ${pct}%`;
        }
    };
    xhr.onload = () => {
        if (xhr.status === 200 || xhr.status === 302) {
            window.location.reload();
        } else {
            status.textContent = 'Upload failed — please try again.';
            uploadBtn.disabled = false;
        }
    };
    xhr.onerror = () => {
        status.textContent = 'Network error.';
        uploadBtn.disabled = false;
    };
    xhr.send(fd);
});
</script>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Media Library', $content, ['section' => 'media']);
