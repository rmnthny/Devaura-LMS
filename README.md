# Devaura-LMS (UBNHS-LMS)

**QR Code-Integrated Library Management System** for **Upper Bicutan National High School (UBNHS)**.

Built by **Group 1 — Devaura** (PUP Taguig):
- **Ramon Anthony V. Dela Fuente** — Lead Backend Developer
- **Shaina Cuevas** — Frontend Developer
- **Kyle E. Mata** — Document Analyst / Database & Backend Developer
- **Kristine P. Salazar** — Project Manager / Quality Assurance

---

## Tech Stack
- **Backend:** PHP 8.3+, Laravel 13
- **Frontend:** Blade, Bootstrap 5, Tailwind CSS, JavaScript (ES6+) — 100% offline assets in `public/vendor/`
- **Database:** MySQL 8.4+ (`devaura_lms`)
- **Local Environment:** Laragon 6.0.0 (`http://localhost:8000`)
- **Key Packages:** `spatie/laravel-permission`, `spatie/laravel-activitylog`, `simplesoftwareio/simple-qrcode`, `html5-qrcode`, `barryvdh/laravel-dompdf`, `maatwebsite/excel`

---

## Core Features
- **7-Role RBAC & Email OTP:** `superadmin` (Head Librarian), `admin` (Assigned Teacher), `staff` (Student Staff), `student`, `faculty`, `visitor`, and `maintenance`. Public sign-up/login is limited to `student` and `faculty` with Email OTP verification; `admin`, `staff`, and `maintenance` accounts are managed exclusively by `superadmin`.
- **Book Inventory & Archiving:** Digital catalog with stock counts, real-time availability, unique QR codes, soft-delete archiving, and QR-verified inventory audits.
- **Public Kiosk & Attendance Monitoring:** Dedicated `/kiosk` terminal for quick QR `time_in` / `time_out`, plus manual attendance entry for all user types including visitors.
- **Book Circulation & Notifications:** QR-assisted borrow/return workflows, overdue tracking (zero fines/payments), and automated alerts.
- **Analytical Dashboard, Reports & Audit Logs:** Interactive charts, PDF/Excel report exports, and chronological activity logs.

---

## Local Setup (Laragon 6.0.0)
1. Clone the repository and install dependencies:
   ```bash
   git clone https://github.com/rmnthny/Devaura-LMS.git
   cd Devaura-LMS
   composer install
   ```
2. Copy `.env.example` to `.env`, generate the app key, and configure your MySQL database (`devaura_lms`):
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Run migrations and link storage:
   ```bash
   php artisan migrate
   php artisan storage:link
   ```
4. Start the local server and run tests:
   ```bash
   php artisan serve
   php artisan test
   ```

---

## Project Docs
- [Project Rules](docs/PROJECT_RULES.md)
- [Sprint Status](docs/SPRINT_STATUS.md)
- [Requirements](docs/REQUIREMENTS.md)
- [Sprint Handoffs](docs/handoffs/README.md)
