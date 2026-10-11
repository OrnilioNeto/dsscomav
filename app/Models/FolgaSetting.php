<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class FolgaSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'dias_para_folga',
        'data_inicio_controle',
        'exige_domingo',
        'bloquear_sem_domingo',
        'incluir_motorista_monitor',
    ];

    protected $casts = [
        'data_inicio_controle' => 'date',
        'exige_domingo' => 'boolean',
        'bloquear_sem_domingo' => 'boolean',
        'incluir_motorista_monitor' => 'boolean',
    ];

    public static function firstOrCreateDefault(): self
    {
        $settings = self::first();
        if (! $settings) {
            $settings = self::create([
                'dias_para_folga' => 6,
                'exige_domingo' => true,
                'bloquear_sem_domingo' => false,
                'incluir_motorista_monitor' => true,
            ]);
        }

        return $settings;
    }

    /**
     * Tipos de usuário incluídos no controle de folgas, conforme a
     * configuração atual ("motorista" sempre; monitor é opcional).
     *
     * @return array<int, string>
     */
    public static function tiposMotorista(): array
    {
        return self::firstOrCreateDefault()->incluir_motorista_monitor
            ? User::TIPOS_MOTORISTA
            : ['motorista'];
    }
}
