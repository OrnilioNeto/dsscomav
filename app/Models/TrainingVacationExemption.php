<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingVacationExemption extends Model
{
    use HasFactory;

    protected $table = 'training_vacation_exemptions';

    protected $fillable = [
        'user_id',
        'training_id',
        'data_inicio',
        'data_fim',
        'motivo',
        'created_by',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
