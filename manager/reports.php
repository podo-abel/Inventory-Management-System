<?php
$page_title = 'Manager Reports';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('manager');
$pdo = get_db_connection();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$status = $_GET['status'] ?? 'all';

// Summary
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed FROM requests WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$summary = $stmt->fetch();

// Chart: requests by status
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM requests WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status");
$stmt->execute([$start_date, $end_date]);
$status_counts = $stmt->fetchAll();
$chart_labels = []; $chart_data = [];
foreach ($status_counts as $r) { $chart_labels[] = ucfirst($r['status']); $chart_data[] = (int)$r['count']; }

// Chart: request trend
$trend = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') as month, COUNT(*) as count FROM requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month")->fetchAll();
$trend_labels = []; $trend_data = [];
foreach ($trend as $r) { $trend_labels[] = $r['month']; $trend_data[] = (int)$r['count']; }

// Employee summary
$stmt = $pdo->prepare("SELECT u.full_name, COUNT(*) as total, SUM(r.status='pending') as pending, SUM(r.status='approved') as approved, SUM(r.status='completed') as completed FROM requests r JOIN users u ON r.user_id=u.id WHERE DATE(r.created_at) BETWEEN ? AND ? GROUP BY u.id ORDER BY total DESC");
$stmt->execute([$start_date, $end_date]);
$employee_summary = $stmt->fetchAll();

// Low stock
$low_stock = $pdo->query("SELECT p.name, p.sku, c.name as category, p.quantity_in_stock, p.min_stock_level, p.unit FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.is_active=1 AND p.quantity_in_stock<=p.min_stock_level ORDER BY p.quantity_in_stock ASC")->fetchAll();

// Filtered requests
$params = [$start_date, $end_date];
$status_sql = '';
if ($status !== 'all') { $status_sql = ' AND r.status = ?'; $params[] = $status; }
$stmt = $pdo->prepare("SELECT r.*, u.full_name as employee, u2.full_name as reviewer, (SELECT COUNT(*) FROM request_items WHERE request_id=r.id) as items_count FROM requests r JOIN users u ON r.user_id=u.id LEFT JOIN users u2 ON r.reviewed_by=u2.id WHERE DATE(r.created_at) BETWEEN ? AND ?$status_sql ORDER BY r.created_at DESC LIMIT 30");
$stmt->execute($params);
$requests = $stmt->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header page-header--flex">
        <div>
            <h1 class="page-header__title">Manager Reports</h1>
            <p class="page-header__subtitle">Operational insights and request analytics.</p>
        </div>
        <div class="page-header__actions">
            <button class="btn btn--secondary" onclick="window.print()"><span class="material-symbols-outlined" style="font-size:16px;">print</span> Print</button>
            <a href="<?= e(app_base_url()) ?>/api/reports.php?type=requests&format=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&status=<?= urlencode($status) ?>" class="btn btn--primary"><span class="material-symbols-outlined" style="font-size:16px;">download</span> Export CSV</a>
        </div>
    </div>
    <?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>

    <div class="content-card" style="margin-bottom:var(--space-5);">
        <div class="content-card__body">
            <form method="GET" style="display:flex;gap:var(--space-3);align-items:flex-end;flex-wrap:wrap;">
                <div class="form-field" style="margin-bottom:0;"><label class="form-label">Date From</label><input type="date" name="start_date" class="form-input" value="<?= e($start_date) ?>"></div>
                <div class="form-field" style="margin-bottom:0;"><label class="form-label">Date To</label><input type="date" name="end_date" class="form-input" value="<?= e($end_date) ?>"></div>
                <div class="form-field" style="margin-bottom:0;"><label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="all" <?= $status==='all'?'selected':'' ?>>All</option>
                        <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending</option>
                        <option value="approved" <?= $status==='approved'?'selected':'' ?>>Approved</option>
                        <option value="rejected" <?= $status==='rejected'?'selected':'' ?>>Rejected</option>
                        <option value="completed" <?= $status==='completed'?'selected':'' ?>>Completed</option>
                        <option value="cancelled" <?= $status==='cancelled'?'selected':'' ?>>Cancelled</option>
                    </select>
                </div>
                <button type="submit" class="btn btn--primary">Filter</button>
            </form>
        </div>
    </div>

    <div class="stats-grid" style="margin-bottom:var(--space-5);">
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">assignment</span></div><div><p class="stat-card__label">Total Requests</p><p class="stat-card__value"><?= number_format($summary['total'] ?? 0) ?></p></div></div>
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--yellow"><span class="material-symbols-outlined">pending_actions</span></div><div><p class="stat-card__label">Pending</p><p class="stat-card__value"><?= number_format($summary['pending'] ?? 0) ?></p></div></div>
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--green"><span class="material-symbols-outlined">check_circle</span></div><div><p class="stat-card__label">Approved</p><p class="stat-card__value"><?= number_format($summary['approved'] ?? 0) ?></p></div></div>
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--red"><span class="material-symbols-outlined">cancel</span></div><div><p class="stat-card__label">Rejected</p><p class="stat-card__value"><?= number_format($summary['rejected'] ?? 0) ?></p></div></div>
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--purple"><span class="material-symbols-outlined">task_alt</span></div><div><p class="stat-card__label">Completed</p><p class="stat-card__value"><?= number_format($summary['completed'] ?? 0) ?></p></div></div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
        <div class="content-card"><div class="content-card__header"><h2 class="content-card__title">Requests by Status</h2></div><div class="content-card__body" style="min-height:280px;"><canvas id="statusChart"></canvas></div></div>
        <div class="content-card"><div class="content-card__header"><h2 class="content-card__title">Request Trend (6 Months)</h2></div><div class="content-card__body" style="min-height:280px;"><canvas id="trendChart"></canvas></div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
        <div class="content-card">
            <div class="content-card__header"><h2 class="content-card__title">Status Summary</h2></div>
            <div class="content-card__body" style="padding:0;">
                <table class="table"><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>
                <?php foreach ($status_counts as $r): ?><tr><td><span class="badge <?= get_status_badge_class($r['status']) ?>"><?= ucfirst(e($r['status'])) ?></span></td><td style="font-weight:600;"><?= number_format($r['count']) ?></td></tr><?php endforeach; ?>
                <?php if(empty($status_counts)): ?><tr><td colspan="2" style="text-align:center;">No data.</td></tr><?php endif; ?>
                </tbody></table>
            </div>
        </div>
        <div class="content-card">
            <div class="content-card__header"><h2 class="content-card__title">Employee Request Summary</h2></div>
            <div class="content-card__body" style="padding:0;overflow-x:auto;">
                <table class="table"><thead><tr><th>Employee</th><th>Total</th><th>Pending</th><th>Approved</th><th>Completed</th></tr></thead><tbody>
                <?php foreach ($employee_summary as $r): ?><tr><td><?= e($r['full_name']) ?></td><td><?= number_format($r['total']) ?></td><td><?= number_format($r['pending']) ?></td><td><?= number_format($r['approved']) ?></td><td><?= number_format($r['completed']) ?></td></tr><?php endforeach; ?>
                <?php if(empty($employee_summary)): ?><tr><td colspan="5" style="text-align:center;">No data.</td></tr><?php endif; ?>
                </tbody></table>
            </div>
        </div>
    </div>

    <div class="content-card" style="margin-bottom:var(--space-5);">
        <div class="content-card__header"><h2 class="content-card__title">Low Stock Alert</h2></div>
        <div class="content-card__body" style="padding:0;overflow-x:auto;">
            <table class="table"><thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Current Stock</th><th>Min Level</th><th>Unit</th></tr></thead><tbody>
            <?php foreach ($low_stock as $p): ?>
            <tr style="<?= $p['quantity_in_stock'] <= 0 ? 'background:#fff5f5;' : '' ?>">
                <td><?= e($p['name']) ?></td><td><code><?= e($p['sku']) ?></code></td><td><?= e($p['category'] ?? '—') ?></td>
                <td style="font-weight:700;color:#dc2626;"><?= e($p['quantity_in_stock']) ?></td><td><?= e($p['min_stock_level']) ?></td><td><?= e($p['unit']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($low_stock)): ?><tr><td colspan="6" style="text-align:center;color:var(--color-on-surface-variant);">All products are sufficiently stocked.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card__header"><h2 class="content-card__title">Filtered Requests</h2></div>
        <div class="content-card__body" style="padding:0;overflow-x:auto;">
            <table class="table"><thead><tr><th>Reference</th><th>Employee</th><th>Status</th><th>Date</th><th>Items</th><th>Reviewed By</th></tr></thead><tbody>
            <?php foreach ($requests as $r): ?>
            <tr><td><code><?= e($r['reference_no']) ?></code></td><td><?= e($r['employee']) ?></td><td><span class="badge <?= get_status_badge_class($r['status']) ?>"><?= ucfirst(e($r['status'])) ?></span></td><td style="font-size:.85rem;"><?= format_date($r['created_at']) ?></td><td><?= number_format($r['items_count']) ?></td><td><?= e($r['reviewer'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
            <?php if(empty($requests)): ?><tr><td colspan="6" style="text-align:center;">No requests found.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: { labels: <?= json_encode($chart_labels) ?>, datasets: [{ label: 'Requests', data: <?= json_encode($chart_data) ?>, backgroundColor: '#00236f', borderRadius: 4 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: { labels: <?= json_encode($trend_labels) ?>, datasets: [{ label: 'Requests', data: <?= json_encode($trend_data) ?>, borderColor: '#0058be', backgroundColor: 'rgba(0,88,190,0.1)', tension: 0.4, fill: true }] },
        options: { responsive: true, maintainAspectRatio: false }
    });
});
</script>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
