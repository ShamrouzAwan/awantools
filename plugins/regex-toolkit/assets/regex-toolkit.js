/* =====================================================================
   Regex Toolkit — AWAN Platform Plugin v1.0.0
   All processing is 100% client-side. Zero server communication.
   ===================================================================== */
var RT = (function () {
    'use strict';

    var currentTool = 'tester';

    /* ── DOM helpers ─────────────────────────────────────────────────── */
    function el(id)    { return document.getElementById(id); }
    function gv(id)    { var e = el(id); return e ? e.value : ''; }
    function gvt(id)   { var e = el(id); return e ? e.value.trim() : ''; }
    function sv(id, v) { var e = el(id); if (e) e.value = v; }
    function sh(id, h) { var e = el(id); if (e) e.innerHTML = h; }

    function htmlEsc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function chip(msg, type) {
        return '<span class="rt-chip ' + type + '"><span class="rt-dot"></span>' + msg + '</span>';
    }

    var COPY_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><polyline points="20 6 9 17 4 12"/></svg>';

    function flash(btn, label) {
        btn.classList.add('copied');
        var orig = btn.innerHTML;
        btn.innerHTML = COPY_SVG + label;
        setTimeout(function () { btn.innerHTML = orig; btn.classList.remove('copied'); }, 1500);
    }

    function cpText(text, btn) {
        if (!text) return;
        navigator.clipboard.writeText(text).then(function () { if (btn) flash(btn, 'Copied!'); });
    }

    function cpEl(id, btn) {
        var e = el(id); if (!e) return;
        var text = e.value !== undefined ? e.value : e.textContent;
        cpText(text, btn);
    }

    /* ── Navigation ──────────────────────────────────────────────────── */
    function switchTool(id) {
        currentTool = id;
        document.querySelectorAll('.rt-panel').forEach(function (p) { p.style.display = 'none'; });
        var panel = el('rt-' + id); if (panel) panel.style.display = '';
        document.querySelectorAll('.rt-nav').forEach(function (b) {
            b.classList.toggle('active', b.dataset.tool === id);
        });
        var active = document.querySelector('.rt-nav.active');
        if (active) active.scrollIntoView({ inline: 'nearest', block: 'nearest' });
    }

    /* ── Get regex from the shared input row (used by multiple tools) ─ */
    function getRegex(patId, flagsId) {
        var pat = gvt(patId);
        if (!pat) return null;
        var inp = el(patId);
        /* Collect flag toggles */
        var flags = '';
        if (flagsId) {
            var container = el(flagsId);
            if (container) {
                container.querySelectorAll('.rt-flag.on').forEach(function (b) { flags += b.dataset.flag; });
            }
        }
        try {
            var re = new RegExp(pat, flags);
            if (inp) inp.classList.remove('invalid');
            return re;
        } catch(e) {
            if (inp) inp.classList.add('invalid');
            return null;
        }
    }

    /* ── Load sample ─────────────────────────────────────────────────── */
    var SAMPLE_PATTERN = '(\\b\\w+\\b)';
    var SAMPLE_TEXT = 'The quick brown fox jumps over the lazy dog.';

    function loadSample() {
        var maps = {
            tester:     { pat:'t-pat', text:'t-text' },
            replace:    { pat:'rp-pat', text:'rp-text' },
            extractor:  { pat:'ex-pat', text:'ex-text' },
            highlighter:{ pat:'hl-pat', text:'hl-text' },
            explainer:  { pat:'xp-pat' },
            validator:  { pat:'vl-pat', text:'vl-text' },
            namedgroups:{ pat:'ng-pat', text:'ng-text' }
        };
        var m = maps[currentTool]; if (!m) return;
        if (m.pat) {
            sv(m.pat, SAMPLE_PATTERN);
            var inp = el(m.pat); if (inp) inp.classList.remove('invalid');
        }
        if (m.text) sv(m.text, SAMPLE_TEXT);
        runCurrent();
    }

    function runCurrent() {
        var fns = {
            tester: runTester, replace: runReplace, extractor: runExtractor,
            highlighter: runHighlighter, explainer: runExplainer, validator: runValidator,
            namedgroups: runNamedGroups
        };
        if (fns[currentTool]) fns[currentTool]();
    }

    function clearCurrent() {
        var pats = { tester:'t-pat', replace:'rp-pat', extractor:'ex-pat', highlighter:'hl-pat', explainer:'xp-pat', validator:'vl-pat', namedgroups:'ng-pat' };
        var texts = { tester:'t-text', replace:'rp-text', extractor:'ex-text', highlighter:'hl-text', validator:'vl-text', namedgroups:'ng-text' };
        var p = pats[currentTool]; if (p) { sv(p, ''); var inp = el(p); if (inp) inp.classList.remove('invalid'); }
        var t = texts[currentTool]; if (t) sv(t, '');
        runCurrent();
    }

    /* ── Flag toggle ─────────────────────────────────────────────────── */
    function toggleFlag(btn) {
        btn.classList.toggle('on');
        runCurrent();
    }

    /* ══════════════════════════════════════════════════════════════════
       1. REGEX TESTER
       ══════════════════════════════════════════════════════════════════ */
    function runTester() {
        var pat = gvt('t-pat');
        var text = gv('t-text');
        var statusEl = el('t-status');
        var matchOut = el('t-matches');
        if (!pat) {
            if (statusEl) statusEl.innerHTML = '';
            if (matchOut) matchOut.innerHTML = '<div class="rt-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Enter a pattern above to begin</div>';
            return;
        }
        var flags = getActiveFlags('t-flags');
        try {
            var inp = el('t-pat'); if (inp) inp.classList.remove('invalid');
            var re = new RegExp(pat, flags.includes('g') ? flags : flags + 'g');
            var matches = [];
            var m;
            re.lastIndex = 0;
            while ((m = re.exec(text)) !== null) {
                matches.push({ index: m.index, value: m[0], groups: Array.from(m).slice(1), namedGroups: m.groups || {} });
                if (m[0].length === 0) re.lastIndex++;
                if (matches.length > 500) break;
            }
            if (statusEl) {
                if (matches.length === 0) statusEl.innerHTML = chip('No matches', 'warn');
                else statusEl.innerHTML = chip(matches.length + ' match' + (matches.length !== 1 ? 'es' : ''), 'ok');
            }
            if (matchOut) {
                if (matches.length === 0) {
                    matchOut.innerHTML = '<div style="padding:14px;color:var(--color-text-muted);font-size:13px">No matches found in the test string.</div>';
                } else {
                    var html = '';
                    matches.forEach(function (m2, i) {
                        html += '<div class="rt-match-item"><div class="rt-match-header">'
                            + '<span class="rt-match-idx">#' + (i+1) + '</span>'
                            + '<span class="rt-match-val">' + htmlEsc(m2.value) + '</span>'
                            + '<span class="rt-match-pos">index ' + m2.index + '&ndash;' + (m2.index + m2.value.length) + '</span>'
                            + '</div>';
                        if (m2.groups.length > 0 || Object.keys(m2.namedGroups).length > 0) {
                            html += '<div class="rt-match-groups">';
                            m2.groups.forEach(function (g, gi) {
                                if (g !== undefined) html += '<span class="rt-group-badge"><span class="rt-group-name">$' + (gi+1) + ':</span> ' + htmlEsc(g) + '</span>';
                            });
                            Object.keys(m2.namedGroups).forEach(function (k) {
                                html += '<span class="rt-group-badge"><span class="rt-group-name">' + htmlEsc(k) + ':</span> ' + htmlEsc(m2.namedGroups[k]) + '</span>';
                            });
                            html += '</div>';
                        }
                        html += '</div>';
                    });
                    matchOut.innerHTML = html;
                }
            }
        } catch(e) {
            var inp2 = el('t-pat'); if (inp2) inp2.classList.add('invalid');
            if (statusEl) statusEl.innerHTML = chip('Error: ' + e.message.substring(0,60), 'error');
            if (matchOut) matchOut.innerHTML = '';
        }
    }

    function getActiveFlags(containerId) {
        var s = '';
        var c = el(containerId); if (!c) return s;
        c.querySelectorAll('.rt-flag.on').forEach(function (b) { s += b.dataset.flag; });
        return s;
    }

    /* ══════════════════════════════════════════════════════════════════
       2. REGEX REPLACE
       ══════════════════════════════════════════════════════════════════ */
    function runReplace() {
        var pat = gvt('rp-pat');
        var repl = gv('rp-repl');
        var text = gv('rp-text');
        var statusEl = el('rp-status');
        var outEl = el('rp-out');
        if (!pat) {
            if (statusEl) statusEl.innerHTML = '';
            if (outEl) outEl.innerHTML = '<div class="rt-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Enter a pattern to replace</div>';
            return;
        }
        var flags = getActiveFlags('rp-flags');
        try {
            var inp = el('rp-pat'); if (inp) inp.classList.remove('invalid');
            var re = new RegExp(pat, flags.includes('g') ? flags : flags + 'g');
            var count = 0;
            var result = text.replace(re, function () { count++; return repl; });
            sv('rp-result-ta', result);
            if (statusEl) statusEl.innerHTML = count > 0 ? chip(count + ' replacement' + (count !== 1 ? 's' : ''), 'ok') : chip('No matches', 'warn');
            /* Show diff */
            if (outEl) {
                if (count === 0) {
                    outEl.innerHTML = '<div style="padding:14px;color:var(--color-text-muted);font-size:13px">No replacements made.</div>';
                } else {
                    var re2 = new RegExp(pat, flags.includes('g') ? flags : flags + 'g');
                    var diffHtml = '';
                    var last = 0;
                    text.replace(re2, function (match) {
                        var idx = arguments[arguments.length - 2];
                        diffHtml += htmlEsc(text.slice(last, idx));
                        diffHtml += '<span class="rt-diff-del">' + htmlEsc(match) + '</span>';
                        diffHtml += '<span class="rt-diff-add">' + htmlEsc(repl.replace(/\$\d+/g, '…')) + '</span>';
                        last = idx + match.length;
                        return repl;
                    });
                    diffHtml += htmlEsc(text.slice(last));
                    outEl.innerHTML = '<div style="padding:12px 14px;font-family:monospace;font-size:13px;line-height:1.7;background:var(--color-background);overflow:auto;max-height:200px">' + diffHtml + '</div>';
                }
            }
        } catch(e) {
            var inp3 = el('rp-pat'); if (inp3) inp3.classList.add('invalid');
            if (statusEl) statusEl.innerHTML = chip('Error: ' + e.message.substring(0,60), 'error');
            if (outEl) outEl.innerHTML = '';
        }
    }

    /* ══════════════════════════════════════════════════════════════════
       3. REGEX EXTRACTOR
       ══════════════════════════════════════════════════════════════════ */
    function runExtractor() {
        var pat = gvt('ex-pat');
        var text = gv('ex-text');
        var statusEl = el('ex-status');
        var outEl = el('ex-out');
        if (!pat) {
            if (statusEl) statusEl.innerHTML = '';
            if (outEl) outEl.innerHTML = '<div class="rt-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Enter a pattern to extract matches</div>';
            return;
        }
        var flags = getActiveFlags('ex-flags');
        try {
            var inp = el('ex-pat'); if (inp) inp.classList.remove('invalid');
            var re = new RegExp(pat, flags.includes('g') ? flags : flags + 'g');
            var matches = [];
            var m;
            re.lastIndex = 0;
            while ((m = re.exec(text)) !== null) {
                matches.push(m);
                if (m[0].length === 0) re.lastIndex++;
                if (matches.length > 1000) break;
            }
            if (statusEl) statusEl.innerHTML = matches.length > 0 ? chip(matches.length + ' match' + (matches.length !== 1 ? 'es' : ''), 'ok') : chip('No matches', 'warn');
            if (!outEl) return;
            if (matches.length === 0) { outEl.innerHTML = '<div style="padding:14px;color:var(--color-text-muted);font-size:13px">No matches found.</div>'; return; }
            /* Table output */
            var hasGroups = matches[0].length > 1;
            var html = '<table style="width:100%;border-collapse:collapse;font-family:monospace;font-size:12.5px"><thead><tr>'
                + '<th style="padding:6px 14px;background:var(--color-surface);border-bottom:1px solid var(--color-border);font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--color-text-muted);text-align:left">#</th>'
                + '<th style="padding:6px 14px;background:var(--color-surface);border-bottom:1px solid var(--color-border);font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--color-text-muted);text-align:left">Match</th>'
                + '<th style="padding:6px 14px;background:var(--color-surface);border-bottom:1px solid var(--color-border);font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--color-text-muted);text-align:left">Index</th>';
            if (hasGroups) {
                for (var g = 1; g < matches[0].length; g++) html += '<th style="padding:6px 14px;background:var(--color-surface);border-bottom:1px solid var(--color-border);font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--color-text-muted);text-align:left">Group ' + g + '</th>';
            }
            html += '</tr></thead><tbody>';
            matches.forEach(function (m2, i) {
                html += '<tr style="border-bottom:1px solid var(--color-border)">'
                    + '<td style="padding:7px 14px;color:var(--color-text-muted);font-size:11px">' + (i+1) + '</td>'
                    + '<td style="padding:7px 14px;color:var(--color-primary);font-weight:600">' + htmlEsc(m2[0]) + '</td>'
                    + '<td style="padding:7px 14px;color:var(--color-text-muted)">' + m2.index + '</td>';
                if (hasGroups) {
                    for (var g2 = 1; g2 < m2.length; g2++) html += '<td style="padding:7px 14px;color:var(--color-text)">' + (m2[g2] !== undefined ? htmlEsc(m2[g2]) : '<span style="color:var(--color-text-muted);font-style:italic">undefined</span>') + '</td>';
                }
                html += '</tr>';
            });
            html += '</tbody></table>';
            outEl.innerHTML = html;
        } catch(e) {
            var inp4 = el('ex-pat'); if (inp4) inp4.classList.add('invalid');
            if (statusEl) statusEl.innerHTML = chip('Error: ' + e.message.substring(0,60), 'error');
            if (outEl) outEl.innerHTML = '';
        }
    }

    /* ══════════════════════════════════════════════════════════════════
       4. REGEX HIGHLIGHTER
       ══════════════════════════════════════════════════════════════════ */
    function runHighlighter() {
        var pat = gvt('hl-pat');
        var text = gv('hl-text');
        var outEl = el('hl-out');
        var statusEl = el('hl-status');
        if (!outEl) return;
        if (!pat || !text) { outEl.textContent = text || ''; if (statusEl) statusEl.innerHTML = ''; return; }
        var flags = getActiveFlags('hl-flags');
        try {
            var inp = el('hl-pat'); if (inp) inp.classList.remove('invalid');
            var re = new RegExp(pat, flags.includes('g') ? flags : flags + 'g');
            var count = 0;
            var html = '';
            var last = 0;
            text.replace(re, function (match) {
                var idx = arguments[arguments.length - 2];
                html += htmlEsc(text.slice(last, idx));
                var cls = 'rt-match rt-match-' + ((count % 5) + 1);
                html += '<mark class="' + cls + '">' + htmlEsc(match) + '</mark>';
                last = idx + match.length;
                count++;
                return match;
            });
            html += htmlEsc(text.slice(last));
            outEl.innerHTML = html;
            if (statusEl) statusEl.innerHTML = count > 0 ? chip(count + ' match' + (count !== 1 ? 'es' : '') + ' highlighted', 'ok') : chip('No matches', 'warn');
        } catch(e) {
            var inp5 = el('hl-pat'); if (inp5) inp5.classList.add('invalid');
            outEl.textContent = text;
            if (statusEl) statusEl.innerHTML = chip('Invalid pattern', 'error');
        }
    }

    /* ══════════════════════════════════════════════════════════════════
       5. REGEX EXPLAINER
       ══════════════════════════════════════════════════════════════════ */
    var ESCAPE_DESCS = {
        'd': 'Any digit (0–9)',
        'D': 'Any non-digit character',
        'w': 'Any word character (letters, digits, underscore)',
        'W': 'Any non-word character',
        's': 'Any whitespace (space, tab, newline)',
        'S': 'Any non-whitespace character',
        'n': 'Newline character (\\n)',
        'r': 'Carriage return (\\r)',
        't': 'Tab character (\\t)',
        'b': 'Word boundary',
        'B': 'Non-word boundary',
        'A': 'Start of string (\\A)',
        'Z': 'End of string (\\Z)',
        '.': 'Literal dot (.)',
        '*': 'Literal asterisk (*)',
        '+': 'Literal plus (+)',
        '?': 'Literal question mark (?)',
        '(': 'Literal open parenthesis',
        ')': 'Literal close parenthesis',
        '[': 'Literal open bracket',
        ']': 'Literal close bracket',
        '{': 'Literal open brace',
        '}': 'Literal close brace',
        '^': 'Literal caret (^)',
        '$': 'Literal dollar ($)',
        '|': 'Literal pipe (|)',
        '\\': 'Literal backslash (\\)',
        '/': 'Literal forward slash (/)',
        '0': 'Null character (\\0)'
    };

    function explainQuantifier(q) {
        if (q === '*')  return 'zero or more times (greedy)';
        if (q === '+')  return 'one or more times (greedy)';
        if (q === '?')  return 'zero or one time (optional)';
        if (q === '*?') return 'zero or more times (lazy)';
        if (q === '+?') return 'one or more times (lazy)';
        if (q === '??') return 'zero or one time (lazy)';
        var m = q.match(/^\{(\d+)\}$/);  if (m) return 'exactly ' + m[1] + ' times';
        var m2 = q.match(/^\{(\d+),\}$/); if (m2) return m2[1] + ' or more times';
        var m3 = q.match(/^\{(\d+),(\d+)\}$/); if (m3) return 'between ' + m3[1] + ' and ' + m3[2] + ' times';
        return q;
    }

    function explainCharClass(cls) {
        var inner = cls.slice(1, -1);
        var negated = inner.startsWith('^');
        if (negated) inner = inner.slice(1);
        var parts = [];
        var i = 0;
        while (i < inner.length) {
            if (inner[i] === '\\' && i + 1 < inner.length) {
                var esc = inner[i+1];
                parts.push(ESCAPE_DESCS[esc] || '\\' + esc);
                i += 2;
            } else if (i + 2 < inner.length && inner[i+1] === '-') {
                parts.push('"' + inner[i] + '" to "' + inner[i+2] + '"');
                i += 3;
            } else {
                parts.push('"' + inner[i] + '"');
                i++;
            }
        }
        return (negated ? 'Any character NOT in: ' : 'Any character in: ') + parts.join(', ');
    }

    function tokenizeRegex(pattern) {
        var tokens = [];
        var i = 0;
        var groupStack = [];
        var groupCount = 0;

        while (i < pattern.length) {
            var ch = pattern[i];

            /* Escaped sequence */
            if (ch === '\\' && i + 1 < pattern.length) {
                var next = pattern[i + 1];
                var backref = next.match(/[1-9]/);
                var desc = backref ? 'Back-reference to capture group #' + next
                    : (ESCAPE_DESCS[next] ? ESCAPE_DESCS[next] : 'Escaped character "' + next + '"');
                var raw = '\\' + next;
                i += 2;
                var quant = readQuantifier(pattern, i);
                if (quant) { desc += ', repeated ' + explainQuantifier(quant.q); i += quant.len; }
                tokens.push({ token: raw + (quant ? quant.q : ''), desc: desc });
                continue;
            }

            /* Character class */
            if (ch === '[') {
                var end = i + 1;
                if (pattern[end] === '^') end++;
                if (pattern[end] === ']') end++;
                while (end < pattern.length && pattern[end] !== ']') {
                    if (pattern[end] === '\\') end++;
                    end++;
                }
                var cls = pattern.slice(i, end + 1);
                var cdesc = explainCharClass(cls);
                i = end + 1;
                var quant2 = readQuantifier(pattern, i);
                if (quant2) { cdesc += ', repeated ' + explainQuantifier(quant2.q); i += quant2.len; }
                tokens.push({ token: cls + (quant2 ? quant2.q : ''), desc: cdesc });
                continue;
            }

            /* Group open */
            if (ch === '(') {
                var gtype = 'Capture group'; var gSkip = 1;
                if (pattern[i+1] === '?') {
                    var after = pattern[i+2] || '';
                    if (after === ':') { gtype = 'Non-capturing group'; gSkip = 3; }
                    else if (after === '=') { gtype = 'Positive lookahead — asserts what follows'; gSkip = 3; }
                    else if (after === '!') { gtype = 'Negative lookahead — asserts what does NOT follow'; gSkip = 3; }
                    else if (after === '<') {
                        var after2 = pattern[i+3] || '';
                        if (after2 === '=') { gtype = 'Positive lookbehind — asserts what precedes'; gSkip = 4; }
                        else if (after2 === '!') { gtype = 'Negative lookbehind — asserts what does NOT precede'; gSkip = 4; }
                        else {
                            var nameEnd = pattern.indexOf('>', i + 3);
                            var gname = nameEnd > 0 ? pattern.slice(i+3, nameEnd) : '?';
                            gSkip = nameEnd > 0 ? nameEnd - i + 1 : 3;
                            gtype = 'Named capture group (?<' + gname + '>)';
                            groupCount++;
                        }
                    }
                } else {
                    groupCount++;
                    gtype = 'Capture group #' + groupCount;
                }
                var raw2 = pattern.slice(i, i + gSkip);
                groupStack.push({ gtype: gtype });
                tokens.push({ token: raw2, desc: 'Open: ' + gtype });
                i += gSkip;
                continue;
            }

            /* Group close */
            if (ch === ')') {
                var ginfo = groupStack.pop() || {};
                var qdesc = '';
                i++;
                var quant3 = readQuantifier(pattern, i);
                if (quant3) { qdesc = ', repeated ' + explainQuantifier(quant3.q); i += quant3.len; }
                tokens.push({ token: ')' + (quant3 ? quant3.q : ''), desc: 'Close: ' + (ginfo.gtype || 'group') + qdesc });
                continue;
            }

            /* Anchors and special singles */
            if (ch === '^') { tokens.push({ token: '^', desc: i === 0 || pattern[i-1] === '|' ? 'Start of string (anchor)' : 'Literal caret' }); i++; continue; }
            if (ch === '$') { tokens.push({ token: '$', desc: 'End of string (anchor)' }); i++; continue; }
            if (ch === '.') {
                i++;
                var quant4 = readQuantifier(pattern, i);
                var ddesc = 'Any character except newline';
                if (quant4) { ddesc += ', repeated ' + explainQuantifier(quant4.q); i += quant4.len; }
                tokens.push({ token: '.' + (quant4 ? quant4.q : ''), desc: ddesc });
                continue;
            }
            if (ch === '|') { tokens.push({ token: '|', desc: 'Alternation — OR (match left side or right side)' }); i++; continue; }

            /* Literal */
            var litDesc = 'Literal character "' + (ch === ' ' ? 'space' : ch) + '"';
            i++;
            var quant5 = readQuantifier(pattern, i);
            if (quant5) { litDesc += ', repeated ' + explainQuantifier(quant5.q); i += quant5.len; }
            tokens.push({ token: ch + (quant5 ? quant5.q : ''), desc: litDesc });
        }

        return tokens;
    }

    function readQuantifier(s, i) {
        var ch = s[i] || '';
        if (ch === '*' || ch === '+' || ch === '?') {
            var q = ch; var len = 1;
            if (s[i+1] === '?') { q += '?'; len = 2; }
            return { q: q, len: len };
        }
        if (ch === '{') {
            var end = s.indexOf('}', i);
            if (end > i) {
                var inner = s.slice(i+1, end);
                if (/^\d+(,\d*)?$/.test(inner)) {
                    var q2 = '{' + inner + '}'; var len2 = end - i + 1;
                    if (s[end+1] === '?') { q2 += '?'; len2++; }
                    return { q: q2, len: len2 };
                }
            }
        }
        return null;
    }

    function runExplainer() {
        var pat = gvt('xp-pat');
        var outEl = el('xp-out');
        if (!outEl) return;
        if (!pat) {
            outEl.innerHTML = '<div class="rt-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/><circle cx="12" cy="12" r="10"/></svg>Enter a regex pattern to explain it</div>';
            return;
        }
        try {
            new RegExp(pat);
            var inp = el('xp-pat'); if (inp) inp.classList.remove('invalid');
        } catch(e) {
            var inp6 = el('xp-pat'); if (inp6) inp6.classList.add('invalid');
            outEl.innerHTML = '<div style="padding:14px;color:#dc2626;font-size:13px">Invalid regex: ' + htmlEsc(e.message) + '</div>';
            return;
        }
        var tokens = tokenizeRegex(pat);
        if (tokens.length === 0) { outEl.innerHTML = '<div style="padding:14px;color:var(--color-text-muted);font-size:13px">Pattern is empty.</div>'; return; }
        var html = '';
        tokens.forEach(function (t) {
            html += '<div class="rt-explain-row"><div class="rt-explain-token">' + htmlEsc(t.token) + '</div>'
                + '<div class="rt-explain-desc">' + htmlEsc(t.desc) + '</div></div>';
        });
        outEl.innerHTML = html;
    }

    /* ══════════════════════════════════════════════════════════════════
       6. REGEX VALIDATOR
       ══════════════════════════════════════════════════════════════════ */
    function runValidator() {
        var pat = gvt('vl-pat');
        var text = gv('vl-text');
        var statusEl = el('vl-status');
        var detailEl = el('vl-detail');
        if (!pat) {
            if (statusEl) statusEl.innerHTML = '';
            if (detailEl) detailEl.innerHTML = '<div class="rt-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg>Enter a pattern to validate</div>';
            return;
        }
        try {
            var flags = getActiveFlags('vl-flags');
            var re = new RegExp(pat, flags);
            var inp = el('vl-pat'); if (inp) inp.classList.remove('invalid');

            var info = [];
            info.push(['Pattern',  '/' + pat + '/' + flags]);
            info.push(['Flags',    flags || '(none)']);
            info.push(['Source',   re.source]);
            /* Count groups */
            var groupMatches = pat.match(/(?<!\\)\((?!\?[:=!])/g) || [];
            var namedMatches = pat.match(/\(\?<[^>]+>/g) || [];
            info.push(['Capture groups', groupMatches.length]);
            if (namedMatches.length) info.push(['Named groups', namedMatches.map(function(m) { return m.slice(3,-1); }).join(', ')]);
            var anchored = (pat.startsWith('^') || pat.endsWith('$'));
            info.push(['Anchored', anchored ? 'Yes (' + (pat.startsWith('^') ? '^' : '') + (pat.endsWith('$') ? '$' : '') + ')' : 'No']);
            /* Test if provided */
            if (text) {
                var result = re.test(text);
                info.push(['Test result', result ? '✓ Matches' : '✗ No match']);
            }

            if (statusEl) statusEl.innerHTML = chip('Valid pattern', 'ok');
            if (detailEl) {
                var html = '<table class="rt-cs-table">';
                info.forEach(function(row) {
                    html += '<tr><td>' + htmlEsc(String(row[0])) + '</td><td>' + htmlEsc(String(row[1])) + '</td></tr>';
                });
                html += '</table>';
                detailEl.innerHTML = html;
            }
        } catch(e) {
            var inp7 = el('vl-pat'); if (inp7) inp7.classList.add('invalid');
            if (statusEl) statusEl.innerHTML = chip('Invalid pattern', 'error');
            if (detailEl) detailEl.innerHTML = '<div style="padding:14px;color:#dc2626;font-size:13px;font-family:monospace">Error: ' + htmlEsc(e.message) + '</div>';
        }
    }

    /* ══════════════════════════════════════════════════════════════════
       7. NAMED GROUPS VIEWER
       ══════════════════════════════════════════════════════════════════ */
    function runNamedGroups() {
        var pat = gvt('ng-pat');
        var text = gv('ng-text');
        var statusEl = el('ng-status');
        var outEl = el('ng-out');
        if (!pat) {
            if (statusEl) statusEl.innerHTML = '';
            if (outEl) outEl.innerHTML = '<div class="rt-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>Enter a pattern with named groups like (?&lt;year&gt;\\d{4})</div>';
            return;
        }
        try {
            var inp = el('ng-pat'); if (inp) inp.classList.remove('invalid');
            var flags = getActiveFlags('ng-flags');
            /* Extract named group names from pattern */
            var nameRe = /\(\?<([^>]+)>/g;
            var names = [];
            var nm;
            while ((nm = nameRe.exec(pat)) !== null) names.push(nm[1]);
            if (names.length === 0) {
                if (statusEl) statusEl.innerHTML = chip('No named groups found', 'warn');
                if (outEl) outEl.innerHTML = '<div style="padding:14px;color:var(--color-text-muted);font-size:13px">Pattern has no named capture groups. Use <code>(?&lt;name&gt;...)</code> syntax.</div>';
                return;
            }
            var re = new RegExp(pat, flags.includes('g') ? flags : flags + 'g');
            var matches = [];
            var m;
            re.lastIndex = 0;
            while ((m = re.exec(text)) !== null) {
                matches.push({ index: m.index, full: m[0], named: m.groups || {} });
                if (m[0].length === 0) re.lastIndex++;
                if (matches.length > 200) break;
            }
            if (statusEl) statusEl.innerHTML = matches.length > 0 ? chip(matches.length + ' match' + (matches.length !== 1 ? 'es' : ''), 'ok') : chip('No matches', 'warn');
            if (!outEl) return;
            if (matches.length === 0) { outEl.innerHTML = '<div style="padding:14px;color:var(--color-text-muted);font-size:13px">No matches found.</div>'; return; }
            var html = '<table class="rt-ng-table"><thead><tr><th>#</th><th>Full Match</th>';
            names.forEach(function (n) { html += '<th>' + htmlEsc(n) + '</th>'; });
            html += '</tr></thead><tbody>';
            matches.forEach(function (m2, i) {
                html += '<tr><td style="color:var(--color-text-muted)">' + (i+1) + '</td><td style="color:var(--color-primary)">' + htmlEsc(m2.full) + '</td>';
                names.forEach(function (n) {
                    var val = m2.named[n];
                    html += '<td>' + (val !== undefined ? htmlEsc(val) : '<span style="color:var(--color-text-muted);font-style:italic">undefined</span>') + '</td>';
                });
                html += '</tr>';
            });
            html += '</tbody></table>';
            outEl.innerHTML = html;
        } catch(e) {
            var inp8 = el('ng-pat'); if (inp8) inp8.classList.add('invalid');
            if (statusEl) statusEl.innerHTML = chip('Error: ' + e.message.substring(0,60), 'error');
            if (outEl) outEl.innerHTML = '';
        }
    }

    /* ══════════════════════════════════════════════════════════════════
       8. REGEX GENERATOR
       ══════════════════════════════════════════════════════════════════ */
    var TEMPLATES = [
        { name: 'Email Address',         pattern: '[a-zA-Z0-9._%+\\-]+@[a-zA-Z0-9.\\-]+\\.[a-zA-Z]{2,}',  flags: 'i', desc: 'Matches standard email addresses' },
        { name: 'URL (http/https)',       pattern: 'https?:\\/\\/[^\\s/$.?#].[^\\s]*',                      flags: 'i', desc: 'Matches http and https URLs' },
        { name: 'IPv4 Address',          pattern: '\\b(?:25[0-5]|2[0-4]\\d|[01]?\\d\\d?)\\.(?:25[0-5]|2[0-4]\\d|[01]?\\d\\d?)\\.(?:25[0-5]|2[0-4]\\d|[01]?\\d\\d?)\\.(?:25[0-5]|2[0-4]\\d|[01]?\\d\\d?)\\b', flags: '', desc: 'Matches IPv4 addresses (0.0.0.0 – 255.255.255.255)' },
        { name: 'Phone (US)',             pattern: '\\+?1?[\\s.\\-]?\\(?\\d{3}\\)?[\\s.\\-]?\\d{3}[\\s.\\-]?\\d{4}', flags: '', desc: 'Flexible US phone number matcher' },
        { name: 'Date (YYYY-MM-DD)',      pattern: '\\b\\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\\d|3[01])\\b', flags: '', desc: 'ISO 8601 date format' },
        { name: 'Date (MM/DD/YYYY)',      pattern: '\\b(0[1-9]|1[0-2])\\/(0[1-9]|[12]\\d|3[01])\\/(\\d{4})\\b', flags: '', desc: 'US date format' },
        { name: 'Time (HH:MM)',           pattern: '\\b([01]?\\d|2[0-3]):([0-5]\\d)\\b',                   flags: '', desc: '24-hour time format' },
        { name: 'Hex Colour (#RRGGBB)',   pattern: '#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})\\b',                  flags: 'i', desc: 'CSS hex colour values' },
        { name: 'UUID',                   pattern: '[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}', flags: 'i', desc: 'Standard UUID v1–v5' },
        { name: 'Credit Card',           pattern: '\\b(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|6(?:011|5[0-9]{2})[0-9]{12})\\b', flags: '', desc: 'Visa, MC, AmEx, Discover' },
        { name: 'ZIP Code (US)',          pattern: '\\b\\d{5}(?:-\\d{4})?\\b',                              flags: '', desc: '5-digit or ZIP+4 format' },
        { name: 'Slug (URL-friendly)',    pattern: '^[a-z0-9]+(?:-[a-z0-9]+)*$',                            flags: '', desc: 'Lowercase letters, numbers, hyphens' },
        { name: 'Username',              pattern: '^[a-zA-Z0-9_]{3,20}$',                                  flags: '', desc: '3–20 alphanumeric chars or underscores' },
        { name: 'Strong Password',       pattern: '^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[\\W_]).{8,}$',   flags: '', desc: 'Min 8 chars, upper, lower, digit, symbol' },
        { name: 'HTML Tag',              pattern: '<([a-z][a-z0-9]*)\\b[^>]*>(.*?)<\\/\\1>',               flags: 'is', desc: 'Matches paired HTML tags with content' },
        { name: 'HTML Comment',          pattern: '<!--[\\s\\S]*?-->',                                      flags: 'g', desc: 'HTML comments' },
        { name: 'Markdown Heading',      pattern: '^(#{1,6})\\s+(.+)$',                                    flags: 'gm', desc: '# Heading through ###### Heading' },
        { name: 'Markdown Link',         pattern: '\\[([^\\[\\]]+)\\]\\(([^()]+)\\)',                      flags: 'g', desc: '[text](url) format' },
        { name: 'JSON String',           pattern: '"(?:[^"\\\\]|\\\\.)*"',                                  flags: 'g', desc: 'Double-quoted JSON string value' },
        { name: 'Integer (positive)',    pattern: '\\b[1-9]\\d*\\b',                                       flags: 'g', desc: 'Positive integer (no leading zeros)' },
        { name: 'Decimal Number',        pattern: '-?\\d+\\.\\d+',                                         flags: 'g', desc: 'Positive or negative decimal number' },
        { name: 'Whitespace (multiple)', pattern: '\\s{2,}',                                               flags: 'g', desc: 'Two or more consecutive whitespace chars' },
        { name: 'Empty Lines',           pattern: '^\\s*$',                                                 flags: 'gm', desc: 'Lines that contain only whitespace' },
        { name: 'Word (whole word)',      pattern: '\\b\\w+\\b',                                            flags: 'g', desc: 'Any whole word boundary match' },
    ];

    function renderGenerator() {
        var container = el('gen-list'); if (!container) return;
        var filter = gvt('gen-search').toLowerCase();
        var filtered = filter ? TEMPLATES.filter(function (t) { return (t.name + t.desc + t.pattern).toLowerCase().includes(filter); }) : TEMPLATES;
        if (filtered.length === 0) { container.innerHTML = '<div style="padding:20px 14px;color:var(--color-text-muted);font-size:13px">No matching patterns.</div>'; return; }
        var html = '';
        filtered.forEach(function (tpl) {
            html += '<div class="rt-pattern-card">'
                + '<div class="rt-pattern-name">' + htmlEsc(tpl.name) + '</div>'
                + '<div class="rt-pattern-regex">/' + htmlEsc(tpl.pattern) + '/' + (tpl.flags || '') + '</div>'
                + '<div class="rt-pattern-desc">' + htmlEsc(tpl.desc) + '</div>'
                + '<div class="rt-pattern-actions">'
                + '<button class="rt-btn" style="font-size:11px;padding:3px 9px" onclick="RT.useTemplate(' + JSON.stringify(tpl.pattern) + ',' + JSON.stringify(tpl.flags) + ')">Use in Tester</button>'
                + '<button class="rt-btn" style="font-size:11px;padding:3px 9px" onclick="RT.cpText(\'' + htmlEsc(tpl.pattern.replace(/'/g,"\\'")) + '\',this)">Copy Pattern</button>'
                + '</div>'
                + '</div>';
        });
        container.innerHTML = html;
    }

    function useTemplate(pattern, flags) {
        sv('t-pat', pattern);
        var inp = el('t-pat'); if (inp) inp.classList.remove('invalid');
        /* Set flags */
        var container = el('t-flags'); if (container) {
            container.querySelectorAll('.rt-flag').forEach(function (b) {
                b.classList.toggle('on', flags.includes(b.dataset.flag));
            });
        }
        switchTool('tester');
        runTester();
    }

    /* ══════════════════════════════════════════════════════════════════
       9. COMMON PATTERNS LIBRARY (same as Generator but focused on browse)
       ══════════════════════════════════════════════════════════════════ */
    function renderPatterns() {
        var container = el('pat-list'); if (!container) return;
        var html = '';
        TEMPLATES.forEach(function (tpl) {
            html += '<div class="rt-pattern-card">'
                + '<div class="rt-pattern-name">' + htmlEsc(tpl.name) + '</div>'
                + '<div class="rt-pattern-regex">/' + htmlEsc(tpl.pattern) + '/' + (tpl.flags || '') + '</div>'
                + '<div class="rt-pattern-desc">' + htmlEsc(tpl.desc) + '</div>'
                + '<div class="rt-pattern-actions">'
                + '<button class="rt-btn" style="font-size:11px;padding:3px 9px" onclick="RT.useTemplate(' + JSON.stringify(tpl.pattern) + ',' + JSON.stringify(tpl.flags) + ')">Test It</button>'
                + '<button class="rt-btn" style="font-size:11px;padding:3px 9px" onclick="RT.cpText(\'' + htmlEsc(tpl.pattern.replace(/\\/g,'\\\\').replace(/'/g,"\\'")) + '\',this)">Copy</button>'
                + '</div>'
                + '</div>';
        });
        container.innerHTML = html;
    }

    /* ══════════════════════════════════════════════════════════════════
       10. CHEAT SHEET  (static, rendered once)
       ══════════════════════════════════════════════════════════════════ */
    var CHEAT = [
        { section: 'Anchors', rows: [
            ['^',        'Start of string (or line with m flag)'],
            ['$',        'End of string (or line with m flag)'],
            ['\\b',      'Word boundary'],
            ['\\B',      'Non-word boundary'],
        ]},
        { section: 'Character Classes', rows: [
            ['.',        'Any character except newline'],
            ['\\d',      'Digit [0-9]'],
            ['\\D',      'Non-digit [^0-9]'],
            ['\\w',      'Word character [A-Za-z0-9_]'],
            ['\\W',      'Non-word character'],
            ['\\s',      'Whitespace (space, tab, newline, etc.)'],
            ['\\S',      'Non-whitespace'],
            ['[abc]',    'Any character in the set (a, b, or c)'],
            ['[^abc]',   'Any character NOT in the set'],
            ['[a-z]',    'Character range (a through z)'],
            ['[\\d\\w]', 'Union of multiple classes'],
        ]},
        { section: 'Quantifiers', rows: [
            ['*',        'Zero or more (greedy)'],
            ['+',        'One or more (greedy)'],
            ['?',        'Zero or one (optional)'],
            ['*?',       'Zero or more (lazy)'],
            ['+?',       'One or more (lazy)'],
            ['??',       'Zero or one (lazy)'],
            ['{n}',      'Exactly n times'],
            ['{n,}',     'n or more times'],
            ['{n,m}',    'Between n and m times'],
        ]},
        { section: 'Groups', rows: [
            ['(abc)',     'Capture group'],
            ['(?:abc)',   'Non-capturing group'],
            ['(?<name>)', 'Named capture group'],
            ['(?=abc)',   'Positive lookahead'],
            ['(?!abc)',   'Negative lookahead'],
            ['(?<=abc)',  'Positive lookbehind'],
            ['(?<!abc)',  'Negative lookbehind'],
            ['\\1',       'Backreference to group 1'],
            ['\\k<name>', 'Backreference to named group'],
        ]},
        { section: 'Alternation & Escaping', rows: [
            ['a|b',      'Match a or b'],
            ['\\.',      'Literal dot (escaping a metacharacter)'],
            ['\\\\',     'Literal backslash'],
            ['\\t',      'Tab character'],
            ['\\n',      'Newline character'],
            ['\\r',      'Carriage return'],
        ]},
        { section: 'Flags (JS)', rows: [
            ['g',  'Global — find all matches, not just the first'],
            ['i',  'Case-insensitive matching'],
            ['m',  'Multiline — ^ and $ match line boundaries'],
            ['s',  'Dot-all — . matches newlines too'],
            ['u',  'Unicode — enables full Unicode support'],
            ['y',  'Sticky — match only from lastIndex position'],
        ]},
        { section: 'Common Patterns', rows: [
            ['^\\s+|\\s+$',       'Trim leading/trailing whitespace'],
            ['\\b\\d{4}\\b',      'Any 4-digit number'],
            ['(?i)\\bhello\\b',   'Case-insensitive whole-word match'],
            ['^(?!.*\\bfoo\\b)',  'Line NOT containing "foo" (negative lookahead)'],
        ]},
    ];

    function renderCheatSheet() {
        var container = el('rt-cheatsheet-content'); if (!container) return;
        if (container.children.length > 0) return;
        var html = '';
        CHEAT.forEach(function (sec) {
            html += '<div class="rt-cs-section">' + sec.section + '</div>';
            html += '<table class="rt-cs-table"><tbody>';
            sec.rows.forEach(function (row) {
                html += '<tr><td>' + htmlEsc(row[0]) + '</td><td>' + htmlEsc(row[1]) + '</td></tr>';
            });
            html += '</tbody></table>';
        });
        container.innerHTML = html;
    }

    /* ── Init ────────────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        switchTool('tester');
        renderGenerator();
        renderPatterns();
        renderCheatSheet();
    });

    /* ── Public API ──────────────────────────────────────────────────── */
    return {
        switchTool:     switchTool,
        loadSample:     loadSample,
        clearCurrent:   clearCurrent,
        toggleFlag:     toggleFlag,
        cpText:         cpText,
        cpEl:           cpEl,
        useTemplate:    useTemplate,
        renderGenerator: renderGenerator,
        runTester:      runTester,
        runReplace:     runReplace,
        runExtractor:   runExtractor,
        runHighlighter: runHighlighter,
        runExplainer:   runExplainer,
        runValidator:   runValidator,
        runNamedGroups: runNamedGroups
    };
})();
