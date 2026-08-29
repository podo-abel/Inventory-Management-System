<?php
/**
 * store/issue-stock.php — Manually issue stock (ad-hoc)
 */
$page_title = 'Issue Stock';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('store');

$uid    = (int) $_SESSION['user_id'];
$pdo    = get_db_connection();
$errors = [];

$products = $pdo->query('SELECT p.id, p.sku, p.name, p.unit, p.quantity_in_stock FROM products p WHERE p.is_active=1 AND p.quantity_in_stock > 0 ORDER BY p.name')->fetchAll();

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
                // Check available stock
                $ps = $pdo->prepare('SELECT quantity_in_stock, name FROM products WHERE id = ? AND is_active = 1 FOR UPDATE');
                $ps->execute([$product_id]);
                $prod = $ps->fetch();
                if (!$prod) { 
                    $errors[] = 'Product not found.'; 
                    $pdo->rollBack();
                } elseif ($prod['quantity_in_stock'] < $quantity) { 
                    $errors[] = "Insufficient stock. Only {$prod['quantity_in_stock']} available."; 
                    $pdo->rollBack();
                } else {
                    $qty_before = (int)$prod['quantity_in_stock'];
                    $qty_after = $qty_before - $quantity;
                    
                    $pdo->prepare('UPDATE products SET quantity_in_stock = ? WHERE id = ?')->execute([$qty_after, $product_id]);
                    $pdo->prepare('INSERT INTO stock_movements (product_id, movement_type, quantity, quantity_before, quantity_after, notes, performed_by) VALUES (?,?,?,?,?,?,?)')->execute([$product_id, 'issue', $quantity, $qty_before, $qty_after, $notes ?: null, $uid]);
                    $pdo->commit();
                    
                    log_activity($uid, 'issue_stock', "Issued $quantity unit(s) of {$prod['name']}.", 'product', $product_id);
                    flash_message('success', "Issued $quantity unit(s) of {$prod['name']}.");
                    header('Location: ' . app_base_url() . '/store/inventory.php'); exit;
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('[Logitrack] issue_stock error: ' . $e->getMessage());
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
<div class="page-header"><h1 class="page-header__title">Issue Stock</h1><p class="page-header__subtitle">Manually issue stock for ad-hoc purposes.</p></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<?php if (!empty($errors)): ?><div class="alert alert--error" style="margin-bottom:var(--space-4);"><?php foreach ($errors as $err): ?><p style="margin:.2rem 0;"><?= e($err) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="content-card" style="max-width:600px;">
    <div class="content-card__header"><h2 class="content-card__title">Issue Stock Entry</h2></div>
    <div class="content-card__body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div class="form-group">
                <label class="form-label">Product *</label>
                <select class="form-input" name="product_id" required>
                    <option value="">— Select Product —</option>
                    <?php foreach ($products as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= (int)($_POST['product_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= e($p['name']) ?> (<?= e($p['sku']) ?>) — <?= e($p['quantity_in_stock']) ?> available
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Quantity to Issue *</label>
                <input class="form-input" type="number" name="quantity" min="1" value="<?= (int)($_POST['quantity'] ?? 1) ?>" required style="max-width:160px;">
            </div>
            <div class="form-group">
                <label class="form-label">Notes / Reason *</label>
                <textarea class="form-input" name="notes" rows="2" placeholder="Reason for issuance…" required><?= e($_POST['notes'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;gap:var(--space-3);">
                <button type="submit" class="btn btn--primary"><span class="material-symbols-outlined">outbox</span> Issue Stock</button>
                <a href="<?= e(app_base_url()) ?>/store/inventory.php" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
