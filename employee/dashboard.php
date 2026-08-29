<?php
/**
 * employee/dashboard.php — Employee Dashboard
 */
$page_title = 'Dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('employee');

$uid = (int) $_SESSION['user_id'];
$pdo = get_db_connection();

$s = $pdo->prepare('SELECT COUNT(*) FROM requests WHERE user_id = ?'); $s->execute([$uid]); $total_requests = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE user_id = ? AND status = 'pending'"); $s->execute([$uid]); $pending_count = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE user_id = ? AND status IN ('approved','processing','issued')"); $s->execute([$uid]); $approved_count = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE user_id = ? AND status = 'completed'"); $s->execute([$uid]); $completed_count = (int)$s->fetchColumn();

$recent_stmt = $pdo->prepare('SELECT r.id, r.reference_no, r.status, r.created_at, COUNT(ri.id) AS item_count FROM requests r LEFT JOIN request_items ri ON ri.request_id = r.id WHERE r.user_id = ? GROUP BY r.id ORDER BY r.created_at DESC LIMIT 5');
$recent_stmt->execute([$uid]);
$recent_requests = $recent_stmt->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1 class="page-header__title">My Dashboard</h1>
    <p class="page-header__subtitle">Welcome back, <?= e($_SESSION['full_name']) ?> &mdash; <?= e(date('l, F j, Y')) ?></p>
</div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="stats-grid">
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">assignment</span></div><div><p class="stat-card__label">Total Requests</p><p class="stat-card__value"><?= e($total_requests) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--yellow"><span class="material-symbols-outlined">pending_actions</span></div><div><p class="stat-card__label">Pending</p><p class="stat-card__value"><?= e($pending_count) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--green"><span class="material-symbols-outlined">check_circle</span></div><div><p class="stat-card__label">Approved / In Progress</p><p class="stat-card__value"><?= e($approved_count) ?></p></div></div>
    <div class="stat-card"><div class="stat-card__icon-wrap stat-card__icon-wrap--purple"><span class="material-symbols-outlined">task_alt</span></div><div><p class="stat-card__label">Completed</p><p class="stat-card__value"><?= e($completed_count) ?></p></div></div>
</div>
<div class="content-card">
    <div class="content-card__header">
        <h2 class="content-card__title">Recent Requests</h2>
        <div style="display:flex;gap:.5rem;">
            <a href="<?= e(app_base_url()) ?>/employee/request-item.php" class="btn btn--primary"><span class="material-symbols-outlined">add_shopping_cart</span> New Request</a>
            <a href="<?= e(app_base_url()) ?>/employee/requests.php" class="btn btn--secondary">View All</a>
        </div>
    </div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Reference</th><th>Date</th><th>Items</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($recent_requests as $req): ?>
            <tr>
                <td><code><?= e($req['reference_no']) ?></code></td>
                <td><?= e(format_date($req['created_at'])) ?></td>
                <td><?= e($req['item_count']) ?> item(s)</td>
                <td><span class="badge <?= e(get_status_badge_class($req['status'])) ?>"><?= e(ucfirst($req['status'])) ?></span></td>
                <td><a href="<?= e(app_base_url()) ?>/employee/requests/view.php?id=<?= (int)$req['id'] ?>" class="btn btn--secondary" style="padding:.25rem .75rem;font-size:.8rem;">View</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recent_requests)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--color-text-secondary);">No requests yet. <a href="<?= e(app_base_url()) ?>/employee/request-item.php">Submit your first request.</a></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
