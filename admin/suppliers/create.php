<?php
/**
 * admin/suppliers/create.php — Add supplier
 */
$page_title = 'Add Supplier';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('admin');

$uid = (int)$_SESSION['user_id'];
$pdo = get_db_connection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Session expired.'; }
    else {
        $name = sanitize_string($_POST['name'] ?? '');
        $contact = sanitize_string($_POST['contact_name'] ?? '');
        $email = sanitize_string($_POST['email'] ?? '');
        $phone = sanitize_string($_POST['phone'] ?? '');
        $address = sanitize_string($_POST['address'] ?? '');
        if (empty($name)) $errors[] = 'Name is required.';
        if (empty($errors)) {
            $pdo->prepare('INSERT INTO suppliers (name,contact_name,email,phone,address) VALUES (?,?,?,?,?)')->execute([$name, $contact ?: null, $email ?: null, $phone ?: null, $address ?: null]);
            $sid = (int)$pdo->lastInsertId();
            log_activity($uid, 'create_supplier', "Created supplier: $name.", 'supplier', $sid);
            flash_message('success', "Supplier '$name' created.");
            header('Location: ' . app_base_url() . '/admin/suppliers/index.php'); exit;
        }
    }
}
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Add Supplier</h1></div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if(!empty($errors)): ?><div class="alert alert--error" style="margin-bottom:var(--space-4);"><?php foreach($errors as $e_): ?><p><?= e($e_) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="content-card" style="max-width:600px;">
    <div class="content-card__header"><h2 class="content-card__title">Supplier Information</h2></div>
    <div class="content-card__body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div class="form-group"><label class="form-label">Company Name *</label><input class="form-input" name="name" value="<?= e($_POST['name'] ?? '') ?>" required></div>
            <div class="form-group"><label class="form-label">Contact Person</label><input class="form-input" name="contact_name" value="<?= e($_POST['contact_name'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Phone</label><input class="form-input" name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Address</label><textarea class="form-input" name="address" rows="2"><?= e($_POST['address'] ?? '') ?></textarea></div>
            <div style="display:flex;gap:var(--space-3);"><button type="submit" class="btn btn--primary">Add Supplier</button><a href="<?= e(app_base_url()) ?>/admin/suppliers/index.php" class="btn btn--secondary">Cancel</a></div>
        </form>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
