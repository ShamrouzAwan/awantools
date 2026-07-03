<?php
defined('AWAN') or die('Direct access denied.');

class PT_Renderer
{
    static function render(array $p): string
    {
        require_once __DIR__ . '/Templates.php';

        $cat = $p['category'];
        $fn  = 'pt_render_' . $cat;

        if (!function_exists($fn)) {
            return self::error_svg($p, "Renderer not found for category: $cat");
        }

        try {
            return $fn($p);
        } catch (\Throwable $e) {
            return self::error_svg($p, $e->getMessage());
        }
    }

    static function dots(int $cols, int $rows, float $startX, float $startY, float $gap, string $color, float $opacity = 0.25, float $r = 2.5): string
    {
        $out = '';
        for ($i = 0; $i < $cols; $i++) {
            for ($j = 0; $j < $rows; $j++) {
                $x = $startX + $i * $gap;
                $y = $startY + $j * $gap;
                $out .= "<circle cx='$x' cy='$y' r='$r' fill='$color' opacity='$opacity'/>";
            }
        }
        return $out;
    }

    static function glow_filter(string $id = 'glow', int $std = 6): string
    {
        return "<filter id='$id' x='-30%' y='-30%' width='160%' height='160%'>
  <feGaussianBlur stdDeviation='$std' result='blur'/>
  <feMerge><feMergeNode in='blur'/><feMergeNode in='blur'/><feMergeNode in='SourceGraphic'/></feMerge>
</filter>";
    }

    static function noise_filter(string $id = 'noise'): string
    {
        return "<filter id='$id'><feTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/><feColorMatrix type='saturate' values='0'/><feBlend in='SourceGraphic' mode='overlay' result='blend'/></filter>";
    }

    static function traffic_lights(float $x, float $y, float $r = 7, float $gap = 20): string
    {
        return "<circle cx='$x' cy='$y' r='$r' fill='#ff5f57'/>
<circle cx='" . ($x + $gap) . "' cy='$y' r='$r' fill='#febc2e'/>
<circle cx='" . ($x + $gap * 2) . "' cy='$y' r='$r' fill='#28c840'/>";
    }

    static function error_svg(array $p, string $msg): string
    {
        $w  = $p['width']  ?? 800;
        $h  = $p['height'] ?? 400;
        $cx = (int)($w / 2);
        $cy = (int)($h / 2);
        $em = htmlspecialchars($msg, ENT_XML1);
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$w" height="$h" viewBox="0 0 $w $h">
  <rect width="$w" height="$h" fill="#1a1a2e"/>
  <text x="$cx" y="$cy" text-anchor="middle" dominant-baseline="middle"
        font-family="monospace" font-size="16" fill="#ef4444">Error: $em</text>
</svg>
SVG;
    }
}
