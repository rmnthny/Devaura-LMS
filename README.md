# Devaura-LMS

Welcome to **Devaura-LMS**, the QR Code-Integrated Library Management System we are building for **Upper Bicutan National High School (UBNHS)**.

Our goal is to replace manual paper logbooks and scattered spreadsheets with a fast, reliable, and offline-ready web application. Students and faculty can scan their QR codes to log library visits, staff can process book borrowing and returns in seconds, and librarians can run physical inventory audits and export reports with ease.

---

## Meet the Team (Group 1 — Devaura, PUP Taguig)
- **Ramon Anthony V. Dela Fuente** — Backend Developer
- **Shaina Cuevas** — Frontend Developer
- **Kyle E. Mata** — Document Analyst / Backend Developer
- **Kristine P. Salazar** — Project Manager / Quality Assurance

---

## What Powers the System
We built this project to run **100% offline** on a local server during our defense, without relying on external internet connections:
- **Backend:** PHP 8.3+ and Laravel 13
- **Database:** MySQL 8.4+ (`devaura_lms`)
- **Frontend:** Laravel Blade with local copies of Bootstrap 5.3.3, Bootstrap Icons 1.11.3, and HTML5-QRCode 2.3.8 stored directly in `public/vendor/`
- **Key Features & Packages:**
  - Role-based access (`spatie/laravel-permission`) for `head_librarian`, `library_staff`, and `student_staff`
  - Activity tracking (`spatie/laravel-activitylog`)
  - Local QR code generation (`simplesoftwareio/simple-qrcode`)
  - Printable PDF and Excel reports (`barryvdh/laravel-dompdf` and `maatwebsite/excel`)

---

## Getting Started on Your Machine (Laragon / Windows)

### 1. Prepare Your Environment
Make sure **Laragon** is running with **PHP 8.3+** (with `gd` and `zip` enabled in `php.ini`) and **MySQL 8.4+**. Then create the local database in MySQL or HeidiSQL:
```sql
CREATE DATABASE IF NOT EXISTS devaura_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Clone and Install
Open your terminal inside `C:\laragon\www` and run:
```bash
git clone https://github.com/rmnthny/Devaura-LMS.git
cd Devaura-LMS
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
```

### 3. Run the App
Start the local server:
```bash
php artisan serve
```
Then open `http://localhost:8000` in your browser.

---

## How We Work Together (Git & AI Workflow)
- **Branching:** Keep `main` clean. Create a short-lived branch for each task (e.g., `feat/sprint-1-members-qr`) and merge it into `main` once Kristine reviews and tests it.
- **AI Context:** Whenever you use an AI coding assistant, it automatically reads our project rules (`docs/PROJECT_RULES.md`), database schema (`docs/SCHEMA.md`), requirements (`docs/REQUIREMENTS.md`), and active sprint (`docs/SPRINT_STATUS.md`).
- **Automatic Handoffs:** You don't need to write markdown documentation by hand—the AI generates sprint handoff notes in `docs/handoffs/` and syncs Notion sprint updates for us.
