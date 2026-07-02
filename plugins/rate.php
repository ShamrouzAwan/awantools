<?php
defined('AWAN') or die();
// This file is routed via _router.php as POST /plugins/rate
require_once __DIR__ . '/../_bootstrap.php';

header('Content-Type: application/json');

if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Login required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

Security::verifyCsrf();

$pluginId = (int)($_POST['plugin_id'] ?? 0);
$rating   = (int)($_POST['rating'] ?? 0);

if ($pluginId <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid rating (1-5) or plugin ID']);
    exit;
}

$plugin = $db->fetch("SELECT id, name FROM plugins WHERE id = ? AND status = 'active'", [$pluginId]);
if (!$plugin) {
    http_response_code(404);
    echo json_encode(['error' => 'Plugin not found']);
    exit;
}

try {
    $existing = $db->fetch(
        "SELECT id FROM plugin_ratings WHERE plugin_id = ? AND user_id = ?",
        [$pluginId, $auth->id()]
    );
    if ($existing) {
        $db->update('plugin_ratings', ['rating' => $rating, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$existing['id']]);
        $action = 'updated';
    } else {
        $db->insert('plugin_ratings', [
            'plugin_id'  => $pluginId,
            'user_id'    => $auth->id(),
            'rating'     => $rating,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $action = 'created';
    }
    // Fetch updated average
    $avg = $db->fetch(
        "SELECT ROUND(AVG(rating),1) AS avg, COUNT(*) AS cnt FROM plugin_ratings WHERE plugin_id = ?",
        [$pluginId]
    );
    echo json_encode(['ok' => true, 'action' => $action, 'avg' => (float)($avg['avg'] ?? 0), 'count' => (int)($avg['cnt'] ?? 0)]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
