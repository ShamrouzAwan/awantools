<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $postAction = Security::sanitize($_POST['action'] ?? '');

    if ($postAction === 'update_status') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = Security::sanitize($_POST['status'] ?? '');
        $reply  = Security::sanitize($_POST['admin_reply'] ?? '');
        $valid  = ['open', 'investigating', 'resolved', 'wont-fix', 'duplicate'];
        if ($id && in_array($status, $valid)) {
            $db->update('issue_reports', ['status' => $status, 'admin_reply' => $reply ?: null], 'id = ?', [$id]);
            Session::flash('success', 'Issue report updated.');
        }
    } elseif ($postAction === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->delete('issue_reports', 'id = ?', [$id]);
            Session::flash('success', 'Report deleted.');
        }
    } elseif ($postAction === 'bulk_delete' && !empty($_POST['ids'])) {
        $ids = array_filter(array_map('intval', (array)$_POST['ids']));
        if (!empty($ids)) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->query("DELETE FROM issue_reports WHERE id IN ({$ph})", $ids);
            Session::flash('success', count($ids) . ' report(s) deleted.');
        }
    }
    redirect('/admin/reports');
}

$statusFilter = Security::sanitize($_GET['status'] ?? '');
$perPage = 25;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$total   = $db->count('issue_reports', $statusFilter ? 'status = ?' : '1=1', $statusFilter ? [$statusFilter] : []);
$reports = $db->fetchAll("SELECT * FROM issue_reports" . ($statusFilter ? " WHERE status = ?" : "") . " ORDER BY id DESC LIMIT ? OFFSET ?",
    $statusFilter ? [$statusFilter, $perPage, $offset] : [$perPage, $offset]);
$totalPages = max(1, (int)ceil($total / $perPage));
$counts = [];
foreach (['open', 'investigating', 'resolved', 'wont-fix', 'duplicate'] as $s) {
    $counts[$s] = $db->count('issue_reports', "status = ?", [$s]);
}

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Issue Reports</div>
            <div class="page-subtitle"><?= number_format($total) ?> reports<?= $statusFilter ? " (status: {$statusFilter})" : '' ?></div>
        </div>
    </div>
    <?php if (!empty($reports)): ?>
    <div class="topbar-actions">
        <button class="btn btn-secondary btn-sm" onclick="toggleBulk()">Bulk Select</button>
    </div>
    <?php endif ?>
</div>
<div class="page-body">

    <!-- Bulk form (hidden until activated) -->
    <form method="POST" id="bulk-form" style="display:none;margin-bottom:12px">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="bulk_delete">
        <div style="display:flex;gap:8px;align-items:center">
            <label style="font-size:13px;cursor:pointer">
                <input type="checkbox" id="select-all-cb" onchange="toggleSelectAll(this.checked)"> Select All
            </label>
            <span id="bulk-count" style="font-size:12px;color:var(--color-text-muted)">0 selected</span>
            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete selected reports?')">Delete Selected</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleBulk()">Cancel</button>
        </div>
    </form>

    <div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
        <a href="/admin/reports" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-secondary' ?>">All</a>
        <?php foreach ($counts as $s => $c): ?>
        <a href="/admin/reports?status=<?= urlencode($s) ?>" class="btn btn-sm <?= $statusFilter === $s ? 'btn-primary' : 'btn-secondary' ?>">
            <?= ucfirst($s) ?> <span style="background:rgba(0,0,0,0.1);padding:1px 5px;border-radius:8px;font-size:10px;margin-left:2px"><?= $c ?></span>
        </a>
        <?php endforeach ?>
    </div>

    <div class="card">
        <?php if (empty($reports)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"></div>
            <h3>No Issue Reports</h3>
            <p>Reports submitted via the "Report Issue" button on plugin pages will appear here.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="bulk-col" style="width:28px;display:none"></th>
                        <th>#</th><th>Plugin</th><th>Reporter</th><th>Issue</th><th>Status</th><th>Date</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reports as $r): ?>
                <tr>
                    <td class="bulk-col" style="display:none">
                        <input type="checkbox" name="ids[]" value="<?= $r['id'] ?>" form="bulk-form" class="bulk-cb" onchange="updateBulkCount()">
                    </td>
                    <td class="text-muted text-sm"><?= $r['id'] ?></td>
                    <td><span class="badge badge-neutral"><?= e($r['plugin_slug'] ?? 'Platform') ?></span></td>
                    <td>
                        <div style="font-size:13px"><?= e($r['reporter_name']) ?></div>
                        <div style="font-size:12px;color:var(--color-text-muted)"><?= e($r['reporter_email']) ?></div>
                    </td>
                    <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px"><?= e($r['description']) ?></td>
                    <td>
                        <span class="badge badge-<?= $r['status'] === 'resolved' ? 'success' : ($r['status'] === 'open' ? 'danger' : ($r['status'] === 'investigating' ? 'warning' : 'neutral')) ?>">
                            <?= e($r['status']) ?>
                        </span>
                    </td>
                    <td class="text-sm text-muted"><?= fdate($r['created_at']) ?></td>
                    <td style="display:flex;gap:4px">
                        <button class="btn btn-ghost btn-sm" onclick="openModal('rpt-modal-<?= $r['id'] ?>')">View</button>
                        <form method="POST" style="display:inline">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-danger)"
                                    onclick="return confirm('Delete this report?')">&#x2715;</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div style="display:flex;gap:6px;justify-content:center;padding:16px;flex-wrap:wrap">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?<?= $statusFilter ? 'status=' . urlencode($statusFilter) . '&' : '' ?>page=<?= $p ?>"
               class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>"><?= $p ?></a>
            <?php endfor ?>
        </div>
        <?php endif ?>
        <?php endif ?>
    </div>
</div>

<!-- Detail modals -->
<?php foreach ($reports as $r): ?>
<div class="modal-overlay" id="rpt-modal-<?= $r['id'] ?>" style="display:none">
    <div class="modal-box" style="max-width:600px">
        <div class="modal-header">
            <div class="modal-title">Issue #<?= $r['id'] ?> — <?= e($r['reporter_name']) ?></div>
            <button class="modal-close">&times;</button>
        </div>
        <div style="margin-bottom:12px;display:flex;gap:8px;align-items:center">
            <span class="badge badge-neutral"><?= e($r['plugin_slug'] ?? 'Platform') ?></span>
            <span class="badge badge-<?= $r['status'] === 'resolved' ? 'success' : ($r['status'] === 'open' ? 'danger' : 'warning') ?>"><?= e($r['status']) ?></span>
            <span style="font-size:12px;color:var(--color-text-muted)"><?= e($r['reporter_email']) ?></span>
        </div>
        <div style="margin-bottom:16px">
            <div class="form-hint" style="margin-bottom:4px">Description</div>
            <div style="background:var(--color-background);padding:12px;border-radius:4px;font-size:13px;line-height:1.6;white-space:pre-wrap"><?= e($r['description']) ?></div>
        </div>
        <?php if ($r['admin_reply']): ?>
        <div style="margin-bottom:16px">
            <div class="form-hint" style="margin-bottom:4px">Previous Admin Reply</div>
            <div style="background:var(--color-background);padding:12px;border-radius:4px;font-size:13px;line-height:1.6"><?= e($r['admin_reply']) ?></div>
        </div>
        <?php endif ?>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <?php foreach (['open','investigating','resolved','wont-fix','duplicate'] as $s): ?>
                        <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Reply to Reporter <span style="font-weight:400;color:var(--color-text-muted)">(internal note)</span></label>
                <textarea name="admin_reply" class="form-control" rows="3" placeholder="Optional reply or internal note…"><?= e($r['admin_reply'] ?? '') ?></textarea>
            </div>
            <div class="modal-footer">
                <?php if ($r['reporter_email']): ?>
                <a href="mailto:<?= e($r['reporter_email']) ?>?subject=Re: Issue Report #<?= $r['id'] ?>" class="btn btn-secondary btn-sm">Reply via Email</a>
                <?php endif ?>
                <button type="button" class="btn btn-ghost modal-close">Close</button>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach ?>

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
}
</script>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Issue Reports', $content, ['section' => 'reports']);
