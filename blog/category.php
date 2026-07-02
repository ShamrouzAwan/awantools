<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';

$catSlug  = $GLOBALS['_route_blog_category'] ?? Security::sanitize(ltrim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/blog/category/'));
$category = null;
try {
    $category = $db->fetch("SELECT * FROM blog_categories WHERE slug = ? LIMIT 1", [$catSlug]);
} catch (Exception $e) {}

if (!$category) {
    http_response_code(404);
    ob_start();
    ?>
    <div class="front-container" style="padding:80px 24px;text-align:center">
        <div style="font-size:64px;font-weight:800;color:var(--color-border)">404</div>
        <h1 style="font-size:22px;margin-bottom:8px">Category Not Found</h1>
        <p style="color:var(--color-text-secondary);margin-bottom:24px">This category does not exist or has been removed.</p>
        <a href="/blog" class="btn btn-primary">Back to Blog</a>
    </div>
    <?php
    $content = ob_get_clean();
    require THEMES_PATH . '/default/templates/layout.php';
    render_page('Category Not Found', $content);
    exit;
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset  = ($page - 1) * $perPage;

try {
    $total = $db->count(
        'blog_posts bp',
        "EXISTS (SELECT 1 FROM blog_post_categories bpc WHERE bpc.post_id = bp.id AND bpc.category_id = ?) AND bp.status = 'published'",
        [$category['id']]
    );
    $posts = $db->fetchAll(
        "SELECT bp.*, u.name AS author_name, u.username AS author_username
         FROM blog_posts bp
         LEFT JOIN users u ON u.id = bp.author_id
         WHERE EXISTS (SELECT 1 FROM blog_post_categories bpc WHERE bpc.post_id = bp.id AND bpc.category_id = ?)
           AND bp.status = 'published'
         ORDER BY bp.published_at DESC, bp.created_at DESC
         LIMIT ? OFFSET ?",
        [$category['id'], $perPage, $offset]
    ) ?: [];
} catch (Exception $e) {
    $total = 0; $posts = [];
}
$totalPages = max(1, (int)ceil($total / $perPage));

if ($settings->get('analytics_enabled', '1') === '1' && !isBot()) {
    try {
        $db->insert('analytics_events', [
            'event'      => 'page_view',
            'path'       => '/blog/category/' . $catSlug,
            'user_id'    => $auth->id(),
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (Exception $e) {}
}

ob_start();
?>
<div class="page-hero">
    <div class="page-hero-inner">
        <div style="font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;opacity:.65;margin-bottom:8px">
            <a href="/blog" style="color:inherit;text-decoration:none">Blog</a>
            <span style="margin:0 6px">&rsaquo;</span>
            Category
        </div>
        <h1><?= e($category['name']) ?></h1>
        <?php if (!empty($category['description'])): ?>
        <p><?= e($category['description']) ?></p>
        <?php else: ?>
        <p><?= number_format($total) ?> post<?= $total !== 1 ? 's' : '' ?> in this category</p>
        <?php endif ?>
    </div>
</div>

<div class="front-section">
    <div class="front-container">

        <?php if (empty($posts)): ?>
        <div style="text-align:center;padding:64px 24px">
            <div style="margin-bottom:16px;opacity:.3;display:inline-block">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
            </div>
            <h2 style="font-size:18px;font-weight:600;margin-bottom:8px">No posts yet</h2>
            <p style="color:var(--color-text-secondary);margin-bottom:24px">No published posts in this category yet. Check back soon.</p>
            <a href="/blog" class="btn btn-primary">Browse All Posts</a>
        </div>
        <?php else: ?>

        <div class="blog-grid">
            <?php foreach ($posts as $post): ?>
            <a href="/blog/<?= e($post['slug']) ?>" class="blog-card" style="text-decoration:none;color:inherit">
                <?php if (!empty($post['cover_image'])): ?>
                <img src="<?= e($post['cover_image']) ?>" alt="<?= e($post['title']) ?>" class="blog-card-cover">
                <?php endif ?>
                <div class="blog-card-body">
                    <div class="blog-card-meta">
                        <?php if ($post['author_name']): ?>
                        <?= e($post['author_name']) ?> &middot;
                        <?php endif ?>
                        <?= $post['published_at'] ? fdate($post['published_at']) : fdate($post['created_at']) ?>
                    </div>
                    <h2 class="blog-card-title"><?= e($post['title']) ?></h2>
                    <?php if ($post['excerpt']): ?>
                    <p class="blog-card-excerpt"><?= e($post['excerpt']) ?></p>
                    <?php endif ?>
                    <span class="btn btn-ghost btn-sm" style="margin-top:auto;padding-left:0">Read more &rarr;</span>
                </div>
            </a>
            <?php endforeach ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div style="display:flex;justify-content:center;gap:6px;margin-top:40px;flex-wrap:wrap">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="btn btn-ghost btn-sm">&larr; Previous</a>
            <?php endif ?>
            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
            <a href="?page=<?= $p ?>" class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-ghost' ?>"><?= $p ?></a>
            <?php endfor ?>
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="btn btn-ghost btn-sm">Next &rarr;</a>
            <?php endif ?>
        </div>
        <?php endif ?>

        <?php endif ?>

        <div style="margin-top:40px;padding-top:24px;border-top:1px solid var(--color-border);text-align:center">
            <a href="/blog" style="color:var(--color-text-muted);font-size:13px;text-decoration:none">&larr; Back to all posts</a>
        </div>

    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Category: ' . $category['name'], $content, [
    'description' => $category['description'] ?: 'Browse ' . $category['name'] . ' posts on ' . $settings->siteName(),
]);
