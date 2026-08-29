<?php
/**
 * admin/products/edit.php — Edit product
 */
$page_title = 'Edit Product';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('admin');

$uid = (int)$_SESSION['user_id'];
$pdo = get_db_connection();
$pid = sanitize_int($_GET['id'] ?? 0);

$ps = $pdo->prepare('SELECT * FROM products WHERE id = ?'); $ps->execute([$pid]); $prod = $ps->fetch();
if (!$prod) { flash_message('error', 'Product not found.'); header('Location: ' . app_base_url() . '/admin/products/index.php'); exit; }

$cats = $pdo->query('SELECT id, name FROM categories WHERE is_active=1 ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Session expired.'; }
    else {
        $cat_id     = sanitize_int($_POST['category_id'] ?? 0);
        $sku        = sanitize_string($_POST['sku'] ?? '');
        $name       = sanitize_string($_POST['name'] ?? '');
        $desc       = sanitize_string($_POST['description'] ?? '');
        $unit       = sanitize_string($_POST['unit'] ?? 'pcs');
        $min_stock  = sanitize_int($_POST['min_stock_level'] ?? 0);
        $unit_price = $_POST['unit_price'] !== '' ? (float)$_POST['unit_price'] : null;
        $is_active  = isset($_POST['is_active']) ? 1 : 0;

        if (empty($sku))  $errors[] = 'SKU required.';
        if (empty($name)) $errors[] = 'Name required.';
        if (empty($errors)) {
            $chk = $pdo->prepare('SELECT id FROM products WHERE sku = ? AND id != ?'); $chk->execute([$sku, $pid]);
            if ($chk->fetch()) $errors[] = 'SKU already used by another product.';
        }
        if (empty($errors)) {
            $pdo->prepare('UPDATE products SET category_id=?,sku=?,name=?,description=?,unit=?,min_stock_level=?,unit_price=?,is_active=? WHERE id=?')->execute([$cat_id ?: null, $sku, $name, $desc ?: null, $unit, $min_stock, $unit_price, $is_active, $pid]);
            log_activity($uid, 'update_product', "Updated product: $name.", 'product', $pid);
            flash_message('success', 'Product updated.');
            header('Location: ' . app_base_url() . '/admin/products/index.php'); exit;
        }
    }
}
$d = $_POST ?: $prod;
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Edit Product</h1></div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if(!empty($errors)): ?><div class="alert alert--error" style="margin-bottom:var(--space-4);"><?php foreach($errors as $e_): ?><p style="margin:.2rem 0;"><?= e($e_) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="content-card" style="max-width:640px;">
    <div class="content-card__header"><h2 class="content-card__title"><?= e($prod['name']) ?></h2></div>
    <div class="content-card__body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);">
                <div class="form-group"><label class="form-label">SKU *</label><input class="form-input" name="sku" value="<?= e($d['sku']) ?>" required></div>
                <div class="form-group"><label class="form-label">Category</label>
                    <select class="form-input" name="category_id">
                        <option value="">— None —</option>
                        <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($d['category_id'] ?? 0)===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Product Name *</label><input class="form-input" name="name" value="<?= e($d['name']) ?>" required></div>
                <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Description</label><textarea class="form-input" name="description" rows="2"><?= e($d['description'] ?? '') ?></textarea></div>
                <div class="form-group"><label class="form-label">Unit</label><input class="form-input" name="unit" value="<?= e($d['unit']) ?>"></div>
                <div class="form-group"><label class="form-label">Min Stock Level</label><input class="form-input" type="number" name="min_stock_level" min="0" value="<?= (int)$d['min_stock_level'] ?>"></div>
                <div class="form-group"><label class="form-label">Unit Price</label><input class="form-input" type="number" name="unit_price" step="0.01" min="0" value="<?= e($d['unit_price'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label">Current Stock (read-only)</label><input class="form-input" value="<?= e($prod['quantity_in_stock']) ?>" disabled style="background:var(--color-bg);"></div>
                <div class="form-group"><label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;"><input type="checkbox" name="is_active" <?= ($d['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label></div>
            </div>
            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-4);"><button type="submit" class="btn btn--primary">Save Changes</button><a href="<?= e(app_base_url()) ?>/admin/products/index.php" class="btn btn--secondary">Cancel</a></div>
        </form>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
