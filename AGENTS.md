# Devaura-LMS AI Working Agreement

This file is the first source of repository-specific instructions for Codex, Antigravity, Claude, and other coding assistants.

## Start every task here

Before proposing or changing code:

1. Read `docs/ACTIVE_HANDOFF.md`, `docs/SPRINT_STATUS.md`, and `docs/PROJECT_RULES.md`.
2. Inspect the current Git branch and working tree. Preserve changes that already exist.
3. For circulation or notifications, also read [the Sprint 4 handoff](docs/handoffs/SPRINT_4_HANDOFF.md).
4. Inspect the relevant routes, controllers, services, models, migrations, views, and tests. The repository is the source of truth for implemented behavior.
5. Compare the request with the confirmed decisions below. Ask only when a decision would materially change the system.
6. Work only within the active sprint unless the user explicitly changes scope.

Do not reconstruct requirements from old chat messages, screenshots, or diagrams when a newer decision is recorded in these documents.

## Current work

- Active local development branch: `feat/sprint-4-backend`
- Current release scope: Sprints 1–4. Sprint 5 source is deferred and outside Laravel discovery.
- Current state and resume instructions: `docs/ACTIVE_HANDOFF.md`
- Sprint checklist: `docs/SPRINT_STATUS.md`

If the checked-out branch differs, report it before making branch-specific changes. Do not create, delete, merge, commit, or push a branch unless the user asks.

## Confirmed product decisions

- Email is the only login identifier.
- Roles and permissions are predefined through Spatie. There is no customizable role-permission editor.
- Only one `superadmin` may exist. The role belongs to a Faculty account and transfer demotes the previous holder to `faculty`.
- `admin` is Faculty only. `staff` is Student only.
- The Head Librarian creates Student and Faculty accounts manually or through bulk import.
- The Head Librarian alone can access Archive and manage permanent deletion where allowed.
- Admins may assign or revoke the Staff role for Students and manage Staff schedules. The Head Librarian alone conducts inventory checks. Reports and the audit-trail viewer are deferred until Sprint 5.
- Public attendance accepts Student and Faculty member identifiers. Visitors provide name, email, and purpose when timing in. Purpose is visitor-only.
- Camera QR scanning is deferred. QR values or reference numbers are entered manually.
- The public kiosk opens only while eligible library personnel coverage is active.
- The public kiosk uses a dedicated origin. Local default is `http://127.0.0.1:8001/kiosk`. Staff pages use `http://localhost:8000`.
- A public kiosk device must use a separate browser profile so staff tabs, login cookies, and browser history are unavailable to visitors.

## Implementation boundaries

- Reuse Laravel validation, authorization, Eloquent models, services, and Spatie permissions already in the repository.
- Enforce access in routes and backend code. Hiding a button is not authorization.
- Keep Sprint 5 routes, controllers, views, migrations, and tests out of active discovery until the sprint is approved.
- Keep public kiosk responses free of staff navigation and authenticated account information.
- Preserve CSRF protection, rate limits, active-account checks, audit logging, transactions, and row or application locks where already used.
- Keep offline frontend assets in `public/vendor`. Do not introduce external CDNs.
- Do not add camera scanner libraries until the camera-scanning feature is scheduled.
- Do not add fines, payments, reservations, e-books, RFID, biometrics, or external school-system integrations.
- Do not edit unrelated files or add generated, temporary, log, credential, or IDE files.
- Add comments only when they explain a non-obvious rule or constraint. Use short, natural language.

## Verification gate

For a code change:

1. Run focused tests for the affected module.
2. Run the complete `php artisan test --compact` suite against the dedicated `devaura_lms_test` database.
3. Run `git diff --check`.
4. For kiosk or route changes, verify real HTTP behavior on staff port `8000` and kiosk port `8001` without changing live attendance data.
5. Report the exact tests run, results, assumptions, and anything that remains unverified.

Never run the automated test suite against `devaura_lms`. The test bootstrap rejects any database other than `devaura_lms_test`.

## Sprint handoff rule

Before starting a new sprint:

1. Complete the current sprint QA gate.
2. Update `docs/SPRINT_STATUS.md` and `docs/ACTIVE_HANDOFF.md`.
3. Create or update `docs/handoffs/SPRINT_<N>_HANDOFF.md`.
4. Record the branch, migrations, services, environment variables, tests, deferred items, and known limitations.
5. Start a fresh AI chat and instruct it to read `AGENTS.md` first.
