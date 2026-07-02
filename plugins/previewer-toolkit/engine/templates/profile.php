<?php
defined('AWAN') or die('Direct access denied.');

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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_pf_vars($p);
    $fa      = PT_Icons::fa_style_import();
    $cx      = $w/2;
    $av_r    = min(60, (int)($h * 0.15));
    $av_cy   = $pad + $av_r;
    $name_y  = $av_cy + $av_r + 34;
    $role_e  = PT_Text::e($p['subheading'] ?: '');
    $name_e  = PT_Text::e($p['heading']);
    $dl      = PT_Text::wrap($p['description'], $w - $pad*2, 16, 0.52, 3);
    $dy      = $name_y + 52;
    $ds      = PT_Text::tspans_center($dl, $cx, 24);
    $fe      = PT_Text::e($p['footer']);
    $border  = PT_Color::h(PT_Color::darken($p['bg_color'], 0.1));
    $tags    = pt_pf_tags($p, $cx, $dy + count($dl)*24 + 16, $ac, $font);
    $icon_r  = (int)($av_r * 0.62);
    $icon_svg = PT_Icons::icon_block($p['icon'], $cx, $av_cy, $av_r, $p['accent_color'], $p['accent_color'], $icon_r);
    $role_y  = $name_y + 26;
    $footer_y = $h - $pad + 10;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg" stroke="$border" stroke-width="1"/>
  <circle cx="$cx" cy="$av_cy" r="$av_r" fill="none" stroke="$ac" stroke-width="2.5"/>
  $icon_svg
  <text x="$cx" y="$name_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="32" font-weight="700" fill="$hc">$name_e</text>
  <text x="$cx" y="$role_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="15" fill="$ac" font-weight="500">$role_e</text>
  <text x="$cx" y="$dy" text-anchor="middle" font-family="'$font',sans-serif" font-size="15" fill="$dc">$ds</text>
  $tags
  <text x="$cx" y="$footer_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="13" fill="$dc">$fe</text>
</g></svg>
SVG;
}

function pt_profile_modern(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_pf_vars($p);
    $fa       = PT_Icons::fa_style_import();
    $bg2      = PT_Color::h(PT_Color::darken($p['bg_color'], 0.2));
    $cx       = $w/2;
    $banner_h = (int)($h * 0.38);
    $av_r     = min(52, (int)($h * 0.13));
    $av_cy    = $banner_h;
    $name_y   = $banner_h + $av_r + 32;
    $name_e   = PT_Text::e($p['heading']);
    $role_e   = PT_Text::e($p['subheading'] ?: '');
    $dl       = PT_Text::wrap($p['description'], $w - $pad*2, 15, 0.52, 2);
    $ds       = PT_Text::tspans_center($dl, $cx, 22);
    $fe       = PT_Text::e($p['footer']);
    $icon_svg = PT_Icons::icon_block($p['icon'], $cx, $av_cy, $av_r, $p['bg_color'], $p['accent_color'], (int)($av_r * 0.6));
    $av_r5    = $av_r + 5;
    $role_y   = $name_y + 26;
    $desc_y   = $name_y + 56;
    $footer_y = $h - $pad + 10;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <style>$fa</style>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <linearGradient id="banner" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="$bg"/><stop offset="100%" stop-color="$bg2"/></linearGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="#ffffff"/>
  <rect x="0" y="0" width="$w" height="$banner_h" fill="url(#banner)"/>
  <circle cx="$cx" cy="$av_cy" r="$av_r5" fill="white"/>
  <circle cx="$cx" cy="$av_cy" r="$av_r" fill="$bg"/>
  $icon_svg
  <text x="$cx" y="$name_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="28" font-weight="700" fill="#111827">$name_e</text>
  <text x="$cx" y="$role_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="$bg" font-weight="600">$role_e</text>
  <text x="$cx" y="$desc_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="#6b7280">$ds</text>
  <text x="$cx" y="$footer_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="12" fill="#9ca3af">$fe</text>
</g></svg>
SVG;
}

function pt_profile_dark(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_pf_vars($p);
    $fa      = PT_Icons::fa_style_import();
    $dots    = PT_Renderer::dots(8, 6, $w - 240, 20, 28, $ac, 0.18, 2);
    $cx      = $w * 0.32;
    $av_r    = min(70, (int)($h * 0.18));
    $av_cy   = $h/2;
    $name_x  = (int)($w * 0.5) + $pad;
    $name_e  = PT_Text::e($p['heading']);
    $role_e  = PT_Text::e($p['subheading'] ?: '');
    $dl      = PT_Text::wrap($p['description'], $w - $name_x - $pad, 16, 0.52, 3);
    $ds      = PT_Text::tspans($dl, $name_x, 22);
    $fe      = PT_Text::e($p['footer']);
    $line_x  = (int)($w * 0.5);
    $icon_svg = PT_Icons::icon_block($p['icon'], $cx, $av_cy, $av_r, $p['accent_color'], $p['accent_color'], (int)($av_r * 0.55));
    $line_y2 = $h - $pad;
    $name_y  = (int)($h * 0.35);
    $role_y  = $name_y + 32;
    $desc_y  = $name_y + 72;
    $footer_y = $h - $pad + 10;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  $dots
  <line x1="$line_x" y1="$pad" x2="$line_x" y2="$line_y2" stroke="$dc" stroke-width="0.5" opacity="0.2"/>
  $icon_svg
  <text x="$name_x" y="$name_y" font-family="'$font',sans-serif" font-size="34" font-weight="800" fill="$hc">$name_e</text>
  <text x="$name_x" y="$role_y" font-family="'$font',sans-serif" font-size="15" fill="$ac" font-weight="500">$role_e</text>
  <text x="$name_x" y="$desc_y" font-family="'$font',sans-serif" font-size="15" fill="$dc">$ds</text>
  <text x="$name_x" y="$footer_y" font-family="'$font',sans-serif" font-size="13" fill="$dc">$fe</text>
</g></svg>
SVG;
}

function pt_profile_glass(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_pf_vars($p);
    $fa      = PT_Icons::fa_style_import();
    $bg2     = PT_Color::h(PT_Color::lighten($p['bg_color'], 0.15));
    $cx      = $w/2;
    $av_r    = min(56, (int)($h * 0.15));
    $av_cy   = $pad + $av_r;
    $name_y  = $av_cy + $av_r + 32;
    $name_e  = PT_Text::e($p['heading']);
    $role_e  = PT_Text::e($p['subheading'] ?: '');
    $dl      = PT_Text::wrap($p['description'], $w - $pad*3, 15, 0.52, 2);
    $ds      = PT_Text::tspans_center($dl, $cx, 22);
    $fe      = PT_Text::e($p['footer']);
    $cp1w    = (int)($w - $pad*3); $cp1h = (int)($h - $pad*3);
    $cp1x    = (int)($pad*1.5);   $cp1y = (int)($pad*1.5);
    $icon_svg = PT_Icons::icon_block($p['icon'], $cx, $av_cy, $av_r, $p['accent_color'], $p['accent_color'], (int)($av_r * 0.58));
    $circ02w = (int)($w * 0.2);
    $circ02h = (int)($h * 0.2);
    $role_y  = $name_y + 26;
    $desc_y  = $name_y + 54;
    $footer_y = $h - $pad + 10;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <style>$fa</style>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <radialGradient id="bg" cx="30%" cy="30%" r="70%"><stop offset="0%" stop-color="$bg2"/><stop offset="100%" stop-color="$bg"/></radialGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="url(#bg)"/>
  <circle cx="$circ02w" cy="$circ02h" r="$h" fill="$ac" fill-opacity="0.08"/>
  <rect x="$cp1x" y="$cp1y" width="$cp1w" height="$cp1h" rx="16" fill="white" fill-opacity="0.07" stroke="white" stroke-opacity="0.12" stroke-width="1"/>
  <circle cx="$cx" cy="$av_cy" r="$av_r" fill="$ac" opacity="0.2"/>
  <circle cx="$cx" cy="$av_cy" r="$av_r" fill="none" stroke="$ac" stroke-width="2" opacity="0.6"/>
  $icon_svg
  <text x="$cx" y="$name_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="30" font-weight="700" fill="$hc">$name_e</text>
  <text x="$cx" y="$role_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="$ac">$role_e</text>
  <text x="$cx" y="$desc_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="$dc">$ds</text>
  <text x="$cx" y="$footer_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="12" fill="$dc" opacity="0.7">$fe</text>
</g></svg>
SVG;
}

function pt_profile_corporate(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_pf_vars($p);
    $fa      = PT_Icons::fa_style_import();
    $side_w  = (int)($w * 0.3);
    $av_cx   = (int)($side_w / 2);
    $av_cy   = (int)($h / 2);
    $av_r    = min(60, (int)($h * 0.16));
    $name_x  = $side_w + $pad;
    $name_y  = (int)($h * 0.3);
    $name_e  = PT_Text::e($p['heading']);
    $role_e  = PT_Text::e($p['subheading'] ?: '');
    $dl      = PT_Text::wrap($p['description'], $w - $side_w - $pad*2, 16, 0.52, 3);
    $ds      = PT_Text::tspans($dl, $name_x, 24);
    $fe      = PT_Text::e($p['footer']);
    $border  = PT_Color::h(PT_Color::darken($p['bg_color'], 0.1));
    $icon_svg = PT_Icons::icon_block($p['icon'], $av_cx, $av_cy, $av_r, $p['accent_color'], $p['accent_color'], (int)($av_r * 0.6));
    $role_y  = $name_y + 28;
    $line_y  = $name_y + 44;
    $line_x2 = $name_x + 60;
    $desc_y  = $name_y + 72;
    $footer_y = $h - $pad + 10;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$side_w" height="$h" fill="$ac" opacity="0.08"/>
  <line x1="$side_w" y1="0" x2="$side_w" y2="$h" stroke="$border" stroke-width="1"/>
  <rect x="0" y="0" width="$w" height="4" fill="$ac"/>
  $icon_svg
  <text x="$name_x" y="$name_y" font-family="'$font',sans-serif" font-size="32" font-weight="700" fill="$hc">$name_e</text>
  <text x="$name_x" y="$role_y" font-family="'$font',sans-serif" font-size="15" fill="$ac" font-weight="500">$role_e</text>
  <line x1="$name_x" y1="$line_y" x2="$line_x2" y2="$line_y" stroke="$ac" stroke-width="2"/>
  <text x="$name_x" y="$desc_y" font-family="'$font',sans-serif" font-size="15" fill="$dc">$ds</text>
  <text x="$name_x" y="$footer_y" font-family="'$font',sans-serif" font-size="13" fill="$dc">$fe</text>
</g></svg>
SVG;
}

function pt_pf_vars(array $p): array
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
    $sx = $cx - $total_w/2;
    foreach ($tag_data as $td) {
        $te = PT_Text::e($td['text']);
        $out .= "<rect x='$sx' y='$y' width='{$td['w']}' height='$tag_h' rx='12' fill='$ac' opacity='0.15'/>";
        $out .= "<text x='" . ($sx + $td['w']/2) . "' y='" . ($y + 16) . "' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='11' font-weight='600' fill='$ac'>$te</text>";
        $sx += $td['w'] + $gap;
    }
    return $out;
}
