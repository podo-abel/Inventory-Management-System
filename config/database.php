<?php
/**
 * config/database.php
 * GCM IMS — Database Configuration
 *
 * Provides a PDO singleton connection to the MySQL/MariaDB database.
 * Never expose credentials or SQL errors to end users.
 *
 * Usage:
 *   require_once __DIR__ . '/database.php';
 *   $pdo = get_db_connection();
 */

// ── Database constants ─────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'gcm_ims');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── PDO singleton ──────────────────────────────────────────────────────────
/**
 * Returns a shared PDO connection instance.
 *
 * On failure the function logs the technical error and returns null.
 * Callers must handle null gracefully and show a user-friendly error.
 *
 * @return PDO|null
 */
function get_db_connection(): ?PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Log technical detail — never expose to browser
        error_log('[GCM DB] Connection failed: ' . $e->getMessage());
        $pdo = null;
    }

    return $pdo;
}
