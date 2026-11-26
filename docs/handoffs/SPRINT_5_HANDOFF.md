# Deferred Sprint 5 Workflows

Sprint 5 is outside the Sprint 1–4 release. The [preserved source](../../deferred/sprint-5/README.md) is outside Laravel discovery. The workflow descriptions and verification below record the earlier draft.

November 26, 2026. The finalized process diagrams, use cases, DFDs 1.0–1.8, and v3 ERD are mapped in the [diagram crosswalk](../DIAGRAMS.md). The draft remains outside the active release. See [deferred source](../../deferred/sprint-5/README.md) for the approval boundary and restoration notes.

## Draft workflow reference

- **Kiosk:** Members enter a registered reference or QR value. Walk-in visitors record attendance without an account. The kiosk refers unregistered members to the Head Librarian and presents attendance on its separate origin.
- **Access:** Personnel assignment selects predefined roles. Existing restrictions on provisioning, Staff assignment, and archival remain in effect. Only the Head Librarian may conduct, approve, and export inventory audits.
- **Inventory:** Start an audit, verify or recount each physical copy, filter missing or damaged discrepancies, and approve the session. Approval requires each outstanding copy to be checked or matched to an open loan. Matching preserves the transaction reference in the audit item. An audit records the evidence. Individual copy checks and repair outcomes update catalog availability.
- **Circulation:** Use QR or manual search and selection. An overdue checkout rejection queues the same deduplicated overdue notice as the scheduler. A damaged return can carry an optional Manila-date repair schedule. Record the repair outcome before making that copy available again.
- **Dashboard and records:** Staff and members can view their own circulation records. Reporting roles also see operational metrics and seven-day Manila-date visit and checkout trends. Audit and report permissions are enforced on the corresponding download and review routes.
- **Reports:** Filter attendance, circulation, inventory checks, or activity by date. Activity also supports an exact area filter. Use the PDF or Excel export for the filtered records or print the current page. The export limit is 1,000 records per request.

## Migrations and local services

- `2026_11_26_120000_add_circulation_request_and_alert_delivery_columns` remains the Sprint 4 migration.
- `2026_11_26_130000_create_inventory_audit_tables` adds `inventory_audit` and `inventory_audit_item` with foreign keys and one verification row per copy per session.
- `2026_11_26_130100_create_book_repair_table` adds repair dates and outcomes linked to the original loan.

After Sprint 5 is approved and its integration is restored, apply its migrations and synchronize the predefined permission matrix before opening its screens in Laragon. This work used a disposable database and left the existing Laragon database intact.

```powershell
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
composer dev
```

Run `php artisan schedule:work` alongside the dev services for due and overdue notices. `composer dev` includes the `member-mail` worker. No new environment variables are required.

## Historical draft verification

| Command | Result |
| --- | --- |
| `php artisan test --compact --filter FinalDiagramWorkflowTest` | 3 passed, 50 assertions |
| `php artisan test --compact --filter 'SprintFourCirculationTest\|SprintThreeAttendanceTest\|SprintTwoInventoryTest\|RbacTest'` | 38 passed, 531 assertions |
| `php artisan test --compact` | 92 passed, 1,027 assertions |
| `git diff --check` and PHP syntax checks | Passed |

Automated tests used synthetic records and a disposable MySQL 8.0.30 instance with `devaura_lms_test`. Isolated HTTP checks on ports 8000 and 8001 passed for role-based access, staff checkout and return, simultaneous checkout, kiosk isolation, the dashboard, PDF and Excel downloads, activity viewing, and the Head Librarian’s audit worklist.

Team acceptance still includes the dedicated kiosk device, a physical print check, real SMTP delivery, and Postman or Apidog verification. The live migration and permission refresh are pending.
