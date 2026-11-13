# Mandatory AI Context & Sprint Gate Instructions

Before generating code, migrations, routes, or terminal commands for `Devaura-LMS`, you MUST read and strictly obey:
1. `docs/PROJECT_RULES.md` (Locked tech stack, RBAC role slugs, and strict out-of-scope exclusions)
2. `docs/SCHEMA.md` (Exact MySQL table names, column names, data types, and foreign keys)
3. `docs/SPRINT_STATUS.md` (Sprint boundary gate: implement ONLY tasks under `CURRENT SPRINT`)
4. `docs/REQUIREMENTS.md` (Functional traceability to UBNHS operational requirements R1–R5)

## Non-Negotiable Execution Rules
- **NO SCOPE CREEP:** Never build features listed under `LOCKED / FUTURE SPRINTS` or `Out-of-Scope Exclusions`.
- **NO SCHEMA HALLUCINATION:** Never invent new database columns or rename existing ones without explicit user approval to update `docs/SCHEMA.md`.
- **OFFLINE ASSETS ONLY:** Never reference external CDNs (`cdn.jsdelivr.net`, `unpkg.com`, `cdnjs.cloudflare.com`) in Blade templates; always use local assets under `public/vendor/`.
- **FRESH-CONTEXT HANDOFF:** When all tasks in `CURRENT SPRINT` are completed and verified, output a concise **Sprint Handoff Summary** (created/modified files, routes, migrations) and STOP so the developer can update `docs/SPRINT_STATUS.md` and start a fresh chat for the next sprint.
