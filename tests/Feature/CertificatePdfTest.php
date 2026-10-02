<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Training;
use App\Models\User;
use App\Services\CertificatePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificatePdfTest extends TestCase
{
    use RefreshDatabase;

    private function criarCertificado(?int $templateVersion): Certificate
    {
        $user = User::create([
            'nome' => 'João da Silva',
            'cpf' => '22222222222',
            'email' => 'joao@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
            'telefone' => '(84) 99999-0000',
            'empresa' => 'Transportadora Teste',
        ]);

        $training = Training::create([
            'titulo' => 'Plano de Emergência',
            'descricao' => 'Treinamento sobre procedimentos de emergência e evacuação da área.',
            'tipo' => 'dss',
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'carga_horaria' => 20,
            'carga_horaria_segundos' => 30,
            'tipo_usuario_permitido' => ['motorista'],
            'status' => 'ativo',
        ]);

        return Certificate::create([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'codigo_certificado' => 'TESTE'.strtoupper(substr(md5(uniqid()), 0, 7)),
            'data_emissao' => now(),
            'data_inicio_assistencia' => now()->subHour(),
            'data_finalizacao_assistencia' => now(),
            'tempo_assistido_segundos' => 1230,
            'porcentagem_assistida' => 100,
            'valido' => true,
            'template_version' => $templateVersion,
        ]);
    }

    public function test_pdf_do_modelo_profissional_e_gerado(): void
    {
        $certificate = $this->criarCertificado(2);

        $pdf = app(CertificatePdfService::class)->output($certificate);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(10000, strlen($pdf));
    }

    public function test_pdf_legado_continua_sendo_gerado(): void
    {
        $certificate = $this->criarCertificado(null);

        $pdf = app(CertificatePdfService::class)->output($certificate);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(10000, strlen($pdf));
    }
}
