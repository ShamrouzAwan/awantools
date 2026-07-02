<?php
defined('AWAN') or die('Direct access denied.');

function pt_render_og(array $p): string
{
    return match($p['template']) {
        'github_light'   => pt_og_github_light($p),
        'glassmorphism'  => pt_og_glassmorphism($p),
        'gradient'       => pt_og_gradient($p),
        'minimal'        => pt_og_minimal($p),
        'neon'           => pt_og_neon($p),
        default          => pt_og_github_dark($p),
    };
}

function pt_og_github_dark(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_og_vars($p);
    $fa  = PT_Icons::fa_style_import();
    $hl  = PT_Text::wrap($p['heading'], $w - $pad*2 - 40, 68, 0.52, 2);
    $dl  = PT_Text::wrap($p['description'], $w - $pad*2, 28, 0.52, 3);
    $icx = $pad + 36; $icy = $pad + 36;
    $hy  = $icy + 60;
    $hlh = 82;
    $dy  = $hy + count($hl)*$hlh + 18;
    $dlh = 38;
    $fy  = $h - $pad + 5;
    $dots = PT_Renderer::dots(8, 6, $w-260, 20, 28, $ac, 0.2, 2.2);
    $icon = PT_Icons::icon_block($p['icon'], $icx, $icy, 36, $p['accent_color'], $p['accent_color'], 22);
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $fe   = PT_Text::e($p['footer']);
    $badge = pt_og_badge($p, $pad, $dy + count($dl)*$dlh + 22, $ac, $font);
    $line_x2 = $w - $pad;
    $text_y  = $h - $pad + 22;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_og_vars($p);
    $fa   = PT_Icons::fa_style_import();
    $hl   = PT_Text::wrap($p['heading'], $w - $pad*2, 68, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], $w - $pad*2, 28, 0.52, 3);
    $icx  = $pad + 36; $icy = $pad + 36;
    $hy   = $icy + 60; $hlh = 82;
    $dy   = $hy + count($hl)*$hlh + 16; $dlh = 38;
    $dots = PT_Renderer::dots(8, 6, $w-260, 20, 28, $ac, 0.12, 2.2);
    $icon = PT_Icons::icon_block($p['icon'], $icx, $icy, 36, $p['accent_color'], $p['accent_color'], 22);
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $fe   = PT_Text::e($p['footer']);
    $badge = pt_og_badge($p, $pad, $dy + count($dl)*$dlh + 22, $ac, $font);
    $border = PT_Color::h(PT_Color::darken($p['bg_color'], 0.08));
    $line_y  = $h - $pad - 10;
    $line_x2 = $w - $pad;
    $text_y  = $h - $pad + 14;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_og_vars($p);
    $fa     = PT_Icons::fa_style_import();
    $bg2    = PT_Color::h(PT_Color::lighten($p['bg_color'], 0.12));
    $hl     = PT_Text::wrap($p['heading'], $w - $pad*4, 62, 0.52, 2);
    $dl     = PT_Text::wrap($p['description'], $w - $pad*4, 26, 0.52, 3);
    $icx    = $w/2; $icy = $pad + 52;
    $hlh    = 74; $dlh = 36;
    $text_y = $icy + 68;
    $hy     = $text_y;
    $dy     = $hy + count($hl)*$hlh + 16;
    $fy     = $h - $pad - 10;
    $cx     = $w/2;
    $icon   = PT_Icons::icon_block($p['icon'], $icx, $icy, 46, $p['accent_color'], $p['accent_color'], 28);
    $hs     = PT_Text::tspans_center($hl, $cx, $hlh);
    $ds     = PT_Text::tspans_center($dl, $cx, $dlh);
    $fe     = PT_Text::e($p['footer']);
    $badge  = pt_og_badge_center($p, $cx, $dy + count($dl)*$dlh + 24, $ac, $font);
    $pad2   = (int)($pad);
    $inner_x = $pad2;
    $inner_y = $pad2;
    $inner_w = $w - $pad*2;
    $inner_h = $h - $pad*2;
    $footer_y = $h - $pad + 12;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <style>$fa</style>
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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_og_vars($p);
    $fa   = PT_Icons::fa_style_import();
    $bg2  = PT_Color::h(PT_Color::darken($p['bg_color'], 0.22));
    $hl   = PT_Text::wrap($p['heading'], $w - $pad*2, 80, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], $w - $pad*2, 30, 0.52, 2);
    $cx   = $pad;
    $icx  = $w - $pad - 60; $icy = $pad + 60;
    $hlh  = 95; $dlh = 40;
    $hy   = (int)($h * 0.35);
    $dy   = $hy + count($hl)*$hlh + 10;
    $icon = PT_Icons::icon_block($p['icon'], $icx, $icy, 55, $p['heading_color'], $p['heading_color'], 34);
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $fe   = PT_Text::e($p['footer']);
    $bar_y   = $h - $pad - 30;
    $text_x  = $pad + 78;
    $text_y  = $h - $pad - 12;
    $circ1_r = (int)($h * 0.9);
    $circ2_r = (int)($h * 0.6);
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <style>$fa</style>
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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_og_vars($p);
    $fa     = PT_Icons::fa_style_import();
    $border = PT_Color::h(PT_Color::darken($p['bg_color'], 0.1));
    $hl     = PT_Text::wrap($p['heading'], $w - $pad*3, 64, 0.52, 2);
    $dl     = PT_Text::wrap($p['description'], $w - $pad*3, 26, 0.52, 3);
    $cx     = $w/2;
    $hlh    = 76; $dlh = 36;
    $icx    = $cx; $icy = $pad + 55;
    $hy     = $icy + 68;
    $dy     = $hy + count($hl)*$hlh + 16;
    $icon   = PT_Icons::icon_block($p['icon'], $icx, $icy, 44, $p['accent_color'], $p['accent_color'], 26);
    $hs     = PT_Text::tspans_center($hl, $cx, $hlh);
    $ds     = PT_Text::tspans_center($dl, $cx, $dlh);
    $fe     = PT_Text::e($p['footer']);
    $badge  = pt_og_badge_center($p, $cx, $dy + count($dl)*$dlh + 22, $ac, $font);
    $dash_x  = $cx - 20;
    $dash_y  = $h - $pad - 8;
    $text_y  = $h - $pad + 18;
    $inner_x = (int)($pad/2);
    $inner_y = (int)($pad/2);
    $inner_w = $w - $pad;
    $inner_h = $h - $pad;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_og_vars($p);
    $fa   = PT_Icons::fa_style_import();
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
    $grid = pt_og_grid_lines($w, $h, 40, '#'.$p['heading_color'], 0.04);
    $cx07w = (int)($w * 0.7); $cy02h = (int)($h * 0.2);
    $cx01w = (int)($w * 0.1); $cy08h = (int)($h * 0.8);
    $bar_y  = $h - $pad - 32;
    $text_x = $pad + 216;
    $text_y = $h - $pad - 14;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <style>$fa</style>
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

// ── Helpers ───────────────────────────────────────────────────────────────────

function pt_og_vars(array $p): array
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

function pt_og_badge(array $p, float $x, float $y, string $ac, string $font): string
{
    if (!$p['badge']) return '';
    $be  = PT_Text::e($p['badge']);
    $bw  = mb_strlen($p['badge']) * 9 + 24;
    $mid = $x + $bw/2;
    $ty  = $y + 17;
    return "<rect x='$x' y='$y' width='$bw' height='26' rx='13' fill='$ac' opacity='0.18'/>
    <text x='$mid' y='$ty' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='12' font-weight='700' fill='$ac'>$be</text>";
}

function pt_og_badge_center(array $p, float $cx, float $y, string $ac, string $font): string
{
    if (!$p['badge']) return '';
    $be  = PT_Text::e($p['badge']);
    $bw  = mb_strlen($p['badge']) * 9 + 28;
    $x   = $cx - $bw/2;
    $ty  = $y + 17;
    return "<rect x='$x' y='$y' width='$bw' height='26' rx='13' fill='$ac' opacity='0.18'/>
    <text x='$cx' y='$ty' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='12' font-weight='700' fill='$ac'>$be</text>";
}

function pt_og_grid_lines(int $w, int $h, int $step, string $color, float $opacity): string
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
