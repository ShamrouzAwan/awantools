<?php
/**
 * Media picker AJAX endpoint — returns image list as JSON.
 * Restricted to logged-in admins; no CSRF needed (GET only).
 */
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$search = Security::sanitize($_GET['q'] ?? '');
$type   = 'image';
$where  = "file_type = 'image'";
$params = [];
if ($search) {
    $where .= ' AND (original_name LIKE ? OR alt_text LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$items = $db->fetchAll(
    "SELECT id, filename, original_name, url_path, alt_text, width, height, file_size, created_at
     FROM media WHERE {$where} ORDER BY created_at DESC LIMIT 200",
    $params
);

echo json_encode(['items' => $items ?: []], JSON_UNESCAPED_SLASHES);
exit;
