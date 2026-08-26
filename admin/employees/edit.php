<?php
$page_title = 'Edit Employee';
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
$pdo = get_db_connection();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { flash_message('error','Invalid ID.'); redirect(app_base_url().'/admin/employees/index.php'); }
$emp = $pdo->prepare('SELECT e.*,u.full_name,u.username FROM employees e JOIN users u ON e.user_id=u.id WHERE e.id=?'); $emp->execute([$id]); $emp = $emp->fetch();
if (!$emp) { flash_message('error','Not found.'); redirect(app_base_url().'/admin/employees/index.php'); }
$errors = []; $form = $emp;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid CSRF.'; }
    else {
        $form['department'] = sanitize_string($_POST['department'] ?? '');
        $form['position']   = sanitize_string($_POST['position'] ?? '');
        $form['hire_date']  = sanitize_string($_POST['hire_date'] ?? '');
        $form['phone']      = sanitize_string($_POST['phone'] ?? '');
        $form['address']    = sanitize_string($_POST['address'] ?? '');
        $form['status']     = sanitize_string($_POST['status'] ?? 'active');
        if (empty($errors)) {
            $pdo->prepare('UPDATE employees SET department=?,position=?,hire_date=?,phone=?,address=?,status=? WHERE id=?')->execute([$form['department'],$form['position'],$form['hire_date']?:null,$form['phone'],$form['address'],$form['status'],$id]);
            log_activity($_SESSION['user_id'],'update_employee','Updated employee record','employee',$id);
            flash_message('success','Employee updated.');
            redirect(app_base_url().'/admin/employees/index.php');
        }
    }
}
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
    <div><h1 class="page-header__title">Edit Employee: <?= e($emp['full_name']) ?></h1></div>
    <a href="index.php" class="btn btn--secondary">Back</a>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if(!empty($errors)): ?><div class="alert alert--error"><span class="material-symbols-outlined">error</span><ul style="margin:0;padding-left:1.25rem;"><?php foreach($errors as $e2): ?><li><?= e($e2) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="content-card" style="max-width:560px;">
    <div class="content-card__body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div class="form-group"><label class="form-label">Department</label><input type="text" name="department" class="form-control" value="<?= e($form['department']) ?>"></div>
            <div class="form-group"><label class="form-label">Position</label><input type="text" name="position" class="form-control" value="<?= e($form['position']) ?>"></div>
            <div class="form-group"><label class="form-label">Hire Date</label><input type="date" name="hire_date" class="form-control" value="<?= e($form['hire_date'] ? date('Y-m-d', strtotime($form['hire_date'])) : '') ?>"></div>
            <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= e($form['phone']) ?>"></div>
            <div class="form-group"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?= e($form['address']) ?></textarea></div>
            <div class="form-group"><label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?= $form['status']==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $form['status']==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-5);">
                <button type="submit" class="btn btn--primary">Save</button>
                <a href="index.php" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
