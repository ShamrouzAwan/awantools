<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$logger = Logger::getInstance($db);

// ─── POST: manual run ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? '';
    $slug   = Security::sanitize($_POST['slug'] ?? '');

    if ($action === 'run' && $slug) {
        $result = Scheduler::runOne($db, $slug);
        $logger->info("Manual task run: {$slug} — {$result['status']}", [], $auth->id());
        Session::flash($result['status'] === 'ok' ? 'success' : 'danger',
            "Task <strong>{$slug}</strong>: " . e($result['message']));
    }

    if ($action === 'run_all') {
        $results = Scheduler::run($db);
        $ok = count(array_filter($results, fn($r) => $r['status'] === 'ok'));
        $logger->info("Run all tasks: {$ok}/" . count($results) . " succeeded", [], $auth->id());
        Session::flash('success', "Ran " . count($results) . " task(s): {$ok} succeeded.");
    }

    redirect('/admin/scheduler');
}

// ─── Load tasks from DB ───────────────────────────────────────────────────────
Scheduler::sync($db);
$registeredSlugs = array_keys(Scheduler::all());
$dbTasks         = [];
if (!empty($registeredSlugs)) {
    $placeholders = implode(',', array_fill(0, count($registeredSlugs), '?'));
    $dbTasks = $db->fetchAll("SELECT * FROM scheduled_tasks WHERE slug IN ({$placeholders}) ORDER BY name ASC", $registeredSlugs);
}
// Also show any DB tasks not currently registered (orphaned)
$allDbTasks = $db->fetchAll("SELECT * FROM scheduled_tasks ORDER BY name ASC");

function formatInterval(int $s): string {
    if ($s < 60)       return "{$s}s";
    if ($s < 3600)     return round($s / 60) . 'm';
    if ($s < 86400)    return round($s / 3600) . 'h';
    return round($s / 86400) . 'd';
}

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Task Scheduler</div>
            <div class="page-subtitle">Manage background tasks and cron jobs</div>
        </div>
    </div>
    <div class="topbar-actions">
        <?php if (!empty($allDbTasks)): ?>
        <form method="POST" style="display:inline">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="run_all">
            <button type="submit" class="btn btn-primary btn-sm"
                    onclick="return confirm('Run all due tasks now?')">▶ Run All Due</button>
        </form>
        <?php endif ?>
    </div>
</div>

<div class="page-body">

    <!-- Cron setup info -->
    <div class="alert alert-info" style="margin-bottom:20px">
        <strong>Cron Setup:</strong> To run tasks automatically, add this to your system crontab:
        <code style="display:block;margin-top:6px;padding:8px 12px;background:rgba(0,0,0,.08);border-radius:4px;font-size:12px">
            * * * * * curl -s "<?= e($settings->get('site_url', 'http://localhost:8080')) ?>/cron?secret=<?= e($settings->get('cron_secret', '[set cron_secret in settings]')) ?>" &gt; /dev/null 2&gt;&amp;1
        </code>
    </div>

    <?php if (empty($allDbTasks)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">⏰</div>
            <h3>No tasks registered</h3>
            <p>Plugins can register tasks using <code>Scheduler::register()</code> in their <code>on_activate.php</code>.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Interval</th>
                        <th>Status</th>
                        <th>Last Run</th>
                        <th>Next Run</th>
                        <th>Runs</th>
                        <th>Last Result</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allDbTasks as $task):
                        $registered = isset(Scheduler::all()[$task['slug']]);
                        $isDue = $task['next_run'] && strtotime($task['next_run']) <= time();
                        $statusBadge = match($task['status']) {
                            'running' => 'warning',
                            'error'   => 'danger',
                            'idle'    => 'neutral',
                            default   => 'neutral',
                        };
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:13px"><?= e($task['name']) ?></div>
                            <div style="font-size:11px;color:var(--color-text-muted);font-family:monospace"><?= e($task['slug']) ?></div>
                            <?php if ($task['description']): ?>
                            <div style="font-size:11px;color:var(--color-text-secondary)"><?= e($task['description']) ?></div>
                            <?php endif ?>
                        </td>
                        <td><span class="badge badge-neutral"><?= formatInterval((int)$task['interval_seconds']) ?></span></td>
                        <td>
                            <span class="badge badge-<?= $statusBadge ?>">
                                <?= e($task['status']) ?>
                            </span>
                            <?php if (!$registered): ?>
                            <span class="badge badge-warning" title="Plugin not active — task not callable">orphan</span>
                            <?php endif ?>
                        </td>
                        <td class="text-muted text-sm"><?= $task['last_run'] ? fdate($task['last_run'], 'M j, g:i A') : '—' ?></td>
                        <td class="text-sm">
                            <?php if ($task['next_run']): ?>
                            <span style="color:<?= $isDue ? 'var(--color-danger)' : 'inherit' ?>" title="<?= e($task['next_run']) ?>">
                                <?= $isDue ? '⏰ Due now' : fdate($task['next_run'], 'M j, g:i A') ?>
                            </span>
                            <?php else: ?>—<?php endif ?>
                        </td>
                        <td class="text-muted"><?= number_format((int)$task['run_count']) ?></td>
                        <td class="text-sm" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                            title="<?= e($task['last_result'] ?? '') ?>">
                            <span style="color:<?= ($task['last_result'] ?? '') === 'OK' || str_starts_with($task['last_result'] ?? '', 'OK') ? 'var(--color-success)' : 'var(--color-danger)' ?>">
                                <?= e(substr($task['last_result'] ?? '—', 0, 30)) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($registered): ?>
                            <form method="POST" style="display:inline">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="run">
                                <input type="hidden" name="slug" value="<?= e($task['slug']) ?>">
                                <button type="submit" class="btn btn-ghost btn-sm">▶ Run</button>
                            </form>
                            <?php endif ?>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif ?>

    <!-- Built-in platform tasks -->
    <div class="card" style="margin-top:16px">
        <div class="card-header"><span class="card-title">Register Your Own Task</span></div>
        <div class="card-body">
            <p class="text-muted text-sm" style="margin-bottom:12px">Add this to your plugin's <code>on_activate.php</code>:</p>
            <pre style="background:var(--color-background);border:1px solid var(--color-border);border-radius:var(--radius-medium);padding:16px;font-size:12px;overflow-x:auto;margin:0">Scheduler::register(
    'my_plugin_cleanup',           // unique slug
    'My Plugin Cleanup',           // display name
    'Removes stale data weekly',   // description
    604800,                        // interval in seconds (1 week)
    function() use ($db) {
        $db->delete('plg_myplugin_cache', 'created_at &lt; ?', [date('Y-m-d', strtotime('-7 days'))]);
    }
);</pre>
        </div>
    </div>

</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Scheduler', $content, ['section' => 'scheduler']);
