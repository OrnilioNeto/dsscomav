<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpiEntrega extends Model
{
    protected $table = 'ss_epi_entrega';
    protected $primaryKey = 'ss_e_nb_id';
    public $timestamps = false;

    protected $fillable = [
        'ss_e_nb_colaborador_id',
        'ss_e_nb_epi_id',
        'ss_e_nb_empresa_id',
        'ss_e_tx_data_entrega',
        'ss_e_nb_quantidade',
        'ss_e_tx_vencimento',
        'ss_e_tx_status',
        'ss_e_tx_assinatura',
        'ss_e_tx_foto',
        'ss_e_tx_observacao',
        'ss_e_tx_justificativa_exclusao',
        'ss_e_nb_userCadastro',
        'ss_e_tx_dataCadastro',
    ];

    public function colaborador()
    {
        return $this->belongsTo(EpiColaborador::class, 'ss_e_nb_colaborador_id', 'ss_c_nb_id');
    }

    public function epi()
    {
        return $this->belongsTo(Epi::class, 'ss_e_nb_epi_id', 'ss_e_nb_id');
    }
}
