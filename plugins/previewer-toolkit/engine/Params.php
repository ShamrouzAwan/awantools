<?php
defined('AWAN') or die('Direct access denied.');

class PT_Params
{
    const DEFAULTS = [
        'category'          => 'og',
        'template'          => 'github_dark',
        'width'             => 1200,
        'height'            => 630,
        'format'            => 'svg',
        'heading'           => 'Developer Toolkit',
        'subheading'        => '',
        'description'       => '200+ developer tools to supercharge your workflow.',
        'footer'            => 'awantools.site',
        'badge'             => '',
        'icon'              => 'code',
        'font'              => 'Inter',
        'font_size'         => 0,
        'bg_color'          => '0d1117',
        'heading_color'     => 'ffffff',
        'description_color' => '94a3b8',
        'accent_color'      => '22c55e',
        'border_radius'     => 20,
        'padding'           => 60,
        'quality'           => 90,
        'transparent'       => false,
        'theme'             => 'dark',
        'align'             => 'left',
        'gradient'          => '',
        'watermark'         => '',
        'logo'              => '',
        'line1'             => '',
        'line2'             => '',
        'line3'             => '',
        'line4'             => '',
        'stat1_label'       => 'Stars',
        'stat1_value'       => '1.2k',
        'stat2_label'       => 'Forks',
        'stat2_value'       => '234',
        'stat3_label'       => 'Issues',
        'stat3_value'       => '12',
        'lang'              => 'JavaScript',
        'lang_color'        => 'f7df1e',
        'url'               => 'https://awantools.site',
        'username'          => 'shamrouzawan',
        'tag'               => 'v1.0.0',
        'prompt'            => '$ npm install awan-tools',
        'code'              => "const x = 'Hello World';\nconsole.log(x);",
        'language'          => 'javascript',
        // Awan Tools category
        'plugin_name'       => 'Awan Tools',
        'badge1'            => 'Lightweight',
        'badge1_icon'       => 'bolt',
        'badge1_sub'        => 'Blazing Fast',
        'badge2'            => 'Secure',
        'badge2_icon'       => 'shield-check',
        'badge2_sub'        => '100% Safe',
        'badge3'            => 'Powerful',
        'badge3_icon'       => 'rocket',
        'badge3_sub'        => 'Feature Rich',
        'stat1_icon'        => 'users',
        'stat2_icon'        => 'star',
        'stat3_icon'        => 'download',
    ];

    const SAFE_FONTS = [
        'Inter', 'Roboto', 'Open Sans', 'Lato', 'Poppins', 'Montserrat',
        'Raleway', 'Source Code Pro', 'JetBrains Mono', 'Fira Code',
        'Nunito', 'Ubuntu', 'Playfair Display', 'Merriweather',
        'Georgia', 'Arial', 'system-ui',
    ];

    static function parse(array $raw): array
    {
        $p = self::DEFAULTS;

        $p['category'] = self::slug($raw['category'] ?? $p['category'], 'og');
        $p['template'] = self::slug($raw['template'] ?? $p['template'], 'github_dark');

        $p['width']  = self::dim($raw['width']  ?? $p['width'],  1200, 3000);
        $p['height'] = self::dim($raw['height'] ?? $p['height'], 630,  3000);

        $fmt = strtolower(trim($raw['format'] ?? 'svg'));
        $fmt = $fmt === 'jpeg' ? 'jpg' : $fmt;
        $p['format'] = in_array($fmt, ['svg','png','jpg','webp'], true) ? $fmt : 'svg';

        foreach (['heading','subheading','description','footer','badge','watermark',
                  'line1','line2','line3','line4',
                  'stat1_label','stat1_value','stat2_label','stat2_value','stat3_label','stat3_value',
                  'lang','url','username','tag','prompt','code','language',
                  'plugin_name',
                  'badge1','badge1_icon','badge1_sub',
                  'badge2','badge2_icon','badge2_sub',
                  'badge3','badge3_icon','badge3_sub',
                  'stat1_icon','stat2_icon','stat3_icon'] as $k) {
            $p[$k] = self::text($raw[$k] ?? $p[$k]);
        }

        foreach (['bg_color','heading_color','description_color','accent_color','lang_color'] as $k) {
            $p[$k] = self::color($raw[$k] ?? $p[$k], ltrim($p[$k], '#'));
        }

        $p['icon']  = self::icon($raw['icon']  ?? $p['icon']);
        $fnt = $raw['font'] ?? 'Inter';
        $p['font'] = in_array($fnt, self::SAFE_FONTS, true) ? $fnt : 'Inter';

        $p['border_radius'] = max(0, min(120, (int)($raw['radius'] ?? $raw['border_radius'] ?? $p['border_radius'])));
        $p['padding']       = max(0, min(200, (int)($raw['padding']  ?? $p['padding'])));
        $p['quality']       = max(1, min(100, (int)($raw['quality']  ?? $p['quality'])));
        $p['font_size']     = max(0, min(200, (int)($raw['font_size']?? $p['font_size'])));

        $p['transparent'] = in_array($raw['transparent'] ?? '', ['1','true','yes'], true);
        $p['theme']       = in_array($raw['theme'] ?? 'dark', ['dark','light'], true) ? ($raw['theme'] ?? 'dark') : 'dark';
        $p['align']       = in_array($raw['align'] ?? 'left', ['left','center','right'], true) ? ($raw['align'] ?? 'left') : 'left';

        $logo = trim($raw['logo'] ?? '');
        $p['logo'] = filter_var($logo, FILTER_VALIDATE_URL) ? $logo : '';

        return $p;
    }

    static function slug(string $s, string $default): string
    {
        $s = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($s)));
        return $s ?: $default;
    }

    static function dim($v, int $default, int $max = 3000): int
    {
        $v = (int)$v;
        return ($v >= 16 && $v <= $max) ? $v : $default;
    }

    static function text(string $s): string
    {
        return substr(strip_tags($s), 0, 500);
    }

    static function color(string $s, string $default): string
    {
        $s = preg_replace('/[^0-9a-fA-F]/', '', ltrim(trim($s), '#'));
        if (strlen($s) === 3) {
            $s = $s[0].$s[0].$s[1].$s[1].$s[2].$s[2];
        }
        return (strlen($s) === 6) ? strtolower($s) : strtolower(preg_replace('/[^0-9a-fA-F]/', '', $default));
    }

    static function icon(string $s): string
    {
        return preg_replace('/[^a-z0-9-]/', '', strtolower(trim($s))) ?: 'code';
    }

    static function hex(string $c): string
    {
        return '#' . self::color($c, '000000');
    }
}
