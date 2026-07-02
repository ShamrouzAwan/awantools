<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';

$slug      = 'scannable-codes';
$_manifest = plugin_manifest($slug);
$_meta     = $_manifest['meta'] ?? [];
plugin_track('plugin_view', '/plugins/scannable-codes/', ['plugin_slug' => $slug]);

ob_start();
?>
<style><?php echo file_get_contents(__DIR__ . '/assets/scannable-codes.css'); ?></style>

<!-- Third-party libraries -->
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bwip-js@4.5.1/dist/bwip-js-min.js"></script>

<div class="sc-wrap">

    <!-- ══ Hero ══════════════════════════════════════════════════════════ -->
    <div class="sc-hero">
        <div class="sc-hero-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="5" y="5" width="3" height="3" fill="currentColor" stroke="none"/>
                <rect x="5" y="16" width="3" height="3" fill="currentColor" stroke="none"/>
                <rect x="16" y="5" width="3" height="3" fill="currentColor" stroke="none"/>
                <path d="M14 14h3v3h-3zM17 17h3v3h-3zM14 20h3"/>
            </svg>
            Scannable Codes
        </div>
        <div class="sc-hero-subtitle">Generate 15+ code types or scan via camera &amp; image upload — 100% client-side, nothing stored</div>
        <div class="sc-hero-stats">
            <span class="sc-hstat"><strong>15</strong> code types</span>
            <span class="sc-hstat">Camera &amp; upload scan</span>
            <span class="sc-hstat">PNG / SVG / JPEG export</span>
            <span class="sc-hstat">100% client-side</span>
        </div>
    </div>

    <!-- ══ Tab Nav ═══════════════════════════════════════════════════════ -->
    <div class="sc-tab-nav">
        <button class="sc-tab-btn active" data-tab="scanner" type="button" onclick="SC.switchTab('scanner')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13">
                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                <circle cx="12" cy="13" r="4"/>
            </svg>
            Scanner
        </button>
        <button class="sc-tab-btn" data-tab="generator" type="button" onclick="SC.switchTab('generator')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="5" y="5" width="3" height="3" fill="currentColor" stroke="none"/>
                <rect x="5" y="16" width="3" height="3" fill="currentColor" stroke="none"/>
                <rect x="16" y="5" width="3" height="3" fill="currentColor" stroke="none"/>
            </svg>
            Generator
        </button>
    </div>

    <!-- ══ Scanner Panel ══════════════════════════════════════════════════ -->
    <div id="sc-tab-scanner" class="sc-tab-panel active">
        <div class="sc-panel-pad">

            <!-- Mode toggle -->
            <div class="sc-mode-row">
                <div class="sc-mode-toggle">
                    <button class="sc-mode-btn active" id="sc-btn-camera" type="button" onclick="SC.switchScanMode('camera')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                        Live Camera
                    </button>
                    <button class="sc-mode-btn" id="sc-btn-upload" type="button" onclick="SC.switchScanMode('upload')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                        Upload Image
                    </button>
                </div>
            </div>

            <!-- Camera view -->
            <div id="sc-cam-wrap" class="sc-cam-wrap">
                <div id="sc-cam-idle" class="sc-camera-idle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                        <circle cx="12" cy="13" r="4"/>
                    </svg>
                    <strong>Point your camera at a code</strong>
                    <p>Supports QR codes, barcodes, Data Matrix, Aztec, PDF417, and more</p>
                </div>
                <div id="sc-reader" style="display:none"></div>
                <div class="sc-cam-actions">
                    <button class="btn btn-primary" id="sc-cam-btn" type="button" onclick="SC.toggleCamera()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                        Start Camera
                    </button>
                </div>
                <div class="sc-scan-status" id="sc-scan-status" style="display:none">
                    <div class="sc-scan-dot"></div>
                    Scanning for codes&hellip;
                </div>
            </div>

            <!-- Upload view -->
            <div id="sc-upload-wrap" class="sc-upload-wrap" style="display:none">
                <div class="sc-drop-zone" id="sc-drop-zone"
                    ondragover="SC.handleDragOver(event)"
                    ondragleave="SC.handleDragLeave(event)"
                    ondrop="SC.handleDrop(event)"
                    onclick="SC.triggerUpload()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                    <strong>Drop an image here</strong>
                    <div class="sc-or">or</div>
                    <button class="btn btn-secondary btn-sm" type="button" onclick="event.stopPropagation();SC.triggerUpload()">Browse Files</button>
                    <span>JPG, PNG, GIF, WebP supported</span>
                    <input type="file" id="sc-file-input" accept="image/*" style="display:none" onchange="SC.handleFileInput(event)">
                </div>
            </div>

            <!-- Result card -->
            <div id="sc-result-card" class="sc-result-card" style="display:none">
                <div class="sc-result-header">
                    <span class="sc-result-badge" id="sc-result-badge">Code</span>
                    <span>Detected successfully</span>
                    <span class="sc-ok-check">&#10003;</span>
                </div>
                <div class="sc-result-value" id="sc-result-value"></div>
                <div class="sc-result-actions">
                    <button class="btn btn-primary btn-sm" type="button" onclick="SC.copyResult()">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px">
                            <rect x="9" y="9" width="13" height="13" rx="2"/>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                        Copy
                    </button>
                    <button class="btn btn-ghost btn-sm" type="button" onclick="SC.clearResult()">Dismiss</button>
                </div>
            </div>

        </div><!-- /sc-panel-pad -->
    </div><!-- /sc-tab-scanner -->


    <!-- ══ Generator Panel ════════════════════════════════════════════════ -->
    <div id="sc-tab-generator" class="sc-tab-panel">
        <div class="sc-panel-pad">

            <!-- Type chip strip (populated by JS) -->
            <div id="sc-type-strip" class="sc-type-strip"></div>

            <!-- Form + Preview -->
            <div class="sc-gen-layout">

                <!-- Options form -->
                <div class="sc-form-card">
                    <div class="sc-form-card-header">
                        <span class="sc-form-card-title" id="sc-form-title">QR Code Options</span>
                        <span class="sc-live-badge">
                            <span class="sc-live-dot"></span>
                            Live
                        </span>
                    </div>
                    <div id="sc-form-container">
                        <div class="sc-select-hint">Select a code type above to begin</div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="sc-preview-wrap sc-preview-sticky">
                    <div class="sc-preview-box" id="sc-preview-box">
                        <div class="sc-preview-empty" id="sc-preview-empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7" rx="1"/>
                                <rect x="3" y="14" width="7" height="7" rx="1"/>
                                <rect x="14" y="3" width="7" height="7" rx="1"/>
                                <rect x="5" y="5" width="3" height="3" fill="currentColor" stroke="none"/>
                                <rect x="5" y="16" width="3" height="3" fill="currentColor" stroke="none"/>
                                <rect x="16" y="5" width="3" height="3" fill="currentColor" stroke="none"/>
                            </svg>
                            Type some content to generate a code
                        </div>
                        <!-- Outputs -->
                        <div id="sc-qr-output" style="display:none;align-items:center;justify-content:center"></div>
                        <svg id="sc-barcode-svg" style="display:none;max-width:100%"></svg>
                        <canvas id="sc-bwip-canvas" style="display:none;max-width:100%;border-radius:4px"></canvas>
                    </div>
                    <div class="sc-preview-meta" id="sc-preview-meta"></div>
                    <div class="sc-download-bar" id="sc-download-bar" style="display:none">
                        <button class="btn btn-secondary btn-sm" type="button" onclick="SC.downloadCode('png')">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            PNG
                        </button>
                        <button class="btn btn-secondary btn-sm" id="sc-dl-svg" type="button" onclick="SC.downloadCode('svg')">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            SVG
                        </button>
                        <button class="btn btn-secondary btn-sm" type="button" onclick="SC.downloadCode('jpeg')">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            JPEG
                        </button>
                    </div>
                </div>

            </div><!-- /sc-gen-layout -->

        </div><!-- /sc-panel-pad -->
    </div><!-- /sc-tab-generator -->

</div><!-- /sc-wrap -->

<?php echo plugin_related_html($slug); ?>

<script><?php echo file_get_contents(__DIR__ . '/assets/scannable-codes.js'); ?></script>

<?php
$content = ob_get_clean();
plugin_render('Scannable Codes — Generate &amp; Scan 15+ Code Types', $content, [
    'description' => 'Free online code generator and scanner. Create QR codes, Code 128, EAN, UPC, PDF417, DataMatrix, Aztec and 15+ more. Scan via camera or image upload.',
    'og_title'    => $_meta['title'] ?? 'Scannable Codes',
    'og_desc'     => $_meta['description'] ?? '',
]);
