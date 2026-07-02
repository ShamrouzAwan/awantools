<?php
defined('AWAN') or die('Direct access denied.');

class Plugin {

    public static function isActive(Database $db, string $slug): bool {
        $row = $db->fetch("SELECT status FROM plugins WHERE slug = ?", [$slug]);
        return $row && $row['status'] === 'active';
    }

    public static function getManifest(string $slug): array {
        $file = PLUGINS_PATH . '/' . $slug . '/plugin.json';
        if (!file_exists($file)) return [];
        return json_decode(file_get_contents($file), true) ?? [];
    }

    public static function getRecord(Database $db, string $slug): ?array {
        return $db->fetch("SELECT * FROM plugins WHERE slug = ?", [$slug]);
    }

    public static function listAll(Database $db): array {
        return $db->fetchAll("SELECT * FROM plugins ORDER BY name ASC");
    }

    public static function listActive(Database $db): array {
        return $db->fetchAll("SELECT * FROM plugins WHERE status = 'active' ORDER BY name ASC");
    }

    // Sync a plugin from filesystem into the DB (idempotent)
    public static function sync(Database $db, string $slug): bool {
        $manifest = self::getManifest($slug);
        if (empty($manifest)) return false;

        // If the plugin directory contains icon.svg, embed its content into the manifest
        $iconFile = PLUGINS_PATH . '/' . $slug . '/icon.svg';
        if (file_exists($iconFile)) {
            $manifest['icon'] = trim(file_get_contents($iconFile));
        }

        $offered = max(1, (int)($manifest['offered'] ?? 1));

        if (!$db->exists('plugins', 'slug = ?', [$slug])) {
            $db->insert('plugins', [
                'slug'         => $slug,
                'name'         => $manifest['name'] ?? $slug,
                'version'      => $manifest['version'] ?? '1.0',
                'description'  => $manifest['description'] ?? '',
                'author'       => $manifest['author'] ?? '',
                'status'       => 'inactive',
                'manifest'     => json_encode($manifest),
                'offered'      => $offered,
                'installed_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $db->update('plugins', [
                'name'        => $manifest['name'] ?? $slug,
                'version'     => $manifest['version'] ?? '1.0',
                'description' => $manifest['description'] ?? '',
                'author'      => $manifest['author'] ?? '',
                'manifest'    => json_encode($manifest),
                'offered'     => $offered,
            ], 'slug = ?', [$slug]);
        }
        return true;
    }

    // Run a lifecycle hook file inside the plugin directory
    public static function runHook(string $slug, string $hook, array $context = []): bool {
        $file = PLUGINS_PATH . '/' . $slug . '/' . $hook . '.php';
        if (!file_exists($file)) return false;
        extract($context, EXTR_SKIP);
        try {
            require $file;
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function activate(Database $db, string $slug): array {
        if (!file_exists(PLUGINS_PATH . '/' . $slug . '/plugin.json')) {
            return ['success' => false, 'error' => 'Plugin not found.'];
        }
        self::sync($db, $slug);
        self::runHook($slug, 'on_activate', ['db' => $db]);
        $db->update('plugins', ['status' => 'active'], 'slug = ?', [$slug]);
        return ['success' => true];
    }

    public static function deactivate(Database $db, string $slug): array {
        self::runHook($slug, 'on_deactivate', ['db' => $db]);
        $db->update('plugins', ['status' => 'inactive'], 'slug = ?', [$slug]);
        return ['success' => true];
    }

    public static function uninstall(Database $db, string $slug): array {
        self::runHook($slug, 'on_uninstall', ['db' => $db]);
        $db->delete('plugins', 'slug = ?', [$slug]);
        // Remove plugin files from filesystem so sync() does not re-register it
        $pluginDir = PLUGINS_PATH . '/' . $slug;
        if (is_dir($pluginDir)) {
            self::deleteDirectory($pluginDir);
        }
        return ['success' => true];
    }

    // Recursively delete a directory and all its contents
    private static function deleteDirectory(string $dir): bool {
        if (!is_dir($dir)) return false;
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                self::deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        return @rmdir($dir);
    }

    // Upload and extract a plugin ZIP
    public static function uploadZip(string $zipPath): array {
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'error' => 'ZipArchive extension not available.'];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'error' => 'Could not open ZIP file.'];
        }

        // Find plugin.json inside zip (may be in a subdirectory)
        $manifestPath = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (basename($name) === 'plugin.json' && substr_count($name, '/') <= 1) {
                $manifestPath = $name;
                break;
            }
        }

        if (!$manifestPath) {
            $zip->close();
            return ['success' => false, 'error' => 'plugin.json not found in ZIP.'];
        }

        $manifest = json_decode($zip->getFromName($manifestPath), true);
        if (!$manifest || empty($manifest['name'])) {
            $zip->close();
            return ['success' => false, 'error' => 'Invalid plugin.json manifest.'];
        }

        // Derive slug from directory name or manifest
        $baseDir = dirname($manifestPath);
        $slug = ($baseDir && $baseDir !== '.') ? basename($baseDir) : strtolower(preg_replace('/[^a-z0-9\-_]/i', '-', $manifest['name']));
        $slug = Security::sanitizeSlug($slug);

        $targetDir = PLUGINS_PATH . '/' . $slug;

        // Extract
        if ($baseDir && $baseDir !== '.') {
            // Plugin is inside a subdirectory in the ZIP
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (strpos($name, $baseDir . '/') === 0) {
                    $relative = substr($name, strlen($baseDir) + 1);
                    if ($relative === '') continue;
                    $dest = $targetDir . '/' . $relative;
                    if (str_ends_with($name, '/')) {
                        @mkdir($dest, 0755, true);
                    } else {
                        @mkdir(dirname($dest), 0755, true);
                        file_put_contents($dest, $zip->getFromIndex($i));
                    }
                }
            }
        } else {
            $zip->extractTo($targetDir);
        }

        $zip->close();
        return ['success' => true, 'slug' => $slug, 'name' => $manifest['name']];
    }

    // Upload and extract a theme ZIP
    public static function uploadThemeZip(string $zipPath): array {
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'error' => 'ZipArchive extension not available.'];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'error' => 'Could not open ZIP file.'];
        }

        $manifestPath = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (basename($name) === 'theme.json' && substr_count($name, '/') <= 1) {
                $manifestPath = $name;
                break;
            }
        }

        if (!$manifestPath) {
            $zip->close();
            return ['success' => false, 'error' => 'theme.json not found in ZIP.'];
        }

        $manifest = json_decode($zip->getFromName($manifestPath), true);
        if (!$manifest || empty($manifest['name'])) {
            $zip->close();
            return ['success' => false, 'error' => 'Invalid theme.json manifest.'];
        }

        $baseDir  = dirname($manifestPath);
        $slug     = ($baseDir && $baseDir !== '.') ? basename($baseDir) : Security::sanitizeSlug(strtolower($manifest['name']));
        $targetDir = THEMES_PATH . '/' . $slug;

        if ($baseDir && $baseDir !== '.') {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (strpos($name, $baseDir . '/') === 0) {
                    $relative = substr($name, strlen($baseDir) + 1);
                    if ($relative === '') continue;
                    $dest = $targetDir . '/' . $relative;
                    if (str_ends_with($name, '/')) {
                        @mkdir($dest, 0755, true);
                    } else {
                        @mkdir(dirname($dest), 0755, true);
                        file_put_contents($dest, $zip->getFromIndex($i));
                    }
                }
            }
        } else {
            $zip->extractTo($targetDir);
        }

        $zip->close();
        return ['success' => true, 'slug' => $slug, 'name' => $manifest['name']];
    }
}
