/* ═══════════════════════════════════════════════════════════════
   Previewer Toolkit — Client-side Logic
   ═══════════════════════════════════════════════════════════════ */

const PT = (() => {

  // ── State ──────────────────────────────────────────────────────
  let state = {
    category:      'og',
    template:      'github_dark',
    zoom:          1,
    debounceTimer: null,
    loadingTimer:  null,
    thumbObserver: null,
  };

  // PT_RENDER_BASE injected by index.php as a global before this script loads.
  // Do NOT redeclare it here — that would shadow the absolute path with 'render',
  // breaking image URLs when the page is served without a trailing slash.

  // ── Template registry ──────────────────────────────────────────
  // Server-authoritative copy is injected by index.php as window.PT_REGISTRY,
  // generated from plugins/previewer-toolkit/templates.php (the single source
  // of truth also used by render.php). The literal object below is only a
  // fallback for resilience if that injection is ever missing.
  const REGISTRY = window.PT_REGISTRY || {
    og:            { templates: ['github_dark','github_light','glass_modern','minimal_clean','gradient_pro','corporate','neon_dark','startup','retro_sunset','ocean','aurora','newspaper','blueprint','dark_amber','cyberpunk','forest','indie','mono','candy','steel','ocean_wave'], defaultW:1200, defaultH:630 },
    social:        { templates: ['twitter','linkedin','discord','telegram','announcement','product_launch','feature_highlight','blog_post','youtube','instagram','facebook','reddit','hackernews','product_hunt','dribbble','newsletter','event','job_post'], defaultW:1200, defaultH:630 },
    placeholder:   { templates: ['simple','grid','gradient','glass','pattern','minimal','modern','empty_state','blueprint_grid','crosshatch','circuit','polka_dots','diagonal_stripes','noise_field','sketch','dots_dark','gradient_mesh','marble'], defaultW:800, defaultH:600 },
    browser:       { templates: ['chrome','firefox','safari','edge','arc','generic','brave','opera','vivaldi','dark_mode','minimal_browser','retro_browser','high_contrast','material','warm_light'], defaultW:1200, defaultH:800 },
    terminal:      { templates: ['linux','modern','hacker','vscode','minimal','powerline','fish_shell','windows_cmd','powershell','ubuntu_term','matrix','amber','iterm2','p10k','dracula_term'], defaultW:900, defaultH:600 },
    profile:       { templates: ['team_member','author','developer','business','creator','speaker','minimal_white','dark_glass','gradient_card','resume_clean','podcast_card','athlete','musician','freelancer','noir'], defaultW:900, defaultH:500 },
    code:          { templates: ['vscode','github','monokai','nord','dracula','minimal','one_dark','synthwave','gruvbox','solarized','tokyo_night','catppuccin','atom_light','sublime','jetbrains'], defaultW:1000, defaultH:600 },
    dashboard:     { templates: ['analytics','saas','stats','kpi','revenue','admin','marketing','crypto','fitness','ecommerce','social_dash','devops','project_dash','finance','monitoring'], defaultW:1200, defaultH:630 },
    docs:          { templates: ['api','readme','changelog','product','developer','knowledge','tutorial','component_doc','library_pkg','cli_doc','guide_doc','reference_doc','faq_doc','notes_doc','quickstart'], defaultW:1200, defaultH:630 },
    github:        { templates: ['repo','package','release','open_source','org','project','stars_showcase','npm_card','contribution_card','profile_readme','docker_card','pr_card','issue_card','workflow_card','monorepo'], defaultW:1200, defaultH:630 },
    business_card: { templates: ['wave_dark','corporate_stripe','minimal_biz','creative_split','tech_grid','luxury_foil'], defaultW:1050, defaultH:600 },
    id_card:       { templates: ['corporate_dark','corporate_red','student_teal','minimal_badge','access_badge','gov_blue'], defaultW:600, defaultH:900 },
    invitation:    { templates: ['vintage_cream','luxury_dark','birthday_fun','wedding_elegant','corporate_event','garden_party'], defaultW:1200, defaultH:800 },
  };

  // ── Template labels ────────────────────────────────────────────
  const TPL_LABELS = {
    // OG
    github_dark:'GitHub Dark', github_light:'GitHub Light', glass_modern:'Glass Modern',
    minimal_clean:'Minimal Clean', gradient_pro:'Gradient Pro', corporate:'Corporate',
    neon_dark:'Neon Dark', startup:'Startup', retro_sunset:'Retro Sunset', ocean:'Ocean Wave',
    aurora:'Aurora', newspaper:'Newspaper', blueprint:'Blueprint', dark_amber:'Dark Amber',
    cyberpunk:'Cyberpunk', forest:'Forest Dark', indie:'Indie', mono:'Monochrome',
    candy:'Candy', steel:'Steel', ocean_wave:'Ocean Wave',
    // Social
    twitter:'Twitter / X', linkedin:'LinkedIn', discord:'Discord', telegram:'Telegram',
    announcement:'Announcement', product_launch:'Product Launch',
    feature_highlight:'Feature', blog_post:'Blog Post',
    youtube:'YouTube', instagram:'Instagram', facebook:'Facebook', reddit:'Reddit',
    hackernews:'Hacker News', product_hunt:'Product Hunt', dribbble:'Dribbble',
    newsletter:'Newsletter', event:'Event Card', job_post:'Job Post',
    // Placeholder
    simple:'Simple', grid:'Grid', gradient:'Gradient', glass:'Glass',
    pattern:'Stripe Pattern', minimal:'Minimal', modern:'Modern', empty_state:'Empty State',
    blueprint_grid:'Blueprint Grid', crosshatch:'Crosshatch', circuit:'Circuit Board',
    polka_dots:'Polka Dots', diagonal_stripes:'Diagonal Stripes', noise_field:'Noise Field',
    sketch:'Sketch', dots_dark:'Dots Dark', gradient_mesh:'Gradient Mesh', marble:'Marble',
    // Browser
    chrome:'Chrome', firefox:'Firefox', safari:'Safari', edge:'Edge', arc:'Arc',
    generic:'Generic', brave:'Brave', opera:'Opera', vivaldi:'Vivaldi',
    dark_mode:'Dark Mode', minimal_browser:'Minimal', retro_browser:'Retro Win95',
    high_contrast:'High Contrast', material:'Material Blue', warm_light:'Warm Light',
    // Terminal
    linux:'Linux', hacker:'Hacker', vscode:'VS Code',
    powerline:'Powerline', fish_shell:'Fish Shell', windows_cmd:'Windows CMD',
    powershell:'PowerShell', ubuntu_term:'Ubuntu', matrix:'Matrix',
    amber:'Amber CRT', iterm2:'iTerm2', p10k:'Powerlevel10k', dracula_term:'Dracula',
    // Profile
    team_member:'Team Member', author:'Author', developer:'Developer',
    business:'Business', creator:'Creator', speaker:'Speaker',
    minimal_white:'Minimal White', dark_glass:'Dark Glass', gradient_card:'Gradient Card',
    resume_clean:'Resume', podcast_card:'Podcast', athlete:'Athlete',
    musician:'Musician', freelancer:'Freelancer', noir:'Noir',
    // Business Card
    wave_dark:'Wave Dark', corporate_stripe:'Corporate Stripe', minimal_biz:'Minimal',
    creative_split:'Creative Split', tech_grid:'Tech Grid', luxury_foil:'Luxury Foil',
    // ID Card
    corporate_dark:'Corporate Dark', corporate_red:'Corporate Red', student_teal:'Student Teal',
    minimal_badge:'Minimal Badge', access_badge:'Access Badge', gov_blue:'Gov Blue',
    // Invitation
    vintage_cream:'Vintage Cream', luxury_dark:'Luxury Dark', birthday_fun:'Birthday Fun',
    wedding_elegant:'Wedding Elegant', corporate_event:'Corporate Event', garden_party:'Garden Party',
    // Code
    monokai:'Monokai', nord:'Nord', dracula:'Dracula',
    one_dark:'One Dark', synthwave:'Synthwave 84', gruvbox:'Gruvbox',
    solarized:'Solarized Dark', tokyo_night:'Tokyo Night', catppuccin:'Catppuccin',
    atom_light:'Atom Light', sublime:'Sublime', jetbrains:'JetBrains',
    // Dashboard
    analytics:'Analytics', saas:'SaaS', stats:'Statistics', kpi:'KPI',
    revenue:'Revenue', admin:'Admin Panel',
    marketing:'Marketing', crypto:'Crypto', fitness:'Fitness', ecommerce:'E-Commerce',
    social_dash:'Social Media', devops:'DevOps', project_dash:'Project Mgmt',
    finance:'Finance', monitoring:'Monitoring',
    // Docs
    api:'API Docs', readme:'README', changelog:'Changelog', product:'Product',
    knowledge:'Knowledge Base',
    tutorial:'Tutorial', component_doc:'Component', library_pkg:'Library',
    cli_doc:'CLI Tool', guide_doc:'Guide', reference_doc:'Reference',
    faq_doc:'FAQ', notes_doc:'Release Notes', quickstart:'Quick Start',
    // GitHub
    repo:'Repository', package:'Package', release:'Release',
    open_source:'Open Source', org:'Organization', project:'Project',
    stars_showcase:'Stars Showcase', npm_card:'npm Package', contribution_card:'Contributions',
    profile_readme:'Profile README', docker_card:'Docker Hub', pr_card:'Pull Request',
    issue_card:'Issue', workflow_card:'GitHub Action', monorepo:'Monorepo',
  };

  // ── Content field groups per category ──────────────────────────
  // Which static groups to show in the Content section
  const CAT_GROUPS = {
    og:            ['base','badge','icon','author','extra'],
    social:        ['base','badge','icon','author','extra'],
    placeholder:   ['base','badge','icon'],
    browser:       ['base'],
    terminal:      [],
    profile:       ['base','badge','author'],
    code:          ['badge'],
    dashboard:     ['base'],
    docs:          ['base','badge','icon'],
    github:        ['base','badge'],
    business_card: ['base','badge','author','extra'],
    id_card:       ['base','author'],
    invitation:    ['base','badge','author','extra'],
  };

  // ── Extra (dynamic) field definitions per category ─────────────
  const EXTRA_FIELDS = {
    terminal: [
      { id:'p_filename',   label:'Terminal title', type:'text', placeholder:'bash' },
      { id:'p_line1',      label:'Line 1 (command)', type:'text', placeholder:'$ echo "Hello World"' },
      { id:'p_line2',      label:'Line 2 (output)',  type:'text', placeholder:'Hello World' },
      { id:'p_line3',      label:'Line 3 (command)', type:'text', placeholder:'$ npm start' },
      { id:'p_line4',      label:'Line 4 (output)',  type:'text', placeholder:'Server running on :3000' },
    ],
    browser: [
      { id:'p_url_bar', label:'URL bar text', type:'text', placeholder:'https://example.com' },
    ],
    code: [
      { id:'p_filename', label:'Filename (tab)',  type:'text',     placeholder:'index.js' },
      { id:'p_lang',     label:'Language hint',   type:'text',     placeholder:'js' },
      { id:'p_code',     label:'Code content',    type:'textarea', placeholder:'function hello() {\n  return "world";\n}' },
    ],
    profile: [
      { id:'p_username',     label:'Username / handle', type:'text', placeholder:'@johndoe' },
      { id:'p_role',         label:'Role / title',      type:'text', placeholder:'Full-Stack Developer' },
      { id:'p_stat1_label',  label:'Stat 1 label', type:'text', placeholder:'Posts' },
      { id:'p_stat1_value',  label:'Stat 1 value', type:'text', placeholder:'128' },
      { id:'p_stat2_label',  label:'Stat 2 label', type:'text', placeholder:'Followers' },
      { id:'p_stat2_value',  label:'Stat 2 value', type:'text', placeholder:'4.2k' },
      { id:'p_stat3_label',  label:'Stat 3 label', type:'text', placeholder:'Stars' },
      { id:'p_stat3_value',  label:'Stat 3 value', type:'text', placeholder:'892' },
    ],
    github: [
      { id:'p_username', label:'Username / org', type:'text', placeholder:'octocat' },
      { id:'p_stars',    label:'Stars',          type:'text', placeholder:'1.2k' },
      { id:'p_forks',    label:'Forks',          type:'text', placeholder:'234' },
      { id:'p_version',  label:'Version',        type:'text', placeholder:'v1.0.0' },
      { id:'p_lang',     label:'Language',       type:'text', placeholder:'TypeScript' },
    ],
    dashboard: [
      { id:'p_description',   label:'Chart title',    type:'text', placeholder:'Monthly Overview' },
      { id:'p_metric1',       label:'Metric 1 value', type:'text', placeholder:'24,891' },
      { id:'p_metric1_label', label:'Metric 1 label', type:'text', placeholder:'Total Users' },
      { id:'p_metric2',       label:'Metric 2 value', type:'text', placeholder:'+12.4%' },
      { id:'p_metric2_label', label:'Metric 2 label', type:'text', placeholder:'Growth' },
      { id:'p_metric3',       label:'Metric 3 value', type:'text', placeholder:'$8,240' },
      { id:'p_metric3_label', label:'Metric 3 label', type:'text', placeholder:'Revenue' },
    ],
    docs: [
      { id:'p_version',        label:'Version badge', type:'text', placeholder:'v2.0.0' },
      { id:'p_category_label', label:'Section label', type:'text', placeholder:'Getting Started' },
    ],
    business_card: [
      { id:'p_username', label:'Address / Extra',   type:'text', placeholder:'City, State' },
    ],
    id_card: [
      { id:'p_role',     label:'Department / Role', type:'text', placeholder:'Computer Science' },
      { id:'p_username', label:'ID Number',         type:'text', placeholder:'ID-123-456-7890' },
      { id:'p_date',     label:'Date (DOB / Issue)', type:'text', placeholder:'01 January 2000' },
    ],
  };

  // ── Thumbnail default content per category ─────────────────────
  const THUMB_DEFAULTS = {
    og:          'heading=Preview+Template&description=Professional+social+image+for+sharing&icon=code',
    social:      'heading=Social+Card&description=Engage+your+audience&icon=share-nodes',
    placeholder: 'heading=Placeholder+Image',
    browser:     'heading=Browser+Mockup&url_bar=https%3A%2F%2Fexample.com',
    terminal:    'line1=%24+npm+run+build&line2=Built+successfully+in+2.3s&line3=%24+git+push+origin+main&line4=remote%3A+100%25+done',
    profile:     'heading=Jane+Developer&role=Full-Stack+Engineer&stat1_value=128&stat1_label=Posts&stat2_value=4.2k&stat2_label=Followers&stat3_value=892&stat3_label=Stars',
    code:        'filename=index.ts&lang=typescript',
    dashboard:   'heading=Dashboard&metric1=24%2C891&metric1_label=Users&metric2=%2B12.4%25&metric2_label=Growth&metric3=%248%2C240&metric3_label=Revenue&description=Monthly+Overview',
    docs:        'heading=Documentation&description=API+Reference+%26+Guides&version=v2.0.0&category_label=Getting+Started',
    github:        'heading=my-project&description=An+awesome+open-source+project&username=octocat&stars=1.2k&forks=234&lang=TypeScript&version=v1.0.0',
    business_card: 'heading=Jane+Smith&description=Creative+Director&author=Acme+Corp&website=jane.design&footer=%2B1+555+000+0000&badge=hello%40jane.design',
    id_card:       'heading=Daniel+Garcia&author=Borcelle+Inc.&role=Marketing+Director&username=ID-123-456-7890',
    invitation:    'heading=Birthday+Gala&description=An+elegant+evening+celebration&author=Olivia+Wilson&website=The+Grand+Hotel&footer=20+August+%C2%B7+7+PM&badge=Black+Tie',
  };

  // ── Thumbnail URL builder ──────────────────────────────────────
  function thumbUrl(cat, tpl) {
    const reg = REGISTRY[cat] || {};
    const W = reg.defaultW || 1200, H = reg.defaultH || 630;
    const tw = 300, th = Math.round(300 * H / W);
    const defaults = THUMB_DEFAULTS[cat] || 'heading=Preview';
    return `${PT_RENDER_BASE}?category=${cat}&template=${tpl}&width=${tw}&height=${th}&format=webp&${defaults}`;
  }

  // ── Init ───────────────────────────────────────────────────────
  function init() {
    renderTemplateGrid('og');
    selectCategory('og');
    selectTemplate('github_dark', false);
    restoreFromHash();
    update();
    setupImageLoadTracking();
    setupThumbObserver();
  }

  function setupImageLoadTracking() {
    const img = document.getElementById('ptPreviewImg');
    img.addEventListener('load',  () => hideLoading());
    img.addEventListener('error', () => {
      hideLoading();
      img.style.opacity = '0.3';
    });
  }

  function setupThumbObserver() {
    if (!('IntersectionObserver' in window)) {
      // Fallback: load all thumbs immediately
      document.querySelectorAll('.pt-tpl-thumb[data-src]').forEach(img => {
        img.src = img.dataset.src; delete img.dataset.src;
      });
      return;
    }
    if (state.thumbObserver) state.thumbObserver.disconnect();
    state.thumbObserver = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          const img = e.target;
          if (img.dataset.src) { img.src = img.dataset.src; delete img.dataset.src; }
          state.thumbObserver.unobserve(img);
        }
      });
    }, { rootMargin: '100px' });
    document.querySelectorAll('.pt-tpl-thumb[data-src]').forEach(img => {
      state.thumbObserver.observe(img);
    });
  }

  // ── Selector / tool open ───────────────────────────────────────
  function openTool(tab) {
    document.getElementById('ptSelector').style.display = 'none';
    document.getElementById('ptTopbar').style.display   = 'flex';
    document.querySelectorAll('.pt-tab-panel').forEach(p => p.style.display = 'none');
    document.getElementById('pt-' + tab).style.display = 'flex';
    document.querySelectorAll('.pt-tab-btn').forEach(b => {
      b.classList.toggle('pt-tab-active', b.dataset.tab === tab);
    });
  }

  function backToSelector() {
    document.querySelectorAll('.pt-tab-panel').forEach(p => p.style.display = 'none');
    document.getElementById('ptTopbar').style.display   = 'none';
    document.getElementById('ptSelector').style.display = 'flex';
  }

  // ── Tab switching ──────────────────────────────────────────────
  function switchTab(tab, btn) {
    document.querySelectorAll('.pt-tab-panel').forEach(p => p.style.display = 'none');
    document.getElementById('pt-' + tab).style.display = 'flex';
    if (btn) document.querySelectorAll('.pt-tab-btn').forEach(b => b.classList.remove('pt-tab-active'));
    if (btn) btn.classList.add('pt-tab-active');
  }

  // ── Category selection ─────────────────────────────────────────
  function selectCategory(cat) {
    state.category = cat;
    // Update chip active state
    document.querySelectorAll('.pt-cat-btn').forEach(b =>
      b.classList.toggle('pt-cat-active', b.dataset.cat === cat)
    );
    renderTemplateGrid(cat);
    // Auto-select first template
    const firstTpl = REGISTRY[cat]?.templates[0];
    if (firstTpl) selectTemplate(firstTpl, false);
    renderExtraFields(cat);
    showContentGroups(cat);
    // Update dimensions
    const reg = REGISTRY[cat];
    if (reg) {
      const wEl = document.getElementById('p_width'), hEl = document.getElementById('p_height');
      if (wEl && !wEl.dataset.manual) wEl.value = reg.defaultW;
      if (hEl && !hEl.dataset.manual) hEl.value = reg.defaultH;
    }
    update();
  }

  // ── Template grid with thumbnails ──────────────────────────────
  function renderTemplateGrid(cat) {
    const grid = document.getElementById('ptTplGrid');
    if (!grid) return;
    const templates = REGISTRY[cat]?.templates || [];
    grid.innerHTML = templates.map(tpl => {
      const label = TPL_LABELS[tpl] || tpl.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
      const url   = thumbUrl(cat, tpl);
      const isActive = tpl === state.template;
      return `<button class="pt-tpl-btn${isActive ? ' pt-tpl-active' : ''}" data-tpl="${tpl}"
          onclick="PT.selectTemplate('${tpl}', true)">
        <div class="pt-tpl-thumb-wrap">
          <img class="pt-tpl-thumb" data-src="${url}" alt="${label}" loading="lazy">
          <div class="pt-tpl-thumb-shimmer"></div>
        </div>
        <span class="pt-tpl-label">${label}</span>
      </button>`;
    }).join('');

    // Re-attach IntersectionObserver for new thumbs
    if (state.thumbObserver) {
      grid.querySelectorAll('.pt-tpl-thumb[data-src]').forEach(img => {
        state.thumbObserver.observe(img);
      });
    } else {
      setupThumbObserver();
    }
  }

  // ── Template selection ─────────────────────────────────────────
  function selectTemplate(tpl, doUpdate = true) {
    state.template = tpl;
    // Highlight active button
    document.querySelectorAll('.pt-tpl-btn').forEach(b => {
      b.classList.toggle('pt-tpl-active', b.dataset.tpl === tpl);
    });
    // Scroll to active button
    const activeBtn = document.querySelector('.pt-tpl-btn.pt-tpl-active');
    if (activeBtn) activeBtn.scrollIntoView({ behavior:'smooth', block:'nearest', inline:'nearest' });
    if (doUpdate) update();
  }

  // ── Content group show/hide ────────────────────────────────────
  function showContentGroups(cat) {
    const groups = CAT_GROUPS[cat] || ['base','badge','icon','author','extra'];
    document.querySelectorAll('#ptContentSection .pt-fgroup').forEach(el => {
      const g = el.dataset.group;
      el.style.display = (g && groups.includes(g)) ? '' : 'none';
    });
  }

  // ── Extra fields ───────────────────────────────────────────────
  function renderExtraFields(cat) {
    const wrap = document.getElementById('ptExtraSection');
    const container = document.getElementById('ptExtraFields');
    if (!wrap || !container) return;
    const fields = EXTRA_FIELDS[cat] || [];
    if (!fields.length) {
      wrap.style.display = 'none';
      container.innerHTML = '';
      return;
    }
    wrap.style.display = '';
    container.innerHTML = fields.map(f => {
      if (f.type === 'textarea') {
        return `<div class="pt-field">
          <label>${f.label}</label>
          <textarea id="${f.id}" class="pt-input pt-textarea" rows="5"
            oninput="PT.debounce()" placeholder="${f.placeholder?.replace(/"/g,'&quot;') || ''}"
          >${escHtml(document.getElementById(f.id)?.value || '')}</textarea>
        </div>`;
      }
      const existing = document.getElementById(f.id);
      const val = existing ? existing.value : '';
      return `<div class="pt-field">
        <label>${f.label}</label>
        <input type="text" id="${f.id}" class="pt-input" value="${escHtml(val)}"
          oninput="PT.debounce()" placeholder="${f.placeholder?.replace(/"/g,'&quot;') || ''}">
      </div>`;
    }).join('');
  }

  // ── Build URL params ───────────────────────────────────────────
  function buildParams() {
    const reg = REGISTRY[state.category];
    const W = parseInt(document.getElementById('p_width')?.value)  || reg?.defaultW || 1200;
    const H = parseInt(document.getElementById('p_height')?.value) || reg?.defaultH || 630;

    const ids = [
      'p_heading','p_description','p_badge','p_icon',
      'p_website','p_author','p_footer','p_watermark',
      // terminal
      'p_filename','p_line1','p_line2','p_line3','p_line4',
      // browser
      'p_url_bar',
      // code
      'p_lang','p_code',
      // profile
      'p_username','p_role',
      'p_stat1_label','p_stat1_value',
      'p_stat2_label','p_stat2_value',
      'p_stat3_label','p_stat3_value',
      // github/docs
      'p_stars','p_forks','p_version','p_category_label',
      // id_card
      'p_date',
      // dashboard
      'p_metric1','p_metric1_label',
      'p_metric2','p_metric2_label',
      'p_metric3','p_metric3_label',
      // style sliders
      'p_font_size','p_padding','p_radius',
    ];

    const params = new URLSearchParams();
    params.set('category', state.category);
    params.set('template', state.template);
    params.set('width',    W);
    params.set('height',   H);
    params.set('format',   document.getElementById('p_format')?.value || 'png');

    ids.forEach(id => {
      const el = document.getElementById(id);
      if (el && el.value !== '') {
        const key = id.replace(/^p_/, '');
        params.set(key, el.value);
      }
    });

    // Color pickers → render params (pc_bg → bg_color, etc.)
    const colorMap = {
      'pc_bg':      'bg_color',
      'pc_accent':  'accent_color',
      'pc_heading': 'heading_color',
      'pc_desc':    'description_color',
    };
    Object.entries(colorMap).forEach(([id, param]) => {
      const el = document.getElementById(id);
      if (el) params.set(param, el.value.replace('#', ''));
    });

    return params;
  }

  // ── Update preview ─────────────────────────────────────────────
  function update() {
    const params  = buildParams();
    const qs      = params.toString();
    const url     = `${PT_RENDER_BASE}?${qs}`;
    const img     = document.getElementById('ptPreviewImg');
    if (!img) return;
    showLoading();
    img.style.opacity = '0.6';
    img.src = url;
    img.onload = () => { img.style.opacity = '1'; hideLoading(); };
    // Write full URL to the URL output field
    const urlOut = document.getElementById('ptUrlOutput');
    if (urlOut) {
      const base = location.href.replace(/\/[^/]*$/, '/').replace(/\?.*$/, '');
      urlOut.value = base + 'render?' + qs;
    }
    // Update hash (for restore-from-hash)
    location.hash = encodeURIComponent(qs).slice(0, 500);
  }

  function debounce() {
    clearTimeout(state.debounceTimer);
    state.debounceTimer = setTimeout(update, 350);
  }

  function refreshPreview() { update(); }

  function showLoading() {
    clearTimeout(state.loadingTimer);
    state.loadingTimer = setTimeout(() => {
      const el = document.getElementById('ptLoading');
      if (el) el.style.display = 'flex';
    }, 200);
  }

  function hideLoading() {
    clearTimeout(state.loadingTimer);
    const el = document.getElementById('ptLoading');
    if (el) el.style.display = 'none';
  }

  // ── Restore from hash ──────────────────────────────────────────
  function restoreFromHash() {
    if (!location.hash) return;
    try {
      const params = new URLSearchParams(decodeURIComponent(location.hash.slice(1)));
      const cat = params.get('category'), tpl = params.get('template');
      if (cat && REGISTRY[cat]) {
        state.category = cat;
        renderTemplateGrid(cat);
        showContentGroups(cat);
        renderExtraFields(cat);
        document.querySelectorAll('.pt-cat-btn').forEach(b =>
          b.classList.toggle('pt-cat-active', b.dataset.cat === cat)
        );
      }
      if (tpl) selectTemplate(tpl, false);
      params.forEach((val, key) => {
        const el = document.getElementById('p_' + key);
        if (el) el.value = val;
      });
    } catch (e) { /* ignore */ }
  }

  // ── Zoom (factor-based: 1.1 = zoom in, 0.9 = zoom out) ───────
  function zoom(factor) {
    state.zoom = Math.min(4, Math.max(0.125, state.zoom * factor));
    applyZoom();
  }

  function zoomFit() {
    const wrap = document.getElementById('ptPreviewWrap');
    const img  = document.getElementById('ptPreviewImg');
    if (!wrap || !img) return;
    const reg   = REGISTRY[state.category] || {};
    const imgW  = parseInt(document.getElementById('p_width')?.value) || reg.defaultW || 1200;
    const wrapW = wrap.clientWidth - 40;
    state.zoom  = Math.min(1, wrapW / imgW);
    applyZoom();
  }

  function applyZoom() {
    const img = document.getElementById('ptPreviewImg');
    if (img) {
      img.style.transform = `scale(${state.zoom})`;
      img.style.transformOrigin = 'top left';
    }
    const lbl = document.getElementById('ptZoomLabel');
    if (lbl) lbl.textContent = Math.round(state.zoom * 100) + '%';
  }

  // ── Actions ────────────────────────────────────────────────────
  function copyUrl() {
    const params = buildParams();
    const url = `${location.origin}${location.pathname.replace('index.php','').replace(/\/[^/]*$/, '/')}render?${params.toString()}`;
    navigator.clipboard.writeText(url).then(() => toast('URL copied!'));
  }

  function openUrl() {
    const params = buildParams();
    window.open(`${PT_RENDER_BASE}?${params.toString()}`, '_blank');
  }

  function download(fmt) {
    const params = buildParams();
    if (fmt) params.set('format', fmt);
    params.set('download', '1');
    window.location.href = `${PT_RENDER_BASE}?${params.toString()}`;
  }

  function setPreset(w, h) {
    const wEl = document.getElementById('p_width'), hEl = document.getElementById('p_height');
    if (wEl) { wEl.value = w; wEl.dataset.manual = '1'; }
    if (hEl) { hEl.value = h; hEl.dataset.manual = '1'; }
    document.querySelectorAll('.pt-preset-btn').forEach(b =>
      b.classList.toggle('pt-preset-active', +b.dataset.w === w && +b.dataset.h === h)
    );
    update();
  }

  function setIcon(ic) {
    const el = document.getElementById('p_icon');
    if (el) { el.value = ic; debounce(); }
  }

  // colorChange(paramName, colorInputEl) — called from index.php color pickers
  function colorChange(paramName, el) {
    const suffix = paramName.replace(/_color$/, '');
    const hexEl  = document.getElementById('pt_' + suffix + '_hex');
    if (hexEl) hexEl.value = el.value.replace('#', '');
    debounce();
  }

  // hexInput(paramName, colorInputId, hexInputEl)
  function hexInput(paramName, colorInputId, el) {
    const v = (el?.value ?? '').trim().replace('#', '');
    if (/^[0-9a-fA-F]{6}$/.test(v)) {
      const col = document.getElementById(colorInputId);
      if (col) col.value = '#' + v;
      debounce();
    }
  }

  // ── Toast ──────────────────────────────────────────────────────
  function toast(msg) {
    let t = document.getElementById('ptToast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'ptToast';
      t.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#1a1a1a;color:#fff;padding:10px 18px;border-radius:8px;font-size:13px;z-index:9999;opacity:0;transition:opacity .2s';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.opacity = '1';
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.style.opacity = '0', 2200);
  }

  // ── Meta Inspector ─────────────────────────────────────────────
  function setInspUrl(url) {
    const el = document.getElementById('ptInspUrl');
    if (el) el.value = url;
  }

  async function inspect() {
    const urlEl = document.getElementById('ptInspUrl');
    const out   = document.getElementById('ptInspMeta');
    if (!urlEl || !out) return;
    const url = urlEl.value.trim();
    if (!url) { out.innerHTML = '<p style="color:#e04">Enter a URL first.</p>'; return; }
    out.innerHTML = '<div class="pt-meta-loading">Fetching…</div>';
    try {
      const resp = await fetch(`meta.php?url=${encodeURIComponent(url)}`);
      if (!resp.ok) throw new Error('HTTP ' + resp.status);
      const data = await resp.json();
      out.innerHTML = renderMeta(data);
    } catch (e) {
      out.innerHTML = `<p style="color:#e04">Error: ${escHtml(e.message)}</p>`;
    }
  }

  function renderMeta(data) {
    const tags = data.tags || {};
    const og   = data.og   || {};
    const tw   = data.twitter || {};
    let html = '';
    if (data.og_image) {
      html += `<div class="pt-og-preview">
        <img src="${escHtml(data.og_image)}" class="pt-og-img" onerror="this.style.display='none'">
        <div class="pt-og-info">
          <div class="pt-og-title">${escHtml(og['og:title'] || tags.title || 'No title')}</div>
          <div class="pt-og-desc">${escHtml((og['og:description'] || tags.description || '').slice(0,150))}</div>
        </div>
      </div>`;
    }
    html += '<div class="pt-meta-rows">';
    if (tags.title)       html += row('Title', escHtml(tags.title));
    if (tags.description) html += row('Description', escHtml(tags.description));
    const ogKeys = ['og:title','og:description','og:image','og:url','og:type','og:site_name'];
    ogKeys.forEach(k => { if (og[k]) html += row(k, escHtml(og[k])); });
    const twKeys = ['twitter:card','twitter:title','twitter:description','twitter:image'];
    twKeys.forEach(k => { if (tw[k]) html += row(k, escHtml(tw[k])); });
    if (data.canonical) html += row('Canonical', escHtml(data.canonical));
    html += '</div>';
    return html;
  }

  function row(key, valHtml) {
    return `<div class="pt-meta-row"><div class="pt-meta-key">${key}</div><div class="pt-meta-val">${valHtml}</div></div>`;
  }

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Inject fade-in animation ───────────────────────────────────
  const style = document.createElement('style');
  style.textContent = '@keyframes pt-fadein{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}} @keyframes pt-shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}';
  document.head.appendChild(style);

  // ── Public API ─────────────────────────────────────────────────
  return {
    init, openTool, backToSelector, switchTab,
    selectCategory, selectTemplate,
    update, debounce, refreshPreview,
    zoom, zoomFit,
    copyUrl, openUrl, download,
    setPreset, setIcon, colorChange, hexInput,
    setInspUrl, inspect,
  };

})();

// ── Boot ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', PT.init);
