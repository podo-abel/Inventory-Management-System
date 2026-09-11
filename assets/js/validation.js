/**
 * validation.js
 * GCM IMS — Client-side Form Validation Utilities
 *
 * This module provides reusable form validation helpers.
 * It does NOT perform any authentication or make any network requests.
 *
 * Usage:
 *   Import (or include) this file before the page-specific JS.
 *   Call FormValidator.validate(formElement) or use the field helpers directly.
 */

'use strict';

/* =============================================================================
   FIELD VALIDATORS
   ============================================================================= */

const Validators = {
    /**
     * Returns true if the value is not empty (after trimming whitespace).
     */
    required(value) {
        return value.trim().length > 0;
    },

    /**
     * Returns true if the value is a structurally valid email address.
     */
    email(value) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(value.trim());
    },

    /**
     * Returns true if the value meets the minimum length requirement.
     * @param {string} value
     * @param {number} min
     */
    minLength(value, min) {
        return value.length >= min;
    },

    /**
     * Returns true if both values match exactly.
     */
    matches(value, other) {
        return value === other;
    },

    /**
     * Returns true if the value contains at least one uppercase letter,
     * one lowercase letter, and one digit.
     * Used for password strength hints (non-blocking in this phase).
     */
    strongPassword(value) {
        return /[A-Z]/.test(value) &&
               /[a-z]/.test(value) &&
               /[0-9]/.test(value);
    }
};

/* =============================================================================
   FIELD ERROR DISPLAY
   ============================================================================= */

const FieldUI = {
    /**
     * Marks a field as invalid and shows the error message.
     * @param {HTMLElement} field  - The input element
     * @param {string}      message - The error text to display
     */
    setError(field, message) {
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');

        const errorEl = FieldUI._getErrorElement(field);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.add('is-visible');
        }
    },

    /**
     * Clears the invalid state from a field.
     * @param {HTMLElement} field
     */
    clearError(field) {
        field.classList.remove('is-invalid');
        field.removeAttribute('aria-invalid');

        const errorEl = FieldUI._getErrorElement(field);
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.remove('is-visible');
        }
    },

    /**
     * Finds the associated .form-error-text element for a given field.
     * It looks for a sibling element with class .form-error-text, or falls
     * back to a data-error attribute pointing to an element ID.
     * @param {HTMLElement} field
     * @returns {HTMLElement|null}
     */
    _getErrorElement(field) {
        // Prefer data-error-id attribute
        if (field.dataset.errorId) {
            return document.getElementById(field.dataset.errorId);
        }
        // Otherwise look in the parent .form-field container
        const wrapper = field.closest('.form-field') || field.parentElement;
        if (wrapper) {
            return wrapper.querySelector('.form-error-text');
        }
        return null;
    }
};

/* =============================================================================
   FORM VALIDATOR CLASS
   ============================================================================= */

class FormValidator {
    /**
     * @param {HTMLFormElement} form
     */
    constructor(form) {
        this.form = form;
        this._rules = [];
    }

    /**
     * Register a validation rule.
     * @param {string}   fieldId   - The id of the input element
     * @param {Function} validator - A function(value) => boolean
     * @param {string}   message   - The error message to display on failure
     */
    addRule(fieldId, validator, message) {
        this._rules.push({ fieldId, validator, message });
        return this; // chainable
    }

    /**
     * Run all registered rules.
     * @returns {boolean} true if all fields are valid.
     */
    validate() {
        let isValid = true;

        for (const rule of this._rules) {
            const field = this.form.querySelector('#' + rule.fieldId) ||
                          this.form.elements[rule.fieldId];

            if (!field) continue;

            const value = field.value;
            const passes = rule.validator(value);

            if (!passes) {
                FieldUI.setError(field, rule.message);
                isValid = false;
            } else {
                FieldUI.clearError(field);
            }
        }

        return isValid;
    }

    /**
     * Attach real-time (blur) validation to all registered fields.
     */
    attachBlurValidation() {
        for (const rule of this._rules) {
            const field = this.form.querySelector('#' + rule.fieldId) ||
                          this.form.elements[rule.fieldId];

            if (!field) continue;

            field.addEventListener('blur', () => {
                const passes = rule.validator(field.value);
                if (!passes) {
                    FieldUI.setError(field, rule.message);
                } else {
                    FieldUI.clearError(field);
                }
            });

            // Clear error on input (live feedback after first blur)
            field.addEventListener('input', () => {
                if (field.classList.contains('is-invalid')) {
                    const passes = rule.validator(field.value);
                    if (passes) {
                        FieldUI.clearError(field);
                    }
                }
            });
        }

        return this; // chainable
    }
}

/* =============================================================================
   BUTTON LOADING STATE
   ============================================================================= */

const ButtonUI = {
    /**
     * Put a button into a loading state (disables it, changes label).
     * @param {HTMLButtonElement} btn
     * @param {string} loadingText - Optional text to display while loading
     */
    setLoading(btn, loadingText) {
        btn.dataset.originalText = btn.innerHTML;
        btn.classList.add('is-loading');
        btn.setAttribute('aria-disabled', 'true');
        btn.setAttribute('aria-busy', 'true');
        if (loadingText) {
            const textSpan = btn.querySelector('.btn-text');
            if (textSpan) {
                textSpan.textContent = loadingText;
            }
        }
    },

    /**
     * Restore a button from its loading state.
     * @param {HTMLButtonElement} btn
     */
    clearLoading(btn) {
        if (btn.dataset.originalText) {
            btn.innerHTML = btn.dataset.originalText;
        }
        btn.classList.remove('is-loading');
        btn.removeAttribute('aria-disabled');
        btn.removeAttribute('aria-busy');
    }
};

/* =============================================================================
   EXPORTS (for use from inline <script> blocks or other modules)
   ============================================================================= */

// Make utilities available on window for non-module scripts.
window.IMS = window.IMS || {};
window.IMS.Validators   = Validators;
window.IMS.FieldUI      = FieldUI;
window.IMS.FormValidator = FormValidator;
window.IMS.ButtonUI     = ButtonUI;
