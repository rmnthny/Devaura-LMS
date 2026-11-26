# Sprint 4 Handoff — Book Circulation and Notifications

Updated November 27, 2026. Local implementation is in the approved checkout on `feat/sprint-4-backend` at current base `f946aee`. Sprint 3 team sign-off and merge remain open.

## Circulation

- Superadmin, Admin, and Staff use Book Borrowing and Book Returns. Enter the member’s LRN, employee number, or QR value. For the book, enter the QR value or search and select the exact copy. Copy numbers distinguish identical titles in search results. Both paths use the same server-side checks and replay protection. The checkout records `borrower_id` and `borrow_processed_by` as account IDs and generates a unique `TRX-YYYYMMDD-XXXX` reference.
- Authorized circulation staff can search for an eligible member by name or reference number and select an available physical copy by title or ISBN. The return screen searches open loans by transaction reference, book, or borrower. Checkout and return still validate the selected records at submission.
- The system assigns the due date three calendar days after checkout in `Asia/Manila`. A loan is due through that date and overdue at the start of the next Manila calendar day. Existing loan dates remain valid.
- Checkout locks the copy and borrower records, updates the loan and copy in one transaction, and uses a unique request UUID to recognize a replay. The availability check also considers existing open loans.
- Returns use the copy QR and the original transaction reference. An old return request cannot affect a later loan. Normal and damaged returns record the return date and processing account. A reported loss closes the loan, updates the copy to `Lost`, and records the processor in the audit log. Return and loss events include the resulting copy status and whether the loan was overdue in Manila time.
- An unresolved overdue loan blocks further checkout even when the scheduler is offline. Staff can process returns for overdue loans. Returning the last overdue item restores eligibility. Staff cannot process their own checkout or return.

## Notifications

- Changed profile, password, membership, status, and role details appear in the account's notification inbox and queue an email. Existing OTP and welcome mail continue to use their established workflows.
- A checkout creates a pending-item notification. The reminder command creates one due alert on the due date and one overdue alert from the following day if the loan remains open. The command also persists the `Overdue` transaction status.
- The database notification and queued job commit with their source transaction. The scheduler reuses the same ID for each loan alert. The worker claims each email before attempting delivery and checks whether loan alerts are still current.
- SMTP does not provide an idempotency guarantee. To prevent duplicate email after an uncertain result, the worker does not resend a claimed delivery. `email_attempted_at` without `email_sent_at` identifies an unresolved delivery attempt. The inbox still retains the notice.

## Migration and local commands

`2026_11_26_120000_add_circulation_request_and_alert_delivery_columns` adds the nullable checkout request UUID and loan indexes to `borrow_transaction` and email delivery timestamps to `notifications`. Existing loans remain usable. Apply the migration before opening the Sprint 4 screens.

```powershell
php artisan migrate
php artisan queue:work member-mail --sleep=1
php artisan schedule:work
```

`composer dev` already starts the mail worker. Start the scheduler separately. Use `php artisan library:circulation-notifications` to run a reminder scan once. No new environment variables are required. Keep the existing Laragon MySQL port `3307` and the configured staff and kiosk origins.

## Sprint 4 task evidence

| Notion task shown in the supplied screenshots | Local implementation and acceptance |
| --- | --- |
| Implement QR-Assisted Book Borrowing and Returning | QR and search-and-select routes use the exact physical copy and open loan. Checkout and return update the loan, copy, and audit together. |
| Implement Overdue Tracking and Borrowing Restrictions | The Manila due date is assigned server-side. Checkout is blocked while an overdue item remains outstanding. A return clears that restriction when no overdue items remain. |
| Develop Account, Pending-Book, and Due/Overdue Notifications | Account notices, checkout confirmations, and due and overdue alerts use the inbox and existing queued delivery. The existing SMTP transport accepted a synthetic checkout email. The redesigned checkout notice was verified in Gmail on November 27. |
| Validate Circulation Workflow and Refine Notifications | The return screen explains stale or missing records and requires an explicit outcome. Notification text distinguishes checked out, due today, and overdue. |
| Test Book Circulation and Notification Management | The focused and full test results below cover circulation and release scope. The live checkout SMTP smoke check passed. Team acceptance remains open. |

## Earlier verification

| Check | Result |
| --- | --- |
| `php artisan test --compact --filter SprintFourCirculationTest` | 10 passed, 158 assertions |
| `php artisan test --compact --filter SprintThreeAttendanceTest` | 10 passed, 165 assertions |
| `php artisan test --compact --filter 'SprintFourCirculationTest\|ReleaseScopeTest'` | 14 passed, 202 assertions |
| `php artisan test --compact --filter 'SprintFourCirculationTest\|SprintThreeAttendanceTest\|SprintTwoInventoryTest\|RbacTest'` | 38 passed, 531 assertions |
| `php artisan test --compact` | 93 passed, 1,021 assertions |
| Cached-route release verification | 2 passed, 34 assertions. HTTP returned `404` for deferred Sprint 5 pages, downloads, audit-session writes, and repair writes. |
| `git diff --check` | Passed |
| `php artisan migrate:fresh --force --no-interaction --quiet` on the disposable database | Passed |
| `php artisan schedule:list --no-ansi` | Confirmed the reminder runs every minute |

The tests and HTTP writes used synthetic records and a disposable MySQL 8.0.30 instance with `devaura_lms_test` on port `13317`. Feature tests used fake mail. HTTP smoke checks used the array mailer.

The current release smoke used a separately generated route cache. Existing servers occupied `127.0.0.1:8000` and `127.0.0.1:8001`. The isolated HTTP run used `127.0.0.2` on the same ports. Protected member/copy search, selection, and open-loan search and selection returned `200`. Checkout using selected records returned `201`, a checkout replay returned `200`, and return and return replay returned `200`. Authentication, authorization, and CSRF checks returned `401`, `403`, and `419`. Concurrent checkout requests on two PHP processes returned `201` and `422`. The member inbox returned `200` and its read action redirected with `302`. Kiosk checks covered the `302` redirect, public page and offline CSS, separate session cookie, no-store cache header, and the `404` response to a staff write on port `8001`.

On November 26, the existing SMTP transport accepted one synthetic checkout email for transaction `TRX-20261126-EHPW`. The isolated mail queue drained and Laravel recorded the delivery timestamp. The test used a disposable database and left the local mail worker and scheduler untouched. The additive Sprint 4 migration was also applied to the local Laragon database. `library:check-database` passed, staff login and the temporary local kiosk both returned HTTP 200, and the kiosk origin redirected the staff login request with HTTP 302.

The checkout test email reached the Gmail inbox. New Sprint 4 notices use a shared formal HTML and plain-text template through the same SMTP transport. Checkout, due, and overdue notices include the book title, transaction reference, checkout date, and Manila due date. Account and password-change notices include the local timestamp and whom to contact.

After the template update, the focused circulation and release scope tests passed (14 tests, 218 assertions), and the full suite passed (93 tests, 1,037 assertions). Laravel's in-memory mail transport generated both MIME alternatives. On November 27, 2026, one synthetic checkout email sent through the configured SMTP transport landed in Gmail Primary at 12:07 AM Manila time. The desktop Gmail view showed the branded HTML and the full circulation details for TRX-QA-20261127.

## November 27, 2026 QA

- The return page keeps QR lookup and open-loan search visible. Selecting a result retains the search filter. The checkout form requires a QR when a copy has not been selected.
- A conflicting loss or physical-return submission is rejected after the borrowing record closes. Matching retries still succeed without rewriting the original circulation record.
- The scheduler checks for existing notices in batches and skips row locks for loans whose status and notice are already current. A previously queued overdue notice still allows the scheduler to set the loan status to Overdue. The worker skips expired due notices and verifies the loan belongs to the intended recipient.
- Focused circulation and release scope tests passed with 15 tests and 232 assertions. The complete suite on devaura_lms_test passed with 94 tests and 1,051 assertions. The reminder is scheduled every minute, and the whitespace check passed.
- Read-only local HTTP checks confirmed the staff login and kiosk load. Unauthenticated checkout, return, and inbox requests on port 8000 redirect to staff login. Checkout and return requests on port 8001 redirect to the kiosk.

## Remaining team QA

The [diagram crosswalk](../DIAGRAMS.md) distinguishes the finalized design from the Sprint 1–4 release boundary.

- Kristine and the team should review the borrowing and return screens, reminder cadence, and staff permission matrix. Record Postman or Apidog verification and the dedicated kiosk device walkthrough.
- Review the delivery-failure procedure before enabling the live mail worker for circulation alerts.
- Record Sprint 3 and Sprint 4 sign-off before any merge.

Camera scanning, repair scheduling, fines, payments, reservations, e-books, and Sprint 5 reporting remain deferred.
