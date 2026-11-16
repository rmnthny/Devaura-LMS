# Agent Guidelines

Before writing any code, migrations, routes, or commands for `Devaura-LMS`, read and follow:
1. `docs/PROJECT_RULES.md` — Tech stack, roles, and out-of-scope items
2. `docs/SCHEMA.md` — Exact MySQL tables, columns, data types, and foreign keys
3. `docs/SPRINT_STATUS.md` — Only build tasks listed under **Current Sprint**
4. `docs/REQUIREMENTS.md` — Functional requirements (R1–R5)
5. `docs/handoffs/` — Check the latest `SPRINT_*_HANDOFF.md` to pick up where the last sprint left off

## Sprint Closing Gate
- **Strict Completion Gate:** Never close a sprint until all tasks in **Current Sprint** are done, `php artisan test` passes, and Kristine's QA fixes are applied.
- **Automatic Sprint Handoff Doc:** Once verified, generate `docs/handoffs/SPRINT_<N>_HANDOFF.md` (with created/updated files, routes, migrations, and test results) and update `docs/SPRINT_STATUS.md`.
- **Suggest New Chat & Stop:** Output a short **Sprint Handoff Summary**, suggest opening a new chat, and stop so the next chat reads `docs/handoffs/SPRINT_<N>_HANDOFF.md` and continues seamlessly.
