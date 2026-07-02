<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = Security::sanitize($_POST['action'] ?? '');
    if ($action === 'mark_read') {
        $nid = (int)($_POST['notification_id'] ?? 0);
        if ($nid) {
            $db->update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', [$nid, $auth->id()]);
        }
    } elseif ($action === 'mark_all_read') {
        $db->query("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0", [$auth->id()]);
    } elseif ($action === 'delete') {
        $nid = (int)($_POST['notification_id'] ?? 0);
        if ($nid) {
            $db->delete('notifications', 'id = ? AND user_id = ?', [$nid, $auth->id()]);
        }
    } elseif ($action === 'clear_all') {
        $db->query("DELETE FROM notifications WHERE user_id = ?", [$auth->id()]);
    }
    redirect('/account/notifications');
}

$perPage = 30;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$total    = 0;
$unread   = 0;
$notifs   = [];
$hasUserCol = false;

try {
    $testCol = $db->fetch("SELECT user_id FROM notifications LIMIT 1");
    $hasUserCol = true;
} catch (Throwable $e) {}

if ($hasUserCol) {
    $total  = $db->count('notifications', 'user_id = ?', [$auth->id()]);
    $unread = $db->count('notifications', 'user_id = ? AND is_read = 0', [$auth->id()]);
    $notifs = $db->fetchAll(
        "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
        [$auth->id(), $perPage, $offset]
    ) ?: [];
} else {
    $total  = $db->count('notifications');
    $unread = $db->count('notifications', 'is_read = 0');
    $notifs = $db->fetchAll(
        "SELECT * FROM notifications ORDER BY created_at DESC LIMIT ? OFFSET ?",
        [$perPage, $offset]
    ) ?: [];
}

$totalPages = max(1, (int)ceil($total / $perPage));

$typeIcons = [
    'info'    => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    'success' => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
    'warning' => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    'error'   => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
];
$typeColors = ['info'=>'#6366f1','success'=>'#22c55e','warning'=>'#f59e0b','error'=>'#ef4444'];

ob_start();
?>
<div class="page-hero" style="padding:40px 0 32px">
    <div class="page-hero-inner">
        <div class="section-eyebrow">My Account</div>
        <h1>Notifications</h1>
        <?php if ($unread > 0): ?>
        <p><?= $unread ?> unread notification<?= $unread !== 1 ? 's' : '' ?></p>
        <?php else: ?>
        <p>You're all caught up.</p>
        <?php endif ?>
    </div>
</div>

<div class="front-container" style="padding-top:32px;padding-bottom:60px;max-width:720px">

    <?php if (!empty($notifs)): ?>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:16px">
        <?php if ($unread > 0): ?>
        <form method="POST" style="display:inline">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="mark_all_read">
            <button type="submit" class="btn btn-secondary btn-sm">Mark All Read</button>
        </form>
        <?php endif ?>
        <form method="POST" style="display:inline" onsubmit="return confirm('Clear all notifications?')">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="clear_all">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-danger)">Clear All</button>
        </form>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
    <?php foreach ($notifs as $n):
        $icon  = $typeIcons[$n['type']] ?? $typeIcons['info'];
        $color = $typeColors[$n['type']] ?? '#6366f1';
        $isNew = !$n['is_read'];
    ?>
    <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 16px;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-medium);<?= $isNew ? 'border-left:3px solid ' . $color . ';' : '' ?>">
        <div style="color:<?= $color ?>;flex-shrink:0;margin-top:1px"><?= $icon ?></div>
        <div style="flex:1;min-width:0">
            <div style="font-weight:<?= $isNew ? '600' : '400' ?>;font-size:14px;margin-bottom:2px"><?= e($n['title']) ?></div>
            <?php if ($n['message']): ?>
            <div style="font-size:13px;color:var(--color-text-muted)"><?= e($n['message']) ?></div>
            <?php endif ?>
            <div style="font-size:11px;color:var(--color-text-muted);margin-top:4px"><?= fdate($n['created_at']) ?></div>
        </div>
        <div style="display:flex;gap:4px;flex-shrink:0">
            <?php if ($n['url']): ?>
            <a href="<?= e($n['url']) ?>" class="btn btn-ghost btn-sm" style="font-size:11px">View</a>
            <?php endif ?>
            <?php if ($isNew): ?>
            <form method="POST" style="display:inline">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="mark_read">
                <input type="hidden" name="notification_id" value="<?= $n['id'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm" style="font-size:11px" title="Mark read">Read</button>
            </form>
            <?php endif ?>
            <form method="POST" style="display:inline">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="notification_id" value="<?= $n['id'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm" style="font-size:11px;color:var(--color-danger)" title="Delete">&times;</button>
            </form>
        </div>
    </div>
    <?php endforeach ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex;gap:8px;justify-content:center;margin-top:24px">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="?page=<?= $p ?>" class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>"><?= $p ?></a>
        <?php endfor ?>
    </div>
    <?php endif ?>

    <?php else: ?>
    <div style="text-align:center;padding:60px 0;color:var(--color-text-muted)">
        <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 16px;display:block;opacity:.35"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <p style="font-size:15px;margin:0;font-weight:500">No notifications yet</p>
    </div>
    <?php endif ?>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Notifications', $content, ['description' => 'Your account notifications.']);
