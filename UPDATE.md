# GCM IMS — Update Log

## Rebranding & Configuration Update

**Date:** September 9, 2026
**Previous Identity:** Logicore IMS / Logitrack IMS
**New Identity:** GCM IMS (Great Commission Ministry — Inventory Management System)

---

## 1. Branding Changes

All references to "Logicore" and "Logitrack" have been replaced with "GCM IMS" across the entire codebase.

### Files Updated

#### Configuration
| File | Change |
|------|--------|
| `config/app.php` | `APP_NAME` → `'GCM IMS'`, `SESSION_NAME` → `'gcm_session'` |
| `config/database.php` | `DB_NAME` → `'gcm_ims'`, error log tag → `[GCM DB]` |

#### Authentication
| File | Change |
|------|--------|
| `auth/login.php` | Page title, meta description, brand heading, subtitle, aria-labels, error log tags |
| `auth/logout.php` | File comment, error log tag |
| `auth/forgot-password.php` | Page title, meta description, brand heading, file comment |

#### Entry Point
| File | Change |
|------|--------|
| `index.php` | File comment header |

#### Shared Includes
| File | Change |
|------|--------|
| `includes/auth.php` | File comment, error log tag |
| `includes/alerts.php` | File comment |
| `includes/header.php` | File comment, style comment |
| `includes/sidebar.php` | File comment, logo alt text |
| `includes/navbar.php` | File comment |
| `includes/footer.php` | File comment, copyright holder → `Great Commission Ministry` |
| `includes/functions.php` | File comment, error log tags (×2) |
| `includes/permissions.php` | File comment |

#### Module Error Logs
| File | Change |
|------|--------|
| `store/receive-stock.php` | `[Logitrack]` → `[GCM]` |
| `store/issue-stock.php` | `[Logitrack]` → `[GCM]` |
| `store/stock-adjustment.php` | `[Logitrack]` → `[GCM]` |
| `store/requests/view.php` | `[Logitrack]` → `[GCM]` |
| `finance/purchases/create.php` | `[Logitrack]` → `[GCM]` |
| `employee/request-item.php` | `[Logitrack]` → `[GCM]` |

#### Frontend Assets
| File | Change |
|------|--------|
| `assets/css/main.css` | File comment |
| `assets/css/layout.css` | File comment |
| `assets/css/responsive.css` | File comment |
| `assets/js/validation.js` | File comment |
| `assets/images/favicon.svg` | Comment label |
| `assets/images/logo.svg` | Comment label |
| `assets/images/logo-sidebar.svg` | Comment label |

#### Database Scripts
| File | Change |
|------|--------|
| `database/schema.sql` | Header comment, database name → `gcm_ims`, timezone → `+03:00` |
| `database/seed.sql` | Complete rewrite with GCM data (see sections below) |

---

## 2. Configuration Changes

| Setting | Before | After |
|---------|--------|-------|
| App Name | `Logicore IMS` | `GCM IMS` |
| Session Name | `logitrack_session` | `gcm_session` |
| Database Name | `logitrack_ims` | `gcm_ims` |
| Currency Symbol | `₱` (Philippine Peso) | `ETB ` (Ethiopian Birr) |
| Timezone | `America/New_York` | `Africa/Addis_Ababa` |
| Login Subtitle | `Login to your operational hub` | `Creating Spiritual Movement` |
| Copyright | `Logicore Operations` | `Great Commission Ministry` |

---

## 3. Database Migration

The `logitrack_ims` database was cloned to `gcm_ims` with the following modifications:

### User Emails Updated

| Username | Old Email | New Email |
|----------|-----------|-----------|
| admin | admin@logitrack.local | admin@gcm.local |
| manager | manager@logitrack.local | manager@gcm.local |
| employee | employee@logitrack.local | employee@gcm.local |
| store | store@logitrack.local | store@gcm.local |
| finance | finance@logitrack.local | finance@gcm.local |

> **Note:** Login credentials remain unchanged. All test passwords follow the `Role@1234` pattern.

---

## 4. New Product Categories

Previous categories (Office Supplies, IT Equipment, Cleaning Supplies, Safety Equipment, Furniture) were replaced with 10 ministry-relevant categories:

| # | Category | Description |
|---|----------|-------------|
| 1 | Computers & Laptops | Desktop computers, laptops, and accessories |
| 2 | Printers & Scanners | Laser printers, inkjet printers, scanners, and MFPs |
| 3 | Projectors & AV Equipment | Projectors, screens, audio equipment, microphones, speakers |
| 4 | Storage Devices | Flash drives, external hard drives, SSDs, floppy disks, memory cards |
| 5 | Networking Equipment | Routers, switches, cables, access points, modems |
| 6 | Office Supplies | Stationery, paper, pens, toner, ink cartridges |
| 7 | Vehicles | Ministry cars, vans, trucks, and vehicle parts |
| 8 | Books & Literature | Bibles, devotionals, ministry books, tracts, pamphlets |
| 9 | Furniture | Desks, chairs, podiums, bookshelves, filing cabinets |
| 10 | Cameras & Photography | Digital cameras, video cameras, tripods, lighting equipment |

---

## 5. New Products (95 Total)

All prices are in Ethiopian Birr (ETB).

### Computers & Laptops (10 items)
- Dell OptiPlex Desktop PC, HP ProDesk 400 G7, Lenovo ThinkCentre M90
- Dell Latitude 5520, HP ProBook 450 G8, Lenovo ThinkPad T14, MacBook Air M2
- Dell 24" Monitor, Logitech Wireless Keyboard, Logitech Wireless Mouse

### Printers & Scanners (8 items)
- HP LaserJet Pro MFP M428, Canon imageRUNNER 2625i
- Epson EcoTank L3250, HP DeskJet 2720e
- Epson DS-530 II Scanner
- HP 28A Toner, Canon GPR-55 Toner, Epson 003 Ink Bottle

### Projectors & AV Equipment (8 items)
- Epson EB-X51, BenQ MH560 Projectors
- 120" Tripod Projector Screen
- Shure SM58 Microphone, Wireless Lapel Microphone Set
- JBL EON715 Speaker, Yamaha MG10XU Mixer
- HDMI Cable 5m

### Storage Devices (8 items)
- SanDisk Ultra 32GB / 64GB / Kingston 128GB Flash Drives
- Seagate 1TB / WD 2TB External HDDs
- Samsung 500GB Portable SSD
- Floppy Disk 3.5" HD (box of 10)
- SanDisk 64GB MicroSD Card

### Networking Equipment (5 items)
- TP-Link Archer AX50 Router, TP-Link 24-Port Switch
- Cat6 Ethernet Cables (5m and 30m)
- Ubiquiti UniFi AC Access Point

### Office Supplies (12 items)
- A4 Copy Paper, A4 Colored Paper
- Ballpoint Pens (Blue and Black), Whiteboard Marker Set
- Heavy-Duty Stapler, Staple Pins
- Brown Envelopes, Manila Folders, Notebooks
- Scotch Tape, Scissors

### Vehicles (10 items)
- Toyota Corolla Sedan, Suzuki Dzire (personal ministry cars)
- Toyota Land Cruiser Prado, Hyundai Tucson (SUVs)
- Toyota HiAce Van (15-seater)
- Isuzu NPR Cargo Truck, Mitsubishi Canter Truck
- Vehicle Tires, Engine Oil, Car Battery (parts/consumables)

### Books & Literature (16 items)
- Holy Bible — NIV, KJV, Amharic Translation, Oromo Translation
- New Testament Pocket Edition
- Daily Devotional (365 Days), Morning & Evening (Spurgeon)
- Systematic Theology (Grudem), Purpose Driven Life (Warren)
- Gospel Tracts — Amharic, English, Oromo (packs of 100)
- Ministry Pamphlets — Discipleship, Evangelism Guide
- Hymnal Book — Amharic
- Children Bible Stories (Illustrated)

### Furniture (10 items)
- Standard Office Desk, Executive Desk
- Swivel Chair, Executive Leather Chair, Visitor Chair (stackable)
- Wooden Podium / Pulpit
- 5-Tier Bookshelf, 4-Drawer Filing Cabinet
- Conference Table (8-seater), Whiteboard

### Cameras & Photography (8 items)
- Canon EOS 90D, Nikon D7500 DSLR Cameras
- Sony FX3 Video Camera, Canon XA50 Camcorder
- Manfrotto Tripod, LED Video Light Panel
- Canon 50mm f/1.8 Lens, Sony 128GB CFexpress Card

---

## 6. New Suppliers (7 Total)

| Supplier | Contact | Location | Specialty |
|----------|---------|----------|-----------|
| Ethio-Tech Solutions | Dawit Mekonnen | Bole, Addis Ababa | IT hardware and computing |
| Abyssinia Office Supplies | Sara Tadesse | Kirkos, Addis Ababa | Stationery and office consumables |
| Addis Motors PLC | Yohannes Bekele | Nifas Silk Lafto, Addis Ababa | Vehicles and auto parts |
| Gospel Literature Press | Miriam Haile | Arada, Addis Ababa | Bibles, books, tracts, and literature |
| Sheba Furniture & Interiors | Abebe Kebede | Yeka, Addis Ababa | Office and church furniture |
| Digital Camera Ethiopia | Henok Assefa | Bole, Addis Ababa | Cameras, lenses, video equipment |
| NetConnect Ethiopia | Feven Girma | Lideta, Addis Ababa | Networking and connectivity |

---

## 7. What Was NOT Changed

The following remain exactly as before:

- **Roles and permissions** — Admin, Manager, Employee, Store, Finance (unchanged)
- **Authentication system** — Login, logout, CSRF, session handling (unchanged)
- **Application structure** — All modules, pages, and routing (unchanged)
- **Database schema** — All 14 tables and their columns (unchanged)
- **Frontend design** — Layout, CSS, components, responsive behavior (unchanged)
- **Business workflows** — Request → Approval → Issue, Purchase → Pay (unchanged)
- **Login credentials** — All test passwords still follow `Role@1234` pattern

---

## 8. How to Apply These Changes to a Fresh Setup

1. Start XAMPP (Apache + MySQL)
2. Run `database/schema.sql` to create the `gcm_ims` database and tables
3. Run `database/seed.sql` to populate default users, categories, suppliers, and products
4. Access the application at `http://localhost/inventory-management-system/`
5. Login with `admin` / `Admin@1234`
