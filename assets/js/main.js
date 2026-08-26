/**
 * main.js
 * Logitrack IMS — General UI Utilities
 *
 * This file provides global UI helpers that are shared across all pages.
 * It is loaded on every page.
 *
 * Responsibilities:
 *   - Password visibility toggle
 *   - Alert dismissal
 *   - Generic utilities
 *
 * This file does NOT handle authentication logic.
 * Authentication belongs to the backend (later phases).
 */

'use strict';

/* =============================================================================
   PASSWORD VISIBILITY TOGGLE
   ============================================================================= */

/**
 * Initialise all password-toggle buttons on the page.
 *
 * Expected markup:
 *   <div class="input-wrapper">
 *     <input type="password" id="password" class="form-control has-toggle">
 *     <button type="button" class="btn-toggle-password" aria-label="Toggle password visibility"
 *             data-target="password">
 *       <span class="material-symbols-outlined">visibility_off</span>
 *     </button>
 *   </div>
 */
function initPasswordToggles() {
    const toggleButtons = document.querySelectorAll('.btn-toggle-password');

    toggleButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = btn.dataset.target;
            const input = targetId
                ? document.getElementById(targetId)
                : btn.closest('.input-wrapper')?.querySelector('input');

            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            // Update icon
            const icon = btn.querySelector('.material-symbols-outlined');
            if (icon) {
                icon.textContent = isPassword ? 'visibility' : 'visibility_off';
            }

            // Update aria-label for screen readers
            btn.setAttribute(
                'aria-label',
                isPassword ? 'Hide password' : 'Show password'
            );

            // Return focus to the input after toggling
            input.focus();
        });
    });
}

/* =============================================================================
   ALERT DISMISSAL
   ============================================================================= */

/**
 * Allow alerts with a close button to be dismissed.
 *
 * Expected markup:
 *   <div class="alert alert--error" role="alert">
 *     <span class="material-symbols-outlined">error</span>
 *     <span>Error message here.</span>
 *     <button type="button" class="alert__close" aria-label="Dismiss">
 *       <span class="material-symbols-outlined">close</span>
 *     </button>
 *   </div>
 */
function initAlertDismissal() {
    document.addEventListener('click', function (e) {
        const closeBtn = e.target.closest('.alert__close');
        if (!closeBtn) return;

        const alert = closeBtn.closest('.alert');
        if (alert) {
            alert.setAttribute('aria-hidden', 'true');
            alert.style.display = 'none';
        }
    });
}

/* =============================================================================
   INITIALISE ON DOM READY
   ============================================================================= */

document.addEventListener('DOMContentLoaded', function () {
    initPasswordToggles();
    initAlertDismissal();
});

/* =============================================================================
   EXPOSE TO GLOBAL NAMESPACE
   ============================================================================= */

window.IMS = window.IMS || {};
window.IMS.initPasswordToggles = initPasswordToggles;
