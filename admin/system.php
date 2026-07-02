<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

// ─── PHP Extensions ───────────────────────────────────────────────────────────
$extensions = [
    'pdo_sqlite' => 'PDO SQLite',
    'pdo_mysql'  => 'PDO MySQL',
    'gd'         => 'GD (image processing)',
    'zip'        => 'ZIP archives',
    'fileinfo'   => 'File info / MIME',
    'mbstring'   => 'Multibyte strings',
    'json'       => 'JSON',
    'openssl'    => 'OpenSSL / HTTPS',
    'curl'       => 'cURL',
    'intl'       => 'Internationalization',
];

// ─── Database stats ───────────────────────────────────────────────────────────
$tables = ['users','roles','user_roles','settings','plugins','logs','theme_overrides','pages','analytics_events','media'];
$tableStats = [];
foreach ($tables as $t) {
    $tableStats[$t] = $db->count($t);
}

$dbSize = 0;
if ($db->driver() === 'sqlite' && file_exists(DB_SQLITE)) {
    $dbSize = filesize(DB_SQLITE);
}

// ─── Storage stats ────────────────────────────────────────────────────────────
function dirSize(string $dir): int {
    $size = 0;
    foreach (glob(rtrim($dir, '/') . '/*') ?: [] as $f) {
        $size += is_file($f) ? filesize($f) : dirSize($f);
    }
    return $size;
}
function formatBytesS(int $b): string {
    if ($b < 1024)     return $b . ' B';
    if ($b < 1048576)  return round($b / 1024, 1) . ' KB';
    if ($b < 1073741824) return round($b / 1048576, 2) . ' MB';
    return round($b / 1073741824, 2) . ' GB';
}

$uploadsSize = dirSize(UPLOADS_PATH);
$logsSize    = dirSize(LOGS_PATH);
$backupsSize = is_dir(STORAGE_PATH . '/backups') ? dirSize(STORAGE_PATH . '/backups') : 0;
$uploadCount = count(glob(UPLOADS_PATH . '/*') ?: []);

$diskFree    = disk_free_space('/');
$diskTotal   = disk_total_space('/');
$diskUsed    = $diskTotal - $diskFree;
$diskPct     = $diskTotal > 0 ? round($diskUsed / $diskTotal * 100) : 0;

// ─── Memory & performance ─────────────────────────────────────────────────────
$memPeak    = memory_get_peak_usage(true);
$memCurrent = memory_get_usage(true);
$memLimit   = ini_get('memory_limit');
$uploadMax  = ini_get('upload_max_filesize');
$postMax    = ini_get('post_max_size');
$maxExecTime = ini_get('max_execution_time');

// ─── Recent errors ────────────────────────────────────────────────────────────
$recentErrors = $db->fetchAll(
    "SELECT message, created_at FROM logs WHERE level IN ('error','critical') ORDER BY created_at DESC LIMIT 5"
);

// ─── View ─────────────────────────────────────────────────────────────────────
ob_start();
?>
<style>
.sys-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.sys-row  { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--color-border); }
.sys-row:last-child { border-bottom:none; }
.sys-key  { font-size:13px; color:var(--color-text-secondary); }
.sys-val  { font-size:13px; font-weight:600; color:var(--color-text); text-align:right; }
.ext-row  { display:flex; align-items:center; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--color-border); }
.ext-row:last-child { border-bottom:none; }
.disk-bar-wrap { background:var(--color-border); border-radius:var(--radius-full); height:8px; margin:8px 0 4px; }
.disk-bar { height:8px; border-radius:var(--radius-full); background:var(--color-primary); }
.disk-bar.warn { background:var(--color-warning); }
.disk-bar.danger { background:var(--color-danger); }
@media(max-width:768px) { .sys-grid { grid-template-columns:1fr; } }
</style>

<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">System Health</div>
            <div class="page-subtitle">Environment, database, and storage status</div>
        </div>
    </div>
    <div class="topbar-actions">
        <a href="/admin/system" class="btn btn-secondary btn-sm">↻ Refresh</a>
    </div>
</div>

<div class="page-body">
<div class="sys-grid">

    <!-- Environment -->
    <div class="card">
        <div class="card-header"><span class="card-title">Environment</span></div>
        <div class="card-body">
            <div class="sys-row"><span class="sys-key">AWAN Version</span><span class="sys-val"><?= e(AWAN_VERSION) ?></span></div>
            <div class="sys-row"><span class="sys-key">Environment</span><span class="sys-val">
                <span class="badge badge-<?= AWAN_ENV === 'production' ? 'success' : 'warning' ?>"><?= e(AWAN_ENV) ?></span>
            </span></div>
            <div class="sys-row"><span class="sys-key">Debug Mode</span><span class="sys-val">
                <span class="badge badge-<?= AWAN_DEBUG ? 'warning' : 'success' ?>"><?= AWAN_DEBUG ? 'ON' : 'OFF' ?></span>
            </span></div>
            <div class="sys-row"><span class="sys-key">PHP Version</span><span class="sys-val"><?= phpversion() ?></span></div>
            <div class="sys-row"><span class="sys-key">Server</span><span class="sys-val"><?= e($_SERVER['SERVER_SOFTWARE'] ?? 'PHP Built-in') ?></span></div>
            <div class="sys-row"><span class="sys-key">OS</span><span class="sys-val"><?= e(php_uname('s') . ' ' . php_uname('r')) ?></span></div>
            <div class="sys-row"><span class="sys-key">Hostname</span><span class="sys-val"><?= e(gethostname() ?: 'unknown') ?></span></div>
            <div class="sys-row"><span class="sys-key">Timezone</span><span class="sys-val"><?= e(date_default_timezone_get()) ?></span></div>
        </div>
    </div>

    <!-- PHP Config -->
    <div class="card">
        <div class="card-header"><span class="card-title">PHP Configuration</span></div>
        <div class="card-body">
            <div class="sys-row"><span class="sys-key">Memory Limit</span><span class="sys-val"><?= e($memLimit) ?></span></div>
            <div class="sys-row"><span class="sys-key">Peak Memory Used</span><span class="sys-val"><?= formatBytesS($memPeak) ?></span></div>
            <div class="sys-row"><span class="sys-key">Current Memory</span><span class="sys-val"><?= formatBytesS($memCurrent) ?></span></div>
            <div class="sys-row"><span class="sys-key">Upload Max Size</span><span class="sys-val"><?= e($uploadMax) ?></span></div>
            <div class="sys-row"><span class="sys-key">POST Max Size</span><span class="sys-val"><?= e($postMax) ?></span></div>
            <div class="sys-row"><span class="sys-key">Max Execution Time</span><span class="sys-val"><?= e($maxExecTime) ?>s</span></div>
            <div class="sys-row"><span class="sys-key">SAPI</span><span class="sys-val"><?= e(php_sapi_name()) ?></span></div>
        </div>
    </div>

    <!-- Database stats -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Database</span>
            <a href="/admin/migrate-db" class="btn btn-secondary btn-sm">Migrate to MySQL</a>
        </div>
        <div class="card-body">
            <div class="sys-row"><span class="sys-key">Driver</span>
                <span class="sys-val"><span class="badge badge-primary"><?= e(strtoupper($db->driver())) ?></span></span>
            </div>
            <?php if ($db->driver() === 'sqlite'): ?>
            <div class="sys-row"><span class="sys-key">File</span><span class="sys-val" style="font-family:monospace;font-size:12px">storage/database.sqlite</span></div>
            <div class="sys-row"><span class="sys-key">File Size</span><span class="sys-val"><?= formatBytesS($dbSize) ?></span></div>
            <?php else: ?>
            <div class="sys-row"><span class="sys-key">Host</span><span class="sys-val" style="font-family:monospace;font-size:12px"><?= e(DB_HOST) ?>:<?= e(DB_PORT) ?></span></div>
            <div class="sys-row"><span class="sys-key">Database</span><span class="sys-val" style="font-family:monospace;font-size:12px"><?= e(DB_NAME) ?></span></div>
            <?php endif ?>
            <?php foreach ($tableStats as $tbl => $cnt): ?>
            <div class="sys-row">
                <span class="sys-key" style="font-family:monospace;font-size:12px"><?= e($tbl) ?></span>
                <span class="sys-val"><?= number_format($cnt) ?> rows</span>
            </div>
            <?php endforeach ?>
        </div>
    </div>

    <!-- Storage -->
    <div class="card">
        <div class="card-header"><span class="card-title">Storage</span></div>
        <div class="card-body">
            <div class="sys-row"><span class="sys-key">Uploads</span><span class="sys-val"><?= formatBytesS($uploadsSize) ?> (<?= $uploadCount ?> files)</span></div>
            <div class="sys-row"><span class="sys-key">Logs</span><span class="sys-val"><?= formatBytesS($logsSize) ?></span></div>
            <div class="sys-row"><span class="sys-key">Backups</span><span class="sys-val"><?= formatBytesS($backupsSize) ?></span></div>
            <div style="margin-top:16px">
                <div style="display:flex;justify-content:space-between">
                    <span class="sys-key">Disk Usage</span>
                    <span class="sys-val"><?= formatBytesS($diskUsed) ?> / <?= formatBytesS($diskTotal) ?></span>
                </div>
                <div class="disk-bar-wrap">
                    <div class="disk-bar <?= $diskPct > 90 ? 'danger' : ($diskPct > 70 ? 'warn' : '') ?>"
                         style="width:<?= $diskPct ?>%"></div>
                </div>
                <div style="font-size:11px;color:var(--color-text-muted)"><?= $diskPct ?>% used · <?= formatBytesS($diskFree) ?> free</div>
            </div>
        </div>
    </div>

</div><!-- /sys-grid -->

<!-- PHP Extensions (full width) -->
<div class="card" style="margin-top:16px">
    <div class="card-header"><span class="card-title">PHP Extensions</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:0 24px">
            <?php foreach ($extensions as $ext => $label):
                $loaded = extension_loaded($ext);
            ?>
            <div class="ext-row">
                <span class="sys-key"><?= e($label) ?><span class="text-muted text-sm" style="margin-left:6px">(<?= e($ext) ?>)</span></span>
                <span class="badge badge-<?= $loaded ? 'success' : 'danger' ?>"><?= $loaded ? 'Loaded' : 'Missing' ?></span>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</div>

<!-- Recent errors -->
<div class="card" style="margin-top:16px">
    <div class="card-header">
        <span class="card-title">Recent Errors</span>
        <a href="/admin/logs?level=error" class="btn btn-ghost btn-sm">View All Logs →</a>
    </div>
    <?php if (empty($recentErrors)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"></div>
        <h3>No errors logged</h3>
        <p>The platform is running cleanly.</p>
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Message</th><th>Time</th></tr></thead>
            <tbody>
                <?php foreach ($recentErrors as $e): ?>
                <tr>
                    <td style="font-size:12px;font-family:monospace"><?= e(substr($e['message'], 0, 120)) ?></td>
                    <td class="text-muted text-sm" style="white-space:nowrap"><?= e($e['created_at']) ?></td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>
</div>

</div><!-- /page-body -->
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('System Health', $content, ['section' => 'system']);
