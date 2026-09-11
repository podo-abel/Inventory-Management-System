<?php
/**
 * store/requests.php — Approved requests to process
 */
$page_title = 'Process Requests';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('store');

$pdo    = get_db_connection();
$status = sanitize_string($_GET['status'] ?? 'approved');
$page   = max(1, sanitize_int($_GET['page'] ?? 1));
$per    = 20;

$allowed = ['approved','processing','issued','completed'];
if (!in_array($status, $allowed)) $status = 'approved';

$cnt_stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status = ?");
$cnt_stmt->execute([$status]);
$total = (int)$cnt_stmt->fetchColumn();
$pg = paginate($total, $per, $page);

$stmt = $pdo->prepare(
    "SELECT r.id, r.reference_no, r.status, r.created_at, u.full_name AS employee_name, COUNT(ri.id) AS item_count
     FROM requests r JOIN users u ON u.id = r.user_id LEFT JOIN request_items ri ON ri.request_id = r.id
     WHERE r.status = ?
     GROUP BY r.id ORDER BY r.created_at ASC LIMIT ? OFFSET ?"
);
$stmt->execute([$status, $per, $pg['offset']]);
$requests = $stmt->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Process Requests</h1><p class="page-header__subtitle">Issue approved inventory requests to employees.</p></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__body" style="border-bottom:1px solid var(--color-border);padding:var(--space-3) var(--space-5);">
        <form method="get" class="filter-form" style="display:flex;gap:.75rem;align-items:center;">
            <label class="form-label" style="margin:0;">Show:</label>
            <?php foreach ($allowed as $s): ?><a href="?status=<?= $s ?>" class="btn <?= $status===$s?'btn--primary':'btn--secondary' ?>" style="padding:.3rem .8rem;font-size:.85rem;"><?= ucfirst($s) ?></a><?php endforeach; ?>
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
                <td><span class="badge <?= e(get_status_badge_class($req['status'])) ?>"><?= e(ucfirst($req['status'])) ?></span></td>
                <td>
                    <a href="<?= e(app_base_url()) ?>/store/requests/view.php?id=<?= (int)$req['id'] ?>" class="btn <?= $req['status']==='approved'?'btn--primary':'btn--secondary' ?>" style="padding:.25rem .75rem;font-size:.8rem;">
                        <?= $req['status']==='approved' ? 'Process' : 'View' ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($requests)): ?><tr><td colspan="6" style="text-align:center;">No <?= e($status) ?> requests.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
