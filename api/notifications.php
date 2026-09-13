<?php
/**
 * api/notifications.php — Notifications API endpoint
 * GCM IMS — In-app notification management
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

start_secure_session();

if (!is_logged_in()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$pdo = get_db_connection();

if (!$pdo) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? 'list';

switch ($action) {
    case 'count':
        $count = get_unread_notifications_count($user_id);
        echo json_encode(['success' => true, 'unread_count' => $count]);
        break;

    case 'list':
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
        $stmt = $pdo->prepare("SELECT id, title, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $unread_count = get_unread_notifications_count($user_id);
        echo json_encode([
            'success' => true,
            'unread_count' => $unread_count,
            'notifications' => $notifs
        ]);
        break;

    case 'mark_read':
        $notif_id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($notif_id > 0) {
            mark_notification_read($notif_id, $user_id);
            echo json_encode(['success' => true, 'unread_count' => get_unread_notifications_count($user_id)]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
        }
        break;

    case 'mark_all_read':
        mark_all_notifications_read($user_id);
        echo json_encode(['success' => true, 'unread_count' => 0]);
        break;

    case 'delete':
        $notif_id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($notif_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$notif_id, $user_id]);
            echo json_encode(['success' => true, 'unread_count' => get_unread_notifications_count($user_id)]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        break;
}
exit;
