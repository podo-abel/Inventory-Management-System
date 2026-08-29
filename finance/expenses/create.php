<?php
/**
 * finance/expenses/create.php — Add an expense
 */
$page_title = 'Add Expense';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('finance');

$uid = (int)$_SESSION['user_id'];
$pdo = get_db_connection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Session expired.'; }
    else {
        $category    = sanitize_string($_POST['category'] ?? '');
        $description = sanitize_string($_POST['description'] ?? '');
        $amount      = (float)($_POST['amount'] ?? 0);
        $date        = sanitize_string($_POST['expense_date'] ?? '');

        if (empty($category))    $errors[] = 'Category is required.';
        if ($amount <= 0)        $errors[] = 'Amount must be greater than 0.';
        if (empty($date))        $errors[] = 'Date is required.';

        if (empty($errors)) {
            $pdo->prepare('INSERT INTO expenses (category, description, amount, expense_date, recorded_by) VALUES (?,?,?,?,?)')->execute([$category, $description ?: null, $amount, $date, $uid]);
            $eid = (int)$pdo->lastInsertId();
            log_activity($uid, 'record_expense', "Recorded expense: $category — " . format_currency($amount), 'expense', $eid);
            flash_message('success', 'Expense recorded.');
            header('Location: ' . app_base_url() . '/finance/expenses/index.php'); exit;
        }
    }
}
$cats = ['Office', 'Travel', 'Utilities', 'Maintenance', 'Marketing', 'Other'];
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Add Expense</h1></div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if(!empty($errors)): ?><div class="alert alert--error" style="margin-bottom:var(--space-4);"><?php foreach($errors as $e_): ?><p style="margin:.2rem 0;"><?= e($e_) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="content-card" style="max-width:520px;">
    <div class="content-card__header"><h2 class="content-card__title">Expense Details</h2></div>
    <div class="content-card__body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div class="form-group"><label class="form-label">Category *</label>
                <select class="form-input" name="category" required>
                    <?php foreach ($cats as $cat): ?><option value="<?= $cat ?>" <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Description</label><input class="form-input" name="description" value="<?= e($_POST['description'] ?? '') ?>" placeholder="Brief description…"></div>
            <div class="form-group"><label class="form-label">Amount *</label><input class="form-input" type="number" name="amount" step="0.01" min="0.01" value="<?= e($_POST['amount'] ?? '') ?>" required style="max-width:160px;"></div>
            <div class="form-group"><label class="form-label">Date *</label><input class="form-input" type="date" name="expense_date" value="<?= e($_POST['expense_date'] ?? date('Y-m-d')) ?>" required style="max-width:180px;"></div>
            <div style="display:flex;gap:var(--space-3);"><button type="submit" class="btn btn--primary">Record Expense</button><a href="<?= e(app_base_url()) ?>/finance/expenses/index.php" class="btn btn--secondary">Cancel</a></div>
        </form>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
