<?php
defined('AWAN') or require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';

$slug = 'internet-speed-test';

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

ob_start();
?>
<link rel="stylesheet" href="/plugins/<?= $slug ?>/assets/speed-test.css?v=<?= filemtime(__DIR__ . '/assets/speed-test.css') ?>">

<div class="ist-app">

<!-- ── Header ────────────────────────────────────────────────────────────── -->
<div class="ist-header">
  <div class="ist-header-left">
    <div class="ist-brand-row">
      <svg class="ist-brand-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
      <span class="ist-brand-name">Internet Speed Test</span>
    </div>
    <div class="ist-brand-sub">Universal Internet Speed & Network Diagnostics Suite</div>
  </div>
  <div class="ist-header-meta">
    <span class="ist-meta-item">
      <span class="ist-meta-dot idle" id="ist-meta-dot"></span>
      <span id="ist-meta-status">Initializing</span>
    </span>
    <span class="ist-meta-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Last Test: <strong id="ist-meta-last">Never</strong>
    </span>
    <span class="ist-meta-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 6l11 6 11-6M1 6v12l11 6 11-6V6"/></svg>
      Elapsed: <strong id="ist-elapsed">0s</strong>
    </span>
    <span class="ist-meta-item" id="ist-meta-countdown-wrap" style="display:none;">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/><path d="M22 12h-2"/></svg>
      Remaining: <strong id="ist-meta-countdown">—</strong>
    </span>
    <span class="ist-meta-item" id="ist-meta-grade-wrap" style="display:none;">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      Grade: <strong><span id="ist-header-grade-badge" class="ist-header-grade-badge">—</span></strong>
    </span>
  </div>
  <div class="ist-header-toolbar">
    <a href="#ist-export-section" class="ist-tool-btn" title="Jump to export section">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export
    </a>
    <button class="ist-tool-btn" id="ist-share-btn" onclick="IST.shareResults()" title="Copy shareable link to clipboard">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
      <span id="ist-share-btn-label">Share</span>
    </button>
    <a class="ist-tool-btn" href="/plugins/network-toolkit" title="Open Network Toolkit">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
      Network Toolkit
    </a>
  </div>
</div>

<!-- ── Shared Results Panel (shown when ?share= param is present) ─────────── -->
<div id="ist-shared-panel" class="ist-shared-panel" style="display:none;" aria-label="Shared speed test results"></div>

<!-- ── ISP Info Strip ─────────────────────────────────────────────────────── -->
<div id="ist-isp-strip" class="ist-isp-strip" style="display:none;" aria-label="ISP information">
  <span id="ist-isp-flag" class="ist-isp-flag"></span>
  <span id="ist-isp-name" class="ist-isp-name">—</span>
  <span class="ist-isp-sep">·</span>
  <span id="ist-isp-location" class="ist-isp-location">—</span>
  <span class="ist-isp-sep">·</span>
  <span id="ist-isp-asn" class="ist-isp-asn">—</span>
</div>
<div id="ist-isp-quality" class="ist-isp-quality" style="display:none;" aria-label="ISP quality assessment">
</div>

<!-- ── Hero — Gauge + KPIs ───────────────────────────────────────────────── -->
<div class="ist-hero" aria-label="Speed test dashboard">

  <!-- Left stat column -->
  <div class="ist-hero-left" style="text-align:right;align-items:flex-end;">
    <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end;">
      <span style="font-size:11px;font-weight:600;color:var(--color-text-subtle);text-transform:uppercase;letter-spacing:.08em;">Latency</span>
      <div style="font-size:36px;font-weight:700;color:var(--color-text);line-height:1;" id="ist-hero-ping">—<span style="font-size:14px;font-weight:400;color:var(--color-text-muted);margin-left:3px;">ms</span></div>
      <svg id="ist-spark-ping-hero" viewBox="0 0 80 28" preserveAspectRatio="none" style="width:80px;height:28px;overflow:visible;"><polyline class="ist-spark-path" points="0,14 80,14"/></svg>
    </div>
    <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end;margin-top:16px;">
      <span style="font-size:11px;font-weight:600;color:var(--color-text-subtle);text-transform:uppercase;letter-spacing:.08em;">Jitter</span>
      <div style="font-size:36px;font-weight:700;color:var(--color-text);line-height:1;" id="ist-hero-jitter">—<span style="font-size:14px;font-weight:400;color:var(--color-text-muted);margin-left:3px;">ms</span></div>
      <svg id="ist-spark-jitter-hero" viewBox="0 0 80 28" preserveAspectRatio="none" style="width:80px;height:28px;overflow:visible;"><polyline class="ist-spark-path" points="0,14 80,14"/></svg>
    </div>
  </div>

  <!-- Center gauge -->
  <div class="ist-gauge-wrap">
    <!-- Dual gauge container: download always visible, upload appears after DL phase -->
    <div class="ist-dual-gauge-area" id="ist-dual-gauge-area">

      <!-- Download Gauge -->
      <div class="ist-single-gauge-block">
        <div class="ist-gauge-phase-label" id="ist-dl-gauge-label">Speed</div>
        <div class="ist-gauge-svg-container" role="img" aria-label="Download speed gauge">
          <svg id="ist-gauge-svg" class="ist-gauge-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 270"></svg>
          <div class="ist-gauge-readout">
            <div class="ist-readout-value" id="ist-readout-value" aria-live="polite">0</div>
            <div class="ist-readout-unit"  id="ist-readout-unit">MBPS</div>
            <div class="ist-readout-phase" id="ist-readout-phase">READY</div>
          </div>
        </div>
      </div>

      <!-- Upload Gauge — hidden until upload phase begins -->
      <div class="ist-single-gauge-block" id="ist-upload-gauge-wrap" style="display:none;" aria-hidden="true">
        <div class="ist-gauge-phase-label" style="color:var(--color-success);">Upload</div>
        <div class="ist-gauge-svg-container" role="img" aria-label="Upload speed gauge">
          <svg id="ist-upload-gauge-svg" class="ist-gauge-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 270"></svg>
          <div class="ist-gauge-readout">
            <div class="ist-readout-value" id="ist-ul-readout-value" aria-live="polite">0</div>
            <div class="ist-readout-unit"  id="ist-ul-readout-unit">MBPS</div>
            <div class="ist-readout-phase" id="ist-ul-readout-phase">WAITING</div>
          </div>
        </div>
      </div>

    </div>
    <!-- Progress bar + phase indicator merged -->
    <div class="ist-progress-combined">
      <div id="ist-phase-live" class="ist-phase-live" style="display:none;" aria-live="polite"></div>
      <div class="ist-progress-row">
        <div class="ist-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100">
          <div class="ist-progress-fill" id="ist-progress-fill"></div>
        </div>
        <span class="ist-progress-pct" id="ist-progress-pct">0%</span>
      </div>
    </div>
    <!-- Test mode selector -->
    <div class="ist-mode-section">
      <div class="ist-mode-label">
        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
        Test Intensity
      </div>
      <div class="ist-mode-track" role="group" aria-label="Test intensity mode">
        <button class="ist-mode-btn" data-mode="fast" onclick="IST.setMode('fast')" title="Faster test — fewer pings, shorter measurement window">
          Fast<span class="ist-mode-sub">~22s</span>
        </button>
        <button class="ist-mode-btn active" data-mode="basic" onclick="IST.setMode('basic')" title="Balanced test — default parameters">
          Basic<span class="ist-mode-sub">~38s</span>
        </button>
        <button class="ist-mode-btn" data-mode="professional" onclick="IST.setMode('professional')" title="Thorough test — more pings, longer measurement window">
          Professional<span class="ist-mode-sub">~80s</span>
        </button>
        <button class="ist-mode-btn" data-mode="custom" onclick="IST.setMode('custom')" title="Custom — configure parameters manually">
          Custom<span class="ist-mode-sub">manual</span>
        </button>
      </div>

      <!-- Connection mode toggle -->
      <div style="display: none;" class="ist-conn-row">
        <span class="ist-conn-label">Connection:</span>
        <div class="ist-conn-track">
          <button class="ist-conn-btn active" data-conn="auto"     onclick="IST.setConnectionMode('auto')">Auto</button>
          <button class="ist-conn-btn"        data-conn="wifi"     onclick="IST.setConnectionMode('wifi')">WiFi</button>
          <button class="ist-conn-btn"        data-conn="cellular" onclick="IST.setConnectionMode('cellular')">Cellular</button>
          <button class="ist-conn-btn"        data-conn="ethernet" onclick="IST.setConnectionMode('ethernet')">Ethernet</button>
        </div>
      </div>

      <!-- Custom mode parameter panel (hidden by default) -->
      <div id="ist-custom-panel" class="ist-custom-panel" style="display:none;">
        <div class="ist-custom-header">
          <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
          Custom Parameters
        </div>
        <div class="ist-custom-grid">
          <label class="ist-custom-field"><span>Ping count</span><input id="ist-custom-ping_count"      type="number" value="8"    min="3"    max="30"    oninput="IST.applyCustomParams()"></label>
          <label class="ist-custom-field"><span>Loss probes</span><input id="ist-custom-pkt_loss_pings"  type="number" value="10"   min="3"    max="40"    oninput="IST.applyCustomParams()"></label>
          <label class="ist-custom-field"><span>DL streams</span><input id="ist-custom-dl_streams"      type="number" value="6"    min="1"    max="16"    oninput="IST.applyCustomParams()"></label>
          <label class="ist-custom-field"><span>DL duration (ms)</span><input id="ist-custom-dl_duration"  type="number" value="6000" min="2000" max="30000" oninput="IST.applyCustomParams()"></label>
          <label class="ist-custom-field"><span>UL streams</span><input id="ist-custom-ul_streams"      type="number" value="4"    min="1"    max="12"    oninput="IST.applyCustomParams()"></label>
          <label class="ist-custom-field"><span>UL duration (ms)</span><input id="ist-custom-ul_duration"  type="number" value="5000" min="2000" max="30000" oninput="IST.applyCustomParams()"></label>
        </div>
      </div>
    </div>

    <!-- Start button -->
    <div class="ist-hero-controls">
      <button id="ist-start-btn" class="ist-start-btn" onclick="IST.run()" aria-label="Start internet speed test">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        Start Basic Diagnostic
      </button>
    </div>
  </div>

  <!-- Right stat column -->
  <div class="ist-hero-right">
    <div style="display:flex;flex-direction:column;gap:4px;">
      <span style="font-size:11px;font-weight:600;color:var(--color-text-subtle);text-transform:uppercase;letter-spacing:.08em;">Download</span>
      <div style="font-size:36px;font-weight:700;color:var(--color-text);line-height:1;" id="ist-hero-dl">—<span style="font-size:14px;font-weight:400;color:var(--color-text-muted);margin-left:3px;">Mbps</span></div>
      <svg id="ist-spark-dl" viewBox="0 0 80 28" preserveAspectRatio="none" style="width:80px;height:28px;overflow:visible;"><polyline class="ist-spark-path" points="0,14 80,14"/></svg>
    </div>
    <div style="display:flex;flex-direction:column;gap:4px;margin-top:16px;">
      <span style="font-size:11px;font-weight:600;color:var(--color-text-subtle);text-transform:uppercase;letter-spacing:.08em;">Upload</span>
      <div style="font-size:36px;font-weight:700;color:var(--color-text);line-height:1;" id="ist-hero-ul">—<span style="font-size:14px;font-weight:400;color:var(--color-text-muted);margin-left:3px;">Mbps</span></div>
      <svg id="ist-spark-ul" viewBox="0 0 80 28" preserveAspectRatio="none" style="width:80px;height:28px;overflow:visible;"><polyline class="ist-spark-path" points="0,14 80,14"/></svg>
    </div>
  </div>

</div>

<!-- ── History panel (always-collapsed accordion, shown once a test has run) ── -->
<div id="ist-history-panel" class="ist-history-panel" style="display:none!important;" aria-label="Test history">
  <div class="ist-history-header" onclick="IST.toggleHistory()" role="button" tabindex="0">
    <div class="ist-history-title">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><line x1="12" y1="7" x2="12" y2="12"/><line x1="12" y1="12" x2="15" y2="15"/></svg>
      Test History
      <span id="ist-history-count" class="ist-history-count">0</span>
      <span class="ist-history-hint">click a result to restore it</span>
    </div>
    <button class="ist-history-expand-btn" id="ist-history-expand-btn" aria-expanded="false">
      <span>View</span>
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
  </div>
  <div id="ist-history-expanded" class="ist-history-expanded" style="display:none;">
    <div id="ist-history-chips" class="ist-history-chips"></div>
    <div id="ist-trend-chart-wrap" class="ist-trend-wrap" style="display:none;">
      <div class="ist-trend-header">
        <span class="ist-trend-title">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
          Connection Quality Trend
        </span>
        <span id="ist-trend-label" class="ist-trend-label"></span>
      </div>
      <div class="ist-trend-chart-area">
        <svg id="ist-trend-svg" class="ist-trend-svg" xmlns="http://www.w3.org/2000/svg"></svg>
      </div>
      <div class="ist-trend-legend">
        <span class="ist-trend-leg-item"><span class="ist-trend-leg-dot" style="background:var(--color-primary)"></span>Score /100</span>
        <span class="ist-trend-leg-item"><span class="ist-trend-leg-dot" style="background:var(--color-success)"></span>Download Mbps</span>
        <span class="ist-trend-leg-item"><span class="ist-trend-leg-dot" style="background:var(--color-warning)"></span>Upload Mbps</span>
      </div>
    </div>
  </div>
</div>

<!-- ── KPI Cards ──────────────────────────────────────────────────────────── -->
<div id="ist-kpi-section" class="ist-hidden">
  <div class="ist-section-header">
    <h2 class="ist-section-title">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Network Metrics
    </h2>
  </div>
  <div class="ist-kpi-grid" aria-label="Network metric cards">

    <?php
    $kpis = [
        ['id'=>'dl',       'title'=>'Download',       'icon'=>'<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',  'spark'=>true],
        ['id'=>'ul',       'title'=>'Upload',          'icon'=>'<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',   'spark'=>true],
        ['id'=>'ping',     'title'=>'Latency',         'icon'=>'<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',                                                               'spark'=>true],
        ['id'=>'jitter',   'title'=>'Jitter',          'icon'=>'<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',                                                                                'spark'=>true],
        ['id'=>'loss',     'title'=>'Packet Loss',     'icon'=>'<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 11.24 19.79 19.79 0 0 1 1.61 2.62 2 2 0 0 1 3.57.43h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 7.91a16 16 0 0 0 6.29 6.29l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 15.92z"/>', 'spark'=>false],
        ['id'=>'dns',      'title'=>'DNS Response',    'icon'=>'<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',                                                       'spark'=>false],
        ['id'=>'tls',      'title'=>'TLS Handshake',   'icon'=>'<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',                                     'spark'=>false],
        ['id'=>'ttfb',     'title'=>'TTFB',            'icon'=>'<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',                                                                          'spark'=>false],
        ['id'=>'http',     'title'=>'HTTP Response',   'icon'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>',                                           'spark'=>false],
        ['id'=>'stability','title'=>'Stability',       'icon'=>'<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',                                                                                              'spark'=>false],
        ['id'=>'overall',  'title'=>'Overall Score',   'icon'=>'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',                                                                            'spark'=>false],
        ['id'=>'conn',     'title'=>'Connection Type', 'icon'=>'<path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>', 'spark'=>false],
        ['id'=>'peak-dl',  'title'=>'Peak Download',   'icon'=>'<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',                                               'spark'=>false],
        ['id'=>'peak-ul',  'title'=>'Peak Upload',     'icon'=>'<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/>',                                              'spark'=>false],
    ];
    foreach ($kpis as $i => $kpi): ?>
    <div class="ist-kpi-card ist-hidden" id="ist-kpi-<?= $kpi['id'] ?>" style="animation-delay:<?= $i * 30 ?>ms" role="region" aria-label="<?= htmlspecialchars($kpi['title']) ?>">
      <div class="ist-kpi-header">
        <div class="ist-kpi-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $kpi['icon'] ?></svg>
        </div>
        <span class="ist-kpi-badge">—</span>
      </div>
      <div class="ist-kpi-title"><?= htmlspecialchars($kpi['title']) ?></div>
      <div class="ist-kpi-value">—<span class="ist-kpi-unit"></span></div>
      <?php if ($kpi['spark']): ?>
      <div class="ist-kpi-sparkline">
        <svg id="ist-spark-<?= $kpi['id'] ?>" viewBox="0 0 80 28" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
          <polyline class="ist-spark-path" points="0,14 80,14"/>
        </svg>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

  </div>
</div>

<!-- ── Platform Latency ──────────────────────────────────────────────────── -->
<div id="ist-platform-section" class="ist-hidden">
  <div class="ist-section-header">
    <h2 class="ist-section-title">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
      Platform Latency
    </h2>
    <span class="ist-section-badge">Professional</span>
  </div>
  <div class="ist-plat-tab-bar" role="tablist" aria-label="Platform category tabs">
    <button class="ist-plat-tab active" data-cat="all"          onclick="IST.switchPlatformTab('all')"          role="tab">All</button>
    <button class="ist-plat-tab"        data-cat="gaming"       onclick="IST.switchPlatformTab('gaming')"       role="tab">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01"/><path d="M7 12h2"/><path d="M15 10v4"/><path d="M17 12h2"/></svg>
      Gaming
    </button>
    <button class="ist-plat-tab"        data-cat="social"       onclick="IST.switchPlatformTab('social')"       role="tab">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Social
    </button>
    <button class="ist-plat-tab"        data-cat="streaming"    onclick="IST.switchPlatformTab('streaming')"    role="tab">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
      Streaming
    </button>
    <button class="ist-plat-tab"        data-cat="productivity" onclick="IST.switchPlatformTab('productivity')" role="tab">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Productivity
    </button>
  </div>
  <div id="ist-platform-latency" class="ist-platform-latency-wrap">
    <p class="ist-empty-hint">Measured in Professional mode — run a Professional test to populate this section.</p>
  </div>
</div>

<!-- ── Real-Time Charts ───────────────────────────────────────────────────── -->
<div id="ist-charts-section" class="ist-charts-section ist-hidden" aria-label="Real-time performance charts">
  <div class="ist-chart-tabs" role="tablist">
    <?php
    $tabs = ['ping'=>'Latency', 'loaded_ping'=>'Loaded Latency', 'download'=>'Download', 'upload'=>'Upload', 'jitter'=>'Jitter', 'loss'=>'Packet Loss', 'realtime'=>'Realtime'];
    $first = true;
    foreach ($tabs as $tabId => $tabLabel):
    ?>
    <button class="ist-chart-tab <?= $first ? 'active' : '' ?>" data-tab="<?= $tabId ?>" role="tab" aria-selected="<?= $first ? 'true' : 'false' ?>" onclick="IST.switchChart('<?= $tabId ?>')"><?= $tabLabel ?></button>
    <?php $first = false; endforeach; ?>
  </div>
  <div class="ist-chart-panes">
    <?php
    $first = true;
    foreach ($tabs as $tabId => $tabLabel):
    ?>
    <div class="ist-chart-pane <?= $first ? 'active' : '' ?>" id="ist-chart-pane-<?= $tabId ?>" role="tabpanel">
      <div class="ist-chart-canvas" id="ist-chart-<?= $tabId ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 160" preserveAspectRatio="none"></svg>
      </div>
      <div class="ist-chart-legend">
        <div class="ist-chart-legend-item"><div class="ist-chart-legend-line"></div><?= $tabLabel ?></div>
        <div class="ist-chart-legend-item"><div class="ist-chart-legend-line avg"></div>Average</div>
        <div class="ist-chart-legend-item"><div class="ist-chart-legend-line peak"></div>Peak</div>
      </div>
    </div>
    <?php $first = false; endforeach; ?>
  </div>
</div>

<!-- ── Offline Banner ─────────────────────────────────────────────────────── -->
<div id="ist-offline-banner" class="ist-offline-banner" style="display:none;" role="alert" aria-live="assertive">
  <div class="ist-offline-icon">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M10.71 5.05A16 16 0 0 1 22.56 9"/><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
  </div>
  <div class="ist-offline-body">
    <div class="ist-offline-title">No Internet Connection</div>
    <div class="ist-offline-msg">Your device appears to be offline. Please check your network connection and try again. The test will resume automatically when connectivity is restored.</div>
  </div>
</div>

<!-- ── Phases Accordion (full-width, shown after test) ────────────────────── -->
<div id="ist-phases-accordion" class="ist-phases-accordion" style="display:none;" aria-label="Diagnostic phases">
  <div class="ist-phases-accordion-header">
    <div class="ist-phases-accordion-title">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      Diagnostic Phases
    </div>
    <button class="ist-phases-accordion-btn" onclick="IST.togglePhasesAccordion()" aria-expanded="false">
      <span id="ist-phases-accordion-btn-label">Expand</span>
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
  </div>
  <div id="ist-phases-accordion-body" class="ist-phases-accordion-body" style="display:none;">
    <div class="ist-phases-accordion-grid">
    <?php
    $phases = [
        ['env',       'Environment Detection',    '1'],
        ['latency',   'Latency &amp; Jitter',     '2'],
        ['dns',       'DNS Resolution',           '3'],
        ['pktloss',   'Packet Loss',              '4'],
        ['download',  'Download Speed',           '5'],
        ['upload',    'Upload Speed',             '6'],
        ['stability', 'Connection Stability',     '7'],
        ['scoring',   'Quality Analysis',         '8'],
        ['report',    'Summary Report',           '9'],
    ];
    foreach ($phases as $phase): ?>
    <div class="ist-phase-item" id="ist-phase-<?= $phase[0] ?>" role="status">
      <div class="ist-phase-indicator"><?= $phase[2] ?></div>
      <div class="ist-phase-body">
        <div class="ist-phase-name"><?= $phase[1] ?></div>
        <div class="ist-phase-bar"><div class="ist-phase-bar-fill"></div></div>
      </div>
      <div class="ist-phase-status">Pending</div>
    </div>
    <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ── Terminal ───────────────────────────────────────────────────────────── -->
<div id="ist-terminal-section" class="ist-hidden">

  <!-- Terminal -->
  <div class="ist-terminal-wrap" aria-label="Diagnostic console">
    <div class="ist-terminal-titlebar">
      <div class="ist-term-dots">
        <div class="ist-term-dot ist-term-dot-r"></div>
        <div class="ist-term-dot ist-term-dot-y"></div>
        <div class="ist-term-dot ist-term-dot-g"></div>
      </div>
      <span class="ist-terminal-title">diagnostics.log</span>
      <div class="ist-terminal-actions">
        <button class="ist-term-action" onclick="IST.toggleTermSearch()" title="Search logs">Search</button>
        <button class="ist-term-action" id="ist-term-pause" onclick="IST.toggleTermPause()" title="Pause auto-scroll">Pause</button>
        <button class="ist-term-action" onclick="IST.exportLogs()" title="Export logs">Export</button>
        <button class="ist-term-action" onclick="IST.clearTerminal()" title="Clear all logs">Clear</button>
      </div>
    </div>
    <!-- Search -->
    <div class="ist-term-search" id="ist-term-search">
      <input type="text" placeholder="Filter log messages..." oninput="IST.setTermSearch(this.value);" aria-label="Search logs">
    </div>
    <!-- Filter bar -->
    <div class="ist-term-filters" role="toolbar" aria-label="Log level filters">
      <?php foreach (['all'=>'All', 'phase'=>'Phase', 'info'=>'Info', 'success'=>'Success', 'warn'=>'Warn', 'error'=>'Error', 'debug'=>'Debug'] as $f => $l): ?>
      <button class="ist-term-filter <?= $f === 'all' ? 'active' : '' ?>" data-filter="<?= $f ?>" onclick="IST.setTermFilter('<?= $f ?>')" aria-label="Filter by <?= $l ?>"><?= $l ?></button>
      <?php endforeach; ?>
    </div>
    <div class="ist-terminal-body" id="ist-terminal-body" role="log" aria-live="polite">
      <div class="ist-log-line">
        <span class="ist-log-time">00:00:00</span>
        <span class="ist-log-level info">INFO</span>
        <span class="ist-log-msg highlight">Awan Tools Network Diagnostics Suite initialized.</span>
      </div>
      <div class="ist-log-line">
        <span class="ist-log-time">00:00:00</span>
        <span class="ist-log-level info">INFO</span>
        <span class="ist-log-msg">Press "Start Full Diagnostic" to begin the test suite.</span>
      </div>
    </div>
  </div>

</div>

<!-- ── Incomplete Results Warning ───────────────────────────────────────────── -->
<div id="ist-incomplete-warning" class="ist-incomplete-warning" style="display:none;" role="alert" aria-live="polite">
  <svg class="ist-incomplete-warning-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
  <div class="ist-incomplete-warning-body">
    <strong class="ist-incomplete-warning-title">Incomplete Results</strong>
    <span class="ist-incomplete-warning-desc" id="ist-incomplete-warning-desc"></span>
  </div>
  <button class="ist-incomplete-warning-dismiss" onclick="this.closest('#ist-incomplete-warning').style.display='none'" aria-label="Dismiss warning">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
  </button>
</div>

<!-- ── Quality Scores ─────────────────────────────────────────────────────── -->
<div id="ist-report-center" class="ist-hidden" aria-label="Quality scores">
  <div class="ist-section-header">
    <h2 class="ist-section-title">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      Quality Scores
    </h2>
  </div>
  <div class="ist-scores-grid" id="ist-scores-grid">
    <div class="ist-empty-state">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <h3>No Results Yet</h3>
      <p>Run a test to generate quality scores for gaming, streaming, video calls, and more.</p>
    </div>
  </div>
</div>

<!-- ── Recommendations ───────────────────────────────────────────────────── -->
<div id="ist-recommendations-section" class="ist-hidden" aria-label="Recommendations">
  <div class="ist-section-header">
    <h2 class="ist-section-title">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      Recommendations
    </h2>
  </div>
  <div style="background:var(--color-surface);border:1px solid var(--color-border);border-radius:18px;padding:20px;">
    <div id="ist-recommendations">
      <div class="ist-empty-state" style="padding:24px;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <h3>Awaiting Test Results</h3>
        <p>Recommendations will appear after the diagnostic suite completes.</p>
      </div>
    </div>
  </div>
</div>

<!-- ── Environment Cards ─────────────────────────────────────────────────── -->
<div aria-label="Environment information">
  <div class="ist-section-header">
    <h2 class="ist-section-title">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
      Environment Profile
    </h2>
  </div>
  <div class="ist-env-grid">

    <!-- Network -->
    <div class="ist-env-card">
      <div class="ist-env-card-header">
        <div class="ist-env-card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
        </div>
        <div class="ist-env-card-title">Network</div>
      </div>
      <div class="ist-env-rows">
        <div class="ist-env-row"><span class="ist-env-key">Public IP</span><span class="ist-env-val" id="ist-env-ip">Detecting...</span></div>
        <div class="ist-env-row"><span class="ist-env-key">IPv6</span><span class="ist-env-val" id="ist-env-ipv6">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">ISP</span><span class="ist-env-val" id="ist-env-isp">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Organization</span><span class="ist-env-val" id="ist-env-org">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">ASN</span><span class="ist-env-val" id="ist-env-asn">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Location</span><span class="ist-env-val" id="ist-env-location">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Conn. Type</span><span class="ist-env-val" id="ist-env-conn-type">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Eff. Type</span><span class="ist-env-val" id="ist-env-eff-type">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Est. Downlink</span><span class="ist-env-val" id="ist-env-downlink">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Est. RTT</span><span class="ist-env-val" id="ist-env-rtt-est">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Save Data</span><span class="ist-env-val" id="ist-env-save-data">—</span></div>
      </div>
    </div>

    <!-- Browser -->
    <div class="ist-env-card">
      <div class="ist-env-card-header">
        <div class="ist-env-card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        </div>
        <div class="ist-env-card-title">Browser</div>
      </div>
      <div class="ist-env-rows">
        <div class="ist-env-row"><span class="ist-env-key">Browser</span><span class="ist-env-val" id="ist-env-browser">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Platform</span><span class="ist-env-val" id="ist-env-platform">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Language</span><span class="ist-env-val" id="ist-env-language">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Color Scheme</span><span class="ist-env-val" id="ist-env-color-scheme">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Timezone</span><span class="ist-env-val" id="ist-env-timezone">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Touch</span><span class="ist-env-val" id="ist-env-touch">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Cookies</span><span class="ist-env-val" id="ist-env-cookie-enabled">—</span></div>
      </div>
    </div>

    <!-- Hardware -->
    <div class="ist-env-card">
      <div class="ist-env-card-header">
        <div class="ist-env-card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/></svg>
        </div>
        <div class="ist-env-card-title">Hardware</div>
      </div>
      <div class="ist-env-rows">
        <div class="ist-env-row"><span class="ist-env-key">Memory</span><span class="ist-env-val" id="ist-env-memory">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">CPU Cores</span><span class="ist-env-val" id="ist-env-cores">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Display</span><span class="ist-env-val" id="ist-env-screen">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Pixel Ratio</span><span class="ist-env-val" id="ist-env-pixel-ratio">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">WebGL</span><span class="ist-env-val" id="ist-env-webgl">—</span></div>
      </div>
    </div>

    <!-- Capabilities -->
    <div class="ist-env-card">
      <div class="ist-env-card-header">
        <div class="ist-env-card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="ist-env-card-title">Capabilities</div>
      </div>
      <div class="ist-env-rows">
        <div class="ist-env-row"><span class="ist-env-key">WebRTC</span><span class="ist-env-val" id="ist-env-webrtc">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">WebSocket</span><span class="ist-env-val" id="ist-env-websocket">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Service Worker</span><span class="ist-env-val" id="ist-env-service-worker">—</span></div>
        <div class="ist-env-row"><span class="ist-env-key">Online Status</span><span class="ist-env-val" id="ist-env-online" style="color:var(--color-success)">Online</span></div>
      </div>
    </div>

  </div>
</div>

<!-- ── Server Timings ─────────────────────────────────────────────────────── -->
<div id="ist-server-timings-section" style="display:none;" aria-label="Server performance timings">
  <div class="ist-section-header">
    <h2 class="ist-section-title">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 10h2"/><path d="M13 10h4"/></svg>
      Server Timings
    </h2>
  </div>
  <div id="ist-server-timings-grid" class="ist-server-timings-grid"></div>
</div>

<!-- ── Professional Tools (accordion — collapsed by default) ──────────────── -->
<div id="ist-pro-tools-section" class="ist-accordion" aria-label="Professional diagnostic tools">
  <div class="ist-section-header ist-accordion-trigger" id="ist-pro-tools-header"
       onclick="IST.toggleProTools()" role="button" tabindex="0"
       aria-controls="ist-pro-tools-body" aria-expanded="false"
       onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();IST.toggleProTools();}">
    <h2 class="ist-section-title">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
      Professional Tools
    </h2>
    <span class="ist-accordion-chevron" aria-hidden="true">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
    </span>
  </div>

  <div class="ist-accordion-body" id="ist-pro-tools-body" style="display:none">

    <!-- ── Individual Diagnostics ──────────────────────────────────────────── -->
    <div class="ist-pro-indiv-diag" aria-label="Individual diagnostic tools">
      <div class="ist-pro-indiv-title">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        Individual Diagnostics
      </div>
      <div class="ist-diag-grid">
        <?php
        $diags = [
            ['id'=>'ping',      'name'=>'Ping Test',            'desc'=>'Measure round-trip latency with 10 ICMP-style probes.',   'time'=>'~3s',  'cat'=>'Latency',    'icon'=>'<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
            ['id'=>'download',  'name'=>'Download Test',        'desc'=>'Measure downstream throughput using multi-chunk HTTP fetch.','time'=>'~15s','cat'=>'Speed',    'icon'=>'<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>'],
            ['id'=>'upload',    'name'=>'Upload Test',          'desc'=>'Measure upstream throughput via POST blob streaming.',    'time'=>'~10s','cat'=>'Speed',     'icon'=>'<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>'],
            ['id'=>'jitter',    'name'=>'Jitter Test',          'desc'=>'Analyze latency variation from 10 consecutive probes.',  'time'=>'~3s',  'cat'=>'Latency',   'icon'=>'<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>'],
            ['id'=>'pktloss',   'name'=>'Packet Loss',          'desc'=>'Estimate packet loss from 20 timed probes with timeouts.','time'=>'~5s',  'cat'=>'Quality',   'icon'=>'<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 11.24 19.79 19.79 0 0 1 1.61 2.62 2 2 0 0 1 3.57.43h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 7.91a16 16 0 0 0 6.29 6.29l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 15.92z"/>'],
            ['id'=>'dns',       'name'=>'DNS Resolution',       'desc'=>'Server-side DNS lookup timing across 3 major resolvers.', 'time'=>'~2s',  'cat'=>'DNS',       'icon'=>'<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>'],
            ['id'=>'http',      'name'=>'HTTP Response',        'desc'=>'Measure live HTTP round-trip time to the current server.','time'=>'~1s',  'cat'=>'HTTP',      'icon'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>'],
            ['id'=>'env',       'name'=>'Browser Capabilities', 'desc'=>'Profile browser APIs, hardware, and network environment.', 'time'=>'~1s',  'cat'=>'Info',      'icon'=>'<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>'],
            ['id'=>'stability', 'name'=>'Connection Stability', 'desc'=>'Measure latency range variance over 10 rapid samples.',   'time'=>'~2s',  'cat'=>'Quality',   'icon'=>'<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>'],
        ];
        foreach ($diags as $d):
        ?>
        <div class="ist-diag-card" id="ist-diag-<?= $d['id'] ?>">
          <div class="ist-diag-status" title="Status indicator"></div>
          <div class="ist-diag-card-header">
            <div class="ist-diag-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $d['icon'] ?></svg>
            </div>
            <span class="ist-diag-category"><?= $d['cat'] ?></span>
          </div>
          <div class="ist-diag-name"><?= htmlspecialchars($d['name']) ?></div>
          <div class="ist-diag-desc"><?= htmlspecialchars($d['desc']) ?></div>
          <div class="ist-diag-footer">
            <span class="ist-diag-time">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <?= htmlspecialchars($d['time']) ?>
            </span>
            <button class="ist-diag-run-btn" onclick="IST.runDiagnostic('<?= $d['id'] ?>')" aria-label="Run <?= htmlspecialchars($d['name']) ?>">Run</button>
          </div>
          <div class="ist-diag-result"></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="ist-pro-tools-grid">

    <!-- Custom Ping / Jitter Tool -->
    <div class="ist-pro-tool-card">
      <div class="ist-pro-tool-header">
        <div class="ist-pro-tool-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
          <div class="ist-pro-tool-name">Custom Latency &amp; Jitter Test</div>
          <div class="ist-pro-tool-desc">Send 10 probes to any URL or domain. Measures average, min, max latency and jitter.</div>
        </div>
      </div>
      <div class="ist-pro-tool-controls">
        <input id="ist-pro-url-input" class="ist-pro-url-input" type="text" placeholder="e.g. google.com  or  https://api.example.com" autocomplete="off" spellcheck="false"
               onkeydown="if(event.key==='Enter') IST.runCustomPing()">
        <button id="ist-pro-ping-btn" class="ist-pro-run-btn" onclick="IST.runCustomPing()">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Run Test
        </button>
      </div>
      <div class="ist-pro-tool-hint">Works with any HTTPS URL. Uses browser fetch timing (no-cors). Results reflect end-to-end round-trip including DNS + TCP + TLS + server processing.</div>
      <div id="ist-pro-ping-result" class="ist-pro-ping-result"></div>
    </div>

    <!-- Traceroute / Hop Analyzer -->
    <div class="ist-pro-tool-card ist-pro-tool-full">
      <div class="ist-pro-tool-header">
        <div class="ist-pro-tool-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="4" r="2"/><circle cx="4" cy="20" r="2"/><circle cx="20" cy="20" r="2"/><line x1="12" y1="6" x2="12" y2="12"/><line x1="12" y1="12" x2="4" y2="18"/><line x1="12" y1="12" x2="20" y2="18"/></svg>
        </div>
        <div>
          <div class="ist-pro-tool-name">Network Path Analyzer</div>
          <div class="ist-pro-tool-desc">Analyze the network path from this server to any host — DNS, TCP handshake, TLS, and TTFB latency at each stage.</div>
        </div>
      </div>
      <div class="ist-pro-tool-controls">
        <input id="ist-trace-host-input" class="ist-pro-url-input" type="text" placeholder="e.g. google.com  or  8.8.8.8" autocomplete="off" spellcheck="false"
               onkeydown="if(event.key==='Enter') IST.runTraceroute()">
        <button id="ist-trace-run-btn" class="ist-pro-run-btn" onclick="IST.runTraceroute()">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Trace Route
        </button>
      </div>
      <div class="ist-pro-tool-hint">Server-side analysis using TCP + curl timing. Probes DNS resolution, TCP handshake, TLS negotiation, and first-byte latency from the server to your target.</div>
      <div id="ist-trace-result" class="ist-trace-result"></div>
    </div>

    <!-- DNS Propagation Checker -->
    <div class="ist-pro-tool-card ist-pro-tool-full">
      <div class="ist-pro-tool-header">
        <div class="ist-pro-tool-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        </div>
        <div>
          <div class="ist-pro-tool-name">DNS Propagation Checker</div>
          <div class="ist-pro-tool-desc">Query your domain's DNS records across 8 global resolvers simultaneously. Verify propagation status, detect inconsistencies, and compare TTL values.</div>
        </div>
      </div>
      <div class="ist-pro-tool-controls">
        <input id="ist-dns-domain-input" class="ist-pro-url-input" type="text" placeholder="e.g. example.com  or  subdomain.example.com" autocomplete="off" spellcheck="false"
               onkeydown="if(event.key==='Enter') IST.runDnsPropagation()">
        <select id="ist-dns-type-select" class="ist-dns-type-select">
          <option value="A">A</option>
          <option value="AAAA">AAAA</option>
          <option value="MX">MX</option>
          <option value="TXT">TXT</option>
          <option value="CNAME">CNAME</option>
          <option value="NS">NS</option>
          <option value="SOA">SOA</option>
          <option value="CAA">CAA</option>
        </select>
        <button id="ist-dns-run-btn" class="ist-pro-run-btn" onclick="IST.runDnsPropagation()">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Check Propagation
        </button>
      </div>
      <div class="ist-pro-tool-hint">Queries 8 resolvers in parallel via DNS over HTTPS — Cloudflare, Google, Quad9, OpenDNS, AdGuard, NextDNS, Mullvad, and CleanBrowsing.</div>
      <div id="ist-dns-result" class="ist-dns-result"></div>
    </div>


    <!-- ═══ Deep Latency Analyzer ════════════════════════════════════════ -->
    <div class="ist-pro-tool-card ist-pro-tool-full" id="ist-la-card">
      <div class="ist-pro-tool-header">
        <div class="ist-pro-tool-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <div>
          <div class="ist-pro-tool-name">Deep Latency Analyzer <span class="ist-la-pro-badge">PRO</span></div>
          <div class="ist-pro-tool-desc">Multi-stage timing breakdown: DNS, TCP, TLS, TTFB, and transfer — measured separately. Full statistical analysis with min/max/avg/median/stddev/jitter/P95 and packet loss across up to 30 parallel probes.</div>
        </div>
      </div>

      <div class="ist-la-controls">
        <div class="ist-la-url-row">
          <input id="ist-la-url" class="ist-pro-url-input" type="text"
                 placeholder="e.g. https://api.example.com  or  example.com"
                 autocomplete="off" spellcheck="false"
                 onkeydown="if(event.key==='Enter') IST.runLatencyAnalyzer()">
          <button id="ist-la-run-btn" class="ist-pro-run-btn" onclick="IST.runLatencyAnalyzer()">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            Analyze
          </button>
        </div>
        <div class="ist-la-opts-row">
          <label class="ist-la-opt">
            <span class="ist-la-opt-lbl">Probes</span>
            <select id="ist-la-count" class="ist-la-sel">
              <option value="5">5</option>
              <option value="10" selected>10</option>
              <option value="15">15</option>
              <option value="20">20</option>
              <option value="30">30</option>
            </select>
          </label>
          <label class="ist-la-opt">
            <span class="ist-la-opt-lbl">Method</span>
            <select id="ist-la-method" class="ist-la-sel">
              <option value="HEAD" selected>HEAD</option>
              <option value="GET">GET</option>
            </select>
          </label>
          <label class="ist-la-opt">
            <span class="ist-la-opt-lbl">Connect timeout</span>
            <select id="ist-la-conn-timeout" class="ist-la-sel">
              <option value="3">3s</option>
              <option value="5" selected>5s</option>
              <option value="10">10s</option>
            </select>
          </label>
          <label class="ist-la-opt">
            <span class="ist-la-opt-lbl">Request timeout</span>
            <select id="ist-la-req-timeout" class="ist-la-sel">
              <option value="5">5s</option>
              <option value="10" selected>10s</option>
              <option value="20">20s</option>
              <option value="30">30s</option>
            </select>
          </label>
        </div>
      </div>

      <div class="ist-pro-tool-hint">Server-side analysis from the Awan Tools server to your target. Uses PHP cURL microsecond timing (<code>*_time_us</code> fields) for DNS, TCP, TLS, TTFB, and transfer stages. Parallel probes via <code>curl_multi</code>. DNS cached after first probe to isolate ongoing latency. Results stored in SQLite history for trend analysis.</div>

      <div id="ist-la-result" class="ist-la-result"></div>
    </div>

    <!-- Connection Quality Breakdown -->
    <div class="ist-pro-tool-card ist-pro-tool-info">
      <div class="ist-pro-tool-header">
        <div class="ist-pro-tool-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div>
          <div class="ist-pro-tool-name">Connection Quality Breakdown</div>
          <div class="ist-pro-tool-desc">Key thresholds professionals use to assess network health.</div>
        </div>
      </div>
      <div class="ist-pro-ref-table">
        <div class="ist-pro-ref-header"><span>Metric</span><span>Excellent</span><span>Good</span><span>Poor</span></div>
        <div class="ist-pro-ref-row"><span>Latency</span><span class="good">&lt;20ms</span><span class="warn">&lt;60ms</span><span class="bad">&gt;100ms</span></div>
        <div class="ist-pro-ref-row"><span>Jitter</span><span class="good">&lt;5ms</span><span class="warn">&lt;20ms</span><span class="bad">&gt;50ms</span></div>
        <div class="ist-pro-ref-row"><span>Packet Loss</span><span class="good">0%</span><span class="warn">&lt;2%</span><span class="bad">&gt;5%</span></div>
        <div class="ist-pro-ref-row"><span>Gaming Latency</span><span class="good">&lt;30ms</span><span class="warn">&lt;60ms</span><span class="bad">&gt;100ms</span></div>
        <div class="ist-pro-ref-row"><span>Streaming Latency</span><span class="good">&lt;50ms</span><span class="warn">&lt;120ms</span><span class="bad">&gt;200ms</span></div>
        <div class="ist-pro-ref-row"><span>Video Call Latency</span><span class="good">&lt;50ms</span><span class="warn">&lt;150ms</span><span class="bad">&gt;300ms</span></div>
        <div class="ist-pro-ref-row"><span>Download (Home)</span><span class="good">&gt;100Mbps</span><span class="warn">&gt;25Mbps</span><span class="bad">&lt;10Mbps</span></div>
        <div class="ist-pro-ref-row"><span>Upload (Home)</span><span class="good">&gt;50Mbps</span><span class="warn">&gt;10Mbps</span><span class="bad">&lt;5Mbps</span></div>
      </div>
    </div>

    <!-- Interpretation guide -->
    <div class="ist-pro-tool-card ist-pro-tool-info">
      <div class="ist-pro-tool-header">
        <div class="ist-pro-tool-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        </div>
        <div>
          <div class="ist-pro-tool-name">Use Case Guide</div>
          <div class="ist-pro-tool-desc">Minimum recommended specs per activity.</div>
        </div>
      </div>
      <div class="ist-pro-use-cases">
        <div class="ist-pro-use-case">
          <span class="ist-pro-use-icon">🎮</span>
          <div><strong>Competitive Gaming</strong><p>Need &lt;30ms latency, &lt;5ms jitter, 0% loss, ≥3 Mbps download.</p></div>
        </div>
        <div class="ist-pro-use-case">
          <span class="ist-pro-use-icon">📹</span>
          <div><strong>4K Video Streaming</strong><p>Need ≥25 Mbps download, &lt;150ms latency, &lt;2% loss.</p></div>
        </div>
        <div class="ist-pro-use-case">
          <span class="ist-pro-use-icon">💼</span>
          <div><strong>Video Conferencing</strong><p>Need ≥3 Mbps up/down, &lt;50ms latency, &lt;30ms jitter, &lt;1% loss.</p></div>
        </div>
        <div class="ist-pro-use-case">
          <span class="ist-pro-use-icon">☁️</span>
          <div><strong>Cloud / Remote Desktop</strong><p>Need ≥10 Mbps upload, &lt;60ms latency, stable jitter.</p></div>
        </div>
        <div class="ist-pro-use-case">
          <span class="ist-pro-use-icon">📡</span>
          <div><strong>Live Streaming (broadcast)</strong><p>Need ≥10 Mbps stable upload, &lt;50ms jitter, 0% loss.</p></div>
        </div>
        <div class="ist-pro-use-case">
          <span class="ist-pro-use-icon">🔒</span>
          <div><strong>VPN / Encrypted Tunnels</strong><p>Expect 5–30% overhead; latency +10–40ms. Optimize ISP routing.</p></div>
        </div>
      </div>
    </div>

    <!-- cURL Command Reference -->
    <div class="ist-pro-tool-card ist-pro-tool-full ist-curl-card">
      <div class="ist-pro-tool-header">
        <div class="ist-pro-tool-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        </div>
        <div>
          <div class="ist-pro-tool-name">cURL Command Reference</div>
          <div class="ist-pro-tool-desc">Ready-to-run cURL commands for network diagnostics, speed testing, latency measurement, and header inspection. Click any command to copy.</div>
        </div>
      </div>

      <div class="ist-curl-groups">

        <!-- Group: Timing & Latency -->
        <div class="ist-curl-group">
          <div class="ist-curl-group-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Timing &amp; Latency
          </div>
          <div class="ist-curl-cmds">
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Full timing breakdown</div>
              <code class="ist-curl-code">curl -s -o /dev/null -w "namelookup: %{time_namelookup}s\nconnect:     %{time_connect}s\nappconnect:  %{time_appconnect}s\npretransfer: %{time_pretransfer}s\nttfb:        %{time_starttransfer}s\ntotal:       %{time_total}s\n" https://example.com</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Quick TTFB check</div>
              <code class="ist-curl-code">curl -s -o /dev/null -w "ttfb: %{time_starttransfer}s  total: %{time_total}s\n" https://example.com</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">DNS resolution time only</div>
              <code class="ist-curl-code">curl -s -o /dev/null -w "dns: %{time_namelookup}s\n" https://example.com</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Repeat 5× and average</div>
              <code class="ist-curl-code">for i in {1..5}; do curl -s -o /dev/null -w "ttfb: %{time_starttransfer}s\n" https://example.com; done</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
          </div>
        </div>

        <!-- Group: Download Speed -->
        <div class="ist-curl-group">
          <div class="ist-curl-group-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="8 17 12 21 16 17"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.88 18.09A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.29"/></svg>
            Download Speed
          </div>
          <div class="ist-curl-cmds">
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Download speed from a 10 MB test file</div>
              <code class="ist-curl-code">curl -o /dev/null https://speed.cloudflare.com/__down?bytes=10000000 -w "speed: %{speed_download} B/s  time: %{time_total}s\n" -s</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Download with live progress bar</div>
              <code class="ist-curl-code">curl -o /dev/null --progress-bar https://speed.cloudflare.com/__down?bytes=50000000</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Upload speed test (POST 10 MB of zeros)</div>
              <code class="ist-curl-code">curl -X POST https://speed.cloudflare.com/__up -H "Content-Type: application/octet-stream" --data-binary @&lt;(dd if=/dev/zero bs=1M count=10 2>/dev/null) -s -o /dev/null -w "upload: %{speed_upload} B/s\n"</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
          </div>
        </div>

        <!-- Group: HTTP Headers & Inspection -->
        <div class="ist-curl-group">
          <div class="ist-curl-group-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            HTTP Headers &amp; Inspection
          </div>
          <div class="ist-curl-cmds">
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Show response headers only</div>
              <code class="ist-curl-code">curl -sI https://example.com</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Follow redirects and show final URL</div>
              <code class="ist-curl-code">curl -sIL -w "final_url: %{url_effective}\nredirects: %{num_redirects}\n" -o /dev/null https://example.com</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">HTTP status code check</div>
              <code class="ist-curl-code">curl -s -o /dev/null -w "%{http_code}" https://example.com</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Verbose connection trace</div>
              <code class="ist-curl-code">curl -v https://example.com 2>&amp;1 | grep -E "^\*|^&lt;|^>"</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Test with custom User-Agent</div>
              <code class="ist-curl-code">curl -sI -A "Mozilla/5.0 (compatible; NetworkTest/1.0)" https://example.com</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
          </div>
        </div>

        <!-- Group: SSL / TLS -->
        <div class="ist-curl-group">
          <div class="ist-curl-group-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            SSL / TLS
          </div>
          <div class="ist-curl-cmds">
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Show TLS certificate details</div>
              <code class="ist-curl-code">curl -vI https://example.com 2>&amp;1 | grep -E "subject|issuer|expire|SSL|TLS|protocol|cipher"</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Force TLS 1.3 only</div>
              <code class="ist-curl-code">curl -sI --tlsv1.3 https://example.com -w "tls_version: %{ssl_verify_result}\n"</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Check cert expiry date</div>
              <code class="ist-curl-code">curl -vI https://example.com 2>&amp;1 | grep "expire date"</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Test with TLS 1.2 max (legacy check)</div>
              <code class="ist-curl-code">curl -sI --tlsv1.2 --tls-max 1.2 https://example.com -o /dev/null -w "http: %{http_code}\n"</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
          </div>
        </div>

        <!-- Group: DNS Diagnostics -->
        <div class="ist-curl-group">
          <div class="ist-curl-group-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            DNS Diagnostics
          </div>
          <div class="ist-curl-cmds">
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Use Cloudflare DNS resolver</div>
              <code class="ist-curl-code">curl -s --dns-servers 1.1.1.1 -o /dev/null -w "dns: %{time_namelookup}s  ttfb: %{time_starttransfer}s\n" https://example.com</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Use Google DNS resolver</div>
              <code class="ist-curl-code">curl -s --dns-servers 8.8.8.8 -o /dev/null -w "dns: %{time_namelookup}s  ttfb: %{time_starttransfer}s\n" https://example.com</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Override DNS with specific IP (bypass DNS)</div>
              <code class="ist-curl-code">curl -s --resolve example.com:443:93.184.216.34 https://example.com -o /dev/null -w "http: %{http_code}  ttfb: %{time_starttransfer}s\n"</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
          </div>
        </div>

        <!-- Group: CDN & Cache Inspection -->
        <div class="ist-curl-group">
          <div class="ist-curl-group-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
            CDN &amp; Cache
          </div>
          <div class="ist-curl-cmds">
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Check cache status (CF-Cache-Status, X-Cache)</div>
              <code class="ist-curl-code">curl -sI https://example.com | grep -i -E "cache|age|cdn|cf-|x-cache|via"</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Bypass cache with no-cache header</div>
              <code class="ist-curl-code">curl -sI -H "Cache-Control: no-cache" -H "Pragma: no-cache" https://example.com</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Check HTTP/2 vs HTTP/1.1</div>
              <code class="ist-curl-code">curl -sI --http2 https://example.com -w "\nprotocol: %{http_version}\n"</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Check HSTS header</div>
              <code class="ist-curl-code">curl -sI https://example.com | grep -i "strict-transport"</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
          </div>
        </div>

        <!-- Group: Advanced / Pro -->
        <div class="ist-curl-group">
          <div class="ist-curl-group-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            Advanced
          </div>
          <div class="ist-curl-cmds">
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Test through HTTP proxy</div>
              <code class="ist-curl-code">curl -s -x http://proxy.example.com:8080 https://example.com -o /dev/null -w "http: %{http_code}  ttfb: %{time_starttransfer}s\n"</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Limit bandwidth (simulate slow connection)</div>
              <code class="ist-curl-code">curl --limit-rate 1M -o /dev/null https://speed.cloudflare.com/__down?bytes=10000000 --progress-bar</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">IPv4 only (disable IPv6)</div>
              <code class="ist-curl-code">curl -4 -sI https://example.com -w "ip: %{remote_ip}  http: %{http_code}\n" -o /dev/null</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">IPv6 only</div>
              <code class="ist-curl-code">curl -6 -sI https://example.com -w "ip: %{remote_ip}  http: %{http_code}\n" -o /dev/null</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
            <div class="ist-curl-cmd" onclick="IST.curlCopy(this)">
              <div class="ist-curl-label">Test API endpoint with JSON payload</div>
              <code class="ist-curl-code">curl -sX POST https://api.example.com/endpoint -H "Content-Type: application/json" -d '{"key":"value"}' -w "\nhttp: %{http_code}  ttfb: %{time_starttransfer}s\n"</code>
              <span class="ist-curl-copy-btn" aria-label="Copy command">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              </span>
            </div>
          </div>
        </div>

      </div><!-- .ist-curl-groups -->
    </div><!-- .ist-curl-card -->

  </div><!-- .ist-pro-tools-grid -->
  </div><!-- .ist-accordion-body -->
</div><!-- .ist-accordion / #ist-pro-tools-section -->

<!-- ── Export / Report Center ────────────────────────────────────────────── -->
<div id="ist-export-section" class="ist-hidden" aria-label="Export and report options">
  <div class="ist-section-header">
    <h2 class="ist-section-title">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Report Center
    </h2>
  </div>
  <div style="background:var(--color-surface);border:1px solid var(--color-border);border-radius:20px;padding:24px;display:flex;flex-direction:column;gap:20px;">
    <div class="ist-report-grid">

      <?php
      $exports = [
          ['fn'=>'IST.exportJSON()',     'icon'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',  'label'=>'JSON',       'sub'=>'Structured'],
          ['fn'=>'IST.exportPDF()',      'icon'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',  'label'=>'PDF',        'sub'=>'Branded report'],
          ['fn'=>'IST.exportCSV()',      'icon'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/>',  'label'=>'CSV',        'sub'=>'Spreadsheet'],
          ['fn'=>'IST.exportTXT()',      'icon'=>'<line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/>',  'label'=>'TXT',        'sub'=>'Plain text'],
          ['fn'=>'IST.copyClipboard()',  'icon'=>'<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>',  'label'=>'Clipboard',  'sub'=>'Copy summary'],
          ['fn'=>'window.print()',       'icon'=>'<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',  'label'=>'Print',      'sub'=>'Send to printer'],
      ];
      foreach ($exports as $ex):
      ?>
      <button class="ist-export-btn" onclick="<?= $ex['fn'] ?>" aria-label="Export as <?= $ex['label'] ?>">
        <div class="ist-export-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $ex['icon'] ?></svg>
        </div>
        <span class="ist-export-label" id="ist-export-clip-label"><?= $x = $ex['label'] ?></span>
        <span class="ist-export-sub"><?= $ex['sub'] ?></span>
      </button>
      <?php endforeach; ?>

    </div>

    <!-- cURL command -->
    <div>
      <div style="font-size:12px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">Terminal Report (cURL)</div>
      <div class="ist-curl-block" aria-label="cURL command">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;color:var(--color-primary)"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
        <span class="ist-curl-cmd" id="ist-curl-cmd">Run a test to generate a cURL command</span>
        <button class="ist-curl-copy" onclick="IST.copyCurl()" aria-label="Copy cURL command">
          <span id="ist-curl-label">Copy</span>
        </button>
      </div>
      <p style="font-size:11px;color:var(--color-text-subtle);margin-top:8px;">
        Run this command in your terminal to get a beautifully formatted ANSI diagnostic report.
      </p>
    </div>

  </div>
</div>

</div><!-- /.ist-app -->

<script src="/plugins/<?= $slug ?>/assets/speed-test.js"></script>
<script>
// Sync hero stat elements with KPI cards
document.addEventListener('DOMContentLoaded', function() {
    // Observe KPI changes to update hero numbers
    function mirrorKPI(kpiId, heroId) {
        var kpiCard = document.getElementById('ist-kpi-' + kpiId);
        if (!kpiCard) return;
        var obs = new MutationObserver(function() {
            var valEl  = kpiCard.querySelector('.ist-kpi-value');
            var heroEl = document.getElementById('ist-hero-' + heroId);
            if (valEl && heroEl) {
                var text = valEl.textContent;
                // Keep unit span
                var unitEl = valEl.querySelector('.ist-kpi-unit');
                var unit   = unitEl ? unitEl.textContent : '';
                var num    = text.replace(unit, '').trim();
                heroEl.innerHTML = num + '<span style="font-size:14px;font-weight:400;color:var(--color-text-muted);margin-left:3px;">' + unit.trim() + '</span>';
            }
        });
        obs.observe(kpiCard, { characterData: true, childList: true, subtree: true });
    }
    mirrorKPI('dl',   'dl');
    mirrorKPI('ul',   'ul');
    mirrorKPI('ping', 'ping');
    mirrorKPI('jitter','jitter');
});
</script>

<?php echo plugin_related_html($slug); ?>

<?php
$content = ob_get_clean();
plugin_render('Internet Speed Test — Network Diagnostics Suite', $content, [
    'description' => 'Professional internet diagnostics: download/upload speed, latency, jitter, packet loss, DNS timing, TLS analysis, connection quality scores, and detailed reports.',
    'canonical'   => '/plugins/internet-speed-test/',
]);


