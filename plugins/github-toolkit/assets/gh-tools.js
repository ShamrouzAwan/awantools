/* ============================================================
   GitHub Toolkit — Single-page dashboard JS
   Namespace: GH  |  All 36 tools across 17 tabs
   ============================================================ */
var GH = (function () {
    'use strict';

    /* ──────────────────────────────────────────────────────────
       STATE
    ────────────────────────────────────────────────────────── */
    var _repo      = null;   // current repo API response
    var _langs     = null;   // current repo languages
    var _pat       = '';
    var _recent    = [];
    var _favs      = [];
    var _tabLoaded = {};
    var _activeTab = 'overview';
    var _treeData      = null;
    var _selectedItems = {};  // path -> {path, name, size}
    var _pathMeta      = {};  // path -> GitHub tree item
    var _trendPeriod     = 'daily';
    var _trendPeriodDevs = 'daily';

    var API_BASE = 'https://api.github.com';

    var POPULAR_REPOS = [
        'facebook/react', 'vuejs/vue', 'laravel/laravel',
        'django/django', 'microsoft/vscode', 'torvalds/linux',
        'openai/openai-python', 'tensorflow/tensorflow',
    ];

    var LANG_COLORS = {
        JavaScript:'#f1e05a', TypeScript:'#3178c6', Python:'#3572A5',
        Java:'#b07219', PHP:'#4F5D95', 'C':'#555', 'C++':'#f34b7d',
        'C#':'#178600', Go:'#00ADD8', Ruby:'#701516', Rust:'#dea584',
        Swift:'#F05138', Kotlin:'#A97BFF', CSS:'#563d7c', HTML:'#e34c26',
        Shell:'#89e051', Dart:'#00B4AB', Vue:'#41b883', Scala:'#c22d40',
        R:'#198CE7', Lua:'#000080', Elixir:'#6e4a7e', Haskell:'#5e5086',
        Clojure:'#db5855', Julia:'#a270ba', Perl:'#0298c3', MATLAB:'#e16737',
    };

    var LICENSE_INFO = {
        'MIT':     { name:'MIT License', desc:'Permissive — allows commercial use, modification, distribution, and private use with minimal restrictions. Just keep the license notice.' },
        'Apache-2.0':{ name:'Apache 2.0', desc:'Permissive with patent protection. You can use, modify, distribute, and sublicense. Must include NOTICE file if present.' },
        'GPL-2.0': { name:'GNU GPL v2', desc:'Copyleft — any distributed derivative must be under GPL-2.0. Strong protection against proprietary forks.' },
        'GPL-3.0': { name:'GNU GPL v3', desc:'Copyleft with patent protection. Like GPL-2.0 but adds protections against DRM and patent claims.' },
        'BSD-2-Clause':{ name:'BSD 2-Clause', desc:'Permissive. Essentially MIT but without the advertising clause.' },
        'BSD-3-Clause':{ name:'BSD 3-Clause', desc:'Permissive. Adds a non-endorsement clause on top of BSD-2-Clause.' },
        'ISC':     { name:'ISC License', desc:'Functionally equivalent to a 2-clause BSD or MIT license. Short and permissive.' },
        'LGPL-2.1':{ name:'GNU LGPL v2.1', desc:'Weak copyleft. Allows linking from proprietary software without GPL obligations.' },
        'MPL-2.0': { name:'Mozilla Public License 2.0', desc:'Weak copyleft at the file level. Changes to MPL files must stay open; new files can be proprietary.' },
        'AGPL-3.0':{ name:'GNU AGPL v3', desc:'Like GPL-3.0 but copyleft also applies to software accessed over a network (SaaS).' },
        'Unlicense':{ name:'The Unlicense', desc:'Public domain dedication. No restrictions at all — use for anything.' },
        'CC0-1.0': { name:'Creative Commons Zero', desc:'Public domain waiver. All rights dedicated to the public domain.' },
    };

    /* ──────────────────────────────────────────────────────────
       DOM HELPERS
    ────────────────────────────────────────────────────────── */
    function el(id) { return document.getElementById(id); }
    function gv(id) { var e = el(id); return e ? e.value.trim() : ''; }
    function sv(id, v) { var e = el(id); if (e) e.value = v; }
    function htmlEsc(s) {
        if (s === null || s === undefined) return '';
        return String(s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function fmtNum(n) {
        if (!n && n !== 0) return '–';
        if (n >= 1000000) return (n/1000000).toFixed(1) + 'M';
        if (n >= 1000)    return (n/1000).toFixed(1) + 'k';
        return String(n);
    }
    function fmtBytes(b) {
        if (!b && b !== 0) return '–';
        if (b >= 1073741824) return (b/1073741824).toFixed(2) + ' GB';
        if (b >= 1048576)    return (b/1048576).toFixed(1) + ' MB';
        if (b >= 1024)       return (b/1024).toFixed(1) + ' KB';
        return b + ' B';
    }
    function fmtKB(kb) {
        if (!kb && kb !== 0) return '–';
        var b = kb * 1024;
        return fmtBytes(b);
    }
    function timeSince(d) {
        if (!d) return '–';
        var s = (Date.now() - new Date(d).getTime()) / 1000;
        if (s < 60)   return 'just now';
        if (s < 3600) return Math.floor(s/60) + 'm ago';
        if (s < 86400)return Math.floor(s/3600) + 'h ago';
        if (s < 2592000) return Math.floor(s/86400) + 'd ago';
        if (s < 31536000) return Math.floor(s/2592000) + 'mo ago';
        return Math.floor(s/31536000) + 'yr ago';
    }
    function fmtDate(d) {
        if (!d) return '–';
        return new Date(d).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
    }
    function getLangColor(lang) { return LANG_COLORS[lang] || '#94a3b8'; }

    /* ──────────────────────────────────────────────────────────
       LOCAL STORAGE
    ────────────────────────────────────────────────────────── */
    function loadStorage() {
        try {
            _pat    = localStorage.getItem('gh_pat') || '';
            _recent = JSON.parse(localStorage.getItem('gh_recent') || '[]');
            _favs   = JSON.parse(localStorage.getItem('gh_favs')   || '[]');
        } catch(e) {}
    }
    function saveRecent() {
        try { localStorage.setItem('gh_recent', JSON.stringify(_recent)); } catch(e) {}
    }
    function saveFavs() {
        try { localStorage.setItem('gh_favs', JSON.stringify(_favs)); } catch(e) {}
    }
    function addToRecent(url, name) {
        _recent = _recent.filter(function(r){ return r.url !== url; });
        _recent.unshift({ url: url, name: name });
        _recent = _recent.slice(0, 10);
        saveRecent();
        renderHeroFooter();
    }
    function addToFavorites() {
        if (!_repo) return;
        var url = 'https://github.com/' + _repo.full_name;
        if (_favs.find(function(f){ return f.url === url; })) {
            alert(_repo.full_name + ' is already in your favorites.');
            return;
        }
        _favs.unshift({ url: url, name: _repo.full_name });
        _favs = _favs.slice(0, 20);
        saveFavs();
        renderHeroFooter();
    }
    function removeRecent(i) {
        _recent.splice(i, 1);
        saveRecent();
        renderHeroFooter();
    }
    function removeFav(i) {
        _favs.splice(i, 1);
        saveFavs();
        renderHeroFooter();
    }

    /* ──────────────────────────────────────────────────────────
       GITHUB API
    ────────────────────────────────────────────────────────── */
    async function api(path, params) {
        var url = API_BASE + path;
        if (params) url += '?' + new URLSearchParams(params).toString();
        var headers = {
            'Accept': 'application/vnd.github+json',
            'X-GitHub-Api-Version': '2022-11-28'
        };
        if (_pat) headers['Authorization'] = 'Bearer ' + _pat;
        var res = await fetch(url, { headers: headers });
        var remaining = res.headers.get('X-RateLimit-Remaining');
        var limit     = res.headers.get('X-RateLimit-Limit');
        if (remaining !== null) updateRateBadge(remaining, limit);
        if (!res.ok) {
            if (res.status === 403 || res.status === 429) throw new Error('Rate limit exceeded. Add a Personal Access Token (PAT) above for 5,000 req/hour.');
            if (res.status === 404) throw new Error('Repository not found. Check the URL.');
            throw new Error('GitHub API error ' + res.status);
        }
        return res.json();
    }
    async function rawFetch(rawUrl) {
        var headers = {};
        if (_pat) headers['Authorization'] = 'Bearer ' + _pat;
        var res = await fetch(rawUrl, { headers: headers });
        if (!res.ok) throw new Error('File not found: ' + res.status);
        return res.text();
    }

    /* ──────────────────────────────────────────────────────────
       URL PARSER
    ────────────────────────────────────────────────────────── */
    function parseGHUrl(input) {
        if (!input) return null;
        input = input.trim().replace(/\/$/, '');
        var m;
        // Gist with user
        m = input.match(/gist\.github\.com\/([^\/]+)\/([0-9a-f]+)/i);
        if (m) return { type:'gist', user:m[1], gistId:m[2] };
        // Gist direct
        m = input.match(/gist\.github\.com\/([0-9a-f]+)/i);
        if (m) return { type:'gist', gistId:m[1] };
        // Blob (file)
        m = input.match(/github\.com\/([^\/]+)\/([^\/]+)\/blob\/([^\/]+)\/(.*)/i);
        if (m) return { type:'blob', owner:m[1], repo:m[2], branch:m[3], path:m[4] };
        // Tree (folder)
        m = input.match(/github\.com\/([^\/]+)\/([^\/]+)\/tree\/([^\/]+)\/(.*)/i);
        if (m) return { type:'tree', owner:m[1], repo:m[2], branch:m[3], path:m[4] };
        // Releases
        m = input.match(/github\.com\/([^\/]+)\/([^\/]+)\/releases/i);
        if (m) return { type:'repo', owner:m[1], repo:m[2] };
        // Repo URL
        m = input.match(/github\.com\/([^\/\s?#]+)\/([^\/\s?#.]+)/i);
        if (m) return { type:'repo', owner:m[1], repo:m[2].replace(/\.git$/,'') };
        // owner/repo
        m = input.match(/^([^\/\s@]+)\/([^\/\s@]+)$/);
        if (m) return { type:'repo', owner:m[1], repo:m[2].replace(/\.git$/,'') };
        // GitHub profile
        m = input.match(/github\.com\/([^\/\s?#]+)$/i);
        if (m) return { type:'user', login:m[1] };
        return null;
    }

    /* ──────────────────────────────────────────────────────────
       PAT MANAGEMENT
    ────────────────────────────────────────────────────────── */
    function savePat() {
        _pat = gv('gh-pat-input');
        try { localStorage.setItem('gh_pat', _pat); } catch(e) {}
        updatePatIndicator();
        checkRate();
    }
    function clearPat() {
        _pat = '';
        try { localStorage.removeItem('gh_pat'); } catch(e) {}
        var inp = el('gh-pat-input');
        if (inp) inp.value = '';
        updatePatIndicator();
        checkRate();
    }
    function togglePatVisibility() {
        var inp = el('gh-pat-input');
        var btn = el('gh-pat-toggle');
        if (!inp || !btn) return;
        if (inp.type === 'password') {
            inp.type = 'text';
            btn.title = 'Hide token';
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
        } else {
            inp.type = 'password';
            btn.title = 'Show token';
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        }
    }
    function updatePatIndicator() {
        var dot = el('gh-pat-dot');
        var clr = el('gh-pat-clear-btn');
        if (dot) dot.style.display = _pat ? 'inline-block' : 'none';
        if (clr) clr.style.display = _pat ? 'inline-flex' : 'none';
    }
    function updateRateBadge(remaining, limit) {
        var b = el('gh-rate-badge');
        if (!b) return;
        var r = parseInt(remaining, 10), l = parseInt(limit, 10);
        b.textContent = r + ' / ' + (l || 60) + ' req left';
        b.className = 'gh-rate-badge ' + (r > 30 ? 'ok' : r > 5 ? 'low' : 'out');
    }
    async function checkRate() {
        try {
            var d = await api('/rate_limit');
            updateRateBadge(d.rate.remaining, d.rate.limit);
        } catch(e) {}
    }

    /* ──────────────────────────────────────────────────────────
       COPY / DOWNLOAD
    ────────────────────────────────────────────────────────── */
    function copyText(text, btn) {
        if (!text) return;
        navigator.clipboard.writeText(text).then(function () {
            if (btn) {
                var orig = btn.innerHTML;
                btn.innerHTML = '✓ Copied';
                btn.classList.add('copied');
                setTimeout(function(){ btn.innerHTML = orig; btn.classList.remove('copied'); }, 1600);
            }
        }).catch(function() {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.focus(); ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        });
    }
    function dlText(text, filename, mime) {
        var a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([text], { type: mime || 'text/plain' }));
        a.download = filename;
        a.click();
    }
    function dlBlobUrl(url, filename) {
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    /* Decode base64 string → Uint8Array (handles binary files correctly) */
    function b64ToUint8Array(b64) {
        var binary = atob(b64);
        var bytes = new Uint8Array(binary.length);
        for (var i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
        return bytes;
    }

    /*
     * Fetch file content via GitHub API (blob endpoint).
     * Works on ALL hosting environments — no CORS preflight issues.
     * raw.githubusercontent.com + Authorization header triggers CORS preflight
     * that is rejected on many shared hosting providers (Hostinger, cPanel, etc).
     * The blob API always returns base64-encoded content with proper CORS headers.
     */
    /* sha is optional — if not supplied it is looked up from _pathMeta */
    async function fetchFileViaApi(fullName, filePath, sha) {
        var effectiveSha = sha || (_pathMeta[filePath] && _pathMeta[filePath].sha);
        if (effectiveSha) {
            /* Blob API: handles files of any size, returns base64 */
            var data = await api('/repos/' + fullName + '/git/blobs/' + effectiveSha);
            return b64ToUint8Array((data.content || '').replace(/\s/g, ''));
        }
        /* Fallback: contents API (files ≤ 1 MB) */
        var data = await api('/repos/' + fullName + '/contents/' + filePath);
        return b64ToUint8Array((data.content || '').replace(/\s/g, ''));
    }

    /*
     * Download a single file via GitHub API → Blob → local download.
     * Called from the tree "download" button.
     * Also updates the button state while downloading.
     */
    async function downloadSingleFile(fullName, branch, filePath, btnEl) {
        var fileName = filePath.split('/').pop();
        var origHTML = btnEl ? btnEl.innerHTML : '';
        if (btnEl) { btnEl.innerHTML = '⏳'; btnEl.disabled = true; }
        try {
            var bytes = await fetchFileViaApi(fullName, filePath);
            var blob = new Blob([bytes]);
            var url  = URL.createObjectURL(blob);
            var a    = document.createElement('a');
            a.href     = url;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(function () { URL.revokeObjectURL(url); }, 30000);
            if (btnEl) {
                btnEl.innerHTML  = '✓';
                btnEl.disabled   = false;
                setTimeout(function () { btnEl.innerHTML = origHTML; }, 1500);
            }
        } catch (e) {
            if (btnEl) { btnEl.innerHTML = origHTML; btnEl.disabled = false; }
            alert('Download failed: ' + e.message
                + '\n\nTip: Add a GitHub Personal Access Token to avoid rate limits on large repos.');
        }
    }

    /* ──────────────────────────────────────────────────────────
       SHARED RENDERERS
    ────────────────────────────────────────────────────────── */
    var IC = {
        star:    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
        fork:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="18" r="3"/><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M6 9v3a3 3 0 0 0 3 3h1m4-9v1a3 3 0 0 1-3 3H9"/></svg>',
        eye:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
        issue:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        dl:      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
        copy:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
        branch:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/></svg>',
        tag:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
        user:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        file:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>',
        folder:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
        link:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
        alert:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    };

    function errHtml(msg) {
        return '<div class="gh-error-box">' + IC.alert + htmlEsc(msg) + '</div>';
    }
    function skeletonHtml(rows) {
        rows = rows || 4;
        var h = '<div class="gh-tab-loading">';
        var widths = ['100%','75%','88%','60%','92%','70%'];
        for (var i = 0; i < rows; i++) h += '<div class="gh-skeleton" style="width:' + widths[i % widths.length] + '"></div>';
        h += '</div>';
        return h;
    }
    function tabLoad(id, fn) {
        var panel = el('tab-' + id + '-content');
        if (!panel) return;
        if (_tabLoaded[id]) return;
        _tabLoaded[id] = true;
        panel.innerHTML = skeletonHtml(5);
        fn().catch(function(e) {
            panel.innerHTML = errHtml(e.message);
            _tabLoaded[id] = false; // allow retry
        });
    }
    function donutHtml(langs, label) {
        if (!langs) return '';
        var total = Object.values(langs).reduce(function(a,b){return a+b;}, 0);
        if (!total) return '';
        var sorted = Object.entries(langs).sort(function(a,b){return b[1]-a[1];}).slice(0, 12);
        var deg = 0, stops = [];
        var items = sorted.map(function(e) {
            var lang = e[0], bytes = e[1];
            var pct = bytes / total * 100;
            var col = getLangColor(lang);
            stops.push(col + ' ' + deg.toFixed(1) + 'deg ' + (deg + pct*3.6).toFixed(1) + 'deg');
            deg += pct * 3.6;
            return { lang:lang, bytes:bytes, pct:pct, col:col };
        });
        var gradient = 'conic-gradient(' + stops.join(', ') + ')';
        var legendHtml = items.map(function(i) {
            return '<div class="gh-legend-item"><span class="gh-legend-dot" style="background:' + i.col + '"></span>'
                + '<span class="gh-legend-lang">' + htmlEsc(i.lang) + '</span>'
                + '<span class="gh-legend-pct">' + i.pct.toFixed(1) + '%</span></div>';
        }).join('');
        return '<div class="gh-chart-wrap">'
            + '<div class="gh-donut-outer"><div class="gh-donut" style="background:' + gradient + '"></div>'
            + '<div class="gh-donut-hole">' + htmlEsc(label || (items.length + ' lang' + (items.length > 1 ? 's' : ''))) + '</div></div>'
            + '<div class="gh-legend">' + legendHtml + '</div></div>';
    }
    function langBarsHtml(langs) {
        if (!langs) return '';
        var total = Object.values(langs).reduce(function(a,b){return a+b;}, 0);
        var sorted = Object.entries(langs).sort(function(a,b){return b[1]-a[1];});
        return '<div class="gh-lang-list">' + sorted.map(function(e) {
            var lang = e[0], bytes = e[1];
            var pct = bytes / total * 100;
            var col = getLangColor(lang);
            return '<div class="gh-lang-row">'
                + '<div class="gh-lang-name"><span class="gh-lang-dot" style="background:' + col + '"></span>' + htmlEsc(lang) + '</div>'
                + '<div class="gh-lang-bar-track"><div class="gh-lang-bar-fill" style="width:' + pct.toFixed(1) + '%;background:' + col + '"></div></div>'
                + '<div class="gh-lang-pct">' + pct.toFixed(1) + '%</div>'
                + '<div class="gh-lang-bytes">' + fmtBytes(bytes) + '</div>'
                + '</div>';
        }).join('') + '</div>';
    }
    function scoresHtml(scores) {
        var bars = [
            { label:'⭐ Popularity',    score: scores.popularity, color:'#f59e0b' },
            { label:'🔧 Maintenance',   score: scores.maintenance, color:'#22c55e' },
            { label:'⚡ Activity',      score: scores.activity, color:'#6366f1' },
        ];
        var total = Math.round((scores.popularity + scores.maintenance + scores.activity) / 3);
        var h = '<div class="gh-scores">';
        bars.forEach(function(b) {
            h += '<div class="gh-score-row">'
                + '<div class="gh-score-label">' + b.label + '</div>'
                + '<div class="gh-score-track"><div class="gh-score-fill" style="width:' + b.score + '%;background:' + b.color + '"></div></div>'
                + '<div class="gh-score-num" style="color:' + b.color + '">' + b.score + '</div>'
                + '</div>';
        });
        h += '</div>';
        h += '<div style="font-size:13px;color:var(--color-text-muted);margin-top:4px">'
            + 'Overall Score: <strong style="color:var(--color-primary);font-size:18px">' + total + '</strong> / 100</div>';
        return h;
    }
    function repoCardHtml(r) {
        var lang = r.language ? '<span class="gh-lang-tag"><span class="gh-lang-dot" style="background:' + getLangColor(r.language) + '"></span>' + htmlEsc(r.language) + '</span>' : '';
        return '<div class="gh-repo-card">'
            + '<div class="gh-repo-card-title"><a href="' + htmlEsc(r.html_url) + '" target="_blank" rel="noopener">' + htmlEsc(r.full_name) + '</a></div>'
            + (r.description ? '<div class="gh-repo-card-desc">' + htmlEsc(r.description) + '</div>' : '')
            + '<div class="gh-repo-card-meta">'
            + (r.stargazers_count ? '<span class="gh-rc-stat">' + IC.star + fmtNum(r.stargazers_count) + '</span>' : '')
            + (r.forks_count ? '<span class="gh-rc-stat">' + IC.fork + fmtNum(r.forks_count) + '</span>' : '')
            + (r.open_issues_count ? '<span class="gh-rc-stat">' + IC.issue + r.open_issues_count + ' issues</span>' : '')
            + lang
            + '<span class="gh-rc-stat gh-text-muted">Updated ' + timeSince(r.updated_at) + '</span>'
            + '</div></div>';
    }

    /* ──────────────────────────────────────────────────────────
       SCORES
    ────────────────────────────────────────────────────────── */
    function calcScores(repo) {
        var stars = repo.stargazers_count || 0;
        var forks = repo.forks_count || 0;
        var issues = repo.open_issues_count || 0;
        var daysOld = (Date.now() - new Date(repo.created_at).getTime()) / 86400000;
        var daysSincePush = (Date.now() - new Date(repo.pushed_at).getTime()) / 86400000;
        var daysSinceUpdate = (Date.now() - new Date(repo.updated_at).getTime()) / 86400000;

        var pop = Math.min(100, Math.round(
            (Math.log(stars + 1) / Math.log(50000)) * 60 +
            (Math.log(forks + 1) / Math.log(10000)) * 40
        ));

        var issueRatio = stars > 0 ? issues / stars : issues;
        var maint = Math.max(0, Math.min(100, Math.round(
            100 - (issueRatio * 20) - (daysSincePush > 365 ? 40 : daysSincePush > 90 ? 20 : 0) +
            (repo.has_wiki ? 5 : 0) + (repo.license ? 10 : 0)
        )));

        var act = Math.max(0, Math.min(100, Math.round(
            100 - (daysSincePush / 3) - (daysSinceUpdate / 6)
        )));

        return { popularity: pop, maintenance: maint, activity: act };
    }

    /* ──────────────────────────────────────────────────────────
       MARKDOWN RENDERER
    ────────────────────────────────────────────────────────── */
    function renderMd(text) {
        if (!text) return '';
        var lines = text.split('\n');
        var out = '', inCode = false, codeLang = '', codeLines = [], listItems = [], ordered = false;

        function flushList() {
            if (!listItems.length) return;
            var tag = ordered ? 'ol' : 'ul';
            out += '<' + tag + ' style="margin:6px 0 6px 20px">' + listItems.map(function(i){return '<li>' + inlineMd(i) + '</li>';}).join('') + '</' + tag + '>';
            listItems = []; ordered = false;
        }

        lines.forEach(function(line) {
            if (line.match(/^```/)) {
                if (!inCode) { flushList(); inCode = true; codeLang = line.slice(3).trim(); codeLines = []; }
                else {
                    var codeHtml = highlight(codeLines.join('\n'), codeLang);
                    out += '<div class="gh-code-wrap"><div class="gh-code-header"><span class="gh-code-lang">' + htmlEsc(codeLang||'code') + '</span></div>'
                        + '<div class="gh-code-body"><pre><code>' + codeHtml + '</code></pre></div></div>';
                    inCode = false; codeLines = []; codeLang = '';
                }
                return;
            }
            if (inCode) { codeLines.push(line); return; }
            var hm = line.match(/^(#{1,6})\s+(.*)/);
            if (hm) { flushList(); var lv = hm[1].length; out += '<h' + lv + ' class="gh-md-h' + lv + '" style="font-size:' + [22,18,15,13.5,13.5,13.5][lv-1] + 'px">' + inlineMd(hm[2]) + '</h' + lv + '>'; return; }
            if (/^[-*_]{3,}$/.test(line.trim())) { flushList(); out += '<hr class="gh-md-body" style="border:none;border-top:1px solid var(--color-border);margin:12px 0">'; return; }
            if (line.startsWith('> ')) { flushList(); out += '<blockquote class="gh-md-body">' + inlineMd(line.slice(2)) + '</blockquote>'; return; }
            var lm = line.match(/^[ \t]*[-*+]\s+(.*)/);
            if (lm) { listItems.push(lm[1]); return; }
            var om = line.match(/^[ \t]*\d+\.\s+(.*)/);
            if (om) { ordered = true; listItems.push(om[1]); return; }
            if (!line.trim()) { flushList(); out += '<br>'; return; }
            flushList();
            out += '<p>' + inlineMd(line) + '</p>';
        });
        flushList();
        return out;
    }

    function inlineMd(t) {
        if (!t) return '';
        return t
            .replace(/\*\*\*(.+?)\*\*\*/g,'<strong><em>$1</em></strong>')
            .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
            .replace(/__(.+?)__/g,'<strong>$1</strong>')
            .replace(/\*(.+?)\*/g,'<em>$1</em>')
            .replace(/_(.+?)_/g,'<em>$1</em>')
            .replace(/~~(.+?)~~/g,'<del>$1</del>')
            .replace(/!\[([^\]]*)\]\(([^)]+)\)/g,'<img src="$2" alt="$1" style="max-width:100%">')
            .replace(/\[([^\]]+)\]\(([^)]+)\)/g,'<a href="$2" target="_blank" rel="noopener">$1</a>')
            .replace(/`([^`]+)`/g,'<code>$1</code>')
            .replace(/(https?:\/\/[^\s<"]+)/g,'<a href="$1" target="_blank" rel="noopener">$1</a>');
    }

    /* ──────────────────────────────────────────────────────────
       SYNTAX HIGHLIGHTER
    ────────────────────────────────────────────────────────── */
    function highlight(code, lang) {
        if (!code) return '';
        if (!lang) return htmlEsc(code);
        lang = lang.toLowerCase();
        var rules = getHlRules(lang);
        if (!rules.length) return htmlEsc(code);
        var spans = [];
        rules.forEach(function(r) {
            var re = new RegExp(r[0].source, r[0].flags);
            var m;
            while ((m = re.exec(code)) !== null) {
                spans.push({ s:m.index, e:m.index + m[0].length, cls:r[1], text:m[0] });
                if (!m[0].length) { re.lastIndex++; }
            }
        });
        spans.sort(function(a,b){ return a.s - b.s || b.e - a.e; });
        var clean = [], last = 0;
        spans.forEach(function(sp) { if (sp.s >= last) { clean.push(sp); last = sp.e; } });
        var out = '', pos = 0;
        clean.forEach(function(sp) {
            if (pos < sp.s) out += htmlEsc(code.slice(pos, sp.s));
            out += '<span class="gh-hl-' + sp.cls + '">' + htmlEsc(sp.text) + '</span>';
            pos = sp.e;
        });
        if (pos < code.length) out += htmlEsc(code.slice(pos));
        return out;
    }
    function getHlRules(lang) {
        var kw, r = [];
        if (['js','javascript','ts','typescript'].indexOf(lang) >= 0) {
            kw = /\b(const|let|var|function|class|return|if|else|for|while|do|switch|case|break|continue|new|this|import|export|default|async|await|try|catch|finally|throw|typeof|instanceof|in|of|void|delete|yield|extends|super|from)\b/g;
            r = [[/\/\/[^\n]*/g,'cmt'],[/\/\*[\s\S]*?\*\//g,'cmt'],[/(["'`])(?:(?!\1)[^\\]|\\.)*\1/g,'str'],[kw,'kw'],[/\b(true|false|null|undefined|NaN|Infinity)\b/g,'lit'],[/\b\d+\.?\d*\b/g,'num']];
        } else if (lang === 'php') {
            r = [[/\/\/[^\n]*|#[^\n]*/g,'cmt'],[/\/\*[\s\S]*?\*\//g,'cmt'],
                 [/(["'])(?:(?!\1)[^\\]|\\.)*\1/g,'str'],[/\$[a-zA-Z_]\w*/g,'var'],
                 [/\b(function|class|return|if|else|elseif|foreach|for|while|do|switch|case|break|continue|new|echo|print|namespace|use|extends|implements|abstract|interface|trait|public|private|protected|static|final|try|catch|throw|null|true|false)\b/g,'kw'],
                 [/\b\d+\.?\d*\b/g,'num']];
        } else if (['python','py'].indexOf(lang) >= 0) {
            r = [[/#[^\n]*/g,'cmt'],[/("""[\s\S]*?"""|'''[\s\S]*?'''|"[^"]*"|'[^']*')/g,'str'],
                 [/\b(def|class|return|if|elif|else|for|while|import|from|as|with|try|except|finally|raise|in|is|not|and|or|pass|break|continue|yield|lambda|global|async|await)\b/g,'kw'],
                 [/\b(True|False|None)\b/g,'lit'],[/\b\d+\.?\d*\b/g,'num']];
        } else if (lang === 'json') {
            r = [[/"[^"]*"\s*:/g,'key'],[/(["'])(?:(?!\1)[^\\]|\\.)*\1/g,'str'],
                 [/\b(true|false|null)\b/g,'lit'],[/\b-?\d+\.?\d*([eE][+-]?\d+)?\b/g,'num']];
        } else if (['html','xml'].indexOf(lang) >= 0) {
            r = [[/<!--[\s\S]*?-->/g,'cmt'],[/<\/?[a-zA-Z][^>]*>/g,'tag'],[/"[^"]*"/g,'str']];
        } else if (['css','scss'].indexOf(lang) >= 0) {
            r = [[/\/\*[\s\S]*?\*\//g,'cmt'],[/(["'])(?:(?!\1)[^\\]|\\.)*\1/g,'str'],
                 [/#[0-9a-fA-F]{3,8}\b/g,'num'],[/\b\d+\.?\d*(px|em|rem|%|vh|vw|s|ms|deg)?/g,'num'],
                 [/[a-zA-Z-]+(?=\s*:)/g,'prop'],[/[.#][a-zA-Z][a-zA-Z0-9_-]*/g,'sel']];
        } else if (['yaml','yml'].indexOf(lang) >= 0) {
            r = [[/#[^\n]*/g,'cmt'],[/(["'])(?:(?!\1)[^\\]|\\.)*\1/g,'str'],
                 [/^[a-zA-Z_][a-zA-Z0-9_-]*\s*:/gm,'key'],
                 [/\b(true|false|null|yes|no)\b/g,'lit'],[/\b\d+\.?\d*\b/g,'num']];
        } else if (['bash','sh','shell'].indexOf(lang) >= 0) {
            r = [[/#[^\n]*/g,'cmt'],[/(["'])(?:(?!\1)[^\\]|\\.)*\1/g,'str'],
                 [/\b(if|then|else|elif|fi|for|while|do|done|case|esac|function|return|exit|echo|export|source)\b/g,'kw'],
                 [/\$\{?[a-zA-Z_]\w*\}?/g,'var']];
        } else if (['go','golang'].indexOf(lang) >= 0) {
            r = [[/\/\/[^\n]*/g,'cmt'],[/\/\*[\s\S]*?\*\//g,'cmt'],[/(["'`])(?:(?!\1)[^\\]|\\.)*\1/g,'str'],
                 [/\b(func|package|import|return|if|else|for|range|switch|case|break|continue|var|const|type|struct|interface|map|chan|go|defer|select|fallthrough|nil|true|false)\b/g,'kw'],
                 [/\b\d+\.?\d*\b/g,'num']];
        } else if (['rust'].indexOf(lang) >= 0) {
            r = [[/\/\/[^\n]*/g,'cmt'],[/\/\*[\s\S]*?\*\//g,'cmt'],[/(["'])(?:(?!\1)[^\\]|\\.)*\1/g,'str'],
                 [/\b(fn|let|mut|const|pub|use|mod|impl|trait|struct|enum|match|if|else|for|while|loop|return|break|continue|async|await|dyn|self|true|false|null)\b/g,'kw'],
                 [/\b\d+\.?\d*\b/g,'num']];
        }
        return r;
    }

    /* ──────────────────────────────────────────────────────────
       INIT + HERO
    ────────────────────────────────────────────────────────── */
    function init() {
        loadStorage();
        if (_pat) { var e = el('gh-pat-input'); if (e) e.value = _pat; }
        updatePatIndicator();
        renderHeroFooter();
        checkRate();
    }

    function renderHeroFooter() {
        var rb = el('gh-hero-recents');
        if (rb) {
            if (_recent.length === 0) {
                rb.innerHTML = '<div class="gh-hero-section-title">Recent Searches</div><div class="gh-text-muted" style="font-size:12px">None yet</div>';
            } else {
                rb.innerHTML = '<div class="gh-hero-section-title">Recent Searches</div>'
                    + '<div class="gh-pill-list">' + _recent.map(function(r, i) {
                        return '<span class="gh-pill" title="' + htmlEsc(r.url) + '">'
                            + '<span onclick="GH.setUrl(' + JSON.stringify(r.url) + ')">' + htmlEsc(r.name || r.url) + '</span>'
                            + '<span class="gh-pill-del" onclick="GH.removeRecent(' + i + ')">×</span>'
                            + '</span>';
                    }).join('') + '</div>';
            }
        }
        var fb = el('gh-hero-favs');
        if (fb) {
            if (_favs.length === 0) {
                fb.innerHTML = '<div class="gh-hero-section-title">Favorites</div><div class="gh-text-muted" style="font-size:12px">Star a repo to save it</div>';
            } else {
                fb.innerHTML = '<div class="gh-hero-section-title">Favorites</div>'
                    + '<div class="gh-pill-list">' + _favs.map(function(f, i) {
                        return '<span class="gh-pill">'
                            + '<span onclick="GH.setUrl(' + JSON.stringify(f.url) + ')">' + IC.star.replace('currentColor','#f59e0b') + ' ' + htmlEsc(f.name) + '</span>'
                            + '<span class="gh-pill-del" onclick="GH.removeFav(' + i + ')">×</span>'
                            + '</span>';
                    }).join('') + '</div>';
            }
        }
    }

    function setUrl(url) {
        sv('gh-main-url', url);
        analyze();
    }
    function useExample(url) {
        sv('gh-main-url', url);
        analyze();
    }

    /* ──────────────────────────────────────────────────────────
       MAIN ANALYZE
    ────────────────────────────────────────────────────────── */
    async function analyze() {
        var raw = gv('gh-main-url');
        var p = parseGHUrl(raw);
        if (!p || !p.owner || !p.repo) {
            var s = el('gh-hero-status');
            if (s) s.textContent = 'Please enter a valid GitHub repository URL.';
            return;
        }
        var btn = el('gh-analyze-btn');
        var status = el('gh-hero-status');
        if (btn) { btn.classList.add('loading'); btn.textContent = 'Analyzing…'; }
        if (status) { status.textContent = ''; status.className = 'gh-hero-status info'; }

        try {
            _repo = await api('/repos/' + p.owner + '/' + p.repo);
            _langs = null; _treeData = null; _tabLoaded = {};

            addToRecent(raw, _repo.full_name);
            renderRepoHeader(_repo);

            var results = el('gh-results');
            if (results) results.style.display = 'block';

            switchTab('overview');

            // Scroll to results
            if (results) results.scrollIntoView({ behavior:'smooth', block:'start' });

        } catch(e) {
            if (status) { status.textContent = '⚠ ' + e.message; status.className = 'gh-hero-status'; }
        } finally {
            if (btn) { btn.classList.remove('loading'); btn.innerHTML = IC.star.replace('currentColor','white').replace('fill="currentColor"','fill="white"') + ' Analyze'; btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg> Analyze Repository'; }
        }
    }

    /* ──────────────────────────────────────────────────────────
       REPO HEADER
    ────────────────────────────────────────────────────────── */
    function renderRepoHeader(r) {
        var rh = el('gh-repo-header-inner');
        if (!rh) return;
        var topics = (r.topics || []).map(function(t){ return '<span class="gh-topic">' + htmlEsc(t) + '</span>'; }).join('');
        var lang = r.language ? '<span class="gh-lang-tag"><span class="gh-lang-dot" style="background:' + getLangColor(r.language) + '"></span>' + htmlEsc(r.language) + '</span>' : '';
        var license = r.license ? htmlEsc(r.license.name) : 'No license';
        var zipUrl = 'https://github.com/' + htmlEsc(r.full_name) + '/archive/refs/heads/' + htmlEsc(r.default_branch) + '.zip';

        rh.innerHTML = '<div class="gh-repo-header-top">'
            + '<img class="gh-owner-avatar" src="' + htmlEsc(r.owner.avatar_url) + '" alt="' + htmlEsc(r.owner.login) + '">'
            + '<div class="gh-repo-title-block">'
            + '<div class="gh-repo-full-name">'
            + '<a href="' + htmlEsc(r.html_url) + '" target="_blank" rel="noopener">' + htmlEsc(r.full_name) + '</a>'
            + '<span class="gh-vis-badge">' + htmlEsc(r.visibility || 'public') + '</span>'
            + lang + '</div>'
            + (r.description ? '<div class="gh-repo-description">' + htmlEsc(r.description) + '</div>' : '')
            + '</div></div>'
            + (topics ? '<div class="gh-repo-topics">' + topics + '</div>' : '')
            + '<div class="gh-repo-stats-row">'
            + '<span class="gh-stat-item gh-star-val">' + '<svg class="gh-star-icon" viewBox="0 0 24 24" fill="#f59e0b" width="14" height="14"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><span class="gh-stat-val">' + fmtNum(r.stargazers_count) + '</span> stars</span>'
            + '<span class="gh-stat-item">' + IC.fork + '<span class="gh-stat-val">' + fmtNum(r.forks_count) + '</span> forks</span>'
            + '<span class="gh-stat-item">' + IC.eye + '<span class="gh-stat-val">' + fmtNum(r.subscribers_count) + '</span> watching</span>'
            + '<span class="gh-stat-item">' + IC.issue + '<span class="gh-stat-val">' + fmtNum(r.open_issues_count) + '</span> issues</span>'
            + '<span class="gh-stat-item" style="font-size:12px;color:var(--color-text-muted)">' + htmlEsc(license) + '</span>'
            + '</div>'
            + '<div class="gh-repo-actions">'
            + '<a href="' + zipUrl + '" class="gh-btn gh-btn-primary" target="_blank">' + IC.dl + ' Download ZIP</a>'
            + '<button class="gh-btn gh-btn-green" onclick="GH.addToFavorites()">' + IC.star + ' Favorite</button>'
            + '<button class="gh-btn" onclick="GH.copyText(\'https://github.com/' + r.full_name + '\',this)">' + IC.copy + ' Share</button>'
            + '<a href="' + htmlEsc(r.html_url) + '" target="_blank" class="gh-btn">' + IC.link + ' GitHub</a>'
            + '</div>';

        // Update sidebar
        updateSidebar(r);
    }

    /* ──────────────────────────────────────────────────────────
       SIDEBAR
    ────────────────────────────────────────────────────────── */
    function updateSidebar(r) {
        var sb = el('gh-sidebar-stats');
        if (!sb) return;
        var scores = calcScores(r);
        sb.innerHTML = '<div class="gh-sb-stat-row"><span class="gh-sb-stat-label">' + IC.star + ' Stars</span><span class="gh-sb-stat-val" style="color:#d97706">' + fmtNum(r.stargazers_count) + '</span></div>'
            + '<div class="gh-sb-stat-row"><span class="gh-sb-stat-label">' + IC.fork + ' Forks</span><span class="gh-sb-stat-val">' + fmtNum(r.forks_count) + '</span></div>'
            + '<div class="gh-sb-stat-row"><span class="gh-sb-stat-label">' + IC.issue + ' Issues</span><span class="gh-sb-stat-val">' + r.open_issues_count + '</span></div>'
            + '<div class="gh-sb-stat-row"><span class="gh-sb-stat-label">' + IC.branch + ' Branch</span><span class="gh-sb-stat-val gh-text-mono" style="font-size:11.5px">' + htmlEsc(r.default_branch) + '</span></div>'
            + '<div class="gh-sb-stat-row"><span class="gh-sb-stat-label">📦 Size</span><span class="gh-sb-stat-val">' + fmtKB(r.size) + '</span></div>'
            + '<div class="gh-sb-stat-row"><span class="gh-sb-stat-label">⭐ Score</span><span class="gh-sb-stat-val" style="color:var(--color-primary)">' + Math.round((scores.popularity+scores.maintenance+scores.activity)/3) + '/100</span></div>';

        var zipUrl = 'https://github.com/' + r.full_name + '/archive/refs/heads/' + r.default_branch + '.zip';
        sb.innerHTML += '<a href="' + zipUrl + '" class="gh-sb-full-btn" target="_blank">' + IC.dl + ' Download ' + htmlEsc(r.default_branch) + '.zip</a>';

        // Lazy-load latest release into sidebar
        var srel = el('gh-sidebar-release');
        if (srel) {
            srel.innerHTML = '<div class="gh-sb-release">Loading…</div>';
            api('/repos/' + r.full_name + '/releases/latest').then(function(rel) {
                srel.innerHTML = '<div class="gh-sb-release">'
                    + '<div class="gh-sb-release-tag">' + htmlEsc(rel.tag_name) + '</div>'
                    + '<div>' + htmlEsc(rel.name || rel.tag_name) + '</div>'
                    + '<div style="font-size:11px;margin-top:3px;color:var(--color-text-muted)">' + timeSince(rel.published_at) + '</div>'
                    + '<a href="' + htmlEsc(rel.html_url) + '" target="_blank" class="gh-sb-full-btn" style="margin-top:8px">' + IC.dl + ' View Release</a>'
                    + '</div>';
            }).catch(function() {
                srel.innerHTML = '<div class="gh-sb-release" style="font-size:12px;color:var(--color-text-muted)">No releases yet</div>';
            });
        }
    }

    /* ──────────────────────────────────────────────────────────
       TAB SYSTEM
    ────────────────────────────────────────────────────────── */
    function switchTab(id) {
        _activeTab = id;
        document.querySelectorAll('.gh-tab-btn').forEach(function(b) {
            b.classList.toggle('active', b.dataset.tab === id);
        });
        document.querySelectorAll('.gh-tab-panel').forEach(function(p) {
            p.classList.toggle('active', p.id === 'gh-tab-' + id);
        });
        loadTab(id);
    }

    function loadTab(id) {
        if (!_repo) return;
        var loaders = {
            overview:     tabOverview,
            downloads:    tabDownloads,
            branches:     tabBranches,
            releases:     tabReleases,
            files:        tabFiles,
            readme:       tabReadme,
            contributors: tabContributors,
            commits:      tabCommits,
            languages:    tabLanguages,
            dependencies: tabDependencies,
            analytics:    tabAnalytics,
            badges:       tabBadges,
            widgets:      tabWidgets,
            api:          tabApi,
            seo:          tabSeo,
            compare:      tabCompare,
            tools:        tabTools,
        };
        if (loaders[id]) tabLoad(id, loaders[id]);
    }

    /* ──────────────────────────────────────────────────────────
       TAB: OVERVIEW
    ────────────────────────────────────────────────────────── */
    async function tabOverview() {
        var r = _repo;
        if (!_langs) _langs = await api('/repos/' + r.full_name + '/languages');
        var scores = calcScores(r);
        var created = fmtDate(r.created_at);
        var updated = fmtDate(r.updated_at);
        var pushed  = fmtDate(r.pushed_at);

        var h = '';
        // Stats grid
        h += '<div class="gh-stats-grid">'
            + '<div class="gh-stat-card gh-stat-card-star"><div class="gh-stat-card-val">' + fmtNum(r.stargazers_count) + '</div><div class="gh-stat-card-label">Stars</div></div>'
            + '<div class="gh-stat-card"><div class="gh-stat-card-val">' + fmtNum(r.forks_count) + '</div><div class="gh-stat-card-label">Forks</div></div>'
            + '<div class="gh-stat-card"><div class="gh-stat-card-val">' + r.open_issues_count + '</div><div class="gh-stat-card-label">Open Issues</div></div>'
            + '<div class="gh-stat-card"><div class="gh-stat-card-val">' + fmtKB(r.size) + '</div><div class="gh-stat-card-label">Size</div></div>'
            + '</div>';

        // Info table
        h += '<div class="gh-card"><table class="gh-info-table">'
            + '<tr><td>Default branch</td><td><code style="font-family:monospace">' + htmlEsc(r.default_branch) + '</code></td></tr>'
            + '<tr><td>Created</td><td>' + created + '</td></tr>'
            + '<tr><td>Last updated</td><td>' + updated + '</td></tr>'
            + '<tr><td>Last push</td><td>' + pushed + ' (' + timeSince(r.pushed_at) + ')</td></tr>'
            + '<tr><td>Homepage</td><td>' + (r.homepage ? '<a href="' + htmlEsc(r.homepage) + '" target="_blank" rel="noopener">' + htmlEsc(r.homepage) + '</a>' : '–') + '</td></tr>'
            + '<tr><td>License</td><td>' + (r.license ? htmlEsc(r.license.name) : '–') + '</td></tr>'
            + '<tr><td>Language</td><td>' + (r.language || '–') + '</td></tr>'
            + '<tr><td>Topics</td><td>' + ((r.topics||[]).map(function(t){ return '<span class="gh-topic">' + htmlEsc(t) + '</span>'; }).join(' ') || '–') + '</td></tr>'
            + '<tr><td>Has wiki</td><td>' + (r.has_wiki ? 'Yes' : 'No') + '</td></tr>'
            + '<tr><td>Has pages</td><td>' + (r.has_pages ? 'Yes' : 'No') + '</td></tr>'
            + '<tr><td>Is fork</td><td>' + (r.fork ? 'Yes — forked from <a href="' + htmlEsc(r.parent ? r.parent.html_url : '#') + '" target="_blank">' + htmlEsc(r.parent ? r.parent.full_name : 'unknown') + '</a>' : 'No') + '</td></tr>'
            + '</table></div>';

        // Language distribution
        if (_langs && Object.keys(_langs).length > 0) {
            h += '<div class="gh-section-title">Language Distribution</div>';
            h += donutHtml(_langs, r.language);
        }

        // Scores
        h += '<div class="gh-section-title">Repository Health</div>';
        h += scoresHtml(scores);

        el('tab-overview-content').innerHTML = h;
    }

    /* ──────────────────────────────────────────────────────────
       TAB: DOWNLOADS
    ────────────────────────────────────────────────────────── */
    async function tabDownloads() {
        var r = _repo;
        var owner = r.owner.login, repo = r.name, defBranch = r.default_branch;

        // Clone URLs
        var httpsUrl = 'https://github.com/' + r.full_name + '.git';
        var sshUrl   = 'git@github.com:' + r.full_name + '.git';
        var cliCmd   = 'gh repo clone ' + r.full_name;
        var zipUrl   = 'https://github.com/' + r.full_name + '/archive/refs/heads/' + defBranch + '.zip';
        var tarUrl   = 'https://github.com/' + r.full_name + '/archive/refs/heads/' + defBranch + '.tar.gz';

        var h = '';
        // Repo ZIPs
        h += '<div class="gh-section-title">Repository Download</div>';
        h += '<div class="gh-card">';
        h += '<div class="gh-repo-actions" style="flex-wrap:wrap;gap:8px">'
            + '<a href="' + zipUrl + '" class="gh-btn gh-btn-primary" target="_blank">' + IC.dl + ' Download ' + htmlEsc(defBranch) + '.zip</a>'
            + '<a href="' + tarUrl + '" class="gh-btn" target="_blank">' + IC.dl + ' Download .tar.gz</a>'
            + '</div>';
        h += '</div>';

        // Clone URLs
        h += '<div class="gh-section-title">Clone URLs</div>';
        h += '<div class="gh-clone-rows">'
            + '<div class="gh-clone-row"><span class="gh-clone-type">HTTPS</span><span class="gh-clone-url">' + htmlEsc(httpsUrl) + '</span><button class="gh-btn gh-btn-sm" onclick="GH.copyText(\'' + httpsUrl + '\',this)">' + IC.copy + ' Copy</button></div>'
            + '<div class="gh-clone-row"><span class="gh-clone-type">SSH</span><span class="gh-clone-url">' + htmlEsc(sshUrl) + '</span><button class="gh-btn gh-btn-sm" onclick="GH.copyText(\'' + sshUrl + '\',this)">' + IC.copy + ' Copy</button></div>'
            + '<div class="gh-clone-row"><span class="gh-clone-type">CLI</span><span class="gh-clone-url">' + htmlEsc(cliCmd) + '</span><button class="gh-btn gh-btn-sm" onclick="GH.copyText(\'' + cliCmd + '\',this)">' + IC.copy + ' Copy</button></div>'
            + '</div>';

        // File downloader
        h += '<div class="gh-section-title">File Downloader</div>';
        h += '<div class="gh-card">';
        h += '<p style="font-size:12.5px;color:var(--color-text-muted);margin-bottom:8px">Paste a GitHub file URL to preview and download it.</p>';
        h += '<div class="gh-input-row">';
        h += '<input class="gh-input-sm" id="dl-file-url" type="url" placeholder="https://github.com/' + r.full_name + '/blob/' + defBranch + '/README.md">';
        h += '<button class="gh-btn gh-btn-primary" onclick="GH.fetchAndPreviewFile()">' + IC.dl + ' Fetch</button>';
        h += '</div><div id="dl-file-out"></div></div>';

        // Folder downloader
        h += '<div class="gh-section-title">Folder Downloader</div>';
        h += '<div class="gh-card">';
        h += '<p style="font-size:12.5px;color:var(--color-text-muted);margin-bottom:8px">Enter a GitHub folder URL to preview files and download as ZIP.</p>';
        h += '<div class="gh-input-row">';
        h += '<input class="gh-input-sm" id="dl-folder-url" type="url" placeholder="https://github.com/' + r.full_name + '/tree/' + defBranch + '/src">';
        h += '<button class="gh-btn gh-btn-primary" onclick="GH.fetchFolderPreview()">' + IC.folder + ' Preview</button>';
        h += '</div><div id="dl-folder-out"></div></div>';

        // Gist downloader
        h += '<div class="gh-section-title">Gist Downloader</div>';
        h += '<div class="gh-card">';
        h += '<div class="gh-input-row">';
        h += '<input class="gh-input-sm" id="dl-gist-url" type="url" placeholder="https://gist.github.com/user/abc123">';
        h += '<button class="gh-btn gh-btn-primary" onclick="GH.fetchGist()">' + IC.dl + ' Load Gist</button>';
        h += '</div><div id="dl-gist-out"></div></div>';

        el('tab-downloads-content').innerHTML = h;
    }

    // File Downloader logic
    async function fetchAndPreviewFile() {
        var raw = gv('dl-file-url');
        var p = parseGHUrl(raw);
        var out = el('dl-file-out');
        if (!out) return;
        if (!p || p.type !== 'blob') {
            out.innerHTML = errHtml('Enter a GitHub file URL like: https://github.com/user/repo/blob/main/file.js');
            return;
        }
        out.innerHTML = skeletonHtml(3);
        try {
            var rawUrl = 'https://raw.githubusercontent.com/' + p.owner + '/' + p.repo + '/' + p.branch + '/' + p.path;
            var code = await rawFetch(rawUrl);
            var ext = p.path.split('.').pop().toLowerCase();
            var highlighted = highlight(code, ext);
            out.innerHTML = '<div class="gh-code-wrap"><div class="gh-code-header">'
                + '<span class="gh-code-lang">' + htmlEsc(p.path) + '</span>'
                + '<div style="display:flex;gap:6px">'
                + '<button class="gh-btn gh-btn-sm" onclick="GH.copyText(' + JSON.stringify(rawUrl) + ',this)">' + IC.copy + ' Raw URL</button>'
                + '<button class="gh-btn gh-btn-sm" onclick="GH.copyText(' + JSON.stringify(code) + ',this)">' + IC.copy + ' Copy Code</button>'
                + '<button class="gh-btn gh-btn-sm gh-btn-green" onclick="GH.dlText(' + JSON.stringify(code) + ',' + JSON.stringify(p.path.split('/').pop()) + ')">' + IC.dl + ' Download</button>'
                + '</div></div>'
                + '<div class="gh-code-body"><pre><code>' + highlighted + '</code></pre></div></div>';
        } catch(e) {
            out.innerHTML = errHtml(e.message);
        }
    }

    // Folder Downloader
    async function fetchFolderPreview() {
        var raw = gv('dl-folder-url');
        var p = parseGHUrl(raw);
        var out = el('dl-folder-out');
        if (!out) return;
        if (!p || p.type !== 'tree') {
            out.innerHTML = errHtml('Enter a folder URL: https://github.com/user/repo/tree/main/src');
            return;
        }
        out.innerHTML = skeletonHtml(4);
        try {
            var tree = await api('/repos/' + p.owner + '/' + p.repo + '/git/trees/' + p.branch, { recursive: '1' });
            var prefix = p.path ? p.path + '/' : '';
            var files = tree.tree.filter(function(i){ return i.type === 'blob' && i.path.startsWith(prefix); });
            if (!files.length) { out.innerHTML = errHtml('No files found in this folder.'); return; }

            var h = '<div class="gh-card"><p style="font-size:12.5px;color:var(--color-text-muted);margin-bottom:8px"><strong>' + files.length + ' files</strong> in <code style="font-family:monospace">' + htmlEsc(p.path || '/') + '</code></p>';
            h += '<div class="gh-tree">';
            files.slice(0, 60).forEach(function(f) {
                var rel = f.path.slice(prefix.length);
                var rawUrl = 'https://raw.githubusercontent.com/' + p.owner + '/' + p.repo + '/' + p.branch + '/' + f.path;
                h += '<div class="gh-tree-item gh-tree-file">' + IC.file
                    + '<span class="gh-tree-name">' + htmlEsc(rel) + '</span>'
                    + '<a href="' + htmlEsc(rawUrl) + '" class="gh-btn gh-btn-sm" download target="_blank">' + IC.dl + '</a>'
                    + '</div>';
            });
            if (files.length > 60) h += '<div style="font-size:12px;padding:6px;color:var(--color-text-muted)">…and ' + (files.length - 60) + ' more files</div>';
            h += '</div>';
            h += '<button class="gh-btn gh-btn-primary" style="margin-top:10px" onclick="GH.downloadFolderZip(' + JSON.stringify(p) + ',' + JSON.stringify(files.map(function(f){return{path:f.path};})) + ')">' + IC.dl + ' Download All as ZIP (' + files.length + ' files)</button>';
            h += '</div>';
            out.innerHTML = h;
        } catch(e) {
            out.innerHTML = errHtml(e.message);
        }
    }

    async function downloadTreeFolder(repoFullName, branch, folderPath) {
        if (!_treeData) { alert('Tree data not loaded yet. Please wait a moment and try again.'); return; }
        var prefix = folderPath + '/';
        var files = _treeData.tree.filter(function(i) { return i.type === 'blob' && i.path.indexOf(prefix) === 0; });
        if (!files.length) { alert('No files found in folder: ' + folderPath); return; }
        var parts = repoFullName.split('/');
        var p = { owner: parts[0], repo: parts[1], path: folderPath, branch: branch };
        /* Pass SHA so fetchFileViaApi can use the blob endpoint (no CORS issues) */
        await downloadFolderZip(p, files.map(function(f) { return { path: f.path, sha: f.sha }; }));
    }

    async function downloadFolderZip(p, files) {
        var JSZip = await loadJSZip();
        var zip = new JSZip();
        var folderName = (p.path || p.repo).split('/').pop();
        var folder = zip.folder(folderName);
        var prefix = p.path ? p.path + '/' : '';
        var errors = [];
        var fullName = p.owner + '/' + p.repo;
        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            var rel = f.path.slice(prefix.length);
            try {
                /* Pass f.sha directly so blob API is used without _pathMeta lookup */
                var bytes = await fetchFileViaApi(fullName, f.path, f.sha);
                folder.file(rel, bytes, { binary: true });
            } catch(e2) {
                errors.push(f.path);
            }
        }
        if (errors.length && files.length && errors.length === files.length) {
            throw new Error('All ' + files.length + ' files failed to download. Please add a GitHub PAT for better rate limits.');
        }
        var blob = await zip.generateAsync({ type: 'blob', compression: 'DEFLATE', compressionOptions: { level: 6 } });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = folderName + '.zip';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(function() { URL.revokeObjectURL(a.href); }, 30000);
    }

    // Gist fetcher
    async function fetchGist() {
        var raw = gv('dl-gist-url');
        var p = parseGHUrl(raw);
        var out = el('dl-gist-out');
        if (!out) return;
        if (!p || p.type !== 'gist') { out.innerHTML = errHtml('Enter a gist URL like https://gist.github.com/user/abc123'); return; }
        out.innerHTML = skeletonHtml(3);
        try {
            var gist = await api('/gists/' + p.gistId);
            var fileKeys = Object.keys(gist.files);
            var h = '<div class="gh-card">';
            h += '<div class="gh-card-title">' + htmlEsc(gist.description || 'Gist ' + gist.id) + '</div>';
            h += '<div class="gh-card-desc">' + fileKeys.length + ' file(s) · ' + timeSince(gist.created_at) + '</div>';
            fileKeys.forEach(function(name) {
                var f = gist.files[name];
                h += '<div style="margin-bottom:10px"><div class="gh-code-header" style="background:var(--color-surface);border:1px solid var(--color-border);border-radius:5px 5px 0 0;padding:6px 10px;display:flex;align-items:center;justify-content:space-between">'
                    + '<span class="gh-code-lang">' + htmlEsc(name) + '</span>'
                    + '<div style="display:flex;gap:6px">'
                    + '<a href="' + htmlEsc(f.raw_url) + '" class="gh-btn gh-btn-sm" download>' + IC.dl + ' Download</a>'
                    + '</div></div>';
                if (f.content) {
                    var ext = name.split('.').pop();
                    h += '<div class="gh-code-body" style="border:1px solid var(--color-border);border-top:none;border-radius:0 0 5px 5px;max-height:200px;overflow:auto"><pre><code>' + highlight(f.content, ext) + '</code></pre></div>';
                }
                h += '</div>';
            });
            h += '<a href="' + htmlEsc(gist.html_url) + '/download" class="gh-btn gh-btn-primary" target="_blank">' + IC.dl + ' Download ZIP</a>';
            h += '</div>';
            out.innerHTML = h;
        } catch(e) {
            out.innerHTML = errHtml(e.message);
        }
    }

    /* ──────────────────────────────────────────────────────────
       TAB: BRANCHES
    ────────────────────────────────────────────────────────── */
    async function tabBranches() {
        var r = _repo;
        var branches = await api('/repos/' + r.full_name + '/branches', { per_page: 100 });
        var h = '<p style="font-size:13px;color:var(--color-text-muted);margin-bottom:12px">' + branches.length + ' branch' + (branches.length !== 1 ? 'es' : '') + '</p>';
        h += '<div class="gh-branch-list">';
        branches.forEach(function(b) {
            var isDefault = b.name === r.default_branch;
            var zipUrl = 'https://github.com/' + r.full_name + '/archive/refs/heads/' + encodeURIComponent(b.name) + '.zip';
            h += '<div class="gh-branch-item">'
                + '<div class="gh-branch-name">' + IC.branch + htmlEsc(b.name)
                + (isDefault ? ' <span class="gh-branch-default">default</span>' : '')
                + '</div>'
                + '<span class="gh-branch-sha" title="' + htmlEsc(b.commit.sha) + '">' + b.commit.sha.slice(0,7) + '</span>'
                + '<div class="gh-branch-actions">'
                + '<button class="gh-btn gh-btn-sm" onclick="GH.copyText(\'' + htmlEsc(b.name) + '\',this)">' + IC.copy + '</button>'
                + '<a href="' + zipUrl + '" class="gh-btn gh-btn-sm gh-btn-green" target="_blank">' + IC.dl + ' ZIP</a>'
                + '</div></div>';
        });
        h += '</div>';
        el('tab-branches-content').innerHTML = h;
    }

    /* ──────────────────────────────────────────────────────────
       TAB: RELEASES
    ────────────────────────────────────────────────────────── */
    async function tabReleases() {
        var r = _repo;
        var releases = await api('/repos/' + r.full_name + '/releases', { per_page: 30 });
        if (!releases.length) { el('tab-releases-content').innerHTML = '<div class="gh-empty-tab"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' + IC.tag + '</svg><p>No releases yet.</p></div>'; return; }
        var h = '';
        releases.forEach(function(rel, idx) {
            var isLatest = idx === 0;
            var assets = rel.assets || [];
            var zipUrl = rel.zipball_url, tarUrl = rel.tarball_url;
            h += '<div class="gh-release">'
                + '<div class="gh-release-header" onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display===\'none\'?\'block\':\'none\'">'
                + '<span class="gh-release-tag ' + (isLatest ? 'gh-release-latest' : '') + '">' + htmlEsc(rel.tag_name) + (isLatest ? ' ★ Latest' : '') + '</span>'
                + '<span class="gh-release-name">' + htmlEsc(rel.name || rel.tag_name) + '</span>'
                + '<span class="gh-release-date">' + fmtDate(rel.published_at) + '</span>'
                + '</div>'
                + '<div class="gh-release-body" style="display:none">'
                + (rel.body ? '<div class="gh-release-notes">' + htmlEsc(rel.body.slice(0, 600)) + (rel.body.length > 600 ? '…' : '') + '</div>' : '')
                + '<div class="gh-release-assets">'
                + '<a href="' + htmlEsc(zipUrl) + '" class="gh-asset-link" target="_blank">' + IC.dl + ' Source.zip</a>'
                + '<a href="' + htmlEsc(tarUrl) + '" class="gh-asset-link" target="_blank">' + IC.dl + ' Source.tar.gz</a>'
                + assets.map(function(a){ return '<a href="' + htmlEsc(a.browser_download_url) + '" class="gh-asset-link" target="_blank">' + IC.dl + ' ' + htmlEsc(a.name) + ' (' + fmtBytes(a.size) + ')</a>'; }).join('')
                + '</div></div></div>';
        });
        el('tab-releases-content').innerHTML = h;
    }

    /* ──────────────────────────────────────────────────────────
       TAB: FILES (TREE BROWSER + MULTI-SELECT)
    ────────────────────────────────────────────────────────── */
    async function tabFiles() {
        var r = _repo;
        if (!_treeData) {
            _treeData = await api('/repos/' + r.full_name + '/git/trees/' + r.default_branch, { recursive: '1' });
        }
        var tree = _treeData.tree;
        var panel = el('tab-files-content');

        // Build path metadata index for fast lookup
        _pathMeta = {};
        tree.forEach(function(item) { _pathMeta[item.path] = item; });

        // Reset selection on tree reload
        _selectedItems = {};

        var blobs   = tree.filter(function(i){ return i.type === 'blob'; });
        var folders = tree.filter(function(i){ return i.type === 'tree'; });
        var nested  = buildNestedTree(tree);

        var h = '';

        // ── Selection action bar (hidden until items are checked) ──
        h += '<div class="gh-sel-bar" id="gh-sel-bar">'
            + '<div class="gh-sel-bar-info">'
            + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>'
            + '<span id="gh-sel-count" class="gh-sel-count">0 files selected</span>'
            + '<span class="gh-sel-sep">·</span>'
            + '<span id="gh-sel-size" class="gh-sel-size">0 B</span>'
            + '</div>'
            + '<div class="gh-sel-bar-paths" id="gh-sel-paths"></div>'
            + '<div class="gh-sel-bar-actions">'
            + '<button class="gh-btn gh-btn-sm" onclick="GH.clearSelection()">✕ Clear</button>'
            + '<button id="gh-dl-sel-btn" class="gh-btn gh-btn-sm gh-btn-primary" onclick="GH.downloadSelectedZip()">'
            + IC.dl + ' Download ZIP'
            + '</button>'
            + '</div>'
            + '</div>';

        // ── Stats row ──
        h += '<div class="gh-files-meta">'
            + '<span>' + blobs.length + ' files · ' + folders.length + ' folders</span>'
            + '<span class="gh-files-meta-hint">Check items to select for batch ZIP download</span>'
            + '</div>';

        // ── Two-column layout: tree + preview ──
        h += '<div class="gh-files-layout">'
            + '<div class="gh-files-tree-col">'
            + '<div class="gh-tree" id="gh-file-tree">' + renderTreeHtml(nested, r, 0, '') + '</div>'
            + '</div>'
            + '<div id="gh-file-preview" class="gh-files-preview-col"></div>'
            + '</div>';

        panel.innerHTML = h;
    }

    function buildNestedTree(flat) {
        var root = {};
        flat.forEach(function(item) {
            var parts = item.path.split('/');
            var node = root;
            parts.forEach(function(part, i) {
                if (!node[part]) node[part] = { _meta: null, _children: {} };
                if (i === parts.length - 1) node[part]._meta = item;
                node = node[part]._children;
            });
        });
        return root;
    }

    function renderTreeHtml(node, r, depth, parentPath) {
        var h = '';
        var keys = Object.keys(node).sort(function(a, b) {
            var aFolder = Object.keys(node[a]._children).length > 0;
            var bFolder = Object.keys(node[b]._children).length > 0;
            if (aFolder && !bFolder) return -1;
            if (!aFolder && bFolder) return 1;
            return a.localeCompare(b);
        });
        keys.forEach(function(name) {
            var entry    = node[name];
            var meta     = entry._meta;
            var children = entry._children;
            var hasChildren = Object.keys(children).length > 0;
            var toggleId = 'tr-' + Math.random().toString(36).slice(2);
            var fullPath = parentPath ? parentPath + '/' + name : name;
            // safe path for single-quoted JS attribute value
            var safePath = fullPath.replace(/\\/g,'\\\\').replace(/'/g,"\\'");
            var cbId = 'cb-' + fullPath.replace(/[^a-z0-9]/gi,'_');

            if (hasChildren) {
                h += '<div class="gh-tree-item gh-tree-folder">'
                    + '<label class="gh-tree-check" onclick="event.stopPropagation()" title="Select all files in this folder">'
                    + '<input type="checkbox" class="gh-sel-cb gh-sel-folder" id="' + cbId + '" onchange="GH.selectFolderItems(\'' + safePath + '\',this.checked)">'
                    + '</label>'
                    + '<span class="gh-folder-toggle" onclick="GH.toggleTreeFolder(\'' + toggleId + '\')">'
                    + '<svg class="gh-caret" id="caret-' + toggleId + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="9" height="9"><polyline points="9 18 15 12 9 6"/></svg>'
                    + '<svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" width="13" height="13"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>'
                    + '<span class="gh-tree-name">' + htmlEsc(name) + '</span>'
                    + '</span>'
                    + '<div class="gh-tree-actions">'
                    + '<button class="gh-btn gh-btn-sm gh-btn-green" onclick="event.stopPropagation();GH.downloadTreeFolder(' + JSON.stringify(r.full_name) + ',' + JSON.stringify(r.default_branch) + ',' + JSON.stringify(fullPath) + ')" title="Download this folder as ZIP">' + IC.dl + ' Folder</button>'
                    + '</div></div>'
                    + '<div id="' + toggleId + '" class="gh-tree-children" style="display:none">' + renderTreeHtml(children, r, depth + 1, fullPath) + '</div>';
            } else {
                var filePath = meta ? meta.path : fullPath;
                var fileSize = meta ? (meta.size || 0) : 0;
                var rawUrl   = 'https://raw.githubusercontent.com/' + r.full_name + '/' + r.default_branch + '/' + filePath;
                var safeFilePath = filePath.replace(/\\/g,'\\\\').replace(/'/g,"\\'");
                h += '<div class="gh-tree-item gh-tree-file">'
                    + '<label class="gh-tree-check" onclick="event.stopPropagation()" title="Select this file">'
                    + '<input type="checkbox" class="gh-sel-cb" id="' + cbId + '" onchange="GH.toggleSelectItem(\'' + safeFilePath + '\',this.checked)">'
                    + '</label>'
                    + '<span class="gh-tree-file-label" onclick="GH.previewFile(' + JSON.stringify(r.full_name) + ',' + JSON.stringify(r.default_branch) + ',' + JSON.stringify(filePath) + ')">'
                    + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>'
                    + '<span class="gh-tree-name">' + htmlEsc(name) + '</span>'
                    + (fileSize ? '<span class="gh-tree-size">' + fmtBytes(fileSize) + '</span>' : '')
                    + '</span>'
                    + '<div class="gh-tree-actions">'
                    + '<button class="gh-btn gh-btn-sm" onclick="event.stopPropagation();GH.copyText(\'' + htmlEsc(filePath) + '\',this)" title="Copy path">' + IC.copy + '</button>'
                    + '<button class="gh-btn gh-btn-sm gh-btn-green" onclick="event.stopPropagation();GH.downloadSingleFile(' + JSON.stringify(r.full_name) + ',' + JSON.stringify(r.default_branch) + ',' + JSON.stringify(filePath) + ',this)" title="Download file">' + IC.dl + '</button>'
                    + '<a href="' + htmlEsc(rawUrl) + '" class="gh-btn gh-btn-sm" target="_blank" rel="noopener" onclick="event.stopPropagation()" title="Open in new tab"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></a>'
                    + '</div></div>';
            }
        });
        return h;
    }

    function toggleTreeFolder(id) {
        var pane  = el(id);
        var caret = el('caret-' + id);
        if (!pane) return;
        var open = pane.style.display !== 'none';
        pane.style.display = open ? 'none' : 'block';
        if (caret) {
            caret.style.transform  = open ? '' : 'rotate(90deg)';
            caret.style.transition = 'transform .15s';
        }
    }

    /* ──────────────────────────────────────────────────────────
       FILES — SELECTION & MULTI-DOWNLOAD
    ────────────────────────────────────────────────────────── */
    function toggleSelectItem(path, checked) {
        if (checked) {
            var meta = _pathMeta[path] || {};
            _selectedItems[path] = { path: path, name: path.split('/').pop(), size: meta.size || 0 };
        } else {
            delete _selectedItems[path];
        }
        updateSelectionBar();
    }

    function selectFolderItems(folderPath, checked) {
        if (!_treeData) return;
        var prefix = folderPath + '/';
        _treeData.tree.forEach(function(item) {
            if (item.type === 'blob' && item.path.indexOf(prefix) === 0) {
                var cbId = 'cb-' + item.path.replace(/[^a-z0-9]/gi, '_');
                var cb = el(cbId);
                if (cb) cb.checked = checked;
                if (checked) {
                    _selectedItems[item.path] = { path: item.path, name: item.path.split('/').pop(), size: item.size || 0 };
                } else {
                    delete _selectedItems[item.path];
                }
            }
        });
        updateSelectionBar();
    }

    function updateSelectionBar() {
        var bar = el('gh-sel-bar');
        if (!bar) return;
        var items = Object.values(_selectedItems);
        var count = items.length;
        var totalSize = items.reduce(function(s, i) { return s + (i.size || 0); }, 0);
        if (count === 0) {
            bar.classList.remove('visible');
        } else {
            bar.classList.add('visible');
            var countEl = el('gh-sel-count');
            var sizeEl  = el('gh-sel-size');
            var pathEl  = el('gh-sel-paths');
            if (countEl) countEl.textContent = count + ' file' + (count !== 1 ? 's' : '') + ' selected';
            if (sizeEl)  sizeEl.textContent  = fmtBytes(totalSize);
            if (pathEl) {
                var shown = items.slice(0, 4).map(function(i){ return '<code class="gh-sel-path">' + htmlEsc(i.path) + '</code>'; });
                if (items.length > 4) shown.push('<span style="color:var(--color-text-muted)">+' + (items.length - 4) + ' more</span>');
                pathEl.innerHTML = shown.join('');
            }
        }
    }

    function clearSelection() {
        _selectedItems = {};
        var cbs = document.querySelectorAll('.gh-sel-cb');
        if (cbs) cbs.forEach(function(cb) { cb.checked = false; });
        updateSelectionBar();
    }

    async function loadJSZip() {
        if (window.JSZip) return window.JSZip;
        return new Promise(function(resolve, reject) {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js';
            s.onload  = function() { resolve(window.JSZip); };
            s.onerror = function() { reject(new Error('Failed to load JSZip. Please check your connection.')); };
            document.head.appendChild(s);
        });
    }

    async function downloadSelectedZip() {
        var items = Object.values(_selectedItems);
        if (!items.length) { alert('No files selected.\n\nCheck the boxes next to files or folders in the tree to select items for download.'); return; }
        var btn = el('gh-dl-sel-btn');
        if (btn) { btn.textContent = '⏳ Loading…'; btn.disabled = true; }
        try {
            var JSZip = await loadJSZip();
            var zip   = new JSZip();
            var r     = _repo;
            var branch = r.default_branch;
            var errors = [];

            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                if (btn) btn.textContent = '⏳ ' + (i + 1) + ' / ' + items.length;
                try {
                    /* Use GitHub blob API — avoids CORS preflight rejection on shared hosting */
                    var bytes = await fetchFileViaApi(r.full_name, item.path);
                    zip.file(item.path, bytes, { binary: true });
                } catch(e2) {
                    errors.push(item.path);
                }
            }

            // Build DOWNLOAD_INFO.txt
            var totalSize = items.reduce(function(s, i) { return s + (i.size || 0); }, 0);
            var infoLines = [
                '=====================================',
                '  AWAN TOOLS — DOWNLOAD INFORMATION',
                '=====================================',
                '',
                'Platform : Awan Tools',
                'Site     : https://awantools.site',
                'Tool     : GitHub Toolkit',
                'Generated: ' + new Date().toUTCString(),
                '',
                'REPOSITORY',
                '----------',
                'Name        : ' + r.full_name,
                'Description : ' + (r.description || 'N/A'),
                'URL         : https://github.com/' + r.full_name,
                'Stars       : ' + (r.stargazers_count || 0).toLocaleString(),
                'Forks       : ' + (r.forks_count || 0).toLocaleString(),
                'Watchers    : ' + (r.watchers_count || 0).toLocaleString(),
                'Language    : ' + (r.language || 'N/A'),
                'License     : ' + (r.license ? r.license.name : 'N/A'),
                'Default Branch : ' + branch,
                'Last Push   : ' + new Date(r.pushed_at).toUTCString(),
                'Created     : ' + new Date(r.created_at).toUTCString(),
                '',
                'DOWNLOAD SUMMARY',
                '----------------',
                'Files Selected : ' + items.length,
                'Total Size     : ' + fmtBytes(totalSize),
                'Files Failed   : ' + errors.length,
                '',
                'FILES INCLUDED',
                '--------------',
            ].concat(items.map(function(item) {
                return '  ' + item.path + (item.size ? '  (' + fmtBytes(item.size) + ')' : '');
            }));

            if (errors.length) {
                infoLines = infoLines.concat([
                    '',
                    'FAILED TO FETCH (' + errors.length + ')',
                    '-------------------'
                ].concat(errors.map(function(p) { return '  ' + p; })));
            }
            infoLines.push('', '=====================================');
            zip.file('DOWNLOAD_INFO.txt', infoLines.join('\n'));

            if (btn) btn.textContent = '⏳ Compressing…';
            var zipBlob = await zip.generateAsync({ type: 'blob', compression: 'DEFLATE', compressionOptions: { level: 6 } });
            var fname = r.full_name.replace('/', '_') + '_files_' + Date.now() + '.zip';
            var a = document.createElement('a');
            a.href = URL.createObjectURL(zipBlob);
            a.download = fname;
            a.click();
            URL.revokeObjectURL(a.href);
        } catch(e) {
            alert('Download error: ' + e.message);
        } finally {
            if (btn) { btn.innerHTML = IC.dl + ' Download ZIP'; btn.disabled = false; }
        }
    }

    async function previewFile(fullName, branch, path) {
        var preview = el('gh-file-preview');
        if (!preview) return;
        preview.innerHTML = skeletonHtml(3);
        try {
            var rawUrl = 'https://raw.githubusercontent.com/' + fullName + '/' + branch + '/' + path;
            var code = await rawFetch(rawUrl);
            var ext = path.split('.').pop().toLowerCase();
            var isImg = ['png','jpg','jpeg','gif','svg','webp','ico'].indexOf(ext) >= 0;
            if (isImg) {
                preview.innerHTML = '<div class="gh-code-wrap"><div class="gh-code-header"><span class="gh-code-lang">' + htmlEsc(path) + '</span></div>'
                    + '<div style="padding:12px;text-align:center"><img src="' + htmlEsc(rawUrl) + '" style="max-width:100%;border-radius:4px"></div></div>';
                return;
            }
            var highlighted = highlight(code, ext);
            preview.innerHTML = '<div class="gh-code-wrap"><div class="gh-code-header">'
                + '<span class="gh-code-lang">' + htmlEsc(path) + '</span>'
                + '<div style="display:flex;gap:5px">'
                + '<button class="gh-btn gh-btn-sm" onclick="GH.copyText(' + JSON.stringify(rawUrl) + ',this)">' + IC.copy + ' Raw URL</button>'
                + '<button class="gh-btn gh-btn-sm" onclick="GH.copyText(' + JSON.stringify(code) + ',this)">' + IC.copy + ' Code</button>'
                + '<button class="gh-btn gh-btn-sm gh-btn-green" onclick="GH.dlText(' + JSON.stringify(code) + ',' + JSON.stringify(path.split('/').pop()) + ')">' + IC.dl + '</button>'
                + '</div></div>'
                + '<div class="gh-code-body"><pre><code>' + highlighted + '</code></pre></div></div>';
        } catch(e) {
            preview.innerHTML = errHtml(e.message);
        }
    }

    /* ──────────────────────────────────────────────────────────
       TAB: README
    ────────────────────────────────────────────────────────── */
    async function tabReadme() {
        var r = _repo;
        var panel = el('tab-readme-content');
        var readme = await api('/repos/' + r.full_name + '/readme');
        var content = atob(readme.content.replace(/\n/g, ''));
        var rendered = renderMd(content);
        panel.innerHTML = '<div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:10px">'
            + '<button class="gh-btn" onclick="GH.copyText(' + JSON.stringify(content) + ',this)">' + IC.copy + ' Copy Markdown</button>'
            + '<button class="gh-btn gh-btn-green" onclick="GH.dlText(' + JSON.stringify(content) + ',\'README.md\')">' + IC.dl + ' Download</button>'
            + '</div>'
            + '<div class="gh-md-body">' + rendered + '</div>';
    }

    /* ──────────────────────────────────────────────────────────
       TAB: CONTRIBUTORS
    ────────────────────────────────────────────────────────── */
    async function tabContributors() {
        var r = _repo;
        var contribs = await api('/repos/' + r.full_name + '/contributors', { per_page: 50 });
        var total = contribs.reduce(function(s, c){ return s + c.contributions; }, 0);
        var h = '<div class="gh-section-title">Top ' + contribs.length + ' Contributors · ' + fmtNum(total) + ' total commits</div>';
        h += '<div class="gh-contrib-list">';
        contribs.forEach(function(c, i) {
            var pct = total > 0 ? (c.contributions / total * 100) : 0;
            h += '<div class="gh-contrib-row">'
                + '<div class="gh-contrib-rank">' + (i+1) + '</div>'
                + '<img class="gh-contrib-avatar" src="' + htmlEsc(c.avatar_url) + '" alt="' + htmlEsc(c.login) + '">'
                + '<div class="gh-contrib-name"><a href="' + htmlEsc(c.html_url) + '" target="_blank" rel="noopener" style="color:var(--color-text);text-decoration:none">' + htmlEsc(c.login) + '</a></div>'
                + '<div class="gh-contrib-bar-wrap"><div class="gh-contrib-bar" style="width:' + pct.toFixed(1) + '%"></div></div>'
                + '<div class="gh-contrib-count">' + fmtNum(c.contributions) + ' commits</div>'
                + '</div>';
        });
        h += '</div>';
        el('tab-contributors-content').innerHTML = h;
    }

    /* ──────────────────────────────────────────────────────────
       TAB: COMMITS
    ────────────────────────────────────────────────────────── */
    async function tabCommits() {
        var r = _repo;
        var panel = el('tab-commits-content');
        // Branch selector
        var branches = await api('/repos/' + r.full_name + '/branches', { per_page: 100 });
        var opts = branches.map(function(b){ return '<option value="' + htmlEsc(b.name) + '"' + (b.name === r.default_branch ? ' selected' : '') + '>' + htmlEsc(b.name) + '</option>'; }).join('');
        panel.innerHTML = '<div style="display:flex;gap:8px;margin-bottom:12px;align-items:center">'
            + '<label style="font-size:12.5px;color:var(--color-text-muted)">Branch:</label>'
            + '<select class="gh-select" id="commits-branch-sel" onchange="GH.loadCommitsForBranch()">' + opts + '</select>'
            + '</div>'
            + '<div id="commits-list">' + skeletonHtml(5) + '</div>';
        loadCommitsForBranch();
    }

    async function loadCommitsForBranch() {
        var branch = gv('commits-branch-sel') || _repo.default_branch;
        var listEl = el('commits-list');
        if (!listEl) return;
        listEl.innerHTML = skeletonHtml(5);
        try {
            var commits = await api('/repos/' + _repo.full_name + '/commits', { sha: branch, per_page: 30 });
            var h = '<div class="gh-commits">';
            commits.forEach(function(c) {
                var author = c.author;
                var msg = c.commit.message.split('\n')[0];
                h += '<div class="gh-commit">'
                    + (author ? '<img class="gh-commit-avatar" src="' + htmlEsc(author.avatar_url) + '" alt="' + htmlEsc(author.login) + '">' : '<div class="gh-commit-avatar" style="background:var(--color-border);border-radius:50%"></div>')
                    + '<div class="gh-commit-body">'
                    + '<div class="gh-commit-msg">' + htmlEsc(msg) + '</div>'
                    + '<div class="gh-commit-meta">'
                    + '<a href="' + htmlEsc(c.html_url) + '" target="_blank" class="gh-commit-sha" rel="noopener">' + c.sha.slice(0,7) + '</a>'
                    + '<span class="gh-commit-author">' + htmlEsc(c.commit.author.name) + '</span>'
                    + '<span class="gh-commit-date">' + timeSince(c.commit.author.date) + '</span>'
                    + '</div></div></div>';
            });
            h += '</div>';
            listEl.innerHTML = h;
        } catch(e) {
            listEl.innerHTML = errHtml(e.message);
        }
    }

    /* ──────────────────────────────────────────────────────────
       TAB: LANGUAGES
    ────────────────────────────────────────────────────────── */
    async function tabLanguages() {
        var r = _repo;
        if (!_langs) _langs = await api('/repos/' + r.full_name + '/languages');
        var total = Object.values(_langs).reduce(function(a,b){return a+b;}, 0);
        var h = '<div class="gh-section-title">Language Distribution · ' + fmtBytes(total) + ' total</div>';
        h += donutHtml(_langs, r.language);
        h += '<div class="gh-section-title" style="margin-top:16px">Breakdown</div>';
        h += langBarsHtml(_langs);
        el('tab-languages-content').innerHTML = h;
    }

    /* ──────────────────────────────────────────────────────────
       TAB: DEPENDENCIES
    ────────────────────────────────────────────────────────── */
    async function tabDependencies() {
        var r = _repo;
        var branch = r.default_branch;
        var depFiles = [
            { file: 'package.json',     type: 'npm/yarn/pnpm', parser: parsePackageJson },
            { file: 'composer.json',    type: 'PHP Composer',  parser: parseComposerJson },
            { file: 'requirements.txt', type: 'pip',           parser: parseRequirements },
            { file: 'Cargo.toml',       type: 'Rust/Cargo',    parser: parseCargoToml },
            { file: 'go.mod',           type: 'Go Modules',    parser: parseGoMod },
            { file: 'Gemfile',          type: 'Ruby Gems',     parser: parseGemfile },
        ];
        var h = '';
        var found = 0;
        for (var i = 0; i < depFiles.length; i++) {
            var df = depFiles[i];
            try {
                var content = await rawFetch('https://raw.githubusercontent.com/' + r.full_name + '/' + branch + '/' + df.file);
                var parsed = df.parser(content);
                found++;
                h += '<div class="gh-section-title">' + htmlEsc(df.file) + ' <span style="font-weight:400">(' + htmlEsc(df.type) + ')</span></div>';
                if (parsed.deps && parsed.deps.length) {
                    h += '<table class="gh-dep-table"><thead><tr><th>Package</th><th>Version</th><th>Type</th></tr></thead><tbody>';
                    parsed.deps.forEach(function(d) {
                        h += '<tr><td>' + htmlEsc(d.name) + '</td><td>' + htmlEsc(d.version) + '</td><td>' + htmlEsc(d.type||'dependency') + '</td></tr>';
                    });
                    h += '</tbody></table>';
                } else {
                    h += '<p style="font-size:12.5px;color:var(--color-text-muted)">No dependencies found.</p>';
                }
            } catch(e2) { /* file not found */ }
        }
        if (!found) h = '<div class="gh-empty-tab"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="40" height="40"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg><p>No dependency files found (package.json, composer.json, requirements.txt, Cargo.toml, go.mod, Gemfile).</p></div>';
        el('tab-dependencies-content').innerHTML = h;
    }

    function parsePackageJson(text) {
        try {
            var pkg = JSON.parse(text);
            var deps = [];
            function addDeps(obj, type) {
                if (!obj) return;
                Object.entries(obj).forEach(function(e){ deps.push({ name:e[0], version:e[1], type:type }); });
            }
            addDeps(pkg.dependencies, 'dependency');
            addDeps(pkg.devDependencies, 'devDependency');
            addDeps(pkg.peerDependencies, 'peerDependency');
            return { deps: deps };
        } catch(e) { return { deps: [] }; }
    }
    function parseComposerJson(text) {
        try {
            var pkg = JSON.parse(text);
            var deps = [];
            function addDeps(obj, type) {
                if (!obj) return;
                Object.entries(obj).forEach(function(e){ deps.push({ name:e[0], version:e[1], type:type }); });
            }
            addDeps(pkg.require, 'require');
            addDeps(pkg['require-dev'], 'require-dev');
            return { deps: deps };
        } catch(e) { return { deps: [] }; }
    }
    function parseRequirements(text) {
        var deps = text.split('\n').map(function(l){ return l.trim(); })
            .filter(function(l){ return l && !l.startsWith('#'); })
            .map(function(l) {
                var parts = l.split(/[>=<!~^]/);
                return { name: parts[0].trim(), version: l.slice(parts[0].length).trim() || '*' };
            });
        return { deps: deps };
    }
    function parseCargoToml(text) {
        var deps = [];
        var inDeps = false;
        text.split('\n').forEach(function(line) {
            if (/^\[dependencies\]/.test(line)) { inDeps = true; return; }
            if (/^\[/.test(line)) inDeps = false;
            if (inDeps) {
                var m = line.match(/^([a-z0-9_-]+)\s*=\s*"([^"]+)"/i);
                if (m) deps.push({ name: m[1], version: m[2] });
            }
        });
        return { deps: deps };
    }
    function parseGoMod(text) {
        var deps = [];
        text.split('\n').forEach(function(line) {
            var m = line.trim().match(/^([^\s]+)\s+v([^\s]+)/);
            if (m && m[1] !== 'module' && m[1] !== 'go') deps.push({ name: m[1], version: 'v' + m[2] });
        });
        return { deps: deps };
    }
    function parseGemfile(text) {
        var deps = [];
        text.split('\n').forEach(function(line) {
            var m = line.trim().match(/^gem ['"]([^'"]+)['"]/);
            if (m) deps.push({ name: m[1], version: '*' });
        });
        return { deps: deps };
    }

    /* ──────────────────────────────────────────────────────────
       TAB: ANALYTICS
    ────────────────────────────────────────────────────────── */
    async function tabAnalytics() {
        var r = _repo;
        if (!_langs) _langs = await api('/repos/' + r.full_name + '/languages');
        var scores = calcScores(r);
        var h = '';
        h += '<div class="gh-section-title">Repository Scores</div>';
        h += scoresHtml(scores);
        h += '<div class="gh-section-title" style="margin-top:16px">Stats Breakdown</div>';
        h += '<div class="gh-stats-grid">'
            + '<div class="gh-stat-card gh-stat-card-star"><div class="gh-stat-card-val">' + fmtNum(r.stargazers_count) + '</div><div class="gh-stat-card-label">Stars</div></div>'
            + '<div class="gh-stat-card"><div class="gh-stat-card-val">' + fmtNum(r.forks_count) + '</div><div class="gh-stat-card-label">Forks</div></div>'
            + '<div class="gh-stat-card"><div class="gh-stat-card-val">' + fmtNum(r.watchers_count) + '</div><div class="gh-stat-card-label">Watchers</div></div>'
            + '<div class="gh-stat-card"><div class="gh-stat-card-val">' + r.open_issues_count + '</div><div class="gh-stat-card-label">Open Issues</div></div>'
            + '<div class="gh-stat-card"><div class="gh-stat-card-val">' + fmtKB(r.size) + '</div><div class="gh-stat-card-label">Repo Size</div></div>'
            + '<div class="gh-stat-card"><div class="gh-stat-card-val">' + timeSince(r.pushed_at) + '</div><div class="gh-stat-card-label">Last Push</div></div>'
            + '</div>';
        if (_langs && Object.keys(_langs).length > 0) {
            h += '<div class="gh-section-title" style="margin-top:16px">Language Split</div>';
            h += donutHtml(_langs, r.language);
        }
        el('tab-analytics-content').innerHTML = h;
    }

    /* ──────────────────────────────────────────────────────────
       TAB: BADGES
    ────────────────────────────────────────────────────────── */
    async function tabBadges() {
        var r = _repo;
        var enc = encodeURIComponent(r.full_name);
        var badges = [
            { label:'Stars',         img:'https://img.shields.io/github/stars/' + enc + '?style=flat',         md:'![Stars](https://img.shields.io/github/stars/' + r.full_name + ')' },
            { label:'Forks',         img:'https://img.shields.io/github/forks/' + enc + '?style=flat',         md:'![Forks](https://img.shields.io/github/forks/' + r.full_name + ')' },
            { label:'Watchers',      img:'https://img.shields.io/github/watchers/' + enc + '?style=flat',      md:'![Watchers](https://img.shields.io/github/watchers/' + r.full_name + ')' },
            { label:'Issues',        img:'https://img.shields.io/github/issues/' + enc + '?style=flat',        md:'![Issues](https://img.shields.io/github/issues/' + r.full_name + ')' },
            { label:'License',       img:'https://img.shields.io/github/license/' + enc + '?style=flat',       md:'![License](https://img.shields.io/github/license/' + r.full_name + ')' },
            { label:'Last Commit',   img:'https://img.shields.io/github/last-commit/' + enc + '?style=flat',   md:'![Last Commit](https://img.shields.io/github/last-commit/' + r.full_name + ')' },
            { label:'Repo Size',     img:'https://img.shields.io/github/repo-size/' + enc + '?style=flat',     md:'![Repo Size](https://img.shields.io/github/repo-size/' + r.full_name + ')' },
            { label:'Language',      img:'https://img.shields.io/github/languages/top/' + enc + '?style=flat', md:'![Language](https://img.shields.io/github/languages/top/' + r.full_name + ')' },
            { label:'Language Count',img:'https://img.shields.io/github/languages/count/' + enc + '?style=flat',md:'![Languages](https://img.shields.io/github/languages/count/' + r.full_name + ')' },
            { label:'Release',       img:'https://img.shields.io/github/v/release/' + enc + '?style=flat',     md:'![Release](https://img.shields.io/github/v/release/' + r.full_name + ')' },
            { label:'Social Stars',  img:'https://img.shields.io/github/stars/' + enc + '?style=social',       md:'![Stars](https://img.shields.io/github/stars/' + r.full_name + '?style=social)' },
        ];
        var h = '<div class="gh-badge-grid">' + badges.map(function(b) {
            return '<div class="gh-badge-item">'
                + '<div class="gh-badge-label">' + htmlEsc(b.label) + '</div>'
                + '<div class="gh-badge-preview"><img src="' + htmlEsc(b.img) + '" alt="' + htmlEsc(b.label) + '" onerror="this.style.display=\'none\'"></div>'
                + '<div class="gh-badge-code">' + htmlEsc(b.md) + '</div>'
                + '<button class="gh-btn gh-btn-sm" onclick="GH.copyText(' + JSON.stringify(b.md) + ',this)">' + IC.copy + '</button>'
                + '</div>';
        }).join('') + '</div>';
        el('tab-badges-content').innerHTML = h;
    }

    /* ──────────────────────────────────────────────────────────
       TAB: WIDGETS
    ────────────────────────────────────────────────────────── */
    async function tabWidgets() {
        var r = _repo;
        var ogImg = 'https://opengraph.githubassets.com/1/' + r.full_name;
        var cardDark  = genEmbedCard(r, 'dark');
        var cardLight = genEmbedCard(r, 'light');

        var h = '<div class="gh-section-title">OpenGraph / Social Card</div>';
        h += '<div class="gh-card"><img src="' + htmlEsc(ogImg) + '" style="max-width:100%;border-radius:4px;display:block;margin-bottom:10px" alt="OG Card">';
        h += '<button class="gh-btn" onclick="GH.copyText(' + JSON.stringify(ogImg) + ',this)">' + IC.copy + ' Copy Image URL</button>';
        h += '<a href="' + htmlEsc(ogImg) + '" class="gh-btn gh-btn-green" download target="_blank" style="margin-left:6px">' + IC.dl + ' Download</a></div>';

        h += '<div class="gh-section-title" style="margin-top:16px">Embeddable Repository Card</div>';
        ['dark','light'].forEach(function(theme) {
            var card = theme === 'dark' ? cardDark : cardLight;
            h += '<div class="gh-card"><div class="gh-card-title">📋 ' + (theme === 'dark' ? '🌙 Dark' : '☀️ Light') + ' Theme</div>';
            h += '<div style="margin:10px 0;border:1px solid var(--color-border);border-radius:6px;overflow:hidden">' + card + '</div>';
            h += '<textarea class="gh-seo-code" style="min-height:80px;font-size:11px" readonly>' + htmlEsc(card) + '</textarea>';
            h += '<button class="gh-btn gh-btn-sm" style="margin-top:6px" onclick="GH.copyText(' + JSON.stringify(card) + ',this)">' + IC.copy + ' Copy HTML</button></div>';
        });
        el('tab-widgets-content').innerHTML = h;
    }

    function genEmbedCard(r, theme) {
        var bg = theme === 'dark' ? '#0d1117' : '#ffffff';
        var tc = theme === 'dark' ? '#c9d1d9' : '#24292f';
        var bc = theme === 'dark' ? '#30363d' : '#d0d7de';
        return '<div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;background:' + bg + ';border:1px solid ' + bc + ';border-radius:8px;padding:14px 16px;max-width:400px">'
            + '<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">'
            + '<img src="' + r.owner.avatar_url + '" style="width:32px;height:32px;border-radius:50%">'
            + '<a href="' + r.html_url + '" style="font-size:14px;font-weight:700;color:' + (theme==='dark'?'#58a6ff':'#0969da') + ';text-decoration:none" target="_blank">' + r.full_name + '</a>'
            + '</div>'
            + (r.description ? '<p style="font-size:12.5px;color:' + tc + ';margin:0 0 10px;line-height:1.5">' + r.description + '</p>' : '')
            + '<div style="display:flex;gap:12px;font-size:12px;color:' + tc + '">'
            + '⭐ ' + fmtNum(r.stargazers_count) + ' &nbsp; 🍴 ' + fmtNum(r.forks_count) + (r.language ? ' &nbsp; 🔵 ' + r.language : '')
            + '</div></div>';
    }

    /* ──────────────────────────────────────────────────────────
       TAB: API
    ────────────────────────────────────────────────────────── */
    async function tabApi() {
        var r = _repo;
        var endpoints = [
            { path: '/repos/' + r.full_name, desc: 'Repository info' },
            { path: '/repos/' + r.full_name + '/branches', desc: 'List branches' },
            { path: '/repos/' + r.full_name + '/tags', desc: 'List tags' },
            { path: '/repos/' + r.full_name + '/releases', desc: 'List releases' },
            { path: '/repos/' + r.full_name + '/commits', desc: 'List commits' },
            { path: '/repos/' + r.full_name + '/contributors', desc: 'Contributors' },
            { path: '/repos/' + r.full_name + '/languages', desc: 'Language bytes' },
            { path: '/repos/' + r.full_name + '/topics', desc: 'Topics' },
            { path: '/repos/' + r.full_name + '/stargazers', desc: 'Stargazers' },
            { path: '/repos/' + r.full_name + '/forks', desc: 'Forks' },
            { path: '/repos/' + r.full_name + '/issues', desc: 'Open issues' },
            { path: '/repos/' + r.full_name + '/pulls', desc: 'Pull requests' },
            { path: '/repos/' + r.full_name + '/git/trees/' + r.default_branch + '?recursive=1', desc: 'File tree (recursive)' },
            { path: '/repos/' + r.full_name + '/readme', desc: 'README file' },
            { path: '/repos/' + r.full_name + '/license', desc: 'License info' },
        ];
        var h = '<div style="margin-bottom:10px"><div class="gh-input-row"><input class="gh-input-sm" id="api-custom-path" placeholder="/repos/' + r.full_name + '/..." value="/repos/' + r.full_name + '"><button class="gh-btn gh-btn-primary" onclick="GH.callCustomApi()">' + IC.link + ' Call</button></div><div id="api-custom-out"></div></div>';
        h += '<div class="gh-section-title">Common Endpoints</div>';
        h += '<div class="gh-api-list">' + endpoints.map(function(ep, i) {
            return '<div class="gh-api-item"><div class="gh-api-item-header" onclick="GH.callApiEndpoint(\'' + htmlEsc(ep.path) + '\',\'api-ep-' + i + '\')">'
                + '<span class="gh-api-method">GET</span>'
                + '<span class="gh-api-path">' + htmlEsc(ep.path) + '</span>'
                + '<span class="gh-api-desc">' + htmlEsc(ep.desc) + '</span>'
                + '<button class="gh-btn gh-btn-sm" onclick="event.stopPropagation();GH.copyText(\'' + API_BASE + htmlEsc(ep.path) + '\',this)">' + IC.copy + '</button>'
                + '</div><div class="gh-api-result" id="api-ep-' + i + '" style="display:none"></div></div>';
        }).join('') + '</div>';
        el('tab-api-content').innerHTML = h;
    }

    async function callApiEndpoint(path, outId) {
        var out = el(outId);
        if (!out) return;
        if (out.style.display === 'block') { out.style.display = 'none'; return; }
        out.style.display = 'block';
        out.innerHTML = skeletonHtml(3);
        try {
            var data = await api(path);
            var json = JSON.stringify(data, null, 2);
            out.innerHTML = '<div class="gh-code-wrap"><div class="gh-code-header"><span class="gh-code-lang">JSON</span><button class="gh-btn gh-btn-sm" onclick="GH.copyText(' + JSON.stringify(json) + ',this)">' + IC.copy + '</button></div><div class="gh-code-body" style="max-height:300px"><pre><code>' + highlight(json, 'json') + '</code></pre></div></div>';
        } catch(e) {
            out.innerHTML = errHtml(e.message);
        }
    }

    async function callCustomApi() {
        var path = gv('api-custom-path');
        var out = el('api-custom-out');
        if (!out || !path) return;
        out.innerHTML = skeletonHtml(3);
        try {
            var data = await api(path);
            var json = JSON.stringify(data, null, 2);
            out.innerHTML = '<div class="gh-code-wrap"><div class="gh-code-header"><span class="gh-code-lang">JSON</span><button class="gh-btn gh-btn-sm" onclick="GH.copyText(' + JSON.stringify(json) + ',this)">' + IC.copy + '</button></div><div class="gh-code-body" style="max-height:380px"><pre><code>' + highlight(json, 'json') + '</code></pre></div></div>';
        } catch(e) {
            out.innerHTML = errHtml(e.message);
        }
    }

    /* ──────────────────────────────────────────────────────────
       TAB: SEO
    ────────────────────────────────────────────────────────── */
    async function tabSeo() {
        var r = _repo;
        var title = r.name + ' by ' + r.owner.login + ' — GitHub Repository';
        var desc  = (r.description || r.name) + ' · ⭐ ' + fmtNum(r.stargazers_count) + ' stars · 🍴 ' + fmtNum(r.forks_count) + ' forks. Available on GitHub.';
        var url   = r.html_url;
        var ogImg = 'https://opengraph.githubassets.com/1/' + r.full_name;

        var metaTags = '<title>' + htmlEsc(title) + '</title>\n'
            + '<meta name="description" content="' + htmlEsc(desc) + '">\n'
            + '<link rel="canonical" href="' + htmlEsc(url) + '">';

        var ogTags = '<meta property="og:type" content="website">\n'
            + '<meta property="og:url" content="' + htmlEsc(url) + '">\n'
            + '<meta property="og:title" content="' + htmlEsc(title) + '">\n'
            + '<meta property="og:description" content="' + htmlEsc(desc) + '">\n'
            + '<meta property="og:image" content="' + htmlEsc(ogImg) + '">';

        var twitterTags = '<meta name="twitter:card" content="summary_large_image">\n'
            + '<meta name="twitter:title" content="' + htmlEsc(title) + '">\n'
            + '<meta name="twitter:description" content="' + htmlEsc(desc) + '">\n'
            + '<meta name="twitter:image" content="' + htmlEsc(ogImg) + '">';

        var jsonLd = JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'SoftwareSourceCode',
            'name': r.name,
            'description': r.description,
            'url': url,
            'codeRepository': url,
            'programmingLanguage': r.language,
            'author': { '@type': 'Person', 'name': r.owner.login, 'url': r.owner.html_url },
            'dateCreated': r.created_at,
            'dateModified': r.updated_at,
            'license': r.license ? r.license.url : undefined,
        }, null, 2);

        var rssFeed = url + '/releases.atom';

        var h = '';
        function block(label, code) {
            return '<div class="gh-seo-block"><label>' + label + '</label><textarea class="gh-seo-code" readonly>' + htmlEsc(code) + '</textarea><button class="gh-btn gh-btn-sm" style="margin-top:4px" onclick="GH.copyText(' + JSON.stringify(code) + ',this)">' + IC.copy + ' Copy</button></div>';
        }
        h += block('Meta Tags', metaTags);
        h += block('OpenGraph Tags', ogTags);
        h += block('Twitter Card Tags', twitterTags);
        h += block('JSON-LD Schema', '<script type="application/ld+json">\n' + jsonLd + '\n<\/script>');
        h += block('RSS Feed URL (Releases)', rssFeed);
        h += block('RSS Feed URL (Commits)', url + '/commits/' + r.default_branch + '.atom');

        el('tab-seo-content').innerHTML = h;
    }

    /* ──────────────────────────────────────────────────────────
       TAB: COMPARE
    ────────────────────────────────────────────────────────── */
    async function tabCompare() {
        var r = _repo;
        var h = '<div class="gh-card">'
            + '<p style="font-size:13px;color:var(--color-text-muted);margin-bottom:12px">Compare this repository against another. The current repo is pre-filled.</p>'
            + '<div class="gh-input-row">'
            + '<input class="gh-input-sm" id="compare-url-a" value="' + htmlEsc(r.html_url) + '" placeholder="Repo A">'
            + '<span style="flex-shrink:0;padding:0 4px;font-weight:700;color:var(--color-text-muted)">vs</span>'
            + '<input class="gh-input-sm" id="compare-url-b" placeholder="https://github.com/org/repo">'
            + '<button class="gh-btn gh-btn-primary" onclick="GH.runCompare()">Compare</button>'
            + '</div></div>'
            + '<div id="compare-out"></div>';
        el('tab-compare-content').innerHTML = h;
    }

    async function runCompare() {
        var rawA = gv('compare-url-a'), rawB = gv('compare-url-b');
        var pA = parseGHUrl(rawA), pB = parseGHUrl(rawB);
        var out = el('compare-out');
        if (!out) return;
        if (!pA || !pA.owner || !pB || !pB.owner) { out.innerHTML = errHtml('Enter two valid GitHub repository URLs.'); return; }
        out.innerHTML = skeletonHtml(5);
        try {
            var [rA, rB] = await Promise.all([
                api('/repos/' + pA.owner + '/' + pA.repo),
                api('/repos/' + pB.owner + '/' + pB.repo),
            ]);
            var scA = calcScores(rA), scB = calcScores(rB);
            var metrics = [
                { label:'⭐ Stars',        a: rA.stargazers_count, b: rB.stargazers_count, fmt: fmtNum, higher:'a' },
                { label:'🍴 Forks',        a: rA.forks_count,      b: rB.forks_count,      fmt: fmtNum, higher:'a' },
                { label:'👁 Watchers',     a: rA.watchers_count,   b: rB.watchers_count,   fmt: fmtNum, higher:'a' },
                { label:'🐛 Open Issues',  a: rA.open_issues_count,b: rB.open_issues_count,fmt: String, higher:'b' },
                { label:'📦 Size',         a: rA.size*1024,        b: rB.size*1024,        fmt: fmtBytes, higher:'b' },
                { label:'🗓 Created',      a: rA.created_at,       b: rB.created_at,       fmt: fmtDate, higher:'none' },
                { label:'🔄 Last Push',    a: rA.pushed_at,        b: rB.pushed_at,        fmt: fmtDate, higher:'none' },
                { label:'🌐 Language',     a: rA.language,         b: rB.language,         fmt: String,  higher:'none' },
                { label:'📜 License',      a: (rA.license||{}).name||'–', b:(rB.license||{}).name||'–', fmt:String, higher:'none' },
                { label:'⭐ Pop. Score',   a: scA.popularity,      b: scB.popularity,      fmt: String, higher:'a' },
                { label:'🔧 Maint. Score', a: scA.maintenance,     b: scB.maintenance,     fmt: String, higher:'a' },
                { label:'⚡ Act. Score',   a: scA.activity,        b: scB.activity,        fmt: String, higher:'a' },
            ];
            var h = '<div class="gh-compare"><table>'
                + '<thead><tr><th>Metric</th><th>' + htmlEsc(rA.full_name) + '</th><th>' + htmlEsc(rB.full_name) + '</th></tr></thead>'
                + '<tbody>';
            metrics.forEach(function(m) {
                var aWins = m.higher === 'a' ? m.a > m.b : (m.higher === 'b' ? m.a < m.b : false);
                var bWins = m.higher === 'a' ? m.b > m.a : (m.higher === 'b' ? m.b < m.a : false);
                h += '<tr>'
                    + '<td class="metric">' + m.label + '</td>'
                    + '<td class="' + (aWins ? 'winner' : (bWins ? 'loser' : '')) + '">' + htmlEsc(m.fmt(m.a)) + (aWins ? ' <span class="gh-winner-badge">✓ Better</span>' : '') + '</td>'
                    + '<td class="' + (bWins ? 'winner' : (aWins ? 'loser' : '')) + '">' + htmlEsc(m.fmt(m.b)) + (bWins ? ' <span class="gh-winner-badge">✓ Better</span>' : '') + '</td>'
                    + '</tr>';
            });
            h += '</tbody></table></div>';
            out.innerHTML = h;
        } catch(e) {
            out.innerHTML = errHtml(e.message);
        }
    }

    /* ──────────────────────────────────────────────────────────
       TAB: TOOLS
    ────────────────────────────────────────────────────────── */
    async function tabTools() {
        var r = _repo;
        var h = '<div class="gh-mini-tools">';

        // 1. License Detector
        h += '<div class="gh-mini-tool"><div class="gh-mini-tool-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> License Detector</div>';
        if (r.license) {
            var lic = LICENSE_INFO[r.license.spdx_id] || { name: r.license.name, desc: 'See license file for details.' };
            h += '<div class="gh-license-box"><div class="gh-license-name">' + htmlEsc(lic.name) + '</div><div class="gh-license-spdx">' + htmlEsc(r.license.spdx_id) + '</div><div class="gh-license-desc">' + htmlEsc(lic.desc) + '</div></div>';
        } else {
            h += '<div style="font-size:12.5px;color:var(--color-text-muted)">No license detected in this repository.</div>';
        }
        h += '</div>';

        // 2. Size Calculator
        h += '<div class="gh-mini-tool"><div class="gh-mini-tool-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg> Size Calculator</div>'
            + '<table class="gh-info-table"><tr><td>Total Size</td><td><strong>' + fmtKB(r.size) + '</strong></td></tr>'
            + '<tr><td>Bytes</td><td>' + (r.size * 1024).toLocaleString() + ' bytes</td></tr>'
            + '<tr><td>Est. Clone</td><td>~' + fmtKB(r.size * 1.3) + ' (with history)</td></tr>'
            + '</table></div>';

        // 3. Raw URL Generator
        h += '<div class="gh-mini-tool"><div class="gh-mini-tool-title">' + IC.link + ' Raw URL Generator</div>'
            + '<div class="gh-input-row"><input class="gh-input-sm" id="raw-url-in" placeholder="GitHub blob URL"></div>'
            + '<button class="gh-btn gh-btn-sm gh-btn-primary" onclick="GH.runRawUrlGen()">Convert</button>'
            + '<div id="raw-url-out" class="gh-mini-out"></div></div>';

        // 4. Clone URL Generator
        h += '<div class="gh-mini-tool"><div class="gh-mini-tool-title">' + IC.copy + ' Clone URL Generator</div>'
            + '<div class="gh-clone-rows" style="margin-top:0">'
            + '<div class="gh-clone-row"><span class="gh-clone-type">HTTPS</span><span class="gh-clone-url" style="font-size:11px">' + htmlEsc('https://github.com/' + r.full_name + '.git') + '</span><button class="gh-btn gh-btn-sm" onclick="GH.copyText(\'https://github.com/' + r.full_name + '.git\',this)">' + IC.copy + '</button></div>'
            + '<div class="gh-clone-row"><span class="gh-clone-type">SSH</span><span class="gh-clone-url" style="font-size:11px">' + htmlEsc('git@github.com:' + r.full_name + '.git') + '</span><button class="gh-btn gh-btn-sm" onclick="GH.copyText(\'git@github.com:' + r.full_name + '.git\',this)">' + IC.copy + '</button></div>'
            + '<div class="gh-clone-row"><span class="gh-clone-type">CLI</span><span class="gh-clone-url" style="font-size:11px">' + htmlEsc('gh repo clone ' + r.full_name) + '</span><button class="gh-btn gh-btn-sm" onclick="GH.copyText(\'gh repo clone ' + r.full_name + '\',this)">' + IC.copy + '</button></div>'
            + '</div></div>';

        // 5. Tags Explorer
        h += '<div class="gh-mini-tool"><div class="gh-mini-tool-title">' + IC.tag + ' Tags</div>'
            + '<div id="tools-tags-out">' + skeletonHtml(3) + '</div></div>';

        // 6. Markdown Previewer
        h += '<div class="gh-mini-tool span-2"><div class="gh-mini-tool-title">📝 Markdown Previewer</div>'
            + '<div style="display:flex;gap:10px;min-height:200px">'
            + '<textarea class="gh-input-sm" id="md-input" style="flex:1;min-height:200px;resize:vertical;font-family:monospace" placeholder="# Paste markdown here\n\nAnd see it rendered on the right..." oninput="GH.runMdPreview()"></textarea>'
            + '<div id="md-preview-out" class="gh-md-body" style="flex:1;padding:8px;overflow:auto;border:1px solid var(--color-border);border-radius:5px;min-height:200px;font-size:13px"></div>'
            + '</div></div>';

        // 7. Profile Analyzer
        h += '<div class="gh-mini-tool span-2"><div class="gh-mini-tool-title">' + IC.user + ' GitHub Profile Analyzer</div>'
            + '<div class="gh-input-row"><input class="gh-input-sm" id="profile-username" placeholder="GitHub username" value="' + htmlEsc(r.owner.login) + '"><button class="gh-btn gh-btn-primary" onclick="GH.runProfile()">Analyze</button></div>'
            + '<div id="profile-out"></div></div>';

        // 8. User / Repo Search
        h += '<div class="gh-mini-tool span-2"><div class="gh-mini-tool-title">🔍 Repository Search</div>'
            + '<div class="gh-input-row"><input class="gh-input-sm" id="repo-search-q" placeholder="Search repos..."><select class="gh-input-sm" id="repo-search-sort" style="flex:0 0 auto;width:auto"><option value="stars">Most Stars</option><option value="updated">Recently Updated</option><option value="forks">Most Forks</option></select><button class="gh-btn gh-btn-primary" onclick="GH.runRepoSearch()">Search</button></div>'
            + '<div id="repo-search-out"></div></div>';

        // 9. Trending Repos
        h += '<div class="gh-mini-tool span-2"><div class="gh-mini-tool-title">📈 Trending Repositories</div>'
            + '<div class="gh-tabs" style="margin-bottom:8px">'
            + '<button class="gh-tab active" id="trend-btn-daily" onclick="GH.setTrendingPeriod(\'daily\')">Daily</button>'
            + '<button class="gh-tab" id="trend-btn-weekly" onclick="GH.setTrendingPeriod(\'weekly\')">Weekly</button>'
            + '<button class="gh-tab" id="trend-btn-monthly" onclick="GH.setTrendingPeriod(\'monthly\')">Monthly</button>'
            + '</div>'
            + '<div id="trending-out">' + skeletonHtml(4) + '</div></div>';

        // 10. Gist Explorer
        h += '<div class="gh-mini-tool span-2"><div class="gh-mini-tool-title">📋 Gist Explorer</div>'
            + '<div class="gh-input-row"><input class="gh-input-sm" id="gist-explore-user" placeholder="GitHub username" value="' + htmlEsc(r.owner.login) + '"><button class="gh-btn gh-btn-primary" onclick="GH.runGistExplore()">Load Gists</button></div>'
            + '<div id="gist-explore-out"></div></div>';

        h += '</div>';
        el('tab-tools-content').innerHTML = h;

        // Auto-load tags and trending
        loadToolsTags();
        loadTrendingRepos();
    }

    // Raw URL generator
    function runRawUrlGen() {
        var raw = gv('raw-url-in');
        var out = el('raw-url-out');
        if (!out) return;
        var p = parseGHUrl(raw);
        if (!p || p.type !== 'blob') {
            out.innerHTML = '<div style="color:#dc2626;font-size:12px">Enter a GitHub blob URL (containing /blob/)</div>';
            return;
        }
        var rawUrl = 'https://raw.githubusercontent.com/' + p.owner + '/' + p.repo + '/' + p.branch + '/' + p.path;
        out.innerHTML = '<div class="gh-clone-row" style="margin-top:6px"><span class="gh-clone-url" style="font-size:11.5px">' + htmlEsc(rawUrl) + '</span>'
            + '<button class="gh-btn gh-btn-sm" onclick="GH.copyText(\'' + rawUrl + '\',this)">' + IC.copy + '</button>'
            + '<a href="' + htmlEsc(rawUrl) + '" class="gh-btn gh-btn-sm gh-btn-green" target="_blank">' + IC.link + '</a></div>';
    }

    // Markdown previewer
    function runMdPreview() {
        var text = el('md-input') ? el('md-input').value : '';
        var out = el('md-preview-out');
        if (out) out.innerHTML = renderMd(text);
    }

    // Trending
    function setTrendingPeriod(period) {
        _trendPeriod = period;
        ['daily','weekly','monthly'].forEach(function(p) {
            var b = el('trend-btn-' + p);
            if (b) b.classList.toggle('active', p === period);
        });
        _tabLoaded.trending = false;
        loadTrendingRepos();
    }

    async function loadTrendingRepos() {
        var out = el('trending-out');
        if (!out) return;
        out.innerHTML = skeletonHtml(4);
        try {
            var days = _trendPeriod === 'daily' ? 1 : _trendPeriod === 'weekly' ? 7 : 30;
            var date = new Date(Date.now() - days * 86400000).toISOString().slice(0, 10);
            var data = await api('/search/repositories', { q: 'created:>=' + date, sort: 'stars', order: 'desc', per_page: 20 });
            var h = '<div>';
            data.items.forEach(function(r, i) {
                h += '<div style="display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid var(--color-border)">'
                    + '<div style="font-size:14px;font-weight:800;color:var(--color-text-muted);width:24px;flex-shrink:0">' + (i+1) + '</div>'
                    + repoCardHtml(r) + '</div>';
            });
            h += '</div>';
            out.innerHTML = h;
        } catch(e) {
            out.innerHTML = errHtml(e.message);
        }
    }

    async function loadToolsTags() {
        var out = el('tools-tags-out');
        if (!out || !_repo) return;
        try {
            var tags = await api('/repos/' + _repo.full_name + '/tags', { per_page: 30 });
            if (!tags.length) { out.innerHTML = '<div style="font-size:12.5px;color:var(--color-text-muted)">No tags yet.</div>'; return; }
            var h = '<div class="gh-branch-list">';
            tags.slice(0, 10).forEach(function(t) {
                var zipUrl = 'https://github.com/' + _repo.full_name + '/archive/refs/' + encodeURIComponent('tags/' + t.name) + '.zip';
                h += '<div class="gh-branch-item"><div class="gh-branch-name">' + IC.tag + htmlEsc(t.name) + '</div>'
                    + '<div class="gh-branch-actions"><a href="' + zipUrl + '" class="gh-btn gh-btn-sm gh-btn-green" target="_blank">' + IC.dl + ' ZIP</a></div></div>';
            });
            if (tags.length > 10) h += '<div style="font-size:12px;color:var(--color-text-muted);padding:6px 0">…and ' + (tags.length - 10) + ' more</div>';
            h += '</div>';
            out.innerHTML = h;
        } catch(e) {
            out.innerHTML = '<div style="font-size:12px;color:var(--color-text-muted)">Failed to load tags.</div>';
        }
    }

    // Profile analyzer
    async function runProfile() {
        var username = gv('profile-username');
        var out = el('profile-out');
        if (!out || !username) return;
        out.innerHTML = skeletonHtml(4);
        try {
            var [user, repos] = await Promise.all([
                api('/users/' + username),
                api('/users/' + username + '/repos', { sort: 'stars', per_page: 100 }),
            ]);
            var totalStars = repos.reduce(function(s, r){ return s + (r.stargazers_count||0); }, 0);
            var langs = {};
            repos.forEach(function(r){ if (r.language) langs[r.language] = (langs[r.language]||0) + 1; });

            // Developer score
            var devScore = Math.min(100, Math.round(
                Math.log(user.followers+1)/Math.log(1000)*40 +
                Math.log(user.public_repos+1)/Math.log(500)*20 +
                Math.log(totalStars+1)/Math.log(10000)*30 +
                10
            ));

            var h = '<div class="gh-profile-card">'
                + '<img class="gh-avatar" src="' + htmlEsc(user.avatar_url) + '" alt="' + htmlEsc(user.login) + '">'
                + '<div class="gh-profile-info">'
                + '<div class="gh-profile-name">' + htmlEsc(user.name || user.login) + '</div>'
                + '<div class="gh-profile-login">@' + htmlEsc(user.login) + '</div>'
                + (user.bio ? '<div class="gh-profile-bio">' + htmlEsc(user.bio) + '</div>' : '')
                + '</div></div>';

            h += '<div class="gh-stats-grid">'
                + '<div class="gh-stat-card"><div class="gh-stat-card-val">' + fmtNum(user.followers) + '</div><div class="gh-stat-card-label">Followers</div></div>'
                + '<div class="gh-stat-card"><div class="gh-stat-card-val">' + fmtNum(user.following) + '</div><div class="gh-stat-card-label">Following</div></div>'
                + '<div class="gh-stat-card"><div class="gh-stat-card-val">' + fmtNum(user.public_repos) + '</div><div class="gh-stat-card-label">Repos</div></div>'
                + '<div class="gh-stat-card gh-stat-card-star"><div class="gh-stat-card-val">' + fmtNum(totalStars) + '</div><div class="gh-stat-card-label">Total Stars</div></div>'
                + '</div>';
            h += '<p style="font-size:13px;margin:10px 0 4px">Developer Score: <strong style="font-size:18px;color:var(--color-primary)">' + devScore + '</strong> / 100</p>';

            if (Object.keys(langs).length > 0) {
                var langTotal = Object.values(langs).reduce(function(a,b){return a+b;},0);
                h += '<div class="gh-section-title" style="margin-top:10px">Top Languages (by repo count)</div>';
                h += donutHtml(Object.fromEntries(Object.entries(langs).slice(0, 8).map(function(e){ return [e[0], e[1] * 1000]; })), 'langs');
            }

            // Top repos
            var topRepos = repos.slice(0, 5);
            if (topRepos.length) {
                h += '<div class="gh-section-title" style="margin-top:10px">Top Repositories</div>';
                topRepos.forEach(function(r){ h += repoCardHtml(r); });
            }

            out.innerHTML = h;
        } catch(e) {
            out.innerHTML = errHtml(e.message);
        }
    }

    // Repo search
    async function runRepoSearch() {
        var q = gv('repo-search-q');
        var sort = gv('repo-search-sort') || 'stars';
        var out = el('repo-search-out');
        if (!out || !q) return;
        out.innerHTML = skeletonHtml(4);
        try {
            var data = await api('/search/repositories', { q: q, sort: sort, order: 'desc', per_page: 15 });
            var h = '<p style="font-size:12.5px;color:var(--color-text-muted);margin-bottom:8px">' + fmtNum(data.total_count) + ' results for <strong>' + htmlEsc(q) + '</strong></p>';
            if (!data.items.length) { out.innerHTML = '<p style="color:var(--color-text-muted)">No results.</p>'; return; }
            data.items.forEach(function(r){ h += repoCardHtml(r); });
            out.innerHTML = h;
        } catch(e) {
            out.innerHTML = errHtml(e.message);
        }
    }

    // Gist explorer
    async function runGistExplore() {
        var username = gv('gist-explore-user');
        var out = el('gist-explore-out');
        if (!out || !username) return;
        out.innerHTML = skeletonHtml(4);
        try {
            var gists = await api('/users/' + username + '/gists', { per_page: 30 });
            if (!gists.length) { out.innerHTML = '<p style="font-size:12.5px;color:var(--color-text-muted)">No public gists found.</p>'; return; }
            var h = '<div class="gh-gist-list">';
            gists.forEach(function(g) {
                var files = Object.keys(g.files);
                h += '<div class="gh-gist-item">'
                    + '<div class="gh-gist-desc">' + htmlEsc(g.description || 'Untitled gist') + '</div>'
                    + '<div class="gh-gist-files">' + files.slice(0, 5).map(function(f){ return '<span class="gh-gist-file-chip">' + htmlEsc(f) + '</span>'; }).join('') + '</div>'
                    + '<div style="display:flex;gap:8px;align-items:center;margin-top:4px">'
                    + '<span style="font-size:11px;color:var(--color-text-muted)">' + timeSince(g.created_at) + '</span>'
                    + '<a href="' + htmlEsc(g.html_url) + '" target="_blank" class="gh-btn gh-btn-sm">' + IC.link + ' View</a>'
                    + '<a href="' + htmlEsc(g.html_url) + '/download" target="_blank" class="gh-btn gh-btn-sm gh-btn-green">' + IC.dl + ' ZIP</a>'
                    + '</div></div>';
            });
            h += '</div>';
            out.innerHTML = h;
        } catch(e) {
            out.innerHTML = errHtml(e.message);
        }
    }

    /* ──────────────────────────────────────────────────────────
       PUBLIC API
    ────────────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', init);

    return {
        // Core
        analyze, switchTab, savePat, clearPat, togglePatVisibility, checkRate,
        setUrl, useExample, addToFavorites,
        removeRecent, removeFav,
        // Copy / Download
        copyText, dlText, dlBlobUrl,
        // File actions
        fetchAndPreviewFile, fetchFolderPreview, downloadFolderZip, downloadTreeFolder,
        downloadSingleFile,
        fetchGist, previewFile, toggleTreeFolder,
        // Selection & multi-download
        toggleSelectItem, selectFolderItems, clearSelection, downloadSelectedZip,
        // Tab internals
        loadCommitsForBranch, callApiEndpoint, callCustomApi,
        runCompare,
        // Tools tab
        runRawUrlGen, runMdPreview, setTrendingPeriod,
        loadTrendingRepos, runProfile, runRepoSearch, runGistExplore,
    };
})();
