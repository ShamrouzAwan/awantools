<?php
defined('AWAN') or die('Direct access denied.');

class Auth {
    private static ?Auth $instance = null;
    private Database $db;
    private Settings $settings;
    private ?array $user = null;

    private function __construct(Database $db, Settings $settings) {
        $this->db       = $db;
        $this->settings = $settings;
        $this->loadUser();
    }

    public static function getInstance(Database $db, Settings $settings): Auth {
        if (self::$instance === null) {
            self::$instance = new self($db, $settings);
        }
        return self::$instance;
    }

    private function loadUser(): void {
        $userId = Session::get('user_id');
        if (!$userId) return;

        $user = $this->db->fetch(
            "SELECT u.*, GROUP_CONCAT(r.slug) as roles FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE u.id = ? AND u.status = 'active'
             GROUP BY u.id",
            [$userId]
        );

        if ($user) {
            $user['roles'] = $user['roles'] ? explode(',', $user['roles']) : [];
            $this->user = $user;
        } else {
            Session::destroy();
        }
    }

    // ─── Auth State ───────────────────────────────────────────────────────────

    public function check(): bool          { return $this->user !== null; }
    public function user(): ?array         { return $this->user; }
    public function id(): ?int             { return $this->user ? (int)$this->user['id'] : null; }
    public function email(): ?string       { return $this->user['email'] ?? null; }
    public function name(): ?string        { return $this->user['name'] ?? $this->user['username'] ?? null; }
    public function username(): ?string    { return $this->user['username'] ?? null; }

    public function isEmailVerified(): bool {
        return !empty($this->user['email_verified_at']);
    }

    public function hasRole(string ...$roles): bool {
        if (!$this->user) return false;
        foreach ($roles as $role) {
            if (in_array($role, $this->user['roles'])) return true;
        }
        return false;
    }

    public function isAdmin(): bool      { return $this->hasRole(ADMIN_ROLE, SUPER_ROLE); }
    public function isSuperAdmin(): bool { return $this->hasRole(SUPER_ROLE); }

    // ─── Login / Logout ───────────────────────────────────────────────────────

    public function attempt(string $emailOrUsername, string $password): array {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!Security::checkRateLimit('login_' . $ip)) {
            return ['success' => false, 'error' => 'Too many login attempts. Please wait 15 minutes.'];
        }

        $user = $this->db->fetch(
            "SELECT * FROM users WHERE (email = ? OR username = ?) AND status = 'active' LIMIT 1",
            [$emailOrUsername, $emailOrUsername]
        );

        if (!$user || !Security::verifyPassword($password, $user['password'])) {
            return ['success' => false, 'error' => 'Invalid credentials. Please try again.'];
        }

        Security::clearRateLimit('login_' . $ip);

        // Email verification check
        if ($this->settings->get('email_verification_enabled', '0') === '1'
            && empty($user['email_verified_at'])) {
            return [
                'success'            => false,
                'needs_verification' => true,
                'user_email'         => $user['email'],
                'error'              => 'Please verify your email address before signing in.',
            ];
        }

        // Email OTP 2FA check
        if (!empty($user['two_fa_enabled'])) {
            $code    = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', time() + 600);
            try {
                $this->db->query("DELETE FROM user_otp_codes WHERE user_id = ?", [$user['id']]);
                $this->db->insert('user_otp_codes', [
                    'user_id'    => $user['id'],
                    'code'       => $code,
                    'expires_at' => $expires,
                    'used'       => 0,
                ]);
            } catch (Throwable $e) {}
            Session::set('otp_pending_user_id', $user['id']);
            Session::set('otp_pending_at', time());
            return [
                'success'    => false,
                'needs_otp'  => true,
                'otp_code'   => $code,
                'user_email' => $user['email'],
                'user_name'  => $user['name'] ?: $user['username'],
            ];
        }

        $this->startSession($user);
        return ['success' => true, 'user' => $user];
    }

    /** Verify an email OTP code and return true if valid. */
    public function verifyOtpCode(int $userId, string $code): bool {
        try {
            $row = $this->db->fetch(
                "SELECT * FROM user_otp_codes WHERE user_id = ? AND code = ? AND used = 0 AND expires_at > ?",
                [$userId, $code, date('Y-m-d H:i:s')]
            );
            if (!$row) return false;
            $this->db->update('user_otp_codes', ['used' => 1], 'id = ?', [$row['id']]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /** Complete login after successful OTP verification. */
    public function completeOtpLogin(int $userId): bool {
        Session::remove('otp_pending_user_id');
        Session::remove('otp_pending_at');
        return $this->loginById($userId);
    }

    /** Complete login after successful TOTP verification (legacy compat). */
    public function completeTotpLogin(int $userId): bool {
        Session::remove('totp_pending_user_id');
        Session::remove('totp_pending_at');
        return $this->loginById($userId);
    }

    public function loginById(int $userId): bool {
        $user = $this->db->fetch(
            "SELECT u.*, GROUP_CONCAT(r.slug) as roles FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE u.id = ? AND u.status = 'active'
             GROUP BY u.id",
            [$userId]
        );
        if (!$user) return false;
        $user['roles'] = $user['roles'] ? explode(',', $user['roles']) : [];
        $this->startSession($user);
        return true;
    }

    private function startSession(array $user): void {
        Session::regenerateId();
        Session::set('user_id', $user['id']);

        $this->db->update('users', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ], 'id = ?', [$user['id']]);

        $user['roles'] = $this->getUserRoles($user['id']);
        $this->user = $user;
    }

    public function logout(): void {
        $this->user = null;
        Session::destroy();
    }

    // ─── Registration ─────────────────────────────────────────────────────────

    public function register(string $username, string $email, string $password, string $name = ''): array {
        if (!$this->settings->registrationEnabled()) {
            return ['success' => false, 'error' => 'Registration is currently disabled.'];
        }

        $errors = [];
        $errors = array_merge($errors, Security::validateUsername($username));
        if (!Security::validateEmail($email)) $errors[] = 'Invalid email address.';
        $errors = array_merge($errors, Security::validatePassword($password));

        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        $email    = Security::sanitizeEmail($email);
        $username = Security::sanitize($username);

        if ($this->db->exists('users', 'email = ?', [$email])) {
            return ['success' => false, 'error' => 'An account with this email already exists.'];
        }
        if ($this->db->exists('users', 'username = ?', [$username])) {
            return ['success' => false, 'error' => 'This username is taken.'];
        }

        $userId = $this->db->insert('users', [
            'username'   => $username,
            'email'      => $email,
            'name'       => $name ?: $username,
            'password'   => Security::hashPassword($password),
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $defaultRole = $this->db->fetch("SELECT id FROM roles WHERE slug = ?", [DEFAULT_ROLE]);
        if ($defaultRole) {
            $this->db->insert('user_roles', ['user_id' => $userId, 'role_id' => $defaultRole['id']]);
        }

        if ($this->db->count('users') === 1) {
            $superRole = $this->db->fetch("SELECT id FROM roles WHERE slug = ?", [SUPER_ROLE]);
            if ($superRole) {
                $this->db->insert('user_roles', ['user_id' => $userId, 'role_id' => $superRole['id']]);
            }
        }

        return ['success' => true, 'user_id' => $userId];
    }

    // ─── Password Reset ───────────────────────────────────────────────────────

    public function generatePasswordResetToken(string $email): ?string {
        $user = $this->db->fetch("SELECT id FROM users WHERE email = ? AND status = 'active' LIMIT 1", [$email]);
        if (!$user) return null;

        $token = bin2hex(random_bytes(32));
        try {
            $this->db->query("DELETE FROM password_reset_tokens WHERE user_id = ?", [$user['id']]);
            $this->db->insert('password_reset_tokens', [
                'user_id'    => $user['id'],
                'token'      => $token,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) { return null; }
        return $token;
    }

    public function validatePasswordResetToken(string $token): ?array {
        try {
            $row = $this->db->fetch(
                "SELECT prt.*, u.email, u.name FROM password_reset_tokens prt
                 JOIN users u ON u.id = prt.user_id
                 WHERE prt.token = ? AND prt.used_at IS NULL AND prt.expires_at > ?
                 LIMIT 1",
                [$token, date('Y-m-d H:i:s')]
            );
        } catch (Throwable $e) { return null; }
        return $row ?: null;
    }

    public function resetPassword(string $token, string $newPassword): array {
        $row = $this->validatePasswordResetToken($token);
        if (!$row) {
            return ['success' => false, 'error' => 'Invalid or expired reset link. Please request a new one.'];
        }

        $errors = Security::validatePassword($newPassword);
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        try {
            $this->db->update('users', [
                'password'   => Security::hashPassword($newPassword),
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$row['user_id']]);

            $this->db->update('password_reset_tokens', ['used_at' => date('Y-m-d H:i:s')], 'id = ?', [$row['id']]);
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'Failed to update password. Please try again.'];
        }

        return ['success' => true, 'user_id' => (int)$row['user_id']];
    }

    // ─── Email Verification ───────────────────────────────────────────────────

    public function generateEmailVerificationToken(int $userId): string {
        $token = bin2hex(random_bytes(32));
        try {
            $this->db->query("DELETE FROM email_verification_tokens WHERE user_id = ?", [$userId]);
            $this->db->insert('email_verification_tokens', [
                'user_id'    => $userId,
                'token'      => $token,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {}
        return $token;
    }

    public function verifyEmailToken(string $token): array {
        try {
            $row = $this->db->fetch(
                "SELECT * FROM email_verification_tokens WHERE token = ? AND used_at IS NULL AND expires_at > ? LIMIT 1",
                [$token, date('Y-m-d H:i:s')]
            );
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'Verification system unavailable.'];
        }
        if (!$row) {
            return ['success' => false, 'error' => 'Invalid or expired verification link.'];
        }
        $this->db->update('email_verification_tokens', ['used_at' => date('Y-m-d H:i:s')], 'id = ?', [$row['id']]);
        $this->db->update('users', ['email_verified_at' => date('Y-m-d H:i:s')], 'id = ?', [$row['user_id']]);
        return ['success' => true, 'user_id' => (int)$row['user_id']];
    }

    // ─── Google OAuth ─────────────────────────────────────────────────────────

    public function findOrCreateGoogleUser(array $googleUser): array {
        $googleId = $googleUser['id'] ?? '';
        $email    = $googleUser['email'] ?? '';
        $name     = $googleUser['name'] ?? '';

        if (!$email) return ['success' => false, 'error' => 'No email returned from Google.'];

        $user = $this->db->fetch("SELECT * FROM users WHERE google_id = ? LIMIT 1", [$googleId]);

        if (!$user) {
            $user = $this->db->fetch("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1", [$email]);
            if ($user) {
                $this->db->update('users', ['google_id' => $googleId], 'id = ?', [$user['id']]);
            }
        }

        if (!$user) {
            $username = $this->generateUniqueUsername($email);
            $userId   = $this->db->insert('users', [
                'username'          => $username,
                'email'             => $email,
                'name'              => $name ?: $username,
                'password'          => Security::hashPassword(bin2hex(random_bytes(32))),
                'google_id'         => $googleId,
                'has_password'      => 0,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'status'            => 'active',
                'created_at'        => date('Y-m-d H:i:s'),
            ]);
            $defaultRole = $this->db->fetch("SELECT id FROM roles WHERE slug = ?", [DEFAULT_ROLE]);
            if ($defaultRole) {
                $this->db->insert('user_roles', ['user_id' => $userId, 'role_id' => $defaultRole['id']]);
            }
            $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        }

        if (!$user || $user['status'] !== 'active') {
            return ['success' => false, 'error' => 'Your account is not active.'];
        }

        $this->startSession($user);
        return ['success' => true, 'user' => $user];
    }

    private function generateUniqueUsername(string $email): string {
        $base = preg_replace('/[^a-z0-9_]/', '', strtolower(explode('@', $email)[0]));
        $base = $base ?: 'user';
        $candidate = $base;
        $i = 1;
        while ($this->db->exists('users', 'username = ?', [$candidate])) {
            $candidate = $base . $i++;
        }
        return $candidate;
    }

    // ─── User Management ──────────────────────────────────────────────────────

    public function getUserRoles(int $userId): array {
        $rows = $this->db->fetchAll(
            "SELECT r.slug FROM roles r JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = ?",
            [$userId]
        );
        return array_column($rows, 'slug');
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): array {
        $user = $this->db->fetch("SELECT password FROM users WHERE id = ?", [$userId]);
        if (!$user || !Security::verifyPassword($currentPassword, $user['password'])) {
            return ['success' => false, 'error' => 'Current password is incorrect.'];
        }
        $errors = Security::validatePassword($newPassword);
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        $this->db->update('users', [
            'password'   => Security::hashPassword($newPassword),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$userId]);
        return ['success' => true];
    }
}
