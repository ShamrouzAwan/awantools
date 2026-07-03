<?php
// AWAN Frontend Layout v1.1 — responsive, dark mode, 4-col footer
defined('AWAN') or die('Direct access denied.');

function render_page(string $title, string $content, array $opts = []): void {
    global $auth, $settings, $theme, $seo, $db;

    $siteName  = $settings->siteName();
    $cssVars   = $theme->cssVariables();
    $flash     = Session::getAllFlash();
    $showNav   = $opts['nav'] ?? true;
    $lang      = $settings->get('language', 'en');
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    // Developer / Branding settings
    $devName       = $settings->get('developer_name', '');
    $devTitle      = $settings->get('developer_title', 'Full-Stack Developer');
    $devEmail      = $settings->get('developer_email', '');
    $devGithub     = $settings->get('developer_github', '');
    $devLinkedin   = $settings->get('developer_linkedin', '');
    $devTwitter    = $settings->get('developer_twitter', '');
    $devFacebook   = $settings->get('developer_facebook', '');
    $devInstagram  = $settings->get('developer_instagram', '');
    $devYoutube    = $settings->get('developer_youtube', '');
    $devWhatsapp   = $settings->get('developer_whatsapp', '');
    $devPortfolio  = $settings->get('developer_portfolio', '');
    $footerTagline = $settings->get('footer_tagline', 'A curated collection of free online tools and applications.');
    $footerCopy    = $settings->get('footer_copyright', '');
    $logoUrl       = $settings->get('logo_url', '');
    $faviconUrl    = $settings->get('favicon_url', '');

    // Footer links from DB (graceful fallback if columns not yet migrated)
    $footerQuickLinks = [];
    $footerLegalLinks = [];
    try {
        $footerQuickLinks = $db->fetchAll(
            "SELECT title, slug FROM pages WHERE show_in_footer = 1 AND status = 'published' AND page_type NOT IN ('legal') ORDER BY sort_order, title"
        ) ?: [];
        $footerLegalLinks = $db->fetchAll(
            "SELECT title, slug FROM pages WHERE show_in_footer = 1 AND status = 'published' AND page_type = 'legal' ORDER BY sort_order, title"
        ) ?: [];
    } catch (Throwable $e) {}

    // SEO
    $htmlTitle = ($seo instanceof Seo) ? $seo->formatTitle($title) : ($title . ' — ' . $siteName);
    $seoTags   = ($seo instanceof Seo) ? $seo->headTags([
        'title'       => $title,
        'description' => $opts['description'] ?? $settings->siteTagline(),
        'image'       => $opts['og_image'] ?? '',
        'canonical'   => $opts['canonical'] ?? '',
    ]) : '    <meta name="description" content="' . e($settings->siteTagline()) . '">' . "\n";
    $gtmNoscript = ($seo instanceof Seo) ? $seo->bodyStart() : '';
    $bodyScripts = ($seo instanceof Seo) ? $seo->bodyEnd() : '';

    // JSON-LD structured data
    $schemaOrgTags = '';
    if ($seo instanceof Seo) {
        if (!empty($opts['schema_org'])) {
            // Per-page custom schema (e.g. BlogPosting) passed from individual page
            $schemaOrgTags = $opts['schema_org'];
        } elseif ($currentPath === '/') {
            $schemaOrgTags = $seo->homepageSchema();
        }
    }

    // Detect if current page is a plugin page for global issue report button
    $isPluginPage = (bool) preg_match('#^/plugins/([a-z0-9_\-]+)#i', $currentPath);
    $pluginSlugMatch = [];
    preg_match('#^/plugins/([a-z0-9_\-]+)#i', $currentPath, $pluginSlugMatch);
    $currentPluginSlug = $pluginSlugMatch[1] ?? '';
    ?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" data-theme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($htmlTitle) ?></title>
<?= $seoTags ?><?= $schemaOrgTags ?>    <link rel="stylesheet" href="/assets/css/awan.css"> <link rel="stylesheet" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style><?= $cssVars ?></style>
    <?php if ($faviconUrl): ?><link rel="icon" href="<?= e($faviconUrl) ?>"><?php endif ?>
    <script>
      // Prevent FOUC — apply stored theme before page renders
      (function(){
        var t=localStorage.getItem('awan-theme');
        var d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;
        if(t==='dark'||((!t)&&d)){document.documentElement.setAttribute('data-theme','dark');}
      })();
    </script>
    <script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "x9xe4hokzy");
</script>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2776042940154221"
     crossorigin="anonymous"></script>

     <?php if ($isPluginPage ): ?>
        <link rel="stylesheet" href="/assets/css/plugin-page.css">
    <?php endif ?>
</head>
<div id="loader-overlay" class="loader-active">
    <div class="wrapper">
        <div class="loader-wheel" id="loader-wheel">
        </div>
    </div>
</div>
<body class="page-front">
<?= $gtmNoscript ?>

<?php if ($showNav): ?>
<nav class="front-nav">
    <a href="/" class="front-nav-brand">
        <?php if ($logoUrl): ?>
        <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>" style="height:32px;width:auto;max-width:140px;object-fit:contain;display:block">
        <?php else: ?>
        <div class="front-nav-brand-icon">A</div>
        <span><?= e($siteName) ?></span>
        <?php endif ?>
    </a>

    <div class="front-nav-links">
        <?php
        // DB-driven nav items (falls back to hardcoded if none configured)
        $dbNavItems = [];
        try {
            $dbNavItems = $db->fetchAll(
                "SELECT * FROM nav_items WHERE location = 'header' AND is_active = 1 ORDER BY sort_order, id"
            ) ?: [];
        } catch (Throwable $e) {}

        if (!empty($dbNavItems)):
            foreach ($dbNavItems as $ni):
                $isActive = ($ni['url'] === '/' && $currentPath === '/')
                         || ($ni['url'] !== '/' && str_starts_with($currentPath, rtrim($ni['url'], '/')));
        ?>
        <a href="<?= e($ni['url']) ?>"<?= $isActive ? ' class="active"' : '' ?><?= $ni['target']==='_blank' ? ' target="_blank" rel="noopener"' : '' ?>><?= e($ni['label']) ?></a>
        <?php endforeach; else: ?>
        <a href="/"<?= $currentPath === '/' ? ' class="active"' : '' ?>>Home</a>
        <a href="/plugins"<?= str_starts_with($currentPath, '/plugins') ? ' class="active"' : '' ?>>Tools</a>
        <a href="/blog"<?= str_starts_with($currentPath, '/blog') ? ' class="active"' : '' ?>>Blog</a>
        <a href="/faq"<?= $currentPath === '/faq' ? ' class="active"' : '' ?>>FAQ</a>
        <a href="/contact"<?= $currentPath === '/contact' ? ' class="active"' : '' ?>>Contact</a>
        <?php endif ?>
        <?php if ($auth->check()): ?>
        <a href="/account/dashboard">Dashboard</a>
        <?php if ($auth->isAdmin()): ?>
        <a href="/admin/">Admin</a>
        <?php endif ?>
        <a href="/logout">Sign Out</a>
        <?php else: ?>
        <?php if ($settings->registrationEnabled()): ?>
        <a href="/register" class="btn btn-primary btn-sm" style="margin-left:6px">Get Started</a>
        <?php else: ?>
        <a href="/login" class="btn btn-outline btn-sm" style="margin-left:6px">Sign In</a>
        <?php endif ?>
        <?php endif ?>
    </div>

    <div class="front-nav-actions">
        <button class="theme-toggle" aria-label="Toggle dark mode" title="Toggle dark mode">
            <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
        <button class="front-hamburger" aria-label="Open menu">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
    </div>
</nav>

<!-- Mobile Nav Drawer -->
<div class="mobile-nav-overlay" style="display:none">
    <div class="mobile-nav-drawer">
        <div class="mobile-nav-header">
            <div class="mobile-nav-header-brand">
                <div class="front-nav-brand-icon" style="width:24px;height:24px;font-size:10px;background:var(--color-primary)">A</div>
                <?= e($siteName) ?>
            </div>
            <button class="mobile-nav-close" aria-label="Close menu">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="mobile-nav-links">
        <?php if (!empty($dbNavItems)): foreach ($dbNavItems as $ni): ?>
            <a href="<?= e($ni['url']) ?>"<?= $ni['target']==='_blank' ? ' target="_blank" rel="noopener"' : '' ?>><?= e($ni['label']) ?></a>
        <?php endforeach; else: ?>
            <a href="/">Home</a>
            <a href="/plugins">Tools</a>
            <a href="/blog">Blog</a>
            <a href="/faq">FAQ</a>
            <a href="/contact">Contact</a>
            <a href="/get-a-quote">Get a Quote</a>
            <a href="/request-tool">Request a Tool</a>
        <?php endif ?>
            <?php if ($auth->check()): ?>
            <a href="/account/dashboard">My Dashboard</a>
            <?php if ($auth->isAdmin()): ?>
            <a href="/admin/">Admin Panel</a>
            <?php endif ?>
            <a href="/logout">Sign Out</a>
            <?php endif ?>
        </div>
        <?php if (!$auth->check()): ?>
        <div class="mobile-nav-footer">
            <?php if ($settings->registrationEnabled()): ?>
            <a href="/register" class="btn btn-primary">Get Started Free</a>
            <?php endif ?>
            <a href="/login" class="btn btn-secondary">Sign In</a>
        </div>
        <?php endif ?>
    </div>
</div>
<?php endif ?>

<?php if (!empty($flash)): ?>
<div style="max-width:1200px;margin:16px auto;padding:0 24px">
    <?php foreach ($flash as $type => $msg): ?>
    <div class="alert alert-<?= in_array($type, ['success','danger','warning','info']) ? e($type) : 'info' ?>" data-dismiss="4000">
        <?= e($msg) ?>
    </div>
    <?php endforeach ?>
</div>
<?php endif ?>

<div class="front-content"><?= $content ?></div>

<!-- ─── 4-Column Footer ─────────────────────────────────────────────────── -->
<footer class="front-footer">
    <div class="front-footer-grid">
        <!-- Column 1: Branding -->
        <div class="footer-col-brand">
            <a href="/" class="footer-brand-logo">
                <div class="footer-brand-icon">A</div>
                <span class="footer-brand-name"><?= e($siteName) ?></span>
            </a>
            <p class="footer-tagline"><?= e($footerTagline) ?></p>
            <?php if ($devName): ?>
            <div class="footer-dev-name">Made by <strong><?= e($devName) ?></strong><?= $devTitle ? ' — ' . e($devTitle) : '' ?></div>
            <?php endif ?>
        </div>

        <!-- Column 2: Quick Links (DB-driven) -->
        <div>
            <div class="footer-col-title">Quick Links</div>
            <ul class="footer-links">
                <?php if (!empty($footerQuickLinks)): ?>
                    <?php foreach ($footerQuickLinks as $fl): ?>
                    <li><a href="/<?= e($fl['slug'] === 'home' ? '' : $fl['slug']) ?>"><?= e($fl['title']) ?></a></li>
                    <?php endforeach ?>
                <?php else: ?>
                    <li><a href="/">Home</a></li>
                    <li><a href="/plugins">Tools</a></li>
                    <li><a href="/blog">Blog</a></li>
                    <li><a href="/faq">FAQ</a></li>
                    <li><a href="/contact">Contact</a></li>
                    <li><a href="/get-a-quote">Get a Quote</a></li>
                    <li><a href="/request-tool">Request a Tool</a></li>
                <?php endif ?>
            </ul>
        </div>

        <!-- Column 3: Legal (DB-driven) -->
        <div>
            <div class="footer-col-title">Legal</div>
            <ul class="footer-links">
                <?php if (!empty($footerLegalLinks)): ?>
                    <?php foreach ($footerLegalLinks as $fl): ?>
                    <li><a href="/<?= e($fl['slug']) ?>"><?= e($fl['title']) ?></a></li>
                    <?php endforeach ?>
                <?php else: ?>
                    <li><a href="/privacy">Privacy Policy</a></li>
                    <li><a href="/terms">Terms of Service</a></li>
                    <li><a href="/disclaimer">Disclaimer</a></li>
                    <li><a href="/cookie-policy">Cookie Policy</a></li>
                <?php endif ?>
            </ul>
        </div>

        <!-- Column 4: Connect -->
        <div>
            <div class="footer-col-title">Connect</div>
            <div class="footer-social">
                <?php if ($devGithub): ?>
                <a href="<?= e($devGithub) ?>" class="footer-social-link" target="_blank" rel="noopener" title="GitHub">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                </a>
                <?php endif ?>
                <?php if ($devLinkedin): ?>
                <a href="<?= e($devLinkedin) ?>" class="footer-social-link" target="_blank" rel="noopener" title="LinkedIn">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                </a>
                <?php endif ?>
                <?php if ($devFacebook): ?>
                <a href="<?= e($devFacebook) ?>" class="footer-social-link" target="_blank" rel="noopener" title="Facebook">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <?php endif ?>
                <?php if ($devTwitter): ?>
                <a href="<?= e($devTwitter) ?>" class="footer-social-link" target="_blank" rel="noopener" title="X / Twitter">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <?php endif ?>
                <?php if ($devEmail): ?>
                <a href="mailto:<?= e($devEmail) ?>" class="footer-social-link" title="Email">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </a>
                <?php endif ?>
                <?php if ($devPortfolio): ?>
                <a href="<?= e($devPortfolio) ?>" class="footer-social-link" target="_blank" rel="noopener" title="Portfolio">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </a>
                <?php endif ?>
                <?php if ($devInstagram): ?>
                <a href="<?= e($devInstagram) ?>" class="footer-social-link" target="_blank" rel="noopener" title="Instagram">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                </a>
                <?php endif ?>
                <?php if ($devYoutube): ?>
                <a href="<?= e($devYoutube) ?>" class="footer-social-link" target="_blank" rel="noopener" title="YouTube">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon fill="currentColor" stroke="none" points="9.75,15.02 15.5,12 9.75,8.98"/></svg>
                </a>
                <?php endif ?>
                <?php if ($devWhatsapp): ?>
                <a href="<?= e($devWhatsapp) ?>" class="footer-social-link" target="_blank" rel="noopener" title="WhatsApp">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                </a>
                <?php endif ?>
            </div>
        </div>
    </div>

    <div class="front-footer-bottom front-container" style="max-width:1200px;margin:0 auto">
        <div class="footer-copyright">
            &copy; <?= date('Y') ?> <?= e($footerCopy ?: $siteName) ?>. All rights reserved.
        </div>
        <div style="display:flex;gap:16px">
            <a href="/privacy" style="font-size:12px;color:#475569;text-decoration:none;transition:color .15s" onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#475569'">Privacy</a>
            <a href="/terms"   style="font-size:12px;color:#475569;text-decoration:none;transition:color .15s" onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#475569'">Terms</a>
            <a href="/contact" style="font-size:12px;color:#475569;text-decoration:none;transition:color .15s" onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#475569'">Contact</a>
        </div>
    </div>
</footer>

<!-- Back to Top -->
<a href="#" class="back-to-top" aria-label="Back to top">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
</a>

<!-- Global Issue Report Button (injected on plugin pages) -->
<?php if ($isPluginPage && $currentPluginSlug): ?>
<button class="report-issue-btn" data-open-modal="report-modal" onclick="openModal('report-modal')">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    Report Issue
</button>

<!-- Report Issue Modal -->
<div class="modal-overlay" id="report-modal" style="display:none">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Report an Issue</div>
            <button class="modal-close" aria-label="Close">&times;</button>
        </div>
        <form method="POST" action="/report-issue" enctype="multipart/form-data" data-loading>
            <?= Security::csrfField() ?>
            <input type="hidden" name="plugin_slug" value="<?= e($currentPluginSlug) ?>">
            <input type="hidden" name="url" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
            <input type="hidden" name="browser" id="report-browser" value="">
            <div class="form-group">
                <label class="form-label">Your Name <span class="req">*</span></label>
                <input type="text" name="reporter_name" class="form-control" required placeholder="John Doe">
            </div>
            <div class="form-group">
                <label class="form-label">Your Email <span class="req">*</span></label>
                <input type="email" name="reporter_email" class="form-control" required placeholder="you@example.com">
            </div>
            <div class="form-group">
                <label class="form-label">Describe the Problem <span class="req">*</span></label>
                <textarea name="description" class="form-control" rows="4" required placeholder="What went wrong? What were you trying to do?"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Screenshot (optional)</label>
                <input type="file" name="screenshot" class="form-control" accept="image/*">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost modal-close">Cancel</button>
                <button type="submit" class="btn btn-danger" data-loading="Submitting…">Submit Report</button>
            </div>
        </form>
    </div>
</div>
<script>document.getElementById('report-browser') && (document.getElementById('report-browser').value = navigator.userAgent);</script>
<?php endif ?>

<!-- Cookie Consent Banner -->
<?php if ($settings->get('cookie_consent_enabled', '1') === '1'): ?>
<div id="awan-cookie-banner" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:9999;background:var(--color-surface,#fff);border-top:1px solid var(--color-border,#e2e8f0);padding:12px 24px;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;box-shadow:0 -2px 10px rgba(0,0,0,.07)">
    <div style="font-size:13px;color:var(--color-text-secondary,#475569);max-width:600px">
        This site uses cookies to improve your experience.
        <a href="/cookie-policy" style="color:var(--color-primary,#6366f1);text-decoration:none;margin-left:4px">Learn more</a>
    </div>
    <div style="display:flex;gap:8px;flex-shrink:0">
        <button id="awan-cookie-reject" style="font-size:12px;padding:6px 14px;border:1px solid var(--color-border,#e2e8f0);border-radius:4px;background:transparent;cursor:pointer;color:var(--color-text-secondary,#475569)">Essential Only</button>
        <button id="awan-cookie-accept" style="font-size:12px;padding:6px 14px;border:none;border-radius:4px;background:var(--color-primary,#6366f1);color:#fff;cursor:pointer;font-weight:600">Accept All</button>
    </div>
</div>
<script>
(function(){
    var b = document.getElementById('awan-cookie-banner');
    if (!b) return;
    if (!localStorage.getItem('cookie_consent')) { b.style.display = 'flex'; }
    function consent(v) { localStorage.setItem('cookie_consent', v); b.style.display = 'none'; }
    var a = document.getElementById('awan-cookie-accept');
    var r = document.getElementById('awan-cookie-reject');
    if (a) a.addEventListener('click', function(){ consent('all'); });
    if (r) r.addEventListener('click', function(){ consent('essential'); });
})();
</script>
<?php endif ?>

<script src="/assets/js/awan.js"></script>
<?= $bodyScripts ?>
</body>
  <script>
  document.addEventListener("DOMContentLoaded", () => {
        const wheel = document.getElementById("loader-wheel");
        const totalCards = 24;

        const themeColors = [
            'var(--color-primary)',
            'var(--color-success)',
            'var(--color-warning)',
            'var(--color-danger)',
            'var(--color-info)',
            'var(--color-sidebar)',
            'var(--color-sidebar-text-active)',
            'var(--color-text-secondary)'
        ];

        for (let i = 0; i < totalCards; i++) {
            const card = document.createElement("div");
            card.classList.add("loader-card");

            const targetRotation = i * (360 / totalCards);
            const color = themeColors[i % themeColors.length];

            card.style.backgroundColor = color;
            card.style.zIndex = i;
            card.style.transform = `rotate(0deg)`;
            wheel.appendChild(card);

            // 800ms (popup duration) + 200ms (pause) = 1000ms wait
            setTimeout(() => {
                card.style.width = "40px"; // Expand to requested 30px width
                card.style.transform = `rotate(${targetRotation}deg)`; // Fan out
            }, 1000 + (i * 15)); // Stagger the expansion
        }
    });

    // Hide loader when page fully loads
    window.addEventListener('load', () => {
        setTimeout(() => {
            const overlay = document.getElementById("loader-overlay");
            overlay.classList.add("loader-hidden");
            overlay.classList.remove("loader-active");
        }, 1200);
    });

    // Trigger for leaving to another page
    function triggerTransition(url) {
        const overlay = document.getElementById("loader-overlay");
        overlay.classList.remove("loader-hidden");
        overlay.classList.add("loader-active");

        // Wait for the animation to play before navigating
        setTimeout(() => {
            window.location.href = url;
        }, 900);
    }
  </script>
</html>
<?php
}
