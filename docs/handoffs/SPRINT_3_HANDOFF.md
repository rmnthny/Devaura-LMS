# Sprint 3 Handoff — Public Kiosk and Attendance Monitoring

Status: Working handoff pending team QA and merge.

The authoritative live summary is `docs/ACTIVE_HANDOFF.md`. Update both files when Sprint 3 behavior changes.

## Delivered

- Personnel Time In and Time Out
- Public kiosk gated by personnel coverage and a successful staff `/up` response at `APP_URL`
- Dedicated kiosk origin and middleware boundary
- Member and visitor public attendance
- Assisted staff attendance entry
- Attendance monitoring and filters
- Audited Force Time Out
- Sprint 3 feature tests

## Configuration

- `KIOSK_URL=http://127.0.0.1:8001`
- `LIBRARY_OPENS_AT=08:00`
- `LIBRARY_AFTERNOON_AT=12:00`
- `LIBRARY_CLOSES_AT=17:00`
- Attendance schedules, date filters, and display use `Asia/Manila`. The application and stored attendance timestamps use UTC.

Confirm official library hours before deployment.

## Verification

- `php artisan test --compact --filter SprintThreeAttendanceTest` passed 10 tests and 165 assertions on the dedicated MySQL database `devaura_lms_test`.
- `php artisan test --compact` passed 79 tests and 818 assertions. The attendance table and both Sprint 3 migrations ran on the rebuilt test database.
- HTTP checks on staff port 8000 and kiosk port 8001 covered redirects, session and cache headers, local assets, CSRF, and blocked staff requests.
- Browser checks covered Back, Refresh, and keyboard activation of the visitor form on a 390-pixel viewport.
- `git diff --check` passed.

A verified role-transfer regression ensures existing member visits cannot become staff coverage. The recheck used a disposable MySQL 8.0.30 instance and temporary localhost servers.

The Maintenance profile now shows Time In and Time Out confirmations after a successful submission. Regression tests cover that flow and the public member and visitor workflows.

## Limitations and deferred items

- A separate kiosk browser profile is required to isolate browser tabs and history from staff use.
- Camera scanning remains deferred. The kiosk accepts typed or scanner-entered QR values and reference numbers.
- Public catalog search is deferred.
- Production routing for a dedicated kiosk hostname remains a deployment task.

## Merge gate

- Kristine's functional and responsive QA
- Postman or Apidog endpoint verification
- Clean-database migration check
- Complete automated test suite
- `git diff --check`
- Documentation updated with final test totals and merge commit
