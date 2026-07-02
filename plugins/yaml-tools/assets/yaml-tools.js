/* =====================================================================
   YAML Tools — AWAN Platform Plugin v1.0.0
   All processing is 100% client-side. Zero server communication.
   Requires js-yaml (bundled separately).
   ===================================================================== */
var YT = (function () {
    'use strict';

    var currentTool = 'formatter';

    /* ── Sample data ─────────────────────────────────────────────────── */
    var SAMPLE_YAML = [
        '# Application configuration',
        'app:',
        '  name: My Application',
        '  version: 2.1.0',
        '  debug: false',
        '',
        'database:',
        '  host: localhost',
        '  port: 5432',
        '  name: myapp_db',
        '  pool:',
        '    min: 2',
        '    max: 10',
        '',
        'server:',
        '  host: 0.0.0.0',
        '  port: 8080',
        '  cors:',
        '    enabled: true',
        '    origins:',
        '      - https://example.com',
        '      - https://api.example.com',
        '',
        'logging:',
        '  level: info',
        '  format: json',
        '  output: stdout'
    ].join('\n');

    var SAMPLE_YAML2 = [
        '# Application configuration',
        'app:',
        '  name: My Application',
        '  version: 2.2.0',
        '  debug: true',
        '',
        'database:',
        '  host: db.prod.example.com',
        '  port: 5432',
        '  name: myapp_db',
        '  pool:',
        '    min: 5',
        '    max: 20',
        '',
        'server:',
        '  host: 0.0.0.0',
        '  port: 443',
        '  cors:',
        '    enabled: true',
        '    origins:',
        '      - https://example.com',
        '',
        'cache:',
        '  ttl: 3600',
        '  driver: redis'
    ].join('\n');

    var SAMPLE_CSV = 'name,version,debug,port\nMy Application,2.1.0,false,8080\nOther App,1.0.0,true,3000';

    var SAMPLE_JSON = '{\n  "app": {\n    "name": "My Application",\n    "version": "2.1.0",\n    "debug": false\n  },\n  "database": {\n    "host": "localhost",\n    "port": 5432\n  },\n  "features": ["auth", "logging", "cache"]\n}';

    var SAMPLE_ESC  = 'Hello: World\nThis has a "quoted" string & special chars like > and <\nMulti-line\n  content here';
    var SAMPLE_UNESC = '"Hello: World\\nThis has a \\"quoted\\" string & special chars like > and <\\nMulti-line\\n  content here"';

    /* ── DOM helpers ─────────────────────────────────────────────────── */
    function el(id)    { return document.getElementById(id); }
    function gv(id)    { var e = el(id); return e ? e.value : ''; }
    function gvt(id)   { var e = el(id); return e ? e.value.trim() : ''; }
    function sv(id, v) { var e = el(id); if (e) e.value = v; }

    function st(id, msg, type) {
        var e = el(id); if (!e) return;
        if (!msg) { e.innerHTML = ''; return; }
        if (type === 'ok' || type === 'error') {
            e.innerHTML = '<span class="yt-status-chip ' + type + '"><span class="yt-dot"></span>' + msg + '</span>';
        } else {
            e.innerHTML = '<span style="color:var(--color-text-muted);font-size:12px">' + msg + '</span>';
        }
    }

    function meta(id, text) { var e = el(id); if (e) e.textContent = text; }

    function bytes(s) {
        var b = new Blob([s]).size;
        return b >= 1024 * 1024 ? (b / 1024 / 1024).toFixed(1) + ' MB'
             : b >= 1024 ? (b / 1024).toFixed(1) + ' KB' : b + ' B';
    }

    function htmlEsc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── YAML helpers ────────────────────────────────────────────────── */
    function tryYAML(s) {
        if (!s || !s.trim()) return { ok: false, err: 'Input is empty.' };
        try {
            var docs = jsyaml.loadAll(s);
            var data = docs.length === 1 ? docs[0] : docs;
            return { ok: true, data: data };
        } catch (e) {
            return { ok: false, err: e.message || String(e) };
        }
    }

    /* Serialize JS value → YAML string with given indent */
    function toYAML(data, indent) {
        indent = indent || 2;
        return jsyaml.dump(data, { indent: indent, lineWidth: -1, noRefs: true });
    }

    /* Escape a string for safe embedding as a YAML scalar */
    function yamlEscapeStr(s) {
        /* Double-quote style: escape backslash and double-quote */
        return '"' + String(s)
            .replace(/\\/g, '\\\\')
            .replace(/"/g, '\\"')
            .replace(/\n/g, '\\n')
            .replace(/\r/g, '\\r')
            .replace(/\t/g, '\\t')
            + '"';
    }

    /* Unescape a quoted YAML scalar */
    function yamlUnescapeStr(s) {
        s = String(s).trim();
        /* Strip surrounding quotes */
        if ((s.charAt(0) === '"' && s.charAt(s.length - 1) === '"') ||
            (s.charAt(0) === "'" && s.charAt(s.length - 1) === "'")) {
            s = s.slice(1, -1);
        }
        return s
            .replace(/\\n/g, '\n')
            .replace(/\\r/g, '\r')
            .replace(/\\t/g, '\t')
            .replace(/\\"/g, '"')
            .replace(/\\'/g, "'")
            .replace(/\\\\/g, '\\');
    }

    /* Recursively sort object keys */
    function sortKeys(obj) {
        if (Array.isArray(obj)) return obj.map(sortKeys);
        if (obj !== null && typeof obj === 'object') {
            var sorted = {};
            Object.keys(obj).sort().forEach(function (k) {
                sorted[k] = sortKeys(obj[k]);
            });
            return sorted;
        }
        return obj;
    }

    /* Flatten nested object to dot-notation keys */
    function flattenObj(obj, prefix, sep, out) {
        prefix = prefix || '';
        sep = sep || '.';
        out = out || {};
        if (Array.isArray(obj)) {
            obj.forEach(function (v, i) {
                flattenObj(v, prefix + '[' + i + ']', sep, out);
            });
        } else if (obj !== null && typeof obj === 'object') {
            Object.keys(obj).forEach(function (k) {
                flattenObj(obj[k], prefix ? prefix + sep + k : k, sep, out);
            });
        } else {
            out[prefix] = obj;
        }
        return out;
    }

    /* Unflatten dot-notation map back to nested object */
    function unflattenObj(flat, sep) {
        sep = sep || '.';
        var out = {};
        Object.keys(flat).forEach(function (key) {
            var parts = key.split(sep);
            var cur = out;
            for (var i = 0; i < parts.length - 1; i++) {
                if (cur[parts[i]] === undefined || typeof cur[parts[i]] !== 'object') {
                    cur[parts[i]] = {};
                }
                cur = cur[parts[i]];
            }
            cur[parts[parts.length - 1]] = flat[key];
        });
        return out;
    }

    /* Flatten obj recursively for diff (returns path→value map) */
    function flattenForDiff(obj, path, map) {
        path = path || '';
        map  = map  || {};
        if (Array.isArray(obj)) {
            obj.forEach(function (v, i) {
                flattenForDiff(v, path + '[' + i + ']', map);
            });
        } else if (obj !== null && typeof obj === 'object') {
            Object.keys(obj).forEach(function (k) {
                flattenForDiff(obj[k], path ? path + '.' + k : k, map);
            });
        } else {
            map[path] = (obj === null ? 'null' : String(obj));
        }
        return map;
    }

    /* ── Navigation ──────────────────────────────────────────────────── */
    function switchTool(id) {
        currentTool = id;
        document.querySelectorAll('.yt-panel').forEach(function (p) { p.style.display = 'none'; });
        var panel = el('yt-' + id); if (panel) panel.style.display = '';
        document.querySelectorAll('.yt-nav').forEach(function (b) {
            b.classList.toggle('active', b.dataset.tool === id);
        });
        var active = document.querySelector('.yt-nav.active');
        if (active) active.scrollIntoView({ inline: 'nearest', block: 'nearest' });
    }

    /* ── Clear ───────────────────────────────────────────────────────── */
    function clearAll() {
        var inMap = {
            formatter:'fmt-in', validator:'val-in', viewer:'view-in',
            sortkeys:'sk-in', flatten:'fl-in',
            escape:'esc-in', unescape:'unesc-in',
            yaml2json:'y2j-in', json2yaml:'j2y-in',
            yaml2csv:'y2c-in', csv2yaml:'c2y-in',
            diff:'diff-a'
        };
        var id = inMap[currentTool]; if (id) sv(id, '');
        if (currentTool === 'diff') sv('diff-b', '');
    }

    /* ── Paste ───────────────────────────────────────────────────────── */
    function pasteInto(targetId, btn) {
        if (!navigator.clipboard || !navigator.clipboard.readText) {
            alert('Clipboard read is not supported. Use Ctrl+V inside the textarea instead.');
            return;
        }
        navigator.clipboard.readText().then(function (text) {
            sv(targetId, text);
            var autoMap = {
                'fmt-in': runFormatter, 'val-in': runValidator, 'view-in': runViewer,
                'sk-in': runSortKeys, 'fl-in': runFlatten,
                'esc-in': runEscape, 'unesc-in': runUnescape,
                'y2j-in': runYaml2Json, 'j2y-in': runJson2Yaml,
                'y2c-in': runYaml2Csv, 'c2y-in': runCsv2Yaml,
                'diff-a': runDiff, 'diff-b': runDiff
            };
            if (autoMap[targetId]) autoMap[targetId]();
            if (btn) {
                var orig = btn.innerHTML;
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Pasted!';
                btn.classList.add('copied');
                setTimeout(function () { btn.innerHTML = orig; btn.classList.remove('copied'); }, 1400);
            }
        }).catch(function () { alert('Could not read clipboard. Please paste manually with Ctrl+V.'); });
    }

    /* ── Open file ───────────────────────────────────────────────────── */
    function openFile(targetId, accept) {
        var inp = document.createElement('input');
        inp.type = 'file';
        inp.accept = accept || '.yaml,.yml,.txt';
        inp.onchange = function () {
            var file = inp.files[0]; if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                sv(targetId, e.target.result);
                var autoMap = {
                    'fmt-in': runFormatter, 'val-in': runValidator, 'view-in': runViewer,
                    'sk-in': runSortKeys, 'fl-in': runFlatten,
                    'y2j-in': runYaml2Json, 'j2y-in': runJson2Yaml,
                    'y2c-in': runYaml2Csv, 'c2y-in': runCsv2Yaml
                };
                if (autoMap[targetId]) autoMap[targetId]();
            };
            reader.readAsText(file);
        };
        inp.click();
    }

    /* ── Copy pane ───────────────────────────────────────────────────── */
    function cpPane(id, btn) {
        var e = el(id); if (!e || !e.value) return;
        navigator.clipboard.writeText(e.value).then(function () {
            if (btn) {
                btn.classList.add('copied');
                var orig = btn.innerHTML;
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><polyline points="20 6 9 17 4 12"/></svg>Copied!';
                setTimeout(function () { btn.innerHTML = orig; btn.classList.remove('copied'); }, 1600);
            }
        });
    }

    /* ── Download ────────────────────────────────────────────────────── */
    function dl(id, fname, mime) {
        var v = gvt(id); if (!v) return;
        var a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([v], { type: mime }));
        a.download = fname; a.click();
    }

    /* ── Load sample ─────────────────────────────────────────────────── */
    function loadSample() {
        var map = {
            formatter:'fmt-in', validator:'val-in', viewer:'view-in',
            sortkeys:'sk-in', flatten:'fl-in',
            escape:'esc-in', unescape:'unesc-in',
            yaml2json:'y2j-in', json2yaml:'j2y-in',
            yaml2csv:'y2c-in', csv2yaml:'c2y-in',
            diff:'diff-a'
        };
        var smap = {
            json2yaml: SAMPLE_JSON, csv2yaml: SAMPLE_CSV,
            escape: SAMPLE_ESC, unescape: SAMPLE_UNESC, diff: SAMPLE_YAML
        };
        var id = map[currentTool]; if (!id) return;
        sv(id, smap[currentTool] || SAMPLE_YAML);
        if (currentTool === 'diff') sv('diff-b', SAMPLE_YAML2);
        var auto = {
            formatter: runFormatter, validator: runValidator, viewer: runViewer,
            sortkeys: runSortKeys, flatten: runFlatten,
            escape: runEscape, unescape: runUnescape,
            yaml2json: runYaml2Json, json2yaml: runJson2Yaml,
            yaml2csv: runYaml2Csv, csv2yaml: runCsv2Yaml,
            diff: runDiff
        };
        if (auto[currentTool]) auto[currentTool]();
    }

    /* ══════════════════════════════════════════════════════════════════
       TOOL IMPLEMENTATIONS
       ══════════════════════════════════════════════════════════════════ */

    /* ── 1. Formatter ────────────────────────────────────────────────── */
    function runFormatter() {
        var raw = gv('fmt-in');
        if (!raw.trim()) { sv('fmt-out', ''); st('fmt-st', '', ''); meta('fmt-meta', ''); return; }
        var indEl = el('fmt-indent');
        var ind = indEl ? parseInt(indEl.value, 10) : 2;
        var r = tryYAML(raw);
        if (!r.ok) { sv('fmt-out', ''); st('fmt-st', 'Error: ' + r.err.split('\n')[0].substring(0, 120), 'error'); meta('fmt-meta', ''); return; }
        try {
            var out = toYAML(r.data, ind);
            sv('fmt-out', out);
            st('fmt-st', bytes(out), 'ok');
            meta('fmt-meta', out.split('\n').length + ' lines');
        } catch (e) {
            st('fmt-st', 'Error: ' + e.message, 'error');
        }
    }

    /* ── 2. Validator ────────────────────────────────────────────────── */
    function runValidator() {
        var raw = gv('val-in');
        var res = el('val-res'); if (!res) return;
        if (!raw.trim()) { res.innerHTML = ''; res.style.display = 'none'; return; }
        var r = tryYAML(raw);
        res.style.display = 'block';
        if (r.ok) {
            var data = r.data;
            var keyCount = 0;
            (function countKeys(obj) {
                if (Array.isArray(obj)) { obj.forEach(countKeys); }
                else if (obj !== null && typeof obj === 'object') {
                    keyCount += Object.keys(obj).length;
                    Object.values(obj).forEach(countKeys);
                }
            })(data);
            var topType = Array.isArray(data) ? 'Array (' + data.length + ' items)' : (typeof data === 'object' && data !== null ? 'Object' : typeof data);
            res.style.background = 'rgba(22,163,74,.08)'; res.style.borderColor = '#22c55e';
            res.innerHTML = '<strong style="color:#16a34a">\u2713 Valid YAML</strong><br>'
                + '<span style="font-size:12px;color:var(--color-text-muted)">Top-level: <strong>' + htmlEsc(topType) + '</strong>'
                + ' &nbsp;&middot;&nbsp; Keys: <strong>' + keyCount + '</strong>'
                + ' &nbsp;&middot;&nbsp; Size: <strong>' + bytes(raw) + '</strong></span>';
        } else {
            var errMsg = r.err.split('\n')[0].substring(0, 280);
            res.style.background = 'rgba(220,38,38,.08)'; res.style.borderColor = '#dc2626';
            res.innerHTML = '<strong style="color:#dc2626">\u2717 Invalid YAML</strong><br>'
                + '<span style="font-size:12px;color:var(--color-text-muted)">' + htmlEsc(errMsg) + '</span>';
        }
    }

    /* ── 3. Viewer ───────────────────────────────────────────────────── */
    function runViewer() {
        var raw = gv('view-in');
        var tree = el('view-tree'); if (!tree) return;
        if (!raw.trim()) {
            tree.innerHTML = '<span style="color:var(--color-text-muted);font-size:13px">Paste YAML above to see the interactive tree.</span>';
            return;
        }
        var r = tryYAML(raw);
        if (!r.ok) {
            tree.innerHTML = '<span style="color:#dc2626;font-size:13px">Error: ' + htmlEsc(r.err.split('\n')[0].substring(0, 200)) + '</span>';
            return;
        }
        _treeId = 0;
        tree.innerHTML = buildTree(r.data, true);
    }

    var _treeId = 0;

    function buildTree(data, expanded) {
        if (Array.isArray(data)) {
            if (data.length === 0) return '<span style="color:var(--color-text-muted)">[]</span>';
            var id = 'ytn' + (++_treeId);
            var items = data.map(function (v, i) {
                return '<div style="padding-left:18px;border-left:2px solid var(--color-border)">'
                    + '<span class="yt-key">[' + i + ']</span> '
                    + buildTree(v, false)
                    + '</div>';
            }).join('');
            return '<span class="yt-toggle" onclick="YT_toggle(\'' + id + '\')">'
                + '<span style="color:var(--color-text-muted)">['
                + '<span id="' + id + '_p"' + (expanded ? ' style="display:none"' : '') + '> ' + data.length + ' items \u2026</span>'
                + ']</span>'
                + '</span>'
                + '<div id="' + id + '"' + (expanded ? '' : ' style="display:none"') + '>' + items + '</div>';
        }
        if (data !== null && typeof data === 'object') {
            var keys = Object.keys(data);
            if (keys.length === 0) return '<span style="color:var(--color-text-muted)">{}</span>';
            var id2 = 'ytn' + (++_treeId);
            var entries = keys.map(function (k) {
                return '<div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;padding-left:18px;border-left:2px solid var(--color-border)">'
                    + '<span class="yt-key">' + htmlEsc(k) + '</span>'
                    + '<span style="color:var(--color-text-muted)">: </span>'
                    + buildTree(data[k], false)
                    + '</div>';
            }).join('');
            return '<span class="yt-toggle" onclick="YT_toggle(\'' + id2 + '\')">'
                + '<span style="color:var(--color-text-muted)">{</span>'
                + '<span id="' + id2 + '_p"' + (expanded ? ' style="display:none"' : '') + ' style="color:var(--color-text-muted)"> ' + keys.length + ' keys \u2026</span>'
                + '<span style="color:var(--color-text-muted)">}</span>'
                + '</span>'
                + '<div id="' + id2 + '"' + (expanded ? '' : ' style="display:none"') + '>' + entries + '</div>';
        }
        if (data === null)             return '<span class="yt-null">null</span>';
        if (typeof data === 'boolean') return '<span class="yt-bool">' + String(data) + '</span>';
        if (typeof data === 'number')  return '<span class="yt-num">'  + String(data) + '</span>';
        return '<span class="yt-str">' + htmlEsc(String(data)) + '</span>';
    }

    function viewerAll(expand) {
        document.querySelectorAll('#view-tree [id^="ytn"]').forEach(function (node) {
            if (node.id.indexOf('_') !== -1) return;
            node.style.display = expand ? '' : 'none';
            var pr = el(node.id + '_p'); if (pr) pr.style.display = expand ? 'none' : '';
        });
    }

    /* ── 4. Sort Keys ────────────────────────────────────────────────── */
    function runSortKeys() {
        var raw = gv('sk-in');
        if (!raw.trim()) { sv('sk-out', ''); st('sk-st', '', ''); meta('sk-meta', ''); return; }
        var r = tryYAML(raw);
        if (!r.ok) { sv('sk-out', ''); st('sk-st', 'Error: ' + r.err.split('\n')[0].substring(0, 120), 'error'); meta('sk-meta', ''); return; }
        try {
            var sorted = sortKeys(r.data);
            var out = toYAML(sorted, 2);
            sv('sk-out', out);
            st('sk-st', bytes(out), 'ok');
            meta('sk-meta', out.split('\n').length + ' lines');
        } catch (e) {
            st('sk-st', 'Error: ' + e.message, 'error');
        }
    }

    /* ── 5. Flatten / Unflatten ──────────────────────────────────────── */
    function runFlatten() {
        var raw = gv('fl-in');
        if (!raw.trim()) { sv('fl-out', ''); st('fl-st', '', ''); meta('fl-meta', ''); return; }
        var modeEl = el('fl-mode');
        var sepEl  = el('fl-sep');
        var mode = modeEl ? modeEl.value : 'flatten';
        var sep  = sepEl  ? sepEl.value  : '.';
        var r = tryYAML(raw);
        if (!r.ok) { sv('fl-out', ''); st('fl-st', 'Error: ' + r.err.split('\n')[0].substring(0, 120), 'error'); meta('fl-meta', ''); return; }
        try {
            var result;
            if (mode === 'flatten') {
                result = flattenObj(r.data, '', sep, {});
            } else {
                if (typeof r.data !== 'object' || Array.isArray(r.data)) {
                    st('fl-st', 'Error: Input must be a flat YAML mapping.', 'error');
                    return;
                }
                result = unflattenObj(r.data, sep);
            }
            var out = toYAML(result, 2);
            sv('fl-out', out);
            st('fl-st', bytes(out), 'ok');
            meta('fl-meta', out.split('\n').length + ' lines');
        } catch (e) {
            st('fl-st', 'Error: ' + e.message, 'error');
        }
    }

    /* ── 6. Diff ─────────────────────────────────────────────────────── */
    function runDiff() {
        var rawA = gvt('diff-a'), rawB = gvt('diff-b');
        var out = el('diff-out'); if (!out) return;

        if (!rawA && !rawB) {
            out.innerHTML = '<div class="yt-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Paste YAML into both panes to compare</div>';
            st('diff-st', '', '');
            return;
        }
        if (!rawA || !rawB) { st('diff-st', 'Paste YAML into both panes', ''); return; }

        var rA = tryYAML(rawA), rB = tryYAML(rawB);
        if (!rA.ok) { out.innerHTML = '<div class="yt-diff-err">Error in A: ' + htmlEsc(rA.err.split('\n')[0]) + '</div>'; return; }
        if (!rB.ok) { out.innerHTML = '<div class="yt-diff-err">Error in B: ' + htmlEsc(rB.err.split('\n')[0]) + '</div>'; return; }

        var mapA = flattenForDiff(rA.data);
        var mapB = flattenForDiff(rB.data);

        var allKeys = Object.keys(Object.assign({}, mapA, mapB)).sort();
        var added = 0, removed = 0, changed = 0;
        var rows = '<div class="yt-diff-head"><span>Path</span><span>Change</span><span>Value A</span><span>Value B</span></div>';

        allKeys.forEach(function (k) {
            var vA = mapA[k], vB = mapB[k];
            var type, badge, cls;
            if      (vA === undefined) { type = 'added';   badge = 'Added';   cls = 'yt-diff-added';   added++; }
            else if (vB === undefined) { type = 'removed'; badge = 'Removed'; cls = 'yt-diff-removed'; removed++; }
            else if (vA !== vB)       { type = 'changed'; badge = 'Changed'; cls = 'yt-diff-changed'; changed++; }
            else { return; }

            rows += '<div class="yt-diff-row ' + cls + '">'
                + '<span class="yt-diff-path">' + htmlEsc(k) + '</span>'
                + '<span><span class="yt-diff-badge yt-diff-badge-' + type + '">' + badge + '</span></span>'
                + '<span class="yt-diff-val">' + (vA !== undefined ? htmlEsc(String(vA).substring(0, 120)) : '<em>\u2014</em>') + '</span>'
                + '<span class="yt-diff-val">' + (vB !== undefined ? htmlEsc(String(vB).substring(0, 120)) : '<em>\u2014</em>') + '</span>'
                + '</div>';
        });

        if (added + removed + changed === 0) {
            out.innerHTML = '<div class="yt-diff-equal"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Documents are structurally equal</div>';
            st('diff-st', 'No differences', 'ok');
            return;
        }

        out.innerHTML = '<div class="yt-diff-table">' + rows + '</div>';
        var summary = [];
        if (added)   summary.push('+' + added   + ' added');
        if (removed) summary.push('-' + removed + ' removed');
        if (changed) summary.push('~' + changed + ' changed');
        st('diff-st', summary.join(', '), 'error');
    }

    /* ── 7. Escape ───────────────────────────────────────────────────── */
    function runEscape() {
        var raw = gv('esc-in');
        if (!raw) { sv('esc-out', ''); meta('esc-meta', ''); return; }
        var out = yamlEscapeStr(raw);
        sv('esc-out', out);
        meta('esc-meta', out.length + ' chars');
    }

    /* ── 8. Unescape ─────────────────────────────────────────────────── */
    function runUnescape() {
        var raw = gv('unesc-in');
        if (!raw) { sv('unesc-out', ''); meta('unesc-meta', ''); return; }
        var out = yamlUnescapeStr(raw);
        sv('unesc-out', out);
        meta('unesc-meta', out.length + ' chars');
    }

    /* ── 9. YAML → JSON ──────────────────────────────────────────────── */
    function runYaml2Json() {
        var raw = gv('y2j-in');
        if (!raw.trim()) { sv('y2j-out', ''); st('y2j-st', '', ''); meta('y2j-meta', ''); return; }
        var r = tryYAML(raw);
        if (!r.ok) { sv('y2j-out', ''); st('y2j-st', 'Error: ' + r.err.split('\n')[0].substring(0, 120), 'error'); meta('y2j-meta', ''); return; }
        try {
            var indEl = el('y2j-indent');
            var ind = indEl ? parseInt(indEl.value, 10) : 2;
            var out = JSON.stringify(r.data, null, ind);
            sv('y2j-out', out);
            st('y2j-st', bytes(out), 'ok');
            meta('y2j-meta', out.split('\n').length + ' lines');
        } catch (e) {
            st('y2j-st', 'Error: ' + e.message, 'error');
        }
    }

    /* ── 10. JSON → YAML ─────────────────────────────────────────────── */
    function runJson2Yaml() {
        var raw = gv('j2y-in');
        if (!raw.trim()) { sv('j2y-out', ''); st('j2y-st', '', ''); meta('j2y-meta', ''); return; }
        try {
            var data = JSON.parse(raw);
            var out = toYAML(data, 2);
            sv('j2y-out', out);
            st('j2y-st', bytes(out), 'ok');
            meta('j2y-meta', out.split('\n').length + ' lines');
        } catch (e) {
            sv('j2y-out', '');
            st('j2y-st', 'Error: ' + e.message.split('\n')[0].substring(0, 120), 'error');
            meta('j2y-meta', '');
        }
    }

    /* ── 11. YAML → CSV ──────────────────────────────────────────────── */
    function runYaml2Csv() {
        var raw = gv('y2c-in');
        if (!raw.trim()) { sv('y2c-out', ''); st('y2c-st', '', ''); meta('y2c-meta', ''); return; }
        var r = tryYAML(raw);
        if (!r.ok) { st('y2c-st', 'Error: ' + r.err.split('\n')[0].substring(0, 120), 'error'); return; }

        try {
            var data = r.data;
            if (!Array.isArray(data)) {
                /* Try to use the values of the top-level object as rows */
                if (typeof data === 'object' && data !== null) {
                    data = Object.values(data);
                } else {
                    st('y2c-st', 'Error: Top-level value must be a sequence (array).', 'error'); return;
                }
            }
            if (data.length === 0) { st('y2c-st', 'Error: Array is empty.', 'error'); return; }

            /* Collect all keys */
            var cols = [], seen = {};
            data.forEach(function (row) {
                if (row !== null && typeof row === 'object' && !Array.isArray(row)) {
                    Object.keys(row).forEach(function (k) {
                        if (!seen[k]) { seen[k] = true; cols.push(k); }
                    });
                }
            });
            if (cols.length === 0) {
                /* Scalar array */
                var out2 = data.map(function (v) { return csvCell(String(v === null ? '' : v)); }).join('\n');
                sv('y2c-out', out2);
                st('y2c-st', bytes(out2), 'ok');
                meta('y2c-meta', data.length + ' rows');
                return;
            }

            var csvRows = [cols.map(csvCell).join(',')];
            data.forEach(function (row) {
                if (row === null || typeof row !== 'object') {
                    csvRows.push(cols.map(function () { return ''; }).join(','));
                    return;
                }
                csvRows.push(cols.map(function (k) { return csvCell(String(row[k] !== undefined && row[k] !== null ? row[k] : '')); }).join(','));
            });

            var out3 = csvRows.join('\n');
            sv('y2c-out', out3);
            st('y2c-st', bytes(out3), 'ok');
            meta('y2c-meta', (csvRows.length - 1) + ' rows, ' + cols.length + ' cols');
        } catch (e) {
            st('y2c-st', 'Error: ' + e.message, 'error');
        }
    }

    function csvCell(v) {
        v = String(v);
        if (v.indexOf(',') >= 0 || v.indexOf('"') >= 0 || v.indexOf('\n') >= 0 || v.indexOf('\r') >= 0) {
            return '"' + v.replace(/"/g, '""') + '"';
        }
        return v;
    }

    /* ── 12. CSV → YAML ──────────────────────────────────────────────── */
    function runCsv2Yaml() {
        var raw = gv('c2y-in');
        if (!raw.trim()) { sv('c2y-out', ''); st('c2y-st', '', ''); meta('c2y-meta', ''); return; }

        try {
            var lines = raw.split('\n').map(function (l) { return l.trimEnd(); }).filter(function (l) { return l.trim(); });
            if (lines.length < 2) { st('c2y-st', 'Error: Need at least a header row and one data row.', 'error'); return; }

            var headers = parseCSVLine(lines[0]).map(function (h) { return h.trim() || 'field'; });
            var records = [];
            for (var i = 1; i < lines.length; i++) {
                var fields = parseCSVLine(lines[i]);
                var obj = {};
                headers.forEach(function (h, idx) {
                    var v = fields[idx] !== undefined ? fields[idx] : '';
                    /* Auto-cast numbers and booleans */
                    if (v === 'true')  { obj[h] = true;  return; }
                    if (v === 'false') { obj[h] = false; return; }
                    if (v === 'null' || v === '') { obj[h] = null; return; }
                    var n = Number(v);
                    obj[h] = (!isNaN(n) && v.trim() !== '') ? n : v;
                });
                records.push(obj);
            }

            var out = toYAML(records, 2);
            sv('c2y-out', out);
            st('c2y-st', bytes(out), 'ok');
            meta('c2y-meta', records.length + ' record' + (records.length !== 1 ? 's' : ''));
        } catch (e) {
            st('c2y-st', 'Error: ' + e.message, 'error');
        }
    }

    function parseCSVLine(line) {
        var fields = [], cur = '', inQ = false;
        for (var i = 0; i < line.length; i++) {
            var c = line[i];
            if (inQ) {
                if (c === '"' && line[i + 1] === '"') { cur += '"'; i++; }
                else if (c === '"') { inQ = false; }
                else { cur += c; }
            } else {
                if (c === '"') { inQ = true; }
                else if (c === ',') { fields.push(cur); cur = ''; }
                else { cur += c; }
            }
        }
        fields.push(cur);
        return fields;
    }

    /* ── Public API ──────────────────────────────────────────────────── */
    return {
        switchTool: switchTool,
        clearAll:   clearAll,
        loadSample: loadSample,
        pasteInto:  pasteInto,
        openFile:   openFile,
        cpPane:     cpPane,
        dl:         dl,
        viewerAll:  viewerAll,
        /* tool runners */
        runFormatter:  runFormatter,
        runValidator:  runValidator,
        runViewer:     runViewer,
        runSortKeys:   runSortKeys,
        runFlatten:    runFlatten,
        runDiff:       runDiff,
        runEscape:     runEscape,
        runUnescape:   runUnescape,
        runYaml2Json:  runYaml2Json,
        runJson2Yaml:  runJson2Yaml,
        runYaml2Csv:   runYaml2Csv,
        runCsv2Yaml:   runCsv2Yaml
    };
})();

/* Global toggle for tree viewer nodes */
function YT_toggle(id) {
    var node = document.getElementById(id);
    var preview = document.getElementById(id + '_p');
    if (!node) return;
    var isHidden = node.style.display === 'none';
    node.style.display = isHidden ? '' : 'none';
    if (preview) preview.style.display = isHidden ? 'none' : '';
}

/* Auto-activate first tool on load; honour ?tool= deep-link */
document.addEventListener('DOMContentLoaded', function () {
    YT.switchTool('formatter');
    var t = new URLSearchParams(location.search).get('tool'); if (t) YT.switchTool(t);
});
