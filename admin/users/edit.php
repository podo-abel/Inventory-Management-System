<?php
/**
 * admin/users/edit.php — Edit Existing User
 */
$page_title = 'Edit User';
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
$pdo = get_db_connection();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { flash_message('error','Invalid user ID.'); redirect(app_base_url().'/admin/users/index.php'); }
$user_row = $pdo->prepare('SELECT * FROM users WHERE id=?'); $user_row->execute([$id]); $user_row = $user_row->fetch();
if (!$user_row) { flash_message('error','User not found.'); redirect(app_base_url().'/admin/users/index.php'); }

$errors = [];
$form = ['username'=>$user_row['username'],'email'=>$user_row['email'],'full_name'=>$user_row['full_name'],'role'=>$user_row['role'],'is_active'=>$user_row['is_active']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid CSRF token.'; }
    else {
        $form['username']  = sanitize_string($_POST['username'] ?? '');
        $form['email']     = sanitize_string($_POST['email'] ?? '');
        $form['full_name'] = sanitize_string($_POST['full_name'] ?? '');
        $form['role']      = sanitize_string($_POST['role'] ?? '');
        $form['is_active'] = isset($_POST['is_active']) ? 1 : 0;
        $new_password      = $_POST['password'] ?? '';
        $confirm           = $_POST['confirm_password'] ?? '';
        if ($form['username'] === '') $errors[] = 'Username is required.';
        if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
        if ($form['full_name'] === '') $errors[] = 'Full name required.';
        if (!in_array($form['role'], ['admin','manager','employee','store','finance'])) $errors[] = 'Invalid role.';
        if ($new_password !== '' && strlen($new_password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($new_password !== '' && $new_password !== $confirm) $errors[] = 'Passwords do not match.';
        if (empty($errors)) {
            $dup = $pdo->prepare('SELECT id FROM users WHERE (username=? OR email=?) AND id!=?');
            $dup->execute([$form['username'],$form['email'],$id]);
            if ($dup->fetch()) $errors[] = 'Username or email already in use.';
        }
        if (empty($errors)) {
            if ($new_password !== '') {
                $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost'=>12]);
                $pdo->prepare('UPDATE users SET username=?,email=?,full_name=?,role=?,is_active=?,password_hash=? WHERE id=?')
                    ->execute([$form['username'],$form['email'],$form['full_name'],$form['role'],$form['is_active'],$hash,$id]);
            } else {
                $pdo->prepare('UPDATE users SET username=?,email=?,full_name=?,role=?,is_active=? WHERE id=?')
                    ->execute([$form['username'],$form['email'],$form['full_name'],$form['role'],$form['is_active'],$id]);
            }
            log_activity($_SESSION['user_id'],'update_user','Updated user: '.$form['username'],'user',$id);
            flash_message('success','User updated successfully.');
            redirect(app_base_url().'/admin/users/index.php');
        }
    }
}
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
    <div><h1 class="page-header__title">Edit User</h1><p class="page-header__subtitle"><?= e($user_row['username']) ?></p></div>
    <a href="index.php" class="btn btn--secondary"><span class="material-symbols-outlined">arrow_back</span> Back</a>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if (!empty($errors)): ?>
<div class="alert alert--error"><span class="material-symbols-outlined">error</span><ul style="margin:0;padding-left:1.25rem;"><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<div class="content-card" style="max-width:600px;">
    <div class="content-card__header"><h2 class="content-card__title">Edit Details</h2></div>
    <div class="content-card__body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= e($id) ?>">
            <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?= e($form['full_name']) ?>" required></div>
            <div class="form-group"><label class="form-label">Username</label><input type="text" name="username" class="form-control" value="<?= e($form['username']) ?>" required></div>
            <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($form['email']) ?>" required></div>
            <div class="form-group"><label class="form-label">Role</label>
                <select name="role" class="form-control">
                    <?php foreach(['admin','manager','employee','store','finance'] as $r): ?>
                    <option value="<?= e($r) ?>" <?= $form['role']===$r?'selected':'' ?>><?= ucfirst(e($r)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label">New Password <small style="color:var(--color-text-secondary);">(leave blank to keep current)</small></label><input type="password" name="password" class="form-control" autocomplete="new-password"></div>
            <div class="form-group"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control" autocomplete="new-password"></div>
            <div class="form-group"><label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;"><input type="checkbox" name="is_active" value="1" <?= $form['is_active']?'checked':'' ?>> <span>Active</span></label></div>
            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-5);">
                <button type="submit" class="btn btn--primary">Save Changes</button>
                <a href="index.php" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
