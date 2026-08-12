<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpiDevolucao extends Model
{
    protected $table = 'ss_epi_devolucao';

    protected $primaryKey = 'ss_ed_nb_id';

    public $timestamps = false;

    protected $fillable = [
        'ss_ed_nb_entrega_id',
        'ss_ed_nb_epi_id',
        'ss_ed_nb_colaborador_id',
        'ss_ed_nb_empresa_id',
        'ss_ed_nb_variacao_id',
        'ss_ed_nb_quantidade',
        'ss_ed_tx_motivo',
        'ss_ed_tx_destino',
        'ss_ed_tx_status',
        'ss_ed_tx_resultado_inspecao',
        'ss_ed_tx_observacao',
        'ss_ed_nb_userRegistro',
        'ss_ed_tx_data_registro',
        'ss_ed_nb_userDecisao',
        'ss_ed_tx_data_decisao',
    ];

    public function entrega()
    {
        return $this->belongsTo(EpiEntrega::class, 'ss_ed_nb_entrega_id', 'ss_e_nb_id');
    }

    public function epi()
    {
        return $this->belongsTo(Epi::class, 'ss_ed_nb_epi_id', 'ss_e_nb_id');
    }

    public function colaborador()
    {
        return $this->belongsTo(EpiColaborador::class, 'ss_ed_nb_colaborador_id', 'ss_c_nb_id');
    }

    public function variacao()
    {
        return $this->belongsTo(EpiVariacao::class, 'ss_ed_nb_variacao_id', 'ss_ev_nb_id');
    }

    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'ss_ed_nb_userRegistro', 'id');
    }

    public function usuarioDecisao()
    {
        return $this->belongsTo(User::class, 'ss_ed_nb_userDecisao', 'id');
    }
}
