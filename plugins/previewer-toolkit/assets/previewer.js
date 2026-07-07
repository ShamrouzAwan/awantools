/* ═══════════════════════════════════════════════════════════════
   Previewer Toolkit — Client-side Logic
   ═══════════════════════════════════════════════════════════════ */

const PT = (() => {

  // ── State ──────────────────────────────────────────────────────
  let state = {
    category:  'og',
    template:  'github_dark',
    zoom:      1,
    debounceTimer: null,
    loadingTimer:  null,
  };

  // ── Template registry (mirrors PHP) ───────────────────────────
  const REGISTRY = {
    og:          { templates: ['github_dark','github_light','glass_modern','minimal_clean','gradient_pro','corporate','neon_dark','startup'],          defaultW: 1200, defaultH: 630 },
    social:      { templates: ['twitter','linkedin','discord','telegram','announcement','product_launch','feature_highlight','blog_post'],             defaultW: 1200, defaultH: 630 },
    placeholder: { templates: ['simple','grid','gradient','glass','pattern','minimal','modern','empty_state'],                                          defaultW: 800,  defaultH: 600 },
    browser:     { templates: ['chrome','firefox','safari','edge','arc','generic'],                                                                     defaultW: 1200, defaultH: 800 },
    terminal:    { templates: ['linux','modern','hacker','vscode','minimal'],                                                                           defaultW: 900,  defaultH: 600 },
    profile:     { templates: ['team_member','author','developer','business','creator','speaker'],                                                      defaultW: 900,  defaultH: 500 },
    code:        { templates: ['vscode','github','monokai','nord','dracula','minimal'],                                                                 defaultW: 1000, defaultH: 600 },
    dashboard:   { templates: ['analytics','saas','stats','kpi','revenue','admin'],                                                                     defaultW: 1200, defaultH: 630 },
    docs:        { templates: ['api','readme','changelog','product','developer','knowledge'],                                                            defaultW: 1200, defaultH: 630 },
    github:      { templates: ['repo','package','release','open_source','org','project'],                                                              defaultW: 1200, defaultH: 630 },
  };

  // ── Template labels ────────────────────────────────────────────
  const TPL_LABELS = {
    github_dark: 'GitHub Dark', github_light: 'GitHub Light', glass_modern: 'Glass Modern',
    minimal_clean: 'Minimal', gradient_pro: 'Gradient Pro', corporate: 'Corporate',
    neon_dark: 'Neon Dark', startup: 'Startup',
    twitter: 'Twitter', linkedin: 'LinkedIn', discord: 'Discord', telegram: 'Telegram',
    announcement: 'Announcement', product_launch: 'Product Launch',
    feature_highlight: 'Feature', blog_post: 'Blog Post',
    simple: 'Simple', grid: 'Grid', gradient: 'Gradient', glass: 'Glass',
    pattern: 'Pattern', minimal: 'Minimal', modern: 'Modern', empty_state: 'Empty State',
    chrome: 'Chrome', firefox: 'Firefox', safari: 'Safari', edge: 'Edge', arc: 'Arc', generic: 'Generic',
    linux: 'Linux', modern: 'Modern', hacker: 'Hacker', vscode: 'VS Code',
    team_member: 'Team Member', author: 'Author', developer: 'Developer',
    business: 'Business', creator: 'Creator', speaker: 'Speaker',
    monokai: 'Monokai', nord: 'Nord', dracula: 'Dracula',
    analytics: 'Analytics', saas: 'SaaS', stats: 'Statistics', kpi: 'KPI',
    revenue: 'Revenue', admin: 'Admin Panel',
    api: 'API Docs', readme: 'README', changelog: 'Changelog', product: 'Product',
    developer: 'Dev Docs', knowledge: 'Knowledge Base',
    repo: 'Repository', package: 'Package', release: 'Release',
    open_source: 'Open Source', org: 'Organization', project: 'Project',
  };

  // ── Extra field definitions per category ──────────────────────
  const EXTRA_FIELDS = {
    terminal: [
      { id: 'p_line1', label: 'Line 1 (command)', type: 'text', placeholder: '$ echo "Hello World"' },
      { id: 'p_line2', label: 'Line 2 (output)', type: 'text', placeholder: 'Hello World' },
      { id: 'p_line3', label: 'Line 3 (command)', type: 'text', placeholder: '$ npm start' },
      { id: 'p_line4', label: 'Line 4 (output)', type: 'text', placeholder: 'Server running on :3000' },
      { id: 'p_filename', label: 'Terminal title', type: 'text', placeholder: 'bash' },
    ],
    browser: [
      { id: 'p_url_bar', label: 'URL bar text', type: 'text', placeholder: 'https://example.com' },
    ],
    code: [
      { id: 'p_filename', label: 'Filename (tab)', type: 'text', placeholder: 'index.js' },
      { id: 'p_lang', label: 'Language hint', type: 'text', placeholder: 'js' },
      { id: 'p_code', label: 'Code content', type: 'textarea', placeholder: 'function hello() {\n  return "world";\n}' },
    ],
    profile: [
      { id: 'p_username', label: 'Username / handle', type: 'text', placeholder: '@johndoe' },
      { id: 'p_role', label: 'Role / title', type: 'text', placeholder: 'Full-Stack Developer' },
      { id: 'p_stat1_label', label: 'Stat 1 label', type: 'text', placeholder: 'Posts' },
      { id: 'p_stat1_value', label: 'Stat 1 value', type: 'text', placeholder: '128' },
      { id: 'p_stat2_label', label: 'Stat 2 label', type: 'text', placeholder: 'Followers' },
      { id: 'p_stat2_value', label: 'Stat 2 value', type: 'text', placeholder: '4.2k' },
      { id: 'p_stat3_label', label: 'Stat 3 label', type: 'text', placeholder: 'Stars' },
      { id: 'p_stat3_value', label: 'Stat 3 value', type: 'text', placeholder: '892' },
    ],
    github: [
      { id: 'p_username', label: 'Username / org', type: 'text', placeholder: 'octocat' },
      { id: 'p_stars', label: 'Stars', type: 'text', placeholder: '1.2k' },
      { id: 'p_forks', label: 'Forks', type: 'text', placeholder: '234' },
      { id: 'p_version', label: 'Version', type: 'text', placeholder: 'v1.0.0' },
      { id: 'p_lang', label: 'Language', type: 'text', placeholder: 'TypeScript' },
    ],
    dashboard: [
      { id: 'p_metric1', label: 'Metric 1 value', type: 'text', placeholder: '24,891' },
      { id: 'p_metric1_label', label: 'Metric 1 label', type: 'text', placeholder: 'Total Users' },
      { id: 'p_metric2', label: 'Metric 2 value', type: 'text', placeholder: '+12.4%' },
      { id: 'p_metric2_label', label: 'Metric 2 label', type: 'text', placeholder: 'Growth' },
      { id: 'p_metric3', label: 'Metric 3 value', type: 'text', placeholder: '$8,240' },
      { id: 'p_metric3_label', label: 'Metric 3 label', type: 'text', placeholder: 'Revenue' },
    ],
    docs: [
      { id: 'p_version', label: 'Version badge', type: 'text', placeholder: 'v2.0.0' },
      { id: 'p_category_label', label: 'Section label', type: 'text', placeholder: 'Getting Started' },
    ],
  };

  // ── Init ───────────────────────────────────────────────────────
  function init() {
    renderTemplateGrid('og');
    selectTemplate('github_dark', false);
    restoreFromHash();
    update();
    setupImageLoadTracking();
  }

  function setupImageLoadTracking() {
    const img = document.getElementById('ptPreviewImg');
    img.addEventListener('load', () => hideLoading());
    img.addEventListener('error', () => {
      hideLoading();
      img.style.opacity = '0.3';
    });
  }

  // ── Tab switching ──────────────────────────────────────────────
  function switchTab(tab, btn) {
    document.querySelectorAll('.pt-tab-btn').forEach(b => b.classList.remove('pt-tab-active'));
    btn.classList.add('pt-tab-active');
    document.querySelectorAll('.pt-tab-panel').forEach(p => p.style.display = 'none');
    document.getElementById('pt-' + tab).style.display = 'flex';
  }

  // ── Category selection ─────────────────────────────────────────
  function selectCategory(cat, btn) {
    state.category = cat;
    document.querySelectorAll('.pt-cat-btn').forEach(b => b.classList.remove('pt-cat-active'));
    btn.classList.add('pt-cat-active');
    renderTemplateGrid(cat);

    // Auto-select first template
    const templates = REGISTRY[cat]?.templates || [];
    if (templates.length > 0) {
      selectTemplate(templates[0]);
    }

    // Update dimensions
    const reg = REGISTRY[cat] || {};
    setDimensions(reg.defaultW || 1200, reg.defaultH || 630);

    // Show/hide extra fields
    renderExtraFields(cat);
  }

  function renderTemplateGrid(cat) {
    const grid = document.getElementById('ptTplGrid');
    const templates = REGISTRY[cat]?.templates || [];
    grid.innerHTML = templates.map(tpl => `
      <button class="pt-tpl-btn ${tpl === state.template ? 'pt-tpl-active' : ''}"
              data-tpl="${tpl}"
              onclick="PT.selectTemplate('${tpl}')">
        ${TPL_LABELS[tpl] || tpl}
      </button>
    `).join('');
  }

  function selectTemplate(tpl, doUpdate = true) {
    state.template = tpl;
    document.querySelectorAll('.pt-tpl-btn').forEach(b => {
      b.classList.toggle('pt-tpl-active', b.dataset.tpl === tpl);
    });
    if (doUpdate) update();
  }

  function renderExtraFields(cat) {
    const section = document.getElementById('ptExtraSection');
    const fields  = EXTRA_FIELDS[cat] || [];
    if (fields.length === 0) { section.style.display = 'none'; return; }
    section.style.display = '';
    const container = document.getElementById('ptExtraFields');
    container.innerHTML = fields.map(f => {
      if (f.type === 'textarea') {
        return `<div class="pt-field">
          <label>${f.label}</label>
          <textarea id="${f.id}" class="pt-input" rows="4" oninput="PT.debounce()" placeholder="${f.placeholder || ''}" style="resize:vertical;font-family:monospace;font-size:11px"></textarea>
        </div>`;
      }
      return `<div class="pt-field">
        <label>${f.label}</label>
        <input type="text" id="${f.id}" class="pt-input" oninput="PT.debounce()" placeholder="${f.placeholder || ''}">
      </div>`;
    }).join('');
  }

  // ── URL building ───────────────────────────────────────────────
  function buildParams() {
    const params = {
      category: state.category,
      template: state.template,
      width:    val('p_width')  || 1200,
      height:   val('p_height') || 630,
      format:   val('p_format') || 'png',
    };

    // Content params
    const textFields = ['heading','description','badge','icon','website','author','footer','watermark',
                        'subheading','category_label','date','version','filename','lang','url_bar','username','role',
                        'stat1_label','stat1_value','stat2_label','stat2_value','stat3_label','stat3_value',
                        'metric1','metric1_label','metric2','metric2_label','metric3','metric3_label',
                        'line1','line2','line3','line4','code','stars','forks'];
    textFields.forEach(f => {
      const el = document.getElementById('p_' + f);
      if (el && el.value.trim()) params[f] = el.value.trim();
    });

    // Color params (only if user changed from defaults)
    const colorFields = [
      ['bg_color', 'pc_bg'], ['accent_color', 'pc_accent'],
      ['heading_color', 'pc_heading'], ['description_color', 'pc_desc'],
    ];
    colorFields.forEach(([paramKey, inputId]) => {
      const el = document.getElementById(inputId);
      if (el && el.dataset.userChanged === '1') {
        params[paramKey] = el.value.replace('#', '');
      }
    });

    // Slider params
    const sliders = [['font_size', 'p_font_size', 48], ['padding', 'p_padding', 60], ['radius', 'p_radius', 16]];
    sliders.forEach(([pk, id, def]) => {
      const el = document.getElementById(id);
      if (el && parseInt(el.value) !== def) params[pk] = el.value;
    });

    return params;
  }

  function buildUrl(format) {
    const params = buildParams();
    if (format) params.format = format;
    const qs = new URLSearchParams(params).toString();
    return PT_RENDER_BASE + '?' + qs;
  }

  // ── Preview update ─────────────────────────────────────────────
  function update() {
    const url = buildUrl();
    document.getElementById('ptUrlOutput').value = url;
    showLoading();
    const img = document.getElementById('ptPreviewImg');
    img.style.opacity = '1';
    img.src = url;
    updateHash();
  }

  function debounce() {
    clearTimeout(state.debounceTimer);
    state.debounceTimer = setTimeout(update, 400);
  }

  function refreshPreview() { update(); }

  function showLoading() {
    const el = document.getElementById('ptLoading');
    el.classList.remove('hidden');
    clearTimeout(state.loadingTimer);
    state.loadingTimer = setTimeout(hideLoading, 8000); // safety timeout
  }

  function hideLoading() {
    clearTimeout(state.loadingTimer);
    const el = document.getElementById('ptLoading');
    if (el) el.classList.add('hidden');
  }

  // ── Zoom ───────────────────────────────────────────────────────
  function zoom(factor) {
    state.zoom = Math.max(0.1, Math.min(4, state.zoom * factor));
    document.getElementById('ptCanvasInner').style.transform = `scale(${state.zoom})`;
  }

  function zoomFit() {
    state.zoom = 1;
    document.getElementById('ptCanvasInner').style.transform = '';
  }

  // ── Actions ────────────────────────────────────────────────────
  async function copyUrl() {
    const url = buildUrl();
    try {
      await navigator.clipboard.writeText(url);
      showToast('URL copied!');
    } catch {
      const inp = document.getElementById('ptUrlOutput');
      inp.select();
      document.execCommand('copy');
      showToast('URL copied!');
    }
  }

  function openUrl() { window.open(buildUrl(), '_blank'); }

  function download(fmt) {
    const url = buildUrl(fmt);
    const cat = state.category, tpl = state.template;
    const filename = `${cat}-${tpl}.${fmt}`;
    // Use anchor trick
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  }

  // ── Helpers ────────────────────────────────────────────────────
  function val(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
  }

  function setDimensions(w, h) {
    const we = document.getElementById('p_width');
    const he = document.getElementById('p_height');
    if (we) we.value = w;
    if (he) he.value = h;
  }

  function setPreset(w, h, btn) {
    setDimensions(w, h);
    document.querySelectorAll('.pt-preset-btn').forEach(b => b.classList.remove('pt-preset-active'));
    btn.classList.add('pt-preset-active');
    update();
  }

  function setIcon(name) {
    const el = document.getElementById('p_icon');
    if (el) { el.value = name; debounce(); }
    document.querySelectorAll('.pt-chip').forEach(c => c.classList.toggle('pt-chip-active', c.title === name));
  }

  function colorChange(paramKey, input) {
    input.dataset.userChanged = '1';
    const hexId = 'pt_' + paramKey.split('_')[0] + '_hex';
    const hexEl = document.getElementById(hexId);
    if (hexEl) hexEl.value = input.value.replace('#', '');
    debounce();
  }

  function hexInput(paramKey, colorId, hexInput) {
    const hex = hexInput.value.replace(/[^0-9a-fA-F]/g, '');
    if (hex.length === 6) {
      const colorEl = document.getElementById(colorId);
      if (colorEl) {
        colorEl.value = '#' + hex;
        colorEl.dataset.userChanged = '1';
      }
      debounce();
    }
  }

  function showToast(msg) {
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#111;color:#fff;padding:8px 16px;border-radius:8px;font-size:13px;z-index:9999;animation:pt-fadein 0.2s ease';
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2000);
  }

  // ── Hash persistence ───────────────────────────────────────────
  function updateHash() {
    const params = buildParams();
    const qs = new URLSearchParams(params).toString();
    history.replaceState(null, '', '#' + qs);
  }

  function restoreFromHash() {
    if (!location.hash) return;
    const qs = location.hash.slice(1);
    const p = new URLSearchParams(qs);
    if (p.get('category') && REGISTRY[p.get('category')]) {
      state.category = p.get('category');
      const catBtn = document.querySelector(`[data-cat="${state.category}"]`);
      if (catBtn) {
        document.querySelectorAll('.pt-cat-btn').forEach(b => b.classList.remove('pt-cat-active'));
        catBtn.classList.add('pt-cat-active');
        renderTemplateGrid(state.category);
      }
    }
    if (p.get('template')) {
      selectTemplate(p.get('template'), false);
    }
    // Render dynamic extra fields BEFORE assigning their values so the elements exist in the DOM
    renderExtraFields(state.category);

    // Restore all text fields (common + category-specific)
    ['heading','description','badge','icon','website','author','footer','watermark',
     'username','role','filename','lang','url_bar','code','stars','forks','version',
     'line1','line2','line3','line4',
     'stat1_label','stat1_value','stat2_label','stat2_value','stat3_label','stat3_value',
     'metric1','metric1_label','metric2','metric2_label','metric3','metric3_label',
    ].forEach(f => {
      if (p.has(f)) {
        const el = document.getElementById('p_' + f);
        if (el) el.value = p.get(f);
      }
    });
    // Dimensions
    if (p.get('width'))  { const e = document.getElementById('p_width');  if (e) e.value = p.get('width'); }
    if (p.get('height')) { const e = document.getElementById('p_height'); if (e) e.value = p.get('height'); }
    if (p.get('format')) { const e = document.getElementById('p_format'); if (e) e.value = p.get('format'); }
  }

  // ── Metadata Inspector ─────────────────────────────────────────
  function setInspUrl(url) {
    const el = document.getElementById('ptInspUrl');
    if (el) { el.value = url; inspect(); }
  }

  async function inspect() {
    const urlEl = document.getElementById('ptInspUrl');
    const url = urlEl?.value?.trim();
    if (!url) return;

    const btn = document.querySelector('.pt-insp-btn');
    if (btn) { btn.disabled = true; btn.textContent = 'Inspecting…'; }

    const metaDiv = document.getElementById('ptInspMeta');
    metaDiv.innerHTML = `<div class="pt-insp-loading"><div class="pt-spinner"></div> Fetching metadata…</div>`;

    try {
      const res = await fetch(PT_META_BASE + '?url=' + encodeURIComponent(url));
      const data = await res.json();

      if (data.error) {
        metaDiv.innerHTML = `<div class="pt-warning pt-warning-miss">⚠ ${escHtml(data.error)}</div>`;
        return;
      }

      renderMetaResults(data);

    } catch (e) {
      metaDiv.innerHTML = `<div class="pt-warning pt-warning-miss">⚠ Failed to fetch metadata: ${escHtml(e.message)}</div>`;
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Inspect'; }
    }
  }

  function renderMetaResults(d) {
    const metaDiv   = document.getElementById('ptInspMeta');
    const previewEl = document.getElementById('ptInspPreviews');
    previewEl.style.display = 'flex';

    let html = '';

    // Warnings
    if (d.warnings?.length || d.missing?.length) {
      html += `<div class="pt-meta-block"><div class="pt-meta-block-title">⚠ Issues Found</div><div class="pt-warning-list">`;
      (d.missing || []).forEach(m => {
        html += `<div class="pt-warning pt-warning-miss">✗ Missing: <strong>${escHtml(m)}</strong></div>`;
      });
      (d.warnings || []).forEach(w => {
        html += `<div class="pt-warning pt-warning-warn">⚠ ${escHtml(w)}</div>`;
      });
      html += `</div></div>`;
    } else {
      html += `<div class="pt-warning pt-warning-ok">✓ All required meta tags are present</div><br>`;
    }

    // Basic
    html += `<div class="pt-meta-block"><div class="pt-meta-block-title">Page Info</div>`;
    if (d.title)       html += row('Title', escHtml(d.title));
    if (d.description) html += row('Description', escHtml(d.description));
    if (d.canonical)   html += row('Canonical', `<a href="${escHtml(d.canonical)}" target="_blank" rel="noopener">${escHtml(d.canonical)}</a>`);
    if (d.robots)      html += row('Robots', escHtml(d.robots));
    if (d.author)      html += row('Author', escHtml(d.author));
    if (d.favicon)     html += row('Favicon', `<img src="${escHtml(d.favicon)}" style="height:20px;vertical-align:middle"> ${escHtml(d.favicon)}`);
    html += `</div>`;

    // OG tags
    if (d.og && Object.keys(d.og).length > 0) {
      html += `<div class="pt-meta-block"><div class="pt-meta-block-title">Open Graph Tags</div>`;
      Object.entries(d.og).forEach(([k, v]) => {
        if (k === 'image') {
          html += row('og:image', `<a href="${escHtml(v)}" target="_blank" rel="noopener">${escHtml(v)}</a><br><img class="pt-meta-img" src="${escHtml(v)}" alt="OG Image" loading="lazy">`);
        } else {
          html += row('og:' + k, escHtml(v));
        }
      });
      html += `</div>`;
    }

    // Twitter
    if (d.twitter && Object.keys(d.twitter).length > 0) {
      html += `<div class="pt-meta-block"><div class="pt-meta-block-title">Twitter Card Tags</div>`;
      Object.entries(d.twitter).forEach(([k, v]) => html += row('twitter:' + k, escHtml(v)));
      html += `</div>`;
    }

    // Structured data
    if (d.structured?.length > 0) {
      html += `<div class="pt-meta-block"><div class="pt-meta-block-title">Structured Data (JSON-LD)</div>`;
      d.structured.forEach((s, i) => {
        html += `<div style="font-size:11px;font-family:monospace;background:var(--color-background);padding:8px;border-radius:6px;margin-bottom:8px;overflow:auto;max-height:120px;">${escHtml(JSON.stringify(s, null, 2))}</div>`;
      });
      html += `</div>`;
    }

    // Recommendations
    if (d.recommendations?.length > 0) {
      html += `<div class="pt-meta-block"><div class="pt-meta-block-title">Recommendations</div><div class="pt-warning-list">`;
      d.recommendations.forEach(r => html += `<div class="pt-warning pt-warning-ok">ℹ ${escHtml(r)}</div>`);
      html += `</div></div>`;
    }

    metaDiv.innerHTML = html;

    // Social previews
    renderGooglePreview(d);
    renderTwitterPreview(d);
    renderOgPreview(d);
  }

  function renderGooglePreview(d) {
    const parsed = new URL(d.url);
    const domain = parsed.hostname;
    const path   = parsed.pathname;
    const favEl  = d.favicon ? `<img src="${escHtml(d.favicon)}" class="pt-g-favicon" onerror="this.style.display='none'">` : '';
    const breadcrumb = `${domain}${path !== '/' ? path : ''}`;
    document.getElementById('ptGooglePreviewInner').innerHTML = `
      <div class="pt-google-preview-inner">
        <div class="pt-google-url">${favEl} ${escHtml(breadcrumb)}</div>
        <div class="pt-google-title">${escHtml((d.og.title || d.title || 'No title').slice(0, 65))}</div>
        <div class="pt-google-desc">${escHtml((d.og.description || d.description || 'No description').slice(0, 160))}</div>
      </div>`;
  }

  function renderTwitterPreview(d) {
    const imgSrc  = d.twitter.image || d.og.image || '';
    const title   = d.twitter.title || d.og.title || d.title || '';
    const desc    = d.twitter.description || d.og.description || d.description || '';
    const site    = d.twitter.site || '';
    const parsed  = new URL(d.url);
    document.getElementById('ptTwitterPreviewInner').innerHTML = `
      <div class="pt-tw-card">
        <div class="pt-tw-img">${imgSrc ? `<img src="${escHtml(imgSrc)}" alt="">` : 'No image'}</div>
        <div class="pt-tw-meta">
          <div class="pt-tw-domain">${escHtml(parsed.hostname)}</div>
          <div class="pt-tw-title">${escHtml(title.slice(0, 70))}</div>
          <div class="pt-tw-desc">${escHtml(desc.slice(0, 140))}</div>
        </div>
      </div>`;
  }

  function renderOgPreview(d) {
    const imgSrc = d.og.image || '';
    const title  = d.og.title || d.title || '';
    const desc   = d.og.description || d.description || '';
    const site   = d.og.site_name || new URL(d.url).hostname;
    document.getElementById('ptOgPreviewInner').innerHTML = `
      <div class="pt-og-card">
        <div class="pt-og-img">${imgSrc ? `<img src="${escHtml(imgSrc)}" alt="">` : '<div style="height:100%;display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--color-text-muted)">No OG image</div>'}</div>
        <div class="pt-og-meta">
          <div class="pt-og-site">${escHtml(site.toUpperCase())}</div>
          <div class="pt-og-title">${escHtml(title.slice(0, 80))}</div>
          <div class="pt-og-desc">${escHtml(desc.slice(0, 150))}</div>
        </div>
      </div>`;
  }

  function row(key, valHtml) {
    return `<div class="pt-meta-row"><div class="pt-meta-key">${key}</div><div class="pt-meta-val">${valHtml}</div></div>`;
  }

  function escHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  // ── Inject fade-in animation ───────────────────────────────────
  const style = document.createElement('style');
  style.textContent = '@keyframes pt-fadein{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}';
  document.head.appendChild(style);

  // ── Public API ─────────────────────────────────────────────────
  return {
    init,
    switchTab,
    selectCategory,
    selectTemplate,
    update,
    debounce,
    refreshPreview,
    zoom,
    zoomFit,
    copyUrl,
    openUrl,
    download,
    setPreset,
    setIcon,
    colorChange,
    hexInput,
    setInspUrl,
    inspect,
  };

})();

// ── Boot ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', PT.init);
