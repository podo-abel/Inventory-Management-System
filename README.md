# GCM Inventory Management System

A comprehensive, web-based Inventory Management System built for the **Great Commission Ministry (GCM)**. The application manages products, inventory, employee item requests, purchasing, payments, suppliers, and operational reporting — all through role-specific dashboards and workflows.

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Default Login Credentials](#default-login-credentials)
- [User Roles & Permissions](#user-roles--permissions)
- [Business Workflows](#business-workflows)
- [Project Structure](#project-structure)
- [Database Schema](#database-schema)
- [API Endpoints](#api-endpoints)
- [Security](#security)
- [Configuration](#configuration)
- [Testing](#testing)
- [Screenshots](#screenshots)
- [Troubleshooting](#troubleshooting)
- [License](#license)

---

## Overview

The GCM Inventory Management System centralizes inventory operations for the organization. It tracks products, stock quantities, stock movements, employee item requests, supplier management, purchase orders, payments, and expenses — all through a secure, role-based interface.

**Key Highlights:**
- 5 distinct user roles with tailored dashboards
- Multi-step approval workflow for inventory requests
- Full audit trail for all stock movements
- Real-time stock availability checking
- In-app notification system
- CSV export and Chart.js analytics
- Responsive design based on a custom design system
- Runs entirely on a local XAMPP environment

---

## Features

### Authentication & Authorization
- Secure login with bcrypt password hashing (cost factor 12)
- PHP session-based authentication with secure session handling
- Role-based access control enforced server-side
- CSRF token protection on all forms
- Session regeneration on login

### Product & Inventory Management
- Full product catalog with SKU, categories, units, and pricing
- Real-time stock level tracking
- Low-stock and out-of-stock monitoring with configurable thresholds
- Stock movements (Receive, Issue, Adjustment, Return) with full audit trail
- Every stock change creates an immutable `stock_movements` record

### Employee Request System
- Employees submit multi-item requests
- Live stock availability warnings during request creation
- Multi-step approval workflow: Store Verification → Manager Confirmation → Stock Issuance
- Request tracking with status badges and timeline
- Employee receipt confirmation

### Purchasing & Finance
- Purchase order management with line items
- Multi-status purchase lifecycle (Draft → Pending Approval → Approved → Ordered → Received)
- Supplier management with contact details
- Payment recording with multiple methods (Cash, Bank Transfer, Cheque, Card)
- General expense tracking
- Financial reporting and analytics

### Reporting & Analytics
- Inventory reports with stock levels and valuation
- Stock movement history reports
- Purchase and payment reports
- Employee request reports
- Chart.js visualizations on dashboards
- CSV export for all report types

### Notifications
- In-app notification system for all users
- Real-time notification badge in the navbar
- Automatic notifications for request status changes, approvals, low stock alerts
- AJAX-powered notification dropdown with mark-as-read functionality

### Activity Logging
- Comprehensive audit trail for important system actions
- Tracks user, action type, entity, and timestamp
- Admin-viewable activity log

---

## Technology Stack

| Layer       | Technology                    |
|-------------|-------------------------------|
| Frontend    | HTML5, CSS3, Vanilla JavaScript |
| Backend     | PHP 8.x                      |
| Database    | MySQL / MariaDB 10.4+        |
| Server      | Apache (XAMPP)                |
| Charts      | Chart.js                     |
| Icons       | Google Material Symbols       |
| Fonts       | Google Fonts (Inter)          |

> **No external frameworks** — no Bootstrap, React, Vue, Angular, Laravel, Tailwind, or Node.js.

---

## Prerequisites

- [XAMPP](https://www.apachefriends.org/) (includes Apache, PHP, and MySQL/MariaDB)
  - PHP 8.0 or higher
  - MariaDB 10.4 or higher
- A modern web browser (Chrome, Firefox, Edge, Safari)

---

## Installation

### 1. Install XAMPP

Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/).

### 2. Clone the Repository

```bash
cd /opt/lampp/htdocs
git clone https://github.com/podo-abel/Inventory-Management-System.git inventory-management-system
```

### 3. Start XAMPP Services

```bash
sudo /opt/lampp/lampp start
```

Or start individual services:

```bash
sudo /opt/lampp/lampp startapache
sudo /opt/lampp/lampp startmysql
```

### 4. Create the Database

Open phpMyAdmin at `http://localhost/phpmyadmin` or use the MySQL command line:

```bash
/opt/lampp/bin/mysql -u root < /opt/lampp/htdocs/inventory-management-system/database/schema.sql
```

### 5. Load Seed Data

```bash
/opt/lampp/bin/mysql -u root < /opt/lampp/htdocs/inventory-management-system/database/seed.sql
```

This creates the default users, sample categories, products, and suppliers.

### 6. Configure Database Connection

Edit `config/database.php` if your MySQL credentials differ from the defaults:

```php
define('DB_HOST',    'localhost');
define('DB_NAME',    'gcm_ims');
define('DB_USER',    'root');
define('DB_PASS',    '');        // Default XAMPP has no password
define('DB_CHARSET', 'utf8mb4');
```

### 7. Access the Application

Open your browser and navigate to:

```
http://localhost/inventory-management-system/
```

---

## Default Login Credentials

After running the seed data, the following test accounts are available:

| Role     | Username   | Password       | Full Name             |
|----------|------------|----------------|-----------------------|
| Admin    | `admin`    | `Admin@1234`   | System Administrator  |
| Manager  | `manager`  | `Manager@1234` | Operations Manager    |
| Employee | `employee` | `Employee@1234`| Jane Employee         |
| Store    | `store`    | `Store@1234`   | Store Keeper          |
| Finance  | `finance`  | `Finance@1234` | Finance Officer       |

> ⚠️ **Important:** Change all default passwords before using in a production environment.

---

## User Roles & Permissions

### Admin
Full system access including:
- User & employee management (create, edit, disable)
- Product, category, and supplier management
- System settings and configuration
- Activity log viewing
- All reports

### Manager
Operational oversight:
- View and monitor employee requests
- **Confirm or reject** store-verified requests (approval gate)
- View inventory levels
- View purchase orders
- View reports
- Employee activity monitoring

### Employee
Item requesting:
- Browse product catalog
- Submit multi-item requests
- Track request status and history
- Receive low-stock warnings
- Confirm receipt of issued items

### Store
Inventory operations:
- **Review and verify** pending employee requests (stock availability check)
- Issue approved stock to employees
- Receive stock from purchase orders
- Perform stock adjustments
- Record stock movements
- View inventory and reports

### Finance
Financial operations:
- Manage purchase orders
- Record and track payments
- Manage suppliers
- Track expenses
- Financial reporting and analytics

### Permission Matrix

| Permission           | Admin | Manager | Employee | Store | Finance |
|---------------------|:-----:|:-------:|:--------:|:-----:|:-------:|
| Manage Users         |  ✅   |         |          |       |         |
| Manage Products      |  ✅   |         |          |       |         |
| Manage Categories    |  ✅   |         |          |       |         |
| Manage Suppliers     |  ✅   |         |          |       |   ✅    |
| View Inventory       |  ✅   |   ✅    |          |  ✅   |         |
| Manage Stock         |  ✅   |         |          |  ✅   |         |
| Submit Requests      |  ✅   |         |    ✅    |       |         |
| Approve Requests     |  ✅   |         |          |  ✅   |         |
| View Requests        |  ✅   |   ✅    |          |  ✅   |         |
| Process Requests     |  ✅   |         |          |  ✅   |         |
| Receive Stock        |  ✅   |         |          |  ✅   |         |
| Manage Purchases     |  ✅   |         |          |       |   ✅    |
| Manage Payments      |  ✅   |         |          |       |   ✅    |
| Manage Expenses      |  ✅   |         |          |       |   ✅    |
| View Reports         |  ✅   |   ✅    |          |       |   ✅    |
| View Activity Log    |  ✅   |         |          |       |         |
| Manage Settings      |  ✅   |   ✅    |          |       |         |

---

## Business Workflows

### Employee Item Request Workflow

```
Employee submits request (status: PENDING)
        │
        ▼
Store reviews & verifies stock availability (status: STORE_APPROVED)
        │
        ▼
Manager confirms or rejects (status: APPROVED or REJECTED)
        │
        ▼
Store issues stock items (status: ISSUED)
  • Inventory decreases
  • Stock movements recorded
        │
        ▼
Employee confirms receipt (status: COMPLETED)
```

**Request Statuses:**
| Status           | Description                                        |
|------------------|----------------------------------------------------|
| `pending`        | Newly submitted, awaiting store review              |
| `store_approved` | Verified by store, awaiting manager confirmation    |
| `approved`       | Confirmed by manager, ready for store to issue      |
| `rejected`       | Rejected by store or manager (with reason)          |
| `issued`         | Items issued by store, awaiting employee receipt    |
| `completed`      | Employee has confirmed receipt of items             |
| `cancelled`      | Cancelled by the employee before review             |

### Purchasing Workflow

```
Inventory need identified
        │
        ▼
Finance creates Purchase Order (status: DRAFT)
        │
        ▼
Purchase submitted for approval (status: PENDING_APPROVAL)
        │
        ▼
Manager approves (status: APPROVED → ORDERED)
        │
        ▼
Store receives stock (status: RECEIVED)
  • Inventory increases
  • Stock movements recorded
        │
        ▼
Finance records payment
```

**Purchase Statuses:** `draft` → `pending_approval` → `approved` → `ordered` → `partially_received` → `received` → `cancelled`

**Payment Statuses:** `pending` → `completed` / `failed` / `refunded`

---

## Project Structure

```
inventory-management-system/
│
├── index.php                    # Entry point — redirects to login/dashboard
│
├── config/
│   ├── app.php                  # App constants, timezone, session config
│   └── database.php             # PDO database connection (singleton)
│
├── auth/
│   ├── login.php                # Login form and authentication
│   ├── logout.php               # Session destruction
│   └── forgot-password.php      # Password recovery
│
├── includes/
│   ├── auth.php                 # Authentication helpers (require_auth, require_role)
│   ├── functions.php            # Utility functions (sanitize, format, paginate, etc.)
│   ├── permissions.php          # Role-permission matrix & navigation builder
│   ├── header.php               # HTML head, session init, config loading
│   ├── navbar.php               # Top navigation bar with notifications
│   ├── sidebar.php              # Role-based sidebar navigation
│   ├── footer.php               # Page footer and closing tags
│   └── alerts.php               # Flash message rendering
│
├── admin/
│   ├── dashboard.php            # Admin overview with stats and charts
│   ├── settings.php             # System settings
│   ├── activity-log.php         # System activity audit log
│   ├── users/                   # User CRUD (index, create, edit, view, delete)
│   ├── employees/               # Employee CRUD (index, create, edit, delete)
│   ├── categories/              # Category CRUD (index, create, edit, delete)
│   ├── products/                # Product CRUD (index, create, edit, delete)
│   ├── suppliers/               # Supplier CRUD (index, create, edit, delete)
│   └── reports/                 # Admin reports with CSV export
│
├── manager/
│   ├── dashboard.php            # Manager overview (awaiting confirmation count)
│   ├── employees.php            # Employee list
│   ├── employee-activity.php    # Employee activity monitoring
│   ├── inventory.php            # Inventory viewing
│   ├── reports.php              # Manager reports with charts
│   ├── purchases.php            # Redirect to purchase requests
│   ├── purchase-requests.php    # Purchase order viewing
│   ├── purchase-view.php        # Single purchase order detail
│   ├── requests.php             # Redirect to requests/index
│   └── requests/
│       ├── index.php            # All requests list with status filters
│       └── view.php             # Request detail with confirm/reject actions
│
├── employee/
│   ├── dashboard.php            # Employee overview with request stats
│   ├── request-item.php         # Submit new item request (with live stock check)
│   ├── requests.php             # Request history with status filters
│   ├── products.php             # Browse product catalog
│   ├── profile.php              # Personal profile management
│   └── requests/
│       └── view.php             # Request detail, cancel, confirm receipt
│
├── store/
│   ├── dashboard.php            # Store overview (pending + approved counts)
│   ├── requests.php             # Request list with status tabs
│   ├── inventory.php            # Full inventory management
│   ├── receive-stock.php        # Stock receiving form
│   ├── issue-stock.php          # Stock issuing form
│   ├── stock-adjustment.php     # Stock adjustment form
│   ├── stock-movements.php      # Movement history log
│   ├── reports.php              # Store reports
│   └── requests/
│       └── view.php             # Review, approve/reject, and issue requests
│
├── finance/
│   ├── dashboard.php            # Finance overview with payment stats
│   ├── inventory.php            # Inventory viewing (read-only)
│   ├── reports.php              # Financial reports
│   ├── suppliers.php            # Redirect to suppliers/index
│   ├── purchases.php            # Redirect to purchases/index
│   ├── payments.php             # Redirect to payments/index
│   ├── expenses.php             # Redirect to expenses/index
│   ├── suppliers/               # Supplier CRUD (index, create, edit)
│   ├── purchases/               # Purchase CRUD (index, create, view)
│   ├── payments/                # Payment CRUD (index, create)
│   └── expenses/                # Expense CRUD (index, create)
│
├── api/
│   ├── notifications.php        # AJAX notifications (fetch, mark read, count)
│   ├── reports.php              # CSV export endpoint
│   ├── inventory.php            # Inventory data API
│   ├── products.php             # Product search/lookup API
│   └── requests.php             # Request data API
│
├── assets/
│   ├── css/
│   │   ├── main.css             # Design tokens, colors, typography
│   │   ├── layout.css           # Page layout, grid, app shell
│   │   ├── components.css       # Buttons, cards, badges, forms, tables
│   │   ├── responsive.css       # Mobile/tablet breakpoints
│   │   └── reports.css          # Report page and print styles
│   ├── js/
│   │   ├── main.js              # Sidebar toggle, general UI
│   │   ├── notifications.js     # Notification polling and dropdown
│   │   └── validation.js        # Client-side form validation
│   └── images/                  # Logo and static assets
│
├── database/
│   ├── schema.sql               # Full database schema (14 tables)
│   ├── seed.sql                 # Sample data with test accounts
│   └── README.md                # Database documentation
│
├── tests/
│   └── integration_test.php     # Automated integration test suite
│
├── notifications.php            # Full notification center page
├── AGENTS.md                    # AI development rules
├── ARCHITECTURE.md              # Technical architecture document
├── PROJECT.md                   # Project goals and requirements
├── PLAN.md                      # Development plan and progress log
└── README.md                    # This file
```

---

## Database Schema

The application uses **14 tables** in the `gcm_ims` database:

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│     users        │────▶│    employees      │     │   categories     │
│                  │     └──────────────────┘     └────────┬────────┘
│  id              │                                       │
│  username        │     ┌──────────────────┐     ┌────────▼────────┐
│  email           │────▶│    requests       │     │    products      │
│  password_hash   │     │                  │     │                  │
│  role            │     │  id              │     │  id              │
│  full_name       │     │  user_id    ────▶│     │  category_id     │
│  is_active       │     │  reference_no    │     │  sku             │
└────────┬─────────┘     │  status          │     │  quantity_in_stock│
         │               │  reviewed_by     │     │  min_stock_level  │
         │               │  issued_by       │     └────────┬─────────┘
         │               └────────┬─────────┘              │
         │                        │                         │
         │               ┌────────▼─────────┐     ┌────────▼─────────┐
         │               │  request_items    │     │ stock_movements   │
         │               │                  │     │                  │
         │               │  request_id      │     │  product_id      │
         │               │  product_id ─────│─────│  movement_type   │
         │               │  quantity_req    │     │  quantity         │
         │               │  quantity_issued │     │  quantity_before  │
         │               └──────────────────┘     │  quantity_after   │
         │                                         │  performed_by    │
         │                                         └──────────────────┘
         │
         │               ┌──────────────────┐     ┌──────────────────┐
         │               │    suppliers      │     │    purchases      │
         │               │                  │────▶│                  │
         │               │  id              │     │  id              │
         │               │  name            │     │  reference_no    │
         │               │  contact_name    │     │  supplier_id     │
         │               └──────────────────┘     │  status          │
         │                                         │  total_amount    │
         │                                         └────────┬─────────┘
         │                                                  │
         │               ┌──────────────────┐     ┌────────▼─────────┐
         │               │    payments       │     │ purchase_items    │
         │               │                  │     │                  │
         │               │  purchase_id ────│─────│  purchase_id     │
         │               │  amount          │     │  product_id      │
         │               │  payment_method  │     │  quantity_ordered │
         │               │  status          │     │  unit_price      │
         │               └──────────────────┘     └──────────────────┘
         │
         │               ┌──────────────────┐     ┌──────────────────┐
         │               │    expenses       │     │  activity_logs    │
         │               │                  │     │                  │
         │               │  category        │     │  user_id         │
         │               │  amount          │     │  action           │
         │               │  expense_date    │     │  entity_type     │
         │               └──────────────────┘     │  description     │
         │                                         └──────────────────┘
         │
         │               ┌──────────────────┐
         └──────────────▶│  notifications    │
                          │                  │
                          │  user_id         │
                          │  title           │
                          │  message         │
                          │  is_read         │
                          └──────────────────┘
```

### Tables Summary

| #  | Table              | Description                                       |
|----|--------------------|----------------------------------------------------|
| 1  | `users`            | Authentication credentials and roles               |
| 2  | `employees`        | Extended profile for employee-role users           |
| 3  | `categories`       | Product categories                                  |
| 4  | `suppliers`        | Vendor/supplier records                             |
| 5  | `products`         | Product catalog with stock quantities               |
| 6  | `requests`         | Employee request headers                            |
| 7  | `request_items`    | Line items for each request                         |
| 8  | `stock_movements`  | Immutable audit trail for all stock changes         |
| 9  | `purchases`        | Purchase order headers                              |
| 10 | `purchase_items`   | Line items for purchase orders                      |
| 11 | `payments`         | Payments linked to purchase orders                  |
| 12 | `expenses`         | General operational expenses                        |
| 13 | `activity_logs`    | Audit trail for system events                       |
| 14 | `notifications`    | In-app notifications for users                      |

---

## API Endpoints

### Notifications API (`api/notifications.php`)

| Method | Parameter        | Description                        |
|--------|------------------|------------------------------------|
| GET    | `action=fetch`   | Fetch recent notifications         |
| GET    | `action=count`   | Get unread notification count      |
| POST   | `action=read`    | Mark a notification as read        |
| POST   | `action=read_all`| Mark all notifications as read     |

### Reports API (`api/reports.php`)

| Method | Parameter         | Description                        |
|--------|-------------------|------------------------------------|
| GET    | `type=inventory`  | Export inventory data as CSV       |
| GET    | `type=movements`  | Export stock movements as CSV      |
| GET    | `type=purchases`  | Export purchase orders as CSV      |
| GET    | `type=requests`   | Export request data as CSV         |

---

## Security

The application implements multiple layers of security:

### Authentication
- Passwords hashed with **bcrypt** (cost factor 12)
- Session-based authentication with `session_regenerate_id()` on login
- Secure session configuration (HTTPOnly cookies, SameSite=Lax)
- Configurable session lifetime (default: 2 hours)

### Authorization
- Server-side role enforcement on every protected page via `require_role()`
- Permission matrix checked via `can_user($role, $action)`
- Frontend visibility does **not** replace backend authorization

### Input Protection
- All SQL queries use **PDO prepared statements** (zero string interpolation)
- Input sanitization via `sanitize_string()`, `sanitize_int()`, `sanitize_email()`
- Output escaping via `e()` helper (wraps `htmlspecialchars()`)
- CSRF token verification on all form submissions via `verify_csrf_token()`

### Data Integrity
- Foreign key constraints throughout the schema
- Stock quantities only modified through auditable stock movement operations
- Immutable `stock_movements` table serves as the audit trail
- CHECK constraints on quantities (must be > 0)

---

## Configuration

### Application Settings (`config/app.php`)

| Constant          | Default                   | Description                    |
|-------------------|---------------------------|--------------------------------|
| `APP_NAME`        | `GCM IMS`                 | Application display name       |
| `APP_VERSION`     | `1.0.0`                   | Version string                 |
| `APP_ENV`         | `development`             | Environment (development/production) |
| `SESSION_LIFETIME`| `7200`                    | Session timeout in seconds (2h)|
| `CURRENCY_SYMBOL` | `ETB `                    | Currency display symbol        |
| `ITEMS_PER_PAGE`  | `15`                      | Default pagination size        |

### Database Settings (`config/database.php`)

| Constant     | Default      | Description              |
|-------------|--------------|--------------------------|
| `DB_HOST`    | `localhost`  | MySQL server hostname    |
| `DB_NAME`    | `gcm_ims`   | Database name            |
| `DB_USER`    | `root`       | MySQL username           |
| `DB_PASS`    | ` `(empty)   | MySQL password           |
| `DB_CHARSET` | `utf8mb4`    | Character set            |

### Timezone

The application is configured for **Africa/Addis_Ababa** (EAT, UTC+3). Change in `config/app.php`:

```php
date_default_timezone_set('Africa/Addis_Ababa');
```

---

## Testing

### Automated Integration Tests

The project includes an automated integration test suite at `tests/integration_test.php`:

```bash
php tests/integration_test.php
```

**Test Suites (9 total):**

1. **User Authentication & Permissions** — Login validation, role-based access
2. **Employee Request Submission** — Multi-item request creation
3. **Store Verification & Manager Approval** — Full approval workflow
4. **Manager Rejection** — Rejection with reason
5. **Stock Receiving** — Receive stock and inventory update
6. **Stock Adjustment** — Adjust quantities with audit trail
7. **Purchase Order & Payment** — Purchase lifecycle and payment recording
8. **Notification System** — Notification creation and retrieval
9. **Reporting Queries** — Report data integrity

### Manual Testing Checklist

- [ ] Log in as each of the 5 roles
- [ ] Submit an employee request with multiple items
- [ ] Verify the store can see and verify the request
- [ ] Verify the manager can confirm or reject
- [ ] Issue stock as the store after manager confirmation
- [ ] Confirm receipt as the employee
- [ ] Check that inventory quantities are correctly updated
- [ ] Create a purchase order and record a payment
- [ ] Verify CSV exports work for all report types
- [ ] Test notification delivery across all workflow steps

---

## Screenshots

> Dashboard views and key screens can be found in the Stitch design reference directory. The application UI closely follows the custom design system with Material Design-inspired components.

**Key Screens:**
- Login page with branded design
- Role-specific dashboards with stats cards and Chart.js analytics
- Product catalog with search and filters
- Request submission form with live stock availability
- Request review page with stock status indicators
- Inventory management with stock movements timeline
- Purchase order management with payment tracking
- Notification center with read/unread state

---

## Troubleshooting

### Common Issues

**"Can't connect to MySQL"**
```bash
sudo /opt/lampp/lampp startmysql
```

**"Access denied for user 'root'"**
- Check `config/database.php` — default XAMPP has no password for root
- If you set a MySQL password, update `DB_PASS` accordingly

**"404 Not Found"**
- Ensure the project is in `/opt/lampp/htdocs/inventory-management-system/`
- Check Apache is running: `sudo /opt/lampp/lampp startapache`

**"Database not found"**
- Run the schema: `/opt/lampp/bin/mysql -u root < database/schema.sql`
- Then the seed: `/opt/lampp/bin/mysql -u root < database/seed.sql`

**"Session expired" errors on forms**
- Clear browser cookies and re-login
- Check PHP session directory permissions

---

## License

This project is developed for internal use by the Great Commission Ministry (GCM). All rights reserved.
