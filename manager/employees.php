<?php
/**
 * manager/employees.php — Employee list (read-only)
 */
$page_title = 'Employees';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('manager');

$pdo = get_db_connection();
$q = sanitize_string($_GET['q'] ?? '');
$params = [];
$where = '';
if ($q) {
    $where = "WHERE u.full_name LIKE ? OR u.email LIKE ? OR e.department LIKE ?";
    $params = ["%$q%", "%$q%", "%$q%"];
}

$employees = $pdo->prepare(
    "SELECT u.id, u.full_name, u.email, u.username, u.role, u.is_active, u.last_login_at,
            e.department, e.phone,
            (SELECT COUNT(*) FROM requests WHERE user_id = u.id) AS total_requests,
            (SELECT COUNT(*) FROM requests WHERE user_id = u.id AND status = 'pending') AS pending_requests
     FROM users u
     LEFT JOIN employees e ON e.user_id = u.id
     $where
     ORDER BY u.full_name"
);
$employees->execute($params);
$employees = $employees->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Company Directory</h1><p class="page-header__subtitle">All active staff across all departments.</p></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Staff Members (<?= count($employees) ?>)</h2></div>
    <div class="content-card__body" style="padding:0; overflow-x:auto;">
        <table class="table">
            <thead><tr><th>Name</th><th>Role / Dept</th><th>Email</th><th>Status</th><th>Total Requests</th><th>Pending</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($employees as $emp): ?>
            <tr>
                <td><strong><?= e($emp['full_name']) ?></strong></td>
                <td>
                    <span style="display:block; font-weight:600; text-transform:capitalize; color:var(--color-primary);"><?= e($emp['role']) ?></span>
                    <span style="font-size:12px; color:var(--color-on-surface-variant);"><?= e($emp['department'] ?? 'No Dept Assigned') ?></span>
                </td>
                <td><?= e($emp['email']) ?></td>
                <td><span class="badge <?= $emp['is_active'] ? 'badge--success' : 'badge--neutral' ?>"><?= $emp['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td style="font-weight:600;"><?= e($emp['total_requests']) ?></td>
                <td style="<?= $emp['pending_requests'] > 0 ? 'color:var(--color-error); font-weight:700;' : '' ?>"><?= e($emp['pending_requests']) ?></td>
                <td>
                    <a href="employee-activity.php?id=<?= e($emp['id']) ?>" class="btn btn--secondary" style="padding:4px 8px; font-size:12px;"><span class="material-symbols-outlined" style="font-size:16px;">analytics</span> Activity</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($employees)): ?><tr><td colspan="7" style="text-align:center;">No staff found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
