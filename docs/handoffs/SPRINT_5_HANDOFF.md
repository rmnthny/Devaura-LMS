# Sprint 5 Handoff — Dashboard, Reports and System QA

Updated November 27, 2026. This development checkout starts at the integrated Sprint 3–4 UI commit `2e986965d2d9ee84874a8f930f2bae70ac278710`. The published `feat/sprint-4-backend` branch stays at `af3eebabe45d7d1a0ee2a74995d6e6179d6e03c0`. Sprint 5 development is on `feat/sprint-5-UI-backend`.

The process diagrams, use cases, DFDs 1.0–1.8, and v3 ERD are mapped in the [diagram crosswalk](../DIAGRAMS.md). The published release covers Sprints 1–4. Inventory sessions, approvals, and repair scheduling and completion remain deferred. Their drafts and the superseded dashboard and reporting copies are available in Git history at `2e98696`.

## Integration sequence

1. Fetched the two Sprint 4 refs and confirmed the backend tip was an ancestor of the UI tip. The comparison returned zero backend-only and five UI-only commits. Fast-forwarded a detached worktree started from the backend tip and carried forward the three local handoff edits.
2. Verified the combined baseline with `php artisan test --compact` on `devaura_lms_test`. All 94 tests and 1,051 assertions passed before feature changes.
3. Restored the dashboard and personal circulation history, filtered operational reports, PDF and Excel exports, printing, and the audit viewer using the integrated workspace layout and existing permissions.
4. For these four Sprint 5 tasks, inventory reports use the existing Head Librarian copy checks and catalog status. Normal and damaged returns and loss reporting use the Sprint 4 flow. Inventory sessions, approval, repair scheduling, and repair completion are recorded in the broader design and remain deferred for a separate scope decision.
5. The report and dashboard routes use existing tables and grants. Run the regular migrations and synchronize predefined permissions when setting up the prototype.

## Dashboard metrics

The shared dashboard is available to the Head Librarian and Admin. Staff, Students and Faculty see their own borrowing KPIs and transaction history. The server scopes personal history by the signed-in account. Maintenance and Visitor roles have no dashboard or history access.

| KPI or chart | Source and period |
| --- | --- |
| Available copies | Current active `book` records with status `Available`. Archived copies are excluded. |
| Open loans | Current `borrow_transaction` rows with no return date and status Borrowed or Overdue. The personal count also filters by borrower account. |
| Overdue loans | Open loans marked Overdue or with a due date before today in Manila. The personal count also filters by borrower account. Loans due today remain eligible unless another overdue loan exists. |
| Visits today | `attendance_log` time-ins starting at Manila midnight through, excluding, the following midnight. The app compares the equivalent UTC timestamps. Each visit counts once. |
| Attendance and circulation charts | Seven Manila calendar dates including today. Attendance counts time-ins. Checkouts count `borrow_date` and returns count `return_date` on Returned transactions, including damaged returns. Losses count as checkouts on their original date. Dates without activity show zero. |

## Reports and audit access

| Screen | Access and data |
| --- | --- |
| Attendance | Head Librarian and Admin. Manila time-in date and recorded role. The active schema has no grade-level field. |
| Circulation | Head Librarian and Admin. Choose checkout date, return date, or the due date of a loan that is currently overdue. All active loans is a current snapshot without a checkout-date cutoff. The return filter includes loans checked out before the selected period. |
| Inventory Checks | Head Librarian. Date-filtered individual copy checks from `activity_log`, with previous and observed condition, Lost or Damaged discrepancies, and a snapshot of the current active catalog by status. Book title, genre, and publication date filters apply to the checks and the snapshot. |
| Audit Trail | Head Librarian and Admin. Newest entries first, filtered by Manila date and activity area. Shows the acting account when retained, action, and Manila timestamp. |

Reports default to the last 30 Manila dates including today. The selected start and end dates are inclusive. The screen, PDF, and Excel use the same validated filters, query, and matching record counts. Export labels omit the Manila suffix where applicable. For example, Attendance shows `Time in (Manila)` on screen and `Time in` in the exports. Timezone calculations are shared. The screen paginates and exports include up to 1,000 matching rows. Narrow the filters for a larger result. Audit Trail applies the same Manila date, activity area, exact event, literal action text, and exact circulation reference filters to the viewer and PDF and Excel downloads. Print uses the current filtered page.

## Dropdown contract for frontend handoff

Backend options are ready for both Operational Reports and Audit Trail. Wiring all listed dropdown fields in the existing screens remains a frontend task. Keep `from` and `through` as date inputs. The existing authenticated GET routes supply Blade view data for the dropdowns. Use the option payloads for choices and the `filters` payload for selected values.

### Audit Trail

`GET /audit-trail` (`audit.index`) is available to Head Librarian and Admin. `auditFilterOptions` contains the following sorted lists of unique strings observed in the selected date range. It uses the date range independently of the other selected filters.

| Option key and query parameter | Label | Matching |
| --- | --- | --- |
| `area` | Activity area | Exact stored area, such as `circulation`. |
| `event` | Event | Exact stored event, such as `checked_out` or `returned`. Entries without an event are included when this filter is empty. |
| `action` | Action contains | Literal substring of the stored action description. |
| `transaction` | Transaction reference | Exact circulation reference. The server accepts lowercase input. |

Use each list item as the submitted value. Labels can be formatted for readability while the raw value is preserved. Example `/audit-trail?from=2026-11-01&through=2026-11-27&area=circulation&event=returned`.

### Operational Reports

`GET /reports` (`reports.index`) is available to Head Librarian and Admin. `operationalFilterOptions.type` is a map from submitted value to display label. It includes `attendance` and `circulation` for both roles and `inventory` only for the Head Librarian with inventory permission. The default is `attendance`.

| Report type | Option key and query parameter | Shape and matching |
| --- | --- | --- |
| Attendance | `role` | Map of role keys to labels. The stored role at time-in is used. |
| Circulation | `circulation_activity` | Map with `checkout` (default), `return`, `overdue`, and `active`. The active option is a current snapshot and ignores the date range for row matching. |
| Inventory Checks | `book_title` | List of book titles, including retained archived copies. The title filter matches literal text contained in the title. |
| Inventory Checks | `genre_id` | Map of genre ID to label. The ID is the submitted value. |
| Inventory Checks | `published_on` | List of publication dates in `YYYY-MM-DD`. The date is an exact match. |

Only option keys for the selected report type are present. On a type change, preserve dates and clear the other type's selections. Example `/reports?type=circulation&circulation_activity=return&from=2026-11-01&through=2026-11-27`.

### Shared behavior

- Submit dates as `YYYY-MM-DD`. Both dates are inclusive and use the library's local calendar. If omitted, they default to the last 30 calendar dates including today. If supplied, both date values must be valid and `through` must be on or after `from`.
- Optional filters can be cleared by omitting them. Omit `circulation_activity` to restore its default.
- When options are empty or a bookmarked selection is absent from the option list, show the empty state and retain any active filter value so it can be cleared.
- Keep the applied query values when adding `format=pdf` or `format=xlsx` to the same route. Downloads include all matches up to 1,000 rows. Browser printing uses the current filtered page. Changing any filter should reset pagination to the first page.
- The server enforces report access for previews and downloads. Direct inventory requests by Admin return HTTP 403. Invalid filters return HTTP 422 for JSON requests or a validation redirect for browser requests.

## Startup and defense rehearsal

Use the [defense walkthrough](../SPRINT_5_DEMO.md) to install dependencies, start MySQL, configure the dedicated demo schema, seed synthetic records, and sign in on the staff and kiosk origins. The demo seeder is opt-in and checks the local environment, schema name, and array mail configuration.

For an existing development schema, run `php artisan migrate` and `php artisan db:seed --class=RolePermissionSeeder`. Sprint 5 adds no required tables or environment variables. Continue to configure `APP_URL`, `KIOSK_URL`, the MySQL connection, database queues, and local mail settings. Start the staff and kiosk servers on ports 8000 and 8001, the default and `member-mail` queue workers, and `php artisan schedule:work`. Use a separate browser profile on the kiosk device.

## Integrated checkout verification history

The earlier isolated QA runs used a disposable MySQL 8.0.30 instance on port 13317 with `devaura_lms_test`. The HTTP walkthrough used the separate `devaura_lms_demo` schema on that instance and the array mailer.

| Check | Result |
| --- | --- |
| Integrated baseline `php artisan test --compact` before Sprint 5 changes | 94 passed, 1,051 assertions |
| `php artisan test --compact --filter SprintFiveReportsTest` | Initial report check after the workbook fix, 5 passed and 80 assertions. |
| `php artisan test --compact --filter 'AuthAndOtpTest\|RbacTest\|LibraryBrowserWorkflowTest\|SprintTwoInventoryTest\|SprintThreeAttendanceTest\|SprintFourCirculationTest\|SprintFiveDashboardTest\|SprintFiveReportsTest\|ReleaseScopeTest'` | 59 passed, 790 assertions after the PDF font fix. |
| `php artisan test --compact` after the initial PDF font fix | 102 passed, 1,172 assertions. |
| Dependency, source and route checks | Composer platform requirements, PHP lint, and Laravel Pint on changed PHP files passed. A temporary Laravel route cache included the four Sprint 5 routes. |
| `git diff --check` | Passed. New source and documentation files also passed the trailing whitespace check. |
| Dashboard in Chrome | 1440×900 and 375×812 with no horizontal overflow. Four seeded copies were available, one loan was open and overdue, and zero visits were recorded today before the walkthrough. Chart values matched the dated fixtures, including empty days. |
| Local database, migrations, permissions, scheduler and queues | Connection and migrations passed. Running the demo seeder twice kept four accounts, five copies, two historical visits and two loans. The minute scheduler changed the synthetic open loan to Overdue. The member-mail worker processed the queued message with the array mailer, and repeating the reminder scan produced no duplicate. |
| `composer --no-interaction dev` | Passed with `concurrently` available in the npm cache. Both HTTP origins returned 200, and the default and member-mail worker processes started. The individually documented startup commands passed independently. |
| Integrated browser walkthrough | Real sign-in, catalog search, Head Librarian coverage, member and visitor attendance, duplicate time-in, checkout, normal and damaged returns, loss, overdue enforcement and clearance, inbox delivery, and a recorded inventory discrepancy. Verified the dashboard, private history, audit actor attribution, Assigned Teacher access, and direct Student report and audit denial. |
| HTTP origin checks | Port 8000 redirected `GET /kiosk` with 302 to the dedicated kiosk. On port 8001, `GET /login` and `GET /dashboard` redirected with 302 to `/kiosk`, a staff checkout POST returned 404, and a tokenless kiosk POST returned 419. |
| Live report comparison | Opened generated PDF and Excel files from seven filter combinations. Compared totals, dates, headers, rows and empty results with the screen. Cases covered attendance by role, empty attendance, checkouts, returns, overdue, and empty and populated inventory reports. |

## Defect log

| Defect | Reproduction | Verification after fix |
| --- | --- | --- |
| Excel headers and frozen pane shifted by one row | Export an Attendance workbook and compare the styled and frozen header position with the headings after the report summary. The Excel writer dropped a fully empty separator row. | Emit the separator explicitly. The reopened workbook puts the headings at row 7 and freezes through that row. The report test and opened demo exports passed. |
| PDF font fallback changed accented text | Export a circulation report with the title `Łódź Library Guide` and reopen the PDF. Dompdf used Helvetica and failed to preserve the full title. | Declare DejaVu Sans explicitly and embed the used glyphs. The rerender preserved the exact title and passed visual and text extraction checks. |
| Composer launcher on a fresh offline cache | Run `composer dev` with npm offline and no cached `concurrently`. The database check passes, then npm cannot start the child processes. | Verified the individual startup commands. Cached the npm dependency in the disposable QA environment and rechecked the Composer launcher, both listeners, and the mail worker. |

## Finalized flowchart follow-up

The nine-flow comparison is in the [diagram crosswalk](../DIAGRAMS.md). Audit Trail downloads use the viewer filters and permission. Printing uses the current page. Batch book and member QR sheets record the requesting actor and the selected record IDs. An archived book in a stale QR selection rejects the batch.

- Focused suite for reports, inventory and member management passed 24 tests with 353 assertions.
- The full suite passed 102 tests with 1,204 assertions on the dedicated devaura_lms_test schema. The whitespace check passed.
- Real HTTP requests to both audit download formats on port 8000 redirected guests to login. An audit download URL on port 8001 redirected to the kiosk, which returned HTTP 200.
- Physical printing remains part of team acceptance.

## Final repository cleanup

- Removed eight verified backend files. These were the `BookAuthor` and `BookPublisher` pivot models and the `AttendanceLogFactory`, `BookAuthorFactory`, `BookPublisherFactory`, `PersonnelScheduleFactory`, `ScheduleDayFactory`, and `SchedulePeriodFactory` fixtures. Active pivot tables, relationships, and schema migrations are still in use.
- Removed the root `deferred/` directory, including superseded dashboard and report copies and the remaining inventory-session and repair drafts. Those workflows remain deferred. Their earlier source is recoverable from Git commit `2e98696`. The 14 files still present before the final directory removal were also backed up locally, including the edited archive README.
- Retained `LibraryVisitor.php` for compatibility and kept `public/js/auth.js` and `public/js/theme.js` for frontend review. The active UI was verified unchanged during cleanup.

## Latest recorded verification

After removal of `deferred/`, `php artisan test --compact` passed **103 tests with 1,242 assertions** on `devaura_lms_test` using the local MySQL instance on port `3307`. `git diff --check` passed. This is the latest full-suite result for the cleaned checkout.

## Pending frontend integration

- Wire Operational Reports dropdowns from `operationalFilterOptions`. This covers report type and the applicable attendance, circulation, or inventory filters.
- Wire Audit Trail dropdowns from `auditFilterOptions`. This covers Activity Area, Event, Action Contains, and Transaction Reference.
- Follow the dropdown contract above for permissions, option values, date inputs, and preserving applied filters across preview, PDF, Excel, and current-page print. Verify the integrated screens before team sign-off.

## Team checks before acceptance

- Kristine and the team should record the Sprint 3–5 sign-off and run the team Postman or Apidog collection.
- Rehearse on the actual defense machine and kiosk browser profile. Verify physical printing and real SMTP on the team's approved environment.
- Verify the target MySQL 8.4+ deployment and run the role and migration refresh there before rollout.

## Earlier draft workflow reference

This section records the archived workflow design. Use the active report and dropdown contract above for dashboard, history, reports, and Audit Trail. Inventory sessions, approvals, repair scheduling, and repair completion remain outside the current checkout.

- **Kiosk:** Members enter a registered reference or QR value. Walk-in visitors record attendance without an account. The kiosk refers unregistered members to the Head Librarian and presents attendance on its separate origin.
- **Access:** Personnel assignment selects predefined roles. Existing restrictions on provisioning, Staff assignment, and archival remain in effect. Only the Head Librarian may conduct, approve, and export inventory audits.
- **Inventory:** Start an audit, verify or recount each physical copy, filter missing or damaged discrepancies, and approve the session. Approval requires each outstanding copy to be checked or matched to an open loan. Matching preserves the transaction reference in the audit item. An audit records the evidence. Individual copy checks and repair outcomes update catalog availability.
- **Circulation:** Use QR or manual search and selection. An overdue checkout rejection queues the same deduplicated overdue notice as the scheduler. A damaged return can carry an optional Manila-date repair schedule. Record the repair outcome before making that copy available again.
- **Dashboard and records:** Staff and members can view their own circulation records. Reporting roles also see operational metrics and seven-day Manila-date visit and checkout trends. Audit and report permissions are enforced on the corresponding download and review routes.
- **Reports:** Filter attendance, circulation, inventory checks, or activity by date. Activity also supports an exact area filter. Use the PDF or Excel export for the filtered records or print the current page. The export limit is 1,000 records per request.

### Draft migrations and services

- `2026_11_26_120000_add_circulation_request_and_alert_delivery_columns` remains the Sprint 4 migration.
- `2026_11_26_130000_create_inventory_audit_tables` adds `inventory_audit` and `inventory_audit_item` with foreign keys and one verification row per copy per session.
- `2026_11_26_130100_create_book_repair_table` adds repair dates and outcomes linked to the original loan.

The draft inventory-session and repair migrations are available in Git history. The earlier draft QA used a disposable database.

## Historical draft verification

| Command | Result |
| --- | --- |
| `php artisan test --compact --filter FinalDiagramWorkflowTest` | 3 passed, 50 assertions |
| `php artisan test --compact --filter 'SprintFourCirculationTest\|SprintThreeAttendanceTest\|SprintTwoInventoryTest\|RbacTest'` | 38 passed, 531 assertions |
| `php artisan test --compact` | 92 passed, 1,027 assertions |
| `git diff --check` and PHP syntax checks | Passed |

Automated tests used synthetic records and a disposable MySQL 8.0.30 instance with `devaura_lms_test`. Isolated HTTP checks on ports 8000 and 8001 passed for role-based access, staff checkout and return, simultaneous checkout, kiosk isolation, the dashboard, PDF and Excel downloads, activity viewing, and the Head Librarian’s audit worklist.

Those results are historical. The current verification and remaining sign-off apply to the integrated Sprint 5 checkout above.
