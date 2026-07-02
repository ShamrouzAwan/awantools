<?php
// AWAN Admin Layout v1.1 — dark mode + mobile hamburger sidebar
defined('AWAN') or die('Direct access denied.');

function render_admin(string $title, string $content, array $opts = []): void {
    global $auth, $settings, $theme;

    global $db;
    $siteName = $settings->siteName();
    $user     = $auth->user();
    $initials = strtoupper(substr($user['name'] ?? $user['username'] ?? '?', 0, 2));
    $cssVars  = $theme->cssVariables();
    $flash    = Session::getAllFlash();
    $section  = $opts['section'] ?? '';

    $pendingCounts = [];
    try {
        require_once AWAN_ROOT . '/_core/Notifications.php';
        $pendingCounts = Notifications::pendingCounts($db);
    } catch (Throwable $e) {}

    $navItems = [
        ['href' => '/admin/',              'label' => 'Dashboard',        'icon' => 'grid',      'section' => 'dashboard'],
        ['href' => '/admin/users',         'label' => 'Users',            'icon' => 'users',     'section' => 'users'],
        ['href' => '/admin/blog',          'label' => 'Blog',             'icon' => 'blog',      'section' => 'blog'],
        ['href' => '/admin/pages',         'label' => 'Pages',            'icon' => 'file',      'section' => 'pages'],
        ['href' => '/admin/media',         'label' => 'Media',            'icon' => 'media',     'section' => 'media'],
        ['href' => '/admin/analytics',     'label' => 'Analytics',        'icon' => 'chart',     'section' => 'analytics'],
        ['href' => '/admin/plugins',       'label' => 'Plugins',          'icon' => 'puzzle',    'section' => 'plugins'],
        ['href' => '/admin/themes',        'label' => 'Themes',           'icon' => 'palette',   'section' => 'themes'],
        ['href' => '/admin/quotes',        'label' => 'Quote Requests',   'icon' => 'briefcase', 'section' => 'quotes',        'badge_key' => 'quotes'],
        ['href' => '/admin/tool-requests', 'label' => 'Tool Requests',    'icon' => 'tools',     'section' => 'tool-requests', 'badge_key' => 'tool-requests'],
        ['href' => '/admin/menus',         'label' => 'Menus',            'icon' => 'menu',      'section' => 'menus'],
        ['href' => '/admin/email-templates','label' => 'Email Templates', 'icon' => 'template',  'section' => 'email-templates'],
        ['href' => '/admin/newsletter',    'label' => 'Newsletter',       'icon' => 'newsletter', 'section' => 'newsletter'],
        ['href' => '/admin/testimonials',  'label' => 'Testimonials',     'icon' => 'star',       'section' => 'testimonials'],
        ['href' => '/admin/contacts',      'label' => 'Contact Messages', 'icon' => 'mail',      'section' => 'contacts',      'badge_key' => 'contacts'],
        ['href' => '/admin/reports',       'label' => 'Issue Reports',    'icon' => 'flag',      'section' => 'reports',       'badge_key' => 'reports'],
        ['href' => '/admin/notifications', 'label' => 'Notifications',    'icon' => 'bell',      'section' => 'notifications', 'badge_key' => 'notifications'],
        ['href' => '/admin/setup',         'label' => 'Setup Checklist',  'icon' => 'setup',     'section' => 'setup'],
        ['href' => '/admin/settings',      'label' => 'Settings',         'icon' => 'settings',  'section' => 'settings'],
        ['href' => '/admin/seo',           'label' => 'SEO',              'icon' => 'seo',       'section' => 'seo'],
        ['href' => '/admin/email-logs',    'label' => 'Email Logs',       'icon' => 'email-log', 'section' => 'email-logs'],
        ['href' => '/admin/logs',          'label' => 'Logs',             'icon' => 'log',       'section' => 'logs'],
        ['href' => '/admin/scheduler',     'label' => 'Scheduler',        'icon' => 'clock',     'section' => 'scheduler'],
        ['href' => '/admin/backup',        'label' => 'Backup',           'icon' => 'backup',    'section' => 'backup'],
        ['href' => '/admin/system',        'label' => 'System',           'icon' => 'server',    'section' => 'system'],
        ['href' => '/admin/profile',       'label' => 'My Profile',       'icon' => 'person',    'section' => 'profile'],
    ];

    // File Browser — super admin only
    if ($auth->isSuperAdmin()) {
        $navItems[] = ['href' => '/admin/files', 'label' => 'File Browser', 'icon' => 'folder-code', 'section' => 'files'];
    }

    $icons = [
        'grid'      => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
        'users'     => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'blog'      => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
        'file'      => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
        'media'     => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
        'puzzle'    => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><line x1="16" y1="8" x2="2" y2="22"/><line x1="17.5" y1="15" x2="9" y2="15"/></svg>',
        'palette'   => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>',
        'briefcase' => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
        'tools'     => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
        'flag'      => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>',
        'settings'  => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'log'       => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'chart'     => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>',
        'backup'    => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/><line x1="8" y1="12" x2="12" y2="8"/><line x1="12" y1="8" x2="16" y2="12"/><line x1="12" y1="8" x2="12" y2="16"/></svg>',
        'server'    => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>',
        'clock'     => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'person'    => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'seo'       => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/><path d="M11 8v6M8 11h6"/></svg>',
        'mail'      => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
        'bell'      => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        'menu'      => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
        'template'  => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
        'setup'       => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
        'folder-code' => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><polyline points="9 15 11 17 15 13"/></svg>',
        'newsletter'  => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/><line x1="12" y1="13" x2="12" y2="20"/></svg>',
        'email-log'   => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/><line x1="8" y1="17" x2="16" y2="17"/></svg>',
        'star'        => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    ];
    ?>
<!DOCTYPE html>
<html lang="en" data-theme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> — <?= e($siteName) ?> Admin</title>
    <link rel="stylesheet" href="/assets/css/awan.css">
    <link rel="stylesheet" href="/assets/css/admin-custom.css">
    <style><?= $cssVars ?></style>
    <?php $faviconUrl = $settings->get('favicon_url', ''); if ($faviconUrl): ?><link rel="icon" href="<?= e($faviconUrl) ?>"><?php endif ?>
    <script>
      (function(){
        var t=localStorage.getItem('awan-theme');
        var d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;
        if(t==='dark'||((!t)&&d)){document.documentElement.setAttribute('data-theme','dark');}
      })();
    </script>
</head>
<body class="aside-deactive">
<div class="awan-layout">

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" style="display:none"></div>

    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="/admin/" class="sidebar-brand">
            <?php $logoUrl = $settings->get('logo_url', ''); if ($logoUrl): ?>
            <div class="sidebar-brand-icon" style="background:transparent;padding:0;overflow:hidden"><img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>" style="width:32px;height:32px;object-fit:contain;display:block"></div>
            <?php else: ?>
            <div class="sidebar-brand-icon">A</div>
            <?php endif ?>
            <span class="sidebar-brand-name"><?= e($siteName) ?></span>
            <span class="sidebar-brand-version">Admin</span>
        </a>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Navigation</div>
            <ul class="sidebar-nav">
                <?php foreach ($navItems as $item): ?>
                <?php $badgeCount = isset($item['badge_key']) ? (int)($pendingCounts[$item['badge_key']] ?? 0) : 0; ?>
                <li class="sidebar-nav-item<?= $section === $item['section'] ? ' active' : '' ?>">
                    <a href="<?= $item['href'] ?>" style="display:flex;align-items:center">
                        <span class="nav-icon"><?= $icons[$item['icon']] ?? '' ?></span>
                        <span style="flex:1"><?= e($item['label']) ?></span>
                        <?php if ($badgeCount > 0): ?>
                        <span style="background:var(--color-danger,#ef4444);color:#fff;font-size:10px;font-weight:700;padding:0 5px;border-radius:10px;min-width:16px;height:16px;display:flex;align-items:center;justify-content:center;line-height:1;margin-left:4px"><?= $badgeCount > 99 ? '99+' : $badgeCount ?></span>
                        <?php endif ?>
                    </a>
                </li>
                <?php endforeach ?>
            </ul>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Platform</div>
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a href="/">
                        <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
                        View Site
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="/account/dashboard">
                        <span class="nav-icon"><?= $icons['person'] ?></span>
                        My Account
                    </a>
                </li>
            </ul>
        </div>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar"><?= e($initials) ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= e($user['name'] ?? $user['username']) ?></div>
                    <div class="sidebar-user-role"><?= e(implode(', ', $auth->getUserRoles($auth->id()))) ?></div>
                </div>
            </div>
            <ul class="sidebar-nav" style="margin-top:4px">
                <li class="sidebar-nav-item">
                    <a href="/logout" data-confirm="Sign out of the admin panel?">
                        <span class="nav-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
                        Sign Out
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- Main -->
    <div class="main-content">
        <!-- Sticky top bar with hamburger + dark mode toggle -->
        <div style="background:var(--color-surface);border-bottom:1px solid var(--color-border);padding:0 28px;height:56px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;gap:12px;transition:background .3s,border-color .3s;">
            <div style="display:flex;align-items:center;gap:10px;">
                <button class="admin-hamburger" aria-label="Toggle sidebar">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <span style="font-size:15px;font-weight:600;color:var(--color-text)"><?= e($title) ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                

                <a href="#" id="my-checkbox" onclick="document.querySelector('body').classList.toggle('aside-deactive');" style="font-size:12px;color:var(--color-text-muted);text-decoration:none;padding:4px 8px;border-radius:4px;border:1px solid var(--color-border);transition:background .15s" title="Toggle Sidebar">
                    <svg width="15" height="15" fill="var(--color-text)" viewBox="0 0 1.2 1.2" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2"><path style="fill:none" d="M0 -1.2h24v15H0z"/><path d="M0.938 1.051H0.263a0.112 0.112 0 0 1 -0.112 -0.112v-0.675a0.112 0.112 0 0 1 0.112 -0.112h0.674a0.112 0.112 0 0 1 0.112 0.112v0.675a0.112 0.112 0 0 1 -0.112 0.112m-0.488 -0.075v-0.75H0.281A0.056 0.056 0 0 0 0.225 0.283v0.638a0.056 0.056 0 0 0 0.056 0.056zm0.469 -0.75H0.526v0.75h0.394a0.056 0.056 0 0 0 0.056 -0.056v-0.637a0.056 0.056 0 0 0 -0.056 -0.056"/></svg>
                </a>
                <a href="/" style="font-size:12px;color:var(--color-text-muted);text-decoration:none;padding:4px 8px;border-radius:4px;border:1px solid var(--color-border);transition:background .15s" title="View site">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:middle"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </a>
                <?php $notifCount = (int)($pendingCounts['notifications'] ?? 0); ?>
                <a href="/admin/notifications" style="position:relative;display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:4px;border:1px solid var(--color-border);color:var(--color-text-muted);text-decoration:none;transition:background .15s" title="Notifications">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if ($notifCount > 0): ?>
                    <span style="position:absolute;top:-4px;right:-4px;background:var(--color-danger,#ef4444);color:#fff;font-size:9px;font-weight:700;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;line-height:1"><?= $notifCount > 9 ? '9+' : $notifCount ?></span>
                    <?php endif ?>
                </a>
                <button class="theme-toggle" aria-label="Toggle dark mode" title="Toggle dark/light mode">
                    <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                    <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
            </div>
        </div>

        <?php if (!empty($flash)): ?>
        <div style="padding:16px 28px 0">
            <?php foreach ($flash as $type => $msg): ?>
            <div class="alert alert-<?= in_array($type, ['success','danger','warning','info']) ? e($type) : 'info' ?>" data-dismiss="4000">
                <?= e($msg) ?>
            </div>
            <?php endforeach ?>
        </div>
        <?php endif ?>

        <?= $content ?>
    </div>
</div>
<script src="/assets/js/awan.js"></script>

<!-- ─── Media Picker Modal ──────────────────────────────────────────────── -->
<div id="media-picker-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);backdrop-filter:blur(3px)" onclick="if(event.target===this)closeMediaPicker()">
  <div style="background:var(--color-surface);border-radius:var(--radius-large);width:90vw;max-width:900px;max-height:86vh;display:flex;flex-direction:column;margin:auto;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);box-shadow:0 20px 60px rgba(0,0,0,.3)">
    <div style="padding:16px 20px;border-bottom:1px solid var(--color-border);display:flex;align-items:center;gap:12px">
      <span style="font-weight:700;font-size:15px;flex:1">Media Library</span>
      <div style="display:flex;gap:8px;align-items:center">
        <input type="text" id="mp-search" placeholder="Search…" style="padding:6px 12px;border:1px solid var(--color-border);border-radius:var(--radius-small);font-size:13px;background:var(--color-background);color:var(--color-text);width:180px" oninput="mpSearch(this.value)">
        <label class="btn btn-secondary btn-sm" style="cursor:pointer">
          Upload
          <input type="file" accept="image/*" multiple style="display:none" onchange="mpUpload(this)">
        </label>
        <button onclick="closeMediaPicker()" style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--color-text-muted);line-height:1;padding:2px 6px">&times;</button>
      </div>
    </div>
    <div id="mp-body" style="flex:1;overflow-y:auto;padding:16px">
      <div id="mp-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:10px"></div>
      <div id="mp-empty" style="display:none;text-align:center;padding:40px;color:var(--color-text-muted);font-size:14px">No images found. Upload one above.</div>
      <div id="mp-loading" style="text-align:center;padding:40px;color:var(--color-text-muted);font-size:14px">Loading…</div>
    </div>
    <div id="mp-footer" style="display:none;padding:12px 16px;border-top:1px solid var(--color-border);display:flex;align-items:center;gap:12px">
      <img id="mp-preview" src="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid var(--color-border)">
      <div style="flex:1;min-width:0">
        <div id="mp-selected-name" style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></div>
        <div id="mp-selected-url" style="font-size:11px;color:var(--color-text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></div>
      </div>
      <button onclick="mpConfirm()" class="btn btn-primary btn-sm">Select Image</button>
    </div>
  </div>
</div>
<script>
(function(){
  var _cb=null, _sel=null, _all=[], _filtered=[];
  function el(id){return document.getElementById(id);}

  window.openMediaPicker=function(cb){
    _cb=cb; _sel=null;
    el('media-picker-modal').style.display='block';
    el('mp-search').value='';
    el('mp-footer').style.display='none';
    mpLoad();
  };
  window.closeMediaPicker=function(){
    el('media-picker-modal').style.display='none';
    _cb=null; _sel=null;
  };
  window.mpConfirm=function(){
    if(_sel&&_cb){_cb(_sel);closeMediaPicker();}
  };

  function mpLoad(){
    el('mp-loading').style.display='block';
    el('mp-grid').innerHTML='';
    el('mp-empty').style.display='none';
    fetch('/admin/media-picker-data')
      .then(r=>r.json())
      .then(function(data){
        el('mp-loading').style.display='none';
        _all=data.items||[];
        _filtered=_all.slice();
        mpRender(_filtered);
      })
      .catch(function(){
        el('mp-loading').style.display='none';
        el('mp-empty').style.display='block';
        el('mp-empty').textContent='Failed to load media. Try again.';
      });
  }

  function mpRender(items){
    var grid=el('mp-grid');
    grid.innerHTML='';
    if(!items.length){el('mp-empty').style.display='block';return;}
    el('mp-empty').style.display='none';
    items.forEach(function(item){
      var d=document.createElement('div');
      d.style.cssText='cursor:pointer;border-radius:8px;overflow:hidden;border:2px solid transparent;transition:border-color .15s;position:relative;aspect-ratio:1';
      d.dataset.url=item.url_path;
      d.dataset.name=item.original_name||item.filename;
      d.innerHTML='<img src="'+item.url_path+'" alt="'+(item.alt_text||'')+'" style="width:100%;height:100%;object-fit:cover;display:block" loading="lazy">';
      d.onclick=function(){
        document.querySelectorAll('#mp-grid > div').forEach(function(x){x.style.borderColor='transparent';});
        d.style.borderColor='var(--color-primary)';
        _sel=d.dataset.url;
        el('mp-preview').src=_sel;
        el('mp-selected-name').textContent=d.dataset.name;
        el('mp-selected-url').textContent=_sel;
        el('mp-footer').style.display='flex';
      };
      d.ondblclick=function(){_sel=d.dataset.url;if(_cb){_cb(_sel);closeMediaPicker();}};
      grid.appendChild(d);
    });
  }

  window.mpSearch=function(q){
    q=q.toLowerCase();
    _filtered=_all.filter(function(i){
      return !q||(i.original_name||'').toLowerCase().includes(q)||(i.alt_text||'').toLowerCase().includes(q);
    });
    mpRender(_filtered);
  };

  window.mpUpload=function(input){
    if(!input.files.length) return;
    var fd=new FormData();
    Array.from(input.files).forEach(function(f){fd.append('files[]',f);});
    fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')&&document.querySelector('meta[name="csrf-token"]').content||'');
    el('mp-loading').textContent='Uploading…';
    el('mp-loading').style.display='block';
    fetch('/admin/media-picker-upload', {method:'POST',body:fd})
      .then(r=>r.json())
      .then(function(d){
        el('mp-loading').style.display='none';
        mpLoad();
      })
      .catch(function(){el('mp-loading').style.display='none';mpLoad();});
    input.value='';
  };
})();
</script>
</body>
</html>
<?php
}
