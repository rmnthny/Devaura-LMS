# Project Rules — UBNHS-LMS (`Devaura-LMS`)

## 1. Project Overview
- **System:** QR Code-Integrated Library Management System for Upper Bicutan National High School (UBNHS-LMS)
- **Team (Group 1 - Devaura, PUP Taguig):**
  - **Anthony (Ramon Anthony V. Dela Fuente)** — Backend Developer
  - **Shaina Cuevas** — Frontend Developer
  - **Kyle E. Mata** — Document Analyst / Database & Backend Developer
  - **Kristine P. Salazar** — Project Manager / QA
- **Current Target:** Localhost prototype on Laragon 6.0.0 (`http://localhost:8000`) for December 1, 2026 defense.
- **Next Phase:** Cloud deployment on Hostinger.

## 2. Tech Stack
- **Backend:** PHP 8.3+, Laravel 13 (MVC, Eloquent ORM, Form Requests, Middleware)
- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Laravel Blade, Bootstrap 5 + Tailwind CSS (local assets in `public/vendor/` — no external CDNs)
- **Database:** MySQL 8.4+ (`devaura_lms`)
- **Packages:** `spatie/laravel-permission`, `spatie/laravel-activitylog`, `simplesoftwareio/simple-qrcode`, `html5-qrcode`, `barryvdh/laravel-dompdf`, `maatwebsite/excel`

## 3. Roles & Access Control
We use 7 roles in `spatie/laravel-permission`:
1. `superadmin` (Head Librarian) — Full access. Creates and manages `admin`, `staff`, and `maintenance` accounts, plus books, members, circulation, attendance, audits, reports, audit logs, and archiving.
2. `admin` (Assigned Teacher) — Manages books, members, circulation, attendance, inventory audits, and reports.
3. `staff` (Student Staff) — Handles attendance monitoring, borrowing/returning, and basic catalog lookup. Cannot delete/archive master records or change roles.
4. `student` — Library member. Can sign up/log in publicly with Email OTP, view the catalog, check borrowing history, and view their personal QR code.
5. `faculty` — Library member. Same public sign-up/log-in with Email OTP and member access.
6. `visitor` — Walk-in guest. Logs attendance at `/kiosk` or via manual entry.
7. `maintenance` — System/facility maintenance role managed only by `superadmin`.

- **Registration Rule:** Public sign-up and login apply only to `student` and `faculty` (with Email OTP). Only `superadmin` can create or manage `admin`, `staff`, and `maintenance` accounts.
- **Public Kiosk (`/kiosk`):** Open terminal for quick QR attendance (`time_in` / `time_out`) and book availability search.

## 4. Out of Scope (Do Not Build)
- **No Fines or Payments:** Track overdue items by status and date only.
- **No Digital Media Hosting:** Physical books only—no e-books, PDFs, or streaming.
- **No Alternate Hardware:** Webcam or USB QR scanners only—no RFID, biometrics, or 1D barcodes.
- **No Reservations:** No seat booking or advance book reservations.
- **No External School APIs:** Standalone database—no DepEd LIS, enrollment, or grading integrations.
- **No Embedded AI:** Keep AI out of the Laravel app itself.

## 5. Git & Testing Workflow
- Keep `main` clean. Build each task on a short-lived branch (e.g., `feat/sprint-1-auth`) and merge to `main` after QA.
- Extend `resources/views/layouts/app.blade.php` for staff/member views and `resources/views/layouts/kiosk.blade.php` for `/kiosk`.
- Every sprint task needs `php artisan test` feature tests covering RBAC, core logic, and edge cases before closing the sprint.
