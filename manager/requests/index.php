<?php
/**
 * manager/requests/index.php — All Employee Requests
 */
$page_title = 'Employee Requests';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('manager');

$pdo    = get_db_connection();
$status = sanitize_string($_GET['status'] ?? '');
$page   = max(1, sanitize_int($_GET['page'] ?? 1));
$per    = 20;

$where  = [];
$params = [];
if ($status !== '') { $where[] = 'r.status = :st'; $params[':st'] = $status; }
$wsql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int)$pdo->prepare("SELECT COUNT(*) FROM requests r $wsql")->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM requests r $wsql")->fetchColumn() : 0;

// Re-execute for count
$cnt_stmt = $pdo->prepare("SELECT COUNT(*) FROM requests r $wsql");
$cnt_stmt->execute($params);
$total = (int)$cnt_stmt->fetchColumn();
$pg = paginate($total, $per, $page);

$list_stmt = $pdo->prepare(
    "SELECT r.id, r.reference_no, r.status, r.created_at,
            u.full_name AS employee_name,
            COUNT(ri.id) AS item_count
     FROM requests r
     JOIN users u ON u.id = r.user_id
     LEFT JOIN request_items ri ON ri.request_id = r.id
     $wsql
     GROUP BY r.id
     ORDER BY FIELD(r.status,'pending','store_approved','approved','processing','issued','completed','rejected','cancelled'), r.created_at DESC
     LIMIT :lim OFFSET :off"
);
if ($status !== '') $list_stmt->bindValue(':st', $status, PDO::PARAM_STR);
$list_stmt->bindValue(':lim', $per, PDO::PARAM_INT);
$list_stmt->bindValue(':off', $pg['offset'], PDO::PARAM_INT);
$list_stmt->execute();
$requests = $list_stmt->fetchAll();

$statuses = ['pending','store_approved','approved','rejected','processing','issued','completed','cancelled'];
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Employee Requests</h1><p class="page-header__subtitle">Monitor employee inventory requests.</p></div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__header">
        <h2 class="content-card__title">All Requests (<?= $total ?>)</h2>
    </div>
    <div class="content-card__body" style="border-bottom:1px solid var(--color-border);padding:var(--space-3) var(--space-5);">
        <form method="get" class="filter-form" style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
            <label class="form-label" style="margin:0;">Status:</label>
            <select class="form-input" name="status" style="width:auto;" onchange="this.form.submit()">
                <option value="">All</option>
                <?php foreach ($statuses as $s): ?>
                <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($status): ?><a href="?" class="btn btn--secondary">Clear</a><?php endif; ?>
        </form>
    </div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Reference</th><th>Employee</th><th>Submitted</th><th>Items</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $req): ?>
            <tr>
                <td><code><?= e($req['reference_no']) ?></code></td>
                <td><?= e($req['employee_name']) ?></td>
                <td><?= e(format_date($req['created_at'])) ?></td>
                <td><?= e($req['item_count']) ?></td>
                <td><span class="badge <?= e(get_status_badge_class($req['status'])) ?>"><?= e(ucwords(str_replace('_', ' ', $req['status']))) ?></span></td>
                <td><a href="<?= e(app_base_url()) ?>/manager/requests/view.php?id=<?= (int)$req['id'] ?>" class="btn <?= $req['status']==='store_approved'?'btn--primary':'btn--secondary' ?>" style="padding:.25rem .75rem;font-size:.8rem;"><?= $req['status']==='store_approved'?'Review':'View' ?></a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($requests)): ?><tr><td colspan="6" style="text-align:center;color:var(--color-text-secondary);">No requests found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pg['total_pages'] > 1): ?>
    <div class="content-card__body" style="display:flex;gap:.5rem;justify-content:center;">
        <?php for ($i = 1; $i <= $pg['total_pages']; $i++): ?>
        <a href="?page=<?= $i ?>&status=<?= e($status) ?>" class="btn <?= $i === $pg['current_page'] ? 'btn--primary' : 'btn--secondary' ?>" style="padding:.25rem .6rem;"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
