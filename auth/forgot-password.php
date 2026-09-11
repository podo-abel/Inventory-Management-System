<?php
/**
 * auth/forgot-password.php
 * GCM IMS — Forgot Password Page (UI Phase)
 *
 * This page provides the UI for password-reset request.
 * The actual backend email / token logic belongs to the Authentication phase.
 *
 * Stitch reference:
 *   No dedicated Stitch screen found for forgot-password.
 *   Design follows the login card pattern from login_gcm_ims/code.html.
 */

// Backend placeholder: in the auth phase this will process the POST and
// send a password-reset email. For now, just capture any status query param.
$reset_error   = '';
$reset_success = '';

if (isset($_GET['sent']) && $_GET['sent'] === '1') {
    $reset_success = 'If that email exists in our system, a reset link has been sent.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — GCM IMS</title>
    <meta name="description" content="Reset your GCM IMS account password.">
    <meta name="robots" content="noindex, nofollow">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/images/favicon.svg">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Google Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0&display=swap" rel="stylesheet">

    <!-- GCM IMS Styles -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body class="auth-page">

    <!-- Forgot Password Card -->
    <main class="auth-card" role="main">

        <!-- ── Back link ──────────────────────────────────────────────── -->
        <a href="login.php" class="auth-card__back-link" aria-label="Return to the login page">
            <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
            Back to Login
        </a>

        <!-- ── Header ─────────────────────────────────────────────────── -->
        <header class="auth-card__header">
            <div class="auth-card__brand">
                <span class="material-symbols-outlined auth-card__brand-icon" aria-hidden="true">inventory_2</span>
                <h1 class="auth-card__app-name">GCM IMS</h1>
            </div>
            <h2 class="auth-card__title">Reset Your Password</h2>
            <p class="auth-card__subtitle">
                Enter the email address linked to your account and we&rsquo;ll send reset instructions.
            </p>
        </header>

        <!-- ── Flash messages ─────────────────────────────────────────── -->
        <?php if (!empty($reset_error)): ?>
            <div class="alert alert--error" role="alert" aria-live="assertive">
                <span class="material-symbols-outlined" aria-hidden="true">error</span>
                <span><?php echo htmlspecialchars($reset_error, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($reset_success)): ?>
            <div class="alert alert--success" role="status" aria-live="polite">
                <span class="material-symbols-outlined" aria-hidden="true">check_circle</span>
                <span><?php echo htmlspecialchars($reset_success, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <!-- ── Forgot Password Form ───────────────────────────────────── -->
        <form
            id="forgotPasswordForm"
            class="form"
            method="POST"
            action=""
            novalidate
            aria-label="Password reset form"
        >
            <!-- Email -->
            <div class="form-field">
                <label class="form-label" for="reset_email">
                    Email Address
                </label>
                <div class="input-wrapper">
                    <span class="material-symbols-outlined input-icon" aria-hidden="true">mail</span>
                    <input
                        type="email"
                        id="reset_email"
                        name="reset_email"
                        class="form-control"
                        placeholder="your.email@company.com"
                        autocomplete="email"
                        autocapitalize="none"
                        autocorrect="off"
                        spellcheck="false"
                        aria-required="true"
                        aria-describedby="reset-email-error reset-email-hint"
                    >
                </div>
                <span class="form-helper" id="reset-email-hint">
                    We&rsquo;ll only send an email if the address is registered.
                </span>
                <span class="form-error-text" id="reset-email-error" role="alert"></span>
            </div>

            <!-- Submit -->
            <button
                type="submit"
                id="resetBtn"
                class="btn btn-primary btn-block"
                aria-label="Send password reset instructions"
            >
                <span class="btn-text">Send Reset Link</span>
                <span class="material-symbols-outlined" aria-hidden="true">send</span>
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
         * Forgot Password page — client-side validation.
         * Validates email format before submission.
         */
        (function () {
            'use strict';

            document.addEventListener('DOMContentLoaded', function () {
                var form = document.getElementById('forgotPasswordForm');
                var submitBtn = document.getElementById('resetBtn');

                if (!form) return;

                var validator = new window.IMS.FormValidator(form);

                validator
                    .addRule(
                        'reset_email',
                        function (v) { return window.IMS.Validators.required(v); },
                        'Please enter your email address.'
                    )
                    .addRule(
                        'reset_email',
                        function (v) { return window.IMS.Validators.email(v); },
                        'Please enter a valid email address.'
                    )
                    .attachBlurValidation();

                form.addEventListener('submit', function (e) {
                    var isValid = validator.validate();

                    if (!isValid) {
                        e.preventDefault();
                        var firstInvalid = form.querySelector('.is-invalid');
                        if (firstInvalid) {
                            firstInvalid.focus();
                        }
                        return;
                    }

                    window.IMS.ButtonUI.setLoading(submitBtn, 'Sending');
                });
            });
        }());
    </script>
</body>
</html>
