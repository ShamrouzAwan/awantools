<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$errors  = [];
$success = '';
$action  = Security::sanitize($_POST['action'] ?? $_GET['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    if ($action === 'add') {
        $name    = Security::sanitize(trim($_POST['name'] ?? ''));
        $code    = strtolower(preg_replace('/[^a-zA-Z_\-]/', '', trim($_POST['code'] ?? '')));
        $isDefault = !empty($_POST['is_default']) ? 1 : 0;
        if (!$name || !$code) {
            $errors[] = 'Language name and code are required.';
        } elseif ($db->exists('languages', 'code = ?', [$code])) {
            $errors[] = 'A language with that code already exists.';
        } else {
            if ($isDefault) {
                $db->query("UPDATE languages SET is_default = 0");
            }
            $db->insert('languages', [
                'name'       => $name,
                'code'       => $code,
                'is_active'  => 1,
                'is_default' => $isDefault,
                'sort_order' => (int)($db->fetch("SELECT COALESCE(MAX(sort_order),0)+10 AS n FROM languages")['n'] ?? 0),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            Session::flash('success', 'Language added.');
            redirect('/admin/languages');
        }
    }

    if ($action === 'toggle') {
        $id  = (int)($_POST['id'] ?? 0);
        $row = $db->fetch("SELECT * FROM languages WHERE id = ?", [$id]);
        if ($row) {
            $db->update('languages', ['is_active' => $row['is_active'] ? 0 : 1], 'id = ?', [$id]);
        }
        redirect('/admin/languages');
    }

    if ($action === 'set_default') {
        $id = (int)($_POST['id'] ?? 0);
        $db->query("UPDATE languages SET is_default = 0");
        $db->update('languages', ['is_default' => 1, 'is_active' => 1], 'id = ?', [$id]);
        Session::flash('success', 'Default language updated.');
        redirect('/admin/languages');
    }

    if ($action === 'delete') {
        $id  = (int)($_POST['id'] ?? 0);
        $row = $db->fetch("SELECT * FROM languages WHERE id = ?", [$id]);
        if ($row && !$row['is_default']) {
            $db->query("DELETE FROM languages WHERE id = ?", [$id]);
            Session::flash('success', 'Language deleted.');
        } else {
            Session::flash('warning', 'Cannot delete the default language.');
        }
        redirect('/admin/languages');
    }
}

$languages = $db->fetchAll("SELECT * FROM languages ORDER BY sort_order ASC, name ASC") ?: [];

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Languages</div>
            <div class="page-subtitle">Manage site language options for users.</div>
        </div>
    </div>
    <div class="page-header-right">
        <button class="btn btn-primary" data-modal-open="modal-add-lang">Add Language</button>
    </div>
</div>

<div class="page-body">
    <div class="card">
        <?php if (empty($languages)): ?>
        <div class="card-body">
            <div class="empty-state" style="padding:40px 24px">
                <div class="empty-state-icon">
                    <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20M2 12h20"/></svg>
                </div>
                <h3>No languages yet</h3>
                <p>Add a language to allow users to select their preferred language.</p>
                <button class="btn btn-primary" style="margin-top:12px" data-modal-open="modal-add-lang">Add Language</button>
            </div>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Language</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Default</th>
                        <th style="width:160px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($languages as $lang): ?>
                <tr>
                    <td style="font-weight:600"><?= e($lang['name']) ?></td>
                    <td><code style="background:var(--color-background);padding:2px 8px;border-radius:4px;font-size:12px"><?= e($lang['code']) ?></code></td>
                    <td>
                        <?php if ($lang['is_active']): ?>
                        <span class="badge badge-success">Active</span>
                        <?php else: ?>
                        <span class="badge badge-neutral">Inactive</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php if ($lang['is_default']): ?>
                        <span class="badge badge-primary">Default</span>
                        <?php else: ?>
                        <form method="POST" style="display:inline">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="set_default">
                            <input type="hidden" name="id" value="<?= $lang['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm">Set default</button>
                        </form>
                        <?php endif ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $lang['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm"><?= $lang['is_active'] ? 'Disable' : 'Enable' ?></button>
                        </form>
                        <?php if (!$lang['is_default']): ?>
                        <form method="POST" style="display:inline">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $lang['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-danger)"
                                    data-confirm="Delete language &quot;<?= e($lang['name']) ?>&quot;?">Delete</button>
                        </form>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>
    </div>

    <div class="card" style="margin-top:20px">
        <div class="card-header"><span class="card-title">Language Codes</span></div>
        <div class="card-body">
            <p style="font-size:13px;color:var(--color-text-secondary)">
                Use standard IETF BCP 47 codes: <code>en</code>, <code>fr</code>, <code>de</code>, <code>es</code>, <code>ar</code>, <code>zh</code>, <code>ur</code>, etc.
                The code is stored as the user&rsquo;s preferred language and passed to the <code>lang</code> attribute on the HTML element.
            </p>
        </div>
    </div>
</div>

<!-- Add Language Modal -->
<div class="modal-backdrop" id="modal-add-lang">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <div class="modal-title">Add Language</div>
            <button class="modal-close" data-modal-close="modal-add-lang">&times;</button>
        </div>
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger"><?= e($err) ?></div>
                <?php endforeach ?>
                <div class="form-group">
                    <label class="form-label">Language Name <span class="req">*</span></label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. English" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Language Code <span class="req">*</span></label>
                    <input type="text" name="code" class="form-input" placeholder="e.g. en" maxlength="10" required>
                    <div class="form-hint">IETF BCP 47 code (lowercase, e.g. en, fr, ar)</div>
                </div>
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="is_default" value="1">
                        <span>Set as default language</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close="modal-add-lang">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Language</button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Languages', $content);
