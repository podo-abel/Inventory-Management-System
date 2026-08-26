<?php
$page_title = 'View User';
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
$pdo = get_db_connection();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { flash_message('error','Invalid user.'); redirect(app_base_url().'/admin/users/index.php'); }
$user_row = $pdo->prepare('SELECT * FROM users WHERE id=?'); $user_row->execute([$id]); $user_row = $user_row->fetch();
if (!$user_row) { flash_message('error','User not found.'); redirect(app_base_url().'/admin/users/index.php'); }
$activity = $pdo->prepare('SELECT * FROM activity_logs WHERE user_id=? ORDER BY created_at DESC LIMIT 15'); $activity->execute([$id]); $activity = $activity->fetchAll();
$emp = $pdo->prepare('SELECT * FROM employees WHERE user_id=?'); $emp->execute([$id]); $emp = $emp->fetch();
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
    <div><h1 class="page-header__title">User: <?= e($user_row['full_name']) ?></h1></div>
    <div style="display:flex;gap:var(--space-2);">
        <a href="edit.php?id=<?= e($id) ?>" class="btn btn--primary">Edit</a>
        <a href="index.php" class="btn btn--secondary">Back</a>
    </div>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<div style="display:grid;grid-template-columns:1fr 2fr;gap:var(--space-6);">
    <div>
        <div class="content-card">
            <div class="content-card__header"><h2 class="content-card__title">Account Info</h2></div>
            <div class="content-card__body">
                <p><strong>Username:</strong> <?= e($user_row['username']) ?></p>
                <p><strong>Email:</strong> <?= e($user_row['email']) ?></p>
                <p><strong>Full Name:</strong> <?= e($user_row['full_name']) ?></p>
                <p><strong>Role:</strong> <span class="badge badge--info"><?= e(ucfirst($user_row['role'])) ?></span></p>
                <p><strong>Status:</strong> <span class="badge <?= $user_row['is_active']?'badge--success':'badge--neutral' ?>"><?= $user_row['is_active']?'Active':'Inactive' ?></span></p>
                <p><strong>Last Login:</strong> <?= e($user_row['last_login_at'] ? format_datetime($user_row['last_login_at']) : 'Never') ?></p>
                <p><strong>Created:</strong> <?= e(format_datetime($user_row['created_at'])) ?></p>
            </div>
        </div>
        <?php if($emp): ?>
        <div class="content-card" style="margin-top:var(--space-4);">
            <div class="content-card__header"><h2 class="content-card__title">Employee Info</h2></div>
            <div class="content-card__body">
                <p><strong>Department:</strong> <?= e($emp['department'] ?? '—') ?></p>
                <p><strong>Position:</strong> <?= e($emp['position'] ?? '—') ?></p>
                <p><strong>Hire Date:</strong> <?= e($emp['hire_date'] ? format_date($emp['hire_date']) : '—') ?></p>
                <p><strong>Phone:</strong> <?= e($emp['phone'] ?? '—') ?></p>
                <p><strong>Status:</strong> <span class="badge <?= ($emp['status']??'')=='active'?'badge--success':'badge--neutral' ?>"><?= e(ucfirst($emp['status']??'—')) ?></span></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="content-card">
        <div class="content-card__header"><h2 class="content-card__title">Recent Activity</h2></div>
        <div class="content-card__body" style="padding:0;">
            <table class="table"><thead><tr><th>Date</th><th>Action</th><th>Description</th><th>IP</th></tr></thead><tbody>
            <?php foreach($activity as $log): ?>
            <tr>
                <td style="font-size:var(--text-xs);"><?= e(format_datetime($log['created_at'])) ?></td>
                <td><code style="font-size:.7rem;background:var(--color-bg);padding:2px 5px;border-radius:3px;"><?= e($log['action']) ?></code></td>
                <td><?= e($log['description']) ?></td>
                <td style="font-size:var(--text-xs);color:var(--color-text-secondary);"><?= e($log['ip_address']??'—') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($activity)): ?><tr><td colspan="4" style="text-align:center;">No activity recorded.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
