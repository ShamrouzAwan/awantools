<?php
defined('AWAN') or die('Direct access denied.');

class PT_Text
{
    static function wrap(string $text, float $maxWidth, float $fontSize, float $charRatio = 0.52, int $maxLines = 3): array
    {
        if ($text === '') return [];
        $charWidth = $fontSize * $charRatio;
        $maxChars  = max(10, (int)($maxWidth / $charWidth));

        $words   = preg_split('/\s+/', $text);
        $lines   = [];
        $current = '';

        foreach ($words as $word) {
            if ($word === '') continue;
            $test = $current === '' ? $word : "$current $word";
            if (mb_strlen($test) <= $maxChars) {
                $current = $test;
            } else {
                if ($current !== '') {
                    $lines[] = $current;
                    if (count($lines) >= $maxLines) { $current = ''; break; }
                }
                if (mb_strlen($word) > $maxChars) {
                    $word    = mb_substr($word, 0, $maxChars - 3) . '...';
                    $lines[] = $word;
                    if (count($lines) >= $maxLines) { $current = ''; break; }
                    $current = '';
                } else {
                    $current = $word;
                }
            }
        }
        if ($current !== '' && count($lines) < $maxLines) {
            $lines[] = $current;
        }
        return $lines;
    }

    static function tspans(array $lines, float $x, float $lineHeight, string $anchor = 'start'): string
    {
        $out = '';
        foreach ($lines as $i => $line) {
            $dy  = $i === 0 ? '0' : $lineHeight;
            $esc = htmlspecialchars($line, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $out .= "<tspan x='$x' dy='$dy' text-anchor='$anchor'>$esc</tspan>";
        }
        return $out;
    }

    static function tspans_center(array $lines, float $cx, float $lineHeight): string
    {
        return self::tspans($lines, $cx, $lineHeight, 'middle');
    }

    static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    static function total_height(array $lines, float $fontSize, float $lineHeight): float
    {
        $count = count($lines);
        if ($count === 0) return 0;
        return $fontSize + ($count - 1) * $lineHeight;
    }

    static function mono_wrap(string $text, int $maxChars, int $maxLines = 20): array
    {
        $raw   = explode("\n", $text);
        $lines = [];
        foreach ($raw as $raw_line) {
            if (count($lines) >= $maxLines) break;
            if (mb_strlen($raw_line) <= $maxChars) {
                $lines[] = $raw_line;
            } else {
                $chunks = mb_str_split($raw_line, $maxChars);
                foreach ($chunks as $chunk) {
                    if (count($lines) >= $maxLines) break;
                    $lines[] = $chunk;
                }
            }
        }
        return array_slice($lines, 0, $maxLines);
    }
}
