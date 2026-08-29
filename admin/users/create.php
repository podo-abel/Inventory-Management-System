<?php
/**
 * admin/users/create.php — Create New User
 */
$page_title = 'Add User';
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
$pdo = get_db_connection();

$errors = [];
$form = ['username'=>'','email'=>'','full_name'=>'','role'=>'employee','is_active'=>1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid CSRF token.'; }
    else {
        $form['username']  = sanitize_string($_POST['username'] ?? '');
        $form['email']     = sanitize_string($_POST['email'] ?? '');
        $form['full_name'] = sanitize_string($_POST['full_name'] ?? '');
        $form['role']      = sanitize_string($_POST['role'] ?? '');
        $form['is_active'] = isset($_POST['is_active']) ? 1 : 0;
        $password          = $_POST['password'] ?? '';
        $confirm           = $_POST['confirm_password'] ?? '';
        if ($form['username'] === '') $errors[] = 'Username is required.';
        if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if ($form['full_name'] === '') $errors[] = 'Full name is required.';
        if (!in_array($form['role'], ['admin','manager','employee','store','finance'])) $errors[] = 'Invalid role.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';
        if (empty($errors)) {
            $dup = $pdo->prepare('SELECT id FROM users WHERE username=? OR email=?');
            $dup->execute([$form['username'], $form['email']]);
            if ($dup->fetch()) $errors[] = 'Username or email already exists.';
        }
        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
            $ins = $pdo->prepare('INSERT INTO users (username,email,password_hash,role,full_name,is_active) VALUES (?,?,?,?,?,?)');
            $ins->execute([$form['username'],$form['email'],$hash,$form['role'],$form['full_name'],$form['is_active']]);
            $new_id = (int)$pdo->lastInsertId();
            log_activity($_SESSION['user_id'],'create_user','Created user: '.$form['username'],'user',$new_id);
            flash_message('success','User "'.$form['username'].'" created successfully.');
            redirect(app_base_url() . '/admin/users/index.php');
        }
    }
}
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
    <div><h1 class="page-header__title">Add New User</h1></div>
    <a href="index.php" class="btn btn--secondary"><span class="material-symbols-outlined">arrow_back</span> Back</a>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if (!empty($errors)): ?>
<div class="alert alert--error" role="alert">
    <span class="material-symbols-outlined">error</span>
    <ul style="margin:0;padding-left:1.25rem;"><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>
<div class="content-card" style="max-width:600px;">
    <div class="content-card__header"><h2 class="content-card__title">User Details</h2></div>
    <div class="content-card__body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div class="form-group"><label class="form-label">Full Name <span style="color:red;">*</span></label><input type="text" name="full_name" class="form-input" value="<?= e($form['full_name']) ?>" required></div>
            <div class="form-group"><label class="form-label">Username <span style="color:red;">*</span></label><input type="text" name="username" class="form-input" value="<?= e($form['username']) ?>" required autocomplete="off"></div>
            <div class="form-group"><label class="form-label">Email <span style="color:red;">*</span></label><input type="email" name="email" class="form-input" value="<?= e($form['email']) ?>" required></div>
            <div class="form-group"><label class="form-label">Role <span style="color:red;">*</span></label>
                <select name="role" class="form-input">
                    <?php foreach(['admin','manager','employee','store','finance'] as $r): ?>
                    <option value="<?= e($r) ?>" <?= $form['role']===$r?'selected':'' ?>><?= ucfirst(e($r)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Password <span style="color:red;">*</span></label><input type="password" name="password" class="form-input" required autocomplete="new-password" minlength="8"></div>
            <div class="form-group"><label class="form-label">Confirm Password <span style="color:red;">*</span></label><input type="password" name="confirm_password" class="form-input" required autocomplete="new-password"></div>
            <div class="form-group"><label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;"><input type="checkbox" name="is_active" value="1" <?= $form['is_active']?'checked':'' ?>> <span>Active (can log in)</span></label></div>
            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-5);">
                <button type="submit" class="btn btn--primary">Create User</button>
                <a href="index.php" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
