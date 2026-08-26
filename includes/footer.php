<?php
/**
 * includes/footer.php
 * Logitrack IMS — Authenticated Page Footer
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
        <?= APP_NAME ?> &bull; v<?= APP_VERSION ?>
        &copy; <?= date('Y') ?>. All rights reserved.
    </p>
</footer>

<!-- Core scripts -->
<script src="<?= e(app_base_url()) ?>/assets/js/main.js"></script>
</body>
</html>
