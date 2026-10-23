<?php

namespace App\Http\Controllers;

use App\Models\EpiColaborador;
use App\Models\EpiEntrega;
use App\Models\EpiFiltro;
use App\Models\EpiFiltroTipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Controle dos filtros de reposição instalados nas máscaras/respiradores que
 * estão com os colaboradores. Os filtros NÃO são EPIs: nenhum fluxo deste
 * controller movimenta estoque, cria devolução ou altera a entrega da máscara.
 */
class EpiFiltroController extends Controller
{
    private function ensureTablesExist(): void
    {
        app(EpiController::class)->ensureTablesExist();
    }

    /**
     * Vincula um filtro à máscara (entrega ativa de EPI) de um colaborador.
     * A assinatura pode ser coletada na hora (canvas) ou ficar pendente para o
     * colaborador assinar na página de assinaturas do módulo.
     */
    public function store(Request $request)
    {
        $this->ensureTablesExist();

        $validated = $request->validate([
            'ss_ef_nb_entrega_id' => 'required|integer',
            'ss_ef_nb_tipo_id' => 'required|integer',
            'ss_ef_nb_quantidade' => 'required|integer|min:1|max:99',
            'ss_ef_tx_data_instalacao' => 'required|date',
            'ss_ef_tx_vencimento' => 'required|date|after_or_equal:ss_ef_tx_data_instalacao',
            'ss_ef_tx_observacao' => 'nullable|string|max:2000',
            'ss_ef_tx_assinatura' => 'nullable|string',
        ]);

        $entrega = EpiEntrega::with('epi')
            ->whereNotIn('ss_e_tx_status', ['inativo', 'devolvido'])
            ->find($validated['ss_ef_nb_entrega_id']);

        if (! $entrega) {
            return $this->resposta($request, 'error', 'Entrega (máscara) não encontrada ou já encerrada.', 422);
        }

        $tipo = EpiFiltroTipo::ativos()->find($validated['ss_ef_nb_tipo_id']);

        if (! $tipo) {
            return $this->resposta($request, 'error', 'Tipo de filtro não encontrado ou inativo.', 422);
        }

        $assinatura = $request->input('ss_ef_tx_assinatura');
        $requerAssinatura = empty($assinatura);

        EpiFiltro::create([
            'ss_ef_nb_entrega_id' => $entrega->ss_e_nb_id,
            'ss_ef_nb_colaborador_id' => $entrega->ss_e_nb_colaborador_id,
            'ss_ef_nb_epi_id' => $entrega->ss_e_nb_epi_id,
            'ss_ef_nb_tipo_id' => $tipo->ss_eft_nb_id,
            'ss_ef_tx_tipo_nome' => $tipo->ss_eft_tx_nome,
            'ss_ef_tx_ca' => $tipo->ss_eft_tx_ca,
            'ss_ef_nb_vida_util_dias' => (int) $tipo->ss_eft_nb_vida_util_dias,
            'ss_ef_nb_quantidade' => (int) $validated['ss_ef_nb_quantidade'],
            'ss_ef_tx_data_instalacao' => $validated['ss_ef_tx_data_instalacao'],
            'ss_ef_tx_vencimento' => $validated['ss_ef_tx_vencimento'],
            'ss_ef_tx_status' => 'ativo',
            'ss_ef_tx_observacao' => $validated['ss_ef_tx_observacao'] ?? null,
            'ss_ef_tx_assinatura' => $assinatura ?: null,
            'ss_ef_tx_requer_assinatura' => $requerAssinatura,
            'ss_ef_tx_status_assinatura' => $requerAssinatura ? 'pendente' : 'assinada',
            'ss_ef_tx_data_assinatura' => $requerAssinatura ? null : now(),
            'ss_ef_tx_grupo_assinatura' => $requerAssinatura ? (string) Str::uuid() : null,
            'ss_ef_nb_userCadastro' => Auth::id(),
            'ss_ef_tx_dataCadastro' => now(),
        ]);

        $mensagem = 'Filtro vinculado com sucesso!';
        if ($requerAssinatura) {
            $mensagem .= ' Assinatura pendente: o colaborador deve assinar na página de assinaturas.';
        }

        return $this->resposta($request, 'success', $mensagem);
    }

    /**
     * Ajusta manualmente a data de vencimento de um filtro.
     */
    public function editarVencimento(Request $request, $id)
    {
        $this->ensureTablesExist();

        $request->validate([
            'ss_ef_tx_vencimento' => 'required|date',
        ]);

        $filtro = EpiFiltro::findOrFail($id);
        $filtro->update(['ss_ef_tx_vencimento' => $request->input('ss_ef_tx_vencimento')]);

        $novaData = date('d/m/Y', strtotime($request->input('ss_ef_tx_vencimento')));

        return $this->resposta($request, 'success', "Vencimento do filtro #{$filtro->ss_ef_nb_id} atualizado para {$novaData}!");
    }

    /**
     * Descarte do filtro (vencido, danificado, substituição, perda...).
     * Encerra APENAS o filtro: a máscara do colaborador permanece intacta,
     * sem devolução e sem movimentação de estoque.
     */
    public function descartar(Request $request, $id)
    {
        $this->ensureTablesExist();

        $request->validate([
            'ss_ef_tx_motivo_descarte' => 'required|string|in:'.implode(',', array_keys(EpiFiltro::MOTIVOS_DESCARTE)),
            'ss_ef_tx_data_descarte' => 'nullable|date',
            'ss_ef_tx_observacao' => 'nullable|string|max:2000',
        ]);

        $filtro = EpiFiltro::findOrFail($id);

        if ($filtro->ss_ef_tx_status === 'descartado') {
            return $this->resposta($request, 'error', 'Este filtro já foi descartado.', 422);
        }

        $filtro->update([
            'ss_ef_tx_status' => 'descartado',
            'ss_ef_tx_motivo_descarte' => $request->input('ss_ef_tx_motivo_descarte'),
            'ss_ef_tx_data_descarte' => $request->input('ss_ef_tx_data_descarte') ?: now()->format('Y-m-d'),
            'ss_ef_tx_observacao' => $request->input('ss_ef_tx_observacao') ?: $filtro->ss_ef_tx_observacao,
        ]);

        return $this->resposta($request, 'success', 'Filtro descartado com sucesso. A máscara do colaborador não foi alterada.');
    }

    /**
     * Exclui um lançamento de filtro (correção de erro de digitação).
     */
    public function destroy(Request $request, $id)
    {
        $this->ensureTablesExist();

        $filtro = EpiFiltro::findOrFail($id);
        $filtro->delete();

        return $this->resposta($request, 'success', "Lançamento do filtro #{$id} excluído com sucesso!");
    }

    /**
     * Colaborador assina o vínculo do filtro (mesmo fluxo das entregas de EPI).
     */
    public function assinar(Request $request, $id)
    {
        $this->ensureTablesExist();

        $colaborador = $this->colaboradorAutenticado();

        if (! $colaborador) {
            return $this->resposta($request, 'error', 'Colaborador não encontrado!', 404);
        }

        $filtro = EpiFiltro::pendentesAssinatura($colaborador->ss_c_nb_id)->findOrFail($id);

        $request->validate([
            'ss_ef_tx_assinatura' => 'required|string',
        ]);

        $grupo = $filtro->ss_ef_tx_grupo_assinatura;
        $query = EpiFiltro::pendentesAssinatura($colaborador->ss_c_nb_id);

        if ($grupo) {
            $query->where('ss_ef_tx_grupo_assinatura', $grupo);
        } else {
            $query->where('ss_ef_nb_id', $id);
        }

        $query->update([
            'ss_ef_tx_assinatura' => $request->input('ss_ef_tx_assinatura'),
            'ss_ef_tx_status_assinatura' => 'assinada',
            'ss_ef_tx_data_assinatura' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Assinatura do filtro registrada com sucesso!']);
        }

        return redirect()->route('epi.assinaturas')->with('success', 'Assinatura do filtro registrada com sucesso!');
    }

    /**
     * Colaborador recusa a assinatura do vínculo do filtro.
     */
    public function negar(Request $request, $id)
    {
        $this->ensureTablesExist();

        $colaborador = $this->colaboradorAutenticado();

        if (! $colaborador) {
            return $this->resposta($request, 'error', 'Colaborador não encontrado!', 404);
        }

        $filtro = EpiFiltro::pendentesAssinatura($colaborador->ss_c_nb_id)->findOrFail($id);

        $grupo = $filtro->ss_ef_tx_grupo_assinatura;
        $query = EpiFiltro::pendentesAssinatura($colaborador->ss_c_nb_id);

        if ($grupo) {
            $query->where('ss_ef_tx_grupo_assinatura', $grupo);
        } else {
            $query->where('ss_ef_nb_id', $id);
        }

        $query->update([
            'ss_ef_tx_status_assinatura' => 'negada',
            'ss_ef_tx_justificativa_negacao' => $request->input('ss_ef_tx_justificativa_negacao'),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Assinatura do filtro recusada. A gestão será notificada.']);
        }

        return redirect()->route('epi.assinaturas')->with('success', 'Assinatura do filtro recusada.');
    }

    /**
     * Gestão: reenviar (volta a pendente) ou cancelar (descarta) um filtro
     * com assinatura negada. O cancelamento descarta apenas o filtro.
     */
    public function alterarFiltro(Request $request, $id)
    {
        $this->ensureTablesExist();

        $filtro = EpiFiltro::findOrFail($id);

        if ($filtro->ss_ef_tx_status_assinatura !== 'negada') {
            return redirect()->back()->with('error', 'Apenas filtros com assinatura negada podem ser alterados.');
        }

        $action = $request->input('action', 'cancelar');
        $justificativa = $request->input('ss_ef_tx_justificativa_exclusao');

        if ($action === 'cancelar') {
            $filtro->update([
                'ss_ef_tx_status' => 'descartado',
                'ss_ef_tx_motivo_descarte' => 'outro',
                'ss_ef_tx_data_descarte' => now()->format('Y-m-d'),
                'ss_ef_tx_observacao' => $justificativa
                    ? '[Gestão] '.$justificativa
                    : 'Filtro descartado pela gestão após recusa do colaborador.',
            ]);

            return redirect()->back()->with('success', 'Filtro descartado (cancelado pela gestão). A máscara não foi alterada.');
        }

        if ($action === 'reativar') {
            $filtro->update([
                'ss_ef_tx_status_assinatura' => 'pendente',
                'ss_ef_tx_justificativa_negacao' => null,
                'ss_ef_tx_assinatura' => null,
                'ss_ef_tx_grupo_assinatura' => $filtro->ss_ef_tx_grupo_assinatura ?: (string) Str::uuid(),
            ]);

            return redirect()->back()->with('success', 'Filtro reenviado para assinatura do colaborador!');
        }

        return redirect()->back()->with('error', 'Ação inválida.');
    }

    /**
     * Cadastro de tipo de filtro no mini-catálogo.
     */
    public function tipoStore(Request $request)
    {
        $this->ensureTablesExist();

        $validated = $request->validate([
            'ss_eft_tx_nome' => 'required|string|max:150',
            'ss_eft_tx_fabricante' => 'nullable|string|max:150',
            'ss_eft_tx_ca' => 'nullable|string|max:50',
            'ss_eft_nb_vida_util_dias' => 'nullable|integer|min:0|max:3650',
        ]);

        EpiFiltroTipo::create([
            'ss_eft_tx_nome' => $validated['ss_eft_tx_nome'],
            'ss_eft_tx_fabricante' => $validated['ss_eft_tx_fabricante'] ?? null,
            'ss_eft_tx_ca' => $validated['ss_eft_tx_ca'] ?? null,
            'ss_eft_nb_vida_util_dias' => (int) ($validated['ss_eft_nb_vida_util_dias'] ?? 0),
            'ss_eft_tx_status' => 'ativo',
        ]);

        return $this->resposta($request, 'success', 'Tipo de filtro cadastrado com sucesso!');
    }

    /**
     * Atualiza os dados de um tipo de filtro do mini-catálogo.
     */
    public function tipoUpdate(Request $request, $id)
    {
        $this->ensureTablesExist();

        $tipo = EpiFiltroTipo::findOrFail($id);

        $validated = $request->validate([
            'ss_eft_tx_nome' => 'required|string|max:150',
            'ss_eft_tx_fabricante' => 'nullable|string|max:150',
            'ss_eft_tx_ca' => 'nullable|string|max:50',
            'ss_eft_nb_vida_util_dias' => 'nullable|integer|min:0|max:3650',
        ]);

        $tipo->update([
            'ss_eft_tx_nome' => $validated['ss_eft_tx_nome'],
            'ss_eft_tx_fabricante' => $validated['ss_eft_tx_fabricante'] ?? null,
            'ss_eft_tx_ca' => $validated['ss_eft_tx_ca'] ?? null,
            'ss_eft_nb_vida_util_dias' => (int) ($validated['ss_eft_nb_vida_util_dias'] ?? 0),
        ]);

        return $this->resposta($request, 'success', 'Tipo de filtro atualizado com sucesso!');
    }

    /**
     * Ativa/inativa um tipo de filtro do mini-catálogo.
     */
    public function tipoToggle(Request $request, $id)
    {
        $this->ensureTablesExist();

        $tipo = EpiFiltroTipo::findOrFail($id);
        $novoStatus = $tipo->ss_eft_tx_status === 'ativo' ? 'inativo' : 'ativo';
        $tipo->update(['ss_eft_tx_status' => $novoStatus]);

        $mensagem = $novoStatus === 'ativo' ? 'Tipo de filtro reativado!' : 'Tipo de filtro inativado!';

        return $this->resposta($request, 'success', $mensagem);
    }

    /**
     * Exclui um tipo de filtro (bloqueado quando já existe lançamento usando-o).
     */
    public function tipoDestroy(Request $request, $id)
    {
        $this->ensureTablesExist();

        $tipo = EpiFiltroTipo::findOrFail($id);

        $emUso = EpiFiltro::where('ss_ef_nb_tipo_id', $tipo->ss_eft_nb_id)->count();
        if ($emUso > 0) {
            return $this->resposta($request, 'error', "Não é possível excluir: este tipo já está em uso em {$emUso} lançamento(s). Inative-o em vez de excluir.", 422);
        }

        $tipo->delete();

        return $this->resposta($request, 'success', 'Tipo de filtro excluído com sucesso!');
    }

    private function colaboradorAutenticado(): ?EpiColaborador
    {
        return EpiColaborador::where('ss_c_tx_cpf', Auth::user()->cpf)->first();
    }

    private function resposta(Request $request, string $status, string $message, int $code = 200)
    {
        if ($request->wantsJson()) {
            return response()->json(['status' => $status, 'message' => $message], $code);
        }

        return redirect()->back()->with($status === 'success' ? 'success' : 'error', $message);
    }
}
