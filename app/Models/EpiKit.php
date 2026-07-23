<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpiKit extends Model
{
    protected $table = 'ss_kit';
    protected $primaryKey = 'ss_k_nb_id';
    public $timestamps = false;

    protected $fillable = [
        'ss_k_tx_nome',
        'ss_k_tx_status',
    ];

    public function itens()
    {
        return $this->hasMany(EpiKitItem::class, 'ss_ki_nb_kit_id', 'ss_k_nb_id');
    }
}
