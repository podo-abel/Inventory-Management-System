<?php
$page_title = 'Financial Reports';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('finance');
$pdo = get_db_connection();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Summary
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM purchases WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelled'");
$stmt->execute([$start_date, $end_date]);
$purchases_total = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed' AND DATE(payment_date) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$payments_total = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$expenses_total = (float)$stmt->fetchColumn();

$outstanding = $purchases_total - $payments_total;

// Chart: monthly spending (payments + expenses for last 6 months)
$spending = $pdo->query("
    SELECT month, SUM(amount) as total FROM (
        SELECT DATE_FORMAT(payment_date,'%Y-%m') as month, amount FROM payments WHERE status='completed' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        UNION ALL
        SELECT DATE_FORMAT(expense_date,'%Y-%m') as month, amount FROM expenses WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    ) combined GROUP BY month ORDER BY month
")->fetchAll();
$spend_labels = [];
$spend_data = [];
foreach ($spending as $r) {
    $spend_labels[] = $r['month'];
    $spend_data[] = (float)$r['total'];
}

// Chart: expenses by category
$stmt = $pdo->prepare("SELECT category, COUNT(*) as cnt, SUM(amount) as total FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ? GROUP BY category ORDER BY total DESC");
$stmt->execute([$start_date, $end_date]);
$exp_by_cat = $stmt->fetchAll();
$cat_labels = [];
$cat_data = [];
foreach ($exp_by_cat as $r) {
    $cat_labels[] = $r['category'];
    $cat_data[] = (float)$r['total'];
}

// PO by status
$stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total FROM purchases WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status");
$stmt->execute([$start_date, $end_date]);
$po_by_status = $stmt->fetchAll();

// Monthly expenses (last 6 months)
$monthly_exp = $pdo->query("SELECT DATE_FORMAT(expense_date,'%Y-%m') AS month, SUM(amount) AS total FROM expenses WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month DESC")->fetchAll();

// Supplier summary
$stmt = $pdo->prepare("SELECT s.name, COUNT(pu.id) as po_count, COALESCE(SUM(pu.total_amount),0) as total FROM suppliers s LEFT JOIN purchases pu ON pu.supplier_id=s.id AND DATE(pu.created_at) BETWEEN ? AND ? GROUP BY s.id ORDER BY total DESC LIMIT 10");
$stmt->execute([$start_date, $end_date]);
$supplier_summary = $stmt->fetchAll();

// Recent payments
$stmt = $pdo->prepare("SELECT p.reference_no, pu.reference_no as po_ref, p.amount, p.payment_method, p.status, p.payment_date FROM payments p JOIN purchases pu ON p.purchase_id=pu.id WHERE DATE(p.payment_date) BETWEEN ? AND ? ORDER BY p.payment_date DESC LIMIT 20");
$stmt->execute([$start_date, $end_date]);
$recent_payments = $stmt->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header page-header--flex">
        <div>
            <h1 class="page-header__title">Financial Reports</h1>
            <p class="page-header__subtitle">Purchase orders, payments, and expense analysis.</p>
        </div>
        <div class="page-header__actions">
            <button class="btn btn--secondary" onclick="window.print()"><span class="material-symbols-outlined" style="font-size:16px;">print</span> Print</button>
            <a href="<?= e(app_base_url()) ?>/api/reports.php?type=purchases&format=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn--primary"><span class="material-symbols-outlined" style="font-size:16px;">download</span> Purchases</a>
            <a href="<?= e(app_base_url()) ?>/api/reports.php?type=payments&format=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn--primary"><span class="material-symbols-outlined" style="font-size:16px;">download</span> Payments</a>
            <a href="<?= e(app_base_url()) ?>/api/reports.php?type=expenses&format=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn--primary"><span class="material-symbols-outlined" style="font-size:16px;">download</span> Expenses</a>
        </div>
    </div>
    <?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>

    <div class="content-card" style="margin-bottom:var(--space-5);">
        <div class="content-card__body">
            <form method="GET" style="display:flex;gap:var(--space-3);align-items:flex-end;flex-wrap:wrap;">
                <div class="form-field" style="margin-bottom:0;"><label class="form-label">Date From</label><input type="date" name="start_date" class="form-input" value="<?= e($start_date) ?>"></div>
                <div class="form-field" style="margin-bottom:0;"><label class="form-label">Date To</label><input type="date" name="end_date" class="form-input" value="<?= e($end_date) ?>"></div>
                <button type="submit" class="btn btn--primary">Filter</button>
            </form>
        </div>
    </div>

    <div class="stats-grid" style="margin-bottom:var(--space-5);">
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">shopping_cart</span></div><div><p class="stat-card__label">Total Purchases</p><p class="stat-card__value"><?= e(format_currency($purchases_total)) ?></p></div></div>
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--green"><span class="material-symbols-outlined">payments</span></div><div><p class="stat-card__label">Total Payments</p><p class="stat-card__value"><?= e(format_currency($payments_total)) ?></p></div></div>
        <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--purple"><span class="material-symbols-outlined">receipt_long</span></div><div><p class="stat-card__label">Total Expenses</p><p class="stat-card__value"><?= e(format_currency($expenses_total)) ?></p></div></div>
        <div class="stat-card" style="border-left:4px solid var(--color-error);"><div class="stat-card__icon-wrap stat-card__icon-wrap--red"><span class="material-symbols-outlined">account_balance</span></div><div><p class="stat-card__label">Outstanding Balance</p><p class="stat-card__value"><?= e(format_currency($outstanding)) ?></p></div></div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
        <div class="content-card"><div class="content-card__header"><h2 class="content-card__title">Monthly Spending (6 Months)</h2></div><div class="content-card__body" style="min-height:280px;"><canvas id="spendingChart"></canvas></div></div>
        <div class="content-card"><div class="content-card__header"><h2 class="content-card__title">Expenses by Category</h2></div><div class="content-card__body" style="min-height:280px;"><canvas id="categoryChart"></canvas></div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
        <div class="content-card">
            <div class="content-card__header"><h2 class="content-card__title">Purchase Orders by Status</h2></div>
            <div class="content-card__body" style="padding:0;">
                <table class="table"><thead><tr><th>Status</th><th>Count</th><th>Total Value</th></tr></thead><tbody>
                <?php foreach ($po_by_status as $r): ?><tr><td><span class="badge <?= e(get_status_badge_class($r['status'])) ?>"><?= e(ucfirst(str_replace('_',' ',$r['status']))) ?></span></td><td><?= e($r['cnt']) ?></td><td style="font-weight:600;"><?= e(format_currency($r['total'])) ?></td></tr><?php endforeach; ?>
                <?php if(empty($po_by_status)): ?><tr><td colspan="3" style="text-align:center;">No data.</td></tr><?php endif; ?>
                </tbody></table>
            </div>
        </div>
        <div class="content-card">
            <div class="content-card__header"><h2 class="content-card__title">Expenses by Category</h2></div>
            <div class="content-card__body" style="padding:0;">
                <table class="table"><thead><tr><th>Category</th><th>Count</th><th>Total</th></tr></thead><tbody>
                <?php foreach ($exp_by_cat as $r): ?><tr><td><?= e($r['category']) ?></td><td><?= e($r['cnt']) ?></td><td style="font-weight:600;"><?= e(format_currency($r['total'])) ?></td></tr><?php endforeach; ?>
                <?php if(empty($exp_by_cat)): ?><tr><td colspan="3" style="text-align:center;">No data.</td></tr><?php endif; ?>
                </tbody></table>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
        <div class="content-card">
            <div class="content-card__header"><h2 class="content-card__title">Purchases by Supplier</h2></div>
            <div class="content-card__body" style="padding:0;">
                <table class="table"><thead><tr><th>Supplier</th><th>POs</th><th>Total Value</th></tr></thead><tbody>
                <?php foreach ($supplier_summary as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['po_count']) ?></td><td style="font-weight:600;"><?= e(format_currency($r['total'])) ?></td></tr><?php endforeach; ?>
                <?php if(empty($supplier_summary)): ?><tr><td colspan="3" style="text-align:center;">No data.</td></tr><?php endif; ?>
                </tbody></table>
            </div>
        </div>
        <div class="content-card">
            <div class="content-card__header"><h2 class="content-card__title">Monthly Expenses</h2></div>
            <div class="content-card__body" style="padding:0;">
                <table class="table"><thead><tr><th>Month</th><th>Total</th></tr></thead><tbody>
                <?php foreach ($monthly_exp as $r): ?><tr><td><?= e($r['month']) ?></td><td style="font-weight:600;"><?= e(format_currency($r['total'])) ?></td></tr><?php endforeach; ?>
                <?php if(empty($monthly_exp)): ?><tr><td colspan="2" style="text-align:center;">No data.</td></tr><?php endif; ?>
                </tbody></table>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card__header"><h2 class="content-card__title">Recent Payments</h2></div>
        <div class="content-card__body" style="padding:0;overflow-x:auto;">
            <table class="table"><thead><tr><th>Reference</th><th>Purchase Ref</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead><tbody>
            <?php foreach ($recent_payments as $r): ?>
            <tr><td><?= e($r['reference_no'] ?? '—') ?></td><td><code><?= e($r['po_ref']) ?></code></td><td style="font-weight:600;"><?= e(format_currency($r['amount'])) ?></td><td><?= e(ucfirst(str_replace('_',' ',$r['payment_method']))) ?></td><td><span class="badge <?= get_status_badge_class($r['status']) ?>"><?= ucfirst(e($r['status'])) ?></span></td><td><?= format_date($r['payment_date']) ?></td></tr>
            <?php endforeach; ?>
            <?php if(empty($recent_payments)): ?><tr><td colspan="6" style="text-align:center;">No recent payments.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('spendingChart'), {
        type: 'line',
        data: { labels: <?= json_encode($spend_labels) ?>, datasets: [{ label: 'Spending (ETB)', data: <?= json_encode($spend_data) ?>, borderColor: '#00236f', backgroundColor: 'rgba(0,35,111,0.1)', fill: true, tension: 0.4 }] },
        options: { responsive: true, maintainAspectRatio: false }
    });
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: { labels: <?= json_encode($cat_labels) ?>, datasets: [{ data: <?= json_encode($cat_data) ?>, backgroundColor: ['#00236f','#0058be','#ba1a1a','#4b1c00','#6b7280','#2e7d32'], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom' } } }
    });
});
</script>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
