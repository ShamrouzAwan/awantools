<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$logger  = Logger::getInstance($db);
$view    = in_array($_GET['view'] ?? 'system', ['system', 'email']) ? ($_GET['view'] ?? 'system') : 'system';
$level   = Security::sanitize($_GET['level'] ?? '');
$perPage = 50;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = Security::sanitize($_POST['action'] ?? '');

    if ($action === 'clear_old') {
        $days    = max(1, (int)($_POST['days'] ?? 30));
        $deleted = $logger->clearOldLogs($days);
        $logger->info("Cleared {$deleted} log entries older than {$days} days.", [], $auth->id());
        Session::flash('success', "Cleared {$deleted} log entries older than {$days} days.");
    }

    if ($action === 'clear_all') {
        $deleted = $logger->clearAllLogs();
        Session::flash('success', "Cleared all {$deleted} log entries and log files.");
    }

    if ($action === 'clear_email_logs') {
        try {
            $deleted = $db->fetch("SELECT COUNT(*) as n FROM email_logs")['n'] ?? 0;
            $db->query("DELETE FROM email_logs");
            Session::flash('success', "Cleared {$deleted} email log entries.");
        } catch (Throwable $e) {
            Session::flash('danger', 'Could not clear email logs.');
        }
    }

    redirect('/admin/logs?view=' . $view);
}

// ── System logs ───────────────────────────────────────────────────────────────
$total      = $logger->countLogs($level);
$logs       = $logger->getLogs($perPage, $offset, $level);
$totalPages = max(1, (int)ceil($total / $perPage));

$levelCounts = [
    'all'   => $db->count('logs'),
    'error' => $db->count('logs', "level = 'error'"),
    'warn'  => $db->count('logs', "level = 'warn'"),
    'auth'  => $db->count('logs', "level = 'auth'"),
    'info'  => $db->count('logs', "level = 'info'"),
];

$logFiles = glob(LOGS_PATH . '/*.log') ?: [];
$logFileSizeTotal = 0;
foreach ($logFiles as $lf) { $logFileSizeTotal += filesize($lf); }

// ── Email logs ────────────────────────────────────────────────────────────────
$emailTotal = 0; $emailLogs = []; $emailPages = 1;
$emailSent = 0; $emailFailed = 0;
try {
    $emailTotal  = $db->count('email_logs');
    $emailSent   = $db->count('email_logs', "status = 'sent'");
    $emailFailed = $db->count('email_logs', "status = 'failed'");
    $emailPages  = max(1, (int)ceil($emailTotal / $perPage));
    $emailLogs   = $db->fetchAll(
        "SELECT * FROM email_logs ORDER BY id DESC LIMIT ? OFFSET ?",
        [$perPage, $offset]
    );
} catch (Throwable $e) {}

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Logs</div>
            <div class="page-subtitle">
                <?php if ($view === 'email'): ?>
                <?= number_format($emailTotal) ?> email log entries &mdash; <?= $emailSent ?> sent, <?= $emailFailed ?> failed
                <?php else: ?>
                <?= number_format($total) ?> system entries<?= $level ? " (level: {$level})" : '' ?>
                &mdash; <?= count($logFiles) ?> log file(s) (<?= round($logFileSizeTotal / 1024, 1) ?> KB)
                <?php endif ?>
            </div>
        </div>
    </div>
    <div class="topbar-actions">
        <?php if ($view === 'email'): ?>
        <form method="POST" style="display:inline">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="clear_email_logs">
            <button type="submit" class="btn btn-danger btn-sm"
                    data-confirm="Delete all email log entries? This cannot be undone.">
                Clear Email Logs
            </button>
        </form>
        <?php else: ?>
        <form method="POST" style="display:inline;display:flex;align-items:center;gap:6px;flex-wrap:wrap">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="clear_old">
            <select name="days" class="form-control" style="width:auto;padding:5px 10px;font-size:12px">
                <option value="7">7 days</option>
                <option value="14">14 days</option>
                <option value="30" selected>30 days</option>
                <option value="60">60 days</option>
                <option value="90">90 days</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm"
                    data-confirm="Delete log entries older than the selected number of days?">
                Clear Old Logs
            </button>
        </form>
        <form method="POST" style="display:inline">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="clear_all">
            <button type="submit" class="btn btn-danger btn-sm"
                    data-confirm="Delete ALL log entries and log files? This cannot be undone.">
                Clear All Logs
            </button>
        </form>
        <?php endif ?>
    </div>
</div>

<div class="page-body">

    <!-- View tabs -->
    <div style="display:flex;gap:6px;margin-bottom:16px">
        <a href="/admin/logs?view=system" class="btn btn-sm <?= $view === 'system' ? 'btn-primary' : 'btn-secondary' ?>">
            System Logs
            <span style="background:rgba(0,0,0,0.12);padding:1px 6px;border-radius:10px;font-size:10px;margin-left:2px"><?= number_format($levelCounts['all']) ?></span>
        </a>
        <a href="/admin/logs?view=email" class="btn btn-sm <?= $view === 'email' ? 'btn-primary' : 'btn-secondary' ?>">
            Email Logs
            <span style="background:rgba(0,0,0,0.12);padding:1px 6px;border-radius:10px;font-size:10px;margin-left:2px"><?= number_format($emailTotal) ?></span>
            <?php if ($emailFailed > 0): ?>
            <span style="background:#dc2626;color:#fff;padding:1px 5px;border-radius:10px;font-size:10px;margin-left:2px"><?= $emailFailed ?> failed</span>
            <?php endif ?>
        </a>
    </div>

<?php if ($view === 'system'): ?>

    <!-- Level filters -->
    <div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
        <?php foreach (['all' => 'All', 'error' => 'Errors', 'warn' => 'Warnings', 'auth' => 'Auth', 'info' => 'Info'] as $k => $label): ?>
        <?php $filterLevel = $k === 'all' ? '' : $k; ?>
        <a href="/admin/logs?view=system<?= $filterLevel ? '&level=' . urlencode($filterLevel) : '' ?>"
           class="btn btn-sm <?= $level === $filterLevel ? 'btn-primary' : 'btn-secondary' ?>">
            <?= $label ?>
            <span style="background:rgba(0,0,0,0.12);padding:1px 6px;border-radius:10px;font-size:10px;margin-left:2px"><?= number_format($levelCounts[$k]) ?></span>
        </a>
        <?php endforeach ?>
    </div>

    <div class="card">
        <?php if (empty($logs)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"></div>
            <h3>No Log Entries</h3>
            <p>No logs found<?= $level ? " for level: {$level}" : '' ?>.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th style="width:80px">Level</th>
                        <th>Message</th>
                        <th style="width:70px">User</th>
                        <th style="width:110px">IP</th>
                        <th style="width:150px">Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="text-muted text-sm"><?= $log['id'] ?></td>
                    <td>
                        <?php $lvl = $log['level']; ?>
                        <span class="badge badge-<?= $lvl === 'error' ? 'danger' : ($lvl === 'warn' ? 'warning' : ($lvl === 'auth' ? 'primary' : 'neutral')) ?>">
                            <?= e($lvl) ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-size:13px"><?= e($log['message']) ?></div>
                        <?php if ($log['context']): ?>
                        <div style="font-size:11px;color:var(--color-text-muted);font-family:monospace;margin-top:2px"><?= e(substr($log['context'], 0, 140)) ?><?= strlen($log['context']) > 140 ? '&hellip;' : '' ?></div>
                        <?php endif ?>
                        <?php if ($log['url']): ?>
                        <div style="font-size:11px;color:var(--color-text-muted);margin-top:1px"><?= e($log['url']) ?></div>
                        <?php endif ?>
                    </td>
                    <td class="text-sm text-muted"><?= $log['user_id'] ? '#' . $log['user_id'] : '—' ?></td>
                    <td style="font-family:monospace;font-size:11px;color:var(--color-text-muted)"><?= e($log['ip'] ?? '—') ?></td>
                    <td class="text-muted" style="font-size:12px;white-space:nowrap" title="<?= e($log['created_at']) ?>">
                        <?= fdate($log['created_at'], 'M j, H:i:s') ?>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="card-footer">
            <div class="pagination">
                <?php if ($page > 1): ?><a href="?view=system&page=<?= $page - 1 ?><?= $level ? '&level=' . urlencode($level) : '' ?>">&laquo;</a><?php endif ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
                    <?php else: ?><a href="?view=system&page=<?= $i ?><?= $level ? '&level=' . urlencode($level) : '' ?>"><?= $i ?></a><?php endif ?>
                <?php endfor ?>
                <?php if ($page < $totalPages): ?><a href="?view=system&page=<?= $page + 1 ?><?= $level ? '&level=' . urlencode($level) : '' ?>">&raquo;</a><?php endif ?>
            </div>
        </div>
        <?php endif ?>
        <?php endif ?>
    </div>

<?php else: ?>
    <!-- Email logs -->
    <div class="card">
        <?php if (empty($emailLogs)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"></div>
            <h3>No Email Logs</h3>
            <p>Emails sent by the platform will be logged here.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th style="width:90px">Status</th>
                        <th>Error</th>
                        <th style="width:150px">Sent At</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($emailLogs as $log): ?>
                <tr>
                    <td class="text-muted text-sm"><?= $log['id'] ?></td>
                    <td style="font-family:monospace;font-size:12px"><?= e($log['recipient']) ?></td>
                    <td style="font-size:13px"><?= e($log['subject']) ?></td>
                    <td>
                        <span class="badge <?= $log['status'] === 'sent' ? 'badge-success' : 'badge-danger' ?>">
                            <?= e($log['status']) ?>
                        </span>
                    </td>
                    <td style="font-size:11px;color:var(--color-text-muted);font-family:monospace">
                        <?= $log['error'] ? e(substr($log['error'], 0, 100)) : '—' ?>
                    </td>
                    <td class="text-muted" style="font-size:12px;white-space:nowrap">
                        <?= fdate($log['created_at'], 'M j, H:i:s') ?>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <?php if ($emailPages > 1): ?>
        <div class="card-footer">
            <div class="pagination">
                <?php if ($page > 1): ?><a href="?view=email&page=<?= $page - 1 ?>">&laquo;</a><?php endif ?>
                <?php for ($i = max(1, $page - 2); $i <= min($emailPages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
                    <?php else: ?><a href="?view=email&page=<?= $i ?>"><?= $i ?></a><?php endif ?>
                <?php endfor ?>
                <?php if ($page < $emailPages): ?><a href="?view=email&page=<?= $page + 1 ?>">&raquo;</a><?php endif ?>
            </div>
        </div>
        <?php endif ?>
        <?php endif ?>
    </div>
<?php endif ?>

</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Logs', $content, ['section' => 'logs']);
