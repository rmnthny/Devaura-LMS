# Devaura-LMS

QR Code-Integrated Library Management System for Upper Bicutan National High School, developed by Group 1 — Devaura of PUP Taguig.

## Team

| Member | Role |
| --- | --- |
| Shaina Cuevas | Frontend Developer |
| Ramon Anthony V. Dela Fuente | Backend Developer |
| Kyle E. Mata | Documentation Analyst |
| Kristine P. Salazar | Project Manager and Quality Assurance |

## Technology

- PHP 8.3 or later
- Laravel 13
- MySQL 8.4 or later
- Laravel Blade and offline Bootstrap assets
- Spatie Laravel Permission and Activitylog
- Server-side QR code generation
- Laravel Mail for Email OTP
- PHPUnit and Postman for backend testing

## Current Sprint

Sprint 1 covers:

- Student and faculty registration
- Email OTP verification
- Login, logout, and account-status protection
- Seven predefined user roles
- Superadmin role management
- Personal profile updates
- Member management and archiving
- Member QR code generation
- Activity logging
- Backend feature tests

Sprint work is developed on feature branches and merged into `main` only after automated tests, Postman verification, and QA approval.

## Local Setup

Clone the repository and install its dependencies:

```powershell
git clone https://github.com/rmnthny/Devaura-LMS.git
Set-Location Devaura-LMS
composer install
```

Create the local environment file and application key:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Configure the following values in `.env`:

- MySQL connection for the `devaura_lms` database
- Mail settings for Email OTP
- Application URL and other machine-specific settings

Prepare and run the application:

```powershell
php artisan migrate
php artisan storage:link
php artisan serve
```

Run the backend tests:

```powershell
php artisan test
```

Do not commit `.env`, passwords, mail credentials, database credentials, private keys, or other secrets.

## Development Workflow

1. Create a feature branch from `main`.
2. Implement only the tasks assigned to the current sprint.
3. Run PHPUnit and Postman checks.
4. Submit the feature branch for review.
5. Apply QA fixes.
6. Merge into `main` after approval.

## Project Documents

- [Project Rules](docs/PROJECT_RULES.md)
- [Team Workflow](docs/TEAM_WORKFLOW.md)
- [Sprint Status](docs/SPRINT_STATUS.md)
- [Requirements](docs/REQUIREMENTS.md)
