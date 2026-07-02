<?php
defined('AWAN') or die('Direct access denied.');

class Theme {
    private static ?Theme $instance = null;
    private Database $db;
    private Settings $settings;
    private array $themeData = [];
    private string $activeTheme;

    private function __construct(Database $db, Settings $settings) {
        $this->db          = $db;
        $this->settings    = $settings;
        $this->activeTheme = $settings->theme();
        $this->load();
    }

    public static function getInstance(Database $db, Settings $settings): Theme {
        if (self::$instance === null) {
            self::$instance = new self($db, $settings);
        }
        return self::$instance;
    }

    private function load(): void {
        $themePath = THEMES_PATH . '/' . $this->activeTheme . '/theme.json';
        if (!file_exists($themePath)) {
            $this->activeTheme = DEFAULT_THEME;
            $themePath = THEMES_PATH . '/default/theme.json';
        }
        if (file_exists($themePath)) {
            $this->themeData = json_decode(file_get_contents($themePath), true) ?? [];
        }
    }

    public function name(): string {
        return $this->themeData['name'] ?? ucfirst($this->activeTheme);
    }

    public function slug(): string {
        return $this->activeTheme;
    }

    // Generate CSS custom properties from theme defaults + DB overrides.
    // Outputs :root { } for light mode AND [data-theme="dark"] { } for dark mode.
    public function cssVariables(): string {
        $defaults  = $this->themeData['variables'] ?? [];
        $darkDefs  = $this->themeData['dark']      ?? [];
        $overrides = [];

        try {
            $rows = $this->db->fetchAll(
                "SELECT variable_key, variable_value FROM theme_overrides WHERE theme_slug = ?",
                [$this->activeTheme]
            );
            foreach ($rows as $row) {
                $overrides[$row['variable_key']] = $row['variable_value'];
            }
        } catch (Exception $e) {}

        // Light mode (applied as :root + DB overrides)
        $vars = array_merge($defaults, $overrides);
        $css  = ":root {\n";
        foreach ($vars as $key => $value) {
            $css .= "  --{$key}: {$value};\n";
        }
        $css .= "}\n";

        // Dark mode (theme dark definitions, DB overrides with dark- prefix take priority)
        if (!empty($darkDefs)) {
            $darkOverrides = [];
            foreach ($overrides as $key => $value) {
                // Allow per-key dark overrides stored as "dark-color-background" etc.
                if (str_starts_with($key, 'dark-')) {
                    $darkOverrides[substr($key, 5)] = $value;
                }
            }
            $darkVars = array_merge($darkDefs, $darkOverrides);
            $css .= "[data-theme=\"dark\"] {\n";
            foreach ($darkVars as $key => $value) {
                $css .= "  --{$key}: {$value};\n";
            }
            $css .= "}\n";
        }

        return $css;
    }

    public function hasDarkMode(): bool {
        return !empty($this->themeData['dark']);
    }

    public function styleUrl(): string {
        return "/themes/{$this->activeTheme}/style.css";
    }

    // List all installed themes
    public static function listThemes(): array {
        $themes = [];
        $dirs   = glob(THEMES_PATH . '/*/theme.json') ?: [];
        foreach ($dirs as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data) {
                $data['slug'] = basename(dirname($file));
                $themes[]     = $data;
            }
        }
        return $themes;
    }

    // Activate a theme
    public static function activate(string $slug, Settings $settings): bool {
        $themePath = THEMES_PATH . '/' . $slug . '/theme.json';
        if (!file_exists($themePath)) return false;
        $settings->set('active_theme', $slug, 'theme');
        return true;
    }

    // Save theme override
    public function setVariable(string $key, string $value): void {
        $exists = $this->db->fetch(
            "SELECT id FROM theme_overrides WHERE theme_slug = ? AND variable_key = ?",
            [$this->activeTheme, $key]
        );
        if ($exists) {
            $this->db->update('theme_overrides', ['variable_value' => $value], 'theme_slug = ? AND variable_key = ?', [$this->activeTheme, $key]);
        } else {
            $this->db->insert('theme_overrides', ['theme_slug' => $this->activeTheme, 'variable_key' => $key, 'variable_value' => $value]);
        }
    }

    public function template(string $name): string {
        $path = THEMES_PATH . '/' . $this->activeTheme . '/templates/' . $name . '.php';
        if (!file_exists($path)) {
            $path = THEMES_PATH . '/default/templates/' . $name . '.php';
        }
        return $path;
    }
}
