<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolgaSaldoMensal extends Model
{
    use BelongsToTenant;

    protected $table = 'folga_saldos_mensais';

    protected $fillable = [
        'user_id',
        'mes',
        'ano',
        'previstas',
        'tiradas',
        'ajustes',
        'saldo_anterior',
        'saldo_acumulado',
        'domingo_cumprido',
        'dias_trabalhados',
        'dias_atestado',
        'dias_licenca',
    ];

    protected $casts = [
        'mes' => 'integer',
        'ano' => 'integer',
        'previstas' => 'integer',
        'tiradas' => 'integer',
        'ajustes' => 'integer',
        'saldo_anterior' => 'integer',
        'saldo_acumulado' => 'integer',
        'domingo_cumprido' => 'boolean',
        'dias_trabalhados' => 'integer',
        'dias_atestado' => 'integer',
        'dias_licenca' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getSaldoDisponivelAttribute(): int
    {
        return $this->saldo_acumulado;
    }

    public function getSaldoStatusAttribute(): string
    {
        if ($this->saldo_acumulado > 0) {
            return 'positivo';
        }
        if ($this->saldo_acumulado < 0) {
            return 'negativo';
        }

        return 'zero';
    }
}
