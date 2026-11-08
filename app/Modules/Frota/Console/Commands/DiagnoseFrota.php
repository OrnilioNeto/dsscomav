<?php

namespace App\Modules\Frota\Console\Commands;

use App\Models\User;
use App\Modules\Frota\Models\FrotaConfiguracao;
use App\Modules\Frota\Models\FrotaMotorista;
use App\Modules\Frota\Models\FrotaVeiculo;
use App\Modules\Frota\Services\AutotracClient;
use App\Modules\Frota\Services\DriverPresenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DiagnoseFrota extends Command
{
    protected $signature = 'frota:diagnostico
        {--cpf=* : CPF(s) de motorista para validar presença (pode repetir)}
        {--veiculo= : Código de veículo para testar o driverlogs}';

    protected $description = 'Diagnostica a integração Autotrac e a regra de acesso aos treinamentos';

    public function handle(): int
    {
        if (! Schema::hasTable('frota_configuracoes')) {
            $this->error('Tabelas do módulo Frota não existem. Rode: php artisan migrate --force');

            return self::FAILURE;
        }

        $config = FrotaConfiguracao::atual();
        $gateEnabled = (bool) config('autotrac.gate_enabled', false);

        $this->info('=== Configuração ===');
        $this->line('Verificação de jornada: '.($gateEnabled ? 'ATIVA' : 'DESATIVADA (AUTOTRAC_GATE_ENABLED=false)'));
        $this->line('Ativa: '.($config->ativo ? 'sim' : 'não'));
        $this->line('Base URL: '.$config->urlBase());
        $this->line('Conta: '.($config->account_code ?: '—'));
        $this->line('Chave: '.$config->chaveMascarada());
        $this->line('Usuário: '.$config->usuarioMascarado());
        $this->line('Tipos afetados: '.implode(', ', $config->tiposMotorista()));
        $this->line('TTL: '.$config->ttl_segundos.'s | Grace: '.$config->grace_minutos.'min | Jornada máxima: '.$config->jornada_maxima_horas.'h');
        $this->newLine();

        if (! $config->podeAutenticar()) {
            $this->error('Credenciais incompletas. Preencha /admin/frota/configuracao ou o .env (AUTOTRAC_*).');

            return self::FAILURE;
        }

        $client = new AutotracClient($config);

        $this->info('=== Conexão (GET /v1/accounts) ===');
        $contas = $client->contas();

        if (! $contas['ok']) {
            $this->error('Falha: '.$contas['error']);

            return self::FAILURE;
        }

        $body = $contas['body'];
        $lista = isset($body['Data']) && is_array($body['Data'])
            ? $body['Data']
            : (is_array($body) ? $body : []);

        foreach ($lista as $conta) {
            $this->line(sprintf('  Conta %s — %s', $conta['Code'] ?? '?', $conta['Name'] ?? '?'));
        }

        $this->newLine();
        $this->info('=== Cadastros locais ===');
        $this->line('Motoristas: '.FrotaMotorista::count().' | Veículos: '.FrotaVeiculo::count());
        $this->line('Tabelas: '.implode(', ', array_filter([
            Schema::hasTable('frota_motoristas') ? 'frota_motoristas' : null,
            Schema::hasTable('frota_jornadas') ? 'frota_jornadas' : null,
            Schema::hasTable('frota_liberacoes_treinamento') ? 'frota_liberacoes_treinamento' : null,
        ])));

        $this->newLine();
        $this->info('=== Driver Status (amostra) ===');
        $status = $client->driverStatus(['_limit' => 10]);

        if (! $status['ok']) {
            $this->warn('Falha: '.$status['error']);
        } else {
            $registros = $status['body']['Data'] ?? [];
            $this->line('Registros retornados: '.count($registros));

            foreach (array_slice($registros, 0, 10) as $registro) {
                $this->line(sprintf(
                    '  Motorista %s — %s — CPF %s | Veículo %s — %s',
                    $registro['DriverCode'] ?? '?',
                    $registro['DriverName'] ?? '?',
                    $registro['DriverCPF'] ?? '?',
                    $registro['VehicleCode'] ?? '?',
                    $registro['VehicleName'] ?? '?'
                ));
            }
        }

        $veiculoOpt = $this->option('veiculo');

        if ($veiculoOpt) {
            $this->newLine();
            $this->info('=== Driverlogs (_last=true) — veículo '.$veiculoOpt.' ===');
            $logs = $client->driverLogs((int) $veiculoOpt, ['_last' => 'true']);

            if (! $logs['ok']) {
                $this->warn('Falha: '.$logs['error']);
            } else {
                $this->line(json_encode($logs['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        $cpfs = (array) $this->option('cpf');

        if ($cpfs === []) {
            $this->newLine();
            $this->comment('Dica: rode com --cpf=000.000.000-00 para validar motoristas específicos.');

            return self::SUCCESS;
        }

        $presence = new DriverPresenceService;

        foreach ($cpfs as $cpfBruto) {
            $cpf = preg_replace('/\D/', '', (string) $cpfBruto) ?? '';

            $this->newLine();
            $this->info('=== CPF '.$cpfBruto.' ===');

            $usuario = User::withoutGlobalScopes()->where('cpf', $cpf)->first();
            $motorista = FrotaMotorista::where('cpf', $cpf)->first();

            $this->line('Usuário DSS: '.($usuario ? $usuario->nome.' (#'.$usuario->id.', '.$usuario->tipo_usuario.')' : 'não encontrado'));
            $this->line('Motorista local: '.($motorista ? $motorista->name.' (code '.$motorista->code.', status '.$motorista->status.')' : 'não encontrado'));

            $resposta = $client->driverStatus($motorista ? [
                '_driverCode' => (int) $motorista->code,
                '_limit' => 50,
            ] : ['_limit' => 500]);

            if (! $resposta['ok']) {
                $this->warn('driverstatus falhou: '.$resposta['error']);
            } else {
                $encontrados = collect($resposta['body']['Data'] ?? [])
                    ->filter(function ($item) use ($cpf) {
                        $cpfItem = preg_replace('/\D/', '', (string) ($item['DriverCPF'] ?? '')) ?? '';

                        return $cpfItem === $cpf;
                    });

                $this->line('Registros no driverstatus: '.$encontrados->count());

                foreach ($encontrados as $item) {
                    $this->line(sprintf(
                        '  Code %s — %s | Veículo %s — %s | ExcessJourney: %s | ExcessExtended: %s',
                        $item['DriverCode'] ?? '?',
                        $item['DriverName'] ?? '?',
                        $item['VehicleCode'] ?? '?',
                        $item['VehicleName'] ?? '?',
                        ($item['ExcessJourney'] ?? false) ? 'sim' : 'não',
                        ($item['ExcessExtendedJourney'] ?? false) ? 'sim' : 'não'
                    ));

                    if (! empty($item['VehicleCode'])) {
                        $logs = $client->driverLogs((int) $item['VehicleCode'], ['_last' => 'true']);

                        if ($logs['ok']) {
                            $this->line('  driverlogs: '.json_encode($logs['body']['Data'] ?? [], JSON_UNESCAPED_UNICODE));
                        } else {
                            $this->warn('  driverlogs falhou: '.$logs['error']);
                        }
                    }
                }
            }

            if ($usuario) {
                if (! $gateEnabled) {
                    $this->line('Decisão: não avaliada (verificação de jornada desativada).');
                } else {
                    $avaliacao = $presence->avaliar($usuario, true);
                    $this->line(sprintf(
                        'Decisão: %s | origem: %s | %s',
                        $avaliacao['liberado'] ? 'LIBERADO' : 'BLOQUEADO',
                        $avaliacao['origem'],
                        $avaliacao['motivo']
                    ));
                }
            }
        }

        return self::SUCCESS;
    }
}
