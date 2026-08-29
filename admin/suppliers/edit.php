<?php
/**
 * admin/suppliers/edit.php — Edit supplier
 */
$page_title = 'Edit Supplier';
require_once dirname(__DIR__, 2) . '/includes/header.php';
require_role('admin');

$uid = (int)$_SESSION['user_id'];
$pdo = get_db_connection();
$sid = sanitize_int($_GET['id'] ?? 0);

$ss = $pdo->prepare('SELECT * FROM suppliers WHERE id = ?'); $ss->execute([$sid]); $sup = $ss->fetch();
if (!$sup) { flash_message('error', 'Supplier not found.'); header('Location: ' . app_base_url() . '/admin/suppliers/index.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Session expired.'; }
    else {
        $name = sanitize_string($_POST['name'] ?? '');
        $contact = sanitize_string($_POST['contact_name'] ?? '');
        $email = sanitize_string($_POST['email'] ?? '');
        $phone = sanitize_string($_POST['phone'] ?? '');
        $address = sanitize_string($_POST['address'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        if (empty($name)) $errors[] = 'Name required.';
        if (empty($errors)) {
            $pdo->prepare('UPDATE suppliers SET name=?,contact_name=?,email=?,phone=?,address=?,is_active=? WHERE id=?')->execute([$name, $contact ?: null, $email ?: null, $phone ?: null, $address ?: null, $is_active, $sid]);
            log_activity($uid, 'update_supplier', "Updated supplier: $name.", 'supplier', $sid);
            flash_message('success', 'Supplier updated.');
            header('Location: ' . app_base_url() . '/admin/suppliers/index.php'); exit;
        }
    }
}
$d = $_POST ?: $sup;
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Edit Supplier</h1></div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<?php if(!empty($errors)): ?><div class="alert alert--error" style="margin-bottom:var(--space-4);"><?php foreach($errors as $e_): ?><p><?= e($e_) ?></p><?php endforeach; ?></div><?php endif; ?>
<div class="content-card" style="max-width:600px;">
    <div class="content-card__header"><h2 class="content-card__title"><?= e($sup['name']) ?></h2></div>
    <div class="content-card__body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <div class="form-group"><label class="form-label">Name *</label><input class="form-input" name="name" value="<?= e($d['name']) ?>" required></div>
            <div class="form-group"><label class="form-label">Contact</label><input class="form-input" name="contact_name" value="<?= e($d['contact_name'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email" value="<?= e($d['email'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Phone</label><input class="form-input" name="phone" value="<?= e($d['phone'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Address</label><textarea class="form-input" name="address" rows="2"><?= e($d['address'] ?? '') ?></textarea></div>
            <div class="form-group"><label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;"><input type="checkbox" name="is_active" <?= ($d['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label></div>
            <div style="display:flex;gap:var(--space-3);"><button type="submit" class="btn btn--primary">Save</button><a href="<?= e(app_base_url()) ?>/admin/suppliers/index.php" class="btn btn--secondary">Cancel</a></div>
        </form>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
