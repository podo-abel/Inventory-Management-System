<?php
/**
 * notifications.php — Notification Center
 * Accessible by all authenticated roles.
 */
$page_title = 'Notifications';
require_once __DIR__ . '/includes/header.php';
require_login();

$user_id = (int)$_SESSION['user_id'];
$pdo = get_db_connection();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash_message('error', 'Session expired. Please try again.');
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'mark_all_read') {
            mark_all_notifications_read($user_id);
            flash_message('success', 'All notifications marked as read.');
        } elseif ($action === 'mark_read') {
            $notif_id = (int)($_POST['id'] ?? 0);
            if ($notif_id > 0) {
                mark_notification_read($notif_id, $user_id);
            }
        } elseif ($action === 'delete') {
            $notif_id = (int)($_POST['id'] ?? 0);
            if ($notif_id > 0) {
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
                $stmt->execute([$notif_id, $user_id]);
                flash_message('success', 'Notification removed.');
            }
        } elseif ($action === 'delete_read') {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND is_read = 1");
            $stmt->execute([$user_id]);
            flash_message('success', 'All read notifications removed.');
        }
        header('Location: ' . app_base_url() . '/notifications.php' . (!empty($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : ''));
        exit;
    }
}

// Handle Direct Click & Redirect
if (isset($_GET['open'])) {
    $notif_id = (int)$_GET['open'];
    $stmt = $pdo->prepare("SELECT link FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $user_id]);
    $link = $stmt->fetchColumn();
    if ($link) {
        mark_notification_read($notif_id, $user_id);
        header('Location: ' . $link);
        exit;
    }
}

// Filtering & Pagination
$filter = sanitize_string($_GET['filter'] ?? 'all');
$where_sql = "user_id = ?";
$params = [$user_id];

if ($filter === 'unread') {
    $where_sql .= " AND is_read = 0";
}

$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = ITEMS_PER_PAGE;

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE $where_sql");
$count_stmt->execute($params);
$total_count = (int)$count_stmt->fetchColumn();
$paged = paginate($total_count, $per_page, $page_num);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE $where_sql ORDER BY created_at DESC LIMIT {$paged['per_page']} OFFSET {$paged['offset']}");
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_unread = get_unread_notifications_count($user_id);
?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="page-header page-header--flex">
        <div>
            <h1 class="page-header__title">Notifications</h1>
            <p class="page-header__subtitle">Stay informed about requests, inventory alerts, and approvals.</p>
        </div>
        <div class="page-header__actions">
            <?php if ($total_unread > 0): ?>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn btn--secondary">
                    <span class="material-symbols-outlined" style="font-size:16px;">done_all</span> Mark All as Read
                </button>
            </form>
            <?php endif; ?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Remove all read notifications?');">
                <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                <input type="hidden" name="action" value="delete_read">
                <button type="submit" class="btn btn--secondary">
                    <span class="material-symbols-outlined" style="font-size:16px;">delete_sweep</span> Clear Read
                </button>
            </form>
        </div>
    </div>

    <?php require_once __DIR__ . '/includes/alerts.php'; ?>

    <div class="content-card" style="margin-bottom:var(--space-4);">
        <div class="content-card__body" style="display:flex;gap:var(--space-3);align-items:center;flex-wrap:wrap;">
            <a href="?filter=all" class="btn <?= $filter === 'all' ? 'btn--primary' : 'btn--secondary' ?>" style="font-size:13px;padding:6px 14px;">
                All Notifications
            </a>
            <a href="?filter=unread" class="btn <?= $filter === 'unread' ? 'btn--primary' : 'btn--secondary' ?>" style="font-size:13px;padding:6px 14px;">
                Unread <?php if ($total_unread > 0): ?><span class="badge badge--error" style="margin-left:6px;font-size:11px;"><?= $total_unread ?></span><?php endif; ?>
            </a>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card__header">
            <h2 class="content-card__title">
                <?= $filter === 'unread' ? 'Unread Notifications' : 'All Notifications' ?> 
                <span style="font-size:13px;color:var(--color-on-surface-variant);font-weight:normal;">(<?= number_format($total_count) ?> total)</span>
            </h2>
        </div>
        <div class="content-card__body" style="padding:0;">
            <?php if (empty($notifications)): ?>
                <div style="padding:var(--space-6);text-align:center;color:var(--color-on-surface-variant);">
                    <span class="material-symbols-outlined" style="font-size:48px;opacity:0.3;display:block;margin-bottom:var(--space-2);">notifications_off</span>
                    <?= $filter === 'unread' ? 'You have no unread notifications.' : 'You have no notifications at this time.' ?>
                </div>
            <?php else: ?>
                <ul style="list-style:none;margin:0;padding:0;">
                    <?php foreach ($notifications as $n): ?>
                    <li style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--space-4);padding:var(--space-4);border-bottom:1px solid var(--color-outline-variant);background:<?= $n['is_read'] ? 'var(--color-surface)' : 'rgba(0, 35, 111, 0.03)' ?>;transition:background 0.15s ease;">
                        <div style="display:flex;gap:var(--space-3);align-items:flex-start;flex:1;">
                            <div style="width:36px;height:36px;border-radius:50%;background:<?= $n['is_read'] ? 'var(--color-surface-container-high, #e5e7eb)' : 'var(--color-primary-fixed, #dbeafe)' ?>;color:<?= $n['is_read'] ? 'var(--color-on-surface-variant)' : 'var(--color-primary)' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                                <span class="material-symbols-outlined" style="font-size:20px;">
                                    <?php 
                                    if (stripos($n['title'], 'stock') !== false) echo 'inventory_2';
                                    elseif (stripos($n['title'], 'payment') !== false) echo 'payments';
                                    elseif (stripos($n['title'], 'purchase') !== false) echo 'shopping_cart';
                                    elseif (stripos($n['title'], 'approved') !== false) echo 'check_circle';
                                    elseif (stripos($n['title'], 'rejected') !== false) echo 'cancel';
                                    else echo 'notifications';
                                    ?>
                                </span>
                            </div>
                            <div style="flex:1;">
                                <div style="display:flex;align-items:center;gap:var(--space-2);flex-wrap:wrap;margin-bottom:4px;">
                                    <strong style="color:var(--color-on-surface);font-size:14px;"><?= e($n['title']) ?></strong>
                                    <?php if (!$n['is_read']): ?>
                                        <span class="badge badge--info" style="font-size:10px;padding:2px 6px;">New</span>
                                    <?php endif; ?>
                                </div>
                                <p style="margin:0 0 6px 0;font-size:13px;color:var(--color-on-surface-variant);line-height:1.4;">
                                    <?= e($n['message']) ?>
                                </p>
                                <span style="font-size:11px;color:var(--color-text-secondary);display:inline-flex;align-items:center;gap:4px;">
                                    <span class="material-symbols-outlined" style="font-size:14px;">schedule</span>
                                    <?= e(format_datetime($n['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                        <div style="display:flex;gap:var(--space-2);align-items:center;flex-shrink:0;">
                            <?php if ($n['link']): ?>
                                <a href="?open=<?= (int)$n['id'] ?>" class="btn btn--primary" style="padding:4px 10px;font-size:12px;">
                                    View <span class="material-symbols-outlined" style="font-size:14px;">arrow_forward</span>
                                </a>
                            <?php endif; ?>
                            <?php if (!$n['is_read']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                <input type="hidden" name="action" value="mark_read">
                                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                                <button type="submit" class="btn btn--secondary" title="Mark as read" style="padding:4px 8px;font-size:12px;">
                                    <span class="material-symbols-outlined" style="font-size:16px;">done</span>
                                </button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                                <button type="submit" class="btn btn--secondary" title="Delete" style="padding:4px 8px;font-size:12px;" onclick="return confirm('Delete this notification?');">
                                    <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
                                </button>
                            </form>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php if ($paged['total_pages'] > 1): ?>
        <div style="padding:var(--space-4);display:flex;gap:var(--space-2);justify-content:center;border-top:1px solid var(--color-outline-variant);">
            <?php if ($paged['has_prev']): ?>
                <a href="?filter=<?= urlencode($filter) ?>&page=<?= $page_num - 1 ?>" class="btn btn--secondary">« Prev</a>
            <?php endif; ?>
            <span style="padding:var(--space-2);color:var(--color-text-secondary);font-size:13px;">
                Page <?= $page_num ?> of <?= $paged['total_pages'] ?>
            </span>
            <?php if ($paged['has_next']): ?>
                <a href="?filter=<?= urlencode($filter) ?>&page=<?= $page_num + 1 ?>" class="btn btn--secondary">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
