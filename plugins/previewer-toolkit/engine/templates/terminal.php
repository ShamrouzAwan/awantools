<?php
defined('AWAN') or die('Direct access denied.');

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
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_term_vars($p);
    $fa     = PT_Icons::fa_style_import();
    $tb_h   = 44;
    $tb_bg  = '#2e2e2e';
    $border = '#1a1a1a';
    $dots   = PT_Renderer::traffic_lights(16, $tb_h/2, 7, 22);
    $title  = PT_Text::e($p['heading'] ?: 'Terminal');
    $lines  = pt_term_build_lines($p, $w - 60, $font);
    $content = pt_term_lines_svg($lines, 40, $tb_h + 20, $dc, $ac, $hc, $font, $h);
    $cx     = (int)($w / 2);
    $tb_mid = (int)($tb_h / 2) + 5;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="$tb_bg"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="$border"/>
  $dots
  <text x="$cx" y="$tb_mid" text-anchor="middle" font-family="'$font',monospace" font-size="14" fill="#888888">$title</text>
  $content
</g></svg>
SVG;
}

function pt_terminal_linux(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_term_vars($p);
    $fa     = PT_Icons::fa_style_import();
    $tb_h   = 36;
    $tb_bg  = '#1a1a1a';
    $title  = PT_Text::e($p['heading'] ?: 'bash');
    $lines  = pt_term_build_lines($p, $w - 60, $font);
    $content = pt_term_lines_svg($lines, 40, $tb_h + 16, $dc, $ac, $hc, $font, $h);
    $cx     = (int)($w / 2);
    $tb_mid = (int)($tb_h / 2) + 5;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="$tb_bg"/>
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
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_term_vars($p);
    $fa     = PT_Icons::fa_style_import();
    $tb_h   = 50;
    $tb_bg  = PT_Color::h(PT_Color::darken($p['bg_color'], 0.15));
    $title  = PT_Text::e($p['heading'] ?: 'Terminal');
    $dots   = PT_Renderer::traffic_lights(18, $tb_h/2, 7, 22);
    $lines  = pt_term_build_lines($p, $w - 60, $font);
    $content = pt_term_lines_svg($lines, 40, $tb_h + 20, $dc, $ac, $hc, $font, $h);
    $bg2    = PT_Color::h(PT_Color::lighten($p['bg_color'], 0.03));
    $cx     = (int)($w / 2);
    $tb_mid = (int)($tb_h / 2) + 5;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs>
  <style>$fa</style>
  <clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath>
</defs>
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
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_term_vars($p);
    $fa     = PT_Icons::fa_style_import();
    $lines  = pt_term_build_lines($p, $w - 60, $font);
    $content = pt_term_lines_svg($lines, 40, 30, $dc, $ac, $hc, $font, $h);
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
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
    $all[] = ['type' => 'prompt',  'text' => $p['prompt']];
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
    $out    = '';
    $y      = $start_y;
    $lh     = 28;
    $fs     = 16;
    foreach ($lines as $line) {
        if ($y + $lh > $max_h - 20) break;
        $te  = PT_Text::e($line['text']);
        if ($line['type'] === 'prompt') {
            $out .= "<text x='$x' y='$y' font-family=\"'$font',monospace\" font-size='$fs' fill='$ac'>$te</text>";
        } elseif ($line['type'] === 'cursor') {
            $cur_y = (int)($y - 16);
            $out .= "<rect x='$x' y='$cur_y' width='10' height='20' fill='$hc' opacity='0.8'/>";
        } else {
            $out .= "<text x='$x' y='$y' font-family=\"'$font',monospace\" font-size='$fs' fill='$dc'>$te</text>";
        }
        $y += $lh;
    }
    if ($y < $max_h - 20) {
        $cur_y = (int)($y - 16);
        $out .= "<rect x='$x' y='$cur_y' width='10' height='20' fill='$hc' opacity='0.7'/>";
    }
    return $out;
}

function pt_term_vars(array $p): array
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
