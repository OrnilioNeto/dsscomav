<?php

namespace Tests\Feature\Modules\LembretesWhatsapp;

use App\Models\Training;
use App\Models\User;
use App\Modules\LembretesWhatsapp\Models\TrainingReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SEGREDO = 'segredo-teste';

    private function criarReminder(string $keyId): TrainingReminder
    {
        $user = User::create([
            'nome' => 'Webhook Teste',
            'cpf' => '99999999999',
            'email' => 'webhook@teste.com',
            'password' => bcrypt('senha123'),
            'tipo_usuario' => 'motorista',
            'status' => 'ativo',
        ]);

        $training = Training::create([
            'titulo' => 'Treinamento Webhook',
            'tipo' => 'dss',
            'url_video' => 'https://www.youtube.com/watch?v=abc123',
            'tipo_video' => 'youtube',
            'tipo_usuario_permitido' => ['motorista'],
            'status' => 'ativo',
            'carga_horaria' => 10,
        ]);

        return TrainingReminder::create([
            'user_id' => $user->id,
            'training_id' => $training->id,
            'telefone' => '11999999999',
            'jid' => '558494017097@s.whatsapp.net',
            'mensagem' => 'Mensagem de teste',
            'status' => TrainingReminder::STATUS_ENVIADO,
            'gateway_message_id' => $keyId,
            'enviado_em' => now(),
        ]);
    }

    private function payload(string $keyId, string $status): string
    {
        return json_encode([
            'event' => 'message.status',
            'sessionId' => '3dvwb',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'keyId' => $keyId,
                'remoteJid' => '558494017097@s.whatsapp.net',
                'status' => $status,
            ],
        ]);
    }

    private function enviarWebhook(string $payload, string $assinatura)
    {
        return $this->call('POST', route('lembretes.whatsapp.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $assinatura,
        ], $payload);
    }

    public function test_webhook_atualiza_status_de_entrega_com_assinatura_valida(): void
    {
        config(['whatsapp.webhook_secret' => self::SEGREDO]);

        $reminder = $this->criarReminder('ABC123');
        $payload = $this->payload('ABC123', 'DELIVERED');
        $assinatura = 'sha256='.hash_hmac('sha256', $payload, self::SEGREDO);

        $this->enviarWebhook($payload, $assinatura)
            ->assertOk()
            ->assertJson(['ok' => true]);

        $reminder->refresh();

        $this->assertSame('DELIVERED', $reminder->delivery_status);
        $this->assertNotNull($reminder->delivered_at);
    }

    public function test_webhook_rejeita_assinatura_invalida(): void
    {
        config(['whatsapp.webhook_secret' => self::SEGREDO]);

        $this->criarReminder('ABC123');
        $payload = $this->payload('ABC123', 'DELIVERED');

        $this->enviarWebhook($payload, 'sha256=assinatura-errada')
            ->assertStatus(401);

        $this->assertDatabaseHas('training_reminders', [
            'gateway_message_id' => 'ABC123',
            'delivery_status' => null,
        ]);
    }

    public function test_webhook_ignora_keyid_desconhecido(): void
    {
        config(['whatsapp.webhook_secret' => self::SEGREDO]);

        $payload = $this->payload('NAOEXISTE', 'READ');
        $assinatura = 'sha256='.hash_hmac('sha256', $payload, self::SEGREDO);

        $this->enviarWebhook($payload, $assinatura)
            ->assertOk()
            ->assertJson(['ok' => true, 'ignored' => true]);
    }

    public function test_webhook_sem_segredo_configurado_responde_503(): void
    {
        config(['whatsapp.webhook_secret' => '']);

        $payload = $this->payload('ABC123', 'DELIVERED');

        $this->enviarWebhook($payload, 'sha256=qualquer')
            ->assertStatus(503);
    }
}
