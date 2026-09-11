<?php
/**
 * admin/products/index.php — Products list
 */
$page_title = 'Products';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('admin');

$pdo    = get_db_connection();
$search = sanitize_string($_GET['search'] ?? '');
$cat_id = sanitize_int($_GET['cat_id'] ?? 0);
$page   = max(1, sanitize_int($_GET['page'] ?? 1));
$per    = 20;

$where = []; $params = [];
if ($search !== '') { $where[] = '(p.name LIKE :s OR p.sku LIKE :s2)'; $params[':s'] = "%$search%"; $params[':s2'] = "%$search%"; }
if ($cat_id > 0)    { $where[] = 'p.category_id = :cat'; $params[':cat'] = $cat_id; }
$wsql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt = $pdo->prepare("SELECT COUNT(*) FROM products p $wsql"); $cnt->execute($params); $total = (int)$cnt->fetchColumn();
$pg = paginate($total, $per, $page);

$list_params = $params;
$stmt = $pdo->prepare("SELECT p.id, p.sku, p.name, p.unit, p.quantity_in_stock, p.unit_price, p.is_active, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id $wsql ORDER BY p.name LIMIT :lim OFFSET :off");
if (isset($params[':s']))   { $stmt->bindValue(':s',   $params[':s'],   PDO::PARAM_STR); $stmt->bindValue(':s2', $params[':s2'], PDO::PARAM_STR); }
if (isset($params[':cat'])) { $stmt->bindValue(':cat', $params[':cat'], PDO::PARAM_INT); }
$stmt->bindValue(':lim', $per, PDO::PARAM_INT);
$stmt->bindValue(':off', $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$cats = $pdo->query('SELECT id, name FROM categories WHERE is_active=1 ORDER BY name')->fetchAll();
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Products</h1><p class="page-header__subtitle">Manage the product catalogue.</p></div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__header">
        <h2 class="content-card__title">Products (<?= $total ?>)</h2>
        <a href="<?= e(app_base_url()) ?>/admin/products/create.php" class="btn btn--primary"><span class="material-symbols-outlined">add</span> Add Product</a>
    </div>
    <div class="content-card__body" style="border-bottom:1px solid var(--color-border);padding:var(--space-3) var(--space-5);">
        <form method="get" class="filter-form" style="display:flex;gap:.75rem;flex-wrap:wrap;">
            <input class="form-input" name="search" value="<?= e($search) ?>" placeholder="Name or SKU…" style="flex:1;min-width:160px;">
            <select class="form-input" name="cat_id" style="width:auto;"><option value="">All Categories</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat_id==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
            <button type="submit" class="btn btn--secondary">Filter</button>
            <a href="?" class="btn btn--secondary">Reset</a>
        </form>
    </div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>SKU</th><th>Name</th><th>Category</th><th>Stock</th><th>Unit</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><code><?= e($p['sku']) ?></code></td>
                <td><?= e($p['name']) ?></td>
                <td><?= e($p['category_name'] ?? '—') ?></td>
                <td style="font-weight:600;"><?= e($p['quantity_in_stock']) ?></td>
                <td><?= e($p['unit']) ?></td>
                <td><?= $p['unit_price'] !== null ? e(format_currency($p['unit_price'])) : '—' ?></td>
                <td><span class="badge <?= $p['is_active'] ? 'badge--success' : 'badge--neutral' ?>"><?= $p['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td style="display:flex;gap:.4rem;">
                    <a href="<?= e(app_base_url()) ?>/admin/products/edit.php?id=<?= (int)$p['id'] ?>" class="btn btn--secondary" style="padding:.2rem .5rem;font-size:.8rem;">Edit</a>
                    <form method="post" action="<?= e(app_base_url()) ?>/admin/products/delete.php" onsubmit="return confirm('Deactivate this product?');">
                        <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn--danger" style="padding:.2rem .5rem;font-size:.8rem;"><?= $p['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($products)): ?><tr><td colspan="8" style="text-align:center;">No products found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pg['total_pages'] > 1): ?>
    <div class="content-card__body" style="display:flex;gap:.5rem;justify-content:center;">
        <?php for ($i = 1; $i <= $pg['total_pages']; $i++): ?><a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&cat_id=<?= $cat_id ?>" class="btn <?= $i===$pg['current_page']?'btn--primary':'btn--secondary' ?>" style="padding:.25rem .6rem;"><?= $i ?></a><?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
