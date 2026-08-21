<?php

namespace App\Services;

class AiSummarizer
{
    /**
     * Solicita um parecer executivo estruturado ao provedor (Gemini) se a chave
     * estiver configurada. Caso contrário retorna o fallback local com a mesma
     * estrutura profissional.
     *
     * @param  array  $metrics  Payload detalhado de analyzeDetailed()
     * @return array ['source' => 'ai'|'fallback', 'ai_summary' => string, 'error' => string|null]
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
                    'maxOutputTokens' => 4096,
                ],
            ];

            $response = $this->sendGeminiRequest($model, $apiKey, $payload);

            if (($response['status'] ?? 0) < 200 || ($response['status'] ?? 0) >= 300) {
                return [
                    'source' => 'fallback',
                    'ai_summary' => $this->buildFallbackSummary($metrics, $trainingTitle),
                    'error' => 'Falha ao chamar Gemini: '.($response['status'] ?? 'sem_status').' - '.($response['body'] ?? 'sem_resposta'),
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
                'error' => 'Erro comunicação AI: '.$e->getMessage(),
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
            CURLOPT_TIMEOUT => 30,
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

    /**
     * Parecer executivo local com a mesma estrutura profissional do prompt da IA.
     */
    protected function buildFallbackSummary(array $metrics, ?string $trainingTitle = null): string
    {
        $training = $metrics['training'] ?? [];
        $kpis = $metrics['kpis'] ?? [];
        $avaliacao = $metrics['avaliacao'] ?? [];
        $title = $trainingTitle ?: ($training['titulo'] ?? 'treinamento selecionado');

        $percentAtivos = $kpis['percentual_usuarios_ativos'] ?? 0;
        $usuariosComCert = (int) ($kpis['usuarios_com_certificado'] ?? 0);
        $usuariosAtivos = (int) ($kpis['usuarios_ativos_total'] ?? 0);
        $concluidos = (int) ($kpis['concluidos'] ?? 0);
        $avgTime = $kpis['avg_time_human'] ?? '00:00:00';
        $avgDays = $kpis['avg_days_to_complete'] ?? null;

        $aprovados1a = $avaliacao['aprovados_1a_tentativa']['total'] ?? 0;
        $aprovados2a = $avaliacao['aprovados_2a_tentativa']['total'] ?? 0;
        $reassistiram = $avaliacao['reassistiram_conteudo']['total'] ?? 0;
        $aguardando = $avaliacao['aguardando_2a_tentativa']['total'] ?? 0;
        $semRegistro = $avaliacao['aprovados_sem_registro_tentativa'] ?? 0;
        $capturaInicio = $avaliacao['captura_inicio'] ?? null;
        $notaMediaSub = $avaliacao['nota_media_submissoes'] ?? null;
        $notaMediaApr = $avaliacao['nota_media_aprovacoes'] ?? null;

        $summary = [];
        $summary[] = '## Panorama Geral';
        $summary[] = "O treinamento \"{$title}\" alcançou {$usuariosComCert} participantes com certificado entre {$usuariosAtivos} usuários ativos elegíveis, o que representa {$percentAtivos}% de cobertura do público efetivo. Ao todo, {$concluidos} progressos foram concluídos, com tempo médio assistido de {$avgTime}.".($avgDays !== null ? " O prazo médio para conclusão foi de {$avgDays} dias." : '');

        $summary[] = '## Desempenho na Avaliação';
        $totalSubmissoes = $avaliacao['total_submissoes'] ?? 0;
        $avalPartes = [];
        $avalPartes[] = "Foram registradas {$totalSubmissoes} submissões de avaliação.";
        $desdeCaptura = $capturaInicio ? " ({$capturaInicio})" : '';
        if ($aprovados1a > 0) {
            $avalPartes[] = "{$aprovados1a} usuário(s) foram aprovados na 1ª tentativa.".($semRegistro > 0 ? " Deste total, {$semRegistro} concluíram antes do início do registro de tentativas na plataforma{$desdeCaptura} e constam nessa categoria sem registro individual de tentativa." : '');
        }
        if ($aprovados2a > 0) {
            $avalPartes[] = "{$aprovados2a} usuário(s) foram aprovados na 2ª tentativa, demonstrando aproveitamento após uma nova oportunidade.";
        }
        if ($reassistiram > 0) {
            $avalPartes[] = "{$reassistiram} usuário(s) reprovaram nas duas tentativas e precisaram reassistir o conteúdo para liberar nova avaliação.";
        }
        if ($aguardando > 0) {
            $avalPartes[] = "{$aguardando} usuário(s) ainda aguardam a 2ª tentativa.";
        }
        if ($semRegistro > 0) {
            $avalPartes[] = "O grupo de 1ª tentativa contempla os {$semRegistro} aprovados sem registro individual (período anterior ao início do registro na plataforma{$desdeCaptura}).";
        }
        if ($notaMediaSub !== null) {
            $avalPartes[] = "A nota média das submissões foi de {$notaMediaSub}%".($notaMediaApr !== null ? " e a média das aprovações de {$notaMediaApr}%" : '').'.';
        }
        $summary[] = implode(' ', $avalPartes);

        $summary[] = '## Pontos de Atenção e Recomendações';
        if ($aguardando > 0 || $reassistiram > 0) {
            $summary[] = "Há usuários com dificuldade de aprovação ({$reassistiram} com reprise de conteúdo e {$aguardando} aguardando nova tentativa). Recomenda-se reforço da comunicação, revisão orientada do conteúdo e acompanhamento individual desses colaboradores para garantir a conformidade do treinamento.";
        } else {
            $summary[] = 'Não há registros atuais de reprovação que exijam reprise de conteúdo, indicando boa assimilação do público participante.';
        }
        if ($percentAtivos < 75) {
            $summary[] = "A cobertura de {$percentAtivos}% está abaixo da meta desejada; recomenda-se intensificar as ações de engajamento e comunicação para elevar a adesão dos usuários pendentes.";
        }

        $summary[] = '## Conclusão Executiva';
        if ($percentAtivos >= 90 && $reassistiram === 0 && $aguardando === 0) {
            $summary[] = 'O treinamento demonstra execução exemplar, alta aderência e excelente desempenho na avaliação, validando a efetividade da plataforma e da estratégia de comunicação adotada.';
        } elseif ($percentAtivos >= 75) {
            $summary[] = 'O treinamento opera dentro das expectativas, com boa aceitação do público e desempenho satisfatório na avaliação, restando apenas refinamentos pontuais para acelerar a finalização dos participantes remanescentes.';
        } else {
            $summary[] = 'Embora o desempenho esteja abaixo do esperado, os dados coletados fornecem insights valiosos para ajustes na comunicação, no conteúdo e nos prazos das próximas ofertas.';
        }

        return implode("\n", $summary);
    }

    protected function normalizeAiText(string $text): string
    {
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);
        $text = preg_replace('/\*(.*?)\*/', '$1', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    protected function buildExecutivePrompt(array $metrics, ?string $trainingTitle = null): string
    {
        $title = $trainingTitle ?: ($metrics['training']['titulo'] ?? 'treinamento selecionado');
        $jsonMetrics = json_encode($metrics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Você é um analista executivo sênior de uma plataforma de treinamentos corporativos e deve escrever um parecer profissional, em português do Brasil, para a diretoria da empresa.

Contexto:
- Treinamento: {$title}
- Dados completos coletados pela plataforma: {$jsonMetrics}

Estrutura obrigatória do parecer (use exatamente estes cabeçalhos, iniciados por "## "):
## Panorama Geral
## Desempenho na Avaliação
## Pontos de Atenção e Recomendações
## Conclusão Executiva

Regras obrigatórias:
- Use exclusivamente os dados fornecidos. NÃO invente, extrapole ou arredonde números de forma diferente do apresentado.
- Panorama Geral: use `usuarios_com_certificado` como participantes validados e `percentual_usuarios_ativos` como cobertura relativa do público efetivo. Mencione tempo médio assistido (`avg_time_human`) e dias médios para conclusão (`avg_days_to_complete`) quando disponíveis.
- Desempenho na Avaliação: detalhe as quantidades de `aprovados_1a_tentativa`, `aprovados_2a_tentativa`, `reassistiram_conteudo` e `aguardando_2a_tentativa` (campos `total`). Cite os NOMES dos usuários de um grupo APENAS quando o total do grupo for menor ou igual a 10 (máximo de 10 nomes, separados por vírgula); grupos maiores devem ser descritos apenas pela quantidade, sem listar nomes, para não repetir o detalhamento da tabela. Se `total` for 0, diga explicitamente que não há casos. O grupo `aprovados_1a_tentativa` inclui usuários aprovados sem registro individual de tentativa (concluíram antes do início da captura em `captura_inicio`); quando `aprovados_sem_registro_tentativa` for maior que 0, mencione essa ressalva com o número exato. Mencione as notas médias quando disponíveis.
- Pontos de Atenção e Recomendações: destaque usuários com dificuldade (reprovações/reprise), cobertura abaixo de 75% e sugira ações concretas e objetivas.
- Conclusão Executiva: resumo final curto e firme baseado nos dados.
- Escreva em tom corporativo, conciso e direto. Use apenas os cabeçalhos "## " para seções; nos parágrafos, evite markdown, bullets e tabelas.

Dados para a análise:
{$jsonMetrics}
PROMPT;
    }
}
