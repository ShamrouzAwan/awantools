<?php
defined('AWAN') or die('Direct access denied.');

/**
 * Shortcode — lightweight [shortcode] processor for AWAN Platform.
 *
 * Usage:
 *   Shortcode::register('my_tag', function(array $attrs): string { return '...'; });
 *   echo Shortcode::process($content);
 */
class Shortcode
{
    private static array $handlers = [];
    private static bool $initialized = false;

    /**
     * Register a shortcode handler.
     * @param string $tag     Shortcode tag name (e.g. 'site_name')
     * @param callable $fn    Receives attrs array, returns HTML string
     */
    public static function register(string $tag, callable $fn): void
    {
        self::$handlers[strtolower(trim($tag))] = $fn;
    }

    /**
     * Process all registered shortcodes in $content.
     */
    public static function process(string $content): string
    {
        if (empty(self::$handlers)) return $content;

        // Match [tag] and [tag attr="value" ...] and [tag /] and [/tag]
        return preg_replace_callback(
            '/\[([a-z_][a-z0-9_]*)([^\]]*)\]/i',
            [self::class, 'dispatch'],
            $content
        );
    }

    private static function dispatch(array $m): string
    {
        $tag   = strtolower($m[1]);
        $attrs = self::parseAttrs($m[2] ?? '');
        if (!isset(self::$handlers[$tag])) {
            return $m[0]; // Unknown tag — leave as-is
        }
        try {
            return (string)(self::$handlers[$tag])($attrs);
        } catch (Throwable $e) {
            return '';
        }
    }

    private static function parseAttrs(string $str): array
    {
        $attrs = [];
        // key="value" or key='value' — fixed regex to properly match both quote types
        preg_match_all('/([a-z_][a-z0-9_]*)\s*=\s*["\']([^"\']*)["\']/', $str, $pairs, PREG_SET_ORDER);
        foreach ($pairs as $p) {
            $attrs[$p[1]] = $p[2];
        }
        return $attrs;
    }

    /**
     * Register all built-in platform shortcodes.
     * Call once after bootstrap.
     */
    public static function registerDefaults(): void
    {
        if (self::$initialized) return;
        self::$initialized = true;

        global $settings, $db;

        self::register('site_name', function() use ($settings): string {
            return htmlspecialchars($settings->get('site_name', 'Awan Tools'), ENT_QUOTES, 'UTF-8');
        });

        self::register('site_url', function() use ($settings): string {
            $url = rtrim($settings->get('site_url', ''), '/');
            return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        });

        self::register('year', function(): string {
            return date('Y');
        });

        self::register('current_year', function(): string {
            return date('Y');
        });

        self::register('tagline', function() use ($settings): string {
            return htmlspecialchars($settings->get('site_tagline', ''), ENT_QUOTES, 'UTF-8');
        });

        self::register('contact_link', function(array $attrs) use ($settings): string {
            $text = $attrs['text'] ?? 'Contact Us';
            return '<a href="/contact">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</a>';
        });

        self::register('plugins_count', function() use ($db): string {
            try {
                $row = $db->fetch("SELECT COALESCE(SUM(offered),0) AS n FROM plugins WHERE status = 'active'");
                return (string)((int)($row['n'] ?? 0));
            } catch (Throwable $e) { return '0'; }
        });

        self::register('tool_count', function() use ($db): string {
            try {
                $row = $db->fetch("SELECT COALESCE(SUM(offered),0) AS n FROM plugins WHERE status = 'active'");
                return (string)((int)($row['n'] ?? 0));
            } catch (Throwable $e) { return '0'; }
        });

        self::register('user_count', function() use ($db): string {
            try {
                return (string)$db->count('users');
            } catch (Throwable $e) { return '0'; }
        });

        self::register('button', function(array $attrs): string {
            $text   = htmlspecialchars($attrs['text']  ?? 'Click Here', ENT_QUOTES, 'UTF-8');
            $url    = htmlspecialchars($attrs['url']   ?? '#',           ENT_QUOTES, 'UTF-8');
            $style  = $attrs['style'] ?? 'primary';
            return '<a href="' . $url . '" class="btn btn-' . htmlspecialchars($style, ENT_QUOTES) . '" style="display:inline-block">' . $text . '</a>';
        });

        self::register('alert', function(array $attrs): string {
            $type = htmlspecialchars($attrs['type'] ?? 'info', ENT_QUOTES, 'UTF-8');
            $text = htmlspecialchars($attrs['text'] ?? '', ENT_QUOTES, 'UTF-8');
            return '<div class="alert alert-' . $type . '" style="margin:12px 0">' . $text . '</div>';
        });
    }
}