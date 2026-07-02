<?php
defined('AWAN') or require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';
require_once AWAN_ROOT . '/_core/Plugin.php';

$slug = 'time-toolkit';

/* ── Accordion definition ── */
$accordions = [
    ['id' => 'basic',   'icon' => 'info',      'title' => 'Basic Information',    'desc' => 'Date, weekday, quarter, season, leap year & more',   'count' => '18 fields'],
    ['id' => 'ts',      'icon' => 'hash',       'title' => 'Timestamp Toolkit',    'desc' => 'Unix, ms, epoch conversions & batch converter',       'count' => '12 tools'],
    ['id' => 'fmt',     'icon' => 'code',       'title' => 'Date Format Toolkit',  'desc' => '20+ formats with PHP, JS, Python & SQL snippets',     'count' => '22 formats'],
    ['id' => 'cal',     'icon' => 'calendar',   'title' => 'Calendar Toolkit',     'desc' => 'Monthly calendar, week numbers, Hijri view',          'count' => '5 views'],
    ['id' => 'tz',      'icon' => 'globe',      'title' => 'Timezone Toolkit',     'desc' => 'World clock, converter, DST detector, meeting planner','count' => '16 zones'],
    ['id' => 'arith',   'icon' => 'plus',       'title' => 'Date Arithmetic',      'desc' => 'Add/subtract days, weeks, months, business days',     'count' => '12 ops'],
    ['id' => 'diff',    'icon' => 'minus',      'title' => 'Date Difference',      'desc' => 'Years, months, weeks, days, hours, business days',    'count' => '10 units'],
    ['id' => 'age',     'icon' => 'users',      'title' => 'Age Toolkit',          'desc' => 'Age, countdown, heartbeats, next birthday',           'count' => '12 stats'],
    ['id' => 'astro',   'icon' => 'sun',        'title' => 'Astronomy Toolkit',    'desc' => 'Sunrise, sunset, moon phase, twilights, solar noon',  'count' => '14 values'],
    ['id' => 'biz',     'icon' => 'briefcase',  'title' => 'Business Toolkit',     'desc' => 'Working days, shift calculator, timesheet',           'count' => '8 tools'],
    ['id' => 'dev',     'icon' => 'terminal',   'title' => 'Developer Toolkit',    'desc' => 'Code snippets for PHP, JS, Python, MySQL, PostgreSQL','count' => '4 languages'],
    ['id' => 'cron',    'icon' => 'repeat',     'title' => 'Cron Toolkit',         'desc' => 'Validate, explain & preview next 10 executions',      'count' => '12 patterns'],
    ['id' => 'export',  'icon' => 'download',   'title' => 'Export Toolkit',       'desc' => 'Export as JSON, CSV, XML, YAML, TXT, Markdown',       'count' => '6 formats'],
    ['id' => 'pomo',    'icon' => 'sparkles',   'title' => 'Productivity Toolkit', 'desc' => 'Pomodoro planner, focus sessions, time blocking',     'count' => '4 tools'],
    ['id' => 'rel',     'icon' => 'leaf',       'title' => 'Religious Toolkit',    'desc' => 'Hijri calendar conversion, Ramadan detection',        'count' => '6 tools'],
    ['id' => 'util',    'icon' => 'tool',       'title' => 'Utility Toolkit',      'desc' => 'Leap year checker, day finder, random date, validator','count' => '6 tools'],
];

/* ── SVG icon function ── */
function tt_icon(string $key): string {
    $icons = [
        'info'      => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'hash'      => '<line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/>',
        'code'      => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        'calendar'  => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'globe'     => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'plus'      => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'minus'     => '<line x1="5" y1="12" x2="19" y2="12"/>',
        'users'     => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'sun'       => '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>',
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'terminal'  => '<polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>',
        'repeat'    => '<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
        'download'  => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'sparkles'  => '<path d="M12 3l1.5 5H18l-4 3 1.5 5L12 13l-3.5 3 1.5-5-4-3h4.5L12 3z"/>',
        'leaf'      => '<path d="M2 22c5.333-5 10.667-5 16 0M2 22V12a10 10 0 0 1 20 0v10"/>',
        'tool'      => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'refresh'   => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
        'link'      => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
    ];
    $d = $icons[$key] ?? '<circle cx="12" cy="12" r="10"/>';
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
}

ob_start();
?>
<link rel="stylesheet" href="/plugins/<?= $slug ?>/assets/time-toolkit.css">

<div class="tt-wrap">

    <!-- ── Search Bar ── -->
    <div class="tt-search-bar">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="tt-search" placeholder="Search tools… e.g. timestamp, calendar, moon phase" autocomplete="off">
    </div>

    <!-- ── Universal Input Workbench ── -->
    <div class="tt-universal">
        <div class="tt-universal-header">
            <h2>⚡ Universal Input Workbench</h2>
            <p>Enter any date, time, timestamp, timezone, coordinates, or cron expression — everything updates automatically</p>
        </div>
        <div class="tt-universal-body">
            <div class="tt-uni-row">
                <input type="text" id="tt-uni-input" class="tt-uni-input" placeholder="Try: 2002-01-01 · 1719838273 · Asia/Karachi · 31.5204,74.3587 · 0 9 * * 1-5 · 2025-01-01T14:30:00Z"
                    autocomplete="off" onkeydown="if(event.key==='Enter') TT.analyze()">
                <button class="tt-uni-btn" onclick="TT.analyze()">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Analyze
                </button>
                <button class="tt-uni-clear" onclick="TT.clear()" title="Clear">✕</button>
            </div>
            <div id="tt-detected" class="tt-detected"></div>
            <div class="tt-uni-hint">
                <span style="color:var(--color-text-subtle)">Try:</span>
                <span class="tt-hint-chip" data-val="2002-01-01">2002-01-01</span>
                <span class="tt-hint-chip" data-val="1719838273">1719838273</span>
                <span class="tt-hint-chip" data-val="1719838273000">1719838273000</span>
                <span class="tt-hint-chip" data-val="2025-06-15T14:30:00Z">ISO 8601</span>
                <span class="tt-hint-chip" data-val="Asia/Karachi">Asia/Karachi</span>
                <span class="tt-hint-chip" data-val="31.5204,74.3587">Coordinates</span>
                <span class="tt-hint-chip" data-val="0 9 * * 1-5">Cron</span>
            </div>
            <!-- Live clock -->
            <div class="tt-live-clock" style="margin-top:16px">
                <div class="tt-clock-item">
                    <div class="tt-clock-label">Local Time</div>
                    <div class="tt-clock-val" id="lc-time">--:--:--</div>
                    <div class="tt-clock-sub" id="lc-date">Loading…</div>
                </div>
                <div class="tt-clock-item">
                    <div class="tt-clock-label">UTC Time</div>
                    <div class="tt-clock-val" id="lc-utc">--:--:--</div>
                </div>
                <div class="tt-clock-item">
                    <div class="tt-clock-label">Unix Timestamp</div>
                    <div class="tt-clock-val" id="lc-unix" style="font-size:15px">----------</div>
                </div>
            </div>

            <!-- Today at a Glance -->
            <div class="tt-today-panel" id="tt-today-panel">
                <div class="tt-today-item">
                    <div class="tt-today-label">📅 Gregorian</div>
                    <div class="tt-today-val" id="tp-greg">—</div>
                </div>
                <div class="tt-today-item">
                    <div class="tt-today-label">☪️ Hijri</div>
                    <div class="tt-today-val" id="tp-hijri">—</div>
                </div>
                <div class="tt-today-item">
                    <div class="tt-today-label">🌸 Persian</div>
                    <div class="tt-today-val" id="tp-persian">—</div>
                </div>
                <div class="tt-today-item">
                    <div class="tt-today-label">🌙 Moon</div>
                    <div class="tt-today-val" id="tp-moon">—</div>
                </div>
                <div class="tt-today-item">
                    <div class="tt-today-label">🌐 Timezone</div>
                    <div class="tt-today-val" id="tp-tz">—</div>
                </div>
                <div class="tt-today-item">
                    <div class="tt-today-label">📆 Week / Q</div>
                    <div class="tt-today-val" id="tp-wq">—</div>
                </div>
                <div class="tt-today-item">
                    <div class="tt-today-label">🗓️ Day of Year</div>
                    <div class="tt-today-val" id="tp-doy">—</div>
                </div>
                <div class="tt-today-item">
                    <div class="tt-today-label">🐲 Zodiac</div>
                    <div class="tt-today-val" id="tp-zodiac">—</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Results Dashboard (hidden until analysis) ── -->
    <div class="tt-dashboard" id="tt-dashboard" style="display:none">
        <div class="tt-dash-header">
            <span class="tt-dash-label">Analyzing:</span>
            <span class="tt-dash-query-val" id="tt-dash-q"></span>
            <span class="tt-dash-type-chip" id="tt-dash-type"></span>
            <div class="tt-dash-header-right">
                <button class="tt-dash-clear" onclick="TT.clear()">✕ Clear</button>
            </div>
        </div>
        <div class="tt-dash-body" id="tt-dash-content"></div>
    </div>

    <!-- ── Tool Explorer label ── -->
    <div class="tt-explorer-label" id="tt-explorer-label" style="display:none">Tool Explorer — Deep Dive</div>

    <!-- ══ ACCORDION 1 — Basic Information ══ -->
    <div class="tt-accordion" id="acc-basic">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('info') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Basic Information</div>
                <div class="tt-acc-desc">Date, weekday, quarter, season, leap year & more</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">18 fields</span>
                <span class="tt-acc-updated" id="upd-basic"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div class="tt-section">
                <div id="basic-out"></div>
            </div>
        </div>
    </div>

    <!-- ══ ACCORDION 2 — Timestamp Toolkit ══ -->
    <div class="tt-accordion" id="acc-ts">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('hash') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Timestamp Toolkit</div>
                <div class="tt-acc-desc">Unix, ms, epoch conversions, batch converter & difference calculator</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">12 tools</span>
                <span class="tt-acc-updated" id="upd-ts"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div id="ts-out"></div>
        </div>
    </div>

    <!-- ══ ACCORDION 3 — Date Format Toolkit ══ -->
    <div class="tt-accordion" id="acc-fmt">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('code') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Date Format Toolkit</div>
                <div class="tt-acc-desc">22+ formats with custom builder & PHP, JS, Python, SQL snippets</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">22 formats</span>
                <span class="tt-acc-updated" id="upd-fmt"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div id="fmt-out"></div>
        </div>
    </div>

    <!-- ══ ACCORDION 4 — Calendar Toolkit ══ -->
    <div class="tt-accordion" id="acc-cal">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('calendar') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Calendar Toolkit</div>
                <div class="tt-acc-desc">Interactive monthly calendar with week numbers and Hijri dates</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">5 views</span>
                <span class="tt-acc-updated" id="upd-cal"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div class="tt-section">
                <div class="tt-grid-2">
                    <div>
                        <div class="tt-cal" id="cal-grid"></div>
                    </div>
                    <div>
                        <div class="tt-section-title">Calendar Info</div>
                        <div id="cal-info-out" class="tt-grid"></div>
                        <div class="tt-divider"></div>
                        <div class="tt-section-title" style="margin-top:12px">Jump to Date</div>
                        <div class="tt-row" style="margin-top:8px">
                            <input class="tt-input" id="cal-jump" type="date" value="<?= date('Y-m-d') ?>">
                            <button class="tt-btn tt-btn-primary" onclick="TT.calJump()">Go</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ ACCORDION 5 — Timezone Toolkit ══ -->
    <div class="tt-accordion" id="acc-tz">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('globe') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Timezone Toolkit</div>
                <div class="tt-acc-desc">Live world clock, timezone converter, DST detector, meeting planner</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">16 zones</span>
                <span class="tt-acc-updated" id="upd-tz"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div class="tt-section">
                <div class="tt-section-title">🌍 Live World Clock</div>
                <div class="tt-world-grid" id="world-clock-grid"></div>
            </div>
            <div id="tz-out"></div>
        </div>
    </div>

    <!-- ══ ACCORDION 6 — Date Arithmetic ══ -->
    <div class="tt-accordion" id="acc-arith">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('plus') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Date Arithmetic</div>
                <div class="tt-acc-desc">Add or subtract days, weeks, months, years, hours & business days</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">12 ops</span>
                <span class="tt-acc-updated" id="upd-arith"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div id="arith-out"></div>
        </div>
    </div>

    <!-- ══ ACCORDION 7 — Date Difference ══ -->
    <div class="tt-accordion" id="acc-diff">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('minus') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Date Difference</div>
                <div class="tt-acc-desc">Difference in years, months, weeks, days, hours, business days</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">10 units</span>
                <span class="tt-acc-updated" id="upd-diff"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div id="diff-out"></div>
        </div>
    </div>

    <!-- ══ ACCORDION 8 — Age Toolkit ══ -->
    <div class="tt-accordion" id="acc-age">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('users') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Age Toolkit</div>
                <div class="tt-acc-desc">Current age, countdown to next birthday, heartbeat estimate</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">12 stats</span>
                <span class="tt-acc-updated" id="upd-age"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div id="age-out"></div>
        </div>
    </div>

    <!-- ══ ACCORDION 9 — Astronomy Toolkit ══ -->
    <div class="tt-accordion" id="acc-astro">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('sun') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Astronomy Toolkit</div>
                <div class="tt-acc-desc">Sunrise, sunset, solar noon, moon phase, twilight times</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">14 values</span>
                <span class="tt-acc-updated" id="upd-astro"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div id="astro-out"></div>
        </div>
    </div>

    <!-- ══ ACCORDION 10 — Business Toolkit ══ -->
    <div class="tt-accordion" id="acc-biz">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('briefcase') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Business Toolkit</div>
                <div class="tt-acc-desc">Working days counter, shift calculator & business hours overlap</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">8 tools</span>
                <span class="tt-acc-updated" id="upd-biz"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div id="biz-out"></div>
        </div>
    </div>

    <!-- ══ ACCORDION 11 — Developer Toolkit ══ -->
    <div class="tt-accordion" id="acc-dev">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('terminal') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Developer Toolkit</div>
                <div class="tt-acc-desc">Ready-to-use date code for PHP, JavaScript, Python, MySQL, PostgreSQL, SQLite</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">4 languages</span>
                <span class="tt-acc-updated" id="upd-dev"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div class="tt-section">
                <div id="dev-out"></div>
            </div>
        </div>
    </div>

    <!-- ══ ACCORDION 12 — Cron Toolkit ══ -->
    <div class="tt-accordion" id="acc-cron">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('repeat') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Cron Toolkit</div>
                <div class="tt-acc-desc">Validate & explain cron expressions, preview next 10 executions</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">12 patterns</span>
                <span class="tt-acc-updated" id="upd-cron"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div id="cron-out"></div>
        </div>
    </div>

    <!-- ══ ACCORDION 13 — Export Toolkit ══ -->
    <div class="tt-accordion" id="acc-export">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('download') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Export Toolkit</div>
                <div class="tt-acc-desc">Export results as JSON, CSV, XML, YAML, TXT or Markdown</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">6 formats</span>
                <span class="tt-acc-updated" id="upd-export"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div id="export-out"></div>
        </div>
    </div>

    <!-- ══ ACCORDION 14 — Productivity Toolkit ══ -->
    <div class="tt-accordion" id="acc-pomo">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('sparkles') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Productivity Toolkit</div>
                <div class="tt-acc-desc">Pomodoro planner, focus sessions & time block calculator</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">4 tools</span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div class="tt-section">
                <div class="tt-section-title">Pomodoro Calculator</div>
                <div class="tt-row">
                    <span class="tt-label">Tasks</span>
                    <input class="tt-input" id="pomo-tasks" type="number" value="8" min="1" style="max-width:80px">
                    <span class="tt-label">Work (min)</span>
                    <input class="tt-input" id="pomo-work" type="number" value="25" min="1" style="max-width:80px">
                    <span class="tt-label">Short break</span>
                    <input class="tt-input" id="pomo-short" type="number" value="5" min="1" style="max-width:80px">
                    <span class="tt-label">Long break</span>
                    <input class="tt-input" id="pomo-long" type="number" value="15" min="1" style="max-width:80px">
                    <button class="tt-btn tt-btn-primary" onclick="TT.calcPomodoro()">Calculate</button>
                </div>
                <div id="pomo-res" class="tt-result" style="margin-top:10px"></div>
            </div>
            <div class="tt-section">
                <div class="tt-section-title">Reading / Speaking Time</div>
                <div class="tt-row">
                    <input class="tt-input" id="read-words" type="number" placeholder="Word count" value="1000">
                    <button class="tt-btn tt-btn-primary" onclick="TT.calcReadTime()">Calculate</button>
                </div>
                <div id="read-res" class="tt-result" style="margin-top:10px"></div>
            </div>
            <div class="tt-section">
                <div class="tt-section-title">Habit Streak Tracker</div>
                <div class="tt-row">
                    <span class="tt-label">Started</span>
                    <input class="tt-input" id="habit-start" type="date" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                    <span class="tt-label">Goal (days)</span>
                    <input class="tt-input" id="habit-goal" type="number" value="66" min="1" style="max-width:80px">
                    <button class="tt-btn tt-btn-primary" onclick="TT.calcHabit()">Check</button>
                </div>
                <div id="habit-res" class="tt-result" style="margin-top:10px"></div>
            </div>
        </div>
    </div>

    <!-- ══ ACCORDION 15 — Religious Toolkit ══ -->
    <div class="tt-accordion" id="acc-rel">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('leaf') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Religious Toolkit</div>
                <div class="tt-acc-desc">Hijri calendar conversion, Ramadan detection</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">6 tools</span>
                <span class="tt-acc-updated" id="upd-rel"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div id="rel-out"></div>
        </div>
    </div>

    <!-- ══ ACCORDION 16 — Utility Toolkit ══ -->
    <div class="tt-accordion" id="acc-util">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('tool') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Utility Toolkit</div>
                <div class="tt-acc-desc">Leap year checker, day of week finder, random date generator, date validator</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">6 tools</span>
                <span class="tt-acc-updated" id="upd-util"></span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div id="util-out"></div>
        </div>
    </div>

    <!-- ══ ACCORDION 17 — Calendar Conversions ══ -->
    <div class="tt-accordion" id="acc-calconv">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('refresh') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">Calendar Conversions</div>
                <div class="tt-acc-desc">Convert between Gregorian, Hijri, Persian (Jalali), Julian &amp; Hebrew calendars</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">5 calendars</span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div class="tt-section">
                <div class="tt-calconv-form">
                    <div class="tt-calconv-input-group">
                        <label class="tt-label" for="calconv-date">Date Value</label>
                        <input class="tt-input" id="calconv-date" type="text"
                            placeholder="e.g. 2025-01-15 or 1446/6/15 (Hijri Y/M/D)"
                            value="<?= date('Y-m-d') ?>"
                            onkeydown="if(event.key==='Enter') TT.convertCalendar()">
                    </div>
                    <div class="tt-calconv-selects">
                        <div class="tt-calconv-select-group">
                            <label class="tt-label" for="calconv-from">From Calendar</label>
                            <select class="tt-select" id="calconv-from">
                                <option value="gregorian">📅 Gregorian</option>
                                <option value="hijri">☪️ Hijri / Islamic</option>
                                <option value="persian">🌸 Persian / Jalali</option>
                                <option value="julian">📜 Julian Calendar</option>
                            </select>
                        </div>
                        <div class="tt-calconv-arrow">→</div>
                        <div class="tt-calconv-select-group">
                            <label class="tt-label" for="calconv-to">To Calendar</label>
                            <select class="tt-select" id="calconv-to">
                                <option value="all">🌍 All Systems</option>
                                <option value="gregorian">📅 Gregorian</option>
                                <option value="hijri">☪️ Hijri / Islamic</option>
                                <option value="persian">🌸 Persian / Jalali</option>
                                <option value="julian">📜 Julian Calendar</option>
                                <option value="hebrew">✡️ Hebrew (Approx.)</option>
                            </select>
                        </div>
                    </div>
                    <button class="tt-btn tt-btn-primary tt-calconv-btn" onclick="TT.convertCalendar()">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                        Convert
                    </button>
                </div>
                <div id="calconv-out" class="tt-calconv-out">
                    <div class="tt-nodata" style="padding:24px 0">Enter a date above and click Convert</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ ACCORDION 18 — cURL API Reference ══ -->
    <div class="tt-accordion" id="acc-curl">
        <div class="tt-acc-header">
            <div class="tt-acc-icon"><?= tt_icon('link') ?></div>
            <div class="tt-acc-info">
                <div class="tt-acc-title">cURL API Reference</div>
                <div class="tt-acc-desc">Every tool accessible via REST API — use from terminal, scripts, or any HTTP client</div>
            </div>
            <div class="tt-acc-meta">
                <span class="tt-acc-count">9 endpoints</span>
            </div>
            <div class="tt-acc-chevron"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>
        </div>
        <div class="tt-acc-body">
            <div class="tt-section">
                <div id="curl-out"></div>
            </div>
        </div>
    </div>

</div><!-- /.tt-wrap -->

<script src="/plugins/<?= $slug ?>/assets/time-toolkit.js"></script>
<script>
// Extra productivity tools not in main namespace
TT.calcReadTime = function() {
    const words = parseInt((document.getElementById('read-words')||{}).value||1000);
    const res = document.getElementById('read-res');
    if (!res) return;
    const readMin = Math.ceil(words / 238);
    const speakMin = Math.ceil(words / 130);
    res.innerHTML = `<div class="tt-grid">
        ${TT._vc ? '' : ''}
        <div class="tt-val-cell"><div class="tt-val-label">Reading Time</div><div class="tt-val-value">${readMin} min</div></div>
        <div class="tt-val-cell"><div class="tt-val-label">Speaking Time</div><div class="tt-val-value">${speakMin} min</div></div>
        <div class="tt-val-cell"><div class="tt-val-label">@ 238 wpm (read)</div><div class="tt-val-value">${words} words</div></div>
        <div class="tt-val-cell"><div class="tt-val-label">@ 130 wpm (speak)</div><div class="tt-val-value">${words} words</div></div>
    </div>`;
};
TT.calcHabit = function() {
    const startVal = (document.getElementById('habit-start')||{}).value;
    const goal = parseInt((document.getElementById('habit-goal')||{}).value||66);
    const res = document.getElementById('habit-res');
    if (!res || !startVal) return;
    const start = new Date(startVal + 'T00:00:00');
    const now = new Date();
    const days = Math.floor((now - start) / 86400000);
    const pct = Math.min(100, Math.round(days / goal * 100));
    const remaining = Math.max(0, goal - days);
    res.innerHTML = `<div class="tt-grid">
        <div class="tt-val-cell"><div class="tt-val-label">Streak</div><div class="tt-val-value big">${days} days</div></div>
        <div class="tt-val-cell"><div class="tt-val-label">Goal</div><div class="tt-val-value">${goal} days</div></div>
        <div class="tt-val-cell"><div class="tt-val-label">Remaining</div><div class="tt-val-value">${remaining} days</div></div>
        <div class="tt-val-cell"><div class="tt-val-label">Complete</div><div class="tt-val-value">${pct}%</div></div>
    </div>
    <div style="margin-top:10px">
        <div class="tt-val-label">Progress</div>
        <div class="tt-bar-wrap" style="height:10px;margin-top:4px">
            <div class="tt-bar-fill ${pct>=100?'success':pct>=50?'warning':'danger'}" style="width:${pct}%"></div>
        </div>
    </div>`;
};
TT.calJump = function() {
    const val = (document.getElementById('cal-jump')||{}).value;
    if (val) { TT._calDate = new Date(val + 'T00:00:00'); TT.renderCalendar(null); }
};
// Init on load
document.addEventListener('DOMContentLoaded', function() {
    // Today at a Glance
    TT.initTodayPanel();
    // cURL docs (pre-render so they're ready when accordion opens)
    TT.renderCurlDocs();
    // Calendar conversions — auto-convert today's date on load
    TT.convertCalendar();
    // Productivity tools
    TT.calcPomodoro();
    TT.calcReadTime();
    TT.calcHabit();
    // Render utility & religious for today
    const today = {type:'date', valid:true, date:new Date(), unix:Math.floor(Date.now()/1000), ms:Date.now(), lat:null, lng:null, cron:null};
    TT.renderUtilities(today);
    TT.renderReligious(today);
    TT.renderTimestamp(today);
    TT.renderArithmetic(today);
    TT.renderDifference(today);
    TT.renderAge(today);
    TT.renderAstronomy({type:'date', valid:true, date:new Date(), lat:null, lng:null});
    TT.renderBusiness(today);
    TT.renderDeveloper(today);
    TT.renderCron({type:'cron', valid:true, cron:'0 9 * * 1-5'});
});
</script>

<?php echo plugin_related_html($slug); ?>

<?php
$content = ob_get_clean();
plugin_render('Time Toolkit — Date & Time Workbench', $content, [
    'description' => 'Professional Date & Time Workbench with 16 intelligent tool groups. Universal input auto-detects any date, timestamp, timezone or coordinates.',
]);
