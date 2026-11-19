# Project Rules — UBNHS-LMS (`Devaura-LMS`)

## 1. Project Overview & Team Ownership
- **System:** QR Code-Integrated Library Management System for Upper Bicutan National High School (UBNHS-LMS)
- **Team (Group 1 - Devaura, PUP Taguig):**
  - **Anthony (Ramon Anthony V. Dela Fuente)** — Backend Developer (`app/`, `routes/`, `tests/` — Auth, OTP, Spatie RBAC, Controllers, Services, JSON/API endpoints, PHPUnit & Postman testing)
  - **Shaina Cuevas** — Frontend Developer (`resources/views/`, Bootstrap 5 + Tailwind CSS, mobile-responsive grids/cards/forms)
  - **Kyle E. Mata** — Document Analyst / Database Developer (Normalized MySQL 8.4+ schema `devaura_lms` & migrations)
  - **Kristine P. Salazar** — Project Manager / QA (Notion sprint board, UI/UX & QA review: RBAC, CSRF, XSS, SQLi, responsiveness)
- **Current Target:** Localhost prototype on Laragon 6.0.0 (`http://localhost:8000`) for December 1, 2026 defense.
- **Next Phase:** Cloud deployment on Hostinger (restricting admin functions to the school network while allowing external student access).

## 2. Tech Stack
- **Backend:** PHP 8.3+, Laravel 13 (MVC, Eloquent ORM, Form Requests, Middleware, Services)
- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Laravel Blade, Bootstrap 5 + Tailwind CSS (local assets in `public/vendor/` — **no external CDNs**)
- **Database:** MySQL 8.4+ (`devaura_lms`) — normalized tables separating credentials (`user_account`/`users`), students & faculty (`library_member`/`members`), personnel (`library_personnel` + `department` + schedules), and walk-in visitors (`library_visitor`)
- **Packages:** `spatie/laravel-permission`, `spatie/laravel-activitylog`, `simplesoftwareio/simple-qrcode`, `html5-qrcode`, `barryvdh/laravel-dompdf`, `maatwebsite/excel` (PhpSpreadsheet)
- **Project Management & Testing:** Notion (sprint tracking), Postman (API/HTTP endpoint testing), PHPUnit (`php artisan test`)

## 3. Roles, Permissions & Security Standards
We use **7 roles** in `spatie/laravel-permission` with **granular, department-aware permissions** enforced in controllers, route middleware, and Blade `@can` directives:
1. `superadmin` (Head Librarian) — Full access. Creates/manages `admin`, `staff`, and `maintenance` accounts; manages role permissions; runs batched CSV/Excel bulk imports; views audit trail and archives.
2. `admin` (Assigned Teacher) — Manages books, members, circulation, attendance, inventory audits, staff schedules, and operational reports.
3. `staff` (Student Staff) — Handles attendance monitoring, borrowing/returning, and catalog lookup. Cannot delete/archive master records or change roles/permissions.
4. `student` — Library member. Public sign-up/login with 6-digit Email OTP, catalog search, borrowing history, personal QR code, and self-profile update.
5. `faculty` — Library member. Same public sign-up/login with Email OTP and member access.
6. `visitor` — Walk-in guest. Logs attendance at `/kiosk` or manual entry with **Full Name, Organization, and Email**.
7. `maintenance` — System/facility maintenance role managed exclusively by `superadmin`.

### Non-Negotiable Security & Data Rules
- **Registration Rule:** Public sign-up applies only to `student` and `faculty` (with Email OTP verification). Only `superadmin` can create or manage `admin`, `staff`, and `maintenance` accounts.
- **Brute-Force & Session Protection:** Enforce **5 login attempts per minute** (1-minute cooldown), active account checks, `PreventBackHistory` middleware on authenticated routes, and database sessions.
- **Web Security:** Always protect against **CSRF** (`@csrf` / token checks), **SQL Injection** (Eloquent/parameterized queries only — never raw concatenated SQL), and **XSS** (Blade `{{ }}` escaping).
- **Soft-Delete Archiving & Audit Trail:** Never hard-delete master records (`users`, `members`, `books`); use `SoftDeletes` and log critical actions via `spatie/laravel-activitylog` (tracking actor, target, and `processed_by` on borrow transactions).
- **Bulk User Import:** Restricted to `superadmin` via CSV/Excel (`maatwebsite/excel`), processed in **100-record batches** with automated email error handling.
- **Public Kiosk (`/kiosk`):** Open terminal for quick QR attendance (`time_in` / `time_out`) and book availability search.

## 4. Out of Scope (Do Not Build for Dec 1 Prototype)
- **No Fines or Payments:** Track overdue items by status and date only.
- **No Digital Media Hosting:** Physical books only — no e-books, PDFs, or streaming.
- **No Alternate Hardware:** Webcam or USB QR scanners only — no RFID, biometrics, or 1D barcodes.
- **No Reservations:** No seat booking or advance book reservations in the Dec 1 prototype.
- **No External School APIs:** Standalone database — no DepEd LIS, enrollment, or grading integrations.
- **No Embedded AI:** Keep AI out of the Laravel app itself.

## 5. Git, Boundaries & Testing Workflow
- **Respect Role Boundaries:** Do not overwrite Kyle's database schema/migrations or Shaina's Blade UI unless explicitly requested. Keep backend controllers/services modular so frontend and DB integrate cleanly.
- **Branching:** Keep `main` clean. Build each task on a short-lived branch (e.g., `feat/anthony-sprint-1-rbac`) and merge to `main` after QA.
- **Secrets:** Never commit `.env`, database passwords, mail/SMTP credentials, or private keys — keep all sensitive files in `.gitignore`.
- **Testing Gate:** Every sprint task must pass `php artisan test` (RBAC, core logic, edge cases) and **Postman** endpoint verification (`GET`, `POST`, `PUT/PATCH`, `DELETE`, status codes 200/201/403/422) before Kristine's QA sign-off.
