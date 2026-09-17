<?php

namespace App\Modules\Exemplo\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ExemploRegistro extends Model
{
    use Auditable, BelongsToTenant;

    protected $table = 'exemplo_registros';

    protected $fillable = [
        'nome',
    ];
}
