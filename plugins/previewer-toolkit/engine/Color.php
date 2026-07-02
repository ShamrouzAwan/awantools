<?php
defined('AWAN') or die('Direct access denied.');

class PT_Color
{
    static function h(string $hex): string
    {
        return '#' . ltrim($hex, '#');
    }

    static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    static function rgba(string $hex, float $alpha): string
    {
        $c = self::rgb($hex);
        return "rgba({$c['r']},{$c['g']},{$c['b']},$alpha)";
    }

    static function luminance(string $hex): float
    {
        $c = self::rgb($hex);
        return (0.299 * $c['r'] + 0.587 * $c['g'] + 0.114 * $c['b']) / 255;
    }

    static function is_dark(string $hex): bool
    {
        return self::luminance($hex) < 0.5;
    }

    static function lighten(string $hex, float $amount): string
    {
        $c = self::rgb($hex);
        $r = min(255, (int)($c['r'] + ($amount * 255)));
        $g = min(255, (int)($c['g'] + ($amount * 255)));
        $b = min(255, (int)($c['b'] + ($amount * 255)));
        return sprintf('%02x%02x%02x', $r, $g, $b);
    }

    static function darken(string $hex, float $amount): string
    {
        $c = self::rgb($hex);
        $r = max(0, (int)($c['r'] - ($amount * 255)));
        $g = max(0, (int)($c['g'] - ($amount * 255)));
        $b = max(0, (int)($c['b'] - ($amount * 255)));
        return sprintf('%02x%02x%02x', $r, $g, $b);
    }

    static function mix(string $hex1, string $hex2, float $weight = 0.5): string
    {
        $a = self::rgb($hex1);
        $b = self::rgb($hex2);
        $r = (int)($a['r'] * (1 - $weight) + $b['r'] * $weight);
        $g = (int)($a['g'] * (1 - $weight) + $b['g'] * $weight);
        $b2= (int)($a['b'] * (1 - $weight) + $b['b'] * $weight);
        return sprintf('%02x%02x%02x', $r, $g, $b2);
    }

    static function auto_text(string $bg_hex): string
    {
        return self::is_dark($bg_hex) ? 'ffffff' : '111111';
    }

    static function auto_muted(string $bg_hex): string
    {
        return self::is_dark($bg_hex) ? '94a3b8' : '6b7280';
    }

    static function gradient_stops(string $from, string $to, string $id = 'g'): string
    {
        $f = self::h($from);
        $t = self::h($to);
        return "<linearGradient id='$id' x1='0' y1='0' x2='1' y2='1'>"
             . "<stop offset='0%' stop-color='$f'/>"
             . "<stop offset='100%' stop-color='$t'/>"
             . "</linearGradient>";
    }

    static function radial_stops(string $center, string $edge, string $id = 'rg'): string
    {
        $c = self::h($center);
        $e = self::h($edge);
        return "<radialGradient id='$id' cx='50%' cy='50%' r='70%'>"
             . "<stop offset='0%' stop-color='$c'/>"
             . "<stop offset='100%' stop-color='$e'/>"
             . "</radialGradient>";
    }
}
