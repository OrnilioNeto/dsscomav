<?php

namespace App\Modules\LembretesWhatsapp\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LembretesWhatsapp\Models\TrainingReminder;
use Illuminate\Http\Request;

/**
 * Recebe eventos do WA-AKG (webhook) e atualiza o status de entrega dos lembretes.
 */
class WebhookController extends Controller
{
    public function status(Request $request)
    {
        $secret = (string) config('whatsapp.webhook_secret');

        if ($secret === '') {
            return response()->json(['ok' => false, 'error' => 'Webhook secret não configurado.'], 503);
        }

        $assinatura = (string) $request->header('X-Webhook-Signature', '');
        $esperada = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if ($assinatura === '' || ! hash_equals($esperada, $assinatura)) {
            return response()->json(['ok' => false, 'error' => 'Assinatura inválida.'], 401);
        }

        $payload = $request->json()->all();
        $evento = (string) ($payload['event'] ?? '');

        if ($evento !== '' && $evento !== 'message.status') {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $keyId = (string) ($payload['data']['keyId'] ?? '');
        $status = strtoupper((string) ($payload['data']['status'] ?? ''));

        if ($keyId === '' || $status === '') {
            return response()->json(['ok' => false, 'error' => 'Payload inválido.'], 422);
        }

        $reminder = TrainingReminder::where('gateway_message_id', $keyId)->first();

        if (! $reminder) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $reminder->update([
            'delivery_status' => $status,
            'delivered_at' => in_array($status, ['DELIVERED', 'READ'], true)
                ? ($reminder->delivered_at ?? now())
                : $reminder->delivered_at,
        ]);

        return response()->json(['ok' => true]);
    }
}
