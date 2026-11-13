# Devaura-LMS — UBNHS QR Code-Integrated Library Management System

**System Title:** *Design, Development, and Evaluation of a QR Code-Integrated Library Management System for Upper Bicutan National High School (UBNHS-LMS)*  
**Team:** Group 1 — Devaura (Cuevas, Shaina; Dela Fuente, Ramon Anthony V.; Mata, Kyle E.; Salazar, Kristine P. — PUP Taguig)

---

## 1. Tech Stack & Offline Guarantee
- **Backend:** PHP 8.3+, Laravel 13
- **Database:** MySQL 8.4+ (`devaura_lms`)
- **Frontend:** Laravel Blade + Local Offline Assets in `public/vendor/`:
  - Bootstrap 5.3.3 (`public/vendor/bootstrap/`)
  - Bootstrap Icons 1.11.3 (`public/vendor/bootstrap-icons/`)
  - HTML5-QRCode 2.3.8 (`public/vendor/html5-qrcode/`)
- **Core Packages:**
  - `spatie/laravel-permission` (RBAC: `head_librarian`, `library_staff`, `student_staff`)
  - `spatie/laravel-activitylog` (Audit logging)
  - `simplesoftwareio/simple-qrcode` (Member & Book QR code generation)
  - `barryvdh/laravel-dompdf` (Printable PDF reports & QR sheets)
  - `maatwebsite/excel` (Excel report exports)

---

## 2. Teammate Local Setup Instructions (Laragon / Windows)

### Prerequisites
- **Laragon** with **PHP 8.3+** (ensure `gd` and `zip` extensions are enabled in `php.ini`) and **MySQL 8.4+** running.
- Create the local database in MySQL / HeidiSQL:
  ```sql
  CREATE DATABASE IF NOT EXISTS devaura_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ```

### Installation Steps
Run the following commands inside `C:\laragon\www`:

```bash
git clone https://github.com/rmnthny/Devaura-LMS.git
cd Devaura-LMS
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
```

Start the local development server:
```bash
php artisan serve
```
Then open `http://localhost:8000` in your browser.

---

## 3. AI-Assisted Development & Sprint Gate Rules
This repository uses strict Cross-AI context loaders (`GEMINI.md`, `CLAUDE.md`, `.github/copilot-instructions.md`, and `.agent/rules/ubnhs-lms.md`). Before prompting any AI coding assistant, ensure it reads:
1. `docs/PROJECT_RULES.md` — Locked stack, RBAC role slugs, and strict out-of-scope exclusions.
2. `docs/SCHEMA.md` — Exact MySQL tables, columns, enums, and foreign keys.
3. `docs/SPRINT_STATUS.md` — Active sprint gate (`CURRENT SPRINT` vs. `LOCKED / FUTURE SPRINTS`).
4. `docs/REQUIREMENTS.md` — Traceability to UBNHS operational requirements `R1`–`R5`.
