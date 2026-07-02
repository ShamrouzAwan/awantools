<?php
defined('AWAN') or die('Direct access denied.');

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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_social_vars($p);
    $fa   = PT_Icons::fa_style_import();
    $hl   = PT_Text::wrap($p['heading'], $w - $pad*2, 62, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], $w - $pad*2, 26, 0.52, 3);
    $hlh  = 74; $dlh = 36;
    $icx  = $pad + 38; $icy = $h/2;
    $hy   = (int)($h * 0.38);
    $dy   = $hy + count($hl)*$hlh + 14;
    $icon = PT_Icons::icon_block($p['icon'], $icx, $icy, 38, $p['accent_color'], $p['accent_color'], 24);
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $fe   = PT_Text::e($p['footer']);
    $ax   = $w - $pad - 38; $ay = $h/2;
    $ai   = PT_Icons::icon_block($p['icon'], $ax, $ay, 42, $p['accent_color'], $p['accent_color'], 28);
    $dots = PT_Renderer::dots(6, 8, $w-200, 20, 26, $ac, 0.18, 2);
    $fy   = $h - $pad + 8;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_social_vars($p);
    $fa   = PT_Icons::fa_style_import();
    $hl   = PT_Text::wrap($p['heading'], (int)($w * 0.6), 58, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], (int)($w * 0.6), 24, 0.52, 3);
    $hlh  = 70; $dlh = 32;
    $hy   = (int)($h * 0.32);
    $dy   = $hy + count($hl)*$hlh + 14;
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $fe   = PT_Text::e($p['footer']);
    $cx   = (int)($w * 0.8); $cy = $h/2;
    $icon = PT_Icons::icon_block($p['icon'], $cx, $cy, 70, $p['heading_color'], $p['heading_color'], 48);
    $bg2  = PT_Color::h(PT_Color::darken($p['bg_color'], 0.15));
    $bar_rect_y  = $h - $pad - 28;
    $bar_text_x  = $pad + 60;
    $bar_text_y  = $h - $pad - 10;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <style>$fa</style>
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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_social_vars($p);
    $fa   = PT_Icons::fa_style_import();
    $hl   = PT_Text::wrap($p['heading'], $w - $pad*2, 72, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], $w - $pad*2, 28, 0.52, 2);
    $hlh  = 86; $dlh = 36;
    $cx   = $w/2;
    $hy   = (int)($h * 0.32);
    $dy   = $hy + count($hl)*$hlh + 16;
    $icx  = $cx; $icy = $pad + 44;
    $icon = PT_Icons::icon_block($p['icon'], $icx, $icy, 44, $p['accent_color'], $p['accent_color'], 28);
    $hs   = PT_Text::tspans_center($hl, $cx, $hlh);
    $ds   = PT_Text::tspans_center($dl, $cx, $dlh);
    $fe   = PT_Text::e($p['footer']);
    $bg2  = PT_Color::h(PT_Color::lighten($p['bg_color'], 0.06));
    $hh   = $h / 2;
    $wr   = $w * 0.4;
    $fy   = $h - $pad + 8;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <style>$fa</style>
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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_social_vars($p);
    $fa   = PT_Icons::fa_style_import();
    $half = (int)($w * 0.52);
    $hl   = PT_Text::wrap($p['heading'], $half - $pad*2, 54, 0.52, 3);
    $dl   = PT_Text::wrap($p['description'], $half - $pad*2, 22, 0.52, 4);
    $hlh  = 66; $dlh = 30;
    $hy   = (int)($h * 0.3);
    $dy   = $hy + count($hl)*$hlh + 14;
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $fe   = PT_Text::e($p['footer']);
    $rx   = $half + $pad;
    $rcx  = $half + ($w - $half)/2;
    $rcy  = $h/2;
    $icon = PT_Icons::icon_block($p['icon'], $rcx, $rcy, 70, $p['accent_color'], $p['accent_color'], 44);
    $accent_bg = PT_Color::h($p['accent_color']);
    $bg2 = PT_Color::h(PT_Color::darken($p['bg_color'], 0.04));
    $border = PT_Color::h(PT_Color::darken($p['bg_color'], 0.1));
    $right_w = $w - $half;
    $fy  = $h - $pad + 6;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_social_vars($p);
    $fa   = PT_Icons::fa_style_import();
    $hl   = PT_Text::wrap($p['heading'], $w - $pad*2, 58, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], $w - $pad*2, 24, 0.52, 3);
    $hlh  = 70; $dlh = 32;
    $hy   = (int)($h * 0.36);
    $dy   = $hy + count($hl)*$hlh + 14;
    $icx  = $pad + 34; $icy = $pad + 34;
    $icon = PT_Icons::icon_block($p['icon'], $icx, $icy, 34, $p['accent_color'], $p['accent_color'], 22);
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $fe   = PT_Text::e($p['footer']);
    $border  = PT_Color::h(PT_Color::darken($p['bg_color'], 0.08));
    $bot_sep = $h - 70;
    $fy      = $h - $pad + 2;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
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

function pt_social_vars(array $p): array
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
