<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset  = ($page - 1) * $perPage;
$catSlug = $GLOBALS['_route_blog_category'] ?? Security::sanitize($_GET['category'] ?? '');
$search  = Security::sanitize($_GET['q'] ?? '');

// Build query
$where   = ["bp.status = 'published'"];
$params  = [];

if ($catSlug) {
    $where[]  = "EXISTS (SELECT 1 FROM blog_post_categories bpc JOIN blog_categories bc ON bc.id = bpc.category_id WHERE bpc.post_id = bp.id AND bc.slug = ?)";
    $params[] = $catSlug;
}
if ($search) {
    $where[]  = "(bp.title LIKE ? OR bp.excerpt LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

try {
    $total = $db->count('blog_posts bp', implode(' AND ', $where), $params);
    $posts = $db->fetchAll(
        "SELECT bp.*, u.name AS author_name, u.username AS author_username
         FROM blog_posts bp
         LEFT JOIN users u ON u.id = bp.author_id
         {$whereClause}
         ORDER BY bp.published_at DESC, bp.created_at DESC
         LIMIT ? OFFSET ?",
        array_merge($params, [$perPage, $offset])
    );
    $categories = $db->fetchAll("SELECT bc.*, COUNT(bpc.post_id) AS post_count FROM blog_categories bc LEFT JOIN blog_post_categories bpc ON bpc.category_id = bc.id GROUP BY bc.id ORDER BY bc.name ASC");
} catch (Exception $e) {
    $total = 0; $posts = []; $categories = [];
}

$totalPages  = max(1, (int)ceil($total / $perPage));
$currentCat  = null;
if ($catSlug && !empty($categories)) {
    foreach ($categories as $c) {
        if ($c['slug'] === $catSlug) { $currentCat = $c; break; }
    }
}

// Track analytics
if ($settings->get('analytics_enabled', '1') === '1' && !isBot()) {
    try { $db->insert('analytics_events', ['event' => 'page_view', 'path' => '/blog', 'user_id' => $auth->id(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? null, 'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255), 'created_at' => date('Y-m-d H:i:s')]); } catch (Exception $e) {}
}

ob_start();
?>
<div class="page-hero">
    <div class="page-hero-inner">
        <h1><?= $currentCat ? 'Category: ' . e($currentCat['name']) : ($search ? 'Search: "' . e($search) . '"' : 'Blog') ?></h1>
        <p><?= $currentCat ? e($currentCat['description'] ?? 'Articles in this category.') : 'Tutorials, updates, and insights about AWAN Platform.' ?></p>
    </div>
</div>

<div class="front-section">
    <div class="front-container">
        <!-- Search bar -->
        <div style="max-width:480px;margin:0 0 32px">
            <form method="GET" action="/blog">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Search articles…" value="<?= e($search) ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>

        <div class="blog-sidebar-layout">
            <!-- Posts grid -->
            <div>
                <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"></div>
                    <h3>No Articles Found</h3>
                    <p><?= $search ? 'Try a different search term.' : ($catSlug ? 'No posts in this category yet.' : 'The blog is empty. Check back soon!') ?></p>
                    <?php if ($search || $catSlug): ?>
                    <a href="/blog" class="btn btn-secondary btn-sm" style="margin-top:12px">View All Posts</a>
                    <?php endif ?>
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
                                <?php if ($post['view_count'] > 0): ?>
                                <span>&middot;</span>
                                <span><?= number_format($post['view_count']) ?> views</span>
                                <?php endif ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination" style="margin-top:32px">
                    <?php $baseQ = ($search ? '&q=' . urlencode($search) : '') . ($catSlug ? '&category=' . urlencode($catSlug) : ''); ?>
                    <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?><?= $baseQ ?>">&laquo; Prev</a><?php endif ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
                        <?php else: ?><a href="?page=<?= $i ?><?= $baseQ ?>"><?= $i ?></a><?php endif ?>
                    <?php endfor ?>
                    <?php if ($page < $totalPages): ?><a href="?page=<?= $page + 1 ?><?= $baseQ ?>">Next &raquo;</a><?php endif ?>
                </div>
                <?php endif ?>
                <?php endif ?>
            </div>

            <!-- Sidebar -->
            <aside>
                <?php if (!empty($categories)): ?>
                <div class="card" style="margin-bottom:20px">
                    <div class="card-header"><div class="card-title">Categories</div></div>
                    <div class="card-body" style="padding:8px 0">
                        <a href="/blog" style="display:flex;align-items:center;justify-content:space-between;padding:8px 20px;font-size:13px;color:var(--color-text-secondary);text-decoration:none;transition:background .15s<?= !$catSlug ? ';font-weight:600;color:var(--color-primary)' : '' ?>">
                            All Posts <span class="badge badge-neutral"><?= $total ?></span>
                        </a>
                        <?php foreach ($categories as $cat): ?>
                        <a href="/blog/category/<?= e($cat['slug']) ?>"
                           style="display:flex;align-items:center;justify-content:space-between;padding:8px 20px;font-size:13px;color:var(--color-text-secondary);text-decoration:none;transition:background .15s;border-top:1px solid var(--color-border)<?= $catSlug === $cat['slug'] ? ';font-weight:600;color:var(--color-primary)' : '' ?>">
                            <?= e($cat['name']) ?>
                            <span class="badge badge-neutral"><?= $cat['post_count'] ?></span>
                        </a>
                        <?php endforeach ?>
                    </div>
                </div>
                <?php endif ?>

                <div class="card">
                    <div class="card-header"><div class="card-title">Newsletter</div></div>
                    <div class="card-body">
                        <p style="font-size:13px;color:var(--color-text-secondary);margin-bottom:12px">Get new articles delivered to your inbox.</p>
                        <form class="newsletter-form" style="flex-direction:column;gap:8px">
                            <input type="email" name="email" placeholder="your@email.com" style="border-radius:var(--radius-small);border:1px solid var(--color-border);padding:8px 12px;font-size:13px;background:var(--color-surface);color:var(--color-text);outline:none;font-family:inherit">
                            <button type="submit" class="btn btn-primary w-full">Subscribe</button>
                        </form>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
$title = $currentCat ? 'Blog — ' . $currentCat['name'] : ($search ? 'Blog Search: ' . $search : 'Blog');
render_page($title, $content, ['description' => 'Articles, tutorials, and updates from the AWAN Platform.']);
