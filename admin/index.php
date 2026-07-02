<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$logger = Logger::getInstance($db);

// ── First-time setup redirect for super admins ────────────────────────────────
if ($auth->isSuperAdmin() && $settings->get('setup_wizard_dismissed', '0') !== '1') {
    $siteNameDefault = $settings->get('site_name', '') === '' || $settings->get('site_name', '') === 'AWAN Platform';
    $logoEmpty       = $settings->get('logo_url', '') === '';
    $fromEmailEmpty  = empty($settings->get('mail_from_email', '') ?: $settings->get('smtp_from_email', ''));
    if ($siteNameDefault && $logoEmpty && $fromEmailEmpty) {
        redirect('/admin/setup');
    }
}

// ── Setup progress (compact banner) ──────────────────────────────────────────
$showSetupBanner = false;
$setupDone       = 0;
$setupTotal      = 0;
if ($auth->isSuperAdmin() && $settings->get('setup_wizard_dismissed', '0') !== '1') {
    $navHeader = 0;
    try { $navHeader = $db->count('nav_items', "location = 'header' AND is_active = 1"); } catch (Throwable $e) {}
    $blogPosts = 0;
    try { $blogPosts = $db->count('blog_posts', "status = 'published'"); } catch (Throwable $e) {}

    $fromEmailSet = !empty($settings->get('mail_from_email', '') ?: $settings->get('smtp_from_email', ''));

    $quickChecks = [
        $settings->get('site_name', '') !== '' && $settings->get('site_name', '') !== 'AWAN Platform',
        $settings->get('site_url', '') !== '',
        $settings->get('logo_url', '') !== '',
        $settings->get('favicon_url', '') !== '',
        $fromEmailSet,
        $settings->get('seo_meta_description', '') !== '',
        $navHeader > 0,
        $blogPosts > 0,
        $settings->get('developer_name', '') !== '',
    ];
    $setupTotal = count($quickChecks);
    $setupDone  = count(array_filter($quickChecks));
    $showSetupBanner = $setupDone < $setupTotal;
}

$totalUsers    = $db->count('users');
$activeUsers   = $db->count('users', "status = 'active'");
$totalPlugins  = $db->count('plugins');
$activePlugins = $db->count('plugins', "status = 'active'");
$totalOffered  = 0;
try { $totalOffered = (int)($db->fetch("SELECT COALESCE(SUM(offered),0) AS n FROM plugins WHERE status = 'active'")['n'] ?? 0); } catch (Throwable $e) {}
$recentLogs    = $logger->getLogs(8);

// Recent users
$recentUsers = $db->fetchAll("SELECT id, username, name, email, created_at, status FROM users ORDER BY id DESC LIMIT 6");

// Log counts by level
$errorCount = $db->count('logs', "level = 'error'");
$warnCount  = $db->count('logs', "level = 'warn'");

// Email queue stats
$queuePending = 0;
$queueFailed  = 0;
$queueSentDay = 0;
try {
    $queuePending = $db->count('email_queue', "status = 'pending'");
    $queueFailed  = $db->count('email_queue', "status = 'failed'");
    $queueSentDay = $db->count('email_queue', "status = 'sent' AND sent_at >= date('now', '-1 day')");
} catch (Throwable $e) {}

// Scheduler stats
$tasksDue   = 0;
$tasksTotal = 0;
$lastTaskRun = null;
try {
    $tasksTotal  = $db->count('scheduled_tasks');
    $tasksDue    = $db->count('scheduled_tasks', "next_run IS NOT NULL AND next_run <= ?", [date('Y-m-d H:i:s')]);
    $lastTaskRow = $db->fetch("SELECT last_run FROM scheduled_tasks WHERE last_run IS NOT NULL ORDER BY last_run DESC LIMIT 1");
    $lastTaskRun = $lastTaskRow['last_run'] ?? null;
} catch (Throwable $e) {}

// Storage info
$storageTotal = @disk_total_space(STORAGE_PATH ?: '/') ?: 0;
$storageFree  = @disk_free_space(STORAGE_PATH ?: '/') ?: 0;
$storageUsedPct = $storageTotal > 0 ? round((($storageTotal - $storageFree) / $storageTotal) * 100) : 0;

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Dashboard</div>
            <div class="page-subtitle"><?= e(date('l, F j, Y')) ?> — Welcome back, <?= e($auth->user()['name'] ?: $auth->user()['username']) ?></div>
        </div>
    </div>
    <div class="topbar-actions">
        <?php if ($auth->isSuperAdmin()): ?>
        <a href="/admin/setup" class="btn btn-ghost btn-sm">Setup Checklist</a>
        <?php endif ?>
        <a href="/admin/settings" class="btn btn-secondary btn-sm">Settings</a>
    </div>
</div>

<div class="page-body">

    <?php if ($showSetupBanner): ?>
    <!-- Setup progress banner -->
    <div style="background:var(--color-primary-light,#ede9fe);border:1px solid var(--color-primary);border-radius:var(--radius-medium);padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <div style="flex:1;min-width:180px">
            <div style="font-size:13px;font-weight:700;color:var(--color-primary);margin-bottom:4px">Platform setup incomplete</div>
            <div style="height:6px;background:rgba(99,102,241,.2);border-radius:99px;overflow:hidden;max-width:300px">
                <div style="height:100%;width:<?= round($setupDone / $setupTotal * 100) ?>%;background:var(--color-primary);border-radius:99px"></div>
            </div>
            <div style="font-size:12px;color:var(--color-primary);margin-top:4px"><?= $setupDone ?>/<?= $setupTotal ?> steps done</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <a href="/admin/setup" class="btn btn-primary btn-sm">Continue setup</a>
            <form method="POST" action="/admin/setup" style="margin:0">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="dismiss">
                <button type="submit" class="btn btn-ghost btn-sm" style="font-size:11px">Dismiss</button>
            </form>
        </div>
    </div>
    <?php endif ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?= number_format($totalUsers) ?></div>
            <div class="stat-change"><?= $activeUsers ?> active</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active Plugins</div>
            <div class="stat-value"><?= $activePlugins ?></div>
            <div class="stat-change"><?= $totalOffered ?> tools offered &middot; <?= $totalPlugins ?> installed</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Errors (All Time)</div>
            <div class="stat-value<?= $errorCount > 0 ? ' text-danger' : '' ?>"><?= number_format($errorCount) ?></div>
            <div class="stat-change"><?= $warnCount ?> warnings</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Platform Version</div>
            <div class="stat-value" style="font-size:18px;letter-spacing:-0.5px"><?= AWAN_VERSION ?></div>
            <div class="stat-change">PHP <?= phpversion() ?></div>
        </div>
    </div>

    <div class="grid-2" style="gap:20px">

        <!-- Recent Users -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Recent Users</span>
                <a href="/admin/users" class="btn btn-ghost btn-sm">View All</a>
            </div>
            <div class="table-wrap">
                <?php if (empty($recentUsers)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"></div>
                    <h3>No users yet</h3>
                    <p>Users will appear here after registration.</p>
                </div>
                <?php else: ?>
                <table class="table">
                    <thead><tr><th>User</th><th>Joined</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td>
                            <div style="font-weight:500"><?= e($u['name'] ?: $u['username']) ?></div>
                            <div class="text-muted text-sm"><?= e($u['email']) ?></div>
                        </td>
                        <td class="text-muted"><?= fdate($u['created_at'], 'M j') ?></td>
                        <td>
                            <span class="badge <?= $u['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
                                <?= e($u['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
                <?php endif ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Recent Activity</span>
                <a href="/admin/logs" class="btn btn-ghost btn-sm">View Logs</a>
            </div>
            <div class="table-wrap">
                <?php if (empty($recentLogs)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"></div>
                    <h3>No activity yet</h3>
                    <p>System events will appear here.</p>
                </div>
                <?php else: ?>
                <table class="table">
                    <thead><tr><th>Event</th><th>Level</th><th>Time</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                    <tr>
                        <td>
                            <div class="truncate" style="max-width:200px" title="<?= e($log['message']) ?>">
                                <?= e($log['message']) ?>
                            </div>
                            <?php if ($log['url']): ?>
                            <div class="text-muted text-sm truncate" style="max-width:200px"><?= e($log['url']) ?></div>
                            <?php endif ?>
                        </td>
                        <td>
                            <?php
                            $levelClass = match($log['level']) {
                                'error' => 'badge-danger',
                                'warn'  => 'badge-warning',
                                'auth'  => 'badge-info',
                                'info'  => 'badge-neutral',
                                default => 'badge-neutral',
                            };
                            ?>
                            <span class="badge <?= $levelClass ?>"><?= e($log['level']) ?></span>
                        </td>
                        <td class="text-muted" style="white-space:nowrap">
                            <?= fdate($log['created_at'], 'M j, g:i a') ?>
                        </td>
                    </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
                <?php endif ?>
            </div>
        </div>
    </div>

    <!-- System Health + Email + Scheduler row -->
    <div class="grid-2" style="gap:20px;margin-top:20px">

        <!-- System Health -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">System Health</span>
                <a href="/admin/system" class="btn btn-ghost btn-sm">Details</a>
            </div>
            <div class="card-body" style="padding-top:8px">
                <?php
                $phpOk     = version_compare(phpversion(), '8.0', '>=');
                $dbOk      = true;
                try { $db->fetch("SELECT 1"); } catch (Throwable $e) { $dbOk = false; }
                $mailFrom  = $settings->get('mail_from_email', '') ?: $settings->get('smtp_from_email', '');
                $mailOk    = !empty($mailFrom);
                $storageOk = $storageFree > (50 * 1024 * 1024); // >50 MB free
                $dbDriver  = strtoupper(DB_DRIVER ?? 'sqlite');

                $items = [
                    ['PHP ' . phpversion(),                     $phpOk,    $phpOk    ? 'OK' : 'PHP 8.0+ required'],
                    [$dbDriver . ' Database',                    $dbOk,    $dbOk     ? 'Connected' : 'Connection failed'],
                    ['Email From Address',                       $mailOk,  $mailOk   ? $mailFrom : 'Not configured — set in Email settings'],
                    ['Storage (' . $storageUsedPct . '% used)', $storageOk, round($storageFree / 1048576) . ' MB free'],
                ];
                ?>
                <div style="display:flex;flex-direction:column;gap:10px">
                <?php foreach ($items as [$label, $ok, $note]): ?>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="width:8px;height:8px;border-radius:50%;background:<?= $ok ? 'var(--color-success,#22c55e)' : 'var(--color-danger,#ef4444)' ?>;flex-shrink:0"></div>
                        <div style="flex:1">
                            <div style="font-size:13px;font-weight:500"><?= e($label) ?></div>
                            <div style="font-size:12px;color:var(--color-text-muted)"><?= e($note) ?></div>
                        </div>
                    </div>
                <?php endforeach ?>
                </div>
            </div>
        </div>

        <!-- Email Queue + Scheduler -->
        <div style="display:flex;flex-direction:column;gap:20px">
            <!-- Email Queue -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Email Queue</span>
                    <a href="/admin/email-queue" class="btn btn-ghost btn-sm">View Queue</a>
                </div>
                <div class="card-body" style="padding-top:8px">
                    <?php $queueOn = $settings->get('email_queue_enabled', '0') === '1'; ?>
                    <?php if (!$queueOn): ?>
                    <div style="font-size:13px;color:var(--color-text-muted)">
                        Async queue is disabled. Emails send inline.
                        <a href="/admin/settings?tab=email" style="font-size:12px">Enable &rarr;</a>
                    </div>
                    <?php else: ?>
                    <div style="display:flex;gap:20px">
                        <div>
                            <div style="font-size:22px;font-weight:700;color:var(--color-warning,#f59e0b)"><?= $queuePending ?></div>
                            <div style="font-size:12px;color:var(--color-text-muted)">Pending</div>
                        </div>
                        <div>
                            <div style="font-size:22px;font-weight:700;color:var(--color-danger,#ef4444)"><?= $queueFailed ?></div>
                            <div style="font-size:12px;color:var(--color-text-muted)">Failed</div>
                        </div>
                        <div>
                            <div style="font-size:22px;font-weight:700;color:var(--color-success,#22c55e)"><?= $queueSentDay ?></div>
                            <div style="font-size:12px;color:var(--color-text-muted)">Sent (24h)</div>
                        </div>
                    </div>
                    <?php if ($queueFailed > 0): ?>
                    <div style="margin-top:8px">
                        <a href="/admin/email-queue?status=failed" class="btn btn-danger btn-sm">Review Failed</a>
                    </div>
                    <?php endif ?>
                    <?php endif ?>
                </div>
            </div>

            <!-- Scheduler -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Scheduler</span>
                    <a href="/admin/scheduler" class="btn btn-ghost btn-sm">View Tasks</a>
                </div>
                <div class="card-body" style="padding-top:8px">
                    <div style="display:flex;gap:20px;margin-bottom:10px">
                        <div>
                            <div style="font-size:22px;font-weight:700"><?= $tasksTotal ?></div>
                            <div style="font-size:12px;color:var(--color-text-muted)">Total Tasks</div>
                        </div>
                        <div>
                            <div style="font-size:22px;font-weight:700;color:<?= $tasksDue > 0 ? 'var(--color-warning,#f59e0b)' : 'var(--color-success,#22c55e)' ?>"><?= $tasksDue ?></div>
                            <div style="font-size:12px;color:var(--color-text-muted)">Due Now</div>
                        </div>
                    </div>
                    <div style="font-size:12px;color:var(--color-text-muted)">
                        <?php if ($lastTaskRun): ?>
                        Last run: <?= fdate($lastTaskRun, 'M j, g:i a') ?>
                        <?php else: ?>
                        No tasks have run yet — configure cron or use the run button in the scheduler.
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="card" style="margin-top:20px">
        <div class="card-header">
            <span class="card-title">System Information</span>
        </div>
        <div class="card-body">
            <div class="grid-3" style="gap:24px">
                <div>
                    <div class="text-muted text-sm" style="margin-bottom:4px">Database Driver</div>
                    <div class="font-medium"><?= strtoupper(DB_DRIVER) ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm" style="margin-bottom:4px">Environment</div>
                    <div class="font-medium"><?= e(AWAN_ENV) ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm" style="margin-bottom:4px">Active Theme</div>
                    <div class="font-medium"><?= e($theme->name()) ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm" style="margin-bottom:4px">PHP Version</div>
                    <div class="font-medium"><?= phpversion() ?> <?= $phpOk ? '' : '(upgrade to 8.0+)' ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm" style="margin-bottom:4px">Server Time</div>
                    <div class="font-medium"><?= date('Y-m-d H:i:s') ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm" style="margin-bottom:4px">Storage Free</div>
                    <div class="font-medium"><?= round($storageFree / 1073741824, 2) ?> GB (<?= 100 - $storageUsedPct ?>% free)</div>
                </div>
                <div>
                    <div class="text-muted text-sm" style="margin-bottom:4px">Schema Version</div>
                    <div class="font-medium"><?= e($settings->get('schema_version', '—')) ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm" style="margin-bottom:4px">Email Transport</div>
                    <div class="font-medium">PHP mail()</div>
                </div>
                <div>
                    <div class="text-muted text-sm" style="margin-bottom:4px">Site URL</div>
                    <div class="font-medium"><?= e($settings->get('site_url', 'Not set') ?: 'Not set') ?></div>
                </div>
            </div>
        </div>
    </div>

</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Dashboard', $content, ['section' => 'dashboard']);
