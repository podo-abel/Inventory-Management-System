-- =============================================================================
-- Logitrack IMS — Seed Data
-- Run AFTER schema.sql.
-- Provides one default admin account and sample reference data.
--
-- DEFAULT ADMIN CREDENTIALS:
--   Username : admin
--   Password : Admin@1234
--   (password_hash is bcrypt of "Admin@1234" — change immediately after setup)
-- =============================================================================

USE `logitrack_ims`;

-- -----------------------------------------------------------------------------
-- USERS — one per role for initial testing
-- Passwords are bcrypt hashes.
-- All test passwords follow the pattern: Role@1234 (e.g. Manager@1234)
-- IMPORTANT: Change all passwords in production before going live.
-- -----------------------------------------------------------------------------
INSERT INTO `users`
    (`username`, `email`, `password_hash`, `role`, `full_name`, `is_active`)
VALUES
    -- admin / Admin@1234
    ('admin',
     'admin@logitrack.local',
     '$2y$12$4QXO0q1y1Fl3s1TLZqKwAOCBvIHAA6WB0VMKNaFxOPa4.i3Bj1Bce',
     'admin',
     'System Administrator',
     1),

    -- manager / Manager@1234
    ('manager',
     'manager@logitrack.local',
     '$2y$12$xHrY6D1rSBzrSfQ0/JTxX.UHClSSk73r4i3OOB8pgjwJGkD5E0TJe',
     'manager',
     'Operations Manager',
     1),

    -- employee / Employee@1234
    ('employee',
     'employee@logitrack.local',
     '$2y$12$4K1fNJFnkpF2tT.7yCVQnOZ2d1R0LJlg5/xkIqKU1DqHRq5J5cVS.',
     'employee',
     'Jane Employee',
     1),

    -- store / Store@1234
    ('store',
     'store@logitrack.local',
     '$2y$12$hLQDwRH6bP3yFV3eT2JFYuWvJg5X9sSJrMrXABGBqbLz7TlrUKyxu',
     'store',
     'Store Keeper',
     1),

    -- finance / Finance@1234
    ('finance',
     'finance@logitrack.local',
     '$2y$12$7tRCmlgX6U4ERx5mHHVWk.KFCXuCIvQ5s4xW9mSL5l4.KCXVT8Aze',
     'finance',
     'Finance Officer',
     1);

-- Employee profile for the employee user (user_id = 3)
INSERT INTO `employees` (`user_id`, `department`, `phone`)
VALUES (3, 'Operations', '+1-555-0100');

-- -----------------------------------------------------------------------------
-- CATEGORIES — starter set
-- -----------------------------------------------------------------------------
INSERT INTO `categories` (`name`, `description`) VALUES
    ('Office Supplies',  'Stationery, paper, pens, and general office consumables'),
    ('IT Equipment',     'Computers, peripherals, cables, and networking gear'),
    ('Cleaning Supplies','Cleaning agents, mops, and janitorial equipment'),
    ('Safety Equipment', 'PPE, first-aid kits, fire extinguishers'),
    ('Furniture',        'Desks, chairs, shelving, and storage units');

-- -----------------------------------------------------------------------------
-- SUPPLIERS — two sample suppliers
-- -----------------------------------------------------------------------------
INSERT INTO `suppliers` (`name`, `contact_name`, `email`, `phone`) VALUES
    ('OfficeWorld Ltd.',    'Sarah Connors', 'orders@officeworld.example', '+1-555-0200'),
    ('TechSupply Co.',      'Mark Reid',     'sales@techsupply.example',  '+1-555-0300');

-- -----------------------------------------------------------------------------
-- PRODUCTS — small sample catalogue
-- -----------------------------------------------------------------------------
INSERT INTO `products`
    (`category_id`, `sku`, `name`, `unit`, `quantity_in_stock`, `min_stock_level`, `unit_price`)
VALUES
    (1, 'OFF-A4-500',  'A4 Copy Paper (500 sheets)',  'ream',  50, 10, 4.50),
    (1, 'OFF-PEN-BK',  'Ballpoint Pen — Black (box)', 'box',   30,  5, 3.20),
    (1, 'OFF-STPL-26', 'Stapler 26/6',                'pcs',   15,  3, 8.00),
    (2, 'IT-USB-HUB',  'USB 3.0 Hub (4-port)',        'pcs',   20,  4, 22.00),
    (2, 'IT-KBDM-STD', 'Standard Keyboard & Mouse',   'set',   12,  3, 35.00),
    (3, 'CLN-DTRG-5L', 'Multi-surface Detergent 5L',  'litre', 40,  8,  6.75),
    (4, 'SAF-HMET-L',  'Hard Hat — Large',            'pcs',   25,  5, 14.00);
