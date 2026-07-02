<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

$q      = trim($_GET['q'] ?? '');
$q      = Security::sanitize($q);
$valid  = strlen($q) >= 2;
$like   = '%' . $q . '%';

$pluginResults = [];
$blogResults   = [];
$pageResults   = [];

if ($valid) {
    // Track search
    if ($settings->get('analytics_enabled', '1') === '1' && !isBot()) {
        try {
            $db->insert('analytics_events', [
                'event'      => 'search',
                'path'       => '/search',
                'user_id'    => $auth->id(),
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {}
    }

    // Search plugins by name + description
    $pluginResults = $db->fetchAll(
        "SELECT slug, name, description, version, manifest FROM plugins
         WHERE status = 'active' AND (name LIKE ? OR description LIKE ?)
         ORDER BY name ASC LIMIT 20",
        [$like, $like]
    ) ?: [];

    // Also search by keywords in manifest JSON for plugins not already matched
    $matchedSlugs = array_column($pluginResults, 'slug');
    $allPlugins   = $db->fetchAll(
        "SELECT slug, name, description, version, manifest FROM plugins WHERE status = 'active'"
    ) ?: [];
    foreach ($allPlugins as $p) {
        if (in_array($p['slug'], $matchedSlugs, true)) continue;
        $m = json_decode($p['manifest'] ?? '{}', true) ?? [];
        $kw = implode(' ', array_map('strtolower', array_merge($m['keywords'] ?? [], $m['tags'] ?? [])));
        if (stripos($kw, $q) !== false || stripos($p['name'], $q) !== false) {
            $pluginResults[] = $p;
            $matchedSlugs[]  = $p['slug'];
        }
    }

    // Search blog posts
    try {
        $blogResults = $db->fetchAll(
            "SELECT id, title, slug, excerpt, cover_image, published_at, created_at
             FROM blog_posts
             WHERE status = 'published' AND (title LIKE ? OR excerpt LIKE ?)
             ORDER BY published_at DESC LIMIT 10",
            [$like, $like]
        ) ?: [];
    } catch (Throwable $e) {}

    // Search CMS pages
    try {
        $pageResults = $db->fetchAll(
            "SELECT title, slug FROM pages
             WHERE status = 'published' AND (title LIKE ? OR content LIKE ?)
             ORDER BY sort_order ASC LIMIT 6",
            [$like, $like]
        ) ?: [];
    } catch (Throwable $e) {}
}

$totalResults = count($pluginResults) + count($blogResults) + count($pageResults);

ob_start();
?>
<div class="page-hero" style="padding:40px 0">
    <div class="page-hero-inner">
        <div class="section-eyebrow">Search</div>
        <h1 style="font-size:28px;margin-bottom:20px">
            <?php if ($q): ?>
                Results for "<?= e($q) ?>"
            <?php else: ?>
                Search Awan Tools
            <?php endif ?>
        </h1>
        <form action="/search" method="GET" style="display:flex;gap:8px;max-width:560px;margin:0 auto">
            <input type="text" name="q" value="<?= e($q) ?>"
                   placeholder="Search tools, articles, pages…"
                   class="form-input"
                   style="flex:1;height:44px;font-size:15px"
                   autofocus
                   autocomplete="off">
            <button type="submit" class="btn btn-primary" style="height:44px;padding:0 20px;white-space:nowrap">Search</button>
        </form>
        <?php if ($valid): ?>
        <div style="margin-top:10px;font-size:13px;color:var(--color-text-muted)">
            <?= $totalResults ?> result<?= $totalResults !== 1 ? 's' : '' ?> found
        </div>
        <?php endif ?>
    </div>
</div>

<div class="front-container" style="padding-top:40px;padding-bottom:64px">

<?php if (!$valid && $q !== ''): ?>
    <div class="alert alert-warning">Search query must be at least 2 characters.</div>

<?php elseif ($valid && $totalResults === 0): ?>
    <div class="empty-state" style="padding:64px 24px">
        <div style="font-size:48px;margin-bottom:16px;opacity:.4">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </div>
        <h3>No results for "<?= e($q) ?>"</h3>
        <p style="color:var(--color-text-muted)">Try a different search term, or browse our tools and blog.</p>
        <div style="display:flex;gap:12px;justify-content:center;margin-top:20px;flex-wrap:wrap">
            <a href="/plugins" class="btn btn-secondary">Browse Tools</a>
            <a href="/blog" class="btn btn-ghost">Read Blog</a>
            <a href="/request-tool" class="btn btn-ghost">Request a Tool</a>
        </div>
    </div>

<?php elseif ($valid): ?>

    <!-- Tools / Plugins -->
    <?php if (!empty($pluginResults)): ?>
    <div style="margin-bottom:48px">
        <div class="section-eyebrow" style="margin-bottom:12px">Tools</div>
        <h2 style="font-size:20px;font-weight:700;margin-bottom:20px"><?= count($pluginResults) ?> Tool<?= count($pluginResults) !== 1 ? 's' : '' ?> Found</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
            <?php foreach ($pluginResults as $plugin):
                $m = json_decode($plugin['manifest'] ?? '{}', true) ?? [];
                $icon = $m['icon'] ?? '';
                $categories = $m['categories'] ?? ($m['category'] ? [$m['category']] : []);
            ?>
            <a href="/plugins/<?= e($plugin['slug']) ?>/"
               class="card"
               style="text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:10px;transition:border-color .15s,box-shadow .15s"
               onmouseover="this.style.borderColor='var(--color-primary)'"
               onmouseout="this.style.borderColor=''">
                <div class="card-body" style="display:flex;align-items:flex-start;gap:12px">
                    <div style="width:44px;height:44px;background:var(--color-primary-light);border-radius:var(--radius-small);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">
                        <?= $icon ?: '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/></svg>' ?>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:700;font-size:14px;margin-bottom:4px"><?= e($plugin['name']) ?></div>
                        <?php foreach (array_slice($categories, 0, 2) as $cat): ?>
                        <span class="badge badge-secondary" style="font-size:10px;margin-right:4px;margin-bottom:4px"><?= e($cat) ?></span>
                        <?php endforeach ?>
                        <div class="text-muted text-sm" style="margin-top:4px"><?= e(substr($plugin['description'] ?? '', 0, 90)) ?><?= strlen($plugin['description'] ?? '') > 90 ? '…' : '' ?></div>
                    </div>
                </div>
                <div style="padding:0 16px 12px;font-size:12px;font-weight:600;color:var(--color-primary)">Open Tool &rarr;</div>
            </a>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>

    <!-- Blog Posts -->
    <?php if (!empty($blogResults)): ?>
    <div style="margin-bottom:48px">
        <div class="section-eyebrow" style="margin-bottom:12px">Articles</div>
        <h2 style="font-size:20px;font-weight:700;margin-bottom:20px"><?= count($blogResults) ?> Article<?= count($blogResults) !== 1 ? 's' : '' ?> Found</h2>
        <div style="display:flex;flex-direction:column;gap:12px">
            <?php foreach ($blogResults as $bp):
                $date = $bp['published_at'] ?: $bp['created_at'];
                $excerpt = $bp['excerpt'] ?: '';
            ?>
            <a href="/blog/<?= e($bp['slug']) ?>"
               style="display:flex;align-items:flex-start;gap:14px;padding:16px;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-large);text-decoration:none;color:inherit;transition:border-color .15s"
               onmouseover="this.style.borderColor='var(--color-primary)'"
               onmouseout="this.style.borderColor=''">
                <?php if ($bp['cover_image']): ?>
                <div style="width:80px;height:60px;border-radius:var(--radius-medium);overflow:hidden;flex-shrink:0">
                    <img src="<?= e($bp['cover_image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
                </div>
                <?php endif ?>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:700;font-size:14px;margin-bottom:4px;line-height:1.4"><?= e($bp['title']) ?></div>
                    <?php if ($excerpt): ?>
                    <div class="text-muted text-sm" style="line-height:1.5"><?= e(substr($excerpt, 0, 120)) ?><?= strlen($excerpt) > 120 ? '…' : '' ?></div>
                    <?php endif ?>
                    <?php if ($date): ?>
                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:6px"><?= date('F j, Y', strtotime($date)) ?></div>
                    <?php endif ?>
                </div>
            </a>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>

    <!-- CMS Pages -->
    <?php if (!empty($pageResults)): ?>
    <div>
        <div class="section-eyebrow" style="margin-bottom:12px">Pages</div>
        <h2 style="font-size:20px;font-weight:700;margin-bottom:16px">Pages</h2>
        <div style="display:flex;flex-direction:column;gap:8px">
            <?php foreach ($pageResults as $page): ?>
            <a href="/<?= e($page['slug']) ?>"
               style="display:flex;align-items:center;gap:10px;padding:12px 16px;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-medium);text-decoration:none;color:var(--color-text);transition:border-color .15s"
               onmouseover="this.style.borderColor='var(--color-primary)'"
               onmouseout="this.style.borderColor=''">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <?= e($page['title']) ?>
            </a>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>

<?php else: ?>
    <!-- Empty state — no query yet -->
    <div style="text-align:center;padding:48px 24px;color:var(--color-text-muted)">
        <div style="margin-bottom:24px;opacity:.4">
            <svg width="56" height="56" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </div>
        <h3 style="color:var(--color-text);margin-bottom:8px">What are you looking for?</h3>
        <p>Search across tools, blog articles, and pages.</p>
        <div style="display:flex;gap:12px;justify-content:center;margin-top:24px;flex-wrap:wrap">
            <a href="/plugins" class="btn btn-secondary">Browse All Tools</a>
            <a href="/blog" class="btn btn-ghost">Read Blog</a>
        </div>
    </div>
<?php endif ?>
</div>

<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Search' . ($q ? ' — ' . $q : ''), $content, [
    'description' => 'Search Awan Tools for free online tools, articles, and pages.',
]);
