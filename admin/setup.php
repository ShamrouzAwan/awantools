<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

// Only super admins should access this
if (!$auth->isSuperAdmin()) {
    redirect('/admin/');
}

$logger = Logger::getInstance($db);

// ── Handle dismiss ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    if ($_POST['action'] ?? '' === 'dismiss') {
        $settings->set('setup_wizard_dismissed', '1', 'general');
        Session::flash('info', 'Setup checklist dismissed. You can reopen it from Settings at any time.');
        redirect('/admin/');
    }
}

// ── Build checklist ───────────────────────────────────────────────────────────
function awan_setup_checks(Database $db, Settings $settings): array
{
    $smtpHost  = $settings->get('smtp_host', '');
    $smtpFrom  = $settings->get('smtp_from_email', '');
    $smtpReady = $settings->get('smtp_enabled', '0') === '1' && $smtpHost !== '';

    $navHeaderCount = 0;
    $navFooterCount = 0;
    $blogCount      = 0;
    try {
        $navHeaderCount = $db->count('nav_items', "location = 'header' AND is_active = 1");
        $navFooterCount = $db->count('nav_items', "location = 'footer' AND is_active = 1");
        $blogCount      = $db->count('blog_posts', "status = 'published'");
    } catch (Throwable $e) {}

    $emailVerifOn  = $settings->get('email_verification_enabled', '0') === '1';
    $googleOn      = $settings->get('google_oauth_enabled', '0') === '1';
    $googleOk      = $settings->get('google_client_id', '') !== '' && $settings->get('google_client_secret', '') !== '';

    $hasSocial = $settings->get('developer_portfolio', '') || $settings->get('developer_github', '')
              || $settings->get('developer_linkedin', '') || $settings->get('developer_twitter', '');

    return [
        [
            'group' => 'Site Identity',
            'icon'  => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>',
            'items' => [
                [
                    'key'   => 'site_name',
                    'label' => 'Custom site name set',
                    'hint'  => 'Give your platform a name other than the default.',
                    'done'  => $settings->get('site_name', '') !== '' && $settings->get('site_name', '') !== 'AWAN Platform',
                    'url'   => '/admin/settings?tab=general',
                ],
                [
                    'key'   => 'site_tagline',
                    'label' => 'Site tagline written',
                    'hint'  => 'A short line shown under the site name in some themes.',
                    'done'  => $settings->get('site_tagline', '') !== '',
                    'url'   => '/admin/settings?tab=general',
                ],
                [
                    'key'   => 'site_url',
                    'label' => 'Site URL configured',
                    'hint'  => 'Required for email links, OG image URLs, and OAuth callbacks.',
                    'done'  => $settings->get('site_url', '') !== '',
                    'url'   => '/admin/settings?tab=general',
                    'warn'  => $settings->get('site_url', '') === '',
                ],
                [
                    'key'   => 'timezone',
                    'label' => 'Timezone configured',
                    'hint'  => 'Set your local timezone for correct date display.',
                    'done'  => $settings->get('timezone', 'UTC') !== 'UTC',
                    'url'   => '/admin/settings?tab=general',
                ],
            ],
        ],
        [
            'group' => 'Branding',
            'icon'  => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
            'items' => [
                [
                    'key'   => 'logo_url',
                    'label' => 'Logo uploaded',
                    'hint'  => 'Shown in the header and outgoing emails.',
                    'done'  => $settings->get('logo_url', '') !== '',
                    'url'   => '/admin/settings?tab=branding',
                ],
                [
                    'key'   => 'favicon_url',
                    'label' => 'Favicon uploaded',
                    'hint'  => 'The small icon shown in browser tabs.',
                    'done'  => $settings->get('favicon_url', '') !== '',
                    'url'   => '/admin/settings?tab=branding',
                ],
                [
                    'key'   => 'developer_name',
                    'label' => 'Developer / author name set',
                    'hint'  => 'Displayed in the footer and the Connect section.',
                    'done'  => $settings->get('developer_name', '') !== '',
                    'url'   => '/admin/settings?tab=branding',
                ],
                [
                    'key'   => 'footer_tagline',
                    'label' => 'Footer tagline customised',
                    'hint'  => 'Short description shown in the footer column.',
                    'done'  => $settings->get('footer_tagline', '') !== ''
                               && $settings->get('footer_tagline', '') !== 'A curated collection of free online tools and applications.',
                    'url'   => '/admin/settings?tab=branding',
                ],
                [
                    'key'   => 'footer_copyright',
                    'label' => 'Copyright line set',
                    'hint'  => 'Shown at the bottom of every page.',
                    'done'  => $settings->get('footer_copyright', '') !== '',
                    'url'   => '/admin/settings?tab=branding',
                ],
            ],
        ],
        [
            'group' => 'Email / SMTP',
            'icon'  => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
            'items' => [
                [
                    'key'   => 'smtp_enabled',
                    'label' => 'SMTP sending enabled',
                    'hint'  => 'Required for all transactional emails.',
                    'done'  => $smtpReady,
                    'url'   => '/admin/settings?tab=email',
                    'warn'  => !$smtpReady,
                ],
                [
                    'key'   => 'smtp_host',
                    'label' => 'SMTP host configured',
                    'hint'  => 'e.g. smtp.gmail.com or mail.example.com',
                    'done'  => $smtpHost !== '',
                    'url'   => '/admin/settings?tab=email',
                ],
                [
                    'key'   => 'smtp_from_email',
                    'label' => 'From email address set',
                    'hint'  => 'The address all outgoing emails are sent from.',
                    'done'  => $smtpFrom !== '',
                    'url'   => '/admin/settings?tab=email',
                ],
                [
                    'key'   => 'smtp_from_name',
                    'label' => 'From name set',
                    'hint'  => 'Display name shown to email recipients.',
                    'done'  => $settings->get('smtp_from_name', '') !== '',
                    'url'   => '/admin/settings?tab=email',
                ],
            ],
        ],
        [
            'group' => 'Authentication',
            'icon'  => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
            'items' => array_filter([
                [
                    'key'   => 'registration_enabled',
                    'label' => 'Registration setting confirmed',
                    'hint'  => 'Decide whether to allow public sign-ups.',
                    'done'  => true, // this is always decided
                    'url'   => '/admin/settings?tab=auth',
                ],
                [
                    'key'   => 'email_verification',
                    'label' => 'Email verification' . ($emailVerifOn ? ' enabled and SMTP ready' : ' decision made'),
                    'hint'  => $emailVerifOn && !$smtpReady
                               ? 'Email verification is ON but SMTP is not configured — users will not receive emails.'
                               : 'Enable to require users to confirm their email before logging in.',
                    'done'  => !$emailVerifOn || ($emailVerifOn && $smtpReady),
                    'warn'  => $emailVerifOn && !$smtpReady,
                    'url'   => '/admin/settings?tab=auth',
                ],
                $googleOn ? [
                    'key'   => 'google_oauth',
                    'label' => 'Google OAuth Client ID and Secret set',
                    'hint'  => 'Google Sign-In is enabled but credentials are missing.',
                    'done'  => $googleOk,
                    'warn'  => !$googleOk,
                    'url'   => '/admin/settings?tab=auth',
                ] : null,
            ]),
        ],
        [
            'group' => 'SEO & Open Graph',
            'icon'  => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
            'items' => [
                [
                    'key'   => 'seo_meta_description',
                    'label' => 'Default meta description written',
                    'hint'  => 'Shown in search engine results for pages without a custom description.',
                    'done'  => $settings->get('seo_meta_description', '') !== '',
                    'url'   => '/admin/seo',
                ],
                [
                    'key'   => 'og_default_image',
                    'label' => 'Default OG / social share image set',
                    'hint'  => 'Image shown when any page is shared on WhatsApp, Twitter, Facebook, etc.',
                    'done'  => $settings->get('og_default_image', '') !== '',
                    'url'   => '/admin/seo',
                ],
                [
                    'key'   => 'seo_canonical_url',
                    'label' => 'Canonical / site URL set for SEO',
                    'hint'  => 'Used for sitemap and canonical link tags.',
                    'done'  => $settings->get('seo_canonical_url', '') !== '' || $settings->get('site_url', '') !== '',
                    'url'   => '/admin/seo',
                ],
            ],
        ],
        [
            'group' => 'Navigation',
            'icon'  => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
            'items' => [
                [
                    'key'   => 'nav_header',
                    'label' => 'Header navigation items added',
                    'hint'  => 'Without items here the nav falls back to hardcoded links.',
                    'done'  => $navHeaderCount > 0,
                    'url'   => '/admin/menus',
                ],
                [
                    'key'   => 'nav_footer',
                    'label' => 'Footer navigation links added',
                    'hint'  => 'Links shown in the footer columns.',
                    'done'  => $navFooterCount > 0,
                    'url'   => '/admin/menus?loc=footer',
                ],
            ],
        ],
        [
            'group' => 'Homepage',
            'icon'  => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
            'items' => [
                [
                    'key'   => 'hero_title',
                    'label' => 'Hero title customised',
                    'hint'  => 'The big headline on your homepage.',
                    'done'  => $settings->get('hero_title', '') !== '' && $settings->get('hero_title', '') !== 'One Account.',
                    'url'   => '/admin/settings?tab=homepage',
                ],
                [
                    'key'   => 'hero_subtitle',
                    'label' => 'Hero subtitle customised',
                    'hint'  => 'The supporting paragraph under the headline.',
                    'done'  => $settings->get('hero_subtitle', '') !== ''
                               && strpos($settings->get('hero_subtitle', ''), 'Register once, use everything') === false,
                    'url'   => '/admin/settings?tab=homepage',
                ],
            ],
        ],
        [
            'group' => 'Social & Developer',
            'icon'  => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'items' => [
                [
                    'key'   => 'developer_email',
                    'label' => 'Contact email set',
                    'hint'  => 'Shown in the Connect section and used for contact replies.',
                    'done'  => $settings->get('developer_email', '') !== '',
                    'url'   => '/admin/settings?tab=branding',
                ],
                [
                    'key'   => 'social_links',
                    'label' => 'At least one social link added',
                    'hint'  => 'GitHub, LinkedIn, Twitter, portfolio, etc.',
                    'done'  => (bool)$hasSocial,
                    'url'   => '/admin/settings?tab=social',
                ],
            ],
        ],
        [
            'group' => 'Content',
            'icon'  => '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
            'items' => [
                [
                    'key'   => 'blog_post',
                    'label' => 'First blog post published',
                    'hint'  => 'Publish an article to activate your blog.',
                    'done'  => $blogCount > 0,
                    'url'   => '/admin/blog',
                ],
            ],
        ],
    ];
}

$groups     = awan_setup_checks($db, $settings);
$totalItems = 0;
$doneItems  = 0;
foreach ($groups as $group) {
    foreach ($group['items'] as $item) {
        if ($item === null) continue;
        $totalItems++;
        if ($item['done']) $doneItems++;
    }
}
$pct = $totalItems > 0 ? round($doneItems / $totalItems * 100) : 0;

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Platform Setup</div>
            <div class="page-subtitle">Complete these items to get your platform ready to launch</div>
        </div>
    </div>
    <div class="topbar-actions">
        <?php if ($doneItems === $totalItems): ?>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="dismiss">
            <button type="submit" class="btn btn-primary btn-sm">Mark complete and close</button>
        </form>
        <?php else: ?>
        <form method="POST" onsubmit="return confirm('Dismiss the setup checklist? You can return to it via Settings.')">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="dismiss">
            <button type="submit" class="btn btn-secondary btn-sm">Dismiss</button>
        </form>
        <?php endif ?>
    </div>
</div>

<div class="page-body">

    <!-- Progress summary -->
    <div class="card" style="margin-bottom:24px">
        <div class="card-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px">
                    <span style="font-weight:600;font-size:14px">Setup Progress</span>
                    <span style="font-size:13px;color:var(--color-text-muted)"><?= $doneItems ?> / <?= $totalItems ?> complete</span>
                </div>
                <div style="height:8px;background:var(--color-border);border-radius:99px;overflow:hidden">
                    <div style="height:100%;width:<?= $pct ?>%;background:<?= $pct === 100 ? 'var(--color-success,#10b981)' : 'var(--color-primary)' ?>;border-radius:99px;transition:width 0.4s"></div>
                </div>
            </div>
            <div style="font-size:28px;font-weight:800;letter-spacing:-1px;color:<?= $pct === 100 ? 'var(--color-success,#10b981)' : 'var(--color-primary)' ?>"><?= $pct ?>%</div>
        </div>
    </div>

    <!-- Check groups -->
    <?php foreach ($groups as $group): ?>
    <?php
    $groupTotal = count(array_filter($group['items']));
    $groupDone  = count(array_filter($group['items'], fn($i) => $i && $i['done']));
    ?>
    <div class="card" style="margin-bottom:16px">
        <div class="card-header" style="padding:14px 20px">
            <span class="card-title" style="display:flex;align-items:center;gap:8px">
                <span style="color:var(--color-primary)"><?= $group['icon'] ?></span>
                <?= e($group['group']) ?>
            </span>
            <span style="font-size:12px;color:<?= $groupDone === $groupTotal ? 'var(--color-success,#10b981)' : 'var(--color-text-muted)' ?>;font-weight:600">
                <?= $groupDone ?>/<?= $groupTotal ?>
            </span>
        </div>
        <div style="padding:0 20px">
        <?php foreach ($group['items'] as $i => $item): ?>
        <?php if ($item === null) continue; ?>
        <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 0;<?= $i < count($group['items']) - 1 ? 'border-bottom:1px solid var(--color-border)' : '' ?>">
            <!-- Status icon -->
            <div style="flex-shrink:0;margin-top:1px">
                <?php if ($item['done']): ?>
                <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:var(--color-success-light,#d1fae5);color:var(--color-success,#10b981)">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                <?php elseif (!empty($item['warn'])): ?>
                <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#fef3c7;color:#d97706">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <?php else: ?>
                <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:var(--color-border);color:var(--color-text-muted)">
                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>
                </span>
                <?php endif ?>
            </div>
            <!-- Text -->
            <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:<?= $item['done'] ? '500' : '600' ?>;color:<?= $item['done'] ? 'var(--color-text-secondary)' : 'var(--color-text)' ?>;<?= $item['done'] ? 'text-decoration:line-through;' : '' ?>">
                    <?= e($item['label']) ?>
                </div>
                <?php if (!$item['done'] && !empty($item['hint'])): ?>
                <div style="font-size:12px;color:<?= !empty($item['warn']) ? '#d97706' : 'var(--color-text-muted)' ?>;margin-top:2px;line-height:1.5">
                    <?= e($item['hint']) ?>
                </div>
                <?php endif ?>
            </div>
            <!-- Action -->
            <?php if (!$item['done']): ?>
            <a href="<?= e($item['url']) ?>" class="btn btn-secondary btn-sm" style="flex-shrink:0;white-space:nowrap">
                Configure
            </a>
            <?php else: ?>
            <a href="<?= e($item['url']) ?>" class="btn btn-ghost btn-sm" style="flex-shrink:0;color:var(--color-text-muted)">
                Edit
            </a>
            <?php endif ?>
        </div>
        <?php endforeach ?>
        </div>
    </div>
    <?php endforeach ?>

    <?php if ($doneItems === $totalItems): ?>
    <div class="card" style="border-color:var(--color-success,#10b981);background:var(--color-success-light,#d1fae5)">
        <div class="card-body" style="text-align:center;padding:32px">
            <div style="font-size:32px;margin-bottom:8px;color:var(--color-success,#10b981)">
                <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div style="font-size:18px;font-weight:700;color:var(--color-success,#10b981);margin-bottom:6px">All done!</div>
            <p style="font-size:14px;color:var(--color-text-secondary);margin-bottom:16px">Your platform is fully configured and ready to go.</p>
            <form method="POST" style="display:inline">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="dismiss">
                <button type="submit" class="btn btn-primary">Close setup checklist</button>
            </form>
        </div>
    </div>
    <?php endif ?>

</div>

<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Platform Setup', $content, ['section' => 'setup']);
