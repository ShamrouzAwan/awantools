<?php
defined('AWAN') or define('AWAN', true);
require_once __DIR__ . '/_bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=86400');

$allowIndex    = $settings->get('seo_robots_index', '1') === '1';
$disallowAdmin = $settings->get('robots_disallow_admin', '1') === '1';
$disallowApi   = $settings->get('robots_disallow_api', '1') === '1';
$customRules   = trim($settings->get('robots_custom_rules', ''));
$siteUrl       = rtrim($settings->get('seo_canonical_url') ?: $settings->get('site_url', ''), '/');
$sitemapEnabled = $settings->get('sitemap_enabled', '1') === '1';

echo "User-agent: *\n";

if (!$allowIndex) {
    echo "Disallow: /\n";
} else {
    echo "Allow: /\n\n";
    if ($disallowAdmin) echo "Disallow: /admin/\n";
    if ($disallowApi)   echo "Disallow: /api/\n";
    echo "Disallow: /storage/\n";
    echo "Disallow: /logout\n";
    echo "Disallow: /account/\n";
}

if ($customRules) {
    echo "\n" . $customRules . "\n";
}

if ($sitemapEnabled && $siteUrl) {
    echo "\nSitemap: {$siteUrl}/sitemap.xml\n";
}
