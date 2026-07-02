<?php
defined('AWAN') or die('Direct access denied.');

// ─── Local Overrides ──────────────────────────────────────────────────────────
// Create config.local.php (git-ignored) to override settings for local dev or staging.
// Example: define('AWAN_ENV_LOCAL', 'development'); define('APP_KEY_LOCAL', 'my-key');
$_localCfg = __DIR__ . '/config.local.php';
if (file_exists($_localCfg)) { require $_localCfg; }
unset($_localCfg);

// ─── Environment ──────────────────────────────────────────────────────────────
define('AWAN_VERSION', '1.0.0');
define('AWAN_ENV',   getenv('AWAN_ENV')   ?: (defined('AWAN_ENV_LOCAL')   ? AWAN_ENV_LOCAL   : 'production'));
define('AWAN_DEBUG', AWAN_ENV === 'development');

// __DIR__ is always the root of the AWAN platform (e.g. public_html/ on Hostinger)
define('AWAN_ROOT', __DIR__);

// ─── Database ─────────────────────────────────────────────────────────────────
define('DB_DRIVER', getenv('DB_DRIVER') ?: (defined('DB_DRIVER_LOCAL') ? DB_DRIVER_LOCAL : 'sqlite'));
define('DB_HOST',   getenv('DB_HOST')   ?: (defined('DB_HOST_LOCAL')   ? DB_HOST_LOCAL   : '127.0.0.1'));
define('DB_PORT',   getenv('DB_PORT')   ?: (defined('DB_PORT_LOCAL')   ? DB_PORT_LOCAL   : '3306'));
define('DB_NAME',   getenv('DB_NAME')   ?: (defined('DB_NAME_LOCAL')   ? DB_NAME_LOCAL   : 'awan'));
define('DB_USER',   getenv('DB_USER')   ?: (defined('DB_USER_LOCAL')   ? DB_USER_LOCAL   : 'root'));
define('DB_PASS',   getenv('DB_PASS')   ?: (defined('DB_PASS_LOCAL')   ? DB_PASS_LOCAL   : ''));
define('DB_SQLITE', AWAN_ROOT . '/storage/database.sqlite');

// ─── Security ─────────────────────────────────────────────────────────────────
// IMPORTANT: Set APP_KEY_LOCAL in config.local.php or APP_KEY env var before deploying.
// The default key below is only safe for initial setup — replace it immediately.
define('APP_KEY', getenv('APP_KEY') ?: (defined('APP_KEY_LOCAL') ? APP_KEY_LOCAL : '1e1ebdf864566fa7f35503b0af0c4ea411debfd8d502d3560c3635740009f70d'));
define('SESSION_LIFETIME', 7200); // 2 hours
define('CSRF_TOKEN_LENGTH', 40);
define('RATE_LIMIT_LOGIN', 5);    // max attempts per window
define('RATE_LIMIT_WINDOW', 900); // 15 minutes

// ─── Paths ────────────────────────────────────────────────────────────────────
define('STORAGE_PATH', AWAN_ROOT . '/storage');
define('LOGS_PATH',    AWAN_ROOT . '/storage/logs');
define('UPLOADS_PATH', AWAN_ROOT . '/storage/uploads');
define('PLUGINS_PATH', AWAN_ROOT . '/plugins');
define('THEMES_PATH',  AWAN_ROOT . '/themes');

// ─── App Defaults ─────────────────────────────────────────────────────────────
define('DEFAULT_THEME', 'default');
define('DEFAULT_ROLE',  'user');
define('ADMIN_ROLE',    'admin');
define('SUPER_ROLE',    'super_admin');
