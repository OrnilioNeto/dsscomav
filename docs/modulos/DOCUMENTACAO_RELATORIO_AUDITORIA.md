# Relatórios de Auditoria e de Avaliações

Este documento explica a metodologia, os filtros, as fontes de dados e as exportações dos dois relatórios gerenciais voltados a auditoria e tomada de decisão.

## Visão geral

### Relatório de Auditoria Gerencial — `/relatorios/auditoria`

Painel de conformidade de treinamentos com tratamento explícito de ausências justificadas e trilha de evidências. Objetivo: permitir auditoria interna/externa (ex.: SASSMAQ, ISO) e decisão gerencial com números rastreáveis.

### Relatório de Avaliações e Notas — `/relatorios/avaliacoes`

Detalhamento de notas por usuário × treinamento: resultado (aprovado/reprovado/pendente), tentativas, data/hora de início e conclusão, tempo assistido, certificado emitido e histórico completo de tentativas de avaliação.

## Base de cálculo (transparência)

- **Excluídos da base:** somente `super_admin` e usuários com `usuario_teste = true`.
  - A contagem de excluídos é exibida no topo do relatório e registrada nos arquivos exportados.
  - Admins, inativos e afastados **permanecem** na base e são isoláveis por filtros.
- **Escopo por visualizador:** admin enxerga apenas usuários comuns (sem papel `admin`/`super_admin`); super_admin enxerga todos. `admin`/`super_admin` com papel não-admin aparecem no escopo do super_admin.
- **Elegibilidade por conteúdo** (mesmas regras de liberação do sistema):
  - corte por data de cadastro (usuários cadastrados após o fim da semana da liberação não são elegíveis);
  - público-alvo (DSS) ou atribuição direta (Treinamento);
  - isenção de férias por conteúdo (`training_vacation_exemptions`);
  - férias cobrindo a data de liberação do conteúdo.
- **Prazo efetivo:** prazo estendido concedido pelo gestor (`user_progress.prazo_estendido_ate`) quando existir; senão, **23:00 do domingo subsequente à liberação**.

## Metodologia de conformidade

Status por par usuário × conteúdo:

| Status | Regra |
|---|---|
| Concluído em dia | `concluido = true` e conclusão dentro do prazo efetivo |
| Concluído em atraso | `concluido = true` e conclusão após o prazo efetivo |
| Em andamento | iniciado, não concluído e prazo vigente |
| Não iniciado | elegível e sem registro de progresso |
| Prazo expirado | não concluído e prazo efetivo já passou |
| Isento | isenção de conteúdo por férias registrada |
| Afastado na liberação | elegível pelas demais regras, mas de férias na data de liberação |

- **Índice de conformidade** = concluídos em dia ÷ pares elegíveis.
  - Por padrão, **afastados justificados (isentos + afastados na liberação) ficam fora do denominador**.
  - O filtro "Incluir afastados/isentos no denominador" aplica o cenário mais rígido.
- A matriz de conformidade reflete o **estado atual** dos conteúdos filtrados; as métricas de atividade (participações, tempo, certificados, ausências) respeitam o **período** selecionado.

## Ausências justificadas

| Fonte | Tabela | Observações |
|---|---|---|
| Férias | `user_vacations` (+ campos `users.ferias_*`) | dias sobrepostos ao período |
| Folgas / Atestados / Licenças | `folga_dias` (`tipo`) | lançamentos com autor (`lancado_por`) e motivo |
| Isenções de conteúdo | `training_vacation_exemptions` | motivo e criador |
| Prazos estendidos | `user_progress.prazo_estendido_*` | motivo, autor e nova data |

Todas aparecem em painel próprio (totais + listas) e em **CSV de ausências**. Usuários afastados que registraram atividade no período continuam contando nas métricas de atividade e aparecem com o afastamento destacado.

## Trilha de auditoria (audit_logs)

- Seção-resumo do relatório de auditoria com eventos do período: totais por tipo, módulos mais acionados, usuários mais ativos e últimos eventos.
- Visível apenas para quem tem a permissão `auditoria` (ou super_admin).
- Link direto para `/admin/auditoria` com o período pré-preenchido.

## Exportações e cadeia de custódia

### Auditoria
- **CSV detalhado** (`relatorios.auditoria.csv`): uma linha por participação (usuário × conteúdo) com elegibilidade, prazos, situação, afastamento no período, isenção, certificado e status de validade. Opção "incluir não iniciados" quando um conteúdo específico está selecionado.
- **CSV de ausências** (`?tipo_exportacao=ausencias`): férias, folgas/atestados/licenças, isenções e prorrogações com motivo e autor.
- **PDF executivo** (`relatorios.auditoria.pdf`): capa com filtros, KPIs, conformidade por conteúdo, ausências e trilha.

### Avaliações
- **CSV resultados**: notas, resultado, tentativas, datas/horas, tempo e certificados.
- **CSV tentativas** (`?tipo_exportacao=tentativas`): histórico de submissões/resets/senha inválida com data/hora e detalhe (acertos).
- **PDF executivo**: indicadores, desempenho por treinamento e resultados (até 1.500 linhas; CSV para a base completa).

### Metadados de integridade
- Todo arquivo exportado inclui: plataforma, título, **data/hora de geração**, **usuário gerador**, **filtros aplicados** e **SHA-256 do recorte de dados**.
- O mesmo conjunto de filtros produz o **mesmo digest** no CSV e no PDF do relatório de auditoria.
- Toda exportação é registrada na trilha de auditoria (`AuditLogger`, evento `exported`).

## Filtros disponíveis

- **Período** (início/fim) e **base da data** (início, conclusão ou emissão, conforme o relatório).
- Perfil/Tipo de usuário, status (ativo/inativo), busca por nome/CPF, setor, cargo, empresa.
- Conteúdo específico, tipo de conteúdo (DSS/Treinamento), tipo de treinamento (inicial/periódico/eventual) e somente obrigatórios.
- Auditoria: incluir afastados/isentos no denominador.
- Avaliações: resultado, faixa de nota (mín./máx.), somente conteúdos com prova e tipo de evento do histórico.

## Fontes de dados

- `users` (perfil, status, empresa, setor, cargo, férias, `usuario_teste`)
- `trainings` (tipo, tipo de treinamento, obrigatoriedade, liberação, validade, nota mínima)
- `user_progress` (início, conclusão, tempo, porcentagem, nota, aprovação, tentativas, prazos estendidos)
- `certificates` (emissão, validade calculada pela configuração do treinamento)
- `training_logs` (histórico de tentativas de avaliação — evidência)
- `user_vacations`, `folga_dias`, `training_vacation_exemptions` (ausências e justificativas)
- `audit_logs` (trilha de ações, módulo `auditoria`)

## Observação sobre tempo e notas

- Tempo sempre em `HH:MM:SS` (mesmo padrão do certificado oficial).
- Nota na escala 0–100; o critério de aprovação é a `nota_minima_aprovacao` do treinamento (padrão 70%).
- O relatório de avaliações exibe o **último estado registrado**; reprovações duplas zeram a nota para nova tentativa após reassistir — por isso o histórico de tentativas é a evidência completa.

## Testes automatizados

- `tests/Feature/RelatorioAuditoriaTest.php`
- `tests/Feature/RelatorioAvaliacoesTest.php`

Cobrem: exclusões da base (super_admin/teste), escopo admin × super_admin, conformidade com prazo e ausências, painel de ausências, filtros, exportações CSV/PDF com hash e trilha condicionada à permissão.
