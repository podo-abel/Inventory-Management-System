<?php
/**
 * manager/reports.php — Manager Reports
 */
$page_title = 'Reports';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('manager');

$pdo = get_db_connection();

$req_by_status = $pdo->query("SELECT status, COUNT(*) AS cnt FROM requests GROUP BY status ORDER BY cnt DESC")->fetchAll();
$low_stock     = $pdo->query("SELECT p.name, p.sku, p.unit, p.quantity_in_stock, p.min_stock_level, c.name AS category FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.is_active=1 AND p.quantity_in_stock <= p.min_stock_level ORDER BY p.quantity_in_stock ASC")->fetchAll();
$emp_requests  = $pdo->query("SELECT u.full_name, COUNT(r.id) AS total, SUM(r.status='pending') AS pending_cnt, SUM(r.status='approved') AS approved_cnt, SUM(r.status='completed') AS completed_cnt FROM requests r JOIN users u ON u.id = r.user_id GROUP BY r.user_id ORDER BY total DESC LIMIT 20")->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Reports</h1><p class="page-header__subtitle">Operational summary and insights.</p></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Requests by Status</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Status</th><th>Count</th></tr></thead>
            <tbody>
            <?php foreach ($req_by_status as $row): ?>
            <tr><td><span class="badge <?= e(get_status_badge_class($row['status'])) ?>"><?= e(ucfirst($row['status'])) ?></span></td><td style="font-weight:600;"><?= e($row['cnt']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Employee Request Summary</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Employee</th><th>Total</th><th>Pending</th><th>Approved</th><th>Completed</th></tr></thead>
            <tbody>
            <?php foreach ($emp_requests as $row): ?>
            <tr><td><?= e($row['full_name']) ?></td><td><?= e($row['total']) ?></td><td><?= e($row['pending_cnt']) ?></td><td><?= e($row['approved_cnt']) ?></td><td><?= e($row['completed_cnt']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($emp_requests)): ?><tr><td colspan="5" style="text-align:center;">No data.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Low Stock Alert</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Current Stock</th><th>Min Level</th><th>Unit</th></tr></thead>
            <tbody>
            <?php foreach ($low_stock as $p): ?>
            <tr style="<?= $p['quantity_in_stock'] <= 0 ? 'background:#fff5f5;' : '' ?>">
                <td><?= e($p['name']) ?></td>
                <td><code><?= e($p['sku']) ?></code></td>
                <td><?= e($p['category'] ?? '—') ?></td>
                <td style="font-weight:700;color:#dc2626;"><?= e($p['quantity_in_stock']) ?></td>
                <td><?= e($p['min_stock_level']) ?></td>
                <td><?= e($p['unit']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($low_stock)): ?><tr><td colspan="6" style="text-align:center;color:var(--color-text-secondary);">All products are sufficiently stocked.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
