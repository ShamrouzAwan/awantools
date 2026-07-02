<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

// ─── Stats ────────────────────────────────────────────────────────────────────
$totalViews   = $db->count('analytics_events', "event = 'page_view'");
$todayViews   = $db->count('analytics_events', "event = 'page_view' AND DATE(created_at) = DATE('now')");
$weekViews    = $db->count('analytics_events', "event = 'page_view' AND created_at >= DATE('now', '-7 days')");
$totalUsers   = $db->count('users');
$activePlugins = $db->count('plugins', "status = 'active'");
$totalPages   = $db->count('pages', "status = 'published'");

// ─── Daily chart data (last 30 days) ─────────────────────────────────────────
$dailyRows = $db->fetchAll(
    "SELECT DATE(created_at) as day, COUNT(*) as views
     FROM analytics_events
     WHERE event = 'page_view' AND created_at >= DATE('now', '-29 days')
     GROUP BY day ORDER BY day ASC"
);

// Fill missing days with 0
$dailyMap = [];
foreach ($dailyRows as $r) $dailyMap[$r['day']] = (int)$r['views'];

$chartLabels = [];
$chartData   = [];
for ($d = 29; $d >= 0; $d--) {
    $day = date('Y-m-d', strtotime("-{$d} days"));
    $chartLabels[] = date('M j', strtotime($day));
    $chartData[]   = $dailyMap[$day] ?? 0;
}

// ─── Top pages ────────────────────────────────────────────────────────────────
$topPages = $db->fetchAll(
    "SELECT path, COUNT(*) as views FROM analytics_events
     WHERE event = 'page_view' AND path IS NOT NULL AND path != ''
     GROUP BY path ORDER BY views DESC LIMIT 8"
);

// ─── Event breakdown ──────────────────────────────────────────────────────────
$eventBreakdown = $db->fetchAll(
    "SELECT event, COUNT(*) as cnt FROM analytics_events
     GROUP BY event ORDER BY cnt DESC LIMIT 8"
);

// ─── Recent events ────────────────────────────────────────────────────────────
$recentEvents = $db->fetchAll(
    "SELECT * FROM analytics_events ORDER BY created_at DESC LIMIT 20"
);

// ─── Signups last 30 days ─────────────────────────────────────────────────────
$signupRows = $db->fetchAll(
    "SELECT DATE(created_at) as day, COUNT(*) as cnt
     FROM analytics_events
     WHERE event = 'user_register' AND created_at >= DATE('now', '-29 days')
     GROUP BY day ORDER BY day ASC"
);
$signupMap = [];
foreach ($signupRows as $r) $signupMap[$r['day']] = (int)$r['cnt'];
$signupData = [];
for ($d = 29; $d >= 0; $d--) {
    $day = date('Y-m-d', strtotime("-{$d} days"));
    $signupData[] = $signupMap[$day] ?? 0;
}

// ─── Device breakdown (parsed from user_agent) ────────────────────────────────
$deviceCounts = ['Mobile' => 0, 'Tablet' => 0, 'Desktop' => 0];
try {
    $uaRows = $db->fetchAll(
        "SELECT user_agent, COUNT(*) as cnt FROM analytics_events
         WHERE event = 'page_view' AND user_agent IS NOT NULL AND user_agent != ''
         GROUP BY user_agent"
    );
    foreach ($uaRows as $row) {
        $ua = $row['user_agent'];
        $cnt = (int)$row['cnt'];
        if (preg_match('/iPad|Tablet|Kindle/i', $ua)) {
            $deviceCounts['Tablet'] += $cnt;
        } elseif (preg_match('/Mobile|Android|iPhone|iPod|BlackBerry|Windows Phone|webOS/i', $ua)) {
            $deviceCounts['Mobile'] += $cnt;
        } else {
            $deviceCounts['Desktop'] += $cnt;
        }
    }
} catch (Throwable $e) {}

$deviceLabels = json_encode(array_keys($deviceCounts));
$deviceData   = json_encode(array_values($deviceCounts));

// ─── Browser breakdown ────────────────────────────────────────────────────────
$browserCounts = [];
try {
    $uaRows2 = $db->fetchAll(
        "SELECT user_agent, COUNT(*) as cnt FROM analytics_events
         WHERE event = 'page_view' AND user_agent IS NOT NULL AND user_agent != ''
         GROUP BY user_agent"
    );
    foreach ($uaRows2 as $row) {
        $ua  = $row['user_agent'];
        $cnt = (int)$row['cnt'];
        if (str_contains($ua, 'Firefox'))       $browser = 'Firefox';
        elseif (str_contains($ua, 'Edg'))       $browser = 'Edge';
        elseif (str_contains($ua, 'OPR') || str_contains($ua, 'Opera')) $browser = 'Opera';
        elseif (str_contains($ua, 'Chrome'))    $browser = 'Chrome';
        elseif (str_contains($ua, 'Safari'))    $browser = 'Safari';
        elseif (str_contains($ua, 'MSIE') || str_contains($ua, 'Trident')) $browser = 'IE';
        else                                     $browser = 'Other';
        $browserCounts[$browser] = ($browserCounts[$browser] ?? 0) + $cnt;
    }
    arsort($browserCounts);
} catch (Throwable $e) {}

$browserLabels = json_encode(array_keys($browserCounts));
$browserData   = json_encode(array_values($browserCounts));

$labelsJson   = json_encode($chartLabels);
$viewsJson    = json_encode($chartData);
$signupsJson  = json_encode($signupData);
$pageLabels   = json_encode(array_column($topPages, 'path'));
$pageViews    = json_encode(array_column($topPages, 'views'));
$evtLabels    = json_encode(array_column($eventBreakdown, 'event'));
$evtCounts    = json_encode(array_column($eventBreakdown, 'cnt'));

// ─── View ─────────────────────────────────────────────────────────────────────
ob_start();
?>
<style>
.analytics-stats { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:16px; margin-bottom:24px; }
.a-stat { background:var(--color-card); border:1px solid var(--color-border); border-radius:var(--radius-medium); padding:20px 16px; }
.a-stat-value { font-size:28px; font-weight:800; color:var(--color-text); line-height:1; margin-bottom:4px; }
.a-stat-label { font-size:12px; color:var(--color-text-muted); font-weight:500; }
.a-stat-sub   { font-size:11px; color:var(--color-text-secondary); margin-top:4px; }
.chart-grid   { display:grid; grid-template-columns:2fr 1fr; gap:16px; margin-bottom:16px; }
.chart-card   { background:var(--color-card); border:1px solid var(--color-border); border-radius:var(--radius-medium); padding:20px; }
.chart-title  { font-size:13px; font-weight:700; color:var(--color-text); margin-bottom:16px; }
@media(max-width:768px){ .chart-grid{ grid-template-columns:1fr; } }
</style>

<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Analytics</div>
            <div class="page-subtitle">Platform activity over the last 30 days</div>
        </div>
    </div>
</div>

<div class="page-body">

    <!-- Stat cards -->
    <div class="analytics-stats">
        <div class="a-stat">
            <div class="a-stat-value"><?= number_format($totalViews) ?></div>
            <div class="a-stat-label">Total Page Views</div>
            <div class="a-stat-sub"><?= number_format($weekViews) ?> this week</div>
        </div>
        <div class="a-stat">
            <div class="a-stat-value"><?= number_format($todayViews) ?></div>
            <div class="a-stat-label">Views Today</div>
        </div>
        <div class="a-stat">
            <div class="a-stat-value"><?= number_format($totalUsers) ?></div>
            <div class="a-stat-label">Registered Users</div>
        </div>
        <div class="a-stat">
            <div class="a-stat-value"><?= number_format($totalPages) ?></div>
            <div class="a-stat-label">Published Pages</div>
        </div>
        <div class="a-stat">
            <div class="a-stat-value"><?= number_format($activePlugins) ?></div>
            <div class="a-stat-label">Active Plugins</div>
        </div>
        <div class="a-stat">
            <div class="a-stat-value"><?= number_format(array_sum($signupData)) ?></div>
            <div class="a-stat-label">Signups (30d)</div>
        </div>
    </div>

    <!-- Main line chart -->
    <div class="chart-card" style="margin-bottom:16px">
        <div class="chart-title">Page Views — Last 30 Days</div>
        <canvas id="chart-views" height="80"></canvas>
    </div>

    <!-- Bottom row: top pages + event breakdown -->
    <div class="chart-grid">
        <div class="chart-card">
            <div class="chart-title">Top Pages</div>
            <canvas id="chart-pages" height="160"></canvas>
        </div>
        <div class="chart-card">
            <div class="chart-title">Event Breakdown</div>
            <canvas id="chart-events" height="160"></canvas>
        </div>
    </div>

    <!-- Signups mini chart -->
    <div class="chart-card" style="margin-bottom:16px">
        <div class="chart-title">User Signups — Last 30 Days</div>
        <canvas id="chart-signups" height="60"></canvas>
    </div>

    <!-- Device & Browser breakdown -->
    <div class="chart-grid" style="margin-bottom:16px">
        <div class="chart-card">
            <div class="chart-title">Browser Breakdown</div>
            <canvas id="chart-browsers" height="160"></canvas>
        </div>
        <div class="chart-card">
            <div class="chart-title">Device Types</div>
            <canvas id="chart-devices" height="160"></canvas>
        </div>
    </div>

    <!-- Recent events table -->
    <div class="card">
        <div class="card-header"><span class="card-title">Recent Events</span></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Path</th>
                        <th>Plugin</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentEvents as $ev): ?>
                    <tr>
                        <td><span class="badge badge-<?= match($ev['event']) {
                            'page_view' => 'info',
                            'user_register' => 'success',
                            'user_login' => 'neutral',
                            'plugin_activate' => 'primary',
                            default => 'neutral'
                        } ?>"><?= e($ev['event']) ?></span></td>
                        <td class="text-muted"><?= e($ev['path'] ?? '—') ?></td>
                        <td class="text-muted"><?= e($ev['plugin_slug'] ?? '—') ?></td>
                        <td class="text-muted text-sm"><?= e($ev['created_at']) ?></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const primary  = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#6366f1';
const success  = getComputedStyle(document.documentElement).getPropertyValue('--color-success').trim() || '#10b981';
const border   = getComputedStyle(document.documentElement).getPropertyValue('--color-border').trim() || '#e2e8f0';
const muted    = getComputedStyle(document.documentElement).getPropertyValue('--color-text-muted').trim() || '#94a3b8';

Chart.defaults.font.family = 'inherit';
Chart.defaults.font.size   = 12;
Chart.defaults.color       = muted;

// Page views line chart
new Chart(document.getElementById('chart-views'), {
    type: 'line',
    data: {
        labels: <?= $labelsJson ?>,
        datasets: [{
            label: 'Page Views',
            data: <?= $viewsJson ?>,
            borderColor: primary,
            backgroundColor: primary + '18',
            borderWidth: 2,
            pointRadius: 2,
            fill: true,
            tension: 0.4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: border + '80' }, ticks: { maxTicksLimit: 10 } },
            y: { grid: { color: border + '80' }, beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

// Top pages bar chart
new Chart(document.getElementById('chart-pages'), {
    type: 'bar',
    data: {
        labels: <?= $pageLabels ?>,
        datasets: [{
            label: 'Views',
            data: <?= $pageViews ?>,
            backgroundColor: primary + 'cc',
            borderRadius: 4,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: border + '80' }, beginAtZero: true, ticks: { precision: 0 } },
            y: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

// Event breakdown doughnut
new Chart(document.getElementById('chart-events'), {
    type: 'doughnut',
    data: {
        labels: <?= $evtLabels ?>,
        datasets: [{
            data: <?= $evtCounts ?>,
            backgroundColor: ['#6366f1','#10b981','#f59e0b','#3b82f6','#ef4444','#8b5cf6','#06b6d4','#ec4899'],
            borderWidth: 2,
            borderColor: 'var(--color-card)',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } }
        },
        cutout: '60%',
    }
});

// Signups mini chart
new Chart(document.getElementById('chart-signups'), {
    type: 'bar',
    data: {
        labels: <?= $labelsJson ?>,
        datasets: [{
            label: 'Signups',
            data: <?= $signupsJson ?>,
            backgroundColor: success + 'cc',
            borderRadius: 3,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
            y: { grid: { color: border + '80' }, beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

// Browser breakdown doughnut
new Chart(document.getElementById('chart-browsers'), {
    type: 'doughnut',
    data: {
        labels: <?= $browserLabels ?>,
        datasets: [{
            data: <?= $browserData ?>,
            backgroundColor: ['#6366f1','#10b981','#f59e0b','#3b82f6','#ef4444','#8b5cf6','#06b6d4'],
            borderWidth: 2,
            borderColor: 'var(--color-card)',
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } } },
        cutout: '58%',
    }
});

// Device type doughnut
new Chart(document.getElementById('chart-devices'), {
    type: 'doughnut',
    data: {
        labels: <?= $deviceLabels ?>,
        datasets: [{
            data: <?= $deviceData ?>,
            backgroundColor: ['#6366f1','#10b981','#f59e0b'],
            borderWidth: 2,
            borderColor: 'var(--color-card)',
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } } },
        cutout: '58%',
    }
});
</script>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Analytics', $content, ['section' => 'analytics']);
