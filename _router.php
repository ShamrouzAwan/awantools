<?php
// AWAN Platform — Router (PHP built-in server + Apache compatible)
define('AWAN', true);

$uri  = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);
$file = __DIR__ . $path;

// Block direct access to storage/ except uploads/
if (preg_match('#^/storage/(?!uploads/)#', $path)) {
    http_response_code(403);
    exit('Forbidden');
}

// Block sensitive directories and files
if (preg_match('#^/(_core|_database|_lang|_bootstrap|_config|start\.sh|README)#i', $path)) {
    http_response_code(403);
    exit('Forbidden');
}

// Generic OG image generator — /og-image.php?slug=my-plugin
// Must run BEFORE _bootstrap.php to avoid session/no-cache headers poisoning the image response
if ($path === '/og-image.php') {
    require __DIR__ . '/og-image.php';
    exit;
}

// Previewer Toolkit image renderer — must run BEFORE _bootstrap.php to avoid session headers
// corrupting the image binary response used by social crawlers
if ($path === '/plugins/previewer-toolkit/render') {
    require __DIR__ . '/plugins/previewer-toolkit/render.php';
    exit;
}

// Block direct access to PHP files inside plugins/ — only index.php is served through the directory route
if (preg_match('#^/plugins/[^/]+/.+\.php$#i', $path)) {
    http_response_code(403);
    exit('Forbidden');
}

// Serve plugin static assets with correct MIME types (works on both built-in server and Apache fallback)
if (preg_match('#^/plugins/([a-z0-9_\-]+)/assets/([a-z0-9_\-\./]+)$#i', $path, $m)) {
    $assetFile = __DIR__ . '/plugins/' . $m[1] . '/assets/' . $m[2];
    if (file_exists($assetFile) && is_file($assetFile)) {
        $mimes = [
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'application/javascript; charset=UTF-8',
            'json'  => 'application/json; charset=UTF-8',
            'svg'   => 'image/svg+xml',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
        ];
        $ext = strtolower(pathinfo($assetFile, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        readfile($assetFile);
        exit;
    }
    http_response_code(404);
    exit('Not Found');
}

// Email open/click tracking — must be routed before maintenance mode check
if (preg_match('#^/email-track/(open|click)/[a-f0-9]{32}$#i', $path)) {
    require __DIR__ . '/email-track.php'; exit;
}

// ─── Maintenance Mode ─────────────────────────────────────────────────────────
// Check early — before any routing — so the page is always served to non-admins
if (!str_starts_with($path, '/admin') && $path !== '/login' && $path !== '/logout'
    && !str_starts_with($path, '/api/') && $path !== '/api'
    && $path !== '/sitemap.xml' && $path !== '/robots.txt') {
    require_once __DIR__ . '/_bootstrap.php';
    if ($settings->get('maintenance_mode', '0') === '1') {
        // Allow admins through
        if (!$auth->isAdmin()) {
            http_response_code(503);
            $siteName = $settings->get('site_name', 'AWAN Platform');
            ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Under Maintenance — <?= htmlspecialchars($siteName) ?></title>
    <link rel="stylesheet" href="/assets/css/awan.css">
    <?php if (isset($theme)): ?><style><?= $theme->cssVariables() ?></style><?php endif ?>
    <script>(function(){var t=localStorage.getItem('awan-theme');var d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;if(t==='dark'||((!t)&&d)){document.documentElement.setAttribute('data-theme','dark');}})();</script>
</head>
<body class="page-error">
    <div class="error-wrap">
        <div class="error-code" style="font-size:48px">—</div>
        <h1><?= htmlspecialchars($settings->get('maintenance_title', 'Under Maintenance')) ?></h1>
        <p><?= htmlspecialchars($settings->get('maintenance_message', "We're currently performing scheduled maintenance. We'll be back shortly.")) ?></p>
        <a href="/login" class="btn btn-secondary" style="margin-top:16px">Admin Login</a>
    </div>
</body>
</html><?php
            exit;
        }
    }
}

// /sitemap.xml and /robots.txt — dynamic generators
if ($path === '/sitemap.xml') { require __DIR__ . '/sitemap.php'; exit; }
if ($path === '/robots.txt')  { require __DIR__ . '/robots.php';  exit; }

// Google Search Console HTML verification file (e.g. /googleXXXXXXX.html)
if (preg_match('/^\/google[a-f0-9]{5,}\.html$/i', $path)) {
    require_once __DIR__ . '/_bootstrap.php';
    $sc_content = $settings->get('google_sc_html_content', '');
    if ($sc_content) {
        header('Content-Type: text/html; charset=utf-8');
        echo $sc_content;
        exit;
    }
}

// Route all /api/* requests to REST API handler
if (str_starts_with($path, '/api/') || $path === '/api') {
    require __DIR__ . '/api/index.php';
    exit;
}

// ─── Admin routes (explicit, before file-based routing) ───────────────────────
if ($path === '/admin/setup')           { require __DIR__ . '/admin/setup.php';           exit; }
if ($path === '/admin/menus')           { require __DIR__ . '/admin/menus.php';           exit; }
if ($path === '/admin/email-templates') { require __DIR__ . '/admin/email-templates.php'; exit; }
if ($path === '/admin/email-logs')      { require __DIR__ . '/admin/email-logs.php';      exit; }
if ($path === '/admin/email-queue')     { require __DIR__ . '/admin/email-queue.php';     exit; }
if ($path === '/admin/newsletter')      { require __DIR__ . '/admin/newsletter.php';      exit; }
if ($path === '/admin/testimonials')         { require __DIR__ . '/admin/testimonials.php';         exit; }
if ($path === '/admin/media-picker-data')    { require __DIR__ . '/admin/media-picker-data.php';    exit; }
if ($path === '/admin/media-picker-upload')  { require __DIR__ . '/admin/media-picker-upload.php';  exit; }
if ($path === '/admin/files')                { require __DIR__ . '/admin/files.php';                exit; }
if ($path === '/admin/comments')             { require __DIR__ . '/admin/comments.php';             exit; }
if ($path === '/admin/homepage-sections')    { require __DIR__ . '/admin/homepage-sections.php';    exit; }
if ($path === '/admin/faq')                  { require __DIR__ . '/admin/faq.php';                  exit; }
if ($path === '/admin/shortcodes')           { require __DIR__ . '/admin/shortcodes.php';           exit; }
if ($path === '/admin/migrate-db')           { require __DIR__ . '/admin/migrate-db.php';           exit; }
if ($path === '/admin/languages')            { require __DIR__ . '/admin/languages.php';            exit; }
if ($path === '/admin/search')               { require __DIR__ . '/admin/search.php';                exit; }
if ($path === '/account/toggle-favourite')   { require __DIR__ . '/account/toggle-favourite.php';   exit; }
if ($path === '/account/write')              { require __DIR__ . '/account/write.php';               exit; }
if ($path === '/account/my-posts')           { require __DIR__ . '/account/my-posts.php';            exit; }
if ($path === '/account/favourites')         { require __DIR__ . '/account/favourites.php';          exit; }
if ($path === '/account/notifications')      { require __DIR__ . '/account/notifications.php';       exit; }

// ─── Search ───────────────────────────────────────────────────────────────────
if ($path === '/search') { require __DIR__ . '/search.php'; exit; }

// ─── RSS / Atom Feed ──────────────────────────────────────────────────────────
if ($path === '/feed' || $path === '/rss' || $path === '/feed.xml' || $path === '/rss.xml') {
    require __DIR__ . '/feed.php'; exit;
}

// ─── Alias routes (alternate URLs that map to existing PHP files) ──────────────
if ($path === '/request-a-tool')     { require __DIR__ . '/request-tool.php';  exit; }
if ($path === '/report-an-issue')    { require __DIR__ . '/report-issue.php';  exit; }
if ($path === '/privacy-policy')     { require __DIR__ . '/privacy.php';       exit; }
if ($path === '/terms-of-service')   { require __DIR__ . '/terms.php';         exit; }
if ($path === '/tools')              { header('Location: /plugins', true, 301); exit; }
if ($path === '/admin/dashboard')    { header('Location: /admin/', true, 301); exit; }
if ($path === '/admin/contact-submissions') { require __DIR__ . '/admin/contacts.php'; exit; }
if ($path === '/admin/issue-reports')       { require __DIR__ . '/admin/reports.php';  exit; }

// ─── Auth routes ──────────────────────────────────────────────────────────────
if ($path === '/login/2fa')           { require __DIR__ . '/login-2fa.php';       exit; }
if ($path === '/2fa')                 { require __DIR__ . '/2fa.php';             exit; }
if ($path === '/forgot-password')     { require __DIR__ . '/forgot-password.php'; exit; }
if ($path === '/reset-password')      { require __DIR__ . '/reset-password.php';  exit; }
if ($path === '/verify-email')        { require __DIR__ . '/verify-email.php';    exit; }
if ($path === '/resend-verification') { require __DIR__ . '/resend-verification.php'; exit; }
if ($path === '/auth/google')         { require __DIR__ . '/auth/google.php';     exit; }
if ($path === '/auth/google/callback') {
    require __DIR__ . '/auth/google/callback.php'; exit;
}

// Route /user/{username} to public profile page
if (preg_match('#^/user/([a-z0-9_.\-]+)$#i', $path, $m)) {
    $GLOBALS['_route_username'] = $m[1];
    require __DIR__ . '/user.php';
    exit;
}

// Route /blog/author/{username}
if (preg_match('#^/blog/author/([a-z0-9_.\-]+)$#i', $path, $m)) {
    $GLOBALS['_route_author_username'] = $m[1];
    require __DIR__ . '/blog/author.php';
    exit;
}

// Route /blog/tag/{slug}
if (preg_match('#^/blog/tag/([a-z0-9_\-]+)$#i', $path, $m)) {
    $GLOBALS['_route_blog_tag'] = $m[1];
    require __DIR__ . '/blog/tag.php';
    exit;
}

// Route /blog/{slug} to single blog post
if (preg_match('#^/blog/([a-z0-9_\-]+)$#i', $path, $m)) {
    $GLOBALS['_route_blog_slug'] = $m[1];
    if (file_exists(__DIR__ . '/blog/post.php')) {
        require __DIR__ . '/blog/post.php';
    } else {
        http_response_code(404);
        require_once __DIR__ . '/_bootstrap.php';
        echo renderError(404, 'Not Found', 'Blog post not found.');
    }
    exit;
}

// Route /blog/category/{slug}
if (preg_match('#^/blog/category/([a-z0-9_\-]+)$#i', $path, $m)) {
    $GLOBALS['_route_blog_category'] = $m[1];
    if (file_exists(__DIR__ . '/blog/category.php')) {
        require __DIR__ . '/blog/category.php';
    } else {
        require __DIR__ . '/blog/index.php';
    }
    exit;
}

// Route /blog → blog/index.php
if ($path === '/blog' || $path === '/blog/') {
    if (file_exists(__DIR__ . '/blog/index.php')) {
        require __DIR__ . '/blog/index.php';
    }
    exit;
}

// Previewer Toolkit metadata inspector API — returns JSON, runs after bootstrap for DB access
if ($path === '/plugins/previewer-toolkit/meta') {
    require_once __DIR__ . '/_bootstrap.php';
    require __DIR__ . '/plugins/previewer-toolkit/meta.php';
    exit;
}

// Route /plugins to plugin list page
if ($path === '/plugins/rate')               { require __DIR__ . '/plugins/rate.php';               exit; }
if ($path === '/plugins' || $path === '/plugins/') {
    require_once __DIR__ . '/_bootstrap.php';
    require __DIR__ . '/plugins/index.php';
    exit;
}

// Serve real static files directly (non-PHP, non-directory)
if ($path !== '/' && file_exists($file) && !is_dir($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false; // PHP built-in server handles static files; Apache handles via .htaccess
}

// Map directory requests to index.php in that directory
if (is_dir($file)) {
    $index = rtrim($file, '/') . '/index.php';
    if (file_exists($index)) {
        // Plugin directory: validate the plugin is active before serving
        if (preg_match('#^/plugins/([a-z0-9_\-]+)(/|$)#i', $path, $m)) {
            $slug = $m[1];
            if ($slug !== '_sdk' && $slug !== 'index') {
                require_once __DIR__ . '/_bootstrap.php';
                require_once __DIR__ . '/_core/Plugin.php';
                if (!Plugin::isActive($db, $slug)) {
                    http_response_code(404);
                    require __DIR__ . '/_bootstrap.php';
                    echo renderError(404, 'Plugin Not Available', "The plugin <strong>" . htmlspecialchars($slug) . "</strong> is not installed or is currently inactive.");
                    exit;
                }
            }
        }
        require $index;
        return true;
    }
    // Special: /account → account/dashboard.php
    $dashboard = rtrim($file, '/') . '/dashboard.php';
    if (file_exists($dashboard)) {
        require $dashboard;
        return true;
    }
}

// Serve exact .php file match
if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    require $file;
    return true;
}

// Clean URL: try appending .php  (e.g. /login → login.php)
if (file_exists($file . '.php')) {
    require $file . '.php';
    return true;
}

// ─── Dynamic CMS page lookup ──────────────────────────────────────────────────
// No physical file found — check the pages table for a published page with this slug
require_once __DIR__ . '/_bootstrap.php';

$slug = ltrim($path, '/');
// Only attempt single-segment slugs (no sub-paths)
if ($slug !== '' && !str_contains($slug, '/') && !str_contains($slug, '.')) {
    $page = $db->fetch(
        "SELECT * FROM pages WHERE slug = ? AND status = 'published' LIMIT 1",
        [$slug]
    );

    if ($page) {
        // Track page view
        if ($settings->get('analytics_enabled', '1') === '1' && !isBot()) {
            try {
                $db->insert('analytics_events', [
                    'event'      => 'page_view',
                    'path'       => '/' . $slug,
                    'user_id'    => $auth->id(),
                    'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (Exception $e) {}
        }

        $seoTitle = $page['seo_title'] ?: $page['title'];
        $seoDesc  = $page['seo_desc'] ?: '';

        ob_start();
        ?>
        <div class="cms-page">
            <div class="page-hero">
                <div class="page-hero-inner">
                    <h1><?= e($page['title']) ?></h1>
                    <p style="font-size:13px;color:var(--color-text-muted);margin-top:8px">Last updated <?= fdate($page['updated_at'] ?? $page['created_at']) ?></p>
                </div>
            </div>
            <div class="front-container" style="padding-top:40px;padding-bottom:40px">
                <div class="cms-content" style="max-width:800px;margin:0 auto">
                    <?= $page['content'] ?>
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        require THEMES_PATH . '/default/templates/layout.php';
        render_page($seoTitle, $content, ['description' => $seoDesc]);
        exit;
    }
}

// ─── 404 ──────────────────────────────────────────────────────────────────────
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Page Not Found</title>
    <link rel="stylesheet" href="/assets/css/awan.css">
    <?php if (isset($theme)): ?><style><?= $theme->cssVariables() ?></style><?php endif ?>
    <script>(function(){var t=localStorage.getItem('awan-theme');var d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;if(t==='dark'||((!t)&&d)){document.documentElement.setAttribute('data-theme','dark');}})();</script>
</head>
<body class="page-error">
    <div class="error-wrap">
        <div class="error-code">404</div>
        <h1>Page Not Found</h1>
        <p>The page you're looking for doesn't exist or has been moved.</p>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
            <a href="/" class="btn btn-primary">Go Home</a>
            <a href="/contact" class="btn btn-secondary">Contact Us</a>
        </div>
    </div>
</body>
</html>
