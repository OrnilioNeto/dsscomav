<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Training;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TCPDF;

class CertificateController extends Controller
{
    public function downloadForTraining($trainingId)
    {
        $user = auth()->user();
        $training = Training::findOrFail($trainingId);

        $progress = UserProgress::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->firstOrFail();

        if (!$progress->concluido || !$progress->avaliacao_aprovada) {
            abort(403, 'O certificado só fica disponível após assistir todo o conteúdo e responder corretamente a avaliação.');
        }

        $certificate = $this->generateCertificate($training, $progress);

        return $this->streamPdf($certificate);
    }

    public function downloadCertificate($id)
    {
        $user = auth()->user();
        $certificate = Certificate::with(['user', 'training'])->findOrFail($id);

        if ($certificate->user_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        return $this->streamPdf($certificate);
    }

    public function validateCertificate($codigo)
    {
        $certificate = Certificate::with(['user', 'training'])->where('codigo_certificado', $codigo)->first();

        if (!$certificate) {
            return view('certificados.validacao', [
                'valido' => false,
                'mensagem' => 'Certificado não encontrado',
            ]);
        }

        if (!$certificate->valido) {
            return view('certificados.validacao', [
                'valido' => false,
                'mensagem' => 'Certificado inválido ou revogado',
                'certificate' => $certificate,
            ]);
        }

        return view('certificados.validacao', [
            'valido' => true,
            'certificate' => $certificate,
            'validationUrl' => $certificate->validation_url,
            'qrCodeUrl' => $certificate->qr_code_url,
        ]);
    }

    public function generateCertificate(Training $training, UserProgress $progress)
    {
        $user = $progress->user;

        // Verificar se certificado já existe
        $existing = Certificate::where('user_id', $user->id)
            ->where('training_id', $training->id)
            ->where('valido', true)
            ->first();

        if ($existing) {
            return $existing;
        }

        do {
            $codigo = strtoupper(Str::random(12));
        } while (Certificate::where('codigo_certificado', $codigo)->exists());

        $dataInicio = $progress->data_inicio ?? $progress->created_at ?? now();
        $dataFinalizacao = $progress->data_conclusao ?? now();

        // Salvar certificado no banco
        return Certificate::create([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'codigo_certificado' => $codigo,
            'data_emissao' => now(),
            'data_inicio_assistencia' => $dataInicio,
            'data_finalizacao_assistencia' => $dataFinalizacao,
            'tempo_assistido_segundos' => (int) $progress->tempo_assistido,
            'porcentagem_assistida' => (int) $progress->porcentagem_assistida,
            'caminho_arquivo' => null,
            'valido' => true,
        ]);
    }

    private function buildViewData(Certificate $certificate): array
    {
        $certificate->loadMissing(['user', 'training']);

        return [
            'certificate' => $certificate,
            'validationUrl' => $certificate->validation_url,
            'qrCodeUrl' => $certificate->qr_code_url,
            'tempoAssistidoFormatado' => gmdate('H:i:s', max(0, (int) $certificate->tempo_assistido_segundos)),
        ];
    }

    private function streamPdf(Certificate $certificate)
    {
        $certificate->loadMissing(['user', 'training']);

        $qrDataUri = null;
        $qrBinary = @file_get_contents($certificate->qr_code_url);
        if ($qrBinary !== false) {
            $qrDataUri = 'data:image/png;base64,' . base64_encode($qrBinary);
        }

        $html = view('certificados.pdf', [
            'certificate' => $certificate,
            'validationUrl' => $certificate->validation_url,
            'qrDataUri' => $qrDataUri,
            'tempoAssistidoFormatado' => gmdate('H:i:s', max(0, (int) $certificate->tempo_assistido_segundos)),
        ])->render();

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Plataforma DSS');
        $pdf->SetAuthor('Plataforma DSS');
        $pdf->SetTitle('Certificado - ' . $certificate->user->nome);
        $pdf->SetSubject('Certificado de Conclusão');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        return response($pdf->Output('certificado-' . $certificate->codigo_certificado . '.pdf', 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="certificado-' . $certificate->codigo_certificado . '.pdf"');
    }

    public function myCertificates()
    {
        $user = auth()->user();
        $certificates = Certificate::where('user_id', $user->id)
            ->where('valido', true)
            ->with(['training', 'user'])
            ->paginate(10);

        return view('certificados.meus_certificados', compact('certificates'));
    }
}
