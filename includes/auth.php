<?php
/**
 * includes/auth.php
 * GCM IMS — Authentication & Session Helpers
 *
 * Provides session management, login/logout, CSRF protection, and
 * role-based access control helpers.
 *
 * Requires: config/app.php, config/database.php
 */

// ── Session ────────────────────────────────────────────────────────────────

/**
 * Start a secure named session.
 * Call this at the top of every page (once per request).
 */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return; // already started
    }

    session_name(SESSION_NAME);

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,   // set true if HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// ── Authentication state ───────────────────────────────────────────────────

/**
 * Returns true if a user is currently logged in.
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Returns the current user's session data array, or null.
 *
 * @return array|null Keys: user_id, username, email, role, full_name
 */
function get_logged_in_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    return [
        'user_id'   => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'email'     => $_SESSION['email'],
        'role'      => $_SESSION['role'],
        'full_name' => $_SESSION['full_name'],
    ];
}

/**
 * Returns the current user's role string, or empty string.
 */
function get_current_role(): string
{
    return $_SESSION['role'] ?? '';
}

// ── Access guards ──────────────────────────────────────────────────────────

/**
 * Redirects to login if user is not authenticated.
 *
 * @param bool $redirect  If false, just returns false instead of redirecting.
 * @return bool
 */
function require_login(bool $redirect = true): bool
{
    if (is_logged_in()) {
        return true;
    }

    if ($redirect) {
        $base = app_base_url();
        $next = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header('Location: ' . $base . '/auth/login.php' . ($next ? '?next=' . $next : ''));
        exit;
    }

    return false;
}

/**
 * Restricts access to users whose role is in $allowed_roles.
 * Redirects to 403 page (or login) if not allowed.
 *
 * @param string|array $allowed_roles  Single role string or array of roles.
 * @param bool         $redirect
 * @return bool
 */
function require_role($allowed_roles, bool $redirect = true): bool
{
    if (!is_logged_in()) {
        return require_login($redirect);
    }

    $allowed = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles];
    $role    = get_current_role();

    if (in_array($role, $allowed, true)) {
        return true;
    }

    if ($redirect) {
        $base = app_base_url();
        header('Location: ' . $base . '/auth/login.php?error=unauthorized');
        exit;
    }

    return false;
}

// ── Login / Logout ─────────────────────────────────────────────────────────

/**
 * Establishes an authenticated session for the given user row.
 * Regenerates the session ID to prevent session fixation.
 * Updates last_login_at in the database.
 *
 * @param array $user  Row from the users table.
 */
function login_user(array $user): void
{
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['logged_in_at'] = time();

    // Update last_login_at
    try {
        $pdo = get_db_connection();
        if ($pdo) {
            $stmt = $pdo->prepare(
                'UPDATE users SET last_login_at = NOW() WHERE id = :id'
            );
            $stmt->execute([':id' => (int) $user['id']]);
        }
    } catch (PDOException $e) {
        error_log('[GCM Auth] last_login_at update failed: ' . $e->getMessage());
    }
}

/**
 * Destroys the session and redirects to the login page.
 *
 * @param string $message  Optional message to flash on the login page.
 */
function logout_user(string $message = 'You have been signed out successfully.'): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Clear session data
        $_SESSION = [];

        // Destroy the cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    $base = app_base_url();
    $msg  = urlencode($message);
    header('Location: ' . $base . '/auth/login.php?logout=1&msg=' . $msg);
    exit;
}

// ── CSRF ───────────────────────────────────────────────────────────────────

/**
 * Generates (or retrieves) a CSRF token stored in the session.
 *
 * @return string  Hex-encoded random token.
 */
function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies a submitted CSRF token against the session token.
 * Uses hash_equals to prevent timing attacks.
 *
 * @param string $token  Token from the form submission.
 * @return bool
 */
function verify_csrf_token(string $token): bool
{
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
