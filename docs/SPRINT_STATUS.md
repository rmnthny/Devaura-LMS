# UBNHS-LMS Sprint Status & Boundary Gate

> **AI EXECUTION GATE:** You may ONLY implement tasks listed under **`CURRENT SPRINT`** below. Never build features listed under **`LOCKED / FUTURE SPRINTS`**. When all tasks in `CURRENT SPRINT` are finished and verified, output a **Sprint Handoff Summary** and **STOP**.

---

## COMPLETED SPRINTS

### Sprint 0 — Foundation & Offline Environment Setup
- [x] **Task 0.1:** Scaffold Laravel 13 project (`Devaura-LMS`) and install required packages (`spatie/laravel-permission`, `spatie/laravel-activitylog`, `simplesoftwareio/simple-qrcode`, `barryvdh/laravel-dompdf`, `maatwebsite/excel`).
- [x] **Task 0.2:** Publish Spatie Permission & Activitylog migrations/config, create `public/storage` symlink, configure `.env` & `.env.example` for MySQL (`devaura_lms`), and verify base migrations.
- [x] **Task 0.3:** Download 100% local offline frontend assets (`bootstrap.min.css`, `bootstrap.bundle.min.js`, `bootstrap-icons.min.css` + fonts, `html5-qrcode.min.js`) into `public/vendor/`.
- [x] **Task 0.4:** Establish Cross-AI Instruction Loaders (`GEMINI.md`, `CLAUDE.md`, `.github/copilot-instructions.md`), Antigravity rules/workflows (`.agent/`), and `docs/` architecture baselines.

---

## CURRENT SPRINT

### Sprint 1 — Core Schema, Auth/RBAC & Member/Book QR Generation
- [ ] **Task 1.1: Migrations & Eloquent Models**
  - Update `users` migration to include `status` (`enum('active', 'inactive')`, default `'active'`).
  - Create migrations and Eloquent models for `members`, `books`, `attendance_logs`, `borrow_transactions`, `inventory_audits`, and `inventory_audit_items` strictly matching `docs/SCHEMA.md`.
  - Configure model relationships, fillable attributes, casts, `HasRoles` (`User`), and `LogsActivity` (`spatie/laravel-activitylog`).
- [ ] **Task 1.2: Authentication, RBAC Middleware & Database Seeders**
  - Implement session authentication (Login/Logout) with active status enforcement (`status === 'active'`) and offline Bootstrap 5 layout (`public/vendor/`).
  - Create role seeders for exact Spatie role slugs: `head_librarian`, `library_staff`, `student_staff`.
  - Seed default UBNHS accounts for each role and realistic sample UBNHS members (students Grade 7–12 + faculty) and cataloged books.
- [ ] **Task 1.3: Member CRUD + QR Code PNG Generation**
  - Build Member management controllers, Form Requests, and offline Blade views (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy` restricted by role).
  - Auto-generate unique `member_code` (e.g., `UBNHS-2026-0001`) and render local QR code PNG via `simplesoftwareio/simple-qrcode` saved to `storage/app/public/qrcodes/members/` (`qr_code_path`), plus printable Member QR ID card view.
- [ ] **Task 1.4: Book CRUD + QR Code PNG Generation**
  - Build Book catalog controllers, Form Requests, and offline Blade views (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy` restricted by role).
  - Auto-generate or validate unique `accession_number` (e.g., `ACC-00001`) and render local QR code PNG via `simplesoftwareio/simple-qrcode` saved to `storage/app/public/qrcodes/books/` (`qr_code_path`), plus printable Book QR label view.

---

## LOCKED / FUTURE SPRINTS (DO NOT IMPLEMENT YET)

### Sprint 2 — Public Kiosk (`/kiosk`) QR Attendance & Public Book Search
- [ ] **Task 2.1:** Unauthenticated `/kiosk` interface using offline Bootstrap 5 and `html5-qrcode.min.js` (webcam + USB scanner support).
- [ ] **Task 2.2:** Rapid QR attendance logging (`time_in` / `time_out` toggle per `member_id` on `log_date`) with instant visual confirmation.
- [ ] **Task 2.3:** Public Kiosk book catalog & availability search (`available`, `borrowed`, `shelf_location`) and Staff Attendance Log viewer.

### Sprint 3 — Borrowing/Returning Circulation & Operational Dashboard
- [ ] **Task 3.1:** QR-assisted Borrow (Checkout) workflow (`TRX-YYYYMMDD-XXXX`, due date assignment, book status -> `'borrowed'`).
- [ ] **Task 3.2:** QR-assisted Return (Check-in) workflow (`returned_at`, overdue status detection without fines/payments, book status -> `'available'` / `'damaged'`).
- [ ] **Task 3.3:** Role-aware Staff Dashboard displaying real-time KPIs (today's attendance, active borrows, overdue books, catalog summary) and Staff/User management for `head_librarian`.

### Sprint 4 — Digital Inventory Auditing & Automated PDF/Excel Reports
- [ ] **Task 4.1:** Inventory Audit session workflow (`inventory_audits` & `inventory_audit_items`) with QR scan verification (`verified`, `missing`, `misplaced`).
- [ ] **Task 4.2:** Automated PDF (`barryvdh/laravel-dompdf`) and Excel (`maatwebsite/excel`) exports for Attendance, Circulation/Overdue, and Inventory Audit reports.
- [ ] **Task 4.3:** Activity Log viewer (`spatie/laravel-activitylog`) for `head_librarian` and final December 1 defense readiness verification.
