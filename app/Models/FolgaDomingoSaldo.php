<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolgaDomingoSaldo extends Model
{
    use BelongsToTenant;

    protected $table = 'folga_domingo_saldos';

    protected $fillable = [
        'user_id',
        'mes',
        'ano',
        'creditos_ganhos',
        'creditos_usados',
        'saldo_mes',
        'saldo_acumulado',
    ];

    protected $casts = [
        'mes' => 'integer',
        'ano' => 'integer',
        'creditos_ganhos' => 'integer',
        'creditos_usados' => 'integer',
        'saldo_mes' => 'integer',
        'saldo_acumulado' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
