/* ── Time Toolkit — Main JS ── */
(function () {
'use strict';

/* ═══════════════════════════════════════════════════════════════
   UTILITIES
═══════════════════════════════════════════════════════════════ */
const $ = id => document.getElementById(id);
const $$ = sel => document.querySelectorAll(sel);

function pad(n, l = 2) { return String(n).padStart(l, '0'); }
function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        if (btn) {
            btn.classList.add('copied');
            const orig = btn.innerHTML;
            btn.innerHTML = iconSVG('check');
            setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('copied'); }, 1500);
        }
        showToast('Copied!');
    }).catch(() => {
        const ta = document.createElement('textarea');
        ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta);
        showToast('Copied!');
    });
}

function showToast(msg, icon = 'check') {
    let t = document.querySelector('.tt-toast');
    if (!t) {
        t = document.createElement('div');
        t.className = 'tt-toast';
        document.body.appendChild(t);
    }
    t.innerHTML = iconSVG(icon) + esc(msg);
    t.classList.add('show');
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 2000);
}

function valCell(label, value, mono = false, big = false) {
    const cls = ['tt-val-value', mono ? 'mono' : '', big ? 'big' : ''].filter(Boolean).join(' ');
    const safeVal = esc(String(value));
    return `<div class="tt-val-cell">
        <div class="tt-val-label">${esc(label)}</div>
        <div class="${cls}">${safeVal}</div>
        <button class="tt-copy-inline" onclick="TT.copy(${JSON.stringify(String(value))},this)" title="Copy">${iconSVG('copy')}</button>
    </div>`;
}

function fmtRow(label, value) {
    return `<tr>
        <td>${esc(label)}</td>
        <td>${esc(String(value))}</td>
        <td class="tt-copy-td"><button class="tt-copy-cell" onclick="TT.copy(${JSON.stringify(String(value))},this)" title="Copy">${iconSVG('copy')}</button></td>
    </tr>`;
}

function iconSVG(key) {
    const icons = {
        clock: '<polyline points="12 6 12 12 16 14"/>',
        copy: '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        check: '<polyline points="20 6 9 17 4 12"/>',
        chevron: '<polyline points="6 9 12 15 18 9"/>',
        search: '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        calendar: '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        zap: '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        globe: '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        hash: '<line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/>',
        code: '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        star: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        sun: '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>',
        moon: '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>',
        briefcase: '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        terminal: '<polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>',
        repeat: '<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
        download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        users: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        minus: '<line x1="5" y1="12" x2="19" y2="12"/>',
        plus: '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        alert: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
        map: '<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>',
        filter: '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        pray: '<path d="M12 2a5 5 0 0 1 5 5c0 3-2 5.5-5 8-3-2.5-5-5-5-8a5 5 0 0 1 5-5z"/>',
        tool: '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        sparkles: '<path d="M12 3l1.5 5H18l-4 3 1.5 5L12 13l-3.5 3 1.5-5-4-3h4.5L12 3z"/>',
        leaf: '<path d="M2 22c5.333-5 10.667-5 16 0M2 22V12a10 10 0 0 1 20 0v10"/>',
    };
    const d = icons[key] || '<circle cx="12" cy="12" r="10"/>';
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`;
}

function codeBlock(lang, code) {
    const id = 'cb-' + Math.random().toString(36).slice(2);
    return `<div class="tt-code-wrap">
        <button class="tt-code-copy" id="${id}-btn" onclick="TT.copyCode('${id}', this)">${iconSVG('copy')} Copy</button>
        <pre class="tt-code" id="${id}">${esc(code)}</pre>
    </div>`;
}

/* ═══════════════════════════════════════════════════════════════
   INPUT DETECTION & PARSING
═══════════════════════════════════════════════════════════════ */
const TZLIST = [
    'UTC','America/New_York','America/Chicago','America/Denver','America/Los_Angeles',
    'America/Anchorage','Pacific/Honolulu','America/Toronto','America/Vancouver',
    'America/Mexico_City','America/Sao_Paulo','America/Buenos_Aires','America/Bogota',
    'Europe/London','Europe/Dublin','Europe/Lisbon','Europe/Paris','Europe/Berlin',
    'Europe/Madrid','Europe/Rome','Europe/Amsterdam','Europe/Brussels','Europe/Warsaw',
    'Europe/Vienna','Europe/Prague','Europe/Budapest','Europe/Bucharest','Europe/Athens',
    'Europe/Helsinki','Europe/Stockholm','Europe/Oslo','Europe/Copenhagen','Europe/Moscow',
    'Europe/Istanbul','Asia/Karachi','Asia/Kolkata','Asia/Dhaka','Asia/Colombo',
    'Asia/Kathmandu','Asia/Dubai','Asia/Riyadh','Asia/Kuwait','Asia/Baghdad',
    'Asia/Tehran','Asia/Kabul','Asia/Tashkent','Asia/Almaty','Asia/Bishkek',
    'Asia/Tokyo','Asia/Seoul','Asia/Shanghai','Asia/Hong_Kong','Asia/Singapore',
    'Asia/Taipei','Asia/Jakarta','Asia/Bangkok','Asia/Ho_Chi_Minh','Asia/Manila',
    'Asia/Kolkata','Africa/Cairo','Africa/Lagos','Africa/Nairobi','Africa/Johannesburg',
    'Australia/Sydney','Australia/Melbourne','Australia/Brisbane','Australia/Perth',
    'Pacific/Auckland','Pacific/Fiji','Pacific/Guam',
];

function detectType(raw) {
    const s = raw.trim();
    if (!s) return null;

    // Unix ms (13 digits)
    if (/^\d{13}$/.test(s)) return 'unix_ms';
    // Unix timestamp (10 digits)
    if (/^\d{10}$/.test(s)) return 'unix';
    // Large number (microseconds/nanoseconds)
    if (/^\d{16}$/.test(s)) return 'unix_us';
    if (/^\d{19}$/.test(s)) return 'unix_ns';

    // ISO 8601 / RFC 3339
    if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(s)) return 'iso';
    // RFC 2822
    if (/^[A-Za-z]{3},?\s+\d{1,2}\s+[A-Za-z]{3}\s+\d{4}\s+\d{2}:\d{2}/.test(s)) return 'rfc2822';

    // Date only YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return 'date';
    // Date DD/MM/YYYY or MM/DD/YYYY
    if (/^\d{2}[\/\-]\d{2}[\/\-]\d{4}$/.test(s)) return 'date_dmy';
    // Date + time
    if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/.test(s)) return 'datetime';
    // Time only HH:MM or HH:MM:SS
    if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(s)) return 'time';

    // Lat/Lng pair
    if (/^-?\d+\.?\d*,\s*-?\d+\.?\d*$/.test(s)) return 'latlng';

    // Cron expression (5 or 6 fields)
    if (/^(\S+\s+){4}\S+(\s+\S+)?$/.test(s) && s.split(/\s+/).length >= 5) return 'cron';

    // UTC offset ±HH:MM or UTC±N
    if (/^(UTC)?[+-]\d{1,2}(:\d{2})?$/.test(s)) return 'utcoffset';

    // Duration (ISO 8601 duration or natural)
    if (/^P/i.test(s)) return 'duration';

    // Timezone name (contains /)
    if (s.includes('/') && TZLIST.some(tz => tz.toLowerCase() === s.toLowerCase())) return 'timezone';
    if (TZLIST.some(tz => tz.toLowerCase() === s.toLowerCase())) return 'timezone';
    if (/^(UTC|GMT)$/i.test(s)) return 'timezone';

    // Fallback: try to parse as date
    const d = new Date(s);
    if (!isNaN(d)) return 'datestring';

    return 'unknown';
}

function parseInput(raw) {
    const s = raw.trim();
    const type = detectType(s);
    let result = { type, raw: s, valid: false, date: null, unix: null, ms: null, tz: null, lat: null, lng: null, cron: null, duration: null };

    try {
        switch (type) {
            case 'unix': {
                const d = new Date(parseInt(s) * 1000);
                result = { ...result, valid: true, date: d, unix: parseInt(s), ms: parseInt(s) * 1000 };
                break;
            }
            case 'unix_ms': {
                const d = new Date(parseInt(s));
                result = { ...result, valid: true, date: d, unix: Math.floor(parseInt(s) / 1000), ms: parseInt(s) };
                break;
            }
            case 'unix_us': {
                const ms = Math.floor(parseInt(s) / 1000);
                const d = new Date(ms);
                result = { ...result, valid: true, date: d, unix: Math.floor(ms / 1000), ms };
                break;
            }
            case 'unix_ns': {
                const ms = Math.floor(parseInt(s) / 1_000_000);
                const d = new Date(ms);
                result = { ...result, valid: true, date: d, unix: Math.floor(ms / 1000), ms };
                break;
            }
            case 'latlng': {
                const [la, lo] = s.split(',').map(x => parseFloat(x.trim()));
                result = { ...result, valid: true, lat: la, lng: lo };
                break;
            }
            case 'cron': {
                result = { ...result, valid: true, cron: s };
                break;
            }
            case 'timezone': {
                const tz = TZLIST.find(t => t.toLowerCase() === s.toLowerCase()) || s;
                result = { ...result, valid: true, tz };
                break;
            }
            case 'utcoffset': {
                result = { ...result, valid: true, tz: s };
                break;
            }
            default: {
                const d = type === 'date' ? new Date(s + 'T00:00:00') : new Date(s);
                if (!isNaN(d)) {
                    result = { ...result, valid: true, date: d, unix: Math.floor(d.getTime() / 1000), ms: d.getTime() };
                }
            }
        }
    } catch (e) { result.valid = false; }

    return result;
}

/* ═══════════════════════════════════════════════════════════════
   MAIN TT NAMESPACE
═══════════════════════════════════════════════════════════════ */
window.TT = {
    _parsed: null,
    _calDate: new Date(),
    _wc_open: false,

    init() {
        this.startLiveClock();
        this.buildWorldClock();
        this.renderAllEmpty();
        // Hint chips
        $$('.tt-hint-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                const inp = $('tt-uni-input');
                if (inp) { inp.value = chip.dataset.val || chip.textContent; this.analyze(); }
            });
        });
        // Accordion toggles
        $$('.tt-acc-header').forEach(h => {
            h.addEventListener('click', () => {
                const acc = h.closest('.tt-accordion');
                acc.classList.toggle('tt-open');
            });
        });
        // Search
        const srch = $('tt-search');
        if (srch) {
            srch.addEventListener('input', () => this.filterAccordions(srch.value));
        }
        // Calendar nav
        this.renderCalendar(null);
        // Tab init
        $$('.tt-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const parent = tab.closest('[data-tabs]');
                parent.querySelectorAll('.tt-tab').forEach(t => t.classList.remove('active'));
                parent.querySelectorAll('.tt-tab-panel').forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                const panel = parent.querySelector('[data-panel="' + tab.dataset.tab + '"]');
                if (panel) panel.classList.add('active');
            });
        });
        // Default tab activation
        $$('[data-tabs]').forEach(container => {
            const firstTab = container.querySelector('.tt-tab');
            if (firstTab && !container.querySelector('.tt-tab.active')) firstTab.click();
        });
    },

    analyze() {
        const raw = ($('tt-uni-input') || {}).value || '';
        if (!raw.trim()) { this.clear(); return; }
        const parsed = parseInput(raw);
        this._parsed = parsed;
        this.showDetected(parsed);
        if (parsed.valid) {
            this.showDashboard(parsed);
            this.renderAll(parsed);
        }
    },

    clear() {
        this._parsed = null;
        if ($('tt-uni-input')) $('tt-uni-input').value = '';
        $('tt-detected').innerHTML = '';
        const dash = $('tt-dashboard');
        if (dash) dash.style.display = 'none';
        const lbl = $('tt-explorer-label');
        if (lbl) lbl.style.display = 'none';
        this.renderAllEmpty();
    },

    /* ── Show the summary dashboard ── */
    showDashboard(p) {
        const dash = $('tt-dashboard');
        const lbl  = $('tt-explorer-label');
        if (!dash) return;

        // Header
        const dashQ = $('tt-dash-q');
        const dashT = $('tt-dash-type');
        if (dashQ) dashQ.textContent = p.raw;
        if (dashT) {
            const typeLabels = {
                unix:'Unix Timestamp', unix_ms:'Unix (ms)', unix_us:'Unix (µs)', unix_ns:'Unix (ns)',
                date:'Date', datetime:'Date & Time', time:'Time Only', iso:'ISO 8601',
                rfc2822:'RFC 2822', datestring:'Date String', latlng:'Coordinates',
                cron:'Cron Expression', timezone:'Timezone', utcoffset:'UTC Offset',
                duration:'Duration', date_dmy:'Date (DMY)',
            };
            dashT.textContent = typeLabels[p.type] || p.type;
        }

        // Build content
        const content = $('tt-dash-content');
        if (content) content.innerHTML = this.renderDashboard(p);

        dash.style.display = 'block';
        if (lbl) lbl.style.display = 'flex';
    },

    /* ── Render dashboard HTML based on type ── */
    renderDashboard(p) {
        if (p.type === 'cron' && p.cron) return this._dashCron(p);
        if (p.type === 'latlng') return this._dashCoords(p);
        if (p.type === 'timezone' || p.type === 'utcoffset') return this._dashTimezone(p);
        if (p.date) return this._dashDateTime(p);
        return `<div class="tt-nodata">Input parsed but no detailed view available for this type.</div>`;
    },

    _dashCard(label, value, extra = '') {
        const isLong = String(value).length > 22;
        const valCls = `tt-dash-card-value${isLong ? '' : ''}`;
        return `<div class="tt-dash-card">
            <div class="tt-dash-card-label">${esc(label)}</div>
            <div class="${valCls}">${esc(String(value))}</div>
            ${extra ? `<div class="tt-dash-card-sub">${extra}</div>` : ''}
            <button class="tt-dash-copy" onclick="TT.copy(${JSON.stringify(String(value))},this)" title="Copy">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            </button>
        </div>`;
    },

    _dashSection(title, cardsHTML, gridClass = 'tt-dash-grid') {
        return `<div class="tt-dash-section">
            <div class="tt-dash-section-title">${esc(title)}</div>
            <div class="${gridClass}">${cardsHTML}</div>
        </div>`;
    },

    _dashDateTime(p) {
        const d = p.date;
        const unix = p.unix || Math.floor(d.getTime() / 1000);
        const weekdays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const C = this._dashCard.bind(this);

        /* ── Date & Time ── */
        const dateTimeCards = [
            C('Full Date', d.toLocaleDateString(undefined, {weekday:'long',year:'numeric',month:'long',day:'numeric'})),
            C('Local Time', d.toLocaleTimeString()),
            C('UTC Date & Time', d.toUTCString()),
            C('ISO 8601', d.toISOString()),
        ].join('');

        /* ── Timestamps ── */
        const tsCards = [
            C('Unix Timestamp', unix, 'seconds since epoch'),
            C('Milliseconds', d.getTime(), 'epoch ms'),
            C('Microseconds', d.getTime() * 1000, 'epoch µs'),
            C('Relative', relativeTime(d)),
        ].join('');

        /* ── Calendar ── */
        const isLeap = isLeapYear(d.getFullYear());
        const doy = getDayOfYear(d);
        const wn = getWeekNumber(d);
        const q = Math.ceil((d.getMonth() + 1) / 3);
        const calCards = [
            C('Weekday', weekdays[d.getDay()]),
            C('Month', months[d.getMonth()]),
            C('Day of Year', doy, `of ${isLeap ? 366 : 365}`),
            C('Week Number', 'W' + wn, 'ISO week'),
            C('Quarter', 'Q' + q, d.getFullYear()),
            C('Season', getSeason(d)),
            C('Leap Year', isLeap ? 'Yes ✓' : 'No'),
            C('Day Type', d.getDay() === 0 || d.getDay() === 6 ? 'Weekend' : 'Weekday'),
        ].join('');

        /* ── Timezone ── */
        const offset = getUTCOffset(d);
        const tzAbbr = getTzAbbr(d);
        const tzCards = [
            C('UTC Offset', offset),
            C('Timezone', tzAbbr),
            C('UTC+0 Time', new Date(d.getTime()).toLocaleString('en-US', {timeZone:'UTC'}), 'UTC'),
        ].join('');

        /* ── Religious (Hijri) ── */
        const h = gregorianToHijri(d);
        const hijriMonths = ['Muharram','Safar','Rabi al-Awwal','Rabi al-Thani','Jumada al-Awwal','Jumada al-Thani','Rajab',"Sha'ban",'Ramadan','Shawwal','Dhu al-Qi\'dah','Dhu al-Hijjah'];
        const hijriCards = [
            C('Hijri Date', `${h.day} ${hijriMonths[h.month-1]} ${h.year} AH`),
            C('Hijri Month', hijriMonths[h.month-1]),
            C('Hijri Year', h.year + ' AH'),
            C('Ramadan', h.month === 9 ? '🌙 Yes' : 'No'),
        ].join('');

        return this._dashSection('📅 Date & Time', dateTimeCards, 'tt-dash-grid-wide')
             + this._dashSection('🕐 Timestamps', tsCards)
             + this._dashSection('📆 Calendar', calCards)
             + this._dashSection('🌍 Timezone', tzCards)
             + this._dashSection('🌙 Islamic Calendar', hijriCards);
    },

    _dashCron(p) {
        const parts = p.cron.trim().split(/\s+/);
        const [min, hour, dom, month, dow] = parts;
        const fieldNames = ['Minute', 'Hour', 'Day of Month', 'Month', 'Day of Week'];
        const fieldVals  = [min, hour, dom, month, dow];
        const explain = cronExplain(min, hour, dom, month, dow);
        const nexts = getNextCronTimes(parts.slice(0, 5), 5);
        const C = this._dashCard.bind(this);

        const fieldsHTML = `<div class="tt-dash-cron-fields">
            ${fieldNames.map((n,i) => `<div class="tt-dash-cron-field">
                <div class="tt-dash-cron-field-name">${n}</div>
                <div class="tt-dash-cron-field-val">${esc(fieldVals[i])}</div>
            </div>`).join('')}
        </div>
        <div class="tt-dash-explain">${esc(explain)}</div>`;

        const nextCards = nexts.map((d, i) => C(
            `Run ${i+1}`,
            d.toLocaleString(),
            relativeTime(d)
        )).join('');

        return `<div class="tt-dash-section">
                    <div class="tt-dash-section-title">⚙️ Cron Fields</div>
                    ${fieldsHTML}
                </div>
                ${this._dashSection('⏰ Next Executions', nextCards)}`;
    },

    _dashCoords(p) {
        const sun = calcSunTimes(p.lat, p.lng, new Date());
        const moon = calcMoonPhase(new Date());
        const C = this._dashCard.bind(this);
        const locCards = [
            C('Latitude', p.lat.toFixed(6)),
            C('Longitude', p.lng.toFixed(6)),
            C('Decimal', `${p.lat.toFixed(4)}, ${p.lng.toFixed(4)}`),
            C('DMS', `${latToDMS(p.lat)} N, ${lngToDMS(p.lng)} E`),
        ].join('');
        const sunCards = [
            C('Sunrise', sun.sunrise),
            C('Sunset', sun.sunset),
            C('Day Length', sun.dayLength),
            C('Solar Noon', sun.solarNoon),
            C('Golden Hour AM', sun.goldenHourAM),
            C('Golden Hour PM', sun.goldenHourPM),
        ].join('');
        const moonCards = [
            C('Phase', moon.phaseName),
            C('Illumination', moon.illumination + '%'),
            C('Age', moon.age.toFixed(1) + ' days'),
            C('Next Full Moon', moon.nextFull),
        ].join('');
        return this._dashSection('📍 Coordinates', locCards)
             + this._dashSection('☀️ Solar Times (Today)', sunCards)
             + this._dashSection('🌙 Moon', moonCards);
    },

    _dashTimezone(p) {
        const tz = p.tz || 'UTC';
        const now = new Date();
        const C = this._dashCard.bind(this);
        let timeStr = '', dateStr = '', ofsStr = '', isDst = false;
        try {
            timeStr = now.toLocaleTimeString('en-US', { timeZone: tz, hour:'2-digit', minute:'2-digit', second:'2-digit' });
            dateStr = now.toLocaleDateString('en-US', { timeZone: tz, weekday:'long', year:'numeric', month:'long', day:'numeric' });
            const ofsMin = getTimezoneOffset(tz, now);
            const sign = ofsMin >= 0 ? '+' : '-';
            const abs = Math.abs(ofsMin);
            ofsStr = `UTC${sign}${pad(Math.floor(abs/60))}:${pad(abs%60)}`;
            isDst = isDST(tz, now);
        } catch(e) { timeStr = 'N/A'; }

        const cards = [
            C('Timezone', tz),
            C('Current Time', timeStr),
            C('Current Date', dateStr),
            C('UTC Offset', ofsStr),
            C('DST Active', isDst ? 'Yes (Daylight Saving)' : 'No'),
            C('UTC Time', now.toUTCString().split(' ').slice(4,5).join(' ') + ' UTC'),
        ].join('');
        return this._dashSection('🌐 Timezone Information', cards, 'tt-dash-grid-wide');
    },

    showDetected(p) {
        const el = $('tt-detected');
        if (!el) return;
        const labels = {
            unix: 'Unix Timestamp', unix_ms: 'Unix (ms)', unix_us: 'Unix (µs)', unix_ns: 'Unix (ns)',
            date: 'Date', datetime: 'Date & Time', time: 'Time', iso: 'ISO 8601',
            rfc2822: 'RFC 2822', datestring: 'Date String', latlng: 'Coordinates',
            cron: 'Cron Expression', timezone: 'Timezone', utcoffset: 'UTC Offset',
            duration: 'Duration', date_dmy: 'Date (DMY)',
        };
        const label = labels[p.type] || p.type;
        const statusClass = p.valid ? 'tt-badge-success' : 'tt-badge-danger';
        let extra = '';
        if (p.date) extra = `<span class="tt-detected-val">→ ${p.date.toISOString()}</span>`;
        if (p.lat !== null) extra = `<span class="tt-detected-val">→ Lat ${p.lat.toFixed(4)}, Lng ${p.lng.toFixed(4)}</span>`;
        el.innerHTML = `<span class="tt-detected-label">Detected:</span>
            <span class="tt-badge ${statusClass}">${iconSVG(p.valid ? 'check' : 'alert')} ${esc(label)}</span>${extra}`;
    },

    renderAll(p) {
        this.renderBasicInfo(p);
        this.renderTimestamp(p);
        this.renderFormats(p);
        this.renderCalendar(p.date);
        this.renderTimezones(p);
        this.renderArithmetic(p);
        this.renderDifference(p);
        this.renderAge(p);
        this.renderAstronomy(p);
        this.renderBusiness(p);
        this.renderDeveloper(p);
        this.renderCron(p);
        this.renderExport(p);
        this.renderReligious(p);
        this.renderUtilities(p);
        // Mark all accordions as updated
        $$('.tt-acc-updated').forEach(d => { if (d) d.classList.add('visible'); });
    },

    renderAllEmpty() {
        ['basic-out','ts-out','fmt-out','tz-out','arith-out','diff-out',
         'age-out','astro-out','biz-out','dev-out','cron-out','export-out',
         'rel-out','util-out'].forEach(id => {
            const el = $(id);
            if (el) el.innerHTML = noDataHTML('Use the Universal Input above or the tool controls below');
        });
        $$('.tt-acc-updated').forEach(d => d.classList.remove('visible'));
    },

    filterAccordions(q) {
        const s = q.toLowerCase().trim();
        $$('.tt-accordion').forEach(acc => {
            if (!s) { acc.classList.remove('tt-hidden'); return; }
            const text = (acc.querySelector('.tt-acc-title')?.textContent || '') +
                         (acc.querySelector('.tt-acc-desc')?.textContent || '');
            acc.classList.toggle('tt-hidden', !text.toLowerCase().includes(s));
        });
    },

    copy(text, btn) { copyText(text, btn); },
    copyCode(id, btn) {
        const el = $(id);
        if (el) copyText(el.textContent, btn);
    },

    /* ── Today at a Glance panel ── */
    initTodayPanel() {
        const now = new Date();
        const set = (id, val) => { const e = $(id); if (e) e.textContent = val; };

        // Gregorian
        set('tp-greg', now.toLocaleDateString('en-US', {weekday:'long', year:'numeric', month:'long', day:'numeric'}));

        // Hijri
        const h = gregorianToHijri(now);
        const hM = ['Muharram','Safar','Rabi al-Awwal','Rabi al-Thani','Jumada al-Awwal','Jumada al-Thani','Rajab',"Sha'ban",'Ramadan','Shawwal',"Dhu al-Qi'dah",'Dhu al-Hijjah'];
        set('tp-hijri', `${h.day} ${hM[h.month-1]} ${h.year} AH`);

        // Persian (Jalali)
        const p = gregorianToJalali(now);
        const pM = ['Farvardin','Ordibehesht','Khordad','Tir','Mordad','Shahrivar','Mehr','Aban','Azar','Dey','Bahman','Esfand'];
        set('tp-persian', `${p.day} ${pM[p.month-1]} ${p.year} AP`);

        // Moon
        const moon = calcMoonPhase(now);
        set('tp-moon', `${moon.phaseName} · ${moon.illumination}%`);

        // Timezone
        const ofs = -now.getTimezoneOffset();
        const sign = ofs >= 0 ? '+' : '-';
        const absOfs = Math.abs(ofs);
        const ofsStr = `UTC${sign}${pad(Math.floor(absOfs/60))}:${pad(absOfs%60)}`;
        set('tp-tz', `${getTzAbbr(now)} ${ofsStr}`);

        // Week + Quarter
        const wn = getWeekNumber(now);
        const q  = Math.ceil((now.getMonth()+1)/3);
        set('tp-wq', `W${wn} · Q${q}`);

        // Day of year
        const doy = getDayOfYear(now);
        const diy = isLeapYear(now.getFullYear()) ? 366 : 365;
        set('tp-doy', `Day ${doy} of ${diy}`);

        // Chinese Zodiac
        set('tp-zodiac', getChineseZodiac(now.getFullYear()));
    },

    /* ── Calendar Conversions accordion ── */
    convertCalendar() {
        const dateEl = $('calconv-date');
        const fromEl = $('calconv-from');
        const toEl   = $('calconv-to');
        const out    = $('calconv-out');
        if (!dateEl || !fromEl || !toEl || !out) return;

        const raw  = dateEl.value.trim();
        const from = fromEl.value;
        if (!raw) { out.innerHTML = '<div class="tt-nodata">Enter a date to convert.</div>'; return; }

        let gDate = null;
        if (from === 'gregorian') {
            gDate = new Date(raw.includes('T') ? raw : raw + 'T00:00:00');
        } else if (from === 'hijri') {
            const pts = raw.split(/[\-\/]/).map(Number);
            if (pts.length === 3 && pts.every(n => !isNaN(n)))
                gDate = hijriToGregorian(pts[0], pts[1], pts[2]);
        } else if (from === 'persian' || from === 'jalali') {
            const pts = raw.split(/[\-\/]/).map(Number);
            if (pts.length === 3 && pts.every(n => !isNaN(n)))
                gDate = jalaliToGregorian(pts[0], pts[1], pts[2]);
        } else if (from === 'julian') {
            const pts = raw.split(/[\-\/]/).map(Number);
            if (pts.length === 3 && pts.every(n => !isNaN(n)))
                gDate = julianCalToGregorian(pts[0], pts[1], pts[2]);
        }

        if (!gDate || isNaN(gDate)) {
            out.innerHTML = '<div class="tt-nodata">⚠️ Could not parse the date. Use <strong>YYYY-MM-DD</strong> for Gregorian, or <strong>YYYY/MM/DD</strong> for other calendars.</div>';
            return;
        }

        const C = this._dashCard.bind(this);
        const S = this._dashSection.bind(this);

        const hijri   = gregorianToHijri(gDate);
        const jalali  = gregorianToJalali(gDate);
        const julian  = gregorianToJulianCal(gDate);
        const hebrew  = gregorianToHebrew(gDate);
        const moon    = calcMoonPhase(gDate);
        const zodiac  = getChineseZodiac(gDate.getFullYear());

        const hM = ['Muharram','Safar','Rabi al-Awwal','Rabi al-Thani','Jumada al-Awwal','Jumada al-Thani','Rajab',"Sha'ban",'Ramadan','Shawwal',"Dhu al-Qi'dah",'Dhu al-Hijjah'];
        const pM = ['Farvardin','Ordibehesht','Khordad','Tir','Mordad','Shahrivar','Mehr','Aban','Azar','Dey','Bahman','Esfand'];
        const jM = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const hbM = ['Tishri','Cheshvan','Kislev','Tevet','Shevat','Adar','Nisan','Iyar','Sivan','Tammuz','Av','Elul'];

        const gregISO = gDate.toISOString().slice(0,10);

        const gregCards = [
            C('Date', gDate.toLocaleDateString('en-US', {weekday:'long',year:'numeric',month:'long',day:'numeric'})),
            C('ISO 8601', gregISO),
            C('Unix', Math.floor(gDate.getTime()/1000), 'seconds since epoch'),
            C('Leap Year', isLeapYear(gDate.getFullYear()) ? 'Yes ✓' : 'No'),
        ].join('');

        const hijriCards = [
            C('Hijri Date',  `${hijri.day} ${hM[hijri.month-1]} ${hijri.year}`),
            C('Hijri Year',  hijri.year + ' AH'),
            C('Hijri Month', hM[hijri.month-1]),
            C('Ramadan?',    hijri.month === 9 ? '🌙 Yes — Holy month' : 'No'),
        ].join('');

        const persianCards = [
            C('Persian Date',  `${jalali.day} ${pM[jalali.month-1]} ${jalali.year}`),
            C('Persian Year',  jalali.year + ' AP'),
            C('Persian Month', pM[jalali.month-1]),
            C('New Year',      'Nowruz — March 20/21'),
        ].join('');

        const julianCards = [
            C('Julian Date',  `${julian.day} ${jM[julian.month-1]} ${julian.year}`),
            C('Julian Year',  julian.year + ' JC'),
            C('Julian Month', jM[julian.month-1]),
            C('Diff from Greg','~13 days behind (20–21st c.)'),
        ].join('');

        const hebrewCards = [
            C('Hebrew Date',  `${hebrew.day} ${hbM[hebrew.month-1]} ${hebrew.year}`),
            C('Hebrew Year',  hebrew.year + ' AM'),
            C('Hebrew Month', hbM[hebrew.month-1]),
            C('Note',         'Approximate (±1 day)'),
        ].join('');

        const extraCards = [
            C('Moon Phase',    moon.phaseName),
            C('Illumination',  moon.illumination + '%'),
            C('Next Full Moon',moon.nextFull),
            C('Chinese Zodiac',zodiac),
        ].join('');

        out.innerHTML = S('📅 Gregorian', gregCards, 'tt-dash-grid-wide')
            + S('☪️ Hijri / Islamic', hijriCards)
            + S('🌸 Persian / Jalali', persianCards)
            + S('📜 Julian Calendar', julianCards)
            + S('✡️ Hebrew (Approx.)', hebrewCards)
            + S('🌙 Lunar & Chinese', extraCards);
    },

    /* ── cURL API Reference accordion ── */
    renderCurlDocs() {
        const out = $('curl-out');
        if (!out) return;
        const base = window.location.origin + '/plugins/time-toolkit/api';
        const today = new Date().toISOString().slice(0,10);

        const endpoint = (method, action, desc, params, example, note = '') => {
            const cmd = `curl "${base}?action=${action}${example}"`;
            const paramRows = params.map(([p,d]) =>
                `<tr><td class="tt-curl-param-name">${esc(p)}</td><td class="tt-curl-param-desc">${esc(d)}</td></tr>`
            ).join('');
            return `<div class="tt-curl-endpoint">
                <div class="tt-curl-ep-header">
                    <span class="tt-curl-badge tt-curl-${method.toLowerCase()}">${method}</span>
                    <span class="tt-curl-action">action=${esc(action)}</span>
                    <span class="tt-curl-desc">${esc(desc)}</span>
                </div>
                ${paramRows ? `<table class="tt-curl-params"><thead><tr><th>Parameter</th><th>Description</th></tr></thead><tbody>${paramRows}</tbody></table>` : ''}
                ${note ? `<div class="tt-curl-note">${esc(note)}</div>` : ''}
                <div class="tt-curl-code-wrap">
                    <pre class="tt-curl-code">${esc(cmd)}</pre>
                    <button class="tt-dash-copy tt-curl-copy" onclick="TT.copy(${JSON.stringify(cmd)},this)" title="Copy">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    </button>
                </div>
            </div>`;
        };

        out.innerHTML = `
        <div class="tt-curl-docs">
            <div class="tt-curl-intro">
                <div class="tt-curl-intro-title">⚡ Time Toolkit REST API</div>
                <div class="tt-curl-intro-sub">Base URL — works from any HTTP client, script, or terminal</div>
                <div class="tt-curl-code-wrap" style="margin-top:10px">
                    <pre class="tt-curl-code">${esc(base)}</pre>
                    <button class="tt-dash-copy tt-curl-copy" onclick="TT.copy(${JSON.stringify(base)},this)" title="Copy">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    </button>
                </div>
                <div class="tt-curl-tips">
                    <span>✓ All endpoints accept GET or POST</span>
                    <span>✓ Returns JSON with status field</span>
                    <span>✓ CORS enabled — works cross-origin</span>
                    <span>✓ No auth required</span>
                </div>
            </div>

            ${endpoint('GET','help','List all endpoints & examples',[],'')}
            ${endpoint('GET','now','Current date & time in all formats — Gregorian, Hijri, Persian, Julian, Hebrew, Moon',
                [],'')}
            ${endpoint('GET','parse','Parse any date, timestamp, or ISO string',
                [['q','Any date string: 2025-01-15, 1719838273, Asia/Karachi, ISO 8601']],
                '&q='+today)}
            ${endpoint('GET','convert','Convert between Gregorian, Hijri, Persian, Julian, Hebrew calendar systems',
                [['date','Source date (YYYY-MM-DD or YYYY/MM/DD)'],['from','gregorian | hijri | persian | julian'],['to','gregorian | hijri | persian | julian | hebrew | all']],
                `&date=${today}&from=gregorian&to=all`)}
            ${endpoint('GET','diff','Date difference in years, months, days, hours, business days',
                [['from','Start date (YYYY-MM-DD)'],['to','End date (YYYY-MM-DD)']],
                `&from=2025-01-01&to=${today}`)}
            ${endpoint('GET','cron','Explain a cron expression + next 10 scheduled runs',
                [['expr','5-field cron expression (use + for spaces in URL)']],
                '&expr=0+9+*+*+1-5',
                'URL-encode spaces as + or %20')}
            ${endpoint('GET','moon','Moon phase, illumination %, age in days, next full moon',
                [['date','Date to check (YYYY-MM-DD). Default: today']],
                `&date=${today}`)}
            ${endpoint('GET','sunrise','Sunrise, sunset, solar noon for any coordinates',
                [['lat','Latitude (decimal degrees, e.g. 31.5204)'],['lng','Longitude (decimal degrees, e.g. 74.3587)'],['date','Date (YYYY-MM-DD). Default: today']],
                `&lat=31.5204&lng=74.3587&date=${today}`)}
            ${endpoint('GET','timestamp','Full breakdown of a Unix timestamp',
                [['ts','10-digit unix timestamp (or 13-digit ms)']],
                '&ts=1719838273')}
            ${endpoint('GET','formats','Date in 15+ international formats',
                [['date','Date (YYYY-MM-DD). Default: today']],
                `&date=${today}`)}
        </div>`;
    },

    /* ── Live Clock ── */
    startLiveClock() {
        const update = () => {
            const now = new Date();
            const setEl = (id, val) => { const e = $(id); if (e) e.textContent = val; };
            setEl('lc-time', now.toLocaleTimeString());
            setEl('lc-date', now.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }));
            setEl('lc-utc', now.toUTCString().split(' ').slice(4).join(' '));
            setEl('lc-unix', Math.floor(now.getTime() / 1000));
        };
        update();
        setInterval(update, 1000);
    },

    /* ═══ ACCORDION 1 — Basic Information ═══ */
    renderBasicInfo(p) {
        const out = $('basic-out');
        if (!out) return;
        if (!p.date) { out.innerHTML = noDataHTML('Provide a date or datetime'); return; }
        const d = p.date;

        const weekdays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const seasons = getSeason(d);
        const doy = getDayOfYear(d);
        const wn = getWeekNumber(d);
        const isLeap = isLeapYear(d.getFullYear());
        const q = Math.ceil((d.getMonth() + 1) / 3);
        const relStr = relativeTime(d);
        const tz = getTzAbbr(d);
        const offset = getUTCOffset(d);
        const century = Math.ceil(d.getFullYear() / 100);
        const millennium = Math.ceil(d.getFullYear() / 1000);

        out.innerHTML = `
        <div class="tt-grid" style="margin-bottom:12px">
            ${valCell('Full Date', d.toLocaleDateString(undefined, { weekday:'long', year:'numeric', month:'long', day:'numeric' }))}
            ${valCell('Full Time', d.toLocaleTimeString())}
            ${valCell('Weekday', weekdays[d.getDay()])}
            ${valCell('Day of Month', d.getDate(), true)}
            ${valCell('Month Name', months[d.getMonth()])}
            ${valCell('Month Number', d.getMonth() + 1, true)}
            ${valCell('Quarter', 'Q' + q, true)}
            ${valCell('Week Number', wn, true)}
            ${valCell('Day of Year', doy, true)}
            ${valCell('Leap Year', isLeap ? 'Yes ✓' : 'No')}
            ${valCell('Type', d.getDay() === 0 || d.getDay() === 6 ? 'Weekend' : 'Weekday')}
            ${valCell('Season', seasons)}
            ${valCell('Century', century + getOrdinalSuffix(century), true)}
            ${valCell('Millennium', millennium + getOrdinalSuffix(millennium), true)}
            ${valCell('UTC Offset', offset, true)}
            ${valCell('Timezone', tz)}
        </div>
        <div class="tt-grid">
            ${valCell('Relative', relStr)}
            ${valCell('ISO Week', d.getFullYear() + '-W' + pad(wn), true)}
            ${valCell('Fiscal Quarter', 'FQ' + Math.ceil((d.getMonth() + 1) / 3) + ' ' + (d.getMonth() >= 9 ? d.getFullYear() + 1 : d.getFullYear()))}
            ${valCell('Academic Year', d.getMonth() >= 8 ? d.getFullYear() + '–' + (d.getFullYear() + 1) : (d.getFullYear() - 1) + '–' + d.getFullYear())}
            ${valCell('Locale (US)', d.toLocaleString('en-US'))}
            ${valCell('Locale (UK)', d.toLocaleString('en-GB'))}
        </div>`;
    },

    /* ═══ ACCORDION 2 — Timestamp Toolkit ═══ */
    renderTimestamp(p) {
        const out = $('ts-out');
        if (!out) return;

        let unix = p.unix || Math.floor(Date.now() / 1000);
        let ms = p.ms || Date.now();
        let d = p.date || new Date(ms);

        const now = new Date();
        const maxInt32 = 2147483647;
        const maxInt64 = '9223372036854775807';

        out.innerHTML = `
        <div class="tt-section">
            <div class="tt-section-title">Conversions</div>
            <div class="tt-grid">
                ${valCell('Unix Timestamp', unix, true)}
                ${valCell('Milliseconds', ms, true)}
                ${valCell('Microseconds', ms * 1000, true)}
                ${valCell('UTC ISO', d.toISOString(), true)}
                ${valCell('Local Datetime', d.toLocaleString(), false)}
                ${valCell('UTC String', d.toUTCString())}
            </div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Limits</div>
            <div class="tt-grid">
                ${valCell('Unix 32-bit Max', maxInt32 + ' (Jan 19, 2038)', true)}
                ${valCell('Days Until 2038', Math.ceil((new Date('2038-01-19') - now) / 86400000) + ' days', true)}
                ${valCell('Current % of 32-bit', ((unix / maxInt32) * 100).toFixed(2) + '%', true)}
                ${valCell('Unix 64-bit Max', maxInt64, true)}
            </div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Unix → Date Converter</div>
            <div class="tt-row">
                <input class="tt-input" id="ts-unix-in" placeholder="Unix timestamp" value="${unix}" onkeyup="TT.convertUnixToDate()">
                <button class="tt-btn tt-btn-primary" onclick="TT.convertUnixToDate()">Convert</button>
            </div>
            <div id="ts-unix-res" class="tt-result" style="margin-top:10px"></div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Date → Unix Converter</div>
            <div class="tt-row">
                <input class="tt-input" id="ts-date-in" type="datetime-local" value="${toLocalDatetimeStr(d)}">
                <button class="tt-btn tt-btn-primary" onclick="TT.convertDateToUnix()">Convert</button>
            </div>
            <div id="ts-date-res" class="tt-result" style="margin-top:10px"></div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Timestamp Difference</div>
            <div class="tt-row">
                <input class="tt-input" id="ts-diff-a" placeholder="Timestamp A (unix)" value="${unix}">
                <input class="tt-input" id="ts-diff-b" placeholder="Timestamp B (unix)" value="${Math.floor(now.getTime()/1000)}">
                <button class="tt-btn tt-btn-primary" onclick="TT.tsDiff()">Calculate</button>
            </div>
            <div id="ts-diff-res" class="tt-result" style="margin-top:10px"></div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Batch Timestamp Converter</div>
            <textarea class="tt-input" id="ts-batch-in" rows="4" placeholder="Paste timestamps one per line (unix or ms)..." style="min-width:100%;resize:vertical"></textarea>
            <button class="tt-btn tt-btn-primary" style="margin-top:6px" onclick="TT.tsBatch()">Convert All</button>
            <div id="ts-batch-res" class="tt-result" style="margin-top:10px"></div>
        </div>`;

        this.convertUnixToDate();
        this.convertDateToUnix();
    },

    convertUnixToDate() {
        const val = ($('ts-unix-in') || {}).value || '';
        const res = $('ts-unix-res');
        if (!res) return;
        const n = parseInt(val);
        if (isNaN(n)) { res.innerHTML = '<div class="tt-result-error">Invalid timestamp</div>'; return; }
        const d = new Date(n < 1e12 ? n * 1000 : n);
        if (isNaN(d)) { res.innerHTML = '<div class="tt-result-error">Could not convert</div>'; return; }
        res.innerHTML = `<div class="tt-grid">${valCell('UTC',d.toUTCString())}${valCell('ISO',d.toISOString(),true)}${valCell('Local',d.toLocaleString())}${valCell('Relative',relativeTime(d))}</div>`;
    },

    convertDateToUnix() {
        const val = ($('ts-date-in') || {}).value || '';
        const res = $('ts-date-res');
        if (!res) return;
        const d = new Date(val);
        if (isNaN(d)) { res.innerHTML = ''; return; }
        const unix = Math.floor(d.getTime() / 1000);
        res.innerHTML = `<div class="tt-grid">${valCell('Unix', unix, true)}${valCell('Milliseconds', d.getTime(), true)}${valCell('ISO', d.toISOString(), true)}</div>`;
    },

    tsDiff() {
        const a = parseInt(($('ts-diff-a') || {}).value || 0);
        const b = parseInt(($('ts-diff-b') || {}).value || 0);
        const res = $('ts-diff-res');
        if (!res) return;
        if (isNaN(a) || isNaN(b)) { res.innerHTML = '<div class="tt-result-error">Enter valid timestamps</div>'; return; }
        const diff = Math.abs(b - a);
        const secs = diff; const mins = Math.floor(diff/60); const hours = Math.floor(diff/3600);
        const days = Math.floor(diff/86400); const weeks = Math.floor(days/7);
        res.innerHTML = `<div class="tt-grid">
            ${valCell('Seconds', secs.toLocaleString(), true)}
            ${valCell('Minutes', mins.toLocaleString(), true)}
            ${valCell('Hours', hours.toLocaleString(), true)}
            ${valCell('Days', days.toLocaleString(), true)}
            ${valCell('Weeks', weeks.toLocaleString(), true)}
            ${valCell('Human', formatDuration(diff))}
        </div>`;
    },

    tsBatch() {
        const lines = (($('ts-batch-in') || {}).value || '').split('\n').filter(l => l.trim());
        const res = $('ts-batch-res');
        if (!res) return;
        if (!lines.length) { res.innerHTML = ''; return; }
        let rows = lines.map(l => {
            const n = parseInt(l.trim());
            if (isNaN(n)) return `<tr><td>${esc(l.trim())}</td><td colspan="3" style="color:var(--color-danger)">Invalid</td></tr>`;
            const d = new Date(n < 1e12 ? n * 1000 : n);
            return `<tr><td>${esc(l.trim())}</td><td>${esc(d.toUTCString())}</td><td>${esc(d.toISOString())}</td><td>${esc(relativeTime(d))}</td></tr>`;
        }).join('');
        res.innerHTML = `<div class="tt-table-wrap"><table class="tt-table"><thead><tr><th>Input</th><th>UTC</th><th>ISO</th><th>Relative</th></tr></thead><tbody>${rows}</tbody></table></div>`;
    },

    /* ═══ ACCORDION 3 — Date Format Toolkit ═══ */
    renderFormats(p) {
        const out = $('fmt-out');
        if (!out) return;
        if (!p.date) { out.innerHTML = noDataHTML('Provide a date'); return; }
        const d = p.date;

        const Y = d.getFullYear(), M = pad(d.getMonth()+1), D = pad(d.getDate());
        const h = pad(d.getHours()), mi = pad(d.getMinutes()), s = pad(d.getSeconds());
        const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const Mname = monthNames[d.getMonth()];
        const Mshort = Mname.slice(0,3);

        const formats = [
            ['YYYY-MM-DD', `${Y}-${M}-${D}`],
            ['DD/MM/YYYY', `${D}/${M}/${Y}`],
            ['MM/DD/YYYY', `${M}/${D}/${Y}`],
            ['DD-MM-YYYY', `${D}-${M}-${Y}`],
            ['DD MMM YYYY', `${D} ${Mshort} ${Y}`],
            ['MMMM D, YYYY', `${Mname} ${d.getDate()}, ${Y}`],
            ['D MMMM YYYY', `${d.getDate()} ${Mname} ${Y}`],
            ['YYYY/MM/DD', `${Y}/${M}/${D}`],
            ['MM-DD-YYYY', `${M}-${D}-${Y}`],
            ['YYYYMMDD', `${Y}${M}${D}`],
            ['ISO 8601', d.toISOString()],
            ['RFC 3339', d.toISOString().replace('T',' ').replace(/\.\d{3}Z$/,'+00:00')],
            ['RFC 2822', d.toUTCString()],
            ['Unix Timestamp', Math.floor(d.getTime()/1000).toString()],
            ['Milliseconds', d.getTime().toString()],
            ['Full Datetime', `${Y}-${M}-${D} ${h}:${mi}:${s}`],
            ['Date + 12h Time', `${M}/${D}/${Y} ${d.toLocaleTimeString('en-US', {hour:'2-digit',minute:'2-digit',hour12:true})}`],
            ['Compact', `${Y}${M}${D}T${h}${mi}${s}`],
            ['US Long', d.toLocaleDateString('en-US', {weekday:'long',year:'numeric',month:'long',day:'numeric'})],
            ['UK Long', d.toLocaleDateString('en-GB', {weekday:'long',year:'numeric',month:'long',day:'numeric'})],
            ['Short Date', d.toLocaleDateString()],
            ['Short Time', d.toLocaleTimeString()],
        ];

        const phpSnippets = [
            ['YYYY-MM-DD', `date('Y-m-d', $timestamp);`],
            ['DD/MM/YYYY', `date('d/m/Y', $timestamp);`],
            ['ISO 8601', `date('c', $timestamp);`],
            ['RFC 2822', `date('r', $timestamp);`],
            ['Unix', `time();`],
            ['Custom', `date('D, d M Y H:i:s T', $timestamp);`],
        ];

        const jsSnippets = [
            ['ISO 8601', `new Date().toISOString()`],
            ['UTC String', `new Date().toUTCString()`],
            ['Unix', `Math.floor(Date.now() / 1000)`],
            ['Locale', `new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })`],
            ['Custom', `const d = new Date(${d.getTime()});\nd.toLocaleString('en-US', { timeZone: 'UTC' });`],
        ];

        const pySnippets = [
            ['ISO 8601', `from datetime import datetime\ndatetime.utcnow().isoformat() + 'Z'`],
            ['Unix', `import time\ntime.time()`],
            ['Custom', `from datetime import datetime\ndatetime.now().strftime('%Y-%m-%d %H:%M:%S')`],
        ];

        const sqlSnippets = [
            ['MySQL NOW()', `SELECT NOW();`],
            ['MySQL format', `SELECT DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s');`],
            ['PostgreSQL', `SELECT NOW()::timestamp;`],
            ['PostgreSQL format', `SELECT TO_CHAR(NOW(), 'YYYY-MM-DD HH24:MI:SS');`],
            ['SQLite', `SELECT datetime('now');`],
            ['SQLite unix', `SELECT strftime('%s', 'now');`],
        ];

        let rows = formats.map(([label, val]) => fmtRow(label, val)).join('');

        out.innerHTML = `
        <div class="tt-section">
            <div class="tt-section-title">All Formats</div>
            <div class="tt-table-wrap">
                <table class="tt-table"><thead><tr><th>Format</th><th>Value</th><th></th></tr></thead>
                <tbody>${rows}</tbody></table>
            </div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Custom Format</div>
            <div class="tt-row">
                <input class="tt-input" id="fmt-custom-in" placeholder="e.g. YYYY/MM/DD HH:mm:ss" value="YYYY-MM-DD HH:mm:ss">
                <button class="tt-btn tt-btn-primary" onclick="TT.customFormat()">Format</button>
            </div>
            <div id="fmt-custom-res" class="tt-result" style="margin-top:10px"></div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">PHP Snippets</div>
            ${phpSnippets.map(([l,c]) => `<div style="margin-bottom:6px"><div class="tt-val-label">${esc(l)}</div>${codeBlock('php', c)}</div>`).join('')}
        </div>
        <div class="tt-section">
            <div class="tt-section-title">JavaScript Snippets</div>
            ${jsSnippets.map(([l,c]) => `<div style="margin-bottom:6px"><div class="tt-val-label">${esc(l)}</div>${codeBlock('js', c)}</div>`).join('')}
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Python Snippets</div>
            ${pySnippets.map(([l,c]) => `<div style="margin-bottom:6px"><div class="tt-val-label">${esc(l)}</div>${codeBlock('python', c)}</div>`).join('')}
        </div>
        <div class="tt-section">
            <div class="tt-section-title">SQL Snippets</div>
            ${sqlSnippets.map(([l,c]) => `<div style="margin-bottom:6px"><div class="tt-val-label">${esc(l)}</div>${codeBlock('sql', c)}</div>`).join('')}
        </div>`;

        this.customFormat(d);
    },

    customFormat(d_override) {
        const inp = $('fmt-custom-in');
        const res = $('fmt-custom-res');
        if (!res) return;
        const d = d_override instanceof Date ? d_override : (this._parsed?.date || new Date());
        const fmt = inp ? inp.value : 'YYYY-MM-DD';

        const result = fmt
            .replace('YYYY', d.getFullYear())
            .replace('YY', String(d.getFullYear()).slice(-2))
            .replace('MM', pad(d.getMonth()+1))
            .replace('M', d.getMonth()+1)
            .replace('DD', pad(d.getDate()))
            .replace('D', d.getDate())
            .replace('HH', pad(d.getHours()))
            .replace('H', d.getHours())
            .replace('hh', pad(d.getHours() > 12 ? d.getHours()-12 : d.getHours() || 12))
            .replace('mm', pad(d.getMinutes()))
            .replace('ss', pad(d.getSeconds()))
            .replace('A', d.getHours() >= 12 ? 'PM' : 'AM')
            .replace('a', d.getHours() >= 12 ? 'pm' : 'am');

        res.innerHTML = `<div class="tt-result-inner" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
            <span style="font-family:var(--font-mono);font-size:15px;font-weight:600">${esc(result)}</span>
            <button class="tt-btn tt-btn-sm" onclick="TT.copy(${JSON.stringify(result)}, this)">${iconSVG('copy')} Copy</button>
        </div>`;
    },

    /* ═══ ACCORDION 4 — Calendar Toolkit ═══ */
    renderCalendar(d) {
        if (d) this._calDate = new Date(d);
        const calEl = $('cal-grid');
        if (!calEl) return;
        const target = this._calDate || new Date();
        const displayDate = this._parsed?.date || null;
        calEl.innerHTML = buildCalendarHTML(target, displayDate);
    },

    calNav(delta) {
        const d = this._calDate || new Date();
        this._calDate = new Date(d.getFullYear(), d.getMonth() + delta, 1);
        this.renderCalendar(null);
    },

    /* ═══ ACCORDION 5 — Timezone Toolkit ═══ */
    buildWorldClock() {
        const keyTZs = [
            ['New York', 'America/New_York'],
            ['London', 'Europe/London'],
            ['Paris', 'Europe/Paris'],
            ['Dubai', 'Asia/Dubai'],
            ['Karachi', 'Asia/Karachi'],
            ['Delhi', 'Asia/Kolkata'],
            ['Singapore', 'Asia/Singapore'],
            ['Tokyo', 'Asia/Tokyo'],
            ['Sydney', 'Australia/Sydney'],
            ['Los Angeles', 'America/Los_Angeles'],
            ['São Paulo', 'America/Sao_Paulo'],
            ['Moscow', 'Europe/Moscow'],
        ];
        const grid = $('world-clock-grid');
        if (!grid) return;
        const update = () => {
            const now = new Date();
            grid.innerHTML = keyTZs.map(([city, tz]) => {
                let time = '', date2 = '', offset = '';
                try {
                    time = now.toLocaleTimeString('en-US', { timeZone: tz, hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    date2 = now.toLocaleDateString('en-US', { timeZone: tz, weekday: 'short', month: 'short', day: 'numeric' });
                    const offsetMs = getTimezoneOffset(tz, now);
                    const sign = offsetMs >= 0 ? '+' : '-';
                    const abs = Math.abs(offsetMs);
                    offset = `UTC${sign}${pad(Math.floor(abs/60))}:${pad(abs%60)}`;
                } catch (e) { time = 'N/A'; }
                return `<div class="tt-wc-card">
                    <div class="tt-wc-tz">${esc(city)} · ${esc(tz)}</div>
                    <div class="tt-wc-time">${esc(time)}</div>
                    <div class="tt-wc-date">${esc(date2)}</div>
                    <div class="tt-wc-offset">${esc(offset)}</div>
                </div>`;
            }).join('');
        };
        update();
        setInterval(update, 1000);
    },

    renderTimezones(p) {
        const out = $('tz-out');
        if (!out) return;
        const d = p.date || new Date();
        const inputTz = p.tz || null;

        const keyConversions = [
            ['UTC', 'UTC'], ['New York', 'America/New_York'], ['London', 'Europe/London'],
            ['Paris', 'Europe/Paris'], ['Berlin', 'Europe/Berlin'], ['Moscow', 'Europe/Moscow'],
            ['Dubai', 'Asia/Dubai'], ['Karachi', 'Asia/Karachi'], ['Delhi', 'Asia/Kolkata'],
            ['Bangkok', 'Asia/Bangkok'], ['Singapore', 'Asia/Singapore'], ['Tokyo', 'Asia/Tokyo'],
            ['Sydney', 'Australia/Sydney'], ['Los Angeles', 'America/Los_Angeles'],
            ['Chicago', 'America/Chicago'], ['São Paulo', 'America/Sao_Paulo'],
        ];

        const rows = keyConversions.map(([city, tz]) => {
            try {
                const timeStr = d.toLocaleTimeString('en-US', { timeZone: tz, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                const dateStr = d.toLocaleDateString('en-US', { timeZone: tz, year: 'numeric', month: 'short', day: 'numeric' });
                const ofsMin = getTimezoneOffset(tz, d);
                const sign = ofsMin >= 0 ? '+' : '-';
                const abs = Math.abs(ofsMin);
                const ofsStr = `UTC${sign}${pad(Math.floor(abs/60))}:${pad(abs%60)}`;
                const isDst = isDST(tz, d);
                return `<tr><td>${esc(city)}</td><td>${esc(tz)}</td><td style="font-family:var(--font-mono)">${esc(timeStr)}</td><td>${esc(dateStr)}</td><td>${esc(ofsStr)}</td><td>${isDst ? '<span class="tt-badge tt-badge-warning">DST</span>' : ''}</td></tr>`;
            } catch (e) { return ''; }
        }).join('');

        out.innerHTML = `
        <div class="tt-section">
            <div class="tt-section-title">Timezone Conversions</div>
            <div class="tt-table-wrap">
                <table class="tt-table"><thead><tr><th>City</th><th>Timezone</th><th>Time</th><th>Date</th><th>Offset</th><th>DST</th></tr></thead>
                <tbody>${rows}</tbody></table>
            </div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Custom Timezone Converter</div>
            <div class="tt-row">
                <input class="tt-input" id="tz-date-in" type="datetime-local" value="${toLocalDatetimeStr(d)}">
                <select class="tt-select" id="tz-zone-sel">
                    ${TZLIST.map(tz => `<option value="${tz}"${tz==='UTC'?' selected':''}>${tz}</option>`).join('')}
                </select>
                <button class="tt-btn tt-btn-primary" onclick="TT.tzConvert()">Convert</button>
            </div>
            <div id="tz-conv-res" class="tt-result" style="margin-top:10px"></div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Meeting Planner</div>
            <p style="font-size:12px;color:var(--color-text-muted);margin:0 0 10px">Find overlapping working hours (9am–5pm) across timezones</p>
            <div class="tt-row">
                <select class="tt-select" id="mtg-tz1"><option value="America/New_York">New York</option>${TZLIST.map(t=>`<option>${t}</option>`).join('')}</select>
                <select class="tt-select" id="mtg-tz2"><option value="Europe/London">London</option>${TZLIST.map(t=>`<option>${t}</option>`).join('')}</select>
                <select class="tt-select" id="mtg-tz3"><option value="Asia/Karachi">Karachi</option>${TZLIST.map(t=>`<option>${t}</option>`).join('')}</select>
                <button class="tt-btn tt-btn-primary" onclick="TT.meetingPlanner()">Find Overlap</button>
            </div>
            <div id="mtg-res" class="tt-result" style="margin-top:10px"></div>
        </div>`;
    },

    tzConvert() {
        const dateVal = ($('tz-date-in') || {}).value;
        const tz = ($('tz-zone-sel') || {}).value || 'UTC';
        const res = $('tz-conv-res');
        if (!res) return;
        const d = new Date(dateVal);
        if (isNaN(d)) { res.innerHTML = '<div class="tt-result-error">Invalid date</div>'; return; }
        try {
            const opts = { timeZone: tz, year:'numeric',month:'long',day:'numeric',weekday:'long',hour:'2-digit',minute:'2-digit',second:'2-digit',timeZoneName:'long' };
            const str = d.toLocaleString('en-US', opts);
            res.innerHTML = `<div class="tt-result-inner">${iconSVG('globe')}&nbsp;<strong>${esc(tz)}</strong>: ${esc(str)}</div>`;
        } catch (e) { res.innerHTML = '<div class="tt-result-error">Timezone error</div>'; }
    },

    meetingPlanner() {
        const tzs = [
            ($('mtg-tz1') || {}).value || 'UTC',
            ($('mtg-tz2') || {}).value || 'UTC',
            ($('mtg-tz3') || {}).value || 'UTC',
        ];
        const res = $('mtg-res');
        if (!res) return;
        const now = new Date();
        const baseDay = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const overlaps = [];
        for (let h = 0; h < 24; h++) {
            const slot = new Date(baseDay.getTime() + h * 3600000);
            const allInBiz = tzs.every(tz => {
                try {
                    const localH = parseInt(slot.toLocaleString('en-US', { timeZone: tz, hour: '2-digit', hour12: false }));
                    return localH >= 9 && localH < 17;
                } catch { return false; }
            });
            if (allInBiz) overlaps.push(h);
        }
        if (!overlaps.length) {
            res.innerHTML = '<div class="tt-result-error">No overlapping business hours found between these timezones</div>';
            return;
        }
        const slots = overlaps.map(h => {
            const slot = new Date(baseDay.getTime() + h * 3600000);
            return `<div style="background:var(--color-success-light);border:1px solid var(--color-success);border-radius:4px;padding:6px 10px;margin-bottom:4px">
                <span style="font-weight:600;color:var(--color-success)">UTC ${pad(h)}:00</span>
                ${tzs.map(tz => { try { return ` · ${tz.split('/').pop()}: ${slot.toLocaleTimeString('en-US', {timeZone:tz, hour:'2-digit', minute:'2-digit', hour12:true})}`; } catch { return ''; } }).join('')}
            </div>`;
        }).join('');
        res.innerHTML = `<div class="tt-result-inner"><div class="tt-val-label" style="margin-bottom:8px">Overlapping windows (${overlaps.length}h)</div>${slots}</div>`;
    },

    /* ═══ ACCORDION 6 — Date Arithmetic ═══ */
    renderArithmetic(p) {
        const out = $('arith-out');
        if (!out) return;
        const d = p.date || new Date();

        out.innerHTML = `
        <div class="tt-section">
            <div class="tt-section-title">Add / Subtract from Date</div>
            <div class="tt-row">
                <input class="tt-input" id="arith-date" type="date" value="${d.toISOString().slice(0,10)}">
                <select class="tt-select" id="arith-op"><option value="add">Add</option><option value="sub">Subtract</option></select>
                <input class="tt-input" id="arith-amount" type="number" value="7" min="0" style="max-width:80px">
                <select class="tt-select" id="arith-unit">
                    <option value="days">Days</option><option value="weeks">Weeks</option>
                    <option value="months">Months</option><option value="years">Years</option>
                    <option value="hours">Hours</option><option value="minutes">Minutes</option>
                    <option value="bizdays">Business Days</option>
                </select>
                <button class="tt-btn tt-btn-primary" onclick="TT.doArithmetic()">Calculate</button>
            </div>
            <div id="arith-res" class="tt-result" style="margin-top:10px"></div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Quick Offsets from ${d.toLocaleDateString()}</div>
            <div class="tt-grid" id="arith-quick"></div>
        </div>`;

        this.doArithmetic(d);
        this.renderArithQuick(d);
    },

    renderArithQuick(base) {
        const el = $('arith-quick');
        if (!el || !base) return;
        const offsets = [
            ['Yesterday', -1, 'days'], ['Tomorrow', 1, 'days'],
            ['+1 Week', 7, 'days'], ['-1 Week', -7, 'days'],
            ['+1 Month', 1, 'months'], ['+3 Months', 3, 'months'],
            ['+6 Months', 6, 'months'], ['+1 Year', 1, 'years'],
            ['Next Monday', null, 'next_mon'], ['Next Friday', null, 'next_fri'],
            ['Start of Month', null, 'som'], ['End of Month', null, 'eom'],
        ];
        el.innerHTML = offsets.map(([label, amt, unit]) => {
            let result;
            if (unit === 'next_mon') result = nextWeekday(base, 1);
            else if (unit === 'next_fri') result = nextWeekday(base, 5);
            else if (unit === 'som') result = new Date(base.getFullYear(), base.getMonth(), 1);
            else if (unit === 'eom') result = new Date(base.getFullYear(), base.getMonth()+1, 0);
            else result = addToDate(base, amt, unit);
            return valCell(label, result.toLocaleDateString(undefined, { weekday:'short', year:'numeric', month:'short', day:'numeric' }));
        }).join('');
    },

    doArithmetic(d_override) {
        const dateVal = ($('arith-date') || {}).value;
        const op = ($('arith-op') || {}).value || 'add';
        const amt = parseInt(($('arith-amount') || {}).value || 7);
        const unit = ($('arith-unit') || {}).value || 'days';
        const res = $('arith-res');
        if (!res) return;
        const base = d_override instanceof Date ? d_override : (dateVal ? new Date(dateVal + 'T00:00:00') : new Date());
        if (isNaN(base)) { res.innerHTML = '<div class="tt-result-error">Invalid date</div>'; return; }

        const realAmt = op === 'sub' ? -amt : amt;
        let result;
        if (unit === 'bizdays') {
            result = addBusinessDays(base, realAmt);
        } else {
            result = addToDate(base, realAmt, unit);
        }

        res.innerHTML = `<div class="tt-grid">
            ${valCell('Result', result.toLocaleDateString(undefined, {weekday:'long',year:'numeric',month:'long',day:'numeric'}))}
            ${valCell('ISO', result.toISOString().slice(0,10), true)}
            ${valCell('Unix', Math.floor(result.getTime()/1000), true)}
        </div>`;
    },

    /* ═══ ACCORDION 7 — Date Difference ═══ */
    renderDifference(p) {
        const out = $('diff-out');
        if (!out) return;
        const d = p.date || new Date();

        out.innerHTML = `
        <div class="tt-section">
            <div class="tt-section-title">Date Difference</div>
            <div class="tt-row">
                <input class="tt-input" id="diff-a" type="date" value="${d.toISOString().slice(0,10)}">
                <span class="tt-label">to</span>
                <input class="tt-input" id="diff-b" type="date" value="${new Date().toISOString().slice(0,10)}">
                <button class="tt-btn tt-btn-primary" onclick="TT.calcDiff()">Calculate</button>
            </div>
            <div id="diff-res" class="tt-result" style="margin-top:10px"></div>
        </div>`;
        this.calcDiff(d, new Date());
    },

    calcDiff(a_override, b_override) {
        const aVal = ($('diff-a') || {}).value;
        const bVal = ($('diff-b') || {}).value;
        const res = $('diff-res');
        if (!res) return;
        const a = a_override instanceof Date ? a_override : (aVal ? new Date(aVal + 'T00:00:00') : new Date());
        const b = b_override instanceof Date ? b_override : (bVal ? new Date(bVal + 'T00:00:00') : new Date());
        if (isNaN(a) || isNaN(b)) { res.innerHTML = '<div class="tt-result-error">Invalid dates</div>'; return; }

        const ms = Math.abs(b - a);
        const secs = Math.floor(ms / 1000);
        const mins = Math.floor(ms / 60000);
        const hours = Math.floor(ms / 3600000);
        const days = Math.floor(ms / 86400000);
        const weeks = Math.floor(days / 7);
        const [aD, bD] = a < b ? [a, b] : [b, a];
        const months = monthsDiff(aD, bD);
        const years = Math.floor(months / 12);
        const bizDays = countBusinessDays(aD, bD);
        const weekends = Math.floor(days / 7) * 2 + [0,1].filter(wd => {
            const c = new Date(aD); let cnt = 0;
            while (c <= bD) { if (c.getDay()===0||c.getDay()===6) cnt++; c.setDate(c.getDate()+1); }
            return false;
        }).length;
        const weekendDays = days - bizDays;

        res.innerHTML = `<div class="tt-grid">
            ${valCell('Years', years.toLocaleString(), true)}
            ${valCell('Months', months.toLocaleString(), true)}
            ${valCell('Weeks', weeks.toLocaleString(), true)}
            ${valCell('Days', days.toLocaleString(), true)}
            ${valCell('Hours', hours.toLocaleString(), true)}
            ${valCell('Minutes', mins.toLocaleString(), true)}
            ${valCell('Seconds', secs.toLocaleString(), true)}
            ${valCell('Business Days', bizDays.toLocaleString(), true)}
            ${valCell('Weekend Days', weekendDays.toLocaleString(), true)}
            ${valCell('Human Readable', formatDuration(ms / 1000))}
        </div>`;
    },

    /* ═══ ACCORDION 8 — Age Toolkit ═══ */
    renderAge(p) {
        const out = $('age-out');
        if (!out) return;
        const d = p.date || new Date('1990-01-01');

        out.innerHTML = `
        <div class="tt-section">
            <div class="tt-section-title">Date of Birth</div>
            <div class="tt-row">
                <input class="tt-input" id="age-dob" type="date" value="${d.toISOString().slice(0,10)}">
                <button class="tt-btn tt-btn-primary" onclick="TT.calcAge()">Calculate Age</button>
            </div>
            <div id="age-res" class="tt-result" style="margin-top:10px"></div>
        </div>`;
        this.calcAge(d);
    },

    calcAge(dob_override) {
        const val = ($('age-dob') || {}).value;
        const res = $('age-res');
        if (!res) return;
        const dob = dob_override instanceof Date ? dob_override : (val ? new Date(val + 'T00:00:00') : null);
        if (!dob || isNaN(dob)) { res.innerHTML = '<div class="tt-result-error">Enter a valid birth date</div>'; return; }

        const now = new Date();
        const ms = now - dob;
        if (ms < 0) { res.innerHTML = '<div class="tt-result-error">Date is in the future</div>'; return; }

        const totalSec = Math.floor(ms / 1000);
        const totalMin = Math.floor(ms / 60000);
        const totalHour = Math.floor(ms / 3600000);
        const totalDays = Math.floor(ms / 86400000);
        const totalWeeks = Math.floor(totalDays / 7);
        const months = monthsDiff(dob, now);
        const years = Math.floor(months / 12);
        const remMonths = months % 12;
        const remDays = Math.floor((now - new Date(dob.getFullYear() + years, dob.getMonth() + remMonths, dob.getDate())) / 86400000);

        // Next birthday
        const nextBday = new Date(now.getFullYear(), dob.getMonth(), dob.getDate());
        if (nextBday <= now) nextBday.setFullYear(now.getFullYear() + 1);
        const bdayDays = Math.ceil((nextBday - now) / 86400000);
        const bdayWeekday = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][nextBday.getDay()];

        // Half birthday
        const halfBday = new Date(dob.getTime() + ms/2 + (ms/2 % 86400000 === 0 ? 0 : 86400000 - ms/2 % 86400000));
        const nextHalf = new Date(now.getFullYear(), dob.getMonth()+6 > 11 ? dob.getMonth()-6 : dob.getMonth()+6, dob.getDate());
        if (nextHalf <= now) nextHalf.setFullYear(now.getFullYear() + 1);

        res.innerHTML = `
        <div class="tt-grid" style="margin-bottom:12px">
            ${valCell('Age', `${years}y ${remMonths}m ${remDays}d`, true, true)}
            ${valCell('Years', years.toLocaleString(), true)}
            ${valCell('Months', months.toLocaleString(), true)}
            ${valCell('Weeks', totalWeeks.toLocaleString(), true)}
            ${valCell('Days', totalDays.toLocaleString(), true)}
            ${valCell('Hours', totalHour.toLocaleString(), true)}
            ${valCell('Minutes', totalMin.toLocaleString(), true)}
            ${valCell('Seconds', totalSec.toLocaleString(), true)}
            ${valCell('Heartbeats ≈', (totalMin * 72).toLocaleString(), true)}
            ${valCell('Next Birthday', nextBday.toLocaleDateString())}
            ${valCell('Birthday Weekday', bdayWeekday)}
            ${valCell('Days Until Birthday', bdayDays + ' days', true)}
        </div>
        <div style="padding:0 0 4px">
            <div class="tt-val-label">Birthday Countdown</div>
            <div class="tt-countdown">
                ${['Days','Hours','Mins','Secs'].map((u,i) => {
                    const vals = [bdayDays, Math.floor((bdayDays*86400 - (now.getHours()*3600+now.getMinutes()*60+now.getSeconds()))/3600)%24, 59-now.getMinutes(), 59-now.getSeconds()];
                    return `<div class="tt-cd-unit"><div class="tt-cd-val">${pad(Math.max(0,vals[i]))}</div><div class="tt-cd-label">${u}</div></div>`;
                }).join('')}
            </div>
        </div>`;
    },

    /* ═══ ACCORDION 9 — Astronomy ═══ */
    renderAstronomy(p) {
        const out = $('astro-out');
        if (!out) return;
        if (p.lat === null && !p.date) { out.innerHTML = noDataHTML('Provide coordinates (lat,lng) or click "Use My Location"'); return; }

        const hasCoords = p.lat !== null;
        const d = p.date || new Date();
        const lat = p.lat ?? 51.5074;
        const lng = p.lng ?? -0.1278;
        const locationStr = hasCoords ? `${lat.toFixed(4)}, ${lng.toFixed(4)}` : 'Default (London)';

        const sun = calcSunTimes(lat, lng, d);
        const moon = calcMoonPhase(d);

        out.innerHTML = `
        <div class="tt-section">
            <div class="tt-section-title">Location</div>
            <div class="tt-row" style="margin-bottom:10px">
                <input class="tt-input" id="astro-lat" placeholder="Latitude" value="${lat}" style="max-width:140px">
                <input class="tt-input" id="astro-lng" placeholder="Longitude" value="${lng}" style="max-width:140px">
                <input class="tt-input" id="astro-date" type="date" value="${d.toISOString().slice(0,10)}">
                <button class="tt-btn tt-btn-primary" onclick="TT.calcAstro()">Calculate</button>
                <button class="tt-btn" onclick="TT.getLocation()" title="Use my location">${iconSVG('map')} My Location</button>
            </div>
            <div class="tt-val-cell" style="margin-bottom:10px">
                <div class="tt-val-label">Using</div>
                <div class="tt-val-value">${esc(locationStr)}</div>
            </div>
        </div>
        <div id="astro-sun-out">
            <div class="tt-section">
                <div class="tt-section-title">☀️ Solar Information</div>
                <div class="tt-grid">
                    ${valCell('Sunrise', sun.sunrise || 'N/A')}
                    ${valCell('Sunset', sun.sunset || 'N/A')}
                    ${valCell('Day Length', sun.dayLength || 'N/A')}
                    ${valCell('Solar Noon', sun.solarNoon || 'N/A')}
                    ${valCell('Golden Hour AM', sun.goldenHourAM || 'N/A')}
                    ${valCell('Golden Hour PM', sun.goldenHourPM || 'N/A')}
                    ${valCell('Civil Twilight', sun.civilTwilight || 'N/A')}
                    ${valCell('Nautical Twilight', sun.nauticalTwilight || 'N/A')}
                    ${valCell('Astro Twilight', sun.astronomicalTwilight || 'N/A')}
                </div>
            </div>
            <div class="tt-section">
                <div class="tt-section-title">🌙 Moon Information</div>
                <div class="tt-grid">
                    ${valCell('Moon Phase', moon.phaseName)}
                    ${valCell('Illumination', moon.illumination + '%', true)}
                    ${valCell('Age (days)', moon.age.toFixed(1), true)}
                    ${valCell('Next Full Moon', moon.nextFull)}
                    ${valCell('Next New Moon', moon.nextNew)}
                </div>
            </div>
        </div>`;
    },

    calcAstro() {
        const lat = parseFloat(($('astro-lat')||{}).value || 0);
        const lng = parseFloat(($('astro-lng')||{}).value || 0);
        const dateVal = ($('astro-date')||{}).value;
        const d = dateVal ? new Date(dateVal + 'T12:00:00') : new Date();
        if (isNaN(lat)||isNaN(lng)) { showToast('Enter valid coordinates', 'alert'); return; }

        const sun = calcSunTimes(lat, lng, d);
        const moon = calcMoonPhase(d);
        const out = $('astro-sun-out');
        if (!out) return;
        out.innerHTML = `
            <div class="tt-section">
                <div class="tt-section-title">☀️ Solar Information</div>
                <div class="tt-grid">
                    ${valCell('Sunrise', sun.sunrise || 'N/A')}${valCell('Sunset', sun.sunset || 'N/A')}
                    ${valCell('Day Length', sun.dayLength || 'N/A')}${valCell('Solar Noon', sun.solarNoon || 'N/A')}
                    ${valCell('Golden Hour AM', sun.goldenHourAM || 'N/A')}${valCell('Golden Hour PM', sun.goldenHourPM || 'N/A')}
                    ${valCell('Civil Twilight', sun.civilTwilight || 'N/A')}${valCell('Nautical Twilight', sun.nauticalTwilight || 'N/A')}
                    ${valCell('Astro Twilight', sun.astronomicalTwilight || 'N/A')}
                </div>
            </div>
            <div class="tt-section">
                <div class="tt-section-title">🌙 Moon Information</div>
                <div class="tt-grid">
                    ${valCell('Moon Phase', moon.phaseName)}${valCell('Illumination', moon.illumination + '%', true)}
                    ${valCell('Age (days)', moon.age.toFixed(1), true)}${valCell('Next Full Moon', moon.nextFull)}
                    ${valCell('Next New Moon', moon.nextNew)}
                </div>
            </div>`;
    },

    getLocation() {
        if (!navigator.geolocation) { showToast('Geolocation not available', 'alert'); return; }
        showToast('Getting location…');
        navigator.geolocation.getCurrentPosition(pos => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            const latEl = $('astro-lat'), lngEl = $('astro-lng');
            if (latEl) latEl.value = lat.toFixed(4);
            if (lngEl) lngEl.value = lng.toFixed(4);
            this.calcAstro();
            showToast('Location updated!');
        }, () => showToast('Location denied', 'alert'));
    },

    /* ═══ ACCORDION 10 — Business Toolkit ═══ */
    renderBusiness(p) {
        const out = $('biz-out');
        if (!out) return;
        const d = p.date || new Date();
        const now = new Date();
        const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
        const endOfMonth = new Date(now.getFullYear(), now.getMonth()+1, 0);
        const bizDaysMonth = countBusinessDays(startOfMonth, endOfMonth);

        out.innerHTML = `
        <div class="tt-section">
            <div class="tt-section-title">This Month at a Glance</div>
            <div class="tt-grid">
                ${valCell('Business Days', bizDaysMonth, true)}
                ${valCell('Weekend Days', endOfMonth.getDate() - bizDaysMonth, true)}
                ${valCell('Total Days', endOfMonth.getDate(), true)}
                ${valCell('Days Remaining', countBusinessDays(now, endOfMonth), true)}
            </div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Business Days Between Dates</div>
            <div class="tt-row">
                <input class="tt-input" id="biz-a" type="date" value="${d.toISOString().slice(0,10)}">
                <span class="tt-label">to</span>
                <input class="tt-input" id="biz-b" type="date" value="${now.toISOString().slice(0,10)}">
                <button class="tt-btn tt-btn-primary" onclick="TT.calcBiz()">Calculate</button>
            </div>
            <div id="biz-res" class="tt-result" style="margin-top:10px"></div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Shift / Timesheet Calculator</div>
            <div class="tt-row">
                <label class="tt-label">Start</label>
                <input class="tt-input" id="shift-start" type="time" value="09:00">
                <label class="tt-label">End</label>
                <input class="tt-input" id="shift-end" type="time" value="17:00">
                <label class="tt-label">Break (min)</label>
                <input class="tt-input" id="shift-break" type="number" value="30" min="0" style="max-width:80px">
                <button class="tt-btn tt-btn-primary" onclick="TT.calcShift()">Calculate</button>
            </div>
            <div id="shift-res" class="tt-result" style="margin-top:10px"></div>
        </div>`;

        this.calcBiz(d, now);
        this.calcShift();
    },

    calcBiz(a_o, b_o) {
        const aVal = ($('biz-a')||{}).value;
        const bVal = ($('biz-b')||{}).value;
        const res = $('biz-res');
        if (!res) return;
        const a = a_o instanceof Date ? a_o : (aVal ? new Date(aVal+'T00:00:00') : new Date());
        const b = b_o instanceof Date ? b_o : (bVal ? new Date(bVal+'T00:00:00') : new Date());
        if (isNaN(a)||isNaN(b)) { res.innerHTML = '<div class="tt-result-error">Invalid dates</div>'; return; }
        const [s,e] = a<b ? [a,b] : [b,a];
        const biz = countBusinessDays(s, e);
        const total = Math.ceil((e-s)/86400000);
        res.innerHTML = `<div class="tt-grid">
            ${valCell('Business Days', biz, true)}${valCell('Weekend Days', total-biz, true)}
            ${valCell('Total Calendar Days', total, true)}${valCell('Work Weeks', (biz/5).toFixed(1), true)}
        </div>`;
    },

    calcShift() {
        const start = ($('shift-start')||{}).value || '09:00';
        const end = ($('shift-end')||{}).value || '17:00';
        const brk = parseInt(($('shift-break')||{}).value || 0);
        const res = $('shift-res');
        if (!res) return;
        const [sh,sm] = start.split(':').map(Number);
        const [eh,em] = end.split(':').map(Number);
        let totalMins = (eh*60+em) - (sh*60+sm);
        if (totalMins < 0) totalMins += 1440;
        const netMins = totalMins - brk;
        const h = Math.floor(netMins/60), m = netMins%60;
        res.innerHTML = `<div class="tt-grid">
            ${valCell('Total Duration', Math.floor(totalMins/60)+'h '+totalMins%60+'m', true)}
            ${valCell('Net Hours (excl. break)', h+'h '+m+'m', true)}
            ${valCell('Decimal Hours', (netMins/60).toFixed(2), true)}
        </div>`;
    },

    /* ═══ ACCORDION 11 — Developer Toolkit ═══ */
    renderDeveloper(p) {
        const out = $('dev-out');
        if (!out) return;
        const d = p.date || new Date();
        const unix = Math.floor(d.getTime()/1000);
        const iso = d.toISOString();
        const Y = d.getFullYear(), M = pad(d.getMonth()+1), D = pad(d.getDate());
        const h = pad(d.getHours()), mi = pad(d.getMinutes()), s = pad(d.getSeconds());

        const snippets = {
            php: `<?php
// Current timestamp
$timestamp = time(); // ${unix}
$datetime  = date('Y-m-d H:i:s', $timestamp);
$iso       = date('c', $timestamp);

// Parse your date
$dt = new DateTime('${iso}');
echo $dt->format('Y-m-d H:i:s');

// Modify
$dt->modify('+7 days');
$dt->setTimezone(new DateTimeZone('Asia/Karachi'));

// Diff
$a = new DateTime('${Y}-${M}-${D}');
$b = new DateTime('now');
$diff = $a->diff($b);
echo $diff->days . ' days';`,

            js: `// Current timestamp
const unix = Math.floor(Date.now() / 1000); // ${unix}
const d    = new Date(${d.getTime()});

// Format
d.toISOString();       // '${iso}'
d.toUTCString();       // '${d.toUTCString()}'
d.toLocaleDateString(); // locale

// Timezone
const nyTime = d.toLocaleString('en-US', { timeZone: 'America/New_York' });

// Diff
const diffMs = Date.now() - ${d.getTime()};
const diffDays = Math.floor(diffMs / 86400000);

// Add days (no library)
const future = new Date(d);
future.setDate(future.getDate() + 7);`,

            python: `from datetime import datetime, timezone, timedelta
import time

# Current timestamp
unix = int(time.time())  # ${unix}
now  = datetime.now()
utc  = datetime.now(timezone.utc)

# Parse your date
dt = datetime.fromisoformat('${iso.slice(0,-1)}')

# Format
dt.strftime('%Y-%m-%d %H:%M:%S')  # '${Y}-${M}-${D} ${h}:${mi}:${s}'

# Timezone
from zoneinfo import ZoneInfo
tz_dt = dt.astimezone(ZoneInfo('Asia/Karachi'))

# Add days
future = dt + timedelta(days=7)

# Diff
delta = datetime.now() - dt
print(delta.days, 'days')`,

            sql: `-- MySQL
SELECT NOW();                                         -- ${Y}-${M}-${D} ${h}:${mi}:${s}
SELECT UNIX_TIMESTAMP();                              -- ${unix}
SELECT FROM_UNIXTIME(${unix});
SELECT DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s');
SELECT DATEDIFF('${Y}-${M}-${D}', CURDATE());
SELECT DATE_ADD(NOW(), INTERVAL 7 DAY);

-- PostgreSQL
SELECT NOW()::timestamp;
SELECT EXTRACT(EPOCH FROM NOW())::bigint;             -- ${unix}
SELECT TO_TIMESTAMP(${unix});
SELECT TO_CHAR(NOW(), 'YYYY-MM-DD HH24:MI:SS');
SELECT NOW() + INTERVAL '7 days';

-- SQLite
SELECT datetime('now');                               -- ${Y}-${M}-${D} ${h}:${mi}:${s}
SELECT strftime('%s', 'now');                         -- ${unix}
SELECT datetime(${unix}, 'unixepoch');
SELECT datetime('now', '+7 days');`
        };

        out.innerHTML = `
        <div data-tabs>
            <div class="tt-tabs">
                <div class="tt-tab" data-tab="php">PHP</div>
                <div class="tt-tab" data-tab="js">JavaScript</div>
                <div class="tt-tab" data-tab="py">Python</div>
                <div class="tt-tab" data-tab="sql">SQL</div>
            </div>
            <div class="tt-tab-panel" data-panel="php">${codeBlock('php', snippets.php)}</div>
            <div class="tt-tab-panel" data-panel="js">${codeBlock('js', snippets.js)}</div>
            <div class="tt-tab-panel" data-panel="py">${codeBlock('python', snippets.python)}</div>
            <div class="tt-tab-panel" data-panel="sql">${codeBlock('sql', snippets.sql)}</div>
        </div>`;

        // Init tabs for this section
        out.querySelectorAll('.tt-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                out.querySelectorAll('.tt-tab').forEach(t => t.classList.remove('active'));
                out.querySelectorAll('.tt-tab-panel').forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                const panel = out.querySelector(`[data-panel="${tab.dataset.tab}"]`);
                if (panel) panel.classList.add('active');
            });
        });
        out.querySelector('.tt-tab')?.click();
    },

    /* ═══ ACCORDION 12 — Cron Toolkit ═══ */
    renderCron(p) {
        const out = $('cron-out');
        if (!out) return;
        const cron = p.cron || '0 9 * * 1-5';

        out.innerHTML = `
        <div class="tt-section">
            <div class="tt-section-title">Cron Expression</div>
            <div class="tt-row">
                <input class="tt-input" id="cron-in" placeholder="e.g. 0 9 * * 1-5" value="${esc(cron)}" style="font-family:var(--font-mono)">
                <button class="tt-btn tt-btn-primary" onclick="TT.parseCron()">Analyze</button>
            </div>
            <div id="cron-res" class="tt-result" style="margin-top:10px"></div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Common Cron Patterns</div>
            <div class="tt-table-wrap">
                <table class="tt-table"><thead><tr><th>Expression</th><th>Meaning</th><th></th></tr></thead>
                <tbody>
                ${[
                    ['* * * * *',     'Every minute'],
                    ['*/5 * * * *',   'Every 5 minutes'],
                    ['0 * * * *',     'Every hour'],
                    ['0 0 * * *',     'Daily at midnight'],
                    ['0 9 * * *',     'Daily at 9:00 AM'],
                    ['0 9 * * 1-5',   'Weekdays at 9:00 AM'],
                    ['0 0 * * 0',     'Every Sunday at midnight'],
                    ['0 0 1 * *',     'First of every month'],
                    ['0 0 1 1 *',     'Every January 1st'],
                    ['*/15 * * * *',  'Every 15 minutes'],
                    ['0 12 * * *',    'Daily at noon'],
                    ['0 0,12 * * *',  'Twice daily (midnight & noon)'],
                ].map(([expr,desc]) => `<tr>
                    <td style="font-family:var(--font-mono)">${esc(expr)}</td>
                    <td>${esc(desc)}</td>
                    <td><button class="tt-btn tt-btn-sm" onclick="TT.setCron(${JSON.stringify(expr)})">Use</button></td>
                </tr>`).join('')}
                </tbody></table>
            </div>
        </div>`;

        this.parseCron(cron);
    },

    setCron(expr) {
        const inp = $('cron-in');
        if (inp) { inp.value = expr; this.parseCron(expr); }
    },

    parseCron(expr_override) {
        const expr = expr_override || ($('cron-in') || {}).value || '';
        const res = $('cron-res');
        if (!res || !expr.trim()) return;

        const parts = expr.trim().split(/\s+/);
        if (parts.length < 5) { res.innerHTML = '<div class="tt-result-error">Invalid cron expression (need 5 fields)</div>'; return; }

        const [min, hour, dom, month, dow] = parts;
        const fieldNames = ['Minute', 'Hour', 'Day of Month', 'Month', 'Day of Week'];
        const fieldVals  = [min, hour, dom, month, dow];

        const explain = cronExplain(min, hour, dom, month, dow);
        const nexts = getNextCronTimes(parts.slice(0,5), 10);

        res.innerHTML = `
        <div class="tt-result-inner" style="margin-bottom:10px">
            <div style="font-size:15px;font-weight:600;margin-bottom:8px">${esc(explain)}</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                ${fieldNames.map((n,i) => `<span class="tt-badge tt-badge-muted">${n}: <strong>${esc(fieldVals[i])}</strong></span>`).join('')}
            </div>
        </div>
        <div class="tt-val-label" style="margin-bottom:6px">Next 10 executions</div>
        <div class="tt-table-wrap">
            <table class="tt-table"><thead><tr><th>#</th><th>Date & Time</th><th>Relative</th></tr></thead>
            <tbody>${nexts.map((d,i) => `<tr><td>${i+1}</td><td style="font-family:var(--font-mono)">${d.toLocaleString()}</td><td>${relativeTime(d)}</td></tr>`).join('')}</tbody>
            </table>
        </div>`;
    },

    /* ═══ ACCORDION 13 — Export ═══ */
    renderExport(p) {
        const out = $('export-out');
        if (!out) return;
        if (!p.date && p.lat === null) { out.innerHTML = noDataHTML('Use Universal Input first'); return; }

        const d = p.date || new Date();
        const unix = p.unix || Math.floor(d.getTime()/1000);
        const data = buildExportData(d, unix, p);

        out.innerHTML = `
        <div class="tt-section">
            <div class="tt-section-title">Preview & Download</div>
            <div class="tt-tabs" style="margin-bottom:10px">
                ${['JSON','CSV','XML','YAML','TXT','Markdown'].map((f,i) => `<div class="tt-tab${i===0?' active':''}" onclick="TT.exportFormat('${f}', this)">${f}</div>`).join('')}
            </div>
            <div id="export-preview" class="tt-code-wrap">
                <button class="tt-code-copy" onclick="TT.copyExport(this)">${iconSVG('copy')} Copy</button>
                <pre class="tt-code" id="export-code" style="max-height:300px;overflow:auto"></pre>
            </div>
            <div class="tt-export-row" style="margin-top:10px">
                ${['JSON','CSV','XML','YAML','TXT','Markdown'].map(f => `<button class="tt-btn" onclick="TT.downloadExport('${f}')">${iconSVG('download')} ${f}</button>`).join('')}
            </div>
        </div>`;

        this._exportData = data;
        this.exportFormat('JSON');
    },

    exportFormat(fmt, tabEl) {
        if (tabEl) {
            tabEl.closest('.tt-tabs')?.querySelectorAll('.tt-tab').forEach(t => t.classList.remove('active'));
            tabEl.classList.add('active');
        }
        const el = $('export-code');
        if (!el || !this._exportData) return;
        el.textContent = formatExport(this._exportData, fmt);
    },

    copyExport(btn) {
        const el = $('export-code');
        if (el) copyText(el.textContent, btn);
    },

    downloadExport(fmt) {
        if (!this._exportData) return;
        const content = formatExport(this._exportData, fmt);
        const exts = { JSON: 'json', CSV: 'csv', XML: 'xml', YAML: 'yaml', TXT: 'txt', Markdown: 'md' };
        const blob = new Blob([content], { type: 'text/plain' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `time-toolkit.${exts[fmt] || 'txt'}`;
        a.click();
    },

    /* ═══ ACCORDION 14 — Productivity ═══ */
    renderProductivity() {
        // Always available, not date-dependent
    },

    calcPomodoro() {
        const tasks = parseInt(($('pomo-tasks')||{}).value||4);
        const workMin = parseInt(($('pomo-work')||{}).value||25);
        const shortBreak = parseInt(($('pomo-short')||{}).value||5);
        const longBreak = parseInt(($('pomo-long')||{}).value||15);
        const res = $('pomo-res');
        if (!res) return;
        const cycles = Math.ceil(tasks);
        const totalMin = cycles*workMin + Math.floor(cycles/4)*longBreak + (cycles - Math.floor(cycles/4))*shortBreak;
        const h = Math.floor(totalMin/60), m = totalMin%60;
        res.innerHTML = `<div class="tt-grid">
            ${valCell('Pomodoros', cycles, true)}
            ${valCell('Work Time', cycles*workMin + ' min', true)}
            ${valCell('Short Breaks', (cycles-Math.floor(cycles/4)), true)}
            ${valCell('Long Breaks', Math.floor(cycles/4), true)}
            ${valCell('Total Time', h+'h '+m+'m', true)}
        </div>`;
    },

    /* ═══ ACCORDION 15 — Religious ═══ */
    renderReligious(p) {
        const out = $('rel-out');
        if (!out) return;
        const d = p.date || new Date();

        const hijri = gregorianToHijri(d);
        const hijriMonths = ['Muharram','Safar','Rabi al-Awwal','Rabi al-Thani','Jumada al-Awwal','Jumada al-Thani','Rajab','Sha\'ban','Ramadan','Shawwal','Dhu al-Qi\'dah','Dhu al-Hijjah'];

        out.innerHTML = `
        <div class="tt-section">
            <div class="tt-section-title">Islamic / Hijri Calendar</div>
            <div class="tt-grid">
                ${valCell('Hijri Date', `${hijri.day} ${hijriMonths[hijri.month-1]} ${hijri.year} AH`)}
                ${valCell('Hijri Day', hijri.day, true)}
                ${valCell('Hijri Month', hijriMonths[hijri.month-1])}
                ${valCell('Hijri Year', hijri.year, true)}
                ${valCell('Ramadan', hijri.month === 9 ? 'Currently Ramadan 🌙' : 'Not Ramadan')}
            </div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Hijri Date Converter</div>
            <div class="tt-row">
                <input class="tt-input" id="hijri-in" type="date" value="${d.toISOString().slice(0,10)}">
                <button class="tt-btn tt-btn-primary" onclick="TT.convertHijri()">Convert</button>
            </div>
            <div id="hijri-res" class="tt-result" style="margin-top:10px"></div>
        </div>`;
    },

    convertHijri() {
        const val = ($('hijri-in')||{}).value;
        const res = $('hijri-res');
        if (!res) return;
        const d = val ? new Date(val+'T00:00:00') : new Date();
        const h = gregorianToHijri(d);
        const hijriMonths = ['Muharram','Safar','Rabi al-Awwal','Rabi al-Thani','Jumada al-Awwal','Jumada al-Thani','Rajab','Sha\'ban','Ramadan','Shawwal','Dhu al-Qi\'dah','Dhu al-Hijjah'];
        res.innerHTML = `<div class="tt-grid">
            ${valCell('Full Hijri', `${h.day} ${hijriMonths[h.month-1]} ${h.year} AH`)}
            ${valCell('Numeric', `${h.year}/${pad(h.month)}/${pad(h.day)}`, true)}
        </div>`;
    },

    /* ═══ ACCORDION 16 — Utilities ═══ */
    renderUtilities(p) {
        const out = $('util-out');
        if (!out) return;
        const d = p.date || new Date();

        out.innerHTML = `
        <div class="tt-section">
            <div class="tt-section-title">Quick Checks</div>
            <div class="tt-grid">
                ${valCell('Leap Year', isLeapYear(d.getFullYear()) ? `${d.getFullYear()} is a leap year ✓` : `${d.getFullYear()} is not a leap year`)}
                ${valCell('Day of Week', ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][d.getDay()])}
                ${valCell('Day of Year', getDayOfYear(d), true)}
                ${valCell('Days in Month', new Date(d.getFullYear(), d.getMonth()+1, 0).getDate(), true)}
            </div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Leap Year Checker</div>
            <div class="tt-row">
                <input class="tt-input" id="leap-yr" type="number" value="${d.getFullYear()}" style="max-width:120px">
                <button class="tt-btn tt-btn-primary" onclick="TT.checkLeap()">Check</button>
            </div>
            <div id="leap-res" class="tt-result" style="margin-top:10px"></div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Day of Week Finder</div>
            <div class="tt-row">
                <input class="tt-input" id="dow-date" type="date" value="${d.toISOString().slice(0,10)}">
                <button class="tt-btn tt-btn-primary" onclick="TT.findDow()">Find</button>
            </div>
            <div id="dow-res" class="tt-result" style="margin-top:10px"></div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Random Date Generator</div>
            <div class="tt-row">
                <input class="tt-input" id="rnd-from" type="date" value="1990-01-01">
                <span class="tt-label">to</span>
                <input class="tt-input" id="rnd-to" type="date" value="${new Date().toISOString().slice(0,10)}">
                <button class="tt-btn tt-btn-primary" onclick="TT.randomDate()">Generate</button>
            </div>
            <div id="rnd-res" class="tt-result" style="margin-top:10px"></div>
        </div>
        <div class="tt-section">
            <div class="tt-section-title">Date Validator</div>
            <div class="tt-row">
                <input class="tt-input" id="val-date-in" placeholder="Enter any date string">
                <button class="tt-btn tt-btn-primary" onclick="TT.validateDate()">Validate</button>
            </div>
            <div id="val-date-res" class="tt-result" style="margin-top:10px"></div>
        </div>`;

        this.checkLeap(d.getFullYear());
        this.findDow(d);
    },

    checkLeap(yr_override) {
        const yr = yr_override || parseInt(($('leap-yr')||{}).value || new Date().getFullYear());
        const res = $('leap-res');
        if (!res) return;
        const leap = isLeapYear(yr);
        res.innerHTML = `<div class="${leap ? 'tt-result-success' : 'tt-result-inner'}">${yr} is ${leap ? '' : 'not '}a leap year${leap ? ' ✓' : ''}</div>`;
    },

    findDow(d_override) {
        const val = ($('dow-date')||{}).value;
        const res = $('dow-res');
        if (!res) return;
        const d = d_override instanceof Date ? d_override : (val ? new Date(val+'T00:00:00') : new Date());
        if (isNaN(d)) { res.innerHTML = '<div class="tt-result-error">Invalid date</div>'; return; }
        const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        const day = days[d.getDay()];
        const isWe = d.getDay() === 0 || d.getDay() === 6;
        res.innerHTML = `<div class="tt-result-inner" style="display:flex;align-items:center;gap:10px">
            <span style="font-size:22px;font-weight:700">${esc(day)}</span>
            <span class="tt-badge ${isWe ? 'tt-badge-warning' : 'tt-badge-primary'}">${isWe ? 'Weekend' : 'Weekday'}</span>
        </div>`;
    },

    randomDate() {
        const fromVal = ($('rnd-from')||{}).value || '1990-01-01';
        const toVal   = ($('rnd-to')||{}).value   || new Date().toISOString().slice(0,10);
        const res = $('rnd-res');
        if (!res) return;
        const a = new Date(fromVal+'T00:00:00'), b = new Date(toVal+'T00:00:00');
        if (isNaN(a)||isNaN(b)||a>b) { res.innerHTML = '<div class="tt-result-error">Invalid range</div>'; return; }
        const rnd = new Date(a.getTime() + Math.random() * (b-a));
        res.innerHTML = `<div class="tt-grid">
            ${valCell('Random Date', rnd.toLocaleDateString(undefined,{weekday:'long',year:'numeric',month:'long',day:'numeric'}))}
            ${valCell('ISO', rnd.toISOString().slice(0,10), true)}
            ${valCell('Unix', Math.floor(rnd.getTime()/1000), true)}
        </div>`;
    },

    validateDate() {
        const val = ($('val-date-in')||{}).value || '';
        const res = $('val-date-res');
        if (!res || !val.trim()) return;
        const d = new Date(val);
        const valid = !isNaN(d) && val.trim() !== '';
        if (valid) {
            res.innerHTML = `<div class="tt-result-success">Valid date! → ${d.toISOString()}</div>`;
        } else {
            res.innerHTML = '<div class="tt-result-error">Invalid date string</div>';
        }
    },
};

/* ═══════════════════════════════════════════════════════════════
   CALCULATION HELPERS
═══════════════════════════════════════════════════════════════ */

function isLeapYear(y) { return (y%4===0 && y%100!==0) || y%400===0; }

/* ── Calendar conversion helpers ── */

function hijriToGregorian(hy, hm, hd) {
    const jdn = hd + Math.ceil(29.5001*(hm-1)) + (hy-1)*354
        + Math.floor((3+11*hy)/30) + 1948440 - 385;
    let l=jdn+68569, n=Math.floor(4*l/146097);
    l=l-Math.floor((146097*n+3)/4);
    let i=Math.floor(4000*(l+1)/1461001);
    l=l-Math.floor(1461*i/4)+31;
    let j=Math.floor(80*l/2447);
    const day=l-Math.floor(2447*j/80); l=Math.floor(j/11);
    const month=j+2-12*l; const year=100*(n-49)+i+l;
    return new Date(year, month-1, day);
}

function gregorianToJalali(date) {
    const gDim=[31,28,31,30,31,30,31,31,30,31,30,31];
    const jDim=[31,31,31,31,31,31,30,30,30,30,30,29];
    let gy=date.getFullYear()-1600, gm=date.getMonth(), gd=date.getDate()-1;
    let gdn=365*gy+Math.floor((gy+3)/4)-Math.floor((gy+99)/100)+Math.floor((gy+399)/400);
    for (let i=0;i<gm;i++) gdn+=gDim[i];
    if (gm>1&&((gy%4===0&&gy%100!==0)||gy%400===0)) gdn++;
    gdn+=gd;
    let jdn=gdn-79, jnp=Math.floor(jdn/12053); jdn=jdn%12053;
    let jy=979+33*jnp+4*Math.floor(jdn/1461); jdn=jdn%1461;
    let jm;
    if (jdn>=366) { jy+=Math.floor((jdn-1)/365); jdn=(jdn-1)%365; }
    for (jm=0;jm<11&&jdn>=jDim[jm];jm++) jdn-=jDim[jm];
    return {year:jy, month:jm+1, day:jdn+1};
}

function jalaliToGregorian(jy, jm, jd) {
    jy+=1595;
    let days=-355779+365*jy+Math.floor(jy/33)*8+Math.floor(((jy%33)+3)/4)+jd;
    if (jm<=6) days+=(jm-1)*31; else days+=((jm-7)*30)+186;
    let gy=400*Math.floor(days/146097); days=days%146097;
    if (days>36524) { gy+=100*Math.floor(--days/36524); days=days%36524; if (days>=365) days++; }
    gy+=4*Math.floor(days/1461); days=days%1461;
    if (days>364) { gy+=Math.floor((days-1)/365); days=(days-1)%365; }
    const sa=[0,31,59,90,120,151,181,212,243,273,304,334];
    let gm; for (gm=0;gm<11&&days>=sa[gm+1];gm++);
    return new Date(gy, gm, days-sa[gm]+1);
}

function gregorianToJulianCal(date) {
    const y=date.getFullYear(), m=date.getMonth()+1, d=date.getDate();
    // Gregorian → JDN
    const a=Math.floor((14-m)/12), y2=y+4800-a, m2=m+12*a-3;
    const jdn=d+Math.floor((153*m2+2)/5)+365*y2+Math.floor(y2/4)-Math.floor(y2/100)+Math.floor(y2/400)-32045;
    // JDN → Julian proleptic calendar (correct formula)
    const a2=jdn+32082;
    const d2=Math.floor((4*a2+3)/1461);
    const e=a2-Math.floor(1461*d2/4);
    const jm=Math.floor((5*e+2)/153);
    const jD=e-Math.floor((153*jm+2)/5)+1;
    const jMo=jm+3-12*Math.floor(jm/10);
    const jY=d2-4800+Math.floor(jm/10);
    return {year:jY, month:jMo, day:jD};
}

function julianCalToGregorian(jy, jm, jd) {
    const a=Math.floor((14-jm)/12), y=jy+4800-a, m=jm+12*a-3;
    const jdn=jd+Math.floor((153*m+2)/5)+365*y+Math.floor(y/4)-32083;
    let l=jdn+68569, n=Math.floor(4*l/146097);
    l=l-Math.floor((146097*n+3)/4);
    let i=Math.floor(4000*(l+1)/1461001);
    l=l-Math.floor(1461*i/4)+31;
    let jj=Math.floor(80*l/2447);
    const day=l-Math.floor(2447*jj/80); l=Math.floor(jj/11);
    const month=jj+2-12*l; const year=100*(n-49)+i+l;
    return new Date(year, month-1, day);
}

function gregorianToHebrew(date) {
    const y=date.getFullYear(), m=date.getMonth()+1, d=date.getDate();
    const hy=(m>=9)?y+3761:y+3760;
    const mmap=[0,5,6,7,8,9,10,11,12,1,2,3,4];
    const hm=mmap[m];
    return {year:hy, month:hm, day:d};
}

function getChineseZodiac(year) {
    const signs=['Rat','Ox','Tiger','Rabbit','Dragon','Snake','Horse','Goat','Monkey','Rooster','Dog','Pig'];
    const elems=['Wood','Fire','Earth','Metal','Water'];
    const sign=signs[(year-4)%12]; const elem=elems[Math.floor(((year-4)%10)/2)];
    return `${elem} ${sign}`;
}

function latToDMS(deg) {
    const d = Math.floor(Math.abs(deg)), m = Math.floor((Math.abs(deg)-d)*60);
    const s = ((Math.abs(deg)-d)*3600 - m*60).toFixed(1);
    return `${d}°${pad(m)}'${pad(s)}" ${deg>=0?'N':'S'}`;
}
function lngToDMS(deg) {
    const d = Math.floor(Math.abs(deg)), m = Math.floor((Math.abs(deg)-d)*60);
    const s = ((Math.abs(deg)-d)*3600 - m*60).toFixed(1);
    return `${d}°${pad(m)}'${pad(s)}" ${deg>=0?'E':'W'}`;
}

function getDayOfYear(d) {
    const start = new Date(d.getFullYear(), 0, 0);
    const diff = d - start;
    return Math.floor(diff / 86400000);
}

function getWeekNumber(d) {
    const date = new Date(d);
    date.setHours(0,0,0,0);
    date.setDate(date.getDate() + 3 - (date.getDay()+6)%7);
    const week1 = new Date(date.getFullYear(), 0, 4);
    return 1 + Math.round(((date-week1)/86400000 - 3 + (week1.getDay()+6)%7) / 7);
}

function getSeason(d) {
    const m = d.getMonth()+1, day = d.getDate();
    if ((m===3&&day>=20)||(m===4)||(m===5)||(m===6&&day<21)) return '🌸 Spring';
    if ((m===6&&day>=21)||(m===7)||(m===8)||(m===9&&day<23)) return '☀️ Summer';
    if ((m===9&&day>=23)||(m===10)||(m===11)||(m===12&&day<22)) return '🍂 Autumn';
    return '❄️ Winter';
}

function getUTCOffset(d) {
    const off = -d.getTimezoneOffset();
    const sign = off >= 0 ? '+' : '-';
    const abs = Math.abs(off);
    return `UTC${sign}${pad(Math.floor(abs/60))}:${pad(abs%60)}`;
}

function getTzAbbr(d) {
    try {
        return d.toLocaleTimeString('en-US', { timeZoneName: 'short' }).split(' ').pop();
    } catch { return 'local'; }
}

function relativeTime(d) {
    const diff = Date.now() - d.getTime();
    const abs  = Math.abs(diff);
    const future = diff < 0;
    const suf = future ? 'from now' : 'ago';
    if (abs < 60000) return 'just now';
    if (abs < 3600000) return `${Math.floor(abs/60000)} minute${Math.floor(abs/60000)!==1?'s':''} ${suf}`;
    if (abs < 86400000) return `${Math.floor(abs/3600000)} hour${Math.floor(abs/3600000)!==1?'s':''} ${suf}`;
    if (abs < 2592000000) return `${Math.floor(abs/86400000)} day${Math.floor(abs/86400000)!==1?'s':''} ${suf}`;
    if (abs < 31536000000) return `${Math.floor(abs/2592000000)} month${Math.floor(abs/2592000000)!==1?'s':''} ${suf}`;
    return `${Math.floor(abs/31536000000)} year${Math.floor(abs/31536000000)!==1?'s':''} ${suf}`;
}

function formatDuration(totalSec) {
    const s = Math.floor(totalSec);
    if (s < 60) return s + ' seconds';
    if (s < 3600) return `${Math.floor(s/60)}m ${s%60}s`;
    if (s < 86400) return `${Math.floor(s/3600)}h ${Math.floor((s%3600)/60)}m`;
    return `${Math.floor(s/86400)}d ${Math.floor((s%86400)/3600)}h`;
}

function getOrdinalSuffix(n) {
    if (n%100>=11 && n%100<=13) return 'th';
    switch(n%10) { case 1: return 'st'; case 2: return 'nd'; case 3: return 'rd'; default: return 'th'; }
}

function monthsDiff(a, b) {
    return (b.getFullYear() - a.getFullYear()) * 12 + (b.getMonth() - a.getMonth());
}

function addToDate(d, amount, unit) {
    const result = new Date(d);
    switch(unit) {
        case 'days':    result.setDate(result.getDate() + amount); break;
        case 'weeks':   result.setDate(result.getDate() + amount * 7); break;
        case 'months':  result.setMonth(result.getMonth() + amount); break;
        case 'years':   result.setFullYear(result.getFullYear() + amount); break;
        case 'hours':   result.setHours(result.getHours() + amount); break;
        case 'minutes': result.setMinutes(result.getMinutes() + amount); break;
    }
    return result;
}

function addBusinessDays(d, n) {
    const result = new Date(d);
    let count = 0;
    const step = n > 0 ? 1 : -1;
    while (Math.abs(count) < Math.abs(n)) {
        result.setDate(result.getDate() + step);
        if (result.getDay() !== 0 && result.getDay() !== 6) count += step;
    }
    return result;
}

function countBusinessDays(a, b) {
    let count = 0;
    const cur = new Date(a);
    cur.setHours(0,0,0,0);
    const end = new Date(b);
    end.setHours(0,0,0,0);
    while (cur <= end) {
        const day = cur.getDay();
        if (day !== 0 && day !== 6) count++;
        cur.setDate(cur.getDate() + 1);
    }
    return count;
}

function nextWeekday(d, targetDay) {
    const result = new Date(d);
    result.setDate(result.getDate() + 1);
    while (result.getDay() !== targetDay) result.setDate(result.getDate() + 1);
    return result;
}

function toLocalDatetimeStr(d) {
    const Y = d.getFullYear(), M = pad(d.getMonth()+1), D = pad(d.getDate());
    const h = pad(d.getHours()), mi = pad(d.getMinutes());
    return `${Y}-${M}-${D}T${h}:${mi}`;
}

function getTimezoneOffset(tz, date) {
    try {
        const utc = new Date(date.toLocaleString('en-US', { timeZone: 'UTC' }));
        const local = new Date(date.toLocaleString('en-US', { timeZone: tz }));
        return (local - utc) / 60000;
    } catch { return 0; }
}

function isDST(tz, date) {
    try {
        const jan = getTimezoneOffset(tz, new Date(date.getFullYear(), 0, 1));
        const jul = getTimezoneOffset(tz, new Date(date.getFullYear(), 6, 1));
        const curr = getTimezoneOffset(tz, date);
        return curr > Math.min(jan, jul);
    } catch { return false; }
}

/* ── Calendar HTML builder ── */
function buildCalendarHTML(monthDate, selectedDate) {
    const y = monthDate.getFullYear(), m = monthDate.getMonth();
    const today = new Date();
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    const firstDay = new Date(y, m, 1).getDay();
    const daysInMonth = new Date(y, m+1, 0).getDate();
    const daysInPrev = new Date(y, m, 0).getDate();

    let html = `<div class="tt-cal-head">
        <span class="tt-cal-nav" onclick="TT.calNav(-1)">‹</span>
        <span>${months[m]} ${y}</span>
        <span class="tt-cal-nav" onclick="TT.calNav(1)">›</span>
    </div>
    <div class="tt-cal-grid">
        ${['Su','Mo','Tu','We','Th','Fr','Sa'].map(d => `<div class="tt-cal-dow">${d}</div>`).join('')}`;

    // Prev month days
    for (let i = firstDay-1; i >= 0; i--) {
        html += `<div class="tt-cal-day other-month">${daysInPrev - i}</div>`;
    }

    // Current month days
    for (let d = 1; d <= daysInMonth; d++) {
        const thisDate = new Date(y, m, d);
        const isToday = today.getFullYear()===y && today.getMonth()===m && today.getDate()===d;
        const isSel = selectedDate && selectedDate.getFullYear()===y && selectedDate.getMonth()===m && selectedDate.getDate()===d;
        const isWe = thisDate.getDay()===0 || thisDate.getDay()===6;
        const cls = ['tt-cal-day', isToday?'today':'', isSel&&!isToday?'selected':'', isWe&&!isToday?'weekend':''].filter(Boolean).join(' ');
        html += `<div class="${cls}">${d}</div>`;
    }

    // Next month days
    const total = firstDay + daysInMonth;
    const remaining = total % 7 === 0 ? 0 : 7 - (total % 7);
    for (let d = 1; d <= remaining; d++) {
        html += `<div class="tt-cal-day other-month">${d}</div>`;
    }

    html += '</div>';
    return html;
}

/* ── Sun times (NOAA algorithm) ── */
function calcSunTimes(lat, lng, date) {
    const deg2rad = Math.PI / 180;
    const rad2deg = 180 / Math.PI;

    const JD = (date) => {
        const y = date.getUTCFullYear(), m = date.getUTCMonth()+1, d = date.getUTCDate();
        return 367*y - Math.floor(7*(y+Math.floor((m+9)/12))/4) + Math.floor(275*m/9) + d + 1721013.5;
    };

    function calcTime(date, lat, lng, type) {
        const jd = JD(date);
        const n = jd - 2451545.0;
        const L = (280.460 + 0.9856474 * n) % 360;
        const g = (357.528 + 0.9856003 * n) % 360;
        const lambda = L + 1.915 * Math.sin(g * deg2rad) + 0.020 * Math.sin(2 * g * deg2rad);
        const eps = 23.439 - 0.0000004 * n;
        const sinDec = Math.sin(eps * deg2rad) * Math.sin(lambda * deg2rad);
        const cosDec = Math.sqrt(1 - sinDec*sinDec);
        const RA = (Math.atan2(Math.cos(eps*deg2rad)*Math.sin(lambda*deg2rad), Math.cos(lambda*deg2rad)) * rad2deg + 360) % 360;
        const EqT = (L - RA) * 4;
        const solarNoonTime = 12 - lng/15 - EqT/60;

        const zenith = { rise: 90.833, civil: 96, nautical: 102, astro: 108, golden_am: 84, golden_pm: 84 };
        const z = zenith[type] || 90.833;

        const cosH = (Math.cos(z * deg2rad) - sinDec * Math.sin(lat * deg2rad)) / (cosDec * Math.cos(lat * deg2rad));
        if (cosH < -1) return { time: null, noon: solarNoonTime }; // sun always up
        if (cosH > 1)  return { time: null, noon: solarNoonTime }; // sun always down
        const H = Math.acos(cosH) * rad2deg;
        return {
            rise: solarNoonTime - H/15,
            set:  solarNoonTime + H/15,
            noon: solarNoonTime,
        };
    }

    function toTimeStr(fracHours) {
        if (fracHours == null || isNaN(fracHours)) return 'N/A';
        const totalMin = Math.round(((fracHours % 24) + 24) % 24 * 60);
        const h = Math.floor(totalMin / 60);
        const m = totalMin % 60;
        return `${pad(h)}:${pad(m)}`;
    }

    const main = calcTime(date, lat, lng, 'rise');
    const civil = calcTime(date, lat, lng, 'civil');
    const nautical = calcTime(date, lat, lng, 'nautical');
    const astro = calcTime(date, lat, lng, 'astro');
    const golden = calcTime(date, lat, lng, 'golden_am');

    const dayMins = main.rise != null && main.set != null ? Math.round((main.set - main.rise) * 60) : null;
    const dayLenStr = dayMins != null ? `${Math.floor(dayMins/60)}h ${dayMins%60}m` : 'N/A';

    return {
        sunrise: toTimeStr(main.rise),
        sunset: toTimeStr(main.set),
        solarNoon: toTimeStr(main.noon),
        dayLength: dayLenStr,
        goldenHourAM: toTimeStr(golden.rise != null ? golden.rise - 1 : null) + ' – ' + toTimeStr(main.rise),
        goldenHourPM: toTimeStr(main.set) + ' – ' + toTimeStr(main.set != null ? main.set + 1 : null),
        civilTwilight: toTimeStr(civil.rise) + ' / ' + toTimeStr(civil.set),
        nauticalTwilight: toTimeStr(nautical.rise) + ' / ' + toTimeStr(nautical.set),
        astronomicalTwilight: toTimeStr(astro.rise) + ' / ' + toTimeStr(astro.set),
    };
}

/* ── Moon phase ── */
function calcMoonPhase(date) {
    const knownNewMoon = new Date('2000-01-06T18:14:00Z');
    const synodicMonth = 29.530588853;
    const daysSince = (date - knownNewMoon) / 86400000;
    const cycles = daysSince / synodicMonth;
    const age = (cycles - Math.floor(cycles)) * synodicMonth;
    const illumination = Math.round((1 - Math.cos(age / synodicMonth * 2 * Math.PI)) / 2 * 100);

    const phases = [
        [0, 1.85, '🌑 New Moon'],
        [1.85, 7.38, '🌒 Waxing Crescent'],
        [7.38, 11.08, '🌓 First Quarter'],
        [11.08, 14.77, '🌔 Waxing Gibbous'],
        [14.77, 16.61, '🌕 Full Moon'],
        [16.61, 22.15, '🌖 Waning Gibbous'],
        [22.15, 25.84, '🌗 Last Quarter'],
        [25.84, 29.53, '🌘 Waning Crescent'],
    ];
    const phase = phases.find(([s,e]) => age >= s && age < e);
    const phaseName = phase ? phase[2] : '🌑 New Moon';

    // Next full and new moon
    const remainToFull = (14.77 - age % synodicMonth + synodicMonth) % synodicMonth;
    const remainToNew  = (synodicMonth - age % synodicMonth) % synodicMonth;
    const nextFull = new Date(date.getTime() + remainToFull * 86400000);
    const nextNew  = new Date(date.getTime() + (remainToNew || synodicMonth) * 86400000);

    return {
        age, illumination, phaseName,
        nextFull: nextFull.toLocaleDateString(),
        nextNew: nextNew.toLocaleDateString(),
    };
}

/* ── Cron helpers ── */
function cronExplain(min, hour, dom, month, dow) {
    const allMin = min === '*', allHour = hour === '*', allDom = dom === '*', allMonth = month === '*', allDow = dow === '*';
    const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const dayNames   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    if (min==='*'&&hour==='*'&&dom==='*'&&month==='*'&&dow==='*') return 'Every minute';
    if (min==='0'&&hour==='*') return 'At the start of every hour';
    if (min==='0'&&hour==='0') return allDom&&allMonth&&allDow ? 'Every day at midnight' : 'At midnight';
    if (min==='0'&&hour==='12') return 'Every day at noon';

    let parts = [];
    if (min !== '*') {
        if (min.startsWith('*/')) parts.push(`every ${min.slice(2)} minutes`);
        else parts.push(`at minute ${min}`);
    }
    if (hour !== '*') {
        if (hour.startsWith('*/')) parts.push(`every ${hour.slice(2)} hours`);
        else {
            const hrs = hour.split(',').map(h => {
                const n = parseInt(h); const ampm = n >= 12 ? 'PM' : 'AM';
                return (n % 12 || 12) + ' ' + ampm;
            });
            parts.push(`at ${hrs.join(' and ')}`);
        }
    }
    if (dow !== '*') {
        const days = dow.split(/[-,]/).map(d => isNaN(d) ? d : (dayNames[parseInt(d)] || d));
        parts.push(`on ${days.join('/')}`);
    }
    if (dom !== '*') parts.push(`on day ${dom} of the month`);
    if (month !== '*') {
        const mths = month.split(',').map(m => isNaN(m) ? m : (monthNames[parseInt(m)-1] || m));
        parts.push(`in ${mths.join(', ')}`);
    }
    return parts.join(', ').replace(/^./, s => s.toUpperCase()) || 'Custom schedule';
}

function getNextCronTimes(parts, count) {
    const [minF, hourF, domF, monthF, dowF] = parts;
    const results = [];
    const now = new Date();
    let cur = new Date(now);
    cur.setSeconds(0, 0);
    cur.setMinutes(cur.getMinutes() + 1);

    const match = (val, field) => {
        if (field === '*') return true;
        const vals = field.split(',').flatMap(p => {
            if (p.includes('/')) {
                const [range, step] = p.split('/');
                const [start, end] = range === '*' ? [0, 59] : range.split('-').map(Number);
                const out = [];
                for (let i = start; i <= (end||59); i += parseInt(step)) out.push(i);
                return out;
            }
            if (p.includes('-')) {
                const [s,e] = p.split('-').map(Number);
                const out = [];
                for (let i=s; i<=e; i++) out.push(i);
                return out;
            }
            return [parseInt(p)];
        });
        return vals.includes(val);
    };

    let attempts = 0;
    while (results.length < count && attempts < 100000) {
        attempts++;
        const valid = match(cur.getMinutes(), minF) &&
                      match(cur.getHours(), hourF) &&
                      match(cur.getDate(), domF) &&
                      match(cur.getMonth()+1, monthF) &&
                      match(cur.getDay(), dowF);
        if (valid) results.push(new Date(cur));
        cur.setMinutes(cur.getMinutes() + 1);
    }
    return results;
}

/* ── Gregorian → Hijri ── */
function gregorianToHijri(date) {
    const JD = Math.floor((date.getTime() / 86400000) + 2440587.5);
    let l = JD - 1948440 + 10632;
    const n = Math.floor((l-1)/10631);
    l = l - 10631*n + 354;
    const J = Math.floor((10985-l)/5316)*Math.floor((50*l)/17719) + Math.floor(l/5670)*Math.floor((43*l)/15238);
    l = l - Math.floor((30-J)/15)*Math.floor((17719*J)/50) - Math.floor(J/16)*Math.floor((15238*J)/43) + 29;
    const month = Math.floor((24*l)/709);
    const day   = l - Math.floor((709*month)/24);
    const year  = 30*n + J - 30;
    return { year, month, day };
}

/* ── Export helpers ── */
function buildExportData(d, unix, p) {
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    return {
        unix, ms: d.getTime(), iso: d.toISOString(),
        utc: d.toUTCString(), rfc2822: d.toUTCString(),
        local: d.toLocaleString(),
        year: d.getFullYear(), month: d.getMonth()+1,
        monthName: months[d.getMonth()],
        day: d.getDate(), hour: d.getHours(),
        minute: d.getMinutes(), second: d.getSeconds(),
        weekday: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][d.getDay()],
        weekNumber: getWeekNumber(d),
        dayOfYear: getDayOfYear(d),
        leapYear: isLeapYear(d.getFullYear()),
        quarter: 'Q' + Math.ceil((d.getMonth()+1)/3),
        utcOffset: getUTCOffset(d),
        lat: p.lat, lng: p.lng,
    };
}

function formatExport(data, fmt) {
    switch(fmt) {
        case 'JSON': return JSON.stringify(data, null, 2);
        case 'CSV': {
            const keys = Object.keys(data);
            return keys.join(',') + '\n' + keys.map(k => JSON.stringify(data[k] ?? '')).join(',');
        }
        case 'XML': {
            const inner = Object.entries(data).map(([k,v]) => `  <${k}>${esc(String(v??''))}</${k}>`).join('\n');
            return `<?xml version="1.0" encoding="UTF-8"?>\n<datetime>\n${inner}\n</datetime>`;
        }
        case 'YAML': {
            return Object.entries(data).map(([k,v]) => `${k}: ${JSON.stringify(v??'')}`).join('\n');
        }
        case 'TXT': {
            return Object.entries(data).map(([k,v]) => `${k.padEnd(15)}: ${v??''}`).join('\n');
        }
        case 'Markdown': {
            const rows = Object.entries(data).map(([k,v]) => `| ${k} | \`${String(v??'')}\` |`).join('\n');
            return `# Date & Time Export\n\n| Field | Value |\n|-------|-------|\n${rows}`;
        }
        default: return JSON.stringify(data, null, 2);
    }
}

function noDataHTML(msg) {
    return `<div class="tt-nodata">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        ${esc(msg)}
    </div>`;
}

/* ── Boot ── */
document.addEventListener('DOMContentLoaded', () => TT.init());

})();
