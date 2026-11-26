# Project Rules — UBNHS-LMS (`Devaura-LMS`)

## 1. Project Overview & Team Ownership
- **System:** QR Code-Integrated Library Management System for Upper Bicutan National High School (UBNHS-LMS)
- **Team (Group 1 - Devaura, PUP Taguig):**
  - **Anthony (Ramon Anthony V. Dela Fuente)** — Backend Developer (`app/`, `routes/`, `tests/` — Auth, OTP, Spatie RBAC, Controllers, Services, JSON/API endpoints, PHPUnit & Postman testing)
  - **Shaina Cuevas** — Frontend Developer (`resources/views/`, Bootstrap 5 offline assets in `public/vendor/`, mobile-responsive grids/cards/forms)
  - **Kyle E. Mata** — Document Analyst / Database Developer (Normalized MySQL 8.4+ schema `devaura_lms` & migrations)
  - **Kristine P. Salazar** — Project Manager / QA (Notion sprint board, UI/UX & QA review: RBAC, CSRF, XSS, SQLi, responsiveness)
- **Current Target:** Localhost prototype on Laragon 6.0.0 (`http://localhost:8000`) for December 1, 2026 defense.
- **Legacy Reference Repo:** `https://github.com/rmnthny/library-management-system` (previous Information Management subject project, usable as functional/domain reference).
- **Next Phase:** Cloud deployment on Hostinger (restricting admin functions to the school network while allowing external student access).

## 2. Tech Stack
- **Backend:** PHP 8.4+, Laravel 13 (MVC, Eloquent ORM, Form Requests, Middleware, Services)
- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Laravel Blade, Bootstrap 5 (local assets in `public/vendor/` — **no external CDNs**)
- **Database:** MySQL 8.4+ (`devaura_lms`) — normalized tables separating credentials (`user_account`), students and faculty (`library_member`), personnel (`library_personnel`, `department`, and schedules), and walk-in visitors (`walk_in_visitor`)
- **Packages:** `spatie/laravel-permission`, `spatie/laravel-activitylog`, `milon/barcode` (QR generation), `barryvdh/laravel-dompdf`, `maatwebsite/excel` (PhpSpreadsheet), Laravel Mail / Symfony Mailer (Gmail SMTP for Email OTP)
- **Project Management & Testing:** Notion (sprint tracking), Postman (API/HTTP endpoint testing), PHPUnit (`php artisan test`)

## 3. Roles, Permissions & Security Standards
We use **7 predefined roles** in `spatie/laravel-permission` (fixed privileges per role aligned with the Use Case Diagrams) enforced in controllers, route middleware, and Blade `@can` directives:
1. `superadmin` (Head Librarian) — Single active superadmin (a Faculty teacher with a subject Department). Full access; promotes Faculty → `admin`, Student → `staff`, or `maintenance` (with strict password confirmation); can transfer `superadmin` to another Faculty member (reverting to `faculty`); runs batched CSV/Excel bulk imports; views audit trail and archives.
2. `admin` (Assigned Teacher — Faculty only) — Can add and edit books, edit members, conduct inventory audits, generate operational reports, assign or revoke the Staff role for Students with password confirmation, and manage Staff schedules.
3. `staff` (Student Staff — Student only) — Handles attendance monitoring, borrowing/returning, book inventory & QR, and catalog lookup. Cannot manage/archive members, delete books/personnel, view audit trail, or change roles.
4. `student` — Library member. An official import requires a **12-digit LRN**. Members sign in with email and can view their QR card and update their profile.
5. `faculty` — Library member. An official import requires a **7-digit DepEd employee number** and a **subject department**.
6. `visitor` — Walk-in guest with no account. Logs attendance at the kiosk or through assisted entry using **Last Name, First Name, optional Middle Initial, Email, and Purpose**.
7. `maintenance` — System/facility maintenance role assigned exclusively by `superadmin`.

### Non-Negotiable Security & Data Rules
- **Account setup:** The Head Librarian adds Student and Faculty accounts individually or by importing official records. Previously created pending accounts can still complete email OTP verification. The Head Librarian manages elevated roles. Admins can assign and revoke the Staff role for Students. Both changes require password confirmation.
- **Brute-Force & Session Protection:** Enforce **3 login attempts per minute** (1-minute cooldown), active account checks, `PreventBackHistory` middleware on authenticated routes, and database sessions.
- **Web Security:** Always protect against **CSRF** (`@csrf` / token checks), **SQL Injection** (Eloquent/parameterized queries only — never raw concatenated SQL), and **XSS** (Blade `{{ }}` escaping).
- **Archive and audit trail:** Only the Head Librarian can archive and restore records or open the Archive. A daily cleanup removes eligible records after six months. The Head Librarian may confirm permanent deletion for eligible archived members. Retain records linked to library history and log archive actions.
- **Bulk User Import:** Restricted to `superadmin` via CSV/Excel (`maatwebsite/excel`), processed in **100-record batches** with automated email error handling.
- **Public Kiosk:** Attendance-only terminal on a dedicated origin. It accepts a member reference number or QR value and visitor details. It opens only while eligible personnel coverage is active. Local default is `http://127.0.0.1:8001/kiosk`. Public catalog lookup and camera scanning are deferred.

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
