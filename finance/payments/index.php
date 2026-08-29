<?php
/**
 * finance/payments/index.php — All payments
 */
$page_title = 'Payments';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('finance');

$pdo = get_db_connection();
$page = max(1, sanitize_int($_GET['page'] ?? 1)); $per = 25;
$cnt = $pdo->query('SELECT COUNT(*) FROM payments'); $total = (int)$cnt->fetchColumn();
$pg = paginate($total, $per, $page);

$stmt = $pdo->prepare('SELECT pa.*, pu.reference_no AS po_ref, s.name AS supplier_name, u.full_name AS recorder FROM payments pa LEFT JOIN purchases pu ON pu.id = pa.purchase_id LEFT JOIN suppliers s ON s.id = pu.supplier_id LEFT JOIN users u ON u.id = pa.recorded_by ORDER BY pa.payment_date DESC, pa.created_at DESC LIMIT :lim OFFSET :off');
$stmt->bindValue(':lim', $per, PDO::PARAM_INT);
$stmt->bindValue(':off', $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$payments = $stmt->fetchAll();
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Payments</h1></div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">All Payments (<?= $total ?>)</h2><a href="<?= e(app_base_url()) ?>/finance/payments/create.php" class="btn btn--primary"><span class="material-symbols-outlined">add</span> Record Payment</a></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Date</th><th>PO Reference</th><th>Supplier</th><th>Amount</th><th>Method</th><th>Status</th><th>Recorded By</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $pay): ?>
            <tr>
                <td><?= e(format_date($pay['payment_date'])) ?></td>
                <td><?= $pay['po_ref'] ? '<code>' . e($pay['po_ref']) . '</code>' : '—' ?></td>
                <td><?= e($pay['supplier_name'] ?? '—') ?></td>
                <td style="font-weight:600;"><?= e(format_currency($pay['amount'])) ?></td>
                <td><?= e(ucfirst(str_replace('_',' ',$pay['payment_method']))) ?></td>
                <td><span class="badge <?= e(get_status_badge_class($pay['status'])) ?>"><?= e(ucfirst($pay['status'])) ?></span></td>
                <td style="font-size:.85rem;color:var(--color-text-secondary);"><?= e($pay['recorder'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($payments)): ?><tr><td colspan="7" style="text-align:center;">No payments yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
