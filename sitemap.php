<?php
defined('AWAN') or define('AWAN', true);
require_once __DIR__ . '/_bootstrap.php';

if ($settings->get('sitemap_enabled', '1') !== '1') {
    http_response_code(404);
    exit('Sitemap is disabled.');
}

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$siteUrl    = rtrim($settings->get('seo_canonical_url') ?: $settings->get('site_url', ''), '/');
$changeFreq = $settings->get('sitemap_change_freq', 'weekly');
$priHome    = $settings->get('sitemap_priority_home', '1.0');
$priPages   = $settings->get('sitemap_priority_pages', '0.8');
$incUsers   = $settings->get('sitemap_include_users', '0') === '1';

$pages      = $db->fetchAll("SELECT slug, updated_at, created_at FROM pages WHERE status='published' ORDER BY updated_at DESC");
$users      = $incUsers ? $db->fetchAll("SELECT username, updated_at FROM users WHERE status='active' ORDER BY username ASC") : [];
$plugins    = $db->fetchAll("SELECT slug, installed_at FROM plugins WHERE status='active' ORDER BY slug ASC");
$toolRegistry = (file_exists(__DIR__ . '/_data/plugin_tools.php'))
    ? require __DIR__ . '/_data/plugin_tools.php'
    : [];

$base = $siteUrl ?: 'http://localhost';

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
echo '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
echo '        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n\n";

// Homepage
echo "  <url>\n";
echo "    <loc>" . htmlspecialchars($base . '/', ENT_XML1) . "</loc>\n";
echo "    <changefreq>daily</changefreq>\n";
echo "    <priority>" . e($priHome) . "</priority>\n";
echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
echo "  </url>\n";

// Published CMS pages
foreach ($pages as $page) {
    $lastmod = $page['updated_at'] ?? $page['created_at'] ?? null;
    $lastmod = $lastmod ? date('Y-m-d', strtotime($lastmod)) : date('Y-m-d');
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($base . '/' . $page['slug'], ENT_XML1) . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>" . e($changeFreq) . "</changefreq>\n";
    echo "    <priority>" . e($priPages) . "</priority>\n";
    echo "  </url>\n";
}

// Active plugin pages + individual tool pages
foreach ($plugins as $plugin) {
    $lastmod = $plugin['installed_at'] ? date('Y-m-d', strtotime($plugin['installed_at'])) : date('Y-m-d');
    // Plugin index page
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($base . '/plugins/' . $plugin['slug'] . '/', ENT_XML1) . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>" . e($changeFreq) . "</changefreq>\n";
    echo "    <priority>" . e($priPages) . "</priority>\n";
    echo "  </url>\n";
    // Individual tool pages for plugins that have a tool registry
    if (!empty($toolRegistry[$plugin['slug']])) {
        foreach ($toolRegistry[$plugin['slug']] as $tool) {
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($base . '/plugins/' . $plugin['slug'] . '/?tool=' . rawurlencode($tool[0]), ENT_XML1) . "</loc>\n";
            echo "    <lastmod>" . $lastmod . "</lastmod>\n";
            echo "    <changefreq>" . e($changeFreq) . "</changefreq>\n";
            echo "    <priority>0.7</priority>\n";
            echo "  </url>\n";
        }
    }
}

// User profiles
foreach ($users as $user) {
    $lastmod = $user['updated_at'] ? date('Y-m-d', strtotime($user['updated_at'])) : date('Y-m-d');
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($base . '/user/' . $user['username'], ENT_XML1) . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.5</priority>\n";
    echo "  </url>\n";
}

echo "\n</urlset>\n";
