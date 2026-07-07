<?php
/**
 * Previewer Toolkit — Image Render Endpoint
 * Served at: /plugins/previewer-toolkit/render
 * MUST run before _bootstrap.php — routed directly in _router.php
 */

// ── Safety: suppress all output that could corrupt image binary ───────────────
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

// ── Remove session/no-cache headers that social crawlers reject ───────────────
header_remove('Set-Cookie');
header_remove('Pragma');
header_remove('Expires');
header_remove('Cache-Control');
header_remove('X-Powered-By');

// ── Font paths ────────────────────────────────────────────────────────────────
define('PT_DIR',       __DIR__);
define('PT_FONT_REG',  PT_DIR . '/assets/fonts/Inter-Regular.ttf');
define('PT_FONT_BOLD', PT_DIR . '/assets/fonts/Inter-Bold.ttf');
define('PT_FONT_FA',   PT_DIR . '/assets/fonts/fa-solid-900.ttf');
define('PT_CACHE_DIR', PT_DIR . '/cache');

// ── Parameter validation & defaults ──────────────────────────────────────────
function pt_str(string $key, string $default = '', int $max = 200): string {
    $v = trim($_GET[$key] ?? $default);
    return substr(strip_tags($v), 0, $max);
}
function pt_int(string $key, int $default, int $min, int $max): int {
    $v = intval($_GET[$key] ?? $default);
    return max($min, min($max, $v));
}
function pt_hex(string $key, string $default): string {
    $v = preg_replace('/[^0-9a-fA-F]/', '', $_GET[$key] ?? $default);
    if (strlen($v) === 3) {
        $v = $v[0].$v[0].$v[1].$v[1].$v[2].$v[2];
    }
    return strlen($v) === 6 ? $v : $default;
}

$p = [
    'category'    => preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['category'] ?? 'og')),
    'template'    => preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['template'] ?? 'github_dark')),
    'format'      => in_array($_GET['format'] ?? 'png', ['png','jpg','jpeg','webp']) ? ($_GET['format'] ?? 'png') : 'png',
    'width'       => pt_int('width',  1200, 100, 2400),
    'height'      => pt_int('height',  630, 100, 2400),
    'heading'     => pt_str('heading', 'Heading'),
    'subheading'  => pt_str('subheading', ''),
    'description' => pt_str('description', ''),
    'badge'       => pt_str('badge', ''),
    'footer'      => pt_str('footer', ''),
    'website'     => pt_str('website', ''),
    'author'      => pt_str('author', ''),
    'date'        => pt_str('date', ''),
    'category_label' => pt_str('category_label', ''),
    'icon'        => pt_str('icon', 'code', 40),
    'bg_color'    => pt_hex('bg_color',    '0d1117'),
    'fg_color'    => pt_hex('fg_color',    'ffffff'),
    'accent_color'=> pt_hex('accent_color','3b82f6'),
    'heading_color'=> pt_hex('heading_color', ''),
    'description_color' => pt_hex('description_color', ''),
    'font_size'   => pt_int('font_size', 48, 12, 96),
    'radius'      => pt_int('radius', 16, 0, 60),
    'padding'     => pt_int('padding', 60, 10, 120),
    'line1'       => pt_str('line1', '$ echo "Hello World"', 120),
    'line2'       => pt_str('line2', ''),
    'line3'       => pt_str('line3', ''),
    'line4'       => pt_str('line4', ''),
    'code'        => pt_str('code', 'function hello() {\n  return "world";\n}', 500),
    'lang'        => pt_str('lang', 'js', 20),
    'filename'    => pt_str('filename', 'index.js', 60),
    'url_bar'     => pt_str('url_bar', 'https://example.com', 100),
    'stars'       => pt_str('stars', '1.2k', 20),
    'forks'       => pt_str('forks', '234', 20),
    'version'     => pt_str('version', 'v1.0.0', 20),
    'username'    => pt_str('username', '@developer', 60),
    'role'        => pt_str('role', 'Full-Stack Developer', 80),
    'stat1_label' => pt_str('stat1_label', 'Posts', 30),
    'stat1_value' => pt_str('stat1_value', '128', 20),
    'stat2_label' => pt_str('stat2_label', 'Followers', 30),
    'stat2_value' => pt_str('stat2_value', '4.2k', 20),
    'stat3_label' => pt_str('stat3_label', 'Stars', 30),
    'stat3_value' => pt_str('stat3_value', '892', 20),
    'metric1'     => pt_str('metric1', '24,891', 20),
    'metric1_label' => pt_str('metric1_label', 'Total Users', 30),
    'metric2'     => pt_str('metric2', '+12.4%', 20),
    'metric2_label' => pt_str('metric2_label', 'Growth', 30),
    'metric3'     => pt_str('metric3', '$8,240', 20),
    'metric3_label' => pt_str('metric3_label', 'Revenue', 30),
    'dark'        => isset($_GET['dark']) ? true : false,
    'watermark'   => pt_str('watermark', '', 60),
];

// ── ETag caching ─────────────────────────────────────────────────────────────
$cacheKey = md5(serialize($p));
$etag = '"pt-' . $cacheKey . '"';
$maxAge = 86400; // 24h

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    ob_end_clean();
    http_response_code(304);
    exit;
}

// ── FA icon name → codepoint map ─────────────────────────────────────────────
function pt_fa_codepoint(string $name): int {
    static $map = [
        'code' => 0xf121, 'globe' => 0xf0ac, 'user' => 0xf007,
        'database' => 0xf1c0, 'server' => 0xf233, 'cloud' => 0xf0c2,
        'terminal' => 0xf120, 'star' => 0xf005, 'rocket' => 0xf135,
        'bolt' => 0xf0e7, 'fire' => 0xf06d, 'gear' => 0xf013,
        'cog' => 0xf013, 'lock' => 0xf023, 'shield' => 0xf132,
        'chart-bar' => 0xf080, 'chart-line' => 0xf201,
        'dashboard' => 0xf0e4, 'tachometer-alt' => 0xf3fd,
        'puzzle-piece' => 0xf12e, 'wrench' => 0xf0ad,
        'book' => 0xf02d, 'file-code' => 0xf1c9,
        'address-card' => 0xf2bb, 'image' => 0xf03e,
        'camera' => 0xf030, 'search' => 0xf002,
        'magnifying-glass' => 0xf002, 'check' => 0xf00c,
        'heart' => 0xf004, 'envelope' => 0xf0e0,
        'home' => 0xf015, 'arrow-right' => 0xf061,
        'download' => 0xf019, 'upload' => 0xf093,
        'link' => 0xf0c1, 'tag' => 0xf02b,
        'calendar' => 0xf073, 'clock' => 0xf017,
        'folder' => 0xf07b, 'paint-brush' => 0xf1fc,
        'mobile' => 0xf10b, 'laptop' => 0xf109,
        'desktop' => 0xf108, 'wifi' => 0xf1eb,
        'plug' => 0xf1e6, 'api' => 0xf121,
        'github' => 0xf09b, 'git-alt' => 0xf841,
        'cube' => 0xf1b2, 'cubes' => 0xf1b3,
        'layer-group' => 0xf5fd, 'sitemap' => 0xf0e8,
        'trophy' => 0xf091, 'medal' => 0xf5a2,
        'crown' => 0xf521, 'gem' => 0xf3a5,
        'microchip' => 0xf2db, 'cpu' => 0xf2db,
        'network-wired' => 0xf6ff, 'broadcast-tower' => 0xf519,
        'tools' => 0xf7d9, 'hammer' => 0xf6e3,
        'magic' => 0xf0d0, 'wand-magic-sparkles' => 0xe2ca,
        'robot' => 0xf544, 'brain' => 0xf5dc,
        'infinity' => 0xf534, 'atom' => 0xf5d2,
    ];
    return $map[$name] ?? 0xf121; // default: code icon
}

function pt_unicode_char(int $cp): string {
    if ($cp < 0x80) return chr($cp);
    if ($cp < 0x800) return chr(0xC0 | ($cp >> 6)) . chr(0x80 | ($cp & 0x3F));
    if ($cp < 0x10000) return chr(0xE0 | ($cp >> 12)) . chr(0x80 | (($cp >> 6) & 0x3F)) . chr(0x80 | ($cp & 0x3F));
    return chr(0xF0 | ($cp >> 18)) . chr(0x80 | (($cp >> 12) & 0x3F)) . chr(0x80 | (($cp >> 6) & 0x3F)) . chr(0x80 | ($cp & 0x3F));
}

// ── Shared drawing utilities ──────────────────────────────────────────────────
function pt_hex2rgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
}

function pt_color(GdImage $im, string $hex, int $alpha = 0): int {
    [$r,$g,$b] = pt_hex2rgb($hex);
    return $alpha > 0
        ? imagecolorallocatealpha($im, $r, $g, $b, min(127, $alpha))
        : imagecolorallocate($im, $r, $g, $b);
}

function pt_color_darken(GdImage $im, string $hex, float $factor = 0.7): int {
    [$r,$g,$b] = pt_hex2rgb($hex);
    return imagecolorallocate($im, (int)($r*$factor), (int)($g*$factor), (int)($b*$factor));
}

function pt_color_lighten(GdImage $im, string $hex, float $factor = 1.3): int {
    [$r,$g,$b] = pt_hex2rgb($hex);
    return imagecolorallocate($im, min(255,(int)($r*$factor)), min(255,(int)($g*$factor)), min(255,(int)($b*$factor)));
}

function pt_gradient_v(GdImage $im, int $x, int $y, int $w, int $h, string $c1, string $c2): void {
    [$r1,$g1,$b1] = pt_hex2rgb($c1);
    [$r2,$g2,$b2] = pt_hex2rgb($c2);
    for ($i = 0; $i < $h; $i++) {
        $t = $h > 1 ? $i / ($h - 1) : 0;
        $color = imagecolorallocate($im,
            (int)($r1 + ($r2-$r1)*$t),
            (int)($g1 + ($g2-$g1)*$t),
            (int)($b1 + ($b2-$b1)*$t)
        );
        imagefilledrectangle($im, $x, $y+$i, $x+$w-1, $y+$i, $color);
    }
}

function pt_gradient_h(GdImage $im, int $x, int $y, int $w, int $h, string $c1, string $c2): void {
    [$r1,$g1,$b1] = pt_hex2rgb($c1);
    [$r2,$g2,$b2] = pt_hex2rgb($c2);
    for ($i = 0; $i < $w; $i++) {
        $t = $w > 1 ? $i / ($w - 1) : 0;
        $color = imagecolorallocate($im,
            (int)($r1 + ($r2-$r1)*$t),
            (int)($g1 + ($g2-$g1)*$t),
            (int)($b1 + ($b2-$b1)*$t)
        );
        imagefilledrectangle($im, $x+$i, $y, $x+$i, $y+$h-1, $color);
    }
}

function pt_gradient_diag(GdImage $im, int $x, int $y, int $w, int $h, string $c1, string $c2): void {
    [$r1,$g1,$b1] = pt_hex2rgb($c1);
    [$r2,$g2,$b2] = pt_hex2rgb($c2);
    for ($i = 0; $i < $h; $i++) {
        $t = $h > 1 ? $i / ($h - 1) : 0;
        for ($j = 0; $j < $w; $j++) {
            $tj = $w > 1 ? $j / ($w - 1) : 0;
            $tt = ($t + $tj) / 2;
            $color = imagecolorallocate($im,
                (int)($r1 + ($r2-$r1)*$tt),
                (int)($g1 + ($g2-$g1)*$tt),
                (int)($b1 + ($b2-$b1)*$tt)
            );
            imagesetpixel($im, $x+$j, $y+$i, $color);
        }
    }
}

function pt_rounded_rect(GdImage $im, int $x, int $y, int $w, int $h, int $r, int $color): void {
    $r = min($r, (int)($w/2), (int)($h/2));
    if ($r <= 0) {
        imagefilledrectangle($im, $x, $y, $x+$w, $y+$h, $color);
        return;
    }
    imagefilledrectangle($im, $x+$r, $y, $x+$w-$r, $y+$h, $color);
    imagefilledrectangle($im, $x, $y+$r, $x+$w, $y+$h-$r, $color);
    imagefilledellipse($im, $x+$r,   $y+$r,   $r*2, $r*2, $color);
    imagefilledellipse($im, $x+$w-$r,$y+$r,   $r*2, $r*2, $color);
    imagefilledellipse($im, $x+$r,   $y+$h-$r,$r*2, $r*2, $color);
    imagefilledellipse($im, $x+$w-$r,$y+$h-$r,$r*2, $r*2, $color);
}

function pt_rounded_rect_border(GdImage $im, int $x, int $y, int $w, int $h, int $r, int $color, int $thickness = 1): void {
    for ($t = 0; $t < $thickness; $t++) {
        $r2 = max(0, $r - $t);
        imagearc($im, $x+$r2+$t,   $y+$r2+$t,   $r2*2, $r2*2, 180, 270, $color);
        imagearc($im, $x+$w-$r2-$t,$y+$r2+$t,   $r2*2, $r2*2, 270, 360, $color);
        imagearc($im, $x+$r2+$t,   $y+$h-$r2-$t,$r2*2, $r2*2,  90, 180, $color);
        imagearc($im, $x+$w-$r2-$t,$y+$h-$r2-$t,$r2*2, $r2*2,   0,  90, $color);
        imageline($im, $x+$r2+$t, $y+$t, $x+$w-$r2-$t, $y+$t, $color);
        imageline($im, $x+$r2+$t, $y+$h-$t, $x+$w-$r2-$t, $y+$h-$t, $color);
        imageline($im, $x+$t, $y+$r2+$t, $x+$t, $y+$h-$r2-$t, $color);
        imageline($im, $x+$w-$t, $y+$r2+$t, $x+$w-$t, $y+$h-$r2-$t, $color);
    }
}

function pt_dot_grid(GdImage $im, int $x, int $y, int $w, int $h, string $dotColor, float $opacity = 0.06): void {
    [$r,$g,$b] = pt_hex2rgb($dotColor);
    $a = min(127, (int)(127 * (1 - $opacity)));
    $color = imagecolorallocatealpha($im, $r, $g, $b, $a);
    imagecolortransparent($im, $color);
    $spacing = max(20, (int)($w / 30));
    for ($dy = $y + $spacing; $dy < $y + $h; $dy += $spacing) {
        for ($dx = $x + $spacing; $dx < $x + $w; $dx += $spacing) {
            imagefilledellipse($im, $dx, $dy, 2, 2, $color);
        }
    }
}

function pt_noise(GdImage $im, int $x, int $y, int $w, int $h, string $noiseColor, float $density = 0.008): void {
    [$r,$g,$b] = pt_hex2rgb($noiseColor);
    $total = $w * $h;
    $count = (int)($total * $density);
    $c = imagecolorallocatealpha($im, $r, $g, $b, 100);
    for ($i = 0; $i < $count; $i++) {
        $px = $x + rand(0, $w - 1);
        $py = $y + rand(0, $h - 1);
        imagesetpixel($im, $px, $py, $c);
    }
}

function pt_text_block(
    GdImage $im, string $font, float $size, int $x, int $y,
    string $hexColor, string $text, int $maxW, float $lineH,
    string $align = 'left', int $maxLines = 0
): int {
    if (!file_exists($font) || $text === '') return $y;
    $color = pt_color($im, $hexColor);
    $lines = pt_wrap_text($font, $size, $text, $maxW);
    if ($maxLines > 0) {
        $lines = array_slice($lines, 0, $maxLines);
        if (count($lines) === $maxLines && count(pt_wrap_text($font, $size, $text, $maxW)) > $maxLines) {
            $last = &$lines[$maxLines - 1];
            while (strlen($last) > 3 && pt_text_width($font, $size, $last . '…') > $maxW) {
                $last = rtrim(substr($last, 0, -1));
            }
            $last .= '…';
        }
    }
    $curY = $y;
    foreach ($lines as $line) {
        $bbox = imagettfbbox($size, 0, $font, $line);
        $tw = abs($bbox[2] - $bbox[0]);
        $tx = $x;
        if ($align === 'center') $tx = $x + (int)(($maxW - $tw) / 2);
        elseif ($align === 'right') $tx = $x + $maxW - $tw;
        imagettftext($im, $size, 0, $tx, (int)($curY + $size), $color, $font, $line);
        $curY += $lineH;
    }
    return (int)($curY);
}

function pt_text_width(string $font, float $size, string $text): int {
    if (!file_exists($font)) return 0;
    $bbox = imagettfbbox($size, 0, $font, $text);
    return abs($bbox[2] - $bbox[0]);
}

function pt_wrap_text(string $font, float $size, string $text, int $maxW): array {
    if (!file_exists($font)) return [$text];
    $words = explode(' ', str_replace(["\n", "\r"], ' ', $text));
    $lines = [];
    $current = '';
    foreach ($words as $word) {
        if ($word === '') continue;
        $test = $current === '' ? $word : $current . ' ' . $word;
        $bbox = imagettfbbox($size, 0, $font, $test);
        $w = abs($bbox[2] - $bbox[0]);
        if ($w > $maxW && $current !== '') {
            $lines[] = $current;
            $current = $word;
        } else {
            $current = $test;
        }
    }
    if ($current !== '') $lines[] = $current;
    return $lines ?: [''];
}

function pt_icon(GdImage $im, string $name, int $x, int $y, int $size, string $hexColor): void {
    if (!file_exists(PT_FONT_FA)) {
        // Fallback: draw simple geometric icon
        $c = pt_color($im, $hexColor);
        imagefilledellipse($im, $x + $size/2, $y + $size/2, (int)($size*0.6), (int)($size*0.6), $c);
        return;
    }
    $cp = pt_fa_codepoint($name);
    $char = pt_unicode_char($cp);
    $color = pt_color($im, $hexColor);
    // Center the icon in the given box
    $bbox = imagettfbbox($size * 0.75, 0, PT_FONT_FA, $char);
    $tw = abs($bbox[2] - $bbox[0]);
    $th = abs($bbox[5] - $bbox[1]);
    $tx = $x + (int)(($size - $tw) / 2);
    $ty = $y + (int)(($size + $th) / 2);
    imagettftext($im, $size * 0.75, 0, $tx, $ty, $color, PT_FONT_FA, $char);
}

function pt_badge(GdImage $im, string $font, string $text, int $x, int $y, string $bgHex, string $fgHex, int $radius = 100, float $fontSize = 14): void {
    if ($text === '' || !file_exists($font)) return;
    $bbox = imagettfbbox($fontSize, 0, $font, $text);
    $tw = abs($bbox[2] - $bbox[0]);
    $ph = 8; $pv = 5;
    $bw = $tw + $ph * 2; $bh = (int)($fontSize) + $pv * 2 + 4;
    $bg = pt_color($im, $bgHex);
    pt_rounded_rect($im, $x, $y, $bw, $bh, min($radius, (int)($bh/2)), $bg);
    imagettftext($im, $fontSize, 0, $x + $ph, $y + $pv + (int)($fontSize), pt_color($im, $fgHex), $font, $text);
}

function pt_badge_width(string $font, string $text, float $fontSize = 14): int {
    if (!file_exists($font)) return 80;
    $bbox = imagettfbbox($fontSize, 0, $font, $text);
    return abs($bbox[2] - $bbox[0]) + 16 * 2;
}

function pt_shadow_rect(GdImage $im, int $x, int $y, int $w, int $h, int $r, string $bgHex, int $blur = 8): void {
    [$sr,$sg,$sb] = pt_hex2rgb($bgHex);
    $shadowR = max(0,$sr-40); $shadowG = max(0,$sg-40); $shadowB = max(0,$sb-40);
    for ($i = $blur; $i >= 1; $i--) {
        $alpha = (int)(127 * ($i / ($blur + 1)));
        $c = imagecolorallocatealpha($im, $shadowR,$shadowG,$shadowB, $alpha);
        pt_rounded_rect($im, $x+$i, $y+$i, $w, $h, $r, $c);
    }
}

function pt_fallback_image(string $format = 'png'): never {
    ob_end_clean();
    $im = imagecreatetruecolor(400, 200);
    $bg = imagecolorallocate($im, 20, 20, 30);
    $fg = imagecolorallocate($im, 120, 120, 140);
    imagefill($im, 0, 0, $bg);
    imagestring($im, 4, 130, 85, 'Image unavailable', $fg);
    pt_output($im, $format, 400, 200);
    exit;
}

function pt_output(GdImage $im, string $format, int $w, int $h): void {
    ob_end_clean();
    $etag = '"pt-' . md5($w . $h . $format . microtime()) . '"';
    header('ETag: ' . $etag);
    header('Cache-Control: public, max-age=86400, immutable');
    header('X-Content-Type-Options: nosniff');
    imagesavealpha($im, true);
    if ($format === 'jpg' || $format === 'jpeg') {
        header('Content-Type: image/jpeg');
        imagejpeg($im, null, 92);
    } elseif ($format === 'webp') {
        header('Content-Type: image/webp');
        imagewebp($im, null, 90);
    } else {
        header('Content-Type: image/png');
        imagepng($im, null, 6);
    }
    imagedestroy($im);
}

// ════════════════════════════════════════════════════════════════════════════
// OG IMAGE TEMPLATES (spec-based)
// ════════════════════════════════════════════════════════════════════════════

$OG_SPECS = [
    'github_dark' => [
        'bg'=>'0d1117','bg2'=>null,'pattern'=>'dots',
        'card_bg'=>'161b22','card_alpha'=>0,'card_border'=>'30363d',
        'heading_color'=>'e6edf3','desc_color'=>'8b949e',
        'badge_bg'=>'21262d','badge_color'=>'3fb950',
        'accent_color'=>'3fb950','accent_pos'=>'left',
        'icon_bg'=>'21262d','icon_color'=>'3fb950',
        'footer_color'=>'484f58','radius'=>8,
    ],
    'github_light' => [
        'bg'=>'ffffff','bg2'=>null,'pattern'=>'none',
        'card_bg'=>'f6f8fa','card_alpha'=>0,'card_border'=>'d0d7de',
        'heading_color'=>'1f2328','desc_color'=>'656d76',
        'badge_bg'=>'ddf4ff','badge_color'=>'0969da',
        'accent_color'=>'0969da','accent_pos'=>'left',
        'icon_bg'=>'ddf4ff','icon_color'=>'0969da',
        'footer_color'=>'6e7781','radius'=>6,
    ],
    'glass_modern' => [
        'bg'=>'1a0533','bg2'=>'0a1628','pattern'=>'gradient',
        'card_bg'=>'ffffff','card_alpha'=>20,'card_border'=>'ffffff',
        'heading_color'=>'ffffff','desc_color'=>'c4b5fd',
        'badge_bg'=>'6d28d9','badge_color'=>'ddd6fe',
        'accent_color'=>'a855f7','accent_pos'=>'bottom',
        'icon_bg'=>'4c1d95','icon_color'=>'a855f7',
        'footer_color'=>'7c3aed','radius'=>20,
    ],
    'minimal_clean' => [
        'bg'=>'ffffff','bg2'=>null,'pattern'=>'dots',
        'card_bg'=>null,'card_alpha'=>0,'card_border'=>null,
        'heading_color'=>'111827','desc_color'=>'6b7280',
        'badge_bg'=>'f3f4f6','badge_color'=>'374151',
        'accent_color'=>'3b82f6','accent_pos'=>'none',
        'icon_bg'=>'eff6ff','icon_color'=>'3b82f6',
        'footer_color'=>'9ca3af','radius'=>0,
    ],
    'gradient_pro' => [
        'bg'=>'0f0c29','bg2'=>'302b63','pattern'=>'gradient',
        'card_bg'=>null,'card_alpha'=>0,'card_border'=>null,
        'heading_color'=>'ffffff','desc_color'=>'94a3b8',
        'badge_bg'=>'3b82f6','badge_color'=>'ffffff',
        'accent_color'=>'60a5fa','accent_pos'=>'none',
        'icon_bg'=>'1d4ed8','icon_color'=>'93c5fd',
        'footer_color'=>'64748b','radius'=>16,
    ],
    'corporate' => [
        'bg'=>'0f2744','bg2'=>'1a3a5c','pattern'=>'none',
        'card_bg'=>'162c47','card_alpha'=>0,'card_border'=>'1e3f63',
        'heading_color'=>'ffffff','desc_color'=>'90a4b9',
        'badge_bg'=>'c8a04a','badge_color'=>'0f2744',
        'accent_color'=>'c8a04a','accent_pos'=>'left',
        'icon_bg'=>'1a3a5c','icon_color'=>'c8a04a',
        'footer_color'=>'4a6a85','radius'=>4,
    ],
    'neon_dark' => [
        'bg'=>'030308','bg2'=>'060614','pattern'=>'noise',
        'card_bg'=>'0d0d1a','card_alpha'=>0,'card_border'=>'1a1a3a',
        'heading_color'=>'ffffff','desc_color'=>'a0a8c0',
        'badge_bg'=>'001a0d','badge_color'=>'00ff88',
        'accent_color'=>'00ff88','accent_pos'=>'left',
        'icon_bg'=>'001a0d','icon_color'=>'00ff88',
        'footer_color'=>'333355','radius'=>4,
    ],
    'startup' => [
        'bg'=>'f72585','bg2'=>'7209b7','pattern'=>'gradient',
        'card_bg'=>null,'card_alpha'=>0,'card_border'=>null,
        'heading_color'=>'ffffff','desc_color'=>'fecdd3',
        'badge_bg'=>'ffffff','badge_color'=>'be185d',
        'accent_color'=>'fbbf24','accent_pos'=>'none',
        'icon_bg'=>'ffffff','icon_color'=>'be185d',
        'footer_color'=>'fbcfe8','radius'=>16,
    ],
    // ── 12 new OG templates ──────────────────────────────────────
    'retro_sunset'  => ['bg'=>'2d1b69','bg2'=>'c43b00','pattern'=>'gradient','card_bg'=>null,'card_alpha'=>0,'card_border'=>null,'heading_color'=>'ffffff','desc_color'=>'ffd6c0','badge_bg'=>'c43b00','badge_color'=>'ffffff','accent_color'=>'ffd700','accent_pos'=>'none','icon_bg'=>'ffffff','icon_color'=>'2d1b69','footer_color'=>'ffd6c0','radius'=>0],
    'ocean'         => ['bg'=>'0c1b33','bg2'=>'023e8a','pattern'=>'gradient','card_bg'=>'0077b6','card_alpha'=>30,'card_border'=>'48cae4','heading_color'=>'caf0f8','desc_color'=>'90e0ef','badge_bg'=>'00b4d8','badge_color'=>'ffffff','accent_color'=>'48cae4','accent_pos'=>'bottom','icon_bg'=>'00b4d8','icon_color'=>'caf0f8','footer_color'=>'90e0ef','radius'=>16],
    'aurora'        => ['bg'=>'0a0f0d','bg2'=>'0d2b1a','pattern'=>'noise','card_bg'=>'0d1f15','card_alpha'=>0,'card_border'=>'1a4a2e','heading_color'=>'ecfdf5','desc_color'=>'6ee7b7','badge_bg'=>'064e3b','badge_color'=>'34d399','accent_color'=>'10b981','accent_pos'=>'left','icon_bg'=>'064e3b','icon_color'=>'34d399','footer_color'=>'34d399','radius'=>8],
    'newspaper'     => ['bg'=>'fef9ef','bg2'=>null,'pattern'=>'none','card_bg'=>'fef9ef','card_alpha'=>0,'card_border'=>'1a1a1a','heading_color'=>'1a1a1a','desc_color'=>'333333','badge_bg'=>'1a1a1a','badge_color'=>'fef9ef','accent_color'=>'1a1a1a','accent_pos'=>'top','icon_bg'=>'e8e0d0','icon_color'=>'1a1a1a','footer_color'=>'777777','radius'=>0],
    'blueprint'     => ['bg'=>'003580','bg2'=>'012a6b','pattern'=>'dots','card_bg'=>null,'card_alpha'=>0,'card_border'=>null,'heading_color'=>'ffffff','desc_color'=>'a8c4f0','badge_bg'=>'ffffff','badge_color'=>'003580','accent_color'=>'4fc3f7','accent_pos'=>'left','icon_bg'=>'ffffff','icon_color'=>'003580','footer_color'=>'a8c4f0','radius'=>0],
    'dark_amber'    => ['bg'=>'120e09','bg2'=>null,'pattern'=>'noise','card_bg'=>'1c1610','card_alpha'=>0,'card_border'=>'3d2f12','heading_color'=>'fef3c7','desc_color'=>'d97706','badge_bg'=>'3d2f12','badge_color'=>'f59e0b','accent_color'=>'f59e0b','accent_pos'=>'left','icon_bg'=>'3d2f12','icon_color'=>'f59e0b','footer_color'=>'78716c','radius'=>4],
    'cyberpunk'     => ['bg'=>'0a0a0a','bg2'=>null,'pattern'=>'noise','card_bg'=>'111111','card_alpha'=>0,'card_border'=>'ffff00','heading_color'=>'ffffff','desc_color'=>'ff00ff','badge_bg'=>'ffff00','badge_color'=>'0a0a0a','accent_color'=>'00ffff','accent_pos'=>'left','icon_bg'=>'1a1a00','icon_color'=>'ffff00','footer_color'=>'ff00ff','radius'=>0],
    'forest'        => ['bg'=>'14231b','bg2'=>'1a3a28','pattern'=>'none','card_bg'=>'1e3d2b','card_alpha'=>0,'card_border'=>'2d5a40','heading_color'=>'ecfdf5','desc_color'=>'86efac','badge_bg'=>'2d5a40','badge_color'=>'bbf7d0','accent_color'=>'22c55e','accent_pos'=>'left','icon_bg'=>'2d5a40','icon_color'=>'22c55e','footer_color'=>'4ade80','radius'=>4],
    'indie'         => ['bg'=>'fef6ee','bg2'=>null,'pattern'=>'dots','card_bg'=>'ffffff','card_alpha'=>0,'card_border'=>'e8d5c0','heading_color'=>'1c0a00','desc_color'=>'7c4a1e','badge_bg'=>'c4600a','badge_color'=>'ffffff','accent_color'=>'c4600a','accent_pos'=>'none','icon_bg'=>'fde8d0','icon_color'=>'c4600a','footer_color'=>'a0816b','radius'=>12],
    'mono'          => ['bg'=>'000000','bg2'=>null,'pattern'=>'none','card_bg'=>null,'card_alpha'=>0,'card_border'=>null,'heading_color'=>'ffffff','desc_color'=>'999999','badge_bg'=>'ffffff','badge_color'=>'000000','accent_color'=>'ffffff','accent_pos'=>'none','icon_bg'=>'222222','icon_color'=>'ffffff','footer_color'=>'666666','radius'=>0],
    'candy'         => ['bg'=>'fce4ec','bg2'=>'e8d5f0','pattern'=>'gradient','card_bg'=>null,'card_alpha'=>0,'card_border'=>null,'heading_color'=>'880e4f','desc_color'=>'ad1457','badge_bg'=>'ad1457','badge_color'=>'ffffff','accent_color'=>'e91e63','accent_pos'=>'none','icon_bg'=>'f8bbd0','icon_color'=>'ad1457','footer_color'=>'c2185b','radius'=>24],
    'steel'         => ['bg'=>'1c2128','bg2'=>null,'pattern'=>'none','card_bg'=>'22272e','card_alpha'=>0,'card_border'=>'444c56','heading_color'=>'cdd9e5','desc_color'=>'768390','badge_bg'=>'316dca','badge_color'=>'ffffff','accent_color'=>'316dca','accent_pos'=>'left','icon_bg'=>'1f3051','icon_color'=>'4493f8','footer_color'=>'444c56','radius'=>6],
];

function pt_render_og(GdImage $im, array $p, array $s): void {
    $W = imagesx($im); $H = imagesy($im);
    $pad = $p['padding'];
    $r = max(0, $s['radius'] ?? 12);

    // Background
    if (!empty($s['bg2'])) {
        if ($s['pattern'] === 'gradient') {
            pt_gradient_diag($im, 0, 0, $W, $H, $s['bg'], $s['bg2']);
        } else {
            pt_gradient_v($im, 0, 0, $W, $H, $s['bg'], $s['bg2']);
        }
    } else {
        imagefill($im, 0, 0, pt_color($im, $s['bg']));
    }

    if ($s['pattern'] === 'dots') {
        pt_dot_grid($im, 0, 0, $W, $H, '888888', 0.05);
    } elseif ($s['pattern'] === 'noise') {
        pt_noise($im, 0, 0, $W, $H, 'ffffff', 0.012);
    }

    // Card
    $cx = $pad; $cy = $pad; $cw = $W - $pad*2; $ch = $H - $pad*2;
    if (!empty($s['card_bg'])) {
        if ($s['card_alpha'] > 0) {
            imagesavealpha($im, true);
            $cardC = pt_color($im, $s['card_bg'], $s['card_alpha']);
            pt_rounded_rect($im, $cx, $cy, $cw, $ch, $r, $cardC);
        } else {
            pt_rounded_rect($im, $cx, $cy, $cw, $ch, $r, pt_color($im, $s['card_bg']));
        }
        if (!empty($s['card_border'])) {
            pt_rounded_rect_border($im, $cx, $cy, $cw, $ch, $r, pt_color($im, $s['card_border']), 1);
        }
    }

    // Accent line
    $ix = $cx; $iy = $cy; $iw = $cw; $ih = $ch;
    if ($s['accent_pos'] === 'left') {
        imagefilledrectangle($im, $ix+16, $iy+16, $ix+20, $iy+$ih-16, pt_color($im, $s['accent_color']));
        $ix += 36; $iw -= 36;
    } elseif ($s['accent_pos'] === 'top') {
        imagefilledrectangle($im, $ix, $iy, $ix+$iw, $iy+4, pt_color($im, $s['accent_color']));
        $iy += 16; $ih -= 16;
    } elseif ($s['accent_pos'] === 'bottom') {
        imagefilledrectangle($im, $ix, $iy+$ih-4, $ix+$iw, $iy+$ih, pt_color($im, $s['accent_color']));
    }

    // Inner padding
    $ip = 40;
    $ox = $ix + $ip; $oy = $iy + $ip; $ow = $iw - $ip*2;

    // Icon box
    $iconBoxSize = (int)($H * 0.11);
    $iconSize    = (int)($iconBoxSize * 0.6);
    pt_rounded_rect($im, $ox, $oy, $iconBoxSize, $iconBoxSize, (int)($iconBoxSize * 0.22), pt_color($im, $s['icon_bg']));
    pt_icon($im, $p['icon'], $ox + (int)(($iconBoxSize - $iconSize)/2), $oy + (int)(($iconBoxSize - $iconSize)/2), $iconSize, $s['icon_color']);

    $curY = $oy + $iconBoxSize + (int)($H * 0.04);

    // Badges (comma-separated support)
    $badgeStr = $p['badge'] ?: $p['category_label'];
    if ($badgeStr) {
        $badges   = array_filter(array_map('trim', explode(',', $badgeStr)));
        $badgeFsz = (int)($H * 0.022);
        $badgeX   = $ox;
        foreach (array_slice($badges, 0, 5) as $bg_label) {
            $bw = pt_badge_width(PT_FONT_REG, strtoupper($bg_label), $badgeFsz);
            if ($badgeX + $bw > $ox + $ow) break;
            pt_badge($im, PT_FONT_REG, strtoupper($bg_label), $badgeX, $curY, $s['badge_bg'], $s['badge_color'], 100, $badgeFsz);
            $badgeX += $bw + 8;
        }
        $curY += (int)($H * 0.06);
    }

    // Heading
    $headingSize = min((int)($H * 0.082), max(22, $p['font_size']));
    $curY = pt_text_block($im, PT_FONT_BOLD, $headingSize, $ox, $curY, $s['heading_color'], $p['heading'] ?: 'Heading', $ow, $headingSize * 1.25, 'left', 2);
    $curY += (int)($H * 0.02);

    // Description
    if ($p['description']) {
        $descSize = (int)($H * 0.036);
        $curY = pt_text_block($im, PT_FONT_REG, $descSize, $ox, $curY, $s['desc_color'], $p['description'], $ow, $descSize * 1.5, 'left', 3);
    }

    // Footer
    $footerText = trim(implode('  ·  ', array_filter([$p['website'], $p['footer'], $p['author']])));
    if ($footerText) {
        $fy = $iy + $ih - (int)($H * 0.08);
        if (!empty($s['card_bg'])) {
            imagefilledrectangle($im, $ox, $fy - 12, $ox + $ow, $fy - 11, pt_color($im, $s['card_border'] ?? $s['footer_color']));
        }
        $fsz = (int)($H * 0.026);
        pt_text_block($im, PT_FONT_REG, $fsz, $ox, $fy, $s['footer_color'], $footerText, $ow, $fsz*1.4, 'left', 1);
    }

    // Watermark
    if ($p['watermark']) {
        $wsz = (int)($H * 0.022);
        $wc = pt_color($im, $s['footer_color']);
        $wbbox = imagettfbbox($wsz, 0, PT_FONT_REG, $p['watermark']);
        $ww = abs($wbbox[2]-$wbbox[0]);
        imagettftext($im, $wsz, 0, $W - $pad - $ww, $H - $pad/2, $wc, PT_FONT_REG, $p['watermark']);
    }
}

// ════════════════════════════════════════════════════════════════════════════
// SOCIAL CARD TEMPLATES
// ════════════════════════════════════════════════════════════════════════════

$SOCIAL_SPECS = [
    'twitter' => [
        'bg'=>'000000','bg2'=>null,
        'card_bg'=>'0f0f0f','card_border'=>'2f3336',
        'heading_color'=>'e7e9ea','desc_color'=>'8b98a5',
        'accent'=>'1d9bf0','badge_bg'=>'1d9bf0','badge_color'=>'ffffff',
        'icon_bg'=>'1d3a4f','icon_color'=>'1d9bf0','footer_color'=>'536471','radius'=>16,
    ],
    'linkedin' => [
        'bg'=>'f3f2ef','bg2'=>null,
        'card_bg'=>'ffffff','card_border'=>'e0dfdc',
        'heading_color'=>'000000','desc_color'=>'666666',
        'accent'=>'0a66c2','badge_bg'=>'0a66c2','badge_color'=>'ffffff',
        'icon_bg'=>'cce5ff','icon_color'=>'0a66c2','footer_color'=>'666666','radius'=>8,
    ],
    'discord' => [
        'bg'=>'313338','bg2'=>null,
        'card_bg'=>'2b2d31','card_border'=>'1e1f22',
        'heading_color'=>'f2f3f5','desc_color'=>'b5bac1',
        'accent'=>'5865f2','badge_bg'=>'5865f2','badge_color'=>'ffffff',
        'icon_bg'=>'3c45a5','icon_color'=>'ffffff','footer_color'=>'4e5058','radius'=>4,
    ],
    'telegram' => [
        'bg'=>'212121','bg2'=>null,
        'card_bg'=>'2c2c2c','card_border'=>'383838',
        'heading_color'=>'ffffff','desc_color'=>'aaaaaa',
        'accent'=>'2aabee','badge_bg'=>'2aabee','badge_color'=>'ffffff',
        'icon_bg'=>'005691','icon_color'=>'2aabee','footer_color'=>'777777','radius'=>12,
    ],
    'announcement' => [
        'bg'=>'020817','bg2'=>'1e1b4b',
        'card_bg'=>'0f172a','card_border'=>'334155',
        'heading_color'=>'f8fafc','desc_color'=>'94a3b8',
        'accent'=>'f59e0b','badge_bg'=>'f59e0b','badge_color'=>'000000',
        'icon_bg'=>'451a03','icon_color'=>'f59e0b','footer_color'=>'475569','radius'=>12,
    ],
    'product_launch' => [
        'bg'=>'06b6d4','bg2'=>'0ea5e9',
        'card_bg'=>null,'card_border'=>null,
        'heading_color'=>'ffffff','desc_color'=>'e0f7fa',
        'accent'=>'ffffff','badge_bg'=>'ffffff','badge_color'=>'0891b2',
        'icon_bg'=>'ffffff','icon_color'=>'0891b2','footer_color'=>'b2ebf2','radius'=>16,
    ],
    'feature_highlight' => [
        'bg'=>'1a1a2e','bg2'=>'16213e',
        'card_bg'=>'0f3460','card_border'=>'e94560',
        'heading_color'=>'ffffff','desc_color'=>'a8b2d8',
        'accent'=>'e94560','badge_bg'=>'e94560','badge_color'=>'ffffff',
        'icon_bg'=>'e94560','icon_color'=>'ffffff','footer_color'=>'627b9a','radius'=>12,
    ],
    'blog_post' => [
        'bg'=>'fafafa','bg2'=>null,
        'card_bg'=>'ffffff','card_border'=>'e4e4e7',
        'heading_color'=>'09090b','desc_color'=>'71717a',
        'accent'=>'ef4444','badge_bg'=>'fef2f2','badge_color'=>'ef4444',
        'icon_bg'=>'fef2f2','icon_color'=>'ef4444','footer_color'=>'a1a1aa','radius'=>8,
    ],
    // ── 10 new social templates ──────────────────────────────────
    'youtube'       => ['bg'=>'0f0f0f','bg2'=>null,'card_bg'=>'212121','card_border'=>'3d3d3d','heading_color'=>'ffffff','desc_color'=>'aaaaaa','accent_color'=>null,'accent_pos'=>'none','badge_bg'=>'ff0000','badge_color'=>'ffffff','icon_bg'=>'330000','icon_color'=>'ff0000','footer_color'=>'717171','radius'=>4],
    'instagram'     => ['bg'=>'833ab4','bg2'=>'fd1d1d','pattern'=>'gradient','card_bg'=>null,'card_border'=>null,'heading_color'=>'ffffff','desc_color'=>'ffecd2','accent_color'=>'fcb045','accent_pos'=>'none','badge_bg'=>'ffffff','badge_color'=>'833ab4','icon_bg'=>'ffffff','icon_color'=>'fd1d1d','footer_color'=>'ffecd2','radius'=>20],
    'facebook'      => ['bg'=>'1877f2','bg2'=>null,'card_bg'=>'ffffff','card_border'=>'dde3ee','heading_color'=>'1c1e21','desc_color'=>'65676b','accent_color'=>'1877f2','accent_pos'=>'none','badge_bg'=>'1877f2','badge_color'=>'ffffff','icon_bg'=>'e7f3ff','icon_color'=>'1877f2','footer_color'=>'8a8d91','radius'=>8],
    'reddit'        => ['bg'=>'1a1a1b','bg2'=>null,'card_bg'=>'272729','card_border'=>'343536','heading_color'=>'d7dadc','desc_color'=>'818384','accent_color'=>null,'accent_pos'=>'none','badge_bg'=>'ff4500','badge_color'=>'ffffff','icon_bg'=>'331400','icon_color'=>'ff4500','footer_color'=>'818384','radius'=>4],
    'hackernews'    => ['bg'=>'f6f6ef','bg2'=>null,'card_bg'=>'ffffff','card_border'=>'e5e5e5','heading_color'=>'000000','desc_color'=>'828282','accent_color'=>null,'accent_pos'=>'none','badge_bg'=>'ff6600','badge_color'=>'ffffff','icon_bg'=>'fff0e6','icon_color'=>'ff6600','footer_color'=>'828282','radius'=>0],
    'product_hunt'  => ['bg'=>'da552f','bg2'=>'c0392b','pattern'=>'gradient','card_bg'=>null,'card_border'=>null,'heading_color'=>'ffffff','desc_color'=>'ffd0c0','accent_color'=>null,'accent_pos'=>'none','badge_bg'=>'ffffff','badge_color'=>'da552f','icon_bg'=>'ffffff','icon_color'=>'da552f','footer_color'=>'ffd0c0','radius'=>16],
    'dribbble'      => ['bg'=>'ea4c89','bg2'=>null,'card_bg'=>'f6478c','card_border'=>'f8a9c9','heading_color'=>'ffffff','desc_color'=>'fce4ef','accent_color'=>null,'accent_pos'=>'none','badge_bg'=>'ffffff','badge_color'=>'ea4c89','icon_bg'=>'ffffff','icon_color'=>'ea4c89','footer_color'=>'fce4ef','radius'=>16],
    'newsletter'    => ['bg'=>'faf7f2','bg2'=>null,'card_bg'=>'ffffff','card_border'=>'e8ddd0','heading_color'=>'1a1208','desc_color'=>'6b5a45','accent_color'=>'c17817','accent_pos'=>'none','badge_bg'=>'fef3e2','badge_color'=>'c17817','icon_bg'=>'fef3e2','icon_color'=>'c17817','footer_color'=>'9b8b78','radius'=>8],
    'event'         => ['bg'=>'13001f','bg2'=>'2d0050','pattern'=>'gradient','card_bg'=>'1a0030','card_border'=>'c89600','heading_color'=>'ffffff','desc_color'=>'e2c8ff','accent_color'=>'c89600','accent_pos'=>'left','badge_bg'=>'c89600','badge_color'=>'000000','icon_bg'=>'c89600','icon_color'=>'13001f','footer_color'=>'9370db','radius'=>4],
    'job_post'      => ['bg'=>'f0fdf4','bg2'=>null,'card_bg'=>'ffffff','card_border'=>'bbf7d0','heading_color'=>'052e16','desc_color'=>'166534','accent_color'=>'16a34a','accent_pos'=>'none','badge_bg'=>'dcfce7','badge_color'=>'15803d','icon_bg'=>'dcfce7','icon_color'=>'16a34a','footer_color'=>'4ade80','radius'=>12],
];

// Social cards use the same renderer as OG but with different default dimensions
function pt_render_social(GdImage $im, array $p, array $s): void {
    pt_render_og($im, $p, $s);
}

// ════════════════════════════════════════════════════════════════════════════
// PLACEHOLDER TEMPLATES
// ════════════════════════════════════════════════════════════════════════════

function pt_render_placeholder(GdImage $im, array $p, string $template): void {
    $W = imagesx($im); $H = imagesy($im);
    $bg = $p['bg_color']; $fg = $p['fg_color']; $accent = $p['accent_color'];
    $pad = $p['padding'];

    switch ($template) {
        case 'simple':
            imagefill($im, 0, 0, pt_color($im, $bg));
            if ($p['heading']) {
                pt_text_block($im, PT_FONT_BOLD, (int)($H * 0.06), $pad, (int)($H/2) - (int)($H*0.04), $fg, $p['heading'], $W-$pad*2, (int)($H*0.08), 'center', 2);
            }
            if ($p['description']) {
                pt_text_block($im, PT_FONT_REG, (int)($H * 0.035), $pad, (int)($H/2) + (int)($H*0.06), $fg . '99', $p['description'], $W-$pad*2, (int)($H*0.05), 'center', 2);
            }
            break;
        case 'grid':
            imagefill($im, 0, 0, pt_color($im, $bg));
            // Draw grid lines
            $gc = pt_color($im, $fg, 110);
            $spacing = (int)(min($W, $H) / 8);
            for ($x = 0; $x <= $W; $x += $spacing) imageline($im, $x, 0, $x, $H, $gc);
            for ($y = 0; $y <= $H; $y += $spacing) imageline($im, 0, $y, $W, $y, $gc);
            if ($p['heading']) {
                // Label box
                $lsz = (int)($H * 0.055);
                $bbox = imagettfbbox($lsz, 0, PT_FONT_BOLD, $p['heading']);
                $tw = abs($bbox[2]-$bbox[0]); $th = abs($bbox[5]-$bbox[1]);
                $lx = (int)(($W-$tw)/2)-16; $ly = (int)(($H-$th)/2)-12;
                $lw = $tw+32; $lh = $th+24;
                pt_rounded_rect($im, $lx, $ly, $lw, $lh, 6, pt_color($im, $bg));
                pt_rounded_rect_border($im, $lx, $ly, $lw, $lh, 6, pt_color($im, $accent), 2);
                pt_text_block($im, PT_FONT_BOLD, $lsz, $lx+16, $ly+8, $fg, $p['heading'], $tw+4, $lsz*1.4, 'center', 1);
            }
            break;
        case 'gradient':
            pt_gradient_diag($im, 0, 0, $W, $H, $bg, $accent);
            if ($p['heading']) {
                pt_text_block($im, PT_FONT_BOLD, (int)($H*0.07), $pad, (int)($H/2)-(int)($H*0.06), $fg, $p['heading'], $W-$pad*2, (int)($H*0.09), 'center', 2);
            }
            if ($p['description']) {
                pt_text_block($im, PT_FONT_REG, (int)($H*0.04), $pad, (int)($H/2)+(int)($H*0.04), $fg . 'cc', $p['description'], $W-$pad*2, (int)($H*0.055), 'center', 2);
            }
            break;
        case 'glass':
            pt_gradient_v($im, 0, 0, $W, $H, $bg, $accent);
            pt_noise($im, 0, 0, $W, $H, 'ffffff', 0.01);
            $gx = (int)($W*0.1); $gy = (int)($H*0.1); $gw = (int)($W*0.8); $gh = (int)($H*0.8);
            $gc = pt_color($im, 'ffffff', 100);
            pt_rounded_rect($im, $gx, $gy, $gw, $gh, (int)($p['radius'] ?? 20), $gc);
            pt_rounded_rect_border($im, $gx, $gy, $gw, $gh, (int)($p['radius'] ?? 20), pt_color($im, 'ffffff', 70), 1);
            if ($p['heading']) {
                pt_text_block($im, PT_FONT_BOLD, (int)($H*0.06), $gx+40, (int)($H/2)-(int)($H*0.04), $fg, $p['heading'], $gw-80, (int)($H*0.08), 'center', 2);
            }
            break;
        case 'pattern':
            imagefill($im, 0, 0, pt_color($im, $bg));
            // Diagonal stripe pattern
            $sc = pt_color($im, $fg, 118);
            $spacing = (int)(min($W,$H)/12);
            for ($i = -$H; $i < $W+$H; $i += $spacing*2) {
                imagefilledpolygon($im, [
                    $i, 0, $i+$spacing, 0, $i+$spacing+$H, $H, $i+$H, $H
                ], $sc);
            }
            if ($p['heading']) {
                pt_text_block($im, PT_FONT_BOLD, (int)($H*0.06), $pad, (int)($H/2)-(int)($H*0.04), $fg, $p['heading'], $W-$pad*2, (int)($H*0.08), 'center', 2);
            }
            break;
        case 'minimal':
        default:
            imagefill($im, 0, 0, pt_color($im, $bg));
            // Border
            $bc = pt_color($im, $accent);
            imagefilledrectangle($im, 0, 0, $W, 4, $bc);
            imagefilledrectangle($im, 0, $H-4, $W, $H, $bc);
            imagefilledrectangle($im, 0, 0, 4, $H, $bc);
            imagefilledrectangle($im, $W-4, 0, $W, $H, $bc);
            $sz = (int)($W/12);
            imagefilledellipse($im, (int)($W/2), (int)($H/2 - $H*0.12), $sz, $sz, $bc);
            if ($p['heading']) {
                pt_text_block($im, PT_FONT_BOLD, (int)($H*0.055), $pad, (int)($H/2), $fg, $p['heading'], $W-$pad*2, (int)($H*0.075), 'center', 1);
            }
            if ($p['description']) {
                pt_text_block($im, PT_FONT_REG, (int)($H*0.035), $pad, (int)($H/2)+(int)($H*0.09), $fg, $p['description'], $W-$pad*2, (int)($H*0.05), 'center', 1);
            }
            $dimText = $W . ' × ' . $H;
            $dsz = (int)($H*0.028);
            pt_text_block($im, PT_FONT_REG, $dsz, $pad, $H-$pad/2-$dsz, $accent, $dimText, $W-$pad*2, $dsz*1.4, 'right', 1);
            break;
        case 'modern':
            pt_gradient_h($im, 0, 0, $W, $H, $bg, pt_hex2rgb($bg)[0] > 127 ? 'f0f0f0' : '1a1a2e');
            // Centered circle with icon
            $cx = (int)($W/2); $cy = (int)($H/2 - $H*0.08);
            $cs = (int)($H*0.18);
            pt_rounded_rect($im, $cx-$cs/2, $cy-$cs/2, $cs, $cs, (int)($cs*0.25), pt_color($im, $accent));
            pt_icon($im, $p['icon'] ?: 'image', $cx-(int)($cs*0.3), $cy-(int)($cs*0.3), (int)($cs*0.6), $fg);
            if ($p['heading']) {
                pt_text_block($im, PT_FONT_BOLD, (int)($H*0.055), $pad, (int)($H/2)+(int)($H*0.12), $fg, $p['heading'], $W-$pad*2, (int)($H*0.07), 'center', 1);
            }
            break;
        case 'empty_state':
            imagefill($im, 0, 0, pt_color($im, $bg));
            $bc = pt_color($im, $accent);
            for ($x = 0; $x < $W; $x += 20) {
                if ((int)($x/10) % 2 === 0) {
                    imagefilledrectangle($im, $x, 4, min($W, $x+10), 7, $bc);
                    imagefilledrectangle($im, $x, $H-7, min($W, $x+10), $H-4, $bc);
                }
            }
            for ($y = 0; $y < $H; $y += 20) {
                if ((int)($y/10) % 2 === 0) {
                    imagefilledrectangle($im, 4, $y, 7, min($H, $y+10), $bc);
                    imagefilledrectangle($im, $W-7, $y, $W-4, min($H, $y+10), $bc);
                }
            }
            $cs = (int)($H*0.2);
            $cx = (int)(($W-$cs)/2); $cy = (int)($H*0.25);
            pt_rounded_rect($im, $cx, $cy, $cs, $cs, (int)($cs*0.2), pt_color($im, $accent, 110));
            pt_icon($im, 'image', $cx+(int)($cs*0.2), $cy+(int)($cs*0.2), (int)($cs*0.6), $accent);
            if ($p['heading']) pt_text_block($im, PT_FONT_BOLD, (int)($H*0.05), $pad, (int)($H*0.57), $fg, $p['heading'], $W-$pad*2, (int)($H*0.07), 'center', 1);
            if ($p['description']) pt_text_block($im, PT_FONT_REG, (int)($H*0.032), $pad, (int)($H*0.67), $fg.'99', $p['description'], $W-$pad*2, (int)($H*0.045), 'center', 2);
            break;

        // ── 10 new placeholder templates ─────────────────────────
        case 'blueprint_grid':
            imagefill($im, 0, 0, pt_color($im, $bg));
            $gc = pt_color($im, $fg, 45); $spacing = (int)(min($W,$H)/16);
            for ($x=0; $x<=$W; $x+=$spacing) imageline($im,$x,0,$x,$H,$gc);
            for ($y=0; $y<=$H; $y+=$spacing) imageline($im,0,$y,$W,$y,$gc);
            $mgC = pt_color($im, $fg, 80);
            for ($x=0; $x<=$W; $x+=$spacing*4) imageline($im,$x,0,$x,$H,$mgC);
            for ($y=0; $y<=$H; $y+=$spacing*4) imageline($im,0,$y,$W,$y,$mgC);
            $cxc=(int)($W/2); $cyc=(int)($H/2);
            imageline($im,$cxc-24,$cyc,$cxc+24,$cyc,pt_color($im,$accent)); imageline($im,$cxc,$cyc-24,$cxc,$cyc+24,pt_color($im,$accent));
            imagefilledellipse($im,$cxc,$cyc,8,8,pt_color($im,$accent));
            if ($p['heading']) pt_text_block($im,PT_FONT_BOLD,(int)($H*0.055),$pad,(int)($H/2)-(int)($H*0.085),$fg,$p['heading'],$W-$pad*2,(int)($H*0.075),'center',1);
            break;
        case 'crosshatch':
            imagefill($im, 0, 0, pt_color($im, $bg));
            $lc = pt_color($im, $fg, 90); $sp = (int)(min($W,$H)/14);
            for ($i=-$H; $i<$W+$H; $i+=$sp) { imageline($im,$i,0,$i+$H,$H,$lc); imageline($im,$i+$H,0,$i,$H,$lc); }
            if ($p['heading']) {
                $hsz=(int)($H*0.055); $hbbox=imagettfbbox($hsz,0,PT_FONT_BOLD,$p['heading']); $hw=abs($hbbox[2]-$hbbox[0]);
                $hx=(int)(($W-$hw)/2)-20; pt_rounded_rect($im,$hx,(int)($H*0.38),$hw+40,$hsz+28,6,pt_color($im,$bg));
                pt_text_block($im,PT_FONT_BOLD,$hsz,$hx+20,(int)($H*0.38)+8,$fg,$p['heading'],$hw+4,$hsz*1.4,'center',1);
            }
            break;
        case 'circuit':
            imagefill($im, 0, 0, pt_color($im, $bg));
            $cc = pt_color($im, $accent, 80); $gS = (int)(min($W,$H)/10);
            for ($row=0; $row<12; $row++) for ($col=0; $col<18; $col++) {
                $x1=$col*$gS; $y1=$row*$gS;
                if (($row+$col)%3===0) imageline($im,$x1,$y1,$x1+$gS,$y1,$cc);
                if (($row+$col)%4===0) imageline($im,$x1,$y1,$x1,$y1+$gS,$cc);
                if (($row+$col)%5===0) imagefilledellipse($im,$x1,$y1,7,7,pt_color($im,$accent));
            }
            if ($p['heading']) pt_text_block($im,PT_FONT_BOLD,(int)($H*0.055),$pad,(int)($H/2)-(int)($H*0.04),$fg,$p['heading'],$W-$pad*2,(int)($H*0.075),'center',1);
            break;
        case 'polka_dots':
            imagefill($im, 0, 0, pt_color($im, $bg));
            $dc=pt_color($im,$accent,90); $dsp=(int)(min($W,$H)/8); $dr=(int)($dsp*0.25);
            for ($y=$dsp/2; $y<$H; $y+=$dsp) for ($x=$dsp/2; $x<$W; $x+=$dsp) imagefilledellipse($im,(int)$x,(int)$y,$dr*2,$dr*2,$dc);
            if ($p['heading']) {
                $hsz=(int)($H*0.055); $hb=imagettfbbox($hsz,0,PT_FONT_BOLD,$p['heading']); $hw=abs($hb[2]-$hb[0]);
                $hx=(int)(($W-$hw)/2)-24; pt_rounded_rect($im,$hx,(int)($H*0.38),$hw+48,$hsz+28,$hsz+14,pt_color($im,$bg));
                pt_text_block($im,PT_FONT_BOLD,$hsz,$hx+24,(int)($H*0.38)+8,$fg,$p['heading'],$hw+4,$hsz*1.4,'center',1);
            }
            break;
        case 'diagonal_stripes':
            imagefill($im, 0, 0, pt_color($im, $bg));
            $sc=pt_color($im,$accent,65); $sp=(int)(min($W,$H)/8);
            for ($i=-$H; $i<$W+$H; $i+=$sp*2) imagefilledpolygon($im,[$i,0,$i+$sp,0,$i+$sp+$H,$H,$i+$H,$H],$sc);
            if ($p['heading']) pt_text_block($im,PT_FONT_BOLD,(int)($H*0.06),$pad,(int)($H/2)-(int)($H*0.04),$fg,$p['heading'],$W-$pad*2,(int)($H*0.08),'center',1);
            break;
        case 'noise_field':
            pt_gradient_diag($im, 0, 0, $W, $H, $bg, $accent);
            pt_noise($im, 0, 0, $W, $H, 'ffffff', 0.025);
            if ($p['heading']) pt_text_block($im,PT_FONT_BOLD,(int)($H*0.06),$pad,(int)($H/2)-(int)($H*0.04),$fg,$p['heading'],$W-$pad*2,(int)($H*0.08),'center',1);
            if ($p['description']) pt_text_block($im,PT_FONT_REG,(int)($H*0.035),$pad,(int)($H/2)+(int)($H*0.05),$fg.'bb',$p['description'],$W-$pad*2,(int)($H*0.05),'center',2);
            break;
        case 'sketch':
            imagefill($im, 0, 0, pt_color($im, $bg));
            $lc=pt_color($im,$fg,85);
            for ($y=0; $y<$H; $y+=(int)($H/20)) {
                $prevX=0; $prevY=$y;
                for ($x=10; $x<=$W; $x+=12) { $ny=$y+rand(-3,3); imageline($im,$prevX,$prevY,$x,$ny,$lc); $prevX=$x; $prevY=$ny; }
            }
            if ($p['heading']) {
                $hs=(int)($H*0.055); $bb=imagettfbbox($hs,0,PT_FONT_BOLD,$p['heading']); $hw=abs($bb[2]-$bb[0]);
                $hx=(int)(($W-$hw)/2)-20; pt_rounded_rect($im,$hx,(int)($H*0.38),$hw+40,$hs+28,4,pt_color($im,$bg));
                pt_text_block($im,PT_FONT_BOLD,$hs,$hx+20,(int)($H*0.38)+8,$fg,$p['heading'],$hw+4,$hs*1.4,'center',1);
            }
            break;
        case 'dots_dark':
            imagefill($im, 0, 0, pt_color($im, $bg));
            pt_dot_grid($im, 0, 0, $W, $H, $accent, 0.18);
            if ($p['heading']) pt_text_block($im,PT_FONT_BOLD,(int)($H*0.06),$pad,(int)($H/2)-(int)($H*0.04),$fg,$p['heading'],$W-$pad*2,(int)($H*0.08),'center',1);
            if ($p['description']) pt_text_block($im,PT_FONT_REG,(int)($H*0.035),$pad,(int)($H/2)+(int)($H*0.06),$fg.'aa',$p['description'],$W-$pad*2,(int)($H*0.05),'center',2);
            break;
        case 'gradient_mesh':
            pt_gradient_diag($im, 0, 0, $W, $H, $bg, $accent);
            pt_noise($im, 0, 0, $W, $H, 'ffffff', 0.01);
            if ($p['heading']) pt_text_block($im,PT_FONT_BOLD,(int)($H*0.065),$pad,(int)($H/2)-(int)($H*0.05),$fg,$p['heading'],$W-$pad*2,(int)($H*0.085),'center',1);
            break;
        case 'marble':
            pt_gradient_v($im, 0, 0, $W, $H, $bg, $fg.'22');
            for ($v=0; $v<6; $v++) {
                $vc=pt_color($im,$accent,(int)(25+$v*15)); $startY=(int)(($H/6)*$v); $prevX=0; $prevY=$startY;
                for ($x=8; $x<=$W; $x+=8) { $y=$startY+(int)(sin($x*0.03+$v)*$H*0.07+cos($x*0.015)*$H*0.03); imageline($im,$prevX,$prevY,$x,$y,$vc); $prevX=$x; $prevY=$y; }
            }
            if ($p['heading']) pt_text_block($im,PT_FONT_BOLD,(int)($H*0.055),$pad,(int)($H/2)-(int)($H*0.04),$fg,$p['heading'],$W-$pad*2,(int)($H*0.075),'center',1);
            break;
    }
}

// ════════════════════════════════════════════════════════════════════════════
// BROWSER MOCKUP TEMPLATES
// ════════════════════════════════════════════════════════════════════════════

function pt_render_browser(GdImage $im, array $p, string $template): void {
    $W = imagesx($im); $H = imagesy($im);
    $chrome_h = (int)($H * 0.12);
    $tab_h    = (int)($H * 0.055);
    $bar_h    = (int)($H * 0.065);

    // Template-specific styles
    $styles = [
        'chrome'  => ['chrome_bg'=>'dee1e6','tab_bg'=>'ffffff','bar_bg'=>'f1f3f4','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'3c4043','border'=>'c9cdd1','accent'=>'1a73e8'],
        'firefox' => ['chrome_bg'=>'2b2a33','tab_bg'=>'42414d','bar_bg'=>'1c1b22','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'fbfbfe','border'=>'52515e','accent'=>'ff7139'],
        'safari'  => ['chrome_bg'=>'ebebeb','tab_bg'=>'ffffff','bar_bg'=>'f5f5f5','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'1c1c1e','border'=>'d1d1d6','accent'=>'006aff'],
        'edge'    => ['chrome_bg'=>'202124','tab_bg'=>'2d2d2d','bar_bg'=>'171717','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'ffffff','border'=>'333333','accent'=>'0078d7'],
        'arc'     => ['chrome_bg'=>'1e1b2e','tab_bg'=>'2a2542','bar_bg'=>'1a1728','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'e8e6f0','border'=>'3d3a52','accent'=>'a78bfa'],
        'generic'         => ['chrome_bg'=>'f0f0f0','tab_bg'=>'ffffff','bar_bg'=>'e8e8e8','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'333333','border'=>'cccccc','accent'=>'4a90d9'],
        // ── 9 new browser templates ──────────────────────────────
        'brave'           => ['chrome_bg'=>'1a1a1a','tab_bg'=>'2a2a2a','bar_bg'=>'111111','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'ffffff','border'=>'333333','accent'=>'fb542b'],
        'opera'           => ['chrome_bg'=>'2b2b2b','tab_bg'=>'363636','bar_bg'=>'1c1c1c','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'ffffff','border'=>'444444','accent'=>'ff1b2d'],
        'vivaldi'         => ['chrome_bg'=>'2b1a2e','tab_bg'=>'3d2a42','bar_bg'=>'1f1224','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'e8d8f0','border'=>'4a3555','accent'=>'ef3939'],
        'dark_mode'       => ['chrome_bg'=>'1e1e1e','tab_bg'=>'2d2d2d','bar_bg'=>'141414','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'e0e0e0','border'=>'3a3a3a','accent'=>'4fc3f7'],
        'minimal_browser' => ['chrome_bg'=>'f8f8f8','tab_bg'=>'ffffff','bar_bg'=>'f0f0f0','dot_colors'=>['dddddd','dddddd','dddddd'],'txt_color'=>'333333','border'=>'e0e0e0','accent'=>'333333'],
        'retro_browser'   => ['chrome_bg'=>'c0c0c0','tab_bg'=>'d4d0c8','bar_bg'=>'c0c0c0','dot_colors'=>['ff0000','ffff00','00ff00'],'txt_color'=>'000000','border'=>'808080','accent'=>'000080'],
        'high_contrast'   => ['chrome_bg'=>'000000','tab_bg'=>'ffffff','bar_bg'=>'000000','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'ffffff','border'=>'ffffff','accent'=>'ffff00'],
        'material'        => ['chrome_bg'=>'1565c0','tab_bg'=>'1976d2','bar_bg'=>'0d47a1','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'ffffff','border'=>'1e88e5','accent'=>'64b5f6'],
        'warm_light'      => ['chrome_bg'=>'f5ede0','tab_bg'=>'fdfaf5','bar_bg'=>'ede5d8','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'3d2b1f','border'=>'d4c4b0','accent'=>'c17817'],
    ];
    $st = $styles[$template] ?? $styles['chrome'];

    // Window chrome background
    $chromeColor = pt_color($im, $st['chrome_bg']);
    imagefilledrectangle($im, 0, 0, $W, $chrome_h, $chromeColor);

    // Window control dots
    $dotY = (int)($tab_h / 2); $dotR = (int)($H * 0.013);
    foreach ([[$st['dot_colors'][0],20],[$st['dot_colors'][1],20+$dotR*3],[$st['dot_colors'][2],20+$dotR*6]] as [$dc,$dx]) {
        imagefilledellipse($im, $dx+$dotR, $dotY, $dotR*2, $dotR*2, pt_color($im, $dc));
    }

    // Tab strip
    $tabBg = pt_color($im, $st['tab_bg']);
    $tabX = (int)($W * 0.12); $tabW = (int)($W * 0.25); $tabH = $tab_h;
    $tabPoints = [$tabX, $tab_h, $tabX+8, 0, $tabX+$tabW-8, 0, $tabX+$tabW, $tab_h];
    imagefilledpolygon($im, $tabPoints, $tabBg);
    // Tab label
    if ($p['heading']) {
        $tsz = (int)($H * 0.023);
        $bbox = imagettfbbox($tsz, 0, PT_FONT_REG, $p['heading']);
        $tw = min(abs($bbox[2]-$bbox[0]), (int)($tabW*0.7));
        imagettftext($im, $tsz, 0, $tabX+12, (int)($tab_h*0.65), pt_color($im, $st['txt_color']), PT_FONT_REG, $p['heading']);
    }
    // Favicon dot
    imagefilledellipse($im, $tabX + 8, (int)($tab_h * 0.5), (int)($H*0.016), (int)($H*0.016), pt_color($im, $st['accent']));

    // URL bar
    $barY = $tab_h; $barH = $bar_h;
    imagefilledrectangle($im, 0, $barY, $W, $barY+$barH, pt_color($im, $st['bar_bg']));
    imagefilledrectangle($im, 0, $barY, $W, $barY+1, pt_color($im, $st['border']));
    $urlX = (int)($W * 0.25); $urlW = (int)($W * 0.5);
    $urlBoxH = (int)($barH * 0.65); $urlBoxY = $barY + (int)(($barH-$urlBoxH)/2);
    pt_rounded_rect($im, $urlX, $urlBoxY, $urlW, $urlBoxH, (int)($urlBoxH/2), pt_color($im, $st['tab_bg']));
    pt_rounded_rect_border($im, $urlX, $urlBoxY, $urlW, $urlBoxH, (int)($urlBoxH/2), pt_color($im, $st['border']), 1);
    $urlText = $p['url_bar'] ?: 'https://example.com';
    $usz = (int)($H * 0.022);
    $ubbox = imagettfbbox($usz, 0, PT_FONT_REG, $urlText);
    $utw = abs($ubbox[2]-$ubbox[0]);
    $utx = $urlX + (int)(($urlW - $utw) / 2);
    imagettftext($im, $usz, 0, $utx, $urlBoxY + (int)($urlBoxH * 0.68), pt_color($im, $st['txt_color']), PT_FONT_REG, $urlText);

    // Content area
    $contentY = $barY + $barH + 1;
    $contentH = $H - $contentY;
    $bg = !empty($p['bg_color']) ? $p['bg_color'] : 'f8fafc';
    pt_gradient_v($im, 0, $contentY, $W, $contentH, $bg, pt_hex2rgb($bg)[0] > 127 ? 'e2e8f0' : '0f172a');
    // Simulated website content
    $wy = $contentY + (int)($contentH * 0.08);
    $wpad = (int)($W * 0.1);
    // Hero heading bar
    if ($p['description']) {
        $descH = (int)($contentH * 0.04);
        $descColor = pt_hex2rgb($bg)[0] > 127 ? '111827' : 'f8fafc';
        pt_text_block($im, PT_FONT_BOLD, $descH, $wpad, $wy, $descColor, $p['description'], $W-$wpad*2, $descH*1.3, 'center', 2);
        $wy += $descH * 3;
    }
    // Simulated content blocks
    $blkColor = pt_hex2rgb($bg)[0] > 127 ? 'e2e8f0' : '1e293b';
    $blkH = (int)($contentH * 0.06);
    for ($i = 0; $i < 3; $i++) {
        $bw = (int)($W * (0.5 + rand(-15,15)/100));
        pt_rounded_rect($im, $wpad, $wy + $i*($blkH+8), $bw, $blkH, 4, pt_color($im, $blkColor));
    }

    // Window shadow/border
    pt_rounded_rect_border($im, 0, 0, $W, $H, 0, pt_color($im, $st['border']), 1);
}

// ════════════════════════════════════════════════════════════════════════════
// TERMINAL PREVIEW TEMPLATES
// ════════════════════════════════════════════════════════════════════════════

function pt_render_terminal(GdImage $im, array $p, string $template): void {
    $W = imagesx($im); $H = imagesy($im);

    $themes = [
        'linux'   => ['bg'=>'300a24','body_bg'=>'2d0922','txt'=>'ffffff','prompt'=>'4af626','path'=>'1a8cff','comment'=>'6a6a6a','title_bg'=>'3d1033'],
        'modern'  => ['bg'=>'1e1e1e','body_bg'=>'1e1e1e','txt'=>'d4d4d4','prompt'=>'569cd6','path'=>'ce9178','comment'=>'6a9955','title_bg'=>'2d2d2d'],
        'hacker'  => ['bg'=>'000000','body_bg'=>'000000','txt'=>'00ff00','prompt'=>'00ff00','path'=>'00cc00','comment'=>'006600','title_bg'=>'001100'],
        'vscode'  => ['bg'=>'1e1e1e','body_bg'=>'1e1e1e','txt'=>'cccccc','prompt'=>'4ec9b0','path'=>'ce9178','comment'=>'6a9955','title_bg'=>'323232'],
        'minimal'      => ['bg'=>'0d1117','body_bg'=>'0d1117','txt'=>'c9d1d9','prompt'=>'79c0ff','path'=>'ffa657','comment'=>'8b949e','title_bg'=>'161b22'],
        // ── 10 new terminal templates ────────────────────────────
        'powerline'    => ['bg'=>'1a1a2e','body_bg'=>'1a1a2e','txt'=>'e0e0e0','prompt'=>'6272a4','path'=>'50fa7b','comment'=>'6272a4','title_bg'=>'282a36'],
        'fish_shell'   => ['bg'=>'102030','body_bg'=>'102030','txt'=>'e0f0f8','prompt'=>'4ac6e8','path'=>'79e6f3','comment'=>'5a8a9a','title_bg'=>'0a1a28'],
        'windows_cmd'  => ['bg'=>'0c0c0c','body_bg'=>'0c0c0c','txt'=>'cccccc','prompt'=>'cccccc','path'=>'cccccc','comment'=>'888888','title_bg'=>'0c0c0c'],
        'powershell'   => ['bg'=>'012456','body_bg'=>'012456','txt'=>'eeedf0','prompt'=>'ffffff','path'=>'f3f99d','comment'=>'6a9fce','title_bg'=>'001a3d'],
        'ubuntu_term'  => ['bg'=>'2c001e','body_bg'=>'300a24','txt'=>'ffffff','prompt'=>'00aa44','path'=>'4e9dc8','comment'=>'888888','title_bg'=>'3d1033'],
        'matrix'       => ['bg'=>'000000','body_bg'=>'000000','txt'=>'00ff00','prompt'=>'00aa00','path'=>'00ff00','comment'=>'005500','title_bg'=>'001100'],
        'amber'        => ['bg'=>'0a0700','body_bg'=>'0c0900','txt'=>'ffb000','prompt'=>'ffcc44','path'=>'ff8800','comment'=>'886600','title_bg'=>'060400'],
        'iterm2'       => ['bg'=>'1e1f26','body_bg'=>'1e1f26','txt'=>'d8d8d8','prompt'=>'80bfff','path'=>'e0c97a','comment'=>'5e6d7a','title_bg'=>'282932'],
        'p10k'         => ['bg'=>'1e1e2e','body_bg'=>'1e1e2e','txt'=>'cdd6f4','prompt'=>'89b4fa','path'=>'a6e3a1','comment'=>'6c7086','title_bg'=>'181825'],
        'dracula_term' => ['bg'=>'282a36','body_bg'=>'282a36','txt'=>'f8f8f2','prompt'=>'50fa7b','path'=>'ff79c6','comment'=>'6272a4','title_bg'=>'21222c'],
    ];
    $t = $themes[$template] ?? $themes['modern'];
    $titleH = (int)($H * 0.09);

    // Window
    pt_rounded_rect($im, 0, 0, $W, $H, 10, pt_color($im, $t['bg']));

    // Title bar
    pt_rounded_rect($im, 0, 0, $W, $titleH, 10, pt_color($im, $t['title_bg']));
    imagefilledrectangle($im, 0, (int)($titleH/2), $W, $titleH, pt_color($im, $t['title_bg']));

    // Window dots
    $dotR = (int)($H * 0.015);
    foreach ([['ff5f57',20],['febc2e',20+$dotR*3],['28c840',20+$dotR*6]] as [$dc,$dx]) {
        imagefilledellipse($im, $dx+$dotR, (int)($titleH/2), $dotR*2, $dotR*2, pt_color($im, $dc));
    }
    // Terminal title
    $tsz = (int)($H * 0.025);
    $ttext = $p['filename'] ?: 'Terminal';
    $tbbox = imagettfbbox($tsz, 0, PT_FONT_REG, $ttext);
    $ttw = abs($tbbox[2]-$tbbox[0]);
    imagettftext($im, $tsz, 0, (int)(($W-$ttw)/2), (int)($titleH*0.66), pt_color($im, $t['txt'] . '99'), PT_FONT_REG, $ttext);

    // Body background
    imagefilledrectangle($im, 0, $titleH, $W, $H, pt_color($im, $t['body_bg']));

    // Terminal content
    $pad = 30; $curY = $titleH + $pad;
    $fsz = (int)($H * 0.033);
    $lineH = (int)($fsz * 1.6);

    // Render command lines
    $lines = [];
    if ($p['line1']) $lines[] = ['prompt' => true,  'text' => $p['line1']];
    if ($p['line2']) $lines[] = ['prompt' => false, 'text' => $p['line2']];
    if ($p['line3']) $lines[] = ['prompt' => true,  'text' => $p['line3']];
    if ($p['line4']) $lines[] = ['prompt' => false, 'text' => $p['line4']];

    if (empty($lines)) {
        // Default terminal output from heading/description
        if ($p['heading']) {
            $lines[] = ['prompt' => true,  'text' => $p['heading']];
        }
        if ($p['description']) {
            $lines[] = ['prompt' => false, 'text' => $p['description']];
        }
        $lines[] = ['prompt' => true,  'text' => ''];
    }

    $promptStr = $template === 'hacker' ? 'root@kali:~# ' : '$ ';
    foreach ($lines as $line) {
        if ($curY + $lineH > $H - $pad) break;
        if ($line['prompt']) {
            $promptColor = pt_color($im, $t['prompt']);
            $pbbox = imagettfbbox($fsz, 0, PT_FONT_BOLD, $promptStr);
            $pw = abs($pbbox[2]-$pbbox[0]);
            imagettftext($im, $fsz, 0, $pad, $curY + $fsz, $promptColor, PT_FONT_BOLD, $promptStr);
            if ($line['text']) {
                imagettftext($im, $fsz, 0, $pad + $pw, $curY + $fsz, pt_color($im, $t['txt']), PT_FONT_REG, $line['text']);
            }
            // Blinking cursor for last line if empty
            if ($line['text'] === '') {
                $cx = $pad + $pw;
                imagefilledrectangle($im, $cx, $curY + 2, $cx + (int)($fsz * 0.55), $curY + $fsz + 4, pt_color($im, $t['prompt']));
            }
        } else {
            imagettftext($im, $fsz, 0, $pad, $curY + $fsz, pt_color($im, $t['txt'] . 'cc'), PT_FONT_REG, $line['text']);
        }
        $curY += $lineH;
    }
}

// ════════════════════════════════════════════════════════════════════════════
// PROFILE CARD TEMPLATES
// ════════════════════════════════════════════════════════════════════════════

$PROFILE_SPECS = [
    'team_member'   => ['bg'=>'ffffff','bg2'=>null,'card_bg'=>'f9fafb','card_border'=>'e5e7eb','name_color'=>'111827','role_color'=>'6b7280','accent'=>'4f46e5','stat_color'=>'111827','stat_label_color'=>'9ca3af','radius'=>16],
    'author'        => ['bg'=>'fafaf9','bg2'=>null,'card_bg'=>'ffffff','card_border'=>'e7e5e4','name_color'=>'1c1917','role_color'=>'78716c','accent'=>'ea580c','stat_color'=>'1c1917','stat_label_color'=>'a8a29e','radius'=>12],
    'developer'     => ['bg'=>'0d1117','bg2'=>'161b22','card_bg'=>'161b22','card_border'=>'30363d','name_color'=>'e6edf3','role_color'=>'8b949e','accent'=>'3fb950','stat_color'=>'e6edf3','stat_label_color'=>'6e7781','radius'=>8],
    'business'      => ['bg'=>'f8fafc','bg2'=>null,'card_bg'=>'ffffff','card_border'=>'e2e8f0','name_color'=>'0f172a','role_color'=>'64748b','accent'=>'0ea5e9','stat_color'=>'0f172a','stat_label_color'=>'94a3b8','radius'=>8],
    'creator'     => ['bg'=>'fdf2f8','bg2'=>null,'card_bg'=>'ffffff','card_border'=>'fce7f3','name_color'=>'831843','role_color'=>'9d174d','accent'=>'db2777','stat_color'=>'831843','stat_label_color'=>'be185d','radius'=>20],
    'speaker'      => ['bg'=>'1e1b4b','bg2'=>'312e81','card_bg'=>'1e1b4b','card_border'=>'4338ca','name_color'=>'e0e7ff','role_color'=>'a5b4fc','accent'=>'818cf8','stat_color'=>'e0e7ff','stat_label_color'=>'6366f1','radius'=>16],
    // ── 9 new profile templates ──────────────────────────────────
    'minimal_white' => ['bg'=>'ffffff','bg2'=>null,'card_bg'=>'ffffff','card_border'=>'f0f0f0','name_color'=>'000000','role_color'=>'666666','accent'=>'000000','stat_color'=>'000000','stat_label_color'=>'999999','radius'=>0],
    'dark_glass'    => ['bg'=>'0d1117','bg2'=>'161b22','card_bg'=>'161b22','card_border'=>'30363d','name_color'=>'ffffff','role_color'=>'8b949e','accent'=>'58a6ff','stat_color'=>'e6edf3','stat_label_color'=>'8b949e','radius'=>16],
    'gradient_card' => ['bg'=>'667eea','bg2'=>'764ba2','card_bg'=>'ffffff','card_border'=>'ffffff','name_color'=>'1a202c','role_color'=>'4a5568','accent'=>'667eea','stat_color'=>'1a202c','stat_label_color'=>'718096','radius'=>20],
    'resume_clean'  => ['bg'=>'f8f9fa','bg2'=>null,'card_bg'=>'ffffff','card_border'=>'dee2e6','name_color'=>'212529','role_color'=>'495057','accent'=>'0d6efd','stat_color'=>'212529','stat_label_color'=>'6c757d','radius'=>4],
    'podcast_card'  => ['bg'=>'1a1a2e','bg2'=>null,'card_bg'=>'16213e','card_border'=>'e94560','name_color'=>'ffffff','role_color'=>'a8b2d8','accent'=>'e94560','stat_color'=>'ffffff','stat_label_color'=>'627b9a','radius'=>12],
    'athlete'       => ['bg'=>'0a0a0a','bg2'=>null,'card_bg'=>'1a1a1a','card_border'=>'ff4500','name_color'=>'ffffff','role_color'=>'ff8c42','accent'=>'ff4500','stat_color'=>'ffffff','stat_label_color'=>'999999','radius'=>0],
    'musician'      => ['bg'=>'0d0208','bg2'=>null,'card_bg'=>'140310','card_border'=>'ff006e','name_color'=>'ffffff','role_color'=>'ff8fb1','accent'=>'ff006e','stat_color'=>'ffffff','stat_label_color'=>'cc6699','radius'=>12],
    'freelancer'    => ['bg'=>'fff8f0','bg2'=>null,'card_bg'=>'ffffff','card_border'=>'f0d8c0','name_color'=>'2d1b00','role_color'=>'7a5c3a','accent'=>'d97706','stat_color'=>'2d1b00','stat_label_color'=>'a0856b','radius'=>12],
    'noir'          => ['bg'=>'0a0a0a','bg2'=>null,'card_bg'=>'1a1a1a','card_border'=>'333333','name_color'=>'ffffff','role_color'=>'aaaaaa','accent'=>'ffffff','stat_color'=>'ffffff','stat_label_color'=>'777777','radius'=>0],
];

function pt_render_profile(GdImage $im, array $p, array $s): void {
    $W = imagesx($im); $H = imagesy($im);

    if (!empty($s['bg2'])) {
        pt_gradient_v($im, 0, 0, $W, $H, $s['bg'], $s['bg2']);
    } else {
        imagefill($im, 0, 0, pt_color($im, $s['bg']));
    }

    $pad = (int)($W * 0.06);
    $cw = $W - $pad*2; $ch = $H - $pad*2;
    pt_rounded_rect($im, $pad, $pad, $cw, $ch, $s['radius'], pt_color($im, $s['card_bg']));
    pt_rounded_rect_border($im, $pad, $pad, $cw, $ch, $s['radius'], pt_color($im, $s['card_border']), 1);

    $ip = (int)($W * 0.05);
    $ox = $pad + $ip; $oy = $pad + $ip; $ow = $cw - $ip*2;

    // Avatar placeholder
    $avSize = (int)($H * 0.28);
    $avX = $ox; $avY = $oy;
    pt_rounded_rect($im, $avX, $avY, $avSize, $avSize, (int)($avSize/2), pt_color($im, $s['accent']));
    // Initials
    $initials = '';
    $name = $p['heading'] ?: $p['author'];
    foreach (explode(' ', trim($name)) as $word) {
        if ($word) $initials .= strtoupper($word[0]);
    }
    $initials = substr($initials, 0, 2);
    if (!$initials) $initials = 'U';
    $isz = (int)($avSize * 0.38);
    $ibbox = imagettfbbox($isz, 0, PT_FONT_BOLD, $initials);
    $itw = abs($ibbox[2]-$ibbox[0]); $ith = abs($ibbox[5]-$ibbox[1]);
    imagettftext($im, $isz, 0, $avX+(int)(($avSize-$itw)/2), $avY+(int)(($avSize+$ith)/2), pt_color($im, 'ffffff'), PT_FONT_BOLD, $initials);

    // Name and role (to the right of avatar)
    $tx = $avX + $avSize + (int)($W*0.04);
    $ty = $avY;
    $tw = $ow - $avSize - (int)($W*0.04);

    // Badge
    if ($p['badge'] || $p['category_label']) {
        $btext = $p['badge'] ?: $p['category_label'];
        pt_badge($im, PT_FONT_REG, $btext, $tx, $ty, $s['accent'], 'ffffff', 100, (int)($H*0.025));
        $ty += (int)($H * 0.07);
    } else {
        $ty += (int)($H * 0.02);
    }

    // Name
    $nsz = (int)($H * 0.07);
    $ty = pt_text_block($im, PT_FONT_BOLD, $nsz, $tx, $ty, $s['name_color'], $name ?: 'Your Name', $tw, $nsz*1.3, 'left', 2);
    $ty += (int)($H * 0.01);

    // Role/title
    $rsz = (int)($H * 0.038);
    $ty = pt_text_block($im, PT_FONT_REG, $rsz, $tx, $ty, $s['role_color'], $p['role'] ?: $p['subheading'] ?: 'Developer', $tw, $rsz*1.5, 'left', 2);
    $ty += (int)($H * 0.03);

    // Description
    if ($p['description']) {
        $dsz = (int)($H * 0.03);
        pt_text_block($im, PT_FONT_REG, $dsz, $tx, $ty, $s['role_color'], $p['description'], $tw, $dsz*1.6, 'left', 3);
    }

    // Stats row
    $stats = [
        [$p['stat1_value'], $p['stat1_label']],
        [$p['stat2_value'], $p['stat2_label']],
        [$p['stat3_value'], $p['stat3_label']],
    ];
    $statsY = $pad + $ch - $ip - (int)($H * 0.12);
    imagefilledrectangle($im, $ox, $statsY - 12, $ox+$ow, $statsY - 11, pt_color($im, $s['card_border']));
    $statW = (int)($ow / 3);
    foreach ($stats as $i => [$val, $label]) {
        if (!$val) continue;
        $sx = $ox + $i * $statW;
        $vsz = (int)($H * 0.05);
        $lsz = (int)($H * 0.025);
        pt_text_block($im, PT_FONT_BOLD, $vsz, $sx, $statsY, $s['stat_color'], $val, $statW, $vsz*1.3, 'center', 1);
        pt_text_block($im, PT_FONT_REG, $lsz, $sx, $statsY + $vsz + 4, $s['stat_label_color'], $label, $statW, $lsz*1.4, 'center', 1);
    }

    // Username
    if ($p['username']) {
        $usz = (int)($H * 0.028);
        pt_text_block($im, PT_FONT_REG, $usz, $ox, $pad + $ch - $ip/2 - $usz, $s['role_color'], $p['username'], $ow/2, $usz*1.4, 'left', 1);
    }
    if ($p['website']) {
        $wsz = (int)($H * 0.028);
        pt_text_block($im, PT_FONT_REG, $wsz, $ox + (int)($ow/2), $pad + $ch - $ip/2 - $wsz, $s['accent'], $p['website'], $ow/2, $wsz*1.4, 'right', 1);
    }
}

// ════════════════════════════════════════════════════════════════════════════
// CODE SNIPPET TEMPLATES
// ════════════════════════════════════════════════════════════════════════════

function pt_render_code(GdImage $im, array $p, string $template): void {
    $W = imagesx($im); $H = imagesy($im);

    $themes = [
        'vscode'  => ['bg'=>'1e1e1e','header_bg'=>'323232','gutter_bg'=>'1e1e1e','gutter_color'=>'858585','txt'=>'d4d4d4','string'=>'ce9178','keyword'=>'569cd6','comment'=>'6a9955','number'=>'b5cea8','function'=>'dcdcaa','variable'=>'9cdcfe','operator'=>'d4d4d4','tab_active'=>'1e1e1e','tab_inactive'=>'2d2d2d'],
        'github'  => ['bg'=>'ffffff','header_bg'=>'f6f8fa','gutter_bg'=>'f6f8fa','gutter_color'=>'6e7781','txt'=>'24292f','string'=>'0a3069','keyword'=>'cf222e','comment'=>'6e7781','number'=>'0550ae','function'=>'8250df','variable'=>'24292f','operator'=>'24292f','tab_active'=>'ffffff','tab_inactive'=>'f6f8fa'],
        'monokai' => ['bg'=>'272822','header_bg'=>'3e3d32','gutter_bg'=>'2c2b26','gutter_color'=>'75715e','txt'=>'f8f8f2','string'=>'e6db74','keyword'=>'f92672','comment'=>'75715e','number'=>'ae81ff','function'=>'a6e22e','variable'=>'fd971f','operator'=>'f8f8f2','tab_active'=>'272822','tab_inactive'=>'3e3d32'],
        'nord'    => ['bg'=>'2e3440','header_bg'=>'3b4252','gutter_bg'=>'3b4252','gutter_color'=>'4c566a','txt'=>'d8dee9','string'=>'a3be8c','keyword'=>'81a1c1','comment'=>'4c566a','number'=>'b48ead','function'=>'88c0d0','variable'=>'d8dee9','operator'=>'81a1c1','tab_active'=>'2e3440','tab_inactive'=>'3b4252'],
        'dracula' => ['bg'=>'282a36','header_bg'=>'343746','gutter_bg'=>'343746','gutter_color'=>'6272a4','txt'=>'f8f8f2','string'=>'f1fa8c','keyword'=>'ff79c6','comment'=>'6272a4','number'=>'bd93f9','function'=>'50fa7b','variable'=>'8be9fd','operator'=>'ff79c6','tab_active'=>'282a36','tab_inactive'=>'343746'],
        'minimal'    => ['bg'=>'f8f8f8','header_bg'=>'f0f0f0','gutter_bg'=>'f0f0f0','gutter_color'=>'aaaaaa','txt'=>'333333','string'=>'448c27','keyword'=>'4b69c6','comment'=>'aaaaaa','number'=>'9c5d27','function'=>'7a3e9d','variable'=>'333333','operator'=>'333333','tab_active'=>'f8f8f8','tab_inactive'=>'f0f0f0'],
        // ── 9 new code themes ────────────────────────────────────
        'one_dark'   => ['bg'=>'282c34','header_bg'=>'21252b','gutter_bg'=>'21252b','gutter_color'=>'495162','txt'=>'abb2bf','string'=>'98c379','keyword'=>'c678dd','comment'=>'5c6370','number'=>'d19a66','function'=>'61afef','variable'=>'e06c75','operator'=>'56b6c2','tab_active'=>'282c34','tab_inactive'=>'21252b'],
        'synthwave'  => ['bg'=>'262335','header_bg'=>'1a1a2e','gutter_bg'=>'1a1a2e','gutter_color'=>'495495','txt'=>'ffffff','string'=>'ff8b39','keyword'=>'ff6bcb','comment'=>'848bbd','number'=>'f97583','function'=>'36f9f6','variable'=>'fede5d','operator'=>'ff6bcb','tab_active'=>'262335','tab_inactive'=>'1a1a2e'],
        'gruvbox'    => ['bg'=>'282828','header_bg'=>'1d2021','gutter_bg'=>'282828','gutter_color'=>'504945','txt'=>'ebdbb2','string'=>'b8bb26','keyword'=>'fb4934','comment'=>'928374','number'=>'d3869b','function'=>'fabd2f','variable'=>'83a598','operator'=>'fe8019','tab_active'=>'282828','tab_inactive'=>'1d2021'],
        'solarized'  => ['bg'=>'002b36','header_bg'=>'073642','gutter_bg'=>'073642','gutter_color'=>'586e75','txt'=>'839496','string'=>'2aa198','keyword'=>'859900','comment'=>'586e75','number'=>'d33682','function'=>'268bd2','variable'=>'b58900','operator'=>'cb4b16','tab_active'=>'002b36','tab_inactive'=>'073642'],
        'tokyo_night'=> ['bg'=>'1a1b2e','header_bg'=>'16161e','gutter_bg'=>'16161e','gutter_color'=>'3b3d57','txt'=>'a9b1d6','string'=>'9ece6a','keyword'=>'7aa2f7','comment'=>'565f89','number'=>'ff9e64','function'=>'7dcfff','variable'=>'f7768e','operator'=>'bb9af7','tab_active'=>'1a1b2e','tab_inactive'=>'16161e'],
        'catppuccin' => ['bg'=>'1e1e2e','header_bg'=>'181825','gutter_bg'=>'181825','gutter_color'=>'45475a','txt'=>'cdd6f4','string'=>'a6e3a1','keyword'=>'cba6f7','comment'=>'6c7086','number'=>'fab387','function'=>'89b4fa','variable'=>'f38ba8','operator'=>'89dceb','tab_active'=>'1e1e2e','tab_inactive'=>'181825'],
        'atom_light' => ['bg'=>'fafafa','header_bg'=>'f0f0f0','gutter_bg'=>'f0f0f0','gutter_color'=>'9d9d9f','txt'=>'383a42','string'=>'50a14f','keyword'=>'a626a4','comment'=>'a0a1a7','number'=>'986801','function'=>'4078f2','variable'=>'e45649','operator'=>'0184bc','tab_active'=>'fafafa','tab_inactive'=>'f0f0f0'],
        'sublime'    => ['bg'=>'23241f','header_bg'=>'272822','gutter_bg'=>'272822','gutter_color'=>'75715e','txt'=>'f8f8f2','string'=>'e6db74','keyword'=>'f92672','comment'=>'75715e','number'=>'ae81ff','function'=>'a6e22e','variable'=>'66d9ef','operator'=>'f92672','tab_active'=>'23241f','tab_inactive'=>'272822'],
        'jetbrains'  => ['bg'=>'2b2b2b','header_bg'=>'3c3f41','gutter_bg'=>'313335','gutter_color'=>'606366','txt'=>'a9b7c6','string'=>'6a8759','keyword'=>'cc7832','comment'=>'629755','number'=>'6897bb','function'=>'ffc66d','variable'=>'a9b7c6','operator'=>'a9b7c6','tab_active'=>'2b2b2b','tab_inactive'=>'3c3f41'],
    ];
    $t = $themes[$template] ?? $themes['vscode'];

    $headerH = (int)($H * 0.12);
    $gutterW = (int)($W * 0.055);
    $fsz     = (int)($H * 0.038);
    $lineH   = (int)($fsz * 1.7);
    $pad     = 16;

    // Background
    imagefill($im, 0, 0, pt_color($im, $t['bg']));

    // Header/title bar
    imagefilledrectangle($im, 0, 0, $W, $headerH, pt_color($im, $t['header_bg']));
    imagefilledrectangle($im, 0, $headerH-1, $W, $headerH, pt_color($im, $t['gutter_color'] . '55'));

    // Window dots
    $dotR = (int)($H * 0.015);
    foreach ([['ff5f57',16],['febc2e',16+$dotR*3],['28c840',16+$dotR*6]] as [$dc,$dx]) {
        imagefilledellipse($im, $dx+$dotR, (int)($headerH/2), $dotR*2, $dotR*2, pt_color($im, $dc));
    }

    // File tab
    $filename = $p['filename'] ?: 'index.js';
    $tbsz = (int)($H * 0.026);
    $tbbbox = imagettfbbox($tbsz, 0, PT_FONT_REG, $filename);
    $tbw = abs($tbbbox[2]-$tbbbox[0]) + 24;
    $tabX = (int)($W * 0.15); $tabY = (int)($headerH * 0.2);
    $tabH2 = (int)($headerH * 0.65);
    imagefilledrectangle($im, $tabX, $tabY, $tabX+$tbw, $tabY+$tabH2, pt_color($im, $t['tab_active']));
    imagefilledrectangle($im, $tabX, $tabY+$tabH2-2, $tabX+$tbw, $tabY+$tabH2, pt_color($im, $t['keyword']));
    imagettftext($im, $tbsz, 0, $tabX+12, $tabY+(int)($tabH2*0.68), pt_color($im, $t['txt']), PT_FONT_REG, $filename);

    // Gutter
    imagefilledrectangle($im, 0, $headerH, $gutterW, $H, pt_color($im, $t['gutter_bg']));
    imagefilledrectangle($im, $gutterW, $headerH, $gutterW+1, $H, pt_color($im, $t['gutter_color'] . '44'));

    // Code lines — parse p['code'] for display
    $rawCode = $p['code'] ?: 'function hello(name) {' . "\n" . '  return `Hello, ${name}!`;' . "\n" . '}' . "\n" . '' . "\n" . '// Call the function' . "\n" . 'console.log(hello("World"));';
    $rawCode = str_replace(['\\n', '\n'], "\n", $rawCode);
    $codeLines = explode("\n", $rawCode);

    $codeX = $gutterW + $pad;
    $codeW = $W - $codeX - $pad;
    $curY  = $headerH + $pad;

    foreach (array_slice($codeLines, 0, 20) as $ln => $codeLine) {
        if ($curY + $lineH > $H - $pad) break;
        // Line number
        $lnStr = (string)($ln + 1);
        $lnbbox = imagettfbbox($fsz * 0.85, 0, PT_FONT_REG, $lnStr);
        $lnw = abs($lnbbox[2]-$lnbbox[0]);
        imagettftext($im, $fsz * 0.85, 0, $gutterW - $lnw - 6, $curY + $fsz, pt_color($im, $t['gutter_color']), PT_FONT_REG, $lnStr);
        // Code (simple colorization by first token)
        if ($codeLine === '' || $codeLine === ' ') {
            $curY += $lineH;
            continue;
        }
        $trimmed = ltrim($codeLine);
        $indent = strlen($codeLine) - strlen($trimmed);
        // Detect token type
        $lineColor = $t['txt'];
        if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '#')) {
            $lineColor = $t['comment'];
        } elseif (preg_match('/^(function|return|const|let|var|if|else|for|while|class|import|export|from|def|public|private|static|void)\b/', $trimmed)) {
            $lineColor = $t['keyword'];
        } elseif (str_contains($trimmed, '"') || str_contains($trimmed, "'") || str_contains($trimmed, '`')) {
            $lineColor = $t['string'];
        } elseif (is_numeric(trim($trimmed, ' ;,'))) {
            $lineColor = $t['number'];
        }
        $displayLine = $codeLine;
        imagettftext($im, $fsz, 0, $codeX, $curY + $fsz, pt_color($im, $lineColor), PT_FONT_REG, $displayLine);
        $curY += $lineH;
    }
}

// ════════════════════════════════════════════════════════════════════════════
// DASHBOARD PREVIEW TEMPLATES
// ════════════════════════════════════════════════════════════════════════════

$DASHBOARD_SPECS = [
    'analytics' => ['bg'=>'0f172a','sidebar_bg'=>'1e293b','card_bg'=>'1e293b','card_border'=>'334155','txt'=>'f8fafc','muted'=>'94a3b8','accent'=>'3b82f6','positive'=>'22c55e','negative'=>'ef4444'],
    'saas'      => ['bg'=>'ffffff','sidebar_bg'=>'f8fafc','card_bg'=>'ffffff','card_border'=>'e2e8f0','txt'=>'0f172a','muted'=>'64748b','accent'=>'6366f1','positive'=>'10b981','negative'=>'f43f5e'],
    'stats'     => ['bg'=>'18181b','sidebar_bg'=>'27272a','card_bg'=>'27272a','card_border'=>'3f3f46','txt'=>'fafafa','muted'=>'a1a1aa','accent'=>'f59e0b','positive'=>'4ade80','negative'=>'f87171'],
    'kpi'       => ['bg'=>'030712','sidebar_bg'=>'111827','card_bg'=>'111827','card_border'=>'1f2937','txt'=>'f9fafb','muted'=>'6b7280','accent'=>'a855f7','positive'=>'34d399','negative'=>'fb7185'],
    'revenue'   => ['bg'=>'fff7ed','sidebar_bg'=>'fff7ed','card_bg'=>'ffffff','card_border'=>'fed7aa','txt'=>'1c1917','muted'=>'78716c','accent'=>'ea580c','positive'=>'059669','negative'=>'dc2626'],
    'admin'        => ['bg'=>'f1f5f9','sidebar_bg'=>'1e293b','card_bg'=>'ffffff','card_border'=>'e2e8f0','txt'=>'0f172a','muted'=>'64748b','accent'=>'0284c7','positive'=>'16a34a','negative'=>'dc2626'],
    // ── 9 new dashboard templates ────────────────────────────────
    'marketing'    => ['bg'=>'0d2137','sidebar_bg'=>'0a1a2e','card_bg'=>'0a1a2e','card_border'=>'1e3a5f','txt'=>'e2f0ff','muted'=>'7aa5cc','accent'=>'00d4ff','positive'=>'00ff88','negative'=>'ff4444'],
    'crypto'       => ['bg'=>'0a0e1a','sidebar_bg'=>'10152a','card_bg'=>'10152a','card_border'=>'1e2a45','txt'=>'e8eaf6','muted'=>'7986cb','accent'=>'f7b731','positive'=>'26de81','negative'=>'fc5c65'],
    'fitness'      => ['bg'=>'0d0d0d','sidebar_bg'=>'1a1a1a','card_bg'=>'1a1a1a','card_border'=>'2d2d2d','txt'=>'ffffff','muted'=>'888888','accent'=>'ff4500','positive'=>'ff6b35','negative'=>'e74c3c'],
    'ecommerce'    => ['bg'=>'fafafa','sidebar_bg'=>'ffffff','card_bg'=>'ffffff','card_border'=>'eeeeee','txt'=>'212121','muted'=>'757575','accent'=>'00897b','positive'=>'43a047','negative'=>'e53935'],
    'social_dash'  => ['bg'=>'f0e6ff','sidebar_bg'=>'e8d5ff','card_bg'=>'ffffff','card_border'=>'d4a6ff','txt'=>'1a0033','muted'=>'6b3399','accent'=>'9c27b0','positive'=>'4caf50','negative'=>'f44336'],
    'devops'       => ['bg'=>'0c1920','sidebar_bg'=>'0f2030','card_bg'=>'0f2030','card_border'=>'1a3a50','txt'=>'c8e6f0','muted'=>'5a8a9e','accent'=>'00bcd4','positive'=>'4caf50','negative'=>'f44336'],
    'project_dash' => ['bg'=>'f7f8fc','sidebar_bg'=>'2d3436','card_bg'=>'ffffff','card_border'=>'e4e7ef','txt'=>'2d3436','muted'=>'636e72','accent'=>'6c63ff','positive'=>'00b894','negative'=>'d63031'],
    'finance'      => ['bg'=>'001233','sidebar_bg'=>'001a4d','card_bg'=>'001a4d','card_border'=>'002a70','txt'=>'e8f0ff','muted'=>'7a9acc','accent'=>'c9a84c','positive'=>'2ecc71','negative'=>'e74c3c'],
    'monitoring'   => ['bg'=>'0e1117','sidebar_bg'=>'161b22','card_bg'=>'161b22','card_border'=>'30363d','txt'=>'c9d1d9','muted'=>'8b949e','accent'=>'f0883e','positive'=>'3fb950','negative'=>'f85149'],
];

function pt_render_dashboard(GdImage $im, array $p, array $s): void {
    $W = imagesx($im); $H = imagesy($im);
    $sideW = (int)($W * 0.18);
    $headerH = (int)($H * 0.1);

    // Main background
    imagefill($im, 0, 0, pt_color($im, $s['bg']));

    // Sidebar
    imagefilledrectangle($im, 0, 0, $sideW, $H, pt_color($im, $s['sidebar_bg']));
    // Sidebar nav items
    $nsz = (int)($H * 0.028);
    $navItems = ['Dashboard', 'Analytics', 'Reports', 'Users', 'Settings'];
    foreach ($navItems as $ni => $navItem) {
        $ny = $headerH + $ni * (int)($H * 0.1) + (int)($H * 0.03);
        if ($ni === 0) {
            imagefilledrectangle($im, 0, $ny - 8, $sideW - 1, $ny + $nsz + 12, pt_color($im, $s['accent'] . '22'));
            imagefilledrectangle($im, 0, $ny - 8, 3, $ny + $nsz + 12, pt_color($im, $s['accent']));
        }
        $c = $ni === 0 ? $s['txt'] : $s['muted'];
        imagettftext($im, $nsz, 0, 16, $ny + $nsz, pt_color($im, $c), PT_FONT_REG, $navItem);
    }

    // Header bar
    imagefilledrectangle($im, $sideW, 0, $W, $headerH, pt_color($im, $s['sidebar_bg']));
    imagefilledrectangle($im, $sideW, $headerH - 1, $W, $headerH, pt_color($im, $s['card_border']));
    $htitle = $p['heading'] ?: 'Analytics Dashboard';
    $hsz = (int)($H * 0.038);
    imagettftext($im, $hsz, 0, $sideW + 20, (int)($headerH * 0.65), pt_color($im, $s['txt']), PT_FONT_BOLD, $htitle);

    // Metric cards
    $cx = $sideW + 16; $cy = $headerH + 16;
    $cpad = 12;
    $cardW = (int)(($W - $cx - 16 - $cpad * 2) / 3);
    $cardH = (int)($H * 0.2);
    $metrics = [
        [$p['metric1'], $p['metric1_label'], $s['positive']],
        [$p['metric2'], $p['metric2_label'], $s['accent']],
        [$p['metric3'], $p['metric3_label'], $s['positive']],
    ];
    foreach ($metrics as $mi => [$val, $label, $mc]) {
        $mx = $cx + $mi * ($cardW + $cpad);
        pt_rounded_rect($im, $mx, $cy, $cardW, $cardH, 8, pt_color($im, $s['card_bg']));
        pt_rounded_rect_border($im, $mx, $cy, $cardW, $cardH, 8, pt_color($im, $s['card_border']), 1);
        $lsz = (int)($H * 0.024);
        $vsz = (int)($H * 0.048);
        imagettftext($im, $lsz, 0, $mx + 14, $cy + $lsz + 10, pt_color($im, $s['muted']), PT_FONT_REG, $label);
        imagettftext($im, $vsz, 0, $mx + 14, $cy + $lsz + 10 + $vsz + 8, pt_color($im, $s['txt']), PT_FONT_BOLD, $val);
        // Mini accent bar
        imagefilledrectangle($im, $mx + 14, $cy + $cardH - 8, $mx + $cardW - 14, $cy + $cardH - 5, pt_color($im, $mc));
    }

    // Bar chart
    $chartY = $cy + $cardH + 16;
    $chartH = $H - $chartY - 20;
    $chartX = $cx; $chartW = (int)(($W - $cx - 16) * 0.55);
    pt_rounded_rect($im, $chartX, $chartY, $chartW, $chartH, 8, pt_color($im, $s['card_bg']));
    pt_rounded_rect_border($im, $chartX, $chartY, $chartW, $chartH, 8, pt_color($im, $s['card_border']), 1);
    $csz = (int)($H * 0.023);
    imagettftext($im, $csz, 0, $chartX+12, $chartY+$csz+10, pt_color($im, $s['txt']), PT_FONT_BOLD, $p['description'] ?: 'Monthly Overview');
    // Bar chart bars
    $bars = [65, 80, 55, 90, 75, 85, 70];
    $bw = (int)(($chartW - 40) / count($bars)) - 8;
    $bMaxH = $chartH - 50;
    foreach ($bars as $bi => $bv) {
        $bx = $chartX + 20 + $bi * ($bw + 8);
        $bh = (int)($bMaxH * ($bv / 100));
        $by = $chartY + $chartH - 20 - $bh;
        pt_rounded_rect($im, $bx, $by, $bw, $bh, 3, pt_color($im, $bi === 3 ? $s['accent'] : $s['accent'] . '66'));
    }

    // Small stats card (right side)
    $scX = $cx + $chartW + 12;
    $scW = $W - $scX - 16;
    pt_rounded_rect($im, $scX, $chartY, $scW, $chartH, 8, pt_color($im, $s['card_bg']));
    pt_rounded_rect_border($im, $scX, $chartY, $scW, $chartH, 8, pt_color($im, $s['card_border']), 1);
    imagettftext($im, $csz, 0, $scX+12, $chartY+$csz+10, pt_color($im, $s['txt']), PT_FONT_BOLD, 'Quick Stats');
    // Stat rows
    $srows = [['Active Users', '1,284', $s['positive']], ['Sessions', '3,891', $s['accent']], ['Bounce Rate', '24.3%', $s['negative']], ['Avg. Time', '4m 32s', $s['accent']]];
    foreach ($srows as $si => [$sl, $sv, $sc]) {
        $sry = $chartY + 50 + $si * (int)($chartH * 0.18);
        imagettftext($im, (int)($H*0.022), 0, $scX+12, $sry+(int)($H*0.022), pt_color($im, $s['muted']), PT_FONT_REG, $sl);
        $svbbox = imagettfbbox((int)($H*0.03), 0, PT_FONT_BOLD, $sv);
        $svw = abs($svbbox[2]-$svbbox[0]);
        imagettftext($im, (int)($H*0.03), 0, $scX+$scW-$svw-12, $sry+(int)($H*0.022), pt_color($im, $s['txt']), PT_FONT_BOLD, $sv);
        imagefilledrectangle($im, $scX+12, $sry+(int)($H*0.03)+4, $scX+$scW-12, $sry+(int)($H*0.03)+5, pt_color($im, $s['card_border']));
    }
}

// ════════════════════════════════════════════════════════════════════════════
// DOCUMENTATION TEMPLATES
// ════════════════════════════════════════════════════════════════════════════

$DOC_SPECS = [
    'api'       => ['bg'=>'ffffff','sidebar_bg'=>'18181b','card_bg'=>'f4f4f5','accent'=>'6366f1','txt'=>'09090b','muted'=>'71717a','method_get'=>'22c55e','method_post'=>'3b82f6','method_put'=>'f59e0b','method_del'=>'ef4444'],
    'readme'    => ['bg'=>'ffffff','sidebar_bg'=>'ffffff','card_bg'=>'f6f8fa','accent'=>'0969da','txt'=>'1f2328','muted'=>'656d76','method_get'=>'1a7f37','method_post'=>'0550ae','method_put'=>'9a6700','method_del'=>'cf222e'],
    'changelog' => ['bg'=>'0f172a','sidebar_bg'=>'0f172a','card_bg'=>'1e293b','accent'=>'a855f7','txt'=>'f8fafc','muted'=>'94a3b8','method_get'=>'4ade80','method_post'=>'60a5fa','method_put'=>'fbbf24','method_del'=>'f87171'],
    'product'   => ['bg'=>'fafafa','sidebar_bg'=>'f4f4f5','card_bg'=>'ffffff','accent'=>'0ea5e9','txt'=>'0a0a0a','muted'=>'737373','method_get'=>'16a34a','method_post'=>'2563eb','method_put'=>'d97706','method_del'=>'dc2626'],
    'developer' => ['bg'=>'1a1a2e','sidebar_bg'=>'16213e','card_bg'=>'0f3460','accent'=>'e94560','txt'=>'ffffff','muted'=>'a8b2d8','method_get'=>'4caf50','method_post'=>'2196f3','method_put'=>'ff9800','method_del'=>'f44336'],
    'knowledge'     => ['bg'=>'ffffff','sidebar_bg'=>'fafafa','card_bg'=>'f9f9f9','accent'=>'10b981','txt'=>'111827','muted'=>'6b7280','method_get'=>'059669','method_post'=>'2563eb','method_put'=>'d97706','method_del'=>'dc2626'],
    // ── 9 new documentation templates ───────────────────────────
    'tutorial'      => ['bg'=>'fff9f0','sidebar_bg'=>'1c1c1c','card_bg'=>'fff9f0','accent'=>'f59e0b','txt'=>'1c1c1c','muted'=>'6b5b45','method_get'=>'16a34a','method_post'=>'2563eb','method_put'=>'d97706','method_del'=>'dc2626'],
    'component_doc' => ['bg'=>'fafafa','sidebar_bg'=>'2d2d2d','card_bg'=>'ffffff','accent'=>'818cf8','txt'=>'111827','muted'=>'6b7280','method_get'=>'059669','method_post'=>'4f46e5','method_put'=>'d97706','method_del'=>'dc2626'],
    'library_pkg'   => ['bg'=>'0a0a0a','sidebar_bg'=>'141414','card_bg'=>'1a1a1a','accent'=>'c2410c','txt'=>'e5e5e5','muted'=>'737373','method_get'=>'4ade80','method_post'=>'60a5fa','method_put'=>'fbbf24','method_del'=>'f87171'],
    'cli_doc'       => ['bg'=>'0d1117','sidebar_bg'=>'0d1117','card_bg'=>'161b22','accent'=>'3fb950','txt'=>'c9d1d9','muted'=>'8b949e','method_get'=>'3fb950','method_post'=>'58a6ff','method_put'=>'ffa657','method_del'=>'ff7b72'],
    'guide_doc'     => ['bg'=>'fff7ed','sidebar_bg'=>'fff7ed','card_bg'=>'ffffff','accent'=>'ea580c','txt'=>'1c1917','muted'=>'78716c','method_get'=>'16a34a','method_post'=>'2563eb','method_put'=>'d97706','method_del'=>'dc2626'],
    'reference_doc' => ['bg'=>'ffffff','sidebar_bg'=>'f8fafc','card_bg'=>'ffffff','accent'=>'334155','txt'=>'0f172a','muted'=>'64748b','method_get'=>'059669','method_post'=>'3b82f6','method_put'=>'f59e0b','method_del'=>'ef4444'],
    'faq_doc'       => ['bg'=>'f0fdf4','sidebar_bg'=>'f0fdf4','card_bg'=>'ffffff','accent'=>'059669','txt'=>'052e16','muted'=>'166534','method_get'=>'16a34a','method_post'=>'2563eb','method_put'=>'d97706','method_del'=>'dc2626'],
    'notes_doc'     => ['bg'=>'fefce8','sidebar_bg'=>'fef9c3','card_bg'=>'fefce8','accent'=>'ca8a04','txt'=>'1a2000','muted'=>'713f12','method_get'=>'16a34a','method_post'=>'2563eb','method_put'=>'d97706','method_del'=>'dc2626'],
    'quickstart'    => ['bg'=>'f0fdf4','sidebar_bg'=>'166534','card_bg'=>'ffffff','accent'=>'16a34a','txt'=>'052e16','muted'=>'166534','method_get'=>'16a34a','method_post'=>'2563eb','method_put'=>'d97706','method_del'=>'dc2626'],
];

function pt_render_doc(GdImage $im, array $p, array $s): void {
    $W = imagesx($im); $H = imagesy($im);
    $sideW = (int)($W * 0.22);
    $headerH = (int)($H * 0.11);

    // Backgrounds
    imagefill($im, 0, 0, pt_color($im, $s['bg']));
    imagefilledrectangle($im, 0, 0, $sideW, $H, pt_color($im, $s['sidebar_bg']));
    imagefilledrectangle($im, $sideW, 0, $sideW+1, $H, pt_color($im, $s['muted'] . '44'));
    imagefilledrectangle($im, 0, $headerH - 1, $W, $headerH, pt_color($im, $s['muted'] . '44'));

    // Header
    $hsz = (int)($H * 0.042);
    $htxt = $p['heading'] ?: 'Documentation';
    imagettftext($im, $hsz, 0, $sideW + 24, (int)($headerH * 0.68), pt_color($im, $s['txt']), PT_FONT_BOLD, $htxt);
    // Accent dot in header
    imagefilledellipse($im, $sideW + 14, (int)($headerH/2), (int)($H*0.012)*2, (int)($H*0.012)*2, pt_color($im, $s['accent']));

    // Sidebar nav
    $nsz = (int)($H * 0.025);
    $sections = ['Getting Started', 'Authentication', 'Endpoints', 'Examples', 'Reference'];
    if ($p['category_label']) array_unshift($sections, $p['category_label']);
    foreach ($sections as $si => $section) {
        $sy = $headerH + (int)($H * 0.04) + $si * (int)($H * 0.1);
        if ($si === 0) {
            imagefilledrectangle($im, 0, $sy - 6, $sideW - 1, $sy + $nsz + 8, pt_color($im, $s['accent'] . '22'));
            imagefilledrectangle($im, 0, $sy - 6, 3, $sy + $nsz + 8, pt_color($im, $s['accent']));
        }
        imagettftext($im, $nsz, 0, 16, $sy + $nsz, pt_color($im, $si === 0 ? $s['txt'] : $s['muted']), PT_FONT_REG, $section);
    }

    // Content area
    $cx = $sideW + 24; $cy = $headerH + 20; $cw = $W - $cx - 24;

    // Title
    $tsz = (int)($H * 0.048);
    $dsz = (int)($H * 0.028);
    $ty = pt_text_block($im, PT_FONT_BOLD, $tsz, $cx, $cy, $s['txt'], $p['heading'] ?: 'API Reference', $cw, $tsz*1.3, 'left', 2);
    $ty += 8;

    // Badge row
    $badges = array_filter([$p['badge'], $p['version'], $p['date']]);
    $bx = $cx;
    foreach ($badges as $badge) {
        pt_badge($im, PT_FONT_REG, $badge, $bx, $ty, $s['accent'] . '22', $s['accent'], 100, $dsz * 0.85);
        $bx += pt_badge_width(PT_FONT_REG, $badge, $dsz * 0.85) + 8;
    }
    if ($badges) $ty += (int)($H * 0.06);

    // Description
    if ($p['description']) {
        $ty = pt_text_block($im, PT_FONT_REG, $dsz, $cx, $ty, $s['muted'], $p['description'], $cw, $dsz*1.6, 'left', 3);
        $ty += 12;
    }

    // API endpoint rows (simulated)
    $endpoints = [
        ['GET', '/api/users', 'List all users'],
        ['POST', '/api/users', 'Create a user'],
        ['PUT', '/api/users/{id}', 'Update a user'],
        ['DELETE', '/api/users/{id}', 'Delete a user'],
    ];
    $esz = (int)($H * 0.028);
    foreach ($endpoints as [$method, $path, $desc]) {
        if ($ty + $esz * 2 + 16 > $H - 20) break;
        $methodColors = ['GET'=>$s['method_get'],'POST'=>$s['method_post'],'PUT'=>$s['method_put'],'DELETE'=>$s['method_del']];
        $mc = $methodColors[$method] ?? $s['accent'];
        $mw = (int)($cw * 0.12);
        pt_rounded_rect($im, $cx, $ty, $mw, $esz+8, 4, pt_color($im, $mc . '22'));
        $mbbox = imagettfbbox($esz*0.85, 0, PT_FONT_BOLD, $method);
        $mw2 = abs($mbbox[2]-$mbbox[0]);
        imagettftext($im, $esz*0.85, 0, $cx + (int)(($mw-$mw2)/2), $ty + $esz, pt_color($im, $mc), PT_FONT_BOLD, $method);
        imagettftext($im, $esz * 0.9, 0, $cx + $mw + 12, $ty + $esz, pt_color($im, $s['txt']), PT_FONT_REG, $path);
        imagettftext($im, $esz * 0.8, 0, $cx + $mw + 12 + (int)($cw * 0.38), $ty + $esz, pt_color($im, $s['muted']), PT_FONT_REG, $desc);
        $ty += $esz + 14;
        imagefilledrectangle($im, $cx, $ty - 6, $cx + $cw, $ty - 5, pt_color($im, $s['muted'] . '22'));
    }
}

// ════════════════════════════════════════════════════════════════════════════
// GITHUB PROJECT CARD TEMPLATES
// ════════════════════════════════════════════════════════════════════════════

$GITHUB_SPECS = [
    'repo'          => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'58a6ff','desc_color'=>'8b949e','badge_bg'=>'21262d','badge_color'=>'3fb950','stat_color'=>'8b949e','accent'=>'3fb950','lang_color'=>'f1e05a','radius'=>6],
    'package'       => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'1a2f1a','badge_color'=>'3fb950','stat_color'=>'8b949e','accent'=>'3fb950','lang_color'=>'3178c6','radius'=>6],
    'release'       => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'1f3a1f','badge_color'=>'3fb950','stat_color'=>'8b949e','accent'=>'3fb950','lang_color'=>'f97316','radius'=>6],
    'open_source'   => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'1a1f2e','badge_color'=>'79c0ff','stat_color'=>'8b949e','accent'=>'79c0ff','lang_color'=>'e34c26','radius'=>6],
    'org'           => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'21262d','badge_color'=>'d2a8ff','stat_color'=>'8b949e','accent'=>'d2a8ff','lang_color'=>'d2a8ff','radius'=>6],
    'project'            => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'1a1a2e','badge_color'=>'818cf8','stat_color'=>'8b949e','accent'=>'818cf8','lang_color'=>'563d7c','radius'=>6],
    // ── 9 new GitHub templates ───────────────────────────────────
    'stars_showcase'     => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'f1e05a','desc_color'=>'8b949e','badge_bg'=>'2d2a00','badge_color'=>'f1e05a','stat_color'=>'f1e05a','accent'=>'f1e05a','lang_color'=>'f1e05a','radius'=>6],
    'npm_card'           => ['bg'=>'1a0000','card_bg'=>'2b0000','card_border'=>'4a0000','heading_color'=>'cb0000','desc_color'=>'cc4444','badge_bg'=>'2b0000','badge_color'=>'cb0000','stat_color'=>'aaaaaa','accent'=>'cb0000','lang_color'=>'cb0000','radius'=>4],
    'contribution_card'  => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'033a16','badge_color'=>'3fb950','stat_color'=>'39d353','accent'=>'39d353','lang_color'=>'39d353','radius'=>6],
    'profile_readme'     => ['bg'=>'ffffff','card_bg'=>'f6f8fa','card_border'=>'d0d7de','heading_color'=>'1f2328','desc_color'=>'656d76','badge_bg'=>'fff8c5','badge_color'=>'9a6700','stat_color'=>'24292f','accent'=>'0969da','lang_color'=>'f1e05a','radius'=>6],
    'docker_card'        => ['bg'=>'0b1d32','card_bg'=>'0a2c4e','card_border'=>'1967a4','heading_color'=>'ffffff','desc_color'=>'7ab7e8','badge_bg'=>'003f75','badge_color'=>'2496ed','stat_color'=>'7ab7e8','accent'=>'2496ed','lang_color'=>'2496ed','radius'=>8],
    'pr_card'            => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'1c2a1c','badge_color'=>'3fb950','stat_color'=>'8b949e','accent'=>'3fb950','lang_color'=>'a371f7','radius'=>6],
    'issue_card'         => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'1c2a00','badge_color'=>'57ab5a','stat_color'=>'8b949e','accent'=>'57ab5a','lang_color'=>'ff7b72','radius'=>6],
    'workflow_card'      => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'221a00','badge_color'=>'e3b341','stat_color'=>'8b949e','accent'=>'e3b341','lang_color'=>'f97316','radius'=>6],
    'monorepo'           => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'002030','badge_color'=>'79c0ff','stat_color'=>'8b949e','accent'=>'79c0ff','lang_color'=>'22d3ee','radius'=>6],
];

function pt_render_github(GdImage $im, array $p, array $s): void {
    $W = imagesx($im); $H = imagesy($im);
    $pad = $p['padding'];

    // Background
    imagefill($im, 0, 0, pt_color($im, $s['bg']));

    // Card
    $cw = $W - $pad*2; $ch = $H - $pad*2;
    pt_rounded_rect($im, $pad, $pad, $cw, $ch, $s['radius'], pt_color($im, $s['card_bg']));
    pt_rounded_rect_border($im, $pad, $pad, $cw, $ch, $s['radius'], pt_color($im, $s['card_border']), 1);

    $ip = 32;
    $ox = $pad + $ip; $oy = $pad + $ip; $ow = $cw - $ip*2;

    // Repo type icon + path
    $typeSz = (int)($H * 0.028);
    pt_icon($im, 'folder', $ox, $oy, (int)($H*0.04), $s['stat_color']);
    $repoPath = ($p['username'] ? $p['username'] . ' / ' : '') . ($p['heading'] ?: 'repository');
    imagettftext($im, $typeSz, 0, $ox + (int)($H*0.05), $oy + $typeSz, pt_color($im, $s['heading_color']), PT_FONT_BOLD, $repoPath);
    $oy += (int)($H * 0.09);

    // Description
    $dsz = (int)($H * 0.032);
    if ($p['description']) {
        $oy = pt_text_block($im, PT_FONT_REG, $dsz, $ox, $oy, $s['desc_color'], $p['description'], $ow, $dsz * 1.55, 'left', 3);
        $oy += (int)($H * 0.03);
    }

    // Topics/badges
    $topics = array_filter([$p['badge'], $p['lang'], $p['category_label'], $p['subheading']]);
    $topicX = $ox;
    foreach (array_slice($topics, 0, 5) as $topic) {
        $bw = pt_badge_width(PT_FONT_REG, $topic, (int)($H*0.024));
        if ($topicX + $bw > $ox + $ow) break;
        pt_badge($im, PT_FONT_REG, $topic, $topicX, $oy, $s['badge_bg'], $s['badge_color'], 100, (int)($H*0.024));
        $topicX += $bw + 8;
    }
    if ($topics) $oy += (int)($H * 0.075);

    // Stats row (stars, forks, version)
    $statsY = $pad + $ch - $ip - (int)($H * 0.06);
    imagefilledrectangle($im, $ox, $statsY - 12, $ox+$ow, $statsY - 11, pt_color($im, $s['card_border']));

    $statSz = (int)($H * 0.027);
    $statItems = [
        ['star', $p['stars'] ?: '0', 'Stars'],
        ['code', $p['forks'] ?: '0', 'Forks'],
        ['tag', $p['version'] ?: 'v1.0', 'Version'],
    ];
    $sx = $ox;
    foreach ($statItems as [$icon, $val, $label]) {
        pt_icon($im, $icon, $sx, $statsY + 2, (int)($H*0.032), $s['lang_color']);
        $iconW = (int)($H * 0.04);
        imagettftext($im, $statSz, 0, $sx + $iconW + 4, $statsY + $statSz + 2, pt_color($im, $s['stat_color']), PT_FONT_REG, $val);
        $vbbox = imagettfbbox($statSz, 0, PT_FONT_REG, $val);
        $sx += $iconW + abs($vbbox[2]-$vbbox[0]) + 24;
    }

    // Language dot
    $langY = $statsY + 4;
    $lang = $p['lang'] ?: 'JavaScript';
    imagefilledellipse($im, $ox + $ow - 120, $langY + (int)($H*0.014), (int)($H*0.016), (int)($H*0.016), pt_color($im, $s['lang_color']));
    imagettftext($im, $statSz, 0, $ox + $ow - 105, $langY + $statSz, pt_color($im, $s['stat_color']), PT_FONT_REG, $lang);

    // Watermark / footer
    if ($p['watermark'] || $p['website'] || $p['footer']) {
        $ftxt = $p['watermark'] ?: $p['website'] ?: $p['footer'];
        $ftz = (int)($H * 0.024);
        $ftbbox = imagettfbbox($ftz, 0, PT_FONT_REG, $ftxt);
        $ftw = abs($ftbbox[2]-$ftbbox[0]);
        imagettftext($im, $ftz, 0, $ox + $ow - $ftw, $statsY + $statSz + 2, pt_color($im, $s['stat_color']), PT_FONT_REG, $ftxt);
    }
}

// ════════════════════════════════════════════════════════════════════════════
// DISPATCH TABLE
// ════════════════════════════════════════════════════════════════════════════

$registry = [
    'og'          => ['templates' => array_keys($OG_SPECS),       'default_w' => 1200, 'default_h' => 630],
    'social'      => ['templates' => array_keys($SOCIAL_SPECS),   'default_w' => 1200, 'default_h' => 630],
    'placeholder' => ['templates' => ['simple','grid','gradient','glass','pattern','minimal','modern','empty_state','blueprint_grid','crosshatch','circuit','polka_dots','diagonal_stripes','noise_field','sketch','dots_dark','gradient_mesh','marble'], 'default_w' => 800, 'default_h' => 600],
    'browser'     => ['templates' => ['chrome','firefox','safari','edge','arc','generic','brave','opera','vivaldi','dark_mode','minimal_browser','retro_browser','high_contrast','material','warm_light'], 'default_w' => 1200, 'default_h' => 800],
    'terminal'    => ['templates' => ['linux','modern','hacker','vscode','minimal','powerline','fish_shell','windows_cmd','powershell','ubuntu_term','matrix','amber','iterm2','p10k','dracula_term'], 'default_w' => 900, 'default_h' => 600],
    'profile'     => ['templates' => array_keys($PROFILE_SPECS),  'default_w' => 900, 'default_h' => 500],
    'code'        => ['templates' => ['vscode','github','monokai','nord','dracula','minimal','one_dark','synthwave','gruvbox','solarized','tokyo_night','catppuccin','atom_light','sublime','jetbrains'], 'default_w' => 1000, 'default_h' => 600],
    'dashboard'   => ['templates' => array_keys($DASHBOARD_SPECS),'default_w' => 1200, 'default_h' => 630],
    'docs'        => ['templates' => array_keys($DOC_SPECS),       'default_w' => 1200, 'default_h' => 630],
    'github'      => ['templates' => array_keys($GITHUB_SPECS),    'default_w' => 1200, 'default_h' => 630],
];

// ════════════════════════════════════════════════════════════════════════════
// MAIN RENDER LOGIC
// ════════════════════════════════════════════════════════════════════════════

try {
    if (!extension_loaded('gd')) throw new RuntimeException('GD not available');

    $cat  = $p['category'];
    $tpl  = $p['template'];
    $fmt  = $p['format'];
    $W    = $p['width'];
    $H    = $p['height'];

    // Validate category/template
    if (!isset($registry[$cat])) $cat = 'og';
    if (!in_array($tpl, $registry[$cat]['templates'])) {
        $tpl = $registry[$cat]['templates'][0];
    }

    // Create image
    $im = imagecreatetruecolor($W, $H);
    if (!$im) throw new RuntimeException('imagecreatetruecolor failed');
    imagesavealpha($im, true);
    imagealphablending($im, true);

    // Send ETag header first
    ob_end_clean();
    ob_start();
    header('ETag: ' . $etag);
    header('Cache-Control: public, max-age=86400, immutable');
    header('X-Content-Type-Options: nosniff');
    header('Vary: Accept');

    // Dispatch
    switch ($cat) {
        case 'og':
            $spec = $OG_SPECS[$tpl] ?? $OG_SPECS['github_dark'];
            // Allow user bg/fg/accent overrides
            if (isset($_GET['bg_color']))     $spec['bg'] = $p['bg_color'];
            if (isset($_GET['fg_color']))     $spec['heading_color'] = $p['fg_color'];
            if (isset($_GET['accent_color'])) { $spec['accent_color'] = $p['accent_color']; $spec['badge_color'] = $p['accent_color']; $spec['icon_color'] = $p['accent_color']; }
            if (isset($_GET['heading_color'])) $spec['heading_color'] = $p['heading_color'];
            if (isset($_GET['description_color'])) $spec['desc_color'] = $p['description_color'];
            pt_render_og($im, $p, $spec);
            break;
        case 'social':
            $spec = $SOCIAL_SPECS[$tpl] ?? $SOCIAL_SPECS['twitter'];
            if (isset($_GET['bg_color']))     $spec['bg'] = $p['bg_color'];
            if (isset($_GET['accent_color'])) $spec['accent'] = $p['accent_color'];
            pt_render_social($im, $p, $spec);
            break;
        case 'placeholder':
            pt_render_placeholder($im, $p, $tpl);
            break;
        case 'browser':
            pt_render_browser($im, $p, $tpl);
            break;
        case 'terminal':
            pt_render_terminal($im, $p, $tpl);
            break;
        case 'profile':
            $spec = $PROFILE_SPECS[$tpl] ?? $PROFILE_SPECS['team_member'];
            if (isset($_GET['bg_color']))     $spec['bg'] = $p['bg_color'];
            if (isset($_GET['accent_color'])) $spec['accent'] = $p['accent_color'];
            pt_render_profile($im, $p, $spec);
            break;
        case 'code':
            pt_render_code($im, $p, $tpl);
            break;
        case 'dashboard':
            $spec = $DASHBOARD_SPECS[$tpl] ?? $DASHBOARD_SPECS['analytics'];
            if (isset($_GET['bg_color']))     $spec['bg'] = $p['bg_color'];
            if (isset($_GET['accent_color'])) $spec['accent'] = $p['accent_color'];
            pt_render_dashboard($im, $p, $spec);
            break;
        case 'docs':
            $spec = $DOC_SPECS[$tpl] ?? $DOC_SPECS['api'];
            if (isset($_GET['bg_color']))     $spec['bg'] = $p['bg_color'];
            if (isset($_GET['accent_color'])) $spec['accent'] = $p['accent_color'];
            pt_render_doc($im, $p, $spec);
            break;
        case 'github':
            $spec = $GITHUB_SPECS[$tpl] ?? $GITHUB_SPECS['repo'];
            pt_render_github($im, $p, $spec);
            break;
    }

    // Output image
    ob_end_clean();
    if ($fmt === 'jpg' || $fmt === 'jpeg') {
        header('Content-Type: image/jpeg');
        imagejpeg($im, null, 92);
    } elseif ($fmt === 'webp') {
        header('Content-Type: image/webp');
        imagewebp($im, null, 90);
    } else {
        header('Content-Type: image/png');
        imagepng($im, null, 6);
    }
    imagedestroy($im);

} catch (Throwable $e) {
    pt_fallback_image($p['format'] ?? 'png');
}
