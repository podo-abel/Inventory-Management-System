<?php
$page_title = 'Add Staff Member';
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }

$pdo = get_db_connection();
$errors = [];
$form = [
    'full_name' => '', 'username' => '', 'email' => '', 'password' => '', 'role' => 'employee',
    'department' => '', 'phone' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $form['full_name']  = sanitize_string($_POST['full_name'] ?? '');
        $form['username']   = sanitize_string($_POST['username'] ?? '');
        $form['email']      = sanitize_string($_POST['email'] ?? '');
        $form['password']   = $_POST['password'] ?? '';
        $form['role']       = sanitize_string($_POST['role'] ?? 'employee');
        $form['department'] = sanitize_string($_POST['department'] ?? '');
        $form['phone']      = sanitize_string($_POST['phone'] ?? '');

        if (empty($form['full_name'])) $errors[] = 'Full Name is required.';
        if (empty($form['username']))  $errors[] = 'Username is required.';
        if (empty($form['email']))     $errors[] = 'Email is required.';
        if (empty($form['password']))  $errors[] = 'Password is required.';

        if (empty($errors)) {
            // Check username & email uniqueness
            $chk = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $chk->execute([$form['username'], $form['email']]);
            if ($chk->fetch()) {
                $errors[] = 'Username or Email is already taken.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $hash = password_hash($form['password'], PASSWORD_DEFAULT);
                    
                    // 1. Create User
                    $u_stmt = $pdo->prepare('INSERT INTO users (full_name, username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, 1)');
                    $u_stmt->execute([$form['full_name'], $form['username'], $form['email'], $hash, $form['role']]);
                    $new_user_id = $pdo->lastInsertId();

                    // 2. Create Employee Profile
                    $e_stmt = $pdo->prepare('INSERT INTO employees (user_id, department, phone) VALUES (?, ?, ?)');
                    $e_stmt->execute([$new_user_id, $form['department'], $form['phone']]);
                    
                    $pdo->commit();
                    
                    log_activity($_SESSION['user_id'], 'create_user', "Created new staff member: {$form['username']}", 'user', $new_user_id);
                    flash_message('success', "Staff account for {$form['full_name']} created successfully.");
                    redirect(app_base_url() . '/admin/employees/index.php');
                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log('Error creating staff: ' . $e->getMessage());
                    $errors[] = 'A database error occurred while creating the account.';
                }
            }
        }
    }
}
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header page-header--flex" style="display:flex;align-items:center;justify-content:space-between;">
    <div>
        <h1 class="page-header__title">Add New Staff Member</h1>
        <p class="page-header__subtitle">Create an account and assign a role & department.</p>
    </div>
    <a href="<?= e(app_base_url()) ?>/admin/employees/index.php" class="btn btn--secondary">Back to Directory</a>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if(!empty($errors)): ?>
<div class="alert alert--error">
    <span class="material-symbols-outlined">error</span>
    <ul style="margin:0;padding-left:1.25rem;">
        <?php foreach($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="content-card" style="max-width:800px;">
    <div class="content-card__body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            
            <h3 style="margin-bottom:15px; color:var(--color-primary); border-bottom:1px solid var(--color-outline-variant); padding-bottom:5px;">Account Details</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-input" value="<?= e($form['full_name']) ?>" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-input" value="<?= e($form['email']) ?>" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:30px;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-input" value="<?= e($form['username']) ?>" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Password *</label>
                    <input type="text" name="password" class="form-input" placeholder="Enter or generate password" required>
                </div>
            </div>

            <h3 style="margin-bottom:15px; color:var(--color-primary); border-bottom:1px solid var(--color-outline-variant); padding-bottom:5px;">Role & Profile</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">System Role *</label>
                    <select name="role" class="form-input" required>
                        <option value="employee" <?= $form['role'] === 'employee' ? 'selected' : '' ?>>Standard Employee</option>
                        <option value="manager" <?= $form['role'] === 'manager' ? 'selected' : '' ?>>Manager / Approver</option>
                        <option value="store" <?= $form['role'] === 'store' ? 'selected' : '' ?>>Store Keeper</option>
                        <option value="finance" <?= $form['role'] === 'finance' ? 'selected' : '' ?>>Finance Officer</option>
                        <option value="admin" <?= $form['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-input" value="<?= e($form['department']) ?>" placeholder="e.g. IT, HR, Sales">
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom:30px;">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-input" value="<?= e($form['phone']) ?>" style="max-width:300px;">
            </div>

            <button type="submit" class="btn btn--primary">
                <span class="material-symbols-outlined">person_add</span> Create Staff Account
            </button>
        </form>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
