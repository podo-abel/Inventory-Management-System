<?php
/**
 * finance/purchases/create.php — Create a purchase order
 */
$page_title = 'New Purchase Order';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('finance');

$uid  = (int)$_SESSION['user_id'];
$pdo  = get_db_connection();
$errors = [];

$suppliers = $pdo->query('SELECT id, name FROM suppliers WHERE is_active=1 ORDER BY name')->fetchAll();
$products  = $pdo->query('SELECT p.id, p.sku, p.name, p.unit, p.unit_price, p.quantity_in_stock FROM products p WHERE p.is_active=1 ORDER BY p.name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Session expired.'; }
    else {
        $supplier_id   = sanitize_int($_POST['supplier_id'] ?? 0);
        $expected_date = sanitize_string($_POST['expected_date'] ?? '');
        $notes         = sanitize_string($_POST['notes'] ?? '');
        $product_ids   = $_POST['product_id'] ?? [];
        $quantities    = $_POST['quantity'] ?? [];
        $unit_prices   = $_POST['unit_price'] ?? [];

        if ($supplier_id <= 0) $errors[] = 'Please select a supplier.';

        $items = [];
        foreach ($product_ids as $idx => $pid) {
            $pid  = (int)$pid;
            $qty  = (int)($quantities[$idx] ?? 0);
            $price = (float)($unit_prices[$idx] ?? 0);
            if ($pid > 0 && $qty > 0 && $price >= 0) {
                $items[] = ['product_id' => $pid, 'quantity' => $qty, 'unit_price' => $price, 'total' => $qty * $price];
            }
        }
        if (empty($items)) $errors[] = 'Add at least one product line.';

        if (empty($errors)) {
            $total_amount = array_sum(array_column($items, 'total'));
            $ref_no = 'PO-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            try {
                $pdo->beginTransaction();
                $pdo->prepare('INSERT INTO purchases (reference_no, supplier_id, status, total_amount, notes, expected_date, ordered_by) VALUES (?,?,?,?,?,?,?)')->execute([$ref_no, $supplier_id, 'draft', $total_amount, $notes ?: null, $expected_date ?: null, $uid]);
                $po_id = (int)$pdo->lastInsertId();
                $ins_item = $pdo->prepare('INSERT INTO purchase_items (purchase_id, product_id, quantity_ordered, unit_price, total_price) VALUES (?,?,?,?,?)');
                foreach ($items as $item) {
                    $ins_item->execute([$po_id, $item['product_id'], $item['quantity'], $item['unit_price'], $item['total']]);
                }
                $pdo->commit();
                log_activity($uid, 'create_purchase', "Created PO $ref_no for supplier #$supplier_id.", 'purchase', $po_id);
                flash_message('success', "Purchase Order $ref_no created.");
                header('Location: ' . app_base_url() . '/finance/purchases/view.php?id=' . $po_id); exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('[GCM] create_purchase error: ' . $e->getMessage());
                $errors[] = 'Failed to create PO. Please try again.';
            }
        }
    }
}
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">New Purchase Order</h1></div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if(!empty($errors)): ?><div class="alert alert--error" style="margin-bottom:var(--space-4);"><?php foreach($errors as $e_): ?><p style="margin:.2rem 0;"><?= e($e_) ?></p><?php endforeach; ?></div><?php endif; ?>
<form method="post" id="po-form">
<input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
<div class="content-card" style="margin-bottom:var(--space-5);">
    <div class="content-card__header"><h2 class="content-card__title">Order Details</h2></div>
    <div class="content-card__body form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);">
        <div class="form-group"><label class="form-label">Supplier *</label>
            <select class="form-input" name="supplier_id" required>
                <option value="">— Select Supplier —</option>
                <?php foreach ($suppliers as $sup): ?><option value="<?= (int)$sup['id'] ?>" <?= (int)($_POST['supplier_id'] ?? 0)===(int)$sup['id']?'selected':'' ?>><?= e($sup['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label class="form-label">Expected Delivery Date</label><input class="form-input" type="date" name="expected_date" value="<?= e($_POST['expected_date'] ?? '') ?>"></div>
        <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Notes</label><textarea class="form-input" name="notes" rows="2"><?= e($_POST['notes'] ?? '') ?></textarea></div>
    </div>
</div>
<div class="content-card" style="margin-bottom:var(--space-5);">
    <div class="content-card__header"><h2 class="content-card__title">Line Items</h2><button type="button" class="btn btn--secondary" id="add-line-btn"><span class="material-symbols-outlined">add</span> Add Line</button></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table" id="lines-table">
            <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th><th></th></tr></thead>
            <tbody id="lines-body">
            <?php $init_pids = $_POST['product_id'] ?? [0]; foreach ($init_pids as $idx => $ipid): ?>
            <tr class="line-row">
                <td><select class="form-input prod-select" name="product_id[]" required style="min-width:200px;">
                    <option value="">— Product —</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" data-price="<?= (float)($p['unit_price'] ?? 0) ?>" <?= (int)$ipid===(int)$p['id']?'selected':'' ?>>
                            <?= e($p['name']) ?> (<?= e($p['sku']) ?>) — <?= $p['quantity_in_stock'] <= 0 ? '⚠️ OUT OF STOCK' : 'In Stock: ' . $p['quantity_in_stock'] ?>
                        </option>
                    <?php endforeach; ?>
                </select></td>
                <td><input class="form-input line-qty" type="number" name="quantity[]" min="1" value="<?= (int)($_POST['quantity'][$idx] ?? 1) ?>" required style="width:80px;" oninput="updateTotal(this)"></td>
                <td><input class="form-input line-price" type="number" name="unit_price[]" min="0" step="0.01" value="<?= number_format((float)($_POST['unit_price'][$idx] ?? 0), 2) ?>" required style="width:100px;" oninput="updateTotal(this)"></td>
                <td class="line-total" style="font-weight:600;">0.00</td>
                <td><button type="button" class="btn btn--danger remove-line" style="padding:.25rem .5rem;">✕</button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="content-card__body" style="text-align:right;font-size:var(--text-lg);font-weight:700;">Order Total: <span id="grand-total">0.00</span></div>
</div>
<div style="display:flex;gap:var(--space-3);">
    <button type="submit" class="btn btn--primary">Create Purchase Order</button>
    <a href="<?= e(app_base_url()) ?>/finance/purchases/index.php" class="btn btn--secondary">Cancel</a>
</div>
</form>
<script>
const productData = {<?php foreach ($products as $p): ?><?= (int)$p['id'] ?>: <?= (float)($p['unit_price'] ?? 0) ?>,<?php endforeach; ?>};
function updateTotal(inp) {
    const row = inp.closest('tr');
    const qty = parseFloat(row.querySelector('.line-qty').value) || 0;
    const price = parseFloat(row.querySelector('.line-price').value) || 0;
    row.querySelector('.line-total').textContent = (qty * price).toFixed(2);
    updateGrand();
}
function updateGrand() {
    let t = 0; document.querySelectorAll('.line-total').forEach(td => t += parseFloat(td.textContent) || 0);
    document.getElementById('grand-total').textContent = t.toFixed(2);
}
document.getElementById('lines-body').addEventListener('change', function(e) {
    if (e.target.classList.contains('prod-select')) {
        const row = e.target.closest('tr');
        const pid = parseInt(e.target.value);
        if (productData[pid]) row.querySelector('.line-price').value = productData[pid].toFixed(2);
        updateTotal(row.querySelector('.line-qty'));
    }
});
document.getElementById('lines-body').addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-line') && document.querySelectorAll('.line-row').length > 1) { e.target.closest('tr').remove(); updateGrand(); }
});
const productOptions = `<?php foreach ($products as $p): ?><option value="<?= (int)$p['id'] ?>" data-price="<?= (float)($p['unit_price'] ?? 0) ?>"><?= e($p['name']) ?> (<?= e($p['sku']) ?>) — <?= $p['quantity_in_stock'] <= 0 ? '⚠️ OUT OF STOCK' : 'In Stock: ' . $p['quantity_in_stock'] ?></option><?php endforeach; ?>`;
document.getElementById('add-line-btn').addEventListener('click', function() {
    const row = document.createElement('tr'); row.className = 'line-row';
    row.innerHTML = `<td><select class="form-input prod-select" name="product_id[]" required style="min-width:200px;"><option value="">— Product —</option>${productOptions}</select></td><td><input class="form-input line-qty" type="number" name="quantity[]" min="1" value="1" required style="width:80px;" oninput="updateTotal(this)"></td><td><input class="form-input line-price" type="number" name="unit_price[]" min="0" step="0.01" value="0.00" required style="width:100px;" oninput="updateTotal(this)"></td><td class="line-total" style="font-weight:600;">0.00</td><td><button type="button" class="btn btn--danger remove-line" style="padding:.25rem .5rem;">✕</button></td>`;
    document.getElementById('lines-body').appendChild(row);
});
updateGrand();
</script>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
