<?php

namespace App\Modules\Frota\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FrotaLiberacao extends Model
{
    use BelongsToTenant;

    protected $table = 'frota_liberacoes_treinamento';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'liberado_por',
        'motivo',
        'inicio',
        'fim',
        'revogado_em',
        'revogado_por',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fim' => 'datetime',
        'revogado_em' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function liberadoPor()
    {
        return $this->belongsTo(User::class, 'liberado_por');
    }

    public function revogadoPor()
    {
        return $this->belongsTo(User::class, 'revogado_por');
    }

    public function scopeVigente(Builder $query): Builder
    {
        return $query->whereNull('revogado_em')
            ->where('inicio', '<=', now())
            ->where('fim', '>=', now());
    }

    public function estaVigente(): bool
    {
        return $this->revogado_em === null
            && $this->inicio !== null
            && $this->fim !== null
            && $this->inicio->lte(now())
            && $this->fim->gte(now());
    }

    public function getSituacaoAttribute(): string
    {
        if ($this->revogado_em !== null) {
            return 'Revogada';
        }

        if ($this->inicio !== null && $this->inicio->isFuture()) {
            return 'Agendada';
        }

        if ($this->fim !== null && $this->fim->isPast()) {
            return 'Expirada';
        }

        return 'Vigente';
    }
}
