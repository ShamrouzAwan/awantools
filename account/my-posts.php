<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireLogin();

// Only admins and blog writers
if (!$auth->isAdmin() && !$auth->hasRole('blog_writer')) {
    redirect('/account/dashboard');
}

// Load user's posts
$myPosts = [];
try {
    $myPosts = $db->fetchAll(
        "SELECT id, title, slug, status, review_status, published_at, created_at, updated_at
         FROM blog_posts WHERE author_id = ? ORDER BY created_at DESC",
        [$auth->id()]
    ) ?: [];
} catch (Exception $e) {}

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">My Posts</div>
            <div class="page-subtitle">Blog posts you have written</div>
        </div>
    </div>
    <div class="page-header-actions">
        <a href="/account/write" class="btn btn-primary btn-sm">Write New Post</a>
        <a href="/account/dashboard" class="btn btn-ghost btn-sm">Dashboard</a>
    </div>
</div>

<div class="page-body">

    <?php if (empty($myPosts)): ?>
    <div class="card">
        <div class="card-body" style="text-align:center;padding:48px 24px">
            <div style="color:var(--color-text-muted);margin-bottom:16px">
                <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            </div>
            <div style="font-weight:600;margin-bottom:8px">No posts yet</div>
            <div class="text-muted text-sm" style="margin-bottom:20px">Write your first blog post and submit it for review.</div>
            <a href="/account/write" class="btn btn-primary">Write a Post</a>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Review</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($myPosts as $post): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:14px"><?= e($post['title']) ?></div>
                        <div class="text-muted text-sm">/blog/<?= e($post['slug']) ?></div>
                    </td>
                    <td>
                        <span class="badge badge-<?= $post['status'] === 'published' ? 'success' : 'neutral' ?>">
                            <?= e($post['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php $rs = $post['review_status'] ?? ''; ?>
                        <?php if ($rs === 'pending'): ?>
                        <span class="badge badge-warning">Pending Review</span>
                        <?php elseif ($rs === 'approved'): ?>
                        <span class="badge badge-success">Approved</span>
                        <?php elseif ($rs === 'rejected'): ?>
                        <span class="badge badge-danger">Rejected</span>
                        <?php else: ?>
                        <span class="text-muted text-sm">—</span>
                        <?php endif ?>
                    </td>
                    <td class="text-sm text-muted"><?= fdate($post['created_at']) ?></td>
                    <td>
                        <div style="display:flex;gap:4px">
                            <?php if ($post['status'] === 'published'): ?>
                            <a href="/blog/<?= e($post['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm">View</a>
                            <?php endif ?>
                            <?php if ($rs === 'rejected' || $post['status'] === 'draft'): ?>
                            <a href="/account/write?edit=<?= $post['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                            <?php endif ?>
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
require THEMES_PATH . '/default/templates/layout.php';
render_page('My Posts', $content);
