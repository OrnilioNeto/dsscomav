<?php

namespace App\Modules\LembretesWhatsapp\Services;

/**
 * Cliente HTTP do gateway WA-AKG (Baileys).
 *
 * Usa cURL nativo (sem dependência de Guzzle — o projeto não o possui em
 * produção) e o envio unitário (POST /api/messages/{sessionId}/{jid}/send),
 * nunca o endpoint de broadcast, para manter mensagens personalizadas e
 * rastreáveis.
 */
class WhatsappGatewayClient
{
    public function configurado(): bool
    {
        return (bool) config('whatsapp.enabled')
            && filled(config('whatsapp.api_key'))
            && filled(config('whatsapp.session_id'));
    }

    /**
     * Consulta o status da sessão no gateway.
     *
     * @return array{connected: bool, status: string, error: ?string}
     */
    public function sessionStatus(): array
    {
        if (! $this->configurado()) {
            return ['connected' => false, 'status' => 'NAO_CONFIGURADO', 'error' => 'Integração WhatsApp não configurada.'];
        }

        $response = $this->request('GET', '/api/sessions/'.rawurlencode((string) config('whatsapp.session_id')));

        if ($response['error'] !== null) {
            return ['connected' => false, 'status' => 'SEM_CONEXAO', 'error' => $response['error']];
        }

        if ($response['status'] < 200 || $response['status'] >= 300) {
            return [
                'connected' => false,
                'status' => 'ERRO_HTTP_'.$response['status'],
                'error' => $this->errorMessage($response['body'], 'Falha ao consultar a sessão.'),
            ];
        }

        $status = (string) ($response['body']['data']['status'] ?? 'DESCONHECIDO');

        return [
            'connected' => $status === 'CONNECTED',
            'status' => $status,
            'error' => null,
        ];
    }

    /**
     * Envia uma mensagem de texto para um JID.
     *
     * @return array{ok: bool, message_id: ?string, error: ?string, http_status: ?int}
     */
    public function sendText(string $jid, string $mensagem): array
    {
        if (! $this->configurado()) {
            return ['ok' => false, 'message_id' => null, 'error' => 'Integração WhatsApp não configurada.', 'http_status' => null];
        }

        $session = rawurlencode((string) config('whatsapp.session_id'));
        $encodedJid = rawurlencode($jid);

        $response = $this->request(
            'POST',
            "/api/messages/{$session}/{$encodedJid}/send",
            ['message' => ['text' => $mensagem]]
        );

        if ($response['error'] !== null) {
            return ['ok' => false, 'message_id' => null, 'error' => $response['error'], 'http_status' => null];
        }

        if ($response['status'] < 200 || $response['status'] >= 300) {
            return [
                'ok' => false,
                'message_id' => null,
                'error' => $this->errorMessage($response['body'], 'HTTP '.$response['status']),
                'http_status' => $response['status'],
            ];
        }

        $messageId = $response['body']['data']['key']['id'] ?? $response['body']['data']['keyID'] ?? null;

        if (blank($messageId)) {
            return [
                'ok' => false,
                'message_id' => null,
                'error' => 'Gateway não retornou o identificador da mensagem.',
                'http_status' => $response['status'],
            ];
        }

        return ['ok' => true, 'message_id' => (string) $messageId, 'error' => null, 'http_status' => $response['status']];
    }

    /**
     * Verifica no WhatsApp quais números existem e devolve o JID canônico de
     * cada um (resolve casos em que o nono dígito não faz parte do JID real).
     *
     * Retorna null quando não foi possível verificar (integração desativada,
     * gateway fora/sessão caída) — nesse caso o chamador deve usar o fallback.
     *
     * @param  array<int, string>  $numbers  números com DDI (ex.: 5511999999999)
     * @return array<string, array{exists: bool, jid: ?string}>|null keyed pelos dígitos informados
     */
    public function checkNumbers(array $numbers): ?array
    {
        $numbers = array_values(array_unique(array_filter(array_map(
            fn ($number) => preg_replace('/\D/', '', (string) $number),
            $numbers
        ))));

        if (! $this->configurado() || $numbers === []) {
            return null;
        }

        $session = rawurlencode((string) config('whatsapp.session_id'));
        $map = [];

        foreach (array_chunk($numbers, 50) as $chunk) {
            $chunk = array_values($chunk);
            $response = $this->request('POST', "/api/chat/{$session}/check", ['numbers' => $chunk]);

            if ($response['error'] !== null || $response['status'] < 200 || $response['status'] >= 300) {
                return null;
            }

            $results = $response['body']['data']['results'] ?? null;

            if (! is_array($results)) {
                return null;
            }

            foreach (array_values($results) as $index => $result) {
                $number = preg_replace('/\D/', '', (string) ($result['number'] ?? ''));

                if ($number === '') {
                    $number = $chunk[$index] ?? '';
                }

                if ($number === '') {
                    continue;
                }

                $map[$number] = [
                    'exists' => (bool) ($result['exists'] ?? false),
                    'jid' => $result['jid'] ?? null,
                ];
            }
        }

        return $map;
    }

    /**
     * Executa a chamada HTTP (cURL). Separado para permitir substituição em testes.
     *
     * @param  array<string, mixed>  $payload
     * @return array{status: int, body: ?array<string, mixed>, error: ?string}
     */
    protected function request(string $method, string $path, array $payload = []): array
    {
        if (! function_exists('curl_init')) {
            return ['status' => 0, 'body' => null, 'error' => 'Extensão cURL do PHP não disponível.'];
        }

        $url = rtrim((string) config('whatsapp.gateway_url'), '/').$path;

        $headers = [
            'Accept: application/json',
            'X-API-Key: '.(string) config('whatsapp.api_key'),
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int) config('whatsapp.timeout', 20),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($payload !== []) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $errno !== 0) {
            return ['status' => 0, 'body' => null, 'error' => 'Gateway indisponível: '.($error !== '' ? $error : 'erro de conexão')];
        }

        $decoded = json_decode((string) $raw, true);

        return [
            'status' => $status,
            'body' => is_array($decoded) ? $decoded : null,
            'error' => null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function errorMessage(?array $body, string $fallback): string
    {
        if (is_array($body)) {
            foreach (['error', 'message'] as $key) {
                if (! empty($body[$key]) && is_string($body[$key])) {
                    return $body[$key];
                }
            }
        }

        return $fallback;
    }
}
