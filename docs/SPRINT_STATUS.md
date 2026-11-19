# Sprint Status

Only work on tasks under **Current Sprint**. Do not build anything under **Upcoming Sprints** yet. Each sprint follows this Agile loop (tracked in **Notion**: *Not Started → In Progress → Postponed / Done*):
**Build → `php artisan test` + Postman API Check → Kristine's QA Review → Apply QA Fixes → Merge to `main`**.

---

## Completed

### Sprint 0 — Project Setup
- [x] **0.1:** Set up Laravel 13 (`Devaura-LMS`) and install `spatie/laravel-permission`, `spatie/laravel-activitylog`, `simplesoftwareio/simple-qrcode`, `barryvdh/laravel-dompdf`, and `maatwebsite/excel`.
- [x] **0.2:** Publish Spatie migrations/config, link `public/storage`, configure `.env` for MySQL (`devaura_lms`), and run base migrations.
- [x] **0.3:** Add offline frontend assets (`bootstrap.min.css`, `bootstrap.bundle.min.js`, `bootstrap-icons.min.css`, `html5-qrcode.min.js`) to `public/vendor/`.
- [x] **0.4:** Add team docs (`docs/`), `AGENTS.md`, `GEMINI.md`, `CLAUDE.md`, and repo guidelines.

---

## Current Sprint

### Sprint 1 — Auth, Email OTP, 7-Role RBAC & Member Management
- [ ] **1.1 Normalized Database & Eloquent Models** *(DB: Kyle | Models: Anthony)*
  - Sprint 1 backend models and a PHPUnit-only schema contract cover `users`, `department`, `library_member`, `library_personnel`, and `email_otps`. Kyle's production migrations and schema review remain pending.
- [ ] **1.2 Auth & Email OTP** *(Backend: Anthony | UI: Shaina)*
  - Login/logout, active account check, **3/min login rate limiting** (1-min cooldown), and **`PreventBackHistory` middleware** on authenticated routes.
  - Public sign-up limited to `student` (strict 12-digit LRN) and `faculty` (strict 7-digit DepEd Employee Number + subject Department) with 6-digit Email OTP via Gmail SMTP (auto-generating `UBNHS-YYYY-XXXX` & Member QR via `milon/barcode`).
- [ ] **1.3 7-Role Predefined RBAC & Superadmin User Management** *(Backend: Anthony | UI: Shaina)*
  - Configured 7 predefined Spatie roles (`superadmin`, `admin`, `staff`, `student`, `faculty`, `visitor`, `maintenance`) aligned with the Use Case Diagrams + role-gated Dashboard module cards.
  - Superadmin-only **User Roles** (`/admin/users`) with strict password confirmation (Faculty → `admin`, Student → `staff`, `maintenance`) and **Single-Superadmin Transfer** (reverting old superadmin to `faculty`), plus self-service **Update Personal Info** (`/profile`).
- [ ] **1.4 Member CRUD, Archiving & QR Codes** *(Backend: Anthony | UI: Shaina)*
  - Member CRUD (with duplicate LRN/DepEd Employee Number rejection), auto-generated `member_code` (`UBNHS-YYYY-XXXX`), `milon/barcode` QR PNGs/SVGs, 6-month Archive Bin + Restore, QR JSON endpoints, **Laravel Factories**, and **34 PHPUnit tests**.
- [ ] **1.5 QA Review & Fixes** *(QA: Kristine + Team)*
  - The current automated suite passes 34/34 tests; Postman verification and Kristine's sign-off remain pending before merging to `main`.

---

## Upcoming Sprints (Locked)

### Sprint 2 — Book Inventory, Archiving, Audits & Bulk Import
- [ ] **2.1:** Build book CRUD with stock counts and availability status (`available`, `borrowed`, `missing`, `damaged`).
- [ ] **2.2:** Auto-generate `accession_number`, Book QR PNGs, and printable Book QR labels.
- [ ] **2.3:** Build the Archive Bin (soft-delete and restore) for books and members.
- [ ] **2.4:** Build Inventory Audit sessions with QR scan checks (`verified`, `missing`, `misplaced`) + Sprint 2 tests.
- [ ] **2.5:** Add batched (**100-record**) CSV/Excel bulk user import via `maatwebsite/excel` (restricted to `superadmin`) with automated email error handling.

### Sprint 3 — Public Kiosk & Attendance Monitoring
- [ ] **3.1:** Build the public `/kiosk` page (`kiosk.blade.php`) with webcam/USB QR scanning (`html5-qrcode.min.js`) for quick `time_in` / `time_out`.
- [ ] **3.2:** Add manual attendance entry for all user types (Head Librarian, Assigned Teacher, Student Staff, Students, Faculty, and Visitors with **Full Name, Organization, and Email**).
- [ ] **3.3:** Build staff attendance monitoring and filter views + Sprint 3 tests.

### Sprint 4 — Book Circulation & Notifications
- [ ] **4.1:** Build QR-assisted borrow and return flows (`TRX-YYYYMMDD-XXXX`) recording `borrower_id` and `processed_by` (`users.id`) for accountability, updating book status in real time.
- [ ] **4.2:** Flag overdue books and block new borrows for members with overdue items (strictly no fines or payments).
- [ ] **4.3:** Send automated notifications for account updates, pending items, and due/overdue alerts + Sprint 4 tests.

### Sprint 5 — Dashboard, Reports, Audit Logs & Defense Polish
- [ ] **5.1:** Build the analytical dashboard with KPIs, attendance trends, and circulation charts.
- [ ] **5.2:** Export formal PDF (`barryvdh/laravel-dompdf`) and Excel (`maatwebsite/excel`) reports for Attendance, Circulation, and Inventory.
- [ ] **5.3:** Build the chronological Audit Trail viewer (`spatie/laravel-activitylog`) for `superadmin` + Sprint 5 tests, **mobile responsiveness check** (Chrome device emulator across grids/cards/inputs), and Dec 1 defense check.
