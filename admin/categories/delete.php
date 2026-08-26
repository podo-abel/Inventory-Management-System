<?php
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(app_base_url().'/admin/categories/index.php'); }
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { flash_message('error','Invalid CSRF.'); redirect(app_base_url().'/admin/categories/index.php'); }
$pdo = get_db_connection();
$id = (int)($_POST['id'] ?? 0);
$cat = $pdo->prepare('SELECT * FROM categories WHERE id=?'); $cat->execute([$id]); $cat = $cat->fetch();
if (!$cat) { flash_message('error','Not found.'); redirect(app_base_url().'/admin/categories/index.php'); }
$prod_count = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id=?'); $prod_count->execute([$id]);
if ((int)$prod_count->fetchColumn() > 0) { flash_message('error','Cannot delete — category has products.'); redirect(app_base_url().'/admin/categories/index.php'); }
$pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
log_activity($_SESSION['user_id'],'delete_category','Deleted category: '.$cat['name'],'category',$id);
flash_message('success','Category deleted.');
redirect(app_base_url().'/admin/categories/index.php');
