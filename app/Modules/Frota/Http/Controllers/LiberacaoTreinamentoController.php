<?php

namespace App\Modules\Frota\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Frota\Models\FrotaLiberacao;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LiberacaoTreinamentoController extends Controller
{
    public function index(Request $request)
    {
        $query = FrotaLiberacao::query()->with(['user', 'liberadoPor', 'revogadoPor']);

        if ($request->filled('busca')) {
            $busca = trim((string) $request->input('busca'));
            $digitos = preg_replace('/\D/', '', $busca) ?? '';

            $query->whereHas('user', function ($q) use ($busca, $digitos) {
                $q->where('nome', 'like', '%'.$busca.'%');

                if ($digitos !== '') {
                    $q->orWhere('cpf', 'like', '%'.$digitos.'%');
                }
            });
        }

        switch ($request->input('situacao')) {
            case 'vigente':
                $query->vigente();
                break;
            case 'expirada':
                $query->whereNull('revogado_em')->where('fim', '<', now());
                break;
            case 'revogada':
                $query->whereNotNull('revogado_em');
                break;
            case 'agendada':
                $query->whereNull('revogado_em')->where('inicio', '>', now());
                break;
        }

        $liberacoes = $query->orderByDesc('created_at')->paginate(30)->withQueryString();

        $resumo = [
            'vigentes' => FrotaLiberacao::vigente()->count(),
            'total' => FrotaLiberacao::count(),
            'revogadas' => FrotaLiberacao::whereNotNull('revogado_em')->count(),
        ];

        return view('frota::admin.liberacoes', compact('liberacoes', 'resumo'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
            'motivo' => ['nullable', 'string', 'max:1000'],
            'inicio' => ['nullable', 'date'],
            'fim' => ['required', 'date', 'after:now'],
        ], [
            'user_ids.required' => 'Selecione ao menos um motorista.',
            'fim.required' => 'Informe a validade da liberação.',
            'fim.after' => 'A validade deve ser posterior a agora.',
        ]);

        $usuarios = User::query()
            ->whereIn('id', array_map('intval', $data['user_ids']))
            ->get();

        if ($usuarios->isEmpty()) {
            return back()->with('error', 'Nenhum usuário válido encontrado para liberar.');
        }

        $inicio = ! empty($data['inicio']) ? Carbon::parse($data['inicio']) : now();
        $fim = Carbon::parse($data['fim']);
        $motivo = trim((string) ($data['motivo'] ?? '')) ?: null;
        $criadas = 0;

        foreach ($usuarios as $usuario) {
            $liberacao = FrotaLiberacao::create([
                'user_id' => $usuario->id,
                'liberado_por' => $request->user()?->id,
                'motivo' => $motivo,
                'inicio' => $inicio,
                'fim' => $fim,
            ]);

            $criadas++;

            app(AuditLogger::class)->log('treinamento_acesso_liberado', [
                'module' => 'frota',
                'auditable_type' => FrotaLiberacao::class,
                'auditable_id' => $liberacao->id,
                'description' => 'Liberou acesso aos treinamentos de '.$usuario->nome
                    .' até '.$fim->format('d/m/Y H:i')
                    .($motivo ? ' — Motivo: '.$motivo : ''),
                'new_values' => [
                    'user_id' => $usuario->id,
                    'inicio' => $inicio->toIso8601String(),
                    'fim' => $fim->toIso8601String(),
                    'motivo' => $motivo,
                ],
            ]);
        }

        return back()->with('success', $criadas === 1
            ? 'Acesso liberado com sucesso.'
            : "Acesso liberado para {$criadas} motoristas.");
    }

    public function revogar(Request $request, FrotaLiberacao $liberacao)
    {
        if ($liberacao->revogado_em !== null) {
            return back()->with('error', 'Esta liberação já foi revogada.');
        }

        $liberacao->forceFill([
            'revogado_em' => now(),
            'revogado_por' => $request->user()?->id,
        ])->save();

        app(AuditLogger::class)->log('treinamento_acesso_revogado', [
            'module' => 'frota',
            'auditable_type' => FrotaLiberacao::class,
            'auditable_id' => $liberacao->id,
            'description' => 'Revogou a liberação de acesso aos treinamentos de '
                .optional($liberacao->user)->nome,
            'old_values' => [
                'inicio' => optional($liberacao->inicio)->toIso8601String(),
                'fim' => optional($liberacao->fim)->toIso8601String(),
                'motivo' => $liberacao->motivo,
            ],
        ]);

        return back()->with('success', 'Liberação revogada.');
    }

    public function exportar(Request $request)
    {
        $query = FrotaLiberacao::query()->with(['user', 'liberadoPor', 'revogadoPor']);

        if ($request->filled('situacao')) {
            switch ($request->input('situacao')) {
                case 'vigente':
                    $query->vigente();
                    break;
                case 'expirada':
                    $query->whereNull('revogado_em')->where('fim', '<', now());
                    break;
                case 'revogada':
                    $query->whereNotNull('revogado_em');
                    break;
            }
        }

        $registros = $query->orderByDesc('created_at')->get();

        app(AuditLogger::class)->log('treinamento_acesso_exportado', [
            'module' => 'frota',
            'description' => 'Exportou relatório de liberações de treinamento ('.$registros->count().' registro(s)).',
        ]);

        $nomeArquivo = 'liberacoes-treinamento-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($registros) {
            $saida = fopen('php://output', 'w');

            fwrite($saida, "\xEF\xBB\xBF");
            fputcsv($saida, [
                'ID',
                'Motorista',
                'CPF',
                'Tipo',
                'Liberado por',
                'Liberado em',
                'Início',
                'Fim',
                'Situação',
                'Motivo',
                'Revogado por',
                'Revogado em',
            ], ';');

            foreach ($registros as $liberacao) {
                fputcsv($saida, [
                    $liberacao->id,
                    optional($liberacao->user)->nome,
                    optional($liberacao->user)->cpf,
                    optional($liberacao->user)->tipo_usuario,
                    optional($liberacao->liberadoPor)->nome,
                    optional($liberacao->created_at)->format('d/m/Y H:i'),
                    optional($liberacao->inicio)->format('d/m/Y H:i'),
                    optional($liberacao->fim)->format('d/m/Y H:i'),
                    $liberacao->situacao,
                    $liberacao->motivo,
                    optional($liberacao->revogadoPor)->nome,
                    optional($liberacao->revogado_em)->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($saida);
        }, $nomeArquivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
