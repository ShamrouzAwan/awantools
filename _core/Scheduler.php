<?php
defined('AWAN') or die('Direct access denied.');

/**
 * Scheduler — Lightweight cron-like task runner for AWAN Platform.
 * Plugins register tasks; the runner checks which are due and executes them.
 */
class Scheduler {
    /** @var array<string, array{slug:string,name:string,description:string,interval:int,callback:callable}> */
    private static array $registry = [];

    /**
     * Register a task.
     * @param string   $slug     Unique identifier (e.g. 'cleanup_logs')
     * @param string   $name     Human-readable name
     * @param string   $desc     Description of what the task does
     * @param int      $interval Interval in seconds (e.g. 3600 = hourly)
     * @param callable $callback The function to execute
     */
    public static function register(string $slug, string $name, string $desc, int $interval, callable $callback): void {
        $slug = preg_replace('/[^a-z0-9_\-]/i', '_', $slug);
        self::$registry[$slug] = compact('slug', 'name', 'desc', 'interval', 'callback');
    }

    /** Get all registered tasks. */
    public static function all(): array { return self::$registry; }

    /** Sync registered tasks into the DB (creates rows for new tasks). */
    public static function sync(Database $db): void {
        foreach (self::$registry as $slug => $task) {
            if (!$db->exists('scheduled_tasks', 'slug = ?', [$slug])) {
                $db->insert('scheduled_tasks', [
                    'slug'             => $slug,
                    'name'             => $task['name'],
                    'description'      => $task['desc'],
                    'interval_seconds' => $task['interval'],
                    'status'           => 'idle',
                    'run_count'        => 0,
                    'next_run'         => date('Y-m-d H:i:s'),
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /**
     * Run all tasks that are due.
     * Returns an array of results keyed by task slug.
     */
    public static function run(Database $db): array {
        self::sync($db);
        $results = [];
        $now     = time();

        foreach (self::$registry as $slug => $task) {
            $row = $db->fetch('SELECT * FROM scheduled_tasks WHERE slug = ?', [$slug]);
            if (!$row) continue;

            $nextRun = $row['next_run'] ? strtotime($row['next_run']) : 0;
            if ($nextRun > $now) continue; // not due yet
            if ($row['status'] === 'running') continue; // already running

            // Mark as running
            $db->update('scheduled_tasks', ['status' => 'running'], 'slug = ?', [$slug]);

            try {
                ($task['callback'])();
                $nextRunTime = date('Y-m-d H:i:s', $now + $task['interval']);
                $db->update('scheduled_tasks', [
                    'status'    => 'idle',
                    'last_run'  => date('Y-m-d H:i:s'),
                    'last_result' => 'OK',
                    'run_count' => (int)$row['run_count'] + 1,
                    'next_run'  => $nextRunTime,
                ], 'slug = ?', [$slug]);
                $results[$slug] = ['status' => 'ok', 'message' => 'Completed'];
            } catch (Throwable $e) {
                $db->update('scheduled_tasks', [
                    'status'      => 'error',
                    'last_run'    => date('Y-m-d H:i:s'),
                    'last_result' => substr($e->getMessage(), 0, 255),
                ], 'slug = ?', [$slug]);
                $results[$slug] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }
        return $results;
    }

    /**
     * Run a single task immediately (bypasses due-time check).
     */
    public static function runOne(Database $db, string $slug): array {
        $task = self::$registry[$slug] ?? null;
        if (!$task) return ['status' => 'error', 'message' => 'Task not registered'];

        $row = $db->fetch('SELECT * FROM scheduled_tasks WHERE slug = ?', [$slug]);
        $db->update('scheduled_tasks', ['status' => 'running'], 'slug = ?', [$slug]);
        try {
            ($task['callback'])();
            $nextRunTime = date('Y-m-d H:i:s', time() + $task['interval']);
            $db->update('scheduled_tasks', [
                'status'      => 'idle',
                'last_run'    => date('Y-m-d H:i:s'),
                'last_result' => 'OK (manual run)',
                'run_count'   => (int)($row['run_count'] ?? 0) + 1,
                'next_run'    => $nextRunTime,
            ], 'slug = ?', [$slug]);
            return ['status' => 'ok', 'message' => 'Task completed successfully'];
        } catch (Throwable $e) {
            $db->update('scheduled_tasks', [
                'status'      => 'error',
                'last_run'    => date('Y-m-d H:i:s'),
                'last_result' => substr($e->getMessage(), 0, 255),
            ], 'slug = ?', [$slug]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
