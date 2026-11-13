---
description: Generate Sprint Handoff Summary and advance docs/SPRINT_STATUS.md
---

# Close Sprint Workflow (`/close-sprint`)

Follow these exact steps to close the active sprint in `Devaura-LMS`:

1. **Verify Current Sprint Tasks:**
   - Read `docs/SPRINT_STATUS.md` and inspect all tasks listed under `CURRENT SPRINT`.
   - Verify that every migration, model, controller, route, Blade view, and constraint required by the active sprint is implemented and matches `docs/PROJECT_RULES.md`, `docs/SCHEMA.md`, and `docs/REQUIREMENTS.md`.
   - Ensure zero external CDN URLs (`cdn.jsdelivr.net`, `unpkg.com`, `cdnjs.cloudflare.com`) exist in Blade templates.

2. **Output Sprint Handoff Summary:**
   - Generate a concise **Sprint Handoff Summary** detailing:
     - Created and modified files
     - Registered routes and middleware/role guards
     - Database migrations and seeders verified
     - Functional requirements (R1–R5) satisfied in this sprint

3. **Advance `docs/SPRINT_STATUS.md`:**
   - Check off (`[x]`) all completed tasks in the active sprint and move that sprint under `COMPLETED SPRINTS`.
   - Promote the next sprint from `LOCKED / FUTURE SPRINTS` into `CURRENT SPRINT`.

4. **Instruct Developer & Stop:**
   - Instruct the developer to stage, commit, and push the completed sprint changes to `main` (`git add . && git commit -m "..." && git push origin main`).
   - Instruct the developer to close the current chat and open a **fresh Agent session** for the newly promoted `CURRENT SPRINT`, then **STOP** immediately.
