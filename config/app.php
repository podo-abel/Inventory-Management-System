<?php
/**
 * config/app.php
 * Logitrack IMS — Application Configuration
 *
 * Application-wide constants and settings.
 * Include before any other application file.
 */

// ── Environment ────────────────────────────────────────────────────────────
define('APP_NAME',    'Logitrack IMS');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     'development');

// ── Timezone ───────────────────────────────────────────────────────────────
date_default_timezone_set('America/New_York');

// ── Session ────────────────────────────────────────────────────────────────
define('SESSION_NAME',     'logitrack_session');
define('SESSION_LIFETIME', 7200);

// ── Paths ──────────────────────────────────────────────────────────────────
define('ROOT_PATH',     dirname(__DIR__));
define('CONFIG_PATH',   ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ASSETS_PATH',   ROOT_PATH . '/assets');

// ── Base URL helper ────────────────────────────────────────────────────────
if (!function_exists('app_base_url')) {
    function app_base_url(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $docRoot  = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        $appRoot  = rtrim(ROOT_PATH, '/');
        $subPath  = str_replace($docRoot, '', $appRoot);
        return $protocol . '://' . $host . $subPath;
    }
}

// ── Role → Dashboard mapping ───────────────────────────────────────────────
define('ROLE_DASHBOARDS', [
    'admin'    => '/admin/dashboard.php',
    'manager'  => '/manager/dashboard.php',
    'employee' => '/employee/dashboard.php',
    'store'    => '/store/dashboard.php',
    'finance'  => '/finance/dashboard.php',
]);

/**
 * Returns the dashboard URL for a given role.
 *
 * @param string $role
 * @return string Absolute URL path
 */
function role_dashboard_url(string $role): string
{
    $map  = ROLE_DASHBOARDS;
    $base = app_base_url();
    return isset($map[$role]) ? $base . $map[$role] : $base . '/auth/login.php';
}

// ── Currency ───────────────────────────────────────────────────────────────
define('CURRENCY_SYMBOL', '₱');

// ── Pagination ─────────────────────────────────────────────────────────────
define('ITEMS_PER_PAGE', 15);

// ── Error reporting ────────────────────────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}
