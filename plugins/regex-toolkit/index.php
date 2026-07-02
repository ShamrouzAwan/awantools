<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';
require_once AWAN_ROOT . '/_core/Plugin.php';

$slug      = 'regex-toolkit';
$_manifest = plugin_manifest($slug);
$_meta     = $_manifest['meta'] ?? [];
plugin_track('plugin_view', '/plugins/regex-toolkit/', ['plugin_slug' => $slug]);

/* ── Helpers ─────────────────────────────────────────────────── */
function rt_ta(string $id, string $extra = ''): string {
    return '<textarea id="' . $id . '" class="rt-ta" spellcheck="false" autocomplete="off" ' . $extra . '></textarea>';
}

function rt_pane_label(string $left, string $right = ''): string {
    return '<div class="rt-pane-label"><span>' . $left . '</span>'
         . '<div class="rt-pane-label-right">' . $right . '</div></div>';
}

function rt_copy_btn(string $taId, string $label = 'Copy'): string {
    $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
    return '<button type="button" class="rt-copy-btn" onclick="RT.cpEl(\'' . $taId . '\',this)">' . $svg . $label . '</button>';
}

function rt_paste_btn(string $taId): string {
    $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>';
    return '<button type="button" class="rt-copy-btn" onclick="if(navigator.clipboard&&navigator.clipboard.readText)navigator.clipboard.readText().then(function(t){document.getElementById(\'' . $taId . '\').value=t;RT.runCurrent&&RT.runCurrent();})">' . $svg . 'Paste</button>';
}

function rt_toolbar(string $title, string $left = '', string $right = ''): string {
    return '<div class="rt-toolbar">'
         . '<div class="rt-toolbar-left"><span class="rt-tool-title">' . $title . '</span>' . $left . '</div>'
         . '<div class="rt-toolbar-right">' . $right . '</div>'
         . '</div>';
}

function rt_regex_row(string $patId, string $flagsId, string $oninput = '', bool $gDefault = true): string {
    $g = $gDefault ? ' on' : '';
    return '<div class="rt-regex-row">'
         . '<span class="rt-slash">/</span>'
         . '<input type="text" id="' . $patId . '" class="rt-regex-input" spellcheck="false" autocomplete="off" placeholder="enter pattern…" oninput="' . $oninput . '">'
         . '<span class="rt-slash">/</span>'
         . '<div id="' . $flagsId . '" class="rt-flags">'
         . '<button class="rt-flag' . $g . '" data-flag="g" onclick="RT.toggleFlag(this)" title="global — find all matches">g</button>'
         . '<button class="rt-flag" data-flag="i" onclick="RT.toggleFlag(this)" title="case-insensitive">i</button>'
         . '<button class="rt-flag" data-flag="m" onclick="RT.toggleFlag(this)" title="multiline — ^ $ match line ends">m</button>'
         . '<button class="rt-flag" data-flag="s" onclick="RT.toggleFlag(this)" title="dotAll — . matches newlines">s</button>'
         . '</div>'
         . '</div>';
}

function rt_nav(string $id, string $name, string $hint, string $icon): string {
    return '<button class="rt-nav" data-tool="' . $id . '" onclick="RT.switchTool(\'' . $id . '\')">'
         . '<span class="rt-nav-icon">' . $icon . '</span>'
         . '<span class="rt-nav-text">'
         .   '<span class="rt-nav-name">' . $name . '</span>'
         .   '<span class="rt-nav-hint">' . $hint . '</span>'
         . '</span></button>';
}

$icons = [
    'test'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
    'replace' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>',
    'extract' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
    'highlight'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
    'explain' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    'valid'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
    'named'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
    'gen'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
    'lib'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
    'cheat'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
];

$groups = [
    'Test &amp; Match' => [
        ['tester',      'Tester',           'Test &amp; highlight',    'test'],
        ['replace',     'Replace',          'Find &amp; replace',      'replace'],
        ['extractor',   'Extractor',        'Extract all matches',     'extract'],
        ['highlighter', 'Highlighter',      'Colour-coded matches',    'highlight'],
    ],
    'Analyze' => [
        ['explainer',   'Explainer',        'Human-readable breakdown','explain'],
        ['validator',   'Validator',        'Validate &amp; inspect',  'valid'],
        ['namedgroups', 'Named Groups',     'Named capture groups',    'named'],
    ],
    'Build &amp; Reference' => [
        ['generator',   'Generator',        'Template patterns',       'gen'],
        ['patterns',    'Pattern Library',  '24 common patterns',      'lib'],
        ['cheatsheet',  'Cheat Sheet',      'Quick reference',         'cheat'],
    ],
];

$copy_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';

ob_start();
?>
<style><?php echo file_get_contents(__DIR__ . '/assets/regex-toolkit.css'); ?></style>

<div class="page-header" style="margin-bottom:0;border-bottom:1px solid var(--color-border);padding-bottom:14px">
    <div class="page-header-left">
        <div class="page-title">Regex Toolkit</div>
        <div class="page-subtitle">10 browser-based regex utilities &mdash; test, replace, extract, explain, validate, highlight &amp; more</div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-ghost btn-sm" onclick="RT.loadSample()">Load Sample</button>
        <button class="btn btn-ghost btn-sm" onclick="RT.clearCurrent()">Clear</button>
    </div>
</div>

<div class="rt-shell">

<!-- ══ Sidebar ════════════════════════════════════════════════ -->
<nav class="rt-sidebar">
<?php foreach ($groups as $label => $tools): ?>
<div class="rt-nav-group"><?= $label ?></div>
<?php foreach ($tools as [$id, $name, $hint, $iconKey]): ?>
<?= rt_nav($id, $name, $hint, $icons[$iconKey] ?? '') ?>
<?php endforeach; ?>
<?php endforeach; ?>
</nav>

<!-- ══ Tool panels ════════════════════════════════════════════ -->
<div class="rt-content">

<!-- ══════════════════════════════════════════════════════════════
     1. REGEX TESTER
     ══════════════════════════════════════════════════════════════ -->
<div class="rt-panel" id="rt-tester">
<?= rt_toolbar('Regex Tester',
    '<span class="rt-hint">Live match highlighting with capture groups</span>',
    '<div id="t-status"></div>'
    . '<button class="rt-btn rt-btn-primary" onclick="RT.runTester()">Test</button>'
) ?>
<?= rt_regex_row('t-pat', 't-flags', 'RT.runTester()') ?>
<div class="rt-editors">
    <div class="rt-editor-col">
        <?= rt_pane_label('Test String', rt_paste_btn('t-text') . rt_copy_btn('t-text', 'Copy')) ?>
        <?= rt_ta('t-text', 'oninput="RT.runTester()" placeholder="Paste test string here…"') ?>
    </div>
    <div class="rt-editor-divider"></div>
    <div class="rt-editor-col">
        <?= rt_pane_label('Matches') ?>
        <div id="t-matches" class="rt-match-list">
            <div class="rt-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Enter a pattern above to begin</div>
        </div>
    </div>
</div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     2. REGEX REPLACE
     ══════════════════════════════════════════════════════════════ -->
<div class="rt-panel" id="rt-replace" style="display:none">
<?= rt_toolbar('Regex Replace',
    '<span class="rt-hint">Use $1, $2 … or $&lt;name&gt; in the replacement</span>',
    '<div id="rp-status"></div>'
    . '<button class="rt-btn" onclick="RT.cpEl(\'rp-result-ta\',this)">' . $copy_svg . 'Copy Result</button>'
    . '<button class="rt-btn rt-btn-primary" onclick="RT.runReplace()">Replace</button>'
) ?>
<?= rt_regex_row('rp-pat', 'rp-flags', 'RT.runReplace()') ?>
<div style="padding:7px 10px;border-bottom:1px solid var(--color-border);background:var(--color-surface);display:flex;align-items:center;gap:8px;flex-shrink:0">
    <span style="font-size:12px;color:var(--color-text-muted);white-space:nowrap">Replace with:</span>
    <input type="text" id="rp-repl" class="rt-regex-input" style="font-size:13px" placeholder="replacement string (use $1, $2 for groups)…" oninput="RT.runReplace()">
</div>
<div class="rt-editors">
    <div class="rt-editor-col">
        <?= rt_pane_label('Input Text', rt_paste_btn('rp-text')) ?>
        <?= rt_ta('rp-text', 'oninput="RT.runReplace()" placeholder="Paste text to search and replace…"') ?>
    </div>
    <div class="rt-editor-divider"></div>
    <div class="rt-editor-col" style="display:flex;flex-direction:column">
        <?= rt_pane_label('Result') ?>
        <?= rt_ta('rp-result-ta', 'readonly style="flex:1;min-height:0"') ?>
        <div id="rp-out" style="min-height:90px;max-height:140px;overflow:auto;border-top:1px solid var(--color-border);background:var(--color-surface)">
            <div style="padding:10px 14px;font-size:12px;color:var(--color-text-muted)">Diff will appear here after a replacement.</div>
        </div>
    </div>
</div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     3. REGEX EXTRACTOR
     ══════════════════════════════════════════════════════════════ -->
<div class="rt-panel" id="rt-extractor" style="display:none">
<?= rt_toolbar('Regex Extractor',
    '<span class="rt-hint">Extracts all matches into a table with capture groups</span>',
    '<div id="ex-status"></div>'
    . '<button class="rt-btn rt-btn-primary" onclick="RT.runExtractor()">Extract</button>'
) ?>
<?= rt_regex_row('ex-pat', 'ex-flags', 'RT.runExtractor()') ?>
<div class="rt-editors">
    <div class="rt-editor-col" style="max-width:340px;border-right:1px solid var(--color-border)">
        <?= rt_pane_label('Test String', rt_paste_btn('ex-text')) ?>
        <?= rt_ta('ex-text', 'oninput="RT.runExtractor()" placeholder="Paste text to extract from…"') ?>
    </div>
    <div class="rt-editor-col" style="overflow:auto">
        <?= rt_pane_label('Extracted Matches') ?>
        <div id="ex-out" style="flex:1;overflow:auto;background:var(--color-background)">
            <div class="rt-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Enter a pattern to extract matches</div>
        </div>
    </div>
</div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     4. REGEX HIGHLIGHTER
     ══════════════════════════════════════════════════════════════ -->
<div class="rt-panel" id="rt-highlighter" style="display:none">
<?= rt_toolbar('Regex Highlighter',
    '<span class="rt-hint">Colour-codes each successive match in the text</span>',
    '<div id="hl-status"></div>'
) ?>
<?= rt_regex_row('hl-pat', 'hl-flags', 'RT.runHighlighter()') ?>
<div class="rt-editors">
    <div class="rt-editor-col">
        <?= rt_pane_label('Test String', rt_paste_btn('hl-text')) ?>
        <?= rt_ta('hl-text', 'oninput="RT.runHighlighter()" placeholder="Type or paste the text to highlight matches in…"') ?>
    </div>
    <div class="rt-editor-divider"></div>
    <div class="rt-editor-col">
        <?= rt_pane_label('Highlighted Output') ?>
        <div id="hl-out" class="rt-highlight-wrap">Matches will be highlighted here.</div>
    </div>
</div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     5. REGEX EXPLAINER
     ══════════════════════════════════════════════════════════════ -->
<div class="rt-panel" id="rt-explainer" style="display:none">
<?= rt_toolbar('Regex Explainer',
    '<span class="rt-hint">Breaks down every token in your pattern into plain English</span>',
    '<button class="rt-btn rt-btn-primary" onclick="RT.runExplainer()">Explain</button>'
) ?>
<div style="border-bottom:1px solid var(--color-border);background:var(--color-surface);padding:8px 10px;flex-shrink:0;display:flex;align-items:center;gap:6px">
    <span class="rt-slash">/</span>
    <input type="text" id="xp-pat" class="rt-regex-input" spellcheck="false" autocomplete="off" placeholder="enter any regex pattern to explain…" oninput="RT.runExplainer()">
    <span class="rt-slash">/</span>
</div>
<div id="xp-out" class="rt-explain-list" style="flex:1;overflow-y:auto">
    <div class="rt-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>Enter a regex pattern to explain it</div>
</div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     6. REGEX VALIDATOR
     ══════════════════════════════════════════════════════════════ -->
<div class="rt-panel" id="rt-validator" style="display:none">
<?= rt_toolbar('Regex Validator',
    '<span class="rt-hint">Validates syntax and inspects the structure of any regex</span>',
    '<div id="vl-status"></div>'
    . '<button class="rt-btn rt-btn-primary" onclick="RT.runValidator()">Validate</button>'
) ?>
<?= rt_regex_row('vl-pat', 'vl-flags', 'RT.runValidator()', false) ?>
<div style="padding:7px 10px;border-bottom:1px solid var(--color-border);background:var(--color-surface);display:flex;align-items:center;gap:8px;flex-shrink:0">
    <span style="font-size:12px;color:var(--color-text-muted);white-space:nowrap">Test string (optional):</span>
    <input type="text" id="vl-text" class="rt-regex-input" style="font-size:13px" placeholder="Enter a string to test the pattern against…" oninput="RT.runValidator()">
</div>
<div id="vl-detail" style="flex:1;overflow:auto;background:var(--color-background)">
    <div class="rt-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg>Enter a pattern to validate</div>
</div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     7. NAMED GROUPS VIEWER
     ══════════════════════════════════════════════════════════════ -->
<div class="rt-panel" id="rt-namedgroups" style="display:none">
<?= rt_toolbar('Named Groups Viewer',
    '<span class="rt-hint">Use (?&lt;name&gt;…) syntax in your pattern</span>',
    '<div id="ng-status"></div>'
    . '<button class="rt-btn rt-btn-primary" onclick="RT.runNamedGroups()">Extract</button>'
) ?>
<?= rt_regex_row('ng-pat', 'ng-flags', 'RT.runNamedGroups()') ?>
<div class="rt-editors">
    <div class="rt-editor-col" style="max-width:340px;border-right:1px solid var(--color-border)">
        <?= rt_pane_label('Test String', rt_paste_btn('ng-text')) ?>
        <?= rt_ta('ng-text', 'oninput="RT.runNamedGroups()" placeholder="Paste text to extract named groups from…"') ?>
    </div>
    <div class="rt-editor-col" style="overflow:auto">
        <?= rt_pane_label('Named Group Results') ?>
        <div id="ng-out" style="flex:1;overflow:auto;background:var(--color-background)">
            <div class="rt-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>Enter a pattern with named groups like (?&lt;year&gt;\d{4})</div>
        </div>
    </div>
</div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     8. GENERATOR
     ══════════════════════════════════════════════════════════════ -->
<div class="rt-panel" id="rt-generator" style="display:none">
<?= rt_toolbar('Regex Generator',
    '<span class="rt-hint">Click any template to open it in the Tester</span>'
) ?>
<div style="padding:8px 14px;border-bottom:1px solid var(--color-border);background:var(--color-surface);flex-shrink:0">
    <input type="text" id="gen-search" class="rt-regex-input" style="width:100%;font-size:13px;max-width:400px" placeholder="Search patterns (email, url, phone, date…)" oninput="RT.renderGenerator()">
</div>
<div id="gen-list" class="rt-pattern-grid" style="overflow-y:auto"></div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     9. COMMON PATTERNS LIBRARY
     ══════════════════════════════════════════════════════════════ -->
<div class="rt-panel" id="rt-patterns" style="display:none">
<?= rt_toolbar('Pattern Library',
    '<span class="rt-hint">24 production-ready patterns &mdash; click Test It to try any in the Tester</span>'
) ?>
<div id="pat-list" class="rt-pattern-grid" style="overflow-y:auto"></div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     10. CHEAT SHEET
     ══════════════════════════════════════════════════════════════ -->
<div class="rt-panel" id="rt-cheatsheet" style="display:none">
<?= rt_toolbar('Regex Cheat Sheet', '<span class="rt-hint">Quick reference for anchors, character classes, quantifiers, groups &amp; flags</span>') ?>
<div class="rt-cs-wrap">
    <div id="rt-cheatsheet-content"></div>
</div>
</div>

</div><!-- /.rt-content -->
</div><!-- /.rt-shell -->

<?php echo plugin_related_html($slug); ?>

<script><?php echo file_get_contents(__DIR__ . '/assets/regex-toolkit.js'); ?></script>

<?php
$content = ob_get_clean();
plugin_render('Regex Toolkit &mdash; 10 Free Online Regular Expression Tools', $content, [
    'description' => $_meta['description'] ?? 'Test, replace, extract, explain and generate regular expressions in your browser.',
    'og_title'    => $_meta['title']       ?? 'Regex Toolkit',
    'og_desc'     => $_meta['description'] ?? '',
    'canonical'   => $_meta['canonical']   ?? '',
]);
