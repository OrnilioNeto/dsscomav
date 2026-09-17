<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolgaDia extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id',
        'data',
        'tipo',
        'motivo_folga',
        'domingo_ref',
        'observacao',
        'origem',
        'lancado_por',
        'lancado_em',
    ];

    protected $casts = [
        'data' => 'date',
        'domingo_ref' => 'date',
        'lancado_em' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lancadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lancado_por');
    }
}
