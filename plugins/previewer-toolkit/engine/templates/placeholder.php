<?php
defined('AWAN') or die('Direct access denied.');

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
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_ph_vars($p);
    $fa    = PT_Icons::fa_style_import();
    $label = $p['heading'] ?: "{$w} × {$h}";
    $sub   = $p['description'] ?: 'Placeholder';
    $cx    = (int)($w/2); $cy = (int)($h/2);
    $border = PT_Color::h(PT_Color::darken($p['bg_color'], 0.12));
    $fs    = min(56, max(18, (int)($w * 0.04)));
    $fs2   = max(12, (int)($fs * 0.5));
    $le    = PT_Text::e($label);
    $se    = PT_Text::e($sub);
    $text1_y = $cy - (int)($fs2/2) - 4;
    $text2_y = $cy + $fs2 + 6;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <line x1="0" y1="0" x2="$w" y2="$h" stroke="$border" stroke-width="1"/>
  <line x1="$w" y1="0" x2="0" y2="$h" stroke="$border" stroke-width="1"/>
  <rect x="0" y="0" width="$w" height="$h" fill="none" stroke="$border" stroke-width="1"/>
  <text x="$cx" y="$text1_y" text-anchor="middle" dominant-baseline="auto" font-family="'$font',sans-serif" font-size="$fs" font-weight="700" fill="$hc">$le</text>
  <text x="$cx" y="$text2_y" text-anchor="middle" dominant-baseline="auto" font-family="'$font',sans-serif" font-size="$fs2" fill="$dc">$se</text>
</g></svg>
SVG;
}

function pt_placeholder_grid(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_ph_vars($p);
    $fa    = PT_Icons::fa_style_import();
    $label = $p['heading'] ?: "{$w} × {$h}";
    $cx    = (int)($w/2); $cy = (int)($h/2);
    $step  = max(20, (int)min($w,$h)/24);
    $grid  = '';
    for ($x = 0; $x <= $w; $x += $step) {
        $grid .= "<line x1='$x' y1='0' x2='$x' y2='$h' stroke='$ac' stroke-width='0.5' opacity='0.3'/>";
    }
    for ($y = 0; $y <= $h; $y += $step) {
        $grid .= "<line x1='0' y1='$y' x2='$w' y2='$y' stroke='$ac' stroke-width='0.5' opacity='0.3'/>";
    }
    $fs     = min(42, max(16, (int)($w * 0.035)));
    $le     = PT_Text::e($label);
    $ic_cy  = $cy - $fs - 20;
    $ic     = PT_Icons::icon_block($p['icon'], $cx, $ic_cy, 34, $p['accent_color'], $p['accent_color'], 22);
    $text_y = $cy + 24;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  $grid
  $ic
  <text x="$cx" y="$text_y" text-anchor="middle" font-family="'$font',monospace" font-size="$fs" font-weight="700" fill="$hc">$le</text>
</g></svg>
SVG;
}

function pt_placeholder_glass(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_ph_vars($p);
    $fa    = PT_Icons::fa_style_import();
    $label = $p['heading'] ?: "{$w} × {$h}";
    $sub   = $p['description'] ?: 'Placeholder Image';
    $cx    = (int)($w/2); $cy = (int)($h/2);
    $bg2   = PT_Color::h(PT_Color::lighten($p['bg_color'], 0.15));
    $fs    = min(44, max(18, (int)($w * 0.04)));
    $fs2   = max(12, (int)($fs / 2));
    $le    = PT_Text::e($label);
    $se    = PT_Text::e($sub);
    $ic_cy = $cy - $fs - 24;
    $icon  = PT_Icons::icon_block($p['icon'], $cx, $ic_cy, 38, $p['accent_color'], $p['accent_color'], 26);
    $c1_cx = (int)($w * 0.8); $c1_cy = (int)($h * 0.2); $c1_r = (int)($w * 0.3);
    $c2_cx = (int)($w * 0.1); $c2_cy = (int)($h * 0.8); $c2_r = (int)($w * 0.25);
    $box_x = $cx - 180; $box_y = $cy - 100;
    $text1_y = $cy + 20;
    $text2_y = $cy + 50;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <radialGradient id="bg" cx="30%" cy="30%" r="70%"><stop offset="0%" stop-color="$bg2"/><stop offset="100%" stop-color="$bg"/></radialGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="url(#bg)"/>
  <circle cx="$c1_cx" cy="$c1_cy" r="$c1_r" fill="$ac" fill-opacity="0.1"/>
  <circle cx="$c2_cx" cy="$c2_cy" r="$c2_r" fill="$ac" fill-opacity="0.08"/>
  <rect x="$box_x" y="$box_y" width="360" height="200" rx="16" fill="$hc" fill-opacity="0.08" stroke="$hc" stroke-opacity="0.15" stroke-width="1"/>
  $icon
  <text x="$cx" y="$text1_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="$fs" font-weight="700" fill="$hc">$le</text>
  <text x="$cx" y="$text2_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="$fs2" fill="$dc">$se</text>
</g></svg>
SVG;
}

function pt_placeholder_gradient(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_ph_vars($p);
    $fa    = PT_Icons::fa_style_import();
    $label = $p['heading'] ?: "{$w} × {$h}";
    $cx    = (int)($w/2); $cy = (int)($h/2);
    $bg2   = PT_Color::h(PT_Color::darken($p['bg_color'], 0.25));
    $fs    = min(44, max(16, (int)($w * 0.04)));
    $le    = PT_Text::e($label);
    $ic_cy = $cy - $fs - 18;
    $icon  = PT_Icons::icon_block($p['icon'], $cx, $ic_cy, 36, $p['heading_color'], $p['heading_color'], 24);
    $text_y = $cy + 20;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="$bg"/><stop offset="100%" stop-color="$bg2"/></linearGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="url(#g)"/>
  $icon
  <text x="$cx" y="$text_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="$fs" font-weight="800" fill="$hc">$le</text>
</g></svg>
SVG;
}

function pt_placeholder_pattern(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_ph_vars($p);
    $fa    = PT_Icons::fa_style_import();
    $label = $p['heading'] ?: "{$w} × {$h}";
    $cx    = (int)($w/2); $cy = (int)($h/2);
    $dots  = PT_Renderer::dots(
        (int)($w/30)+1, (int)($h/30)+1, 0, 0, 30, $ac, 0.2, 2.5
    );
    $fs    = min(44, max(16, (int)($w * 0.04)));
    $le    = PT_Text::e($label);
    $ic_cy = $cy - $fs - 18;
    $icon  = PT_Icons::icon_block($p['icon'], $cx, $ic_cy, 36, $p['accent_color'], $p['accent_color'], 24);
    $box_x = $cx - 220; $box_y = $cy - 110;
    $text_y = $cy + 22;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  $dots
  <rect x="$box_x" y="$box_y" width="440" height="220" rx="16" fill="$bg" fill-opacity="0.7"/>
  $icon
  <text x="$cx" y="$text_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="$fs" font-weight="700" fill="$hc">$le</text>
</g></svg>
SVG;
}

function pt_ph_vars(array $p): array
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
