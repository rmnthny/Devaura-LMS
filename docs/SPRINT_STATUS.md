# Sprint Status

The active release covers **Sprints 1–4**. Sprint 5 source remains deferred outside the running application. Each sprint follows this Agile loop (tracked in **Notion**: *Not Started → In Progress → Postponed / Done*):
**Build → `php artisan test` + Apidog/Postman API Check → Kristine's QA Review → Apply QA Fixes → Merge to `main`**.

---

## Completed

### Sprint 0 — Project Setup
- [x] **0.1:** Set up Laravel 13 (`Devaura-LMS`) and install `spatie/laravel-permission`, `spatie/laravel-activitylog`, `milon/barcode`, `barryvdh/laravel-dompdf`, and `maatwebsite/excel`.
- [x] **0.2:** Publish Spatie migrations/config, link `public/storage`, configure `.env` for MySQL (`devaura_lms`), and run base migrations.
- [x] **0.3:** Add offline Bootstrap CSS and Bootstrap Icons to `public/vendor/`.
- [x] **0.4:** Add team documentation, the root `AGENTS.md` AI working agreement, and repository guidelines.

---

## Sprint 1 Baseline

### Sprint 1 — Auth, Email OTP, 7-Role RBAC & Member Management
- [x] **1.1 Auth & Email OTP** *(Backend: Anthony | UI: Shaina)*
  - Login/logout, active account check, **3/min login rate limiting** (1-min cooldown), and **`PreventBackHistory` middleware** on authenticated routes.
  - The Head Librarian creates Student and Faculty accounts through official imports. Existing pending accounts can still complete six-digit email OTP verification.
- [x] **1.2 7-Role Predefined RBAC & Superadmin User Management** *(Backend: Anthony | UI: Shaina)*
  - Predefined 7 Spatie roles (`superadmin`, `admin`, `staff`, `student`, `faculty`, `visitor`, `maintenance`) aligned with Use Case Diagrams.
  - Head Librarian role management and Admin assignment or revocation of the Student Staff role, with password confirmation and **Single-Superadmin Transfer** (reverting old superadmin to `faculty`), plus self-service **Update Personal Info**.
- [x] **1.3 Member CRUD, Archiving & QR Codes** *(Backend: Anthony | UI: Shaina)*
  - Member CRUD (with duplicate `reference_number` rejection), `milon/barcode` QR PNG/SVG generation, 6-month Archive Bin + Restore, and **PHPUnit + Apidog/Postman** tests.
- [ ] **1.4 QA Review & Fixes** *(QA: Kristine + Team)*
  - Verify CSRF, XSS, SQLi, RBAC, rate limiting, `php artisan test`, and Apidog/Postman endpoints; merge to `main`.

---

## Sprint 2 implementation

### Sprint 2 — Book Inventory, Archiving, Audits & Personnel Schedules
- [x] **2.1 Catalog:** Manage books, authors, genres, and publishers. Track copies and each copy's status.
- [x] **2.2 QR labels:** Generate a unique QR per physical copy and print individual labels or selected batches.
- [x] **2.3 Archive:** Restore books, members, personnel, and accounts. Preserve linked history during cleanup. Permanent removal of eligible archived members requires Head Librarian confirmation.
- [x] **2.4 Audit and schedules:** The Head Librarian records individual inventory checks by entering a book code. Admins can assign and revoke the Staff role for Students and manage Staff schedules.
- [x] **Bulk import:** Accept CSV and Excel files for members and books. Queue larger imports and send welcome emails through a separate worker.
- [ ] **2.5 Final gate:** Confirm hosted CI, verify endpoints in Postman or Apidog, get Kristine's QA sign-off, and record the handoff before merging.

## Sprint 3 closeout

### Sprint 3 — Public Kiosk & Attendance Monitoring
- [x] **3.1:** Public kiosk with manual QR value or ID entry. It opens while the Head Librarian is timed in today or while assigned personnel are timed in during their shift. Camera scanning is deferred.
- [x] **3.2:** Protected staff attendance and assisted entry. Walk-in purposes are stored per visit.
- [x] **3.3:** Attendance monitoring, date and status filters, and audited Force Time Out for Superadmin and Admin.
- [x] **3.4:** Separate public kiosk origin with isolated kiosk session, staff-route blocking, safe redirects, no staff navigation, and a staff availability check before public attendance.
- [ ] **3.5:** QA Review & Fixes *(QA: Kristine + Team)*, endpoint verification, final handoff, and merge to `main`.

Kiosk coverage uses Manila time. The initial defaults are 08:00–12:00 for Morning,
12:00–17:00 for Afternoon, and 08:00–17:00 for Whole Day. Set
`LIBRARY_OPENS_AT`, `LIBRARY_AFTERNOON_AT`, and `LIBRARY_CLOSES_AT` when official hours are confirmed.
Local QA passed with 10 focused tests, 79 total tests, and HTTP checks on both origins.
The dedicated kiosk device walkthrough and Kristine's QA decision are pending.
The visit-purpose migration is additive. Run it before opening the Sprint 3 screens.
See `docs/ACTIVE_HANDOFF.md` for the exact behavior, local addresses, last known tests, and remaining QA.

## Current Sprint

### Sprint 4 — Book Circulation & Notifications
- [x] **4.1:** Staff-assisted QR checkout and return with `TRX-YYYYMMDD-XXXX` references, borrower and processor accounts, coordinated copy updates, replay protection, and protected search and selection for members, available copies, and open loans.
- [x] **4.2:** Manila-date overdue tracking and a new-borrow restriction until overdue loans are resolved. Staff can still process returns.
- [x] **4.3:** In-app and queued email notifications for account updates, pending items, and due and overdue loans. The November 27 full suite passed 94 tests and 1,051 assertions. Live Gmail verification passed for the redesigned checkout notice.
- [ ] **4.4:** Team QA, Postman or Apidog verification, and sign-off before merge.

Sprint 4 is local on `feat/sprint-4-backend`, based on `f946aee`. See the [Sprint 4 handoff](handoffs/SPRINT_4_HANDOFF.md) for exact results and remaining QA. The [diagram crosswalk](DIAGRAMS.md) records alignment and decisions for team review.

## Upcoming Sprints

### Sprint 5 — Analytical Dashboard, Formal Reports, Audit Logs & Defense Polish
- [ ] **5.1:** Role-scoped dashboards with operational KPIs and Manila-date attendance and checkout trends, plus private member transaction history.
- [ ] **5.2:** Date-filtered, printable PDF and Excel reports for attendance, circulation, and inventory checks.
- [ ] **5.3:** Chronological Audit Trail for Head Librarian and Admin. Inventory sessions and approval are Head Librarian-only. The damaged-return flow supports repair scheduling and completion.
- [ ] **5.4:** Team QA, real-device/browser walkthrough, and defense readiness sign-off.

Deferred source and prior QA evidence are recorded in the [Sprint 5 handoff](handoffs/SPRINT_5_HANDOFF.md). The [diagram crosswalk](DIAGRAMS.md) identifies the active release boundary.
