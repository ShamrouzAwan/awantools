<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireLogin();

$user = $auth->user();

// Favourites
$favourites      = [];
$totalFavourites = 0;
try {
    $totalFavourites = (int)($db->fetch(
        "SELECT COUNT(*) AS n FROM user_favourites WHERE user_id = ?", [$auth->id()]
    )['n'] ?? 0);
    $favourites = $db->fetchAll(
        "SELECT p.id, p.slug, p.name, p.description, p.manifest
         FROM plugins p
         JOIN user_favourites uf ON uf.plugin_id = p.id
         WHERE uf.user_id = ? AND p.status = 'active'
         ORDER BY uf.created_at DESC LIMIT 8",
        [$auth->id()]
    ) ?: [];
} catch (Exception $e) {}

// My Applications — plugins the user has recently visited
$myApps = [];
try {
    $myApps = $db->fetchAll(
        "SELECT DISTINCT p.id, p.slug, p.name, p.description, p.version, p.manifest
         FROM plugins p
         INNER JOIN analytics_events ae ON ae.path = '/plugins/' || p.slug || '/'
         WHERE ae.user_id = ? AND p.status = 'active'
         ORDER BY ae.id DESC LIMIT 8",
        [$auth->id()]
    ) ?: [];
    // Fallback for new users: show dashboard-enabled plugins
    if (empty($myApps)) {
        $myApps = $db->fetchAll(
            "SELECT * FROM plugins WHERE status = 'active' AND manifest LIKE '%\"dashboard_enabled\":true%' ORDER BY name ASC LIMIT 8"
        ) ?: [];
    }
} catch (Exception $e) {}

// Stats
$loginCount   = 0;
try { $loginCount = (int)($db->fetch(
    "SELECT COUNT(*) AS n FROM logs WHERE user_id = ? AND message LIKE '%login%'", [$auth->id()]
)['n'] ?? 0); } catch (Exception $e) {}
$twoFaEnabled = !empty($user['two_fa_enabled']);
$daysMember   = max(1, (int)ceil((time() - strtotime($user['created_at'] ?? 'now')) / 86400));
$totalTools   = 0;
try { $totalTools = (int)($db->fetch(
    "SELECT COALESCE(SUM(offered),0) AS n FROM plugins WHERE status = 'active'"
)['n'] ?? 0); } catch (Exception $e) {}

// Recent posts
$recentPosts = [];
try { $recentPosts = $db->fetchAll(
    "SELECT id, title, slug, published_at FROM blog_posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 3"
) ?: []; } catch (Exception $e) {}

$displayName = $user['name'] ?: $user['username'];

ob_start();
?>
<style>
.dash-layout   { display:grid; grid-template-columns:240px 1fr; gap:24px; align-items:start; }
.stat-card     { background:var(--color-card); border:1px solid var(--color-border); border-radius:var(--radius-medium); padding:20px; display:flex; align-items:center; gap:16px; }
.stat-icon     { width:44px; height:44px; border-radius:var(--radius-small); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.stat-val      { font-size:26px; font-weight:800; line-height:1; letter-spacing:-1px; }
.stat-lbl      { font-size:12px; color:var(--color-text-muted); margin-top:3px; font-weight:500; }
.tool-grid     { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:10px; }
.tool-item     { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:20px 12px; text-align:center; text-decoration:none; background:var(--color-card); border:1px solid var(--color-border); border-radius:var(--radius-medium); transition:border-color .15s, box-shadow .15s; color:var(--color-text); }
.tool-item:hover { border-color:var(--color-primary); box-shadow:0 4px 12px rgba(0,0,0,.06); }
.tool-icon-wrap { width:40px; height:40px; border-radius:var(--radius-small); background:var(--color-primary-light); display:flex; align-items:center; justify-content:center; margin-bottom:10px; }
@media(max-width:768px){ .dash-layout{ grid-template-columns:1fr; } }
</style>

<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Welcome back, <?= e($displayName) ?></div>
            <div class="page-subtitle"><?= fdate($user['last_login_at'] ?? $user['created_at'], 'M j, Y') ?> &middot; <?= e($user['email']) ?></div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="dash-layout">

        <!-- Sidebar -->
        <div>
            <div class="card" style="position:sticky;top:calc(var(--header-height)+16px)">
                <div class="card-body" style="text-align:center;padding-bottom:8px">
                    <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= e($user['avatar']) ?>" alt="Avatar" style="width:68px;height:68px;border-radius:50%;object-fit:cover;margin:0 auto 10px;display:block;border:2px solid var(--color-border)">
                    <?php else: ?>
                    <div style="width:68px;height:68px;border-radius:50%;background:var(--color-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;font-weight:700;margin:0 auto 10px">
                        <?= strtoupper(substr($displayName, 0, 2)) ?>
                    </div>
                    <?php endif ?>
                    <div style="font-weight:700;font-size:15px"><?= e($displayName) ?></div>
                    <div class="text-muted text-sm">@<?= e($user['username']) ?></div>
                    <?php if ($auth->isAdmin()): ?>
                    <div style="margin-top:8px"><span class="badge badge-primary">Admin</span></div>
                    <?php endif ?>
                    <?php if ($twoFaEnabled): ?>
                    <div style="margin-top:6px"><span class="badge badge-success" title="Email 2FA active">2FA Active</span></div>
                    <?php endif ?>
                </div>
                <div class="card-footer" style="padding:8px 12px">
                    <ul style="list-style:none;display:flex;flex-direction:column;gap:2px;margin:0">
                        <li><a href="/account/dashboard" class="btn btn-ghost btn-sm w-full" style="justify-content:flex-start;background:var(--color-background)">Dashboard</a></li>
                        <li><a href="/plugins" class="btn btn-ghost btn-sm w-full" style="justify-content:flex-start">Browse Tools</a></li>
                        <li><a href="/account/profile" class="btn btn-ghost btn-sm w-full" style="justify-content:flex-start">Profile Settings</a></li>
                        <li><a href="/account/profile?tab=security" class="btn btn-ghost btn-sm w-full" style="justify-content:flex-start">Security</a></li>
                        <li><a href="/account/profile?tab=2fa" class="btn btn-ghost btn-sm w-full" style="justify-content:flex-start">Two-Factor Auth</a></li>
                        <?php if ($auth->isAdmin() || $auth->hasRole('blog_writer')): ?>
                        <li style="border-top:1px solid var(--color-border);margin-top:4px;padding-top:4px">
                            <a href="/account/write" class="btn btn-ghost btn-sm w-full" style="justify-content:flex-start">Write a Post</a>
                        </li>
                        <li>
                            <a href="/account/my-posts" class="btn btn-ghost btn-sm w-full" style="justify-content:flex-start">My Posts</a>
                        </li>
                        <?php endif ?>
                        <?php if ($auth->isAdmin()): ?>
                        <li style="border-top:1px solid var(--color-border);margin-top:4px;padding-top:4px">
                            <a href="/admin/" class="btn btn-ghost btn-sm w-full" style="justify-content:flex-start">Admin Panel</a>
                        </li>
                        <?php endif ?>
                        <li style="border-top:1px solid var(--color-border);margin-top:4px;padding-top:4px">
                            <a href="/logout" class="btn btn-ghost btn-sm w-full" style="justify-content:flex-start;color:var(--color-danger)" data-confirm="Sign out?">Sign Out</a>
                        </li>
                    </ul>
                </div>
            </div>

            <?php if (!$twoFaEnabled): ?>
            <div class="card" style="margin-top:12px;border-color:rgba(250,204,21,.5);background:rgba(250,204,21,.06)">
                <div class="card-body" style="padding:14px 16px">
                    <div style="font-size:13px;font-weight:600;margin-bottom:4px">Secure your account</div>
                    <div style="font-size:12px;color:var(--color-text-muted);margin-bottom:10px">Enable 2FA to get an email code at every sign-in.</div>
                    <a href="/account/profile?tab=2fa" class="btn btn-sm w-full" style="background:rgba(250,204,21,.2);color:#92400e;border:1px solid rgba(250,204,21,.5);font-size:12px">Enable 2FA</a>
                </div>
            </div>
            <?php endif ?>
        </div>

        <!-- Main -->
        <div>
            <!-- Stats -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:24px">
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--color-primary-light);color:var(--color-primary)">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    </div>
                    <div>
                        <div class="stat-val"><?= $totalTools ?></div>
                        <div class="stat-lbl">Available Tools</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(239,68,68,.1);color:#ef4444">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    </div>
                    <div>
                        <div class="stat-val"><?= $totalFavourites ?></div>
                        <div class="stat-lbl">Favourites</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(99,102,241,.1);color:#6366f1">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="stat-val"><?= $daysMember ?></div>
                        <div class="stat-lbl">Days as Member</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:<?= $twoFaEnabled ? 'rgba(34,197,94,.12)' : 'rgba(250,204,21,.12)' ?>;color:<?= $twoFaEnabled ? '#16a34a' : '#ca8a04' ?>">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div>
                        <div class="stat-val" style="font-size:14px;padding-top:4px"><?= $twoFaEnabled ? 'On' : 'Off' ?></div>
                        <div class="stat-lbl">2FA Status</div>
                    </div>
                </div>
            </div>

            <!-- Favourites Section -->
            <div class="card" style="margin-bottom:20px">
                <div class="card-header">
                    <span class="card-title">My Favourites</span>
                    <?php if ($totalFavourites > 0): ?>
                    <a href="/plugins" class="btn btn-ghost btn-sm">Browse more &rarr;</a>
                    <?php endif ?>
                </div>
                <div class="card-body">
                    <?php if (empty($favourites)): ?>
                    <div style="text-align:center;padding:28px 16px">
                        <div style="margin-bottom:8px;color:var(--color-text-muted)">
                            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        </div>
                        <div style="font-size:14px;font-weight:600;margin-bottom:4px">No favourites yet</div>
                        <div style="font-size:13px;color:var(--color-text-muted);margin-bottom:14px">Click the heart button on any tool to save it here.</div>
                        <a href="/plugins" class="btn btn-primary btn-sm">Browse Tools</a>
                    </div>
                    <?php else: ?>
                    <div class="tool-grid">
                        <?php foreach ($favourites as $fav):
                            $m = json_decode($fav['manifest'] ?? '{}', true) ?? [];
                        ?>
                        <a href="/plugins/<?= e($fav['slug']) ?>/" class="tool-item">
                            <div class="tool-icon-wrap">
                                <?php if (!empty($m['icon'])): ?>
                                <span style="font-size:20px;line-height:1"><?= $m['icon'] ?></span>
                                <?php else: ?>
                                <svg width="18" height="18" fill="none" stroke="var(--color-primary)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>
                                <?php endif ?>
                            </div>
                            <div style="font-size:12px;font-weight:600"><?= e($fav['name']) ?></div>
                        </a>
                        <?php endforeach ?>
                    </div>
                    <?php endif ?>
                </div>
            </div>

            <!-- My Applications (dashboard-enabled) -->
            <?php if (!empty($myApps)): ?>
            <div class="card" style="margin-bottom:20px">
                <div class="card-header"><span class="card-title">My Applications</span></div>
                <div class="card-body">
                    <div class="tool-grid">
                        <?php foreach ($myApps as $app):
                            $m = json_decode($app['manifest'] ?? '{}', true) ?? [];
                        ?>
                        <a href="/plugins/<?= e($app['slug']) ?>/" class="tool-item">
                            <div class="tool-icon-wrap">
                                <?php if (!empty($m['icon'])): ?>
                                <span style="font-size:20px;line-height:1"><?= $m['icon'] ?></span>
                                <?php else: ?>
                                <svg width="18" height="18" fill="none" stroke="var(--color-primary)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>
                                <?php endif ?>
                            </div>
                            <div style="font-size:12px;font-weight:600"><?= e($app['name']) ?></div>
                            <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px"><?= e(substr($app['description'] ?? '', 0, 40)) ?></div>
                        </a>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
            <?php endif ?>

            <!-- Account Summary + Recent Posts -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="card">
                    <div class="card-header"><span class="card-title">Account Details</span></div>
                    <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
                        <?php foreach ([
                            ['label' => 'Username',     'value' => '@' . ($user['username'] ?? '')],
                            ['label' => 'Member Since', 'value' => fdate($user['created_at'])],
                            ['label' => 'Last Login',   'value' => $user['last_login_at'] ? fdate($user['last_login_at'], 'M j, Y g:i a') : 'Now'],
                            ['label' => 'Status',       'value' => ucfirst($user['status'] ?? 'active'), 'badge' => 'success'],
                        ] as $row): ?>
                        <div>
                            <div style="font-size:11px;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em;font-weight:600;margin-bottom:3px"><?= $row['label'] ?></div>
                            <?php if (isset($row['badge'])): ?>
                            <span class="badge badge-<?= $row['badge'] ?>"><?= e($row['value']) ?></span>
                            <?php else: ?>
                            <div style="font-size:14px;font-weight:500"><?= e($row['value']) ?></div>
                            <?php endif ?>
                        </div>
                        <?php endforeach ?>
                    </div>
                </div>

                <?php if (!empty($recentPosts)): ?>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Latest Articles</span>
                        <a href="/blog" class="btn btn-ghost btn-sm">All &rarr;</a>
                    </div>
                    <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
                        <?php foreach ($recentPosts as $rp): ?>
                        <a href="/blog/<?= e($rp['slug']) ?>" style="text-decoration:none;color:inherit">
                            <div style="font-size:13px;font-weight:600;line-height:1.35;margin-bottom:3px;color:var(--color-text)"><?= e($rp['title']) ?></div>
                            <div style="font-size:11px;color:var(--color-text-muted)"><?= fdate($rp['published_at'] ?? $rp['created_at']) ?></div>
                        </a>
                        <?php endforeach ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-header"><span class="card-title">Quick Links</span></div>
                    <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
                        <?php foreach ([
                            ['href' => '/plugins',                  'label' => 'Browse All Tools'],
                            ['href' => '/blog',                     'label' => 'Read the Blog'],
                            ['href' => '/account/profile',          'label' => 'Edit Profile'],
                            ['href' => '/account/profile?tab=2fa',  'label' => 'Manage 2FA'],
                            ['href' => '/contact',                  'label' => 'Contact Us'],
                        ] as $link): ?>
                        <a href="<?= $link['href'] ?>" class="btn btn-ghost btn-sm" style="justify-content:flex-start"><?= e($link['label']) ?> &rarr;</a>
                        <?php endforeach ?>
                    </div>
                </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('My Dashboard', $content);
