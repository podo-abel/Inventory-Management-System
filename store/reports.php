<?php
/**
 * store/reports.php — Store Reports
 */
$page_title = 'Reports';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('store');

$pdo = get_db_connection();
$s = $pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1'); $total_prod = (int)$s->fetchColumn();
$s = $pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND quantity_in_stock<=min_stock_level'); $low_prod = (int)$s->fetchColumn();
$s = $pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND quantity_in_stock=0'); $out_prod = (int)$s->fetchColumn();

$movements_by_type = $pdo->query("SELECT movement_type, COUNT(*) AS cnt, SUM(quantity) AS total_qty FROM stock_movements GROUP BY movement_type")->fetchAll();
$low_stock = $pdo->query('SELECT p.name, p.sku, p.unit, p.quantity_in_stock, p.min_stock_level FROM products p WHERE p.is_active=1 AND p.quantity_in_stock <= p.min_stock_level ORDER BY p.quantity_in_stock ASC LIMIT 20')->fetchAll();
$recent_movements = $pdo->query("SELECT sm.movement_type, sm.quantity, sm.created_at, p.name AS product_name, u.full_name AS performed_name FROM stock_movements sm JOIN products p ON p.id = sm.product_id LEFT JOIN users u ON u.id = sm.performed_by ORDER BY sm.created_at DESC LIMIT 10")->fetchAll();
$type_badge = ['receive'=>'badge--success','issue'=>'badge--info','adjustment'=>'badge--warning','return'=>'badge--neutral'];
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Store Reports</h1><p class="page-header__subtitle">Inventory status and movement summary.</p></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="stats-grid" style="margin-bottom:var(--space-5);">
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">inventory_2</span></div><div><p class="stat-card__label">Total Products</p><p class="stat-card__value"><?= $total_prod ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--yellow"><span class="material-symbols-outlined">warning</span></div><div><p class="stat-card__label">Low Stock</p><p class="stat-card__value"><?= $low_prod ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--red"><span class="material-symbols-outlined">block</span></div><div><p class="stat-card__label">Out of Stock</p><p class="stat-card__value"><?= $out_prod ?></p></div></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Movements by Type</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Type</th><th>Transactions</th><th>Total Qty</th></tr></thead>
            <tbody>
            <?php foreach ($movements_by_type as $m): ?>
            <tr><td><span class="badge <?= $type_badge[$m['movement_type']] ?? 'badge--neutral' ?>"><?= ucfirst($m['movement_type']) ?></span></td><td><?= e($m['cnt']) ?></td><td><?= e($m['total_qty']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($movements_by_type)): ?><tr><td colspan="3" style="text-align:center;">No movements yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Low / Out of Stock Items</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Product</th><th>Stock</th><th>Min</th></tr></thead>
            <tbody>
            <?php foreach ($low_stock as $p): ?>
            <tr><td><?= e($p['name']) ?></td><td style="font-weight:700;color:#dc2626;"><?= e($p['quantity_in_stock']) ?></td><td><?= e($p['min_stock_level']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($low_stock)): ?><tr><td colspan="3" style="text-align:center;color:var(--color-text-secondary);">All stock OK.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Recent Movements</h2><a href="<?= e(app_base_url()) ?>/store/stock-movements.php" class="btn btn--secondary">View All</a></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Date</th><th>Product</th><th>Type</th><th>Qty</th><th>By</th></tr></thead>
            <tbody>
            <?php foreach ($recent_movements as $m): ?>
            <tr><td style="font-size:.85rem;"><?= e(format_datetime($m['created_at'])) ?></td><td><?= e($m['product_name']) ?></td><td><span class="badge <?= $type_badge[$m['movement_type']] ?? 'badge--neutral' ?>"><?= ucfirst($m['movement_type']) ?></span></td><td><?= e($m['quantity']) ?></td><td><?= e($m['performed_name'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($recent_movements)): ?><tr><td colspan="5" style="text-align:center;">No movements.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
