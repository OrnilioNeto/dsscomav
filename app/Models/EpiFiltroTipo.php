<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class EpiFiltroTipo extends Model
{
    use Auditable;
    use BelongsToTenant;

    protected $table = 'ss_epi_filtro_tipo';

    protected $primaryKey = 'ss_eft_nb_id';

    public $timestamps = false;

    protected $fillable = [
        'ss_eft_tx_nome',
        'ss_eft_tx_fabricante',
        'ss_eft_tx_ca',
        'ss_eft_nb_vida_util_dias',
        'ss_eft_tx_status',
    ];

    public function filtros()
    {
        return $this->hasMany(EpiFiltro::class, 'ss_ef_nb_tipo_id', 'ss_eft_nb_id');
    }

    public function scopeAtivos($query)
    {
        return $query->where('ss_eft_tx_status', 'ativo');
    }
}
