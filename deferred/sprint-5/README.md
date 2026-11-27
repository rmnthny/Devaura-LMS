# Deferred Sprint 5 source

These files preserve the local Sprint 5 drafts. Their paths are relative to the repository root so the team can review and restore them in a future Sprint 5 checkout. Laravel only loads the active routes, PSR-4 application source, views, migrations, and tests from their usual directories.

This folder includes the dashboard, member transaction history, report exports, audit viewer, inventory audit sessions, and scheduled repairs. Sprint 1–4 still include individual Head Librarian copy checks, archived history protection, and damage or loss recording on returns.

When Sprint 5 is approved, restore the source in its corresponding application paths and reconcile it with the then-current application. Register the reporting and supplemental routes, restore the appropriate navigation and return repair input, and connect repair scheduling to the existing locked circulation transaction. Review the saved feature tests against the target sprint's authorization policy before registering the migrations. The existing archive guards preserve rows if the earlier draft schema was already applied locally.

The [sprint status](../../docs/SPRINT_STATUS.md) tracks the release boundary. The historical Sprint 5 QA results are in [the deferred implementation handoff](../../docs/handoffs/SPRINT_5_HANDOFF.md).
