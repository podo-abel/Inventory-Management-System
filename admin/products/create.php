<?php
/**
 * admin/products/create.php — Add a new product
 */
$page_title = 'Add Product';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('admin');

$uid = (int)$_SESSION['user_id'];
$pdo = get_db_connection();
$errors = [];

$cats = $pdo->query('SELECT id, name FROM categories WHERE is_active=1 ORDER BY name')->fetchAll();

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

        if (empty($sku))  $errors[] = 'SKU is required.';
        if (empty($name)) $errors[] = 'Name is required.';
        if (empty($errors)) {
            // Check unique SKU
            $chk = $pdo->prepare('SELECT id FROM products WHERE sku = ?'); $chk->execute([$sku]);
            if ($chk->fetch()) $errors[] = 'SKU already exists.';
        }
        if (empty($errors)) {
            $pdo->prepare('INSERT INTO products (category_id, sku, name, description, unit, min_stock_level, unit_price) VALUES (?,?,?,?,?,?,?)')->execute([$cat_id ?: null, $sku, $name, $desc ?: null, $unit, $min_stock, $unit_price]);
            $pid = (int)$pdo->lastInsertId();
            log_activity($uid, 'create_product', "Created product: $name ($sku).", 'product', $pid);
            flash_message('success', "Product '$name' created.");
            header('Location: ' . app_base_url() . '/admin/products/index.php'); exit;
        }
    }
}
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Add Product</h1></div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if(!empty($errors)): ?><div class="alert alert--error" style="margin-bottom:var(--space-4);"><?php foreach($errors as $e_): ?><p style="margin:.2rem 0;"><?= e($e_) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="content-card" style="max-width:640px;">
    <div class="content-card__header"><h2 class="content-card__title">Product Information</h2></div>
    <div class="content-card__body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);">
                <div class="form-group"><label class="form-label">SKU *</label><input class="form-input" name="sku" value="<?= e($_POST['sku'] ?? '') ?>" required placeholder="e.g. OFF-A4-500"></div>
                <div class="form-group"><label class="form-label">Category</label>
                    <select class="form-input" name="category_id">
                        <option value="">— None —</option>
                        <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($_POST['category_id'] ?? 0)===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Product Name *</label><input class="form-input" name="name" value="<?= e($_POST['name'] ?? '') ?>" required></div>
                <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Description</label><textarea class="form-input" name="description" rows="2"><?= e($_POST['description'] ?? '') ?></textarea></div>
                <div class="form-group"><label class="form-label">Unit</label><input class="form-input" name="unit" value="<?= e($_POST['unit'] ?? 'pcs') ?>" placeholder="e.g. pcs, box, ream"></div>
                <div class="form-group"><label class="form-label">Min Stock Level</label><input class="form-input" type="number" name="min_stock_level" min="0" value="<?= (int)($_POST['min_stock_level'] ?? 0) ?>"></div>
                <div class="form-group"><label class="form-label">Unit Price</label><input class="form-input" type="number" name="unit_price" step="0.01" min="0" value="<?= e($_POST['unit_price'] ?? '') ?>" placeholder="Optional"></div>
            </div>
            <p style="font-size:.85rem;color:var(--color-text-secondary);margin-top:var(--space-3);">Note: Initial stock quantity is 0. Add stock through the Store module (Receive Stock).</p>
            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-4);"><button type="submit" class="btn btn--primary">Create Product</button><a href="<?= e(app_base_url()) ?>/admin/products/index.php" class="btn btn--secondary">Cancel</a></div>
        </form>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
