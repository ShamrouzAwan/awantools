<?php
defined('AWAN') or die('Direct access denied.');

require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';
require_once AWAN_ROOT . '/_core/Plugin.php';

require_once __DIR__ . '/engine/Color.php';
require_once __DIR__ . '/engine/Text.php';
require_once __DIR__ . '/engine/Icons.php';
require_once __DIR__ . '/engine/Params.php';
require_once __DIR__ . '/engine/Registry.php';
require_once __DIR__ . '/engine/Renderer.php';
require_once __DIR__ . '/engine/Exporter.php';
require_once __DIR__ . '/engine/Cache.php';

$raw = array_map('strval', $_GET);

// ── Shared helper: parse an HTML attribute string into a key→value array ──────
function pt_parse_attrs(string $attrs): array {
    $out = [];
    preg_match_all(
        '/(\w[\w-]*)\s*=\s*(?:"([^"]*?)"|\'([^\']*?)\'|([^\s>\'"]*))/i',
        $attrs, $av, PREG_SET_ORDER
    );
    foreach ($av as $a) {
        $key = strtolower($a[1]);
        $out[$key] = ($a[2] ?? '') !== '' ? $a[2] : (($a[3] ?? '') !== '' ? $a[3] : ($a[4] ?? ''));
    }
    // Valueless charset="UTF-8" variant
    if (!isset($out['charset']) &&
        preg_match('/\bcharset\s*=\s*["\']?([^\s"\'>;]+)/i', $attrs, $cs)) {
        $out['charset'] = $cs[1];
    }
    return $out;
}

// ── Cache action endpoints ─────────────────────────────────────────────────────
$action = $raw['action'] ?? '';
if ($action === 'clear_cache') {
    $n = PT_Cache::clear();
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'cleared' => $n]);
    exit;
}
if ($action === 'cache_stats') {
    header('Content-Type: application/json');
    echo json_encode(PT_Cache::stats());
    exit;
}

// ── Meta Inspector endpoint ────────────────────────────────────────────────────
if ($action === 'inspect_meta') {
    header('Content-Type: application/json; charset=utf-8');
    $url = trim($raw['url'] ?? '');
    if (!$url) {
        echo json_encode(['ok' => false, 'error' => 'No URL provided.']);
        exit;
    }
    if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode(['ok' => false, 'error' => 'Invalid URL. Make sure to include https://']);
        exit;
    }
    // ── SSRF guard: block private / loopback / reserved IP ranges ─────────────
    $host = parse_url($url, PHP_URL_HOST);
    if ($host) {
        $host = trim($host, '[]'); // strip IPv6 brackets
        $ip   = gethostbyname($host);
        if ($ip !== $host) { // resolution succeeded
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                echo json_encode(['ok' => false, 'error' => 'Requests to private or internal addresses are not allowed.']);
                exit;
            }
        }
        // Explicit loopback / metadata-service block regardless of resolution
        if (preg_match('/^(localhost|127\.|::1|169\.254\.|0\.0\.0\.0)/i', $host) ||
            preg_match('/^(localhost|127\.|::1|169\.254\.|0\.0\.0\.0)/i', $ip)) {
            echo json_encode(['ok' => false, 'error' => 'Requests to private or internal addresses are not allowed.']);
            exit;
        }
    }
    $ctx = stream_context_create([
        'http' => [
            'timeout'         => 12,
            'method'          => 'GET',
            'follow_location' => true,
            'max_redirects'   => 5,
            'header'          => implode("\r\n", [
                'User-Agent: Mozilla/5.0 (compatible; AwanTools MetaBot/1.0; +https://awantools.site)',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
            ]),
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $html = @file_get_contents($url, false, $ctx);
    if ($html === false) {
        echo json_encode(['ok' => false, 'error' => 'Could not fetch the URL. The site may block bots, require a login, or be unavailable.']);
        exit;
    }
    // ── Parse title ───────────────────────────────────────────────────────────
    $title = '';
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
        $title = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
    // ── Parse <meta> tags ─────────────────────────────────────────────────────
    $tags = [];
    preg_match_all('/<meta\b([^>]*?)(?:\s*\/?>)/i', $html, $metas);
    foreach ($metas[1] as $attrs) {
        $tag = pt_parse_attrs($attrs);
        if (empty($tag)) continue;
        $charset = $tag['charset'] ?? '';
        if ($charset) {
            $tags[] = ['type' => 'meta', 'name' => 'charset', 'content' => strtoupper($charset), 'attr' => 'charset'];
            continue;
        }
        $name    = $tag['name'] ?? $tag['property'] ?? $tag['http-equiv'] ?? $tag['itemprop'] ?? '';
        $content = $tag['content'] ?? $tag['value'] ?? '';
        if ($name === '') continue;
        $attr = isset($tag['property']) ? 'property'
              : (isset($tag['http-equiv']) ? 'http-equiv'
              : (isset($tag['itemprop'])   ? 'itemprop' : 'name'));
        $tags[] = [
            'type'    => 'meta',
            'name'    => strtolower(trim($name)),
            'content' => html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'attr'    => $attr,
        ];
    }
    // ── Parse <link> tags ─────────────────────────────────────────────────────
    $keep_rels = ['canonical','alternate','icon','shortcut icon','apple-touch-icon','manifest','sitemap','preconnect','dns-prefetch'];
    preg_match_all('/<link\b([^>]*?)(?:\s*\/?>)/i', $html, $links);
    foreach ($links[1] as $attrs) {
        $tag = pt_parse_attrs($attrs);
        if (empty($tag['rel'])) continue;
        $rel = strtolower(trim($tag['rel']));
        foreach ($keep_rels as $kr) {
            if (strpos($rel, $kr) !== false) {
                $tags[] = ['type' => 'link', 'name' => $rel, 'content' => $tag['href'] ?? '', 'attr' => 'rel'];
                break;
            }
        }
    }
    echo json_encode(
        ['ok' => true, 'url' => $url, 'title' => $title, 'tags' => $tags],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

// ── Render mode detection ──────────────────────────────────────────────────────
$is_render = isset($raw['category']) && isset($raw['template']);

if ($is_render) {

    // ── Strip session/no-cache headers before any image output ────────────────
    // _bootstrap.php calls session_start(), which queues three problematic
    // headers on every response:
    //   • Set-Cookie: AWAN_SESSION=…   (session identification)
    //   • Pragma: no-cache             (PHP "nocache" cache-limiter default)
    //   • Expires: Thu, 19 Nov 1981 … (PHP "nocache" cache-limiter default)
    //
    // Social-media OG crawlers (Facebook, Twitter/X, LinkedIn, Slack, …) treat
    // any response carrying Set-Cookie or Pragma: no-cache as non-cacheable /
    // private and either refuse to render it as an og:image or display it with
    // no styling.  We don't need session state to generate an image, so we
    // close the session immediately and remove those headers before anything
    // else is sent.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();   // flush & release — no session writes needed here
    }
    header_remove('Set-Cookie');   // no session cookie on image responses
    header_remove('Pragma');       // remove Pragma: no-cache
    header_remove('Expires');      // remove Expires: (past date)
    header_remove('Cache-Control');// let PT_Exporter set the correct Cache-Control

    $cache_key = PT_Cache::key($raw);
    $cache_fmt = PT_Cache::fmt($raw);

    // ── Cache HIT ─────────────────────────────────────────────────────────────
    if (PT_Cache::serve_if_hit($cache_key, $cache_fmt)) {
        exit;
    }

    // ── Cache MISS — render, capture, store ───────────────────────────────────
    header('X-Cache: MISS');
    $cat    = PT_Params::slug($raw['category'] ?? 'og', 'og');
    $tpl    = PT_Params::slug($raw['template']  ?? 'github_dark', 'github_dark');
    $tdefs  = PT_Registry::get_template_defaults($cat, $tpl);
    $merged = array_merge($tdefs, $raw);
    $p      = PT_Params::parse($merged);
    $svg    = PT_Renderer::render($p);

    ob_start();
    PT_Exporter::output($svg, $p);
    $bytes = ob_get_clean();

    PT_Cache::store($cache_key, $cache_fmt, $bytes);

    // Headers were already sent by PT_Exporter::output() before ob_get_clean().
    echo $bytes;
    exit;
}

// ── Builder mode ──────────────────────────────────────────────────────────────
$cats = PT_Registry::categories();

// Current URL base for generating preview URLs.
// Build a full absolute URL so the generated image URLs work anywhere
// (og:image tags, external tools, and embed contexts all need absolute URLs).
$path     = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');  // strip query string
$_scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = $_scheme . '://' . $_host . rtrim($path, '/') . '/';

// Serialize categories for JS
$cats_json = json_encode($cats, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

ob_start(); ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="/plugins/previewer-toolkit/assets/builder.css">

<div class="pt-app" id="ptApp">

  <!-- ── Top Bar ── -->
  <div class="pt-topbar">
    <div class="pt-logo">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      <span>Previewer <strong>Toolkit</strong></span>
    </div>
    <button class="pt-btn-cache" id="ptCacheBtn" onclick="PT.clearCache()" title="Clear all server-cached images">
      <i class="fa-solid fa-trash-can"></i> Clear Cache
      <span class="pt-cache-badge" id="ptCacheBadge">…</span>
    </button>
  </div>

  <!-- ── Hero / Mode Selector ── -->
  <div class="pt-hero">
    <div class="pt-hero-intro">
      <h1 class="pt-hero-title">OG Image Generator &amp; Meta Inspector</h1>
      <p class="pt-hero-sub">Design your Open Graph image, customize it to your brand, and get a permanent URL — no image hosting, no downloads required. Or inspect any site's OG &amp; meta tags in one click.</p>
    </div>
    <div class="pt-mode-cards">

      <!-- ── Generator Card ── -->
      <div class="pt-mode-card pt-mode-card--gen active" id="ptModeGenerate" onclick="PT.selectMode('generate')">
        <div class="pt-mode-accent pt-mode-accent--gen">
          <i class="fa-solid fa-wand-magic-sparkles"></i>
        </div>
        <div class="pt-mode-body">
          <div class="pt-mode-name">OG Image Generator</div>
          <div class="pt-mode-desc">Pick a template, set your colors and text, then copy the URL. Paste it as your <code>og:image</code> meta tag — your server generates the image on demand, forever.</div>
          <div class="pt-mode-flow">
            <span class="pt-flow-step"><i class="fa-solid fa-palette"></i> Customize</span>
            <i class="fa-solid fa-chevron-right pt-flow-arrow"></i>
            <span class="pt-flow-step"><i class="fa-solid fa-link"></i> Copy URL</span>
            <i class="fa-solid fa-chevron-right pt-flow-arrow"></i>
            <span class="pt-flow-step"><i class="fa-solid fa-code"></i> Paste as og:image</span>
          </div>
        </div>
        <div class="pt-mode-cta">Open Generator <i class="fa-solid fa-arrow-right"></i></div>
      </div>

      <!-- ── Inspector Card ── -->
      <div class="pt-mode-card pt-mode-card--inspect" id="ptModeInspect" onclick="PT.selectMode('inspect')">
        <div class="pt-mode-accent pt-mode-accent--inspect">
          <i class="fa-solid fa-magnifying-glass-chart"></i>
        </div>
        <div class="pt-mode-body">
          <div class="pt-mode-name">Meta Inspector</div>
          <div class="pt-mode-desc">Enter any URL to instantly reveal all its Open Graph tags, Twitter Card data, canonical links, favicon, and a live preview of the OG image.</div>
          <div class="pt-mode-flow">
            <span class="pt-flow-step"><i class="fa-solid fa-link"></i> Paste URL</span>
            <i class="fa-solid fa-chevron-right pt-flow-arrow"></i>
            <span class="pt-flow-step"><i class="fa-solid fa-table-list"></i> View All Tags</span>
            <i class="fa-solid fa-chevron-right pt-flow-arrow"></i>
            <span class="pt-flow-step"><i class="fa-solid fa-image"></i> See OG Preview</span>
          </div>
        </div>
        <div class="pt-mode-cta">Open Inspector <i class="fa-solid fa-arrow-right"></i></div>
      </div>

    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════
       Generator Workspace
       ══════════════════════════════════════════════════════════ -->
  <div id="ptGeneratorWorkspace">

    <!-- Category Tabs -->
    <div class="pt-cats-row">
      <div class="pt-cats" id="ptCats">
        <?php foreach ($cats as $slug => $cat): ?>
        <button class="pt-cat <?= $slug === 'og' ? 'active' : '' ?>" data-cat="<?= $slug ?>">
          <i class="fa-solid fa-<?= htmlspecialchars(str_replace([' '], ['-'], strtolower($cat['icon']))) ?>"></i>
          <?= htmlspecialchars($cat['name']) ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Template Chooser -->
    <div class="pt-templates-section" id="ptTemplatesSection">
      <div class="pt-templates-header">
        <h3>Choose a Template</h3>
        <span id="ptTplInfo" class="pt-tpl-info">Template: <strong>GitHub Dark</strong></span>
      </div>
      <div class="pt-template-grid" id="ptTemplateGrid"><!-- Populated by JS --></div>
    </div>

    <!-- Main Builder -->
    <div class="pt-main" id="ptMain">

      <!-- Left: Controls -->
      <div class="pt-controls" id="ptControls">
        <div class="pt-controls-header">
          <h3>Customize</h3>
          <button class="pt-btn-ghost" onclick="PT.resetToDefaults()">Reset</button>
        </div>

        <!-- 1. Content -->
        <div class="pt-section">
          <div class="pt-section-head" onclick="PT.toggleSection(this)">
            <span>1. Content</span>
            <i class="fa-solid fa-chevron-down pt-chevron"></i>
          </div>
          <div class="pt-section-body">
            <div class="pt-field">
              <label>Icon <span class="pt-hint">(Font Awesome)</span></label>
              <div class="pt-icon-input">
                <input type="text" id="f_icon" placeholder="e.g. code" value="code" onchange="PT.update()">
                <button class="pt-icon-preview" id="iconPreviewBtn" onclick="PT.openIconPicker()">
                  <i class="fa-solid fa-code" id="iconPreviewEl"></i>
                </button>
              </div>
            </div>
            <div class="pt-field">
              <label>Heading</label>
              <input type="text" id="f_heading" value="Developer Toolkit" oninput="PT.update()">
            </div>
            <div class="pt-field">
              <label>Subheading</label>
              <input type="text" id="f_subheading" value="" placeholder="Optional subheading" oninput="PT.update()">
            </div>
            <div class="pt-field">
              <label>Description <span class="pt-hint">120 chars</span></label>
              <textarea id="f_description" rows="2" oninput="PT.update()">200+ developer tools to supercharge your workflow.</textarea>
            </div>
            <div class="pt-field">
              <label>Footer / Website</label>
              <input type="text" id="f_footer" value="awantools.site" oninput="PT.update()">
            </div>
            <div class="pt-field">
              <label>Badge Text</label>
              <input type="text" id="f_badge" value="" placeholder="e.g. Open Source" oninput="PT.update()">
            </div>
            <div class="pt-extra-fields" id="ptExtraFields"></div>
          </div>
        </div>

        <!-- 2. Colors -->
        <div class="pt-section">
          <div class="pt-section-head" onclick="PT.toggleSection(this)">
            <span>2. Colors</span>
            <i class="fa-solid fa-chevron-down pt-chevron"></i>
          </div>
          <div class="pt-section-body">
            <div class="pt-field pt-color-field">
              <label>Background</label>
              <div class="pt-color-row">
                <input type="color" id="fc_bg" value="#0d1117" oninput="PT.syncColor('bg', this.value)">
                <input type="text" id="ft_bg" value="#0d1117" placeholder="#hex" oninput="PT.syncColorText('bg', this.value)" maxlength="7">
              </div>
            </div>
            <div class="pt-field pt-color-field">
              <label>Heading</label>
              <div class="pt-color-row">
                <input type="color" id="fc_heading" value="#ffffff" oninput="PT.syncColor('heading', this.value)">
                <input type="text" id="ft_heading" value="#ffffff" placeholder="#hex" oninput="PT.syncColorText('heading', this.value)" maxlength="7">
              </div>
            </div>
            <div class="pt-field pt-color-field">
              <label>Description</label>
              <div class="pt-color-row">
                <input type="color" id="fc_description" value="#94a3b8" oninput="PT.syncColor('description', this.value)">
                <input type="text" id="ft_description" value="#94a3b8" placeholder="#hex" oninput="PT.syncColorText('description', this.value)" maxlength="7">
              </div>
            </div>
            <div class="pt-field pt-color-field">
              <label>Accent</label>
              <div class="pt-color-row">
                <input type="color" id="fc_accent" value="#22c55e" oninput="PT.syncColor('accent', this.value)">
                <input type="text" id="ft_accent" value="#22c55e" placeholder="#hex" oninput="PT.syncColorText('accent', this.value)" maxlength="7">
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Design -->
        <div class="pt-section">
          <div class="pt-section-head" onclick="PT.toggleSection(this)">
            <span>3. Design</span>
            <i class="fa-solid fa-chevron-down pt-chevron"></i>
          </div>
          <div class="pt-section-body">
            <div class="pt-field">
              <label>Font <span class="pt-hint">Google Fonts</span></label>
              <select id="f_font" onchange="PT.update()">
                <option value="Inter" selected>Inter</option>
                <option value="Roboto">Roboto</option>
                <option value="Poppins">Poppins</option>
                <option value="Montserrat">Montserrat</option>
                <option value="Raleway">Raleway</option>
                <option value="Lato">Lato</option>
                <option value="Open Sans">Open Sans</option>
                <option value="Nunito">Nunito</option>
                <option value="Playfair Display">Playfair Display</option>
                <option value="Source Code Pro">Source Code Pro</option>
                <option value="JetBrains Mono">JetBrains Mono</option>
                <option value="Fira Code">Fira Code</option>
              </select>
            </div>
            <div class="pt-field">
              <label>Border Radius <span class="pt-val" id="v_radius">20px</span></label>
              <input type="range" id="f_radius" min="0" max="60" value="20" oninput="PT.updateSlider('radius', this.value); PT.update()">
            </div>
            <div class="pt-field">
              <label>Padding <span class="pt-val" id="v_padding">60px</span></label>
              <input type="range" id="f_padding" min="10" max="120" value="60" oninput="PT.updateSlider('padding', this.value); PT.update()">
            </div>
          </div>
        </div>

        <!-- 4. Output Size -->
        <div class="pt-section">
          <div class="pt-section-head" onclick="PT.toggleSection(this)">
            <span>4. Output Size</span>
            <i class="fa-solid fa-chevron-down pt-chevron"></i>
          </div>
          <div class="pt-section-body">
            <div class="pt-field-row">
              <div class="pt-field">
                <label>Width</label>
                <input type="number" id="f_width" value="1200" min="100" max="3000" oninput="PT.update()">
              </div>
              <div class="pt-field">
                <label>Height</label>
                <input type="number" id="f_height" value="630" min="100" max="3000" oninput="PT.update()">
              </div>
            </div>
            <div class="pt-field">
              <label>Format</label>
              <select id="f_format" onchange="PT.update()">
                <option value="svg">SVG (Vector)</option>
                <option value="png" selected>PNG</option>
                <option value="jpg">JPG</option>
                <option value="webp">WebP</option>
              </select>
            </div>
          </div>
        </div>
      </div><!-- /pt-controls -->

      <!-- Right: Preview + Output -->
      <div class="pt-preview-col">

        <!-- Live Preview -->
        <div class="pt-preview-panel">
          <div class="pt-preview-bar">
            <span class="pt-preview-live"><span class="pt-dot"></span> Live Preview</span>
            <div class="pt-device-btns">
              <button class="pt-dev active" data-scale="1" title="Desktop" onclick="PT.setScale(1, this)"><i class="fa-solid fa-desktop"></i></button>
              <button class="pt-dev" data-scale="0.66" title="Tablet" onclick="PT.setScale(0.66, this)"><i class="fa-solid fa-tablet-screen-button"></i></button>
              <button class="pt-dev" data-scale="0.45" title="Mobile" onclick="PT.setScale(0.45, this)"><i class="fa-solid fa-mobile-screen"></i></button>
              <button class="pt-dev" data-scale="0.33" title="Small" onclick="PT.setScale(0.33, this)"><i class="fa-solid fa-compress"></i></button>
            </div>
          </div>
          <div class="pt-preview-frame" id="ptPreviewFrame">
            <div class="pt-preview-wrap" id="ptPreviewWrap">
              <img id="ptPreviewImg" src="" alt="Preview" loading="lazy">
            </div>
            <div class="pt-preview-loading" id="ptLoading">
              <i class="fa-solid fa-spinner fa-spin"></i>
            </div>
          </div>
        </div>

        <!-- ★ URL Copy Hero — the primary action ── -->
        <div class="pt-url-hero">
          <div class="pt-url-hero-header">
            <div class="pt-url-hero-title">
              <i class="fa-solid fa-link"></i> Your OG Image URL
            </div>
            <div class="pt-url-hero-hint">Copy this URL and set it as your <code>og:image</code> — no hosting needed, the image generates on every request</div>
          </div>
          <div class="pt-url-hero-row">
            <input type="text" id="ptUrlOut" class="pt-url-hero-input" readonly onclick="this.select()" placeholder="Generating URL…">
            <button class="pt-url-hero-copy" onclick="PT.copy('ptUrlOut', this)">
              <i class="fa-solid fa-copy"></i> Copy URL
            </button>
          </div>
          <div class="pt-url-hero-actions">
            <button class="pt-url-open-btn" onclick="PT.openPreview()">
              <i class="fa-solid fa-arrow-up-right-from-square"></i> Open in new tab
            </button>
            <button class="pt-url-open-btn" onclick="PT.downloadImage()">
              <i class="fa-solid fa-download"></i> Download image
            </button>
          </div>
        </div>

        <!-- Secondary Output Panel -->
        <div class="pt-output-panel">
          <div class="pt-output-panel-head">
            <span class="pt-output-panel-label">Also copy as:</span>
            <div class="pt-output-tabs">
              <button class="pt-otab active" onclick="PT.switchOutTab('html', this)">HTML</button>
              <button class="pt-otab" onclick="PT.switchOutTab('md', this)">Markdown</button>
              <button class="pt-otab" onclick="PT.switchOutTab('params', this)">Parameters</button>
            </div>
          </div>

          <div id="otab_html" class="pt-output-section">
            <div class="pt-url-row">
              <input type="text" id="ptHtmlOut" class="pt-url-input" readonly onclick="this.select()">
              <button class="pt-copy-btn" onclick="PT.copy('ptHtmlOut', this)"><i class="fa-solid fa-copy"></i> Copy</button>
            </div>
          </div>
          <div id="otab_md" class="pt-output-section" style="display:none">
            <div class="pt-url-row">
              <input type="text" id="ptMdOut" class="pt-url-input" readonly onclick="this.select()">
              <button class="pt-copy-btn" onclick="PT.copy('ptMdOut', this)"><i class="fa-solid fa-copy"></i> Copy</button>
            </div>
          </div>
          <div id="otab_params" class="pt-output-section" style="display:none">
            <div class="pt-params-list" id="ptParamsList"></div>
          </div>

          <div class="pt-action-row">
            <button class="pt-btn-secondary" onclick="PT.randomize()">
              <i class="fa-solid fa-shuffle"></i> Randomize
            </button>
            <button class="pt-btn-ghost" onclick="PT.resetToDefaults()">
              <i class="fa-solid fa-rotate-left"></i> Reset
            </button>
          </div>

          <div class="pt-examples" id="ptExamples">
            <div class="pt-examples-title">Example URLs <span class="pt-hint">Click to load</span></div>
            <div class="pt-example-list" id="ptExampleList"></div>
          </div>
        </div>

      </div><!-- /pt-preview-col -->
    </div><!-- /pt-main -->
  </div><!-- /ptGeneratorWorkspace -->

  <!-- ══════════════════════════════════════════════════════════
       Meta Inspector Workspace
       ══════════════════════════════════════════════════════════ -->
  <div class="pt-meta-inspector" id="ptMetaInspector" style="display:none">

    <!-- URL Input Bar -->
    <div class="pt-mi-bar">
      <div class="pt-mi-form">
        <div class="pt-mi-input-wrap">
          <svg class="pt-mi-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <input id="ptMiUrl" type="url" class="pt-mi-url-input"
                 placeholder="https://example.com — enter any URL to inspect its meta &amp; OG tags"
                 onkeydown="if(event.key==='Enter')PT.inspectMeta()">
        </div>
        <button class="pt-mi-btn" id="ptMiBtn" onclick="PT.inspectMeta()">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
          Inspect
        </button>
      </div>
      <div class="pt-mi-examples-bar">
        <span class="pt-mi-ex-label">Try:</span>
        <button class="pt-mi-ex-btn" onclick="PT.miQuick('https://github.com')">github.com</button>
        <button class="pt-mi-ex-btn" onclick="PT.miQuick('https://vercel.com')">vercel.com</button>
        <button class="pt-mi-ex-btn" onclick="PT.miQuick('https://awantools.site')">awantools.site</button>
        <button class="pt-mi-ex-btn" onclick="PT.miQuick('https://tailwindcss.com')">tailwindcss.com</button>
        <button class="pt-mi-ex-btn" onclick="PT.miQuick('https://stripe.com')">stripe.com</button>
      </div>
    </div>

    <div class="pt-mi-loading" id="ptMiLoading" style="display:none">
      <i class="fa-solid fa-spinner fa-spin"></i>
      <span>Fetching and parsing meta tags…</span>
    </div>
    <div class="pt-mi-error" id="ptMiError" style="display:none">
      <i class="fa-solid fa-circle-exclamation"></i>
      <span id="ptMiErrorMsg">Could not fetch URL.</span>
    </div>
    <div class="pt-mi-results" id="ptMiResults" style="display:none">
      <div class="pt-mi-compact">
        <div class="pt-mi-col-left"  id="ptMiColLeft"></div>
        <div class="pt-mi-col-right" id="ptMiColRight"></div>
      </div>
      <div class="pt-mi-raw-wrap" id="ptMiRawWrap"></div>
    </div>

  </div><!-- /ptMetaInspector -->

  <!-- Icon Picker Modal -->
  <div class="pt-modal" id="ptIconModal" style="display:none" onclick="if(event.target===this)PT.closeIconPicker()">
    <div class="pt-modal-box">
      <div class="pt-modal-head">
        <h4>Choose an Icon</h4>
        <button onclick="PT.closeIconPicker()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="pt-modal-search">
        <input type="text" id="iconSearch" placeholder="Search icons..." oninput="PT.filterIcons(this.value)">
      </div>
      <div class="pt-icon-grid" id="ptIconGrid"></div>
    </div>
  </div>

</div><!-- /pt-app -->

<script>
var PT_BASE_URL = <?= json_encode($base_url) ?>;
var PT_CATS = <?= $cats_json ?>;
</script>
<script src="/plugins/previewer-toolkit/assets/builder.js"></script>

<?php
$content = ob_get_clean();

plugin_render('Previewer Toolkit — OG Image Generator & Meta Inspector', $content, [
    'description' => 'Generate OG images, social cards, browser mockups, profile cards, code snippets and more from URL parameters — and inspect any URL\'s meta and Open Graph tags. No uploads, no storage.',
    'canonical'   => 'https://awantools.site/plugins/preview/',
]);
