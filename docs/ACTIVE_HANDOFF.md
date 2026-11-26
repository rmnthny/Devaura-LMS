# Active Development Handoff

Updated November 26, 2026.

## Resume point

- Branch: `feat/sprint-3-attendance`
- Sprint: Sprint 3, Public Kiosk and Attendance Monitoring
- Status: Implementation is present. Team QA and final handoff are still required before merge.
- Latest automated result: 79 tests passed with 818 assertions after the personnel coverage review.

Always verify the branch and rerun the tests because this document records the last known result and cannot guarantee that later local changes still pass.

## Implemented attendance behavior

- Eligible personnel record their own Time In and Time Out from the protected Attendance page.
- The Head Librarian can provide kiosk coverage without a schedule.
- Admin and Staff coverage requires an assignment matching the current Manila-time shift.
- The kiosk opens only while eligible coverage exists and the staff application's `/up` endpoint on `APP_URL` returns HTTP 200.
- If the staff application is unreachable or unhealthy, the kiosk shows a temporary-unavailability page and rejects submissions, including from an already open form.
- A member visit stays a member visit after a role transfer or promotion. Personnel must record a new Time In before they can provide coverage.
- When the final covering personnel times out, the kiosk closes. Existing open visits remain visible for follow-up.
- Personnel can record assisted attendance from the protected page.
- Maintenance staff can record their own attendance from My Profile and return there after a successful submission.
- Attendance monitoring supports date, role, and open or closed status filters.
- Superadmin and Admin can force an open visit to time out. A reason is required and the action is audited.
- Duplicate Time In keeps the original open visit.
- Locked or archived accounts cannot use public attendance.

## Public kiosk boundary

- Staff application: `http://localhost:8000`
- Public kiosk: `http://127.0.0.1:8001/kiosk`
- Configuration: `KIOSK_URL`
- Opening `/kiosk` on the staff origin redirects to the dedicated kiosk origin.
- Requests for staff pages on the kiosk origin redirect to the kiosk. Unsafe requests to staff endpoints on the kiosk origin are rejected.
- The kiosk uses a separate session-cookie name and sends no-store, referrer, and frame restrictions.
- The kiosk view has no staff navigation.
- The Open Attendance Kiosk button remains disabled until coverage is active and opens the kiosk in a new tab when available.

Application routing cannot erase browser history or close existing staff tabs. The public terminal must use a dedicated browser profile or kiosk-mode browser session that has never signed in to a staff account.

## Public attendance fields

Library members:

- Reference number or stored member QR value
- Time In or Time Out action

Walk-in visitors when timing in:

- Last name
- First name
- Optional middle initial
- Email
- Purpose of visit

Walk-in visitors use the same email to time out. Purpose is recorded per visit and is never requested from library members.

## Important files

- `routes/attendance.php`
- `app/Http/Controllers/AttendanceController.php`
- `app/Services/Attendance.php`
- `app/Http/Middleware/KioskOnly.php`
- `app/Models/AttendanceLog.php`
- `app/Models/WalkInVisitor.php`
- `resources/views/attendance/index.blade.php`
- `resources/views/attendance/kiosk.blade.php`
- `resources/views/attendance/forms.blade.php`
- `config/attendance.php`
- `tests/Feature/SprintThreeAttendanceTest.php`

## Local operation

Use `composer dev` to start the staff server, kiosk server, import listener, and welcome-email worker.

When running services individually:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
php artisan serve --host=127.0.0.1 --port=8001 --tries=1
```

The default staff URL may appear as either `localhost:8000` or `127.0.0.1:8000`. Keep `APP_URL` and `KIOSK_URL` consistent with the addresses used during testing.

## QA results

- The latest QA used a disposable MySQL 8.0.30 instance. The dedicated database `devaura_lms_test` was rebuilt by the feature tests. The attendance table, visit-purpose column, and attendance indexes were confirmed through migration status.
- `php artisan test --compact --filter SprintThreeAttendanceTest` passed 10 tests with 165 assertions. `php artisan test --compact` passed 79 tests with 818 assertions.
- Live HTTP checks on ports 8000 and 8001 confirmed the staff-to-kiosk redirect, public kiosk and local CSS, staff-route rejection, session isolation, cache and frame headers, and CSRF rejection for a tokenless kiosk request.
- Live outage testing returned HTTP 503 and hid the public forms while port 8000 was down. The forms reopened after the staff server recovered and existing coverage was confirmed.
- Browser checks confirmed Back and Refresh stay on the kiosk in the public session. At 390 pixels wide, the forms stacked without horizontal scrolling and the visitor Time In form opened using the keyboard.
- Composer validation and platform checks passed. PHP lint passed across 138 project files, and Laravel Pint passed for the Sprint 3 PHP files.
- The attendance workflow checks used synthetic test records. Live attendance records were untouched.

## Remaining team QA

- Review the staff screens and complete an end-to-end walkthrough on the dedicated kiosk device, including the public browser profile.
- Record Kristine's QA decision and complete the merge gate.

## Deferred work

- Camera-based QR scanning
- Public book-catalog lookup on the kiosk
- Circulation and overdue workflows
- Dashboard and formal reports
- Production web-server configuration for a dedicated kiosk hostname
