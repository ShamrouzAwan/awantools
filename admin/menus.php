<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$logger = Logger::getInstance($db);
$loc    = in_array($_GET['loc'] ?? 'header', ['header','footer']) ? ($_GET['loc']) : 'header';

// ─── POST Actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $label    = trim(Security::sanitize($_POST['label'] ?? ''));
        $url      = trim(Security::sanitize($_POST['url'] ?? ''));
        $target   = in_array($_POST['target'] ?? '_self', ['_self','_blank']) ? $_POST['target'] : '_self';
        $location = in_array($_POST['location'] ?? 'header', ['header','footer','both']) ? $_POST['location'] : 'header';
        $active   = isset($_POST['is_active']) ? 1 : 0;

        if (!$label || !$url) {
            Session::flash('danger', 'Label and URL are required.');
            redirect('/admin/menus?loc=' . $location);
        }

        if ($action === 'add') {
            $maxOrder = $db->fetch("SELECT MAX(sort_order) as m FROM nav_items WHERE location = ?", [$location]);
            $db->insert('nav_items', [
                'label'      => $label,
                'url'        => $url,
                'target'     => $target,
                'location'   => $location,
                'sort_order' => (int)($maxOrder['m'] ?? 0) + 1,
                'is_active'  => $active,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            Session::flash('success', 'Menu item added.');
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $db->update('nav_items', [
                'label'     => $label,
                'url'       => $url,
                'target'    => $target,
                'location'  => $location,
                'is_active' => $active,
            ], 'id = ?', [$id]);
            Session::flash('success', 'Menu item updated.');
        }
        redirect('/admin/menus?loc=' . $location);
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->query("DELETE FROM nav_items WHERE id = ?", [$id]);
        Session::flash('success', 'Menu item deleted.');
        redirect('/admin/menus?loc=' . $loc);
    }

    if ($action === 'move') {
        $id        = (int)($_POST['id'] ?? 0);
        $direction = $_POST['direction'] ?? '';
        $item = $db->fetch("SELECT * FROM nav_items WHERE id = ?", [$id]);
        if ($item && in_array($direction, ['up','down'])) {
            if ($direction === 'up') {
                $swap = $db->fetch(
                    "SELECT * FROM nav_items WHERE location = ? AND sort_order < ? ORDER BY sort_order DESC LIMIT 1",
                    [$item['location'], $item['sort_order']]
                );
            } else {
                $swap = $db->fetch(
                    "SELECT * FROM nav_items WHERE location = ? AND sort_order > ? ORDER BY sort_order ASC LIMIT 1",
                    [$item['location'], $item['sort_order']]
                );
            }
            if ($swap) {
                $db->update('nav_items', ['sort_order' => $swap['sort_order']], 'id = ?', [$item['id']]);
                $db->update('nav_items', ['sort_order' => $item['sort_order']], 'id = ?', [$swap['id']]);
            }
        }
        redirect('/admin/menus?loc=' . $item['location']);
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $item = $db->fetch("SELECT is_active FROM nav_items WHERE id = ?", [$id]);
        if ($item) {
            $db->update('nav_items', ['is_active' => $item['is_active'] ? 0 : 1], 'id = ?', [$id]);
        }
        redirect('/admin/menus?loc=' . $loc);
    }

    if ($action === 'seed_defaults') {
        $seedLoc = in_array($_POST['seed_loc'] ?? 'header', ['header','footer']) ? $_POST['seed_loc'] : 'header';
        $seeds = $seedLoc === 'header' ? [
            ['label' => 'Home',    'url' => '/'],
            ['label' => 'Tools',   'url' => '/plugins'],
            ['label' => 'Blog',    'url' => '/blog'],
            ['label' => 'FAQ',     'url' => '/faq'],
            ['label' => 'Contact', 'url' => '/contact'],
        ] : [
            ['label' => 'Home',           'url' => '/'],
            ['label' => 'About',          'url' => '/about'],
            ['label' => 'Get a Quote',    'url' => '/get-a-quote'],
            ['label' => 'Request a Tool', 'url' => '/request-tool'],
            ['label' => 'Privacy Policy', 'url' => '/privacy'],
            ['label' => 'Terms of Service', 'url' => '/terms'],
        ];
        $sort = 1;
        foreach ($seeds as $seed) {
            if (!$db->exists('nav_items', 'label = ? AND location = ?', [$seed['label'], $seedLoc])) {
                $db->insert('nav_items', [
                    'label'      => $seed['label'],
                    'url'        => $seed['url'],
                    'target'     => '_self',
                    'location'   => $seedLoc,
                    'sort_order' => $sort++,
                    'is_active'  => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
        Session::flash('success', 'Default ' . $seedLoc . ' links added. You can now edit or reorder them.');
        redirect('/admin/menus?loc=' . $seedLoc);
    }
}

// ─── Load items ───────────────────────────────────────────────────────────────
$headerItems = $db->fetchAll("SELECT * FROM nav_items WHERE location = 'header' ORDER BY sort_order, id") ?: [];
$footerItems = $db->fetchAll("SELECT * FROM nav_items WHERE location = 'footer' ORDER BY sort_order, id") ?: [];
$editItem    = null;
if (isset($_GET['edit'])) {
    $editItem = $db->fetch("SELECT * FROM nav_items WHERE id = ?", [(int)$_GET['edit']]);
}

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div class="page-title">Menu Management</div>
        <div class="page-subtitle">Control navigation links shown on the front-end</div>
    </div>
</div>

<div class="page-body">

<!-- Tabs -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:1px solid var(--color-border)">
    <a href="?loc=header" style="padding:8px 16px;font-size:13px;font-weight:500;text-decoration:none;border-bottom:2px solid <?= $loc==='header' ? 'var(--color-primary)' : 'transparent' ?>;color:<?= $loc==='header' ? 'var(--color-primary)' : 'var(--color-text-secondary)' ?>;margin-bottom:-1px">Header Menu</a>
    <a href="?loc=footer" style="padding:8px 16px;font-size:13px;font-weight:500;text-decoration:none;border-bottom:2px solid <?= $loc==='footer' ? 'var(--color-primary)' : 'transparent' ?>;color:<?= $loc==='footer' ? 'var(--color-primary)' : 'var(--color-text-secondary)' ?>;margin-bottom:-1px">Footer Menu</a>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

<!-- Items list -->
<div>
    <?php $items = $loc === 'header' ? $headerItems : $footerItems; ?>
    <?php if (empty($items)): ?>
    <div class="card">
        <div class="card-body" style="text-align:center;padding:40px">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;opacity:.4;color:var(--color-text-muted)"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            <p style="color:var(--color-text-muted);margin-bottom:16px">No <?= $loc ?> menu items yet.</p>
            <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
                <span style="font-size:13px;color:var(--color-text-secondary)">Use the form to add items, or:</span>
                <form method="POST" style="margin:0">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="seed_defaults">
                    <input type="hidden" name="seed_loc" value="<?= $loc ?>">
                    <button type="submit" class="btn btn-secondary btn-sm">Add default <?= $loc ?> links</button>
                </form>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:40px"></th>
                    <th>Label</th>
                    <th>URL</th>
                    <th style="width:70px">Target</th>
                    <th style="width:70px">Status</th>
                    <th style="width:120px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <tr style="<?= $editItem && $editItem['id'] == $item['id'] ? 'background:var(--color-primary-light, #ede9fe)' : '' ?>">
                <td style="color:var(--color-text-muted);font-size:11px">
                    <div style="display:flex;flex-direction:column;gap:2px">
                        <form method="POST" style="margin:0">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="move">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="direction" value="up">
                            <button type="submit" class="btn btn-ghost btn-sm" style="padding:2px 4px;font-size:10px" title="Move up">▲</button>
                        </form>
                        <form method="POST" style="margin:0">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="move">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="direction" value="down">
                            <button type="submit" class="btn btn-ghost btn-sm" style="padding:2px 4px;font-size:10px" title="Move down">▼</button>
                        </form>
                    </div>
                </td>
                <td><strong><?= e($item['label']) ?></strong></td>
                <td style="font-size:12px;color:var(--color-text-muted);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($item['url']) ?></td>
                <td>
                    <span class="badge <?= $item['target']==='_blank' ? 'badge-warning' : 'badge-secondary' ?>" style="font-size:10px">
                        <?= $item['target'] === '_blank' ? 'New tab' : 'Same' ?>
                    </span>
                </td>
                <td>
                    <form method="POST" style="margin:0">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn btn-ghost btn-sm" style="padding:2px 8px;font-size:11px">
                            <?php if ($item['is_active']): ?>
                            <span style="color:#16a34a">● Active</span>
                            <?php else: ?>
                            <span style="color:var(--color-text-muted)">○ Hidden</span>
                            <?php endif ?>
                        </button>
                    </form>
                </td>
                <td>
                    <div style="display:flex;gap:4px">
                        <a href="?loc=<?= $loc ?>&edit=<?= $item['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                        <form method="POST" style="margin:0" onsubmit="return confirm('Delete this menu item?')">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-danger)">Del</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>

    <div class="card" style="margin-top:16px;border-color:var(--color-warning,#f59e0b)">
        <div class="card-body" style="padding:12px 16px">
            <p style="font-size:12px;color:var(--color-text-secondary);line-height:1.6;margin:0">
                <strong>How nav items work:</strong> When the header list is empty, the public nav shows hardcoded fallback links (Home, Tools, Blog, FAQ, Contact). Once you add <em>any</em> item here, the nav switches fully to DB-driven — only items you manage here will appear. Use "Add default header links" (shown when empty) to pre-populate all the standard links so you can customise them.
            </p>
        </div>
    </div>
</div>

<!-- Add / Edit Form -->
<div class="card" style="position:sticky;top:16px">
    <div class="card-header">
        <span class="card-title"><?= $editItem ? 'Edit Item' : 'Add Item' ?></span>
        <?php if ($editItem): ?>
        <a href="?loc=<?= $loc ?>" class="btn btn-ghost btn-sm">Cancel</a>
        <?php endif ?>
    </div>
    <div class="card-body">
        <form method="POST" action="/admin/menus?loc=<?= $loc ?>">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="<?= $editItem ? 'edit' : 'add' ?>">
            <?php if ($editItem): ?>
            <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif ?>

            <div class="form-group">
                <label class="form-label">Label <span class="req">*</span></label>
                <input type="text" name="label" class="form-input" required
                       value="<?= e($editItem['label'] ?? '') ?>"
                       placeholder="e.g. About Us">
            </div>

            <div class="form-group">
                <label class="form-label">URL <span class="req">*</span></label>
                <input type="text" name="url" class="form-input" required
                       value="<?= e($editItem['url'] ?? '') ?>"
                       placeholder="/about or https://example.com">
                <div class="form-hint">Use relative paths (e.g. /about) or full URLs.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Location</label>
                <select name="location" class="form-input">
                    <option value="header" <?= ($editItem['location'] ?? $loc) === 'header' ? 'selected' : '' ?>>Header Menu</option>
                    <option value="footer" <?= ($editItem['location'] ?? $loc) === 'footer' ? 'selected' : '' ?>>Footer Menu</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Open In</label>
                <select name="target" class="form-input">
                    <option value="_self"  <?= ($editItem['target'] ?? '_self') === '_self'  ? 'selected' : '' ?>>Same tab</option>
                    <option value="_blank" <?= ($editItem['target'] ?? '') === '_blank' ? 'selected' : '' ?>>New tab</option>
                </select>
            </div>

            <div class="form-group mb-0">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="is_active" value="1"
                           <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0">Active (visible)</span>
                </label>
            </div>

            <div style="margin-top:16px">
                <button type="submit" class="btn btn-primary w-full">
                    <?= $editItem ? 'Save Changes' : 'Add Item' ?>
                </button>
            </div>
        </form>
    </div>
</div>

</div>
</div>

<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Menu Management', $content, ['section' => 'menus']);
