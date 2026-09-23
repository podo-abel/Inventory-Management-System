<?php
/**
 * store/requests/view.php — Review, approve/reject, and process requests
 *
 * The Store role now handles the full request lifecycle:
 *   1. Review pending requests (check stock availability, approve/reject)
 *   2. Process approved requests (issue stock)
 *
 * Stock availability is reported for each item so the store can
 * indicate whether items are available or not.
 */
$page_title = 'Review Request';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('store');

$uid    = (int) $_SESSION['user_id'];
$pdo    = get_db_connection();
$req_id = sanitize_int($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT r.*, u.full_name AS employee_name, u.email AS employee_email FROM requests r JOIN users u ON u.id = r.user_id WHERE r.id = ?');
$stmt->execute([$req_id]);
$req = $stmt->fetch();

if (!$req) { flash_message('error', 'Request not found.'); header('Location: ' . app_base_url() . '/store/requests.php'); exit; }

$items = $pdo->prepare('SELECT ri.*, p.name AS product_name, p.sku, p.unit, p.quantity_in_stock FROM request_items ri JOIN products p ON p.id = ri.product_id WHERE ri.request_id = ?');
$items->execute([$req_id]);
$items = $items->fetchAll();

$errors = [];

// ── Handle POST Actions ────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expired.';
    } else {
        $action = sanitize_string($_POST['action'] ?? '');

        // ── APPROVE (pending → approved) ────────────────────────────────
        if ($action === 'approve' && $req['status'] === 'pending') {
            $pdo->prepare("UPDATE requests SET status = 'store_approved' WHERE id = ?")
                ->execute([$req_id]);
            log_activity($uid, 'store_approve_request', "Store approved request {$req['reference_no']} pending manager confirmation.", 'request', $req_id);

            // Notify Employee
            send_notification($req['user_id'], 'Request Verified by Store', "Your request {$req['reference_no']} has been verified by the store and is awaiting manager confirmation.", app_base_url() . '/employee/requests/view.php?id=' . $req_id);
            
            // Notify Managers
            $m_stmt = $pdo->query("SELECT id FROM users WHERE role = 'manager'");
            while ($mgr = $m_stmt->fetch()) {
                send_notification($mgr['id'], 'Request Awaiting Confirmation', "Request {$req['reference_no']} has been verified by the store and needs your confirmation.", app_base_url() . '/manager/requests/view.php?id=' . $req_id);
            }

            flash_message('success', "Request {$req['reference_no']} approved. Awaiting manager confirmation.");
            header('Location: ' . app_base_url() . '/store/requests.php'); exit;

        // ── REJECT (pending → rejected) ─────────────────────────────────
        } elseif ($action === 'reject' && $req['status'] === 'pending') {
            $reason = sanitize_string($_POST['rejection_reason'] ?? '');
            if (empty($reason)) {
                $errors[] = 'A rejection reason is required.';
            } else {
                $pdo->prepare("UPDATE requests SET status = 'rejected', rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
                    ->execute([$reason, $uid, $req_id]);
                log_activity($uid, 'reject_request', "Rejected request {$req['reference_no']}: $reason", 'request', $req_id);

                // Notify Employee
                send_notification($req['user_id'], 'Request Rejected', "Your request {$req['reference_no']} was rejected. Reason: $reason", app_base_url() . '/employee/requests/view.php?id=' . $req_id);

                flash_message('success', "Request {$req['reference_no']} rejected.");
                header('Location: ' . app_base_url() . '/store/requests.php'); exit;
            }

        // ── ISSUE (approved → issued) ───────────────────────────────────
        } elseif ($action === 'issue' && $req['status'] === 'approved') {
            $qty_issue = $_POST['qty_issue'] ?? [];

            // Validate
            foreach ($items as $item) {
                $issue_qty = (int)($qty_issue[$item['id']] ?? 0);
                if ($issue_qty < 0) { $errors[] = "Invalid quantity for {$item['product_name']}."; }
                if ($issue_qty > $item['quantity_in_stock']) { $errors[] = "Cannot issue {$issue_qty} of {$item['product_name']} — only {$item['quantity_in_stock']} in stock."; }
            }

            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();

                    foreach ($items as $item) {
                        $issue_qty = (int)($qty_issue[$item['id']] ?? 0);
                        if ($issue_qty <= 0) continue;

                        $pdo->prepare('UPDATE products SET quantity_in_stock = quantity_in_stock - ? WHERE id = ? AND quantity_in_stock >= ?')->execute([$issue_qty, $item['product_id'], $issue_qty]);

                        $qty_before = (int)$item['quantity_in_stock'];
                        $qty_after = $qty_before - $issue_qty;

                        $pdo->prepare('INSERT INTO stock_movements (product_id, movement_type, quantity, quantity_before, quantity_after, reference_type, reference_id, notes, performed_by) VALUES (?,?,?,?,?,?,?,?,?)')->execute([$item['product_id'], 'issue', $issue_qty, $qty_before, $qty_after, 'request', $req_id, "Issued for request {$req['reference_no']}", $uid]);

                        $pdo->prepare('UPDATE request_items SET quantity_issued = ? WHERE id = ?')->execute([$issue_qty, $item['id']]);
                    }

                    $pdo->prepare("UPDATE requests SET status = 'issued', issued_by = ?, issued_at = NOW() WHERE id = ?")->execute([$uid, $req_id]);
                    $pdo->commit();

                    // Trigger low stock notifications for issued products if needed
                    foreach ($items as $item) {
                        $issue_qty = (int)($qty_issue[$item['id']] ?? 0);
                        if ($issue_qty > 0) {
                            check_and_notify_low_stock($item['product_id']);
                        }
                    }

                    log_activity($uid, 'issue_request', "Issued stock for request {$req['reference_no']}.", 'request', $req_id);

                    // Check if any items were partially fulfilled
                    $is_partial = false;
                    foreach ($items as $item) {
                        $issue_qty = (int)($qty_issue[$item['id']] ?? 0);
                        if ($issue_qty < $item['quantity_requested']) {
                            $is_partial = true;
                            break;
                        }
                    }

                    if ($is_partial) {
                        send_notification($req['user_id'], 'Request Partially Issued', "Your request {$req['reference_no']} has been processed but some items had insufficient stock. Please confirm receipt.", app_base_url() . '/employee/requests/view.php?id=' . $req_id);
                    } else {
                        send_notification($req['user_id'], 'Request Issued', "Your request {$req['reference_no']} is fully issued and ready. Please confirm receipt.", app_base_url() . '/employee/requests/view.php?id=' . $req_id);
                    }

                    flash_message('success', "Request {$req['reference_no']} issued successfully.");
                    header('Location: ' . app_base_url() . '/store/requests.php?status=approved'); exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log('[GCM] issue_request error: ' . $e->getMessage());
                    $errors[] = 'Failed to process. Please try again.';
                }
            }
        }
    }
}

// ── Stock Availability Summary (for pending review) ────────────────────────
$all_available = true;
$none_available = true;
foreach ($items as $item) {
    if ((int)$item['quantity_in_stock'] < (int)$item['quantity_requested']) {
        $all_available = false;
    }
    if ((int)$item['quantity_in_stock'] > 0) {
        $none_available = false;
    }
}
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header page-header--flex" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:var(--space-6);">
    <div>
        <h1 class="text-display color-primary" style="margin-bottom:var(--space-1); letter-spacing:-0.02em; font-weight:700;">
            <?php if ($req['status'] === 'pending'): ?>Review Request
            <?php elseif ($req['status'] === 'approved'): ?>Process Approved Request
            <?php else: ?>Request Details<?php endif; ?>
        </h1>
        <p class="text-body-lg color-on-surface-var" style="margin:0;">Reference: <code style="color:var(--color-secondary); font-size:16px;"><?= e($req['reference_no']) ?></code> &mdash; From <?= e($req['employee_name']) ?></p>
    </div>
    <a href="<?= e(app_base_url()) ?>/store/requests.php" class="btn btn--secondary">
        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back
    </a>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if (!empty($errors)): ?><div class="alert alert--error" style="margin-bottom:var(--space-4);"><?php foreach ($errors as $err): ?><p style="margin:.2rem 0;"><?= e($err) ?></p><?php endforeach; ?></div><?php endif; ?>

<?php // ─── PENDING: Review & Approve/Reject ──────────────────────────────────── ?>
<?php if ($req['status'] === 'pending'): ?>

<!-- Stock Availability Report Banner -->
<?php if ($none_available): ?>
<div class="alert alert--error" style="margin-bottom:var(--space-4); display:flex; align-items:center; gap:var(--space-3);">
    <span class="material-symbols-outlined" style="font-size:24px;">inventory</span>
    <div>
        <strong>No Items Available</strong> — None of the requested items are currently in stock. Consider rejecting this request or waiting for stock replenishment.
    </div>
</div>
<?php elseif (!$all_available): ?>
<div class="alert alert--warning" style="margin-bottom:var(--space-4); display:flex; align-items:center; gap:var(--space-3);">
    <span class="material-symbols-outlined" style="font-size:24px;">warning</span>
    <div>
        <strong>Partial Availability</strong> — Some requested items have insufficient stock. Review the availability column below before approving.
    </div>
</div>
<?php else: ?>
<div class="alert alert--success" style="margin-bottom:var(--space-4); display:flex; align-items:center; gap:var(--space-3);">
    <span class="material-symbols-outlined" style="font-size:24px;">check_circle</span>
    <div>
        <strong>All Items Available</strong> — All requested items are in stock and ready to be issued.
    </div>
</div>
<?php endif; ?>

<div class="form-grid" style="display:grid; grid-template-columns: 7fr 5fr; gap:var(--space-6); align-items:start;">
    
    <!-- Left Column: Requested Items with Availability Report -->
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
                        <th>Availability</th>
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
                        <div style="display:flex; align-items:center; gap:4px; font-size:var(--text-body-sm-size); font-weight:600; <?= (int)$item['quantity_in_stock'] < (int)$item['quantity_requested'] ? 'color:var(--color-error);' : '' ?>">
                            <span class="material-symbols-outlined" style="font-size:16px; color:var(--color-on-surface-variant);">inventory_2</span>
                            <?= e($item['quantity_in_stock']) ?> <?= e($item['unit']) ?>
                        </div>
                    </td>
                    <td>
                        <?php if ((int)$item['quantity_in_stock'] <= 0): ?>
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600; background-color:#fee2e2; color:#991b1b;">
                            <span class="material-symbols-outlined" style="font-size:14px;">block</span> No Item
                        </span>
                        <?php elseif ((int)$item['quantity_in_stock'] < (int)$item['quantity_requested']): ?>
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600; background-color:#ffedd5; color:#9a3412;">
                            <span class="material-symbols-outlined" style="font-size:14px;">warning</span> Insufficient (<?= e($item['quantity_in_stock']) ?> available)
                        </span>
                        <?php else: ?>
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600; background-color:#d1fae5; color:#065f46;">
                            <span class="material-symbols-outlined" style="font-size:14px;">check_circle</span> In Stock
                        </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right Column: Review Panel -->
    <div class="content-card" style="margin:0; position:sticky; top:84px;">
        <div class="content-card__header" style="background-color:var(--color-surface-bright); border-bottom:1px solid var(--color-outline-variant); flex-direction:column; align-items:flex-start; padding:var(--space-4);">
            <div style="display:flex; justify-content:space-between; width:100%; margin-bottom:var(--space-2);">
                <div>
                    <span style="font-family:var(--font-family-code); color:var(--color-secondary); font-size:var(--text-body-sm-size); font-weight:600;"><?= e($req['reference_no']) ?></span>
                    <h3 class="text-headline-md" style="margin:0;">Request Review</h3>
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
        
        <!-- Approve / Reject Actions -->
        <div class="content-card__body">
            <div style="display:flex; flex-direction:column; gap:var(--space-4);">
                <form method="post" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn--primary" style="width:100%; justify-content:center; padding:12px; font-size:var(--text-title-md-size);" onclick="return confirm('Approve this request? It will be queued for stock issuance.')">
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
        </div>
    </div>
</div>

<?php // ─── APPROVED: Issue Stock ────────────────────────────────────────────── ?>
<?php elseif ($req['status'] === 'approved'): ?>

<div class="form-grid" style="display:grid; grid-template-columns: 8fr 4fr; gap:var(--space-6); align-items:start;">
    
    <!-- Left: Items to Issue -->
    <div class="content-card" style="margin:0;">
        <div class="content-card__header" style="background-color:var(--color-surface-bright); border-bottom:1px solid var(--color-outline-variant); display:flex; justify-content:space-between; align-items:center;">
            <h2 class="content-card__title">Items to Issue</h2>
            <span class="badge <?= e(get_status_badge_class($req['status'])) ?>"><?= e(ucwords(str_replace('_', ' ', $req['status']))) ?></span>
        </div>
        
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="issue">
            <div class="content-card__body" style="padding:0; overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Requested</th>
                            <th>In Stock</th>
                            <th style="width:120px;">Qty to Issue</th>
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
                        <td><span style="font-weight:700; font-size:16px;"><?= e($item['quantity_requested']) ?></span> <?= e($item['unit']) ?></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:4px; font-weight:600; <?= $item['quantity_in_stock'] < $item['quantity_requested'] ? 'color:var(--color-error);' : '' ?>">
                                <span class="material-symbols-outlined" style="font-size:16px;">inventory_2</span>
                                <?= e($item['quantity_in_stock']) ?>
                            </div>
                        </td>
                        <td>
                            <?php $issue_amount = min((int)$item['quantity_requested'], (int)$item['quantity_in_stock']); ?>
                            <input type="hidden" name="qty_issue[<?= (int)$item['id'] ?>]" value="<?= $issue_amount ?>">
                            <strong style="font-size:16px; color:var(--color-primary);"><?= $issue_amount ?></strong>
                        </td>
                        <td>
                            <?php if ($item['quantity_in_stock'] >= $item['quantity_requested']): ?>
                            <span style="display:inline-flex; align-items:center; padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600; background-color:#d1fae5; color:#065f46;">Sufficient</span>
                            <?php else: ?>
                            <span style="display:inline-flex; align-items:center; padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600; background-color:#ffedd5; color:#9a3412;">Insufficient</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="content-card__body" style="background-color:var(--color-surface-container-low); display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn--primary" style="padding:12px 24px; font-size:var(--text-title-md-size);" onclick="return confirm('Confirm issuing these items? This will deduct stock permanently.')">
                    <span class="material-symbols-outlined">outbox</span> Complete Issuance
                </button>
            </div>
        </form>
    </div>

    <!-- Right: Context & Info -->
    <div style="display:flex; flex-direction:column; gap:var(--space-5);">
        <div class="content-card" style="margin:0;">
            <div class="content-card__header" style="background-color:var(--color-surface-bright); border-bottom:1px solid var(--color-outline-variant);">
                <h3 class="content-card__title text-title-md">Timeline</h3>
            </div>
            <div class="content-card__body">
                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:var(--space-4);">
                    <li style="display:flex; gap:var(--space-3); align-items:flex-start;">
                        <span class="material-symbols-outlined color-success" style="font-size:24px;">check_circle</span>
                        <div>
                            <p style="margin:0; font-weight:600;">Request Submitted</p>
                            <p style="margin:0; font-size:var(--text-body-sm-size); color:var(--color-on-surface-variant);"><?= e(format_datetime($req['created_at'])) ?></p>
                        </div>
                    </li>
                    <li style="display:flex; gap:var(--space-3); align-items:flex-start;">
                        <span class="material-symbols-outlined color-success" style="font-size:24px;">check_circle</span>
                        <div>
                            <p style="margin:0; font-weight:600;">Store Approved</p>
                            <p style="margin:0; font-size:var(--text-body-sm-size); color:var(--color-on-surface-variant);"><?= e(format_datetime($req['reviewed_at'])) ?></p>
                        </div>
                    </li>
                    <li style="display:flex; gap:var(--space-3); align-items:flex-start;">
                        <span class="material-symbols-outlined" style="font-size:24px; color:var(--color-outline);">pending</span>
                        <div>
                            <p style="margin:0; font-weight:600; color:var(--color-on-surface-variant);">Stock Issued</p>
                            <p style="margin:0; font-size:var(--text-body-sm-size); color:var(--color-on-surface-variant);">Pending Action</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php // ─── OTHER STATUSES: Read-Only View ──────────────────────────────────── ?>
<?php else: ?>

<div class="form-grid" style="display:grid; grid-template-columns: 8fr 4fr; gap:var(--space-6); align-items:start;">
    <div class="content-card" style="margin:0;">
        <div class="content-card__header" style="background-color:var(--color-surface-bright); border-bottom:1px solid var(--color-outline-variant); display:flex; justify-content:space-between; align-items:center;">
            <h2 class="content-card__title">Request Items</h2>
            <span class="badge <?= e(get_status_badge_class($req['status'])) ?>"><?= e(ucwords(str_replace('_', ' ', $req['status']))) ?></span>
        </div>
        <div class="content-card__body" style="padding:0; overflow-x:auto;">
            <table class="table">
                <thead><tr><th>Product</th><th>Requested</th><th>Issued</th><th>Unit</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;"><?= e($item['product_name']) ?></div>
                        <div style="font-size:var(--text-body-sm-size); color:var(--color-on-surface-variant); font-family:var(--font-family-code);">SKU: <?= e($item['sku']) ?></div>
                    </td>
                    <td><?= e($item['quantity_requested']) ?></td>
                    <td><strong style="color:var(--color-success); font-size:16px;"><?= $item['quantity_issued'] !== null ? e($item['quantity_issued']) : '—' ?></strong></td>
                    <td><?= e($item['unit']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right: Timeline -->
    <div style="display:flex; flex-direction:column; gap:var(--space-5);">
        <div class="content-card" style="margin:0;">
            <div class="content-card__header" style="background-color:var(--color-surface-bright); border-bottom:1px solid var(--color-outline-variant);">
                <h3 class="content-card__title text-title-md">Timeline</h3>
            </div>
            <div class="content-card__body">
                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:var(--space-4);">
                    <li style="display:flex; gap:var(--space-3); align-items:flex-start;">
                        <span class="material-symbols-outlined color-success" style="font-size:24px;">check_circle</span>
                        <div>
                            <p style="margin:0; font-weight:600;">Request Submitted</p>
                            <p style="margin:0; font-size:var(--text-body-sm-size); color:var(--color-on-surface-variant);"><?= e(format_datetime($req['created_at'])) ?></p>
                        </div>
                    </li>
                    <?php if ($req['status'] === 'rejected'): ?>
                    <li style="display:flex; gap:var(--space-3); align-items:flex-start;">
                        <span class="material-symbols-outlined" style="font-size:24px; color:var(--color-error);">cancel</span>
                        <div>
                            <p style="margin:0; font-weight:600; color:var(--color-error);">Rejected</p>
                            <p style="margin:0; font-size:var(--text-body-sm-size); color:var(--color-on-surface-variant);"><?= e(format_datetime($req['reviewed_at'])) ?></p>
                            <?php if ($req['rejection_reason']): ?>
                            <p style="margin-top:4px; padding:8px; background:var(--color-error-container); border-radius:var(--radius-sm); font-size:var(--text-body-sm-size); border:1px solid rgba(186,26,26,0.2);"><?= e($req['rejection_reason']) ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php else: ?>
                    <li style="display:flex; gap:var(--space-3); align-items:flex-start;">
                        <span class="material-symbols-outlined color-success" style="font-size:24px;">check_circle</span>
                        <div>
                            <p style="margin:0; font-weight:600;">Store Approved</p>
                            <p style="margin:0; font-size:var(--text-body-sm-size); color:var(--color-on-surface-variant);"><?= $req['reviewed_at'] ? e(format_datetime($req['reviewed_at'])) : 'Approved' ?></p>
                        </div>
                    </li>
                    <li style="display:flex; gap:var(--space-3); align-items:flex-start;">
                        <span class="material-symbols-outlined" style="font-size:24px; color: <?= in_array($req['status'], ['issued', 'completed']) ? 'var(--color-success)' : 'var(--color-outline)' ?>;">
                            <?= in_array($req['status'], ['issued', 'completed']) ? 'check_circle' : 'pending' ?>
                        </span>
                        <div>
                            <p style="margin:0; font-weight:600; color: <?= in_array($req['status'], ['issued', 'completed']) ? 'var(--color-on-surface)' : 'var(--color-on-surface-variant)' ?>;">Stock Issued</p>
                            <p style="margin:0; font-size:var(--text-body-sm-size); color:var(--color-on-surface-variant);"><?= $req['issued_at'] ? e(format_datetime($req['issued_at'])) : 'Pending' ?></p>
                        </div>
                    </li>
                    <?php if ($req['status'] === 'completed'): ?>
                    <li style="display:flex; gap:var(--space-3); align-items:flex-start;">
                        <span class="material-symbols-outlined color-success" style="font-size:24px;">check_circle</span>
                        <div>
                            <p style="margin:0; font-weight:600;">Receipt Confirmed</p>
                            <p style="margin:0; font-size:var(--text-body-sm-size); color:var(--color-on-surface-variant);">Completed</p>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
