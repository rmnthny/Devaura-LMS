# UBNHS-LMS Master Project Rules & Guardrails

## 1. Project Identity & Environment
- **System Title:** Design, Development, and Evaluation of a QR Code-Integrated Library Management System for Upper Bicutan National High School (UBNHS-LMS)
- **Repository:** `Devaura-LMS` (Group 1 - Devaura: Cuevas, Shaina; Dela Fuente, Ramon Anthony V.; Mata, Kyle E.; Salazar, Kristine P. — PUP Taguig)
- **Current Phase:** Fundamentals of Research Defense (December 1, 2026) — Localhost Prototype (`http://localhost:8000` via Laragon + MySQL)
- **Next Phase:** Capstone Project — Cloud Deployment on Hostinger

## 2. Locked Technology Stack (Do Not Substitute)
- **Backend:** PHP 8.3+, Laravel 13 (MVC, Eloquent ORM, Form Requests, Middleware)
- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Laravel Blade, Bootstrap 5 + Tailwind CSS (stored locally in `public/vendor/` — zero external CDN dependency)
- **Database:** MySQL 8.4+ (`devaura_lms`)
- **Required Packages:** `spatie/laravel-permission`, `spatie/laravel-activitylog`, `simplesoftwareio/simple-qrcode`, `html5-qrcode`, `barryvdh/laravel-dompdf`, `maatwebsite/excel`

## 3. Role-Based Access Control (Exact Role Slugs)
7 user roles exist in `spatie/laravel-permission`:
1. `superadmin` (Head Librarian) — Full administrative access (creates/manages `admin`, `staff`, and `maintenance` accounts; full access to books, members, circulation, attendance, inventory audits, reports, audit logs, archiving).
2. `admin` (Assigned Teacher) — Operational management (books, members, circulation, attendance, inventory audits, reports).
3. `staff` (Student Staff) — Restricted operational assistance (attendance monitoring, borrowing/returning transactions, basic book catalog lookup; NO deletion/archiving of master records or role changes).
4. `student` — Library member (public sign up/log in with Email OTP; can view catalog, borrowing history, and personal QR).
5. `faculty` — Library member (public sign up/log in with Email OTP; can view catalog, borrowing history, and personal QR).
6. `visitor` — Walk-in guest (attendance logging at `/kiosk` or manual entry).
7. `maintenance` — System/facility maintenance role managed exclusively by `superadmin`.
- **Auth & Registration Rule:** Public Sign Up / Log In is restricted strictly to `student` and `faculty` (with Email OTP verification). `admin`, `staff`, and `maintenance` accounts are created and managed **only** by `superadmin`.
- **Public Kiosk (Unauthenticated Route `/kiosk`):** Dedicated library terminal interface for rapid QR code entry/exit attendance logging and public book availability search.

## 4. Strict Out-of-Scope Exclusions (NEVER CODE THESE)
- **NO Fines or Payments:** Overdue items are flagged by status/date only; no fee calculation, penalties, or payment collection.
- **NO Digital Media Hosting:** Physical library materials only; no e-book, PDF, or file hosting/streaming.
- **NO Alternate Hardware:** QR code scanning via standard webcam or USB QR scanner only; no RFID, biometrics, or 1D barcode dependencies.
- **NO Reservations/Scheduling:** No seat booking, study-space reservations, or advance book reservations.
- **NO External School System Integration:** Standalone database; no DepEd LIS, enrollment, or grading API connections.
- **NO Embedded AI:** AI tools are used strictly for development, never integrated inside the Laravel application.

## 5. Team Workflow, Branching & QA Standards
- **Team Roles:**
  - **Ramon Anthony V. Dela Fuente** — Backend Developer 
  - **Shaina Cuevas** — Frontend Developer
  - **Kyle E. Mata** — Document Analyst / Backend Developer
  - **Kristine P. Salazar** — Project Manager / Quality Assurance
- **Git Branching:** Keep `main` clean and deployable. Develop sprint tasks on short-lived feature branches (e.g., `feat/sprint-1-auth`, `feat/sprint-1-members-qr`, `feat/sprint-1-books-qr`) and merge to `main` after QA verification.
- **Shared Blade Layouts:** All staff views must extend `resources/views/layouts/app.blade.php` and public kiosk views must extend `resources/views/layouts/kiosk.blade.php` using local `public/vendor/` assets.
- **Sprint Feature Tests:** Every sprint task must include corresponding `php artisan test` feature tests covering RBAC guards, core CRUD/circulation logic, and edge cases before closing the sprint.
