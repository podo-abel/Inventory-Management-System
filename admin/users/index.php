<?php
/**
 * admin/users/index.php — User Management List
 */
$page_title = 'Users';
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
$pdo = get_db_connection();

$search   = sanitize_string($_GET['q'] ?? '');
$role_f   = sanitize_string($_GET['role'] ?? '');
$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = ITEMS_PER_PAGE;

$where = ['1=1'];
$params = [];
if ($search !== '') { $where[] = '(username LIKE ? OR full_name LIKE ? OR email LIKE ?)'; $s = "%$search%"; $params = array_merge($params,[$s,$s,$s]); }
if ($role_f !== '') { $where[] = 'role = ?'; $params[] = $role_f; }
$where_sql = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where_sql");
$total->execute($params);
$paged = paginate((int)$total->fetchColumn(), $per_page, $page_num);

$stmt = $pdo->prepare("SELECT * FROM users WHERE $where_sql ORDER BY created_at DESC LIMIT {$paged['per_page']} OFFSET {$paged['offset']}");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
    <div><h1 class="page-header__title">User Management</h1><p class="page-header__subtitle">Manage system access and roles.</p></div>
    <a href="<?= e(app_base_url()) ?>/admin/users/create.php" class="btn btn--primary"><span class="material-symbols-outlined">person_add</span> Add User</a>
</div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__header">
        <h2 class="content-card__title">All Users</h2>
        <form method="GET" style="display:flex;gap:var(--space-2);align-items:center;">
            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= e($search) ?>" style="width:200px;">
            <select name="role" class="form-control">
                <option value="">All Roles</option>
                <?php foreach(['admin','manager','employee','store','finance'] as $r): ?>
                <option value="<?= e($r) ?>" <?= $role_f===$r?'selected':'' ?>><?= ucfirst(e($r)) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn--secondary">Filter</button>
            <?php if($search||$role_f): ?><a href="?" class="btn btn--secondary">Clear</a><?php endif; ?>
        </form>
    </div>
    <div class="content-card__body" style="padding:0;">
        <table class="table">
            <thead><tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= e($u['id']) ?></td>
                <td><strong><?= e($u['username']) ?></strong></td>
                <td><?= e($u['full_name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><span class="badge badge--info"><?= e(ucfirst($u['role'])) ?></span></td>
                <td><span class="badge <?= $u['is_active'] ? 'badge--success' : 'badge--neutral' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td style="font-size:var(--text-xs);color:var(--color-text-secondary);"><?= e(format_date($u['created_at'])) ?></td>
                <td>
                    <a href="view.php?id=<?= e($u['id']) ?>" class="btn btn--secondary" style="padding:2px 8px;font-size:.75rem;">View</a>
                    <a href="edit.php?id=<?= e($u['id']) ?>" class="btn btn--secondary" style="padding:2px 8px;font-size:.75rem;">Edit</a>
                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                    <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                        <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= e($u['id']) ?>">
                        <button type="submit" class="btn btn--danger" style="padding:2px 8px;font-size:.75rem;">Delete</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($users)): ?><tr><td colspan="8" style="text-align:center;color:var(--color-text-secondary);">No users found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($paged['total_pages']>1): ?>
    <div style="padding:var(--space-4);display:flex;gap:var(--space-2);justify-content:center;">
        <?php if($paged['has_prev']): ?><a href="?page=<?= $page_num-1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_f) ?>" class="btn btn--secondary">« Prev</a><?php endif; ?>
        <span style="padding:var(--space-2);color:var(--color-text-secondary);">Page <?= $page_num ?> of <?= $paged['total_pages'] ?></span>
        <?php if($paged['has_next']): ?><a href="?page=<?= $page_num+1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_f) ?>" class="btn btn--secondary">Next »</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
