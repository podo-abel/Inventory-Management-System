<?php
$page_title = 'Categories';
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
$pdo = get_db_connection();
$cats = $pdo->query("SELECT c.*, COUNT(p.id) product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.is_active=1 GROUP BY c.id ORDER BY c.name")->fetchAll();
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
    <div><h1 class="page-header__title">Categories</h1></div>
    <a href="create.php" class="btn btn--primary"><span class="material-symbols-outlined">add</span> Add Category</a>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__body" style="padding:0;">
        <table class="table"><thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Products</th><th>Actions</th></tr></thead><tbody>
        <?php foreach($cats as $cat): ?>
        <tr>
            <td><?= e($cat['id']) ?></td>
            <td><strong><?= e($cat['name']) ?></strong></td>
            <td><?= e($cat['description'] ?? '—') ?></td>
            <td><?= e($cat['product_count']) ?></td>
            <td>
                <a href="edit.php?id=<?= e($cat['id']) ?>" class="btn btn--secondary" style="padding:2px 8px;font-size:.75rem;">Edit</a>
                <?php if($cat['product_count']==0): ?>
                <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Delete this category?');">
                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= e($cat['id']) ?>">
                    <button type="submit" class="btn btn--danger" style="padding:2px 8px;font-size:.75rem;">Delete</button>
                </form>
                <?php else: ?>
                <span style="font-size:.7rem;color:var(--color-text-secondary);">Has products</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($cats)): ?><tr><td colspan="5" style="text-align:center;">No categories found.</td></tr><?php endif; ?>
        </tbody></table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
