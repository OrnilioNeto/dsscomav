<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'training_id',
        'codigo_certificado',
        'data_emissao',
        'data_inicio_assistencia',
        'data_finalizacao_assistencia',
        'tempo_assistido_segundos',
        'porcentagem_assistida',
        'caminho_arquivo',
        'valido',
    ];

    protected $casts = [
        'data_emissao' => 'datetime',
        'data_inicio_assistencia' => 'datetime',
        'data_finalizacao_assistencia' => 'datetime',
        'valido' => 'boolean',
        'tempo_assistido_segundos' => 'integer',
        'porcentagem_assistida' => 'integer',
    ];

    // Converte timestamps persistidos em UTC para timezone local da aplicacao.
    public function getDataEmissaoAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value, 'UTC')->setTimezone(config('app.timezone'));
        }

        return $value;
    }

    public function getDataInicioAssistenciaAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value, 'UTC')->setTimezone(config('app.timezone'));
        }

        return $value;
    }

    public function getDataFinalizacaoAssistenciaAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value, 'UTC')->setTimezone(config('app.timezone'));
        }

        return $value;
    }

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function getValidationUrlAttribute(): string
    {
        return route('validar.certificado', $this->codigo_certificado);
    }

    public function getQrCodeUrlAttribute(): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($this->validation_url);
    }

    // Metodos auxiliares
    public function generateCodigoUnico()
    {
        return strtoupper(
            substr(md5($this->user_id . $this->training_id . time()), 0, 12)
        );
    }
}
