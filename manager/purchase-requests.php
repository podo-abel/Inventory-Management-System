<?php
/**
 * manager/purchase-requests.php — Purchase orders overview
 */
$page_title = 'Purchase Orders';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('manager');

$pdo = get_db_connection();
$purchases = $pdo->query(
    "SELECT pu.id, pu.reference_no, pu.status, pu.total_amount, pu.created_at, pu.expected_date,
            s.name AS supplier_name
     FROM purchases pu LEFT JOIN suppliers s ON s.id = pu.supplier_id
     ORDER BY pu.created_at DESC LIMIT 50"
)->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Purchase Orders</h1><p class="page-header__subtitle">Monitor purchase orders managed by Finance.</p></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Recent Purchase Orders</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Reference</th><th>Supplier</th><th>Status</th><th>Total</th><th>Expected</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($purchases as $po): ?>
            <tr>
                <td><code><?= e($po['reference_no']) ?></code></td>
                <td><?= e($po['supplier_name'] ?? '—') ?></td>
                <td><span class="badge <?= e(get_status_badge_class($po['status'])) ?>"><?= e(ucfirst(str_replace('_',' ',$po['status']))) ?></span></td>
                <td><?= e(format_currency($po['total_amount'])) ?></td>
                <td><?= e(format_date($po['expected_date'])) ?></td>
                <td><?= e(format_date($po['created_at'])) ?></td>
                <td>
                    <a href="purchase-view.php?id=<?= e($po['id']) ?>" class="btn btn--secondary" style="padding:4px 8px; font-size:12px;">Review</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($purchases)): ?><tr><td colspan="7" style="text-align:center;">No purchase orders yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
