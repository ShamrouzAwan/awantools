<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../_bootstrap.php';
require_once __DIR__ . '/../../plugins/_sdk.php';
require_once AWAN_ROOT . '/_core/Plugin.php';

$slug      = 'github-toolkit';
$_manifest = plugin_manifest($slug);
$_meta     = $_manifest['meta'] ?? [];
plugin_track('plugin_view', '/plugins/github-toolkit/', ['plugin_slug' => $slug]);

$related_html = plugin_related_html($slug) ?? '';

/* ── SVG Icons ──────────────────────────────────────────────────── */
$IC = [
    'github' => '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0 1 12 6.844a9.59 9.59 0 0 1 2.504.337c1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.02 10.02 0 0 0 22 12.017C22 6.484 17.522 2 12 2z"/></svg>',
    'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
    'key'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>',
    'save'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>',
    'star'   => '<svg viewBox="0 0 24 24" fill="currentColor" width="11" height="11"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
    'x'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
];

/* ── Tab definitions ─────────────────────────────────────────────── */
$tabs = [
    ['id'=>'overview',     'label'=>'Overview',     'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>'],
    ['id'=>'files',        'label'=>'Files Tree',        'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>'],
    ['id'=>'downloads',    'label'=>'Downloader',    'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>'],
    ['id'=>'branches',     'label'=>'Branches',     'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/></svg>'],
    ['id'=>'releases',     'label'=>'Releases',     'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>'],
    ['id'=>'readme',       'label'=>'README',       'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>'],
    ['id'=>'contributors', 'label'=>'Contributors', 'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
    ['id'=>'commits',      'label'=>'Commits',      'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><circle cx="12" cy="12" r="4"/><line x1="1.05" y1="12" x2="7" y2="12"/><line x1="17.01" y1="12" x2="22.96" y2="12"/></svg>'],
    ['id'=>'languages',    'label'=>'Languages',    'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>'],
    ['id'=>'dependencies', 'label'=>'Dependencies', 'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>'],
    ['id'=>'analytics',    'label'=>'Analytics',    'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>'],
    ['id'=>'badges',       'label'=>'Badges',       'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>'],
    ['id'=>'widgets',      'label'=>'Widgets',       'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>'],
    ['id'=>'api',          'label'=>'API',           'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>'],
    ['id'=>'seo',          'label'=>'SEO',           'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>'],
    ['id'=>'compare',      'label'=>'Compare',       'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><line x1="18" y1="20" x2="18" y2="10"/><line x1="6" y1="20" x2="6" y2="10"/><path d="M3 7l9-5 9 5"/></svg>'],
    ['id'=>'tools',        'label'=>'Tools',         'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>'],
];

/* ── Popular repos list ──────────────────────────────────────────── */
$popular_repos = [
    'facebook/react', 'vuejs/vue', 'laravel/laravel', 'django/django',
    'microsoft/vscode', 'torvalds/linux', 'openai/openai-python', 'tensorflow/tensorflow',
];

ob_start();
?>
<div class="gh-wrap">

    <!-- ═══════════════════════════════════════════════════════
         HERO SECTION
    ════════════════════════════════════════════════════════ -->
    <div class="gh-hero">
        <div class="gh-hero-title">
            <?= $IC['github'] ?>
            GitHub Toolkit
        </div>
        <div class="gh-hero-subtitle">
            Analyze any repository — download, explore branches, releases, files, README, contributors,
            commits, language stats, badges, widgets, SEO tags, and more. All browser-based, no sign-up required.
        </div>

        <!-- Main URL input -->
        <div class="gh-hero-form">
            <input
                type="url"
                id="gh-main-url"
                class="gh-hero-url"
                placeholder="https://github.com/laravel/laravel"
                autocomplete="off"
                spellcheck="false"
                onkeydown="if(event.key==='Enter')GH.analyze()"
            >
            <button id="gh-analyze-btn" class="gh-analyze-btn" onclick="GH.analyze()">
                <?= $IC['search'] ?>
                Analyze Repository
            </button>
        </div>

        <!-- Example links -->
        <div class="gh-hero-examples">
            <span>Try:</span>
            <span class="gh-example-link" onclick="GH.useExample('https://github.com/facebook/react')">facebook/react</span>
            <span class="gh-example-link" onclick="GH.useExample('https://github.com/laravel/laravel')">laravel/laravel</span>
            <span class="gh-example-link" onclick="GH.useExample('https://github.com/microsoft/vscode')">microsoft/vscode</span>
            <span class="gh-example-link" onclick="GH.useExample('https://github.com/openai/openai-python')">openai/openai-python</span>
            <span class="gh-example-link" onclick="GH.useExample('https://github.com/vuejs/vue')">vuejs/vue</span>
        </div>

        <div id="gh-hero-status" class="gh-hero-status"></div>

        <!-- Recent searches · Favorites · Popular -->
        <div class="gh-hero-footer">
            <div class="gh-hero-footer-col" id="gh-hero-recents">
                <div class="gh-hero-section-title">Recent Searches</div>
                <div class="gh-text-muted" style="font-size:12px">None yet</div>
            </div>
            <div class="gh-hero-footer-col" id="gh-hero-favs">
                <div class="gh-hero-section-title">Favorites</div>
                <div class="gh-text-muted" style="font-size:12px">Star a repo to save it</div>
            </div>
            <div class="gh-hero-footer-col">
                <div class="gh-hero-section-title">Popular</div>
                <div class="gh-popular-grid">
                    <?php foreach ($popular_repos as $pr): ?>
                    <span class="gh-popular-item" onclick="GH.useExample('https://github.com/<?= htmlspecialchars($pr, ENT_QUOTES) ?>')">
                        <?= $IC['star'] ?> <?= htmlspecialchars($pr, ENT_QUOTES) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div><!-- /.gh-hero -->

    <!-- ═══════════════════════════════════════════════════════
         PAT BAR
    ════════════════════════════════════════════════════════ -->
    <div class="gh-pat-bar">
        <?= $IC['key'] ?>
        <span class="gh-pat-label">GitHub Token <span class="gh-pat-label-hint">(optional — 5,000 req/hr vs 60)</span></span>
        <span id="gh-pat-dot" class="gh-pat-dot" title="Token active" style="display:none">●</span>
        <div class="gh-pat-field">
            <input
                type="password"
                id="gh-pat-input"
                class="gh-pat-input"
                placeholder="ghp_xxxxxxxxxxxxxxxxxxxx"
                onkeydown="if(event.key==='Enter')GH.savePat()"
                autocomplete="off"
                spellcheck="false"
            >
            <button id="gh-pat-toggle" class="gh-pat-icon-btn" type="button"
                    onclick="GH.togglePatVisibility()" title="Show token">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
        </div>
        <button class="gh-btn gh-btn-sm gh-btn-primary" onclick="GH.savePat()">
            <?= $IC['save'] ?> Save
        </button>
        <button id="gh-pat-clear-btn" class="gh-btn gh-btn-sm gh-btn-danger" onclick="GH.clearPat()" title="Remove saved token" style="display:none">
            <?= $IC['x'] ?? '✕' ?> Clear
        </button>
        <span id="gh-rate-badge" class="gh-rate-badge">checking…</span>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         RESULTS (hidden until analyze)
    ════════════════════════════════════════════════════════ -->
    <div id="gh-results">

        <!-- Repo Header -->
        <div class="gh-repo-header" id="gh-repo-header">
            <div id="gh-repo-header-inner">
                <!-- filled by JS renderRepoHeader() -->
            </div>
        </div>

        <!-- Main layout: full-width tabs -->
        <div class="gh-results-layout">

            <!-- ── Main content ────────────────────────────── -->
            <div class="gh-results-main gh-results-full">

                <!-- Tab Navigation -->
                <div class="gh-tab-nav" id="gh-tab-nav">
                    <?php foreach ($tabs as $tab): ?>
                    <button
                        class="gh-tab-btn<?= $tab['id'] === 'overview' ? ' active' : '' ?>"
                        data-tab="<?= htmlspecialchars($tab['id'], ENT_QUOTES) ?>"
                        onclick="GH.switchTab('<?= htmlspecialchars($tab['id'], ENT_QUOTES) ?>')"
                    >
                        <?= $tab['icon'] ?>
                        <?= htmlspecialchars($tab['label'], ENT_QUOTES) ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- Tab Panels -->
                <div class="gh-tab-content">

                    <!-- Overview -->
                    <div id="gh-tab-overview" class="gh-tab-panel active">
                        <div class="gh-tab-inner" id="tab-overview-content">
                            <div class="gh-empty-tab">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                <p>Analyze a repository above to see the overview.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Files -->
                    <div id="gh-tab-files" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-files-content"></div>
                    </div>

                    <!-- Downloads -->
                    <div id="gh-tab-downloads" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-downloads-content"></div>
                    </div>

                    <!-- Branches -->
                    <div id="gh-tab-branches" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-branches-content"></div>
                    </div>

                    <!-- Releases -->
                    <div id="gh-tab-releases" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-releases-content"></div>
                    </div>

                    <!-- README -->
                    <div id="gh-tab-readme" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-readme-content"></div>
                    </div>

                    <!-- Contributors -->
                    <div id="gh-tab-contributors" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-contributors-content"></div>
                    </div>

                    <!-- Commits -->
                    <div id="gh-tab-commits" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-commits-content"></div>
                    </div>

                    <!-- Languages -->
                    <div id="gh-tab-languages" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-languages-content"></div>
                    </div>

                    <!-- Dependencies -->
                    <div id="gh-tab-dependencies" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-dependencies-content"></div>
                    </div>

                    <!-- Analytics -->
                    <div id="gh-tab-analytics" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-analytics-content"></div>
                    </div>

                    <!-- Badges -->
                    <div id="gh-tab-badges" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-badges-content"></div>
                    </div>

                    <!-- Widgets -->
                    <div id="gh-tab-widgets" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-widgets-content"></div>
                    </div>

                    <!-- API -->
                    <div id="gh-tab-api" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-api-content"></div>
                    </div>

                    <!-- SEO -->
                    <div id="gh-tab-seo" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-seo-content"></div>
                    </div>

                    <!-- Compare -->
                    <div id="gh-tab-compare" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-compare-content"></div>
                    </div>

                    <!-- Tools -->
                    <div id="gh-tab-tools" class="gh-tab-panel">
                        <div class="gh-tab-inner" id="tab-tools-content"></div>
                    </div>

                </div><!-- /.gh-tab-content -->
            </div><!-- /.gh-results-main -->


        </div><!-- /.gh-results-layout -->
    </div><!-- /#gh-results -->

</div><!-- /.gh-wrap -->
<?php
$body = ob_get_clean();

$css = file_get_contents(__DIR__ . '/assets/gh-tools.css');
$js  = file_get_contents(__DIR__ . '/assets/gh-tools.js');

// Assemble content: inline CSS, then body, then related tools, then inline JS
$content = '<style>' . $css . '</style>'
         . $body
         . $related_html
         . '<script>' . $js . '</script>';

plugin_render(
    $_meta['title']       ?? 'GitHub Toolkit — 36 Free GitHub Developer Tools',
    $content,
    [
        'description' => $_meta['description'] ?? 'Download repos, files, folders, releases and gists from GitHub. Analyze repositories and profiles. Generate badges, shields, and embeds. 100% browser-based, no sign-up required.',
        'canonical'   => $_meta['canonical']   ?? '',
    ]
);
