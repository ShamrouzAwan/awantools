<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';
require_once AWAN_ROOT . '/_core/Plugin.php';

$slug      = 'json-tools';
$_manifest = plugin_manifest($slug);
$_meta     = $_manifest['meta'] ?? [];
plugin_track('plugin_view', '/plugins/json-tools/', ['plugin_slug' => $slug]);

/* ── Helpers ─────────────────────────────────────────────────── */
function jt_ta(string $id, string $extra = ''): string {
    return '<textarea id="' . $id . '" class="jt-ta" spellcheck="false" autocomplete="off" ' . $extra . '></textarea>';
}

/* Input pane label: includes Paste + Open File actions */
function jt_in_pane(string $label, string $taId, string $accept = '.json,.txt'): string {
    $pasteBtn = '<button type="button" class="jt-copy-btn" onclick="JT.pasteInto(\'' . $taId . '\', this)">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>'
        . 'Paste</button>';
    $openBtn = '<button type="button" class="jt-copy-btn" onclick="JT.openFile(\'' . $taId . '\',\'' . $accept . '\')">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>'
        . 'Open</button>';
    return '<div class="jt-pane-label"><span>' . $label . '</span>'
         . '<div class="jt-pane-label-right">' . $pasteBtn . $openBtn . '</div></div>';
}

/* Output pane label: includes line/size counter + Copy */
function jt_out_pane(string $label, string $metaId = '', string $taId = ''): string {
    $right = '';
    if ($metaId) $right .= '<span class="jt-pane-meta" id="' . $metaId . '"></span>';
    if ($taId) {
        $right .= '<button type="button" class="jt-copy-btn" onclick="JT.cpPane(\'' . $taId . '\', this)">'
               . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>'
               . 'Copy</button>';
    }
    return '<div class="jt-pane-label"><span>' . $label . '</span>'
         . '<div class="jt-pane-label-right">' . $right . '</div></div>';
}

/* SVG icons */
$icons = [
    'formatter'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>',
    'beautifier'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
    'minifier'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg>',
    'validator'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
    'parser'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
    'viewer'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    'prettyprint' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
    'escape'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 10 4 15 9 20"/><path d="M20 4v7a4 4 0 0 1-4 4H4"/></svg>',
    'unescape'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 10 20 15 15 20"/><path d="M4 4v7a4 4 0 0 0 4 4h12"/></svg>',
    'json2csv'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="1"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="9" x2="9" y2="21"/></svg>',
    'csv2json'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
    'json2xml'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
    'xml2json'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    'json2yaml'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>',
    'yaml2json'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>',
    'diff'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    'sortkeys'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
    'flatten'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
    'jsonpath'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
];

function jt_nav(string $id, string $name, string $hint, string $icon): string {
    return '<button class="jt-nav" data-tool="' . $id . '" onclick="JT.switchTool(\'' . $id . '\')">'
         . '<span class="jt-nav-icon">' . $icon . '</span>'
         . '<span class="jt-nav-text">'
         .   '<span class="jt-nav-name">' . $name . '</span>'
         .   '<span class="jt-nav-hint">' . $hint . '</span>'
         . '</span></button>';
}

$groups = [
    'Format &amp; View' => [
        ['formatter',   'Formatter',    'Indent &amp; format',    'formatter'],
        ['beautifier',  'Beautifier',   'Human-readable',         'beautifier'],
        ['minifier',    'Minifier',     'Strip whitespace',       'minifier'],
        ['validator',   'Validator',    'Check for errors',       'validator'],
        ['parser',      'Parser',       'Inspect structure',      'parser'],
        ['viewer',      'Viewer',       'Interactive tree',       'viewer'],
        ['prettyprint', 'Pretty Print', 'Syntax highlight',       'prettyprint'],
    ],
    'Transform' => [
        ['sortkeys',    'Sort Keys',    'Alphabetical sort',      'sortkeys'],
        ['flatten',     'Flatten',      'Dot-notation &amp; back','flatten'],
        ['jsonpath',    'JSONPath',     'Query &amp; extract',    'jsonpath'],
        ['diff',        'Diff',         'Compare two JSONs',      'diff'],
    ],
    'String Ops' => [
        ['escape',   'Escape',   'Escape for JSON',  'escape'],
        ['unescape', 'Unescape', 'Decode escaped',   'unescape'],
    ],
    'Convert' => [
        ['json2csv',  'JSON &rarr; CSV',  'Export tabular',  'json2csv'],
        ['csv2json',  'CSV &rarr; JSON',  'Import from CSV', 'csv2json'],
        ['json2xml',  'JSON &rarr; XML',  'XML serialise',   'json2xml'],
        ['xml2json',  'XML &rarr; JSON',  'Parse XML',       'xml2json'],
        ['json2yaml', 'JSON &rarr; YAML', 'YAML serialise',  'json2yaml'],
        ['yaml2json', 'YAML &rarr; JSON', 'Parse YAML',      'yaml2json'],
    ],
];

function jt_toolbar(string $title, string $left = '', string $right = ''): string {
    return '<div class="jt-toolbar">'
         . '<div class="jt-toolbar-left"><span class="jt-tool-title">' . $title . '</span>' . $left . '</div>'
         . '<div class="jt-toolbar-right">' . $right . '</div>'
         . '</div>';
}

$copy_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
$dl_svg   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';

ob_start();
?>
<style><?php echo file_get_contents(__DIR__ . '/assets/json-tools.css'); ?></style>

<div class="header-hero">
    <div class="page-header-left">
        <div class="header-hero-title" >JSON Tools</div>
        <div class="header-hero-subtitle">19 client-side utilities &mdash; format, validate, diff, transform &amp; convert JSON</div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-ghost btn-sm header-hstat" onclick="JT.loadSample()">Load Sample</button>
        <button class="btn btn-ghost btn-sm header-hstat" onclick="JT.clearAll()">Clear</button>
    </div>
</div>

<div class="jt-shell">

<!-- ══ Sidebar / Tab strip ════════════════════════════════════ -->
<nav class="jt-sidebar">
<?php foreach ($groups as $label => $tools): ?>
<div class="jt-nav-group"><?= $label ?></div>
<?php foreach ($tools as [$id, $name, $hint, $iconKey]): ?>
<?= jt_nav($id, $name, $hint, $icons[$iconKey] ?? '') ?>
<?php endforeach; ?>
<?php endforeach; ?>
</nav>

<!-- ══ Tool panels ════════════════════════════════════════════ -->
<div class="jt-content">

<!-- 1. Formatter ─────────────────────────────────────────── -->
<div class="jt-panel" id="jt-formatter">
<?= jt_toolbar('JSON Formatter', '',
    '<div id="fmt-st"></div>'
    . '<label class="jt-select-wrap">Indent <select id="fmt-indent" onchange="JT.runFormatter()"><option value="2">2 spaces</option><option value="4">4 spaces</option><option value="tab">Tab</option></select></label>'
    . '<button class="jt-btn" onclick="JT.cpPane(\'fmt-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="jt-btn jt-btn-primary" onclick="JT.runFormatter()">Format</button>'
) ?>
<div class="jt-editors">
    <div class="jt-editor-col">
        <?= jt_in_pane('Input JSON', 'fmt-in') ?>
        <?= jt_ta('fmt-in', 'oninput="JT.runFormatter()" placeholder="Paste or type JSON here…"') ?>
    </div>
    <div class="jt-editor-divider"></div>
    <div class="jt-editor-col">
        <?= jt_out_pane('Formatted Output', 'fmt-meta', 'fmt-out') ?>
        <?= jt_ta('fmt-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 2. Beautifier ────────────────────────────────────────── -->
<div class="jt-panel" id="jt-beautifier" style="display:none">
<?= jt_toolbar('JSON Beautifier', '',
    '<div id="beau-st"></div>'
    . '<button class="jt-btn" onclick="JT.cpPane(\'beau-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="jt-btn jt-btn-primary" onclick="JT.runBeautifier()">Beautify</button>'
) ?>
<div class="jt-editors">
    <div class="jt-editor-col">
        <?= jt_in_pane('Input JSON', 'beau-in') ?>
        <?= jt_ta('beau-in', 'oninput="JT.runBeautifier()" placeholder="Paste JSON to beautify…"') ?>
    </div>
    <div class="jt-editor-divider"></div>
    <div class="jt-editor-col">
        <?= jt_out_pane('Beautified Output', 'beau-meta', 'beau-out') ?>
        <?= jt_ta('beau-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 3. Minifier ──────────────────────────────────────────── -->
<div class="jt-panel" id="jt-minifier" style="display:none">
<?= jt_toolbar('JSON Minifier', '',
    '<div id="min-st"></div>'
    . '<button class="jt-btn" onclick="JT.cpPane(\'min-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="jt-btn jt-btn-primary" onclick="JT.runMinifier()">Minify</button>'
) ?>
<div class="jt-editors">
    <div class="jt-editor-col">
        <?= jt_in_pane('Input JSON', 'min-in') ?>
        <?= jt_ta('min-in', 'oninput="JT.runMinifier()" placeholder="Paste formatted JSON to minify…"') ?>
    </div>
    <div class="jt-editor-divider"></div>
    <div class="jt-editor-col">
        <?= jt_out_pane('Minified Output', 'min-meta', 'min-out') ?>
        <?= jt_ta('min-out', 'readonly style="word-break:break-all"') ?>
    </div>
</div>
</div>

<!-- 4. Validator ─────────────────────────────────────────── -->
<div class="jt-panel" id="jt-validator" style="display:none">
<?= jt_toolbar('JSON Validator', '',
    '<button class="jt-btn" onclick="JT.clrValidator()">Clear</button>'
    . '<button class="jt-btn jt-btn-primary" onclick="JT.runValidator()">Validate</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div class="jt-editor-col" style="flex:1;overflow:hidden">
        <?= jt_in_pane('JSON Input', 'val-in') ?>
        <?= jt_ta('val-in', 'oninput="JT.runValidator()" placeholder="Paste JSON here to check for errors…"') ?>
    </div>
    <div id="val-res" class="jt-val-result" style="display:none"></div>
</div>
</div>

<!-- 5. Parser ────────────────────────────────────────────── -->
<div class="jt-panel" id="jt-parser" style="display:none">
<?= jt_toolbar('JSON Parser', '',
    '<button class="jt-btn" onclick="JT.clrParser()">Clear</button>'
    . '<button class="jt-btn jt-btn-primary" onclick="JT.runParser()">Parse</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="height:160px;display:flex;flex-direction:column;border-bottom:1px solid var(--color-border);flex-shrink:0">
        <?= jt_in_pane('JSON Input', 'par-in') ?>
        <?= jt_ta('par-in', 'oninput="JT.runParser()" placeholder="Paste JSON to inspect types and structure…"') ?>
    </div>
    <div id="par-res" style="flex:1;overflow:auto"></div>
</div>
</div>

<!-- 6. Viewer ────────────────────────────────────────────── -->
<div class="jt-panel" id="jt-viewer" style="display:none">
<?= jt_toolbar('JSON Viewer', '',
    '<button class="jt-btn" onclick="JT.viewerAll(true)">Expand All</button>'
    . '<button class="jt-btn" onclick="JT.viewerAll(false)">Collapse All</button>'
    . '<button class="jt-btn jt-btn-primary" onclick="JT.runViewer()">View</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="height:140px;display:flex;flex-direction:column;border-bottom:1px solid var(--color-border);flex-shrink:0">
        <?= jt_in_pane('JSON Input', 'view-in') ?>
        <?= jt_ta('view-in', 'oninput="JT.runViewer()" placeholder="Paste JSON to explore as an interactive tree…"') ?>
    </div>
    <div id="view-tree" style="flex:1;overflow:auto;padding:14px;font-family:monospace;font-size:13px;line-height:1.85;background:var(--color-background)">
        <span style="color:var(--color-text-muted);font-size:13px">Paste JSON above to see the interactive tree.</span>
    </div>
</div>
</div>

<!-- 7. Pretty Print ──────────────────────────────────────── -->
<div class="jt-panel" id="jt-prettyprint" style="display:none">
<?= jt_toolbar('JSON Pretty Print', '',
    '<div id="pp-st"></div>'
    . '<button class="jt-btn jt-btn-primary" onclick="JT.runPrettyPrint()">Pretty Print</button>'
) ?>
<div class="jt-editors">
    <div class="jt-editor-col">
        <?= jt_in_pane('Input JSON', 'pp-in') ?>
        <?= jt_ta('pp-in', 'oninput="JT.runPrettyPrint()" placeholder="Paste JSON to view with syntax highlighting…"') ?>
    </div>
    <div class="jt-editor-divider"></div>
    <div class="jt-editor-col" style="display:flex;flex-direction:column;overflow:hidden">
        <?= jt_out_pane('Highlighted Output') ?>
        <pre id="pp-out" style="flex:1;margin:0;padding:12px 14px;background:var(--color-background);font-family:monospace;font-size:13px;line-height:1.65;overflow:auto;white-space:pre-wrap;word-break:break-word"></pre>
    </div>
</div>
</div>

<!-- 8. Sort Keys ─────────────────────────────────────────── -->
<div class="jt-panel" id="jt-sortkeys" style="display:none">
<?= jt_toolbar('Sort Keys', '<span class="jt-hint">Recursively alphabetise all object keys</span>',
    '<div id="sk-st"></div>'
    . '<button class="jt-btn" onclick="JT.cpPane(\'sk-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="jt-btn" onclick="JT.dl(\'sk-out\',\'sorted.json\',\'application/json\')">' . $dl_svg . 'Download</button>'
    . '<button class="jt-btn jt-btn-primary" onclick="JT.runSortKeys()">Sort</button>'
) ?>
<div class="jt-editors">
    <div class="jt-editor-col">
        <?= jt_in_pane('Input JSON', 'sk-in') ?>
        <?= jt_ta('sk-in', 'oninput="JT.runSortKeys()" placeholder="Paste JSON object to sort…"') ?>
    </div>
    <div class="jt-editor-divider"></div>
    <div class="jt-editor-col">
        <?= jt_out_pane('Sorted Output', 'sk-meta', 'sk-out') ?>
        <?= jt_ta('sk-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 9. Flatten ───────────────────────────────────────────── -->
<div class="jt-panel" id="jt-flatten" style="display:none">
<?= jt_toolbar('Flatten / Unflatten', '',
    '<div id="fl-st"></div>'
    . '<label class="jt-select-wrap">Mode <select id="fl-mode" onchange="JT.runFlatten()"><option value="flatten">Flatten</option><option value="unflatten">Unflatten</option></select></label>'
    . '<label class="jt-select-wrap">Separator <select id="fl-sep" onchange="JT.runFlatten()"><option value=".">Dot (.)</option><option value="_">Underscore (_)</option><option value="/">Slash (/)</option></select></label>'
    . '<button class="jt-btn" onclick="JT.cpPane(\'fl-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="jt-btn jt-btn-primary" onclick="JT.runFlatten()">Run</button>'
) ?>
<div class="jt-editors">
    <div class="jt-editor-col">
        <?= jt_in_pane('Input JSON', 'fl-in') ?>
        <?= jt_ta('fl-in', 'oninput="JT.runFlatten()" placeholder="Paste nested JSON to flatten, or flat JSON to unflatten…"') ?>
    </div>
    <div class="jt-editor-divider"></div>
    <div class="jt-editor-col">
        <?= jt_out_pane('Output', 'fl-meta', 'fl-out') ?>
        <?= jt_ta('fl-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 10. JSONPath ─────────────────────────────────────────── -->
<div class="jt-panel" id="jt-jsonpath" style="display:none">
<?= jt_toolbar('JSONPath Query', '',
    '<div id="jp-st"></div>'
    . '<button class="jt-btn jt-btn-primary" onclick="JT.runJsonPath()">Query</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div class="jt-jp-query-row">
        <span class="jt-jp-dollar">$</span>
        <input type="text" id="jp-query" class="jt-jp-input" placeholder=".config.limits  or  .tags[*]  or  ..version" oninput="JT.runJsonPath()">
        <button class="jt-btn jt-btn-primary" onclick="JT.runJsonPath()">Run</button>
    </div>
    <div class="jt-editors" style="flex:1;border-top:1px solid var(--color-border)">
        <div class="jt-editor-col">
            <?= jt_in_pane('JSON Input', 'jp-in') ?>
            <?= jt_ta('jp-in', 'oninput="JT.runJsonPath()" placeholder="Paste JSON to query…"') ?>
        </div>
        <div class="jt-editor-divider"></div>
        <div class="jt-editor-col" style="display:flex;flex-direction:column;overflow:hidden">
            <?= jt_out_pane('Results') ?>
            <pre id="jp-out-pre" style="flex:1;margin:0;padding:12px 14px;background:var(--color-background);font-family:monospace;font-size:13px;line-height:1.65;overflow:auto;white-space:pre-wrap;word-break:break-word"></pre>
        </div>
    </div>
</div>
</div>

<!-- 11. Diff ─────────────────────────────────────────────── -->
<div class="jt-panel" id="jt-diff" style="display:none">
<?= jt_toolbar('JSON Diff', '<span class="jt-hint">Compare two JSON values and see what changed</span>',
    '<div id="diff-st"></div>'
    . '<button class="jt-btn jt-btn-primary" onclick="JT.runDiff()">Compare</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div class="jt-editors" style="height:200px;flex-shrink:0;border-bottom:1px solid var(--color-border)">
        <div class="jt-editor-col">
            <?= jt_in_pane('JSON A (original)', 'diff-a') ?>
            <?= jt_ta('diff-a', 'oninput="JT.runDiff()" placeholder="Paste original JSON (A)…"') ?>
        </div>
        <div class="jt-editor-divider"></div>
        <div class="jt-editor-col">
            <?= jt_in_pane('JSON B (modified)', 'diff-b') ?>
            <?= jt_ta('diff-b', 'oninput="JT.runDiff()" placeholder="Paste modified JSON (B)…"') ?>
        </div>
    </div>
    <div id="diff-out" style="flex:1;overflow:auto;background:var(--color-background)">
        <div class="jt-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Paste JSON into both panes to compare
        </div>
    </div>
</div>
</div>

<!-- 12. Escape ───────────────────────────────────────────── -->
<div class="jt-panel" id="jt-escape" style="display:none">
<?= jt_toolbar('JSON Escape', '<span class="jt-hint">Escape raw strings for embedding inside a JSON value</span>',
    '<button class="jt-btn" onclick="JT.cpPane(\'esc-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="jt-btn jt-btn-primary" onclick="JT.runEscape()">Escape</button>'
) ?>
<div class="jt-editors">
    <div class="jt-editor-col">
        <?= jt_in_pane('Raw String', 'esc-in', '.txt') ?>
        <?= jt_ta('esc-in', 'oninput="JT.runEscape()" placeholder="Enter raw text with quotes, newlines, tabs…"') ?>
    </div>
    <div class="jt-editor-divider"></div>
    <div class="jt-editor-col">
        <?= jt_out_pane('Escaped Output', 'esc-meta', 'esc-out') ?>
        <?= jt_ta('esc-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 13. Unescape ─────────────────────────────────────────── -->
<div class="jt-panel" id="jt-unescape" style="display:none">
<?= jt_toolbar('JSON Unescape', '<span class="jt-hint">Decode a JSON-escaped string back to raw text</span>',
    '<button class="jt-btn" onclick="JT.cpPane(\'unesc-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="jt-btn jt-btn-primary" onclick="JT.runUnescape()">Unescape</button>'
) ?>
<div class="jt-editors">
    <div class="jt-editor-col">
        <?= jt_in_pane('Escaped Input', 'unesc-in', '.txt,.json') ?>
        <?= jt_ta('unesc-in', 'oninput="JT.runUnescape()" placeholder="Paste escaped JSON string value…"') ?>
    </div>
    <div class="jt-editor-divider"></div>
    <div class="jt-editor-col">
        <?= jt_out_pane('Unescaped Output', 'unesc-meta', 'unesc-out') ?>
        <?= jt_ta('unesc-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 14. JSON → CSV ───────────────────────────────────────── -->
<div class="jt-panel" id="jt-json2csv" style="display:none">
<?= jt_toolbar('JSON to CSV', '',
    '<div id="j2c-st"></div>'
    . '<button class="jt-btn" onclick="JT.cpPane(\'j2c-out\',this)">' . $copy_svg . 'Copy CSV</button>'
    . '<button class="jt-btn" onclick="JT.dl(\'j2c-out\',\'data.csv\',\'text/csv\')">' . $dl_svg . 'Download</button>'
) ?>
<div class="jt-editors">
    <div class="jt-editor-col">
        <?= jt_in_pane('JSON Array Input', 'j2c-in') ?>
        <?= jt_ta('j2c-in', 'oninput="JT.runJson2Csv()" placeholder="Paste a JSON array of objects…"') ?>
    </div>
    <div class="jt-editor-divider"></div>
    <div class="jt-editor-col">
        <?= jt_out_pane('CSV Output', 'j2c-meta', 'j2c-out') ?>
        <?= jt_ta('j2c-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 15. CSV → JSON ───────────────────────────────────────── -->
<div class="jt-panel" id="jt-csv2json" style="display:none">
<?= jt_toolbar('CSV to JSON', '<span class="jt-hint">First row = header names</span>',
    '<div id="c2j-st"></div>'
    . '<button class="jt-btn" onclick="JT.cpPane(\'c2j-out\',this)">' . $copy_svg . 'Copy JSON</button>'
) ?>
<div class="jt-editors">
    <div class="jt-editor-col">
        <?= jt_in_pane('CSV Input', 'c2j-in', '.csv,.txt') ?>
        <?= jt_ta('c2j-in', 'oninput="JT.runCsv2Json()" placeholder="name,age,city\nAlice,30,London\nBob,25,Paris"') ?>
    </div>
    <div class="jt-editor-divider"></div>
    <div class="jt-editor-col">
        <?= jt_out_pane('JSON Output', 'c2j-meta', 'c2j-out') ?>
        <?= jt_ta('c2j-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 16. JSON → XML ───────────────────────────────────────── -->
<div class="jt-panel" id="jt-json2xml" style="display:none">
<?= jt_toolbar('JSON to XML', '',
    '<div id="j2x-st"></div>'
    . '<button class="jt-btn" onclick="JT.cpPane(\'j2x-out\',this)">' . $copy_svg . 'Copy XML</button>'
) ?>
<div class="jt-editors">
    <div class="jt-editor-col">
        <?= jt_in_pane('JSON Input', 'j2x-in') ?>
        <?= jt_ta('j2x-in', 'oninput="JT.runJson2Xml()" placeholder="Paste JSON object…"') ?>
    </div>
    <div class="jt-editor-divider"></div>
    <div class="jt-editor-col">
        <?= jt_out_pane('XML Output', 'j2x-meta', 'j2x-out') ?>
        <?= jt_ta('j2x-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 17. XML → JSON ───────────────────────────────────────── -->
<div class="jt-panel" id="jt-xml2json" style="display:none">
<?= jt_toolbar('XML to JSON', '',
    '<div id="x2j-st"></div>'
    . '<button class="jt-btn" onclick="JT.cpPane(\'x2j-out\',this)">' . $copy_svg . 'Copy JSON</button>'
) ?>
<div class="jt-editors">
    <div class="jt-editor-col">
        <?= jt_in_pane('XML Input', 'x2j-in', '.xml,.txt') ?>
        <?= jt_ta('x2j-in', 'oninput="JT.runXml2Json()" placeholder="Paste XML markup…"') ?>
    </div>
    <div class="jt-editor-divider"></div>
    <div class="jt-editor-col">
        <?= jt_out_pane('JSON Output', 'x2j-meta', 'x2j-out') ?>
        <?= jt_ta('x2j-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 18. JSON → YAML ──────────────────────────────────────── -->
<div class="jt-panel" id="jt-json2yaml" style="display:none">
<?= jt_toolbar('JSON to YAML', '',
    '<div id="j2y-st"></div>'
    . '<button class="jt-btn" onclick="JT.cpPane(\'j2y-out\',this)">' . $copy_svg . 'Copy YAML</button>'
) ?>
<div class="jt-editors">
    <div class="jt-editor-col">
        <?= jt_in_pane('JSON Input', 'j2y-in') ?>
        <?= jt_ta('j2y-in', 'oninput="JT.runJson2Yaml()" placeholder="Paste JSON object or array…"') ?>
    </div>
    <div class="jt-editor-divider"></div>
    <div class="jt-editor-col">
        <?= jt_out_pane('YAML Output', 'j2y-meta', 'j2y-out') ?>
        <?= jt_ta('j2y-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 19. YAML → JSON ──────────────────────────────────────── -->
<div class="jt-panel" id="jt-yaml2json" style="display:none">
<?= jt_toolbar('YAML to JSON', '',
    '<div id="y2j-st"></div>'
    . '<button class="jt-btn" onclick="JT.cpPane(\'y2j-out\',this)">' . $copy_svg . 'Copy JSON</button>'
) ?>
<div class="jt-editors">
    <div class="jt-editor-col">
        <?= jt_in_pane('YAML Input', 'y2j-in', '.yaml,.yml,.txt') ?>
        <?= jt_ta('y2j-in', 'oninput="JT.runYaml2Json()" placeholder="Paste YAML…"') ?>
    </div>
    <div class="jt-editor-divider"></div>
    <div class="jt-editor-col">
        <?= jt_out_pane('JSON Output', 'y2j-meta', 'y2j-out') ?>
        <?= jt_ta('y2j-out', 'readonly') ?>
    </div>
</div>
</div>

</div><!-- /jt-content -->
</div><!-- /jt-shell -->
<?php echo plugin_related_html($slug); ?>

<script><?php echo file_get_contents(__DIR__ . '/assets/js-yaml.min.js'); ?></script>
<script><?php echo file_get_contents(__DIR__ . '/assets/json-tools.js'); ?></script>
<?php
$content = ob_get_clean();
plugin_render($_meta['title'] ?? 'JSON Tools', $content, [
    'description' => $_meta['description'] ?? '',
    'og_image'    => $_meta['og_image'] ?? '',
    'canonical'   => $_meta['canonical'] ?? '',
]);
