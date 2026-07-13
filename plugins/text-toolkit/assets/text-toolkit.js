/* ================================================================
   Text Toolkit — Workbench engine
   Namespace: XT
   ================================================================ */
(function () {
"use strict";

const $ = (s, r) => (r || document).querySelector(s);
const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));

/* ── DOM refs ─────────────────────────────────────────────────── */
const input      = $('#xt-input');
const fileInput  = $('#xt-file-input');
const toastEl    = $('#xt-toast');

let history = [];
const HISTORY_MAX = 40;

/* ── Utilities ────────────────────────────────────────────────── */
function toast(msg) {
    toastEl.textContent = msg;
    toastEl.classList.add('show');
    clearTimeout(toastEl._t);
    toastEl._t = setTimeout(() => toastEl.classList.remove('show'), 1800);
}

function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => toast('Copied to clipboard')).catch(() => fallbackCopy(text));
    } else fallbackCopy(text);
}
function fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); toast('Copied to clipboard'); } catch (e) { toast('Copy failed'); }
    document.body.removeChild(ta);
}

function pushHistory() {
    history.push(input.value);
    if (history.length > HISTORY_MAX) history.shift();
    $('#xt-btn-undo').disabled = history.length === 0;
}
function setText(newText, opts) {
    opts = opts || {};
    if (opts.record !== false) pushHistory();
    input.value = newText;
    render();
    if (opts.toast) toast(opts.toast);
}

function esc(s) {
    return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

const STOPWORDS = new Set(('a an the and or but if then so because as until while of at by for with about '
    + 'against between into through during before after above below to from up down in out on off over under '
    + 'again further once here there when where why how all any both each few more most other some such no nor '
    + 'not only own same than too very s t can will just don should now is are was were be been being have has had '
    + 'do does did this that these those i you he she it we they me him her us them my your his its our their').split(' '));

/* ================================================================
   TEXT ANALYSIS ENGINE
   ================================================================ */
function words(text) { const m = text.match(/[A-Za-z0-9'’_-]+/g); return m || []; }
function sentences(text) {
    const t = text.trim();
    if (!t) return [];
    const m = t.match(/[^.!?]+[.!?]+(?=\s|$)|[^.!?]+$/g);
    return (m || []).map(s => s.trim()).filter(Boolean);
}
function paragraphs(text) { return text.split(/\n\s*\n/).map(p => p.trim()).filter(Boolean); }
function lines(text) { return text === '' ? [] : text.split('\n'); }

function countSyllables(word) {
    word = word.toLowerCase().replace(/[^a-z]/g, '');
    if (!word) return 0;
    if (word.length <= 3) return 1;
    word = word.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/, '');
    word = word.replace(/^y/, '');
    const m = word.match(/[aeiouy]{1,2}/g);
    return m ? Math.max(1, m.length) : 1;
}

function analyze(text) {
    const w = words(text);
    const s = sentences(text);
    const p = paragraphs(text);
    const l = lines(text);
    const chars = text.length;
    const charsNoSpace = text.replace(/\s/g, '').length;
    const letters = (text.match(/[A-Za-z]/g) || []).length;
    const digits = (text.match(/[0-9]/g) || []).length;
    const punctuation = (text.match(/[.,!?;:'"(){}\[\]\-–—]/g) || []).length;
    const symbols = (text.match(/[^A-Za-z0-9\s]/g) || []).length;

    const totalSyllables = w.reduce((a, x) => a + countSyllables(x), 0);
    const complexWords = w.filter(x => countSyllables(x) >= 3).length;

    const wordCount = w.length || 0;
    const sentCount = s.length || 0;
    const avgWordLen = wordCount ? (w.reduce((a, x) => a + x.length, 0) / wordCount) : 0;
    const avgSentLen = sentCount ? (wordCount / sentCount) : 0;

    const lower = w.map(x => x.toLowerCase());
    const contentWords = lower.filter(x => !STOPWORDS.has(x));
    const lexicalDensity = wordCount ? (contentWords.length / wordCount) * 100 : 0;
    const uniqueWords = new Set(lower);
    const vocabRichness = wordCount ? (uniqueWords.size / wordCount) * 100 : 0;

    const freq = {};
    lower.forEach(x => { if (!STOPWORDS.has(x) && x.length > 1) freq[x] = (freq[x] || 0) + 1; });
    const mostUsed = Object.entries(freq).sort((a, b) => b[1] - a[1]).slice(0, 10);

    const readTimeSec = wordCount ? Math.round((wordCount / 200) * 60) : 0;
    const speakTimeSec = wordCount ? Math.round((wordCount / 130) * 60) : 0;

    // Readability
    const wps = sentCount ? wordCount / sentCount : 0;
    const spw = wordCount ? totalSyllables / wordCount : 0;
    const flesch = wordCount && sentCount ? (206.835 - 1.015 * wps - 84.6 * spw) : 0;
    const fog = wordCount && sentCount ? 0.4 * (wps + 100 * (complexWords / wordCount)) : 0;
    const smog = sentCount ? (1.0430 * Math.sqrt(30 * (complexWords / sentCount)) + 3.1291) : 0;
    const L = wordCount ? (letters / wordCount) * 100 : 0;
    const S = wordCount ? (sentCount / wordCount) * 100 : 0;
    const coleman = wordCount ? (0.0588 * L - 0.296 * S - 15.8) : 0;
    const ari = wordCount && sentCount ? (4.71 * (charsNoSpace / wordCount) + 0.5 * wps - 21.43) : 0;
    const fk = wordCount && sentCount ? (0.39 * wps + 11.8 * spw - 15.59) : 0;

    return {
        wordCount, sentCount, paraCount: p.length, lineCount: l.length,
        chars, charsNoSpace, letters, digits, punctuation, symbols,
        avgWordLen, avgSentLen, lexicalDensity, vocabRichness, mostUsed,
        readTimeSec, speakTimeSec, totalSyllables, complexWords,
        flesch, fog, smog, coleman, ari, fk,
        wArr: w, sArr: s, pArr: p, lArr: l,
    };
}

function fmtTime(sec) {
    if (sec < 60) return sec + 's';
    const m = Math.floor(sec / 60), s2 = sec % 60;
    return m + 'm ' + (s2 ? s2 + 's' : '');
}
function readingLevel(flesch) {
    if (flesch >= 90) return ['Very Easy', 'ok'];
    if (flesch >= 80) return ['Easy', 'ok'];
    if (flesch >= 70) return ['Fairly Easy', 'ok'];
    if (flesch >= 60) return ['Standard', ''];
    if (flesch >= 50) return ['Fairly Difficult', 'warn'];
    if (flesch >= 30) return ['Difficult', 'warn'];
    return ['Very Confusing', 'err'];
}
function gradeFromFK(fk) {
    if (fk <= 0) return 'K';
    if (fk >= 17) return 'Graduate';
    return Math.round(fk) + 'th grade';
}

function ngrams(wordsArr, n) {
    const out = {};
    for (let i = 0; i <= wordsArr.length - n; i++) {
        const g = wordsArr.slice(i, i + n).join(' ');
        out[g] = (out[g] || 0) + 1;
    }
    return Object.entries(out).sort((a, b) => b[1] - a[1]).slice(0, 10);
}

/* ── Render Analysis tab ──────────────────────────────────────── */
function metricCard(label, value, sub, cls) {
    return `<div class="xt-metric-card ${cls || ''}"><div class="xt-metric-label">${esc(label)}</div>`
        + `<div class="xt-metric-value">${esc(value)}</div>${sub ? `<div class="xt-metric-sub">${esc(sub)}</div>` : ''}</div>`;
}
function group(title, cardsHtml) {
    return `<div class="xt-metric-group"><div class="xt-metric-group-title">${esc(title)}</div><div class="xt-metric-grid">${cardsHtml}</div></div>`;
}

function renderAnalysis() {
    const text = input.value;
    const a = analyze(text);
    const kw = ($('#xt-keyword').value || '').trim().toLowerCase();
    const nSize = parseInt($('#xt-ngram-size').value, 10) || 2;

    let html = '';

    html += group('Counters', [
        metricCard('Words', a.wordCount),
        metricCard('Characters', a.chars),
        metricCard('Characters (no spaces)', a.charsNoSpace),
        metricCard('Sentences', a.sentCount),
        metricCard('Paragraphs', a.paraCount),
        metricCard('Lines', a.lineCount),
        metricCard('Letters', a.letters),
        metricCard('Digits', a.digits),
        metricCard('Symbols', a.symbols),
        metricCard('Punctuation', a.punctuation),
    ].join(''));

    let kwHtml = '';
    if (kw) {
        const re = new RegExp('\\b' + kw.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'gi');
        const kwCount = (text.match(re) || []).length;
        const density = a.wordCount ? ((kwCount / a.wordCount) * 100).toFixed(2) : '0.00';
        kwHtml = metricCard('Keyword occurrences', kwCount, '"' + kw + '"')
            + metricCard('Keyword density', density + '%', '"' + kw + '"');
    } else {
        kwHtml = metricCard('Keyword density', '—', 'enter a keyword above')
            + metricCard('Keyword frequency', '—', 'enter a keyword above');
    }

    html += group('Reading Metrics', [
        metricCard('Reading time', fmtTime(a.readTimeSec), '~200 wpm'),
        metricCard('Speaking time', fmtTime(a.speakTimeSec), '~130 wpm'),
        metricCard('Avg word length', a.avgWordLen.toFixed(2), 'characters'),
        metricCard('Avg sentence length', a.avgSentLen.toFixed(2), 'words'),
        metricCard('Lexical density', a.lexicalDensity.toFixed(1) + '%', 'content words'),
        metricCard('Vocabulary richness', a.vocabRichness.toFixed(1) + '%', 'unique / total'),
        kwHtml,
    ].join(''));

    const mostUsedHtml = a.mostUsed.length
        ? `<div class="xt-word-chip-list">${a.mostUsed.map(([w, c]) => `<span class="xt-word-chip">${esc(w)} <b>${c}</b></span>`).join('')}</div>`
        : `<div class="xt-metric-sub">No repeated words yet.</div>`;
    const ngramList = ngrams(a.wArr.map(x => x.toLowerCase()), nSize);
    const ngramHtml = ngramList.length
        ? `<div class="xt-word-chip-list">${ngramList.map(([g, c]) => `<span class="xt-word-chip">${esc(g)} <b>${c}</b></span>`).join('')}</div>`
        : `<div class="xt-metric-sub">Not enough words for ${nSize}-grams.</div>`;

    html += `<div class="xt-metric-group"><div class="xt-metric-group-title">Most Used Words &amp; N-Grams</div>
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:18px">
        <div class="xt-metric-card" style="border-left-color:var(--color-info)"><div class="xt-metric-label">Most used words (top 10)</div>${mostUsedHtml}</div>
        <div class="xt-metric-card" style="border-left-color:var(--color-info)"><div class="xt-metric-label">${nSize}-Gram analyzer (top 10)</div>${ngramHtml}</div>
        </div></div>`;

    const [levelLabel, levelCls] = readingLevel(a.flesch);
    html += group('Readability', [
        metricCard('Flesch Reading Ease', a.wordCount ? a.flesch.toFixed(1) : '—', levelLabel, levelCls || 'ok'),
        metricCard('Gunning Fog Index', a.wordCount ? a.fog.toFixed(1) : '—', 'grade level'),
        metricCard('SMOG Index', a.sentCount ? a.smog.toFixed(1) : '—', 'grade level'),
        metricCard('Coleman-Liau Index', a.wordCount ? a.coleman.toFixed(1) : '—', 'grade level'),
        metricCard('Automated Readability Index', a.wordCount ? a.ari.toFixed(1) : '—', 'grade level'),
        metricCard('Reading Grade', a.wordCount ? gradeFromFK(a.fk) : '—', 'Flesch-Kincaid'),
        metricCard('Reading Difficulty', a.wordCount ? levelLabel : '—', 'based on Flesch score', levelCls || 'ok'),
    ].join(''));

    $('#xt-analysis-groups').innerHTML = html;
    $('#cnt-analysis').textContent = '27';
}

/* ================================================================
   ACTIONS — Format & Case, Cleanup & Utilities (transform Workbench text)
   ================================================================ */
function icon(paths) { return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${paths}</svg>`; }
const ICONS = {
    case: '<path d="M4 20V4h4l4 16h4l4-16"/>',
    space: '<path d="M4 6h16"/><path d="M4 18h16"/><path d="M9 12h6"/>',
    line: '<line x1="3" y1="12" x2="21" y2="12"/>',
    reverse: '<path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
    wrap: '<path d="M3 6h18"/><path d="M3 12h13a3 3 0 0 1 0 6h-4"/><path d="M15 15l3 3-3 3"/>',
    braces: '<path d="M8 3a2 2 0 0 0-2 2v3a2 2 0 0 1-2 2H3v4h1a2 2 0 0 1 2 2v3a2 2 0 0 0 2 2"/><path d="M16 3a2 2 0 0 1 2 2v3a2 2 0 0 0 2 2h1v4h-1a2 2 0 0 0-2 2v3a2 2 0 0 1-2 2"/>',
    dash: '<line x1="5" y1="12" x2="19" y2="12"/>',
    dot: '<circle cx="12" cy="12" r="1"/>',
    remove: '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
    dup: '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
    replace: '<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
    clean: '<path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
    split: '<path d="M6 3v18"/><path d="M18 3v18"/><path d="M6 12h12"/>',
    merge: '<path d="M12 3v18"/><path d="M5 8l7-5 7 5"/><path d="M5 16l7 5 7-5"/>',
    list: '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',
    sort: '<path d="M11 5h10"/><path d="M11 9h7"/><path d="M11 13h4"/><path d="M3 17l3 3 3-3"/><path d="M6 18V4"/>',
    shuffle: '<polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/>',
};

function toTitle(w) { return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase(); }
function splitWords(text) { return (text.match(/[A-Za-z0-9]+/g) || []); }
function toCaseStyle(text, style) {
    return lines(text).map(line => {
        const ws = splitWords(line).flatMap(w => w.replace(/([a-z0-9])([A-Z])/g, '$1 $2').split(/\s+/)).filter(Boolean);
        if (!ws.length) return line;
        switch (style) {
            case 'camel': return ws.map((w, i) => i === 0 ? w.toLowerCase() : toTitle(w)).join('');
            case 'pascal': return ws.map(toTitle).join('');
            case 'snake': return ws.map(w => w.toLowerCase()).join('_');
            case 'kebab': return ws.map(w => w.toLowerCase()).join('-');
            case 'dot': return ws.map(w => w.toLowerCase()).join('.');
            case 'constant': return ws.map(w => w.toUpperCase()).join('_');
            case 'path': return ws.map(w => w.toLowerCase()).join('/');
            case 'header': return ws.map(toTitle).join('-');
            case 'train': return ws.map(toTitle).join('-');
            default: return line;
        }
    }).join('\n');
}

const ACTIONS = [
    /* ---- Format & Case tab ---- */
    { id: 'upper', tab: 'format', group: 'Case Styles', name: 'Uppercase Converter', hint: 'ALL CAPS', icon: ICONS.case, run: t => t.toUpperCase() },
    { id: 'lower', tab: 'format', group: 'Case Styles', name: 'Lowercase Converter', hint: 'all lowercase', icon: ICONS.case, run: t => t.toLowerCase() },
    { id: 'sentence', tab: 'format', group: 'Case Styles', name: 'Sentence Case Converter', hint: 'First letter of each sentence', icon: ICONS.case, run: t => t.toLowerCase().replace(/(^\s*[a-z])|([.!?]\s+[a-z])/g, m => m.toUpperCase()) },
    { id: 'title', tab: 'format', group: 'Case Styles', name: 'Title Case Converter', hint: 'Capitalize Every Word', icon: ICONS.case, run: t => t.replace(/[A-Za-z']+/g, toTitle) },
    { id: 'toggle', tab: 'format', group: 'Case Styles', name: 'Toggle Case Converter', hint: 'sWAP eXISTING cASE', icon: ICONS.case, run: t => t.replace(/[A-Za-z]/g, c => c === c.toUpperCase() ? c.toLowerCase() : c.toUpperCase()) },
    { id: 'invert', tab: 'format', group: 'Case Styles', name: 'Invert Case Converter', hint: 'Same as toggle, per character', icon: ICONS.case, run: t => t.replace(/[A-Za-z]/g, c => c === c.toUpperCase() ? c.toLowerCase() : c.toUpperCase()) },
    { id: 'alt', tab: 'format', group: 'Case Styles', name: 'Alternating Case Converter', hint: 'aLtErNaTiNg cHaRaCtErS', icon: ICONS.case, run: t => { let i = 0; return t.replace(/[A-Za-z]/g, c => (i++ % 2 === 0) ? c.toLowerCase() : c.toUpperCase()); } },
    { id: 'randcase', tab: 'format', group: 'Case Styles', name: 'Random Case Generator', hint: 'RaNdoM per character', icon: ICONS.case, run: t => t.replace(/[A-Za-z]/g, c => Math.random() > 0.5 ? c.toUpperCase() : c.toLowerCase()) },
    { id: 'extraspace', tab: 'format', group: 'Whitespace Cleanup', name: 'Remove Extra Spaces', hint: 'Collapse repeated spaces', icon: ICONS.space, run: t => t.replace(/[ \t]+/g, ' ') },
    { id: 'trim', tab: 'format', group: 'Whitespace Cleanup', name: 'Trim Text', hint: 'Trim start/end of every line', icon: ICONS.space, run: t => lines(t).map(l => l.trim()).join('\n').trim() },
    { id: 'normalize', tab: 'format', group: 'Whitespace Cleanup', name: 'Normalize Whitespace', hint: 'Single spaces, trimmed lines', icon: ICONS.space, run: t => t.split('\n').map(l => l.trim().replace(/\s+/g, ' ')).join('\n') },
    { id: 'blanklines', tab: 'format', group: 'Whitespace Cleanup', name: 'Remove Blank Lines', hint: 'Drop empty lines', icon: ICONS.space, run: t => lines(t).filter(l => l.trim() !== '').join('\n') },
    { id: 'duplines', tab: 'format', group: 'Line Operations', name: 'Remove Duplicate Lines', hint: 'Keep first occurrence', icon: ICONS.dup, run: t => { const seen = new Set(); return lines(t).filter(l => { if (seen.has(l)) return false; seen.add(l); return true; }).join('\n'); } },
    { id: 'sortaz', tab: 'format', group: 'Line Operations', name: 'Sort Lines A-Z', hint: 'Alphabetical order', icon: ICONS.sort, run: t => lines(t).slice().sort((a, b) => a.localeCompare(b)).join('\n') },
    { id: 'sortza', tab: 'format', group: 'Line Operations', name: 'Sort Lines Z-A', hint: 'Reverse alphabetical', icon: ICONS.sort, run: t => lines(t).slice().sort((a, b) => b.localeCompare(a)).join('\n') },
    { id: 'revtext', tab: 'format', group: 'Reversal', name: 'Reverse Text', hint: 'Reverse every character', icon: ICONS.reverse, run: t => t.split('').reverse().join('') },
    { id: 'revwords', tab: 'format', group: 'Reversal', name: 'Reverse Words', hint: 'Reverse word order', icon: ICONS.reverse, run: t => lines(t).map(l => l.split(/\s+/).filter(Boolean).reverse().join(' ')).join('\n') },
    { id: 'revsentences', tab: 'format', group: 'Reversal', name: 'Reverse Sentences', hint: 'Reverse sentence order', icon: ICONS.reverse, run: t => sentences(t).reverse().join(' ') },
    { id: 'revlines', tab: 'format', group: 'Reversal', name: 'Reverse Lines', hint: 'Reverse line order', icon: ICONS.reverse, run: t => lines(t).reverse().join('\n') },
    { id: 'wrap', tab: 'format', group: 'Wrapping', name: 'Text Wrapper', hint: 'Wrap to a max line width', icon: ICONS.wrap,
      params: [{ key: 'width', label: 'Max width (characters)', type: 'number', default: 80 }],
      run: (t, p) => { const w = Math.max(10, parseInt(p.width, 10) || 80); return t.split('\n').map(line => { const words = line.split(' '); let out = [], cur = ''; words.forEach(word => { if ((cur + ' ' + word).trim().length > w) { out.push(cur.trim()); cur = word; } else cur = (cur + ' ' + word).trim(); }); if (cur) out.push(cur); return out.join('\n'); }).join('\n'); } },
    { id: 'camel', tab: 'format', group: 'Case Conversion (Identifier Styles)', name: 'Camel Case Converter', hint: 'camelCase', icon: ICONS.braces, run: t => toCaseStyle(t, 'camel') },
    { id: 'pascal', tab: 'format', group: 'Case Conversion (Identifier Styles)', name: 'Pascal Case Converter', hint: 'PascalCase', icon: ICONS.braces, run: t => toCaseStyle(t, 'pascal') },
    { id: 'snake', tab: 'format', group: 'Case Conversion (Identifier Styles)', name: 'Snake Case Converter', hint: 'snake_case', icon: ICONS.dash, run: t => toCaseStyle(t, 'snake') },
    { id: 'kebab', tab: 'format', group: 'Case Conversion (Identifier Styles)', name: 'Kebab Case Converter', hint: 'kebab-case', icon: ICONS.dash, run: t => toCaseStyle(t, 'kebab') },
    { id: 'dotcase', tab: 'format', group: 'Case Conversion (Identifier Styles)', name: 'Dot Case Converter', hint: 'dot.case', icon: ICONS.dot, run: t => toCaseStyle(t, 'dot') },
    { id: 'constant', tab: 'format', group: 'Case Conversion (Identifier Styles)', name: 'Constant Case Converter', hint: 'CONSTANT_CASE', icon: ICONS.dash, run: t => toCaseStyle(t, 'constant') },
    { id: 'pathcase', tab: 'format', group: 'Case Conversion (Identifier Styles)', name: 'Path Case Converter', hint: 'path/case', icon: ICONS.dash, run: t => toCaseStyle(t, 'path') },
    { id: 'headercase', tab: 'format', group: 'Case Conversion (Identifier Styles)', name: 'Header Case Converter', hint: 'Header-Case', icon: ICONS.dash, run: t => toCaseStyle(t, 'header') },
    { id: 'traincase', tab: 'format', group: 'Case Conversion (Identifier Styles)', name: 'Train Case Converter', hint: 'Train-Case', icon: ICONS.dash, run: t => toCaseStyle(t, 'train') },

    /* ---- Cleanup & Utilities tab ---- */
    { id: 'rmnum', tab: 'cleanup', group: 'Character Cleanup', name: 'Remove Numbers', hint: 'Strip all digits', icon: ICONS.clean, run: t => t.replace(/[0-9]/g, '') },
    { id: 'rmletters', tab: 'cleanup', group: 'Character Cleanup', name: 'Remove Letters', hint: 'Strip all letters', icon: ICONS.clean, run: t => t.replace(/[A-Za-z]/g, '') },
    { id: 'rmspecial', tab: 'cleanup', group: 'Character Cleanup', name: 'Remove Special Characters', hint: 'Keep letters, numbers, spaces', icon: ICONS.clean, run: t => t.replace(/[^A-Za-z0-9\s]/g, '') },
    { id: 'rmpunct', tab: 'cleanup', group: 'Character Cleanup', name: 'Remove Punctuation', hint: 'Strip punctuation marks', icon: ICONS.clean, run: t => t.replace(/[.,\/#!$%\^&\*;:{}=\-_`~()"'?!]/g, '') },
    { id: 'rmhtml', tab: 'cleanup', group: 'Character Cleanup', name: 'Remove HTML Tags', hint: 'Strip <tags>', icon: ICONS.clean, run: t => t.replace(/<[^>]*>/g, '') },
    { id: 'rmemoji', tab: 'cleanup', group: 'Character Cleanup', name: 'Remove Emojis', hint: 'Strip emoji characters', icon: ICONS.clean, run: t => t.replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}\u{1F1E6}-\u{1F1FF}]/gu, '') },
    { id: 'dupwords', tab: 'cleanup', group: 'Duplicate Cleanup', name: 'Remove Duplicate Words', hint: 'Per line, keep first', icon: ICONS.dup, run: t => lines(t).map(l => { const seen = new Set(); return l.split(/(\s+)/).filter(tok => { if (/^\s+$/.test(tok) || tok === '') return true; const k = tok.toLowerCase(); if (seen.has(k)) return false; seen.add(k); return true; }).join(''); }).join('\n') },
    { id: 'dupsentences', tab: 'cleanup', group: 'Duplicate Cleanup', name: 'Remove Duplicate Sentences', hint: 'Keep first occurrence', icon: ICONS.dup, run: t => { const seen = new Set(); return sentences(t).filter(s => { const k = s.toLowerCase().trim(); if (seen.has(k)) return false; seen.add(k); return true; }).join(' '); } },
    { id: 'dupparas', tab: 'cleanup', group: 'Duplicate Cleanup', name: 'Remove Duplicate Paragraphs', hint: 'Keep first occurrence', icon: ICONS.dup, run: t => { const seen = new Set(); return paragraphs(t).filter(p => { const k = p.toLowerCase().trim(); if (seen.has(k)) return false; seen.add(k); return true; }).join('\n\n'); } },
    { id: 'findreplace', tab: 'cleanup', group: 'Find &amp; Replace', name: 'Find and Replace', hint: 'Single find/replace pair', icon: ICONS.replace,
      params: [{ key: 'find', label: 'Find', type: 'text' }, { key: 'replace', label: 'Replace with', type: 'text' }, { key: 'ci', label: 'Case-insensitive', type: 'checkbox', default: true }],
      run: (t, p) => { if (!p.find) return t; const re = new RegExp(p.find.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), p.ci ? 'gi' : 'g'); return t.replace(re, p.replace || ''); } },
    { id: 'batchreplace', tab: 'cleanup', group: 'Find &amp; Replace', name: 'Batch Find and Replace', hint: 'One "find=>replace" per line', icon: ICONS.replace,
      params: [{ key: 'pairs', label: 'Rules — one per line, format: find=>replace', type: 'textarea', placeholder: 'foo=>bar\nhello=>hi' }],
      run: (t, p) => { const rules = (p.pairs || '').split('\n').map(l => l.split('=>')).filter(r => r.length === 2); let out = t; rules.forEach(([f, r]) => { if (f.trim()) out = out.split(f.trim()).join(r.trim()); }); return out; } },
    { id: 'sanitize', tab: 'cleanup', group: 'Sanitize', name: 'Text Sanitizer', hint: 'Strip control chars, normalize spaces', icon: ICONS.clean, run: t => t.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '').replace(/[ \t]+/g, ' ').split('\n').map(l => l.trim()).join('\n') },
    { id: 'splitter', tab: 'cleanup', group: 'Utilities', name: 'Text Splitter', hint: 'Split by a delimiter, one part per line', icon: ICONS.split,
      params: [{ key: 'delim', label: 'Delimiter', type: 'text', default: ',' }],
      run: (t, p) => t.split(p.delim || ',').map(s => s.trim()).filter(Boolean).join('\n') },
    { id: 'merger', tab: 'cleanup', group: 'Utilities', name: 'Text Merger', hint: 'Join all lines with a separator', icon: ICONS.merge,
      params: [{ key: 'sep', label: 'Separator', type: 'text', default: ' ' }],
      run: (t, p) => lines(t).filter(l => l !== '').join(p.sep ?? ' ') },
    { id: 'chunker', tab: 'cleanup', group: 'Utilities', name: 'Text Chunker', hint: 'Split into fixed-size chunks', icon: ICONS.split,
      params: [{ key: 'size', label: 'Chunk size (characters)', type: 'number', default: 100 }],
      run: (t, p) => { const size = Math.max(1, parseInt(p.size, 10) || 100); const out = []; for (let i = 0; i < t.length; i += size) out.push(t.slice(i, i + size)); return out.join('\n\n---\n\n'); } },
    { id: 'bullets', tab: 'cleanup', group: 'Utilities', name: 'Bullet List Generator', hint: 'Prefix every line with "- "', icon: ICONS.list, run: t => lines(t).filter(l => l.trim()).map(l => '- ' + l.trim()).join('\n') },
    { id: 'numbered', tab: 'cleanup', group: 'Utilities', name: 'Numbered List Generator', hint: 'Prefix every line with "1. "', icon: ICONS.list, run: t => lines(t).filter(l => l.trim()).map((l, i) => (i + 1) + '. ' + l.trim()).join('\n') },
    { id: 'csvtolist', tab: 'cleanup', group: 'Utilities', name: 'CSV to List', hint: 'Comma-separated → one per line', icon: ICONS.list,
      params: [{ key: 'delim', label: 'CSV delimiter', type: 'text', default: ',' }],
      run: (t, p) => t.split(p.delim || ',').map(s => s.trim()).filter(Boolean).join('\n') },
    { id: 'listtocsv', tab: 'cleanup', group: 'Utilities', name: 'List to CSV', hint: 'One per line → comma-separated', icon: ICONS.list,
      params: [{ key: 'delim', label: 'Output delimiter', type: 'text', default: ',' }],
      run: (t, p) => lines(t).map(l => l.trim()).filter(Boolean).join((p.delim || ',') + ' ') },
    { id: 'alphabetizer', tab: 'cleanup', group: 'Utilities', name: 'Alphabetizer', hint: 'Sort every word A-Z', icon: ICONS.sort, run: t => words(t).slice().sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' })).join(' ') },
    { id: 'shuffle', tab: 'cleanup', group: 'Utilities', name: 'Text Shuffle Tool', hint: 'Randomize line order', icon: ICONS.shuffle, run: t => { const arr = lines(t); for (let i = arr.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [arr[i], arr[j]] = [arr[j], arr[i]]; } return arr.join('\n'); } },
    { id: 'dedupwords', tab: 'cleanup', group: 'Utilities', name: 'Text Deduplicator', hint: 'Remove duplicate words, whole text', icon: ICONS.dup, run: t => { const seen = new Set(); return words(t).filter(w => { const k = w.toLowerCase(); if (seen.has(k)) return false; seen.add(k); return true; }).join(' '); } },
];

function renderActionGrid(tab, containerId, filter) {
    const groups = {};
    ACTIONS.filter(a => a.tab === tab).forEach(a => { (groups[a.group] = groups[a.group] || []).push(a); });
    const f = (filter || '').toLowerCase();
    let html = '', total = 0, shown = 0;
    Object.entries(groups).forEach(([gName, acts]) => {
        const filtered = f ? acts.filter(a => a.name.toLowerCase().includes(f) || a.hint.toLowerCase().includes(f)) : acts;
        total += acts.length;
        if (!filtered.length) return;
        shown += filtered.length;
        html += `<div class="xt-action-group"><div class="xt-action-group-title">${gName} <span style="opacity:.6">(${filtered.length})</span></div><div class="xt-action-grid">`;
        filtered.forEach(a => {
            const card = a.params
                ? `<div class="xt-action-card has-params" id="ac-${a.id}">
                     <div class="xt-action-top" onclick="XT.toggleParams('${a.id}')">
                       <div class="xt-action-icon">${icon(a.icon)}</div>
                       <div class="xt-action-info"><div class="xt-action-name">${esc(a.name)}</div><div class="xt-action-hint">${esc(a.hint)}</div></div>
                     </div>
                     <div class="xt-action-params">
                       ${a.params.map(p => paramField(a.id, p)).join('')}
                       <div class="xt-action-run-row"><button class="xt-btn xt-btn-primary xt-btn-sm" onclick="XT.runAction('${a.id}')">Run</button></div>
                     </div>
                   </div>`
                : `<button type="button" class="xt-action-card" id="ac-${a.id}" onclick="XT.runAction('${a.id}')">
                     <div class="xt-action-icon">${icon(a.icon)}</div>
                     <div class="xt-action-info"><div class="xt-action-name">${esc(a.name)}</div><div class="xt-action-hint">${esc(a.hint)}</div></div>
                   </button>`;
            html += card;
        });
        html += `</div></div>`;
    });
    if (!shown && f) html = `<div class="xt-no-results">No tools match "${esc(filter)}".</div>`;
    $('#' + containerId).innerHTML = html;
    return total;
}
function paramField(actionId, p) {
    const id = `p-${actionId}-${p.key}`;
    if (p.type === 'textarea') return `<label>${esc(p.label)}<textarea id="${id}" placeholder="${esc(p.placeholder || '')}"></textarea></label>`;
    if (p.type === 'checkbox') return `<label style="flex-direction:row"><input type="checkbox" id="${id}" ${p.default ? 'checked' : ''} style="width:auto"> ${esc(p.label)}</label>`;
    return `<label>${esc(p.label)}<input type="${p.type === 'number' ? 'number' : 'text'}" id="${id}" value="${p.default !== undefined ? esc(p.default) : ''}" placeholder="${esc(p.placeholder || '')}"></label>`;
}
function collectParams(action) {
    const out = {};
    (action.params || []).forEach(p => {
        const el = $('#p-' + action.id + '-' + p.key);
        if (!el) return;
        out[p.key] = p.type === 'checkbox' ? el.checked : el.value;
    });
    return out;
}

function runAction(id) {
    const action = ACTIONS.find(a => a.id === id);
    if (!action) return;
    const card = $('#ac-' + id);
    if (action.params && !card.classList.contains('_ran-once')) {
        // params already visible; proceed to run
    }
    const params = collectParams(action);
    try {
        const result = action.run(input.value, params);
        setText(result, { toast: action.name + ' applied' });
        if (card) { card.classList.add('flash'); setTimeout(() => card.classList.remove('flash'), 600); }
    } catch (e) {
        toast('Error running ' + action.name);
        console.error(e);
    }
}
function toggleParams(id) {
    const card = $('#ac-' + id);
    if (card) card.classList.toggle('open');
}

/* ================================================================
   EXTRACTION
   ================================================================ */
const EXTRACTORS = [
    { id: 'emails', name: 'Emails', re: /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g },
    { id: 'urls', name: 'URLs', re: /\bhttps?:\/\/[^\s<>"']+/g },
    { id: 'domains', name: 'Domains', re: /\b(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}\b/gi },
    { id: 'phones', name: 'Phone Numbers', re: /(?:\+?\d{1,3}[\s.-]?)?(?:\(\d{2,4}\)[\s.-]?)?\d{3,4}[\s.-]?\d{3,4}(?:[\s.-]?\d{2,4})?/g, filter: m => m.replace(/\D/g, '').length >= 7 },
    { id: 'hashtags', name: 'Hashtags', re: /#[A-Za-z0-9_]+/g },
    { id: 'mentions', name: 'Mentions', re: /@[A-Za-z0-9_]+/g },
    { id: 'numbers', name: 'Numbers', re: /-?\b\d[\d,]*(?:\.\d+)?\b/g },
    { id: 'dates', name: 'Dates', re: /\b(\d{4}[-\/]\d{1,2}[-\/]\d{1,2}|\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}|(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+\d{1,2},?\s+\d{4})\b/gi },
    { id: 'addresses', name: 'Addresses (heuristic)', re: /\b\d{1,5}\s+[A-Za-z0-9.'-]+\s+(?:Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Lane|Ln|Drive|Dr|Court|Ct|Way|Circle|Place|Pl)\b[^\n,]*/gi },
    { id: 'ips', name: 'IP Addresses', re: /\b(?:(?:25[0-5]|2[0-4]\d|1?\d?\d)\.){3}(?:25[0-5]|2[0-4]\d|1?\d?\d)\b|\b(?:[A-F0-9]{1,4}:){2,7}[A-F0-9]{1,4}\b/gi },
    { id: 'handles', name: 'Social Media Handles', re: /(?<![\w@])@[A-Za-z][A-Za-z0-9_]{2,}/g },
];

function renderExtraction() {
    const text = input.value;
    let html = '', activeCount = 0;
    EXTRACTORS.forEach(ex => {
        let matches = Array.from(new Set((text.match(ex.re) || [])));
        if (ex.filter) matches = matches.filter(ex.filter);
        if (matches.length) activeCount++;
        html += `<div class="xt-ext-group ${matches.length ? '' : 'collapsed'}" data-ex="${ex.id}">
            <div class="xt-ext-head" onclick="XT.toggleExtract('${ex.id}')">
                <div class="xt-ext-head-left">${esc(ex.name)}</div>
                <div style="display:flex;align-items:center;gap:8px">
                    <span class="xt-ext-count ${matches.length ? '' : 'zero'}">${matches.length}</span>
                    <svg class="xt-ext-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
            </div>
            <div class="xt-ext-body">${matches.length ? matches.map(m => extractItem(m)).join('') : `<span class="xt-ext-empty">No ${esc(ex.name.toLowerCase())} found.</span>`}</div>
        </div>`;
    });
    $('#xt-extract-groups').innerHTML = html;
    $('#cnt-extract').textContent = '11';
    window._xtExtractCache = EXTRACTORS.map(ex => { let m = Array.from(new Set((text.match(ex.re) || []))); if (ex.filter) m = m.filter(ex.filter); return m; }).flat();
}
function extractItem(val) {
    return `<span class="xt-ext-item">${esc(val)}<button onclick="XT.copyOne(this,'${esc(val).replace(/'/g, "\\'")}')" title="Copy">${icon('<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>')}</button></span>`;
}
function toggleExtract(id) {
    const el = document.querySelector(`.xt-ext-group[data-ex="${id}"]`);
    if (el) el.classList.toggle('collapsed');
}

/* ================================================================
   COMPARISON
   ================================================================ */
const CMP_TOOLS = [
    { id: 'compare', name: 'Text Compare' },
    { id: 'sidebyside', name: 'Side-by-Side Compare' },
    { id: 'similarity', name: 'Similarity Checker' },
    { id: 'difference', name: 'Difference Checker' },
    { id: 'duplicate', name: 'Duplicate Detector' },
    { id: 'paragraph', name: 'Paragraph Compare' },
    { id: 'sentence', name: 'Sentence Compare' },
    { id: 'word', name: 'Word Compare' },
];
let cmpActive = 'compare';

function lcsDiff(a, b) {
    const n = a.length, m = b.length;
    const dp = Array.from({ length: n + 1 }, () => new Uint32Array(m + 1));
    for (let i = n - 1; i >= 0; i--) for (let j = m - 1; j >= 0; j--)
        dp[i][j] = a[i] === b[j] ? dp[i + 1][j + 1] + 1 : Math.max(dp[i + 1][j], dp[i][j + 1]);
    const ops = [];
    let i = 0, j = 0;
    while (i < n && j < m) {
        if (a[i] === b[j]) { ops.push(['same', a[i]]); i++; j++; }
        else if (dp[i + 1][j] >= dp[i][j + 1]) { ops.push(['del', a[i]]); i++; }
        else { ops.push(['add', b[j]]); j++; }
    }
    while (i < n) { ops.push(['del', a[i]]); i++; }
    while (j < m) { ops.push(['add', b[j]]); j++; }
    return ops;
}
function jaccard(a, b) {
    const sa = new Set(a.map(x => x.toLowerCase()));
    const sb = new Set(b.map(x => x.toLowerCase()));
    const inter = [...sa].filter(x => sb.has(x)).length;
    const union = new Set([...sa, ...sb]).size;
    return union ? inter / union : 1;
}
function levenshtein(a, b) {
    const n = a.length, m = b.length;
    if (!n) return m; if (!m) return n;
    const dp = Array.from({ length: n + 1 }, (_, i) => [i, ...Array(m).fill(0)]);
    for (let j = 0; j <= m; j++) dp[0][j] = j;
    for (let i = 1; i <= n; i++) for (let j = 1; j <= m; j++)
        dp[i][j] = a[i - 1] === b[j - 1] ? dp[i - 1][j - 1] : 1 + Math.min(dp[i - 1][j], dp[i][j - 1], dp[i - 1][j - 1]);
    return dp[n][m];
}

function renderCompareTools() {
    $('#xt-compare-tools').innerHTML = CMP_TOOLS.map(t =>
        `<button type="button" class="xt-cmp-tool ${t.id === cmpActive ? 'active' : ''}" data-cmp="${t.id}">${t.name}</button>`).join('');
    $('#cnt-compare').textContent = '8';
}
function runCompare() {
    const a = $('#xt-cmp-a').value, b = $('#xt-cmp-b').value;
    const out = $('#xt-compare-result');
    if (!a && !b) { out.innerHTML = `<div class="xt-empty" style="padding:30px"><p>Paste text into A and B (or load from the Workbench) to compare.</p></div>`; return; }

    if (cmpActive === 'duplicate') {
        const seen = {}; lines(a).forEach((l, idx) => { const k = l.trim().toLowerCase(); if (!k) return; (seen[k] = seen[k] || []).push(idx + 1); });
        const dups = Object.entries(seen).filter(([, idxs]) => idxs.length > 1);
        out.innerHTML = `<div class="xt-cmp-summary"><div class="xt-cmp-stat"><div class="xt-cmp-stat-val">${dups.length}</div><div class="xt-cmp-stat-label">Duplicate lines (in Text A)</div></div></div>
            <div class="xt-diff-body">${dups.length ? dups.map(([l, idxs]) => `<div><span class="xt-diff-add">${esc(l)}</span> — lines ${idxs.join(', ')}</div>`).join('') : 'No duplicate lines found in Text A.'}</div>`;
        return;
    }

    let unitA, unitB, joiner;
    if (cmpActive === 'paragraph') { unitA = paragraphs(a); unitB = paragraphs(b); joiner = '\n\n'; }
    else if (cmpActive === 'sentence') { unitA = sentences(a); unitB = sentences(b); joiner = ' '; }
    else if (cmpActive === 'word') { unitA = words(a); unitB = words(b); joiner = ' '; }
    else { unitA = lines(a); unitB = lines(b); joiner = '\n'; }

    const wA = words(a), wB = words(b);
    const sim = a && b ? (jaccard(wA, wB) * 100) : 0;
    const lev = levenshtein(a, b);
    const maxLen = Math.max(a.length, b.length) || 1;
    const charSim = ((1 - lev / maxLen) * 100).toFixed(1);

    if (cmpActive === 'similarity') {
        out.innerHTML = `<div class="xt-cmp-summary">
            <div class="xt-cmp-stat"><div class="xt-cmp-stat-val">${sim.toFixed(1)}%</div><div class="xt-cmp-stat-label">Word similarity (Jaccard)</div></div>
            <div class="xt-cmp-stat"><div class="xt-cmp-stat-val">${charSim}%</div><div class="xt-cmp-stat-label">Character similarity</div></div>
            <div class="xt-cmp-stat"><div class="xt-cmp-stat-val">${lev}</div><div class="xt-cmp-stat-label">Levenshtein distance</div></div>
        </div>`;
        return;
    }

    const ops = lcsDiff(unitA, unitB);
    const added = ops.filter(o => o[0] === 'add').length, removed = ops.filter(o => o[0] === 'del').length, same = ops.filter(o => o[0] === 'same').length;

    if (cmpActive === 'sidebyside') {
        out.innerHTML = `<div class="xt-cmp-summary">
            <div class="xt-cmp-stat"><div class="xt-cmp-stat-val" style="color:var(--color-success)">${added}</div><div class="xt-cmp-stat-label">Added</div></div>
            <div class="xt-cmp-stat"><div class="xt-cmp-stat-val" style="color:var(--color-danger)">${removed}</div><div class="xt-cmp-stat-label">Removed</div></div>
            <div class="xt-cmp-stat"><div class="xt-cmp-stat-val">${same}</div><div class="xt-cmp-stat-label">Unchanged</div></div>
        </div>
        <div class="xt-cmp-sbs">
            <div class="xt-cmp-sbs-col">${ops.filter(o => o[0] !== 'add').map(o => `<div class="${o[0] === 'del' ? 'xt-diff-del' : 'xt-diff-same'}">${esc(o[1])}</div>`).join('')}</div>
            <div class="xt-cmp-sbs-col">${ops.filter(o => o[0] !== 'del').map(o => `<div class="${o[0] === 'add' ? 'xt-diff-add' : 'xt-diff-same'}">${esc(o[1])}</div>`).join('')}</div>
        </div>`;
        return;
    }

    // compare / difference / paragraph / sentence / word (unified diff view)
    out.innerHTML = `<div class="xt-cmp-summary">
        <div class="xt-cmp-stat"><div class="xt-cmp-stat-val" style="color:var(--color-success)">${added}</div><div class="xt-cmp-stat-label">Added</div></div>
        <div class="xt-cmp-stat"><div class="xt-cmp-stat-val" style="color:var(--color-danger)">${removed}</div><div class="xt-cmp-stat-label">Removed</div></div>
        <div class="xt-cmp-stat"><div class="xt-cmp-stat-val">${same}</div><div class="xt-cmp-stat-label">Unchanged</div></div>
        <div class="xt-cmp-stat"><div class="xt-cmp-stat-val">${sim.toFixed(1)}%</div><div class="xt-cmp-stat-label">Similarity</div></div>
    </div>
    <div class="xt-diff-body">${ops.map(([type, val]) => {
        if (type === 'add') return `<span class="xt-diff-add">${esc(val)}</span>${joiner === '\n' ? '<br>' : ' '}`;
        if (type === 'del') return `<span class="xt-diff-del">${esc(val)}</span>${joiner === '\n' ? '<br>' : ' '}`;
        return `<span class="xt-diff-same">${esc(val)}</span>${joiner === '\n' ? '<br>' : ' '}`;
    }).join('')}</div>`;
}

/* ================================================================
   ENCODING
   ================================================================ */
const MORSE = { A: '.-', B: '-...', C: '-.-.', D: '-..', E: '.', F: '..-.', G: '--.', H: '....', I: '..', J: '.---', K: '-.-', L: '.-..', M: '--', N: '-.', O: '---', P: '.--.', Q: '--.-', R: '.-.', S: '...', T: '-', U: '..-', V: '...-', W: '.--', X: '-..-', Y: '-.--', Z: '--..', '0': '-----', '1': '.----', '2': '..---', '3': '...--', '4': '....-', '5': '.....', '6': '-....', '7': '--...', '8': '---..', '9': '----.', ' ': '/' };
const MORSE_REV = Object.fromEntries(Object.entries(MORSE).map(([k, v]) => [v, k]));

function b64encode(str) { return btoa(unescape(encodeURIComponent(str))); }
function b64decode(str) { return decodeURIComponent(escape(atob(str))); }
function rot13(str) { return str.replace(/[a-zA-Z]/g, c => String.fromCharCode((c <= 'Z' ? 90 : 122) >= (c.charCodeAt(0) + 13) ? c.charCodeAt(0) + 13 : c.charCodeAt(0) + 13 - 26)); }

const CODECS = [
    { id: 'base64', name: 'Base64', dirs: ['Encode', 'Decode'],
      run: (t, dir) => dir === 'Encode' ? b64encode(t) : b64decode(t) },
    { id: 'rot13', name: 'ROT13', dirs: ['Encode', 'Decode'],
      run: (t) => rot13(t) },
    { id: 'morse', name: 'Morse Code', dirs: ['Encode', 'Decode'],
      run: (t, dir) => dir === 'Encode'
        ? t.toUpperCase().split('').map(c => MORSE[c] !== undefined ? MORSE[c] : c).join(' ')
        : t.trim().split(/\s+/).map(code => MORSE_REV[code] !== undefined ? MORSE_REV[code] : (code === '/' ? ' ' : code)).join('').replace(/\/o/g, ' ') },
    { id: 'ascii', name: 'ASCII Converter', dirs: ['Text \u2192 ASCII', 'ASCII \u2192 Text'],
      run: (t, dir) => dir.startsWith('Text') ? t.split('').map(c => c.charCodeAt(0)).join(' ') : t.trim().split(/\s+/).map(n => String.fromCharCode(parseInt(n, 10))).join('') },
    { id: 'unicode', name: 'Unicode Converter', dirs: ['Text \u2192 Unicode', 'Unicode \u2192 Text'],
      run: (t, dir) => dir.startsWith('Text') ? t.split('').map(c => 'U+' + c.codePointAt(0).toString(16).toUpperCase().padStart(4, '0')).join(' ') : t.trim().split(/\s+/).map(u => String.fromCodePoint(parseInt(u.replace(/^U\+/i, ''), 16))).join('') },
    { id: 'binary', name: 'Binary Converter', dirs: ['Text \u2192 Binary', 'Binary \u2192 Text'],
      run: (t, dir) => dir.startsWith('Text') ? t.split('').map(c => c.charCodeAt(0).toString(2).padStart(8, '0')).join(' ') : t.trim().split(/\s+/).map(b => String.fromCharCode(parseInt(b, 2))).join('') },
    { id: 'hex', name: 'Hex Converter', dirs: ['Text \u2192 Hex', 'Hex \u2192 Text'],
      run: (t, dir) => dir.startsWith('Text') ? t.split('').map(c => c.charCodeAt(0).toString(16).padStart(2, '0')).join(' ') : t.trim().split(/\s+/).map(h => String.fromCharCode(parseInt(h, 16))).join('') },
    { id: 'url', name: 'URL Encoding', dirs: ['Encode', 'Decode'],
      run: (t, dir) => dir === 'Encode' ? encodeURIComponent(t) : decodeURIComponent(t) },
];
let codecActive = 'base64', dirActive = 0;

function renderCodecTabs() {
    $('#xt-codec-tabs').innerHTML = CODECS.map(c => `<button type="button" class="xt-codec-tab ${c.id === codecActive ? 'active' : ''}" data-codec="${c.id}">${c.name}</button>`).join('');
    $('#cnt-encode').textContent = '12';
    renderDirToggle();
}
function renderDirToggle() {
    const codec = CODECS.find(c => c.id === codecActive);
    $('#xt-dir-toggle').innerHTML = codec.dirs.map((d, i) => `<button type="button" class="xt-dir-btn ${i === dirActive ? 'active' : ''}" data-dir="${i}">${d}</button>`).join('');
    $('#xt-enc-in-label').textContent = codec.dirs[dirActive].replace(' \u2192 ', ' ').split(' ')[0] === 'Encode' ? 'Input (plain text)' : ('Input (' + codec.dirs[dirActive] + ')').replace('  ', ' ');
    $('#xt-enc-out-label').textContent = 'Output';
    runEncode();
}
function runEncode() {
    const codec = CODECS.find(c => c.id === codecActive);
    const dir = codec.dirs[dirActive];
    const errEl = $('#xt-enc-err');
    errEl.textContent = '';
    try {
        const out = codec.run($('#xt-enc-in').value, dir);
        $('#xt-enc-out').value = out;
    } catch (e) {
        $('#xt-enc-out').value = '';
        errEl.textContent = 'Could not ' + dir.toLowerCase() + ': input is not valid for this codec.';
    }
}

/* ================================================================
   SEARCH
   ================================================================ */
function buildSearchIndex() {
    const idx = [];
    ACTIONS.forEach(a => idx.push({ name: a.name, tab: a.tab, action: 'action:' + a.id }));
    EXTRACTORS.forEach(e => idx.push({ name: 'Extract ' + e.name, tab: 'extract', action: null }));
    CMP_TOOLS.forEach(c => idx.push({ name: c.name, tab: 'compare', action: 'cmp:' + c.id }));
    CODECS.forEach(c => { c.dirs.forEach(d => idx.push({ name: c.name + ' ' + d, tab: 'encode', action: 'codec:' + c.id })); });
    ['Word Counter', 'Character Counter', 'Character Counter (No Spaces)', 'Sentence Counter', 'Paragraph Counter', 'Line Counter', 'Letter Counter', 'Digit Counter', 'Symbol Counter', 'Punctuation Counter',
     'Reading Time Calculator', 'Speaking Time Calculator', 'Average Word Length', 'Average Sentence Length', 'Lexical Density Calculator', 'Vocabulary Richness Analyzer', 'Keyword Density Checker', 'Keyword Frequency Analyzer', 'Most Used Words Finder', 'N-Gram Analyzer',
     'Flesch Reading Ease', 'Gunning Fog Index', 'SMOG Index', 'Coleman-Liau Index', 'Automated Readability Index', 'Reading Grade Calculator', 'Reading Difficulty Checker']
        .forEach(n => idx.push({ name: n, tab: 'analysis', action: null }));
    return idx;
}
const TAB_LABEL = { analysis: 'Analysis', format: 'Format & Case', cleanup: 'Cleanup', extract: 'Extraction', compare: 'Comparison', encode: 'Encoding' };
let searchIndex = [];

function runSearch(q) {
    const box = $('#xt-search-results');
    if (!q.trim()) { box.classList.remove('open'); return; }
    const ql = q.toLowerCase();
    const results = searchIndex.filter(i => i.name.toLowerCase().includes(ql)).slice(0, 12);
    if (!results.length) { box.innerHTML = `<div class="xt-search-empty">No tools match "${esc(q)}"</div>`; box.classList.add('open'); return; }
    box.innerHTML = results.map((r, i) => `<div class="xt-search-item" data-i="${i}" data-tab="${r.tab}" data-action="${r.action || ''}"><span class="xt-si-tab">${TAB_LABEL[r.tab]}</span>${esc(r.name)}</div>`).join('');
    box.classList.add('open');
}
function goToSearchResult(el) {
    const tab = el.dataset.tab, action = el.dataset.action;
    switchTab(tab);
    $('#xt-search-results').classList.remove('open');
    $('#xt-global-search').value = '';
    if (action && action.startsWith('action:')) {
        const id = action.slice(7);
        const card = $('#ac-' + id);
        if (card) { card.scrollIntoView({ behavior: 'smooth', block: 'center' }); card.classList.add('flash'); setTimeout(() => card.classList.remove('flash'), 900); }
    } else if (action && action.startsWith('cmp:')) {
        setCmpActive(action.slice(4));
    } else if (action && action.startsWith('codec:')) {
        codecActive = action.slice(6); dirActive = 0; renderCodecTabs();
    }
}

/* ================================================================
   TABS + LIVE UPDATE
   ================================================================ */
function switchTab(tab) {
    $$('.xt-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
    $$('.xt-pane').forEach(p => p.classList.toggle('active', p.id === 'pane-' + tab));
}
function setCmpActive(id) { cmpActive = id; renderCompareTools(); runCompare(); }

function updateEmptyStates() {
    const empty = !input.value.trim();
    $$('.xt-pane[data-empty-icon]').forEach(pane => {
        let ph = pane.querySelector('.xt-empty');
        const body = pane.querySelector('#xt-analysis-groups, #xt-actions-format, #xt-actions-cleanup, #xt-extract-groups');
        if (empty) {
            if (!ph) {
                ph = document.createElement('div');
                ph.className = 'xt-empty';
                ph.innerHTML = `${icon(ICONS.split)}<p>${pane.dataset.emptyText}</p>`;
                pane.appendChild(ph);
            }
            ph.style.display = 'flex';
        } else if (ph) ph.style.display = 'none';
    });
}

function render() {
    renderAnalysis();
    renderActionGrid('format', 'xt-actions-format');
    renderActionGrid('cleanup', 'xt-actions-cleanup');
    renderExtraction();
    updateEmptyStates();

    const a = analyze(input.value);
    $('#qs-words').textContent = a.wordCount;
    $('#qs-chars').textContent = a.chars;
    $('#qs-sentences').textContent = a.sentCount;
    $('#qs-lines').textContent = a.lineCount;
    $('#qs-time').textContent = fmtTime(a.readTimeSec);
    $('#cnt-format').textContent = ACTIONS.filter(x => x.tab === 'format').length;
    $('#cnt-cleanup').textContent = ACTIONS.filter(x => x.tab === 'cleanup').length;
}

const SAMPLE = `The Text Toolkit brings together over a hundred small, focused utilities that writers, editors and developers reach for every day. It counts words and characters, measures reading time, and scores how easy your writing is to follow using the Flesch Reading Ease and Gunning Fog formulas.\n\nBeyond analysis, it can reformat text into camelCase or snake_case, strip duplicate lines, extract every email address or URL hiding in a document, compare two drafts side-by-side, and encode or decode Base64, Morse code and more — all without sending a single byte to a server.\n\nContact: hello@example.com · https://example.com · #texttools @awantools`;

/* ── Event wiring ─────────────────────────────────────────────── */
let renderTimer = null;
function debouncedRender() { clearTimeout(renderTimer); renderTimer = setTimeout(render, 120); }

input.addEventListener('input', debouncedRender);
$('#xt-keyword').addEventListener('input', debouncedRender);
$('#xt-ngram-size').addEventListener('change', debouncedRender);

$('#xt-btn-sample').addEventListener('click', () => setText(SAMPLE, { toast: 'Sample text loaded' }));
$('#xt-btn-clear').addEventListener('click', () => setText('', { toast: 'Workbench cleared' }));
$('#xt-btn-undo').addEventListener('click', () => {
    if (!history.length) return;
    const prev = history.pop();
    input.value = prev;
    $('#xt-btn-undo').disabled = history.length === 0;
    render();
    toast('Undone');
});
$('#xt-btn-paste').addEventListener('click', async () => {
    try {
        const text = await navigator.clipboard.readText();
        setText(text, { toast: 'Pasted from clipboard' });
    } catch (e) { toast('Clipboard access denied — paste manually with Ctrl/Cmd+V'); }
});
$('#xt-btn-upload').addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => setText(reader.result, { toast: 'File loaded: ' + file.name });
    reader.readAsText(file);
    fileInput.value = '';
});

$$('.xt-tab').forEach(t => t.addEventListener('click', () => switchTab(t.dataset.tab)));

$$('.xt-mini-search').forEach(inp => inp.addEventListener('input', () => {
    const target = inp.dataset.target;
    renderActionGrid(target, target === 'format' ? 'xt-actions-format' : 'xt-actions-cleanup', inp.value);
}));

searchIndex = buildSearchIndex();
const searchBox = $('#xt-global-search');
searchBox.addEventListener('input', () => runSearch(searchBox.value));
searchBox.addEventListener('focus', () => { if (searchBox.value.trim()) runSearch(searchBox.value); });
document.addEventListener('click', (e) => {
    if (!e.target.closest('.xt-hero-search-wrap')) $('#xt-search-results').classList.remove('open');
    const item = e.target.closest('.xt-search-item');
    if (item) goToSearchResult(item);
});

$('#xt-copy-all-extract').addEventListener('click', () => {
    render(); // ensure cache fresh
    const all = window._xtExtractCache || [];
    if (!all.length) { toast('Nothing to copy'); return; }
    copyText(all.join('\n'));
});

renderCompareTools();
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-cmp]');
    if (btn) setCmpActive(btn.dataset.cmp);
});
['xt-cmp-a', 'xt-cmp-b'].forEach(id => $('#' + id).addEventListener('input', () => runCompare()));
$('#xt-cmp-a-load').addEventListener('click', () => { $('#xt-cmp-a').value = input.value; runCompare(); toast('Loaded into Text A'); });
$('#xt-cmp-b-load').addEventListener('click', () => { $('#xt-cmp-b').value = input.value; runCompare(); toast('Loaded into Text B'); });

renderCodecTabs();
document.addEventListener('click', (e) => {
    const ct = e.target.closest('[data-codec]');
    if (ct) { codecActive = ct.dataset.codec; dirActive = 0; renderCodecTabs(); }
    const dt = e.target.closest('[data-dir]');
    if (dt) { dirActive = parseInt(dt.dataset.dir, 10); renderDirToggle(); }
});
$('#xt-enc-in').addEventListener('input', runEncode);
$('#xt-enc-load').addEventListener('click', () => { $('#xt-enc-in').value = input.value; runEncode(); toast('Loaded Workbench text'); });
$('#xt-enc-send').addEventListener('click', () => { const out = $('#xt-enc-out').value; if (!out) { toast('Nothing to send'); return; } setText(out, { toast: 'Sent to Workbench' }); });

render();
$('#xt-btn-undo').disabled = true;

/* ── Public API (used by inline onclick handlers in generated HTML) ── */
window.XT = { runAction, toggleParams, toggleExtract, copyOne: (el, val) => copyText(val) };

})();
