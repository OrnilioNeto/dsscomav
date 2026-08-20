<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingQuestion extends Model
{
    use HasFactory;

    protected $table = 'training_questions';

    protected $fillable = [
        'training_id',
        'pergunta',
        'opcoes',
        'resposta_correta',
        'ordem',
    ];

    protected $casts = [
        'opcoes' => 'array',
        'resposta_correta' => 'integer',
        'ordem' => 'integer',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class, 'training_id', 'id');
    }
}