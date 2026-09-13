<?php
/**
 * store/stock-adjustment.php — Adjust stock quantity (correction)
 */
$page_title = 'Stock Adjustment';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('store');

$uid    = (int) $_SESSION['user_id'];
$pdo    = get_db_connection();
$errors = [];

$products = $pdo->query('SELECT p.id, p.sku, p.name, p.unit, p.quantity_in_stock FROM products p WHERE p.is_active=1 ORDER BY p.name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Session expired.'; }
    else {
        $product_id  = sanitize_int($_POST['product_id'] ?? 0);
        $new_qty     = sanitize_int($_POST['new_quantity'] ?? -1);
        $notes       = sanitize_string($_POST['notes'] ?? '');

        if ($product_id <= 0) $errors[] = 'Please select a product.';
        if ($new_qty < 0)     $errors[] = 'New quantity cannot be negative.';
        if (empty($notes))    $errors[] = 'A reason for the adjustment is required.';

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                $ps = $pdo->prepare('SELECT quantity_in_stock, name FROM products WHERE id = ? AND is_active = 1 FOR UPDATE');
                $ps->execute([$product_id]);
                $prod = $ps->fetch();
                if (!$prod) { 
                    $errors[] = 'Product not found.'; 
                    $pdo->rollBack();
                } else {
                    $qty_before = (int)$prod['quantity_in_stock'];
                    $diff = $new_qty - $qty_before;
                    $adj_qty = abs($diff);
                    $qty_after = $new_qty;
                    
                    $pdo->prepare('UPDATE products SET quantity_in_stock = ? WHERE id = ?')->execute([$qty_after, $product_id]);
                    $pdo->prepare('INSERT INTO stock_movements (product_id, movement_type, quantity, quantity_before, quantity_after, notes, performed_by) VALUES (?,?,?,?,?,?,?)')->execute([$product_id, 'adjustment', $adj_qty, $qty_before, $qty_after, "Adjustment: $notes (was {$qty_before}, now $new_qty)", $uid]);
                    $pdo->commit();
                    log_activity($uid, 'stock_adjustment', "Adjusted {$prod['name']} from {$qty_before} to $new_qty. Reason: $notes", 'product', $product_id);
                    check_and_notify_low_stock($product_id);
                    flash_message('success', "Stock for {$prod['name']} adjusted to $new_qty.");
                    header('Location: ' . app_base_url() . '/store/inventory.php'); exit;
                }
            } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log('[GCM] stock_adjustment error: ' . $e->getMessage());
                    $errors[] = 'Failed to process. Please try again.';
                }
            }
        }
    }
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Stock Adjustment</h1><p class="page-header__subtitle">Correct inventory quantities after physical count or error.</p></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<?php if (!empty($errors)): ?><div class="alert alert--error" style="margin-bottom:var(--space-4);"><?php foreach ($errors as $err): ?><p style="margin:.2rem 0;"><?= e($err) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="content-card" style="max-width:600px;">
    <div class="content-card__header"><h2 class="content-card__title">Adjust Stock</h2></div>
    <div class="content-card__body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div class="form-group">
                <label class="form-label">Product *</label>
                <select class="form-input" name="product_id" id="product_select" required onchange="updateCurrentStock(this)">
                    <option value="">— Select Product —</option>
                    <?php foreach ($products as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" data-stock="<?= e($p['quantity_in_stock']) ?>" <?= (int)($_POST['product_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= e($p['name']) ?> (<?= e($p['sku']) ?>) — <?= e($p['quantity_in_stock']) ?> in stock
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Current Stock</label>
                <input class="form-input" type="text" id="current_stock_display" readonly style="background:var(--color-bg);max-width:160px;" value="">
            </div>
            <div class="form-group">
                <label class="form-label">New Quantity *</label>
                <input class="form-input" type="number" name="new_quantity" min="0" value="<?= isset($_POST['new_quantity']) ? (int)$_POST['new_quantity'] : '' ?>" required style="max-width:160px;" placeholder="Enter correct quantity">
            </div>
            <div class="form-group">
                <label class="form-label">Reason for Adjustment *</label>
                <textarea class="form-input" name="notes" rows="2" required placeholder="e.g. Physical count discrepancy, damaged goods written off…"><?= e($_POST['notes'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;gap:var(--space-3);">
                <button type="submit" class="btn btn--primary" onclick="return confirm('Confirm stock adjustment?')"><span class="material-symbols-outlined">tune</span> Apply Adjustment</button>
                <a href="<?= e(app_base_url()) ?>/store/inventory.php" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
function updateCurrentStock(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('current_stock_display').value = opt.dataset.stock || '';
}
window.addEventListener('DOMContentLoaded', function(){ updateCurrentStock(document.getElementById('product_select')); });
</script>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
