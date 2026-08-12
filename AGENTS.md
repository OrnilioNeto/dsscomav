# AGENTS.md

Laravel 10 + PHP 8.1 app ("Plataforma DSS" - corporate training / safety trainings). UI text is Brazilian Portuguese (pt-BR); comments are mixed PT/EN — keep new UI strings in pt-BR.

## Commands & tooling

- **No frontend build step**: there is no `package.json`/`node_modules`. Tailwind, jQuery, FontAwesome and SweetAlert are loaded from CDNs in `resources/views/layout.blade.php`. Never run `npm install`/`npm run build`.
- **No test suite**: no `tests/` dir and no `phpunit.xml` exist. `php artisan test` is useless here. Verify changes with `php artisan tinker` or manual browser testing (`php artisan serve`).
- Dev server with raised upload limits: `serve_with_limits.bat` (`php -d upload_max_filesize=250M -d post_max_size=300M -d memory_limit=512M artisan serve`). Needed for video/material uploads.
- Docker stack: `docker compose up` → app on `localhost:9000`, MariaDB `dss_db` on `3306`, Adminer on `8001`.
- Ranking artisan commands (live in `app/Console/Commands/`): `php artisan ranking:recalculate --month= --year=`, `php artisan ranking:consolidate --month= --year=`, `php artisan ranking:check`. `ranking:recalculate` is scheduled daily in `app/Console/Kernel.php` (needs cron on deploy).
- Code style: `vendor/bin/pint` (laravel/pint is a dev dep).

## Database

- Default connection is `mysql` (`config/database.php`); deploy target (ValueHost/cPanel) uses `pgsql`; Docker uses MariaDB; local dev commonly uses SQLite (`database/database.sqlite`, gitignored).
- **Raw date SQL must branch per driver.** Pattern used in `app/Http/Controllers/Admin/RankingController.php:148` and `CertificateManagementController.php:960`: `DB::connection()->getDriverName() === 'sqlite' ? 'strftime(...)' : 'DATE_FORMAT(...)'` / `UNIX_TIMESTAMP(...)`. Follow it — code has no PostgreSQL-specific paths.
- JSON-ish columns (`users.tipo_usuario`, `trainings.tipo_usuario_permitido`) are stored as text and decoded manually with `json_decode` (see `app/Models/User.php:175`), not cast to JSON.
- Migrations are the source of truth; seeder order matters (`database/seeders/DatabaseSeeder.php`).

## Architecture

- **All routes live in `routes/web.php`** (no per-module route files). Admin routes are nested under `middleware('admin')`; some modules also need `permission:<module>`; super-admin-only routes use `CheckRole::class . ':super_admin'` (class-string concat style).
- **Custom RBAC, no Spatie**: `roles` (super_admin / admin / usuario) + `role_permissions` (module, can_view, can_edit). See `app/Models/User.php:126` (`hasPermission`). Middleware aliases are declared in BOTH `$routeMiddleware` and `$middlewareAliases` in `app/Http/Kernel.php` (legacy duplication — keep both in sync).
- **Auth is by CPF**: login strips non-digits and matches the 11-digit `cpf` string (`app/Http/Controllers/AuthController.php:33`). Seeded logins: super admin `10178415430` / `@Machado2025`, admin `11111111111` / `admin123`, motorista `22222222222` / `senha123`.
- Ranking: controllers in `app/Http/Controllers/Admin/`, logic in `app/Services/Ranking*.php`, repo in `app/Repositories/RankingRepository.php`. Routes under `/admin/ranking`, protected by `permission:rankings`.
- `app/Http/Middleware/LogSystemRequests.php` is global middleware — every request is logged.
- Certificates are TCPDF generated **on the fly** (never stored); QR codes via `simplesoftwareio/simple-qrcode`.
- Uploads: profile photos `public/uploads/perfil`, splash `public/uploads/splash`, social `public/uploads/social`; training materials & EPI photos use the `public` disk (`storage/app/public`, served via `/storage/` symlink — `public/storage` is gitignored).

## Gotchas

- **Many stale/misplaced PHP files exist. The canonical code is under `app/`.** Treat these as dead code, do not edit them:
  - Repo root: `RankingController.php`, `RankingCriterion.php`, `RankingRule.php`, `RankingRuleResolverService.php`, `RankingSettingsController.php`, `2026_06_03_000009_create_engagement_ranks_table.php`, `check_ranking.php`, `verify_ranking_data.php`, `test_*.php`, `run_ss_epi_ddl_mysql.php`, `gen_hash.php`, `create_admin.php`, `create_super_admin.php`, `index.blade.php`.
  - `database/seeders/`: `RankingController.php`, `RankingSettingsController.php`, `settings.blade.php`.
  - `app/Services/`: `index.blade.php`, `2026_06_08_000000_add_total_raw_score_to_ranking_monthly_scores_table.php`.
  - Root `index.php` is a real duplicate of `public/index.php` used for cPanel root hosting — update both if you ever touch the front controller.
- **`.env.example` and `.env.production.example` are gitignored and absent** from the repo (only `.env` exists locally). README/QUICKSTART/setup.bat reference them, but a fresh clone cannot `cp .env.example .env`.
- EPI module (`routes/web.php` `epi` prefix) uses legacy `ss_` snake_case column names (e.g. `ss_c_tx_cpf`) — don't "modernize" them.
- Dockerfile installs `php:8.2-cli` (no apache/nginx — serves via `artisan serve`); local docs say PHP 8.1+.
- Deployment is manual cPanel/ValueHost (`composer install --no-dev --optimize-autoloader`, see `DEPLOY_CPANEL.sh`); there is no CI.
