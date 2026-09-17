# Plano de Transformação em SaaS — Plataforma DSS

> Documento vivo. Última atualização: 2026-09-16.
> Este é o plano mestre da migração single-tenant → multi-tenant (SaaS).
> Alterações de escopo devem ser registradas aqui e, quando estruturais, em `docs/adr/`.

## 0. Status atual

| Fase | Status |
|---|---|
| F0 Saneamento | **Concluída** (migrations do zero OK em MySQL; auto-migrate/DDL runtime desligados; testes 19/19 verdes) |
| F1 Fundação | **Concluída** — tenants/tenant_modules, tenant_id em ~43 tabelas, uniques compostos, backfill (tenant #1), TenantManager + scope + trait, ResolveTenant (web/api), auth escopado (web + Sanctum), uploads namespaced, comandos/observer/logs por tenant, filtro de tenant em queries raw (`whereTenant`), testes de isolamento |
| F2 Módulos/RBAC | **Concluída (núcleo)** — `config/modules.php`, gate de módulo central em `CheckPermission` + middleware `module:`, roles de sistema visíveis no tenant (`TenantScope` especial para Role/RolePermission), painel `/plataforma` (CRUD clientes + toggle de módulos). **Adiado**: roles customizadas por tenant (F2.3 parcial — requer UI de gestão de perfis do tenant) |
| F3 White-label | **Concluído (núcleo)** — comando `tenant:create`, botão "Criar Admin", branding por tenant (logo no layout/login via `<x-logo>`, logo do certificado TCPDF, cores CSS, nome de exibição, instrutor nos certificados), upload de logos e campos de branding no painel `/plataforma`. **Pendente**: checklist cPanel (subdomínio + AutoSSL) e ADRs |
| F4 Futuro | Não iniciada (limites, billing, observabilidade, domínio próprio, fila database) |

**Próximos passos recomendados (F3):**
- [ ] Branding no layout/login/dashboard e certificado TCPDF por tenant
- [ ] Checklist cPanel (subdomínio + AutoSSL por cliente)
- [ ] ADRs das decisões + atualizar `AGENTS.md`

---

## 1. Contexto

A Plataforma DSS é um sistema corporativo de treinamentos de segurança (DSS), certificados,
EPI, folgas, ranking e rede social, em **produção**, atendendo um cliente (COMAV/DSS).
O objetivo é transformá-la em um **SaaS multi-tenant**: vários clientes na mesma base,
cada um vendo somente os seus dados, com módulos liberados por cliente e white-label.

**Restrições inegociáveis:**

- O sistema está em produção e não pode parar além de janelas curtas planejadas.
- A produção roda em **cPanel/ValueHost** (permanece por enquanto).
- Toda alteração deve ser validada em **localhost** antes de subir.
- O cliente atual não pode perceber mudanças (mesmas URLs, mesmos dados, mesmas funções).

---

## 2. Decisões de arquitetura (fechadas)

| Tema | Decisão |
|---|---|
| Isolamento de dados | Banco único + `tenant_id` em todas as tabelas de domínio, com isolamento garantido por código (global scope + middleware + testes) |
| Identificação do tenant | **Subdomínio** (`cliente.seudominio.com`). A raiz do domínio atual continua sendo o **tenant #1** (cliente atual) para preservar QR codes e certificados já emitidos |
| Painel da plataforma | `/plataforma` na raiz (reservado), acessível apenas pelo platform admin (super_admin). Pode virar subdomínio dedicado no futuro |
| CPF | Único **por tenant**: `unique(tenant_id, cpf)` e `unique(tenant_id, email)`. Login continua por CPF, resolvido no host |
| Módulos | Tabela `tenant_modules` (flag por cliente) + middleware `module:<x>` combinado com `role_permissions` |
| RBAC | Roles de sistema globais (`super_admin`, `admin`, `usuario`); roles customizadas passam a ter `tenant_id` |
| Hierarquia de perfis | **Platform admin** (dono do SaaS) → **tenant admin** (cliente) → usuários |
| White-label | Completo: nome, logo, cores, logo do certificado, e-mail remetente por tenant |
| Infra | cPanel/ValueHost; onboarding semi-manual (até ~10 clientes no 1º ano) |
| Fila | `sync` hoje. Futuro: driver `database` + worker via cron do cPanel |
| Driver de banco | **Pendente de verificação no cPanel** (impacta uniques/índices e queries raw). Código hoje só ramifica `sqlite` vs `mysql` |

---

## 3. Diagnóstico do estado atual

### 3.1 O que já ajuda

- RBAC modular pronta: `role_permissions` (`module`, `can_view`, `can_edit`) + middleware `permission:<modulo>`.
- Rotas centralizadas em `routes/web.php` e separadas por módulo/prefixo.
- Módulos bem separados: treinamentos, certificados, EPI, folgas, ranking, social, splash,
  projeto pedagógico, relatórios, API mobile (Sanctum).
- `users.empresa` (texto) permite mapear o tenant #1 sem adivinhação.
- EPI já possui noção primitiva de filial (`ss_filial` + `ss_*_nb_empresa_id`).
- Migrations são a fonte da verdade e são defensivas (`hasTable`/`hasColumn`).

### 3.2 Bloqueadores

1. Nenhuma tabela core possui `tenant_id` (~36 tabelas).
2. `users.cpf` e `users.email` são UNIQUE globais.
3. Roles e permissões são globais; `isAdmin()` considera qualquer role ≠ `usuario` como admin.
4. Configurações singleton globais: `ranking_settings`, `folga_settings`, critérios de ranking, splash.
5. Ranking/folgas/dashboards/relatórios calculam sobre a base inteira.
6. Uploads em pastas fixas e logo COMAV hardcoded no certificado.
7. Comandos agendados e `CertificateObserver` operam na base toda.
8. Tokens Sanctum sem escopo de tenant; rotas públicas (ficha QR, validação de certificado) sem contexto.
9. Queries raw `DB::table()` (EPI, folgas, ranking settings) não são cobertas por global scopes.

### 3.3 Riscos críticos de produção

1. **Auto-migrate em request** (`AppServiceProvider::autoMigrate`) + **DDL em runtime** em controllers:
   consolidar em migrations e desligar (F0).
2. **Zero testes**: criar infraestrutura de testes antes de mexer em tenancy (F0).
3. **Driver de banco ambíguo**: confirmar no cPanel.
4. **Controllers gigantes** (`EpiController` 2.060 linhas, `CertificateManagementController` 1.727).
5. **Fila `sync`**: recálculo de ranking roda na request.
6. **cPanel**: sem Redis, sem SSL wildcard nativo (AutoSSL é por subdomínio).
7. **Código morto/stale** espalhado (raiz, seeders, services).

---

## 4. Arquitetura alvo

### 4.1 Tabelas novas

| Tabela | Colunas principais |
|---|---|
| `tenants` | `id`, `nome`, `slug` (unique), `dominio` (unique, nullable p/ domínio próprio), `status` (`ativo`/`trial`/`suspenso`/`cancelado`), `plano`, branding (`logo`, `logo_certificado`, `cor_primaria`, `cor_secundaria`, `nome_exibicao`, `email_remetente`), `timestamps`, `deleted_at` |
| `tenant_modules` | `id`, `tenant_id` (FK), `module` (slug), `enabled`, `enabled_at`, `expires_at`, `timestamps`, unique `(tenant_id, module)` |

### 4.2 Colunas e uniques alterados

- `tenant_id` (nullable → NOT NULL após backfill) em todas as tabelas de domínio:
  `users`, `trainings`, `training_materials`, `training_questions`, `training_assignments`,
  `training_logs`, `training_rewatch_requests`, `training_vacation_exemptions`, `employee_trainings`,
  `employee_epis`, `user_progress`, `certificates`, `user_vacations`, `ranking_*` (settings, criteria,
  rules, scores, monthly_scores, histories), `folga_*` (settings, dias, movimentos, saldos_mensais,
  domingo_saldos, logs), `social_*` (posts, likes, comments, follows), `splash_contents`,
  `training_projetos_pedagogicos`, `projeto_pedagogico_trainings`, `ss_*` (epi, colaborador, estoque,
  entrega, devolucao, variacao, kit, kit_item, filial), `roles`.
- `users.tenant_id` é **nullable** (NULL = usuário da plataforma/super_admin).
- `personal_access_tokens.tenant_id` para tokens da API.
- Uniques:
  - `users`: remover unique global de `cpf` e `email` → `unique(tenant_id, cpf)`, `unique(tenant_id, email)`.
    `qrcode_token` continua unique global (lookup público).
  - `roles`: `unique(tenant_id, nome)` (MySQL/MariaDB permitem múltiplos NULL).
  - `ranking_criteria`: `unique(tenant_id, slug)`.
  - `ranking_settings` e `folga_settings`: 1 linha por tenant (`unique(tenant_id)`).
  - `certificates.codigo_certificado`: permanece unique global (lookup público).
- Conceito de **filial** (`ss_*_nb_empresa_id`, `ss_filial`) é sub-unidade **dentro** do tenant.
  Não confundir com tenant.

### 4.3 Runtime

- `App\Support\TenantManager` (singleton): tenant atual, resolução por host, helpers `tenant()`, `tenant_id()`.
- Trait `App\Models\Concerns\BelongsToTenant`: adiciona global scope + `creating` (preenche `tenant_id`).
- Macro/helper para queries raw: `DB::table(...)` recebe filtro explícito; auditoria via grep + testes.
- Middleware `ResolveTenant` (web e api): resolve tenant pelo host (subdomínio → raiz = tenant #1)
  e aborta 404 se o tenant não existir/estiver suspenso.
- Middleware `module:<slug>`: verifica `tenant_modules.enabled` e `role_permissions`.
- Login web: `where('tenant_id', tenant()->id)->where('cpf', $cpf)`.
- Login API: app informa o código/subdomínio da empresa; token Sanctum grava `tenant_id`.
- Uploads namespaced: `public/uploads/{tenant_id}/perfil`, `storage/app/public/tenants/{tenant_id}/...`.
- Comandos (`ranking:*`, `folgas:*`) iteram por tenant ativo; `CertificateObserver` resolve o tenant do usuário.
- `LogSystemRequests` grava `tenant_id` no contexto.
- Rotas públicas (ficha QR, validação de certificado) resolvem tenant pelo host; raiz = tenant #1
  garante compatibilidade com códigos já impressos.

### 4.4 Hierarquia de acesso

```
Platform admin (users.tenant_id = NULL, role super_admin)
  └── /plataforma: CRUD de tenants, módulos, status, impersonation (futuro)
Tenant admin (users.tenant_id = X, role admin ou custom)
  └── /admin/* do próprio tenant, respeitando tenant_modules + role_permissions
Usuário (users.tenant_id = X, role usuario)
  └── dashboard, treinamentos, certificados, social, ficha
```

---

## 5. Fases

### F0 — Saneamento (pré-requisito)

| # | Tarefa | Critério de aceite |
|---|---|---|
| 0.1 | Verificar driver real de produção no cPanel | `DB_CONNECTION` documentado no plano e no AGENTS.md |
| 0.2 | Consolidar DDL de runtime em migrations idempotentes | `php artisan migrate:fresh` cria 100% das tabelas sem acessar controllers |
| 0.3 | Desligar `autoMigrate()` e DDL em runtime (flag/config) | Nenhum request executa `Artisan::call('migrate')` nem `Schema::create` |
| 0.4 | Criar infraestrutura de testes (`phpunit.xml` + `tests/`) | `php vendor/phpunit/phpunit/phpunit` roda verde |
| 0.5 | Testes de caracterização dos fluxos atuais | Login, treinamento, certificado e EPI cobertos |
| 0.6 | Runbook de backup e staging | Documento com passos de backup (DB + uploads) |

### F1 — Fundação multi-tenant

| # | Tarefa | Critério de aceite |
|---|---|---|
| 1.1 | Migrations: `tenants`, `tenant_modules`, `tenant_id` (aditivo) | `migrate` em base existente não quebra |
| 1.2 | Backfill: cliente atual = tenant #1 | Todas as linhas com `tenant_id` preenchido |
| 1.3 | `TenantManager` + trait `BelongsToTenant` + suporte a `DB::table` | Testes de scope passando |
| 1.4 | Middleware `ResolveTenant` + `SetTenantContext` (web/api) | Host desconhecido → 404; raiz → tenant #1 |
| 1.5 | Auth escopado (web e Sanctum) | CPF de outro tenant não autentica |
| 1.6 | Uniques compostos (janela de manutenção) | `unique(tenant_id, cpf/email/nome/slug)` |
| 1.7 | Uploads namespaced + script de migração dos arquivos | Arquivos do tenant #1 movidos e paths atualizados |
| 1.8 | Comandos, observer e logs por tenant | `ranking:recalculate` itera tenants |
| 1.9 | Testes de isolamento (web, API, públicas, uploads) | Tenant A não acessa nada de B |

### F2 — Módulos e RBAC por tenant

| # | Tarefa | Critério de aceite |
|---|---|---|
| 2.1 | `config/modules.php` (catálogo único) | Nenhuma lista de módulos hardcoded |
| 2.2 | Middleware `module:<slug>` (módulo + permissão) | Rota de módulo desligado → 403 |
| 2.3 | `roles.tenant_id` + gestão de roles por tenant | Tenant A não vê role custom de B |
| 2.4 | Painel `/plataforma`: clientes, módulos, status | Platform admin provisiona cliente |
| 2.5 | Painel do tenant admin (usuários/roles internos) | Admin do cliente gerencia só o seu tenant |

### F3 — White-label e onboarding

| # | Tarefa | Critério de aceite |
|---|---|---|
| 3.1 | Branding no layout/login/dashboard | Cores e logo do tenant |
| 3.2 | Certificado TCPDF com logo do tenant | Sem COMAV hardcoded |
| 3.3 | Comando `tenant:create` | Cria tenant + admin + seed + módulos |
| 3.4 | Checklist cPanel (subdomínio + AutoSSL) | Documentado |
| 3.5 | Documentação (`docs/SAAS_*.md`, ADRs, AGENTS.md) | Atualizada |

### F4 — Futuro

Limites por plano (nº usuários, storage), billing (Mercado Pago/Stripe/PIX),
observabilidade por tenant, domínio próprio, fila `database` + cron worker, impersonation.

---

## 6. Estratégia de migração e rollout

1. **Migrations aditivas**: `tenant_id` nullable + índices, sem quebrar o código atual.
2. **Backfill** via comando (`tenant:backfill`) idempotente e em chunks.
3. **Código novo** lê tenant do contexto; com a flag `SAAS_MULTITENANT_ENABLED=false`,
   o tenant #1 é implícito e o comportamento é idêntico ao atual.
4. **Uniques compostos** e `NOT NULL` só depois do backfill, em janela de manutenção curta.
5. **Testes de isolamento** são gate de deploy (nenhum deploy sem verde).
6. **Staging** com 2 tenants fake no cPanel antes do switch.
7. **Backup completo** (DB + uploads) antes de cada etapa crítica.

### Deploy em cPanel (runbook resumido)

1. Backup: `mysqldump` (ou backup do cPanel) + zip de `storage/app/public` e `public/uploads`.
2. `git pull` / upload dos arquivos.
3. `composer install --no-dev --optimize-autoloader`.
4. `php artisan migrate --force` (substitui o auto-migrate desligado no F0).
5. `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
6. Smoke test: login raiz (tenant #1), abrir treinamento, emitir certificado.

---

## 7. Infraestrutura (cPanel/ValueHost)

| Limitação | Mitigação |
|---|---|
| Sem SSL wildcard nativo | Criar subdomínio + AutoSSL por cliente (semi-manual) |
| Sem Redis | Cache/sessão em arquivo (atual); reavaliar com volume |
| Cron limitado | Cron a cada 5 min para comandos por tenant; futuro worker `database` |
| Sem CI | Rodar testes em localhost + checklist manual antes do deploy |
| Driver de banco | Confirmar antes da F1 (item 0.1) |

---

## 8. Estratégia de testes

- **Local**: `phpunit.xml` com SQLite `:memory:` para testes rápidos.
- **Banco real**: MariaDB do Docker (`dss-db`) para validar migrations e uniques.
- **Cobertura mínima por fase**:
  - F0: caracterização (login, player, certificado, EPI).
  - F1: isolamento por tenant (web, API, rotas públicas, uploads, comandos).
  - F2: gating de módulos e roles por tenant.
- **Smoke manual** ao final de cada fase em `localhost:9000` (Docker) ou `localhost:8000` (`artisan serve`).

---

## 9. Riscos e mitigações

| Risco | Prob. | Impacto | Mitigação |
|---|---|---|---|
| Migration quebrar produção | Média | Alto | Aditivas + staging + backup + janela |
| Vazamento de dados entre tenants | Média | Crítico | Global scope + testes de isolamento + auditoria de `DB::table` |
| cPanel não suportar subdomínios/SSL | Baixa | Alto | Verificar antes da F2; fallback: path `/t/{slug}` |
| Driver pgsql sem branches no código | Média | Alto | Item 0.1 e revisão de raw SQL |
| Ranking/relatórios pesados (sync) | Média | Médio | Comandos por tenant + futuro queue `database` |
| Regressão no cliente atual | Média | Alto | Flag + tenant #1 na raiz + testes de caracterização |
| Código morto ser editado por engano | Alta | Médio | Limpeza/marcação no F0 |

---

## 10. Pendências

- [ ] **0.1** Confirmar driver real do banco em produção (cPanel).
- [ ] Definir domínio oficial do SaaS (para subdomínios).
- [ ] Definir política de limites por plano (F4).
