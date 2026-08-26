# AGENTS.md

## 1. Purpose

This document defines the rules, responsibilities, boundaries, workflow, and quality standards that all AI agents and sub-agents must follow while developing the Inventory Management System.

The AI must treat this document as a binding development contract.

The project must be developed carefully, incrementally, and maintainably.

The AI must never make major architectural or technology decisions silently.

---

# 2. Project Context

This is a local web-based Inventory Management System.

The application will run entirely locally using XAMPP.

The authoritative project design reference is located at:

`/home/elijah/Desktop/stitch_enterprise_inventory_management_system`

The Stitch export contains:

* HTML implementations
* Design screenshots/images
* `DESIGN.md`
* Other supporting design assets

The AI must inspect this directory before implementing UI.

`DESIGN.md` is especially important and must be treated as the primary visual design reference for:

* Color palette
* Typography
* Spacing
* Layout
* Components
* Borders
* Shadows
* Responsive behavior
* Visual consistency

The AI may use either the Stitch HTML or image references depending on which provides the clearest implementation guidance.

---

# 3. Technology Rules

The project uses only the following core technologies:

* HTML5
* CSS3
* Vanilla JavaScript
* PHP
* MySQL
* Apache through XAMPP
* Chart.js where charts are required

## Strictly prohibited unless explicitly approved

Do not introduce:

* Bootstrap
* React
* Vue
* Angular
* Node.js backend
* Laravel
* Symfony
* Express
* Tailwind CSS
* PostgreSQL
* MongoDB
* Docker
* External backend services
* External authentication services

Do not introduce a framework simply because it is convenient.

The application must remain straightforward to run locally through XAMPP.

---

# 4. Design Rules

The Stitch design is the visual source of truth.

Before creating or modifying UI:

1. Inspect the relevant Stitch screen.
2. Inspect `DESIGN.md`.
3. Check whether an existing project component already implements the required pattern.
4. Reuse existing components where possible.
5. Maintain visual consistency.

Do not redesign screens arbitrarily.

Do not replace the Stitch visual language with generic Bootstrap-style interfaces.

Do not invent new colors when an appropriate design-system value already exists.

Do not create inconsistent buttons, cards, forms, tables, spacing, typography, or navigation patterns.

If the Stitch HTML conflicts with `DESIGN.md`, use judgment and prioritize the documented design system while preserving the intended appearance.

---

# 5. Agent Responsibilities

The primary agent is responsible for coordinating the project.

Sub-agents must have narrowly defined responsibilities.

A sub-agent must not perform unrelated tasks.

## Database Agent

Responsible only for:

* Database schema
* Tables
* Relationships
* Primary keys
* Foreign keys
* Indexes
* Constraints
* Seed data
* Database migrations/scripts where applicable
* Database integrity

The Database Agent must not redesign the UI.

---

## Backend Agent

Responsible only for:

* PHP backend logic
* Authentication logic
* Authorization
* Business rules
* CRUD operations
* Request workflows
* Inventory calculations
* Purchase workflows
* Payment workflows
* Server-side validation

The Backend Agent must not modify the design system unless explicitly requested.

---

## Frontend Agent

Responsible only for:

* HTML
* CSS
* Vanilla JavaScript
* UI components
* Responsive behavior
* Stitch design implementation
* Frontend validation
* Client-side interactions

The Frontend Agent must not modify database architecture.

---

## Authentication/Authorization Agent

Responsible only for:

* Login
* Logout
* Sessions
* Password hashing
* Role-based authorization
* Access restrictions
* Session security
* Unauthorized-access handling

---

## Testing Agent

Responsible only for:

* Functional testing
* Regression testing
* Role-permission testing
* Form validation testing
* Workflow testing
* Database integrity testing
* UI consistency checks
* Identifying bugs

The Testing Agent must not silently fix unrelated issues.

---

## Documentation Agent

Responsible only for:

* Updating project documentation
* Updating PLAN.md
* Updating architecture documentation when architecture actually changes
* Recording completed work
* Recording known limitations
* Maintaining development state

---

# 6. Primary Agent Rules

The primary agent must:

1. Understand the current project state before making changes.
2. Read `PROJECT.md`.
3. Read `ARCHITECTURE.md`.
4. Read `PLAN.md`.
5. Read relevant sections of `AGENTS.md`.
6. Inspect the Stitch design when working on UI.
7. Break large tasks into smaller tasks.
8. Assign specialized tasks to appropriate sub-agents.
9. Review sub-agent output before accepting it.
10. Run tests after meaningful changes.
11. Update project documentation after completing milestones.

The primary agent must not blindly trust sub-agent output.

---

# 7. Task Execution Rules

Before starting a task:

* Identify the exact objective.
* Identify affected files.
* Identify affected database tables.
* Identify dependencies.
* Check the current PLAN.md state.
* Check existing implementation before creating new code.

After completing a task:

* Test the implementation.
* Check for regressions.
* Verify consistency with the Stitch design.
* Update PLAN.md.
* Update other documentation only if necessary.

---

# 8. File Modification Rules

Do not create duplicate files when an existing file can be reused.

Do not duplicate business logic across multiple PHP files.

Prefer reusable components.

Examples:

* Shared navigation should be reusable.
* Shared authentication should be reusable.
* Shared database connection should be reusable.
* Shared validation should be reusable.
* Shared UI components should be reusable.

Avoid unnecessary global variables.

Avoid hardcoded database credentials throughout the application.

---

# 9. Security Rules

All user input must be treated as untrusted.

Use:

* Prepared SQL statements
* Password hashing
* Session-based authentication
* Server-side authorization
* Input validation
* Output escaping
* CSRF protection where appropriate
* Secure session handling

Never store plaintext passwords.

Never trust role information submitted by the browser.

Never authorize an operation based only on frontend controls.

Authorization must be enforced on the server.

---

# 10. Database Rules

Never modify database structure casually.

Before changing schema:

1. Identify the reason.
2. Check existing dependencies.
3. Update the database documentation if required.
4. Update affected backend code.
5. Test existing workflows.

Avoid deleting production-like data during development unless explicitly requested.

Use foreign keys and constraints where appropriate.

---

# 11. Inventory Rules

Inventory quantities must never be changed arbitrarily.

Inventory changes must originate from legitimate operations such as:

* Stock receiving
* Stock issuing
* Stock adjustment
* Stock return

Every stock-changing operation should have an auditable stock movement record.

The system must preserve traceability.

---

# 12. Role Rules

The system contains five roles:

* Admin
* Manager
* Employee
* Store
* Finance

Role permissions must be enforced server-side.

A user must only have access to functions appropriate to their role.

Hiding a menu item is not sufficient authorization.

---

# 13. Business Workflow Rules

The core employee request workflow is:

Employee submits request
→ Manager reviews
→ Manager approves/rejects
→ Store processes approved request
→ Store issues stock
→ Inventory decreases
→ Request becomes completed

The purchasing workflow is:

Low stock / purchase requirement
→ Purchase request/order
→ Manager approval where required
→ Finance processes payment
→ Store receives stock
→ Inventory increases

Do not bypass these workflows without an explicit requirement.

---

# 14. Error Handling

Errors must be handled gracefully.

Never expose:

* Database passwords
* SQL statements
* Internal server paths
* Sensitive configuration
* Stack traces to normal users

Use clear user-facing messages.

Log useful technical information during development where appropriate.

---

# 15. No Unnecessary Complexity

The AI must prefer the simplest solution that satisfies the requirements.

Do not introduce additional architecture merely to appear sophisticated.

This is a local PHP/MySQL application.

Maintainability and reliability are more important than architectural complexity.

---

# 16. Documentation State Rules

`PLAN.md` is the living project-state document.

Whenever a meaningful task is completed:

* Mark it completed.
* Record what changed.
* Record relevant files.
* Record tests performed.
* Record remaining work.

`ARCHITECTURE.md` must only be changed when the actual architecture or technology stack changes.

`PROJECT.md` should remain relatively stable.

`AGENTS.md` should only change when development rules or agent responsibilities change.

---

# 17. Definition of Done

A task is not considered complete merely because code was generated.

A task is complete only when:

* Implementation exists.
* Code is integrated.
* Required database changes exist.
* Relevant permissions are implemented.
* Basic validation exists.
* Relevant tests have been performed.
* No known blocking regression exists.
* PLAN.md is updated.

---

# 18. General Principle

Build the system as if another developer will maintain it after the AI is gone.

Prefer:

* Clear code
* Small functions
* Reusable components
* Explicit business rules
* Secure database access
* Consistent UI
* Documented decisions
* Testable functionality

Never optimize for generating the largest amount of code.

Optimize for producing a working, understandable, maintainable Inventory Management System.
