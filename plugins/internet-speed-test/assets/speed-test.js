/**
 * Internet Speed Test — IST Engine
 * Self-contained IIFE; exposes a single `IST` global.
 *
 * ┌─────────────────────────────────────────────────────┐
 * │  DEVELOPER TWEAKABLE SECTION ──> search for "★"    │
 * └─────────────────────────────────────────────────────┘
 */
/* global IST */
var IST = (function () {
    'use strict';

    /* ════════════════════════════════════════════════════════════════
     * ★ CONFIGURATION — Edit these values to tune the plugin.
     * ════════════════════════════════════════════════════════════════ */
    var CFG = {
        /* Gauge geometry — must match SVG viewBox in HTML */
        gauge: { CX: 160, CY: 155, R: 120, MIN_DEG: -135, MAX_DEG: 135, MAX_MBPS: 1000 },

        /* Test parameters */
        test: {
            PING_COUNT:          8,       // total pings; first 2 discarded as warm-up
            PING_TIMEOUT:        3000,    // ms per ping
            PING_INTERVAL:       80,      // ms between pings
            DL_ROUNDS:           2,       // legacy single-chunk rounds (not used by streaming)
            DL_CHUNK_SIZE:       2097152, // bytes per download chunk (legacy)
            UL_ROUNDS:           2,       // upload rounds (legacy)
            UL_SIZE:             1048576, // bytes per upload blob (legacy)
            PKT_LOSS_PINGS:      10,      // pings for packet-loss estimate
            PKT_LOSS_TIMEOUT:    1500,    // ms per packet-loss ping
            DNS_TIMEOUT:         5000,

            /* ── Parallel streaming test (high-accuracy mode) ── */
            DL_STREAMS:          6,       // parallel download connections
            DL_DURATION:         6000,    // total measurement window (ms)
            DL_WARMUP:           1500,    // discard first N ms (TCP slow-start)
            DL_SERVER_DURATION:  8,       // server streams for this many seconds

            UL_STREAMS:          4,       // parallel upload connections
            UL_BLOB_SIZE:        4194304, // 4 MB per upload blob
            UL_DURATION:         5000,    // upload measurement window (ms)
        },

        /* Quality thresholds (units noted in comments) */
        thresholds: {
            download: { excellent: 100, good: 25, fair: 10 },  // Mbps
            upload:   { excellent:  50, good: 10, fair:  5 },  // Mbps
            ping:     { excellent:  20, good: 60, fair: 100 }, // ms
            jitter:   { excellent:   5, good: 20, fair:  50 }, // ms
            loss:     { excellent: 0.5, good:  2, fair:   5 }, // %
        },

        /* Score weights (sum = 1.0) */
        scoreWeights: {
            download: 0.30,
            upload:   0.20,
            ping:     0.25,
            jitter:   0.15,
            loss:     0.10,
        },

        /* Export defaults */
        export: { filename: 'speed-test-report' },

        /* API base path (relative, no trailing slash) */
        api:     '/plugins/internet-speed-test/api',

        /* Network Toolkit path for deep-link integration */
        ntPath:  '/plugins/network-toolkit',

        /* Chart: max data points retained */
        chartMaxPoints: 60,

        /* ── Test mode presets ── */
        presets: {
            fast: {
                label: 'Fast', estSec: 22,
                PING_COUNT: 5,  PING_INTERVAL: 80, PING_TIMEOUT: 2000,
                PKT_LOSS_PINGS: 5,  PKT_LOSS_TIMEOUT: 600,
                DL_STREAMS: 3, DL_DURATION: 3000,  DL_WARMUP: 800,  DL_SERVER_DURATION: 5,
                UL_STREAMS: 2, UL_BLOB_SIZE: 2097152, UL_DURATION: 3000,
                phases: ['env','latency','download','upload','scoring','report'],
            },
            basic: {
                label: 'Basic', estSec: 38,
                PING_COUNT: 8,  PING_INTERVAL: 80, PING_TIMEOUT: 3000,
                PKT_LOSS_PINGS: 10, PKT_LOSS_TIMEOUT: 600,
                DL_STREAMS: 8,  DL_DURATION: 6000,  DL_WARMUP: 1500, DL_SERVER_DURATION: 8,
                UL_STREAMS: 6,  UL_BLOB_SIZE: 8388608, UL_DURATION: 5000,
                phases: ['env','latency','dns','pktloss','download','upload','stability','scoring','report'],
            },
            professional: {
                label: 'Professional', estSec: 80,
                PING_COUNT: 15, PING_INTERVAL: 80, PING_TIMEOUT: 4000,
                PKT_LOSS_PINGS: 20, PKT_LOSS_TIMEOUT: 600,
                DL_STREAMS: 16, DL_DURATION: 12000, DL_WARMUP: 2000, DL_SERVER_DURATION: 15,
                UL_STREAMS: 10, UL_BLOB_SIZE: 33554432, UL_DURATION: 10000,
                SERVER_TIMINGS: true,
                phases: ['env','latency','dns','pktloss','download','upload','stability','platform','scoring','report'],
            },
        },
        currentMode: 'basic',
    };

    /* ════════════════════════════════════════════════════════════════
     * State
     * ════════════════════════════════════════════════════════════════ */
    var state = {
        running:      false,
        cancelled:    false,
        needsRefresh: false,
        results:      {},
        charts:       {},   // chartId → { data, opts, el }
        samples:      { download: [], upload: [], ping: [], jitter: [], loss: [] },
        peak:         { download: 0, upload: 0, ping: 0, jitter: 0 },
        termLogs:     [],
        termFilter:   'all',
        termSearch:   '',
        termPaused:   false,
        lastTest:     null,
        elapsedTimer: null,
        startTime:    null,
        diagResults:  {},
    };

    /* ════════════════════════════════════════════════════════════════
     * Gauge helpers — CSS-angle convention:
     *   0° = top (12 o'clock), positive = clockwise.
     *   x = CX + R·sin(rad),  y = CY − R·cos(rad)
     * ════════════════════════════════════════════════════════════════ */
    function gaugePoint(cssDeg, r) {
        var rad = cssDeg * Math.PI / 180;
        return { x: CFG.gauge.CX + r * Math.sin(rad), y: CFG.gauge.CY - r * Math.cos(rad) };
    }

    function arcPath(startDeg, endDeg, r) {
        var s = gaugePoint(startDeg, r);
        var e = gaugePoint(endDeg,   r);
        var span = ((endDeg - startDeg) + 360) % 360;
        var largeArc = span > 180 ? 1 : 0;
        return 'M ' + s.x.toFixed(2) + ' ' + s.y.toFixed(2) +
               ' A ' + r + ' ' + r + ' 0 ' + largeArc + ' 1 ' +
               e.x.toFixed(2) + ' ' + e.y.toFixed(2);
    }

    /* Map Mbps → gauge CSS-angle (log10 scale for visual spread) */
    function speedToDeg(mbps) {
        var min = CFG.gauge.MIN_DEG, max = CFG.gauge.MAX_DEG;
        if (!mbps || mbps <= 0) return min;
        // Log scale: 0.1 Mbps → min, 1000 Mbps → max
        var lo = Math.log10(0.1), hi = Math.log10(CFG.gauge.MAX_MBPS);
        var t  = Math.max(0, Math.min(1, (Math.log10(mbps) - lo) / (hi - lo)));
        return min + t * (max - min);
    }

    /* Arc circumference for a degree span */
    function arcLen(span, r) { return 2 * Math.PI * r * (Math.abs(span) / 360); }

    /* Total track length for the gauge arc (270°) */
    var TRACK_LEN = arcLen(270, CFG.gauge.R); // ≈ 565.5

    /* ════════════════════════════════════════════════════════════════
     * DOM helpers
     * ════════════════════════════════════════════════════════════════ */
    function $$(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $$$(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }
    function el(tag, attrs, children) {
        var e = document.createElementNS(
            attrs && attrs.ns ? attrs.ns : (tag === 'svg' || attrs && attrs._svg ? 'http://www.w3.org/2000/svg' : null)
            , tag);
        if (attrs) Object.keys(attrs).forEach(function(k) {
            if (k !== 'ns' && k !== '_svg') e.setAttribute(k, attrs[k]);
        });
        (children || []).forEach(function(c) { c && e.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
        return e;
    }
    function svgEl(tag, attrs) {
        var e = document.createElementNS('http://www.w3.org/2000/svg', tag);
        if (attrs) Object.keys(attrs).forEach(function(k) { e.setAttribute(k, attrs[k]); });
        return e;
    }

    /* ════════════════════════════════════════════════════════════════
     * Gauge initialisation
     * ════════════════════════════════════════════════════════════════ */
    var _gaugeNeedleEl = null, _gaugeProgressEl = null, _gaugePeakEl = null, _gaugeValEl = null;
    var _currentGaugeDeg = CFG.gauge.MIN_DEG;
    var _peakGaugeDeg = null;

    /* Upload gauge — separate state */
    var _ulNeedleEl = null, _ulProgressEl = null, _ulPeakEl = null;
    var _ulCurrentDeg = CFG.gauge.MIN_DEG;
    var _ulRafId = null;
    var _ulInitialized = false;

    function initGauge() {
        var svg = $$('#ist-gauge-svg');
        if (!svg) return;

        var CX = CFG.gauge.CX, CY = CFG.gauge.CY, R = CFG.gauge.R;

        // Gradient definition — danger (slow/left) → warning → success (fast/right)
        var defs = svgEl('defs');
        var lg = svgEl('linearGradient', { id: 'istGaugeGrad', x1: '0%', y1: '0%', x2: '100%', y2: '0%' });
        var s1 = svgEl('stop', { offset: '0%',   'stop-color': 'var(--color-danger)' });
        var s2 = svgEl('stop', { offset: '40%',  'stop-color': 'var(--color-warning)' });
        var s3 = svgEl('stop', { offset: '100%', 'stop-color': 'var(--color-success)' });
        lg.appendChild(s1); lg.appendChild(s2); lg.appendChild(s3);

        // Area gradient for charts
        var ag = svgEl('linearGradient', { id: 'istChartGrad', x1: '0%', y1: '0%', x2: '0%', y2: '100%' });
        var as1 = svgEl('stop', { offset: '0%',   'stop-color': 'var(--color-primary)', 'stop-opacity': '0.4' });
        var as2 = svgEl('stop', { offset: '100%', 'stop-color': 'var(--color-primary)', 'stop-opacity': '0' });
        ag.appendChild(as1); ag.appendChild(as2);

        defs.appendChild(lg); defs.appendChild(ag);
        svg.insertBefore(defs, svg.firstChild);

        // Background track
        var bgTrack = svgEl('path', {
            d: arcPath(-135, 135, R),
            class: 'ist-gauge-track',
            'stroke-dasharray': TRACK_LEN.toFixed(1),
            'stroke-dashoffset': '0',
        });
        svg.appendChild(bgTrack);

        // Quality zone arcs (subtle colored bands: slow=danger, medium=warning, fast=success)
        // Divide the 270° gauge arc into 3 equal thirds by position
        var zoneSpecs = [
            { from: -135, to:  -45, cls: 'ist-gauge-zone-slow'   }, // left 1/3 (slow)
            { from:  -45, to:   45, cls: 'ist-gauge-zone-medium' }, // middle 1/3
            { from:   45, to:  135, cls: 'ist-gauge-zone-fast'   }, // right 1/3 (fast)
        ];
        zoneSpecs.forEach(function(z) {
            var za = svgEl('path', {
                d: arcPath(z.from, z.to, R),
                class: 'ist-gauge-zone ' + z.cls,
                'stroke-linecap': 'butt',
            });
            svg.appendChild(za);
        });

        // Tick marks and labels
        var tickLabels = [0.1, 1, 5, 25, 100, 300, 600, 1000];
        tickLabels.forEach(function(mbps) {
            var deg  = speedToDeg(mbps);
            var pOuter = gaugePoint(deg, R - 4);
            var pInner = gaugePoint(deg, R - 14);
            var pLabel = gaugePoint(deg, R - 26);
            var tick = svgEl('line', {
                x1: pOuter.x.toFixed(1), y1: pOuter.y.toFixed(1),
                x2: pInner.x.toFixed(1), y2: pInner.y.toFixed(1),
                class: 'ist-gauge-tick ist-gauge-tick-major',
            });
            svg.appendChild(tick);
            var text = svgEl('text', {
                x: pLabel.x.toFixed(1), y: pLabel.y.toFixed(1),
                class: 'ist-gauge-label',
                'text-anchor': 'middle',
                'dominant-baseline': 'middle',
            });
            text.textContent = mbps >= 1000 ? '1G' : (mbps >= 1 ? mbps + '' : '.1');
            svg.appendChild(text);
        });

        // Minor ticks (every ~15°)
        for (var d = -135; d <= 135; d += 15) {
            var hasLabel = tickLabels.some(function(m) { return Math.abs(speedToDeg(m) - d) < 5; });
            if (!hasLabel) {
                var pO = gaugePoint(d, R - 4);
                var pI = gaugePoint(d, R - 10);
                svg.appendChild(svgEl('line', {
                    x1: pO.x.toFixed(1), y1: pO.y.toFixed(1),
                    x2: pI.x.toFixed(1), y2: pI.y.toFixed(1),
                    class: 'ist-gauge-tick',
                }));
            }
        }

        // Progress arc (starts empty)
        var progressArc = svgEl('path', {
            id: 'ist-gauge-progress-arc',
            d: arcPath(-135, 135, R),
            class: 'ist-gauge-progress',
            'stroke-dasharray': TRACK_LEN.toFixed(1),
            'stroke-dashoffset': TRACK_LEN.toFixed(1),
        });
        svg.appendChild(progressArc);
        _gaugeProgressEl = progressArc;

        // Peak marker line
        var peakP1 = gaugePoint(-135, R + 4);
        var peakP2 = gaugePoint(-135, R - 18);
        var peakLine = svgEl('line', {
            id: 'ist-gauge-peak',
            x1: peakP1.x.toFixed(1), y1: peakP1.y.toFixed(1),
            x2: peakP2.x.toFixed(1), y2: peakP2.y.toFixed(1),
            class: 'ist-gauge-peak',
        });
        svg.appendChild(peakLine);
        _gaugePeakEl = peakLine;

        // Needle — SVG transform attr so rotation is in SVG space (immune to CSS scaling on mobile)
        var needleG = svgEl('g', {
            id: 'ist-gauge-needle',
            class: 'ist-gauge-needle',
            transform: 'rotate(' + CFG.gauge.MIN_DEG + ',' + CX + ',' + CY + ')',
        });

        // Needle body
        var needleBody = svgEl('polygon', {
            points: (CX) + ',' + (CY - (R - 10)) + ' ' +
                    (CX - 3.5) + ',' + CY + ' ' +
                    (CX + 3.5) + ',' + CY,
            fill: 'var(--color-primary)',
        });
        needleG.appendChild(needleBody);

        // Needle pivot circle
        var pivot = svgEl('circle', {
            cx: CX, cy: CY, r: 8,
            fill: 'var(--color-surface)',
            stroke: 'var(--color-primary)',
            'stroke-width': '2',
        });
        needleG.appendChild(pivot);
        svg.appendChild(needleG);
        _gaugeNeedleEl = needleG;

        // Glow pulse circle
        var glowCirc = svgEl('circle', {
            cx: CX, cy: CY, r: R + 12,
            fill: 'none',
            stroke: 'var(--color-primary)',
            'stroke-width': '2',
            opacity: '0',
            id: 'ist-gauge-glow',
        });
        svg.insertBefore(glowCirc, svg.firstChild);
    }

    /* Redraw gauge tick marks and labels for a new MAX_MBPS scale */
    function rescaleGaugeLabels(maxMbps) {
        var svg = $$('#ist-gauge-svg');
        if (!svg) return;
        var R = CFG.gauge.R;

        // Remove old ticks and labels (but keep progress arc, needle, glow)
        Array.from(svg.querySelectorAll('.ist-gauge-tick, .ist-gauge-label')).forEach(function(e) {
            e.parentNode && e.parentNode.removeChild(e);
        });

        // Choose nice tick values based on scale
        var tickLabels;
        if (maxMbps <= 25)   tickLabels = [0.5, 1, 5, 10, 20, 25];
        else if (maxMbps <= 50)   tickLabels = [1, 5, 10, 25, 40, 50];
        else if (maxMbps <= 100)  tickLabels = [1, 5, 25, 50, 75, 100];
        else if (maxMbps <= 200)  tickLabels = [1, 10, 50, 100, 150, 200];
        else if (maxMbps <= 500)  tickLabels = [1, 25, 100, 250, 400, 500];
        else if (maxMbps <= 1000) tickLabels = [1, 25, 100, 300, 600, 1000];
        else if (maxMbps <= 2500) tickLabels = [10, 100, 500, 1000, 2000, 2500];
        else                      tickLabels = [10, 100, 500, 2000, 5000, 10000];

        // Insert before needle so ticks stay behind it
        var insertRef = $$('#ist-gauge-needle') || null;
        function ins(el) { insertRef ? svg.insertBefore(el, insertRef) : svg.appendChild(el); }

        // Minor ticks
        for (var d = -135; d <= 135; d += 15) {
            var hasLabel = tickLabels.some(function(m) { return Math.abs(speedToDeg(m) - d) < 5; });
            if (!hasLabel) {
                var pO = gaugePoint(d, R - 4), pI = gaugePoint(d, R - 10);
                ins(svgEl('line', { x1: pO.x.toFixed(1), y1: pO.y.toFixed(1), x2: pI.x.toFixed(1), y2: pI.y.toFixed(1), class: 'ist-gauge-tick' }));
            }
        }
        // Major ticks + labels
        tickLabels.forEach(function(mbps) {
            var deg = speedToDeg(mbps);
            var pOuter = gaugePoint(deg, R - 4), pInner = gaugePoint(deg, R - 14), pLbl = gaugePoint(deg, R - 26);
            ins(svgEl('line', { x1: pOuter.x.toFixed(1), y1: pOuter.y.toFixed(1), x2: pInner.x.toFixed(1), y2: pInner.y.toFixed(1), class: 'ist-gauge-tick ist-gauge-tick-major' }));
            var txt = svgEl('text', { x: pLbl.x.toFixed(1), y: pLbl.y.toFixed(1), class: 'ist-gauge-label', 'text-anchor': 'middle', 'dominant-baseline': 'middle' });
            txt.textContent = mbps >= 10000 ? '10G' : mbps >= 1000 ? (mbps / 1000).toFixed(0) + 'G' : mbps >= 1 ? mbps.toFixed(0) : '.' + (mbps * 10).toFixed(0);
            ins(txt);
        });
    }

    /* Quick 2s calibration download using 3 parallel streams → pick a nice gauge max */
    async function calibrateGauge() {
        var CAL_MS      = 3000; // measurement window (3 s for more stable estimate)
        var CAL_STREAMS = 5;    // 5 parallel streams saturate server better than 3

        log('Gauge calibration — ' + CAL_STREAMS + '-stream, ' + (CAL_MS / 1000) + 's pre-test...', 'debug');
        setReadout('CAL', '', 'CALIBRATE');

        // Use AbortController so streams cancel instantly when window ends
        var calControllers = [];
        for (var ci = 0; ci < CAL_STREAMS; ci++) calControllers.push(new AbortController());
        var calAbortTimer = setTimeout(function() {
            calControllers.forEach(function(c) { try { c.abort(); } catch(e) {} });
        }, CAL_MS);

        var calBytes = 0;
        var t0 = performance.now();

        try {
            var promises = [];
            for (var cs = 0; cs < CAL_STREAMS; cs++) {
                (function(idx) {
                    var url = CFG.api + '?action=download_stream&duration=5&s=cal' + idx + '&_=' + (Date.now() + idx);
                    var ctrl = calControllers[idx];
                    var p = fetch(url, { cache: 'no-store', signal: ctrl.signal })
                        .then(function(res) {
                            if (!res.ok) return;
                            var reader = res.body.getReader();
                            function pump() {
                                return reader.read().then(function(chunk) {
                                    if (chunk.done) return;
                                    calBytes += chunk.value.byteLength;
                                    return pump();
                                });
                            }
                            return pump();
                        })
                        .catch(function(e) {
                            if (e.name !== 'AbortError') log('Cal stream ' + idx + ' error: ' + e.message, 'debug');
                        });
                    promises.push(p);
                })(cs);
            }

            // Wait for measurement window to elapse (abort fires at CAL_MS)
            await new Promise(function(resolve) { setTimeout(resolve, CAL_MS + 50); });
            clearTimeout(calAbortTimer);
            await Promise.all(promises);

            var elapsed = (performance.now() - t0) / 1000;
            if (elapsed < 0.5 || calBytes < 131072) {
                log('Calibration skipped — insufficient data (' + (calBytes / 1024).toFixed(0) + ' KB)', 'debug');
                return;
            }

            var estMbps = (calBytes * 8) / elapsed / 1e6;
            log('Calibration estimate: ' + estMbps.toFixed(1) + ' Mbps (' + (calBytes / 1048576).toFixed(1) + ' MB in ' + elapsed.toFixed(1) + 's)', 'info');

            // Show calibrated speed in readout briefly so user can see the estimate
            setReadout(estMbps >= 10 ? estMbps.toFixed(0) : estMbps.toFixed(1), 'MBPS', 'CAL ~');

            // Pick next tidy max above 1.6× estimated speed.
            // 1.6× gives headroom without wasting scale on very fast connections.
            var niceMaxes = [10, 25, 50, 100, 200, 300, 500, 750, 1000, 2500, 5000, 10000];
            var ideal     = estMbps * 1.6;
            var newMax    = niceMaxes.find(function(m) { return m >= ideal; }) || 10000;

            // Floor: 100 Mbps minimum (prevents 1-bar gauge on very slow links).
            // No artificial 500 Mbps floor — let calibration set realistic scales.
            newMax = Math.max(newMax, 100);

            // Only scale DOWN if we have high-confidence data (>3 MB received)
            if (newMax < CFG.gauge.MAX_MBPS && calBytes < 3145728) {
                log('Calibration: keeping existing max (' + CFG.gauge.MAX_MBPS + ' Mbps) — low confidence', 'debug');
                return;
            }

            if (newMax !== CFG.gauge.MAX_MBPS) {
                CFG.gauge.MAX_MBPS = newMax;
                rescaleGaugeLabels(newMax);
                rescaleUploadGaugeLabels(newMax);
                var lbl = newMax >= 1000 ? (newMax / 1000).toFixed(1).replace('.0', '') + ' Gbps' : newMax + ' Mbps';
                log('Gauge auto-scaled to 0–' + lbl + ' based on calibration', 'info');
            }
        } catch(e) {
            clearTimeout(calAbortTimer);
            calControllers.forEach(function(c) { try { c.abort(); } catch(_) {} });
            log('Calibration skipped: ' + e.message, 'debug');
        }
    }

    /* Rescale upload gauge tick labels to match a new MAX_MBPS */
    function rescaleUploadGaugeLabels(maxMbps) {
        var svg = $$('#ist-upload-gauge-svg');
        if (!svg) return;
        var R = CFG.gauge.R;

        Array.from(svg.querySelectorAll('.ist-gauge-tick, .ist-gauge-label')).forEach(function(e) {
            e.parentNode && e.parentNode.removeChild(e);
        });

        var tickLabels;
        if (maxMbps <= 25)        tickLabels = [0.5, 1, 5, 10, 20, 25];
        else if (maxMbps <= 50)   tickLabels = [1, 5, 10, 25, 40, 50];
        else if (maxMbps <= 100)  tickLabels = [1, 5, 25, 50, 75, 100];
        else if (maxMbps <= 200)  tickLabels = [1, 10, 50, 100, 150, 200];
        else if (maxMbps <= 500)  tickLabels = [1, 25, 100, 250, 400, 500];
        else if (maxMbps <= 1000) tickLabels = [1, 25, 100, 300, 600, 1000];
        else if (maxMbps <= 2500) tickLabels = [10, 100, 500, 1000, 2000, 2500];
        else                      tickLabels = [10, 100, 500, 2000, 5000, 10000];

        var insertRef = $$('#ist-ul-gauge-needle') || null;
        function ins(el) { insertRef ? svg.insertBefore(el, insertRef) : svg.appendChild(el); }

        for (var d = -135; d <= 135; d += 15) {
            var hasLabel = tickLabels.some(function(m) { return Math.abs(speedToDeg(m) - d) < 5; });
            if (!hasLabel) {
                var pO = gaugePoint(d, R - 4), pI = gaugePoint(d, R - 10);
                ins(svgEl('line', { x1: pO.x.toFixed(1), y1: pO.y.toFixed(1), x2: pI.x.toFixed(1), y2: pI.y.toFixed(1), class: 'ist-gauge-tick' }));
            }
        }
        tickLabels.forEach(function(mbps) {
            var deg = speedToDeg(mbps);
            var pOuter = gaugePoint(deg, R - 4), pInner = gaugePoint(deg, R - 14), pLbl = gaugePoint(deg, R - 26);
            ins(svgEl('line', { x1: pOuter.x.toFixed(1), y1: pOuter.y.toFixed(1), x2: pInner.x.toFixed(1), y2: pInner.y.toFixed(1), class: 'ist-gauge-tick ist-gauge-tick-major' }));
            var txt = svgEl('text', { x: pLbl.x.toFixed(1), y: pLbl.y.toFixed(1), class: 'ist-gauge-label', 'text-anchor': 'middle', 'dominant-baseline': 'middle' });
            txt.textContent = mbps >= 10000 ? '10G' : mbps >= 1000 ? (mbps / 1000).toFixed(0) + 'G' : mbps >= 1 ? mbps.toFixed(0) : '.' + (mbps * 10).toFixed(0);
            ins(txt);
        });
    }

    /* Auto-rescale gauge mid-test if live speed exceeds current max */
    function maybeRescaleForSpeed(mbps) {
        if (mbps <= CFG.gauge.MAX_MBPS * 0.85) return; // plenty of headroom
        var niceMaxes = [100, 200, 300, 500, 750, 1000, 2500, 5000, 10000];
        var ideal     = mbps * 1.5;
        var newMax    = niceMaxes.find(function(t) { return t >= ideal; }) || 10000;
        if (newMax > CFG.gauge.MAX_MBPS) {
            CFG.gauge.MAX_MBPS = newMax;
            rescaleGaugeLabels(newMax);
            rescaleUploadGaugeLabels(newMax);
            var lbl = newMax >= 1000 ? (newMax / 1000).toFixed(0) + 'G' : newMax;
            log('Gauge auto-scaled mid-test: 0–' + lbl + ' Mbps', 'debug');
        }
    }

    var _rafId = null;
    function animateGauge(targetMbps, phaseLabel, onTick) {
        if (_rafId) { cancelAnimationFrame(_rafId); _rafId = null; }
        var fromDeg  = _currentGaugeDeg;
        var targetDeg = speedToDeg(targetMbps);
        var startTime = performance.now();
        var dur = 150; // ms per frame cycle

        function frame(now) {
            var t  = Math.min((now - startTime) / dur, 1);
            var deg = fromDeg + (targetDeg - fromDeg) * (1 - Math.pow(1 - t, 3));
            deg += (t < 0.95 && targetMbps > 0) ? (Math.random() - 0.5) * 3 : 0;
            deg  = Math.max(CFG.gauge.MIN_DEG, Math.min(CFG.gauge.MAX_DEG, deg));

            _currentGaugeDeg = deg;

            // Rotate needle
            if (_gaugeNeedleEl) {
                _gaugeNeedleEl.setAttribute('transform', 'rotate(' + deg + ',' + CFG.gauge.CX + ',' + CFG.gauge.CY + ')');
            }

            // Update progress arc
            if (_gaugeProgressEl) {
                var spanFromStart = ((deg - CFG.gauge.MIN_DEG) / (CFG.gauge.MAX_DEG - CFG.gauge.MIN_DEG));
                var filled = spanFromStart * TRACK_LEN;
                _gaugeProgressEl.setAttribute('stroke-dashoffset', (TRACK_LEN - filled).toFixed(2));
            }

            // Update displayed number
            var dispMbps = ((deg - CFG.gauge.MIN_DEG) / (CFG.gauge.MAX_DEG - CFG.gauge.MIN_DEG));
            // Reverse log scale for display
            var lo = Math.log10(0.1), hi = Math.log10(CFG.gauge.MAX_MBPS);
            var mbpsDisplay = Math.max(0, Math.pow(10, lo + dispMbps * (hi - lo)));
            if ($$('#ist-readout-value')) {
                $$('#ist-readout-value').textContent = mbpsDisplay >= 10 ? mbpsDisplay.toFixed(0) : mbpsDisplay.toFixed(1);
            }

            if (onTick) onTick(mbpsDisplay);
            if (t < 1) { _rafId = requestAnimationFrame(frame); }
        }
        _rafId = requestAnimationFrame(frame);
    }

    function setGaugePeak(mbps) {
        if (!_gaugePeakEl) return;
        var deg = speedToDeg(mbps);
        var p1 = gaugePoint(deg, CFG.gauge.R + 4);
        var p2 = gaugePoint(deg, CFG.gauge.R - 18);
        _gaugePeakEl.setAttribute('x1', p1.x.toFixed(1));
        _gaugePeakEl.setAttribute('y1', p1.y.toFixed(1));
        _gaugePeakEl.setAttribute('x2', p2.x.toFixed(1));
        _gaugePeakEl.setAttribute('y2', p2.y.toFixed(1));
        _gaugePeakEl.style.opacity = '1';
    }

    function resetGauge() {
        _currentGaugeDeg = CFG.gauge.MIN_DEG;
        if (_gaugeNeedleEl) _gaugeNeedleEl.setAttribute('transform', 'rotate(' + CFG.gauge.MIN_DEG + ',' + CFG.gauge.CX + ',' + CFG.gauge.CY + ')');
        if (_gaugeProgressEl) _gaugeProgressEl.setAttribute('stroke-dashoffset', TRACK_LEN.toFixed(2));
        if (_gaugePeakEl) _gaugePeakEl.style.opacity = '0';
        setReadout('0', 'MBPS', 'READY');
    }

    function setReadout(val, unit, phase) {
        var ve = $$('#ist-readout-value'), ue = $$('#ist-readout-unit'), pe = $$('#ist-readout-phase');
        if (ve) ve.textContent = val;
        if (ue && unit !== undefined) ue.textContent = unit;
        if (pe && phase !== undefined) pe.textContent = phase;
    }

    /* ════════════════════════════════════════════════════════════════
     * Upload Gauge — separate SVG gauge for upload phase
     * ════════════════════════════════════════════════════════════════ */
    function initUploadGauge() {
        var svg = $$('#ist-upload-gauge-svg');
        if (!svg || _ulInitialized) return;
        _ulInitialized = true;

        var CX = CFG.gauge.CX, CY = CFG.gauge.CY, R = CFG.gauge.R;

        // Gradient — danger (slow/left) → warning → success (fast/right), tinted blue for upload distinction
        var defs = svgEl('defs');
        var lg = svgEl('linearGradient', { id: 'istUlGaugeGrad', x1: '0%', y1: '0%', x2: '100%', y2: '0%' });
        var s1 = svgEl('stop', { offset: '0%',   'stop-color': 'var(--color-danger)' });
        var s2 = svgEl('stop', { offset: '45%',  'stop-color': 'var(--color-primary)' });
        var s3 = svgEl('stop', { offset: '100%', 'stop-color': 'var(--color-success)' });
        lg.appendChild(s1); lg.appendChild(s2); lg.appendChild(s3);
        defs.appendChild(lg);
        svg.insertBefore(defs, svg.firstChild);

        // Background track
        var bgTrack = svgEl('path', {
            d: arcPath(-135, 135, R),
            class: 'ist-gauge-track',
            'stroke-dasharray': TRACK_LEN.toFixed(1),
            'stroke-dashoffset': '0',
        });
        svg.appendChild(bgTrack);

        // Quality zone arcs (same layout as download gauge)
        [{ from: -135, to: -45, cls: 'ist-gauge-zone-slow' },
         { from: -45,  to:  45, cls: 'ist-gauge-zone-medium' },
         { from:  45,  to: 135, cls: 'ist-gauge-zone-fast' }
        ].forEach(function(z) {
            svg.appendChild(svgEl('path', {
                d: arcPath(z.from, z.to, R),
                class: 'ist-gauge-zone ' + z.cls,
                'stroke-linecap': 'butt',
            }));
        });

        // Tick marks (same as download gauge)
        var tickLabels = [0.1, 1, 5, 25, 100, 300, 600, 1000];
        tickLabels.forEach(function(mbps) {
            var deg = speedToDeg(mbps);
            var pOuter = gaugePoint(deg, R - 4);
            var pInner = gaugePoint(deg, R - 14);
            var pLabel = gaugePoint(deg, R - 26);
            svg.appendChild(svgEl('line', { x1: pOuter.x.toFixed(1), y1: pOuter.y.toFixed(1), x2: pInner.x.toFixed(1), y2: pInner.y.toFixed(1), class: 'ist-gauge-tick ist-gauge-tick-major' }));
            var text = svgEl('text', { x: pLabel.x.toFixed(1), y: pLabel.y.toFixed(1), class: 'ist-gauge-label', 'text-anchor': 'middle', 'dominant-baseline': 'middle' });
            text.textContent = mbps >= 1000 ? '1G' : (mbps >= 1 ? mbps + '' : '.1');
            svg.appendChild(text);
        });
        for (var d = -135; d <= 135; d += 15) {
            var hasLabel = tickLabels.some(function(m) { return Math.abs(speedToDeg(m) - d) < 5; });
            if (!hasLabel) {
                var pO = gaugePoint(d, R - 4), pI = gaugePoint(d, R - 10);
                svg.appendChild(svgEl('line', { x1: pO.x.toFixed(1), y1: pO.y.toFixed(1), x2: pI.x.toFixed(1), y2: pI.y.toFixed(1), class: 'ist-gauge-tick' }));
            }
        }

        // Progress arc
        var progressArc = svgEl('path', {
            id: 'ist-ul-gauge-progress-arc',
            d: arcPath(-135, 135, R),
            class: 'ist-gauge-progress',
            'stroke': 'url(#istUlGaugeGrad)',
            'stroke-dasharray': TRACK_LEN.toFixed(1),
            'stroke-dashoffset': TRACK_LEN.toFixed(1),
        });
        svg.appendChild(progressArc);
        _ulProgressEl = progressArc;

        // Peak marker
        var peakP1 = gaugePoint(-135, R + 4), peakP2 = gaugePoint(-135, R - 18);
        var peakLine = svgEl('line', {
            id: 'ist-ul-gauge-peak',
            x1: peakP1.x.toFixed(1), y1: peakP1.y.toFixed(1),
            x2: peakP2.x.toFixed(1), y2: peakP2.y.toFixed(1),
            class: 'ist-gauge-peak',
        });
        svg.appendChild(peakLine);
        _ulPeakEl = peakLine;

        // Needle — SVG transform attr (mobile-safe)
        var needleG = svgEl('g', { id: 'ist-ul-gauge-needle', class: 'ist-gauge-needle',
            transform: 'rotate(' + CFG.gauge.MIN_DEG + ',' + CX + ',' + CY + ')' });
        needleG.appendChild(svgEl('polygon', {
            points: CX + ',' + (CY - (R - 10)) + ' ' + (CX - 3.5) + ',' + CY + ' ' + (CX + 3.5) + ',' + CY,
            fill: 'var(--color-success)',
        }));
        needleG.appendChild(svgEl('circle', { cx: CX, cy: CY, r: 8, fill: 'var(--color-surface)', stroke: 'var(--color-success)', 'stroke-width': '2' }));
        svg.appendChild(needleG);
        _ulNeedleEl = needleG;
    }

    function animateUploadGauge(targetMbps) {
        if (_ulRafId) { cancelAnimationFrame(_ulRafId); _ulRafId = null; }
        var fromDeg   = _ulCurrentDeg;
        var targetDeg = speedToDeg(targetMbps);
        var startTime = performance.now();
        var dur = 150;

        function frame(now) {
            var t   = Math.min((now - startTime) / dur, 1);
            var deg = fromDeg + (targetDeg - fromDeg) * (1 - Math.pow(1 - t, 3));
            deg += (t < 0.95 && targetMbps > 0) ? (Math.random() - 0.5) * 3 : 0;
            deg  = Math.max(CFG.gauge.MIN_DEG, Math.min(CFG.gauge.MAX_DEG, deg));
            _ulCurrentDeg = deg;

            if (_ulNeedleEl)   _ulNeedleEl.setAttribute('transform', 'rotate(' + deg + ',' + CFG.gauge.CX + ',' + CFG.gauge.CY + ')');
            if (_ulProgressEl) {
                var span  = (deg - CFG.gauge.MIN_DEG) / (CFG.gauge.MAX_DEG - CFG.gauge.MIN_DEG);
                _ulProgressEl.setAttribute('stroke-dashoffset', (TRACK_LEN - span * TRACK_LEN).toFixed(2));
            }

            var dispMbps = ((deg - CFG.gauge.MIN_DEG) / (CFG.gauge.MAX_DEG - CFG.gauge.MIN_DEG));
            var lo = Math.log10(0.1), hi = Math.log10(CFG.gauge.MAX_MBPS);
            var mbpsDisplay = Math.max(0, Math.pow(10, lo + dispMbps * (hi - lo)));
            var ulVal = $$('#ist-ul-readout-value');
            if (ulVal) ulVal.textContent = mbpsDisplay >= 10 ? mbpsDisplay.toFixed(0) : mbpsDisplay.toFixed(1);

            if (t < 1) { _ulRafId = requestAnimationFrame(frame); }
        }
        _ulRafId = requestAnimationFrame(frame);
    }

    function resetUploadGauge() {
        if (_ulRafId) { cancelAnimationFrame(_ulRafId); _ulRafId = null; }
        _ulCurrentDeg = CFG.gauge.MIN_DEG;
        if (_ulNeedleEl)   _ulNeedleEl.setAttribute('transform', 'rotate(' + CFG.gauge.MIN_DEG + ',' + CFG.gauge.CX + ',' + CFG.gauge.CY + ')');
        if (_ulProgressEl) _ulProgressEl.setAttribute('stroke-dashoffset', TRACK_LEN.toFixed(2));
        if (_ulPeakEl)     _ulPeakEl.style.opacity = '0';
        setUploadReadout('0', 'MBPS', 'WAITING');
    }

    function setUploadReadout(val, unit, phase) {
        var ve = $$('#ist-ul-readout-value'), ue = $$('#ist-ul-readout-unit'), pe = $$('#ist-ul-readout-phase');
        if (ve) ve.textContent = val;
        if (ue && unit !== undefined) ue.textContent = unit;
        if (pe && phase !== undefined) pe.textContent = phase;
    }

    function setUploadGaugePeak(mbps) {
        if (!_ulPeakEl) return;
        var deg = speedToDeg(mbps);
        var p1 = gaugePoint(deg, CFG.gauge.R + 4), p2 = gaugePoint(deg, CFG.gauge.R - 18);
        _ulPeakEl.setAttribute('x1', p1.x.toFixed(1)); _ulPeakEl.setAttribute('y1', p1.y.toFixed(1));
        _ulPeakEl.setAttribute('x2', p2.x.toFixed(1)); _ulPeakEl.setAttribute('y2', p2.y.toFixed(1));
        _ulPeakEl.style.opacity = '1';
    }

    function showUploadGauge() {
        var wrap = $$('#ist-upload-gauge-wrap');
        var area = $$('#ist-dual-gauge-area');
        var dlLabel = $$('#ist-dl-gauge-label');
        if (!_ulInitialized) {
            initUploadGauge();
            // Apply the calibrated scale immediately after init if it changed from default
            if (CFG.gauge.MAX_MBPS !== 1000) {
                rescaleUploadGaugeLabels(CFG.gauge.MAX_MBPS);
            }
        }
        if (wrap) { wrap.style.display = ''; wrap.removeAttribute('aria-hidden'); }
        if (area) area.classList.add('ist-has-upload');
        if (dlLabel) dlLabel.textContent = 'Download';
        setUploadReadout('0', 'MBPS', 'UPLOAD');
    }

    function hideUploadGauge() {
        var wrap = $$('#ist-upload-gauge-wrap');
        var area = $$('#ist-dual-gauge-area');
        var dlLabel = $$('#ist-dl-gauge-label');
        if (wrap) { wrap.style.display = 'none'; wrap.setAttribute('aria-hidden', 'true'); }
        if (area) area.classList.remove('ist-has-upload');
        if (dlLabel) dlLabel.textContent = 'Speed';
    }

    /* ════════════════════════════════════════════════════════════════
     * Terminal / Logging
     * ════════════════════════════════════════════════════════════════ */
    function log(msg, level) {
        level = level || 'info';
        var now  = new Date();
        var time = now.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
        var entry = { ts: now, time: time, msg: msg, level: level };
        state.termLogs.push(entry);

        if (!state.termPaused) _renderLogEntry(entry);
        if (state.termSearch || state.termFilter !== 'all') _applyTermFilters();
    }

    function _renderLogEntry(entry) {
        var body = $$('#ist-terminal-body');
        if (!body) return;

        var shouldShow = (state.termFilter === 'all' || state.termFilter === entry.level) &&
            (!state.termSearch || entry.msg.toLowerCase().includes(state.termSearch.toLowerCase()));
        if (!shouldShow) return;

        var div = document.createElement('div');
        div.className = 'ist-log-line';
        div.dataset.level = entry.level;

        var timeSpan  = document.createElement('span');
        timeSpan.className = 'ist-log-time';
        timeSpan.textContent = entry.time;

        var lvlSpan   = document.createElement('span');
        lvlSpan.className = 'ist-log-level ' + entry.level;
        lvlSpan.textContent = entry.level.toUpperCase().slice(0, 4);

        var msgSpan   = document.createElement('span');
        msgSpan.className = 'ist-log-msg' + (entry.level === 'phase' || entry.level === 'success' ? ' highlight' : '');
        msgSpan.textContent = entry.msg;

        div.appendChild(timeSpan);
        div.appendChild(lvlSpan);
        div.appendChild(msgSpan);
        body.appendChild(div);

        // Auto-scroll
        if (!state.termPaused) { body.scrollTop = body.scrollHeight; }
    }

    function _applyTermFilters() {
        var body = $$('#ist-terminal-body');
        if (!body) return;
        body.innerHTML = '';
        state.termLogs.forEach(function(e) { _renderLogEntry(e); });
    }

    /* ════════════════════════════════════════════════════════════════
     * Phase progress
     * ════════════════════════════════════════════════════════════════ */
    var PHASE_IDS = ['env', 'latency', 'dns', 'pktloss', 'download', 'upload', 'stability', 'scoring', 'report'];

    function setPhase(id, status, pct) {
        var item = $$('#ist-phase-' + id);
        if (!item) return;
        item.className = 'ist-phase-item ' + (status || '');
        var fill = item.querySelector('.ist-phase-bar-fill');
        if (fill) fill.style.width = (pct != null ? pct : (status === 'done' ? 100 : (status === 'running' ? 50 : 0))) + '%';
        var statusEl = item.querySelector('.ist-phase-status');
        if (statusEl) statusEl.textContent = status === 'done' ? 'Done' : (status === 'running' ? 'Running' : (status === 'error' ? 'Error' : 'Pending'));
        var ind = item.querySelector('.ist-phase-indicator');
        if (ind) {
            ind.innerHTML = status === 'done' ? '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>' :
                (status === 'error' ? '!' : (status === 'running' ? '<div class="ist-spinner"></div>' : ind.textContent));
        }
    }

    function resetPhases() {
        PHASE_IDS.forEach(function(id) { setPhase(id, ''); });
    }

    /* ════════════════════════════════════════════════════════════════
     * Phase live indicator (shown under start button during test)
     * ════════════════════════════════════════════════════════════════ */
    var PHASE_LABELS = {
        env:       'Environment Detection',
        latency:   'Latency & Jitter',
        dns:       'DNS Resolution',
        pktloss:   'Packet Loss',
        download:  'Download Speed',
        upload:    'Upload Speed',
        stability: 'Connection Stability',
        scoring:   'Quality Analysis',
        report:    'Summary Report',
    };

    function showPhaseIndicator(phaseId) {
        var wrap = $$('#ist-phase-live');
        if (!wrap) return;
        var idx = PHASE_IDS.indexOf(phaseId) + 1;
        var label = PHASE_LABELS[phaseId] || phaseId;
        wrap.style.display = 'flex';
        wrap.innerHTML =
            '<span class="ist-phase-live-left">' +
            '<div class="ist-phase-live-dot"></div>' +
            '<span class="ist-phase-live-text">' + label + '</span>' +
            '</span>' +
            '<span class="ist-phase-live-count"><strong>' + idx + '</strong><span class="ist-phase-live-total">/' + PHASE_IDS.length + '</span></span>';
    }

    function hidePhaseIndicator() {
        var wrap = $$('#ist-phase-live');
        if (wrap) wrap.style.display = 'none';
    }

    function showPhasesAccordion(autoCollapse) {
        var acc = $$('#ist-phases-accordion');
        if (!acc) return;
        acc.style.display = 'block';
        // Auto-expand
        _setPhasesAccordionOpen(true);
        if (autoCollapse) {
            setTimeout(function() { _setPhasesAccordionOpen(false); }, 2000);
        }
    }

    function _setPhasesAccordionOpen(open) {
        var body   = $$('#ist-phases-accordion-body');
        var btnLbl = $$('#ist-phases-accordion-btn-label');
        if (body)   body.style.display = open ? 'block' : 'none';
        if (btnLbl) btnLbl.textContent  = open ? 'Collapse' : 'Expand';
        var acc = $$('#ist-phases-accordion');
        if (acc) acc.classList.toggle('ist-phases-accordion-open', open);
    }

    function togglePhasesAccordion() {
        var body = $$('#ist-phases-accordion-body');
        if (!body) return;
        var isOpen = body.style.display !== 'none';
        _setPhasesAccordionOpen(!isOpen);
    }

    /* ════════════════════════════════════════════════════════════════
     * KPI card updater
     * ════════════════════════════════════════════════════════════════ */
    function revealSection(el) {
        if (!el) return;
        el.classList.remove('ist-hidden');
        var p = el.parentElement;
        while (p && !p.classList.contains('ist-app')) {
            if (p.classList.contains('ist-hidden')) p.classList.remove('ist-hidden');
            p = p.parentElement;
        }
    }

    function updateKPI(id, val, unit, badge, badgeClass) {
        var card = $$('#ist-kpi-' + id);
        if (!card) return;
        if (val != null && val !== '—') revealSection(card);
        var valEl  = card.querySelector('.ist-kpi-value');
        var unitEl = card.querySelector('.ist-kpi-unit');
        var badgeEl= card.querySelector('.ist-kpi-badge');
        if (valEl) {
            var txt = val != null ? String(val) : '—';
            if (valEl.firstChild && valEl.firstChild.nodeType === 3) {
                valEl.firstChild.textContent = txt;
            } else {
                valEl.textContent = txt;
            }
        }
        if (unitEl && unit !== undefined) unitEl.textContent = unit ? ' ' + unit : '';
        if (badgeEl && badge !== undefined) { badgeEl.textContent = badge; badgeEl.className = 'ist-kpi-badge ' + (badgeClass || ''); }
    }

    /* Update the hero stat numbers displayed left/right of the gauge */
    function updateHeroStat(id, val, unit) {
        var e = $$('#ist-hero-' + id);
        if (!e) return;
        var unitSpan = unit ? '<span style="font-size:14px;font-weight:400;color:var(--color-text-muted);margin-left:3px;">' + unit + '</span>' : '';
        e.innerHTML = (val != null ? String(val) : '—') + unitSpan;
    }

    /* Reset ALL result-dependent UI back to empty state (called on every test start) */
    function resetAllResults() {
        var kpiIds = ['dl','ul','ping','jitter','loss','dns','tls','ttfb','http','stability','overall','conn','peak-dl','peak-ul'];
        kpiIds.forEach(function(id) { updateKPI(id, null, '', '—', ''); });

        // Re-hide all progressive-reveal sections so next test starts clean
        ['#ist-kpi-section','#ist-platform-section','#ist-charts-section',
         '#ist-terminal-section','#ist-report-center',
         '#ist-recommendations-section','#ist-export-section'].forEach(function(sel) {
            var el = $$(sel);
            if (el) el.classList.add('ist-hidden');
        });
        // Re-hide individual KPI cards so they reveal one-by-one next run
        $$$('.ist-kpi-card').forEach(function(c) { c.classList.add('ist-hidden'); });

        // Reset hero stat values and hide columns
        var heroUnits = { ping: 'ms', jitter: 'ms', dl: 'Mbps', ul: 'Mbps' };
        Object.keys(heroUnits).forEach(function(id) {
            var e = $$('#ist-hero-' + id);
            if (e) e.innerHTML = '—<span style="font-size:14px;font-weight:400;color:var(--color-text-muted);margin-left:3px;">' + heroUnits[id] + '</span>';
        });
        $$('.ist-hero-left')  && $$('.ist-hero-left').classList.add('ist-hero-stats-hidden');
        $$('.ist-hero-right') && $$('.ist-hero-right').classList.add('ist-hero-stats-hidden');

        // Hide grade in header
        var gradeWrapHdr = $$('#ist-meta-grade-wrap');
        if (gradeWrapHdr) gradeWrapHdr.style.display = 'none';
        var acc = $$('#ist-phases-accordion');
        if (acc) acc.style.display = 'none';

        // Hide and reset the upload gauge — it reappears fresh each test run
        hideUploadGauge();
        resetUploadGauge();

        // Reset scores grid to empty state
        var sg = $$('#ist-scores-grid');
        if (sg) sg.innerHTML = '<div class="ist-empty-state"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg><h3>No Results Yet</h3><p>Run a test to generate quality scores for gaming, streaming, video calls, and more.</p></div>';

        // Reset recommendations to empty state
        var recEl = $$('#ist-recommendations');
        if (recEl) recEl.innerHTML = '<div class="ist-empty-state" style="padding:24px;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg><h3>Awaiting Test Results</h3><p>Recommendations will appear after the diagnostic suite completes.</p></div>';

        // Reset all sparklines to flat line
        $$$('.ist-spark-path').forEach(function(pl) { pl.setAttribute('points', '0,14 80,14'); });

        // Clear chart data and re-render
        Object.keys(CHART_DATA).forEach(function(k) { CHART_DATA[k] = []; });
        Object.keys(CHART_DATA).forEach(function(k) { renderChart(k); });
    }

    function rateMetric(type, val) {
        var t = CFG.thresholds[type];
        if (!t || val == null) return { badge: '—', cls: '' };
        var inverted = (type === 'ping' || type === 'jitter' || type === 'loss');
        var isGood = inverted ? val <= t.excellent : val >= t.excellent;
        var isFair = inverted ? val <= t.good      : val >= t.good;
        if (isGood) return { badge: 'Excellent', cls: 'good' };
        if (isFair) return { badge: 'Good',      cls: 'good' };
        var isOk   = inverted ? val <= t.fair      : val >= t.fair;
        if (isOk)   return { badge: 'Fair',       cls: 'warn' };
        return { badge: 'Poor', cls: 'bad' };
    }

    /* ════════════════════════════════════════════════════════════════
     * Sparklines
     * ════════════════════════════════════════════════════════════════ */
    function updateSparkline(id, data) {
        var svg = $$('#ist-spark-' + id);
        if (!svg || !data || data.length < 2) return;
        var w = 80, h = 28;
        var mn = Math.min.apply(null, data), mx = Math.max.apply(null, data);
        var range = mx - mn || 1;
        var pts = data.map(function(v, i) {
            return (i / (data.length - 1) * w).toFixed(1) + ',' + ((1 - (v - mn) / range) * h).toFixed(1);
        }).join(' ');
        var path = svg.querySelector('.ist-spark-path');
        if (!path) {
            path = svgEl('polyline', { class: 'ist-spark-path', points: pts });
            svg.appendChild(path);
        } else {
            path.setAttribute('points', pts);
        }
    }

    /* ════════════════════════════════════════════════════════════════
     * SVG Charts
     * ════════════════════════════════════════════════════════════════ */
    var CHART_DATA = {
        download: [], upload: [], ping: [], loaded_ping: [], jitter: [], loss: [], realtime: [],
    };

    function renderChart(chartId) {
        var container = $$('#ist-chart-' + chartId);
        if (!container) return;
        var data = CHART_DATA[chartId] || [];
        var svg  = container.querySelector('svg');
        if (!svg) return;

        var W = 640, H = 160;
        svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
        svg.innerHTML = '';

        // Defs
        var defs = svgEl('defs');
        var ag = svgEl('linearGradient', { id: 'chartGrad-' + chartId, x1: '0%', y1: '0%', x2: '0%', y2: '100%' });
        [['0%', '0.35'], ['100%', '0']].forEach(function(s) {
            var stop = svgEl('stop', { offset: s[0], 'stop-color': 'var(--color-primary)', 'stop-opacity': s[1] });
            ag.appendChild(stop);
        });
        defs.appendChild(ag);
        svg.appendChild(defs);

        if (!data || data.length < 2) {
            var txt = svgEl('text', { x: W / 2, y: H / 2, 'text-anchor': 'middle', 'dominant-baseline': 'middle', class: 'ist-chart-axis', opacity: '0.5' });
            txt.textContent = 'Awaiting data...';
            svg.appendChild(txt);
            return;
        }

        // Always start Y-axis from 0; add 15% headroom above the maximum
        var mn    = 0;
        var rawMx = Math.max.apply(null, data);
        var mx    = rawMx > 0 ? rawMx * 1.15 : 1;
        var range = mx;   // mn is 0, so range === mx
        var pad  = { t: 12, r: 12, b: 24, l: 36 };
        var cW   = W - pad.l - pad.r;
        var cH   = H - pad.t - pad.b;
        var avg  = data.reduce(function(a, b) { return a + b; }, 0) / data.length;
        var peak = rawMx;

        function toX(i) { return pad.l + (i / (data.length - 1)) * cW; }
        function toY(v) { return pad.t + (1 - v / range) * cH; }

        // Grid lines (4 horizontal)
        for (var g = 0; g <= 4; g++) {
            var gy = pad.t + (g / 4) * cH;
            var gv = ((4 - g) / 4) * range;
            var gridL = svgEl('line', { x1: pad.l, y1: gy, x2: W - pad.r, y2: gy, class: 'ist-chart-grid', opacity: '0.5' });
            svg.appendChild(gridL);
            var gLabel = svgEl('text', { x: pad.l - 4, y: gy, 'text-anchor': 'end', 'dominant-baseline': 'middle', class: 'ist-chart-axis' });
            gLabel.textContent = gv.toFixed(gv < 10 ? 1 : 0);
            svg.appendChild(gLabel);
        }

        // Area fill
        var areaPoints = 'M ' + toX(0).toFixed(1) + ' ' + (pad.t + cH).toFixed(1);
        data.forEach(function(v, i) { areaPoints += ' L ' + toX(i).toFixed(1) + ' ' + toY(v).toFixed(1); });
        areaPoints += ' L ' + toX(data.length - 1).toFixed(1) + ' ' + (pad.t + cH).toFixed(1) + ' Z';
        svg.appendChild(svgEl('path', { d: areaPoints, fill: 'url(#chartGrad-' + chartId + ')' }));

        // Line path
        var pts = data.map(function(v, i) { return toX(i).toFixed(1) + ',' + toY(v).toFixed(1); }).join(' L ');
        svg.appendChild(svgEl('path', { d: 'M ' + pts, class: 'ist-chart-line' }));

        // Average line
        var avgY = toY(avg).toFixed(1);
        svg.appendChild(svgEl('line', { x1: pad.l, y1: avgY, x2: W - pad.r, y2: avgY, class: 'ist-chart-avg' }));

        // Peak line
        var peakY = toY(peak).toFixed(1);
        svg.appendChild(svgEl('line', { x1: pad.l, y1: peakY, x2: W - pad.r, y2: peakY, class: 'ist-chart-peak' }));

        // Dots for last data point
        var lx = toX(data.length - 1), ly = toY(data[data.length - 1]);
        svg.appendChild(svgEl('circle', { cx: lx, cy: ly, r: 3, fill: 'var(--color-primary)' }));
    }

    function addChartPoint(chartId, val) {
        if (!CHART_DATA[chartId]) CHART_DATA[chartId] = [];
        CHART_DATA[chartId].push(val);
        if (CHART_DATA[chartId].length > CFG.chartMaxPoints) CHART_DATA[chartId].shift();
        if ($$('#ist-chart-pane-' + chartId + '.active') || $$('#ist-chart-pane-realtime.active')) {
            renderChart(chartId);
        }
    }

    /* ════════════════════════════════════════════════════════════════
     * Network fetch helpers
     * ════════════════════════════════════════════════════════════════ */
    function _pingWarm() {
        return new Promise(function(resolve) {
            var url = CFG.api + '?action=ping&_=' + Date.now();
            var t0  = performance.now();
            fetch(url, { method: 'HEAD', cache: 'no-store' })
                .then(function() {
                    var entries = performance.getEntriesByName ? performance.getEntriesByName(url) : [];
                    var entry   = entries.length ? entries[entries.length - 1] : null;
                    var rtt = (entry && entry.responseEnd > 0 && entry.requestStart > 0)
                        ? entry.responseEnd - entry.requestStart
                        : performance.now() - t0;
                    resolve(rtt);
                })
                .catch(function() { resolve(null); });
        });
    }

    function _pingFallback() {
        return new Promise(function(resolve) {
            var t0 = performance.now();
            fetch(CFG.api + '?action=ping&_=' + Date.now(), { method: 'HEAD', cache: 'no-store' })
                .then(function() { resolve(performance.now() - t0); })
                .catch(function() { resolve(null); });
        });
    }

    function _pingTimeout(ms) {
        return new Promise(function(resolve) {
            var ac  = new AbortController();
            var url = CFG.api + '?action=ping&_=' + Date.now();
            var t0  = performance.now();
            var tid = setTimeout(function() { ac.abort(); resolve(null); }, ms);
            fetch(url, { method: 'HEAD', cache: 'no-store', signal: ac.signal })
                .then(function() {
                    clearTimeout(tid);
                    var entries = performance.getEntriesByName ? performance.getEntriesByName(url) : [];
                    var entry   = entries.length ? entries[entries.length - 1] : null;
                    var rtt = (entry && entry.responseEnd > 0 && entry.requestStart > 0)
                        ? entry.responseEnd - entry.requestStart
                        : performance.now() - t0;
                    resolve(rtt);
                })
                .catch(function() { clearTimeout(tid); resolve(null); });
        });
    }

    function _sleep(ms) { return new Promise(function(r) { setTimeout(r, ms); }); }

    /* ════════════════════════════════════════════════════════════════
     * Individual test functions
     * ════════════════════════════════════════════════════════════════ */
    async function measurePing() {
        var pings = [];
        var WARM_COUNT = 3;
        log('Starting latency measurement — ' + WARM_COUNT + ' warm-up probes + ' + CFG.test.PING_COUNT + ' timed probes...', 'debug');
        setPhase('latency', 'running', 5);

        // Pre-warm: establish TCP/TLS connection before timing
        for (var w = 0; w < WARM_COUNT; w++) {
            if (state.cancelled) break;
            await _pingWarm();
            await _sleep(40);
        }

        // Timed probes
        for (var i = 0; i < CFG.test.PING_COUNT; i++) {
            if (state.cancelled) break;
            var rtt = await _pingFallback();
            if (rtt !== null) {
                pings.push(rtt);
                state.samples.ping.push(rtt);
                addChartPoint('ping', rtt);
                log('Probe ' + (i + 1) + '/' + CFG.test.PING_COUNT + ' → ' + rtt.toFixed(1) + ' ms', 'debug');
                var pr = rateMetric('ping', rtt);
                updateKPI('ping', rtt.toFixed(1), 'ms', pr.badge, pr.cls);
                updateHeroStat('ping', rtt.toFixed(1), 'ms');

                if (pings.length >= 2) {
                    var runJit = 0;
                    for (var ji = 1; ji < pings.length; ji++) runJit += Math.abs(pings[ji] - pings[ji - 1]);
                    runJit /= (pings.length - 1);
                    addChartPoint('jitter', runJit);
                    var jr2 = rateMetric('jitter', runJit);
                    updateKPI('jitter', runJit.toFixed(1), 'ms', jr2.badge, jr2.cls);
                    updateHeroStat('jitter', runJit.toFixed(1), 'ms');
                }
            } else {
                log('Probe ' + (i + 1) + ' — timeout', 'warn');
            }
            await _sleep(CFG.test.PING_INTERVAL);
            setPhase('latency', 'running', Math.round((i + 1) / CFG.test.PING_COUNT * 100));
        }

        if (!pings.length) return null;

        var avg  = pings.reduce(function(a, b) { return a + b; }, 0) / pings.length;
        var min  = Math.min.apply(null, pings);
        var max  = Math.max.apply(null, pings);

        var diffs = [];
        for (var j = 1; j < pings.length; j++) diffs.push(Math.abs(pings[j] - pings[j - 1]));
        var jitter = diffs.length ? diffs.reduce(function(a, b) { return a + b; }, 0) / diffs.length : 0;

        var r = { ping: avg, min_ping: min, max_ping: max, jitter: jitter, samples: pings };
        var pr = rateMetric('ping', avg);
        var jr = rateMetric('jitter', jitter);
        updateKPI('ping',   avg.toFixed(1),    'ms', pr.badge, pr.cls);
        updateKPI('jitter', jitter.toFixed(1), 'ms', jr.badge, jr.cls);
        updateSparkline('ping', state.samples.ping.slice(-20));
        state.samples.jitter.push(jitter);
        addChartPoint('jitter', jitter);
        updateHeroStat('ping',   avg.toFixed(1),    'ms');
        updateHeroStat('jitter', jitter.toFixed(1), 'ms');
        setPhase('latency', 'done');
        log('Latency avg=' + avg.toFixed(1) + 'ms  jitter=' + jitter.toFixed(1) + 'ms  min=' + min.toFixed(1) + '  max=' + max.toFixed(1), 'success');
        return r;
    }

    async function measurePacketLoss() {
        var TOTAL  = CFG.test.PKT_LOSS_PINGS;
        var BATCH  = 5;
        var TIMEOUT = CFG.test.PKT_LOSS_TIMEOUT || 600;
        log('Estimating packet loss — ' + TOTAL + ' probes in batches of ' + BATCH + '...', 'debug');
        setPhase('pktloss', 'running', 10);

        var sent = 0, received = 0;
        var batches = Math.ceil(TOTAL / BATCH);

        for (var b = 0; b < batches; b++) {
            if (state.cancelled) break;
            var batchSize = Math.min(BATCH, TOTAL - b * BATCH);
            var promises  = [];
            for (var k = 0; k < batchSize; k++) promises.push(_pingTimeout(TIMEOUT));
            sent += batchSize;
            var results = await Promise.all(promises);
            results.forEach(function(r) { if (r !== null) received++; });

            var runLoss = ((sent - received) / sent) * 100;
            var rlr = rateMetric('loss', runLoss);
            updateKPI('loss', runLoss.toFixed(1), '%', rlr.badge, rlr.cls);
            addChartPoint('loss', runLoss);
            state.samples.loss.push(runLoss);
            setPhase('pktloss', 'running', Math.round((b + 1) / batches * 100));
            if (b < batches - 1) await _sleep(80);
        }

        var loss = sent > 0 ? ((sent - received) / sent) * 100 : 0;
        var lr   = rateMetric('loss', loss);
        updateKPI('loss', loss.toFixed(1), '%', lr.badge, lr.cls);
        setPhase('pktloss', 'done');
        log('Packet loss: ' + loss.toFixed(1) + '% (' + (sent - received) + '/' + sent + ' dropped)', loss > 2 ? 'warn' : 'success');
        return { packet_loss: loss, sent: sent, received: received };
    }

    async function measureDownload() {
        var T = CFG.test;
        log('Starting download — ' + T.DL_STREAMS + ' parallel streams × ' + (T.DL_DURATION / 1000).toFixed(0) + 's window...', 'phase');
        setPhase('download', 'running', 5);

        var t0           = performance.now();
        var warmupEnd    = t0 + T.DL_WARMUP;
        var measureStart = 0;    // set on first post-warmup byte
        var totalBytes   = 0;    // bytes counted after warmup
        var warmupBytes  = 0;    // bytes during warmup (used for inline calibration)
        var peakMbps     = 0;
        var lastPeak     = 0;
        var liveSnapshots = [];
        // Windowed chart sampling (instantaneous rate, not cumulative)
        var chartBytes   = 0;
        var chartTime    = 0;

        // One AbortController per stream for hard cancellation
        var controllers = [];
        for (var ci = 0; ci < T.DL_STREAMS; ci++) controllers.push(new AbortController());

        // Stop all streams when the measurement window ends
        var hardStop = setTimeout(function() {
            controllers.forEach(function(c) { try { c.abort(); } catch (e) {} });
        }, T.DL_DURATION);

        function onBytes(bytes) {
            var now = performance.now();
            if (now < warmupEnd) {
                // Warmup: use accumulated bytes for gauge calibration
                warmupBytes += bytes;
                var warmupElapsed = (now - t0) / 1000;
                if (warmupElapsed > 0.2) {
                    var warmupMbps = (warmupBytes * 8) / warmupElapsed / 1e6;
                    maybeRescaleForSpeed(warmupMbps);
                }
                return;
            }
            // First post-warmup byte — record precise start time
            if (measureStart === 0) measureStart = performance.now();
            totalBytes += bytes;
            var elapsed = (now - measureStart) / 1000;
            if (elapsed > 0.1) {
                var liveMbps = (totalBytes * 8) / elapsed / 1e6;
                // Auto-rescale gauge mid-test if speed exceeds current max
                maybeRescaleForSpeed(liveMbps);
                if (liveMbps > peakMbps) {
                    peakMbps = liveMbps;
                    setGaugePeak(peakMbps);
                    updateKPI('peak-dl', peakMbps.toFixed(1), 'Mbps');
                }
                animateGauge(liveMbps, 'DOWNLOAD');
                setReadout(liveMbps >= 10 ? liveMbps.toFixed(0) : liveMbps.toFixed(1), 'MBPS', 'DOWNLOAD');
                // Windowed instantaneous rate for chart (every ~300 ms window)
                if (chartTime === 0) {
                    chartTime  = now;
                    chartBytes = totalBytes;
                } else if (now - chartTime >= 300) {
                    var windowSecs = (now - chartTime) / 1000;
                    var instMbps   = ((totalBytes - chartBytes) * 8) / windowSecs / 1e6;
                    addChartPoint('download', instMbps);
                    addChartPoint('realtime', instMbps);
                    chartTime  = now;
                    chartBytes = totalBytes;
                }
                // Sample for sparkline
                if (now - lastPeak > 500) {
                    liveSnapshots.push(liveMbps);
                    state.samples.download.push(liveMbps);
                    updateSparkline('dl', state.samples.download.slice(-20));
                    lastPeak = now;
                    // Phase progress: 0→100 over the measurement window (after warmup)
                    var pct = Math.min(98, Math.round((now - warmupEnd) / (T.DL_DURATION - T.DL_WARMUP) * 95));
                    setPhase('download', 'running', pct);
                }
            }
        }

        // Background loaded-latency probe: measures RTT while download is saturating
        var _ldProbeActive = true;
        (function startLoadedProbe() {
            function probe() {
                if (!_ldProbeActive || state.cancelled) return;
                var t0p = performance.now();
                fetch(CFG.api + '?action=ping&_=' + Date.now(), { method: 'HEAD', cache: 'no-store' })
                    .then(function() {
                        if (!_ldProbeActive) return;
                        var rtt = performance.now() - t0p;
                        addChartPoint('loaded_ping', rtt);
                    })
                    .catch(function() {})
                    .then(function() {
                        if (_ldProbeActive && !state.cancelled) setTimeout(probe, 1000);
                    });
            }
            setTimeout(probe, 600);
        })();

        // Launch N parallel streams — each with its own AbortController
        var streamPromises = [];
        for (var s = 0; s < T.DL_STREAMS; s++) {
            (function(streamIdx) {
                var url = CFG.api + '?action=download_stream&duration=' + T.DL_SERVER_DURATION + '&s=' + streamIdx + '&_=' + (Date.now() + streamIdx);
                var ctrl = controllers[streamIdx];
                var p = fetch(url, { cache: 'no-store', signal: ctrl.signal })
                    .then(function(res) {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        var reader = res.body.getReader();
                        function pump() {
                            return reader.read().then(function(chunk) {
                                if (chunk.done) return;
                                onBytes(chunk.value.byteLength);
                                return pump();
                            });
                        }
                        return pump();
                    })
                    .catch(function(e) {
                        if (e.name !== 'AbortError') log('Stream ' + streamIdx + ' error: ' + e.message, 'warn');
                    });
                streamPromises.push(p);
            })(s);
        }

        await Promise.all(streamPromises);
        clearTimeout(hardStop);
        _ldProbeActive = false;

        // Compute final result using actual measured time (not nominal window)
        var actualElapsed = measureStart > 0 ? (performance.now() - measureStart) / 1000 : (T.DL_DURATION - T.DL_WARMUP) / 1000;
        var avgMbps = (totalBytes * 8) / Math.max(0.5, actualElapsed) / 1e6;

        if (totalBytes === 0) { setPhase('download', 'error'); return null; }

        var dr = rateMetric('download', avgMbps);
        updateKPI('dl', avgMbps.toFixed(1), 'Mbps', dr.badge, dr.cls);
        updateKPI('peak-dl', peakMbps.toFixed(1), 'Mbps');
        updateHeroStat('dl', avgMbps.toFixed(1), 'Mbps');
        setPhase('download', 'done');
        log('Download complete — avg=' + avgMbps.toFixed(1) + ' Mbps  peak=' + peakMbps.toFixed(1) + ' Mbps  total=' + (totalBytes / 1048576).toFixed(1) + ' MB', 'success');
        return { download: avgMbps, peak_download: peakMbps };
    }

    async function measureUpload() {
        var T = CFG.test;
        log('Starting upload — ' + T.UL_STREAMS + ' parallel streams × ' + (T.UL_BLOB_SIZE / 1048576).toFixed(0) + ' MB blobs...', 'phase');
        setPhase('upload', 'running', 5);

        // Pre-generate one random blob shared across all streams
        // Use crypto.getRandomValues (64 KB chunks) — much faster than Math.random() loop
        var buf = new Uint8Array(T.UL_BLOB_SIZE);
        var cryptoChunk = 65536;
        for (var fi = 0; fi < buf.length; fi += cryptoChunk) {
            crypto.getRandomValues(buf.subarray(fi, Math.min(fi + cryptoChunk, buf.length)));
        }
        var blob = new Blob([buf]);

        var t0         = performance.now();
        var deadline   = t0 + T.UL_DURATION;
        var peakMbps   = 0;
        var streamResults   = []; // {bytes, time} per completed stream
        var liveStreamBytes = new Array(T.UL_STREAMS).fill(0); // in-progress bytes per stream
        var lastUpdate = 0;

        function liveUpdate() {
            var now     = performance.now();
            var elapsed = (now - t0) / 1000;
            if (elapsed < 0.5) return;
            // Count completed + in-progress bytes together
            var doneBytes = streamResults.reduce(function(a, r) { return a + r.bytes; }, 0);
            var liveBytes = liveStreamBytes.reduce(function(a, b) { return a + b; }, 0);
            var sentBytes = doneBytes + liveBytes;
            if (sentBytes === 0) return;
            var liveMbps = (sentBytes * 8) / elapsed / 1e6;
            maybeRescaleForSpeed(liveMbps);
            if (liveMbps > peakMbps) {
                peakMbps = liveMbps;
                setUploadGaugePeak(peakMbps);
                updateKPI('peak-ul', peakMbps.toFixed(1), 'Mbps');
            }
            animateUploadGauge(liveMbps);
            setUploadReadout(liveMbps >= 10 ? liveMbps.toFixed(0) : liveMbps.toFixed(1), 'MBPS', 'UPLOAD');
            addChartPoint('upload', liveMbps);
            addChartPoint('realtime', liveMbps);
            state.samples.upload.push(liveMbps);
            updateSparkline('ul', state.samples.upload.slice(-20));
            var pct = Math.min(95, Math.round((now - t0) / T.UL_DURATION * 90));
            setPhase('upload', 'running', pct);
        }

        // One upload stream: sends blobs repeatedly until deadline
        function runStream(streamIdx) {
            var streamBytes = 0;
            var streamT0    = performance.now();

            function sendOne() {
                if (state.cancelled || performance.now() >= deadline) {
                    streamResults.push({ bytes: streamBytes, time: (performance.now() - streamT0) / 1000 });
                    return Promise.resolve();
                }
                return new Promise(function(resolve) {
                    var xhr  = new XMLHttpRequest();
                    var sent = 0;
                    xhr.upload.onprogress = function(ev) {
                        var now = performance.now();
                        var delta = ev.loaded - sent;
                        if (delta > 0) {
                            streamBytes               += delta;
                            liveStreamBytes[streamIdx] = streamBytes;
                            sent = ev.loaded;
                        }
                        if (now - lastUpdate > 300) { liveUpdate(); lastUpdate = now; }
                    };
                    xhr.onload  = function() { liveStreamBytes[streamIdx] = 0; resolve(true); };
                    xhr.onerror = function() { resolve(false); };
                    xhr.ontimeout = function() { resolve(false); };
                    xhr.timeout = 20000;
                    xhr.open('POST', CFG.api + '?action=upload&s=' + streamIdx + '&_=' + Date.now());
                    xhr.send(blob);
                }).then(function(ok) {
                    if (ok && performance.now() < deadline) return sendOne();
                    streamResults.push({ bytes: streamBytes, time: (performance.now() - streamT0) / 1000 });
                });
            }
            return sendOne();
        }

        var promises = [];
        for (var s = 0; s < T.UL_STREAMS; s++) promises.push(runStream(s));
        await Promise.all(promises);

        // Include any bytes still tracked in liveStreamBytes (in-flight at deadline)
        var completedBytes = streamResults.reduce(function(a, r) { return a + r.bytes; }, 0);
        var liveBytes      = liveStreamBytes.reduce(function(a, b) { return a + b; }, 0);
        var totalBytes     = completedBytes + liveBytes;
        var totalTime      = Math.max(0.5, (performance.now() - t0) / 1000);
        if (totalBytes === 0) { setPhase('upload', 'error'); return null; }

        var avgMbps = (totalBytes * 8) / totalTime / 1e6;
        var ur = rateMetric('upload', avgMbps);
        updateKPI('ul', avgMbps.toFixed(1), 'Mbps', ur.badge, ur.cls);
        updateKPI('peak-ul', peakMbps.toFixed(1), 'Mbps');
        updateHeroStat('ul', avgMbps.toFixed(1), 'Mbps');
        setPhase('upload', 'done');
        log('Upload complete — avg=' + avgMbps.toFixed(1) + ' Mbps  peak=' + peakMbps.toFixed(1) + ' Mbps  total=' + (totalBytes / 1048576).toFixed(1) + ' MB', 'success');
        return { upload: avgMbps, peak_upload: peakMbps };
    }

    async function measureEnv() {
        setPhase('env', 'running', 20);
        log('Detecting environment...', 'debug');

        // Browser APIs
        var nav  = navigator;
        var conn = nav.connection || nav.mozConnection || nav.webkitConnection || {};
        var mem  = nav.deviceMemory;
        var cores = nav.hardwareConcurrency;

        var env = {
            browser:         _detectBrowser(),
            user_agent:      nav.userAgent,
            platform:        nav.platform || nav.userAgentData && nav.userAgentData.platform || 'Unknown',
            language:        nav.language,
            online:          nav.onLine,
            connection_type:  conn.type || '—',
            effective_type:   conn.effectiveType || '—',
            downlink:         conn.downlink != null ? conn.downlink + ' Mbps' : '—',
            rtt_est:          conn.rtt != null ? conn.rtt + ' ms' : '—',
            save_data:        conn.saveData ? 'Enabled' : 'Disabled',
            device_memory:    mem ? mem + ' GB' : '—',
            cpu_cores:        cores || '—',
            screen:           screen.width + '×' + screen.height + ' ' + (screen.colorDepth || '') + '-bit',
            pixel_ratio:      window.devicePixelRatio || 1,
            timezone:         Intl && Intl.DateTimeFormat ? Intl.DateTimeFormat().resolvedOptions().timeZone : '—',
            locale:           nav.language,
            color_scheme:     window.matchMedia('(prefers-color-scheme: dark)').matches ? 'Dark' : 'Light',
            touch:            ('ontouchstart' in window) ? 'Yes' : 'No',
            webrtc:           typeof RTCPeerConnection !== 'undefined' ? 'Supported' : 'Not Available',
            webgl:            _checkWebGL(),
            cookie_enabled:   nav.cookieEnabled ? 'Yes' : 'No',
            service_worker:   'serviceWorker' in nav ? 'Supported' : 'Not Available',
            websocket:        typeof WebSocket !== 'undefined' ? 'Supported' : 'Not Available',
        };

        setPhase('env', 'running', 50);

        // Fetch server-side IP + ISP lookup
        try {
            var resp = await fetch(CFG.api + '?action=myip', { cache: 'no-store' });
            var data = await resp.json();
            env.ip           = data.ip;
            env.is_ipv6      = data.is_ipv6;
            env.server       = data.server;
            env.server_ip    = data.server_ip;
            env.ipv6_support = data.ipv6_support;
            env.isp          = data.isp || {};
            log('Client IP: ' + data.ip, 'info');
            if (env.isp && env.isp.isp) {
                log('ISP: ' + env.isp.isp + (env.isp.city ? ' (' + env.isp.city + ', ' + env.isp.country + ')' : ''), 'info');
            }
        } catch (e) {
            env.ip  = 'Unavailable';
            env.isp = {};
            log('IP detection failed: ' + e.message, 'warn');
        }

        setPhase('env', 'done');
        return env;
    }

    async function measureDNS() {
        setPhase('dns', 'running', 20);
        log('Measuring DNS resolution time...', 'debug');

        // Try client-side via ResourceTiming API first (most accurate, no server RTT)
        var clientDns = null;
        try {
            if (window.performance && performance.getEntriesByType) {
                var navEntries = performance.getEntriesByType('navigation');
                if (navEntries.length) {
                    var nav = navEntries[0];
                    var dnsTime = nav.domainLookupEnd - nav.domainLookupStart;
                    if (dnsTime > 0) clientDns = dnsTime;
                }
                // Also check resource entries for any recent fetch
                if (!clientDns) {
                    var res = performance.getEntriesByType('resource');
                    for (var ri = res.length - 1; ri >= 0; ri--) {
                        var re = res[ri];
                        var dt = re.domainLookupEnd - re.domainLookupStart;
                        if (dt > 0) { clientDns = dt; break; }
                    }
                }
            }
        } catch (e) {}

        if (clientDns !== null) {
            setPhase('dns', 'done');
            log('DNS resolution time (client): ' + clientDns.toFixed(1) + ' ms', clientDns > 100 ? 'warn' : 'success');
            updateKPI('dns', clientDns.toFixed(0), 'ms');
            return { dns_ms: clientDns };
        }

        // Fallback: server-side measurement
        try {
            var resp = await fetch(CFG.api + '?action=dns_time', { cache: 'no-store' });
            var data = await resp.json();
            var avg  = data.avg_ms;
            setPhase('dns', 'done');
            log('DNS avg resolution time (server): ' + avg + ' ms', avg > 100 ? 'warn' : 'success');
            updateKPI('dns', avg.toFixed(0), 'ms');
            return { dns_ms: avg, dns_results: data.results };
        } catch (e) {
            setPhase('dns', 'error');
            log('DNS measurement failed: ' + e.message, 'error');
            return null;
        }
    }

    async function warmConnections(count) {
        count = count || 3;
        log('Pre-warming ' + count + ' connections...', 'debug');
        var promises = [];
        for (var i = 0; i < count; i++) {
            (function(idx) {
                var url = CFG.api + '?action=ping&warm=' + idx + '&_=' + (Date.now() + idx);
                promises.push(
                    fetch(url, { method: 'HEAD', cache: 'no-store' }).catch(function() {})
                );
            })(i);
        }
        await Promise.all(promises);
    }

    async function measureHTTPTiming() {
        log('Measuring HTTP response timing...', 'debug');
        // Use Performance API if available
        var timing = {};
        if (window.performance && window.performance.timing) {
            var t = window.performance.timing;
            timing.dns_ms  = t.domainLookupEnd - t.domainLookupStart;
            timing.tcp_ms  = t.connectEnd - t.connectStart;
            // Guard: secureConnectionStart is 0 when connection is not HTTPS or not
            // tracked — subtracting 0 from connectEnd gives a huge epoch timestamp.
            timing.tls_ms  = (t.secureConnectionStart > 0) ? (t.connectEnd - t.secureConnectionStart) : null;
            timing.ttfb_ms = t.responseStart - t.requestStart;
            timing.http_ms = t.responseEnd - t.requestStart;
        }

        // Also measure a live fetch timing
        try {
            var t0   = performance.now();
            var res  = await fetch(CFG.api + '?action=ping&t=http&_=' + Date.now(), { cache: 'no-store' });
            await res.json();
            var liveMs = performance.now() - t0;
            timing.live_rtt_ms = liveMs;
            updateKPI('ttfb', liveMs.toFixed(0), 'ms');
            updateKPI('http', liveMs.toFixed(0), 'ms');
            log('HTTP round-trip: ' + liveMs.toFixed(1) + ' ms', 'debug');
        } catch (e) {}

        if (timing.tls_ms != null && timing.tls_ms > 0) {
            updateKPI('tls', timing.tls_ms.toFixed(0), 'ms');
            log('TLS handshake: ' + timing.tls_ms.toFixed(1) + ' ms', 'debug');
        }
        return timing;
    }

    /* ════════════════════════════════════════════════════════════════
     * Platform latency — categorized with tabs
     * ════════════════════════════════════════════════════════════════ */

    /* Category-specific thresholds (ms) and what the metric means */
    var PLAT_CATEGORIES = {
        gaming: {
            label: 'Gaming',
            thresholds: [30, 60, 100],  // excellent, good, fair (above = poor)
            metric: 'Latency',
            tip: 'For gaming, <30ms is excellent. Anything >100ms causes noticeable lag.',
        },
        social: {
            label: 'Social',
            thresholds: [80, 160, 280],
            metric: 'Latency',
            tip: 'Social platforms need <160ms for snappy feed loads and media.',
        },
        streaming: {
            label: 'Streaming',
            thresholds: [50, 120, 200],
            metric: 'Latency',
            tip: 'Streaming servers should be <120ms. Higher means longer buffering.',
        },
        productivity: {
            label: 'Productivity',
            thresholds: [100, 200, 350],
            metric: 'Latency',
            tip: 'Productivity tools need <200ms for real-time collaboration.',
        },
    };

    var PLATFORM_CATALOG = {
        gaming: [
            { id: 'steam',         label: 'Steam',         url: 'https://store.steampowered.com/favicon.ico' },
            { id: 'discord',       label: 'Discord',       url: 'https://discord.com/favicon.ico' },
            { id: 'twitch',        label: 'Twitch',        url: 'https://www.twitch.tv/favicon.ico' },
            { id: 'epicgames',     label: 'Epic Games',    url: 'https://www.epicgames.com/favicon.ico' },
            { id: 'riotgames',     label: 'Riot Games',    url: 'https://www.riotgames.com/favicon.ico' },
            { id: 'playstation',   label: 'PlayStation',   url: 'https://www.playstation.com/favicon.ico' },
            { id: 'xbox',          label: 'Xbox',          url: 'https://www.xbox.com/favicon.ico' },
            { id: 'blizzard',      label: 'Battle.net',    url: 'https://www.blizzard.com/favicon.ico' },
            { id: 'ea',            label: 'EA',            url: 'https://www.ea.com/favicon.ico' },
            { id: 'gog',           label: 'GOG',           url: 'https://www.gog.com/favicon.ico' },
            { id: 'ubisoft',       label: 'Ubisoft',       url: 'https://www.ubisoft.com/favicon.ico' },
        ],
        social: [
            { id: 'facebook',      label: 'Facebook',      url: 'https://www.facebook.com/favicon.ico' },
            { id: 'instagram',     label: 'Instagram',     url: 'https://www.instagram.com/favicon.ico' },
            { id: 'twitter',       label: 'X / Twitter',   url: 'https://x.com/favicon.ico' },
            { id: 'tiktok',        label: 'TikTok',        url: 'https://www.tiktok.com/favicon.ico' },
            { id: 'youtube',       label: 'YouTube',       url: 'https://www.youtube.com/favicon.ico' },
            { id: 'reddit',        label: 'Reddit',        url: 'https://www.reddit.com/favicon.ico' },
            { id: 'linkedin',      label: 'LinkedIn',      url: 'https://www.linkedin.com/favicon.ico' },
            { id: 'snapchat',      label: 'Snapchat',      url: 'https://www.snapchat.com/favicon.ico' },
            { id: 'pinterest',     label: 'Pinterest',     url: 'https://www.pinterest.com/favicon.ico' },
            { id: 'whatsapp',      label: 'WhatsApp',      url: 'https://web.whatsapp.com/favicon.ico' },
            { id: 'telegram',      label: 'Telegram',      url: 'https://web.telegram.org/favicon.ico' },
        ],
        streaming: [
            { id: 'netflix',       label: 'Netflix',       url: 'https://www.netflix.com/favicon.ico' },
            { id: 'spotify',       label: 'Spotify',       url: 'https://www.spotify.com/favicon.ico' },
            { id: 'youtubestream', label: 'YouTube',       url: 'https://www.youtube.com/favicon.ico' },
            { id: 'twitchstream',  label: 'Twitch',        url: 'https://www.twitch.tv/favicon.ico' },
            { id: 'primevideo',    label: 'Amazon Prime',  url: 'https://www.primevideo.com/favicon.ico' },
            { id: 'disneyplus',    label: 'Disney+',       url: 'https://www.disneyplus.com/favicon.ico' },
            { id: 'appletv',       label: 'Apple TV+',     url: 'https://tv.apple.com/favicon.ico' },
            { id: 'max',           label: 'Max (HBO)',      url: 'https://www.max.com/favicon.ico' },
            { id: 'soundcloud',    label: 'SoundCloud',    url: 'https://soundcloud.com/favicon.ico' },
            { id: 'hulu',          label: 'Hulu',          url: 'https://www.hulu.com/favicon.ico' },
            { id: 'deezer',        label: 'Deezer',        url: 'https://www.deezer.com/favicon.ico' },
        ],
        productivity: [
            { id: 'google',        label: 'Google',        url: 'https://www.google.com/generate_204' },
            { id: 'github',        label: 'GitHub',        url: 'https://github.com/favicon.ico' },
            { id: 'slack',         label: 'Slack',         url: 'https://slack.com/favicon.ico' },
            { id: 'zoom',          label: 'Zoom',          url: 'https://zoom.us/favicon.ico' },
            { id: 'dropbox',       label: 'Dropbox',       url: 'https://www.dropbox.com/favicon.ico' },
            { id: 'notion',        label: 'Notion',        url: 'https://www.notion.so/favicon.ico' },
            { id: 'figma',         label: 'Figma',         url: 'https://www.figma.com/favicon.ico' },
            { id: 'teams',         label: 'MS Teams',      url: 'https://teams.microsoft.com/favicon.ico' },
            { id: 'jira',          label: 'Jira',          url: 'https://www.atlassian.com/favicon.ico' },
            { id: 'cloudflare',    label: 'Cloudflare',    url: 'https://1.1.1.1/cdn-cgi/trace' },
            { id: 'salesforce',    label: 'Salesforce',    url: 'https://www.salesforce.com/favicon.ico' },
        ],
    };

    /* Flatten all platforms for the "All" tab (dedup by url) */
    var _allPlatforms = (function() {
        var seen = {}, list = [];
        Object.keys(PLATFORM_CATALOG).forEach(function(cat) {
            PLATFORM_CATALOG[cat].forEach(function(p) {
                if (!seen[p.url]) { seen[p.url] = true; list.push(Object.assign({ cat: cat }, p)); }
            });
        });
        return list;
    })();

    var _platResults   = {};
    var _platActiveCat = 'all';

    async function measurePlatformLatency() {
        log('Measuring platform latency (server-side HEAD) for ' + _allPlatforms.length + ' platforms...', 'debug');
        var payload = _allPlatforms.map(function(p) { return { id: p.id, url: p.url }; });
        try {
            var res = await fetch(CFG.api + '?action=ping_batch&_=' + Date.now(), {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            var data = await res.json();
            if (data.results) {
                _allPlatforms.forEach(function(p) {
                    if (data.results[p.id] != null)
                        log(p.label + ' — ' + data.results[p.id] + ' ms', 'debug');
                });
                _platResults = data.results;
                // Retry platforms that timed out
                var timedOut = _allPlatforms.filter(function(p) { return _platResults[p.id] == null; });
                if (timedOut.length > 0) {
                    log('Retrying ' + timedOut.length + ' timed-out platform(s)...', 'debug');
                    try {
                        var retryPayload = timedOut.map(function(p) { return { id: p.id, url: p.url }; });
                        var retryRes = await fetch(CFG.api + '?action=ping_batch&_=' + Date.now(), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(retryPayload),
                        });
                        if (retryRes.ok) {
                            var retryData = await retryRes.json();
                            if (retryData.results) {
                                Object.assign(_platResults, retryData.results);
                                timedOut.forEach(function(p) {
                                    if (retryData.results[p.id] != null)
                                        log(p.label + ' (retry) — ' + retryData.results[p.id] + ' ms', 'debug');
                                });
                            }
                        }
                    } catch (e) {
                        log('Platform latency retry failed: ' + e.message, 'warn');
                    }
                }
                _renderPlatformLatency(_platActiveCat);
                return _platResults;
            }
        } catch (e) {
            log('Platform latency batch failed: ' + e.message, 'warn');
        }
        return {};
    }

    function _platQuality(ms, cat) {
        if (ms == null) return { cls: 'bad', badge: 'Timeout' };
        var th = PLAT_CATEGORIES[cat] ? PLAT_CATEGORIES[cat].thresholds : [60, 120, 200];
        if (ms <= th[0]) return { cls: 'good', badge: 'Excellent' };
        if (ms <= th[1]) return { cls: 'good', badge: 'Good' };
        if (ms <= th[2]) return { cls: 'warn', badge: 'Fair' };
        return { cls: 'bad', badge: 'Poor' };
    }

    function _renderPlatformLatency(cat) {
        var wrap = $$('#ist-platform-latency');
        if (!wrap) return;
        _platActiveCat = cat || 'all';

        /* Update tab active state */
        $$$('.ist-plat-tab').forEach(function(b) {
            b.classList.toggle('active', b.dataset.cat === _platActiveCat);
        });

        var targets = _platActiveCat === 'all'
            ? _allPlatforms
            : (PLATFORM_CATALOG[_platActiveCat] || []);

        if (!targets.length) { wrap.innerHTML = '<p class="ist-empty-hint">No platforms in this category.</p>'; return; }

        var catInfo = PLAT_CATEGORIES[_platActiveCat];
        var tip = catInfo ? catInfo.tip : '';

        /* Check if results exist */
        var hasResults = Object.keys(_platResults).length > 0;
        if (!hasResults) {
            wrap.innerHTML = '<p class="ist-empty-hint">Measured in Professional mode — run a Professional test to populate this section.</p>';
            return;
        }

        /* Sort by latency ascending (nulls last) */
        var sorted = targets.slice().sort(function(a, b) {
            var va = _platResults[a.id], vb = _platResults[b.id];
            if (va == null && vb == null) return 0;
            if (va == null) return 1;
            if (vb == null) return -1;
            return va - vb;
        });

        /* Max for bar scaling */
        var vals = sorted.map(function(t) { return _platResults[t.id]; }).filter(function(v) { return v != null; });
        var maxVal = vals.length ? Math.max.apply(null, vals) : 1;

        var html = '';
        if (tip) html += '<p class="ist-plat-tip">' + tip + '</p>';
        html += '<div class="ist-plat-grid">';

        sorted.forEach(function(t) {
            var val = _platResults[t.id];
            var q   = _platQuality(val, t.cat || _platActiveCat);
            var pct = val != null ? Math.min(100, (val / Math.max(maxVal, 1)) * 100) : 100;
            html += '<div class="ist-plat-item ist-plat-' + q.cls + '">' +
                '<div class="ist-plat-item-top">' +
                    '<span class="ist-plat-label">' + t.label + '</span>' +
                    '<span class="ist-plat-badge ist-plat-badge-' + q.cls + '">' + q.badge + '</span>' +
                '</div>' +
                '<span class="ist-plat-val">' + (val != null ? val + '<small>ms</small>' : 'N/A') + '</span>' +
                '<div class="ist-plat-bar-wrap"><div class="ist-plat-bar-fill ist-plat-bar-' + q.cls + '" style="width:' + pct.toFixed(1) + '%"></div></div>' +
                '</div>';
        });

        html += '</div>';
        wrap.innerHTML = html;
    }

    function switchPlatformTab(cat) {
        _renderPlatformLatency(cat);
    }

    /* ════════════════════════════════════════════════════════════════
     * Scoring engine
     * ════════════════════════════════════════════════════════════════ */
    function calcScores(r) {
        /*
         * Piecewise-linear normalization:
         * Maps a metric value to 0–100 using real-world breakpoints:
         *   excellent → 100, good → 75, fair → 50, poor → 25, worst → 0
         * For inverted metrics (ping/jitter/loss) lower = better.
         */
        function piecewise(val, breakpoints, invert) {
            // breakpoints: [[thresholdVal, score], ...] sorted from best to worst
            if (val == null) return 50;
            if (invert) val = -val;
            var pts = invert
                ? breakpoints.map(function(b) { return [-b[0], b[1]]; })
                : breakpoints;
            if (val >= pts[0][0]) return pts[0][1];
            if (val <= pts[pts.length - 1][0]) return pts[pts.length - 1][1];
            for (var i = 0; i < pts.length - 1; i++) {
                var hi = pts[i], lo = pts[i + 1];
                if (val <= hi[0] && val >= lo[0]) {
                    var t = (val - lo[0]) / (hi[0] - lo[0]);
                    return lo[1] + t * (hi[1] - lo[1]);
                }
            }
            return 50;
        }

        var dl   = r.download    != null ? r.download    : null;
        var ul   = r.upload      != null ? r.upload      : null;
        var ping = r.ping        != null ? r.ping        : null;
        var jit  = r.jitter      != null ? r.jitter      : null;
        var loss = r.packet_loss != null ? r.packet_loss : null;

        // Download: <3 Mbps → 0, 10 → 30, 25 → 55, 100 → 80, 300+ → 100
        var scoreDL   = piecewise(dl,   [[300,100],[100,80],[25,55],[10,30],[3,10],[0,0]], false);
        // Upload: <1 → 0, 5 → 40, 20 → 65, 50 → 85, 100+ → 100
        var scoreUL   = piecewise(ul,   [[100,100],[50,85],[20,65],[5,40],[1,15],[0,0]], false);
        // Ping (inverted): <10ms → 100, 20 → 90, 50 → 75, 100 → 50, 200 → 20, 400+ → 0
        var scorePing = piecewise(ping, [[10,100],[20,90],[50,75],[100,50],[200,20],[400,0]], true);
        // Jitter (inverted): <2ms → 100, 5 → 85, 15 → 65, 30 → 40, 80+ → 0
        var scoreJit  = piecewise(jit,  [[2,100],[5,85],[15,65],[30,40],[80,5],[120,0]], true);
        // Packet loss (inverted): 0% → 100, 0.5% → 85, 1% → 65, 2% → 40, 5% → 15, 10%+ → 0
        var scoreLoss = piecewise(loss, [[0,100],[0.5,85],[1,65],[2,40],[5,15],[10,0]], true);

        var w = CFG.scoreWeights;
        var overall = scoreDL * w.download + scoreUL * w.upload + scorePing * w.ping + scoreJit * w.jitter + scoreLoss * w.loss;

        // Penalty: any metric null → score is an estimate (reduce confidence)
        var nullCount = [dl, ul, ping, jit, loss].filter(function(v) { return v == null; }).length;
        overall = overall * (1 - nullCount * 0.05);

        // Hard floor: packet loss ≥5% or ping ≥300ms caps overall to 50
        if (loss != null && loss >= 5)  overall = Math.min(overall, 50);
        if (ping != null && ping >= 300) overall = Math.min(overall, 55);

        overall = Math.max(0, Math.min(100, Math.round(overall)));

        var scores = {
            'Overall':        overall,
            'Gaming':         Math.round(scorePing * 0.40 + scoreJit * 0.35 + scoreLoss * 0.25),
            'Streaming':      Math.round(scoreDL   * 0.55 + scorePing * 0.25 + scoreJit * 0.20),
            'Video Call':     Math.round(scoreUL   * 0.35 + scorePing * 0.30 + scoreJit * 0.20 + scoreDL * 0.15),
            'Large Download': Math.round(scoreDL   * 0.80 + scorePing * 0.10 + scoreUL  * 0.10),
            'Web Browsing':   Math.round(scorePing * 0.45 + scoreDL   * 0.40 + scoreJit * 0.15),
            'Remote Work':    Math.round(scoreUL   * 0.35 + scoreDL   * 0.25 + scorePing * 0.25 + scoreJit * 0.15),
        };

        // Apply same loss/ping hard floors to sub-scores
        Object.keys(scores).forEach(function(k) {
            if (k !== 'Overall') {
                if (loss != null && loss >= 5)  scores[k] = Math.min(scores[k], 45);
                if (ping != null && ping >= 300) scores[k] = Math.min(scores[k], 55);
            }
            scores[k] = Math.max(0, Math.min(100, Math.round(scores[k])));
        });

        return scores;
    }

    function gradeFromScore(s) {
        if (s >= 95) return 'A+';
        if (s >= 85) return 'A';
        if (s >= 70) return 'B';
        if (s >= 55) return 'C';
        if (s >= 40) return 'D';
        return 'F';
    }

    var SCORE_REASONS = {
        'Gaming':         function(r) {
            if ((r.ping || 999) <= 20 && (r.jitter || 99) <= 5) return 'Exceptional low-latency performance for competitive gaming.';
            if ((r.ping || 999) <= 60) return 'Good for online gaming; minor lag may occur in fast-paced titles.';
            return 'High latency or jitter may cause noticeable lag in real-time games.';
        },
        'Streaming':      function(r) {
            if ((r.download || 0) >= 100) return '4K HDR streaming on multiple devices simultaneously.';
            if ((r.download || 0) >= 25) return 'HD streaming comfortable; 4K may buffer occasionally.';
            return 'SD streaming only; HD may experience frequent buffering.';
        },
        'Video Call':     function(r) {
            if ((r.upload || 0) >= 10 && (r.ping || 999) <= 60) return 'Crystal-clear video conferencing on any platform.';
            if ((r.upload || 0) >= 5) return 'Standard video calls work; 4K calls may drop quality.';
            return 'Limited upload may cause video quality degradation in calls.';
        },
        'Large Download': function(r) {
            if ((r.download || 0) >= 200) return 'Multi-GB files download in seconds.';
            if ((r.download || 0) >= 50)  return 'Large files download comfortably within minutes.';
            return 'Large downloads will take considerable time.';
        },
        'Web Browsing':   function(r) {
            if ((r.ping || 999) <= 30 && (r.download || 0) >= 25) return 'Instant page loads, zero perceptible latency.';
            if ((r.ping || 999) <= 80) return 'Comfortable browsing with occasional slight delays.';
            return 'Noticeable delay on page loads; heavy sites may be sluggish.';
        },
        'Remote Work':    function(r) {
            if ((r.upload || 0) >= 20 && (r.download || 0) >= 50) return 'Ideal for cloud-heavy workflows and remote collaboration.';
            if ((r.upload || 0) >= 5) return 'Adequate for most remote work scenarios.';
            return 'Upload constraints may limit file sharing and cloud sync performance.';
        },
    };

    function renderScores(scores, results) {
        var container = $$('#ist-scores-grid');
        if (!container) return;
        container.innerHTML = '';

        var icons = {
            'Overall':        '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
            'Gaming':         '<rect x="6" y="3" width="12" height="18" rx="2"/><path d="M9 7h1m1 0h4m-6 4h.01M12 11h.01m1.99 0h.01M12 15h.01"/>',
            'Streaming':      '<polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/>',
            'Video Call':     '<path d="M15 10l4.553-2.069A1 1 0 0 1 21 8.82v6.36a1 1 0 0 1-1.447.894L15 14v-4z"/><rect x="1" y="6" width="14" height="12" rx="2"/>',
            'Large Download': '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
            'Web Browsing':   '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
            'Remote Work':    '<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>',
        };

        Object.keys(scores).forEach(function(name) {
            var score = scores[name];
            var grade = gradeFromScore(score);
            var gradeKey = grade.replace('+', 'p').toLowerCase();
            var icon  = icons[name] || icons['Overall'];
            var reason = SCORE_REASONS[name] ? SCORE_REASONS[name](results) : '';

            // Color for bar
            var barClr = score >= 80 ? 'var(--color-success)' : (score >= 55 ? 'var(--color-warning)' : 'var(--color-danger)');

            var card = document.createElement('div');
            card.className = 'ist-score-card';
            card.innerHTML =
                '<div class="ist-score-header">' +
                  '<div class="ist-score-name">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + icon + '</svg>' +
                    name +
                  '</div>' +
                  '<div style="display:flex;align-items:center;gap:8px">' +
                    '<span class="ist-score-value">' + score + '</span>' +
                    '<span class="ist-score-grade ' + gradeKey + '">' + grade + '</span>' +
                  '</div>' +
                '</div>' +
                '<div class="ist-score-bar-track"><div class="ist-score-bar-fill" style="width:0%;background:' + barClr + '"></div></div>' +
                (reason ? '<div class="ist-score-reason">' + reason + '</div>' : '');

            container.appendChild(card);

            // Animate bar
            requestAnimationFrame(function() {
                var fill = card.querySelector('.ist-score-bar-fill');
                if (fill) fill.style.width = score + '%';
            });
        });
    }

    /* ════════════════════════════════════════════════════════════════
     * Recommendations generator
     * ════════════════════════════════════════════════════════════════ */
    function generateRecommendations(r) {
        var recs = [];
        var dl   = r.download    != null ? r.download    : null;
        var ul   = r.upload      != null ? r.upload      : null;
        var ping = r.ping        != null ? r.ping        : null;
        var jit  = r.jitter      != null ? r.jitter      : null;
        var loss = r.packet_loss != null ? r.packet_loss : null;

        // Packet loss — most severe issue, flag first
        if (loss != null && loss >= 5)
            recs.push('[Critical] Packet loss is ' + loss.toFixed(1) + '% — far above the acceptable threshold of 1%. This will cause dropped connections, audio/video glitches, and retransmissions. Contact your ISP immediately; this typically indicates a physical line fault or severely congested network segment.');
        else if (loss != null && loss >= 2)
            recs.push('[Warning] Packet loss at ' + loss.toFixed(1) + '% is noticeable and will degrade VoIP, gaming, and streaming. Run the test at different times to rule out congestion. If persistent, request a line check from your ISP.');
        else if (loss != null && loss >= 0.5)
            recs.push('[Notice] Marginal packet loss (' + loss.toFixed(1) + '%) detected. Usually benign for TCP traffic but may affect UDP-based apps like games or voice calls. Monitor over multiple tests.');

        // Latency
        if (ping != null && ping >= 200)
            recs.push('[Critical] Latency is very high at ' + ping.toFixed(0) + ' ms. Real-time applications such as gaming, video calls, and VoIP will be severely impacted. Check for bandwidth saturation, switch to a wired connection, or contact your ISP about your line quality.');
        else if (ping != null && ping >= 80)
            recs.push('[Warning] Latency of ' + ping.toFixed(0) + ' ms is elevated. Gaming and video calls will experience noticeable delay. Try connecting via Ethernet, closing background applications, or restarting your router.');
        else if (ping != null && ping >= 40)
            recs.push('[Notice] Latency is ' + ping.toFixed(0) + ' ms — acceptable for most use cases but not ideal for competitive gaming (target: under 20 ms). A wired connection or closer server may help.');

        // Jitter
        if (jit != null && jit >= 30)
            recs.push('[Critical] Jitter of ' + jit.toFixed(0) + ' ms indicates highly unstable latency. VoIP calls will sound choppy and games will feel unresponsive. Enable QoS on your router and prioritize real-time traffic. Check for interference if on Wi-Fi.');
        else if (jit != null && jit >= 15)
            recs.push('[Warning] Jitter of ' + jit.toFixed(0) + ' ms will cause occasional audio stuttering in calls and inconsistent game responsiveness. Check for other devices consuming bandwidth during the test, and consider enabling QoS.');
        else if (jit != null && jit >= 8)
            recs.push('[Notice] Jitter is ' + jit.toFixed(0) + ' ms — slightly elevated. This is typically fine for streaming and browsing but may cause occasional artifacts in live video calls.');

        // Download speed
        if (dl != null && dl < 5)
            recs.push('[Critical] Download speed of ' + dl.toFixed(1) + ' Mbps is far below the recommended minimum of 25 Mbps for modern usage. SD streaming and basic web browsing may still work, but HD video, large downloads, and multi-device use are not practical. Consider upgrading your plan.');
        else if (dl != null && dl < 25)
            recs.push('[Warning] Download speed is ' + dl.toFixed(1) + ' Mbps. This meets basic needs but will struggle with HD streaming (' + (dl < 10 ? 'HD requires ~8 Mbps' : '4K requires ~25 Mbps') + '). If this is below your plan speed, restart your router or contact your ISP.');
        else if (dl != null && dl < 100)
            recs.push('[Good] Download speed of ' + dl.toFixed(1) + ' Mbps supports HD streaming and moderate multi-device usage comfortably. For 4K streaming on multiple screens simultaneously, speeds above 100 Mbps are recommended.');

        // Upload speed
        if (ul != null && ul < 2)
            recs.push('[Critical] Upload speed of ' + ul.toFixed(1) + ' Mbps is very low. Video calls require at least 3–5 Mbps for standard quality. Cloud backup, file sharing, and collaborative tools will be significantly impaired. Contact your ISP or consider a plan upgrade.');
        else if (ul != null && ul < 10)
            recs.push('[Warning] Upload speed of ' + ul.toFixed(1) + ' Mbps is limited. 4K video calls require ~15 Mbps upload. Large file uploads and cloud sync will be slow. Wired connections and off-peak usage can help.');

        // DNS performance
        if (r.dns_ms != null && r.dns_ms > 100)
            recs.push('[Notice] DNS resolution took ' + r.dns_ms.toFixed(0) + ' ms — slower than optimal. Switching to a faster public DNS resolver (e.g., 1.1.1.1 or 8.8.8.8) can reduce page load times and perceived latency.');

        // All good
        if (!recs.length)
            recs.push('[Good] Your connection is performing well across all measured parameters. Latency, jitter, packet loss, and throughput are all within excellent ranges. No issues detected.');

        return recs;
    }

    function renderRecommendations(recs) {
        var el = $$('#ist-recommendations');
        if (!el) return;
        el.innerHTML = '';

        var severityMap = {
            '[Critical]': { cls: 'ist-rec-critical', label: 'Critical' },
            '[Warning]':  { cls: 'ist-rec-warning',  label: 'Warning'  },
            '[Notice]':   { cls: 'ist-rec-notice',   label: 'Notice'   },
            '[Good]':     { cls: 'ist-rec-good',     label: 'Good'     },
        };

        recs.forEach(function(r) {
            var sev = null, text = r;
            Object.keys(severityMap).forEach(function(tag) {
                if (r.indexOf(tag) === 0) { sev = severityMap[tag]; text = r.slice(tag.length).trim(); }
            });

            var div = document.createElement('div');
            div.className = 'ist-rec-item' + (sev ? ' ' + sev.cls : '');
            div.innerHTML =
                (sev ? '<span class="ist-rec-label">' + sev.label + '</span>' : '') +
                '<span class="ist-rec-text">' + text + '</span>';
            el.appendChild(div);
        });
    }

    /* ════════════════════════════════════════════════════════════════
     * Environment cards renderer
     * ════════════════════════════════════════════════════════════════ */
    function countryFlag(cc) {
        if (!cc || cc.length !== 2) return '';
        try {
            return String.fromCodePoint(
                0x1F1E6 + cc.toUpperCase().charCodeAt(0) - 65,
                0x1F1E6 + cc.toUpperCase().charCodeAt(1) - 65
            );
        } catch (e) { return ''; }
    }

    function renderEnvCards(env) {
        function set(id, val) {
            var e = $$('#ist-env-' + id);
            if (e) e.textContent = val || '—';
        }

        set('ip',           env.ip);
        set('ipv6',         env.is_ipv6 ? 'Yes (IPv6)' : 'No (IPv4)');
        set('browser',      env.browser);
        set('platform',     env.platform);
        set('language',     env.language);
        set('conn-type',    env.connection_type);
        set('eff-type',     env.effective_type);
        set('downlink',     env.downlink);
        set('rtt-est',      env.rtt_est);
        set('save-data',    env.save_data);
        set('memory',       env.device_memory);
        set('cores',        env.cpu_cores);
        set('screen',       env.screen);
        set('pixel-ratio',  env.pixel_ratio + 'x');
        set('timezone',     env.timezone);
        set('color-scheme', env.color_scheme);
        set('touch',        env.touch);
        set('webrtc',       env.webrtc);
        set('webgl',        env.webgl);
        set('websocket',    env.websocket);
        set('service-worker', env.service_worker);

        // ISP / geo fields
        var isp = env.isp || {};
        set('isp',      isp.isp);
        set('org',      isp.org && isp.org !== isp.isp ? isp.org : null);
        set('asn',      isp.as);
        var loc = [isp.city, isp.region, isp.country].filter(Boolean).join(', ');
        set('location', loc || null);

        // ISP strip
        var strip = $$('#ist-isp-strip');
        if (strip && isp.isp) {
            var flagEl    = $$('#ist-isp-flag');
            var nameEl    = $$('#ist-isp-name');
            var locEl     = $$('#ist-isp-location');
            var asnEl     = $$('#ist-isp-asn');
            if (flagEl) flagEl.textContent = countryFlag(isp.country_code);
            if (nameEl) nameEl.textContent = isp.isp;
            if (locEl)  locEl.textContent  = [isp.city, isp.region, isp.country].filter(Boolean).join(', ');
            if (asnEl)  asnEl.textContent  = isp.as || '';
            strip.style.display = 'flex';
        }

        // Network Toolkit clickable IP
        var ipEl = $$('#ist-env-ip');
        if (ipEl && env.ip && env.ip !== 'Unavailable') {
            ipEl.className = 'ist-env-val link';
            ipEl.title = 'Analyze in Network Toolkit';
            ipEl.style.cursor = 'pointer';
            ipEl.onclick = function() {
                var type = env.is_ipv6 ? 'ipv6' : 'ip';
                window.open(CFG.ntPath + '?q=' + encodeURIComponent(env.ip) + '&type=' + type, '_blank');
            };
        }
    }

    /* ── Share results ──────────────────────────────────────────────────────── */
    function buildShareUrl() {
        var r = state.results;
        if (!r || r.download == null) return null;
        var isp = (r.env && r.env.isp) || {};
        var data = {
            v:    1,
            ts:   Math.floor(Date.now() / 1000),
            mode: CFG.currentMode,
            dl:   r.download    != null ? +r.download.toFixed(2)    : null,
            ul:   r.upload      != null ? +r.upload.toFixed(2)      : null,
            p:    r.ping        != null ? +r.ping.toFixed(1)        : null,
            j:    r.jitter      != null ? +r.jitter.toFixed(1)      : null,
            loss: r.packet_loss != null ? +r.packet_loss.toFixed(2) : null,
            dns:  r.dns_ms      != null ? +r.dns_ms.toFixed(0)      : null,
            grade: r.grade      || null,
            isp:  isp.isp       || null,
            city: isp.city      || null,
            cc:   isp.country_code || null,
        };
        try { return window.location.pathname + '?share=' + btoa(JSON.stringify(data)); }
        catch (e) { return null; }
    }

    function shareResults() {
        var url = buildShareUrl();
        if (!url) {
            _showToast('Run a test first to get a share link.', 'warn');
            return;
        }
        var full = window.location.origin + url;
        var lbl = $$('#ist-share-btn-label');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(full).then(function() {
                if (lbl) lbl.textContent = 'Copied!';
                setTimeout(function() { if (lbl) lbl.textContent = 'Share'; }, 2000);
            });
        } else {
            var ta = document.createElement('textarea');
            ta.value = full; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            if (lbl) lbl.textContent = 'Copied!';
            setTimeout(function() { if (lbl) lbl.textContent = 'Share'; }, 2000);
        }
    }

    function _showToast(msg, type) {
        var t = document.createElement('div');
        t.textContent = msg;
        t.style.cssText = 'position:fixed;bottom:24px;right:24px;padding:10px 18px;border-radius:12px;font-size:13px;font-weight:600;z-index:9999;pointer-events:none;transition:opacity .3s;' +
            (type === 'warn' ? 'background:var(--color-warning);color:#fff;' : 'background:var(--color-primary);color:#fff;');
        document.body.appendChild(t);
        setTimeout(function() { t.style.opacity = '0'; setTimeout(function() { t.parentNode && t.parentNode.removeChild(t); }, 300); }, 2200);
    }

    function showSharedResults(data) {
        var panel = $$('#ist-shared-panel');
        if (!panel) return;
        var date  = data.ts ? new Date(data.ts * 1000).toLocaleString() : '';
        var flag  = data.cc ? countryFlag(data.cc) : '';
        var modeLabel = (CFG.presets[data.mode] || {}).label || data.mode || 'Unknown';
        var ispLine = [flag, data.isp, data.city, data.cc].filter(Boolean).join(' ');

        function metric(label, val, unit, color) {
            var c = color ? 'color:var(--color-' + color + ');' : '';
            return '<div class="ist-shared-metric">' +
                '<div class="ist-shared-metric-val" style="' + c + '">' + (val != null ? val : '—') +
                '<span class="ist-shared-metric-unit">' + (val != null ? unit : '') + '</span></div>' +
                '<div class="ist-shared-metric-label">' + label + '</div></div>';
        }

        var dlColor  = data.dl  > 100 ? 'success' : data.dl  > 25 ? null : 'danger';
        var ulColor  = data.ul  > 25  ? 'success' : data.ul  > 5  ? null : 'danger';
        var pColor   = data.p   < 30  ? 'success' : data.p   < 80 ? null : 'danger';
        var lossColor= data.loss <= 1 ? 'success' : 'danger';

        panel.innerHTML =
            '<div class="ist-shared-header">' +
                '<div class="ist-shared-title">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>' +
                    'Shared Speed Test Results' +
                '</div>' +
                (data.grade ? '<div class="ist-shared-grade">' + data.grade + '</div>' : '') +
            '</div>' +
            (ispLine ? '<div class="ist-shared-isp">' + ispLine + '</div>' : '') +
            '<div class="ist-shared-meta" style="margin-bottom:14px;">' + (date ? date + ' · ' : '') + modeLabel + ' mode</div>' +
            '<div class="ist-shared-metrics">' +
                metric('Download',    data.dl   != null ? data.dl.toFixed(1)   : null, 'Mbps', dlColor) +
                metric('Upload',      data.ul   != null ? data.ul.toFixed(1)   : null, 'Mbps', ulColor) +
                metric('Ping',        data.p    != null ? data.p.toFixed(0)    : null, 'ms',   pColor) +
                metric('Jitter',      data.j    != null ? data.j.toFixed(1)    : null, 'ms',   null) +
                metric('Packet Loss', data.loss != null ? data.loss.toFixed(1) : null, '%',    lossColor) +
                metric('DNS',         data.dns  != null ? data.dns             : null, 'ms',   null) +
            '</div>' +
            '<button class="ist-shared-run-btn" onclick="window.location.href=window.location.pathname">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>' +
                'Run Your Own Test' +
            '</button>';
        panel.style.display = 'block';
    }

    function _detectBrowser() {
        var ua = navigator.userAgent;
        if (ua.includes('Edg/'))     return 'Microsoft Edge';
        if (ua.includes('OPR/'))     return 'Opera';
        if (ua.includes('Chrome/'))  return 'Google Chrome';
        if (ua.includes('Firefox/')) return 'Mozilla Firefox';
        if (ua.includes('Safari/'))  return 'Apple Safari';
        return 'Unknown Browser';
    }

    function _checkWebGL() {
        try {
            var c = document.createElement('canvas');
            return c.getContext('webgl') || c.getContext('experimental-webgl') ? 'Supported' : 'Not Available';
        } catch (e) { return 'Not Available'; }
    }

    /* ════════════════════════════════════════════════════════════════
     * Test mode management
     * ════════════════════════════════════════════════════════════════ */
    function applyPreset(mode) {
        var p = CFG.presets[mode];
        if (!p) return;
        Object.keys(p).forEach(function(k) {
            if (k !== 'label' && k !== 'estSec' && k !== 'phases') CFG.test[k] = p[k];
        });
    }

    function setMode(mode) {
        if (state.running) return;
        CFG.currentMode = mode;
        document.querySelectorAll('.ist-mode-btn').forEach(function(b) {
            b.classList.toggle('active', b.dataset.mode === mode);
        });
        var customPanel = document.querySelector('#ist-custom-panel');
        if (customPanel) customPanel.style.display = mode === 'custom' ? 'block' : 'none';
        if (mode !== 'custom') applyPreset(mode);
        _updateEstimatedTime();
        _updateStartBtnLabel();
    }

    function _updateEstimatedTime() {
        var el = document.querySelector('#ist-est-time');
        if (!el) return;
        var mode = CFG.currentMode;
        var p = CFG.presets[mode];
        if (p) {
            el.textContent = 'Est. ~' + p.estSec + 's';
        } else if (mode === 'custom') {
            var T = CFG.test;
            var est = Math.round(
                (T.PING_COUNT * T.PING_INTERVAL / 1000) +
                (T.PKT_LOSS_PINGS * 0.06) + 3 +
                (T.DL_DURATION / 1000) + (T.UL_DURATION / 1000) + 5
            );
            el.textContent = 'Est. ~' + est + 's';
        }
    }

    function _updateStartBtnLabel() {
        var btn = $$('#ist-start-btn');
        if (!btn || state.running) return;
        var mode = CFG.currentMode;
        var p = CFG.presets[mode];
        var modeLabel = p ? p.label : 'Custom';
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Start ' + modeLabel + ' Diagnostic';
    }

    function applyCustomParams() {
        var fields = ['PING_COUNT','PKT_LOSS_PINGS','DL_STREAMS','DL_DURATION','UL_STREAMS','UL_DURATION'];
        fields.forEach(function(f) {
            var el = document.querySelector('#ist-custom-' + f.toLowerCase());
            if (el && el.value) {
                var v = parseInt(el.value, 10);
                if (!isNaN(v) && v > 0) CFG.test[f] = v;
            }
        });
        _updateEstimatedTime();
    }

    function scrollToReport() {
        var el = document.querySelector('#ist-report-center');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /* ════════════════════════════════════════════════════════════════
     * Elapsed time tracker
     * ════════════════════════════════════════════════════════════════ */
    /* ════════════════════════════════════════════════════════════════
     * Test history — localStorage persistence
     * ════════════════════════════════════════════════════════════════ */
    var _HIST_KEY = 'ist_history_v1';
    var _HIST_MAX = 10;

    function _loadHistory() {
        try { return JSON.parse(localStorage.getItem(_HIST_KEY) || '[]'); } catch (e) { return []; }
    }

    function _saveHistory(r) {
        var hist = _loadHistory();
        hist.unshift({
            ts:      r.timestamp || new Date().toISOString(),
            dl:      r.download,
            ul:      r.upload,
            ping:    r.ping,
            grade:   r.grade,
            score:   r.score,
            results: r,
        });
        if (hist.length > _HIST_MAX) hist = hist.slice(0, _HIST_MAX);
        try { localStorage.setItem(_HIST_KEY, JSON.stringify(hist)); } catch (e) {}
        return hist;
    }

    /* ════════════════════════════════════════════════════════════════
     * Historical Trend Chart
     * ════════════════════════════════════════════════════════════════ */
    function renderTrendChart(hist) {
        var wrap   = $$('#ist-trend-chart-wrap');
        var svg    = $$('#ist-trend-svg');
        var label  = $$('#ist-trend-label');
        if (!wrap || !svg || !hist || hist.length < 2) {
            if (wrap) wrap.style.display = 'none';
            return;
        }
        wrap.style.display = '';

        // Reverse so oldest → newest left → right
        var data = hist.slice().reverse();

        var W = 600, H = 120;
        var pad = { t: 16, r: 16, b: 28, l: 36 };
        var cW = W - pad.l - pad.r;
        var cH = H - pad.t - pad.b;
        svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
        svg.setAttribute('preserveAspectRatio', 'none');
        svg.innerHTML = '';

        function svgN(tag, attrs) {
            var el = document.createElementNS('http://www.w3.org/2000/svg', tag);
            Object.keys(attrs || {}).forEach(function(k) { el.setAttribute(k, attrs[k]); });
            return el;
        }

        // Scores and speeds
        var scores = data.map(function(e) { return e.score != null ? e.score : null; });
        var dls    = data.map(function(e) { return e.dl   != null ? e.dl   : null; });
        var uls    = data.map(function(e) { return e.ul   != null ? e.ul   : null; });

        var validScores = scores.filter(function(v) { return v != null; });
        var allSpeeds   = dls.concat(uls).filter(function(v) { return v != null; });

        var scoreMin = 0, scoreMax = 100;
        var speedMax = allSpeeds.length ? Math.max.apply(null, allSpeeds) : 100;
        speedMax = Math.max(speedMax, 1);

        function toX(i) { return pad.l + (data.length > 1 ? i / (data.length - 1) : 0.5) * cW; }
        function scoreY(v) { return pad.t + (1 - (v - scoreMin) / (scoreMax - scoreMin)) * cH; }
        function speedY(v) { return pad.t + (1 - v / speedMax) * cH; }

        // Defs (gradient for score area)
        var defs = svgN('defs');
        var grad = svgN('linearGradient', { id: 'ist-trend-grad', x1: '0%', y1: '0%', x2: '0%', y2: '100%' });
        [['0%', '0.25'], ['100%', '0']].forEach(function(s) {
            var stop = svgN('stop', { offset: s[0], 'stop-color': 'var(--color-primary)', 'stop-opacity': s[1] });
            grad.appendChild(stop);
        });
        defs.appendChild(grad);
        svg.appendChild(defs);

        // Grid lines
        [0, 25, 50, 75, 100].forEach(function(v) {
            var y = scoreY(v).toFixed(1);
            svg.appendChild(svgN('line', { x1: pad.l, y1: y, x2: W - pad.r, y2: y, stroke: 'var(--color-border)', 'stroke-width': '1', opacity: '0.6' }));
            var txt = svgN('text', { x: pad.l - 4, y: y, 'text-anchor': 'end', 'dominant-baseline': 'middle', fill: 'var(--color-text-subtle)', 'font-size': '8', 'font-family': 'var(--font-sans)' });
            txt.textContent = v;
            svg.appendChild(txt);
        });

        // Score area fill
        var validScorePts = data.map(function(e, i) { return e.score != null ? [toX(i), scoreY(e.score)] : null; }).filter(Boolean);
        if (validScorePts.length >= 2) {
            var areaD = 'M ' + validScorePts[0][0].toFixed(1) + ' ' + (pad.t + cH).toFixed(1) +
                validScorePts.map(function(p) { return ' L ' + p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join('') +
                ' L ' + validScorePts[validScorePts.length - 1][0].toFixed(1) + ' ' + (pad.t + cH).toFixed(1) + ' Z';
            svg.appendChild(svgN('path', { d: areaD, fill: 'url(#ist-trend-grad)' }));
            var lineD = 'M ' + validScorePts.map(function(p) { return p[0].toFixed(1) + ',' + p[1].toFixed(1); }).join(' L ');
            svg.appendChild(svgN('path', { d: lineD, fill: 'none', stroke: 'var(--color-primary)', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }));
        }

        // DL line (scaled to speed axis, normalised to fit)
        function drawSpeedLine(vals, color) {
            var pts = data.map(function(e, i) { return vals[i] != null ? [toX(i), speedY(Math.min(vals[i], speedMax))] : null; }).filter(Boolean);
            if (pts.length < 2) return;
            var d = 'M ' + pts.map(function(p) { return p[0].toFixed(1) + ',' + p[1].toFixed(1); }).join(' L ');
            svg.appendChild(svgN('path', { d: d, fill: 'none', stroke: color, 'stroke-width': '1.5', 'stroke-dasharray': '4 2', 'stroke-linecap': 'round', opacity: '0.75' }));
        }
        drawSpeedLine(dls, 'var(--color-success)');
        drawSpeedLine(uls, 'var(--color-warning)');

        // Dots + grade labels on x-axis
        data.forEach(function(e, i) {
            var x = toX(i);
            if (e.score != null) {
                var y = scoreY(e.score);
                svg.appendChild(svgN('circle', { cx: x.toFixed(1), cy: y.toFixed(1), r: '4', fill: 'var(--color-primary)', stroke: 'var(--color-surface)', 'stroke-width': '2' }));
            }
            // X-axis date label
            var d = new Date(e.ts);
            var lbl = svgN('text', { x: x.toFixed(1), y: (H - 4) + '', 'text-anchor': 'middle', fill: 'var(--color-text-subtle)', 'font-size': '8', 'font-family': 'var(--font-sans)' });
            lbl.textContent = d.toLocaleDateString([], { month: 'numeric', day: 'numeric' });
            svg.appendChild(lbl);
        });

        // Trend label
        if (label && validScores.length >= 2) {
            var first = validScores[0], last = validScores[validScores.length - 1];
            var delta = last - first;
            if (Math.abs(delta) < 3) {
                label.textContent = '→ Stable';
                label.style.color = 'var(--color-text-muted)';
            } else if (delta > 0) {
                label.textContent = '↑ Improving (+' + delta.toFixed(0) + ' pts)';
                label.style.color = 'var(--color-success)';
            } else {
                label.textContent = '↓ Degrading (' + delta.toFixed(0) + ' pts)';
                label.style.color = 'var(--color-danger)';
            }
        }
    }

    function _renderHistoryChips(hist) {
        var panel   = $$('#ist-history-panel');
        var chips   = $$('#ist-history-chips');
        var countEl = $$('#ist-history-count');
        if (!panel || !chips) return;
        if (!hist || !hist.length) { panel.style.display = 'none'; return; }
        panel.style.display = '';
        if (countEl) countEl.textContent = hist.length;
        chips.innerHTML = '';
        hist.forEach(function(entry, idx) {
            var d      = new Date(entry.ts);
            var day    = d.toLocaleDateString([], { month: 'short', day: 'numeric' });
            var tim    = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            var dlStr  = entry.dl  != null ? entry.dl.toFixed(0) + ' Mbps' : '—';
            var grStr  = entry.grade || '—';
            var grCls  = 'ist-hchip-grade-' + grStr.replace('+', 'p').toLowerCase();
            var chip   = document.createElement('button');
            chip.className = 'ist-history-chip';
            chip.title = 'Restore result from ' + day + ' at ' + tim;
            chip.innerHTML =
                '<span class="ist-hchip-grade ' + grCls + '">' + grStr + '</span>' +
                '<span class="ist-hchip-dl">&#8595;&nbsp;' + dlStr + '</span>' +
                '<span class="ist-hchip-time">' + day + ' ' + tim + '</span>';
            chip.onclick = function(e) { e.stopPropagation(); restoreFromHistory(idx); };
            chips.appendChild(chip);
        });

        renderTrendChart(hist);
    }

    function restoreFromHistory(idx) {
        var hist  = _loadHistory();
        var entry = hist[idx];
        if (!entry || !entry.results) return;
        var r = entry.results;

        resetGauge();

        if (r.download != null) {
            var dlR = rateMetric('download', r.download);
            updateKPI('dl', r.download.toFixed(1), 'Mbps', dlR.badge, dlR.cls);
            updateKPI('peak-dl', r.peak_download != null ? r.peak_download.toFixed(1) : '—', 'Mbps');
            updateHeroStat('dl', r.download.toFixed(1), 'Mbps');
            animateGauge(r.download, 'DOWNLOAD');
            setReadout(r.download >= 10 ? r.download.toFixed(0) : r.download.toFixed(1), 'Mbps', 'RESTORED');
        }
        if (r.upload != null) {
            var ulR = rateMetric('upload', r.upload);
            updateKPI('ul', r.upload.toFixed(1), 'Mbps', ulR.badge, ulR.cls);
            updateKPI('peak-ul', r.peak_upload != null ? r.peak_upload.toFixed(1) : '—', 'Mbps');
            updateHeroStat('ul', r.upload.toFixed(1), 'Mbps');
            showUploadGauge();
            animateUploadGauge(r.upload);
            setUploadReadout(r.upload >= 10 ? r.upload.toFixed(0) : r.upload.toFixed(1), 'Mbps', 'RESTORED');
        }
        if (r.ping != null) {
            var pR = rateMetric('ping', r.ping);
            updateKPI('ping', r.ping.toFixed(1), 'ms', pR.badge, pR.cls);
            updateHeroStat('ping', r.ping.toFixed(1), 'ms');
        }
        if (r.jitter != null) {
            var jR = rateMetric('jitter', r.jitter);
            updateKPI('jitter', r.jitter.toFixed(1), 'ms', jR.badge, jR.cls);
            updateHeroStat('jitter', r.jitter.toFixed(1), 'ms');
        }
        if (r.packet_loss != null) {
            var lR = rateMetric('loss', r.packet_loss);
            updateKPI('loss', r.packet_loss.toFixed(1), '%', lR.badge, lR.cls);
        }
        if (r.dns_ms      != null) updateKPI('dns',       r.dns_ms.toFixed(0),      'ms');
        if (r.tls_ms      != null) updateKPI('tls',       r.tls_ms.toFixed(0),      'ms');
        if (r.ttfb_ms     != null) updateKPI('ttfb',      r.ttfb_ms.toFixed(0),     'ms');
        if (r.live_rtt_ms != null) updateKPI('http',      r.live_rtt_ms.toFixed(0), 'ms');
        if (r.stability_cov != null) updateKPI('stability', (100 - r.stability_cov * 100).toFixed(0), '%');
        if (r.score != null) {
            updateKPI('overall', r.score, '/100', r.grade, r.score >= 80 ? 'good' : (r.score >= 55 ? 'warn' : 'bad'));
            updateKPI('conn', r.grade, '', r.grade, r.score >= 80 ? 'good' : 'warn');
            var gb = $$('#ist-header-grade-badge');
            if (gb) { gb.textContent = r.grade; gb.className = 'ist-header-grade-badge ' + (r.grade || '').replace('+', ''); }
            var gw = $$('#ist-meta-grade-wrap');
            if (gw) gw.style.display = '';
        }
        if (r.scores)          renderScores(r.scores, r);
        if (r.recommendations) renderRecommendations(r.recommendations);
        if (r.env)             renderEnvCards(r.env);

        $$('.ist-hero-left')  && $$('.ist-hero-left').classList.remove('ist-hero-stats-hidden');
        $$('.ist-hero-right') && $$('.ist-hero-right').classList.remove('ist-hero-stats-hidden');

        log('Restored test from ' + new Date(entry.ts).toLocaleString(), 'info');
    }

    function toggleHistory() {
        var exp = $$('#ist-history-expanded');
        var btn = $$('#ist-history-expand-btn');
        if (!exp) return;
        var open = exp.style.display !== 'none';
        exp.style.display = open ? 'none' : '';
        if (btn) btn.classList.toggle('ist-history-open', !open);
        if (btn) btn.setAttribute('aria-expanded', String(!open));
    }

    function startTimer() {
        state.startTime = Date.now();
        if (state.elapsedTimer) clearInterval(state.elapsedTimer);
        var mode = CFG.currentMode;
        var p = CFG.presets[mode];
        var estTotal = p ? p.estSec : 0;
        var cntWrap = $$('#ist-meta-countdown-wrap');
        if (cntWrap && estTotal) cntWrap.style.display = '';
        state.elapsedTimer = setInterval(function() {
            var elapsed = (Date.now() - state.startTime) / 1000;
            var e = $$('#ist-elapsed');
            if (e) e.textContent = elapsed.toFixed(0) + 's';
            if (estTotal) {
                var remaining = Math.max(0, Math.ceil(estTotal - elapsed));
                var cnt = $$('#ist-meta-countdown');
                if (cnt) cnt.textContent = remaining + 's';
            }
        }, 500);
    }

    function stopTimer() {
        if (state.elapsedTimer) { clearInterval(state.elapsedTimer); state.elapsedTimer = null; }
        var cntWrap = $$('#ist-meta-countdown-wrap');
        if (cntWrap) cntWrap.style.display = 'none';
    }

    /* ════════════════════════════════════════════════════════════════
     * Full test orchestration
     * ════════════════════════════════════════════════════════════════ */
    function buildRunUrl(mode) {
        var base = window.location.pathname;
        if (mode === 'custom') {
            var T = CFG.test;
            return base + '?run&mode=custom' +
                '&pc='  + T.PING_COUNT      +
                '&lp='  + T.PKT_LOSS_PINGS  +
                '&dls=' + T.DL_STREAMS      +
                '&dld=' + T.DL_DURATION     +
                '&uls=' + T.UL_STREAMS      +
                '&uld=' + T.UL_DURATION;
        }
        return base + '?run&mode=' + mode;
    }

    function phaseEnabled(phase) {
        if (CFG.currentMode === 'custom') return true;
        var preset = CFG.presets[CFG.currentMode];
        if (!preset || !preset.phases) return true;
        return preset.phases.indexOf(phase) !== -1;
    }

    async function runFull() {
        if (state.running) return cancel();

        // Soft reset instead of page reload
        if (state.needsRefresh) {
            state.needsRefresh = false;
            resetAllResults();
            resetPhases();
            resetGauge();
            hideUploadGauge();
            updateProgress(0);
            var sbody = $$('#ist-terminal-body');
            if (sbody) sbody.innerHTML = '';
            state.termLogs = [];
            state.results = {};
            state.samples = { download: [], upload: [], ping: [], jitter: [], loss: [] };
            state.peak    = { download: 0, upload: 0, ping: 0, jitter: 0 };
            Object.keys(CHART_DATA).forEach(function(k) { CHART_DATA[k] = []; });
        }

        if (CFG.currentMode !== 'custom') applyPreset(CFG.currentMode);
        else applyCustomParams();

        state.running = true; state.cancelled = false;
        document.body.classList.add('test-active', CFG.currentMode);
        state.results = {}; state.samples = { download: [], upload: [], ping: [], jitter: [], loss: [] };
        state.peak    = { download: 0, upload: 0, ping: 0, jitter: 0 };
        state.failedMetrics = [];
        Object.keys(CHART_DATA).forEach(function(k) { CHART_DATA[k] = []; });

        var estEl = $$('#ist-est-time');
        if (estEl) estEl.style.display = '';
        var warnBanner = $$('#ist-incomplete-warning');
        if (warnBanner) warnBanner.style.display = 'none';

        var btn = $$('#ist-start-btn');
        if (btn) { btn.innerHTML = '<div class="ist-spinner"></div> Cancel'; btn.classList.add('ist-stop-btn'); }
        $$('#ist-meta-status') && ($$('#ist-meta-status').textContent = 'Running');
        $$('#ist-meta-dot') && ($$('#ist-meta-dot').className = 'ist-meta-dot running');
        resetAllResults();
        resetPhases();
        resetGauge();
        updateProgress(0);
        startTimer();

        // Reveal test-time sections (terminal + charts) at the moment the test starts
        revealSection($$('#ist-terminal-section'));
        revealSection($$('#ist-charts-section'));

        var tbody = $$('#ist-terminal-body');
        if (tbody) tbody.innerHTML = '';
        state.termLogs = [];

        log('Awan Tools Internet Speed Test — universal diagnostic mode', 'phase');
        log('Initializing test suite...', 'info');

        try {
            // Phase 1: Environment
            if (phaseEnabled('env')) {
                setPhase('env', 'running');
                showPhaseIndicator('env');
                log('Phase 1: Environment Detection', 'phase');
                var env = await measureEnv();
                state.results.env = env;
            }
            updateProgress(10);

            if (state.cancelled) throw new Error('cancelled');
            await _sleep(250);

            // Phase 2: Latency + Jitter
            if (phaseEnabled('latency')) {
                showPhaseIndicator('latency');
                log('Phase 2: Latency & Jitter Analysis', 'phase');
                switchChart('ping');
                var latencyRes = await measurePing();
                if (!latencyRes && !state.cancelled) {
                    log('Latency measurement failed — retrying once...', 'warn');
                    await _sleep(500);
                    latencyRes = await measurePing();
                    if (!latencyRes) { log('Latency measurement failed after retry — ping/jitter results unavailable.', 'warn'); state.failedMetrics.push('Ping & Jitter'); }
                }
                if (latencyRes) {
                    state.results.ping     = latencyRes.ping;
                    state.results.jitter   = latencyRes.jitter;
                    state.results.min_ping = latencyRes.min_ping;
                    state.results.max_ping = latencyRes.max_ping;
                }
                $$('.ist-hero-left') && $$('.ist-hero-left').classList.remove('ist-hero-stats-hidden');
            }
            updateProgress(25);

            if (state.cancelled) throw new Error('cancelled');
            await _sleep(350);

            // Phase 3: DNS
            if (phaseEnabled('dns')) {
                showPhaseIndicator('dns');
                log('Phase 3: DNS Resolution Timing', 'phase');
                var dnsRes = await measureDNS();
                if (dnsRes) state.results.dns_ms = dnsRes.dns_ms;
            }
            updateProgress(35);

            if (state.cancelled) throw new Error('cancelled');
            await _sleep(200);

            // Phase 4: Packet Loss
            if (phaseEnabled('pktloss')) {
                showPhaseIndicator('pktloss');
                log('Phase 4: Packet Loss Estimation', 'phase');
                var lossRes = await measurePacketLoss();
                if (lossRes) state.results.packet_loss = lossRes.packet_loss;
            }
            updateProgress(45);

            if (state.cancelled) throw new Error('cancelled');
            log('Cooling down before download phase...', 'debug');
            await _sleep(800);

            // Pre-warm connections before download (replaces separate calibrateGauge)
            log('Pre-warming connections for download...', 'debug');
            await warmConnections(3);

            if (state.cancelled) throw new Error('cancelled');

            // Phase 5: Download
            if (phaseEnabled('download')) {
                showPhaseIndicator('download');
                log('Phase 5: Download Speed — Saturation Test', 'phase');
                setReadout('0', 'MBPS', 'DOWNLOAD');
                switchChart('loaded_ping');
                var dlRes = await measureDownload();
                if (!dlRes && !state.cancelled) {
                    log('Download measurement failed — retrying once...', 'warn');
                    await _sleep(500);
                    dlRes = await measureDownload();
                    if (!dlRes) { log('Download measurement failed after retry — download result unavailable.', 'warn'); state.failedMetrics.push('Download Speed'); }
                }
                if (dlRes) {
                    state.results.download      = dlRes.download;
                    state.results.peak_download = dlRes.peak_download;
                }
                $$('.ist-hero-right') && $$('.ist-hero-right').classList.remove('ist-hero-stats-hidden');
                resetGauge();
            }
            updateProgress(65);

            if (state.cancelled) throw new Error('cancelled');

            log('Cooling down before upload phase...', 'debug');
            await _sleep(900);

            // Phase 6: Upload
            if (phaseEnabled('upload')) {
                showPhaseIndicator('upload');
                log('Phase 6: Upload Speed — Saturation Test', 'phase');
                showUploadGauge();
                switchChart('upload');
                log('Pre-warming connections for upload...', 'debug');
                await warmConnections(2);
                var ulRes = await measureUpload();
                if (!ulRes && !state.cancelled) {
                    log('Upload measurement failed — retrying once...', 'warn');
                    await _sleep(500);
                    ulRes = await measureUpload();
                    if (!ulRes) { log('Upload measurement failed after retry — upload result unavailable.', 'warn'); state.failedMetrics.push('Upload Speed'); }
                }
                if (ulRes) {
                    state.results.upload      = ulRes.upload;
                    state.results.peak_upload = ulRes.peak_upload;
                }
            }
            updateProgress(82);

            if (state.cancelled) throw new Error('cancelled');

            // Phase 7: Stability / HTTP timing
            if (phaseEnabled('stability')) {
                showPhaseIndicator('stability');
                log('Phase 7: Connection Stability & HTTP Timing', 'phase');
                setPhase('stability', 'running', 50);
                var httpTiming = await measureHTTPTiming();
                state.results = Object.assign(state.results, httpTiming);
                if (state.samples.download.length > 1) {
                    var sAvg = state.samples.download.reduce(function(a, b) { return a + b; }, 0) / state.samples.download.length;
                    var sVar = state.samples.download.reduce(function(a, b) { return a + Math.pow(b - sAvg, 2); }, 0) / state.samples.download.length;
                    state.results.stability_cov = Math.sqrt(sVar) / sAvg;
                    updateKPI('stability', (100 - state.results.stability_cov * 100).toFixed(0), '%');
                }
                setPhase('stability', 'done');
            }
            updateProgress(90);

            // Phase 7b: Platform Latency (professional mode only)
            if (phaseEnabled('platform')) {
                log('Phase 7b: Platform Latency Analysis', 'phase');
                var platRes = await measurePlatformLatency();
                if (platRes) {
                    state.results.platform_latency = platRes;
                    revealSection($$('#ist-platform-section'));
                }
            }

            // Supplemental: fill in any metrics skipped by the current mode
            if (!state.cancelled) {
                log('Supplemental: computing missing metrics...', 'debug');

                // DNS (client-side timing, near-instant)
                if (state.results.dns_ms == null) {
                    try {
                        var suppDns = await measureDNS();
                        if (suppDns && suppDns.dns_ms != null) {
                            state.results.dns_ms = suppDns.dns_ms;
                            updateKPI('dns', suppDns.dns_ms.toFixed(0), 'ms', '', suppDns.dns_ms < 80 ? 'good' : (suppDns.dns_ms < 200 ? 'warn' : 'bad'));
                        }
                    } catch (e) { log('Supplemental DNS skipped: ' + e.message, 'debug'); }
                }

                // Stability CoV from existing download samples
                if (state.results.stability_cov == null && state.samples.download.length > 1) {
                    var sAvg2 = state.samples.download.reduce(function(a, b) { return a + b; }, 0) / state.samples.download.length;
                    var sVar2 = state.samples.download.reduce(function(a, b) { return a + Math.pow(b - sAvg2, 2); }, 0) / state.samples.download.length;
                    state.results.stability_cov = Math.sqrt(sVar2) / (sAvg2 || 1);
                    var stabPct = Math.max(0, Math.min(100, (1 - state.results.stability_cov) * 100));
                    updateKPI('stability', stabPct.toFixed(0), '%', stabPct >= 80 ? 'Stable' : (stabPct >= 55 ? 'Variable' : 'Unstable'), stabPct >= 80 ? 'good' : (stabPct >= 55 ? 'warn' : 'bad'));
                }

                // HTTP timing (TLS, TTFB, HTTP round-trip) if not yet measured
                if (state.results.tls_ms == null) {
                    try {
                        var suppHttp = await measureHTTPTiming();
                        if (suppHttp) state.results = Object.assign(state.results, suppHttp);
                    } catch (e) { log('Supplemental HTTP timing skipped: ' + e.message, 'debug'); }
                }

                // Quick packet loss estimate (5 probes) if not yet measured
                if (state.results.packet_loss == null) {
                    try {
                        var origLossPings = CFG.test.PKT_LOSS_PINGS;
                        CFG.test.PKT_LOSS_PINGS = 5;
                        var suppLoss = await measurePacketLoss();
                        CFG.test.PKT_LOSS_PINGS = origLossPings;
                        if (suppLoss && suppLoss.packet_loss != null) {
                            state.results.packet_loss = suppLoss.packet_loss;
                        }
                    } catch (e) { log('Supplemental packet loss skipped: ' + e.message, 'debug'); }
                }

                // Peak DL / UL KPIs
                if (state.results.peak_download != null) updateKPI('peak-dl', state.results.peak_download >= 10 ? state.results.peak_download.toFixed(0) : state.results.peak_download.toFixed(1), 'Mbps');
                if (state.results.peak_upload   != null) updateKPI('peak-ul', state.results.peak_upload   >= 10 ? state.results.peak_upload.toFixed(0)   : state.results.peak_upload.toFixed(1),   'Mbps');
            }

            // Phase 8: Scoring
            showPhaseIndicator('scoring');
            log('Phase 8: Quality Score Analysis', 'phase');
            setPhase('scoring', 'running', 50);
            if (state.results.env) state.results = Object.assign(state.results, state.results.env);
            var scores  = calcScores(state.results);
            state.results.scores = scores;
            state.results.grade  = gradeFromScore(scores.Overall);
            state.results.score  = scores.Overall;
            state.results.timestamp = new Date().toISOString();
            renderScores(scores, state.results);

            var gradeBadge = $$('#ist-header-grade-badge');
            if (gradeBadge) {
                gradeBadge.textContent = state.results.grade;
                gradeBadge.className   = 'ist-header-grade-badge ' + state.results.grade.replace('+', '');
            }
            var gradeWrapHdr = $$('#ist-meta-grade-wrap');
            if (gradeWrapHdr) gradeWrapHdr.style.display = '';
            updateKPI('overall', state.results.score, '/100', state.results.grade, state.results.score >= 80 ? 'good' : (state.results.score >= 55 ? 'warn' : 'bad'));
            setPhase('scoring', 'done');
            updateProgress(96);

            // Show incomplete-results banner if any critical metric failed after retry
            if (state.failedMetrics && state.failedMetrics.length > 0) {
                var warnEl = $$('#ist-incomplete-warning');
                var descEl = $$('#ist-incomplete-warning-desc');
                if (descEl) {
                    var missing = state.failedMetrics.join(', ');
                    descEl.textContent = missing + ' could not be measured even after a retry. '
                        + 'The quality score below is estimated from the available data and may be inaccurate.';
                }
                if (warnEl) warnEl.style.display = 'flex';
            }

            // Reveal quality scores section after scoring completes
            revealSection($$('#ist-report-center'));

            // Phase 8b: Server Timings (professional mode only)
            var preset = CFG.presets[CFG.currentMode] || {};
            if (preset.SERVER_TIMINGS) {
                log('Phase 8b: Server Performance Timings', 'phase');
                try {
                    var srvTimings = await measureServerTimings();
                    if (srvTimings) { state.results.server_timings = srvTimings; renderServerTimings(srvTimings); }
                } catch (e) { log('Server timings skipped: ' + e.message, 'warn'); }
            }

            // Phase 9: Summary Report
            if (phaseEnabled('report')) {
                showPhaseIndicator('report');
                log('Phase 9: Generating Report', 'phase');
                setPhase('report', 'running', 70);
                var recs = generateRecommendations(state.results);
                state.results.recommendations = recs;
                renderRecommendations(recs);
                renderEnvCards(state.results.env || {});
                Object.keys(CHART_DATA).forEach(function(k) { renderChart(k); });
                updateProgress(100);
                setPhase('report', 'done');

                // Reveal recommendations + export sections now that report is complete
                revealSection($$('#ist-recommendations-section'));
                revealSection($$('#ist-export-section'));
            }

            stopTimer();
            var elapsed = ((Date.now() - state.startTime) / 1000).toFixed(1);
            state.lastTest = new Date();
            var compDl = state.results.download;
            if (compDl != null) {
                animateGauge(compDl, 'DOWNLOAD');
                setReadout(compDl >= 10 ? compDl.toFixed(0) : compDl.toFixed(1), 'Mbps', 'DONE');
            } else { setReadout('—', '', 'DONE'); }
            var compUl = state.results.upload;
            if (compUl != null) {
                animateUploadGauge(compUl);
                setUploadReadout(compUl >= 10 ? compUl.toFixed(0) : compUl.toFixed(1), 'Mbps', 'DONE');
            }
            $$('#ist-meta-last') && ($$('#ist-meta-last').textContent = 'Just now');
            $$('#ist-meta-status') && ($$('#ist-meta-status').textContent = 'Complete');
            $$('#ist-meta-dot') && ($$('#ist-meta-dot').className = 'ist-meta-dot');
            updateKPI('conn', state.results.grade, '', state.results.grade, state.results.score >= 80 ? 'good' : 'warn');
            _enableExports();

            $$('.ist-hero-left')  && $$('.ist-hero-left').classList.remove('ist-hero-stats-hidden');
            $$('.ist-hero-right') && $$('.ist-hero-right').classList.remove('ist-hero-stats-hidden');

            var updatedHist = _saveHistory(state.results);
            _renderHistoryChips(updatedHist);

            if (state.results.env && state.results.env.isp) {
                renderISPQuality(state.results.env.isp, state.results);
            }

            hidePhaseIndicator();
            showPhasesAccordion(true);

            log('Universal diagnostic complete — ' + elapsed + 's  Grade: ' + state.results.grade + '  Score: ' + state.results.score + '/100', 'success');

        } catch (e) {
            hidePhaseIndicator();
            if (e.message !== 'cancelled') {
                log('Test suite failed: ' + e.message, 'error');
                $$('#ist-meta-status') && ($$('#ist-meta-status').textContent = 'Error');
                $$('#ist-meta-dot') && ($$('#ist-meta-dot').className = 'ist-meta-dot error');
            } else {
                log('Test cancelled by user.', 'warn');
                $$('#ist-meta-status') && ($$('#ist-meta-status').textContent = 'Cancelled');
                $$('#ist-meta-dot') && ($$('#ist-meta-dot').className = 'ist-meta-dot idle');
            }
        }

        state.running = false;
        state.needsRefresh = true;
        document.body.classList.remove('test-active', 'fast', 'basic', 'professional', 'custom');
        var b = $$('#ist-start-btn');
        if (b) {
            var mLabel = CFG.presets[CFG.currentMode] ? CFG.presets[CFG.currentMode].label : 'Custom';
            b.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Run ' + mLabel + ' Test Again';
            b.classList.remove('ist-stop-btn');
        }
    }

    function cancel() {
        state.cancelled = true;
        if (_rafId)   { cancelAnimationFrame(_rafId);   _rafId   = null; }
        if (_ulRafId) { cancelAnimationFrame(_ulRafId); _ulRafId = null; }
        stopTimer();
    }

    function updateProgress(pct) {
        var fill = $$('#ist-progress-fill');
        var pctEl = $$('#ist-progress-pct');
        if (fill) fill.style.width = pct + '%';
        if (pctEl) pctEl.textContent = pct + '%';
    }

    /* ════════════════════════════════════════════════════════════════
     * Server Timings
     * ════════════════════════════════════════════════════════════════ */
    async function measureServerTimings() {
        var t0 = performance.now();
        var res = await fetch(CFG.api + '?action=server_timings&_=' + Date.now(), {
            method: 'GET', cache: 'no-store',
        });
        var latency = +(performance.now() - t0).toFixed(1);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        var data = await res.json();
        data.client_latency_ms = latency;
        return data;
    }

    function renderServerTimings(data) {
        var section = $$('#ist-server-timings-section');
        var grid    = $$('#ist-server-timings-grid');
        if (!section || !grid) return;

        var SRV_ICONS = {
            latency: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            memory:  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><line x1="6" y1="10" x2="6" y2="14"/><line x1="10" y1="10" x2="10" y2="14"/><line x1="14" y1="10" x2="14" y2="14"/><line x1="18" y1="10" x2="18" y2="14"/></svg>',
            exec:    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
            tcp80:   '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
            tcp443:  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
            clock:   '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
            php:     '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
            sapi:    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
        };
        var items = [
            { label: 'Round-trip Latency',     val: data.client_latency_ms  != null ? data.client_latency_ms + ' ms'  : '—', icon: SRV_ICONS.latency, note: 'Client → server → client' },
            { label: 'PHP Memory Peak',        val: data.memory_peak_mb     != null ? data.memory_peak_mb + ' MB'     : '—', icon: SRV_ICONS.memory,  note: 'Peak memory during request' },
            { label: 'Server Exec Time',       val: data.exec_time_ms       != null ? data.exec_time_ms + ' ms'       : '—', icon: SRV_ICONS.exec,    note: 'PHP script execution time' },
            { label: 'TCP Socket (80)',        val: data.tcp_port80_ms      != null ? data.tcp_port80_ms + ' ms'      : (data.tcp_port80_ms === null ? 'Filtered' : '—'), icon: SRV_ICONS.tcp80,  note: 'Outbound TCP to 8.8.8.8:80' },
            { label: 'TCP Socket (443)',       val: data.tcp_port443_ms     != null ? data.tcp_port443_ms + ' ms'     : (data.tcp_port443_ms === null ? 'Filtered' : '—'), icon: SRV_ICONS.tcp443, note: 'Outbound TCP to 8.8.8.8:443' },
            { label: 'Server Timestamp',       val: data.server_time        || '—',                                      icon: SRV_ICONS.clock,   note: 'Server clock (UTC)' },
            { label: 'PHP Version',            val: data.php_version        || '—',                                      icon: SRV_ICONS.php,     note: 'Runtime version' },
            { label: 'SAPI',                   val: data.sapi               || '—',                                      icon: SRV_ICONS.sapi,    note: 'Server API interface' },
        ];

        grid.innerHTML = '';
        items.forEach(function(item) {
            var card = document.createElement('div');
            card.className = 'ist-srv-card';
            card.innerHTML =
                '<div class="ist-srv-icon">' + item.icon + '</div>' +
                '<div class="ist-srv-body">' +
                  '<div class="ist-srv-val">' + item.val + '</div>' +
                  '<div class="ist-srv-label">' + item.label + '</div>' +
                  '<div class="ist-srv-note">' + item.note + '</div>' +
                '</div>';
            grid.appendChild(card);
        });

        section.style.display = '';
        log('Server timings: latency=' + data.client_latency_ms + 'ms  mem=' + data.memory_peak_mb + 'MB  exec=' + data.exec_time_ms + 'ms', 'info');
    }

    /* ════════════════════════════════════════════════════════════════
     * ISP Quality Assessment
     * ════════════════════════════════════════════════════════════════ */
    var ISP_QUALITY_TABLE = [
        { match: /fiber|fibre|fios|ftth|fttn|gpon|ont/i,         tier: 'Fiber',     color: 'success', desc: 'Fiber-optic connection — excellent symmetrical speeds, low latency, high reliability.' },
        { match: /cable|docsis|coax|comcast|xfinity|spectrum/i,  tier: 'Cable',     color: 'primary', desc: 'Cable (DOCSIS) connection — fast downstream, moderate upload, occasional congestion at peak hours.' },
        { match: /dsl|adsl|vdsl|copper|telephone/i,              tier: 'DSL',       color: 'warning', desc: 'DSL connection — speeds and latency depend heavily on distance from the DSLAM (exchange).' },
        { match: /satellite|starlink|viasat|hughes|geosat/i,     tier: 'Satellite', color: 'danger',  desc: 'Satellite connection — high latency (~600 ms for GEO, ~40 ms for LEO) expected; good for rural areas.' },
        { match: /mobile|cellular|lte|5g|4g|3g|gsm|umts/i,      tier: 'Mobile',    color: 'warning', desc: 'Mobile/cellular connection — speeds vary widely; latency and throughput affected by signal strength and congestion.' },
        { match: /wireless|wimax|fixed wireless|lmds/i,          tier: 'Fixed Wireless', color: 'warning', desc: 'Fixed wireless — depends on tower proximity and line-of-sight; latency typically 10–50 ms.' },
        { match: /business|enterprise|dedicated|leased|corporate/i, tier: 'Business', color: 'success', desc: 'Business/dedicated connection — typically includes guaranteed SLAs, symmetrical speeds, and priority routing.' },
    ];

    function renderISPQuality(isp, results) {
        var el = $$('#ist-isp-strip');
        if (!el) return;

        var ispName = isp.isp || isp.org || '';
        var tierInfo = null;
        for (var i = 0; i < ISP_QUALITY_TABLE.length; i++) {
            if (ISP_QUALITY_TABLE[i].match.test(ispName)) {
                tierInfo = ISP_QUALITY_TABLE[i];
                break;
            }
        }

        var dl   = results.download;
        var ul   = results.upload;
        var ping = results.ping;

        var perfStr = '';
        if (dl != null && ul != null) {
            var ratio = dl > 0 ? (ul / dl) : 0;
            var symLabel = ratio >= 0.7 ? '≈ Symmetrical' : (ratio >= 0.3 ? 'Asymmetrical' : 'Heavily asymmetrical');
            perfStr = dl.toFixed(0) + '↓ / ' + ul.toFixed(0) + '↑ Mbps (' + symLabel + ')';
        }

        var pingLabel = '';
        if (ping != null) {
            pingLabel = ping < 20 ? 'Excellent latency' : ping < 60 ? 'Good latency' : ping < 150 ? 'Moderate latency' : 'High latency';
        }

        var colorVar = tierInfo ? '--color-' + tierInfo.color : '--color-text-muted';
        var tierLabel = tierInfo ? tierInfo.tier : 'Unknown';
        var tierDesc  = tierInfo ? tierInfo.desc : 'Connection type could not be determined from ISP name.';

        el.innerHTML = el.innerHTML +
                (perfStr   ? '<div class="ist-isp-quality-stat">' + perfStr + '</div>'   : '') +
                (pingLabel ? '<div class="ist-isp-quality-stat">' + pingLabel + '</div>' : '');
    }

    /* ════════════════════════════════════════════════════════════════
     * Individual diagnostic test runner
     * ════════════════════════════════════════════════════════════════ */
    var DIAG_TESTS = {
        'ping':        { label: 'Ping Test',           fn: async function() { var r = await measurePing(); return r ? r.ping.toFixed(1) + ' ms avg latency' : 'Failed'; } },
        'download':    { label: 'Download Test',       fn: async function() { var r = await measureDownload(); return r ? r.download.toFixed(1) + ' Mbps' : 'Failed'; } },
        'upload':      { label: 'Upload Test',         fn: async function() { var r = await measureUpload(); return r ? r.upload.toFixed(1) + ' Mbps' : 'Failed'; } },
        'jitter':      { label: 'Jitter Test',         fn: async function() { var r = await measurePing(); return r ? r.jitter.toFixed(1) + ' ms jitter' : 'Failed'; } },
        'pktloss':     { label: 'Packet Loss',         fn: async function() { var r = await measurePacketLoss(); return r ? r.packet_loss.toFixed(1) + '% packet loss' : 'Failed'; } },
        'dns':         { label: 'DNS Resolution',      fn: async function() { var r = await measureDNS(); return r ? r.dns_ms.toFixed(0) + ' ms DNS avg' : 'Failed'; } },
        'http':        { label: 'HTTP Response',       fn: async function() { var r = await measureHTTPTiming(); return r ? (r.live_rtt_ms || 0).toFixed(0) + ' ms HTTP RTT' : 'Failed'; } },
        'env':         { label: 'Browser Capabilities',fn: async function() { var r = await measureEnv(); renderEnvCards(r); return 'Environment profiled'; } },
        'stability':   { label: 'Connection Stability',fn: async function() {
            var samples = []; for (var i = 0; i < 10; i++) { var t = await _ping(); if (t !== null) samples.push(t); await _sleep(100); }
            if (!samples.length) return 'Failed';
            var mn = Math.min.apply(null, samples), mx = Math.max.apply(null, samples);
            return 'Stable: ' + mn.toFixed(0) + '–' + mx.toFixed(0) + ' ms range';
        }},
    };

    async function runDiagnostic(id) {
        var test  = DIAG_TESTS[id];
        var card  = $$('#ist-diag-' + id);
        var resEl = card && card.querySelector('.ist-diag-result');
        var dot   = card && card.querySelector('.ist-diag-status');
        var btn   = card && card.querySelector('.ist-diag-run-btn');

        if (!test || (btn && btn.disabled)) return;
        if (btn) { btn.disabled = true; btn.textContent = 'Running...'; }
        if (dot) dot.className = 'ist-diag-status running';
        if (resEl) { resEl.textContent = 'Running...'; resEl.className = 'ist-diag-result visible'; }

        log('Running: ' + test.label, 'phase');
        try {
            var result = await test.fn();
            state.diagResults[id] = result;
            if (dot) dot.className = 'ist-diag-status done';
            if (resEl) resEl.textContent = result;
            log(test.label + ': ' + result, 'success');
        } catch (e) {
            if (dot) dot.className = 'ist-diag-status error';
            if (resEl) { resEl.textContent = 'Error: ' + e.message; }
            log(test.label + ' failed: ' + e.message, 'error');
        }

        if (btn) { btn.disabled = false; btn.textContent = 'Run Again'; }

        // After any individual diagnostic, show the terminal section
        revealSection($$('#ist-terminal-section'));
        // Only reveal export section if a full test has been run with real results
        if (state.results && state.results.download != null) {
            revealSection($$('#ist-export-section'));
        }
    }

    /* ════════════════════════════════════════════════════════════════
     * Export functions
     * ════════════════════════════════════════════════════════════════ */
    function _enableExports() {
        $$$('.ist-export-btn[disabled]').forEach(function(b) { b.removeAttribute('disabled'); });
    }

    function _download(content, filename, mime) {
        var blob = new Blob([content], { type: mime });
        var url  = URL.createObjectURL(blob);
        var a    = document.createElement('a');
        a.href = url; a.download = filename;
        document.body.appendChild(a); a.click();
        setTimeout(function() { URL.revokeObjectURL(url); document.body.removeChild(a); }, 1000);
    }

    function exportJSON() {
        var r = state.results;
        if (!r || !Object.keys(r).length) { alert('No test results yet. Run a test first.'); return; }
        var data = Object.assign({}, r, { generated_by: 'Awan Tools Internet Speed Test', url: location.href });
        _download(JSON.stringify(data, null, 2), CFG.export.filename + '.json', 'application/json');
        log('Exported results as JSON', 'info');
    }

    function exportCSV() {
        var r = state.results;
        if (!r || !Object.keys(r).length) { alert('No test results yet. Run a test first.'); return; }
        var rows = [['Metric', 'Value', 'Unit']];
        if (r.download    != null) rows.push(['Download Speed',    r.download.toFixed(2),    'Mbps']);
        if (r.upload      != null) rows.push(['Upload Speed',      r.upload.toFixed(2),      'Mbps']);
        if (r.ping        != null) rows.push(['Latency (Ping)',    r.ping.toFixed(2),         'ms']);
        if (r.jitter      != null) rows.push(['Jitter',            r.jitter.toFixed(2),       'ms']);
        if (r.packet_loss != null) rows.push(['Packet Loss',       r.packet_loss.toFixed(2),  '%']);
        if (r.dns_ms      != null) rows.push(['DNS Resolution',    r.dns_ms.toFixed(2),       'ms']);
        if (r.ttfb_ms     != null) rows.push(['TTFB',              r.ttfb_ms.toFixed(2),      'ms']);
        if (r.tls_ms      != null) rows.push(['TLS Handshake',     r.tls_ms.toFixed(2),       'ms']);
        if (r.score       != null) rows.push(['Overall Score',     r.score,                   '/100']);
        if (r.grade             )  rows.push(['Grade',             r.grade,                   '']);
        if (r.timestamp         )  rows.push(['Timestamp',         r.timestamp,               '']);
        if (r.scores) {
            Object.keys(r.scores).forEach(function(k) { rows.push([k + ' Score', r.scores[k], '/100']); });
        }
        var csv = rows.map(function(row) { return row.map(function(c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(','); }).join('\n');
        _download(csv, CFG.export.filename + '.csv', 'text/csv');
        log('Exported results as CSV', 'info');
    }

    function exportTXT() {
        var r = state.results;
        if (!r || !Object.keys(r).length) { alert('No test results yet. Run a test first.'); return; }
        var lines = [
            'INTERNET SPEED TEST REPORT',
            'Generated: ' + (r.timestamp || new Date().toISOString()),
            'Platform:  Awan Tools — awantools.site',
            '',
            'CONNECTION RESULTS',
            '──────────────────────────────────',
        ];
        if (r.download    != null) lines.push('Download Speed : ' + r.download.toFixed(1)    + ' Mbps');
        if (r.upload      != null) lines.push('Upload Speed   : ' + r.upload.toFixed(1)      + ' Mbps');
        if (r.ping        != null) lines.push('Latency (Ping) : ' + r.ping.toFixed(1)        + ' ms');
        if (r.jitter      != null) lines.push('Jitter         : ' + r.jitter.toFixed(1)      + ' ms');
        if (r.packet_loss != null) lines.push('Packet Loss    : ' + r.packet_loss.toFixed(1) + ' %');
        if (r.dns_ms      != null) lines.push('DNS Resolution : ' + r.dns_ms.toFixed(0)      + ' ms');
        if (r.tls_ms      != null) lines.push('TLS Handshake  : ' + r.tls_ms.toFixed(0)      + ' ms');
        if (r.ttfb_ms     != null) lines.push('Time to 1st Byte: ' + r.ttfb_ms.toFixed(0)   + ' ms');
        lines.push('');
        if (r.score != null) {
            lines.push('QUALITY SCORES');
            lines.push('──────────────────────────────────');
            lines.push('Overall Score  : ' + r.score + '/100  Grade: ' + r.grade);
            if (r.scores) Object.keys(r.scores).forEach(function(k) {
                lines.push((k + '          ').slice(0, 16) + ': ' + r.scores[k] + '/100  ' + gradeFromScore(r.scores[k]));
            });
        }
        if (r.recommendations && r.recommendations.length) {
            lines.push('');
            lines.push('RECOMMENDATIONS');
            lines.push('──────────────────────────────────');
            r.recommendations.forEach(function(rec, i) { lines.push((i + 1) + '. ' + rec); });
        }
        lines.push('');
        lines.push('Generated by Awan Tools Internet Speed Test');
        _download(lines.join('\n'), CFG.export.filename + '.txt', 'text/plain');
        log('Exported results as TXT', 'info');
    }

    function exportPDF() {
        var r = state.results;
        if (!r || !Object.keys(r).length) { alert('No test results yet. Run a test first.'); return; }
        var accent = '#4f46e5';
        var dateStr = new Date(r.timestamp || Date.now()).toLocaleDateString();
        var timeStr = new Date(r.timestamp || Date.now()).toLocaleTimeString();

        // All key metrics
        var metricDefs = [
            { label: 'Download',      val: r.download      != null ? r.download.toFixed(1)      + ' Mbps' : '—', color: '#10b981' },
            { label: 'Upload',        val: r.upload        != null ? r.upload.toFixed(1)        + ' Mbps' : '—', color: '#3b82f6' },
            { label: 'Peak Download', val: r.peak_download != null ? r.peak_download.toFixed(1) + ' Mbps' : '—', color: '#34d399' },
            { label: 'Peak Upload',   val: r.peak_upload   != null ? r.peak_upload.toFixed(1)   + ' Mbps' : '—', color: '#60a5fa' },
            { label: 'Ping',          val: r.ping          != null ? r.ping.toFixed(1)          + ' ms'   : '—', color: '#8b5cf6' },
            { label: 'Jitter',        val: r.jitter        != null ? r.jitter.toFixed(1)        + ' ms'   : '—', color: '#f59e0b' },
            { label: 'Min Ping',      val: r.min_ping      != null ? r.min_ping.toFixed(1)      + ' ms'   : '—', color: '#a78bfa' },
            { label: 'Max Ping',      val: r.max_ping      != null ? r.max_ping.toFixed(1)      + ' ms'   : '—', color: '#c4b5fd' },
            { label: 'Packet Loss',   val: r.packet_loss   != null ? r.packet_loss.toFixed(1)   + '%'     : '—', color: '#ef4444' },
            { label: 'DNS Lookup',    val: r.dns_ms        != null ? r.dns_ms.toFixed(0)        + ' ms'   : '—', color: '#06b6d4' },
            { label: 'TLS Handshake', val: r.tls_ms        != null ? r.tls_ms.toFixed(0)        + ' ms'   : '—', color: '#f97316' },
            { label: 'TTFB',          val: r.ttfb_ms       != null ? r.ttfb_ms.toFixed(0)       + ' ms'   : '—', color: '#ec4899' },
            { label: 'HTTP RTT',      val: r.live_rtt_ms   != null ? r.live_rtt_ms.toFixed(0)   + ' ms'   : '—', color: '#14b8a6' },
            { label: 'Grade',         val: r.grade  || '—', color: '#10b981' },
            { label: 'Score',         val: r.score  != null ? r.score + '/100' : '—', color: '#4f46e5' },
        ];
        var metricsHtml = '';
        metricDefs.forEach(function(m) {
            metricsHtml += '<div class="pdf-metric"><div class="pdf-metric-dot" style="background:' + m.color + '"></div>' +
                '<span class="pdf-metric-label">' + m.label + '</span><span class="pdf-metric-val">' + m.val + '</span></div>';
        });

        // Environment section
        var env = r.env || r;
        var envHtml = '';
        var envFields = [
            ['IP Address',       env.ip || '—'],
            ['ISP',              env.isp || '—'],
            ['Location',         env.location || (env.city && env.country ? env.city + ', ' + env.country : '—')],
            ['Browser',          env.browser || '—'],
            ['Platform',         env.platform || '—'],
            ['Connection Type',  env.connection_type || '—'],
            ['Effective Type',   env.effective_type  || '—'],
            ['Network Downlink', env.downlink || '—'],
            ['Memory',           env.device_memory   ? env.device_memory + ' GB' : '—'],
            ['CPU Cores',        env.cpu_cores || '—'],
            ['Screen',           env.screen || '—'],
            ['Timezone',         env.timezone || '—'],
            ['WebRTC',           env.webrtc || '—'],
            ['WebGL',            env.webgl || '—'],
            ['WebSocket',        env.websocket || '—'],
        ];
        envFields.forEach(function(f) {
            if (f[1] && f[1] !== '—') {
                envHtml += '<div class="pdf-module"><div class="pdf-module-title">' + f[0] + '</div>' +
                    '<div class="pdf-module-status">' + f[1] + '</div></div>';
            }
        });

        var scoresHtml = '';
        if (r.scores) {
            Object.keys(r.scores).forEach(function(k) {
                var s = r.scores[k], g = gradeFromScore(s);
                var fill = s >= 80 ? '#10b981' : (s >= 55 ? '#f59e0b' : '#ef4444');
                scoresHtml += '<div class="pdf-module"><div class="pdf-module-title">' + k + '</div>' +
                    '<div class="pdf-module-status">Grade ' + g + ' — ' + s + '/100</div>' +
                    '<div style="height:4px;background:#e2e8f0;border-radius:2px;overflow:hidden"><div style="height:100%;background:' + fill + ';width:' + s + '%"></div></div></div>';
            });
        }

        var recHtml = '';
        if (r.recommendations) {
            r.recommendations.forEach(function(rec, i) {
                recHtml += '<div class="pdf-module" style="grid-column:1/-1"><span style="font-weight:700;color:' + accent + '">' + (i + 1) + '. </span>' + rec + '</div>';
            });
        }

        var html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Speed Test Report</title><style>' +
            'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;font-size:12px;color:#1e293b;background:#fff;line-height:1.5;margin:0}' +
            '.pdf-header{background:' + accent + ';color:#fff;padding:32px 48px 28px;display:flex;align-items:flex-start;justify-content:space-between}' +
            '.pdf-brand-name{font-size:18px;font-weight:700}.pdf-brand-tag{font-size:11px;opacity:.75;margin-top:2px}' +
            '.pdf-header-right{text-align:right;font-size:11px;opacity:.85}' +
            '.pdf-header-right strong{display:block;font-size:22px;font-weight:800;opacity:1;margin-bottom:4px}' +
            '.pdf-meta{background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:14px 48px;display:flex;flex-wrap:wrap;gap:24px}' +
            '.pdf-meta-item{display:flex;flex-direction:column;gap:2px}' +
            '.pdf-meta-item label{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8}' +
            '.pdf-meta-item span{font-size:12px;font-weight:500}' +
            '.pdf-metrics-wrap{padding:16px 48px;border-bottom:1px solid #e2e8f0}' +
            '.pdf-metrics-title{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:8px}' +
            '.pdf-metrics{display:flex;flex-wrap:wrap;gap:8px}' +
            '.pdf-metric{display:flex;align-items:center;gap:6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:6px 10px;min-width:120px}' +
            '.pdf-metric-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}' +
            '.pdf-metric-label{font-size:10px;color:#64748b;flex:1}.pdf-metric-val{font-size:11px;font-weight:600}' +
            '.pdf-body{padding:20px 32px 60px}' +
            '.pdf-section{margin-bottom:24px;page-break-inside:avoid}' +
            '.pdf-section-title{font-size:13px;font-weight:700;margin-bottom:10px;padding-bottom:6px;border-bottom:2px solid #f1f5f9}' +
            '.pdf-modules{display:grid;grid-template-columns:1fr 1fr;gap:8px}' +
            '.pdf-modules-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}' +
            '.pdf-module{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px;page-break-inside:avoid}' +
            '.pdf-module-title{font-size:11px;font-weight:700;margin-bottom:3px;color:#64748b;text-transform:uppercase;letter-spacing:.05em;font-size:9px}' +
            '.pdf-module-status{font-size:12px;font-weight:600;color:#1e293b}' +
            '.pdf-footer{position:fixed;bottom:0;left:0;right:0;padding:10px 48px;background:#f8fafc;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;font-size:9.5px;color:#94a3b8}' +
            '@media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact}.pdf-header{-webkit-print-color-adjust:exact;print-color-adjust:exact}@page{size:A4;margin:18mm 14mm 22mm}}' +
            '</style></head><body>' +
            '<div class="pdf-header"><div><div class="pdf-brand-name">Awan Tools</div><div class="pdf-brand-tag">Internet Speed Test · Network Diagnostics Report</div></div>' +
            '<div class="pdf-header-right"><strong>' + (r.score || '—') + '/100</strong>' + dateStr + ' at ' + timeStr + '<div style="background:rgba(255,255,255,.2);border-radius:20px;padding:2px 10px;font-size:10px;font-weight:600;display:inline-block;margin-top:4px">Grade ' + (r.grade || '—') + '</div></div></div>' +
            '<div class="pdf-meta">' +
            '<div class="pdf-meta-item"><label>Generated</label><span>' + dateStr + ', ' + timeStr + '</span></div>' +
            '<div class="pdf-meta-item"><label>Overall Score</label><span>' + (r.score || '—') + '/100</span></div>' +
            '<div class="pdf-meta-item"><label>Grade</label><span>' + (r.grade || '—') + '</span></div>' +
            (env.ip ? '<div class="pdf-meta-item"><label>IP Address</label><span>' + env.ip + '</span></div>' : '') +
            (env.isp ? '<div class="pdf-meta-item"><label>ISP</label><span>' + env.isp + '</span></div>' : '') +
            '<div class="pdf-meta-item"><label>Platform</label><span>Awan Tools · Internet Speed Test</span></div>' +
            '</div>' +
            '<div class="pdf-metrics-wrap"><div class="pdf-metrics-title">All Metrics</div><div class="pdf-metrics">' + metricsHtml + '</div></div>' +
            '<div class="pdf-body">' +
            (scoresHtml ? '<div class="pdf-section"><div class="pdf-section-title">Quality Scores</div><div class="pdf-modules">' + scoresHtml + '</div></div>' : '') +
            (envHtml ? '<div class="pdf-section"><div class="pdf-section-title">Environment & Network</div><div class="pdf-modules-3">' + envHtml + '</div></div>' : '') +
            (recHtml ? '<div class="pdf-section"><div class="pdf-section-title">Recommendations</div><div class="pdf-modules">' + recHtml + '</div></div>' : '') +
            '</div>' +
            '<div class="pdf-footer"><span>Awan Tools — awantools.site</span><span>Internet Speed Test Report</span><span>' + dateStr + '</span></div>' +
            '<script>window.onload=function(){window.print();}<\/script></body></html>';

        var win = window.open('', '_blank', 'width=900,height=700');
        if (!win) { alert('Allow popups to generate the PDF report.'); return; }
        win.document.open(); win.document.write(html); win.document.close();
        log('PDF report opened in new window', 'info');
    }

    function copyClipboard() {
        var r = state.results;
        if (!r || !Object.keys(r).length) { alert('No results yet.'); return; }
        var txt = 'Internet Speed Test Results\n' +
            'Download: ' + (r.download ? r.download.toFixed(1) + ' Mbps' : '—') + '\n' +
            'Upload: '   + (r.upload   ? r.upload.toFixed(1)   + ' Mbps' : '—') + '\n' +
            'Ping: '     + (r.ping     ? r.ping.toFixed(1)     + ' ms'   : '—') + '\n' +
            'Jitter: '   + (r.jitter   ? r.jitter.toFixed(1)  + ' ms'   : '—') + '\n' +
            'Loss: '     + (r.packet_loss != null ? r.packet_loss.toFixed(1) + '%' : '—') + '\n' +
            'Grade: '    + (r.grade || '—') + '  Score: ' + (r.score != null ? r.score + '/100' : '—') + '\n' +
            'via Awan Tools — ' + location.origin + '/plugins/internet-speed-test/';
        var btn = $$('#ist-export-clip-label');
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(txt).then(function() {
                if (btn) { btn.textContent = 'Copied!'; setTimeout(function() { btn.textContent = 'Clipboard'; }, 2000); }
            });
        } else {
            var ta = document.createElement('textarea');
            ta.value = txt; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); if (btn) { btn.textContent = 'Copied!'; setTimeout(function() { btn.textContent = 'Clipboard'; }, 2000); } } catch (e) {}
            document.body.removeChild(ta);
        }
        log('Results copied to clipboard', 'info');
    }

    function copyCurl() {
        var r = state.results;
        var params = [];
        if (r.download    != null) params.push('dl='    + r.download.toFixed(1));
        if (r.upload      != null) params.push('ul='    + r.upload.toFixed(1));
        if (r.ping        != null) params.push('ping='  + r.ping.toFixed(1));
        if (r.jitter      != null) params.push('jitter='+ r.jitter.toFixed(1));
        if (r.packet_loss != null) params.push('loss='  + r.packet_loss.toFixed(1));
        if (r.grade             )  params.push('grade=' + r.grade);
        if (r.score        != null) params.push('score='+ r.score);
        if (r.ip                )  params.push('ip='    + encodeURIComponent(r.ip));
        params.push('ts=' + encodeURIComponent(r.timestamp || new Date().toISOString()));

        var url = location.origin + CFG.api + '?action=report&' + params.join('&');
        var cmd = 'curl "' + url + '"';

        var el = $$('#ist-curl-cmd');
        if (el) el.textContent = cmd;

        var label = $$('#ist-curl-label');
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(cmd).then(function() {
                if (label) { label.textContent = 'Copied!'; setTimeout(function() { label.textContent = 'Copy'; }, 2200); }
            });
        } else {
            var ta = document.createElement('textarea');
            ta.value = cmd; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); if (label) { label.textContent = 'Copied!'; setTimeout(function() { label.textContent = 'Copy'; }, 2200); } } catch (e) { prompt('Copy this command:', cmd); }
            document.body.removeChild(ta);
        }
        log('cURL command copied to clipboard', 'info');
    }

    /* ════════════════════════════════════════════════════════════════
     * Terminal UI interactions
     * ════════════════════════════════════════════════════════════════ */
    function setTermFilter(filter) {
        state.termFilter = filter;
        $$('.ist-term-filter').forEach(function(b) { b.classList.toggle('active', b.dataset.filter === filter); });
        _applyTermFilters();
    }

    function setTermSearch(val) {
        state.termSearch = (val || '').trim();
        _applyTermFilters();
    }

    function toggleTermSearch() {
        var s = $$('#ist-term-search');
        if (s) { s.classList.toggle('open'); if (s.classList.contains('open')) s.querySelector('input').focus(); }
    }

    function clearTerminal() {
        var body = $$('#ist-terminal-body');
        if (body) body.innerHTML = '';
        state.termLogs = [];
    }

    function exportLogs() {
        var txt = state.termLogs.map(function(e) { return '[' + e.time + '] [' + e.level.toUpperCase() + '] ' + e.msg; }).join('\n');
        _download(txt, 'speed-test-logs.txt', 'text/plain');
    }

    function toggleTermPause() {
        state.termPaused = !state.termPaused;
        var btn = $$('#ist-term-pause');
        if (btn) btn.textContent = state.termPaused ? 'Resume' : 'Pause';
    }

    /* ════════════════════════════════════════════════════════════════
     * Chart tab switching
     * ════════════════════════════════════════════════════════════════ */
    function switchChart(tab) {
        $$$('.ist-chart-tab').forEach(function(b) { b.classList.toggle('active', b.dataset.tab === tab); });
        $$$('.ist-chart-pane').forEach(function(p) { p.classList.toggle('active', p.id === 'ist-chart-pane-' + tab); });
        renderChart(tab);
    }

    /* ════════════════════════════════════════════════════════════════
     * Initialisation
     * ════════════════════════════════════════════════════════════════ */
    function setConnectionMode(mode) {
        var connModes = { wifi: 'WiFi', cellular: 'Cellular', ethernet: 'Ethernet', auto: 'Auto' };
        if (!connModes[mode]) return;
        log('Connection mode set to: ' + connModes[mode], 'info');
        var btns = document.querySelectorAll('.ist-conn-btn');
        btns.forEach(function(b) { b.classList.toggle('active', b.dataset.conn === mode); });
    }

    function initEnvProfile() {
        var nav = navigator, conn = nav.connection || nav.mozConnection || nav.webkitConnection || {};
        var quickEnv = {
            browser:         _detectBrowser(),
            platform:        nav.platform || nav.userAgentData && nav.userAgentData.platform || '—',
            language:        nav.language || '—',
            timezone:        Intl && Intl.DateTimeFormat ? Intl.DateTimeFormat().resolvedOptions().timeZone : '—',
            color_scheme:    window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'Dark' : 'Light',
            touch:           ('ontouchstart' in window || navigator.maxTouchPoints > 0) ? 'Yes' : 'No',
            cookie_enabled:  nav.cookieEnabled ? 'Yes' : 'No',
            device_memory:   nav.deviceMemory ? nav.deviceMemory + ' GB' : '—',
            cpu_cores:       nav.hardwareConcurrency ? nav.hardwareConcurrency + ' cores' : '—',
            pixel_ratio:     (window.devicePixelRatio || 1).toFixed(1),
            webrtc:          (typeof RTCPeerConnection !== 'undefined' || typeof webkitRTCPeerConnection !== 'undefined') ? 'Supported' : 'Not supported',
            webgl:           (function() { try { return !!document.createElement('canvas').getContext('webgl') ? 'Supported' : 'Not supported'; } catch(e) { return 'Not supported'; } })(),
            websocket:       typeof WebSocket !== 'undefined' ? 'Supported' : 'Not supported',
            service_worker:  'serviceWorker' in nav ? 'Supported' : 'Not supported',
            connection_type: conn.type || '—',
            effective_type:  conn.effectiveType || '—',
            downlink:        conn.downlink != null ? conn.downlink + ' Mbps' : '—',
            rtt_est:         conn.rtt != null ? conn.rtt + ' ms' : '—',
            save_data:       conn.saveData ? 'Yes' : 'No',
            screen:          screen.width + '×' + screen.height,
        };
        renderEnvCards(quickEnv);
        // Also fetch IP/ISP/geo immediately on page load
        fetch(CFG.api + '?action=myip').then(function(r) { return r.json(); }).then(function(data) {
            if (data && data.ip) {
                renderEnvCards(Object.assign(quickEnv, {
                    ip:       data.ip,
                    is_ipv6:  data.is_ipv6,
                    isp:      data.isp || {},
                }));
            }
        }).catch(function() {});
    }

    /* ════════════════════════════════════════════════════════════════
     * Professional custom ping/jitter tool
     * ════════════════════════════════════════════════════════════════ */
    /* ════════════════════════════════════════════════════════════════
     * Deep Latency Analyzer
     * ════════════════════════════════════════════════════════════════ */
    async function runLatencyAnalyzer() {
        var urlEl  = $$('#ist-la-url');
        var btn    = $$('#ist-la-run-btn');
        var resEl  = $$('#ist-la-result');
        if (!urlEl || !resEl) return;

        var rawUrl = (urlEl.value || '').trim();
        if (!rawUrl) { urlEl.focus(); return; }
        if (!/^https?:\/\//i.test(rawUrl)) rawUrl = 'https://' + rawUrl;

        var count      = parseInt(($$('#ist-la-count')        || {value:'10'}).value, 10) || 10;
        var method     = ($$('#ist-la-method')                || {value:'HEAD'}).value || 'HEAD';
        var connTout   = parseInt(($$('#ist-la-conn-timeout') || {value:'5'}).value,  10) || 5;
        var reqTout    = parseInt(($$('#ist-la-req-timeout')  || {value:'10'}).value, 10) || 10;

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Analyzing…';
        }
        resEl.innerHTML = '<div class="ist-la-loading">Probing <strong>' + _escHtml(rawUrl) + '</strong> with ' + count + ' request' + (count===1?'':'s') + '…</div>';

        try {
            var fd = new FormData();
            fd.append('url',          rawUrl);
            fd.append('count',        count);
            fd.append('method',       method);
            fd.append('conn_timeout', connTout);
            fd.append('req_timeout',  reqTout);

            var resp = await fetch(API + '?action=latency_analyze', { method: 'POST', body: fd });
            var data = await resp.json();

            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg> Analyze';
            }

            if (!data.ok) {
                resEl.innerHTML = '<div class="ist-la-error">⚠ ' + _escHtml(data.error || 'Analysis failed.') + '</div>';
                return;
            }
            resEl.innerHTML = _laRender(data);

        } catch (err) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg> Analyze';
            }
            resEl.innerHTML = '<div class="ist-la-error">⚠ ' + _escHtml(err.message || 'Network error') + '</div>';
        }
    }

    /* ── Render helpers ─────────────────────────────────────────────── */

    function _laLatCls(ms) {
        if (ms == null || ms <= 0) return 'neutral';
        return ms <= 50 ? 'excellent' : ms <= 100 ? 'good' : ms <= 200 ? 'fair' : 'poor';
    }

    function _laFmt(v, dec) {
        if (v == null) return '—';
        return typeof v === 'number' ? v.toFixed(dec != null ? dec : 1) : String(v);
    }

    function _laRatingToCls(r) {
        return {'Excellent':'excellent','Good':'good','Fair':'fair','Poor':'poor'}[r] || 'neutral';
    }

    function _laStat(label, val, cls) {
        return '<div class="ist-la-stat ist-la-' + (cls||'neutral') + '">' +
            '<span class="ist-la-stat-val">' + _escHtml(String(val)) + '</span>' +
            '<span class="ist-la-stat-label">' + label + '</span>' +
            '</div>';
    }

    function _laRender(d) {
        var s    = d.stats    || {};
        var seg  = d.segments || {};
        var meta = d.meta     || {};
        var rat  = d.ratings  || {};
        var probes = (d.probes || []);
        var okProbes = probes.filter(function(p) { return p.ok; });

        var h = '<div class="ist-la-result-wrap">';

        /* 1. Meta bar */
        h += '<div class="ist-la-meta-bar">';
        h += '<span class="ist-la-meta-url">' + _escHtml(d.host || d.url || '') + '</span>';
        if (meta.ip) h += '<span class="ist-la-meta-chip">' + _escHtml(meta.ip) + '</span>';
        if (meta.http_version) h += '<span class="ist-la-meta-chip">' + _escHtml(meta.http_version) + '</span>';
        if (meta.http_code) {
            var codeCls = meta.http_code >= 200 && meta.http_code < 300 ? 'ok' : meta.http_code < 400 ? 'redir' : 'err';
            h += '<span class="ist-la-meta-chip ist-la-code-' + codeCls + '">' + meta.http_code + '</span>';
        }
        if (meta.server) h += '<span class="ist-la-meta-chip ist-la-meta-server">' + _escHtml((meta.server+'').substring(0,36)) + '</span>';
        h += '<span class="ist-la-meta-chip ist-la-meta-timing">' + d.duration_ms + 'ms total test</span>';
        h += '</div>';

        /* 2. Overall rating + avg */
        var ov = rat.overall || {};
        h += '<div class="ist-la-overall ist-la-' + (ov.cls || 'neutral') + '">';
        h += '<span class="ist-la-overall-label">' + (ov.label || '—') + '</span>';
        h += '<span class="ist-la-overall-score">' + (ov.score || 0) + ' / 100</span>';
        h += '<span class="ist-la-overall-avg">' + _laFmt(s.avg) + ' ms avg</span>';
        h += '</div>';

        /* 3. Stats grid */
        h += '<div class="ist-la-stat-grid">';
        h += _laStat('Average',     _laFmt(s.avg)    + ' ms', _laLatCls(s.avg));
        h += _laStat('Median',      _laFmt(s.median) + ' ms', _laLatCls(s.median));
        h += _laStat('Min',         _laFmt(s.min)    + ' ms', 'excellent');
        h += _laStat('Max',         _laFmt(s.max)    + ' ms', _laLatCls(s.max));
        h += _laStat('Std Dev',     _laFmt(s.stddev) + ' ms', s.stddev < 10 ? 'excellent' : s.stddev < 30 ? 'fair' : 'poor');
        h += _laStat('Jitter',      _laFmt(s.jitter) + ' ms', s.jitter  <  5 ? 'excellent' : s.jitter  < 20 ? 'good' : s.jitter < 50 ? 'fair' : 'poor');
        h += _laStat('P95',         _laFmt(s.p95)    + ' ms', _laLatCls(s.p95));
        h += _laStat('Packet Loss', _laFmt(s.packet_loss) + '%', s.packet_loss < 1 ? 'excellent' : s.packet_loss < 5 ? 'good' : 'poor');
        h += '</div>';

        /* 4. Segment rating badges */
        h += '<div class="ist-la-ratings-row">';
        [['Overall',rat.overall],['TTFB',rat.ttfb],['DNS',rat.dns],['TCP',rat.tcp],['TLS',rat.tls]].forEach(function(pair) {
            var name = pair[0], r = pair[1] || {};
            if (!r.label) return;
            h += '<div class="ist-la-rating-badge ist-la-' + (r.cls||'neutral') + '">';
            h += '<span class="ist-la-rb-name">' + name + '</span>';
            h += '<span class="ist-la-rb-label">' + r.label + '</span>';
            h += '</div>';
        });
        h += '</div>';

        /* 5. Segment timing breakdown */
        h += '<div class="ist-la-section-title">Timing Breakdown</div>';
        h += '<div class="ist-la-segments">';
        var segDefs = [
            ['DNS Lookup',    'dns',      '#6366f1'],
            ['TCP Connect',   'tcp',      '#0ea5e9'],
            ['TLS Handshake', 'tls',      '#10b981'],
            ['TTFB',          'ttfb',     '#f59e0b'],
            ['Transfer',      'transfer', '#8b5cf6'],
        ];
        var totalAvgMs = (seg.total && seg.total.avg != null) ? seg.total.avg :
            segDefs.reduce(function(acc, sd) { var sg = seg[sd[1]]; return acc + (sg && sg.avg ? sg.avg : 0); }, 0);
        totalAvgMs = totalAvgMs || 1;

        segDefs.forEach(function(sd) {
            var key = sd[1], color = sd[2], sg = seg[key];
            if (!sg || sg.avg == null) return;
            var pct = Math.min(100, Math.max(1, Math.round((sg.avg / totalAvgMs) * 100)));
            h += '<div class="ist-la-seg-row">';
            h += '<span class="ist-la-seg-label">' + sd[0] + '</span>';
            h += '<div class="ist-la-seg-bar-wrap"><div class="ist-la-seg-bar" style="width:' + pct + '%;background:' + color + '" title="' + sg.avg.toFixed(3) + 'ms avg"></div></div>';
            h += '<span class="ist-la-seg-val">' + sg.avg.toFixed(1) + ' ms</span>';
            h += '<span class="ist-la-seg-range">(' + (sg.min!=null?sg.min.toFixed(1):'—') + '–' + (sg.max!=null?sg.max.toFixed(1):'—') + ')</span>';
            h += '</div>';
        });
        /* Total row */
        var tsg = seg.total || {};
        h += '<div class="ist-la-seg-row ist-la-seg-total">';
        h += '<span class="ist-la-seg-label">Total</span>';
        h += '<div class="ist-la-seg-bar-wrap"><div class="ist-la-seg-bar" style="width:100%;background:var(--ist-accent)"></div></div>';
        h += '<span class="ist-la-seg-val"><strong>' + (tsg.avg!=null?tsg.avg.toFixed(1):'—') + ' ms</strong></span>';
        h += '<span class="ist-la-seg-range">' + (tsg.min!=null?'(' + tsg.min.toFixed(1) + '–' + tsg.max.toFixed(1) + ')':'') + '</span>';
        h += '</div></div>';

        /* 6. Per-probe chart */
        if (okProbes.length >= 2) {
            h += '<div class="ist-la-section-title">Per-Probe Latency</div>';
            h += _laProbeChart(okProbes);
        }

        /* 7. Probe details table */
        h += '<div class="ist-la-section-title">Probe Details <span class="ist-la-section-sub">(' + okProbes.length + '/' + d.count + ' succeeded';
        if (s.outliers_removed > 0) h += ', ' + s.outliers_removed + ' outlier' + (s.outliers_removed>1?'s':'') + ' excluded from stats';
        h += ')</span></div>';
        h += _laProbeTable(probes);

        /* 8. Metadata (collapsible) */
        h += '<div class="ist-la-section-title ist-la-meta-toggle" onclick="IST._laToggleMeta(this)">';
        h += 'Response Metadata <span class="ist-la-chevron">▾</span></div>';
        h += '<div class="ist-la-meta-detail" style="display:none">' + _laMetaPanel(meta, d) + '</div>';

        /* 9. History */
        if (d.history && d.history.length) {
            h += '<div class="ist-la-section-title">Test History (previous ' + d.history.length + ' runs)</div>';
            h += _laHistoryTable(d.history);
        }

        h += '</div>';
        return h;
    }

    function _laProbeChart(probes) {
        var vals = probes.map(function(p) { return p.total_ms || 0; });
        var maxV = Math.max.apply(null, vals) || 1;
        var n    = vals.length;
        var W = 560, H = 100, pl = 34, pr = 10, pt = 8, pb = 20;
        var cw = W - pl - pr, ch = H - pt - pb;
        var bw = Math.max(2, Math.floor(cw / n) - 3);
        var slotW = cw / n;

        var svg = '<svg viewBox="0 0 ' + W + ' ' + H + '" class="ist-la-probe-chart" preserveAspectRatio="xMidYMid meet">';

        /* grid lines */
        [0.25, 0.5, 0.75, 1].forEach(function(f) {
            var y = pt + ch - Math.round(f * ch);
            svg += '<line x1="' + pl + '" y1="' + y + '" x2="' + (W-pr) + '" y2="' + y + '" stroke="rgba(255,255,255,0.06)" stroke-width="1"/>';
            if (f === 0.5 || f === 1) {
                var lab = Math.round(maxV * f);
                svg += '<text x="' + (pl-3) + '" y="' + (y+3) + '" fill="rgba(255,255,255,0.3)" font-size="8" text-anchor="end">' + lab + '</text>';
            }
        });

        /* bars */
        vals.forEach(function(v, i) {
            var x  = pl + i * slotW + (slotW - bw) / 2;
            var bh = Math.max(2, Math.round((v / maxV) * ch));
            var y  = pt + ch - bh;
            var fc = v <= 50 ? '#10b981' : v <= 100 ? '#6366f1' : v <= 200 ? '#f59e0b' : '#ef4444';
            svg += '<rect x="' + x.toFixed(1) + '" y="' + y.toFixed(1) + '" width="' + bw + '" height="' + bh + '" fill="' + fc + '" rx="2" opacity="0.85">';
            svg += '<title>Probe ' + (i+1) + ': ' + v.toFixed(1) + 'ms</title></rect>';
        });

        /* avg line */
        var avg = vals.reduce(function(s,v){return s+v;},0)/n;
        var ay  = pt + ch - Math.round((avg / maxV) * ch);
        svg += '<line x1="' + pl + '" y1="' + ay + '" x2="' + (W-pr) + '" y2="' + ay + '" stroke="rgba(255,255,255,0.35)" stroke-width="1" stroke-dasharray="4,3"/>';
        svg += '<text x="' + (W-pr-2) + '" y="' + (ay-3) + '" fill="rgba(255,255,255,0.4)" font-size="8" text-anchor="end">avg ' + avg.toFixed(1) + 'ms</text>';

        /* probe-number x axis labels (sparse) */
        var labelEvery = Math.max(1, Math.ceil(n / 10));
        vals.forEach(function(v, i) {
            if ((i+1) % labelEvery !== 0 && i !== 0 && i !== n-1) return;
            var lx = pl + i * slotW + slotW / 2;
            svg += '<text x="' + lx.toFixed(0) + '" y="' + (H-4) + '" fill="rgba(255,255,255,0.25)" font-size="7" text-anchor="middle">' + (i+1) + '</text>';
        });

        svg += '</svg>';
        return '<div class="ist-la-chart-wrap">' + svg + '</div>';
    }

    function _laProbeTable(probes) {
        var h = '<div class="ist-la-probe-table-wrap"><table class="ist-la-probe-table">';
        h += '<thead><tr><th>#</th><th>DNS</th><th>TCP</th><th>TLS</th><th>TTFB</th><th>Transfer</th><th>Total</th><th>Code</th></tr></thead><tbody>';
        probes.forEach(function(p) {
            if (!p.ok) {
                h += '<tr class="ist-la-probe-fail"><td>' + p.n + '</td><td colspan="7" class="ist-la-probe-err">Failed: ' + _escHtml(p.error || 'Unknown error') + '</td></tr>';
                return;
            }
            h += '<tr class="ist-la-probe-ok' + (p.is_fresh ? ' ist-la-probe-fresh' : '') + '">';
            h += '<td>' + p.n + (p.is_fresh ? '<span class="ist-la-fresh-tag">DNS</span>' : '') + '</td>';
            h += '<td>' + _laFmt(p.dns_ms,  2) + '</td>';
            h += '<td>' + _laFmt(p.tcp_ms,  2) + '</td>';
            h += '<td>' + (p.tls_ms > 0 ? _laFmt(p.tls_ms, 2) : '—') + '</td>';
            h += '<td class="' + _laLatCls(p.ttfb_ms) + '">'  + _laFmt(p.ttfb_ms, 2) + '</td>';
            h += '<td>' + _laFmt(p.transfer_ms, 2) + '</td>';
            h += '<td class="ist-la-total-cell ' + _laLatCls(p.total_ms) + '"><strong>' + _laFmt(p.total_ms, 2) + '</strong></td>';
            var cc = p.http_code >= 200 && p.http_code < 300 ? 'ok' : 'err';
            h += '<td><span class="ist-la-code ist-la-code-' + cc + '">' + (p.http_code || '—') + '</span></td>';
            h += '</tr>';
        });
        h += '</tbody></table></div>';
        return h;
    }

    function _laMetaPanel(meta, d) {
        var s = d.stats || {};
        var items = [
            ['Server IP',        meta.ip],
            ['Port',             meta.port ? meta.port : null],
            ['HTTP Version',     meta.http_version],
            ['HTTP Status',      meta.http_code],
            ['Content-Type',     meta.content_type],
            ['Server',           meta.server],
            ['X-Powered-By',     meta.x_powered_by],
            ['Cache-Control',    meta.cache_control],
            ['CDN / Via',        meta.via || meta.x_cache || (meta.cf_ray ? 'Cloudflare (CF-Ray: '+meta.cf_ray+')' : null)],
            ['Redirects',        meta.redirect_count ? meta.redirect_count + ' redirect(s)' + (meta.redirect_url ? ' → ' + meta.redirect_url : '') : null],
            ['SSL Verify',       meta.ssl_verify === 0 ? 'OK' : (meta.ssl_verify > 0 ? 'Error code ' + meta.ssl_verify : null)],
            ['Test method',      d.method],
            ['Probes sent',      (s.succeeded||0) + '/' + (d.count||0) + ' succeeded'],
            ['Outliers removed', s.outliers_removed || 0],
            ['DNS (OS fresh)',   s.dns_fresh_ms != null ? s.dns_fresh_ms.toFixed(3) + ' ms' : null],
            ['Total test time',  d.duration_ms + ' ms'],
        ];
        var h = '<div class="ist-la-meta-grid">';
        items.forEach(function(item) {
            if (item[1] == null || item[1] === '') return;
            h += '<div class="ist-la-meta-item"><span class="ist-la-meta-key">' + item[0] + '</span><span class="ist-la-meta-val">' + _escHtml(String(item[1])) + '</span></div>';
        });
        h += '</div>';
        return h;
    }

    function _laHistoryTable(hist) {
        var h = '<div class="ist-la-history-wrap"><table class="ist-la-history-table">';
        h += '<thead><tr><th>Date / Time</th><th>Avg</th><th>P95</th><th>Jitter</th><th>Loss</th><th>TTFB</th><th>Rating</th></tr></thead><tbody>';
        hist.forEach(function(row) {
            var dt  = new Date((row.tested_at || 0) * 1000);
            var ts  = dt.toLocaleDateString([],{month:'short',day:'numeric'}) + ' ' + dt.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
            var avg = row.avg_ms != null ? parseFloat(row.avg_ms).toFixed(1) : '—';
            h += '<tr>';
            h += '<td>' + ts + '</td>';
            h += '<td class="' + _laLatCls(row.avg_ms) + '">' + avg + '</td>';
            h += '<td>' + (row.p95_ms    != null ? parseFloat(row.p95_ms).toFixed(1)    : '—') + '</td>';
            h += '<td>' + (row.jitter_ms != null ? parseFloat(row.jitter_ms).toFixed(1) : '—') + '</td>';
            h += '<td>' + (row.packet_loss != null ? parseFloat(row.packet_loss).toFixed(1)+'%' : '0%') + '</td>';
            h += '<td>' + (row.ttfb_ms   != null ? parseFloat(row.ttfb_ms).toFixed(1)   : '—') + '</td>';
            h += '<td><span class="ist-la-rating-pill ist-la-' + _laRatingToCls(row.rating) + '">' + _escHtml(row.rating || '—') + '</span></td>';
            h += '</tr>';
        });
        h += '</tbody></table></div>';
        return h;
    }

    /* Toggle metadata panel */
    function _laToggleMeta(el) {
        var next = el && el.nextElementSibling;
        if (!next) return;
        var open = next.style.display !== 'none';
        next.style.display = open ? 'none' : 'block';
        var ch = el.querySelector('.ist-la-chevron');
        if (ch) ch.textContent = open ? '▾' : '▴';
    }

    async function runCustomPing() {
        var input = $$('#ist-pro-url-input');
        var btn   = $$('#ist-pro-ping-btn');
        var resEl = $$('#ist-pro-ping-result');
        if (!input || !resEl) return;

        var raw = (input.value || '').trim();
        if (!raw) { alert('Please enter a URL or domain.'); return; }

        // Normalize to URL
        var url = raw;
        if (!/^https?:\/\//i.test(url)) url = 'https://' + url;

        // Add a cache-buster favicon path if no path given
        try {
            var u = new URL(url);
            if (u.pathname === '/') u.pathname = '/favicon.ico';
            url = u.href;
        } catch(e) {}

        var SAMPLES = 10;
        if (btn) { btn.disabled = true; btn.textContent = 'Probing...'; }
        resEl.innerHTML = '<div class="ist-pro-ping-running">Sending ' + SAMPLES + ' probes to <strong>' + _escHtml(raw) + '</strong>…</div>';

        var times = [];
        for (var i = 0; i < SAMPLES; i++) {
            try {
                var t0 = performance.now();
                var ac = new AbortController();
                var tid = setTimeout(function() { ac.abort(); }, 4000);
                await fetch(url + '?_nc=' + Date.now(), { mode: 'no-cors', cache: 'no-store', signal: ac.signal });
                clearTimeout(tid);
                times.push(+(performance.now() - t0).toFixed(1));
            } catch(e) {
                // timed out or blocked
            }
            // brief gap between probes
            await new Promise(function(res) { setTimeout(res, 150); });
        }

        if (btn) { btn.disabled = false; btn.textContent = 'Run Test'; }

        if (!times.length) {
            resEl.innerHTML = '<div class="ist-pro-ping-error">All probes timed out or were blocked by CORS. Try a different URL.</div>';
            return;
        }

        var min = Math.min.apply(null, times);
        var max = Math.max.apply(null, times);
        var avg = +(times.reduce(function(s,v){ return s+v; }, 0) / times.length).toFixed(1);
        var jitter = +(Math.sqrt(times.reduce(function(s,v){ return s + Math.pow(v - avg, 2); }, 0) / times.length)).toFixed(1);
        var loss   = +(((SAMPLES - times.length) / SAMPLES) * 100).toFixed(0);

        var pingCls  = avg < 60 ? 'good' : avg < 120 ? 'warn' : 'bad';
        var jitCls   = jitter < 10 ? 'good' : jitter < 30 ? 'warn' : 'bad';
        var lossCls  = loss < 5 ? 'good' : loss < 20 ? 'warn' : 'bad';

        resEl.innerHTML =
            '<div class="ist-pro-ping-header">Results for <strong>' + _escHtml(raw) + '</strong> (' + times.length + '/' + SAMPLES + ' probes succeeded)</div>' +
            '<div class="ist-pro-ping-grid">' +
                _proPingMetric('Avg Latency', avg + ' ms', pingCls) +
                _proPingMetric('Min', min + ' ms', 'good') +
                _proPingMetric('Max', max + ' ms', max > 200 ? 'bad' : 'warn') +
                _proPingMetric('Jitter', jitter + ' ms', jitCls) +
                _proPingMetric('Packet Loss', loss + '%', lossCls) +
                _proPingMetric('Samples', times.length + '/' + SAMPLES, 'neutral') +
            '</div>' +
            '<div class="ist-pro-ping-samples">Raw samples: ' + times.map(function(v) { return v + 'ms'; }).join(' · ') + '</div>';
    }

    function _proPingMetric(label, value, cls) {
        return '<div class="ist-pro-ping-metric ist-pro-ping-' + cls + '">' +
            '<span class="ist-pro-ping-m-label">' + label + '</span>' +
            '<span class="ist-pro-ping-m-val">' + value + '</span>' +
            '</div>';
    }

    /* ════════════════════════════════════════════════════════════════
     * Traceroute / Hop Analyzer
     * ════════════════════════════════════════════════════════════════ */
    async function runTraceroute() {
        var input  = $$('#ist-trace-host-input');
        var btn    = $$('#ist-trace-run-btn');
        var resEl  = $$('#ist-trace-result');
        if (!input || !resEl) return;

        var raw = (input.value || '').trim();
        if (!raw) { input.focus(); return; }

        // Normalize — strip protocol, keep host only
        var host = raw.replace(/^https?:\/\//i, '').split('/')[0].split('?')[0].trim();

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" opacity=".3"/><path d="M12 2a10 10 0 0 1 0 20" stroke-dasharray="16" stroke-dashoffset="0"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur=".8s" repeatCount="indefinite"/></path></svg> Tracing…';
        }
        resEl.innerHTML = '<div class="ist-trace-running"><span class="ist-trace-spinner"></span>Tracing route to <strong>' + _escHtml(host) + '</strong>… this may take up to 15 s.</div>';

        try {
            var res  = await fetch(CFG.api + '?action=traceroute&host=' + encodeURIComponent(host) + '&hops=15&_=' + Date.now());
            var data = await res.json();

            if (data.error) {
                resEl.innerHTML = '<div class="ist-trace-error">' + _escHtml(data.error) + '</div>';
                return;
            }

            var hops   = data.hops || [];
            var maxAvg = Math.max.apply(null, hops.map(function(h) { return h.avg || 0; }).concat([1]));

            if (!hops.length) {
                resEl.innerHTML = '<div class="ist-trace-error">No hops returned. The host may be unreachable or firewalled.</div>';
                return;
            }

            // Type → icon SVG
            var TYPE_ICONS = {
                dns:      '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
                waypoint: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>',
                tcp:      '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
                tls:      '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
                ttfb:     '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
            };

            var html = '<div class="ist-trace-summary">Network path analysis to <strong>' + _escHtml(data.host) + '</strong>' + (data.ip ? ' (' + _escHtml(data.ip) + ')' : '') + '</div>';

            if (data.mode === 'tcp_analysis') {
                html += '<div class="ist-trace-note">Server-side TCP/curl timing analysis — shows DNS, TCP, TLS, and response latency at each stage.</div>';
            }

            html += '<div class="ist-trace-table">' +
                '<div class="ist-trace-header ist-trace-header-path">' +
                  '<span>#</span><span>Stage / Node</span><span>RTT</span><span>IP / Note</span><span>Latency</span>' +
                '</div>';

            hops.forEach(function(hop) {
                var q = !hop.ok ? 'timeout' : (hop.avg == null ? 'timeout' : (hop.avg < 20 ? 'good' : (hop.avg < 80 ? 'warn' : 'bad')));
                var barPct  = hop.avg && maxAvg > 0 ? Math.min(100, (hop.avg / maxAvg) * 100) : 0;
                var rttDisp = hop.avg != null ? hop.avg + ' ms' : '—';
                var icon    = TYPE_ICONS[hop.type] || TYPE_ICONS.waypoint;

                html += '<div class="ist-trace-row ist-trace-q-' + q + '">' +
                    '<span class="ist-trace-hop">' + hop.hop + '</span>' +
                    '<span class="ist-trace-label">' + icon + ' ' + _escHtml(hop.label) + '</span>' +
                    '<span class="ist-trace-rtt">' + rttDisp + '</span>' +
                    '<span class="ist-trace-probes">' + _escHtml((hop.ip || '') + (hop.note ? (hop.ip ? ' · ' : '') + hop.note : '')) + '</span>' +
                    '<span class="ist-trace-bar-wrap"><span class="ist-trace-bar ist-trace-bar-' + q + '" style="width:' + barPct.toFixed(1) + '%"></span></span>' +
                    '</div>';
            });

            html += '</div>';
            resEl.innerHTML = html;

        } catch (e) {
            resEl.innerHTML = '<div class="ist-trace-error">Request failed: ' + _escHtml(e.message) + '</div>';
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg> Trace Route';
            }
        }
    }

    function _escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function init() {
        initGauge();
        initUploadGauge();

        // Initialize test mode (sets button states, estimated time, start label)
        setMode(CFG.currentMode);

        initEnvProfile();

        // Initial chart render
        Object.keys(CHART_DATA).forEach(function(k) { renderChart(k); });

        // Update cURL placeholder
        var curlEl = $$('#ist-curl-cmd');
        if (curlEl) curlEl.textContent = 'curl "' + location.origin + CFG.api + '?action=report&dl=0&ul=0&ping=0"';

        // Set status
        $$('#ist-meta-status') && ($$('#ist-meta-status').textContent = 'Ready');
        $$('#ist-meta-last')   && ($$('#ist-meta-last').textContent   = 'Never');
        $$('#ist-meta-dot')    && ($$('#ist-meta-dot').className = 'ist-meta-dot idle');

        // Hide hero stat columns until a test completes
        $$('.ist-hero-left')  && $$('.ist-hero-left').classList.add('ist-hero-stats-hidden');
        $$('.ist-hero-right') && $$('.ist-hero-right').classList.add('ist-hero-stats-hidden');

        // Load any saved history and show panel if entries exist
        var hist = _loadHistory();
        _renderHistoryChips(hist);
    }

    /* ════════════════════════════════════════════════════════════════
     * Offline detection
     * ════════════════════════════════════════════════════════════════ */
    var _HIDEABLE_SECTIONS = [
        '.ist-header', '.ist-hero', '.ist-kpi-grid', '.ist-charts-section',
        '.ist-terminal-section', '.ist-scores-section', '.ist-recommendations-section',
        '.ist-env-section', '.ist-diagnostics-section', '.ist-export-section',
        '.ist-history-panel', '.ist-mode-selector-row',
    ];

    function _showOfflineBanner(show) {
        var banner = $$('#ist-offline-banner');
        if (!banner) return;
        banner.style.display = show ? 'flex' : 'none';
        _HIDEABLE_SECTIONS.forEach(function(sel) {
            document.querySelectorAll(sel).forEach(function(el) {
                el.style.display = show ? 'none' : '';
            });
        });
        if (show && state.running) cancel();
    }

    window.addEventListener('offline', function() { _showOfflineBanner(true); });
    window.addEventListener('online',  function() { _showOfflineBanner(false); });

    document.addEventListener('DOMContentLoaded', function() {
        init();
        // Check initial connectivity
        if (!navigator.onLine) _showOfflineBanner(true);

        // Show shared results if ?share= param is present
        var sp = new URLSearchParams(window.location.search);
        if (sp.has('share')) {
            try { showSharedResults(JSON.parse(atob(sp.get('share')))); } catch (e) { /* malformed */ }
        }

        // Auto-start if ?run param is in the URL
        if (sp.has('run')) {
            var mode = sp.get('mode') || 'basic';
            if (!CFG.presets[mode] && mode !== 'custom') mode = 'basic';
            if (mode === 'custom') {
                var paramMap = { pc: 'PING_COUNT', lp: 'PKT_LOSS_PINGS', dls: 'DL_STREAMS', dld: 'DL_DURATION', uls: 'UL_STREAMS', uld: 'UL_DURATION' };
                Object.keys(paramMap).forEach(function(k) {
                    var v = parseInt(sp.get(k), 10);
                    if (!isNaN(v) && v > 0) CFG.test[paramMap[k]] = v;
                });
            }
            setMode(mode);
            setTimeout(runFull, 400);
        }

        // Inject ▶ Run buttons into cURL command reference
        initCurlRunButtons();
    });

    /* ─── DNS Propagation Checker ──────────────────────────────────── */
    async function runDnsPropagation(prefillDomain) {
        var input  = $$('#ist-dns-domain-input');
        var sel    = $$('#ist-dns-type-select');
        var btn    = $$('#ist-dns-run-btn');
        var resEl  = $$('#ist-dns-result');
        if (!resEl) return;

        if (prefillDomain && input) input.value = prefillDomain;

        var domain = (input ? input.value : '').trim();
        if (!domain) { if (input) input.focus(); return; }
        var type   = sel ? sel.value : 'A';

        domain = domain.replace(/^https?:\/\//i, '').split('/')[0].split(':')[0].trim();

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" opacity=".3"/><path d="M12 2a10 10 0 0 1 0 20" stroke-dasharray="16" stroke-dashoffset="0"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur=".8s" repeatCount="indefinite"/></path></svg> Checking…';
        }
        resEl.innerHTML = '<div class="ist-trace-running"><span class="ist-trace-spinner"></span>Querying 8 global resolvers for <strong>' + _escHtml(domain) + '</strong> <span class="ist-dns-type-badge">' + _escHtml(type) + '</span>…</div>';

        try {
            var res  = await fetch(CFG.api + '?action=dns_propagation&domain=' + encodeURIComponent(domain) + '&type=' + encodeURIComponent(type) + '&_=' + Date.now());
            var data = await res.json();

            if (data.error) {
                resEl.innerHTML = '<div class="ist-trace-error">' + _escHtml(data.error) + '</div>';
                return;
            }

            var resolvers   = data.resolvers || [];
            var consistent  = data.consistent;
            var okCount     = data.ok_count || 0;
            var total       = data.total || resolvers.length;

            // Banner
            var bannerClass = consistent ? 'consistent' : 'inconsistent';
            var bannerIcon  = consistent
                ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
            var bannerText  = consistent
                ? okCount + '/' + total + ' resolvers returned results — records are propagated consistently.'
                : 'Inconsistent results detected — DNS may still be propagating or some resolvers are blocking this domain.';

            var html = '<div class="ist-dns-banner ' + bannerClass + '">' + bannerIcon + bannerText + '</div>';
            html += '<div class="ist-dns-table">';
            html += '<div class="ist-dns-header"><span>Resolver</span><span>Location</span><span>' + _escHtml(type) + ' Record(s)</span><span>Latency</span></div>';

            var STATUS_LABEL = { ok: 'OK', nxdomain: 'NXDOMAIN', nodata: 'No Data', timeout: 'Timeout', error: 'Error', servfail: 'SERVFAIL' };
            var STATUS_ICONS = {
                ok:       '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
                nxdomain: '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
                nodata:   '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
                timeout:  '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg>',
                servfail: '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
                error:    '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
            };

            resolvers.forEach(function(r) {
                var statusCls  = r.status || 'error';
                var statusIcon = STATUS_ICONS[statusCls] || STATUS_ICONS.error;
                var statusLbl  = STATUS_LABEL[statusCls] || r.status;
                var latDisp    = r.latency != null ? r.latency + ' ms' : '—';

                var answersHtml = '';
                if (r.answers && r.answers.length) {
                    answersHtml = r.answers.map(function(a) {
                        var ttlStr = a.ttl != null ? '<span class="ist-dns-answer-ttl">TTL ' + a.ttl + 's</span>' : '';
                        return '<div class="ist-dns-answer-item"><span class="ist-dns-answer-val">' + _escHtml(a.data) + '</span>' + ttlStr + '</div>';
                    }).join('');
                } else {
                    answersHtml = '<span class="ist-dns-row-noanswer">' + statusLbl + '</span>';
                }

                html +=
                    '<div class="ist-dns-row">' +
                      '<div class="ist-dns-row-resolver">' +
                        '<div class="ist-dns-row-name">' + _escHtml(r.resolver) + '</div>' +
                        '<div class="ist-dns-row-org">' + _escHtml(r.org || '') + '</div>' +
                      '</div>' +
                      '<div class="ist-dns-row-region">' + _escHtml(r.region || '') + '</div>' +
                      '<div class="ist-dns-row-answers">' + answersHtml + '</div>' +
                      '<div class="ist-dns-row-latency">' +
                        '<div class="ist-dns-status ' + statusCls + '">' + statusIcon + ' ' + statusLbl + '</div>' +
                        '<div class="ist-dns-lat">' + latDisp + '</div>' +
                      '</div>' +
                    '</div>';
            });

            html += '</div>';
            resEl.innerHTML = html;

        } catch (e) {
            resEl.innerHTML = '<div class="ist-trace-error">Request failed: ' + _escHtml(e.message) + '</div>';
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg> Check Propagation';
            }
        }
    }

    /* ─── Professional Tools Accordion ─────────────────────────────── */
    function toggleProTools() {
        var body    = $$('#ist-pro-tools-body');
        var trigger = $$('#ist-pro-tools-header');
        if (!body) return;
        var open = body.style.display !== 'none';
        body.style.display = open ? 'none' : '';
        if (trigger) trigger.setAttribute('aria-expanded', String(!open));
    }

    /* ─── cURL Run in Browser ───────────────────────────────────────── */
    function curlRun(btn, runType) {
        var cmdEl = btn.closest('.ist-curl-cmd');
        if (!cmdEl) return;
        var code  = cmdEl.querySelector('.ist-curl-code');
        if (!code) return;
        var text  = code.textContent;

        // Extract first https?:// URL from command text
        var match = text.match(/https?:\/\/([a-zA-Z0-9.\-_~:/?#\[\]@!$&'()*+,;=%]+)/);
        if (!match) return;
        var fullUrl = match[0];
        var host    = fullUrl.replace(/^https?:\/\//i, '').split('/')[0].split('?')[0].split(':')[0];

        if (runType === 'dns') {
            var dnsInput = $$('#ist-dns-domain-input');
            var dnsCard  = $$('#ist-dns-result');
            if (dnsInput) dnsInput.value = host;
            if (dnsCard) dnsCard.closest('.ist-pro-tool-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
            runDnsPropagation(host);
        } else {
            var traceInput = $$('#ist-trace-host-input');
            var traceCard  = $$('#ist-trace-result');
            if (traceInput) traceInput.value = host;
            if (traceCard) traceCard.closest('.ist-pro-tool-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
            runTraceroute();
        }
    }

    /* ─── Auto-inject Run buttons into cURL commands ────────────────── */
    function initCurlRunButtons() {
        var cmds = document.querySelectorAll('.ist-curl-cmd');
        cmds.forEach(function(el) {
            var code = el.querySelector('.ist-curl-code');
            if (!code) return;
            var text = code.textContent;
            if (!text.match(/https?:\/\//)) return;   // no URL → no run button

            // Detect DNS group
            var groupTitle = el.closest('.ist-curl-group');
            groupTitle = groupTitle && groupTitle.querySelector('.ist-curl-group-title');
            var isDns = groupTitle && /dns/i.test(groupTitle.textContent);
            var runType = isDns ? 'dns' : 'trace';

            var PLAY_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>';
            var runBtn = document.createElement('span');
            runBtn.className = 'ist-curl-run-btn' + (isDns ? ' dns-type' : '');
            runBtn.title     = isDns ? 'Check in DNS Propagation Checker' : 'Analyze in Network Path Analyzer';
            runBtn.setAttribute('aria-label', runBtn.title);
            runBtn.innerHTML = PLAY_SVG;
            runBtn.onclick   = function(e) { e.stopPropagation(); curlRun(runBtn, runType); };
            el.appendChild(runBtn);
        });
    }

    /* ─── cURL copy helper ─────────────────────────────────────────── */
    function curlCopy(el) {
        const code = el.querySelector('.ist-curl-code');
        if (!code) return;
        const text = code.textContent;
        navigator.clipboard.writeText(text).then(function() {
            el.classList.add('copied');
            const btn = el.querySelector('.ist-curl-copy-btn');
            if (btn) {
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
            }
            setTimeout(function() {
                el.classList.remove('copied');
                if (btn) {
                    btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
                }
            }, 1800);
        }).catch(function() {
            /* fallback: select the text */
            const range = document.createRange();
            range.selectNodeContents(code);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        });
    }

    /* ════════════════════════════════════════════════════════════════
     * Public API
     * ════════════════════════════════════════════════════════════════ */
    return {
        run:                   runFull,
        cancel:                cancel,
        runDiagnostic:         runDiagnostic,
        switchChart:           switchChart,
        setTermFilter:         setTermFilter,
        setTermSearch:         setTermSearch,
        toggleTermSearch:      toggleTermSearch,
        clearTerminal:         clearTerminal,
        exportLogs:            exportLogs,
        toggleTermPause:       toggleTermPause,
        exportJSON:            exportJSON,
        exportCSV:             exportCSV,
        exportTXT:             exportTXT,
        exportPDF:             exportPDF,
        copyClipboard:         copyClipboard,
        copyCurl:              copyCurl,
        togglePhasesAccordion: togglePhasesAccordion,
        toggleHistory:         toggleHistory,
        restoreFromHistory:    restoreFromHistory,
        setMode:               setMode,
        scrollToReport:        scrollToReport,
        applyCustomParams:     applyCustomParams,
        shareResults:          shareResults,
        switchPlatformTab:     switchPlatformTab,
        runLatencyAnalyzer:    runLatencyAnalyzer,
        _laToggleMeta:         _laToggleMeta,
        runCustomPing:         runCustomPing,
        runTraceroute:         runTraceroute,
        runDnsPropagation:     runDnsPropagation,
        curlCopy:              curlCopy,
        curlRun:               curlRun,
        toggleProTools:        toggleProTools,
        setConnectionMode:     setConnectionMode,
        getResults:            function() { return state.results; },
        getConfig:             function() { return CFG; },
    };
})();
