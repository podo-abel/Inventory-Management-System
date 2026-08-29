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

**Current Phase:** Phase 9 — Finance Module (complete)

**Overall Status:** Modules built through Phase 9. Ready for Phase 10 (Reporting).

**Current Priority:** Complete API endpoints, Reporting, and Notifications (Phases 10-11).

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
* [x] Verify MySQL
* [x] Create initial MySQL database
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
* [x] Identify missing screens
* [x] Identify duplicated screens/components
* [x] Create UI component inventory

### Completion Criteria

Every required application screen has a known Stitch reference or an explicitly documented implementation decision.

---

# Phase 2 — Database Design

## Objectives

Create a reliable relational database model.

### Tasks

* [x] Design users table
* [x] Design employees table
* [x] Design categories table
* [x] Design products table
* [x] Design suppliers table
* [x] Design requests table
* [x] Design request_items table
* [x] Design stock_movements table
* [x] Design purchases table
* [x] Design purchase_items table
* [x] Design payments table
* [x] Design expenses table
* [x] Design activity_logs table
* [x] Define primary keys
* [x] Define foreign keys
* [x] Define indexes
* [x] Define constraints
* [x] Define initial roles
* [x] Define seed/admin account strategy
* [x] Generate initial SQL schema
* [x] Test schema in MySQL

### Completion Criteria

The database can be created successfully in MySQL and supports all planned core workflows.

---

# Phase 3 — Application Foundation

## Objectives

Create the reusable PHP application foundation.

### Tasks

* [x] Create project directory structure
* [x] Configure database connection
* [x] Create reusable header
* [x] Create reusable sidebar
* [x] Create reusable navigation
* [x] Create reusable footer
* [x] Create shared CSS
* [x] Create JavaScript foundation
* [x] Implement error handling
* [x] Implement basic security utilities
* [x] Implement reusable form validation
* [x] Implement reusable database helpers where appropriate

### Completion Criteria

The application loads correctly through XAMPP and shared components work.

---

# Phase 4 — Authentication and Authorization

## Objectives

Implement secure role-based access.

### Tasks

* [x] Create login page (UI only — backend in later phase)
* [x] Implement authentication
* [x] Implement password hashing
* [x] Implement sessions
* [x] Implement logout
* [x] Implement role detection
* [x] Implement authorization checks
* [x] Protect Admin routes
* [x] Protect Manager routes
* [x] Protect Employee routes
* [x] Protect Store routes
* [x] Protect Finance routes
* [x] Test unauthorized access
* [x] Test session expiration/logout

### Completion Criteria

Each role can log in and only access authorized functionality.

---

# Phase 5 — Admin Module

## Objectives

Implement system administration.

### Tasks

* [x] Admin dashboard
* [x] User management
* [x] Employee management
* [x] Category management
* [x] Product management
* [x] Supplier management
* [x] Activity log
* [x] System settings
* [x] Admin reports

### Completion Criteria

Admin can manage the core system configuration and users.

---

# Phase 6 — Inventory / Store Module

## Objectives

Implement physical inventory management.

### Tasks

* [x] Store dashboard
* [x] Inventory listing
* [x] Product stock information
* [x] Low-stock detection
* [x] Out-of-stock detection
* [x] Receive stock
* [x] Issue stock
* [x] Stock adjustment
* [x] Stock return
* [x] Stock movement history
* [x] Approved request processing

### Completion Criteria

Inventory quantities change correctly and every change is traceable.

---

# Phase 7 — Employee Module

## Objectives

Allow employees to request inventory.

### Tasks

* [x] Employee dashboard
* [x] Product browsing
* [x] Item request form
* [x] Multiple-item requests
* [x] Request submission
* [x] Request history
* [x] Request status tracking
* [x] Employee profile

### Completion Criteria

Employees can submit and track requests.

---

# Phase 8 — Manager Module

## Objectives

Implement approval and operational supervision.

### Tasks

* [x] Manager dashboard
* [x] Pending requests
* [x] Request details
* [x] Approve request
* [x] Reject request
* [x] Rejection reason
* [x] Purchase request monitoring
* [x] Inventory monitoring
* [x] Manager reports

### Completion Criteria

Managers can correctly approve/reject requests and monitor operations.

---

# Phase 9 — Finance Module

## Objectives

Implement financial inventory operations.

### Tasks

* [x] Finance dashboard
* [x] Supplier management
* [x] Purchase management
* [x] Purchase items
* [x] Payment management
* [x] Expense management
* [x] Payment status tracking
* [x] Financial reports

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
* [x] Activity logging
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

* [x] Compare login with Stitch
* [x] Compare Admin dashboard
* [x] Compare Manager dashboard
* [x] Compare Employee dashboard
* [x] Compare Store dashboard
* [x] Compare Finance dashboard
* [x] Compare tables
* [x] Compare forms
* [x] Compare modals
* [x] Compare status badges
* [x] Compare responsive layouts
* [x] Verify typography
* [x] Verify colors
* [x] Verify spacing
* [x] Verify navigation consistency

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
* [x] Low-stock detection
* [ ] Purchase → Payment → Stock receiving
* [ ] User creation → Login → Authorization
* [ ] Request rejection workflow
* [x] Stock adjustment workflow
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

**Phase 10 — Reporting**: Add detailed reports for each role.
[x] Implement Design Fixes based on Audit Report

Completed:
2026-08-29

Files:
- assets/css/components.css
- assets/css/layout.css
- assets/css/responsive.css
- assets/js/main.js
- includes/sidebar.php
- includes/navbar.php
- includes/footer.php
- admin/dashboard.php
- employee/request-item.php
- manager/requests/view.php
- store/requests/view.php

Details:
Redesigned the application to match the Stitch design system. Applied BEM classes for components, updated sidebar and navbar architecture, added responsive toggles, added Chart.js to admin dashboard, updated Employee request layout (8/4 split), Manager approval layout (7/5 split), and Store issue stock layout.

Tests:
- Navigated pages to verify visual layout.
- Reviewed POST arrays structure in request-item.php to ensure backend compatibility.

Next:
Phase 10 — Reporting
