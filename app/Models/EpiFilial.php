<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EpiFilial extends Model
{
    use HasFactory;

    protected $table = 'ss_filial';
    protected $primaryKey = 'ss_f_nb_id';
    public $timestamps = false;

    protected $fillable = [
        'ss_f_tx_nome',
        'ss_f_tx_codigo',
        'ss_f_tx_cidade',
        'ss_f_tx_status',
    ];
}
