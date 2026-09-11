<?php
/**
 * includes/alerts.php
 * GCM IMS — Flash Message Renderer
 *
 * Reads and outputs any pending flash message from the session.
 * Include this after start_secure_session() wherever alerts should appear.
 *
 * Requires: includes/functions.php (get_flash_message, e)
 */

$_ims_flash = get_flash_message();

if ($_ims_flash): ?>
<?php
$_ims_type = $_ims_flash['type'] ?? 'info';
$_ims_msg  = $_ims_flash['message'] ?? '';

$_ims_icon_map = [
    'success' => 'check_circle',
    'error'   => 'error',
    'warning' => 'warning',
    'info'    => 'info',
];
$_ims_icon = $_ims_icon_map[$_ims_type] ?? 'info';
?>
<div class="alert alert--<?= e($_ims_type) ?>" role="alert" aria-live="polite">
    <span class="material-symbols-outlined" aria-hidden="true"><?= e($_ims_icon) ?></span>
    <span><?= e($_ims_msg) ?></span>
</div>
<?php endif;
unset($_ims_flash, $_ims_type, $_ims_msg, $_ims_icon_map, $_ims_icon);
