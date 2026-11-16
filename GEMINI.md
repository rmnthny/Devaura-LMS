# Project Guidelines

Before writing any code, migrations, routes, or commands for `Devaura-LMS`, read and follow:
1. `docs/PROJECT_RULES.md` — Tech stack, roles, and out-of-scope items
2. `docs/SCHEMA.md` — Exact MySQL tables, columns, data types, and foreign keys
3. `docs/SPRINT_STATUS.md` — Only build tasks listed under **Current Sprint**
4. `docs/REQUIREMENTS.md` — Functional requirements (R1–R5)
5. `docs/handoffs/` — Check the latest `SPRINT_*_HANDOFF.md` to pick up where the last sprint left off

## Do's and Don'ts
**Do:**
- Read `docs/PROJECT_RULES.md`, `docs/SCHEMA.md`, `docs/SPRINT_STATUS.md`, and `docs/REQUIREMENTS.md` before coding.
- Use local assets in `public/vendor/` (Bootstrap 5, Tailwind CSS, JS).
- Write feature tests (`php artisan test`) for every sprint task.
- Keep sensitive files (`.env`, keys, passwords) in `.gitignore`.

**Don't:**
- Never build tasks from **Upcoming Sprints** or **Out of Scope**.
- Never invent or rename database columns without updating `docs/SCHEMA.md` first.
- Never link external CDNs in Blade templates.
- Never commit `.env` or any credentials to GitHub.
