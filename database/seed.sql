-- =============================================================================
-- GCM IMS — Seed Data (Great Commission Ministry)
-- Run AFTER schema.sql.
-- Provides one default admin account and sample reference data.
--
-- DEFAULT ADMIN CREDENTIALS:
--   Username : admin
--   Password : Admin@1234
--   (password_hash is bcrypt of "Admin@1234" — change immediately after setup)
-- =============================================================================

USE `gcm_ims`;

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
     'admin@gcm.local',
     '$2y$12$4QXO0q1y1Fl3s1TLZqKwAOCBvIHAA6WB0VMKNaFxOPa4.i3Bj1Bce',
     'admin',
     'System Administrator',
     1),

    -- manager / Manager@1234
    ('manager',
     'manager@gcm.local',
     '$2y$12$xHrY6D1rSBzrSfQ0/JTxX.UHClSSk73r4i3OOB8pgjwJGkD5E0TJe',
     'manager',
     'Operations Manager',
     1),

    -- employee / Employee@1234
    ('employee',
     'employee@gcm.local',
     '$2y$12$4K1fNJFnkpF2tT.7yCVQnOZ2d1R0LJlg5/xkIqKU1DqHRq5J5cVS.',
     'employee',
     'Jane Employee',
     1),

    -- store / Store@1234
    ('store',
     'store@gcm.local',
     '$2y$12$hLQDwRH6bP3yFV3eT2JFYuWvJg5X9sSJrMrXABGBqbLz7TlrUKyxu',
     'store',
     'Store Keeper',
     1),

    -- finance / Finance@1234
    ('finance',
     'finance@gcm.local',
     '$2y$12$7tRCmlgX6U4ERx5mHHVWk.KFCXuCIvQ5s4xW9mSL5l4.KCXVT8Aze',
     'finance',
     'Finance Officer',
     1);

-- Employee profile for the employee user (user_id = 3)
INSERT INTO `employees` (`user_id`, `department`, `phone`)
VALUES (3, 'Ministry Operations', '+251-91-100-0100');

-- -----------------------------------------------------------------------------
-- CATEGORIES
-- -----------------------------------------------------------------------------
INSERT INTO `categories` (`name`, `description`) VALUES
    ('Computers & Laptops',         'Desktop computers, laptops, and accessories'),
    ('Printers & Scanners',         'Laser printers, inkjet printers, scanners, and MFPs'),
    ('Projectors & AV Equipment',   'Projectors, screens, audio equipment, microphones, speakers'),
    ('Storage Devices',             'Flash drives, external hard drives, SSDs, floppy disks, memory cards'),
    ('Networking Equipment',        'Routers, switches, cables, access points, modems'),
    ('Office Supplies',             'Stationery, paper, pens, toner, ink cartridges'),
    ('Vehicles',                    'Ministry cars, vans, trucks, and vehicle parts'),
    ('Books & Literature',          'Bibles, devotionals, ministry books, tracts, pamphlets'),
    ('Furniture',                   'Desks, chairs, podiums, bookshelves, filing cabinets'),
    ('Cameras & Photography',       'Digital cameras, video cameras, tripods, lighting equipment');

-- -----------------------------------------------------------------------------
-- SUPPLIERS
-- -----------------------------------------------------------------------------
INSERT INTO `suppliers` (`name`, `contact_name`, `email`, `phone`, `address`) VALUES
    ('Ethio-Tech Solutions',        'Dawit Mekonnen',   'sales@ethiotech.com.et',       '+251-11-551-2345', 'Bole Sub City, Addis Ababa'),
    ('Abyssinia Office Supplies',   'Sara Tadesse',     'info@abyssiniaoffice.com',     '+251-11-662-8900', 'Kirkos Sub City, Addis Ababa'),
    ('Addis Motors PLC',            'Yohannes Bekele',  'fleet@addismotors.com.et',     '+251-11-442-1100', 'Nifas Silk Lafto, Addis Ababa'),
    ('Gospel Literature Press',     'Miriam Haile',     'orders@gospelpress.org',       '+251-11-553-7700', 'Arada Sub City, Addis Ababa'),
    ('Sheba Furniture & Interiors', 'Abebe Kebede',     'sales@shebafurniture.com',     '+251-11-667-4500', 'Yeka Sub City, Addis Ababa'),
    ('Digital Camera Ethiopia',     'Henok Assefa',     'info@digitalcam.et',           '+251-91-234-5678', 'Bole Sub City, Addis Ababa'),
    ('NetConnect Ethiopia',         'Feven Girma',      'support@netconnect.com.et',    '+251-11-551-9900', 'Lideta Sub City, Addis Ababa');

-- -----------------------------------------------------------------------------
-- PRODUCTS — Representative sample across all categories
-- Prices in Ethiopian Birr (ETB)
-- -----------------------------------------------------------------------------
INSERT INTO `products`
    (`category_id`, `sku`, `name`, `unit`, `quantity_in_stock`, `min_stock_level`, `unit_price`)
VALUES
    -- Computers & Laptops
    (1, 'CMP-DT-001',  'Dell OptiPlex Desktop PC',           'pcs',   12,  3,  45000.00),
    (1, 'CMP-DT-002',  'HP ProDesk 400 G7 Desktop',          'pcs',    8,  2,  42000.00),
    (1, 'CMP-LP-001',  'Dell Latitude 5520 Laptop',          'pcs',   15,  5,  65000.00),
    (1, 'CMP-LP-002',  'HP ProBook 450 G8 Laptop',           'pcs',   10,  3,  58000.00),
    (1, 'CMP-LP-003',  'Lenovo ThinkPad T14 Laptop',         'pcs',    7,  2,  72000.00),
    (1, 'CMP-MN-001',  'Dell 24" Monitor',                   'pcs',   20,  5,  15000.00),
    (1, 'CMP-KB-001',  'Logitech Wireless Keyboard',         'pcs',   30, 10,   2500.00),
    -- Printers & Scanners
    (2, 'PRN-LJ-001',  'HP LaserJet Pro MFP M428',           'pcs',    6,  2,  35000.00),
    (2, 'PRN-IJ-001',  'Epson EcoTank L3250',                'pcs',    8,  3,  12000.00),
    (2, 'PRN-TN-001',  'HP 28A Toner Cartridge (Black)',      'pcs',   25, 10,   3200.00),
    -- Projectors & AV
    (3, 'PRJ-HD-001',  'Epson EB-X51 Projector',             'pcs',    5,  2,  45000.00),
    (3, 'PRJ-MIC-001', 'Shure SM58 Dynamic Microphone',      'pcs',   12,  4,  12000.00),
    (3, 'PRJ-SPK-001', 'JBL EON715 Powered Speaker',         'pcs',    8,  2,  45000.00),
    -- Storage
    (4, 'STR-USB-001', 'SanDisk Ultra 32GB Flash Drive',     'pcs',   50, 15,    350.00),
    (4, 'STR-FLP-001', 'Floppy Disk 3.5" HD (box of 10)',    'box',    5,  2,   1200.00),
    -- Vehicles
    (7, 'VEH-SDN-001', 'Toyota Corolla Sedan (Ministry Car)','pcs',    3,  1, 3500000.00),
    (7, 'VEH-VAN-001', 'Toyota HiAce Van (15-seater)',       'pcs',    2,  1, 4500000.00),
    (7, 'VEH-TRK-001', 'Isuzu NPR Cargo Truck',             'pcs',    1,  0, 6000000.00),
    -- Books & Literature
    (8, 'BK-BIB-001',  'Holy Bible — NIV (English)',          'pcs',  150, 30,    850.00),
    (8, 'BK-BIB-003',  'Holy Bible — Amharic Translation',   'pcs',  200, 50,    650.00),
    (8, 'BK-TRC-001',  'Gospel Tract — Amharic (pack/100)',  'pack', 100, 30,    200.00),
    (8, 'BK-HYM-001',  'Hymnal Book — Amharic',              'pcs',   75, 20,    400.00),
    -- Furniture
    (9, 'FRN-DSK-001', 'Office Desk — Standard (120x60cm)',  'pcs',   10,  3,   8500.00),
    (9, 'FRN-CHR-001', 'Office Chair — Swivel',              'pcs',   15,  5,   5500.00),
    (9, 'FRN-PDM-001', 'Wooden Podium / Pulpit',             'pcs',    3,  1,  15000.00),
    -- Cameras
    (10,'CAM-DSL-001', 'Canon EOS 90D DSLR Camera',          'pcs',    2,  1, 120000.00),
    (10,'CAM-VID-001', 'Sony FX3 Video Camera',              'pcs',    1,  0, 250000.00);
