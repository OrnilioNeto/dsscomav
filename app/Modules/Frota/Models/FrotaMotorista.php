<?php

namespace App\Modules\Frota\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FrotaMotorista extends Model
{
    use BelongsToTenant;

    public const STATUS_ONLINE = 'online';

    public const STATUS_OFFLINE = 'offline';

    public const STATUS_DESCONHECIDO = 'desconhecido';

    protected $table = 'frota_motoristas';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'cpf',
        'user_id',
        'status',
        'verificado_em',
        'ultimo_login_em',
        'ultimo_logout_em',
        'ultimo_online_em',
        'veiculo_atual_id',
        'ultimo_erro',
        'ativo',
        'synced_at',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'verificado_em' => 'datetime',
        'ultimo_login_em' => 'datetime',
        'ultimo_logout_em' => 'datetime',
        'ultimo_online_em' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function veiculoAtual()
    {
        return $this->belongsTo(FrotaVeiculo::class, 'veiculo_atual_id');
    }

    public function jornadas()
    {
        return $this->hasMany(FrotaJornada::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function getCpfDigitosAttribute(): string
    {
        return preg_replace('/\D/', '', (string) $this->cpf) ?? '';
    }

    public function estaOnline(): bool
    {
        return $this->status === self::STATUS_ONLINE;
    }

    /**
     * Horas da sessão aberta (null quando não há login conhecido).
     */
    public function horasSessao(): ?float
    {
        if (! $this->ultimo_login_em) {
            return null;
        }

        $minutos = $this->ultimo_login_em->diffInMinutes(now(), false);

        return $minutos > 0 ? round($minutos / 60, 1) : 0.0;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ONLINE => 'Online',
            self::STATUS_OFFLINE => 'Offline',
            default => 'Não verificado',
        };
    }
}
