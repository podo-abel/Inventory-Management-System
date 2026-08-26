<?php
/**
 * includes/navbar.php
 * Logitrack IMS — Top Navigation Bar
 *
 * Renders the top bar for all authenticated pages.
 * Includes: app logo, hamburger (mobile sidebar toggle), user info, logout.
 *
 * Requires: includes/auth.php, includes/functions.php
 * Assumes start_secure_session() and require_login() have already been called.
 */

$_nav_user = get_logged_in_user();
$_nav_role = get_current_role();
$_nav_csrf = generate_csrf_token();

// Human-readable role labels
$_nav_role_labels = [
    'admin'    => 'Administrator',
    'manager'  => 'Manager',
    'employee' => 'Employee',
    'store'    => 'Store Keeper',
    'finance'  => 'Finance Officer',
];
$_nav_role_label = $_nav_role_labels[$_nav_role] ?? ucfirst($_nav_role);
?>
<header class="navbar" role="banner">
    <div class="navbar__left">
        <!-- Mobile sidebar toggle -->
        <button
            id="sidebar-toggle"
            class="navbar__menu-btn"
            aria-label="Toggle navigation menu"
            aria-expanded="false"
            aria-controls="sidebar"
            type="button"
        >
            <span class="material-symbols-outlined" aria-hidden="true">menu</span>
        </button>

        <!-- Brand -->
        <a href="<?= e(app_base_url()) ?>/index.php" class="navbar__brand" aria-label="Logitrack IMS home">
            <span class="material-symbols-outlined navbar__brand-icon" aria-hidden="true">inventory_2</span>
            <span class="navbar__brand-name"><?= APP_NAME ?></span>
        </a>
    </div>

    <div class="navbar__right">
        <!-- User info -->
        <div class="navbar__user" aria-label="Logged in user">
            <span class="material-symbols-outlined navbar__user-icon" aria-hidden="true">account_circle</span>
            <div class="navbar__user-info">
                <span class="navbar__user-name"><?= e($_nav_user['full_name'] ?? '') ?></span>
                <span class="badge badge--role navbar__user-role"><?= e($_nav_role_label) ?></span>
            </div>
        </div>

        <!-- Logout -->
        <form method="POST" action="<?= e(app_base_url()) ?>/auth/logout.php" class="navbar__logout-form">
            <input type="hidden" name="csrf_token" value="<?= e($_nav_csrf) ?>">
            <button type="submit" class="navbar__logout-btn" aria-label="Sign out">
                <span class="material-symbols-outlined" aria-hidden="true">logout</span>
                <span class="navbar__logout-label">Sign out</span>
            </button>
        </form>
    </div>
</header>
<?php unset($_nav_user, $_nav_role, $_nav_csrf, $_nav_role_labels, $_nav_role_label); ?>
