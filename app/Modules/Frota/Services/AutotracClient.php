<?php

namespace App\Modules\Frota\Services;

use App\Modules\Frota\Models\FrotaConfiguracao;
use App\Support\TenantManager;
use Illuminate\Support\Facades\Log;

/**
 * Cliente HTTP da ATIC API (Autotrac).
 *
 * Usa cURL nativo (o projeto não tem Guzzle em produção), autenticação
 * Basic + header Ocp-Apim-Subscription-Key, TLS 1.2+ e timeouts curtos.
 * Nunca lança exceção: devolve sempre um array com ok/status/body/error.
 */
class AutotracClient
{
    private ?FrotaConfiguracao $config;

    private ?int $timeout;

    private ?int $connectTimeout;

    public function __construct(?FrotaConfiguracao $config = null, ?int $timeout = null, ?int $connectTimeout = null)
    {
        $this->config = $config;
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
    }

    public function configuracao(): FrotaConfiguracao
    {
        return $this->config ??= FrotaConfiguracao::atual();
    }

    public function configurado(): bool
    {
        return $this->configuracao()->estaConfigurada();
    }

    /**
     * GET /v1/accounts — também usado como teste de conexão.
     *
     * @return array{ok: bool, status: ?int, body: ?array, error: ?string}
     */
    public function contas(): array
    {
        return $this->get('/v1/accounts');
    }

    /**
     * GET /v1/drivers
     *
     * @return array{ok: bool, status: ?int, body: ?array, error: ?string}
     */
    public function motoristas(int $offset = 0, int $limit = 500): array
    {
        return $this->get('/v1/drivers', [
            '_offset' => max(0, $offset),
            '_limit' => max(1, $limit),
        ]);
    }

    /**
     * GET /v1/accounts/{accountCode}/vehicles
     *
     * @return array{ok: bool, status: ?int, body: ?array, error: ?string}
     */
    public function veiculos(int $offset = 0, int $limit = 500): array
    {
        $conta = (int) $this->configuracao()->account_code;
        $pagina = '?_limit='.max(1, $limit).'&_offset='.max(0, $offset);

        return $this->get("/v1/accounts/{$conta}/vehicles".$pagina);
    }

    /**
     * GET /v1/driverstatus — jornadas/status dos motoristas.
     *
     * @param  array<string, mixed>  $filtros
     * @return array{ok: bool, status: ?int, body: ?array, error: ?string}
     */
    public function driverStatus(array $filtros = []): array
    {
        return $this->get('/v1/driverstatus', array_filter($filtros, static function ($valor) {
            return $valor !== null && $valor !== '';
        }));
    }

    /**
     * GET /v1/accounts/{accountCode}/vehicles/{vehicleCode}/driverlogs
     *
     * @param  array<string, mixed>  $params  _dateTimeFrom, _dateTimeTo, _limit, _offset, _last
     * @return array{ok: bool, status: ?int, body: ?array, error: ?string}
     */
    public function driverLogs(int $vehicleCode, array $params = []): array
    {
        $conta = (int) $this->configuracao()->account_code;
        $query = $params !== [] ? ('?'.http_build_query($params)) : '';

        return $this->get("/v1/accounts/{$conta}/vehicles/{$vehicleCode}/driverlogs".$query);
    }

    /**
     * @return array{ok: bool, status: ?int, body: ?array, error: ?string}
     */
    private function get(string $path, array $query = []): array
    {
        $config = $this->configuracao();

        if (! $config->estaConfigurada()) {
            return $this->falha(null, 'Integração Autotrac não configurada.');
        }

        $url = $config->urlBase().'/'.ltrim($path, '/');

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        $ch = curl_init($url);

        if ($ch === false) {
            return $this->falha(null, 'Não foi possível inicializar a conexão.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $config->usuario.':'.$config->senha,
            CURLOPT_HTTPHEADER => [
                'Ocp-Apim-Subscription-Key: '.$config->subscription_key,
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout ?? max(1, (int) config('autotrac.connect_timeout', 5)),
            CURLOPT_TIMEOUT => $this->timeout ?? max(2, (int) config('autotrac.timeout', 15)),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
        ]);

        $resposta = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erroCurl = curl_error($ch);
        curl_close($ch);

        if ($resposta === false || $erroCurl !== '') {
            $mensagem = $erroCurl !== '' ? $erroCurl : 'Falha de rede ao consultar a Autotrac.';
            $this->registrarFalha($path, $status, $mensagem);

            return $this->falha($status ?: null, $mensagem);
        }

        $body = json_decode((string) $resposta, true);

        if ($status < 200 || $status >= 300) {
            $mensagem = $this->mensagemDeErro($body, 'HTTP '.$status);
            $this->registrarFalha($path, $status, $mensagem);

            return $this->falha($status, $mensagem, is_array($body) ? $body : null);
        }

        if (! is_array($body)) {
            $this->registrarFalha($path, $status, 'Resposta inválida (JSON).');

            return $this->falha($status, 'Resposta inválida da Autotrac (JSON).');
        }

        return ['ok' => true, 'status' => $status, 'body' => $body, 'error' => null];
    }

    private function mensagemDeErro($body, string $fallback): string
    {
        if (is_array($body)) {
            foreach (['message', 'Message', 'error', 'Error', 'title', 'Title'] as $chave) {
                if (! empty($body[$chave]) && is_string($body[$chave])) {
                    return $body[$chave];
                }
            }

            if (isset($body['errors']) && is_array($body['errors'])) {
                $primeiro = reset($body['errors']);

                if (is_string($primeiro)) {
                    return $primeiro;
                }

                if (is_array($primeiro)) {
                    $primeiro = reset($primeiro);

                    if (is_string($primeiro)) {
                        return $primeiro;
                    }
                }
            }
        }

        return match ($fallback) {
            'HTTP 401' => 'Requer autenticação (usuário/senha da Autotrac inválidos).',
            'HTTP 403' => 'Acesso negado pela Autotrac (IP de saída ou chave de API não liberados).',
            'HTTP 422' => 'Regra de negócio inválida (verifique conta/veículo e Authorized Units).',
            'HTTP 404' => 'Recurso não encontrado na Autotrac.',
            default => $fallback,
        };
    }

    /**
     * @return array{ok: false, status: ?int, body: ?array, error: string}
     */
    private function falha(?int $status, string $erro, ?array $body = null): array
    {
        return ['ok' => false, 'status' => $status, 'body' => $body, 'error' => $erro];
    }

    private function registrarFalha(string $path, int $status, string $mensagem): void
    {
        try {
            Log::channel('system')->warning('autotrac_api_error', [
                'path' => $path,
                'status' => $status,
                'error' => $mensagem,
                'tenant_id' => app(TenantManager::class)->id(),
            ]);
        } catch (\Throwable $ignored) {
            // log nunca derruba a integração
        }
    }
}
