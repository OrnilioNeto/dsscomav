# Runbook — Deploy e Backup (cPanel/ValueHost)

> Documento operacional da Plataforma DSS. Complementa `docs/SAAS_PLANO.md`.
> Regra de ouro: **sempre testar em localhost antes de subir** (migrations + testes + smoke).

---

## 1. Antes de qualquer deploy

- [ ] Rodar testes: `php -d extension=gd -d extension=fileinfo vendor/phpunit/phpunit/phpunit` → **OK (19/19)**.
- [ ] Validar migrations do zero em MySQL: criar `dss_migration_check` no Docker e `php artisan migrate --force`.
- [ ] Backup completo (seção 3).
- [ ] Anotar versão anterior (git log -1) para rollback.

## 2. Deploy

```bash
# No cPanel / terminal do servidor
composer install --no-dev --optimize-autoloader
php artisan migrate --force          # OBRIGATÓRIO — auto-migrate foi desligado (F0)
php artisan tenant:backfill --name="<Cliente Atual>" --slug=cliente   # UMA VEZ, após o primeiro deploy
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Ordem de ativação do multi-tenancy (quando pronto):**

1. Backups completos.
2. `migrate --force` + `tenant:backfill` (cliente atual = tenant #1).
3. Smoke test na raiz (login, dashboard, treinamento, certificado).
4. Definir `SAAS_ROOT_DOMAIN` e `SAAS_MULTITENANT_ENABLED=true` no `.env`.
5. Smoke test novamente + criar tenant de teste (subdomínio no cPanel + AutoSSL).
6. Janela de manutenção curta para a troca de uniques (já embutida nas migrations).

## 3. Backup

### Banco de dados

```bash
# MySQL/MariaDB (cPanel: phpMyAdmin export também funciona)
mysqldump -u USUARIO -p BANCO > backup_$(date +%Y%m%d_%H%M).sql
```

### Arquivos (uploads e storage)

```bash
zip -r uploads_backup.zip public/uploads storage/app/public
```

- `public/uploads` — fotos de perfil, splash, social (web).
- `storage/app/public` — materiais de apoio, fotos de EPI, projetos pedagógicos.

### Verificação do backup

- [ ] `.sql` abre sem erro (`grep -c "INSERT INTO" backup.sql` > 0).
- [ ] ZIP tem arquivos (`unzip -l uploads_backup.zip | tail -1`).

## 4. Rollback

1. Restaurar backup do banco (substituir arquivo `.sql` + importar).
2. Restaurar backup de uploads.
3. Reverter código com git (ou upload do zip anterior).
4. `php artisan config:cache` + `php artisan view:clear`.

> Migrations novas são aditivas (não quebram o código anterior), mas se precisar
> reverter um schema, restaure o banco — não use `migrate:rollback` em produção
> sem entender as dependências.

## 5. Staging com 2 tenants (antes de ativar a flag)

1. `tenant:backfill` no banco de staging → tenant #1.
2. Painel `/plataforma` (super admin): criar tenant "Empresa Beta" (slug `empresa-beta`).
3. No cPanel, criar subdomínio `empresa-beta.dominio.com` apontando para o mesmo docroot + AutoSSL.
4. Criar um usuário no tenant beta (pelo painel admin de um tenant) e testar:
   - Login no subdomínio beta com CPF do beta → OK.
   - Login no subdomínio beta com CPF do tenant #1 → **deve falhar**.
   - Módulo desligado no painel `/plataforma` → 403 no módulo.
   - Uploads caem em `uploads/{tenant_id}/...` e `storage/app/public/tenants/{tenant_id}/...`.

## 6. Cron (após deploy)

```cron
0 6 * * *  cd /home/USUARIO/plataforma && php artisan ranking:recalculate --force >> storage/logs/ranking.log 2>&1
0 7 * * *  cd /home/USUARIO/plataforma && php artisan ranking:consolidate --force >> storage/logs/ranking.log 2>&1
```

(Com a flag SaaS ativa, os comandos já iteram por tenant.)

## 7. Checklist pós-deploy

- [ ] Raiz do domínio: login + dashboard (cliente atual).
- [ ] Emitir certificado e validar na rota pública.
- [ ] Ficha QR.
- [ ] `/plataforma` acessível apenas pelo super admin.
- [ ] Logs: `storage/logs/` sem erros novos (`request_exception`).
- [ ] Testes verdes em localhost com o mesmo código.