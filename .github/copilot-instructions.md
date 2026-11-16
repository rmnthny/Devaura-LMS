# Copilot Guidelines

Before suggesting code, migrations, routes, or commands for `Devaura-LMS`, read and follow:
1. `docs/PROJECT_RULES.md`
2. `docs/SCHEMA.md`
3. `docs/SPRINT_STATUS.md` (only build tasks under **Current Sprint**)
4. `docs/REQUIREMENTS.md`

## Sprint Closing Gate
- **Strict Completion Gate:** Never close a sprint until all tasks in **Current Sprint** are done, `php artisan test` passes, and Kristine's QA fixes are applied.
- **Automatic Sprint Handoff Doc:** Once verified, generate `docs/handoffs/SPRINT_<N>_HANDOFF.md` (with created/updated files, routes, migrations, and test results) and update `docs/SPRINT_STATUS.md`.
- **Suggest New Chat & Stop:** Output a short **Sprint Handoff Summary**, suggest opening a new chat, and stop so the next chat reads `docs/handoffs/SPRINT_<N>_HANDOFF.md` and continues seamlessly.
