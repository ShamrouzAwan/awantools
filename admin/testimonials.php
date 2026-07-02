<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

// ─── POST Actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name        = Security::sanitize($_POST['name'] ?? '');
        $company     = Security::sanitize($_POST['company'] ?? '');
        $title       = Security::sanitize($_POST['title'] ?? '');
        $photo       = Security::sanitize($_POST['photo'] ?? '');
        $testimonial = Security::sanitize($_POST['testimonial'] ?? '');
        $rating      = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $is_active   = isset($_POST['is_active']) ? 1 : 0;
        $sort_order  = (int)($_POST['sort_order'] ?? 0);

        if (empty($name) || empty($testimonial)) {
            Session::flash('danger', 'Name and testimonial are required.');
            redirect('/admin/testimonials');
        }

        $data = compact('name', 'company', 'title', 'photo', 'testimonial', 'rating', 'is_active', 'sort_order');

        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $db->update('testimonials', $data, 'id = ?', [$id]);
            Session::flash('success', 'Testimonial updated.');
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->insert('testimonials', $data);
            Session::flash('success', 'Testimonial added.');
        }
        redirect('/admin/testimonials');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->delete('testimonials', 'id = ?', [$id]);
        Session::flash('success', 'Testimonial deleted.');
        redirect('/admin/testimonials');
    }

    if ($action === 'toggle') {
        $id  = (int)($_POST['id'] ?? 0);
        $row = $db->fetch("SELECT is_active FROM testimonials WHERE id = ?", [$id]);
        if ($row) {
            $db->update('testimonials', ['is_active' => $row['is_active'] ? 0 : 1], 'id = ?', [$id]);
        }
        redirect('/admin/testimonials');
    }

    redirect('/admin/testimonials');
}

// ─── Edit mode ────────────────────────────────────────────────────────────────
$editId   = (int)($_GET['edit'] ?? 0);
$editItem = $editId ? $db->fetch("SELECT * FROM testimonials WHERE id = ?", [$editId]) : null;

// ─── Query ────────────────────────────────────────────────────────────────────
$testimonials = $db->fetchAll("SELECT * FROM testimonials ORDER BY sort_order ASC, id DESC");
$total        = count($testimonials);

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Testimonials</div>
            <div class="page-subtitle"><?= $total ?> testimonial<?= $total !== 1 ? 's' : '' ?></div>
        </div>
    </div>
</div>

<div class="page-body">
<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">

<!-- ─── List ─────────────────────────────────────────────────────────── -->
<div>
<?php if (empty($testimonials)): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:48px 24px;color:var(--color-text-muted)">
        No testimonials yet. Add your first one on the right.
    </div>
</div>
<?php else: ?>
<?php foreach ($testimonials as $t): ?>
<div class="card" style="margin-bottom:12px">
    <div class="card-body" style="display:flex;gap:16px;align-items:flex-start">
        <?php if ($t['photo']): ?>
        <img src="<?= e($t['photo']) ?>" alt="<?= e($t['name']) ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;flex-shrink:0">
        <?php else: ?>
        <div style="width:48px;height:48px;border-radius:50%;background:var(--color-primary-light);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;color:var(--color-primary);flex-shrink:0"><?= strtoupper(substr($t['name'],0,1)) ?></div>
        <?php endif ?>
        <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                <strong><?= e($t['name']) ?></strong>
                <?php if ($t['company']): ?><span style="font-size:12px;color:var(--color-text-muted)"><?= e($t['company']) ?></span><?php endif ?>
                <span class="badge <?= $t['is_active'] ? 'badge-success' : 'badge-neutral' ?>"><?= $t['is_active'] ? 'Active' : 'Hidden' ?></span>
            </div>
            <div style="color:#f59e0b;font-size:14px;margin-bottom:6px"><?= str_repeat('★', (int)$t['rating']) . str_repeat('☆', 5 - (int)$t['rating']) ?></div>
            <div style="font-size:13px;color:var(--color-text-secondary);font-style:italic">"<?= e(substr($t['testimonial'], 0, 180)) ?><?= strlen($t['testimonial']) > 180 ? '…' : '' ?>"</div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
            <a href="/admin/testimonials?edit=<?= $t['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
            <form method="POST" style="display:inline">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm"><?= $t['is_active'] ? 'Hide' : 'Show' ?></button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this testimonial?')">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div>
    </div>
</div>
<?php endforeach ?>
<?php endif ?>
</div>

<!-- ─── Form ──────────────────────────────────────────────────────────── -->
<div>
<div class="card">
    <div class="card-header">
        <span class="card-title"><?= $editItem ? 'Edit Testimonial' : 'Add Testimonial' ?></span>
        <?php if ($editItem): ?><a href="/admin/testimonials" class="btn btn-ghost btn-sm">Cancel</a><?php endif ?>
    </div>
    <div class="card-body">
        <form method="POST" data-loading>
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="<?= $editItem ? 'update' : 'create' ?>">
            <?php if ($editItem): ?><input type="hidden" name="id" value="<?= $editItem['id'] ?>"><?php endif ?>

            <div class="form-group">
                <label class="form-label">Name <span style="color:var(--color-danger)">*</span></label>
                <input type="text" name="name" class="form-input" value="<?= e($editItem['name'] ?? '') ?>" placeholder="Jane Smith" required>
            </div>

            <div class="form-group">
                <label class="form-label">Company</label>
                <input type="text" name="company" class="form-input" value="<?= e($editItem['company'] ?? '') ?>" placeholder="Acme Corp">
            </div>

            <div class="form-group">
                <label class="form-label">Title / Role</label>
                <input type="text" name="title" class="form-input" value="<?= e($editItem['title'] ?? '') ?>" placeholder="CEO">
            </div>

            <div class="form-group">
                <label class="form-label">Photo URL</label>
                <div style="display:flex;gap:8px">
                    <input type="text" name="photo" id="t-photo" class="form-input" value="<?= e($editItem['photo'] ?? '') ?>" placeholder="https://...">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="openMediaPicker(function(url){document.getElementById('t-photo').value=url})">Choose</button>
                </div>
                <div class="form-hint">Leave blank to use an initial avatar.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Testimonial <span style="color:var(--color-danger)">*</span></label>
                <textarea name="testimonial" class="form-input" rows="4" placeholder="What they said…" required><?= e($editItem['testimonial'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Rating</label>
                <select name="rating" class="form-input">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>" <?= ($editItem['rating'] ?? 5) == $i ? 'selected' : '' ?>><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
                    <?php endfor ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-input" value="<?= (int)($editItem['sort_order'] ?? 0) ?>" min="0" max="999">
            </div>

            <div class="form-group mb-0">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="is_active" <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0">Visible on homepage</span>
                </label>
            </div>

            <div style="margin-top:16px">
                <button type="submit" class="btn btn-primary w-full" data-loading="Saving…">
                    <?= $editItem ? 'Update Testimonial' : 'Add Testimonial' ?>
                </button>
            </div>
        </form>
    </div>
</div>
</div>

</div><!-- /grid -->
</div><!-- /page-body -->
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Testimonials', $content, ['section' => 'testimonials']);
