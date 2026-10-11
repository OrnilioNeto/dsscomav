# Changelog

Todas as mudanças relevantes deste projeto são registradas neste arquivo.

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/).
A versão exibida no rodapé do sistema vem de `config/version.php` — mantenha os
dois arquivos em sincronia a cada release (ver `docs/operacao/VERSIONAMENTO.md`).
## [2.0.72] - 2026-10-04

### Adicionado
- Validação do número no WhatsApp antes de enfileirar (gateway
  `POST /api/chat/{sessionId}/check`): grava o **JID canônico** retornado, o que
  corrige os casos em que o número só entrega no formato antigo (sem o nono
  dígito). Números que não existem no WhatsApp viram `pulado` com o motivo,
  em vez de mensagem que fica presa em "enviado".
- Webhook de status de entrega `message.status` do WA-AKG → DSS
  (`POST /api/lembretes-whatsapp/status`, assinatura HMAC via `WA_WEBHOOK_SECRET`)
  com as colunas `delivery_status`/`delivered_at` e coluna **Entrega** no
  histórico (`enviado` ≠ `entregue`).
- Diagnóstico mostra se o segredo do webhook está configurado.

## [2.0.71] - 2026-10-04

### Adicionado
- Comando `php artisan lembretes:diagnostico`: mostra a configuração da
  integração (flag, URL, sessão, API key mascarada, janela, cache de config),
  o status real da sessão no WA-AKG e as contagens da fila por tenant.
- Badge ao vivo do status da sessão (reutilizável) na tela de **Disparo**; o
  botão **Enviar agora** fica desabilitado com explicação no tooltip quando a
  integração está desativada ou a sessão desconectada.
- Dicas de rede Docker (`host.docker.internal`) e porta do WA-AKG (`PORT`) na
  documentação do módulo.

## [2.0.70] - 2026-10-04

### Adicionado
- Botão **Enviar agora** na tela de disparo dos Lembretes WhatsApp: envia a
  seleção imediatamente (`lembretes:processar --force` na mesma requisição,
  ignorando agendamento e janela), útil para testes locais e urgências. Exige
  integração ativa e sessão conectada (senão nada é enfileirado) e mantém o
  teto diário; o flash informa enviados/falhas/na fila.

## [2.0.69] - 2026-10-04

### Adicionado
- Tela **Disparo** no módulo Lembretes WhatsApp
  (`/admin/lembretes-whatsapp/disparo`): panorama de **todos os treinamentos**
  com elegíveis, concluídos e pendentes (contagens em cache por 10 min, com
  atualização manual) e lista completa dos pendentes do treinamento escolhido,
  com filtros (tipo de usuário, nome/CPF), seleção em massa e envio em lote.
- Navegação por abas **Disparo | Histórico** no módulo.

### Alterado
- Atalhos "Lembrar pendentes" (Foco no Treinamento) e "Lembrar" (Desempenho por
  Conteúdo) do relatório agora abrem a tela de disparo já com o treinamento
  selecionado (modal removido).
- Disparo em lote passa a recusar treinamentos inativos ou ainda não liberados e
  usa formulário padrão com redirect + mensagem de sucesso (mantém resposta JSON
  para chamadas assíncronas).

## [2.0.68] - 2026-10-04

### Alterado
- Processador de lembretes WhatsApp (`lembretes:processar`) passa a rodar a cada
  **5 minutos** e somente na janela configurada (padrão 08:00–18:00), reduzindo de
  1.440 para ~120 execuções/dia.
- O comando verifica a fila antes de qualquer consulta pesada: com a fila vazia,
  encerra sem contar teto/falhas e **sem consultar a sessão no gateway**.
- Intervalo padrão entre lembretes ajustado para 300–600s (`WA_DELAY_MIN`/
  `WA_DELAY_MAX`), coerente com a nova cadência do processador (textos do modal,
  histórico e documentação atualizados).

## [2.0.67] - 2026-10-04

### Adicionado
- Módulo **Lembretes WhatsApp** (`lembretes_whatsapp`, `app/Modules/LembretesWhatsapp`):
  botão "Lembrar pendentes" no relatório de treinamentos que lista os colaboradores
  que não concluíram o conteúdo, permite selecionar quem recebe, editar a mensagem
  (placeholders `{nome}` e `{treinamento}`) e enfileira envios personalizados.
- Integração com o gateway **WA-AKG** (Baileys) por envio unitário
  (`POST /api/messages/{sessionId}/{jid}/send`), com cURL nativo e configuração
  em `config/whatsapp.php` (`.env`: `WA_GATEWAY_URL`, `WA_GATEWAY_API_KEY`,
  `WA_GATEWAY_SESSION` etc.).
- Comando `lembretes:processar` (agendado a cada minuto) que envia um a um, com
  intervalo aleatório de 45–180s, janela 08:00–18:00 em dias úteis, teto diário,
  checagem da sessão e pausa automática após falhas consecutivas.
- Histórico em `/admin/lembretes-whatsapp` (filtros, KPI, cancelamento de itens
  na fila, indicador da sessão do gateway) e tabela `training_reminders`.
- Helper `phone_to_jid()` (normalização de telefone brasileiro para JID) e aviso
  no modal de quem já recebeu lembrete no dia (reenvio fica a critério do gestor).
- Documentação em `docs/modulos/LEMBRETES_WHATSAPP.md` (inclui boas práticas
  anti-ban e operação do gateway).

## [2.0.66] - 2026-10-03

### Adicionado
- Data de **liberação** do conteúdo (segunda-feira agendada) exibida nas listas e
  cards: dashboard do usuário, lista de treinamentos, detalhe, campos
  "Treinamento" dos relatórios (treinamentos, certificados, auditoria e IA),
  isenções de férias e reassistir. Quando não há data de liberação, usa a
  publicação e, por último, a criação do registro.
- Regra única de elegibilidade em `User::eligibleForContent()`: cadastro após a
  semana de liberação, público-alvo/atribuição, isenção de férias do conteúdo e
  férias na data de liberação.

### Alterado
- Listas de conteúdo passam a ser ordenadas pela data de liberação (liberados
  mais recentes primeiro; bloqueados com a próxima liberação primeiro) em vez da
  data de cadastro.
- Relatórios de conteúdo (tela de treinamentos, PDFs, CSV, auditoria e relatórios
  com IA) não contabilizam mais usuários inelegíveis, isentos por férias ou em
  férias na data de liberação — em totais, taxas, listas de pendentes/concluídos/
  não iniciados e resumo por conteúdo.
- Taxa de conclusão (`Training::getTaxaConclusao`) passa a usar a base elegível
  do conteúdo.

### Corrigido
- Usuários sem liberação para o conteúdo ou em férias apareciam como "não
  iniciados" e inflavam as taxas em relatórios e dashboards.

## [2.0.65] - 2026-10-02

### Adicionado
- Botão **Remover fundo** do certificado, disponível no painel da Plataforma
  (`/plataforma/configuracoes`, fundo padrão) e no cadastro de cada cliente
  (`/plataforma/{cliente}`). Ao remover, o arquivo é apagado e o certificado
  volta a usar o fundo padrão (cliente → fundo padrão da plataforma → imagem
  padrão do sistema).

## [2.0.64] - 2026-10-02

### Corrigido
- Erro 500 ao baixar certificado novo com selo **REASSISTIDO**: a chamada
  `RoundedRect` do TCPDF recebia o estilo no argumento errado. Corrigido e
  coberto por teste.
- Geração do certificado com rede de segurança: se o modelo novo falhar,
  registra no log e entrega o layout legado (não retorna mais 500).

### Adicionado
- Página **/plataforma/configuracoes** — fundo padrão do certificado da empresa
  raiz (sem tenant), com upload pelo super admin (nova tabela
  `platform_settings`).
- Resolução do fundo: tenant → fundo padrão da plataforma → imagem padrão do
  sistema → moldura.

### Alterado
- QR Code só é desenhado se o retorno for um PNG válido; recorte do logo agora
  usa `storage/app/certificado` (evita restrição de open_basedir) e converte
  WebP para PNG quando possível.

## [2.0.63] - 2026-10-02

### Adicionado
- Certificado único e profissional (A4 paisagem) para todos os **novos**
  certificados: fundo azul/dourado, título, narrativa, dados completos
  (beneficiário, treinamento, carga horária, início/fim, tempo assistido,
  instrutor), QR Code e assinaturas.
- Campo **Fundo do certificado** no painel da Plataforma (`/plataforma` →
  cliente) para o super admin enviar a imagem de base; sem upload, usa a
  imagem padrão `public/images/certificado-fundo.png`.
- Coluna `certificates.template_version` (2 = modelo novo) e
  `tenants.fundo_certificado`.

### Observação
- Certificados já emitidos (`template_version` nulo) continuam sendo gerados
  exatamente como antes — nada muda para eles.
- Novos certificados usam o modelo único para todos os tipos (DSS e Treinamento).

## [2.0.62] - 2026-10-02

### Adicionado
- Carga horária do treinamento agora usa um **campo único no formato MM:SS**
  (ex.: 20:30) no cadastro e na edição, aceitando também apenas minutos.
  Nova coluna `trainings.carga_horaria_segundos` (nula para registros antigos).
- Player, API e cálculos de progresso passam a usar a duração total em segundos
  para treinamentos novos; exibição da carga horária atualizada em listas,
  certificados e relatórios.

### Observação
- Nenhum registro existente é alterado: quando `carga_horaria_segundos` é nulo,
  o comportamento permanece exatamente o anterior (`carga_horaria * 60`).

## [2.0.61] - 2026-10-02

### Corrigido
- Instabilidade do modal de avaliação (abria e fechava sozinho): o modal foi
  substituído por um painel fixo abaixo do vídeo.
- Avaliação agora tem bloqueio validado no servidor: o envio das respostas é
  recusado antes de 99% de conclusão do vídeo, mesmo que o painel seja
  reabilitado via DevTools/F12.
- Bloqueio global de teclado do player não interfere mais na digitação da senha
  de re-identificação nem na seleção das alternativas.

### Alterado
- Avaliação do treinamento agora aparece abaixo do vídeo: perguntas e opções
  ficam visíveis, porém desabilitadas, e são liberadas automaticamente após
  100% de conclusão do vídeo (com aviso ao usuário).
- Re-identificação por senha (NR-01 Anexo II) validada no envio das respostas
  para treinamentos do tipo **Treinamento**; DSS segue dispensando a senha.

### Removido
- Rota `POST /treinamentos/{id}/avaliacao/iniciar` e o modal de avaliação.

## [2.0.60] - 2026-09-25

### Adicionado
- Gestão de EPI / Devoluções: botões **Alterar** e **Excluir** no histórico de
  devoluções. Alterar reabre o lançamento para correção (quantidade, motivo,
  destino e observação); Excluir remove o registro e reverte estoque e status
  da entrega ao estado anterior ao lançamento.
- Migration `ss_e_nb_devolucao_id` em `ss_epi_estoque` — vincula cada movimento
  de devolução ao lançamento correspondente (com backfill dos registros antigos).

### Alterado
- Confirmação por senha antes da avaliação agora é exigida apenas para
  treinamentos do tipo **Treinamento**; DSS inicia a avaliação direto.

## [2.0.58] - 2026-09-17

## [2.0.57] - 2026-09-17

### Alterado
-modificado o modulo de folgas agora contem, lancar periodo de folga, remover folgas, programar folga

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
