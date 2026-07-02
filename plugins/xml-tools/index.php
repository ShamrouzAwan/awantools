<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';
require_once AWAN_ROOT . '/_core/Plugin.php';

$slug      = 'xml-tools';
$_manifest = plugin_manifest($slug);
$_meta     = $_manifest['meta'] ?? [];
plugin_track('plugin_view', '/plugins/xml-tools/', ['plugin_slug' => $slug]);

/* ── Helpers ─────────────────────────────────────────────────── */
function xt_ta(string $id, string $extra = ''): string {
    return '<textarea id="' . $id . '" class="xt-ta" spellcheck="false" autocomplete="off" ' . $extra . '></textarea>';
}

/* Input pane label: Paste + Open File */
function xt_in_pane(string $label, string $taId, string $accept = '.xml,.txt'): string {
    $pasteBtn = '<button type="button" class="xt-copy-btn" onclick="XT.pasteInto(\'' . $taId . '\', this)">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>'
        . 'Paste</button>';
    $openBtn = '<button type="button" class="xt-copy-btn" onclick="XT.openFile(\'' . $taId . '\',\'' . $accept . '\')">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>'
        . 'Open</button>';
    return '<div class="xt-pane-label"><span>' . $label . '</span>'
         . '<div class="xt-pane-label-right">' . $pasteBtn . $openBtn . '</div></div>';
}

/* Output pane label: meta counter + Copy */
function xt_out_pane(string $label, string $metaId = '', string $taId = ''): string {
    $right = '';
    if ($metaId) $right .= '<span class="xt-pane-meta" id="' . $metaId . '"></span>';
    if ($taId) {
        $right .= '<button type="button" class="xt-copy-btn" onclick="XT.cpPane(\'' . $taId . '\', this)">'
               . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>'
               . 'Copy</button>';
    }
    return '<div class="xt-pane-label"><span>' . $label . '</span>'
         . '<div class="xt-pane-label-right">' . $right . '</div></div>';
}

/* SVG icons */
$icons = [
    'formatter'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>',
    'beautifier' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
    'minifier'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg>',
    'validator'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
    'viewer'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    'escape'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 10 4 15 9 20"/><path d="M20 4v7a4 4 0 0 1-4 4H4"/></svg>',
    'unescape'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 10 20 15 15 20"/><path d="M4 4v7a4 4 0 0 0 4 4h12"/></svg>',
    'xml2csv'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="1"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="9" x2="9" y2="21"/></svg>',
    'csv2xml'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
    'diff'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
];

function xt_nav(string $id, string $name, string $hint, string $icon): string {
    return '<button class="xt-nav" data-tool="' . $id . '" onclick="XT.switchTool(\'' . $id . '\')">'
         . '<span class="xt-nav-icon">' . $icon . '</span>'
         . '<span class="xt-nav-text">'
         .   '<span class="xt-nav-name">' . $name . '</span>'
         .   '<span class="xt-nav-hint">' . $hint . '</span>'
         . '</span></button>';
}

$groups = [
    'Format &amp; View' => [
        ['formatter',  'Formatter',  'Indent &amp; format',    'formatter'],
        ['beautifier', 'Beautifier', 'Human-readable',         'beautifier'],
        ['minifier',   'Minifier',   'Strip whitespace',       'minifier'],
        ['validator',  'Validator',  'Check for errors',       'validator'],
        ['viewer',     'Viewer',     'Interactive tree',       'viewer'],
    ],
    'String Ops' => [
        ['escape',   'Escape',   'Escape entities',   'escape'],
        ['unescape', 'Unescape', 'Decode entities',   'unescape'],
    ],
    'Convert' => [
        ['xml2csv', 'XML &rarr; CSV', 'Export tabular',  'xml2csv'],
        ['csv2xml', 'CSV &rarr; XML', 'Import from CSV', 'csv2xml'],
    ],
    'Compare' => [
        ['diff', 'Diff', 'Compare two XMLs', 'diff'],
    ],
];

function xt_toolbar(string $title, string $left = '', string $right = ''): string {
    return '<div class="xt-toolbar">'
         . '<div class="xt-toolbar-left"><span class="xt-tool-title">' . $title . '</span>' . $left . '</div>'
         . '<div class="xt-toolbar-right">' . $right . '</div>'
         . '</div>';
}

$copy_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
$dl_svg   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';

ob_start();
?>
<style><?php echo file_get_contents(__DIR__ . '/assets/xml-tools.css'); ?></style>

<div class="header-hero">
    <div class="page-header-left">
        <div class="header-hero-title">XML Tools</div>
        <div class="header-hero-subtitle">10 client-side utilities - format, validate, diff, escape &amp; convert XML</div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-ghost btn-sm header-hstat" onclick="XT.loadSample()">Load Sample</button>
        <button class="btn btn-ghost btn-sm header-hstat" onclick="XT.clearAll()">Clear</button>
    </div>
</div>

<div class="xt-shell">

<!-- ══ Sidebar ════════════════════════════════════════════════ -->
<nav class="xt-sidebar">
<?php foreach ($groups as $label => $tools): ?>
<div class="xt-nav-group"><?= $label ?></div>
<?php foreach ($tools as [$id, $name, $hint, $iconKey]): ?>
<?= xt_nav($id, $name, $hint, $icons[$iconKey] ?? '') ?>
<?php endforeach; ?>
<?php endforeach; ?>
</nav>

<!-- ══ Tool panels ════════════════════════════════════════════ -->
<div class="xt-content">

<!-- 1. Formatter ─────────────────────────────────────────── -->
<div class="xt-panel" id="xt-formatter">
<?= xt_toolbar('XML Formatter', '',
    '<div id="fmt-st"></div>'
    . '<label class="xt-select-wrap">Indent <select id="fmt-indent" onchange="XT.runFormatter()"><option value="2">2 spaces</option><option value="4">4 spaces</option><option value="tab">Tab</option></select></label>'
    . '<button class="xt-btn" onclick="XT.cpPane(\'fmt-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="xt-btn" onclick="XT.dl(\'fmt-out\',\'formatted.xml\',\'application/xml\')">' . $dl_svg . 'Download</button>'
    . '<button class="xt-btn xt-btn-primary" onclick="XT.runFormatter()">Format</button>'
) ?>
<div class="xt-editors">
    <div class="xt-editor-col">
        <?= xt_in_pane('Input XML', 'fmt-in') ?>
        <?= xt_ta('fmt-in', 'oninput="XT.runFormatter()" placeholder="Paste or type XML here\u2026"') ?>
    </div>
    <div class="xt-editor-divider"></div>
    <div class="xt-editor-col">
        <?= xt_out_pane('Formatted Output', 'fmt-meta', 'fmt-out') ?>
        <?= xt_ta('fmt-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 2. Beautifier ────────────────────────────────────────── -->
<div class="xt-panel" id="xt-beautifier" style="display:none">
<?= xt_toolbar('XML Beautifier', '<span class="xt-hint">Formats with 2-space indent &amp; preserves structure</span>',
    '<div id="beau-st"></div>'
    . '<button class="xt-btn" onclick="XT.cpPane(\'beau-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="xt-btn" onclick="XT.dl(\'beau-out\',\'beautiful.xml\',\'application/xml\')">' . $dl_svg . 'Download</button>'
    . '<button class="xt-btn xt-btn-primary" onclick="XT.runBeautifier()">Beautify</button>'
) ?>
<div class="xt-editors">
    <div class="xt-editor-col">
        <?= xt_in_pane('Input XML', 'beau-in') ?>
        <?= xt_ta('beau-in', 'oninput="XT.runBeautifier()" placeholder="Paste XML to beautify\u2026"') ?>
    </div>
    <div class="xt-editor-divider"></div>
    <div class="xt-editor-col">
        <?= xt_out_pane('Beautified Output', 'beau-meta', 'beau-out') ?>
        <?= xt_ta('beau-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 3. Minifier ──────────────────────────────────────────── -->
<div class="xt-panel" id="xt-minifier" style="display:none">
<?= xt_toolbar('XML Minifier', '<span class="xt-hint">Strips whitespace &amp; comments</span>',
    '<div id="min-st"></div>'
    . '<button class="xt-btn" onclick="XT.cpPane(\'min-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="xt-btn" onclick="XT.dl(\'min-out\',\'minified.xml\',\'application/xml\')">' . $dl_svg . 'Download</button>'
    . '<button class="xt-btn xt-btn-primary" onclick="XT.runMinifier()">Minify</button>'
) ?>
<div class="xt-editors">
    <div class="xt-editor-col">
        <?= xt_in_pane('Input XML', 'min-in') ?>
        <?= xt_ta('min-in', 'oninput="XT.runMinifier()" placeholder="Paste formatted XML to minify\u2026"') ?>
    </div>
    <div class="xt-editor-divider"></div>
    <div class="xt-editor-col">
        <?= xt_out_pane('Minified Output', 'min-meta', 'min-out') ?>
        <?= xt_ta('min-out', 'readonly style="word-break:break-all"') ?>
    </div>
</div>
</div>

<!-- 4. Validator ─────────────────────────────────────────── -->
<div class="xt-panel" id="xt-validator" style="display:none">
<?= xt_toolbar('XML Validator', '',
    '<button class="xt-btn" onclick="sv(\'val-in\',\'\');var r=el(\'val-res\');if(r){r.innerHTML=\'\';r.style.display=\'none\';}">Clear</button>'
    . '<button class="xt-btn xt-btn-primary" onclick="XT.runValidator()">Validate</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div class="xt-editor-col" style="flex:1;overflow:hidden">
        <?= xt_in_pane('XML Input', 'val-in') ?>
        <?= xt_ta('val-in', 'oninput="XT.runValidator()" placeholder="Paste XML here to check for errors\u2026"') ?>
    </div>
    <div id="val-res" class="xt-val-result" style="display:none"></div>
</div>
</div>

<!-- 5. Viewer ────────────────────────────────────────────── -->
<div class="xt-panel" id="xt-viewer" style="display:none">
<?= xt_toolbar('XML Viewer', '',
    '<button class="xt-btn" onclick="XT.viewerAll(true)">Expand All</button>'
    . '<button class="xt-btn" onclick="XT.viewerAll(false)">Collapse All</button>'
    . '<button class="xt-btn xt-btn-primary" onclick="XT.runViewer()">View</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="height:140px;display:flex;flex-direction:column;border-bottom:1px solid var(--color-border);flex-shrink:0">
        <?= xt_in_pane('XML Input', 'view-in') ?>
        <?= xt_ta('view-in', 'oninput="XT.runViewer()" placeholder="Paste XML to explore as an interactive tree\u2026"') ?>
    </div>
    <div id="view-tree" style="flex:1;overflow:auto;padding:14px;font-family:monospace;font-size:13px;line-height:1.85;background:var(--color-background)">
        <span style="color:var(--color-text-muted);font-size:13px">Paste XML above to see the interactive tree.</span>
    </div>
</div>
</div>

<!-- 6. Escape ────────────────────────────────────────────── -->
<div class="xt-panel" id="xt-escape" style="display:none">
<?= xt_toolbar('XML Escape', '<span class="xt-hint">Converts &amp; &lt; &gt; &quot; &apos; to XML entities</span>',
    '<button class="xt-btn" onclick="XT.cpPane(\'esc-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="xt-btn xt-btn-primary" onclick="XT.runEscape()">Escape</button>'
) ?>
<div class="xt-editors">
    <div class="xt-editor-col">
        <?= xt_in_pane('Raw String', 'esc-in', '.txt') ?>
        <?= xt_ta('esc-in', 'oninput="XT.runEscape()" placeholder="Paste text to escape for use inside XML\u2026"') ?>
    </div>
    <div class="xt-editor-divider"></div>
    <div class="xt-editor-col">
        <?= xt_out_pane('Escaped Output', 'esc-meta', 'esc-out') ?>
        <?= xt_ta('esc-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 7. Unescape ──────────────────────────────────────────── -->
<div class="xt-panel" id="xt-unescape" style="display:none">
<?= xt_toolbar('XML Unescape', '<span class="xt-hint">Decodes &amp;amp; &amp;lt; &amp;gt; &amp;quot; &amp;apos; and numeric entities</span>',
    '<button class="xt-btn" onclick="XT.cpPane(\'unesc-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="xt-btn xt-btn-primary" onclick="XT.runUnescape()">Unescape</button>'
) ?>
<div class="xt-editors">
    <div class="xt-editor-col">
        <?= xt_in_pane('Escaped XML String', 'unesc-in', '.txt,.xml') ?>
        <?= xt_ta('unesc-in', 'oninput="XT.runUnescape()" placeholder="Paste XML-escaped string to decode\u2026"') ?>
    </div>
    <div class="xt-editor-divider"></div>
    <div class="xt-editor-col">
        <?= xt_out_pane('Unescaped Output', 'unesc-meta', 'unesc-out') ?>
        <?= xt_ta('unesc-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 8. XML → CSV ─────────────────────────────────────────── -->
<div class="xt-panel" id="xt-xml2csv" style="display:none">
<?= xt_toolbar('XML &rarr; CSV', '<span class="xt-hint">Extracts repeating child elements of the root into tabular CSV</span>',
    '<div id="x2c-st"></div>'
    . '<button class="xt-btn" onclick="XT.cpPane(\'x2c-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="xt-btn" onclick="XT.dl(\'x2c-out\',\'output.csv\',\'text/csv\')">' . $dl_svg . 'Download</button>'
    . '<button class="xt-btn xt-btn-primary" onclick="XT.runXml2Csv()">Convert</button>'
) ?>
<div class="xt-editors">
    <div class="xt-editor-col">
        <?= xt_in_pane('Input XML', 'x2c-in') ?>
        <?= xt_ta('x2c-in', 'oninput="XT.runXml2Csv()" placeholder="Paste XML with repeating child elements\u2026"') ?>
    </div>
    <div class="xt-editor-divider"></div>
    <div class="xt-editor-col">
        <?= xt_out_pane('CSV Output', 'x2c-meta', 'x2c-out') ?>
        <?= xt_ta('x2c-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 9. CSV → XML ─────────────────────────────────────────── -->
<div class="xt-panel" id="xt-csv2xml" style="display:none">
<?= xt_toolbar('CSV &rarr; XML', '',
    '<div id="c2x-st"></div>'
    . '<button class="xt-btn" onclick="XT.cpPane(\'c2x-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="xt-btn" onclick="XT.dl(\'c2x-out\',\'output.xml\',\'application/xml\')">' . $dl_svg . 'Download</button>'
    . '<button class="xt-btn xt-btn-primary" onclick="XT.runCsv2Xml()">Convert</button>'
) ?>
<div class="xt-input-row">
    <label>Root tag <input type="text" id="c2x-root-tag" value="root" oninput="XT.runCsv2Xml()" placeholder="root"></label>
    <label>Row tag &nbsp;<input type="text" id="c2x-row-tag"  value="item" oninput="XT.runCsv2Xml()" placeholder="item"></label>
</div>
<div class="xt-editors">
    <div class="xt-editor-col">
        <?= xt_in_pane('Input CSV', 'c2x-in', '.csv,.txt') ?>
        <?= xt_ta('c2x-in', 'oninput="XT.runCsv2Xml()" placeholder="Paste CSV with header row\u2026"') ?>
    </div>
    <div class="xt-editor-divider"></div>
    <div class="xt-editor-col">
        <?= xt_out_pane('XML Output', 'c2x-meta', 'c2x-out') ?>
        <?= xt_ta('c2x-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 10. Diff ─────────────────────────────────────────────── -->
<div class="xt-panel" id="xt-diff" style="display:none">
<?= xt_toolbar('XML Diff', '<span class="xt-hint">Compare two XML documents and see what changed</span>',
    '<div id="diff-st"></div>'
    . '<button class="xt-btn xt-btn-primary" onclick="XT.runDiff()">Compare</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div class="xt-editors" style="height:200px;flex-shrink:0;border-bottom:1px solid var(--color-border)">
        <div class="xt-editor-col">
            <?= xt_in_pane('XML A (original)', 'diff-a') ?>
            <?= xt_ta('diff-a', 'oninput="XT.runDiff()" placeholder="Paste original XML (A)\u2026"') ?>
        </div>
        <div class="xt-editor-divider"></div>
        <div class="xt-editor-col">
            <?= xt_in_pane('XML B (modified)', 'diff-b') ?>
            <?= xt_ta('diff-b', 'oninput="XT.runDiff()" placeholder="Paste modified XML (B)\u2026"') ?>
        </div>
    </div>
    <div id="diff-out" style="flex:1;overflow:auto;background:var(--color-background)">
        <div class="xt-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Paste XML into both panes to compare
        </div>
    </div>
</div>
</div>

</div><!-- /.xt-content -->
</div><!-- /.xt-shell -->

<?php echo plugin_related_html($slug); ?>

<script>
/* tiny shims so the clear button on Validator can call these directly */
function el(id) { return document.getElementById(id); }
function sv(id, v) { var e = el(id); if (e) e.value = v; }
</script>
<script><?php echo file_get_contents(__DIR__ . '/assets/xml-tools.js'); ?></script>

<?php
$content = ob_get_clean();
plugin_render('XML Tools &mdash; 10 Free Online XML Utilities', $content, [
    'description' => 'Format, validate, minify, beautify, view, escape, unescape, convert and diff XML with 10 free browser-based tools. No sign-up required.',
    'og_title'    => $_meta['title']       ?? 'XML Tools',
    'og_desc'     => $_meta['description'] ?? '',
]);
