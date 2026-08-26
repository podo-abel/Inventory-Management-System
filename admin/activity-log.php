<?php
$page_title = 'Activity Log';
require_once dirname(__DIR__) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
$pdo = get_db_connection();
$page_num = max(1,(int)($_GET['page']??1));
$per_page = ITEMS_PER_PAGE;
$filter_user = (int)($_GET['user_id'] ?? 0);
$filter_date = sanitize_string($_GET['date'] ?? '');
$where = ['1=1']; $params = [];
if ($filter_user > 0) { $where[] = 'al.user_id=?'; $params[] = $filter_user; }
if ($filter_date !== '') { $where[] = 'DATE(al.created_at)=?'; $params[] = $filter_date; }
$where_sql = implode(' AND ', $where);
$cnt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs al WHERE $where_sql"); $cnt->execute($params);
$paged = paginate((int)$cnt->fetchColumn(), $per_page, $page_num);
$stmt = $pdo->prepare("SELECT al.*,u.full_name,u.role FROM activity_logs al LEFT JOIN users u ON al.user_id=u.id WHERE $where_sql ORDER BY al.created_at DESC LIMIT {$paged['per_page']} OFFSET {$paged['offset']}");
$stmt->execute($params);
$logs = $stmt->fetchAll();
$users_list = $pdo->query("SELECT id,full_name FROM users ORDER BY full_name")->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Activity Log</h1><p class="page-header__subtitle">Complete system activity history.</p></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div class="content-card">
    <div class="content-card__header">
        <h2 class="content-card__title">Log Entries</h2>
        <form method="GET" style="display:flex;gap:var(--space-2);">
            <select name="user_id" class="form-control" style="width:180px;">
                <option value="">All Users</option>
                <?php foreach($users_list as $ul): ?><option value="<?= e($ul['id']) ?>" <?= $filter_user===$ul['id']?'selected':'' ?>><?= e($ul['full_name']) ?></option><?php endforeach; ?>
            </select>
            <input type="date" name="date" class="form-control" value="<?= e($filter_date) ?>">
            <button type="submit" class="btn btn--secondary">Filter</button>
            <?php if($filter_user||$filter_date): ?><a href="activity-log.php" class="btn btn--secondary">Clear</a><?php endif; ?>
        </form>
    </div>
    <div class="content-card__body" style="padding:0;">
        <table class="table"><thead><tr><th>Date/Time</th><th>User</th><th>Role</th><th>Action</th><th>Description</th><th>Entity</th><th>IP</th></tr></thead><tbody>
        <?php foreach($logs as $log): ?>
        <tr>
            <td style="white-space:nowrap;font-size:var(--text-xs);"><?= e(format_datetime($log['created_at'])) ?></td>
            <td><?= e($log['full_name']??'System') ?></td>
            <td><span class="badge badge--info" style="font-size:.65rem;"><?= e(ucfirst($log['role']??'—')) ?></span></td>
            <td><code style="font-size:.7rem;background:var(--color-bg);padding:2px 5px;border-radius:3px;"><?= e($log['action']) ?></code></td>
            <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($log['description']) ?>"><?= e($log['description']) ?></td>
            <td style="font-size:var(--text-xs);"><?= e($log['entity_type']??'—') ?><?= $log['entity_id']?' #'.e($log['entity_id']):'' ?></td>
            <td style="font-size:var(--text-xs);color:var(--color-text-secondary);"><?= e($log['ip_address']??'—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($logs)): ?><tr><td colspan="7" style="text-align:center;">No log entries.</td></tr><?php endif; ?>
        </tbody></table>
    </div>
    <?php if($paged['total_pages']>1): ?>
    <div style="padding:var(--space-4);display:flex;gap:var(--space-2);justify-content:center;">
        <?php if($paged['has_prev']): ?><a href="?page=<?= $page_num-1 ?>&user_id=<?= $filter_user ?>&date=<?= urlencode($filter_date) ?>" class="btn btn--secondary">« Prev</a><?php endif; ?>
        <span style="padding:var(--space-2);color:var(--color-text-secondary);">Page <?= $page_num ?> of <?= $paged['total_pages'] ?></span>
        <?php if($paged['has_next']): ?><a href="?page=<?= $page_num+1 ?>&user_id=<?= $filter_user ?>&date=<?= urlencode($filter_date) ?>" class="btn btn--secondary">Next »</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
