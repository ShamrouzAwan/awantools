<?php
/**
 * AWAN Plugin SDK
 * Include this file at the top of your plugin's index.php.
 * It provides helper functions for authentication, routing, rendering, and more.
 */
defined('AWAN') or die('Direct access denied.');

// ─── Authentication ────────────────────────────────────────────────────────────

/**
 * Require login to access this plugin.
 * Redirects to /login if the user is not authenticated.
 */
function plugin_requires_login(string $slug): void {
    requireLogin('/plugins/' . $slug . '/');
}

// ─── URLs & Routing ────────────────────────────────────────────────────────────

/**
 * Get the base URL for a plugin.
 */
function plugin_url(string $slug, string $path = ''): string {
    return '/plugins/' . $slug . '/' . ltrim($path, '/');
}

/**
 * Get the URL for a plugin asset.
 */
function plugin_asset(string $slug, string $asset): string {
    return '/plugins/' . $slug . '/assets/' . ltrim($asset, '/');
}

/**
 * Redirect to a path inside the plugin.
 */
function plugin_redirect(string $slug, string $path = ''): void {
    redirect(plugin_url($slug, $path));
}

// ─── Rendering ────────────────────────────────────────────────────────────────

/**
 * Include a view file from the plugin's views/ directory.
 * Variables in $vars are extracted into the view scope.
 */
function plugin_view(string $slug, string $view, array $vars = []): string {
    $file = PLUGINS_PATH . '/' . $slug . '/views/' . $view . '.php';
    if (!file_exists($file)) {
        return '<div class="alert alert-danger">View not found: ' . e($view) . '</div>';
    }
    extract($vars, EXTR_SKIP);
    ob_start();
    require $file;
    return ob_get_clean();
}

/**
 * Build the Previewer Toolkit OG image URL for a plugin.
 * Uses the manifest's name, categories, description, and author.
 * Returns an absolute URL pointing to the previewer-toolkit renderer.
 */
function plugin_og_image(string $slug): string {
    global $settings;
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base   = $scheme . '://' . $host;

    $m      = plugin_manifest($slug);
    $name   = $m['name']        ?? $slug;
    $desc   = $m['description'] ?? '';
    $author = $m['author']      ?? $settings->get('developer_name', '');
    $cats   = $m['categories']  ?? (isset($m['category']) ? (array)$m['category'] : []);

    $subheading = implode(' · ', array_slice($cats, 0, 3));
    $footer     = $author ? 'Developer: ' . $author : '';

    $params = http_build_query([
        'category'          => 'profile',
        'template'          => 'modern',
        'icon'              => 'address-card',
        'heading'           => $name,
        'subheading'        => $subheading,
        'description'       => $desc,
        'footer'            => $footer,
        'bg_color'          => '3d8bff',
        'heading_color'     => '317ff3',
        'description_color' => '454545',
        'accent_color'      => '050b18',
        'font'              => 'Inter',
        'radius'            => '20',
        'padding'           => '60',
        'width'             => '800',
        'height'            => '460',
        'format'            => 'webp',
    ]);

    return $base . '/plugins/previewer-toolkit/?' . $params;
}

/**
 * Render a full page using the active theme layout.
 * Call this at the end of your plugin's page handler.
 * Automatically injects a Previewer Toolkit OG image if og_image is not set.
 */
function plugin_render(string $title, string $content, array $opts = []): void {
    global $theme, $settings, $auth;
    if (!isset($opts['og_image'])) {
        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (preg_match('#^/plugins/([a-z0-9_\-]+)#i', $uriPath, $match)) {
            $opts['og_image'] = plugin_og_image($match[1]);
        }
    }
    require $theme->template('layout');
    render_page($title, $content, $opts);
}

// ─── Manifest ─────────────────────────────────────────────────────────────────

/**
 * Get the manifest array for a plugin.
 */
function plugin_manifest(string $slug): array {
    $file = PLUGINS_PATH . '/' . $slug . '/plugin.json';
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}

// ─── Database ─────────────────────────────────────────────────────────────────

/**
 * Get a consistent table name prefix for your plugin's tables.
 * Example: plugin_table('notes', 'items') → 'plg_notes_items'
 */
function plugin_table(string $slug, string $table): string {
    $slug  = preg_replace('/[^a-z0-9_]/i', '_', $slug);
    $table = preg_replace('/[^a-z0-9_]/i', '_', $table);
    return 'plg_' . $slug . '_' . $table;
}

// ─── Flash Messages ────────────────────────────────────────────────────────────

function plugin_flash_success(string $msg): void { Session::flash('success', $msg); }
function plugin_flash_danger(string $msg): void  { Session::flash('danger',  $msg); }
function plugin_flash_info(string $msg): void    { Session::flash('info',    $msg); }

// ─── Input Helpers ─────────────────────────────────────────────────────────────

/**
 * Get a sanitized POST or GET value, or return the default.
 */
function plugin_input(string $key, mixed $default = '', string $method = 'POST'): mixed {
    $value = ($method === 'GET' ? $_GET : $_POST)[$key] ?? $default;
    return is_string($value) ? Security::sanitize($value) : $default;
}

// ─── Email ─────────────────────────────────────────────────────────────────────

/**
 * Send an email from your plugin.
 * Uses the platform's configured SMTP settings.
 */
function plugin_send_email(string $to, string $subject, string $body, bool $isHtml = true): bool {
    global $mailer;
    return $mailer instanceof Mailer ? $mailer->send($to, $subject, $body, $isHtml) : false;
}

/**
 * Send a standard HTML email using the platform template.
 */
function plugin_email_html(string $to, string $subject, string $title, string $body, string $ctaText = '', string $ctaUrl = ''): bool {
    global $settings;
    $siteName = $settings->get('site_name', 'AWAN Platform');
    $html = Mailer::html($siteName, $title, $body, $ctaText, $ctaUrl);
    return plugin_send_email($to, $subject, $html, true);
}

// ─── Analytics Tracking ────────────────────────────────────────────────────────

/**
 * Track a custom analytics event from your plugin.
 */
function plugin_track(string $event, string $path = '', array $data = []): void {
    global $db, $auth, $settings;
    if ($settings->get('analytics_enabled', '1') !== '1') return;
    try {
        $db->insert('analytics_events', [
            'event'       => $event,
            'path'        => $path ?: ($_SERVER['REQUEST_URI'] ?? ''),
            'user_id'     => $auth->id(),
            'plugin_slug' => $data['plugin_slug'] ?? null,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    } catch (Exception $e) {}
}

// ─── Related Plugins ───────────────────────────────────────────────────────────

/**
 * Get related plugins for a given plugin slug, based on matching categories/keywords.
 * Returns an array of up to $limit plugin rows.
 */
function plugin_related(string $slug, int $limit = 4): array {
    global $db;
    try {
        $self = $db->fetch("SELECT id, manifest FROM plugins WHERE slug = ? AND status = 'active'", [$slug]);
        if (!$self) return [];
        $m    = json_decode($self['manifest'] ?? '{}', true) ?? [];
        $cats = $m['categories'] ?? (isset($m['category']) ? [$m['category']] : []);
        $keys = $m['keywords'] ?? [];
        if (empty($cats) && empty($keys)) {
            return $db->fetchAll(
                "SELECT id, slug, name, description, manifest FROM plugins WHERE status = 'active' AND id != ? ORDER BY RANDOM() LIMIT ?",
                [$self['id'], $limit]
            ) ?: [];
        }
        // Build LIKE clauses for each category
        $likes = [];
        $params = [];
        foreach ($cats as $c) {
            $likes[]  = "manifest LIKE ?";
            $params[] = '%' . $c . '%';
        }
        foreach (array_slice($keys, 0, 3) as $k) {
            $likes[]  = "manifest LIKE ?";
            $params[] = '%' . $k . '%';
        }
        $where = "status = 'active' AND id != ? AND (" . implode(' OR ', $likes) . ")";
        array_unshift($params, $self['id']);
        $params[] = $limit;
        return $db->fetchAll(
            "SELECT id, slug, name, description, manifest FROM plugins WHERE {$where} ORDER BY name ASC LIMIT ?",
            $params
        ) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Render a compact related-tools sidebar card for a given plugin slug.
 * Returns HTML string — embed inside your plugin's page.
 */
function plugin_related_html(string $slug): string {
    $related = plugin_related($slug, 4);
    if (empty($related)) return '';
    $html  = '<div class="card" style="margin-top:24px"><div class="card-header"><span class="card-title" style="font-size:14px">Related Tools</span></div><div class="card-body" style="padding:0">';
    foreach ($related as $r) {
        $m    = json_decode($r['manifest'] ?? '{}', true) ?? [];
        $icon = $m['icon'] ?? '';
        $html .= '<a href="/plugins/' . e($r['slug']) . '/" style="display:flex;align-items:center;gap:12px;padding:12px 16px;text-decoration:none;color:var(--color-text);border-bottom:1px solid var(--color-border);transition:background .1s" onmouseover="this.style.background=\'var(--color-background)\'" onmouseout="this.style.background=\'\'">';
        $html .= '<div style="width:32px;height:32px;border-radius:var(--radius-small);background:var(--color-primary-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--color-primary)">';
        $html .= $icon ?: '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/></svg>';
        $html .= '</div>';
        $html .= '<div><div style="font-size:13px;font-weight:600">' . e($r['name']) . '</div>';
        $html .= '<div style="font-size:11px;color:var(--color-text-muted)">' . e(substr($r['description'] ?? '', 0, 60)) . '</div></div>';
        $html .= '</a>';
    }
    $html .= '</div><div class="card-footer" style="text-align:center"><a href="/plugins" class="btn btn-ghost btn-sm">Browse All Tools</a></div></div>';
    return $html;
}

// ─── Scheduled Tasks ───────────────────────────────────────────────────────────

/**
 * Register a scheduled task from your plugin.
 * Call this in your on_activate.php.
 *
 * @param string   $slug     Unique task identifier
 * @param string   $name     Human-readable name
 * @param string   $desc     What the task does
 * @param int      $interval Interval in seconds
 * @param callable $callback The function to run
 */
function plugin_register_task(string $slug, string $name, string $desc, int $interval, callable $callback): void {
    Scheduler::register($slug, $name, $desc, $interval, $callback);
}
