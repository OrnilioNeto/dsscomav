# Plataforma DSS — Treinamento Corporativo (SaaS Multi-Tenant)

Sistema web de treinamentos corporativos e DSS (Diálogo Semanal de Segurança) em **Laravel 10 / PHP 8.1+**, com multi-tenancy: vários clientes na mesma base, cada um com seus dados, módulos e marca próprios.

## Características

- Autenticação por CPF (web + API mobile via Sanctum)
- RBAC customizada (roles + permissões por módulo)
- Treinamentos em vídeo (YouTube/Vimeo/upload), progresso, avaliação e certificados TCPDF com QR Code
- Módulos: EPI, banco de folgas, ranking/gamificação, rede social, splash, projeto pedagógico (NR-01), relatórios
- **Segurança**: uploads com allowlist/UUID, rate limit no login, headers de segurança, mascaramento de PII (LGPD)
- **Auditoria**: trilha de logins, CRUD e downloads em `audit_logs` + tela `/admin/auditoria` com filtros e CSV
- **Modular**: novos módulos autocontidos em `app/Modules/<Nome>` (descoberta automática, rotas/views/migrations próprias) — `docs/modulos/GUIA_MODULOS.md`
- **Multi-tenant**: banco único + `tenant_id`, subdomínio por cliente, módulos liberados por cliente (`tenant_modules`), white-label (logo, cores, nome, instrutor)
- Painel da plataforma (`/plataforma`, super admin): clientes, módulos, admin inicial, branding

## Tecnologias

| Camada | Stack |
|---|---|
| Backend | Laravel 10, PHP 8.1+ |
| Frontend | Tailwind CSS (CDN), jQuery, FontAwesome, SweetAlert — **sem build step** |
| Banco | MySQL/MariaDB (produção) / SQLite (testes `:memory:`) |
| PDF/QR | TCPDF + simple-qrcode |

## Início rápido

```bash
composer install
# Docker: docker compose up  →  app em http://localhost:9000 (MariaDB dss_db em :3306)
# Local:  php artisan serve    (requer banco configurado no .env)

# Migrations e dados iniciais
php artisan migrate --force
php artisan tenant:backfill --name="<Cliente Atual>" --slug=cliente   # cria o tenant #1 (idempotente)

# Testes
php -d extension=gd -d extension=fileinfo vendor/phpunit/phpunit/phpunit
```

> ⚠️ O auto-migrate foi desligado (F0): todo deploy deve rodar `php artisan migrate --force` manualmente.

## Credenciais padrão (seeders)

| Perfil | CPF | Senha |
|---|---|---|
| Super admin (plataforma) | `10178415430` | `@Machado2025` |
| Admin | `11111111111` | `admin123` |
| Motorista | `22222222222` | `senha123` |

## Multi-tenancy (resumo)

- `config/saas.php` → flag `SAAS_MULTITENANT_ENABLED` + `SAAS_ROOT_DOMAIN` (raiz = tenant #1; subdomínio = slug)
- Isolamento: global scope `TenantScope` + macro `whereTenant()` para queries raw + middleware `ResolveTenant`
- Provisionar cliente: painel `/plataforma` ou `php artisan tenant:create --name=... --admin-cpf=... --admin-senha=...`
- Detalhes: `docs/SAAS_PLANO.md` (plano mestre) e `docs/SAAS_RUNBOOK_DEPLOY.md` (deploy/backup)

## Documentação

A documentação vive em `docs/`:

```
docs/
├── SAAS_PLANO.md              # Plano mestre da transformação em SaaS (decisões, fases, riscos)
├── SAAS_RUNBOOK_DEPLOY.md     # Runbook operacional (deploy, backup, staging, rollback)
├── operacao/                  # Instalação, quickstart, deploy cPanel/ValueHost, logs, dependências,
│                              # SEGURANCA.md, AUDITORIA.md, VERSIONAMENTO.md
├── modulos/                   # Manuais por módulo + GUIA_MODULOS.md (como criar um módulo)
└── arquivo/                   # Documentos históricos/one-off (não refletem o estado atual)
```

Versão atual exibida no rodapé: `config/version.php` + `CHANGELOG.md`
(regras em `docs/operacao/VERSIONAMENTO.md`).

## Estrutura do projeto

```
├── app/                # Models, Controllers, Middleware, Services, Commands, Support (TenantManager)
│                       # + Modules/ (módulos novos autocontidos — GUIA_MODULOS.md)
├── bootstrap/
├── config/             # config/saas.php (multi-tenancy), config/modules.php (catálogo de módulos)
├── database/
│   ├── migrations/     # Fonte da verdade do schema (nada de DDL em runtime)
│   ├── seeders/
│   └── sql/            # Scripts SQL avulsos (ex.: seed_super_admin.sql)
├── docs/               # Documentação (plano SaaS, operação, módulos, arquivo)
├── public/             # index.php, images, uploads/
├── resources/views/
├── routes/             # web.php (todas as rotas web) + api.php
├── storage/
└── tests/              # Feature tests (SQLite :memory:)
```

## Deploy

Ver `docs/operacao/DEPLOYMENT_CPANEL.md` e `docs/SAAS_RUNBOOK_DEPLOY.md`.