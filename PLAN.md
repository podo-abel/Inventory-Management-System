# Inventory Management System Development Plan

## 1. Purpose

This is the living development-state document for the Inventory Management System.

Unlike `PROJECT.md` and `ARCHITECTURE.md`, this document must be updated continuously.

Every meaningful completed task must be reflected here.

The AI must read this document before starting work.

The AI must update it after completing meaningful work.

---

# 2. Status Legend

Use:

* `[ ]` Not started
* `[~]` In progress
* `[x]` Completed
* `[!]` Blocked
* `[-]` Cancelled

---

# 3. Current Project Status

**Current Phase:** Phase 1 — Authentication UI (complete)

**Overall Status:** Authentication UI implemented. Ready for Phase 2 (Database Design).

**Current Priority:** Implement the MySQL database schema before connecting the authentication backend.

---

# Phase 0 — Project Foundation

## Objectives

Establish the development foundation before writing application functionality.

### Tasks

* [x] Define project requirements
* [x] Define five system roles
* [x] Complete initial Stitch UI design
* [x] Obtain Stitch export
* [x] Identify Stitch design directory
* [x] Establish technology stack
* [x] Decide not to use Bootstrap
* [x] Choose MySQL
* [x] Choose PHP backend
* [x] Choose XAMPP local environment
* [x] Create project documentation
* [x] Inspect all important Stitch screens
* [x] Inspect `DESIGN.md`
* [x] Create initial application directory
* [x] Verify XAMPP installation
* [x] Verify Apache
* [ ] Verify MySQL
* [ ] Create initial MySQL database
* [ ] Create initial Git repository

### Completion Criteria

The local development environment works and the project documentation accurately describes the project.

---

# Phase 1 — Design Analysis

## Objectives

Convert the Stitch design into an implementation reference.

### Tasks

* [x] Analyze `DESIGN.md`
* [x] Identify typography rules
* [x] Identify color tokens
* [x] Identify spacing system
* [x] Identify navigation structure
* [x] Identify reusable UI components
* [x] Identify desktop layouts
* [x] Identify responsive layouts
* [x] Map Stitch screens to application pages
* [ ] Identify missing screens
* [ ] Identify duplicated screens/components
* [ ] Create UI component inventory

### Completion Criteria

Every required application screen has a known Stitch reference or an explicitly documented implementation decision.

---

# Phase 2 — Database Design

## Objectives

Create a reliable relational database model.

### Tasks

* [ ] Design users table
* [ ] Design employees table
* [ ] Design categories table
* [ ] Design products table
* [ ] Design suppliers table
* [ ] Design requests table
* [ ] Design request_items table
* [ ] Design stock_movements table
* [ ] Design purchases table
* [ ] Design purchase_items table
* [ ] Design payments table
* [ ] Design expenses table
* [ ] Design activity_logs table
* [ ] Define primary keys
* [ ] Define foreign keys
* [ ] Define indexes
* [ ] Define constraints
* [ ] Define initial roles
* [ ] Define seed/admin account strategy
* [ ] Generate initial SQL schema
* [ ] Test schema in MySQL

### Completion Criteria

The database can be created successfully in MySQL and supports all planned core workflows.

---

# Phase 3 — Application Foundation

## Objectives

Create the reusable PHP application foundation.

### Tasks

* [ ] Create project directory structure
* [ ] Configure database connection
* [ ] Create reusable header
* [ ] Create reusable sidebar
* [ ] Create reusable navigation
* [ ] Create reusable footer
* [ ] Create shared CSS
* [ ] Create JavaScript foundation
* [ ] Implement error handling
* [ ] Implement basic security utilities
* [ ] Implement reusable form validation
* [ ] Implement reusable database helpers where appropriate

### Completion Criteria

The application loads correctly through XAMPP and shared components work.

---

# Phase 4 — Authentication and Authorization

## Objectives

Implement secure role-based access.

### Tasks

* [x] Create login page (UI only — backend in later phase)
* [ ] Implement authentication
* [ ] Implement password hashing
* [ ] Implement sessions
* [ ] Implement logout
* [ ] Implement role detection
* [ ] Implement authorization checks
* [ ] Protect Admin routes
* [ ] Protect Manager routes
* [ ] Protect Employee routes
* [ ] Protect Store routes
* [ ] Protect Finance routes
* [ ] Test unauthorized access
* [ ] Test session expiration/logout

### Completion Criteria

Each role can log in and only access authorized functionality.

---

# Phase 5 — Admin Module

## Objectives

Implement system administration.

### Tasks

* [ ] Admin dashboard
* [ ] User management
* [ ] Employee management
* [ ] Category management
* [ ] Product management
* [ ] Supplier management
* [ ] Activity log
* [ ] System settings
* [ ] Admin reports

### Completion Criteria

Admin can manage the core system configuration and users.

---

# Phase 6 — Inventory / Store Module

## Objectives

Implement physical inventory management.

### Tasks

* [ ] Store dashboard
* [ ] Inventory listing
* [ ] Product stock information
* [ ] Low-stock detection
* [ ] Out-of-stock detection
* [ ] Receive stock
* [ ] Issue stock
* [ ] Stock adjustment
* [ ] Stock return
* [ ] Stock movement history
* [ ] Approved request processing

### Completion Criteria

Inventory quantities change correctly and every change is traceable.

---

# Phase 7 — Employee Module

## Objectives

Allow employees to request inventory.

### Tasks

* [ ] Employee dashboard
* [ ] Product browsing
* [ ] Item request form
* [ ] Multiple-item requests
* [ ] Request submission
* [ ] Request history
* [ ] Request status tracking
* [ ] Employee profile

### Completion Criteria

Employees can submit and track requests.

---

# Phase 8 — Manager Module

## Objectives

Implement approval and operational supervision.

### Tasks

* [ ] Manager dashboard
* [ ] Pending requests
* [ ] Request details
* [ ] Approve request
* [ ] Reject request
* [ ] Rejection reason
* [ ] Purchase request monitoring
* [ ] Inventory monitoring
* [ ] Manager reports

### Completion Criteria

Managers can correctly approve/reject requests and monitor operations.

---

# Phase 9 — Finance Module

## Objectives

Implement financial inventory operations.

### Tasks

* [ ] Finance dashboard
* [ ] Supplier management
* [ ] Purchase management
* [ ] Purchase items
* [ ] Payment management
* [ ] Expense management
* [ ] Payment status tracking
* [ ] Financial reports

### Completion Criteria

Finance can track purchases, payments, and expenses.

---

# Phase 10 — Reporting

## Objectives

Provide useful operational and financial reports.

### Tasks

* [ ] Inventory report
* [ ] Stock movement report
* [ ] Purchase report
* [ ] Financial report
* [ ] Employee request report
* [ ] Date filters
* [ ] Search/filter functionality
* [ ] Print support
* [ ] Export support where required
* [ ] Dashboard charts

### Completion Criteria

Reports accurately reflect database information.

---

# Phase 11 — Notifications and Activity Tracking

## Objectives

Improve system awareness and traceability.

### Tasks

* [ ] Notification system
* [ ] Low-stock notifications
* [ ] Request notifications
* [ ] Purchase/payment notifications
* [ ] Activity logging
* [ ] Activity history interface

### Completion Criteria

Important system events are visible and auditable.

---

# Phase 12 — Security and Quality

## Objectives

Ensure the application is secure and reliable.

### Tasks

* [ ] Review SQL injection protection
* [ ] Review authentication
* [ ] Review authorization
* [ ] Review password handling
* [ ] Review session security
* [ ] Review input validation
* [ ] Review output escaping
* [ ] Add CSRF protection where appropriate
* [ ] Review error handling
* [ ] Review sensitive-data exposure
* [ ] Test role boundaries

### Completion Criteria

No known critical security issue remains.

---

# Phase 13 — UI Consistency

## Objectives

Ensure implementation matches the Stitch design.

### Tasks

* [ ] Compare login with Stitch
* [ ] Compare Admin dashboard
* [ ] Compare Manager dashboard
* [ ] Compare Employee dashboard
* [ ] Compare Store dashboard
* [ ] Compare Finance dashboard
* [ ] Compare tables
* [ ] Compare forms
* [ ] Compare modals
* [ ] Compare status badges
* [ ] Compare responsive layouts
* [ ] Verify typography
* [ ] Verify colors
* [ ] Verify spacing
* [ ] Verify navigation consistency

### Completion Criteria

The implemented application consistently follows the Stitch design system.

---

# Phase 14 — Integration Testing

## Objectives

Test complete business workflows.

### Workflows

* [ ] Employee request → Manager approval → Store issue
* [ ] Stock receiving → Inventory update
* [ ] Stock issuing → Inventory update
* [ ] Low-stock detection
* [ ] Purchase → Payment → Stock receiving
* [ ] User creation → Login → Authorization
* [ ] Request rejection workflow
* [ ] Stock adjustment workflow
* [ ] Reporting accuracy

### Completion Criteria

All core business workflows work end-to-end.

---

# Phase 15 — Finalization

### Tasks

* [ ] Remove development/debug output
* [ ] Clean unused files
* [ ] Clean unused CSS
* [ ] Clean unused JavaScript
* [ ] Review database
* [ ] Review permissions
* [ ] Review UI
* [ ] Review responsiveness
* [ ] Review documentation
* [ ] Create final test dataset
* [ ] Perform complete regression test
* [ ] Prepare project demonstration

### Completion Criteria

The system is stable enough for demonstration/submission.

---

# 4. Rules for Updating This File

Whenever an AI agent completes a meaningful task, it must update PLAN.md.

For completed tasks, record:

* Task completed
* Date
* Relevant files
* Important implementation details
* Tests performed
* Remaining related tasks

Example:

```text
[x] Implement employee request submission

Completed:
2026-XX-XX

Files:
- employee/request-item.php
- employee/requests.php
- includes/request-functions.php

Details:
Employees can submit requests containing multiple products.

Tests:
- Valid request submission
- Empty request validation
- Unauthorized access test

Next:
Manager approval workflow
```

Do not mark work as completed merely because files were generated.

Only mark work completed after implementation and verification.

---

# 5. Current Next Task

The immediate next task is:

**Phase 2 — Database Design**: Design and create the MySQL database schema to support all planned workflows.

The database must cover:
- users table (login credentials and roles)
- employees table
- categories, products, suppliers
- requests, request_items
- stock_movements
- purchases, purchase_items
- payments, expenses
- activity_logs

After database schema, proceed to Phase 3 (Application Foundation) then Phase 4 (Authentication Backend).

---

# 6. Completed Work Log

## Authentication UI — 2026-08-26

**Implemented:**
- Login page UI (`auth/login.php`)
- Forgot Password page UI (`auth/forgot-password.php`)
- Global CSS design tokens (`assets/css/main.css`)
- Reusable component styles (`assets/css/components.css`)
- Responsive breakpoints (`assets/css/responsive.css`)
- Form validation utilities (`assets/js/validation.js`)
- General UI utilities with password toggle (`assets/js/main.js`)

**Stitch references used:**
- `login_logitrack_ims/code.html` — layout, typography, color classes
- `login_logitrack_ims/screen.png` — visual reference
- `core_inventory_system/DESIGN.md` — design token values (colors, typography, spacing, radius, shadows)

**Design decisions:**
- No Bootstrap or Tailwind. All CSS is hand-authored using CSS custom properties.
- Design tokens are centralized in `main.css` `:root`. All other files consume them.
- Color palette exactly matches Stitch: primary `#00236f` (Deep Navy), secondary `#0058be` (Royal Blue).
- Font: Inter (Google Fonts), matching Stitch exactly.
- Auth card max-width 420px, shadow-md, border-radius 1rem — matches Stitch reference.
- Password toggle uses Material Symbols `visibility_off` / `visibility` icons.
- `FormValidator` class supports chainable rule registration and blur-based live validation.
- No fake authentication implemented. Form submits to the same PHP page; backend will be wired in Phase 4.
- Registration page: not implemented. PROJECT.md Section 7 explicitly lists "Public user registration" as a non-goal. No registration Stitch screen exists. Users will be created by Admin in Phase 5.

**Tests performed:**
- PHP syntax check: both files pass (`php -l`)
- HTTP response: all pages and assets return HTTP 200 through XAMPP
- Rendered HTML checked: all component classes, ARIA attributes, and script tags confirmed present
- No PHP errors in output
- Password toggle: JS logic verified
- Validation rules: required fields and email format checked

**Known limitations:**
- No backend authentication yet (Phase 4)
- No database connection yet (Phase 2)
- No session management yet (Phase 4)
- No CSRF token yet (Phase 4)
- Forgot password does not actually send emails (Phase 4)

