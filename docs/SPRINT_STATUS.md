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

### Sprint 1 — Auth, Email OTP, 7-Role RBAC & Member Management
- [ ] **Task 1.1: Database & Eloquent Models** *(DB: Kyle | Backend Models: Anthony)*
  - Finalize MySQL schema (`devaura_lms`) and Eloquent models with relationships and `SoftDeletes` for archiving (`users`, `members`, `books`, `attendance_logs`, `borrow_transactions`, `inventory_audits`, `inventory_audit_items`, `email_otps`, `notifications`).
- [ ] **Task 1.2: Authentication & Email OTP Verification** *(Backend: Anthony | Frontend: Shaina)*
  - Implement session Login/Logout with active status enforcement.
  - Restrict public Sign Up / Log In strictly to `student` and `faculty` with Email OTP verification.
- [ ] **Task 1.3: 7-Role RBAC & Superadmin User Management** *(Backend: Anthony | Frontend: Shaina)*
  - Configure Spatie roles: `superadmin` (Head Librarian), `admin` (Assigned Teacher), `staff` (Student Staff), `student`, `faculty`, `visitor`, `maintenance`.
  - Enforce that `admin`, `staff`, and `maintenance` accounts are created/managed exclusively by `superadmin`.
- [ ] **Task 1.4: Member CRUD, Archiving & QR Generation** *(Backend: Anthony | Frontend: Shaina)*
  - Build Member CRUD, auto-generated `member_code` & QR PNG (`simple-qrcode`), soft-delete archiving, printable Member QR ID cards, and Sprint 1 feature tests.

---

## LOCKED / FUTURE SPRINTS (DO NOT IMPLEMENT YET)

### Sprint 2 — Book Inventory Management, Archiving & QR Integration
- [ ] **Task 2.1:** Book CRUD + stock quantities & real-time availability status (`available`, `borrowed`, `missing`, `damaged`).
- [ ] **Task 2.2:** Auto-generated `accession_number` & Book QR PNG generation + printable Book QR labels.
- [ ] **Task 2.3:** Archive Bin (soft-delete & restore) for books and members.
- [ ] **Task 2.4:** Inventory Audit session workflow & QR scan verification (`verified`, `missing`, `misplaced`) + Sprint 2 feature tests.

### Sprint 3 — Public Kiosk & Attendance Monitoring (QR + Manual)
- [ ] **Task 3.1:** Unauthenticated `/kiosk` terminal (`kiosk.blade.php`) with webcam/USB QR scanner (`html5-qrcode.min.js`) for rapid `time_in` / `time_out`.
- [ ] **Task 3.2:** Manual attendance entry for all user types (Head Librarian, Assigned Teacher, Student Staff, Students, Faculty, Visitors).
- [ ] **Task 3.3:** Staff Attendance Monitoring & filtering views + Sprint 3 feature tests.

### Sprint 4 — Book Circulation (QR Borrow/Return) & Notifications
- [ ] **Task 4.1:** QR-assisted Borrow (checkout) & Return (check-in) workflows (`TRX-YYYYMMDD-XXXX`) with real-time book availability updates.
- [ ] **Task 4.2:** Overdue detection & borrow-blocking for members with overdue books (strictly zero fines/payments).
- [ ] **Task 4.3:** Automated Notifications for account updates, pending items, and due/overdue alerts + Sprint 4 feature tests.

### Sprint 5 — Analytical Dashboard, Formal Reports & Audit Logs
- [ ] **Task 5.1:** Interactive Analytical Dashboard with KPIs and charts (attendance trends, circulation statistics).
- [ ] **Task 5.2:** Formal PDF (`barryvdh/laravel-dompdf`) and Excel (`maatwebsite/excel`) exports for Attendance, Circulation, and Inventory.
- [ ] **Task 5.3:** Chronological Audit Log viewer (`spatie/laravel-activitylog`) for `superadmin` + Sprint 5 feature tests & Dec 1 defense readiness check.
