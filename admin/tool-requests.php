<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $postAction = Security::sanitize($_POST['action'] ?? 'update_status');

    if ($postAction === 'update_status') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = Security::sanitize($_POST['status'] ?? '');
        $notes  = Security::sanitize($_POST['admin_notes'] ?? '');
        $valid  = ['new', 'reviewing', 'planned', 'in-progress', 'completed', 'rejected'];
        if ($id && in_array($status, $valid)) {
            $db->update('tool_requests', ['status' => $status, 'admin_notes' => $notes ?: null], 'id = ?', [$id]);
            Session::flash('success', 'Tool request updated.');
        }
    } elseif ($postAction === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) { $db->delete('tool_requests', 'id = ?', [$id]); Session::flash('success', 'Request deleted.'); }
    } elseif ($postAction === 'bulk_delete' && !empty($_POST['ids'])) {
        $ids = array_filter(array_map('intval', (array)$_POST['ids']));
        if (!empty($ids)) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->query("DELETE FROM tool_requests WHERE id IN ({$ph})", $ids);
            Session::flash('success', count($ids) . ' request(s) deleted.');
        }
    }
    redirect('/admin/tool-requests');
}

$statusFilter = Security::sanitize($_GET['status'] ?? '');
$perPage = 25;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$total    = $db->count('tool_requests', $statusFilter ? 'status = ?' : '1=1', $statusFilter ? [$statusFilter] : []);
$requests = $db->fetchAll("SELECT * FROM tool_requests" . ($statusFilter ? " WHERE status = ?" : "") . " ORDER BY id DESC LIMIT ? OFFSET ?",
    $statusFilter ? [$statusFilter, $perPage, $offset] : [$perPage, $offset]);

$counts = [];
foreach (['new', 'reviewing', 'planned', 'in-progress', 'completed', 'rejected'] as $s) {
    $counts[$s] = $db->count('tool_requests', "status = ?", [$s]);
}

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Tool Requests</div>
            <div class="page-subtitle"><?= number_format($total) ?> requests</div>
        </div>
    </div>
    <div class="topbar-actions">
        <button class="btn btn-secondary btn-sm" onclick="toggleBulk()">Bulk Select</button>
        <a href="/request-tool" target="_blank" class="btn btn-secondary btn-sm">View Form</a>
    </div>
</div>
<div class="page-body">
    <form method="POST" id="bulk-form" style="display:none;margin-bottom:12px">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="bulk_delete">
        <div style="display:flex;gap:8px;align-items:center">
            <label style="font-size:13px;cursor:pointer">
                <input type="checkbox" id="select-all-cb" onchange="toggleSelectAll(this.checked)"> Select All
            </label>
            <span id="bulk-count" style="font-size:12px;color:var(--color-text-muted)">0 selected</span>
            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete selected requests?')">Delete Selected</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleBulk()">Cancel</button>
        </div>
    </form>
    <div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
        <a href="/admin/tool-requests" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-secondary' ?>">All</a>
        <?php foreach ($counts as $s => $c): ?>
        <a href="/admin/tool-requests?status=<?= $s ?>" class="btn btn-sm <?= $statusFilter === $s ? 'btn-primary' : 'btn-secondary' ?>">
            <?= ucfirst(str_replace('-', ' ', $s)) ?>
            <span style="background:rgba(0,0,0,0.1);padding:1px 5px;border-radius:8px;font-size:10px;margin-left:2px"><?= $c ?></span>
        </a>
        <?php endforeach ?>
    </div>
    <div class="card">
        <?php if (empty($requests)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"></div>
            <h3>No Tool Requests</h3>
            <p>Requests submitted via the website will appear here.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="bulk-col" style="width:28px;display:none"></th>
                        <th>#</th><th>Title</th><th>Type</th><th>Requester</th><th>Status</th><th>Date</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td class="bulk-col" style="display:none">
                        <input type="checkbox" name="ids[]" value="<?= $r['id'] ?>" form="bulk-form" class="bulk-cb" onchange="updateBulkCount()">
                    </td>
                    <td class="text-muted text-sm"><?= $r['id'] ?></td>
                    <td style="font-weight:600;font-size:13px"><?= e($r['title']) ?></td>
                    <td><span class="badge badge-info"><?= e($r['request_type']) ?></span></td>
                    <td>
                        <div style="font-size:13px"><?= e($r['name']) ?></div>
                        <div style="font-size:12px;color:var(--color-text-muted)"><?= e($r['email']) ?></div>
                    </td>
                    <td>
                        <span class="badge badge-<?= $r['status'] === 'completed' ? 'success' : ($r['status'] === 'rejected' ? 'danger' : ($r['status'] === 'new' ? 'warning' : 'neutral')) ?>">
                            <?= e($r['status']) ?>
                        </span>
                    </td>
                    <td class="text-sm text-muted"><?= fdate($r['created_at']) ?></td>
                    <td style="display:flex;gap:4px">
                        <button class="btn btn-ghost btn-sm" onclick="openModal('tr-modal-<?= $r['id'] ?>')">View</button>
                        <form method="POST" style="display:inline">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-danger)"
                                    onclick="return confirm('Delete this request?')">&#x2715;</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>
    </div>
</div>

<?php foreach ($requests as $r): ?>
<div class="modal-overlay" id="tr-modal-<?= $r['id'] ?>" style="display:none">
    <div class="modal-box" style="max-width:600px">
        <div class="modal-header">
            <div class="modal-title"><?= e($r['title']) ?></div>
            <button class="modal-close">&times;</button>
        </div>
        <div style="margin-bottom:12px;display:flex;gap:8px">
            <span class="badge badge-info"><?= e($r['request_type']) ?></span>
            <span class="badge badge-neutral">by <?= e($r['name']) ?></span>
        </div>
        <div style="margin-bottom:16px">
            <div class="form-hint" style="margin-bottom:4px">Description</div>
            <div style="background:var(--color-background);padding:12px;border-radius:4px;font-size:13px;line-height:1.6;white-space:pre-wrap"><?= e($r['description']) ?></div>
        </div>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Update Status</label>
                    <select name="status" class="form-control">
                        <?php foreach (['new','reviewing','planned','in-progress','completed','rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('-', ' ', $s)) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Admin Notes</label>
                    <input type="text" name="admin_notes" class="form-control" value="<?= e($r['admin_notes'] ?? '') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($r['email']): ?>
                <a href="mailto:<?= e($r['email']) ?>?subject=Re: Your Tool Request" class="btn btn-secondary btn-sm">Reply via Email</a>
                <?php endif ?>
                <button type="button" class="btn btn-ghost modal-close">Close</button>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach ?>

<?php
$content = ob_get_clean();
$content .= '<script>
var bulkActive = false;
function toggleBulk() {
    bulkActive = !bulkActive;
    document.querySelectorAll(".bulk-col").forEach(function(el){ el.style.display = bulkActive ? "" : "none"; });
    document.getElementById("bulk-form").style.display = bulkActive ? "block" : "none";
}
function toggleSelectAll(checked) {
    document.querySelectorAll(".bulk-cb").forEach(function(cb){ cb.checked = checked; });
    updateBulkCount();
}
function updateBulkCount() {
    var n = document.querySelectorAll(".bulk-cb:checked").length;
    document.getElementById("bulk-count").textContent = n + " selected";
}
</script>';
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Tool Requests', $content, ['section' => 'tool-requests']);
