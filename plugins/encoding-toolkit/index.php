<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';
require_once AWAN_ROOT . '/_core/Plugin.php';

$slug      = 'encoding-toolkit';
$_manifest = plugin_manifest($slug);
$_meta     = $_manifest['meta'] ?? [];
plugin_track('plugin_view', '/plugins/encoding-toolkit/', ['plugin_slug' => $slug]);

/* ── Helpers ─────────────────────────────────────────────────── */
function et_ta(string $id, string $extra = ''): string {
    return '<textarea id="' . $id . '" class="et-ta" spellcheck="false" autocomplete="off" ' . $extra . '></textarea>';
}

function et_in_pane(string $label, string $taId, string $accept = '.txt'): string {
    $pasteBtn = '<button type="button" class="et-copy-btn" onclick="ET.pasteInto(\'' . $taId . '\', this)">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>'
        . 'Paste</button>';
    $openBtn = '<button type="button" class="et-copy-btn" onclick="ET.openFile(\'' . $taId . '\',\'' . $accept . '\')">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>'
        . 'Open</button>';
    return '<div class="et-pane-label"><span>' . $label . '</span>'
         . '<div class="et-pane-label-right">' . $pasteBtn . $openBtn . '</div></div>';
}

function et_out_pane(string $label, string $metaId = '', string $taId = ''): string {
    $right = '';
    if ($metaId) $right .= '<span class="et-pane-meta" id="' . $metaId . '"></span>';
    if ($taId) {
        $right .= '<button type="button" class="et-copy-btn" onclick="ET.cpPane(\'' . $taId . '\', this)">'
               . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>'
               . 'Copy</button>';
    }
    return '<div class="et-pane-label"><span>' . $label . '</span>'
         . '<div class="et-pane-label-right">' . $right . '</div></div>';
}

/* SVG icons */
$icons = [
    'encode'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 10 4 15 9 20"/><path d="M20 4v7a4 4 0 0 1-4 4H4"/></svg>',
    'decode'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 10 20 15 15 20"/><path d="M4 4v7a4 4 0 0 0 4 4h12"/></svg>',
    'image'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
    'imgdec'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><polyline points="15 10 20 15 15 20"/></svg>',
    'urlsafe'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    'urlenc'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
    'qbuild'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
    'qparse'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
    'urlparse' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
    'extract'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
    'clean'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
    'split'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>',
    'ascii'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>',
    'unicode'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
    'binary'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
    'hex'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
    'charcode' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
    'hash'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>',
    'hmac'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="12" y1="9" x2="12" y2="15"/></svg>',
];

function et_nav(string $id, string $name, string $hint, string $icon): string {
    return '<button class="et-nav" data-tool="' . $id . '" onclick="ET.switchTool(\'' . $id . '\')">'
         . '<span class="et-nav-icon">' . $icon . '</span>'
         . '<span class="et-nav-text">'
         .   '<span class="et-nav-name">' . $name . '</span>'
         .   '<span class="et-nav-hint">' . $hint . '</span>'
         . '</span></button>';
}

$groups = [
    'Base64' => [
        ['b64enc',    'Encoder',          'Text &rarr; Base64',      'encode'],
        ['b64dec',    'Decoder',          'Base64 &rarr; Text',      'decode'],
        ['b64imgenc', 'Image Encoder',    'Image &rarr; Data URI',   'image'],
        ['b64imgdec', 'Image Decoder',    'Data URI &rarr; Image',   'imgdec'],
        ['b64urlenc', 'URL-safe Encoder', 'Text &rarr; Base64url',   'urlsafe'],
        ['b64urldec', 'URL-safe Decoder', 'Base64url &rarr; Text',   'decode'],
    ],
    'URL Encoding' => [
        ['urlenc',    'URL Encoder',      'Percent-encode',          'urlenc'],
        ['urldec',    'URL Decoder',      'Percent-decode',          'decode'],
        ['qbuild',    'Query Builder',    'Build query string',      'qbuild'],
        ['qparse',    'Query Parser',     'Parse query string',      'qparse'],
        ['urlparse',  'URL Parser',       'Break down a URL',        'urlparse'],
        ['urlextract','URL Extractor',    'Find URLs in text',       'extract'],
        ['urlclean',  'URL Cleaner',      'Strip tracking params',   'clean'],
        ['urlsplit',  'URL Splitter',     'Visual breakdown',        'split'],
    ],
    'Character Encoding' => [
        ['ascii',    'ASCII',             'Decimal code points',     'ascii'],
        ['unicode',  'Unicode',           'U+XXXX code points',      'unicode'],
        ['utf8',     'UTF-8',             'Hex byte sequence',       'hex'],
        ['utf16',    'UTF-16',            'Hex code units',          'binary'],
        ['binenc',   'Binary Encoder',    'Text &rarr; binary',      'binary'],
        ['bindec',   'Binary Decoder',    'Binary &rarr; text',      'decode'],
        ['octal',    'Octal',             'Octal escape sequences',  'hex'],
        ['decimal',  'Decimal',           'Decimal code points',     'ascii'],
        ['hex',      'Hex',               'Hex byte values',         'hex'],
        ['charcode', 'Char Code Table',   'All encodings at once',   'charcode'],
    ],
    'Hash &amp; Checksum' => [
        ['md5',    'MD5',    '128-bit digest',   'hash'],
        ['crc32',  'CRC32',  '32-bit checksum',  'hash'],
        ['sha1',   'SHA-1',  '160-bit digest',   'hash'],
        ['sha256', 'SHA-256','256-bit digest',   'hash'],
        ['sha384', 'SHA-384','384-bit digest',   'hash'],
        ['sha512', 'SHA-512','512-bit digest',   'hash'],
        ['hmac',   'HMAC',   'Keyed-hash MAC',   'hmac'],
    ],
];

function et_toolbar(string $title, string $left = '', string $right = ''): string {
    return '<div class="et-toolbar">'
         . '<div class="et-toolbar-left"><span class="et-tool-title">' . $title . '</span>' . $left . '</div>'
         . '<div class="et-toolbar-right">' . $right . '</div>'
         . '</div>';
}

$copy_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
$dl_svg   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';

ob_start();
?>
<style><?php echo file_get_contents(__DIR__ . '/assets/encoding-toolkit.css'); ?></style>

<div class="page-header" style="margin-bottom:0;border-bottom:1px solid var(--color-border);padding-bottom:14px">
    <div class="page-header-left">
        <div class="page-title">Encoding Toolkit</div>
        <div class="page-subtitle">31 client-side utilities &mdash; Base64, URL encoding, ASCII, Unicode, UTF-8/16, Binary, Hex, Hash &amp; more</div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-ghost btn-sm" onclick="ET.loadSample()">Load Sample</button>
        <button class="btn btn-ghost btn-sm" onclick="ET.clearAll()">Clear</button>
    </div>
</div>

<div class="et-shell">

<!-- ══ Sidebar ════════════════════════════════════════════════ -->
<nav class="et-sidebar">
<?php foreach ($groups as $label => $tools): ?>
<div class="et-nav-group"><?= $label ?></div>
<?php foreach ($tools as [$id, $name, $hint, $iconKey]): ?>
<?= et_nav($id, $name, $hint, $icons[$iconKey] ?? '') ?>
<?php endforeach; ?>
<?php endforeach; ?>
</nav>

<!-- ══ Tool panels ════════════════════════════════════════════ -->
<div class="et-content">

<!-- ══ BASE64 ═══════════════════════════════════════════════════════════════ -->

<!-- 1. Base64 Encoder -->
<div class="et-panel" id="et-b64enc">
<?= et_toolbar('Base64 Encoder', '<span class="et-hint">UTF-8 safe &mdash; handles emoji &amp; non-ASCII</span>',
    '<span class="et-pane-meta" id="b64e-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'b64e-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn" onclick="ET.dl(\'b64e-out\',\'encoded.txt\',\'text/plain\')">' . $dl_svg . 'Download</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runB64Enc()">Encode</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Plain Text Input', 'b64e-in') ?>
        <?= et_ta('b64e-in', 'oninput="ET.runB64Enc()" placeholder="Type or paste text to encode…"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('Base64 Output', '', 'b64e-out') ?>
        <?= et_ta('b64e-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 2. Base64 Decoder -->
<div class="et-panel" id="et-b64dec" style="display:none">
<?= et_toolbar('Base64 Decoder', '',
    '<div id="b64d-st"></div>'
    . '<button class="et-btn" onclick="ET.cpPane(\'b64d-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runB64Dec()">Decode</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Base64 Input', 'b64d-in') ?>
        <?= et_ta('b64d-in', 'oninput="ET.runB64Dec()" placeholder="Paste Base64 string to decode…"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('Decoded Text', '', 'b64d-out') ?>
        <?= et_ta('b64d-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 3. Base64 Image Encoder -->
<div class="et-panel" id="et-b64imgenc" style="display:none">
<?= et_toolbar('Base64 Image Encoder', '<span class="et-hint">Converts any image to an embeddable data URI</span>',
    '<span class="et-pane-meta" id="b64ie-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'b64ie-out\',this)">' . $copy_svg . 'Copy Data URI</button>'
    . '<button class="et-btn" onclick="ET.dl(\'b64ie-out\',\'image.txt\',\'text/plain\')">' . $dl_svg . 'Download</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col" style="max-width:340px;border-right:1px solid var(--color-border)">
        <div id="b64ie-zone" class="et-drop-zone">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            <strong>Click or drag &amp; drop an image</strong>
            <span style="font-size:12px">PNG, JPG, GIF, WebP, SVG&hellip;</span>
        </div>
    </div>
    <div class="et-editor-col">
        <?= et_out_pane('Data URI Output', '', 'b64ie-out') ?>
        <?= et_ta('b64ie-out', 'readonly placeholder="data:image/png;base64,…"') ?>
    </div>
</div>
</div>

<!-- 4. Base64 Image Decoder -->
<div class="et-panel" id="et-b64imgdec" style="display:none">
<?= et_toolbar('Base64 Image Decoder', '<span class="et-hint">Paste a data URI or raw Base64 image string</span>',
    '<div id="b64id-st"></div>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runB64ImgDec()">Decode</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="height:130px;display:flex;flex-direction:column;border-bottom:1px solid var(--color-border);flex-shrink:0">
        <?= et_in_pane('Data URI or Base64 Input', 'b64id-in') ?>
        <?= et_ta('b64id-in', 'oninput="ET.runB64ImgDec()" placeholder="data:image/png;base64,iVBORw0K…"') ?>
    </div>
    <div id="b64id-preview" style="flex:1;overflow:auto;padding:20px;display:flex;flex-direction:column;align-items:center;justify-content:center;background:var(--color-background)">
        <span style="color:var(--color-text-muted);font-size:13px">Paste a data URI or Base64 image string above.</span>
    </div>
</div>
</div>

<!-- 5. URL-safe Base64 Encoder -->
<div class="et-panel" id="et-b64urlenc" style="display:none">
<?= et_toolbar('URL-safe Base64 Encoder', '<span class="et-hint">Replaces + and / with - and _, removes padding =</span>',
    '<span class="et-pane-meta" id="b64ue-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'b64ue-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runB64UrlEnc()">Encode</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Plain Text Input', 'b64ue-in') ?>
        <?= et_ta('b64ue-in', 'oninput="ET.runB64UrlEnc()" placeholder="Type or paste text to encode as Base64url…"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('Base64url Output', '', 'b64ue-out') ?>
        <?= et_ta('b64ue-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 6. URL-safe Base64 Decoder -->
<div class="et-panel" id="et-b64urldec" style="display:none">
<?= et_toolbar('URL-safe Base64 Decoder', '<span class="et-hint">Accepts both padded and unpadded Base64url</span>',
    '<div id="b64ud-st"></div>'
    . '<button class="et-btn" onclick="ET.cpPane(\'b64ud-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runB64UrlDec()">Decode</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Base64url Input', 'b64ud-in') ?>
        <?= et_ta('b64ud-in', 'oninput="ET.runB64UrlDec()" placeholder="Paste Base64url string (- _ instead of + /)…"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('Decoded Text', '', 'b64ud-out') ?>
        <?= et_ta('b64ud-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- ══ URL ENCODING ══════════════════════════════════════════════════════════ -->

<!-- 7. URL Encoder -->
<div class="et-panel" id="et-urlenc" style="display:none">
<?= et_toolbar('URL Encoder', '',
    '<div id="ue-st"></div>'
    . '<label class="et-select-wrap">Mode <select id="ue-mode" onchange="ET.runUrlEnc()"><option value="component">encodeURIComponent</option><option value="full">encodeURI (full URL)</option></select></label>'
    . '<span class="et-pane-meta" id="ue-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'ue-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runUrlEnc()">Encode</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Plain Text Input', 'ue-in') ?>
        <?= et_ta('ue-in', 'oninput="ET.runUrlEnc()" placeholder="Paste text to percent-encode…"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('Encoded Output', '', 'ue-out') ?>
        <?= et_ta('ue-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 8. URL Decoder -->
<div class="et-panel" id="et-urldec" style="display:none">
<?= et_toolbar('URL Decoder', '<span class="et-hint">Decodes both %XX and + (space) encoding</span>',
    '<div id="ud-st"></div>'
    . '<button class="et-btn" onclick="ET.cpPane(\'ud-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runUrlDec()">Decode</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Encoded Input', 'ud-in') ?>
        <?= et_ta('ud-in', 'oninput="ET.runUrlDec()" placeholder="Paste percent-encoded text to decode…"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('Decoded Output', '', 'ud-out') ?>
        <?= et_ta('ud-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 9. URL Query Builder -->
<div class="et-panel" id="et-qbuild" style="display:none">
<?= et_toolbar('URL Query Builder', '<span class="et-hint">Build a query string from key-value pairs</span>',
    '<span class="et-pane-meta" id="qb-meta"></span>'
    . '<button class="et-btn" onclick="ET._qbAdd()">+ Add Param</button>'
    . '<button class="et-btn" onclick="ET.cpPane(\'qb-out\',this)">' . $copy_svg . 'Copy</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="padding:8px 14px;background:var(--color-surface);border-bottom:1px solid var(--color-border);flex-shrink:0;display:flex;align-items:center;gap:8px">
        <label style="font-size:12px;color:var(--color-text-muted);white-space:nowrap">Base URL (optional)</label>
        <input type="text" id="qb-base" class="et-qb-input" placeholder="https://example.com/search" oninput="ET.buildQuery()" style="flex:1">
    </div>
    <div id="qb-rows" class="et-qb-rows" style="overflow-y:auto"></div>
    <div style="border-top:1px solid var(--color-border);flex-shrink:0">
        <?= et_out_pane('Query String Output', '', 'qb-out') ?>
        <?= et_ta('qb-out', 'readonly style="min-height:60px;max-height:90px"') ?>
    </div>
</div>
</div>

<!-- 10. URL Query Parser -->
<div class="et-panel" id="et-qparse" style="display:none">
<?= et_toolbar('URL Query Parser', '<span class="et-hint">Paste a full URL or just the query string</span>',
    '<span class="et-pane-meta" id="qp-meta"></span>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runQParse()">Parse</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="height:80px;display:flex;flex-direction:column;border-bottom:1px solid var(--color-border);flex-shrink:0">
        <?= et_in_pane('Query String or URL', 'qp-in') ?>
        <?= et_ta('qp-in', 'oninput="ET.runQParse()" placeholder="name=John+Doe&amp;lang=en&amp;page=2"') ?>
    </div>
    <div id="qp-out" class="et-result-list" style="overflow-y:auto;flex:1">
        <div class="et-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Paste a query string above</div>
    </div>
</div>
</div>

<!-- 11. URL Parser -->
<div class="et-panel" id="et-urlparse" style="display:none">
<?= et_toolbar('URL Parser', '<span class="et-hint">Breaks a URL into all its components</span>',
    '<button class="et-btn et-btn-primary" onclick="ET.runUrlParse()">Parse</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="height:70px;display:flex;flex-direction:column;border-bottom:1px solid var(--color-border);flex-shrink:0">
        <?= et_in_pane('URL Input', 'up-in') ?>
        <?= et_ta('up-in', 'oninput="ET.runUrlParse()" placeholder="https://example.com/path?key=value#section"') ?>
    </div>
    <div id="up-out" style="flex:1;overflow:auto;background:var(--color-background)">
        <div class="et-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>Paste a URL above to parse it</div>
    </div>
</div>
</div>

<!-- 12. URL Extractor -->
<div class="et-panel" id="et-urlextract" style="display:none">
<?= et_toolbar('URL Extractor', '<span class="et-hint">Finds all http/https URLs in a block of text</span>',
    '<span class="et-pane-meta" id="uex-meta"></span>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runUrlExtract()">Extract</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="height:130px;display:flex;flex-direction:column;border-bottom:1px solid var(--color-border);flex-shrink:0">
        <?= et_in_pane('Text Input', 'uex-in', '.txt,.html,.md') ?>
        <?= et_ta('uex-in', 'oninput="ET.runUrlExtract()" placeholder="Paste any text, HTML, markdown — URLs will be extracted…"') ?>
    </div>
    <div id="uex-out" class="et-result-list" style="overflow-y:auto;flex:1">
        <div class="et-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>Paste text above to extract URLs</div>
    </div>
</div>
</div>

<!-- 13. URL Cleaner -->
<div class="et-panel" id="et-urlclean" style="display:none">
<?= et_toolbar('URL Cleaner', '<span class="et-hint">Strips utm_*, fbclid, gclid and other tracking parameters</span>',
    '<span class="et-pane-meta" id="ucl-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'ucl-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runUrlClean()">Clean</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="padding:8px 14px;background:var(--color-surface);border-bottom:1px solid var(--color-border);flex-shrink:0;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <label style="font-size:12px;color:var(--color-text-muted);white-space:nowrap">Also remove (comma-separated)</label>
        <input type="text" id="ucl-custom" class="et-qb-input" placeholder="ref, source, affiliate" oninput="ET.runUrlClean()" style="flex:1;min-width:180px">
    </div>
    <div class="et-editors">
        <div class="et-editor-col">
            <?= et_in_pane('Dirty URL', 'ucl-in') ?>
            <?= et_ta('ucl-in', 'oninput="ET.runUrlClean()" placeholder="https://example.com/page?utm_source=google&fbclid=abc123"') ?>
        </div>
        <div class="et-editor-divider"></div>
        <div class="et-editor-col">
            <?= et_out_pane('Clean URL', '', 'ucl-out') ?>
            <?= et_ta('ucl-out', 'readonly') ?>
        </div>
    </div>
</div>
</div>

<!-- 14. URL Splitter -->
<div class="et-panel" id="et-urlsplit" style="display:none">
<?= et_toolbar('URL Splitter', '<span class="et-hint">Visual colour-coded breakdown of every URL segment</span>',
    '<button class="et-btn et-btn-primary" onclick="ET.runUrlSplit()">Split</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="height:70px;display:flex;flex-direction:column;border-bottom:1px solid var(--color-border);flex-shrink:0">
        <?= et_in_pane('URL Input', 'usp-in') ?>
        <?= et_ta('usp-in', 'oninput="ET.runUrlSplit()" placeholder="https://api.example.com/v2/users?page=1&limit=20#top"') ?>
    </div>
    <div id="usp-out" style="flex:1;overflow:auto;background:var(--color-background)">
        <div class="et-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>Paste a URL to split</div>
    </div>
</div>
</div>

<!-- ══ CHARACTER ENCODING ═══════════════════════════════════════════════════ -->

<!-- 15. ASCII Converter -->
<div class="et-panel" id="et-ascii" style="display:none">
<?= et_toolbar('ASCII Converter', '',
    '<label class="et-select-wrap">Mode <select id="asc-mode" onchange="ET.runAscii()"><option value="encode">Text &rarr; Codes</option><option value="decode">Codes &rarr; Text</option></select></label>'
    . '<span class="et-pane-meta" id="asc-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'asc-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runAscii()">Convert</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Input', 'asc-in') ?>
        <?= et_ta('asc-in', 'oninput="ET.runAscii()" placeholder="Hello  →  72 101 108 108 111"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('Output', '', 'asc-out') ?>
        <?= et_ta('asc-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 16. Unicode Converter -->
<div class="et-panel" id="et-unicode" style="display:none">
<?= et_toolbar('Unicode Converter', '',
    '<label class="et-select-wrap">Mode <select id="uni-mode" onchange="ET.runUnicode()"><option value="encode">Text &rarr; U+XXXX</option><option value="decode">U+XXXX &rarr; Text</option></select></label>'
    . '<span class="et-pane-meta" id="uni-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'uni-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runUnicode()">Convert</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Input', 'uni-in') ?>
        <?= et_ta('uni-in', 'oninput="ET.runUnicode()" placeholder="Hi ✓  →  U+0048 U+0069 U+0020 U+2713"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('Output', '', 'uni-out') ?>
        <?= et_ta('uni-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 17. UTF-8 Converter -->
<div class="et-panel" id="et-utf8" style="display:none">
<?= et_toolbar('UTF-8 Converter', '<span class="et-hint">Text &rarr; UTF-8 byte sequence in hex</span>',
    '<label class="et-select-wrap">Separator <select id="u8-sep" onchange="ET.runUtf8()"><option value=" ">Space</option><option value="">None</option><option value="\x5c\x78">\\x</option><option value="%">%</option></select></label>'
    . '<span class="et-pane-meta" id="u8-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'u8-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runUtf8()">Convert</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Text Input', 'u8-in') ?>
        <?= et_ta('u8-in', 'oninput="ET.runUtf8()" placeholder="Hello →  48 65 6C 6C 6F"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('UTF-8 Bytes (Hex)', '', 'u8-out') ?>
        <?= et_ta('u8-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 18. UTF-16 Converter -->
<div class="et-panel" id="et-utf16" style="display:none">
<?= et_toolbar('UTF-16 Converter', '<span class="et-hint">Text &rarr; UTF-16 code units in hex</span>',
    '<label class="et-select-wrap">Separator <select id="u16-sep" onchange="ET.runUtf16()"><option value=" ">Space</option><option value="">None</option><option value="\x5c\x75">\\u</option></select></label>'
    . '<span class="et-pane-meta" id="u16-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'u16-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runUtf16()">Convert</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Text Input', 'u16-in') ?>
        <?= et_ta('u16-in', 'oninput="ET.runUtf16()" placeholder="Hi →  0048 0069"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('UTF-16 Code Units (Hex)', '', 'u16-out') ?>
        <?= et_ta('u16-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 19. Binary Encoder -->
<div class="et-panel" id="et-binenc" style="display:none">
<?= et_toolbar('Binary Encoder', '<span class="et-hint">Converts text to 8-bit binary groups via UTF-8</span>',
    '<label class="et-select-wrap">Separator <select id="bine-sep" onchange="ET.runBinEnc()"><option value=" ">Space</option><option value="">None</option><option value="\n">Newline</option></select></label>'
    . '<span class="et-pane-meta" id="bine-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'bine-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runBinEnc()">Encode</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Text Input', 'bine-in') ?>
        <?= et_ta('bine-in', 'oninput="ET.runBinEnc()" placeholder="Hi →  01001000 01101001"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('Binary Output', '', 'bine-out') ?>
        <?= et_ta('bine-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 20. Binary Decoder -->
<div class="et-panel" id="et-bindec" style="display:none">
<?= et_toolbar('Binary Decoder', '<span class="et-hint">Converts space-separated 8-bit groups back to text</span>',
    '<div id="bind-st"></div>'
    . '<button class="et-btn" onclick="ET.cpPane(\'bind-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runBinDec()">Decode</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Binary Input', 'bind-in') ?>
        <?= et_ta('bind-in', 'oninput="ET.runBinDec()" placeholder="01001000 01100101 01101100 01101100 01101111"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('Decoded Text', '', 'bind-out') ?>
        <?= et_ta('bind-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 21. Octal Converter -->
<div class="et-panel" id="et-octal" style="display:none">
<?= et_toolbar('Octal Converter', '<span class="et-hint">Converts between text and octal escape sequences</span>',
    '<label class="et-select-wrap">Mode <select id="oct-mode" onchange="ET.runOctal()"><option value="encode">Text &rarr; Octal</option><option value="decode">Octal &rarr; Text</option></select></label>'
    . '<span class="et-pane-meta" id="oct-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'oct-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runOctal()">Convert</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Input', 'oct-in') ?>
        <?= et_ta('oct-in', 'oninput="ET.runOctal()" placeholder="Hi →  \\110\\151"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('Output', '', 'oct-out') ?>
        <?= et_ta('oct-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 22. Decimal Converter -->
<div class="et-panel" id="et-decimal" style="display:none">
<?= et_toolbar('Decimal Converter', '<span class="et-hint">Converts between characters and their decimal code points</span>',
    '<label class="et-select-wrap">Mode <select id="deci-mode" onchange="ET.runDecimal()"><option value="encode">Text &rarr; Decimal</option><option value="decode">Decimal &rarr; Text</option></select></label>'
    . '<span class="et-pane-meta" id="deci-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'deci-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runDecimal()">Convert</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Input', 'deci-in') ?>
        <?= et_ta('deci-in', 'oninput="ET.runDecimal()" placeholder="Hi →  72 105"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('Output', '', 'deci-out') ?>
        <?= et_ta('deci-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 23. Hex Converter -->
<div class="et-panel" id="et-hex" style="display:none">
<?= et_toolbar('Hex Converter', '<span class="et-hint">Converts between text and hex byte values</span>',
    '<label class="et-select-wrap">Mode <select id="hex-mode" onchange="ET.runHex()"><option value="encode">Text &rarr; Hex</option><option value="decode">Hex &rarr; Text</option></select></label>'
    . '<span class="et-pane-meta" id="hex-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'hex-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="et-btn et-btn-primary" onclick="ET.runHex()">Convert</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Input', 'hex-in') ?>
        <?= et_ta('hex-in', 'oninput="ET.runHex()" placeholder="Hi →  48 69   or   48 65 6C 6C 6F"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('Output', '', 'hex-out') ?>
        <?= et_ta('hex-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 24. Character Code Converter -->
<div class="et-panel" id="et-charcode" style="display:none">
<?= et_toolbar('Character Code Table', '<span class="et-hint">See all encodings for every character at once</span>',
    '<button class="et-btn et-btn-primary" onclick="ET.runCharCode()">Show Table</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="height:70px;display:flex;flex-direction:column;border-bottom:1px solid var(--color-border);flex-shrink:0">
        <?= et_in_pane('Text Input (up to 64 chars)', 'cc-in') ?>
        <?= et_ta('cc-in', 'oninput="ET.runCharCode()" placeholder="Type characters to see their decimal, hex, binary, Unicode…"') ?>
    </div>
    <div id="cc-out" style="flex:1;overflow:auto;background:var(--color-background);display:flex;flex-direction:column">
        <div class="et-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>Type or paste characters above</div>
    </div>
</div>
</div>

<!-- ══ HASH & CHECKSUM ═══════════════════════════════════════════════════════ -->

<!-- 25. MD5 -->
<div class="et-panel" id="et-md5" style="display:none">
<?= et_toolbar('MD5',
    '<span class="et-hint">128-bit digest &mdash; fast checksum, not for security</span>',
    '<span class="et-pane-meta" id="md5-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'md5-out\',this)">' . $copy_svg . 'Copy</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Input Text', 'md5-in') ?>
        <?= et_ta('md5-in', 'oninput="ET.runMd5()" placeholder="Type or paste text to hash…"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('MD5 Hash', '', 'md5-out') ?>
        <?= et_ta('md5-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 26. CRC32 -->
<div class="et-panel" id="et-crc32" style="display:none">
<?= et_toolbar('CRC32',
    '<span class="et-hint">32-bit cyclic redundancy check &mdash; hex, decimal &amp; binary output</span>',
    '<span class="et-pane-meta" id="crc32-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'crc32-out\',this)">' . $copy_svg . 'Copy</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Input Text', 'crc32-in') ?>
        <?= et_ta('crc32-in', 'oninput="ET.runCrc32()" placeholder="Type or paste text to checksum…"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('CRC32 Output', '', 'crc32-out') ?>
        <?= et_ta('crc32-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 27. SHA-1 -->
<div class="et-panel" id="et-sha1" style="display:none">
<?= et_toolbar('SHA-1',
    '<span class="et-hint">160-bit digest via Web Crypto API &mdash; deprecated for security use</span>',
    '<span class="et-pane-meta" id="sha1-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'sha1-out\',this)">' . $copy_svg . 'Copy</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Input Text', 'sha1-in') ?>
        <?= et_ta('sha1-in', 'oninput="ET.runSha1()" placeholder="Type or paste text to hash…"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('SHA-1 Hash', '', 'sha1-out') ?>
        <?= et_ta('sha1-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 28. SHA-256 -->
<div class="et-panel" id="et-sha256" style="display:none">
<?= et_toolbar('SHA-256',
    '<span class="et-hint">256-bit digest &mdash; most common SHA-2 variant, excellent for file integrity</span>',
    '<span class="et-pane-meta" id="sha256-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'sha256-out\',this)">' . $copy_svg . 'Copy</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Input Text', 'sha256-in') ?>
        <?= et_ta('sha256-in', 'oninput="ET.runSha256()" placeholder="Type or paste text to hash…"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('SHA-256 Hash', '', 'sha256-out') ?>
        <?= et_ta('sha256-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 29. SHA-384 -->
<div class="et-panel" id="et-sha384" style="display:none">
<?= et_toolbar('SHA-384',
    '<span class="et-hint">384-bit digest &mdash; truncated SHA-512 with stronger security margin</span>',
    '<span class="et-pane-meta" id="sha384-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'sha384-out\',this)">' . $copy_svg . 'Copy</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Input Text', 'sha384-in') ?>
        <?= et_ta('sha384-in', 'oninput="ET.runSha384()" placeholder="Type or paste text to hash…"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('SHA-384 Hash', '', 'sha384-out') ?>
        <?= et_ta('sha384-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 30. SHA-512 -->
<div class="et-panel" id="et-sha512" style="display:none">
<?= et_toolbar('SHA-512',
    '<span class="et-hint">512-bit digest &mdash; highest SHA-2 strength, recommended for modern use</span>',
    '<span class="et-pane-meta" id="sha512-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'sha512-out\',this)">' . $copy_svg . 'Copy</button>'
) ?>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Input Text', 'sha512-in') ?>
        <?= et_ta('sha512-in', 'oninput="ET.runSha512()" placeholder="Type or paste text to hash…"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('SHA-512 Hash', '', 'sha512-out') ?>
        <?= et_ta('sha512-out', 'readonly') ?>
    </div>
</div>
</div>

<!-- 31. HMAC -->
<div class="et-panel" id="et-hmac" style="display:none">
<?= et_toolbar('HMAC',
    '<span class="et-hint">Keyed-hash message authentication code via Web Crypto</span>',
    '<span class="et-pane-meta" id="hmac-meta"></span>'
    . '<button class="et-btn" onclick="ET.cpPane(\'hmac-out\',this)">' . $copy_svg . 'Copy</button>'
) ?>
<div style="padding:7px 10px;border-bottom:1px solid var(--color-border);background:var(--color-surface);display:flex;align-items:center;gap:12px;flex-shrink:0;flex-wrap:wrap">
    <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--color-text-muted);white-space:nowrap">
        Secret key:
        <input type="text" id="hmac-key" style="font-family:monospace;font-size:13px;padding:4px 8px;border:1px solid var(--color-border);border-radius:5px;background:var(--color-background);color:var(--color-text);min-width:160px;outline:none" placeholder="secret key" oninput="ET.runHmac()">
    </label>
    <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--color-text-muted);white-space:nowrap">
        Algorithm:
        <select id="hmac-algo" style="font-size:12px;padding:4px 8px;border:1px solid var(--color-border);border-radius:5px;background:var(--color-surface);color:var(--color-text);cursor:pointer" onchange="ET.runHmac()">
            <option value="SHA-256" selected>SHA-256</option>
            <option value="SHA-384">SHA-384</option>
            <option value="SHA-512">SHA-512</option>
            <option value="SHA-1">SHA-1</option>
        </select>
    </label>
</div>
<div class="et-editors">
    <div class="et-editor-col">
        <?= et_in_pane('Message Text', 'hmac-in') ?>
        <?= et_ta('hmac-in', 'oninput="ET.runHmac()" placeholder="Type or paste the message to authenticate…"') ?>
    </div>
    <div class="et-editor-divider"></div>
    <div class="et-editor-col">
        <?= et_out_pane('HMAC Output', '', 'hmac-out') ?>
        <?= et_ta('hmac-out', 'readonly') ?>
    </div>
</div>
</div>

</div><!-- /.et-content -->
</div><!-- /.et-shell -->

<?php echo plugin_related_html($slug); ?>

<script><?php echo file_get_contents(__DIR__ . '/assets/encoding-toolkit.js'); ?></script>

<?php
$content = ob_get_clean();
plugin_render('Encoding Toolkit &mdash; 31 Free Online Encoding &amp; Decoding Utilities', $content, [
    'description' => $_meta['description'] ?? 'Encode, decode and hash with 31 free browser-based tools — Base64, URL, ASCII, Unicode, UTF-8/16, Binary, Hex, MD5, SHA-256, HMAC &amp; more.',
    'og_title'    => $_meta['title']       ?? 'Encoding Toolkit',
    'og_desc'     => $_meta['description'] ?? '',
    'canonical'   => $_meta['canonical']   ?? '',
]);
