<?php
/**
 * Generic OG Image Generator — /og-image.php?slug=my-plugin
 *
 * Priority:
 *   1. Static override at assets/img/og-image-{slug}.png
 *   2. Static fallback  at assets/img/og-image.png
 *   3. GD-generated image (customizable by super admin via Admin › SEO › OpenGraph)
 *
 * Reads plugin.json for any active plugin and renders a branded 1200×630 PNG.
 * DB settings are loaded via a lightweight direct PDO query (no bootstrap).
 */
if (!defined('AWAN')) { define('AWAN', true); }

// ── Input validation ─────────────────────────────────────────────────────────
$slug = preg_replace('/[^a-z0-9\-_]/i', '', trim($_GET['slug'] ?? ''));

// ── Static file priority ─────────────────────────────────────────────────────
$imgDir = __DIR__ . '/assets/img/';

// 1. Per-plugin static override
if ($slug && file_exists($imgDir . 'og-image-' . $slug . '.png')) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    readfile($imgDir . 'og-image-' . $slug . '.png');
    exit;
}

// 2. Global static fallback
if (file_exists($imgDir . 'og-image.png')) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    readfile($imgDir . 'og-image.png');
    exit;
}

// ── Check GD is available ─────────────────────────────────────────────────────
if (!function_exists('imagecreatetruecolor') || !function_exists('imagettftext')) {
    http_response_code(404);
    exit('OG image generation unavailable: GD/FreeType not installed.');
}

// ── Load manifest ─────────────────────────────────────────────────────────────
$manifestPath = __DIR__ . '/plugins/' . $slug . '/plugin.json';
$manifest     = [];
if ($slug && file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true) ?? [];
}

// Extract fields with sensible fallbacks
$name        = $manifest['name']        ?? 'Awan Tools';
$description = $manifest['description'] ?? 'Free tools built to help you work smarter.';
$offered     = (int)($manifest['offered'] ?? 1);
$license     = $manifest['license']     ?? 'Free';
$reqLogin    = !empty($manifest['requires_login']);
$categories  = $manifest['categories']  ?? [];
$pills       = array_slice($categories, 0, 5);
if (empty($pills)) $pills = ['Utilities'];

$subtitle = mb_strlen($description) > 84
    ? mb_substr($description, 0, 81) . '...'
    : $description;

// ── Load DB settings (lightweight — no bootstrap) ─────────────────────────────
$ogBgColor      = '#0f172a';
$ogCardColor    = '#1a2235';
$ogPrimaryColor = '#6366f1';
$ogEyebrow      = '';
$ogWatermark    = '';

try {
    require_once __DIR__ . '/_config.php';
    if (DB_DRIVER === 'sqlite') {
        $pdo = new PDO('sqlite:' . DB_SQLITE, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } else {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
    $keys = ['og_image_bg_color', 'og_image_card_color', 'og_image_primary_color', 'og_image_eyebrow', 'og_image_watermark', 'site_url'];
    $stmt = $pdo->prepare("SELECT `key`, `value` FROM settings WHERE `key` IN ('" . implode("','", $keys) . "')");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    if (!empty($rows['og_image_bg_color']))      $ogBgColor      = $rows['og_image_bg_color'];
    if (!empty($rows['og_image_card_color']))     $ogCardColor    = $rows['og_image_card_color'];
    if (!empty($rows['og_image_primary_color']))  $ogPrimaryColor = $rows['og_image_primary_color'];
    if (!empty($rows['og_image_eyebrow']))        $ogEyebrow      = $rows['og_image_eyebrow'];
    if (!empty($rows['og_image_watermark']))      $ogWatermark    = $rows['og_image_watermark'];

    if (!$ogWatermark) {
        $siteUrl     = $rows['site_url'] ?? '';
        $ogWatermark = $siteUrl ? parse_url($siteUrl, PHP_URL_HOST) : ($_SERVER['HTTP_HOST'] ?? 'awantools.site');
    }
} catch (Throwable $e) {
    $ogWatermark = $_SERVER['HTTP_HOST'] ?? 'awantools.site';
}

// ── Helper: hex to RGB ────────────────────────────────────────────────────────
function hexToRgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
}

// ── Canvas dimensions ─────────────────────────────────────────────────────────
$W = 1200; $H = 630;
$img = imagecreatetruecolor($W, $H);
imagesavealpha($img, true);

// ── Palette ──────────────────────────────────────────────────────────────────
[$bgR,$bgG,$bgB]       = hexToRgb($ogBgColor);
[$cdR,$cdG,$cdB]       = hexToRgb($ogCardColor);
[$prR,$prG,$prB]       = hexToRgb($ogPrimaryColor);

// Lighter primary (mix with white ~40%)
$plR = min(255, (int)($prR + (255 - $prR) * 0.4));
$plG = min(255, (int)($prG + (255 - $prG) * 0.4));
$plB = min(255, (int)($prB + (255 - $prB) * 0.4));

$bg      = imagecolorallocate($img, $bgR, $bgG, $bgB);
$card    = imagecolorallocate($img, $cdR, $cdG, $cdB);
$primary = imagecolorallocate($img, $prR, $prG, $prB);
$pLight  = imagecolorallocate($img, $plR, $plG, $plB);
$white   = imagecolorallocate($img, 248, 250, 252);
$muted   = imagecolorallocate($img, 148, 163, 184);
$border  = imagecolorallocate($img, min(255,$cdR+15), min(255,$cdG+15), min(255,$cdB+15));
$success = imagecolorallocate($img,  52, 211, 153);

// ── Fonts ─────────────────────────────────────────────────────────────────────
$fontBold    = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
$fontRegular = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
$fontMono    = '/usr/share/fonts/truetype/dejavu/DejaVuSansMono-Bold.ttf';

// Fallback fonts
if (!file_exists($fontBold))    $fontBold    = '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf';
if (!file_exists($fontRegular)) $fontRegular = '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf';
if (!file_exists($fontMono))    $fontMono    = $fontBold;

// If fonts still missing, serve 404 gracefully
if (!file_exists($fontBold) || !file_exists($fontRegular)) {
    http_response_code(404);
    exit('OG image generation unavailable: fonts not found.');
}

// Helpers
function ogTextX(int $canvasW, float $size, string $font, string $text): int {
    $box = imagettfbbox($size, 0, $font, $text);
    return (int)(($canvasW - abs($box[4] - $box[0])) / 2);
}
function ogTextW(float $size, string $font, string $text): int {
    $box = imagettfbbox($size, 0, $font, $text);
    return abs($box[4] - $box[0]);
}

// ── Background ────────────────────────────────────────────────────────────────
imagefill($img, 0, 0, $bg);

// Subtle top-gradient strip
for ($i = 0; $i < 160; $i++) {
    $factor = $i / 160;
    $r = max(0, min(255, (int)($bgR - ($bgR * 0.3 * (1 - $factor)))));
    $g = max(0, min(255, (int)($bgG - ($bgG * 0.2 * (1 - $factor)))));
    $b = max(0, min(255, (int)($bgB + (20 * (1 - $factor)))));
    $c = imagecolorallocate($img, $r, $g, $b);
    imageline($img, 0, $i, $W, $i, $c);
}

// ── Card ─────────────────────────────────────────────────────────────────────
$cx = 56; $cy = 56; $cw = $W - 112; $ch = $H - 112;
imagefilledrectangle($img, $cx, $cy, $cx + $cw, $cy + $ch, $card);
imagerectangle($img,       $cx, $cy, $cx + $cw, $cy + $ch, $border);

// Left accent bar
imagefilledrectangle($img, $cx, $cy, $cx + 5, $cy + $ch, $primary);

// Top-right glow blob
$blobX = $cx + $cw - 160; $blobY = $cy + 50;
for ($r = 130; $r > 0; $r -= 2) {
    $alpha = min(126, (int)(115 - $r * 0.85));
    if ($alpha < 0) $alpha = 0;
    $c = imagecolorallocatealpha($img, $prR, $prG, $prB, $alpha);
    imagefilledellipse($img, $blobX, $blobY, $r * 2, $r * 2, $c);
}

// ── Icon circle ───────────────────────────────────────────────────────────────
$iconCX = $cx + 90;
$iconCY = $cy + (int)($ch / 2) - 20;
$iconBgR = max(0, $bgR - 10); $iconBgG = max(0, $bgG - 5); $iconBgB = min(255, $bgB + 30);
$iconBgC = imagecolorallocate($img, $iconBgR, $iconBgG, $iconBgB);
imagefilledellipse($img, $iconCX, $iconCY, 92, 92, $iconBgC);
imageellipse($img,       $iconCX, $iconCY, 92, 92, $primary);

$ix = $iconCX - 18; $iy = $iconCY - 26;
imagerectangle($img, $ix, $iy, $ix + 36, $iy + 46, $pLight);
imageline($img, $ix + 5, $iy + 12, $ix + 30, $iy + 12, $pLight);
imageline($img, $ix + 5, $iy + 20, $ix + 30, $iy + 20, $pLight);
imageline($img, $ix + 5, $iy + 28, $ix + 22, $iy + 28, $pLight);
imagettftext($img, 9, 0, $ix + 3, $iy + 45, $primary, $fontMono, '{ }');

// ── Text content ──────────────────────────────────────────────────────────────
$tx = $iconCX + 66;

$eyebrow = $ogEyebrow ?: ('AWAN TOOLS  ·  FREE ' . strtoupper($reqLogin ? 'MEMBER TOOL' : 'ONLINE TOOL'));
imagettftext($img, 12, 0, $tx, $cy + 108, $pLight, $fontBold, $eyebrow);
imagettftext($img, 46, 0, $tx, $cy + 168, $white, $fontBold, $name);
imagettftext($img, 18, 0, $tx, $cy + 208, $muted, $fontRegular, $subtitle);

// ── Category pills ────────────────────────────────────────────────────────────
$px = $tx; $py = $cy + 248;
foreach ($pills as $label) {
    $pw = ogTextW(12, $fontBold, $label) + 24;
    imagefilledrectangle($img, $px, $py - 18, $px + $pw, $py + 10, $primary);
    imagettftext($img, 12, 0, $px + 12, $py, $white, $fontBold, $label);
    $px += $pw + 10;
}

// ── Stats bar ─────────────────────────────────────────────────────────────────
$sy = $cy + $ch - 54;
imageline($img, $cx + 24, $sy - 14, $cx + $cw - 24, $sy - 14, $border);

$stats = [
    [(string)$offered,             $offered === 1 ? 'Tool' : 'Tools'],
    [$reqLogin ? 'Login' : '100%', $reqLogin ? 'Required' : 'Client-Side'],
    [$reqLogin ? 'Member' : 'Free','No Sign-Up'],
    [$license ?: 'MIT',            'License'],
];
$sw = ($cw - 48) / count($stats);
foreach ($stats as $i => [$val, $lbl]) {
    $sx = $cx + 24 + (int)(($i + 0.5) * $sw);
    $vw = ogTextW(18, $fontBold, $val);
    imagettftext($img, 18, 0, $sx - (int)($vw / 2), $sy + 14, $success, $fontBold, $val);
    $lw = ogTextW(11, $fontRegular, $lbl);
    imagettftext($img, 11, 0, $sx - (int)($lw / 2), $sy + 30, $muted, $fontRegular, $lbl);
}

// ── Watermark ─────────────────────────────────────────────────────────────────
$domain = $ogWatermark;
$dw = ogTextW(12, $fontRegular, $domain);
imagettftext($img, 12, 0, $cx + $cw - $dw - 14, $cy + $ch - 10, $muted, $fontRegular, $domain);

// ── Output ────────────────────────────────────────────────────────────────────
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
imagepng($img, null, 6);
imagedestroy($img);
exit;
