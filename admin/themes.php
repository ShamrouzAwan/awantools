<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$logger = Logger::getInstance($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = Security::sanitize($_POST['action'] ?? '');
    $slug   = basename(trim(Security::sanitize($_POST['slug'] ?? '')));

    if ($action === 'activate' && $slug) {
        if (Theme::activate($slug, $settings)) {
            $logger->info("Theme '{$slug}' activated", [], $auth->id());
            Session::flash('success', 'Theme activated successfully.');
        } else {
            Session::flash('danger', 'Theme not found.');
        }
    }

    if ($action === 'upload') {
        if (!isset($_FILES['theme_zip']) || $_FILES['theme_zip']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('danger', 'Upload failed. Please select a valid ZIP file.');
            redirect('/admin/themes');
        }

        $file = $_FILES['theme_zip'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = function_exists('mime_content_type') ? mime_content_type($file['tmp_name']) : '';
        $validMimes = ['application/zip', 'application/x-zip-compressed', 'application/x-zip', 'application/octet-stream'];
        if ($ext !== 'zip' || ($mime && !in_array($mime, $validMimes, true))) {
            Session::flash('danger', 'Only valid ZIP archives are accepted.');
            redirect('/admin/themes');
        }

        $result = Plugin::uploadThemeZip($file['tmp_name']);
        if (!$result['success']) {
            Session::flash('danger', 'Upload failed: ' . $result['error']);
        } else {
            $logger->info("Theme uploaded: {$result['slug']}", [], $auth->id());
            Session::flash('success', "Theme &ldquo;{$result['name']}&rdquo; uploaded. Activate it below.");
        }
    }

    redirect('/admin/themes');
}

$themes     = Theme::listThemes();
$activeSlug = $settings->theme();

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Themes</div>
            <div class="page-subtitle"><?= count($themes) ?> installed · Active: <?= e($theme->name()) ?></div>
        </div>
    </div>
    <div class="topbar-actions">
        <a href="/admin/theme-editor" class="btn btn-secondary btn-sm">🎨 Customize Theme</a>
    </div>
</div>

<div class="page-body">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        <?php foreach ($themes as $t): ?>
        <?php $isActive = $t['slug'] === $activeSlug; ?>
        <div class="card<?= $isActive ? '" style="border-color:var(--color-primary);box-shadow:0 0 0 2px var(--color-primary-light)' : '' ?>">
            <div style="height:120px;background:var(--color-background);border-bottom:1px solid var(--color-border);display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);overflow:hidden">
                <?php
                $screenshot = THEMES_PATH . '/' . $t['slug'] . '/screenshot.png';
                if (file_exists($screenshot)): ?>
                    <img src="/themes/<?= e($t['slug']) ?>/screenshot.png" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                    <div style="text-align:center">
                        <div style="font-size:28px;opacity:0.3">🎨</div>
                        <div class="text-sm text-muted" style="margin-top:4px">No preview</div>
                    </div>
                <?php endif ?>
            </div>
            <div class="card-body">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
                    <div style="font-size:14px;font-weight:600"><?= e($t['name'] ?? $t['slug']) ?></div>
                    <?php if ($isActive): ?>
                    <span class="badge badge-success">Active</span>
                    <?php endif ?>
                </div>
                <div class="text-sm text-muted" style="margin-bottom:4px"><?= e($t['description'] ?? '') ?></div>
                <div class="text-sm text-muted">
                    v<?= e($t['version'] ?? '1.0') ?>
                    <?php if (!empty($t['author'])): ?> · by <?= e($t['author']) ?><?php endif ?>
                </div>
            </div>
            <div class="card-footer">
                <?php if ($isActive): ?>
                <div style="display:flex;gap:8px">
                    <span class="text-muted text-sm" style="flex:1;line-height:30px">Currently active</span>
                    <a href="/admin/theme-editor" class="btn btn-secondary btn-sm">Customize</a>
                </div>
                <?php else: ?>
                <form method="POST">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="activate">
                    <input type="hidden" name="slug" value="<?= e($t['slug']) ?>">
                    <button type="submit" class="btn btn-primary btn-sm w-full" data-confirm="Activate the '<?= e($t['name'] ?? $t['slug']) ?>' theme?">
                        Activate Theme
                    </button>
                </form>
                <?php endif ?>
            </div>
        </div>
        <?php endforeach ?>
    </div>

    <!-- Upload Theme -->
    <div class="card" style="margin-top:20px">
        <div class="card-header"><span class="card-title">Upload Theme</span></div>
        <div class="card-body">
            <p style="margin-bottom:16px;color:var(--color-text-secondary);font-size:13px">
                Upload a theme as a <strong>ZIP file</strong>. The ZIP must contain a <code>theme.json</code> manifest with name, version, and CSS variable definitions.
            </p>
            <form method="POST" enctype="multipart/form-data" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="upload">
                <div style="flex:1;min-width:240px">
                    <label class="form-label">Theme ZIP File</label>
                    <input type="file" name="theme_zip" accept=".zip" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-primary">Upload Theme</button>
            </form>
        </div>
    </div>

    <!-- Theme structure reference -->
    <div class="card" style="margin-top:16px">
        <div class="card-header"><span class="card-title">Theme Structure</span></div>
        <div class="card-body">
            <p style="margin-bottom:12px;color:var(--color-text-secondary);font-size:13px">
                Place theme folders inside <code>themes/</code>. Each theme requires a <code>theme.json</code> manifest. Templates in the <code>templates/</code> folder override the default layout and admin templates.
            </p>
            <pre style="background:var(--color-background);border:1px solid var(--color-border);border-radius:var(--radius-small);padding:16px;font-size:12px">themes/
  my-theme/
    theme.json          ← required: name, version, variables
    style.css           ← theme stylesheet (optional — variables auto-applied)
    screenshot.png      ← preview image (optional)
    assets/             ← images, fonts, icons
    templates/
      layout.php        ← overrides default frontend layout
      admin.php         ← overrides default admin layout</pre>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Themes', $content, ['section' => 'themes']);
