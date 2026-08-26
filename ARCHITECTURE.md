# Inventory Management System Architecture

## 1. Architecture Status

This document defines the current technical architecture of the project.

**Status:** Initial architecture

This document should only be modified when the project's actual architecture or technology stack changes.

Normal feature development should not require changes to this file.

---

# 2. Technology Stack

## Frontend

* HTML5
* CSS3
* Vanilla JavaScript

No frontend framework is currently used.

No Bootstrap is used.

The UI is implemented according to the Google Stitch design reference.

---

## Backend

* PHP

PHP is responsible for:

* Business logic
* Authentication
* Authorization
* Database interaction
* Server-side validation
* Request processing
* Inventory operations
* Purchase operations
* Payment operations
* Report generation

---

## Database

* MySQL

MySQL is the application's persistent data store.

---

## Local Server Environment

* XAMPP
* Apache
* PHP
* MySQL

The application is intended to run completely locally.

Example development URL:

`http://localhost/inventory-management-system/`

---

## Charts

Chart.js may be used for dashboard and reporting visualizations where appropriate.

---

# 3. Architectural Style

The application uses a modular server-rendered PHP architecture.

The system should separate:

* Presentation
* Business logic
* Database access
* Authentication/authorization
* Shared components

The project does not currently use a large PHP framework.

---

# 4. Proposed Project Structure

```text
inventory-management-system/
│
├── index.php
│
├── config/
│   └── database.php
│
├── auth/
│   ├── login.php
│   └── logout.php
│
├── includes/
│   ├── auth.php
│   ├── header.php
│   ├── sidebar.php
│   ├── navigation.php
│   └── footer.php
│
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── fonts/
│
├── admin/
│   ├── dashboard.php
│   ├── users.php
│   ├── products.php
│   ├── categories.php
│   ├── suppliers.php
│   ├── reports.php
│   ├── activity-log.php
│   └── settings.php
│
├── manager/
│   ├── dashboard.php
│   ├── requests.php
│   ├── inventory.php
│   ├── purchase-requests.php
│   └── reports.php
│
├── employee/
│   ├── dashboard.php
│   ├── products.php
│   ├── request-item.php
│   ├── requests.php
│   └── profile.php
│
├── store/
│   ├── dashboard.php
│   ├── inventory.php
│   ├── receive-stock.php
│   ├── issue-stock.php
│   ├── stock-movements.php
│   └── reports.php
│
├── finance/
│   ├── dashboard.php
│   ├── suppliers.php
│   ├── purchases.php
│   ├── payments.php
│   ├── expenses.php
│   └── reports.php
│
└── database/
    └── inventory.sql
```

The exact structure may evolve during implementation if a better modular structure is required. Any significant architectural change must be reflected in this document.

---

# 5. Database Architecture

The initial database model contains the following major entities:

```text
users
employees
categories
products
suppliers
requests
request_items
stock_movements
purchases
purchase_items
payments
expenses
activity_logs
```

Relationships must enforce data integrity.

Examples:

```text
users
  ↓
employees
  ↓
requests
  ↓
request_items
  ↓
products
```

```text
suppliers
  ↓
purchases
  ↓
purchase_items
  ↓
products
```

```text
products
  ↓
stock_movements
```

The exact schema should be finalized before major backend implementation begins.

---

# 6. Authentication Architecture

Authentication uses PHP sessions.

Passwords must be stored using secure password hashing.

The application must verify:

1. User credentials
2. Account status
3. User role
4. Authorization for the requested operation

Authentication must be enforced server-side.

---

# 7. Authorization Architecture

The system contains five roles:

```text
ADMIN
MANAGER
EMPLOYEE
STORE
FINANCE
```

Authorization must be role-based.

Each protected operation must verify that the current session has permission to perform the action.

Frontend visibility does not replace backend authorization.

---

# 8. Inventory Architecture

Inventory is controlled through stock transactions.

Valid inventory-changing operations include:

* RECEIVE
* ISSUE
* ADJUSTMENT
* RETURN

Each operation should create an auditable stock movement record.

The system should not allow arbitrary direct modification of inventory quantities through unrelated screens.

---

# 9. Request Architecture

Employee requests consist of:

```text
Request
    └── Request Items
            ├── Product
            └── Quantity
```

Request status follows a controlled workflow.

Example:

```text
PENDING
   ↓
APPROVED / REJECTED
   ↓
READY_FOR_ISSUE
   ↓
ISSUED
   ↓
COMPLETED
```

Exact statuses may be refined during implementation.

---

# 10. Purchasing Architecture

Purchases consist of:

```text
Purchase
    └── Purchase Items
            ├── Product
            └── Quantity
```

Purchases may be associated with suppliers and payments.

Receiving stock from a purchase must create the corresponding inventory movement.

---

# 11. Financial Architecture

Finance manages:

* Purchases
* Payments
* Expenses

Financial records should remain separate from inventory quantities while maintaining relationships between financial transactions and relevant purchases.

---

# 12. UI Architecture

The application uses reusable server-rendered components.

Shared UI elements should include:

* Header
* Sidebar
* Navigation
* Dashboard cards
* Tables
* Forms
* Modals
* Alerts
* Status badges
* Pagination
* Notifications

The UI must follow the Stitch design system.

The Stitch source is:

`/home/elijah/Desktop/stitch_enterprise_inventory_management_system`

The AI should inspect the Stitch HTML and image assets and use whichever provides the best implementation reference.

`DESIGN.md` is the authoritative visual reference.

---

# 13. Security Architecture

The application must use:

* Prepared statements
* Password hashing
* Session authentication
* Server-side authorization
* Input validation
* Output escaping
* CSRF protection where appropriate
* Secure session handling

Sensitive configuration must not be exposed to users.

---

# 14. Local Deployment Architecture

The system runs entirely on the local machine.

```text
Browser
   │
   ▼
Apache / XAMPP
   │
   ▼
PHP Application
   │
   ▼
MySQL
```

No cloud infrastructure is required for the current project.

---

# 15. Architecture Change Policy

This file must be updated when any of the following changes:

* Programming language
* Database technology
* Server architecture
* Frontend framework
* Backend framework
* Major application architecture
* Authentication architecture
* Major database architecture
* Deployment architecture

This file should NOT be updated for:

* Normal bug fixes
* New pages
* Normal CRUD features
* UI refinements
* Small database field additions that do not alter architectural decisions

When architecture changes, document:

1. What changed
2. Why it changed
3. What components are affected
4. What migration is required
5. Date of change
