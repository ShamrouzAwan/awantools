<?php
defined('AWAN') or die('Direct access denied.');

class Database {
    private static ?Database $instance = null;
    private PDO $pdo;
    private string $driver;

    private function __construct() {
        $this->driver = DB_DRIVER;

        if ($this->driver === 'sqlite') {
            $dsn = 'sqlite:' . DB_SQLITE;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            $this->pdo = new PDO($dsn, null, null, $options);
            $this->pdo->exec('PRAGMA journal_mode=WAL');
            $this->pdo->exec('PRAGMA foreign_keys=ON');
        } else {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        }

        $this->initSchema();
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initSchema(): void {
        $schemaFile = AWAN_ROOT . '/_database/schema.php';
        if (file_exists($schemaFile)) {
            require_once $schemaFile;
            schema_init($this);
        }
    }

    // ─── Query Helpers ────────────────────────────────────────────────────────

    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $table, array $data): string {
        $cols         = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $this->query("INSERT INTO {$table} ({$cols}) VALUES ({$placeholders})", array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $set = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        $stmt = $this->query("UPDATE {$table} SET {$set} WHERE {$where}", [...array_values($data), ...$whereParams]);
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int {
        return $this->query("DELETE FROM {$table} WHERE {$where}", $params)->rowCount();
    }

    public function exists(string $table, string $where, array $params = []): bool {
        $row = $this->fetch("SELECT 1 FROM {$table} WHERE {$where} LIMIT 1", $params);
        return $row !== null;
    }

    public function count(string $table, string $where = '1=1', array $params = []): int {
        $row = $this->fetch("SELECT COUNT(*) as n FROM {$table} WHERE {$where}", $params);
        return (int)($row['n'] ?? 0);
    }

    public function beginTransaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void           { $this->pdo->commit(); }
    public function rollback(): void         { $this->pdo->rollBack(); }

    public function driver(): string { return $this->driver; }
    public function pdo(): PDO { return $this->pdo; }

    public function tableExists(string $table): bool {
        if ($this->driver === 'sqlite') {
            $row = $this->fetch("SELECT name FROM sqlite_master WHERE type='table' AND name=?", [$table]);
        } else {
            $row = $this->fetch("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?", [DB_NAME, $table]);
        }
        return $row !== null;
    }
}
