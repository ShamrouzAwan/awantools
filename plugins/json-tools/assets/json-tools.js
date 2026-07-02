/* =====================================================================
   JSON Tools — AWAN Platform Plugin v2.0.0
   All processing is 100% client-side. Zero server communication.
   ===================================================================== */
var JT = (function() {
    'use strict';

    var currentTool = 'formatter';

    /* ── Sample data ─────────────────────────────────────────────────── */
    var SAMPLE_JSON  = '{\n  "platform": "Awan Tools",\n  "version": "2.0.0",\n  "active": true,\n  "users": 1024,\n  "tags": ["developer", "utilities", "json"],\n  "config": {\n    "debug": false,\n    "timezone": "UTC",\n    "limits": { "api": 1000, "storage": 512 }\n  },\n  "metadata": null\n}';
    var SAMPLE_JSON2 = '{\n  "platform": "Awan Tools",\n  "version": "2.1.0",\n  "active": true,\n  "users": 2048,\n  "tags": ["developer", "utilities", "json", "yaml"],\n  "config": {\n    "debug": true,\n    "timezone": "UTC",\n    "limits": { "api": 2000 }\n  },\n  "author": "AWAN Team"\n}';
    var SAMPLE_CSV   = 'name,age,city,active\nAlice,30,London,true\nBob,25,Paris,false\nCarla,35,Tokyo,true';
    var SAMPLE_YAML  = 'platform: Awan Tools\nversion: 2.0.0\nactive: true\nusers: 1024\ntags:\n  - developer\n  - utilities\n  - json\nconfig:\n  debug: false\n  timezone: UTC';
    var SAMPLE_XML   = '<root>\n  <platform>Awan Tools</platform>\n  <version>2.0.0</version>\n  <active>true</active>\n  <users>1024</users>\n  <config>\n    <debug>false</debug>\n    <timezone>UTC</timezone>\n  </config>\n</root>';
    var SAMPLE_ESC   = 'He said "Hello, World!"\nNew line here.\tAnd a tab.';
    var SAMPLE_UNESC = 'He said \\"Hello, World!\\"\\nNew line here.\\tAnd a tab.';

    /* ── DOM helpers ─────────────────────────────────────────────────── */
    function el(id)    { return document.getElementById(id); }
    function gv(id)    { var e = el(id); return e ? e.value.trim() : ''; }
    function sv(id, v) { var e = el(id); if (e) e.value = v; }

    function st(id, msg, type) {
        var e = el(id); if (!e) return;
        if (!msg) { e.innerHTML = ''; return; }
        if (type === 'ok' || type === 'error') {
            e.innerHTML = '<span class="jt-status-chip ' + type + '"><span class="jt-dot"></span>' + msg + '</span>';
        } else {
            e.innerHTML = '<span style="color:var(--color-text-muted);font-size:12px">' + msg + '</span>';
        }
    }

    function meta(id, text) { var e = el(id); if (e) e.textContent = text; }

    /* ── Parse helpers ───────────────────────────────────────────────── */
    function tryJSON(s) {
        try { return { ok: true, data: JSON.parse(s) }; }
        catch (e) { return { ok: false, err: e.message }; }
    }

    function bytes(s) {
        var b = new Blob([s]).size;
        return b >= 1024 * 1024 ? (b / 1024 / 1024).toFixed(1) + ' MB' : b >= 1024 ? (b / 1024).toFixed(1) + ' KB' : b + ' B';
    }

    function esc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* ── Navigation ──────────────────────────────────────────────────── */
    function switchTool(id) {
        currentTool = id;
        document.querySelectorAll('.jt-panel').forEach(function(p) { p.style.display = 'none'; });
        var p = el('jt-' + id); if (p) p.style.display = '';
        document.querySelectorAll('.jt-nav').forEach(function(b) { b.classList.toggle('active', b.dataset.tool === id); });
        /* scroll active tab into view on mobile */
        var activeBtn = document.querySelector('.jt-nav.active');
        if (activeBtn) activeBtn.scrollIntoView({ inline: 'nearest', block: 'nearest' });
    }

    /* ── Clear helpers ───────────────────────────────────────────────── */
    function clrValidator() { sv('val-in', ''); var r = el('val-res'); if (r) { r.innerHTML = ''; r.style.display = 'none'; } }
    function clrParser()    { sv('par-in', ''); var r = el('par-res'); if (r) r.innerHTML = ''; }
    function clrViewer()    { sv('view-in', ''); var r = el('view-tree'); if (r) r.innerHTML = '<span style="color:var(--color-text-muted);font-size:13px">Paste JSON above to see the interactive tree.</span>'; }

    function clearAll() {
        var inMap = {
            formatter:'fmt-in', beautifier:'beau-in', minifier:'min-in',
            validator:'val-in', parser:'par-in', viewer:'view-in',
            prettyprint:'pp-in', escape:'esc-in', unescape:'unesc-in',
            json2csv:'j2c-in', csv2json:'c2j-in', json2xml:'j2x-in',
            xml2json:'x2j-in', json2yaml:'j2y-in', yaml2json:'y2j-in',
            diff:'diff-a', sortkeys:'sk-in', flatten:'fl-in', jsonpath:'jp-in'
        };
        var id = inMap[currentTool]; if (id) sv(id, '');
        if (currentTool === 'diff') sv('diff-b', '');
    }

    /* ── Paste from clipboard ────────────────────────────────────────── */
    function pasteInto(targetId, btn) {
        if (!navigator.clipboard || !navigator.clipboard.readText) {
            alert('Clipboard read is not supported in this browser. Use Ctrl+V inside the textarea instead.');
            return;
        }
        navigator.clipboard.readText().then(function(text) {
            sv(targetId, text);
            /* auto-trigger the relevant tool */
            var autoMap = {
                'fmt-in': runFormatter, 'beau-in': runBeautifier, 'min-in': runMinifier,
                'val-in': runValidator, 'par-in': runParser, 'view-in': runViewer,
                'pp-in': runPrettyPrint, 'esc-in': runEscape, 'unesc-in': runUnescape,
                'sk-in': runSortKeys, 'fl-in': runFlatten, 'jp-in': runJsonPath,
                'j2c-in': runJson2Csv, 'c2j-in': runCsv2Json,
                'j2x-in': runJson2Xml, 'x2j-in': runXml2Json,
                'j2y-in': runJson2Yaml, 'y2j-in': runYaml2Json,
                'diff-a': runDiff, 'diff-b': runDiff
            };
            if (autoMap[targetId]) autoMap[targetId]();
            if (btn) {
                var orig = btn.innerHTML;
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Pasted!';
                btn.classList.add('copied');
                setTimeout(function() { btn.innerHTML = orig; btn.classList.remove('copied'); }, 1400);
            }
        }).catch(function() {
            alert('Could not read clipboard. Please paste manually with Ctrl+V.');
        });
    }

    /* ── Open file ───────────────────────────────────────────────────── */
    function openFile(targetId, accept) {
        var inp = document.createElement('input');
        inp.type = 'file';
        inp.accept = accept || '.json,.txt';
        inp.onchange = function() {
            var file = inp.files[0]; if (!file) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                sv(targetId, e.target.result);
                var autoMap = {
                    'fmt-in': runFormatter, 'beau-in': runBeautifier, 'min-in': runMinifier,
                    'val-in': runValidator, 'par-in': runParser, 'view-in': runViewer,
                    'pp-in': runPrettyPrint, 'sk-in': runSortKeys, 'fl-in': runFlatten, 'jp-in': runJsonPath,
                    'j2c-in': runJson2Csv, 'j2x-in': runJson2Xml, 'j2y-in': runJson2Yaml,
                    'c2j-in': runCsv2Json, 'x2j-in': runXml2Json, 'y2j-in': runYaml2Json
                };
                if (autoMap[targetId]) autoMap[targetId]();
            };
            reader.readAsText(file);
        };
        inp.click();
    }

    /* ── Copy ────────────────────────────────────────────────────────── */
    function cp(id) {
        var e = el(id); if (!e || !e.value) return;
        navigator.clipboard.writeText(e.value).then(function() {
            var orig = e.style.border;
            e.style.border = '1.5px solid #22c55e';
            setTimeout(function() { e.style.border = orig; }, 1200);
        });
    }

    function cpPane(id, btn) {
        var e = el(id); if (!e || !e.value) return;
        navigator.clipboard.writeText(e.value).then(function() {
            if (btn) {
                btn.classList.add('copied');
                var orig = btn.innerHTML;
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><polyline points="20 6 9 17 4 12"/></svg>Copied!';
                setTimeout(function() { btn.innerHTML = orig; btn.classList.remove('copied'); }, 1600);
            }
        });
    }

    /* ── Download ────────────────────────────────────────────────────── */
    function dl(id, fname, mime) {
        var v = gv(id); if (!v) return;
        var a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([v], { type: mime }));
        a.download = fname; a.click();
    }

    /* ── Load sample ─────────────────────────────────────────────────── */
    function loadSample() {
        var map = {
            formatter:'fmt-in', beautifier:'beau-in', minifier:'min-in',
            validator:'val-in', parser:'par-in', viewer:'view-in',
            prettyprint:'pp-in', escape:'esc-in', unescape:'unesc-in',
            json2csv:'j2c-in', csv2json:'c2j-in', json2xml:'j2x-in',
            xml2json:'x2j-in', json2yaml:'j2y-in', yaml2json:'y2j-in',
            sortkeys:'sk-in', flatten:'fl-in', jsonpath:'jp-in',
            diff:'diff-a'
        };
        var smap = {
            csv2json: SAMPLE_CSV, xml2json: SAMPLE_XML,
            yaml2json: SAMPLE_YAML, escape: SAMPLE_ESC, unescape: SAMPLE_UNESC,
            diff: SAMPLE_JSON
        };
        var id = map[currentTool]; if (!id) return;
        sv(id, smap[currentTool] || SAMPLE_JSON);
        if (currentTool === 'diff') sv('diff-b', SAMPLE_JSON2);
        var auto = {
            formatter: runFormatter, beautifier: runBeautifier, minifier: runMinifier,
            validator: runValidator, parser: runParser, viewer: runViewer,
            prettyprint: runPrettyPrint, escape: runEscape, unescape: runUnescape,
            sortkeys: runSortKeys, flatten: runFlatten, jsonpath: runJsonPath, diff: runDiff,
            json2csv: runJson2Csv, csv2json: runCsv2Json,
            json2xml: runJson2Xml, xml2json: runXml2Json,
            json2yaml: runJson2Yaml, yaml2json: runYaml2Json
        };
        if (auto[currentTool]) auto[currentTool]();
    }

    /* ══════════════════════════════════════════════════════════════════
       TOOL IMPLEMENTATIONS
       ══════════════════════════════════════════════════════════════════ */

    /* ── 1. Formatter ────────────────────────────────────────────────── */
    function runFormatter() {
        var raw = gv('fmt-in');
        if (!raw) { sv('fmt-out', ''); st('fmt-st', '', ''); meta('fmt-meta', ''); return; }
        var indEl = el('fmt-indent');
        var ind = indEl ? (indEl.value === 'tab' ? '\t' : parseInt(indEl.value) || 2) : 2;
        var r = tryJSON(raw);
        if (!r.ok) { sv('fmt-out', ''); st('fmt-st', 'Error: ' + r.err, 'error'); meta('fmt-meta', ''); return; }
        var out = JSON.stringify(r.data, null, ind);
        sv('fmt-out', out);
        st('fmt-st', bytes(out), 'ok');
        meta('fmt-meta', out.split('\n').length + ' lines');
    }

    /* ── 2. Beautifier ───────────────────────────────────────────────── */
    function runBeautifier() {
        var raw = gv('beau-in');
        if (!raw) { sv('beau-out', ''); st('beau-st', '', ''); meta('beau-meta', ''); return; }
        var r = tryJSON(raw);
        if (!r.ok) { sv('beau-out', ''); st('beau-st', 'Error: ' + r.err, 'error'); meta('beau-meta', ''); return; }
        var out = JSON.stringify(r.data, null, 2);
        sv('beau-out', out);
        st('beau-st', bytes(out), 'ok');
        meta('beau-meta', out.split('\n').length + ' lines');
    }

    /* ── 3. Minifier ─────────────────────────────────────────────────── */
    function runMinifier() {
        var raw = gv('min-in');
        if (!raw) { sv('min-out', ''); st('min-st', '', ''); meta('min-meta', ''); return; }
        var r = tryJSON(raw);
        if (!r.ok) { sv('min-out', ''); st('min-st', 'Error: ' + r.err, 'error'); meta('min-meta', ''); return; }
        var out = JSON.stringify(r.data);
        sv('min-out', out);
        var pct = raw.length > 0 ? Math.round((1 - out.length / raw.length) * 100) : 0;
        st('min-st', bytes(out) + ' \u2013 \u2212' + pct + '%', 'ok');
        meta('min-meta', out.length + ' chars');
    }

    /* ── 4. Validator ────────────────────────────────────────────────── */
    function runValidator() {
        var raw = gv('val-in');
        var res = el('val-res'); if (!res) return;
        if (!raw) { res.innerHTML = ''; res.style.display = 'none'; return; }
        var r = tryJSON(raw);
        res.style.display = 'block';
        if (r.ok) {
            var type = Array.isArray(r.data) ? 'array' : (r.data === null ? 'null' : typeof r.data);
            var cnt = type === 'object' ? Object.keys(r.data).length : type === 'array' ? r.data.length : null;
            res.style.background = 'rgba(22,163,74,.08)'; res.style.borderColor = '#22c55e';
            res.innerHTML = '<strong style="color:#16a34a">\u2713 Valid JSON</strong><br>'
                + '<span style="font-size:12px;color:var(--color-text-muted)">Type: <strong>' + type + '</strong>'
                + (cnt !== null ? ' &nbsp;&middot;&nbsp; Count: <strong>' + cnt + '</strong>' : '')
                + ' &nbsp;&middot;&nbsp; Size: <strong>' + bytes(raw) + '</strong></span>';
        } else {
            res.style.background = 'rgba(220,38,38,.08)'; res.style.borderColor = '#dc2626';
            res.innerHTML = '<strong style="color:#dc2626">\u2717 Invalid JSON</strong><br>'
                + '<span style="font-size:12px;color:var(--color-text-muted)">' + esc(r.err) + '</span>';
        }
    }

    /* ── 5. Parser ───────────────────────────────────────────────────── */
    function runParser() {
        var raw = gv('par-in');
        var out = el('par-res'); if (!out) return;
        if (!raw) { out.innerHTML = ''; return; }
        var r = tryJSON(raw);
        if (!r.ok) {
            out.innerHTML = '<div style="padding:12px;background:rgba(220,38,38,.08);border:1px solid #dc2626;border-radius:6px;color:#dc2626;font-size:13px">Error: ' + esc(r.err) + '</div>';
            return;
        }
        out.innerHTML = '<div class="card"><div class="card-header"><span class="card-title" style="font-size:13px">Parsed Structure</span></div>'
            + '<div class="card-body" style="padding:0;font-family:monospace;font-size:12px;line-height:1.9">'
            + buildParserRows(r.data, 'root', 0) + '</div></div>';
    }

    var TYPE_CLR = { string:'#16a34a', number:'#d97706', boolean:'#dc2626', 'null':'var(--color-text-muted)', object:'var(--color-primary)', array:'#a855f7' };

    function buildParserRows(data, path, depth) {
        var pad = depth * 16, type, displayVal;
        if (data === null)            { type = 'null';   displayVal = 'null'; }
        else if (Array.isArray(data)) { type = 'array';  displayVal = '[' + data.length + ' item' + (data.length !== 1 ? 's' : '') + ']'; }
        else if (typeof data === 'object') { type = 'object'; displayVal = '{' + Object.keys(data).length + ' key' + (Object.keys(data).length !== 1 ? 's' : '') + '}'; }
        else { type = typeof data; displayVal = type === 'string' ? '"' + esc(String(data)) + '"' : esc(String(data)); }
        var c = TYPE_CLR[type] || 'var(--color-text)';
        var row = '<div style="display:flex;gap:8px;padding:3px 12px 3px ' + (12 + pad) + 'px;border-bottom:1px solid var(--color-border)">'
            + '<span style="flex:1;color:var(--color-text);word-break:break-all">' + esc(path) + '</span>'
            + '<span style="width:54px;text-align:center;font-size:10px;font-weight:700;color:' + c + ';background:' + c + '18;border-radius:3px;padding:1px 4px;flex-shrink:0">' + type + '</span>'
            + '<span style="flex:1;text-align:right;color:' + c + ';word-break:break-all">' + displayVal + '</span>'
            + '</div>';
        if (Array.isArray(data)) { for (var i = 0; i < data.length; i++) row += buildParserRows(data[i], path + '[' + i + ']', depth + 1); }
        else if (type === 'object') { var ks = Object.keys(data); for (var k = 0; k < ks.length; k++) row += buildParserRows(data[ks[k]], path + '.' + ks[k], depth + 1); }
        return row;
    }

    /* ── 6. Viewer ───────────────────────────────────────────────────── */
    function runViewer() {
        var raw = gv('view-in');
        var tree = el('view-tree'); if (!tree) return;
        if (!raw) { tree.innerHTML = '<span style="color:var(--color-text-muted);font-size:13px">Paste JSON above to see the interactive tree.</span>'; return; }
        var r = tryJSON(raw);
        if (!r.ok) { tree.innerHTML = '<span style="color:#dc2626;font-size:13px">Error: ' + esc(r.err) + '</span>'; return; }
        _treeId = 0;
        tree.innerHTML = buildTreeNode(r.data, null, true);
    }

    var _treeId = 0;
    function buildTreeNode(data, key, expanded) {
        var keyHtml = key !== null ? '<span class="jt-key">' + esc(String(key)) + '</span><span style="color:var(--color-text-muted)">: </span>' : '';
        if (data === null)             return '<div>' + keyHtml + '<span class="jt-null">null</span></div>';
        if (typeof data === 'boolean') return '<div>' + keyHtml + '<span class="jt-bool">' + data + '</span></div>';
        if (typeof data === 'number')  return '<div>' + keyHtml + '<span class="jt-num">' + data + '</span></div>';
        if (typeof data === 'string')  return '<div>' + keyHtml + '<span class="jt-str">"' + esc(data) + '"</span></div>';
        var isArr = Array.isArray(data);
        var count = isArr ? data.length : Object.keys(data).length;
        var id = 'jtn' + (++_treeId);
        var ob = isArr ? '[' : '{', cb = isArr ? ']' : '}';
        var children = '';
        if (isArr) { for (var i = 0; i < data.length; i++) children += buildTreeNode(data[i], i, false); }
        else { var ks = Object.keys(data); for (var j = 0; j < ks.length; j++) children += buildTreeNode(data[ks[j]], ks[j], false); }
        return '<div>'
            + '<span class="jt-toggle" onclick="JT_toggle(\'' + id + '\')">' + keyHtml
            + '<span style="color:var(--color-text-muted)">' + ob + '</span> '
            + '<span id="' + id + '_p" style="font-size:11px;color:var(--color-text-muted)' + (!expanded ? '' : ';display:none') + '">' + count + (isArr ? ' item' : ' key') + (count !== 1 ? 's' : '') + ' &hellip;</span></span>'
            + '<div id="' + id + '" style="' + (expanded ? '' : 'display:none') + ';padding-left:18px;border-left:2px solid var(--color-border)">' + children + '</div>'
            + '<span style="color:var(--color-text-muted)">' + cb + '</span>'
            + '</div>';
    }

    function viewerAll(expand) {
        document.querySelectorAll('#view-tree [id^="jtn"]').forEach(function(node) {
            if (node.id.indexOf('_') !== -1) return;
            node.style.display = expand ? '' : 'none';
            var pr = el(node.id + '_p'); if (pr) pr.style.display = expand ? 'none' : '';
        });
    }

    /* ── 7. Pretty Print ─────────────────────────────────────────────── */
    function runPrettyPrint() {
        var raw = gv('pp-in');
        var out = el('pp-out'); if (!out) return;
        if (!raw) { out.innerHTML = ''; st('pp-st', '', ''); return; }
        var r = tryJSON(raw);
        if (!r.ok) { out.innerHTML = ''; st('pp-st', 'Error: ' + r.err, 'error'); return; }
        var pretty = JSON.stringify(r.data, null, 2);
        out.innerHTML = syntaxHL(pretty);
        st('pp-st', bytes(pretty), 'ok');
    }

    function syntaxHL(json) {
        return json.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function(m) {
                if (/^"/.test(m)) return /:$/.test(m) ? '<span class="jt-hl-key">' + m + '</span>' : '<span class="jt-hl-str">' + m + '</span>';
                if (/true|false/.test(m)) return '<span class="jt-hl-bool">' + m + '</span>';
                if (/null/.test(m)) return '<span class="jt-hl-null">' + m + '</span>';
                return '<span class="jt-hl-num">' + m + '</span>';
            });
    }

    /* ── 8. Escape ───────────────────────────────────────────────────── */
    function runEscape() {
        var raw = gv('esc-in');
        if (!raw) { sv('esc-out', ''); meta('esc-meta', ''); return; }
        var out = raw.replace(/\\/g,'\\\\').replace(/"/g,'\\"')
            .replace(/\n/g,'\\n').replace(/\r/g,'\\r')
            .replace(/\t/g,'\\t').replace(/\f/g,'\\f').replace(/\b/g,'\\b')
            .replace(/[\u0000-\u001f\u007f-\u009f]/g, function(c) { return '\\u' + ('0000' + c.charCodeAt(0).toString(16)).slice(-4); });
        sv('esc-out', out);
        meta('esc-meta', out.length + ' chars');
    }

    /* ── 9. Unescape ─────────────────────────────────────────────────── */
    function runUnescape() {
        var raw = gv('unesc-in');
        if (!raw) { sv('unesc-out', ''); meta('unesc-meta', ''); return; }
        try {
            var out = JSON.parse('"' + raw + '"');
            sv('unesc-out', out); meta('unesc-meta', out.length + ' chars');
        } catch (e) {
            try {
                var out2 = JSON.parse(raw);
                var s = typeof out2 === 'string' ? out2 : JSON.stringify(out2, null, 2);
                sv('unesc-out', s); meta('unesc-meta', s.length + ' chars');
            } catch (e2) { st('unesc-st', 'Error: ' + e.message, 'error'); }
        }
    }

    /* ── 10. JSON → CSV ──────────────────────────────────────────────── */
    function runJson2Csv() {
        var raw = gv('j2c-in');
        if (!raw) { sv('j2c-out', ''); st('j2c-st', '', ''); meta('j2c-meta', ''); return; }
        var r = tryJSON(raw);
        if (!r.ok) { st('j2c-st', 'Error: ' + r.err, 'error'); return; }
        if (!Array.isArray(r.data)) { st('j2c-st', 'Error: Input must be a JSON array of objects.', 'error'); return; }
        if (!r.data.length) { st('j2c-st', 'Error: Array is empty.', 'error'); return; }
        var keys = [], seen = {};
        r.data.forEach(function(row) { if (row && typeof row === 'object' && !Array.isArray(row)) Object.keys(row).forEach(function(k) { if (!seen[k]) { seen[k]=1; keys.push(k); } }); });
        function ce(v) { var s = v == null ? '' : typeof v === 'object' ? JSON.stringify(v) : String(v); return (s.indexOf(',')!==-1||s.indexOf('"')!==-1||s.indexOf('\n')!==-1) ? '"'+s.replace(/"/g,'""')+'"' : s; }
        var rows = [keys.map(ce).join(',')];
        r.data.forEach(function(row) { rows.push(keys.map(function(k) { return ce(row && typeof row==='object' ? row[k] : row); }).join(',')); });
        var out = rows.join('\n');
        sv('j2c-out', out);
        st('j2c-st', r.data.length + ' rows \xb7 ' + keys.length + ' cols', 'ok');
        meta('j2c-meta', bytes(out));
    }

    /* ── 11. CSV → JSON ──────────────────────────────────────────────── */
    function runCsv2Json() {
        var raw = gv('c2j-in');
        if (!raw) { sv('c2j-out', ''); st('c2j-st', '', ''); meta('c2j-meta', ''); return; }
        try {
            var lines = raw.replace(/\r\n/g,'\n').replace(/\r/g,'\n').split('\n').filter(function(l) { return l.trim(); });
            if (lines.length < 2) throw new Error('Need at least a header row and one data row.');
            function parseLine(line) {
                var res=[], cur='', inQ=false;
                for (var i=0;i<line.length;i++) {
                    if (line[i]==='"') { if (inQ && line[i+1]==='"') { cur+='"'; i++; } else inQ=!inQ; }
                    else if (line[i]===',' && !inQ) { res.push(cur); cur=''; }
                    else cur+=line[i];
                }
                res.push(cur); return res;
            }
            var headers = parseLine(lines[0]);
            var arr = lines.slice(1).map(function(line) {
                var vals=parseLine(line), obj={};
                headers.forEach(function(h,i) { var v=vals[i]!==undefined?vals[i]:''; obj[h]=v==='true'?true:v==='false'?false:(!isNaN(v)&&v!==''?Number(v):v); });
                return obj;
            });
            var out = JSON.stringify(arr, null, 2);
            sv('c2j-out', out);
            st('c2j-st', arr.length + ' records \xb7 ' + headers.length + ' fields', 'ok');
            meta('c2j-meta', bytes(out));
        } catch (e) { sv('c2j-out', ''); st('c2j-st', 'Error: ' + e.message, 'error'); }
    }

    /* ── 12. JSON → XML ──────────────────────────────────────────────── */
    function runJson2Xml() {
        var raw = gv('j2x-in');
        if (!raw) { sv('j2x-out', ''); st('j2x-st', '', ''); meta('j2x-meta', ''); return; }
        var r = tryJSON(raw);
        if (!r.ok) { st('j2x-st', 'Error: ' + r.err, 'error'); return; }
        try {
            function xesc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
            function toXml(obj, tag, depth) {
                var pad=''; for (var p=0;p<depth;p++) pad+='  ';
                var t=(tag||'item').replace(/[^a-zA-Z0-9_\-\.]/g,'_').replace(/^(\d)/,'_$1')||'item';
                if (obj===null) return pad+'<'+t+' xsi:nil="true"/>';
                if (typeof obj!=='object') return pad+'<'+t+'>'+xesc(String(obj))+'</'+t+'>';
                if (Array.isArray(obj)) { var parts=[]; for (var i=0;i<obj.length;i++) parts.push(toXml(obj[i],t,depth)); return parts.join('\n'); }
                var kids=[],ks=Object.keys(obj);
                for (var j=0;j<ks.length;j++) kids.push(toXml(obj[ks[j]],ks[j],depth+1));
                if (!kids.length) return pad+'<'+t+'/>';
                return pad+'<'+t+'>\n'+kids.join('\n')+'\n'+pad+'</'+t+'>';
            }
            var decl='<?xml version="1.0" encoding="UTF-8"?>', xml;
            if (typeof r.data==='object' && r.data!==null && !Array.isArray(r.data)) {
                var ks=Object.keys(r.data);
                xml = ks.length===1 ? decl+'\n'+toXml(r.data[ks[0]],ks[0],0) : decl+'\n<root>\n'+ks.map(function(k){return toXml(r.data[k],k,1);}).join('\n')+'\n</root>';
            } else { xml = decl+'\n<root>\n'+toXml(r.data,'item',1)+'\n</root>'; }
            sv('j2x-out', xml);
            st('j2x-st', bytes(xml), 'ok');
            meta('j2x-meta', xml.split('\n').length + ' lines');
        } catch (e) { st('j2x-st', 'Error: ' + e.message, 'error'); }
    }

    /* ── 13. XML → JSON ──────────────────────────────────────────────── */
    function runXml2Json() {
        var raw = gv('x2j-in');
        if (!raw) { sv('x2j-out', ''); st('x2j-st', '', ''); meta('x2j-meta', ''); return; }
        try {
            var parser = new DOMParser();
            var doc = parser.parseFromString(raw, 'application/xml');
            var err = doc.querySelector('parsererror');
            if (err) throw new Error('XML parse error: ' + err.textContent.substring(0,120));
            function nodeToObj(node) {
                function coerce(t) { return isNaN(t)?(t==='true'?true:t==='false'?false:t):Number(t); }
                if (node.nodeType!==1) return undefined;
                var obj={};
                if (node.attributes && node.attributes.length) { obj['@']={};for(var a=0;a<node.attributes.length;a++) obj['@'][node.attributes[a].name]=node.attributes[a].value; }
                var childEls = Array.prototype.slice.call(node.childNodes).filter(function(n){return n.nodeType===1;});
                if (!childEls.length) { var txt=node.textContent.trim(); if(!txt) return Object.keys(obj).length?obj:null; if(Object.keys(obj).length){obj['#text']=txt;return obj;} return coerce(txt); }
                var tagC={};
                childEls.forEach(function(n){tagC[n.tagName]=(tagC[n.tagName]||0)+1;});
                childEls.forEach(function(n){var v=nodeToObj(n),k=n.tagName;if(tagC[k]>1){if(!Array.isArray(obj[k]))obj[k]=obj[k]?[obj[k]]:[];obj[k].push(v);}else obj[k]=v;});
                return obj;
            }
            var root=doc.documentElement, result={}; result[root.tagName]=nodeToObj(root);
            var out=JSON.stringify(result,null,2);
            sv('x2j-out', out);
            st('x2j-st', bytes(out), 'ok');
            meta('x2j-meta', out.split('\n').length + ' lines');
        } catch (e) { st('x2j-st', 'Error: ' + e.message, 'error'); }
    }

    /* ── 14. JSON → YAML ─────────────────────────────────────────────── */
    function runJson2Yaml() {
        var raw = gv('j2y-in');
        if (!raw) { sv('j2y-out', ''); st('j2y-st', '', ''); meta('j2y-meta', ''); return; }
        var r = tryJSON(raw);
        if (!r.ok) { st('j2y-st', 'Error: ' + r.err, 'error'); return; }
        try {
            var out = jsyaml.dump(r.data, { indent:2, lineWidth:-1, noRefs:true });
            sv('j2y-out', out);
            st('j2y-st', bytes(out), 'ok');
            meta('j2y-meta', out.split('\n').length + ' lines');
        } catch (e) { st('j2y-st', 'Error: ' + e.message, 'error'); }
    }

    /* ── 15. YAML → JSON ─────────────────────────────────────────────── */
    function runYaml2Json() {
        var raw = gv('y2j-in');
        if (!raw) { sv('y2j-out', ''); st('y2j-st', '', ''); meta('y2j-meta', ''); return; }
        try {
            var out = JSON.stringify(jsyaml.load(raw), null, 2);
            sv('y2j-out', out);
            st('y2j-st', bytes(out), 'ok');
            meta('y2j-meta', out.split('\n').length + ' lines');
        } catch (e) { st('y2j-st', 'Error: ' + e.message, 'error'); }
    }

    /* ── 16. JSON Diff ───────────────────────────────────────────────── */
    function runDiff() {
        var rawA = gv('diff-a'), rawB = gv('diff-b');
        var out = el('diff-out'); if (!out) return;
        if (!rawA && !rawB) { out.innerHTML = '<div class="jt-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Paste JSON into both panes to compare</div>'; st('diff-st','',''); return; }
        var rA = tryJSON(rawA), rB = tryJSON(rawB);
        if (!rA.ok) { out.innerHTML = '<div class="jt-diff-err">JSON A: ' + esc(rA.err) + '</div>'; return; }
        if (!rB.ok) { out.innerHTML = '<div class="jt-diff-err">JSON B: ' + esc(rB.err) + '</div>'; return; }
        var changes = [];
        diffObjects(rA.data, rB.data, '', changes);
        if (!changes.length) {
            out.innerHTML = '<div class="jt-diff-equal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><polyline points="20 6 9 17 4 12"/></svg> Objects are identical</div>';
            st('diff-st', 'Identical', 'ok'); return;
        }
        var added=0, removed=0, changed=0;
        var html = '<div class="jt-diff-table">'
            + '<div class="jt-diff-head"><span>Path</span><span>Change</span><span>Old value</span><span>New value</span></div>';
        changes.forEach(function(c) {
            if (c.type==='added') added++;
            else if (c.type==='removed') removed++;
            else changed++;
            html += '<div class="jt-diff-row jt-diff-' + c.type + '">'
                + '<span class="jt-diff-path">' + esc(c.path || '(root)') + '</span>'
                + '<span class="jt-diff-badge jt-diff-badge-' + c.type + '">' + c.type + '</span>'
                + '<span class="jt-diff-val">' + (c.type==='added' ? '<em>—</em>' : renderDiffVal(c.oldVal)) + '</span>'
                + '<span class="jt-diff-val">' + (c.type==='removed' ? '<em>—</em>' : renderDiffVal(c.newVal)) + '</span>'
                + '</div>';
        });
        html += '</div>';
        out.innerHTML = html;
        var parts = [];
        if (added)   parts.push('<span style="color:#16a34a">+' + added + ' added</span>');
        if (removed) parts.push('<span style="color:#dc2626">\u2212' + removed + ' removed</span>');
        if (changed) parts.push('<span style="color:#d97706">\u25b2' + changed + ' changed</span>');
        st('diff-st', parts.join(' &nbsp; '), '');
    }

    function renderDiffVal(v) {
        if (v === undefined) return '<em>\u2014</em>';
        if (v === null) return '<em class="jt-null">null</em>';
        if (typeof v === 'object') return '<em>' + (Array.isArray(v) ? '[array]' : '{object}') + '</em>';
        return esc(JSON.stringify(v));
    }

    function diffObjects(a, b, path, changes) {
        var typeA = a === null ? 'null' : Array.isArray(a) ? 'array' : typeof a;
        var typeB = b === null ? 'null' : Array.isArray(b) ? 'array' : typeof b;
        if (typeA !== typeB || typeA === 'number' || typeA === 'string' || typeA === 'boolean' || typeA === 'null') {
            if (JSON.stringify(a) !== JSON.stringify(b)) changes.push({ path:path, type:'changed', oldVal:a, newVal:b });
            return;
        }
        if (typeA === 'object') {
            var allKeys = Object.keys(Object.assign({}, a, b));
            allKeys.forEach(function(k) {
                var p = path ? path + '.' + k : k;
                if (!(k in a)) changes.push({ path:p, type:'added', oldVal:undefined, newVal:b[k] });
                else if (!(k in b)) changes.push({ path:p, type:'removed', oldVal:a[k], newVal:undefined });
                else diffObjects(a[k], b[k], p, changes);
            });
        } else if (typeA === 'array') {
            var maxLen = Math.max(a.length, b.length);
            for (var i = 0; i < maxLen; i++) {
                var p2 = path + '[' + i + ']';
                if (i >= a.length) changes.push({ path:p2, type:'added', oldVal:undefined, newVal:b[i] });
                else if (i >= b.length) changes.push({ path:p2, type:'removed', oldVal:a[i], newVal:undefined });
                else diffObjects(a[i], b[i], p2, changes);
            }
        }
    }

    /* ── 17. Sort Keys ───────────────────────────────────────────────── */
    function runSortKeys() {
        var raw = gv('sk-in');
        if (!raw) { sv('sk-out', ''); st('sk-st', '', ''); meta('sk-meta', ''); return; }
        var r = tryJSON(raw);
        if (!r.ok) { sv('sk-out', ''); st('sk-st', 'Error: ' + r.err, 'error'); return; }
        function sortObj(v) {
            if (v === null || typeof v !== 'object') return v;
            if (Array.isArray(v)) return v.map(sortObj);
            return Object.keys(v).sort().reduce(function(acc, k) { acc[k] = sortObj(v[k]); return acc; }, {});
        }
        var out = JSON.stringify(sortObj(r.data), null, 2);
        sv('sk-out', out);
        st('sk-st', bytes(out), 'ok');
        meta('sk-meta', out.split('\n').length + ' lines');
    }

    /* ── 18. Flatten / Unflatten ─────────────────────────────────────── */
    function runFlatten() {
        var raw = gv('fl-in');
        var mode = el('fl-mode') ? el('fl-mode').value : 'flatten';
        var sep  = (el('fl-sep') ? el('fl-sep').value : null) || '.';
        if (!raw) { sv('fl-out', ''); st('fl-st', '', ''); meta('fl-meta', ''); return; }
        var r = tryJSON(raw);
        if (!r.ok) { sv('fl-out', ''); st('fl-st', 'Error: ' + r.err, 'error'); return; }
        try {
            var out;
            if (mode === 'flatten') {
                var flat = {};
                function flattenObj(obj, prefix) {
                    if (obj === null || typeof obj !== 'object') { flat[prefix] = obj; return; }
                    if (Array.isArray(obj)) { obj.forEach(function(v,i) { flattenObj(v, prefix ? prefix+sep+i : String(i)); }); return; }
                    var keys = Object.keys(obj);
                    if (!keys.length && prefix) { flat[prefix] = {}; return; }
                    keys.forEach(function(k) { flattenObj(obj[k], prefix ? prefix+sep+k : k); });
                }
                flattenObj(r.data, '');
                out = JSON.stringify(flat, null, 2);
            } else {
                var unflat = {};
                Object.keys(r.data).forEach(function(key) {
                    var parts = key.split(sep), cur = unflat;
                    for (var i = 0; i < parts.length - 1; i++) {
                        var nextIsIndex = !isNaN(parseInt(parts[i+1]));
                        if (cur[parts[i]] === undefined) cur[parts[i]] = nextIsIndex ? [] : {};
                        cur = cur[parts[i]];
                    }
                    cur[parts[parts.length-1]] = r.data[key];
                });
                out = JSON.stringify(unflat, null, 2);
            }
            sv('fl-out', out);
            st('fl-st', (mode==='flatten'?'Flattened':'Unflattened') + ' \xb7 ' + bytes(out), 'ok');
            meta('fl-meta', out.split('\n').length + ' lines');
        } catch (e) { st('fl-st', 'Error: ' + e.message, 'error'); }
    }

    /* ── 19. JSONPath Query ──────────────────────────────────────────── */
    function runJsonPath() {
        var raw   = gv('jp-in');
        var query = gv('jp-query');
        var out   = el('jp-out-pre'); if (!out) return;
        if (!raw)   { out.textContent = ''; st('jp-st', '', ''); return; }
        if (!query) { out.textContent = ''; st('jp-st', 'Enter a JSONPath query above', ''); return; }
        var r = tryJSON(raw);
        if (!r.ok) { out.textContent = 'JSON Error: ' + r.err; st('jp-st', 'Invalid JSON', 'error'); return; }
        try {
            var results = jsonPathQuery(r.data, query);
            if (!results.length) { out.innerHTML = '<em style="color:var(--color-text-muted)">No matches found</em>'; st('jp-st', '0 matches', ''); return; }
            var pretty = results.length === 1 ? JSON.stringify(results[0], null, 2) : JSON.stringify(results, null, 2);
            out.innerHTML = syntaxHL(pretty);
            st('jp-st', results.length + ' match' + (results.length !== 1 ? 'es' : ''), 'ok');
        } catch (e) { out.textContent = 'Query Error: ' + e.message; st('jp-st', 'Error', 'error'); }
    }

    /* Minimal JSONPath engine supporting $, ., [], *, [*], recursive .. */
    function jsonPathQuery(data, path) {
        var results = [];
        function collect(node, tokens) {
            if (!tokens.length) { results.push(node); return; }
            var t = tokens[0], rest = tokens.slice(1);
            if (t === '$') { collect(node, rest); return; }
            if (t === '*') {
                if (Array.isArray(node)) { node.forEach(function(v) { collect(v, rest); }); }
                else if (node && typeof node === 'object') { Object.keys(node).forEach(function(k) { collect(node[k], rest); }); }
                return;
            }
            if (t === '..') {
                collect(node, rest);
                if (node && typeof node === 'object') {
                    (Array.isArray(node) ? node : Object.values(node)).forEach(function(v) { if (v && typeof v==='object') collect(v, [t].concat(rest)); });
                }
                return;
            }
            /* array index */
            if (/^\d+$/.test(t) && Array.isArray(node) && node[parseInt(t)] !== undefined) { collect(node[parseInt(t)], rest); return; }
            /* slice [start:end] */
            if (/^(\d*):(\d*)$/.test(t) && Array.isArray(node)) {
                var m = t.match(/^(\d*):(\d*)$/);
                var start = m[1] !== '' ? parseInt(m[1]) : 0;
                var end   = m[2] !== '' ? parseInt(m[2]) : node.length;
                node.slice(start, end).forEach(function(v) { collect(v, rest); }); return;
            }
            /* object key */
            if (node && typeof node === 'object' && !Array.isArray(node) && node[t] !== undefined) { collect(node[t], rest); return; }
        }
        /* tokenise: $.store.book[*].author  or  $..author  or  $.items[0] */
        var cleaned = path.trim().replace(/\[(\d+)\]/g,'.$1').replace(/\[\*\]/g,'.*').replace(/\['([^']+)'\]/g,'.$1').replace(/\["([^"]+)"\]/g,'.$1');
        var tokens = cleaned.split('.').filter(function(t) { return t !== ''; });
        if (tokens[0] !== '$') tokens.unshift('$');
        collect(data, tokens);
        return results;
    }

    /* ── Init ────────────────────────────────────────────────────────── */
    switchTool('formatter');

    /* Public API */
    return {
        switchTool: switchTool, clearAll: clearAll,
        clrValidator: clrValidator, clrParser: clrParser, clrViewer: clrViewer,
        cp: cp, cpPane: cpPane, dl: dl,
        pasteInto: pasteInto, openFile: openFile,
        loadSample: loadSample,
        runFormatter: runFormatter, runBeautifier: runBeautifier, runMinifier: runMinifier,
        runValidator: runValidator, runParser: runParser, runViewer: runViewer,
        runPrettyPrint: runPrettyPrint, runEscape: runEscape, runUnescape: runUnescape,
        runJson2Csv: runJson2Csv, runCsv2Json: runCsv2Json,
        runJson2Xml: runJson2Xml, runXml2Json: runXml2Json,
        runJson2Yaml: runJson2Yaml, runYaml2Json: runYaml2Json,
        runDiff: runDiff, runSortKeys: runSortKeys, runFlatten: runFlatten, runJsonPath: runJsonPath,
        viewerAll: viewerAll,
    };
})();

/* Deep-link: ?tool=<id> activates that tool on page load */
document.addEventListener('DOMContentLoaded', function() {
    var t = new URLSearchParams(location.search).get('tool');
    if (t) JT.switchTool(t);
});

/* Global toggle for interactive tree nodes */
function JT_toggle(id) {
    var e = document.getElementById(id);
    var p = document.getElementById(id + '_p');
    if (!e) return;
    var open = e.style.display === 'none';
    e.style.display = open ? '' : 'none';
    if (p) p.style.display = open ? 'none' : '';
}
