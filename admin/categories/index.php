<?php
$page_title = 'Product Categories';
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
$pdo = get_db_connection();

$q = sanitize_string($_GET['q'] ?? '');
$params = [];
$where = '';
if ($q) {
    $where = "WHERE c.name LIKE ? OR c.description LIKE ?";
    $params = ["%$q%", "%$q%"];
}

$cats = $pdo->prepare("SELECT c.*, COUNT(p.id) product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.is_active=1 $where GROUP BY c.id ORDER BY c.name");
$cats->execute($params);
$cats = $cats->fetchAll();

// Stats
$total_cats = count($cats);
$empty_cats = 0;
$total_prods = 0;
foreach($cats as $c) {
    if ($c['product_count'] == 0) $empty_cats++;
    $total_prods += $c['product_count'];
}

?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header page-header--flex" style="display:flex;align-items:center;justify-content:space-between; margin-bottom:var(--space-6);">
    <div>
        <h1 class="text-display color-primary" style="margin-bottom:var(--space-1); letter-spacing:-0.02em; font-weight:700;">Categories</h1>
        <p class="text-body-lg color-on-surface-var" style="margin:0;">Organize and manage your product groupings.</p>
    </div>
    <a href="create.php" class="btn btn--primary" style="padding:10px 20px;"><span class="material-symbols-outlined">add</span> New Category</a>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>

<!-- Stats Grid -->
<div class="stats-grid" style="margin-bottom:var(--space-5);">
    <div class="stat-card">
        <div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">category</span></div>
        <div><p class="stat-card__label">Total Categories</p><p class="stat-card__value"><?= $total_cats ?></p></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon-wrap stat-card__icon-wrap--green"><span class="material-symbols-outlined">inventory_2</span></div>
        <div><p class="stat-card__label">Total Categorized Products</p><p class="stat-card__value"><?= $total_prods ?></p></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon-wrap stat-card__icon-wrap--yellow"><span class="material-symbols-outlined">inbox</span></div>
        <div><p class="stat-card__label">Empty Categories</p><p class="stat-card__value"><?= $empty_cats ?></p></div>
    </div>
</div>

<div class="content-card">
    <div class="content-card__header" style="display:flex; justify-content:space-between; align-items:center;">
        <h2 class="content-card__title">Category Directory</h2>
        <form method="GET" class="filter-form" style="display:flex; gap:10px;">
            <input type="text" name="q" class="form-input" placeholder="Search categories..." value="<?= e($q) ?>" style="max-width:250px;">
            <button type="submit" class="btn btn--secondary">Search</button>
            <?php if($q): ?><a href="index.php" class="btn btn--secondary">Clear</a><?php endif; ?>
        </form>
    </div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Category Name</th><th>Description</th><th>Active Products</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach($cats as $cat): ?>
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="material-symbols-outlined" style="color:var(--color-primary-container);">folder</span>
                        <strong style="font-size:16px; color:var(--color-on-surface);"><?= e($cat['name']) ?></strong>
                    </div>
                </td>
                <td style="color:var(--color-on-surface-variant); max-width:400px;"><?= e($cat['description'] ?? '—') ?></td>
                <td>
                    <span class="badge <?= $cat['product_count'] > 0 ? 'badge--success' : 'badge--neutral' ?>">
                        <?= e($cat['product_count']) ?> Item(s)
                    </span>
                </td>
                <td>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <a href="edit.php?id=<?= e($cat['id']) ?>" class="btn btn--secondary" style="padding:4px 10px;font-size:13px;">Edit</a>
                        <?php if($cat['product_count']==0): ?>
                        <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Permanently delete this category?');">
                            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= e($cat['id']) ?>">
                            <button type="submit" class="btn btn--danger" style="padding:4px 10px;font-size:13px;">Delete</button>
                        </form>
                        <?php else: ?>
                        <span style="font-size:12px;color:var(--color-text-secondary);"><span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">lock</span> In Use</span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($cats)): ?>
            <tr><td colspan="4" style="text-align:center; padding:30px; color:var(--color-text-secondary);">No categories found matching your criteria.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
