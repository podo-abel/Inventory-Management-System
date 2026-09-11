<?php
/**
 * employee/products.php — Browse available products
 */
$page_title = 'Browse Products';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('employee');

$pdo = get_db_connection();
$search = sanitize_string($_GET['search'] ?? '');
$cat_id = sanitize_int($_GET['cat_id'] ?? 0);

$where = ['p.is_active = 1'];
$params = [];
if ($search !== '') { $where[] = '(p.name LIKE :s OR p.sku LIKE :s2)'; $params[':s'] = "%$search%"; $params[':s2'] = "%$search%"; }
if ($cat_id > 0)    { $where[] = 'p.category_id = :cat'; $params[':cat'] = $cat_id; }

$sql = 'SELECT p.id, p.sku, p.name, p.unit, p.quantity_in_stock, c.name AS category_name
        FROM products p LEFT JOIN categories c ON c.id = p.category_id
        WHERE ' . implode(' AND ', $where) . ' ORDER BY p.name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$cats = $pdo->query('SELECT id, name FROM categories WHERE is_active=1 ORDER BY name')->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1 class="page-header__title">Browse Products</h1>
    <p class="page-header__subtitle">Find items you need and submit a request.</p>
</div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__header">
        <h2 class="content-card__title">Products (<?= count($products) ?>)</h2>
        <a href="<?= e(app_base_url()) ?>/employee/request-item.php" class="btn btn--primary"><span class="material-symbols-outlined">add_shopping_cart</span> New Request</a>
    </div>
    <div class="content-card__body" style="border-bottom:1px solid var(--color-border);padding:var(--space-4);">
        <form method="get" class="filter-form" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin:0;flex:1;min-width:160px;">
                <label class="form-label">Search</label>
                <input class="form-input" name="search" value="<?= e($search) ?>" placeholder="Name or SKU…">
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:140px;">
                <label class="form-label">Category</label>
                <select class="form-input" name="cat_id">
                    <option value="">All Categories</option>
                    <?php foreach ($cats as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= $cat_id == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn--secondary">Filter</button>
            <a href="<?= e(app_base_url()) ?>/employee/products.php" class="btn btn--secondary">Reset</a>
        </form>
    </div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>Unit</th><th>In Stock</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><code><?= e($p['sku']) ?></code></td>
                <td><?= e($p['name']) ?></td>
                <td><?= e($p['category_name'] ?? '—') ?></td>
                <td><?= e($p['unit']) ?></td>
                <td>
                    <?php if ($p['quantity_in_stock'] <= 0): ?>
                    <span class="badge badge--danger">Out of Stock</span>
                    <?php else: ?>
                    <span style="font-weight:600;"><?= e($p['quantity_in_stock']) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= e(app_base_url()) ?>/employee/request-item.php?product_id=<?= (int)$p['id'] ?>" class="btn btn--primary" style="padding:.25rem .75rem;font-size:.8rem;">Request</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--color-text-secondary);">No products found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
