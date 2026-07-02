<?php
/**
 * Email Open & Click Tracking Endpoint
 * Routes: /email-track/open/{token}  — returns 1x1 pixel GIF, records open
 *         /email-track/click/{token} — records click, redirects to ?url=
 */
define('AWAN', true);
require_once __DIR__ . '/_bootstrap.php';

$uri    = $_SERVER['REQUEST_URI'] ?? '';
$path   = parse_url($uri, PHP_URL_PATH);

// Extract action and token from path: /email-track/{action}/{token}
if (!preg_match('#^/email-track/(open|click)/([a-f0-9]{32})$#i', $path, $m)) {
    http_response_code(404);
    exit;
}

$action = $m[1];
$token  = $m[2];

// Record in DB — silently ignore errors
try {
    if ($action === 'open') {
        $db->query(
            "UPDATE email_logs SET
                opened_at   = COALESCE(opened_at, ?),
                open_count  = open_count + 1
             WHERE tracking_token = ?",
            [date('Y-m-d H:i:s'), $token]
        );
    } elseif ($action === 'click') {
        $db->query(
            "UPDATE email_logs SET
                clicked_at  = COALESCE(clicked_at, ?),
                click_count = click_count + 1
             WHERE tracking_token = ?",
            [date('Y-m-d H:i:s'), $token]
        );
    }
} catch (Throwable $e) {
    // Fail silently — tracking should never break email delivery
}

if ($action === 'open') {
    // Return a 1x1 transparent GIF
    $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    header('Content-Type: image/gif');
    header('Content-Length: ' . strlen($gif));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo $gif;
    exit;
}

if ($action === 'click') {
    $url = $_GET['url'] ?? '';
    // Validate URL — only allow http/https, prevent open-redirect abuse
    if ($url && preg_match('#^https?://#i', $url)) {
        header('Location: ' . $url, true, 302);
    } else {
        header('Location: ' . siteUrl('/'), true, 302);
    }
    exit;
}

http_response_code(404);
exit;
