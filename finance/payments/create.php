<?php
/**
 * finance/payments/create.php — Record a payment
 */
$page_title = 'Record Payment';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('finance');

$uid = (int)$_SESSION['user_id'];
$pdo = get_db_connection();
$errors = [];

$purchases = $pdo->query("SELECT pu.id, pu.reference_no, pu.total_amount, s.name AS supplier_name FROM purchases pu LEFT JOIN suppliers s ON s.id = pu.supplier_id WHERE pu.status NOT IN ('received','cancelled') ORDER BY pu.created_at DESC")->fetchAll();
$prefill_po = sanitize_int($_GET['purchase_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Session expired.'; }
    else {
        $purchase_id    = sanitize_int($_POST['purchase_id'] ?? 0);
        $amount         = (float)($_POST['amount'] ?? 0);
        $method         = sanitize_string($_POST['payment_method'] ?? '');
        $ref_no         = sanitize_string($_POST['reference_no'] ?? '');
        $payment_date   = sanitize_string($_POST['payment_date'] ?? '');
        $notes          = sanitize_string($_POST['notes'] ?? '');

        if ($purchase_id <= 0)   $errors[] = 'Select a purchase order.';
        if ($amount <= 0)        $errors[] = 'Amount must be greater than 0.';
        if (empty($method))      $errors[] = 'Payment method is required.';
        if (empty($payment_date)) $errors[] = 'Payment date is required.';

        if (empty($errors)) {
            $pdo->prepare('INSERT INTO payments (purchase_id, reference_no, amount, payment_method, status, payment_date, notes, recorded_by) VALUES (?,?,?,?,?,?,?,?)')->execute([$purchase_id, $ref_no ?: null, $amount, $method, 'completed', $payment_date, $notes ?: null, $uid]);
            $pay_id = (int)$pdo->lastInsertId();
            log_activity($uid, 'record_payment', "Recorded payment of " . format_currency($amount) . " for PO #$purchase_id.", 'payment', $pay_id);
            
            $po_info_stmt = $pdo->prepare("SELECT reference_no FROM purchases WHERE id = ?");
            $po_info_stmt->execute([$purchase_id]);
            $po_ref = $po_info_stmt->fetchColumn() ?: "PO #$purchase_id";
            send_notification_to_role('manager', 'Payment Recorded', "Payment of " . format_currency($amount) . " recorded for $po_ref.", app_base_url() . '/finance/purchases/view.php?id=' . $purchase_id);
            send_notification_to_role('store', 'Payment Processed for PO', "Payment recorded for $po_ref. Incoming stock can be received upon arrival.", app_base_url() . '/store/receive-stock.php');

            flash_message('success', 'Payment recorded successfully.');
            header('Location: ' . app_base_url() . '/finance/purchases/view.php?id=' . $purchase_id); exit;
        }
    }
}
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Record Payment</h1></div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if(!empty($errors)): ?><div class="alert alert--error" style="margin-bottom:var(--space-4);"><?php foreach($errors as $e_): ?><p style="margin:.2rem 0;"><?= e($e_) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="content-card" style="max-width:580px;">
    <div class="content-card__header"><h2 class="content-card__title">Payment Details</h2></div>
    <div class="content-card__body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div class="form-group"><label class="form-label">Purchase Order *</label>
                <select class="form-input" name="purchase_id" required>
                    <option value="">— Select PO —</option>
                    <?php foreach ($purchases as $po): ?><option value="<?= (int)$po['id'] ?>" <?= (int)($_POST['purchase_id'] ?? $prefill_po)===(int)$po['id']?'selected':'' ?>><?= e($po['reference_no']) ?> — <?= e($po['supplier_name'] ?? '?') ?> (<?= e(format_currency($po['total_amount'])) ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Amount *</label><input class="form-input" type="number" name="amount" step="0.01" min="0.01" value="<?= e($_POST['amount'] ?? '') ?>" required style="max-width:180px;"></div>
            <div class="form-group"><label class="form-label">Payment Method *</label>
                <select class="form-input" name="payment_method" required>
                    <?php foreach (['bank_transfer'=>'Bank Transfer','cash'=>'Cash','cheque'=>'Cheque','card'=>'Card','other'=>'Other'] as $val=>$label): ?>
                    <option value="<?= $val ?>" <?= ($_POST['payment_method'] ?? 'bank_transfer')===$val?'selected':'' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Payment Date *</label><input class="form-input" type="date" name="payment_date" value="<?= e($_POST['payment_date'] ?? date('Y-m-d')) ?>" required style="max-width:180px;"></div>
            <div class="form-group"><label class="form-label">Reference Number</label><input class="form-input" name="reference_no" value="<?= e($_POST['reference_no'] ?? '') ?>" placeholder="Cheque/transfer reference…"></div>
            <div class="form-group"><label class="form-label">Notes</label><textarea class="form-input" name="notes" rows="2"><?= e($_POST['notes'] ?? '') ?></textarea></div>
            <div style="display:flex;gap:var(--space-3);"><button type="submit" class="btn btn--primary"><span class="material-symbols-outlined">payments</span> Record Payment</button><a href="<?= e(app_base_url()) ?>/finance/payments/index.php" class="btn btn--secondary">Cancel</a></div>
        </form>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
