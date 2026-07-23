<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpiEstoque extends Model
{
    protected $table = 'ss_epi_estoque';
    protected $primaryKey = 'ss_e_nb_id';
    public $timestamps = false;

    protected $fillable = [
        'ss_e_nb_epi_id',
        'ss_e_nb_empresa_id',
        'ss_e_nb_quantidade',
        'ss_e_tx_tipo',
        'ss_e_db_valor_unitario',
        'ss_e_db_valor_total',
        'ss_e_tx_data_recebimento',
        'ss_e_tx_validade',
        'ss_e_tx_chave_nf',
        'ss_e_tx_fornecedor',
        'ss_e_tx_data',
        'ss_e_tx_motivo',
        'ss_e_tx_foto',
        'ss_e_nb_userCadastro',
    ];

    public function epi()
    {
        return $this->belongsTo(Epi::class, 'ss_e_nb_epi_id', 'ss_e_nb_id');
    }
}
