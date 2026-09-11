<?php
/**
 * store/requests/view.php — Process an approved request
 */
$page_title = 'Process Request';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('store');

$uid    = (int) $_SESSION['user_id'];
$pdo    = get_db_connection();
$req_id = sanitize_int($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT r.*, u.full_name AS employee_name FROM requests r JOIN users u ON u.id = r.user_id WHERE r.id = ?');
$stmt->execute([$req_id]);
$req = $stmt->fetch();

if (!$req) { flash_message('error', 'Request not found.'); header('Location: ' . app_base_url() . '/store/requests.php'); exit; }

$items = $pdo->prepare('SELECT ri.*, p.name AS product_name, p.sku, p.unit, p.quantity_in_stock FROM request_items ri JOIN products p ON p.id = ri.product_id WHERE ri.request_id = ?');
$items->execute([$req_id]);
$items = $items->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $req['status'] === 'approved') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Session expired.'; }
    else {
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
                header('Location: ' . app_base_url() . '/store/requests.php'); exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('[GCM] issue_request error: ' . $e->getMessage());
                $errors[] = 'Failed to process. Please try again.';
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
        <h1 class="text-display color-primary" style="margin-bottom:var(--space-1); letter-spacing:-0.02em; font-weight:700;">Process Approved Request</h1>
        <p class="text-body-lg color-on-surface-var" style="margin:0;">Reference: <code style="color:var(--color-secondary); font-size:16px;"><?= e($req['reference_no']) ?></code> &mdash; From <?= e($req['employee_name']) ?></p>
    </div>
    <a href="<?= e(app_base_url()) ?>/store/requests.php" class="btn btn--secondary">
        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back
    </a>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if (!empty($errors)): ?><div class="alert alert--error" style="margin-bottom:var(--space-4);"><?php foreach ($errors as $err): ?><p style="margin:.2rem 0;"><?= e($err) ?></p><?php endforeach; ?></div><?php endif; ?>

<div class="form-grid" style="display:grid; grid-template-columns: 8fr 4fr; gap:var(--space-6); align-items:start;">
    
    <!-- Left: Items to Issue -->
    <div class="content-card" style="margin:0;">
        <div class="content-card__header" style="background-color:var(--color-surface-bright); border-bottom:1px solid var(--color-outline-variant); display:flex; justify-content:space-between; align-items:center;">
            <h2 class="content-card__title">Items to Issue</h2>
            <span class="badge <?= e(get_status_badge_class($req['status'])) ?>"><?= e(ucfirst($req['status'])) ?></span>
        </div>
        
        <?php if ($req['status'] === 'approved'): ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
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
        <?php else: ?>
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
        <?php endif; ?>
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
                            <p style="margin:0; font-weight:600;">Manager Approved</p>
                            <p style="margin:0; font-size:var(--text-body-sm-size); color:var(--color-on-surface-variant);">Status: Approved</p>
                        </div>
                    </li>
                    <li style="display:flex; gap:var(--space-3); align-items:flex-start;">
                        <span class="material-symbols-outlined" style="font-size:24px; color: <?= $req['status'] === 'issued' ? 'var(--color-success)' : 'var(--color-outline)' ?>;">
                            <?= $req['status'] === 'issued' ? 'check_circle' : 'pending' ?>
                        </span>
                        <div>
                            <p style="margin:0; font-weight:600; color: <?= $req['status'] === 'issued' ? 'var(--color-on-surface)' : 'var(--color-on-surface-variant)' ?>;">Stock Issued</p>
                            <p style="margin:0; font-size:var(--text-body-sm-size); color:var(--color-on-surface-variant);"><?= $req['status'] === 'issued' ? 'Completed' : 'Pending Action' ?></p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
