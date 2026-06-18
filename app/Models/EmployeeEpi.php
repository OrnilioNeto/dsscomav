<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeEpi extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nome',
        'ca',
        'quantidade',
        'data_entrega',
        'observacoes',
    ];

    protected $casts = [
        'data_entrega' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
