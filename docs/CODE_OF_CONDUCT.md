# Code of Conduct: SIKDS

This document defines our expectations for collaboration, **clean code principles**, and the **GitHub workspace workflow** for the Secure Institutional Knowledge & Distribution System (SIKDS) project.

---

## 1. Our Pledge

We are committed to providing a respectful and productive environment. We expect all contributors to:

- Be respectful and inclusive in communication and in code review.
- Focus on constructive feedback and on delivering maintainable, secure code aligned with the [SRS](docs/SIKDS_SRS.pdf).
- Follow the branching and merge rules below so that the repository stays predictable and releasable.

---

## 2. GitHub Workspace & Branching Strategy

We use a **branch-by-feature** workflow. Integration happens on `dev`; `main` is updated only at the **end of each sprint**.

### 2.1 Branch Naming

Create a branch **per feature or coherent change**, using a clear prefix and short description:

| Prefix | Use for | Examples |
|--------|--------|----------|
| `feature/` | New functionality | `feature/user-management`, `feature/document-upload`, `feature/rag-query` |
| `fix/` | Bug fixes | `fix/download-watermark`, `fix/login-redirect` |
| `docs/` | Documentation only | `docs/readme-setup`, `docs/api-routes` |
| `refactor/` | Code refactoring (no new behavior) | `refactor/audit-service` |
| `test/` | Tests or test infrastructure | `test/document-upload-integration` |

### 2.2 Workflow Steps

1. **Create a branch from `dev`**:
   ```bash
   git checkout dev
   git pull origin dev
   git checkout -b feature/your-feature-name
   ```

2. **Work on your feature** on that branch. Commit often with clear messages.

3. **When the feature is ready**, push your branch and open a **Pull Request (PR) into `dev`**:
   ```bash
   git push origin feature/your-feature-name
   ```
   - Target branch of the PR must be **`dev`**, not `main`.

4. **Merge into `dev`:** Delete the feature branch after merge, if it is completely done.

5. **Merge `dev` into `main` only at the end of each sprint:**  
   `main` is the release branch. No direct commits to `main` for features or fixes; they flow via `dev`.

Please try to have atomic commits (i.e, one feature/addition at a time).

### 2.3 Summary

| Branch | Purpose |
|--------|--------|
| `main` | Production-ready state; updated only at **sprint end** from `dev`. |
| `dev` | Integration branch; all feature/fix branches merge here first. |
| `feature/*`, `fix/*`, etc. | Short-lived branches for one feature or fix; merge into `dev` when ready. |

---

## 3. Clean Code Principles

All code contributed to SIKDS should adhere to the following principles. They support maintainability, testability, and alignment with the SRS (e.g. Section 4.1).

### 3.1 Naming

- **Meaningful names:** Variables, functions, classes, and files should reveal intent.
- **Consistent vocabulary:** Use the same terms as the SRS and the codebase.

### 3.2 Functions & Methods

- **Single responsibility:** One function does one thing. If a method does "upload + validate + index + notify", split it into smaller functions or use dedicated services/jobs.
- **Short and readable:** Use descriptive names so that code reads like prose.
- **Few parameters:** Prefer at most a few parameters; use objects/DTOs or options arrays when many values are needed.

### 3.3 Structure & Design

- **DRY (Don't Repeat Yourself):** Extract shared logic into services, traits, or helpers; avoid copy-paste.
- **Separation of concerns:** Keep HTTP (controllers), business logic (services, jobs), and data access (Eloquent, repositories) clearly separated.
- **Dependency injection:** Prefer constructor or method injection for services and dependencies; avoid global state and `new` for testability.

### 3.4 Error Handling & Validation

- **Fail fast:** Validate inputs and permissions at the entry point; return clear errors (e.g. 403, 422) instead of failing later in the stack.

### 3.5 Documentation & Tests
- Use **Pest**. Run `php artisan test` before pushing.
- Cover new behavior: happy path, errors, and edge cases.

## 3. 6 Database

- Use **migrations** for schema changes; do not change the DB by hand.
- Keep **seeders** for realistic test data.

### 3.7 Style & Formatting

- **Consistent style:** Follow the project's PHP/JS style. Run formatters before committing.

---

## 4. Pull Requests & Reviews

- **One logical change per PR:** One feature or one fix; keep PRs reviewable.
---

## 5. References

- **Project overview & setup:** [README.md](README.md)
- **Requirements & architecture:** [docs/SIKDS_SRS.pdf](docs/SIKDS_SRS.pdf)
---

*SIKDS Code of Conduct*
