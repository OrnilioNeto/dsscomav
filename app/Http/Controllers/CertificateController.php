<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\UserProgress;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use TCPDF;

class CertificateController extends Controller
{
    public function downloadCertificate($id)
    {
        $user = auth()->user();
        $certificate = Certificate::findOrFail($id);

        if ($certificate->user_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        $filePath = storage_path('certificates/' . $certificate->caminho_arquivo);

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'Certificado não encontrado'], 404);
        }

        return response()->download($filePath);
    }

    public function validateCertificate($codigo)
    {
        $certificate = Certificate::where('codigo_certificado', $codigo)->first();

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

        // Gerar código único
        $codigo = strtoupper(
            substr(md5($user->id . $training->id . time()), 0, 12)
        );

        // Gerar PDF do certificado
        $pdfPath = $this->generateCertificatePDF($user, $training, $codigo);

        // Gerar QR Code
        $qrCodePath = storage_path('certificates/qr_' . $codigo . '.png');
        QrCode::format('png')
            ->size(300)
            ->generate(url('/validar/' . $codigo), $qrCodePath);

        // Salvar certificado no banco
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'codigo_certificado' => $codigo,
            'data_emissao' => now(),
            'caminho_arquivo' => basename($pdfPath),
            'valido' => true,
        ]);

        return $certificate;
    }

    private function generateCertificatePDF($user, $training, $codigo)
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Plataforma DSS');
        $pdf->SetAuthor('Plataforma DSS');
        $pdf->SetTitle('Certificado - ' . $user->nome);
        $pdf->SetSubject('Certificado de Conclusão');

        $pdf->AddPage();

        // Cores
        $primaryColor = [0, 51, 102]; // Azul escuro
        $accentColor = [240, 120, 20]; // Laranja

        // Cabeçalho
        $pdf->SetFillColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Rect(0, 0, 210, 40, 'F');

        // Logo/Título
        $pdf->SetFont('helvetica', 'B', 28);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(10, 10);
        $pdf->Cell(0, 20, 'CERTIFICADO', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetXY(10, 28);
        $pdf->Cell(0, 10, 'de Conclusão de Treinamento', 0, 1, 'C');

        // Corpo do certificado
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(10, 60);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 8, 'Certificamos que', 0, 'C');

        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetXY(10, 75);
        $pdf->Cell(0, 12, strtoupper($user->nome), 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetXY(10, 92);
        $pdf->MultiCell(0, 8, 'Concluiu com êxito o seguinte treinamento:', 0, 'C');

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetFillColor(240, 120, 20);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(10, 110);
        $pdf->Cell(0, 15, $training->titulo, 0, 1, 'C', true);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 10);

        $pdf->SetXY(10, 130);
        $pdf->MultiCell(0, 8, 'Carga horária: ' . $training->carga_horaria . ' minutos | Data de emissão: ' . now()->format('d/m/Y'), 0, 'C');

        // QR Code
        $qrCodePath = storage_path('certificates/qr_' . $codigo . '.png');
        if (file_exists($qrCodePath)) {
            $pdf->Image($qrCodePath, 80, 150, 50, 50);
        }

        // Código do certificado
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetXY(10, 205);
        $pdf->Cell(0, 5, 'Código: ' . $codigo, 0, 1, 'C');

        // Rodapé
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetXY(10, 215);
        $pdf->Cell(0, 5, 'Este certificado é válido e consultável em: ' . url('/validar/' . $codigo), 0, 1, 'C');

        $fileName = 'cert_' . $user->id . '_' . $training->id . '_' . time() . '.pdf';
        $pdfPath = storage_path('certificates/' . $fileName);

        $pdf->Output($pdfPath, 'F');

        return $pdfPath;
    }

    public function myCertificates()
    {
        $user = auth()->user();
        $certificates = Certificate::where('user_id', $user->id)
            ->where('valido', true)
            ->with('training')
            ->paginate(10);

        return view('certificados.meus_certificados', compact('certificates'));
    }
}
