# UBNHS-LMS Functional Requirements Traceability Matrix (R1-R5)

This document maps the Upper Bicutan National High School operational requirements to the normalized database, Laravel modules, and the authoritative sprint order in `SPRINT_STATUS.md`.

## R1: Centralized Database Management

- **Operational Goal:** Replace paper logbooks and separate spreadsheets with one role-secured database for accounts, members, personnel, visitors, books, attendance, circulation, inventory audits, and system activity.
- **Mapped Tables:** `user_account`, `library_member`, `library_personnel`, `department`, `walk_in_visitor`, Spatie permission tables, and `activity_log`.
- **Required Roles:** `superadmin`, `admin`, `staff`, `student`, `faculty`, `visitor`, and `maintenance`.
- **Key Features:**
  - Separate authentication credentials from member, personnel, and visitor profiles.
  - Enforce fixed role permissions with `spatie/laravel-permission`.
  - Retain archived master records for six months before pruning.
  - Record critical actions with `spatie/laravel-activitylog`.
- **Target Sprints:** Sprint 1 for accounts, authentication, RBAC, members, and the database foundation; Sprint 5 for the audit-trail viewer.

## R2: Digital Cataloging and Circulation Management

- **Operational Goal:** Manage physical-book cataloging, availability, borrowing, returning, and overdue tracking without borrower cards.
- **Mapped Tables:** `book`, `genre`, `author`, `book_author`, `publisher`, `book_publisher`, and `borrow_transaction`.
- **Key Features:**
  - Catalog each physical book with a unique accession number/QR value, ISBN, title, author, publisher, publication date, genre, and availability state.
  - Support `available`, `borrowed`, `missing`, and `damaged` operational states.
  - Process borrow/return transactions using `TRX-YYYYMMDD-XXXX`, a borrower, the processing account, due dates, and real-time book-state changes.
  - Flag overdue transactions and block additional borrowing while an overdue item remains unresolved.
  - Provide public book-availability lookup in a later kiosk enhancement. Sprint 3 currently provides attendance only.
  - Do not implement fines, payments, or reservations.
- **Target Sprints:** Sprint 2 for cataloging and Book QR; Sprint 4 for circulation, overdue tracking, and notifications; Sprint 5 for dashboard metrics and reports. Public kiosk catalog lookup is deferred until scheduled.

## R3: Digital Inventory and Book Auditing

- **Operational Goal:** Conduct physical stocktaking with QR scanning and identify verified, missing, misplaced, or damaged books.
- **Planned Tables:** `inventory_audits` and `inventory_audit_items`, linked to `book` and the responsible `user_account`.
- **Key Features:**
  - Create an audit session with `in_progress` and `completed` states.
  - Prepopulate audit items from the active catalog.
  - Record QR scan results as `verified`, `missing`, or `misplaced`, with scan timestamps.
  - Present a discrepancy summary for review.
- **Target Sprints:** Sprint 2 for auditing workflows and tests; Sprint 5 for PDF/Excel inventory and discrepancy reports.

## R4: Automated Report Generation

- **Operational Goal:** Produce accurate administrative reports for library leadership and DepEd documentation.
- **Mapped Data:** `attendance_log`, `borrow_transaction`, `book`, `library_member`, `inventory_audits`, and `inventory_audit_items`.
- **Key Features:**
  - Export PDF reports with `barryvdh/laravel-dompdf`.
  - Export Excel reports with `maatwebsite/excel`.
  - Report attendance by date range, role/member type, and grade level where available.
  - Report borrowed, returned, and overdue circulation activity.
  - Report catalog status and inventory-audit discrepancies.
- **Target Sprint:** Sprint 5.

## R5: QR Code Integration

- **Operational Goal:** Reduce manual entry errors during attendance, circulation, and inventory operations using webcam or USB QR scanners.
- **Mapped Data:** account/member QR values, Book QR values, `attendance_log`, `borrow_transaction`, and `inventory_audit_items`.
- **Key Features:**
  - Generate member and Book QR images locally with `milon/barcode`.
  - Enter QR code values manually. Camera scanning is deferred.
  - Provide printable member cards and Book labels.
  - Accept QR values from manual entry or keyboard-style scanners. Camera scanning remains deferred.
- **Target Sprints:** Sprint 1 for Member QR; Sprint 2 for Book QR and inventory scanning; Sprint 3 for kiosk attendance; Sprint 4 for circulation scanning.

## Traceability Rule

If a sprint assignment here conflicts with `SPRINT_STATUS.md`, follow `SPRINT_STATUS.md` and update this matrix in the same pull request.
