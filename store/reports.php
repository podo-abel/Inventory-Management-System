<?php
$page_title = 'Store Reports';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('store');
$pdo = get_db_connection();

$start_date = $_GET['date_from'] ?? date('Y-m-01');
$end_date = $_GET['date_to'] ?? date('Y-m-d');
$filter_type = $_GET['movement_type'] ?? 'All';
$filter_category = $_GET['category'] ?? 'All';

$all_categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Summary Cards
$total_products = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
$low_stock = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1 AND quantity_in_stock <= min_stock_level AND quantity_in_stock > 0")->fetchColumn();
$out_of_stock = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1 AND quantity_in_stock = 0")->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE created_at >= ? AND created_at <= ?");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$total_movements = (int)$stmt->fetchColumn();

// Chart: movements by type
$stmt = $pdo->prepare("SELECT movement_type, COUNT(*) as count FROM stock_movements WHERE created_at >= ? AND created_at <= ? GROUP BY movement_type");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$mv_labels = []; $mv_data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $mv_labels[] = ucfirst($row['movement_type']); $mv_data[] = (int)$row['count']; }

// Chart: top 10 products by stock value
$stmt = $pdo->query("SELECT name, (quantity_in_stock * unit_price) as stock_value FROM products WHERE is_active=1 AND quantity_in_stock > 0 ORDER BY stock_value DESC LIMIT 10");
$top_labels = []; $top_data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $top_labels[] = $row['name']; $top_data[] = (float)$row['stock_value']; }

// Table: movements summary
$stmt = $pdo->prepare("SELECT movement_type, COUNT(*) as count, SUM(quantity) as total_qty FROM stock_movements WHERE created_at >= ? AND created_at <= ? GROUP BY movement_type");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$movements_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
$type_badge = ['receive'=>'badge--success','issue'=>'badge--info','adjustment'=>'badge--warning','return'=>'badge--neutral'];

// Table: low stock items
$low_stock_items = $pdo->query("SELECT p.name, p.sku, COALESCE(c.name,'—') as category_name, p.quantity_in_stock, p.min_stock_level, p.unit FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active=1 AND p.quantity_in_stock <= p.min_stock_level ORDER BY p.quantity_in_stock ASC")->fetchAll(PDO::FETCH_ASSOC);

// Table: recent movements (filtered)
$sql = "SELECT sm.created_at, p.name as product_name, sm.movement_type, sm.quantity, sm.quantity_before, sm.quantity_after, COALESCE(u.full_name,'—') as performed_by FROM stock_movements sm LEFT JOIN products p ON sm.product_id = p.id LEFT JOIN users u ON sm.performed_by = u.id WHERE sm.created_at >= ? AND sm.created_at <= ?";
$params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
if ($filter_type !== 'All') { $sql .= " AND sm.movement_type = ?"; $params[] = $filter_type; }
if ($filter_category !== 'All') { $sql .= " AND p.category_id = ?"; $params[] = (int)$filter_category; }
$sql .= " ORDER BY sm.created_at DESC LIMIT 20";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$recent_movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header page-header--flex">
        <div>
            <h1 class="page-header__title">Store Reports</h1>
            <p class="page-header__subtitle">Inventory status, stock movements, and analysis.</p>
        </div>
        <div class="page-header__actions">
            <button class="btn btn--secondary" onclick="window.print()"><span class="material-symbols-outlined" style="font-size:16px;">print</span> Print</button>
            <a href="<?= e(app_base_url()) ?>/api/reports.php?type=stock_movements&format=csv&date_from=<?= urlencode($start_date) ?>&date_to=<?= urlencode($end_date) ?>" class="btn btn--primary"><span class="material-symbols-outlined" style="font-size:16px;">download</span> Export CSV</a>
        </div>
    </div>
    <?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>

    <div class="content-card" style="margin-bottom:var(--space-5);">
        <div class="content-card__body">
            <form method="GET" style="display:flex;gap:var(--space-3);align-items:flex-end;flex-wrap:wrap;">
                <div class="form-field" style="margin-bottom:0;"><label class="form-label">Date From</label><input type="date" name="date_from" class="form-input" value="<?= e($start_date) ?>"></div>
                <div class="form-field" style="margin-bottom:0;"><label class="form-label">Date To</label><input type="date" name="date_to" class="form-input" value="<?= e($end_date) ?>"></div>
                <div class="form-field" style="margin-bottom:0;"><label class="form-label">Movement Type</label>
                    <select name="movement_type" class="form-input">
                        <option value="All" <?= $filter_type === 'All' ? 'selected' : '' ?>>All</option>
                        <option value="receive" <?= $filter_type === 'receive' ? 'selected' : '' ?>>Receive</option>
                        <option value="issue" <?= $filter_type === 'issue' ? 'selected' : '' ?>>Issue</option>
                        <option value="adjustment" <?= $filter_type === 'adjustment' ? 'selected' : '' ?>>Adjustment</option>
                        <option value="return" <?= $filter_type === 'return' ? 'selected' : '' ?>>Return</option>
                    </select>
                </div>
                <div class="form-field" style="margin-bottom:0;"><label class="form-label">Category</label>
                    <select name="category" class="form-input">
                        <option value="All">All</option>
                        <?php foreach($all_categories as $cat): ?><option value="<?= e($cat['id']) ?>" <?= (string)$filter_category === (string)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn--primary">Filter</button>
            </form>
        </div>
    </div>

    <div class="stats-grid" style="margin-bottom:var(--space-5);">
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">inventory_2</span></div><div><p class="stat-card__label">Total Products</p><p class="stat-card__value"><?= e($total_products) ?></p></div></div>
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--yellow"><span class="material-symbols-outlined">warning</span></div><div><p class="stat-card__label">Low Stock</p><p class="stat-card__value"><?= e($low_stock) ?></p></div></div>
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--red"><span class="material-symbols-outlined">block</span></div><div><p class="stat-card__label">Out of Stock</p><p class="stat-card__value"><?= e($out_of_stock) ?></p></div></div>
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--green"><span class="material-symbols-outlined">swap_vert</span></div><div><p class="stat-card__label">Total Movements</p><p class="stat-card__value"><?= e($total_movements) ?></p></div></div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
        <div class="content-card"><div class="content-card__header"><h2 class="content-card__title">Stock Movements by Type</h2></div><div class="content-card__body" style="min-height:280px;"><canvas id="movementsTypeChart"></canvas></div></div>
        <div class="content-card"><div class="content-card__header"><h2 class="content-card__title">Top 10 Products by Value</h2></div><div class="content-card__body" style="min-height:280px;"><canvas id="topProductsChart"></canvas></div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
        <div class="content-card">
            <div class="content-card__header"><h2 class="content-card__title">Movements Summary</h2></div>
            <div class="content-card__body" style="padding:0;">
                <table class="table"><thead><tr><th>Type</th><th>Count</th><th>Total Qty</th></tr></thead><tbody>
                <?php foreach ($movements_summary as $m): ?>
                <tr><td><span class="badge <?= $type_badge[$m['movement_type']] ?? 'badge--neutral' ?>"><?= ucfirst(e($m['movement_type'])) ?></span></td><td><?= e($m['count']) ?></td><td><?= e($m['total_qty']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($movements_summary)): ?><tr><td colspan="3" style="text-align:center;">No movements for this period.</td></tr><?php endif; ?>
                </tbody></table>
            </div>
        </div>
        <div class="content-card">
            <div class="content-card__header"><h2 class="content-card__title">Low / Out of Stock Items</h2></div>
            <div class="content-card__body" style="padding:0;overflow-x:auto;">
                <table class="table"><thead><tr><th>Product</th><th>Stock</th><th>Min</th><th>Status</th></tr></thead><tbody>
                <?php foreach ($low_stock_items as $p): ?>
                <tr><td><?= e($p['name']) ?></td><td style="font-weight:700;color:<?= $p['quantity_in_stock'] <= 0 ? 'var(--color-error)' : 'inherit' ?>;"><?= e($p['quantity_in_stock']) ?> <?= e($p['unit']) ?></td><td><?= e($p['min_stock_level']) ?></td><td><?php if($p['quantity_in_stock']<=0): ?><span class="badge badge--error">Out of Stock</span><?php else: ?><span class="badge badge--warning">Low Stock</span><?php endif; ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($low_stock_items)): ?><tr><td colspan="4" style="text-align:center;color:var(--color-on-surface-variant);">All stock OK.</td></tr><?php endif; ?>
                </tbody></table>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card__header"><h2 class="content-card__title">Recent Stock Movements</h2><a href="<?= e(app_base_url()) ?>/store/stock-movements.php" class="btn btn--secondary">View All</a></div>
        <div class="content-card__body" style="padding:0;overflow-x:auto;">
            <table class="table"><thead><tr><th>Date</th><th>Product</th><th>Type</th><th>Qty</th><th>Change</th><th>By</th></tr></thead><tbody>
            <?php foreach ($recent_movements as $m): ?>
            <tr><td style="font-size:.85rem;"><?= e(format_datetime($m['created_at'])) ?></td><td><?= e($m['product_name']) ?></td><td><span class="badge <?= $type_badge[$m['movement_type']] ?? 'badge--neutral' ?>"><?= ucfirst(e($m['movement_type'])) ?></span></td><td><?= e($m['quantity']) ?></td><td><?= e($m['quantity_before']) ?> &rarr; <?= e($m['quantity_after']) ?></td><td><?= e($m['performed_by']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($recent_movements)): ?><tr><td colspan="6" style="text-align:center;">No movements found.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('movementsTypeChart'), {
        type: 'bar',
        data: { labels: <?= json_encode($mv_labels) ?>, datasets: [{ label: 'Count', data: <?= json_encode($mv_data) ?>, backgroundColor: ['#2e7d32','#0058be','#f59e0b','#6b7280'], borderRadius: 4 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
    new Chart(document.getElementById('topProductsChart'), {
        type: 'bar',
        data: { labels: <?= json_encode($top_labels) ?>, datasets: [{ label: 'Stock Value (ETB)', data: <?= json_encode($top_data) ?>, backgroundColor: '#00236f' }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
});
</script>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
