<?php
/**
 * AWAN Platform REST API v1
 * Hardened: DB-rate-limiting, origin-restricted CORS, user-token verification,
 * newsletter honeypot, health endpoint, email-queue cron, deprecation headers.
 */
defined('AWAN') or define('AWAN', true);
require_once __DIR__ . '/../_bootstrap.php';
require_once AWAN_ROOT . '/_core/RateLimit.php';

// ─── Helpers ──────────────────────────────────────────────────────────────────
function apiOk(mixed $data, int $code = 200, array $meta = []): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_filter(['success' => true, 'data' => $data] + $meta, fn($v) => $v !== null));
    exit;
}

function apiError(string $message, int $code = 400, array $extra = []): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => false, 'error' => $message, 'code' => $code], $extra));
    exit;
}

/**
 * Set CORS headers.
 * $restricted=true → only allow origins listed in api_allowed_origins setting.
 * $restricted=false → allow * (suitable for fully public endpoints).
 */
function setCors(bool $restricted = false): void {
    global $settings;
    $origin     = $_SERVER['HTTP_ORIGIN'] ?? '';
    $configured = array_filter(array_map('trim', explode(',', $settings->get('api_allowed_origins', ''))));

    if ($restricted && !empty($configured)) {
        if (in_array($origin, $configured, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Vary: Origin');
        }
        // No wildcard for authenticated routes when origins are configured
    } else {
        header('Access-Control-Allow-Origin: *');
    }
}

// ─── CORS preflight ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    setCors(false);
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Api-Key, X-Cron-Secret');
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    exit;
}

// ─── Parse route ──────────────────────────────────────────────────────────────
$rawPath  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$apiPath  = preg_replace('#^/api/?#', '', $rawPath);
$segments = array_values(array_filter(explode('/', $apiPath)));
if (isset($segments[0]) && preg_match('/^v\d+$/', $segments[0])) {
    array_shift($segments);
}
$method   = $_SERVER['REQUEST_METHOD'];
$resource = $segments[0] ?? '';
$id       = $segments[1] ?? '';
$sub      = $segments[2] ?? '';

// ─── API key resolution ───────────────────────────────────────────────────────
$validKey = $settings->get('api_key', '');
$givenKey = '';

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (str_starts_with($authHeader, 'Bearer ')) {
    $givenKey = substr($authHeader, 7);
}
if (empty($givenKey)) {
    $givenKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
}
// Legacy URL param — accepted but deprecated
if (empty($givenKey) && !empty($_GET['api_key'])) {
    $givenKey = $_GET['api_key'];
    header('X-Deprecated: The api_key URL parameter leaks keys in logs. Use Authorization: Bearer {key} instead.');
    header('X-Sunset: 2027-01-01');
}

$isAuthenticated = !empty($validKey) && !empty($givenKey) && hash_equals(trim($validKey), trim($givenKey));

// ─── DB-backed rate limiter ───────────────────────────────────────────────────
$rl = new RateLimit($db);

// ─── Health check (public, no key) ───────────────────────────────────────────
if ($resource === 'health') {
    setCors(false);
    header('Cache-Control: no-store');
    $dbOk = false;
    try { $db->count('users'); $dbOk = true; } catch (Throwable $e) {}
    $ok = $dbOk;
    http_response_code($ok ? 200 : 503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'    => $ok ? 'ok' : 'degraded',
        'db'        => $dbOk ? 'ok' : 'error',
        'version'   => AWAN_VERSION,
        'timestamp' => date('c'),
    ]);
    exit;
}

// ─── API index ────────────────────────────────────────────────────────────────
if ($resource === '') {
    setCors(false);
    apiOk([
        'platform' => 'AWAN Platform',
        'version'  => AWAN_VERSION,
        'endpoints' => [
            'GET  /api/health'                    => 'Health check (public)',
            'GET  /api/v1/pages'                  => 'List published pages (public)',
            'GET  /api/v1/pages/{slug}'           => 'Get page by slug (public)',
            'GET  /api/v1/users/{username}'       => 'Public user profile (public)',
            'POST /api/v1/auth/login'             => 'Authenticate, get token (rate-limited)',
            'GET  /api/v1/auth/me'                => 'Current user info (user token)',
            'POST /api/v1/newsletter'             => 'Newsletter subscribe (rate-limited)',
            'GET  /api/v1/media'                  => 'List media [key required]',
            'GET  /api/v1/analytics'              => 'Analytics summary [key required]',
            'GET  /api/v1/plugins'                => 'List active plugins [key required]',
            'GET  /api/v1/settings'               => 'Public settings [key required]',
            'POST /api/v1/cron/process-emails'    => 'Process email queue [key + cron secret]',
        ],
        'rate_limits' => [
            'login'      => '10 attempts / 15 min / IP',
            'newsletter' => '3 attempts / hour / IP',
        ],
    ]);
}

// ─── Search (public, no key required) ─────────────────────────────────────────
if ($resource === 'search' && $method === 'GET') {
    setCors(false);
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) apiError('Search query must be at least 2 characters.', 422);

    $like = '%' . $q . '%';

    // Search active plugins by name + description
    $plugins = $db->fetchAll(
        "SELECT slug, name, description, version, manifest FROM plugins
         WHERE status = 'active' AND (name LIKE ? OR description LIKE ?)
         ORDER BY name ASC LIMIT 8",
        [$like, $like]
    ) ?: [];

    // Also match plugins by keywords stored in manifest JSON
    $matchedSlugs = array_column($plugins, 'slug');
    $allPlugins   = $db->fetchAll("SELECT slug, name, description, version, manifest FROM plugins WHERE status = 'active'") ?: [];
    foreach ($allPlugins as $p) {
        if (in_array($p['slug'], $matchedSlugs, true)) continue;
        $m  = json_decode($p['manifest'] ?? '{}', true) ?? [];
        $kw = strtolower(implode(' ', $m['keywords'] ?? []));
        if (stripos($kw, $q) !== false) {
            $plugins[]      = $p;
            $matchedSlugs[] = $p['slug'];
        }
    }
    // Strip manifest from response
    $plugins = array_map(function($p) {
        unset($p['manifest']);
        return $p;
    }, array_slice($plugins, 0, 10));

    // Search published blog posts
    $posts = [];
    try {
        $posts = $db->fetchAll(
            "SELECT id, title, slug, excerpt, published_at FROM blog_posts
             WHERE status = 'published' AND (title LIKE ? OR excerpt LIKE ?)
             ORDER BY published_at DESC LIMIT 6",
            [$like, $like]
        ) ?: [];
    } catch (Throwable $e) {}

    apiOk([
        'query'   => $q,
        'plugins' => $plugins,
        'posts'   => $posts,
        'total'   => count($plugins) + count($posts),
    ]);
}

// ─── Pages (public) ───────────────────────────────────────────────────────────
if ($resource === 'pages' && $method === 'GET') {
    setCors(false);
    if ($id) {
        $page = $db->fetch(
            "SELECT id,title,slug,content,seo_title,seo_desc,created_at,updated_at FROM pages WHERE slug=? AND status='published'",
            [$id]
        );
        if (!$page) apiError('Page not found', 404);
        apiOk($page);
    }
    $perPage = min(50, (int)($_GET['per_page'] ?? 20));
    $pg      = max(1, (int)($_GET['page'] ?? 1));
    $total   = $db->count('pages', "status='published'");
    $rows    = $db->fetchAll(
        "SELECT id,title,slug,seo_title,seo_desc,created_at,updated_at
         FROM pages WHERE status='published'
         ORDER BY created_at DESC LIMIT {$perPage} OFFSET " . (($pg - 1) * $perPage)
    );
    apiOk($rows, 200, ['total' => $total, 'page' => $pg, 'per_page' => $perPage, 'pages' => (int)ceil($total / $perPage)]);
}

// ─── Public user profile ──────────────────────────────────────────────────────
if ($resource === 'users' && $method === 'GET' && $id) {
    setCors(false);
    $u = $db->fetch(
        "SELECT id,username,name,bio,avatar,created_at FROM users WHERE username=? AND status='active'",
        [$id]
    );
    if (!$u) apiError('User not found', 404);
    $u['pages_count'] = $db->count('pages', "author_id=? AND status='published'", [$u['id']]);
    apiOk($u);
}

// ─── Auth: login → HMAC token (rate-limited) ─────────────────────────────────
if ($resource === 'auth' && $id === 'login' && $method === 'POST') {
    setCors(false);
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = 'api_login_' . md5($ip);
    if (!$rl->check($key, 10, 900)) {
        header('Retry-After: 900');
        apiError('Too many login attempts. Try again in 15 minutes.', 429, ['retry_after' => 900]);
    }

    $body  = json_decode(file_get_contents('php://input'), true) ?: [];
    $email = trim(strtolower($body['email'] ?? ($_POST['email'] ?? '')));
    $pass  = $body['password'] ?? ($_POST['password'] ?? '');
    if (empty($email) || empty($pass)) apiError('email and password required', 422);

    $user = $db->fetch("SELECT * FROM users WHERE email=? AND status='active'", [$email]);
    if (!$user || !password_verify($pass, $user['password'])) {
        apiError('Invalid credentials', 401);
    }

    $rl->clear($key);

    $expiry  = time() + 3600;
    $payload = $user['id'] . ':' . $expiry;
    $token   = $payload . ':' . hash_hmac('sha256', $payload, APP_KEY);

    apiOk([
        'token'      => $token,
        'token_type' => 'bearer',
        'expires_at' => date('c', $expiry),
        'user'       => [
            'id'       => (int)$user['id'],
            'username' => $user['username'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => $user['avatar'],
        ],
    ]);
}

// ─── Auth: me (user HMAC token verification) ─────────────────────────────────
if ($resource === 'auth' && $id === 'me' && $method === 'GET') {
    setCors(false);
    $bearer = '';
    $ah     = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (str_starts_with($ah, 'Bearer ')) $bearer = substr($ah, 7);

    $parts = explode(':', $bearer);
    if (count($parts) !== 3) apiError('Invalid or missing token', 401);

    [$userId, $expiry, $hmac] = $parts;
    if ((int)$expiry < time()) apiError('Token expired', 401);

    $expected = hash_hmac('sha256', $userId . ':' . $expiry, APP_KEY);
    if (!hash_equals($expected, $hmac)) apiError('Invalid token signature', 401);

    $user = $db->fetch(
        "SELECT id,username,name,email,avatar,bio,status,created_at FROM users WHERE id=? AND status='active'",
        [(int)$userId]
    );
    if (!$user) apiError('User not found', 404);
    apiOk($user);
}

// ─── Newsletter subscribe (public, rate-limited, honeypot) ───────────────────
if ($resource === 'newsletter' && $method === 'POST') {
    setCors(false);
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = 'newsletter_' . md5($ip);
    if (!$rl->check($key, 3, 3600)) {
        header('Retry-After: 3600');
        apiError('Too many subscription attempts. Please try again later.', 429);
    }

    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    // Honeypot: legitimate clients never send 'website'
    if (!empty($body['website'] ?? ($_POST['website'] ?? ''))) {
        // Silent success — bot trap
        apiOk(['message' => 'Thank you for subscribing!'], 201);
    }

    $email = strtolower(trim($body['email'] ?? ($_POST['email'] ?? '')));
    $name  = trim($body['name'] ?? ($_POST['name'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        apiError('A valid email address is required.', 422);
    }

    $existing = $db->fetch("SELECT id, status, unsubscribe_token FROM newsletter_subscribers WHERE email = ?", [$email]);
    if ($existing) {
        if ($existing['status'] === 'active') {
            apiOk(['message' => 'You are already subscribed.']);
        }
        $resubData = ['status' => 'active', 'name' => $name ?: null];
        if (empty($existing['unsubscribe_token'])) {
            $resubData['unsubscribe_token'] = bin2hex(random_bytes(32));
        }
        $db->update('newsletter_subscribers', $resubData, 'id = ?', [$existing['id']]);
        apiOk(['message' => 'Welcome back! You have been re-subscribed.']);
    }

    $unsubToken = bin2hex(random_bytes(32));
    $db->insert('newsletter_subscribers', [
        'email'             => $email,
        'name'              => $name ?: null,
        'status'            => 'active',
        'unsubscribe_token' => $unsubToken,
        'created_at'        => date('Y-m-d H:i:s'),
    ]);

    // Send welcome email
    try {
        $mailer->sendTemplate('newsletter-welcome', $email, [
            'name'  => $name ?: 'Subscriber',
            'email' => $email,
        ]);
    } catch (Throwable $e) {}

    // Dashboard notification for new subscriber
    try {
        require_once AWAN_ROOT . '/_core/Notifications.php';
        Notifications::create($db, 'newsletter', 'New Subscriber', ($name ? htmlspecialchars($name, ENT_QUOTES) . ' (' . htmlspecialchars($email, ENT_QUOTES) . ')' : htmlspecialchars($email, ENT_QUOTES)) . ' joined your newsletter.', '/admin/newsletter');
    } catch (Throwable $e) {}

    apiOk(['message' => 'Thank you for subscribing!'], 201);
}

// ─── API key required beyond this point ──────────────────────────────────────
if (!$isAuthenticated) {
    setCors(false);
    apiError('API key required. Pass "Authorization: Bearer {key}" header.', 401);
}

setCors(true); // restrict CORS for authenticated routes

// ─── Cron: process email queue (key + cron secret) ───────────────────────────
if ($resource === 'cron' && $id === 'process-emails' && $method === 'POST') {
    $cronSecret  = $_SERVER['HTTP_X_CRON_SECRET'] ?? ($_GET['secret'] ?? '');
    $storedSecret = $settings->get('cron_secret', '');
    if (empty($storedSecret) || !hash_equals($storedSecret, $cronSecret)) {
        apiError('Invalid cron secret.', 403);
    }
    $processed = $mailer->processQueue(20);
    apiOk(['processed' => $processed, 'message' => "Processed {$processed} queued email(s)."]);
}

// ─── Media ────────────────────────────────────────────────────────────────────
if ($resource === 'media' && $method === 'GET') {
    $type   = Security::sanitize($_GET['type'] ?? '');
    $where  = '1=1';
    $params = [];
    if ($type && in_array($type, ['image', 'document', 'archive', 'other'])) {
        $where .= ' AND file_type = ?'; $params[] = $type;
    }
    $perPage = min(50, (int)($_GET['per_page'] ?? 20));
    $pg      = max(1, (int)($_GET['page'] ?? 1));
    $total   = $db->count('media', $where, $params);
    $rows    = $db->fetchAll(
        "SELECT id,filename,original_name,url_path,mime_type,file_type,file_size,width,height,folder,alt_text,created_at
         FROM media WHERE {$where} ORDER BY created_at DESC
         LIMIT {$perPage} OFFSET " . (($pg - 1) * $perPage),
        $params
    );
    apiOk($rows, 200, ['total' => $total, 'page' => $pg, 'per_page' => $perPage]);
}

// ─── Analytics ────────────────────────────────────────────────────────────────
if ($resource === 'analytics' && $method === 'GET') {
    $totalViews = $db->count('analytics_events', "event='page_view'");
    $todayViews = $db->count('analytics_events', "event='page_view' AND DATE(created_at)=DATE('now')");
    $weekViews  = $db->count('analytics_events', "event='page_view' AND created_at>=DATE('now','-7 days')");
    $totalUsers = $db->count('users');
    $activePlug = $db->count('plugins', "status='active'");
    $topPages   = $db->fetchAll(
        "SELECT path, COUNT(*) as views FROM analytics_events WHERE event='page_view' GROUP BY path ORDER BY views DESC LIMIT 10"
    );
    apiOk(compact('totalViews', 'todayViews', 'weekViews', 'totalUsers', 'activePlug', 'topPages'));
}

// ─── Plugins ──────────────────────────────────────────────────────────────────
if ($resource === 'plugins' && $method === 'GET') {
    $rows = $db->fetchAll(
        "SELECT slug,name,version,description,author,status,installed_at FROM plugins WHERE status='active' ORDER BY name ASC"
    );
    apiOk($rows, 200, ['total' => count($rows)]);
}

// ─── Settings (public subset) ─────────────────────────────────────────────────
if ($resource === 'settings' && $method === 'GET') {
    $allowed = ['site_name', 'site_tagline', 'site_url', 'active_theme', 'analytics_enabled', 'registration_enabled', 'timezone', 'date_format'];
    $result  = [];
    foreach ($allowed as $k) $result[$k] = $settings->get($k, '');
    apiOk($result);
}

// ─── 404 fallback ─────────────────────────────────────────────────────────────
apiError("Endpoint not found: {$method} /api/v1/{$resource}", 404);
