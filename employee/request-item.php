<?php
/**
 * employee/request-item.php — Submit a new inventory request
 */
$page_title = 'New Request';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('employee');

$uid = (int) $_SESSION['user_id'];
$pdo = get_db_connection();

$errors = [];

// Pre-fill product if coming from products page
$prefill_product_id = sanitize_int($_GET['product_id'] ?? 0);

// Load active products for dropdown
$q = sanitize_string($_GET['q'] ?? '');
$params = [];
$where = "WHERE p.is_active = 1";
if ($q) {
    $where .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $params = ["%$q%", "%$q%"];
}

$stmt = $pdo->prepare(
    "SELECT p.id, p.sku, p.name, p.unit, p.quantity_in_stock, c.name AS category
     FROM products p LEFT JOIN categories c ON c.id = p.category_id
     $where ORDER BY p.name"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expired. Please refresh and try again.';
    } else {
        $notes       = sanitize_string($_POST['notes'] ?? '');
        $product_ids = $_POST['product_id'] ?? [];
        $quantities  = $_POST['quantity'] ?? [];
        $item_notes  = $_POST['item_notes'] ?? [];

        $items = [];
        foreach ($product_ids as $idx => $pid) {
            $pid = (int)$pid;
            $qty = (int)($quantities[$idx] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $items[] = ['product_id' => $pid, 'quantity' => $qty, 'notes' => sanitize_string($item_notes[$idx] ?? '')];
            }
        }

        if (empty($items)) { $errors[] = 'Please add at least one item with a valid product and quantity.'; }

        if (empty($errors)) {
            $ref_no = 'REQ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            try {
                $pdo->beginTransaction();

                $ins = $pdo->prepare('INSERT INTO requests (user_id, reference_no, status, notes) VALUES (:uid, :ref, :st, :notes)');
                $ins->execute([':uid' => $uid, ':ref' => $ref_no, ':st' => 'pending', ':notes' => $notes ?: null]);
                $req_id = (int)$pdo->lastInsertId();

                $ins_item = $pdo->prepare('INSERT INTO request_items (request_id, product_id, quantity_requested, notes) VALUES (:rid, :pid, :qty, :notes)');
                foreach ($items as $item) {
                    $ins_item->execute([':rid' => $req_id, ':pid' => $item['product_id'], ':qty' => $item['quantity'], ':notes' => $item['notes'] ?: null]);
                }

                $pdo->commit();

                log_activity($uid, 'submit_request', "Submitted request $ref_no with " . count($items) . " item(s).", 'request', $req_id);
                send_notification_to_role('store', 'New Request Submitted', "Request $ref_no has been submitted and is pending your review.", app_base_url() . '/store/requests/view.php?id=' . $req_id);
                
                // Check stock levels and notify employee about low/out-of-stock items
                $low_stock_items = [];
                foreach ($items as $item) {
                    $stock_check = $pdo->prepare('SELECT name, quantity_in_stock FROM products WHERE id = ?');
                    $stock_check->execute([$item['product_id']]);
                    $prod_info = $stock_check->fetch(PDO::FETCH_ASSOC);
                    if ($prod_info && (int)$prod_info['quantity_in_stock'] < $item['quantity']) {
                        $stock_qty = (int)$prod_info['quantity_in_stock'];
                        if ($stock_qty <= 0) {
                            $low_stock_items[] = "{$prod_info['name']} (Out of Stock)";
                        } else {
                            $low_stock_items[] = "{$prod_info['name']} (only {$stock_qty} available, you requested {$item['quantity']})";
                        }
                    }
                }
                if (!empty($low_stock_items)) {
                    $item_list = implode(', ', $low_stock_items);
                    send_notification(
                        $uid,
                        'Low Stock Warning',
                        "Your request $ref_no includes items with insufficient stock: $item_list. The store will review availability.",
                        app_base_url() . '/employee/requests/view.php?id=' . $req_id
                    );
                }
                
                flash_message('success', "Request $ref_no submitted successfully.");
                header('Location: ' . app_base_url() . '/employee/requests.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('[GCM] request submit failed: ' . $e->getMessage());
                $errors[] = 'Failed to submit request. Please try again.';
            }
        }
    }
}
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header page-header--flex" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:var(--space-6);">
    <div>
        <h1 class="text-display color-primary" style="margin-bottom:var(--space-1); letter-spacing:-0.02em; font-weight:700;">Request New Item</h1>
        <p class="text-body-lg color-on-surface-var" style="margin:0;">Select items and submit your request batch for store review.</p>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<?php if (!empty($errors)): ?>
<div class="alert alert--error" style="margin-bottom:var(--space-4);">
    <?php foreach ($errors as $err): ?><p style="margin:.25rem 0;"><?= e($err) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" id="request-form" style="display:grid; grid-template-columns: 2fr 1fr; gap:var(--space-6); align-items:start;">
    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">

    <!-- Left Column: Selection Form -->
    <div style="display:flex; flex-direction:column; gap:var(--space-5);">
        <div class="content-card">
            <div class="content-card__header" style="border-bottom:1px solid var(--color-outline-variant);">
                <div style="display:flex; align-items:center; gap:var(--space-2);">
                    <span class="material-symbols-outlined color-primary">search</span>
                    <h2 class="content-card__title color-primary">Find & Select Item</h2>
                </div>
            </div>
            <div class="content-card__body">
                <div class="form-group" style="margin-bottom:var(--space-4);">
                    <label class="form-label">Product</label>
                    <select class="form-input" id="item-product" style="font-size:var(--text-body-md-size); padding:10px;">
                        <option value="">— Select a product to add —</option>
                        <?php foreach ($products as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" data-name="<?= e($p['name']) ?>" data-stock="<?= e($p['quantity_in_stock']) ?>">
                            <?= e($p['name']) ?> (<?= e($p['sku']) ?>) — <?= e($p['quantity_in_stock']) ?> <?= e($p['unit']) ?> avail.
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:var(--space-4); margin-bottom:var(--space-4);">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Quantity Required</label>
                        <input type="number" class="form-input" id="item-qty" min="1" value="1" style="padding:10px;">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Item Purpose / Notes</label>
                        <input type="text" class="form-input" id="item-note" placeholder="Optional context..." style="padding:10px;">
                    </div>
                </div>

                <!-- Live stock warning shown when quantity exceeds available stock -->
                <div id="stock-warning" style="display:none; margin-bottom:var(--space-4); padding:10px 14px; border-radius:var(--radius-md); border-left:4px solid #ea580c; background:#fff7ed; color:#9a3412; font-size:var(--text-body-sm-size); display:none; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined" style="font-size:18px;">warning</span>
                    <span id="stock-warning-text"></span>
                </div>

                <div style="display:flex; justify-content:flex-end;">
                    <button type="button" class="btn btn--secondary" id="add-to-batch-btn" style="padding:10px 20px;">
                        <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add to Request Batch
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Request Summary Sidebar -->
    <div class="content-card" style="position:sticky; top:20px; background-color:var(--color-surface-container-low); margin:0;">
        <div class="content-card__header" style="border-bottom:1px solid var(--color-outline-variant); display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:var(--space-2);">
                <span class="material-symbols-outlined color-primary">receipt_long</span>
                <h2 class="content-card__title color-primary">Request Summary</h2>
            </div>
            <span class="badge" id="batch-count" style="background:var(--color-surface-container); color:var(--color-on-surface-variant); border:1px solid var(--color-outline-variant);">0 Items</span>
        </div>
        
        <div class="content-card__body" style="display:flex; flex-direction:column; gap:var(--space-4);">
            <!-- Dynamic Batch List -->
            <ul id="batch-list" style="list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:var(--space-2);">
                <!-- Empty state shown by default -->
                <li id="empty-batch-msg" style="text-align:center; padding:var(--space-6) 0; color:var(--color-outline); border:2px dashed var(--color-outline-variant); border-radius:var(--radius-md);">
                    <span class="material-symbols-outlined" style="font-size:32px; opacity:0.5;">shopping_cart</span>
                    <p style="margin:var(--space-2) 0 0; font-size:var(--text-body-md-size);">Your batch is empty.</p>
                </li>
            </ul>

            <div class="form-group" style="margin-top:var(--space-4);">
                <label class="form-label">Overall Request Notes (optional)</label>
                <textarea class="form-input" name="notes" rows="2" placeholder="Explain the overall reason for this request..."><?= e($_POST['notes'] ?? '') ?></textarea>
            </div>

            <div style="display:flex; flex-direction:column; gap:var(--space-3); margin-top:var(--space-4);">
                <button type="submit" class="btn btn--primary" style="justify-content:center; padding:12px; font-size:var(--text-title-md-size);">
                    <span class="material-symbols-outlined" style="font-size:20px;">send</span> Submit Request
                </button>
                <a href="<?= e(app_base_url()) ?>/employee/requests.php" class="btn btn--secondary" style="justify-content:center;">Cancel</a>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.getElementById('add-to-batch-btn');
    const productSelect = document.getElementById('item-product');
    const qtyInput = document.getElementById('item-qty');
    const noteInput = document.getElementById('item-note');
    
    const batchList = document.getElementById('batch-list');
    const emptyMsg = document.getElementById('empty-batch-msg');
    const batchCountSpan = document.getElementById('batch-count');
    
    let itemCount = 0;
    
    const stockWarningDiv = document.getElementById('stock-warning');
    const stockWarningText = document.getElementById('stock-warning-text');
    
    // Live stock check — shows warning as soon as qty exceeds available stock
    function checkStockWarning() {
        if (!productSelect.value) {
            stockWarningDiv.style.display = 'none';
            return;
        }
        const opt = productSelect.options[productSelect.selectedIndex];
        const stock = parseInt(opt.getAttribute('data-stock') || '0', 10);
        const qty = parseInt(qtyInput.value, 10) || 0;
        
        if (qty > stock && stock <= 0) {
            stockWarningText.innerHTML = '<strong>Out of Stock!</strong> This item is currently unavailable in the store (0 in stock).';
            stockWarningDiv.style.display = 'flex';
            stockWarningDiv.style.background = '#fee2e2';
            stockWarningDiv.style.color = '#991b1b';
            stockWarningDiv.style.borderLeftColor = '#dc2626';
        } else if (qty > stock) {
            stockWarningText.innerHTML = '<strong>Low Stock!</strong> Only <strong>' + stock + '</strong> available in store, but you are requesting <strong>' + qty + '</strong>. Your request may be partially fulfilled or rejected.';
            stockWarningDiv.style.display = 'flex';
            stockWarningDiv.style.background = '#fff7ed';
            stockWarningDiv.style.color = '#9a3412';
            stockWarningDiv.style.borderLeftColor = '#ea580c';
        } else {
            stockWarningDiv.style.display = 'none';
        }
    }
    
    productSelect.addEventListener('change', checkStockWarning);
    qtyInput.addEventListener('input', checkStockWarning);

    // Handle initial items if post validation failed (prefill)
    <?php
    $initial_items = $_POST['product_id'] ?? ($prefill_product_id ? [$prefill_product_id] : []);
    $initial_qtys  = $_POST['quantity'] ?? [];
    $initial_notes = $_POST['item_notes'] ?? [];
    foreach ($initial_items as $idx => $pid):
        if ($pid > 0):
            // We need the name - we'll just set it via JS on load by looking at the select
    ?>
        setTimeout(() => {
            productSelect.value = "<?= (int)$pid ?>";
            qtyInput.value = "<?= (int)($initial_qtys[$idx] ?? 1) ?>";
            noteInput.value = "<?= e(addslashes($initial_notes[$idx] ?? '')) ?>";
            if (productSelect.selectedIndex > 0) addBtn.click();
        }, 100);
    <?php 
        endif;
    endforeach; 
    ?>

    addBtn.addEventListener('click', function() {
        if (!productSelect.value) {
            alert('Please select a product first.');
            return;
        }
        
        const pid = productSelect.value;
        const opt = productSelect.options[productSelect.selectedIndex];
        const pName = opt.getAttribute('data-name');
        const stock = parseInt(opt.getAttribute('data-stock') || '0', 10);
        const qty = parseInt(qtyInput.value, 10);
        const note = noteInput.value;
        
        // Remove empty state if present
        if (emptyMsg) emptyMsg.style.display = 'none';
        
        itemCount++;
        batchCountSpan.textContent = itemCount + ' Item' + (itemCount !== 1 ? 's' : '');
        
        // Determine stock warning
        let stockWarning = '';
        if (qty > stock && stock <= 0) {
            stockWarning = `
                <div style="margin-top:6px; padding:6px 10px; background:#fee2e2; border-radius:4px; border-left:3px solid #dc2626; display:flex; align-items:center; gap:6px; font-size:12px; color:#991b1b;">
                    <span class="material-symbols-outlined" style="font-size:14px;">block</span>
                    <strong>Out of Stock</strong> — This item is currently unavailable
                </div>`;
        } else if (qty > stock) {
            stockWarning = `
                <div style="margin-top:6px; padding:6px 10px; background:#fff7ed; border-radius:4px; border-left:3px solid #ea580c; display:flex; align-items:center; gap:6px; font-size:12px; color:#9a3412;">
                    <span class="material-symbols-outlined" style="font-size:14px;">warning</span>
                    <strong>Low Stock</strong> — Only ${stock} available, you requested ${qty}
                </div>`;
        }
        
        // Create list item UI
        const li = document.createElement('li');
        li.style.cssText = 'background:var(--color-surface-container-lowest); padding:var(--space-3); border:1px solid var(--color-outline-variant); border-radius:var(--radius-md); display:flex; justify-content:space-between; align-items:flex-start; gap:var(--space-2);';
        
        // Add UI content + Hidden Inputs for Form Submission
        li.innerHTML = `
            <div style="flex:1;">
                <p style="margin:0; font-weight:600; color:var(--color-on-surface); font-size:var(--text-body-md-size);">${pName}</p>
                <p style="margin:4px 0 0; color:var(--color-on-surface-variant); font-size:var(--text-body-sm-size);">Qty: <strong>${qty}</strong> ${note ? '— ' + note : ''}</p>
                ${stockWarning}
                
                <input type="hidden" name="product_id[]" value="${pid}">
                <input type="hidden" name="quantity[]" value="${qty}">
                <input type="hidden" name="item_notes[]" value="${note}">
            </div>
            <button type="button" class="remove-item-btn" style="background:none; border:none; cursor:pointer; color:var(--color-error); padding:4px;" title="Remove">
                <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
            </button>
        `;
        
        batchList.appendChild(li);
        
        // Reset inputs
        productSelect.value = '';
        qtyInput.value = '1';
        noteInput.value = '';
        stockWarningDiv.style.display = 'none';
        productSelect.focus();
    });
    
    // Delegate deletion
    batchList.addEventListener('click', function(e) {
        const btn = e.target.closest('.remove-item-btn');
        if (btn) {
            btn.closest('li').remove();
            itemCount--;
            batchCountSpan.textContent = itemCount + ' Item' + (itemCount !== 1 ? 's' : '');
            if (itemCount === 0 && emptyMsg) {
                emptyMsg.style.display = 'block';
            }
        }
    });
});
</script>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
