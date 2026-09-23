<?php
/**
 * includes/permissions.php
 * GCM IMS — Role-Based Permissions & Navigation
 *
 * Defines the permission matrix for all five roles and builds
 * role-appropriate navigation menus.
 *
 * Requires: config/app.php, includes/auth.php
 */

// ── Permission Matrix ──────────────────────────────────────────────────────

/**
 * Maps each role to the set of actions it may perform.
 * Check with can_user($role, $action).
 */
const PERMISSIONS = [
    'admin' => [
        'manage_users',
        'manage_employees',
        'manage_categories',
        'manage_products',
        'manage_suppliers',
        'manage_stock',
        'view_inventory',
        'approve_requests',
        'view_requests',
        'submit_requests',
        'process_requests',
        'receive_stock',
        'manage_purchases',
        'manage_payments',
        'manage_expenses',
        'view_financial_reports',
        'view_reports',
        'manage_settings',
        'view_activity_log',
    ],
    'manager' => [
        'manage_employees',
        'view_inventory',
        'view_requests',
        'view_purchases',
        'view_reports',
        'manage_settings',
    ],
    'employee' => [
        'submit_requests',
        'view_own_requests',
        'view_products',
    ],
    'store' => [
        'approve_requests',
        'reject_requests',
        'process_requests',
        'manage_stock',
        'view_inventory',
        'receive_stock',
        'view_requests',
    ],
    'finance' => [
        'manage_purchases',
        'manage_payments',
        'manage_suppliers',
        'manage_expenses',
        'view_financial_reports',
        'view_reports',
    ],
];

/**
 * Checks whether the given role is allowed to perform the action.
 *
 * @param string $role
 * @param string $action
 * @return bool
 */
function can_user(string $role, string $action): bool
{
    if (!isset(PERMISSIONS[$role])) {
        return false;
    }
    return in_array($action, PERMISSIONS[$role], true);
}

// ── Navigation Builder ─────────────────────────────────────────────────────

/**
 * Returns the navigation items appropriate for the given role.
 *
 * Each item is an array with:
 *   - label  : display text
 *   - icon   : Material Symbols icon name
 *   - href   : URL relative to the application root (prefixed by app_base_url())
 *   - section: optional grouping label for separators
 *
 * @param string $role
 * @return array
 */
function get_nav_items(string $role): array
{
    $base = app_base_url();

    switch ($role) {
        case 'admin':
            return [
                ['label' => 'Dashboard',       'icon' => 'dashboard',       'href' => $base . '/admin/dashboard.php'],
                ['label' => 'Users',           'icon' => 'manage_accounts', 'href' => $base . '/admin/users/'],
                ['label' => 'Employees',       'icon' => 'badge',           'href' => $base . '/admin/employees/'],
                ['label' => 'Categories',      'icon' => 'category',        'href' => $base . '/admin/categories/'],
                ['label' => 'Products',        'icon' => 'inventory_2',     'href' => $base . '/admin/products/'],
                ['label' => 'Suppliers',       'icon' => 'local_shipping',  'href' => $base . '/admin/suppliers/'],
                ['label' => 'Reports',         'icon' => 'bar_chart',       'href' => $base . '/admin/reports/'],
                ['label' => 'Activity Log',    'icon' => 'history',         'href' => $base . '/admin/activity-log.php'],
                ['label' => 'Settings',        'icon' => 'settings',        'href' => $base . '/admin/settings.php'],
            ];

        case 'manager':
            return [
                ['label' => 'Dashboard',       'icon' => 'dashboard',       'href' => $base . '/manager/dashboard.php'],
                ['label' => 'Employees',       'icon' => 'badge',           'href' => $base . '/manager/employees.php'],
                ['label' => 'Requests',        'icon' => 'assignment',      'href' => $base . '/manager/requests.php'],
                ['label' => 'Inventory',       'icon' => 'inventory_2',     'href' => $base . '/manager/inventory.php'],
                ['label' => 'Purchase Orders', 'icon' => 'shopping_cart',   'href' => $base . '/manager/purchases.php'],
                ['label' => 'Reports',         'icon' => 'bar_chart',       'href' => $base . '/manager/reports.php'],
            ];

        case 'employee':
            return [
                ['label' => 'Dashboard',       'icon' => 'dashboard',       'href' => $base . '/employee/dashboard.php'],
                ['label' => 'Request Items',   'icon' => 'add_shopping_cart','href' => $base . '/employee/request-item.php'],
                ['label' => 'My Requests',     'icon' => 'assignment',      'href' => $base . '/employee/requests.php'],
                ['label' => 'Browse Products', 'icon' => 'inventory_2',     'href' => $base . '/employee/products.php'],
            ];

        case 'store':
            return [
                ['label' => 'Dashboard',       'icon' => 'dashboard',       'href' => $base . '/store/dashboard.php'],
                ['label' => 'Requests',         'icon' => 'assignment',       'href' => $base . '/store/requests.php'],
                ['label' => 'Receive Stock',   'icon' => 'move_to_inbox',   'href' => $base . '/store/receive-stock.php'],
                ['label' => 'Issue Stock',     'icon' => 'outbox',          'href' => $base . '/store/issue-stock.php'],
                ['label' => 'Inventory',       'icon' => 'inventory_2',     'href' => $base . '/store/inventory.php'],
                ['label' => 'Stock Movements', 'icon' => 'swap_vert',       'href' => $base . '/store/stock-movements.php'],
                ['label' => 'Reports',         'icon' => 'bar_chart',       'href' => $base . '/store/reports.php'],
            ];

        case 'finance':
            return [
                ['label' => 'Dashboard',       'icon' => 'dashboard',       'href' => $base . '/finance/dashboard.php'],
                ['label' => 'Inventory',       'icon' => 'inventory_2',     'href' => $base . '/finance/inventory.php'],
                ['label' => 'Suppliers',       'icon' => 'local_shipping',  'href' => $base . '/finance/suppliers.php'],
                ['label' => 'Purchases',       'icon' => 'shopping_cart',   'href' => $base . '/finance/purchases.php'],
                ['label' => 'Payments',        'icon' => 'payments',        'href' => $base . '/finance/payments.php'],
                ['label' => 'Expenses',        'icon' => 'receipt_long',    'href' => $base . '/finance/expenses.php'],
                ['label' => 'Financial Reports','icon'=> 'bar_chart',       'href' => $base . '/finance/reports.php'],
            ];

        default:
            return [];
    }
}
