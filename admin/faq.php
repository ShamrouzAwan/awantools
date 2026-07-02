<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

// ── POST handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $question = trim(Security::sanitize($_POST['question'] ?? ''));
        $answer   = trim(Security::sanitize($_POST['answer']   ?? ''));
        $category = trim(Security::sanitize($_POST['category'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if (!$question || !$answer) {
            Session::flash('danger', 'Question and answer are required.');
        } else {
            if ($action === 'add') {
                $db->insert('faqs', [
                    'question'   => $question,
                    'answer'     => $answer,
                    'category'   => $category ?: null,
                    'sort_order' => $sortOrder,
                    'is_active'  => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                Session::flash('success', 'FAQ added.');
            } else {
                $id = (int)$_POST['id'];
                $db->update('faqs', [
                    'question'   => $question,
                    'answer'     => $answer,
                    'category'   => $category ?: null,
                    'sort_order' => $sortOrder,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$id]);
                Session::flash('success', 'FAQ updated.');
            }
        }
        redirect('/admin/faq');
    }

    if ($action === 'delete' && !empty($_POST['id'])) {
        $db->delete('faqs', 'id = ?', [(int)$_POST['id']]);
        Session::flash('success', 'FAQ deleted.');
        redirect('/admin/faq');
    }

    if ($action === 'toggle' && !empty($_POST['id'])) {
        $faq = $db->fetch('SELECT is_active FROM faqs WHERE id = ?', [(int)$_POST['id']]);
        if ($faq) {
            $db->update('faqs', [
                'is_active'  => $faq['is_active'] ? 0 : 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [(int)$_POST['id']]);
        }
        redirect('/admin/faq');
    }

    if ($action === 'reorder') {
        $order = $_POST['order'] ?? [];
        if (is_array($order)) {
            foreach ($order as $i => $id) {
                $db->update('faqs', ['sort_order' => (int)$i * 10], 'id = ?', [(int)$id]);
            }
            Session::flash('success', 'Order saved.');
        }
        redirect('/admin/faq');
    }
}

// ── Load data ─────────────────────────────────────────────────────────────────
$faqs = [];
try {
    $faqs = $db->fetchAll("SELECT * FROM faqs ORDER BY sort_order ASC, id ASC") ?: [];
} catch (Throwable $e) {
    Session::flash('warning', 'FAQs table not found — the schema migration will create it on next page load.');
}

$categories = [];
foreach ($faqs as $f) {
    if ($f['category']) $categories[$f['category']] = true;
}
$categories = array_keys($categories);

$editId = (int)($_GET['edit'] ?? 0);
$editFaq = $editId ? $db->fetch('SELECT * FROM faqs WHERE id = ?', [$editId]) : null;

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">FAQs</div>
            <div class="page-subtitle"><?= count($faqs) ?> question<?= count($faqs) !== 1 ? 's' : '' ?> — drag to reorder, click Edit to change</div>
        </div>
    </div>
    <div class="topbar-actions">
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('add-faq-form').style.display=document.getElementById('add-faq-form').style.display==='none'?'block':'none'">+ Add FAQ</button>
    </div>
</div>

<div class="page-body">

<!-- Add FAQ form -->
<div id="add-faq-form" style="<?= $editFaq ? 'display:none' : 'display:none' ?>margin-bottom:20px">
    <div class="card">
        <div class="card-header"><span class="card-title">Add New FAQ</span></div>
        <div class="card-body">
            <form method="POST" data-loading>
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label class="form-label">Question <span class="req">*</span></label>
                    <input type="text" name="question" class="form-input" placeholder="What is..." required>
                </div>
                <div class="form-group">
                    <label class="form-label">Answer <span class="req">*</span></label>
                    <textarea name="answer" class="form-input" rows="4" placeholder="The answer..." required></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr auto;gap:12px">
                    <div class="form-group mb-0">
                        <label class="form-label">Category <span style="color:var(--color-text-muted);font-size:12px">(optional)</span></label>
                        <input type="text" name="category" class="form-input" placeholder="e.g. General, Billing"
                               list="faq-categories-list" value="">
                        <datalist id="faq-categories-list">
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>">
                            <?php endforeach ?>
                        </datalist>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-input" value="<?= count($faqs) * 10 ?>" style="width:90px">
                    </div>
                </div>
                <div style="margin-top:16px;display:flex;gap:8px">
                    <button type="submit" class="btn btn-primary" data-loading="Saving...">Add FAQ</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('add-faq-form').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editFaq): ?>
<!-- Edit FAQ form -->
<div style="margin-bottom:20px">
    <div class="card">
        <div class="card-header"><span class="card-title">Edit FAQ</span></div>
        <div class="card-body">
            <form method="POST" data-loading>
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= (int)$editFaq['id'] ?>">
                <div class="form-group">
                    <label class="form-label">Question <span class="req">*</span></label>
                    <input type="text" name="question" class="form-input" value="<?= e($editFaq['question']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Answer <span class="req">*</span></label>
                    <textarea name="answer" class="form-input" rows="5" required><?= e($editFaq['answer']) ?></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr auto;gap:12px">
                    <div class="form-group mb-0">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" class="form-input" value="<?= e($editFaq['category'] ?? '') ?>"
                               list="faq-categories-list2">
                        <datalist id="faq-categories-list2">
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>">
                            <?php endforeach ?>
                        </datalist>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-input" value="<?= (int)$editFaq['sort_order'] ?>" style="width:90px">
                    </div>
                </div>
                <div style="margin-top:16px;display:flex;gap:8px">
                    <button type="submit" class="btn btn-primary" data-loading="Saving...">Save Changes</button>
                    <a href="/admin/faq" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif ?>

<?php if (empty($faqs)): ?>
<div class="empty-state">
    <div class="empty-state-icon">&#10067;</div>
    <h3>No FAQs yet</h3>
    <p>Add your first FAQ using the button above. FAQs appear on the homepage and can be displayed anywhere on the site.</p>
    <button class="btn btn-primary" onclick="document.getElementById('add-faq-form').style.display='block'">Add First FAQ</button>
</div>
<?php else: ?>

<form id="reorder-form" method="POST" action="/admin/faq">
    <?= Security::csrfField() ?>
    <input type="hidden" name="action" value="reorder">

    <div id="faq-list" style="display:flex;flex-direction:column;gap:6px">
        <?php foreach ($faqs as $faq): ?>
        <div class="faq-row" data-id="<?= (int)$faq['id'] ?>"
             style="display:flex;align-items:flex-start;gap:10px;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-medium);padding:14px 16px;cursor:grab;<?= !$faq['is_active'] ? 'opacity:.6' : '' ?>">
            <input type="hidden" name="order[]" value="<?= (int)$faq['id'] ?>">
            <div style="color:var(--color-text-muted);cursor:grab;padding-top:2px;flex-shrink:0;font-size:16px">&#8942;&#8942;</div>
            <div style="flex:1;min-width:0">
                <div style="font-weight:600;font-size:14px;color:var(--color-text);margin-bottom:4px">
                    <?= e($faq['question']) ?>
                    <?php if ($faq['category']): ?>
                    <span class="badge badge-neutral" style="font-size:10px;margin-left:6px"><?= e($faq['category']) ?></span>
                    <?php endif ?>
                </div>
                <div style="font-size:13px;color:var(--color-text-secondary);line-height:1.5;white-space:pre-wrap"><?= e(substr($faq['answer'], 0, 180)) ?><?= strlen($faq['answer']) > 180 ? '…' : '' ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;padding-top:2px">
                <span class="badge <?= $faq['is_active'] ? 'badge-success' : 'badge-neutral' ?>">
                    <?= $faq['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
                <a href="/admin/faq?edit=<?= (int)$faq['id'] ?>" class="btn btn-ghost btn-xs">Edit</a>
                <form method="POST" style="display:inline">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int)$faq['id'] ?>">
                    <button class="btn btn-ghost btn-xs"><?= $faq['is_active'] ? 'Hide' : 'Show' ?></button>
                </form>
                <form method="POST" style="display:inline"
                      onsubmit="return confirm('Delete this FAQ?')">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$faq['id'] ?>">
                    <button class="btn btn-danger btn-xs">Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach ?>
    </div>

    <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
        <button type="submit" class="btn btn-secondary btn-sm">Save Order</button>
        <span style="font-size:12px;color:var(--color-text-muted)">Drag rows to reorder, then save.</span>
    </div>
</form>
<?php endif ?>

</div>

<script>
(function() {
    var list = document.getElementById('faq-list');
    if (!list) return;
    var dragging = null;
    list.querySelectorAll('.faq-row').forEach(function(r) { r.setAttribute('draggable', 'true'); });
    list.addEventListener('dragstart', function(e) {
        dragging = e.target.closest('.faq-row');
        if (dragging) { dragging.style.opacity = '.4'; e.dataTransfer.effectAllowed = 'move'; }
    });
    list.addEventListener('dragend', function() {
        if (dragging) { dragging.style.opacity = ''; dragging = null; }
    });
    list.addEventListener('dragover', function(e) {
        e.preventDefault();
        var target = e.target.closest('.faq-row');
        if (!target || target === dragging) return;
        var box = target.getBoundingClientRect();
        list.insertBefore(dragging, e.clientY < box.top + box.height / 2 ? target : target.nextSibling);
    });
})();
</script>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('FAQs', $content, ['section' => 'faq']);
