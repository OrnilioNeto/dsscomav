# Auditoria — Plataforma DSS

Sistema de trilha de auditoria: registra **quem** fez, **o quê**, **quando**,
**de onde** (IP) e os **valores antes/depois** das principais operações.

## 1. Onde ficam os dados

Tabela `audit_logs` (model `App\Models\AuditLog`, migration
`2026_09_16_000100_create_audit_logs_table.php`):

| Coluna | Descrição |
|---|---|
| `tenant_id` | Tenant da operação (multi-tenancy) |
| `user_id` | Usuário que executou (nulo em falhas de login de usuário inexistente) |
| `event` | `created`, `updated`, `deleted`, `login`, `login_failed`, `login_blocked`, `logout`, `downloaded`, `exported`, `accessed` |
| `module` | `users`, `trainings`, `certificates`, `rankings`, `splash`, `social`, `epi`, `folgas`, `projeto_pedagogico`, `permissions`, `plataforma`, `auth`, `auditoria` |
| `auditable_type` / `auditable_id` | Classe e ID do registro afetado |
| `description` | Texto legível da ação |
| `old_values` / `new_values` | JSON com os campos alterados (**dados sensíveis mascarados**) |
| `ip`, `user_agent`, `route`, `method` | Contexto da requisição |
| `created_at` | Data/hora (fuso `America/Sao_Paulo`) |

Índices: `tenant_id+created_at`, `user_id+created_at`, `event`, `module`,
`auditable_type+auditable_id`.

## 2. Como a captura funciona

1. **CRUD automático** — trait `App\Models\Concerns\Auditable` (hooks
   `created`/`updated`/`deleted`) nos models: `User`, `Role`, `RolePermission`,
   `Tenant`, `Training`, `TrainingMaterial`, `Certificate`, `UserVacation`,
   `EmployeeTraining`, `SplashContent`, `SocialPost`, `ProjetoPedagogico`,
   `Epi`, `EpiEntrega`, `RankingRule`, `RankingCriterion`.
2. **Autenticação** — listeners de `Login`, `Failed` e `Logout` registrados em
   `AppServiceProvider`; falhas e bloqueios manuais registrados em
   `AuthController` (web) e `Api\AuthController`.
3. **Downloads/exportações** — chamadas explícitas em certificados, materiais e
   exportação CSV da própria auditoria.
4. **Gravação resiliente** — `App\Services\AuditLogger` nunca lança exceção: se a
   auditoria falhar, gera `audit_log_failed` no canal `system` e o fluxo continua.

## 3. O que é mascarado

Campos `password`, `password_confirmation`, `remember_token`, `qrcode_token`,
`token`, `access_token`, `api_key` e `secret` viram `***`. CPF exibido em falhas
de login é mascarado (`***.222.222-**`).

## 4. Tela `/admin/auditoria`

- Acesso: permissão **Auditoria** (`auditoria,view`) — default super_admin,
  concedível em `/admin/permissoes`.
- Filtros: busca (descrição/rota/IP), usuário, evento, módulo, período (`de`/`até`).
- Paginação de 25 registros; coluna "Detalhes" mostra o JSON antes/depois.
- **Exportar CSV** (`/admin/auditoria/exportar`): respeita os filtros ativos,
  separador `;`, UTF-8 com BOM (abre no Excel). A exportação é auditada
  (`exported`).

## 5. Retenção e limpeza

- `config/audit.php` → `AUDIT_RETENTION_DAYS` (default **365**).
- Comando: `php artisan audit:prune {--days=} {--tenant=}`.
- Agendado diariamente às 03:15 em `app/Console/Kernel.php` (requer cron no deploy).
- Em multi-tenant, o comando percorre cada tenant e também limpa registros de
  plataforma (`tenant_id` nulo).

## 6. Consultas úteis (SQL)

```sql
-- Últimas ações de um usuário
SELECT created_at, event, module, description, ip
FROM audit_logs WHERE user_id = 123 ORDER BY created_at DESC LIMIT 50;

-- Exclusões dos últimos 7 dias
SELECT created_at, user_id, module, description
FROM audit_logs WHERE event = 'deleted' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Tentativas de login falhas por IP
SELECT ip, COUNT(*) total FROM audit_logs
WHERE event IN ('login_failed','login_blocked')
GROUP BY ip ORDER BY total DESC LIMIT 20;
```

## 7. Boas práticas

- Não altere registros de auditoria manualmente; use `audit:prune` para retenção.
- Ao adicionar novos models administrativos, inclua a trait `Auditable`.
- Para ações que não são CRUD (download, exportação, acesso), chame
  `app(\App\Services\AuditLogger::class)->log('downloaded', [...])`.
