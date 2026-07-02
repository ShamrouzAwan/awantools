<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = Security::sanitize($_POST['action'] ?? '');

    if ($action === 'retry') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->update('email_queue',
                ['status' => 'pending', 'attempts' => 0, 'error' => null, 'failed_at' => null],
                'id = ?', [$id]
            );
            Session::flash('success', 'Item queued for retry.');
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->query("DELETE FROM email_queue WHERE id = ?", [$id]);
            Session::flash('success', 'Item deleted.');
        }
    }

    if ($action === 'clear_failed') {
        $db->query("DELETE FROM email_queue WHERE status = 'failed'");
        Session::flash('success', 'Failed queue items cleared.');
    }

    if ($action === 'clear_sent') {
        $db->query("DELETE FROM email_queue WHERE status = 'sent'");
        Session::flash('success', 'Sent queue items cleared.');
    }

    if ($action === 'process_now') {
        $sent = $mailer->processQueue(20);
        Session::flash('success', "Processed queue: {$sent} email(s) sent.");
    }

    redirect('/admin/email-queue');
}

$perPage = 40;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$status  = Security::sanitize($_GET['status'] ?? '');

$where  = [];
$params = [];
if ($status !== '') {
    $where[]  = 'status = ?';
    $params[] = $status;
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int)($db->fetch("SELECT COUNT(*) AS n FROM email_queue $whereClause", $params)['n'] ?? 0);
$items = $db->fetchAll("SELECT * FROM email_queue $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params) ?: [];
$pages = max(1, (int)ceil($total / $perPage));

$stats = [];
foreach (['pending','processing','sent','failed'] as $s) {
    $stats[$s] = (int)($db->fetch("SELECT COUNT(*) AS n FROM email_queue WHERE status = ?", [$s])['n'] ?? 0);
}

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div class="page-title">Email Queue</div>
        <div class="page-subtitle">Async email delivery queue</div>
    </div>
    <div class="topbar-actions">
        <?php if (($stats['pending'] ?? 0) > 0): ?>
        <form method="POST" onsubmit="return confirm('Process up to 20 pending emails now?')">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="process_now">
            <button class="btn btn-primary btn-sm">Process Now</button>
        </form>
        <?php endif ?>
        <?php if (($stats['failed'] ?? 0) > 0): ?>
        <form method="POST" onsubmit="return confirm('Delete all failed items?')">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="clear_failed">
            <button class="btn btn-danger btn-sm">Clear Failed</button>
        </form>
        <?php endif ?>
        <?php if (($stats['sent'] ?? 0) > 0): ?>
        <form method="POST" onsubmit="return confirm('Delete sent items?')">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="clear_sent">
            <button class="btn btn-ghost btn-sm">Clear Sent</button>
        </form>
        <?php endif ?>
        <a href="/admin/email-logs" class="btn btn-ghost btn-sm">Logs</a>
    </div>
</div>

<div class="page-body">

    <!-- Stats row -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px">
        <?php
        $statDefs = [
            ['pending',    'Pending',    'var(--color-warning,#f59e0b)'],
            ['processing', 'Processing', 'var(--color-primary,#6366f1)'],
            ['sent',       'Sent',       'var(--color-success,#22c55e)'],
            ['failed',     'Failed',     'var(--color-danger,#ef4444)'],
        ];
        foreach ($statDefs as [$key, $label, $color]):
        ?>
        <a href="?status=<?= $key ?>" style="text-decoration:none">
            <div class="card" style="<?= $status === $key ? 'border:2px solid var(--color-primary,#6366f1)' : '' ?>">
                <div class="card-body" style="padding:14px 18px">
                    <div style="font-size:24px;font-weight:700;color:<?= $color ?>"><?= number_format($stats[$key] ?? 0) ?></div>
                    <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px"><?= $label ?></div>
                </div>
            </div>
        </a>
        <?php endforeach ?>
    </div>

    <?php if ($status): ?>
    <div style="margin-bottom:12px">
        <a href="/admin/email-queue" class="btn btn-ghost btn-sm">← Show All</a>
        <span style="font-size:13px;color:var(--color-text-muted);margin-left:8px">Filtering: <?= e(ucfirst($status)) ?></span>
    </div>
    <?php endif ?>

    <?php if ($settings->get('email_queue_enabled', '0') !== '1'): ?>
    <div class="alert alert-warning" style="margin-bottom:16px">
        <strong>Queue is disabled.</strong> Enable the email queue in
        <a href="/admin/settings?tab=email">Settings &rarr; Email</a>
        so emails are delivered via queue instead of inline.
    </div>
    <?php endif ?>

    <!-- Table -->
    <div class="card">
        <div class="table-wrap">
            <?php if (empty($items)): ?>
            <div style="padding:48px;text-align:center;color:var(--color-text-muted)">
                <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;opacity:.4">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                <p style="margin:0;font-size:14px">Queue is empty<?= $status ? ' for this filter' : '' ?>.</p>
            </div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th style="width:90px">Status</th>
                        <th style="width:60px">Tries</th>
                        <th style="width:110px">Queued</th>
                        <th style="width:90px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td style="font-family:monospace;font-size:12px"><?= e($item['to_email']) ?></td>
                        <td style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px"
                            title="<?= e($item['subject']) ?>"><?= e($item['subject']) ?></td>
                        <td>
                            <?php
                            $statusColors = [
                                'pending'    => 'background:#fef3c7;color:#92400e',
                                'processing' => 'background:#ede9fe;color:#6d28d9',
                                'sent'       => 'background:#dcfce7;color:#166534',
                                'failed'     => 'background:#fee2e2;color:#991b1b',
                            ];
                            $sc = $statusColors[$item['status']] ?? 'background:#f1f5f9;color:#475569';
                            ?>
                            <span style="font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px;<?= $sc ?>">
                                <?= e(ucfirst($item['status'])) ?>
                            </span>
                            <?php if ($item['error']): ?>
                            <div style="font-size:10px;color:var(--color-danger,#ef4444);margin-top:3px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                 title="<?= e($item['error']) ?>"><?= e($item['error']) ?></div>
                            <?php endif ?>
                        </td>
                        <td style="font-size:12px;text-align:center;color:var(--color-text-muted)">
                            <?= (int)$item['attempts'] ?>/<?= (int)$item['max_attempts'] ?>
                        </td>
                        <td style="font-size:11px;color:var(--color-text-muted);white-space:nowrap">
                            <?= fdate($item['created_at']) ?>
                            <?php if ($item['sent_at']): ?>
                            <div style="color:var(--color-success,#22c55e)">Sent <?= fdate($item['sent_at']) ?></div>
                            <?php endif ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:4px">
                                <?php if (in_array($item['status'], ['failed','pending'])): ?>
                                <form method="POST">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="retry">
                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                    <button class="btn btn-ghost btn-sm" style="font-size:11px;padding:3px 8px">Retry</button>
                                </form>
                                <?php endif ?>
                                <form method="POST" onsubmit="return confirm('Delete this queue item?')">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                    <button class="btn btn-ghost btn-sm" style="color:var(--color-danger,#ef4444);font-size:11px;padding:3px 8px">Del</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <?php endif ?>
        </div>

        <?php if ($pages > 1): ?>
        <div style="padding:12px 20px;border-top:1px solid var(--color-border);display:flex;gap:6px;align-items:center;flex-wrap:wrap">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="?page=<?= $i ?>&status=<?= urlencode($status) ?>"
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
render_admin('Email Queue', $content, ['section' => 'email-queue']);
