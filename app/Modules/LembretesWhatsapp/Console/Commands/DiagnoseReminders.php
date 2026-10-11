<?php

namespace App\Modules\LembretesWhatsapp\Console\Commands;

use App\Modules\LembretesWhatsapp\Models\TrainingReminder;
use App\Modules\LembretesWhatsapp\Services\WhatsappGatewayClient;
use App\Support\TenantManager;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DiagnoseReminders extends Command
{
    protected $signature = 'lembretes:diagnostico';

    protected $description = 'Diagnóstico da integração de Lembretes WhatsApp (configuração, gateway e fila)';

    public function handle(WhatsappGatewayClient $client): int
    {
        $this->info('Lembretes WhatsApp — diagnóstico');
        $this->newLine();

        $enabled = (bool) config('whatsapp.enabled');
        $this->line('Integração ativa (WA_REMINDERS_ENABLED): '.($enabled ? '<fg=green>sim</>' : '<fg=red>não</>'));
        $this->line('Gateway URL: '.config('whatsapp.gateway_url'));
        $this->line('Sessão: '.config('whatsapp.session_id'));
        $this->line('API key: '.(filled(config('whatsapp.api_key')) ? $this->mascarar((string) config('whatsapp.api_key')) : '<fg=red>não configurada</>'));
        $this->line('Janela: '.config('whatsapp.window_start').'–'.config('whatsapp.window_end').(config('whatsapp.allow_weekends') ? ' (fins de semana)' : ' (dias úteis)'));
        $this->line('Intervalo: '.config('whatsapp.delay_min').'–'.config('whatsapp.delay_max').'s · Teto diário: '.config('whatsapp.daily_cap').' · Lote: '.config('whatsapp.batch_limit'));
        $this->line('Config cache: '.(app()->configurationIsCached() ? '<fg=yellow>ativo — alterações no .env exigem config:clear</>' : 'inativo'));
        $this->line('Webhook de entrega: '.(filled(config('whatsapp.webhook_secret')) ? '<fg=green>configurado</>' : '<fg=yellow>sem segredo (WA_WEBHOOK_SECRET)</>'));

        $url = (string) config('whatsapp.gateway_url');
        if (preg_match('#^https?://(localhost|127\.0\.0\.1)#', $url)) {
            $this->line('<fg=yellow>Dica: se o DSS roda em Docker, "localhost" aponta para o próprio container. Use http://host.docker.internal:PORTA (Docker Desktop) ou o nome do serviço na mesma rede.</>');
        }

        $this->newLine();

        if (! $client->configurado()) {
            $this->warn('Gateway não configurado: preencha WA_GATEWAY_API_KEY/WA_GATEWAY_SESSION e ative WA_REMINDERS_ENABLED.');
        } else {
            $sessao = $client->sessionStatus();
            $this->line('Status da sessão: '.$sessao['status'].($sessao['connected'] ? ' <fg=green>(conectada)</>' : ' <fg=red>(não conectada)</>'));

            if ($sessao['error']) {
                $this->line('Detalhe: '.$sessao['error']);
            }
        }

        $this->newLine();
        $this->line('Fila:');

        app(TenantManager::class)->runForEachTenant(function () {
            $hoje = Carbon::now(config('app.timezone'))->toDateString();
            $tenant = app(TenantManager::class)->current();
            $prefixo = $tenant ? "[{$tenant->nome}] " : '';

            $fila = TrainingReminder::where('status', TrainingReminder::STATUS_FILA)->count();
            $vencidos = TrainingReminder::vencidos()->count();
            $enviadosHoje = TrainingReminder::query()
                ->where('status', TrainingReminder::STATUS_ENVIADO)
                ->whereDate('enviado_em', $hoje)
                ->count();
            $falhas = TrainingReminder::where('status', TrainingReminder::STATUS_FALHOU)->count();
            $pulados = TrainingReminder::where('status', TrainingReminder::STATUS_PULADO)->count();

            $this->line("{$prefixo}Fila: {$fila} (vencidos: {$vencidos}) · Enviados hoje: {$enviadosHoje} · Falhas: {$falhas} · Sem telefone: {$pulados}");
        });

        return self::SUCCESS;
    }

    private function mascarar(string $valor): string
    {
        if (strlen($valor) <= 6) {
            return '***';
        }

        return substr($valor, 0, 4).'***'.substr($valor, -2);
    }
}
