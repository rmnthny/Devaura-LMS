# System Diagrams Reference — UBNHS-LMS

Reviewed November 26, 2026 against the proposed v10 process flow, v3 ERD, role use cases, and DFDs 1.0–1.8. The workflow diagrams are the design reference. The current release covers Sprints 1–4. The current development checkout includes the approved Sprint 5 workflows. The current-process flowcharts describe the paper logbooks and staff handoffs the system replaces. Use the proposed flows as the QA reference, with the confirmed policies in [Project Rules](PROJECT_RULES.md) and the rollout recorded in [Sprint Status](SPRINT_STATUS.md).

## Finalized process flowcharts and Sprint 5 comparison

| Flowchart | Current app and scope |
| --- | --- |
| Public Kiosk | Personnel sign in and time in before coverage opens the dedicated kiosk. Members use a QR value or reference. Visitors enter name, email, and purpose on entry and use email to time out. Personnel can verify and assist from Attendance Monitoring. Camera scanning and a separate public visitor approval step are outside the approved kiosk flow. |
| Borrow Book | Staff use QR values or search and select the borrower and physical copy. The server checks active account and copy availability, blocks overdue borrowers, queues the notice, and records the loan, three-day Manila due date, copy state, and audit entry. |
| Return Book | Staff look up the open loan using the book QR or search. Normal return, damaged return, and loss update circulation and the copy state and create audit entries. Repair scheduling stays deferred. |
| Book Management | Head Librarian and Admin can add, import, edit, and prepare printable QR labels. Head Librarian alone can archive eligible records. Imports validate before processing and write audit entries. Submission is the commit step for edits and imports. A separate post-validation import confirmation is outside the approved Sprint 5 scope. Batch QR PDF generation now records the actor and selected copies. |
| Library Member Management | The Head Librarian adds and imports Students and Faculty, and authorized managers edit or generate printable QR cards. Archival is Head Librarian-only. Provisioning and welcome mail follow existing rules. Imports validate before processing. Batch QR PDF generation now records the actor and member selection. |
| Library Personnel Management | Password-confirmed role changes, Head Librarian transfer, and schedule create/update/remove follow the predefined role matrix. Admins manage Student Staff assignments and schedules. |
| Operational Reports | Authorized users select report type and filters, preview results, export PDF or Excel, and print the current page. Individual inventory checks remain Head Librarian-only. |
| Audit Trail | Head Librarian and Admin can preview by Manila dates and exact activity area, export the same matching entries as PDF or Excel, and print the current filtered page. PDF and Excel include all matches up to the existing 1,000-row limit. |
| Book Lookup | Signed-in users with catalog access can search, apply filters, and see physical-copy details and availability. The public kiosk stays focused on attendance. |

The prototype accepts typed QR values and keyboard-style scanners. QR sheet audit entries record document generation. Browser and printer actions occur on the client.

## Finalized use cases and Sprint 5 comparison

| Diagram | Current app and scope |
| --- | --- |
| 1. Library Management System overview | Authentication, dashboard, attendance, circulation, catalog, and administration are exposed through role-specific routes and navigation. Operational Reports and Audit Trail are for Head Librarian and Admin. Walk-in visitors use the attendance kiosk. |
| 2. Library Member and Walk-In Visitor | Signed-in members can view their personal dashboard, account settings, borrowing history, and catalog. Members request loans at the desk. Walk-in visitors use the attendance kiosk without an account. |
| 3. Record Attendance | Members and visitors record their own time in and out. Personnel coverage controls kiosk access. Head Librarian and Admin can force time out with a reason recorded in the audit trail. |
| 4. Library Management System personnel overview | Head Librarian and Admin can access operational reports and activity reports. Staff process circulation and have their personal dashboard. The confirmed permission matrix controls management actions and reserves inventory checks for the Head Librarian. |
| 5. Borrow and Return | Members can search the catalog and request borrowing or returns. Eligible personnel process checkout, normal and damaged returns, or a reported loss. QR values can be typed or entered by a keyboard-style scanner. Camera integration remains deferred. |
| 6. Book Management | Head Librarian and Admin add, import, and update books. Staff can look up copies and access permitted QR functions. Head Librarian archives copies subject to circulation restrictions. Book removal follows the existing archival and retention rules. |
| 7. Library Members Management | Head Librarian adds, imports, and archives members and can permanently remove eligible archived records. Admin can search and edit permitted member records. Members and Staff can update their own personal information. |
| 8. Library Personnel Management | Head Librarian controls broader personnel assignments and privileged role transfer. Admin can manage Student Staff assignments and schedules. Search and schedule management use the predefined role limits. |
| 9. Inventory Auditing | Head Librarian records per-copy verification and Lost or Damaged outcomes. Inventory Checks now filters by check dates and current or retained book title, genre, and publication date. The catalog status snapshot uses the same book filters. Reports support PDF, Excel, and current-page print. Inventory sessions and approvals remain deferred. |
| 10. Operational Reports | Head Librarian and Admin can filter, preview, download, and print attendance and circulation reports. Circulation now includes a current snapshot of all active loans. Attendance retains visit counts and role filters. A separate membership census is outside the approved report types. Inventory Checks retains its Head Librarian-only permission. |
| 11. Audit Trail | Head Librarian and Admin can combine Manila dates, activity area, exact event, literal action text, and exact circulation reference. The viewer shows the transaction reference when recorded. PDF and Excel share the filter query and print uses the current filtered page. |

## Process and use-case alignment

| Process | System behavior and QA checkpoints |
| --- | --- |
| Public kiosk and attendance | Eligible personnel sign in and time in before the attendance-only kiosk can open. Members enter a QR value or registered reference number. Walk-in visitors enter their name, email, and purpose at time-in and use their email to time out. Verify invalid identifiers, duplicate time-in, role and shift coverage, auditable time-in/out, authorized force time-out, and the separate kiosk origin. |
| Borrow a physical copy | Authorized personnel use the member QR value or reference number and the book QR value. Staff can also search for an eligible member and select an available physical copy on the borrowing screen. The system assigns the due date three calendar days after checkout in Manila time. Checkout validates the member and copy, rejects checkout while the member has an unresolved overdue loan, records the processor and transaction reference, updates the copy, and writes an audit event. Check that two copies with the same title remain distinguishable. |
| Return or report a loss | Authorized personnel locate the open loan using the book QR value. Staff can also search and select an open borrowing record. The return audit records whether the loan was overdue and the resulting copy status. Returns use the original transaction reference. A fit copy becomes available, a damaged copy remains out of circulation, and a reported loss marks the copy lost. Repair scheduling and completion remain deferred. Verify the recorded outcome and that a replay cannot close a later loan. |
| Book management and catalog search | Head Librarian and Admin add or edit copies and manage catalog lookups. Registered members and authorized staff can filter the catalog. QR labels identify individual copies. Archive confirmation and the Head Librarian-only archive boundary preserve book history. |
| Member and personnel management | Head Librarian provisions Student and Faculty accounts manually or by import. Authorized personnel manage members within their assigned privileges. Personnel assignments build on existing member accounts and fixed roles. Verify duplicate ID rejection, password-confirmed role changes, role transfer, schedule access, profile edits, and permitted archival. |
| Inventory audit | In Sprints 1–4 the Head Librarian conducts individual copy checks. Each check records the actor and outcome and preserves open loans. Session worklists, recounts, discrepancy review, and approval are deferred. |
| Operational reports and activity | Authentication, approved management operations, attendance, circulation, and inventory checks produce activity records. Sprint 5 adds dashboards, private transaction history, and filtered operational reports and audit trails with PDF, Excel, and current-page printing. |

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
| 1.7 — Conduct Inventory Audits | Verify Head Librarian access and per-copy checks now. Inventory session worklists, recounts, and approvals remain deferred. |
| 1.8 — Generate Reports and Retrieve Records | Verify that reporting, dashboard, own-record history, and activity viewer routes are absent in Sprints 1–4. Verify access, filters, and exports in the Sprint 5 checkout. |

## Finalized access and workflow decisions

- The public kiosk offers attendance on a separate origin. It directs unregistered members to the Head Librarian for registration. Visitors can record attendance without an account.
- QR inputs accept typed values and keyboard-style readers. Member reference and search-based selection provide the manual paths.
- Authorized staff assign existing roles within the Spatie permission matrix. The Head Librarian retains Archive access. Admins may assign and revoke Student Staff within their authorized scope.
- The Head Librarian alone conducts individual copy checks. Session workflows and approvals remain deferred.
- Damaged returns remain out of circulation. Scheduled repairs and their completion screen remain deferred.
- An overdue checkout rejection queues the same notice as the scheduler, which prevents duplicate alerts for the same loan.
- The [active handoff](ACTIVE_HANDOFF.md) records the Sprint 1–4 release and QA. The [deferred Sprint 5 handoff](handoffs/SPRINT_5_HANDOFF.md) records the earlier draft and its prior QA.
