<?php
defined('AWAN') or define('AWAN', true);
require_once __DIR__ . '/_bootstrap.php';

// ─── Cron endpoint — must be called with correct secret ──────────────────────
$secret       = $settings->get('cron_secret', '');
$givenSecret  = $_GET['secret'] ?? ($_SERVER['HTTP_X_CRON_SECRET'] ?? '');

if (empty($secret) || !hash_equals($secret, $givenSecret)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json');
$started = microtime(true);
$results = Scheduler::run($db);
$elapsed = round((microtime(true) - $started) * 1000, 1);

$logger = Logger::getInstance($db);
$logger->info("Cron executed: " . count($results) . " task(s)", ['elapsed_ms' => $elapsed]);

echo json_encode([
    'success'    => true,
    'elapsed_ms' => $elapsed,
    'tasks_run'  => count($results),
    'results'    => $results,
    'timestamp'  => date('Y-m-d H:i:s'),
]);
