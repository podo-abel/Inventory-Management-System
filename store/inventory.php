<?php
/**
 * store/inventory.php — Full inventory listing
 */
$page_title = 'Inventory';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('store');

$pdo    = get_db_connection();
$search = sanitize_string($_GET['q'] ?? '');
$cat_id = sanitize_int($_GET['cat_id'] ?? 0);
$filter = sanitize_string($_GET['filter'] ?? '');

$where  = ['p.is_active = 1'];
$params = [];
if ($search !== '') { $where[] = '(p.name LIKE :s OR p.sku LIKE :s2)'; $params[':s'] = "%$search%"; $params[':s2'] = "%$search%"; }
if ($cat_id > 0)    { $where[] = 'p.category_id = :cat'; $params[':cat'] = $cat_id; }
if ($filter === 'low')  { $where[] = 'p.quantity_in_stock <= p.min_stock_level AND p.quantity_in_stock > 0'; }
if ($filter === 'out')  { $where[] = 'p.quantity_in_stock = 0'; }

$stmt = $pdo->prepare('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.name');
$stmt->execute($params);
$products = $stmt->fetchAll();
$cats = $pdo->query('SELECT id, name FROM categories WHERE is_active=1 ORDER BY name')->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:var(--space-3);">
        <div><h1 class="page-header__title">Inventory</h1><p class="page-header__subtitle"><?= count($products) ?> products shown</p></div>
        <div style="display:flex;gap:.5rem;">
            <a href="<?= e(app_base_url()) ?>/store/receive-stock.php" class="btn btn--primary"><span class="material-symbols-outlined">move_to_inbox</span> Receive Stock</a>
            <a href="<?= e(app_base_url()) ?>/store/stock-adjustment.php" class="btn btn--secondary">Adjust Stock</a>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__body" style="border-bottom:1px solid var(--color-border);padding:var(--space-4);">
        <form method="get" style="display:flex;gap:.75rem;flex-wrap:wrap;">
            <input class="form-input" name="search" value="<?= e($search) ?>" placeholder="Name or SKU…" style="flex:1;min-width:160px;">
            <select class="form-input" name="cat_id" style="width:auto;"><option value="">All Categories</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat_id==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
            <select class="form-input" name="filter" style="width:auto;">
                <option value="">All Stock</option>
                <option value="low" <?= $filter==='low'?'selected':'' ?>>Low Stock</option>
                <option value="out" <?= $filter==='out'?'selected':'' ?>>Out of Stock</option>
            </select>
            <button type="submit" class="btn btn--secondary">Filter</button>
            <a href="?" class="btn btn--secondary">Reset</a>
        </form>
    </div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>In Stock</th><th>Min Level</th><th>Unit</th><th>Price</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
            <?php
            if ($p['quantity_in_stock'] <= 0) { $sb = 'badge--danger'; $sl = 'Out of Stock'; }
            elseif ($p['quantity_in_stock'] <= $p['min_stock_level']) { $sb = 'badge--warning'; $sl = 'Low Stock'; }
            else { $sb = 'badge--success'; $sl = 'In Stock'; }
            ?>
            <tr>
                <td><code><?= e($p['sku']) ?></code></td>
                <td><?= e($p['name']) ?></td>
                <td><?= e($p['category_name'] ?? '—') ?></td>
                <td style="font-weight:600;<?= $p['quantity_in_stock'] <= $p['min_stock_level'] ? 'color:#dc2626;' : '' ?>"><?= e($p['quantity_in_stock']) ?></td>
                <td><?= e($p['min_stock_level']) ?></td>
                <td><?= e($p['unit']) ?></td>
                <td><?= $p['unit_price'] !== null ? e(format_currency($p['unit_price'])) : '—' ?></td>
                <td><span class="badge <?= $sb ?>"><?= $sl ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?><tr><td colspan="8" style="text-align:center;">No products found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
