# UBNHS-LMS Functional Requirements Traceability Matrix (R1–R5)

This document maps Upper Bicutan National High School (UBNHS) operational requirements (`R1`–`R5`) to database tables, Laravel modules, and sprint deliverables.

---

## R1: Centralized Database Management
- **Operational Goal:** Replace fragmented paper logbooks and standalone spreadsheets with a unified, role-secured relational database for staff, library members (students and faculty), books, attendance, circulation, and inventory audits.
- **Mapped Schema Tables:** `users`, `members`, `books`, `attendance_logs`, `borrow_transactions`, `inventory_audits`, `inventory_audit_items`, `roles`, `activity_log`.
- **Key Features:**
  - Role-Based Access Control (`head_librarian`, `library_staff`, `student_staff`) enforced via `spatie/laravel-permission`.
  - Master record management for UBNHS students (LRN, grade level 7–12, section) and faculty (Employee ID, department).
  - System-wide audit trail via `spatie/laravel-activitylog`.
- **Target Sprint:** Sprint 1 (Core Schema, Auth/RBAC & Member/Book CRUD).

---

## R2: Digital Cataloging & Circulation Management
- **Operational Goal:** Streamline book cataloging, availability lookup, borrowing (checkout), returning (check-in), and overdue tracking without manual borrower cards.
- **Mapped Schema Tables:** `books`, `members`, `borrow_transactions`.
- **Key Features:**
  - Physical copy cataloging by unique `accession_number` (`ACC-00001`), ISBN, title, author, publisher, publication year, category, and shelf location.
  - Real-time book status transitions (`available`, `borrowed`, `missing`, `damaged`).
  - Borrow/return circulation processing (`TRX-YYYYMMDD-XXXX`) linking `book_id`, `member_id`, and `processed_by` (`users.id`).
  - Automatic flagging of overdue transactions by `due_date` and `status` (`overdue`) — **strictly zero fines or payment processing**.
  - Public book availability lookup at `/kiosk`.
- **Target Sprints:** Sprint 1 (Book CRUD), Sprint 2 (Public Kiosk Search), Sprint 3 (Borrowing/Returning Circulation & Dashboard).

---

## R3: Digital Inventory & Book Auditing
- **Operational Goal:** Enable structured physical stocktaking of the library collection using QR scanning to rapidly identify verified, missing, or misplaced books.
- **Mapped Schema Tables:** `inventory_audits`, `inventory_audit_items`, `books`.
- **Key Features:**
  - Creation of `inventory_audits` sessions (`in_progress` -> `completed`) by authorized library staff.
  - Pre-population of `inventory_audit_items` for cataloged books with default `scanned_status = 'missing'`.
  - Rapid QR scan verification updating `scanned_status` to `'verified'` (or `'misplaced'`) and recording `scanned_at`.
  - Discrepancy summary comparing expected catalog holdings against scanned physical copies.
- **Target Sprint:** Sprint 4 (Inventory Auditing & PDF/Excel Reports).

---

## R4: Automated Report Generation
- **Operational Goal:** Generate accurate, printable, and exportable administrative reports for UBNHS library leadership and DepEd documentation.
- **Mapped Schema Tables:** `attendance_logs`, `borrow_transactions`, `books`, `members`, `inventory_audits`, `inventory_audit_items`.
- **Key Features:**
  - Exportable PDF reports (`barryvdh/laravel-dompdf`) and Excel spreadsheets (`maatwebsite/excel`).
  - Daily/monthly Library Attendance Reports (filtered by date range, member type, grade level).
  - Circulation & Overdue Book Reports (`borrowed`, `returned`, `overdue`).
  - Book Inventory & Audit Discrepancy Reports (`verified`, `missing`, `misplaced`, `damaged`).
- **Target Sprint:** Sprint 4 (Inventory Auditing & PDF/Excel Reports).

---

## R5: QR Code Integration (Generation & Scanning)
- **Operational Goal:** Eliminate manual typing errors during library entry/exit, book borrowing/returning, and physical inventory auditing through offline-capable QR code generation and scanning.
- **Mapped Schema Tables:** `members` (`member_code`, `qr_code_path`), `books` (`accession_number`, `qr_code_path`), `attendance_logs`, `borrow_transactions`, `inventory_audit_items`.
- **Key Features:**
  - Local server-side QR code PNG generation (`simplesoftwareio/simple-qrcode`) stored under `storage/app/public/` (`public/storage/`) for every registered member (`member_code`) and cataloged book (`accession_number`).
  - Printable QR ID cards/stickers for members and book spine/cover labels.
  - Browser-based webcam and USB scanner QR decoding using local `public/vendor/html5-qrcode/html5-qrcode.min.js` across:
    1. Public Kiosk (`/kiosk`) rapid entry/exit attendance (`time_in` / `time_out`).
    2. Staff Circulation desk instant member + book lookup.
    3. Inventory Audit rapid book verification.
- **Target Sprints:** Sprint 1 (Member/Book QR Generation), Sprint 2 (Kiosk QR Attendance), Sprint 3 (QR Circulation), Sprint 4 (QR Inventory Auditing).
