<?php
defined('AWAN') or die('Direct access denied.');

class Logger {
    private static ?Logger $instance = null;
    private Database $db;
    private string $logFile;

    private function __construct(Database $db) {
        $this->db = $db;
        $this->logFile = LOGS_PATH . '/awan-' . date('Y-m-d') . '.log';
    }

    public static function getInstance(Database $db): Logger {
        if (self::$instance === null) {
            self::$instance = new self($db);
        }
        return self::$instance;
    }

    // ─── Core Log Method ──────────────────────────────────────────────────────

    public function log(string $level, string $message, array $context = [], ?int $userId = null): void {
        $entry = [
            'level'      => $level,
            'message'    => $message,
            'context'    => !empty($context) ? json_encode($context) : null,
            'user_id'    => $userId ?? Session::get('user_id'),
            'ip'         => $this->getIp(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'url'        => substr($_SERVER['REQUEST_URI'] ?? '', 0, 500),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Write to database
        try {
            $this->db->insert('logs', $entry);
        } catch (Exception $e) {
            // DB failed, fall through to file logging
        }

        // Write to file
        $line = sprintf("[%s] [%s] %s %s\n",
            $entry['created_at'],
            strtoupper($level),
            $message,
            $entry['context'] ?? ''
        );
        @file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }

    public function info(string $message, array $context = [], ?int $userId = null): void  { $this->log('info',  $message, $context, $userId); }
    public function warn(string $message, array $context = [], ?int $userId = null): void  { $this->log('warn',  $message, $context, $userId); }
    public function error(string $message, array $context = [], ?int $userId = null): void { $this->log('error', $message, $context, $userId); }
    public function debug(string $message, array $context = []): void {
        if (AWAN_DEBUG) $this->log('debug', $message, $context);
    }

    public function auth(string $action, ?int $userId = null, array $context = []): void {
        $this->log('auth', $action, $context, $userId);
    }

    // ─── Retrieval ────────────────────────────────────────────────────────────

    public function getLogs(int $limit = 100, int $offset = 0, string $level = ''): array {
        if ($level) {
            return $this->db->fetchAll(
                "SELECT * FROM logs WHERE level = ? ORDER BY id DESC LIMIT ? OFFSET ?",
                [$level, $limit, $offset]
            );
        }
        return $this->db->fetchAll(
            "SELECT * FROM logs ORDER BY id DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    public function countLogs(string $level = ''): int {
        return $this->db->count('logs', $level ? 'level = ?' : '1=1', $level ? [$level] : []);
    }

    // Delete logs older than $daysOld days. Returns number of deleted rows.
    public function clearOldLogs(int $daysOld = 30): int {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
        $count  = $this->db->delete('logs', 'created_at < ?', [$cutoff]);
        $this->clearOldLogFiles($daysOld);
        return $count;
    }

    // Delete ALL logs (DB + all log files).
    public function clearAllLogs(): int {
        $count = $this->db->delete('logs', '1=1');
        // Remove all log files
        foreach (glob(LOGS_PATH . '/*.log') ?: [] as $file) {
            @unlink($file);
        }
        return $count;
    }

    // Remove log files older than $daysOld days.
    private function clearOldLogFiles(int $daysOld): void {
        $cutoffTs = strtotime("-{$daysOld} days");
        foreach (glob(LOGS_PATH . '/*.log') ?: [] as $file) {
            if (filemtime($file) < $cutoffTs) {
                @unlink($file);
            }
        }
    }

    private function getIp(): string {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                return explode(',', $_SERVER[$key])[0];
            }
        }
        return '0.0.0.0';
    }
}
