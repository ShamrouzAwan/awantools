<?php
defined('AWAN') or die('Direct access denied.');

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
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_br_vars($p);
    $fa      = PT_Icons::fa_style_import();
    $tb_h    = 52;
    $tab_h   = 38;
    $total_chrome = $tb_h + $tab_h;
    $content_h = $h - $total_chrome;
    $tb_bg   = '#292929';
    $tab_bg  = '#3c3c3c';
    $url_bg  = '#404040';
    $border  = '#1a1a1a';
    $url_e   = PT_Text::e($p['url'] ?: 'https://awantools.site');
    $content = pt_br_content($p, $w, $content_h, $total_chrome, $font);
    $dots    = PT_Renderer::traffic_lights(18, $tab_h + $tb_h/2, 7, 20);
    $hl      = PT_Text::wrap($p['heading'], $w - 100, 28, 0.52, 1);
    $tab_title = PT_Text::e($hl[0] ?? $p['heading']);
    $tab_rect_h = $tab_h - 4;
    $url_x   = 130;
    $url_w   = $w - 280;
    $url_bar_y = $tab_h + 10;
    $url_text_x = (int)($w / 2);
    $url_text_y = $tab_h + 31;
    $bot_y   = $h - 1;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tab_h" fill="$tab_bg"/>
  <rect x="0" y="$tab_h" width="$w" height="1" fill="$border"/>
  <rect x="80" y="4" width="220" height="$tab_rect_h" rx="8" fill="$tb_bg"/>
  <text x="104" y="27" font-family="'$font',sans-serif" font-size="13" fill="#cccccc">$tab_title</text>
  <rect x="0" y="$tab_h" width="$w" height="$tb_h" fill="$tb_bg"/>
  $dots
  <rect x="$url_x" y="$url_bar_y" width="$url_w" height="32" rx="16" fill="$url_bg"/>
  <text x="$url_text_x" y="$url_text_y" text-anchor="middle" font-family="'$font',monospace" font-size="13" fill="#8b8b8b">$url_e</text>
  $content
  <rect x="0" y="$bot_y" width="$w" height="1" fill="$border"/>
</g></svg>
SVG;
}

function pt_browser_chrome_light(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_br_vars($p);
    $fa      = PT_Icons::fa_style_import();
    $tb_h    = 52;
    $tab_h   = 38;
    $total_chrome = $tb_h + $tab_h;
    $tb_bg   = '#f1f3f4';
    $tab_bg  = '#dee1e6';
    $url_bg  = '#ffffff';
    $border  = '#c6c6c6';
    $url_e   = PT_Text::e($p['url'] ?: 'https://awantools.site');
    $content = pt_br_content($p, $w, $h - $total_chrome, $total_chrome, $font);
    $dots    = PT_Renderer::traffic_lights(18, $tab_h + $tb_h/2, 7, 20);
    $hl      = PT_Text::wrap($p['heading'], $w - 100, 28, 0.52, 1);
    $tab_title = PT_Text::e($hl[0] ?? $p['heading']);
    $tab_rect_h = $tab_h - 4;
    $url_x      = 130;
    $url_w      = $w - 280;
    $url_bar_y  = $tab_h + 10;
    $url_text_x = (int)($w / 2);
    $url_text_y = $tab_h + 31;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tab_h" fill="$tab_bg"/>
  <rect x="0" y="$tab_h" width="$w" height="1" fill="$border"/>
  <rect x="80" y="4" width="220" height="$tab_rect_h" rx="8" fill="#f8f9fa"/>
  <text x="104" y="27" font-family="'$font',sans-serif" font-size="13" fill="#333333">$tab_title</text>
  <rect x="0" y="$tab_h" width="$w" height="$tb_h" fill="$tb_bg"/>
  $dots
  <rect x="$url_x" y="$url_bar_y" width="$url_w" height="32" rx="16" fill="$url_bg" stroke="$border" stroke-width="1"/>
  <text x="$url_text_x" y="$url_text_y" text-anchor="middle" font-family="'$font',monospace" font-size="13" fill="#606368">$url_e</text>
  $content
  <rect x="0" y="0" width="$w" height="$h" rx="$r" fill="none" stroke="$border" stroke-width="1"/>
</g></svg>
SVG;
}

function pt_browser_safari(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_br_vars($p);
    $fa     = PT_Icons::fa_style_import();
    $tb_h   = 60;
    $url_e  = PT_Text::e($p['url'] ?: 'https://awantools.site');
    $tb_bg  = '#ececec';
    $border = '#c0c0c0';
    $content = pt_br_content($p, $w, $h - $tb_h, $tb_h, $font);
    $dots    = PT_Renderer::traffic_lights(18, $tb_h/2, 7, 20);
    $url_half = (int)($w / 2);
    $url_rect_x = $url_half - 240;
    $url_rect_y = (int)($tb_h/2) - 16;
    $url_text_y = (int)($tb_h/2) + 6;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="$tb_bg"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="$border"/>
  $dots
  <rect x="$url_rect_x" y="$url_rect_y" width="480" height="32" rx="10" fill="#ffffff" stroke="$border" stroke-width="1"/>
  <text x="$url_half" y="$url_text_y" text-anchor="middle" font-family="'$font',sans-serif" font-size="14" fill="#333">$url_e</text>
  $content
  <rect x="0" y="0" width="$w" height="$h" rx="$r" fill="none" stroke="$border" stroke-width="1"/>
</g></svg>
SVG;
}

function pt_browser_minimal(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_br_vars($p);
    $fa      = PT_Icons::fa_style_import();
    $tb_h    = 44;
    $tb_bg   = PT_Color::h(PT_Color::lighten($p['bg_color'], 0.06));
    $border  = PT_Color::h(PT_Color::lighten($p['bg_color'], 0.12));
    $url_e   = PT_Text::e($p['url'] ?: 'https://awantools.site');
    $content = pt_br_content($p, $w, $h - $tb_h, $tb_h, $font);
    $dots    = PT_Renderer::traffic_lights(14, $tb_h/2, 6, 17);
    $cx      = (int)($w / 2);
    $tb_mid  = (int)($tb_h / 2) + 5;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
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
    $nav_sep_y = $offset + $nav_h;
    $nav_text_y = $offset + (int)($nav_h/2) + 6;
    $btn_dl_count = count($dl);
    $btn_y  = $dy + $btn_dl_count * $dlh + 16;
    $btn_x  = $cx - 50;
    $btn_ty = $dy + $btn_dl_count * $dlh + 37;
    $heading_e = PT_Text::e($p['heading']);
    $footer_e  = PT_Text::e($p['footer'] ?: 'Get Started');
    return "
    <rect x='0' y='$offset' width='$w' height='$content_h' fill='$bg2'/>
    <rect x='0' y='$offset' width='$w' height='$nav_h' fill='$nav_bg'/>
    <rect x='0' y='$nav_sep_y' width='$w' height='1' fill='$nav_bd'/>
    <text x='$cx' y='$nav_text_y' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='14' font-weight='600' fill='$hc'>$heading_e</text>
    $icon
    <text x='$cx' y='$hy' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='44' font-weight='800' fill='$hc'>$hs</text>
    <text x='$cx' y='$dy' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='20' fill='$dc'>$ds</text>
    <rect x='$btn_x' y='$btn_y' width='100' height='32' rx='8' fill='$ac'/>
    <text x='$cx' y='$btn_ty' text-anchor='middle' font-family=\"'$font',sans-serif\" font-size='13' font-weight='600' fill='white'>$footer_e</text>";
}

function pt_br_vars(array $p): array
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
