<?php
$page_title = 'Edit Category';
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
$pdo = get_db_connection();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { flash_message('error','Invalid ID.'); redirect(app_base_url().'/admin/categories/index.php'); }
$cat = $pdo->prepare('SELECT * FROM categories WHERE id=?'); $cat->execute([$id]); $cat = $cat->fetch();
if (!$cat) { flash_message('error','Not found.'); redirect(app_base_url().'/admin/categories/index.php'); }
$errors = []; $form = ['name'=>$cat['name'],'description'=>$cat['description']??''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid CSRF token.'; }
    else {
        $form['name'] = sanitize_string($_POST['name'] ?? '');
        $form['description'] = sanitize_string($_POST['description'] ?? '');
        if ($form['name'] === '') $errors[] = 'Name is required.';
        if (empty($errors)) {
            $dup = $pdo->prepare('SELECT id FROM categories WHERE name=? AND id!=?'); $dup->execute([$form['name'],$id]);
            if ($dup->fetch()) $errors[] = 'Name already exists.';
        }
        if (empty($errors)) {
            $pdo->prepare('UPDATE categories SET name=?,description=? WHERE id=?')->execute([$form['name'],$form['description'],$id]);
            log_activity($_SESSION['user_id'],'update_category','Updated category: '.$form['name'],'category',$id);
            flash_message('success','Category updated.');
            redirect(app_base_url().'/admin/categories/index.php');
        }
    }
}
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header page-header--flex" style="display:flex;align-items:center;justify-content:space-between;">
    <div><h1 class="page-header__title">Edit Category</h1></div>
    <a href="index.php" class="btn btn--secondary">Back</a>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if(!empty($errors)): ?><div class="alert alert--error"><span class="material-symbols-outlined">error</span><ul style="margin:0;padding-left:1.25rem;"><?php foreach($errors as $e2): ?><li><?= e($e2) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="content-card" style="max-width:500px;">
    <div class="content-card__body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div class="form-group"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" value="<?= e($form['name']) ?>" required></div>
            <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= e($form['description']) ?></textarea></div>
            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-5);">
                <button type="submit" class="btn btn--primary">Save</button>
                <a href="index.php" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
