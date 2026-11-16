# Project Guidelines

Before writing any code, migrations, routes, or commands for `Devaura-LMS`, read and follow:
1. `docs/PROJECT_RULES.md` — Tech stack, roles, and out-of-scope items
2. `docs/SCHEMA.md` — Exact MySQL tables, columns, data types, and foreign keys
3. `docs/SPRINT_STATUS.md` — Only build tasks listed under **Current Sprint**
4. `docs/REQUIREMENTS.md` — Functional requirements (R1–R5)
5. `docs/handoffs/` — Check the latest `SPRINT_*_HANDOFF.md` to pick up where the last sprint left off

## Core Rules
- **Stay in scope:** Never build features from **Upcoming Sprints** or **Out of Scope**.
- **Stick to the schema:** Never invent or rename database columns without updating `docs/SCHEMA.md` first.
- **Offline assets only:** Never link external CDNs (`cdn.jsdelivr.net`, `unpkg.com`, `cdnjs.cloudflare.com`) in Blade views; use local files in `public/vendor/`.
- **Keep docs updated:** When a sprint finishes or the structure changes, update `docs/handoffs/SPRINT_<N>_HANDOFF.md` and `docs/SPRINT_STATUS.md` automatically.
- **Notion sync:** When Kristine shares a Notion sprint, validate it against `docs/PROJECT_RULES.md` and `docs/SCHEMA.md`, then update `docs/SPRINT_STATUS.md`.
- **Sprint handoff:** Once all tasks in **Current Sprint** pass tests, write the handoff note, update `docs/SPRINT_STATUS.md`, share a short summary, and stop so we can push to `main` and start the next sprint fresh.
