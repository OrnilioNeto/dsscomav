<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    use HasFactory;

    protected $table = 'user_progress';

    protected $fillable = [
        'user_id',
        'training_id',
        'data_inicio',
        'tempo_assistido',
        'data_conclusao',
        'concluido',
        'porcentagem_assistida',
        'avaliacao_aprovada',
        'avaliacao_tentativas',
        'avaliacao_resposta_usuario',
        'avaliacao_respostas_json',
        'avaliacao_nota',
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
        'data_conclusao' => 'datetime',
        'concluido' => 'boolean',
        'avaliacao_aprovada' => 'boolean',
        'avaliacao_tentativas' => 'integer',
        'avaliacao_resposta_usuario' => 'integer',
        'avaliacao_respostas_json' => 'array',
        'avaliacao_nota' => 'integer',
        'porcentagem_assistida' => 'integer',
    ];

    /**
     * Data em que a validade do treinamento expira para este colaborador
     * (data de conclusão + dias de validade configurados no treinamento).
     * Retorna null quando o treinamento não possui validade.
     */
    public function getDataValidadeAttribute()
    {
        $training = $this->training;
        if (!$training || !$training->dias_validade || !$this->data_conclusao) {
            return null;
        }

        return $this->data_conclusao->copy()->addDays((int) $training->dias_validade);
    }

    public function getStatusValidadeAttribute(): string
    {
        $dataValidade = $this->data_validade;
        if (!$dataValidade) {
            return 'sem_validade';
        }

        if ($dataValidade->isPast()) {
            return 'vencido';
        }

        if ($dataValidade->lte(now()->addDays(30))) {
            return 'vencendo';
        }

        return 'valido';
    }

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
