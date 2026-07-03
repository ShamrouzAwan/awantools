/* ── Previewer Toolkit Builder JS ───────────────────────────────────────── */
(function () {
  'use strict';

  // ── State ──────────────────────────────────────────────────────────────────
  let state = {
    category: 'og',
    template: 'github_dark',
    scale: 1,
    outTab: 'html',
    debounceTimer: null,
    currentDefaults: {},
  };

  const ICON_LIST = [
    'globe','code','database','terminal','server','star','heart','bolt','rocket',
    'shield','lock','key','wifi','cloud','cog','gear','chart-bar','chart-line',
    'users','user','envelope','phone','map-marker','search','link','check','ban',
    'info','warning','plus','minus','fire','lightbulb','atom','microchip','robot',
    'tools','wrench','bug','eye','tag','home','bell','flag','brush','cube','gem',
    'crown','trophy','infinity','brain','fingerprint','satellite','folder','file',
    'image','video','music','trash','download','upload','share','pen','calendar',
    'clock','bookmark','flask','microscope','sitemap','layer-group','filter',
    'sliders','palette','magic','paper-plane','comments','rss','plug','spinner',
    'bars','circle','square','sun','moon','leaf','tree','graduation-cap','book',
    'pencil','crop','camera','gamepad','puzzle-piece','award','certificate',
    'credit-card','money-bill','shopping-cart','store','truck','box','gift','map',
    'compass','plane','car','bicycle','hospital','pills','diamond','recycle',
    'briefcase','building','city','landmark','university','balance-scale','gavel',
    'handshake','address-book','address-card','id-badge','drafting-compass','ruler',
    'bezier-curve','vector-square','shapes','swatchbook','desktop','laptop','mobile',
    'tablet','keyboard','hard-drive','memory','network','signal','broadcast-tower',
    'radio','bluetooth','usb','sd-card','compact-disc','battery-full','charging-station',
    'solar-panel','seedling','spa','feather','dragon','cat','dog','paw','spider',
    'butterfly','thermometer','weight','running','walking','bicycle','swimming',
    'football','basketball','baseball','dumbbell','chess','dice','hat-wizard',
    'mask','ghost','skull','thumbs-up','thumbs-down','hand-peace','hand-point-up',
    'pray','peace','yin-yang','cross','book-open','newspaper','highlighter','print',
    'headphones','volume-up','microphone','newspaper','qrcode','barcode','receipt',
    'calculator','percent','divide','toggle-on','power-off','expand','compress',
    'sync','rotate','external-link','arrow-up','arrow-down','ellipsis','chevron-down',
    'github','gitlab','twitter','facebook','instagram','linkedin','youtube','discord',
    'slack','telegram','whatsapp','google','apple','windows','linux','android',
    'chrome','firefox','safari','npm','node-js','react','vuejs','angular','php',
    'python','java','swift','rust','golang','css3','html5','sass','bootstrap',
    'wordpress','shopify','laravel','django','docker','kubernetes','aws','azure',
    'vercel','netlify','cloudflare','mysql','postgresql','mongodb','redis','git',
    'figma','sketch','adobe','photoshop','dribbble','behance','medium','spotify',
    'amazon','paypal','stripe','bitcoin','ethereum',
  ];

  const EXAMPLE_TEMPLATES = [
    { category: 'og',          template: 'glassmorphism', icon: 'rocket',   heading: 'Launch Fast',       description: 'Built with passion for the world.' },
    { category: 'og',          template: 'gradient',      icon: 'star',      heading: 'Premium Quality',   description: 'Built with love, by you.' },
    { category: 'og',          template: 'neon',          icon: 'heart',     heading: 'Made with Love',    description: 'Crafted beautifully for you.' },
    { category: 'og',          template: 'minimal',       icon: 'lightbulb', heading: 'Simple Ideas',      description: 'Turning ideas into reality.' },
    { category: 'social',      template: 'modern_dark',   icon: 'bolt',      heading: 'Ship Faster',       description: 'Build and deploy in minutes.' },
    { category: 'terminal',    template: 'macos',         icon: 'terminal',  heading: 'Terminal',          description: '' },
    { category: 'github',      template: 'repo_dark',     icon: 'github',    heading: 'My Project',        description: 'Open source and free.' },
    { category: 'code',        template: 'dracula',       icon: 'code',      heading: 'snippet.js',        description: '' },
  ];

  // ── DOM Helpers ────────────────────────────────────────────────────────────
  const $ = id => document.getElementById(id);
  const $$ = sel => document.querySelectorAll(sel);

  // ── Build URL ──────────────────────────────────────────────────────────────
  function buildURL(format) {
    const params = collectParams(format);
    const q = Object.entries(params)
      .filter(([,v]) => v !== '' && v !== undefined && v !== null)
      .map(([k,v]) => encodeURIComponent(k) + '=' + encodeURIComponent(v))
      .join('&');
    return PT_BASE_URL + '?' + q;
  }

  function collectParams(format) {
    const cat = state.category;
    const tpl = state.template;
    const fmt = format || $('f_format').value;
    return {
      category:          cat,
      template:          tpl,
      icon:              $('f_icon')?.value || 'code',
      heading:           $('f_heading')?.value || '',
      subheading:        $('f_subheading')?.value || '',
      description:       $('f_description')?.value || '',
      footer:            $('f_footer')?.value || '',
      badge:             $('f_badge')?.value || '',
      bg_color:          ($('ft_bg')?.value || '#0d1117').replace('#',''),
      heading_color:     ($('ft_heading')?.value || '#ffffff').replace('#',''),
      description_color: ($('ft_description')?.value || '#94a3b8').replace('#',''),
      accent_color:      ($('ft_accent')?.value || '#22c55e').replace('#',''),
      font:              $('f_font')?.value || 'Inter',
      radius:            $('f_radius')?.value || '20',
      padding:           $('f_padding')?.value || '60',
      width:             $('f_width')?.value || '1200',
      height:            $('f_height')?.value || '630',
      format:            fmt,
      // Extra params for specific categories
      ...collectExtraParams(),
    };
  }

  function collectExtraParams() {
    const extra = {};
    document.querySelectorAll('[data-param]').forEach(el => {
      extra[el.dataset.param] = el.value;
    });
    return extra;
  }

  // ── Update Preview ─────────────────────────────────────────────────────────
  function update() {
    clearTimeout(state.debounceTimer);
    state.debounceTimer = setTimeout(() => {
      const img   = $('ptPreviewImg');
      const url   = buildURL('svg');
      const pngUrl = buildURL('png');

      // Show loading indicator
      $('ptLoading').style.display = 'flex';
      img.style.opacity = '0.5';

      img.onload = () => {
        $('ptLoading').style.display = 'none';
        img.style.opacity = '1';
      };
      img.onerror = () => {
        $('ptLoading').style.display = 'none';
        img.style.opacity = '1';
      };
      img.src = url;

      // Update outputs
      const displayUrl = buildURL($('f_format').value);
      $('ptUrlOut').value = displayUrl;
      $('ptHtmlOut').value = `<img src="${displayUrl}" alt="${$('f_heading')?.value || 'Preview'}" width="${$('f_width')?.value}" height="${$('f_height')?.value}">`;
      $('ptMdOut').value = `![${$('f_heading')?.value || 'Preview'}](${displayUrl})`;
      updateParamsList();
      updateIconPreview();
    }, 150);
  }

  function updateParamsList() {
    const p = collectParams();
    const list = $('ptParamsList');
    if (!list) return;
    list.innerHTML = Object.entries(p)
      .filter(([,v]) => v !== '')
      .map(([k,v]) => `<div class="pp"><span class="pk">${k}</span><span>=</span><span class="pv">${escHtml(String(v))}</span></div>`)
      .join('');
  }

  function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  // Escape for use inside HTML attribute values (adds " and ')
  function escAttr(s) {
    return escHtml(String(s)).replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  // ── Mode Switching (Generate vs Inspect) ──────────────────────────────────
  function selectMode(mode) {
    const genCard = $('ptModeGenerate');
    const insCard = $('ptModeInspect');
    const genWs   = $('ptGeneratorWorkspace');
    const insWs   = $('ptMetaInspector');
    const isInspect = mode === 'inspect';

    if (genCard) genCard.classList.toggle('active', !isInspect);
    if (insCard) insCard.classList.toggle('active', isInspect);
    if (genWs)   genWs.style.display = isInspect ? 'none' : '';
    if (insWs)   insWs.style.display = isInspect ? '' : 'none';

    if (isInspect) {
      state.category = 'meta_inspector';
      // Auto-fill URL from footer field if the input is blank
      const urlInput = $('ptMiUrl');
      if (urlInput && !urlInput.value.trim()) {
        const footer = $('f_footer')?.value?.trim() || '';
        if (footer) {
          urlInput.value = footer.startsWith('http') ? footer : 'https://' + footer;
        }
      }
      setTimeout(() => $('ptMiUrl')?.focus(), 80);
    } else {
      // Switch back to generator
      if (state.category === 'meta_inspector' || !PT_CATS[state.category]) {
        selectCategory('og');
      }
    }
  }

  // ── Category & Template Selection ─────────────────────────────────────────
  function selectCategory(cat, tpl) {
    // Update tab UI
    $$('.pt-cat').forEach(el => el.classList.toggle('active', el.dataset.cat === cat));

    state.category = cat;
    state.template = tpl || Object.keys(PT_CATS[cat]?.templates || {})[0] || 'default';

    // Render template grid
    renderTemplateGrid(cat);

    // Apply template defaults
    applyTemplateDefaults(cat, state.template);

    // Update width/height defaults
    const catData = PT_CATS[cat];
    if (catData) {
      if ($('f_width') && !$('f_width').dataset.userSet) $('f_width').value = catData.default_width || 1200;
      if ($('f_height') && !$('f_height').dataset.userSet) $('f_height').value = catData.default_height || 630;
    }

    // Render extra fields
    renderExtraFields(cat);

    update();
  }

  function renderTemplateGrid(cat) {
    const grid  = $('ptTemplateGrid');
    if (!grid) return;
    const catData = PT_CATS[cat];
    if (!catData) return;

    grid.innerHTML = '';
    Object.entries(catData.templates).forEach(([slug, tpl]) => {
      const thumbUrl = buildThumbURL(cat, slug);
      const card = document.createElement('div');
      card.className = 'pt-tpl-card' + (slug === state.template ? ' active' : '');
      card.dataset.template = slug;
      card.innerHTML = `
        <img class="pt-tpl-thumb" src="${thumbUrl}" alt="${escHtml(tpl.name)}" loading="lazy">
        <div class="pt-tpl-label">${escHtml(tpl.name)}</div>`;
      card.addEventListener('click', () => selectTemplate(cat, slug));
      grid.appendChild(card);
    });
  }

  function buildThumbURL(cat, tpl) {
    const catData = PT_CATS[cat];
    const defs = (catData?.defaults?.[tpl]) || {};
    const params = {
      category: cat,
      template: tpl,
      width: Math.min(catData?.default_width || 1200, 400),
      height: Math.min(catData?.default_height || 630, 225),
      format: 'svg',
      heading: 'Preview',
      description: 'Template preview',
      footer: 'awantools.site',
      icon: 'code',
      padding: '30',
      radius: '10',
      ...defs,
    };
    const q = Object.entries(params)
      .map(([k,v]) => encodeURIComponent(k) + '=' + encodeURIComponent(v))
      .join('&');
    return PT_BASE_URL + '?' + q;
  }

  function selectTemplate(cat, tpl) {
    state.template = tpl;
    // Match by data-template slug, not by NodeList index, so grid reordering won't break highlighting.
    $$('.pt-tpl-card').forEach(card => card.classList.toggle('active', card.dataset.template === tpl));

    // Update info label
    const info = $('ptTplInfo');
    if (info) {
      const name = PT_CATS[cat]?.templates?.[tpl]?.name || tpl;
      info.innerHTML = `Template: <strong>${escHtml(name)}</strong>`;
    }

    applyTemplateDefaults(cat, tpl);
    update();
  }

  function applyTemplateDefaults(cat, tpl) {
    const defs = PT_CATS[cat]?.defaults?.[tpl] || {};
    state.currentDefaults = defs;

    if (defs.bg_color) setColor('bg', '#' + defs.bg_color);
    if (defs.heading_color) setColor('heading', '#' + defs.heading_color);
    if (defs.description_color) setColor('description', '#' + defs.description_color);
    if (defs.accent_color) setColor('accent', '#' + defs.accent_color);
  }

  function setColor(key, hex) {
    const picker = $('fc_' + key);
    const text   = $('ft_' + key);
    if (picker) picker.value = hex;
    if (text)   text.value   = hex;
  }

  // ── Extra Fields Per Category ──────────────────────────────────────────────
  function renderExtraFields(cat) {
    const container = $('ptExtraFields');
    if (!container) return;
    container.innerHTML = '';

    const extraDefs = {
      terminal: [
        { param: 'prompt',   label: 'Prompt / Command',  type: 'text',  value: '~ $ npm install',              placeholder: '$ command' },
        { param: 'line1',    label: 'Output Line 1',     type: 'text',  value: '✓ Installed 47 packages',       placeholder: 'Output line' },
        { param: 'line2',    label: 'Output Line 2',     type: 'text',  value: '✓ Ready in 1.2s',               placeholder: 'Output line' },
        { param: 'line3',    label: 'Output Line 3',     type: 'text',  value: '',                              placeholder: 'Optional' },
      ],
      github: [
        { param: 'username', label: 'GitHub Username',   type: 'text',  value: 'shamrouzawan',                  placeholder: 'username' },
        { param: 'tag',      label: 'Repository Name',   type: 'text',  value: 'awan-tools',                    placeholder: 'repo-name' },
        { param: 'lang',     label: 'Language',          type: 'text',  value: 'JavaScript',                    placeholder: 'JavaScript' },
        { param: 'lang_color',label:'Language Color',    type: 'text',  value: 'f7df1e',                        placeholder: 'hex (no #)' },
        { param: 'stat1_label', label: 'Stat 1 Label',  type: 'text',  value: 'Stars',                         placeholder: 'Stars' },
        { param: 'stat1_value', label: 'Stat 1 Value',  type: 'text',  value: '1.2k',                          placeholder: '1.2k' },
        { param: 'stat2_label', label: 'Stat 2 Label',  type: 'text',  value: 'Forks',                         placeholder: 'Forks' },
        { param: 'stat2_value', label: 'Stat 2 Value',  type: 'text',  value: '234',                           placeholder: '234' },
        { param: 'stat3_label', label: 'Stat 3 Label',  type: 'text',  value: 'Issues',                        placeholder: 'Issues' },
        { param: 'stat3_value', label: 'Stat 3 Value',  type: 'text',  value: '12',                            placeholder: '12' },
      ],
      browser: [
        { param: 'url',      label: 'URL to Display',    type: 'text',  value: 'https://awantools.site',        placeholder: 'https://...' },
      ],
      profile: [
        { param: 'subheading', label: 'Role / Title',   type: 'text',  value: 'Full Stack Developer',          placeholder: 'Job title' },
        { param: 'username',   label: 'Username',        type: 'text',  value: '@shamrouzawan',                 placeholder: '@handle' },
        { param: 'badge',      label: 'Skills (comma)', type: 'text',  value: 'PHP,JavaScript,React',           placeholder: 'Skill1, Skill2' },
      ],
      code: [
        { param: 'language', label: 'Language',          type: 'text',  value: 'javascript',                    placeholder: 'javascript' },
        { param: 'code',     label: 'Code',              type: 'textarea', value: "const x = 'Hello World';\nconsole.log(x);\n\nfunction add(a, b) {\n  return a + b;\n}", placeholder: 'Enter code...' },
      ],
    };

    const fields = extraDefs[cat] || [];
    fields.forEach(f => {
      const div = document.createElement('div');
      div.className = 'pt-extra-field';
      const el = f.type === 'textarea'
        ? `<textarea data-param="${f.param}" rows="6" placeholder="${escHtml(f.placeholder)}" oninput="PT.update()">${escHtml(f.value)}</textarea>`
        : `<input type="text" data-param="${f.param}" value="${escHtml(f.value)}" placeholder="${escHtml(f.placeholder)}" oninput="PT.update()">`;
      div.innerHTML = `<label>${escHtml(f.label)}</label>${el}`;
      container.appendChild(div);
    });
  }

  // ── Color Sync ─────────────────────────────────────────────────────────────
  function syncColor(key, hex) {
    const text = $('ft_' + key);
    if (text) text.value = hex;
    update();
  }

  function syncColorText(key, val) {
    if (!val.startsWith('#')) val = '#' + val;
    // Only update when a complete, valid 6-digit hex is entered.
    // This avoids firing render requests on every partial keystroke.
    if (/^#[0-9a-fA-F]{6}$/.test(val)) {
      const picker = $('fc_' + key);
      if (picker) picker.value = val;
      update();
    }
  }

  // ── Slider ─────────────────────────────────────────────────────────────────
  function updateSlider(key, val) {
    const el = $('v_' + key);
    if (el) el.textContent = val + 'px';
  }

  // ── Icon Preview ───────────────────────────────────────────────────────────
  function updateIconPreview() {
    const icon = $('f_icon')?.value || 'code';
    const el   = $('iconPreviewEl');
    if (!el) return;
    el.className = `fa-solid fa-${icon}`;
  }

  // ── Icon Picker Modal ──────────────────────────────────────────────────────
  function openIconPicker() {
    const modal = $('ptIconModal');
    const grid  = $('ptIconGrid');
    if (!modal || !grid) return;
    renderIconGrid(grid, '');
    modal.style.display = 'flex';
    setTimeout(() => $('iconSearch')?.focus(), 50);
  }

  function closeIconPicker() {
    const modal = $('ptIconModal');
    if (modal) modal.style.display = 'none';
  }

  function renderIconGrid(grid, filter) {
    const current = $('f_icon')?.value || '';
    const filtered = filter
      ? ICON_LIST.filter(ic => ic.includes(filter.toLowerCase()))
      : ICON_LIST;
    grid.innerHTML = filtered.slice(0, 200).map(ic =>
      `<div class="pt-icon-item${ic === current ? ' selected' : ''}" onclick="PT.pickIcon('${ic}')" title="${ic}">
        <i class="fa-solid fa-${ic}"></i>
        <span>${ic}</span>
      </div>`
    ).join('');
  }

  function filterIcons(val) {
    const grid = $('ptIconGrid');
    if (grid) renderIconGrid(grid, val);
  }

  function pickIcon(icon) {
    const inp = $('f_icon');
    if (inp) inp.value = icon;
    closeIconPicker();
    updateIconPreview();
    update();
  }

  // ── Meta Inspector ─────────────────────────────────────────────────────────
  function inspectMeta() {
    const urlInput = $('ptMiUrl');
    let url = urlInput?.value?.trim() || '';
    if (!url) { urlInput?.focus(); return; }
    if (!url.startsWith('http')) url = 'https://' + url;
    if (urlInput) urlInput.value = url;

    $('ptMiLoading').style.display = 'flex';
    $('ptMiResults').style.display = 'none';
    $('ptMiError').style.display   = 'none';

    const btn = $('ptMiBtn');
    if (btn) { btn.classList.add('loading'); btn.disabled = true; }

    fetch('?action=inspect_meta&url=' + encodeURIComponent(url))
      .then(r => r.json())
      .then(data => {
        $('ptMiLoading').style.display = 'none';
        if (btn) { btn.classList.remove('loading'); btn.disabled = false; }
        if (!data.ok) {
          $('ptMiError').style.display = 'flex';
          $('ptMiErrorMsg').textContent = data.error || 'Unknown error';
          return;
        }
        renderMetaResults(data);
      })
      .catch(err => {
        $('ptMiLoading').style.display = 'none';
        if (btn) { btn.classList.remove('loading'); btn.disabled = false; }
        $('ptMiError').style.display = 'flex';
        $('ptMiErrorMsg').textContent = 'Request failed: ' + err.message;
      });
  }

  function miQuick(url) {
    const inp = $('ptMiUrl');
    if (inp) inp.value = url;
    inspectMeta();
  }

  function renderTagContent(tag) {
    const raw = tag.content || '';
    if (!raw) return '<span style="color:var(--pt-muted)">—</span>';
    const v   = escHtml(raw);
    if (raw.startsWith('http://') || raw.startsWith('https://')) {
      const safe  = escAttr(raw);
      const isImg = /\.(jpg|jpeg|png|gif|webp|svg)(\?.*)?$/i.test(raw.split('?')[0]);
      const link  = `<a href="${safe}" target="_blank" rel="noopener noreferrer">${v}</a>`;
      return isImg
        ? link + `<img class="pt-mi-inline-img" src="${safe}" alt="" loading="lazy">`
        : link;
    }
    return v;
  }

  // Resolve a potentially relative OG image URL against the inspected page's origin.
  function resolveOgUrl(imgUrl, pageUrl) {
    if (!imgUrl) return '';
    if (imgUrl.startsWith('http://') || imgUrl.startsWith('https://')) return imgUrl;
    try {
      const base = new URL(pageUrl);
      if (imgUrl.startsWith('//')) return base.protocol + imgUrl;
      if (imgUrl.startsWith('/'))  return base.origin + imgUrl;
      return new URL(imgUrl, pageUrl).href;
    } catch { return imgUrl; }
  }

  function renderMetaResults(data) {
    const groups = {
      basic:   { title: 'Basic',            icon: 'circle-info',  tags: [] },
      og:      { title: 'Open Graph',       icon: 'share-nodes',  tags: [] },
      twitter: { title: 'Twitter / X Card', icon: 'hashtag',      tags: [] },
      link:    { title: 'Link Relations',   icon: 'link',         tags: [] },
      other:   { title: 'Other Meta',       icon: 'tag',          tags: [] },
    };

    // Page title as a synthetic basic tag
    if (data.title) {
      groups.basic.tags.unshift({ type: 'synthetic', name: 'title', content: data.title, attr: 'element' });
    }

    (data.tags || []).forEach(tag => {
      const n = (tag.name || '').toLowerCase();
      if (tag.type === 'link') {
        groups.link.tags.push(tag);
      } else if (n.startsWith('og:')) {
        groups.og.tags.push(tag);
      } else if (n.startsWith('twitter:')) {
        groups.twitter.tags.push(tag);
      } else if (['description','viewport','charset','robots','keywords','author',
                  'theme-color','msapplication-tilecolor','generator',
                  'application-name','referrer','color-scheme'].includes(n) || tag.attr === 'charset') {
        groups.basic.tags.push(tag);
      } else {
        groups.other.tags.push(tag);
      }
    });

    // ── Social link preview card ─────────────────────────────────────────────
    const ogImg      = groups.og.tags.find(t => t.name === 'og:image')?.content ||
                       groups.twitter.tags.find(t => t.name === 'twitter:image')?.content || '';
    const ogTitle    = groups.og.tags.find(t => t.name === 'og:title')?.content || data.title || '';
    const ogDesc     = groups.og.tags.find(t => t.name === 'og:description')?.content ||
                       groups.basic.tags.find(t => t.name === 'description')?.content || '';
    const siteName   = groups.og.tags.find(t => t.name === 'og:site_name')?.content || '';
    const pageHost   = (() => { try { return new URL(data.url || '').hostname.replace(/^www\./, ''); } catch { return data.url || ''; } })();

    const lpCard    = $('ptMiLinkPreview');
    const lpImgWrap = $('ptMiLpImgWrap');
    const lpImg     = $('ptMiLpImg');
    const lpSite    = $('ptMiLpSite');
    const lpTitleEl = $('ptMiLpTitle');
    const lpDescEl  = $('ptMiLpDesc');

    if (lpCard) {
      if (lpSite)    lpSite.textContent    = siteName || pageHost;
      if (lpTitleEl) lpTitleEl.textContent = ogTitle;
      if (lpDescEl)  lpDescEl.textContent  = ogDesc;

      // Resolve relative OG image URLs against the inspected page's origin
      const resolvedImg = resolveOgUrl(ogImg, data.url || '');

      const noImgFallback = '<div class="pt-mi-lp-no-img-msg"><i class="fa-regular fa-image"></i><span>This page has no OG image</span></div>';

      if (resolvedImg && lpImg && lpImgWrap) {
        lpImgWrap.style.display = '';
        lpImgWrap.classList.remove('pt-mi-lp-no-img');
        lpImgWrap.classList.add('pt-mi-lp-loading');
        lpImg.style.display = '';
        lpImg.style.opacity = '0';
        lpImg.onload = () => {
          lpImgWrap.classList.remove('pt-mi-lp-loading');
          lpImg.style.opacity = '1';
        };
        lpImg.onerror = () => {
          lpImgWrap.classList.remove('pt-mi-lp-loading');
          lpImg.classList.add('pt-mi-lp-no-img');
          lpImg.style.display = 'none';
          lpImgWrap.innerHTML = noImgFallback;
        };
        lpImg.src = resolvedImg;
      } else if (lpImgWrap) {
        lpImgWrap.style.display = '';
        lpImgWrap.classList.add('pt-mi-lp-no-img');
        lpImgWrap.innerHTML = noImgFallback;
      }
      lpCard.style.display = '';
    }

    // ── Summary cards ────────────────────────────────────────────────────────
    const summaryEl = $('ptMiSummaryInfo');
    if (summaryEl) {
      const ogTitle    = groups.og.tags.find(t => t.name === 'og:title')?.content || '';
      const ogDesc     = groups.og.tags.find(t => t.name === 'og:description')?.content ||
                         groups.basic.tags.find(t => t.name === 'description')?.content || '';
      const canonical  = groups.link.tags.find(t => t.name === 'canonical')?.content || data.url;
      const ogType     = groups.og.tags.find(t => t.name === 'og:type')?.content || '';
      const siteName   = groups.og.tags.find(t => t.name === 'og:site_name')?.content || '';
      const twCard     = groups.twitter.tags.find(t => t.name === 'twitter:card')?.content || '';
      const charset    = groups.basic.tags.find(t => t.name === 'charset')?.content || '';
      const viewport   = groups.basic.tags.find(t => t.name === 'viewport')?.content || '';
      const robots     = groups.basic.tags.find(t => t.name === 'robots')?.content || '';

      const badge = (label, val) => val
        ? `<div class="pt-mi-badge"><span>${escHtml(label)}</span><div class="pt-mi-badge-val">${escHtml(val)}</div></div>`
        : '';

      summaryEl.innerHTML = `
        <div class="pt-mi-card">
          <div class="pt-mi-card-label"><i class="fa-solid fa-heading"></i> Page Title</div>
          <div class="pt-mi-card-value">${escHtml(data.title || '—')}</div>
        </div>
        ${ogTitle && ogTitle !== data.title ? `
        <div class="pt-mi-card">
          <div class="pt-mi-card-label"><i class="fa-solid fa-share-nodes"></i> OG Title</div>
          <div class="pt-mi-card-value">${escHtml(ogTitle)}</div>
        </div>` : ''}
        ${ogDesc ? `
        <div class="pt-mi-card">
          <div class="pt-mi-card-label"><i class="fa-solid fa-align-left"></i> Description</div>
          <div class="pt-mi-card-value">${escHtml(ogDesc)}</div>
        </div>` : ''}
        <div class="pt-mi-card">
          <div class="pt-mi-card-label"><i class="fa-solid fa-link"></i> URL</div>
          <div class="pt-mi-card-value pt-mi-card-url"><a href="${escAttr(canonical)}" target="_blank" rel="noopener noreferrer">${escHtml(canonical)}</a></div>
        </div>
        <div class="pt-mi-card-row">
          ${badge('OG Type', ogType)}
          ${badge('Site Name', siteName)}
          ${badge('TW Card', twCard)}
          ${badge('Charset', charset)}
          ${badge('Robots', robots)}
          ${viewport ? badge('Viewport', viewport.split(',')[0].trim()) : ''}
        </div>`;
    }

    // ── Tag group tables ─────────────────────────────────────────────────────
    const groupsEl = $('ptMiGroups');
    if (groupsEl) {
      const filledGroups = Object.entries(groups).filter(([, g]) => g.tags.length > 0);
      if (filledGroups.length === 0) {
        groupsEl.innerHTML = `<div class="pt-mi-empty"><i class="fa-solid fa-circle-info" style="margin-right:8px;color:var(--pt-muted2)"></i>No meta or Open Graph tags were found on this page.</div>`;
      } else {
        groupsEl.innerHTML = filledGroups.map(([, g]) => `
          <div class="pt-mi-group">
            <div class="pt-mi-group-head">
              <i class="fa-solid fa-${escAttr(g.icon)}"></i>
              ${escHtml(g.title)}
              <span class="pt-mi-group-count">${g.tags.length}</span>
            </div>
            <table class="pt-mi-table">
              <thead><tr><th>Name / Property</th><th>Content / Value</th></tr></thead>
              <tbody>${g.tags.map(tag => `
                <tr class="pt-mi-tag-row">
                  <td class="pt-mi-tag-name"><code>${escHtml(tag.name)}</code></td>
                  <td class="pt-mi-tag-content">${renderTagContent(tag)}</td>
                </tr>`).join('')}
              </tbody>
            </table>
          </div>`).join('');
      }
    }

    $('ptMiResults').style.display = '';
    $('ptMiResults').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  // ── Output Tabs ────────────────────────────────────────────────────────────
  function switchOutTab(tab, btn) {
    state.outTab = tab;
    $$('.pt-otab').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    ['html','md','params'].forEach(t => {
      const el = $('otab_' + t);
      if (el) el.style.display = t === tab ? '' : 'none';
    });
  }

  // ── Copy ───────────────────────────────────────────────────────────────────
  function copy(inputId, btn) {
    const inp = $(inputId);
    if (!inp) return;
    navigator.clipboard.writeText(inp.value).then(() => {
      if (btn) {
        btn.classList.add('copied');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        setTimeout(() => { btn.classList.remove('copied'); btn.innerHTML = orig; }, 2000);
      }
    });
  }

  // ── Download ───────────────────────────────────────────────────────────────
  function downloadImage() {
    const fmt = $('f_format').value;
    const url = buildURL(fmt);
    const a = document.createElement('a');
    a.href = url;
    a.download = `preview-${state.template}.${fmt}`;
    a.target = '_blank';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  }

  // ── Open Preview ───────────────────────────────────────────────────────────
  function openPreview() {
    window.open(buildURL($('f_format').value), '_blank');
  }

  // ── Set Scale ──────────────────────────────────────────────────────────────
  function setScale(scale, btn) {
    state.scale = scale;
    $$('.pt-dev').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    const wrap = $('ptPreviewWrap');
    if (wrap) {
      if (scale < 1) {
        wrap.style.transform = `scale(${scale})`;
        wrap.style.transformOrigin = 'top center';
        $('ptPreviewFrame').style.minHeight = `${200 * scale + 40}px`;
      } else {
        wrap.style.transform = '';
        wrap.style.transformOrigin = '';
        $('ptPreviewFrame').style.minHeight = '';
      }
    }
  }

  // ── Toggle Section ─────────────────────────────────────────────────────────
  function toggleSection(head) {
    head.classList.toggle('collapsed');
    const body = head.nextElementSibling;
    if (body) body.classList.toggle('hidden');
  }

  // ── Randomize ──────────────────────────────────────────────────────────────
  const RANDOM_COLORS = [
    ['0d1117','ffffff','94a3b8','22c55e'],
    ['1e1b4b','ffffff','c4b5fd','a78bfa'],
    ['0f172a','e2e8f0','64748b','38bdf8'],
    ['030712','4ade80','6ee7b7','4ade80'],
    ['18181b','ffffff','a1a1aa','f97316'],
    ['ffffff','111827','6b7280','4f46e5'],
    ['f8fafc','1e293b','64748b','2563eb'],
    ['0a66c2','ffffff','e7f0f9','ffffff'],
    ['15202b','ffffff','8b98a5','1d9bf0'],
    ['7c3aed','ffffff','e9d5ff','fbbf24'],
    ['0ea5e9','ffffff','e0f2fe','fbbf24'],
    ['282a36','f8f8f2','6272a4','bd93f9'],
  ];

  const RANDOM_HEADINGS = [
    'Developer Toolkit','Open Source Tools','Build Better Apps',
    'Ship Faster Today','Modern Design System','API First Platform',
    'Zero Config Setup','Cloud Native Stack','Full Stack Magic',
    'Design to Code','Performance First','Security by Default',
  ];
  const RANDOM_DESCS = [
    '200+ tools to supercharge your development workflow.',
    'Built with passion for developers who ship things.',
    'The fastest way to build and deploy modern applications.',
    'Everything you need to build great products.',
    'Powerful, flexible, and beautiful by default.',
    'Open source tools trusted by thousands of developers.',
  ];
  const RANDOM_ICONS = ['rocket','bolt','star','fire','crown','gem','magic','atom','brain','code','globe','terminal','server','shield','bolt','wand-magic-sparkles'];

  function randomize() {
    const colors = RANDOM_COLORS[Math.floor(Math.random() * RANDOM_COLORS.length)];

    // Pick template FIRST — selectTemplate calls applyTemplateDefaults which resets
    // colours, so we must run it before applying our random overrides.
    const tpls = Object.keys(PT_CATS[state.category]?.templates || {});
    if (tpls.length > 1) {
      const current = state.template;
      const others  = tpls.filter(t => t !== current);
      selectTemplate(state.category, others[Math.floor(Math.random() * others.length)]);
    }

    // Override with random colours after applyTemplateDefaults has run
    setColor('bg',          '#' + colors[0]);
    setColor('heading',     '#' + colors[1]);
    setColor('description', '#' + colors[2]);
    setColor('accent',      '#' + colors[3]);

    const h = $('f_heading');
    if (h) h.value = RANDOM_HEADINGS[Math.floor(Math.random() * RANDOM_HEADINGS.length)];
    const d = $('f_description');
    if (d) d.value = RANDOM_DESCS[Math.floor(Math.random() * RANDOM_DESCS.length)];
    const i = $('f_icon');
    if (i) i.value = RANDOM_ICONS[Math.floor(Math.random() * RANDOM_ICONS.length)];

    update();
  }

  // ── Reset ──────────────────────────────────────────────────────────────────
  // ── Cache management ────────────────────────────────────────────────────────

  function loadCacheStats() {
    var badge = document.getElementById('ptCacheBadge');
    if (!badge) return;
    fetch('?action=cache_stats')
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d.count === 0) {
          badge.textContent = '0';
        } else {
          badge.textContent = d.count + ' · ' + d.human;
        }
      })
      .catch(function(){ badge.textContent = '—'; });
  }

  function clearCache() {
    var btn   = document.getElementById('ptCacheBtn');
    var badge = document.getElementById('ptCacheBadge');
    if (!btn) return;
    btn.classList.add('pt-cache-clearing');
    badge.textContent = '…';
    fetch('?action=clear_cache')
      .then(function(r){ return r.json(); })
      .then(function(d){
        badge.textContent = '0';
        btn.classList.remove('pt-cache-clearing');
        // brief flash to confirm
        btn.style.borderColor = '#22c55e';
        btn.style.color = '#22c55e';
        setTimeout(function(){
          btn.style.borderColor = '';
          btn.style.color = '';
        }, 1200);
      })
      .catch(function(){
        btn.classList.remove('pt-cache-clearing');
        badge.textContent = '?';
      });
  }

  function resetToDefaults() {
    applyTemplateDefaults(state.category, state.template);
    const catData = PT_CATS[state.category];
    if (catData) {
      if ($('f_width'))  $('f_width').value  = catData.default_width  || 1200;
      if ($('f_height')) $('f_height').value = catData.default_height || 630;
    }
    // Reset text fields to defaults
    if ($('f_heading'))     $('f_heading').value     = 'Developer Toolkit';
    if ($('f_description')) $('f_description').value = '200+ developer tools to supercharge your workflow.';
    if ($('f_footer'))      $('f_footer').value      = 'awantools.site';
    if ($('f_badge'))       $('f_badge').value       = '';
    if ($('f_subheading'))  $('f_subheading').value  = '';
    if ($('f_icon'))        $('f_icon').value        = 'code';
    if ($('f_radius'))      { $('f_radius').value = 20; updateSlider('radius', 20); }
    if ($('f_padding'))     { $('f_padding').value = 60; updateSlider('padding', 60); }
    update();
  }

  // ── Build Example URLs ─────────────────────────────────────────────────────
  function buildExamples() {
    const list = $('ptExampleList');
    if (!list) return;
    list.innerHTML = EXAMPLE_TEMPLATES.slice(0, 5).map(ex => {
      const p = {
        category: ex.category,
        template: ex.template,
        icon: ex.icon,
        heading: ex.heading,
        description: ex.description,
        footer: 'awantools.site',
        format: 'png',
        width: 1200,
        height: 630,
        ...(PT_CATS[ex.category]?.defaults?.[ex.template] || {}),
      };
      const q = Object.entries(p)
        .filter(([,v]) => v !== '')
        .map(([k,v]) => encodeURIComponent(k) + '=' + encodeURIComponent(v))
        .join('&');
      const url = PT_BASE_URL + '?' + q;
      return `<div class="pt-example-item" onclick="PT.useExample('${ex.category}','${ex.template}')" title="${url}">
        <span class="pt-example-url">${url.replace(PT_BASE_URL, '.../')}</span>
        <i class="fa-solid fa-arrow-right"></i>
      </div>`;
    }).join('');
  }

  function useExample(cat, tpl) {
    selectCategory(cat, tpl);
  }

  // ── Keyboard Shortcuts ─────────────────────────────────────────────────────
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeIconPicker();
    if ((e.ctrlKey || e.metaKey) && e.key === 'c' && document.activeElement === $('ptUrlOut')) {
      copy('ptUrlOut', null);
    }
  });

  // ── Init ───────────────────────────────────────────────────────────────────
  function init() {
    // Event listeners for category tabs
    document.querySelectorAll('.pt-cat').forEach(btn => {
      btn.addEventListener('click', () => selectCategory(btn.dataset.cat));
    });

    // Width/Height change tracking
    ['f_width','f_height'].forEach(id => {
      const el = $(id);
      if (el) el.addEventListener('input', () => { el.dataset.userSet = '1'; update(); });
    });

    // Init slider displays
    updateSlider('radius', $('f_radius')?.value || 20);
    updateSlider('padding', $('f_padding')?.value || 60);

    // Build example URLs
    buildExamples();

    // Start in Generate mode, then render the first category + template dynamically
    selectMode('generate');
    const firstCat = Object.keys(PT_CATS)[0] || 'og';
    const firstTpl = Object.keys(PT_CATS[firstCat]?.templates || {})[0] || 'default';
    selectCategory(firstCat, firstTpl);

    // Load cache stats into button badge
    loadCacheStats();
  }

  // ── Public API ─────────────────────────────────────────────────────────────
  window.PT = {
    update,
    selectMode,
    selectCategory,
    selectTemplate,
    syncColor,
    syncColorText,
    updateSlider,
    openIconPicker,
    closeIconPicker,
    filterIcons,
    pickIcon,
    switchOutTab,
    copy,
    downloadImage,
    openPreview,
    setScale,
    toggleSection,
    randomize,
    resetToDefaults,
    useExample,
    clearCache,
    // Meta Inspector
    inspectMeta,
    miQuick,
  };

  // ── Wait for DOM ───────────────────────────────────────────────────────────
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
