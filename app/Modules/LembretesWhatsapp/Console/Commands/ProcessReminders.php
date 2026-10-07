<?php

namespace App\Modules\LembretesWhatsapp\Console\Commands;

use App\Modules\LembretesWhatsapp\Models\TrainingReminder;
use App\Modules\LembretesWhatsapp\Services\ReminderDispatcher;
use App\Modules\LembretesWhatsapp\Services\WhatsappGatewayClient;
use App\Support\TenantManager;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessReminders extends Command
{
    protected $signature = 'lembretes:processar
                            {--limit=1 : Máximo de mensagens por tenant nesta execução}
                            {--dry-run : Apenas mostra o que seria enviado}
                            {--force : Ignora a janela de horário e a pausa por falhas}';

    protected $description = 'Envia os lembretes de treinamento na fila via WhatsApp (gateway WA-AKG)';

    public function handle(WhatsappGatewayClient $client): int
    {
        if (! config('whatsapp.enabled')) {
            $this->warn('Integração WhatsApp desativada (WA_REMINDERS_ENABLED=false). Nada a processar.');

            return self::SUCCESS;
        }

        $enviados = 0;

        app(TenantManager::class)->runForEachTenant(function () use ($client, &$enviados) {
            $enviados += $this->processarTenant($client);
        });

        $this->info("Lembretes processados nesta execução: {$enviados}.");

        return self::SUCCESS;
    }

    private function processarTenant(WhatsappGatewayClient $client): int
    {
        if (! $client->configurado()) {
            $this->warn('Gateway WhatsApp não configurado (WA_GATEWAY_URL/API_KEY/SESSION).');

            return 0;
        }

        if (! $this->option('force') && ! ReminderDispatcher::dentroDaJanela()) {
            $this->line('Fora da janela de envio (dias úteis '.config('whatsapp.window_start').'–'.config('whatsapp.window_end').').');

            return 0;
        }

        // Fila vazia: sai antes de contar teto/pausa e de pingar o gateway.
        if (! TrainingReminder::vencidos()->exists()) {
            return 0;
        }

        if (! $this->option('force')) {
            $falhas = $this->falhasConsecutivas();
            $maxFalhas = (int) config('whatsapp.max_consecutive_failures', 5);

            if ($maxFalhas > 0 && $falhas >= $maxFalhas) {
                $this->error("Pausado: {$falhas} falhas consecutivas. Verifique a sessão/números e use --force para retomar.");

                return 0;
            }
        }

        $hoje = Carbon::now(config('app.timezone'))->toDateString();
        $enviadosHoje = TrainingReminder::query()
            ->where('status', TrainingReminder::STATUS_ENVIADO)
            ->whereDate('enviado_em', $hoje)
            ->count();
        $restante = max(0, (int) config('whatsapp.daily_cap', 40) - $enviadosHoje);

        if ($restante <= 0 && ! $this->option('dry-run')) {
            $this->line("Teto diário atingido ({$enviadosHoje} enviados).");

            return 0;
        }

        $limite = max(1, (int) $this->option('limit'));
        if (! $this->option('dry-run')) {
            $limite = min($limite, $restante);
        }

        if (! $this->option('dry-run')) {
            $sessao = $client->sessionStatus();

            if (! $sessao['connected']) {
                $this->error("Sessão WhatsApp não conectada ({$sessao['status']}). ".($sessao['error'] ?? ''));

                return 0;
            }
        }

        $processados = 0;

        for ($i = 0; $i < $limite; $i++) {
            $reminder = TrainingReminder::vencidos()
                ->with(['user', 'training'])
                ->orderBy('agendado_para')
                ->first();

            if (! $reminder) {
                break;
            }

            if ($this->option('dry-run')) {
                $this->line(sprintf(
                    '[dry-run] %s → %s (%s)',
                    $reminder->user?->nome ?? "#{$reminder->user_id}",
                    $reminder->telefone,
                    $reminder->training?->titulo ?? "#{$reminder->training_id}"
                ));
                $processados++;

                continue;
            }

            $this->enviar($client, $reminder);
            $processados++;
            $this->reagendarProximo($reminder);
        }

        return $processados;
    }

    private function enviar(WhatsappGatewayClient $client, TrainingReminder $reminder): void
    {
        $resultado = $client->sendText((string) $reminder->jid, (string) $reminder->mensagem);

        if ($resultado['ok']) {
            $reminder->update([
                'status' => TrainingReminder::STATUS_ENVIADO,
                'enviado_em' => Carbon::now(config('app.timezone')),
                'gateway_message_id' => $resultado['message_id'],
                'erro' => null,
            ]);

            $this->info("Enviado para {$reminder->user?->nome} ({$reminder->telefone}).");

            return;
        }

        $reminder->update([
            'status' => TrainingReminder::STATUS_FALHOU,
            'enviado_em' => Carbon::now(config('app.timezone')),
            'erro' => $resultado['error'] ?? 'Falha desconhecida no envio.',
        ]);

        $this->error("Falha ao enviar para {$reminder->user?->nome}: ".($resultado['error'] ?? 'erro desconhecido'));
    }

    /**
     * Garante espaçamento mínimo entre o envio atual e o próximo da fila.
     */
    private function reagendarProximo(TrainingReminder $atual): void
    {
        $proximo = TrainingReminder::naFila()
            ->whereKeyNot($atual->getKey())
            ->orderBy('agendado_para')
            ->first();

        if (! $proximo) {
            return;
        }

        $delayMin = min((int) config('whatsapp.delay_min', 45), (int) config('whatsapp.delay_max', 180));
        $delayMax = max((int) config('whatsapp.delay_min', 45), (int) config('whatsapp.delay_max', 180));
        $minimo = Carbon::now(config('app.timezone'))->addSeconds(random_int($delayMin, $delayMax));

        if ($proximo->agendado_para === null || $proximo->agendado_para->lt($minimo)) {
            $proximo->update(['agendado_para' => $minimo]);
        }
    }

    private function falhasConsecutivas(): int
    {
        $max = max(1, (int) config('whatsapp.max_consecutive_failures', 5));

        $ultimos = TrainingReminder::query()
            ->whereIn('status', [TrainingReminder::STATUS_ENVIADO, TrainingReminder::STATUS_FALHOU])
            ->orderByDesc('updated_at')
            ->limit($max)
            ->pluck('status');

        $falhas = 0;

        foreach ($ultimos as $status) {
            if ($status !== TrainingReminder::STATUS_FALHOU) {
                break;
            }

            $falhas++;
        }

        return $falhas;
    }
}
