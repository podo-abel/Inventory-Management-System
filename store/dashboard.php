<?php
/**
 * store/dashboard.php — Store Dashboard
 *
 * Updated: Store now handles request approval.
 * Dashboard shows pending requests waiting for review alongside approved requests ready for issuance.
 */
$page_title = 'Dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('store');

$pdo = get_db_connection();
$s = $pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1'); $total_products = (int)$s->fetchColumn();
$s = $pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND quantity_in_stock <= min_stock_level AND quantity_in_stock > 0'); $low_count = (int)$s->fetchColumn();
$s = $pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND quantity_in_stock = 0'); $out_count = (int)$s->fetchColumn();
$s = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'"); $pending_count = (int)$s->fetchColumn();
$s = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'approved'"); $approved_count = (int)$s->fetchColumn();

$pending_reqs = $pdo->query(
    "SELECT r.id, r.reference_no, r.created_at, u.full_name AS employee_name, COUNT(ri.id) AS item_count
     FROM requests r JOIN users u ON u.id = r.user_id LEFT JOIN request_items ri ON ri.request_id = r.id
     WHERE r.status = 'pending'
     GROUP BY r.id ORDER BY r.created_at ASC LIMIT 5"
)->fetchAll();

$ready_reqs = $pdo->query(
    "SELECT r.id, r.reference_no, r.created_at, u.full_name AS employee_name, COUNT(ri.id) AS item_count
     FROM requests r JOIN users u ON u.id = r.user_id LEFT JOIN request_items ri ON ri.request_id = r.id
     WHERE r.status = 'approved'
     GROUP BY r.id ORDER BY r.created_at ASC LIMIT 5"
)->fetchAll();

$low_stock = $pdo->query('SELECT p.name, p.sku, p.unit, p.quantity_in_stock, p.min_stock_level FROM products p WHERE p.is_active=1 AND p.quantity_in_stock <= p.min_stock_level ORDER BY p.quantity_in_stock ASC LIMIT 8')->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Store Dashboard</h1><p class="page-header__subtitle"><?= e(date('l, F j, Y')) ?></p></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="stats-grid">
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--yellow"><span class="material-symbols-outlined">pending_actions</span></div><div><p class="stat-card__label">Pending Review</p><p class="stat-card__value"><?= e($pending_count) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--green"><span class="material-symbols-outlined">assignment_turned_in</span></div><div><p class="stat-card__label">Ready to Issue</p><p class="stat-card__value"><?= e($approved_count) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">inventory_2</span></div><div><p class="stat-card__label">Total Products</p><p class="stat-card__value"><?= e($total_products) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--red"><span class="material-symbols-outlined">warning</span></div><div><p class="stat-card__label">Low / Out of Stock</p><p class="stat-card__value"><?= e($low_count + $out_count) ?></p></div></div>
</div>

<div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);">

<!-- Pending Requests Awaiting Review -->
<div class="content-card">
    <div class="content-card__header">
        <h2 class="content-card__title">Pending Requests</h2>
        <a href="<?= e(app_base_url()) ?>/store/requests.php?status=pending" class="btn btn--secondary">View All</a>
    </div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Reference</th><th>Employee</th><th>Items</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($pending_reqs as $req): ?>
            <tr>
                <td><code><?= e($req['reference_no']) ?></code></td>
                <td><?= e($req['employee_name']) ?></td>
                <td><?= e($req['item_count']) ?></td>
                <td><a href="<?= e(app_base_url()) ?>/store/requests/view.php?id=<?= (int)$req['id'] ?>" class="btn btn--primary" style="padding:.25rem .75rem;font-size:.8rem;">Review</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($pending_reqs)): ?><tr><td colspan="4" style="text-align:center; color:var(--color-text-secondary);">No pending requests.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Approved Requests Ready for Issuance -->
<div class="content-card">
    <div class="content-card__header">
        <h2 class="content-card__title">Approved — Ready to Issue</h2>
        <a href="<?= e(app_base_url()) ?>/store/requests.php?status=approved" class="btn btn--secondary">View All</a>
    </div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Reference</th><th>Employee</th><th>Items</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($ready_reqs as $req): ?>
            <tr>
                <td><code><?= e($req['reference_no']) ?></code></td>
                <td><?= e($req['employee_name']) ?></td>
                <td><?= e($req['item_count']) ?></td>
                <td><a href="<?= e(app_base_url()) ?>/store/requests/view.php?id=<?= (int)$req['id'] ?>" class="btn btn--primary" style="padding:.25rem .75rem;font-size:.8rem;">Process</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($ready_reqs)): ?><tr><td colspan="4" style="text-align:center; color:var(--color-text-secondary);">No approved requests.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<!-- Stock Alerts -->
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Stock Alerts</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Product</th><th>Status</th><th>Stock</th><th>Min</th></tr></thead>
            <tbody>
            <?php foreach ($low_stock as $p): ?>
            <tr style="<?= $p['quantity_in_stock'] <= 0 ? 'background:#fff5f5;' : '' ?>">
                <td style="font-weight:500;"><?= e($p['name']) ?></td>
                <td>
                    <?php if ($p['quantity_in_stock'] <= 0): ?>
                        <span class="badge badge--error" style="font-size:12px;">Out of Stock</span>
                    <?php else: ?>
                        <span class="badge badge--warning" style="font-size:12px; background:var(--color-warning-container); color:var(--color-on-warning-container);">Low Stock</span>
                    <?php endif; ?>
                </td>
                <td><strong style="color:<?= $p['quantity_in_stock'] <= 0 ? 'var(--color-error)' : 'inherit' ?>"><?= e($p['quantity_in_stock']) ?></strong> <?= e($p['unit']) ?></td>
                <td class="color-on-surface-var"><?= e($p['min_stock_level']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($low_stock)): ?><tr><td colspan="4" style="text-align:center;">Stock levels are good.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
