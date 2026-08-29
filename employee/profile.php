<?php
/**
 * employee/profile.php — My Profile
 */
$page_title = 'My Profile';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('employee');

$uid = (int) $_SESSION['user_id'];
$pdo = get_db_connection();

$user = $pdo->prepare('SELECT u.*, e.department, e.phone FROM users u LEFT JOIN employees e ON e.user_id = u.id WHERE u.id = ?');
$user->execute([$uid]);
$user = $user->fetch();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expired.';
    } elseif (isset($_POST['update_profile'])) {
        $full_name  = sanitize_string($_POST['full_name'] ?? '');
        $phone      = sanitize_string($_POST['phone'] ?? '');
        $department = sanitize_string($_POST['department'] ?? '');
        if (empty($full_name)) $errors[] = 'Full name is required.';
        if (empty($errors)) {
            $pdo->prepare('UPDATE users SET full_name = ? WHERE id = ?')->execute([$full_name, $uid]);
            $exists = $pdo->prepare('SELECT id FROM employees WHERE user_id = ?'); $exists->execute([$uid]);
            if ($exists->fetch()) {
                $pdo->prepare('UPDATE employees SET department = ?, phone = ? WHERE user_id = ?')->execute([$department ?: null, $phone ?: null, $uid]);
            } else {
                $pdo->prepare('INSERT INTO employees (user_id, department, phone) VALUES (?,?,?)')->execute([$uid, $department ?: null, $phone ?: null]);
            }
            $_SESSION['full_name'] = $full_name;
            log_activity($uid, 'update_profile', 'Updated profile.', 'user', $uid);
            flash_message('success', 'Profile updated.');
            header('Location: ' . app_base_url() . '/employee/profile.php'); exit;
        }
    } elseif (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new_pw  = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password_hash'])) $errors[] = 'Current password is incorrect.';
        if (strlen($new_pw) < 8) $errors[] = 'New password must be at least 8 characters.';
        if ($new_pw !== $confirm) $errors[] = 'New passwords do not match.';
        if (empty($errors)) {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new_pw, PASSWORD_DEFAULT), $uid]);
            log_activity($uid, 'change_password', 'Changed own password.', 'user', $uid);
            flash_message('success', 'Password changed successfully.');
            header('Location: ' . app_base_url() . '/employee/profile.php'); exit;
        }
    }
    // Re-fetch user
    $s = $pdo->prepare('SELECT u.*, e.department, e.phone FROM users u LEFT JOIN employees e ON e.user_id = u.id WHERE u.id = ?'); $s->execute([$uid]); $user = $s->fetch();
}
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">My Profile</h1></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<?php if (!empty($errors)): ?><div class="alert alert--error" style="margin-bottom:var(--space-4);"><?php foreach ($errors as $e_): ?><p style="margin:.2rem 0;"><?= e($e_) ?></p><?php endforeach; ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);">
<div>
<div class="content-card" style="margin-bottom:var(--space-5);">
    <div class="content-card__header"><h2 class="content-card__title">Personal Information</h2></div>
    <div class="content-card__body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div class="form-group"><label class="form-label">Full Name *</label><input class="form-input" name="full_name" value="<?= e($user['full_name']) ?>" required></div>
            <div class="form-group"><label class="form-label">Email</label><input class="form-input" value="<?= e($user['email']) ?>" disabled></div>
            <div class="form-group"><label class="form-label">Username</label><input class="form-input" value="<?= e($user['username']) ?>" disabled></div>
            <div class="form-group"><label class="form-label">Role</label><span class="badge badge--info"><?= e(ucfirst($user['role'])) ?></span></div>
            <div class="form-group"><label class="form-label">Department</label><input class="form-input" name="department" value="<?= e($user['department'] ?? '') ?>" placeholder="e.g. Operations"></div>
            <div class="form-group"><label class="form-label">Phone</label><input class="form-input" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="e.g. +1-555-0100"></div>
            <button type="submit" name="update_profile" class="btn btn--primary">Save Changes</button>
        </form>
    </div>
</div>
</div>
<div>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Change Password</h2></div>
    <div class="content-card__body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div class="form-group"><label class="form-label">Current Password *</label><input class="form-input" type="password" name="current_password" required></div>
            <div class="form-group"><label class="form-label">New Password *</label><input class="form-input" type="password" name="new_password" minlength="8" required></div>
            <div class="form-group"><label class="form-label">Confirm New Password *</label><input class="form-input" type="password" name="confirm_password" required></div>
            <button type="submit" name="change_password" class="btn btn--primary">Change Password</button>
        </form>
    </div>
</div>
</div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
