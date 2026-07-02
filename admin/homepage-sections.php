<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

// ── Handle reorder / toggle actions ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    $action = $_POST['action'] ?? '';

    // Toggle a section on/off
    if ($action === 'toggle') {
        $key     = Security::sanitize($_POST['section_key'] ?? '');
        $enabled = isset($_POST['enabled']) ? (int)$_POST['enabled'] : null;
        if ($key && $enabled !== null) {
            try {
                $db->update('homepage_sections',
                    ['is_enabled' => $enabled, 'updated_at' => date('Y-m-d H:i:s')],
                    'section_key = ?', [$key]
                );
                Session::flash('success', 'Section updated.');
            } catch (Throwable $e) {
                Session::flash('danger', 'Failed to update section.');
            }
        }
        redirect('/admin/homepage-sections');
    }

    // Reorder all sections
    if ($action === 'reorder') {
        $order = $_POST['order'] ?? [];
        if (is_array($order)) {
            foreach ($order as $i => $key) {
                $key = Security::sanitize((string)$key);
                try {
                    $db->update('homepage_sections',
                        ['sort_order' => (int)$i * 10, 'updated_at' => date('Y-m-d H:i:s')],
                        'section_key = ?', [$key]
                    );
                } catch (Throwable $e) {}
            }
            Session::flash('success', 'Section order saved.');
        }
        redirect('/admin/homepage-sections');
    }

    // Save config for a single section
    if ($action === 'save_config') {
        $key    = Security::sanitize($_POST['section_key'] ?? '');
        $config = [];

        // Collect all posted config_ fields
        foreach ($_POST as $field => $val) {
            if (str_starts_with($field, 'config_')) {
                $cfgKey          = substr($field, 7);
                $config[$cfgKey] = Security::sanitize((string)$val);
            }
        }

        // Booleans / checkboxes
        foreach (['show_title', 'show_description', 'show_cta'] as $ck) {
            $config[$ck] = isset($_POST['config_' . $ck]) ? '1' : '0';
        }

        if ($key) {
            try {
                $db->update('homepage_sections',
                    ['config' => json_encode($config), 'updated_at' => date('Y-m-d H:i:s')],
                    'section_key = ?', [$key]
                );
                Session::flash('success', 'Section settings saved.');
            } catch (Throwable $e) {
                Session::flash('danger', 'Failed to save section settings.');
            }
        }
        redirect('/admin/homepage-sections');
    }
}

// ── Load sections ─────────────────────────────────────────────────────────────
$sections = [];
try {
    $sections = $db->fetchAll(
        "SELECT * FROM homepage_sections ORDER BY sort_order ASC, name ASC"
    ) ?: [];
} catch (Throwable $e) {
    Session::flash('warning', 'Homepage sections table not found. Run the schema migration to create it.');
}

// Section descriptions
$sectionMeta = [
    'hero'           => ['icon' => '&#9650;',  'desc' => 'Main banner with headline, subtitle, and call-to-action buttons.'],
    'stats'          => ['icon' => '&#128202;','desc' => 'Key statistics like total tools, users, or any custom metric.'],
    'search'         => ['icon' => '&#128269;','desc' => 'Live search bar for browsing installed tools/plugins.'],
    'featured_tools' => ['icon' => '&#128736;','desc' => 'Highlighted grid of active plugins/tools.'],
    'why_us'         => ['icon' => '&#10003;', 'desc' => 'Value proposition list — why users should choose this platform.'],
    'blog'           => ['icon' => '&#128203;','desc' => 'Latest published blog posts.'],
    'testimonials'   => ['icon' => '&#128172;','desc' => 'Social proof quotes from users or clients.'],
    'cta'            => ['icon' => '&#128161;','desc' => 'Full-width call-to-action block with a headline and button.'],
    'faq'            => ['icon' => '&#10067;', 'desc' => 'Frequently asked questions accordion.'],
    'contact'        => ['icon' => '&#9993;',  'desc' => 'Contact form or contact details block.'],
    'custom_block'   => ['icon' => '&#128292;','desc' => 'Free-form HTML content block for any custom section.'],
];

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Homepage Sections</div>
            <div class="page-subtitle">Drag to reorder, toggle sections on/off, and configure each section.</div>
        </div>
    </div>
    <div class="topbar-actions">
        <a href="/admin/settings?tab=homepage" class="btn btn-ghost btn-sm">Hero Settings</a>
        <a href="/" target="_blank" class="btn btn-secondary btn-sm">Preview Site</a>
    </div>
</div>

<div class="page-body">

    <?php if (empty($sections)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">&#128203;</div>
        <h3>No sections found</h3>
        <p>Run the schema migration to seed the default homepage sections.</p>
        <a href="/admin/system" class="btn btn-primary">Go to System</a>
    </div>
    <?php else: ?>

    <div class="card" style="margin-bottom:16px">
        <div class="card-body" style="padding:12px 16px">
            <div style="font-size:13px;color:var(--color-text-muted)">
                Drag sections to reorder them, then click <strong>Save Order</strong>.
                Disabled sections are hidden from the homepage entirely.
                Configure content and settings per section using the Edit button.
            </div>
        </div>
    </div>

    <form id="reorder-form" method="POST" action="/admin/homepage-sections">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="reorder">
        <div id="sections-list" style="display:flex;flex-direction:column;gap:8px">
            <?php foreach ($sections as $sec):
                $key    = $sec['section_key'];
                $meta   = $sectionMeta[$key] ?? ['icon' => '&#9632;', 'desc' => ''];
                $config = json_decode($sec['config'] ?? '{}', true) ?: [];
            ?>
            <div class="section-row" data-key="<?= e($key) ?>"
                 style="display:flex;align-items:center;gap:12px;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-medium);padding:12px 16px;cursor:grab;<?= $sec['is_enabled'] ? '' : 'opacity:.6' ?>">
                <input type="hidden" name="order[]" value="<?= e($key) ?>">
                <!-- Drag handle -->
                <div style="font-size:18px;color:var(--color-text-muted);cursor:grab;flex-shrink:0" title="Drag to reorder">
                    &#8942;&#8942;
                </div>
                <!-- Icon -->
                <div style="font-size:20px;flex-shrink:0;width:28px;text-align:center"><?= $meta['icon'] ?></div>
                <!-- Info -->
                <div style="flex:1;min-width:0">
                    <div style="font-weight:600;font-size:14px"><?= e($sec['name']) ?></div>
                    <div style="font-size:12px;color:var(--color-text-muted)"><?= e($meta['desc']) ?></div>
                </div>
                <!-- Status badge -->
                <div style="flex-shrink:0">
                    <span class="badge <?= $sec['is_enabled'] ? 'badge-success' : 'badge-neutral' ?>">
                        <?= $sec['is_enabled'] ? 'Enabled' : 'Disabled' ?>
                    </span>
                </div>
                <!-- Toggle button -->
                <form method="POST" action="/admin/homepage-sections" style="margin:0">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action"      value="toggle">
                    <input type="hidden" name="section_key" value="<?= e($key) ?>">
                    <input type="hidden" name="enabled"     value="<?= $sec['is_enabled'] ? '0' : '1' ?>">
                    <button type="submit" class="btn btn-ghost btn-sm"
                            title="<?= $sec['is_enabled'] ? 'Disable' : 'Enable' ?> this section">
                        <?= $sec['is_enabled'] ? 'Disable' : 'Enable' ?>
                    </button>
                </form>
                <!-- Edit / configure -->
                <button type="button" class="btn btn-secondary btn-sm"
                        onclick="openSectionConfig('<?= e($key) ?>', <?= htmlspecialchars(json_encode($config), ENT_QUOTES) ?>)">
                    Edit
                </button>
            </div>
            <?php endforeach ?>
        </div>

        <div style="margin-top:16px;display:flex;gap:10px;align-items:center">
            <button type="submit" class="btn btn-primary">Save Order</button>
            <span style="font-size:12px;color:var(--color-text-muted)">Drag rows above to change display order, then save.</span>
        </div>
    </form>

    <?php endif ?>
</div>

<!-- Section Config Modal -->
<div id="section-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
    <div style="background:var(--color-surface);border-radius:12px;padding:0;max-width:520px;width:100%;margin:16px;box-shadow:0 20px 60px rgba(0,0,0,.3);max-height:90vh;overflow:auto">
        <div style="padding:20px 24px;border-bottom:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between">
            <div style="font-weight:700;font-size:16px" id="modal-title">Edit Section</div>
            <button type="button" onclick="closeSectionModal()" style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--color-text-muted)">&times;</button>
        </div>
        <form method="POST" action="/admin/homepage-sections" id="section-config-form">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="save_config">
            <input type="hidden" name="section_key" id="modal-section-key">
            <div id="modal-body" style="padding:24px">
                <!-- Populated by JS -->
            </div>
            <div style="padding:16px 24px;border-top:1px solid var(--color-border);display:flex;gap:10px">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" onclick="closeSectionModal()" class="btn btn-ghost">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// ─── Drag-and-drop reorder ────────────────────────────────────────────────────
(function () {
    var list = document.getElementById('sections-list');
    if (!list) return;
    var dragging = null;
    list.addEventListener('dragstart', function(e) {
        dragging = e.target.closest('.section-row');
        if (dragging) { dragging.style.opacity = '.4'; e.dataTransfer.effectAllowed = 'move'; }
    });
    list.addEventListener('dragend', function() {
        if (dragging) { dragging.style.opacity = ''; dragging = null; }
    });
    list.addEventListener('dragover', function(e) {
        e.preventDefault();
        var target = e.target.closest('.section-row');
        if (!target || target === dragging) return;
        var box = target.getBoundingClientRect();
        if (e.clientY < box.top + box.height / 2) {
            list.insertBefore(dragging, target);
        } else {
            list.insertBefore(dragging, target.nextSibling);
        }
    });
    // Make rows draggable
    list.querySelectorAll('.section-row').forEach(function(r) { r.setAttribute('draggable', 'true'); });
})();

// ─── Section config modal ─────────────────────────────────────────────────────
var sectionMeta = <?= json_encode(array_map(fn($m) => ['desc' => $m['desc']], $sectionMeta)) ?>;

function openSectionConfig(key, config) {
    document.getElementById('modal-section-key').value = key;
    document.getElementById('modal-title').textContent = 'Edit: ' + key.replace(/_/g, ' ').replace(/\b\w/g, function(c){ return c.toUpperCase(); });
    document.getElementById('section-modal').style.display = 'flex';

    var body = document.getElementById('modal-body');
    // Generic config fields — extendable per section
    var fields = {
        hero:           [['title','Headline Override','text',''],['subtitle','Subtitle Override','text','']],
        stats:          [['count1_label','Stat 1 Label','text','Tools Available'],['count1_value','Stat 1 Value','text','50+'],['count2_label','Stat 2 Label','text','Happy Users'],['count2_value','Stat 2 Value','text','1000+']],
        featured_tools: [['limit','Number of Tools','number','6']],
        blog:           [['limit','Number of Posts','number','3'],['title','Section Title','text','Latest Articles']],
        testimonials:   [['title','Section Title','text','What People Say'],['limit','Max Testimonials','number','6']],
        cta:            [['title','CTA Title','text','Ready to get started?'],['subtitle','CTA Subtitle','text',''],['btn_text','Button Text','text','Get Started Free'],['btn_url','Button URL','text','/register']],
        why_us:         [['title','Section Title','text','Why Choose Us?'],['point1','Point 1','text','Free forever — no credit card'],['point2','Point 2','text','Privacy-first, no tracking'],['point3','Point 3','text','Open source friendly']],
        faq:            [['title','Section Title','text','Frequently Asked Questions']],
        contact:        [['title','Section Title','text','Get in Touch'],['show_form','Show Contact Form','checkbox','1']],
        custom_block:   [['content','HTML Content','textarea','']],
        search:         [['placeholder','Search Placeholder','text','Search tools…']],
    };
    var sFields = fields[key] || [['title','Section Title','text','']];
    var html = '';
    sFields.forEach(function(f) {
        var fkey = f[0], label = f[1], type = f[2], def = f[3];
        var val = config[fkey] !== undefined ? config[fkey] : def;
        var inputName = 'config_' + fkey;
        if (type === 'textarea') {
            html += '<div class="form-group"><label class="form-label">' + label + '</label>'
                  + '<textarea name="' + inputName + '" class="form-input" rows="5">' + escHtml(val) + '</textarea></div>';
        } else if (type === 'checkbox') {
            html += '<div class="form-group"><label style="display:flex;align-items:center;gap:8px;cursor:pointer">'
                  + '<input type="checkbox" name="' + inputName + '" value="1" ' + (val == '1' ? 'checked' : '') + '>'
                  + '<span class="form-label" style="margin:0">' + label + '</span></label></div>';
        } else {
            html += '<div class="form-group"><label class="form-label">' + label + '</label>'
                  + '<input type="' + type + '" name="' + inputName + '" class="form-input" value="' + escHtml(val) + '"></div>';
        }
    });
    if (!html) html = '<p style="color:var(--color-text-muted);font-size:13px">This section uses global settings. No additional config options are available here.</p>';
    body.innerHTML = html;
}

function closeSectionModal() {
    document.getElementById('section-modal').style.display = 'none';
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Close modal on backdrop click
document.getElementById('section-modal').addEventListener('click', function(e) {
    if (e.target === this) closeSectionModal();
});
</script>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Homepage Sections', $content, ['section' => 'homepage-sections']);
