<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Training;
use App\Models\UserProgress;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    private function serializeCertificate(Certificate $certificate): array
    {
        $certificate->loadMissing(['user', 'training']);

        return [
            'id' => $certificate->id,
            'codigo' => $certificate->codigo_certificado,
            'valido' => (bool) $certificate->valido,
            'data_emissao' => $certificate->data_emissao?->toISOString(),
            'data_inicio_assistencia' => $certificate->data_inicio_assistencia?->toISOString(),
            'data_finalizacao_assistencia' => $certificate->data_finalizacao_assistencia?->toISOString(),
            'tempo_assistido_segundos' => (int) ($certificate->tempo_assistido_segundos ?? 0),
            'tempo_assistido_formatado' => gmdate('H:i:s', max(0, (int) ($certificate->tempo_assistido_segundos ?? 0))),
            'porcentagem_assistida' => (int) ($certificate->porcentagem_assistida ?? 0),
            'training' => $certificate->training ? [
                'id' => $certificate->training->id,
                'titulo' => $certificate->training->titulo,
                'tipo' => $certificate->training->tipo,
                'carga_horaria' => (int) $certificate->training->carga_horaria,
            ] : null,
            'user' => $certificate->user ? [
                'id' => $certificate->user->id,
                'nome' => $certificate->user->nome,
                'cpf_formatado' => $certificate->user->getCpfFormatted(),
                'email' => $certificate->user->email,
                'telefone' => $certificate->user->telefone,
                'empresa' => $certificate->user->empresa,
                'cargo' => $certificate->user->cargo,
            ] : null,
            'validation_url' => $certificate->validation_url,
            'qr_code_url' => $certificate->qr_code_url,
            'download_url' => url('/api/v1/certificates/' . $certificate->id . '/download'),
        ];
    }

    public function mine(Request $request)
    {
        $user = $request->user();

        $certificates = Certificate::with(['training', 'user'])
            ->where('user_id', $user->id)
            ->where('valido', true)
            ->orderByDesc('data_emissao')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $certificates->map(fn ($c) => $this->serializeCertificate($c))->values(),
        ]);
    }

    public function download($id)
    {
        $user = request()->user();
        $certificate = Certificate::with(['user', 'training'])->findOrFail($id);

        if ($certificate->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Acesso negado.'], 403);
        }

        return app(\App\Http\Controllers\CertificateController::class)->streamPdfForApi($certificate);
    }

    public function downloadForTraining($trainingId)
    {
        $user = request()->user();
        $training = Training::findOrFail($trainingId);

        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->firstOrFail();

        if (!$progress->concluido || !$progress->avaliacao_aprovada) {
            return response()->json([
                'status' => 'error',
                'message' => 'O certificado só fica disponível após assistir todo o conteúdo e responder corretamente a avaliação.',
            ], 403);
        }

        $certificate = app(\App\Http\Controllers\CertificateController::class)->generateCertificate($training, $progress);

        return app(\App\Http\Controllers\CertificateController::class)->streamPdfForApi($certificate);
    }

    public function validateCertificate($codigo)
    {
        $certificate = Certificate::with(['user', 'training'])
            ->where('codigo_certificado', $codigo)
            ->first();

        if (!$certificate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Certificado não encontrado',
                'valido' => false,
            ], 404);
        }

        if (!$certificate->valido) {
            return response()->json([
                'status' => 'error',
                'message' => 'Certificado inválido ou revogado',
                'valido' => false,
                'data' => $this->serializeCertificate($certificate),
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Certificado válido',
            'valido' => true,
            'data' => $this->serializeCertificate($certificate),
        ]);
    }
}
