<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';
require_once AWAN_ROOT . '/_core/Plugin.php';

$slug      = 'text-toolkit';
$_manifest = plugin_manifest($slug);
$_meta     = $_manifest['meta'] ?? [];
plugin_track('plugin_view', '/plugins/text-toolkit/', ['plugin_slug' => $slug]);

/* ── Small inline icon helper (feather-style strokes) ───────────────── */
function xt_icon(string $paths, string $extra = ''): string {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
         . 'stroke-linecap="round" stroke-linejoin="round" ' . $extra . '>' . $paths . '</svg>';
}

$ic = [
    'workbench' => '<path d="M4 4h16v12H4z"/><path d="M4 20h16"/><path d="M9 8h6"/><path d="M9 12h4"/>',
    'search'    => '<circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
    'analysis'  => '<path d="M3 3v18h18"/><path d="M7 15l4-6 4 3 5-8"/>',
    'format'    => '<path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/>',
    'cleanup'   => '<path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6"/>',
    'extract'   => '<path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/><path d="M21 16v3a2 2 0 0 1-2 2h-3"/><path d="M3 8V5a2 2 0 0 1 2-2h3"/><circle cx="12" cy="12" r="3"/>',
    'compare'   => '<path d="M9 3v18"/><path d="M15 3v18"/><path d="M4 8h5"/><path d="M4 16h5"/><path d="M15 8h5"/><path d="M15 16h5"/>',
    'encode'    => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
    'paste'     => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
    'upload'    => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
    'sample'    => '<path d="M12 2l2.4 7.2H22l-6 4.4 2.3 7.2L12 16.4 5.7 20.8 8 13.6 2 9.2h7.6z"/>',
    'trash'     => '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/>',
    'undo'      => '<path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/>',
    'copy'      => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
    'shield'    => '<path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5z"/>',
    'bolt'      => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
];

ob_start();
?>
<link rel="stylesheet" href="/plugins/<?= $slug ?>/assets/text-toolkit.css">
<div class="xt-app">

    <!-- ── Hero ─────────────────────────────────────────────────────── -->
    <section class="xt-hero">
        <span class="xt-hero-badge"><?= xt_icon($ic['shield']) ?> 100% client-side · nothing leaves your browser</span>
        <h1 class="xt-hero-title">Text Toolkit</h1>
        <p class="xt-hero-sub">One Workbench. 109 text tools. Type or paste once on the left, and analysis, formatting,
            extraction, comparison and encoding tools on the right all work off the same text — live.</p>

        <div class="xt-hero-search-wrap">
            <span class="xt-hero-search-icon"><?= xt_icon($ic['search']) ?></span>
            <input type="text" id="xt-global-search" class="xt-hero-search" autocomplete="off" spellcheck="false"
                   placeholder="Search any tool — e.g. word counter, camelCase, remove duplicate lines, base64…">
            <div class="xt-search-results" id="xt-search-results"></div>
        </div>

        <div class="xt-hero-stats">
            <span class="xt-hero-stat"><strong>109</strong> tools</span>
            <span class="xt-hero-stat-sep">·</span>
            <span class="xt-hero-stat"><strong>6</strong> workspaces</span>
            <span class="xt-hero-stat-sep">·</span>
            <span class="xt-hero-stat"><strong>0</strong> uploads to a server</span>
        </div>
    </section>

    <!-- ── Layout: Workbench + Tabbed panel ────────────────────────── -->
    <div class="xt-layout">

        <!-- Workbench -->
        <aside class="xt-workbench" id="xt-workbench">
            <div class="xt-wb-head">
                <div class="xt-wb-title"><?= xt_icon($ic['workbench']) ?> Workbench</div>
                <span class="xt-wb-live-dot" title="Updates live as you type"></span>
            </div>

            <div class="xt-wb-toolbar">
                <button class="xt-wb-btn" id="xt-btn-sample" type="button"><?= xt_icon($ic['sample']) ?> Sample</button>
                <button class="xt-wb-btn" id="xt-btn-paste" type="button"><?= xt_icon($ic['paste']) ?> Paste</button>
                <button class="xt-wb-btn" id="xt-btn-upload" type="button"><?= xt_icon($ic['upload']) ?> Upload</button>
                <input type="file" id="xt-file-input" accept=".txt,.md,.csv,.log" hidden>
                <button class="xt-wb-btn" id="xt-btn-undo" type="button" disabled><?= xt_icon($ic['undo']) ?> Undo</button>
                <button class="xt-wb-btn xt-wb-btn-danger" id="xt-btn-clear" type="button"><?= xt_icon($ic['trash']) ?> Clear</button>
            </div>

            <textarea id="xt-input" class="xt-ta" spellcheck="false" autocomplete="off"
                placeholder="Type or paste your text here…&#10;&#10;Every tool in the panel on the right (or below, on mobile) reads and — where relevant — rewrites this exact text."></textarea>

            <div class="xt-wb-quickstats" id="xt-quickstats">
                <div class="xt-qs"><span class="xt-qs-val" id="qs-words">0</span><span class="xt-qs-label">Words</span></div>
                <div class="xt-qs"><span class="xt-qs-val" id="qs-chars">0</span><span class="xt-qs-label">Characters</span></div>
                <div class="xt-qs"><span class="xt-qs-val" id="qs-sentences">0</span><span class="xt-qs-label">Sentences</span></div>
                <div class="xt-qs"><span class="xt-qs-val" id="qs-lines">0</span><span class="xt-qs-label">Lines</span></div>
                <div class="xt-qs"><span class="xt-qs-val" id="qs-time">0s</span><span class="xt-qs-label">Read time</span></div>
            </div>
        </aside>

        <!-- Tabbed panel -->
        <section class="xt-panel">
            <nav class="xt-tabs" id="xt-tabs">
                <button class="xt-tab active" data-tab="analysis" style="--xt-tab-c:var(--color-info)"><?= xt_icon($ic['analysis']) ?><span>Analysis</span><small id="cnt-analysis">0</small></button>
                <button class="xt-tab" data-tab="format" style="--xt-tab-c:var(--color-primary)"><?= xt_icon($ic['format']) ?><span>Format &amp; Case</span><small id="cnt-format">0</small></button>
                <button class="xt-tab" data-tab="cleanup" style="--xt-tab-c:var(--color-warning)"><?= xt_icon($ic['cleanup']) ?><span>Cleanup &amp; Utilities</span><small id="cnt-cleanup">0</small></button>
                <button class="xt-tab" data-tab="extract" style="--xt-tab-c:var(--color-success)"><?= xt_icon($ic['extract']) ?><span>Extraction</span><small id="cnt-extract">0</small></button>
                <button class="xt-tab" data-tab="compare" style="--xt-tab-c:var(--color-danger)"><?= xt_icon($ic['compare']) ?><span>Comparison</span><small id="cnt-compare">0</small></button>
                <button class="xt-tab" data-tab="encode" style="--xt-tab-c:var(--color-text-secondary)"><?= xt_icon($ic['encode']) ?><span>Encoding</span><small id="cnt-encode">0</small></button>
            </nav>

            <!-- Analysis -->
            <div class="xt-pane active" id="pane-analysis" data-empty-icon="analysis"
                 data-empty-text="Type something in the Workbench to see word counts, reading metrics and readability scores update live.">
                <div class="xt-pane-toolbar">
                    <div class="xt-pane-toolbar-title"><?= xt_icon($ic['bolt']) ?> Live stats — updates as you type</div>
                    <div class="xt-kw-inline">
                        <label for="xt-keyword">Keyword focus</label>
                        <input type="text" id="xt-keyword" placeholder="optional keyword…" autocomplete="off">
                        <select id="xt-ngram-size">
                            <option value="2">2-grams</option>
                            <option value="3">3-grams</option>
                            <option value="4">4-grams</option>
                        </select>
                    </div>
                </div>
                <div id="xt-analysis-groups"></div>
            </div>

            <!-- Format & Case -->
            <div class="xt-pane" id="pane-format"
                 data-empty-icon="format"
                 data-empty-text="Type something in the Workbench, then click any tool below to transform it in place.">
                <div class="xt-pane-toolbar">
                    <div class="xt-pane-toolbar-title"><?= xt_icon($ic['format']) ?> Click a tool to apply it to the Workbench text</div>
                    <input type="text" class="xt-mini-search" data-target="format" placeholder="Filter tools…">
                </div>
                <div id="xt-actions-format"></div>
            </div>

            <!-- Cleanup & Utilities -->
            <div class="xt-pane" id="pane-cleanup"
                 data-empty-icon="cleanup"
                 data-empty-text="Type something in the Workbench, then click any tool below to clean or restructure it.">
                <div class="xt-pane-toolbar">
                    <div class="xt-pane-toolbar-title"><?= xt_icon($ic['cleanup']) ?> Click a tool to apply it to the Workbench text</div>
                    <input type="text" class="xt-mini-search" data-target="cleanup" placeholder="Filter tools…">
                </div>
                <div id="xt-actions-cleanup"></div>
            </div>

            <!-- Extraction -->
            <div class="xt-pane" id="pane-extract"
                 data-empty-icon="extract"
                 data-empty-text="Type or paste text with emails, URLs, dates, IPs etc. — every entity type extracts automatically.">
                <div class="xt-pane-toolbar">
                    <div class="xt-pane-toolbar-title"><?= xt_icon($ic['extract']) ?> All 11 extractors run automatically on the Workbench text</div>
                    <button class="xt-btn xt-btn-sm" id="xt-copy-all-extract"><?= xt_icon($ic['copy']) ?> Copy all matches</button>
                </div>
                <div id="xt-extract-groups"></div>
            </div>

            <!-- Comparison -->
            <div class="xt-pane" id="pane-compare">
                <div class="xt-compare-tools" id="xt-compare-tools"></div>
                <div class="xt-compare-grid">
                    <div class="xt-compare-col">
                        <div class="xt-pane-label">Text A</div>
                        <textarea id="xt-cmp-a" class="xt-ta xt-ta-cmp" spellcheck="false" placeholder="Paste the first text…"></textarea>
                        <button class="xt-mini-link" id="xt-cmp-a-load">Load from Workbench</button>
                    </div>
                    <div class="xt-compare-col">
                        <div class="xt-pane-label">Text B</div>
                        <textarea id="xt-cmp-b" class="xt-ta xt-ta-cmp" spellcheck="false" placeholder="Paste the second text…"></textarea>
                        <button class="xt-mini-link" id="xt-cmp-b-load">Load from Workbench</button>
                    </div>
                </div>
                <div id="xt-compare-result"></div>
            </div>

            <!-- Encoding -->
            <div class="xt-pane" id="pane-encode">
                <div class="xt-codec-tabs" id="xt-codec-tabs"></div>
                <div class="xt-encode-toolbar">
                    <div class="xt-dir-toggle" id="xt-dir-toggle"></div>
                    <div class="xt-encode-actions">
                        <button class="xt-btn xt-btn-sm" id="xt-enc-load">Load Workbench → Input</button>
                        <button class="xt-btn xt-btn-sm" id="xt-enc-send">Send Output → Workbench</button>
                    </div>
                </div>
                <div class="xt-encode-grid">
                    <div class="xt-encode-col">
                        <div class="xt-pane-label" id="xt-enc-in-label">Input</div>
                        <textarea id="xt-enc-in" class="xt-ta xt-ta-cmp" spellcheck="false"></textarea>
                    </div>
                    <div class="xt-encode-col">
                        <div class="xt-pane-label" id="xt-enc-out-label">Output</div>
                        <textarea id="xt-enc-out" class="xt-ta xt-ta-cmp" readonly spellcheck="false"></textarea>
                    </div>
                </div>
                <div class="xt-encode-err" id="xt-enc-err"></div>
            </div>

        </section>
    </div>
</div>

<div class="xt-toast" id="xt-toast"></div>

<script><?php echo file_get_contents(__DIR__ . '/assets/text-toolkit.js'); ?></script>

<?php
$content = ob_get_clean();
plugin_render('Text Toolkit &mdash; 109 Free Online Text Analysis &amp; Formatting Tools', $content, [
    'slug'        => $slug,
    'description' => $_meta['description'] ?? 'Word counters, readability scores, case converters, cleanup, extraction, comparison and encoding — 109 free browser-based text tools in one live Workbench.',
    'og_title'    => $_meta['title']       ?? 'Text Toolkit',
    'og_desc'     => $_meta['description'] ?? '',
    'canonical'   => $_meta['canonical']   ?? '',
    'stylesheet'  => '/plugins/' . $slug . '/assets/text-toolkit.css',
]);
