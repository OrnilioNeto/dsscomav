<?php

namespace App\Services;

class AiSummarizer
{
    /**
     * Solicita um resumo ao provedor (Gemini) se chave estiver configurada.
     * Caso contrário retorna null e uma mensagem de fallback.
     * @param array $metrics
     * @param string|null $trainingTitle
     * @return array ['ai_summary' => string|null, 'error' => string|null]
     */
    public function summarize(array $metrics, ?string $trainingTitle = null): array
    {
        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            return [
                'source' => 'fallback',
                'ai_summary' => $this->buildFallbackSummary($metrics, $trainingTitle),
                'error' => 'GEMINI_API_KEY não configurada. Resumo local gerado.',
            ];
        }

        $model = env('GEMINI_MODEL', 'gemini-2.5-flash');

        $prompt = $this->buildExecutivePrompt($metrics, $trainingTitle);

        try {
            $payload = [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[
                        'text' => $prompt,
                    ]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.35,
                    'maxOutputTokens' => 2048,
                ],
            ];

            $response = $this->sendGeminiRequest($model, $apiKey, $payload);

            if (($response['status'] ?? 0) < 200 || ($response['status'] ?? 0) >= 300) {
                return [
                    'source' => 'fallback',
                    'ai_summary' => $this->buildFallbackSummary($metrics, $trainingTitle),
                    'error' => 'Falha ao chamar Gemini: ' . ($response['status'] ?? 'sem_status') . ' - ' . ($response['body'] ?? 'sem_resposta'),
                ];
            }

            $json = json_decode($response['body'] ?? '', true) ?: [];
            $text = data_get($json, 'candidates.0.content.parts.0.text');
            $finishReason = data_get($json, 'candidates.0.finishReason');
            if (empty($text)) {
                return [
                    'source' => 'fallback',
                    'ai_summary' => $this->buildFallbackSummary($metrics, $trainingTitle),
                    'error' => 'Gemini respondeu sem conteúdo útil. Resumo local gerado.',
                ];
            }

            $text = $this->normalizeAiText($text);

            if ($finishReason === 'MAX_TOKENS') {
                return [
                    'source' => 'fallback',
                    'ai_summary' => $text,
                    'error' => 'Gemini interrompeu a resposta por limite de tokens. Mostrando a versão recebida.',
                ];
            }

            return ['source' => 'ai', 'ai_summary' => $text, 'error' => null];

        } catch (\Throwable $e) {
            return [
                'source' => 'fallback',
                'ai_summary' => $this->buildFallbackSummary($metrics, $trainingTitle),
                'error' => 'Erro comunicação AI: ' . $e->getMessage(),
            ];
        }
    }

    protected function sendGeminiRequest(string $model, string $apiKey, array $payload): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            return [
                'status' => 0,
                'body' => $error ?: 'Falha desconhecida no cURL.',
            ];
        }

        return [
            'status' => $status,
            'body' => $body,
        ];
    }

    protected function buildFallbackSummary(array $metrics, ?string $trainingTitle = null): string
    {
        $title = $trainingTitle ?: 'treinamento selecionado';
        $percentAtivos = $metrics['percentual_usuarios_ativos'] ?? 0;
        $usuariosComCert = (int) ($metrics['usuarios_com_certificado'] ?? 0);
        $usuariosAtivos = (int) ($metrics['usuarios_ativos_total'] ?? 0);
        $totalProgressos = (int) ($metrics['total_progressos'] ?? 0);
        $concluidos = (int) ($metrics['concluidos'] ?? 0);
        $avgTime = $metrics['avg_time_human'] ?? '00:00:00';
        $avgDays = $metrics['avg_days_to_complete'] ?? null;

        $summary = [];
        $summary[] = "Panorama geral de {$title}: entre {$usuariosAtivos} usuários ativos, {$usuariosComCert} participantes completaram o treinamento ({$percentAtivos}% de conclusão).";
        $summary[] = "O tempo médio assistido foi de {$avgTime}, indicando consumo regular do conteúdo e sinal de engajamento com a trilha.";

        if ($avgDays !== null) {
            $summary[] = "O prazo médio para conclusão foi de {$avgDays} dias, refletindo a velocidade de assimilação e conformidade com os prazos estabelecidos.";
        }

        if ($percentAtivos >= 95) {
            $summary[] = "Os dados comprovam que o sistema atende às expectativas, com forte aderência e execução consistente, validando a efetividade da plataforma e da estratégia de comunicação adotada.";
        } elseif ($percentAtivos >= 75) {
            $summary[] = "Os dados indicam que o sistema está operando conforme esperado. O treinamento demonstra boa aceitação e viabilidade, com espaço para refinamento na comunicação para acelerar a finalização entre participantes remanescentes.";
        } else {
            $summary[] = "Embora o desempenho esteja abaixo do esperado, os dados coletados pelo sistema fornecem insights valiosos para ajustes na comunicação, conteúdo ou prazos nas próximas ofertas.";
        }

        return implode(' ', $summary);
    }

    protected function normalizeAiText(string $text): string
    {
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);
        $text = preg_replace('/\*(.*?)\*/', '$1', $text);
        $text = preg_replace('/^#+\s*/m', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    protected function buildExecutivePrompt(array $metrics, ?string $trainingTitle = null): string
    {
        $title = $trainingTitle ?: 'treinamento selecionado';
        $jsonMetrics = json_encode($metrics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
    Você é um analista executivo e deve escrever um parecer claro e objetivo, em português do Brasil, para a diretoria.

    Contexto:
    - Treinamento: {$title}
    - Métricas coletadas pela plataforma: {$jsonMetrics}

    IMPORTANTE (instruções obrigatórias ao usar os números):
    - Use exclusivamente `usuarios_com_certificado` como a contagem de participantes validados que concluíram o treinamento.
    - Use `percentual_usuarios_ativos` (ou `usuarios_ativos_total_effective`) como a métrica principal de cobertura relativa.
    - Não reporte `total_progressos`, `unique_users` ou outras contagens brutas como "número de participantes".
    - Se `usuarios_com_certificado` for maior que `usuarios_ativos_total_effective`, inclua uma frase curta explicativa: "observou-se que alguns certificados foram emitidos para usuários fora da base ativa no período; por isso a contagem de certificados pode exceder o total ativo." — sem atribuir culpa.

    Instruções de escrita:
    - Produza entre 3 e 5 frases com tom profissional e conciso.
    - Comece com panorama geral usando `usuarios_com_certificado` e `percentual_usuarios_ativos`.
    - Em seguida, destaque tempo médio assistido (`avg_time_human`) e dias médios para conclusão (`avg_days_to_complete`) quando disponíveis.
    - Se houver discrepância, adicione a frase explicativa curta conforme instruído.
    - Finalize com uma conclusão executiva curta baseada nos dados.
    - Não use markdown, bullets, tabelas ou JSON no texto final.

    Campos de referência (use como fonte de verdade): `usuarios_com_certificado`, `usuarios_ativos_total_effective`, `percentual_usuarios_ativos`, `avg_time_human`, `avg_days_to_complete`.

    Use estes dados para embasar o texto:
    {$jsonMetrics}
    PROMPT;
    }
}
