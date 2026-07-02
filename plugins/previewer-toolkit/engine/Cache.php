<?php
defined('AWAN') or die('Direct access denied.');

/**
 * PT_Cache — file-based server-side image cache for Previewer Toolkit.
 *
 * Cache directory : sys_get_temp_dir()/pt_img_cache/
 * Cache key       : MD5 of sorted, normalised query params
 * Eviction        : manual (Clear Cache button) + auto-prune above MAX_FILES
 * TTL             : none — content is deterministic; clear on code updates
 */
class PT_Cache
{
    const MAX_FILES = 2000;
    const DIR_NAME  = 'pt_img_cache';

    // ── Internals ──────────────────────────────────────────────────────────────

    private static ?string $dir = null;

    private static function dir(): string
    {
        if (self::$dir === null) {
            $d = sys_get_temp_dir() . '/' . self::DIR_NAME;
            if (!is_dir($d)) {
                @mkdir($d, 0755, true);
            }
            self::$dir = $d;
        }
        return self::$dir;
    }

    /** Normalise format string the same way Params::parse() does. */
    private static function norm_fmt(string $f): string
    {
        $f = strtolower(trim($f));
        $f = ($f === 'jpeg') ? 'jpg' : $f;
        return in_array($f, ['svg', 'png', 'jpg', 'webp'], true) ? $f : 'svg';
    }

    /** Remove oldest files until we are below MAX_FILES. */
    private static function maybe_prune(): void
    {
        $dir   = self::dir();
        $files = glob($dir . '/*') ?: [];
        if (count($files) < self::MAX_FILES) return;

        usort($files, fn($a, $b) => filemtime($a) <=> filemtime($b));
        $remove = (int)(self::MAX_FILES * 0.20);   // drop oldest 20 %
        foreach (array_slice($files, 0, $remove) as $f) {
            @unlink($f);
        }
    }

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Compute a stable cache key from raw GET params.
     * Format is normalised so jpeg/jpg share the same entry.
     */
    static function key(array $raw): string
    {
        $raw['format'] = self::norm_fmt($raw['format'] ?? 'svg');
        unset($raw['_']);               // ignore cache-buster params if any
        ksort($raw);
        return md5(http_build_query($raw));
    }

    /** Return format extension from raw params (normalised). */
    static function fmt(array $raw): string
    {
        return self::norm_fmt($raw['format'] ?? 'svg');
    }

    /** Path to cache file (may not exist). */
    static function path(string $key, string $fmt): string
    {
        return self::dir() . '/' . $key . '.' . $fmt;
    }

    /**
     * Try to serve a cached response.
     * Returns true and outputs the response if found, false on miss.
     */
    static function serve_if_hit(string $key, string $fmt): bool
    {
        $path = self::path($key, $fmt);
        if (!file_exists($path) || filesize($path) === 0) return false;

        $mime = self::mime($fmt);
        header("Content-Type: $mime");
        header('Cache-Control: public, max-age=86400');
        header('X-Cache: HIT');
        readfile($path);
        return true;
    }

    /**
     * Write captured output to cache.
     * Called after ob_get_clean() captures the exporter output.
     */
    static function store(string $key, string $fmt, string $data): void
    {
        if ($data === '') return;
        self::maybe_prune();
        $path = self::path($key, $fmt);
        file_put_contents($path, $data, LOCK_EX);
    }

    /**
     * Delete all cache files.
     * @return int number of files removed
     */
    static function clear(): int
    {
        $dir   = self::dir();
        $files = glob($dir . '/*') ?: [];
        $n     = 0;
        foreach ($files as $f) {
            if (is_file($f) && @unlink($f)) $n++;
        }
        return $n;
    }

    /** Return cache statistics [count, size_bytes, size_human]. */
    static function stats(): array
    {
        $dir   = self::dir();
        $files = glob($dir . '/*') ?: [];
        $size  = 0;
        foreach ($files as $f) {
            if (is_file($f)) $size += (int)filesize($f);
        }
        return [
            'count' => count($files),
            'size'  => $size,
            'human' => self::human_size($size),
        ];
    }

    static function mime(string $fmt): string
    {
        return match($fmt) {
            'jpg'  => 'image/jpeg',
            'webp' => 'image/webp',
            'png'  => 'image/png',
            default => 'image/svg+xml; charset=utf-8',
        };
    }

    private static function human_size(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
