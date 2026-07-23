<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpiKitItem extends Model
{
    protected $table = 'ss_kit_item';
    protected $primaryKey = 'ss_ki_nb_id';
    public $timestamps = false;

    protected $fillable = [
        'ss_ki_nb_kit_id',
        'ss_ki_nb_epi_id',
        'ss_ki_nb_quantidade',
    ];

    public function kit()
    {
        return $this->belongsTo(EpiKit::class, 'ss_ki_nb_kit_id', 'ss_k_nb_id');
    }

    public function epi()
    {
        return $this->belongsTo(Epi::class, 'ss_ki_nb_epi_id', 'ss_e_nb_id');
    }
}
