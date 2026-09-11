<?php
/**
 * store/stock-movements.php — Stock movement history
 */
$page_title = 'Stock Movements';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('store');

$pdo      = get_db_connection();
$type_f   = sanitize_string($_GET['type'] ?? '');
$prod_f   = sanitize_int($_GET['product_id'] ?? 0);
$page     = max(1, sanitize_int($_GET['page'] ?? 1));
$per      = 25;

$where  = [];
$params = [];
if ($type_f !== '') { $where[] = 'sm.movement_type = :type'; $params[':type'] = $type_f; }
if ($prod_f > 0)    { $where[] = 'sm.product_id = :pid'; $params[':pid'] = $prod_f; }
$wsql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt_stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_movements sm $wsql");
$cnt_stmt->execute($params);
$total = (int)$cnt_stmt->fetchColumn();
$pg = paginate($total, $per, $page);

$list_params = $params;
$list_params[':lim'] = $per;
$list_params[':off'] = $pg['offset'];

$stmt = $pdo->prepare(
    "SELECT sm.id, sm.movement_type, sm.quantity, sm.notes, sm.created_at, sm.reference_type, sm.reference_id,
            p.name AS product_name, p.unit,
            u.full_name AS performed_name
     FROM stock_movements sm
     JOIN products p ON p.id = sm.product_id
     LEFT JOIN users u ON u.id = sm.performed_by
     $wsql
     ORDER BY sm.created_at DESC
     LIMIT :lim OFFSET :off"
);
foreach ($list_params as $k => $v) {
    if (in_array($k, [':lim',':off',':pid'])) $stmt->bindValue($k, (int)$v, PDO::PARAM_INT);
    else $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->execute();
$movements = $stmt->fetchAll();

$products = $pdo->query('SELECT id, name FROM products WHERE is_active=1 ORDER BY name')->fetchAll();
$type_badge = ['receive'=>'badge--success','issue'=>'badge--info','adjustment'=>'badge--warning','return'=>'badge--neutral'];
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Stock Movements</h1><p class="page-header__subtitle">History of all inventory changes. Total: <?= $total ?></p></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__body" style="border-bottom:1px solid var(--color-border);padding:var(--space-3) var(--space-5);">
        <form method="get" class="filter-form" style="display:flex;gap:.75rem;flex-wrap:wrap;">
            <select class="form-input" name="type" style="width:auto;" onchange="this.form.submit()">
                <option value="">All Types</option>
                <?php foreach (['receive','issue','adjustment','return'] as $t): ?>
                <option value="<?= $t ?>" <?= $type_f===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-input" name="product_id" style="width:auto;" onchange="this.form.submit()">
                <option value="">All Products</option>
                <?php foreach ($products as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $prod_f==(int)$p['id']?'selected':'' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
            </select>
            <?php if ($type_f || $prod_f): ?><a href="?" class="btn btn--secondary">Clear</a><?php endif; ?>
        </form>
    </div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Date</th><th>Product</th><th>Type</th><th>Qty</th><th>Performed By</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach ($movements as $m): ?>
            <tr>
                <td style="white-space:nowrap;font-size:.85rem;"><?= e(format_datetime($m['created_at'])) ?></td>
                <td><?= e($m['product_name']) ?> <small style="color:var(--color-text-secondary);">(<?= e($m['unit']) ?>)</small></td>
                <td><span class="badge <?= $type_badge[$m['movement_type']] ?? 'badge--neutral' ?>"><?= e(ucfirst($m['movement_type'])) ?></span></td>
                <td style="font-weight:600;"><?= e($m['quantity']) ?></td>
                <td><?= e($m['performed_name'] ?? '—') ?></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($m['notes'] ?? '') ?>"><?= e($m['notes'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($movements)): ?><tr><td colspan="6" style="text-align:center;">No movements found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pg['total_pages'] > 1): ?>
    <div class="content-card__body" style="display:flex;gap:.5rem;justify-content:center;">
        <?php for ($i = 1; $i <= $pg['total_pages']; $i++): ?><a href="?page=<?= $i ?>&type=<?= e($type_f) ?>&product_id=<?= $prod_f ?>" class="btn <?= $i===$pg['current_page']?'btn--primary':'btn--secondary' ?>" style="padding:.25rem .6rem;"><?= $i ?></a><?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
