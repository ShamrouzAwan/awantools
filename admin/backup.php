<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$backupDir  = STORAGE_PATH . '/backups';
$isSqlite   = $db->driver() === 'sqlite';

if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

// ─── GET: file downloads (no CSRF — read-only) ────────────────────────────────
$action = $_GET['action'] ?? '';

if ($action === 'download_db' && $isSqlite) {
    $dbPath = DB_SQLITE;
    if (!file_exists($dbPath)) { http_response_code(404); exit('DB file not found.'); }
    $fname = 'awan_db_' . date('Ymd_His') . '.sqlite';
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Content-Length: ' . filesize($dbPath));
    header('Cache-Control: no-cache');
    readfile($dbPath);
    exit;
}

if ($action === 'export_sql') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="awan_export_' . date('Ymd_His') . '.sql"');
    header('Cache-Control: no-cache');

    echo "-- AWAN Platform SQL Export\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Driver: " . $db->driver() . "\n\n";

    $tables = $db->fetchAll(
        "SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
    );

    foreach ($tables as $tbl) {
        echo "\n-- Table: {$tbl['name']}\n";
        echo $tbl['sql'] . ";\n\n";

        $rows = $db->fetchAll("SELECT * FROM `{$tbl['name']}`");
        foreach ($rows as $row) {
            $vals = array_map(function($v) {
                if ($v === null) return 'NULL';
                return "'" . str_replace(["'", "\n", "\r"], ["''", "\\n", "\\r"], (string)$v) . "'";
            }, array_values($row));
            echo "INSERT INTO `{$tbl['name']}` VALUES (" . implode(', ', $vals) . ");\n";
        }
    }
    exit;
}

if ($action === 'download_uploads') {
    if (!class_exists('ZipArchive')) {
        Session::flash('danger', 'ZipArchive extension is not available on this server.');
        redirect('/admin/backup');
    }
    $files   = glob(UPLOADS_PATH . '/*');
    $files   = array_filter($files, 'is_file');
    if (empty($files)) {
        Session::flash('danger', 'No uploaded files to export.');
        redirect('/admin/backup');
    }
    $tmpZip = sys_get_temp_dir() . '/awan_uploads_' . time() . '.zip';
    $zip    = new ZipArchive();
    if ($zip->open($tmpZip, ZipArchive::CREATE) !== true) {
        Session::flash('danger', 'Could not create ZIP archive.');
        redirect('/admin/backup');
    }
    foreach ($files as $f) $zip->addFile($f, basename($f));
    $zip->close();

    $fname = 'awan_uploads_' . date('Ymd_His') . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Content-Length: ' . filesize($tmpZip));
    header('Cache-Control: no-cache');
    readfile($tmpZip);
    @unlink($tmpZip);
    exit;
}

// ─── POST: restore DB ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'restore_db' && $isSqlite) {
        $file = $_FILES['backup_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('danger', 'Upload failed or no file selected.');
            redirect('/admin/backup');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sqlite') {
            Session::flash('danger', 'Only .sqlite files can be restored.');
            redirect('/admin/backup');
        }
        // Back up current DB before replacing
        $stamp      = date('Ymd_His');
        $backupPath = $backupDir . '/db_before_restore_' . $stamp . '.sqlite';
        copy(DB_SQLITE, $backupPath);

        if (!move_uploaded_file($file['tmp_name'], DB_SQLITE)) {
            Session::flash('danger', 'Failed to write database file.');
            redirect('/admin/backup');
        }
        $logger->info("Database restored from upload. Old DB saved as: " . basename($backupPath), [], $auth->id());
        Session::flash('success', 'Database restored. Previous DB saved as: ' . basename($backupPath));
        redirect('/admin/backup');
    }

    if ($postAction === 'delete_backup') {
        $fname = basename($_POST['filename'] ?? '');
        if ($fname && preg_match('/^[a-z0-9_\-\.]+\.sqlite$/i', $fname)) {
            $path = $backupDir . '/' . $fname;
            if (file_exists($path)) @unlink($path);
            Session::flash('success', 'Backup deleted.');
        }
        redirect('/admin/backup');
    }

    redirect('/admin/backup');
}

// ─── View data ────────────────────────────────────────────────────────────────
$dbSize      = $isSqlite && file_exists(DB_SQLITE) ? filesize(DB_SQLITE) : 0;
$uploadsSize = 0;
foreach (glob(UPLOADS_PATH . '/*') ?: [] as $f) {
    if (is_file($f)) $uploadsSize += filesize($f);
}
$mediaCount  = $db->count('media');
$uploadCount = count(glob(UPLOADS_PATH . '/*') ?: []);

// Saved backup files
$backupFiles = glob($backupDir . '/*.sqlite') ?: [];
rsort($backupFiles);

function formatBytes(int $b): string {
    if ($b < 1024)     return $b . ' B';
    if ($b < 1048576)  return round($b / 1024, 1) . ' KB';
    return round($b / 1048576, 2) . ' MB';
}

// ─── View ─────────────────────────────────────────────────────────────────────
ob_start();
?>
<style>
.backup-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; margin-bottom:24px; }
.backup-card { background:var(--color-card); border:1px solid var(--color-border); border-radius:var(--radius-medium); padding:24px; }
.backup-icon { font-size:28px; margin-bottom:12px; }
.backup-title { font-size:15px; font-weight:700; color:var(--color-text); margin-bottom:4px; }
.backup-desc  { font-size:12px; color:var(--color-text-muted); margin-bottom:16px; line-height:1.5; }
.backup-meta  { font-size:11px; color:var(--color-text-secondary); margin-bottom:12px; }
</style>

<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Backup &amp; Restore</div>
            <div class="page-subtitle">Export your data and restore from backups</div>
        </div>
    </div>
</div>

<div class="page-body">

    <!-- Export cards -->
    <div class="backup-grid">

        <?php if ($isSqlite): ?>
        <div class="backup-card">
            <div class="backup-icon">🗄️</div>
            <div class="backup-title">Database File</div>
            <div class="backup-desc">Download the raw SQLite database file. Use this for full database backups and migrations.</div>
            <div class="backup-meta">Current size: <strong><?= formatBytes($dbSize) ?></strong></div>
            <a href="/admin/backup?action=download_db" class="btn btn-primary btn-sm">Download .sqlite</a>
        </div>
        <?php endif ?>

        <div class="backup-card">
            <div class="backup-icon">📋</div>
            <div class="backup-title">SQL Export</div>
            <div class="backup-desc">Export all database tables as SQL INSERT statements. Compatible with MySQL and SQLite for migration.</div>
            <div class="backup-meta">Tables exported with full CREATE + INSERT statements</div>
            <a href="/admin/backup?action=export_sql" class="btn btn-primary btn-sm">Download .sql</a>
        </div>

        <div class="backup-card">
            <div class="backup-icon"></div>
            <div class="backup-title">Media Uploads</div>
            <div class="backup-desc">Download all uploaded media files as a ZIP archive.</div>
            <div class="backup-meta">
                <?= number_format($mediaCount) ?> files in library
                · <?= formatBytes($uploadsSize) ?> total
            </div>
            <a href="/admin/backup?action=download_uploads" class="btn btn-primary btn-sm">Download .zip</a>
        </div>

    </div>

    <!-- Restore DB -->
    <?php if ($isSqlite): ?>
    <div class="card" style="margin-bottom:20px">
        <div class="card-header">
            <span class="card-title">Restore Database</span>
        </div>
        <div class="card-body">
            <div class="alert alert-warning" style="margin-bottom:16px">
                <strong>Warning:</strong> Restoring will replace your current database. The current database will be automatically backed up before replacement.
            </div>
            <form method="POST" enctype="multipart/form-data">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="restore_db">
                <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
                    <div style="flex:1;min-width:200px">
                        <label class="form-label">Select .sqlite backup file</label>
                        <input type="file" name="backup_file" class="form-input" accept=".sqlite" required>
                    </div>
                    <button type="submit" class="btn btn-danger"
                            onclick="return confirm('This will replace the current database. Are you sure?')">
                        Restore Database
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif ?>

    <!-- Backup history -->
    <?php if (!empty($backupFiles)): ?>
    <div class="card">
        <div class="card-header"><span class="card-title">Auto-Saved Backups</span></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Filename</th><th>Size</th><th>Created</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($backupFiles as $bf):
                        $bname = basename($bf);
                        $bsize = filesize($bf);
                        $bmtime = date('Y-m-d H:i:s', filemtime($bf));
                    ?>
                    <tr>
                        <td><code style="font-size:12px"><?= e($bname) ?></code></td>
                        <td class="text-muted"><?= formatBytes($bsize) ?></td>
                        <td class="text-muted text-sm"><?= e($bmtime) ?></td>
                        <td style="text-align:right">
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('Delete this backup file?')">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="delete_backup">
                                <input type="hidden" name="filename" value="<?= e($bname) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">💾</div>
            <h3>No saved backups</h3>
            <p>Auto-saved backups appear here when you restore the database.</p>
        </div>
    </div>
    <?php endif ?>

</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Backup & Restore', $content, ['section' => 'backup']);
