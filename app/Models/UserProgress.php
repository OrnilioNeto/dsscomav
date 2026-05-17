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
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
        'data_conclusao' => 'datetime',
        'concluido' => 'boolean',
        'avaliacao_aprovada' => 'boolean',
        'avaliacao_tentativas' => 'integer',
        'avaliacao_resposta_usuario' => 'integer',
        'porcentagem_assistida' => 'integer',
    ];

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
