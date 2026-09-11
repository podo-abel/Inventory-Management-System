<?php
/**
 * finance/dashboard.php — Finance Dashboard
 */
$page_title = 'Dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('finance');

$pdo = get_db_connection();
$s = $pdo->query("SELECT COUNT(*) FROM suppliers WHERE is_active=1"); $supplier_count = (int)$s->fetchColumn();
$s = $pdo->query("SELECT COUNT(*) FROM purchases WHERE status NOT IN ('received','cancelled')"); $open_purchases = (int)$s->fetchColumn();

$month_start = date('Y-m-01');
$s = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed' AND payment_date >= ?"); $s->execute([$month_start]); $paid_month = (float)$s->fetchColumn();
$s = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= ?"); $s->execute([$month_start]); $expense_month = (float)$s->fetchColumn();

$recent_purchases = $pdo->query("SELECT pu.id, pu.reference_no, pu.status, pu.total_amount, pu.created_at, s.name AS supplier_name FROM purchases pu LEFT JOIN suppliers s ON s.id = pu.supplier_id ORDER BY pu.created_at DESC LIMIT 5")->fetchAll();
$recent_expenses  = $pdo->query("SELECT ex.id, ex.category, ex.description, ex.amount, ex.expense_date FROM expenses ex ORDER BY ex.expense_date DESC LIMIT 5")->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Finance Dashboard</h1><p class="page-header__subtitle"><?= e(date('l, F j, Y')) ?></p></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="stats-grid">
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">local_shipping</span></div><div><p class="stat-card__label">Active Suppliers</p><p class="stat-card__value"><?= e($supplier_count) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--yellow"><span class="material-symbols-outlined">shopping_cart</span></div><div><p class="stat-card__label">Open Purchase Orders</p><p class="stat-card__value"><?= e($open_purchases) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--green"><span class="material-symbols-outlined">payments</span></div><div><p class="stat-card__label">Payments This Month</p><p class="stat-card__value"><?= e(format_currency($paid_month)) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--red"><span class="material-symbols-outlined">receipt_long</span></div><div><p class="stat-card__label">Expenses This Month</p><p class="stat-card__value"><?= e(format_currency($expense_month)) ?></p></div></div>
</div>
<div class="form-grid" style="display:grid;grid-template-columns:3fr 2fr;gap:var(--space-5);">
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Recent Purchase Orders</h2><a href="<?= e(app_base_url()) ?>/finance/purchases.php" class="btn btn--secondary">View All</a></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Reference</th><th>Supplier</th><th>Status</th><th>Amount</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recent_purchases as $po): ?>
            <tr>
                <td><code><?= e($po['reference_no']) ?></code></td>
                <td><?= e($po['supplier_name'] ?? '—') ?></td>
                <td><span class="badge <?= e(get_status_badge_class($po['status'])) ?>"><?= e(ucfirst(str_replace('_',' ',$po['status']))) ?></span></td>
                <td><?= e(format_currency($po['total_amount'])) ?></td>
                <td><?= e(format_date($po['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($recent_purchases)): ?><tr><td colspan="5" style="text-align:center;">No purchases yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Recent Expenses</h2><a href="<?= e(app_base_url()) ?>/finance/expenses.php" class="btn btn--secondary">View All</a></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Date</th><th>Category</th><th>Amount</th></tr></thead>
            <tbody>
            <?php foreach ($recent_expenses as $ex): ?>
            <tr><td><?= e(format_date($ex['expense_date'])) ?></td><td><?= e($ex['category']) ?></td><td style="font-weight:600;"><?= e(format_currency($ex['amount'])) ?></td></tr>
            <?php endforeach; ?>
            <?php if(empty($recent_expenses)): ?><tr><td colspan="3" style="text-align:center;">No expenses.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
