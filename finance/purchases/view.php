<?php
/**
 * finance/purchases/view.php — View a purchase order
 */
$page_title = 'Purchase Order';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('finance');

$uid   = (int)$_SESSION['user_id'];
$pdo   = get_db_connection();
$po_id = sanitize_int($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT pu.*, s.name AS supplier_name, s.contact_name, s.email AS supplier_email, u.full_name AS ordered_by_name FROM purchases pu LEFT JOIN suppliers s ON s.id = pu.supplier_id LEFT JOIN users u ON u.id = pu.ordered_by WHERE pu.id = ?');
$stmt->execute([$po_id]);
$po = $stmt->fetch();
if (!$po) { flash_message('error', 'Purchase order not found.'); header('Location: ' . app_base_url() . '/finance/purchases/index.php'); exit; }

$items    = $pdo->prepare('SELECT pi.*, p.name AS product_name, p.sku, p.unit FROM purchase_items pi JOIN products p ON p.id = pi.product_id WHERE pi.purchase_id = ?');
$items->execute([$po_id]); $items = $items->fetchAll();
$payments = $pdo->prepare('SELECT pa.*, u.full_name AS recorded_by_name FROM payments pa LEFT JOIN users u ON u.id = pa.recorded_by WHERE pa.purchase_id = ? ORDER BY pa.payment_date DESC');
$payments->execute([$po_id]); $payments = $payments->fetchAll();
$total_paid = array_sum(array_column(array_filter($payments, fn($p) => $p['status'] === 'completed'), 'amount'));
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:var(--space-3);">
        <div><h1 class="page-header__title">PO: <code><?= e($po['reference_no']) ?></code></h1><p class="page-header__subtitle">Supplier: <?= e($po['supplier_name'] ?? '—') ?> &mdash; <?= e(format_date($po['created_at'])) ?></p></div>
        <div style="display:flex;gap:.5rem;">
            <a href="<?= e(app_base_url()) ?>/finance/payments/create.php?purchase_id=<?= $po_id ?>" class="btn btn--primary"><span class="material-symbols-outlined">payments</span> Record Payment</a>
            <a href="<?= e(app_base_url()) ?>/finance/purchases/index.php" class="btn btn--secondary">← Back</a>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>

<div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Order Info</h2></div>
    <div class="content-card__body">
        <p><strong>Status:</strong> <span class="badge <?= e(get_status_badge_class($po['status'])) ?>"><?= e(ucfirst(str_replace('_',' ',$po['status']))) ?></span></p>
        <p><strong>Supplier:</strong> <?= e($po['supplier_name'] ?? '—') ?></p>
        <p><strong>Contact:</strong> <?= e($po['contact_name'] ?? '—') ?> / <?= e($po['supplier_email'] ?? '—') ?></p>
        <p><strong>Order Total:</strong> <strong style="font-size:1.1rem;"><?= e(format_currency($po['total_amount'])) ?></strong></p>
        <p><strong>Total Paid:</strong> <?= e(format_currency($total_paid)) ?></p>
        <p><strong>Balance:</strong> <?= e(format_currency($po['total_amount'] - $total_paid)) ?></p>
        <?php if ($po['expected_date']): ?><p><strong>Expected:</strong> <?= e(format_date($po['expected_date'])) ?></p><?php endif; ?>
        <?php if ($po['notes']): ?><p><strong>Notes:</strong> <?= e($po['notes']) ?></p><?php endif; ?>
    </div>
</div>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Payments</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $pay): ?>
            <tr><td><?= e(format_date($pay['payment_date'])) ?></td><td style="font-weight:600;"><?= e(format_currency($pay['amount'])) ?></td><td><?= e(ucfirst(str_replace('_',' ',$pay['payment_method']))) ?></td><td><span class="badge <?= e(get_status_badge_class($pay['status'])) ?>"><?= e(ucfirst($pay['status'])) ?></span></td></tr>
            <?php endforeach; ?>
            <?php if(empty($payments)): ?><tr><td colspan="4" style="text-align:center;">No payments yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Line Items</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Product</th><th>SKU</th><th>Qty Ordered</th><th>Qty Received</th><th>Unit Price</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <tr><td><?= e($item['product_name']) ?></td><td><code><?= e($item['sku']) ?></code></td><td><?= e($item['quantity_ordered']) ?></td><td><?= e($item['quantity_received']) ?></td><td><?= e(format_currency($item['unit_price'])) ?></td><td style="font-weight:600;"><?= e(format_currency($item['total_price'])) ?></td></tr>
            <?php endforeach; ?>
            <tr style="background:var(--color-bg);"><td colspan="5" style="text-align:right;font-weight:600;">Grand Total:</td><td style="font-weight:700;font-size:1.05rem;"><?= e(format_currency($po['total_amount'])) ?></td></tr>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
