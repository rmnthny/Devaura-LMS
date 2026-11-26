# Devaura-LMS (UBNHS-LMS)

**QR Code-Integrated Library Management System** for **Upper Bicutan National High School (UBNHS)**.

Built by **Group 1 — Devaura** (PUP Taguig):
- **Ramon Anthony V. Dela Fuente** — Lead Backend Developer
- **Shaina Cuevas** — Frontend Developer
- **Kyle E. Mata** — Document Analyst / Database Developer
- **Kristine P. Salazar** — Project Manager / Quality Assurance

---

## Tech Stack
- **Backend:** PHP 8.4+, Laravel 13
- **Frontend:** Blade, Bootstrap 5, JavaScript (ES6+) — local assets under `public/vendor/` for offline operation
- **Database:** MySQL 8.4+ (`devaura_lms`)
- **Local Environment:** Laragon 6.0.0 (`http://localhost:8000`)
- **Key Packages:** `spatie/laravel-permission`, `spatie/laravel-activitylog`, `milon/barcode`, `barryvdh/laravel-dompdf`, `maatwebsite/excel`

---

## Core Features
- **7-Role RBAC:** `superadmin` (Head Librarian), `admin` (Assigned Teacher), `staff` (Student Staff), `student`, `faculty`, `visitor`, and `maintenance`. The Head Librarian adds Student and Faculty accounts individually or by import and is the only role with Archive access. The Head Librarian and Admins can add books. Existing pending accounts can still verify their email.
- **Book Inventory & Archiving:** Digital catalog with stock counts, real-time availability, unique QR codes, soft-delete archiving, and QR-verified inventory audits.
- **Personnel schedules:** Assigned teachers and student staff can view their shifts. Schedule managers can add, edit, or remove shifts within their assigned permissions.
- **Current sprint:** Public kiosk and attendance monitoring. Current implementation details and resume instructions are in [Active Development Handoff](docs/ACTIVE_HANDOFF.md).

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
3. Enable the PHP MySQL, GD, and Zip extensions. Run the migrations and seed the library settings:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```
4. Start the web server and queue workers:
   ```bash
   composer dev
   ```

### Tests

Create a separate MySQL database named `devaura_lms_test`. Run `php artisan test`
with a MySQL user that can create and update tables in that database. The test suite
checks the database name before running migrations. The GitHub workflow starts
MySQL 8.4 and creates this database for each run.

### Bulk imports

Use `composer dev` for localhost to start the staff web server, public attendance kiosk, import listener, and welcome email worker together.

The public kiosk runs at `http://127.0.0.1:8001/kiosk` locally. If you start the staff server with `php artisan serve`, start the kiosk in another terminal with `php artisan serve --host=127.0.0.1 --port=8001 --tries=1`. Set `KIOSK_URL` to the dedicated kiosk origin in a deployment and direct that origin to Laravel. Use a separate browser profile on the public terminal so its browser history and tabs start without a staff session.
Small imports of up to 20 members or 20 book copies finish during the upload request.
Larger imports use the configured queue. Welcome emails use a separate queue so an SMTP delay does not
hold up account creation. Email jobs are saved with the accounts and become available after commit.
If you run `php artisan serve` separately with `QUEUE_CONNECTION=database`, run
`php artisan queue:work` for imports and `php artisan queue:work member-mail` for welcome emails.
Enable PHP's `zip` extension in Laragon for XLSX imports and templates, then restart the existing web server.
The Head Librarian imports official Student and Faculty records from Members. The Head
Librarian and Assigned Teachers can import books from Books.
Upload an Excel workbook (`.xlsx` or `.xls`) with one worksheet or a CSV file (`.csv`).
Each import supports up to 2,000 data rows and 5 MB. Download an Excel or CSV template
from Members or Books. Keep the headers in the first row. The Excel templates include column notes
and format member references and ISBNs as text to preserve leading zeros.
Configure the SMTP mailer for welcome delivery. Temporary passwords are not sent through the log mailer.
For a fresh installation with no Head Librarian, an authorized operator can run
`php artisan library:bootstrap-head 1234567 --from=first-head.csv`. The file must use the
member template and contain only the designated Faculty record. Replace the sample
employee number with the matching value from that official record.

---

## Project Docs
- [AI Working Agreement](AGENTS.md)
- [Active Development Handoff](docs/ACTIVE_HANDOFF.md)
- [Project Rules](docs/PROJECT_RULES.md)
- [Sprint Status](docs/SPRINT_STATUS.md)
- [Requirements](docs/REQUIREMENTS.md)
- [Sprint Handoffs](docs/handoffs/README.md)
