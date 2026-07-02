<?php
defined('AWAN') or die('Direct access denied.');

/**
 * Totp — Pure-PHP TOTP (RFC 6238) implementation. No Composer required.
 * Compatible with Google Authenticator, Authy, 1Password, etc.
 */
class Totp {
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const DIGITS       = 6;
    private const PERIOD       = 30; // seconds per window

    // ─── Secret Management ────────────────────────────────────────────────────

    public static function generateSecret(int $length = 16): string {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, 31)];
        }
        return $secret;
    }

    public static function generateBackupCodes(int $count = 8): array {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $raw    = bin2hex(random_bytes(4));
            $codes[] = strtoupper(substr($raw, 0, 4) . '-' . substr($raw, 4));
        }
        return $codes;
    }

    // ─── Code Generation & Verification ──────────────────────────────────────

    public static function getCode(string $secret, ?int $timestamp = null): string {
        $timestamp = $timestamp ?? time();
        $counter   = (int)floor($timestamp / self::PERIOD);
        $key       = self::base32Decode($secret);
        // Pack counter as 8-byte big-endian unsigned int
        $msg  = "\x00\x00\x00\x00" . pack('N', $counter);
        $hash = hash_hmac('sha1', $msg, $key, true);
        // Dynamic truncation
        $offset = ord($hash[19]) & 0x0f;
        $code   = ((ord($hash[$offset])     & 0x7f) << 24)
                | ((ord($hash[$offset + 1]) & 0xff) << 16)
                | ((ord($hash[$offset + 2]) & 0xff) << 8)
                |  (ord($hash[$offset + 3]) & 0xff);
        return str_pad((string)($code % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a code. $window is the number of 30s steps in each direction
     * to accept (handles clock drift). Default ±1 window = ±30 seconds.
     */
    public static function verify(string $secret, string $code, int $window = 1): bool {
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) return false;
        $now = time();
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::getCode($secret, $now + $i * self::PERIOD), $code)) {
                return true;
            }
        }
        return false;
    }

    /** Verify a single-use backup code against a JSON-encoded array in the DB. */
    public static function verifyBackupCode(string $stored, string $input): array {
        $codes = json_decode($stored, true) ?: [];
        $input = strtoupper(trim($input));
        foreach ($codes as $i => $code) {
            if (hash_equals($code, $input)) {
                unset($codes[$i]);
                return ['valid' => true, 'remaining' => array_values($codes)];
            }
        }
        return ['valid' => false, 'remaining' => $codes];
    }

    // ─── QR Code URI ──────────────────────────────────────────────────────────

    /** Return a Google Charts QR image URL for the setup QR code. */
    public static function qrImageUrl(string $secret, string $accountLabel, string $issuer): string {
        $uri  = 'otpauth://totp/' . rawurlencode($issuer . ':' . $accountLabel)
              . '?secret=' . $secret
              . '&issuer=' . rawurlencode($issuer)
              . '&algorithm=SHA1&digits=6&period=30';
        return 'https://chart.googleapis.com/chart?chs=200x200&chld=M%7C0&cht=qr&chl=' . rawurlencode($uri);
    }

    // ─── Base32 ───────────────────────────────────────────────────────────────

    private static function base32Decode(string $input): string {
        $input  = strtoupper(rtrim($input, '='));
        $output = '';
        $buffer = 0;
        $bits   = 0;
        foreach (str_split($input) as $char) {
            $val = strpos(self::BASE32_CHARS, $char);
            if ($val === false) continue;
            $buffer = ($buffer << 5) | $val;
            $bits  += 5;
            if ($bits >= 8) {
                $bits  -= 8;
                $output .= chr(($buffer >> $bits) & 0xff);
            }
        }
        return $output;
    }
}
