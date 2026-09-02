<?php
/**
 * includes/sidebar.php
 * Logitrack IMS — Left Sidebar Navigation
 *
 * Renders the role-appropriate sidebar navigation menu.
 * Matches Stitch design: dark navy background, logo area at top,
 * navigation links in the middle, logout at the bottom.
 *
 * Requires: includes/auth.php, includes/functions.php, includes/permissions.php
 */

$_sb_role  = get_current_role();
$_sb_items = get_nav_items($_sb_role);
$_sb_uri   = strtok($_SERVER['REQUEST_URI'] ?? '/', '?'); // strip query string
$_sb_csrf  = generate_csrf_token();
?>
<aside id="sidebar" class="sidebar" aria-label="Main navigation">
    <!-- Brand / Logo Area -->
    <div class="sidebar__brand">
        <div class="sidebar__brand-icon-wrap">
            <img src="<?= app_base_url() ?>/assets/images/logo-sidebar.svg" alt="LogiTrack" class="sidebar__brand-icon" width="28" height="28">
        </div>
        <div>
            <h1 class="sidebar__brand-name"><?= APP_NAME ?></h1>
            <p class="sidebar__brand-subtitle">Operational Hub</p>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="sidebar__nav" role="navigation">
        <ul class="sidebar__list" role="list">
        <?php foreach ($_sb_items as $_sb_item): ?>
            <?php
            // Mark item as active if the current URI starts with the item href path
            $_sb_href       = $_sb_item['href'];
            $_sb_href_path  = parse_url($_sb_href, PHP_URL_PATH);
            $_sb_is_active  = ($href_path = $_sb_href_path) && (
                $_sb_uri === $href_path ||
                (strlen($href_path) > 1 && strpos($_sb_uri, rtrim($href_path, '/')) === 0)
            );
            ?>
            <li class="sidebar__item">
                <a
                    href="<?= e($_sb_href) ?>"
                    class="sidebar__link<?= $_sb_is_active ? ' sidebar__link--active' : '' ?>"
                    <?= $_sb_is_active ? 'aria-current="page"' : '' ?>
                >
                    <span class="material-symbols-outlined sidebar__icon" aria-hidden="true"<?= $_sb_is_active ? ' style="font-variation-settings: \'FILL\' 1;"' : '' ?>>
                        <?= e($_sb_item['icon']) ?>
                    </span>
                    <span class="sidebar__label"><?= e($_sb_item['label']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
    </nav>

    <!-- Logout (bottom) -->
    <div class="sidebar__footer">
        <form method="POST" action="<?= e(app_base_url()) ?>/auth/logout.php" class="sidebar__logout-form">
            <input type="hidden" name="csrf_token" value="<?= e($_sb_csrf) ?>">
            <button type="submit" class="sidebar__link sidebar__logout-btn" aria-label="Sign out">
                <span class="material-symbols-outlined sidebar__icon" aria-hidden="true">logout</span>
                <span class="sidebar__label">Logout</span>
            </button>
        </form>
    </div>
</aside>
<?php unset($_sb_role, $_sb_items, $_sb_uri, $_sb_item, $_sb_href, $_sb_href_path, $_sb_is_active, $_sb_csrf); ?>
