<?php
/**
 * includes/navbar.php
 * GCM IMS — Top Navigation Bar
 *
 * Matches Stitch design: light bg-surface background, page title on left,
 * search bar + notifications + user info on right.
 * Brand/logo and logout are handled by the sidebar.
 *
 * Requires: includes/auth.php, includes/functions.php
 * Assumes start_secure_session() and require_login() have already been called.
 */

$_nav_user = get_logged_in_user();
$_nav_role = get_current_role();

// Human-readable role labels
$_nav_role_labels = [
    'admin'    => 'Administrator',
    'manager'  => 'Manager',
    'employee' => 'Employee',
    'store'    => 'Store Keeper',
    'finance'  => 'Finance Officer',
];
$_nav_role_label = $_nav_role_labels[$_nav_role] ?? ucfirst($_nav_role);
$_nav_display_name = $_nav_user['full_name'] ?? 'User';

// Generate initials for avatar fallback
$_nav_initials = '';
$_name_parts = explode(' ', $_nav_display_name);
foreach ($_name_parts as $_part) {
    if (!empty($_part)) $_nav_initials .= strtoupper($_part[0]);
}
$_nav_initials = substr($_nav_initials, 0, 2);
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

        <!-- Page title (visible on desktop) -->
        <h2 class="navbar__page-title">
            <?= e($page_title ?? 'Dashboard') ?>
        </h2>
    </div>

    <div class="navbar__right">
<?php
        $search_action = app_base_url();
        $search_placeholder = 'Search...';
        
        if (in_array($_nav_role, ['admin'])) {
            $search_action .= '/admin/users/index.php';
            $search_placeholder = 'Search employees...';
        } elseif ($_nav_role === 'manager') {
            $search_action .= '/manager/employees.php';
            $search_placeholder = 'Search staff...';
        } elseif ($_nav_role === 'employee') {
            $search_action .= '/employee/request-item.php';
            $search_placeholder = 'Search items to request...';
        } elseif ($_nav_role === 'store') {
            $search_action .= '/store/inventory.php';
            $search_placeholder = 'Search inventory...';
        } else {
            $search_action .= '/finance/inventory.php';
            $search_placeholder = 'Search inventory...';
        }
        ?>
        <!-- Search bar -->
        <form class="navbar__search" action="<?= e($search_action) ?>" method="GET">
            <span class="material-symbols-outlined navbar__search-icon" aria-hidden="true">search</span>
            <input
                type="text"
                name="q"
                class="navbar__search-input"
                placeholder="<?= e($search_placeholder) ?>"
                aria-label="Search"
                value="<?= e($_GET['q'] ?? '') ?>"
            >
        </form>

        <!-- Notifications -->
        <?php
        $nav_user_id = (int)($_SESSION['user_id'] ?? 0);
        $nav_unread = get_unread_notifications_count($nav_user_id);
        $nav_pdo = get_db_connection();
        $nav_notifs = $nav_pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $nav_notifs->execute([$nav_user_id]);
        $nav_notifs = $nav_notifs->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div style="position:relative;">
            <button class="navbar__icon-btn" aria-label="Notifications" type="button" onclick="document.getElementById('notif-dropdown').classList.toggle('show');">
                <span class="material-symbols-outlined" aria-hidden="true">notifications</span>
                <?php if ($nav_unread > 0): ?>
                <span class="navbar__notif-dot" id="nav-notif-badge" aria-hidden="true" style="display:flex; justify-content:center; align-items:center; font-size:10px; font-weight:bold; color:white;"><?= $nav_unread > 99 ? '99+' : $nav_unread ?></span>
                <?php endif; ?>
            </button>
            <div id="notif-dropdown" style="display:none; position:absolute; top:100%; right:0; width:320px; background:white; border:1px solid var(--color-outline-variant); border-radius:var(--radius-md); box-shadow:0 8px 16px rgba(0,0,0,0.12); z-index:1000;">
                <div style="padding:12px 16px; border-bottom:1px solid var(--color-outline-variant); display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-weight:700; font-size:14px; color:var(--color-on-surface);">Notifications <?php if ($nav_unread > 0): ?><span class="badge badge--error" style="font-size:10px; margin-left:4px;"><?= $nav_unread ?></span><?php endif; ?></div>
                    <?php if ($nav_unread > 0): ?>
                    <button type="button" onclick="markAllNotifsRead()" style="background:none; border:none; font-size:11px; color:var(--color-primary); cursor:pointer; font-weight:600; padding:0;">Mark all read</button>
                    <?php endif; ?>
                </div>
                <div id="notif-dropdown-list" style="max-height:300px; overflow-y:auto;">
                    <?php if (empty($nav_notifs)): ?>
                        <div style="padding:24px 16px; text-align:center; color:var(--color-text-secondary); font-size:13px;">No notifications.</div>
                    <?php else: ?>
                        <?php foreach ($nav_notifs as $n): ?>
                        <a href="<?= e(app_base_url()) ?>/notifications.php?open=<?= (int)$n['id'] ?>" style="display:block; padding:10px 14px; border-bottom:1px solid var(--color-outline-variant); text-decoration:none; background:<?= $n['is_read'] ? 'white' : '#f0f7ff' ?>;">
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:2px;">
                                <strong style="font-size:13px; color:var(--color-on-surface);"><?= e($n['title']) ?></strong>
                                <?php if (!$n['is_read']): ?><span style="width:7px; height:7px; border-radius:50%; background:var(--color-primary); display:inline-block;"></span><?php endif; ?>
                            </div>
                            <div style="font-size:12px; color:var(--color-on-surface-variant); line-height:1.3; overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;"><?= e($n['message']) ?></div>
                            <div style="font-size:10px; color:var(--color-text-secondary); margin-top:4px;"><?= e(format_datetime($n['created_at'])) ?></div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div style="padding:10px; text-align:center; border-top:1px solid var(--color-outline-variant); background:var(--color-surface-container-lowest, #fafafa); border-radius:0 0 var(--radius-md) var(--radius-md);">
                    <a href="<?= e(app_base_url()) ?>/notifications.php" style="font-size:12px; font-weight:600; color:var(--color-primary); text-decoration:none;">View All Notifications →</a>
                </div>
            </div>
        </div>
        <script>
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.navbar__icon-btn') && !e.target.closest('#notif-dropdown')) {
                const drop = document.getElementById('notif-dropdown');
                if (drop) drop.classList.remove('show');
            }
        });
        function markAllNotifsRead() {
            fetch('<?= e(app_base_url()) ?>/api/notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mark_all_read'
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    const badge = document.getElementById('nav-notif-badge');
                    if (badge) badge.remove();
                    const list = document.getElementById('notif-dropdown-list');
                    if (list) {
                        const items = list.querySelectorAll('a');
                        items.forEach(el => el.style.background = 'white');
                        const dots = list.querySelectorAll('span[style*="border-radius:50%"]');
                        dots.forEach(d => d.remove());
                    }
                }
            }).catch(console.error);
        }
        </script>
        <style>#notif-dropdown.show { display:block !important; }</style>

        <!-- Divider -->
        <div class="navbar__divider"></div>

        <!-- User info -->
        <div class="navbar__user" aria-label="Logged in user">
            <div class="navbar__user-info">
                <span class="navbar__user-name"><?= e($_nav_display_name) ?></span>
                <span class="navbar__user-role"><?= e($_nav_role_label) ?></span>
            </div>
            <div class="navbar__avatar" aria-hidden="true">
                <span><?= e($_nav_initials) ?></span>
            </div>
        </div>
    </div>
</header>
<?php unset($_nav_user, $_nav_role, $_nav_role_labels, $_nav_role_label, $_nav_display_name, $_nav_initials, $_name_parts, $_part, $nav_pdo, $nav_notifs, $nav_unread); ?>
