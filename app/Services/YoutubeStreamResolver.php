<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Resolve vídeos do YouTube para URLs de stream direto (MP4/HLS),
 * usando a API Innertube com o client "ANDROID" (mesma técnica do yt-dlp).
 * As URLs expiram (~6h), por isso o resultado é cacheado.
 */
class YoutubeStreamResolver
{
    private const CACHE_TTL_SECONDS = 21600; // 6 horas

    public function resolve(string $videoId): ?string
    {
        $cacheKey = 'yt_stream_' . $videoId;

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($videoId) {
            return $this->resolveFromInnertube($videoId);
        });
    }

    private function resolveFromInnertube(string $videoId): ?string
    {
        try {
            $payload = json_encode([
                'context' => [
                    'client' => [
                        'clientName' => 'ANDROID',
                        'clientVersion' => '20.06.26',
                        'androidSdkVersion' => 34,
                        'hl' => 'pt-BR',
                        'gl' => 'BR',
                    ],
                ],
                'videoId' => $videoId,
            ]);

            $ch = curl_init('https://www.youtube.com/youtubei/v1/player?prettyPrint=false');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'User-Agent: com.google.android.youtube/20.06.26 (Linux; U; Android 14) gzip',
                    'Accept-Language: pt-BR,pt;q=0.9',
                ],
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$responseBody) {
                Log::warning('YouTube: resposta inválida do Innertube', [
                    'video_id' => $videoId,
                    'http_code' => $httpCode,
                ]);

                return null;
            }

            $data = json_decode($responseBody, true);

            if (!is_array($data) || !isset($data['streamingData'])) {
                Log::warning('YouTube: resposta sem streamingData', [
                    'video_id' => $videoId,
                    'status' => $data['playabilityStatus']['status'] ?? 'desconhecido',
                    'reason' => $data['playabilityStatus']['reason'] ?? null,
                ]);

                return null;
            }

            $formats = array_merge(
                $data['streamingData']['formats'] ?? [],
                $data['streamingData']['adaptiveFormats'] ?? []
            );

            // Prefere formatos progressivos (áudio + vídeo juntos) com maior resolução.
            // CRÍTICO: exige codec de áudio NO MESMO formato (ex.: avc1.42001E, mp4a.40.2).
            // Formatos só-vídeo (itag 137/299 etc.) ou só-áudio são descartados —
            // o app não consegue juntar trilhas e o arquivo resultante não reproduz.
            usort($formats, fn ($a, $b) => (int) ($b['height'] ?? 0) <=> (int) ($a['height'] ?? 0));

            foreach ($formats as $format) {
                $url = $format['url'] ?? null;
                $mime = $format['mimeType'] ?? '';
                $codecs = $format['codecs'] ?? '';

                $temAudioVideo = stripos($mime, 'mp4a') !== false
                    || stripos($mime, 'opus') !== false
                    || stripos($codecs, 'mp4a') !== false
                    || stripos($codecs, 'opus') !== false;

                if (
                    $url
                    && str_starts_with($url, 'http')
                    && empty($format['signatureCipher'])
                    && empty($format['cipher'])
                    && $temAudioVideo
                ) {
                    return $url;
                }
            }

            // Sem formato progressivo com áudio: não devolve HLS (o player do app
            // não reproduz m3u8 baixado como arquivo).
            return null;
        } catch (\Throwable $e) {
            Log::warning('Falha ao resolver stream do YouTube: ' . $e->getMessage(), ['video_id' => $videoId]);

            return null;
        }
    }

    public function extractVideoId(string $url): ?string
    {
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/youtube\.com\/embed\/([^&\n?#]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
