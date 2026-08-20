<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SplashContent;
use App\Models\EmployeeTraining;
use App\Models\Certificate;
use App\Models\EpiEntrega;
use App\Models\EpiColaborador;
use Illuminate\Http\Request;

class MiscController extends Controller
{
    public function splashActive(Request $request)
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $today = now()->format('Y-m-d');

        $contents = SplashContent::where('status', 'ativo')
            ->whereDate('data_inicio', '<=', $today)
            ->whereDate('data_fim', '>=', $today)
            ->orderBy('ordem')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'titulo' => $c->titulo,
                'texto' => $c->texto_conteudo,
                'material_tipo' => $c->material_tipo,
                'material_url' => $c->material_path ? url($c->material_path) : null,
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $contents,
        ]);
    }

    /**
     * Registro remoto de eventos de vídeo/player do app — usado para diagnosticar
     * falhas de reprodução sem acesso físico ao aparelho.
     */
    public function videoLog(Request $request)
    {
        $user = $request->user();

        \Illuminate\Support\Facades\Log::info('APP-DEBUG ' . json_encode([
            'user_id' => $user?->id,
            'training_id' => $request->input('training_id'),
            'event' => $request->input('event'),
            'mode' => $request->input('mode'),
            'message' => $request->input('message'),
            'ip' => $request->ip(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return response()->json(['status' => 'success']);
    }

    public function fichaPublica($token)
    {
        $user = \App\Models\User::where('qrcode_token', $token)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ficha não encontrada.',
            ], 404);
        }

        $certificates = Certificate::with('training')
            ->where('user_id', $user->id)
            ->where('valido', true)
            ->orderByDesc('data_emissao')
            ->get();

        $externalTrainings = EmployeeTraining::where('user_id', $user->id)->get();

        $colaborador = EpiColaborador::where('ss_c_tx_cpf', $user->cpf)->first();
        $epis = collect();
        if ($colaborador) {
            $epis = EpiEntrega::with(['epi', 'variacao'])
                ->where('ss_e_nb_colaborador_id', $colaborador->ss_c_nb_id)
                ->where('ss_e_tx_status', 'ativo')
                ->orderByDesc('ss_e_tx_data_entrega')
                ->get()
                ->map(fn ($e) => [
                    'item' => $e->epi?->ss_e_tx_item ?: ($e->epi?->ss_e_tx_grupo ?: 'EPI'),
                    'ca' => $e->epi?->ss_e_tx_ca,
                    'variacao' => $e->variacao?->ss_ev_tx_nome,
                    'quantidade' => (int) $e->ss_e_nb_quantidade,
                    'data_entrega' => $e->ss_e_tx_data_entrega,
                    'vencimento' => $e->ss_e_tx_vencimento,
                    'status_assinatura' => $e->ss_e_tx_status_assinatura,
                ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'colaborador' => [
                    'nome' => $user->nome,
                    'cpf' => $user->getCpfFormatted(),
                    'cargo' => $user->cargo,
                    'empresa' => $user->empresa,
                    'avatar_url' => $user->getFotoPerfilUrl(),
                ],
                'certificados' => $certificates->map(fn ($c) => [
                    'codigo' => $c->codigo_certificado,
                    'titulo' => $c->training?->titulo,
                    'tipo' => $c->training?->tipo,
                    'carga_horaria' => (int) ($c->training?->carga_horaria ?? 0),
                    'data_emissao' => $c->data_emissao?->toISOString(),
                    'data_validade' => $c->data_validade?->toISOString(),
                    'status_validade' => $c->status_validade,
                ])->values(),
                'treinamentos_externos' => $externalTrainings->map(fn ($t) => [
                    'nome' => $t->nome,
                    'data_treinamento' => $t->data_treinamento,
                    'data_validade' => $t->data_validade,
                    'observacoes' => $t->observacoes,
                ])->values(),
                'epis' => $epis->values(),
            ],
        ]);
    }
}
