<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$logger = Logger::getInstance($db);
$editSlug = Security::sanitize($_GET['edit'] ?? '');
$editItem = null;
if ($editSlug) {
    $editItem = $db->fetch("SELECT * FROM email_templates WHERE slug = ?", [$editSlug]);
}

// ─── POST Actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $slug      = Security::sanitize($_POST['slug'] ?? '');
        $name      = Security::sanitize($_POST['name'] ?? '');
        $subject   = Security::sanitize($_POST['subject'] ?? '');
        $body      = $_POST['body'] ?? '';
        $variables = Security::sanitize($_POST['variables'] ?? '');

        if (!$name || !$subject || !$body) {
            Session::flash('danger', 'Name, subject, and body are required.');
            redirect('/admin/email-templates?edit=' . urlencode($slug));
        }

        $existing = $db->fetch("SELECT id, is_system FROM email_templates WHERE slug = ?", [$slug]);
        if ($existing) {
            $db->update('email_templates', [
                'name'       => $name,
                'subject'    => $subject,
                'body'       => $body,
                'variables'  => $variables,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'slug = ?', [$slug]);
            Session::flash('success', 'Template updated.');
        } else {
            // New custom template
            if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
                Session::flash('danger', 'Slug must contain only lowercase letters, numbers, and hyphens.');
                redirect('/admin/email-templates');
            }
            if ($db->exists('email_templates', 'slug = ?', [$slug])) {
                Session::flash('danger', 'A template with this slug already exists.');
                redirect('/admin/email-templates');
            }
            $db->insert('email_templates', [
                'slug'       => $slug,
                'name'       => $name,
                'subject'    => $subject,
                'body'       => $body,
                'variables'  => $variables,
                'is_system'  => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            Session::flash('success', 'Template created.');
        }
        $logger->info("Email template saved: {$slug}", [], $auth->id());
        redirect('/admin/email-templates');
    }

    if ($action === 'delete') {
        $slug = Security::sanitize($_POST['slug'] ?? '');
        $tpl  = $db->fetch("SELECT is_system FROM email_templates WHERE slug = ?", [$slug]);
        if ($tpl && !$tpl['is_system']) {
            $db->query("DELETE FROM email_templates WHERE slug = ?", [$slug]);
            Session::flash('success', 'Template deleted.');
        } else {
            Session::flash('danger', 'System templates cannot be deleted.');
        }
        redirect('/admin/email-templates');
    }

    if ($action === 'add_new') {
        redirect('/admin/email-templates?new=1');
    }
}

$templates  = $db->fetchAll("SELECT * FROM email_templates ORDER BY is_system DESC, name") ?: [];
$isNew      = isset($_GET['new']);

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div class="page-title">Email Templates</div>
        <div class="page-subtitle">Customize transactional email content and subjects</div>
    </div>
    <div class="page-header-right">
        <?php if (!$editItem && !$isNew): ?>
        <a href="?new=1" class="btn btn-primary btn-sm">+ Add Template</a>
        <?php endif ?>
    </div>
</div>

<div class="page-body">

<?php if ($editItem || $isNew): ?>
<!-- Edit / New Form -->
<div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start">
<div>
    <div class="card">
        <div class="card-header">
            <span class="card-title"><?= $editItem ? 'Edit Template: ' . e($editItem['name']) : 'New Template' ?></span>
            <a href="/admin/email-templates" class="btn btn-ghost btn-sm">← Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="/admin/email-templates">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="save">

                <div class="grid-2" style="gap:12px;margin-bottom:16px">
                    <div class="form-group mb-0">
                        <label class="form-label">Template Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-input" required
                               value="<?= e($editItem['name'] ?? '') ?>" placeholder="e.g. Welcome Email">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Slug <?= (!$editItem && !$isNew) ? '<span class="req">*</span>' : '' ?></label>
                        <input type="text" name="slug" class="form-input"
                               value="<?= e($editItem['slug'] ?? '') ?>"
                               placeholder="e.g. my-custom-email"
                               <?= $editItem ? 'readonly style="background:var(--color-background);color:var(--color-text-muted)"' : 'required' ?>>
                        <div class="form-hint">Lowercase letters, numbers, hyphens only. Cannot be changed.</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Subject <span class="req">*</span></label>
                    <input type="text" name="subject" class="form-input" required
                           value="<?= e($editItem['subject'] ?? '') ?>"
                           placeholder="e.g. Welcome to {{site_name}}!">
                </div>

                <div class="form-group">
                    <label class="form-label">Body (HTML) <span class="req">*</span></label>
                    <textarea name="body" class="form-input" rows="14" required
                              style="font-family:monospace;font-size:12px"
                              placeholder="<p>Hi {{name}},</p>..."><?= e($editItem['body'] ?? '') ?></textarea>
                    <div class="form-hint">Use <code>{{variable}}</code> placeholders for dynamic content.</div>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label">Available Variables (hint)</label>
                    <input type="text" name="variables" class="form-input"
                           value="<?= e($editItem['variables'] ?? '') ?>"
                           placeholder="e.g. name, site_name, verify_url">
                    <div class="form-hint">Comma-separated list — documentation only, not enforced.</div>
                </div>

                <div style="margin-top:20px;display:flex;gap:8px">
                    <button type="submit" class="btn btn-primary">Save Template</button>
                    <a href="/admin/email-templates" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<div>
    <?php if ($editItem): ?>
    <div class="card">
        <div class="card-header"><span class="card-title">Email Preview</span></div>
        <div class="card-body" style="padding:12px">
            <?php
            $sn   = e($settings->siteName());
            $prev = $editItem['body'];
            $prev = str_replace(['{{site_name}}','{{name}}','{{code}}'],
                                [$sn, 'John', '123456'], $prev);
            ?>
            <div style="background:#f8fafc;border:1px solid var(--color-border);border-radius:6px;padding:16px;font-size:13px;max-height:340px;overflow:auto">
                <?= $prev ?>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:12px">
        <div class="card-header"><span class="card-title">Template Info</span></div>
        <div class="card-body">
            <div style="font-size:12px;color:var(--color-text-muted)">
                <div style="margin-bottom:6px"><strong>Slug:</strong> <code><?= e($editItem['slug']) ?></code></div>
                <div style="margin-bottom:6px"><strong>Type:</strong> <?= $editItem['is_system'] ? '<span style="color:#6366f1">System</span>' : 'Custom' ?></div>
                <div style="margin-bottom:6px"><strong>Created:</strong> <?= fdate($editItem['created_at']) ?></div>
                <?php if ($editItem['updated_at']): ?>
                <div><strong>Updated:</strong> <?= fdate($editItem['updated_at']) ?></div>
                <?php endif ?>
            </div>
            <?php if (!$editItem['is_system']): ?>
            <form method="POST" style="margin-top:12px" onsubmit="return confirm('Delete this template?')">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="slug" value="<?= e($editItem['slug']) ?>">
                <button type="submit" class="btn btn-danger btn-sm w-full">Delete Template</button>
            </form>
            <?php else: ?>
            <div style="margin-top:12px;padding:8px 12px;background:#fef9ec;border:1px solid #fde68a;border-radius:6px;font-size:12px;color:#92400e">
                System templates are required by the platform and cannot be deleted.
            </div>
            <?php endif ?>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-header"><span class="card-title">Tips</span></div>
        <div class="card-body" style="font-size:13px;color:var(--color-text-secondary)">
            <p style="margin-bottom:8px">Use <code style="font-size:11px">{{variable}}</code> syntax for dynamic content.</p>
            <p style="margin-bottom:8px">Common variables: <code style="font-size:11px">site_name</code>, <code style="font-size:11px">name</code>.</p>
            <p>A <strong>CTA button</strong> is added automatically when <code style="font-size:11px">cta_text</code> and <code style="font-size:11px">cta_url</code> are passed when sending.</p>
        </div>
    </div>
    <?php endif ?>
</div>
</div>

<?php else: ?>
<!-- Template list -->
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Subject</th>
                <th style="width:70px">Type</th>
                <th style="width:80px">Updated</th>
                <th style="width:80px">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($templates as $tpl): ?>
        <tr>
            <td>
                <strong><?= e($tpl['name']) ?></strong>
            </td>
            <td><code style="font-size:11px"><?= e($tpl['slug']) ?></code></td>
            <td style="font-size:12px;color:var(--color-text-muted);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($tpl['subject']) ?></td>
            <td>
                <?php if ($tpl['is_system']): ?>
                <span class="badge" style="background:#ede9fe;color:#6366f1;font-size:10px">System</span>
                <?php else: ?>
                <span class="badge badge-secondary" style="font-size:10px">Custom</span>
                <?php endif ?>
            </td>
            <td style="font-size:11px;color:var(--color-text-muted)"><?= $tpl['updated_at'] ? fdate($tpl['updated_at']) : '—' ?></td>
            <td>
                <div style="display:flex;gap:4px">
                    <a href="?edit=<?= urlencode($tpl['slug']) ?>" class="btn btn-ghost btn-sm">Edit</a>
                    <?php if (!$tpl['is_system']): ?>
                    <form method="POST" onsubmit="return confirm('Delete?')">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="slug" value="<?= e($tpl['slug']) ?>">
                        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-danger)">Del</button>
                    </form>
                    <?php endif ?>
                </div>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php endif ?>

</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Email Templates', $content, ['section' => 'email-templates']);
