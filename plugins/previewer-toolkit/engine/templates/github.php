<?php
defined('AWAN') or die('Direct access denied.');

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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_gh_vars($p);
    $fa  = PT_Icons::fa_style_import();
    $hl  = PT_Text::wrap($p['heading'], $w - $pad*2, 52, 0.52, 2);
    $dl  = PT_Text::wrap($p['description'], $w - $pad*2, 22, 0.52, 3);
    $hlh = 62; $dlh = 30;
    $hy  = $pad + 90;
    $dy  = $hy + count($hl)*$hlh + 12;
    $hs  = PT_Text::tspans($hl, $pad, $hlh);
    $ds  = PT_Text::tspans($dl, $pad, $dlh);
    $icx = $pad + 32; $icy = $pad + 32;
    $icon = PT_Icons::icon_block($p['icon'], $icx, $icy, 32, $p['accent_color'], $p['accent_color'], 20);
    $stats = pt_gh_stats_row($p, $pad, $h - $pad - 30, $hc, $dc, $ac, $font);
    $border = '#30363d';
    $fe  = PT_Text::e($p['footer'] ?: $p['username']);
    $tag = PT_Text::e($p['tag']);
    $w1  = $w - 1;
    $h1  = $h - 1;
    $head_x = $pad + 70;
    $head_y = $pad + 24;
    $tag_x  = $pad + 70 + mb_strlen($p['footer'] ?: $p['username'])*11 + 20;
    $line_y = $h - $pad - 70;
    $line_x2 = $w - $pad;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_gh_vars($p);
    $fa   = PT_Icons::fa_style_import();
    $hl   = PT_Text::wrap($p['heading'], $w - $pad*2, 52, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], $w - $pad*2, 22, 0.52, 3);
    $hlh  = 62; $dlh = 30;
    $hy   = $pad + 90;
    $dy   = $hy + count($hl)*$hlh + 12;
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $icx  = $pad + 32; $icy = $pad + 32;
    $icon = PT_Icons::icon_block($p['icon'], $icx, $icy, 32, $p['accent_color'], $p['accent_color'], 20);
    $stats = pt_gh_stats_row($p, $pad, $h - $pad - 30, $hc, $dc, $ac, $font);
    $border = '#d0d7de';
    $fe   = PT_Text::e($p['footer'] ?: $p['username']);
    $tag  = PT_Text::e($p['tag']);
    $head_x = $pad + 70;
    $head_y = $pad + 24;
    $tag_x  = $pad + 70 + mb_strlen($p['footer'] ?: $p['username'])*11 + 20;
    $line_y = $h - $pad - 70;
    $line_x2 = $w - $pad;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_gh_vars($p);
    $fa    = PT_Icons::fa_style_import();
    $cx    = (int)($w/2);
    $title = PT_Text::e($p['heading']);
    $sub   = PT_Text::e($p['subheading'] ?: ($p['footer'] ?: $p['username']));
    $border = '#21262d';
    $sl1_l = PT_Text::e($p['stat1_label']); $sl1_v = PT_Text::e($p['stat1_value']);
    $sl2_l = PT_Text::e($p['stat2_label']); $sl2_v = PT_Text::e($p['stat2_value']);
    $sl3_l = PT_Text::e($p['stat3_label']); $sl3_v = PT_Text::e($p['stat3_value']);
    $col_w = (int)(($w - $pad*2) / 3);
    $stat_y = (int)($h * 0.6);
    $lang_c = PT_Color::h($p['lang_color']);
    $lang   = PT_Text::e($p['lang']);
    $icx = $cx; $icy = $pad + 60;
    $icon = PT_Icons::icon_block($p['icon'], $icx, $icy, 50, $p['accent_color'], $p['accent_color'], 32);
    $title_y = $pad + 140;
    $sub_y   = $pad + 168;
    $sep_y   = $stat_y - 20;
    $sep_x2  = $w - $pad;
    $col1_cx = $pad + (int)($col_w/2);
    $col1_lbl_y = $stat_y + 26;
    $col2_x  = $pad + $col_w + (int)($col_w/2);
    $col3_x  = $pad + $col_w*2 + (int)($col_w/2);
    $sep1_x  = $pad + $col_w;
    $sep1_x2 = $sep1_x;
    $sep_bot = $stat_y + 46;
    $sep2_x  = $pad + $col_w*2;
    $lang_cx = $pad;
    $lang_cy = $h - $pad - 20;
    $lang_tx = $pad + 14;
    $lang_ty = $h - $pad - 13;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg" stroke="$border" stroke-width="1"/>
  $icon
  <text x="$cx" y="$title_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="42" font-weight="800" fill="$hc">$title</text>
  <text x="$cx" y="$sub_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="18" fill="$dc">$sub</text>
  <line x1="$pad" y1="$sep_y" x2="$sep_x2" y2="$sep_y" stroke="$border" stroke-width="1"/>
  <text x="$col1_cx" y="$stat_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="36" font-weight="800" fill="$ac">$sl1_v</text>
  <text x="$col1_cx" y="$col1_lbl_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="$dc">$sl1_l</text>
  <line x1="$sep1_x" y1="$sep_y" x2="$sep1_x2" y2="$sep_bot" stroke="$border" stroke-width="1"/>
  <text x="$col2_x" y="$stat_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="36" font-weight="800" fill="$ac">$sl2_v</text>
  <text x="$col2_x" y="$col1_lbl_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="$dc">$sl2_l</text>
  <line x1="$sep2_x" y1="$sep_y" x2="$sep2_x" y2="$sep_bot" stroke="$border" stroke-width="1"/>
  <text x="$col3_x" y="$stat_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="36" font-weight="800" fill="$ac">$sl3_v</text>
  <text x="$col3_x" y="$col1_lbl_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="$dc">$sl3_l</text>
  <circle cx="$lang_cx" cy="$lang_cy" r="7" fill="$lang_c"/>
  <text x="$lang_tx" y="$lang_ty" font-family="'$font',sans-serif" font-size="14" fill="$dc">$lang</text>
</g></svg>
SVG;
}

function pt_github_compact(array $p): string
{
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_gh_vars($p);
    $fa  = PT_Icons::fa_style_import();
    $hl  = PT_Text::wrap($p['heading'], (int)($w * 0.65), 42, 0.52, 2);
    $dl  = PT_Text::wrap($p['description'], $w - $pad*2, 18, 0.52, 2);
    $hlh = 52; $dlh = 26;
    $hy  = (int)($h * 0.38);
    $dy  = $hy + count($hl)*$hlh + 10;
    $hs  = PT_Text::tspans($hl, $pad, $hlh);
    $ds  = PT_Text::tspans($dl, $pad, $dlh);
    $icx = $w - $pad - 50; $icy = $h/2;
    $icon = PT_Icons::icon_block($p['icon'], $icx, $icy, 50, $p['accent_color'], $p['accent_color'], 32);
    $stats = pt_gh_stats_row($p, $pad, $h - $pad - 22, $hc, $dc, $ac, $font);
    $border = '#30363d';
    $line_y  = $h - $pad - 54;
    $line_x2 = $w - $pad;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
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
    [$w,$h,$pad,$bg,$hc,$dc,$ac,$r,$font] = pt_gh_vars($p);
    $fa   = PT_Icons::fa_style_import();
    $bg2  = PT_Color::h(PT_Color::darken($p['bg_color'], 0.2));
    $hl   = PT_Text::wrap($p['heading'], $w - $pad*2, 58, 0.52, 2);
    $dl   = PT_Text::wrap($p['description'], $w - $pad*2, 24, 0.52, 3);
    $hlh  = 70; $dlh = 32;
    $hy   = (int)($h * 0.34);
    $dy   = $hy + count($hl)*$hlh + 14;
    $icx  = $pad + 36; $icy = $pad + 36;
    $icon = PT_Icons::icon_block($p['icon'], $icx, $icy, 36, $p['heading_color'], $p['heading_color'], 24);
    $hs   = PT_Text::tspans($hl, $pad, $hlh);
    $ds   = PT_Text::tspans($dl, $pad, $dlh);
    $stats = pt_gh_stats_row($p, $pad, $h - $pad - 30, $hc, $dc, '#'.$p['heading_color'], $font);
    $circ_cx = (int)($w * 0.85);
    $circ_cy = (int)($h * 0.2);
    $circ_r  = (int)($h * 0.5);
    $line_y  = $h - $pad - 65;
    $line_x2 = $w - $pad;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <style>$fa</style>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
  <linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="$bg"/><stop offset="100%" stop-color="$bg2"/></linearGradient>
</defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="url(#g)"/>
  <circle cx="$circ_cx" cy="$circ_cy" r="$circ_r" fill="$hc" fill-opacity="0.05"/>
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
    $lc  = PT_Color::h($p['lang_color']);
    $lang = PT_Text::e($p['lang']);
    $off  = 160;
    $x2   = $x + $off;
    $x3   = $x + $off + 100;
    $x4   = $x + $off + 200;
    return "
    <circle cx='$x' cy='$y' r='6' fill='$lc'/>
    <text x='$x' y='$y' dominant-baseline='auto' dy='5' dx='14' font-family=\"'$font',sans-serif\" font-size='13' fill='$dc'>$lang</text>
    <text x='$x2' y='$y' dominant-baseline='auto' dy='5' font-family=\"'$font',sans-serif\" font-size='13' fill='$dc'>★ $s1v</text>
    <text x='$x3' y='$y' dominant-baseline='auto' dy='5' font-family=\"'$font',sans-serif\" font-size='13' fill='$dc'>⑂ $s2v</text>
    <text x='$x4' y='$y' dominant-baseline='auto' dy='5' font-family=\"'$font',sans-serif\" font-size='13' fill='$dc'>⊙ $s3v</text>";
}

function pt_gh_vars(array $p): array
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
