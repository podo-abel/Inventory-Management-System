<?php
$page_title = 'Settings';
require_once dirname(__DIR__) . '/includes/header.php';
if (get_current_role() !== 'admin') { header('Location: ' . app_base_url() . '/index.php'); exit; }
?>
<?php require_once dirname(__DIR__) . '/includes/navbar.php'; ?>
<div class="app-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header"><h1 class="page-header__title">System Settings</h1></div>
<?php require_once dirname(__DIR__) . '/includes/alerts.php'; ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);">
    <div class="content-card">
        <div class="content-card__header"><h2 class="content-card__title">Application Info</h2></div>
        <div class="content-card__body">
            <table style="width:100%;border-collapse:collapse;">
                <tr><td style="padding:var(--space-2);color:var(--color-text-secondary);width:40%;">Application Name</td><td style="padding:var(--space-2);font-weight:600;"><?= e(APP_NAME) ?></td></tr>
                <tr><td style="padding:var(--space-2);color:var(--color-text-secondary);">Version</td><td style="padding:var(--space-2);"><?= e(APP_VERSION) ?></td></tr>
                <tr><td style="padding:var(--space-2);color:var(--color-text-secondary);">Environment</td><td style="padding:var(--space-2);"><span class="badge badge--warning"><?= e(APP_ENV) ?></span></td></tr>
                <tr><td style="padding:var(--space-2);color:var(--color-text-secondary);">Database</td><td style="padding:var(--space-2);"><?= e(DB_NAME) ?> @ <?= e(DB_HOST) ?></td></tr>
                <tr><td style="padding:var(--space-2);color:var(--color-text-secondary);">Currency</td><td style="padding:var(--space-2);"><?= e(CURRENCY_SYMBOL) ?></td></tr>
                <tr><td style="padding:var(--space-2);color:var(--color-text-secondary);">Items Per Page</td><td style="padding:var(--space-2);"><?= e(ITEMS_PER_PAGE) ?></td></tr>
                <tr><td style="padding:var(--space-2);color:var(--color-text-secondary);">PHP Version</td><td style="padding:var(--space-2);"><?= e(PHP_VERSION) ?></td></tr>
                <tr><td style="padding:var(--space-2);color:var(--color-text-secondary);">Server Time</td><td style="padding:var(--space-2);"><?= e(date('Y-m-d H:i:s')) ?></td></tr>
            </table>
        </div>
    </div>
    <div class="content-card">
        <div class="content-card__header"><h2 class="content-card__title">System Roles</h2></div>
        <div class="content-card__body">
            <?php
            $roles_info = [
                'admin'    => ['Administrator — Full system access','manage_accounts'],
                'manager'  => ['Manager — Approvals and monitoring','supervisor_account'],
                'employee' => ['Employee — Request submission','person'],
                'store'    => ['Store Keeper — Stock operations','warehouse'],
                'finance'  => ['Finance Officer — Purchases and payments','payments'],
            ];
            foreach($roles_info as $r => $info):
            ?>
            <div style="display:flex;align-items:center;gap:var(--space-3);padding:var(--space-2) 0;border-bottom:1px solid var(--color-border);">
                <span class="material-symbols-outlined" style="color:var(--color-primary);"><?= e($info[1]) ?></span>
                <div>
                    <strong><?= e(ucfirst($r)) ?></strong>
                    <p style="margin:0;font-size:var(--text-xs);color:var(--color-text-secondary);"><?= e($info[0]) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<div class="content-card" style="margin-top:var(--space-4)">
    <div class="content-card__header"><h2 class="content-card__title">Security Notes</h2></div>
    <div class="content-card__body">
        <ul style="margin:0;padding-left:1.5rem;color:var(--color-text-secondary);">
            <li>All passwords are stored as bcrypt hashes (cost 12) — never in plaintext.</li>
            <li>All database queries use PDO prepared statements.</li>
            <li>All forms are protected with CSRF tokens.</li>
            <li>Sessions are named and use HttpOnly, SameSite=Lax cookies.</li>
            <li>Role-based access is enforced server-side on every page.</li>
        </ul>
    </div>
</div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
