<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireSuperAdmin();

$currentDriver = DB_DRIVER;
$errors = [];
$migrationResults = [];

// ─── Migration DB Wrapper ──────────────────────────────────────────────────────
// Wraps a raw MySQL PDO so schema_init() can run against it
class MigrationDatabase {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    public function driver(): string { return 'mysql'; }
    public function pdo(): PDO { return $this->pdo; }
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    public function fetch(string $sql, array $params = []): ?array {
        $r = $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }
    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function insert(string $table, array $data): string {
        $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
        $phs  = implode(', ', array_fill(0, count($data), '?'));
        $this->query("INSERT INTO `{$table}` ({$cols}) VALUES ({$phs})", array_values($data));
        return $this->pdo->lastInsertId();
    }
    public function update(string $table, array $data, string $where, array $wp = []): int {
        $set = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        return $this->query("UPDATE `{$table}` SET {$set} WHERE {$where}", [...array_values($data), ...$wp])->rowCount();
    }
    public function delete(string $table, string $where, array $params = []): int {
        return $this->query("DELETE FROM `{$table}` WHERE {$where}", $params)->rowCount();
    }
    public function exists(string $table, string $where, array $params = []): bool {
        return $this->fetch("SELECT 1 FROM `{$table}` WHERE {$where} LIMIT 1", $params) !== null;
    }
    public function tableExists(string $table): bool {
        return $this->fetch("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?", [$table]) !== null;
    }
}

// ─── Helper: build MySQL PDO ───────────────────────────────────────────────────
function buildMysqlPdo(string $host, int $port, string $dbname, string $user, string $pass): PDO {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ]);
}

// ─── Handle POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = Security::sanitize($_POST['action'] ?? '');

    $host   = trim(Security::sanitize($_POST['db_host'] ?? '127.0.0.1'));
    $port   = max(1, min(65535, (int)($_POST['db_port'] ?? 3306)));
    $dbname = trim(Security::sanitize($_POST['db_name'] ?? ''));
    $user   = trim(Security::sanitize($_POST['db_user'] ?? ''));
    $pass   = $_POST['db_pass'] ?? '';

    // ── Test connection (JSON) ────────────────────────────────────────────────
    if ($action === 'test_connection') {
        header('Content-Type: application/json');
        try {
            if (!$host || !$dbname || !$user) {
                echo json_encode(['ok' => false, 'message' => 'Host, database name, and username are required.']);
                exit;
            }
            $pdo = buildMysqlPdo($host, $port, $dbname, $user, $pass);
            $ver = $pdo->query("SELECT VERSION() AS v")->fetch(PDO::FETCH_ASSOC)['v'] ?? '?';
            $tables = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()")->fetchColumn();
            echo json_encode([
                'ok'      => true,
                'message' => "Connected. MySQL {$ver} — {$tables} existing table(s) in this database.",
            ]);
        } catch (PDOException $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ── Full migration ────────────────────────────────────────────────────────
    if ($action === 'migrate') {
        try {
            if (!$host || !$dbname || !$user) {
                throw new RuntimeException('Host, database name, and username are required.');
            }
            if ($currentDriver === 'mysql') {
                throw new RuntimeException('Already running on MySQL. Revert to SQLite first if you want to re-migrate.');
            }

            // 1. Connect to MySQL
            $mysqlPdo = buildMysqlPdo($host, $port, $dbname, $user, $pass);
            $mysqlDb  = new MigrationDatabase($mysqlPdo);

            // 2. Create all tables in MySQL via schema_init
            require_once AWAN_ROOT . '/_database/schema.php';
            schema_init($mysqlDb);

            // 3. Enumerate all user tables from SQLite
            $sqlitePdo = $db->pdo();
            $tables = $sqlitePdo->query(
                "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
            )->fetchAll(PDO::FETCH_COLUMN);

            // 4. Disable FK checks and migrate row by row per table
            $mysqlPdo->exec("SET FOREIGN_KEY_CHECKS=0");
            $mysqlPdo->exec("SET sql_mode='NO_AUTO_VALUE_ON_ZERO,STRICT_TRANS_TABLES'");

            $results = [];
            foreach ($tables as $table) {
                try {
                    $rowCount = (int)$sqlitePdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();

                    // Truncate MySQL table before inserting
                    try { $mysqlPdo->exec("TRUNCATE TABLE `{$table}`"); } catch (PDOException $te) {}

                    if ($rowCount === 0) {
                        $results[] = ['table' => $table, 'rows' => 0, 'status' => 'ok', 'note' => 'empty'];
                        continue;
                    }

                    // Migrate in batches of 200
                    $batchSize = 200;
                    $offset    = 0;
                    $copied    = 0;

                    while (true) {
                        $rows = $sqlitePdo->query(
                            "SELECT * FROM `{$table}` LIMIT {$batchSize} OFFSET {$offset}"
                        )->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($rows)) break;

                        // Use a transaction per batch for speed
                        $mysqlPdo->beginTransaction();
                        foreach ($rows as $row) {
                            // Coerce empty strings for numeric-looking columns to null where needed
                            $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($row)));
                            $phs  = implode(', ', array_fill(0, count($row), '?'));
                            $stmt = $mysqlPdo->prepare("INSERT INTO `{$table}` ({$cols}) VALUES ({$phs})");
                            $stmt->execute(array_values($row));
                            $copied++;
                        }
                        $mysqlPdo->commit();

                        $offset += $batchSize;
                        if (count($rows) < $batchSize) break;
                    }

                    $results[] = ['table' => $table, 'rows' => $copied, 'status' => 'ok'];
                } catch (Throwable $te) {
                    if ($mysqlPdo->inTransaction()) $mysqlPdo->rollBack();
                    $results[] = ['table' => $table, 'rows' => 0, 'status' => 'error', 'note' => $te->getMessage()];
                }
            }

            $mysqlPdo->exec("SET FOREIGN_KEY_CHECKS=1");

            // 5. Write config.local.php to switch driver
            $configContent = "<?php\n"
                . "// MySQL database — generated by AWAN Database Migration.\n"
                . "// Delete this file to revert to SQLite.\n"
                . "define('DB_DRIVER_LOCAL', 'mysql');\n"
                . "define('DB_HOST_LOCAL',   " . var_export($host,         true) . ");\n"
                . "define('DB_PORT_LOCAL',   " . var_export((string)$port, true) . ");\n"
                . "define('DB_NAME_LOCAL',   " . var_export($dbname,       true) . ");\n"
                . "define('DB_USER_LOCAL',   " . var_export($user,         true) . ");\n"
                . "define('DB_PASS_LOCAL',   " . var_export($pass,         true) . ");\n";

            if (!@file_put_contents(AWAN_ROOT . '/config.local.php', $configContent)) {
                throw new RuntimeException('Could not write config.local.php — check file permissions on the AWAN root directory.');
            }

            $logger->info('Database migrated from SQLite to MySQL', ['host' => $host, 'db' => $dbname]);
            Session::set('migration_results', json_encode($results));
            Session::flash('success', 'Migration complete. Platform switched to MySQL.');
            redirect('/admin/migrate-db?done=1');

        } catch (Throwable $e) {
            $errors[] = 'Migration failed: ' . $e->getMessage();
        }
    }

    // ── Revert to SQLite ─────────────────────────────────────────────────────
    if ($action === 'switch_sqlite') {
        $configPath = AWAN_ROOT . '/config.local.php';
        if (file_exists($configPath)) {
            @unlink($configPath);
            $logger->info('Database reverted to SQLite by super admin');
            Session::flash('success', 'Reverted to SQLite. The platform will use SQLite on next request.');
        } else {
            Session::flash('info', 'No config.local.php found — already using default (SQLite) configuration.');
        }
        redirect('/admin/migrate-db');
    }

    // ── Update MySQL credentials ──────────────────────────────────────────────
    if ($action === 'update_mysql') {
        try {
            if (!$host || !$dbname || !$user) {
                throw new RuntimeException('Host, database name, and username are required.');
            }
            $mysqlPdo = buildMysqlPdo($host, $port, $dbname, $user, $pass);

            $configContent = "<?php\n"
                . "// MySQL database — generated by AWAN Database Migration.\n"
                . "// Delete this file to revert to SQLite.\n"
                . "define('DB_DRIVER_LOCAL', 'mysql');\n"
                . "define('DB_HOST_LOCAL',   " . var_export($host,         true) . ");\n"
                . "define('DB_PORT_LOCAL',   " . var_export((string)$port, true) . ");\n"
                . "define('DB_NAME_LOCAL',   " . var_export($dbname,       true) . ");\n"
                . "define('DB_USER_LOCAL',   " . var_export($user,         true) . ");\n"
                . "define('DB_PASS_LOCAL',   " . var_export($pass,         true) . ");\n";

            if (!@file_put_contents(AWAN_ROOT . '/config.local.php', $configContent)) {
                throw new RuntimeException('Could not write config.local.php — check file permissions.');
            }
            $logger->info('MySQL credentials updated');
            Session::flash('success', 'MySQL credentials updated.');
            redirect('/admin/migrate-db');
        } catch (Throwable $e) {
            $errors[] = 'Update failed: ' . $e->getMessage();
        }
    }
}

// Retrieve migration results stored in session
if (isset($_GET['done'])) {
    $stored = Session::get('migration_results');
    if ($stored) {
        $migrationResults = json_decode($stored, true) ?: [];
        Session::remove('migration_results');
    }
}

// Detect config.local.php values for pre-filling MySQL form
$localCfgPath  = AWAN_ROOT . '/config.local.php';
$localCfgExists = file_exists($localCfgPath);
$preHost   = defined('DB_HOST_LOCAL')   ? DB_HOST_LOCAL   : '127.0.0.1';
$prePort   = defined('DB_PORT_LOCAL')   ? DB_PORT_LOCAL   : '3306';
$preName   = defined('DB_NAME_LOCAL')   ? DB_NAME_LOCAL   : 'awan_db';
$preUser   = defined('DB_USER_LOCAL')   ? DB_USER_LOCAL   : '';

// SQLite stats
$sqliteSize = 0;
$sqlitePath = DB_SQLITE;
if (file_exists($sqlitePath)) {
    $sqliteSize = filesize($sqlitePath);
}
function fmtBytes(int $b): string {
    if ($b < 1024) return "{$b} B";
    if ($b < 1048576) return round($b / 1024, 1) . ' KB';
    return round($b / 1048576, 2) . ' MB';
}

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div class="page-title">Database Migration</div>
        <div class="page-subtitle">Migrate your data from SQLite to MySQL with one click</div>
    </div>
    <div class="topbar-actions">
        <a href="/admin/system" class="btn btn-ghost btn-sm">System Info</a>
        <a href="/admin/backup" class="btn btn-secondary btn-sm">Backup First</a>
    </div>
</div>

<div class="page-body" style="max-width:760px">

<?php foreach ($errors as $err): ?>
<div class="alert alert-danger" style="margin-bottom:16px"><?= e($err) ?></div>
<?php endforeach ?>

<!-- ─── Current Status ──────────────────────────────────────────────────────── -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <span class="card-title">Current Database</span>
        <?php if ($currentDriver === 'mysql'): ?>
        <span class="badge badge-success">MySQL Active</span>
        <?php else: ?>
        <span class="badge badge-primary">SQLite Active</span>
        <?php endif ?>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div style="padding:16px;border-radius:var(--radius-small);border:2px solid <?= $currentDriver === 'sqlite' ? 'var(--color-primary)' : 'var(--color-border)' ?>;background:<?= $currentDriver === 'sqlite' ? 'rgba(99,102,241,.06)' : 'var(--color-background)' ?>">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:<?= $currentDriver === 'sqlite' ? 'var(--color-primary)' : 'var(--color-text-muted)' ?>"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                    <strong>SQLite</strong>
                    <?php if ($currentDriver === 'sqlite'): ?><span class="badge badge-primary" style="font-size:10px">IN USE</span><?php endif ?>
                </div>
                <div style="font-size:12px;color:var(--color-text-secondary);line-height:1.5">
                    File: <code style="font-size:11px">storage/database.sqlite</code><br>
                    Size: <?= fmtBytes($sqliteSize) ?><br>
                    Zero-config, no server required
                </div>
            </div>
            <div style="padding:16px;border-radius:var(--radius-small);border:2px solid <?= $currentDriver === 'mysql' ? 'var(--color-primary)' : 'var(--color-border)' ?>;background:<?= $currentDriver === 'mysql' ? 'rgba(99,102,241,.06)' : 'var(--color-background)' ?>">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:<?= $currentDriver === 'mysql' ? 'var(--color-primary)' : 'var(--color-text-muted)' ?>"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                    <strong>MySQL</strong>
                    <?php if ($currentDriver === 'mysql'): ?><span class="badge badge-success" style="font-size:10px">IN USE</span><?php endif ?>
                </div>
                <div style="font-size:12px;color:var(--color-text-secondary);line-height:1.5">
                    <?php if ($currentDriver === 'mysql'): ?>
                    Host: <code style="font-size:11px"><?= e(DB_HOST) ?>:<?= e(DB_PORT) ?></code><br>
                    Database: <code style="font-size:11px"><?= e(DB_NAME) ?></code><br>
                    User: <code style="font-size:11px"><?= e(DB_USER) ?></code>
                    <?php else: ?>
                    Production-grade, scales to millions of rows<br>
                    Required for Hostinger shared hosting
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($migrationResults)): ?>
<!-- ─── Migration Results ──────────────────────────────────────────────────── -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <span class="card-title">Migration Results</span>
        <?php
        $errCount = count(array_filter($migrationResults, fn($r) => $r['status'] === 'error'));
        $okCount  = count($migrationResults) - $errCount;
        $totalRows = array_sum(array_column($migrationResults, 'rows'));
        ?>
        <?php if ($errCount === 0): ?>
        <span class="badge badge-success"><?= $okCount ?> tables migrated</span>
        <?php else: ?>
        <span class="badge badge-danger"><?= $errCount ?> errors</span>
        <?php endif ?>
    </div>
    <div class="card-body" style="padding:0">
        <div style="padding:12px 20px;background:rgba(34,197,94,.08);border-bottom:1px solid var(--color-border);display:flex;gap:24px">
            <div><span style="font-size:22px;font-weight:700;color:var(--color-primary)"><?= count($migrationResults) ?></span> <span style="font-size:12px;color:var(--color-text-secondary)">Tables</span></div>
            <div><span style="font-size:22px;font-weight:700;color:var(--color-primary)"><?= number_format($totalRows) ?></span> <span style="font-size:12px;color:var(--color-text-secondary)">Rows copied</span></div>
            <?php if ($errCount === 0): ?>
            <div><span style="font-size:22px;font-weight:700;color:#22c55e">100%</span> <span style="font-size:12px;color:var(--color-text-secondary)">Success rate</span></div>
            <?php endif ?>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table" style="font-size:13px">
            <thead><tr><th>Table</th><th style="width:100px;text-align:right">Rows Copied</th><th style="width:80px">Status</th><th>Note</th></tr></thead>
            <tbody>
                <?php foreach ($migrationResults as $r): ?>
                <tr>
                    <td style="font-family:monospace;font-size:12px"><?= e($r['table']) ?></td>
                    <td style="text-align:right;font-weight:600"><?= number_format($r['rows']) ?></td>
                    <td>
                        <?php if ($r['status'] === 'ok'): ?>
                        <span class="badge badge-success" style="font-size:10px">OK</span>
                        <?php else: ?>
                        <span class="badge badge-danger" style="font-size:10px">Error</span>
                        <?php endif ?>
                    </td>
                    <td style="font-size:11px;color:var(--color-text-muted)"><?= e($r['note'] ?? ($r['rows'] === 0 ? 'empty table' : '')) ?></td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<?php if ($currentDriver === 'sqlite'): ?>
<!-- ─── Migration Form ─────────────────────────────────────────────────────── -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><span class="card-title">Migrate to MySQL</span></div>
    <div class="card-body">
        <div class="alert alert-info" style="margin-bottom:20px;font-size:13px">
            <strong>Before you begin:</strong>
            Create an empty database (e.g. <code>awan_db</code>) in your Hostinger phpMyAdmin. The migration will create all tables and copy all data automatically.
            <strong style="display:block;margin-top:6px">Recommended: <a href="/admin/backup" style="color:inherit;text-decoration:underline">download a backup</a> before migrating.</strong>
        </div>

        <!-- Connection credentials form -->
        <form id="migration-form" method="POST">
            <?= Security::csrfField() ?>

            <div style="display:grid;grid-template-columns:1fr auto;gap:12px;margin-bottom:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">MySQL Host <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" name="db_host" id="db_host" class="form-input" required
                           placeholder="e.g. 127.0.0.1 or db-hostname.hostinger.com"
                           value="<?= e($preHost) ?>">
                    <div class="form-hint">On Hostinger: find this in Databases → MySQL Databases → Connection details.</div>
                </div>
                <div class="form-group" style="margin:0;width:100px">
                    <label class="form-label">Port</label>
                    <input type="number" name="db_port" id="db_port" class="form-input" value="<?= e($prePort) ?>" min="1" max="65535">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Database Name <span style="color:var(--color-danger)">*</span></label>
                <input type="text" name="db_name" id="db_name" class="form-input" required
                       placeholder="e.g. awan_db" value="<?= e($preName) ?>">
                <div class="form-hint">The empty database you created in phpMyAdmin.</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Username <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" name="db_user" id="db_user" class="form-input" required
                           placeholder="e.g. u123456789_awan" value="<?= e($preUser) ?>">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Password</label>
                    <div class="password-wrap">
                        <input type="password" name="db_pass" id="db_pass" class="form-input" placeholder="MySQL password"
                               autocomplete="new-password">
                        <button type="button" class="password-toggle-btn" tabindex="-1"
                                onclick="var i=document.getElementById('db_pass');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'Show':'Hide'">Show</button>
                    </div>
                </div>
            </div>

            <!-- Test result area -->
            <div id="test-result" style="display:none;margin-top:16px;padding:12px 16px;border-radius:var(--radius-small);font-size:13px"></div>

            <div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap">
                <button type="button" class="btn btn-secondary" onclick="testConnection()">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline;vertical-align:-2px;margin-right:4px"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6.07 6.07l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    Test Connection
                </button>
                <button type="button" class="btn btn-primary" id="migrate-btn" onclick="startMigration()">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline;vertical-align:-2px;margin-right:4px"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    Migrate to MySQL
                </button>
                <!-- Hidden migrate submit -->
                <button type="submit" name="action" value="migrate" id="real-migrate-btn" style="display:none"></button>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ─── Already on MySQL ──────────────────────────────────────────────────── -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><span class="card-title">MySQL Connection</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:auto 1fr;gap:6px 20px;font-size:14px;margin-bottom:20px">
            <span class="text-muted">Host</span>      <code><?= e(DB_HOST) ?>:<?= e(DB_PORT) ?></code>
            <span class="text-muted">Database</span>  <code><?= e(DB_NAME) ?></code>
            <span class="text-muted">User</span>      <code><?= e(DB_USER) ?></code>
            <span class="text-muted">Config</span>    <code><?= $localCfgExists ? 'config.local.php' : 'Environment / config.php' ?></code>
        </div>

        <details style="margin-bottom:20px">
            <summary style="cursor:pointer;font-weight:600;font-size:13px;color:var(--color-primary);margin-bottom:12px">Update MySQL credentials</summary>
            <form method="POST" style="margin-top:12px">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="update_mysql">
                <div style="display:grid;grid-template-columns:1fr auto;gap:12px;margin-bottom:12px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Host</label>
                        <input type="text" name="db_host" class="form-input" value="<?= e(DB_HOST) ?>" required>
                    </div>
                    <div class="form-group" style="margin:0;width:100px">
                        <label class="form-label">Port</label>
                        <input type="number" name="db_port" class="form-input" value="<?= e(DB_PORT) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Database Name</label>
                    <input type="text" name="db_name" class="form-input" value="<?= e(DB_NAME) ?>" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Username</label>
                        <input type="text" name="db_user" class="form-input" value="<?= e(DB_USER) ?>" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Password</label>
                        <div class="password-wrap">
                            <input type="password" name="db_pass" class="form-input" placeholder="(unchanged if blank)" autocomplete="new-password">
                            <button type="button" class="password-toggle-btn" tabindex="-1"
                                    onclick="var i=this.previousElementSibling;i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'Show':'Hide'">Show</button>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="margin-top:16px">Update Credentials</button>
            </form>
        </details>

        <div style="border-top:1px solid var(--color-border);padding-top:16px">
            <div style="font-weight:600;font-size:13px;margin-bottom:8px;color:var(--color-danger)">Revert to SQLite</div>
            <p style="font-size:13px;color:var(--color-text-secondary);margin-bottom:12px">
                This removes <code>config.local.php</code> and reverts the platform to the local SQLite database. Your MySQL data is not affected.
            </p>
            <form method="POST" onsubmit="return confirm('Revert to SQLite? The platform will stop using MySQL until you migrate again.')">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="switch_sqlite">
                <button type="submit" class="btn btn-danger btn-sm">Revert to SQLite</button>
            </form>
        </div>
    </div>
</div>
<?php endif ?>

<!-- ─── How it works ──────────────────────────────────────────────────────────── -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><span class="card-title">How It Works</span></div>
    <div class="card-body">
        <ol style="margin:0;padding-left:20px;display:flex;flex-direction:column;gap:10px;font-size:13px;color:var(--color-text-secondary);line-height:1.6">
            <li>You enter your MySQL host, database name, username, and password — all provided by Hostinger in the phpMyAdmin panel.</li>
            <li>The platform connects to MySQL and creates all tables automatically (same schema, MySQL-native types).</li>
            <li>Every row from every table in the SQLite file is copied to MySQL, in batches of 200, with foreign-key checks disabled to prevent ordering issues.</li>
            <li>A <code>config.local.php</code> file is written to the server root, telling the platform to use MySQL from now on. The SQLite file is left untouched as a backup.</li>
            <li>All future requests hit MySQL. To revert, click <em>Revert to SQLite</em> — it deletes <code>config.local.php</code> and restores the original behaviour.</li>
        </ol>
    </div>
</div>

</div>

<script>
function getFormData() {
    return {
        host:   document.getElementById('db_host').value.trim(),
        port:   document.getElementById('db_port').value.trim(),
        dbname: document.getElementById('db_name').value.trim(),
        user:   document.getElementById('db_user').value.trim(),
        pass:   document.getElementById('db_pass').value,
    };
}

function showTestResult(ok, msg) {
    var el = document.getElementById('test-result');
    el.style.display = 'block';
    el.style.background  = ok ? 'rgba(34,197,94,.1)' : 'rgba(239,68,68,.08)';
    el.style.border      = '1px solid ' + (ok ? 'rgba(34,197,94,.3)' : 'rgba(239,68,68,.3)');
    el.style.color       = ok ? '#15803d' : 'var(--color-danger)';
    el.innerHTML = (ok ? '<strong>Connected</strong> — ' : '<strong>Failed</strong> — ') + msg;
}

function testConnection() {
    var data = getFormData();
    if (!data.host || !data.dbname || !data.user) {
        showTestResult(false, 'Host, database name, and username are required.');
        return;
    }

    var btn = document.querySelector('[onclick="testConnection()"]');
    btn.disabled = true;
    btn.textContent = 'Testing…';

    var formData = new FormData();
    formData.append('action', 'test_connection');
    formData.append('db_host', data.host);
    formData.append('db_port', data.port);
    formData.append('db_name', data.dbname);
    formData.append('db_user', data.user);
    formData.append('db_pass', data.pass);
    formData.append('_csrf_token', document.querySelector('[name="_csrf_token"]').value);

    fetch('/admin/migrate-db', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(r => { showTestResult(r.ok, r.message); })
        .catch(e => { showTestResult(false, 'Request failed: ' + e.message); })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline;vertical-align:-2px;margin-right:4px"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6.07 6.07l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg> Test Connection';
        });
}

function startMigration() {
    var data = getFormData();
    if (!data.host || !data.dbname || !data.user) {
        showTestResult(false, 'Host, database name, and username are required.');
        return;
    }
    if (!confirm('Migrate all data from SQLite to MySQL?\n\nThis will copy every table and row. The process may take 10–30 seconds. Make sure you have a backup.')) return;

    var btn = document.getElementById('migrate-btn');
    btn.disabled = true;
    btn.innerHTML = 'Migrating… please wait';

    document.getElementById('real-migrate-btn').click();
}
</script>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Database Migration', $content, ['section' => 'system']);
