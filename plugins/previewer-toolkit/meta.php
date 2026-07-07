<?php
/**
 * Previewer Toolkit — Metadata Inspector API
 * Served at: /plugins/previewer-toolkit/meta (clean URL via router)
 */
defined('AWAN') or die('Direct access denied.');

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function pt_meta_error(string $msg): never {
    echo json_encode(['error' => $msg]);
    exit;
}

function pt_is_safe_url(string $url): bool {
    $parsed = parse_url($url);
    if (!$parsed || !isset($parsed['host'])) return false;
    $host = strtolower($parsed['host']);
    $scheme = strtolower($parsed['scheme'] ?? 'https');
    if (!in_array($scheme, ['http', 'https'])) return false;

    // Block localhost/loopback
    $loopback = ['localhost', '127.0.0.1', '::1', '0.0.0.0'];
    if (in_array($host, $loopback)) return false;

    // Block metadata services
    if (in_array($host, ['169.254.169.254', 'metadata.google.internal', 'instance-data'])) return false;

    // DNS resolution check
    $ips = @gethostbynamel($host);
    if ($ips === false) return false;

    foreach ($ips as $ip) {
        $long = ip2long($ip);
        if ($long === false) continue;
        // Private ranges
        $privateRanges = [
            ['10.0.0.0', '10.255.255.255'],
            ['172.16.0.0', '172.31.255.255'],
            ['192.168.0.0', '192.168.255.255'],
            ['127.0.0.0', '127.255.255.255'],
            ['169.254.0.0', '169.254.255.255'],
            ['0.0.0.0', '0.255.255.255'],
            ['100.64.0.0', '100.127.255.255'],
        ];
        foreach ($privateRanges as [$start, $end]) {
            if ($long >= ip2long($start) && $long <= ip2long($end)) return false;
        }
    }
    return true;
}

function pt_fetch_url(string $url): string|false {
    // Manually follow redirects so we can validate each hop against the safe-URL policy.
    // Never pass follow_location=true — doing so bypasses host/IP validation on redirect targets.
    $maxRedirects = 3;
    $current      = $url;

    for ($hop = 0; $hop <= $maxRedirects; $hop++) {
        $ctx = stream_context_create(['http' => [
            'timeout'          => 8,
            'follow_location'  => false,          // never auto-follow; we validate each hop
            'user_agent'       => 'Mozilla/5.0 (compatible; PreviewerToolkitBot/1.0)',
            'ignore_errors'    => true,            // get body even on 4xx/5xx
            'protocol_version' => '1.1',
        ], 'ssl' => [
            'verify_peer'      => true,
            'verify_peer_name' => true,
            'cafile'           => '/etc/ssl/certs/ca-certificates.crt',
        ]]);

        $body = @file_get_contents($current, false, $ctx, 0, 500000);

        // Check for redirect
        $headers = $http_response_header ?? [];
        $statusLine = $headers[0] ?? '';
        preg_match('#HTTP/\S+\s+(\d+)#i', $statusLine, $sm);
        $statusCode = isset($sm[1]) ? (int)$sm[1] : 200;

        if (in_array($statusCode, [301, 302, 303, 307, 308], true)) {
            // Extract Location header
            $location = '';
            foreach ($headers as $h) {
                if (preg_match('/^Location:\s*(.+)$/i', $h, $lm)) {
                    $location = trim($lm[1]);
                    break;
                }
            }
            if (!$location) break; // no location, stop

            // Resolve relative redirects
            if (str_starts_with($location, '//')) {
                $scheme   = parse_url($current, PHP_URL_SCHEME) ?? 'https';
                $location = $scheme . ':' . $location;
            } elseif (str_starts_with($location, '/')) {
                $p        = parse_url($current);
                $location = ($p['scheme'] ?? 'https') . '://' . ($p['host'] ?? '') . $location;
            }

            if (!filter_var($location, FILTER_VALIDATE_URL)) break;

            // Validate redirect target before following
            if (!pt_is_safe_url($location)) return false;

            $current = $location;
            continue;
        }

        return $body !== false ? $body : false;
    }

    return false;
}

function pt_extract_meta(string $html, string $baseUrl): array {
    $meta = [
        'url'          => $baseUrl,
        'title'        => '',
        'description'  => '',
        'canonical'    => '',
        'favicon'      => '',
        'robots'       => '',
        'author'       => '',
        'og'           => [],
        'twitter'      => [],
        'structured'   => [],
        'warnings'     => [],
        'missing'      => [],
    ];

    // Extract title
    if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
        $meta['title'] = html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8');
    }

    // Extract all meta tags
    preg_match_all('/<meta\s+([^>]+)>/si', $html, $metaTags);
    foreach ($metaTags[1] as $attrs) {
        $name    = '';
        $property = '';
        $content = '';
        $httpEquiv = '';

        if (preg_match('/\bname=["\']([^"\']+)["\']/i', $attrs, $m)) $name = strtolower(trim($m[1]));
        if (preg_match('/\bproperty=["\']([^"\']+)["\']/i', $attrs, $m)) $property = strtolower(trim($m[1]));
        if (preg_match('/\bcontent=["\']([^"\']*)["\']|content=([^\s>]+)/i', $attrs, $m)) {
            $content = html_entity_decode(trim($m[1] ?: ($m[2] ?? '')), ENT_QUOTES, 'UTF-8');
        }
        if (preg_match('/\bhttp-equiv=["\']([^"\']+)["\']/i', $attrs, $m)) $httpEquiv = strtolower(trim($m[1]));

        if ($name === 'description') $meta['description'] = $content;
        elseif ($name === 'robots') $meta['robots'] = $content;
        elseif ($name === 'author') $meta['author'] = $content;
        elseif (str_starts_with($name, 'twitter:')) $meta['twitter'][substr($name, 8)] = $content;

        if (str_starts_with($property, 'og:')) $meta['og'][substr($property, 3)] = $content;
    }

    // Extract canonical
    if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]*href=["\']([^"\']+)["\']/si', $html, $m) ||
        preg_match('/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']canonical["\']/si', $html, $m)) {
        $meta['canonical'] = $m[1];
    }

    // Extract favicon
    $parsedBase = parse_url($baseUrl);
    $origin = ($parsedBase['scheme'] ?? 'https') . '://' . ($parsedBase['host'] ?? '');
    if (preg_match('/<link[^>]+rel=["\'][^"\']*(?:shortcut\s)?icon[^"\']*["\'][^>]*href=["\']([^"\']+)["\']/si', $html, $m)) {
        $favicon = $m[1];
        $meta['favicon'] = str_starts_with($favicon, 'http') ? $favicon :
            (str_starts_with($favicon, '//') ? 'https:' . $favicon :
            (str_starts_with($favicon, '/') ? $origin . $favicon : $origin . '/' . $favicon));
    } else {
        $meta['favicon'] = $origin . '/favicon.ico';
    }

    // Extract JSON-LD structured data
    preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $jsonLd);
    foreach ($jsonLd[1] as $jsonStr) {
        $decoded = json_decode(trim($jsonStr), true);
        if ($decoded) $meta['structured'][] = $decoded;
    }

    // Warnings & missing tags
    $required = ['og:title', 'og:description', 'og:image', 'og:url', 'og:type'];
    foreach ($required as $req) {
        $key = substr($req, 3);
        if (empty($meta['og'][$key])) {
            $meta['missing'][] = $req;
            $meta['warnings'][] = "Missing required OG tag: <{$req}>";
        }
    }
    if (empty($meta['twitter']['card'])) $meta['warnings'][] = 'Missing twitter:card tag';
    if (empty($meta['twitter']['title']) && empty($meta['twitter']['description'])) {
        $meta['warnings'][] = 'No Twitter Card tags found — platform will fall back to OG tags';
    }
    if (empty($meta['description'])) $meta['warnings'][] = 'Missing meta description';
    if (empty($meta['canonical'])) $meta['warnings'][] = 'No canonical URL set';
    if (empty($meta['title'])) $meta['warnings'][] = 'Missing page title';
    if (strlen($meta['title']) > 60) $meta['warnings'][] = 'Title may be too long for search results (' . strlen($meta['title']) . ' chars, recommended ≤60)';
    if (strlen($meta['description']) > 160) $meta['warnings'][] = 'Meta description is too long (' . strlen($meta['description']) . ' chars, recommended ≤160)';

    // Recommendations
    $meta['recommendations'] = [];
    if (!empty($meta['og']['image'])) {
        $meta['recommendations'][] = 'OG image found — ensure it is at least 1200×630px for best social sharing quality';
    }
    if (!empty($meta['structured'])) {
        $meta['recommendations'][] = count($meta['structured']) . ' JSON-LD structured data block(s) detected';
    }
    if (!empty($meta['robots']) && str_contains($meta['robots'], 'noindex')) {
        $meta['warnings'][] = 'Page is set to noindex — it will not appear in search results';
    }

    return $meta;
}

// ── Main handler ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    pt_meta_error('Method not allowed');
}

$url = trim($_GET['url'] ?? $_POST['url'] ?? '');
if (!$url) pt_meta_error('URL is required');

// Ensure scheme
if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
    $url = 'https://' . $url;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    pt_meta_error('Invalid URL format');
}

if (!pt_is_safe_url($url)) {
    pt_meta_error('URL is not allowed — internal/private addresses are blocked');
}

if (!ini_get('allow_url_fopen')) {
    pt_meta_error('Server configuration does not allow fetching external URLs (allow_url_fopen is disabled)');
}

$html = pt_fetch_url($url);
if ($html === false || $html === '') {
    pt_meta_error('Failed to fetch URL — the site may be down or blocking bots');
}

$result = pt_extract_meta($html, $url);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
