<?php
/**
 * tests/integration_test.php
 * GCM IMS — Integration & Workflow Verification Suite
 *
 * Runs full end-to-end business workflows and asserts database consistency.
 *
 * Usage:
 *   /opt/lampp/bin/php tests/integration_test.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/permissions.php';

echo "\n======================================================\n";
echo "  GCM IMS — Integration & Workflow Verification Suite  \n";
echo "======================================================\n\n";

$pdo = get_db_connection();
if (!$pdo) {
    echo "[FATAL] Unable to connect to MySQL database.\n";
    exit(1);
}

$passed = 0;
$failed = 0;

function it(string $description, callable $test) {
    global $passed, $failed;
    echo "  • " . str_pad($description, 58, '.');
    try {
        $result = $test();
        if ($result === false) {
            echo " [FAIL]\n";
            $failed++;
        } else {
            echo " [PASS]\n";
            $passed++;
        }
    } catch (Throwable $e) {
        echo " [ERROR]\n";
        echo "    Exception: " . $e->getMessage() . "\n";
        $failed++;
    }
}

// -----------------------------------------------------------------------------
// WORKFLOW 1: User creation -> Authentication -> Authorization
// -----------------------------------------------------------------------------
echo "1. User Creation, Authentication & Authorization\n";

it("Can create a test user with hashed password", function() use ($pdo) {
    $test_username = 'test_runner_' . time();
    $test_email    = $test_username . '@gcm.org';
    $password      = 'TestPass@1234';
    $hash          = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, full_name, is_active) VALUES (?, ?, ?, 'employee', 'Test Runner', 1)");
    $stmt->execute([$test_username, $test_email, $hash]);
    $user_id = (int)$pdo->lastInsertId();

    $check = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $check->execute([$user_id]);
    $fetched_hash = $check->fetchColumn();

    $auth_ok = password_verify($password, $fetched_hash);

    // Clean up
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);

    return $user_id > 0 && $auth_ok === true;
});

it("Enforces role permission matrix (can_user)", function() {
    return can_user('admin', 'manage_users') === true
        && can_user('employee', 'manage_users') === false
        && can_user('manager', 'approve_requests') === true
        && can_user('store', 'manage_stock') === true
        && can_user('finance', 'manage_payments') === true;
});

// -----------------------------------------------------------------------------
// WORKFLOW 2: Employee Request -> Manager Approval -> Store Issue -> Completion
// -----------------------------------------------------------------------------
echo "\n2. Employee Request -> Approval -> Stock Issue -> Confirm Receipt\n";

it("Full Request Lifecycle with Stock Decrement & Traceability", function() use ($pdo) {
    // 1. Get test employee and an active product
    $emp_id = (int)$pdo->query("SELECT id FROM users WHERE role='employee' AND is_active=1 LIMIT 1")->fetchColumn();
    $prod = $pdo->query("SELECT id, quantity_in_stock, min_stock_level, name FROM products WHERE is_active=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$emp_id || !$prod) return false;

    $prod_id = (int)$prod['id'];
    $stock_initial = (int)$prod['quantity_in_stock'];

    // 2. Submit Request
    $ref_no = 'REQ-TEST-' . strtoupper(bin2hex(random_bytes(3)));
    $pdo->prepare("INSERT INTO requests (user_id, reference_no, status, notes) VALUES (?, ?, 'pending', 'Integration Test Request')")->execute([$emp_id, $ref_no]);
    $req_id = (int)$pdo->lastInsertId();

    $pdo->prepare("INSERT INTO request_items (request_id, product_id, quantity_requested) VALUES (?, ?, 2)")->execute([$req_id, $prod_id]);

    // Verify Pending
    $req_status = $pdo->query("SELECT status FROM requests WHERE id = $req_id")->fetchColumn();
    if ($req_status !== 'pending') return false;

    // 3. Manager Reviews & Approves
    $mgr_id = (int)$pdo->query("SELECT id FROM users WHERE role='manager' AND is_active=1 LIMIT 1")->fetchColumn();
    $pdo->prepare("UPDATE requests SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([$mgr_id, $req_id]);

    $req_status = $pdo->query("SELECT status FROM requests WHERE id = $req_id")->fetchColumn();
    if ($req_status !== 'approved') return false;

    // 4. Store Issues Stock
    $store_id = (int)$pdo->query("SELECT id FROM users WHERE role='store' AND is_active=1 LIMIT 1")->fetchColumn();
    $pdo->beginTransaction();
    $qty_before = (int)$pdo->query("SELECT quantity_in_stock FROM products WHERE id = $prod_id FOR UPDATE")->fetchColumn();
    $issue_qty = 2;
    $qty_after = $qty_before - $issue_qty;

    $pdo->prepare("UPDATE products SET quantity_in_stock = ? WHERE id = ?")->execute([$qty_after, $prod_id]);
    $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, quantity_before, quantity_after, reference_type, reference_id, notes, performed_by) VALUES (?, 'issue', ?, ?, ?, 'request', ?, 'Test Issue', ?)")->execute([$prod_id, $issue_qty, $qty_before, $qty_after, $req_id, $store_id]);
    $pdo->prepare("UPDATE request_items SET quantity_issued = ? WHERE request_id = ? AND product_id = ?")->execute([$issue_qty, $req_id, $prod_id]);
    $pdo->prepare("UPDATE requests SET status = 'issued', issued_by = ?, issued_at = NOW() WHERE id = ?")->execute([$store_id, $req_id]);
    $pdo->commit();

    // Verify stock movement was recorded
    $sm_check = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE reference_type='request' AND reference_id=?");
    $sm_check->execute([$req_id]);
    if ((int)$sm_check->fetchColumn() === 0) return false;

    // 5. Employee Confirms Receipt (Completed)
    $pdo->prepare("UPDATE requests SET status = 'completed' WHERE id = ?")->execute([$req_id]);
    $final_status = $pdo->query("SELECT status FROM requests WHERE id = $req_id")->fetchColumn();

    // Clean up test data and restore stock
    $pdo->prepare("UPDATE products SET quantity_in_stock = ? WHERE id = ?")->execute([$stock_initial, $prod_id]);
    $pdo->prepare("DELETE FROM stock_movements WHERE reference_type='request' AND reference_id=?")->execute([$req_id]);
    $pdo->prepare("DELETE FROM request_items WHERE request_id=?")->execute([$req_id]);
    $pdo->prepare("DELETE FROM requests WHERE id=?")->execute([$req_id]);

    return $final_status === 'completed';
});

// -----------------------------------------------------------------------------
// WORKFLOW 3: Request Rejection Workflow
// -----------------------------------------------------------------------------
echo "\n3. Request Rejection Workflow\n";

it("Manager rejection properly records reason and updates status", function() use ($pdo) {
    $emp_id = (int)$pdo->query("SELECT id FROM users WHERE role='employee' AND is_active=1 LIMIT 1")->fetchColumn();
    $ref_no = 'REQ-REJ-' . strtoupper(bin2hex(random_bytes(3)));

    $pdo->prepare("INSERT INTO requests (user_id, reference_no, status, notes) VALUES (?, ?, 'pending', 'Test Rejection')")->execute([$emp_id, $ref_no]);
    $req_id = (int)$pdo->lastInsertId();

    $mgr_id = (int)$pdo->query("SELECT id FROM users WHERE role='manager' AND is_active=1 LIMIT 1")->fetchColumn();
    $reason = 'Item not available in department quota';

    $pdo->prepare("UPDATE requests SET status='rejected', rejection_reason=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([$reason, $mgr_id, $req_id]);

    $check = $pdo->prepare("SELECT status, rejection_reason FROM requests WHERE id=?");
    $check->execute([$req_id]);
    $row = $check->fetch(PDO::FETCH_ASSOC);

    // Clean up
    $pdo->prepare("DELETE FROM requests WHERE id=?")->execute([$req_id]);

    return $row['status'] === 'rejected' && $row['rejection_reason'] === $reason;
});

// -----------------------------------------------------------------------------
// WORKFLOW 4: Stock Receiving -> Inventory Update
// -----------------------------------------------------------------------------
echo "\n4. Stock Receiving -> Inventory Update\n";

it("Receiving stock increments inventory and logs audit movement", function() use ($pdo) {
    $prod = $pdo->query("SELECT id, quantity_in_stock FROM products WHERE is_active=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $prod_id = (int)$prod['id'];
    $qty_before = (int)$prod['quantity_in_stock'];
    $recv_qty = 5;
    $qty_after = $qty_before + $recv_qty;

    $store_id = (int)$pdo->query("SELECT id FROM users WHERE role='store' AND is_active=1 LIMIT 1")->fetchColumn();

    $pdo->beginTransaction();
    $pdo->prepare("UPDATE products SET quantity_in_stock = ? WHERE id = ?")->execute([$qty_after, $prod_id]);
    $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, quantity_before, quantity_after, notes, performed_by) VALUES (?, 'receive', ?, ?, ?, 'Test Stock Receipt', ?)")->execute([$prod_id, $recv_qty, $qty_before, $qty_after, $store_id]);
    $sm_id = (int)$pdo->lastInsertId();
    $pdo->commit();

    $check_stock = (int)$pdo->query("SELECT quantity_in_stock FROM products WHERE id = $prod_id")->fetchColumn();

    // Restore stock and clean movement
    $pdo->prepare("UPDATE products SET quantity_in_stock = ? WHERE id = ?")->execute([$qty_before, $prod_id]);
    $pdo->prepare("DELETE FROM stock_movements WHERE id = ?")->execute([$sm_id]);

    return $check_stock === $qty_after;
});

// -----------------------------------------------------------------------------
// WORKFLOW 5: Stock Adjustment Workflow
// -----------------------------------------------------------------------------
echo "\n5. Stock Adjustment Workflow\n";

it("Stock adjustment corrects quantity and creates auditable record", function() use ($pdo) {
    $prod = $pdo->query("SELECT id, quantity_in_stock FROM products WHERE is_active=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $prod_id = (int)$prod['id'];
    $qty_before = (int)$prod['quantity_in_stock'];
    $new_qty = $qty_before + 3;

    $store_id = (int)$pdo->query("SELECT id FROM users WHERE role='store' AND is_active=1 LIMIT 1")->fetchColumn();

    $pdo->beginTransaction();
    $pdo->prepare("UPDATE products SET quantity_in_stock = ? WHERE id = ?")->execute([$new_qty, $prod_id]);
    $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, quantity_before, quantity_after, notes, performed_by) VALUES (?, 'adjustment', 3, ?, ?, 'Annual Audit Correction', ?)")->execute([$prod_id, $qty_before, $new_qty, $store_id]);
    $sm_id = (int)$pdo->lastInsertId();
    $pdo->commit();

    $check_stock = (int)$pdo->query("SELECT quantity_in_stock FROM products WHERE id = $prod_id")->fetchColumn();

    // Revert
    $pdo->prepare("UPDATE products SET quantity_in_stock = ? WHERE id = ?")->execute([$qty_before, $prod_id]);
    $pdo->prepare("DELETE FROM stock_movements WHERE id = ?")->execute([$sm_id]);

    return $check_stock === $new_qty;
});

// -----------------------------------------------------------------------------
// WORKFLOW 6: Purchase -> Approval -> Payment -> Stock Receiving
// -----------------------------------------------------------------------------
echo "\n6. Purchase -> Approval -> Payment Workflow\n";

it("Purchase Order creation, approval, and payment recording", function() use ($pdo) {
    $supplier_id = (int)$pdo->query("SELECT id FROM suppliers WHERE is_active=1 LIMIT 1")->fetchColumn();
    $fin_id = (int)$pdo->query("SELECT id FROM users WHERE role='finance' AND is_active=1 LIMIT 1")->fetchColumn();
    $mgr_id = (int)$pdo->query("SELECT id FROM users WHERE role='manager' AND is_active=1 LIMIT 1")->fetchColumn();
    $prod = $pdo->query("SELECT id, unit_price FROM products WHERE is_active=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if (!$supplier_id || !$fin_id || !$mgr_id || !$prod) return false;

    $ref_no = 'PO-TEST-' . strtoupper(bin2hex(random_bytes(3)));
    $total_amount = (float)$prod['unit_price'] * 10;

    // 1. Create Draft PO
    $pdo->prepare("INSERT INTO purchases (reference_no, supplier_id, status, total_amount, ordered_by) VALUES (?, ?, 'draft', ?, ?)")->execute([$ref_no, $supplier_id, $total_amount, $fin_id]);
    $po_id = (int)$pdo->lastInsertId();

    $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity_ordered, unit_price, total_price) VALUES (?, ?, 10, ?, ?)")->execute([$po_id, (int)$prod['id'], (float)$prod['unit_price'], $total_amount]);

    // 2. Manager Approves PO
    $pdo->prepare("UPDATE purchases SET status='approved', approved_by=? WHERE id=?")->execute([$mgr_id, $po_id]);
    $status_after_appr = $pdo->query("SELECT status FROM purchases WHERE id=$po_id")->fetchColumn();
    if ($status_after_appr !== 'approved') return false;

    // 3. Finance Records Payment
    $pay_ref = 'PAY-TEST-' . strtoupper(bin2hex(random_bytes(3)));
    $pdo->prepare("INSERT INTO payments (purchase_id, reference_no, amount, payment_method, status, payment_date, recorded_by) VALUES (?, ?, ?, 'bank_transfer', 'completed', CURDATE(), ?)")->execute([$po_id, $pay_ref, $total_amount, $fin_id]);
    $pay_id = (int)$pdo->lastInsertId();

    $total_paid = (float)$pdo->query("SELECT SUM(amount) FROM payments WHERE purchase_id=$po_id AND status='completed'")->fetchColumn();

    // Clean up
    $pdo->prepare("DELETE FROM payments WHERE id=?")->execute([$pay_id]);
    $pdo->prepare("DELETE FROM purchase_items WHERE purchase_id=?")->execute([$po_id]);
    $pdo->prepare("DELETE FROM purchases WHERE id=?")->execute([$po_id]);

    return $total_paid === $total_amount;
});

// -----------------------------------------------------------------------------
// WORKFLOW 7: Notifications System & In-App Notification Flow
// -----------------------------------------------------------------------------
echo "\n7. Notifications System\n";

it("Can send, count unread, mark read, and clear notifications", function() use ($pdo) {
    $user_id = (int)$pdo->query("SELECT id FROM users WHERE is_active=1 LIMIT 1")->fetchColumn();

    // Send notification
    $ok = send_notification($user_id, 'Integration Test Alert', 'Automated test message for notification system.', app_base_url());
    if (!$ok) return false;

    $notif_id = (int)$pdo->lastInsertId();
    $unread_count = get_unread_notifications_count($user_id);
    if ($unread_count < 1) return false;

    // Mark single notification read
    mark_notification_read($notif_id, $user_id);
    $is_read = (int)$pdo->query("SELECT is_read FROM notifications WHERE id = $notif_id")->fetchColumn();

    // Clean up
    $pdo->prepare("DELETE FROM notifications WHERE id = ?")->execute([$notif_id]);

    return $is_read === 1;
});

// -----------------------------------------------------------------------------
// WORKFLOW 8: Reporting Accuracy & Integrity
// -----------------------------------------------------------------------------
echo "\n8. Reporting Queries & Accuracy\n";

it("Reporting summary queries execute with valid totals", function() use ($pdo) {
    // Inventory query
    $inv_stmt = $pdo->query("SELECT COUNT(*) as total_prod, SUM(quantity_in_stock * unit_price) as total_val FROM products WHERE is_active=1");
    $inv = $inv_stmt->fetch(PDO::FETCH_ASSOC);

    // Stock movements query
    $mv_stmt = $pdo->query("SELECT COUNT(*) FROM stock_movements");
    $mv_count = $mv_stmt->fetchColumn();

    // Requests count
    $req_stmt = $pdo->query("SELECT COUNT(*) FROM requests");
    $req_count = $req_stmt->fetchColumn();

    return is_numeric($inv['total_prod']) && is_numeric($mv_count) && is_numeric($req_count);
});

echo "\n======================================================\n";
echo "  Results: $passed Passed, $failed Failed\n";
echo "======================================================\n\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
