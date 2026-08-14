<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EpiColaborador;
use App\Models\EpiEntrega;
use App\Models\User;
use App\Models\EpiDevolucao;
use App\Models\Epi;
use Illuminate\Http\Request;

class EpiController extends Controller
{
    public function __construct()
    {
        // Garante que as tabelas ss_* existam e sincroniza colaboradores
        app(\App\Http\Controllers\EpiController::class)->ensureTablesExist();
    }

    private function colaboradorDoUsuario(User $user): ?EpiColaborador
    {
        return EpiColaborador::where('ss_c_tx_cpf', $user->cpf)->first();
    }

    /**
     * Grupos de entregas pendentes de assinatura do colaborador autenticado (por CPF).
     */
    public function pendingSignatures(Request $request)
    {
        $user = $request->user();
        $colaborador = $this->colaboradorDoUsuario($user);

        if (!$colaborador) {
            return response()->json(['status' => 'success', 'data' => [], 'count' => 0]);
        }

        $pendentes = EpiEntrega::with(['epi', 'variacao', 'colaborador'])
            ->pendentesAssinatura($colaborador->ss_c_nb_id)
            ->orderBy('ss_e_tx_data_entrega', 'desc')
            ->get();

        $grupos = $pendentes->groupBy(function ($item) {
            return $item->ss_e_tx_grupo_assinatura ?: 'grupo_' . $item->ss_e_nb_id;
        });

        $data = $grupos->map(function ($items, $grupoId) {
            $first = $items->first();

            return [
                'grupo' => (string) $grupoId,
                'data_entrega' => $first->ss_e_tx_data_entrega,
                'data_cadastro' => $first->ss_e_tx_dataCadastro,
                'itens' => $items->map(fn ($item) => [
                    'id' => $item->ss_e_nb_id,
                    'epi_id' => $item->ss_e_nb_epi_id,
                    'item' => $item->epi?->ss_e_tx_item ?: ($item->epi?->ss_e_tx_grupo ?: 'EPI'),
                    'grupo_epi' => $item->epi?->ss_e_tx_grupo,
                    'ca' => $item->epi?->ss_e_tx_ca,
                    'validade_ca' => $item->epi?->ss_e_tx_validade_ca,
                    'variacao' => $item->variacao?->ss_ev_tx_nome,
                    'quantidade' => (int) $item->ss_e_nb_quantidade,
                    'vencimento' => $item->ss_e_tx_vencimento,
                ])->values(),
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'count' => $grupos->count(),
        ]);
    }

    public function sign(Request $request, $id)
    {
        $user = $request->user();
        $colaborador = $this->colaboradorDoUsuario($user);

        if (!$colaborador) {
            return response()->json(['status' => 'error', 'message' => 'Colaborador não encontrado!'], 404);
        }

        $entrega = EpiEntrega::pendentesAssinatura($colaborador->ss_c_nb_id)->findOrFail($id);

        $validator = validator($request->all(), [
            'ss_e_tx_assinatura' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Assinatura é obrigatória.'], 422);
        }

        $grupo = $entrega->ss_e_tx_grupo_assinatura;
        $assinatura = $request->input('ss_e_tx_assinatura');
        $agora = now();

        $query = EpiEntrega::pendentesAssinatura($colaborador->ss_c_nb_id);
        if ($grupo) {
            $query->porGrupoAssinatura($grupo);
        } else {
            $query->where('ss_e_nb_id', $id);
        }

        $query->update([
            'ss_e_tx_assinatura' => $assinatura,
            'ss_e_tx_status_assinatura' => 'assinada',
            'ss_e_tx_data_assinatura' => $agora,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Assinatura registrada com sucesso!',
        ]);
    }

    public function deny(Request $request, $id)
    {
        $user = $request->user();
        $colaborador = $this->colaboradorDoUsuario($user);

        if (!$colaborador) {
            return response()->json(['status' => 'error', 'message' => 'Colaborador não encontrado!'], 404);
        }

        $entrega = EpiEntrega::pendentesAssinatura($colaborador->ss_c_nb_id)->findOrFail($id);

        $grupo = $entrega->ss_e_tx_grupo_assinatura;
        $justificativa = $request->input('ss_e_tx_justificativa_negacao');

        $query = EpiEntrega::pendentesAssinatura($colaborador->ss_c_nb_id);
        if ($grupo) {
            $query->porGrupoAssinatura($grupo);
        } else {
            $query->where('ss_e_nb_id', $id);
        }

        $query->update([
            'ss_e_tx_status_assinatura' => 'negada',
            'ss_e_tx_justificativa_negacao' => $justificativa,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Assinatura recusada. A gestão será notificada.',
        ]);
    }

    /**
     * Ficha individual do colaborador logado (termo NR-06, EPIs e assinaturas).
     */
    public function fichaMe(Request $request)
    {
        $user = $request->user();
        $colaborador = $this->colaboradorDoUsuario($user);

        if (!$colaborador) {
            return response()->json(['status' => 'error', 'message' => 'Colaborador não encontrado!'], 404);
        }

        $entregas = EpiEntrega::with(['epi', 'variacao'])
            ->where('ss_e_nb_colaborador_id', $colaborador->ss_c_nb_id)
            ->where('ss_e_tx_status', 'ativo')
            ->orderByDesc('ss_e_tx_data_entrega')
            ->get();

        $data = $entregas->map(fn ($e) => [
            'id' => $e->ss_e_nb_id,
            'item' => $e->epi?->ss_e_tx_item ?: ($e->epi?->ss_e_tx_grupo ?: 'EPI'),
            'grupo' => $e->epi?->ss_e_tx_grupo,
            'ca' => $e->epi?->ss_e_tx_ca,
            'variacao' => $e->variacao?->ss_ev_tx_nome,
            'quantidade' => (int) $e->ss_e_nb_quantidade,
            'data_entrega' => $e->ss_e_tx_data_entrega,
            'vencimento' => $e->ss_e_tx_vencimento,
            'status' => $e->ss_e_tx_status,
            'status_assinatura' => $e->ss_e_tx_status_assinatura,
            'data_assinatura' => $e->ss_e_tx_data_assinatura,
            'tem_assinatura' => !empty($e->ss_e_tx_assinatura),
            'retroativo' => (bool) $e->ss_e_tx_retroativo,
        ])->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'colaborador' => [
                    'nome' => $colaborador->ss_c_tx_nome,
                    'cpf' => $colaborador->ss_c_tx_cpf,
                    'matricula' => $colaborador->ss_c_tx_matricula,
                    'cargo' => $colaborador->ss_c_tx_cargo,
                ],
                'epis' => $data,
            ],
        ]);
    }
}
