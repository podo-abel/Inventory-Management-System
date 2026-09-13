<?php
/**
 * api/reports.php — CSV Export API
 * GCM IMS — Exports report data as CSV files.
 *
 * GET Parameters:
 *   type       : inventory|stock_movements|purchases|payments|expenses|requests
 *   format     : csv
 *   date_from  : optional YYYY-MM-DD
 *   date_to    : optional YYYY-MM-DD
 *   status     : optional status filter
 *   movement_type : optional movement type filter
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

start_secure_session();
require_login();

$role = get_current_role();
if (!in_array($role, ['admin', 'manager', 'store', 'finance'])) {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

$type   = $_GET['type'] ?? '';
$format = $_GET['format'] ?? '';

if ($format !== 'csv') {
    http_response_code(400);
    echo 'Invalid format.';
    exit;
}

$pdo = get_db_connection();
if (!$pdo) {
    http_response_code(500);
    echo 'Database error.';
    exit;
}

$date_from = $_GET['date_from'] ?? $_GET['start_date'] ?? null;
$date_to   = $_GET['date_to'] ?? $_GET['end_date'] ?? null;

$filename = $type . '_report_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
// BOM for Excel UTF-8
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

switch ($type) {

    case 'inventory':
        fputcsv($output, ['Product', 'SKU', 'Category', 'Unit', 'Stock', 'Min Level', 'Unit Price', 'Stock Value', 'Status']);
        $stmt = $pdo->query("
            SELECT p.name, p.sku, COALESCE(c.name,'—') AS category, p.unit, 
                   p.quantity_in_stock, p.min_stock_level, p.unit_price,
                   (p.quantity_in_stock * p.unit_price) AS stock_value
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.is_active = 1
            ORDER BY c.name, p.name
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = 'OK';
            if ($row['quantity_in_stock'] <= 0) $status = 'Out of Stock';
            elseif ($row['quantity_in_stock'] <= $row['min_stock_level']) $status = 'Low Stock';
            fputcsv($output, [
                $row['name'], $row['sku'], $row['category'], $row['unit'],
                $row['quantity_in_stock'], $row['min_stock_level'],
                number_format((float)$row['unit_price'], 2, '.', ''),
                number_format((float)$row['stock_value'], 2, '.', ''),
                $status
            ]);
        }
        break;

    case 'stock_movements':
        fputcsv($output, ['Date', 'Product', 'Type', 'Quantity', 'Before', 'After', 'Reference', 'Performed By', 'Notes']);
        $sql = "SELECT sm.created_at, p.name AS product_name, sm.movement_type, sm.quantity,
                       sm.quantity_before, sm.quantity_after, 
                       CONCAT(COALESCE(sm.reference_type,''), ' ', COALESCE(sm.reference_id,'')) AS reference,
                       COALESCE(u.full_name,'—') AS performed_by, sm.notes
                FROM stock_movements sm
                LEFT JOIN products p ON p.id = sm.product_id
                LEFT JOIN users u ON u.id = sm.performed_by
                WHERE 1=1";
        $params = [];
        if ($date_from) { $sql .= " AND sm.created_at >= ?"; $params[] = $date_from . ' 00:00:00'; }
        if ($date_to)   { $sql .= " AND sm.created_at <= ?"; $params[] = $date_to . ' 23:59:59'; }
        if (!empty($_GET['movement_type']) && $_GET['movement_type'] !== 'All') {
            $sql .= " AND sm.movement_type = ?"; $params[] = $_GET['movement_type'];
        }
        $sql .= " ORDER BY sm.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['created_at'], $row['product_name'], ucfirst($row['movement_type']),
                $row['quantity'], $row['quantity_before'], $row['quantity_after'],
                trim($row['reference']), $row['performed_by'], $row['notes']
            ]);
        }
        break;

    case 'purchases':
        fputcsv($output, ['Reference', 'Supplier', 'Status', 'Total Amount', 'Order Date', 'Expected Date']);
        $sql = "SELECT pu.reference_no, COALESCE(s.name,'—') AS supplier, pu.status,
                       pu.total_amount, pu.created_at, pu.expected_date
                FROM purchases pu
                LEFT JOIN suppliers s ON s.id = pu.supplier_id WHERE 1=1";
        $params = [];
        if ($date_from) { $sql .= " AND pu.created_at >= ?"; $params[] = $date_from . ' 00:00:00'; }
        if ($date_to)   { $sql .= " AND pu.created_at <= ?"; $params[] = $date_to . ' 23:59:59'; }
        $sql .= " ORDER BY pu.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['reference_no'], $row['supplier'], ucfirst(str_replace('_',' ',$row['status'])),
                number_format((float)$row['total_amount'], 2, '.', ''),
                $row['created_at'], $row['expected_date'] ?? '—'
            ]);
        }
        break;

    case 'payments':
        fputcsv($output, ['Reference', 'Purchase Ref', 'Amount', 'Method', 'Status', 'Payment Date']);
        $sql = "SELECT p.reference_no, pu.reference_no AS po_ref, p.amount,
                       p.payment_method, p.status, p.payment_date
                FROM payments p
                LEFT JOIN purchases pu ON pu.id = p.purchase_id WHERE 1=1";
        $params = [];
        if ($date_from) { $sql .= " AND p.payment_date >= ?"; $params[] = $date_from; }
        if ($date_to)   { $sql .= " AND p.payment_date <= ?"; $params[] = $date_to; }
        $sql .= " ORDER BY p.payment_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['reference_no'] ?? '—', $row['po_ref'],
                number_format((float)$row['amount'], 2, '.', ''),
                ucfirst(str_replace('_',' ',$row['payment_method'])),
                ucfirst($row['status']), $row['payment_date']
            ]);
        }
        break;

    case 'expenses':
        fputcsv($output, ['Date', 'Category', 'Description', 'Amount', 'Recorded By']);
        $sql = "SELECT ex.expense_date, ex.category, ex.description, ex.amount,
                       COALESCE(u.full_name,'—') AS recorded_by
                FROM expenses ex
                LEFT JOIN users u ON u.id = ex.recorded_by WHERE 1=1";
        $params = [];
        if ($date_from) { $sql .= " AND ex.expense_date >= ?"; $params[] = $date_from; }
        if ($date_to)   { $sql .= " AND ex.expense_date <= ?"; $params[] = $date_to; }
        $sql .= " ORDER BY ex.expense_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['expense_date'], $row['category'], $row['description'],
                number_format((float)$row['amount'], 2, '.', ''), $row['recorded_by']
            ]);
        }
        break;

    case 'requests':
        fputcsv($output, ['Reference', 'Employee', 'Status', 'Date', 'Items Count', 'Reviewed By']);
        $sql = "SELECT r.reference_no, u.full_name AS employee, r.status, r.created_at,
                       (SELECT COUNT(*) FROM request_items WHERE request_id = r.id) AS items_count,
                       COALESCE(u2.full_name,'—') AS reviewer
                FROM requests r
                JOIN users u ON u.id = r.user_id
                LEFT JOIN users u2 ON u2.id = r.reviewed_by WHERE 1=1";
        $params = [];
        if ($date_from) { $sql .= " AND r.created_at >= ?"; $params[] = $date_from . ' 00:00:00'; }
        if ($date_to)   { $sql .= " AND r.created_at <= ?"; $params[] = $date_to . ' 23:59:59'; }
        if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
            $sql .= " AND r.status = ?"; $params[] = $_GET['status'];
        }
        $sql .= " ORDER BY r.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['reference_no'], $row['employee'], ucfirst($row['status']),
                $row['created_at'], $row['items_count'], $row['reviewer']
            ]);
        }
        break;

    default:
        fputcsv($output, ['Error']);
        fputcsv($output, ['Invalid report type: ' . $type]);
}

fclose($output);
exit;
