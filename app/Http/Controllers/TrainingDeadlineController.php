<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingLog;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TrainingDeadlineController extends Controller
{
    /**
     * Lista os usuários com prazo de treinamento expirado (ou vencendo em até
     * 7 dias) para o gestor conceder um novo prazo de conclusão.
     */
    public function index(Request $request)
    {
        $situacao = $request->input('situacao', 'expirado');
        if (! in_array($situacao, ['expirado', 'vencendo'], true)) {
            $situacao = 'expirado';
        }

        $agora = Carbon::now(config('app.timezone'));
        $limiteVencendo = $agora->copy()->addDays(7);

        $trainingId = $request->filled('training_id') ? $request->integer('training_id') : null;
        $usuarioNome = trim((string) $request->input('usuario_nome'));

        // Treinamentos com prorrogação registrada (para não descartar
        // candidatos cujo prazo padrão ainda não está na janela).
        $treinamentosComExtensao = UserProgress::whereNotNull('prazo_estendido_ate')
            ->where('concluido', false)
            ->where('prazo_estendido_ate', '<=', $limiteVencendo)
            ->pluck('training_id')
            ->unique();

        $treinamentosLiberados = Training::where('status', 'ativo')
            ->orderByReleaseDate('desc')
            ->get()
            ->filter(fn (Training $t) => $t->isReleased());

        $treinamentos = $trainingId
            ? $treinamentosLiberados->where('id', $trainingId)
            : $treinamentosLiberados;

        $rows = collect();

        foreach ($treinamentos as $training) {
            $prazoBase = $training->prazoBaseConclusao();
            if (! $prazoBase) {
                continue;
            }

            $podeSerCandidato = $prazoBase->lte($limiteVencendo)
                || $treinamentosComExtensao->contains($training->id);

            if (! $podeSerCandidato) {
                continue;
            }

            // Alunos que já iniciaram e ainda não concluíram
            $progressos = UserProgress::with('user')
                ->where('training_id', $training->id)
                ->where('concluido', false)
                ->whereHas('user', function ($query) use ($usuarioNome) {
                    $query->kpiEligible();
                    if ($usuarioNome !== '') {
                        $query->where('nome', 'like', '%'.$usuarioNome.'%');
                    }
                })
                ->get();

            foreach ($progressos as $progress) {
                $prazo = $progress->prazo_estendido_ate ?? $prazoBase;

                if (! $this->dentroDaSituacao($prazo, $situacao, $agora, $limiteVencendo)) {
                    continue;
                }

                $rows->push((object) [
                    'user' => $progress->user,
                    'training' => $training,
                    'progress' => $progress,
                    'porcentagem' => (int) $progress->porcentagem_assistida,
                    'tem_progresso' => true,
                    'prazo' => $prazo,
                    'prazo_estendido' => $progress->prazo_estendido_ate !== null,
                    'prazo_estendido_por' => $progress->prazo_estendido_por,
                    'prazo_estendido_em' => $progress->prazo_estendido_em,
                    'prazo_estendido_motivo' => $progress->prazo_estendido_motivo,
                ]);
            }

            // Alunos elegíveis que nunca iniciaram (prazo padrão, sem prorrogação)
            if ($this->dentroDaSituacao($prazoBase, $situacao, $agora, $limiteVencendo)) {
                $naoIniciados = User::query()
                    ->kpiEligible()
                    ->eligibleForContent($training)
                    ->when($usuarioNome !== '', fn ($query) => $query->where('nome', 'like', '%'.$usuarioNome.'%'))
                    ->whereDoesntHave('progress', function ($query) use ($training) {
                        $query->where('training_id', $training->id);
                    })
                    ->orderBy('nome')
                    ->get();

                foreach ($naoIniciados as $user) {
                    $rows->push((object) [
                        'user' => $user,
                        'training' => $training,
                        'progress' => null,
                        'porcentagem' => 0,
                        'tem_progresso' => false,
                        'prazo' => $prazoBase,
                        'prazo_estendido' => false,
                        'prazo_estendido_por' => null,
                        'prazo_estendido_em' => null,
                        'prazo_estendido_motivo' => null,
                    ]);
                }
            }
        }

        $ordenadas = $rows->sort(function ($a, $b) {
            $comparacao = $a->prazo->getTimestamp() <=> $b->prazo->getTimestamp();

            return $comparacao !== 0 ? $comparacao : strcasecmp($a->user->nome, $b->user->nome);
        })->values();

        $perPage = 25;
        $pagina = LengthAwarePaginator::resolveCurrentPage();

        $paginados = new LengthAwarePaginator(
            $ordenadas->forPage($pagina, $perPage)->values(),
            $ordenadas->count(),
            $perPage,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.prazos.index', [
            'paginados' => $paginados,
            'treinamentos' => $treinamentosLiberados,
            'situacao' => $situacao,
            'trainingId' => $trainingId,
            'usuarioNome' => $usuarioNome,
            'prazoSugerido' => $this->proximoDomingo23h($agora)->format('Y-m-d\TH:i'),
        ]);
    }

    /**
     * Concede um novo prazo (prorrogação) para o usuário concluir o treinamento.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'training_id' => 'required|integer|exists:trainings,id',
            'prazo' => 'required|string',
            'justificativa' => 'required|string|max:1000',
        ]);

        $user = User::findOrFail($data['user_id']);
        $training = Training::findOrFail($data['training_id']);

        if (! $user->canAccessTraining($training)) {
            return back()->with('error', 'Este usuário não tem acesso a este treinamento.');
        }

        try {
            $prazo = Carbon::createFromFormat('Y-m-d\TH:i', $data['prazo'], config('app.timezone'));
        } catch (\Throwable $e) {
            $prazo = null;
        }

        if (! $prazo || $prazo->lte(Carbon::now(config('app.timezone')))) {
            return back()->with('error', 'Informe uma data/hora futura válida para o novo prazo.')->withInput();
        }

        $progress = UserProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'training_id' => $training->id,
            ],
            [
                'data_inicio' => now(config('app.timezone')),
                'tempo_assistido' => 0,
                'porcentagem_assistida' => 0,
                'concluido' => false,
                'avaliacao_tentativas' => 0,
            ]
        );

        $prazoAnterior = $progress->prazo_estendido_ate;

        $progress->update([
            'prazo_estendido_ate' => $prazo,
            'prazo_estendido_por' => $request->user()->id,
            'prazo_estendido_em' => now(config('app.timezone')),
            'prazo_estendido_motivo' => $data['justificativa'],
        ]);

        TrainingLog::registrar(
            $training->id,
            $user->id,
            'prazo_estendido',
            'Novo prazo de conclusão: '.$prazo->format('d/m/Y H:i').' (concedido por '.$request->user()->nome.').'
        );

        app(AuditLogger::class)->log('prazo_estendido', [
            'module' => 'prazos',
            'auditable_type' => UserProgress::class,
            'auditable_id' => $progress->id,
            'description' => "Novo prazo liberado para {$user->nome} concluir \"{$training->titulo}\" até {$prazo->format('d/m/Y H:i')}. Justificativa: {$data['justificativa']}",
            'old_values' => ['prazo_estendido_ate' => $prazoAnterior?->format('d/m/Y H:i')],
            'new_values' => ['prazo_estendido_ate' => $prazo->format('d/m/Y H:i')],
        ]);

        return back()->with('success', 'Novo prazo liberado com sucesso! O treinamento volta a ficar disponível para o usuário.');
    }

    /**
     * Remove a prorrogação; o treinamento volta ao prazo padrão.
     */
    public function destroy(UserProgress $progress)
    {
        $training = $progress->training;
        $user = $progress->user;
        $prazoAnterior = $progress->prazo_estendido_ate;

        $progress->update([
            'prazo_estendido_ate' => null,
            'prazo_estendido_por' => null,
            'prazo_estendido_em' => null,
            'prazo_estendido_motivo' => null,
        ]);

        if ($training && $prazoAnterior) {
            TrainingLog::registrar(
                $training->id,
                $progress->user_id,
                'prazo_estendido_removido',
                'Prorrogação de prazo removida por '.auth()->user()->nome.'.'
            );
        }

        app(AuditLogger::class)->log('prazo_estendido_removido', [
            'module' => 'prazos',
            'auditable_type' => UserProgress::class,
            'auditable_id' => $progress->id,
            'description' => 'Prorrogação de prazo removida para '.($user?->nome ?? 'usuário').' em "'.($training?->titulo ?? 'treinamento').'".',
            'old_values' => ['prazo_estendido_ate' => $prazoAnterior?->format('d/m/Y H:i')],
        ]);

        return back()->with('success', 'Prorrogação removida. O treinamento volta ao prazo padrão.');
    }

    private function dentroDaSituacao(Carbon $prazo, string $situacao, Carbon $agora, Carbon $limiteVencendo): bool
    {
        if ($situacao === 'vencendo') {
            return $prazo->gte($agora) && $prazo->lte($limiteVencendo);
        }

        return $prazo->lt($agora);
    }

    private function proximoDomingo23h(Carbon $referencia): Carbon
    {
        $data = $referencia->copy()->startOfDay();

        $diasAteDomingo = 7 - (int) $data->dayOfWeekIso;
        if ($diasAteDomingo === 0) {
            $diasAteDomingo = 7;
        }

        return $data->addDays($diasAteDomingo)->setTime(23, 0, 0);
    }
}
