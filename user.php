<?php
defined('AWAN') or define('AWAN', true);
require_once __DIR__ . '/_bootstrap.php';

// Username injected by router
$username = $GLOBALS['_route_username'] ?? ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/user/');
$username = Security::sanitize($username);

$profileUser = $db->fetch("SELECT * FROM users WHERE username = ? AND status = 'active' LIMIT 1", [$username]);

if (!$profileUser) {
    http_response_code(404);
    echo renderError(404, 'User Not Found', "No user with the username @{$username} exists on this platform.");
    exit;
}

// Stats
$pageCount = $db->count('pages', "author_id = ? AND status = 'published'", [$profileUser['id']]);

// Published pages by this user
$authoredPages = $db->fetchAll(
    "SELECT title, slug, seo_desc, created_at FROM pages WHERE author_id = ? AND status = 'published' ORDER BY created_at DESC LIMIT 10",
    [$profileUser['id']]
);

// Track profile view
if ($settings->get('analytics_enabled', '1') === '1') {
    try {
        $db->insert('analytics_events', [
            'event'      => 'page_view',
            'path'       => '/user/' . $username,
            'user_id'    => $auth->id(),
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (Exception $e) {}
}

$displayName = $profileUser['name'] ?: $profileUser['username'];
$initial     = strtoupper(substr($displayName, 0, 1));

ob_start();
?>
<style>
.profile-hero {
    display: flex;
    align-items: flex-start;
    gap: 24px;
    margin-bottom: 40px;
    padding-bottom: 40px;
    border-bottom: 1px solid var(--color-border);
}
.profile-avatar {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 3px solid var(--color-border);
}
.profile-avatar-placeholder {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    background: var(--color-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 800;
    color: #fff;
    flex-shrink: 0;
}
.profile-meta { flex: 1; min-width: 0; }
.profile-name { font-size: 26px; font-weight: 800; color: var(--color-text); margin-bottom: 4px; letter-spacing: -0.5px; }
.profile-username { font-size: 14px; color: var(--color-text-muted); margin-bottom: 12px; }
.profile-bio { font-size: 14px; color: var(--color-text-secondary); line-height: 1.6; margin-bottom: 12px; }
.profile-stats { display: flex; gap: 20px; }
.profile-stat { text-align: center; }
.profile-stat-value { font-size: 18px; font-weight: 700; color: var(--color-text); }
.profile-stat-label { font-size: 11px; color: var(--color-text-muted); }
.profile-pages { max-width: 680px; }
.profile-page-item {
    padding: 16px 0;
    border-bottom: 1px solid var(--color-border);
}
.profile-page-item:last-child { border-bottom: none; }
.profile-page-title { font-size: 15px; font-weight: 600; color: var(--color-text); text-decoration: none; }
.profile-page-title:hover { color: var(--color-primary); }
.profile-page-desc { font-size: 13px; color: var(--color-text-secondary); margin-top: 4px; }
.profile-page-meta { font-size: 11px; color: var(--color-text-muted); margin-top: 6px; }
</style>

<div class="profile-hero">
    <?php if (!empty($profileUser['avatar'])): ?>
    <img class="profile-avatar" src="<?= e($profileUser['avatar']) ?>" alt="<?= e($displayName) ?>">
    <?php else: ?>
    <div class="profile-avatar-placeholder"><?= e($initial) ?></div>
    <?php endif ?>

    <div class="profile-meta">
        <div class="profile-name"><?= e($displayName) ?></div>
        <div class="profile-username">@<?= e($profileUser['username']) ?></div>
        <?php if (!empty($profileUser['bio'])): ?>
        <div class="profile-bio"><?= nl2br(e($profileUser['bio'])) ?></div>
        <?php endif ?>
        <div class="profile-stats">
            <div class="profile-stat">
                <div class="profile-stat-value"><?= $pageCount ?></div>
                <div class="profile-stat-label">Published</div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-value"><?= fdate($profileUser['created_at'], 'Y') ?></div>
                <div class="profile-stat-label">Joined</div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($authoredPages)): ?>
<div class="profile-pages">
    <h2 style="font-size:16px;font-weight:700;margin-bottom:0;color:var(--color-text-secondary);text-transform:uppercase;letter-spacing:.05em">Published Pages</h2>
    <?php foreach ($authoredPages as $pg): ?>
    <div class="profile-page-item">
        <a href="/<?= e($pg['slug']) ?>" class="profile-page-title"><?= e($pg['title']) ?></a>
        <?php if ($pg['seo_desc']): ?>
        <div class="profile-page-desc"><?= e($pg['seo_desc']) ?></div>
        <?php endif ?>
        <div class="profile-page-meta"><?= fdate($pg['created_at']) ?></div>
    </div>
    <?php endforeach ?>
</div>
<?php endif ?>
<?php
$pageContent = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('@' . $profileUser['username'] . ' — ' . $settings->get('site_name', 'AWAN Platform'), $pageContent, [
    'robots' => 'noindex, follow',
]);
