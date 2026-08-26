<?php
/**
 * admin/users/delete.php — Delete user (POST only)
 */
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(app_base_url().'/admin/users/index.php'); }
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { flash_message('error','Invalid CSRF token.'); redirect(app_base_url().'/admin/users/index.php'); }
$pdo = get_db_connection();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0 || $id === (int)$_SESSION['user_id']) { flash_message('error','Cannot delete this user.'); redirect(app_base_url().'/admin/users/index.php'); }
$stmt = $pdo->prepare('SELECT username FROM users WHERE id=?'); $stmt->execute([$id]); $u = $stmt->fetch();
if (!$u) { flash_message('error','User not found.'); redirect(app_base_url().'/admin/users/index.php'); }
$pdo->prepare('UPDATE users SET is_active=0 WHERE id=?')->execute([$id]);
log_activity($_SESSION['user_id'],'delete_user','Deactivated user: '.$u['username'],'user',$id);
flash_message('success','User "'.$u['username'].'" deactivated.');
redirect(app_base_url().'/admin/users/index.php');
