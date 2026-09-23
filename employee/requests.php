<?php
/**
 * employee/requests.php — My Requests
 */
$page_title = 'My Requests';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('employee');

$uid    = (int) $_SESSION['user_id'];
$pdo    = get_db_connection();
$status = sanitize_string($_GET['status'] ?? '');
$page   = max(1, sanitize_int($_GET['page'] ?? 1));
$per    = 15;

$where  = ['r.user_id = :uid'];
$params = [':uid' => $uid];
if ($status !== '') { $where[] = 'r.status = :st'; $params[':st'] = $status; }

$count_stmt = $pdo->prepare('SELECT COUNT(*) FROM requests r WHERE ' . implode(' AND ', $where));
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pg = paginate($total, $per, $page);

$params[':limit']  = $per;
$params[':offset'] = $pg['offset'];

$stmt = $pdo->prepare(
    'SELECT r.id, r.reference_no, r.status, r.created_at, COUNT(ri.id) AS item_count
     FROM requests r LEFT JOIN request_items ri ON ri.request_id = r.id
     WHERE ' . implode(' AND ', $where) . '
     GROUP BY r.id ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':uid',    $uid, PDO::PARAM_INT);
if ($status !== '') $stmt->bindValue(':st', $status, PDO::PARAM_STR);
$stmt->bindValue(':limit',  $per, PDO::PARAM_INT);
$stmt->bindValue(':offset', $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$requests = $stmt->fetchAll();

$statuses = ['pending','store_approved','approved','rejected','processing','issued','completed','cancelled'];
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1 class="page-header__title">My Requests</h1>
    <p class="page-header__subtitle">Track all your inventory requests.</p>
</div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__header">
        <h2 class="content-card__title">Requests (<?= $total ?>)</h2>
        <a href="<?= e(app_base_url()) ?>/employee/request-item.php" class="btn btn--primary"><span class="material-symbols-outlined">add_shopping_cart</span> New Request</a>
    </div>
    <div class="content-card__body" style="border-bottom:1px solid var(--color-border);padding:var(--space-3) var(--space-5);">
        <form method="get" class="filter-form" style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
            <label class="form-label" style="margin:0;">Filter by Status:</label>
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
            <thead><tr><th>Reference</th><th>Submitted</th><th>Items</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $req): ?>
            <tr>
                <td><code><?= e($req['reference_no']) ?></code></td>
                <td><?= e(format_date($req['created_at'])) ?></td>
                <td><?= e($req['item_count']) ?></td>
                <td><span class="badge <?= e(get_status_badge_class($req['status'])) ?>"><?= e(ucwords(str_replace('_', ' ', $req['status']))) ?></span></td>
                <td><a href="<?= e(app_base_url()) ?>/employee/requests/view.php?id=<?= (int)$req['id'] ?>" class="btn btn--secondary" style="padding:.25rem .75rem;font-size:.8rem;">View</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($requests)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--color-text-secondary);">No requests found.</td></tr>
            <?php endif; ?>
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
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
