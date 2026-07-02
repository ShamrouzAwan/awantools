<?php
/**
 * Previewer Toolkit — All Templates
 * ==================================
 * Every template for every category lives in this single file.
 *
 * HOW TO ADD A NEW TEMPLATE
 * ─────────────────────────
 * 1. Write a render function below in the right section:
 *      function pt_<category>_<slug>(array $p): string { ... return $svg; }
 *
 * 2. Add the slug to the match() in the section's dispatcher:
 *      function pt_render_<category>(array $p): string
 *
 * 3. Register metadata + defaults in Registry.php → categories() → '<category>' → 'templates' / 'defaults'
 *
 * TEMPLATE FUNCTION RULES
 * ───────────────────────
 * • Return a complete <svg>…</svg> string.
 * • Use PT_Icons::icon_block() for icon circles; icons are now pure SVG shapes.
 * • Pre-compute ALL arithmetic before heredoc/string blocks — PHP does NOT allow
 *   expressions like {$a+$b} inside double-quoted strings or heredocs.
 * • Helper: pt_vars($p) for common colour/font unpacking.
 */
defined('AWAN') or die('Direct access denied.');


// ═══════════════════════════════════════════════════════════════════════════════
// SHARED HELPERS
// ═══════════════════════════════════════════════════════════════════════════════

/** Unpack the most-used params in one line. */
function pt_vars(array $p): array
{
    return [
        $p['width'], $p['height'], $p['padding'],
        PT_Color::h($p['bg_color']),
        PT_Color::h($p['heading_color']),
        PT_Color::h($p['description_color']),
        PT_Color::h($p['accent_color']),
        $p['border_radius'],
        $p['font'],
    ];
}

/** Unpack without padding (code / terminal / browser). */
function pt_vars_np(array $p): array
{
    return [
        $p['width'], $p['height'],
        PT_Color::h($p['bg_color']),
        PT_Color::h($p['heading_color']),
        PT_Color::h($p['description_color']),
        PT_Color::h($p['accent_color']),
        $p['border_radius'],
        $p['font'],
    ];
}

/** Badge pill left-aligned. */
function pt_badge(array $p, float $x, float $y, string $ac, string $font): string
{
    if (!$p['badge']) return '';
    $be  = PT_Text::e($p['badge']);
    $bw  = mb_strlen($p['badge']) * 9 + 24;
    $mid = $x + $bw / 2;
    $ty  = $y + 17;
    return "<rect x='$x' y='$y' width='$bw' height='26' rx='13' fill='$ac' opacity='0.18'/>" .
           "<text x='$mid' y='$ty' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='12' font-weight='700' fill='$ac'>$be</text>";
}

/** Badge pill centered. */
function pt_badge_c(array $p, float $cx, float $y, string $ac, string $font): string
{
    if (!$p['badge']) return '';
    $be  = PT_Text::e($p['badge']);
    $bw  = mb_strlen($p['badge']) * 9 + 28;
    $x   = $cx - $bw / 2;
    $ty  = $y + 17;
    return "<rect x='$x' y='$y' width='$bw' height='26' rx='13' fill='$ac' opacity='0.18'/>" .
           "<text x='$cx' y='$ty' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='12' font-weight='700' fill='$ac'>$be</text>";
}

/** Simple grid lines for neon-style templates. */
function pt_grid_lines(int $w, int $h, int $step, string $color, float $opacity): string
{
    $out = '';
    for ($x = 0; $x <= $w; $x += $step) {
        $out .= "<line x1='$x' y1='0' x2='$x' y2='$h' stroke='$color' stroke-width='0.5' opacity='$opacity'/>";
    }
    for ($y = 0; $y <= $h; $y += $step) {
        $out .= "<line x1='0' y1='$y' x2='$w' y2='$y' stroke='$color' stroke-width='0.5' opacity='$opacity'/>";
    }
    return $out;
}


// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 1 — OG IMAGES  (1200 × 630)
// ═══════════════════════════════════════════════════════════════════════════════

function pt_render_og(array $p): string
{
    return match($p['template']) {
        'github_light'  => pt_og_github_light($p),
        'glassmorphism' => pt_og_glassmorphism($p),
        'gradient'      => pt_og_gradient($p),
        'minimal'       => pt_og_minimal($p),
        'neon'          => pt_og_neon($p),
        default         => pt_og_github_dark($p),
    };
}

function pt_og_github_dark(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $hl   = PT_Text::wrap($p['heading'], $w - $pad*2 - 40, 68, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], $w - $pad*2, 28, 0.52, 3);
    $icx  = $pad + 36; $icy = $pad + 36;
    $hy   = $icy + 60; $hlh = 82;
    $dy   = $hy + count($hl)*$hlh + 18; $dlh = 38;
    $fy   = $h - $pad + 5;
    $dots = PT_Renderer::dots(8, 6, $w-260, 20, 28, $ac, 0.2, 2.2);
    $icon = PT_Icons::icon_block($p['icon'], $icx, $icy, 36, $p['accent_color'], $p['accent_color'], 22);
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $fe   = PT_Text::e($p['footer']);
    $badge = pt_badge($p, $pad, $dy + count($dl)*$dlh + 22, $ac, $font);
    $line_x2 = $w - $pad; $text_y = $h - $pad + 22;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  $dots
  <rect x="0" y="0" width="$w" height="5" fill="$ac"/>
  $icon
  <text x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="68" font-weight="800" fill="$hc" letter-spacing="-1.5">$hs</text>
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="28" fill="$dc">$ds</text>
  $badge
  <line x1="$pad" y1="$fy" x2="$line_x2" y2="$fy" stroke="$dc" stroke-width="0.5" opacity="0.2"/>
  <text x="$pad" y="$text_y" font-family="'$font',sans-serif" font-size="20" fill="$dc" opacity="0.75">$fe</text>
</g></svg>
SVG;
}

function pt_og_github_light(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $hl    = PT_Text::wrap($p['heading'], $w - $pad*2, 68, 0.52, 2);
    $dl    = PT_Text::wrap($p['description'], $w - $pad*2, 28, 0.52, 3);
    $icx   = $pad + 36; $icy = $pad + 36;
    $hy    = $icy + 60; $hlh = 82;
    $dy    = $hy + count($hl)*$hlh + 16; $dlh = 38;
    $dots  = PT_Renderer::dots(8, 6, $w-260, 20, 28, $ac, 0.12, 2.2);
    $icon  = PT_Icons::icon_block($p['icon'], $icx, $icy, 36, $p['accent_color'], $p['accent_color'], 22);
    $hs    = PT_Text::tspans($hl, $pad, $hlh);
    $ds    = PT_Text::tspans($dl, $pad, $dlh);
    $fe    = PT_Text::e($p['footer']);
    $badge = pt_badge($p, $pad, $dy + count($dl)*$dlh + 22, $ac, $font);
    $border  = PT_Color::h(PT_Color::darken($p['bg_color'], 0.08));
    $line_y  = $h - $pad - 10;
    $line_x2 = $w - $pad;
    $text_y  = $h - $pad + 14;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="1" fill="$border"/>
  $dots
  <rect x="0" y="0" width="5" height="$h" fill="$ac"/>
  $icon
  <text x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="68" font-weight="800" fill="$hc" letter-spacing="-1.5">$hs</text>
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="28" fill="$dc">$ds</text>
  $badge
  <line x1="$pad" y1="$line_y" x2="$line_x2" y2="$line_y" stroke="$border" stroke-width="1"/>
  <text x="$pad" y="$text_y" font-family="'$font',sans-serif" font-size="20" fill="$dc">$fe</text>
</g></svg>
SVG;
}

function pt_og_glassmorphism(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $bg2    = PT_Color::h(PT_Color::lighten($p['bg_color'], 0.12));
    $hl     = PT_Text::wrap($p['heading'], $w - $pad*4, 62, 0.52, 2);
    $dl     = PT_Text::wrap($p['description'], $w - $pad*4, 26, 0.52, 3);
    $cx     = (int)($w/2);
    $icx    = $cx; $icy = $pad + 52;
    $hlh    = 74; $dlh = 36;
    $hy     = $icy + 68;
    $dy     = $hy + count($hl)*$hlh + 16;
    $icon   = PT_Icons::icon_block($p['icon'], $icx, $icy, 46, $p['accent_color'], $p['accent_color'], 28);
    $hs     = PT_Text::tspans_center($hl, $cx, $hlh);
    $ds     = PT_Text::tspans_center($dl, $cx, $dlh);
    $fe     = PT_Text::e($p['footer']);
    $badge  = pt_badge_c($p, $cx, $dy + count($dl)*$dlh + 24, $ac, $font);
    $inner_x = (int)($pad);   $inner_y = (int)($pad);
    $inner_w = $w - $pad*2;   $inner_h = $h - $pad*2;
    $footer_y = $h - $pad + 12;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <radialGradient id="bg1" cx="75%" cy="25%" r="60%"><stop offset="0%" stop-color="$ac" stop-opacity="0.4"/><stop offset="100%" stop-color="$bg" stop-opacity="0"/></radialGradient>
  <radialGradient id="bg2" cx="20%" cy="75%" r="50%"><stop offset="0%" stop-color="$bg2" stop-opacity="0.5"/><stop offset="100%" stop-color="$bg" stop-opacity="0"/></radialGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect width="$w" height="$h" fill="url(#bg1)"/>
  <rect width="$w" height="$h" fill="url(#bg2)"/>
  <rect x="$inner_x" y="$inner_y" width="$inner_w" height="$inner_h" rx="16" fill="$hc" fill-opacity="0.06" stroke="$hc" stroke-opacity="0.12" stroke-width="1"/>
  $icon
  <text x="$cx" y="$hy" text-anchor="middle" font-family="'$font',sans-serif" font-size="62" font-weight="800" fill="$hc" letter-spacing="-1">$hs</text>
  <text x="$cx" y="$dy" text-anchor="middle" font-family="'$font',sans-serif" font-size="26" fill="$dc">$ds</text>
  $badge
  <text x="$cx" y="$footer_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="18" fill="$dc" opacity="0.8">$fe</text>
</g></svg>
SVG;
}

function pt_og_gradient(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $bg2   = PT_Color::h(PT_Color::darken($p['bg_color'], 0.22));
    $hl    = PT_Text::wrap($p['heading'], $w - $pad*2, 80, 0.52, 2);
    $dl    = PT_Text::wrap($p['description'], $w - $pad*2, 30, 0.52, 2);
    $icx   = $w - $pad - 60; $icy = $pad + 60;
    $hlh   = 95; $dlh = 40;
    $hy    = (int)($h * 0.35);
    $dy    = $hy + count($hl)*$hlh + 10;
    $icon  = PT_Icons::icon_block($p['icon'], $icx, $icy, 55, $p['heading_color'], $p['heading_color'], 34);
    $hs    = PT_Text::tspans($hl, $pad, $hlh);
    $ds    = PT_Text::tspans($dl, $pad, $dlh);
    $fe    = PT_Text::e($p['footer']);
    $bar_y   = $h - $pad - 30;
    $text_x  = $pad + 78;
    $text_y  = $h - $pad - 12;
    $circ1_r = (int)($h * 0.9);
    $circ2_r = (int)($h * 0.6);
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <linearGradient id="grad" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="$bg"/><stop offset="100%" stop-color="$bg2"/></linearGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="url(#grad)"/>
  <circle cx="$w" cy="0" r="$circ1_r" fill="$hc" fill-opacity="0.05"/>
  <circle cx="0" cy="$h" r="$circ2_r" fill="$hc" fill-opacity="0.05"/>
  $icon
  <text x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="80" font-weight="900" fill="$hc" letter-spacing="-2">$hs</text>
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="30" fill="$dc">$ds</text>
  <rect x="$pad" y="$bar_y" width="60" height="4" rx="2" fill="$hc" opacity="0.5"/>
  <text x="$text_x" y="$text_y" font-family="'$font',sans-serif" font-size="20" fill="$hc" opacity="0.7">$fe</text>
</g></svg>
SVG;
}

function pt_og_minimal(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $border = PT_Color::h(PT_Color::darken($p['bg_color'], 0.1));
    $hl     = PT_Text::wrap($p['heading'], $w - $pad*3, 64, 0.52, 2);
    $dl     = PT_Text::wrap($p['description'], $w - $pad*3, 26, 0.52, 3);
    $cx     = (int)($w/2);
    $hlh    = 76; $dlh = 36;
    $icx    = $cx; $icy = $pad + 55;
    $hy     = $icy + 68;
    $dy     = $hy + count($hl)*$hlh + 16;
    $icon   = PT_Icons::icon_block($p['icon'], $icx, $icy, 44, $p['accent_color'], $p['accent_color'], 26);
    $hs     = PT_Text::tspans_center($hl, $cx, $hlh);
    $ds     = PT_Text::tspans_center($dl, $cx, $dlh);
    $fe     = PT_Text::e($p['footer']);
    $badge  = pt_badge_c($p, $cx, $dy + count($dl)*$dlh + 22, $ac, $font);
    $dash_x  = $cx - 20; $dash_y = $h - $pad - 8;
    $text_y  = $h - $pad + 18;
    $inner_x = (int)($pad/2); $inner_y = (int)($pad/2);
    $inner_w = $w - $pad;     $inner_h = $h - $pad;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg" stroke="$border" stroke-width="1"/>
  <rect x="$inner_x" y="$inner_y" width="$inner_w" height="$inner_h" rx="12" fill="none" stroke="$border" stroke-width="1" stroke-dasharray="4 4" opacity="0.5"/>
  $icon
  <text x="$cx" y="$hy" text-anchor="middle" font-family="'$font',sans-serif" font-size="64" font-weight="800" fill="$hc" letter-spacing="-1">$hs</text>
  <text x="$cx" y="$dy" text-anchor="middle" font-family="'$font',sans-serif" font-size="26" fill="$dc">$ds</text>
  $badge
  <rect x="$dash_x" y="$dash_y" width="40" height="2" rx="1" fill="$ac"/>
  <text x="$cx" y="$text_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="18" fill="$dc">$fe</text>
</g></svg>
SVG;
}

function pt_og_neon(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $hl   = PT_Text::wrap($p['heading'], $w - $pad*2, 76, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], $w - $pad*2, 28, 0.52, 3);
    $hlh  = 90; $dlh = 38;
    $hy   = (int)($h * 0.36);
    $dy   = $hy + count($hl)*$hlh + 14;
    $icx  = $pad + 38; $icy = $pad + 38;
    $icon = PT_Icons::icon_block($p['icon'], $icx, $icy, 38, $p['accent_color'], $p['accent_color'], 24);
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $fe   = PT_Text::e($p['footer']);
    $grid = pt_grid_lines($w, $h, 40, '#'.$p['heading_color'], 0.04);
    $cx07w = (int)($w * 0.7); $cy02h = (int)($h * 0.2);
    $cx01w = (int)($w * 0.1); $cy08h = (int)($h * 0.8);
    $bar_y  = $h - $pad - 32;
    $text_x = $pad + 216;
    $text_y = $h - $pad - 14;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
    <feGaussianBlur stdDeviation="8" result="blur"/>
    <feMerge><feMergeNode in="blur"/><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
  </filter>
  <filter id="glow_sm" x="-10%" y="-10%" width="120%" height="120%">
    <feGaussianBlur stdDeviation="3" result="blur"/>
    <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
  </filter>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  $grid
  <circle cx="$cx07w" cy="$cy02h" r="200" fill="$ac" opacity="0.05"/>
  <circle cx="$cx01w" cy="$cy08h" r="150" fill="$hc" opacity="0.04"/>
  $icon
  <text filter="url(#glow)" x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="76" font-weight="900" fill="$hc" letter-spacing="-2">$hs</text>
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="28" fill="$dc">$ds</text>
  <rect x="$pad" y="$bar_y" width="200" height="3" rx="1.5" fill="$ac" filter="url(#glow_sm)"/>
  <text x="$text_x" y="$text_y" font-family="'$font',sans-serif" font-size="20" fill="$dc">$fe</text>
</g></svg>
SVG;
}


// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 2 — SOCIAL CARDS  (1200 × 675)
// ═══════════════════════════════════════════════════════════════════════════════

function pt_render_social(array $p): string
{
    return match($p['template']) {
        'linkedin'     => pt_social_linkedin($p),
        'modern_dark'  => pt_social_modern_dark($p),
        'split'        => pt_social_split($p),
        'corporate'    => pt_social_corporate($p),
        default        => pt_social_twitter_dark($p),
    };
}

function pt_social_twitter_dark(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $hl   = PT_Text::wrap($p['heading'], $w - $pad*2, 62, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], $w - $pad*2, 26, 0.52, 3);
    $hlh  = 74; $dlh = 36;
    $hy   = (int)($h * 0.38);
    $dy   = $hy + count($hl)*$hlh + 14;
    $icon = PT_Icons::icon_block($p['icon'], $pad + 38, (int)($h/2), 38, $p['accent_color'], $p['accent_color'], 24);
    $ai   = PT_Icons::icon_block($p['icon'], $w - $pad - 38, (int)($h/2), 42, $p['accent_color'], $p['accent_color'], 28);
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $fe   = PT_Text::e($p['footer']);
    $dots = PT_Renderer::dots(6, 8, $w-200, 20, 26, $ac, 0.18, 2);
    $fy   = $h - $pad + 8;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  $dots
  <rect x="0" y="0" width="$w" height="4" fill="$ac"/>
  $ai
  <text x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="62" font-weight="800" fill="$hc" letter-spacing="-1">$hs</text>
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="26" fill="$dc">$ds</text>
  <text x="$pad" y="$fy" font-family="'$font',sans-serif" font-size="18" fill="$ac">$fe</text>
</g></svg>
SVG;
}

function pt_social_linkedin(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $hl   = PT_Text::wrap($p['heading'], (int)($w * 0.6), 58, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], (int)($w * 0.6), 24, 0.52, 3);
    $hlh  = 70; $dlh = 32;
    $hy   = (int)($h * 0.32);
    $dy   = $hy + count($hl)*$hlh + 14;
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $fe   = PT_Text::e($p['footer']);
    $cx   = (int)($w * 0.8); $cy = (int)($h/2);
    $icon = PT_Icons::icon_block($p['icon'], $cx, $cy, 70, $p['heading_color'], $p['heading_color'], 48);
    $bg2  = PT_Color::h(PT_Color::darken($p['bg_color'], 0.15));
    $bar_rect_y = $h - $pad - 28;
    $bar_text_x = $pad + 60;
    $bar_text_y = $h - $pad - 10;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <linearGradient id="bg" x1="0" y1="0" x2="1" y2="0"><stop offset="0%" stop-color="$bg"/><stop offset="100%" stop-color="$bg2"/></linearGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="url(#bg)"/>
  <rect x="0" y="0" width="4" height="$h" fill="$ac"/>
  $icon
  <text x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="58" font-weight="800" fill="$hc" letter-spacing="-1">$hs</text>
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="24" fill="$dc">$ds</text>
  <rect x="$pad" y="$bar_rect_y" width="48" height="3" rx="1.5" fill="$ac" opacity="0.6"/>
  <text x="$bar_text_x" y="$bar_text_y" font-family="'$font',sans-serif" font-size="18" fill="$dc">$fe</text>
</g></svg>
SVG;
}

function pt_social_modern_dark(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $hl   = PT_Text::wrap($p['heading'], $w - $pad*2, 72, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], $w - $pad*2, 28, 0.52, 2);
    $hlh  = 86; $dlh = 36;
    $cx   = (int)($w/2); $hh = (int)($h/2);
    $hy   = (int)($h * 0.32);
    $dy   = $hy + count($hl)*$hlh + 16;
    $icon = PT_Icons::icon_block($p['icon'], $cx, $pad + 44, 44, $p['accent_color'], $p['accent_color'], 28);
    $hs   = PT_Text::tspans_center($hl, $cx, $hlh);
    $ds   = PT_Text::tspans_center($dl, $cx, $dlh);
    $fe   = PT_Text::e($p['footer']);
    $bg2  = PT_Color::h(PT_Color::lighten($p['bg_color'], 0.06));
    $wr   = (int)($w * 0.4);
    $fy   = $h - $pad + 8;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <radialGradient id="center" cx="50%" cy="50%" r="60%"><stop offset="0%" stop-color="$bg2"/><stop offset="100%" stop-color="$bg"/></radialGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="url(#center)"/>
  <circle cx="$cx" cy="$hh" r="$wr" fill="$ac" fill-opacity="0.04"/>
  $icon
  <text x="$cx" y="$hy" text-anchor="middle" font-family="'$font',sans-serif" font-size="72" font-weight="900" fill="$hc" letter-spacing="-2">$hs</text>
  <text x="$cx" y="$dy" text-anchor="middle" font-family="'$font',sans-serif" font-size="28" fill="$dc">$ds</text>
  <text x="$cx" y="$fy" text-anchor="middle" font-family="'$font',sans-serif" font-size="19" fill="$ac">$fe</text>
</g></svg>
SVG;
}

function pt_social_split(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $half  = (int)($w * 0.52);
    $hl    = PT_Text::wrap($p['heading'], $half - $pad*2, 54, 0.52, 3);
    $dl    = PT_Text::wrap($p['description'], $half - $pad*2, 22, 0.52, 4);
    $hlh   = 66; $dlh = 30;
    $hy    = (int)($h * 0.3);
    $dy    = $hy + count($hl)*$hlh + 14;
    $hs    = PT_Text::tspans($hl, $pad, $hlh);
    $ds    = PT_Text::tspans($dl, $pad, $dlh);
    $fe    = PT_Text::e($p['footer']);
    $rcx   = $half + (int)(($w - $half)/2);
    $rcy   = (int)($h/2);
    $icon  = PT_Icons::icon_block($p['icon'], $rcx, $rcy, 70, $p['accent_color'], $p['accent_color'], 44);
    $border  = PT_Color::h(PT_Color::darken($p['bg_color'], 0.1));
    $right_w = $w - $half;
    $fy   = $h - $pad + 6;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="$half" y="0" width="$right_w" height="$h" fill="$ac" opacity="0.08"/>
  <line x1="$half" y1="0" x2="$half" y2="$h" stroke="$border" stroke-width="1"/>
  <text x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="54" font-weight="800" fill="$hc" letter-spacing="-1">$hs</text>
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="22" fill="$dc">$ds</text>
  <text x="$pad" y="$fy" font-family="'$font',sans-serif" font-size="16" fill="$ac">$fe</text>
  $icon
</g></svg>
SVG;
}

function pt_social_corporate(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $hl     = PT_Text::wrap($p['heading'], $w - $pad*2, 58, 0.52, 2);
    $dl     = PT_Text::wrap($p['description'], $w - $pad*2, 24, 0.52, 3);
    $hlh    = 70; $dlh = 32;
    $hy     = (int)($h * 0.36);
    $dy     = $hy + count($hl)*$hlh + 14;
    $icon   = PT_Icons::icon_block($p['icon'], $pad + 34, $pad + 34, 34, $p['accent_color'], $p['accent_color'], 22);
    $hs     = PT_Text::tspans($hl, $pad, $hlh);
    $ds     = PT_Text::tspans($dl, $pad, $dlh);
    $fe     = PT_Text::e($p['footer']);
    $border  = PT_Color::h(PT_Color::darken($p['bg_color'], 0.08));
    $bot_sep = $h - 70;
    $fy      = $h - $pad + 2;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="72" fill="$ac" opacity="0.06"/>
  <rect x="0" y="70" width="$w" height="1" fill="$border"/>
  <rect x="0" y="$bot_sep" width="$w" height="1" fill="$border"/>
  <rect x="0" y="$bot_sep" width="$w" height="70" fill="$ac" opacity="0.04"/>
  $icon
  <text x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="58" font-weight="700" fill="$hc" letter-spacing="-0.5">$hs</text>
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="24" fill="$dc">$ds</text>
  <text x="$pad" y="$fy" font-family="'$font',sans-serif" font-size="18" fill="$ac" font-weight="600">$fe</text>
</g></svg>
SVG;
}


// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 3 — PLACEHOLDER IMAGES
// ═══════════════════════════════════════════════════════════════════════════════

function pt_render_placeholder(array $p): string
{
    return match($p['template']) {
        'grid'     => pt_placeholder_grid($p),
        'glass'    => pt_placeholder_glass($p),
        'gradient' => pt_placeholder_gradient($p),
        'pattern'  => pt_placeholder_pattern($p),
        default    => pt_placeholder_simple($p),
    };
}

function pt_placeholder_simple(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $label = $p['heading'] ?: "{$w} × {$h}";
    $sub   = $p['description'] ?: 'Placeholder';
    $cx = (int)($w/2); $cy = (int)($h/2);
    $border = PT_Color::h(PT_Color::darken($p['bg_color'], 0.12));
    $fs  = min(56, max(18, (int)($w * 0.04)));
    $fs2 = max(12, (int)($fs * 0.5));
    $le  = PT_Text::e($label);
    $se  = PT_Text::e($sub);
    $ty1 = $cy - (int)($fs2/2) - 4;
    $ty2 = $cy + $fs2 + 6;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <line x1="0" y1="0" x2="$w" y2="$h" stroke="$border" stroke-width="1"/>
  <line x1="$w" y1="0" x2="0" y2="$h" stroke="$border" stroke-width="1"/>
  <rect x="0" y="0" width="$w" height="$h" fill="none" stroke="$border" stroke-width="1"/>
  <text x="$cx" y="$ty1" text-anchor="middle" font-family="'$font',sans-serif" font-size="$fs" font-weight="700" fill="$hc">$le</text>
  <text x="$cx" y="$ty2" text-anchor="middle" font-family="'$font',sans-serif" font-size="$fs2" fill="$dc">$se</text>
</g></svg>
SVG;
}

function pt_placeholder_grid(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $label = $p['heading'] ?: "{$w} × {$h}";
    $cx = (int)($w/2); $cy = (int)($h/2);
    $step = max(20, (int)(min($w,$h)/24));
    $grid = '';
    for ($gx = 0; $gx <= $w; $gx += $step) {
        $grid .= "<line x1='$gx' y1='0' x2='$gx' y2='$h' stroke='$ac' stroke-width='0.5' opacity='0.3'/>";
    }
    for ($gy = 0; $gy <= $h; $gy += $step) {
        $grid .= "<line x1='0' y1='$gy' x2='$w' y2='$gy' stroke='$ac' stroke-width='0.5' opacity='0.3'/>";
    }
    $fs  = min(42, max(16, (int)($w * 0.035)));
    $le  = PT_Text::e($label);
    $icy = $cy - $fs - 20;
    $icon = PT_Icons::icon_block($p['icon'], $cx, $icy, 34, $p['accent_color'], $p['accent_color'], 22);
    $ty = $cy + 24;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  $grid
  $icon
  <text x="$cx" y="$ty" text-anchor="middle" font-family="'$font',monospace" font-size="$fs" font-weight="700" fill="$hc">$le</text>
</g></svg>
SVG;
}

function pt_placeholder_glass(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $label = $p['heading'] ?: "{$w} × {$h}";
    $sub   = $p['description'] ?: 'Placeholder Image';
    $cx = (int)($w/2); $cy = (int)($h/2);
    $bg2   = PT_Color::h(PT_Color::lighten($p['bg_color'], 0.15));
    $fs  = min(44, max(18, (int)($w * 0.04)));
    $fs2 = max(12, (int)($fs / 2));
    $le  = PT_Text::e($label);
    $se  = PT_Text::e($sub);
    $icy = $cy - $fs - 24;
    $icon = PT_Icons::icon_block($p['icon'], $cx, $icy, 38, $p['accent_color'], $p['accent_color'], 26);
    $c1cx = (int)($w * 0.8); $c1cy = (int)($h * 0.2); $c1r = (int)($w * 0.3);
    $c2cx = (int)($w * 0.1); $c2cy = (int)($h * 0.8); $c2r = (int)($w * 0.25);
    $bx = $cx - 180; $by = $cy - 100;
    $ty1 = $cy + 20; $ty2 = $cy + 50;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <radialGradient id="bg" cx="30%" cy="30%" r="70%"><stop offset="0%" stop-color="$bg2"/><stop offset="100%" stop-color="$bg"/></radialGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="url(#bg)"/>
  <circle cx="$c1cx" cy="$c1cy" r="$c1r" fill="$ac" fill-opacity="0.1"/>
  <circle cx="$c2cx" cy="$c2cy" r="$c2r" fill="$ac" fill-opacity="0.08"/>
  <rect x="$bx" y="$by" width="360" height="200" rx="16" fill="$hc" fill-opacity="0.08" stroke="$hc" stroke-opacity="0.15" stroke-width="1"/>
  $icon
  <text x="$cx" y="$ty1" text-anchor="middle" font-family="'$font',sans-serif" font-size="$fs" font-weight="700" fill="$hc">$le</text>
  <text x="$cx" y="$ty2" text-anchor="middle" font-family="'$font',sans-serif" font-size="$fs2" fill="$dc">$se</text>
</g></svg>
SVG;
}

function pt_placeholder_gradient(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $label = $p['heading'] ?: "{$w} × {$h}";
    $cx = (int)($w/2); $cy = (int)($h/2);
    $bg2 = PT_Color::h(PT_Color::darken($p['bg_color'], 0.25));
    $fs  = min(44, max(16, (int)($w * 0.04)));
    $le  = PT_Text::e($label);
    $icy = $cy - $fs - 18;
    $icon = PT_Icons::icon_block($p['icon'], $cx, $icy, 36, $p['heading_color'], $p['heading_color'], 24);
    $ty = $cy + 20;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="$bg"/><stop offset="100%" stop-color="$bg2"/></linearGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="url(#g)"/>
  $icon
  <text x="$cx" y="$ty" text-anchor="middle" font-family="'$font',sans-serif" font-size="$fs" font-weight="800" fill="$hc">$le</text>
</g></svg>
SVG;
}

function pt_placeholder_pattern(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $label = $p['heading'] ?: "{$w} × {$h}";
    $cx = (int)($w/2); $cy = (int)($h/2);
    $dots = PT_Renderer::dots((int)($w/30)+1, (int)($h/30)+1, 0, 0, 30, $ac, 0.2, 2.5);
    $fs   = min(44, max(16, (int)($w * 0.04)));
    $le   = PT_Text::e($label);
    $icy  = $cy - $fs - 18;
    $icon = PT_Icons::icon_block($p['icon'], $cx, $icy, 36, $p['accent_color'], $p['accent_color'], 24);
    $bx   = $cx - 220; $by = $cy - 110;
    $ty   = $cy + 22;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  $dots
  <rect x="$bx" y="$by" width="440" height="220" rx="16" fill="$bg" fill-opacity="0.7"/>
  $icon
  <text x="$cx" y="$ty" text-anchor="middle" font-family="'$font',sans-serif" font-size="$fs" font-weight="700" fill="$hc">$le</text>
</g></svg>
SVG;
}


// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 4 — GITHUB CARDS  (1200 × 600)
// ═══════════════════════════════════════════════════════════════════════════════

function pt_render_github(array $p): string
{
    return match($p['template']) {
        'repo_light' => pt_github_repo_light($p),
        'stats'      => pt_github_stats($p),
        'compact'    => pt_github_compact($p),
        'gradient'   => pt_github_gradient($p),
        default      => pt_github_repo_dark($p),
    };
}

function pt_github_repo_dark(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $hl  = PT_Text::wrap($p['heading'], $w - $pad*2, 52, 0.52, 2);
    $dl  = PT_Text::wrap($p['description'], $w - $pad*2, 22, 0.52, 3);
    $hlh = 62; $dlh = 30;
    $hy  = $pad + 90;
    $dy  = $hy + count($hl)*$hlh + 12;
    $hs  = PT_Text::tspans($hl, $pad, $hlh);
    $ds  = PT_Text::tspans($dl, $pad, $dlh);
    $icon  = PT_Icons::icon_block($p['icon'], $pad + 32, $pad + 32, 32, $p['accent_color'], $p['accent_color'], 20);
    $stats = pt_gh_stats_row($p, $pad, $h - $pad - 30, $hc, $dc, $ac, $font);
    $fe    = PT_Text::e($p['footer'] ?: $p['username']);
    $tag   = PT_Text::e($p['tag']);
    $border  = '#30363d';
    $w1 = $w - 1; $h1 = $h - 1;
    $head_x = $pad + 70; $head_y = $pad + 24;
    $tag_x  = $pad + 70 + mb_strlen($p['footer'] ?: $p['username'])*11 + 20;
    $line_y  = $h - $pad - 70;
    $line_x2 = $w - $pad;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="1" fill="$border"/>
  <rect x="0" y="$h1" width="$w" height="1" fill="$border"/>
  <rect x="0" y="0" width="1" height="$h" fill="$border"/>
  <rect x="$w1" y="0" width="1" height="$h" fill="$border"/>
  $icon
  <text x="$head_x" y="$head_y" font-family="'$font',sans-serif" font-size="18" fill="$dc">$fe /</text>
  <text x="$tag_x" y="$head_y" font-family="'$font',sans-serif" font-size="18" font-weight="700" fill="$ac">$tag</text>
  <text x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="52" font-weight="800" fill="$hc" letter-spacing="-1">$hs</text>
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="22" fill="$dc">$ds</text>
  <line x1="$pad" y1="$line_y" x2="$line_x2" y2="$line_y" stroke="$border" stroke-width="1"/>
  $stats
</g></svg>
SVG;
}

function pt_github_repo_light(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $hl   = PT_Text::wrap($p['heading'], $w - $pad*2, 52, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], $w - $pad*2, 22, 0.52, 3);
    $hlh  = 62; $dlh = 30;
    $hy   = $pad + 90;
    $dy   = $hy + count($hl)*$hlh + 12;
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $icon   = PT_Icons::icon_block($p['icon'], $pad + 32, $pad + 32, 32, $p['accent_color'], $p['accent_color'], 20);
    $stats  = pt_gh_stats_row($p, $pad, $h - $pad - 30, $hc, $dc, $ac, $font);
    $fe     = PT_Text::e($p['footer'] ?: $p['username']);
    $tag    = PT_Text::e($p['tag']);
    $border = '#d0d7de';
    $head_x = $pad + 70; $head_y = $pad + 24;
    $tag_x  = $pad + 70 + mb_strlen($p['footer'] ?: $p['username'])*11 + 20;
    $line_y  = $h - $pad - 70;
    $line_x2 = $w - $pad;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg" stroke="$border" stroke-width="1"/>
  $icon
  <text x="$head_x" y="$head_y" font-family="'$font',sans-serif" font-size="18" fill="$dc">$fe /</text>
  <text x="$tag_x" y="$head_y" font-family="'$font',sans-serif" font-size="18" font-weight="700" fill="$ac">$tag</text>
  <text x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="52" font-weight="800" fill="$hc" letter-spacing="-1">$hs</text>
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="22" fill="$dc">$ds</text>
  <line x1="$pad" y1="$line_y" x2="$line_x2" y2="$line_y" stroke="$border" stroke-width="1"/>
  $stats
</g></svg>
SVG;
}

function pt_github_stats(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $cx    = (int)($w/2);
    $title = PT_Text::e($p['heading']);
    $sub   = PT_Text::e($p['subheading'] ?: ($p['footer'] ?: $p['username']));
    $border = '#21262d';
    $sl1l = PT_Text::e($p['stat1_label']); $sl1v = PT_Text::e($p['stat1_value']);
    $sl2l = PT_Text::e($p['stat2_label']); $sl2v = PT_Text::e($p['stat2_value']);
    $sl3l = PT_Text::e($p['stat3_label']); $sl3v = PT_Text::e($p['stat3_value']);
    $col_w = (int)(($w - $pad*2) / 3);
    $stat_y = (int)($h * 0.6);
    $lang_c = PT_Color::h($p['lang_color']);
    $lang   = PT_Text::e($p['lang']);
    $icon   = PT_Icons::icon_block($p['icon'], $cx, $pad + 60, 50, $p['accent_color'], $p['accent_color'], 32);
    $title_y   = $pad + 140; $sub_y = $pad + 168;
    $sep_y     = $stat_y - 20; $sep_x2 = $w - $pad;
    $col1cx    = $pad + (int)($col_w/2);
    $lbl_y     = $stat_y + 26;
    $col2x     = $pad + $col_w + (int)($col_w/2);
    $col3x     = $pad + $col_w*2 + (int)($col_w/2);
    $sep1x     = $pad + $col_w;
    $sep_bot   = $stat_y + 46;
    $sep2x     = $pad + $col_w*2;
    $lang_cx   = $pad; $lang_cy = $h - $pad - 20;
    $lang_tx   = $pad + 14; $lang_ty = $h - $pad - 13;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg" stroke="$border" stroke-width="1"/>
  $icon
  <text x="$cx" y="$title_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="42" font-weight="800" fill="$hc">$title</text>
  <text x="$cx" y="$sub_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="18" fill="$dc">$sub</text>
  <line x1="$pad" y1="$sep_y" x2="$sep_x2" y2="$sep_y" stroke="$border" stroke-width="1"/>
  <text x="$col1cx" y="$stat_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="36" font-weight="800" fill="$ac">$sl1v</text>
  <text x="$col1cx" y="$lbl_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="$dc">$sl1l</text>
  <line x1="$sep1x" y1="$sep_y" x2="$sep1x" y2="$sep_bot" stroke="$border" stroke-width="1"/>
  <text x="$col2x" y="$stat_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="36" font-weight="800" fill="$ac">$sl2v</text>
  <text x="$col2x" y="$lbl_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="$dc">$sl2l</text>
  <line x1="$sep2x" y1="$sep_y" x2="$sep2x" y2="$sep_bot" stroke="$border" stroke-width="1"/>
  <text x="$col3x" y="$stat_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="36" font-weight="800" fill="$ac">$sl3v</text>
  <text x="$col3x" y="$lbl_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="$dc">$sl3l</text>
  <circle cx="$lang_cx" cy="$lang_cy" r="7" fill="$lang_c"/>
  <text x="$lang_tx" y="$lang_ty" font-family="'$font',sans-serif" font-size="14" fill="$dc">$lang</text>
</g></svg>
SVG;
}

function pt_github_compact(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $hl  = PT_Text::wrap($p['heading'], (int)($w * 0.65), 42, 0.52, 2);
    $dl  = PT_Text::wrap($p['description'], $w - $pad*2, 18, 0.52, 2);
    $hlh = 52; $dlh = 26;
    $hy  = (int)($h * 0.38);
    $dy  = $hy + count($hl)*$hlh + 10;
    $hs  = PT_Text::tspans($hl, $pad, $hlh);
    $ds  = PT_Text::tspans($dl, $pad, $dlh);
    $icon  = PT_Icons::icon_block($p['icon'], $w - $pad - 50, (int)($h/2), 50, $p['accent_color'], $p['accent_color'], 32);
    $stats = pt_gh_stats_row($p, $pad, $h - $pad - 22, $hc, $dc, $ac, $font);
    $border  = '#30363d';
    $line_y  = $h - $pad - 54; $line_x2 = $w - $pad;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg" stroke="$border" stroke-width="1"/>
  <rect x="0" y="0" width="4" height="$h" fill="$ac"/>
  $icon
  <text x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="42" font-weight="800" fill="$hc" letter-spacing="-0.5">$hs</text>
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="18" fill="$dc">$ds</text>
  <line x1="$pad" y1="$line_y" x2="$line_x2" y2="$line_y" stroke="$border" stroke-width="1"/>
  $stats
</g></svg>
SVG;
}

function pt_github_gradient(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $bg2  = PT_Color::h(PT_Color::darken($p['bg_color'], 0.2));
    $hl   = PT_Text::wrap($p['heading'], $w - $pad*2, 58, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], $w - $pad*2, 24, 0.52, 3);
    $hlh  = 70; $dlh = 32;
    $hy   = (int)($h * 0.34);
    $dy   = $hy + count($hl)*$hlh + 14;
    $icon = PT_Icons::icon_block($p['icon'], $pad + 36, $pad + 36, 36, $p['heading_color'], $p['heading_color'], 24);
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $stats = pt_gh_stats_row($p, $pad, $h - $pad - 30, $hc, $dc, '#'.$p['heading_color'], $font);
    $cx   = (int)($w * 0.85); $cy = (int)($h * 0.2); $cr = (int)($h * 0.5);
    $line_y  = $h - $pad - 65; $line_x2 = $w - $pad;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="$bg"/><stop offset="100%" stop-color="$bg2"/></linearGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="url(#g)"/>
  <circle cx="$cx" cy="$cy" r="$cr" fill="$hc" fill-opacity="0.05"/>
  $icon
  <text x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="58" font-weight="800" fill="$hc" letter-spacing="-1">$hs</text>
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="24" fill="$dc">$ds</text>
  <line x1="$pad" y1="$line_y" x2="$line_x2" y2="$line_y" stroke="$hc" stroke-width="0.5" opacity="0.2"/>
  $stats
</g></svg>
SVG;
}

function pt_gh_stats_row(array $p, float $x, float $y, string $hc, string $dc, string $ac, string $font): string
{
    $s1l = PT_Text::e($p['stat1_label']); $s1v = PT_Text::e($p['stat1_value']);
    $s2l = PT_Text::e($p['stat2_label']); $s2v = PT_Text::e($p['stat2_value']);
    $s3l = PT_Text::e($p['stat3_label']); $s3v = PT_Text::e($p['stat3_value']);
    $lc   = PT_Color::h($p['lang_color']);
    $lang = PT_Text::e($p['lang']);
    $x2 = $x + 160; $x3 = $x + 260; $x4 = $x + 360;
    return "
    <circle cx='$x' cy='$y' r='6' fill='$lc'/>
    <text x='$x' y='$y' dy='5' dx='14' font-family=\"'$font',sans-serif\" font-size='13' fill='$dc'>$lang</text>
    <text x='$x2' y='$y' dy='5' font-family=\"'$font',sans-serif\" font-size='13' fill='$dc'>★ $s1v</text>
    <text x='$x3' y='$y' dy='5' font-family=\"'$font',sans-serif\" font-size='13' fill='$dc'>⑂ $s2v</text>
    <text x='$x4' y='$y' dy='5' font-family=\"'$font',sans-serif\" font-size='13' fill='$dc'>⊙ $s3v</text>";
}


// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 5 — BROWSER MOCKUPS  (1400 × 900)
// ═══════════════════════════════════════════════════════════════════════════════

function pt_render_browser(array $p): string
{
    return match($p['template']) {
        'chrome_light' => pt_browser_chrome_light($p),
        'safari'       => pt_browser_safari($p),
        'minimal'      => pt_browser_minimal($p),
        default        => pt_browser_chrome_dark($p),
    };
}

function pt_browser_chrome_dark(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_vars_np($p);
    $tb_h = 52; $tab_h = 38;
    $total = $tb_h + $tab_h;
    $url_e = PT_Text::e($p['url'] ?: 'https://awantools.site');
    $content = pt_br_content($p, $w, $h - $total, $total, $font);
    $dots    = PT_Renderer::traffic_lights(18, $tab_h + (int)($tb_h/2), 7, 20);
    $hl      = PT_Text::wrap($p['heading'], $w - 100, 28, 0.52, 1);
    $tab_t   = PT_Text::e($hl[0] ?? $p['heading']);
    $tab_h4  = $tab_h - 4;
    $url_x   = 130; $url_w = $w - 280;
    $url_by  = $tab_h + 10; $url_tx = (int)($w/2); $url_ty = $tab_h + 31;
    $bot_y   = $h - 1;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tab_h" fill="#3c3c3c"/>
  <rect x="0" y="$tab_h" width="$w" height="1" fill="#1a1a1a"/>
  <rect x="80" y="4" width="220" height="$tab_h4" rx="8" fill="#292929"/>
  <text x="104" y="27" font-family="'$font',sans-serif" font-size="13" fill="#cccccc">$tab_t</text>
  <rect x="0" y="$tab_h" width="$w" height="$tb_h" fill="#292929"/>
  $dots
  <rect x="$url_x" y="$url_by" width="$url_w" height="32" rx="16" fill="#404040"/>
  <text x="$url_tx" y="$url_ty" text-anchor="middle" font-family="'$font',monospace" font-size="13" fill="#8b8b8b">$url_e</text>
  $content
  <rect x="0" y="$bot_y" width="$w" height="1" fill="#1a1a1a"/>
</g></svg>
SVG;
}

function pt_browser_chrome_light(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_vars_np($p);
    $tb_h = 52; $tab_h = 38;
    $total = $tb_h + $tab_h;
    $url_e = PT_Text::e($p['url'] ?: 'https://awantools.site');
    $content = pt_br_content($p, $w, $h - $total, $total, $font);
    $dots    = PT_Renderer::traffic_lights(18, $tab_h + (int)($tb_h/2), 7, 20);
    $hl      = PT_Text::wrap($p['heading'], $w - 100, 28, 0.52, 1);
    $tab_t   = PT_Text::e($hl[0] ?? $p['heading']);
    $tab_h4  = $tab_h - 4;
    $url_x   = 130; $url_w = $w - 280;
    $url_by  = $tab_h + 10; $url_tx = (int)($w/2); $url_ty = $tab_h + 31;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tab_h" fill="#dee1e6"/>
  <rect x="0" y="$tab_h" width="$w" height="1" fill="#c6c6c6"/>
  <rect x="80" y="4" width="220" height="$tab_h4" rx="8" fill="#f8f9fa"/>
  <text x="104" y="27" font-family="'$font',sans-serif" font-size="13" fill="#333333">$tab_t</text>
  <rect x="0" y="$tab_h" width="$w" height="$tb_h" fill="#f1f3f4"/>
  $dots
  <rect x="$url_x" y="$url_by" width="$url_w" height="32" rx="16" fill="#ffffff" stroke="#c6c6c6" stroke-width="1"/>
  <text x="$url_tx" y="$url_ty" text-anchor="middle" font-family="'$font',monospace" font-size="13" fill="#606368">$url_e</text>
  $content
  <rect x="0" y="0" width="$w" height="$h" rx="$r" fill="none" stroke="#c6c6c6" stroke-width="1"/>
</g></svg>
SVG;
}

function pt_browser_safari(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_vars_np($p);
    $tb_h = 60;
    $url_e = PT_Text::e($p['url'] ?: 'https://awantools.site');
    $content = pt_br_content($p, $w, $h - $tb_h, $tb_h, $font);
    $dots    = PT_Renderer::traffic_lights(18, (int)($tb_h/2), 7, 20);
    $uhalf   = (int)($w/2);
    $urx     = $uhalf - 240; $ury = (int)($tb_h/2) - 16;
    $uty     = (int)($tb_h/2) + 6;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="#ececec"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="#c0c0c0"/>
  $dots
  <rect x="$urx" y="$ury" width="480" height="32" rx="10" fill="#ffffff" stroke="#c0c0c0" stroke-width="1"/>
  <text x="$uhalf" y="$uty" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="#333">$url_e</text>
  $content
  <rect x="0" y="0" width="$w" height="$h" rx="$r" fill="none" stroke="#c0c0c0" stroke-width="1"/>
</g></svg>
SVG;
}

function pt_browser_minimal(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_vars_np($p);
    $tb_h   = 44;
    $tb_bg  = PT_Color::h(PT_Color::lighten($p['bg_color'], 0.06));
    $border = PT_Color::h(PT_Color::lighten($p['bg_color'], 0.12));
    $url_e  = PT_Text::e($p['url'] ?: 'https://awantools.site');
    $content = pt_br_content($p, $w, $h - $tb_h, $tb_h, $font);
    $dots    = PT_Renderer::traffic_lights(14, (int)($tb_h/2), 6, 17);
    $cx      = (int)($w/2);
    $tb_mid  = (int)($tb_h/2) + 5;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="$tb_bg"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="$border"/>
  $dots
  <text x="$cx" y="$tb_mid" text-anchor="middle" font-family="'$font',monospace" font-size="12" fill="$dc">$url_e</text>
  $content
</g></svg>
SVG;
}

function pt_br_content(array $p, int $w, int $content_h, int $offset, string $font): string
{
    $bg2    = PT_Color::h(PT_Color::lighten($p['bg_color'], 0.04));
    $hc     = PT_Color::h($p['heading_color']);
    $dc     = PT_Color::h($p['description_color']);
    $ac     = PT_Color::h($p['accent_color']);
    $cx     = (int)($w/2);
    $hl     = PT_Text::wrap($p['heading'], $w - 160, 44, 0.52, 2);
    $dl     = PT_Text::wrap($p['description'], $w - 200, 20, 0.52, 3);
    $hlh    = 56; $dlh = 28;
    $hy     = $offset + (int)($content_h * 0.36);
    $dy     = $hy + count($hl)*$hlh + 14;
    $hs     = PT_Text::tspans_center($hl, $cx, $hlh);
    $ds     = PT_Text::tspans_center($dl, $cx, $dlh);
    $icx    = $cx; $icy = $offset + (int)($content_h * 0.18);
    $icon   = PT_Icons::icon_block($p['icon'], $icx, $icy, 36, $p['accent_color'], $p['accent_color'], 24);
    $nav_h  = 44;
    $nav_bg = PT_Color::h(PT_Color::darken($p['bg_color'], 0.04));
    $nav_bd = PT_Color::h(PT_Color::darken($p['bg_color'], 0.08));
    $nav_sep_y  = $offset + $nav_h;
    $nav_text_y = $offset + (int)($nav_h/2) + 6;
    $btn_y  = $dy + count($dl)*$dlh + 16;
    $btn_x  = $cx - 50;
    $btn_ty = $btn_y + 21;
    $he     = PT_Text::e($p['heading']);
    $fe     = PT_Text::e($p['footer'] ?: 'Get Started');
    return "
    <rect x='0' y='$offset' width='$w' height='$content_h' fill='$bg2'/>
    <rect x='0' y='$offset' width='$w' height='$nav_h' fill='$nav_bg'/>
    <rect x='0' y='$nav_sep_y' width='$w' height='1' fill='$nav_bd'/>
    <text x='$cx' y='$nav_text_y' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='14' font-weight='600' fill='$hc'>$he</text>
    $icon
    <text x='$cx' y='$hy' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='44' font-weight='800' fill='$hc'>$hs</text>
    <text x='$cx' y='$dy' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='20' fill='$dc'>$ds</text>
    <rect x='$btn_x' y='$btn_y' width='100' height='32' rx='8' fill='$ac'/>
    <text x='$cx' y='$btn_ty' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='13' font-weight='600' fill='white'>$fe</text>";
}


// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 6 — TERMINAL  (1100 × 660)
// ═══════════════════════════════════════════════════════════════════════════════

function pt_render_terminal(array $p): string
{
    return match($p['template']) {
        'linux'   => pt_terminal_linux($p),
        'dark'    => pt_terminal_dark($p),
        'minimal' => pt_terminal_minimal($p),
        default   => pt_terminal_macos($p),
    };
}

function pt_terminal_macos(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_vars_np($p);
    $tb_h = 44; $cx = (int)($w/2); $tb_mid = (int)($tb_h/2) + 5;
    $dots    = PT_Renderer::traffic_lights(16, (int)($tb_h/2), 7, 22);
    $title   = PT_Text::e($p['heading'] ?: 'Terminal');
    $lines   = pt_term_build_lines($p, $w - 60, $font);
    $content = pt_term_lines_svg($lines, 40, $tb_h + 20, $dc, $ac, $hc, $font, $h);
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="#2e2e2e"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="#1a1a1a"/>
  $dots
  <text x="$cx" y="$tb_mid" text-anchor="middle" font-family="'$font',monospace" font-size="14" fill="#888888">$title</text>
  $content
</g></svg>
SVG;
}

function pt_terminal_linux(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_vars_np($p);
    $tb_h = 36; $cx = (int)($w/2); $tb_mid = (int)($tb_h/2) + 5;
    $title   = PT_Text::e($p['heading'] ?: 'bash');
    $lines   = pt_term_build_lines($p, $w - 60, $font);
    $content = pt_term_lines_svg($lines, 40, $tb_h + 16, $dc, $ac, $hc, $font, $h);
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="#1a1a1a"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="$ac" opacity="0.4"/>
  <rect x="6" y="6" width="20" height="22" rx="2" fill="#ff5555" opacity="0.8"/>
  <rect x="30" y="6" width="20" height="22" rx="2" fill="#ffb86c" opacity="0.8"/>
  <rect x="54" y="6" width="20" height="22" rx="2" fill="#50fa7b" opacity="0.8"/>
  <text x="$cx" y="$tb_mid" text-anchor="middle" font-family="'$font',monospace" font-size="13" fill="#888">$title</text>
  $content
</g></svg>
SVG;
}

function pt_terminal_dark(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_vars_np($p);
    $tb_h   = 50; $cx = (int)($w/2); $tb_mid = (int)($tb_h/2) + 5;
    $tb_bg  = PT_Color::h(PT_Color::darken($p['bg_color'], 0.15));
    $title  = PT_Text::e($p['heading'] ?: 'Terminal');
    $dots   = PT_Renderer::traffic_lights(18, (int)($tb_h/2), 7, 22);
    $lines  = pt_term_build_lines($p, $w - 60, $font);
    $content = pt_term_lines_svg($lines, 40, $tb_h + 20, $dc, $ac, $hc, $font, $h);
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="$tb_bg"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="$ac" opacity="0.3"/>
  $dots
  <text x="$cx" y="$tb_mid" text-anchor="middle" font-family="'$font',monospace" font-size="14" fill="$dc">$title</text>
  $content
</g></svg>
SVG;
}

function pt_terminal_minimal(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_vars_np($p);
    $lines   = pt_term_build_lines($p, $w - 60, $font);
    $content = pt_term_lines_svg($lines, 40, 30, $dc, $ac, $hc, $font, $h);
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="3" height="$h" fill="$ac" opacity="0.7"/>
  $content
</g></svg>
SVG;
}

function pt_term_build_lines(array $p, int $max_w, string $font): array
{
    $fs = 16;
    $max_chars = max(20, (int)($max_w / ($fs * 0.60)));
    $all = [];
    $all[] = ['type' => 'prompt', 'text' => $p['prompt']];
    foreach (['line1','line2','line3','line4'] as $k) {
        if ($p[$k] !== '') $all[] = ['type' => 'output', 'text' => $p[$k]];
    }
    if ($p['description'] !== '') {
        $wrapped = PT_Text::mono_wrap($p['description'], $max_chars, 6);
        foreach ($wrapped as $l) $all[] = ['type' => 'output', 'text' => $l];
    }
    if (count($all) < 3) {
        $all[] = ['type' => 'output', 'text' => ''];
        $all[] = ['type' => 'cursor', 'text' => ''];
    }
    return $all;
}

function pt_term_lines_svg(array $lines, float $x, float $start_y, string $dc, string $ac, string $hc, string $font, int $max_h): string
{
    $out = ''; $y = $start_y; $lh = 28; $fs = 16;
    foreach ($lines as $line) {
        if ($y + $lh > $max_h - 20) break;
        $te = PT_Text::e($line['text']);
        if ($line['type'] === 'prompt') {
            $out .= "<text x='$x' y='$y' font-family=\"'$font',monospace\" font-size='$fs' fill='$ac'>$te</text>";
        } elseif ($line['type'] === 'cursor') {
            $cy = (int)($y - 16);
            $out .= "<rect x='$x' y='$cy' width='10' height='20' fill='$hc' opacity='0.8'/>";
        } else {
            $out .= "<text x='$x' y='$y' font-family=\"'$font',monospace\" font-size='$fs' fill='$dc'>$te</text>";
        }
        $y += $lh;
    }
    if ($y < $max_h - 20) {
        $cy = (int)($y - 16);
        $out .= "<rect x='$x' y='$cy' width='10' height='20' fill='$hc' opacity='0.7'/>";
    }
    return $out;
}


// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 7 — PROFILE CARDS  (800 × 460)
// ═══════════════════════════════════════════════════════════════════════════════

function pt_render_profile(array $p): string
{
    return match($p['template']) {
        'modern'    => pt_profile_modern($p),
        'dark'      => pt_profile_dark($p),
        'glass'     => pt_profile_glass($p),
        'corporate' => pt_profile_corporate($p),
        default     => pt_profile_minimal($p),
    };
}

function pt_profile_minimal(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $cx     = (int)($w/2);
    $av_r   = min(60, (int)($h * 0.15));
    $av_cy  = $pad + $av_r;
    $name_y = $av_cy + $av_r + 34;
    $ne     = PT_Text::e($p['heading']);
    $re     = PT_Text::e($p['subheading'] ?: '');
    $dl     = PT_Text::wrap($p['description'], $w - $pad*2, 16, 0.52, 3);
    $dy     = $name_y + 52;
    $ds     = PT_Text::tspans_center($dl, $cx, 24);
    $fe     = PT_Text::e($p['footer']);
    $border = PT_Color::h(PT_Color::darken($p['bg_color'], 0.1));
    $tags   = pt_pf_tags($p, $cx, $dy + count($dl)*24 + 16, $ac, $font);
    $icon_r = (int)($av_r * 0.62);
    $icon_s = PT_Icons::icon_block($p['icon'], $cx, $av_cy, $av_r, $p['accent_color'], $p['accent_color'], $icon_r);
    $role_y  = $name_y + 26;
    $foot_y  = $h - $pad + 10;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg" stroke="$border" stroke-width="1"/>
  <circle cx="$cx" cy="$av_cy" r="$av_r" fill="none" stroke="$ac" stroke-width="2.5"/>
  $icon_s
  <text x="$cx" y="$name_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="32" font-weight="700" fill="$hc">$ne</text>
  <text x="$cx" y="$role_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="15" fill="$ac" font-weight="500">$re</text>
  <text x="$cx" y="$dy" text-anchor="middle" font-family="'$font',sans-serif" font-size="15" fill="$dc">$ds</text>
  $tags
  <text x="$cx" y="$foot_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="13" fill="$dc">$fe</text>
</g></svg>
SVG;
}

function pt_profile_modern(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $bg2     = PT_Color::h(PT_Color::darken($p['bg_color'], 0.2));
    $cx      = (int)($w/2);
    $banner_h = (int)($h * 0.38);
    $av_r    = min(52, (int)($h * 0.13));
    $av_cy   = $banner_h;
    $name_y  = $banner_h + $av_r + 32;
    $ne      = PT_Text::e($p['heading']);
    $re      = PT_Text::e($p['subheading'] ?: '');
    $dl      = PT_Text::wrap($p['description'], $w - $pad*2, 15, 0.52, 2);
    $ds      = PT_Text::tspans_center($dl, $cx, 22);
    $fe      = PT_Text::e($p['footer']);
    $av_r5   = $av_r + 5;
    $icon_s  = PT_Icons::icon_block($p['icon'], $cx, $av_cy, $av_r, $p['bg_color'], $p['accent_color'], (int)($av_r * 0.6));
    $role_y  = $name_y + 26; $desc_y = $name_y + 56; $foot_y = $h - $pad + 10;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <linearGradient id="banner" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="$bg"/><stop offset="100%" stop-color="$bg2"/></linearGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="#ffffff"/>
  <rect x="0" y="0" width="$w" height="$banner_h" fill="url(#banner)"/>
  <circle cx="$cx" cy="$av_cy" r="$av_r5" fill="white"/>
  <circle cx="$cx" cy="$av_cy" r="$av_r" fill="$bg"/>
  $icon_s
  <text x="$cx" y="$name_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="28" font-weight="700" fill="#111827">$ne</text>
  <text x="$cx" y="$role_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="$bg" font-weight="600">$re</text>
  <text x="$cx" y="$desc_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="#6b7280">$ds</text>
  <text x="$cx" y="$foot_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="12" fill="#9ca3af">$fe</text>
</g></svg>
SVG;
}

function pt_profile_dark(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $dots   = PT_Renderer::dots(8, 6, $w - 240, 20, 28, $ac, 0.18, 2);
    $cx     = (int)($w * 0.32);
    $av_r   = min(70, (int)($h * 0.18));
    $av_cy  = (int)($h/2);
    $name_x = (int)($w * 0.5) + $pad;
    $ne     = PT_Text::e($p['heading']);
    $re     = PT_Text::e($p['subheading'] ?: '');
    $dl     = PT_Text::wrap($p['description'], $w - $name_x - $pad, 16, 0.52, 3);
    $ds     = PT_Text::tspans($dl, $name_x, 22);
    $fe     = PT_Text::e($p['footer']);
    $line_x = (int)($w * 0.5); $line_y2 = $h - $pad;
    $icon_s = PT_Icons::icon_block($p['icon'], $cx, $av_cy, $av_r, $p['accent_color'], $p['accent_color'], (int)($av_r * 0.55));
    $name_y = (int)($h * 0.35); $role_y = $name_y + 32;
    $desc_y = $name_y + 72; $foot_y = $h - $pad + 10;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  $dots
  <line x1="$line_x" y1="$pad" x2="$line_x" y2="$line_y2" stroke="$dc" stroke-width="0.5" opacity="0.2"/>
  $icon_s
  <text x="$name_x" y="$name_y" font-family="'$font',sans-serif" font-size="34" font-weight="800" fill="$hc">$ne</text>
  <text x="$name_x" y="$role_y" font-family="'$font',sans-serif" font-size="15" fill="$ac" font-weight="500">$re</text>
  <text x="$name_x" y="$desc_y" font-family="'$font',sans-serif" font-size="15" fill="$dc">$ds</text>
  <text x="$name_x" y="$foot_y" font-family="'$font',sans-serif" font-size="13" fill="$dc">$fe</text>
</g></svg>
SVG;
}

function pt_profile_glass(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $bg2    = PT_Color::h(PT_Color::lighten($p['bg_color'], 0.15));
    $cx     = (int)($w/2);
    $av_r   = min(56, (int)($h * 0.15));
    $av_cy  = $pad + $av_r;
    $name_y = $av_cy + $av_r + 32;
    $ne     = PT_Text::e($p['heading']);
    $re     = PT_Text::e($p['subheading'] ?: '');
    $dl     = PT_Text::wrap($p['description'], $w - $pad*3, 15, 0.52, 2);
    $ds     = PT_Text::tspans_center($dl, $cx, 22);
    $fe     = PT_Text::e($p['footer']);
    $cp1w = (int)($w - $pad*3); $cp1h = (int)($h - $pad*3);
    $cp1x = (int)($pad*1.5);    $cp1y = (int)($pad*1.5);
    $icon_s = PT_Icons::icon_block($p['icon'], $cx, $av_cy, $av_r, $p['accent_color'], $p['accent_color'], (int)($av_r * 0.58));
    $c02w = (int)($w * 0.2); $c02h = (int)($h * 0.2);
    $role_y = $name_y + 26; $desc_y = $name_y + 54; $foot_y = $h - $pad + 10;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <radialGradient id="bg" cx="30%" cy="30%" r="70%"><stop offset="0%" stop-color="$bg2"/><stop offset="100%" stop-color="$bg"/></radialGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="url(#bg)"/>
  <circle cx="$c02w" cy="$c02h" r="$h" fill="$ac" fill-opacity="0.08"/>
  <rect x="$cp1x" y="$cp1y" width="$cp1w" height="$cp1h" rx="16" fill="white" fill-opacity="0.07" stroke="white" stroke-opacity="0.12" stroke-width="1"/>
  <circle cx="$cx" cy="$av_cy" r="$av_r" fill="$ac" opacity="0.2"/>
  <circle cx="$cx" cy="$av_cy" r="$av_r" fill="none" stroke="$ac" stroke-width="2" opacity="0.6"/>
  $icon_s
  <text x="$cx" y="$name_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="30" font-weight="700" fill="$hc">$ne</text>
  <text x="$cx" y="$role_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="$ac">$re</text>
  <text x="$cx" y="$desc_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="$dc">$ds</text>
  <text x="$cx" y="$foot_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="12" fill="$dc" opacity="0.7">$fe</text>
</g></svg>
SVG;
}

function pt_profile_corporate(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);
    $side_w = (int)($w * 0.3);
    $av_cx  = (int)($side_w / 2);
    $av_cy  = (int)($h / 2);
    $av_r   = min(60, (int)($h * 0.16));
    $name_x = $side_w + $pad;
    $ne     = PT_Text::e($p['heading']);
    $re     = PT_Text::e($p['subheading'] ?: '');
    $dl     = PT_Text::wrap($p['description'], $w - $side_w - $pad*2, 16, 0.52, 3);
    $ds     = PT_Text::tspans($dl, $name_x, 24);
    $fe     = PT_Text::e($p['footer']);
    $border = PT_Color::h(PT_Color::darken($p['bg_color'], 0.1));
    $icon_s = PT_Icons::icon_block($p['icon'], $av_cx, $av_cy, $av_r, $p['accent_color'], $p['accent_color'], (int)($av_r * 0.6));
    $name_y = (int)($h * 0.3); $role_y = $name_y + 28;
    $line_y = $name_y + 44; $line_x2 = $name_x + 60;
    $desc_y = $name_y + 72; $foot_y = $h - $pad + 10;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$side_w" height="$h" fill="$ac" opacity="0.08"/>
  <line x1="$side_w" y1="0" x2="$side_w" y2="$h" stroke="$border" stroke-width="1"/>
  <rect x="0" y="0" width="$w" height="4" fill="$ac"/>
  $icon_s
  <text x="$name_x" y="$name_y" font-family="'$font',sans-serif" font-size="32" font-weight="700" fill="$hc">$ne</text>
  <text x="$name_x" y="$role_y" font-family="'$font',sans-serif" font-size="15" fill="$ac" font-weight="500">$re</text>
  <line x1="$name_x" y1="$line_y" x2="$line_x2" y2="$line_y" stroke="$ac" stroke-width="2"/>
  <text x="$name_x" y="$desc_y" font-family="'$font',sans-serif" font-size="15" fill="$dc">$ds</text>
  <text x="$name_x" y="$foot_y" font-family="'$font',sans-serif" font-size="13" fill="$dc">$fe</text>
</g></svg>
SVG;
}

function pt_pf_tags(array $p, float $cx, float $y, string $ac, string $font): string
{
    if (!$p['badge']) return '';
    $tags_raw = explode(',', $p['badge']);
    $out = ''; $gap = 10; $tag_h = 24; $total_w = 0;
    $tag_data = [];
    foreach (array_slice($tags_raw, 0, 5) as $t) {
        $t  = trim($t);
        $tw = mb_strlen($t) * 9 + 20;
        $tag_data[] = ['text' => $t, 'w' => $tw];
        $total_w += $tw + $gap;
    }
    $total_w -= $gap;
    $sx = $cx - $total_w / 2;
    foreach ($tag_data as $td) {
        $te = PT_Text::e($td['text']);
        $tw = $td['w'];
        $mx = $sx + $tw / 2;
        $ty = $y + 16;
        $out .= "<rect x='$sx' y='$y' width='$tw' height='$tag_h' rx='12' fill='$ac' opacity='0.15'/>";
        $out .= "<text x='$mx' y='$ty' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='11' font-weight='600' fill='$ac'>$te</text>";
        $sx += $tw + $gap;
    }
    return $out;
}


// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 8 — CODE SNIPPETS  (1000 × 600)
// ═══════════════════════════════════════════════════════════════════════════════

function pt_render_code(array $p): string
{
    return match($p['template']) {
        'github_dark' => pt_code_github_dark($p),
        'dracula'     => pt_code_dracula($p),
        'minimal'     => pt_code_minimal($p),
        default       => pt_code_dark($p),
    };
}

function pt_code_dark(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_vars_np($p);
    $tb_h    = 44; $tb_bg = '#252526'; $border = '#1e1e1e';
    $dots    = PT_Renderer::traffic_lights(16, (int)($tb_h/2), 7, 22);
    $lang_e  = PT_Text::e(strtolower($p['language'] ?: 'js'));
    $title   = PT_Text::e($p['heading'] ?: 'snippet.js');
    $lines   = pt_code_tokenize($p, 'vscode_dark');
    $svg_l   = pt_code_lines_svg($lines, 40, $tb_h + 18, 26, $font, 15, $h - 20);
    $cx      = (int)($w/2); $tb_mid = (int)($tb_h/2) + 5;
    $gutter_h = $h - $tb_h;
    $badge_x  = $w - 70; $badge_y = $h - 30;
    $badge_tx = $w - 40; $badge_ty = $h - 15;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="$tb_bg"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="$border"/>
  $dots
  <text x="$cx" y="$tb_mid" text-anchor="middle" font-family="'$font',monospace" font-size="13" fill="#888888">$title</text>
  <rect x="0" y="$tb_h" width="30" height="$gutter_h" fill="$tb_bg" opacity="0.5"/>
  $svg_l
  <rect x="$badge_x" y="$badge_y" width="60" height="20" rx="4" fill="$ac" opacity="0.2"/>
  <text x="$badge_tx" y="$badge_ty" text-anchor="middle" font-family="'$font',monospace" font-size="11" fill="$ac">$lang_e</text>
</g></svg>
SVG;
}

function pt_code_github_dark(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_vars_np($p);
    $tb_h    = 48; $tb_bg = '#161b22'; $border = '#30363d';
    $title   = PT_Text::e($p['heading'] ?: 'snippet.js');
    $lang_e  = PT_Text::e(strtolower($p['language'] ?: 'javascript'));
    $dots    = PT_Renderer::traffic_lights(16, (int)($tb_h/2), 7, 22);
    $lines   = pt_code_tokenize($p, 'github_dark');
    $svg_l   = pt_code_lines_svg($lines, 50, $tb_h + 18, 26, $font, 15, $h - 20);
    $badge_x = $w - 100; $badge_y = (int)($tb_h/2) - 12;
    $badge_tx = $w - 60;  $badge_ty = (int)($tb_h/2) + 5;
    $gutter_h = $h - $tb_h;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg" stroke="$border" stroke-width="1"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="$tb_bg"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="$border"/>
  $dots
  <rect x="$badge_x" y="$badge_y" width="80" height="24" rx="12" fill="#30363d"/>
  <text x="$badge_tx" y="$badge_ty" text-anchor="middle" font-family="'$font',monospace" font-size="12" fill="#8b949e">$lang_e</text>
  <text x="18" y="$badge_ty" font-family="'$font',monospace" font-size="13" fill="#8b949e">$title</text>
  <rect x="0" y="$tb_h" width="40" height="$gutter_h" fill="#0d1117" opacity="0.5"/>
  $svg_l
</g></svg>
SVG;
}

function pt_code_dracula(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_vars_np($p);
    $tb_h    = 44; $tb_bg = '#21222c'; $border = '#1e1f29';
    $title   = PT_Text::e($p['heading'] ?: 'snippet.js');
    $dots    = PT_Renderer::traffic_lights(16, (int)($tb_h/2), 7, 22);
    $lines   = pt_code_tokenize($p, 'dracula');
    $svg_l   = pt_code_lines_svg($lines, 40, $tb_h + 18, 26, $font, 15, $h - 20);
    $cx      = (int)($w/2); $tb_mid = (int)($tb_h/2) + 5;
    $gutter_h = $h - $tb_h; $bar_y = $h - 3;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="$tb_bg"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="$border"/>
  $dots
  <text x="$cx" y="$tb_mid" text-anchor="middle" font-family="'$font',monospace" font-size="13" fill="#6272a4">$title</text>
  <rect x="0" y="$tb_h" width="32" height="$gutter_h" fill="$tb_bg" opacity="0.6"/>
  $svg_l
  <rect x="0" y="$bar_y" width="$w" height="3" fill="#bd93f9"/>
</g></svg>
SVG;
}

function pt_code_minimal(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_vars_np($p);
    $tb_h    = 44; $tb_bg = '#f0f0f0'; $border = '#e0e0e0';
    $title   = PT_Text::e($p['heading'] ?: 'snippet.js');
    $dots    = PT_Renderer::traffic_lights(16, (int)($tb_h/2), 7, 22);
    $lines   = pt_code_tokenize($p, 'minimal_light');
    $svg_l   = pt_code_lines_svg($lines, 40, $tb_h + 18, 26, $font, 15, $h - 20);
    $cx      = (int)($w/2); $tb_mid = (int)($tb_h/2) + 5;
    $gutter_h = $h - $tb_h;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg" stroke="$border" stroke-width="1"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="$tb_bg"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="$border"/>
  $dots
  <text x="$cx" y="$tb_mid" text-anchor="middle" font-family="'$font',monospace" font-size="13" fill="#666666">$title</text>
  <rect x="0" y="$tb_h" width="32" height="$gutter_h" fill="#f8f8f8"/>
  <rect x="32" y="$tb_h" width="1" height="$gutter_h" fill="$border"/>
  $svg_l
</g></svg>
SVG;
}

function pt_code_tokenize(array $p, string $theme): array
{
    $raw  = $p['code'] ?: "const greeting = 'Hello, World!';\nconsole.log(greeting);\n\nfunction add(a, b) {\n  return a + b;\n}\n\nexport default { greeting, add };";
    $lines = explode("\n", $raw);
    $themes = [
        'vscode_dark'   => ['kw'=>'#569cd6','str'=>'#ce9178','num'=>'#b5cea8','cm'=>'#6a9955','fn'=>'#dcdcaa','var'=>'#9cdcfe','def'=>'#d4d4d4','op'=>'#d4d4d4','ln'=>'#858585'],
        'github_dark'   => ['kw'=>'#ff7b72','str'=>'#a5d6ff','num'=>'#79c0ff','cm'=>'#8b949e','fn'=>'#d2a8ff','var'=>'#ffa657','def'=>'#e6edf3','op'=>'#e6edf3','ln'=>'#30363d'],
        'dracula'       => ['kw'=>'#ff79c6','str'=>'#f1fa8c','num'=>'#bd93f9','cm'=>'#6272a4','fn'=>'#50fa7b','var'=>'#8be9fd','def'=>'#f8f8f2','op'=>'#ff79c6','ln'=>'#44475a'],
        'minimal_light' => ['kw'=>'#0550ae','str'=>'#0a3069','num'=>'#0550ae','cm'=>'#6e7781','fn'=>'#8250df','var'=>'#953800','def'=>'#24292f','op'=>'#24292f','ln'=>'#8c959f'],
    ];
    $c = $themes[$theme] ?? $themes['vscode_dark'];
    $kws = ['const','let','var','function','return','if','else','for','while','class',
            'import','export','default','new','this','async','await','try','catch',
            'throw','typeof','instanceof','null','undefined','true','false','void',
            'delete','in','of','from','do','switch','case','break','continue',
            'extends','super','static','get','set','yield','with'];
    $result = [];
    foreach ($lines as $i => $line) {
        $result[] = pt_code_tokenize_line($line, $i + 1, $c, $kws);
    }
    return $result;
}

function pt_code_tokenize_line(string $line, int $ln, array $c, array $kws): array
{
    $tokens = [['text' => str_pad((string)$ln, 3, ' ', STR_PAD_LEFT), 'color' => $c['ln'], 'ln' => true]];
    if (preg_match('#^\s*(//|/\*)#', $line)) {
        $tokens[] = ['text' => $line, 'color' => $c['cm'], 'ln' => false];
        return $tokens;
    }
    $remaining = $line;
    while ($remaining !== '') {
        if (preg_match('/^("(?:[^"\\\\]|\\\\.)*")/u', $remaining, $m)) {
            $tokens[] = ['text' => $m[1], 'color' => $c['str'], 'ln' => false];
            $remaining = substr($remaining, strlen($m[1]));
        } elseif (preg_match("/^('(?:[^'\\\\]|\\\\.)*')/u", $remaining, $m)) {
            $tokens[] = ['text' => $m[1], 'color' => $c['str'], 'ln' => false];
            $remaining = substr($remaining, strlen($m[1]));
        } elseif (preg_match('/^(`[^`]*`)/u', $remaining, $m)) {
            $tokens[] = ['text' => $m[1], 'color' => $c['str'], 'ln' => false];
            $remaining = substr($remaining, strlen($m[1]));
        } elseif (preg_match('/^(\d+\.?\d*)/u', $remaining, $m)) {
            $tokens[] = ['text' => $m[1], 'color' => $c['num'], 'ln' => false];
            $remaining = substr($remaining, strlen($m[1]));
        } elseif (preg_match('/^([a-zA-Z_$][a-zA-Z0-9_$]*)/u', $remaining, $m)) {
            $word = $m[1];
            $rest = substr($remaining, strlen($word));
            if (in_array($word, $kws, true)) {
                $tokens[] = ['text' => $word, 'color' => $c['kw'], 'ln' => false];
            } elseif ($rest !== '' && $rest[0] === '(') {
                $tokens[] = ['text' => $word, 'color' => $c['fn'], 'ln' => false];
            } else {
                $tokens[] = ['text' => $word, 'color' => $c['var'], 'ln' => false];
            }
            $remaining = substr($remaining, strlen($word));
        } elseif (str_starts_with($remaining, '//')) {
            $tokens[] = ['text' => $remaining, 'color' => $c['cm'], 'ln' => false];
            break;
        } else {
            $tokens[] = ['text' => $remaining[0], 'color' => $c['op'], 'ln' => false];
            $remaining = substr($remaining, 1);
        }
    }
    return $tokens;
}

function pt_code_lines_svg(array $lines, float $x_code, float $start_y, float $lh, string $font, float $fs, int $max_y): string
{
    $out  = ''; $y = $start_y; $x_ln = $x_code - 36;
    foreach ($lines as $line_tokens) {
        if ($y > $max_y) break;
        $x = $x_code;
        foreach ($line_tokens as $token) {
            $te  = PT_Text::e($token['text']);
            $col = $token['color'];
            if ($token['ln'] ?? false) {
                $out .= "<text x='$x_ln' y='$y' font-family=\"'$font',monospace\" font-size='$fs' fill='$col'>$te</text>";
            } else {
                $cw  = mb_strlen($token['text']) * $fs * 0.605;
                $out .= "<text x='$x' y='$y' font-family=\"'$font',monospace\" font-size='$fs' fill='$col'>$te</text>";
                $x  += $cw;
            }
        }
        $y += $lh;
    }
    return $out;
}


// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 9 — AWAN TOOLS  (1200 × 630)
// Branded plugin cards: logo, heading, badges, stats bar, footer URL.
// ═══════════════════════════════════════════════════════════════════════════════

function pt_render_awan_tools(array $p): string
{
    return match($p['template']) {
        'dark' => pt_awan_dark($p),
        'neon' => pt_awan_neon($p),
        default => pt_awan_light($p),
    };
}

// ── Shared helpers ────────────────────────────────────────────────────────────

/** Logo area: <image> if URL given, else a dashed placeholder rect + icon. */
function pt_awan_logo_svg(string $logo_url, float $x, float $y, float $sz, string $ac): string
{
    if ($logo_url) {
        $lh = htmlspecialchars($logo_url, ENT_XML1);
        return "<image href='$lh' x='$x' y='$y' width='$sz' height='$sz' preserveAspectRatio='xMidYMid meet'/>";
    }
    $ac_h = strpos($ac, '#') === 0 ? $ac : '#' . $ac;
    $cx   = $x + $sz * 0.5;
    $cy   = $y + $sz * 0.5;
    $is   = $sz * 0.48;
    $ic   = PT_Icons::icon_svg('image', $cx, $cy, $is, $ac);
    return "<rect x='$x' y='$y' width='$sz' height='$sz' rx='8' fill='none' stroke='$ac_h' stroke-width='1.5' stroke-dasharray='5 3' opacity='0.55'/>" . $ic;
}

/** Three feature badge pills in a horizontal row. */
function pt_awan_badges(array $p, float $x, float $y, string $pill_bg, string $lbl_c, string $sub_c, string $icon_c, string $font): string
{
    $items = [
        [$p['badge1'], $p['badge1_icon'], $p['badge1_sub']],
        [$p['badge2'], $p['badge2_icon'], $p['badge2_sub']],
        [$p['badge3'], $p['badge3_icon'], $p['badge3_sub']],
    ];
    $pw = 196; $ph = 52; $pr = 10; $gap = 14;
    $out = '';
    foreach ($items as $i => [$lbl, $icn, $sub]) {
        if (!$lbl) continue;
        $bx  = $x + $i * ($pw + $gap);
        $by  = $y;
        $icx = (int)($bx + $pr + 18);
        $icy = (int)($by + $ph * 0.5);
        $tx  = (int)($bx + $pr + 46);
        $ty1 = (int)($by + $ph * 0.5 - 7);
        $ty2 = (int)($by + $ph * 0.5 + 11);
        $le  = PT_Text::e(strtoupper($lbl));
        $se  = PT_Text::e($sub);
        $ic  = PT_Icons::icon_svg($icn, $icx, $icy, 22, $icon_c);
        $out .= "<rect x='$bx' y='$by' width='$pw' height='$ph' rx='$pr' fill='$pill_bg'/>";
        $out .= $ic;
        $out .= "<text x='$tx' y='$ty1' font-family=\"'$font',sans-serif\" font-size='12' font-weight='700' fill='$lbl_c' letter-spacing='0.5'>$le</text>";
        if ($sub) {
            $out .= "<text x='$tx' y='$ty2' font-family=\"'$font',sans-serif\" font-size='11' fill='$sub_c'>$se</text>";
        }
    }
    return $out;
}

/** Stats bar: rounded rect containing three stat items with icon + value + label. */
function pt_awan_stats(array $p, float $x, float $y, float $sw, float $sh, string $bar_bg, string $val_c, string $lbl_c, string $icon_c, string $font): string
{
    $items = [
        [$p['stat1_value'], $p['stat1_label'], $p['stat1_icon']],
        [$p['stat2_value'], $p['stat2_label'], $p['stat2_icon']],
        [$p['stat3_value'], $p['stat3_label'], $p['stat3_icon']],
    ];
    $pr  = 14;
    $out = "<rect x='$x' y='$y' width='$sw' height='$sh' rx='$pr' fill='$bar_bg'/>";
    $slw = $sw / 3;
    $d1x = (int)($x + $slw);
    $d2x = (int)($x + $slw * 2);
    $dy1 = (int)($y + 10);
    $dy2 = (int)($y + $sh - 10);
    $out .= "<line x1='$d1x' y1='$dy1' x2='$d1x' y2='$dy2' stroke='$icon_c' stroke-width='0.5' opacity='0.18'/>";
    $out .= "<line x1='$d2x' y1='$dy1' x2='$d2x' y2='$dy2' stroke='$icon_c' stroke-width='0.5' opacity='0.18'/>";
    foreach ($items as $i => [$val, $lbl, $icn]) {
        $slcx = (int)($x + $slw * $i + $slw * 0.5);
        $slcy = (int)($y + $sh * 0.5);
        $icx  = $slcx - 56;
        $icy  = $slcy;
        $tx   = $slcx - 24;
        $ty1  = $slcy - 8;
        $ty2  = $slcy + 13;
        $ic   = PT_Icons::icon_block($icn, $icx, $icy, 20, $icon_c, $icon_c, 18);
        $ve   = PT_Text::e($val);
        $le   = PT_Text::e($lbl);
        $out .= $ic;
        $out .= "<text x='$tx' y='$ty1' font-family=\"'$font',sans-serif\" font-size='22' font-weight='800' fill='$val_c'>$ve</text>";
        $out .= "<text x='$tx' y='$ty2' font-family=\"'$font',sans-serif\" font-size='12' fill='$lbl_c'>$le</text>";
    }
    return $out;
}

/** Footer URL row with globe icon. */
function pt_awan_footer(string $footer, int $total_w, int $fy, string $color, string $font): string
{
    $fe_e   = PT_Text::e($footer);
    $fe_px  = mb_strlen($footer) * 9;
    $grp_w  = 22 + $fe_px;
    $fe_lft = (int)(($total_w - $grp_w) / 2);
    $gl_cx  = $fe_lft + 8;
    $fe_tx  = $fe_lft + 20;
    $globe  = PT_Icons::icon_svg('globe', $gl_cx, $fy - 3, 14, $color);
    return $globe . "<text x='$fe_tx' y='$fy' font-family=\"'$font',sans-serif\" font-size='15' fill='$color'>$fe_e</text>";
}

// ── Template 1: Light ─────────────────────────────────────────────────────────

function pt_awan_light(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);

    // Icon box geometry (upper-right)
    $bx = 784; $by = 42; $bw = 366; $bh = 366; $br = 66;
    $icx = 967; $icy = 225;

    // Colors fixed for light theme
    $pale   = '#f3f0ff';
    $navy   = '#1a1040';
    $ac_h   = PT_Color::h($p['accent_color']);
    $ac_lt  = PT_Color::h(PT_Color::lighten($p['accent_color'], 0.2));
    $ac_dk  = PT_Color::h(PT_Color::darken($p['accent_color'], 0.1));
    $desc_c = '#4b5563';
    $muted  = '#6b7280';
    $white  = '#ffffff';

    // Logo row
    $logo_x  = $pad;
    $logo_y  = $pad - 10;
    $logo_sz = 44;
    $sep_x   = $pad + $logo_sz + 10;
    $sep_y1  = $logo_y + 8;
    $sep_y2  = $logo_y + $logo_sz - 8;
    $name_x  = $sep_x + 12;
    $name_y  = $logo_y + 18;
    $pn_e    = PT_Text::e($p['plugin_name']);
    $logo_s  = pt_awan_logo_svg($p['logo'], $logo_x, $logo_y, $logo_sz, $ac_h);

    // Heading (max 2 lines, large)
    $hl     = PT_Text::wrap($p['heading'], 660, 70, 0.52, 2);
    $hy     = 125;
    $hlh    = 83;
    $hs     = PT_Text::tspans($hl, $pad, $hlh);
    $h_bot  = $hy + count($hl) * $hlh;

    // Subheading pill
    $sub    = $p['subheading'];
    $sub_s  = '';
    $sub_bt = $h_bot;
    if ($sub) {
        $sub_y  = $h_bot + 10;
        $sub_bw = (int)(mb_strlen($sub) * 9.5 + 42);
        $sub_mx = $pad + (int)($sub_bw / 2);
        $sub_ty = $sub_y + 20;
        $sub_e  = PT_Text::e($sub);
        $sub_s  = "<rect x='$pad' y='$sub_y' width='$sub_bw' height='30' rx='5' fill='$ac_h'/>"
                . "<text x='$sub_mx' y='$sub_ty' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='12' font-weight='700' fill='$white' letter-spacing='1.5'>$sub_e</text>";
        $sub_bt = $sub_y + 42;
    }

    // Description
    $dl     = PT_Text::wrap($p['description'], 640, 22, 0.52, 3);
    $dy     = $sub_bt + 12;
    $dlh    = 31;
    $ds     = PT_Text::tspans($dl, $pad, $dlh);
    $d_bot  = $dy + count($dl) * $dlh;

    // Badge pills
    $bdg_y  = $d_bot + 20;
    $badges = pt_awan_badges($p, $pad, $bdg_y, $white, $navy, $muted, $ac_h, $font);

    // Stats bar
    $st_y   = $bdg_y + 66;
    $st_w   = 660;
    $stats  = pt_awan_stats($p, $pad, $st_y, $st_w, 50, $white, $navy, $muted, $ac_h, $font);

    // Separator + footer
    $line_y = $h - 36;
    $lx2    = $w - $pad;
    $footer = pt_awan_footer($p['footer'], $w, $h - 14, $muted, $font);

    // Decorative blobs + dots
    $bl1x = $w + 50; $bl1y = -70;
    $bl2x = -70;     $bl2y = (int)($h + 70);
    $bl3x = (int)($w * 0.52); $bl3y = (int)($h * 0.9);
    $dotx = $w - 226;
    $dots = PT_Renderer::dots(7, 5, $dotx, 28, 26, $p['accent_color'], 0.1, 2);

    // Icon box highlight cap
    $bhl = (int)($bh * 0.28);
    $icon_s = PT_Icons::icon_svg($p['icon'], $icx, $icy, 162, $white);

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <radialGradient id="al_bgr" cx="85%" cy="0%" r="65%">
    <stop offset="0%" stop-color="$ac_lt" stop-opacity="0.3"/>
    <stop offset="100%" stop-color="$pale" stop-opacity="0"/>
  </radialGradient>
  <linearGradient id="al_igr" x1="0.2" y1="0" x2="0" y2="1">
    <stop offset="0%" stop-color="$ac_lt"/>
    <stop offset="100%" stop-color="$ac_dk"/>
  </linearGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$pale"/>
  <rect width="$w" height="$h" fill="url(#al_bgr)"/>
  <circle cx="$bl1x" cy="$bl1y" r="290" fill="$ac_h" opacity="0.05"/>
  <circle cx="$bl2x" cy="$bl2y" r="200" fill="$ac_h" opacity="0.04"/>
  <circle cx="$bl3x" cy="$bl3y" r="130" fill="$ac_h" opacity="0.04"/>
  $dots
  <rect x="$bx" y="$by" width="$bw" height="$bh" rx="$br" fill="url(#al_igr)"/>
  <rect x="$bx" y="$by" width="$bw" height="$bhl" rx="$br" fill="$white" fill-opacity="0.1"/>
  $icon_s
  $logo_s
  <line x1="$sep_x" y1="$sep_y1" x2="$sep_x" y2="$sep_y2" stroke="$navy" stroke-width="1" opacity="0.2"/>
  <text x="$name_x" y="$name_y" font-family="'$font',sans-serif" font-size="13" font-weight="700" fill="$navy" opacity="0.7">$pn_e</text>
  <text x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="70" font-weight="800" fill="$navy" letter-spacing="-1.5">$hs</text>
  $sub_s
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="22" fill="$desc_c">$ds</text>
  $badges
  $stats
  <line x1="$pad" y1="$line_y" x2="$lx2" y2="$line_y" stroke="$navy" stroke-width="0.5" opacity="0.1"/>
  $footer
</g></svg>
SVG;
}

// ── Template 2: Dark Neon ─────────────────────────────────────────────────────

function pt_awan_dark(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);

    // Icon box geometry
    $bx = 784; $by = 42; $bw = 366; $bh = 366; $br = 66;
    $icx = 967; $icy = 225;

    // Colors (dark)
    $bg_d    = '#07070f';
    $white   = '#ffffff';
    $ac_h    = PT_Color::h($p['accent_color']);
    $ac_lt   = PT_Color::h(PT_Color::lighten($p['accent_color'], 0.18));
    $ac_dk   = PT_Color::h(PT_Color::darken($p['accent_color'], 0.14));
    $muted   = '#94a3b8';
    $pill_bg = 'rgba(255,255,255,0.06)';
    $bar_bg  = 'rgba(255,255,255,0.05)';

    // Logo row
    $logo_x  = $pad;
    $logo_y  = $pad - 10;
    $logo_sz = 44;
    $sep_x   = $pad + $logo_sz + 10;
    $sep_y1  = $logo_y + 8;
    $sep_y2  = $logo_y + $logo_sz - 8;
    $name_x  = $sep_x + 12;
    $name_y  = $logo_y + 18;
    $pn_e    = PT_Text::e($p['plugin_name']);
    $logo_s  = pt_awan_logo_svg($p['logo'], $logo_x, $logo_y, $logo_sz, $ac_h);

    // Heading line 1
    $hl    = PT_Text::wrap($p['heading'], 660, 70, 0.52, 2);
    $hy    = 125;
    $hlh   = 83;
    $hs    = PT_Text::tspans($hl, $pad, $hlh);
    $h_bot = $hy + count($hl) * $hlh;

    // Subheading as large accent-colored text line
    $sub    = $p['subheading'];
    $sub_s  = '';
    $sub_bt = $h_bot;
    if ($sub) {
        $sub_e  = PT_Text::e($sub);
        $sub_y2 = $h_bot + 46;
        $sub_s  = "<text x='$pad' y='$sub_y2' font-family=\"'$font',sans-serif\" font-size='52' font-weight='800' fill='$ac_h' letter-spacing='-1'>$sub_e</text>";
        $sub_bt = $sub_y2 + 10;
    }

    // Description
    $dl    = PT_Text::wrap($p['description'], 640, 22, 0.52, 3);
    $dy    = $sub_bt + 12;
    $dlh   = 31;
    $ds    = PT_Text::tspans($dl, $pad, $dlh);
    $d_bot = $dy + count($dl) * $dlh;

    // Badges
    $bdg_y  = $d_bot + 20;
    $badges = pt_awan_badges($p, $pad, $bdg_y, $pill_bg, $white, $muted, $ac_h, $font);

    // Stats
    $st_y  = $bdg_y + 66;
    $st_w  = 660;
    $stats = pt_awan_stats($p, $pad, $st_y, $st_w, 50, $bar_bg, $ac_h, $muted, $ac_h, $font);

    // Footer
    $line_y = $h - 36;
    $lx2    = $w - $pad;
    $footer = pt_awan_footer($p['footer'], $w, $h - 14, $muted, $font);

    // Grid + glow blobs
    $grid = pt_grid_lines($w, $h, 44, '#ffffff', 0.022);
    $gc1x = (int)($w * 0.78); $gc1y = -60;
    $gc2x = -60;               $gc2y = (int)($h * 1.1);
    $bhl  = (int)($bh * 0.28);
    $icon_s = PT_Icons::icon_svg($p['icon'], $icx, $icy, 162, $white);

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <radialGradient id="ad_g1" cx="78%" cy="5%" r="60%">
    <stop offset="0%" stop-color="$ac_h" stop-opacity="0.22"/>
    <stop offset="100%" stop-color="$bg_d" stop-opacity="0"/>
  </radialGradient>
  <radialGradient id="ad_g2" cx="0%" cy="95%" r="45%">
    <stop offset="0%" stop-color="$ac_lt" stop-opacity="0.12"/>
    <stop offset="100%" stop-color="$bg_d" stop-opacity="0"/>
  </radialGradient>
  <linearGradient id="ad_igr" x1="0.2" y1="0" x2="0" y2="1">
    <stop offset="0%" stop-color="$ac_lt"/>
    <stop offset="100%" stop-color="$ac_dk"/>
  </linearGradient>
  <filter id="ad_glow" x="-25%" y="-25%" width="150%" height="150%">
    <feGaussianBlur stdDeviation="10" result="b"/>
    <feMerge><feMergeNode in="b"/><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
  </filter>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg_d"/>
  $grid
  <rect width="$w" height="$h" fill="url(#ad_g1)"/>
  <rect width="$w" height="$h" fill="url(#ad_g2)"/>
  <rect x="$bx" y="$by" width="$bw" height="$bh" rx="$br" fill="url(#ad_igr)"/>
  <rect x="$bx" y="$by" width="$bw" height="$bhl" rx="$br" fill="$white" fill-opacity="0.08"/>
  $icon_s
  $logo_s
  <line x1="$sep_x" y1="$sep_y1" x2="$sep_x" y2="$sep_y2" stroke="$muted" stroke-width="1" opacity="0.3"/>
  <text x="$name_x" y="$name_y" font-family="'$font',sans-serif" font-size="13" font-weight="600" fill="$muted">$pn_e</text>
  <text filter="url(#ad_glow)" x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="70" font-weight="800" fill="$white" letter-spacing="-1.5">$hs</text>
  $sub_s
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="22" fill="$muted">$ds</text>
  $badges
  $stats
  <line x1="$pad" y1="$line_y" x2="$lx2" y2="$line_y" stroke="$muted" stroke-width="0.5" opacity="0.15"/>
  $footer
</g></svg>
SVG;
}

// ── Template 3: Cyber Purple ──────────────────────────────────────────────────

function pt_awan_neon(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_vars($p);

    // Icon box geometry
    $bx = 784; $by = 42; $bw = 366; $bh = 366; $br = 66;
    $icx = 967; $icy = 225;

    // Colors (deep purple cyber)
    $bg_d    = '#0d0820';
    $white   = '#ffffff';
    $ac_h    = PT_Color::h($p['accent_color']);
    $ac_lt   = PT_Color::h(PT_Color::lighten($p['accent_color'], 0.15));
    $ac_dk   = PT_Color::h(PT_Color::darken($p['accent_color'], 0.1));
    $mid_pur = '#c4b5fd';
    $muted   = '#a78bfa';
    $pill_bg = 'rgba(167,139,250,0.12)';
    $bar_bg  = 'rgba(167,139,250,0.1)';

    // Logo row
    $logo_x  = $pad;
    $logo_y  = $pad - 10;
    $logo_sz = 44;
    $sep_x   = $pad + $logo_sz + 10;
    $sep_y1  = $logo_y + 8;
    $sep_y2  = $logo_y + $logo_sz - 8;
    $name_x  = $sep_x + 12;
    $name_y  = $logo_y + 18;
    $pn_e    = PT_Text::e($p['plugin_name']);
    $logo_s  = pt_awan_logo_svg($p['logo'], $logo_x, $logo_y, $logo_sz, $ac_h);

    // Heading
    $hl    = PT_Text::wrap($p['heading'], 660, 70, 0.52, 2);
    $hy    = 125;
    $hlh   = 83;
    $hs    = PT_Text::tspans($hl, $pad, $hlh);
    $h_bot = $hy + count($hl) * $hlh;

    // Subheading pill
    $sub    = $p['subheading'];
    $sub_s  = '';
    $sub_bt = $h_bot;
    if ($sub) {
        $sub_y  = $h_bot + 10;
        $sub_bw = (int)(mb_strlen($sub) * 9.5 + 42);
        $sub_mx = $pad + (int)($sub_bw / 2);
        $sub_ty = $sub_y + 20;
        $sub_e  = PT_Text::e($sub);
        $sub_s  = "<rect x='$pad' y='$sub_y' width='$sub_bw' height='30' rx='5' fill='$ac_h' fill-opacity='0.85'/>"
                . "<text x='$sub_mx' y='$sub_ty' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='12' font-weight='700' fill='$bg_d' letter-spacing='1.5'>$sub_e</text>";
        $sub_bt = $sub_y + 42;
    }

    // Description
    $dl    = PT_Text::wrap($p['description'], 640, 22, 0.52, 3);
    $dy    = $sub_bt + 12;
    $dlh   = 31;
    $ds    = PT_Text::tspans($dl, $pad, $dlh);
    $d_bot = $dy + count($dl) * $dlh;

    // Badges
    $bdg_y  = $d_bot + 20;
    $badges = pt_awan_badges($p, $pad, $bdg_y, $pill_bg, $white, $muted, $ac_h, $font);

    // Stats
    $st_y  = $bdg_y + 66;
    $st_w  = 660;
    $stats = pt_awan_stats($p, $pad, $st_y, $st_w, 50, $bar_bg, $ac_h, $muted, $ac_h, $font);

    // Footer
    $line_y = $h - 36;
    $lx2    = $w - $pad;
    $footer = pt_awan_footer($p['footer'], $w, $h - 14, $muted, $font);

    // Decorative
    $gc1x = (int)($w * 0.72); $gc1y = -80;
    $gc2x = -80;               $gc2y = (int)($h + 80);
    $gc3x = (int)($w * 0.45); $gc3y = (int)($h * 0.85);

    // Sparkle dots
    $spark = PT_Renderer::dots(4, 3, $w - 200, 60, 60, $p['accent_color'], 0.3, 1.5);

    $bhl    = (int)($bh * 0.28);
    $icon_s = PT_Icons::icon_svg($p['icon'], $icx, $icy, 162, $white);

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <radialGradient id="an_g1" cx="70%" cy="0%" r="65%">
    <stop offset="0%" stop-color="$muted" stop-opacity="0.25"/>
    <stop offset="100%" stop-color="$bg_d" stop-opacity="0"/>
  </radialGradient>
  <radialGradient id="an_g2" cx="0%" cy="100%" r="50%">
    <stop offset="0%" stop-color="$ac_lt" stop-opacity="0.18"/>
    <stop offset="100%" stop-color="$bg_d" stop-opacity="0"/>
  </radialGradient>
  <radialGradient id="an_g3" cx="45%" cy="85%" r="40%">
    <stop offset="0%" stop-color="$ac_h" stop-opacity="0.1"/>
    <stop offset="100%" stop-color="$bg_d" stop-opacity="0"/>
  </radialGradient>
  <linearGradient id="an_igr" x1="0.2" y1="0" x2="0" y2="1">
    <stop offset="0%" stop-color="$ac_lt"/>
    <stop offset="100%" stop-color="$ac_dk"/>
  </linearGradient>
  <filter id="an_glow" x="-25%" y="-25%" width="150%" height="150%">
    <feGaussianBlur stdDeviation="8" result="b"/>
    <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
  </filter>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg_d"/>
  <rect width="$w" height="$h" fill="url(#an_g1)"/>
  <rect width="$w" height="$h" fill="url(#an_g2)"/>
  <rect width="$w" height="$h" fill="url(#an_g3)"/>
  $spark
  <rect x="$bx" y="$by" width="$bw" height="$bh" rx="$br" fill="url(#an_igr)"/>
  <rect x="$bx" y="$by" width="$bw" height="$bhl" rx="$br" fill="$white" fill-opacity="0.08"/>
  $icon_s
  $logo_s
  <line x1="$sep_x" y1="$sep_y1" x2="$sep_x" y2="$sep_y2" stroke="$muted" stroke-width="1" opacity="0.35"/>
  <text x="$name_x" y="$name_y" font-family="'$font',sans-serif" font-size="13" font-weight="600" fill="$mid_pur">$pn_e</text>
  <text filter="url(#an_glow)" x="$pad" y="$hy" font-family="'$font',sans-serif" font-size="70" font-weight="800" fill="$white" letter-spacing="-1.5">$hs</text>
  $sub_s
  <text x="$pad" y="$dy" font-family="'$font',sans-serif" font-size="22" fill="$mid_pur">$ds</text>
  $badges
  $stats
  <line x1="$pad" y1="$line_y" x2="$lx2" y2="$line_y" stroke="$muted" stroke-width="0.5" opacity="0.2"/>
  $footer
</g></svg>
SVG;
}
