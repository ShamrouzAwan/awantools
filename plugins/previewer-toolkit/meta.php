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

function pt_fetch_url(string $url): array|false {
    // Manually follow redirects so we can validate each hop against the safe-URL policy.
    // Never pass follow_location=true — doing so bypasses host/IP validation on redirect targets.
    $maxRedirects = 3;
    $current      = $url;
    $startedAt    = microtime(true);

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
            // Resolve the system CA bundle portably across Debian, CentOS,
            // cPanel, and macOS hosts. Fall back to PHP's compiled-in default
            // when no bundle is found at the expected paths.
            'cafile'           => (static function (): ?string {
                static $paths = [
                    '/etc/ssl/certs/ca-certificates.crt',      // Debian/Ubuntu
                    '/etc/pki/tls/certs/ca-bundle.crt',        // RHEL/CentOS/cPanel
                    '/etc/ssl/ca-bundle.pem',                  // OpenSUSE
                    '/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem', // Fedora
                    '/usr/local/etc/openssl/cert.pem',         // macOS brew
                ];
                foreach ($paths as $p) {
                    if (is_readable($p)) return $p;
                }
                return null;  // let PHP use its compiled-in default
            })(),
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

        if ($body === false) return false;

        $contentType = '';
        foreach ($headers as $h) {
            if (preg_match('/^Content-Type:\s*(.+)$/i', $h, $ctm)) { $contentType = trim($ctm[1]); break; }
        }

        return [
            'body'         => $body,
            'status'       => $statusCode,
            'contentType'  => $contentType,
            'finalUrl'     => $current,
            'timeMs'       => (int) round((microtime(true) - $startedAt) * 1000),
            'sizeBytes'    => strlen($body),
        ];
    }

    return false;
}

function pt_abs_url(string $href, string $origin, string $baseUrl): string {
    if ($href === '') return '';
    if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) return $href;
    if (str_starts_with($href, '//')) return (parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https') . ':' . $href;
    if (str_starts_with($href, '/')) return $origin . $href;
    return rtrim($origin . dirname(parse_url($baseUrl, PHP_URL_PATH) ?? ''), '/') . '/' . $href;
}

function pt_extract_meta(string $html, string $baseUrl, array $fetchInfo): array {
    $meta = [
        'url'          => $baseUrl,
        'finalUrl'     => $fetchInfo['finalUrl'] ?? $baseUrl,
        'title'        => '',
        'description'  => '',
        'canonical'    => '',
        'favicon'      => '',
        'robots'       => '',
        'author'       => '',
        'keywords'     => '',
        'viewport'     => '',
        'charset'      => '',
        'lang'         => '',
        'themeColor'   => '',
        'og'           => [],
        'twitter'      => [],
        'structured'   => [],
        'structuredTypes' => [],
        'hreflang'     => [],
        'headings'     => ['h1' => [], 'h2' => 0, 'h3' => 0, 'h4' => 0, 'h5' => 0, 'h6' => 0],
        'content'      => ['wordCount' => 0, 'imagesTotal' => 0, 'imagesMissingAlt' => 0,
                            'linksInternal' => 0, 'linksExternal' => 0, 'linksNofollow' => 0],
        'technical'    => [
            'https'        => str_starts_with($fetchInfo['finalUrl'] ?? $baseUrl, 'https://'),
            'status'       => $fetchInfo['status'] ?? 0,
            'contentType'  => $fetchInfo['contentType'] ?? '',
            'responseTime' => $fetchInfo['timeMs'] ?? 0,
            'sizeBytes'    => $fetchInfo['sizeBytes'] ?? 0,
            'redirected'   => ($fetchInfo['finalUrl'] ?? $baseUrl) !== $baseUrl,
        ],
        'warnings'     => [],
        'passed'       => [],
        'missing'      => [],
    ];

    // Extract title
    if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
        $meta['title'] = html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8');
    }

    // <html lang="">
    if (preg_match('/<html[^>]+lang=["\']([^"\']+)["\']/si', $html, $m)) $meta['lang'] = trim($m[1]);

    // Extract all meta tags
    preg_match_all('/<meta\s+([^>]+)>/si', $html, $metaTags);
    foreach ($metaTags[1] as $attrs) {
        $name    = '';
        $property = '';
        $content = '';
        $charsetAttr = '';

        if (preg_match('/\bname=["\']([^"\']+)["\']/i', $attrs, $m)) $name = strtolower(trim($m[1]));
        if (preg_match('/\bproperty=["\']([^"\']+)["\']/i', $attrs, $m)) $property = strtolower(trim($m[1]));
        if (preg_match('/\bcontent=["\']([^"\']*)["\']|content=([^\s>]+)/i', $attrs, $m)) {
            $content = html_entity_decode(trim($m[1] ?: ($m[2] ?? '')), ENT_QUOTES, 'UTF-8');
        }
        if (preg_match('/\bcharset=["\']?([^"\'\s>]+)/i', $attrs, $m)) $charsetAttr = trim($m[1]);

        if ($name === 'description') $meta['description'] = $content;
        elseif ($name === 'robots') $meta['robots'] = $content;
        elseif ($name === 'author') $meta['author'] = $content;
        elseif ($name === 'keywords') $meta['keywords'] = $content;
        elseif ($name === 'viewport') $meta['viewport'] = $content;
        elseif ($name === 'theme-color') $meta['themeColor'] = $content;
        elseif (str_starts_with($name, 'twitter:')) $meta['twitter'][substr($name, 8)] = $content;
        elseif ($charsetAttr) $meta['charset'] = $charsetAttr;

        if (str_starts_with($property, 'og:')) $meta['og'][substr($property, 3)] = $content;
    }

    // Extract canonical
    if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]*href=["\']([^"\']+)["\']/si', $html, $m) ||
        preg_match('/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']canonical["\']/si', $html, $m)) {
        $meta['canonical'] = $m[1];
    }

    // Extract hreflang alternates
    preg_match_all('/<link[^>]+rel=["\']alternate["\'][^>]*hreflang=["\']([^"\']+)["\'][^>]*href=["\']([^"\']+)["\']/si', $html, $hm, PREG_SET_ORDER);
    foreach ($hm as $h) $meta['hreflang'][] = ['lang' => $h[1], 'href' => $h[2]];

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
        if ($decoded) {
            $meta['structured'][] = $decoded;
            $items = isset($decoded['@graph']) ? $decoded['@graph'] : [$decoded];
            foreach ($items as $it) {
                if (!empty($it['@type'])) {
                    $t = is_array($it['@type']) ? implode('/', $it['@type']) : $it['@type'];
                    $meta['structuredTypes'][] = $t;
                }
            }
        }
    }
    $meta['structuredTypes'] = array_values(array_unique($meta['structuredTypes']));

    // Headings
    if (preg_match_all('/<h1\b[^>]*>(.*?)<\/h1>/si', $html, $hm)) {
        foreach ($hm[1] as $h) $meta['headings']['h1'][] = html_entity_decode(trim(strip_tags($h)), ENT_QUOTES, 'UTF-8');
    }
    foreach (['h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
        $meta['headings'][$tag] = preg_match_all('/<' . $tag . '\b[^>]*>/si', $html);
    }

    // Body word count (strip scripts/styles/tags)
    $bodyHtml = $html;
    if (preg_match('/<body[^>]*>(.*?)<\/body>/si', $html, $bm)) $bodyHtml = $bm[1];
    $bodyText = preg_replace('/<script\b[^>]*>.*?<\/script>/si', ' ', $bodyHtml);
    $bodyText = preg_replace('/<style\b[^>]*>.*?<\/style>/si', ' ', $bodyText);
    $bodyText = html_entity_decode(strip_tags($bodyText), ENT_QUOTES, 'UTF-8');
    $meta['content']['wordCount'] = count(array_filter(preg_split('/\s+/', trim($bodyText))));

    // Images: total + missing alt
    preg_match_all('/<img\s+([^>]*)>/si', $html, $imgs);
    $meta['content']['imagesTotal'] = count($imgs[1]);
    foreach ($imgs[1] as $attrs) {
        if (!preg_match('/\balt=["\']([^"\']*)["\']/i', $attrs, $am) || trim($am[1]) === '') {
            $meta['content']['imagesMissingAlt']++;
        }
    }

    // Links: internal/external/nofollow
    preg_match_all('/<a\s+([^>]*)>/si', $html, $links);
    $host = $parsedBase['host'] ?? '';
    foreach ($links[1] as $attrs) {
        if (!preg_match('/\bhref=["\']([^"\']+)["\']/i', $attrs, $lm)) continue;
        $href = trim($lm[1]);
        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) continue;
        $linkHost = parse_url(pt_abs_url($href, $origin, $baseUrl), PHP_URL_HOST);
        if ($linkHost && strtolower($linkHost) === strtolower($host)) $meta['content']['linksInternal']++;
        else $meta['content']['linksExternal']++;
        if (preg_match('/\brel=["\'][^"\']*nofollow[^"\']*["\']/i', $attrs)) $meta['content']['linksNofollow']++;
    }

    // ── Warnings, passes & missing tags ──────────────────────────
    $required = ['og:title', 'og:description', 'og:image', 'og:url', 'og:type'];
    foreach ($required as $req) {
        $key = substr($req, 3);
        if (empty($meta['og'][$key])) {
            $meta['missing'][] = $req;
            $meta['warnings'][] = "Missing required OG tag: <{$req}>";
        } else {
            $meta['passed'][] = "og:{$key} present";
        }
    }
    if (empty($meta['twitter']['card'])) $meta['warnings'][] = 'Missing twitter:card tag';
    else $meta['passed'][] = 'twitter:card present';
    if (empty($meta['twitter']['title']) && empty($meta['twitter']['description'])) {
        $meta['warnings'][] = 'No Twitter Card tags found — platform will fall back to OG tags';
    }
    if (empty($meta['description'])) $meta['warnings'][] = 'Missing meta description';
    else $meta['passed'][] = 'Meta description present';
    if (empty($meta['canonical'])) $meta['warnings'][] = 'No canonical URL set';
    else $meta['passed'][] = 'Canonical URL set';
    if (empty($meta['title'])) $meta['warnings'][] = 'Missing page title';
    else $meta['passed'][] = 'Page title present';
    if (strlen($meta['title']) > 60) $meta['warnings'][] = 'Title may be too long for search results (' . strlen($meta['title']) . ' chars, recommended ≤60)';
    if ($meta['title'] && strlen($meta['title']) <= 60) $meta['passed'][] = 'Title length OK';
    if (strlen($meta['description']) > 160) $meta['warnings'][] = 'Meta description is too long (' . strlen($meta['description']) . ' chars, recommended ≤160)';
    if ($meta['description'] && strlen($meta['description']) <= 160) $meta['passed'][] = 'Description length OK';
    if (empty($meta['viewport'])) $meta['warnings'][] = 'Missing viewport meta tag — page may not be mobile-friendly';
    else $meta['passed'][] = 'Viewport meta tag present';
    if (empty($meta['lang'])) $meta['warnings'][] = 'Missing lang attribute on <html> tag';
    else $meta['passed'][] = 'HTML lang attribute set';
    if (count($meta['headings']['h1']) === 0) $meta['warnings'][] = 'No <h1> heading found';
    elseif (count($meta['headings']['h1']) > 1) $meta['warnings'][] = 'Multiple <h1> tags found (' . count($meta['headings']['h1']) . ') — should typically have exactly one';
    else $meta['passed'][] = 'Exactly one <h1> heading';
    if ($meta['content']['imagesMissingAlt'] > 0) $meta['warnings'][] = $meta['content']['imagesMissingAlt'] . ' image(s) missing alt text';
    elseif ($meta['content']['imagesTotal'] > 0) $meta['passed'][] = 'All images have alt text';
    if ($meta['content']['wordCount'] < 300) $meta['warnings'][] = 'Low content: only ' . $meta['content']['wordCount'] . ' words on page (thin content may hurt rankings)';
    if (!$meta['technical']['https']) $meta['warnings'][] = 'Page is not served over HTTPS';
    else $meta['passed'][] = 'Served over HTTPS';
    if (empty($meta['favicon'])) $meta['warnings'][] = 'No favicon detected';

    // Recommendations
    $meta['recommendations'] = [];
    if (!empty($meta['og']['image'])) {
        $meta['recommendations'][] = 'OG image found — ensure it is at least 1200×630px for best social sharing quality';
    }
    if (!empty($meta['structured'])) {
        $meta['recommendations'][] = count($meta['structured']) . ' JSON-LD structured data block(s) detected' .
            (!empty($meta['structuredTypes']) ? ' (' . implode(', ', $meta['structuredTypes']) . ')' : '');
    } else {
        $meta['recommendations'][] = 'No structured data (JSON-LD) found — consider adding schema.org markup';
    }
    if (!empty($meta['robots']) && str_contains($meta['robots'], 'noindex')) {
        $meta['warnings'][] = 'Page is set to noindex — it will not appear in search results';
    }
    if (empty($meta['hreflang'])) {
        $meta['recommendations'][] = 'No hreflang tags — add them if this content targets multiple languages/regions';
    }

    // ── SEO score (0-100) ─────────────────────────────────────────
    $maxScore = count($meta['passed']) + count($meta['warnings']);
    $meta['score'] = $maxScore > 0 ? (int) round((count($meta['passed']) / $maxScore) * 100) : 0;

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

$fetched = pt_fetch_url($url);
if ($fetched === false || $fetched['body'] === '') {
    pt_meta_error('Failed to fetch URL — the site may be down or blocking bots');
}

$result = pt_extract_meta($fetched['body'], $url, $fetched);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
