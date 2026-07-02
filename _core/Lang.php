<?php
defined('AWAN') or die('Direct access denied.');

/**
 * Lang — Simple i18n translation system for AWAN Platform.
 * Language files live in AWAN_ROOT/_lang/{locale}.php and return an associative array.
 */
class Lang {
    private static ?self $instance = null;
    private array  $strings = [];
    private string $locale;

    private function __construct(string $locale) {
        $this->locale = $locale;
        $this->load($locale);
    }

    public static function getInstance(string $locale = 'en'): self {
        if (!self::$instance) {
            self::$instance = new self($locale);
        }
        return self::$instance;
    }

    private function load(string $locale): void {
        $safe = preg_replace('/[^a-z0-9_\-]/i', '', $locale);
        $file = AWAN_ROOT . '/_lang/' . $safe . '.php';
        if (file_exists($file)) {
            $loaded = require $file;
            if (is_array($loaded)) {
                $this->strings = $loaded;
            }
        }
        // Merge English as fallback for any missing keys
        if ($locale !== 'en') {
            $enFile = AWAN_ROOT . '/_lang/en.php';
            if (file_exists($enFile)) {
                $en = require $enFile;
                if (is_array($en)) {
                    $this->strings = array_merge($en, $this->strings);
                }
            }
        }
    }

    public function get(string $key, array $replace = []): string {
        $str = $this->strings[$key] ?? $key;
        foreach ($replace as $k => $v) {
            $str = str_replace(':' . $k, (string)$v, $str);
        }
        return $str;
    }

    public function locale(): string { return $this->locale; }
    public function all(): array    { return $this->strings; }
}

/** Global translation helper. */
function t(string $key, array $replace = []): string {
    global $lang;
    if ($lang instanceof Lang) return $lang->get($key, $replace);
    // Fallback: apply simple replacements if $lang not yet loaded
    $str = $key;
    foreach ($replace as $k => $v) $str = str_replace(':' . $k, (string)$v, $str);
    return $str;
}
