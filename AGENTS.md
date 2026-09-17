# AGENTS.md

Laravel 10 + PHP 8.1 app ("Plataforma DSS" - corporate training / safety trainings). UI text is Brazilian Portuguese (pt-BR); comments are mixed PT/EN — keep new UI strings in pt-BR.

## Commands & tooling

- **No frontend build step**: there is no `package.json`/`node_modules`. Tailwind, jQuery, FontAwesome and SweetAlert are loaded from CDNs in `resources/views/layout.blade.php`. Never run `npm install`/`npm run build`.
- **Tests exist** (`phpunit.xml` + `tests/`). Run: `php -d extension=gd -d extension=fileinfo vendor/phpunit/phpunit/phpunit` (SQLite `:memory:`). On Windows CLI, pdo_mysql/gd/fileinfo may need `-d extension=...`.
- **No `php artisan test` shortcut** (phpunit isn't installed as bin); use the command above.
- Dev server with raised upload limits: `serve_with_limits.bat` (`php -d upload_max_filesize=250M -d post_max_size=300M -d memory_limit=512M artisan serve`). Needed for video/material uploads.
- Docker stack: `docker compose up` → app on `localhost:9000`, MariaDB `dss_db` on `3306`, Adminer on `8001`.
- Ranking artisan commands (live in `app/Console/Commands/`): `php artisan ranking:recalculate --month= --year=`, `php artisan ranking:consolidate --month= --year=`, `php artisan ranking:check`. `ranking:recalculate` is scheduled daily in `app/Console/Kernel.php` (needs cron on deploy).
- **Tenant commands**: `php artisan tenant:backfill --name= --slug=` (cria tenant #1 e preenche tenant_id — rodar na produção após `migrate`).
- Code style: `vendor/bin/pint` — **run it only on changed files** (`pint <file>...`), never on the whole repo (it reformats everything, line endings included).
- **Auto-migrate foi desligado (F0)**: DDL de runtime removido. Deploy DEVE rodar `php artisan migrate --force` manualmente.

## Multi-tenancy (em andamento — F1/F2)

- `config/saas.php`: flag `SAAS_MULTITENANT_ENABLED` (default **false** = comportamento single-tenant atual). Ao ativar: tenant resolvido pelo host (raiz = tenant padrão `root_tenant_slug`, subdomínio = slug).
- Models de domínio usam `App\Models\Concerns\BelongsToTenant` (global scope `TenantScope` + auto-fill de `tenant_id`). `users.tenant_id = NULL` = usuário da plataforma (super_admin), que não é filtrado pelo escopo.
- `TenantScope` tem regra especial para `Role`/`RolePermission`: além das linhas do tenant, as linhas de sistema (tenant_id NULL) são sempre visíveis.
- Auth: provider customizado `tenant-eloquent` (`config/auth.php`) ignora o TenantScope na resolução de sessão; login por CPF tem fallback para super_admin em qualquer host.
- Queries raw (`DB::table`) usam a macro `->whereTenant('tabela')` (definida no `AppServiceProvider`) — obrigatório em tabelas de domínio (EPI, filiais, projeto pedagógico).
- Módulos por tenant: `tenant_modules` + gate central no `CheckPermission` + middleware `module:<slug>` (rotas admin sem `permission:`).
- Painel da Plataforma: `/plataforma` (somente super_admin) — CRUD de clientes e toggle de módulos (`Admin\PlataformaTenantController`).
- Uploads isolados por tenant: `tenant_upload_dir()` (public/uploads/{tenant}/...) e `tenant_public_storage_dir()` (storage/app/public/tenants/{tenant}/...) — fallback legado nos acessores (foto de perfil, social).
- Comandos (`ranking:*`, `folgas:*`) iteram por tenant via `TenantManager::runForEachTenant()` quando a flag está ativa; `CertificateObserver` roda no contexto do tenant do usuário; `LogSystemRequests` grava `tenant_id`.
- Rotas públicas (ficha QR, validação de certificado) usam lookups globais (`qrcode_token`, `codigo_certificado` continuam UNIQUE globais).
- **Deploy**: rodar `php artisan migrate --force` + `php artisan tenant:backfill` antes de ativar a flag.

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

- **Código morto removido em 2026-09-16** (backup local em temp). Os arquivos antes listados como dead code (controllers de ranking/folgas na raiz, `test_*.php`, `create_admin.php`, `create_super_admin.php`, migrations fora de `database/migrations`, `*.blade.php` soltos, `composer.json.exemplo`, `camera-test.html`, `.venv`) foram excluídos. Não reintroduza arquivos na raiz do repo.
- **Documentação** organizada em `docs/`: `SAAS_PLANO.md` + `SAAS_RUNBOOK_DEPLOY.md` na raiz de docs; `docs/operacao/` (instalação, deploy cPanel/ValueHost, logs, dependências); `docs/modulos/` (manuais por módulo); `docs/arquivo/` (históricos — não refletem o estado atual). `README.md` é a porta de entrada.
- Root `index.php` is a real duplicate of `public/index.php` used for cPanel root hosting — update both if you ever touch the front controller.
- **`.env.example` and `.env.production.example` are gitignored and absent** from the repo (only `.env` exists locally). README/QUICKSTART/setup.bat reference them, but a fresh clone cannot `cp .env.example .env`.
- EPI module (`routes/web.php` `epi` prefix) uses legacy `ss_` snake_case column names (e.g. `ss_c_tx_cpf`) — don't "modernize" them.
- Dockerfile installs `php:8.2-cli` (no apache/nginx — serves via `artisan serve`); local docs say PHP 8.1+.
- Deployment is manual cPanel/ValueHost (`composer install --no-dev --optimize-autoloader`, see `DEPLOY_CPANEL.sh`); there is no CI.
