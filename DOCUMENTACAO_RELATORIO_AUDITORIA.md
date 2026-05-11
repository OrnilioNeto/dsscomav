# Documentação do Relatório de Auditoria

Este documento explica o significado dos principais cards, gráficos e filtros do relatório executivo de auditoria.

## Visão geral

O relatório consolida dados de:

- `User` para usuários, perfis e status
- `UserProgress` para participações, conclusão e tempo assistido
- `Training` para catálogo de conteúdos
- `Certificate` para emissão de certificados e histórico de validação

O objetivo é permitir leitura gerencial rápida sobre engajamento, consumo de conteúdo, certificação e pontos de atenção.

## Cards do topo

### Total de Usuários

- **O que mostra:** total de usuários dentro do filtro atual.
- **De onde vem:** consulta em `usuariosBase`.
- **Como interpretar:** quantas pessoas compõem a base analisada naquele recorte.

### Conteúdos

- **O que mostra:** total de treinamentos cadastrados.
- **De onde vem:** `Training::count()`.
- **Como interpretar:** tamanho do catálogo disponível no sistema.

### Certificados

- **O que mostra:** número de certificados emitidos dentro do filtro atual.
- **De onde vem:** consulta em `certificadosBase`.
- **Como interpretar:** volume de evidências geradas no período ou filtro selecionado.

### Tempo

- **O que mostra:** soma exata do tempo assistido, no formato `HH:MM:SS`.
- **De onde vem:** soma de `tempo_assistido` em `progressBase`.
- **Como interpretar:** tempo total consumido pela base no recorte atual.

## Cards de leitura executiva

### Taxa de conclusão

- **O que mostra:** percentual de registros finalizados sobre o total de participações.
- **De onde vem:** `concluidas / totalAssistencias`.
- **Como interpretar:** quanto da jornada registrada terminou com sucesso.

### Engajamento

- **O que mostra:** percentual de usuários com pelo menos um registro em `UserProgress`.
- **De onde vem:** `usuariosComProgresso / totalUsuarios`.
- **Como interpretar:** mede a adesão da base aos treinamentos.
- **Exemplo:** `60,00%` significa que 60 de cada 100 usuários tiveram ao menos uma participação registrada.

### Certificação

- **O que mostra:** percentual de usuários com pelo menos um certificado.
- **De onde vem:** `usuariosComCertificados / totalUsuarios`.
- **Como interpretar:** mede a proporção da base que já concluiu conteúdos com emissão de evidência.

### Usuários sem progresso

- **O que mostra:** quantidade de usuários sem nenhum registro de treinamento.
- **De onde vem:** `User::whereDoesntHave('progress')` com o mesmo escopo de permissões.
- **Como interpretar:** é o grupo de maior risco para ações de ativação.

## Seções analíticas

### Saúde da operação

Reúne participações registradas, pendências, taxa de conclusão, engajamento e certificação em uma visão única.

### Distribuição e conteúdo

#### Usuários por perfil

- **De onde vem:** `usuariosPorTipo`
- **O que mostra:** distribuição de usuários por tipo/perfil.
- **Como interpretar:** ajuda a entender a composição da base filtrada.

#### Conteúdos por tipo

- **De onde vem:** `conteudosPorTipo`
- **O que mostra:** volume de conteúdos por categoria.
- **Como interpretar:** mostra a estrutura do catálogo.

### Evolução mensal

#### Participações por mês

- **De onde vem:** `atividadesPorMes`
- **O que mostra:** quantidade de registros de progresso iniciados em cada mês.
- **Como interpretar:** tendência de uso da plataforma ao longo do tempo.

#### Certificados por mês

- **De onde vem:** `certificadosPorMes`
- **O que mostra:** quantidade de certificados emitidos por mês.
- **Como interpretar:** evolução da conclusão formal dos treinamentos.

### Usuários em destaque

- **De onde vem:** `usuariosEmDestaque`
- **O que mostra:** ranking dos usuários com maior volume de participações e tempo acumulado.
- **Como interpretar:** identifica quem mais consome conteúdo.

### Treinamentos com maior impacto

- **De onde vem:** `treinamentosMaisAssistidos`
- **O que mostra:** conteúdos com maior número de participações, conclusões e tempo total.
- **Como interpretar:** revela quais treinamentos puxam mais a operação.

### Mapa de oportunidade

- **De onde vem:** `usuariosSemTreinamentoLista`
- **O que mostra:** usuários sem nenhum progresso registrado.
- **Como interpretar:** lista prática para abordagem e ativação.

## Filtros

### Tipo de usuário

Filtra o relatório pelo perfil do usuário.

### Usuário

Filtra um usuário específico.

### Tipo de conteúdo

Filtra entre DSS e Treinamento.

### Treinamento

Filtra um conteúdo específico.

### Período inicial e final

Restringe os registros pela data de início e pela data de emissão dos certificados.

## Observação sobre tempo

O tempo exibido no relatório segue o mesmo formato do certificado oficial: `HH:MM:SS`.

Isso significa que:

- horas são mostradas quando existem
- minutos e segundos sempre aparecem
- o valor é calculado a partir dos segundos armazenados no banco

## Fonte dos dados

- `UserProgress.tempo_assistido` armazena o tempo assistido em segundos
- `Certificate.tempo_assistido_segundos` espelha esse valor no certificado
- os relatórios formatam esse tempo com `gmdate('H:i:s', ...)`
