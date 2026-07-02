/* =====================================================================
   Encoding Toolkit — AWAN Platform Plugin v1.0.0
   All processing is 100% client-side. Zero server communication.
   ===================================================================== */
var ET = (function () {
    'use strict';

    var currentTool = 'b64enc';

    /* ── DOM helpers ─────────────────────────────────────────────────── */
    function el(id)    { return document.getElementById(id); }
    function gv(id)    { var e = el(id); return e ? e.value : ''; }
    function gvt(id)   { var e = el(id); return e ? e.value.trim() : ''; }
    function sv(id, v) { var e = el(id); if (e) e.value = v; }
    function sh(id, h) { var e = el(id); if (e) e.innerHTML = h; }

    function st(id, msg, type) {
        var e = el(id); if (!e) return;
        if (!msg) { e.innerHTML = ''; return; }
        if (type === 'ok' || type === 'error') {
            e.innerHTML = '<span class="et-status-chip ' + type + '"><span class="et-dot"></span>' + msg + '</span>';
        } else {
            e.innerHTML = '<span style="color:var(--color-text-muted);font-size:12px">' + msg + '</span>';
        }
    }

    function meta(id, text) { var e = el(id); if (e) e.textContent = text; }

    function bytes(s) {
        var b = new Blob([s]).size;
        return b >= 1048576 ? (b/1048576).toFixed(1)+' MB'
             : b >= 1024    ? (b/1024).toFixed(1)+' KB'
             : b+' B';
    }

    function htmlEsc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Navigation ──────────────────────────────────────────────────── */
    function switchTool(id) {
        currentTool = id;
        document.querySelectorAll('.et-panel').forEach(function (p) { p.style.display = 'none'; });
        var panel = el('et-' + id); if (panel) panel.style.display = '';
        document.querySelectorAll('.et-nav').forEach(function (b) {
            b.classList.toggle('active', b.dataset.tool === id);
        });
        var active = document.querySelector('.et-nav.active');
        if (active) active.scrollIntoView({ inline: 'nearest', block: 'nearest' });
    }

    /* ── Paste / Open / Copy / Download ─────────────────────────────── */
    function pasteInto(targetId, btn) {
        if (!navigator.clipboard || !navigator.clipboard.readText) {
            alert('Clipboard read not supported — use Ctrl+V inside the textarea.');
            return;
        }
        navigator.clipboard.readText().then(function (text) {
            sv(targetId, text);
            triggerAuto(targetId);
            if (btn) flash(btn, 'Pasted!');
        }).catch(function () { alert('Could not read clipboard. Paste with Ctrl+V.'); });
    }

    function openFile(targetId, accept) {
        var inp = document.createElement('input');
        inp.type = 'file'; inp.accept = accept || '.txt';
        inp.onchange = function () {
            var file = inp.files[0]; if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) { sv(targetId, e.target.result); triggerAuto(targetId); };
            reader.readAsText(file);
        };
        inp.click();
    }

    function cpPane(id, btn) {
        var e = el(id); if (!e || !e.value) return;
        navigator.clipboard.writeText(e.value).then(function () { if (btn) flash(btn, 'Copied!'); });
    }

    function dl(id, fname, mime) {
        var v = gvt(id); if (!v) return;
        var a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([v], { type: mime }));
        a.download = fname; a.click();
    }

    var COPY_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><polyline points="20 6 9 17 4 12"/></svg>';

    function flash(btn, label) {
        btn.classList.add('copied');
        var orig = btn.innerHTML;
        btn.innerHTML = COPY_SVG + label;
        setTimeout(function () { btn.innerHTML = orig; btn.classList.remove('copied'); }, 1500);
    }

    /* Map textarea IDs → their run functions (filled after definitions) */
    var _autoMap = {};

    function triggerAuto(id) {
        if (_autoMap[id]) _autoMap[id]();
    }

    /* ── clearAll ────────────────────────────────────────────────────── */
    function clearAll() {
        var inIds = {
            'b64enc':'b64e-in','b64dec':'b64d-in',
            'b64imgenc':'', 'b64imgdec':'b64id-in',
            'b64urlenc':'b64ue-in','b64urldec':'b64ud-in',
            'urlenc':'ue-in','urldec':'ud-in',
            'qbuild':'','qparse':'qp-in',
            'urlparse':'up-in','urlextract':'uex-in',
            'urlclean':'ucl-in','urlsplit':'usp-in',
            'ascii':'asc-in','unicode':'uni-in',
            'utf8':'u8-in','utf16':'u16-in',
            'binenc':'bine-in','bindec':'bind-in',
            'octal':'oct-in','decimal':'deci-in',
            'hex':'hex-in','charcode':'cc-in'
        };
        var id = inIds[currentTool]; if (id) sv(id, '');
    }

    /* ── Load sample ─────────────────────────────────────────────────── */
    var SAMPLE_TEXT = 'Hello, World! This is a sample text with special chars: <>&"\'';
    var SAMPLE_URL  = 'https://example.com/path/to/page?name=John%20Doe&lang=en&utm_source=google&utm_medium=cpc#section-2';
    var SAMPLE_BINARY = '01001000 01100101 01101100 01101100 01101111';

    function loadSample() {
        var map = {
            'b64enc':'b64e-in','b64dec':'b64d-in','b64urlenc':'b64ue-in','b64urldec':'b64ud-in',
            'urlenc':'ue-in','urldec':'ud-in','qparse':'qp-in',
            'urlparse':'up-in','urlextract':'uex-in','urlclean':'ucl-in','urlsplit':'usp-in',
            'ascii':'asc-in','unicode':'uni-in','utf8':'u8-in','utf16':'u16-in',
            'binenc':'bine-in','bindec':'bind-in',
            'octal':'oct-in','decimal':'deci-in','hex':'hex-in','charcode':'cc-in'
        };
        var smap = {
            'b64dec': btoa(unescape(encodeURIComponent(SAMPLE_TEXT))),
            'b64urlenc': SAMPLE_TEXT,
            'b64urldec': btoa(unescape(encodeURIComponent(SAMPLE_TEXT))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
            'urldec': encodeURIComponent(SAMPLE_TEXT),
            'qparse': 'name=John+Doe&lang=en&sort=date&page=2',
            'urlparse': SAMPLE_URL, 'urlextract': 'Visit https://example.com or https://github.com/user/repo for more info.\nSee also: http://docs.example.org/guide?ref=home',
            'urlclean': SAMPLE_URL, 'urlsplit': SAMPLE_URL,
            'bindec': SAMPLE_BINARY
        };
        var id = map[currentTool]; if (!id) return;
        sv(id, smap[currentTool] || SAMPLE_TEXT);
        triggerAuto(id);
    }

    /* ══════════════════════════════════════════════════════════════════
       BASE64 TOOLS
       ══════════════════════════════════════════════════════════════════ */

    /* UTF-8 safe base64 encode */
    function b64Encode(str) {
        return btoa(unescape(encodeURIComponent(str)));
    }
    /* UTF-8 safe base64 decode */
    function b64Decode(b64) {
        return decodeURIComponent(escape(atob(b64)));
    }
    /* Standard → URL-safe */
    function toUrlSafe(b64) {
        return b64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }
    /* URL-safe → standard */
    function fromUrlSafe(b64url) {
        var s = b64url.replace(/-/g, '+').replace(/_/g, '/');
        while (s.length % 4) s += '=';
        return s;
    }

    /* ── 1. Base64 Encoder ───────────────────────────────────────────── */
    function runB64Enc() {
        var raw = gv('b64e-in');
        if (!raw) { sv('b64e-out', ''); meta('b64e-meta', ''); return; }
        try {
            var out = b64Encode(raw);
            sv('b64e-out', out);
            meta('b64e-meta', out.length + ' chars');
        } catch(e) { sv('b64e-out', 'Error: ' + e.message); }
    }

    /* ── 2. Base64 Decoder ───────────────────────────────────────────── */
    function runB64Dec() {
        var raw = gvt('b64d-in').replace(/\s/g,'');
        if (!raw) { sv('b64d-out', ''); st('b64d-st', '', ''); return; }
        try {
            var out = b64Decode(raw);
            sv('b64d-out', out);
            st('b64d-st', bytes(out), 'ok');
        } catch(e) {
            sv('b64d-out', '');
            st('b64d-st', 'Error: invalid Base64', 'error');
        }
    }

    /* ── 3. Base64 Image Encoder ─────────────────────────────────────── */
    function initImgEnc() {
        var zone = el('b64ie-zone');
        if (!zone) return;
        zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('drag-over'); });
        zone.addEventListener('dragleave', function() { zone.classList.remove('drag-over'); });
        zone.addEventListener('drop', function(e) {
            e.preventDefault(); zone.classList.remove('drag-over');
            var file = e.dataTransfer.files[0];
            if (file) handleImgFile(file);
        });
        zone.addEventListener('click', function() {
            var inp = document.createElement('input');
            inp.type = 'file'; inp.accept = 'image/*';
            inp.onchange = function() { if (inp.files[0]) handleImgFile(inp.files[0]); };
            inp.click();
        });
    }

    function handleImgFile(file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var dataUrl = e.target.result;
            var b64 = dataUrl.split(',')[1];
            sv('b64ie-out', dataUrl);
            meta('b64ie-meta', bytes(b64) + ' — ' + file.type + ' — ' + file.name);
            var prev = el('b64ie-preview');
            if (prev) { prev.src = dataUrl; prev.style.display = 'block'; }
            var zone = el('b64ie-zone');
            if (zone) {
                zone.innerHTML = '<img id="b64ie-preview" class="et-img-preview" src="' + dataUrl + '">'
                    + '<div style="font-size:11px;color:var(--color-text-muted);margin-top:8px">'
                    + htmlEsc(file.name) + ' &middot; ' + file.type + ' &middot; ' + bytes(file.size)
                    + '</div>';
            }
        };
        reader.readAsDataURL(file);
    }

    /* ── 4. Base64 Image Decoder ─────────────────────────────────────── */
    function runB64ImgDec() {
        var raw = gvt('b64id-in');
        if (!raw) { sh('b64id-preview', '<span style="color:var(--color-text-muted);font-size:13px">Paste a data URI or Base64 image string above.</span>'); st('b64id-st', '', ''); return; }
        try {
            var dataUrl = raw;
            if (!raw.startsWith('data:')) {
                /* Assume plain base64 — guess mime from magic bytes */
                var stripped = raw.replace(/\s/g, '');
                var mime = 'image/png';
                if (stripped.startsWith('/9j/')) mime = 'image/jpeg';
                else if (stripped.startsWith('R0lGOD')) mime = 'image/gif';
                else if (stripped.startsWith('UklGR')) mime = 'image/webp';
                dataUrl = 'data:' + mime + ';base64,' + stripped;
            }
            var b64part = dataUrl.split(',')[1] || '';
            var mimepart = (dataUrl.match(/data:([^;]+)/) || [])[1] || 'unknown';
            sh('b64id-preview',
                '<img class="et-img-preview" src="' + dataUrl + '" onerror="this.parentNode.innerHTML=\'<span style=color:#dc2626>Invalid image data</span>\'">'
                + '<div style="font-size:11px;color:var(--color-text-muted);margin-top:8px">'
                + mimepart + ' &middot; ' + bytes(b64part) + '</div>'
            );
            st('b64id-st', mimepart, 'ok');
        } catch(e) {
            sh('b64id-preview', '<span style="color:#dc2626;font-size:13px">Error: ' + htmlEsc(e.message) + '</span>');
            st('b64id-st', 'Error', 'error');
        }
    }

    /* ── 5. URL-safe Base64 Encoder ──────────────────────────────────── */
    function runB64UrlEnc() {
        var raw = gv('b64ue-in');
        if (!raw) { sv('b64ue-out', ''); meta('b64ue-meta', ''); return; }
        try {
            var out = toUrlSafe(b64Encode(raw));
            sv('b64ue-out', out);
            meta('b64ue-meta', out.length + ' chars');
        } catch(e) { sv('b64ue-out', 'Error: ' + e.message); }
    }

    /* ── 6. URL-safe Base64 Decoder ──────────────────────────────────── */
    function runB64UrlDec() {
        var raw = gvt('b64ud-in').replace(/\s/g,'');
        if (!raw) { sv('b64ud-out', ''); st('b64ud-st', '', ''); return; }
        try {
            var out = b64Decode(fromUrlSafe(raw));
            sv('b64ud-out', out);
            st('b64ud-st', bytes(out), 'ok');
        } catch(e) {
            sv('b64ud-out', '');
            st('b64ud-st', 'Error: invalid Base64url', 'error');
        }
    }

    /* ══════════════════════════════════════════════════════════════════
       URL ENCODING TOOLS
       ══════════════════════════════════════════════════════════════════ */

    /* ── 7. URL Encoder ──────────────────────────────────────────────── */
    function runUrlEnc() {
        var raw = gv('ue-in');
        if (!raw) { sv('ue-out', ''); meta('ue-meta', ''); return; }
        var modeEl = el('ue-mode');
        var mode = modeEl ? modeEl.value : 'component';
        var out = (mode === 'full') ? encodeURI(raw) : encodeURIComponent(raw);
        sv('ue-out', out);
        meta('ue-meta', out.length + ' chars');
    }

    /* ── 8. URL Decoder ──────────────────────────────────────────────── */
    function runUrlDec() {
        var raw = gv('ud-in');
        if (!raw) { sv('ud-out', ''); st('ud-st', '', ''); return; }
        try {
            var out;
            try { out = decodeURIComponent(raw.replace(/\+/g, ' ')); }
            catch(_) { out = decodeURI(raw); }
            sv('ud-out', out);
            st('ud-st', bytes(out), 'ok');
        } catch(e) {
            sv('ud-out', '');
            st('ud-st', 'Error: malformed encoding', 'error');
        }
    }

    /* ── 9. URL Query Builder ────────────────────────────────────────── */
    var _qbRows = [['', '']];

    function renderQbRows() {
        var container = el('qb-rows');
        if (!container) return;
        var html = '';
        _qbRows.forEach(function(row, i) {
            html += '<div class="et-qb-row">'
                + '<input class="et-qb-input" type="text" placeholder="key" value="' + htmlEsc(row[0]) + '" oninput="ET._qbKey(' + i + ',this.value)">'
                + '<span class="et-qb-sep">=</span>'
                + '<input class="et-qb-input" type="text" placeholder="value" value="' + htmlEsc(row[1]) + '" oninput="ET._qbVal(' + i + ',this.value)">'
                + '<button class="et-qb-del" onclick="ET._qbDel(' + i + ')" title="Remove">&times;</button>'
                + '</div>';
        });
        container.innerHTML = html;
        buildQuery();
    }

    function _qbKey(i, v) { _qbRows[i][0] = v; buildQuery(); }
    function _qbVal(i, v) { _qbRows[i][1] = v; buildQuery(); }
    function _qbAdd()     { _qbRows.push(['', '']); renderQbRows(); }
    function _qbDel(i)    { if (_qbRows.length > 1) { _qbRows.splice(i, 1); } else { _qbRows[0] = ['','']; } renderQbRows(); }

    function buildQuery() {
        var baseEl = el('qb-base');
        var base = baseEl ? baseEl.value.trim() : '';
        var pairs = _qbRows.filter(function(r) { return r[0].trim(); });
        var qs = pairs.map(function(r) { return encodeURIComponent(r[0]) + '=' + encodeURIComponent(r[1]); }).join('&');
        var out = base ? (base + (qs ? '?' + qs : '')) : (qs || '');
        sv('qb-out', out);
        meta('qb-meta', pairs.length + ' param' + (pairs.length !== 1 ? 's' : ''));
    }

    /* ── 10. URL Query Parser ────────────────────────────────────────── */
    function runQParse() {
        var raw = gvt('qp-in');
        var out = el('qp-out'); if (!out) return;
        if (!raw) { out.innerHTML = '<div class="et-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Paste a query string above</div>'; meta('qp-meta', ''); return; }
        try {
            var qs = raw.includes('?') ? raw.split('?')[1] : raw;
            qs = qs.split('#')[0];
            var pairs = qs ? qs.split('&') : [];
            if (pairs.length === 0 || (pairs.length === 1 && !pairs[0])) {
                out.innerHTML = '<div style="padding:14px;color:var(--color-text-muted);font-size:13px">No parameters found.</div>';
                return;
            }
            var html = '';
            var count = 0;
            pairs.forEach(function(pair) {
                if (!pair) return;
                var idx = pair.indexOf('=');
                var k = idx >= 0 ? pair.substring(0, idx) : pair;
                var v = idx >= 0 ? pair.substring(idx + 1) : '';
                try { k = decodeURIComponent(k.replace(/\+/g,' ')); } catch(_) {}
                try { v = decodeURIComponent(v.replace(/\+/g,' ')); } catch(_) {}
                html += '<div class="et-result-item"><span class="et-result-key">' + htmlEsc(k) + '</span><span class="et-result-val">' + htmlEsc(v) + '</span></div>';
                count++;
            });
            out.innerHTML = html;
            meta('qp-meta', count + ' param' + (count !== 1 ? 's' : ''));
        } catch(e) {
            out.innerHTML = '<div style="padding:14px;color:#dc2626;font-size:13px">Error: ' + htmlEsc(e.message) + '</div>';
        }
    }

    /* ── 11. URL Parser ──────────────────────────────────────────────── */
    function runUrlParse() {
        var raw = gvt('up-in');
        var out = el('up-out'); if (!out) return;
        if (!raw) {
            out.innerHTML = '<div class="et-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>Paste a URL above to parse it</div>';
            return;
        }
        try {
            var url = new URL(raw.includes('://') ? raw : 'https://' + raw);
            var fields = [
                ['Protocol',  url.protocol.replace(':','')],
                ['Host',      url.hostname],
                ['Port',      url.port || '(default)'],
                ['Path',      url.pathname || '/'],
                ['Query',     url.search   || '(none)'],
                ['Hash',      url.hash     || '(none)'],
                ['Origin',    url.origin],
                ['Full URL',  url.href]
            ];
            var html = '<table class="et-url-table">';
            fields.forEach(function(f) {
                var empty = f[1] === '(none)' || f[1] === '(default)';
                html += '<tr><td>' + f[0] + '</td><td' + (empty ? ' class="et-url-empty"' : '') + '>' + htmlEsc(f[1]) + '</td></tr>';
            });
            /* Query params breakdown */
            if (url.search) {
                html += '<tr><td>Params</td><td><table style="width:100%;font-size:12px">';
                url.searchParams.forEach(function(v, k) {
                    html += '<tr><td style="padding:2px 8px 2px 0;color:var(--color-primary);font-weight:500">' + htmlEsc(k) + '</td><td style="padding:2px 0">' + htmlEsc(v) + '</td></tr>';
                });
                html += '</table></td></tr>';
            }
            html += '</table>';
            out.innerHTML = html;
        } catch(e) {
            out.innerHTML = '<div style="padding:14px;color:#dc2626;font-size:13px">Error: ' + htmlEsc(e.message) + '</div>';
        }
    }

    /* ── 12. URL Extractor ───────────────────────────────────────────── */
    function runUrlExtract() {
        var raw = gv('uex-in');
        var out = el('uex-out'); if (!out) return;
        if (!raw.trim()) {
            out.innerHTML = '<div class="et-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>Paste text above to extract URLs</div>';
            meta('uex-meta', '');
            return;
        }
        var re = /https?:\/\/[^\s"'<>\])\}]+/g;
        var matches = raw.match(re) || [];
        /* Deduplicate preserving order */
        var seen = {}, unique = [];
        matches.forEach(function(u) { if (!seen[u]) { seen[u] = true; unique.push(u); } });
        if (unique.length === 0) {
            out.innerHTML = '<div style="padding:14px;color:var(--color-text-muted);font-size:13px">No URLs found.</div>';
            meta('uex-meta', '');
            return;
        }
        var html = '';
        unique.forEach(function(u, i) {
            html += '<div class="et-result-item"><span class="et-result-idx">#' + (i+1) + '</span><a href="' + htmlEsc(u) + '" target="_blank" rel="noopener" style="color:var(--color-primary);text-decoration:none;word-break:break-all">' + htmlEsc(u) + '</a></div>';
        });
        out.innerHTML = html;
        meta('uex-meta', unique.length + ' URL' + (unique.length !== 1 ? 's' : ''));
    }

    /* ── 13. URL Cleaner ─────────────────────────────────────────────── */
    var TRACKING_PARAMS = [
        'utm_source','utm_medium','utm_campaign','utm_term','utm_content',
        'utm_id','utm_source_platform','utm_creative_format','utm_marketing_tactic',
        'fbclid','gclid','gclsrc','dclid','gbraid','wbraid',
        'msclkid','twclid','mc_cid','mc_eid','igshid',
        '_ga','_gl','ref','referrer','source','campaign',
        'affiliate','aff_id','offer_id','transaction_id'
    ];

    function runUrlClean() {
        var raw = gvt('ucl-in');
        if (!raw) { sv('ucl-out', ''); meta('ucl-meta', ''); return; }
        try {
            var url = new URL(raw.includes('://') ? raw : 'https://' + raw);
            var removed = 0;
            TRACKING_PARAMS.forEach(function(p) {
                if (url.searchParams.has(p)) { url.searchParams.delete(p); removed++; }
            });
            /* Custom params typed by user */
            var customEl = el('ucl-custom');
            if (customEl && customEl.value.trim()) {
                customEl.value.split(',').forEach(function(p) {
                    p = p.trim();
                    if (p && url.searchParams.has(p)) { url.searchParams.delete(p); removed++; }
                });
            }
            var out = url.toString();
            /* Remove trailing ? if no params remain */
            out = out.replace(/\?$/, '');
            sv('ucl-out', out);
            meta('ucl-meta', removed + ' param' + (removed !== 1 ? 's' : '') + ' removed');
        } catch(e) {
            sv('ucl-out', '');
            meta('ucl-meta', 'Error: ' + e.message);
        }
    }

    /* ── 14. URL Splitter ────────────────────────────────────────────── */
    function runUrlSplit() {
        var raw = gvt('usp-in');
        var out = el('usp-out'); if (!out) return;
        if (!raw) {
            out.innerHTML = '<div class="et-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>Paste a URL to split</div>';
            return;
        }
        try {
            var url = new URL(raw.includes('://') ? raw : 'https://' + raw);
            var segments = url.pathname.split('/').filter(function(s) { return s; });
            var html = '<div style="padding:14px;font-family:monospace;font-size:13px;line-height:2">';
            html += '<span style="color:#d97706">' + htmlEsc(url.protocol + '//') + '</span>';
            html += '<span style="color:var(--color-primary);font-weight:600">' + htmlEsc(url.hostname) + '</span>';
            if (url.port) html += '<span style="color:#7c3aed">:' + htmlEsc(url.port) + '</span>';
            segments.forEach(function(seg) {
                html += '<span style="color:var(--color-text-muted)">/</span><span style="color:#16a34a">' + htmlEsc(seg) + '</span>';
            });
            if (!segments.length) html += '<span style="color:var(--color-text-muted)">/</span>';
            if (url.search) {
                html += '<span style="color:var(--color-text-muted)">?</span>';
                var first = true;
                url.searchParams.forEach(function(v, k) {
                    if (!first) html += '<span style="color:var(--color-text-muted)">&amp;</span>';
                    html += '<span style="color:#d97706">' + htmlEsc(k) + '</span>'
                          + '<span style="color:var(--color-text-muted)">=</span>'
                          + '<span style="color:#0ea5e9">' + htmlEsc(v) + '</span>';
                    first = false;
                });
            }
            if (url.hash) html += '<span style="color:#7c3aed">' + htmlEsc(url.hash) + '</span>';
            html += '</div>';
            /* Segment table */
            html += '<table class="et-url-table" style="margin-top:0;border-top:1px solid var(--color-border)">';
            html += '<tr><td>Protocol</td><td style="color:#d97706">' + htmlEsc(url.protocol.replace(':','')) + '</td></tr>';
            html += '<tr><td>Host</td><td style="color:var(--color-primary)">' + htmlEsc(url.hostname) + '</td></tr>';
            if (url.port) html += '<tr><td>Port</td><td style="color:#7c3aed">' + htmlEsc(url.port) + '</td></tr>';
            segments.forEach(function(seg, i) {
                html += '<tr><td>Segment ' + (i+1) + '</td><td style="color:#16a34a">' + htmlEsc(seg) + '</td></tr>';
            });
            url.searchParams.forEach(function(v, k) {
                html += '<tr><td style="padding-left:14px">' + htmlEsc(k) + '</td><td style="color:#0ea5e9">' + htmlEsc(v) + '</td></tr>';
            });
            if (url.hash) html += '<tr><td>Hash</td><td style="color:#7c3aed">' + htmlEsc(url.hash.slice(1)) + '</td></tr>';
            html += '</table>';
            out.innerHTML = html;
        } catch(e) {
            out.innerHTML = '<div style="padding:14px;color:#dc2626;font-size:13px">Error: ' + htmlEsc(e.message) + '</div>';
        }
    }

    /* ══════════════════════════════════════════════════════════════════
       CHARACTER ENCODING TOOLS
       ══════════════════════════════════════════════════════════════════ */

    /* ── 15. ASCII Converter ─────────────────────────────────────────── */
    function runAscii() {
        var raw = gv('asc-in');
        var modeEl = el('asc-mode');
        var mode = modeEl ? modeEl.value : 'encode';
        if (!raw) { sv('asc-out', ''); meta('asc-meta', ''); return; }
        try {
            if (mode === 'encode') {
                var codes = Array.from(raw).map(function(c) { return c.codePointAt(0); }).join(' ');
                sv('asc-out', codes);
                meta('asc-meta', Array.from(raw).length + ' chars');
            } else {
                var chars = raw.trim().split(/\s+/).map(function(n) {
                    var code = parseInt(n, 10);
                    if (isNaN(code)) throw new Error('Invalid code: ' + n);
                    return String.fromCodePoint(code);
                }).join('');
                sv('asc-out', chars);
                meta('asc-meta', chars.length + ' chars');
            }
        } catch(e) { sv('asc-out', 'Error: ' + e.message); meta('asc-meta', ''); }
    }

    /* ── 16. Unicode Converter ───────────────────────────────────────── */
    function runUnicode() {
        var raw = gv('uni-in');
        var modeEl = el('uni-mode');
        var mode = modeEl ? modeEl.value : 'encode';
        if (!raw) { sv('uni-out', ''); meta('uni-meta', ''); return; }
        try {
            if (mode === 'encode') {
                var points = Array.from(raw).map(function(c) {
                    return 'U+' + c.codePointAt(0).toString(16).toUpperCase().padStart(4, '0');
                }).join(' ');
                sv('uni-out', points);
                meta('uni-meta', Array.from(raw).length + ' code points');
            } else {
                var text = raw.trim().split(/\s+/).map(function(token) {
                    token = token.replace(/^U\+/i, '');
                    var code = parseInt(token, 16);
                    if (isNaN(code)) throw new Error('Invalid code point: ' + token);
                    return String.fromCodePoint(code);
                }).join('');
                sv('uni-out', text);
                meta('uni-meta', text.length + ' chars');
            }
        } catch(e) { sv('uni-out', 'Error: ' + e.message); meta('uni-meta', ''); }
    }

    /* ── 17. UTF-8 Converter ─────────────────────────────────────────── */
    function runUtf8() {
        var raw = gv('u8-in');
        if (!raw) { sv('u8-out', ''); meta('u8-meta', ''); return; }
        try {
            var encoder = new TextEncoder();
            var bytes2 = encoder.encode(raw);
            var hex = Array.from(bytes2).map(function(b) { return b.toString(16).padStart(2,'0').toUpperCase(); });
            var sepEl = el('u8-sep');
            var sep = sepEl ? sepEl.value : ' ';
            var out = hex.join(sep);
            sv('u8-out', out);
            meta('u8-meta', bytes2.length + ' byte' + (bytes2.length !== 1 ? 's' : ''));
        } catch(e) { sv('u8-out', 'Error: ' + e.message); }
    }

    /* ── 18. UTF-16 Converter ────────────────────────────────────────── */
    function runUtf16() {
        var raw = gv('u16-in');
        if (!raw) { sv('u16-out', ''); meta('u16-meta', ''); return; }
        try {
            var units = [];
            for (var i = 0; i < raw.length; i++) {
                units.push(raw.charCodeAt(i).toString(16).padStart(4,'0').toUpperCase());
            }
            var sepEl = el('u16-sep');
            var sep = sepEl ? sepEl.value : ' ';
            var out = units.join(sep);
            sv('u16-out', out);
            meta('u16-meta', units.length + ' code unit' + (units.length !== 1 ? 's' : ''));
        } catch(e) { sv('u16-out', 'Error: ' + e.message); }
    }

    /* ── 19. Binary Encoder ──────────────────────────────────────────── */
    function runBinEnc() {
        var raw = gv('bine-in');
        if (!raw) { sv('bine-out', ''); meta('bine-meta', ''); return; }
        var encoder = new TextEncoder();
        var byteArr = encoder.encode(raw);
        var bins = Array.from(byteArr).map(function(b) { return b.toString(2).padStart(8,'0'); });
        var sepEl = el('bine-sep');
        var sep = sepEl ? sepEl.value : ' ';
        var out = bins.join(sep);
        sv('bine-out', out);
        meta('bine-meta', byteArr.length + ' byte' + (byteArr.length !== 1 ? 's' : ''));
    }

    /* ── 20. Binary Decoder ──────────────────────────────────────────── */
    function runBinDec() {
        var raw = gvt('bind-in');
        if (!raw) { sv('bind-out', ''); st('bind-st', '', ''); return; }
        try {
            var tokens = raw.replace(/[^01\s]/g,'').trim().split(/\s+/).filter(function(t) { return t; });
            var byteArr = tokens.map(function(t) {
                if (t.length !== 8) throw new Error('Expected 8-bit groups, got: ' + t);
                return parseInt(t, 2);
            });
            var decoder = new TextDecoder('utf-8', { fatal: true });
            var out = decoder.decode(new Uint8Array(byteArr));
            sv('bind-out', out);
            st('bind-st', bytes(out), 'ok');
        } catch(e) {
            sv('bind-out', '');
            st('bind-st', 'Error: ' + e.message.substring(0,80), 'error');
        }
    }

    /* ── 21. Octal Converter ─────────────────────────────────────────── */
    function runOctal() {
        var raw = gv('oct-in');
        var modeEl = el('oct-mode');
        var mode = modeEl ? modeEl.value : 'encode';
        if (!raw) { sv('oct-out', ''); meta('oct-meta', ''); return; }
        try {
            if (mode === 'encode') {
                var encoder = new TextEncoder();
                var byteArr = encoder.encode(raw);
                var octs = Array.from(byteArr).map(function(b) { return '\\' + b.toString(8).padStart(3,'0'); });
                var out = octs.join('');
                sv('oct-out', out);
                meta('oct-meta', byteArr.length + ' byte' + (byteArr.length !== 1 ? 's' : ''));
            } else {
                var matches = raw.match(/\\[0-7]{1,3}/g) || [];
                var byteVals = matches.map(function(m) { return parseInt(m.slice(1), 8); });
                var decoder = new TextDecoder('utf-8', { fatal: true });
                var text = decoder.decode(new Uint8Array(byteVals));
                sv('oct-out', text);
                meta('oct-meta', text.length + ' chars');
            }
        } catch(e) { sv('oct-out', 'Error: ' + e.message); meta('oct-meta', ''); }
    }

    /* ── 22. Decimal Converter ───────────────────────────────────────── */
    function runDecimal() {
        var raw = gv('deci-in');
        var modeEl = el('deci-mode');
        var mode = modeEl ? modeEl.value : 'encode';
        if (!raw) { sv('deci-out', ''); meta('deci-meta', ''); return; }
        try {
            if (mode === 'encode') {
                var codes = Array.from(raw).map(function(c) { return c.codePointAt(0); }).join(' ');
                sv('deci-out', codes);
                meta('deci-meta', Array.from(raw).length + ' code points');
            } else {
                var text = raw.trim().split(/\s+/).map(function(n) {
                    var code = parseInt(n, 10);
                    if (isNaN(code)) throw new Error('Invalid: ' + n);
                    return String.fromCodePoint(code);
                }).join('');
                sv('deci-out', text);
                meta('deci-meta', text.length + ' chars');
            }
        } catch(e) { sv('deci-out', 'Error: ' + e.message); meta('deci-meta', ''); }
    }

    /* ── 23. Hex Converter ───────────────────────────────────────────── */
    function runHex() {
        var raw = gv('hex-in');
        var modeEl = el('hex-mode');
        var mode = modeEl ? modeEl.value : 'encode';
        if (!raw) { sv('hex-out', ''); meta('hex-meta', ''); return; }
        try {
            if (mode === 'encode') {
                var encoder = new TextEncoder();
                var byteArr = encoder.encode(raw);
                var hexStr = Array.from(byteArr).map(function(b) { return b.toString(16).padStart(2,'0').toUpperCase(); }).join(' ');
                sv('hex-out', hexStr);
                meta('hex-meta', byteArr.length + ' byte' + (byteArr.length !== 1 ? 's' : ''));
            } else {
                var tokens = raw.replace(/0x/gi,'').replace(/\\x/gi,'').trim().split(/[\s,]+/).filter(function(t) { return t; });
                var byteVals = tokens.map(function(t) {
                    var v = parseInt(t, 16);
                    if (isNaN(v)) throw new Error('Invalid hex: ' + t);
                    return v;
                });
                var decoder = new TextDecoder('utf-8', { fatal: true });
                var text = decoder.decode(new Uint8Array(byteVals));
                sv('hex-out', text);
                meta('hex-meta', text.length + ' chars');
            }
        } catch(e) { sv('hex-out', 'Error: ' + e.message); meta('hex-meta', ''); }
    }

    /* ── 24. Character Code Converter ───────────────────────────────── */
    function runCharCode() {
        var raw = gv('cc-in');
        var out = el('cc-out'); if (!out) return;
        if (!raw) {
            out.innerHTML = '<div class="et-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>Type or paste characters above</div>';
            return;
        }
        var chars = Array.from(raw).slice(0, 64); /* Limit for display */
        var html = '<div style="overflow:auto;flex:1"><table class="et-char-table"><thead><tr>'
            + '<th>Char</th><th>Decimal</th><th>Hex</th><th>Binary</th><th>Octal</th><th>HTML</th><th>Unicode</th>'
            + '</tr></thead><tbody>';
        chars.forEach(function(ch) {
            var cp = ch.codePointAt(0);
            var dec = cp;
            var hex = cp.toString(16).toUpperCase().padStart(2,'0');
            var bin = cp.toString(2).padStart(8,'0');
            var oct = cp.toString(8).padStart(3,'0');
            var htmlEnt = '&#' + cp + ';';
            var uni = 'U+' + cp.toString(16).toUpperCase().padStart(4,'0');
            html += '<tr>'
                + '<td>' + (cp < 32 ? '<span style="color:var(--color-text-muted);font-size:11px">ctrl</span>' : htmlEsc(ch)) + '</td>'
                + '<td>' + dec + '</td>'
                + '<td>' + hex + '</td>'
                + '<td style="font-size:11px">' + bin + '</td>'
                + '<td>' + oct + '</td>'
                + '<td>' + htmlEsc(htmlEnt) + '</td>'
                + '<td>' + uni + '</td>'
                + '</tr>';
        });
        if (Array.from(raw).length > 64) html += '<tr><td colspan="7" style="padding:8px 14px;color:var(--color-text-muted);font-size:12px;font-style:italic">Showing first 64 characters…</td></tr>';
        html += '</tbody></table></div>';
        out.innerHTML = html;
    }

    /* ══════════════════════════════════════════════════════════════════
       HASH & CHECKSUM TOOLS
       ══════════════════════════════════════════════════════════════════ */

    /* ── MD5 pure JS ─────────────────────────────────────────────────── */
    function _md5(inputStr) {
        var str = unescape(encodeURIComponent(inputStr));
        function safeAdd(x,y){var l=(x&0xFFFF)+(y&0xFFFF);return(((x>>16)+(y>>16)+(l>>16))<<16)|(l&0xFFFF);}
        function rotL(n,c){return(n<<c)|(n>>>(32-c));}
        function cmn(q,a,b,x,s,t){return safeAdd(rotL(safeAdd(safeAdd(a,q),safeAdd(x,t)),s),b);}
        function ff(a,b,c,d,x,s,t){return cmn((b&c)|(~b&d),a,b,x,s,t);}
        function gg(a,b,c,d,x,s,t){return cmn((b&d)|(c&~d),a,b,x,s,t);}
        function hh(a,b,c,d,x,s,t){return cmn(b^c^d,a,b,x,s,t);}
        function ii(a,b,c,d,x,s,t){return cmn(c^(b|~d),a,b,x,s,t);}
        var l=str.length, nb=((l+8)>>6)+1, blks=new Array(nb*16).fill(0);
        for(var i=0;i<l;i++) blks[i>>2]|=str.charCodeAt(i)<<((i%4)*8);
        blks[l>>2]|=0x80<<((l%4)*8); blks[nb*16-2]=l*8;
        var a=1732584193,b=-271733879,c=-1732584194,d=271733878;
        for(var i=0;i<blks.length;i+=16){
            var oa=a,ob=b,oc=c,od=d;
            a=ff(a,b,c,d,blks[i],7,-680876936);d=ff(d,a,b,c,blks[i+1],12,-389564586);c=ff(c,d,a,b,blks[i+2],17,606105819);b=ff(b,c,d,a,blks[i+3],22,-1044525330);
            a=ff(a,b,c,d,blks[i+4],7,-176418897);d=ff(d,a,b,c,blks[i+5],12,1200080426);c=ff(c,d,a,b,blks[i+6],17,-1473231341);b=ff(b,c,d,a,blks[i+7],22,-45705983);
            a=ff(a,b,c,d,blks[i+8],7,1770035416);d=ff(d,a,b,c,blks[i+9],12,-1958414417);c=ff(c,d,a,b,blks[i+10],17,-42063);b=ff(b,c,d,a,blks[i+11],22,-1990404162);
            a=ff(a,b,c,d,blks[i+12],7,1804603682);d=ff(d,a,b,c,blks[i+13],12,-40341101);c=ff(c,d,a,b,blks[i+14],17,-1502002290);b=ff(b,c,d,a,blks[i+15],22,1236535329);
            a=gg(a,b,c,d,blks[i+1],5,-165796510);d=gg(d,a,b,c,blks[i+6],9,-1069501632);c=gg(c,d,a,b,blks[i+11],14,643717713);b=gg(b,c,d,a,blks[i],20,-373897302);
            a=gg(a,b,c,d,blks[i+5],5,-701558691);d=gg(d,a,b,c,blks[i+10],9,38016083);c=gg(c,d,a,b,blks[i+15],14,-660478335);b=gg(b,c,d,a,blks[i+4],20,-405537848);
            a=gg(a,b,c,d,blks[i+9],5,568446438);d=gg(d,a,b,c,blks[i+14],9,-1019803690);c=gg(c,d,a,b,blks[i+3],14,-187363961);b=gg(b,c,d,a,blks[i+8],20,1163531501);
            a=gg(a,b,c,d,blks[i+13],5,-1444681467);d=gg(d,a,b,c,blks[i+2],9,-51403784);c=gg(c,d,a,b,blks[i+7],14,1735328473);b=gg(b,c,d,a,blks[i+12],20,-1926607734);
            a=hh(a,b,c,d,blks[i+5],4,-378558);d=hh(d,a,b,c,blks[i+8],11,-2022574463);c=hh(c,d,a,b,blks[i+11],16,1839030562);b=hh(b,c,d,a,blks[i+14],23,-35309556);
            a=hh(a,b,c,d,blks[i+1],4,-1530992060);d=hh(d,a,b,c,blks[i+4],11,1272893353);c=hh(c,d,a,b,blks[i+7],16,-155497632);b=hh(b,c,d,a,blks[i+10],23,-1094730640);
            a=hh(a,b,c,d,blks[i+13],4,681279174);d=hh(d,a,b,c,blks[i],11,-358537222);c=hh(c,d,a,b,blks[i+3],16,-722521979);b=hh(b,c,d,a,blks[i+6],23,76029189);
            a=hh(a,b,c,d,blks[i+9],4,-640364487);d=hh(d,a,b,c,blks[i+12],11,-421815835);c=hh(c,d,a,b,blks[i+15],16,530742520);b=hh(b,c,d,a,blks[i+2],23,-995338651);
            a=ii(a,b,c,d,blks[i],6,-198630844);d=ii(d,a,b,c,blks[i+7],10,1126891415);c=ii(c,d,a,b,blks[i+14],15,-1416354905);b=ii(b,c,d,a,blks[i+5],21,-57434055);
            a=ii(a,b,c,d,blks[i+12],6,1700485571);d=ii(d,a,b,c,blks[i+3],10,-1894986606);c=ii(c,d,a,b,blks[i+10],15,-1051523);b=ii(b,c,d,a,blks[i+1],21,-2054922799);
            a=ii(a,b,c,d,blks[i+8],6,1873313359);d=ii(d,a,b,c,blks[i+15],10,-30611744);c=ii(c,d,a,b,blks[i+6],15,-1560198380);b=ii(b,c,d,a,blks[i+13],21,1309151649);
            a=ii(a,b,c,d,blks[i+4],6,-145523070);d=ii(d,a,b,c,blks[i+11],10,-1120210379);c=ii(c,d,a,b,blks[i+2],15,718787259);b=ii(b,c,d,a,blks[i+9],21,-343485551);
            a=safeAdd(a,oa);b=safeAdd(b,ob);c=safeAdd(c,oc);d=safeAdd(d,od);
        }
        function h32(n){var s='';for(var j=0;j<4;j++)s+=('0'+((n>>>(j*8+4))&0xF).toString(16)).slice(-1)+('0'+((n>>>(j*8))&0xF).toString(16)).slice(-1);return s;}
        return h32(a)+h32(b)+h32(c)+h32(d);
    }

    /* ── CRC32 pure JS ───────────────────────────────────────────────── */
    var _crc32Table = null;
    function _makeCrc32Table() {
        if (_crc32Table) return _crc32Table;
        _crc32Table = new Uint32Array(256);
        for (var i=0;i<256;i++){var c=i;for(var j=0;j<8;j++)c=(c&1)?(0xEDB88320^(c>>>1)):(c>>>1);_crc32Table[i]=c;}
        return _crc32Table;
    }
    function _crc32(str) {
        var table = _makeCrc32Table();
        var byteArr = new TextEncoder().encode(str);
        var crc = 0xFFFFFFFF;
        for (var i=0;i<byteArr.length;i++) crc=(crc>>>8)^table[(crc^byteArr[i])&0xFF];
        return (crc^0xFFFFFFFF)>>>0;
    }

    /* ── Web Crypto SHA ──────────────────────────────────────────────── */
    function _sha(algo, str) {
        return crypto.subtle.digest(algo, new TextEncoder().encode(str))
            .then(function(buf){
                return Array.from(new Uint8Array(buf)).map(function(b){return b.toString(16).padStart(2,'0');}).join('');
            });
    }
    function _hmacFn(algo, key, str) {
        var enc = new TextEncoder();
        return crypto.subtle.importKey('raw', enc.encode(key||''), {name:'HMAC',hash:algo}, false, ['sign'])
            .then(function(k){return crypto.subtle.sign('HMAC',k,enc.encode(str));})
            .then(function(buf){return Array.from(new Uint8Array(buf)).map(function(b){return b.toString(16).padStart(2,'0');}).join('');});
    }

    /* ── Hash run helpers ────────────────────────────────────────────── */
    function _hashRun(inId, outId, metaId, algo, metaText) {
        var raw = gv(inId);
        if (!raw) { sv(outId,''); meta(metaId,''); return; }
        sv(outId,'Computing…');
        _sha(algo, raw).then(function(h){ sv(outId,h); meta(metaId,metaText); })
            .catch(function(e){ sv(outId,'Error: '+e.message); });
    }

    /* ── 25. MD5 ─────────────────────────────────────────────────────── */
    function runMd5() {
        var raw = gv('md5-in');
        if (!raw) { sv('md5-out',''); meta('md5-meta',''); return; }
        try { var h = _md5(raw); sv('md5-out',h); meta('md5-meta','128 bits / 16 bytes'); }
        catch(e) { sv('md5-out','Error: '+e.message); }
    }

    /* ── 26. CRC32 ───────────────────────────────────────────────────── */
    function runCrc32() {
        var raw = gv('crc32-in');
        if (!raw) { sv('crc32-out',''); meta('crc32-meta',''); return; }
        var crc = _crc32(raw);
        sv('crc32-out',
            'Hex:     ' + crc.toString(16).toUpperCase().padStart(8,'0') + '\n'
          + 'Decimal: ' + crc + '\n'
          + 'Binary:  ' + crc.toString(2).padStart(32,'0'));
        meta('crc32-meta','32 bits / 4 bytes');
    }

    /* ── 27. SHA-1 ───────────────────────────────────────────────────── */
    function runSha1()   { _hashRun('sha1-in',  'sha1-out',  'sha1-meta',  'SHA-1',   '160 bits'); }

    /* ── 28. SHA-256 ─────────────────────────────────────────────────── */
    function runSha256() { _hashRun('sha256-in','sha256-out','sha256-meta','SHA-256',  '256 bits'); }

    /* ── 29. SHA-384 ─────────────────────────────────────────────────── */
    function runSha384() { _hashRun('sha384-in','sha384-out','sha384-meta','SHA-384',  '384 bits'); }

    /* ── 30. SHA-512 ─────────────────────────────────────────────────── */
    function runSha512() { _hashRun('sha512-in','sha512-out','sha512-meta','SHA-512',  '512 bits'); }

    /* ── 31. HMAC ────────────────────────────────────────────────────── */
    function runHmac() {
        var raw = gv('hmac-in');
        var key = gvt('hmac-key') || '';
        var algoEl = el('hmac-algo');
        var algo = algoEl ? algoEl.value : 'SHA-256';
        if (!raw) { sv('hmac-out',''); meta('hmac-meta',''); return; }
        sv('hmac-out','Computing…');
        _hmacFn(algo, key, raw)
            .then(function(h){ sv('hmac-out',h); meta('hmac-meta','HMAC-'+algo); })
            .catch(function(e){ sv('hmac-out','Error: '+e.message); });
    }

    /* ── Wire up the auto-map ────────────────────────────────────────── */
    _autoMap = {
        'b64e-in': runB64Enc, 'b64d-in': runB64Dec,
        'b64id-in': runB64ImgDec,
        'b64ue-in': runB64UrlEnc, 'b64ud-in': runB64UrlDec,
        'ue-in': runUrlEnc, 'ud-in': runUrlDec,
        'qp-in': runQParse, 'up-in': runUrlParse,
        'uex-in': runUrlExtract, 'ucl-in': runUrlClean, 'usp-in': runUrlSplit,
        'asc-in': runAscii, 'uni-in': runUnicode,
        'u8-in': runUtf8, 'u16-in': runUtf16,
        'bine-in': runBinEnc, 'bind-in': runBinDec,
        'oct-in': runOctal, 'deci-in': runDecimal,
        'hex-in': runHex, 'cc-in': runCharCode,
        'md5-in': runMd5, 'crc32-in': runCrc32,
        'sha1-in': runSha1, 'sha256-in': runSha256,
        'sha384-in': runSha384, 'sha512-in': runSha512,
        'hmac-in': runHmac
    };

    /* ── Init on DOM ready ───────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        switchTool('b64enc');
        renderQbRows();
        initImgEnc();
    });

    /* ── Public API ──────────────────────────────────────────────────── */
    return {
        switchTool: switchTool,
        clearAll:   clearAll,
        loadSample: loadSample,
        pasteInto:  pasteInto,
        openFile:   openFile,
        cpPane:     cpPane,
        dl:         dl,
        /* Base64 */
        runB64Enc:    runB64Enc,
        runB64Dec:    runB64Dec,
        runB64ImgDec: runB64ImgDec,
        runB64UrlEnc: runB64UrlEnc,
        runB64UrlDec: runB64UrlDec,
        /* URL */
        runUrlEnc:    runUrlEnc,
        runUrlDec:    runUrlDec,
        buildQuery:   buildQuery,
        _qbKey:       _qbKey, _qbVal: _qbVal, _qbAdd: _qbAdd, _qbDel: _qbDel,
        runQParse:    runQParse,
        runUrlParse:  runUrlParse,
        runUrlExtract: runUrlExtract,
        runUrlClean:  runUrlClean,
        runUrlSplit:  runUrlSplit,
        /* Char encoding */
        runAscii:     runAscii,
        runUnicode:   runUnicode,
        runUtf8:      runUtf8,
        runUtf16:     runUtf16,
        runBinEnc:    runBinEnc,
        runBinDec:    runBinDec,
        runOctal:     runOctal,
        runDecimal:   runDecimal,
        runHex:       runHex,
        runCharCode:  runCharCode,
        /* Hash & Checksum */
        runMd5:    runMd5,
        runCrc32:  runCrc32,
        runSha1:   runSha1,
        runSha256: runSha256,
        runSha384: runSha384,
        runSha512: runSha512,
        runHmac:   runHmac
    };
})();
