/* ==========================================================================
   dgt-features.js — All 10 smart features for Developer Generator Toolkit
   Loads after dev-generator-toolkit.js and hooks into DGT._hooks
   ========================================================================== */
(function () {
    'use strict';

    /* ── Storage keys ──────────────────────────────────────────────────── */
    var HIST_KEY = 'dgt_hist_v1';
    var FAVS_KEY = 'dgt_favs_v1';
    var PRST_KEY = 'dgt_prst_v1';
    var RCNT_KEY = 'dgt_rcnt_v1';

    /* ── State ─────────────────────────────────────────────────────────── */
    var histDB       = {};
    var favSet       = new Set();
    var presetDB     = {};
    var recentList   = [];   // toolIds, most-recent first
    var paletteOpen  = false;
    var paletteIdx   = 0;
    var paletteCurrent = [];

    /* Compose tool state */
    var composeFields = [
        { name:'id',    type:'uuid-v4'   },
        { name:'name',  type:'fake-name' },
        { name:'email', type:'fake-email'},
    ];
    var composeCount = 5;

    /* Enricher state */
    var enrichAddFields = [{ col:'id', type:'uuid-v4' }];

    /* ── Helpers ───────────────────────────────────────────────────────── */
    function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function $id(id){ return document.getElementById(id); }
    function toast(msg){ if(DGT._toast) DGT._toast(msg); }

    /* ── Storage ───────────────────────────────────────────────────────── */
    function loadAll() {
        try { histDB     = JSON.parse(localStorage.getItem(HIST_KEY)||'{}'); }    catch(e){ histDB={}; }
        try { favSet     = new Set(JSON.parse(localStorage.getItem(FAVS_KEY)||'[]')); } catch(e){ favSet=new Set(); }
        try { presetDB   = JSON.parse(localStorage.getItem(PRST_KEY)||'{}'); }   catch(e){ presetDB={}; }
        try { recentList = JSON.parse(localStorage.getItem(RCNT_KEY)||'[]'); }   catch(e){ recentList=[]; }
    }
    function saveHist(){ try{ localStorage.setItem(HIST_KEY, JSON.stringify(histDB)); }catch(e){} }
    function saveFavs(){ try{ localStorage.setItem(FAVS_KEY, JSON.stringify([...favSet])); }catch(e){} }
    function savePrst(){ try{ localStorage.setItem(PRST_KEY, JSON.stringify(presetDB)); }catch(e){} }
    function saveRcnt(){ try{ localStorage.setItem(RCNT_KEY, JSON.stringify(recentList)); }catch(e){} }

    function addHistory(toolId, val, cfg) {
        if(!histDB[toolId]) histDB[toolId]=[];
        histDB[toolId].unshift({ val: String(val).slice(0,3000), cfg: cfg||{}, ts: Date.now() });
        if(histDB[toolId].length > 20) histDB[toolId].pop();
        saveHist();
    }
    function addRecent(toolId) {
        recentList = [toolId, ...recentList.filter(id=>id!==toolId)].slice(0,8);
        saveRcnt();
    }
    function toggleFav(toolId, e) {
        if(e){ e.stopPropagation(); e.preventDefault(); }
        if(favSet.has(toolId)) favSet.delete(toolId); else favSet.add(toolId);
        saveFavs();
        renderFavButtons();
        renderRecentlyUsed();
    }
    function savePreset(toolId, name, cfg) {
        if(!presetDB[toolId]) presetDB[toolId]=[];
        presetDB[toolId] = presetDB[toolId].filter(p=>p.name!==name);
        presetDB[toolId].unshift({ name, cfg });
        if(presetDB[toolId].length>10) presetDB[toolId].pop();
        savePrst();
    }
    function deletePresetEntry(toolId, name) {
        if(!presetDB[toolId]) return;
        presetDB[toolId] = presetDB[toolId].filter(p=>p.name!==name);
        savePrst();
    }

    /* ── SVG constants ─────────────────────────────────────────────────── */
    var STAR_EMPTY  = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
    var STAR_FILLED = '<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';

    /* ── DGT Hook wiring ───────────────────────────────────────────────── */
    function hookDGT() {
        if(!DGT._hooks) return;
        DGT._hooks.onOpen = function(tool) {
            if(!tool) return;
            addRecent(tool.id);
            renderRecentlyUsed();
            renderFavButtons();
            renderPresetBar(tool.id);
            renderHistorySidebar(tool.id);
            renderFavStar(tool.id);
            handleCustomUI(tool);
            updateURLHash(tool.id);
        };
        DGT._hooks.onGenerate = function(tool, result) {
            if(!tool||!result) return;
            var cfg = readActiveCfg(tool);
            addHistory(tool.id, result, cfg);
            renderHistorySidebar(tool.id);
        };
    }

    function readActiveCfg(tool) {
        var cfg = {};
        (tool.inputs||[]).forEach(function(inp) {
            var el = $id('dgt-inp-'+inp.id);
            if(el) cfg[inp.id] = inp.type==='checkbox' ? el.checked : el.value;
        });
        return cfg;
    }

    /* ═══════════════════════════════════════════════════════════════════
       FEATURE 4 — Shareable URL hash
    ═══════════════════════════════════════════════════════════════════ */
    function updateURLHash(toolId) {
        try { history.replaceState(null,'','#'+toolId); } catch(e){}
    }
    function parseURLHash() {
        var hash = window.location.hash.replace('#','').trim();
        if(!hash) return;
        var TOOLS = DGT._tools||[];
        var tool  = TOOLS.find(function(t){ return t.id===hash; });
        if(!tool) return;
        setTimeout(function(){
            DGT.open(hash);
            var ws=$id('dgt-workspace');
            if(ws) ws.scrollIntoView({behavior:'smooth',block:'start'});
        }, 400);
    }

    /* ═══════════════════════════════════════════════════════════════════
       FEATURE 1 — Smart History (sidebar panel)
    ═══════════════════════════════════════════════════════════════════ */
    function renderHistorySidebar(toolId) {
        var el = $id('dgt-hist-list');
        if(!el) return;
        var items = (histDB[toolId]||[]).slice(0,5);
        if(!items.length){
            el.innerHTML='<p class="dgt-hist-empty">No history yet — generate something!</p>';
            return;
        }
        el.innerHTML = items.map(function(item,i){
            var preview = item.val.split('\n')[0].slice(0,52) + (item.val.length>52?'…':'');
            var time    = new Date(item.ts).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
            return '<div class="dgt-hist-item" onclick="DGTF.restoreHistory(\''+toolId+'\','+i+')" title="Restore this output">'
                +'<span class="dgt-hist-val">'+esc(preview)+'</span>'
                +'<span class="dgt-hist-time">'+time+'</span>'
                +'</div>';
        }).join('');
    }

    function restoreHistory(toolId, idx) {
        var item = (histDB[toolId]||[])[idx];
        if(!item) return;
        var ta  = $id('dgt-ws-output');
        var emp = document.querySelector('#dgt-workspace .dgt-empty');
        if(ta){ ta.value=item.val; ta.style.display='block'; }
        if(emp) emp.style.display='none';
        toast('↩ Restored from history');
    }

    /* ═══════════════════════════════════════════════════════════════════
       FEATURE 1 — Favorites (star on each card + workspace button)
    ═══════════════════════════════════════════════════════════════════ */
    function injectFavButtons() {
        (DGT._tools||[]).forEach(function(t){
            var card = $id('card-'+t.id);
            if(!card||$id('dgt-fav-'+t.id)) return;
            var btn  = document.createElement('button');
            btn.className = 'dgt-fav-btn'+(favSet.has(t.id)?' active':'');
            btn.id        = 'dgt-fav-'+t.id;
            btn.title     = favSet.has(t.id)?'Remove from favorites':'Add to favorites';
            btn.innerHTML = favSet.has(t.id)?STAR_FILLED:STAR_EMPTY;
            btn.onclick   = function(e){ toggleFav(t.id, e); };
            card.appendChild(btn);
        });
    }
    function renderFavButtons() {
        (DGT._tools||[]).forEach(function(t){
            var el = $id('dgt-fav-'+t.id);
            if(!el) return;
            el.innerHTML  = favSet.has(t.id)?STAR_FILLED:STAR_EMPTY;
            el.title      = favSet.has(t.id)?'Remove from favorites':'Add to favorites';
            el.classList.toggle('active', favSet.has(t.id));
        });
    }
    function renderFavStar(toolId) {
        var btn = $id('dgt-ws-fav-btn');
        if(!btn) return;
        var on = favSet.has(toolId);
        btn.innerHTML  = (on?STAR_FILLED:STAR_EMPTY)+'<span>'+(on?'Favorited':'Favorite')+'</span>';
        btn.classList.toggle('active', on);
        btn.onclick    = function(){ toggleFav(toolId); renderFavStar(toolId); };
    }

    /* ═══════════════════════════════════════════════════════════════════
       FEATURE 3 — Named Presets (preset bar above config form)
    ═══════════════════════════════════════════════════════════════════ */
    function renderPresetBar(toolId) {
        var el = $id('dgt-preset-bar');
        if(!el) return;
        var list = (presetDB[toolId]||[]);
        if(!list.length){
            el.innerHTML='<button class="dgt-preset-save-btn" onclick="DGTF.promptSavePreset()">+ Save preset</button>';
            return;
        }
        var opts = list.map(function(p){
            return '<option value="'+esc(p.name)+'">'+esc(p.name)+'</option>';
        }).join('');
        el.innerHTML=''
            +'<select class="dgt-preset-select" id="dgt-preset-sel" onchange="DGTF.loadPreset()">'
            +'<option value="">— Load preset —</option>'+opts+'</select>'
            +'<button class="dgt-preset-save-btn" onclick="DGTF.promptSavePreset()" title="Save current config">Save</button>'
            +'<button class="dgt-preset-del-btn"  onclick="DGTF.deleteActivePreset()" title="Delete selected">✕</button>';
    }
    function promptSavePreset() {
        var st   = DGT._state();
        var tool = st.activeTool;
        if(!tool){ toast('Open a tool first'); return; }
        var name = prompt('Name for this preset:', 'My preset');
        if(!name||!name.trim()) return;
        savePreset(tool.id, name.trim(), readActiveCfg(tool));
        renderPresetBar(tool.id);
        toast('Preset "'+name.trim()+'" saved!');
    }
    function loadPreset() {
        var sel  = $id('dgt-preset-sel');
        if(!sel||!sel.value) return;
        var st   = DGT._state();
        var tool = st.activeTool;
        if(!tool) return;
        var preset = (presetDB[tool.id]||[]).find(function(p){ return p.name===sel.value; });
        if(!preset) return;
        Object.keys(preset.cfg).forEach(function(key){
            var val = preset.cfg[key];
            var el  = $id('dgt-inp-'+key);
            if(!el) return;
            var inp = (tool.inputs||[]).find(function(i){ return i.id===key; });
            if(inp&&inp.type==='checkbox') el.checked = val;
            else el.value = val;
            // Update toggle button active state
            if(inp&&inp.type==='toggle'){
                var wrap = el.closest('.dgt-toggle-group');
                if(wrap) wrap.querySelectorAll('.dgt-toggle-btn').forEach(function(b){ b.classList.toggle('active', b.textContent.trim()===val); });
            }
            // Update slider display
            if(inp&&inp.type==='slider'){
                var sv = $id('dgt-slv-'+key);
                if(sv) sv.textContent = val;
            }
        });
        toast('Preset "'+sel.value+'" loaded');
    }
    function deleteActivePreset() {
        var sel  = $id('dgt-preset-sel');
        if(!sel||!sel.value) return;
        var st   = DGT._state();
        var tool = st.activeTool;
        if(!tool) return;
        if(!confirm('Delete preset "'+sel.value+'"?')) return;
        deletePresetEntry(tool.id, sel.value);
        renderPresetBar(tool.id);
        toast('Preset deleted');
    }

    /* ═══════════════════════════════════════════════════════════════════
       FEATURE 2 — Copy As multi-format
    ═══════════════════════════════════════════════════════════════════ */
    function showCopyAsMenu(btn) {
        var existing = $id('dgt-copyas-menu');
        if(existing){ existing.remove(); return; }
        var menu = document.createElement('div');
        menu.id  = 'dgt-copyas-menu';
        menu.className = 'dgt-copyas-menu';
        var formats = [
            {id:'text',   label:'📄 Plain text'},
            {id:'json',   label:'⬡  JSON array'},
            {id:'js',     label:'🟨 JS array (const)'},
            {id:'python', label:'🐍 Python list'},
            {id:'sql',    label:'🗄️  SQL IN (...)'},
            {id:'csv',    label:'📊 CSV row'},
            {id:'php',    label:'🐘 PHP array'},
        ];
        menu.innerHTML = formats.map(function(f){
            return '<button class="dgt-copyas-item" onclick="DGTF.copyAs(\''+f.id+'\')">'+f.label+'</button>';
        }).join('');
        var rect = btn.getBoundingClientRect();
        menu.style.cssText = 'position:fixed;top:'+(rect.bottom+4)+'px;left:'+rect.left+'px;z-index:9999';
        document.body.appendChild(menu);
        setTimeout(function(){
            document.addEventListener('click', function closeCopyAs(){
                var m=$id('dgt-copyas-menu'); if(m)m.remove();
                document.removeEventListener('click', closeCopyAs);
            });
        }, 20);
    }
    function copyAs(format) {
        var ta = $id('dgt-ws-output');
        if(!ta||!ta.value){ toast('Generate something first'); return; }
        var lines = ta.value.split('\n').filter(Boolean);
        var q = function(s){ return '"'+String(s).replace(/\\/g,'\\\\').replace(/"/g,'\\"')+'"'; };
        var sq= function(s){ return "'"+String(s).replace(/'/g,"''")+"'"; };
        var result;
        if(format==='text')   result = ta.value;
        else if(format==='json')   result = JSON.stringify(lines, null, 2);
        else if(format==='js')     result = 'const items = [\n'+lines.map(function(l){ return '  '+q(l); }).join(',\n')+'\n];';
        else if(format==='python') result = 'items = [\n'+lines.map(function(l){ return '    '+q(l); }).join(',\n')+'\n]';
        else if(format==='sql')    result = 'WHERE id IN (\n  '+lines.map(sq).join(',\n  ')+'\n)';
        else if(format==='csv')    result = lines.join(',');
        else if(format==='php')    result = '$items = [\n'+lines.map(function(l){ return '    '+q(l)+','; }).join('\n')+'\n];';
        if(result){
            if(navigator.clipboard) navigator.clipboard.writeText(result).then(function(){ toast('✓ Copied as '+format+'!'); });
            else {
                var tmp=document.createElement('textarea'); tmp.value=result; tmp.style.cssText='position:fixed;opacity:0';
                document.body.appendChild(tmp); tmp.select(); document.execCommand('copy'); document.body.removeChild(tmp);
                toast('✓ Copied as '+format+'!');
            }
        }
        var m=$id('dgt-copyas-menu'); if(m)m.remove();
    }

    /* ═══════════════════════════════════════════════════════════════════
       FEATURE 10 — Command Palette (⌘K)
    ═══════════════════════════════════════════════════════════════════ */
    function openPalette() {
        paletteOpen = true;
        var el = $id('dgt-palette');
        if(!el) return;
        el.style.display='flex';
        requestAnimationFrame(function(){ el.classList.add('open'); });
        var inp = $id('dgt-palette-input');
        if(inp){ inp.value=''; inp.focus(); }
        renderPaletteList('');
    }
    function closePalette() {
        paletteOpen = false;
        var el = $id('dgt-palette');
        if(!el) return;
        el.classList.remove('open');
        setTimeout(function(){ el.style.display='none'; }, 200);
    }
    function renderPaletteList(q) {
        q = (q||'').toLowerCase().trim();
        var TOOLS = DGT._tools||[];
        var CATS  = DGT._cats||{};
        if(q){
            paletteCurrent = TOOLS.filter(function(t){
                return t.title.toLowerCase().includes(q)
                    || t.desc.toLowerCase().includes(q)
                    || (t.tags||[]).some(function(tag){ return tag.includes(q); })
                    || t.cat.includes(q);
            });
        } else {
            // Pin recently used at top
            var rcnt = recentList.map(function(id){ return TOOLS.find(function(t){ return t.id===id; }); }).filter(Boolean);
            var rest = TOOLS.filter(function(t){ return !recentList.includes(t.id); });
            paletteCurrent = rcnt.concat(rest);
        }
        paletteIdx = 0;
        var list = $id('dgt-palette-list');
        if(!list) return;
        if(!paletteCurrent.length){
            list.innerHTML='<div class="dgt-palette-empty">No results for "'+esc(q)+'"</div>';
            return;
        }
        list.innerHTML = paletteCurrent.slice(0,50).map(function(t,i){
            var cat   = (CATS[t.cat]&&CATS[t.cat].label)||t.cat;
            var faved = favSet.has(t.id);
            var rcntBadge = (!q && recentList.slice(0,3).includes(t.id)) ? '<span class="dgt-palette-recent">Recent</span>' : '';
            return '<div class="dgt-palette-item'+(i===0?' active':'')+'" data-idx="'+i+'" '
                +'onmouseenter="DGTF.paletteHover('+i+')" onclick="DGTF.paletteSelect('+i+')">'
                +'<div class="dgt-palette-icon">'+(DGT._toolIcon?DGT._toolIcon(t.id):'')+'</div>'
                +'<div class="dgt-palette-info">'
                +'<span class="dgt-palette-title">'+esc(t.title)+'</span>'
                +'<span class="dgt-palette-cat">'+esc(cat)+'</span>'
                +'</div>'
                +rcntBadge
                +(faved?'<span class="dgt-palette-fav">'+STAR_FILLED+'</span>':'')
                +'</div>';
        }).join('');
    }
    function paletteHover(idx) {
        paletteIdx = idx;
        updatePaletteActive();
    }
    function paletteSelect(idx) {
        var tool = paletteCurrent[idx];
        if(!tool) return;
        closePalette();
        DGT.open(tool.id);
        setTimeout(function(){
            var ws=$id('dgt-workspace');
            if(ws) ws.scrollIntoView({behavior:'smooth',block:'start'});
        }, 80);
    }
    function paletteKeyNav(e) {
        if(!paletteOpen) return;
        if(e.key==='ArrowDown'){ e.preventDefault(); paletteIdx=Math.min(paletteIdx+1, paletteCurrent.length-1); updatePaletteActive(); }
        else if(e.key==='ArrowUp'){ e.preventDefault(); paletteIdx=Math.max(paletteIdx-1, 0); updatePaletteActive(); }
        else if(e.key==='Enter'){ e.preventDefault(); paletteSelect(paletteIdx); }
        else if(e.key==='Escape'){ e.preventDefault(); closePalette(); }
    }
    function updatePaletteActive() {
        document.querySelectorAll('.dgt-palette-item').forEach(function(el,i){
            el.classList.toggle('active', i===paletteIdx);
            if(i===paletteIdx) el.scrollIntoView({block:'nearest'});
        });
    }

    /* ═══════════════════════════════════════════════════════════════════
       FEATURE 9 — Surprise Me (daily rotating tool)
    ═══════════════════════════════════════════════════════════════════ */
    var DEV_FACTS = {
        uuid:'UUIDs have 2¹²² possible values — more atoms than in the observable universe.',
        passwords:'A 16-char random password takes ~92 trillion years to crack at 1 billion tries/sec.',
        random:'True randomness is impossible in software — only cryptographic pseudo-randomness exists.',
        lorem:'"Lorem ipsum" is scrambled Cicero from 45 BC — the world\'s oldest placeholder text.',
        fakedata:'GDPR legally requires fake data in dev/test environments — real names in logs = risk.',
        structured:'JSON became an RFC standard in 2006 — 4 years after Douglas Crockford proposed it.',
        code:'The average developer writes 10–12 lines of production code per day.',
        utils:'A well-formed URL slug has never caused a 404. Protect it like a treasure.',
        power:'Composing generators in one click saves 30+ minutes per API mock session.',
    };
    function renderSurpriseBanner() {
        var el = $id('dgt-surprise');
        if(!el) return;
        var TOOLS = DGT._tools||[];
        var CATS  = DGT._cats||{};
        var day   = Math.floor(Date.now()/86400000);
        var tool  = TOOLS[day % TOOLS.length];
        if(!tool) return;
        var fact    = DEV_FACTS[tool.cat] || DEV_FACTS.utils;
        var catName = (CATS[tool.cat]&&CATS[tool.cat].label)||tool.cat;
        el.innerHTML=''
            +'<div class="dgt-surprise-inner">'
            +'<div class="dgt-surprise-badge">✨ Tool of the Day</div>'
            +'<div class="dgt-surprise-content">'
            +'<h3 class="dgt-surprise-title">'+esc(tool.title)+'</h3>'
            +'<p class="dgt-surprise-desc">'+esc(tool.desc)+'</p>'
            +'<p class="dgt-surprise-fact">💡 '+esc(fact)+'</p>'
            +'</div>'
            +'<div class="dgt-surprise-cta">'
            +'<span class="dgt-surprise-cat">'+esc(catName)+'</span>'
            +'<button class="dgt-surprise-btn" onclick="DGT.open(\''+tool.id+'\');document.getElementById(\'dgt-workspace\').scrollIntoView({behavior:\'smooth\'})">Try it →</button>'
            +'</div>'
            +'</div>';
        el.style.display='block';
    }

    /* ═══════════════════════════════════════════════════════════════════
       FEATURE 1 — Recently Used + Favorites strip
    ═══════════════════════════════════════════════════════════════════ */
    function renderRecentlyUsed() {
        var el = $id('dgt-recently-used');
        if(!el) return;
        var TOOLS    = DGT._tools||[];
        var favTools = TOOLS.filter(function(t){ return favSet.has(t.id); });
        var rcntTools= recentList.map(function(id){ return TOOLS.find(function(t){ return t.id===id; }); }).filter(Boolean);
        if(!favTools.length && !rcntTools.length){ el.style.display='none'; return; }
        el.style.display='block';
        var html='';
        if(favTools.length){
            html+='<div class="dgt-ru-row"><span class="dgt-ru-label">⭐ Favorites</span><div class="dgt-ru-chips">';
            favTools.forEach(function(t){
                html+='<button class="dgt-ru-chip dgt-ru-chip--fav" onclick="DGT.open(\''+t.id+'\')" title="'+esc(t.desc)+'">'+esc(t.title)+'</button>';
            });
            html+='</div></div>';
        }
        if(rcntTools.length){
            html+='<div class="dgt-ru-row"><span class="dgt-ru-label">🕐 Recent</span><div class="dgt-ru-chips">';
            rcntTools.slice(0,6).forEach(function(t){
                html+='<button class="dgt-ru-chip" onclick="DGT.open(\''+t.id+'\')" title="'+esc(t.desc)+'">'+esc(t.title)+'</button>';
            });
            html+='</div></div>';
        }
        el.innerHTML=html;
    }

    /* ═══════════════════════════════════════════════════════════════════
       FEATURE 7 — Data Composer custom UI
    ═══════════════════════════════════════════════════════════════════ */
    var CTYPE_OPTS = [
        {v:'uuid-v4',     l:'UUID v4'},          {v:'uuid-v7',   l:'UUID v7'},
        {v:'ulid',        l:'ULID'},              {v:'nanoid',    l:'NanoID'},
        {v:'fake-name',   l:'Full Name'},         {v:'fake-first',l:'First Name'},
        {v:'fake-last',   l:'Last Name'},         {v:'fake-email',l:'Email'},
        {v:'fake-phone',  l:'Phone'},             {v:'fake-job',  l:'Job Title'},
        {v:'fake-company',l:'Company'},           {v:'fake-city', l:'City'},
        {v:'fake-country',l:'Country'},           {v:'fake-address',l:'Address'},
        {v:'password',    l:'Password'},          {v:'random-hex',l:'Random Hex'},
        {v:'random-int',  l:'Random Int'},        {v:'random-bool',l:'Boolean'},
        {v:'timestamp-iso',l:'ISO Timestamp'},    {v:'timestamp-unix',l:'Unix Timestamp'},
        {v:'lorem-sentence',l:'Lorem Sentence'},  {v:'index',     l:'Index (0,1,2…)'},
        {v:'index-1',     l:'Index (1,2,3…)'},   {v:'static',    l:'Static value'},
    ];
    var CTYPE_HTML = CTYPE_OPTS.map(function(o){ return '<option value="'+o.v+'">'+o.l+'</option>'; }).join('');

    function handleCustomUI(tool) {
        if(tool.id==='compose')  renderComposeUI();
        else if(tool.id==='enricher') renderEnricherUI();
    }

    function renderComposeUI() {
        var form=$id('dgt-ws-form');
        if(!form) return;
        form.innerHTML=''
            +'<div class="dgt-compose-wrap">'
            +'<div id="dgt-compose-fields" class="dgt-compose-fields"></div>'
            +'<button class="dgt-compose-add" type="button" onclick="DGTF.composeAddField()">+ Add Field</button>'
            +'<div class="dgt-field" style="margin-top:10px">'
            +'<label>Records to generate</label>'
            +'<div class="dgt-slider-wrap">'
            +'<input type="range" class="dgt-slider" id="dgt-compose-count" min="1" max="200" value="'+composeCount+'" '
            +'oninput="document.getElementById(\'dgt-cc-v\').textContent=this.value;DGTF.setComposeCount(this.value)">'
            +'<span class="dgt-slider-val" id="dgt-cc-v">'+composeCount+'</span>'
            +'</div></div>'
            +'</div>';
        renderComposeFields();
        // Override generate button text
        var btn=$id('dgt-gen-btn');
        if(btn){
            btn.dataset.originalOnclick=btn.getAttribute('onclick')||'';
            btn.onclick=function(){ DGTF.runCompose(); };
            btn.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg> Compose';
        }
    }
    function renderComposeFields() {
        var el=$id('dgt-compose-fields');
        if(!el) return;
        el.innerHTML = composeFields.map(function(f,i){
            var extraIn='';
            if(f.type==='static')      extraIn='<input class="dgt-input dgt-cf-extra" type="text" value="'+esc(f.staticVal||'')+'" placeholder="value" oninput="DGTF.setCF('+i+',\'staticVal\',this.value)" style="width:80px">';
            if(f.type==='random-int')  extraIn='<input class="dgt-input dgt-cf-extra" type="number" value="'+(f.min||0)+'" placeholder="min" oninput="DGTF.setCF('+i+',\'min\',this.value)" style="width:55px">'
                +'<input class="dgt-input dgt-cf-extra" type="number" value="'+(f.max||100)+'" placeholder="max" oninput="DGTF.setCF('+i+',\'max\',this.value)" style="width:55px">';
            return '<div class="dgt-compose-field">'
                +'<input class="dgt-input" type="text" value="'+esc(f.name)+'" placeholder="field_name" oninput="DGTF.setCF('+i+',\'name\',this.value)" style="width:100px;flex-shrink:0">'
                +'<select class="dgt-select" onchange="DGTF.setCF('+i+',\'type\',this.value)" style="flex:1">'+CTYPE_HTML.replace('value="'+f.type+'"','value="'+f.type+'" selected')+'</select>'
                +extraIn
                +'<button class="dgt-compose-remove" type="button" onclick="DGTF.composeRemoveField('+i+')" title="Remove">✕</button>'
                +'</div>';
        }).join('');
    }
    function composeAddField(){ composeFields.push({name:'field_'+composeFields.length,type:'random-hex'}); renderComposeFields(); }
    function composeRemoveField(i){ composeFields.splice(i,1); renderComposeFields(); }
    function setCF(i,k,v){ if(!composeFields[i]) return; composeFields[i][k]=v; if(k==='type') renderComposeFields(); }
    function setComposeCount(n){ composeCount=parseInt(n)||5; }

    /* Shared random engine for compose values */
    function rr(lo,hi){ var b=new Uint32Array(1); crypto.getRandomValues(b); return lo+(b[0]%(hi-lo+1)); }
    function rHex(n){ var a=new Uint8Array(n); crypto.getRandomValues(a); return Array.from(a).map(function(x){ return ('0'+x.toString(16)).slice(-2); }).join(''); }
    var _FM=['James','John','Robert','Michael','William','David','Joseph','Charles','Thomas','Daniel'];
    var _FF=['Mary','Patricia','Jennifer','Linda','Barbara','Elizabeth','Susan','Jessica','Sarah','Karen'];
    var _FL=['Smith','Johnson','Williams','Brown','Jones','Garcia','Miller','Davis','Rodriguez','Martinez'];
    var _FD=['gmail.com','yahoo.com','hotmail.com','outlook.com','protonmail.com'];
    var _FJ=['Software Engineer','Data Analyst','Product Manager','UX Designer','DevOps Engineer','QA Engineer'];
    var _FC=['Acme Corp','Globex Inc','Initech LLC','Aperture Science','Wayne Enterprises','Stark Industries'];
    var _CITY=['New York','Los Angeles','Chicago','Houston','Phoenix','Seattle','Austin','Boston'];
    var _CTR =['United States','United Kingdom','Canada','Australia','Germany','France','Japan','Brazil'];
    var _ST  =['Main St','Oak Ave','Maple Dr','Cedar Ln','Park Blvd','River Rd'];
    function ri(a){ return a[rr(0,a.length-1)]; }
    function makeUUID4(){ var b=new Uint8Array(16); crypto.getRandomValues(b); b[6]=(b[6]&0x0f)|0x40; b[8]=(b[8]&0x3f)|0x80; var h=Array.from(b).map(function(x){return ('0'+x.toString(16)).slice(-2);}); return h[0]+h[1]+h[2]+h[3]+'-'+h[4]+h[5]+'-'+h[6]+h[7]+'-'+h[8]+h[9]+'-'+h[10]+h[11]+h[12]+h[13]+h[14]+h[15]; }
    function makeUUID7(){ var ms=Date.now(),b=new Uint8Array(16);crypto.getRandomValues(b);b[0]=(ms/0x10000000000)&0xff;b[1]=(ms/0x100000000)&0xff;b[2]=(ms/0x1000000)&0xff;b[3]=(ms/0x10000)&0xff;b[4]=(ms/0x100)&0xff;b[5]=ms&0xff;b[6]=(b[6]&0x0f)|0x70;b[8]=(b[8]&0x3f)|0x80;var h=Array.from(b).map(function(x){return ('0'+x.toString(16)).slice(-2);});return h[0]+h[1]+h[2]+h[3]+'-'+h[4]+h[5]+'-'+h[6]+h[7]+'-'+h[8]+h[9]+'-'+h[10]+h[11]+h[12]+h[13]+h[14]+h[15]; }
    var _UC='0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    function makeULID(){ var ms=Date.now(),t='';for(var i=9;i>=0;i--){t=_UC[ms%32]+t;ms=Math.floor(ms/32);}var rv='';for(var j=0;j<16;j++)rv+=_UC[rr(0,31)];return t+rv; }
    var _NC='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-';
    function makeNanoID(n){ return Array.from(new Uint8Array(n)).map(function(x,_,a){crypto.getRandomValues(a);return _NC[x%64];}).join(''); }
    var _PW='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
    var _LW=['lorem','ipsum','dolor','sit','amet','consectetur','adipiscing','elit','sed','do'];
    function composeVal(f,idx){
        var t=f.type, fn=ri(_FM), ln=ri(_FL);
        if(t==='uuid-v4')        return makeUUID4();
        if(t==='uuid-v7')        return makeUUID7();
        if(t==='ulid')           return makeULID();
        if(t==='nanoid')         return makeNanoID(21);
        if(t==='fake-name')      return ri(rr(0,1)?_FM:_FF)+' '+ri(_FL);
        if(t==='fake-first')     return ri(rr(0,1)?_FM:_FF);
        if(t==='fake-last')      return ri(_FL);
        if(t==='fake-email')     return (fn+'.'+ln).toLowerCase()+'@'+ri(_FD);
        if(t==='fake-phone')     return '+1-'+rr(200,999)+'-'+rr(100,999)+'-'+('0000'+rr(0,9999)).slice(-4);
        if(t==='fake-job')       return ri(_FJ);
        if(t==='fake-company')   return ri(_FC);
        if(t==='fake-city')      return ri(_CITY);
        if(t==='fake-country')   return ri(_CTR);
        if(t==='fake-address')   return rr(1,999)+' '+ri(_ST)+', '+ri(_CITY);
        if(t==='password')       { var s='';for(var k=0;k<16;k++)s+=_PW[rr(0,_PW.length-1)];return s; }
        if(t==='random-hex')     return rHex(8);
        if(t==='random-int')     return rr(+(f.min||0), +(f.max||100));
        if(t==='random-bool')    return rr(0,1)?'true':'false';
        if(t==='timestamp-iso')  return new Date().toISOString();
        if(t==='timestamp-unix') return String(Math.floor(Date.now()/1000));
        if(t==='lorem-sentence') { var ws=[]; for(var w=0;w<rr(5,9);w++) ws.push(ri(_LW)); return ws.join(' ')+'.'; }
        if(t==='index')          return String(idx);
        if(t==='index-1')        return String(idx+1);
        if(t==='static')         return f.staticVal||'';
        return '';
    }

    function runCompose() {
        var btn=$id('dgt-gen-btn');
        if(btn){btn.disabled=true;btn.innerHTML='<span class="dgt-spinner"></span> Composing…';}
        setTimeout(function(){
            try{
                var records=[];
                for(var i=0;i<composeCount;i++){
                    var obj={};
                    composeFields.forEach(function(f){ if(f.name) obj[f.name]=composeVal(f,i); });
                    records.push(obj);
                }
                var result = composeCount===1 ? JSON.stringify(records[0],null,2) : JSON.stringify(records,null,2);
                var ta=$id('dgt-ws-output'), emp=document.querySelector('#dgt-workspace .dgt-empty');
                if(ta){ta.value=result;ta.style.display='block';}
                if(emp) emp.style.display='none';
                var st=DGT._state();
                if(st.activeTool) addHistory(st.activeTool.id, result, {});
                toast('✓ Composed '+composeCount+' record'+(composeCount>1?'s':'')+'!');
            }catch(err){ toast('Error: '+err.message); }
            if(btn){btn.disabled=false;btn.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg> Compose';}
        },10);
    }

    /* ═══════════════════════════════════════════════════════════════════
       FEATURE 8 — CSV / JSON Enricher custom UI
    ═══════════════════════════════════════════════════════════════════ */
    function renderEnricherUI() {
        var form=$id('dgt-ws-form');
        if(!form) return;
        form.innerHTML=''
            +'<div class="dgt-field">'
            +'<label>Paste CSV or JSON data</label>'
            +'<textarea id="dgt-enrich-input" class="dgt-input" rows="5" style="font-family:var(--font-mono);font-size:11px;resize:vertical" placeholder="Paste CSV rows or a JSON array here…" oninput="DGTF.detectEnrichFmt()"></textarea>'
            +'<span id="dgt-enrich-detect" class="dgt-field-help" style="font-size:11px;margin-top:4px;display:block"></span>'
            +'</div>'
            +'<div class="dgt-field">'
            +'<label>Columns to add</label>'
            +'<div id="dgt-enrich-cols"></div>'
            +'<button class="dgt-compose-add" type="button" onclick="DGTF.enrichAddCol()" style="margin-top:6px">+ Add column</button>'
            +'</div>';
        renderEnrichCols();
        var btn=$id('dgt-gen-btn');
        if(btn){
            btn.onclick=function(){ DGTF.runEnrich(); };
            btn.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg> Enrich Data';
        }
    }
    function renderEnrichCols(){
        var el=$id('dgt-enrich-cols'); if(!el) return;
        el.innerHTML=enrichAddFields.map(function(f,i){
            return '<div class="dgt-compose-field">'
                +'<input class="dgt-input" type="text" value="'+esc(f.col)+'" placeholder="col_name" oninput="DGTF.setEF('+i+',\'col\',this.value)" style="width:90px;flex-shrink:0">'
                +'<select class="dgt-select" onchange="DGTF.setEF('+i+',\'type\',this.value)" style="flex:1">'+CTYPE_HTML.replace('value="'+f.type+'"','value="'+f.type+'" selected')+'</select>'
                +'<button class="dgt-compose-remove" type="button" onclick="DGTF.enrichRemoveCol('+i+')">✕</button>'
                +'</div>';
        }).join('');
    }
    function enrichAddCol(){ enrichAddFields.push({col:'col_'+enrichAddFields.length,type:'uuid-v4'}); renderEnrichCols(); }
    function enrichRemoveCol(i){ enrichAddFields.splice(i,1); renderEnrichCols(); }
    function setEF(i,k,v){ if(enrichAddFields[i]) enrichAddFields[i][k]=v; }
    function detectEnrichFmt(){
        var ta=$id('dgt-enrich-input'), det=$id('dgt-enrich-detect');
        if(!ta||!det) return;
        var v=ta.value.trim();
        if(!v){det.textContent='';return;}
        try{ var p=JSON.parse(v); det.textContent='✓ JSON detected ('+(Array.isArray(p)?p.length:1)+' record'+(Array.isArray(p)&&p.length!==1?'s':'')+')'; det.style.color='var(--color-success)'; return; }catch(e){}
        var lines=v.split('\n').filter(Boolean);
        if(lines.length>1&&lines[0].includes(',')){ det.textContent='✓ CSV detected ('+lines.length+' rows incl. header)'; det.style.color='var(--color-success)'; return; }
        det.textContent='? Format not recognised — expecting CSV or JSON array'; det.style.color='var(--color-warning,#f59e0b)';
    }
    function runEnrich(){
        var btn=$id('dgt-gen-btn');
        if(btn){ btn.disabled=true; }
        setTimeout(function(){
            try{
                var raw=($id('dgt-enrich-input')||{}).value||'';
                raw=raw.trim();
                if(!raw){ toast('Paste some data first'); if(btn)btn.disabled=false; return; }
                var records=[], fmt='unknown';
                try{ var parsed=JSON.parse(raw); records=Array.isArray(parsed)?parsed:[parsed]; fmt='json'; }catch(e){}
                if(fmt==='unknown'){
                    var lines=raw.split('\n').filter(Boolean);
                    var headers=lines[0].split(',').map(function(h){ return h.trim().replace(/^"|"$/g,''); });
                    records=lines.slice(1).map(function(line){
                        var vals=line.split(',').map(function(v){ return v.trim().replace(/^"|"$/g,''); });
                        var obj={}; headers.forEach(function(h,i){ obj[h]=vals[i]||''; }); return obj;
                    });
                    fmt='csv';
                }
                records=records.map(function(rec,idx){
                    enrichAddFields.forEach(function(f){ if(f.col) rec[f.col]=composeVal({type:f.type},idx); });
                    return rec;
                });
                var result;
                if(fmt==='csv'){
                    var keys=Object.keys(records[0]||{});
                    result=keys.join(',')+'\n'+records.map(function(r){ return keys.map(function(k){ return '"'+String(r[k]).replace(/"/g,'""')+'"'; }).join(','); }).join('\n');
                } else {
                    result=JSON.stringify(records,null,2);
                }
                var ta=$id('dgt-ws-output'),emp=document.querySelector('#dgt-workspace .dgt-empty');
                if(ta){ta.value=result;ta.style.display='block';}
                if(emp) emp.style.display='none';
                var st=DGT._state();
                if(st.activeTool) addHistory(st.activeTool.id, result, {});
                toast('✓ Enriched '+records.length+' record'+(records.length!==1?'s':'')+'!');
            }catch(err){ toast('Error: '+err.message); }
            if(btn){btn.disabled=false;btn.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg> Enrich Data';}
        },10);
    }

    /* ═══════════════════════════════════════════════════════════════════
       FEATURE 5 — Postman export button (sidebar)
    ═══════════════════════════════════════════════════════════════════ */
    function exportPostman() {
        var ta=$id('dgt-ws-output');
        if(!ta||!ta.value){ toast('Generate a Postman collection first'); return; }
        var st=DGT._state(), fn='postman-collection.json';
        if(st.activeTool&&st.activeTool.id==='postman'){
            try{ var parsed=JSON.parse(ta.value); fn=(parsed.info&&parsed.info.name?parsed.info.name.replace(/\s+/g,'-').toLowerCase():fn)+'.json'; }catch(e){}
        }
        var blob=new Blob([ta.value],{type:'application/json;charset=utf-8'});
        var url=URL.createObjectURL(blob), a=document.createElement('a');
        a.href=url; a.download=fn;
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        URL.revokeObjectURL(url);
        toast('↓ Postman collection downloaded');
    }

    /* ═══════════════════════════════════════════════════════════════════
       FEATURE 6 — Batch browser (paginated output for large results)
    ═══════════════════════════════════════════════════════════════════ */
    var BATCH_PG  = 50;
    var batchLines= [];
    var batchPage = 1;
    var batchSel  = new Set();

    function showBatchBrowser(lines) {
        batchLines = lines;
        batchPage  = 1;
        batchSel   = new Set();
        var el = $id('dgt-batch');
        if(!el) return;
        el.style.display='block';
        renderBatchPage();
    }
    function renderBatchPage() {
        var start=(batchPage-1)*BATCH_PG, end=Math.min(batchPage*BATCH_PG, batchLines.length);
        var totalPages=Math.ceil(batchLines.length/BATCH_PG);
        var el=$id('dgt-batch'); if(!el) return;
        var rows=batchLines.slice(start,end).map(function(line,i){
            var absIdx=start+i;
            var checked=batchSel.has(absIdx)?'checked':'';
            return '<div class="dgt-batch-row'+(batchSel.has(absIdx)?' selected':'')+'">'
                +'<input type="checkbox" '+checked+' onchange="DGTF.batchToggle('+absIdx+',this.checked)">'
                +'<span class="dgt-batch-idx">'+(absIdx+1)+'</span>'
                +'<span class="dgt-batch-val">'+esc(line)+'</span>'
                +'<button class="dgt-batch-copy" onclick="DGTF.batchCopyOne('+absIdx+')" title="Copy">⧉</button>'
                +'</div>';
        }).join('');
        el.innerHTML=''
            +'<div class="dgt-batch-header">'
            +'<span>'+batchLines.length+' results &nbsp;|&nbsp; '+batchSel.size+' selected</span>'
            +'<div class="dgt-batch-actions">'
            +'<button class="dgt-tool-btn" onclick="DGTF.batchSelectPage()">Select page</button>'
            +'<button class="dgt-tool-btn" onclick="DGTF.batchExport()" '+(batchSel.size?'':'disabled')+'>Export selected ('+batchSel.size+')</button>'
            +'<button class="dgt-tool-btn" onclick="DGTF.batchExportAll()">Export all</button>'
            +'<button class="dgt-tool-btn" onclick="DGTF.hideBatch()">✕ Close</button>'
            +'</div></div>'
            +'<div class="dgt-batch-list">'+rows+'</div>'
            +'<div class="dgt-batch-pager">'
            +'<button class="dgt-batch-pg-btn" onclick="DGTF.batchGoto('+Math.max(1,batchPage-1)+')" '+(batchPage===1?'disabled':'')+'>&larr;</button>'
            +'<span>Page '+batchPage+' / '+totalPages+'</span>'
            +'<button class="dgt-batch-pg-btn" onclick="DGTF.batchGoto('+(batchPage+1)+')" '+(batchPage>=totalPages?'disabled':'')+'>&rarr;</button>'
            +'</div>';
    }
    function batchGoto(p){ batchPage=p; renderBatchPage(); }
    function batchToggle(idx,checked){
        if(checked) batchSel.add(idx); else batchSel.delete(idx);
        renderBatchPage();
    }
    function batchSelectPage(){
        var start=(batchPage-1)*BATCH_PG, end=Math.min(batchPage*BATCH_PG,batchLines.length);
        for(var i=start;i<end;i++) batchSel.add(i);
        renderBatchPage();
    }
    function batchCopyOne(idx){
        var line=batchLines[idx]; if(!line) return;
        if(navigator.clipboard) navigator.clipboard.writeText(line).then(function(){ toast('✓ Copied'); });
        else { var t=document.createElement('textarea');t.value=line;t.style.cssText='position:fixed;opacity:0';document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);toast('✓ Copied'); }
    }
    function batchExport(){
        if(!batchSel.size){ toast('Select some rows first'); return; }
        var selected=[...batchSel].sort(function(a,b){return a-b;}).map(function(i){ return batchLines[i]; }).join('\n');
        var blob=new Blob([selected],{type:'text/plain'}), url=URL.createObjectURL(blob), a=document.createElement('a');
        a.href=url; a.download='selected.txt'; document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
        toast('↓ Exported '+batchSel.size+' selected items');
    }
    function batchExportAll(){
        var blob=new Blob([batchLines.join('\n')],{type:'text/plain'}), url=URL.createObjectURL(blob), a=document.createElement('a');
        a.href=url; a.download='all-results.txt'; document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
        toast('↓ Exported all '+batchLines.length+' items');
    }
    function hideBatch(){ var el=$id('dgt-batch'); if(el) el.style.display='none'; }

    /* Intercept generate output — show batch browser for large results */
    function interceptGenerate(tool, result) {
        var lines = result ? result.split('\n').filter(Boolean) : [];
        if(lines.length >= 20) {
            var batchWrap = $id('dgt-batch-wrap');
            if(!batchWrap) return;
            batchWrap.style.display='block';
            showBatchBrowser(lines);
        } else {
            hideBatch();
        }
    }

    /* ═══════════════════════════════════════════════════════════════════
       DOM INJECTION — All new HTML elements
    ═══════════════════════════════════════════════════════════════════ */
    function injectElements() {
        var cats = $id('dgt-categories');

        /* Surprise Me banner */
        if(cats && !$id('dgt-surprise')) {
            var surp=document.createElement('div'); surp.id='dgt-surprise'; surp.className='dgt-surprise'; surp.style.display='none';
            cats.parentNode.insertBefore(surp, cats);
        }

        /* Recently Used + Favorites strip */
        if(cats && !$id('dgt-recently-used')) {
            var ru=document.createElement('div'); ru.id='dgt-recently-used'; ru.className='dgt-recently-used'; ru.style.display='none';
            var surpEl=$id('dgt-surprise')||cats;
            surpEl.parentNode.insertBefore(ru, surpEl.nextSibling||cats);
        }

        /* Favorites chip in chip bar */
        var chips=document.querySelector('.dgt-chips');
        if(chips && !$id('dgt-chip-favs')) {
            var fc=document.createElement('button');
            fc.id='dgt-chip-favs'; fc.className='dgt-chip';
            fc.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> Favorites';
            fc.onclick=function(){ var el=$id('dgt-recently-used'); if(el&&el.style.display!=='none') el.scrollIntoView({behavior:'smooth'}); else { renderRecentlyUsed(); setTimeout(function(){var e=$id('dgt-recently-used');if(e)e.scrollIntoView({behavior:'smooth'});},80); } };
            chips.appendChild(fc);
            var pc=document.createElement('button');
            pc.id='dgt-chip-palette'; pc.className='dgt-chip';
            pc.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg> ⌘K';
            pc.style.fontFamily='var(--font-mono)';
            pc.onclick=openPalette;
            chips.appendChild(pc);
        }

        /* Preset bar above config form */
        var config=document.querySelector('.dgt-config');
        if(config && !$id('dgt-preset-bar')) {
            var bar=document.createElement('div'); bar.id='dgt-preset-bar'; bar.className='dgt-preset-bar';
            var form=$id('dgt-ws-form');
            if(form) config.insertBefore(bar, form);
        }

        /* Workspace favorite button in sidebar */
        var side=document.querySelector('.dgt-ws-side');
        if(side && !$id('dgt-ws-fav-btn')) {
            var favBtn=document.createElement('button');
            favBtn.id='dgt-ws-fav-btn'; favBtn.className='dgt-ws-fav-btn';
            favBtn.innerHTML=STAR_EMPTY+'<span>Favorite</span>';
            side.insertBefore(favBtn, side.firstChild);
        }

        /* History panel in sidebar */
        if(side && !$id('dgt-hist-list')) {
            var histSec=document.createElement('div');
            histSec.className='dgt-side-section';
            histSec.innerHTML='<p class="dgt-side-section-title">Recent Outputs</p><div id="dgt-hist-list" class="dgt-hist-list"><p class="dgt-hist-empty">No history yet.</p></div>';
            side.appendChild(histSec);
        }

        /* "Copy as…" button in output tab bar */
        var outTabs=document.querySelector('.dgt-out-tabs');
        if(outTabs && !$id('dgt-copyas-btn')) {
            var cab=document.createElement('button');
            cab.id='dgt-copyas-btn'; cab.className='dgt-tool-btn'; cab.style.marginLeft='4px';
            cab.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copy as…';
            cab.onclick=function(){ showCopyAsMenu(cab); };
            var clearBtn=outTabs.querySelector('.dgt-tool-btn:last-child');
            if(clearBtn) outTabs.insertBefore(cab, clearBtn); else outTabs.appendChild(cab);
        }

        /* Batch browser wrap (below output textarea) */
        var outputPanel=document.querySelector('.dgt-out-tab-panel');
        if(outputPanel && !$id('dgt-batch-wrap')) {
            var bw=document.createElement('div'); bw.id='dgt-batch-wrap'; bw.style.display='none';
            var bd=document.createElement('div'); bd.id='dgt-batch'; bd.className='dgt-batch';
            bw.appendChild(bd);
            outputPanel.appendChild(bw);
        }

        /* Command palette overlay */
        if(!$id('dgt-palette')) {
            var pal=document.createElement('div');
            pal.id='dgt-palette'; pal.className='dgt-palette-overlay'; pal.style.display='none';
            pal.onclick=function(e){ if(e.target===pal) closePalette(); };
            pal.innerHTML=''
                +'<div class="dgt-palette-modal">'
                +'<div class="dgt-palette-search">'
                +'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
                +'<input id="dgt-palette-input" type="text" placeholder="Search 38+ generators…" oninput="DGTF.renderPaletteList(this.value)" onkeydown="DGTF.paletteKeyNav(event)" autocomplete="off" spellcheck="false">'
                +'<kbd class="dgt-kbd">Esc</kbd>'
                +'</div>'
                +'<div id="dgt-palette-list" class="dgt-palette-list"></div>'
                +'<div class="dgt-palette-footer">'
                +'<span><kbd class="dgt-kbd-sm">↑↓</kbd> navigate</span>'
                +'<span><kbd class="dgt-kbd-sm">↵</kbd> open</span>'
                +'<span><kbd class="dgt-kbd-sm">Esc</kbd> close</span>'
                +'</div>'
                +'</div>';
            document.body.appendChild(pal);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════
       KEYBOARD EXTENSIONS — override Ctrl+K with palette
    ═══════════════════════════════════════════════════════════════════ */
    function initKeys() {
        document.addEventListener('keydown', function(e){
            if((e.ctrlKey||e.metaKey) && e.key==='k'){
                e.preventDefault(); e.stopPropagation();
                if(paletteOpen) closePalette(); else openPalette();
            }
        }, true); // capture phase — runs before existing handler
    }

    /* ═══════════════════════════════════════════════════════════════════
       INIT
    ═══════════════════════════════════════════════════════════════════ */
    function init() {
        loadAll();
        hookDGT();
        injectElements();
        renderSurpriseBanner();
        renderRecentlyUsed();
        setTimeout(injectFavButtons, 200);
        parseURLHash();
        initKeys();

        /* Wire generate hook to also trigger batch browser */
        var origOnGen = DGT._hooks.onGenerate;
        DGT._hooks.onGenerate = function(tool, result) {
            if(origOnGen) origOnGen(tool, result);
            interceptGenerate(tool, result);
        };
    }

    document.addEventListener('DOMContentLoaded', init);

    /* ═══════════════════════════════════════════════════════════════════
       PUBLIC API — called from inline onclick handlers
    ═══════════════════════════════════════════════════════════════════ */
    window.DGTF = {
        /* History */
        restoreHistory:   restoreHistory,
        /* Favorites */
        toggleFav:        toggleFav,
        /* Presets */
        promptSavePreset: promptSavePreset,
        loadPreset:       loadPreset,
        deleteActivePreset: deleteActivePreset,
        /* Copy As */
        showCopyAsMenu:   showCopyAsMenu,
        copyAs:           copyAs,
        /* Palette */
        openPalette:      openPalette,
        closePalette:     closePalette,
        renderPaletteList: renderPaletteList,
        paletteHover:     paletteHover,
        paletteSelect:    paletteSelect,
        paletteKeyNav:    paletteKeyNav,
        /* Compose */
        composeAddField:  composeAddField,
        composeRemoveField: composeRemoveField,
        setCF:            setCF,
        setComposeCount:  setComposeCount,
        runCompose:       runCompose,
        /* Enricher */
        enrichAddCol:     enrichAddCol,
        enrichRemoveCol:  enrichRemoveCol,
        setEF:            setEF,
        detectEnrichFmt:  detectEnrichFmt,
        runEnrich:        runEnrich,
        /* Postman */
        exportPostman:    exportPostman,
        /* Batch */
        batchGoto:        batchGoto,
        batchToggle:      batchToggle,
        batchSelectPage:  batchSelectPage,
        batchCopyOne:     batchCopyOne,
        batchExport:      batchExport,
        batchExportAll:   batchExportAll,
        hideBatch:        hideBatch,
    };

})();
