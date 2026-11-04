<?php

namespace App\Modules\Frota\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FrotaVeiculo extends Model
{
    use BelongsToTenant;

    protected $table = 'frota_veiculos';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'address',
        'license_plate',
        'trip_name',
        'ativo',
        'synced_at',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function motoristas()
    {
        return $this->hasMany(FrotaMotorista::class, 'veiculo_atual_id');
    }

    public function jornadas()
    {
        return $this->hasMany(FrotaJornada::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function getIdentificacaoAttribute(): string
    {
        $partes = array_filter([$this->name, $this->license_plate]);

        return $partes !== [] ? implode(' • ', $partes) : ('Veículo #'.$this->code);
    }
}
