# Ativar o Multi-Tenancy (isolamento por cliente)

Runbook para ativar o isolamento por cliente com segurança. Referências:
`config/saas.php`, `docs/SAAS_PLANO.md`, `docs/SAAS_RUNBOOK_DEPLOY.md` e
`docs/modulos/GUIA_MODULOS.md`.

> Regra de ouro: **backfill antes de ligar a flag**. Com a flag ligada e dados
> sem `tenant_id`, o cliente vê um sistema vazio; com a flag desligada e vários
> clientes, todos veem todos os dados.

## Pré-requisitos

- Código com o `tenant:backfill` corrigido (identifica o tenant raiz pelo **slug**,
  nunca por `Tenant::first()`) e com o comando `saas:doctor`.
- **Backup do banco** antes de qualquer passo.
- Acesso ao app root no servidor (ex.: `/home/USUARIO/repositories/dsscomavprod`).
- Editar sempre o `.env` **do app root** (não o de `public_html`).

## Passo a passo

1. **Backup do banco** (cPanel → phpMyAdmin/Backup ou `mysqldump`).

2. **Atualizar o código**:
   ```bash
   cd /home/USUARIO/repositories/dsscomavprod
   git pull
   composer install --no-dev --optimize-autoloader
   php artisan optimize:clear
   ```

3. **Diagnóstico do cenário atual** (somente leitura):
   ```bash
   php artisan saas:doctor
   ```

4. **Backfill do tenant raiz** (cliente atual) — simule e depois aplique:
   ```bash
   php artisan tenant:backfill --name="NOME DO CLIENTE ATUAL" --slug=cliente --dry-run
   php artisan tenant:backfill --name="NOME DO CLIENTE ATUAL" --slug=cliente
   ```
   - Cria/seleciona o tenant pelo slug (`cliente`) e preenche `tenant_id` **apenas
     onde está NULL**.
   - Super admins permanecem com `tenant_id = NULL` (usuários da plataforma).
   - `role_permissions` herdam o tenant das roles; `tenant_modules` são habilitados.
   - **Nunca** use a versão antiga do comando (que usava o primeiro tenant).

5. **Conferir o resultado**:
   ```bash
   php artisan saas:doctor
   ```
   Esperado: 2+ tenants; o tenant `cliente` com os dados do cliente atual; nenhum
   alerta crítico; nenhum usuário não-super_admin com `tenant_id NULL`.

6. **Ligar a flag** no `.env` do app root:
   ```env
   SAAS_MULTITENANT_ENABLED=true
   SAAS_ROOT_DOMAIN=seudominio.com
   ```
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   ```

7. **Testar**:
   - `https://seudominio.com` → login do cliente atual.
   - `https://seudominio.com/plataforma` → super admin.
   - Subdomínio de um cliente → login do **admin do cliente** (não use o super
     admin: ele vê todos os dados por design).
   - CPF de um cliente não loga no subdomínio de outro.

## Provisionar cada cliente novo

1. Painel `/plataforma` → criar cliente (slug = label do subdomínio; `dominio` vazio).
2. cPanel → **Domains → Create A New Domain**: `slug.seudominio.com`
   **compartilhando o DocumentRoot** do domínio principal.
3. cPanel → **SSL/TLS Status** → Run AutoSSL (grátis).
4. Painel → editar cliente → **Criar Admin** (nome, CPF, senha).
5. Testar o login em `https://slug.seudominio.com/login`.

Domínio próprio do cliente: ele cria o registro DNS (A) apontando para o IP do
servidor → você adiciona como Addon Domain no cPanel com o mesmo DocumentRoot →
Run AutoSSL → preenche o campo `dominio` no painel (host puro, sem `https://`).

## Rollback

1. No `.env` do app root: `SAAS_MULTITENANT_ENABLED=false`
2. `php artisan optimize:clear && php artisan config:cache`
3. Os `tenant_id` já preenchidos permanecem (inofensivo) e a migração pode ser
   retomada depois.

## Alertas importantes

- Não crie subdomínios de clientes com a flag desligada.
- Não rode `git clean -fd` no servidor (apagaria uploads em `storage/app/public`
  e `public/uploads`).
- Cada subdomínio precisa de vhost + AutoSSL no cPanel (sem wildcard no plano atual).
- Host desconhecido com a flag ligada responde 404 “Empresa não encontrada”.
