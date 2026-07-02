<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$q       = trim(Security::sanitize($_GET['q'] ?? ''));
$results = [];

if ($q !== '') {
    $like = '%' . $q . '%';

    // Users
    $users = $db->fetchAll(
        "SELECT id, username, email, name, status FROM users
         WHERE username LIKE ? OR email LIKE ? OR name LIKE ?
         LIMIT 10",
        [$like, $like, $like]
    ) ?: [];
    foreach ($users as $u) {
        $results[] = [
            'type'  => 'User',
            'title' => $u['name'] ?: $u['username'],
            'sub'   => $u['email'],
            'url'   => '/admin/users?action=edit&id=' . $u['id'],
            'badge' => $u['status'],
        ];
    }

    // Blog posts
    $posts = $db->fetchAll(
        "SELECT id, title, slug, status FROM blog_posts
         WHERE title LIKE ? OR slug LIKE ? OR content LIKE ?
         ORDER BY created_at DESC LIMIT 10",
        [$like, $like, $like]
    ) ?: [];
    foreach ($posts as $p) {
        $results[] = [
            'type'  => 'Blog Post',
            'title' => $p['title'],
            'sub'   => '/blog/' . $p['slug'],
            'url'   => '/admin/blog?action=edit&id=' . $p['id'],
            'badge' => $p['status'],
        ];
    }

    // CMS Pages
    $pages = $db->fetchAll(
        "SELECT id, title, slug, status FROM pages
         WHERE title LIKE ? OR slug LIKE ? OR content LIKE ?
         LIMIT 8",
        [$like, $like, $like]
    ) ?: [];
    foreach ($pages as $p) {
        $results[] = [
            'type'  => 'Page',
            'title' => $p['title'],
            'sub'   => '/' . $p['slug'],
            'url'   => '/admin/pages?action=edit&id=' . $p['id'],
            'badge' => $p['status'],
        ];
    }

    // Plugins
    $plugins = $db->fetchAll(
        "SELECT id, name, slug, status FROM plugins WHERE name LIKE ? OR slug LIKE ? LIMIT 8",
        [$like, $like]
    ) ?: [];
    foreach ($plugins as $p) {
        $results[] = [
            'type'  => 'Plugin',
            'title' => $p['name'],
            'sub'   => $p['slug'],
            'url'   => '/admin/plugins',
            'badge' => $p['status'],
        ];
    }

    // Email templates
    $tpls = $db->fetchAll(
        "SELECT id, name, slug FROM email_templates WHERE name LIKE ? OR slug LIKE ? LIMIT 5",
        [$like, $like]
    ) ?: [];
    foreach ($tpls as $t) {
        $results[] = [
            'type'  => 'Email Template',
            'title' => $t['name'],
            'sub'   => $t['slug'],
            'url'   => '/admin/email-templates',
            'badge' => null,
        ];
    }

    // Contact messages
    $contacts = $db->fetchAll(
        "SELECT id, name, email, subject FROM contact_messages WHERE name LIKE ? OR email LIKE ? OR subject LIKE ? LIMIT 5",
        [$like, $like, $like]
    ) ?: [];
    foreach ($contacts as $c) {
        $results[] = [
            'type'  => 'Contact',
            'title' => $c['subject'] ?: '(no subject)',
            'sub'   => $c['name'] . ' <' . $c['email'] . '>',
            'url'   => '/admin/contacts?action=view&id=' . $c['id'],
            'badge' => null,
        ];
    }
}

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Search</div>
            <div class="page-subtitle">Search across users, posts, pages, plugins, and more</div>
        </div>
    </div>
</div>

<div class="page-body">
    <form method="GET" action="/admin/search" style="margin-bottom:24px">
        <div style="display:flex;gap:8px;max-width:560px">
            <input type="text" name="q" class="form-input" value="<?= e($q) ?>"
                   placeholder="Search users, posts, pages, plugins…" autofocus style="flex:1">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>

    <?php if ($q === ''): ?>
    <div class="card">
        <div class="card-body" style="text-align:center;padding:48px;color:var(--color-text-muted)">
            <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;opacity:.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <p style="margin:0;font-size:14px">Enter a search term to find users, posts, pages, plugins, and more.</p>
        </div>
    </div>

    <?php elseif (empty($results)): ?>
    <div class="card">
        <div class="card-body" style="text-align:center;padding:48px;color:var(--color-text-muted)">
            <p style="margin:0;font-size:14px">No results found for <strong><?= e($q) ?></strong>.</p>
        </div>
    </div>

    <?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:110px">Type</th>
                        <th>Title / Name</th>
                        <th>Detail</th>
                        <th style="width:90px">Status</th>
                        <th style="width:80px"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $r): ?>
                <tr>
                    <td><span class="badge badge-neutral" style="font-size:10px"><?= e($r['type']) ?></span></td>
                    <td style="font-weight:500;font-size:13px"><?= e($r['title']) ?></td>
                    <td style="font-size:12px;color:var(--color-text-muted)"><?= e($r['sub']) ?></td>
                    <td><?php if ($r['badge']): ?><span class="badge badge-<?= in_array($r['badge'],['active','published','sent']) ? 'success' : ($r['badge'] === 'inactive' || $r['badge'] === 'draft' ? 'neutral' : 'warning') ?>" style="font-size:10px"><?= e($r['badge']) ?></span><?php endif ?></td>
                    <td><a href="<?= e($r['url']) ?>" class="btn btn-ghost btn-sm">Open</a></td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="form-hint" style="margin-top:8px"><?= count($results) ?> result<?= count($results) !== 1 ? 's' : '' ?> for <strong><?= e($q) ?></strong></div>
    <?php endif ?>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Search', $content, ['section' => 'search']);
