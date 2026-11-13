---
trigger: always_on
---

# Mandatory AI Context & Sprint Gate Instructions

Before generating code, migrations, routes, or terminal commands for `Devaura-LMS`, you MUST read and strictly obey:
1. `docs/PROJECT_RULES.md` (Locked tech stack, RBAC role slugs, and strict out-of-scope exclusions)
2. `docs/SCHEMA.md` (Exact MySQL table names, column names, data types, and foreign keys)
3. `docs/SPRINT_STATUS.md` (Sprint boundary gate: implement ONLY tasks under `CURRENT SPRINT`)
4. `docs/REQUIREMENTS.md` (Functional traceability to UBNHS operational requirements R1–R5)
5. `docs/handoffs/` (Read the latest `SPRINT_*_HANDOFF.md` if present to inherit exact context from the previous sprint)

## Non-Negotiable Execution Rules
- **NO SCOPE CREEP:** Never build features listed under `LOCKED / FUTURE SPRINTS` or `Out-of-Scope Exclusions`.
- **NO SCHEMA HALLUCINATION:** Never invent new database columns or rename existing ones without explicit user approval to update `docs/SCHEMA.md`.
- **OFFLINE ASSETS ONLY:** Never reference external CDNs (`cdn.jsdelivr.net`, `unpkg.com`, `cdnjs.cloudflare.com`) in Blade templates; always use local assets under `public/vendor/`.
- **AI-MANAGED DOCUMENTATION (ZERO MANUAL `.md`):** Never ask the user to write or update markdown files manually. Whenever you complete a sprint or make structural changes to the system, YOU must automatically:
  1. Generate/update `docs/handoffs/SPRINT_<N>_HANDOFF.md` with exact created/modified files, routes, migrations, and verification results.
  2. Update `docs/SPRINT_STATUS.md` (and `docs/SCHEMA.md` / `docs/REQUIREMENTS.md` if approved changes occurred).
- **NOTION SPRINT SYNC:** When the Project Manager's Notion sprint is provided (via Notion MCP or pasted text), parse and validate it against `docs/PROJECT_RULES.md` and `docs/SCHEMA.md`, then update `docs/SPRINT_STATUS.md` (`CURRENT SPRINT`) automatically.
- **FRESH-CONTEXT HANDOFF:** When all tasks in `CURRENT SPRINT` are completed and verified, generate the handoff markdown file, update `docs/SPRINT_STATUS.md`, output a concise **Sprint Handoff Summary**, and STOP so the developer can commit/push to `main` and start a fresh chat for the next sprint.
