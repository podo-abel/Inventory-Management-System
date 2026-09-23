<?php
/**
 * manager/dashboard.php — Manager Dashboard
 *
 * Updated: Manager now monitors requests (read-only).
 * Approval is handled by Store.
 */
$page_title = 'Dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('manager');

$pdo = get_db_connection();

$s = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'store_approved'"); $pending = (int)$s->fetchColumn();
$s = $pdo->query('SELECT COUNT(*) FROM users WHERE role = \'employee\' AND is_active = 1'); $emp_count = (int)$s->fetchColumn();
$s = $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1 AND quantity_in_stock <= min_stock_level'); $low_stock = (int)$s->fetchColumn();
$s = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'approved' AND reviewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"); $approved_week = (int)$s->fetchColumn();

$recent_reqs = $pdo->query(
    "SELECT r.id, r.reference_no, r.status, r.created_at, u.full_name AS employee_name, COUNT(ri.id) AS item_count
     FROM requests r JOIN users u ON u.id = r.user_id LEFT JOIN request_items ri ON ri.request_id = r.id
     GROUP BY r.id ORDER BY r.created_at DESC LIMIT 8"
)->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Manager Dashboard</h1><p class="page-header__subtitle"><?= e(date('l, F j, Y')) ?></p></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="stats-grid">
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--yellow"><span class="material-symbols-outlined">pending_actions</span></div><div><p class="stat-card__label">Awaiting Confirmation</p><p class="stat-card__value"><?= e($pending) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">badge</span></div><div><p class="stat-card__label">Employees</p><p class="stat-card__value"><?= e($emp_count) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--red"><span class="material-symbols-outlined">warning</span></div><div><p class="stat-card__label">Low Stock Items</p><p class="stat-card__value"><?= e($low_stock) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--green"><span class="material-symbols-outlined">check_circle</span></div><div><p class="stat-card__label">Approved This Week</p><p class="stat-card__value"><?= e($approved_week) ?></p></div></div>
</div>

<div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-5);">
    <div class="content-card" style="margin-bottom:0;">
        <div class="content-card__header">
            <h2 class="content-card__title">Recent Requests</h2>
            <a href="<?= e(app_base_url()) ?>/manager/requests.php" class="btn btn--secondary">View All Requests</a>
        </div>
        <div class="content-card__body" style="padding:0;">
            <table class="table">
                <thead><tr><th>Reference</th><th>Employee</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($recent_reqs as $req): ?>
                <tr>
                    <td><code><?= e($req['reference_no']) ?></code></td>
                    <td><?= e($req['employee_name']) ?></td>
                    <td><?= e(format_date($req['created_at'])) ?></td>
                    <td><span class="badge <?= e(get_status_badge_class($req['status'])) ?>"><?= e(ucfirst($req['status'])) ?></span></td>
                    <td><a href="<?= e(app_base_url()) ?>/manager/requests/view.php?id=<?= (int)$req['id'] ?>" class="btn btn--secondary" style="padding:.25rem .75rem;font-size:.8rem;">View</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recent_reqs)): ?><tr><td colspan="5" style="text-align:center;color:var(--color-text-secondary);">No requests yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="content-card" style="margin-bottom:0;">
        <div class="content-card__header" style="background-color:var(--color-error-container); border-bottom-color:rgba(186,26,26,0.2);">
            <h2 class="content-card__title" style="color:var(--color-on-error-container);">Low Stock Alerts</h2>
            <a href="<?= e(app_base_url()) ?>/manager/inventory.php" class="btn btn--secondary" style="background:rgba(255,255,255,0.5); color:var(--color-on-error-container); border:none;">View Inventory</a>
        </div>
        <div class="content-card__body" style="padding:0;">
            <?php 
            $low_stock_items = $pdo->query('SELECT id, name, quantity_in_stock, min_stock_level FROM products WHERE is_active=1 AND quantity_in_stock<=min_stock_level LIMIT 5')->fetchAll();
            if(!empty($low_stock_items)):
            ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Status</th>
                        <th>Current Stock</th>
                        <th>Min Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_stock_items as $item): ?>
                    <tr style="<?= $item['quantity_in_stock'] <= 0 ? 'background:#fff5f5;' : '' ?>">
                        <td style="font-weight:500;"><?= e($item['name']) ?></td>
                        <td>
                            <?php if ($item['quantity_in_stock'] <= 0): ?>
                                <span class="badge badge--error" style="font-size:12px;">Out of Stock</span>
                            <?php else: ?>
                                <span class="badge badge--warning" style="font-size:12px; background:var(--color-warning-container); color:var(--color-on-warning-container);">Low Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="color:<?= $item['quantity_in_stock'] <= 0 ? 'var(--color-error)' : 'inherit' ?>">
                                <?= e($item['quantity_in_stock']) ?>
                            </strong>
                        </td>
                        <td class="color-on-surface-var"><?= e($item['min_stock_level']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="padding:var(--space-6); text-align:center; color:var(--color-on-surface-variant);">
                <span class="material-symbols-outlined" style="font-size:48px; opacity:0.2; margin-bottom:var(--space-3); display:block;">inventory_2</span>
                All products are sufficiently stocked.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
