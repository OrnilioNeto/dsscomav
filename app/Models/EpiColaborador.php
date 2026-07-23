<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpiColaborador extends Model
{
    protected $table = 'ss_colaborador';
    protected $primaryKey = 'ss_c_nb_id';
    public $timestamps = false;

    protected $fillable = [
        'ss_c_tx_nome',
        'ss_c_tx_cpf',
        'ss_c_tx_matricula',
        'ss_c_tx_cargo',
        'ss_c_tx_status',
        'ss_c_nb_empresa_id',
    ];

    public function entregas()
    {
        return $this->hasMany(EpiEntrega::class, 'ss_e_nb_colaborador_id', 'ss_c_nb_id');
    }

    /**
     * Regra 4: Filtra colaboradores elegíveis para recebimento de EPI,
     * removendo diretores e cargos administrativos de diretoria.
     */
    public function scopeElegiveisParaEntrega($query)
    {
        return $query->where('ss_c_tx_status', 'ativo')
            ->where(function ($q) {
                $q->whereNull('ss_c_tx_cargo')
                  ->orWhere(function ($sub) {
                      $sub->where('ss_c_tx_cargo', 'NOT LIKE', '%Diretor%')
                          ->where('ss_c_tx_cargo', 'NOT LIKE', '%Diretoria%')
                          ->where('ss_c_tx_cargo', 'NOT LIKE', '%Executive%')
                          ->where('ss_c_tx_cargo', 'NOT LIKE', '%CEO%')
                          ->where('ss_c_tx_cargo', 'NOT LIKE', '%CFO%')
                          ->where('ss_c_tx_cargo', 'NOT LIKE', '%COO%');
                  });
            });
    }
}
