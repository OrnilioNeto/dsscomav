# Lembretes de Treinamento via WhatsApp

Módulo `lembretes_whatsapp` — dispara lembretes individuais aos colaboradores que
ainda não concluíram um treinamento, usando o gateway **WA-AKG** (Baileys) como
transporte. O DSS orquestra seleção, personalização, ritmo e histórico; o gateway
apenas envia.

## Como funciona

1. Acesse **Lembretes WhatsApp** no menu (`/admin/lembretes-whatsapp`), aba **Disparo**:
   - **Panorama**: todos os treinamentos cadastrados com elegíveis, concluídos,
     pendentes e situação (ativo/inativo, liberado/não liberado). Contagens ficam
     em cache por 10 minutos (botão "Atualizar contagens" com `?refresh=1`).
   - Clique em **Lembrar** no treinamento desejado para abrir a lista de pendentes.
2. A lista de pendentes (pendente = iniciou e não concluiu; não iniciado = sem
   registro de progresso) mostra telefone, situação e aviso de "já enviado hoje",
   com filtros de tipo de usuário e busca por nome/CPF. O gestor marca/desmarca
   quem quiser (lote máximo `WA_BATCH_LIMIT`, padrão 30) e edita a mensagem.
3. Ao confirmar, o DSS **valida os números no WhatsApp** (`POST /api/chat/{sessionId}/check`)
   e grava o **JID canônico** de cada colaborador — isso corrige o caso do nono
   dígito, em que o número só entrega no formato sem o `9`. Quem não existe no
   WhatsApp vira `pulado` ("número não encontrado"), em vez de mensagem que
   nunca chega. O DSS cria um registro por colaborador em `training_reminders`
   com status `fila` e horários escalonados (intervalo aleatório de 300–600s).
   Telefone ausente/inválido também vira `pulado`.
4. O comando agendado `lembretes:processar` roda **a cada 5 minutos, somente na
   janela configurada** (`app/Console/Kernel.php`) e envia **uma mensagem por vez**
   por tenant, via `POST /api/messages/{sessionId}/{jid}/send` do WA-AKG, respeitando:
   - janela de envio (padrão 08:00–18:00, dias úteis);
   - teto diário (`WA_DAILY_CAP`, padrão 40);
   - sessão `CONNECTED` no gateway;
   - pausa automática após N falhas consecutivas (`WA_MAX_CONSECUTIVE_FAILURES`).
   Com a fila vazia o comando sai após um único `SELECT` — não consulta o gateway.

Atalhos no relatório de treinamentos (`/relatorios/treinamentos`): os botões
**Lembrar pendentes** (Foco no Treinamento) e **Lembrar** (Desempenho por
Conteúdo) abrem a tela de disparo já com o treinamento selecionado.

### Enviar agora (teste/urgência)

Além de **Enfileirar** (respeita agendamento, janela e escalonamento), o botão
**Enviar agora** agenda a seleção para o instante atual e processa a fila na
mesma requisição (`lembretes:processar --force`). Regras:

- exige integração ativa (`WA_REMINDERS_ENABLED=true`) e sessão `CONNECTED`;
  se falhar, nada é enfileirado e o motivo aparece na tela;
- continua respeitando o **teto diário** (`WA_DAILY_CAP`);
- ignora janela de horário e pausa por falhas — use para testes locais e casos
  pontuais; em produção prefira o envio escalonado.

No teste local, sem o scheduler rodando, use **Enviar agora** (ou execute
`php artisan lembretes:processar --force` manualmente). A tela mostra o status da
sessão no topo (badge ao vivo) e deixa o **Enviar agora** desabilitado quando a
integração está desativada ou a sessão desconectada.

## Permissão

- Módulo: `lembretes_whatsapp` (ver `config/modules.php`).
- Ver as telas: `can_view`. Disparar/cancelar: `can_edit`.
- Os atalhos no relatório aparecem apenas para quem tem a permissão de edição.

## Configuração (`.env`)

```env
WA_REMINDERS_ENABLED=false        # liga o processamento
WA_GATEWAY_URL=http://localhost:3030
WA_GATEWAY_API_KEY=wag_xxxxxxxx   # chave gerada no dashboard do WA-AKG
WA_GATEWAY_SESSION=dss            # sessionId conectado via QR no WA-AKG
WA_DAILY_CAP=40
WA_BATCH_LIMIT=30
WA_DELAY_MIN=300
WA_DELAY_MAX=600
WA_WINDOW_START=08:00
WA_WINDOW_END=18:00
WA_ALLOW_WEEKENDS=false
WA_MAX_CONSECUTIVE_FAILURES=5
WA_WEBHOOK_SECRET=um-segredo-forte
```

O texto padrão fica em `config/whatsapp.php` (`default_message`) e pode ser
editado a cada disparo. Placeholders: `{nome}` e `{treinamento}`.
Não há link na mensagem por padrão (menor risco de bloqueio).

## Status de entrega (webhook)

`enviado` no histórico significa que o **gateway aceitou** a mensagem; a entrega
real chega pelo webhook `message.status` do WA-AKG:

1. No DSS, defina `WA_WEBHOOK_SECRET` (HMAC) e rode `php artisan config:clear`.
2. Registre o webhook no WA-AKG apontando para o DSS (o segredo deve ser o mesmo):
   ```bash
   curl -X POST "http://localhost:3000/api/webhooks/SEU_SESSION_ID" \
     -H "X-API-Key: SUA_API_KEY" -H "Content-Type: application/json" \
     -d '{"name":"DSS Lembretes","url":"https://SEU_DSS/api/lembretes-whatsapp/status","secret":"um-segredo-forte","events":["message.status"]}'
   ```
   Em Docker local (WA-AKG → DSS no host): use `http://host.docker.internal:9000/...`.
3. A coluna **Entrega** do histórico passa a mostrar `Entregue`/`Lido`/`Falha`/
   `Enviado (sem confirmação)`. Sem o webhook, ela fica `—`.

## Requisitos do gateway

- WA-AKG no ar (Node 20+). Em desenvolvimento: `docker compose up -d` no projeto
  do gateway e conectar a sessão pelo dashboard (QR).
- Gerar a **API key** no dashboard do WA-AKG e configurar `WA_GATEWAY_API_KEY`.
- Confira a porta no `.env` do WA-AKG (`PORT`): neste ambiente está **3000** (o
  runtime usa 3030 como padrão). Ajuste `WA_GATEWAY_URL` para a mesma porta.
- **DSS e gateway em Docker**: dentro do container do DSS, `localhost` aponta para
  ele mesmo. Use `http://host.docker.internal:3000` (Docker Desktop) ou conecte os
  dois containers na mesma rede e use `http://<container-do-gateway>:3000`.
- Deixe o anti-spam do WA-AKG ativo. O DSS já espaça os envios; os dois somados
  reduzem o risco, mas **Baileys é não-oficial e ban é sempre possível**.

## Boas práticas anti-ban

- Use um **chip dedicado** (nunca o WhatsApp pessoal do gestor) e aqueça o número:
  poucas mensagens/dia na primeira semana, subindo aos poucos.
- Comece com `WA_DAILY_CAP` baixo (20–40) e lotes de 20–30.
- Não remova os intervalos aleatórios nem envie fora da janela comercial.
- Evite reenviar todos os dias para as mesmas pessoas (a tela de disparo avisa
  quem já recebeu hoje; a decisão é do gestor).
- Em caso de muitas falhas (números inválidos), o comando pausa sozinho —
  verifique as falhas no histórico antes de retomar.

## Comandos

```bash
# processa a fila (roda pelo scheduler; manualmente para testar)
php artisan lembretes:processar

# simula sem enviar
php artisan lembretes:processar --dry-run

# envia mais de um por execução (cuidado com o teto/intervalos)
php artisan lembretes:processar --limit=5

# ignora janela e pausa por falhas (uso excepcional)
php artisan lembretes:processar --force

# diagnóstico da integração (config, sessão no gateway e fila)
php artisan lembretes:diagnostico
```

Produção exige cron chamando `php artisan schedule:run` a cada minuto (o mesmo
cron do ranking). No desenvolvimento local, use `php artisan schedule:work`.

## Tabela `training_reminders`

`tenant_id`, `user_id`, `training_id`, `telefone`, `jid`, `mensagem`, `status`
(`fila|enviado|falhou|pulado|cancelado`), `agendado_para`, `enviado_em`,
`gateway_message_id`, `erro`, `created_by`. As telas ficam em
`/admin/lembretes-whatsapp/disparo` (aba **Disparo**) e
`/admin/lembretes-whatsapp` (aba **Histórico**) — permissão `lembretes_whatsapp`.

## Migração/deploy

```bash
php artisan migrate --force   # cria training_reminders
```

O módulo é descoberto automaticamente (`app/Modules/LembretesWhatsapp`), sem
registro manual em `config/app.php`. Em multi-tenancy, o comando itera os
tenants ativos (`TenantManager::runForEachTenant`); credenciais por tenant ainda
não existem — a configuração é global nesta versão.
