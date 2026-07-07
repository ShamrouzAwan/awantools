<?php
// AWAN Platform Bootstrap — loaded before every request
defined('AWAN') or define('AWAN', true);
define('AWAN_START', microtime(true));

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_core/Database.php';
require_once __DIR__ . '/_core/Session.php';
require_once __DIR__ . '/_core/Security.php';
require_once __DIR__ . '/_core/Logger.php';
require_once __DIR__ . '/_core/Settings.php';
require_once __DIR__ . '/_core/Auth.php';
require_once __DIR__ . '/_core/Totp.php';
require_once __DIR__ . '/_core/Theme.php';
require_once __DIR__ . '/_core/Plugin.php';
require_once __DIR__ . '/_core/Lang.php';
require_once __DIR__ . '/_core/Mailer.php';
require_once __DIR__ . '/_core/Scheduler.php';
require_once __DIR__ . '/_core/Seo.php';
require_once __DIR__ . '/_core/Shortcode.php';

// Ensure storage directories exist (before DB init so schema can write version file)
foreach ([STORAGE_PATH, LOGS_PATH, UPLOADS_PATH, STORAGE_PATH . '/cache', STORAGE_PATH . '/backups'] as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

// Initialize database (runs schema on first boot or schema change)
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    if (AWAN_DEBUG) {
        die('<pre style="padding:2rem;font-family:monospace">Database Error: ' . htmlspecialchars($e->getMessage()) . '</pre>');
    }
    die('A database error occurred. Please contact support.');
}

// Start session
Session::start();

// Load settings
$settings = Settings::getInstance($db);

// Initialize auth
$auth = Auth::getInstance($db, $settings);

// Initialize theme
$theme = Theme::getInstance($db, $settings);

// Initialize i18n
$lang = Lang::getInstance($settings->get('language', 'en'));

// Initialize mailer
$mailer = Mailer::getInstance($settings);

// Initialize SEO
$seo = Seo::getInstance($settings);

// Initialize logger
$logger = Logger::getInstance($db);

// Register default shortcodes (site_name, year, contact_link, etc.)
Shortcode::registerDefaults();

// Error handling based on environment
if (AWAN_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Timezone
date_default_timezone_set($settings->get('timezone', 'UTC'));

// ─── Security Headers ─────────────────────────────────────────────────────────
// Sent early but after settings are loaded so CSP can be customised
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Allow camera only on the scannable-codes plugin page (needed for QR/barcode scanner).
    // header_remove() clears any server-level Permissions-Policy set by Apache/hosting before
    // we send our own, preventing duplicate/conflicting headers.
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $isScannableCodes = ($requestPath === '/plugins/scannable-codes'
        || strpos($requestPath, '/plugins/scannable-codes/') === 0);
    $cameraPolicy = $isScannableCodes ? 'camera=(self)' : 'camera=()';
    header_remove('Permissions-Policy');
    header('Permissions-Policy: ' . $cameraPolicy . ', microphone=(), geolocation=()');
    // CSP — allows CDNs required by admin editors (Monaco, TinyMCE, Quill)
    // unsafe-eval is required by Monaco Editor's language services
    // unsafe-inline is required by inline admin scripts (cannot be nonce'd easily)
    // header_remove() clears any host-injected CSP first; dual CSP headers cause browsers
    // to enforce the intersection (most restrictive), which blocks cdnjs fonts/icons.
    header_remove('Content-Security-Policy');
    header(
        "Content-Security-Policy: "
        . "default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' 'unsafe-eval' "
        .     "cdn.jsdelivr.net cdn.quilljs.com cdn.tiny.cloud cdnjs.cloudflare.com "
        .     "chart.googleapis.com www.google.com www.gstatic.com "
        .     "www.googletagmanager.com www.clarity.ms pagead2.googlesyndication.com "
        .     "googleads.g.doubleclick.net; "
        . "style-src 'self' 'unsafe-inline' "
        .     "cdn.jsdelivr.net cdn.quilljs.com cdn.tiny.cloud cdnjs.cloudflare.com; "
        . "img-src 'self' data: blob: https:; "
        . "font-src 'self' data: cdn.jsdelivr.net cdnjs.cloudflare.com; "
        . "worker-src 'self' blob: data:; "
        . "connect-src 'self' https: wss:; "
        . "frame-src 'self' www.google.com www.googletagmanager.com; "
        . "object-src 'none'; "
        . "base-uri 'self';"
    );
}

// ─── Register Built-in Scheduler Tasks ────────────────────────────────────────
// These tasks are seeded into the scheduler once per boot if they don't exist.
// Each task runs on its own interval and is executed by the scheduler cron endpoint.
(function () use ($db) {
    if (!class_exists('Scheduler')) return;
    try {
        $builtinTasks = [
            [
                'slug'             => 'core_email_queue',
                'name'             => 'Process Email Queue',
                'description'      => 'Delivers pending emails from the async email queue.',
                'interval_seconds' => 300,   // every 5 minutes
            ],
            [
                'slug'             => 'core_log_cleanup',
                'name'             => 'Log Cleanup',
                'description'      => 'Removes old log entries beyond the retention period.',
                'interval_seconds' => 86400, // daily
            ],
            [
                'slug'             => 'core_notification_cleanup',
                'name'             => 'Notification Cleanup',
                'description'      => 'Removes expired and old read notifications.',
                'interval_seconds' => 86400, // daily
            ],
            [
                'slug'             => 'core_analytics_prune',
                'name'             => 'Analytics Pruning',
                'description'      => 'Removes analytics events older than the retention period.',
                'interval_seconds' => 604800, // weekly
            ],
            [
                'slug'             => 'core_backup_cleanup',
                'name'             => 'Backup Cleanup',
                'description'      => 'Removes database backups older than the retention period.',
                'interval_seconds' => 604800, // weekly
            ],
        ];
        foreach ($builtinTasks as $task) {
            if (!$db->exists('scheduled_tasks', 'slug = ?', [$task['slug']])) {
                $db->insert('scheduled_tasks', array_merge($task, [
                    'status'     => 'idle',
                    'run_count'  => 0,
                    'next_run'   => date('Y-m-d H:i:s', time() + ($task['interval_seconds'] ?? 3600)),
                    'created_at' => date('Y-m-d H:i:s'),
                ]));
            }
        }
    } catch (Throwable $e) {
        // Scheduler tasks table may not exist yet on first boot — schema will create it
    }
})();

// ─── Safe Plugin Loading ───────────────────────────────────────────────────────
// Active plugins may optionally expose a _bootstrap.php file that runs on every
// request. We load these with full try/catch isolation so a broken plugin can
// never crash the entire platform. Any plugin that throws is silently deactivated
// in the DB so subsequent requests skip it cleanly.
(function () use ($db, $settings) {
    if (!defined('PLUGINS_PATH') || !is_dir(PLUGINS_PATH)) return;
    try {
        $activePlugins = $db->fetchAll(
            "SELECT slug FROM plugins WHERE status = 'active' ORDER BY name ASC"
        ) ?: [];
    } catch (Throwable $e) {
        return;
    }
    foreach ($activePlugins as $row) {
        $slug       = $row['slug'] ?? '';
        $hookFile   = PLUGINS_PATH . '/' . $slug . '/_bootstrap.php';
        if (!$slug || !file_exists($hookFile)) continue;
        try {
            require_once $hookFile;
        } catch (Throwable $e) {
            // Deactivate the broken plugin so it doesn't keep crashing the platform
            try {
                $db->update('plugins', ['status' => 'inactive'], 'slug = ?', [$slug]);
            } catch (Throwable $dbErr) {}
        }
    }
})();

// ─── Helper Functions ─────────────────────────────────────────────────────────

function redirect(string $url, int $code = 302): void {
    header("Location: $url", true, $code);
    exit;
}

function requireLogin(string $redirect = ''): void {
    global $auth;
    if (!$auth->check()) {
        $back = $redirect ?: $_SERVER['REQUEST_URI'];
        redirect('/login?next=' . urlencode($back));
    }
}

function requireAdmin(): void {
    global $auth;
    if (!$auth->check()) {
        redirect('/login?next=' . urlencode($_SERVER['REQUEST_URI']));
    }
    if (!$auth->hasRole(ADMIN_ROLE) && !$auth->hasRole(SUPER_ROLE)) {
        http_response_code(403);
        die(renderError(403, 'Access Denied', 'You do not have permission to access this page.'));
    }
}

function requireSuperAdmin(): void {
    global $auth;
    if (!$auth->check()) {
        redirect('/login?next=' . urlencode($_SERVER['REQUEST_URI']));
    }
    if (!$auth->hasRole(SUPER_ROLE)) {
        http_response_code(403);
        die(renderError(403, 'Access Denied', 'Super admin access required.'));
    }
}

function renderError(int $code, string $title, string $message): string {
    return "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>{$code} {$title}</title>"
         . "<link rel='stylesheet' href='/assets/css/awan.css'></head>"
         . "<body class='page-error'><div class='error-wrap'>"
         . "<div class='error-code'>{$code}</div><h1>" . htmlspecialchars($title) . "</h1>"
         . "<p>" . htmlspecialchars($message) . "</p>"
         . "<a href='/' class='btn btn-primary'>Go Home</a>"
         . "</div></body></html>";
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/** HTML-escape a string for output. */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/** Format a datetime string. */
function fdate(string $datetime, string $format = 'M j, Y'): string {
    return (new DateTime($datetime))->format($format);
}

/** Return absolute site URL; auto-detects scheme+host when site_url is blank. */
function siteUrl(string $path = ''): string {
    global $settings;
    $base = rtrim($settings->get('site_url', ''), '/');
    if (!$base) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base   = $scheme . '://' . $host;
    }
    return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
}

/**
 * Bot/crawler detection based on User-Agent string.
 * Used to filter analytics events so bots don't inflate counts.
 */
function isBot(): bool {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (empty($ua)) return true;
    $patterns = [
        'bot', 'spider', 'crawl', 'slurp', 'wget', 'curl/', 'python-requests',
        'scrapy', 'java/', 'libwww', 'httpunit', 'nutch', 'go-http-client',
        'phpcrawl', 'archive.org', 'heritrix', 'yandexbot', 'baiduspider',
        'ahrefsbot', 'semrushbot', 'dotbot', 'mj12bot', 'petalbot',
        'facebookexternalhit', 'linkedinbot', 'twitterbot', 'whatsapp',
        'applebot', 'googlebot', 'bingbot', 'duckduckbot',
    ];
    foreach ($patterns as $p) {
        if (str_contains($ua, $p)) return true;
    }
    return false;
}
