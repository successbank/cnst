# Repository Guidelines

## Project Structure & Module Organization
- `html/` — Web root (public PHP pages, UI, assets). Most feature work lives here.
- `api/` — PHP endpoints consumed by the UI.
- `admin/` — Admin utilities and maintenance pages.
- `includes/` — Shared PHP (e.g., `db.php`), headers/partials.
- `sql/` — Schema and helper SQL; keep migration snippets here.
- `uploads/` — User files; treat as non-executable content.
- `docker-compose.yml`, `Dockerfile`, `nginx*.conf` — Container and web server config.
- `*.sh` — Operational scripts (start, restart, backup, restore).

## Build, Test, and Development Commands
- Start stack: `docker compose up -d`
- Follow logs: `docker compose logs -f`
- PHP shell: `docker compose exec php bash`
- Lint PHP: `docker compose exec php php -l path/to/file.php`
- Reload Nginx: `./restart-nginx.sh` (inside repo)
- Smoke test: `curl -I http://localhost` and hit key pages under `/html`.

## Coding Style & Naming Conventions
- PHP: 4-space indent, UTF-8, PSR-12 style where practical. Filenames use `snake_case.php` (e.g., `product_detail.php`).
- Functions/vars: `snake_case`; classes (rare) `StudlyCase`.
- SQL: UPPERCASE keywords; one statement per file where possible.
- HTML/CSS/JS: keep inline scripts minimal; prefer `html/css/` assets.
- Config: validate with `docker compose exec nginx nginx -t` before reload.

## Testing Guidelines
- No formal test suite. Add pragmatic checks:
  - Create `html/debug_*.php` scripts with clear assertions for new logic.
  - Name ad-hoc tests `*_test.php` and remove or guard behind admin checks.
  - Use `php -l` for syntax, and `curl` to verify 200/expected content.
  - Seed data changes go in `sql/`; do not modify `database_backup/` for tests.

## Commit & Pull Request Guidelines
- Commits: short, imperative subject (Korean or English), explain “why + what”.
- Reference affected paths or issue IDs when relevant.
- PRs must include: summary, steps to reproduce/verify, screenshots for UI, and risk/rollback notes.
- Run locally with Docker and include sample `curl` outputs for critical endpoints.
- Never commit secrets or large datasets; exclude transient outputs.

## Security & Configuration Tips
- Keep credentials in environment variables (`docker-compose.yml`), not in code.
- Sanitize all input; validate file uploads; ensure `uploads/` is non-executable via Nginx.
- Changes to `nginx*.conf` and `db.php` require extra review.

## Agent-Specific Instructions
- Scope: this file applies to the entire repo.
- Prefer minimal, targeted diffs; avoid renaming public endpoints.
- Do not delete `uploads/` or backups; avoid destructive scripts without approval.
