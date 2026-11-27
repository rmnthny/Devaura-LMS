# System Diagrams Reference — UBNHS-LMS

Reviewed November 26, 2026 against the proposed v10 process flow, v3 ERD, role use cases, and DFDs 1.0–1.8. The workflow diagrams are the design reference. The current release covers Sprints 1–4. Sprint 5 behavior is documented for later activation. The current-process flowcharts describe the paper logbooks and staff handoffs the system replaces. Use the proposed flows as the QA reference, with the confirmed policies in [Project Rules](PROJECT_RULES.md) and the rollout recorded in [Sprint Status](SPRINT_STATUS.md).

## Process and use-case alignment

| Process | System behavior and QA checkpoints |
| --- | --- |
| Public kiosk and attendance | Eligible personnel sign in and time in before the attendance-only kiosk can open. Members enter a QR value or registered reference number. Walk-in visitors enter their name, email, and purpose at time-in and use their email to time out. Verify invalid identifiers, duplicate time-in, role and shift coverage, auditable time-in/out, authorized force time-out, and the separate kiosk origin. |
| Borrow a physical copy | Authorized personnel use the member QR value or reference number and the book QR value. Staff can also search for an eligible member and select an available physical copy on the borrowing screen. The system assigns the due date three calendar days after checkout in Manila time. Checkout validates the member and copy, rejects checkout while the member has an unresolved overdue loan, records the processor and transaction reference, updates the copy, and writes an audit event. Check that two copies with the same title remain distinguishable. |
| Return or report a loss | Authorized personnel locate the open loan using the book QR value. Staff can also search and select an open borrowing record. The return audit records whether the loan was overdue and the resulting copy status. Returns use the original transaction reference. A fit copy becomes available, a damaged copy remains out of circulation, and a reported loss marks the copy lost. Repair scheduling and completion are Sprint 5 workflows. Verify the recorded outcome and that a replay cannot close a later loan. |
| Book management and catalog search | Head Librarian and Admin add or edit copies and manage catalog lookups. Registered members and authorized staff can filter the catalog. QR labels identify individual copies. Archive confirmation and the Head Librarian-only archive boundary preserve book history. |
| Member and personnel management | Head Librarian provisions Student and Faculty accounts manually or by import. Authorized personnel manage members within their assigned privileges. Personnel assignments build on existing member accounts and fixed roles. Verify duplicate ID rejection, password-confirmed role changes, role transfer, schedule access, profile edits, and permitted archival. |
| Inventory audit | In Sprints 1–4 the Head Librarian conducts individual copy checks. Each check records the actor and outcome and preserves open loans. Session worklists, recounts, discrepancy review, and approval are deferred. |
| Operational reports and activity | Authentication, approved management operations, attendance, circulation, and inventory checks produce activity records. Report filtering, printing, PDF and Excel export, personal transaction history, dashboards, and audit-trail viewing are deferred to Sprint 5. |

Use the [Sprint 4 handoff](handoffs/SPRINT_4_HANDOFF.md) to verify which circulation branches were exercised in QA.

## DFD and ERD crosswalk

| Store in the supplied DFDs | Current schema or draft status |
| --- | --- |
| D1 — Member Records | `library_member`, linked to `user_account` by `account_id` |
| D2 — Personnel Records | `library_personnel`, `department`, `personnel_schedule`, `schedule_day`, `schedule_period` |
| D3 — Attendance Logs | `attendance_log` links either `account_id` or `visitor_id` and retains visitor-only purpose |
| D4 — Book Catalog | `book`, `genre`, `author`, `book_author`, `publisher`, `book_publisher`. `book_id` and `book_qr_value` identify a physical copy. |
| D5 — Borrow Transactions | `borrow_transaction` links `borrower_id`, `borrow_processed_by`, and `return_processed_by` to accounts. It tracks the copy, loan dates, transaction status, and a unique transaction code. |
| D6 — Audit Trail | `activity_log` records the actor, subject, event, and changes. |
| D7 — User Accounts | `user_account` holds the unique reference number, email, account status, and member QR value. Email is used for login. |
| D8 and D9 — Inventory Audit and Items | The deferred `inventory_audit` and `inventory_audit_item` schema stores sessions, verification, and discrepancies. Individual copy checks use `book` and `activity_log` in the current release. |
| D10 — Visitor Records | `walk_in_visitor` stores visitor identity. Purpose belongs to the attendance visit. |
| D11 — Roles and Permissions | Predefined Spatie roles and permissions and their assignment tables. |

`notifications` stores member notices separately. The Sprint 4 migration also adds a checkout request identifier for replay protection and timestamps for email delivery attempts.

| DFD | Verification against the local system |
| --- | --- |
| 1.0 — Library Management System | Verify the access and account boundaries for Head Librarian, Admin, Staff, library members, and walk-in visitors. |
| 1.1 — Authenticate Users and Manage Access | Verify email login, account status, OTP where applicable, profile updates, assigned permissions, and authentication activity. |
| 1.2 — Record Library Attendance | Verify member and visitor attendance against D3, with personnel coverage, correct identity, and D6 activity. |
| 1.3 — Manage Book Catalog and Inventory | Verify authorized maintenance, import, QR print, and catalog filters against D4. |
| 1.4 — Process Book Borrowing | Verify account eligibility and staff authorization, availability and overdue checks, D4/D5 consistency, D6 audit, and the checkout notice. |
| 1.5 — Process Book Returns and Losses | Verify the selected loan and physical copy, the return assessment, D4/D5 status updates, and D6 outcome. |
| 1.6 — Manage Members and Personnel | Verify provisioning, member and personnel changes, schedule access, fixed role assignment, and management activity. |
| 1.7 — Conduct Inventory Audits | Verify Head Librarian access and per-copy checks now. Worklists, recounts, discrepancy filters, and approvals belong to Sprint 5. |
| 1.8 — Generate Reports and Retrieve Records | Verify that reporting, dashboard, own-record history, and activity viewer routes are absent in Sprints 1–4. Test their access, filters, and exports when Sprint 5 opens. |

## Finalized access and workflow decisions

- The public kiosk offers attendance on a separate origin. It directs unregistered members to the Head Librarian for registration. Visitors can record attendance without an account.
- QR inputs accept typed values and keyboard-style readers. Member reference and search-based selection provide the manual paths.
- Authorized staff assign existing roles within the Spatie permission matrix. The Head Librarian retains Archive access. Admins may assign and revoke Student Staff within their authorized scope.
- The Head Librarian alone conducts individual copy checks. When Sprint 5 opens, the Head Librarian also conducts and approves audit sessions.
- Damaged returns remain out of circulation. Scheduled repairs and their completion screen are deferred to Sprint 5.
- An overdue checkout rejection queues the same notice as the scheduler, which prevents duplicate alerts for the same loan.
- The [active handoff](ACTIVE_HANDOFF.md) records the Sprint 1–4 release and QA. The [deferred Sprint 5 handoff](handoffs/SPRINT_5_HANDOFF.md) records the earlier draft and its prior QA.
