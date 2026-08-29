<?php
/**
 * finance/purchases/index.php — Purchase orders list
 */
$page_title = 'Purchase Orders';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('finance');

$pdo = get_db_connection();
$status_f = sanitize_string($_GET['status'] ?? '');
$q = sanitize_string($_GET['q'] ?? '');
$where = []; $params = [];
if ($status_f !== '') { $where[] = 'pu.status = :st'; $params[':st'] = $status_f; }
if ($q !== '') { 
    $where[] = '(pu.reference_no LIKE :q OR s.name LIKE :q2)'; 
    $params[':q'] = "%$q%"; 
    $params[':q2'] = "%$q%"; 
}
$wsql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt = $pdo->prepare("SELECT COUNT(*) FROM purchases pu $wsql"); $cnt->execute($params); $total = (int)$cnt->fetchColumn();
$pg = paginate($total, 20, max(1, sanitize_int($_GET['page'] ?? 1)));

$stmt = $pdo->prepare("SELECT pu.id, pu.reference_no, pu.status, pu.total_amount, pu.created_at, pu.expected_date, s.name AS supplier_name FROM purchases pu LEFT JOIN suppliers s ON s.id = pu.supplier_id $wsql ORDER BY pu.created_at DESC LIMIT :lim OFFSET :off");
if ($status_f !== '') $stmt->bindValue(':st', $status_f, PDO::PARAM_STR);
if ($q !== '') {
    $stmt->bindValue(':q', "%$q%", PDO::PARAM_STR);
    $stmt->bindValue(':q2', "%$q%", PDO::PARAM_STR);
}
$stmt->bindValue(':lim', 20, PDO::PARAM_INT);
$stmt->bindValue(':off', $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$purchases = $stmt->fetchAll();

$statuses = ['draft','pending_approval','approved','ordered','partially_received','received','cancelled'];
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Purchase Orders</h1></div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Purchase Orders (<?= $total ?>)</h2><a href="<?= e(app_base_url()) ?>/finance/purchases/create.php" class="btn btn--primary"><span class="material-symbols-outlined">add</span> New PO</a></div>
    <div class="content-card__body" style="border-bottom:1px solid var(--color-border);padding:var(--space-3) var(--space-5);">
        <form method="get" style="display:flex;gap:.75rem;align-items:center;">
            <select class="form-input" name="status" style="width:auto;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $status_f===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option><?php endforeach; ?>
            </select>
            <?php if ($status_f): ?><a href="?" class="btn btn--secondary">Clear</a><?php endif; ?>
        </form>
    </div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Reference</th><th>Supplier</th><th>Status</th><th>Total</th><th>Expected</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($purchases as $po): ?>
            <tr>
                <td><code><?= e($po['reference_no']) ?></code></td>
                <td><?= e($po['supplier_name'] ?? '—') ?></td>
                <td><span class="badge <?= e(get_status_badge_class($po['status'])) ?>"><?= e(ucfirst(str_replace('_',' ',$po['status']))) ?></span></td>
                <td><?= e(format_currency($po['total_amount'])) ?></td>
                <td><?= e(format_date($po['expected_date'])) ?></td>
                <td><?= e(format_date($po['created_at'])) ?></td>
                <td><a href="<?= e(app_base_url()) ?>/finance/purchases/view.php?id=<?= (int)$po['id'] ?>" class="btn btn--secondary" style="padding:.25rem .6rem;font-size:.8rem;">View</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($purchases)): ?><tr><td colspan="7" style="text-align:center;">No purchase orders.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
