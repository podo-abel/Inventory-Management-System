<?php
/**
 * manager/employee-activity.php — View detailed activity report for a specific employee
 */
$page_title = 'Employee Activity Report';
require_once dirname(__DIR__) . '/includes/header.php';
require_role('manager');

$pdo = get_db_connection();
$emp_id = sanitize_int($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.email, u.username, u.role, u.is_active, u.last_login_at, u.created_at,
           e.department, e.phone
    FROM users u
    LEFT JOIN employees e ON e.user_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$emp_id]);
$employee = $stmt->fetch();

if (!$employee) {
    flash_message('error', 'Employee not found.');
    header('Location: ' . app_base_url() . '/manager/employees.php');
    exit;
}

// Get recent activity logs
$activity_stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
$activity_stmt->execute([$emp_id]);
$activities = $activity_stmt->fetchAll();

// Get summary stats
$stats = [
    'requests' => $pdo->query("SELECT COUNT(*) FROM requests WHERE user_id = {$emp_id}")->fetchColumn(),
    'purchases' => $pdo->query("SELECT COUNT(*) FROM purchases WHERE ordered_by = {$emp_id}")->fetchColumn(),
    'movements' => $pdo->query("SELECT COUNT(*) FROM stock_movements WHERE performed_by = {$emp_id}")->fetchColumn()
];

?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <div style="display:flex; justify-content:space-between; align-items:flex-end;">
        <div>
            <h1 class="page-header__title"><?= e($employee['full_name']) ?>'s Activity Report</h1>
            <p class="page-header__subtitle"><?= e(ucfirst($employee['role'])) ?> &mdash; <?= e($employee['department'] ?? 'No Department') ?></p>
        </div>
        <a href="<?= e(app_base_url()) ?>/manager/employees.php" class="btn btn--secondary">← Back to Directory</a>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:var(--space-5);">
    <div class="stat-card">
        <div class="stat-card__icon-wrap stat-card__icon-wrap--blue"><span class="material-symbols-outlined">shopping_cart</span></div>
        <div><p class="stat-card__label">Requests Created</p><p class="stat-card__value"><?= $stats['requests'] ?></p></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon-wrap stat-card__icon-wrap--green"><span class="material-symbols-outlined">receipt</span></div>
        <div><p class="stat-card__label">Purchases Ordered</p><p class="stat-card__value"><?= $stats['purchases'] ?></p></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon-wrap stat-card__icon-wrap--yellow"><span class="material-symbols-outlined">inventory</span></div>
        <div><p class="stat-card__label">Stock Movements</p><p class="stat-card__value"><?= $stats['movements'] ?></p></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon-wrap stat-card__icon-wrap--purple"><span class="material-symbols-outlined">login</span></div>
        <div>
            <p class="stat-card__label">Last Login</p>
            <p class="stat-card__value" style="font-size:16px;"><?= $employee['last_login_at'] ? e(format_date($employee['last_login_at'])) : 'Never' ?></p>
        </div>
    </div>
</div>

<div class="form-grid" style="display:grid; grid-template-columns: 1fr 3fr; gap:var(--space-5);">
    <!-- Left: Profile Details -->
    <div class="content-card" style="margin:0;">
        <div class="content-card__header"><h2 class="content-card__title">Profile Information</h2></div>
        <div class="content-card__body" style="display:flex; flex-direction:column; gap:var(--space-3);">
            <div><strong>Status:</strong> <span class="badge <?= $employee['is_active'] ? 'badge--success' : 'badge--neutral' ?>"><?= $employee['is_active'] ? 'Active' : 'Inactive' ?></span></div>
            <div><strong>Username:</strong> <code style="padding:2px 4px; background:var(--color-bg);"><?= e($employee['username']) ?></code></div>
            <div><strong>Email:</strong> <?= e($employee['email']) ?></div>
            <div><strong>Phone:</strong> <?= e($employee['phone'] ?? '—') ?></div>
            <hr style="border:none; border-top:1px solid var(--color-outline-variant);">
            <div><strong>System Role:</strong> <span style="text-transform:capitalize;"><?= e($employee['role']) ?></span></div>
            <div><strong>Department:</strong> <?= e($employee['department'] ?? '—') ?></div>
            <div><strong>Account Created:</strong> <?= e(format_date($employee['created_at'])) ?></div>
        </div>
    </div>

    <!-- Right: Activity Log -->
    <div class="content-card" style="margin:0;">
        <div class="content-card__header">
            <h2 class="content-card__title">Activity Timeline (Last 100)</h2>
            <span style="font-size:12px; color:var(--color-on-surface-variant);">Most recent first</span>
        </div>
        <div class="content-card__body" style="padding:0;">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:180px;">Date & Time</th>
                        <th>Action Performed</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($activities as $act): ?>
                <tr>
                    <td style="font-size:13px; color:var(--color-on-surface-variant);"><?= e(format_datetime($act['created_at'])) ?></td>
                    <td>
                        <strong style="color:var(--color-primary);"><?= e($act['action']) ?></strong><br>
                        <span style="font-size:14px;"><?= e($act['description']) ?></span>
                    </td>
                    <td>
                        <?php if ($act['entity_type'] && $act['entity_id']): ?>
                            <span class="badge badge--neutral"><?= e(ucfirst($act['entity_type'])) ?> #<?= (int)$act['entity_id'] ?></span>
                        <?php else: ?>
                            <span style="color:var(--color-text-secondary);">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($activities)): ?>
                <tr><td colspan="3" style="text-align:center; padding:var(--space-4); color:var(--color-text-secondary);">No activity recorded for this user yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
