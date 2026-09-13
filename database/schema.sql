-- =============================================================================
-- GCM IMS — Database Schema (Great Commission Ministry)
-- Engine: MariaDB 10.4 (XAMPP)
-- Charset: utf8mb4
-- Collation: utf8mb4_unicode_ci
--
-- Creation order respects foreign-key dependencies.
-- Run this file once to create the full schema.
-- =============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+03:00";

-- -----------------------------------------------------------------------------
-- DATABASE
-- -----------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `gcm_ims`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `gcm_ims`;

-- =============================================================================
-- 1. USERS
-- Stores authentication credentials and role for every system user.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(50)      NOT NULL,
    `email`         VARCHAR(150)     NOT NULL,
    `password_hash` VARCHAR(255)     NOT NULL  COMMENT 'bcrypt hash — never plaintext',
    `role`          ENUM(
                        'admin',
                        'manager',
                        'employee',
                        'store',
                        'finance'
                    )                NOT NULL,
    `full_name`     VARCHAR(120)     NOT NULL,
    `is_active`     TINYINT(1)       NOT NULL  DEFAULT 1,
    `last_login_at` DATETIME                   DEFAULT NULL,
    `created_at`    DATETIME         NOT NULL  DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME         NOT NULL  DEFAULT CURRENT_TIMESTAMP
                                               ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email`    (`email`),
    KEY `idx_users_role`           (`role`),
    KEY `idx_users_is_active`      (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='System users — one row per person who can log in.';

-- =============================================================================
-- 2. EMPLOYEES
-- Extended profile for users whose role is "employee".
-- A user can exist without an employee record (admin, finance, etc.).
-- =============================================================================
CREATE TABLE IF NOT EXISTS `employees` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED  NOT NULL,
    `department`  VARCHAR(100)           DEFAULT NULL,
    `phone`       VARCHAR(30)            DEFAULT NULL,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                         ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_employees_user_id` (`user_id`),
    CONSTRAINT `fk_employees_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Extended profile for employee-role users.';

-- =============================================================================
-- 3. CATEGORIES
-- Product categories (e.g. Office Supplies, IT Equipment).
-- =============================================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100)  NOT NULL,
    `description` TEXT                   DEFAULT NULL,
    `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                         ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Product categories.';

-- =============================================================================
-- 4. SUPPLIERS
-- Vendor / supplier records used by purchasing and finance.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`         VARCHAR(150)  NOT NULL,
    `contact_name` VARCHAR(120)           DEFAULT NULL,
    `email`        VARCHAR(150)           DEFAULT NULL,
    `phone`        VARCHAR(30)            DEFAULT NULL,
    `address`      TEXT                   DEFAULT NULL,
    `is_active`    TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_suppliers_name`      (`name`),
    KEY `idx_suppliers_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Supplier / vendor records.';

-- =============================================================================
-- 5. PRODUCTS
-- Core product catalogue. Current stock quantity is tracked here.
-- Stock quantity is ONLY changed via stock_movements (AGENTS.md §11).
-- =============================================================================
CREATE TABLE IF NOT EXISTS `products` (
    `id`                INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `category_id`       INT UNSIGNED               DEFAULT NULL,
    `sku`               VARCHAR(60)      NOT NULL  COMMENT 'Stock-keeping unit — unique identifier',
    `name`              VARCHAR(200)     NOT NULL,
    `description`       TEXT                       DEFAULT NULL,
    `unit`              VARCHAR(30)      NOT NULL  DEFAULT 'pcs'
                                                   COMMENT 'e.g. pcs, box, ream, litre',
    `quantity_in_stock` INT              NOT NULL  DEFAULT 0,
    `min_stock_level`   INT              NOT NULL  DEFAULT 0
                                                   COMMENT 'Threshold for low-stock alert',
    `unit_price`        DECIMAL(12, 2)             DEFAULT NULL,
    `is_active`         TINYINT(1)       NOT NULL  DEFAULT 1,
    `created_at`        DATETIME         NOT NULL  DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME         NOT NULL  DEFAULT CURRENT_TIMESTAMP
                                                   ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_products_sku`       (`sku`),
    KEY `idx_products_category`        (`category_id`),
    KEY `idx_products_is_active`       (`is_active`),
    KEY `idx_products_quantity`        (`quantity_in_stock`),
    CONSTRAINT `fk_products_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Product catalogue. Quantity changes only through stock_movements.';

-- =============================================================================
-- 6. REQUESTS
-- Employee item requests — header record.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `requests` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        INT UNSIGNED NOT NULL  COMMENT 'Employee who submitted the request',
    `reference_no`   VARCHAR(30)  NOT NULL  COMMENT 'Human-readable reference e.g. REQ-2026-001',
    `status`         ENUM(
                         'pending',
                         'approved',
                         'rejected',
                         'processing',
                         'issued',
                         'completed',
                         'cancelled'
                     )            NOT NULL  DEFAULT 'pending',
    `notes`          TEXT                  DEFAULT NULL,
    `rejection_reason` TEXT                DEFAULT NULL,
    `reviewed_by`    INT UNSIGNED          DEFAULT NULL  COMMENT 'Manager user_id',
    `reviewed_at`    DATETIME              DEFAULT NULL,
    `issued_by`      INT UNSIGNED          DEFAULT NULL  COMMENT 'Store user_id',
    `issued_at`      DATETIME              DEFAULT NULL,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                           ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_requests_reference`   (`reference_no`),
    KEY `idx_requests_user`              (`user_id`),
    KEY `idx_requests_status`            (`status`),
    KEY `idx_requests_reviewed_by`       (`reviewed_by`),
    CONSTRAINT `fk_requests_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `fk_requests_reviewed_by`
        FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_requests_issued_by`
        FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Employee request headers.';

-- =============================================================================
-- 7. REQUEST_ITEMS
-- Line items for each employee request.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `request_items` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`   INT UNSIGNED NOT NULL,
    `product_id`   INT UNSIGNED NOT NULL,
    `quantity_requested` INT    NOT NULL CHECK (`quantity_requested` > 0),
    `quantity_issued`    INT             DEFAULT NULL,
    `notes`        TEXT                  DEFAULT NULL,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_request_items_request`  (`request_id`),
    KEY `idx_request_items_product`  (`product_id`),
    CONSTRAINT `fk_request_items_request`
        FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_request_items_product`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Line items belonging to employee requests.';

-- =============================================================================
-- 8. STOCK_MOVEMENTS
-- Audit trail for every inventory quantity change.
-- This is the single source of truth for stock changes (AGENTS.md §11).
-- =============================================================================
CREATE TABLE IF NOT EXISTS `stock_movements` (
    `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `product_id`    INT UNSIGNED     NOT NULL,
    `movement_type` ENUM(
                        'receive',
                        'issue',
                        'adjustment',
                        'return'
                    )                NOT NULL,
    `quantity`      INT              NOT NULL  COMMENT 'Always positive; direction inferred from type',
    `quantity_before` INT            NOT NULL  COMMENT 'Stock before this movement',
    `quantity_after`  INT            NOT NULL  COMMENT 'Stock after this movement',
    `reference_type`  VARCHAR(30)              DEFAULT NULL
                                               COMMENT 'e.g. request, purchase',
    `reference_id`    INT UNSIGNED             DEFAULT NULL
                                               COMMENT 'ID in the referenced table',
    `notes`           TEXT                     DEFAULT NULL,
    `performed_by`    INT UNSIGNED             DEFAULT NULL  COMMENT 'User who performed the action',
    `created_at`      DATETIME       NOT NULL  DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_sm_product`   (`product_id`),
    KEY `idx_sm_type`      (`movement_type`),
    KEY `idx_sm_ref`       (`reference_type`, `reference_id`),
    KEY `idx_sm_date`      (`created_at`),
    CONSTRAINT `fk_sm_product`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `fk_sm_performed_by`
        FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Immutable audit trail of all inventory quantity changes.';

-- =============================================================================
-- 9. PURCHASES
-- Purchase order header.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `purchases` (
    `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `reference_no`  VARCHAR(30)      NOT NULL  COMMENT 'e.g. PO-2026-001',
    `supplier_id`   INT UNSIGNED               DEFAULT NULL,
    `status`        ENUM(
                        'draft',
                        'pending_approval',
                        'approved',
                        'ordered',
                        'partially_received',
                        'received',
                        'cancelled'
                    )                NOT NULL  DEFAULT 'draft',
    `total_amount`  DECIMAL(14, 2)   NOT NULL  DEFAULT 0.00,
    `notes`         TEXT                       DEFAULT NULL,
    `ordered_by`    INT UNSIGNED               DEFAULT NULL  COMMENT 'Finance/manager user_id',
    `approved_by`   INT UNSIGNED               DEFAULT NULL,
    `approved_at`   DATETIME                   DEFAULT NULL,
    `expected_date` DATE                       DEFAULT NULL,
    `created_at`    DATETIME         NOT NULL  DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME         NOT NULL  DEFAULT CURRENT_TIMESTAMP
                                               ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_purchases_reference`   (`reference_no`),
    KEY `idx_purchases_supplier`          (`supplier_id`),
    KEY `idx_purchases_status`            (`status`),
    CONSTRAINT `fk_purchases_supplier`
        FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_purchases_ordered_by`
        FOREIGN KEY (`ordered_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_purchases_approved_by`
        FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Purchase order headers.';

-- =============================================================================
-- 10. PURCHASE_ITEMS
-- Line items for each purchase order.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `purchase_items` (
    `id`                 INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `purchase_id`        INT UNSIGNED    NOT NULL,
    `product_id`         INT UNSIGNED    NOT NULL,
    `quantity_ordered`   INT             NOT NULL CHECK (`quantity_ordered` > 0),
    `quantity_received`  INT             NOT NULL DEFAULT 0,
    `unit_price`         DECIMAL(12, 2)  NOT NULL DEFAULT 0.00,
    `total_price`        DECIMAL(14, 2)  NOT NULL DEFAULT 0.00,
    `created_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_pi_purchase` (`purchase_id`),
    KEY `idx_pi_product`  (`product_id`),
    CONSTRAINT `fk_pi_purchase`
        FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_pi_product`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Line items for purchase orders.';

-- =============================================================================
-- 11. PAYMENTS
-- Payments against purchase orders.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `purchase_id`    INT UNSIGNED     NOT NULL,
    `reference_no`   VARCHAR(30)                DEFAULT NULL  COMMENT 'Cheque/transfer ref',
    `amount`         DECIMAL(14, 2)   NOT NULL,
    `payment_method` ENUM(
                         'cash',
                         'bank_transfer',
                         'cheque',
                         'card',
                         'other'
                     )                NOT NULL  DEFAULT 'bank_transfer',
    `status`         ENUM(
                         'pending',
                         'completed',
                         'failed',
                         'refunded'
                     )                NOT NULL  DEFAULT 'pending',
    `payment_date`   DATE                       DEFAULT NULL,
    `notes`          TEXT                       DEFAULT NULL,
    `recorded_by`    INT UNSIGNED               DEFAULT NULL  COMMENT 'Finance user_id',
    `created_at`     DATETIME         NOT NULL  DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME         NOT NULL  DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_payments_purchase`  (`purchase_id`),
    KEY `idx_payments_status`    (`status`),
    CONSTRAINT `fk_payments_purchase`
        FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_recorded_by`
        FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Payments linked to purchase orders.';

-- =============================================================================
-- 12. EXPENSES
-- General operational expenses not tied to a specific purchase order.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `expenses` (
    `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `category`     VARCHAR(80)      NOT NULL,
    `description`  TEXT                       DEFAULT NULL,
    `amount`       DECIMAL(14, 2)   NOT NULL,
    `expense_date` DATE             NOT NULL,
    `recorded_by`  INT UNSIGNED               DEFAULT NULL,
    `created_at`   DATETIME         NOT NULL  DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME         NOT NULL  DEFAULT CURRENT_TIMESTAMP
                                              ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_expenses_date`        (`expense_date`),
    KEY `idx_expenses_category`    (`category`),
    CONSTRAINT `fk_expenses_recorded_by`
        FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='General operational expenses.';

-- =============================================================================
-- 13. ACTIVITY_LOGS
-- Audit trail of important system actions.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id`          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED               DEFAULT NULL,
    `action`      VARCHAR(80)      NOT NULL  COMMENT 'e.g. login, create_product, approve_request',
    `entity_type` VARCHAR(50)                DEFAULT NULL  COMMENT 'e.g. product, request',
    `entity_id`   INT UNSIGNED               DEFAULT NULL,
    `description` TEXT                       DEFAULT NULL,
    `ip_address`  VARCHAR(45)                DEFAULT NULL,
    `created_at`  DATETIME         NOT NULL  DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_al_user`        (`user_id`),
    KEY `idx_al_action`      (`action`),
    KEY `idx_al_entity`      (`entity_type`, `entity_id`),
    KEY `idx_al_date`        (`created_at`),
    CONSTRAINT `fk_al_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit trail for important system events.';

-- =============================================================================
-- 14. NOTIFICATIONS
-- In-app alerts for users (stock alerts, request updates, purchase orders).
-- =============================================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED     NOT NULL,
    `title`      VARCHAR(100)     NOT NULL,
    `message`    TEXT             NOT NULL,
    `link`       VARCHAR(255)              DEFAULT NULL,
    `is_read`    TINYINT(1)       NOT NULL DEFAULT 0,
    `created_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_notif_user`    (`user_id`),
    KEY `idx_notif_is_read` (`user_id`, `is_read`),
    KEY `idx_notif_created` (`created_at`),
    CONSTRAINT `fk_notif_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='In-app notifications for users.';

-- =============================================================================
-- RE-ENABLE FK CHECKS
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
