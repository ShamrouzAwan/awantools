<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$logger      = Logger::getInstance($db);
$activeSlug  = $settings->theme();
$themeFile   = THEMES_PATH . '/' . $activeSlug . '/theme.json';

if (!file_exists($themeFile)) {
    Session::flash('danger', 'Active theme not found.');
    redirect('/admin/themes');
}

$themeData = json_decode(file_get_contents($themeFile), true) ?? [];
$defaults  = $themeData['variables'] ?? [];

// Load current overrides
$overrides = [];
$rows = $db->fetchAll(
    "SELECT variable_key, variable_value FROM theme_overrides WHERE theme_slug = ?",
    [$activeSlug]
);
foreach ($rows as $row) {
    $overrides[$row['variable_key']] = $row['variable_value'];
}

// Current effective values
$current = array_merge($defaults, $overrides);

// ─── POST: Save overrides ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $postAction = Security::sanitize($_POST['action'] ?? 'save');

    if ($postAction === 'reset_all') {
        $db->query("DELETE FROM theme_overrides WHERE theme_slug = ?", [$activeSlug]);
        $logger->info("Theme '{$activeSlug}' variables reset to defaults", [], $auth->id());
        Session::flash('success', 'Theme reset to defaults.');
        redirect('/admin/theme-editor');
    }

    if ($postAction === 'reset_one') {
        $key = Security::sanitize($_POST['var_key'] ?? '');
        if ($key) {
            $db->query("DELETE FROM theme_overrides WHERE theme_slug = ? AND variable_key = ?", [$activeSlug, $key]);
        }
        redirect('/admin/theme-editor');
    }

    if ($postAction === 'save') {
        $vars = $_POST['vars'] ?? [];
        $saved = 0;
        foreach ($vars as $key => $value) {
            $key   = Security::sanitize($key);
            $value = trim($value);
            if (!$key || !array_key_exists($key, $defaults)) continue;

            if ($value === $defaults[$key]) {
                // Same as default — remove override if it exists
                $db->query("DELETE FROM theme_overrides WHERE theme_slug = ? AND variable_key = ?", [$activeSlug, $key]);
            } else {
                $exists = $db->fetch("SELECT id FROM theme_overrides WHERE theme_slug = ? AND variable_key = ?", [$activeSlug, $key]);
                if ($exists) {
                    $db->update('theme_overrides', ['variable_value' => $value], 'theme_slug = ? AND variable_key = ?', [$activeSlug, $key]);
                } else {
                    $db->insert('theme_overrides', ['theme_slug' => $activeSlug, 'variable_key' => $key, 'variable_value' => $value]);
                }
                $saved++;
            }
        }
        $logger->info("Theme '{$activeSlug}' variables saved ({$saved} overrides)", [], $auth->id());
        Session::flash('success', 'Theme variables saved.');
        redirect('/admin/theme-editor');
    }
}

// ─── Group variables ──────────────────────────────────────────────────────────
$groups = [
    'Colors'       => [],
    'Typography'   => [],
    'Spacing'      => [],
    'Shadows'      => [],
    'Other'        => [],
];

foreach ($defaults as $key => $value) {
    if (str_starts_with($key, 'color-')) {
        $groups['Colors'][$key] = $value;
    } elseif (str_starts_with($key, 'font-') || str_starts_with($key, 'line-height')) {
        $groups['Typography'][$key] = $value;
    } elseif (str_starts_with($key, 'radius-') || str_starts_with($key, 'spacing-') || str_ends_with($key, '-width') || str_ends_with($key, '-height')) {
        $groups['Spacing'][$key] = $value;
    } elseif (str_starts_with($key, 'shadow-')) {
        $groups['Shadows'][$key] = $value;
    } else {
        $groups['Other'][$key] = $value;
    }
}
$groups = array_filter($groups);

ob_start();
?>
<style>
.var-row {
    display:grid; grid-template-columns:220px 1fr 80px;
    gap:16px; align-items:center;
    padding:12px 0; border-bottom:1px solid var(--color-border);
}
.var-row:last-child { border-bottom:none; }
.var-key { font-size:12px; font-family:monospace; color:var(--color-text-secondary); word-break:break-all; }
.var-overridden .var-key { color:var(--color-primary); font-weight:600; }
.var-overridden .var-key::after { content:'*'; color:var(--color-warning); margin-left:2px; }
.color-input-wrap { display:flex; align-items:center; gap:8px; }
.color-swatch { width:28px; height:28px; border-radius:var(--radius-small); border:1px solid var(--color-border); cursor:pointer; flex-shrink:0; }
</style>

<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Theme Editor</div>
            <div class="page-subtitle">Customizing: <?= e($themeData['name'] ?? $activeSlug) ?> · <span style="color:var(--color-text-muted)">* = overridden from default</span></div>
        </div>
    </div>
    <div class="topbar-actions">
        <a href="/admin/themes" class="btn btn-secondary btn-sm">← Back to Themes</a>
        <form method="POST" style="display:inline">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="reset_all">
            <button type="submit" class="btn btn-ghost btn-sm" data-confirm="Reset all theme variables to defaults?" style="color:var(--color-danger)">Reset All</button>
        </form>
    </div>
</div>

<div class="page-body">
    <form method="POST" id="theme-editor-form">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="save">

        <?php foreach ($groups as $groupName => $vars): ?>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header">
                <span class="card-title"><?= e($groupName) ?></span>
            </div>
            <div class="card-body">
                <?php foreach ($vars as $key => $defaultValue):
                    $effectiveValue = $current[$key] ?? $defaultValue;
                    $isOverridden   = isset($overrides[$key]);
                    $isColor        = preg_match('/^#[0-9a-f]{3,8}$/i', $effectiveValue) || str_starts_with($effectiveValue, 'rgb') || str_starts_with($effectiveValue, 'hsl');
                ?>
                <div class="var-row <?= $isOverridden ? 'var-overridden' : '' ?>">
                    <div>
                        <div class="var-key">--<?= e($key) ?></div>
                        <?php if ($isOverridden): ?>
                        <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px">Default: <?= e($defaultValue) ?></div>
                        <?php endif ?>
                    </div>
                    <div>
                        <?php if ($isColor): ?>
                        <div class="color-input-wrap">
                            <input type="color"
                                   class="color-swatch"
                                   value="<?= e(strlen($effectiveValue) === 7 ? $effectiveValue : '#6366f1') ?>"
                                   oninput="document.getElementById('var-<?= e(str_replace(['-','.'], '_', $key)) ?>').value=this.value">
                            <input type="text"
                                   id="var-<?= e(str_replace(['-','.'], '_', $key)) ?>"
                                   name="vars[<?= e($key) ?>]"
                                   class="form-input"
                                   value="<?= e($effectiveValue) ?>"
                                   oninput="this.previousElementSibling.value=this.value"
                                   style="font-family:monospace;font-size:13px">
                        </div>
                        <?php else: ?>
                        <input type="text"
                               name="vars[<?= e($key) ?>]"
                               class="form-input"
                               value="<?= e($effectiveValue) ?>"
                               style="font-family:monospace;font-size:13px">
                        <?php endif ?>
                    </div>
                    <div>
                        <?php if ($isOverridden): ?>
                        <form method="POST" style="display:inline">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="reset_one">
                            <input type="hidden" name="var_key" value="<?= e($key) ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" title="Reset to default" style="color:var(--color-text-muted)">Reset</button>
                        </form>
                        <?php endif ?>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
        </div>
        <?php endforeach ?>

        <div style="position:sticky;bottom:0;background:var(--color-surface);border-top:1px solid var(--color-border);padding:16px 0;display:flex;justify-content:flex-end;gap:8px">
            <a href="/admin/themes" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Theme Variables</button>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Theme Editor', $content, ['section' => 'themes']);
