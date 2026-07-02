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

$pages   = $db->fetchAll("SELECT slug, updated_at, created_at FROM pages WHERE status='published' ORDER BY updated_at DESC");
$users   = $incUsers ? $db->fetchAll("SELECT username, updated_at FROM users WHERE status='active' ORDER BY username ASC") : [];
$plugins = $db->fetchAll("SELECT slug, installed_at FROM plugins WHERE status='active' ORDER BY slug ASC");

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

// Active plugin pages + tag/keyword discovery URLs from plugin.json
foreach ($plugins as $plugin) {
    $lastmod = $plugin['installed_at'] ? date('Y-m-d', strtotime($plugin['installed_at'])) : date('Y-m-d');

    // Plugin index page
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($base . '/plugins/' . $plugin['slug'] . '/', ENT_XML1) . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>" . e($changeFreq) . "</changefreq>\n";
    echo "    <priority>" . e($priPages) . "</priority>\n";
    echo "  </url>\n";

    // Read plugin.json to get tags and keywords
    $manifestPath = __DIR__ . '/plugins/' . $plugin['slug'] . '/plugin.json';
    if (!file_exists($manifestPath)) continue;

    $manifest = json_decode(file_get_contents($manifestPath), true);
    if (!is_array($manifest)) continue;

    // Collect tags (URL-slug formatted, e.g. "base64-encoder") and
    // keywords (raw terms, e.g. "base64") — deduplicated, sorted
    $tags     = array_filter((array)($manifest['tags']     ?? []), 'is_string');
    $keywords = array_filter((array)($manifest['keywords'] ?? []), 'is_string');

    // Normalise keywords into URL slugs (lowercase, spaces → hyphens)
    $kwSlugs = array_map(fn($k) => strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($k))), $keywords);

    // Merge, deduplicate, drop empties, sort
    $allTerms = array_values(array_unique(array_filter(array_merge($tags, $kwSlugs))));
    sort($allTerms);

    foreach ($allTerms as $term) {
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($base . '/plugins/' . $plugin['slug'] . '/?t=' . rawurlencode($term), ENT_XML1) . "</loc>\n";
        echo "    <lastmod>" . $lastmod . "</lastmod>\n";
        echo "    <changefreq>" . e($changeFreq) . "</changefreq>\n";
        echo "    <priority>0.6</priority>\n";
        echo "  </url>\n";
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
