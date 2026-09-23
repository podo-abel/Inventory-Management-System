<?php
/**
 * includes/functions.php
 * GCM IMS — General Utility Functions
 *
 * Reusable helper functions used across all modules.
 * No side effects — pure utilities only.
 *
 * Requires: config/app.php, config/database.php
 */

// ── Output / Formatting ────────────────────────────────────────────────────

/**
 * Escapes a string for safe HTML output.
 *
 * @param string|null $str
 * @return string
 */
function e(?string $str): string
{
    return htmlspecialchars((string) $str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Formats a number as Philippine Peso currency.
 *
 * @param float|int|string $amount
 * @return string  e.g. "₱1,250.00"
 */
function format_currency($amount): string
{
    return CURRENCY_SYMBOL . number_format((float) $amount, 2);
}

/**
 * Formats a datetime string to a readable date.
 *
 * @param string|null $datetime  MySQL DATETIME string or any strtotime-parseable value.
 * @param string      $format    PHP date format string.
 * @return string
 */
function format_date(?string $datetime, string $format = 'M j, Y'): string
{
    if (empty($datetime)) {
        return '—';
    }
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '—';
}

/**
 * Formats a datetime string to date + time.
 *
 * @param string|null $datetime
 * @return string  e.g. "Aug 26, 2026 2:30 PM"
 */
function format_datetime(?string $datetime): string
{
    return format_date($datetime, 'M j, Y g:i A');
}

// ── Input Sanitization ─────────────────────────────────────────────────────

/**
 * Trims and strips tags from a string. Returns empty string for null/non-string.
 *
 * @param mixed $str
 * @return string
 */
function sanitize_string($str): string
{
    return trim(strip_tags((string) $str));
}

/**
 * Converts a value to an integer. Returns 0 for non-numeric input.
 *
 * @param mixed $val
 * @return int
 */
function sanitize_int($val): int
{
    return (int) $val;
}

/**
 * Converts a value to a float. Returns 0.0 for non-numeric input.
 *
 * @param mixed $val
 * @return float
 */
function sanitize_float($val): float
{
    return (float) $val;
}

// ── Navigation / Redirect ──────────────────────────────────────────────────

/**
 * Redirects to the given URL and halts execution.
 *
 * @param string $url
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// ── Flash Messages ─────────────────────────────────────────────────────────

/**
 * Stores a flash message in the session to be displayed on the next page load.
 *
 * @param string $type     'success' | 'error' | 'warning' | 'info'
 * @param string $message  Human-readable message.
 */
function flash_message(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieves and removes the flash message from the session.
 *
 * @return array|null  ['type' => string, 'message' => string] or null.
 */
function get_flash_message(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// ── Pagination ─────────────────────────────────────────────────────────────

/**
 * Builds a pagination metadata array.
 *
 * @param int $total        Total number of records.
 * @param int $per_page     Records per page.
 * @param int $current_page Current page number (1-based).
 * @return array  Keys: total, per_page, current_page, total_pages, offset, has_prev, has_next
 */
function paginate(int $total, int $per_page, int $current_page): array
{
    $per_page     = max(1, $per_page);
    $total_pages  = (int) ceil($total / $per_page);
    $current_page = max(1, min($current_page, $total_pages ?: 1));
    $offset       = ($current_page - 1) * $per_page;

    return [
        'total'        => $total,
        'per_page'     => $per_page,
        'current_page' => $current_page,
        'total_pages'  => $total_pages,
        'offset'       => $offset,
        'has_prev'     => $current_page > 1,
        'has_next'     => $current_page < $total_pages,
    ];
}

// ── Status Badge ───────────────────────────────────────────────────────────

/**
 * Maps a status string to a CSS badge modifier class.
 *
 * @param string $status
 * @return string  CSS class, e.g. 'badge--success'
 */
function get_status_badge_class(string $status): string
{
    $map = [
        'pending'    => 'badge--warning',
        'store_approved' => 'badge--info',
        'approved'   => 'badge--success',
        'rejected'   => 'badge--danger',
        'cancelled'  => 'badge--neutral',
        'completed'  => 'badge--success',
        'processing' => 'badge--info',
        'paid'       => 'badge--success',
        'unpaid'     => 'badge--danger',
        'partial'    => 'badge--warning',
        'received'   => 'badge--success',
        'ordered'    => 'badge--info',
        'active'     => 'badge--success',
        'inactive'   => 'badge--neutral',
        'issued'     => 'badge--info',
        'low'        => 'badge--warning',
        'out_of_stock' => 'badge--danger',
    ];
    return $map[strtolower($status)] ?? 'badge--neutral';
}

// ── Activity Logging ───────────────────────────────────────────────────────

/**
 * Logs a user action to the activity_logs table.
 *
 * @param int         $user_id      The user performing the action.
 * @param string      $action       Short action identifier, e.g. 'login', 'create_product'.
 * @param string      $description  Human-readable description.
 * @param string|null $entity_type  Entity type, e.g. 'product', 'request'.
 * @param int|null    $entity_id    Primary key of the affected entity.
 */
function log_activity(
    int     $user_id,
    string  $action,
    string  $description,
    ?string $entity_type = null,
    ?int    $entity_id   = null
): void {
    try {
        $pdo = get_db_connection();
        if (!$pdo) {
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $stmt = $pdo->prepare(
            'INSERT INTO activity_logs
                (user_id, action, entity_type, entity_id, description, ip_address)
             VALUES
                (:user_id, :action, :entity_type, :entity_id, :description, :ip)'
        );
        $stmt->execute([
            ':user_id'     => $user_id,
            ':action'      => $action,
            ':entity_type' => $entity_type,
            ':entity_id'   => $entity_id,
            ':description' => $description,
            ':ip'          => $ip,
        ]);
    } catch (PDOException $e) {
        error_log('[GCM] log_activity failed: ' . $e->getMessage());
    }
}

// ── Misc ───────────────────────────────────────────────────────────────────

/**
 * Returns the current page's base filename without path or extension.
 * Useful for marking the active navigation item.
 *
 * @return string  e.g. "dashboard"
 */
function current_page(): string
{
    $file = basename($_SERVER['PHP_SELF'] ?? '', '.php');
    return $file ?: '';
}

/**
 * Send an in-app notification to a user
 */
function send_notification($user_id, $title, $message, $link = null) {
    try {
        global $pdo;
        $db = isset($pdo) ? $pdo : get_db_connection();
        $stmt = $db->prepare('INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user_id, $title, $message, $link]);
        return true;
    } catch (Exception $e) {
        error_log('[GCM] send_notification failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send a notification to all users with a specific role
 */
function send_notification_to_role($role, $title, $message, $link = null) {
    try {
        global $pdo;
        $db = isset($pdo) ? $pdo : get_db_connection();
        $stmt = $db->prepare('SELECT id FROM users WHERE role = ? AND is_active = 1');
        $stmt->execute([$role]);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($users as $uid) {
            send_notification($uid, $title, $message, $link);
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get total unread notifications count for a user
 */
function get_unread_notifications_count($user_id): int {
    try {
        global $pdo;
        $db = isset($pdo) ? $pdo : get_db_connection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Mark a single notification as read for a user
 */
function mark_notification_read($notification_id, $user_id): bool {
    try {
        global $pdo;
        $db = isset($pdo) ? $pdo : get_db_connection();
        $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        return $stmt->execute([$notification_id, $user_id]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Mark all notifications as read for a user
 */
function mark_all_notifications_read($user_id): bool {
    try {
        global $pdo;
        $db = isset($pdo) ? $pdo : get_db_connection();
        $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
        return $stmt->execute([$user_id]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check product inventory level and trigger low stock notifications if needed
 */
function check_and_notify_low_stock($product_id) {
    try {
        global $pdo;
        $db = isset($pdo) ? $pdo : get_db_connection();
        $stmt = $db->prepare('SELECT id, name, sku, quantity_in_stock, min_stock_level FROM products WHERE id = ? AND is_active = 1');
        $stmt->execute([$product_id]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($prod && (int)$prod['quantity_in_stock'] <= (int)$prod['min_stock_level']) {
            $is_out = ((int)$prod['quantity_in_stock'] <= 0);
            $title = $is_out ? "Out of Stock: {$prod['name']}" : "Low Stock Alert: {$prod['name']}";
            $msg = $is_out 
                ? "Product {$prod['name']} ({$prod['sku']}) is completely out of stock!" 
                : "Product {$prod['name']} ({$prod['sku']}) stock is low ({$prod['quantity_in_stock']} remaining, min level: {$prod['min_stock_level']}).";
            $store_link = app_base_url() . '/store/inventory.php';
            $mgr_link   = app_base_url() . '/manager/inventory.php';
            send_notification_to_role('store', $title, $msg, $store_link);
            send_notification_to_role('manager', $title, $msg, $mgr_link);
        }
    } catch (Exception $e) {
        error_log('[GCM] check_and_notify_low_stock failed: ' . $e->getMessage());
    }
}

