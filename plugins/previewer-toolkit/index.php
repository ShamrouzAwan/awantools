<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';
require_once AWAN_ROOT . '/_core/Plugin.php';

$slug      = 'previewer-toolkit';
$_manifest = plugin_manifest($slug);
plugin_track('plugin_view', '/plugins/previewer-toolkit/', ['plugin_slug' => $slug]);

// For the JS preview, use a root-relative path so the browser resolves it against
// whatever origin it is currently on (dev proxy, production, etc.) without going
// cross-origin to the site_url setting. The absolute URL is only needed for og:image.
$renderBase    = '/plugins/previewer-toolkit/render';
$renderBaseAbs = siteUrl('/plugins/previewer-toolkit/render'); // used for og_image only
$metaBase      = '/plugins/previewer-toolkit/meta';

ob_start();
?>
<link rel="stylesheet" href="/plugins/previewer-toolkit/assets/previewer.css">

<div class="pt-shell">

  <!-- ══ Tool Selector ═════════════════════════════════════════════════════ -->
  <div class="pt-selector" id="ptSelector">
    <div class="pt-sel-header">
      <h1 class="pt-sel-title">OG Image Generator &amp; Meta Inspector</h1>
      <p class="pt-sel-subtitle">Design your Open Graph image, customize it to your brand, and get a permanent URL — no image hosting, no downloads required. Or inspect any site's OG &amp; meta tags in one click.</p>
    </div>
    <div class="pt-sel-cards">

      <!-- Generator card -->
      <div class="pt-sel-card pt-sel-card--gen">
        <div class="pt-sel-card-inner">
          <div class="pt-sel-card-icon pt-sel-card-icon--gen">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 3v18"/></svg>
          </div>
          <div class="pt-sel-card-body">
            <div class="pt-sel-card-title">OG Image Generator</div>
            <div class="pt-sel-card-desc">Pick a template, set your colors and text, then copy the URL. Paste it as your <code class="pt-sel-code">og:image</code> meta tag — your server generates the image on demand, forever.</div>
            <div class="pt-sel-chips">
              <span class="pt-sel-chip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                Customize
              </span>
              <span class="pt-sel-chip-sep">›</span>
              <span class="pt-sel-chip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Copy URL
              </span>
              <span class="pt-sel-chip-sep">›</span>
              <span class="pt-sel-chip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                Paste as ogimage
              </span>
            </div>
          </div>
        </div>
        <div class="pt-sel-card-foot">
          <button class="pt-sel-open pt-sel-open--gen" onclick="PT.openTool('generator')">Open Generator →</button>
        </div>
      </div>

      <!-- Inspector card -->
      <div class="pt-sel-card pt-sel-card--insp">
        <div class="pt-sel-card-inner">
          <div class="pt-sel-card-icon pt-sel-card-icon--insp">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35M11 8v3M11 14h.01"/></svg>
          </div>
          <div class="pt-sel-card-body">
            <div class="pt-sel-card-title">Meta Inspector</div>
            <div class="pt-sel-card-desc">Enter any URL to instantly reveal all its Open Graph tags, Twitter Card data, canonical links, favicon, and a live preview of the OG image.</div>
            <div class="pt-sel-chips">
              <span class="pt-sel-chip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                Paste URL
              </span>
              <span class="pt-sel-chip-sep">›</span>
              <span class="pt-sel-chip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                View All Tags
              </span>
              <span class="pt-sel-chip-sep">›</span>
              <span class="pt-sel-chip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                See OG Preview
              </span>
            </div>
          </div>
        </div>
        <div class="pt-sel-card-foot">
          <button class="pt-sel-open pt-sel-open--insp" onclick="PT.openTool('inspector')">Open Inspector →</button>
        </div>
      </div>

    </div>
  </div>

  <!-- ══ In-tool top bar (shown when a tool is active) ═════════════════════ -->
  <div class="pt-topbar" id="ptTopbar" style="display:none">
    <div class="pt-topbar-left">
      <button class="pt-back-btn" onclick="PT.backToSelector()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Back
      </button>
      <button class="pt-tab-btn pt-tab-active" id="ptTabGenerator" data-tab="generator" onclick="PT.switchTab('generator', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 3v18"/></svg>
        Image Generator
      </button>
      <button class="pt-tab-btn" id="ptTabInspector" data-tab="inspector" onclick="PT.switchTab('inspector', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35M11 8v3M11 14h.01"/></svg>
        Meta Inspector
      </button>
    </div>
    <div class="pt-topbar-right">
      <span class="pt-topbar-badge">52 Templates</span>
      <a href="/plugins" class="btn btn-ghost btn-sm">← All Tools</a>
    </div>
  </div>

  <!-- ══ GENERATOR TAB ════════════════════════════════════════════════════ -->
  <div class="pt-tab-panel" id="pt-generator" style="display:none">
    <div class="pt-layout">

      <!-- Left: Category & Template selector -->
      <aside class="pt-sidebar">
        <div class="pt-sidebar-header">Category</div>
        <nav class="pt-cat-nav" id="ptCatNav">
          <?php
          $categories = [
            ['og',          'image',         'OG Images',         '8 templates'],
            ['social',      'share-nodes',   'Social Cards',      '8 templates'],
            ['placeholder', 'fill',          'Placeholders',      '8 templates'],
            ['browser',     'globe',         'Browser Mockups',   '6 templates'],
            ['terminal',    'terminal',      'Terminal Previews', '5 templates'],
            ['profile',     'id-card',       'Profile Cards',     '6 templates'],
            ['code',        'file-code',     'Code Snippets',     '6 templates'],
            ['dashboard',   'chart-bar',     'Dashboards',        '6 templates'],
            ['docs',        'book',          'Docs Previews',     '6 templates'],
            ['github',      'code-branch',   'GitHub Cards',      '6 templates'],
          ];
          foreach ($categories as [$catId, $icon, $label, $count]):
          ?>
          <button class="pt-cat-btn <?= $catId === 'og' ? 'pt-cat-active' : '' ?>"
                  data-cat="<?= $catId ?>"
                  onclick="PT.selectCategory('<?= $catId ?>', this)">
            <span class="pt-cat-icon"><i class="fa-solid fa-<?= $icon ?>"></i></span>
            <span class="pt-cat-info">
              <span class="pt-cat-name"><?= $label ?></span>
              <span class="pt-cat-count"><?= $count ?></span>
            </span>
          </button>
          <?php endforeach; ?>
        </nav>

        <div class="pt-sidebar-header" style="margin-top:16px">Template</div>
        <div class="pt-tpl-grid" id="ptTplGrid"><!-- filled by JS --></div>
      </aside>

      <!-- Center: Preview -->
      <main class="pt-main">
        <div class="pt-preview-wrap">
          <div class="pt-preview-toolbar">
            <span class="pt-preview-label">Live Preview</span>
            <div class="pt-preview-actions">
              <button class="pt-icon-btn" title="Refresh preview" onclick="PT.refreshPreview()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
              </button>
              <button class="pt-icon-btn" title="Zoom in" onclick="PT.zoom(1.1)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
              </button>
              <button class="pt-icon-btn" title="Zoom out" onclick="PT.zoom(0.9)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
              </button>
              <button class="pt-icon-btn" title="Fit to screen" onclick="PT.zoomFit()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
              </button>
            </div>
          </div>
          <div class="pt-canvas" id="ptCanvas">
            <div class="pt-canvas-inner" id="ptCanvasInner">
              <img id="ptPreviewImg" src="" alt="Preview" draggable="false">
              <div class="pt-preview-loading" id="ptLoading">
                <div class="pt-spinner"></div>
                <span>Rendering…</span>
              </div>
            </div>
          </div>
        </div>

        <!-- URL + Download bar -->
        <div class="pt-url-bar">
          <div class="pt-url-field">
            <svg class="pt-url-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            <input type="text" id="ptUrlOutput" class="pt-url-input" readonly placeholder="URL will appear here…" onclick="this.select()">
          </div>
          <div class="pt-url-actions">
            <button class="pt-action-btn" onclick="PT.copyUrl()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              Copy URL
            </button>
            <button class="pt-action-btn" onclick="PT.openUrl()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              Open
            </button>
            <button class="pt-action-btn pt-action-dl" onclick="PT.download('png')">↓ PNG</button>
            <button class="pt-action-btn pt-action-dl" onclick="PT.download('jpg')">↓ JPG</button>
            <button class="pt-action-btn pt-action-dl" onclick="PT.download('webp')">↓ WEBP</button>
          </div>
        </div>
      </main>

      <!-- Right: Controls panel -->
      <aside class="pt-panel" id="ptControlPanel">
        <div class="pt-panel-scroll">

          <!-- Dimensions -->
          <div class="pt-section">
            <div class="pt-section-title">Dimensions</div>
            <div class="pt-presets" id="ptPresets">
              <button class="pt-preset-btn pt-preset-active" data-w="1200" data-h="630" onclick="PT.setPreset(1200,630,this)">1200×630</button>
              <button class="pt-preset-btn" data-w="1200" data-h="628" onclick="PT.setPreset(1200,628,this)">LinkedIn</button>
              <button class="pt-preset-btn" data-w="1200" data-h="600" onclick="PT.setPreset(1200,600,this)">Twitter</button>
              <button class="pt-preset-btn" data-w="800" data-h="600" onclick="PT.setPreset(800,600,this)">Square-ish</button>
              <button class="pt-preset-btn" data-w="1000" data-h="600" onclick="PT.setPreset(1000,600,this)">Code</button>
              <button class="pt-preset-btn" data-w="900" data-h="500" onclick="PT.setPreset(900,500,this)">Profile</button>
            </div>
            <div class="pt-row">
              <div class="pt-field">
                <label>Width</label>
                <input type="number" id="p_width" value="1200" min="100" max="2400" class="pt-input" onchange="PT.update()">
              </div>
              <div class="pt-field">
                <label>Height</label>
                <input type="number" id="p_height" value="630" min="100" max="2400" class="pt-input" onchange="PT.update()">
              </div>
              <div class="pt-field">
                <label>Format</label>
                <select id="p_format" class="pt-select" onchange="PT.update()">
                  <option value="png">PNG</option>
                  <option value="jpg">JPG</option>
                  <option value="webp">WEBP</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Content -->
          <div class="pt-section" id="ptContentSection">
            <div class="pt-section-title">Content</div>
            <div class="pt-field">
              <label>Heading <span class="pt-field-hint">main title</span></label>
              <input type="text" id="p_heading" value="Developer Toolkit" class="pt-input" oninput="PT.debounce()" placeholder="Main heading…">
            </div>
            <div class="pt-field">
              <label>Description</label>
              <input type="text" id="p_description" value="200+ free online tools for developers" class="pt-input" oninput="PT.debounce()" placeholder="Short description…">
            </div>
            <div class="pt-field">
              <label>Badge / Label</label>
              <input type="text" id="p_badge" value="" class="pt-input" oninput="PT.debounce()" placeholder="e.g. Open Source, v2.0">
            </div>
            <div class="pt-field">
              <label>Icon <span class="pt-field-hint">Font Awesome name</span></label>
              <div class="pt-icon-row">
                <input type="text" id="p_icon" value="code" class="pt-input" oninput="PT.debounce()" placeholder="e.g. code, globe, star">
                <div class="pt-icon-chips" id="ptIconChips">
                  <?php foreach (['code','globe','user','database','server','cloud','star','rocket','bolt','fire','gear','lock','shield','chart-bar','book','file-code'] as $ic): ?>
                  <button class="pt-chip" onclick="PT.setIcon('<?= $ic ?>')" title="<?= $ic ?>"><i class="fa-solid fa-<?= $ic ?>"></i></button>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <div class="pt-row">
              <div class="pt-field">
                <label>Website</label>
                <input type="text" id="p_website" value="" class="pt-input" oninput="PT.debounce()" placeholder="awantools.site">
              </div>
              <div class="pt-field">
                <label>Author</label>
                <input type="text" id="p_author" value="" class="pt-input" oninput="PT.debounce()" placeholder="@username">
              </div>
            </div>
            <div class="pt-row">
              <div class="pt-field">
                <label>Footer text</label>
                <input type="text" id="p_footer" value="" class="pt-input" oninput="PT.debounce()" placeholder="Footer note…">
              </div>
              <div class="pt-field">
                <label>Watermark</label>
                <input type="text" id="p_watermark" value="" class="pt-input" oninput="PT.debounce()" placeholder="Small watermark…">
              </div>
            </div>
          </div>

          <!-- Extra fields (shown for relevant categories) -->
          <div class="pt-section pt-section-extra" id="ptExtraSection" style="display:none">
            <div class="pt-section-title">Extra Fields</div>
            <div id="ptExtraFields"><!-- dynamic --></div>
          </div>

          <!-- Colors -->
          <div class="pt-section">
            <div class="pt-section-title">Colors</div>
            <div class="pt-colors-grid">
              <div class="pt-color-field">
                <label>Background</label>
                <div class="pt-color-wrap">
                  <input type="color" id="pc_bg" value="#0d1117" oninput="PT.colorChange('bg_color', this)" class="pt-color-swatch">
                  <input type="text" id="pt_bg_hex" value="0d1117" class="pt-hex-input" oninput="PT.hexInput('bg_color','pc_bg',this)" maxlength="6">
                </div>
              </div>
              <div class="pt-color-field">
                <label>Accent</label>
                <div class="pt-color-wrap">
                  <input type="color" id="pc_accent" value="#3b82f6" oninput="PT.colorChange('accent_color', this)" class="pt-color-swatch">
                  <input type="text" id="pt_accent_hex" value="3b82f6" class="pt-hex-input" oninput="PT.hexInput('accent_color','pc_accent',this)" maxlength="6">
                </div>
              </div>
              <div class="pt-color-field">
                <label>Heading</label>
                <div class="pt-color-wrap">
                  <input type="color" id="pc_heading" value="#ffffff" oninput="PT.colorChange('heading_color', this)" class="pt-color-swatch">
                  <input type="text" id="pt_heading_hex" value="ffffff" class="pt-hex-input" oninput="PT.hexInput('heading_color','pc_heading',this)" maxlength="6">
                </div>
              </div>
              <div class="pt-color-field">
                <label>Description</label>
                <div class="pt-color-wrap">
                  <input type="color" id="pc_desc" value="#8b949e" oninput="PT.colorChange('description_color', this)" class="pt-color-swatch">
                  <input type="text" id="pt_desc_hex" value="8b949e" class="pt-hex-input" oninput="PT.hexInput('description_color','pc_desc',this)" maxlength="6">
                </div>
              </div>
            </div>
          </div>

          <!-- Typography -->
          <div class="pt-section">
            <div class="pt-section-title">Typography</div>
            <div class="pt-row">
              <div class="pt-field">
                <label>Heading size</label>
                <input type="range" id="p_font_size" value="48" min="16" max="90" class="pt-range" oninput="PT.debounce(); document.getElementById('pt_fs_val').textContent=this.value+'px'">
                <span id="pt_fs_val" class="pt-range-val">48px</span>
              </div>
              <div class="pt-field">
                <label>Padding</label>
                <input type="range" id="p_padding" value="60" min="10" max="120" class="pt-range" oninput="PT.debounce(); document.getElementById('pt_pad_val').textContent=this.value">
                <span id="pt_pad_val" class="pt-range-val">60</span>
              </div>
            </div>
            <div class="pt-field">
              <label>Border radius</label>
              <input type="range" id="p_radius" value="16" min="0" max="60" class="pt-range" oninput="PT.debounce(); document.getElementById('pt_r_val').textContent=this.value">
              <span id="pt_r_val" class="pt-range-val">16</span>
            </div>
          </div>

        </div>
      </aside>
    </div>
  </div><!-- /generator tab -->

  <!-- ══ INSPECTOR TAB ════════════════════════════════════════════════════ -->
  <div class="pt-tab-panel" id="pt-inspector" style="display:none">
    <div class="pt-inspector-layout">

      <div class="pt-inspector-top">
        <div class="pt-insp-search">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input type="text" id="ptInspUrl" class="pt-insp-input" placeholder="Enter any URL to inspect…" value="">
          <button class="pt-insp-btn" onclick="PT.inspect()">Inspect</button>
        </div>
        <div class="pt-insp-examples">
          <span>Try:</span>
          <button onclick="PT.setInspUrl('https://github.com')">github.com</button>
          <button onclick="PT.setInspUrl('https://vercel.com')">vercel.com</button>
          <button onclick="PT.setInspUrl('https://stripe.com')">stripe.com</button>
          <button onclick="PT.setInspUrl('https://tailwindcss.com')">tailwindcss.com</button>
        </div>
      </div>

      <div class="pt-inspector-body">
        <!-- Left: parsed meta -->
        <div class="pt-insp-meta" id="ptInspMeta">
          <div class="pt-insp-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px;height:48px;opacity:.3"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35M11 8v3M11 14h.01"/></svg>
            <p>Enter a URL above and click <strong>Inspect</strong></p>
          </div>
        </div>

        <!-- Right: social previews -->
        <div class="pt-insp-previews" id="ptInspPreviews" style="display:none">
          <div class="pt-preview-card" id="ptGooglePreview">
            <div class="pt-preview-card-title">Google Search</div>
            <div class="pt-google-preview" id="ptGooglePreviewInner"></div>
          </div>
          <div class="pt-preview-card" id="ptTwitterPreview">
            <div class="pt-preview-card-title">Twitter / X Card</div>
            <div class="pt-twitter-preview" id="ptTwitterPreviewInner"></div>
          </div>
          <div class="pt-preview-card" id="ptOgPreview">
            <div class="pt-preview-card-title">OG Preview</div>
            <div class="pt-og-preview" id="ptOgPreviewInner"></div>
          </div>
        </div>
      </div>

    </div>
  </div><!-- /inspector tab -->

</div><!-- /pt-shell -->

<script>
const PT_RENDER_BASE = <?= json_encode($renderBase) ?>;
const PT_META_BASE   = <?= json_encode($metaBase) ?>;
</script>
<script src="/plugins/previewer-toolkit/assets/previewer.js"></script>
<?php
$content = ob_get_clean();

plugin_render('Previewer Toolkit', $content, [
    'description' => 'Generate professional OG images, Twitter cards, social previews and more. Plus inspect any URL\'s metadata and social tags.',
    'og_image'    => $renderBaseAbs . '?category=og&template=github_dark&heading=Previewer+Toolkit&description=52+templates+%C2%B7+OG+images+%C2%B7+Social+cards+%C2%B7+Metadata+inspector&icon=image&badge=Free+Tool&website=awantools.site&format=webp',
]);
