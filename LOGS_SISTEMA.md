# Sistema de Logs - Plataforma DSS

## Objetivo
Este documento descreve o sistema de logs implementado para facilitar investigacao de falhas, rastreabilidade de requisoes e diagnostico rapido em producao.

## O que foi implementado
- Canal dedicado `system` em [config/logging.php](config/logging.php)
- Middleware global de log para todas as requisicoes HTTP em [app/Http/Middleware/LogSystemRequests.php](app/Http/Middleware/LogSystemRequests.php)
- Registro global de excecoes nao tratadas em [app/Exceptions/Handler.php](app/Exceptions/Handler.php)

## Cobertura
A cobertura inclui todas as funcionalidades acessadas por HTTP, porque o middleware foi registrado globalmente em [app/Http/Kernel.php](app/Http/Kernel.php).

Eventos registrados:
- `request_completed`: toda requisicao com status, tempo e usuario
- `request_exception`: excecao durante o ciclo da requisicao
- `unhandled_exception`: excecao nao tratada no Handler

## Arquivos de log
- Log padrao Laravel: `storage/logs/laravel.log`
- Log dedicado do sistema: `storage/logs/system-YYYY-MM-DD.log`

Observacao:
- O canal `system` esta configurado como `daily` com retencao padrao de 30 dias.
- Em ambiente Docker deste projeto, a pasta `storage/logs` esta espelhada com o host, entao os arquivos ficam visiveis diretamente no repositorio.

## Configuracao via .env
Use estas variaveis:

- `LOG_CHANNEL=stack`
- `LOG_LEVEL=debug`
- `LOG_SYSTEM_LEVEL=debug`
- `LOG_SYSTEM_DAYS=30`
- `LOG_SYSTEM_PATH=`

Se `LOG_SYSTEM_PATH` ficar vazio, sera usado `storage/logs/system.log` (com sufixo diario).

## Campos gravados no log de requisicao
No evento `request_completed`:
- `request_id`
- `method`
- `path`
- `full_url`
- `ip`
- `user_id`
- `user_agent`
- `status`
- `duration_ms`
- `route`

## Como investigar incidentes
1. Filtrar erros 500:
```powershell
Select-String -Path .\storage\logs\system-*.log -Pattern '"status":500|request_exception|unhandled_exception'
```

2. Filtrar por usuario:
```powershell
Select-String -Path .\storage\logs\system-*.log -Pattern '"user_id":123'
```

3. Filtrar por rota especifica:
```powershell
Select-String -Path .\storage\logs\system-*.log -Pattern '"path":"treinamentos/4/avaliacao"'
```

4. Acompanhar em tempo real (container):
```powershell
docker-compose exec -T app-dss sh -lc "tail -f storage/logs/system-$(date +%F).log"
```

5. Acompanhar em tempo real (host/repositorio):
```powershell
Get-Content .\storage\logs\system-$(Get-Date -Format yyyy-MM-dd).log -Wait
```

## Boas praticas
- Nao gravar senha, token bruto ou payload sensivel no log.
- Em producao, usar `LOG_SYSTEM_LEVEL=info` e elevar para `debug` apenas durante incidente.
- Revisar periodicamente o volume de logs e retencao (`LOG_SYSTEM_DAYS`).

## Checklist de ativacao em producao
1. Publicar o codigo.
2. Garantir `LOG_CHANNEL=stack` no `.env`.
3. Ajustar variaveis `LOG_SYSTEM_*` se necessario.
4. Limpar cache:
```powershell
docker-compose exec -T app-dss php artisan optimize:clear
```
5. Validar geracao de arquivo em `storage/logs`.

## Observacoes tecnicas
- O middleware de logs nao interrompe o fluxo da aplicacao.
- Excecoes continuam sendo lancadas normalmente apos registro.
- O sistema de log foi desenhado para funcionar mesmo quando ocorrer erro interno em controllers/servicos.
