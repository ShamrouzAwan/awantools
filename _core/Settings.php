<?php
defined('AWAN') or die('Direct access denied.');

class Settings {
    private static ?Settings $instance = null;
    private Database $db;
    private array $cache = [];
    private bool $loaded = false;

    private function __construct(Database $db) {
        $this->db = $db;
    }

    public static function getInstance(Database $db): Settings {
        if (self::$instance === null) {
            self::$instance = new self($db);
        }
        return self::$instance;
    }

    private function loadAll(): void {
        if ($this->loaded) return;
        try {
            $rows = $this->db->fetchAll("SELECT `key`, `value` FROM settings");
            foreach ($rows as $row) {
                $this->cache[$row['key']] = $row['value'];
            }
        } catch (Exception $e) {
            // table may not exist yet on first run
        }
        $this->loaded = true;
    }

    public function get(string $key, mixed $default = null): mixed {
        $this->loadAll();
        return $this->cache[$key] ?? $default;
    }

    public function set(string $key, mixed $value, string $group = 'general'): void {
        $this->loadAll();
        $strValue = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;

        if (isset($this->cache[$key])) {
            $this->db->update('settings', ['value' => $strValue, 'group' => $group], '`key` = ?', [$key]);
        } else {
            $this->db->insert('settings', ['key' => $key, 'value' => $strValue, 'group' => $group]);
        }
        $this->cache[$key] = $strValue;
    }

    public function getGroup(string $group): array {
        $this->loadAll();
        try {
            $rows = $this->db->fetchAll("SELECT `key`, `value` FROM settings WHERE `group` = ?", [$group]);
            $result = [];
            foreach ($rows as $row) {
                $result[$row['key']] = $row['value'];
            }
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }

    public function all(): array {
        $this->loadAll();
        return $this->cache;
    }

    public function flush(): void {
        $this->cache = [];
        $this->loaded = false;
    }

    // Shorthand helpers
    public function siteName(): string    { return $this->get('site_name', 'AWAN Platform'); }
    public function siteTagline(): string { return $this->get('site_tagline', 'One Platform. Unlimited Plugins.'); }
    public function theme(): string       { return $this->get('active_theme', DEFAULT_THEME); }
    public function isMaintenanceMode(): bool { return $this->get('maintenance_mode', '0') === '1'; }
    public function registrationEnabled(): bool { return $this->get('registration_enabled', '1') === '1'; }
}
