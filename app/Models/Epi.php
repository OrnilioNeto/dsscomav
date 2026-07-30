<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Epi extends Model
{
    protected $table = 'ss_epi';
    protected $primaryKey = 'ss_e_nb_id';
    public $timestamps = false;

    protected $fillable = [
        'ss_e_tx_grupo',
        'ss_e_tx_subgrupo',
        'ss_e_tx_item',
        'ss_e_tx_descricao',
        'ss_e_tx_fabricante',
        'ss_e_tx_ca',
        'ss_e_tx_validade_ca',
        'ss_e_nb_vida_util_dias',
        'ss_e_tx_status',
        'ss_e_tx_cadastro_tipo',
        'ss_e_tx_foto',
        'ss_e_tx_modelo',
        'ss_e_nb_userCadastro',
        'ss_e_tx_dataCadastro',
    ];

    public function estoques()
    {
        return $this->hasMany(EpiEstoque::class, 'ss_e_nb_epi_id', 'ss_e_nb_id');
    }

    public function entregas()
    {
        return $this->hasMany(EpiEntrega::class, 'ss_e_nb_epi_id', 'ss_e_nb_id');
    }

    public function variacoes()
    {
        return $this->hasMany(EpiVariacao::class, 'ss_ev_nb_epi_id', 'ss_e_nb_id');
    }

    /**
     * Calcula o saldo do EPI para uma filial específica (0/null = Matriz) ou consolidado.
     */
    public function getSaldoPorFilial($empresaId = null, $variacaoId = null): int
    {
        $query = DB::table('ss_epi_estoque')
            ->where('ss_e_nb_epi_id', $this->ss_e_nb_id);

        if ($empresaId !== null && $empresaId !== '') {
            $empresaIdInt = (int)$empresaId;
            if ($empresaIdInt === 0) {
                $query->where(function ($q) {
                    $q->whereNull('ss_e_nb_empresa_id')
                      ->orWhere('ss_e_nb_empresa_id', 0);
                });
            } else {
                $query->where('ss_e_nb_empresa_id', $empresaIdInt);
            }
        }

        if ($variacaoId !== null) {
            $query->where('ss_e_nb_variacao_id', $variacaoId);
        }

        $entradas = (int) (clone $query)->where('ss_e_tx_tipo', 'entrada')->sum('ss_e_nb_quantidade');
        $saidas = (int) (clone $query)->whereIn('ss_e_tx_tipo', ['saida', 'substituicao'])->sum('ss_e_nb_quantidade');

        return max(0, $entradas - $saidas);
    }

    /**
     * Retorna o saldo total somado em TODAS as filiais da rede.
     */
    public function getSaldoTotalRede($variacaoId = null): int
    {
        $query = DB::table('ss_epi_estoque')
            ->where('ss_e_nb_epi_id', $this->ss_e_nb_id);

        if ($variacaoId !== null) {
            $query->where('ss_e_nb_variacao_id', $variacaoId);
        }

        $entradas = (int) (clone $query)->where('ss_e_tx_tipo', 'entrada')->sum('ss_e_nb_quantidade');
        $saidas = (int) (clone $query)->whereIn('ss_e_tx_tipo', ['saida', 'substituicao'])->sum('ss_e_nb_quantidade');

        return max(0, $entradas - $saidas);
    }
}
