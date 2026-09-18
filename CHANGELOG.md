# Changelog

Todas as mudanças relevantes deste projeto são registradas neste arquivo.

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/).
A versão exibida no rodapé do sistema vem de `config/version.php` — mantenha os
dois arquivos em sincronia a cada release (ver `docs/operacao/VERSIONAMENTO.md`).

## [2.0.56] - 2026-09-18

### Corrigido
- `tenant:backfill` agora identifica o tenant raiz pelo **slug** (nunca por
  `Tenant::first()`), evitando atribuir os dados do cliente atual a outro cliente
  já cadastrado (causa do vazamento entre tenants na ativação).
- Diagnóstico de isolamento: alerta quando a flag multi-tenant está desligada com
  vários clientes cadastrados.

### Adicionado
- Comando `php artisan saas:doctor` — diagnóstico somente leitura (flag efetiva,
  tenants, dados por tenant e riscos de isolamento).
- Opção `--dry-run` no `tenant:backfill` (simula sem gravar).
- Runbook `docs/operacao/ATIVAR_MULTITENANT.md`.

## [2.0.55] - 2026-09-18

### Removido
- app/Modules/Exemplo/ (provider, controller, model, rota, view, migration e README — 7 arquivos)
- Os 2 testes que dependiam dele (test_modulo_de_exemplo... e test_rota_do_modulo...); no lugar, entrou um teste pequeno do provider base (slug)

### Adicionado
- app/Modules/.gitkeep — mantém a pasta app/Modules versionada (vazia, pronta para o Agendamento)
- database/migrations/2026_09_17_000400_drop_exemplo_registros_table.php — remove a tabela exemplo_registros se ela existir (no servidor, foi criada quando você rodou o migrate; em banco novo é no-op)

## [2.0.54] - 2026-09-18

### Alterado
- CHANGELOG.md e version.php com a versao correta em sincronia com o git hub

## [2.1.0] - 2026-09-17

### Adicionado
- Infraestrutura de módulos autocontidos em `app/Modules/<Nome>` (sem dependência
  externa): descoberta automática via `ModulesServiceProvider`, rotas/views/migrations/
  commands próprios (`App\Support\Modules\ModuleServiceProvider`).
- Registro de menu automático por módulo (`ModuleRegistry` + `registerMenu()`),
  filtrado por permissão do usuário — sem editar o layout a cada módulo.
- Guia `docs/modulos/GUIA_MODULOS.md` (como criar um módulo em 10 passos).
- Testes da infraestrutura modular (`tests/Feature/Modules`).

### Alterado
- Matriz de permissões agora lê os módulos de `config/modules.php` (fonte única;
  antes havia duplicação de labels no `PermissionController`).

## [2.0.0] - 2026-09-16

### Segurança
- Uploads com nome/extensão gerados no servidor (allowlist de MIME) em treinamentos,
  materiais (inclusive upload em chunks), splash, EPI, perfil e rede social.
- Bloqueio de execução de scripts em `public/storage` e `public/uploads` via `.htaccess`.
- Correção de escalação de privilégio: `role_id = super_admin` e campos sensíveis
  não são mais aceitos por `$request->all()` em usuários (web e API).
- Proteções contra exclusão/edição de super_admin por admins comuns e auto-exclusão.
- Rate limit no login web (`throttle:login`, 5/min por CPF+IP e 20/min por IP).
- Senha não fica mais no flash de sessão em falhas de login.
- Mascaramento de PII (CPF, e-mail, telefone) em validação pública de certificado
  e na ficha pública da API (LGPD).

### Adicionado
- Sistema de auditoria (`audit_logs`) com trilha de login/logout/falhas, CRUD de
  usuários, treinamentos, certificados, permissões, tenants, EPI, folgas e
  materiais, com mascaramento de dados sensíveis.
- Tela `/admin/auditoria` com filtros (usuário, evento, módulo, período, busca),
  exportação CSV e permissão dedicada (`auditoria`).
- Comando `audit:prune` (retenção configurável em `AUDIT_RETENTION_DAYS`) agendado
  diariamente.
- Controle de versão exibido no rodapé: `v2.0.0 — atualizado em 16/09/2026`.

### Corrigido
- Middleware de logs agora registra usuário/tenant após a resolução da sessão,
  usa IP correto atrás de proxy e mascara tokens em URLs.
- Exceções não são mais registradas em duplicidade no canal `system`.
- Rodapé do layout sem tag de abertura `<footer>` (HTML inválido).

## [1.0.0]

- Versão inicial da Platarforma DSS.
