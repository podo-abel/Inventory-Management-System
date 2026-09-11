/**
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

/* 
   PASSWORD VISIBILITY TOGGLE
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

/* 
   ALERT DISMISSAL
    */

/**
 * Allow alerts with a close button to be dismissed.
 
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

/* 
   SIDEBAR TOGGLE
    */

function initSidebarToggle() {
    const toggleBtn = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('sidebar--open');
        if (overlay) overlay.classList.remove('active');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
    }
    
    function openSidebar() {
        if (sidebar) sidebar.classList.add('sidebar--open');
        if (overlay) overlay.classList.add('active');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
    }
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (sidebar.classList.contains('sidebar--open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && sidebar.classList.contains('sidebar--open')) {
                if (!sidebar.contains(e.target)) {
                    closeSidebar();
                }
            }
        });
        
        // Close sidebar when clicking overlay
        if (overlay) {
            overlay.addEventListener('click', function() {
                closeSidebar();
            });
        }
        
        // Close sidebar on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('sidebar--open')) {
                closeSidebar();
            }
        });
    }
}

/* =============================================================================
   INITIALISE ON DOM READY
   ============================================================================= */

document.addEventListener('DOMContentLoaded', function () {
    initPasswordToggles();
    initAlertDismissal();
    initSidebarToggle();
});

/* =============================================================================
   EXPOSE TO GLOBAL NAMESPACE
   ============================================================================= */

window.IMS = window.IMS || {};
window.IMS.initPasswordToggles = initPasswordToggles;
