<?php

namespace App\Modules\Frota\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FrotaJornada extends Model
{
    use BelongsToTenant;

    protected $table = 'frota_jornadas';

    protected $fillable = [
        'tenant_id',
        'frota_veiculo_id',
        'frota_motorista_id',
        'driver_name',
        'driver_cpf',
        'login_time',
        'logout_time',
        'aberta',
        'excess_journey',
        'excess_extended_journey',
        'synced_at',
    ];

    protected $casts = [
        'login_time' => 'datetime',
        'logout_time' => 'datetime',
        'aberta' => 'boolean',
        'excess_journey' => 'boolean',
        'excess_extended_journey' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function veiculo()
    {
        return $this->belongsTo(FrotaVeiculo::class, 'frota_veiculo_id');
    }

    public function motorista()
    {
        return $this->belongsTo(FrotaMotorista::class, 'frota_motorista_id');
    }

    public function scopeAbertas(Builder $query): Builder
    {
        return $query->where('aberta', true);
    }

    public function getDuracaoMinutosAttribute(): ?int
    {
        if (! $this->login_time) {
            return null;
        }

        $fim = $this->logout_time ?? now();

        return max(0, (int) $this->login_time->diffInMinutes($fim, false));
    }
}
