<?php
/**
 * auth/logout.php
 * Logitrack IMS — Logout Handler
 *
 * Accepts a POST request with a valid CSRF token.
 * Destroys the session and redirects to the login page.
 *
 * GET requests without a valid token are redirected to login directly.
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

start_secure_session();

// Only destroy session on a POST with valid CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (verify_csrf_token($token)) {
        // Log the logout action before destroying session
        if (is_logged_in()) {
            $user = get_logged_in_user();
            try {
                $pdo = get_db_connection();
                if ($pdo && $user) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO activity_logs (user_id, action, description, ip_address)
                         VALUES (:uid, :action, :desc, :ip)'
                    );
                    $stmt->execute([
                        ':uid'    => $user['user_id'],
                        ':action' => 'logout',
                        ':desc'   => 'User logged out',
                        ':ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
                    ]);
                }
            } catch (PDOException $e) {
                error_log('[Logitrack] logout log error: ' . $e->getMessage());
            }
        }

        logout_user('You have been signed out successfully.');
        // logout_user() calls exit — execution does not continue
    }
}

// Invalid token or GET request — redirect without destroying session
$base = app_base_url();
header('Location: ' . $base . '/auth/login.php');
exit;
