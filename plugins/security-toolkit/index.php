<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';
require_once AWAN_ROOT . '/_core/Plugin.php';

$slug      = 'security-toolkit';
$_manifest = plugin_manifest($slug);
$_meta     = $_manifest['meta'] ?? [];
plugin_track('plugin_view', '/plugins/security-toolkit/', ['plugin_slug' => $slug]);

/* ── SVG icons ────────────────────────────────────────────── */
$icons = [
    'hash'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>',
    'shield'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    'key'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>',
    'lock'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    'check'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
    'token'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    'decode'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 10 20 15 15 20"/><path d="M4 4v7a4 4 0 0 0 4 4h12"/></svg>',
    'encode'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 10 4 15 9 20"/><path d="M20 4v7a4 4 0 0 1-4 4H4"/></svg>',
    'inspect'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
    'clock'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'file'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>',
    'verify'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
    'hmac'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="12" y1="9" x2="12" y2="15"/></svg>',
    'strength'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    'bcrypt'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>',
];

/* ── Nav builder ──────────────────────────────────────────── */
function st_nav(string $id, string $name, string $hint, string $icon): string {
    return '<button class="st-nav" data-tool="' . $id . '" onclick="ST.switchTool(\'' . $id . '\')">'
         . '<span class="st-nav-icon">' . $icon . '</span>'
         . '<span class="st-nav-text">'
         .   '<span class="st-nav-name">' . $name . '</span>'
         .   '<span class="st-nav-hint">' . $hint . '</span>'
         . '</span></button>';
}

function st_toolbar(string $title, string $hint = '', string $right = ''): string {
    $h = $hint ? '<span class="st-hint">' . $hint . '</span>' : '';
    return '<div class="st-toolbar">'
         . '<div class="st-toolbar-left"><span class="st-tool-title">' . $title . '</span>' . $h . '</div>'
         . '<div class="st-toolbar-right">' . $right . '</div>'
         . '</div>';
}

$copy_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';

$groups = [
    'Hash Generators' => [
        ['md5',       'MD5',          '128-bit digest',    'hash'],
        ['sha1',      'SHA-1',        '160-bit digest',    'hash'],
        ['sha224',    'SHA-224',      '224-bit digest',    'hash'],
        ['sha256',    'SHA-256',      '256-bit digest',    'hash'],
        ['sha384',    'SHA-384',      '384-bit digest',    'hash'],
        ['sha512',    'SHA-512',      '512-bit digest',    'hash'],
        ['crc32',     'CRC32',        '32-bit checksum',   'hash'],
        ['ripemd160', 'RIPEMD-160',   '160-bit digest',    'hash'],
        ['whirlpool', 'Whirlpool',    '512-bit digest',    'hash'],
    ],
    'Password Tools' => [
        ['pwgen',     'Generator',    'Random password',   'key'],
        ['pwhash',    'Hash Generator','Password → hash',  'lock'],
        ['pwcheck',   'Strength Checker','Analyse strength','strength'],
        ['bcrypt',    'Bcrypt Generator','Bcrypt hash',     'bcrypt'],
    ],
    'JWT Tools' => [
        ['jwtdec',    'JWT Decoder',  'Decode a token',    'decode'],
        ['jwtenc',    'JWT Encoder',  'Build a token',     'encode'],
        ['jwtval',    'JWT Validator','Validate structure', 'check'],
        ['jwtinsp',   'JWT Inspector','All fields at once', 'inspect'],
        ['jwtexp',    'Expiry Checker','Check exp claim',  'clock'],
    ],
    'Security Utilities' => [
        ['hmac',      'HMAC Generator','Keyed-hash MAC',   'hmac'],
        ['filehash',  'File Hash',    'Hash a file',       'file'],
        ['hashverify','Hash Verifier','Compare hashes',    'verify'],
        ['tokengen',  'Token Generator','UUIDs, API keys, nonces','key'],
    ],
];

ob_start();
?>
<style><?php echo file_get_contents(__DIR__ . '/assets/security-toolkit.css'); ?></style>

<div class="page-header" style="margin-bottom:0;border-bottom:1px solid var(--color-border);padding-bottom:14px">
    <div class="page-header-left">
        <div class="page-title">Security Toolkit</div>
        <div class="page-subtitle">22 client-side utilities &mdash; hash generators, password tools, JWT encoder/decoder, HMAC &amp; token generator</div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-ghost btn-sm" onclick="ST.clearCurrent()">Clear</button>
    </div>
</div>

<div class="st-shell">

<!-- ══ Sidebar ════════════════════════════════════════════════ -->
<nav class="st-sidebar">
<?php foreach ($groups as $label => $tools): ?>
<div class="st-nav-group"><?= $label ?></div>
<?php foreach ($tools as [$id, $name, $hint, $iconKey]): ?>
<?= st_nav($id, $name, $hint, $icons[$iconKey] ?? '') ?>
<?php endforeach; ?>
<?php endforeach; ?>
</nav>

<!-- ══ Content panels ════════════════════════════════════════ -->
<div class="st-content">

<!-- ══ HASH GENERATORS ══════════════════════════════════════════════════════ -->

<!-- MD5 -->
<div class="st-panel" id="st-md5">
<?= st_toolbar('MD5 Generator', '128-bit (32 hex chars) — fast but not collision-resistant',
    '<span id="md5-st"></span>'
    . '<button class="st-btn" onclick="ST.cp(\'md5-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runHash(\'md5\')">Generate</button>'
) ?>
<div class="st-single-panel">
    <div>
        <div class="st-field-label">Input Text</div>
        <div class="st-input-row"><input type="text" id="md5-in" placeholder="Type or paste text to hash…" oninput="ST.runHash(\'md5\')"></div>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">MD5 Hash <div><button class="st-copy-btn" onclick="ST.cp(\'md5-out\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val placeholder" id="md5-out">Result will appear here…</div>
    </div>
    <div id="md5-len" style="font-size:11px;color:var(--color-text-muted)"></div>
</div>
</div>

<!-- SHA-1 -->
<div class="st-panel" id="st-sha1" style="display:none">
<?= st_toolbar('SHA-1 Generator', '160-bit (40 hex chars) — deprecated for security use',
    '<span id="sha1-st"></span>'
    . '<button class="st-btn" onclick="ST.cp(\'sha1-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runHash(\'sha1\')">Generate</button>'
) ?>
<div class="st-single-panel">
    <div>
        <div class="st-field-label">Input Text</div>
        <div class="st-input-row"><input type="text" id="sha1-in" placeholder="Type or paste text to hash…" oninput="ST.runHash(\'sha1\')"></div>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">SHA-1 Hash <div><button class="st-copy-btn" onclick="ST.cp(\'sha1-out\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val placeholder" id="sha1-out">Result will appear here…</div>
    </div>
</div>
</div>

<!-- SHA-224 -->
<div class="st-panel" id="st-sha224" style="display:none">
<?= st_toolbar('SHA-224 Generator', '224-bit (56 hex chars) — truncated variant of SHA-256',
    '<span id="sha224-st"></span>'
    . '<button class="st-btn" onclick="ST.cp(\'sha224-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runHash(\'sha224\')">Generate</button>'
) ?>
<div class="st-single-panel">
    <div>
        <div class="st-field-label">Input Text</div>
        <div class="st-input-row"><input type="text" id="sha224-in" placeholder="Type or paste text to hash…" oninput="ST.runHash(\'sha224\')"></div>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">SHA-224 Hash <div><button class="st-copy-btn" onclick="ST.cp(\'sha224-out\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val placeholder" id="sha224-out">Result will appear here…</div>
    </div>
</div>
</div>

<!-- SHA-256 -->
<div class="st-panel" id="st-sha256" style="display:none">
<?= st_toolbar('SHA-256 Generator', '256-bit (64 hex chars) — widely used cryptographic standard',
    '<span id="sha256-st"></span>'
    . '<button class="st-btn" onclick="ST.cp(\'sha256-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runHash(\'sha256\')">Generate</button>'
) ?>
<div class="st-single-panel">
    <div>
        <div class="st-field-label">Input Text</div>
        <div class="st-input-row"><input type="text" id="sha256-in" placeholder="Type or paste text to hash…" oninput="ST.runHash(\'sha256\')"></div>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">SHA-256 Hash <div><button class="st-copy-btn" onclick="ST.cp(\'sha256-out\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val placeholder" id="sha256-out">Result will appear here…</div>
    </div>
</div>
</div>

<!-- SHA-384 -->
<div class="st-panel" id="st-sha384" style="display:none">
<?= st_toolbar('SHA-384 Generator', '384-bit (96 hex chars) — part of SHA-2 family',
    '<span id="sha384-st"></span>'
    . '<button class="st-btn" onclick="ST.cp(\'sha384-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runHash(\'sha384\')">Generate</button>'
) ?>
<div class="st-single-panel">
    <div>
        <div class="st-field-label">Input Text</div>
        <div class="st-input-row"><input type="text" id="sha384-in" placeholder="Type or paste text to hash…" oninput="ST.runHash(\'sha384\')"></div>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">SHA-384 Hash <div><button class="st-copy-btn" onclick="ST.cp(\'sha384-out\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val placeholder" id="sha384-out">Result will appear here…</div>
    </div>
</div>
</div>

<!-- SHA-512 -->
<div class="st-panel" id="st-sha512" style="display:none">
<?= st_toolbar('SHA-512 Generator', '512-bit (128 hex chars) — strongest SHA-2 variant',
    '<span id="sha512-st"></span>'
    . '<button class="st-btn" onclick="ST.cp(\'sha512-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runHash(\'sha512\')">Generate</button>'
) ?>
<div class="st-single-panel">
    <div>
        <div class="st-field-label">Input Text</div>
        <div class="st-input-row"><input type="text" id="sha512-in" placeholder="Type or paste text to hash…" oninput="ST.runHash(\'sha512\')"></div>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">SHA-512 Hash <div><button class="st-copy-btn" onclick="ST.cp(\'sha512-out\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val placeholder" id="sha512-out">Result will appear here…</div>
    </div>
</div>
</div>

<!-- CRC32 -->
<div class="st-panel" id="st-crc32" style="display:none">
<?= st_toolbar('CRC32 Generator', '32-bit cyclic redundancy check — error detection checksum',
    '<span id="crc32-st"></span>'
    . '<button class="st-btn" onclick="ST.cp(\'crc32-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runHash(\'crc32\')">Generate</button>'
) ?>
<div class="st-single-panel">
    <div>
        <div class="st-field-label">Input Text</div>
        <div class="st-input-row"><input type="text" id="crc32-in" placeholder="Type or paste text to checksum…" oninput="ST.runHash(\'crc32\')"></div>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">CRC32 (Hex) <div><button class="st-copy-btn" onclick="ST.cp(\'crc32-out\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val placeholder" id="crc32-out">Result will appear here…</div>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">CRC32 (Decimal) <div><button class="st-copy-btn" onclick="ST.cp(\'crc32-dec\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val placeholder" id="crc32-dec">Result will appear here…</div>
    </div>
</div>
</div>

<!-- RIPEMD-160 -->
<div class="st-panel" id="st-ripemd160" style="display:none">
<?= st_toolbar('RIPEMD-160 Generator', '160-bit (40 hex chars) — used in Bitcoin address generation',
    '<span id="ripemd160-st"></span>'
    . '<button class="st-btn" onclick="ST.cp(\'ripemd160-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runHash(\'ripemd160\')">Generate</button>'
) ?>
<div class="st-single-panel">
    <div>
        <div class="st-field-label">Input Text</div>
        <div class="st-input-row"><input type="text" id="ripemd160-in" placeholder="Type or paste text to hash…" oninput="ST.runHash(\'ripemd160\')"></div>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">RIPEMD-160 Hash <div><button class="st-copy-btn" onclick="ST.cp(\'ripemd160-out\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val placeholder" id="ripemd160-out">Result will appear here…</div>
    </div>
</div>
</div>

<!-- Whirlpool -->
<div class="st-panel" id="st-whirlpool" style="display:none">
<?= st_toolbar('Whirlpool Generator', '512-bit (128 hex chars) — designed by Rijmen &amp; Barreto',
    '<span id="whirlpool-st"></span>'
    . '<button class="st-btn" onclick="ST.cp(\'whirlpool-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runHash(\'whirlpool\')">Generate</button>'
) ?>
<div class="st-single-panel">
    <div>
        <div class="st-field-label">Input Text</div>
        <div class="st-input-row"><input type="text" id="whirlpool-in" placeholder="Type or paste text to hash…" oninput="ST.runHash(\'whirlpool\')"></div>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">Whirlpool Hash <div><button class="st-copy-btn" onclick="ST.cp(\'whirlpool-out\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val placeholder" id="whirlpool-out">Result will appear here…</div>
    </div>
</div>
</div>

<!-- ══ PASSWORD TOOLS ════════════════════════════════════════════════════════ -->

<!-- Password Generator -->
<div class="st-panel" id="st-pwgen" style="display:none">
<?= st_toolbar('Password Generator', 'Cryptographically random passwords using Web Crypto',
    '<button class="st-btn" onclick="ST.cp(\'pwgen-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runPwGen()">Generate</button>'
) ?>
<div class="st-single-panel">
    <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end">
        <div>
            <div class="st-field-label">Length</div>
            <input type="number" id="pwgen-len" value="20" min="4" max="128" style="width:80px;padding:7px 10px;border:1px solid var(--color-border);border-radius:var(--radius-small);background:var(--color-background);color:var(--color-text);font-size:13px;outline:none" oninput="ST.runPwGen()">
        </div>
        <div class="st-pw-options">
            <label class="st-pw-option"><input type="checkbox" id="pwgen-upper" checked onchange="ST.runPwGen()"> Uppercase (A–Z)</label>
            <label class="st-pw-option"><input type="checkbox" id="pwgen-lower" checked onchange="ST.runPwGen()"> Lowercase (a–z)</label>
            <label class="st-pw-option"><input type="checkbox" id="pwgen-num"   checked onchange="ST.runPwGen()"> Numbers (0–9)</label>
            <label class="st-pw-option"><input type="checkbox" id="pwgen-sym"   checked onchange="ST.runPwGen()"> Symbols (!@#…)</label>
            <label class="st-pw-option"><input type="checkbox" id="pwgen-noamb"       onchange="ST.runPwGen()"> Exclude ambiguous (0Oo1Il)</label>
        </div>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">Generated Password <div><button class="st-copy-btn" onclick="ST.cp(\'pwgen-out\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val" id="pwgen-out" style="font-size:15px;letter-spacing:.04em;word-break:break-all"></div>
    </div>
    <div id="pwgen-entropy" style="font-size:12px;color:var(--color-text-muted)"></div>
</div>
</div>

<!-- Password Hash Generator -->
<div class="st-panel" id="st-pwhash" style="display:none">
<?= st_toolbar('Password Hash Generator', 'Hash a password using common algorithms',
    '<button class="st-btn" onclick="ST.cp(\'pwhash-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runPwHash()">Hash</button>'
) ?>
<div class="st-single-panel">
    <div>
        <div class="st-field-label">Password</div>
        <div class="st-input-row"><input type="password" id="pwhash-in" placeholder="Enter password to hash…" oninput="ST.runPwHash()"></div>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        <div class="st-select-wrap">Algorithm
            <select id="pwhash-algo" onchange="ST.runPwHash()">
                <option value="sha256">SHA-256</option>
                <option value="sha384">SHA-384</option>
                <option value="sha512">SHA-512</option>
                <option value="md5">MD5</option>
            </select>
        </div>
        <div class="st-select-wrap">Case
            <select id="pwhash-case" onchange="ST.runPwHash()">
                <option value="lower">Lowercase</option>
                <option value="upper">Uppercase</option>
            </select>
        </div>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">Hash Output <div><button class="st-copy-btn" onclick="ST.cp(\'pwhash-out\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val placeholder" id="pwhash-out">Result will appear here…</div>
    </div>
</div>
</div>

<!-- Password Strength Checker -->
<div class="st-panel" id="st-pwcheck" style="display:none">
<?= st_toolbar('Password Strength Checker', 'Analyse complexity, entropy and common weaknesses') ?>
<div class="st-single-panel">
    <div>
        <div class="st-field-label">Password</div>
        <div class="st-input-row"><input type="text" id="pwcheck-in" placeholder="Type a password to analyse…" oninput="ST.runPwCheck()"></div>
    </div>
    <div id="pwcheck-res">
        <div class="st-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Type a password above to see its strength</div>
    </div>
</div>
</div>

<!-- Bcrypt Generator -->
<div class="st-panel" id="st-bcrypt" style="display:none">
<?= st_toolbar('Bcrypt Generator', 'Adaptive hash function designed for password storage',
    '<button class="st-btn" onclick="ST.cp(\'bcrypt-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runBcrypt()">Hash</button>'
) ?>
<div class="st-single-panel">
    <div>
        <div class="st-field-label">Password</div>
        <div class="st-input-row"><input type="password" id="bcrypt-in" placeholder="Enter password to bcrypt…"></div>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        <div class="st-select-wrap">Cost rounds
            <select id="bcrypt-cost">
                <option value="10" selected>10 (default)</option>
                <option value="11">11</option>
                <option value="12">12 (strong)</option>
                <option value="13">13</option>
                <option value="14">14 (very strong)</option>
            </select>
        </div>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">Bcrypt Hash <div><button class="st-copy-btn" onclick="ST.cp(\'bcrypt-out\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val placeholder" id="bcrypt-out">Result will appear here…</div>
    </div>
    <div id="bcrypt-st" style="font-size:12px;color:var(--color-text-muted)"></div>
</div>
</div>

<!-- ══ JWT TOOLS ═════════════════════════════════════════════════════════════ -->

<!-- JWT Decoder -->
<div class="st-panel" id="st-jwtdec" style="display:none">
<?= st_toolbar('JWT Decoder', 'Decode a JWT token — header, payload and signature parts') ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="height:90px;display:flex;flex-direction:column;border-bottom:1px solid var(--color-border);flex-shrink:0">
        <div class="st-pane-label"><span>JWT Input</span></div>
        <textarea class="st-ta" id="jwtdec-in" oninput="ST.runJwtDec()" placeholder="Paste a JWT token (eyJ…)…" style="min-height:unset"></textarea>
    </div>
    <div id="jwtdec-out" style="flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:12px">
        <div class="st-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="15 10 20 15 15 20"/><path d="M4 4v7a4 4 0 0 0 4 4h12"/></svg>Paste a JWT above to decode it</div>
    </div>
</div>
</div>

<!-- JWT Encoder -->
<div class="st-panel" id="st-jwtenc" style="display:none">
<?= st_toolbar('JWT Encoder', 'Build and sign a JWT token',
    '<button class="st-btn" onclick="ST.cp(\'jwtenc-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runJwtEnc()">Encode</button>'
) ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="display:flex;flex:1;overflow:hidden">
        <div class="st-editor-col">
            <div class="st-pane-label"><span>Header JSON</span></div>
            <textarea class="st-ta" id="jwtenc-header" placeholder='{"alg":"HS256","typ":"JWT"}'><?= htmlspecialchars('{"alg":"HS256","typ":"JWT"}') ?></textarea>
        </div>
        <div class="st-editor-divider"></div>
        <div class="st-editor-col">
            <div class="st-pane-label"><span>Payload JSON</span></div>
            <textarea class="st-ta" id="jwtenc-payload" placeholder='{"sub":"1234567890","name":"John Doe","iat":1516239022}'><?= htmlspecialchars('{"sub":"1234567890","name":"John Doe","iat":1516239022}') ?></textarea>
        </div>
    </div>
    <div style="border-top:1px solid var(--color-border);padding:10px 14px;background:var(--color-surface);display:flex;align-items:center;gap:10px;flex-shrink:0">
        <div style="flex:1">
            <div class="st-field-label" style="margin-bottom:4px">Secret / Signing Key</div>
            <input type="text" id="jwtenc-secret" value="your-256-bit-secret" style="width:100%;padding:6px 10px;font-family:monospace;font-size:12px;border:1px solid var(--color-border);border-radius:var(--radius-small);background:var(--color-background);color:var(--color-text);outline:none">
        </div>
        <div class="st-select-wrap" style="margin-top:16px">Algorithm
            <select id="jwtenc-algo">
                <option value="HS256" selected>HS256</option>
                <option value="HS384">HS384</option>
                <option value="HS512">HS512</option>
            </select>
        </div>
    </div>
    <div style="border-top:1px solid var(--color-border);flex-shrink:0">
        <div class="st-pane-label"><span>Encoded JWT</span><div><button class="st-copy-btn" onclick="ST.cp(\'jwtenc-out\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val placeholder" id="jwtenc-out" style="padding:10px 14px;word-break:break-all;font-size:12px;min-height:50px">Token will appear here…</div>
    </div>
</div>
</div>

<!-- JWT Validator -->
<div class="st-panel" id="st-jwtval" style="display:none">
<?= st_toolbar('JWT Validator', 'Validate JWT structure and verify HMAC signature') ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="height:90px;display:flex;flex-direction:column;border-bottom:1px solid var(--color-border);flex-shrink:0">
        <div class="st-pane-label"><span>JWT Token</span></div>
        <textarea class="st-ta" id="jwtval-in" oninput="ST.runJwtVal()" placeholder="Paste JWT to validate…" style="min-height:unset"></textarea>
    </div>
    <div style="padding:10px 14px;background:var(--color-surface);border-bottom:1px solid var(--color-border);display:flex;align-items:center;gap:10px;flex-shrink:0">
        <div style="flex:1">
            <input type="text" id="jwtval-secret" placeholder="Secret key (optional — for signature verification)" oninput="ST.runJwtVal()" style="width:100%;padding:6px 10px;font-family:monospace;font-size:12px;border:1px solid var(--color-border);border-radius:var(--radius-small);background:var(--color-background);color:var(--color-text);outline:none">
        </div>
    </div>
    <div id="jwtval-out" style="flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px">
        <div class="st-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg>Paste a JWT above to validate it</div>
    </div>
</div>
</div>

<!-- JWT Inspector -->
<div class="st-panel" id="st-jwtinsp" style="display:none">
<?= st_toolbar('JWT Inspector', 'Full breakdown of all JWT fields and claims') ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="height:90px;display:flex;flex-direction:column;border-bottom:1px solid var(--color-border);flex-shrink:0">
        <div class="st-pane-label"><span>JWT Token</span></div>
        <textarea class="st-ta" id="jwtinsp-in" oninput="ST.runJwtInsp()" placeholder="Paste JWT to inspect…" style="min-height:unset"></textarea>
    </div>
    <div id="jwtinsp-out" style="flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:12px">
        <div class="st-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Paste a JWT above to inspect it</div>
    </div>
</div>
</div>

<!-- JWT Expiry Checker -->
<div class="st-panel" id="st-jwtexp" style="display:none">
<?= st_toolbar('JWT Expiry Checker', 'Check the exp, iat and nbf claims against current time') ?>
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div style="height:90px;display:flex;flex-direction:column;border-bottom:1px solid var(--color-border);flex-shrink:0">
        <div class="st-pane-label"><span>JWT Token</span></div>
        <textarea class="st-ta" id="jwtexp-in" oninput="ST.runJwtExp()" placeholder="Paste JWT to check expiry…" style="min-height:unset"></textarea>
    </div>
    <div id="jwtexp-out" style="flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:12px">
        <div class="st-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Paste a JWT above to check its expiry</div>
    </div>
</div>
</div>

<!-- ══ SECURITY UTILITIES ════════════════════════════════════════════════════ -->

<!-- HMAC Generator -->
<div class="st-panel" id="st-hmac" style="display:none">
<?= st_toolbar('HMAC Generator', 'Hash-based message authentication code using WebCrypto',
    '<button class="st-btn" onclick="ST.cp(\'hmac-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runHmac()">Generate</button>'
) ?>
<div class="st-single-panel">
    <div>
        <div class="st-field-label">Message</div>
        <div class="st-input-row"><input type="text" id="hmac-msg" placeholder="Message to authenticate…" oninput="ST.runHmac()"></div>
    </div>
    <div>
        <div class="st-field-label">Secret Key</div>
        <div class="st-input-row"><input type="text" id="hmac-key" placeholder="Your secret key…" oninput="ST.runHmac()"></div>
    </div>
    <div class="st-select-wrap">Algorithm
        <select id="hmac-algo" onchange="ST.runHmac()">
            <option value="SHA-256" selected>HMAC-SHA256</option>
            <option value="SHA-384">HMAC-SHA384</option>
            <option value="SHA-512">HMAC-SHA512</option>
            <option value="SHA-1">HMAC-SHA1</option>
        </select>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">HMAC Output <div><button class="st-copy-btn" onclick="ST.cp(\'hmac-out\',this)"><?= $copy_svg ?>Copy</button></div></div>
        <div class="st-output-val placeholder" id="hmac-out">Result will appear here…</div>
    </div>
</div>
</div>

<!-- File Hash Calculator -->
<div class="st-panel" id="st-filehash" style="display:none">
<?= st_toolbar('File Hash Calculator', 'Compute cryptographic hashes of any file — 100% client-side') ?>
<div class="st-single-panel">
    <div id="filehash-zone" class="st-drop-zone" onclick="document.getElementById(\'filehash-input\').click()" ondragover="event.preventDefault();this.classList.add(\'drag-over\')" ondragleave="this.classList.remove(\'drag-over\')" ondrop="ST.onFileDrop(event)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
        <strong>Click or drag &amp; drop a file</strong>
        <span style="font-size:12px">Any file type — hashed in your browser</span>
    </div>
    <input type="file" id="filehash-input" style="display:none" onchange="ST.onFileSelect(this)">
    <div id="filehash-out"></div>
</div>
</div>

<!-- Hash Verifier -->
<div class="st-panel" id="st-hashverify" style="display:none">
<?= st_toolbar('Hash Verifier', 'Compute a hash and compare it against a known value',
    '<button class="st-btn st-btn-primary" onclick="ST.runHashVerify()">Verify</button>'
) ?>
<div class="st-single-panel">
    <div>
        <div class="st-field-label">Input Text</div>
        <div class="st-input-row"><input type="text" id="hashverify-in" placeholder="Enter text to hash…"></div>
    </div>
    <div>
        <div class="st-field-label">Known Hash (to compare against)</div>
        <div class="st-input-row"><input type="text" id="hashverify-expected" placeholder="Paste the expected hash here…" style="font-family:monospace"></div>
    </div>
    <div class="st-select-wrap">Algorithm
        <select id="hashverify-algo">
            <option value="sha256" selected>SHA-256</option>
            <option value="sha512">SHA-512</option>
            <option value="sha1">SHA-1</option>
            <option value="md5">MD5</option>
            <option value="sha384">SHA-384</option>
        </select>
    </div>
    <div id="hashverify-out"></div>
</div>
</div>

<!-- Secure Token Generator -->
<div class="st-panel" id="st-tokengen" style="display:none">
<?= st_toolbar('Secure Token Generator', 'Cryptographically random UUIDs, API keys &amp; nonces via Web Crypto',
    '<button class="st-btn" onclick="ST.cp(\'tokengen-out\',this)">' . $copy_svg . 'Copy</button>'
    . '<button class="st-btn st-btn-primary" onclick="ST.runTokenGen()">Generate</button>'
) ?>
<div class="st-single-panel">
    <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end">
        <div class="st-select-wrap">Token type
            <select id="tokengen-type" onchange="ST.onTokenTypeChange()">
                <option value="uuid4">UUID v4</option>
                <option value="uuid1">UUID v1 (time-based)</option>
                <option value="hex">Hex string</option>
                <option value="base64">Base64 string</option>
                <option value="base64url">Base64URL string</option>
                <option value="alphanumeric">Alphanumeric (a–z, A–Z, 0–9)</option>
                <option value="numeric">Numeric (0–9 only)</option>
                <option value="apikey">API key (prefix:token format)</option>
            </select>
        </div>
        <div id="tokengen-len-wrap" style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--color-text-muted)">
            Bytes / length
            <input type="number" id="tokengen-len" value="32" min="4" max="256" style="width:72px;padding:5px 8px;font-size:12px;border:1px solid var(--color-border);border-radius:var(--radius-small);background:var(--color-background);color:var(--color-text);outline:none" oninput="ST.runTokenGen()">
        </div>
        <div id="tokengen-prefix-wrap" style="display:none;align-items:center;gap:6px;font-size:12px;color:var(--color-text-muted)">
            Prefix
            <input type="text" id="tokengen-prefix" value="sk" placeholder="sk" maxlength="16" style="width:64px;padding:5px 8px;font-size:12px;font-family:monospace;border:1px solid var(--color-border);border-radius:var(--radius-small);background:var(--color-background);color:var(--color-text);outline:none" oninput="ST.runTokenGen()">
        </div>
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--color-text-muted)">
            Quantity
            <input type="number" id="tokengen-qty" value="1" min="1" max="20" style="width:56px;padding:5px 8px;font-size:12px;border:1px solid var(--color-border);border-radius:var(--radius-small);background:var(--color-background);color:var(--color-text);outline:none" oninput="ST.runTokenGen()">
        </div>
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--color-text-muted)">
            Case
            <select id="tokengen-case" onchange="ST.runTokenGen()" style="font-size:12px;padding:5px 8px;border:1px solid var(--color-border);border-radius:var(--radius-small);background:var(--color-surface);color:var(--color-text);cursor:pointer">
                <option value="lower">Lowercase</option>
                <option value="upper">Uppercase</option>
                <option value="mixed">Mixed</option>
            </select>
        </div>
    </div>
    <div class="st-output-box">
        <div class="st-output-head">Generated Token(s) <div><button class="st-copy-btn" onclick="ST.cp(\'tokengen-out\',this)"><?= $copy_svg ?>Copy all</button></div></div>
        <div class="st-output-val" id="tokengen-out" style="white-space:pre;word-break:break-all;line-height:2"></div>
    </div>
    <div id="tokengen-meta" style="font-size:11.5px;color:var(--color-text-muted);display:flex;gap:16px;flex-wrap:wrap"></div>
</div>
</div>

</div><!-- /st-content -->
</div><!-- /st-shell -->

<script>
/* ===================================================================
   Security Toolkit — ST namespace
   =================================================================== */
'use strict';

/* ─── MD5 ──────────────────────────────────────────────────────────── */
function _md5(str) {
    function safeAdd(x, y) {
        var lsw = (x & 0xffff) + (y & 0xffff);
        return ((x >> 16) + (y >> 16) + (lsw >> 16)) << 16 | lsw & 0xffff;
    }
    function rol(n, c) { return n << c | n >>> 32 - c; }
    function cmn(q, a, b, x, s, t) { return safeAdd(rol(safeAdd(safeAdd(a, q), safeAdd(x, t)), s), b); }
    function ff(a,b,c,d,x,s,t){ return cmn(b&c|~b&d,a,b,x,s,t); }
    function gg(a,b,c,d,x,s,t){ return cmn(b&d|c&~d,a,b,x,s,t); }
    function hh(a,b,c,d,x,s,t){ return cmn(b^c^d,a,b,x,s,t); }
    function ii(a,b,c,d,x,s,t){ return cmn(c^(b|~d),a,b,x,s,t); }
    function md5blk(s) {
        var m = [], i;
        for (i=0;i<64;i+=4) m[i>>2]=s.charCodeAt(i)+(s.charCodeAt(i+1)<<8)+(s.charCodeAt(i+2)<<16)+(s.charCodeAt(i+3)<<24);
        return m;
    }
    var b64pad='', bytes = unescape(encodeURIComponent(str));
    var n = bytes.length, state = [1732584193,-271733879,-1732584194,271733878], i;
    for (i=64;i<=n;i+=64) {
        var c = md5blk(bytes.slice(i-64,i));
        var a=state[0],b=state[1],cc=state[2],d=state[3];
        a=ff(a,b,cc,d,c[0],7,-680876936); d=ff(d,a,b,cc,c[1],12,-389564586); cc=ff(cc,d,a,b,c[2],17,606105819); b=ff(b,cc,d,a,c[3],22,-1044525330);
        a=ff(a,b,cc,d,c[4],7,-176418897); d=ff(d,a,b,cc,c[5],12,1200080426); cc=ff(cc,d,a,b,c[6],17,-1473231341); b=ff(b,cc,d,a,c[7],22,-45705983);
        a=ff(a,b,cc,d,c[8],7,1770035416); d=ff(d,a,b,cc,c[9],12,-1958414417); cc=ff(cc,d,a,b,c[10],17,-42063); b=ff(b,cc,d,a,c[11],22,-1990404162);
        a=ff(a,b,cc,d,c[12],7,1804603682); d=ff(d,a,b,cc,c[13],12,-40341101); cc=ff(cc,d,a,b,c[14],17,-1502002290); b=ff(b,cc,d,a,c[15],22,1236535329);
        a=gg(a,b,cc,d,c[1],5,-165796510); d=gg(d,a,b,cc,c[6],9,-1069501632); cc=gg(cc,d,a,b,c[11],14,643717713); b=gg(b,cc,d,a,c[0],20,-373897302);
        a=gg(a,b,cc,d,c[5],5,-701558691); d=gg(d,a,b,cc,c[10],9,38016083); cc=gg(cc,d,a,b,c[15],14,-660478335); b=gg(b,cc,d,a,c[4],20,-405537848);
        a=gg(a,b,cc,d,c[9],5,568446438); d=gg(d,a,b,cc,c[14],9,-1019803690); cc=gg(cc,d,a,b,c[3],14,-187363961); b=gg(b,cc,d,a,c[8],20,1163531501);
        a=gg(a,b,cc,d,c[13],5,-1444681467); d=gg(d,a,b,cc,c[2],9,-51403784); cc=gg(cc,d,a,b,c[7],14,1735328473); b=gg(b,cc,d,a,c[12],20,-1926607734);
        a=hh(a,b,cc,d,c[5],4,-378558); d=hh(d,a,b,cc,c[8],11,-2022574463); cc=hh(cc,d,a,b,c[11],16,1839030562); b=hh(b,cc,d,a,c[14],23,-35309556);
        a=hh(a,b,cc,d,c[1],4,-1530992060); d=hh(d,a,b,cc,c[4],11,1272893353); cc=hh(cc,d,a,b,c[7],16,-155497632); b=hh(b,cc,d,a,c[10],23,-1094730640);
        a=hh(a,b,cc,d,c[13],4,681279174); d=hh(d,a,b,cc,c[0],11,-358537222); cc=hh(cc,d,a,b,c[3],16,-722521979); b=hh(b,cc,d,a,c[6],23,76029189);
        a=hh(a,b,cc,d,c[9],4,-640364487); d=hh(d,a,b,cc,c[12],11,-421815835); cc=hh(cc,d,a,b,c[15],16,530742520); b=hh(b,cc,d,a,c[2],23,-995338651);
        a=ii(a,b,cc,d,c[0],6,-198630844); d=ii(d,a,b,cc,c[7],10,1126891415); cc=ii(cc,d,a,b,c[14],15,-1416354905); b=ii(b,cc,d,a,c[5],21,-57434055);
        a=ii(a,b,cc,d,c[12],6,1700485571); d=ii(d,a,b,cc,c[3],10,-1894986606); cc=ii(cc,d,a,b,c[10],15,-1051523); b=ii(b,cc,d,a,c[1],21,-2054922799);
        a=ii(a,b,cc,d,c[8],6,1873313359); d=ii(d,a,b,cc,c[15],10,-30611744); cc=ii(cc,d,a,b,c[6],15,-1560198380); b=ii(b,cc,d,a,c[13],21,1309151649);
        a=ii(a,b,cc,d,c[4],6,-145523070); d=ii(d,a,b,cc,c[11],10,-1120210379); cc=ii(cc,d,a,b,c[2],15,718787259); b=ii(b,cc,d,a,c[9],21,-343485551);
        state[0]=safeAdd(a,state[0]); state[1]=safeAdd(b,state[1]); state[2]=safeAdd(cc,state[2]); state[3]=safeAdd(d,state[3]);
    }
    var tail = bytes.slice(n - n%64), length1 = n, s = [];
    tail += '\x80';
    while (tail.length%64 !== 56) tail += '\x00';
    tail += String.fromCharCode(length1<<3&0xff,length1>>>5&0xff,length1>>>13&0xff,length1>>>21&0xff,0,0,0,0);
    for (i=64;i<=tail.length;i+=64) {
        var c2=md5blk(tail.slice(i-64,i));
        var a2=state[0],b2=state[1],c3=state[2],d2=state[3];
        a2=ff(a2,b2,c3,d2,c2[0],7,-680876936); d2=ff(d2,a2,b2,c3,c2[1],12,-389564586); c3=ff(c3,d2,a2,b2,c2[2],17,606105819); b2=ff(b2,c3,d2,a2,c2[3],22,-1044525330);
        a2=ff(a2,b2,c3,d2,c2[4],7,-176418897); d2=ff(d2,a2,b2,c3,c2[5],12,1200080426); c3=ff(c3,d2,a2,b2,c2[6],17,-1473231341); b2=ff(b2,c3,d2,a2,c2[7],22,-45705983);
        a2=ff(a2,b2,c3,d2,c2[8],7,1770035416); d2=ff(d2,a2,b2,c3,c2[9],12,-1958414417); c3=ff(c3,d2,a2,b2,c2[10],17,-42063); b2=ff(b2,c3,d2,a2,c2[11],22,-1990404162);
        a2=ff(a2,b2,c3,d2,c2[12],7,1804603682); d2=ff(d2,a2,b2,c3,c2[13],12,-40341101); c3=ff(c3,d2,a2,b2,c2[14],17,-1502002290); b2=ff(b2,c3,d2,a2,c2[15],22,1236535329);
        a2=gg(a2,b2,c3,d2,c2[1],5,-165796510); d2=gg(d2,a2,b2,c3,c2[6],9,-1069501632); c3=gg(c3,d2,a2,b2,c2[11],14,643717713); b2=gg(b2,c3,d2,a2,c2[0],20,-373897302);
        a2=gg(a2,b2,c3,d2,c2[5],5,-701558691); d2=gg(d2,a2,b2,c3,c2[10],9,38016083); c3=gg(c3,d2,a2,b2,c2[15],14,-660478335); b2=gg(b2,c3,d2,a2,c2[4],20,-405537848);
        a2=gg(a2,b2,c3,d2,c2[9],5,568446438); d2=gg(d2,a2,b2,c3,c2[14],9,-1019803690); c3=gg(c3,d2,a2,b2,c2[3],14,-187363961); b2=gg(b2,c3,d2,a2,c2[8],20,1163531501);
        a2=gg(a2,b2,c3,d2,c2[13],5,-1444681467); d2=gg(d2,a2,b2,c3,c2[2],9,-51403784); c3=gg(c3,d2,a2,b2,c2[7],14,1735328473); b2=gg(b2,c3,d2,a2,c2[12],20,-1926607734);
        a2=hh(a2,b2,c3,d2,c2[5],4,-378558); d2=hh(d2,a2,b2,c3,c2[8],11,-2022574463); c3=hh(c3,d2,a2,b2,c2[11],16,1839030562); b2=hh(b2,c3,d2,a2,c2[14],23,-35309556);
        a2=hh(a2,b2,c3,d2,c2[1],4,-1530992060); d2=hh(d2,a2,b2,c3,c2[4],11,1272893353); c3=hh(c3,d2,a2,b2,c2[7],16,-155497632); b2=hh(b2,c3,d2,a2,c2[10],23,-1094730640);
        a2=hh(a2,b2,c3,d2,c2[13],4,681279174); d2=hh(d2,a2,b2,c3,c2[0],11,-358537222); c3=hh(c3,d2,a2,b2,c2[3],16,-722521979); b2=hh(b2,c3,d2,a2,c2[6],23,76029189);
        a2=hh(a2,b2,c3,d2,c2[9],4,-640364487); d2=hh(d2,a2,b2,c3,c2[12],11,-421815835); c3=hh(c3,d2,a2,b2,c2[15],16,530742520); b2=hh(b2,c3,d2,a2,c2[2],23,-995338651);
        a2=ii(a2,b2,c3,d2,c2[0],6,-198630844); d2=ii(d2,a2,b2,c3,c2[7],10,1126891415); c3=ii(c3,d2,a2,b2,c2[14],15,-1416354905); b2=ii(b2,c3,d2,a2,c2[5],21,-57434055);
        a2=ii(a2,b2,c3,d2,c2[12],6,1700485571); d2=ii(d2,a2,b2,c3,c2[3],10,-1894986606); c3=ii(c3,d2,a2,b2,c2[10],15,-1051523); b2=ii(b2,c3,d2,a2,c2[1],21,-2054922799);
        a2=ii(a2,b2,c3,d2,c2[8],6,1873313359); d2=ii(d2,a2,b2,c3,c2[15],10,-30611744); c3=ii(c3,d2,a2,b2,c2[6],15,-1560198380); b2=ii(b2,c3,d2,a2,c2[13],21,1309151649);
        a2=ii(a2,b2,c3,d2,c2[4],6,-145523070); d2=ii(d2,a2,b2,c3,c2[11],10,-1120210379); c3=ii(c3,d2,a2,b2,c2[2],15,718787259); b2=ii(b2,c3,d2,a2,c2[9],21,-343485551);
        state[0]=safeAdd(a2,state[0]); state[1]=safeAdd(b2,state[1]); state[2]=safeAdd(c3,state[2]); state[3]=safeAdd(d2,state[3]);
    }
    var hex='', t;
    for (i=0;i<4;i++) {
        t=state[i];
        hex+=('0'+(t&0xff).toString(16)).slice(-2)+('0'+(t>>>8&0xff).toString(16)).slice(-2)+('0'+(t>>>16&0xff).toString(16)).slice(-2)+('0'+(t>>>24&0xff).toString(16)).slice(-2);
    }
    return hex;
}

/* ─── CRC32 ─────────────────────────────────────────────────────────── */
var _crc32table = (function(){
    var t=[], c, i, j;
    for(i=0;i<256;i++){ c=i; for(j=0;j<8;j++) c=c&1?0xEDB88320^(c>>>1):c>>>1; t[i]=c; }
    return t;
})();
function _crc32(str) {
    var bytes=new TextEncoder().encode(str), crc=-1;
    for(var i=0;i<bytes.length;i++) crc=(crc>>>8)^_crc32table[(crc^bytes[i])&0xff];
    return (crc^-1)>>>0;
}

/* ─── RIPEMD-160 ─────────────────────────────────────────────────────── */
function _ripemd160(str) {
    function rol(n,l){return n<<l|n>>>32-l;}
    function f(x,y,z,j){return j<16?x^y^z:j<32?x&y|~x&z:j<48?(x|~y)^z:j<64?x&z|y&~z:x^(y|~z);}
    function K(j){return j<16?0:j<32?0x5A827999:j<48?0x6ED9EBA1:j<64?0x8F1BBCDC:0xA953FD4E;}
    function KK(j){return j<16?0x50A28BE6:j<32?0x5C4DD124:j<48?0x6D703EF3:j<64?0x7A6D76E9:0;}
    var R=[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,7,4,13,1,10,6,15,3,12,0,9,5,2,14,11,8,3,10,14,4,9,15,8,1,2,7,0,6,13,11,5,12,1,9,11,10,0,8,12,4,13,3,7,15,14,5,6,2,4,0,5,9,7,12,2,10,14,1,3,8,11,6,15,13];
    var RR=[5,14,7,0,9,2,11,4,13,6,15,8,1,10,3,12,6,11,3,7,0,13,5,10,14,15,8,12,4,9,1,2,15,5,1,3,7,14,6,9,11,8,12,2,10,0,4,13,8,6,4,1,3,11,15,0,5,12,2,13,9,7,10,14,12,15,10,4,1,5,8,7,6,2,13,14,0,3,9,11];
    var S=[11,14,15,12,5,8,7,9,11,13,14,15,6,7,9,8,7,6,8,13,11,9,7,15,7,12,15,9,11,7,13,12,11,13,6,7,14,9,13,15,14,8,13,6,5,12,7,5,11,12,14,15,14,15,9,8,9,14,5,6,8,6,5,12,9,15,5,11,6,8,13,12,5,12,13,14,11,8,5,6];
    var SS=[8,9,9,11,13,15,15,5,7,7,8,11,14,14,12,6,9,13,15,7,12,8,9,11,7,7,12,7,6,15,13,11,9,7,15,11,8,6,6,14,12,13,5,14,13,13,7,5,15,5,8,11,14,14,6,14,6,9,12,9,12,5,15,8,8,5,12,9,12,5,14,6,8,13,6,5,15,13,11,11];
    var bytes=new TextEncoder().encode(str);
    var len=bytes.length, bitLen=len*8;
    var msg=Array.from(bytes);
    msg.push(0x80);
    while(msg.length%64!==56) msg.push(0);
    for(var i=0;i<8;i++) msg.push(i<4?(bitLen>>>(i*8))&0xff:0);
    var words=[];
    for(i=0;i<msg.length;i+=4) words.push(msg[i]|(msg[i+1]<<8)|(msg[i+2]<<16)|(msg[i+3]<<24));
    var h=[0x67452301,0xEFCDAB89,0x98BADCFE,0x10325476,0xC3D2E1F0];
    for(var blk=0;blk<words.length;blk+=16) {
        var W=words.slice(blk,blk+16);
        var al=h[0],bl=h[1],cl=h[2],dl=h[3],el=h[4];
        var ar=h[0],br=h[1],cr=h[2],dr=h[3],er=h[4];
        for(var j=0;j<80;j++){
            var t=(al+f(bl,cl,dl,j)+W[R[j]]+K(j))|0; t=rol(t,S[j])+el|0; al=el;el=dl;dl=rol(cl,10);cl=bl;bl=t;
            t=(ar+f(br,cr,dr,79-j)+W[RR[j]]+KK(j))|0; t=rol(t,SS[j])+er|0; ar=er;er=dr;dr=rol(cr,10);cr=br;br=t;
        }
        var t2=(h[1]+cl+dr)|0; h[1]=(h[2]+dl+er)|0; h[2]=(h[3]+el+ar)|0; h[3]=(h[4]+al+br)|0; h[4]=(h[0]+bl+cr)|0; h[0]=t2;
    }
    var hex='';
    for(i=0;i<5;i++) { var v=h[i]; hex+=('0'+((v)&0xff).toString(16)).slice(-2)+('0'+((v>>>8)&0xff).toString(16)).slice(-2)+('0'+((v>>>16)&0xff).toString(16)).slice(-2)+('0'+((v>>>24)&0xff).toString(16)).slice(-2); }
    return hex;
}

/* ─── SHA-224 (pure JS) ─────────────────────────────────────────────── */
function _sha224(str) {
    var K=[0x428a2f98,0x71374491,0xb5c0fbcf,0xe9b5dba5,0x3956c25b,0x59f111f1,0x923f82a4,0xab1c5ed5,0xd807aa98,0x12835b01,0x243185be,0x550c7dc3,0x72be5d74,0x80deb1fe,0x9bdc06a7,0xc19bf174,0xe49b69c1,0xefbe4786,0x0fc19dc6,0x240ca1cc,0x2de92c6f,0x4a7484aa,0x5cb0a9dc,0x76f988da,0x983e5152,0xa831c66d,0xb00327c8,0xbf597fc7,0xc6e00bf3,0xd5a79147,0x06ca6351,0x14292967,0x27b70a85,0x2e1b2138,0x4d2c6dfc,0x53380d13,0x650a7354,0x766a0abb,0x81c2c92e,0x92722c85,0xa2bfe8a1,0xa81a664b,0xc24b8b70,0xc76c51a3,0xd192e819,0xd6990624,0xf40e3585,0x106aa070,0x19a4c116,0x1e376c08,0x2748774c,0x34b0bcb5,0x391c0cb3,0x4ed8aa4a,0x5b9cca4f,0x682e6ff3,0x748f82ee,0x78a5636f,0x84c87814,0x8cc70208,0x90befffa,0xa4506ceb,0xbef9a3f7,0xc67178f2];
    var H=[0xc1059ed8,0x367cd507,0x3070dd17,0xf70e5939,0xffc00b31,0x68581511,0x64f98fa7,0xbefa4fa4];
    function rotr(x,n){return x>>>n|x<<32-n;}
    function ch(x,y,z){return x&y^~x&z;}
    function maj(x,y,z){return x&y^x&z^y&z;}
    function sig0(x){return rotr(x,2)^rotr(x,13)^rotr(x,22);}
    function sig1(x){return rotr(x,6)^rotr(x,11)^rotr(x,25);}
    function gam0(x){return rotr(x,7)^rotr(x,18)^x>>>3;}
    function gam1(x){return rotr(x,17)^rotr(x,19)^x>>>10;}
    var enc=new TextEncoder(), bytes=enc.encode(str);
    var bLen=bytes.length, bitLen=bLen*8;
    var msg=Array.from(bytes);
    msg.push(0x80);
    while(msg.length%64!==56) msg.push(0);
    for(var i=7;i>=0;i--) msg.push((bitLen/Math.pow(2,i*8))&0xff);
    var words=[]; for(i=0;i<msg.length;i+=4) words.push(msg[i]<<24|msg[i+1]<<16|msg[i+2]<<8|msg[i+3]);
    for(var blk=0;blk<words.length;blk+=16) {
        var W=words.slice(blk,blk+16);
        for(var j=16;j<64;j++) W[j]=(gam1(W[j-2])+W[j-7]+gam0(W[j-15])+W[j-16])|0;
        var a=H[0],b=H[1],c=H[2],d=H[3],e=H[4],f=H[5],g=H[6],h=H[7];
        for(j=0;j<64;j++){
            var t1=(h+sig1(e)+ch(e,f,g)+K[j]+W[j])|0;
            var t2=(sig0(a)+maj(a,b,c))|0;
            h=g;g=f;f=e;e=(d+t1)|0;d=c;c=b;b=a;a=(t1+t2)|0;
        }
        H[0]=(H[0]+a)|0;H[1]=(H[1]+b)|0;H[2]=(H[2]+c)|0;H[3]=(H[3]+d)|0;
        H[4]=(H[4]+e)|0;H[5]=(H[5]+f)|0;H[6]=(H[6]+g)|0;H[7]=(H[7]+h)|0;
    }
    var hex='';
    for(i=0;i<7;i++) hex+=('00000000'+((H[i]>>>0).toString(16))).slice(-8);
    return hex;
}

/* ─── Whirlpool ─────────────────────────────────────────────────────── */
var _whirlpool=(function(){
    var SBOX=[0x18,0x23,0xc6,0xe8,0x87,0xb8,0x01,0x4f,0x36,0xa6,0xd2,0xf5,0x79,0x6f,0x91,0x52,0x60,0xbc,0x9b,0x8e,0xa3,0x0c,0x7b,0x35,0x1d,0xe0,0xd7,0xc2,0x2e,0x4b,0xfe,0x57,0x15,0x77,0x37,0xe5,0x9f,0xf0,0x4a,0xda,0x58,0xc9,0x29,0x0a,0xb1,0xa0,0x6b,0x85,0xbd,0x5d,0x10,0xf4,0xcb,0x3e,0x05,0x67,0xe4,0x27,0x41,0x8b,0xa7,0x7d,0x95,0xd8,0xfb,0xee,0x7c,0x66,0xdd,0x17,0x47,0x9e,0xca,0x2d,0xbf,0x07,0xad,0x5a,0x83,0x33,0x63,0x02,0xaa,0x71,0xc8,0x19,0x49,0xd9,0xf2,0xe3,0x5b,0x88,0x9a,0x26,0x32,0xb0,0xe9,0x0f,0xd5,0x80,0xbe,0xcd,0x34,0x48,0xff,0x7a,0x90,0x5f,0x20,0x68,0x1a,0xae,0xb4,0x54,0x93,0x22,0x64,0xf1,0x73,0x12,0x40,0x08,0xc3,0xec,0xdb,0xa1,0x8d,0x3d,0x97,0x00,0xcf,0x2b,0x76,0x82,0xd6,0x1b,0xb5,0xaf,0x6a,0x50,0x45,0xf3,0x30,0xef,0x3f,0x55,0xa2,0xea,0x65,0xba,0x2f,0xc0,0xde,0x1c,0xfd,0x4d,0x92,0x75,0x06,0x8a,0xb2,0xe6,0x0e,0x1f,0x62,0xd4,0xa8,0x96,0xf9,0xc5,0x25,0x59,0x84,0x72,0x39,0x4c,0x5e,0x78,0x38,0x8c,0xd1,0xa5,0xe2,0x61,0xb3,0x21,0x9c,0x1e,0x43,0xc7,0xfc,0x04,0x51,0x99,0x6d,0x0d,0xfa,0xdf,0x7e,0x24,0x3b,0xab,0xce,0x11,0x8f,0x4e,0xb7,0xeb,0x3c,0x81,0x94,0xf7,0xb9,0x13,0x2c,0xd3,0xe7,0x6e,0xc4,0x03,0x56,0x44,0x7f,0xa9,0x2a,0xbb,0xc1,0x53,0xdc,0x0b,0x9d,0x6c,0x31,0x74,0xf6,0x46,0xac,0x89,0x14,0xe1,0x16,0x3a,0x69,0x09,0x70,0xb6,0xd0,0xed,0xcc,0x42,0x98,0xa4,0x28,0x5c,0xf8,0x86];
    function G(x){return x<0x80?x*0x02:(x*0x02)^0x011d;}
    function buildC(i){
        var v=new Array(256);
        for(var x=0;x<256;x++){
            var s=SBOX[x];
            var g1=G(s),g2=G(g1),g3=s^g2,g4=G(g2),g5=s^g4,g6=G(g3),g7=s^g6;
            var vs=[s,s,g4,g1,g2,g3,g5,g7];
            var rot=i&7,idx=(7-i+rot)%8;
            v[x]=BigInt('0x'+[vs[(0+i)%8],vs[(1+i)%8],vs[(2+i)%8],vs[(3+i)%8],vs[(4+i)%8],vs[(5+i)%8],vs[(6+i)%8],vs[(7+i)%8]].map(function(b){return b.toString(16).padStart(2,'0');}).join(''));
        }
        return v;
    }
    var C=[];
    for(var i=0;i<8;i++) C.push(buildC(i));
    var RC=[];
    for(var r=1;r<=10;r++){
        var rc=0n;
        for(var c2=0;c2<8;c2++){
            var ii=(r-1)*8+c2;
            rc=(rc<<8n)|BigInt(SBOX[ii]||0);
        }
        RC.push(rc<<(BigInt(56)-BigInt(0))&0xFFFFFFFFFFFFFFFFn);
        RC[r-1]=(BigInt(SBOX[(r-1)*8])<<56n|BigInt(SBOX[(r-1)*8+1]||0)<<48n|BigInt(SBOX[(r-1)*8+2]||0)<<40n|BigInt(SBOX[(r-1)*8+3]||0)<<32n|BigInt(SBOX[(r-1)*8+4]||0)<<24n|BigInt(SBOX[(r-1)*8+5]||0)<<16n|BigInt(SBOX[(r-1)*8+6]||0)<<8n|BigInt(SBOX[(r-1)*8+7]||0));
    }
    function whirl(str){
        var enc=new TextEncoder(),bytes=Array.from(enc.encode(str));
        var bLen=bytes.length,bitLen=BigInt(bLen)*8n;
        bytes.push(0x80);
        while(bytes.length%64!==32) bytes.push(0);
        for(var i=7;i>=0;i--) bytes.push(0);
        for(var i=7;i>=0;i--) bytes.push(Number((bitLen>>(BigInt(i)*8n))&0xffn));
        var H=[0n,0n,0n,0n,0n,0n,0n,0n];
        function toWords(blk){
            var w=[];
            for(var j=0;j<8;j++){
                var val=0n;
                for(var k=0;k<8;k++) val=(val<<8n)|BigInt(blk[j*8+k]||0);
                w.push(val);
            }
            return w;
        }
        function lookup(L,idx){
            return C[idx][Number((L>>BigInt((7-idx)*8))&0xffn)];
        }
        for(var blk=0;blk<bytes.length;blk+=64){
            var block=bytes.slice(blk,blk+64);
            var W=toWords(block);
            var K2=H.slice(),L=[];
            var state=W.map(function(w,j){return w^H[j];});
            for(var r=0;r<10;r++){
                for(var j2=0;j2<8;j2++){
                    L[j2]=lookup(K2[0],j2)|0n;
                    for(var c3=1;c3<8;c3++) L[j2]^=C[j2][Number((K2[c3]>>BigInt((7-j2)*8))&0xffn)];
                }
                L[0]^=RC[r];
                K2=L.slice();
                var newSt=[];
                for(var j3=0;j3<8;j3++){
                    var val2=K2[j3];
                    for(var c4=0;c4<8;c4++) val2^=C[c4][Number((state[(j3-c4+8)%8]>>BigInt((7-c4)*8))&0xffn)];
                    newSt.push(val2&0xFFFFFFFFFFFFFFFFn);
                }
                state=newSt;
            }
            for(var j4=0;j4<8;j4++) H[j4]^=state[j4]^W[j4];
        }
        return H.map(function(v){return (v&0xFFFFFFFFFFFFFFFFn).toString(16).padStart(16,'0');}).join('');
    }
    return whirl;
})();

/* ─── WebCrypto helpers ─────────────────────────────────────────────── */
async function _webcrypto(algo, str) {
    var enc=new TextEncoder();
    var buf=await crypto.subtle.digest(algo, enc.encode(str));
    return Array.from(new Uint8Array(buf)).map(function(b){return b.toString(16).padStart(2,'0');}).join('');
}
async function _hmacCrypto(algo, key, msg) {
    var enc=new TextEncoder();
    var k=await crypto.subtle.importKey('raw',enc.encode(key),{name:'HMAC',hash:algo},false,['sign']);
    var sig=await crypto.subtle.sign('HMAC',k,enc.encode(msg));
    return Array.from(new Uint8Array(sig)).map(function(b){return b.toString(16).padStart(2,'0');}).join('');
}
async function _webcryptoBytes(algo, buf) {
    var hash=await crypto.subtle.digest(algo, buf);
    return Array.from(new Uint8Array(hash)).map(function(b){return b.toString(16).padStart(2,'0');}).join('');
}

/* ─── Base64url helpers ─────────────────────────────────────────────── */
function b64urlEncode(str) {
    return btoa(unescape(encodeURIComponent(str))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,'');
}
function b64urlDecode(str) {
    var s=str.replace(/-/g,'+').replace(/_/g,'/');
    while(s.length%4) s+='=';
    try { return decodeURIComponent(escape(atob(s))); } catch(e) { return atob(s); }
}
function b64urlDecodeBytes(str) {
    var s=str.replace(/-/g,'+').replace(/_/g,'/');
    while(s.length%4) s+='=';
    try { return atob(s); } catch(e) { return ''; }
}

/* ─── bcryptjs (CDN) ────────────────────────────────────────────────── */
var _bcryptjsLoaded=false;
function _loadBcrypt(cb) {
    if(typeof dcodeIO!=='undefined'&&dcodeIO.bcrypt) { cb(dcodeIO.bcrypt); return; }
    if(typeof bcrypt!=='undefined') { cb(bcrypt); return; }
    var s=document.createElement('script');
    s.src='https://cdnjs.cloudflare.com/ajax/libs/bcryptjs/2.4.3/bcrypt.min.js';
    s.onload=function(){ cb(typeof dcodeIO!=='undefined'?dcodeIO.bcrypt:window.bcrypt); };
    s.onerror=function(){ cb(null); };
    document.head.appendChild(s);
}

/* ─── Main ST object ────────────────────────────────────────────────── */
var ST = {
    _cur: 'md5',

    switchTool: function(id) {
        document.querySelectorAll('.st-panel').forEach(function(p){p.style.display='none';});
        document.querySelectorAll('.st-nav').forEach(function(n){n.classList.remove('active');});
        var panel=document.getElementById('st-'+id);
        if(panel) panel.style.display='flex';
        var btn=document.querySelector('.st-nav[data-tool="'+id+'"]');
        if(btn) btn.classList.add('active');
        ST._cur=id;
    },

    clearCurrent: function() {
        var inputs=document.querySelectorAll('#st-'+ST._cur+' input[type="text"],#st-'+ST._cur+' input[type="password"],#st-'+ST._cur+' textarea');
        inputs.forEach(function(el){el.value='';if(el.oninput)el.oninput();});
    },

    cp: function(id, btn) {
        var el=document.getElementById(id);
        if(!el) return;
        var text=el.dataset.value||el.textContent||el.value||'';
        if(!text.trim()) return;
        navigator.clipboard.writeText(text).then(function(){
            if(!btn) return;
            var old=btn.innerHTML;
            btn.innerHTML=btn.innerHTML.replace(/Copy/,'Copied!');
            btn.classList.add('copied');
            setTimeout(function(){btn.innerHTML=old;btn.classList.remove('copied');},1500);
        });
    },

    setOut: function(id, val, placeholder) {
        var el=document.getElementById(id);
        if(!el) return;
        if(!val) { el.textContent=placeholder||''; el.dataset.value=''; el.className=(el.className||'').replace('placeholder','').trim()+' placeholder'; return; }
        el.textContent=val; el.dataset.value=val;
        el.className=(el.className||'').replace('placeholder','').trim();
    },

    /* ── Hash Generators ── */
    runHash: async function(type) {
        var inp=document.getElementById(type+'-in');
        if(!inp) return;
        var val=inp.value, result='';
        try {
            switch(type) {
                case 'md5':      result=_md5(val).toUpperCase(); break;
                case 'sha1':     result=(await _webcrypto('SHA-1',val)).toUpperCase(); break;
                case 'sha224':   result=_sha224(val).toUpperCase(); break;
                case 'sha256':   result=(await _webcrypto('SHA-256',val)).toUpperCase(); break;
                case 'sha384':   result=(await _webcrypto('SHA-384',val)).toUpperCase(); break;
                case 'sha512':   result=(await _webcrypto('SHA-512',val)).toUpperCase(); break;
                case 'crc32':
                    var c=_crc32(val);
                    ST.setOut('crc32-out',val?c.toString(16).toUpperCase().padStart(8,'0'):'');
                    ST.setOut('crc32-dec',val?c.toString():'');
                    return;
                case 'ripemd160':result=_ripemd160(val).toUpperCase(); break;
                case 'whirlpool':result=_whirlpool(val).toUpperCase(); break;
            }
            ST.setOut(type+'-out',val?result:'');
        } catch(e) { ST.setOut(type+'-out','Error: '+e.message); }
    },

    /* ── Password Generator ── */
    runPwGen: function() {
        var len=Math.max(4,Math.min(128,parseInt(document.getElementById('pwgen-len').value)||20));
        var upper=document.getElementById('pwgen-upper').checked;
        var lower=document.getElementById('pwgen-lower').checked;
        var num=document.getElementById('pwgen-num').checked;
        var sym=document.getElementById('pwgen-sym').checked;
        var noamb=document.getElementById('pwgen-noamb').checked;
        var chars='';
        if(upper) chars+='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if(lower) chars+='abcdefghijklmnopqrstuvwxyz';
        if(num)   chars+='0123456789';
        if(sym)   chars+='!@#$%^&*()-_=+[]{}|;:,.<>?';
        if(noamb) chars=chars.replace(/[0Oo1Il]/g,'');
        if(!chars) { document.getElementById('pwgen-out').textContent='Select at least one character set'; return; }
        var arr=new Uint32Array(len);
        crypto.getRandomValues(arr);
        var pw=Array.from(arr).map(function(v){return chars[v%chars.length];}).join('');
        document.getElementById('pwgen-out').textContent=pw;
        document.getElementById('pwgen-out').dataset.value=pw;
        var entropy=Math.floor(len*Math.log2(chars.length));
        var strength=entropy<40?'Weak':entropy<60?'Reasonable':entropy<80?'Strong':'Very Strong';
        document.getElementById('pwgen-entropy').textContent='Entropy: ~'+entropy+' bits — '+strength;
    },

    /* ── Password Hash Generator ── */
    runPwHash: async function() {
        var pw=document.getElementById('pwhash-in').value;
        var algo=document.getElementById('pwhash-algo').value;
        var caseMode=document.getElementById('pwhash-case').value;
        if(!pw) { ST.setOut('pwhash-out',''); return; }
        var hash;
        if(algo==='md5') hash=_md5(pw);
        else hash=await _webcrypto(algo==='sha256'?'SHA-256':algo==='sha384'?'SHA-384':'SHA-512',pw);
        ST.setOut('pwhash-out',caseMode==='upper'?hash.toUpperCase():hash.toLowerCase());
    },

    /* ── Password Strength Checker ── */
    runPwCheck: function() {
        var pw=document.getElementById('pwcheck-in').value;
        var res=document.getElementById('pwcheck-res');
        if(!pw) { res.innerHTML='<div class="st-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Type a password above to see its strength</div>'; return; }
        var checks=[
            {label:'At least 8 characters',pass:pw.length>=8},
            {label:'At least 12 characters (recommended)',pass:pw.length>=12},
            {label:'Contains uppercase letters',pass:/[A-Z]/.test(pw)},
            {label:'Contains lowercase letters',pass:/[a-z]/.test(pw)},
            {label:'Contains numbers',pass:/[0-9]/.test(pw)},
            {label:'Contains symbols',pass:/[^A-Za-z0-9]/.test(pw)},
            {label:'No common patterns (123, abc, qwerty)',pass:!/123|abc|qwerty|password|letmein|admin/i.test(pw)},
        ];
        var passCount=checks.filter(function(c){return c.pass;}).length;
        var strength=passCount<=2?1:passCount<=3?2:passCount<=4?3:passCount<=5?4:5;
        var labels=['','Very Weak','Weak','Fair','Strong','Very Strong'];
        var poolSize=((/[A-Z]/.test(pw)?26:0)+(/[a-z]/.test(pw)?26:0)+(/[0-9]/.test(pw)?10:0)+(/[^A-Za-z0-9]/.test(pw)?32:0))||1;
        var entropy=Math.floor(pw.length*Math.log2(poolSize));
        var checkSvg='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
        var xSvg='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
        var html='<div style="margin-bottom:12px">'
            +'<div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px"><span style="font-weight:600">Strength: '+labels[strength]+'</span><span style="color:var(--color-text-muted)">Entropy: ~'+entropy+' bits</span></div>'
            +'<div class="st-strength-meter"><div class="st-strength-bar st-strength-'+strength+'"></div></div></div>'
            +'<div class="st-check-list">';
        checks.forEach(function(c){
            html+='<div class="st-check-item'+(c.pass?' pass':'')+'"><span style="color:'+(c.pass?'#22c55e':'#ef4444')+'">'+(c.pass?checkSvg:xSvg)+'</span>'+c.label+'</div>';
        });
        html+='</div>';
        res.innerHTML=html;
    },

    /* ── Bcrypt Generator ── */
    runBcrypt: function() {
        var pw=document.getElementById('bcrypt-in').value;
        var cost=parseInt(document.getElementById('bcrypt-cost').value)||10;
        var out=document.getElementById('bcrypt-out');
        var status=document.getElementById('bcrypt-st');
        if(!pw) { ST.setOut('bcrypt-out',''); status.textContent=''; return; }
        status.textContent='Hashing… (cost='+cost+', this may take a moment)';
        out.textContent='Computing…'; out.className=out.className.replace('placeholder','').trim();
        _loadBcrypt(function(bc) {
            if(!bc) { ST.setOut('bcrypt-out','Error: bcryptjs library could not be loaded'); status.textContent=''; return; }
            try {
                var salt=bc.genSaltSync(cost);
                var hash=bc.hashSync(pw,salt);
                ST.setOut('bcrypt-out',hash);
                status.textContent='Done — cost='+cost+', algorithm=2b';
            } catch(e) { ST.setOut('bcrypt-out','Error: '+e.message); status.textContent=''; }
        });
    },

    /* ── JWT Helpers ── */
    _jwtDecode: function(token) {
        var parts=token.trim().split('.');
        if(parts.length<2) throw new Error('Invalid JWT — expected 3 parts separated by "."');
        var header, payload;
        try { header=JSON.parse(b64urlDecode(parts[0])); } catch(e) { throw new Error('Invalid header: '+e.message); }
        try { payload=JSON.parse(b64urlDecode(parts[1])); } catch(e) { payload=null; }
        return {header:header, payload:payload, sig:parts[2]||'', parts:parts};
    },

    /* ── JWT Decoder ── */
    runJwtDec: function() {
        var token=document.getElementById('jwtdec-in').value.trim();
        var out=document.getElementById('jwtdec-out');
        if(!token) { out.innerHTML='<div class="st-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="15 10 20 15 15 20"/><path d="M4 4v7a4 4 0 0 0 4 4h12"/></svg>Paste a JWT above to decode it</div>'; return; }
        try {
            var d=ST._jwtDecode(token);
            out.innerHTML=ST._jwtSections(d);
        } catch(e) {
            out.innerHTML='<div class="alert alert-danger" style="margin:0">'+e.message+'</div>';
        }
    },

    _jwtSections: function(d) {
        function fmt(obj) { try{return JSON.stringify(obj,null,2);}catch(e){return String(obj);} }
        var h='<div class="st-jwt-section"><div class="st-jwt-section-head"><span class="st-jwt-badge header">Header</span> Algorithm &amp; token type</div><div class="st-jwt-body">'+escHtml(fmt(d.header))+'</div></div>';
        var p='<div class="st-jwt-section"><div class="st-jwt-section-head"><span class="st-jwt-badge payload">Payload</span> Claims</div><div class="st-jwt-body">'+(d.payload?escHtml(fmt(d.payload)):'<em>Could not parse payload</em>')+'</div></div>';
        var s='<div class="st-jwt-section"><div class="st-jwt-section-head"><span class="st-jwt-badge sig">Signature</span> Verification</div><div class="st-jwt-body" style="word-break:break-all">'+escHtml(d.sig||'(empty)')+'</div></div>';
        return h+p+s;
    },

    /* ── JWT Encoder ── */
    runJwtEnc: async function() {
        var headerStr=document.getElementById('jwtenc-header').value.trim();
        var payloadStr=document.getElementById('jwtenc-payload').value.trim();
        var secret=document.getElementById('jwtenc-secret').value;
        var algo=document.getElementById('jwtenc-algo').value;
        var out=document.getElementById('jwtenc-out');
        try {
            var header=JSON.parse(headerStr);
            header.alg=algo; header.typ='JWT';
            var payload=JSON.parse(payloadStr);
            var h=b64urlEncode(JSON.stringify(header));
            var p=b64urlEncode(JSON.stringify(payload));
            var sigInput=h+'.'+p;
            var cryptoAlgo=algo==='HS256'?'SHA-256':algo==='HS384'?'SHA-384':'SHA-512';
            var sig=await _hmacCrypto(cryptoAlgo,secret,sigInput);
            var sigB64=btoa(sig.match(/.{2}/g).map(function(h){return String.fromCharCode(parseInt(h,16));}).join('')).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,'');
            var token=sigInput+'.'+sigB64;
            out.textContent=token; out.dataset.value=token; out.className=out.className.replace('placeholder','').trim();
        } catch(e) {
            out.textContent='Error: '+e.message; out.dataset.value='';
        }
    },

    /* ── JWT Validator ── */
    runJwtVal: function() {
        var token=document.getElementById('jwtval-in').value.trim();
        var secret=document.getElementById('jwtval-secret').value;
        var out=document.getElementById('jwtval-out');
        if(!token) { out.innerHTML='<div class="st-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg>Paste a JWT above to validate it</div>'; return; }
        try {
            var d=ST._jwtDecode(token);
            var issues=[];
            var oks=[];
            if(d.header.typ==='JWT') oks.push('Token type is JWT'); else issues.push('Unexpected typ: '+d.header.typ);
            var alg=d.header.alg||'';
            if(alg.startsWith('HS')||alg.startsWith('RS')||alg.startsWith('ES')) oks.push('Algorithm declared: '+alg); else issues.push('Unknown algorithm: '+alg);
            if(d.payload) {
                oks.push('Payload is valid JSON');
                var now=Math.floor(Date.now()/1000);
                if(d.payload.exp) {
                    if(d.payload.exp>now) oks.push('Token not expired (exp: '+new Date(d.payload.exp*1000).toUTCString()+')');
                    else issues.push('Token is EXPIRED (exp: '+new Date(d.payload.exp*1000).toUTCString()+')');
                }
                if(d.payload.nbf&&d.payload.nbf>now) issues.push('Token not yet valid (nbf: '+new Date(d.payload.nbf*1000).toUTCString()+')');
                if(d.payload.iat) oks.push('Issued at: '+new Date(d.payload.iat*1000).toUTCString());
            } else issues.push('Payload could not be decoded as JSON');
            if(!d.sig) issues.push('Missing signature');
            else oks.push('Signature present');
            var html='';
            var tick='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
            var cross='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
            oks.forEach(function(m){ html+='<div class="st-check-item pass"><span style="color:#22c55e">'+tick+'</span>'+escHtml(m)+'</div>'; });
            issues.forEach(function(m){ html+='<div class="st-check-item"><span style="color:#ef4444">'+cross+'</span>'+escHtml(m)+'</div>'; });
            out.innerHTML='<div class="st-check-list">'+html+'</div>';
        } catch(e) {
            out.innerHTML='<div class="alert alert-danger" style="margin:0">'+escHtml(e.message)+'</div>';
        }
    },

    /* ── JWT Inspector ── */
    runJwtInsp: function() {
        var token=document.getElementById('jwtinsp-in').value.trim();
        var out=document.getElementById('jwtinsp-out');
        if(!token) { out.innerHTML='<div class="st-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Paste a JWT above to inspect it</div>'; return; }
        try {
            var d=ST._jwtDecode(token);
            var meta='<div class="st-output-box"><div class="st-output-head">Token Metadata</div><table class="st-kv-table">';
            var now=Math.floor(Date.now()/1000);
            meta+='<tr><td>Parts</td><td>'+d.parts.length+'</td></tr>';
            meta+='<tr><td>Header size</td><td>'+d.parts[0].length+' chars</td></tr>';
            meta+='<tr><td>Payload size</td><td>'+d.parts[1].length+' chars</td></tr>';
            meta+='<tr><td>Signature size</td><td>'+(d.sig?d.sig.length:0)+' chars</td></tr>';
            if(d.payload&&d.payload.exp) meta+='<tr><td>Expiry</td><td>'+new Date(d.payload.exp*1000).toUTCString()+(d.payload.exp<now?' <span style="color:#ef4444">(EXPIRED)</span>':' <span style="color:#22c55e">(valid)</span>')+'</td></tr>';
            if(d.payload&&d.payload.iat) meta+='<tr><td>Issued at</td><td>'+new Date(d.payload.iat*1000).toUTCString()+'</td></tr>';
            meta+='</table></div>';
            out.innerHTML=meta+ST._jwtSections(d);
        } catch(e) {
            out.innerHTML='<div class="alert alert-danger" style="margin:0">'+escHtml(e.message)+'</div>';
        }
    },

    /* ── JWT Expiry Checker ── */
    runJwtExp: function() {
        var token=document.getElementById('jwtexp-in').value.trim();
        var out=document.getElementById('jwtexp-out');
        if(!token) { out.innerHTML='<div class="st-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Paste a JWT above to check its expiry</div>'; return; }
        try {
            var d=ST._jwtDecode(token);
            if(!d.payload) { out.innerHTML='<div class="alert alert-danger" style="margin:0">Could not decode payload</div>'; return; }
            var now=Math.floor(Date.now()/1000);
            var p=d.payload, html='';
            function timeRow(label, ts, type) {
                var date=new Date(ts*1000);
                var diff=ts-now;
                var absStr=date.toUTCString();
                var relStr=diff>0?'in '+fmtDur(diff):fmtDur(-diff)+' ago';
                var color=type==='exp'?(diff<0?'#ef4444':'#22c55e'):type==='nbf'?(diff>0?'#f59e0b':'#22c55e'):'var(--color-text-muted)';
                return '<tr><td>'+escHtml(label)+'</td><td><span style="color:'+color+'">'+absStr+'</span><br><small style="color:var(--color-text-muted)">'+relStr+'</small></td></tr>';
            }
            html='<div class="st-output-box"><table class="st-kv-table">';
            html+='<tr><td>Current time</td><td>'+new Date(now*1000).toUTCString()+'</td></tr>';
            if(p.iat) html+=timeRow('Issued at (iat)',p.iat,'iat');
            if(p.nbf) html+=timeRow('Not before (nbf)',p.nbf,'nbf');
            if(p.exp) html+=timeRow('Expires at (exp)',p.exp,'exp');
            html+='</table></div>';
            if(!p.exp&&!p.iat&&!p.nbf) html+='<div class="alert alert-info" style="margin-top:10px">This JWT has no exp, iat or nbf claims.</div>';
            if(p.exp) {
                var expired=p.exp<now;
                html+='<div class="alert '+(expired?'alert-danger':'alert-success')+'" style="margin-top:10px;font-weight:600">'+(expired?'Token is EXPIRED':'Token is VALID and not expired')+'</div>';
            }
            out.innerHTML=html;
        } catch(e) {
            out.innerHTML='<div class="alert alert-danger" style="margin:0">'+escHtml(e.message)+'</div>';
        }
    },

    /* ── HMAC Generator ── */
    runHmac: async function() {
        var msg=document.getElementById('hmac-msg').value;
        var key=document.getElementById('hmac-key').value;
        var algo=document.getElementById('hmac-algo').value;
        if(!msg||!key) { ST.setOut('hmac-out',''); return; }
        try {
            var sig=await _hmacCrypto(algo,key,msg);
            ST.setOut('hmac-out',sig.toUpperCase());
        } catch(e) { ST.setOut('hmac-out','Error: '+e.message); }
    },

    /* ── File Hash Calculator ── */
    onFileDrop: function(e) {
        e.preventDefault();
        document.getElementById('filehash-zone').classList.remove('drag-over');
        var file=e.dataTransfer.files[0];
        if(file) ST._hashFile(file);
    },
    onFileSelect: function(inp) {
        var file=inp.files[0];
        if(file) ST._hashFile(file);
    },
    _hashFile: function(file) {
        var zone=document.getElementById('filehash-zone');
        var out=document.getElementById('filehash-out');
        zone.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><strong>Computing hashes…</strong><span>'+escHtml(file.name)+' ('+fmtBytes(file.size)+')</span>';
        out.innerHTML='';
        var reader=new FileReader();
        reader.onload=async function(e) {
            var buf=e.target.result;
            var rows=[['SHA-256','SHA-256'],['SHA-512','SHA-512'],['SHA-1','SHA-1'],['SHA-384','SHA-384']];
            var results={};
            for(var r of rows) results[r[0]]=(await _webcryptoBytes(r[1],buf)).toUpperCase();
            results['MD5']=_md5(new TextDecoder('latin1').decode(buf)).toUpperCase();
            zone.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg><strong>'+escHtml(file.name)+'</strong><span>'+fmtBytes(file.size)+' &middot; Click to select another</span>';
            var html='<div class="st-hash-grid" style="margin-top:12px">';
            for(var alg of ['SHA-256','SHA-512','SHA-384','SHA-1','MD5']) {
                html+='<div class="st-hash-row"><div class="st-hash-row-head"><span class="st-hash-row-label">'+alg+'</span><button class="st-copy-btn" onclick="ST._cpText(this,\''+results[alg]+'\')"><?= $copy_svg ?>Copy</button></div><div class="st-hash-row-val">'+results[alg]+'</div></div>';
            }
            html+='</div>';
            out.innerHTML=html;
        };
        reader.readAsArrayBuffer(file);
    },
    _cpText: function(btn, text) {
        navigator.clipboard.writeText(text).then(function(){
            var old=btn.innerHTML;
            btn.innerHTML=btn.innerHTML.replace(/Copy/,'Copied!');
            btn.classList.add('copied');
            setTimeout(function(){btn.innerHTML=old;btn.classList.remove('copied');},1500);
        });
    },

    /* ── Hash Verifier ── */
    runHashVerify: async function() {
        var input=document.getElementById('hashverify-in').value;
        var expected=document.getElementById('hashverify-expected').value.trim().toLowerCase();
        var algo=document.getElementById('hashverify-algo').value;
        var out=document.getElementById('hashverify-out');
        if(!input||!expected) { out.innerHTML=''; return; }
        try {
            var computed;
            if(algo==='md5') computed=_md5(input).toLowerCase();
            else computed=(await _webcrypto(algo==='sha256'?'SHA-256':algo==='sha512'?'SHA-512':algo==='sha1'?'SHA-1':'SHA-384',input)).toLowerCase();
            var match=computed===expected;
            out.innerHTML='<div class="st-hash-grid" style="margin-top:4px">'
                +'<div class="st-hash-row"><div class="st-hash-row-head"><span class="st-hash-row-label">Computed ('+algo.toUpperCase()+')</span></div><div class="st-hash-row-val">'+computed.toUpperCase()+'</div></div>'
                +'<div class="st-hash-row"><div class="st-hash-row-head"><span class="st-hash-row-label">Expected</span></div><div class="st-hash-row-val">'+escHtml(expected.toUpperCase())+'</div></div>'
                +'</div>'
                +'<div class="alert '+(match?'alert-success':'alert-danger')+'" style="margin-top:10px;font-weight:600">'+(match?'✓ Hashes match — file/text integrity verified':'✗ Hashes do NOT match')+'</div>';
        } catch(e) { out.innerHTML='<div class="alert alert-danger" style="margin:0">'+escHtml(e.message)+'</div>'; }
    },

    /* ── Secure Token Generator ── */
    onTokenTypeChange: function() {
        var type=document.getElementById('tokengen-type').value;
        var isUuid=type==='uuid4'||type==='uuid1';
        var isApiKey=type==='apikey';
        document.getElementById('tokengen-len-wrap').style.display=isUuid?'none':'flex';
        document.getElementById('tokengen-prefix-wrap').style.display=isApiKey?'flex':'none';
        ST.runTokenGen();
    },

    runTokenGen: function() {
        var type=document.getElementById('tokengen-type').value;
        var len=Math.max(4,Math.min(256,parseInt(document.getElementById('tokengen-len').value)||32));
        var qty=Math.max(1,Math.min(20,parseInt(document.getElementById('tokengen-qty').value)||1));
        var caseMode=document.getElementById('tokengen-case').value;
        var prefix=(document.getElementById('tokengen-prefix').value||'sk').replace(/[^a-zA-Z0-9_\-]/g,'');
        var tokens=[], meta='';
        for(var i=0;i<qty;i++) tokens.push(ST._genToken(type,len,prefix,caseMode));
        var joined=tokens.join('\n');
        var el=document.getElementById('tokengen-out');
        el.textContent=joined; el.dataset.value=joined;
        el.className=el.className.replace('placeholder','').trim();
        var bits=type==='uuid4'?122:type==='uuid1'?48:type==='hex'?len*4:type==='base64'||type==='base64url'||type==='apikey'?len*8:type==='numeric'?Math.floor(len*Math.log2(10)):type==='alphanumeric'?Math.floor(len*Math.log2(62)):0;
        var metaEl=document.getElementById('tokengen-meta');
        metaEl.innerHTML='<span>~'+bits+' bits of entropy per token</span>'+(qty>1?'<span>'+qty+' tokens generated</span>':'')+'<span>Powered by Web Crypto CSPRNG</span>';
    },

    _genToken: function(type, len, prefix, caseMode) {
        function randBytes(n) { var a=new Uint8Array(n); crypto.getRandomValues(a); return a; }
        function toHex(arr) { return Array.from(arr).map(function(b){return b.toString(16).padStart(2,'0');}).join(''); }
        function applyCase(s) { return caseMode==='upper'?s.toUpperCase():caseMode==='lower'?s.toLowerCase():s; }
        switch(type) {
            case 'uuid4': {
                var b=randBytes(16);
                b[6]=(b[6]&0x0f)|0x40; b[8]=(b[8]&0x3f)|0x80;
                var h=toHex(b);
                var uuid=h.slice(0,8)+'-'+h.slice(8,12)+'-'+h.slice(12,16)+'-'+h.slice(16,20)+'-'+h.slice(20,32);
                return applyCase(uuid);
            }
            case 'uuid1': {
                var now=BigInt(Date.now());
                var t=(now*10000n+122192928000000000n)&0xFFFFFFFFFFFFFFFFn;
                var tLow=Number(t&0xFFFFFFFFn), tMid=Number((t>>32n)&0xFFFFn), tHi=Number((t>>48n)&0x0FFFn)|0x1000;
                var clockSeq=randBytes(2); clockSeq[0]=(clockSeq[0]&0x3f)|0x80;
                var node=randBytes(6); node[0]|=0x01;
                var h2=tLow.toString(16).padStart(8,'0')+'-'+tMid.toString(16).padStart(4,'0')+'-'+tHi.toString(16).padStart(4,'0')+'-'+toHex(clockSeq)+'-'+toHex(node);
                return applyCase(h2);
            }
            case 'hex': {
                return applyCase(toHex(randBytes(Math.ceil(len/2))).slice(0,len));
            }
            case 'base64': {
                var raw=randBytes(Math.ceil(len*3/4));
                return applyCase(btoa(String.fromCharCode.apply(null,raw)).slice(0,len));
            }
            case 'base64url': {
                var raw2=randBytes(Math.ceil(len*3/4));
                return applyCase(btoa(String.fromCharCode.apply(null,raw2)).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,'').slice(0,len));
            }
            case 'alphanumeric': {
                var chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                var idxs=randBytes(len*2), out='';
                for(var j=0;j<idxs.length&&out.length<len;j++) { var c=idxs[j]; if(c<62*4) out+=chars[c%62]; }
                while(out.length<len) { var ex=randBytes(4); ex.forEach(function(b){if(b<62*4&&out.length<len) out+=chars[b%62];}); }
                return applyCase(out.slice(0,len));
            }
            case 'numeric': {
                var digits='0123456789';
                var nb=randBytes(len*2), nd='';
                for(var k=0;k<nb.length&&nd.length<len;k++) { if(nb[k]<250) nd+=digits[nb[k]%10]; }
                return nd.slice(0,len);
            }
            case 'apikey': {
                var keyBytes=randBytes(Math.ceil(len*3/4));
                var keyB64=btoa(String.fromCharCode.apply(null,keyBytes)).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,'').slice(0,len);
                return (prefix||'sk')+'_'+applyCase(keyB64);
            }
            default: return '';
        }
    },
};

/* ─── Utilities ─────────────────────────────────────────────────────── */
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmtBytes(b) {
    if(b<1024) return b+' B';
    if(b<1048576) return (b/1024).toFixed(1)+' KB';
    return (b/1048576).toFixed(2)+' MB';
}
function fmtDur(s) {
    if(s<60) return s+'s';
    if(s<3600) return Math.floor(s/60)+'m '+s%60+'s';
    if(s<86400) return Math.floor(s/3600)+'h '+Math.floor((s%3600)/60)+'m';
    return Math.floor(s/86400)+'d '+Math.floor((s%86400)/3600)+'h';
}

/* ─── Init ──────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    ST.switchTool('md5');
    ST.runPwGen();
    ST.runTokenGen();
});
</script>
<?php
$content = ob_get_clean();
plugin_render('Security Toolkit', $content, [
    'description' => '21 free browser-based security utilities — hash generators (MD5, SHA-1/224/256/384/512, CRC32, RIPEMD-160, Whirlpool), password tools, JWT encoder/decoder, HMAC generator, file hash calculator and hash verifier.',
    'canonical'   => 'https://awantools.site/plugins/security-toolkit/',
]);
