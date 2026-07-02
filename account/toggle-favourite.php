<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';

header('Content-Type: application/json');

if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated', 'logged_in' => false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

Security::verifyCsrf();

$pluginId = (int)($_POST['plugin_id'] ?? 0);
if (!$pluginId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid plugin_id']);
    exit;
}

// Verify plugin exists and is active
try {
    $plugin = $db->fetch("SELECT id, name FROM plugins WHERE id = ? AND status = 'active'", [$pluginId]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
}

if (!$plugin) {
    http_response_code(404);
    echo json_encode(['error' => 'Plugin not found']);
    exit;
}

try {
    $existing = $db->fetch(
        "SELECT id FROM user_favourites WHERE user_id = ? AND plugin_id = ?",
        [$auth->id(), $pluginId]
    );

    if ($existing) {
        $db->query("DELETE FROM user_favourites WHERE user_id = ? AND plugin_id = ?", [$auth->id(), $pluginId]);
        echo json_encode(['favourited' => false, 'message' => 'Removed from favourites']);
    } else {
        $db->insert('user_favourites', [
            'user_id'    => $auth->id(),
            'plugin_id'  => $pluginId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        echo json_encode(['favourited' => true, 'message' => 'Added to favourites']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update favourite']);
}
