<?php
/**
 * Media picker AJAX upload endpoint.
 * Handles file uploads via multipart/form-data POST.
 */
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

$allowedMimes = [
    'image/jpeg'  => ['jpg',  'image'],
    'image/png'   => ['png',  'image'],
    'image/gif'   => ['gif',  'image'],
    'image/webp'  => ['webp', 'image'],
    'image/svg+xml' => ['svg', 'image'],
];

$uploaded = [];
$errors   = [];

$files = $_FILES['files'] ?? null;
if (!$files) {
    echo json_encode(['uploaded' => 0, 'errors' => ['No files received']]);
    exit;
}

// Normalize single-file to multi-file format
if (!is_array($files['name'])) {
    $files = array_map(fn($v) => [$v], $files);
}

$count = count($files['name']);
for ($i = 0; $i < $count; $i++) {
    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

    $originalName = basename($files['name'][$i]);
    $tmpPath      = $files['tmp_name'][$i];
    $size         = (int)$files['size'][$i];

    if ($size > 10 * 1024 * 1024) {
        $errors[] = "{$originalName}: exceeds 10 MB limit.";
        continue;
    }

    $mimeType = mime_content_type($tmpPath) ?: '';
    if (!isset($allowedMimes[$mimeType])) {
        $errors[] = "{$originalName}: type not allowed.";
        continue;
    }

    [$ext, $fileType] = $allowedMimes[$mimeType];
    $safeName = preg_replace('/[^a-z0-9\-_]/i', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $safeName = strtolower(substr($safeName, 0, 60));
    $filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safeName . '.' . $ext;
    $filePath = UPLOADS_PATH . '/' . $filename;
    $urlPath  = '/storage/uploads/' . $filename;

    if (!move_uploaded_file($tmpPath, $filePath)) {
        $errors[] = "{$originalName}: could not save file.";
        continue;
    }

    $width = $height = null;
    if ($fileType === 'image' && $ext !== 'svg') {
        $info = @getimagesize($filePath);
        if ($info) { $width = $info[0]; $height = $info[1]; }
    }

    $db->insert('media', [
        'filename'      => $filename,
        'original_name' => $originalName,
        'file_path'     => $filePath,
        'url_path'      => $urlPath,
        'mime_type'     => $mimeType,
        'file_type'     => $fileType,
        'file_size'     => $size,
        'width'         => $width,
        'height'        => $height,
        'folder'        => '',
        'uploader_id'   => $auth->id(),
        'created_at'    => date('Y-m-d H:i:s'),
    ]);
    $uploaded[] = ['url_path' => $urlPath, 'original_name' => $originalName];
}

echo json_encode(['uploaded' => count($uploaded), 'files' => $uploaded, 'errors' => $errors]);
exit;
