<?php

namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjetoPedagogicoTraining extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'projeto_pedagogico_trainings';

    protected $fillable = [
        'projeto_pedagogico_id',
        'training_id',
    ];

    public function projetoPedagogico()
    {
        return $this->belongsTo(ProjetoPedagogico::class, 'projeto_pedagogico_id', 'id');
    }

    public function training()
    {
        return $this->belongsTo(Training::class, 'training_id', 'id');
    }
}