<?php
/**
 * finance/reports.php — Financial Reports
 */
$page_title = 'Financial Reports';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('finance');

$pdo = get_db_connection();

$po_by_status = $pdo->query("SELECT status, COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS total FROM purchases GROUP BY status")->fetchAll();
$expense_by_cat = $pdo->query("SELECT category, COUNT(*) AS cnt, SUM(amount) AS total FROM expenses GROUP BY category ORDER BY total DESC")->fetchAll();
$monthly_exp = $pdo->query("SELECT DATE_FORMAT(expense_date,'%Y-%m') AS month, SUM(amount) AS total FROM expenses WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month DESC")->fetchAll();
$supplier_summary = $pdo->query("SELECT s.name, COUNT(pu.id) AS po_count, COALESCE(SUM(pu.total_amount),0) AS total FROM suppliers s LEFT JOIN purchases pu ON pu.supplier_id = s.id GROUP BY s.id ORDER BY total DESC LIMIT 10")->fetchAll();
$total_payments = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed'")->fetchColumn();
$total_expenses = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Financial Reports</h1></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="stats-grid" style="margin-bottom:var(--space-5);">
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--green"><span class="material-symbols-outlined">payments</span></div><div><p class="stat-card__label">Total Payments Made</p><p class="stat-card__value"><?= e(format_currency($total_payments)) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--red"><span class="material-symbols-outlined">receipt_long</span></div><div><p class="stat-card__label">Total Expenses</p><p class="stat-card__value"><?= e(format_currency($total_expenses)) ?></p></div></div>
</div>
<div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Purchase Orders by Status</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Status</th><th>Count</th><th>Total Value</th></tr></thead>
            <tbody>
            <?php foreach ($po_by_status as $row): ?>
            <tr><td><span class="badge <?= e(get_status_badge_class($row['status'])) ?>"><?= e(ucfirst(str_replace('_',' ',$row['status']))) ?></span></td><td><?= e($row['cnt']) ?></td><td style="font-weight:600;"><?= e(format_currency($row['total'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if(empty($po_by_status)): ?><tr><td colspan="3" style="text-align:center;">No data.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Expenses by Category</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Category</th><th>Count</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($expense_by_cat as $row): ?>
            <tr><td><?= e($row['category']) ?></td><td><?= e($row['cnt']) ?></td><td style="font-weight:600;"><?= e(format_currency($row['total'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if(empty($expense_by_cat)): ?><tr><td colspan="3" style="text-align:center;">No data.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);">
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Monthly Expenses (Last 6 Months)</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Month</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($monthly_exp as $row): ?>
            <tr><td><?= e($row['month']) ?></td><td style="font-weight:600;"><?= e(format_currency($row['total'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if(empty($monthly_exp)): ?><tr><td colspan="2" style="text-align:center;">No data.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Purchases by Supplier</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Supplier</th><th>POs</th><th>Total Value</th></tr></thead>
            <tbody>
            <?php foreach ($supplier_summary as $row): ?>
            <tr><td><?= e($row['name']) ?></td><td><?= e($row['po_count']) ?></td><td style="font-weight:600;"><?= e(format_currency($row['total'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if(empty($supplier_summary)): ?><tr><td colspan="3" style="text-align:center;">No data.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
