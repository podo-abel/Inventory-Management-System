<?php
/**
 * manager/inventory.php — Inventory overview (read-only)
 */
$page_title = 'Inventory Overview';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('manager');

$pdo    = get_db_connection();
$search = sanitize_string($_GET['search'] ?? '');
$cat_id = sanitize_int($_GET['cat_id'] ?? 0);

$where  = ['p.is_active = 1'];
$params = [];
if ($search !== '') { $where[] = '(p.name LIKE :s OR p.sku LIKE :s2)'; $params[':s'] = "%$search%"; $params[':s2'] = "%$search%"; }
if ($cat_id > 0)    { $where[] = 'p.category_id = :cat'; $params[':cat'] = $cat_id; }

$stmt = $pdo->prepare('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.name');
$stmt->execute($params);
$products = $stmt->fetchAll();

$cats = $pdo->query('SELECT id, name FROM categories WHERE is_active=1 ORDER BY name')->fetchAll();
$low_count = 0; $out_count = 0;
foreach ($products as $p) { if ($p['quantity_in_stock'] <= 0) $out_count++; elseif ($p['quantity_in_stock'] <= $p['min_stock_level']) $low_count++; }
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Inventory Overview</h1><p class="page-header__subtitle">Current stock levels across all products.</p></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="stats-grid" style="margin-bottom:var(--space-5);">
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">inventory_2</span></div><div><p class="stat-card__label">Total Products</p><p class="stat-card__value"><?= count($products) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--yellow"><span class="material-symbols-outlined">warning</span></div><div><p class="stat-card__label">Low Stock</p><p class="stat-card__value"><?= $low_count ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--red"><span class="material-symbols-outlined">block</span></div><div><p class="stat-card__label">Out of Stock</p><p class="stat-card__value"><?= $out_count ?></p></div></div>
</div>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Products (<?= count($products) ?>)</h2></div>
    <div class="content-card__body" style="border-bottom:1px solid var(--color-border);padding:var(--space-4);">
        <form method="get" style="display:flex;gap:.75rem;flex-wrap:wrap;">
            <input class="form-input" name="search" value="<?= e($search) ?>" placeholder="Search by name or SKU…" style="flex:1;min-width:180px;">
            <select class="form-input" name="cat_id" style="width:auto;">
                <option value="">All Categories</option>
                <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat_id==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn--secondary">Filter</button>
            <a href="?" class="btn btn--secondary">Reset</a>
        </form>
    </div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>Stock</th><th>Min Level</th><th>Unit</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
            <?php
            if ($p['quantity_in_stock'] <= 0) { $status_badge = 'badge--danger'; $status_label = 'Out of Stock'; }
            elseif ($p['quantity_in_stock'] <= $p['min_stock_level']) { $status_badge = 'badge--warning'; $status_label = 'Low Stock'; }
            else { $status_badge = 'badge--success'; $status_label = 'In Stock'; }
            ?>
            <tr>
                <td><code><?= e($p['sku']) ?></code></td>
                <td><?= e($p['name']) ?></td>
                <td><?= e($p['category_name'] ?? '—') ?></td>
                <td style="font-weight:600;<?= $p['quantity_in_stock'] <= $p['min_stock_level'] ? 'color:#dc2626;' : '' ?>"><?= e($p['quantity_in_stock']) ?></td>
                <td><?= e($p['min_stock_level']) ?></td>
                <td><?= e($p['unit']) ?></td>
                <td><span class="badge <?= $status_badge ?>"><?= $status_label ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?><tr><td colspan="7" style="text-align:center;">No products found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
