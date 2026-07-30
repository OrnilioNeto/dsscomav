<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpiVariacao extends Model
{
    protected $table = 'ss_epi_variacao';
    protected $primaryKey = 'ss_ev_nb_id';
    public $timestamps = false;

    protected $fillable = [
        'ss_ev_nb_epi_id',
        'ss_ev_tx_nome',
        'ss_ev_tx_status',
    ];

    public function epi()
    {
        return $this->belongsTo(Epi::class, 'ss_ev_nb_epi_id', 'ss_e_nb_id');
    }
}
