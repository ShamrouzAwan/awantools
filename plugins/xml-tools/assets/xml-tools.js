/* =====================================================================
   XML Tools — AWAN Platform Plugin v1.0.0
   All processing is 100% client-side. Zero server communication.
   ===================================================================== */
var XT = (function () {
    'use strict';

    var currentTool = 'formatter';

    /* ── Sample data ─────────────────────────────────────────────────── */
    var SAMPLE_XML = '<?xml version="1.0" encoding="UTF-8"?>\n<catalog>\n  <book id="bk101">\n    <author>Gambardella, Matthew</author>\n    <title>XML Developer\'s Guide</title>\n    <genre>Computer</genre>\n    <price>44.95</price>\n    <publish_date>2000-10-01</publish_date>\n    <description>An in-depth look at creating applications with XML.</description>\n  </book>\n  <book id="bk102">\n    <author>Ralls, Kim</author>\n    <title>Midnight Rain</title>\n    <genre>Fantasy</genre>\n    <price>5.95</price>\n    <publish_date>2000-12-16</publish_date>\n  </book>\n  <book id="bk103">\n    <author>Corets, Eva</author>\n    <title>Maeve Ascendant</title>\n    <genre>Fantasy</genre>\n    <price>5.95</price>\n    <publish_date>2000-11-17</publish_date>\n  </book>\n</catalog>';

    var SAMPLE_XML2 = '<?xml version="1.0" encoding="UTF-8"?>\n<catalog>\n  <book id="bk101">\n    <author>Gambardella, Matthew</author>\n    <title>XML Developer\'s Guide</title>\n    <genre>Computer</genre>\n    <price>49.95</price>\n    <publish_date>2000-10-01</publish_date>\n  </book>\n  <book id="bk104">\n    <author>Thurman, Paula</author>\n    <title>Splish Splash</title>\n    <genre>Romance</genre>\n    <price>4.95</price>\n  </book>\n</catalog>';

    var SAMPLE_CSV = 'id,author,title,genre,price\nbk101,Gambardella Matthew,XML Developers Guide,Computer,44.95\nbk102,Ralls Kim,Midnight Rain,Fantasy,5.95\nbk103,Corets Eva,Maeve Ascendant,Fantasy,5.95';

    var SAMPLE_ESC  = '<book id="1">\n  <title>Alice & Bob\'s "Adventures"</title>\n  <price>4.95</price>\n</book>';
    var SAMPLE_UNESC = '&lt;book id=&quot;1&quot;&gt;\n  &lt;title&gt;Alice &amp; Bob&apos;s &quot;Adventures&quot;&lt;/title&gt;\n&lt;/book&gt;';

    /* ── DOM helpers ─────────────────────────────────────────────────── */
    function el(id)    { return document.getElementById(id); }
    function gv(id)    { var e = el(id); return e ? e.value : ''; }
    function gvt(id)   { var e = el(id); return e ? e.value.trim() : ''; }
    function sv(id, v) { var e = el(id); if (e) e.value = v; }

    function st(id, msg, type) {
        var e = el(id); if (!e) return;
        if (!msg) { e.innerHTML = ''; return; }
        if (type === 'ok' || type === 'error') {
            e.innerHTML = '<span class="xt-status-chip ' + type + '"><span class="xt-dot"></span>' + msg + '</span>';
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

    /* ── XML helpers ─────────────────────────────────────────────────── */
    function xmlEsc(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&apos;');
    }

    function xmlUnesc(s) {
        return String(s)
            .replace(/&amp;/g, '&')
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>')
            .replace(/&quot;/g, '"')
            .replace(/&apos;/g, "'")
            .replace(/&#(\d+);/g, function (_, n) { return String.fromCharCode(parseInt(n, 10)); })
            .replace(/&#x([0-9a-fA-F]+);/g, function (_, h) { return String.fromCharCode(parseInt(h, 16)); });
    }

    /* Parse XML — returns {ok, doc} or {ok:false, err} */
    function tryXML(s) {
        if (!s || !s.trim()) return { ok: false, err: 'Input is empty.' };
        var parser = new DOMParser();
        var doc = parser.parseFromString(s, 'application/xml');
        var errNode = doc.querySelector('parsererror');
        if (errNode) {
            var msg = errNode.textContent || 'Parse error.';
            msg = msg.replace(/This page contains the following errors?:\s*/i, '');
            msg = msg.replace(/Below is a rendering of the page.*$/si, '').trim();
            return { ok: false, err: msg };
        }
        return { ok: true, doc: doc };
    }

    function hasDecl(raw) { return raw.trimStart().startsWith('<?xml'); }

    /* Serialize a DOM node to pretty-printed string */
    function serializeNode(node, depth, indent) {
        var pad = '';
        for (var i = 0; i < depth; i++) pad += indent;

        switch (node.nodeType) {
            case 1: { /* Element */
                var tag = node.tagName;
                var attrs = '';
                for (var a = 0; a < node.attributes.length; a++) {
                    var at = node.attributes[a];
                    attrs += ' ' + at.name + '="' + at.value.replace(/&/g,'&amp;').replace(/"/g,'&quot;') + '"';
                }
                /* Filter out whitespace-only text children */
                var kids = Array.from(node.childNodes).filter(function (c) {
                    return c.nodeType !== 3 || c.nodeValue.trim() !== '';
                });
                if (kids.length === 0) {
                    return pad + '<' + tag + attrs + '/>\n';
                }
                /* Single text child → inline */
                if (kids.length === 1 && kids[0].nodeType === 3) {
                    return pad + '<' + tag + attrs + '>' + kids[0].nodeValue.trim().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</' + tag + '>\n';
                }
                /* Children */
                var inner = '';
                kids.forEach(function (k) { inner += serializeNode(k, depth + 1, indent); });
                return pad + '<' + tag + attrs + '>\n' + inner + pad + '</' + tag + '>\n';
            }
            case 3: { /* Text */
                var txt = node.nodeValue.trim();
                return txt ? pad + txt.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '\n' : '';
            }
            case 4: { /* CDATA */
                return pad + '<![CDATA[' + node.nodeValue + ']]>\n';
            }
            case 8: { /* Comment */
                return pad + '<!--' + node.nodeValue + '-->\n';
            }
            case 7: { /* PI */
                return pad + '<?' + node.target + ' ' + node.data + '?>\n';
            }
            default: return '';
        }
    }

    function formatDoc(doc, raw, indent) {
        var out = hasDecl(raw) ? '<?xml version="1.0" encoding="UTF-8"?>\n' : '';
        Array.from(doc.childNodes).forEach(function (n) {
            if (n.nodeType === 7) return; /* skip PI already in declaration */
            out += serializeNode(n, 0, indent);
        });
        return out.trim();
    }

    /* Minify: strip whitespace text nodes, strip comments */
    function minifyNode(node) {
        switch (node.nodeType) {
            case 1: {
                var tag = node.tagName;
                var attrs = '';
                for (var a = 0; a < node.attributes.length; a++) {
                    var at = node.attributes[a];
                    attrs += ' ' + at.name + '="' + at.value.replace(/&/g,'&amp;').replace(/"/g,'&quot;') + '"';
                }
                var kids = Array.from(node.childNodes).filter(function (c) {
                    return c.nodeType !== 3 || c.nodeValue.trim() !== '';
                });
                if (kids.length === 0) return '<' + tag + attrs + '/>';
                var inner = kids.map(minifyNode).join('');
                return '<' + tag + attrs + '>' + inner + '</' + tag + '>';
            }
            case 3: {
                var t = node.nodeValue.trim();
                return t ? t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') : '';
            }
            case 4: return '<![CDATA[' + node.nodeValue + ']]>';
            case 8: return ''; /* strip comments */
            default: return '';
        }
    }

    /* Flatten XML to path→value map for diff */
    function flattenXML(node, path, map) {
        if (!node || node.nodeType !== 1) return;
        /* Attributes */
        for (var a = 0; a < node.attributes.length; a++) {
            map[path + '[@' + node.attributes[a].name + ']'] = node.attributes[a].value;
        }
        var childEls = Array.from(node.children);
        if (childEls.length === 0) {
            map[path] = node.textContent.trim();
            return;
        }
        /* Count occurrences of each tag name */
        var counts = {}, idxs = {};
        childEls.forEach(function (c) { counts[c.tagName] = (counts[c.tagName] || 0) + 1; });
        childEls.forEach(function (child) {
            var t = child.tagName;
            idxs[t] = (idxs[t] || 0) + 1;
            var childPath = path + '/' + t + (counts[t] > 1 ? '[' + idxs[t] + ']' : '');
            flattenXML(child, childPath, map);
        });
    }

    /* ── Navigation ──────────────────────────────────────────────────── */
    function switchTool(id) {
        currentTool = id;
        document.querySelectorAll('.xt-panel').forEach(function (p) { p.style.display = 'none'; });
        var panel = el('xt-' + id); if (panel) panel.style.display = '';
        document.querySelectorAll('.xt-nav').forEach(function (b) {
            b.classList.toggle('active', b.dataset.tool === id);
        });
        var active = document.querySelector('.xt-nav.active');
        if (active) active.scrollIntoView({ inline: 'nearest', block: 'nearest' });
    }

    /* ── Clear ───────────────────────────────────────────────────────── */
    function clearAll() {
        var inMap = {
            formatter:'fmt-in', beautifier:'beau-in', minifier:'min-in',
            validator:'val-in', viewer:'view-in', escape:'esc-in', unescape:'unesc-in',
            xml2csv:'x2c-in', csv2xml:'c2x-in', diff:'diff-a'
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
                'fmt-in': runFormatter, 'beau-in': runBeautifier, 'min-in': runMinifier,
                'val-in': runValidator, 'view-in': runViewer,
                'esc-in': runEscape, 'unesc-in': runUnescape,
                'x2c-in': runXml2Csv, 'c2x-in': runCsv2Xml,
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
        inp.accept = accept || '.xml,.txt';
        inp.onchange = function () {
            var file = inp.files[0]; if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                sv(targetId, e.target.result);
                var autoMap = {
                    'fmt-in': runFormatter, 'beau-in': runBeautifier, 'min-in': runMinifier,
                    'val-in': runValidator, 'view-in': runViewer,
                    'x2c-in': runXml2Csv, 'c2x-in': runCsv2Xml
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
            formatter:'fmt-in', beautifier:'beau-in', minifier:'min-in',
            validator:'val-in', viewer:'view-in', escape:'esc-in', unescape:'unesc-in',
            xml2csv:'x2c-in', csv2xml:'c2x-in', diff:'diff-a'
        };
        var smap = { csv2xml: SAMPLE_CSV, escape: SAMPLE_ESC, unescape: SAMPLE_UNESC, diff: SAMPLE_XML };
        var id = map[currentTool]; if (!id) return;
        sv(id, smap[currentTool] || SAMPLE_XML);
        if (currentTool === 'diff') sv('diff-b', SAMPLE_XML2);
        var auto = {
            formatter: runFormatter, beautifier: runBeautifier, minifier: runMinifier,
            validator: runValidator, viewer: runViewer,
            escape: runEscape, unescape: runUnescape,
            xml2csv: runXml2Csv, csv2xml: runCsv2Xml, diff: runDiff
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
        var ind = indEl ? (indEl.value === 'tab' ? '\t' : new Array(parseInt(indEl.value, 10) + 1).join(' ')) : '  ';
        var r = tryXML(raw);
        if (!r.ok) { sv('fmt-out', ''); st('fmt-st', 'Error: ' + r.err.split('\n')[0].substring(0, 120), 'error'); meta('fmt-meta', ''); return; }
        var out = formatDoc(r.doc, raw, ind);
        sv('fmt-out', out);
        st('fmt-st', bytes(out), 'ok');
        meta('fmt-meta', out.split('\n').length + ' lines');
    }

    /* ── 2. Beautifier ───────────────────────────────────────────────── */
    function runBeautifier() {
        var raw = gv('beau-in');
        if (!raw.trim()) { sv('beau-out', ''); st('beau-st', '', ''); meta('beau-meta', ''); return; }
        var r = tryXML(raw);
        if (!r.ok) { sv('beau-out', ''); st('beau-st', 'Error: ' + r.err.split('\n')[0].substring(0, 120), 'error'); meta('beau-meta', ''); return; }
        var out = formatDoc(r.doc, raw, '  ');
        sv('beau-out', out);
        st('beau-st', bytes(out), 'ok');
        meta('beau-meta', out.split('\n').length + ' lines');
    }

    /* ── 3. Minifier ─────────────────────────────────────────────────── */
    function runMinifier() {
        var raw = gv('min-in');
        if (!raw.trim()) { sv('min-out', ''); st('min-st', '', ''); meta('min-meta', ''); return; }
        var r = tryXML(raw);
        if (!r.ok) { sv('min-out', ''); st('min-st', 'Error: ' + r.err.split('\n')[0].substring(0, 120), 'error'); meta('min-meta', ''); return; }
        var decl = hasDecl(raw) ? '<?xml version="1.0" encoding="UTF-8"?>' : '';
        var bodyNodes = Array.from(r.doc.childNodes).filter(function (n) { return n.nodeType !== 7; });
        var out = decl + bodyNodes.map(minifyNode).join('');
        sv('min-out', out);
        var pct = raw.trim().length > 0 ? Math.round((1 - out.length / raw.trim().length) * 100) : 0;
        st('min-st', bytes(out) + ' \u2013 \u2212' + Math.max(0, pct) + '%', 'ok');
        meta('min-meta', out.length + ' chars');
    }

    /* ── 4. Validator ────────────────────────────────────────────────── */
    function runValidator() {
        var raw = gv('val-in');
        var res = el('val-res'); if (!res) return;
        if (!raw.trim()) { res.innerHTML = ''; res.style.display = 'none'; return; }
        var r = tryXML(raw);
        res.style.display = 'block';
        if (r.ok) {
            var root = r.doc.documentElement;
            var allEls = r.doc.querySelectorAll('*');
            var attrCount = 0;
            allEls.forEach(function (e) { attrCount += e.attributes.length; });
            res.style.background = 'rgba(22,163,74,.08)'; res.style.borderColor = '#22c55e';
            res.innerHTML = '<strong style="color:#16a34a">\u2713 Valid XML</strong><br>'
                + '<span style="font-size:12px;color:var(--color-text-muted)">Root: <strong>&lt;' + htmlEsc(root.tagName) + '&gt;</strong>'
                + ' &nbsp;&middot;&nbsp; Elements: <strong>' + allEls.length + '</strong>'
                + ' &nbsp;&middot;&nbsp; Attributes: <strong>' + attrCount + '</strong>'
                + ' &nbsp;&middot;&nbsp; Size: <strong>' + bytes(raw) + '</strong></span>';
        } else {
            var errMsg = r.err.split('\n')[0].substring(0, 220);
            res.style.background = 'rgba(220,38,38,.08)'; res.style.borderColor = '#dc2626';
            res.innerHTML = '<strong style="color:#dc2626">\u2717 Invalid XML</strong><br>'
                + '<span style="font-size:12px;color:var(--color-text-muted)">' + htmlEsc(errMsg) + '</span>';
        }
    }

    /* ── 5. Viewer ───────────────────────────────────────────────────── */
    function runViewer() {
        var raw = gv('view-in');
        var tree = el('view-tree'); if (!tree) return;
        if (!raw.trim()) {
            tree.innerHTML = '<span style="color:var(--color-text-muted);font-size:13px">Paste XML above to see the interactive tree.</span>';
            return;
        }
        var r = tryXML(raw);
        if (!r.ok) {
            tree.innerHTML = '<span style="color:#dc2626;font-size:13px">Error: ' + htmlEsc(r.err.split('\n')[0].substring(0, 200)) + '</span>';
            return;
        }
        _treeId = 0;
        tree.innerHTML = buildXMLTree(r.doc.documentElement, true);
    }

    var _treeId = 0;

    function buildXMLTree(node, expanded) {
        if (!node || node.nodeType !== 1) return '';
        var id = 'xtn' + (++_treeId);
        var tag = node.tagName;

        /* Render attributes inline */
        var attrHtml = '';
        for (var a = 0; a < node.attributes.length; a++) {
            var attr = node.attributes[a];
            attrHtml += ' <span class="xt-attr-name">' + htmlEsc(attr.name) + '</span>'
                + '<span style="color:var(--color-text-muted)">=</span>'
                + '<span class="xt-attr-val">&quot;' + htmlEsc(attr.value) + '&quot;</span>';
        }

        var childEls = Array.from(node.children);
        var directText = '';
        Array.from(node.childNodes).forEach(function (c) {
            if (c.nodeType === 3) { var t = c.nodeValue.trim(); if (t) directText += t; }
        });

        /* Leaf node */
        if (childEls.length === 0) {
            return '<div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'
                + '<span class="xt-el-tag">&lt;' + htmlEsc(tag) + attrHtml + '&gt;</span>'
                + (directText ? '<span class="xt-el-text">' + htmlEsc(directText) + '</span>' : '')
                + '<span class="xt-el-close">&lt;/' + htmlEsc(tag) + '&gt;</span>'
                + '</div>';
        }

        /* Branch node */
        var children = '';
        childEls.forEach(function (child) { children += buildXMLTree(child, false); });

        return '<div>'
            + '<span class="xt-toggle" onclick="XT_toggle(\'' + id + '\')">'
            + '<span class="xt-el-tag">&lt;' + htmlEsc(tag) + attrHtml + '&gt;</span>'
            + '<span id="' + id + '_p" style="font-size:11px;color:var(--color-text-muted)'
            + (expanded ? ';display:none' : '') + '"> '
            + childEls.length + ' element' + (childEls.length !== 1 ? 's' : '') + ' \u2026</span>'
            + '</span>'
            + '<div id="' + id + '" style="' + (expanded ? '' : 'display:none') + ';padding-left:18px;border-left:2px solid var(--color-border)">'
            + children
            + '</div>'
            + '<span class="xt-el-close">&lt;/' + htmlEsc(tag) + '&gt;</span>'
            + '</div>';
    }

    function viewerAll(expand) {
        document.querySelectorAll('#view-tree [id^="xtn"]').forEach(function (node) {
            if (node.id.indexOf('_') !== -1) return;
            node.style.display = expand ? '' : 'none';
            var pr = el(node.id + '_p'); if (pr) pr.style.display = expand ? 'none' : '';
        });
    }

    /* ── 6. XML Escape ───────────────────────────────────────────────── */
    function runEscape() {
        var raw = gv('esc-in');
        if (!raw) { sv('esc-out', ''); meta('esc-meta', ''); return; }
        var out = xmlEsc(raw);
        sv('esc-out', out);
        meta('esc-meta', out.length + ' chars');
    }

    /* ── 7. XML Unescape ─────────────────────────────────────────────── */
    function runUnescape() {
        var raw = gv('unesc-in');
        if (!raw) { sv('unesc-out', ''); meta('unesc-meta', ''); return; }
        var out = xmlUnesc(raw);
        sv('unesc-out', out);
        meta('unesc-meta', out.length + ' chars');
    }

    /* ── 8. XML → CSV ────────────────────────────────────────────────── */
    function runXml2Csv() {
        var raw = gv('x2c-in');
        if (!raw.trim()) { sv('x2c-out', ''); st('x2c-st', '', ''); meta('x2c-meta', ''); return; }
        var r = tryXML(raw);
        if (!r.ok) { st('x2c-st', 'Error: ' + r.err.split('\n')[0].substring(0, 120), 'error'); return; }

        try {
            var root = r.doc.documentElement;
            var rows = Array.from(root.children);
            if (rows.length === 0) { st('x2c-st', 'Error: No child elements found in root.', 'error'); return; }

            /* Collect all column names: attributes (@name) and child element names */
            var cols = [], seen = {};
            rows.forEach(function (row) {
                for (var a = 0; a < row.attributes.length; a++) {
                    var k = '@' + row.attributes[a].name;
                    if (!seen[k]) { seen[k] = true; cols.push(k); }
                }
                Array.from(row.children).forEach(function (child) {
                    if (!seen[child.tagName]) { seen[child.tagName] = true; cols.push(child.tagName); }
                });
            });

            if (cols.length === 0) { st('x2c-st', 'Error: No extractable fields found.', 'error'); return; }

            var csvRows = [cols.map(csvCell).join(',')];
            rows.forEach(function (row) {
                var vals = cols.map(function (col) {
                    if (col.charAt(0) === '@') {
                        return csvCell(row.getAttribute(col.slice(1)) || '');
                    }
                    var child = row.querySelector(col);
                    return csvCell(child ? child.textContent.trim() : '');
                });
                csvRows.push(vals.join(','));
            });

            var out = csvRows.join('\n');
            sv('x2c-out', out);
            st('x2c-st', bytes(out), 'ok');
            meta('x2c-meta', (csvRows.length - 1) + ' rows, ' + cols.length + ' cols');
        } catch (e) {
            st('x2c-st', 'Error: ' + e.message, 'error');
        }
    }

    function csvCell(v) {
        v = String(v);
        if (v.indexOf(',') >= 0 || v.indexOf('"') >= 0 || v.indexOf('\n') >= 0 || v.indexOf('\r') >= 0) {
            return '"' + v.replace(/"/g, '""') + '"';
        }
        return v;
    }

    /* ── 9. CSV → XML ────────────────────────────────────────────────── */
    function runCsv2Xml() {
        var raw = gv('c2x-in');
        if (!raw.trim()) { sv('c2x-out', ''); st('c2x-st', '', ''); meta('c2x-meta', ''); return; }

        try {
            var lines = raw.split('\n').map(function (l) { return l.trimEnd(); }).filter(function (l) { return l.trim(); });
            if (lines.length < 2) { st('c2x-st', 'Error: Need at least a header row and one data row.', 'error'); return; }

            var headers = parseCSVLine(lines[0]).map(function (h) {
                h = h.trim().replace(/[^a-zA-Z0-9_\-.]/g, '_');
                if (/^\d/.test(h)) h = '_' + h;
                return h || 'field';
            });

            var rowTagEl = el('c2x-row-tag'), rootTagEl = el('c2x-root-tag');
            var rowTag  = sanitizeTag((rowTagEl  && rowTagEl.value.trim())  || 'item');
            var rootTag = sanitizeTag((rootTagEl && rootTagEl.value.trim()) || 'root');

            var xmlLines = ['<?xml version="1.0" encoding="UTF-8"?>', '<' + rootTag + '>'];
            var count = 0;
            for (var i = 1; i < lines.length; i++) {
                var fields = parseCSVLine(lines[i]);
                xmlLines.push('  <' + rowTag + '>');
                headers.forEach(function (h, idx) {
                    var val = fields[idx] !== undefined ? fields[idx] : '';
                    xmlLines.push('    <' + h + '>' + xmlEsc(val) + '</' + h + '>');
                });
                xmlLines.push('  </' + rowTag + '>');
                count++;
            }
            xmlLines.push('</' + rootTag + '>');

            var out = xmlLines.join('\n');
            sv('c2x-out', out);
            st('c2x-st', bytes(out), 'ok');
            meta('c2x-meta', count + ' record' + (count !== 1 ? 's' : ''));
        } catch (e) {
            st('c2x-st', 'Error: ' + e.message, 'error');
        }
    }

    function sanitizeTag(t) {
        t = t.replace(/[^a-zA-Z0-9_\-.]/g, '_');
        return /^\d/.test(t) ? '_' + t : (t || 'item');
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

    /* ── 10. XML Diff ────────────────────────────────────────────────── */
    function runDiff() {
        var rawA = gvt('diff-a'), rawB = gvt('diff-b');
        var out = el('diff-out'); if (!out) return;

        if (!rawA && !rawB) {
            out.innerHTML = '<div class="xt-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Paste XML into both panes to compare</div>';
            st('diff-st', '', '');
            return;
        }
        if (!rawA || !rawB) { st('diff-st', 'Paste XML into both panes', ''); return; }

        var rA = tryXML(rawA), rB = tryXML(rawB);
        if (!rA.ok) { out.innerHTML = '<div class="xt-diff-err">Error in A: ' + htmlEsc(rA.err.split('\n')[0]) + '</div>'; return; }
        if (!rB.ok) { out.innerHTML = '<div class="xt-diff-err">Error in B: ' + htmlEsc(rB.err.split('\n')[0]) + '</div>'; return; }

        var mapA = {}, mapB = {};
        flattenXML(rA.doc.documentElement, rA.doc.documentElement.tagName, mapA);
        flattenXML(rB.doc.documentElement, rB.doc.documentElement.tagName, mapB);

        var allKeys = Object.keys(Object.assign({}, mapA, mapB)).sort();
        var added = 0, removed = 0, changed = 0;
        var rows = '<div class="xt-diff-head"><span>Path</span><span>Change</span><span>Value A</span><span>Value B</span></div>';

        allKeys.forEach(function (k) {
            var vA = mapA[k], vB = mapB[k];
            var type, badge, cls;
            if      (vA === undefined)  { type = 'added';   badge = 'Added';   cls = 'xt-diff-added';   added++; }
            else if (vB === undefined)  { type = 'removed';  badge = 'Removed'; cls = 'xt-diff-removed'; removed++; }
            else if (vA !== vB)        { type = 'changed'; badge = 'Changed'; cls = 'xt-diff-changed'; changed++; }
            else { return; }

            rows += '<div class="xt-diff-row ' + cls + '">'
                + '<span class="xt-diff-path">' + htmlEsc(k) + '</span>'
                + '<span><span class="xt-diff-badge xt-diff-badge-' + type + '">' + badge + '</span></span>'
                + '<span class="xt-diff-val">' + (vA !== undefined ? htmlEsc(String(vA).substring(0, 120)) : '<em>\u2014</em>') + '</span>'
                + '<span class="xt-diff-val">' + (vB !== undefined ? htmlEsc(String(vB).substring(0, 120)) : '<em>\u2014</em>') + '</span>'
                + '</div>';
        });

        if (added + removed + changed === 0) {
            out.innerHTML = '<div class="xt-diff-equal"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Documents are structurally equal</div>';
            st('diff-st', 'No differences', 'ok');
            return;
        }

        out.innerHTML = '<div class="xt-diff-table">' + rows + '</div>';
        var summary = [];
        if (added)   summary.push('+' + added   + ' added');
        if (removed) summary.push('-' + removed + ' removed');
        if (changed) summary.push('~' + changed + ' changed');
        st('diff-st', summary.join(', '), 'error');
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
        runBeautifier: runBeautifier,
        runMinifier:   runMinifier,
        runValidator:  runValidator,
        runViewer:     runViewer,
        runEscape:     runEscape,
        runUnescape:   runUnescape,
        runXml2Csv:    runXml2Csv,
        runCsv2Xml:    runCsv2Xml,
        runDiff:       runDiff
    };
})();

/* Global toggle for tree viewer nodes */
function XT_toggle(id) {
    var node = document.getElementById(id);
    var preview = document.getElementById(id + '_p');
    if (!node) return;
    var isHidden = node.style.display === 'none';
    node.style.display = isHidden ? '' : 'none';
    if (preview) preview.style.display = isHidden ? 'none' : '';
}

/* Auto-activate first tool on load; honour ?tool= deep-link */
document.addEventListener('DOMContentLoaded', function () {
    XT.switchTool('formatter');
    var t = new URLSearchParams(location.search).get('tool'); if (t) XT.switchTool(t);
});
