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
        $valid  = ['new', 'reviewing', 'quoted', 'accepted', 'declined', 'archived'];
        if ($id && in_array($status, $valid)) {
            $db->update('quote_requests', ['status' => $status, 'admin_notes' => $notes ?: null], 'id = ?', [$id]);
            Session::flash('success', 'Quote request updated.');
        }
    } elseif ($postAction === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) { $db->delete('quote_requests', 'id = ?', [$id]); Session::flash('success', 'Quote deleted.'); }
    } elseif ($postAction === 'bulk_delete' && !empty($_POST['ids'])) {
        $ids = array_filter(array_map('intval', (array)$_POST['ids']));
        if (!empty($ids)) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->query("DELETE FROM quote_requests WHERE id IN ({$ph})", $ids);
            Session::flash('success', count($ids) . ' quote(s) deleted.');
        }
    }
    redirect('/admin/quotes');
}

$status   = Security::sanitize($_GET['status'] ?? '');
$perPage  = 25;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;

$where   = $status ? "status = ?" : "1=1";
$params  = $status ? [$status] : [];
$total   = $db->count('quote_requests', $where, $params);
$quotes  = $db->fetchAll("SELECT * FROM quote_requests" . ($status ? " WHERE status = ?" : "") . " ORDER BY id DESC LIMIT ? OFFSET ?",
    $status ? [$status, $perPage, $offset] : [$perPage, $offset]);
$totalPages = max(1, (int)ceil($total / $perPage));

$counts = [];
foreach (['new', 'reviewing', 'quoted', 'accepted', 'declined'] as $s) {
    $counts[$s] = $db->count('quote_requests', "status = ?", [$s]);
}

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Quote Requests</div>
            <div class="page-subtitle"><?= number_format($total) ?> requests<?= $status ? " (status: {$status})" : '' ?></div>
        </div>
    </div>
    <div class="topbar-actions">
        <button class="btn btn-secondary btn-sm" onclick="toggleBulk()">Bulk Select</button>
        <a href="/get-a-quote" target="_blank" class="btn btn-secondary btn-sm">View Form</a>
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
            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete selected quotes?')">Delete Selected</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleBulk()">Cancel</button>
        </div>
    </form>
    <div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
        <a href="/admin/quotes" class="btn btn-sm <?= !$status ? 'btn-primary' : 'btn-secondary' ?>">All</a>
        <?php foreach ($counts as $s => $c): ?>
        <a href="/admin/quotes?status=<?= $s ?>" class="btn btn-sm <?= $status === $s ? 'btn-primary' : 'btn-secondary' ?>">
            <?= ucfirst($s) ?>
            <span style="background:rgba(0,0,0,0.1);padding:1px 5px;border-radius:8px;font-size:10px;margin-left:2px"><?= $c ?></span>
        </a>
        <?php endforeach ?>
    </div>

    <div class="card">
        <?php if (empty($quotes)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">💼</div>
            <h3>No Quote Requests</h3>
            <p>Quote requests submitted via the website will appear here.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="bulk-col" style="width:28px;display:none"></th>
                        <th>#</th><th>Name / Email</th><th>Company</th><th>Budget</th><th>Timeline</th><th>Status</th><th>Date</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($quotes as $q): ?>
                <tr>
                    <td class="bulk-col" style="display:none">
                        <input type="checkbox" name="ids[]" value="<?= $q['id'] ?>" form="bulk-form" class="bulk-cb" onchange="updateBulkCount()">
                    </td>
                    <td class="text-muted text-sm"><?= $q['id'] ?></td>
                    <td>
                        <div style="font-weight:600;font-size:13px"><?= e($q['name']) ?></div>
                        <div style="font-size:12px;color:var(--color-text-muted)"><a href="mailto:<?= e($q['email']) ?>" style="color:var(--color-primary)"><?= e($q['email']) ?></a></div>
                    </td>
                    <td class="text-sm text-muted"><?= e($q['company'] ?? '—') ?></td>
                    <td class="text-sm"><?= e($q['budget'] ?? '—') ?></td>
                    <td class="text-sm"><?= e($q['timeline'] ?? '—') ?></td>
                    <td>
                        <span class="badge badge-<?= $q['status'] === 'accepted' ? 'success' : ($q['status'] === 'declined' ? 'danger' : ($q['status'] === 'new' ? 'warning' : 'neutral')) ?>">
                            <?= e($q['status']) ?>
                        </span>
                    </td>
                    <td class="text-sm text-muted"><?= fdate($q['created_at']) ?></td>
                    <td style="display:flex;gap:4px">
                        <button class="btn btn-ghost btn-sm" onclick="openModal('q-modal-<?= $q['id'] ?>')">View</button>
                        <form method="POST" style="display:inline">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $q['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-danger)"
                                    onclick="return confirm('Delete this quote request?')">&#x2715;</button>
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

<!-- Detail Modals -->
<?php foreach ($quotes as $q): ?>
<div class="modal-overlay" id="q-modal-<?= $q['id'] ?>" style="display:none">
    <div class="modal-box" style="max-width:640px">
        <div class="modal-header">
            <div class="modal-title">Quote Request #<?= $q['id'] ?> — <?= e($q['name']) ?></div>
            <button class="modal-close">&times;</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div><div class="form-hint" style="margin-bottom:2px">Email</div><div style="font-weight:500"><?= e($q['email']) ?></div></div>
            <div><div class="form-hint" style="margin-bottom:2px">Company</div><div><?= e($q['company'] ?? '—') ?></div></div>
            <div><div class="form-hint" style="margin-bottom:2px">Budget</div><div><?= e($q['budget'] ?? '—') ?></div></div>
            <div><div class="form-hint" style="margin-bottom:2px">Timeline</div><div><?= e($q['timeline'] ?? '—') ?></div></div>
        </div>
        <div style="margin-bottom:16px">
            <div class="form-hint" style="margin-bottom:4px">Project Description</div>
            <div style="background:var(--color-background);padding:14px;border-radius:var(--radius-small);font-size:13px;line-height:1.6;white-space:pre-wrap"><?= e($q['project_description']) ?></div>
        </div>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" value="<?= $q['id'] ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Update Status</label>
                    <select name="status" class="form-control">
                        <?php foreach (['new','reviewing','quoted','accepted','declined','archived'] as $s): ?>
                        <option value="<?= $s ?>" <?= $q['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Admin Notes</label>
                    <input type="text" name="admin_notes" class="form-control" value="<?= e($q['admin_notes'] ?? '') ?>" placeholder="Internal notes…">
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($q['email']): ?>
                <a href="mailto:<?= e($q['email']) ?>?subject=Re: Your Quote Request #<?= $q['id'] ?>" class="btn btn-secondary btn-sm">Reply via Email</a>
                <?php endif ?>
                <button type="button" class="btn btn-ghost modal-close">Close</button>
                <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach ?>

<?php
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
$content = ob_get_clean() . $content;
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Quote Requests', $content, ['section' => 'quotes']);
