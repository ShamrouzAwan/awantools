<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../_core/Notifications.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read' && !empty($_POST['id'])) {
        Notifications::markRead($db, (int) $_POST['id']);
    } elseif ($action === 'mark_all_read') {
        Notifications::markAllRead($db);
        Session::flash('success', 'All notifications marked as read.');
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        Notifications::delete($db, (int) $_POST['id']);
    } elseif ($action === 'delete_all') {
        Notifications::deleteAll($db);
        Session::flash('success', 'All notifications deleted.');
    }
    redirect('/admin/notifications');
}

$filter = Security::sanitize($_GET['filter'] ?? 'all');
if (!in_array($filter, ['all', 'unread'])) $filter = 'all';

$where = $filter === 'unread' ? 'is_read = 0' : '1=1';
$notifications = $db->fetchAll("SELECT * FROM notifications WHERE {$where} ORDER BY created_at DESC LIMIT 200") ?: [];
$unreadCount   = Notifications::unreadCount($db);

$typeIcons = [
    'contact'   => '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
    'quote'     => '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    'tool'      => '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
    'user'      => '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'report'    => '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>',
    'newsletter'=> '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
    'info'      => '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
];
$typeColors = [
    'contact'    => 'badge-info',
    'quote'      => 'badge-primary',
    'tool'       => 'badge-warning',
    'user'       => 'badge-success',
    'report'     => 'badge-danger',
    'newsletter' => 'badge-neutral',
    'info'       => 'badge-neutral',
];

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Notifications
                <?php if ($unreadCount > 0): ?>
                <span class="badge badge-danger" style="font-size:11px;margin-left:6px"><?= $unreadCount ?> new</span>
                <?php endif ?>
            </div>
            <div class="page-subtitle">Admin activity and platform events</div>
        </div>
    </div>
    <div class="page-header-right" style="display:flex;gap:8px;align-items:center">
        <?php if ($unreadCount > 0): ?>
        <form method="POST" style="display:inline">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="mark_all_read">
            <button type="submit" class="btn btn-secondary btn-sm">Mark All Read</button>
        </form>
        <?php endif ?>
        <?php if (!empty($notifications)): ?>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete all notifications?')">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="delete_all">
            <button type="submit" class="btn btn-danger btn-sm">Delete All</button>
        </form>
        <?php endif ?>
    </div>
</div>

<div class="page-body">
    <!-- Filter tabs -->
    <div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:1px solid var(--color-border);padding-bottom:0">
        <a href="?filter=all" style="padding:8px 14px;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;border-bottom:2px solid <?= $filter==='all' ? 'var(--color-primary)' : 'transparent' ?>;color:<?= $filter==='all' ? 'var(--color-primary)' : 'var(--color-text-secondary)' ?>;margin-bottom:-1px">
            All
        </a>
        <a href="?filter=unread" style="padding:8px 14px;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;border-bottom:2px solid <?= $filter==='unread' ? 'var(--color-primary)' : 'transparent' ?>;color:<?= $filter==='unread' ? 'var(--color-primary)' : 'var(--color-text-secondary)' ?>;margin-bottom:-1px">
            Unread<?= $unreadCount > 0 ? " ({$unreadCount})" : '' ?>
        </a>
    </div>

    <?php if (empty($notifications)): ?>
    <div class="card">
        <div class="card-body" style="text-align:center;padding:48px;color:var(--color-text-muted)">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 16px;display:block;opacity:.4"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <p style="margin:0;font-size:14px"><?= $filter === 'unread' ? 'No unread notifications.' : 'No notifications yet.' ?></p>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:36px"></th>
                        <th>Notification</th>
                        <th style="width:100px">Type</th>
                        <th style="width:140px">Time</th>
                        <th style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $n): ?>
                    <tr style="<?= $n['is_read'] ? '' : 'background:var(--color-primary-subtle, rgba(59,130,246,.04))' ?>">
                        <td style="text-align:center">
                            <?php if (!$n['is_read']): ?>
                            <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--color-primary)"></span>
                            <?php endif ?>
                        </td>
                        <td>
                            <div style="font-weight:<?= $n['is_read'] ? '400' : '600' ?>;font-size:13px;color:var(--color-text)">
                                <?= e($n['title']) ?>
                            </div>
                            <?php if ($n['message']): ?>
                            <div style="font-size:12px;color:var(--color-text-muted);margin-top:2px"><?= e($n['message']) ?></div>
                            <?php endif ?>
                            <?php if ($n['url']): ?>
                            <a href="<?= e($n['url']) ?>" style="font-size:12px;color:var(--color-primary);text-decoration:none">View →</a>
                            <?php endif ?>
                        </td>
                        <td>
                            <span class="badge <?= $typeColors[$n['type']] ?? 'badge-neutral' ?>" style="display:inline-flex;align-items:center;gap:4px">
                                <?= $typeIcons[$n['type']] ?? $typeIcons['info'] ?>
                                <?= e(ucfirst($n['type'])) ?>
                            </span>
                        </td>
                        <td style="font-size:12px;color:var(--color-text-muted)"><?= fdate($n['created_at']) ?></td>
                        <td>
                            <div style="display:flex;gap:4px">
                                <?php if (!$n['is_read']): ?>
                                <form method="POST" style="display:inline">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                    <button type="submit" class="btn btn-ghost btn-xs" title="Mark read" style="font-size:11px">✓ Read</button>
                                </form>
                                <?php endif ?>
                                <form method="POST" style="display:inline">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                    <button type="submit" class="btn btn-ghost btn-xs" title="Delete" style="font-size:11px;color:var(--color-danger)">✕</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif ?>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Notifications', $content, ['section' => 'notifications']);
