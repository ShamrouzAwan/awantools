<?php
defined('AWAN') or die('Direct access denied.');

require_once __DIR__ . '/engine/Color.php';
require_once __DIR__ . '/engine/Text.php';
require_once __DIR__ . '/engine/Icons.php';
require_once __DIR__ . '/engine/Params.php';
require_once __DIR__ . '/engine/Registry.php';
require_once __DIR__ . '/engine/Renderer.php';
require_once __DIR__ . '/engine/Exporter.php';
require_once __DIR__ . '/engine/Cache.php';

$raw = array_map('strval', $_GET);

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

// ── Awan Tools: resolve aliases BEFORE render-mode detection ──────────────────
// This runs early so that: (a) theme_mode can inject `template` when omitted,
// (b) category=awan_tools_plugins works, and (c) short param names are canonical
// before the cache key is computed.
if (($raw['category'] ?? '') === 'awan_tools_plugins') {
    $raw['category'] = 'awan_tools';
}
if (($raw['category'] ?? '') === 'awan_tools') {
    // Short content aliases → canonical names; alias key removed so the cache
    // key is the same regardless of which alias the caller used.
    if (isset($raw['title']))    { $raw['heading']     = $raw['heading']     ?? $raw['title'];    unset($raw['title']); }
    if (isset($raw['desc']))     { $raw['description'] = $raw['description'] ?? $raw['desc'];     unset($raw['desc']); }
    if (isset($raw['counter1'])) { $raw['stat1_value'] = $raw['stat1_value'] ?? $raw['counter1']; unset($raw['counter1']); }
    if (isset($raw['counter2'])) { $raw['stat2_value'] = $raw['stat2_value'] ?? $raw['counter2']; unset($raw['counter2']); }
    if (isset($raw['counter3'])) { $raw['stat3_value'] = $raw['stat3_value'] ?? $raw['counter3']; unset($raw['counter3']); }

    // Numeric template aliases: 1=light  2=dark  3=neon
    $num_tpl = ['1' => 'light', '2' => 'dark', '3' => 'neon'];
    if (isset($num_tpl[$raw['template'] ?? ''])) {
        $raw['template'] = $num_tpl[$raw['template']];
    }

    // theme_mode → inject template when not already a valid slug.
    // This also handles the case where `template` is absent entirely — after this
    // block `$raw['template']` is always set, so $is_render becomes true.
    if (!in_array($raw['template'] ?? '', ['light', 'dark', 'neon'], true)) {
        $tm = strtolower(trim($raw['theme_mode'] ?? 'dark'));
        $raw['template'] = ($tm === 'light') ? 'light' : 'dark'; // default → dark
    }
    unset($raw['theme_mode']); // consumed; must not pollute the cache key
}

// ── Render mode detection ──────────────────────────────────────────────────────
$is_render = isset($raw['category']) && isset($raw['template']);

if ($is_render) {

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

    header('Content-Type: ' . PT_Cache::mime($cache_fmt));
    header('Cache-Control: public, max-age=86400');
    echo $bytes;
    exit;
}

// ── Builder mode ──────────────────────────────────────────────────────────────
$cats = PT_Registry::categories();

// Current URL base for generating preview URLs — use the actual request path
// so it works on any platform regardless of the URL prefix used.
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path     = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');  // strip query string
// Ensure trailing slash so the image URL appends ?params cleanly
$base_url = $scheme . '://' . $host . rtrim($path, '/') . '/';

// Serialize categories for JS
$cats_json = json_encode($cats, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

ob_start(); ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="/plugins/preview/assets/builder.css">

<div class="pt-app" id="ptApp">

  <!-- ── Header ── -->
  <div class="pt-header">
    <div class="pt-header-left">
      <div class="pt-logo">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        <span>Previewer <strong>Toolkit</strong></span>
      </div>
      <p>Generate beautiful previews for anything.</p>
      <p class="pt-sub">Create OG Images, Social Cards, Browser Mockups, Profile Cards, Code Snippets, and more with real-time customization.</p>
      <div class="pt-header-btns">
        <button class="pt-btn-primary" onclick="PT.openPreview()">
          <i class="fa-solid fa-eye"></i> Live Preview
        </button>
        <button class="pt-btn-secondary" onclick="PT.downloadImage()">
          <i class="fa-solid fa-download"></i> Export Image
        </button>
        <button class="pt-btn-cache" id="ptCacheBtn" onclick="PT.clearCache()" title="Clear all server-cached images">
          <i class="fa-solid fa-trash-can"></i> Clear Cache
          <span class="pt-cache-badge" id="ptCacheBadge">…</span>
        </button>
      </div>
    </div>
    <div class="pt-how-it-works">
      <div class="pt-how-title"><i class="fa-solid fa-circle-question"></i> How it works</div>
      <ol>
        <li><i class="fa-solid fa-1"></i> Choose a category</li>
        <li><i class="fa-solid fa-2"></i> Select a template</li>
        <li><i class="fa-solid fa-3"></i> Customize content &amp; styles</li>
        <li><i class="fa-solid fa-4"></i> Copy your URL or export image</li>
      </ol>
    </div>
  </div>

  <!-- ── Category Tabs ── -->
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

  <!-- ── Template Chooser ── -->
  <div class="pt-templates-section">
    <div class="pt-templates-header">
      <h3>Choose a Template</h3>
      <span id="ptTplInfo" class="pt-tpl-info">Template: <strong>GitHub Dark</strong></span>
    </div>
    <div class="pt-template-grid" id="ptTemplateGrid">
      <!-- Populated by JS -->
    </div>
  </div>

  <!-- ── Main Layout ── -->
  <div class="pt-main">

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
          <!-- Extended fields shown per category -->
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
            <label>Background Color</label>
            <div class="pt-color-row">
              <input type="color" id="fc_bg" value="#0d1117" oninput="PT.syncColor('bg', this.value)">
              <input type="text" id="ft_bg" value="#0d1117" placeholder="#hex" oninput="PT.syncColorText('bg', this.value)" maxlength="7">
            </div>
          </div>
          <div class="pt-field pt-color-field">
            <label>Heading Color</label>
            <div class="pt-color-row">
              <input type="color" id="fc_heading" value="#ffffff" oninput="PT.syncColor('heading', this.value)">
              <input type="text" id="ft_heading" value="#ffffff" placeholder="#hex" oninput="PT.syncColorText('heading', this.value)" maxlength="7">
            </div>
          </div>
          <div class="pt-field pt-color-field">
            <label>Description Color</label>
            <div class="pt-color-row">
              <input type="color" id="fc_description" value="#94a3b8" oninput="PT.syncColor('description', this.value)">
              <input type="text" id="ft_description" value="#94a3b8" placeholder="#hex" oninput="PT.syncColorText('description', this.value)" maxlength="7">
            </div>
          </div>
          <div class="pt-field pt-color-field">
            <label>Accent Color</label>
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
            <label>Font Family <span class="pt-hint">Google Fonts</span></label>
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

      <!-- 4. Output -->
      <div class="pt-section">
        <div class="pt-section-head" onclick="PT.toggleSection(this)">
          <span>4. Output</span>
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
    </div>

    <!-- Right: Preview + Output -->
    <div class="pt-preview-col">

      <!-- Preview -->
      <div class="pt-preview-panel">
        <div class="pt-preview-bar">
          <span class="pt-preview-live"><span class="pt-dot"></span> Live Preview</span>
          <div class="pt-device-btns">
            <button class="pt-dev active" data-scale="1" title="Desktop" onclick="PT.setScale(1, this)">
              <i class="fa-solid fa-desktop"></i>
            </button>
            <button class="pt-dev" data-scale="0.66" title="Tablet" onclick="PT.setScale(0.66, this)">
              <i class="fa-solid fa-tablet-screen-button"></i>
            </button>
            <button class="pt-dev" data-scale="0.45" title="Mobile" onclick="PT.setScale(0.45, this)">
              <i class="fa-solid fa-mobile-screen"></i>
            </button>
            <button class="pt-dev" data-scale="0.33" title="Small" onclick="PT.setScale(0.33, this)">
              <i class="fa-solid fa-compress"></i>
            </button>
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

      <!-- Output URLs -->
      <div class="pt-output-panel">
        <div class="pt-output-section">
          <div class="pt-output-label">
            <i class="fa-solid fa-link"></i> Generated Image URL
            <span class="pt-output-hint">Use this URL in any &lt;img&gt; tag to display your image</span>
          </div>
          <div class="pt-url-row">
            <input type="text" id="ptUrlOut" class="pt-url-input" readonly onclick="this.select()">
            <button class="pt-copy-btn" onclick="PT.copy('ptUrlOut', this)" title="Copy URL">
              <i class="fa-solid fa-copy"></i> Copy URL
            </button>
          </div>
        </div>

        <div class="pt-output-tabs">
          <button class="pt-otab active" onclick="PT.switchOutTab('html', this)">HTML</button>
          <button class="pt-otab" onclick="PT.switchOutTab('md', this)">Markdown</button>
          <button class="pt-otab" onclick="PT.switchOutTab('params', this)">Parameters</button>
        </div>

        <div id="otab_html" class="pt-output-section">
          <div class="pt-url-row">
            <input type="text" id="ptHtmlOut" class="pt-url-input" readonly onclick="this.select()">
            <button class="pt-copy-btn" onclick="PT.copy('ptHtmlOut', this)">
              <i class="fa-solid fa-copy"></i> Copy
            </button>
          </div>
        </div>
        <div id="otab_md" class="pt-output-section" style="display:none">
          <div class="pt-url-row">
            <input type="text" id="ptMdOut" class="pt-url-input" readonly onclick="this.select()">
            <button class="pt-copy-btn" onclick="PT.copy('ptMdOut', this)">
              <i class="fa-solid fa-copy"></i> Copy
            </button>
          </div>
        </div>
        <div id="otab_params" class="pt-output-section" style="display:none">
          <div class="pt-params-list" id="ptParamsList"></div>
        </div>

        <div class="pt-action-btns">
          <button class="pt-btn-primary" onclick="PT.downloadImage()">
            <i class="fa-solid fa-download"></i> Download
          </button>
          <button class="pt-btn-secondary" onclick="PT.randomize()">
            <i class="fa-solid fa-shuffle"></i> Randomize
          </button>
          <button class="pt-btn-ghost" onclick="PT.resetToDefaults()">
            <i class="fa-solid fa-rotate-left"></i> Reset
          </button>
        </div>

        <!-- Example URLs -->
        <div class="pt-examples" id="ptExamples">
          <div class="pt-examples-title">Example URLs <span class="pt-hint">Click to use</span></div>
          <div class="pt-example-list" id="ptExampleList"></div>
        </div>
      </div>

    </div><!-- /pt-preview-col -->
  </div><!-- /pt-main -->

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
<script src="/plugins/preview/assets/builder.js"></script>

<?php
$content = ob_get_clean();

plugin_render('Previewer Toolkit — Dynamic Image Generator', $content, [
    'description' => 'Generate OG images, social cards, browser mockups, profile cards, code snippets and more from URL parameters. No uploads, no storage — just URLs.',
    'canonical'   => 'https://awantools.site/plugins/preview/',
]);
