<?php
/**
 * index.php
 * Logitrack IMS — Entry Point
 *
 * Routes authenticated users to their role dashboard.
 * Redirects unauthenticated users to the login page.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

start_secure_session();

if (is_logged_in()) {
    header('Location: ' . role_dashboard_url(get_current_role()));
} else {
    header('Location: ' . app_base_url() . '/auth/login.php');
}
exit;
