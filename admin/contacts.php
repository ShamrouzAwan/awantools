<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$action = Security::sanitize($_GET['action'] ?? 'list');
$id     = (int)($_GET['id'] ?? 0);

// ── POST handlers ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $postAction = Security::sanitize($_POST['action'] ?? '');

    if ($postAction === 'delete' && !empty($_POST['id'])) {
        $db->delete('contact_messages', 'id = ?', [(int)$_POST['id']]);
        Session::flash('success', 'Message deleted.');
    } elseif ($postAction === 'delete_all') {
        $db->query('DELETE FROM contact_messages');
        Session::flash('success', 'All contact messages deleted.');
    } elseif ($postAction === 'bulk_delete' && !empty($_POST['ids'])) {
        $ids = array_map('intval', (array)$_POST['ids']);
        $ids = array_filter($ids);
        if (!empty($ids)) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->query("DELETE FROM contact_messages WHERE id IN ({$ph})", $ids);
            Session::flash('success', count($ids) . ' message(s) deleted.');
        }
    }
    redirect('/admin/contacts');
}

// ── Detail view ─────────────────────────────────────────────────────────────
if ($action === 'view' && $id) {
    $msg = $db->fetch('SELECT * FROM contact_messages WHERE id = ?', [$id]);
    if (!$msg) { Session::flash('danger', 'Message not found.'); redirect('/admin/contacts'); }

    ob_start();
    ?>
    <div class="page-header">
        <div class="page-header-left">
            <div>
                <div class="page-title">Contact Message</div>
                <div class="page-subtitle"><?= e($msg['name']) ?> &mdash; <?= e($msg['email']) ?></div>
            </div>
        </div>
        <div class="topbar-actions">
            <a href="/admin/contacts" class="btn btn-secondary btn-sm">Back</a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this message?')">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div>
    </div>
    <div class="page-body">
        <div class="card" style="max-width:680px">
            <div class="card-body">
                <div style="display:grid;grid-template-columns:100px 1fr;gap:8px 16px;font-size:14px;margin-bottom:20px">
                    <span style="color:var(--color-text-muted);font-weight:500">From</span>
                    <span><?= e($msg['name']) ?> &lt;<?= e($msg['email']) ?>&gt;</span>
                    <span style="color:var(--color-text-muted);font-weight:500">Subject</span>
                    <span style="font-weight:600"><?= e($msg['subject']) ?></span>
                    <span style="color:var(--color-text-muted);font-weight:500">Received</span>
                    <span><?= fdate($msg['created_at']) ?></span>
                    <?php if ($msg['ip']): ?>
                    <span style="color:var(--color-text-muted);font-weight:500">IP</span>
                    <span style="font-family:monospace"><?= e($msg['ip']) ?></span>
                    <?php endif ?>
                </div>
                <div style="border-top:1px solid var(--color-border);padding-top:16px;font-size:14px;line-height:1.7;white-space:pre-wrap;color:var(--color-text)"><?= e($msg['message']) ?></div>
                <?php if ($msg['email']): ?>
                <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--color-border)">
                    <a href="mailto:<?= e($msg['email']) ?>?subject=Re: <?= rawurlencode($msg['subject'] ?? '') ?>" class="btn btn-primary btn-sm">Reply via Email</a>
                </div>
                <?php endif ?>
            </div>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    require THEMES_PATH . '/default/templates/admin.php';
    render_admin('Contact Message', $content, ['section' => 'contacts']);
    return;
}

// ── List view ───────────────────────────────────────────────────────────────
$perPage = 30;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$total   = $db->count('contact_messages');
$messages = $db->fetchAll('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT ? OFFSET ?', [$perPage, $offset]) ?: [];
$totalPages = max(1, (int)ceil($total / $perPage));

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Contact Messages</div>
            <div class="page-subtitle"><?= number_format($total) ?> total message<?= $total !== 1 ? 's' : '' ?></div>
        </div>
    </div>
    <?php if (!empty($messages)): ?>
    <div class="topbar-actions">
        <button class="btn btn-secondary btn-sm" onclick="toggleBulk()">Bulk Select</button>
        <form method="POST" id="delete-all-form" onsubmit="return confirm('Delete ALL contact messages? This cannot be undone.')">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="delete_all">
            <button type="submit" class="btn btn-danger btn-sm">Delete All</button>
        </form>
    </div>
    <?php endif ?>
</div>

<div class="page-body">
    <!-- Bulk action bar (hidden until bulk mode) -->
    <form method="POST" id="bulk-form" style="display:none;margin-bottom:12px">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="bulk_delete">
        <div style="display:flex;gap:8px;align-items:center">
            <label style="font-size:13px;cursor:pointer">
                <input type="checkbox" id="select-all-cb" onchange="toggleSelectAll(this.checked)"> Select All
            </label>
            <span id="bulk-count" style="font-size:12px;color:var(--color-text-muted)">0 selected</span>
            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete selected messages?')">Delete Selected</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleBulk()">Cancel</button>
        </div>
    </form>

    <?php if (empty($messages)): ?>
    <div class="card">
        <div class="card-body" style="text-align:center;padding:48px;color:var(--color-text-muted)">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 16px;display:block;opacity:.4"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <p style="margin:0;font-size:14px">No contact messages yet.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:28px" id="bulk-col" class="bulk-col" style="display:none"></th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th style="width:140px">Received</th>
                        <th style="width:100px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $m): ?>
                    <tr>
                        <td class="bulk-col" style="display:none">
                            <input type="checkbox" name="ids[]" value="<?= $m['id'] ?>" form="bulk-form" class="bulk-cb" onchange="updateBulkCount()">
                        </td>
                        <td style="font-weight:500;font-size:13px"><?= e($m['name']) ?></td>
                        <td style="font-size:13px"><a href="mailto:<?= e($m['email']) ?>" style="color:var(--color-primary);text-decoration:none"><?= e($m['email']) ?></a></td>
                        <td style="font-size:13px;color:var(--color-text-muted);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($m['subject']) ?></td>
                        <td style="font-size:12px;color:var(--color-text-muted)"><?= fdate($m['created_at']) ?></td>
                        <td>
                            <a href="/admin/contacts?action=view&id=<?= $m['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                            <form method="POST" style="display:inline">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-danger)">&#x2715;</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex;gap:6px;justify-content:center;margin-top:16px;flex-wrap:wrap">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="?page=<?= $p ?>" class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>"><?= $p ?></a>
        <?php endfor ?>
    </div>
    <?php endif ?>
    <?php endif ?>
</div>

<script>
var bulkActive = false;
function toggleBulk() {
    bulkActive = !bulkActive;
    document.querySelectorAll('.bulk-col').forEach(function(el){ el.style.display = bulkActive ? '' : 'none'; });
    document.getElementById('bulk-form').style.display = bulkActive ? 'block' : 'none';
}
function toggleSelectAll(checked) {
    document.querySelectorAll('.bulk-cb').forEach(function(cb){ cb.checked = checked; });
    updateBulkCount();
}
function updateBulkCount() {
    var n = document.querySelectorAll('.bulk-cb:checked').length;
    document.getElementById('bulk-count').textContent = n + ' selected';
    document.getElementById('select-all-cb').checked = n === document.querySelectorAll('.bulk-cb').length;
}
</script>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Contact Messages', $content, ['section' => 'contacts']);
