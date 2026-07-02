<?php
defined('AWAN') or die('Direct access denied.');

class Session {
    private static bool $started = false;

    public static function start(): void {
        if (self::$started) return;

        // Detect HTTPS — works with direct TLS and reverse-proxy environments
        // (Nginx, Cloudflare, etc. forward via X-Forwarded-Proto)
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['HTTP_X_FORWARDED_SSL']   ?? '') === 'on');

        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        session_name('AWAN_SESSION');

        // Production: SameSite=Lax prevents CSRF while allowing top-level navigations.
        // SameSite=Strict would break OAuth redirects; SameSite=None requires a proxy context.
        if ($isHttps) {
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.cookie_secure', 1);
        } else {
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.cookie_secure', 0);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Rotate session ID only after a long idle period — NOT on every new session.
        if (isset($_SESSION['_last_active']) && time() - $_SESSION['_last_active'] > 1800) {
            session_regenerate_id(true);
        }
        $_SESSION['_last_active'] = time();

        self::$started = true;
    }

    public static function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    // Flash messages — survive exactly one redirect
    public static function flash(string $key, string $message): void {
        $_SESSION['_flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string {
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function hasFlash(string $key): bool {
        return isset($_SESSION['_flash'][$key]);
    }

    public static function getAllFlash(): array {
        $all = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $all;
    }

    // CSRF token storage
    public static function csrfToken(): string {
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }
        return $_SESSION['_csrf'];
    }

    public static function regenerateId(): void {
        session_regenerate_id(true);
    }
}
