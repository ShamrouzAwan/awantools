<?php
defined('AWAN') or die('Direct access denied.');

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
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_code_vars($p);
    $fa      = PT_Icons::fa_style_import();
    $tb_h    = 44;
    $tb_bg   = '#252526';
    $border  = '#1e1e1e';
    $dots    = PT_Renderer::traffic_lights(16, $tb_h/2, 7, 22);
    $lang_e  = PT_Text::e(strtolower($p['language'] ?: 'js'));
    $title   = PT_Text::e($p['heading'] ?: 'snippet.js');
    $lines   = pt_code_tokenize($p, 'vscode_dark');
    $svg_lines = pt_code_lines_svg($lines, 40, $tb_h + 18, 26, $font, 15, $h - 20);
    $cx      = (int)($w / 2);
    $tb_mid  = (int)($tb_h / 2) + 5;
    $gutter_h = $h - $tb_h;
    $badge_x  = $w - 70;
    $badge_y  = $h - 30;
    $badge_tx = $w - 40;
    $badge_ty = $h - 15;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="$tb_bg"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="$border"/>
  $dots
  <text x="$cx" y="$tb_mid" text-anchor="middle" font-family="'$font',monospace" font-size="13" fill="#888888">$title</text>
  <rect x="0" y="$tb_h" width="30" height="$gutter_h" fill="$tb_bg" opacity="0.5"/>
  $svg_lines
  <rect x="$badge_x" y="$badge_y" width="60" height="20" rx="4" fill="$ac" opacity="0.2"/>
  <text x="$badge_tx" y="$badge_ty" text-anchor="middle" font-family="'$font',monospace" font-size="11" fill="$ac">$lang_e</text>
</g></svg>
SVG;
}

function pt_code_github_dark(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_code_vars($p);
    $fa      = PT_Icons::fa_style_import();
    $tb_h    = 48;
    $tb_bg   = '#161b22';
    $border  = '#30363d';
    $title   = PT_Text::e($p['heading'] ?: 'snippet.js');
    $lang_e  = PT_Text::e(strtolower($p['language'] ?: 'javascript'));
    $dots    = PT_Renderer::traffic_lights(16, $tb_h/2, 7, 22);
    $lines   = pt_code_tokenize($p, 'github_dark');
    $svg_lines = pt_code_lines_svg($lines, 50, $tb_h + 18, 26, $font, 15, $h - 20);
    $badge_x  = $w - 100;
    $badge_y  = (int)($tb_h/2) - 12;
    $badge_tx = $w - 60;
    $badge_ty = (int)($tb_h/2) + 5;
    $gutter_h = $h - $tb_h;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg" stroke="$border" stroke-width="1"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="$tb_bg"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="$border"/>
  $dots
  <rect x="$badge_x" y="$badge_y" width="80" height="24" rx="12" fill="#30363d"/>
  <text x="$badge_tx" y="$badge_ty" text-anchor="middle" font-family="'$font',monospace" font-size="12" fill="#8b949e">$lang_e</text>
  <text x="18" y="$badge_ty" font-family="'$font',monospace" font-size="13" fill="#8b949e">$title</text>
  <rect x="0" y="$tb_h" width="40" height="$gutter_h" fill="#0d1117" opacity="0.5"/>
  $svg_lines
</g></svg>
SVG;
}

function pt_code_dracula(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_code_vars($p);
    $fa      = PT_Icons::fa_style_import();
    $tb_h    = 44;
    $tb_bg   = '#21222c';
    $border  = '#1e1f29';
    $title   = PT_Text::e($p['heading'] ?: 'snippet.js');
    $lang_e  = PT_Text::e($p['language'] ?: 'javascript');
    $dots    = PT_Renderer::traffic_lights(16, $tb_h/2, 7, 22);
    $lines   = pt_code_tokenize($p, 'dracula');
    $svg_lines = pt_code_lines_svg($lines, 40, $tb_h + 18, 26, $font, 15, $h - 20);
    $cx      = (int)($w / 2);
    $tb_mid  = (int)($tb_h / 2) + 5;
    $gutter_h = $h - $tb_h;
    $bar_y   = $h - 3;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="$tb_bg"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="$border"/>
  $dots
  <text x="$cx" y="$tb_mid" text-anchor="middle" font-family="'$font',monospace" font-size="13" fill="#6272a4">$title</text>
  <rect x="0" y="$tb_h" width="32" height="$gutter_h" fill="$tb_bg" opacity="0.6"/>
  $svg_lines
  <rect x="0" y="$bar_y" width="$w" height="3" fill="#bd93f9"/>
</g></svg>
SVG;
}

function pt_code_minimal(array $p): string
{
    [$w,$h,$bg,$hc,$dc,$ac,$r,$font] = pt_code_vars($p);
    $fa      = PT_Icons::fa_style_import();
    $tb_h    = 44;
    $tb_bg   = '#f0f0f0';
    $border  = '#e0e0e0';
    $title   = PT_Text::e($p['heading'] ?: 'snippet.js');
    $lang_e  = PT_Text::e($p['language'] ?: 'javascript');
    $dots    = PT_Renderer::traffic_lights(16, $tb_h/2, 7, 22);
    $lines   = pt_code_tokenize($p, 'minimal_light');
    $svg_lines = pt_code_lines_svg($lines, 40, $tb_h + 18, 26, $font, 15, $h - 20);
    $cx       = (int)($w / 2);
    $tb_mid   = (int)($tb_h / 2) + 5;
    $gutter_h = $h - $tb_h;
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$w" height="$h" viewBox="0 0 $w $h">
<defs><style>$fa</style><clipPath id="clip"><rect width="$w" height="$h" rx="$r"/></clipPath></defs>
<g clip-path="url(#clip)">
  <rect width="$w" height="$h" fill="$bg" stroke="$border" stroke-width="1"/>
  <rect x="0" y="0" width="$w" height="$tb_h" fill="$tb_bg"/>
  <rect x="0" y="$tb_h" width="$w" height="1" fill="$border"/>
  $dots
  <text x="$cx" y="$tb_mid" text-anchor="middle" font-family="'$font',monospace" font-size="13" fill="#666666">$title</text>
  <rect x="0" y="$tb_h" width="32" height="$gutter_h" fill="#f8f8f8"/>
  <rect x="32" y="$tb_h" width="1" height="$gutter_h" fill="$border"/>
  $svg_lines
</g></svg>
SVG;
}

function pt_code_tokenize(array $p, string $theme): array
{
    $raw   = $p['code'] ?: "const greeting = 'Hello, World!';\nconsole.log(greeting);\n\nfunction add(a, b) {\n  return a + b;\n}\n\nexport default { greeting, add };";
    $lines = explode("\n", $raw);

    $themes = [
        'vscode_dark'  => ['kw' => '#569cd6', 'str' => '#ce9178', 'num' => '#b5cea8', 'cm' => '#6a9955', 'fn' => '#dcdcaa', 'var' => '#9cdcfe', 'def' => '#d4d4d4', 'op' => '#d4d4d4', 'ln' => '#858585'],
        'github_dark'  => ['kw' => '#ff7b72', 'str' => '#a5d6ff', 'num' => '#79c0ff', 'cm' => '#8b949e', 'fn' => '#d2a8ff', 'var' => '#ffa657', 'def' => '#e6edf3', 'op' => '#e6edf3', 'ln' => '#30363d'],
        'dracula'      => ['kw' => '#ff79c6', 'str' => '#f1fa8c', 'num' => '#bd93f9', 'cm' => '#6272a4', 'fn' => '#50fa7b', 'var' => '#8be9fd', 'def' => '#f8f8f2', 'op' => '#ff79c6', 'ln' => '#44475a'],
        'minimal_light'=> ['kw' => '#0550ae', 'str' => '#0a3069', 'num' => '#0550ae', 'cm' => '#6e7781', 'fn' => '#8250df', 'var' => '#953800', 'def' => '#24292f', 'op' => '#24292f', 'ln' => '#8c959f'],
    ];
    $c = $themes[$theme] ?? $themes['vscode_dark'];

    $result = [];
    $kws    = ['const','let','var','function','return','if','else','for','while','class','import','export','default','new','this','async','await','try','catch','throw','typeof','instanceof','null','undefined','true','false','void','delete','in','of','from','do','switch','case','break','continue','extends','super','static','get','set','yield','with'];

    foreach ($lines as $i => $line) {
        $ln = $i + 1;
        $result[] = pt_code_tokenize_line($line, $ln, $c, $kws);
    }
    return $result;
}

function pt_code_tokenize_line(string $line, int $ln, array $c, array $kws): array
{
    $tokens = [];
    $tokens[] = ['text' => str_pad((string)$ln, 3, ' ', STR_PAD_LEFT), 'color' => $c['ln'], 'ln' => true];

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
            if (in_array($word, $kws, true)) {
                $tokens[] = ['text' => $word, 'color' => $c['kw'], 'ln' => false];
            } else {
                $rest = substr($remaining, strlen($word));
                if ($rest !== '' && $rest[0] === '(') {
                    $tokens[] = ['text' => $word, 'color' => $c['fn'], 'ln' => false];
                } else {
                    $tokens[] = ['text' => $word, 'color' => $c['var'], 'ln' => false];
                }
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
    $out  = '';
    $y    = $start_y;
    $x_ln = $x_code - 36;
    foreach ($lines as $line_tokens) {
        if ($y > $max_y) break;
        $x = $x_code;
        foreach ($line_tokens as $token) {
            if ($token['ln'] ?? false) {
                $te  = PT_Text::e($token['text']);
                $col = $token['color'];
                $out .= "<text x='$x_ln' y='$y' font-family=\"'$font',monospace\" font-size='$fs' fill='$col'>$te</text>";
            } else {
                $te  = PT_Text::e($token['text']);
                $col = $token['color'];
                $cw  = mb_strlen($token['text']) * $fs * 0.605;
                $out .= "<text x='$x' y='$y' font-family=\"'$font',monospace\" font-size='$fs' fill='$col'>$te</text>";
                $x  += $cw;
            }
        }
        $y += $lh;
    }
    return $out;
}

function pt_code_vars(array $p): array
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
