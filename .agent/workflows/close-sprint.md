---
description: Generate Sprint Handoff Markdown, update docs/SPRINT_STATUS.md, and prepare fresh session
---

# Close Sprint Workflow (`/close-sprint`)

Follow these exact steps to close the active sprint in `Devaura-LMS` without requiring the user to write any markdown manually:

1. **Verify Current Sprint Tasks:**
   - Read `docs/SPRINT_STATUS.md` and inspect all tasks listed under `CURRENT SPRINT`.
   - Verify that every migration, model, controller, route, Blade view, and constraint required by the active sprint is implemented and matches `docs/PROJECT_RULES.md`, `docs/SCHEMA.md`, and `docs/REQUIREMENTS.md`.
   - Ensure zero external CDN URLs (`cdn.jsdelivr.net`, `unpkg.com`, `cdnjs.cloudflare.com`) exist in Blade templates.

2. **Auto-Generate Sprint Handoff Markdown (`docs/handoffs/SPRINT_<N>_HANDOFF.md`):**
   - Create `docs/handoffs/SPRINT_<N>_HANDOFF.md` (where `<N>` is the completed sprint number) containing:
     - **Completed Tasks:** Checked-off summary of what was built.
     - **Created & Modified Files:** Exact file paths.
     - **Routes & Role Guards:** Exact endpoints and middleware/Spatie roles.
     - **Database & Schema State:** Migrations run, seeders added, and confirmation of `docs/SCHEMA.md` alignment.
     - **Verification Results:** Exact artisan/test/lint commands run and their observed outcomes.
     - **Next Sprint Entry Point:** What the next fresh AI session needs to know immediately.

3. **Advance `docs/SPRINT_STATUS.md`:**
   - Check off (`[x]`) all completed tasks in the active sprint and move that sprint under `COMPLETED SPRINTS`.
   - Promote the next sprint from `LOCKED / FUTURE SPRINTS` into `CURRENT SPRINT`.

4. **Instruct Developer & Stop:**
   - Output a concise **Sprint Handoff Summary** in chat.
   - Instruct the developer to stage, commit, and push the completed sprint changes to `main` (`git add . && git commit -m "..." && git push origin main`).
   - Instruct the developer to close the current chat and open a **fresh Agent session** for the newly promoted `CURRENT SPRINT`, then **STOP** immediately.
