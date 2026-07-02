<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';

$username = $GLOBALS['_route_author_username'] ?? ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/blog/author/');
$username = Security::sanitize($username);

$author = null;
try {
    $author = $db->fetch(
        "SELECT id, name, username, bio, avatar, created_at FROM users WHERE username = ? AND status = 'active' LIMIT 1",
        [$username]
    );
} catch (Exception $e) {}

if (!$author) {
    http_response_code(404);
    ob_start();
    ?>
    <div class="front-container" style="padding:80px 24px;text-align:center">
        <div style="font-size:48px;font-weight:800;color:var(--color-text-muted)">404</div>
        <h1 style="font-size:20px;margin-bottom:8px">Author Not Found</h1>
        <p style="color:var(--color-text-secondary);margin-bottom:24px">This author profile doesn't exist.</p>
        <a href="/blog" class="btn btn-primary">&larr; Back to Blog</a>
    </div>
    <?php
    $content = ob_get_clean();
    require THEMES_PATH . '/default/templates/layout.php';
    render_page('Author Not Found', $content);
    exit;
}

$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 9;
$offset   = ($page - 1) * $perPage;

$posts = [];
$total = 0;
try {
    $total = (int)($db->fetch(
        "SELECT COUNT(*) AS n FROM blog_posts WHERE author_id = ? AND status = 'published'",
        [$author['id']]
    )['n'] ?? 0);
    $posts = $db->fetchAll(
        "SELECT bp.id, bp.title, bp.slug, bp.excerpt, bp.cover_image, bp.published_at, bp.view_count
         FROM blog_posts bp
         WHERE bp.author_id = ? AND bp.status = 'published'
         ORDER BY bp.published_at DESC
         LIMIT ? OFFSET ?",
        [$author['id'], $perPage, $offset]
    ) ?: [];
} catch (Exception $e) {}

$totalPages  = $total > 0 ? (int)ceil($total / $perPage) : 1;
$authorName  = $author['name'] ?: $author['username'];
$memberSince = fdate($author['created_at'], 'F Y');

ob_start();
?>
<!-- Author Hero -->
<div style="background:var(--color-surface);border-bottom:1px solid var(--color-border);padding:56px 0 40px">
    <div class="front-container" style="max-width:800px">
        <a href="/blog" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--color-text-muted);text-decoration:none;margin-bottom:28px;transition:color .15s" onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--color-text-muted)'">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Blog
        </a>
        <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap">
            <?php if (!empty($author['avatar'])): ?>
            <img src="<?= e($author['avatar']) ?>" alt="<?= e($authorName) ?>"
                 style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--color-border);flex-shrink:0">
            <?php else: ?>
            <div style="width:80px;height:80px;border-radius:50%;background:var(--color-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;font-weight:700;flex-shrink:0">
                <?= strtoupper(substr($authorName, 0, 2)) ?>
            </div>
            <?php endif ?>
            <div>
                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--color-text-muted);margin-bottom:4px">Author</div>
                <h1 style="font-size:28px;font-weight:800;letter-spacing:-0.5px;margin:0 0 4px"><?= e($authorName) ?></h1>
                <div style="color:var(--color-text-muted);font-size:13px">@<?= e($author['username']) ?> &middot; Member since <?= $memberSince ?></div>
                <?php if ($author['bio']): ?>
                <p style="margin-top:12px;font-size:15px;color:var(--color-text-secondary);max-width:520px;line-height:1.6"><?= e($author['bio']) ?></p>
                <?php endif ?>
            </div>
        </div>
        <?php if ($total > 0): ?>
        <div style="margin-top:20px;display:flex;gap:24px">
            <div>
                <div style="font-size:22px;font-weight:800"><?= $total ?></div>
                <div style="font-size:12px;color:var(--color-text-muted)"><?= $total === 1 ? 'Article' : 'Articles' ?></div>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- Posts Grid -->
<div class="front-container" style="padding-top:48px;padding-bottom:64px;max-width:1100px">
    <?php if (empty($posts)): ?>
    <div class="empty-state" style="padding:60px 24px">
        <div class="empty-state-icon">
            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        </div>
        <h3>No articles yet</h3>
        <p>This author hasn't published any articles yet.</p>
        <a href="/blog" class="btn btn-primary" style="margin-top:12px">&larr; Back to Blog</a>
    </div>
    <?php else: ?>
    <div style="margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <h2 style="font-size:20px;font-weight:700">Articles by <?= e($authorName) ?></h2>
        <span style="font-size:13px;color:var(--color-text-muted)"><?= $total ?> article<?= $total !== 1 ? 's' : '' ?></span>
    </div>
    <div class="blog-grid">
        <?php foreach ($posts as $post): ?>
        <a href="/blog/<?= e($post['slug']) ?>" class="blog-card">
            <?php if ($post['cover_image']): ?>
            <img src="<?= e($post['cover_image']) ?>" alt="<?= e($post['title']) ?>" class="blog-card-cover">
            <?php else: ?>
            <div class="blog-card-cover-placeholder">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" opacity="0.4"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            </div>
            <?php endif ?>
            <div class="blog-card-body">
                <div class="blog-card-title"><?= e($post['title']) ?></div>
                <?php if ($post['excerpt']): ?>
                <div class="blog-card-excerpt"><?= e(substr($post['excerpt'], 0, 110)) ?>…</div>
                <?php endif ?>
                <div class="blog-card-meta">
                    <?= fdate($post['published_at'] ?? $post['created_at']) ?>
                    <?php if ($post['view_count'] > 0): ?>
                    &middot; <?= number_format($post['view_count']) ?> views
                    <?php endif ?>
                </div>
            </div>
        </a>
        <?php endforeach ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:8px;margin-top:40px">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="?page=<?= $p ?>" class="btn btn-<?= $p === $page ? 'primary' : 'ghost' ?> btn-sm"><?= $p ?></a>
        <?php endfor ?>
    </div>
    <?php endif ?>
    <?php endif ?>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page($authorName . ' — Author', $content, [
    'description' => $author['bio'] ? substr($author['bio'], 0, 160) : "Articles by {$authorName} on {$settings->siteName()}.",
]);
