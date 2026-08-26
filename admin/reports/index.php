<?php
$page_title = 'Reports';
require_once dirname(__DIR__, 2) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
$pdo = get_db_connection();
$user_by_role    = $pdo->query("SELECT role, COUNT(*) cnt FROM users WHERE is_active=1 GROUP BY role")->fetchAll();
$prods_by_cat    = $pdo->query("SELECT c.name, COUNT(p.id) cnt FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.is_active=1 GROUP BY c.id ORDER BY cnt DESC")->fetchAll();
$req_by_status   = $pdo->query("SELECT status, COUNT(*) cnt FROM requests GROUP BY status ORDER BY cnt DESC")->fetchAll();
$recent_requests = $pdo->query("SELECT r.*,u.full_name FROM requests r LEFT JOIN users u ON r.requested_by=u.id ORDER BY r.created_at DESC LIMIT 10")->fetchAll();
?>
<?php require_once dirname(__DIR__, 2) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__, 2) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">Admin Reports</h1></div>
<?php require_once dirname(__DIR__, 2) . '/includes/alerts.php'; ?>
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--space-6);margin-bottom:var(--space-6);">
    <div class="content-card">
        <div class="content-card__header"><h2 class="content-card__title">Users by Role</h2></div>
        <div class="content-card__body" style="padding:0;">
            <table class="table"><thead><tr><th>Role</th><th>Count</th></tr></thead><tbody>
            <?php foreach($user_by_role as $r): ?><tr><td><span class="badge badge--info"><?= e(ucfirst($r['role'])) ?></span></td><td><strong><?= e($r['cnt']) ?></strong></td></tr><?php endforeach; ?>
            </tbody></table>
        </div>
    </div>
    <div class="content-card">
        <div class="content-card__header"><h2 class="content-card__title">Products by Category</h2></div>
        <div class="content-card__body" style="padding:0;">
            <table class="table"><thead><tr><th>Category</th><th>Count</th></tr></thead><tbody>
            <?php foreach($prods_by_cat as $r): ?><tr><td><?= e($r['name']) ?></td><td><strong><?= e($r['cnt']) ?></strong></td></tr><?php endforeach; ?>
            <?php if(empty($prods_by_cat)): ?><tr><td colspan="2" style="text-align:center;">No data.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </div>
    <div class="content-card">
        <div class="content-card__header"><h2 class="content-card__title">Requests by Status</h2></div>
        <div class="content-card__body" style="padding:0;">
            <table class="table"><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>
            <?php foreach($req_by_status as $r): ?><tr><td><span class="badge <?= e(get_status_badge_class($r['status'])) ?>"><?= e(ucfirst($r['status'])) ?></span></td><td><strong><?= e($r['cnt']) ?></strong></td></tr><?php endforeach; ?>
            <?php if(empty($req_by_status)): ?><tr><td colspan="2" style="text-align:center;">No requests.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </div>
</div>
<div class="content-card">
    <div class="content-card__header"><h2 class="content-card__title">Recent Requests</h2></div>
    <div class="content-card__body" style="padding:0;">
        <table class="table"><thead><tr><th>#</th><th>Requested By</th><th>Status</th><th>Notes</th><th>Date</th></tr></thead><tbody>
        <?php foreach($recent_requests as $r): ?>
        <tr>
            <td><?= e($r['id']) ?></td>
            <td><?= e($r['full_name']??'—') ?></td>
            <td><span class="badge <?= e(get_status_badge_class($r['status'])) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($r['notes']??'—') ?></td>
            <td style="font-size:var(--text-xs);color:var(--color-text-secondary);"><?= e(format_date($r['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($recent_requests)): ?><tr><td colspan="5" style="text-align:center;">No requests.</td></tr><?php endif; ?>
        </tbody></table>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
