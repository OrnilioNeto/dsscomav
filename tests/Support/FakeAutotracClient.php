<?php

namespace Tests\Support;

use App\Modules\Frota\Services\AutotracClient;

/**
 * Cliente Autotrac em memória para testes (sem cURL/rede).
 */
class FakeAutotracClient extends AutotracClient
{
    public array $statusData = [];

    public array $logsData = [];

    public array $motoristasData = [];

    public array $veiculosData = [];

    public array $contasData = [];

    public ?string $statusError = null;

    public ?string $logsError = null;

    public array $statusCalls = [];

    public array $logsCalls = [];

    public function configurado(): bool
    {
        return true;
    }

    public function contas(): array
    {
        return $this->resposta(true, ['Data' => $this->contasData, 'IsLastPage' => true], null);
    }

    public function motoristas(int $offset = 0, int $limit = 500): array
    {
        return $this->resposta(true, ['Data' => $this->motoristasData, 'IsLastPage' => true], null);
    }

    public function veiculos(int $offset = 0, int $limit = 500): array
    {
        return $this->resposta(true, ['Data' => $this->veiculosData, 'IsLastPage' => true], null);
    }

    public function driverStatus(array $filtros = []): array
    {
        $this->statusCalls[] = $filtros;

        if ($this->statusError !== null) {
            return $this->resposta(false, null, $this->statusError);
        }

        return $this->resposta(true, [
            'Data' => $this->statusData,
            'Limit' => 500,
            'Offset' => 0,
            'IsLastPage' => true,
        ], null);
    }

    public function driverLogs(int $vehicleCode, array $params = []): array
    {
        $this->logsCalls[] = ['veiculo' => $vehicleCode, 'params' => $params];

        if ($this->logsError !== null) {
            return $this->resposta(false, null, $this->logsError);
        }

        return $this->resposta(true, [
            'Data' => $this->logsData[$vehicleCode] ?? [],
            'Limit' => 10,
            'Offset' => 0,
            'IsLastPage' => true,
        ], null);
    }

    private function resposta(bool $ok, ?array $body, ?string $error): array
    {
        return [
            'ok' => $ok,
            'status' => $ok ? 200 : 500,
            'body' => $body,
            'error' => $error,
        ];
    }
}
