# Team Workflow & Engineering Standards (`Devaura-LMS`)

Aligned with the **Nov 18, 2026 Architecture & Security Review** and our **Lucidchart Diagrams** (Proposed Process Flowchart, Use Case Diagram, DFD, ERD).

---

## 1. Division of Responsibilities
| Member | Role | Primary Folders / Deliverables |
| :--- | :--- | :--- |
| **Anthony** | Backend Developer | `app/`, `routes/`, `tests/`, Spatie RBAC, Auth/OTP, QR Service, Bulk Import, Postman & PHPUnit |
| **Shaina** | Frontend Developer | `resources/views/`, `public/vendor/`, Bootstrap 5 (offline), mobile-responsive UI & `@can` buttons |
| **Kyle** | Document Analyst / Database Developer | Normalized MySQL (`devaura_lms`) schema, ERD/DFD/Flowchart/Use Case documentation, migrations |
| **Kristine** | Project Manager / QA | Notion sprint tracking, QA checklist (RBAC, CSRF, XSS, SQLi, Postman, mobile responsiveness) |

> **AI Tool Rule (Antigravity / Codex / Claude):** Respect these boundaries—when working on a backend-only task, do not overwrite Kyle's DB schema or Shaina's Blade views unless explicitly asked.

---

## 2. Sprint & Notion Workflow
1. **Notion Board Statuses:** `Not Started` → `In Progress` → `Postponed` / `Done`.
2. **Branch per Task:** Create a short-lived branch from `main` (e.g., `feat/anthony-sprint-1-rbac`, `feat/shaina-sprint-1-ui`, `feat/kyle-db-schema`).
3. **Pull Request & QA Gate:** Never merge directly to `main` until:
   - `php artisan test` passes (100% green).
   - **Postman API Verification** passes (`GET`, `POST`, `PUT/PATCH`, `DELETE`, CSRF token handling, expected `200`/`201`/`403`/`422` codes).
   - **Security Checks** pass (3/min login rate limit, `PreventBackHistory`, active check, no raw SQL, Blade escaping).
   - **Responsiveness Check** passes (Chrome DevTools / mobile screen viewer for grids, cards, and inputs).
4. **Sprint Handoff:** Generate `docs/handoffs/SPRINT_<N>_HANDOFF.md`, update `docs/SPRINT_STATUS.md`, merge to `main`, and start a fresh AI chat for the next sprint.

---

## 3. Localhost (Dec 1 Defense) vs. Hostinger (Next Phase)
- **Localhost Prototype (Laragon, `http://localhost:8000`):**
  - Uses local MySQL (`devaura_lms`), offline assets in `public/vendor/`, and `MAIL_MAILER=log` (or Gmail SMTP if Wi-Fi is available during defense).
  - Backend-only development can run tests anytime without MySQL using `php artisan test` (in-memory SQLite).
- **Cloud Deployment (Hostinger — Next Phase):**
  - Restrict admin/staff routes to the school network while allowing external student/faculty portal access.
