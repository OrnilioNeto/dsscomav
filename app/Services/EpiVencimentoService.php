<?php

namespace App\Services;

use App\Models\Epi;
use App\Models\EpiEntrega;

/**
 * Regras de vencimento das entregas de EPI: a vida útil do catálogo é contada
 * a partir da data da entrega.
 */
class EpiVencimentoService
{
    /**
     * Calcula o vencimento de uma entrega somando a vida útil (dias) à data
     * da entrega. Retorna 'Y-m-d' ou null quando não há vida útil configurada.
     */
    public function calcular(?string $dataEntrega, int $diasVidaUtil): ?string
    {
        if ($diasVidaUtil <= 0 || empty($dataEntrega)) {
            return null;
        }

        return date('Y-m-d', strtotime("{$dataEntrega} + {$diasVidaUtil} days"));
    }

    /**
     * Recalcula o vencimento das entregas ativas de um EPI. Entregas
     * inativadas ou devolvidas (inclusive itens descartados) não são alteradas.
     */
    public function recalcularPorEpi(int $epiId, int $diasVidaUtil): int
    {
        $recalculadas = 0;

        EpiEntrega::where('ss_e_nb_epi_id', $epiId)
            ->whereNotIn('ss_e_tx_status', ['inativo', 'devolvido'])
            ->get()
            ->each(function (EpiEntrega $entrega) use ($diasVidaUtil, &$recalculadas) {
                $novoVencimento = $this->calcular($entrega->ss_e_tx_data_entrega, $diasVidaUtil);

                $vencimentoAtual = $entrega->ss_e_tx_vencimento
                    ? date('Y-m-d', strtotime($entrega->ss_e_tx_vencimento))
                    : null;

                if ($vencimentoAtual === $novoVencimento) {
                    return;
                }

                $entrega->update(['ss_e_tx_vencimento' => $novoVencimento]);
                $recalculadas++;
            });

        return $recalculadas;
    }

    /**
     * Recalcula o vencimento de todas as entregas ativas, EPI por EPI.
     */
    public function recalcularTodos(): int
    {
        $total = 0;

        Epi::query()->orderBy('ss_e_nb_id')->chunkById(100, function ($epis) use (&$total) {
            foreach ($epis as $epi) {
                $total += $this->recalcularPorEpi(
                    (int) $epi->ss_e_nb_id,
                    (int) $epi->ss_e_nb_vida_util_dias
                );
            }
        });

        return $total;
    }
}
