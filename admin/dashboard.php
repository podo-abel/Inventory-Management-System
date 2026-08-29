<?php
/**
 * admin/dashboard.php — Admin Dashboard
 */
$page_title = 'Dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
$pdo = get_db_connection();
$total_users      = $pdo->query('SELECT COUNT(*) FROM users WHERE is_active=1')->fetchColumn();
$total_products   = $pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1')->fetchColumn();
$total_categories = $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$total_suppliers  = $pdo->query('SELECT COUNT(*) FROM suppliers WHERE is_active=1')->fetchColumn();
$low_stock_count  = $pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND quantity_in_stock<=min_stock_level')->fetchColumn();
$pending_requests = $pdo->query("SELECT COUNT(*) FROM requests WHERE status='pending'")->fetchColumn();
$activity_list    = $pdo->query("SELECT al.*,u.full_name FROM activity_logs al LEFT JOIN users u ON al.user_id=u.id ORDER BY al.created_at DESC LIMIT 8")->fetchAll();
$role_counts      = $pdo->query("SELECT role,COUNT(*) cnt FROM users WHERE is_active=1 GROUP BY role")->fetchAll();
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:var(--space-6);">
    <div>
        <h1 class="text-display color-primary" style="margin-bottom:var(--space-1); letter-spacing:-0.02em; font-weight:700;">Admin Dashboard</h1>
        <p class="text-body-lg color-on-surface-var" style="margin:0;">Overview of system metrics and recent activities.</p>
    </div>
    <button class="btn btn--primary">
        <span class="material-symbols-outlined" style="font-size:18px;">download</span> Export Report
    </button>
</div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>

<!-- Stats Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
    <!-- Total Products -->
    <div class="stat-card" style="flex-direction:column; align-items:flex-start; position:relative; overflow:hidden;">
        <div style="display:flex; justify-content:space-between; width:100%; margin-bottom:var(--space-4); position:relative; z-index:10;">
            <div>
                <p class="text-label-md color-on-surface-var" style="margin-bottom:4px;">Total Products</p>
                <h3 class="text-headline-lg color-on-surface" style="margin:0;"><?= e(number_format($total_products)) ?></h3>
            </div>
            <div style="width:40px; height:40px; border-radius:var(--radius-base); background-color:var(--color-secondary-container); color:var(--color-on-secondary-container); display:flex; align-items:center; justify-content:center;">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">inventory_2</span>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:4px; font-size:var(--text-body-sm-size); color:var(--color-success); font-weight:500;">
            <span class="material-symbols-outlined" style="font-size:16px;">arrow_upward</span>
            <span>+4.2% from last month</span>
        </div>
    </div>

    <!-- Total Stock Value -->
    <div class="stat-card" style="flex-direction:column; align-items:flex-start; position:relative; overflow:hidden;">
        <div style="display:flex; justify-content:space-between; width:100%; margin-bottom:var(--space-4); position:relative; z-index:10;">
            <div>
                <p class="text-label-md color-on-surface-var" style="margin-bottom:4px;">Total Stock Value</p>
                <?php $total_stock_value = $pdo->query('SELECT COALESCE(SUM(quantity_in_stock * unit_price),0) FROM products WHERE is_active=1')->fetchColumn(); ?>
                <h3 class="text-headline-lg color-on-surface" style="margin:0;"><?= e(format_currency($total_stock_value)) ?></h3>
            </div>
            <div style="width:40px; height:40px; border-radius:var(--radius-base); background-color:var(--color-tertiary-container); color:var(--color-on-tertiary-container); display:flex; align-items:center; justify-content:center;">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">payments</span>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:4px; font-size:var(--text-body-sm-size); color:var(--color-success); font-weight:500;">
            <span class="material-symbols-outlined" style="font-size:16px;">arrow_upward</span>
            <span>+1.8% from last month</span>
        </div>
    </div>

    <!-- Low Stock Items -->
    <div class="stat-card" style="flex-direction:column; align-items:flex-start; position:relative; overflow:hidden; border-left:4px solid var(--color-error);">
        <div style="display:flex; justify-content:space-between; width:100%; margin-bottom:var(--space-4); position:relative; z-index:10;">
            <div>
                <p class="text-label-md color-on-surface-var" style="margin-bottom:4px;">Low Stock Items</p>
                <h3 class="text-headline-lg color-on-surface" style="margin:0;"><?= e(number_format($low_stock_count)) ?></h3>
            </div>
            <div style="width:40px; height:40px; border-radius:var(--radius-base); background-color:var(--color-error-container); color:var(--color-on-error-container); display:flex; align-items:center; justify-content:center;">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">warning</span>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:4px; font-size:var(--text-body-sm-size); color:var(--color-error); font-weight:500;">
            <span class="material-symbols-outlined" style="font-size:16px;">arrow_downward</span>
            <span>Needs immediate attention</span>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="stat-card" style="flex-direction:column; align-items:flex-start; position:relative; overflow:hidden;">
        <div style="display:flex; justify-content:space-between; width:100%; margin-bottom:var(--space-4); position:relative; z-index:10;">
            <div>
                <p class="text-label-md color-on-surface-var" style="margin-bottom:4px;">Pending Requests</p>
                <h3 class="text-headline-lg color-on-surface" style="margin:0;"><?= e(number_format($pending_requests)) ?></h3>
            </div>
            <div style="width:40px; height:40px; border-radius:var(--radius-base); background-color:var(--color-primary-fixed); color:var(--color-on-primary-fixed); display:flex; align-items:center; justify-content:center;">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">assignment</span>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:4px; font-size:var(--text-body-sm-size); color:var(--color-on-surface-variant); font-weight:500;">
            <span class="material-symbols-outlined" style="font-size:16px;">schedule</span>
            <span>Awaiting approval</span>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:var(--space-6); margin-bottom:var(--space-6);">
    <!-- Line Chart -->
    <div class="content-card" style="grid-column: span 2; margin-bottom:0; display:flex; flex-direction:column;">
        <div class="content-card__header" style="background-color:transparent;">
            <h3 class="text-title-md color-on-surface" style="margin:0;">Monthly Inventory Movement</h3>
            <select class="form-input" style="width:auto; padding:4px 32px 4px 12px; font-size:var(--text-body-sm-size);">
                <option>Last 6 Months</option>
                <option>This Year</option>
            </select>
        </div>
        <div class="content-card__body" style="flex:1; min-height:300px; position:relative; display:flex; align-items:center; justify-content:center;">
            <canvas id="movementChart"></canvas>
        </div>
    </div>

    <!-- Doughnut Chart -->
    <div class="content-card" style="margin-bottom:0; display:flex; flex-direction:column;">
        <div class="content-card__header" style="background-color:transparent;">
            <h3 class="text-title-md color-on-surface" style="margin:0;">Expenses by Category</h3>
            <button style="background:none; border:none; cursor:pointer; color:var(--color-outline);"><span class="material-symbols-outlined">more_vert</span></button>
        </div>
        <div class="content-card__body" style="flex:1; min-height:300px; position:relative; display:flex; align-items:center; justify-content:center;">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

<!-- Tables Row -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:var(--space-6);">
    <!-- Recent Activities Table -->
    <div class="content-card" style="grid-column: span 2; margin-bottom:0;">
        <div class="content-card__header" style="background-color:var(--color-surface-container-low);">
            <h3 class="text-title-md color-on-surface" style="margin:0;">Recent Activities</h3>
            <a href="<?= e(app_base_url()) ?>/admin/activity-log.php" class="color-secondary text-label-md" style="text-decoration:none;">VIEW ALL</a>
        </div>
        <div class="content-card__body" style="padding:0; overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activity_list as $log): ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:var(--space-2);">
                                <div style="width:24px; height:24px; border-radius:50%; background:var(--color-primary-fixed); display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; color:var(--color-on-primary-fixed);">
                                    <?= e(strtoupper(substr($log['full_name'] ?? 'U', 0, 1))) ?>
                                </div>
                                <span style="font-weight:500;"><?= e($log['full_name'] ?? '—') ?></span>
                            </div>
                        </td>
                        <td class="color-on-surface-var"><?= e($log['description']) ?></td>
                        <td class="color-on-surface-var"><?= e(format_datetime($log['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($activity_list)): ?><tr><td colspan="3" class="text-center">No activity yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="content-card" style="margin-bottom:0;">
        <div class="content-card__header" style="background-color:var(--color-error-container); border-bottom-color:rgba(186,26,26,0.2);">
            <h3 class="text-title-md" style="margin:0; color:var(--color-on-error-container);">Low Stock Alerts</h3>
        </div>
        <div class="content-card__body" style="padding:0;">
            <?php 
            $low_stock_items = $pdo->query('SELECT id, name, quantity_in_stock, min_stock_level FROM products WHERE is_active=1 AND quantity_in_stock<=min_stock_level LIMIT 4')->fetchAll();
            if(!empty($low_stock_items)):
            ?>
            <ul style="list-style:none; margin:0; padding:0;">
                <?php foreach($low_stock_items as $item): ?>
                <li style="padding:var(--space-3) var(--space-4); border-bottom:1px solid var(--color-outline-variant); display:flex; justify-content:space-between; align-items:center; <?= $item['quantity_in_stock'] <= 0 ? 'background:#fff5f5;' : '' ?>">
                    <div>
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                            <p style="margin:0; font-weight:500; font-size:var(--text-body-md-size);"><?= e($item['name']) ?></p>
                            <?php if ($item['quantity_in_stock'] <= 0): ?>
                                <span class="badge badge--error" style="font-size:10px; padding:2px 6px;">Out of Stock</span>
                            <?php else: ?>
                                <span class="badge badge--warning" style="font-size:10px; padding:2px 6px; background:var(--color-warning-container); color:var(--color-on-warning-container);">Low Stock</span>
                            <?php endif; ?>
                        </div>
                        <p style="margin:0; font-size:var(--text-body-sm-size); color:<?= $item['quantity_in_stock'] <= 0 ? 'var(--color-error)' : 'var(--color-on-surface-var)' ?>;">Current Stock: <strong><?= e($item['quantity_in_stock']) ?></strong> (Min: <?= e($item['min_stock_level']) ?>)</p>
                    </div>
                    <a href="<?= e(app_base_url()) ?>/admin/products/edit.php?id=<?= $item['id'] ?>" class="btn btn--secondary" style="padding:4px 8px; font-size:12px;">Restock</a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="text-center" style="padding:var(--space-4); color:var(--color-on-surface-variant); margin:0;">Inventory levels are healthy.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Colors from CSS custom properties if possible, falling back to hex
    const primaryColor = '#00236f';
    const secondaryColor = '#0058be';
    const tertiaryColor = '#4b1c00';
    const errorColor = '#ba1a1a';
    const surfaceDim = '#dad9e1';

    // 1. Monthly Inventory Movement (Line Chart)
    const ctxMovement = document.getElementById('movementChart');
    if (ctxMovement) {
        new Chart(ctxMovement, {
            type: 'line',
            data: {
                labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [
                    {
                        label: 'Stock In',
                        data: [650, 800, 720, 950, 850, 1100],
                        borderColor: primaryColor,
                        backgroundColor: 'rgba(0, 35, 111, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Stock Out',
                        data: [400, 600, 550, 700, 680, 890],
                        borderColor: secondaryColor,
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: surfaceDim } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 2. Expenses by Category (Doughnut Chart)
    const ctxCategory = document.getElementById('categoryChart');
    if (ctxCategory) {
        new Chart(ctxCategory, {
            type: 'doughnut',
            data: {
                labels: ['Electronics', 'Office Supplies', 'Furniture', 'Maintenance'],
                datasets: [{
                    data: [45, 25, 20, 10],
                    backgroundColor: [primaryColor, secondaryColor, '#2170e4', surfaceDim],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
                }
            }
        });
    }
});
</script>

</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
