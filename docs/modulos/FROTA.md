# Módulo Frota e Jornada (acesso de motoristas aos treinamentos)

Módulo novo (`app/Modules/Frota`, slug `frota`) que integra a **ATIC API (Autotrac)** ao
controle de acesso aos treinamentos da Plataforma DSS.

## Regra de negócio

Motoristas (`tipo_usuario` = `motorista` ou `motorista_monitor`) só podem iniciar,
continuar, avaliar ou concluir treinamentos quando estão com **jornada aberta no
rastreador** — ou quando receberam uma **liberação manual** da gestão.

| Situação | Acesso aos treinamentos |
|---|---|
| Não é motorista/monitor | Liberado |
| Admin / gestor | Liberado |
| Motorista online, sessão < limite (padrão 12h) | Liberado |
| Motorista online com sessão ≥ limite | Bloqueado |
| Motorista offline | Bloqueado |
| Liberação manual vigente | Liberado (sobrepõe) |
| API indisponível e último online dentro da tolerância (padrão 30 min) | Liberado (degradado) |
| API indisponível sem status recente | Bloqueado |

O login na DSS **nunca é bloqueado**. A regra é aplicada apenas nas rotas de treinamento
(web e API). Dashboard, perfil, EPI, social, ranking e certificados já emitidos continuam
acessíveis.

## Endpoints da Autotrac usados

Base de produção: `https://aapi3.autotrac-online.com.br/aticapi`

| Método | Endpoint | Uso |
|---|---|---|
| GET | `/v1/accounts` | Teste de conexão e descoberta do `accountCode` |
| GET | `/v1/drivers?_limit=500&_offset=0` | Cadastro de motoristas (Code, Name, DriverCPF) |
| GET | `/v1/accounts/{account}/vehicles?_limit=500` | Cadastro de veículos (inclui placa) |
| GET | `/v1/driverstatus?_driverCode=...` | Status de jornada (janela de 24h) |
| GET | `/v1/accounts/{account}/vehicles/{vehicle}/driverlogs?_last=true` | Último login/logout do veículo |

Autenticação: `Authorization: Basic usuario@companhia:senha` + header
`Ocp-Apim-Subscription-Key`. TLS 1.2+. O IP de saída do servidor precisa estar liberado
no Home Office da Autotrac (senão **403**).

`LogoutTime = 1970-01-01` (ou anterior a 1970) significa **sessão aberta**.

## Kill switch (desativação global)

`AUTOTRAC_GATE_ENABLED=false` (padrão) desliga a regra inteira:

- ninguém é bloqueado (motorista, monitor ou qualquer perfil);
- o middleware não consulta banco nem API no caminho da request;
- o sync agendado não roda (use `php artisan frota:sync --force` para testar a integração manualmente);
- as telas de configuração/diagnóstico continuam disponíveis.

Para reativar quando as credenciais chegarem:

1. `AUTOTRAC_GATE_ENABLED=true` no `.env` e reinicie a aplicação
   (`docker compose restart app-dss` no Docker / recarregue o PHP-FPM no cPanel);
2. preencha as credenciais em `/admin/frota/configuracao` (ou no `.env`);
3. teste a conexão, marque **Integração ativa** e salve;
4. rode `php artisan frota:sync` e o `frota:diagnostico --cpf=...`.

Sem o kill switch ligado, o checkbox “Integração ativa” sozinho **não** ativa a regra.

## Configuração

Tela: `/admin/frota/configuracao` (permissão `integracao_autotrac,edit`).

Os valores salvos no banco têm prioridade sobre o `.env`:

```env
AUTOTRAC_GATE_ENABLED=false
AUTOTRAC_ENABLED=true
AUTOTRAC_BASE_URL=https://aapi3.autotrac-online.com.br/aticapi
AUTOTRAC_ACCOUNT_CODE=1234
AUTOTRAC_SUBSCRIPTION_KEY=...
AUTOTRAC_USERNAME=usuario@companhia
AUTOTRAC_PASSWORD=...
AUTOTRAC_GRACE_MINUTES=30
AUTOTRAC_MAX_SESSION_HOURS=12
AUTOTRAC_PRESENCE_TTL=60
AUTOTRAC_FAIL_MODE=grace
```

A senha e a chave são gravadas criptografadas (`encrypted`) e exibidas mascaradas.

## Comandos

```bash
# Sincroniza cadastros e status (agendado a cada 5 min no Kernel)
php artisan frota:sync

# Etapas isoladas
php artisan frota:sync --only=drivers
php artisan frota:sync --only=vehicles
php artisan frota:sync --only=status

# Diagnóstico da integração e da regra de acesso
php artisan frota:diagnostico
php artisan frota:diagnostico --cpf="107.630.214-90" --cpf="033.187.174-28"
php artisan frota:diagnostico --veiculo=56789
```

O cron de produção precisa chamar `php artisan schedule:run` a cada minuto (o mesmo
do ranking/lembretes).

## Telas

| Rota | Permissão | Função |
|---|---|---|
| `/admin/frota` | `frota,view` | Motoristas online, status e atalho de liberação |
| `/admin/frota/veiculos` | `frota,view` | Frota importada |
| `/admin/frota/historico` | `frota,view` | Histórico de jornadas |
| `/admin/frota/liberacoes-treinamento` | `frota,view` | Histórico de liberações + CSV |
| `/admin/frota/configuracao` | `integracao_autotrac,edit` | Credenciais e regras |
| `/treinamentos/bloqueado` | autenticado | Explicação + verificação automática (60s) |

A liberação manual aceita lote (`user_ids[]`), motivo opcional e validade (fim do dia,
24h, 7 dias ou data específica). Toda liberação/revogação/exportação é registrada em
`audit_logs`.

## Tabelas

- `frota_configuracoes` — credenciais e regras por tenant.
- `frota_motoristas` — Code/Name/CPF, vínculo `user_id`, status e última verificação.
- `frota_veiculos` — frota importada.
- `frota_jornadas` — histórico de login/logout.
- `frota_liberacoes_treinamento` — liberações manuais.

Todas usam `tenant_id` + `BelongsToTenant`.

## Deploy

```bash
php artisan migrate --force
php artisan frota:sync          # primeira carga
php artisan frota:diagnostico   # valida credenciais/IP/CPFs
```

Depois, liberar as permissões `frota` e `integracao_autotrac` para os perfis desejados em
**Perfis e Permissões** (a migration já cria para `admin`/`super_admin`).

Com `SAAS_MULTITENANT_ENABLED=true`, habilite os módulos `frota` e `integracao_autotrac`
no painel da plataforma para cada tenant e configure as credenciais em
`/admin/frota/configuracao` dentro do tenant (as credenciais são por tenant).

O veículo precisa estar na lista de **Authorized Units** da Autotrac; fora dela, o
`driverlogs` responde 422 e o comportamento cai na tolerância/liberação manual.

## Limitações conhecidas

1. O login no rastreador é o proxy de "horário de trabalho": se o motorista não se logar
   no equipamento, não treina — mesmo trabalhando.
2. Se ele esquecer de deslogar, a sessão continua aberta até o corte de
   `jornada_maxima_horas` (padrão 12h).
3. Motorista sem CPF correspondente na Autotrac fica bloqueado até liberação manual.
4. A API guarda apenas 7 dias de histórico; o sync contínuo é o que preserva o passado.
5. O app mobile precisa de ajuste visual para exibir a mensagem do `403`
   (`code: DRIVER_TRAINING_BLOCKED`); a API já devolve `acesso_treinamentos` no
   login/`me`/dashboard.

## LGPD

CPF e jornada são dados pessoais. O acesso às telas é restrito por permissão, o CPF é
mascarado nas listagens (`mask_cpf`) e liberações/exportações são auditadas.
