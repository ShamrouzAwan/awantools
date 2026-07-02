<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $postAction = Security::sanitize($_POST['action'] ?? '');

    if ($postAction === 'delete' && !empty($_POST['id'])) {
        $db->delete('newsletter_subscribers', 'id = ?', [(int)$_POST['id']]);
        Session::flash('success', 'Subscriber removed.');
    } elseif ($postAction === 'delete_all') {
        $db->query('DELETE FROM newsletter_subscribers');
        Session::flash('success', 'All subscribers deleted.');
    } elseif ($postAction === 'set_status' && !empty($_POST['id'])) {
        $newStatus = Security::sanitize($_POST['status'] ?? 'active');
        if (in_array($newStatus, ['active', 'unsubscribed'])) {
            $db->update('newsletter_subscribers', ['status' => $newStatus], 'id = ?', [(int)$_POST['id']]);
            Session::flash('success', 'Subscriber status updated.');
        }
    } elseif ($postAction === 'send_campaign') {
        $subject  = Security::sanitize(trim($_POST['campaign_subject'] ?? ''));
        $htmlBody = trim($_POST['campaign_body'] ?? '');
        if ($subject && $htmlBody) {
            $active = $db->fetchAll("SELECT email, name FROM newsletter_subscribers WHERE status = 'active'") ?: [];
            $sent = 0;
            $failed = 0;
            foreach ($active as $sub) {
                $personalHtml = str_replace(
                    ['{{name}}', '{{email}}'],
                    [e($sub['name'] ?: 'Subscriber'), e($sub['email'])],
                    $htmlBody
                );
                try {
                    if ($settings->get('email_queue_enabled', '0') === '1') {
                        $mailer->queue($sub['email'], $subject, $personalHtml);
                    } else {
                        $mailer->send($sub['email'], $subject, $personalHtml, true);
                    }
                    $sent++;
                } catch (Throwable $err) {
                    $failed++;
                }
            }
            Session::flash('success', "Campaign sent: {$sent} delivered" . ($failed ? ", {$failed} failed" : '') . '.');
        } else {
            Session::flash('warning', 'Subject and body are required.');
        }
        redirect('/admin/newsletter?tab=compose');
    } elseif ($postAction === 'export_csv') {
        $rows = $db->fetchAll('SELECT email, name, status, created_at FROM newsletter_subscribers ORDER BY created_at DESC') ?: [];
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="newsletter-subscribers-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Email', 'Name', 'Status', 'Subscribed At']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['email'], $r['name'] ?? '', $r['status'], $r['created_at']]);
        }
        fclose($out);
        exit;
    }
    redirect('/admin/newsletter');
}

$perPage = 50;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$status  = Security::sanitize($_GET['status'] ?? '');
$search  = Security::sanitize($_GET['q'] ?? '');

$where  = [];
$params = [];
if ($status !== '') {
    $where[]  = 'status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[]  = '(email LIKE ? OR name LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total  = (int)($db->fetch("SELECT COUNT(*) AS n FROM newsletter_subscribers $whereClause", $params)['n'] ?? 0);
$subs   = $db->fetchAll("SELECT * FROM newsletter_subscribers $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params) ?: [];
$pages  = max(1, ceil($total / $perPage));

$countAll          = (int)($db->fetch("SELECT COUNT(*) AS n FROM newsletter_subscribers")['n'] ?? 0);
$countActive       = (int)($db->fetch("SELECT COUNT(*) AS n FROM newsletter_subscribers WHERE status='active'")['n'] ?? 0);
$countUnsubscribed = (int)($db->fetch("SELECT COUNT(*) AS n FROM newsletter_subscribers WHERE status='unsubscribed'")['n'] ?? 0);
$countThisMonth    = (int)($db->fetch("SELECT COUNT(*) AS n FROM newsletter_subscribers WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')")['n'] ?? 0);

$activeTab = Security::sanitize($_GET['tab'] ?? 'subscribers');

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Newsletter</div>
            <div class="page-subtitle"><?= number_format($countActive) ?> active subscriber<?= $countActive !== 1 ? 's' : '' ?></div>
        </div>
    </div>
    <div class="topbar-actions">
        <form method="POST" style="display:inline">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="export_csv">
            <button class="btn btn-secondary btn-sm">Export CSV</button>
        </form>
        <?php if ($countAll > 0): ?>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete all subscribers? This cannot be undone.')">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="delete_all">
            <button class="btn btn-danger btn-sm">Delete All</button>
        </form>
        <?php endif ?>
    </div>
</div>

<div class="page-body">

    <!-- Tabs -->
    <div style="display:flex;gap:6px;margin-bottom:20px;border-bottom:1px solid var(--color-border);padding-bottom:0">
        <a href="?tab=subscribers" class="btn btn-sm <?= $activeTab !== 'compose' ? 'btn-primary' : 'btn-ghost' ?>" style="border-bottom-left-radius:0;border-bottom-right-radius:0;margin-bottom:-1px">Subscribers</a>
        <a href="?tab=compose" class="btn btn-sm <?= $activeTab === 'compose' ? 'btn-primary' : 'btn-ghost' ?>" style="border-bottom-left-radius:0;border-bottom-right-radius:0;margin-bottom:-1px">Send Campaign</a>
    </div>

    <?php if ($activeTab === 'compose'): ?>
    <!-- Compose Campaign -->
    <div style="max-width:720px">
        <div class="card">
            <div class="card-header"><span class="card-title">Send Newsletter Campaign</span></div>
            <div class="card-body">
                <p style="font-size:14px;color:var(--color-text-secondary);margin-bottom:20px">
                    This will send the email to all <strong><?= number_format($countActive) ?> active subscribers</strong>.
                    Use <code>{{name}}</code> and <code>{{email}}</code> as personalisation tokens in the body.
                </p>
                <form method="POST" data-loading>
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="send_campaign">
                    <div class="form-group">
                        <label class="form-label">Subject Line <span class="req">*</span></label>
                        <input type="text" name="campaign_subject" class="form-input" placeholder="Your newsletter subject..." required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Body (HTML supported) <span class="req">*</span></label>
                        <textarea name="campaign_body" id="newsletter-body" class="form-input" rows="12"
                            placeholder="<p>Hi {{name}},</p>&#10;<p>Your newsletter content goes here.</p>&#10;<p>To unsubscribe, <a href='...'>click here</a>.</p>" required></textarea>
                        <div class="form-hint">HTML is supported. Use {{name}} for personalisation. Always include an unsubscribe link.</div>
                    </div>
                    <?php if ($countActive === 0): ?>
                    <div class="alert alert-warning">No active subscribers to send to.</div>
                    <?php else: ?>
                    <button type="submit" class="btn btn-primary" data-loading="Sending..."
                        onclick="return confirm('Send this campaign to <?= number_format($countActive) ?> active subscriber<?= $countActive !== 1 ? 's' : '' ?>?')">
                        Send to <?= number_format($countActive) ?> Subscriber<?= $countActive !== 1 ? 's' : '' ?>
                    </button>
                    <?php endif ?>
                </form>
            </div>
        </div>
    </div>
    <?php else: ?>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
        <div class="card">
            <div class="card-body" style="padding:16px 20px">
                <div style="font-size:28px;font-weight:700;color:var(--color-text)"><?= number_format($countAll) ?></div>
                <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px">Total</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body" style="padding:16px 20px">
                <div style="font-size:28px;font-weight:700;color:var(--color-success,#22c55e)"><?= number_format($countActive) ?></div>
                <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px">Active</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body" style="padding:16px 20px">
                <div style="font-size:28px;font-weight:700;color:var(--color-text-muted)"><?= number_format($countUnsubscribed) ?></div>
                <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px">Unsubscribed</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body" style="padding:16px 20px">
                <div style="font-size:28px;font-weight:700;color:var(--color-primary,#6366f1)"><?= number_format($countThisMonth) ?></div>
                <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px">This Month</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-body" style="padding:14px 20px">
            <form method="GET" action="/admin/newsletter" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                <div style="flex:1;min-width:200px">
                    <label class="form-label" style="font-size:12px;margin-bottom:4px">Search</label>
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Email or name..." value="<?= e($search) ?>">
                </div>
                <div>
                    <label class="form-label" style="font-size:12px;margin-bottom:4px">Status</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All</option>
                        <option value="active"       <?= $status === 'active'       ? 'selected' : '' ?>>Active</option>
                        <option value="unsubscribed" <?= $status === 'unsubscribed' ? 'selected' : '' ?>>Unsubscribed</option>
                    </select>
                </div>
                <div style="display:flex;gap:6px">
                    <button class="btn btn-primary btn-sm">Filter</button>
                    <?php if ($search || $status): ?>
                    <a href="/admin/newsletter" class="btn btn-secondary btn-sm">Reset</a>
                    <?php endif ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="table-wrap">
            <?php if (empty($subs)): ?>
            <div style="padding:48px;text-align:center;color:var(--color-text-muted)">
                <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;opacity:.4"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <p style="margin:0;font-size:14px">No subscribers yet.</p>
            </div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Subscribed</th>
                        <th style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subs as $sub): ?>
                    <tr>
                        <td style="font-family:monospace;font-size:13px"><?= e($sub['email']) ?></td>
                        <td><?= $sub['name'] ? e($sub['name']) : '<span style="color:var(--color-text-muted)">—</span>' ?></td>
                        <td>
                            <?php if ($sub['status'] === 'active'): ?>
                            <span class="badge badge-success">Active</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">Unsubscribed</span>
                            <?php endif ?>
                        </td>
                        <td style="white-space:nowrap;font-size:12px;color:var(--color-text-muted)"><?= fdate($sub['created_at']) ?></td>
                        <td>
                            <div style="display:flex;gap:4px">
                                <?php if ($sub['status'] === 'active'): ?>
                                <form method="POST" style="display:inline">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="set_status">
                                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                    <input type="hidden" name="status" value="unsubscribed">
                                    <button class="btn btn-secondary btn-xs" title="Unsubscribe">Unsub</button>
                                </form>
                                <?php else: ?>
                                <form method="POST" style="display:inline">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="set_status">
                                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                    <input type="hidden" name="status" value="active">
                                    <button class="btn btn-success btn-xs" title="Resubscribe">Re-sub</button>
                                </form>
                                <?php endif ?>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Remove <?= e(addslashes($sub['email'])) ?>?')">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                    <button class="btn btn-danger btn-xs">Delete</button>
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
            <a href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&q=<?= urlencode($search) ?>"
               style="padding:4px 10px;border-radius:4px;font-size:13px;text-decoration:none;border:1px solid var(--color-border);<?= $i === $page ? 'background:var(--color-primary,#6366f1);color:#fff;border-color:var(--color-primary,#6366f1)' : 'color:var(--color-text)' ?>"><?= $i ?></a>
            <?php endfor ?>
            <span style="font-size:12px;color:var(--color-text-muted);margin-left:6px"><?= number_format($total) ?> total</span>
        </div>
        <?php endif ?>
    </div>

    <?php endif ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function() {
    var bodyEl = document.getElementById('newsletter-body');
    if (!bodyEl) return;
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    tinymce.init({
        license_key: 'gpl',
        selector: '#newsletter-body',
        plugins: 'anchor autolink charmap link lists searchreplace visualblocks wordcount',
        toolbar: 'undo redo | blocks | bold italic underline | link | numlist bullist | removeformat',
        height: 380,
        menubar: false,
        statusbar: false,
        branding: false,
        promotion: false,
        skin: isDark ? 'oxide-dark' : 'oxide',
        content_css: isDark ? 'dark' : 'default',
    });
    var form = bodyEl.closest('form');
    if (form) {
        form.addEventListener('submit', function() {
            if (tinymce.activeEditor) {
                bodyEl.value = tinymce.activeEditor.getContent();
            }
        });
    }
})();
</script>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Newsletter', $content, ['section' => 'newsletter']);
