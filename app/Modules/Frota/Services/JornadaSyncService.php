<?php

namespace App\Modules\Frota\Services;

use App\Models\User;
use App\Modules\Frota\Models\FrotaJornada;
use App\Modules\Frota\Models\FrotaMotorista;
use App\Modules\Frota\Models\FrotaVeiculo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Sincroniza cadastros e status de jornada com a ATIC API.
 *
 * Toda a escrita passa por updateOrCreate para o comando poder rodar
 * repetidamente (a cada 5 min) sem duplicar histórico.
 */
class JornadaSyncService
{
    private const LIMITE_PAGINA = 500;

    private const MAX_PAGINAS = 50;

    private AutotracClient $client;

    public function __construct(?AutotracClient $client = null)
    {
        $this->client = $client ?? new AutotracClient;
    }

    /**
     * @return array{motoristas: int, veiculos: int, status: int, erros: array<int, string>}
     */
    public function sincronizarTudo(): array
    {
        $erros = [];

        $motoristas = $this->sincronizarMotoristas();
        if (! $motoristas['ok']) {
            $erros[] = 'Motoristas: '.$motoristas['error'];
        }

        $veiculos = $this->sincronizarVeiculos();
        if (! $veiculos['ok']) {
            $erros[] = 'Veículos: '.$veiculos['error'];
        }

        $status = $this->sincronizarStatus();
        if (! $status['ok']) {
            $erros[] = 'Status: '.$status['error'];
        }

        return [
            'motoristas' => $motoristas['processados'],
            'veiculos' => $veiculos['processados'],
            'status' => $status['processados'],
            'erros' => $erros,
        ];
    }

    /**
     * @return array{ok: bool, processados: int, error: ?string}
     */
    public function sincronizarMotoristas(): array
    {
        if (! $this->client->configurado()) {
            return ['ok' => false, 'processados' => 0, 'error' => 'Integração não configurada.'];
        }

        $pagina = $this->paginar(fn (int $offset, int $limite) => $this->client->motoristas($offset, $limite));

        if (! $pagina['ok']) {
            return ['ok' => false, 'processados' => 0, 'error' => $pagina['error']];
        }

        $registros = collect($pagina['itens'])->filter(fn ($item) => is_array($item) && ! empty($item['Code']));
        $cpfs = $registros
            ->map(fn (array $item) => $this->somenteDigitos($item['DriverCPF'] ?? null))
            ->filter()
            ->unique()
            ->values();

        $usuariosPorCpf = $this->usuariosPorCpf($cpfs);

        $processados = 0;

        foreach ($registros as $item) {
            $cpf = $this->somenteDigitos($item['DriverCPF'] ?? null);
            $usuario = $cpf !== '' ? $usuariosPorCpf->get($cpf) : null;

            FrotaMotorista::updateOrCreate(
                ['code' => (int) $item['Code']],
                [
                    'name' => $item['Name'] ?? null,
                    'cpf' => $cpf !== '' ? $cpf : null,
                    'user_id' => $usuario?->id,
                    'ativo' => true,
                    'synced_at' => now(),
                ]
            );

            $processados++;
        }

        return ['ok' => true, 'processados' => $processados, 'error' => null];
    }

    /**
     * @return array{ok: bool, processados: int, error: ?string}
     */
    public function sincronizarVeiculos(): array
    {
        if (! $this->client->configurado()) {
            return ['ok' => false, 'processados' => 0, 'error' => 'Integração não configurada.'];
        }

        $pagina = $this->paginar(fn (int $offset, int $limite) => $this->client->veiculos($offset, $limite));

        if (! $pagina['ok']) {
            return ['ok' => false, 'processados' => 0, 'error' => $pagina['error']];
        }

        $processados = 0;

        foreach ($pagina['itens'] as $item) {
            if (! is_array($item) || empty($item['Code'])) {
                continue;
            }

            FrotaVeiculo::updateOrCreate(
                ['code' => (int) $item['Code']],
                [
                    'name' => $item['Name'] ?? null,
                    'address' => isset($item['Address']) ? (string) $item['Address'] : null,
                    'license_plate' => $item['LicensePlate'] ?? null,
                    'trip_name' => $item['TripName'] ?? null,
                    'ativo' => true,
                    'synced_at' => now(),
                ]
            );

            $processados++;
        }

        return ['ok' => true, 'processados' => $processados, 'error' => null];
    }

    /**
     * Consulta o status de jornada (janela de 24h da Autotrac) e reconcilia
     * as sessões locais. Também confirma o login real pelo driverlogs.
     *
     * @return array{ok: bool, processados: int, error: ?string}
     */
    public function sincronizarStatus(): array
    {
        if (! $this->client->configurado()) {
            return ['ok' => false, 'processados' => 0, 'error' => 'Integração não configurada.'];
        }

        $pagina = $this->paginar(fn (int $offset, int $limite) => $this->client->driverStatus([
            '_offset' => $offset,
            '_limit' => $limite,
        ]));

        if (! $pagina['ok']) {
            return ['ok' => false, 'processados' => 0, 'error' => $pagina['error']];
        }

        $registros = collect($pagina['itens'])->filter(fn ($item) => is_array($item) && ! empty($item['DriverCode']));
        $codigosVistos = [];
        $logsPorVeiculo = [];
        $processados = 0;

        foreach ($registros as $item) {
            $driverCode = (int) $item['DriverCode'];
            $codigosVistos[] = $driverCode;

            $cpf = $this->somenteDigitos($item['DriverCPF'] ?? null);
            $motorista = $this->localizarMotorista($driverCode, $cpf, $item['DriverName'] ?? null);

            if (! $motorista) {
                continue;
            }

            $veiculo = $this->localizarVeiculo($item);

            $flags = [
                'excess_journey' => (bool) ($item['ExcessJourney'] ?? false),
                'excess_extended_journey' => (bool) ($item['ExcessExtendedJourney'] ?? false),
            ];

            $confirmacao = $veiculo
                ? $this->confirmarLogin($veiculo, $motorista, $logsPorVeiculo)
                : null;

            // Sem confirmação (falha pontual no driverlogs), mantém otimista:
            // a presença no driverstatus já indica jornada na janela de 24h.
            $online = $confirmacao !== null && $confirmacao['estado'] === 'ok'
                ? $confirmacao['online']
                : true;

            $loginTime = $confirmacao['login_time'] ?? null;
            $logoutTime = $confirmacao['logout_time'] ?? null;

            $this->aplicarJornada($motorista, $veiculo, $loginTime, $logoutTime, $online, $flags);

            $processados++;
        }

        // Quem estava online e não apareceu mais no status volta para offline.
        FrotaMotorista::query()
            ->where('status', FrotaMotorista::STATUS_ONLINE)
            ->when($codigosVistos !== [], fn ($q) => $q->whereNotIn('code', array_unique($codigosVistos)))
            ->update([
                'status' => FrotaMotorista::STATUS_OFFLINE,
                'verificado_em' => now(),
            ]);

        return ['ok' => true, 'processados' => $processados, 'error' => null];
    }

    /**
     * Confirma no driverlogs se a última sessão do veículo está aberta.
     *
     * @param  array<int, array{estado: string, online: bool, login_time: ?Carbon, logout_time: ?Carbon}>  $cache
     * @return array{estado: string, online: bool, login_time: ?Carbon, logout_time: ?Carbon}
     */
    private function confirmarLogin(FrotaVeiculo $veiculo, FrotaMotorista $motorista, array &$cache): array
    {
        if (array_key_exists((int) $veiculo->code, $cache)) {
            return $cache[(int) $veiculo->code];
        }

        $resposta = $this->client->driverLogs((int) $veiculo->code, ['_last' => 'true']);

        if (! $resposta['ok']) {
            return $cache[(int) $veiculo->code] = [
                'estado' => 'erro',
                'online' => false,
                'login_time' => null,
                'logout_time' => null,
            ];
        }

        $registros = $resposta['body']['Data'] ?? [];

        if (! is_array($registros) || $registros === []) {
            return $cache[(int) $veiculo->code] = [
                'estado' => 'sem_registro',
                'online' => false,
                'login_time' => null,
                'logout_time' => null,
            ];
        }

        $registro = $registros[0];
        $cpfRegistro = $this->somenteDigitos($registro['DriverCPF'] ?? null);
        $cpfMotorista = $motorista->cpf_digitos;

        if ($cpfMotorista !== '' && $cpfRegistro !== '' && $cpfRegistro !== $cpfMotorista) {
            return $cache[(int) $veiculo->code] = [
                'estado' => 'outro_motorista',
                'online' => false,
                'login_time' => null,
                'logout_time' => null,
            ];
        }

        $login = $this->parseData($registro['LoginTime'] ?? null);
        $logout = $this->parseData($registro['LogoutTime'] ?? null);

        return $cache[(int) $veiculo->code] = [
            'estado' => 'ok',
            'online' => $logout === null,
            'login_time' => $login,
            'logout_time' => $logout,
        ];
    }

    /**
     * Verificação estrita de um motorista (usada pelo gate de treinamentos).
     *
     * Diferente do sync de fundo, aqui uma falha da API marca indisponivel=true
     * para o chamador aplicar a janela de tolerância (grace).
     *
     * @return array{ok: bool, online: bool, confirmado: bool, indisponivel: bool, error: ?string}
     */
    public function verificarMotorista(FrotaMotorista $motorista): array
    {
        if (! $this->client->configurado()) {
            return [
                'ok' => false,
                'online' => $motorista->estaOnline(),
                'confirmado' => false,
                'indisponivel' => true,
                'error' => 'Integração não configurada.',
            ];
        }

        $resposta = $this->client->driverStatus([
            '_driverCode' => (int) $motorista->code,
            '_limit' => 50,
        ]);

        if (! $resposta['ok']) {
            $motorista->forceFill([
                'verificado_em' => now(),
                'ultimo_erro' => $resposta['error'],
            ])->save();

            return [
                'ok' => false,
                'online' => $motorista->estaOnline(),
                'confirmado' => false,
                'indisponivel' => true,
                'error' => $resposta['error'],
            ];
        }

        $registros = collect($resposta['body']['Data'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->values();

        if ($registros->isEmpty()) {
            $motorista->forceFill([
                'status' => FrotaMotorista::STATUS_OFFLINE,
                'verificado_em' => now(),
                'veiculo_atual_id' => null,
                'ultimo_erro' => null,
            ])->save();

            FrotaJornada::query()
                ->where('frota_motorista_id', $motorista->id)
                ->where('aberta', true)
                ->update(['aberta' => false, 'logout_time' => now(), 'synced_at' => now()]);

            return [
                'ok' => true,
                'online' => false,
                'confirmado' => true,
                'indisponivel' => false,
                'error' => null,
            ];
        }

        $cache = [];
        $houveErro = false;
        $ultimaFechada = null;

        foreach ($registros as $item) {
            $veiculo = $this->localizarVeiculo($item);

            if (! $veiculo) {
                continue;
            }

            $flags = [
                'excess_journey' => (bool) ($item['ExcessJourney'] ?? false),
                'excess_extended_journey' => (bool) ($item['ExcessExtendedJourney'] ?? false),
            ];

            $confirmacao = $this->confirmarLogin($veiculo, $motorista, $cache);

            if ($confirmacao['estado'] === 'erro') {
                $houveErro = true;

                continue;
            }

            if ($confirmacao['estado'] === 'ok' && $confirmacao['online']) {
                $this->aplicarJornada(
                    $motorista,
                    $veiculo,
                    $confirmacao['login_time'],
                    null,
                    true,
                    $flags
                );

                return [
                    'ok' => true,
                    'online' => true,
                    'confirmado' => true,
                    'indisponivel' => false,
                    'error' => null,
                ];
            }

            if ($confirmacao['estado'] === 'ok') {
                $ultimaFechada = [$veiculo, $confirmacao, $flags];
            }
        }

        if ($houveErro) {
            $motorista->forceFill([
                'verificado_em' => now(),
                'ultimo_erro' => 'Falha ao confirmar a sessão no driverlogs.',
            ])->save();

            return [
                'ok' => false,
                'online' => $motorista->estaOnline(),
                'confirmado' => false,
                'indisponivel' => true,
                'error' => 'Falha ao confirmar a sessão no driverlogs.',
            ];
        }

        if ($ultimaFechada !== null) {
            [$veiculo, $confirmacao, $flags] = $ultimaFechada;

            $this->aplicarJornada(
                $motorista,
                $veiculo,
                $confirmacao['login_time'],
                $confirmacao['logout_time'],
                false,
                $flags
            );
        } else {
            $motorista->forceFill([
                'status' => FrotaMotorista::STATUS_OFFLINE,
                'verificado_em' => now(),
                'veiculo_atual_id' => null,
                'ultimo_erro' => null,
            ])->save();
        }

        return [
            'ok' => true,
            'online' => false,
            'confirmado' => true,
            'indisponivel' => false,
            'error' => null,
        ];
    }

    /**
     * Cadastro de emergência: antes do primeiro sync, localiza o CPF no
     * driverstatus e cria o vínculo local com o usuário da plataforma.
     */
    public function cadastrarMotoristaPorCpf(User $user): ?FrotaMotorista
    {
        $cpf = $this->somenteDigitos($user->cpf);

        if ($cpf === '' || ! $this->client->configurado()) {
            return null;
        }

        $resposta = $this->client->driverStatus(['_limit' => 500]);

        if (! $resposta['ok']) {
            return null;
        }

        $registros = $resposta['body']['Data'] ?? [];

        if (! is_array($registros)) {
            return null;
        }

        foreach ($registros as $item) {
            if (! is_array($item) || empty($item['DriverCode'])) {
                continue;
            }

            if ($this->somenteDigitos($item['DriverCPF'] ?? null) !== $cpf) {
                continue;
            }

            return FrotaMotorista::updateOrCreate(
                ['code' => (int) $item['DriverCode']],
                [
                    'name' => $item['DriverName'] ?? null,
                    'cpf' => $cpf,
                    'user_id' => $user->id,
                    'ativo' => true,
                    'synced_at' => now(),
                ]
            );
        }

        return null;
    }

    /**
     * @param  array{excess_journey: bool, excess_extended_journey: bool}  $flags
     */
    private function aplicarJornada(
        FrotaMotorista $motorista,
        ?FrotaVeiculo $veiculo,
        ?Carbon $loginTime,
        ?Carbon $logoutTime,
        bool $online,
        array $flags
    ): void {
        if ($loginTime && $veiculo) {
            FrotaJornada::updateOrCreate(
                [
                    'frota_veiculo_id' => $veiculo->id,
                    'driver_cpf' => $motorista->cpf_digitos,
                    'login_time' => $loginTime,
                ],
                [
                    'frota_motorista_id' => $motorista->id,
                    'driver_name' => $motorista->name,
                    'logout_time' => $logoutTime,
                    'aberta' => $online,
                    'excess_journey' => $flags['excess_journey'],
                    'excess_extended_journey' => $flags['excess_extended_journey'],
                    'synced_at' => now(),
                ]
            );
        }

        $motorista->fill([
            'status' => $online ? FrotaMotorista::STATUS_ONLINE : FrotaMotorista::STATUS_OFFLINE,
            'verificado_em' => now(),
            'ultimo_login_em' => $online ? ($loginTime ?? $motorista->ultimo_login_em) : $motorista->ultimo_login_em,
            'ultimo_logout_em' => $logoutTime ?? ($online ? $motorista->ultimo_logout_em : now()),
            'ultimo_online_em' => $online ? now() : $motorista->ultimo_online_em,
            'veiculo_atual_id' => $online ? $veiculo?->id : null,
            'ultimo_erro' => null,
        ]);

        $motorista->save();
    }

    private function localizarMotorista(int $driverCode, string $cpf, ?string $nome): ?FrotaMotorista
    {
        $motorista = FrotaMotorista::where('code', $driverCode)->first();

        if ($motorista) {
            if ($cpf !== '' && $motorista->cpf_digitos === '') {
                $motorista->forceFill([
                    'cpf' => $cpf,
                    'user_id' => $this->usuariosPorCpf(collect([$cpf]))->get($cpf)?->id,
                ])->save();
            }

            return $motorista;
        }

        $usuario = $cpf !== '' ? $this->usuariosPorCpf(collect([$cpf]))->get($cpf) : null;

        return FrotaMotorista::create([
            'code' => $driverCode,
            'name' => $nome,
            'cpf' => $cpf !== '' ? $cpf : null,
            'user_id' => $usuario?->id,
            'ativo' => true,
            'synced_at' => now(),
        ]);
    }

    private function localizarVeiculo(array $item): ?FrotaVeiculo
    {
        $code = isset($item['VehicleCode']) ? (int) $item['VehicleCode'] : null;

        if (! $code) {
            return null;
        }

        $veiculo = FrotaVeiculo::where('code', $code)->first();

        if ($veiculo) {
            return $veiculo;
        }

        return FrotaVeiculo::create([
            'code' => $code,
            'name' => $item['VehicleName'] ?? null,
            'address' => isset($item['VehicleAddress']) ? (string) $item['VehicleAddress'] : null,
            'license_plate' => $item['VehicleLicensePlate'] ?? null,
            'ativo' => true,
            'synced_at' => now(),
        ]);
    }

    /**
     * @param  Collection<int, string>  $cpfs
     * @return Collection<string, User>
     */
    private function usuariosPorCpf(Collection $cpfs): Collection
    {
        $cpfs = $cpfs->filter(fn ($cpf) => is_string($cpf) && strlen($cpf) === 11)->unique()->values();

        if ($cpfs->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('cpf', $cpfs->all())
            ->get()
            ->keyBy(fn (User $user) => preg_replace('/\D/', '', (string) $user->cpf) ?? '');
    }

    /**
     * @return array{ok: bool, itens: array<int, mixed>, error: ?string}
     */
    private function paginar(callable $buscar): array
    {
        $itens = [];
        $offset = 0;
        $paginas = 0;

        do {
            $resposta = $buscar($offset, self::LIMITE_PAGINA);

            if (! $resposta['ok']) {
                return ['ok' => false, 'itens' => $itens, 'error' => $resposta['error']];
            }

            $dados = $resposta['body']['Data'] ?? [];
            $dados = is_array($dados) ? $dados : [];

            if ($dados === []) {
                break;
            }

            $itens = array_merge($itens, $dados);
            $ultimaPagina = (bool) ($resposta['body']['IsLastPage'] ?? true);
            $offset += self::LIMITE_PAGINA;
            $paginas++;
        } while (! $ultimaPagina && $paginas < self::MAX_PAGINAS);

        return ['ok' => true, 'itens' => $itens, 'error' => null];
    }

    private function parseData($valor): ?Carbon
    {
        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        try {
            $data = Carbon::parse($valor)->setTimezone(config('app.timezone'));

            // A Autotrac usa 1970-01-01 (ou 0001-01-01) para "sem data".
            if ((int) $data->year <= 1970) {
                return null;
            }

            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function somenteDigitos($valor): string
    {
        return preg_replace('/\D/', '', (string) $valor) ?? '';
    }
}
