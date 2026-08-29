<?php
/**
 * manager/purchase-view.php — View & Approve a purchase order
 */
$page_title = 'Purchase Order';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('manager');

$uid   = (int)$_SESSION['user_id'];
$pdo   = get_db_connection();
$po_id = sanitize_int($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { flash_message('error', 'Session expired.'); }
    else {
        $action = $_POST['action'] ?? '';
        if ($action === 'approve') {
            $pdo->prepare("UPDATE purchases SET status='approved' WHERE id=? AND status='draft'")->execute([$po_id]);
            log_activity($uid, 'approve_purchase', "Approved PO #$po_id", 'purchase', $po_id);
            flash_message('success', 'Purchase order approved.');
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE purchases SET status='cancelled' WHERE id=? AND status='draft'")->execute([$po_id]);
            log_activity($uid, 'reject_purchase', "Rejected PO #$po_id", 'purchase', $po_id);
            flash_message('success', 'Purchase order rejected.');
        }
        header('Location: ' . app_base_url() . '/manager/purchase-view.php?id=' . $po_id); exit;
    }
}

$stmt = $pdo->prepare('SELECT pu.*, s.name AS supplier_name, s.contact_name, s.email AS supplier_email, u.full_name AS ordered_by_name FROM purchases pu LEFT JOIN suppliers s ON s.id = pu.supplier_id LEFT JOIN users u ON u.id = pu.ordered_by WHERE pu.id = ?');
$stmt->execute([$po_id]);
$po = $stmt->fetch();
if (!$po) { flash_message('error', 'Purchase order not found.'); header('Location: ' . app_base_url() . '/manager/purchase-requests.php'); exit; }

$items = $pdo->prepare('SELECT pi.*, p.name AS product_name, p.sku, p.unit FROM purchase_items pi JOIN products p ON p.id = pi.product_id WHERE pi.purchase_id = ?');
$items->execute([$po_id]); $items = $items->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:var(--space-3);">
        <div><h1 class="page-header__title">PO: <code><?= e($po['reference_no']) ?></code></h1><p class="page-header__subtitle">Supplier: <?= e($po['supplier_name'] ?? '—') ?> &mdash; <?= e(format_date($po['created_at'])) ?></p></div>
        <div style="display:flex;gap:.5rem;">
            <a href="<?= e(app_base_url()) ?>/manager/purchase-requests.php" class="btn btn--secondary">← Back</a>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
    <div class="content-card" style="margin:0;">
        <div class="content-card__header"><h2 class="content-card__title">Line Items</h2></div>
        <div class="content-card__body" style="padding:0;">
            <table class="table">
                <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                <tr><td><?= e($item['product_name']) ?></td><td><code><?= e($item['sku']) ?></code></td><td><?= e($item['quantity_ordered']) ?></td><td><?= e(format_currency($item['unit_price'])) ?></td><td style="font-weight:600;"><?= e(format_currency($item['total_price'])) ?></td></tr>
                <?php endforeach; ?>
                <tr style="background:var(--color-bg);"><td colspan="4" style="text-align:right;font-weight:600;">Grand Total:</td><td style="font-weight:700;font-size:1.05rem;"><?= e(format_currency($po['total_amount'])) ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="content-card" style="margin:0;">
        <div class="content-card__header"><h2 class="content-card__title">Order Info</h2></div>
        <div class="content-card__body">
            <p><strong>Status:</strong> <span class="badge <?= e(get_status_badge_class($po['status'])) ?>"><?= e(ucfirst(str_replace('_',' ',$po['status']))) ?></span></p>
            <p><strong>Supplier:</strong> <?= e($po['supplier_name'] ?? '—') ?></p>
            <p><strong>Order Total:</strong> <strong style="font-size:1.1rem;"><?= e(format_currency($po['total_amount'])) ?></strong></p>
            <?php if ($po['expected_date']): ?><p><strong>Expected:</strong> <?= e(format_date($po['expected_date'])) ?></p><?php endif; ?>
            <?php if ($po['notes']): ?><p><strong>Notes:</strong> <?= e($po['notes']) ?></p><?php endif; ?>
            
            <?php if ($po['status'] === 'draft'): ?>
            <hr style="margin:var(--space-4) 0; border:none; border-top:1px solid var(--color-outline-variant);">
            <form method="POST" style="display:flex; flex-direction:column; gap:var(--space-3);">
                <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                <button type="submit" name="action" value="approve" class="btn btn--primary" style="width:100%; justify-content:center;"><span class="material-symbols-outlined">check_circle</span> Approve Purchase</button>
                <button type="submit" name="action" value="reject" class="btn btn--danger" style="width:100%; justify-content:center;" onclick="return confirm('Reject this purchase order?')"><span class="material-symbols-outlined">cancel</span> Reject Purchase</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
