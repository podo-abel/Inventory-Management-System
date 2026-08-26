<?php
/**
 * admin/dashboard.php — Admin Dashboard
 */
$page_title = 'Dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
$pdo = get_db_connection();
$total_users      = $pdo->query('SELECT COUNT(*) FROM users WHERE is_active=1')->fetchColumn();
$total_products   = $pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1')->fetchColumn();
$total_categories = $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$total_suppliers  = $pdo->query('SELECT COUNT(*) FROM suppliers WHERE is_active=1')->fetchColumn();
$low_stock_count  = $pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND quantity_in_stock<=min_stock_level')->fetchColumn();
$pending_requests = $pdo->query("SELECT COUNT(*) FROM requests WHERE status='pending'")->fetchColumn();
$activity_list    = $pdo->query("SELECT al.*,u.full_name FROM activity_logs al LEFT JOIN users u ON al.user_id=u.id ORDER BY al.created_at DESC LIMIT 8")->fetchAll();
$role_counts      = $pdo->query("SELECT role,COUNT(*) cnt FROM users WHERE is_active=1 GROUP BY role")->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1 class="page-header__title">Admin Dashboard</h1>
    <p class="page-header__subtitle">System overview &mdash; <?= e(date('l, F j, Y')) ?></p>
</div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="stats-grid">
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">group</span></div><div><p class="stat-card__label">Active Users</p><p class="stat-card__value"><?= e($total_users) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--green"><span class="material-symbols-outlined">inventory_2</span></div><div><p class="stat-card__label">Products</p><p class="stat-card__value"><?= e($total_products) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--purple"><span class="material-symbols-outlined">category</span></div><div><p class="stat-card__label">Categories</p><p class="stat-card__value"><?= e($total_categories) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">local_shipping</span></div><div><p class="stat-card__label">Suppliers</p><p class="stat-card__value"><?= e($total_suppliers) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--yellow"><span class="material-symbols-outlined">warning</span></div><div><p class="stat-card__label">Low Stock Items</p><p class="stat-card__value"><?= e($low_stock_count) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--red"><span class="material-symbols-outlined">pending_actions</span></div><div><p class="stat-card__label">Pending Requests</p><p class="stat-card__value"><?= e($pending_requests) ?></p></div></div>
</div>
<div class="content-card" style="margin-bottom:var(--space-6)">
    <div class="content-card__header"><h2 class="content-card__title">Quick Actions</h2></div>
    <div class="content-card__body" style="display:flex;gap:var(--space-3);flex-wrap:wrap;">
        <a href="<?= e(app_base_url()) ?>/admin/users/create.php" class="btn btn--primary"><span class="material-symbols-outlined">person_add</span> Add User</a>
        <a href="<?= e(app_base_url()) ?>/admin/products/create.php" class="btn btn--primary"><span class="material-symbols-outlined">add_box</span> Add Product</a>
        <a href="<?= e(app_base_url()) ?>/admin/categories/create.php" class="btn btn--secondary"><span class="material-symbols-outlined">create_new_folder</span> Add Category</a>
        <a href="<?= e(app_base_url()) ?>/admin/suppliers/create.php" class="btn btn--secondary"><span class="material-symbols-outlined">add_business</span> Add Supplier</a>
        <a href="<?= e(app_base_url()) ?>/admin/activity-log.php" class="btn btn--secondary"><span class="material-symbols-outlined">history</span> Activity Log</a>
    </div>
</div>
<div style="display:grid;grid-template-columns:1fr 2fr;gap:var(--space-6);">
    <div class="content-card">
        <div class="content-card__header"><h2 class="content-card__title">Users by Role</h2></div>
        <div class="content-card__body" style="padding:0;">
            <table class="table"><thead><tr><th>Role</th><th>Count</th></tr></thead><tbody>
            <?php foreach ($role_counts as $rc): ?><tr><td><span class="badge badge--info"><?= e(ucfirst($rc['role'])) ?></span></td><td style="font-weight:600;"><?= e($rc['cnt']) ?></td></tr><?php endforeach; ?>
            <?php if(empty($role_counts)): ?><tr><td colspan="2" class="text-center">No users.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </div>
    <div class="content-card">
        <div class="content-card__header"><h2 class="content-card__title">Recent Activity</h2><a href="<?= e(app_base_url()) ?>/admin/activity-log.php" class="btn btn--secondary">View All</a></div>
        <div class="content-card__body" style="padding:0;">
            <table class="table"><thead><tr><th>User</th><th>Action</th><th>Description</th><th>Date</th></tr></thead><tbody>
            <?php foreach ($activity_list as $log): ?>
            <tr>
                <td><?= e($log['full_name'] ?? '—') ?></td>
                <td><code style="font-size:.7rem;background:var(--color-bg);padding:2px 6px;border-radius:4px;"><?= e($log['action']) ?></code></td>
                <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($log['description']) ?>"><?= e($log['description']) ?></td>
                <td style="white-space:nowrap;font-size:var(--text-xs);color:var(--color-text-secondary);"><?= e(format_datetime($log['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($activity_list)): ?><tr><td colspan="4" class="text-center">No activity yet.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
