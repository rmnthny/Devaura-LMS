# Sprint Status

Only work on tasks under **Current Sprint**. Do not build anything under **Upcoming Sprints** yet. Each sprint follows this Agile loop: **Build → `php artisan test` → Kristine's QA Review → Apply QA Fixes → Merge to `main`**. Once all tasks and QA fixes pass, write a short handoff note and stop.

---

## Completed

### Sprint 0 — Project Setup
- [x] **0.1:** Set up Laravel 13 (`Devaura-LMS`) and install `spatie/laravel-permission`, `spatie/laravel-activitylog`, `simplesoftwareio/simple-qrcode`, `barryvdh/laravel-dompdf`, and `maatwebsite/excel`.
- [x] **0.2:** Publish Spatie migrations/config, link `public/storage`, configure `.env` for MySQL (`devaura_lms`), and run base migrations.
- [x] **0.3:** Add offline frontend assets (`bootstrap.min.css`, `bootstrap.bundle.min.js`, `bootstrap-icons.min.css`, `html5-qrcode.min.js`) to `public/vendor/`.
- [x] **0.4:** Add team docs (`docs/`) and repo guidelines.

---

## Current Sprint

### Sprint 1 — Auth, Email OTP, 7-Role RBAC & Member Management
- [ ] **1.1 Database & Eloquent Models** *(DB: Kyle | Models: Anthony)*
  - Finalize the MySQL schema (`devaura_lms`) and Eloquent models with soft-delete archiving (`users`, `members`, `books`, `attendance_logs`, `borrow_transactions`, `inventory_audits`, `inventory_audit_items`, `email_otps`, `notifications`).
- [ ] **1.2 Auth & Email OTP** *(Anthony + Shaina)*
  - Build login/logout and check that the account is active.
  - Limit public sign-up and login to `student` and `faculty` with Email OTP verification.
- [ ] **1.3 7-Role RBAC & Superadmin User Management** *(Anthony + Shaina)*
  - Set up roles: `superadmin` (Head Librarian), `admin` (Assigned Teacher), `staff` (Student Staff), `student`, `faculty`, `visitor`, and `maintenance`.
  - Restrict `admin`, `staff`, and `maintenance` account creation and management to `superadmin` only.
- [ ] **1.4 Member CRUD, Archiving & QR Codes** *(Anthony + Shaina)*
  - Build member CRUD, auto-generate `member_code` and QR PNGs (`simple-qrcode`), add soft-delete archiving, printable Member QR ID cards, and Sprint 1 feature tests.
- [ ] **1.5 QA Review & Fixes** *(Kristine + Team)*
  - Run Kristine's QA check, apply any fixes, and verify all Sprint 1 tests pass before merging to `main`.

---

## Upcoming Sprints (Locked)

### Sprint 2 — Book Inventory, Archiving & QR Codes
- [ ] **2.1:** Build book CRUD with stock counts and availability status (`available`, `borrowed`, `missing`, `damaged`).
- [ ] **2.2:** Auto-generate `accession_number`, Book QR PNGs, and printable Book QR labels.
- [ ] **2.3:** Build the Archive Bin (soft-delete and restore) for books and members.
- [ ] **2.4:** Build Inventory Audit sessions with QR scan checks (`verified`, `missing`, `misplaced`) and Sprint 2 tests.

### Sprint 3 — Public Kiosk & Attendance Monitoring
- [ ] **3.1:** Build the public `/kiosk` page (`kiosk.blade.php`) with webcam/USB QR scanning (`html5-qrcode.min.js`) for quick `time_in` / `time_out`.
- [ ] **3.2:** Add manual attendance entry for all user types (Head Librarian, Assigned Teacher, Student Staff, Students, Faculty, Visitors).
- [ ] **3.3:** Build staff attendance monitoring and filter views + Sprint 3 tests.

### Sprint 4 — Book Circulation & Notifications
- [ ] **4.1:** Build QR-assisted borrow and return flows (`TRX-YYYYMMDD-XXXX`) that update book status in real time.
- [ ] **4.2:** Flag overdue books and block new borrows for members with overdue items (no fines or payments).
- [ ] **4.3:** Send automated notifications for account updates, pending items, and due/overdue alerts + Sprint 4 tests.

### Sprint 5 — Dashboard, Reports & Audit Logs
- [ ] **5.1:** Build the analytical dashboard with KPIs, attendance trends, and circulation charts.
- [ ] **5.2:** Export formal PDF (`barryvdh/laravel-dompdf`) and Excel (`maatwebsite/excel`) reports for Attendance, Circulation, and Inventory.
- [ ] **5.3:** Build the chronological Audit Log viewer (`spatie/laravel-activitylog`) for `superadmin` + Sprint 5 tests and Dec 1 defense check.
