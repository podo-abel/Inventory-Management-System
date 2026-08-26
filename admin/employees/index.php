<?php
$page_title = 'Employees';
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
$pdo = get_db_connection();
$employees = $pdo->query("SELECT e.*, u.full_name, u.email, u.username, u.role FROM employees e JOIN users u ON e.user_id=u.id ORDER BY u.full_name")->fetchAll();
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
    <div><h1 class="page-header__title">Employees</h1></div>
    <a href="create.php" class="btn btn--primary"><span class="material-symbols-outlined">person_add</span> Add Employee</a>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__body" style="padding:0;">
        <table class="table"><thead><tr><th>Name</th><th>Username</th><th>Department</th><th>Position</th><th>Hire Date</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        <?php foreach($employees as $emp): ?>
        <tr>
            <td><strong><?= e($emp['full_name']) ?></strong></td>
            <td><?= e($emp['username']) ?></td>
            <td><?= e($emp['department'] ?? '—') ?></td>
            <td><?= e($emp['position'] ?? '—') ?></td>
            <td><?= e($emp['hire_date'] ? format_date($emp['hire_date']) : '—') ?></td>
            <td><span class="badge <?= ($emp['status']??'')=='active'?'badge--success':'badge--neutral' ?>"><?= e(ucfirst($emp['status']??'—')) ?></span></td>
            <td>
                <a href="edit.php?id=<?= e($emp['id']) ?>" class="btn btn--secondary" style="padding:2px 8px;font-size:.75rem;">Edit</a>
                <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Remove employee record?');">
                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= e($emp['id']) ?>">
                    <button type="submit" class="btn btn--danger" style="padding:2px 8px;font-size:.75rem;">Remove</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($employees)): ?><tr><td colspan="7" style="text-align:center;">No employees.</td></tr><?php endif; ?>
        </tbody></table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
