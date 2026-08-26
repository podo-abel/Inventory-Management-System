<?php
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(app_base_url().'/admin/employees/index.php'); }
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { flash_message('error','Invalid CSRF.'); redirect(app_base_url().'/admin/employees/index.php'); }
$pdo = get_db_connection();
$id = (int)($_POST['id'] ?? 0);
$pdo->prepare('DELETE FROM employees WHERE id=?')->execute([$id]);
log_activity($_SESSION['user_id'],'delete_employee','Removed employee record','employee',$id);
flash_message('success','Employee record removed.');
redirect(app_base_url().'/admin/employees/index.php');
