<?php
defined('AWAN') or die('Direct access denied.');

/**
 * RateLimit — DB-based rate limiter for stateless contexts (API, no sessions).
 * Uses the api_rate_limits table. Falls open on DB error so a DB hiccup
 * never locks out legitimate users.
 */
class RateLimit {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Check if the key is within the allowed rate. Increments the counter.
     * Returns false if the limit has been exceeded; true otherwise.
     */
    public function check(string $key, int $maxAttempts = 5, int $windowSeconds = 900): bool {
        $now = time();
        try {
            $row = $this->db->fetch(
                "SELECT attempts, window_start FROM api_rate_limits WHERE rate_key = ?",
                [$key]
            );

            if (!$row || $now > ((int)$row['window_start'] + $windowSeconds)) {
                // Start fresh window
                if (!$row) {
                    $this->db->query(
                        "INSERT INTO api_rate_limits (rate_key, attempts, window_start) VALUES (?, 1, ?)",
                        [$key, $now]
                    );
                } else {
                    $this->db->query(
                        "UPDATE api_rate_limits SET attempts = 1, window_start = ? WHERE rate_key = ?",
                        [$now, $key]
                    );
                }
                return true;
            }

            if ((int)$row['attempts'] >= $maxAttempts) {
                return false;
            }

            $this->db->query(
                "UPDATE api_rate_limits SET attempts = attempts + 1 WHERE rate_key = ?",
                [$key]
            );
            return true;
        } catch (Throwable $e) {
            return true; // fail-open
        }
    }

    /** Clear (reset) the counter for a key (e.g., after successful auth). */
    public function clear(string $key): void {
        try {
            $this->db->query("DELETE FROM api_rate_limits WHERE rate_key = ?", [$key]);
        } catch (Throwable $e) {}
    }

    /** How many attempts remain before the key is blocked. */
    public function remaining(string $key, int $maxAttempts = 5, int $windowSeconds = 900): int {
        try {
            $row = $this->db->fetch(
                "SELECT attempts, window_start FROM api_rate_limits WHERE rate_key = ?",
                [$key]
            );
            if (!$row || time() > ((int)$row['window_start'] + $windowSeconds)) {
                return $maxAttempts;
            }
            return max(0, $maxAttempts - (int)$row['attempts']);
        } catch (Throwable $e) {
            return $maxAttempts;
        }
    }

    /** Prune stale rate-limit rows older than $windowSeconds. */
    public function prune(int $windowSeconds = 900): void {
        try {
            $this->db->query(
                "DELETE FROM api_rate_limits WHERE window_start < ?",
                [time() - $windowSeconds]
            );
        } catch (Throwable $e) {}
    }
}
