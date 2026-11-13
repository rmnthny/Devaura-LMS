---
description: Read Project Manager's Notion sprint and integrate it into docs/SPRINT_STATUS.md
---

# Sync Notion Sprint Workflow (`/sync-notion-sprint`)

Use this workflow whenever the Project Manager updates or defines a sprint in Notion:

1. **Ingest Notion Sprint Data:**
   - If a Notion MCP server/tool is connected, fetch the target Notion sprint page/database directly.
   - Otherwise, ask the user to paste the Notion sprint title, tasks, and acceptance criteria (or provide the Notion export).

2. **Validate Against Guardrails:**
   - Cross-check every Notion task against `docs/PROJECT_RULES.md` (locked tech stack, exact RBAC role slugs `head_librarian`, `library_staff`, `student_staff`, and **Strict Out-of-Scope Exclusions**).
   - Cross-check any data/table changes against `docs/SCHEMA.md`. If a Notion task requires a new column or table, **flag it explicitly** and ask for approval before updating `docs/SCHEMA.md`.
   - Map each task to `R1`–`R5` in `docs/REQUIREMENTS.md`.

3. **Update Repository Markdown Automatically:**
   - Update `docs/SPRINT_STATUS.md` so the synced Notion sprint becomes `CURRENT SPRINT` with clear, actionable subtasks.
   - If approved schema or requirement updates are needed, update `docs/SCHEMA.md` and `docs/REQUIREMENTS.md` in the same step.

4. **Confirm & Ready for Execution:**
   - Summarize the synced `CURRENT SPRINT` tasks and any flagged scope/schema conflicts.
   - Instruct the developer to commit `docs/` to `main` so all groupmates' AI assistants immediately share the exact same sprint context.
