<?php
defined('AWAN') or die('Direct access denied.');

class Security {
    // ─── CSRF ─────────────────────────────────────────────────────────────────

    public static function csrfToken(): string {
        return Session::csrfToken();
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="_csrf" value="' . self::csrfToken() . '">';
    }

    public static function verifyCsrf(): void {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals(Session::csrfToken(), $token)) {
            http_response_code(419);
            die(renderError(419, 'Session Expired', 'Your session has expired. Please go back and try again.'));
        }
    }

    // ─── Rate Limiting ────────────────────────────────────────────────────────

    public static function checkRateLimit(string $key, int $maxAttempts = RATE_LIMIT_LOGIN, int $window = RATE_LIMIT_WINDOW): bool {
        $cacheKey = '_rl_' . md5($key);
        $data = Session::get($cacheKey, ['count' => 0, 'reset' => time() + $window]);

        if (time() > $data['reset']) {
            $data = ['count' => 0, 'reset' => time() + $window];
        }

        if ($data['count'] >= $maxAttempts) {
            return false;
        }

        $data['count']++;
        Session::set($cacheKey, $data);
        return true;
    }

    public static function clearRateLimit(string $key): void {
        Session::remove('_rl_' . md5($key));
    }

    public static function getRateLimitRemaining(string $key, int $maxAttempts = RATE_LIMIT_LOGIN, int $window = RATE_LIMIT_WINDOW): int {
        $data = Session::get('_rl_' . md5($key), ['count' => 0, 'reset' => time() + $window]);
        if (time() > $data['reset']) return $maxAttempts;
        return max(0, $maxAttempts - $data['count']);
    }

    // ─── Input Sanitization ───────────────────────────────────────────────────

    public static function sanitize(string $input): string {
        return trim(strip_tags($input));
    }

    public static function sanitizeEmail(string $email): string {
        return strtolower(trim(filter_var($email, FILTER_SANITIZE_EMAIL)));
    }

    public static function sanitizeInt(mixed $val): int {
        return (int)filter_var($val, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function sanitizeSlug(string $str): string {
        $str = strtolower(trim($str));
        $str = preg_replace('/[^a-z0-9\-_]/', '-', $str);
        return preg_replace('/-+/', '-', trim($str, '-'));
    }

    // ─── Validation ───────────────────────────────────────────────────────────

    public static function validateEmail(string $email): bool {
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validatePassword(string $password): array {
        $errors = [];
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must contain at least one uppercase letter.';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain at least one number.';
        return $errors;
    }

    public static function validateUsername(string $username): array {
        $errors = [];
        if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
        if (strlen($username) > 40) $errors[] = 'Username must be 40 characters or fewer.';
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) $errors[] = 'Username may only contain letters, numbers, underscores, and hyphens.';
        return $errors;
    }

    // ─── Password Hashing ─────────────────────────────────────────────────────

    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    // ─── Secure Random ────────────────────────────────────────────────────────

    public static function token(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }

    // ─── File Upload Security ─────────────────────────────────────────────────

    public static function validateUpload(array $file, array $allowedMime = [], int $maxBytes = 10485760): array {
        $errors = [];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload failed with error code ' . $file['error'];
            return $errors;
        }
        if ($file['size'] > $maxBytes) {
            $errors[] = 'File too large. Maximum size is ' . round($maxBytes / 1048576, 1) . 'MB.';
        }
        if (!empty($allowedMime)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);
            if (!in_array($mime, $allowedMime)) {
                $errors[] = 'File type not allowed.';
            }
        }
        return $errors;
    }

    public static function safeFilename(string $name): string {
        $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($name));
        return preg_replace('/\.+/', '.', $name);
    }
}
