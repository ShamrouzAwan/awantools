<?php
defined('AWAN') or die();

if ($settings->get('analytics_enabled', '1') === '1' && !isBot()) {
    try {
        $db->insert('analytics_events', [
            'event'      => 'page_view',
            'path'       => '/plugins',
            'user_id'    => $auth->id(),
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (Exception $e) {}
}

$activePlugins = $db->fetchAll(
    "SELECT id, slug, name, version, description, manifest, offered FROM plugins WHERE status = 'active' ORDER BY name ASC"
) ?: [];

// Total tools offered across all active plugins
$totalOfferedRow = $db->fetch("SELECT COALESCE(SUM(offered),0) AS n FROM plugins WHERE status = 'active'");
$totalOffered    = (int)($totalOfferedRow['n'] ?? 0);

// Extract unique categories from manifest JSON (supports both 'categories' array and legacy 'category' string)
$allCategories = [];
foreach ($activePlugins as $plugin) {
    $manifest = json_decode($plugin['manifest'] ?? '{}', true) ?? [];
    $cats = $manifest['categories'] ?? (isset($manifest['category']) && $manifest['category'] ? [$manifest['category']] : []);
    foreach ($cats as $cat) {
        $cat = trim($cat);
        if ($cat && !in_array($cat, $allCategories, true)) {
            $allCategories[] = $cat;
        }
    }
}
sort($allCategories);

// User favourites for heart button state
$userFavIds = [];
if ($auth->check()) {
    try {
        $favRows    = $db->fetchAll("SELECT plugin_id FROM user_favourites WHERE user_id = ?", [$auth->id()]) ?: [];
        $userFavIds = array_map('intval', array_column($favRows, 'plugin_id'));
    } catch (Exception $e) {}
}
$_favCsrf = Security::csrfToken();

// Fetch plugin ratings (avg + count per plugin)
$_pluginRatings = [];
try {
    $ratingRows = $db->fetchAll(
        "SELECT plugin_id, ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS rating_count FROM plugin_ratings GROUP BY plugin_id"
    ) ?: [];
    foreach ($ratingRows as $rr) {
        $_pluginRatings[(int)$rr['plugin_id']] = $rr;
    }
} catch (Throwable $_e) {}

// User's own ratings
$_myRatings  = [];
$_rateCsrf   = Security::csrfToken();
if ($auth->check()) {
    try {
        $myRateRows = $db->fetchAll("SELECT plugin_id, rating FROM plugin_ratings WHERE user_id = ?", [$auth->id()]) ?: [];
        foreach ($myRateRows as $mr) { $_myRatings[(int)$mr['plugin_id']] = (int)$mr['rating']; }
    } catch (Throwable $_e) {}
}

// Map first category to an accent colour (used for card top-band)
function _plg_accent_color(array $categories): string {
    $map = [
        'security'       => '#f43f5e',
        'network'        => '#f97316',
        'design'         => '#d946ef',
        'ide'            => '#ec4899',
        'editor'         => '#ec4899',
        'frontend'       => '#ec4899',
        'github'         => '#475569',
        'generators'     => '#8b5cf6',
        'converters'     => '#10b981',
        'formatters'     => '#14b8a6',
        'text processing'=> '#f59e0b',
        'downloaders'    => '#06b6d4',
        'analytics'      => '#06b6d4',
        'utilities'      => '#0ea5e9',
        'developer tools'=> '#6366f1',
    ];
    foreach ($categories as $cat) {
        $key = strtolower(trim($cat));
        if (isset($map[$key])) return $map[$key];
    }
    return '#6366f1';
}

ob_start();
?>

<!-- ══ PLUGIN PAGE STYLES ══════════════════════════════════════════════ -->
<style>
/* ── Hero ────────────────────────────────────────────────────────────── */
.plg-hero {
    background: linear-gradient(160deg, var(--color-primary-light,#eef2ff) 0%, var(--color-background,#f8fafc) 60%);
    border-bottom: 1px solid var(--color-border,#e2e8f0);
    padding: 56px 0 44px;
}
.plg-hero-inner { max-width: 640px; }
.plg-hero-title {
    font-size: clamp(28px,5vw,42px);
    font-weight: 800;
    letter-spacing: -0.04em;
    line-height: 1.15;
    color: var(--color-text,#0f172a);
    margin: 10px 0 14px;
}
.plg-hero-sub {
    font-size: 15px;
    color: var(--color-text-secondary,#64748b);
    margin-bottom: 28px;
    line-height: 1.6;
}
/* Search */
.plg-search-wrap {
    position: relative;
    display: flex;
    align-items: center;
    background: var(--color-card,#fff);
    border: 1.5px solid var(--color-border,#e2e8f0);
    border-radius: 12px;
    padding: 0 14px;
    height: 50px;
    max-width: 560px;
    gap: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    transition: border-color .18s, box-shadow .18s;
}
.plg-search-wrap:focus-within {
    border-color: var(--color-primary,#6366f1);
    box-shadow: 0 0 0 3px rgba(99,102,241,.12), 0 2px 12px rgba(0,0,0,.06);
}
.plg-search-wrap input {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    font-size: 15px;
    color: var(--color-text,#0f172a);
}
.plg-search-wrap input::placeholder { color: var(--color-text-muted,#94a3b8); }
.plg-search-clear {
    background: none; border: none; cursor: pointer;
    color: var(--color-text-muted,#94a3b8);
    font-size: 18px; line-height: 1; padding: 0;
    display: none; align-items: center; justify-content: center;
    width: 20px; height: 20px; flex-shrink: 0;
    transition: color .15s;
}
.plg-search-clear:hover { color: var(--color-text,#0f172a); }
.plg-search-clear.visible { display: flex; }
/* Hero stats */
.plg-hero-stats {
    display: flex;
    align-items: center;
    gap: 0;
    margin-top: 22px;
    flex-wrap: wrap;
}
.plg-stat {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    color: var(--color-text-secondary,#64748b);
    padding: 0 16px 0 0;
}
.plg-stat:first-child { padding-left: 0; }
.plg-stat strong {
    font-size: 18px;
    font-weight: 800;
    color: var(--color-primary,#6366f1);
    letter-spacing: -0.04em;
}
.plg-stat-divider {
    width: 1px; height: 22px;
    background: var(--color-border,#e2e8f0);
    margin-right: 16px;
    flex-shrink: 0;
}

/* ── Controls Bar ─────────────────────────────────────────────────────── */
.plg-controls-outer {
    position: sticky;
    top: 0;
    z-index: 80;
    background: var(--color-card,#fff);
    border-bottom: 1px solid var(--color-border,#e2e8f0);
    box-shadow: 0 1px 8px rgba(0,0,0,.05);
}
.plg-controls {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    min-height: 56px;
}
/* Category scroll */
.plg-cat-scroll {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
    overflow-x: auto;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 2px;
}
.plg-cat-scroll::-webkit-scrollbar { display: none; }
.plg-cat-btn {
    flex-shrink: 0;
    font-size: 12px;
    font-weight: 600;
    padding: 5px 13px;
    border-radius: 20px;
    border: 1px solid var(--color-border,#e2e8f0);
    background: transparent;
    color: var(--color-text-secondary,#64748b);
    cursor: pointer;
    transition: background .15s, color .15s, border-color .15s;
    white-space: nowrap;
}
.plg-cat-btn:hover {
    background: var(--color-background,#f1f5f9);
    color: var(--color-text,#0f172a);
}
.plg-cat-btn.plg-cat-active {
    background: var(--color-primary,#6366f1);
    color: #fff;
    border-color: var(--color-primary,#6366f1);
}
/* Right controls */
.plg-controls-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.plg-sort-select {
    font-size: 12px;
    font-weight: 600;
    padding: 5px 10px;
    border-radius: 8px;
    border: 1px solid var(--color-border,#e2e8f0);
    background: var(--color-card,#fff);
    color: var(--color-text,#0f172a);
    cursor: pointer;
    outline: none;
    transition: border-color .15s;
}
.plg-sort-select:focus { border-color: var(--color-primary,#6366f1); }
.plg-view-toggle {
    display: flex;
    border: 1px solid var(--color-border,#e2e8f0);
    border-radius: 8px;
    overflow: hidden;
}
.plg-view-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px 9px;
    color: var(--color-text-muted,#94a3b8);
    line-height: 1;
    transition: background .15s, color .15s;
    display: flex; align-items: center; justify-content: center;
}
.plg-view-btn:hover { background: var(--color-background,#f1f5f9); color: var(--color-text,#0f172a); }
.plg-view-btn.active { background: var(--color-primary,#6366f1); color: #fff; }
.plg-result-count {
    font-size: 12px;
    color: var(--color-text-muted,#94a3b8);
    white-space: nowrap;
    min-width: 60px;
    text-align: right;
}

/* ── Grid ─────────────────────────────────────────────────────────────── */
@keyframes pcFadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}
.plg-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 20px;
    align-items: stretch;
}

/* ── Card (Grid View) ────────────────────────────────────────────────── */
.pc-card {
    display: flex;
    flex-direction: column;
    background: var(--color-card,#fff);
    border: 1px solid var(--color-border,#e2e8f0);
    border-radius: 14px;
    text-decoration: none;
    color: inherit;
    overflow: hidden;
    position: relative;
    transition:
        transform .22s cubic-bezier(.34,1.36,.64,1),
        box-shadow .22s ease,
        border-color .18s;
    animation: pcFadeUp .36s ease both;
    animation-delay: calc(var(--pc-i,0) * 35ms);
    will-change: transform;
}
.pc-card:hover {
    transform: translateY(-5px);
    border-color: var(--color-primary,#6366f1);
    box-shadow: 0 10px 32px rgba(99,102,241,.14), 0 2px 8px rgba(0,0,0,.06);
}
.pc-card:active {
    transform: translateY(-1px) scale(0.985);
    transition-duration: .08s;
}
/* Accent top band */
.pc-accent-band {
    height: 4px;
    background: var(--pc-accent, var(--color-primary,#6366f1));
    flex-shrink: 0;
    transition: height .22s ease;
}
.pc-card:hover .pc-accent-band { height: 6px; }
/* Head */
.pc-card-head {
    padding: 18px 18px 0;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
}
.pc-icon {
    width: 52px; height: 52px;
    border-radius: 12px;
    background: rgba(99,102,241,0.12);
    background: color-mix(in srgb, var(--pc-accent,#6366f1) 12%, transparent);
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    color: var(--pc-accent, var(--color-primary,#6366f1));
    flex-shrink: 0;
    transition: background .2s, color .2s, transform .22s cubic-bezier(.34,1.56,.64,1);
}
.pc-card:hover .pc-icon {
    background: var(--pc-accent, var(--color-primary,#6366f1));
    color: #fff;
    transform: scale(1.1) rotate(-4deg);
}
.pc-fav {
    background: none; border: none; cursor: pointer;
    padding: 5px; color: var(--color-text-muted,#94a3b8);
    border-radius: 7px; line-height: 1; flex-shrink: 0;
    transition: color .15s, background .15s;
}
.pc-fav:hover  { background: var(--color-background,#f1f5f9); color: #ef4444; }
.pc-fav.is-fav { color: #ef4444; }
/* Body */
.pc-card-body {
    padding: 14px 18px 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.pc-name {
    font-size: 15px; font-weight: 700;
    color: var(--color-text,#0f172a);
    letter-spacing: -0.2px; line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.pc-cats { display: flex; flex-wrap: wrap; gap: 4px; }
.pc-cat {
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.05em;
    padding: 2px 8px; border-radius: 20px;
    background: rgba(99,102,241,0.10);
    background: color-mix(in srgb, var(--pc-accent,#6366f1) 10%, transparent);
    color: var(--pc-accent, var(--color-primary,#6366f1));
    border: 1px solid rgba(99,102,241,0.20);
    border: 1px solid color-mix(in srgb, var(--pc-accent,#6366f1) 20%, transparent);
    transition: background .15s, color .15s, border-color .15s;
}
.pc-desc {
    font-size: 12.5px;
    color: var(--color-text-secondary,#64748b);
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
}
/* Tags */
.pc-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 2px;
}
.pc-tag {
    font-size: 10px;
    padding: 2px 7px;
    border-radius: 4px;
    background: var(--color-background,#f1f5f9);
    color: var(--color-text-muted,#94a3b8);
    border: 1px solid var(--color-border,#e2e8f0);
    font-family: ui-monospace, monospace;
    letter-spacing: 0;
}
/* Footer */
.pc-card-foot {
    margin-top: 14px;
    padding: 10px 18px 14px;
    border-top: 1px solid var(--color-border,#e2e8f0);
    display: flex; align-items: center;
    justify-content: space-between; gap: 8px;
    transition: border-color .18s;
}
.pc-card:hover .pc-card-foot { border-color: rgba(99,102,241,0.20); border-color: color-mix(in srgb, var(--pc-accent,#6366f1) 20%, transparent); }
.pc-meta { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; min-width: 0; }
.pc-tools-badge {
    font-size: 11px; font-weight: 700;
    color: var(--pc-accent,var(--color-primary,#6366f1));
    background: rgba(99,102,241,0.10);
    background: color-mix(in srgb, var(--pc-accent,#6366f1) 10%, transparent);
    padding: 3px 8px; border-radius: 20px;
    white-space: nowrap;
}
.pc-rating {
    font-size: 11px; color: #f59e0b;
    display: flex; align-items: center; gap: 2px;
    white-space: nowrap;
}
.pc-rating-count { color: var(--color-text-muted,#94a3b8); margin-left: 2px; font-size: 10px; }
.pc-ver { font-size: 10px; color: var(--color-text-muted,#94a3b8); }
.pc-login-badge {
    font-size: 10px; font-weight: 600;
    padding: 2px 7px; border-radius: 20px;
    background: #fef3c7; color: #92400e;
}
.pc-open {
    font-size: 12px; font-weight: 700;
    color: var(--pc-accent,var(--color-primary,#6366f1));
    white-space: nowrap;
    display: flex; align-items: center; gap: 3px;
    opacity: .6;
    transition: opacity .15s, gap .18s;
}
.pc-card:hover .pc-open { opacity: 1; gap: 7px; }

/* ── List View ────────────────────────────────────────────────────────── */
.plg-grid.list-view {
    grid-template-columns: 1fr;
    gap: 10px;
}
.plg-grid.list-view .pc-card {
    flex-direction: row;
    align-items: stretch;
    min-height: unset;
    border-radius: 12px;
}
.plg-grid.list-view .pc-accent-band {
    width: 4px;
    height: auto;
    flex-shrink: 0;
}
.plg-grid.list-view .pc-card:hover .pc-accent-band { width: 6px; height: auto; }
.plg-grid.list-view .pc-card-head {
    padding: 16px 14px 16px 18px;
    align-items: center;
    flex-shrink: 0;
}
.plg-grid.list-view .pc-icon {
    width: 44px; height: 44px;
    font-size: 18px;
    border-radius: 10px;
}
.plg-grid.list-view .pc-card-body {
    padding: 16px 0;
    flex: 1;
    gap: 4px;
    min-width: 0;
}
.plg-grid.list-view .pc-name { font-size: 14px; }
.plg-grid.list-view .pc-desc {
    -webkit-line-clamp: 1;
    font-size: 12px;
}
.plg-grid.list-view .pc-tags { display: none; }
.plg-grid.list-view .pc-card-foot {
    margin-top: 0;
    padding: 16px 18px;
    border-top: none;
    border-left: 1px solid var(--color-border,#e2e8f0);
    flex-shrink: 0;
    flex-direction: column;
    align-items: flex-end;
    justify-content: center;
    gap: 6px;
    min-width: 120px;
}
.plg-grid.list-view .pc-card:hover .pc-card-foot {
    border-left-color: rgba(99,102,241,0.20);
    border-left-color: color-mix(in srgb, var(--pc-accent,#6366f1) 20%, transparent);
}

/* ── CTA Box ─────────────────────────────────────────────────────────── */
.plg-cta-box {
    margin-top: 48px;
    border-radius: 16px;
    background: linear-gradient(135deg, var(--color-primary-light,#eef2ff) 0%, var(--color-background,#f8fafc) 100%);
    border: 1px solid rgba(99,102,241,0.25);
    border: 1px solid color-mix(in srgb, var(--color-primary,#6366f1) 25%, transparent);
    padding: 40px 32px;
    text-align: center;
}
.plg-cta-box h3 { font-size: 20px; font-weight: 800; margin-bottom: 8px; color: var(--color-text,#0f172a); }
.plg-cta-box p  { color: var(--color-text-secondary,#64748b); margin-bottom: 22px; font-size: 14px; }
.plg-cta-actions { display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap; }

/* ── Empty state ─────────────────────────────────────────────────────── */
.plg-empty {
    display: none;
    text-align: center;
    padding: 64px 24px;
    color: var(--color-text-muted,#94a3b8);
}
.plg-empty svg { opacity: .35; margin-bottom: 16px; }
.plg-empty h4 { font-size: 16px; font-weight: 700; color: var(--color-text-secondary,#64748b); margin-bottom: 6px; }
.plg-empty p  { font-size: 13px; margin-bottom: 16px; }

/* ── Dark mode tweaks ───────────────────────────────────────────────── */
[data-theme="dark"] .plg-hero { background: linear-gradient(160deg, rgba(99,102,241,0.12) 0%, var(--color-background,#0f172a) 60%); background: linear-gradient(160deg, color-mix(in srgb, var(--color-primary,#6366f1) 12%, transparent) 0%, var(--color-background,#0f172a) 60%); }
[data-theme="dark"] .pc-card:hover { box-shadow: 0 10px 32px rgba(99,102,241,.25), 0 2px 8px rgba(0,0,0,.4); }
[data-theme="dark"] .plg-search-wrap { background: var(--color-surface,#1e293b); }
[data-theme="dark"] .plg-controls-outer { background: var(--color-surface,#1e293b); }
[data-theme="dark"] .plg-sort-select { background: var(--color-surface,#1e293b); }

/* ── Responsive ──────────────────────────────────────────────────────── */
@media (max-width: 640px) {
    .plg-hero { padding: 36px 0 32px; }
    .plg-hero-title { font-size: 26px; }
    .plg-result-count { display: none; }
    .plg-grid { grid-template-columns: 1fr; }
    .plg-grid.list-view .pc-card-foot { min-width: 90px; padding: 12px 14px; }
    .plg-cta-box { padding: 28px 20px; }
}
</style>

<?php if (empty($activePlugins)): ?>
<!-- Empty state (no plugins installed) -->
<div class="front-container" style="padding:80px 24px;text-align:center">
    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" style="opacity:.3;margin-bottom:16px;color:var(--color-text-muted)"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    <h3 style="font-size:18px;font-weight:700;margin-bottom:8px">No Tools Available Yet</h3>
    <p style="color:var(--color-text-secondary);margin-bottom:20px">New tools will appear here as they are made available. Check back soon!</p>
    <?php if (!$auth->check()): ?>
    <a href="/register" class="btn btn-primary">Create a Free Account</a>
    <?php endif ?>
</div>
<?php else: ?>

<!-- ══ HERO ═══════════════════════════════════════════════════════════ -->
<div class="plg-hero">
    <div class="front-container">
        <div class="plg-hero-inner">
            <div class="section-eyebrow">Free Tools Library</div>
            <h1 class="plg-hero-title">Hundred of Tools,<br>All in One Place</h1>
            <p class="plg-hero-sub">All tools are free to use. One account gives you access to everything — no subscriptions, no ads.</p>

            <!-- Hero search -->
            <div class="plg-search-wrap">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink:0;color:var(--color-text-muted)"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="plugin-search"
                       placeholder="Search tools by name, keyword or category…"
                       oninput="liveFilter()" autocomplete="off" spellcheck="false">
                <button class="plg-search-clear" id="plg-search-clear" onclick="clearSearch()" aria-label="Clear search" tabindex="-1">&#x2715;</button>
            </div>

            <!-- Stats -->
            <?php if ($totalOffered > 0): ?>
            <div class="plg-hero-stats">
                <div class="plg-stat"><strong><?= number_format($totalOffered) ?>+</strong>&nbsp;Tools</div>
                <div class="plg-stat-divider"></div>
                <div class="plg-stat"><strong><?= count($activePlugins) ?></strong>&nbsp;Plugins</div>
                <div class="plg-stat-divider"></div>
                <div class="plg-stat"><strong><?= count($allCategories) ?></strong>&nbsp;Categories</div>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>

<!-- ══ CONTROLS BAR ═══════════════════════════════════════════════════ -->
<div class="plg-controls-outer">
    <div class="front-container">
        <div class="plg-controls">
            <!-- Category pills -->
            <div class="plg-cat-scroll" id="plg-cat-scroll">
                <button class="plg-cat-btn plg-cat-active" data-cat="all" onclick="setCat('all',this)">All <span id="plg-cat-all-count" style="opacity:.6;font-weight:400">(<?= count($activePlugins) ?>)</span></button>
                <?php foreach ($allCategories as $cat):
                    // Count plugins in this category
                    $catCount = 0;
                    foreach ($activePlugins as $_p) {
                        $_m = json_decode($_p['manifest'] ?? '{}', true) ?? [];
                        $_cats = $_m['categories'] ?? (isset($_m['category']) ? [$_m['category']] : []);
                        if (in_array($cat, $_cats, true)) $catCount++;
                    }
                ?>
                <button class="plg-cat-btn" data-cat="<?= e($cat) ?>"
                        onclick="setCat(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES) ?>,this)"><?= e($cat) ?> <span style="opacity:.55;font-weight:400">(<?= $catCount ?>)</span></button>
                <?php endforeach ?>
            </div>

            <!-- Right controls -->
            <div class="plg-controls-right">
                <select id="plg-sort" class="plg-sort-select" onchange="doSort()" aria-label="Sort plugins">
                    <option value="az">A → Z</option>
                    <option value="za">Z → A</option>
                    <option value="tools">Most Tools</option>
                    <option value="rating">Top Rated</option>
                </select>

                <div class="plg-view-toggle" role="group" aria-label="View mode">
                    <button id="btn-grid" class="plg-view-btn active" onclick="setView('grid')" title="Grid view" aria-pressed="true">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor"><rect x="0" y="0" width="6" height="6" rx="1.5"/><rect x="8" y="0" width="6" height="6" rx="1.5"/><rect x="0" y="8" width="6" height="6" rx="1.5"/><rect x="8" y="8" width="6" height="6" rx="1.5"/></svg>
                    </button>
                    <button id="btn-list" class="plg-view-btn" onclick="setView('list')" title="List view" aria-pressed="false">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor"><rect x="0" y="1" width="14" height="2.5" rx="1.25"/><rect x="0" y="5.75" width="14" height="2.5" rx="1.25"/><rect x="0" y="10.5" width="14" height="2.5" rx="1.25"/></svg>
                    </button>
                </div>

                <span class="plg-result-count" id="plg-result-count" aria-live="polite"></span>
            </div>
        </div>
    </div>
</div>

<!-- ══ GRID ════════════════════════════════════════════════════════════ -->
<div class="front-container" style="padding-top:28px;padding-bottom:72px">

    <div id="plugins-grid" class="plg-grid">
    <?php foreach ($activePlugins as $_pcIdx => $plugin):
        $manifest       = json_decode($plugin['manifest'] ?? '{}', true) ?? [];
        $ogIcon         = $manifest['og_icon'] ?? 'puzzle-piece';
        $categories     = $manifest['categories'] ?? (isset($manifest['category']) && $manifest['category'] ? [$manifest['category']] : []);
        $keywords       = array_map('strtolower', $manifest['keywords'] ?? []);
        $tags           = $manifest['tags'] ?? [];
        $allSearchTerms = array_unique(array_merge($keywords, array_map('strtolower', $tags)));
        $requiresAuth   = !empty($manifest['requires_login']);
        $catAttr        = implode('|', $categories);
        $offeredCount   = (int)($plugin['offered'] ?? $manifest['offered'] ?? 1);
        $isFav          = in_array((int)($plugin['id'] ?? 0), $userFavIds);
        $_pr            = $_pluginRatings[(int)$plugin['id']] ?? null;
        $hasRating      = $_pr && (int)$_pr['rating_count'] > 0;
        $accentColor    = _plg_accent_color($categories);
        $displayTags    = array_slice($tags, 0, 4);
        $avgRating      = $hasRating ? (float)$_pr['avg_rating'] : 0;
    ?>
        <a href="/plugins/<?= e($plugin['slug']) ?>/"
           class="pc-card"
           style="--pc-i:<?= min($_pcIdx, 20) ?>;--pc-accent:<?= e($accentColor) ?>"
           data-cat="<?= e($catAttr) ?>"
           data-name="<?= e(strtolower($plugin['name'])) ?>"
           data-keywords="<?= e(implode(' ', $allSearchTerms)) ?>"
           data-desc="<?= e(strtolower($plugin['description'] ?? '')) ?>"
           data-offered="<?= $offeredCount ?>"
           data-rating="<?= number_format($avgRating, 1) ?>"
           data-sort-name="<?= e(strtolower($plugin['name'])) ?>">

            <div class="pc-accent-band"></div>

            <div class="pc-card-head">
                <div class="pc-icon">
                    <i class="fa-solid fa-<?= e($ogIcon) ?>"></i>
                </div>
                <?php if ($auth->check()): ?>
                <button type="button"
                        class="pc-fav<?= $isFav ? ' is-fav' : '' ?>"
                        data-plugin-id="<?= (int)$plugin['id'] ?>"
                        onclick="event.stopPropagation();event.preventDefault();toggleFav(this)"
                        title="<?= $isFav ? 'Remove from favourites' : 'Save to favourites' ?>"
                        aria-label="<?= $isFav ? 'Remove from favourites' : 'Save to favourites' ?>">
                    <svg width="15" height="15" fill="<?= $isFav ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </button>
                <?php endif ?>
            </div>

            <div class="pc-card-body">
                <div class="pc-name"><?= e($plugin['name']) ?></div>
                <?php if (!empty($categories)): ?>
                <div class="pc-cats">
                    <?php foreach (array_slice($categories, 0, 2) as $_pcat): ?>
                    <span class="pc-cat"><?= e($_pcat) ?></span>
                    <?php endforeach ?>
                </div>
                <?php endif ?>
                <div class="pc-desc"><?= e($plugin['description'] ?? '') ?></div>
                <?php if (!empty($displayTags)): ?>
                <div class="pc-tags">
                    <?php foreach ($displayTags as $_tag): ?>
                    <span class="pc-tag"><?= e($_tag) ?></span>
                    <?php endforeach ?>
                </div>
                <?php endif ?>
            </div>

            <div class="pc-card-foot">
                <div class="pc-meta">
                    <span class="pc-tools-badge"><?= $offeredCount ?> <?= $offeredCount === 1 ? 'tool' : 'tools' ?></span>
                    <?php if ($hasRating): ?>
                    <span class="pc-rating" title="<?= number_format($avgRating,1) ?>/5 from <?= (int)$_pr['rating_count'] ?> rating<?= (int)$_pr['rating_count'] !== 1 ? 's' : '' ?>">
                        <?php for ($__i = 1; $__i <= 5; $__i++): ?>
                            <?php if ($__i <= floor($avgRating)): ?>&#9733;<?php elseif ($__i - 0.5 <= $avgRating): ?>&#9733;<?php else: ?>&#9734;<?php endif ?>
                        <?php endfor ?>
                        <span class="pc-rating-count">(<?= (int)$_pr['rating_count'] ?>)</span>
                    </span>
                    <?php else: ?>
                    <span class="pc-ver">v<?= e($plugin['version'] ?? '1.0') ?></span>
                    <?php endif ?>
                    <?php if ($requiresAuth && !$auth->check()): ?>
                    <span class="pc-login-badge">Login required</span>
                    <?php endif ?>
                </div>
                <span class="pc-open">Open <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
            </div>

        </a>
    <?php endforeach ?>
    </div>

    <!-- Empty state -->
    <div class="plg-empty" id="plugins-empty">
        <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <h4>No tools match your search</h4>
        <p>Try different keywords or browse by category.</p>
        <button onclick="clearSearch()" class="btn btn-ghost btn-sm">Clear Search</button>
    </div>

    <?php if (!$auth->check()): ?>
    <div class="plg-cta-box">
        <h3>One account for everything</h3>
        <p>Create a free account to unlock all <?= $totalOffered ?>+ tools, save your work, and access your history.</p>
        <div class="plg-cta-actions">
            <a href="/register" class="btn btn-primary">Get Started — It's Free</a>
            <a href="/login" class="btn btn-ghost">Sign In</a>
        </div>
    </div>
    <?php endif ?>

</div><!-- /front-container -->

<?php endif ?>

<script>
(function () {
    'use strict';

    var _activeCat = 'all';
    var _view      = localStorage.getItem('plg_view') || 'grid';
    var _sort      = 'az';
    var _TOTAL     = <?= count($activePlugins) ?>;

    /* ── Init ─────────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        setView(_view, true);
        updateCount(_TOTAL);
    });

    /* ── Search ───────────────────────────────────────────────────── */
    window.liveFilter = function () {
        var q = (document.getElementById('plugin-search').value || '').toLowerCase().trim();
        var clearBtn = document.getElementById('plg-search-clear');
        if (clearBtn) clearBtn.classList.toggle('visible', q.length > 0);
        _applyFilter();
    };

    window.clearSearch = function () {
        var inp = document.getElementById('plugin-search');
        if (inp) { inp.value = ''; inp.focus(); }
        var clearBtn = document.getElementById('plg-search-clear');
        if (clearBtn) clearBtn.classList.remove('visible');
        _applyFilter();
    };

    /* ── Category ─────────────────────────────────────────────────── */
    window.setCat = function (cat, btn) {
        _activeCat = cat;
        document.querySelectorAll('.plg-cat-btn').forEach(function (b) {
            b.classList.toggle('plg-cat-active', b === btn);
        });
        _applyFilter();
    };

    /* ── Sort ─────────────────────────────────────────────────────── */
    window.doSort = function () {
        _sort = document.getElementById('plg-sort').value;
        _applyFilter();
    };

    /* ── View toggle ──────────────────────────────────────────────── */
    window.setView = function (v, skipSave) {
        _view = v;
        if (!skipSave) localStorage.setItem('plg_view', v);
        var grid    = document.getElementById('plugins-grid');
        var btnGrid = document.getElementById('btn-grid');
        var btnList = document.getElementById('btn-list');
        if (!grid) return;
        grid.classList.toggle('list-view', v === 'list');
        if (btnGrid) { btnGrid.classList.toggle('active', v === 'grid'); btnGrid.setAttribute('aria-pressed', v === 'grid'); }
        if (btnList) { btnList.classList.toggle('active', v === 'list'); btnList.setAttribute('aria-pressed', v === 'list'); }
    };

    /* ── Core filter + sort ───────────────────────────────────────── */
    function _applyFilter() {
        var q     = (document.getElementById('plugin-search').value || '').toLowerCase().trim();
        var grid  = document.getElementById('plugins-grid');
        var cards = Array.from(document.querySelectorAll('.pc-card'));
        var vis   = [];

        cards.forEach(function (card) {
            var matchCat = _activeCat === 'all' ||
                card.dataset.cat.split('|').indexOf(_activeCat) !== -1;
            var matchQ   = !q ||
                card.dataset.name.indexOf(q) !== -1 ||
                card.dataset.desc.indexOf(q) !== -1 ||
                card.dataset.keywords.indexOf(q) !== -1;
            var show = matchCat && matchQ;
            card.style.display = show ? '' : 'none';
            if (show) vis.push(card);
        });

        // Sort visible cards
        vis.sort(function (a, b) {
            if (_sort === 'za')    return a.dataset.sortName > b.dataset.sortName ? -1 : 1;
            if (_sort === 'tools') return parseInt(b.dataset.offered, 10) - parseInt(a.dataset.offered, 10);
            if (_sort === 'rating') return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
            return a.dataset.sortName > b.dataset.sortName ? 1 : -1; // az
        });
        vis.forEach(function (card, i) {
            card.style.setProperty('--pc-i', Math.min(i, 20));
            grid.appendChild(card);
        });

        // Empty state
        var emptyEl = document.getElementById('plugins-empty');
        if (emptyEl) emptyEl.style.display = vis.length === 0 ? 'block' : 'none';

        updateCount(vis.length);
    }

    function updateCount(n) {
        var el = document.getElementById('plg-result-count');
        if (!el) return;
        el.textContent = n === _TOTAL ? n + ' plugins' : n + ' of ' + _TOTAL;
    }

    /* ── Favourites ───────────────────────────────────────────────── */
    var _favCsrf = '<?= e($_favCsrf) ?>';

    window.toggleFav = function (btn) {
        var id = btn.dataset.pluginId;
        var fd = new FormData();
        fd.append('plugin_id', id);
        fd.append('_csrf', _favCsrf);
        btn.style.opacity = '0.4';
        fetch('/account/toggle-favourite', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                btn.style.opacity = '';
                if (d.error) { if (d.logged_in === false) window.location = '/login'; return; }
                var svg = btn.querySelector('svg');
                if (d.favourited) {
                    svg.setAttribute('fill', 'currentColor');
                    btn.classList.add('is-fav');
                    btn.style.color = '#ef4444';
                    btn.title = 'Remove from favourites';
                    btn.setAttribute('aria-label', 'Remove from favourites');
                } else {
                    svg.setAttribute('fill', 'none');
                    btn.classList.remove('is-fav');
                    btn.style.color = '';
                    btn.title = 'Save to favourites';
                    btn.setAttribute('aria-label', 'Save to favourites');
                }
            })
            .catch(function () { btn.style.opacity = ''; });
    };
}());
</script>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Tools & Applications', $content, [
    'description' => 'Browse all free tools and applications on Awan Tools. One account gives you access to everything.',
]);
