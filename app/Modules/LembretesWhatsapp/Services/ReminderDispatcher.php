<?php

namespace App\Modules\LembretesWhatsapp\Services;

use App\Models\Training;
use App\Models\User;
use App\Modules\LembretesWhatsapp\Models\TrainingReminder;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Enfileira os lembretes com espaçamento aleatório e calcula a janela de envio.
 */
class ReminderDispatcher
{
    public function __construct(
        private AuditLogger $audit,
        private WhatsappGatewayClient $gateway
    ) {}

    /**
     * @param  Collection<int, User>  $users
     * @param  bool  $imediato  agenda tudo para agora (sem escalonamento) — usado no "Enviar agora"
     * @return array{enfileirados: int, pulados: int, primeiro_envio: ?Carbon, ultimo_envio: ?Carbon, reminder_ids: array<int, int>}
     */
    public function enfileirar(Training $training, Collection $users, string $mensagemBase, User $gestor, bool $imediato = false): array
    {
        $delayMin = min((int) config('whatsapp.delay_min', 45), (int) config('whatsapp.delay_max', 180));
        $delayMax = max((int) config('whatsapp.delay_min', 45), (int) config('whatsapp.delay_max', 180));

        $agendado = $imediato ? Carbon::now() : self::proximoInicioJanela(Carbon::now());
        $enfileirados = 0;
        $pulados = 0;
        $primeiro = null;
        $ultimo = null;
        $reminderIds = [];

        // Resolve o JID canônico no WhatsApp (corrige o nono dígito). Null =
        // não foi possível verificar → usa o fallback phone_to_jid().
        $candidatos = $users
            ->map(fn (User $user) => $this->numeroInternacional($user->telefone))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $verificacao = $this->gateway->configurado() ? $this->gateway->checkNumbers($candidatos) : null;

        DB::transaction(function () use (
            $users,
            $training,
            $mensagemBase,
            $gestor,
            $delayMin,
            $delayMax,
            $imediato,
            $verificacao,
            &$agendado,
            &$enfileirados,
            &$pulados,
            &$primeiro,
            &$ultimo,
            &$reminderIds
        ) {
            foreach ($users as $user) {
                $mensagem = $this->personalizar($mensagemBase, $user, $training);
                $digits = $this->numeroInternacional($user->telefone);

                if ($verificacao !== null && $digits !== null) {
                    $resolvido = $verificacao[$digits] ?? null;

                    if ($resolvido === null || ($resolvido['exists'] ?? false) === false) {
                        TrainingReminder::create([
                            'user_id' => $user->id,
                            'training_id' => $training->id,
                            'telefone' => $user->telefone,
                            'mensagem' => $mensagem,
                            'status' => TrainingReminder::STATUS_PULADO,
                            'erro' => 'Número não encontrado no WhatsApp.',
                            'agendado_para' => null,
                            'created_by' => $gestor->id,
                        ]);

                        $pulados++;

                        continue;
                    }

                    $jid = $resolvido['jid'] ?: phone_to_jid($user->telefone);
                } else {
                    $jid = phone_to_jid($user->telefone);
                }

                if ($jid === null) {
                    TrainingReminder::create([
                        'user_id' => $user->id,
                        'training_id' => $training->id,
                        'telefone' => $user->telefone,
                        'mensagem' => $mensagem,
                        'status' => TrainingReminder::STATUS_PULADO,
                        'erro' => 'Telefone ausente ou inválido para WhatsApp.',
                        'agendado_para' => null,
                        'created_by' => $gestor->id,
                    ]);

                    $pulados++;

                    continue;
                }

                $reminder = TrainingReminder::create([
                    'user_id' => $user->id,
                    'training_id' => $training->id,
                    'telefone' => $user->telefone,
                    'jid' => $jid,
                    'mensagem' => $mensagem,
                    'status' => TrainingReminder::STATUS_FILA,
                    'agendado_para' => $agendado,
                    'created_by' => $gestor->id,
                ]);

                $reminderIds[] = $reminder->id;
                $primeiro ??= $agendado->copy();
                $ultimo = $agendado->copy();
                $enfileirados++;

                if (! $imediato) {
                    $agendado = $agendado->copy()->addSeconds(random_int($delayMin, $delayMax));
                }
            }
        });

        $this->audit->log('whatsapp_reminder_queued', [
            'module' => 'lembretes_whatsapp',
            'auditable_type' => Training::class,
            'auditable_id' => $training->id,
            'description' => "Lembretes WhatsApp enfileirados para o treinamento \"{$training->titulo}\": {$enfileirados} na fila, {$pulados} não enfileirado(s).".($imediato ? ' (envio imediato)' : ''),
            'new_values' => [
                'enfileirados' => $enfileirados,
                'pulados' => $pulados,
                'primeiro_envio' => $primeiro?->toDateTimeString(),
                'user_ids' => $users->pluck('id')->all(),
            ],
        ]);

        return [
            'enfileirados' => $enfileirados,
            'pulados' => $pulados,
            'primeiro_envio' => $primeiro,
            'ultimo_envio' => $ultimo,
            'reminder_ids' => $reminderIds,
        ];
    }

    /**
     * Número internacional (com DDI) usado na validação do WhatsApp.
     * Retorna null quando o telefone não é normalizável.
     */
    private function numeroInternacional(?string $telefone): ?string
    {
        $jid = phone_to_jid($telefone);

        return $jid ? str_replace('@s.whatsapp.net', '', $jid) : null;
    }

    public function personalizar(string $mensagem, User $user, Training $training): string
    {
        return str_replace(
            ['{nome}', '{treinamento}'],
            [$user->nome, $training->titulo],
            $mensagem
        );
    }

    /**
     * O envio só ocorre dentro da janela configurada (dias úteis e horário).
     */
    public static function dentroDaJanela(?Carbon $at = null): bool
    {
        $at = ($at ?? Carbon::now())->copy();

        if (! config('whatsapp.allow_weekends', false) && $at->isWeekend()) {
            return false;
        }

        $abertura = $at->copy()->setTimeFromTimeString((string) config('whatsapp.window_start', '08:00'));
        $fechamento = $at->copy()->setTimeFromTimeString((string) config('whatsapp.window_end', '18:00'));

        return $at->betweenIncluded($abertura, $fechamento);
    }

    /**
     * Próximo instante válido para envio: agora (se dentro da janela) ou a
     * abertura seguinte (próximo dia útil / horário).
     */
    public static function proximoInicioJanela(?Carbon $from = null): Carbon
    {
        $candidate = ($from ?? Carbon::now())->copy();
        $abertura = (string) config('whatsapp.window_start', '08:00');
        $fechamento = (string) config('whatsapp.window_end', '18:00');
        $permitirFimDeSemana = (bool) config('whatsapp.allow_weekends', false);

        for ($i = 0; $i < 14; $i++) {
            if (! $permitirFimDeSemana && $candidate->isWeekend()) {
                $candidate = $candidate->copy()->addDay()->startOfDay();

                continue;
            }

            $open = $candidate->copy()->setTimeFromTimeString($abertura);
            $close = $candidate->copy()->setTimeFromTimeString($fechamento);

            if ($candidate->lt($open)) {
                return $open;
            }

            if ($candidate->lte($close)) {
                return $candidate->copy();
            }

            $candidate = $candidate->copy()->addDay()->startOfDay();
        }

        return $from ?? Carbon::now();
    }
}
