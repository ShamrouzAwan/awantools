<?php
defined('AWAN') or die('Direct access denied.');

/**
 * PT_Icons — inline SVG icon renderer.
 *
 * All icons are drawn with SVG primitives (line, rect, circle, polyline, polygon, path)
 * in a 100×100 viewBox. No external fonts required — renders perfectly in every SVG
 * context including <img> tags and PNG/JPG exports.
 *
 * Template variables:
 *   $C  → stroke / fill colour  (substituted at render time)
 *   $W  → stroke-width           (substituted at render time, scales with icon size)
 *
 * HOW TO ADD AN ICON
 * ------------------
 * 1. Add a key → SVG-string entry to the ICONS constant below.
 * 2. Use only: line, rect, circle, ellipse, polyline, polygon, path
 * 3. Design in a 100×100 coordinate space; leave ~8px padding on all sides.
 * 4. Use $C for colour and $W for stroke-width.
 * 5. Optionally add alias keys that point to the same string (see ALIASES below).
 */
class PT_Icons
{
    // ── Stroke-based icons (100 × 100 viewBox) ──────────────────────────────

    const ICONS = [

        // ── Development ──────────────────────────────────────────────────────
        'code' =>
            '<polyline points="33,22 8,50 33,78" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>' .
            '<polyline points="67,22 92,50 67,78" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>' .
            '<line x1="60" y1="18" x2="40" y2="82" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'terminal' =>
            '<polyline points="12,34 42,50 12,66" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>' .
            '<line x1="47" y1="66" x2="88" y2="66" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'database' =>
            '<ellipse cx="50" cy="22" rx="35" ry="12" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="15" y1="22" x2="15" y2="78" stroke="$C" stroke-width="$W"/>' .
            '<line x1="85" y1="22" x2="85" y2="78" stroke="$C" stroke-width="$W"/>' .
            '<ellipse cx="50" cy="50" rx="35" ry="12" fill="none" stroke="$C" stroke-width="$W" opacity="0.45"/>' .
            '<ellipse cx="50" cy="78" rx="35" ry="12" fill="none" stroke="$C" stroke-width="$W"/>',

        'server' =>
            '<rect x="10" y="18" width="80" height="22" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="10" y="56" width="80" height="22" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="75" cy="29" r="5" fill="$C"/>' .
            '<circle cx="62" cy="29" r="5" fill="$C" opacity="0.5"/>' .
            '<circle cx="75" cy="67" r="5" fill="$C"/>' .
            '<circle cx="62" cy="67" r="5" fill="$C" opacity="0.5"/>',

        'bug' =>
            '<circle cx="50" cy="57" r="22" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M32,42 C28,32 36,18 50,18 C64,18 72,32 68,42" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="35" x2="50" y2="79" stroke="$C" stroke-width="$W"/>' .
            '<line x1="28" y1="55" x2="14" y2="48" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="28" y1="65" x2="14" y2="72" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="72" y1="55" x2="86" y2="48" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="72" y1="65" x2="86" y2="72" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'file-code' =>
            '<path d="M22,10 L22,90 L78,90 L78,36 L52,10 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="52" y1="10" x2="52" y2="36" stroke="$C" stroke-width="$W"/>' .
            '<line x1="52" y1="36" x2="78" y2="36" stroke="$C" stroke-width="$W"/>' .
            '<polyline points="36,56 26,64 36,72" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>' .
            '<polyline points="52,56 62,64 52,72" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'atom' =>
            '<circle cx="50" cy="50" r="8" fill="$C"/>' .
            '<ellipse cx="50" cy="50" rx="42" ry="16" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<ellipse cx="50" cy="50" rx="42" ry="16" fill="none" stroke="$C" stroke-width="$W" transform="rotate(60,50,50)"/>' .
            '<ellipse cx="50" cy="50" rx="42" ry="16" fill="none" stroke="$C" stroke-width="$W" transform="rotate(-60,50,50)"/>',

        'microchip' =>
            '<rect x="30" y="30" width="40" height="40" rx="3" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="37" y1="30" x2="37" y2="18" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="30" x2="50" y2="18" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="63" y1="30" x2="63" y2="18" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="37" y1="70" x2="37" y2="82" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="70" x2="50" y2="82" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="63" y1="70" x2="63" y2="82" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="30" y1="40" x2="18" y2="40" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="30" y1="50" x2="18" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="30" y1="60" x2="18" y2="60" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="70" y1="40" x2="82" y2="40" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="70" y1="50" x2="82" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="70" y1="60" x2="82" y2="60" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'robot' =>
            '<rect x="24" y="26" width="52" height="44" rx="8" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="36" y="38" width="11" height="11" rx="2" fill="$C"/>' .
            '<rect x="53" y="38" width="11" height="11" rx="2" fill="$C"/>' .
            '<line x1="38" y1="58" x2="62" y2="58" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="26" x2="50" y2="14" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<circle cx="50" cy="12" r="4" fill="$C"/>' .
            '<line x1="24" y1="50" x2="12" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="76" y1="50" x2="88" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="36" y1="70" x2="36" y2="86" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="64" y1="70" x2="64" y2="86" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'network-wired' =>
            '<rect x="34" y="10" width="32" height="18" rx="3" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="50" y1="28" x2="50" y2="42" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="18" y1="42" x2="82" y2="42" stroke="$C" stroke-width="$W"/>' .
            '<line x1="18" y1="42" x2="18" y2="54" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="42" x2="50" y2="54" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="82" y1="42" x2="82" y2="54" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<rect x="6" y="54" width="24" height="16" rx="2" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="38" y="54" width="24" height="16" rx="2" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="70" y="54" width="24" height="16" rx="2" fill="none" stroke="$C" stroke-width="$W"/>',

        'sitemap' =>
            '<rect x="34" y="10" width="32" height="18" rx="2" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="50" y1="28" x2="50" y2="44" stroke="$C" stroke-width="$W"/>' .
            '<line x1="18" y1="44" x2="82" y2="44" stroke="$C" stroke-width="$W"/>' .
            '<line x1="18" y1="44" x2="18" y2="54" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="44" x2="50" y2="54" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="82" y1="44" x2="82" y2="54" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<rect x="4" y="54" width="28" height="16" rx="2" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="36" y="54" width="28" height="16" rx="2" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="68" y="54" width="28" height="16" rx="2" fill="none" stroke="$C" stroke-width="$W"/>',

        'layer-group' =>
            '<polygon points="50,12 90,30 50,48 10,30 50,12" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="10" y1="50" x2="50" y2="68" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="90" y1="50" x2="50" y2="68" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="10" y1="68" x2="50" y2="86" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="90" y1="68" x2="50" y2="86" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        // ── Interface / UI ────────────────────────────────────────────────────
        'globe' =>
            '<circle cx="50" cy="50" r="38" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<ellipse cx="50" cy="50" rx="20" ry="38" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="12" y1="50" x2="88" y2="50" stroke="$C" stroke-width="$W"/>' .
            '<path d="M22,31 Q50,26 78,31" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M22,69 Q50,74 78,69" fill="none" stroke="$C" stroke-width="$W"/>',

        'search' =>
            '<circle cx="42" cy="42" r="27" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="61" y1="61" x2="88" y2="88" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'link' =>
            '<path d="M40,30 L25,30 A16,16 0 0 0 25,62 L40,62" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M60,30 L75,30 A16,16 0 0 1 75,62 L60,62" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="37" y1="46" x2="63" y2="46" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'external-link' =>
            '<path d="M46,20 L18,20 L18,82 L82,82 L82,54" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>' .
            '<line x1="50" y1="50" x2="88" y2="12" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<polyline points="62,12 88,12 88,38" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'filter' =>
            '<polygon points="10,18 90,18 62,50 62,82 38,74 38,50 10,18" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'sliders' =>
            '<line x1="10" y1="26" x2="90" y2="26" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="10" y1="50" x2="90" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="10" y1="74" x2="90" y2="74" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<rect x="22" y="19" width="16" height="14" rx="7" fill="$C"/>' .
            '<rect x="62" y="43" width="16" height="14" rx="7" fill="$C"/>' .
            '<rect x="36" y="67" width="16" height="14" rx="7" fill="$C"/>',

        'grid' =>
            '<rect x="10" y="10" width="32" height="32" rx="3" fill="$C"/>' .
            '<rect x="58" y="10" width="32" height="32" rx="3" fill="$C"/>' .
            '<rect x="10" y="58" width="32" height="32" rx="3" fill="$C"/>' .
            '<rect x="58" y="58" width="32" height="32" rx="3" fill="$C"/>',

        'list' =>
            '<line x1="14" y1="28" x2="86" y2="28" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="14" y1="50" x2="86" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="14" y1="72" x2="86" y2="72" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'bars' =>
            '<line x1="10" y1="28" x2="90" y2="28" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="10" y1="50" x2="90" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="10" y1="72" x2="90" y2="72" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'palette' =>
            '<circle cx="50" cy="50" r="36" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="50" cy="20" r="6" fill="$C"/>' .
            '<circle cx="74" cy="34" r="6" fill="$C"/>' .
            '<circle cx="74" cy="66" r="6" fill="$C"/>' .
            '<circle cx="50" cy="80" r="6" fill="$C"/>' .
            '<circle cx="26" cy="66" r="6" fill="$C"/>' .
            '<circle cx="26" cy="34" r="6" fill="$C"/>',

        'paper-plane' =>
            '<polygon points="10,12 92,50 10,88 26,50 10,12" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="26" y1="50" x2="92" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'comment' =>
            '<path d="M10,18 L90,18 L90,68 L60,68 L50,82 L40,68 L10,68 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'comments' =>
            '<path d="M8,14 L76,14 L76,54 L52,54 L44,68 L36,54 L8,54 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<path d="M76,36 L92,36 L92,72 L76,72 L76,86 L64,72 L52,72" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'rss' =>
            '<path d="M14,86 A72,72 0 0 1 86,14" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M14,60 A46,46 0 0 1 60,14" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M14,34 A22,22 0 0 1 36,14" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<circle cx="18" cy="82" r="8" fill="$C"/>',

        'plug' =>
            '<line x1="38" y1="46" x2="38" y2="20" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="62" y1="46" x2="62" y2="20" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M24,46 L24,62 A26,26 0 0 0 76,62 L76,46 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="50" y1="76" x2="50" y2="90" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        // ── Icons / Symbols ───────────────────────────────────────────────────
        'check' =>
            '<polyline points="12,50 38,76 88,24" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'circle-check' =>
            '<circle cx="50" cy="50" r="38" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<polyline points="28,50 44,66 72,34" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'times' =>
            '<line x1="18" y1="18" x2="82" y2="82" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="82" y1="18" x2="18" y2="82" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'circle-xmark' =>
            '<circle cx="50" cy="50" r="38" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="32" y1="32" x2="68" y2="68" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="68" y1="32" x2="32" y2="68" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'circle-info' =>
            '<circle cx="50" cy="50" r="38" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="50" y1="44" x2="50" y2="72" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<circle cx="50" cy="30" r="4" fill="$C"/>',

        'circle-exclamation' =>
            '<circle cx="50" cy="50" r="38" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="50" y1="26" x2="50" y2="58" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<circle cx="50" cy="70" r="4" fill="$C"/>',

        'plus' =>
            '<line x1="50" y1="10" x2="50" y2="90" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="10" y1="50" x2="90" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'minus' =>
            '<line x1="10" y1="50" x2="90" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'arrow-right' =>
            '<line x1="12" y1="50" x2="88" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<polyline points="62,24 88,50 62,76" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'arrow-left' =>
            '<line x1="88" y1="50" x2="12" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<polyline points="38,24 12,50 38,76" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'arrow-up' =>
            '<line x1="50" y1="88" x2="50" y2="12" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<polyline points="24,38 50,12 76,38" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'arrow-down' =>
            '<line x1="50" y1="12" x2="50" y2="88" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<polyline points="24,62 50,88 76,62" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'chevron-right' =>
            '<polyline points="28,16 72,50 28,84" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'chevron-left' =>
            '<polyline points="72,16 28,50 72,84" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'chevron-down' =>
            '<polyline points="16,28 50,72 84,28" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'chevron-up' =>
            '<polyline points="16,72 50,28 84,72" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        // ── People / Social ───────────────────────────────────────────────────
        'user' =>
            '<circle cx="50" cy="35" r="20" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M14,90 C14,65 86,65 86,90" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'users' =>
            '<circle cx="36" cy="36" r="16" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M8,90 C8,68 64,68 64,90" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<circle cx="68" cy="34" r="13" fill="none" stroke="$C" stroke-width="$W" opacity="0.65"/>' .
            '<path d="M55,90 C55,72 93,72 93,90" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" opacity="0.65"/>',

        'envelope' =>
            '<rect x="10" y="22" width="80" height="56" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<polyline points="10,22 50,52 90,22" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'phone' =>
            '<rect x="28" y="10" width="44" height="80" rx="8" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="40" y1="18" x2="60" y2="18" stroke="$C" stroke-width="$W" stroke-linecap="round" opacity="0.5"/>' .
            '<circle cx="50" cy="78" r="5" fill="$C"/>',

        'id-card' =>
            '<rect x="8" y="22" width="84" height="56" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="30" cy="44" r="10" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M14,66 C14,56 46,56 46,66" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="54" y1="40" x2="82" y2="40" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="54" y1="54" x2="78" y2="54" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'address-book' =>
            '<rect x="18" y="10" width="68" height="80" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="52" cy="38" r="12" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M30,68 C30,56 74,56 74,68" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="10" y1="30" x2="18" y2="30" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="10" y1="50" x2="18" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        // ── Home / Buildings ──────────────────────────────────────────────────
        'home' =>
            '<polygon points="50,12 90,50 80,50 80,88 60,88 60,64 40,64 40,88 20,88 20,50 10,50 50,12" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'building' =>
            '<rect x="16" y="16" width="68" height="74" rx="2" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="26" y="28" width="12" height="12" rx="1" fill="$C"/>' .
            '<rect x="62" y="28" width="12" height="12" rx="1" fill="$C"/>' .
            '<rect x="26" y="50" width="12" height="12" rx="1" fill="$C"/>' .
            '<rect x="62" y="50" width="12" height="12" rx="1" fill="$C"/>' .
            '<rect x="38" y="68" width="24" height="22" rx="2" fill="none" stroke="$C" stroke-width="$W"/>',

        'briefcase' =>
            '<rect x="14" y="36" width="72" height="52" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M34,36 L34,24 C34,20 38,18 42,18 L58,18 C62,18 66,20 66,24 L66,36" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="14" y1="56" x2="86" y2="56" stroke="$C" stroke-width="$W"/>',

        // ── Controls / Media ──────────────────────────────────────────────────
        'bell' =>
            '<path d="M50,14 C30,14 20,30 20,50 L15,76 L85,76 L80,50 C80,30 70,14 50,14 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<path d="M42,76 Q50,88 58,76" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'clock' =>
            '<circle cx="50" cy="50" r="38" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="50" y1="50" x2="50" y2="20" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="50" x2="72" y2="62" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<circle cx="50" cy="50" r="3" fill="$C"/>',

        'calendar' =>
            '<rect x="14" y="22" width="72" height="66" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="14" y1="46" x2="86" y2="46" stroke="$C" stroke-width="$W"/>' .
            '<line x1="34" y1="14" x2="34" y2="34" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="66" y1="14" x2="66" y2="34" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<rect x="28" y="56" width="10" height="10" rx="2" fill="$C"/>' .
            '<rect x="45" y="56" width="10" height="10" rx="2" fill="$C"/>' .
            '<rect x="62" y="56" width="10" height="10" rx="2" fill="$C"/>' .
            '<rect x="28" y="72" width="10" height="10" rx="2" fill="$C"/>' .
            '<rect x="45" y="72" width="10" height="10" rx="2" fill="$C"/>',

        'bookmark' =>
            '<path d="M22,10 L78,10 L78,90 L50,70 L22,90 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'tag' =>
            '<path d="M14,14 L50,14 L88,50 L50,88 L14,52 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<circle cx="32" cy="32" r="6" fill="$C"/>',

        // ── Stars / Awards ────────────────────────────────────────────────────
        'star' =>
            '<polygon points="50,10 62,36 90,38 70,57 76,85 50,72 24,85 30,57 10,38 38,36" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'heart' =>
            '<path d="M50,82 C10,58 10,20 50,36 C90,20 90,58 50,82 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'crown' =>
            '<polygon points="10,80 22,40 50,60 78,40 90,80" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="10" y1="80" x2="90" y2="80" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<circle cx="22" cy="36" r="5" fill="$C"/>' .
            '<circle cx="78" cy="36" r="5" fill="$C"/>' .
            '<circle cx="50" cy="56" r="5" fill="$C"/>',

        'trophy' =>
            '<path d="M20,10 L80,10 L80,46 C80,62 66,74 50,74 C34,74 20,62 20,46 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<path d="M20,10 C18,10 10,10 10,26 C10,42 20,42 20,42" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M80,10 C82,10 90,10 90,26 C90,42 80,42 80,42" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="50" y1="74" x2="50" y2="84" stroke="$C" stroke-width="$W"/>' .
            '<line x1="30" y1="90" x2="70" y2="90" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'gem' =>
            '<polygon points="50,10 82,36 70,82 30,82 18,36 50,10" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="18" y1="36" x2="82" y2="36" stroke="$C" stroke-width="$W"/>',

        'award' =>
            '<circle cx="50" cy="40" r="28" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<polygon points="50,58 44,80 38,93 50,84 62,93 56,80 50,58" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        // ── Actions ───────────────────────────────────────────────────────────
        'bolt' =>
            '<polygon points="60,10 25,55 50,55 40,90 75,45 50,45" fill="$C"/>',

        'rocket' =>
            '<path d="M50,12 C60,12 78,30 78,56 L62,70 L38,70 L22,56 C22,30 40,12 50,12 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<circle cx="50" cy="42" r="9" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M38,70 L28,88 L50,78 L72,88 L62,70" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'fire' =>
            '<path d="M50,90 C28,90 16,74 16,58 C16,44 24,36 34,28 C30,40 36,50 44,46 C36,36 40,22 50,14 C48,28 54,36 60,32 C64,44 70,52 66,66 C74,60 76,50 74,42 C82,50 82,68 76,78 C70,88 60,90 50,90 Z" fill="$C"/>',

        'download' =>
            '<line x1="50" y1="12" x2="50" y2="68" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<polyline points="28,50 50,72 72,50" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>' .
            '<line x1="14" y1="84" x2="86" y2="84" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'upload' =>
            '<line x1="50" y1="72" x2="50" y2="16" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<polyline points="28,34 50,12 72,34" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>' .
            '<line x1="14" y1="84" x2="86" y2="84" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'share' =>
            '<circle cx="76" cy="22" r="10" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="76" cy="78" r="10" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="24" cy="50" r="10" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="34" y1="44" x2="66" y2="28" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="34" y1="56" x2="66" y2="72" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'trash' =>
            '<rect x="20" y="30" width="60" height="58" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="12" y1="30" x2="88" y2="30" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M38,18 C38,15 62,15 62,18 L64,30 L36,30 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="36" y1="48" x2="36" y2="74" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="48" x2="50" y2="74" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="64" y1="48" x2="64" y2="74" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'pen' =>
            '<path d="M72,14 C78,14 88,24 86,30 L30,84 L10,90 L16,70 L72,14 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="60" y1="26" x2="74" y2="40" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'copy' =>
            '<rect x="28" y="28" width="54" height="62" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M28,28 L28,18 C28,14 32,10 36,10 L82,10 L82,64" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'save' =>
            '<path d="M14,14 L14,86 L86,86 L86,34 L66,14 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<rect x="30" y="14" width="36" height="28" rx="2" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="26" y="54" width="48" height="32" rx="2" fill="none" stroke="$C" stroke-width="$W"/>',

        'print' =>
            '<rect x="20" y="38" width="60" height="38" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M30,38 L30,14 L70,14 L70,38" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<rect x="30" y="60" width="40" height="22" rx="2" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="66" cy="49" r="4" fill="$C"/>',

        // ── Security ──────────────────────────────────────────────────────────
        'shield' =>
            '<path d="M50,10 L85,25 L85,56 C85,72 70,83 50,92 C30,83 15,72 15,56 L15,25 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'lock' =>
            '<rect x="22" y="46" width="56" height="44" rx="6" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M34,46 L34,30 C34,16 66,16 66,30 L66,46" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<circle cx="50" cy="66" r="6" fill="$C"/>' .
            '<line x1="50" y1="66" x2="50" y2="76" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'lock-open' =>
            '<rect x="22" y="46" width="56" height="44" rx="6" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M34,46 L34,30 C34,16 66,16 66,30 L76,30" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<circle cx="50" cy="66" r="6" fill="$C"/>',

        'key' =>
            '<circle cx="32" cy="40" r="20" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="46" y1="54" x2="88" y2="86" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="70" y1="76" x2="70" y2="88" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="82" y1="83" x2="82" y2="95" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'fingerprint' =>
            '<path d="M50,90 C24,86 14,70 14,50 C14,28 30,14 50,14 C70,14 86,28 86,50" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M50,78 C34,74 28,64 28,50 C28,36 38,28 50,28 C62,28 72,36 72,50 C72,60 66,68 56,72" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M50,66 C42,62 42,56 50,54 C58,54 56,62 52,68" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'eye' =>
            '<path d="M8,50 C20,28 34,22 50,22 C66,22 80,28 92,50 C80,72 66,78 50,78 C34,78 20,72 8,50 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<circle cx="50" cy="50" r="14" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="50" cy="50" r="6" fill="$C"/>',

        // ── Nature / Misc ─────────────────────────────────────────────────────
        'sun' =>
            '<circle cx="50" cy="50" r="18" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="50" y1="10" x2="50" y2="22" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="78" x2="50" y2="90" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="10" y1="50" x2="22" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="78" y1="50" x2="90" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="22" y1="22" x2="30" y2="30" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="70" y1="70" x2="78" y2="78" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="78" y1="22" x2="70" y2="30" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="30" y1="70" x2="22" y2="78" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'moon' =>
            '<path d="M36,18 C18,28 10,48 20,66 C30,84 52,90 70,80 C52,82 34,68 32,50 C30,32 44,20 60,16 C52,16 44,16 36,18 Z" fill="$C"/>',

        'snowflake' =>
            '<line x1="50" y1="10" x2="50" y2="90" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="10" y1="50" x2="90" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="22" y1="22" x2="78" y2="78" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="78" y1="22" x2="22" y2="78" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'leaf' =>
            '<path d="M20,80 C20,80 24,40 62,24 C80,16 88,20 88,20 C88,20 88,48 68,64 C50,80 20,80 20,80 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="20" y1="80" x2="62" y2="38" stroke="$C" stroke-width="$W" stroke-linecap="round" opacity="0.5"/>',

        'tree' =>
            '<polygon points="50,10 76,42 62,42 78,66 58,66 68,86 32,86 42,66 22,66 38,42 24,42 50,10" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'recycle' =>
            '<polyline points="50,10 62,30 38,30 50,10" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<path d="M62,30 L78,56 L62,80" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<polyline points="62,80 82,80 76,62" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<path d="M22,56 L38,30" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<polyline points="20,56 18,78 36,78" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        // ── Science / Education ───────────────────────────────────────────────
        'brain' =>
            '<path d="M50,22 C40,16 24,20 22,36 C14,38 10,50 18,56 C12,66 20,78 32,74 C38,86 62,86 68,74 C80,78 88,66 82,56 C90,50 86,38 78,36 C76,20 60,16 50,22 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="50" y1="22" x2="50" y2="80" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'lightbulb' =>
            '<path d="M50,14 A28,28 0 0 1 78,42 C78,56 68,66 65,72 L35,72 C32,66 22,56 22,42 A28,28 0 0 1 50,14 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="38" y1="80" x2="62" y2="80" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="40" y1="88" x2="60" y2="88" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'flask' =>
            '<line x1="36" y1="10" x2="36" y2="38" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="64" y1="10" x2="64" y2="38" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M36,38 L14,80 C12,86 16,90 22,90 L78,90 C84,90 88,86 86,80 L64,38 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="24" y1="66" x2="58" y2="66" stroke="$C" stroke-width="$W" stroke-linecap="round" opacity="0.5"/>',

        'graduation-cap' =>
            '<polygon points="50,16 92,36 50,56 8,36 50,16" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<path d="M26,46 L26,70 C26,70 38,82 50,82 C62,82 74,70 74,70 L74,46" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="92" y1="36" x2="92" y2="58" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'book' =>
            '<path d="M14,16 L50,16 L50,84 L14,84 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<path d="M50,16 L86,16 L86,84 L50,84 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="50" y1="16" x2="50" y2="84" stroke="$C" stroke-width="$W"/>',

        'book-open' =>
            '<path d="M50,22 C40,18 22,20 10,28 L10,82 C22,74 40,76 50,80 C60,76 78,74 90,82 L90,28 C78,20 60,18 50,22 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="50" y1="22" x2="50" y2="80" stroke="$C" stroke-width="$W"/>',

        'microscope' =>
            '<line x1="50" y1="10" x2="50" y2="58" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<rect x="36" y="22" width="28" height="10" rx="2" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="38" y1="58" x2="62" y2="58" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M38,70 C30,62 30,78 50,78 C70,78 70,62 62,70" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="20" y1="86" x2="80" y2="86" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'stethoscope' =>
            '<path d="M22,14 L22,52 A22,22 0 0 0 66,52 L66,36 A14,14 0 0 1 86,50" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<circle cx="84" cy="58" r="8" fill="none" stroke="$C" stroke-width="$W"/>',

        // ── Media ─────────────────────────────────────────────────────────────
        'music' =>
            '<ellipse cx="28" cy="74" rx="12" ry="9" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<ellipse cx="70" cy="64" rx="12" ry="9" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="40" y1="74" x2="40" y2="18" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="82" y1="64" x2="82" y2="18" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="40" y1="18" x2="82" y2="26" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'video' =>
            '<rect x="10" y="22" width="58" height="56" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<polygon points="68,32 90,44 90,56 68,68" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'image' =>
            '<rect x="10" y="20" width="80" height="60" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="32" cy="38" r="8" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<polyline points="10,72 30,52 46,62 62,42 90,72" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'camera' =>
            '<path d="M34,18 L22,32 L10,32 L10,78 L90,78 L90,32 L78,32 L66,18 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<circle cx="50" cy="55" r="18" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="50" cy="55" r="8" fill="$C" opacity="0.5"/>',

        'microphone' =>
            '<rect x="34" y="14" width="32" height="44" rx="16" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M20,46 C20,66 34,78 50,78 C66,78 80,66 80,46" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="78" x2="50" y2="90" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="34" y1="90" x2="66" y2="90" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'headphones' =>
            '<path d="M18,50 C18,28 32,14 50,14 C68,14 82,28 82,50" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<rect x="10" y="50" width="16" height="28" rx="8" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="74" y="50" width="16" height="28" rx="8" fill="none" stroke="$C" stroke-width="$W"/>',

        'volume-up' =>
            '<polygon points="10,36 30,36 52,18 52,82 30,64 10,64" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<path d="M60,32 C70,38 70,62 60,68" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M68,22 C84,30 84,70 68,78" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'gamepad' =>
            '<rect x="12" y="30" width="76" height="44" rx="22" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="28" y1="50" x2="42" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="35" y1="43" x2="35" y2="57" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<circle cx="64" cy="44" r="4" fill="$C"/>' .
            '<circle cx="72" cy="52" r="4" fill="$C"/>' .
            '<circle cx="56" cy="52" r="4" fill="$C"/>',

        // ── Charts ────────────────────────────────────────────────────────────
        'chart-bar' =>
            '<rect x="10" y="54" width="18" height="36" rx="2" fill="$C"/>' .
            '<rect x="35" y="36" width="18" height="54" rx="2" fill="$C"/>' .
            '<rect x="60" y="18" width="18" height="72" rx="2" fill="$C"/>' .
            '<line x1="8" y1="92" x2="92" y2="92" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'chart-line' =>
            '<polyline points="10,74 30,54 50,40 66,52 86,20" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>' .
            '<line x1="8" y1="88" x2="8" y2="10" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="8" y1="88" x2="92" y2="88" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        // ── Finance / Commerce ────────────────────────────────────────────────
        'dollar-sign' =>
            '<line x1="50" y1="10" x2="50" y2="90" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M70,28 C60,20 30,20 30,40 C30,60 70,50 70,70 C70,82 38,84 26,76" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'credit-card' =>
            '<rect x="10" y="24" width="80" height="52" rx="6" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="10" y1="44" x2="90" y2="44" stroke="$C" stroke-width="$W"/>' .
            '<rect x="20" y="56" width="24" height="8" rx="2" fill="$C"/>',

        'shopping-cart' =>
            '<path d="M6,10 L22,10 L34,56 L78,56 L86,26 L22,26" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>' .
            '<circle cx="38" cy="70" r="8" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="72" cy="70" r="8" fill="none" stroke="$C" stroke-width="$W"/>',

        'receipt' =>
            '<path d="M18,10 L18,90 L26,84 L34,90 L42,84 L50,90 L58,84 L66,90 L74,84 L82,90 L82,10 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="28" y1="34" x2="72" y2="34" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="28" y1="48" x2="72" y2="48" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="28" y1="62" x2="55" y2="62" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'calculator' =>
            '<rect x="18" y="10" width="64" height="80" rx="6" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="26" y="18" width="48" height="20" rx="3" fill="$C" opacity="0.4"/>' .
            '<rect x="26" y="46" width="12" height="10" rx="2" fill="$C"/>' .
            '<rect x="44" y="46" width="12" height="10" rx="2" fill="$C"/>' .
            '<rect x="62" y="46" width="12" height="10" rx="2" fill="$C"/>' .
            '<rect x="26" y="62" width="12" height="10" rx="2" fill="$C"/>' .
            '<rect x="44" y="62" width="12" height="10" rx="2" fill="$C"/>' .
            '<rect x="62" y="62" width="12" height="22" rx="2" fill="$C"/>',

        'qr-code' =>
            '<rect x="10" y="10" width="32" height="32" rx="2" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="16" y="16" width="20" height="20" rx="1" fill="$C"/>' .
            '<rect x="58" y="10" width="32" height="32" rx="2" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="64" y="16" width="20" height="20" rx="1" fill="$C"/>' .
            '<rect x="10" y="58" width="32" height="32" rx="2" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="16" y="64" width="20" height="20" rx="1" fill="$C"/>' .
            '<rect x="58" y="58" width="8" height="8" rx="1" fill="$C"/>' .
            '<rect x="70" y="58" width="8" height="8" rx="1" fill="$C"/>' .
            '<rect x="82" y="58" width="8" height="8" rx="1" fill="$C"/>' .
            '<rect x="58" y="70" width="8" height="8" rx="1" fill="$C"/>' .
            '<rect x="70" y="70" width="8" height="8" rx="1" fill="$C"/>' .
            '<rect x="82" y="82" width="8" height="8" rx="1" fill="$C"/>' .
            '<rect x="58" y="82" width="8" height="8" rx="1" fill="$C"/>',

        'barcode' =>
            '<rect x="12" y="18" width="6" height="64" fill="$C"/>' .
            '<rect x="22" y="18" width="4" height="64" fill="$C"/>' .
            '<rect x="30" y="18" width="8" height="64" fill="$C"/>' .
            '<rect x="42" y="18" width="4" height="64" fill="$C"/>' .
            '<rect x="50" y="18" width="6" height="64" fill="$C"/>' .
            '<rect x="60" y="18" width="4" height="64" fill="$C"/>' .
            '<rect x="68" y="18" width="8" height="64" fill="$C"/>' .
            '<rect x="80" y="18" width="4" height="64" fill="$C"/>' .
            '<line x1="12" y1="88" x2="88" y2="88" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        // ── Files / Folders ───────────────────────────────────────────────────
        'folder' =>
            '<path d="M10,30 L10,80 L90,80 L90,40 L46,40 L36,30 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'file' =>
            '<path d="M22,10 L22,90 L78,90 L78,36 L52,10 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="52" y1="10" x2="52" y2="36" stroke="$C" stroke-width="$W"/>' .
            '<line x1="52" y1="36" x2="78" y2="36" stroke="$C" stroke-width="$W"/>',

        // ── Navigation / Location ─────────────────────────────────────────────
        'map-marker' =>
            '<path d="M50,90 C50,90 18,60 18,38 A32,32 0 0 1 82,38 C82,60 50,90 50,90 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<circle cx="50" cy="38" r="12" fill="$C"/>',

        'compass' =>
            '<circle cx="50" cy="50" r="38" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<polygon points="50,20 58,50 50,44 42,50" fill="$C"/>' .
            '<polygon points="50,80 42,50 50,56 58,50" fill="$C" opacity="0.4"/>',

        'map' =>
            '<polygon points="38,14 62,20 62,86 38,80 38,14" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<polyline points="14,20 38,14 38,80 14,86 14,20" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<polyline points="62,20 86,14 86,80 62,86" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'plane' =>
            '<path d="M50,12 C58,12 80,28 82,50 L50,44 L18,50 C20,28 42,12 50,12 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<path d="M56,50 L70,66 L62,66 L50,54 L38,66 L30,66 L44,50" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<line x1="50" y1="54" x2="50" y2="86" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'route' =>
            '<circle cx="22" cy="26" r="10" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M22,36 C22,60 78,40 78,64" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M78,74 C78,80 72,86 78,86 C84,86 78,80 78,74 Z" fill="$C"/>',

        // ── Hardware / Tech ───────────────────────────────────────────────────
        'desktop' =>
            '<rect x="10" y="14" width="80" height="56" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="10" y1="56" x2="90" y2="56" stroke="$C" stroke-width="$W"/>' .
            '<line x1="36" y1="70" x2="30" y2="86" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="64" y1="70" x2="70" y2="86" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="26" y1="86" x2="74" y2="86" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'laptop' =>
            '<rect x="16" y="20" width="68" height="48" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M8,68 L92,68 L96,74 L4,74 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'mobile' =>
            '<rect x="28" y="10" width="44" height="80" rx="8" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="40" y1="20" x2="60" y2="20" stroke="$C" stroke-width="$W" stroke-linecap="round" opacity="0.5"/>' .
            '<circle cx="50" cy="78" r="5" fill="$C"/>',

        'keyboard' =>
            '<rect x="10" y="28" width="80" height="44" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="18" y="36" width="10" height="8" rx="2" fill="$C"/>' .
            '<rect x="32" y="36" width="10" height="8" rx="2" fill="$C"/>' .
            '<rect x="46" y="36" width="10" height="8" rx="2" fill="$C"/>' .
            '<rect x="60" y="36" width="10" height="8" rx="2" fill="$C"/>' .
            '<rect x="74" y="36" width="8" height="8" rx="2" fill="$C"/>' .
            '<rect x="18" y="50" width="8" height="8" rx="2" fill="$C"/>' .
            '<rect x="36" y="60" width="28" height="8" rx="4" fill="$C"/>' .
            '<rect x="60" y="50" width="10" height="8" rx="2" fill="$C"/>' .
            '<rect x="74" y="50" width="8" height="8" rx="2" fill="$C"/>',

        'hard-drive' =>
            '<rect x="12" y="26" width="76" height="48" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="12" y1="56" x2="88" y2="56" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="68" cy="66" r="7" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="20" y1="66" x2="55" y2="66" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'gauge' =>
            '<path d="M18,72 A40,40 0 1 1 82,72" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="50" x2="72" y2="34" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<circle cx="50" cy="50" r="6" fill="$C"/>',

        'battery-full' =>
            '<rect x="10" y="30" width="72" height="40" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M82,42 L90,42 L90,58 L82,58" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<rect x="14" y="34" width="62" height="32" rx="2" fill="$C"/>',

        'satellite' =>
            '<rect x="30" y="30" width="40" height="40" rx="3" fill="none" stroke="$C" stroke-width="$W" transform="rotate(45,50,50)"/>' .
            '<line x1="15" y1="36" x2="30" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="15" y1="22" x2="28" y2="22" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="15" y1="22" x2="15" y2="36" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<circle cx="72" cy="28" r="9" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="70" y1="36" x2="70" y2="50" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'wifi' =>
            '<path d="M10,46 Q50,20 90,46" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M23,58 Q50,38 77,58" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M36,70 Q50,58 64,70" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<circle cx="50" cy="82" r="5" fill="$C"/>',

        'cloud' =>
            '<path d="M24,72 Q12,72 12,56 Q12,42 26,40 Q26,22 46,20 Q64,18 70,32 Q82,28 88,42 Q96,46 90,58 Q88,72 76,72 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        // ── Brand / Social (geometric approximations) ─────────────────────────
        'github' =>
            '<circle cx="50" cy="40" r="26" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M34,62 C28,66 22,70 22,80 C22,86 30,88 50,88 C70,88 78,86 78,80 C78,70 72,66 66,62" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<path d="M34,62 C28,56 26,46 36,38" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M66,62 C72,56 74,46 64,38" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="42" y1="86" x2="42" y2="70" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="58" y1="86" x2="58" y2="70" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'twitter' =>
            '<line x1="14" y1="14" x2="86" y2="86" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="86" y1="14" x2="14" y2="86" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'x-twitter' =>
            '<line x1="14" y1="14" x2="86" y2="86" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="86" y1="14" x2="14" y2="86" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'linkedin' =>
            '<rect x="10" y="10" width="80" height="80" rx="10" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="24" y="38" width="12" height="38" rx="2" fill="$C"/>' .
            '<circle cx="30" cy="26" r="7" fill="$C"/>' .
            '<path d="M42,54 C42,42 76,38 76,52 L76,76 L64,76 L64,52 C64,46 42,44 42,54 L42,76" fill="$C"/>',

        'youtube' =>
            '<rect x="8" y="22" width="84" height="56" rx="10" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<polygon points="40,36 40,64 70,50" fill="$C"/>',

        'discord' =>
            '<path d="M26,22 C26,22 14,56 14,70 C14,70 28,82 50,82 C72,82 86,70 86,70 C86,56 74,22 74,22 L62,18 C62,18 60,26 50,26 C40,26 38,18 38,18 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<circle cx="36" cy="52" r="9" fill="$C"/>' .
            '<circle cx="64" cy="52" r="9" fill="$C"/>',

        'slack' =>
            '<rect x="10" y="38" width="24" height="24" rx="12" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="38" y="10" width="24" height="24" rx="12" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="66" y="38" width="24" height="24" rx="12" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="38" y="66" width="24" height="24" rx="12" fill="none" stroke="$C" stroke-width="$W"/>',

        'docker' =>
            '<path d="M10,48 C14,36 26,32 36,34 C38,24 46,20 56,22 C60,14 70,12 78,16 L82,20 C86,26 86,34 80,40 L80,48 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<path d="M10,48 C6,44 6,38 12,36" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="26" y1="34" x2="26" y2="48" stroke="$C" stroke-width="$W"/>' .
            '<line x1="36" y1="34" x2="36" y2="48" stroke="$C" stroke-width="$W"/>' .
            '<line x1="46" y1="34" x2="46" y2="48" stroke="$C" stroke-width="$W"/>',

        'git' =>
            '<circle cx="72" cy="28" r="10" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="28" cy="72" r="10" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="72" cy="72" r="10" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="38" y1="72" x2="62" y2="72" stroke="$C" stroke-width="$W"/>' .
            '<line x1="72" y1="38" x2="72" y2="62" stroke="$C" stroke-width="$W"/>' .
            '<line x1="34" y1="64" x2="64" y2="34" stroke="$C" stroke-width="$W"/>',

        'npm' =>
            '<rect x="8" y="32" width="84" height="36" rx="4" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="16" y="40" width="20" height="20" rx="2" fill="$C"/>' .
            '<rect x="40" y="40" width="20" height="20" rx="2" fill="$C"/>' .
            '<rect x="44" y="44" width="12" height="16" rx="1" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="64" y="40" width="20" height="20" rx="2" fill="$C"/>',

        'react' =>
            '<circle cx="50" cy="50" r="8" fill="$C"/>' .
            '<ellipse cx="50" cy="50" rx="42" ry="16" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<ellipse cx="50" cy="50" rx="42" ry="16" fill="none" stroke="$C" stroke-width="$W" transform="rotate(60,50,50)"/>' .
            '<ellipse cx="50" cy="50" rx="42" ry="16" fill="none" stroke="$C" stroke-width="$W" transform="rotate(-60,50,50)"/>',

        'python' =>
            '<path d="M50,10 C36,10 28,16 28,28 L28,44 L50,44 L50,50 L28,50 C16,50 10,56 10,68 C10,82 18,90 32,90 L36,90 L36,76 L32,76 C26,76 22,72 22,66 C22,60 26,56 32,56 L68,56 C74,56 78,52 78,46 C78,40 74,36 68,36 L50,36 L50,30 C50,26 54,22 58,22 L74,22 C82,22 88,28 88,36 L88,64 C88,74 82,80 72,80 L50,80 L50,90 L72,90 C86,90 92,82 92,68 L92,32 C92,20 86,10 72,10 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'vue' =>
            '<polygon points="50,14 72,52 86,24 100,24 50,92 0,24 14,24 28,52 50,14" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'figma' =>
            '<rect x="34" y="10" width="32" height="32" rx="8" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="34" y="42" width="32" height="32" rx="0 0 8 8" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<rect x="34" y="74" width="32" height="16" rx="8" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="66" cy="58" r="16" fill="none" stroke="$C" stroke-width="$W"/>',

        'stripe' =>
            '<rect x="10" y="10" width="80" height="80" rx="10" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<path d="M46,38 C46,34 52,32 62,34 L62,48 C52,46 46,52 46,58 C46,66 52,70 62,68 L62,82 C48,84 34,78 34,62 C34,50 40,42 46,38 Z" fill="$C"/>',

        'paypal' =>
            '<path d="M34,14 C34,14 54,14 62,14 C78,14 86,24 82,40 C78,56 64,60 54,60 L48,60 L44,80 L28,80 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<path d="M48,60 C48,60 62,60 70,60 C86,60 90,72 86,84" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'bitcoin' =>
            '<circle cx="50" cy="50" r="38" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="38" y1="24" x2="38" y2="76" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="24" x2="50" y2="76" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M38,24 L58,24 C66,24 70,30 66,38 C70,40 72,46 68,54 C64,62 56,62 50,62 L38,62" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<path d="M38,44 L62,44" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        // ── Misc ─────────────────────────────────────────────────────────────
        'infinity' =>
            '<path d="M36,50 C36,40 26,30 18,30 C8,30 8,50 18,50 C28,50 50,30 82,30 C92,30 92,50 82,50 C72,50 50,70 18,70 C8,70 8,50 18,50" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'flag' =>
            '<line x1="20" y1="10" x2="20" y2="90" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<polygon points="20,10 82,26 20,46" fill="$C"/>',

        'cog' =>
            '<circle cx="50" cy="50" r="14" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<circle cx="50" cy="50" r="32" fill="none" stroke="$C" stroke-width="$W" stroke-dasharray="14 8" stroke-dashoffset="7"/>',

        'brush' =>
            '<path d="M72,14 C78,14 88,24 86,30 L50,66 C46,70 40,72 36,68 C32,64 34,58 38,54 L72,14 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<path d="M38,54 C34,64 26,72 22,80 C28,84 36,80 42,74" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'highlighter' =>
            '<rect x="20" y="30" width="60" height="30" rx="4" fill="none" stroke="$C" stroke-width="$W" transform="rotate(-20,50,50)"/>' .
            '<line x1="28" y1="78" x2="70" y2="78" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="40" y1="72" x2="60" y2="72" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'magic' =>
            '<line x1="10" y1="90" x2="70" y2="30" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<polygon points="70,30 76,10 82,30 90,28 82,36" fill="$C"/>' .
            '<line x1="50" y1="18" x2="54" y2="10" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="82" y1="60" x2="90" y2="56" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'crop' =>
            '<polyline points="16,10 16,70 76,70" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<polyline points="30,34 84,34 84,90" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'expand' =>
            '<polyline points="10,38 10,10 38,10" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>' .
            '<polyline points="62,10 90,10 90,38" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>' .
            '<polyline points="10,62 10,90 38,90" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>' .
            '<polyline points="90,62 90,90 62,90" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'signal' =>
            '<rect x="8" y="68" width="16" height="22" rx="2" fill="$C"/>' .
            '<rect x="30" y="52" width="16" height="38" rx="2" fill="$C"/>' .
            '<rect x="52" y="34" width="16" height="56" rx="2" fill="$C"/>' .
            '<rect x="74" y="16" width="16" height="74" rx="2" fill="$C"/>',

        'power-off' =>
            '<path d="M34,20 A38,38 0 1 0 66,20" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="10" x2="50" y2="56" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        'rotate' =>
            '<path d="M82,36 A36,36 0 1 0 82,64" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<polyline points="82,18 82,36 64,36" fill="none" stroke="$C" stroke-width="$W" stroke-linecap="round" stroke-linejoin="round"/>',

        'thumbs-up' =>
            '<path d="M22,50 L22,82 L40,82 L40,50 Z" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<path d="M40,50 L48,18 C52,10 60,14 60,22 L60,38 L78,38 C84,38 86,42 84,48 L76,76 C74,80 70,82 66,82 L40,82" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>',

        'peace' =>
            '<circle cx="50" cy="50" r="38" fill="none" stroke="$C" stroke-width="$W"/>' .
            '<line x1="50" y1="12" x2="50" y2="88" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="50" x2="18" y2="78" stroke="$C" stroke-width="$W" stroke-linecap="round"/>' .
            '<line x1="50" y1="50" x2="82" y2="78" stroke="$C" stroke-width="$W" stroke-linecap="round"/>',

        // ── Default fallback ──────────────────────────────────────────────────
        '_default' =>
            '<polygon points="50,12 82,28 88,62 68,88 32,88 12,62 18,28 50,12" fill="none" stroke="$C" stroke-width="$W" stroke-linejoin="round"/>' .
            '<circle cx="50" cy="50" r="10" fill="$C" opacity="0.6"/>',
    ];

    // ── Aliases ──────────────────────────────────────────────────────────────
    // Maps alternate icon names to canonical keys in ICONS above.
    const ALIASES = [
        'gear'               => 'cog',
        'gears'              => 'cog',
        'cogs'               => 'cog',
        'magnifying-glass'   => 'search',
        'house'              => 'home',
        'xmark'              => 'times',
        'x'                  => 'times',
        'times-circle'       => 'circle-xmark',
        'check-circle'       => 'circle-check',
        'info-circle'        => 'circle-info',
        'exclamation-circle' => 'circle-exclamation',
        'circle-question'    => 'circle-info',
        'location-dot'       => 'map-marker',
        'map-pin'            => 'map-marker',
        'heart-pulse'        => 'heart',
        'heartbeat'          => 'stethoscope',
        'file-code'          => 'file-code',
        'code-branch'        => 'git',
        'share-nodes'        => 'share',
        'share-alt'          => 'share',
        'network'            => 'network-wired',
        'ethernet'           => 'network-wired',
        'sitemap'            => 'sitemap',
        'tools'              => 'cog',
        'wrench'             => 'cog',
        'hammer'             => 'cog',
        'screwdriver'        => 'cog',
        'edit'               => 'pen',
        'pencil'             => 'pen',
        'pen-nib'            => 'pen',
        'photo'              => 'image',
        'video-camera'       => 'video',
        'film'               => 'video',
        'headset'            => 'headphones',
        'bolt-lightning'     => 'bolt',
        'fire-flame'         => 'fire',
        'location-arrow'     => 'map-marker',
        'crosshairs'         => 'compass',
        'bullseye'           => 'compass',
        'target'             => 'compass',
        'dashboard'          => 'gauge',
        'tachometer'         => 'gauge',
        'speedometer'        => 'gauge',
        'hdd'                => 'hard-drive',
        'ssd'                => 'hard-drive',
        'memory'             => 'hard-drive',
        'desktop-alt'        => 'desktop',
        'window-maximize'    => 'desktop',
        'tablet'             => 'mobile',
        'rss-square'         => 'rss',
        'cart'               => 'shopping-cart',
        'user-circle'        => 'user',
        'user-shield'        => 'shield',
        'user-lock'          => 'lock',
        'building-columns'   => 'building',
        'city'               => 'building',
        'landmark'           => 'building',
        'university'         => 'graduation-cap',
        'award-alt'          => 'award',
        'medal'              => 'award',
        'graduation'         => 'graduation-cap',
        'dna'                => 'atom',
        'bacterium'          => 'atom',
        'virus'              => 'atom',
        'percent'            => 'dollar-sign',
        'percentage'         => 'dollar-sign',
        'money-bill'         => 'dollar-sign',
        'euro-sign'          => 'dollar-sign',
        'cube'               => 'box',
        'box'                => '_default',
        'cubes'              => 'layer-group',
        'object-group'       => 'grid',
        'th'                 => 'grid',
        'th-large'           => 'grid',
        'columns'            => 'layer-group',
        'sort'               => 'filter',
        'table'              => 'grid',
        'wand-magic-sparkles'=> 'magic',
        'magic-wand'         => 'magic',
        'sparkles'           => 'magic',
        'star-of-david'      => 'star',
        'broadcast-tower'    => 'satellite',
        'tower-broadcast'    => 'satellite',
        'radio'              => 'satellite',
        'globe-americas'     => 'globe',
        'globe-europe'       => 'globe',
        'globe-asia'         => 'globe',
        'earth-americas'     => 'globe',
        'earth-europe'       => 'globe',
        'github-alt'         => 'github',
        'gitlab'             => 'git',
        'bitbucket'          => 'git',
        'sourcetree'         => 'git',
        'node'               => 'npm',
        'node-js'            => 'npm',
        'js'                 => 'code',
        'javascript'         => 'code',
        'vuejs'              => 'vue',
        'angular'            => 'code',
        'php'                => 'code',
        'java'               => 'code',
        'swift'              => 'code',
        'rust'               => 'code',
        'golang'             => 'code',
        'go'                 => 'code',
        'css3'               => 'code',
        'css'                => 'code',
        'html5'              => 'code',
        'html'               => 'code',
        'bootstrap'          => 'code',
        'tailwind'           => 'code',
        'docker'             => 'docker',
        'aws'                => 'cloud',
        'azure'              => 'cloud',
        'google-cloud'       => 'cloud',
        'cloudflare'         => 'cloud',
        'heroku'             => 'cloud',
        'digitalocean'       => 'cloud',
        'vercel'             => 'cloud',
        'netlify'            => 'cloud',
        'mysql'              => 'database',
        'postgresql'         => 'database',
        'mongodb'            => 'database',
        'redis'              => 'database',
        'jira'               => 'sitemap',
        'trello'             => 'sitemap',
        'notion'             => 'book',
        'cc-visa'            => 'credit-card',
        'cc-mastercard'      => 'credit-card',
        'cc-stripe'          => 'stripe',
        'cc-paypal'          => 'paypal',
        'cc-apple-pay'       => 'credit-card',
        'apple-pay'          => 'credit-card',
        'google-pay'         => 'credit-card',
        'ethereum'           => 'bitcoin',
        'btc'                => 'bitcoin',
        'eth'                => 'bitcoin',
        'monero'             => 'bitcoin',
    ];

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Render an icon as SVG elements, centered at (x, y) with given size.
     * @return string SVG fragment
     */
    static function icon_svg(string $name, float $x, float $y, float $size, string $color): string
    {
        $canonical = self::ALIASES[$name] ?? $name;
        $shapes    = self::ICONS[$canonical] ?? self::ICONS['_default'];

        $col = strpos($color, '#') === 0 ? $color : '#' . $color;
        $sw  = max(4, round($size * 0.11));   // stroke width scales with icon size

        $content = str_replace(['$C', '$W'], [$col, $sw], $shapes);

        $s  = $size / 100;
        $tx = $x - $size / 2;
        $ty = $y - $size / 2;
        return "<g transform='translate($tx,$ty) scale($s)'>$content</g>";
    }

    /**
     * Render icon inside a softly-lit circle backdrop (drop-in replacement).
     */
    static function icon_block(
        string $icon,
        float $cx, float $cy,
        float $bg_r,
        string $bg_color, string $icon_color,
        float $icon_size
    ): string {
        $bg  = strpos($bg_color,  '#') === 0 ? $bg_color  : '#' . $bg_color;
        $svg = self::icon_svg($icon, $cx, $cy, $icon_size, $icon_color);
        $r2  = round($bg_r * 0.72, 1);
        return "<circle cx='$cx' cy='$cy' r='$bg_r' fill='$bg' opacity='0.18'/>" .
               "<circle cx='$cx' cy='$cy' r='$r2' fill='$bg' opacity='0.25'/>" .
               $svg;
    }

    /**
     * Returns empty string — FontAwesome CSS is no longer required.
     * Kept for backwards-compatibility with existing template calls.
     */
    static function fa_style_import(): string { return ''; }

    // ── Legacy helpers (kept for compatibility) ───────────────────────────────

    static function is_brand(string $icon): bool
    {
        $brands = ['github','twitter','x-twitter','linkedin','youtube','discord',
                   'slack','docker','npm','react','vue','vuejs','python','figma',
                   'stripe','paypal','bitcoin','git','gitlab','bitbucket'];
        return in_array($icon, $brands, true) || isset(self::ALIASES[$icon]);
    }

    static function get_codepoint(string $icon): string { return 'f121'; }
    static function get_font_family(string $icon): string { return "'sans-serif'"; }
    static function get_font_weight(string $icon): string { return '400'; }

    static function svg_text(string $icon, float $x, float $y, float $size, string $color): string
    {
        return self::icon_svg($icon, $x, $y, $size, $color);
    }
}
