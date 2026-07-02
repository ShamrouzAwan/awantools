<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$logger   = Logger::getInstance($db);
$allRoles = $db->fetchAll("SELECT * FROM roles ORDER BY name ASC");
$perPage  = (int)$settings->get('items_per_page', 25);
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;
$search   = Security::sanitize($_GET['q'] ?? '');
$action   = Security::sanitize($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    $userId = (int)($_POST['user_id'] ?? 0);

    // ── Edit user ─────────────────────────────────────────────────────────────
    if ($action === 'edit_user' && $userId) {
        $name     = Security::sanitize($_POST['name'] ?? '');
        $email    = Security::sanitizeEmail($_POST['email'] ?? '');
        $newRoles = array_map('trim', (array)($_POST['roles'] ?? []));

        if ($email && !Security::validateEmail($email)) {
            Session::flash('danger', 'Invalid email address.');
        } elseif ($email && $db->exists('users', 'email = ? AND id != ?', [$email, $userId])) {
            Session::flash('danger', 'That email is already used by another account.');
        } else {
            $updates = ['name' => $name, 'updated_at' => date('Y-m-d H:i:s')];
            if ($email) $updates['email'] = $email;
            $db->update('users', $updates, 'id = ?', [$userId]);

            $db->delete('user_roles', 'user_id = ?', [$userId]);
            foreach ($allRoles as $role) {
                if (in_array($role['slug'], $newRoles)) {
                    $db->insert('user_roles', ['user_id' => $userId, 'role_id' => $role['id']]);
                }
            }
            $logger->info("User #{$userId} updated by admin", [], $auth->id());
            Session::flash('success', 'User updated.');
        }
        redirect('/admin/users');
    }

    // ── Bulk action ───────────────────────────────────────────────────────────
    if ($action === 'bulk_action') {
        $bulkOp  = Security::sanitize($_POST['bulk_op'] ?? '');
        $userIds = array_map('intval', (array)($_POST['user_ids'] ?? []));
        $userIds = array_values(array_filter($userIds, fn($id) => $id > 0 && $id !== $auth->id()));

        if (empty($userIds)) {
            Session::flash('warning', 'No users selected.');
        } elseif (in_array($bulkOp, ['suspend', 'activate', 'delete'])) {
            $ph = implode(',', array_fill(0, count($userIds), '?'));
            if ($bulkOp === 'delete') {
                $db->query("DELETE FROM user_roles WHERE user_id IN ($ph)", $userIds);
                $db->query("DELETE FROM users WHERE id IN ($ph)", $userIds);
                $logger->warn("Bulk deleted " . count($userIds) . " users", [], $auth->id());
                Session::flash('success', count($userIds) . ' user(s) deleted.');
            } else {
                $status = ($bulkOp === 'suspend') ? 'suspended' : 'active';
                $params = array_merge([date('Y-m-d H:i:s'), $status], $userIds);
                $db->query("UPDATE users SET updated_at=?, status=? WHERE id IN ($ph)", $params);
                Session::flash('success', count($userIds) . ' user(s) ' . $bulkOp . 'd.');
            }
        }
        redirect('/admin/users');
    }

    // ── Single-user suspend / activate / delete ───────────────────────────────
    if ($userId && $userId !== $auth->id()) {
        if ($action === 'suspend') {
            $db->update('users', ['status' => 'suspended', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);
            $logger->info("User #{$userId} suspended", [], $auth->id());
            Session::flash('success', 'User suspended.');
        } elseif ($action === 'activate') {
            $db->update('users', ['status' => 'active', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);
            $logger->info("User #{$userId} activated", [], $auth->id());
            Session::flash('success', 'User activated.');
        } elseif ($action === 'delete') {
            $db->delete('user_roles', 'user_id = ?', [$userId]);
            $db->delete('users', 'id = ?', [$userId]);
            $logger->warn("User #{$userId} deleted", [], $auth->id());
            Session::flash('success', 'User deleted.');
        }
    } elseif ($userId === $auth->id()) {
        Session::flash('warning', 'You cannot modify your own account from this panel.');
    }
    redirect('/admin/users');
}

// Fetch users
$whereClause = $search
    ? "WHERE username LIKE ? OR email LIKE ? OR name LIKE ?"
    : "WHERE 1=1";
$params = $search ? ["%$search%", "%$search%", "%$search%"] : [];

$total = (int)($db->fetch("SELECT COUNT(*) as n FROM users $whereClause", $params)['n'] ?? 0);
$users = $db->fetchAll(
    "SELECT u.*, GROUP_CONCAT(r.slug) as role_slugs, GROUP_CONCAT(r.name) as role_names
     FROM users u
     LEFT JOIN user_roles ur ON ur.user_id = u.id
     LEFT JOIN roles r ON r.id = ur.role_id
     $whereClause
     GROUP BY u.id ORDER BY u.id DESC LIMIT ? OFFSET ?",
    [...$params, $perPage, $offset]
);

$totalPages = max(1, (int)ceil($total / $perPage));

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Users</div>
            <div class="page-subtitle"><?= number_format($total) ?> total users</div>
        </div>
    </div>
    <div class="topbar-actions">
        <form method="GET" style="display:flex;gap:8px">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search users..." class="form-control" style="width:220px">
            <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            <?php if ($search): ?><a href="/admin/users" class="btn btn-ghost btn-sm">Clear</a><?php endif ?>
        </form>
    </div>
</div>

<div class="page-body">
    <div class="card">

        <?php if (!empty($users)): ?>
        <!-- Bulk action bar -->
        <div id="bulk-bar" style="display:none;padding:10px 16px;border-bottom:1px solid var(--color-border);background:var(--color-background);display:flex;align-items:center;gap:10px">
            <span id="bulk-count" class="text-sm text-muted"></span>
            <select id="bulk-op" class="form-control" style="width:auto;padding:5px 10px;font-size:13px">
                <option value="">With selected...</option>
                <option value="activate">Activate</option>
                <option value="suspend">Suspend</option>
                <option value="delete">Delete</option>
            </select>
            <button type="button" class="btn btn-secondary btn-sm" onclick="applyBulk()">Apply</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="clearSelection()">Clear</button>
        </div>
        <?php endif ?>

        <div class="table-wrap">
            <?php if (empty($users)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"></div>
                <h3>No users found</h3>
                <p><?= $search ? 'Try a different search term.' : 'Users will appear here after registration.' ?></p>
            </div>
            <?php else: ?>
            <table class="table" id="users-table">
                <thead>
                    <tr>
                        <th style="width:36px"><input type="checkbox" id="select-all" title="Select all" onchange="toggleAll(this)"></th>
                        <th>ID</th>
                        <th>User</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php if ($u['id'] !== $auth->id()): ?><input type="checkbox" class="row-check" value="<?= $u['id'] ?>" onchange="updateBulkBar()"><?php endif ?></td>
                    <td class="text-muted">#<?= $u['id'] ?></td>
                    <td>
                        <div style="font-weight:500"><?= e($u['name'] ?: $u['username']) ?></div>
                        <div class="text-muted text-sm">@<?= e($u['username']) ?> &middot; <?= e($u['email']) ?></div>
                    </td>
                    <td>
                        <?php foreach (explode(',', $u['role_names'] ?? 'User') as $role): ?>
                        <span class="badge badge-neutral" style="margin-right:2px"><?= e(trim($role)) ?></span>
                        <?php endforeach ?>
                    </td>
                    <td>
                        <span class="badge <?= $u['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
                            <?= e($u['status']) ?>
                        </span>
                    </td>
                    <td class="text-muted"><?= fdate($u['created_at']) ?></td>
                    <td class="text-muted"><?= $u['last_login_at'] ? fdate($u['last_login_at'], 'M j, g:i a') : '—' ?></td>
                    <td>
                        <?php if ($u['id'] !== $auth->id()): ?>
                        <div style="display:flex;gap:4px;flex-wrap:wrap">
                            <button type="button" class="btn btn-ghost btn-sm"
                                onclick="openEdit(<?= $u['id'] ?>, <?= htmlspecialchars(json_encode($u['name']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($u['email']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($u['role_slugs'] ?? ''), ENT_QUOTES) ?>)">
                                Edit
                            </button>
                            <?php if ($u['status'] === 'active'): ?>
                            <form method="POST" style="display:inline">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="suspend">
                                <button type="submit" class="btn btn-ghost btn-sm" data-confirm="Suspend this user?">Suspend</button>
                            </form>
                            <?php else: ?>
                            <form method="POST" style="display:inline">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="activate">
                                <button type="submit" class="btn btn-ghost btn-sm">Activate</button>
                            </form>
                            <?php endif ?>
                            <form method="POST" style="display:inline">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-ghost btn-sm text-danger" data-confirm="Delete this user permanently? This cannot be undone.">Delete</button>
                            </form>
                        </div>
                        <?php else: ?>
                        <span class="text-muted text-sm">You</span>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
            <?php endif ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="card-footer">
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>">&lsaquo;</a>
                <?php endif ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i === $page): ?>
                <span class="current"><?= $i ?></span>
                <?php else: ?>
                <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
                <?php endif ?>
                <?php endfor ?>
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>">&rsaquo;</a>
                <?php endif ?>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- ── Edit User Modal ──────────────────────────────────────────────────────── -->
<div id="edit-modal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;padding:16px" onclick="if(event.target===this)closeEdit()">
    <div style="background:var(--color-card);border-radius:var(--radius-large);width:100%;max-width:480px;box-shadow:var(--shadow-large);overflow:hidden">
        <div style="padding:20px 24px;border-bottom:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between">
            <span style="font-weight:700;font-size:15px">Edit User</span>
            <button type="button" onclick="closeEdit()" style="background:none;border:none;cursor:pointer;color:var(--color-text-muted);font-size:18px;line-height:1;padding:2px 6px">&times;</button>
        </div>
        <form method="POST" action="/admin/users" id="edit-form">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="edit-user-id">
            <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px">
                <div class="form-group mb-0">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" id="edit-name" class="form-control" placeholder="Display name">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" id="edit-email" class="form-control" placeholder="user@example.com">
                    <div class="form-hint">Leave blank to keep existing email.</div>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Roles</label>
                    <div style="display:flex;flex-direction:column;gap:6px;margin-top:4px">
                        <?php foreach ($allRoles as $role): ?>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="checkbox" name="roles[]" value="<?= e($role['slug']) ?>" class="edit-role-check">
                            <span style="font-size:13px"><?= e($role['name']) ?></span>
                        </label>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
            <div style="padding:16px 24px;border-top:1px solid var(--color-border);display:flex;gap:10px;justify-content:flex-end">
                <button type="button" onclick="closeEdit()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Bulk action hidden form ────────────────────────────────────────────────── -->
<form id="bulk-form" method="POST" action="/admin/users" style="display:none">
    <?= Security::csrfField() ?>
    <input type="hidden" name="action" value="bulk_action">
    <input type="hidden" name="bulk_op" id="bulk-op-val">
    <div id="bulk-ids"></div>
</form>

<script>
// ── Edit modal ──────────────────────────────────────────────────────────────
function openEdit(id, name, email, roleSlugs) {
    document.getElementById('edit-user-id').value = id;
    document.getElementById('edit-name').value = name || '';
    document.getElementById('edit-email').value = '';
    document.getElementById('edit-email').placeholder = email;

    var slugs = roleSlugs ? roleSlugs.split(',') : [];
    document.querySelectorAll('.edit-role-check').forEach(function(cb) {
        cb.checked = slugs.indexOf(cb.value) !== -1;
    });

    var modal = document.getElementById('edit-modal');
    modal.style.display = 'flex';
    setTimeout(function(){ document.getElementById('edit-name').focus(); }, 50);
}

function closeEdit() {
    document.getElementById('edit-modal').style.display = 'none';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeEdit();
});

// ── Bulk actions ────────────────────────────────────────────────────────────
function toggleAll(cb) {
    document.querySelectorAll('.row-check').forEach(function(c){ c.checked = cb.checked; });
    updateBulkBar();
}

function updateBulkBar() {
    var checked = document.querySelectorAll('.row-check:checked');
    var bar = document.getElementById('bulk-bar');
    if (!bar) return;
    if (checked.length > 0) {
        bar.style.display = 'flex';
        document.getElementById('bulk-count').textContent = checked.length + ' user(s) selected';
    } else {
        bar.style.display = 'none';
        document.getElementById('select-all').checked = false;
    }
}

function clearSelection() {
    document.querySelectorAll('.row-check').forEach(function(c){ c.checked = false; });
    document.getElementById('select-all').checked = false;
    updateBulkBar();
}

function applyBulk() {
    var op = document.getElementById('bulk-op').value;
    if (!op) { alert('Please select an action.'); return; }

    var checked = document.querySelectorAll('.row-check:checked');
    if (!checked.length) { alert('No users selected.'); return; }

    var label = op === 'delete' ? 'permanently delete' : op;
    if (!confirm('Are you sure you want to ' + label + ' ' + checked.length + ' user(s)?')) return;

    document.getElementById('bulk-op-val').value = op;
    var container = document.getElementById('bulk-ids');
    container.innerHTML = '';
    checked.forEach(function(cb) {
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'user_ids[]';
        inp.value = cb.value;
        container.appendChild(inp);
    });
    document.getElementById('bulk-form').submit();
}

// Hide bulk bar on load (display:flex in markup for non-JS but we hide via JS)
document.addEventListener('DOMContentLoaded', function() {
    var bar = document.getElementById('bulk-bar');
    if (bar) bar.style.display = 'none';
});
</script>

<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Users', $content, ['section' => 'users']);
