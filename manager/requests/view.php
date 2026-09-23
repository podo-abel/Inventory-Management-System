<?php
/**
 * manager/requests/view.php — View a single request (read-only)
 *
 * Manager can view requests for monitoring purposes but
 * approval/rejection is now handled by the Store role.
 */
$page_title = 'View Request';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('manager');

$pdo    = get_db_connection();
$req_id = sanitize_int($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT r.*, u.full_name AS employee_name, u.email AS employee_email, rev.full_name AS reviewer_name FROM requests r JOIN users u ON u.id = r.user_id LEFT JOIN users rev ON rev.id = r.reviewed_by WHERE r.id = ?');
$stmt->execute([$req_id]);
$req = $stmt->fetch();

if (!$req) {
    flash_message('error', 'Request not found.');
    header('Location: ' . app_base_url() . '/manager/requests/index.php'); exit;
}

$items = $pdo->prepare('SELECT ri.*, p.name AS product_name, p.sku, p.unit, p.quantity_in_stock FROM request_items ri JOIN products p ON p.id = ri.product_id WHERE ri.request_id = ?');
$items->execute([$req_id]);
$items = $items->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expired.';
    } else {
        $action = sanitize_string($_POST['action'] ?? '');
        $uid = (int) $_SESSION['user_id'];

        if ($action === 'confirm' && $req['status'] === 'store_approved') {
            $pdo->prepare("UPDATE requests SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")->execute([$uid, $req_id]);
            log_activity($uid, 'manager_confirm_request', "Manager confirmed request {$req['reference_no']}.", 'request', $req_id);
            send_notification($req['user_id'], 'Request Approved', "Your request {$req['reference_no']} has been approved by the manager and will be issued shortly.", app_base_url() . '/employee/requests/view.php?id=' . $req_id);
            flash_message('success', "Request {$req['reference_no']} has been confirmed.");
            header('Location: ' . app_base_url() . '/manager/requests/index.php'); exit;
        } elseif ($action === 'reject' && $req['status'] === 'store_approved') {
            $reason = sanitize_string($_POST['rejection_reason'] ?? '');
            if (!$reason) $errors[] = 'Rejection reason is required.';
            else {
                $pdo->prepare("UPDATE requests SET status = 'rejected', rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")->execute([$reason, $uid, $req_id]);
                log_activity($uid, 'manager_reject_request', "Manager rejected request {$req['reference_no']}.", 'request', $req_id);
                send_notification($req['user_id'], 'Request Rejected', "Your request {$req['reference_no']} was rejected by the manager.", app_base_url() . '/employee/requests/view.php?id=' . $req_id);
                flash_message('success', "Request {$req['reference_no']} has been rejected.");
                header('Location: ' . app_base_url() . '/manager/requests/index.php'); exit;
            }
        }
    }
}
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header page-header--flex" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:var(--space-6);">
    <div>
        <h1 class="text-display color-primary" style="margin-bottom:var(--space-1); letter-spacing:-0.02em; font-weight:700;">Request Details</h1>
        <p class="text-body-lg color-on-surface-var" style="margin:0;">View employee item request details</p>
    </div>
    <a href="<?= e(app_base_url()) ?>/manager/requests/index.php" class="btn btn--secondary">
        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back to All
    </a>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if (!empty($errors)): foreach($errors as $err): ?><div class="alert alert--error"><?= e($err) ?></div><?php endforeach; endif; ?>

<!-- Split Layout -->
<div class="form-grid" style="display:grid; grid-template-columns: 7fr 5fr; gap:var(--space-6); align-items:start;">
    
    <!-- Left Column: Requested Items Table -->
    <div class="content-card" style="margin:0;">
        <div class="content-card__header" style="background-color:var(--color-surface-bright); border-bottom:1px solid var(--color-outline-variant);">
            <h2 class="content-card__title">Requested Items (<?= count($items) ?>)</h2>
        </div>
        <div class="content-card__body" style="padding:0; overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty Requested</th>
                        <th>Qty Issued</th>
                        <th>Current Stock</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div style="font-weight:600; color:var(--color-on-surface);"><?= e($item['product_name']) ?></div>
                        <div style="font-size:var(--text-body-sm-size); color:var(--color-on-surface-variant); font-family:var(--font-family-code);">SKU: <?= e($item['sku']) ?></div>
                    </td>
                    <td><span style="font-weight:700; font-size:var(--text-title-md-size);"><?= e($item['quantity_requested']) ?></span> <?= e($item['unit']) ?></td>
                    <td>
                        <?php if ($item['quantity_issued'] !== null): ?>
                        <strong style="color:var(--color-success); font-size:16px;"><?= e($item['quantity_issued']) ?></strong> <?= e($item['unit']) ?>
                        <?php else: ?>
                        <span style="color:var(--color-on-surface-variant);">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex; align-items:center; gap:4px; font-size:var(--text-body-sm-size);">
                            <span class="material-symbols-outlined" style="font-size:16px; color:var(--color-on-surface-variant);">inventory_2</span>
                            Stock: <?= e($item['quantity_in_stock']) ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right Column: Request Info Panel (Read-only) -->
    <div class="content-card" style="margin:0; position:sticky; top:84px;">
        <div class="content-card__header" style="background-color:var(--color-surface-bright); border-bottom:1px solid var(--color-outline-variant); flex-direction:column; align-items:flex-start; padding:var(--space-4);">
            <div style="display:flex; justify-content:space-between; width:100%; margin-bottom:var(--space-2);">
                <div>
                    <span style="font-family:var(--font-family-code); color:var(--color-secondary); font-size:var(--text-body-sm-size); font-weight:600;"><?= e($req['reference_no']) ?></span>
                    <h3 class="text-headline-md" style="margin:0;">Request Details</h3>
                </div>
                <span class="badge <?= e(get_status_badge_class($req['status'])) ?>" style="font-size:11px; text-transform:uppercase; letter-spacing:0.05em; padding:4px 8px;"><?= e(ucwords(str_replace('_', ' ', $req['status']))) ?></span>
            </div>
            
            <!-- Employee Info Box -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-4); width:100%; padding:var(--space-3); background-color:var(--color-surface-container-low); border:1px solid rgba(0,0,0,0.05); border-radius:var(--radius-sm); margin-top:var(--space-2);">
                <div>
                    <p class="text-label-md color-on-surface-var" style="margin-bottom:2px;">Employee</p>
                    <p class="text-title-md color-on-surface" style="margin:0;"><?= e($req['employee_name']) ?></p>
                    <p class="text-body-sm color-on-surface-var" style="margin:0;"><?= e($req['employee_email']) ?></p>
                </div>
                <div>
                    <p class="text-label-md color-on-surface-var" style="margin-bottom:2px;">Submitted</p>
                    <p class="text-body-md color-on-surface" style="margin:0; font-weight:500;"><?= e(format_date($req['created_at'])) ?></p>
                </div>
            </div>
            
            <?php if ($req['notes']): ?>
            <div style="width:100%; margin-top:var(--space-3);">
                <p class="text-label-md color-on-surface-var" style="margin-bottom:2px;">Purpose / Notes</p>
                <p class="text-body-md color-on-surface" style="margin:0;"><?= e($req['notes']) ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Status Info -->
        <div class="content-card__body">
            <?php if ($req['status'] === 'pending'): ?>
            <div style="text-align:center; padding:var(--space-4) 0; color:var(--color-on-surface-variant);">
                <span class="material-symbols-outlined" style="font-size:32px; color:var(--color-warning); margin-bottom:8px;">hourglass_top</span>
                <p style="margin:0; font-weight:500;">Awaiting Store Review</p>
                <p class="text-body-sm" style="margin-top:4px; color:var(--color-on-surface-variant);">This request is pending review by the Store team.</p>
            </div>
            <?php elseif ($req['status'] === 'store_approved'): ?>
            <div style="padding:var(--space-2) 0;">
                <div style="text-align:center; padding-bottom:var(--space-4); color:var(--color-on-surface-variant);">
                    <span class="material-symbols-outlined" style="font-size:32px; color:var(--color-warning); margin-bottom:8px;">verified</span>
                    <p style="margin:0; font-weight:500;">Verified by Store</p>
                    <p class="text-body-sm" style="margin-top:4px; color:var(--color-on-surface-variant);">This request was verified by the store and needs your confirmation to proceed.</p>
                </div>
                <form method="post" style="display:flex; flex-direction:column; gap:var(--space-3);">
                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                    <button type="submit" name="action" value="confirm" class="btn btn--primary" style="width:100%; justify-content:center;">
                        <span class="material-symbols-outlined" style="font-size:18px;">check</span> Confirm Request
                    </button>
                </form>
                <form method="post" style="margin-top:var(--space-3); padding-top:var(--space-3); border-top:1px solid var(--color-outline-variant); display:flex; flex-direction:column; gap:var(--space-2);">
                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                    <input type="hidden" name="action" value="reject">
                    <label class="form-label" style="font-size:var(--text-body-sm-size);">Reason for Rejection</label>
                    <textarea name="rejection_reason" class="form-input" rows="2" placeholder="Required if rejecting" required></textarea>
                    <button type="submit" class="btn btn--secondary" style="color:var(--color-error); border-color:var(--color-error); justify-content:center;">
                        <span class="material-symbols-outlined" style="font-size:18px;">close</span> Reject Request
                    </button>
                </form>
            </div>
            <?php elseif ($req['status'] === 'rejected'): ?>
            <div>
                <p class="text-label-md" style="color:var(--color-error); margin-bottom:4px;">Rejection Reason</p>
                <p class="text-body-md" style="color:var(--color-on-surface); margin:0; padding:12px; background:var(--color-error-container); border-radius:var(--radius-sm); border:1px solid rgba(186,26,26,0.2);"><?= e($req['rejection_reason']) ?></p>
                <?php if ($req['reviewer_name']): ?>
                <p class="text-body-sm" style="margin-top:8px; color:var(--color-on-surface-variant);">Reviewed by <?= e($req['reviewer_name']) ?> on <?= e(format_datetime($req['reviewed_at'])) ?></p>
                <?php endif; ?>
            </div>
            <?php elseif ($req['status'] === 'approved'): ?>
            <div style="text-align:center; padding:var(--space-4) 0; color:var(--color-on-surface-variant);">
                <span class="material-symbols-outlined" style="font-size:32px; color:var(--color-success); margin-bottom:8px;">check_circle</span>
                <p style="margin:0; font-weight:500;">Approved — Awaiting Issuance</p>
                <?php if ($req['reviewer_name']): ?>
                <p class="text-body-sm" style="margin-top:4px;">Approved by <?= e($req['reviewer_name']) ?> on <?= e(format_datetime($req['reviewed_at'])) ?></p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div style="text-align:center; padding:var(--space-4) 0; color:var(--color-on-surface-variant);">
                <span class="material-symbols-outlined" style="font-size:32px; color:var(--color-success); margin-bottom:8px;">task_alt</span>
                <p style="margin:0; font-weight:500;">This request has been processed.</p>
                <?php if ($req['reviewer_name']): ?>
                <p class="text-body-sm" style="margin-top:4px;">Reviewed by <?= e($req['reviewer_name']) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
