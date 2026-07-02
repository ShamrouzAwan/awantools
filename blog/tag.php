<?php
// Blog tag archive page
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';

$tagSlug = $GLOBALS['_route_blog_tag'] ?? Security::sanitize(ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/blog/tag/'));
$tag = null;
try {
    $tag = $db->fetch("SELECT * FROM blog_tags WHERE slug = ? LIMIT 1", [$tagSlug]);
} catch (Exception $e) {}

if (!$tag) {
    http_response_code(404);
    ob_start();
    ?>
    <div class="front-container" style="padding:80px 24px;text-align:center">
        <div class="error-code" style="font-size:48px">404</div>
        <h1 style="font-size:20px;margin-bottom:8px">Tag Not Found</h1>
        <p style="color:var(--color-text-secondary);margin-bottom:24px">This tag doesn't exist or has no posts.</p>
        <a href="/blog" class="btn btn-primary">Back to Blog</a>
    </div>
    <?php
    $content = ob_get_clean();
    require THEMES_PATH . '/default/templates/layout.php';
    render_page('Tag Not Found', $content);
    exit;
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset  = ($page - 1) * $perPage;

try {
    $total = (int)($db->fetch(
        "SELECT COUNT(*) AS n FROM blog_posts bp
         JOIN blog_post_tags bpt ON bpt.post_id = bp.id
         WHERE bpt.tag_id = ? AND bp.status = 'published'",
        [$tag['id']]
    )['n'] ?? 0);
    $posts = $db->fetchAll(
        "SELECT bp.*, u.name AS author_name, u.username AS author_username
         FROM blog_posts bp
         JOIN blog_post_tags bpt ON bpt.post_id = bp.id
         LEFT JOIN users u ON u.id = bp.author_id
         WHERE bpt.tag_id = ? AND bp.status = 'published'
         ORDER BY bp.published_at DESC
         LIMIT ? OFFSET ?",
        [$tag['id'], $perPage, $offset]
    ) ?: [];
} catch (Exception $e) {
    $total = 0; $posts = [];
}
$totalPages = max(1, (int)ceil($total / $perPage));

// Track analytics
if ($settings->get('analytics_enabled', '1') === '1' && !isBot()) {
    try { $db->insert('analytics_events', ['event' => 'page_view', 'path' => '/blog/tag/' . $tagSlug, 'user_id' => $auth->id(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? null, 'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255), 'created_at' => date('Y-m-d H:i:s')]); } catch (Exception $e) {}
}

ob_start();
?>
<div class="page-hero">
    <div class="page-hero-inner">
        <div style="margin-bottom:8px">
            <a href="/blog" style="font-size:13px;color:var(--color-text-muted);text-decoration:none">Blog</a>
            <span style="margin:0 6px;color:var(--color-border)">/</span>
            <span style="font-size:13px;color:var(--color-text-muted)">Tag</span>
        </div>
        <h1>Tag: <?= e($tag['name']) ?></h1>
        <p><?= number_format($total) ?> article<?= $total !== 1 ? 's' : '' ?> with this tag.</p>
    </div>
</div>

<div class="front-section">
    <div class="front-container">
        <?php if (empty($posts)): ?>
        <div class="empty-state">
            <h3>No Posts Yet</h3>
            <p>No published posts with this tag yet.</p>
            <a href="/blog" class="btn btn-secondary btn-sm" style="margin-top:12px">View All Posts</a>
        </div>
        <?php else: ?>
        <div class="blog-grid">
            <?php foreach ($posts as $post): ?>
            <a href="/blog/<?= e($post['slug']) ?>" class="blog-card">
                <?php if ($post['cover_image']): ?>
                <img src="<?= e($post['cover_image']) ?>" alt="<?= e($post['title']) ?>" class="blog-card-cover">
                <?php else: ?>
                <div class="blog-card-cover-placeholder">
                    <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" opacity="0.4"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                </div>
                <?php endif ?>
                <div class="blog-card-body">
                    <div class="blog-card-title"><?= e($post['title']) ?></div>
                    <?php if ($post['excerpt']): ?>
                    <div class="blog-card-excerpt"><?= e(substr($post['excerpt'], 0, 120)) ?><?= strlen($post['excerpt']) > 120 ? '…' : '' ?></div>
                    <?php endif ?>
                    <div class="blog-card-meta">
                        <?php if ($post['author_name'] ?? $post['author_username']): ?>
                        <span><?= e($post['author_name'] ?? $post['author_username']) ?></span>
                        <span>&middot;</span>
                        <?php endif ?>
                        <span><?= fdate($post['published_at'] ?? $post['created_at']) ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination" style="margin-top:32px">
            <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>">&laquo; Prev</a><?php endif ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
                <?php else: ?><a href="?page=<?= $i ?>"><?= $i ?></a><?php endif ?>
            <?php endfor ?>
            <?php if ($page < $totalPages): ?><a href="?page=<?= $page + 1 ?>">Next &raquo;</a><?php endif ?>
        </div>
        <?php endif ?>
        <?php endif ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Tag: ' . $tag['name'], $content, [
    'description' => 'Articles tagged with "' . $tag['name'] . '" on ' . $settings->siteName() . '.',
    'canonical'   => '/blog/tag/' . $tagSlug,
]);
