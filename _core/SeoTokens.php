<?php
defined('AWAN') or die('Direct access denied.');

/**
 * SeoTokens — resolves {{token}} placeholders inside SEO strings (meta
 * descriptions, OG titles, and especially Previewer Toolkit OG image URLs)
 * into real, live site/entity values at render time.
 *
 * Used by render_page() (themes/default/templates/layout.php) so that any
 * admin-entered SEO field — including og_image URLs built with the
 * Previewer Toolkit builder in Admin → Page SEO Manager — can reference
 * live data instead of being hardcoded.
 */
class SeoTokens {
    private static ?array $globalCache = null;

    /**
     * Replace every {{token}} found in $tpl using $context first, then
     * falling back to global site-wide tokens. Unknown tokens are left
     * untouched (so partially-configured strings still degrade gracefully).
     */
    public static function resolve(string $tpl, array $context = []): string {
        if ($tpl === '' || !str_contains($tpl, '{{')) return $tpl;
        $tokens = array_merge(self::globalTokens(), $context);
        return preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function ($m) use ($tokens) {
            $key = strtolower($m[1]);
            return array_key_exists($key, $tokens) ? (string)$tokens[$key] : $m[0];
        }, $tpl);
    }

    /** Site-wide tokens available on every page. Computed once per request. */
    public static function globalTokens(): array {
        if (self::$globalCache !== null) return self::$globalCache;
        global $db, $settings;

        $totalPlugins = 0;
        $totalTools   = 0;
        $totalUsers   = 0;
        try { $totalPlugins = (int)($db->count('plugins', "status='active'") ?? 0); } catch (Throwable $e) {}
        try {
            $totalTools = (int)($db->fetchValue("SELECT COALESCE(SUM(offered),0) FROM plugins WHERE status='active'") ?? 0);
        } catch (Throwable $e) {}
        try { $totalUsers = (int)($db->count('users') ?? 0); } catch (Throwable $e) {}

        $siteName = $settings instanceof Settings ? $settings->siteName() : 'Site';
        $tagline  = $settings instanceof Settings ? $settings->siteTagline() : '';
        $siteUrl  = $settings instanceof Settings ? rtrim($settings->get('seo_canonical_url') ?: $settings->get('site_url', ''), '/') : '';

        return self::$globalCache = [
            'site_name'      => $siteName,
            'site_tagline'   => $tagline,
            'site_url'       => $siteUrl,
            'total_plugins'  => $totalPlugins,
            'total_tools'    => $totalTools,
            'total_users'    => $totalUsers,
            'developer_name' => $settings instanceof Settings ? $settings->get('developer_name', '') : '',
            'current_date'   => date('F j, Y'),
            'current_year'   => date('Y'),
            'current_month'  => date('F'),
        ];
    }

    /**
     * Describes every insertable token, grouped, for the admin builder UI.
     * $scope narrows which contextual groups are shown (e.g. 'plugin', 'blog', 'page', 'static').
     */
    public static function catalog(string $scope = ''): array {
        $groups = [
            'Site' => [
                'site_name'      => 'Site name',
                'site_tagline'   => 'Site tagline',
                'site_url'       => 'Site base URL',
                'total_plugins'  => 'Total active plugins',
                'total_tools'    => 'Total tools offered (sum)',
                'total_users'    => 'Total registered users',
                'developer_name' => 'Developer / author name',
                'current_date'   => 'Current date',
                'current_year'   => 'Current year',
                'current_month'  => 'Current month',
            ],
        ];
        if ($scope === '' || $scope === 'plugin') {
            $groups['Plugin'] = [
                'plugin_name'        => 'Plugin name',
                'plugin_description' => 'Plugin description',
                'plugin_slug'        => 'Plugin slug',
                'plugin_author'      => 'Plugin author',
                'tool_count'         => 'Tools offered by this plugin',
            ];
        }
        if ($scope === '' || $scope === 'blog') {
            $groups['Blog Post'] = [
                'blog_title'   => 'Post title',
                'blog_excerpt' => 'Post excerpt',
                'author_name'  => 'Post author name',
            ];
        }
        if ($scope === '' || $scope === 'page') {
            $groups['CMS Page'] = [
                'page_title' => 'Page title',
                'page_url'   => 'Page URL path',
            ];
        }
        if ($scope === '' || $scope === 'static') {
            $groups['Page'] = [
                'page_title' => 'Page title',
                'page_url'   => 'Page URL path',
            ];
        }
        return $groups;
    }
}
