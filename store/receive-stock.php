<?php
/**
 * store/receive-stock.php — Receive stock into inventory
 */
$page_title = 'Receive Stock';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('store');

$uid     = (int) $_SESSION['user_id'];
$pdo     = get_db_connection();
$errors  = [];

$products = $pdo->query('SELECT p.id, p.sku, p.name, p.unit, p.quantity_in_stock FROM products p WHERE p.is_active=1 ORDER BY p.name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Session expired.'; }
    else {
        $product_id = sanitize_int($_POST['product_id'] ?? 0);
        $quantity   = sanitize_int($_POST['quantity'] ?? 0);
        $notes      = sanitize_string($_POST['notes'] ?? '');

        if ($product_id <= 0) $errors[] = 'Please select a product.';
        if ($quantity <= 0)   $errors[] = 'Quantity must be at least 1.';

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT quantity_in_stock FROM products WHERE id = ? FOR UPDATE');
                $stmt->execute([$product_id]);
                $qty_before = (int)$stmt->fetchColumn();
                $qty_after = $qty_before + $quantity;

                $pdo->prepare('UPDATE products SET quantity_in_stock = ? WHERE id = ?')->execute([$qty_after, $product_id]);
                $pdo->prepare('INSERT INTO stock_movements (product_id, movement_type, quantity, quantity_before, quantity_after, notes, performed_by) VALUES (?,?,?,?,?,?,?)')->execute([$product_id, 'receive', $quantity, $qty_before, $qty_after, $notes ?: null, $uid]);
                $pdo->commit();

                $pname = '';
                foreach ($products as $p) { if ((int)$p['id'] === $product_id) { $pname = $p['name']; break; } }
                log_activity($uid, 'receive_stock', "Received $quantity unit(s) of $pname.", 'product', $product_id);
                send_notification_to_role('manager', 'Stock Received', "Store received $quantity unit(s) of $pname into inventory.", app_base_url() . '/manager/inventory.php');
                flash_message('success', "Received $quantity unit(s) of $pname successfully.");
                header('Location: ' . app_base_url() . '/store/inventory.php'); exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('[GCM] receive_stock error: ' . $e->getMessage());
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
<div class="page-header"><h1 class="page-header__title">Receive Stock</h1><p class="page-header__subtitle">Record incoming stock to increase inventory.</p></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<?php if (!empty($errors)): ?><div class="alert alert--error" style="margin-bottom:var(--space-4);"><?php foreach ($errors as $err): ?><p style="margin:.2rem 0;"><?= e($err) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="content-card" style="max-width:600px;">
    <div class="content-card__header"><h2 class="content-card__title">Receive Stock Entry</h2></div>
    <div class="content-card__body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div class="form-group">
                <label class="form-label">Product *</label>
                <select class="form-input" name="product_id" required>
                    <option value="">— Select Product —</option>
                    <?php foreach ($products as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= (int)($_POST['product_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= e($p['name']) ?> (<?= e($p['sku']) ?>) — <?= e($p['quantity_in_stock']) ?> in stock
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Quantity to Receive *</label>
                <input class="form-input" type="number" name="quantity" min="1" value="<?= (int)($_POST['quantity'] ?? 1) ?>" required style="max-width:160px;">
            </div>
            <div class="form-group">
                <label class="form-label">Notes / Reference (optional)</label>
                <textarea class="form-input" name="notes" rows="2" placeholder="e.g. PO-20260828-001 or delivery reference"><?= e($_POST['notes'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;gap:var(--space-3);">
                <button type="submit" class="btn btn--primary"><span class="material-symbols-outlined">move_to_inbox</span> Confirm Receipt</button>
                <a href="<?= e(app_base_url()) ?>/store/inventory.php" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
