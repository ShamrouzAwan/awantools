<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * Previewer Toolkit — TEMPLATE REGISTRY
 * ════════════════════════════════════════════════════════════════════════════
 * Single source of truth for every category and template in the OG Image
 * Generator. `render.php` (image rendering) and `index.php` (category list +
 * template picker UI) both `require_once` this file.
 *
 * This file contains ONLY data — plain arrays, no header/session/output side
 * effects — so it is always safe to include from either entry point.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * HOW TO ADD A NEW TEMPLATE TO AN EXISTING CATEGORY
 * ────────────────────────────────────────────────────────────────────────────
 * 1. Find that category's spec array below (e.g. $OG_SPECS for the 'og'
 *    category, or a PT_*_STYLES/THEMES constant for browser/terminal/code).
 * 2. Copy an existing entry and rename its array key to a new lowercase,
 *    underscore_separated id — that id is what shows up in ?template=... URLs
 *    and in the on-page template grid.
 * 3. Fill in the same properties used by sibling entries in that array (see
 *    PROPERTY REFERENCE below for what each key controls).
 * 4. Save. Nothing else needs to change — $CATEGORIES / $PT_REGISTRY at the
 *    bottom of this file are built from these arrays with array_keys(), so
 *    the sidebar count, the template grid, and render.php's validation all
 *    pick up the new template automatically.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * HOW TO REMOVE A TEMPLATE
 * ────────────────────────────────────────────────────────────────────────────
 * Delete its entry from the spec array (or its name from a plain template
 * list, for the 'placeholder' category — see below). That's it: render.php's
 * dispatch logic always falls back to the category's first remaining
 * template whenever an old/bookmarked ?template= value no longer resolves,
 * so removing an entry can never produce a broken image or a fatal error.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * HOW TO ADD A BRAND NEW CATEGORY
 * ────────────────────────────────────────────────────────────────────────────
 * 1. Add a new spec array here, e.g. `$MYCAT_SPECS = ['tpl_a' => [...], ...]`.
 *    Prefer this data-driven approach whenever templates in the category only
 *    differ by colors/layout choice — that's how every category except
 *    'placeholder' works today.
 *    If a template's look genuinely can't be reduced to data (e.g. each one
 *    draws fundamentally different shapes), list plain name strings instead —
 *    see $PLACEHOLDER_TEMPLATES — and keep a switch/case per template in
 *    render.php.
 * 2. Write a `pt_render_mycat(GdImage $im, array $p, array $s): void`
 *    function in render.php. Use an existing category's function (e.g.
 *    `pt_render_bizcard`) as a model for how to read `$p` (user content) and
 *    `$s` (the resolved spec for the chosen template).
 * 3. Register the category in the `$CATEGORIES` list below: id, sidebar
 *    icon (a Font Awesome solid icon name), label, the template list (usually
 *    `array_keys($MYCAT_SPECS)`), and default width/height.
 * 4. Add one `case 'mycat':` to the dispatch `switch` in render.php's "MAIN
 *    RENDER LOGIC" section, resolving the spec (`$MYCAT_SPECS[$tpl] ?? ...`)
 *    and calling your render function.
 * That switch case is the only code change needed elsewhere — index.php's
 * sidebar and previewer.js's template grid both read from `$PT_REGISTRY`,
 * which is generated below from `$CATEGORIES`.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * PROPERTY REFERENCE — `$p` (user-supplied content, from render.php's query
 * string parsing; same keys for every category, categories just use whichever
 * ones make sense for their layout)
 * ────────────────────────────────────────────────────────────────────────────
 *   category, template   Which spec/renderer to use (see $PT_REGISTRY below).
 *   format                png | jpg | jpeg | webp
 *   width, height         Output image size in pixels (100-2400).
 *   heading               Main title text.
 *   subheading            Secondary title text.
 *   description           Body/description paragraph.
 *   badge                 Comma-separated list of short badge/topic labels.
 *   footer                Small footer text line.
 *   website               Site/domain shown in footer or header.
 *   author                Author/host name.
 *   date                  Date string (invitations, docs, etc).
 *   category_label        Small category/kicker label.
 *   icon                  Icon name (see pt_fa_codepoint() in render.php).
 *   bg_color, fg_color, accent_color, heading_color, description_color
 *                         Hex colors (no '#') the user can override; when
 *                         present they patch the chosen template's spec
 *                         at render time (see the 'og'/'social' dispatch
 *                         cases in render.php).
 *   font_size             Requested heading font size (12-96, clamped per
 *                         layout).
 *   radius                Corner radius override for card-based layouts.
 *   padding               Outer padding override.
 *   line1..line4          Individual lines of text (terminal-style content).
 *   code, lang, filename  Code-snippet category content.
 *   url_bar               Browser-mockup address bar text.
 *   stars, forks, version Repo-style stat strings (github/browser cards).
 *   username, role        Profile-card identity fields.
 *   stat1_label/value .. stat3_label/value
 *                         Up to three labeled stats (profile cards).
 *   metric1..metric3, metric1_label..metric3_label
 *                         Up to three labeled metrics (dashboards).
 *   dark                  Boolean flag (?dark present) some templates read.
 *   watermark             Small watermark text drawn in a corner.
 *
 * PROPERTY REFERENCE — `$s` (a resolved template spec array; not every key
 * is used by every category, this is the union across all spec arrays below)
 * ────────────────────────────────────────────────────────────────────────────
 *   layout                Which shared layout function renders this template
 *                         (e.g. 'stack'|'editorial'|'hero'|'split'|'floating'|
 *                         'diagonal' for og/social; 'horizontal'|'vertical'|
 *                         'split'|'glass'|'minimal' for profile; etc).
 *   bg, bg2               Background color, and optional second color for a
 *                         gradient background.
 *   pattern               Optional background texture: 'none'|'dots'|'noise'|
 *                         'gradient'.
 *   card_bg, card_border  Card fill/border color for card-based layouts.
 *   accent, accent_color  Primary accent color used for icons/badges/lines.
 *   heading_color, desc_color, footer_color, name_color, role_color,
 *   title_color, subtitle_color, body_color, txt/txt_color, muted
 *                         Text colors for the various text roles a layout
 *                         draws.
 *   badge_bg, badge_color icon_bg, icon_color
 *                         Badge and icon chip colors.
 *   radius                Corner radius for this specific template.
 *   split_bg, panel_bg    Secondary panel background (split/panel layouts).
 *   stat_color, stat_label_color
 *                         Colors for labeled stat rows (profile/github/docs).
 *   positive, negative    Up/down indicator colors (dashboards).
 *   method_get/post/put/del
 *                         HTTP verb badge colors (docs category).
 */

// ════════════════════════════════════════════════════════════════════════════
// OG IMAGE TEMPLATES  (1200 × 630 default)
// ════════════════════════════════════════════════════════════════════════════

$OG_SPECS = [
    // ── STACK: icon top-left, badges, title, desc, footer ────────────────
    'github_dark'   => ['layout'=>'stack','bg'=>'0d1117','bg2'=>null,'pattern'=>'dots','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'21262d','badge_color'=>'3fb950','accent_color'=>'3fb950','icon_bg'=>'21262d','icon_color'=>'3fb950','footer_color'=>'484f58','radius'=>8],
    'aurora'        => ['layout'=>'stack','bg'=>'0a0f0d','bg2'=>'0d2b1a','pattern'=>'noise','card_bg'=>'0d1f15','card_border'=>'1a4a2e','heading_color'=>'ecfdf5','desc_color'=>'6ee7b7','badge_bg'=>'064e3b','badge_color'=>'34d399','accent_color'=>'10b981','icon_bg'=>'064e3b','icon_color'=>'34d399','footer_color'=>'34d399','radius'=>8],
    'forest'        => ['layout'=>'stack','bg'=>'14231b','bg2'=>'1a3a28','pattern'=>'none','card_bg'=>'1e3d2b','card_border'=>'2d5a40','heading_color'=>'ecfdf5','desc_color'=>'86efac','badge_bg'=>'2d5a40','badge_color'=>'bbf7d0','accent_color'=>'22c55e','icon_bg'=>'2d5a40','icon_color'=>'22c55e','footer_color'=>'4ade80','radius'=>4],
    'dark_amber'    => ['layout'=>'stack','bg'=>'120e09','bg2'=>null,'pattern'=>'noise','card_bg'=>'1c1610','card_border'=>'3d2f12','heading_color'=>'fef3c7','desc_color'=>'d97706','badge_bg'=>'3d2f12','badge_color'=>'f59e0b','accent_color'=>'f59e0b','icon_bg'=>'3d2f12','icon_color'=>'f59e0b','footer_color'=>'78716c','radius'=>4],
    // ── EDITORIAL: large headline across full width, top + bottom rule ─────
    'github_light'  => ['layout'=>'editorial','bg'=>'ffffff','bg2'=>null,'pattern'=>'none','heading_color'=>'1f2328','desc_color'=>'656d76','badge_bg'=>'ddf4ff','badge_color'=>'0969da','accent_color'=>'0969da','footer_color'=>'6e7781','radius'=>6],
    'newspaper'     => ['layout'=>'editorial','bg'=>'f7f2e8','bg2'=>null,'pattern'=>'none','heading_color'=>'1a1207','desc_color'=>'44392a','badge_bg'=>'1a1207','badge_color'=>'f7f2e8','accent_color'=>'b5441b','footer_color'=>'8a7360','radius'=>0],
    'neon_dark'     => ['layout'=>'editorial','bg'=>'030308','bg2'=>null,'pattern'=>'noise','heading_color'=>'ffffff','desc_color'=>'a0a8c0','badge_bg'=>'001a0d','badge_color'=>'00ff88','accent_color'=>'00ff88','footer_color'=>'333355','radius'=>0],
    'mono'          => ['layout'=>'editorial','bg'=>'0a0a0a','bg2'=>null,'pattern'=>'none','heading_color'=>'ffffff','desc_color'=>'777777','badge_bg'=>'ffffff','badge_color'=>'0a0a0a','accent_color'=>'ffffff','footer_color'=>'444444','radius'=>0],
    // ── HERO: large centered icon circle, centered title + desc ───────────
    'glass_modern'  => ['layout'=>'hero','bg'=>'1a0533','bg2'=>'0a1628','pattern'=>'gradient','heading_color'=>'ffffff','desc_color'=>'c4b5fd','badge_bg'=>'6d28d9','badge_color'=>'ddd6fe','accent_color'=>'a855f7','icon_bg'=>'4c1d95','icon_color'=>'a855f7','footer_color'=>'7c3aed','radius'=>24],
    'gradient_pro'  => ['layout'=>'hero','bg'=>'0f0c29','bg2'=>'302b63','pattern'=>'gradient','heading_color'=>'ffffff','desc_color'=>'94a3b8','badge_bg'=>'3b82f6','badge_color'=>'ffffff','accent_color'=>'60a5fa','icon_bg'=>'1d4ed8','icon_color'=>'93c5fd','footer_color'=>'64748b','radius'=>20],
    'indie'         => ['layout'=>'hero','bg'=>'fef6ee','bg2'=>null,'pattern'=>'dots','heading_color'=>'1c0a00','desc_color'=>'7c4a1e','badge_bg'=>'c4600a','badge_color'=>'ffffff','accent_color'=>'c4600a','icon_bg'=>'fde8d0','icon_color'=>'c4600a','footer_color'=>'a0816b','radius'=>16],
    'candy'         => ['layout'=>'hero','bg'=>'fce4ec','bg2'=>'e8d5f0','pattern'=>'gradient','heading_color'=>'4a0027','desc_color'=>'ad1457','badge_bg'=>'ad1457','badge_color'=>'ffffff','accent_color'=>'e91e63','icon_bg'=>'f8bbd0','icon_color'=>'ad1457','footer_color'=>'c2185b','radius'=>28],
    // ── SPLIT: left icon panel | right content panel ──────────────────────
    'corporate'     => ['layout'=>'split','bg'=>'fafbfc','bg2'=>null,'pattern'=>'none','heading_color'=>'0f2744','desc_color'=>'4a6a85','badge_bg'=>'0f2744','badge_color'=>'c8a04a','accent_color'=>'c8a04a','icon_bg'=>'0f2744','icon_color'=>'c8a04a','footer_color'=>'7a9ab5','split_bg'=>'0f2744','radius'=>0],
    'blueprint'     => ['layout'=>'split','bg'=>'f0f4ff','bg2'=>null,'pattern'=>'dots','heading_color'=>'003580','desc_color'=>'1a4a9e','badge_bg'=>'003580','badge_color'=>'4fc3f7','accent_color'=>'4fc3f7','icon_bg'=>'003580','icon_color'=>'ffffff','footer_color'=>'7090c8','split_bg'=>'003580','radius'=>0],
    'retro_sunset'  => ['layout'=>'split','bg'=>'fdf0e8','bg2'=>null,'pattern'=>'none','heading_color'=>'2d1b69','desc_color'=>'9a6a4a','badge_bg'=>'2d1b69','badge_color'=>'ffd700','accent_color'=>'c43b00','icon_bg'=>'2d1b69','icon_color'=>'ffd700','footer_color'=>'c0856a','split_bg'=>'2d1b69','radius'=>0],
    // ── FLOATING: dark background + elevated card with drop shadow ─────────
    'ocean'         => ['layout'=>'floating','bg'=>'0c1b33','bg2'=>'023e8a','pattern'=>'gradient','card_bg'=>'0a2a55','card_border'=>'48cae4','heading_color'=>'caf0f8','desc_color'=>'90e0ef','badge_bg'=>'005f86','badge_color'=>'caf0f8','accent_color'=>'48cae4','icon_bg'=>'00b4d8','icon_color'=>'caf0f8','footer_color'=>'90e0ef','radius'=>16],
    'steel'         => ['layout'=>'floating','bg'=>'111318','bg2'=>null,'pattern'=>'none','card_bg'=>'1c2128','card_border'=>'30363d','heading_color'=>'cdd9e5','desc_color'=>'768390','badge_bg'=>'316dca','badge_color'=>'ffffff','accent_color'=>'316dca','icon_bg'=>'1f3051','icon_color'=>'4493f8','footer_color'=>'444c56','radius'=>8],
    'startup'       => ['layout'=>'floating','bg'=>'f72585','bg2'=>'7209b7','pattern'=>'gradient','card_bg'=>'ffffff','card_border'=>'f0e0ff','heading_color'=>'2d0058','desc_color'=>'6b21a8','badge_bg'=>'7c3aed','badge_color'=>'ffffff','accent_color'=>'7c3aed','icon_bg'=>'ede9fe','icon_color'=>'7c3aed','footer_color'=>'9333ea','radius'=>20],
    // ── DIAGONAL: two-tone angled split ───────────────────────────────────
    'minimal_clean' => ['layout'=>'diagonal','bg'=>'ffffff','bg2'=>'eef2ff','pattern'=>'none','heading_color'=>'111827','desc_color'=>'6b7280','badge_bg'=>'eff6ff','badge_color'=>'3b82f6','accent_color'=>'3b82f6','icon_bg'=>'dbeafe','icon_color'=>'2563eb','footer_color'=>'9ca3af','radius'=>0],
    'cyberpunk'     => ['layout'=>'diagonal','bg'=>'0a0a0a','bg2'=>'1a0020','pattern'=>'noise','heading_color'=>'ffffff','desc_color'=>'ff00ff','badge_bg'=>'ffff00','badge_color'=>'0a0a0a','accent_color'=>'00ffff','icon_bg'=>'1a1a00','icon_color'=>'ffff00','footer_color'=>'ff00ff','radius'=>0],
    'ocean_wave'    => ['layout'=>'diagonal','bg'=>'e0f7ff','bg2'=>'b3e5fc','pattern'=>'none','heading_color'=>'01579b','desc_color'=>'0277bd','badge_bg'=>'01579b','badge_color'=>'ffffff','accent_color'=>'039be5','icon_bg'=>'01579b','icon_color'=>'ffffff','footer_color'=>'4fc3f7','radius'=>0],
];

// ════════════════════════════════════════════════════════════════════════════
// SOCIAL CARD TEMPLATES — same layout engine as OG (see $OG_SPECS)
// ════════════════════════════════════════════════════════════════════════════

$SOCIAL_SPECS = [
    // ── STACK ───────────────────────────────────────────────────────────────
    'twitter'          => ['layout'=>'stack','bg'=>'000000','bg2'=>null,'pattern'=>'none','card_bg'=>'0f0f0f','card_border'=>'2f3336','heading_color'=>'e7e9ea','desc_color'=>'8b98a5','badge_bg'=>'1d9bf0','badge_color'=>'ffffff','accent_color'=>'1d9bf0','icon_bg'=>'1d3a4f','icon_color'=>'1d9bf0','footer_color'=>'536471','radius'=>16],
    'discord'          => ['layout'=>'stack','bg'=>'313338','bg2'=>null,'pattern'=>'none','card_bg'=>'2b2d31','card_border'=>'1e1f22','heading_color'=>'f2f3f5','desc_color'=>'b5bac1','badge_bg'=>'5865f2','badge_color'=>'ffffff','accent_color'=>'5865f2','icon_bg'=>'3c45a5','icon_color'=>'ffffff','footer_color'=>'4e5058','radius'=>4],
    'announcement'     => ['layout'=>'stack','bg'=>'020817','bg2'=>'1e1b4b','pattern'=>'gradient','card_bg'=>'0f172a','card_border'=>'334155','heading_color'=>'f8fafc','desc_color'=>'94a3b8','badge_bg'=>'f59e0b','badge_color'=>'000000','accent_color'=>'f59e0b','icon_bg'=>'451a03','icon_color'=>'f59e0b','footer_color'=>'475569','radius'=>12],
    'feature_highlight'=> ['layout'=>'stack','bg'=>'1a1a2e','bg2'=>'16213e','pattern'=>'none','card_bg'=>'0f3460','card_border'=>'e94560','heading_color'=>'ffffff','desc_color'=>'a8b2d8','badge_bg'=>'e94560','badge_color'=>'ffffff','accent_color'=>'e94560','icon_bg'=>'e94560','icon_color'=>'ffffff','footer_color'=>'627b9a','radius'=>12],
    'reddit'           => ['layout'=>'stack','bg'=>'1a1a1b','bg2'=>null,'pattern'=>'none','card_bg'=>'272729','card_border'=>'343536','heading_color'=>'d7dadc','desc_color'=>'818384','badge_bg'=>'ff4500','badge_color'=>'ffffff','accent_color'=>'ff4500','icon_bg'=>'331400','icon_color'=>'ff4500','footer_color'=>'818384','radius'=>4],
    // ── EDITORIAL ────────────────────────────────────────────────────────────
    'linkedin'         => ['layout'=>'editorial','bg'=>'f3f2ef','bg2'=>null,'pattern'=>'none','heading_color'=>'000000','desc_color'=>'666666','badge_bg'=>'0a66c2','badge_color'=>'ffffff','accent_color'=>'0a66c2','icon_bg'=>'cce5ff','icon_color'=>'0a66c2','footer_color'=>'666666','radius'=>8],
    'hackernews'       => ['layout'=>'editorial','bg'=>'f6f6ef','bg2'=>null,'pattern'=>'none','heading_color'=>'000000','desc_color'=>'828282','badge_bg'=>'ff6600','badge_color'=>'ffffff','accent_color'=>'ff6600','icon_bg'=>'fff0e6','icon_color'=>'ff6600','footer_color'=>'828282','radius'=>0],
    'blog_post'        => ['layout'=>'editorial','bg'=>'fafafa','bg2'=>null,'pattern'=>'none','heading_color'=>'09090b','desc_color'=>'71717a','badge_bg'=>'fef2f2','badge_color'=>'ef4444','accent_color'=>'ef4444','icon_bg'=>'fef2f2','icon_color'=>'ef4444','footer_color'=>'a1a1aa','radius'=>8],
    'newsletter'       => ['layout'=>'editorial','bg'=>'faf7f2','bg2'=>null,'pattern'=>'none','heading_color'=>'1a1208','desc_color'=>'6b5a45','badge_bg'=>'fef3e2','badge_color'=>'c17817','accent_color'=>'c17817','icon_bg'=>'fef3e2','icon_color'=>'c17817','footer_color'=>'9b8b78','radius'=>8],
    // ── HERO ─────────────────────────────────────────────────────────────────
    'instagram'        => ['layout'=>'hero','bg'=>'833ab4','bg2'=>'fd1d1d','pattern'=>'gradient','heading_color'=>'ffffff','desc_color'=>'ffecd2','badge_bg'=>'ffffff','badge_color'=>'833ab4','accent_color'=>'fcb045','icon_bg'=>'ffffff','icon_color'=>'fd1d1d','footer_color'=>'ffecd2','radius'=>20],
    'product_hunt'     => ['layout'=>'hero','bg'=>'da552f','bg2'=>'c0392b','pattern'=>'gradient','heading_color'=>'ffffff','desc_color'=>'ffd0c0','badge_bg'=>'ffffff','badge_color'=>'da552f','accent_color'=>'da552f','icon_bg'=>'ffffff','icon_color'=>'da552f','footer_color'=>'ffd0c0','radius'=>16],
    'dribbble'         => ['layout'=>'hero','bg'=>'ea4c89','bg2'=>'f06292','pattern'=>'gradient','heading_color'=>'ffffff','desc_color'=>'fce4ef','badge_bg'=>'ffffff','badge_color'=>'ea4c89','accent_color'=>'ea4c89','icon_bg'=>'ffffff','icon_color'=>'ea4c89','footer_color'=>'fce4ef','radius'=>20],
    'event'            => ['layout'=>'hero','bg'=>'13001f','bg2'=>'2d0050','pattern'=>'gradient','heading_color'=>'ffffff','desc_color'=>'e2c8ff','badge_bg'=>'c89600','badge_color'=>'000000','accent_color'=>'c89600','icon_bg'=>'c89600','icon_color'=>'13001f','footer_color'=>'9370db','radius'=>4],
    // ── SPLIT ────────────────────────────────────────────────────────────────
    'telegram'         => ['layout'=>'split','bg'=>'ffffff','bg2'=>null,'pattern'=>'none','heading_color'=>'1a1a1a','desc_color'=>'555555','badge_bg'=>'2aabee','badge_color'=>'ffffff','accent_color'=>'2aabee','icon_bg'=>'2aabee','icon_color'=>'ffffff','footer_color'=>'888888','split_bg'=>'2aabee','radius'=>12],
    'youtube'          => ['layout'=>'split','bg'=>'f9f9f9','bg2'=>null,'pattern'=>'none','heading_color'=>'0f0f0f','desc_color'=>'717171','badge_bg'=>'ff0000','badge_color'=>'ffffff','accent_color'=>'ff0000','icon_bg'=>'ff0000','icon_color'=>'ffffff','footer_color'=>'aaaaaa','split_bg'=>'0f0f0f','radius'=>0],
    'facebook'         => ['layout'=>'split','bg'=>'f0f2f5','bg2'=>null,'pattern'=>'none','heading_color'=>'1c1e21','desc_color'=>'65676b','badge_bg'=>'1877f2','badge_color'=>'ffffff','accent_color'=>'1877f2','icon_bg'=>'1877f2','icon_color'=>'ffffff','footer_color'=>'8a8d91','split_bg'=>'1877f2','radius'=>8],
    'job_post'         => ['layout'=>'split','bg'=>'f0fdf4','bg2'=>null,'pattern'=>'none','heading_color'=>'052e16','desc_color'=>'166534','badge_bg'=>'dcfce7','badge_color'=>'15803d','accent_color'=>'16a34a','icon_bg'=>'16a34a','icon_color'=>'ffffff','footer_color'=>'4ade80','split_bg'=>'052e16','radius'=>12],
    // ── FLOATING ─────────────────────────────────────────────────────────────
    'product_launch'   => ['layout'=>'floating','bg'=>'0c3547','bg2'=>'06b6d4','pattern'=>'gradient','card_bg'=>'ffffff','card_border'=>'e0f7fa','heading_color'=>'0c3547','desc_color'=>'0277bd','badge_bg'=>'0891b2','badge_color'=>'ffffff','accent_color'=>'06b6d4','icon_bg'=>'e0f7fa','icon_color'=>'0891b2','footer_color'=>'90cae0','radius'=>16],
];

// ════════════════════════════════════════════════════════════════════════════
// PLACEHOLDER TEMPLATES — procedural (each name is a `case` in render.php's
// pt_render_placeholder() switch, drawing genuinely different GD shapes, so
// it can't be reduced to a data table). This list is the registry/UI source
// of truth for the category; render.php's switch must have a matching case
// for every name listed here.
// ════════════════════════════════════════════════════════════════════════════

$PLACEHOLDER_TEMPLATES = [
    'simple','grid','gradient','glass','pattern','minimal','modern','empty_state',
    'blueprint_grid','crosshatch','circuit','polka_dots','diagonal_stripes',
    'noise_field','sketch','dots_dark','gradient_mesh','marble',
];

// ════════════════════════════════════════════════════════════════════════════
// BROWSER MOCKUP TEMPLATES — pure style lookup, consumed by
// pt_render_browser() in render.php.
// ════════════════════════════════════════════════════════════════════════════

$BROWSER_STYLES = [
    'chrome'  => ['chrome_bg'=>'dee1e6','tab_bg'=>'ffffff','bar_bg'=>'f1f3f4','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'3c4043','border'=>'c9cdd1','accent'=>'1a73e8'],
    'firefox' => ['chrome_bg'=>'2b2a33','tab_bg'=>'42414d','bar_bg'=>'1c1b22','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'fbfbfe','border'=>'52515e','accent'=>'ff7139'],
    'safari'  => ['chrome_bg'=>'ebebeb','tab_bg'=>'ffffff','bar_bg'=>'f5f5f5','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'1c1c1e','border'=>'d1d1d6','accent'=>'006aff'],
    'edge'    => ['chrome_bg'=>'202124','tab_bg'=>'2d2d2d','bar_bg'=>'171717','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'ffffff','border'=>'333333','accent'=>'0078d7'],
    'arc'     => ['chrome_bg'=>'1e1b2e','tab_bg'=>'2a2542','bar_bg'=>'1a1728','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'e8e6f0','border'=>'3d3a52','accent'=>'a78bfa'],
    'generic'         => ['chrome_bg'=>'f0f0f0','tab_bg'=>'ffffff','bar_bg'=>'e8e8e8','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'333333','border'=>'cccccc','accent'=>'4a90d9'],
    // ── 9 new browser templates ──────────────────────────────
    'brave'           => ['chrome_bg'=>'1a1a1a','tab_bg'=>'2a2a2a','bar_bg'=>'111111','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'ffffff','border'=>'333333','accent'=>'fb542b'],
    'opera'           => ['chrome_bg'=>'2b2b2b','tab_bg'=>'363636','bar_bg'=>'1c1c1c','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'ffffff','border'=>'444444','accent'=>'ff1b2d'],
    'vivaldi'         => ['chrome_bg'=>'2b1a2e','tab_bg'=>'3d2a42','bar_bg'=>'1f1224','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'e8d8f0','border'=>'4a3555','accent'=>'ef3939'],
    'dark_mode'       => ['chrome_bg'=>'1e1e1e','tab_bg'=>'2d2d2d','bar_bg'=>'141414','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'e0e0e0','border'=>'3a3a3a','accent'=>'4fc3f7'],
    'minimal_browser' => ['chrome_bg'=>'f8f8f8','tab_bg'=>'ffffff','bar_bg'=>'f0f0f0','dot_colors'=>['dddddd','dddddd','dddddd'],'txt_color'=>'333333','border'=>'e0e0e0','accent'=>'333333'],
    'retro_browser'   => ['chrome_bg'=>'c0c0c0','tab_bg'=>'d4d0c8','bar_bg'=>'c0c0c0','dot_colors'=>['ff0000','ffff00','00ff00'],'txt_color'=>'000000','border'=>'808080','accent'=>'000080'],
    'high_contrast'   => ['chrome_bg'=>'000000','tab_bg'=>'ffffff','bar_bg'=>'000000','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'ffffff','border'=>'ffffff','accent'=>'ffff00'],
    'material'        => ['chrome_bg'=>'1565c0','tab_bg'=>'1976d2','bar_bg'=>'0d47a1','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'ffffff','border'=>'1e88e5','accent'=>'64b5f6'],
    'warm_light'      => ['chrome_bg'=>'f5ede0','tab_bg'=>'fdfaf5','bar_bg'=>'ede5d8','dot_colors'=>['ff5f57','febc2e','28c840'],'txt_color'=>'3d2b1f','border'=>'d4c4b0','accent'=>'c17817'],
];

// ════════════════════════════════════════════════════════════════════════════
// TERMINAL PREVIEW TEMPLATES — pure style lookup, consumed by
// pt_render_terminal() in render.php.
// ════════════════════════════════════════════════════════════════════════════

$TERMINAL_THEMES = [
    'linux'   => ['bg'=>'300a24','body_bg'=>'2d0922','txt'=>'ffffff','prompt'=>'4af626','path'=>'1a8cff','comment'=>'6a6a6a','title_bg'=>'3d1033'],
    'modern'  => ['bg'=>'1e1e1e','body_bg'=>'1e1e1e','txt'=>'d4d4d4','prompt'=>'569cd6','path'=>'ce9178','comment'=>'6a9955','title_bg'=>'2d2d2d'],
    'hacker'  => ['bg'=>'000000','body_bg'=>'000000','txt'=>'00ff00','prompt'=>'00ff00','path'=>'00cc00','comment'=>'006600','title_bg'=>'001100'],
    'vscode'  => ['bg'=>'1e1e1e','body_bg'=>'1e1e1e','txt'=>'cccccc','prompt'=>'4ec9b0','path'=>'ce9178','comment'=>'6a9955','title_bg'=>'323232'],
    'minimal'      => ['bg'=>'0d1117','body_bg'=>'0d1117','txt'=>'c9d1d9','prompt'=>'79c0ff','path'=>'ffa657','comment'=>'8b949e','title_bg'=>'161b22'],
    // ── 10 new terminal templates ────────────────────────────
    'powerline'    => ['bg'=>'1a1a2e','body_bg'=>'1a1a2e','txt'=>'e0e0e0','prompt'=>'6272a4','path'=>'50fa7b','comment'=>'6272a4','title_bg'=>'282a36'],
    'fish_shell'   => ['bg'=>'102030','body_bg'=>'102030','txt'=>'e0f0f8','prompt'=>'4ac6e8','path'=>'79e6f3','comment'=>'5a8a9a','title_bg'=>'0a1a28'],
    'windows_cmd'  => ['bg'=>'0c0c0c','body_bg'=>'0c0c0c','txt'=>'cccccc','prompt'=>'cccccc','path'=>'cccccc','comment'=>'888888','title_bg'=>'0c0c0c'],
    'powershell'   => ['bg'=>'012456','body_bg'=>'012456','txt'=>'eeedf0','prompt'=>'ffffff','path'=>'f3f99d','comment'=>'6a9fce','title_bg'=>'001a3d'],
    'ubuntu_term'  => ['bg'=>'2c001e','body_bg'=>'300a24','txt'=>'ffffff','prompt'=>'00aa44','path'=>'4e9dc8','comment'=>'888888','title_bg'=>'3d1033'],
    'matrix'       => ['bg'=>'000000','body_bg'=>'000000','txt'=>'00ff00','prompt'=>'00aa00','path'=>'00ff00','comment'=>'005500','title_bg'=>'001100'],
    'amber'        => ['bg'=>'0a0700','body_bg'=>'0c0900','txt'=>'ffb000','prompt'=>'ffcc44','path'=>'ff8800','comment'=>'886600','title_bg'=>'060400'],
    'iterm2'       => ['bg'=>'1e1f26','body_bg'=>'1e1f26','txt'=>'d8d8d8','prompt'=>'80bfff','path'=>'e0c97a','comment'=>'5e6d7a','title_bg'=>'282932'],
    'p10k'         => ['bg'=>'1e1e2e','body_bg'=>'1e1e2e','txt'=>'cdd6f4','prompt'=>'89b4fa','path'=>'a6e3a1','comment'=>'6c7086','title_bg'=>'181825'],
    'dracula_term' => ['bg'=>'282a36','body_bg'=>'282a36','txt'=>'f8f8f2','prompt'=>'50fa7b','path'=>'ff79c6','comment'=>'6272a4','title_bg'=>'21222c'],
];

// ════════════════════════════════════════════════════════════════════════════
// CODE SNIPPET TEMPLATES — pure style lookup, consumed by pt_render_code()
// in render.php.
// ════════════════════════════════════════════════════════════════════════════

$CODE_THEMES = [
    'vscode'  => ['bg'=>'1e1e1e','header_bg'=>'323232','gutter_bg'=>'1e1e1e','gutter_color'=>'858585','txt'=>'d4d4d4','string'=>'ce9178','keyword'=>'569cd6','comment'=>'6a9955','number'=>'b5cea8','function'=>'dcdcaa','variable'=>'9cdcfe','operator'=>'d4d4d4','tab_active'=>'1e1e1e','tab_inactive'=>'2d2d2d'],
    'github'  => ['bg'=>'ffffff','header_bg'=>'f6f8fa','gutter_bg'=>'f6f8fa','gutter_color'=>'6e7781','txt'=>'24292f','string'=>'0a3069','keyword'=>'cf222e','comment'=>'6e7781','number'=>'0550ae','function'=>'8250df','variable'=>'24292f','operator'=>'24292f','tab_active'=>'ffffff','tab_inactive'=>'f6f8fa'],
    'monokai' => ['bg'=>'272822','header_bg'=>'3e3d32','gutter_bg'=>'2c2b26','gutter_color'=>'75715e','txt'=>'f8f8f2','string'=>'e6db74','keyword'=>'f92672','comment'=>'75715e','number'=>'ae81ff','function'=>'a6e22e','variable'=>'fd971f','operator'=>'f8f8f2','tab_active'=>'272822','tab_inactive'=>'3e3d32'],
    'nord'    => ['bg'=>'2e3440','header_bg'=>'3b4252','gutter_bg'=>'3b4252','gutter_color'=>'4c566a','txt'=>'d8dee9','string'=>'a3be8c','keyword'=>'81a1c1','comment'=>'4c566a','number'=>'b48ead','function'=>'88c0d0','variable'=>'d8dee9','operator'=>'81a1c1','tab_active'=>'2e3440','tab_inactive'=>'3b4252'],
    'dracula' => ['bg'=>'282a36','header_bg'=>'343746','gutter_bg'=>'343746','gutter_color'=>'6272a4','txt'=>'f8f8f2','string'=>'f1fa8c','keyword'=>'ff79c6','comment'=>'6272a4','number'=>'bd93f9','function'=>'50fa7b','variable'=>'8be9fd','operator'=>'ff79c6','tab_active'=>'282a36','tab_inactive'=>'343746'],
    'minimal'    => ['bg'=>'f8f8f8','header_bg'=>'f0f0f0','gutter_bg'=>'f0f0f0','gutter_color'=>'aaaaaa','txt'=>'333333','string'=>'448c27','keyword'=>'4b69c6','comment'=>'aaaaaa','number'=>'9c5d27','function'=>'7a3e9d','variable'=>'333333','operator'=>'333333','tab_active'=>'f8f8f8','tab_inactive'=>'f0f0f0'],
    // ── 9 new code themes ────────────────────────────────────
    'one_dark'   => ['bg'=>'282c34','header_bg'=>'21252b','gutter_bg'=>'21252b','gutter_color'=>'495162','txt'=>'abb2bf','string'=>'98c379','keyword'=>'c678dd','comment'=>'5c6370','number'=>'d19a66','function'=>'61afef','variable'=>'e06c75','operator'=>'56b6c2','tab_active'=>'282c34','tab_inactive'=>'21252b'],
    'synthwave'  => ['bg'=>'262335','header_bg'=>'1a1a2e','gutter_bg'=>'1a1a2e','gutter_color'=>'495495','txt'=>'ffffff','string'=>'ff8b39','keyword'=>'ff6bcb','comment'=>'848bbd','number'=>'f97583','function'=>'36f9f6','variable'=>'fede5d','operator'=>'ff6bcb','tab_active'=>'262335','tab_inactive'=>'1a1a2e'],
    'gruvbox'    => ['bg'=>'282828','header_bg'=>'1d2021','gutter_bg'=>'282828','gutter_color'=>'504945','txt'=>'ebdbb2','string'=>'b8bb26','keyword'=>'fb4934','comment'=>'928374','number'=>'d3869b','function'=>'fabd2f','variable'=>'83a598','operator'=>'fe8019','tab_active'=>'282828','tab_inactive'=>'1d2021'],
    'solarized'  => ['bg'=>'002b36','header_bg'=>'073642','gutter_bg'=>'073642','gutter_color'=>'586e75','txt'=>'839496','string'=>'2aa198','keyword'=>'859900','comment'=>'586e75','number'=>'d33682','function'=>'268bd2','variable'=>'b58900','operator'=>'cb4b16','tab_active'=>'002b36','tab_inactive'=>'073642'],
    'tokyo_night'=> ['bg'=>'1a1b2e','header_bg'=>'16161e','gutter_bg'=>'16161e','gutter_color'=>'3b3d57','txt'=>'a9b1d6','string'=>'9ece6a','keyword'=>'7aa2f7','comment'=>'565f89','number'=>'ff9e64','function'=>'7dcfff','variable'=>'f7768e','operator'=>'bb9af7','tab_active'=>'1a1b2e','tab_inactive'=>'16161e'],
    'catppuccin' => ['bg'=>'1e1e2e','header_bg'=>'181825','gutter_bg'=>'181825','gutter_color'=>'45475a','txt'=>'cdd6f4','string'=>'a6e3a1','keyword'=>'cba6f7','comment'=>'6c7086','number'=>'fab387','function'=>'89b4fa','variable'=>'f38ba8','operator'=>'89dceb','tab_active'=>'1e1e2e','tab_inactive'=>'181825'],
    'atom_light' => ['bg'=>'fafafa','header_bg'=>'f0f0f0','gutter_bg'=>'f0f0f0','gutter_color'=>'9d9d9f','txt'=>'383a42','string'=>'50a14f','keyword'=>'a626a4','comment'=>'a0a1a7','number'=>'986801','function'=>'4078f2','variable'=>'e45649','operator'=>'0184bc','tab_active'=>'fafafa','tab_inactive'=>'f0f0f0'],
    'sublime'    => ['bg'=>'23241f','header_bg'=>'272822','gutter_bg'=>'272822','gutter_color'=>'75715e','txt'=>'f8f8f2','string'=>'e6db74','keyword'=>'f92672','comment'=>'75715e','number'=>'ae81ff','function'=>'a6e22e','variable'=>'66d9ef','operator'=>'f92672','tab_active'=>'23241f','tab_inactive'=>'272822'],
    'jetbrains'  => ['bg'=>'2b2b2b','header_bg'=>'3c3f41','gutter_bg'=>'313335','gutter_color'=>'606366','txt'=>'a9b7c6','string'=>'6a8759','keyword'=>'cc7832','comment'=>'629755','number'=>'6897bb','function'=>'ffc66d','variable'=>'a9b7c6','operator'=>'a9b7c6','tab_active'=>'2b2b2b','tab_inactive'=>'3c3f41'],
];

// ════════════════════════════════════════════════════════════════════════════
// PROFILE CARD TEMPLATES  (900 × 500 default)
// ════════════════════════════════════════════════════════════════════════════

$PROFILE_SPECS = [
    // ── HORIZONTAL: avatar left, content right ────────────────────────────
    'team_member'   => ['layout'=>'horizontal','bg'=>'eef2ff','card_bg'=>'ffffff','card_border'=>'c7d2fe','accent'=>'4f46e5','name_color'=>'1e1b4b','role_color'=>'4338ca','stat_color'=>'4f46e5','stat_label_color'=>'818cf8','radius'=>16],
    'business'      => ['layout'=>'horizontal','bg'=>'0f2744','card_bg'=>'162c47','card_border'=>'c8a04a','accent'=>'c8a04a','name_color'=>'ffffff','role_color'=>'90a4b9','stat_color'=>'c8a04a','stat_label_color'=>'4a6a85','radius'=>4],
    'speaker'       => ['layout'=>'horizontal','bg'=>'1e1b4b','card_bg'=>'1e1b4b','card_border'=>'4338ca','accent'=>'818cf8','name_color'=>'e0e7ff','role_color'=>'a5b4fc','stat_color'=>'c7d2fe','stat_label_color'=>'6366f1','radius'=>16],
    // ── VERTICAL: avatar centered top, content centered below ─────────────
    'author'        => ['layout'=>'vertical','bg'=>'faf8f5','card_bg'=>'ffffff','card_border'=>'e8ddd0','accent'=>'c4600a','name_color'=>'1c0a00','role_color'=>'7c4a1e','stat_color'=>'c4600a','stat_label_color'=>'a0816b','radius'=>12],
    'creator'       => ['layout'=>'vertical','bg'=>'0f0f1a','card_bg'=>'1a1a2e','card_border'=>'2d2d4e','accent'=>'06b6d4','name_color'=>'ffffff','role_color'=>'94a3b8','stat_color'=>'06b6d4','stat_label_color'=>'475569','radius'=>12],
    'podcast_card'  => ['layout'=>'vertical','bg'=>'fde8d8','card_bg'=>'ffffff','card_border'=>'f9c299','accent'=>'ea580c','name_color'=>'431407','role_color'=>'92400e','stat_color'=>'ea580c','stat_label_color'=>'9a3412','radius'=>12],
    // ── SPLIT PANEL: left accent panel with avatar, content right ─────────
    'developer'     => ['layout'=>'split','bg'=>'0d1117','card_bg'=>'0d1117','card_border'=>'30363d','accent'=>'3fb950','name_color'=>'e6edf3','role_color'=>'8b949e','stat_color'=>'3fb950','stat_label_color'=>'484f58','panel_bg'=>'161b22','radius'=>6],
    'freelancer'    => ['layout'=>'split','bg'=>'fff7ed','card_bg'=>'ffffff','card_border'=>'fed7aa','accent'=>'f97316','name_color'=>'1c0a00','role_color'=>'78350f','stat_color'=>'f97316','stat_label_color'=>'a16207','panel_bg'=>'f97316','radius'=>8],
    'musician'      => ['layout'=>'split','bg'=>'0a0014','card_bg'=>'0a0014','card_border'=>'3d0071','accent'=>'a855f7','name_color'=>'ffffff','role_color'=>'c4b5fd','stat_color'=>'d8b4fe','stat_label_color'=>'7c3aed','panel_bg'=>'1e0033','radius'=>8],
    // ── GLASS: dark bg + glass card, centered avatar ──────────────────────
    'dark_glass'    => ['layout'=>'glass','bg'=>'030711','card_bg'=>'0f172a','card_border'=>'1e293b','accent'=>'38bdf8','name_color'=>'f0f9ff','role_color'=>'94a3b8','stat_color'=>'38bdf8','stat_label_color'=>'475569','radius'=>24],
    'noir'          => ['layout'=>'glass','bg'=>'000000','card_bg'=>'111111','card_border'=>'222222','accent'=>'ffffff','name_color'=>'ffffff','role_color'=>'888888','stat_color'=>'ffffff','stat_label_color'=>'444444','radius'=>0],
    'athlete'       => ['layout'=>'glass','bg'=>'0a1628','card_bg'=>'0f1f3d','card_border'=>'ef4444','accent'=>'ef4444','name_color'=>'ffffff','role_color'=>'93c5fd','stat_color'=>'ef4444','stat_label_color'=>'3b82f6','radius'=>8],
    // ── MINIMAL: clean flat card, bold left accent bar ────────────────────
    'minimal_white' => ['layout'=>'minimal','bg'=>'f0f2f5','card_bg'=>'ffffff','card_border'=>'e2e8f0','accent'=>'6366f1','name_color'=>'0f172a','role_color'=>'475569','stat_color'=>'6366f1','stat_label_color'=>'94a3b8','radius'=>8],
    'resume_clean'  => ['layout'=>'minimal','bg'=>'f1f5f9','card_bg'=>'ffffff','card_border'=>'cbd5e1','accent'=>'0284c7','name_color'=>'0c1a28','role_color'=>'334155','stat_color'=>'0284c7','stat_label_color'=>'64748b','radius'=>4],
    'gradient_card' => ['layout'=>'minimal','bg'=>'f0fdf4','card_bg'=>'ffffff','card_border'=>'bbf7d0','accent'=>'16a34a','name_color'=>'052e16','role_color'=>'166534','stat_color'=>'16a34a','stat_label_color'=>'4ade80','radius'=>12],
];

// ════════════════════════════════════════════════════════════════════════════
// BUSINESS CARD TEMPLATES  (1050 × 600 default)
// ════════════════════════════════════════════════════════════════════════════

$BIZCARD_SPECS = [
    'wave_dark'        => ['layout'=>'wave',     'bg'=>'1e2235','panel_bg'=>'e8eaf0','accent'=>'c8a04a','name_color'=>'ffffff','title_color'=>'8b98b5','contact_color'=>'c0c8da'],
    'corporate_stripe' => ['layout'=>'stripe',   'bg'=>'ffffff','panel_bg'=>'0f2744','accent'=>'c8a04a','name_color'=>'0f2744','title_color'=>'4a6a85','contact_color'=>'555555'],
    'minimal_biz'      => ['layout'=>'minimal',  'bg'=>'f8f9fa','panel_bg'=>'343a40','accent'=>'343a40','name_color'=>'212529','title_color'=>'495057','contact_color'=>'6c757d'],
    'creative_split'   => ['layout'=>'creative', 'bg'=>'6366f1','panel_bg'=>'ffffff','accent'=>'6366f1','name_color'=>'4338ca','title_color'=>'5b21b6','contact_color'=>'4b5563'],
    'tech_grid'        => ['layout'=>'tech',     'bg'=>'0a0e1a','panel_bg'=>'101520','accent'=>'00d4ff','name_color'=>'ffffff','title_color'=>'7aa5cc','contact_color'=>'5a8a9e'],
    'luxury_foil'      => ['layout'=>'luxury',   'bg'=>'0a0a0a','panel_bg'=>'111111','accent'=>'c9a84c','name_color'=>'ffffff','title_color'=>'c9a84c','contact_color'=>'888888'],
];

// ════════════════════════════════════════════════════════════════════════════
// ID CARD TEMPLATES  (600 × 900 default)
// ════════════════════════════════════════════════════════════════════════════

$IDCARD_SPECS = [
    'corporate_dark' => ['layout'=>'dark_id',   'bg'=>'1a1f2e','accent'=>'38bdf8','name_color'=>'ffffff','role_color'=>'94a3b8','company_color'=>'38bdf8','barcode_color'=>'38bdf8'],
    'corporate_red'  => ['layout'=>'red_side',  'bg'=>'ffffff','accent'=>'ef4444','name_color'=>'1a1a1a','role_color'=>'4b5563','company_color'=>'ef4444','barcode_color'=>'111111'],
    'student_teal'   => ['layout'=>'student',   'bg'=>'ffffff','accent'=>'0d9488','name_color'=>'1a1a1a','role_color'=>'374151','company_color'=>'ffffff','barcode_color'=>'000000','deco_color'=>'0e7490'],
    'minimal_badge'  => ['layout'=>'minimal',   'bg'=>'f8fafc','accent'=>'6366f1','name_color'=>'1e1b4b','role_color'=>'4338ca','company_color'=>'6366f1','barcode_color'=>'1e1b4b'],
    'access_badge'   => ['layout'=>'access',    'bg'=>'0f172a','accent'=>'f59e0b','name_color'=>'ffffff','role_color'=>'fbbf24','company_color'=>'f59e0b','barcode_color'=>'f59e0b'],
    'gov_blue'       => ['layout'=>'gov',       'bg'=>'0a2463','accent'=>'ffd700','name_color'=>'ffffff','role_color'=>'bfd3fe','company_color'=>'ffd700','barcode_color'=>'ffd700'],
];

// ════════════════════════════════════════════════════════════════════════════
// INVITATION TEMPLATES  (1200 × 800 default)
// ════════════════════════════════════════════════════════════════════════════

$INVITATION_SPECS = [
    'vintage_cream'   => ['layout'=>'vintage',   'bg'=>'e8e0d0','accent'=>'3d7a6a','title_color'=>'1e3d38','subtitle_color'=>'3d7a6a','body_color'=>'2a4040'],
    'luxury_dark'     => ['layout'=>'luxury',    'bg'=>'0a0a0a','accent'=>'c9a84c','title_color'=>'c9a84c','subtitle_color'=>'ffffff','body_color'=>'aaaaaa'],
    'birthday_fun'    => ['layout'=>'festive',   'bg'=>'fff8e1','accent'=>'f97316','title_color'=>'1c0a00','subtitle_color'=>'ea580c','body_color'=>'78350f'],
    'wedding_elegant' => ['layout'=>'elegant',   'bg'=>'fefefe','accent'=>'a3875a','title_color'=>'1c1917','subtitle_color'=>'44403c','body_color'=>'78716c'],
    'corporate_event' => ['layout'=>'corporate', 'bg'=>'0f2744','accent'=>'60a5fa','title_color'=>'ffffff','subtitle_color'=>'93c5fd','body_color'=>'64748b'],
    'garden_party'    => ['layout'=>'garden',    'bg'=>'f0fdf4','accent'=>'22c55e','title_color'=>'052e16','subtitle_color'=>'166534','body_color'=>'374151'],
];

// ════════════════════════════════════════════════════════════════════════════
// DASHBOARD PREVIEW TEMPLATES  (1200 × 630 default)
// ════════════════════════════════════════════════════════════════════════════

$DASHBOARD_SPECS = [
    'analytics' => ['bg'=>'0f172a','sidebar_bg'=>'1e293b','card_bg'=>'1e293b','card_border'=>'334155','txt'=>'f8fafc','muted'=>'94a3b8','accent'=>'3b82f6','positive'=>'22c55e','negative'=>'ef4444'],
    'saas'      => ['bg'=>'ffffff','sidebar_bg'=>'f8fafc','card_bg'=>'ffffff','card_border'=>'e2e8f0','txt'=>'0f172a','muted'=>'64748b','accent'=>'6366f1','positive'=>'10b981','negative'=>'f43f5e'],
    'stats'     => ['bg'=>'18181b','sidebar_bg'=>'27272a','card_bg'=>'27272a','card_border'=>'3f3f46','txt'=>'fafafa','muted'=>'a1a1aa','accent'=>'f59e0b','positive'=>'4ade80','negative'=>'f87171'],
    'kpi'       => ['bg'=>'030712','sidebar_bg'=>'111827','card_bg'=>'111827','card_border'=>'1f2937','txt'=>'f9fafb','muted'=>'6b7280','accent'=>'a855f7','positive'=>'34d399','negative'=>'fb7185'],
    'revenue'   => ['bg'=>'fff7ed','sidebar_bg'=>'fff7ed','card_bg'=>'ffffff','card_border'=>'fed7aa','txt'=>'1c1917','muted'=>'78716c','accent'=>'ea580c','positive'=>'059669','negative'=>'dc2626'],
    'admin'        => ['bg'=>'f1f5f9','sidebar_bg'=>'1e293b','card_bg'=>'ffffff','card_border'=>'e2e8f0','txt'=>'0f172a','muted'=>'64748b','accent'=>'0284c7','positive'=>'16a34a','negative'=>'dc2626'],
    // ── 9 new dashboard templates ────────────────────────────────
    'marketing'    => ['bg'=>'0d2137','sidebar_bg'=>'0a1a2e','card_bg'=>'0a1a2e','card_border'=>'1e3a5f','txt'=>'e2f0ff','muted'=>'7aa5cc','accent'=>'00d4ff','positive'=>'00ff88','negative'=>'ff4444'],
    'crypto'       => ['bg'=>'0a0e1a','sidebar_bg'=>'10152a','card_bg'=>'10152a','card_border'=>'1e2a45','txt'=>'e8eaf6','muted'=>'7986cb','accent'=>'f7b731','positive'=>'26de81','negative'=>'fc5c65'],
    'fitness'      => ['bg'=>'0d0d0d','sidebar_bg'=>'1a1a1a','card_bg'=>'1a1a1a','card_border'=>'2d2d2d','txt'=>'ffffff','muted'=>'888888','accent'=>'ff4500','positive'=>'ff6b35','negative'=>'e74c3c'],
    'ecommerce'    => ['bg'=>'fafafa','sidebar_bg'=>'ffffff','card_bg'=>'ffffff','card_border'=>'eeeeee','txt'=>'212121','muted'=>'757575','accent'=>'00897b','positive'=>'43a047','negative'=>'e53935'],
    'social_dash'  => ['bg'=>'f0e6ff','sidebar_bg'=>'e8d5ff','card_bg'=>'ffffff','card_border'=>'d4a6ff','txt'=>'1a0033','muted'=>'6b3399','accent'=>'9c27b0','positive'=>'4caf50','negative'=>'f44336'],
    'devops'       => ['bg'=>'0c1920','sidebar_bg'=>'0f2030','card_bg'=>'0f2030','card_border'=>'1a3a50','txt'=>'c8e6f0','muted'=>'5a8a9e','accent'=>'00bcd4','positive'=>'4caf50','negative'=>'f44336'],
    'project_dash' => ['bg'=>'f7f8fc','sidebar_bg'=>'2d3436','card_bg'=>'ffffff','card_border'=>'e4e7ef','txt'=>'2d3436','muted'=>'636e72','accent'=>'6c63ff','positive'=>'00b894','negative'=>'d63031'],
    'finance'      => ['bg'=>'001233','sidebar_bg'=>'001a4d','card_bg'=>'001a4d','card_border'=>'002a70','txt'=>'e8f0ff','muted'=>'7a9acc','accent'=>'c9a84c','positive'=>'2ecc71','negative'=>'e74c3c'],
    'monitoring'   => ['bg'=>'0e1117','sidebar_bg'=>'161b22','card_bg'=>'161b22','card_border'=>'30363d','txt'=>'c9d1d9','muted'=>'8b949e','accent'=>'f0883e','positive'=>'3fb950','negative'=>'f85149'],
];

// ════════════════════════════════════════════════════════════════════════════
// DOCS PREVIEW TEMPLATES  (1200 × 630 default)
// ════════════════════════════════════════════════════════════════════════════

$DOC_SPECS = [
    'api'       => ['bg'=>'ffffff','sidebar_bg'=>'18181b','card_bg'=>'f4f4f5','accent'=>'6366f1','txt'=>'09090b','muted'=>'71717a','method_get'=>'22c55e','method_post'=>'3b82f6','method_put'=>'f59e0b','method_del'=>'ef4444'],
    'readme'    => ['bg'=>'ffffff','sidebar_bg'=>'ffffff','card_bg'=>'f6f8fa','accent'=>'0969da','txt'=>'1f2328','muted'=>'656d76','method_get'=>'1a7f37','method_post'=>'0550ae','method_put'=>'9a6700','method_del'=>'cf222e'],
    'changelog' => ['bg'=>'0f172a','sidebar_bg'=>'0f172a','card_bg'=>'1e293b','accent'=>'a855f7','txt'=>'f8fafc','muted'=>'94a3b8','method_get'=>'4ade80','method_post'=>'60a5fa','method_put'=>'fbbf24','method_del'=>'f87171'],
    'product'   => ['bg'=>'fafafa','sidebar_bg'=>'f4f4f5','card_bg'=>'ffffff','accent'=>'0ea5e9','txt'=>'0a0a0a','muted'=>'737373','method_get'=>'16a34a','method_post'=>'2563eb','method_put'=>'d97706','method_del'=>'dc2626'],
    'developer' => ['bg'=>'1a1a2e','sidebar_bg'=>'16213e','card_bg'=>'0f3460','accent'=>'e94560','txt'=>'ffffff','muted'=>'a8b2d8','method_get'=>'4caf50','method_post'=>'2196f3','method_put'=>'ff9800','method_del'=>'f44336'],
    'knowledge'     => ['bg'=>'ffffff','sidebar_bg'=>'fafafa','card_bg'=>'f9f9f9','accent'=>'10b981','txt'=>'111827','muted'=>'6b7280','method_get'=>'059669','method_post'=>'2563eb','method_put'=>'d97706','method_del'=>'dc2626'],
    // ── 9 new documentation templates ───────────────────────────
    'tutorial'      => ['bg'=>'fff9f0','sidebar_bg'=>'1c1c1c','card_bg'=>'fff9f0','accent'=>'f59e0b','txt'=>'1c1c1c','muted'=>'6b5b45','method_get'=>'16a34a','method_post'=>'2563eb','method_put'=>'d97706','method_del'=>'dc2626'],
    'component_doc' => ['bg'=>'fafafa','sidebar_bg'=>'2d2d2d','card_bg'=>'ffffff','accent'=>'818cf8','txt'=>'111827','muted'=>'6b7280','method_get'=>'059669','method_post'=>'4f46e5','method_put'=>'d97706','method_del'=>'dc2626'],
    'library_pkg'   => ['bg'=>'0a0a0a','sidebar_bg'=>'141414','card_bg'=>'1a1a1a','accent'=>'c2410c','txt'=>'e5e5e5','muted'=>'737373','method_get'=>'4ade80','method_post'=>'60a5fa','method_put'=>'fbbf24','method_del'=>'f87171'],
    'cli_doc'       => ['bg'=>'0d1117','sidebar_bg'=>'0d1117','card_bg'=>'161b22','accent'=>'3fb950','txt'=>'c9d1d9','muted'=>'8b949e','method_get'=>'3fb950','method_post'=>'58a6ff','method_put'=>'ffa657','method_del'=>'ff7b72'],
    'guide_doc'     => ['bg'=>'fff7ed','sidebar_bg'=>'fff7ed','card_bg'=>'ffffff','accent'=>'ea580c','txt'=>'1c1917','muted'=>'78716c','method_get'=>'16a34a','method_post'=>'2563eb','method_put'=>'d97706','method_del'=>'dc2626'],
    'reference_doc' => ['bg'=>'ffffff','sidebar_bg'=>'f8fafc','card_bg'=>'ffffff','accent'=>'334155','txt'=>'0f172a','muted'=>'64748b','method_get'=>'059669','method_post'=>'3b82f6','method_put'=>'f59e0b','method_del'=>'ef4444'],
    'faq_doc'       => ['bg'=>'f0fdf4','sidebar_bg'=>'f0fdf4','card_bg'=>'ffffff','accent'=>'059669','txt'=>'052e16','muted'=>'166534','method_get'=>'16a34a','method_post'=>'2563eb','method_put'=>'d97706','method_del'=>'dc2626'],
    'notes_doc'     => ['bg'=>'fefce8','sidebar_bg'=>'fef9c3','card_bg'=>'fefce8','accent'=>'ca8a04','txt'=>'1a2000','muted'=>'713f12','method_get'=>'16a34a','method_post'=>'2563eb','method_put'=>'d97706','method_del'=>'dc2626'],
    'quickstart'    => ['bg'=>'f0fdf4','sidebar_bg'=>'166534','card_bg'=>'ffffff','accent'=>'16a34a','txt'=>'052e16','muted'=>'166534','method_get'=>'16a34a','method_post'=>'2563eb','method_put'=>'d97706','method_del'=>'dc2626'],
];

// ════════════════════════════════════════════════════════════════════════════
// GITHUB CARD TEMPLATES  (1200 × 630 default)
// ════════════════════════════════════════════════════════════════════════════

$GITHUB_SPECS = [
    'repo'          => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'58a6ff','desc_color'=>'8b949e','badge_bg'=>'21262d','badge_color'=>'3fb950','stat_color'=>'8b949e','accent'=>'3fb950','lang_color'=>'f1e05a','radius'=>6],
    'package'       => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'1a2f1a','badge_color'=>'3fb950','stat_color'=>'8b949e','accent'=>'3fb950','lang_color'=>'3178c6','radius'=>6],
    'release'       => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'1f3a1f','badge_color'=>'3fb950','stat_color'=>'8b949e','accent'=>'3fb950','lang_color'=>'f97316','radius'=>6],
    'open_source'   => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'1a1f2e','badge_color'=>'79c0ff','stat_color'=>'8b949e','accent'=>'79c0ff','lang_color'=>'e34c26','radius'=>6],
    'org'           => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'21262d','badge_color'=>'d2a8ff','stat_color'=>'8b949e','accent'=>'d2a8ff','lang_color'=>'d2a8ff','radius'=>6],
    'project'            => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'1a1a2e','badge_color'=>'818cf8','stat_color'=>'8b949e','accent'=>'818cf8','lang_color'=>'563d7c','radius'=>6],
    // ── 9 new GitHub templates ───────────────────────────────────
    'stars_showcase'     => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'f1e05a','desc_color'=>'8b949e','badge_bg'=>'2d2a00','badge_color'=>'f1e05a','stat_color'=>'f1e05a','accent'=>'f1e05a','lang_color'=>'f1e05a','radius'=>6],
    'npm_card'           => ['bg'=>'1a0000','card_bg'=>'2b0000','card_border'=>'4a0000','heading_color'=>'cb0000','desc_color'=>'cc4444','badge_bg'=>'2b0000','badge_color'=>'cb0000','stat_color'=>'aaaaaa','accent'=>'cb0000','lang_color'=>'cb0000','radius'=>4],
    'contribution_card'  => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'033a16','badge_color'=>'3fb950','stat_color'=>'39d353','accent'=>'39d353','lang_color'=>'39d353','radius'=>6],
    'profile_readme'     => ['bg'=>'ffffff','card_bg'=>'f6f8fa','card_border'=>'d0d7de','heading_color'=>'1f2328','desc_color'=>'656d76','badge_bg'=>'fff8c5','badge_color'=>'9a6700','stat_color'=>'24292f','accent'=>'0969da','lang_color'=>'f1e05a','radius'=>6],
    'docker_card'        => ['bg'=>'0b1d32','card_bg'=>'0a2c4e','card_border'=>'1967a4','heading_color'=>'ffffff','desc_color'=>'7ab7e8','badge_bg'=>'003f75','badge_color'=>'2496ed','stat_color'=>'7ab7e8','accent'=>'2496ed','lang_color'=>'2496ed','radius'=>8],
    'pr_card'            => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'1c2a1c','badge_color'=>'3fb950','stat_color'=>'8b949e','accent'=>'3fb950','lang_color'=>'a371f7','radius'=>6],
    'issue_card'         => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'1c2a00','badge_color'=>'57ab5a','stat_color'=>'8b949e','accent'=>'57ab5a','lang_color'=>'ff7b72','radius'=>6],
    'workflow_card'      => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'221a00','badge_color'=>'e3b341','stat_color'=>'8b949e','accent'=>'e3b341','lang_color'=>'f97316','radius'=>6],
    'monorepo'           => ['bg'=>'0d1117','card_bg'=>'161b22','card_border'=>'30363d','heading_color'=>'e6edf3','desc_color'=>'8b949e','badge_bg'=>'002030','badge_color'=>'79c0ff','stat_color'=>'8b949e','accent'=>'79c0ff','lang_color'=>'22d3ee','radius'=>6],
];

// ════════════════════════════════════════════════════════════════════════════
// CUSTOM TEMPLATES — your own rich, freeform designs (flex boxes, gradients,
// shadows, rounded corners, icons, multi-line text), built without touching
// GD code directly.
// ────────────────────────────────────────────────────────────────────────────
// HOW TO ADD ONE
// 1. Open tools/custom-template-builder.html (in this same folder) in any
//    browser — it's a standalone file, no server/install needed. Design your
//    card visually, then click "Export PHP".
// 2. Paste the exported `$CUSTOM_SPECS['your_id'] = [...]` block below, above
//    the closing `];` — or just before this comment if this is your first one.
// 3. Save. It appears automatically under a new "Custom Templates" category —
//    nothing else needs to change (same auto-registration as every other
//    category, see $CATEGORIES/$PT_REGISTRY below).
// ────────────────────────────────────────────────────────────────────────────
// SHAPE of one entry — see the builder's own on-page docs for full property
// reference; this is the short version:
//   $CUSTOM_SPECS['my_card'] = [
//       'name'       => 'My Card',      // shown in the UI
//       'default_w'  => 1200,
//       'default_h'  => 630,
//       'root'       => [ ... a node tree, see below ... ],
//   ];
// A node is one of:
//
//   ['type'=>'box',  'direction'=>'row'|'column', 'justify'=>.., 'align'=>..,
//    'gap'=>int, 'padding'=>[t,r,b,l]|int, 'width'=>'auto'|'fill'|'NN%'|int,
//    'height'=>same, 'background'=>['type'=>'solid','color'=>'#hex'] or
//    ['type'=>'gradient','angle'=>'vertical'|'horizontal'|'diagonal','from'=>'#hex','to'=>'#hex'],
//    'radius'=>int, 'border'=>['width'=>int,'color'=>'#hex'],
//    'shadow'=>['x'=>int,'y'=>int,'blur'=>int,'color'=>'#hex','opacity'=>0..1],
//    'opacity'=>0..1, 'rotation'=>0..359 (degrees, clockwise),
//    'clip'=>'circle'|'pill' (pixel-mask the box to that shape),
//    'bgPattern'=>['type'=>'grid'|'diagonal'|'dots','color'=>'#hex','opacity'=>0..1,
//                  'size'=>int,'width'=>int],
//    'position'=>'absolute', 'x'=>int, 'y'=>int  (absolute coords within parent)
//    'children'=>[ ...nodes... ]]
//
//   ['type'=>'text', 'content'=>'{{heading}}', 'font'=>'regular'|'bold',
//    'size'=>int, 'color'=>'#hex', 'align'=>'left'|'center'|'right',
//    'valign'=>'top'|'middle'|'bottom', 'lineHeight'=>float, 'maxLines'=>int,
//    'transform'=>'none'|'uppercase', 'width'=>.., 'height'=>..,
//    'rotation'=>0..359,
//    'shadow'=>['x'=>int,'y'=>int,'blur'=>int,'color'=>'#hex','opacity'=>0..1]]
//
//   ['type'=>'icon', 'icon'=>'star', 'size'=>int, 'color'=>'#hex',
//    'rotation'=>0..359]
//
//   ['type'=>'shape', 'shape'=>'rect'|'circle'|'pill'|'triangle'|'triangle-down'|
//    'diamond'|'hexagon'|'star'|'parallelogram'|'arrow-right'|'arrow-left'|'line'|'vline',
//    'width'=>int, 'height'=>int,
//    'fill'=>'#hex', 'opacity'=>0..1, 'rotation'=>0..359,
//    'stroke'=>['color'=>'#hex','width'=>int],
//    'gradient'=>['angle'=>'vertical'|'horizontal'|'diagonal','from'=>'#hex','to'=>'#hex']]
//
//   ['type'=>'divider', 'orientation'=>'horizontal'|'vertical',
//    'width'=>int (for horizontal: length; for vertical: thickness)|'fill',
//    'height'=>int (for vertical: length; for horizontal: thickness)|'fill',
//    'color'=>'#hex', 'opacity'=>0..1]
//
// `content` and any 'color' value may use {{placeholder}} tokens resolved
// from the same fields every other category uses: heading, subheading,
// description, badge, footer, website, author, date, username, role,
// category_label, icon, bg_color, fg_color, accent_color, watermark.
// ════════════════════════════════════════════════════════════════════════════
$CUSTOM_SPECS['platform_homepage'] = [
    'name' => 'Awan Tools - Homepage',
    'default_w' => 1200,
    'default_h' => 630,
    'root' => [
        'type' => 'box',
        'justify' => 'center',
        'gap' => 20,
        'padding' => 64,
        'background' => [
            'type' => 'solid',
            'color' => '#ffffff',
        ],
        'children' => [
            [
                'type' => 'box',
                'justify' => 'center',
                'align' => 'center',
                'gap' => 12,
                'width' => 'fill',
                'children' => [
                    [
                        'type' => 'box',
                        'justify' => 'center',
                        'align' => 'center',
                        'padding' => [
                            30,
                            30,
                            30,
                            30,
                        ],
                        'width' => 200,
                        'height' => 200,
                        'background' => [
                            'type' => 'solid',
                            'color' => '#ffffff',
                        ],
                        'radius' => 100,
                        'clip' => 'circle',
                        'children' => [
                            [
                                'type' => 'icon',
                                'icon' => 'tools',
                                'size' => 120,
                                'color' => '#0066ff',
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'content' => '{{heading}}',
                        'font' => 'bold',
                        'size' => 36,
                        'color' => '#0066ff',
                        'align' => 'center',
                        'valign' => 'middle',
                        'lineHeight' => 2,
                    ],
                    [
                        'type' => 'text',
                        'content' => '{{description}}',
                        'color' => '#8f8f8f',
                        'align' => 'center',
                        'valign' => 'middle',
                    ],
                    [
                        'type' => 'box',
                        'direction' => 'row',
                        'justify' => 'center',
                        'gap' => 30,
                        'padding' => [
                            3,
                            0,
                            33,
                            0,
                        ],
                        'children' => [
                            [
                                'type' => 'box',
                                'direction' => 'row',
                                'justify' => 'center',
                                'align' => 'center',
                                'gap' => 5,
                                'padding' => [
                                    10,
                                    20,
                                    10,
                                    20,
                                ],
                                'background' => [
                                    'type' => 'solid',
                                    'color' => '#0066ff',
                                ],
                                'radius' => 204,
                                'children' => [
                                    [
                                        'type' => 'icon',
                                        'icon' => 'tools',
                                        'size' => 26,
                                    ],
                                    [
                                        'type' => 'text',
                                        'content' => '{{badge}}+ Tools',
                                        'size' => 18,
                                    ],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'direction' => 'row',
                                'justify' => 'center',
                                'align' => 'center',
                                'gap' => 5,
                                'padding' => [
                                    10,
                                    20,
                                    10,
                                    20,
                                ],
                                'background' => [
                                    'type' => 'solid',
                                    'color' => '#0066ff',
                                ],
                                'radius' => 204,
                                'children' => [
                                    [
                                        'type' => 'icon',
                                        'icon' => 'user',
                                        'size' => 26,
                                    ],
                                    [
                                        'type' => 'text',
                                        'content' => 'No Logins',
                                        'size' => 18,
                                    ],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'direction' => 'row',
                                'justify' => 'center',
                                'align' => 'center',
                                'gap' => 5,
                                'padding' => [
                                    10,
                                    20,
                                    10,
                                    20,
                                ],
                                'background' => [
                                    'type' => 'solid',
                                    'color' => '#0066ff',
                                ],
                                'radius' => 204,
                                'children' => [
                                    [
                                        'type' => 'icon',
                                        'icon' => 'dollar-sign',
                                        'size' => 26,
                                    ],
                                    [
                                        'type' => 'text',
                                        'content' => '100% Free',
                                        'size' => 18,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'divider',
                        'color' => '#cfcfcf',
                        'thickness' => 3,
                    ],
                    [
                        'type' => 'box',
                        'direction' => 'row',
                        'justify' => 'between',
                        'align' => 'end',
                        'width' => 'fill',
                        'children' => [
                            [
                                'type' => 'box',
                                'direction' => 'row',
                                'justify' => 'center',
                                'align' => 'center',
                                'gap' => 7,
                                'children' => [
                                    [
                                        'type' => 'icon',
                                        'icon' => 'globe',
                                        'size' => 20,
                                        'color' => '#8f8f8f',
                                    ],
                                    [
                                        'type' => 'text',
                                        'content' => '{{website}}',
                                        'size' => 18,
                                        'color' => '#8f8f8f',
                                        'align' => 'center',
                                        'valign' => 'middle',
                                    ],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'direction' => 'row',
                                'justify' => 'center',
                                'align' => 'center',
                                'gap' => 7,
                                'children' => [
                                    [
                                        'type' => 'icon',
                                        'icon' => 'code',
                                        'size' => 20,
                                        'color' => '#8f8f8f',
                                    ],
                                    [
                                        'type' => 'text',
                                        'content' => '{{author}}',
                                        'size' => 18,
                                        'color' => '#8f8f8f',
                                        'align' => 'center',
                                        'valign' => 'middle',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
$CUSTOM_SPECS['my_card'] = [
    'name' => 'Blog',
    'default_w' => 1200,
    'default_h' => 630,
    'root' => [
        'type' => 'box',
        'justify' => 'center',
        'gap' => 20,
        'padding' => 64,
        'background' => [
            'type' => 'gradient',
            'angle' => 'vertical',
            'from' => '#759cff',
            'to' => '#0048f0',
        ],
        'children' => [
            [
                'type' => 'shape',
                'shape' => 'circle',
                'width' => 500,
                'height' => 500,
                'fill' => '#80b0ff',
                'opacity' => 0.3,
                'position' => 'absolute',
                'x' => 902,
                'y' => 352,
            ],
            [
                'type' => 'shape',
                'shape' => 'circle',
                'width' => 500,
                'height' => 500,
                'fill' => '#80b0ff',
                'opacity' => 0.3,
                'position' => 'absolute',
                'x' => -260,
                'y' => -310,
            ],
            [
                'type' => 'box',
                'direction' => 'row',
                'justify' => 'between',
                'align' => 'center',
                'gap' => 12,
                'width' => 'fill',
                'children' => [
                    [
                        'type' => 'box',
                        'direction' => 'row',
                        'justify' => 'center',
                        'align' => 'center',
                        'gap' => 20,
                        'children' => [
                            [
                                'type' => 'box',
                                'justify' => 'center',
                                'align' => 'center',
                                'padding' => [
                                    10,
                                    10,
                                    10,
                                    10,
                                ],
                                'width' => 100,
                                'height' => 100,
                                'background' => [
                                    'type' => 'solid',
                                    'color' => '#ffffff',
                                ],
                                'radius' => 10,
                                'children' => [
                                    [
                                        'type' => 'icon',
                                        'icon' => 'comment',
                                        'size' => 80,
                                        'color' => '#0066ff',
                                    ],
                                ],
                            ],
                            [
                                'type' => 'text',
                                'content' => '{{watermark}}',
                                'font' => 'bold',
                                'size' => 60,
                            ],
                        ],
                    ],
                    [
                        'type' => 'box',
                        'direction' => 'row',
                        'gap' => 20,
                        'padding' => [
                            10,
                            15,
                            10,
                            15,
                        ],
                        'background' => [
                            'type' => 'solid',
                            'color' => '#ffffff',
                        ],
                        'radius' => 50,
                        'children' => [
                            [
                                'type' => 'text',
                                'content' => '{{badge}}',
                                'size' => 18,
                                'color' => '#0066ff',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'divider',
                'color' => '#3d98ff',
                'thickness' => 10,
            ],
            [
                'type' => 'text',
                'content' => '{{heading}}',
                'font' => 'bold',
                'size' => 56,
                'color' => '#f8fafc',
                'lineHeight' => 1.15,
                'maxLines' => 2,
            ],
            [
                'type' => 'text',
                'content' => '{{description}}',
                'size' => 20,
                'color' => '#dedede',
                'lineHeight' => 1.4,
                'maxLines' => 8,
            ],
            [
                'type' => 'spacer',
            ],
            [
                'type' => 'box',
                'direction' => 'row',
                'justify' => 'between',
                'align' => 'center',
                'width' => 'fill',
                'children' => [
                    [
                        'type' => 'box',
                        'direction' => 'row',
                        'justify' => 'center',
                        'align' => 'center',
                        'gap' => 3,
                        'children' => [
                            [
                                'type' => 'icon',
                                'icon' => 'globe',
                                'size' => 26,
                            ],
                            [
                                'type' => 'text',
                                'content' => '{{website}}',
                                'size' => 18,
                            ],
                        ],
                    ],
                    [
                        'type' => 'box',
                        'direction' => 'row',
                        'justify' => 'center',
                        'align' => 'center',
                        'gap' => 3,
                        'children' => [
                            [
                                'type' => 'icon',
                                'icon' => 'code',
                                'size' => 26,
                            ],
                            [
                                'type' => 'text',
                                'content' => '{{author}}',
                                'size' => 18,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
$CUSTOM_SPECS['pugins'] = [
    'name' => 'Plugins',
    'default_w' => 1200,
    'default_h' => 630,
    'root' => [
        'type' => 'box',
        'justify' => 'center',
        'gap' => 20,
        'padding' => 64,
        'background' => [
            'type' => 'solid',
            'color' => '#020f22',
        ],
        'children' => [
            [
                'type' => 'box',
                'direction' => 'row',
                'justify' => 'center',
                'align' => 'center',
                'gap' => 20,
                'children' => [
                    [
                        'type' => 'box',
                        'padding' => [
                            10,
                            10,
                            10,
                            10,
                        ],
                        'background' => [
                            'type' => 'solid',
                            'color' => '#0066ff',
                        ],
                        'radius' => 20,
                        'border' => [
                            'width' => 1,
                            'color' => '#ffffff',
                        ],
                        'children' => [
                            [
                                'type' => 'icon',
                                'icon' => '{{icon}}',
                                'size' => 60,
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'content' => '{{heading}}',
                        'font' => 'bold',
                        'size' => 44,
                    ],
                ],
            ],
            [
                'type' => 'text',
                'content' => '{{description}}',
                'size' => 20,
                'color' => '#bfbfbf',
            ],
            [
                'type' => 'spacer',
            ],
            [
                'type' => 'box',
                'direction' => 'row',
                'justify' => 'center',
                'align' => 'center',
                'width' => 'fill',
                'children' => [
                    [
                        'type' => 'text',
                        'content' => '{{badge}}',
                        'size' => 18,
                        'color' => '#0066ff',
                        'align' => 'center',
                        'shadow' => [
                            'x' => 2,
                            'y' => 2,
                            'blur' => 4,
                            'color' => '#000000',
                            'shadow_opacity' => 0.5,
                        ],
                    ],
                ],
            ],
            [
                'type' => 'box',
                'direction' => 'row',
                'justify' => 'center',
                'align' => 'center',
                'gap' => 20,
                'width' => 'fill',
                'children' => [
                    [
                        'type' => 'box',
                        'direction' => 'row',
                        'justify' => 'center',
                        'align' => 'center',
                        'gap' => 2,
                        'padding' => [
                            5,
                            10,
                            5,
                            10,
                        ],
                        'background' => [
                            'type' => 'solid',
                            'color' => '#0066ff',
                        ],
                        'radius' => 20,
                        'children' => [
                            [
                                'type' => 'icon',
                                'icon' => 'dollar-sign',
                                'size' => 22,
                            ],
                            [
                                'type' => 'text',
                                'content' => 'Free',
                                'size' => 18,
                            ],
                        ],
                    ],
                    [
                        'type' => 'box',
                        'direction' => 'row',
                        'justify' => 'center',
                        'align' => 'center',
                        'gap' => 2,
                        'padding' => [
                            5,
                            10,
                            5,
                            10,
                        ],
                        'background' => [
                            'type' => 'solid',
                            'color' => '#0066ff',
                        ],
                        'radius' => 20,
                        'children' => [
                            [
                                'type' => 'icon',
                                'icon' => 'bolt',
                                'size' => 22,
                            ],
                            [
                                'type' => 'text',
                                'content' => 'Fast',
                                'size' => 18,
                            ],
                        ],
                    ],
                    [
                        'type' => 'box',
                        'direction' => 'row',
                        'justify' => 'center',
                        'align' => 'center',
                        'gap' => 2,
                        'padding' => [
                            5,
                            10,
                            5,
                            10,
                        ],
                        'background' => [
                            'type' => 'solid',
                            'color' => '#0066ff',
                        ],
                        'radius' => 20,
                        'children' => [
                            [
                                'type' => 'icon',
                                'icon' => 'user-lock',
                                'size' => 22,
                            ],
                            [
                                'type' => 'text',
                                'content' => 'Private',
                                'size' => 18,
                            ],
                        ],
                    ],
                    [
                        'type' => 'box',
                        'direction' => 'row',
                        'justify' => 'center',
                        'align' => 'center',
                        'gap' => 2,
                        'padding' => [
                            5,
                            10,
                            5,
                            10,
                        ],
                        'background' => [
                            'type' => 'solid',
                            'color' => '#0066ff',
                        ],
                        'radius' => 20,
                        'children' => [
                            [
                                'type' => 'icon',
                                'icon' => 'gauge',
                                'size' => 22,
                            ],
                            [
                                'type' => 'text',
                                'content' => 'Accurate',
                                'size' => 18,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'box',
                'align' => 'center',
                'width' => 'fill',
                'children' => [
                    [
                        'type' => 'box',
                        'padding' => [
                            10,
                            20,
                            10,
                            20,
                        ],
                        'background' => [
                            'type' => 'solid',
                            'color' => '#0066ff',
                        ],
                        'radius' => 100,
                        'children' => [
                            [
                                'type' => 'text',
                                'content' => '{{metric1}} Login Required',
                                'color' => '#1c2739',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'box',
                'direction' => 'row',
                'justify' => 'between',
                'align' => 'end',
                'width' => 'fill',
                'children' => [
                    [
                        'type' => 'shape',
                        'shape' => 'circle',
                        'width' => 1000,
                        'height' => 1000,
                        'fill' => '#ffffff',
                        'opacity' => 0.1,
                        'position' => 'absolute',
                        'x' => 35,
                        'y' => -200,
                    ],
                    [
                        'type' => 'box',
                        'direction' => 'row',
                        'justify' => 'center',
                        'align' => 'center',
                        'gap' => 10,
                        'children' => [
                            [
                                'type' => 'icon',
                                'icon' => 'globe',
                                'size' => 26,
                            ],
                            [
                                'type' => 'text',
                                'content' => '{{website}}',
                                'size' => 18,
                            ],
                        ],
                    ],
                    [
                        'type' => 'box',
                        'direction' => 'row',
                        'justify' => 'center',
                        'align' => 'center',
                        'gap' => 10,
                        'children' => [
                            [
                                'type' => 'icon',
                                'icon' => 'code',
                                'size' => 26,
                            ],
                            [
                                'type' => 'text',
                                'content' => '{{author}}',
                                'size' => 18,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];

// ════════════════════════════════════════════════════════════════════════════
// CATEGORY REGISTRY — the single list that drives the sidebar (index.php),
// the template picker (previewer.js, via $PT_REGISTRY as JSON), and template
// validation (render.php). Add a new category by adding one entry here (see
// "HOW TO ADD A BRAND NEW CATEGORY" above) — nothing else duplicates this list.
// ════════════════════════════════════════════════════════════════════════════

$CATEGORIES = [
    ['id'=>'og',            'icon'=>'image',              'label'=>'OG Images',         'templates'=>array_keys($OG_SPECS),         'default_w'=>1200,'default_h'=>630],
    ['id'=>'social',        'icon'=>'share-nodes',        'label'=>'Social Cards',      'templates'=>array_keys($SOCIAL_SPECS),     'default_w'=>1200,'default_h'=>630],
    ['id'=>'placeholder',   'icon'=>'fill',               'label'=>'Placeholders',      'templates'=>$PLACEHOLDER_TEMPLATES,        'default_w'=>800, 'default_h'=>600],
    ['id'=>'browser',       'icon'=>'globe',              'label'=>'Browser Mockups',   'templates'=>array_keys($BROWSER_STYLES),   'default_w'=>1200,'default_h'=>800],
    ['id'=>'terminal',      'icon'=>'terminal',           'label'=>'Terminal Previews', 'templates'=>array_keys($TERMINAL_THEMES),  'default_w'=>900, 'default_h'=>600],
    ['id'=>'profile',       'icon'=>'id-card',            'label'=>'Profile Cards',     'templates'=>array_keys($PROFILE_SPECS),    'default_w'=>900, 'default_h'=>500],
    ['id'=>'code',          'icon'=>'file-code',          'label'=>'Code Snippets',     'templates'=>array_keys($CODE_THEMES),      'default_w'=>1000,'default_h'=>600],
    ['id'=>'dashboard',     'icon'=>'chart-bar',          'label'=>'Dashboards',        'templates'=>array_keys($DASHBOARD_SPECS),  'default_w'=>1200,'default_h'=>630],
    ['id'=>'docs',          'icon'=>'book',               'label'=>'Docs Previews',     'templates'=>array_keys($DOC_SPECS),        'default_w'=>1200,'default_h'=>630],
    ['id'=>'github',        'icon'=>'code-branch',        'label'=>'GitHub Cards',      'templates'=>array_keys($GITHUB_SPECS),     'default_w'=>1200,'default_h'=>630],
    ['id'=>'business_card', 'icon'=>'credit-card',        'label'=>'Business Cards',    'templates'=>array_keys($BIZCARD_SPECS),    'default_w'=>1050,'default_h'=>600],
    ['id'=>'id_card',       'icon'=>'id-card-alt',        'label'=>'ID Cards',          'templates'=>array_keys($IDCARD_SPECS),     'default_w'=>600, 'default_h'=>900],
    ['id'=>'invitation',    'icon'=>'envelope-open-text', 'label'=>'Invitations',       'templates'=>array_keys($INVITATION_SPECS), 'default_w'=>1200,'default_h'=>800],
];

// Custom Templates only shows up once you've added at least one — an empty
// category would just be a dead end in the sidebar.
if (!empty($CUSTOM_SPECS)) {
    // Every custom template can have its own canvas size, so default_w/h here
    // is just what a brand-new pick defaults to (the first one's size).
    $firstCustom = reset($CUSTOM_SPECS);
    $CATEGORIES[] = [
        'id' => 'custom', 'icon' => 'wand-magic-sparkles', 'label' => 'Custom Templates',
        'templates' => array_keys($CUSTOM_SPECS),
        'default_w' => $firstCustom['default_w'] ?? 1200,
        'default_h' => $firstCustom['default_h'] ?? 630,
    ];
}

// $PT_REGISTRY — same shape render.php has always used for template
// validation/dispatch: category id => ['templates' => [...], 'default_w' =>
// int, 'default_h' => int]. Derived from $CATEGORIES so it never drifts.
$PT_REGISTRY = [];
foreach ($CATEGORIES as $c) {
    $PT_REGISTRY[$c['id']] = [
        'templates'  => $c['templates'],
        'default_w'  => $c['default_w'],
        'default_h'  => $c['default_h'],
    ];
}
