<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTraining extends Model
{
    use Auditable;
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nome',
        'data_treinamento',
        'data_validade',
        'observacoes',
    ];

    protected $casts = [
        'data_treinamento' => 'date',
        'data_validade' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        if (! $this->data_validade) {
            return false;
        }

        return $this->data_validade->isPast();
    }
}
