<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $postAction = Security::sanitize($_POST['action'] ?? '');
    if ($postAction === 'clear_all') {
        $db->query('DELETE FROM email_logs');
        Session::flash('success', 'All email logs cleared.');
    }
    redirect('/admin/email-logs');
}

$perPage = 50;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$status  = Security::sanitize($_GET['status'] ?? '');
$search  = Security::sanitize($_GET['q'] ?? '');
$dateFrom = Security::sanitize($_GET['from'] ?? '');
$dateTo   = Security::sanitize($_GET['to']   ?? '');

$where  = [];
$params = [];

if ($status !== '') {
    $where[]  = 'status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[]  = '(recipient LIKE ? OR subject LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($dateFrom !== '') {
    $where[]  = 'created_at >= ?';
    $params[] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $where[]  = 'created_at <= ?';
    $params[] = $dateTo . ' 23:59:59';
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int)($db->fetch("SELECT COUNT(*) AS n FROM email_logs $whereClause", $params)['n'] ?? 0);
$logs  = $db->fetchAll("SELECT * FROM email_logs $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params) ?: [];
$pages = max(1, (int)ceil($total / $perPage));

$countSent    = (int)($db->fetch("SELECT COUNT(*) AS n FROM email_logs WHERE status='sent'")['n']          ?? 0);
$countFailed  = (int)($db->fetch("SELECT COUNT(*) AS n FROM email_logs WHERE status='failed'")['n']        ?? 0);
$countTotal   = (int)($db->fetch("SELECT COUNT(*) AS n FROM email_logs")['n']                              ?? 0);
$countOpened  = (int)($db->fetch("SELECT COUNT(*) AS n FROM email_logs WHERE open_count > 0")['n']        ?? 0);
$countClicked = (int)($db->fetch("SELECT COUNT(*) AS n FROM email_logs WHERE click_count > 0")['n']       ?? 0);

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div class="page-title">Email Logs</div>
        <div class="page-subtitle">Record of all outgoing email attempts</div>
    </div>
    <div class="topbar-actions">
        <a href="/admin/email-queue" class="btn btn-secondary btn-sm">Queue</a>
        <?php if ($countTotal > 0): ?>
        <form method="POST" onsubmit="return confirm('Clear all email logs? This cannot be undone.')">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="clear_all">
            <button class="btn btn-danger btn-sm">Clear All</button>
        </form>
        <?php endif ?>
    </div>
</div>

<div class="page-body">

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:24px">
        <div class="card">
            <div class="card-body" style="padding:16px 20px">
                <div style="font-size:28px;font-weight:700;color:var(--color-text)"><?= number_format($countTotal) ?></div>
                <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px">Total</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body" style="padding:16px 20px">
                <div style="font-size:28px;font-weight:700;color:var(--color-success,#22c55e)"><?= number_format($countSent) ?></div>
                <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px">Sent</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body" style="padding:16px 20px">
                <div style="font-size:28px;font-weight:700;color:var(--color-danger,#ef4444)"><?= number_format($countFailed) ?></div>
                <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px">Failed</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body" style="padding:16px 20px">
                <div style="font-size:28px;font-weight:700;color:#8b5cf6"><?= number_format($countOpened) ?></div>
                <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px">Opened</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body" style="padding:16px 20px">
                <div style="font-size:28px;font-weight:700;color:#0ea5e9"><?= number_format($countClicked) ?></div>
                <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px">Clicked</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-body" style="padding:14px 20px">
            <form method="GET" action="/admin/email-logs" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                <div style="flex:1;min-width:180px">
                    <label class="form-label" style="font-size:12px;margin-bottom:4px">Search</label>
                    <input type="text" name="q" class="form-input" style="height:34px;font-size:13px"
                           placeholder="Recipient or subject…" value="<?= e($search) ?>">
                </div>
                <div>
                    <label class="form-label" style="font-size:12px;margin-bottom:4px">Status</label>
                    <select name="status" class="form-input" style="height:34px;font-size:13px;padding:0 10px">
                        <option value="">All</option>
                        <option value="sent"   <?= $status === 'sent'   ? 'selected' : '' ?>>Sent</option>
                        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size:12px;margin-bottom:4px">From</label>
                    <input type="date" name="from" class="form-input" style="height:34px;font-size:13px;padding:0 10px"
                           value="<?= e($dateFrom) ?>">
                </div>
                <div>
                    <label class="form-label" style="font-size:12px;margin-bottom:4px">To</label>
                    <input type="date" name="to" class="form-input" style="height:34px;font-size:13px;padding:0 10px"
                           value="<?= e($dateTo) ?>">
                </div>
                <div style="display:flex;gap:6px;align-items:flex-end">
                    <button class="btn btn-primary btn-sm" style="height:34px">Filter</button>
                    <?php if ($search || $status || $dateFrom || $dateTo): ?>
                    <a href="/admin/email-logs" class="btn btn-ghost btn-sm" style="height:34px;line-height:34px;padding:0 12px">Reset</a>
                    <?php endif ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="table-wrap">
            <?php if (empty($logs)): ?>
            <div style="padding:48px;text-align:center;color:var(--color-text-muted)">
                <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;opacity:.4">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                <p style="margin:0;font-size:14px">No email logs found.</p>
            </div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th style="width:70px">Status</th>
                        <th style="width:60px">Via</th>
                        <th style="width:80px">Opened</th>
                        <th style="width:80px">Clicked</th>
                        <th>Error</th>
                        <th style="width:120px">Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="font-family:monospace;font-size:12px"><?= e($log['recipient']) ?></td>
                        <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px"><?= e($log['subject']) ?></td>
                        <td>
                            <?php if ($log['status'] === 'sent'): ?>
                            <span class="badge badge-success" style="font-size:10px">Sent</span>
                            <?php elseif ($log['status'] === 'failed'): ?>
                            <span class="badge badge-danger" style="font-size:10px">Failed</span>
                            <?php else: ?>
                            <span class="badge" style="font-size:10px"><?= e($log['status']) ?></span>
                            <?php endif ?>
                        </td>
                        <td>
                            <?php $via = $log['transport'] ?? 'php'; ?>
                            <span style="font-size:10px;font-weight:600;padding:2px 6px;border-radius:4px;<?= $via === 'smtp' ? 'background:#ede9fe;color:#6d28d9' : 'background:#f1f5f9;color:#475569' ?>">
                                <?= strtoupper(e($via)) ?>
                            </span>
                        </td>
                        <td style="text-align:center">
                            <?php $openCount = (int)($log['open_count'] ?? 0); ?>
                            <?php if ($openCount > 0): ?>
                            <span title="Opened <?= $openCount ?>x — <?= fdate($log['opened_at'] ?? '') ?>"
                                  style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#7c3aed">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <?= $openCount ?>
                            </span>
                            <?php else: ?>
                            <span style="color:var(--color-text-muted);font-size:11px">—</span>
                            <?php endif ?>
                        </td>
                        <td style="text-align:center">
                            <?php $clickCount = (int)($log['click_count'] ?? 0); ?>
                            <?php if ($clickCount > 0): ?>
                            <span title="Clicked <?= $clickCount ?>x — <?= fdate($log['clicked_at'] ?? '') ?>"
                                  style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#0284c7">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                <?= $clickCount ?>
                            </span>
                            <?php else: ?>
                            <span style="color:var(--color-text-muted);font-size:11px">—</span>
                            <?php endif ?>
                        </td>
                        <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;color:var(--color-danger,#ef4444)">
                            <?= ($log['error_message'] ?? $log['error'] ?? '') ? e($log['error_message'] ?? $log['error'] ?? '') : '<span style="color:var(--color-text-muted)">—</span>' ?>
                        </td>
                        <td style="white-space:nowrap;font-size:11px;color:var(--color-text-muted)"><?= fdate($log['created_at']) ?></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <?php endif ?>
        </div>

        <?php if ($pages > 1): ?>
        <div style="padding:12px 20px;border-top:1px solid var(--color-border);display:flex;gap:6px;align-items:center;flex-wrap:wrap">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&q=<?= urlencode($search) ?>&from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>"
               style="padding:4px 10px;border-radius:4px;font-size:13px;text-decoration:none;border:1px solid var(--color-border);<?= $i === $page ? 'background:var(--color-primary,#6366f1);color:#fff;border-color:var(--color-primary,#6366f1)' : 'color:var(--color-text)' ?>">
                <?= $i ?>
            </a>
            <?php endfor ?>
            <span style="font-size:12px;color:var(--color-text-muted);margin-left:6px"><?= number_format($total) ?> total</span>
        </div>
        <?php endif ?>
    </div>

</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Email Logs', $content, ['section' => 'email-logs']);
