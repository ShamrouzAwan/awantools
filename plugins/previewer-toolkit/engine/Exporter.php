<?php
defined('AWAN') or die('Direct access denied.');

class PT_Exporter
{
    /**
     * Returns true only if exec() is callable and not blocked by disable_functions.
     */
    private static function exec_available(): bool
    {
        if (!function_exists('exec')) return false;
        $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
        return !in_array('exec', $disabled, true);
    }

    static function output(string $svg, array $p): void
    {
        $fmt     = $p['format'];
        $quality = $p['quality'];
        $w       = $p['width'];
        $h       = $p['height'];

        if ($fmt === 'svg') {
            header('Content-Type: image/svg+xml; charset=utf-8');
            header('Cache-Control: public, max-age=86400');
            echo $svg;
            return;
        }

        // Try ImageMagick only when exec() is available
        if (self::exec_available()) {
            // Use random_bytes() to generate unguessable temp filenames in one
            // step — avoids the TOCTOU window that tempnam()+rename() creates.
            $rnd     = bin2hex(random_bytes(8));
            $tmp_dir = sys_get_temp_dir();
            $tmp_svg = $tmp_dir . '/pt_' . $rnd . '_in.svg';
            file_put_contents($tmp_svg, $svg);

            $ext     = $fmt === 'jpg' ? 'jpg' : $fmt;
            $tmp_out = $tmp_dir . '/pt_' . $rnd . '_out.' . $ext;

            $quality_flag = '';
            if ($fmt === 'jpg' || $fmt === 'webp') {
                $quality_flag = '-quality ' . escapeshellarg((string)$quality);
            }

            $density = 96;
            $cmd = 'magick'
                 . ' -density ' . escapeshellarg((string)$density)
                 . ' -background ' . ($p['transparent'] ? 'none' : escapeshellarg('#' . $p['bg_color']))
                 . ' ' . escapeshellarg($tmp_svg)
                 . ' -resize ' . escapeshellarg("{$w}x{$h}")
                 . ($quality_flag !== '' ? ' ' . $quality_flag : '')
                 . ' ' . escapeshellarg($tmp_out)
                 . ' 2>&1';

            $output = [];
            $ret    = 0;
            exec($cmd, $output, $ret);

            if ($ret === 0 && file_exists($tmp_out) && filesize($tmp_out) > 0) {
                $mime = match($fmt) {
                    'jpg'  => 'image/jpeg',
                    'webp' => 'image/webp',
                    default => 'image/png',
                };

                header("Content-Type: $mime");
                header('Cache-Control: public, max-age=86400');
                readfile($tmp_out);

                @unlink($tmp_svg);
                @unlink($tmp_out);
                return;
            }

            @unlink($tmp_svg);
            @unlink($tmp_out);
        }

        // Fall back to GD (always available, no exec needed)
        self::gd_fallback($svg, $p);
    }

    private static function gd_fallback(string $svg, array $p): void
    {
        $w   = $p['width'];
        $h   = $p['height'];
        $fmt = $p['format'];

        $im  = imagecreatetruecolor($w, $h);
        $bg  = PT_Color::rgb($p['bg_color']);
        $hc  = PT_Color::rgb($p['heading_color']);
        $dc  = PT_Color::rgb($p['description_color']);
        $ac  = PT_Color::rgb($p['accent_color']);

        $bg_c = imagecolorallocate($im, $bg['r'], $bg['g'], $bg['b']);
        $hc_c = imagecolorallocate($im, $hc['r'], $hc['g'], $hc['b']);
        $dc_c = imagecolorallocate($im, $dc['r'], $dc['g'], $dc['b']);
        $ac_c = imagecolorallocate($im, $ac['r'], $ac['g'], $ac['b']);

        imagefill($im, 0, 0, $bg_c);
        // Accent top bar
        imagefilledrectangle($im, 0, 0, $w, (int)($h * 0.006), $ac_c);

        $pad = $p['padding'];

        // Use built-in GD font (no external font file needed)
        // Font 5 = largest built-in, ~9×15 px per char
        $font_h = 5;
        $font_d = 3;

        $heading     = substr($p['heading'], 0, 60);
        $description = substr($p['description'], 0, 100);
        $footer      = substr($p['footer'], 0, 60);

        $char_w_h = imagefontwidth($font_h);
        $char_h_h = imagefontheight($font_h);
        $char_w_d = imagefontwidth($font_d);

        $max_chars_h = max(1, (int)(($w - $pad * 2) / $char_w_h));
        $max_chars_d = max(1, (int)(($w - $pad * 2) / $char_w_d));

        $lines_h = str_split($heading, $max_chars_h);
        $y = (int)($h * 0.35);
        foreach (array_slice($lines_h, 0, 3) as $line) {
            imagestring($im, $font_h, $pad, $y, $line, $hc_c);
            $y += $char_h_h + 4;
        }

        $lines_d = str_split($description, $max_chars_d);
        $y += 8;
        foreach (array_slice($lines_d, 0, 3) as $line) {
            imagestring($im, $font_d, $pad, $y, $line, $dc_c);
            $y += imagefontheight($font_d) + 3;
        }

        imagestring($im, 2, $pad, $h - $pad - imagefontheight(2), $footer, $dc_c);

        header('Content-Type: image/' . ($fmt === 'jpg' ? 'jpeg' : $fmt));
        header('Cache-Control: public, max-age=86400');

        match($fmt) {
            'jpg'  => imagejpeg($im, null, $p['quality']),
            'webp' => imagewebp($im, null, $p['quality']),
            default => imagepng($im),
        };
        imagedestroy($im);
    }
}
