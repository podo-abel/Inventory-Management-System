<?php
/**
 * manager/requests/view.php — Review a single request
 */
$page_title = 'Review Request';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('manager');

$uid    = (int) $_SESSION['user_id'];
$pdo    = get_db_connection();
$req_id = sanitize_int($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT r.*, u.full_name AS employee_name, u.email AS employee_email FROM requests r JOIN users u ON u.id = r.user_id WHERE r.id = ?');
$stmt->execute([$req_id]);
$req = $stmt->fetch();

if (!$req) {
    flash_message('error', 'Request not found.');
    header('Location: ' . app_base_url() . '/manager/requests/index.php'); exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expired.';
    } elseif ($req['status'] !== 'pending') {
        $errors[] = 'This request has already been reviewed.';
    } else {
        $action = sanitize_string($_POST['action'] ?? '');
        if ($action === 'approve') {
            $pdo->prepare("UPDATE requests SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")->execute([$uid, $req_id]);
            log_activity($uid, 'approve_request', "Approved request {$req['reference_no']}.", 'request', $req_id);
            
            // Notify Employee
            send_notification($req['user_id'], 'Request Approved', "Your request {$req['reference_no']} has been approved.", app_base_url() . '/employee/requests/view.php?id=' . $req_id);
            // Notify Store
            send_notification_to_role('store', 'Approved Request Ready for Processing', "Request {$req['reference_no']} is approved and waiting to be issued.", app_base_url() . '/store/requests/view.php?id=' . $req_id);
            
            flash_message('success', "Request {$req['reference_no']} approved.");
            header('Location: ' . app_base_url() . '/manager/requests/index.php'); exit;
        } elseif ($action === 'reject') {
            $reason = sanitize_string($_POST['rejection_reason'] ?? '');
            if (empty($reason)) { $errors[] = 'A rejection reason is required.'; }
            else {
                $pdo->prepare("UPDATE requests SET status = 'rejected', rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")->execute([$reason, $uid, $req_id]);
                log_activity($uid, 'reject_request', "Rejected request {$req['reference_no']}: $reason", 'request', $req_id);
                
                // Notify Employee
                send_notification($req['user_id'], 'Request Rejected', "Your request {$req['reference_no']} was rejected.", app_base_url() . '/employee/requests/view.php?id=' . $req_id);
                
                flash_message('success', "Request {$req['reference_no']} rejected.");
                header('Location: ' . app_base_url() . '/manager/requests/index.php'); exit;
            }
        }
    }
}

$items = $pdo->prepare('SELECT ri.*, p.name AS product_name, p.sku, p.unit, p.quantity_in_stock FROM request_items ri JOIN products p ON p.id = ri.product_id WHERE ri.request_id = ?');
$items->execute([$req_id]);
$items = $items->fetchAll();
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:var(--space-6);">
    <div>
        <h1 class="text-display color-primary" style="margin-bottom:var(--space-1); letter-spacing:-0.02em; font-weight:700;">Request Approvals</h1>
        <p class="text-body-lg color-on-surface-var" style="margin:0;">Review and manage employee item requests</p>
    </div>
    <a href="<?= e(app_base_url()) ?>/manager/requests/index.php" class="btn btn--secondary">
        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back to All
    </a>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if (!empty($errors)): ?><div class="alert alert--error" style="margin-bottom:var(--space-4);"><?php foreach ($errors as $err): ?><p style="margin:.2rem 0;"><?= e($err) ?></p><?php endforeach; ?></div><?php endif; ?>

<!-- Split Layout -->
<div style="display:grid; grid-template-columns: 7fr 5fr; gap:var(--space-6); align-items:start;">
    
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
                        <th>Current Stock</th>
                        <th>Status</th>
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
                        <div style="display:flex; align-items:center; gap:4px; font-size:var(--text-body-sm-size);">
                            <span class="material-symbols-outlined" style="font-size:16px; color:var(--color-on-surface-variant);">inventory_2</span>
                            Stock: <?= e($item['quantity_in_stock']) ?>
                        </div>
                    </td>
                    <td>
                        <?php if ($item['quantity_in_stock'] >= $item['quantity_requested']): ?>
                        <span style="display:inline-flex; align-items:center; padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600; background-color:#d1fae5; color:#065f46;">Available</span>
                        <?php else: ?>
                        <span style="display:inline-flex; align-items:center; padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600; background-color:#ffedd5; color:#9a3412;">Low Stock</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right Column: Detailed Review Panel -->
    <div class="content-card" style="margin:0; position:sticky; top:84px;">
        <div class="content-card__header" style="background-color:var(--color-surface-bright); border-bottom:1px solid var(--color-outline-variant); flex-direction:column; align-items:flex-start; padding:var(--space-4);">
            <div style="display:flex; justify-content:space-between; width:100%; margin-bottom:var(--space-2);">
                <div>
                    <span style="font-family:var(--font-family-code); color:var(--color-secondary); font-size:var(--text-body-sm-size); font-weight:600;"><?= e($req['reference_no']) ?></span>
                    <h3 class="text-headline-md" style="margin:0;">Request Review</h3>
                </div>
                <span class="badge <?= e(get_status_badge_class($req['status'])) ?>" style="font-size:11px; text-transform:uppercase; letter-spacing:0.05em; padding:4px 8px;"><?= e(ucfirst($req['status'])) ?></span>
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
        
        <!-- Actions -->
        <div class="content-card__body">
            <?php if ($req['status'] === 'pending'): ?>
            <div style="display:flex; flex-direction:column; gap:var(--space-4);">
                <form method="post" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn--primary" style="width:100%; justify-content:center; padding:12px; font-size:var(--text-title-md-size);" onclick="return confirm('Approve this request?')">
                        <span class="material-symbols-outlined" style="font-size:20px;">check_circle</span> Approve Request
                    </button>
                </form>
                
                <hr style="border:none; border-top:1px solid var(--color-outline-variant); margin:0;">
                
                <form method="post" style="margin:0; display:flex; flex-direction:column; gap:var(--space-3);">
                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                    <input type="hidden" name="action" value="reject">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="color:var(--color-error);">Rejection Reason *</label>
                        <textarea class="form-input" name="rejection_reason" rows="2" required placeholder="Explain why this request is being rejected…"><?= e($_POST['rejection_reason'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn--danger" style="width:100%; justify-content:center;">Reject Request</button>
                </form>
            </div>
            <?php elseif ($req['status'] === 'rejected'): ?>
            <div>
                <p class="text-label-md" style="color:var(--color-error); margin-bottom:4px;">Rejection Reason</p>
                <p class="text-body-md" style="color:var(--color-on-surface); margin:0; padding:12px; background:var(--color-error-container); border-radius:var(--radius-sm); border:1px solid rgba(186,26,26,0.2);"><?= e($req['rejection_reason']) ?></p>
            </div>
            <?php else: ?>
            <div style="text-align:center; padding:var(--space-4) 0; color:var(--color-on-surface-variant);">
                <span class="material-symbols-outlined" style="font-size:32px; color:var(--color-success); margin-bottom:8px;">task_alt</span>
                <p style="margin:0; font-weight:500;">This request has been processed.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
