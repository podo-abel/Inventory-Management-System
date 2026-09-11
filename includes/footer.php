<?php
/**
 * includes/footer.php
 * GCM IMS — Authenticated Page Footer
 *
 * Closes the main content area and app shell.
 * Include at the very bottom of every authenticated page.
 *
 * Requires: config/app.php (for APP_NAME, APP_VERSION)
 */
?>
        </main><!-- /.main-content -->
    </div><!-- /.app-body -->
</div><!-- /.app-shell -->

<footer class="app-footer" role="contentinfo">
    <p class="app-footer__text">
        &copy; <?= date('Y') ?> Great Commission Ministry. All rights reserved. (v<?= APP_VERSION ?>)
    </p>
    <div class="app-footer__links">
        <a href="#" class="app-footer__link">Privacy Policy</a>
        <a href="#" class="app-footer__link">Help Center</a>
        <a href="#" class="app-footer__link">System Status</a>
    </div>
</footer>

<!-- Core scripts -->
<script src="<?= e(app_base_url()) ?>/assets/js/main.js"></script>
</body>
</html>
