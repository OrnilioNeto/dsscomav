<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use TCPDF;

/**
 * Geração do PDF do certificado.
 *
 * Certificados antigos (template_version < 2) continuam usando o layout
 * legado, exatamente como sempre foram emitidos. Certificados novos usam o
 * modelo único profissional, com imagem de fundo configurável por tenant.
 */
class CertificatePdfService
{
    private const PAGE_W = 297.0;

    private const PAGE_H = 210.0;

    private const NAVY = [11, 42, 74];

    private const GOLD = [201, 162, 39];

    private const SLATE = [71, 85, 105];

    private const MUTED = [100, 116, 139];

    /**
     * @param  string  $destination  'S' retorna bytes, 'I'/'D' enviam ao navegador.
     */
    public function output(Certificate $certificate, string $destination = 'S'): string
    {
        $certificate->loadMissing(['user', 'training']);

        if (((int) ($certificate->template_version ?? 0)) >= 2) {
            try {
                $pdf = $this->buildProfessional($certificate);
            } catch (\Throwable $e) {
                // Rede de segurança: nunca retorna 500 por falha do modelo novo.
                Log::error('Falha ao gerar certificado no modelo novo; usando layout legado.', [
                    'certificate_id' => $certificate->id,
                    'error' => $e->getMessage(),
                ]);

                $pdf = $this->buildLegacy($certificate);
            }
        } else {
            $pdf = $this->buildLegacy($certificate);
        }

        return $pdf->Output('certificado-'.$certificate->codigo_certificado.'.pdf', $destination);
    }

    /**
     * Layout legado (não alterar): mantém idênticos os certificados já emitidos.
     */
    private function buildLegacy(Certificate $certificate): TCPDF
    {
        $qrDataUri = null;
        $qrBinary = $this->fetchQrBinary($certificate);
        if ($qrBinary !== null) {
            $qrDataUri = 'data:image/png;base64,'.base64_encode($qrBinary);
        }

        $html = view('certificados.pdf', [
            'certificate' => $certificate,
            'validationUrl' => $certificate->validation_url,
            'qrDataUri' => $qrDataUri,
            'tempoAssistidoFormatado' => gmdate('H:i:s', max(0, (int) $certificate->tempo_assistido_segundos)),
        ])->render();

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(plataforma_nome());
        $pdf->SetAuthor(plataforma_nome());
        $pdf->SetTitle('Certificado - '.$certificate->user->nome);
        $pdf->SetSubject('Certificado de Conclusão');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf;
    }

    /**
     * Modelo único profissional (A4 paisagem), desenhado nativamente no TCPDF
     * para posicionamento preciso sobre a imagem de fundo.
     */
    private function buildProfessional(Certificate $certificate): TCPDF
    {
        $tenant = tenant();
        $training = $certificate->training;
        $user = $certificate->user;

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(plataforma_nome());
        $pdf->SetAuthor(plataforma_nome());
        $pdf->SetTitle('Certificado - '.$user->nome);
        $pdf->SetSubject('Certificado de Conclusão');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->AddPage();

        $this->drawBackground($pdf, $tenant);

        // Logo do tenant centralizada no topo
        $logoPath = $tenant?->logoFilePath('logo_certificado')
            ?? $tenant?->logoFilePath('logo')
            ?? (file_exists(public_path('images/logo-comav-transportes.png'))
                ? public_path('images/logo-comav-transportes.png')
                : null);

        if ($logoPath) {
            $this->drawImageFit($pdf, $logoPath, 0, 13, self::PAGE_W, 16, 'C');
        }

        // Título
        $pdf->SetTextColor(...self::NAVY);
        $pdf->SetFont('helvetica', 'B', 25);
        $pdf->SetXY(0, 31);
        $pdf->Cell(self::PAGE_W, 12, 'C E R T I F I C A D O', 0, 0, 'C');

        $pdf->SetTextColor(...self::MUTED);
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetXY(0, 43);
        $pdf->Cell(self::PAGE_W, 5, 'CERTIFICADO DE CONCLUSÃO', 0, 0, 'C');

        $pdf->SetDrawColor(...self::GOLD);
        $pdf->SetLineWidth(0.7);
        $pdf->Line(116, 50, 181, 50);

        if ($certificate->foi_reassistido) {
            // Assinatura TCPDF: RoundedRect($x, $y, $w, $h, $r, $round_corner, $style, $border_style, $fill_color)
            $pdf->RoundedRect(236, 15, 40, 6.5, 3.2, '1111', 'F', [], self::GOLD);
            $pdf->SetFont('helvetica', 'B', 6.8);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY(236, 15);
            $pdf->Cell(40, 6.5, 'REASSISTIDO', 0, 0, 'C');
        }

        // Beneficiário
        $pdf->SetTextColor(...self::SLATE);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetXY(28, 55);
        $pdf->Cell(241, 6, 'Certificamos que', 0, 0, 'C');

        $tamanhoNome = mb_strlen($user->nome) > 42 ? 17 : (mb_strlen($user->nome) > 30 ? 21 : 25);
        $pdf->SetTextColor(...self::NAVY);
        $pdf->SetFont('helvetica', 'B', $tamanhoNome);
        $pdf->SetXY(28, 62);
        $pdf->Cell(241, 11, $user->nome, 0, 0, 'C');

        $pdf->SetTextColor(...self::SLATE);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetXY(28, 74);
        $pdf->Cell(241, 5, 'CPF: '.$user->getCpfFormatted().'  |  E-mail: '.($user->email ?: 'Não informado').'  |  Empresa: '.($user->empresa ?: plataforma_nome()), 0, 0, 'C');

        // Texto narrativo (conteúdo do treinamento)
        $dataConclusao = $certificate->data_finalizacao_assistencia?->format('d/m/Y')
            ?? $certificate->data_emissao?->format('d/m/Y')
            ?? '';

        $descricao = trim(preg_replace('/\s+/', ' ', strip_tags((string) $training->descricao)));
        if (mb_strlen($descricao) > 420) {
            $descricao = mb_substr($descricao, 0, 417).'...';
        }

        $narrativa = 'participou do treinamento "'.$training->titulo.'", concluído em '.$dataConclusao
            .' com carga horária de '.$training->carga_horaria_formatada_extenso
            .', obtendo aproveitamento satisfatório na avaliação.';

        if ($descricao !== '') {
            $narrativa .= ' '.$descricao;
        }

        $pdf->SetTextColor(...self::SLATE);
        $pdf->SetFont('helvetica', '', 9.8);
        $pdf->SetXY(35, 83);
        $pdf->MultiCell(227, 5.1, $narrativa, 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'T', false);

        // Grade com os dados do certificado
        $gridY = max(112, $pdf->GetY() + 5);
        $gridY = min($gridY, 120);
        $this->drawGrid($pdf, $certificate, $gridY);

        // Assinaturas
        $this->drawSignatures($pdf, $certificate, $tenant);

        // QR Code + auditoria
        $this->drawQrAndAudit($pdf, $certificate);

        return $pdf;
    }

    private function drawBackground(TCPDF $pdf, ?Tenant $tenant): void
    {
        $bg = $tenant?->fundoCertificadoFilePath()
            ?? PlatformSetting::fundoCertificadoGlobalPath();

        if (! $bg) {
            $default = public_path('images/certificado-fundo.png');
            $bg = file_exists($default) ? $default : null;
        }

        $bg = $bg ? $this->supportedImagePath($bg) : null;

        if ($bg) {
            $pdf->Image($bg, 0, 0, self::PAGE_W, self::PAGE_H, '', '', '', false, 300, '', false, false, 0);

            return;
        }

        // Sem imagem de fundo: moldura dourada discreta
        $pdf->SetDrawColor(...self::GOLD);
        $pdf->SetLineWidth(1.2);
        $pdf->Rect(8, 8, self::PAGE_W - 16, self::PAGE_H - 16);
    }

    /**
     * Garante que a imagem é suportada pelo TCPDF (PNG/JPEG). WebP/GIF são
     * convertidos para PNG via GD quando possível; caso contrário, ignora.
     */
    private function supportedImagePath(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }

        $mime = $info['mime'] ?? '';

        if (in_array($mime, ['image/png', 'image/jpeg'], true)) {
            return $path;
        }

        $img = match ($mime) {
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            'image/gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($path) : null,
            default => null,
        };

        if (! $img) {
            return null;
        }

        $destino = $this->storageTempPath($path);

        if (! @imagepng($img, $destino)) {
            imagedestroy($img);

            return null;
        }

        imagedestroy($img);

        return is_file($destino) ? $destino : null;
    }

    private function drawGrid(TCPDF $pdf, Certificate $certificate, float $y): void
    {
        $training = $certificate->training;
        $user = $certificate->user;

        $campos = [
            ['Empresa', $user->empresa ?: plataforma_nome()],
            ['Tipo de usuário', ucfirst((string) ($user->tipo_usuario ?: 'Não informado'))],
            ['Telefone', $user->telefone ?: 'Não informado'],
            ['E-mail', $user->email ?: 'Não informado'],
            ['Carga horária', $training->carga_horaria_formatada_extenso],
            ['Início do treinamento', $certificate->data_inicio_assistencia?->format('d/m/Y H:i') ?? 'Não informado'],
            ['Fim do treinamento', $certificate->data_finalizacao_assistencia?->format('d/m/Y H:i') ?? 'Não informado'],
            ['Tempo assistido', gmdate('H:i:s', max(0, (int) $certificate->tempo_assistido_segundos))],
        ];

        $validade = $certificate->data_validade;
        if ($validade) {
            $campos[] = ['Válido até', $validade->format('d/m/Y')];
        }

        $colunas = 4;
        $largura = 59.0;
        $inicioX = 26.0;
        $altura = 13.0;

        // Linha superior da grade
        $pdf->SetDrawColor(...self::GOLD);
        $pdf->SetLineWidth(0.35);
        $pdf->Line($inicioX, $y - 3, self::PAGE_W - $inicioX, $y - 3);

        foreach ($campos as $i => $campo) {
            $col = $i % $colunas;
            $linha = intdiv($i, $colunas);

            $this->drawField(
                $pdf,
                $inicioX + ($col * $largura),
                $y + ($linha * $altura),
                $largura - 4,
                $campo[0],
                $campo[1]
            );
        }

        $linhas = (int) ceil(count($campos) / $colunas);
        $fimGrade = $y + ($linhas - 1) * $altura + 10;

        $pdf->SetDrawColor(...self::GOLD);
        $pdf->SetLineWidth(0.35);
        $pdf->Line($inicioX, $fimGrade, self::PAGE_W - $inicioX, $fimGrade);
    }

    private function drawField(TCPDF $pdf, float $x, float $y, float $w, string $label, string $value): void
    {
        $pdf->SetTextColor(...self::MUTED);
        $pdf->SetFont('helvetica', 'B', 6.6);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 3.4, mb_strtoupper($label), 0, 0, 'L');

        $pdf->SetTextColor(...self::NAVY);
        $pdf->SetFont('helvetica', '', 8.6);
        $pdf->SetXY($x, $y + 3.6);
        $pdf->Cell($w, 4.2, $this->truncar($value, 34), 0, 0, 'L');
    }

    private function drawSignatures(TCPDF $pdf, Certificate $certificate, ?Tenant $tenant): void
    {
        $linhaY = 173.0;
        $user = $certificate->user;

        // Aluno
        $pdf->SetDrawColor(150, 160, 175);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(105, $linhaY, 180, $linhaY);

        $pdf->SetTextColor(...self::MUTED);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY(105, $linhaY + 1.2);
        $pdf->Cell(75, 3.6, 'ALUNO', 0, 0, 'C');

        $pdf->SetTextColor(...self::NAVY);
        $pdf->SetFont('helvetica', '', 8.2);
        $pdf->SetXY(105, $linhaY + 4.8);
        $pdf->Cell(75, 4, $this->truncar($user->nome, 40), 0, 0, 'C');

        // Instrutor
        $pdf->SetDrawColor(150, 160, 175);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(202, $linhaY, 277, $linhaY);

        $pdf->SetTextColor(...self::MUTED);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY(202, $linhaY + 1.2);
        $pdf->Cell(75, 3.6, 'INSTRUTOR', 0, 0, 'C');

        $pdf->SetTextColor(...self::NAVY);
        $pdf->SetFont('helvetica', '', 8.2);
        $pdf->SetXY(202, $linhaY + 4.8);
        $pdf->Cell(75, 4, $this->truncar($tenant?->getInstrutorNome() ?? 'Ornilio Machado Neto', 42), 0, 0, 'C');

        $pdf->SetTextColor(...self::SLATE);
        $pdf->SetFont('helvetica', '', 7.2);
        $pdf->SetXY(202, $linhaY + 8.6);
        $qualificacao = ($tenant?->getInstrutorQualificacao() ?? 'Tec Segurança do Trabalho').' - RG: '.($tenant?->getInstrutorRg() ?? '10827');
        $pdf->Cell(75, 3.6, $this->truncar($qualificacao, 50), 0, 0, 'C');
    }

    private function drawQrAndAudit(TCPDF $pdf, Certificate $certificate): void
    {
        $x = 18.0;
        $y = 148.0;

        $qrBinary = $this->fetchQrBinary($certificate);
        if ($qrBinary !== null) {
            $pdf->Image('@'.$qrBinary, $x, $y, 25, 25, 'PNG', '', '', false, 300, '', false, false, 0);
        }

        $textoX = 46.0;
        $textoY = 148.5;

        $pdf->SetTextColor(...self::MUTED);
        $pdf->SetFont('helvetica', 'B', 6.4);
        $pdf->SetXY($textoX, $textoY);
        $pdf->Cell(52, 3.2, 'CÓDIGO', 0, 0, 'L');

        $pdf->SetTextColor(...self::NAVY);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY($textoX, $textoY + 3);
        $pdf->Cell(52, 4, $certificate->codigo_certificado, 0, 0, 'L');

        $pdf->SetTextColor(...self::SLATE);
        $pdf->SetFont('helvetica', '', 7.2);
        $pdf->SetXY($textoX, $textoY + 9);
        $pdf->Cell(52, 3.6, 'Emitido em: '.($certificate->data_emissao?->format('d/m/Y H:i') ?? '-'), 0, 0, 'L');

        $pdf->SetXY($textoX, $textoY + 13);
        $pdf->Cell(52, 3.6, 'Válido: Sim', 0, 0, 'L');

        $pdf->SetTextColor(...self::MUTED);
        $pdf->SetFont('helvetica', '', 5.8);
        $pdf->SetXY($textoX, $textoY + 17.5);
        $pdf->Cell(52, 3.2, 'Valide em: '.$this->truncar($certificate->validation_url, 46), 0, 0, 'L');
    }

    private function drawImageFit(TCPDF $pdf, string $path, float $x, float $y, float $boxW, float $boxH, string $align = 'L'): void
    {
        $path = $this->supportedImagePath($path);
        if (! $path) {
            return;
        }

        $path = $this->trimmedImagePath($path);

        $info = @getimagesize($path);
        if (! $info) {
            return;
        }

        [$origW, $origH] = $info;
        $ratio = min($boxW / $origW, $boxH / $origH);
        $w = $origW * $ratio;
        $h = $origH * $ratio;

        if ($align === 'C') {
            $x += ($boxW - $w) / 2;
        } elseif ($align === 'R') {
            $x += $boxW - $w;
        }

        $pdf->Image($path, $x, $y, $w, $h, '', '', '', true, 300, '', false, false, 0);
    }

    /**
     * Remove margens transparentes/brancas de logos (ex.: logo com grande
     * espaço vazio), gerando um PNG temporário recortado para o certificado.
     */
    private function trimmedImagePath(string $path): string
    {
        try {
            if (! function_exists('imagecreatefrompng') || ! function_exists('imagecreatetruecolor') || ! is_file($path)) {
                return $path;
            }

            $info = @getimagesize($path);
            if (! $info) {
                return $path;
            }

            // Evita consumo excessivo de memória em imagens muito grandes.
            if (($info[0] * $info[1]) > 2500000) {
                return $path;
            }

            $img = match ($info['mime'] ?? '') {
                'image/png' => @imagecreatefrompng($path),
                'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : null,
                'image/gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($path) : null,
                default => null,
            };

            if (! $img) {
                return $path;
            }

            $largura = imagesx($img);
            $altura = imagesy($img);
            $minX = $largura;
            $minY = $altura;
            $maxX = -1;
            $maxY = -1;

            for ($y = 0; $y < $altura; $y++) {
                for ($x = 0; $x < $largura; $x++) {
                    $cor = imagecolorat($img, $x, $y);
                    $alpha = ($cor >> 24) & 0x7F;
                    $r = ($cor >> 16) & 0xFF;
                    $g = ($cor >> 8) & 0xFF;
                    $b = $cor & 0xFF;

                    // Considera fundo: transparente ou quase-branco
                    if ($alpha > 100 || ($r > 240 && $g > 240 && $b > 240)) {
                        continue;
                    }

                    $minX = min($minX, $x);
                    $minY = min($minY, $y);
                    $maxX = max($maxX, $x);
                    $maxY = max($maxY, $y);
                }
            }

            if ($maxX < 0) {
                imagedestroy($img);

                return $path;
            }

            // Pequena margem de respiro
            $minX = max(0, $minX - 2);
            $minY = max(0, $minY - 2);
            $maxX = min($largura - 1, $maxX + 2);
            $maxY = min($altura - 1, $maxY + 2);

            $novaLargura = $maxX - $minX + 1;
            $novaAltura = $maxY - $minY + 1;

            if ($novaLargura >= $largura && $novaAltura >= $altura) {
                imagedestroy($img);

                return $path;
            }

            $recortada = imagecreatetruecolor($novaLargura, $novaAltura);
            if (! $recortada) {
                imagedestroy($img);

                return $path;
            }

            imagealphablending($recortada, false);
            imagesavealpha($recortada, true);
            imagecopy($recortada, $img, 0, 0, $minX, $minY, $novaLargura, $novaAltura);
            imagedestroy($img);

            $destino = $this->storageTempPath($path.'|crop');

            if (! @imagepng($recortada, $destino)) {
                imagedestroy($recortada);

                return $path;
            }

            imagedestroy($recortada);

            return is_file($destino) ? $destino : $path;
        } catch (\Throwable $e) {
            Log::warning('Falha ao recortar logo do certificado; usando imagem original.', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return $path;
        }
    }

    /**
     * Caminho temporário dentro de storage/app/certificado (evita restrições
     * de open_basedir do /tmp em hospedagens compartilhadas).
     */
    private function storageTempPath(string $seed): string
    {
        $dir = storage_path('app/certificado');

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir.DIRECTORY_SEPARATOR.'img_'.md5($seed).'.png';
    }

    /**
     * Baixa o QR Code com timeout curto para nunca travar a geração do PDF.
     */
    private function fetchQrBinary(Certificate $certificate): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 8,
            ],
        ]);

        $binary = @file_get_contents($certificate->qr_code_url, false, $context);

        // Só usa se for realmente um PNG (evita exceção no TCPDF quando o
        // serviço externo devolve HTML/erro).
        if ($binary === false || ! str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return null;
        }

        return $binary;
    }

    private function truncar(string $valor, int $limite): string
    {
        $valor = trim($valor);

        if (mb_strlen($valor) <= $limite) {
            return $valor;
        }

        return mb_substr($valor, 0, $limite - 3).'...';
    }
}
