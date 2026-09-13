<?php
/**
 * employee/requests/view.php — View a single request
 */
$page_title = 'View Request';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('employee');

$uid    = (int) $_SESSION['user_id'];
$pdo    = get_db_connection();
$req_id = sanitize_int($_GET['id'] ?? 0);

// Load request — must belong to this user
$stmt = $pdo->prepare('SELECT r.*, u.full_name AS reviewer_name FROM requests r LEFT JOIN users u ON u.id = r.reviewed_by WHERE r.id = ? AND r.user_id = ?');
$stmt->execute([$req_id, $uid]);
$req = $stmt->fetch();

if (!$req) {
    flash_message('error', 'Request not found or access denied.');
    header('Location: ' . app_base_url() . '/employee/requests.php');
    exit;
}

// Handle cancel action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash_message('error', 'Session expired.');
    } elseif (isset($_POST['cancel']) && $req['status'] === 'pending') {
        $pdo->prepare("UPDATE requests SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'")->execute([$req_id, $uid]);
        log_activity($uid, 'cancel_request', "Cancelled request {$req['reference_no']}.", 'request', $req_id);
        send_notification_to_role('manager', 'Request Cancelled', "Employee {$_SESSION['full_name']} cancelled pending request {$req['reference_no']}.", app_base_url() . '/manager/requests.php');
        flash_message('success', 'Request cancelled.');
        header('Location: ' . app_base_url() . '/employee/requests.php');
        exit;
    } elseif (isset($_POST['complete']) && $req['status'] === 'issued') {
        $pdo->prepare("UPDATE requests SET status = 'completed' WHERE id = ? AND user_id = ? AND status = 'issued'")->execute([$req_id, $uid]);
        log_activity($uid, 'complete_request', "Confirmed receipt of request {$req['reference_no']}.", 'request', $req_id);
        send_notification_to_role('store', 'Request Completed', "Employee {$_SESSION['full_name']} confirmed receipt of request {$req['reference_no']}.", app_base_url() . '/store/requests/view.php?id=' . $req_id);
        flash_message('success', 'Request marked as completed. Thank you!');
        header('Location: ' . app_base_url() . '/employee/dashboard.php');
        exit;
    }
}

// Load items
$items_stmt = $pdo->prepare(
    'SELECT ri.*, p.name AS product_name, p.sku, p.unit FROM request_items ri JOIN products p ON p.id = ri.product_id WHERE ri.request_id = ?'
);
$items_stmt->execute([$req_id]);
$items = $items_stmt->fetchAll();
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-3);">
        <div>
            <h1 class="page-header__title">Request <code><?= e($req['reference_no']) ?></code></h1>
            <p class="page-header__subtitle">Submitted <?= e(format_datetime($req['created_at'])) ?></p>
        </div>
        <a href="<?= e(app_base_url()) ?>/employee/requests.php" class="btn btn--secondary">← Back to Requests</a>
    </div>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>

<div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
    <div class="content-card">
        <div class="content-card__header"><h2 class="content-card__title">Status</h2></div>
        <div class="content-card__body">
            <p><strong>Current Status:</strong> <span class="badge <?= e(get_status_badge_class($req['status'])) ?>"><?= e(ucfirst($req['status'])) ?></span></p>
            <?php if ($req['reviewer_name']): ?>
            <p><strong>Reviewed by:</strong> <?= e($req['reviewer_name']) ?> on <?= e(format_datetime($req['reviewed_at'])) ?></p>
            <?php endif; ?>
            <?php if ($req['status'] === 'rejected' && $req['rejection_reason']): ?>
            <div style="margin-top:var(--space-3);padding:var(--space-3);background:#fee2e2;border-radius:var(--radius-md);border-left:4px solid #dc2626;">
                <strong style="color:#dc2626;">Rejection Reason:</strong><br>
                <?= e($req['rejection_reason']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="content-card">
        <div class="content-card__header"><h2 class="content-card__title">Notes</h2></div>
        <div class="content-card__body">
            <?= $req['notes'] ? e($req['notes']) : '<span style="color:var(--color-text-secondary);">No notes.</span>' ?>
        </div>
    </div>
</div>

<div class="content-card" style="margin-bottom:var(--space-5);">
    <div class="content-card__header"><h2 class="content-card__title">Items Requested</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Product</th><th>SKU</th><th>Qty Requested</th><th>Qty Issued</th><th>Unit</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['product_name']) ?></td>
                <td><code><?= e($item['sku']) ?></code></td>
                <td><?= e($item['quantity_requested']) ?></td>
                <td><?= $item['quantity_issued'] !== null ? e($item['quantity_issued']) : '<span style="color:var(--color-text-secondary);">—</span>' ?></td>
                <td><?= e($item['unit']) ?></td>
                <td style="font-size:.85rem;color:var(--color-text-secondary);"><?= e($item['notes'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($req['status'] === 'pending'): ?>
<form method="post" onsubmit="return confirm('Cancel this request?');">
    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
    <button type="submit" name="cancel" class="btn btn--danger">Cancel Request</button>
</form>
<?php endif; ?>

<?php if ($req['status'] === 'issued'): ?>
<div class="content-card" style="background-color:var(--color-surface-bright); border-color:var(--color-primary); text-align:center;">
    <h3 class="text-title-md color-primary" style="margin-bottom:var(--space-2);">Your items are ready!</h3>
    <p class="text-body-md" style="margin-bottom:var(--space-4);">The Store has issued your requested items. Please confirm that you have received them to complete this request.</p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
        <button type="submit" name="complete" class="btn btn--primary" style="padding:12px 24px; font-size:var(--text-title-md-size);">
            <span class="material-symbols-outlined">check_circle</span> Confirm Receipt
        </button>
    </form>
</div>
<?php endif; ?>

</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
