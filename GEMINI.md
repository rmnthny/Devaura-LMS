# Project Guidelines

Before writing any code, migrations, routes, or commands for `Devaura-LMS`, read and follow:
1. `docs/PROJECT_RULES.md` — Tech stack, 7-role RBAC, security rules, and out-of-scope items
2. `docs/TEAM_WORKFLOW.md` — Team boundaries (Anthony: Backend, Shaina: Frontend, Kyle: Document Analyst / DB, Kristine: PM/QA), Notion, Postman & QA workflow
3. `docs/SPRINT_STATUS.md` — Only build tasks listed under **Current Sprint**
4. `docs/REQUIREMENTS.md` — Functional requirements (R1–R5)
5. `docs/handoffs/` — Check the latest `SPRINT_*_HANDOFF.md` to pick up where the last sprint left off

## Do's and Don'ts
**Do:**
- Read `docs/PROJECT_RULES.md`, `docs/TEAM_WORKFLOW.md`, `docs/SPRINT_STATUS.md`, and `docs/REQUIREMENTS.md` before coding.
- Respect team role boundaries (don't overwrite Kyle's DB migrations or Shaina's UI when doing backend-only work).
- Use local assets in `public/vendor/` (Bootstrap 5, Tailwind CSS, JS).
- Write feature tests (`php artisan test`) and keep endpoints Postman-testable for every sprint task.
- Keep sensitive files (`.env`, keys, passwords) in `.gitignore`.

**Don't:**
- Never build tasks from **Upcoming Sprints** or **Out of Scope**.
- Never link external CDNs in Blade templates.
- Never commit `.env` or any credentials to GitHub.

## Sprint Closing Gate
- **Strict Completion Gate:** Never close a sprint until all tasks in **Current Sprint** are done, `php artisan test` passes, Postman API checks are verified, and Kristine's QA fixes are applied.
- **Automatic Sprint Handoff Doc:** Once verified, generate `docs/handoffs/SPRINT_<N>_HANDOFF.md` (with created/updated files, routes, and test results) and update `docs/SPRINT_STATUS.md`.
- **Suggest New Chat & Stop:** Output a short **Sprint Handoff Summary**, suggest opening a new chat, and stop so the next chat reads `docs/handoffs/SPRINT_<N>_HANDOFF.md` and continues seamlessly.
