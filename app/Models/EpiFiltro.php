<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Filtro de reposição instalado em uma máscara/respirador que está com o
 * colaborador. Não é um EPI: o descarte encerra apenas o filtro e nunca
 * altera a entrega da máscara (ss_epi_entrega) nem o estoque.
 */
class EpiFiltro extends Model
{
    use Auditable;
    use BelongsToTenant;

    public const MOTIVOS_DESCARTE = [
        'vencido' => 'Vencido',
        'danificado' => 'Danificado',
        'substituicao' => 'Substituição',
        'perda' => 'Perda / Extravio',
        'outro' => 'Outro',
    ];

    protected $table = 'ss_epi_filtro';

    protected $primaryKey = 'ss_ef_nb_id';

    public $timestamps = false;

    protected $fillable = [
        'ss_ef_nb_entrega_id',
        'ss_ef_nb_colaborador_id',
        'ss_ef_nb_epi_id',
        'ss_ef_nb_tipo_id',
        'ss_ef_tx_tipo_nome',
        'ss_ef_tx_ca',
        'ss_ef_nb_vida_util_dias',
        'ss_ef_nb_quantidade',
        'ss_ef_tx_data_instalacao',
        'ss_ef_tx_vencimento',
        'ss_ef_tx_status',
        'ss_ef_tx_motivo_descarte',
        'ss_ef_tx_data_descarte',
        'ss_ef_tx_observacao',
        'ss_ef_tx_assinatura',
        'ss_ef_tx_requer_assinatura',
        'ss_ef_tx_status_assinatura',
        'ss_ef_tx_justificativa_negacao',
        'ss_ef_tx_data_assinatura',
        'ss_ef_tx_grupo_assinatura',
        'ss_ef_nb_userCadastro',
        'ss_ef_tx_dataCadastro',
    ];

    public function entrega()
    {
        return $this->belongsTo(EpiEntrega::class, 'ss_ef_nb_entrega_id', 'ss_e_nb_id');
    }

    public function epi()
    {
        return $this->belongsTo(Epi::class, 'ss_ef_nb_epi_id', 'ss_e_nb_id');
    }

    public function colaborador()
    {
        return $this->belongsTo(EpiColaborador::class, 'ss_ef_nb_colaborador_id', 'ss_c_nb_id');
    }

    public function tipo()
    {
        return $this->belongsTo(EpiFiltroTipo::class, 'ss_ef_nb_tipo_id', 'ss_eft_nb_id');
    }

    public function usuarioCadastro()
    {
        return $this->belongsTo(User::class, 'ss_ef_nb_userCadastro', 'id');
    }

    public function scopeAtivos($query)
    {
        return $query->where('ss_ef_tx_status', 'ativo');
    }

    public function scopePendentesAssinatura($query, $colaboradorId)
    {
        return $query->where('ss_ef_nb_colaborador_id', $colaboradorId)
            ->where('ss_ef_tx_requer_assinatura', true)
            ->where('ss_ef_tx_status_assinatura', 'pendente')
            ->where('ss_ef_tx_status', 'ativo');
    }

    public function getMotivoDescarteLabelAttribute(): ?string
    {
        if (! $this->ss_ef_tx_motivo_descarte) {
            return null;
        }

        return self::MOTIVOS_DESCARTE[$this->ss_ef_tx_motivo_descarte] ?? $this->ss_ef_tx_motivo_descarte;
    }
}
