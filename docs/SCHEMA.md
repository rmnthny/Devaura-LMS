# UBNHS-LMS Locked MySQL Database Schema (`devaura_lms`)

> **WARNING:** Never invent new database columns, alter data types, or rename existing tables/columns without explicit developer approval to update this file first.

## 1. `users`
Authenticated staff accounts managed with `spatie/laravel-permission` roles (`head_librarian`, `library_staff`, `student_staff`).

| Column | Data Type | Modifiers / Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `bigint unsigned` | PK, Auto-Increment | Primary key |
| `name` | `varchar(255)` | Required | Full name of staff user |
| `email` | `varchar(255)` | Unique, Required | Staff login email |
| `password` | `varchar(255)` | Required | Hashed password |
| `status` | `enum('active', 'inactive')` | Default `'active'` | Account status |
| `remember_token` | `varchar(100)` | Nullable | Laravel session remember token |
| `created_at` | `timestamp` | Nullable | Record creation timestamp |
| `updated_at` | `timestamp` | Nullable | Record update timestamp |

---

## 2. `members`
Registered library borrowers and visitors (UBNHS students and faculty).

| Column | Data Type | Modifiers / Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `bigint unsigned` | PK, Auto-Increment | Primary key |
| `member_code` | `varchar(255)` | Unique, Required | System member code (e.g., `UBNHS-2026-0001`) |
| `lrn_or_employee_id` | `varchar(255)` | Unique, Required | DepEd LRN (12 digits) or Employee ID |
| `first_name` | `varchar(255)` | Required | Member first name |
| `last_name` | `varchar(255)` | Required | Member last name |
| `member_type` | `enum('student', 'faculty')` | Required | Borrower classification |
| `grade_level` | `tinyint unsigned` | Nullable | Grade level (`7`–`12` for students; `null` for faculty) |
| `section` | `varchar(255)` | Nullable | Class section or department |
| `qr_code_path` | `varchar(255)` | Nullable | Relative storage path to generated QR PNG |
| `status` | `enum('active', 'inactive')` | Default `'active'` | Membership status |
| `created_at` | `timestamp` | Nullable | Record creation timestamp |
| `updated_at` | `timestamp` | Nullable | Record update timestamp |

---

## 3. `books`
Physical library catalog items (each physical copy has a unique `accession_number` and QR code).

| Column | Data Type | Modifiers / Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `bigint unsigned` | PK, Auto-Increment | Primary key |
| `accession_number` | `varchar(255)` | Unique, Required | Unique physical copy identifier (e.g., `ACC-00001`) |
| `isbn` | `varchar(255)` | Nullable | International Standard Book Number |
| `title` | `varchar(255)` | Required | Book title |
| `author` | `varchar(255)` | Required | Book author(s) |
| `publisher` | `varchar(255)` | Nullable | Publisher name |
| `publication_year` | `year` | Nullable | Year of publication |
| `category` | `varchar(255)` | Required | Subject/Dewey/learning area classification |
| `shelf_location` | `varchar(255)` | Nullable | Physical shelf/rack identifier |
| `qr_code_path` | `varchar(255)` | Nullable | Relative storage path to generated QR PNG |
| `status` | `enum('available', 'borrowed', 'missing', 'damaged')` | Default `'available'` | Current physical copy status |
| `created_at` | `timestamp` | Nullable | Record creation timestamp |
| `updated_at` | `timestamp` | Nullable | Record update timestamp |

---

## 4. `attendance_logs`
Daily library entry and exit logs captured via QR code scanning at `/kiosk` or staff desk.

| Column | Data Type | Modifiers / Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `bigint unsigned` | PK, Auto-Increment | Primary key |
| `member_id` | `bigint unsigned` | FK -> `members.id` (`cascadeOnDelete`) | Visiting library member |
| `time_in` | `datetime` | Required | Entry timestamp |
| `time_out` | `datetime` | Nullable | Exit timestamp (`null` while currently inside library) |
| `log_date` | `date` | Indexed, Required | Calendar date of visit (`YYYY-MM-DD`) |
| `created_at` | `timestamp` | Nullable | Record creation timestamp |
| `updated_at` | `timestamp` | Nullable | Record update timestamp |

---

## 5. `borrow_transactions`
Circulation ledger tracking physical book loans and returns (no fines/payments).

| Column | Data Type | Modifiers / Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `bigint unsigned` | PK, Auto-Increment | Primary key |
| `transaction_code` | `varchar(255)` | Unique, Required | Unique transaction code (e.g., `TRX-20261113-0001`) |
| `book_id` | `bigint unsigned` | FK -> `books.id` (`restrictOnDelete`) | Borrowed physical book copy |
| `member_id` | `bigint unsigned` | FK -> `members.id` (`restrictOnDelete`) | Borrowing library member |
| `processed_by` | `bigint unsigned` | FK -> `users.id` (`restrictOnDelete`) | Authenticated staff who processed transaction |
| `borrowed_at` | `datetime` | Required | Checkout timestamp |
| `due_date` | `date` | Required | Expected return date |
| `returned_at` | `datetime` | Nullable | Actual return timestamp (`null` until returned) |
| `status` | `enum('borrowed', 'returned', 'overdue')` | Default `'borrowed'` | Circulation status |
| `remarks` | `text` | Nullable | Optional condition or return remarks |
| `created_at` | `timestamp` | Nullable | Record creation timestamp |
| `updated_at` | `timestamp` | Nullable | Record update timestamp |

---

## 6. `inventory_audits`
Periodic physical stocktaking/auditing sessions conducted by library staff.

| Column | Data Type | Modifiers / Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `bigint unsigned` | PK, Auto-Increment | Primary key |
| `audit_title` | `varchar(255)` | Required | Audit session name (e.g., `SY 2026-2027 Q2 Inventory Audit`) |
| `conducted_by` | `bigint unsigned` | FK -> `users.id` | Staff member who initiated the audit |
| `audit_date` | `date` | Required | Date audit was started/conducted |
| `status` | `enum('in_progress', 'completed')` | Default `'in_progress'` | Audit workflow state |
| `notes` | `text` | Nullable | Summary notes or discrepancy remarks |
| `created_at` | `timestamp` | Nullable | Record creation timestamp |
| `updated_at` | `timestamp` | Nullable | Record update timestamp |

---

## 7. `inventory_audit_items`
Per-book verification records within an `inventory_audits` session.

| Column | Data Type | Modifiers / Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `bigint unsigned` | PK, Auto-Increment | Primary key |
| `audit_id` | `bigint unsigned` | FK -> `inventory_audits.id` (`cascadeOnDelete`) | Parent inventory audit session |
| `book_id` | `bigint unsigned` | FK -> `books.id` (`cascadeOnDelete`) | Catalog book copy being audited |
| `scanned_status` | `enum('verified', 'missing', 'misplaced')` | Default `'missing'` | Audit verification outcome |
| `scanned_at` | `datetime` | Nullable | Timestamp when book QR code was scanned |
| `created_at` | `timestamp` | Nullable | Record creation timestamp |
| `updated_at` | `timestamp` | Nullable | Record update timestamp |

---

## 8. Package-Managed Tables
- **Spatie Permission (`spatie/laravel-permission`):** `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
- **Spatie Activitylog (`spatie/laravel-activitylog`):** `activity_log`
- **Laravel Core:** `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `sessions`
