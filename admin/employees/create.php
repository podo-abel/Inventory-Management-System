<?php
$page_title = 'Add Employee';
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
$pdo = get_db_connection();
// Users without employee records
$users_list = $pdo->query("SELECT u.id,u.full_name,u.username FROM users u LEFT JOIN employees e ON e.user_id=u.id WHERE e.id IS NULL AND u.is_active=1 ORDER BY u.full_name")->fetchAll();
$errors = []; $form = ['user_id'=>'','department'=>'','position'=>'','hire_date'=>'','phone'=>'','address'=>'','status'=>'active'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid CSRF.'; }
    else {
        $form['user_id'] = (int)($_POST['user_id'] ?? 0);
        $form['department'] = sanitize_string($_POST['department'] ?? '');
        $form['position']   = sanitize_string($_POST['position'] ?? '');
        $form['hire_date']  = sanitize_string($_POST['hire_date'] ?? '');
        $form['phone']      = sanitize_string($_POST['phone'] ?? '');
        $form['address']    = sanitize_string($_POST['address'] ?? '');
        $form['status']     = sanitize_string($_POST['status'] ?? 'active');
        if ($form['user_id'] <= 0) $errors[] = 'User is required.';
        if (empty($errors)) {
            $pdo->prepare('INSERT INTO employees (user_id,department,position,hire_date,phone,address,status) VALUES (?,?,?,?,?,?,?)')->execute([$form['user_id'],$form['department'],$form['position'],$form['hire_date']?:null,$form['phone'],$form['address'],$form['status']]);
            $nid = (int)$pdo->lastInsertId();
            log_activity($_SESSION['user_id'],'create_employee','Created employee record','employee',$nid);
            flash_message('success','Employee record created.');
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
    <div><h1 class="page-header__title">Add Employee Record</h1></div>
    <a href="index.php" class="btn btn--secondary">Back</a>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if(!empty($errors)): ?><div class="alert alert--error"><span class="material-symbols-outlined">error</span><ul style="margin:0;padding-left:1.25rem;"><?php foreach($errors as $e2): ?><li><?= e($e2) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="content-card" style="max-width:560px;">
    <div class="content-card__body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div class="form-group"><label class="form-label">User *</label>
                <select name="user_id" class="form-control" required>
                    <option value="">— Select User —</option>
                    <?php foreach($users_list as $u2): ?><option value="<?= e($u2['id']) ?>" <?= (int)$form['user_id']===$u2['id']?'selected':'' ?>><?= e($u2['full_name'].' ('.$u2['username'].')') ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Department</label><input type="text" name="department" class="form-control" value="<?= e($form['department']) ?>"></div>
            <div class="form-group"><label class="form-label">Position</label><input type="text" name="position" class="form-control" value="<?= e($form['position']) ?>"></div>
            <div class="form-group"><label class="form-label">Hire Date</label><input type="date" name="hire_date" class="form-control" value="<?= e($form['hire_date']) ?>"></div>
            <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= e($form['phone']) ?>"></div>
            <div class="form-group"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?= e($form['address']) ?></textarea></div>
            <div class="form-group"><label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?= $form['status']==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $form['status']==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-5);">
                <button type="submit" class="btn btn--primary">Create</button>
                <a href="index.php" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
