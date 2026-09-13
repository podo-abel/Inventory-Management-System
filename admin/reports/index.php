<?php
$page_title = 'Reports';
require_once dirname(__DIR__, 2) . '/includes/header.php';

if (get_current_role() !== 'admin') {
    header('Location: ' . app_base_url() . '/index.php');
    exit;
}

$pdo = get_db_connection();

$start_date = $_GET['date_from'] ?? date('Y-m-01');
$end_date = $_GET['date_to'] ?? date('Y-m-d');

// Summary Cards
$total_products = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$total_stock_value = (float)$pdo->query("SELECT COALESCE(SUM(quantity_in_stock * unit_price),0) FROM products WHERE is_active = 1")->fetchColumn();
$low_stock_items = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1 AND quantity_in_stock <= min_stock_level")->fetchColumn();
$total_requests = (int)$pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();

// Monthly stock movements (receive vs issue) last 6 months
$months_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months_data[$month] = ['receive' => 0, 'issue' => 0];
}
$stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, movement_type, COUNT(*) as count 
                       FROM stock_movements 
                       WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                       GROUP BY month, movement_type");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (isset($months_data[$row['month']][$row['movement_type']])) {
        $months_data[$row['month']][$row['movement_type']] = (int)$row['count'];
    }
}
$chart_months = array_keys($months_data);
$chart_receive = array_values(array_map(fn($m) => $m['receive'], $months_data));
$chart_issue = array_values(array_map(fn($m) => $m['issue'], $months_data));

// Products by category
$stmt = $pdo->query("SELECT c.name, COUNT(p.id) as count FROM categories c LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1 GROUP BY c.id ORDER BY count DESC");
$category_labels = [];
$category_counts = [];
$categories_data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $category_labels[] = $row['name'];
    $category_counts[] = (int)$row['count'];
    $categories_data[] = $row;
}

// Requests by Status
$requests_by_status = $pdo->query("SELECT status, COUNT(*) as count FROM requests GROUP BY status ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);

// Recent Requests filtered by date
$stmt = $pdo->prepare("SELECT r.reference_no, u.full_name as employee_name, r.status, r.created_at 
    FROM requests r LEFT JOIN users u ON r.user_id = u.id
    WHERE r.created_at >= ? AND r.created_at <= ?
    ORDER BY r.created_at DESC LIMIT 20");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$recent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header page-header--flex">
        <div>
            <h1 class="page-header__title">Admin Reports</h1>
            <p class="page-header__subtitle">System-wide analytics and reporting.</p>
        </div>
        <div class="page-header__actions">
            <button class="btn btn--secondary" onclick="window.print()"><span class="material-symbols-outlined" style="font-size:16px;">print</span> Print</button>
            <a href="<?= e(app_base_url()) ?>/api/reports.php?type=inventory&format=csv&date_from=<?= urlencode($start_date) ?>&date_to=<?= urlencode($end_date) ?>" class="btn btn--primary"><span class="material-symbols-outlined" style="font-size:16px;">download</span> Export CSV</a>
        </div>
    </div>

    <?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>

    <div class="content-card" style="margin-bottom:var(--space-5);">
        <div class="content-card__body">
            <form method="GET" style="display:flex;gap:var(--space-3);align-items:flex-end;flex-wrap:wrap;">
                <div class="form-field" style="margin-bottom:0;">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-input" value="<?= e($start_date) ?>">
                </div>
                <div class="form-field" style="margin-bottom:0;">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-input" value="<?= e($end_date) ?>">
                </div>
                <button type="submit" class="btn btn--primary">Filter</button>
            </form>
        </div>
    </div>

    <div class="stats-grid" style="margin-bottom:var(--space-5);">
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">inventory_2</span></div><div><p class="stat-card__label">Total Products</p><p class="stat-card__value"><?= e(number_format($total_products)) ?></p></div></div>
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--green"><span class="material-symbols-outlined">payments</span></div><div><p class="stat-card__label">Total Stock Value</p><p class="stat-card__value"><?= e(format_currency($total_stock_value)) ?></p></div></div>
        <div class="stat-card" style="border-left:4px solid var(--color-error);"><div class="stat-card__icon-wrap stat-card__icon-wrap--red"><span class="material-symbols-outlined">warning</span></div><div><p class="stat-card__label">Low Stock Items</p><p class="stat-card__value"><?= e(number_format($low_stock_items)) ?></p></div></div>
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--purple"><span class="material-symbols-outlined">assignment</span></div><div><p class="stat-card__label">Total Requests</p><p class="stat-card__value"><?= e(number_format($total_requests)) ?></p></div></div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
        <div class="content-card">
            <div class="content-card__header"><h2 class="content-card__title">Monthly Stock Movements</h2></div>
            <div class="content-card__body" style="min-height:280px;"><canvas id="movementsChart"></canvas></div>
        </div>
        <div class="content-card">
            <div class="content-card__header"><h2 class="content-card__title">Products by Category</h2></div>
            <div class="content-card__body" style="min-height:280px;"><canvas id="categoryChart"></canvas></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
        <div class="content-card">
            <div class="content-card__header"><h2 class="content-card__title">Requests by Status</h2></div>
            <div class="content-card__body" style="padding:0;">
                <table class="table"><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>
                <?php foreach ($requests_by_status as $row): ?>
                <tr><td><span class="badge <?= e(get_status_badge_class($row['status'])) ?>"><?= e(ucfirst(str_replace('_',' ',$row['status']))) ?></span></td><td style="font-weight:600;"><?= e($row['count']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($requests_by_status)): ?><tr><td colspan="2" style="text-align:center;">No data.</td></tr><?php endif; ?>
                </tbody></table>
            </div>
        </div>
        <div class="content-card">
            <div class="content-card__header"><h2 class="content-card__title">Products by Category</h2></div>
            <div class="content-card__body" style="padding:0;">
                <table class="table"><thead><tr><th>Category</th><th>Count</th></tr></thead><tbody>
                <?php foreach ($categories_data as $row): ?>
                <tr><td><?= e($row['name']) ?></td><td style="font-weight:600;"><?= e($row['count']) ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card__header"><h2 class="content-card__title">Recent Requests</h2></div>
        <div class="content-card__body" style="padding:0;overflow-x:auto;">
            <table class="table"><thead><tr><th>Reference</th><th>Employee</th><th>Status</th><th>Date</th></tr></thead><tbody>
            <?php foreach ($recent_requests as $req): ?>
            <tr>
                <td><code><?= e($req['reference_no']) ?></code></td>
                <td><?= e($req['employee_name'] ?? '—') ?></td>
                <td><span class="badge <?= e(get_status_badge_class($req['status'])) ?>"><?= e(ucfirst($req['status'])) ?></span></td>
                <td style="font-size:.85rem;color:var(--color-on-surface-variant);"><?= e(format_datetime($req['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recent_requests)): ?><tr><td colspan="4" style="text-align:center;">No requests found for this period.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const primaryColor = '#00236f';
    const secondaryColor = '#0058be';

    new Chart(document.getElementById('movementsChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_months) ?>,
            datasets: [
                { label: 'Received', data: <?= json_encode($chart_receive) ?>, borderColor: primaryColor, backgroundColor: 'rgba(0,35,111,0.1)', tension: 0.4, fill: true },
                { label: 'Issued', data: <?= json_encode($chart_issue) ?>, borderColor: secondaryColor, borderDash: [5,5], tension: 0.4, fill: false }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
    });

    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($category_labels) ?>,
            datasets: [{ data: <?= json_encode($category_counts) ?>, backgroundColor: ['#00236f','#0058be','#4b1c00','#ba1a1a','#dad9e1','#5c5c5c','#2e7d32','#f9a825','#1565c0','#6a1b9a'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12 } } } }
    });
});
</script>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
