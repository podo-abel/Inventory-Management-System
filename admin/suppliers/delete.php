<?php
/**
 * admin/suppliers/delete.php — Toggle supplier status
 */
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('admin');

$uid = (int)$_SESSION['user_id'];
$pdo = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    flash_message('error', 'Invalid request.'); header('Location: ' . app_base_url() . '/admin/suppliers/index.php'); exit;
}

$sid = sanitize_int($_POST['id'] ?? 0);
$ss = $pdo->prepare('SELECT id, name, is_active FROM suppliers WHERE id = ?'); $ss->execute([$sid]); $sup = $ss->fetch();
if (!$sup) { flash_message('error', 'Supplier not found.'); header('Location: ' . app_base_url() . '/admin/suppliers/index.php'); exit; }

$new_active = $sup['is_active'] ? 0 : 1;
$pdo->prepare('UPDATE suppliers SET is_active = ? WHERE id = ?')->execute([$new_active, $sid]);
$action = $new_active ? 'activated' : 'deactivated';
log_activity($uid, 'toggle_supplier', "Supplier '{$sup['name']}' $action.", 'supplier', $sid);
flash_message('success', "Supplier '{$sup['name']}' $action.");
header('Location: ' . app_base_url() . '/admin/suppliers/index.php'); exit;
