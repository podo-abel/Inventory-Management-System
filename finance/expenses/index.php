<?php
/**
 * finance/expenses/index.php — Expenses list
 */
$page_title = 'Expenses';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('finance');

$pdo = get_db_connection();
$page = max(1, sanitize_int($_GET['page'] ?? 1)); $per = 25;
$cnt = $pdo->query('SELECT COUNT(*) FROM expenses'); $total = (int)$cnt->fetchColumn();
$pg = paginate($total, $per, $page);

$stmt = $pdo->prepare('SELECT ex.*, u.full_name AS recorder FROM expenses ex LEFT JOIN users u ON u.id = ex.recorded_by ORDER BY ex.expense_date DESC, ex.created_at DESC LIMIT :lim OFFSET :off');
$stmt->bindValue(':lim', $per, PDO::PARAM_INT);
$stmt->bindValue(':off', $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$expenses = $stmt->fetchAll();

$total_amount = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM expenses')->fetchColumn();
$this_month = (float)$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= ?")->execute([date('Y-m-01')]) ? $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= ?")->fetchColumn() : 0;
$s = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= ?"); $s->execute([date('Y-m-01')]); $this_month = (float)$s->fetchColumn();
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Expenses</h1></div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<div class="stats-grid" style="margin-bottom:var(--space-5);">
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">receipt_long</span></div><div><p class="stat-card__label">Total Expenses</p><p class="stat-card__value"><?= e(format_currency($total_amount)) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--yellow"><span class="material-symbols-outlined">calendar_month</span></div><div><p class="stat-card__label">This Month</p><p class="stat-card__value"><?= e(format_currency($this_month)) ?></p></div></div>
</div>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">All Expenses (<?= $total ?>)</h2><a href="<?= e(app_base_url()) ?>/finance/expenses/create.php" class="btn btn--primary"><span class="material-symbols-outlined">add</span> Add Expense</a></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Recorded By</th></tr></thead>
            <tbody>
            <?php foreach ($expenses as $ex): ?>
            <tr>
                <td><?= e(format_date($ex['expense_date'])) ?></td>
                <td><span class="badge badge--info"><?= e($ex['category']) ?></span></td>
                <td><?= e($ex['description'] ?? '—') ?></td>
                <td style="font-weight:600;"><?= e(format_currency($ex['amount'])) ?></td>
                <td style="font-size:.85rem;color:var(--color-text-secondary);"><?= e($ex['recorder'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($expenses)): ?><tr><td colspan="5" style="text-align:center;">No expenses recorded.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
