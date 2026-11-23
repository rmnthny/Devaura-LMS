# System Diagrams Reference — UBNHS-LMS (`Devaura-LMS`)

Consolidated reference from the team's Lucidchart diagrams (Current & Proposed Process Flowcharts, ERD, Use Case Diagrams, and DFDs 1.1–1.7) so future sprints and chats have full context without re-uploading images.

---

## 1. Proposed Process Flowcharts
1. **Public Kiosk (Attendance):**
   - **Library Member / Personnel:** Scan QR code or enter ID → System checks if registered → if found, record `time_in_at` / `time_out_at` in `attendance_log` + log to `audit_trail`; if not found, prompt to register.
   - **Walk-In Visitor:** Select Visitor → enter **Last Name, First Name, Middle Initial, Email, Purpose** → record `time_in_at` / `time_out_at` in `attendance_log` + log to `audit_trail`.
2. **Borrow Book:**
   - Scan/search Library Member QR → scan/search Book QR → check `book_status === 'Available'` → check if borrower has overdue books (if yes, notify & reject borrow) → choose due date → record `borrow_transaction` → update `book_status` to `Borrowed` → log to `audit_trail`.
3. **Return Book:**
   - If reported **Lost** → mark book `Lost` & log to `audit_trail`.
   - Otherwise scan/search Book QR → check if **Damaged** (if repairable, schedule repair; else keep `Damaged` out of circulation) → check if overdue (update borrow status) → record book return → if fit for borrowing, set `book_status` to `Available` → log to `audit_trail`.
4. **Book Management:** Add Book (+ generate QR code), Update Book, Delete/Archive Book (with confirmation) → update DB & log to `audit_trail`.
5. **Library Members Management:** Search/Filter Member, Edit Member (with duplicate ID rejection), Delete/Archive Member (Head Librarian/Admin with confirmation) → update DB & log to `audit_trail`.
6. **Library Personnel Management:** Add Personnel (enter personal info, library assignment, credentials, duplicate ID rejection), Search Personnel, Edit Personnel, Delete/Archive Personnel (Head Librarian with confirmation) → log to `audit_trail`.
7. **Book List / Search:** Select & apply book filters → query DB → display matches.
8. **Audit Reports & Audit Trail:** Select filters → generate report / audit trail → Save as PDF or Print.
9. **Role Permission (Updated to Predefined 7-Role Spatie RBAC):** 7 predefined roles (`superadmin`, `admin`, `staff`, `student`, `faculty`, `visitor`, `maintenance`) with fixed privileges per role; `superadmin` assigns personnel roles (`Faculty → admin`, `Student → staff`, `maintenance`) with strict confirmation.

---

## 2. Entity Relationship Diagram (ERD) & DFD Data Store Mapping
Aligned with `feat/sprint-1-database`:

| DFD Store | Database Table(s) | Key Columns / Scope |
| :--- | :--- | :--- |
| **D1 — Member Records** | `library_member` | `member_id` (PK), `account_id` (FK → `user_account`), `last_name`, `first_name`, `middle_initial`, timestamps, `deleted_at` |
| **D2 — Personnel Records** | `library_personnel`, `department`, `personnel_schedule`, `schedule_day`, `schedule_period`, Spatie `roles`/`permissions` | Personnel profiles, subject-only `department`, schedules (`day_id`, `period_id`), and Spatie RBAC |
| **D3 — Visitor Records** | `walk_in_visitor` | `visitor_id` (PK), `last_name`, `first_name`, `middle_initial`, `email` |
| **D4 — Attendance Logs** | `attendance_log` | `log_id` (PK), `user_role`, `member_id`, `personnel_id`, `visitor_id`, `time_in_at`, `time_out_at` |
| **D5 — Book Catalog** | `book`, `genre`, `author`, `book_author`, `publisher`, `book_publisher` | `book_id` (PK), `genre_id`, `isbn`, `book_title`, `book_status` (`Available`, `Borrowed`, `Lost`, `Damaged`), `book_qr_value` (`BK-XXXX-XX`), `date_published`, `deleted_at` |
| **D6 — Borrow Transactions** | `borrow_transaction` | `transaction_id` (PK), `borrower_id`, `processed_by`, `book_id`, `borrow_date`, `return_date`, `due_date`, `transaction_status` (`Borrowed`, `Returned`, `Overdue`, `Lost`) |
| **D7 — Audit Trail** | `activity_log` (`spatie/laravel-activitylog`) | Chronological system activities, actor, target, properties, timestamps |
| **D8 — Login Credentials** | `user_account` | `account_id` (PK), `reference_number` (12-digit LRN / 7-digit DepEd Emp. No.), `username`, `email`, `password_hash`, `account_status` (`Active`, `Inactive`, `Locked`, `Pending`), `user_qr_value` (`UBNHS-YYYY-XXXX`), `deleted_at` |

---

## 3. Use Case Diagrams (By Actor)
- **Superadmin (Head Librarian):** Time In/Out, Manage Book Inventory (+ Generate/Print QR), Audit Library Inventory, Generate Operational Reports, View Audit Trail, Manage User Roles & Superadmin Transfer, Manage Library Personnel & Schedules, Manage Library Members, Search Book Catalog, Process Book Borrowing/Return, View Attendance Logs, Borrow/Return Book.
- **Admin (Assigned Teacher — Faculty only):** Time In/Out, Search Book Catalog, Process Book Borrowing/Return, View Attendance Logs, Update Personal Info, View Audit Trail, Borrow/Return Book, Manage Library Personnel & Staff Schedules, Manage Book Inventory (+ Generate/Print QR), Manage Members, Generate Operational Reports, Audit Inventory.
- **Staff (Student Staff — Student only):** Time In/Out, Search Book Catalog, Process Book Borrowing/Return, View Attendance Logs, Update Personal Info, Borrow/Return Book, Manage Library Personnel (view/schedule), Manage Book Inventory (+ Generate/Print QR).
- **Library Member (Student / Faculty):** Register Account (+ Email OTP & Member QR), Log In, Time In/Out, Search Book Catalog, Borrow Book, Return Book, View Borrowing History, View Personal QR, Update Personal Info.
- **Walk-In Visitor:** Time In/Out (at `/kiosk` or manual entry), Search Book Catalog.

---

## 4. Data Flow Diagrams (DFD 1.1 – 1.7)
- **1.1 Account Registration & Login:** Retrieve User Info → Verify User Via Email (6-digit OTP) → Record Registration (`D1`, `D8`) → Identify & Verify User → Allow Access → Record Login/Attendance (`D7`).
- **1.2 Library Attendance:** Identify Library Visitor (`D1`, `D2`, `D3`) → Record Time-In or Time-Out (`D4`) + Audit (`D7`).
- **1.3 Book Catalog & Inventory:** Access & Maintain Book Catalog (`D5`) / Search Book Catalog (`D5`).
- **1.4 Book Borrowing:** Validate Borrower (`D1`, check overdue in `D6`) → Check Book Availability (`D5`) → Record Borrowing (`D6`, update `D5`, log `D7`).
- **1.5 Returns & Losses:** Find Active Borrow Record (`D6`) → Assess Return or Loss → Update Status (`D5`, `D6`) & Audit (`D7`).
- **1.6 QR Code Generation:** Retrieve Member (`D1`/`D8`) or Book (`D5`) ID → Generate QR Code (`milon/barcode`).
- **1.7 Reports & Record Retrieval:** Receive Report/History Query → Compile & Output Records (`D4`, `D5`, `D6`, `D7`).
