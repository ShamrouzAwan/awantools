<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';
require_once AWAN_ROOT . '/_core/Plugin.php';

$slug      = 'yaml-tools';
$_manifest = plugin_manifest($slug);
$_meta     = $_manifest['meta'] ?? [];
plugin_track('plugin_view', '/plugins/yaml-tools/', ['plugin_slug' => $slug]);

/* ── Helpers ─────────────────────────────────────────────────── */
function yt_ta(string $id, string $extra = ''): string {
    return '<textarea id="' . $id . '" class="yt-ta" spellcheck="false" autocomplete="off" ' . $extra . '></textarea>';
}

/* Input pane label: Paste + Open File */
function yt_in_pane(string $label, string $taId, string $accept = '.yaml,.yml,.txt'): string {
    $pasteBtn = '<button type="button" class="yt-copy-btn" onclick="YT.pasteInto(\'' . $taId . '\', this)">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>'
        . 'Paste</button>';
    $openBtn = '<button type="button" class="yt-copy-btn" onclick="YT.openFile(\'' . $taId . '\',\'' . $accept . '\')">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>'
        . 'Open</button>';
    return '<div class="yt-pane-label"><span>' . $label . '</span>'
         . '<div class="yt-pane-label-right">' . $pasteBtn . $openBtn . '</div></div>';
}

/* Output pane label: meta counter + Copy */
function yt_out_pane(string $label, string $metaId = '', string $taId = ''): string {
    $right = '';
    if ($metaId) $right .= '<span class="yt-pane-meta" id="' . $metaId . '"></span>';
    if ($taId) {
        $right .= '<button type="button" class="yt-copy-btn" onclick="YT.cpPane(\'' . $taId . '\', this)">'
               . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>'
               . 'Copy</button>';
    }
    return '<div class="yt-pane-label"><span>' . $label . '</span>'
         . '<div class="yt-pane-label-right">' . $right . '</div></div>';
}

/* SVG icons */
$icons = [
    'formatter'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>',
    'validator'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
    'viewer'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    'sortkeys'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
    'flatten'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
    'diff'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    'escape'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 10 4 15 9 20"/><path d="M20 4v7a4 4 0 0 1-4 4H4"/></svg>',
    'unescape'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 10 20 15 15 20"/><path d="M4 4v7a4 4 0 0 0 4 4h12"/></svg>',
    'yaml2json'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>',
    'json2yaml'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>',
    'yaml2csv'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="1"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="9" x2="9" y2="21"/></svg>',
    'csv2yaml'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
];

function yt_nav(string $id, string $name, string $hint, string $icon): string {
    return '<button class="yt-nav" data-tool="' . $id . '" onclick="YT.switchTool(\'' . $id . '\')">'
         . '<span class="yt-nav-icon">' . $icon . '</span>'
         . '<span class="yt-nav-text">'
         .   '<span class="yt-nav-name">' . $name . '</span>'
         .   '<span class="yt-nav-hint">' . $hint . '</span>'
         . '</span></button>';
}

$groups = [
    'Format &amp; View' => [
        ['formatter', 'Formatter', 'Indent &amp; format',  'formatter'],
        ['validator', 'Validator', 'Check for errors',     'validator'],
        ['viewer',    'Viewer',    'Interactive tree',     'viewer'],
    ],
    'Transform' => [
        ['sortkeys', 'Sort Keys', 'Alphabetical sort',      'sortkeys'],
        ['flatten',  'Flatten',   'Dot-notation &amp; back','flatten'],
        ['diff',     'Diff',      'Compare two YAMLs',      'diff'],
    ],
    'String Ops' => [
        ['escape',   'Escape',   'Quote for YAML',    'escape'],
        ['unescape', 'Unescape', 'Decode quoted',     'unescape'],
    ],
    'Convert' => [
        ['yaml2json', 'YAML &rarr; JSON', 'Export as JSON',  'yaml2json'],
        ['json2yaml', 'JSON &rarr; YAML', 'Import from JSON','json2yaml'],
        ['yaml2csv',  'YAML &rarr; CSV',  'Export tabular',  'yaml2csv'],
        ['csv2yaml',  'CSV &rarr; YAML',  'Import from CSV', 'csv2yaml'],
    ],
];

function yt_toolbar(string $title, string $left = '', string $right = ''): string {
    return '<div class="yt-toolbar">'
         . '<div class="yt-toolbar-left"><span class="yt-tool-title">' . $title . '</span>' . $left . '</div>'
         . '<div class="yt-toolbar-right">' . $right . '</div>'
         . '</div>';
}

$copy_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
$dl_svg   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';

ob_start();
?>
<style><?php echo file_get_contents(__DIR__ . '/assets/yaml-tools.css'); ?></style>

<div class="page-header" style="margin-bottom:0;border-bottom:1px solid var(--color-border);padding-bottom:14px">
    <div class="page-header-left">
        <div class="page-title">YAML Tools</div>
        <div class="page-subtitle">12 client-side utilities &mdash; format, validate, diff, transform &amp; convert YAML</div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-ghost btn-sm" onclick="YT.loadSample()">Load Sample</button>
        <button class="btn btn-ghost btn-sm" onclick="YT.clearAll()">Clear</button>
    </div>
</div>

<div class="yt-shell">

<!-- ══ Sidebar ════════════════════════════════════════════════ -->
<nav class="yt-sidebar">
<?php foreach ($groups as $label => $tools): ?>
<div class="yt-nav-group"><?= $label ?></div>
<?php foreach ($tools as [$id, $name, $hint, $iconKey]): ?>
<?= yt_nav($id, $name, $hint, $icons[$iconKey] ?? '') ?>
<?php endforeach; ?>
<?php endforeach; ?>
</nav>

<!-- ══ Tool panels ════════════════════════════════════════════ -->
<div class="yt-content">

<!-- 1. Formatter ─────────────────────────────────────────── -->
<div class="yt-panel" id="yt-formatter">
<?= yt_toolbar('YAML Formatter', '',
    '<div id="fmt-st"></div>'
    . '<label class="yt-select-wrap">Indent <select id="fmt-indent" onchange="YT.runFormatter()"><option value="2">2 spaces</option><option value="4">4 spaces</option></select></label>'
    . '<button class="yt-btn" onclick="YT.cpPane(\'fmt-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="yt-btn" onclick="YT.dl(\'fmt-out\',\'formatted.yaml\',\'text/yaml\')">' . $dl_svg . 'Download</button>'
    . '<button class="yt-btn yt-btn-primary" onclick="YT.runFormatter()">Format</button>'
) ?>
<div class="yt-editors">
    <div class="yt-editor-col">
        <?= yt_in_pane('Input YAML', 'fmt-in') ?>
        <?= yt_ta('fmt-in', 'oninput="YT.runFormatter()" placeholder="Paste or type YAML here…"') ?>
    </div>
    <div class="yt-editor-divider"></div>
    <div class="yt-editor-col">
        <?= yt_out_pane('Formatted Output', 'fmt-meta', 'fmt-out') ?>
        <?= yt_ta('fmt-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 2. Validator ─────────────────────────────────────────── -->
<div class="yt-panel" id="yt-validator" style="display:none">
<?= yt_toolbar('YAML Validator', '',
    '<button class="yt-btn" onclick="(function(){document.getElementById(\'val-in\').value=\'\';var r=document.getElementById(\'val-res\');if(r){r.innerHTML=\'\';r.style.display=\'none\';}})()">Clear</button>'
    . '<button class="yt-btn yt-btn-primary" onclick="YT.runValidator()">Validate</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div class="yt-editor-col" style="flex:1;overflow:hidden">
        <?= yt_in_pane('YAML Input', 'val-in') ?>
        <?= yt_ta('val-in', 'oninput="YT.runValidator()" placeholder="Paste YAML here to check for errors…"') ?>
    </div>
    <div id="val-res" class="yt-val-result" style="display:none"></div>
</div>
</div>

<!-- 3. Viewer ────────────────────────────────────────────── -->
<div class="yt-panel" id="yt-viewer" style="display:none">
<?= yt_toolbar('YAML Viewer', '',
    '<button class="yt-btn" onclick="YT.viewerAll(true)">Expand All</button>'
    . '<button class="yt-btn" onclick="YT.viewerAll(false)">Collapse All</button>'
    . '<button class="yt-btn yt-btn-primary" onclick="YT.runViewer()">View</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="height:140px;display:flex;flex-direction:column;border-bottom:1px solid var(--color-border);flex-shrink:0">
        <?= yt_in_pane('YAML Input', 'view-in') ?>
        <?= yt_ta('view-in', 'oninput="YT.runViewer()" placeholder="Paste YAML to explore as an interactive tree…"') ?>
    </div>
    <div id="view-tree" style="flex:1;overflow:auto;padding:14px;font-family:monospace;font-size:13px;line-height:1.85;background:var(--color-background)">
        <span style="color:var(--color-text-muted);font-size:13px">Paste YAML above to see the interactive tree.</span>
    </div>
</div>
</div>

<!-- 4. Sort Keys ─────────────────────────────────────────── -->
<div class="yt-panel" id="yt-sortkeys" style="display:none">
<?= yt_toolbar('Sort Keys', '<span class="yt-hint">Recursively alphabetise all mapping keys</span>',
    '<div id="sk-st"></div>'
    . '<button class="yt-btn" onclick="YT.cpPane(\'sk-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="yt-btn" onclick="YT.dl(\'sk-out\',\'sorted.yaml\',\'text/yaml\')">' . $dl_svg . 'Download</button>'
    . '<button class="yt-btn yt-btn-primary" onclick="YT.runSortKeys()">Sort</button>'
) ?>
<div class="yt-editors">
    <div class="yt-editor-col">
        <?= yt_in_pane('Input YAML', 'sk-in') ?>
        <?= yt_ta('sk-in', 'oninput="YT.runSortKeys()" placeholder="Paste YAML mapping to sort…"') ?>
    </div>
    <div class="yt-editor-divider"></div>
    <div class="yt-editor-col">
        <?= yt_out_pane('Sorted Output', 'sk-meta', 'sk-out') ?>
        <?= yt_ta('sk-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 5. Flatten / Unflatten ───────────────────────────────── -->
<div class="yt-panel" id="yt-flatten" style="display:none">
<?= yt_toolbar('Flatten / Unflatten', '',
    '<div id="fl-st"></div>'
    . '<label class="yt-select-wrap">Mode <select id="fl-mode" onchange="YT.runFlatten()"><option value="flatten">Flatten</option><option value="unflatten">Unflatten</option></select></label>'
    . '<label class="yt-select-wrap">Separator <select id="fl-sep" onchange="YT.runFlatten()"><option value=".">Dot (.)</option><option value="_">Underscore (_)</option><option value="/">Slash (/)</option></select></label>'
    . '<button class="yt-btn" onclick="YT.cpPane(\'fl-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="yt-btn yt-btn-primary" onclick="YT.runFlatten()">Run</button>'
) ?>
<div class="yt-editors">
    <div class="yt-editor-col">
        <?= yt_in_pane('Input YAML', 'fl-in') ?>
        <?= yt_ta('fl-in', 'oninput="YT.runFlatten()" placeholder="Paste nested YAML to flatten, or flat YAML to unflatten…"') ?>
    </div>
    <div class="yt-editor-divider"></div>
    <div class="yt-editor-col">
        <?= yt_out_pane('Output', 'fl-meta', 'fl-out') ?>
        <?= yt_ta('fl-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 6. Diff ──────────────────────────────────────────────── -->
<div class="yt-panel" id="yt-diff" style="display:none">
<?= yt_toolbar('YAML Diff', '<span class="yt-hint">Compare two YAML documents and see what changed</span>',
    '<div id="diff-st"></div>'
    . '<button class="yt-btn yt-btn-primary" onclick="YT.runDiff()">Compare</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div class="yt-editors" style="height:200px;flex-shrink:0;border-bottom:1px solid var(--color-border)">
        <div class="yt-editor-col">
            <?= yt_in_pane('YAML A (original)', 'diff-a') ?>
            <?= yt_ta('diff-a', 'oninput="YT.runDiff()" placeholder="Paste original YAML (A)…"') ?>
        </div>
        <div class="yt-editor-divider"></div>
        <div class="yt-editor-col">
            <?= yt_in_pane('YAML B (modified)', 'diff-b') ?>
            <?= yt_ta('diff-b', 'oninput="YT.runDiff()" placeholder="Paste modified YAML (B)…"') ?>
        </div>
    </div>
    <div id="diff-out" style="flex:1;overflow:auto;background:var(--color-background)">
        <div class="yt-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Paste YAML into both panes to compare
        </div>
    </div>
</div>
</div>

<!-- 7. Escape ────────────────────────────────────────────── -->
<div class="yt-panel" id="yt-escape" style="display:none">
<?= yt_toolbar('YAML Escape', '<span class="yt-hint">Wraps a string in double-quotes and escapes special characters</span>',
    '<button class="yt-btn" onclick="YT.cpPane(\'esc-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="yt-btn yt-btn-primary" onclick="YT.runEscape()">Escape</button>'
) ?>
<div class="yt-editors">
    <div class="yt-editor-col">
        <?= yt_in_pane('Raw String', 'esc-in', '.txt') ?>
        <?= yt_ta('esc-in', 'oninput="YT.runEscape()" placeholder="Enter text with newlines, colons, quotes…"') ?>
    </div>
    <div class="yt-editor-divider"></div>
    <div class="yt-editor-col">
        <?= yt_out_pane('Escaped Output', 'esc-meta', 'esc-out') ?>
        <?= yt_ta('esc-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 8. Unescape ──────────────────────────────────────────── -->
<div class="yt-panel" id="yt-unescape" style="display:none">
<?= yt_toolbar('YAML Unescape', '<span class="yt-hint">Decodes a quoted YAML scalar back to raw text</span>',
    '<button class="yt-btn" onclick="YT.cpPane(\'unesc-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="yt-btn yt-btn-primary" onclick="YT.runUnescape()">Unescape</button>'
) ?>
<div class="yt-editors">
    <div class="yt-editor-col">
        <?= yt_in_pane('Escaped Input', 'unesc-in', '.txt,.yaml,.yml') ?>
        <?= yt_ta('unesc-in', 'oninput="YT.runUnescape()" placeholder="Paste a quoted YAML scalar to decode…"') ?>
    </div>
    <div class="yt-editor-divider"></div>
    <div class="yt-editor-col">
        <?= yt_out_pane('Unescaped Output', 'unesc-meta', 'unesc-out') ?>
        <?= yt_ta('unesc-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 9. YAML → JSON ───────────────────────────────────────── -->
<div class="yt-panel" id="yt-yaml2json" style="display:none">
<?= yt_toolbar('YAML to JSON', '',
    '<div id="y2j-st"></div>'
    . '<label class="yt-select-wrap">Indent <select id="y2j-indent" onchange="YT.runYaml2Json()"><option value="2">2 spaces</option><option value="4">4 spaces</option></select></label>'
    . '<button class="yt-btn" onclick="YT.cpPane(\'y2j-out\',this)">' . $copy_svg . 'Copy JSON</button>'
    . '<button class="yt-btn" onclick="YT.dl(\'y2j-out\',\'output.json\',\'application/json\')">' . $dl_svg . 'Download</button>'
) ?>
<div class="yt-editors">
    <div class="yt-editor-col">
        <?= yt_in_pane('YAML Input', 'y2j-in') ?>
        <?= yt_ta('y2j-in', 'oninput="YT.runYaml2Json()" placeholder="Paste YAML to convert to JSON…"') ?>
    </div>
    <div class="yt-editor-divider"></div>
    <div class="yt-editor-col">
        <?= yt_out_pane('JSON Output', 'y2j-meta', 'y2j-out') ?>
        <?= yt_ta('y2j-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 10. JSON → YAML ──────────────────────────────────────── -->
<div class="yt-panel" id="yt-json2yaml" style="display:none">
<?= yt_toolbar('JSON to YAML', '',
    '<div id="j2y-st"></div>'
    . '<button class="yt-btn" onclick="YT.cpPane(\'j2y-out\',this)">' . $copy_svg . 'Copy YAML</button>'
    . '<button class="yt-btn" onclick="YT.dl(\'j2y-out\',\'output.yaml\',\'text/yaml\')">' . $dl_svg . 'Download</button>'
) ?>
<div class="yt-editors">
    <div class="yt-editor-col">
        <?= yt_in_pane('JSON Input', 'j2y-in', '.json,.txt') ?>
        <?= yt_ta('j2y-in', 'oninput="YT.runJson2Yaml()" placeholder="Paste JSON object or array…"') ?>
    </div>
    <div class="yt-editor-divider"></div>
    <div class="yt-editor-col">
        <?= yt_out_pane('YAML Output', 'j2y-meta', 'j2y-out') ?>
        <?= yt_ta('j2y-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 11. YAML → CSV ───────────────────────────────────────── -->
<div class="yt-panel" id="yt-yaml2csv" style="display:none">
<?= yt_toolbar('YAML to CSV', '<span class="yt-hint">Top-level sequence of mappings is exported as rows</span>',
    '<div id="y2c-st"></div>'
    . '<button class="yt-btn" onclick="YT.cpPane(\'y2c-out\',this)">' . $copy_svg . 'Copy CSV</button>'
    . '<button class="yt-btn" onclick="YT.dl(\'y2c-out\',\'output.csv\',\'text/csv\')">' . $dl_svg . 'Download</button>'
    . '<button class="yt-btn yt-btn-primary" onclick="YT.runYaml2Csv()">Convert</button>'
) ?>
<div class="yt-editors">
    <div class="yt-editor-col">
        <?= yt_in_pane('YAML Input', 'y2c-in') ?>
        <?= yt_ta('y2c-in', 'oninput="YT.runYaml2Csv()" placeholder="Paste a YAML sequence of mappings…"') ?>
    </div>
    <div class="yt-editor-divider"></div>
    <div class="yt-editor-col">
        <?= yt_out_pane('CSV Output', 'y2c-meta', 'y2c-out') ?>
        <?= yt_ta('y2c-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 12. CSV → YAML ───────────────────────────────────────── -->
<div class="yt-panel" id="yt-csv2yaml" style="display:none">
<?= yt_toolbar('CSV to YAML', '<span class="yt-hint">First row = field names; numbers &amp; booleans are auto-cast</span>',
    '<div id="c2y-st"></div>'
    . '<button class="yt-btn" onclick="YT.cpPane(\'c2y-out\',this)">' . $copy_svg . 'Copy YAML</button>'
    . '<button class="yt-btn" onclick="YT.dl(\'c2y-out\',\'output.yaml\',\'text/yaml\')">' . $dl_svg . 'Download</button>'
    . '<button class="yt-btn yt-btn-primary" onclick="YT.runCsv2Yaml()">Convert</button>'
) ?>
<div class="yt-editors">
    <div class="yt-editor-col">
        <?= yt_in_pane('CSV Input', 'c2y-in', '.csv,.txt') ?>
        <?= yt_ta('c2y-in', 'oninput="YT.runCsv2Yaml()" placeholder="name,version,debug\nMy App,2.0.0,false"') ?>
    </div>
    <div class="yt-editor-divider"></div>
    <div class="yt-editor-col">
        <?= yt_out_pane('YAML Output', 'c2y-meta', 'c2y-out') ?>
        <?= yt_ta('c2y-out', 'readonly') ?>
    </div>
</div>
</div>

</div><!-- /.yt-content -->
</div><!-- /.yt-shell -->

<?php echo plugin_related_html($slug); ?>

<script><?php echo file_get_contents(__DIR__ . '/assets/js-yaml.min.js'); ?></script>
<script><?php echo file_get_contents(__DIR__ . '/assets/yaml-tools.js'); ?></script>

<?php
$content = ob_get_clean();
plugin_render('YAML Tools &mdash; 12 Free Online YAML Utilities', $content, [
    'description' => $_meta['description'] ?? 'Format, validate, view, sort, flatten, diff, and convert YAML with 12 free browser-based tools.',
    'og_title'    => $_meta['title']       ?? 'YAML Tools',
    'og_desc'     => $_meta['description'] ?? '',
    'canonical'   => $_meta['canonical']   ?? '',
]);
