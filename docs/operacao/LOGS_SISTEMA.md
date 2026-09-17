# Sistema de Logs Técnicos - Plataforma DSS

> Para a trilha de auditoria de negócio (quem criou/alterou/excluiu, logins,
> downloads), veja **`AUDITORIA.md`**. Este documento cobre os logs técnicos de
> requisições e exceções.

## Objetivo
Facilitar investigação de falhas, rastreabilidade de requisições e diagnóstico rápido em produção.

## O que está implementado
- Canal dedicado `system` em `config/logging.php` (arquivo diário `storage/logs/system-AAAA-MM-DD.log`, retenção padrão 30 dias).
- Middleware global `app/Http/Middleware/LogSystemRequests.php` para todas as requisições HTTP.
- Exceções não tratadas são registradas **apenas** pelo middleware (`request_exception`), evitando duplicidade.

## Eventos registrados
- `request_completed`: toda requisição com status, tempo, usuário e tenant.
- `request_exception`: exceção durante o ciclo da requisição (com classe e mensagem).
- `audit_log_failed`: falha ao gravar um registro de auditoria (não interrompe o fluxo).

## Campos do log de requisição
- `request_id` (validado: `[A-Za-z0-9._-]{1,64}`; caso contrário é gerado)
- `method`, `path`, `full_url`
- `ip` (capturado após o `TrustProxies`, respeitando `TRUSTED_PROXIES`)
- `user_id`, `tenant_id` (capturados **após** a resolução da sessão/tenant)
- `user_agent`
- `status`, `duration_ms`, `route`

## Mascaramento de dados sensíveis
- Query strings com `token`, `codigo`, `password`, `api_key`, `secret`, etc. são gravadas como `***`.
- Paths `/ficha/{token}` e `/validar/{codigo}` são gravados como `ficha/***` e `validar/***`.
- Corpo da requisição e headers (exceto `X-Request-Id`/`User-Agent`) **não** são registrados.

## Configuração via .env
- `LOG_CHANNEL=stack`
- `LOG_LEVEL=debug`
- `LOG_SYSTEM_LEVEL=debug`
- `LOG_SYSTEM_DAYS=30`
- `LOG_SYSTEM_PATH=` (vazio usa `storage/logs/system.log` com sufixo diário)

Em produção, use `LOG_SYSTEM_LEVEL=info` e eleve para `debug` apenas durante incidentes.

## Como investigar incidentes
1. Filtrar erros 500:
```powershell
Select-String -Path .\storage\logs\system-*.log -Pattern '"status":500|request_exception'
```

2. Filtrar por usuário:
```powershell
Select-String -Path .\storage\logs\system-*.log -Pattern '"user_id":123'
```

3. Filtrar por rota específica:
```powershell
Select-String -Path .\storage\logs\system-*.log -Pattern '"path":"treinamentos/4/avaliacao"'
```

4. Acompanhar em tempo real (host/repositório):
```powershell
Get-Content .\storage\logs\system-$(Get-Date -Format yyyy-MM-dd).log -Wait
```

## Boas práticas
- Não gravar senha, token bruto ou payload sensível no log (novos campos devem seguir o mascaramento do middleware).
- Revisar periodicamente o volume de logs e a retenção (`LOG_SYSTEM_DAYS`).
- Incidentes de segurança (tentativas de login, alterações suspeitas) devem ser
  investigados na tela de auditoria (`AUDITORIA.md`), que preserva o histórico em banco.
