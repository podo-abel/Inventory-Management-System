<?php
/**
 * includes/sidebar.php
 * Logitrack IMS — Left Sidebar Navigation
 *
 * Renders the role-appropriate sidebar navigation menu.
 * Active item is detected by comparing the current request URI to each href.
 *
 * Requires: includes/auth.php, includes/functions.php, includes/permissions.php
 */

$_sb_role  = get_current_role();
$_sb_items = get_nav_items($_sb_role);
$_sb_uri   = strtok($_SERVER['REQUEST_URI'] ?? '/', '?'); // strip query string
?>
<aside id="sidebar" class="sidebar" aria-label="Main navigation">
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
                    <span class="material-symbols-outlined sidebar__icon" aria-hidden="true">
                        <?= e($_sb_item['icon']) ?>
                    </span>
                    <span class="sidebar__label"><?= e($_sb_item['label']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
    </nav>
</aside>
<?php unset($_sb_role, $_sb_items, $_sb_uri, $_sb_item, $_sb_href, $_sb_href_path, $_sb_is_active); ?>
