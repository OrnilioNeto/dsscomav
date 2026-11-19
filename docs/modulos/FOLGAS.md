# Módulo de Folgas

Controle do banco de folgas dos motoristas (`motorista` e, opcionalmente,
`motorista_monitor`) e, desde a versão 2.0.83, integração com a elegibilidade
dos treinamentos.

## O que pode ser lançado

Na tela `/admin/folgas` (permissão `folgas`):

- **Lançar Dia**: um dia com tipo `trabalho`, `folga`, `atestado` ou `licenca`.
- **Lançar Período**: todos os dias de um intervalo (máximo de 60 dias) com o
  mesmo tipo. Ex.: atestado de 16/11 a 22/11.
- **Programação**: agendamento futuro de `folga`, `atestado` ou `licenca`
  (máximo de 90 dias). O débito acontece conforme a data chega.
- **Importação CSV**: `cpf;data;tipo;motivo;observacao`.

Para registrar uma perícia, use o tipo `atestado` ou `licenca` e descreva
"Perícia" no campo **Motivo**/`motivo_folga`.

## Regra de isenção de treinamento

O colaborador tem a **semana inteira (segunda a domingo)** da liberação do
conteúdo para assistir e concluir. Por isso:

- **Afastamento parcial** (ex.: atestado de segunda a quinta) **não altera**
  a pendência: o colaborador continua elegível e o conteúdo continua sendo
  cobrado.
- **Afastamento de semana completa** (segunda a domingo, os 7 dias) da semana
  do prazo do conteúdo **isenta** o treinamento daquela semana.

A semana considerada é a do prazo de conclusão do conteúdo:

- liberação de segunda a sábado: segunda a domingo da mesma semana;
- liberação no domingo: segunda a domingo da semana seguinte (quando vence o
  prazo padrão das 23:00).

A cobertura pode vir de:

1. **7 registros diários** em `folga_dias` com tipo `folga`, `atestado` ou
   `licenca`; ou
2. **uma programação** (`folga_programacoes`) ativa cobrindo o intervalo
   inteiro. Programação cancelada continua valendo para as semanas anteriores
   ao cancelamento; cancelada dentro da própria semana não isenta. Um dia
   lançado explicitamente como `trabalho` dentro da semana anula a programação
   (a mesma regra do banco de folgas).

Misturar lançamentos diários com programação para fechar os 7 dias não é
considerado. Nesse caso, lance o período completo em **Lançar Período**.

## Onde a isenção reflete

Quando a semana é coberta:

- o conteúdo **não aparece** no dashboard do colaborador (disponíveis,
  pendentes, bloqueados ou prazo expirado);
- o player web e os endpoints da API bloqueiam o acesso;
- o colaborador **sai das listas de pendentes**: prazos/vencidos, lembretes
  de WhatsApp, contagens de certificados e relatórios;
- lembretes já enfileirados são cancelados antes do envio;
- no **Relatório de Auditoria** (`/relatorios/auditoria`) o par usuário ×
  conteúdo é contabilizado como **"Afastados semana"** (justificado, fora do
  denominador de conformidade por padrão). O CSV detalhado ganhou a coluna
  **"Afastamento semana"** e a situação **"afastado na semana"**;
- a tela do usuário em **Treinamentos de Férias** mostra o selo
  **"Afastado na semana"**, sem permitir remover (a isenção é automática).

Isenções manuais de férias (`training_vacation_exemptions`) continuam
funcionando como antes.

## Data de corte

A regra vale apenas para lançamentos feitos a partir da implantação, para não
alterar o histórico consolidado. A data fica em `config/folgas.php`:

```php
'isencao_treinamento_desde' => env('FOLGA_ISENCAO_TREINAMENTO_DESDE', '2026-11-19'),
```

- Formato `Y-m-d`.
- Para considerar todo o histórico, defina `null`.
- Lançamentos anteriores à data de corte são ignorados pela regra (mas
  continuam valendo normalmente para o banco de folgas).

## Regras do banco de folgas

As regras de saldo, ciclo 6x1, domingo e marco zero não mudaram. Consulte a
tela **Configurações** (`/admin/folgas/configuracoes`) e os comandos:

```bash
php artisan folgas:recalculate --month= --year=
php artisan folgas:check --month= --year=
```

## Testes

`tests/Feature/TrainingAbsenceExemptionTest.php` cobre:

- afastamento parcial não isenta;
- afastamento de segunda a domingo isenta (scope e verificação em memória);
- programação ativa isenta e programação cancelada antes do fim não isenta;
- lançamento anterior à data de corte não isenta;
- bloqueio do player e ocultação no dashboard;
- contador `afastados_semana` no relatório de auditoria.
