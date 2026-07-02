<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = Security::sanitize($_POST['action'] ?? '');
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'approve' && $id) {
        $db->update('blog_comments', ['status' => 'approved'], 'id = ?', [$id]);
        // Notify the post author
        try {
            $approvedComment = $db->fetch(
                "SELECT bc.author_name, bc.author_email, bp.title AS post_title, bp.slug AS post_slug, bp.author_id
                 FROM blog_comments bc
                 LEFT JOIN blog_posts bp ON bp.id = bc.post_id
                 WHERE bc.id = ?",
                [$id]
            );
            if ($approvedComment && !empty($approvedComment['author_id'])) {
                $db->insert('notifications', [
                    'user_id'    => (int)$approvedComment['author_id'],
                    'type'       => 'comment_approved',
                    'title'      => 'New comment on your post',
                    'message'    => 'A comment by ' . $approvedComment['author_name'] . ' was approved on "' . $approvedComment['post_title'] . '".',
                    'link'       => '/blog/' . ($approvedComment['post_slug'] ?? ''),
                    'is_read'    => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (Throwable $_e) {}
        Session::flash('success', 'Comment approved.');
    } elseif ($action === 'pending' && $id) {
        $db->update('blog_comments', ['status' => 'pending'], 'id = ?', [$id]);
        Session::flash('success', 'Comment set to pending.');
    } elseif ($action === 'delete' && $id) {
        $db->delete('blog_comments', 'id = ?', [$id]);
        Session::flash('success', 'Comment deleted.');
    } elseif ($action === 'delete_all_spam') {
        $db->query("DELETE FROM blog_comments WHERE status = 'pending'");
        Session::flash('success', 'All pending comments deleted.');
    }
    redirect('/admin/comments');
}

$tab    = Security::sanitize($_GET['tab'] ?? 'pending');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$statusFilter = in_array($tab, ['pending', 'approved', 'all']) ? ($tab === 'all' ? '' : $tab) : 'pending';

$where  = $statusFilter ? "WHERE bc.status = '$statusFilter'" : '';
$total  = (int)($db->fetch("SELECT COUNT(*) AS n FROM blog_comments bc $where")['n'] ?? 0);
$comments = $db->fetchAll(
    "SELECT bc.*, bp.title AS post_title, bp.slug AS post_slug
     FROM blog_comments bc
     LEFT JOIN blog_posts bp ON bp.id = bc.post_id
     $where
     ORDER BY bc.created_at DESC
     LIMIT ? OFFSET ?",
    [$perPage, $offset]
) ?: [];
$pages = max(1, (int)ceil($total / $perPage));

$counts = [
    'pending'  => (int)($db->fetch("SELECT COUNT(*) AS n FROM blog_comments WHERE status='pending'")['n'] ?? 0),
    'approved' => (int)($db->fetch("SELECT COUNT(*) AS n FROM blog_comments WHERE status='approved'")['n'] ?? 0),
    'all'      => (int)($db->fetch("SELECT COUNT(*) AS n FROM blog_comments")['n'] ?? 0),
];

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Comments</div>
            <div class="page-subtitle"><?= number_format($counts['all']) ?> total &mdash; <?= number_format($counts['pending']) ?> pending review</div>
        </div>
    </div>
    <?php if ($counts['pending'] > 0): ?>
    <div class="topbar-actions">
        <form method="POST" onsubmit="return confirm('Delete all <?= $counts['pending'] ?> pending comments? This cannot be undone.')">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="delete_all_spam">
            <button class="btn btn-danger btn-sm">Delete All Pending (<?= $counts['pending'] ?>)</button>
        </form>
    </div>
    <?php endif ?>
</div>

<div class="page-body">
    <!-- Tabs -->
    <div style="display:flex;gap:6px;margin-bottom:16px;border-bottom:1px solid var(--color-border);padding-bottom:0">
        <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'all' => 'All'] as $key => $label): ?>
        <a href="?tab=<?= $key ?>" class="btn btn-sm <?= $tab === $key ? 'btn-primary' : 'btn-ghost' ?>" style="border-bottom-left-radius:0;border-bottom-right-radius:0;margin-bottom:-1px">
            <?= $label ?>
            <span style="background:rgba(0,0,0,0.1);padding:1px 5px;border-radius:8px;font-size:10px;margin-left:4px"><?= $counts[$key] ?></span>
        </a>
        <?php endforeach ?>
    </div>

    <?php if (empty($comments)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" opacity="0.3"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <h3>No Comments</h3>
        <p>No <?= $tab !== 'all' ? $tab . ' ' : '' ?>comments found.</p>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Author</th>
                        <th>Comment</th>
                        <th>Post</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $c): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:13px"><?= e($c['author_name']) ?></div>
                            <?php if ($c['author_email']): ?>
                            <div style="font-size:11px;color:var(--color-text-muted)"><?= e($c['author_email']) ?></div>
                            <?php endif ?>
                        </td>
                        <td style="max-width:300px">
                            <div style="font-size:13px;color:var(--color-text);line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical"><?= e($c['content']) ?></div>
                        </td>
                        <td>
                            <?php if ($c['post_title']): ?>
                            <a href="/blog/<?= e($c['post_slug']) ?>" target="_blank" style="font-size:12px;color:var(--color-primary);text-decoration:none"><?= e(substr($c['post_title'], 0, 40)) ?><?= strlen($c['post_title']) > 40 ? '…' : '' ?></a>
                            <?php else: ?>
                            <span style="font-size:12px;color:var(--color-text-muted)">—</span>
                            <?php endif ?>
                        </td>
                        <td style="white-space:nowrap;font-size:12px;color:var(--color-text-muted)"><?= fdate($c['created_at'], 'M j, Y') ?></td>
                        <td>
                            <span class="badge badge-<?= $c['status'] === 'approved' ? 'success' : ($c['status'] === 'pending' ? 'warning' : 'neutral') ?>">
                                <?= ucfirst($c['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <?php if ($c['status'] !== 'approved'): ?>
                                <form method="POST" style="display:inline">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button class="btn btn-success btn-xs" style="font-size:11px;padding:3px 8px">Approve</button>
                                </form>
                                <?php else: ?>
                                <form method="POST" style="display:inline">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="pending">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button class="btn btn-secondary btn-xs" style="font-size:11px;padding:3px 8px">Unpublish</button>
                                </form>
                                <?php endif ?>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this comment?')">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button class="btn btn-danger btn-xs" style="font-size:11px;padding:3px 8px">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($pages > 1): ?>
    <div class="pagination" style="margin-top:16px">
        <?php if ($page > 1): ?><a href="?tab=<?= $tab ?>&page=<?= $page - 1 ?>">&laquo; Prev</a><?php endif ?>
        <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
            <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
            <?php else: ?><a href="?tab=<?= $tab ?>&page=<?= $i ?>"><?= $i ?></a><?php endif ?>
        <?php endfor ?>
        <?php if ($page < $pages): ?><a href="?tab=<?= $tab ?>&page=<?= $page + 1 ?>">Next &raquo;</a><?php endif ?>
    </div>
    <?php endif ?>
    <?php endif ?>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Comments', $content, ['section' => 'comments']);
