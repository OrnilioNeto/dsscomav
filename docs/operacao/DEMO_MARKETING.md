# Dados de demonstração (marketing) e testes

## Ativar modo demo

No `.env`:

```env
DEMO_MODE=true
```

Reinicie o `php artisan serve`. O sistema passará a usar o banco SQLite `database/demo.sqlite` (criado automaticamente).

## Preparar o banco demo (uma vez)

```powershell
$env:DEMO_MODE="true"
php artisan demo:prepare
```

Cria o arquivo SQLite, roda migrations/seeders padrão e popula 14 usuários fictícios + ranking.

## Refazer dados demo

```powershell
$env:DEMO_MODE="true"
php artisan demo:reset
php artisan demo:seed
```

## Logins demo

- Colaborador: CPF gerado no seeder (lista no console) / senha `Demo@2026`
- Admin padrão: `11111111111` / `admin123`
- Super admin: `10178415430` / `@Machado2025`

## Modo testes com dump real

1. Desative o modo demo: `DEMO_MODE=false`
2. Aponte `DB_DATABASE` para um banco de teste (ex.: `dss_db_test`) e importe o dump.
3. Anonimize PII:

```powershell
php artisan demo:anonymize --force --truncate-audit --mark-test
```

Senha após anonimização: `Teste@123` (configurável em `config/demo.php`).

## Proteção

Os comandos `demo:*` abortam em `APP_ENV=production` sem `--force`.