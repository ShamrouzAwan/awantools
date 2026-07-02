<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';
require_once AWAN_ROOT . '/_core/Plugin.php';

$slug      = 'dev-generator-toolkit';
$_manifest = plugin_manifest($slug);
$_meta     = $_manifest['meta'] ?? [];
plugin_track('plugin_view', '/plugins/dev-generator-toolkit/', ['plugin_slug' => $slug]);

ob_start();
?>
<link rel="stylesheet" href="./assets/dev-generator-toolkit.css">

<div class="dgt">

    <!-- ══ PAGE HEADER ══════════════════════════════════════════════ -->
    <header class="dgt-header">
        <div>
            <h1 class="dgt-header-title">Developer Generator Toolkit</h1>
            <p class="dgt-header-desc">Generate identifiers, fake data, configuration files, API payloads, passwords, SQL, JSON, and development resources instantly.</p>
        </div>
        <div class="dgt-stats">
            <span class="dgt-stat-chip">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                125+ Generators
            </span>
            <span class="dgt-stat-chip">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                No API Required
            </span>
            <span class="dgt-stat-chip">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                Runs Locally
            </span>
        </div>
    </header>

    <!-- ══ SEARCH ═══════════════════════════════════════════════════ -->
    <div class="dgt-search-wrap">
        <span class="dgt-search-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </span>
        <input
            id="dgt-search"
            type="search"
            class="dgt-search-input"
            placeholder="Search generators…"
            oninput="DGT.search(this.value)"
            autocomplete="off"
            spellcheck="false"
            aria-label="Search generators"
        >
        <span id="dgt-search-count" class="dgt-search-count"></span>
        <span class="dgt-search-kbd">Ctrl K</span>
    </div>

    <!-- ══ QUICK ACCESS CHIPS ════════════════════════════════════════ -->
    <nav class="dgt-chips" aria-label="Quick access">
        <button class="dgt-chip" onclick="DGT.scrollToCat('cat-uuid')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8" cy="12" r="1" fill="currentColor"/><circle cx="12" cy="12" r="1" fill="currentColor"/><circle cx="16" cy="12" r="1" fill="currentColor"/></svg>
            UUID
        </button>
        <button class="dgt-chip" onclick="DGT.scrollToCat('cat-passwords')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Passwords
        </button>
        <button class="dgt-chip" onclick="DGT.scrollToCat('cat-fakedata')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Fake Data
        </button>
        <button class="dgt-chip" onclick="DGT.scrollToCat('cat-lorem')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="13" y2="18"/></svg>
            Lorem Ipsum
        </button>
        <button class="dgt-chip" onclick="DGT.scrollToCat('cat-structured')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            JSON / CSV
        </button>
        <button class="dgt-chip" onclick="DGT.scrollToCat('cat-code')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            Code
        </button>
        <button class="dgt-chip" onclick="DGT.scrollToCat('cat-random')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18h1.4c1.3 0 2.5-.6 3.3-1.7l6.1-8.6c.7-1.1 2-1.7 3.3-1.7H22"/><path d="m18 2 4 4-4 4"/><path d="M2 6h1.9c1.5 0 2.9.9 3.6 2.2"/><path d="M22 18h-5.9c-1.3 0-2.6-.7-3.3-1.8l-.5-.8"/><path d="m18 14 4 4-4 4"/></svg>
            Random
        </button>
        <button class="dgt-chip" onclick="DGT.scrollToCat('cat-utils')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M6.3 6.3a8 8 0 1 0 11.31 0"/></svg>
            Utilities
        </button>
        <button class="dgt-chip" onclick="DGT.scrollToCat('cat-code');DGT.open('gen-regex')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v8"/><path d="m4.93 10.93 5.66 5.66"/><path d="M2 18h8"/><path d="M14 18h8"/><path d="m13.41 16.59 5.66-5.66"/><path d="M12 22v-4"/></svg>
            Regex
        </button>
        <button class="dgt-chip" onclick="DGT.scrollToCat('cat-utils');DGT.open('gen-config')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M12 2v2M12 20v2M2 12h2M20 12h2"/></svg>
            Config
        </button>
        <button class="dgt-chip" onclick="DGT.scrollToCat('cat-code');DGT.open('gen-sql')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
            SQL
        </button>
    </nav>

    <!-- ══ CATEGORY GRID (rendered by JS) ═══════════════════════════ -->
    <div id="dgt-categories"></div>

    <!-- ══ NO RESULTS ════════════════════════════════════════════════ -->
    <div id="dgt-no-results" class="dgt-no-results">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        No generators found. Try a different search term.
    </div>

    <!-- ══ WORKSPACE ══════════════════════════════════════════════════ -->
    <section id="dgt-workspace" aria-label="Generator workspace">

        <!-- Header / Breadcrumb -->
        <div class="dgt-ws-header">
            <nav class="dgt-ws-breadcrumb" aria-label="Breadcrumb"></nav>
            <button class="dgt-ws-close" onclick="DGT.closeWorkspace()" aria-label="Close workspace">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Close
            </button>
        </div>

        <!-- Body: config + output + sidebar -->
        <div class="dgt-ws-body">

            <!-- ── Config panel ── -->
            <div class="dgt-config">
                <h2 class="dgt-config-title"></h2>
                <p class="dgt-config-desc"></p>
                <hr class="dgt-divider">

                <form id="dgt-ws-form" onsubmit="return false;"></form>

                <button id="dgt-gen-btn" class="dgt-gen-btn" onclick="DGT.generate()" aria-label="Generate">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    Generate
                </button>
            </div>

            <!-- ── Output / Preview panel ── -->
            <div class="dgt-output">

                <!-- Tab bar -->
                <div class="dgt-out-tabs">
                    <button id="dgt-tab-output" class="dgt-out-tab active" onclick="DGT.switchTab('output')" aria-selected="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                        Output
                    </button>
                    <button id="dgt-tab-preview" class="dgt-out-tab" onclick="DGT.switchTab('preview')" aria-selected="false" style="display:none">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Preview
                    </button>
                    <span style="flex:1"></span>
                    <!-- Toolbar buttons always visible -->
                    <button class="dgt-tool-btn" onclick="DGT.copy()" aria-label="Copy output">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        Copy
                    </button>
                    <button class="dgt-tool-btn" onclick="DGT.download()" aria-label="Download output">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Download
                    </button>
                    <button class="dgt-tool-btn" onclick="DGT.regenerate()" aria-label="Regenerate">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        New
                    </button>
                    <button class="dgt-tool-btn" onclick="DGT.clear()" aria-label="Clear output">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                        Clear
                    </button>
                </div>

                <!-- ── OUTPUT TAB ── -->
                <div id="dgt-panel-output" class="dgt-out-tab-panel active">
                    <div class="dgt-code-editor">
                        <div class="dgt-code-header">
                            <span class="dgt-code-dot dgt-code-dot-r"></span>
                            <span class="dgt-code-dot dgt-code-dot-y"></span>
                            <span class="dgt-code-dot dgt-code-dot-g"></span>
                            <span class="dgt-code-filename">output.txt</span>
                        </div>
                        <div class="dgt-empty">
                            <svg class="dgt-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            <p class="dgt-empty-title">Ready to generate</p>
                            <p class="dgt-empty-desc">Configure options and click <strong>Generate</strong></p>
                        </div>
                        <textarea
                            id="dgt-ws-output"
                            class="dgt-code-ta"
                            readonly
                            spellcheck="false"
                            autocorrect="off"
                            aria-label="Generated output"
                            style="display:none"
                        ></textarea>
                    </div>
                </div>

                <!-- ── PREVIEW TAB ── -->
                <div id="dgt-panel-preview" class="dgt-out-tab-panel">
                    <div class="dgt-preview-panel">
                        <div class="dgt-preview-header">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <span id="dgt-preview-head-label">Preview</span>
                        </div>
                        <div class="dgt-preview-body" id="dgt-preview-body">
                            <div class="dgt-preview-empty">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                <p>Click Generate to see a live preview</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── Sidebar: related tools + shortcuts ── -->
            <aside class="dgt-ws-side">
                <div>
                    <p class="dgt-side-section-title">Related Tools</p>
                    <div class="dgt-related-list"></div>
                </div>
                <div>
                    <p class="dgt-side-section-title">Keyboard Shortcuts</p>
                    <div class="dgt-shortcut-list">
                        <div class="dgt-shortcut-row"><span>Search</span><kbd class="dgt-kbd">Ctrl K</kbd></div>
                        <div class="dgt-shortcut-row"><span>Generate</span><kbd class="dgt-kbd">↵ Enter</kbd></div>
                        <div class="dgt-shortcut-row"><span>Close</span><kbd class="dgt-kbd">Esc</kbd></div>
                    </div>
                </div>
            </aside>

        </div><!-- /dgt-ws-body -->
    </section><!-- /dgt-workspace -->

</div><!-- /dgt -->

<!-- Toast -->
<div id="dgt-toast" role="status" aria-live="polite">
    <svg class="dgt-toast-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    <span class="dgt-toast-msg"></span>
</div>

<script src="./assets/dev-generator-toolkit.js"></script>
<script src="./assets/dgt-features.js"></script>
<?php


<?php echo plugin_related_html($slug); ?>


$content = ob_get_clean();
plugin_render(
    $_meta['title']       ?? 'Developer Generator Toolkit — 125+ Free Online Generators',
    $content,
    [
        'description' => $_meta['description'] ?? 'Generate UUIDs, passwords, fake data, lorem ipsum, SQL, JSON, CSV, HTML, CSS, JavaScript and more. 100% free, client-side, no API required.',
        'canonical'   => $_meta['canonical']   ?? '',
    ]
);
