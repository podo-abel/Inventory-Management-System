<?php
/**
 * Logitrack IMS — Login Page
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

start_secure_session();

// ── Already logged in → redirect to dashboard ─────────────────────────────
if (is_logged_in()) {
    header('Location: ' . role_dashboard_url(get_current_role()));
    exit;
}

// ── Flash messages ────────────────────────────────────────────────────────
$login_error   = '';
$login_success = '';

// Logout confirmation
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    $logout_msg    = isset($_GET['msg']) ? urldecode($_GET['msg']) : 'You have been signed out.';
    $login_success = htmlspecialchars($logout_msg, ENT_QUOTES, 'UTF-8');
}

// ── POST: Process login ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $login_error = 'Your session has expired. Please refresh the page and try again.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $login_error = 'Please enter your credentials.';
        } else {
            $pdo = get_db_connection();
            if (!$pdo) {
                $login_error = 'A system error occurred. Please try again later.';
                error_log('[Logitrack Login] DB connection failed');
            } else {
                try {
                    // Look up by username OR email
                    $stmt = $pdo->prepare(
                        'SELECT id, username, email, password_hash, role, full_name, is_active
                         FROM users
                         WHERE (username = :id1 OR email = :id2)
                         LIMIT 1'
                    );
                    $stmt->execute([':id1' => $identifier, ':id2' => $identifier]);
                    $user = $stmt->fetch();

                    $auth_ok = false;
                    if ($user && $user['is_active'] == 1) {
                        $auth_ok = password_verify($password, $user['password_hash']);
                    }

                    if ($auth_ok) {
                        login_user($user);

                        // Log login activity
                        try {
                            $logStmt = $pdo->prepare(
                                'INSERT INTO activity_logs (user_id, action, description, ip_address)
                                 VALUES (:uid, :action, :desc, :ip)'
                            );
                            $logStmt->execute([
                                ':uid'    => $user['id'],
                                ':action' => 'login',
                                ':desc'   => 'User logged in',
                                ':ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
                            ]);
                        } catch (PDOException $e) {
                            error_log('[Logitrack] login log error: ' . $e->getMessage());
                        }

                        header('Location: ' . role_dashboard_url($user['role']));
                        exit;
                    } else {
                        // Generic message — do not reveal whether username or password was wrong
                        $login_error = 'Invalid username or password. Please check your credentials.';
                        // Small artificial delay to slow brute-force
                        usleep(300000);
                    }
                } catch (PDOException $e) {
                    error_log('[Logitrack Login] DB error: ' . $e->getMessage());
                    $login_error = 'A system error occurred. Please try again later.';
                }
            }
        }
    }
}

// ── CSRF token for the form ───────────────────────────────────────────────
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Logicore IMS</title>
    <meta name="description" content="Sign in to the Logicore Inventory Management System.">
    <meta name="robots" content="noindex, nofollow">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Google Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0&display=swap" rel="stylesheet">

    <!-- Logitrack IMS Styles -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body class="auth-page">

    <!-- Login Card -->
    <main class="auth-card" role="main">

        <!-- ── Header ─────────────────────────────────────────────────── -->
        <header class="auth-card__header">
            <div class="auth-card__brand">
                <span class="material-symbols-outlined auth-card__brand-icon" aria-hidden="true">inventory_2</span>
                <h1 class="auth-card__app-name">Logicore IMS</h1>
            </div>
            <h2 class="auth-card__title">Welcome Back</h2>
            <p class="auth-card__subtitle">Login to your operational hub</p>
        </header>

        <!-- ── Flash messages ─────────────────────────────────────────── -->
        <?php if (!empty($login_error)): ?>
            <div class="alert alert--error" role="alert" aria-live="assertive">
                <span class="material-symbols-outlined" aria-hidden="true">error</span>
                <span><?php echo htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($login_success)): ?>
            <div class="alert alert--success" role="status" aria-live="polite">
                <span class="material-symbols-outlined" aria-hidden="true">check_circle</span>
                <span><?php echo htmlspecialchars($login_success, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <!-- ── Login Form ─────────────────────────────────────────────── -->
        <form
            id="loginForm"
            class="form"
            method="POST"
            action=""
            novalidate
            aria-label="Login form"
        >
            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
            <!-- Email / Username -->
            <div class="form-field">
                <label class="form-label" for="identifier">
                    Email or Username
                </label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon" aria-hidden="true">person</span>
                    <input
                        type="text"
                        id="identifier"
                        name="identifier"
                        class="form-control"
                        placeholder="Enter your credentials"
                        autocomplete="username"
                        autocapitalize="none"
                        autocorrect="off"
                        spellcheck="false"
                        aria-required="true"
                        aria-describedby="identifier-error"
                    >
                </div>
                <span class="form-error-text" id="identifier-error" role="alert"></span>
            </div>

            <!-- Password -->
            <div class="form-field">
                <label class="form-label" for="password">
                    Password
                </label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon" aria-hidden="true">lock</span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control has-toggle"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        aria-required="true"
                        aria-describedby="password-error"
                    >
                    <button
                        type="button"
                        class="btn-toggle-password"
                        data-target="password"
                        aria-label="Show password"
                    >
                        <span class="material-symbols-outlined" aria-hidden="true">visibility_off</span>
                    </button>
                </div>
                <span class="form-error-text" id="password-error" role="alert"></span>
            </div>

            <!-- Remember me + Forgot password -->
            <div class="form-row">
                <label class="form-check">
                    <input
                        type="checkbox"
                        name="remember_me"
                        id="remember_me"
                        class="form-check__input"
                        value="1"
                    >
                    <span class="form-check__label">Remember me</span>
                </label>

                <a
                    href="forgot-password.php"
                    class="form-link"
                    aria-label="Forgot your password? Reset it here."
                >
                    Forgot password?
                </a>
            </div>

            <!-- Submit -->
            <button
                type="submit"
                id="loginBtn"
                class="btn btn-primary btn-block"
                aria-label="Login to Logicore IMS"
            >
                <span class="btn-text">Login</span>
                <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
            </button>
        </form>

        <!-- ── Footer ─────────────────────────────────────────────────── -->
        <footer class="auth-card__footer" aria-label="Security notice">
            <span class="material-symbols-outlined auth-card__footer-icon" aria-hidden="true">security</span>
            <span class="auth-card__footer-text">Enterprise Security Enabled</span>
        </footer>

    </main>

    <!-- Scripts -->
    <script src="../assets/js/validation.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        /**
         * Login page — client-side validation.
         *
         * This script validates the form before submission.
         * It does NOT authenticate the user. Authentication is handled
         * by the PHP backend (implemented in the Auth phase).
         */
        (function () {
            'use strict';

            document.addEventListener('DOMContentLoaded', function () {
                var form = document.getElementById('loginForm');
                var submitBtn = document.getElementById('loginBtn');

                if (!form) return;

                var validator = new window.IMS.FormValidator(form);

                validator
                    .addRule(
                        'identifier',
                        function (v) { return window.IMS.Validators.required(v); },
                        'Please enter your email or username.'
                    )
                    .addRule(
                        'password',
                        function (v) { return window.IMS.Validators.required(v); },
                        'Please enter your password.'
                    )
                    .attachBlurValidation();

                form.addEventListener('submit', function (e) {
                    var isValid = validator.validate();

                    if (!isValid) {
                        e.preventDefault();

                        // Move focus to the first invalid field
                        var firstInvalid = form.querySelector('.is-invalid');
                        if (firstInvalid) {
                            firstInvalid.focus();
                        }
                        return;
                    }

                    // Visual loading state — backend will ultimately control
                    // whether the submission succeeds or not.
                    window.IMS.ButtonUI.setLoading(submitBtn, 'Signing in');
                });
            });
        }());
    </script>
</body>
</html>
