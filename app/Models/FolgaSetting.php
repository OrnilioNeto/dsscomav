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
    ];

    protected $casts = [
        'data_inicio_controle' => 'date',
        'exige_domingo' => 'boolean',
        'bloquear_sem_domingo' => 'boolean',
    ];

    public static function firstOrCreateDefault(): self
    {
        $settings = self::first();
        if (! $settings) {
            $settings = self::create([
                'dias_para_folga' => 6,
                'exige_domingo' => true,
                'bloquear_sem_domingo' => false,
            ]);
        }

        return $settings;
    }
}
