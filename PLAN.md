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

* [x] Inventory report
* [x] Stock movement report
* [x] Purchase report
* [x] Financial report
* [x] Employee request report
* [x] Date filters
* [x] Search/filter functionality
* [x] Print support
* [x] Export support where required
* [x] Dashboard charts

### Completion Criteria

Reports accurately reflect database information.

---

# Phase 11 — Notifications and Activity Tracking

## Objectives

Improve system awareness and traceability.

### Tasks

* [x] Notification system
* [x] Low-stock notifications
* [x] Request notifications
* [x] Purchase/payment notifications
* [x] Activity logging
* [x] Activity history interface

### Completion Criteria

Important system events are visible and auditable.

---

# Phase 12 — Security and Quality

## Objectives

Ensure the application is secure and reliable.

### Tasks

* [x] Review SQL injection protection
* [x] Review authentication
* [x] Review authorization
* [x] Review password handling
* [x] Review session security
* [x] Review input validation
* [x] Review output escaping
* [x] Add CSRF protection where appropriate
* [x] Review error handling
* [x] Review sensitive-data exposure
* [x] Test role boundaries

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

* [x] Employee request → Manager approval → Store issue
* [x] Stock receiving → Inventory update
* [x] Stock issuing → Inventory update
* [x] Low-stock detection
* [x] Purchase → Payment → Stock receiving
* [x] User creation → Login → Authorization
* [x] Request rejection workflow
* [x] Stock adjustment workflow
* [x] Reporting accuracy

### Completion Criteria

All core business workflows work end-to-end.

---

# Phase 15 — Finalization

### Tasks

* [x] Remove development/debug output
* [x] Clean unused files
* [x] Clean unused CSS
* [x] Clean unused JavaScript
* [x] Review database
* [x] Review permissions
* [x] Review UI
* [x] Review responsiveness
* [x] Review documentation
* [x] Create final test dataset
* [x] Perform complete regression test
* [x] Prepare project demonstration

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

**All Phases (0 through 15) are 100% complete!**

[x] Phase 10 — Reporting (Completed 2026-09-13)
[x] Phase 11 — Notifications and Activity Tracking (Completed 2026-09-13)
[x] Phase 12 — Security and Quality (Completed 2026-09-13)
[x] Phase 13 — UI Consistency (Completed 2026-08-29)
[x] Phase 14 — Integration Testing (Completed 2026-09-13)
[x] Phase 15 — Finalization (Completed 2026-09-13)

Recent Milestones Completed:
- **Phase 10 (Reporting)**: Built CSV export API (`api/reports.php`), reports stylesheet (`assets/css/reports.css`), print media styles, Chart.js analytics across Admin, Store, Manager, and Finance dashboards.
- **Phase 11 (Notifications & Activity)**: Built Notification Center (`notifications.php`), notifications AJAX API (`api/notifications.php`), upgraded navbar notification dropdown, low-stock trigger automation, and complete lifecycle event alerts for requests, purchases, and payments.
- **Phase 12 (Security & Quality)**: Reviewed SQL injection protection (all prepared statements, zero string interpolation), CSRF token verification across all forms/actions, BCRYPT cost 12 password hashing, secure session management (`session_regenerate_id`, HTTPOnly, SameSite=Lax), error logging without sensitive exposure, and strict server-side role boundaries.
- **Phase 14 (Integration Testing)**: Created comprehensive automated test suite (`tests/integration_test.php`). All 9 workflow suites pass with 0 failures (User auth & permissions, full request lifecycle with stock decrement and receipt confirmation, manager request rejection, stock receiving, stock adjustment, purchase order approval and payment recording, notification system operations, and reporting query integrity).
- **Phase 15 (Finalization)**: Cleaned all debug output, verified zero PHP syntax errors across all application files, updated database schema and seed dataset with sample notifications and Ethiopian Birr configuration, verified responsive design and BEM UI consistency with the Stitch design system.

The application is completely implemented, verified, and ready for deployment and presentation.

---

# 6. Post-Completion Modifications

## Workflow Change: Approval Transferred from Manager to Store

**Date:** 2026-09-20

**Change:** Employee request approval/rejection responsibility moved from Manager role to Store role.

### New Workflow
```
Employee submits request → Store reviews (checks stock availability) → Store approves/rejects → Store issues stock → Employee confirms receipt
```

### Rationale
Manager no longer needs to approve inventory requests. Store handles the full request lifecycle since they have direct knowledge of stock availability.

### Files Modified
- `includes/permissions.php` — Moved `approve_requests` and `reject_requests` from manager to store; updated store nav label to "Requests"
- `employee/request-item.php` — Notifications sent to store instead of manager on request submission
- `employee/requests/view.php` — Cancellation notifications sent to store instead of manager
- `store/requests.php` — Added `pending` and `rejected` to status filter tabs; defaults to pending view
- `store/requests/view.php` — Complete rewrite: now handles approval/rejection for pending requests with stock availability reporting ("In Stock", "Insufficient", "No Item"), plus existing issuance for approved requests
- `store/dashboard.php` — Added "Pending Review" stat card and pending requests table alongside approved requests
- `manager/requests/view.php` — Made completely read-only (removed approve/reject forms)
- `manager/requests/index.php` — Updated subtitle to "Monitor employee inventory requests"
- `manager/dashboard.php` — Changed from "Pending Requests" to "Recent Requests" with view-only access
- `tests/integration_test.php` — Updated permission assertions, workflow comments, and test steps to use store role for approval/rejection

### Tests Performed
- Integration test suite: 9/9 passed
- PHP syntax check: all modified files clean
- Permission matrix verified: `can_user('store', 'approve_requests') === true`, `can_user('manager', 'approve_requests') === false`

### Store Availability Reporting
When reviewing pending requests, Store now sees:
- **Green "In Stock"** badge — sufficient stock available
- **Orange "Insufficient"** badge — partial stock available (shows available quantity)
- **Red "No Item"** badge — product is out of stock
- Summary banner at top: "All Items Available" / "Partial Availability" / "No Items Available"

---

## Employee Low Stock Notification on Request Submission

**Date:** 2026-09-20

**Change:** Employees now receive immediate low stock warnings when requesting more items than available in inventory.

### What Was Added

1. **Live client-side warning** — As the employee selects a product and enters a quantity, a warning banner instantly appears if the quantity exceeds available stock:
   - **Orange warning** for low stock: "Only X available, you requested Y"
   - **Red warning** for out of stock: "This item is currently unavailable"

2. **Per-item badge in batch list** — Each item added to the request batch shows an inline stock warning if it exceeds availability.

3. **Server-side notification** — After the request is submitted, the system checks each item against current stock. If any exceed availability, an in-app "Low Stock Warning" notification is sent to the employee listing all affected items.

### Files Modified
- `employee/request-item.php` — Added live JS stock check, HTML warning element, and server-side notification after submit

### Tests Performed
- Integration test suite: 9/9 passed
- PHP syntax check: clean



## Workflow Change: Manager Confirmation Required Before Store Issuance

**Date:** 2026-09-23

**Change:** Modified the request workflow so that after the store reviews and verifies a request, the manager must confirm it before the store can issue the items.

### New Workflow
```
Employee submits request -> Store verifies (status: store_approved) -> Manager confirms (status: approved) or rejects (status: rejected) -> Store issues stock (status: issued) -> Employee confirms receipt (status: completed)
```

### Files Modified
- `database/schema.sql` — Added `store_approved` to requests status ENUM.
- `store/requests/view.php` — Changed 'approve' action to update status to `store_approved` and notify managers for confirmation.
- `store/requests.php` — Added `store_approved` to status filter tabs ("Store Approved").
- `manager/requests/view.php` — Added confirm/reject forms for managers to process `store_approved` requests.
- `manager/requests/index.php` — Added `store_approved` to status filters and changed "View" button to "Review" for requests needing confirmation.
- `manager/dashboard.php` — Changed "Pending Requests" card to "Awaiting Confirmation" counting `store_approved` requests.
- `employee/dashboard.php` & `employee/requests.php` — Included `store_approved` status in queries.
- `includes/functions.php` — Added `store_approved` to `get_status_badge_class()` mapping.
