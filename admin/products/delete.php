<?php
/**
 * admin/products/delete.php — Toggle product active status
 */
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('admin');

$uid = (int)$_SESSION['user_id'];
$pdo = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    flash_message('error', 'Invalid request.'); header('Location: ' . app_base_url() . '/admin/products/index.php'); exit;
}

$pid = sanitize_int($_POST['id'] ?? 0);
$ps = $pdo->prepare('SELECT id, name, is_active FROM products WHERE id = ?'); $ps->execute([$pid]); $prod = $ps->fetch();
if (!$prod) { flash_message('error', 'Product not found.'); header('Location: ' . app_base_url() . '/admin/products/index.php'); exit; }

$new_active = $prod['is_active'] ? 0 : 1;
$pdo->prepare('UPDATE products SET is_active = ? WHERE id = ?')->execute([$new_active, $pid]);
$action = $new_active ? 'activated' : 'deactivated';
log_activity($uid, 'toggle_product', "Product '{$prod['name']}' $action.", 'product', $pid);
flash_message('success', "Product '{$prod['name']}' $action.");
header('Location: ' . app_base_url() . '/admin/products/index.php'); exit;
