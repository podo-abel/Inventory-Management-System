<?php
/**
 * finance/suppliers/index.php — Supplier list
 */
$page_title = 'Suppliers';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('finance');

$pdo       = get_db_connection();
$suppliers = $pdo->query('SELECT * FROM suppliers ORDER BY name')->fetchAll();
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Suppliers</h1></div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Suppliers (<?= count($suppliers) ?>)</h2><a href="<?= e(app_base_url()) ?>/finance/suppliers/create.php" class="btn btn--primary"><span class="material-symbols-outlined">add</span> Add Supplier</a></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Name</th><th>Contact</th><th>Email</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($suppliers as $sup): ?>
            <tr>
                <td style="font-weight:600;"><?= e($sup['name']) ?></td>
                <td><?= e($sup['contact_name'] ?? '—') ?></td>
                <td><?= e($sup['email'] ?? '—') ?></td>
                <td><?= e($sup['phone'] ?? '—') ?></td>
                <td><span class="badge <?= $sup['is_active'] ? 'badge--success' : 'badge--neutral' ?>"><?= $sup['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td style="display:flex;gap:.5rem;">
                    <a href="<?= e(app_base_url()) ?>/finance/suppliers/edit.php?id=<?= (int)$sup['id'] ?>" class="btn btn--secondary" style="padding:.25rem .6rem;font-size:.8rem;">Edit</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($suppliers)): ?><tr><td colspan="6" style="text-align:center;">No suppliers.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
